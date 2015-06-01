<?php
/**
 * Default mailer component
 *
 * Date: 13.01.15
 * Time: 10:52
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	13.01.2015/goshi 
 */

namespace webtFramework\Components\Mail;

class oMailDefault extends oMailAbstract {

    protected function _createAddress($email, $name = null){

        return $name ? '=?'.$this->_mail_encoding.'?B?'.base64_encode($name)."?= <".$email.">" : $email;

    }

    public function send($addresses = null){

        // init local vars
        $bound = md5(time());
        $content_encoding = 'base64';
        $message_id = '';

        if (!$this->_from)
            $this->_from = $this->_p->getVar('mail')['default_from_mail'];

        $subject = "=?".$this->_mail_encoding."?B?".base64_encode($this->_subject)."?=\n";
        $from = $this->_createAddress($this->_from, $this->_fromName);

        $headers = $this->_custom_headers;

        $images = '';

        $content_type = null;

        $eol = "\n";
        if ($this->_mail_type == 'html') {

            $headers[] = 'Mime-Version: 1.0';

            if ($this->_is_embed_images){

                preg_match_all("/[^(]http:\/\/([w]{3}?\.)?".$this->_p->getVar('server_name')."([A-Za-z0-9\/\.\+\_\-\%]+(jpg|gif|png|jpeg))/iu", $this->_body, $img); // |doc|pdf|docx|zip|rar|pages|xls|odf|ods

                $img = array_flip($img[2]);
                if (count($img) > 0) {
                    $images .= $eol.$eol;

                    $content_type = 'multipart/related';

                    foreach($img as $file_name => $value){
                        if (file_exists($this->_p->getVar('DOC_DIR').$file_name)) {
                            $img[$file_name] = md5($file_name);
                            $this->_body = str_replace('http://www.'.$this->_p->getVar('server_name').$file_name,"cid:".$img[$file_name], $this->_body);
                            $this->_body = str_replace('http://'.$this->_p->getVar('server_name').$file_name,"cid:".$img[$file_name], $this->_body);
                            $images .= "--".$bound.$eol;
                            $images .= "Content-Type: application/octet-stream; name=".basename($this->_p->getVar('DOC_DIR').$file_name).$eol;
                            $images .= "Content-Transfer-Encoding: base64".$eol;
                            $images .= "Content-ID: <".$img[$file_name].">".$eol.$eol;
                            $file_name = urldecode($file_name);
                            $f = fopen($this->_p->getVar('DOC_DIR').$file_name,"rb");
                            $images .= chunk_split(base64_encode(fread($f, filesize($this->_p->getVar('DOC_DIR').$file_name))));
                            $images .= $eol.$eol;
                            fclose($f);
                        }
                    }
                }

            }
        }

        // we use SERVER_NAME for compiled templates of smarty for cron and for web :(
        $this->_p->tpl->remove('_mailer_delivery'.$this->_p->getVar('server_name'));
        $this->_p->tpl->add('_mailer_delivery'.$this->_p->getVar('server_name'), $this->_body, false, true);
        $this->_body = $this->_p->tpl->get('_mailer_delivery'.$this->_p->getVar('server_name'));

        // tracking links
        if ($this->_is_track_links){

            $this->_body = $this->_trackLinks($this->_body, $this->_mail_type);

        }

        // init headers
        $headers[] = "From: ".$from;
        if ($this->_replyto && !empty($this->_replyto)){

            $replyTo = array();
            foreach ($this->_replyto as $k => $v){
                $replyTo[] = $this->_createAddress($k, $v);
            }
            $headers[] = "Reply-To: ".join(';', $replyTo);
        }


        if ($this->_mail_type == "html"){
            $headers[] = "Content-Type: ".($content_type != '' ? $content_type : 'multipart/mixed' ).";charset=".$this->_mail_encoding.";\n boundary=\"".$bound."\"".$eol."--".$bound;
            $headers[] = "Content-Type: text/html; charset=".$this->_mail_encoding;

            // by default sending in base 64
            if ($content_encoding != 'base64'){
                $headers[] = "Content-Transfer-Encoding: 8bit";
            } else {
                $headers[] = "Content-Transfer-Encoding: base64";
                $this->_body = chunk_split(base64_encode($this->_p->response->compressHtml($this->_body)));
            }

        } else {
            $headers[] = "Content-Type: text/plain; charset=".$this->_mail_encoding;
        }
        $this->_body .= $images;

        //dump($this->_body, false);
        $response = true;

        if ($this->_is_use_message_id){
            $message_id = $this->getMessageIdPrefix();
        }

        if ($addresses){

            $addresses = $this->_convertAddresses($addresses);

        } else {
            $addresses = $this->_addresses;
        }

        if (!$addresses)
            return false;

        foreach ($addresses as $k => $v){

            if ($this->_is_use_message_id){

                $headers[] = str_replace('_', '-', "Message-ID: <".$message_id.$this->_p->getTime().".".$this->_mail_type."@".md5($k)).">";

            }

            $response = $response && mail($this->_createAddress($k, $v), $subject, $this->_body, join($eol, $headers));
        }

        return $response;

    }

} 
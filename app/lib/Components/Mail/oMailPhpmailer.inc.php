<?php
/**
 * PHPMailer driver component for oMail object
 *
 * Date: 13.01.15
 * Time: 22:36
 * @version 1.0
 * @author goshi
 * @package web-T[Mail]
 * 
 * Changelog:
 *	1.0	13.01.2015/goshi 
 */

namespace webtFramework\Components\Mail;


class oMailPhpmailer extends oMailAbstract {

    public function send($addresses = null){

        //Create a new PHPMailer instance
        $mail = new \PHPMailer;

        //Tell PHPMailer to use SMTP
        if ($this->_transport == 'smtp')
            $mail->isSMTP();

        //Enable SMTP debugging
        // 0 = off (for production use)
        // 1 = client messages
        // 2 = client and server messages
        if ($this->_p->getVar('is_debug')){
            $mail->SMTPDebug = 2;

            //Ask for HTML-friendly debug output
            $mail->Debugoutput = 'html';
        }

        //Set the hostname of the mail server
        if ($this->_transport_host)
            $mail->Host = $this->_transport_host;

        //Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
        if ($this->_transport_port)
            $mail->Port = $this->_transport_port;

        //Set the encryption system to use - ssl (deprecated) or tls
        if ($this->_transport_secure)
            $mail->SMTPSecure = $this->_transport_secure;

        //Whether to use SMTP authentication
        if ($this->_transport_login){
            $mail->SMTPAuth = true;

            //Username to use for SMTP authentication - use full email address for gmail
            $mail->Username = $this->_transport_login;

            //Password to use for SMTP authentication
            $mail->Password = $this->_transport_password;
        }

        // setup charset
        if ($this->_mail_encoding){
            $mail->CharSet = $this->_mail_encoding;
        }

        // track links
        if ($this->_is_track_links){
            $this->_body = $this->_trackLinks($this->_body, $this->_mail_type);
            $this->_alt_body = $this->_trackLinks($this->_alt_body, $this->_mail_type);
        }


        //Set who the message is to be sent from
        if ($this->_from)
            $mail->setFrom($this->_from, $this->_fromName);


        //Set the subject line
        if ($this->_subject)
            $mail->Subject = $this->_subject;

        // set message format
        if ($this->_mail_type == 'html'){
            $mail->isHTML(true);

            if ($this->_is_embed_images){
                //Read an HTML message body from an external file, convert referenced images to embedded,
                //convert HTML into a basic plain-text alternative body
                $mail->msgHTML($this->_body);
            } else {
                $mail->Body = $this->_body;
            }

        } else {

            $mail->Body = $this->_body;
        }

        // if we have custom altbody
        if ($this->_alt_body){
            $mail->AltBody = $this->_alt_body;
        }

        // add attachements
        if ($this->_attachements){

            foreach ($this->_attachements as $k => $v){
                //Attach an image file
                $mail->addAttachment($k, $v);
            }

        }

        //Set an alternative reply-to address
        if ($this->_replyto){
            foreach ($this->_replyto as $k => $v){
                $mail->addReplyTo($k, $v);
            }
        }

        //Set CC header
        if ($this->_cc){
            foreach ($this->_cc as $k => $v){
                $mail->addCC($k, $v);
            }
        }

        //Set BCC header
        if ($this->_bcc){
            foreach ($this->_bcc as $k => $v){
                $mail->addBCC($k, $v);
            }
        }

        // set custom headers
        if (!empty($this->_custom_headers)){
            foreach ($this->_custom_headers as $header){
                $mail->addCustomHeader($header);
            }
        }

        // check for use message id prefix
        $message_id = '';
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

        $response = true;

        foreach ($addresses as $k => $v){

            if ($this->_is_use_message_id){

                $mail->MessageID = $message_id.$this->_p->getTime().".".$this->_mail_type."@".md5($k);

            }

            //Set who the message is to be sent to
            $mail->addAddress($k, $v);

            //send the message, check for errors
            if (!($response = $mail->send())) {
                $this->_lastError = $mail->ErrorInfo;
            } else {
                $this->_lastError = null;
            }

            $mail->clearAddresses();


        }

        return $response;

    }

} 
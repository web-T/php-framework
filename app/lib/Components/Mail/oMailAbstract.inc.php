<?php
/**
 * Abstract class for mail drivers
 *
 * Date: 13.01.15
 * Time: 10:11
 * @version 1.0
 * @author goshi
 * @package web-T[Mail]
 * 
 * Changelog:
 *	1.0	13.01.2015/goshi 
 */

namespace webtFramework\Components\Mail;

use webtFramework\Core\oPortal;

abstract class oMailAbstract implements iMail{

    /**
     * last error on sending
     * @var null
     */
    protected $_lastError = null;

    /**
     * transport type, can be 'php', 'smtp'
     * @var string
     */
    protected $_transport = 'php';

    /**
     * default transport host
     * @var string
     */
    protected $_transport_host;

    /**
     * default transport port number
     * @var string
     */
    protected $_transport_port;

    /**
     * transport secure layer ('tls', etc.)
     * depends on concrete decorator
     * @var string
     */
    protected $_transport_secure = '';

    /**
     * transport login name
     * @var string
     */
    protected $_transport_login;

    /**
     * transport password
     * @var
     */
    protected $_transport_password;

    /**
     * mail codepage (default - utf-8)
     * @var string
     */
    protected $_mail_encoding = 'utf-8';

    /**
     * default mail type (possible 'html' or 'text')
     * @var string
     */
    protected $_mail_type = 'html';

    /**
     * flag for embed images to the html
     * @var bool
     */
    protected $_is_embed_images = false;

    /**
     * flag for track links with GA markers
     * @var bool
     */
    protected $_is_track_links = false;

    /**
     * flag for use special message id (used in mail analyzer module)
     * @var bool
     */
    protected $_is_use_message_id = false;

    /**
     * From email
     * @var string
     */
    protected $_from;

    /**
     * From name
     * @var
     */
    protected $_fromName;

    /**
     * array of 'email' => 'name' pairs for replyTo header
     * @var array
     */
    protected $_replyto = array();

    /**
     * array of 'email' => 'name' pairs for To header
     * @var array
     */
    protected $_addresses = array();

    /**
     * array of 'email' => 'name' pairs for CC header
     * @var array
     */
    protected $_cc = array();

    /**
     * array of 'email' => 'name' pairs for BCC header
     * @var array
     */
    protected $_bcc = array();

    /**
     * Subject of the mail
     * @var string
     */
    protected $_subject;

    /**
     * body of the message
     * @var string
     */
    protected $_body;

    /**
     * alternative body
     * @var string
     */
    protected $_alt_body;

    /**
     * array of attachements to the mail in format ('filename' => 'connect name')
     * @var array
     */
    protected $_attachements = array();

    /**
     * additional custom headers
     * @var array
     */
    protected $_custom_headers = array();

    /**
     * possible list of mail types
     * @var array
     */
    protected $_mail_types = array('html', 'text');

    /**
     * @var oPortal
     */
    protected $_p;

    public function __construct(oPortal &$p){

        $this->_p = &$p;

    }


    /**
     * @param $transport
     * @return $this|oMailAbstract
     */
    public function setTransport($transport){

        if ($transport){

            $this->_transport = $transport;

        }

        return $this;

    }

    public function getTransport(){

        return $this->_transport;

    }

    /**
     * @param $host
     * @return $this|oMailAbstract
     */
    public function setTransportHost($host){

        if ($host){
            $this->_transport_host = $host;
        }

        return $this;

    }

    public function getTransportHost(){

        return $this->_transport_host;

    }

    /**
     * @param $port
     * @return $this|oMailAbstract
     */
    public function setTransportPort($port){

        if ($port){

            $this->_transport_port = $port;

        }

        return $this;

    }

    public function getTransportPort(){

        return $this->_transport_port;

    }

    /**
     * @param null $secure
     * @return $this|oMailAbstract
     */
    public function setTransportSecure($secure = null){

        $this->_transport_secure = $secure;

        return $this;

    }

    public function getTransportSecure(){

        return $this->_transport_secure;

    }

    /**
     * @param null $login
     * @param null $password
     * @return $this|oMailAbstract
     */
    public function setTransportCredentials($login = null, $password = null){

        $this->_transport_login = $login;
        $this->_transport_password = $password;

        return $this;

    }

    /**
     * @param $from
     * @return $this|oMailAbstract
     */
    public function setFrom($from){

        $this->_from = (string)$from;

        return $this;

    }

    /**
     * @param null $name
     * @return $this|oMailAbstract
     */
    public function setFromName($name = null){

        $this->_fromName = (string)$name;

        return $this;

    }

    /**
     * @param $subject
     * @return $this|oMailAbstract
     */
    public function setSubject($subject){

        $this->_subject = (string)$subject;

        return $this;

    }

    /**
     * @param null $body
     * @return $this|oMailAbstract
     */
    public function setBody($body = null){

        $this->_body = (string)$body;

        return $this;

    }

    /**
     * @param null $body
     * @return $this|oMailAbstract
     */
    public function setAltBody($body = null){

        $this->_alt_body = (string)$body;

        return $this;

    }

    /**
     * @param bool $is_embed_images
     * @return $this|oMailAbstract
     */
    public function setIsEmbedImages($is_embed_images = false){

        $this->_is_embed_images = $is_embed_images;

        return $this;

    }

    /**
     * @param bool $is_track_links
     * @return $this|oMailAbstract
     */
    public function setIsTrackLinks($is_track_links = false){

        $this->_is_track_links = $is_track_links;

        return $this;

    }

    /**
     * @param bool $is_use_message_id
     * @return $this|oMailAbstract
     */
    public function setIsUseMessageID($is_use_message_id = false){

        $this->_is_use_message_id = $is_use_message_id;

        return $this;

    }

    /**
     * @param $type
     * @return $this|oMailAbstract
     */
    public function setMailType($type){

        if (in_array($type, $this->_mail_types)){

            $this->_mail_type = $type;

        }

        return $this;

    }

    /**
     * @param $encoding
     * @return $this|oMailAbstract
     */
    public function setMailEncoding($encoding){

        if ($encoding){

            $this->_mail_encoding = $encoding;

        }

        return $this;

    }

    /**
     * @param $email
     * @param null $name
     * @return $this|oMailAbstract
     */
    public function addAddress($email, $name = null){

        if ($email && is_string($email)){

            $this->_addresses[trim($email)] = is_string($name) ? trim($name) : $name;

        }

        return $this;

    }

    /**
     * @param $email
     * @param null $name
     * @return $this|oMailAbstract
     */
    public function addReplyTo($email, $name = null){

        if ($email && is_string($email)){

            $this->_replyto[$email] = $name;

        }

        return $this;

    }

    /**
     * @param $email
     * @param null $name
     * @return $this|oMailAbstract
     */
    public function addCC($email, $name = null){

        if ($email && is_string($email)){

            $this->_cc[$email] = $name;

        }

        return $this;

    }

    /**
     * @param $email
     * @param null $name
     * @return $this|oMailAbstract
     */
    public function addBCC($email, $name = null){

        if ($email && is_string($email)){

            $this->_bcc[$email] = $name;

        }

        return $this;

    }

    /**
     * @param $file
     * @param null $name
     * @return $this|oMailAbstract
     */
    public function addAttachment($file, $name = null){

        if ($file && is_string($file) && file_exists($file) && is_file($file)){

            $this->_attachements[$file] = $name && is_string($name) ? $name : basename($file);

        }

        return $this;

    }

    /**
     * @param string|array $header
     * @return $this
     */
    public function addCustomHeader($header){

        if (!$header)
            return $this;

        if (!is_array($header)){
            $header = array($header);
        }

        foreach ($header as $h){
            $h_e = explode(':', $h);
            // remove duplicates
            $this->_custom_headers[trim($h_e[0])] = trim($h);
        }

        return $this;

    }

    public function getLastError(){

        return $this->_lastError;

    }

    /**
     * method cleanup all local variables
     * @return $this|oMailAbstract
     */
    public function cleanup(){

        //$this->_mail_type = 'html';
        //$this->_mail_encoding = 'utf-8';
        //$this->_transport_login = '';
        //$this->_transport = 'php';
        //$this->_transport_password = '';
        //$this->_transport_port = null;
        //$this->_transport_secure = null;
        //$this->_transport_host = null;
        $this->_lastError = null;
        $this->_subject = '';
        $this->_body = '';
        $this->_alt_body = '';
        $this->_attachements = array();
        $this->_from = '';
        $this->_fromName = null;
        $this->_replyto = array();
        $this->_addresses = array();
        $this->_cc = array();
        $this->_bcc = array();
        $this->_replyto = array();
        $this->_custom_headers = array();
        //$this->_is_embed_images = false;
        //$this->_is_track_links = false;
        //$this->_is_use_message_id = false;

        return $this;

    }


    /**
     * generate message id prefix
     * @return string
     */
    public function getMessageIdPrefix(){

        if ($this->_p->getVar('server_name'))
            return $this->_p->getVar('tbl_prefix').md5($this->_p->getVar('server_name')).'_';
        elseif ($this->_p->getVar('mail')['default_from_mail'])
            return $this->_p->getVar('tbl_prefix').md5($this->_p->getVar('mail')['default_from_mail']).'_';
        else
            return $this->_p->getVar('tbl_prefix').md5(dirname(__FILE__)).'_';

    }

    /**
     * method track links in the text with GA markers
     *
     * @param string $text
     * @param string $type @see oMailAbstract::_mail_types
     * @return mixed
     */
    protected function _trackLinks($text, $type){

        $matches = array();

        if ($type == 'html'){

            preg_match_all("/<a.*?href=[\"'](.*?)[\"'].*?>/is", $text, $matches);

        } else {

            preg_match_all("/((?:https?:\/\/)(?:[a-zA-Z0-9\_\.\-\?\/=]+)+)/is", $text, $matches);

        }

        $source = $replace = array();
        $black_list = array('^#.*', '^mailto:.*', '\[addgroup=[^\]]+\]', '\[ungroup=[^\]]+\]', '^\{%\$');

        if (!empty($matches)){
            //unset($matches[0]);
            for ($i = 0; $i<count($matches[1]); $i++){
                // checking match in black list
                //if (in_array($matches[1][$i], $black_list)) continue;
                if (preg_match('/'.join('|', $black_list).'/is', $matches[1][$i])) continue;

                $source[$i] = $matches[0][$i];
                $replace[$i] = str_replace($matches[1][$i], $this->_trackLink($matches[1][$i]), $matches[0][$i]);
            }

            $text = str_replace($source, $replace, $text);
        }

        return $text;

    }


    /**
     * track link
     *
     * @param $link
     * @return array|string
     */
    protected function _trackLink($link){

        if ($link){

            $hash = (array)explode('#', $link);

            $link = (array)explode('?', $hash[0]);

            $track = 'utm_source=newsletter&utm_medium=email&utm_content='.rawurlencode(date('d/m/Y', $this->_p->getTime())).'&utm_campaign='.rawurlencode(mb_substr($this->_p->getVar('server_name').'::'.$this->_subject, 0, 30));

            if (isset($link[1])){
                $link = $link[0].'?'.$link[1].'&'.$track;
            } else {
                $link = $link[0].'?'.$track;
            }
            if (isset($hash[1])){
                $link .= '#'.$hash[1];
            }

        }

        return $link;
    }

    /**
     * method convert input addresses variable to the right format ('email' => 'name')
     * @param $addresses
     * @return array|null
     */
    protected function _convertAddresses($addresses){

        if (!$addresses)
            return null;

        if (!is_array($addresses))
            $addresses = array($addresses => null);
        else {
            $new_addresses = array();
            foreach ($addresses as $k => $v){
                if (!$k || !preg_match('/@/', $k))
                    $new_addresses[$v] = null;
                else
                    $new_addresses[$k] = $v;
            }

            $addresses = $new_addresses;
            unset($new_addresses);
        }

        return $addresses;

    }

    abstract public function send($addresses = null);



} 
<?php
/**
 * Interface for Mail driver
 *
 * Date: 13.01.15
 * Time: 09:50
 * @version 1.0
 * @author goshi
 * @package web-T[Mail]
 * 
 * Changelog:
 *	1.0	13.01.2015/goshi 
 */

namespace webtFramework\Components\Mail;

interface iMail {


    public function setTransport($transport);

    public function getTransport();

    public function setTransportHost($host);

    public function getTransportHost();

    public function setTransportPort($port);

    public function getTransportPort();

    public function setTransportSecure($secure);

    public function getTransportSecure();

    public function setTransportCredentials($login = null, $password = null);

    public function setFrom($from);

    public function setFromName($name = null);

    public function setSubject($subject);

    public function setBody($body = null);

    public function setAltBody($body = null);

    public function setIsEmbedImages($is_embed_images = false);

    public function setIsTrackLinks($is_track_links = false);

    public function setIsUseMessageID($is_use_message_id = false);

    public function setMailType($type);

    public function setMailEncoding($encoding);

    public function addAddress($email, $name = null);

    public function addReplyTo($email, $name = null);

    public function addCC($email, $name = null);

    public function addBCC($email, $name = null);

    public function addAttachment($file, $name = null);

    public function getLastError();

    public function send($addresses = null);

}
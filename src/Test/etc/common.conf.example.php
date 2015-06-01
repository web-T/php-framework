<?php
/**
 * Test settings common file
 */

namespace Test\Config;

if (!isset($INFO)){
    $INFO = array();
}

// include another config
// include(substr(__FILE__, 0, strrpos(__FILE__, '/')).'/../../BUNDLE/etc/common.conf.php');

// update user settings
$INFO['user']['session_cookie']    = 'sess_id';
$INFO['user']['session_authed_cookie']    = 'authed';
$INFO['user']['check_session_by_ip']    = false;
$INFO['user']['model']    = 'User';
$INFO['user']['rules_model']    = 'UserRule';
$INFO['user']['user_rules_model']    = 'UserRuleLink';
$INFO['user']['is_merge_anonym_session']    = true;

$INFO['user']['app']    = 'BUNDLE:APP';



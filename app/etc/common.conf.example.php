<?php
/**
 * Main config file
 *
 * Date: 09.01.13
 * Time: 22:24
 * @version 1.0
 * @author goshi
 * @package web-T[Framework]
 *
 * Changelog:
 *    1.0    09.01.2013/goshi
 */

$INFO = array();

define('WEBT_VERSION', '5.8.6');

// checking for environment
if (file_exists(substr(__FILE__, 0, strrpos(__FILE__, '/')).'/environment'))
    require_once(substr(__FILE__, 0, strrpos(__FILE__, '/')).'/environment');

if (!defined('WEBT_ENV'))
    define('WEBT_ENV', 'debug');

// check for environment file exists
if (file_exists(substr(__FILE__, 0, strrpos(__FILE__, '/')).'/'.WEBT_ENV.'.common.conf.php'))
    require_once(substr(__FILE__, 0, strrpos(__FILE__, '/')).'/'.WEBT_ENV.'.common.conf.php');


// define your configuration here
switch (WEBT_ENV){

    case 'debug':

        $INFO['storages'] 			= array(
            'base'  => array(
                'db_type' => 'mypdo',
                'db_host' => '127.0.0.1',
                'db_name' => '',
                'db_user' => '',
                'db_pass' => ''
            ),
        );

        $INFO['is_dev_env']			= 1;			// flag of developer version

        /**
         * Image generationg properties
         */
        $INFO['image']			= array(

            /**
             * can be "auto", "imagick", "imagickshell", "gd"
             * also, you can override it per application and environment
             */
            'type' => 'auto',

            /**
             * image quality (from 0 to 100)
             */
            'quality' => 85,

            /**
             * flag determine if images has watermark by default
             */
            'has_watermark' => false,

            /**
             * additional path for imagick exeutables, like 'C:\\ImageMagick', or '/usr/local/bin'
             */
            'imagick_path' =>  '/opt/local/bin',

            /**
             * service, that handles pictures requests
             */
            'service' => 'webtCMS:oImagesUploader',

        );

        $INFO['cache_dir']			= "cache/";		// folder for cache - base for modules

        $INFO['geo']['db']	 		= "/opt/local/share/GeoIP/GeoIP.dat";

        // add xdebug settings
        ini_set('display_errors', 1);
        ini_set('xdebug.profiler_enable_trigger', 1);
        ini_set('xdebug.show_mem_delta', 1);

        $INFO['social'] = array(

            'twitter'	=> array(
                'consumer_key'			=> '',
                'consumer_secret'		=> '',
            ),

            'facebook'	=> array(
                'app_id'				=> '',
                'app_secret'			=> '',
                'perms'					=> 'email,public_profile,user_birthday,user_likes,user_photos' //offline_access, publish_stream,
            ),

            'vkontakte'	=> array(
                'app_id'				=> '',
                'app_secret'			=> '',
                'perms'					=> 'notify,wall,offline,email'
            ),

            'mailru' => array(
                'app_id'                => '',
                'consumer_key'          => '',
                'consumer_secret'       => '',
                ),

            'odnoklassniki'	=> array(
                'app_id'				=> '',
                'consumer_key'			=> '',
                'consumer_secret'		=> '',
                'perms'					=> 'VALUABLE ACCESS'
            ),

            'google'	=> array(
                'app_id'                       => '',
                'consumer_key'                 => '',
                'consumer_secret'              => '',
                'scope'                        => array('email', 'profile'),
                'access_type'                  => 'offline'
            )

        );

        /**
         * check API KEY
         */
        $INFO['API_KEYS'] = array();

        /**
         * external api list for communication with other APIs
         */
        $INFO['EXTERNAL_API_KEYS'] = array(
            'INSTANCES' => '', // Instances (need in both sections)
        );

        /**
         * List of instances (name => array('url' => 'http(s)://...')))
         */
        $INFO['INSTANCES'] = array(
            'main' => array('url' => 'http://xxx'),
        );

        /**
         * Define current Instance name
         * Be very careful with it
         */
        define('INSTANCE_NAME', 'main');


        define('DEFAULT_EXTERNAL_API', '');


        $INFO['DOC_DIR'] 		    = 'www/';

        /**
         * Whole application directory related to the DOC_DIR
         */
        $INFO['APP_DIR'] 		    = '';



        break;

    case 'production':

        $INFO['storages'] 			= array(
            'base'  => array(
                'db_type' => 'mypdo',
                'db_host' => 'localhost',
                'db_name' => '',
                'db_user' => '',
                'db_pass' => ''
            )
        );

        $INFO['is_dev_env']				= 0;			// flag of developer version

        /**
         * Image generationg properties
         */
        $INFO['image']			= array(

            /**
             * can be "auto", "imagick", "imagickshell", "gd"
             * also, you can override it per application and environment
             */
            'type' => 'auto',

            /**
             * image quality (from 0 to 100)
             */
            'quality' => 85,

            /**
             * flag determine if images has watermark by default
             */
            'has_watermark' => false,

            /**
             * additional path for imagick exeutables, like 'C:\\ImageMagick', or '/usr/local/bin'
             */
            'imagick_path' =>  '/usr/bin',

            /**
             * service, that handles pictures requests
             */
            'service' => 'webtCMS:oImagesUploader',

        );

        $INFO['cache_dir']			= "cache/";		// folder for cache - base for modules

        $INFO['geo']['db']	 		= "/usr/local/share/GeoIP/GeoIP.dat";


        $INFO['social'] = array(

            'twitter'	=> array(
                'consumer_key'			=> '',
                'consumer_secret'		=> '',
            ),

            'facebook'	=> array(
                'app_id'				=> '',
                'app_secret'			=> '',
                'perms'					=> 'email,public_profile,user_birthday,user_likes,user_photos' //offline_access, publish_stream,
            ),

            'vkontakte'	=> array(
                'app_id'				=> '',
                'app_secret'			=> '',
                'perms'					=> 'notify,wall,offline,email'
            ),

            'mailru' => array(
                'app_id'                => '',
                'consumer_key'          => '',
                'consumer_secret'       => '',
            ),

            'odnoklassniki'	=> array(
                'app_id'				=> '',
                'consumer_key'			=> '',
                'consumer_secret'		=> '',
                'perms'					=> 'VALUABLE ACCESS'
            ),

            'google'	=> array(
                'app_id'                       => '',
                'consumer_key'                 => '',
                'consumer_secret'              => '',
                'scope'                        => array('email', 'profile'),
                'access_type'                  => 'offline'
            )

        );

        /**
         * check API KEY
         */
        $INFO['API_KEYS'] = array();

        /**
         * external api list for communication with other APIs
         */
        $INFO['EXTERNAL_API_KEYS'] = array(
            'INSTANCES' => '', // Instances (need in both sections)
        );

        /**
         * List of instances (name => array('url' => 'http(s)://...')))
         */
        $INFO['INSTANCES'] = array(
            'main' => array('url' => 'http://xxx'),
        );

        /**
         * Define current Instance name
         * Be very careful with it
         */
        define('INSTANCE_NAME', 'main');


        define('DEFAULT_EXTERNAL_API', '');


        $INFO['DOC_DIR'] 		    = 'www/';

        /**
         * Whole application directory related to the DOC_DIR
         */
        $INFO['APP_DIR'] 		    = '';

        break;

}

/** define PHP version **/
// PHP_VERSION_ID is available as of PHP 5.2.7, if our
// version is lower than that, then emulate it
if (!defined('PHP_VERSION_ID')) {
    $version = explode('.', PHP_VERSION);

    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}

// PHP_VERSION_ID is defined as a number, where the higher the number
// is, the newer a PHP version is used. It's defined as used in the above
// expression:
//
// $version_id = $major_version * 10000 + $minor_version * 100 + $release_version;
//
// Now with PHP_VERSION_ID we can check for features this PHP version
// may have, this doesn't require to use version_compare() everytime
// you check if the current PHP version may not support a feature.
//
// For example, we may here define the PHP_VERSION_* constants thats
// not available in versions prior to 5.2.7

if (isset($version) && PHP_VERSION_ID < 50207) {
    define('PHP_MAJOR_VERSION',   $version[0]);
    define('PHP_MINOR_VERSION',   $version[1]);
    define('PHP_RELEASE_VERSION', $version[2]);
}


/** Patching constants **/
define('PHP_FLOAT_MAX', 1.8e300);
ini_set('pcre.recursion_limit', 7000); // fixing PCRE segmentation fault


define('WEBT_DS', DIRECTORY_SEPARATOR);

/**
 * define app types
 */
define('WEBT_APP_WEB', 1);
define('WEBT_APP_CONSOLE', 2);
define('WEBT_APP_API', 3);


$INFO['BASE_APP_DIR'] 		= substr(__FILE__, 0, strrpos(__FILE__, WEBT_DS)).WEBT_DS.'..'.WEBT_DS.'..'.WEBT_DS.$INFO['APP_DIR'];


$INFO['ROOT_DIR'] 			= ''; //$_SERVER['DOCUMENT_ROOT'].'/';

/**
 * Framework directory
 */
$INFO['FW_DIR'] 			= 'app'.WEBT_DS;

/**
 * application kernel file
 */
$INFO['ROOT_CGI']			= "index.php";

/**
 * root URL for query, if you setup application like subsection of another application - you may change the variable
 * 'ROOT_URL' to something, like '/anotherapp/'
 */
$INFO['ROOT_URL']			= "/";

/**
 * custom router routes
 */
$INFO['ROUTES']			    = array();

/**
 * config files
 */
$INFO['config_files']              = array(
    'config' => 'common.conf.php',
    'routes' => 'routing.conf.php',
    'assets' => 'assets.conf.php',

);


/**
 * REQUEST/RESPONSE params
 */

/**
 * Stream types used for output formatted inforamtion to the stream
 */
define('ST_BROWSER', 1);
define('ST_CONSOLE', 2);

/**
 * content types
 */
define('CT_HTML', 1);
define('CT_XML', 2);
define('CT_JSON', 3);
define('CT_AJAX', 4);
define('CT_PDF', 5);


/**
 * define default stream type
 */
$INFO['STREAM_TYPE']		= ST_BROWSER;

/**
 * base application server name
 */
$INFO['server_name']		= '';

/**
 * server aliases for application
 */
$INFO['server_aliases']		= '';

/**
 * query document types (content_type => content file name)
 */
$INFO['doc_types']			= array(
    CT_HTML	=>	'index.html', // name of standart page (may be index.asp, index.php anf others :)
    CT_XML	=>	'index.xml',  // name of XML page (may be index.asp, index.php anf others, but some browsers can't hold them!!! :)
    CT_JSON  =>  'index.json', // JSON type
    CT_AJAX  =>  'index.ajax'  // special type for JsHttpRequest
);


// for header Expires set number of days to expires
$INFO['expires_days']			= '0.02';


$INFO['response'] = array(

    /**
     * flag for sending files by framework's read/write methods
     */
    'send_files_by_framework' => true,

);



/**
 * Base Directories
 */

$INFO['skin_dir']			= "skin";
$INFO['img_dir']			= "img";
$INFO['dev_dir']			= "dev/";		// development dir

$INFO['share_dir']			= "share/";
$INFO['config_dir']			= "etc".WEBT_DS;
$INFO['bundles_dir']        = "src".WEBT_DS;
$INFO['vendor_dir']         = "vendor".WEBT_DS;
$INFO['skin_img_dir']		= "/".$INFO['skin_dir']."/".$INFO['img_dir']."/";


$INFO['temp_dir']			= "temp/";		// folder for temporary files
$INFO['files_dir']			= "/files/";		// folder for uploaded files
$INFO['images_dir']			= "images/";		// folder for images
$INFO['uploads_dir']		= "upload/";		// folder for direct uploads


/**
 *	framework structure
 */
$INFO['core_dir']			    = "Core/";		// portal core directory

$INFO['interfaces_dir']			= "Interfaces/";	// Interfaces and classes directory
$INFO['components_dir']		    = "Components/";	// Components directory
$INFO['services_dir']		    = "Services/";	    // Services directory
$INFO['common_dir']		        = "Common/";	    // Common directory
$INFO['modules_dir']			= "Modules/";		// Modules directory
$INFO['models_dir']			    = "Models/";		// Models directory
$INFO['plugins_dir']			= "Plugins/";		// plugins directory
$INFO['tools_dir']			    = "Tools/";		// plugins directory
$INFO['drivers_dir']		    = 'Drivers/';		// drivers directory

/**
 * bundles application controllers
 */
$INFO['apps_dir']			= "apps".WEBT_DS;

/**
 * console applications directory
 * they are must located in bundles
 */
$INFO['console_dir']		= "console".WEBT_DS;

/**
 * api applications directory
 * they are must located in bundles
 */
$INFO['api_dir']		= "api".WEBT_DS;



/**
 * variables and temporary information directory
 */
$INFO['var_dir']		    = "var".WEBT_DS;

$INFO['log_dir']		    = $INFO['var_dir']."log".WEBT_DS;

$INFO['lib_dir']		    = "lib".WEBT_DS;		// libraries directory

$INFO['lib_js_dir']	        = "js/";		// javascript-libraries directory

$INFO['css_dir']			= "css/";		// default CSS directory

$INFO['cron_files_dir']			= $INFO['var_dir']."/cron/";		// temporary cron files directory

$INFO['upload_dir']				= "/files/upload/";		// uploaded files directory



/**
 * CORE parameters
 */
$INFO['core']= array(

    /**
     * settings parameters
     */
    'settings' => array(

        /**
         * if you have external settings storage, then you need to describe it here in format Bundle:Model
         */
        'model' => '',

        /**
         * model's mapping for nick field
         */
        'model_map_nick' => '',

        /**
         * model's mapping for value field
         */
        'model_map_value' => ''

    )

);


/**
 * server settings
 */
$INFO['server'] = array(

    /**
     * maximum load average value which determines server overload status
     */
    'overload_value' => 80

);

/**
 * router settings
 */
$INFO['router'] = array(

    /**
     * deafult URL scheme per application
     */
    'default_scheme' => 'http',

    /**
     * flag for generating userfriendly URL's
     * TODO: refactor code
     */
    'is_friendly_URL' => true,

    /**
     * flag for merging non-www and www domains
     */
    'merge_www_domain' => true,

    /**
     * model for custom router
     */
    'model' => '', // webtCMS:Page

    /**
     * default page nick for query->parser
     */
    'default_query_page' => 'main',

);


/**
 * api settings
 */
$INFO['api'] = array(

    /**
     * default application for api kernel (you can change it in the controllers)
     */
    'default_application' => 'Frontend'

);

/**
 * CACHE paramateres
 */

define('WEBT_CACHE_TYPE_STATIC', 1);
define('WEBT_CACHE_TYPE_LIFETIME', 2);
define('WEBT_CACHE_TYPE_UPDATETIME', 3);


$INFO['cache'] = array(

    /**
     * folder for cached queries
     */
    'queries_dir'   =>      $INFO['var_dir'].$INFO['cache_dir'].WEBT_ENV.WEBT_DS."queries".WEBT_DS,

    /**
     * folder for cached pages
     */
    'data_dir'      =>	    $INFO['var_dir'].$INFO['cache_dir'].WEBT_ENV.WEBT_DS."data".WEBT_DS,

    /**
     * folder for cached meta data
     */
    'meta_dir'		=>      $INFO['var_dir'].$INFO['cache_dir'].WEBT_ENV.WEBT_DS."meta".WEBT_DS,

    /**
     * folder for cached tags
     */
    'tags_dir'	=>   $INFO['var_dir'].$INFO['cache_dir'].WEBT_ENV.WEBT_DS."tags".WEBT_DS,


    /**
     * folder for serialized data
     */
    'serial_dir'	=>   $INFO['var_dir'].$INFO['cache_dir'].WEBT_ENV.WEBT_DS."serial".WEBT_DS,

    /**
     * folder for cached static pages
     */
    'static_dir'	=>  $INFO['var_dir'].$INFO['cache_dir'].WEBT_ENV.WEBT_DS."static".WEBT_DS,

    /**
     * folder for cached static pages
     */
    'modules_dir'	=>  $INFO['var_dir'].$INFO['cache_dir'].WEBT_ENV.WEBT_DS."modules".WEBT_DS,


    /**
     * serial cache storage (supports 'files', 'shmem')
     */
    'serial_storage' => 'files',

    /**
     * data cache storage (supports 'files', 'shmem')
     */
    'data_storage' => 'files',

    /**
     * meta cache storage (supports 'files', 'shmem')
     */
    'meta_storage' => 'files',


    /**
     * save gzipped version of static cache file for mod_gzip (it is get .gz file from directory if flag mod_gzip_can_negotiate set to true)
     */
    'gzip_static_cache'     => true,


    /**
     * methods for serializaion, can be 'serialize', 'json', 'bittorent2'
     */
    'serialize_method' =>   'serialize',


    /**
     * cache model
     */
    'cache_model' => 'Cache',

    /**
     * cache tag model
     */
    'cache_tag_model' => 'CacheTag',

    /**
     * cache block types
     */
    'block_types'	=> array(
        'page' => array('_add' => '_page'),
        'clip' => array('_add' => '_clip'),
    ),

);




/**
 * Templator settings
 */
$INFO['templator']			= array(
    /**
     * type of the templator
     * possible types: smarty, smarty3
     */
    'type' => 'smarty',

    /**
     * default templates directory
     */
    'dir' => 'views',

    /**
     * folder for compiled templates
     */
    'compile_dir'	=> $INFO['var_dir'].$INFO['cache_dir'].WEBT_ENV.WEBT_DS."tpl_compile".WEBT_DS,

    /**
     * folder for cached Smarty templates
     */
    'cache_dir'		=> $INFO['var_dir'].$INFO['cache_dir'].WEBT_ENV.WEBT_DS."tpl_cache".WEBT_DS,

    /**
     * default templates directory
     */
    //'template_dir' => '...'

    /**
     * flag for compress HTML templates
     */
    'compress_html' => true,

    /**
     * The beginning of the normal tags
     */
    'begin_key' => '{%',

    /**
     * The end of the normal tags
     */
    'end_key' => '%}'

);


/**
 * USER settings
 */

$INFO['user']               = array(

    /**
     * session cookie name
     */
    'session_cookie'    => 'sess_id',

    /**
     * session cookie authed flag
     */
    'session_authed_cookie' => 'authed',

    /**
     * session name for all users
     */
    'session_name'			=> 'WEBTID',

    /**
     * autostart session
     */
    'session_autostart'		=> false,


    /**
     * session model
     */
    'session_model' => 'Session',

    /**
     * session history model
     */
    'session_history_model' => 'SessionHistory',

    /**
     * flag for check session by users IP-address
     */
    'check_session_by_ip'   => false,

    /**
     * flag for save user session_id in cookie for long time (not only during browser session)
     * you can manipulate it for security reason
     */
    'is_cooked_session'     => true,

    /**
     * check user auth by defined IP
     */
    'is_check_auth_ip'      => false,

    /**
     * flag for merge anonymous cookie data with his session data
     */
    'is_merge_anonym_session' => true,

    /**
     * user model
     */
    'model'             => 'User',

    /**
     * rules model
     */
    'rules_model'       => 'UserRule',

    /**
     * link model for user <-> rules
     */
    'user_rules_model'  => 'UserRuleLink',

    /**
     * default salt for generate passwords and tokens
     * WARNING! YOU NEED TO CHANGE IT FOR YOUR PROJECT
     */
    'salt'              => '-Z&adsd5s$%ER45',

    /**
     * can be 'cron' or 'normal'. Normal cleanup mode cleans ones per day on one query from user, cron works like it want
     */
    'cleanup_mode'      =>	'normal',

    /**
     * timeout for user sessions
     */
    'lastuse_timeout'	=> 60*60*24*100,

    /**
     * timeout for history of user sessions
     */
    'lastuse_history_timeout'	=> 60*60*24,

    /**
     * cookie timeout in days
     */
    'cookie_timeout'			=> 356,

);



/**
 * Forms properties
 */
$INFO['forms']              = array(

    /**
     * base array for all data in forms
     */
    'base' => 'ch_elem',

    /**
     * multistore fields separator
     */
    'fields_multistore_sep'		=> ',?,',

    /**
     * flag turn on/off show weight field near custom field
     */
    'is_show_custom_fields_weight'	=> true,

    /**
     * default fields classes
     */
    'default_classes'	=> array('b-input', 'form-control'),

);



/**
 * Standart mail properties
 */
$INFO['mail']				= array(

    /**
     * driver for oMail control, can be 'default', 'phpmailer', and other (depends on the software version)
     */
    'driver' => 'default',

    /**
     * transport (possible values 'php', 'sendmail', 'smtp')
     */
    'transport' => 'php',

    /**
     * transport host (for SMTP mode)
     */
    'transport_host' => 'smtp.gmail.com',

    /**
     * transport port (for SMTP mode)
     */
    'transport_port' => 587,

    /**
     * transport secure type - possible 'tls', 'ssl' (for SMTP mode)
     */
    'transport_secure' => 'tls',

    /**
     * transport login (for SMTP mode)
     */
    'transport_login' => '',

    /**
     * transport password (for SMTP mode)
     */
    'transport_password' => '',

    /**
     * defaut encoding
     */
    'encoding' => 'utf-8',

    /**
     * default mail type (possible: 'html', 'text')
     */
    'type' => 'html',

    /**
     * flag for tracking links
     */
    'track_links' => true,

    /**
     * flag for embed images to the file
     */
    'embed_images' => false,

    /**
     * use in messages special generated message_id
     */
    'use_message_id' => true,

    /**
     * default from mail
     */
    'default_from_mail' => 'admin@',

    /**
     * default from name
     */
    'default_from_name' => 'Admin'
);


/**
 * JOBS params
 */

/** job statuses and priorities **/
define('JOB_PENDING', 1);
define('JOB_PROCESS', 2);
define('JOB_DONE', 3);

define('JOB_PRIOR_LOW', 1);
define('JOB_PRIOR_NORMAL', 2);
define('JOB_PRIOR_HIGH', 3);

$INFO['jobs'] = array(

    /**
     * can be 'cron' or 'normal'. Normal job runs immediately on call
     */
    'mode'			=> 'normal',
);



/**
 * DEBUG params
 */

$INFO['debugger'] = array(

    /**
     * debug level
     * */
    'request_key'	=>			'_debug',

    /**
     * debug panel locker
     */
    'debug_show_panel'	=>		false,

    /**
     * debug show all errors flag
     **/
    'debug_show_all_errors'	=>	false,

    /**
     * debugger assets directory
     */
    'debug_assets_dir' => $INFO['DOC_DIR'].'debugger',

    /**
     * debugger profiler (supports default and xhprof now)
     */
    'profiler' => 'default',

    /**
     * exists log files, please add your nick for other logs
     * */
    'logs'			=> array(
        'error'     => '/'.$INFO['log_dir'].'error.'.WEBT_ENV.'.log',
        'db'        => '/'.$INFO['log_dir'].'db.'.WEBT_ENV.'.log',
        'app'       => '/'.$INFO['log_dir'].'app.'.WEBT_ENV.'.log',
        'parser'       => '/'.$INFO['log_dir'].'parser.'.WEBT_ENV.'.log',
        'autoloader'       => '/'.$INFO['log_dir'].'autoloader.'.WEBT_ENV.'.log',
    )
);

/**
 * debug level
 */
$INFO['is_debug']				= /*$INFO['is_dev_env'] ? */ ((isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], $INFO['debugger']['request_key']) !== false) || $INFO['debugger']['debug_show_panel'] || defined('IS_DEBUG') ? true : false);




/**
 * Rules params
 **/
$INFO['rules_mask'] = array(0 => 'read', 1 => 'add', 2 => 'edit', 3 => 'delete');


/**
 * LANGUAGE params
 */
$INFO['translator'] = array(

    /**
     * base language model
     */
    'model' => '', //Language

    /**
     * languages directory
     */
    'languages_dir'		=> "languages/",


    /**
     * model for additional translating
     */
    'translates_model' => '' // webtCMS:TextConstant

);

/**
 * current language nick
 */
$INFO['language']			= "ru";

/**
 * flag for build links with multilanguage mode
 */
$INFO['is_multilang']			= false;

/**
 * default codepage
 */
$INFO['codepage']			= "utf-8";

/**
 * default locale
 */
$INFO['locale']             = 'utf-8.ru_RU';

/**
 * default timezone
 */
$INFO['timezone']           = 'Europe/Kiev';



/**
 * ASSETS settings
 */
$INFO['assets'] = array(

    /**
     * CSS settings
     */
    'css' => array(

        /**
         * CSS assets output directory
         */
        'output_dir' => $INFO['DOC_DIR'].'css/build/',

        /**
         * default filters for assets
         */
        'default_filters' => array('compress', 'gzip', 'convertPath'),
    ),

    /**
     * JS output directory
     */
    'js' => array(

        /**
         * JS output directory
         */
        'output_dir' => $INFO['DOC_DIR'].'js/build/',

        /**
         * default filters for assets
         */
        'default_filters' => array('minify', 'gzip'),

        /**
         * settings for minify library
         */
        'filter_minify' => array(

            'library' => $INFO['BASE_APP_DIR'].$INFO['vendor_dir'].'JSPacker/JSmin.php',

            'class' => '\JSMin',

            /**
             * method must be called static
             */
            'method' => 'minify'

        )

    ),

    /**
     * assets map, like: 'filename' => array('version' => int, 'build' => array(array of source files from BASE_APP_DIR))
     */
    'map' => array(
        //...
    )

);

/**
 * Search parameters
 */
$INFO['search'] = array(

    /**
     * flag to turn on speed indexing
     */
    'is_indexing' => true,

    /**
     * index driver
     * supports 'mysql_standart', 'sphinx' values
     * full list of drivers you can @see oSearch
     */
    'indexing_driver' => 'mysql_standart',

    /**
     * indexing model name
     */
    'indexing_model' => 'Search',

    /**
     * fields which could be added to the index by oSearch and searchs in the admin part
     */
    'fields' => array('title', 'sname', 'mname', 'fname', 'email', 'phone', 'descr', 'header', 'tags')
);



/**
 * Application linker parameters
 */
$INFO['linker'] = array(

    /**
     * linker model
     */
    'model' => 'Linker',

    /**
     * linker service (which will be calling in framework)
     */
    'service' => 'webtCMS:oLinker',

);





/**
 * Upload params
 */
$INFO['upload']	= array(

    /**
     * main control to handle all uploads
     */
    'service' => 'webtCMS:oUploader',

    /**
     * upload model
     */
    'model' => 'webtCMS:Upload',

    /**
     * default accept file types for upload
     */
    'accept' => array('txt', 'pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'csv', 'jpg', 'jpeg', 'png', 'gif',
        'mp3', 'mp4', 'flv', 'wmv', 'avi', 'html', 'rtf', 'tif', 'tiff', 'xml', 'zip', 'swf'),

);



/**
 * STATISTIC params
 */
$INFO['statistic'] = array(

    /**
     * timeout for active users (stat)
     */
    'active_timeout'	=> 5*60,

    /**
     * URL of the tracking counter
     */
    'counter_url'	=> 'updt_pop',

    /**
     * filename of the tracking counter
     */
    'counter_filename'	=>	'webt_cntr.gif',

    /**
     * popularity write mode ('sync' or 'async')
     */
    'popularity_mode'	=> 'sync',



);



// bots, for which don't set any session
// full list you can find on http://www.iplists.com/nw/ with ip addresses
$INFO['bots'] = array('Yandex', 'Google', 'gsa-crawler', 'StackRambler', 'Aport', 'msn', 'AltaVista', 'Inktomi', 'Ask', 'WiseNut');


/**
 * reserved nicks for url
 * depends on bundle
 */
$INFO['reserved_pages']			= array();


/**
 * files/dirs rules
 */
define('PERM_DIRS', 0777);
define('PERM_FILES', 0666);

/**
 * ACCESS constants
 **/
define('ACL_ACCESS_ALLOW_ALL', 1);
define('ACL_ACCESS_BANNED_ALL', 2);
define('ACL_ACCESS_BANNED_POST', 3);
define('ACL_ACCESS_ALLOW_BY_RULE', 4);


/**
 * define moderator/editor constants
 **/
define('ADMIN_RULE', 'admin');
define('MODERATOR_RULE', 'moderator');
define('EDITOR_RULE', 'editor');


/**
 * event types for events listener
 */
define('WEBT_CORE_INIT', 1);

define('WEBT_CORE_CACHE_DATA_CLEAR', 20);
define('WEBT_CORE_CACHE_SERIAL_CLEAR', 21);
define('WEBT_CORE_CACHE_QUERIES_CLEAR', 22);
define('WEBT_CORE_CACHE_STATIC_CLEAR', 23);
define('WEBT_CORE_CACHE_MODULES_CLEAR', 24);
define('WEBT_CORE_CACHE_META_CLEAR', 25);

define('WEBT_CORE_JOB_DONE', 30);

// model events
define('WEBT_MODEL_PRE_SAVE', 51);
define('WEBT_MODEL_POST_SAVE', 52);
define('WEBT_MODEL_POST_PREPARE_SAVE_FIELDS', 53);
define('WEBT_MODEL_PRE_DELETE', 54);
define('WEBT_MODEL_POST_DELETE', 55);
define('WEBT_MODEL_PRE_UPDATE', 56);
define('WEBT_MODEL_POST_UPDATE', 57);
define('WEBT_MODEL_PRE_DUPLICATE', 58);
define('WEBT_MODEL_POST_DUPLICATE', 59);

define('WEBT_CORE_SEARCH_REINDEX', 100);

/** some exceptinos */
define('ERROR_NO_MODEL_FOUND', 1900);

define('ERROR_CONSOLE_WRONG_FORMAT', 2000);

define('ERROR_NO_API_FOUND', 2100);

// regular expressions
$INFO['regualars']['nick'] = '/[a-zA-Z0-9.-]*/';
$INFO['regualars']['field_nick'] = '/[a-zA-Z0-9-_]*/';
$INFO['regualars']['email'] = '/[0-9a-z_\.-]+@[0-9a-z_^\.-]+.[a-z]{2,6}/i';
$INFO['regualars']['url_nick_neg'] = '/[^a-zA-Z0-9-]/is';
$INFO['regualars']['field_nick_neg'] = '/[^a-zA-Z0-9-_]/is'; // use sometimes for nick fields
$INFO['regualars']['field_field_neg'] = '/[^a-zA-Z0-9_]/is'; // use sometimes for nick fields
$INFO['regualars']['url'] = '@((https?://)?([-\w]+\.[-\w\.]+)+\w(:\d+)?(/([-\w/_\.]*(\?\S+)?)?)*)@i';//'/^(http(s?):\\/\\/|ftp:\\/\\/{1})(([a-zA-Z0-9$-_.+!@*\'(),]+\.)+)\w{2,}(\/?)$/i';
$INFO['regualars']['url_full'] = '@((https?://)?([-\w]+\.[-\w\.]+)+\w(:\d+)?(/([-\w/_\.]*(\?\S+)?)?)*)@i';
$INFO['regualars']['datetime'] = '/^\d{2}:\d{2}:\d{2}\s\d{2}-\d{2}-\d{4}$/i';
$INFO['regualars']['float'] = '/^(-)?\d+((\.|,)\d+)?$/i';

/** video **/
$INFO['regualars']['youtube'] = '/watch\?.*v=([^(\&)]*)/';
$INFO['regualars']['youtube_short'] = '/youtu.be\/([^(\&)]*)/';
$INFO['regualars']['youtube_v'] = '/youtube.com\/v\/([^?"]+)[^>]+>/';
$INFO['regualars']['youtube_embed'] = '/youtube.com\/embed\/([^?"]+).*?"[^>]*?/';
$INFO['regualars']['vimeo'] = '/vimeo\.com\/(\d+)/';
$INFO['regualars']['burberry'] = '/http:\/\/live\.burberry\.com(.+)/';
$INFO['regualars']['kseniaschnaider'] = '/http:\/\/kseniaschnaider\.com(.+)/';
$INFO['regualars']['rutube'] = '/http:\/\/rutube\.ru\/tracks\/(\d+).*/';
$INFO['regualars']['sname'] = '/^[A-Za-z0-9А-Яа-я~!@#$%^&\*\(\)_\+\s]+$/Ui';

// data formats
$INFO['formats']['date'] = '%H:%M:%S %d-%m-%Y';


/**
 * Domains for resources
 * Used for domain_crosser (see /lib/functions.php)
 */
$INFO['resource_domains'] = array();
isset($_SERVER['SERVER_NAME']) ? $INFO['resource_domains'][] = $_SERVER['SERVER_NAME'] : null;
    /*'img1.'.$_SERVER['SERVER_NAME'],
     'img2.'.$_SERVER['SERVER_NAME'],
     'img3.'.$_SERVER['SERVER_NAME'],*/


$INFO['resource_domains_cnt'] = count($INFO['resource_domains']);



/**
 * TABLES params
 */

/**
 * tables prefix
 */
$INFO['tbl_prefix']			= "webt_";

$INFO['tables']['tbl_access_keys']		= "webt_access_keys";	// access keys
$INFO['tables']['tbl_banned_ips']		= "webt_banned_ips";	// banned IP-addresses
$INFO['tables']['tbl_cache']			= "webt_cache";		// cache for site
$INFO['tables']['tbl_cache_tags']		= "webt_cache_tags";	// cache for site
$INFO['tables']['tbl_core_jobs']			= "webt_core_jobs";
$INFO['tables']['tbl_fields']			= "webt_fields";
$INFO['tables']['tbl_fields_values']	= "webt_fields_values";
$INFO['tables']['tbl_langs']			= "webt_languages";
$INFO['tables']['tbl_linker']			= "webt_linker";

$INFO['tables']['tbl_search']			= "webt_search";		// full text search
$INFO['tables']['tbl_sessions']			= "webt_sessions";
$INFO['tables']['tbl_sessions_history']			= "webt_sessions_history";


$INFO['tables']['tbl_users']			= "webt_users";
$INFO['tables']['tbl_users_lnk']		= "webt_users_lnk";
$INFO['tables']['tbl_usr_rules']		= "webt_usr_rules";	// all rules for not admin user
$INFO['tables']['tbl_usr_rules_lnk']	= "webt_usr_rules_lnk";	// all rules for not admin user




#!/usr/bin/env php
<?php
/**
 * Console kernel application
 *
 * Date: 25.12.14
 * Time: 00:02
 * @version 1.0
 * @author goshi
 * @package web-T[kernel]
 * 
 * Changelog:
 *	1.0	25.12.2014/goshi 
 */

use webtFramework\Components\Console\oConsoleInput;
use webtFramework\Components\Console\oConsoleOutput;

// parse argv parameters
if ($argv && $argv[0] == 'php'){
    $argv = array_slice($argv, 1, count($argv) - 1, false);
}

// slice current filename
$argv = array_slice($argv, 1, count($argv) - 1, false);

/**
 * possible options list
 */
$options_list = array(
    '--env' => array('title' => 'environment', 'descr' => 'Setup application environment (default - debug)'),
    '--app' => array('title' => 'application', 'descr' => 'Default application to load (sometimes you need to load special settings)'),
    '--help' => array('title' => 'help', 'descr' => 'Show help info'),
    '--debug' => array('title' => 'debug', 'descr' => 'Turn on debug mode'),
);

/**
 * maximum output string length
 */
$max_str_length = 100;

$options = $arguments = array();
$app_found = null;
$option_found = $argument_found = null;

foreach ($argv as $k => $v){

    if (!$app_found && isset($options_list[$v])){

        $option_found = $v;
        $options[$option_found] = true;

    } elseif (!$app_found && $option_found){

        $options[$option_found] = preg_replace('/^\'(.*)\'$/is', '$1', $v);
        $option_found = null;

    } elseif (!$app_found && !preg_match('/^-.*$/is', $v)){

        $option_found = null;
        $app_found = $v;

    } elseif ($app_found && preg_match('/^-.*$/', $v)){

        $argument_found = $v;
        $arguments[$argument_found] = true;

    } elseif ($app_found && $argument_found){

        $arguments[$argument_found] = $v;
        $argument_found = null;

    }
}

/**
 * detect options
 */
if ($options){
    foreach ($options as $k => $v){
        switch ($options_list[$k]['title']){

            /**
             * setup application
             */
            case 'application':
                if ($v)
                    define('WEBT_APP', $v);
                break;

            /**
             * setup environment
             */
            case 'environment':
                if ($v)
                    define('WEBT_ENV', $v);
                break;

            /**
             * turn on debug mode
             */
            case 'debug':
                define('IS_DEBUG', true);
                break;

        }
    }
}

// include bootstrap
include('common.php');

$output = new oConsoleOutput($p);

$output->send(' ');

// set attribute for console app
$p->setVar('APP_TYPE', WEBT_APP_CONSOLE);

try {

    if ($app_found){

        // try to find command
        $app = $p->Console($app_found, array('is_init_bundle' => true));

        if ($app){

            $app->init()->parse(new oConsoleInput($arguments, $argv), $output);

        }

    } else {
        throw new \Exception('error.console.wrong_format', ERROR_CONSOLE_WRONG_FORMAT);
    }

} catch (\Exception $e){

    $output->send('webT::Framework version '.WEBT_VERSION);
    $output->send('Environment: '.WEBT_ENV);
    $output->send(' ');
    $output->send('Usage:');
    $output->send('      '.__FILE__.' [options] [bundle:]command [arguments]');
    $output->send(' ');
    $output->send('Options:');
    $output->send(' ');
    foreach ($options_list as $option => $op_data){

        // detect length of the string
        $string = '  '.str_pad($option, 30, ' ', STR_PAD_RIGHT).' '.$op_data['descr'];


        if (mb_strlen($string) > $max_str_length){

            $new_string = array(mb_substr($string, 0, $max_str_length));
            $string = str_replace($new_string[0], '', $string);
            while (mb_strlen($string) > 0){
                $cut = mb_substr($string, 0, $max_str_length - 30 - 2);
                $new_string[] = str_pad('', 30 + 3, ' ', STR_PAD_LEFT).$cut;
                $string = str_replace($cut, '', $string);
            }

            $string = join("\r\n", $new_string);

        }

        $output->send($string);

    }

    $output->send(' ');

    // now detect all possible commands
    $output->send('Commands:');
    $output->send(' ');

    $output->send('  core');
    // 1. Core
    $files = scandir($p->getVar('FW_DIR').$p->getVar('lib_dir').ucfirst($p->getVar('console_dir')));

    if ($files){

        foreach ($files as $file){
            if ($file != '.' && $file != '..'){
                $module = str_replace('.console.php', '', $file);
                $module = $p->Console('Core:'.$module)->init();
                $output->send('    '.str_pad($module->getTitle(), 30, ' ', STR_PAD_RIGHT).$module->getDescription());
                unset($module);
            }
        }
    }

    $output->send(' ');

    // 2. bundles
    $bundles = scandir($p->getVar('bundles_dir'));

    if ($bundles){
        foreach ($bundles as $bundle){
            if ($bundle != '.' && $bundle != '..' && file_exists($p->getVar('bundles_dir').$bundle.WEBT_DS.$p->getVar('console_dir'))){

                $output->send('  '.$bundle);
                $files = scandir($p->getVar('bundles_dir').$bundle.WEBT_DS.$p->getVar('console_dir'));
                if ($files){

                    foreach ($files as $file){
                        if ($file != '.' && $file != '..'){
                            $module = str_replace('.console.php', '', $file);
                            $module = $p->Console($bundle.':'.$module)->init();
                            $output->send('    '.str_pad($module->getTitle(), 30, ' ', STR_PAD_RIGHT).$module->getDescription());
                            unset($module);
                        }
                    }
                }
                $output->send(' ');

            }
        }
    }

    if ($e->getCode() != ERROR_CONSOLE_WRONG_FORMAT){
        $output->send(' ');
        $output->send('Status: '.$p->trans($e->getMessage()));
    }

}

$output->send(' ');
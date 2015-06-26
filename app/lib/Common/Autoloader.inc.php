<?php
/**
 * Autoloader for web-T::CMS
 *
 * Date: 29.07.14
 * Time: 11:28
 * @version 1.0
 * @author goshi
 * @package web-T[Common]
 * 
 * Changelog:
 *	1.0	29.07.2014/goshi 
 */

namespace webtFramework\Common;

use webtFramework\Core\oPortal;

class Autoloader
{
    const debug = 0;
    public function __construct(){}

    public static function autoload($file)
    {
        /**
         * @var oPortal $p
         */
        global $p, $INFO;

        $vector = explode('\\', $file);
        $loaded = false;

        $base_fw_dir = $INFO['lib_dir'].WEBT_DS;
        if ($vector[0] != 'webtFramework'){
            if (file_exists($INFO['bundles_dir'].$vector[0]))
                $fw_dir = $INFO['bundles_dir'].$vector[0];
            else
                $fw_dir = $INFO['bundles_dir'].mb_strtolower($vector[0]);
        } else
            $fw_dir = $INFO['FW_DIR'];

        if ($p && isset($p->debug) && $p->getVar('is_debug'))
            $p->debug->add('AUTOLOADER: try to connect ' .$file);

        if (isset($vector[1])){
            switch ($vector[1]){

                case 'Core':
                case 'Services':
                case 'Common':
                case 'Project':
                case 'Tools':
                case 'Helpers':
                    $fw_dir .= WEBT_DS.$base_fw_dir;
                    $filepath = $fw_dir.$vector[1].WEBT_DS.$vector[2].'.inc.php';

                    if (file_exists($filepath) && is_file($filepath))
                    {
                        if (Autoloader::debug && $p && isset($p->debug))
                            $p->debug->log('connect ' .$filepath, 'autoloader');

                        require_once($filepath);
                        $loaded = true;

                    }
                    break;

                case 'Apps':
                    if (file_exists($fw_dir.WEBT_DS.$vector[1]))
                        $apps_dir = $fw_dir.WEBT_DS.$vector[1];
                    else
                        $apps_dir = $fw_dir.WEBT_DS.strtolower($vector[1]);

                    $filepath = $apps_dir.WEBT_DS.$vector[2].'.app.php';

                    if (file_exists($filepath) && is_file($filepath))
                    {
                        if (Autoloader::debug && $p && isset($p->debug))
                            $p->debug->log('connect ' .$filepath, 'autoloader');

                        require_once($filepath);
                        $loaded = true;

                    }
                    break;

                case 'Models':
                    $fw_dir .= WEBT_DS.$base_fw_dir;
                    $filepath = $fw_dir.$vector[1].WEBT_DS.$vector[2].'.model.php';

                    if (file_exists($filepath) && is_file($filepath))
                    {
                        if (Autoloader::debug && $p && isset($p->debug))
                            $p->debug->log('connect ' .$filepath, 'autoloader');

                        require_once($filepath);
                        $loaded = true;

                    }
                    break;

                case 'Modules':
                    $fw_dir .= WEBT_DS.$base_fw_dir;
                    $filepath = $fw_dir.$vector[1].WEBT_DS.$vector[2].WEBT_DS.$vector[2].'.inc.php';

                    if (count($vector) == 3 && file_exists($filepath) && is_file($filepath))
                    {
                        if (Autoloader::debug && $p && isset($p->debug))
                            $p->debug->log('connect ' .$filepath, 'autoloader');

                        require_once($filepath);
                        $loaded = true;

                    }
                    break;

                case 'Components':
                    $fw_dir .= WEBT_DS.$base_fw_dir;
                    $v = $vector;
                    unset($v[0]);
                    $filepath = $fw_dir.join(WEBT_DS, $v).'.inc.php';

                    if (file_exists($filepath) && is_file($filepath))
                    {
                        if (Autoloader::debug && $p && isset($p->debug))
                            $p->debug->log('connect ' .$filepath, 'autoloader');

                        require_once($filepath);
                        $loaded = true;

                    }
                    break;

                case 'Interfaces':
                    $fw_dir .= WEBT_DS.$base_fw_dir;
                    $filepath = $fw_dir.$vector[1].WEBT_DS.'i'.substr($vector[2], 1).'.inc.php';

                    if (file_exists($filepath) && is_file($filepath))
                    {
                        if (Autoloader::debug && $p && isset($p->debug))
                            $p->debug->log('connect ' .$filepath, 'autoloader');
                        require_once($filepath);
                        $loaded = true;

                    }
                    break;

            }
        }

        if (!$loaded){
            if (Autoloader::debug && $p && isset($p->debug))
                $p->debug->log('not found ' .$file, 'autoloader');

            // try to find deeper
            $unused = array_slice($vector, 1, count($vector) - 2);
            $filepath = $fw_dir.join(WEBT_DS, $unused).WEBT_DS.$vector[count($vector) - 1].'.inc.php';

            if (file_exists($filepath))
            {
                if (Autoloader::debug)
                    $p->debug->log('connect ' .$filepath, 'autoloader');
                require_once($filepath);

            }

            //var_dump('---last---');
            //var_dump($filepath);//die();
            /*$file = str_replace('\\', WEBT_DS, $file);
            $path = $_SERVER['DOCUMENT_ROOT'] . '../classes';
            $filepath = $_SERVER['DOCUMENT_ROOT'] . '../classes/' . $file . '.php';

            if (file_exists($filepath))
            {
                if (Autoloader::debug)
                    $p->debug->log('connect ' .$filepath, 'autoloader');
                require_once($filepath);

            }
            else
            {
                $flag = true;
                if(Autoloader::debug)
                    $p->debug->log('start recursive search', 'autoloader');

                Autoloader::recursive_autoload($file, $path, $flag);
            }*/

        }

        if ($p && isset($p->debug) && $p->getVar('is_debug'))
            $p->debug->add('AUTOLOADER: connected ' .$file);
    }

    public static function recursive_autoload($file, $path, &$flag)
    {
        /**
         * @var oPortal $p
         */
        global $p;

        if (FALSE !== ($handle = opendir($path)) && $flag)
        {
            while (FAlSE !== ($dir = readdir($handle)) && $flag)
            {

                if (strpos($dir, '.') === FALSE)
                {
                    $path2 = $path .WEBT_DS . $dir;
                    $filepath = $path2 . WEBT_DS . $file . '.php';
                    if(Autoloader::debug)
                        $p->debug->log('search file <b>' .$file .'</b> in ' .$filepath, 'autoloader');

                    if (file_exists($filepath))
                    {
                        if(Autoloader::debug)
                            $p->debug->log('connect ' .$filepath, 'autoloader');

                        $flag = FALSE;
                        require_once($filepath);
                        break;
                    }
                    Autoloader::recursive_autoload($file, $path2, $flag);
                }
            }
            closedir($handle);
        }
    }

    /*private static function StPutFile($data)
    {
        $dir = $_SERVER['DOCUMENT_ROOT'] .'/../var/log/autoloader.log.html';
        $file = fopen($dir, 'a');
        flock($file, LOCK_EX);
        fwrite($file, ('║' .$data .'=>' .date('d.m.Y H:i:s') .' ║' .PHP_EOL));
        flock($file, LOCK_UN);
        fclose ($file);
    }*/

}

\spl_autoload_register('webtFramework\Common\Autoloader::autoload');

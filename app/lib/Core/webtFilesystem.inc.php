<?php
/**
 * ...
 *
 * Date: 03.01.15
 * Time: 11:58
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	03.01.2015/goshi 
 */

namespace webtFramework\Core;


class webtFilesystem {

    /**
     * @var null|oPortal
     */
    protected $_p = null;

    public function __construct(oPortal &$p){

        $this->_p = $p;

    }

    /**
     * method delete files from directory
     *
     * @param string $dir base directory
     * @param bool $is_rec flag for delete files recursively
     * @return bool|int
     */
    public function removeFilesFromDir($dir, $is_rec = false){

        // protection
        if ($dir == '')
            return false;

        if (substr($dir, strlen($dir)-1, 1) != '/')
            $dir .= '/';

        $i = 0;

        if (file_exists($dir)){

            if ($this->_p->server->getOsType() == 'nix' && function_exists('exec')){
                // in unix mode we cant count files for deleting
                // now use rm instead of find - because it is very slow
                exec('rm -'.($is_rec ? 'R' : '').'f '.escapeshellarg($dir)."*", $out);

                //exec('find '.escapeshellarg($dir).' '.($is_rec ? '' : '-maxdepth 1').' -exec rm -'.($is_rec ? ''/*'R'*/ : '').'f \'{}\' \;', $out);

            } else {

                $d = dir($dir);
                while (false !== ($entry = $d->read())) {
                    if ($entry == '.' || $entry == '..') continue;

                    if (is_dir($dir.$entry)){

                        if ($is_rec == 1){

                            $i += (int)$this->removeFilesFromDir($dir.$entry, $is_rec);
                            @rmdir($dir.$entry);

                        }

                    } else {

                        @unlink($dir.$entry);
                        $i++;

                    }

                }

                $d->close();
            }

        }

        return (int)$i;

    }

    /**
     * method return count of cached pages
     * it mean that function will return numbers of FILES (not dirs)
     *
     * @param string $dir directory for getting data
     * @param bool $is_rec flag for get recursively info
     * @param bool $get_size flag for get filesize info
     * @return array return hash with 'size' and 'count' properties
     */
    public function getFilesCount($dir, $is_rec = false, $get_size = false){

        $i = array('count' => 0, 'size' => 0);

        if (substr($dir, strlen($dir)-1, 1) != '/')
            $dir .= '/';

        if (!file_exists($dir))
            return $i;

        $d = dir($dir);
        if (!is_object($d))
            return $i;

        while (false !== ($entry = $d->read())) {
            if ($entry == '.' || $entry == '..') continue;
            if (is_dir($dir.$entry)){
                if ($is_rec == 1){
                    $tmp = $this->getFilesCount($dir.$entry, $is_rec, $get_size);
                    $i['count'] += $tmp['count'];
                    $i['size'] += $tmp['size'];
                } else
                    continue;
            } else {

                $i['count']++;
                $get_size ? $i['size'] += filesize($dir.$entry) : null;
            }
        }

        $d->close();

        return $i;

    }

    /**
     * extract file modified time
     *
     * @param string $file filename for extract modified time
     * @return int
     */
    public function getFileMtime($file){

        if ($file){

            $stat = stat($file);
            return  $stat['mtime'];

        } else {

            return time();
        }

    }


    /**
     * make directories recursive
     */
    public function rmkdir($path, $mode = PERM_DIRS) {

        if (file_exists($path))
            return true;

        $path = rtrim(preg_replace(array("/\\\\/", "/\/{2,}/"), "/", $path), "/");
        $path = str_replace('//', '/', $path);
        $e = explode("/", ltrim($path, "/"));
        if(substr($path, 0, 1) == "/") {
            $e[0] = "/".$e[0];
        }
        $c = count($e);
        $cp = $e[0];
        for($i = 1; $i < $c; $i++) {

            //$old = umask(0);
            if ($cp != '.'){

                if(!is_dir($cp) && !mkdir($cp, $mode)) {
                    //umask($old);
                    return false;
                }
                @chmod($cp, $mode);
            }
            $cp .= "/".$e[$i];
            //echo "udpate rules dir: ".$cp."---".$mode."\n";
            //umask($old);
        }
        $ret = @mkdir($path, $mode);
        @chmod($path, $mode);
        return $ret;
    }




    /**
     * return formated bytes to the normal view
     *
     * @param int $bytes bytes size
     * @return string formated string from
     */
    public function formatSizeFromBytes($bytes){

        return round($bytes/1000000, 2)." MB";

    }

    /**
     * file extension without dot
     *
     * @param $filename
     * @return bool
     */
    public function getFileExtension($filename){

        if (!$filename)
            return false;

        $arr = explode('.', $filename);

        if ($arr[count($arr)-1])
            return $arr[count($arr)-1];
        else
            return false;
    }


    /**
     * return exists image extension
     * @param $pic
     * @return bool|string
     */
    public function getExistsImageExt($pic){

        $exts = array('png', 'gif', 'gif', 'jpg', 'jpeg', 'swf', 'psd', 'bmp', 'tif', 'jpc', 'jp2', 'jpx', 'jb2', 'swc', 'iff', 'jpg', 'wbmp', 'xbm');

        foreach ($exts as $ext){
            if (file_exists($pic.".".$ext))
                return ".".$ext;
        }

        return false;

    }

    /**
     * function writes safety data to files
     * got from http://ua.php.net/manual/en/function.flock.php
     * suggested mode 'a' for writing to the end of the file
     *
     * @param string $path
     * @param string $data
     * @param string $mode current write mode (default is 'wb')
     * @param int $rules rules octet
     * @return bool
     */
    public function writeData($path, $data, $mode = null, $rules = null){

        if (!$mode)
            $mode = 'wb';

        $fp = fopen($path, $mode);
        $retries = 0;
        $max_retries = 100;

        if (!$fp) {
            // failure
            return false;
        }

        // keep trying to get a lock as long as possible
        do {
            if ($retries > 0) {
                usleep(rand(1, 10000));
            }
            $retries += 1;
        } while (!flock($fp, LOCK_EX) and $retries <= $max_retries);

        // couldn't get the lock, give up
        if ($retries == $max_retries) {
            // failure
            return false;
        }

        // got the lock, write the data
        fwrite($fp, $data);
        fflush($fp);
        // release the lock
        flock($fp, LOCK_UN);
        fclose($fp);

        if ($rules){
            @chmod($path, $rules);
        }

        // success
        return true;
    }


    /**
     * method gzip data to file
     * @param null|string $fromfile file, which will be compressed
     * @param string $tofile target filename
     * @param null $data optional data (if $fromfile not provided)
     * @param int $level level of compression (from 0 to 9)
     * @param int $rules file rules (default @const PERM_FILES)
     * @return bool
     */
    public function gzip($fromfile = null, $tofile, $data = null, $level = -1, $rules = null){

        if (function_exists('gzencode') && ($fromfile || $data !== null) && $tofile){

            $this->_p->filesystem->writeData($tofile, gzencode($fromfile ? file_get_contents($fromfile) : $data, $level), "wb", $rules ? $rules : PERM_FILES);

            unset($data);

            return $tofile;
        }

        return false;

    }

} 
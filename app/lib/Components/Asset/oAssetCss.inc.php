<?php
/**
 * Create CSS asset
 *
 * Date: 31.01.15
 * Time: 14:57
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	31.01.2015/goshi 
 */

namespace webtFramework\Components\Asset;

class oAssetCss extends oAssetAbstract {

    protected $_namespace = 'css';

    /**
     * compress filter
     * @param $data
     * @return mixed
     */
    protected function _filterCompress($data){

        $data = preg_replace('!\/\*(.*?)\*\/!i', '', $data); # remove comments
        $data = preg_replace('/\s+/si', ' ', $data); # collapse space
        $data = preg_replace('/\} /si', "}\n", $data); # add line breaks
        $data = preg_replace('/\n$/si', '', $data); # remove last break
        $data = preg_replace('/ \{ /si', ' {', $data); # trim inside brackets
        return preg_replace('/; \}/si', '}', $data); # trim inside brackets

    }

    /**
     * convert reltive pathes to full filter
     * @param $data
     * @param $base_file
     * @param $target_path
     * @return mixed
     */
    protected function _filterConvertPath($data, $base_file, $target_path){

        if (preg_match_all('/(url\(.*?\))/is', $data, $match)){

            foreach ($match[1] as $v){
                // check if path is relative
                $clear = str_replace(array('\'', '"', 'url(', ')'), '', $v);

                if (!preg_match('/^http/is', $v) && !preg_match('/^\//', $v)){
                    $base_path = preg_replace('/\/+/', '/', pathinfo($base_file, PATHINFO_DIRNAME));
                    $arr = explode('/', $base_path);

                    $x = '';
                    $i = 0;
                    do {
                        $x .= $arr[$i].'/';
                        $i++;

                    } while (strpos($target_path, $x.$arr[$i]) !== false);

                    $new_path = str_replace($x, '/', $base_path).'/';
                    $data = str_replace($v, 'url('.$new_path.$clear.')', $data);
                }
            }

        }

        return $data;

    }

    public function build($version = null){

        if ($this->_sources && !empty($this->_sources)){

            $ext = $this->_p->filesystem->getFileExtension($this->_target);

            $path = $this->_p->getVar('BASE_APP_DIR').$this->_p->getVar('assets')[$this->_namespace]['output_dir'];
            $result_file = basename($this->_target, ".".$ext).($version ? ".v".$version : '').".".$ext;

            // additional checking for file exists
            if (file_exists($path.$result_file)){
                return file_get_contents($path.$result_file);
            }

            $compiled = '';
            foreach ($this->_sources as $file){

                if (file_exists($this->_p->getVar('BASE_APP_DIR').WEBT_DS.$file)){

                    $filename = $this->_p->getVar('BASE_APP_DIR').WEBT_DS.$file;

                    // compressing css
                    $data = file_get_contents($filename);

                    // detect filters
                    foreach ($this->_filters as $filter){

                        $method = '_filter'.ucfirst($filter);
                        if (method_exists($this, $method)){
                            $data = $this->$method($data, $filename, $path);
                        }

                    }

                    $compiled .= $data;

                }

                unset($data);

            }

            $this->_p->filesystem->writeData($path.$result_file, $compiled, 'w', PERM_FILES);

            // gziping
            if (isset($this->_filters['gzip']))
                $this->_p->filesystem->gzip(null, $path.$result_file.'.gz', $compiled, 9);

            // cleanup before return
            $this->cleanup();

            return $compiled;

        }

        return null;

    }

} 
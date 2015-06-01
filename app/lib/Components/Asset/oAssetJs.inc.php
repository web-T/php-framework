<?php
/**
 * Javascript asset builder
 *
 * Date: 31.01.15
 * Time: 16:01
 * @version 1.0
 * @author goshi
 * @package web-T[Asset]
 * 
 * Changelog:
 *	1.0	31.01.2015/goshi 
 */

namespace webtFramework\Components\Asset;


class oAssetJs extends oAssetAbstract{

    protected $_namespace = 'js';

    protected function _filterMinify($data){

        if ($data != '' && file_exists($this->_p->getVar('assets')[$this->_namespace]['filter_minify']['library'])){

            require_once($this->_p->getVar('assets')[$this->_namespace]['filter_minify']['library']);
            if (class_exists($this->_p->getVar('assets')[$this->_namespace]['filter_minify']['class'])){
                $class = $this->_p->getVar('assets')[$this->_namespace]['filter_minify']['class'];
                $method = $this->_p->getVar('assets')[$this->_namespace]['filter_minify']['method'];
                $data = $class::$method($data);
            }
        }
        return $data;

    }

    public function build($version = null){

        if ($this->_sources && !empty($this->_sources)){

            $ext = $this->_p->filesystem->getFileExtension($this->_target);

            $path = $this->_p->getVar('BASE_APP_DIR').$this->_p->getVar('assets')[$this->_namespace]['output_dir'].basename($this->_target, ".".$ext).($version ? ".v".$version : '').".".$ext;

            // additional checking for file exists
            if (file_exists($path)){
                return file_get_contents($path);
            }

            $compiled = '';
            foreach ($this->_sources as $file){

                if (is_array($file)){
                    $settings = $file;
                    $file = $file['filename'];
                } else {
                    $settings = array();
                }

                if (!isset($settings['deprecated_filters'])){
                    $settings['deprecated_filters'] = array();
                }

                if (file_exists($this->_p->getVar('BASE_APP_DIR').WEBT_DS.$file)){

                    $filename = $this->_p->getVar('BASE_APP_DIR').WEBT_DS.$file;

                    $data = file_get_contents($filename);

                    // detect filters
                    foreach ($this->_filters as $filter){

                        $method = '_filter'.ucfirst($filter);
                        if (method_exists($this, $method) && !isset($settings['deprecated_filters'][$filter])){
                            $data = $this->$method($data);
                        }

                    }

                    $compiled .= $data."\r\n";

                }

                unset($data);

            }


            $this->_p->filesystem->writeData($path, $compiled, 'w', PERM_FILES);

            // gziping
            if (isset($this->_filters['gzip']))
                $this->_p->filesystem->gzip(null, $path.'.gz', $compiled, 9);

            // cleanup before return
            $this->cleanup();

            return $compiled;

        }

        return null;

    }


} 
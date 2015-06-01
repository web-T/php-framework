<?php
/**
 * Core Api methods
 *
 * Date: 16.02.15
 * Time: 06:50
 * @version 1.0
 * @author goshi
 * @package web-T[Api]
 * 
 * Changelog:
 *	1.0	16.02.2015/goshi 
 */

namespace webtFramework\Api;

use webtFramework\Interfaces\oApi;
use webtFramework\Components\Response\oResponse;

class Core extends oApi{

    protected $_authMethods = array('checkAllowCoreMethods');

    /**
     * method checking API key for executing core methods
     * @param $data
     * @return bool
     */
    public function checkAllowCoreMethods($data){

        $apikey = $this->_p->query->request->get('apikey');

        $found = false;
        if ($apikey && isset($this->_p->getVar('EXTERNAL_API_KEYS')['INSTANCES']) && $this->_p->getVar('EXTERNAL_API_KEYS')['INSTANCES'] == $apikey){
            $found = true;
        }

        return $found;

    }

    /**
     * make empty response
     * need for some core functions
     */
    protected function _makeEmptyResponse(){
        ob_end_clean();
        header("Connection: close\r\n");
        header("Content-Encoding: none\r\n");
        header("Content-Length: 1");
    }

    public function cacheRemoveStaticPagePost($data){

        $this->_makeEmptyResponse();
        $this->_p->cache->removeStaticPage($data['value']);

        return new oResponse('', 200);

    }

    public function cacheRemovePagePost($data){

        $this->_makeEmptyResponse();
        $this->_p->cache->remove($data['value'], $data['params']);

        return new oResponse('', 200);
    }

    public function cacheRemoveSerialPost($data){

        $this->_makeEmptyResponse();
        $this->_p->cache->removeSerial($data['value'], $data['dir']);

        return new oResponse('', 200);
    }

    public function cacheClearPost($data){

        $this->_makeEmptyResponse();
        $this->_p->cache->clear($data['value']);

        return new oResponse('', 200);
    }

    public function searchIndexPost($data){

        $this->_makeEmptyResponse();
        $this->_p->Module('oSearch')->index(array_merge(array('no_common_index' => true), (array)$data['value']));

        return new oResponse('', 200);
    }

    public function filesPutPost($data){

        // filterting path
        if ($_FILES['file'] && $_FILES['file']['error'] == 0 && $data['path']){
            // detecting target path
            $data['path'] = str_replace(array('../', '//'), '/', $data['path']);
            $data['path'] = preg_replace('/^\.(.*)$/is', '$1', $data['path']);
            //dump_file(array('parsed_path' => $data['path']));
            // now checking for '/files/' begin
            if (preg_match('/^\/(files|cache|cache_mem)\/.+$/is', $data['path'])){

                // preparing all categories
                $path = dirname($data['path']);

                //dump_file(array('dirname' => $path));
                $this->_p->filesystem->rmkdir($this->_p->getDocDir().$path);

                if (move_uploaded_file($_FILES['file']['tmp_name'], './'.$this->_p->getDocDir().$data['path'])) {
                    @chmod('./'.$this->_p->getDocDir().$data['path'], 0666);
                    $this->_p->debug->log('API :: save file '.'./'.$this->_p->getDocDir().$data['path']);
                } else {
                    $this->_p->debug->log('API :: ERROR :: cannot save file '.'./'.$this->_p->getDocDir().$data['path']);
                }
            }

        }

        return new oResponse('', 200);
    }

    public function filesDeletePost($data){

        // filterting path
        if ($data['path']){
            // detecting target path
            $data['path'] = str_replace(array('../', '//'), '/', $data['path']);
            $data['path'] = preg_replace('/^\.(.*)$/is', '$1', $data['path']);
            // now checking for '/files/' begin
            if (preg_match('/^\/files\/.+$/is', $data['path']) && file_exists($this->_p->getDocDir().$data['path'])){

                if (unlink($this->_p->getDocDir().$data['path'])) {
                    $this->_p->debug->log('API :: delete file '.$this->_p->getDocDir().$data['path']);
                } else {
                    $this->_p->debug->log('API :: ERROR :: cannot delete file '.$this->_p->getDocDir().$data['path']);
                }
            }

        }

        return new oResponse('', 200);
    }


} 
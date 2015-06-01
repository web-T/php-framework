<?php
/**
 * web-T::CMS Access keys controller
 *
 * Date: 16.04.13
 * Time: 17:50
 * @version 1.0
 * @author goshi
 * @package web-T[share]
 *
 * Changelog:
 *    1.0    16.04.2013/goshi
 */

namespace webtFramework\Services;

use webtFramework\Core\oPortal;
use webtFramework\Interfaces\oBase;

/**
 * Class oKeys definition
 * @package web-T[share]
 */
class oKeys extends oBase{

    /**
     * property for timeout for a preview key
     * @var int
     */
    protected $_expired_timeout = 600;

    //protected $_work_tbl = null;

    /**
     * base constructor
     * @param oPortal $p
     * @param array $params
     */
    public function __construct(oPortal &$p, $params = array()){

        parent::__construct($p, $params);

    }

    /**
     * generate key name
     * @param $num
     * @param int $b
     * @return string
     */
    protected function _toBase($num, $b = 62) {
        $base='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $r = $num  % $b ;
        $res = $base[$r];
        $q = floor($num/$b);
        while ($q) {
            $r = $q % $b;
            $q =floor($q/$b);
            $res = $base[$r].$res;
        }
        return $res;
    }

    /**
     * method generate new key and
     * @param string $work_tbl work table for acess
     * @param int $elem_id element id
     * @param int $expired expired date in seconds
     * @return string
     */
    public function generate($work_tbl = null, $elem_id = null, $expired = null){

        // setting expired
        if (!$expired){
            $expired = $this->_p->getTime() + $this->_expired_timeout;
        }

        $data = array('expired' => $expired);
        $data['key'] = md5($this->_p->query->request->getHeaders()['REFERER'] . mt_rand(0, 1000000000) . md5($this->_p->getTime())).md5(mt_rand(0, 10000000000) . $this->_p->query->request->getHeaders()['REFERER'] . md5($this->_p->getTime()));

        if ($work_tbl){
            $data['tbl_name'] = (string)$work_tbl;
        }

        if ($elem_id){
            $data['elem_id'] = (int)$elem_id;
        }

        // setting current admin id
        if ($this->_p->user->getId('backend')){
            $data['adm_user_id'] = (int)$this->_p->user->getId('backend');
        }

        $em = $this->_p->db->getManager();
        $model = $this->_p->Model('AccessKey');
        $model->setModelData($data);
        $em->initPrimaryValue($model);
        $em->save($model);

        return $data['key'];

    }

    /**
     * method checking for a working nonexpired key
     *
     * @param string $key key
     * @param string $tbl_name table for checking access
     * @param int $elem_id
     * @return bool
     */
    public function check($key, $tbl_name = null, $elem_id = null){

        if (trim($key) == ''){
            return false;
        }

        $conditions = array('no_array_key' => true, 'select' => array('a' => 'key'), 'where' => array('key' => $key, 'expired' => array('op' => '>=', 'value' => $this->_p->getTime())));

        if ($tbl_name){
            $conditions['where']['tbl_name'] = $tbl_name;
        }

        if ($elem_id){
            $conditions['where']['elem_id'] = $elem_id;
        }

        $model = $this->_p->Model('AccessKey');

        return (boolean)$this->_p->db->selectCell($this->_p->db->getQueryBuilder($model->getModelStorage())->compile($model, $conditions), $model->getModelStorage());

    }

    /**
     * cleanup old access keys
     */
    public function cleanup(){

        $model = $this->_p->Model('AccessKey');

        $this->_p->db->query($this->_p->db->getQueryBuilder($model->getModelStorage())->compileDelete($model, array('where' => array('expired' => array('op' => '<', 'value' => $this->_p->getTime())))), $model->getModelStorage());

    }

}

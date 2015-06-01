<?php
/**
 * Special virtual field type
 *
 * Date: 15.02.15
 * Time: 13:03
 * @version 1.0
 * @author goshi
 * @package web-T[Forms]
 * 
 * Changelog:
 *	1.0	15.02.2015/goshi 
 */

namespace webtFramework\Components\Form\Fields;


use webtFramework\Components\Form\oField;

class oFieldVirtual extends oField {


    /**
     * method return row for virtual field
     * @param null $data
     * @param array $params
     * @return array|mixed|null|string|void
     */
    public function getValue($data = null, $params = array()){

        $full_row = $this->_oForms->getData();

        if (isset($this->_handlerNodes)){
            if (is_array($this->_handlerNodes)){

                $data = array();
                foreach ($this->_handlerNodes as $v){
                    $data[] = is_array($full_row[$v]) ? current($full_row[$v]) : $full_row[$v];
                }
                return join(' ', $data);
            } else {
                return is_array($full_row[$this->_handlerNodes]) ? current($full_row[$this->_handlerNodes]) : $full_row[$this->_handlerNodes];
            }
        } elseif ($this->_handlerExternal){

            if (isset($this->_handlerExternal['source']['model']) && ($model = $this->_p->Model($this->_handlerExternal['source']['model']))){

                $conditions = array(
                    'no_array_key' => true,
                    'where' => array(
                        $this->_handlerExternal['source']['targetField'] => $full_row[$this->_handlerExternal['source']['sourceField']]),
                    'select' => array());

                if ($model->getIsMultilang()){
                    $conditions['where']['lang_id'] = $this->_p->getLangId();
                }

                if (!isset($this->_handlerExternal['source']['select'])){
                    $conditions['select']['a'] = $model->getPrimaryKey();
                } else {
                    $conditions['select']['a'] = $this->_handlerExternal['source']['select'];
                }

                $sql = $this->_p->db->getQueryBuilder()->compile($model, $conditions);

                $data = $this->_p->db->selectRow($sql);
                if ($data){
                    $data = join(' ', $data);
                } else {
                    $data = '';
                }

                return $data;

            } elseif ($this->_p->getVar($this->_handlerExternal['source']['tbl_name'])){
                // for backward compatibility

                $tbl_fields = describe_table($this->_p, $this->_p->getVar($this->_handlerExternal['source']['tbl_name']));
                if (!isset($this->_handlerExternal['source']['select'])){
                    $this->_handlerExternal['source']['select'] = get_primary($tbl_fields);
                }
                if (!is_array($this->_handlerExternal['source']['select']))
                    $this->_handlerExternal['source']['select'] = array($this->_handlerExternal['source']['select']);

                array_walk($this->_handlerExternal['source']['select'], 'alter_field');

                $sql = 'SELECT '.join(',', $this->_handlerExternal['source']['select']).' FROM '.$this->_p->getVar($this->_handlerExternal['source']['tbl_name']).' WHERE
                                    `'.qstr($this->_handlerExternal['source']['targetField']).'`="'.qstr($full_row[$this->_handlerExternal['source']['sourceField']]).'" '.(in_array('lang_id', $tbl_fields) ? ' AND lang_id='.(int)$this->_p->getLangId() : '');

                $data = $this->_p->db->selectRow($sql);
                if ($data){
                    $data = join(' ', $data);
                } else {
                    $data = '';
                }

                return $data;
            }
        }

        return null;
    }

    public function get($data = null, $params = array()){

        return array();

    }

    public function save($value, &$row = array(), &$old_data, $lang_id = null){

        return null;

    }



} 
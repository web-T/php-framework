<?php
/**
 * Autocompleter field
 *
 * Date: 11.08.14
 * Time: 20:57
 * @version 1.0
 * @author goshi
 * @package web-T[Forms]
 * 
 * Changelog:
 *	1.0	11.08.2014/goshi 
 */

namespace webtFramework\Components\Form\Fields;

use webtFramework\Components\Form\oField;
use webtFramework\Services\oForms;
use webtFramework\Core\oPortal;

/**
 * Autocompleter field
 * @package web-T[CMS]
 */
class oFieldAutocompleter extends oField{

    public function __construct(oPortal $p, oForms $oForms, $params = array()){

        parent::__construct($p, $oForms, $params);

    }

    public function save($value, &$row = array(), &$old_data, $lang_id = null){

        $f_params = $this->_params_backup;
        unset($f_params['visual']);

        $fc = new oForms($this->_p, array(
            'base' => $this->_base,
            'data' => $value ? array($this->_field_id => $value) : null,
            'fields' => array($this->_field_id => $f_params),
            'multilang' => $this->_oForms->_multilang,
            'upload_dir' => $this->_oForms->_upload_dir,
        ));

        return unqstr($fc->getSaveField($this->_field_id, $row, $old_data, $lang_id));

    }

    /**
     * generate autcomplete field
     * @param null|array $data
     * @param array $params
     * @return array|void
     */
    public function get($data = null, $params = array()){

        parent::get($data, $params);

        $f_params = $this->_params_backup;
        unset($f_params['visual']);

        $fc = new oForms($this->_p, array(
            'base' => $this->_base,
            'data' => $data ? array($this->_field_id => $data) : null,
            'fields' => array($this->_field_id => $f_params),
            'multilang' => $this->_oForms->_multilang,
            'upload_dir' => $this->_oForms->_upload_dir,
        ));

        return $fc->getField($this->_field_id, null, $params['name_add']);

    }
}
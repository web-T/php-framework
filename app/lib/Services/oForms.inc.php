<?php

/**
 * web-T::CMS fields controller
 * Forms service
 *
 * @version 4.0
 * @author goshi
 * @package web-T[share]
 *
 */

namespace webtFramework\Services;

use webtFramework\Core\oPortal;
use webtFramework\Interfaces\oBase;
use webtFramework\Interfaces\oModel;
use webtFramework\Helpers\Images;

class oForms extends oBase{

    /**
     * base for all field forms
     * @var string
     */
    protected $_base = null;

    /**
     * callbacks for some operations
     * @var array
     */
    protected $_callbacks = array();

    /**
     * caller method, which have check mehods for unique values
     * @var null|\Frontend\Interfaces\oClip
     * @deprecated use 'callbacks'
     */
    protected $_caller	=	null;

    /**
     * multilang property of the form
     * @var string
     */
    protected $_multilang	=	'';

    /**
     * directory for uploads
     * @var string
     */
    protected $_upload_dir	=	'';

    /**
     * current fields list
     * @var array
     */
    protected $_fields = array();

    /**
     * form's data
     * @var array
     */
    protected $_data = array();

    /**
     * primary form's key
     * @var null
     */
    protected $_primary = null;

    /**
     * @var null|oModel
     */
    protected $_model = null;

    /**
     * validate info (injected from external software)
     * @var array
     */
    protected $_non_valid_fields = array();
	protected $_non_valid_details = array();

    /**
     * postfix for generate id of the fields
     * @var string
     */
    protected $_fields_postfix = '';

    /**
     * field types aliases
     * @var array
     */
    protected $_aliases = array(
        'boolean' => array('bool'),
        'integer' => array('int'),
        'float' => array('double'),
    );

    /********************    special parameters     ************************/
	protected $_temp = array();
    protected $_debug = false;

    /**
     * collection of @oField objects
     * @var array
     */
    protected $_fields_cache = array();


	public function __construct(oPortal &$p, $params = array()){

		if ($p->getVar('is_debug')){
			$p->debug->add("oForms: __construct");
		}

		parent::__construct($p, $params);

        if (!$this->_base)
            $this->_base = $this->_p->getVar('forms')['base'];

		// init array for parsed fields
		$this->_temp['always_parsed'] = array();

	}

    /**
     * reinit fields controller for reuse
     * @return oForms $this
     */
    public function init(){

        $this->_base = 'ch_elem';
        $this->_caller	=	null;
        $this->_callbacks	=	array();
	    $this->_multilang	=	'';
        $this->_upload_dir	=	'';

	    $this->_fields = array();
	    $this->_data = array();
        $this->_non_valid_fields = array();
	    $this->_non_valid_details = array();

	    $this->_fields_postfix = '';
        $this->_model = null;

        return $this;
    }

    /**
     * override standart AddParam method
     * @param array $params
     * @return oForms
     */
    public function AddParams($params = array()){

        $result = parent::AddParams($params);


        if (isset($params['model'])){

            $params['model'] = $this->_p->Model($params['model']);

            if ($params['model'] instanceof oModel){
                // update other parameters
                if (!isset($params['fields'])){
                    $this->_fields = $params['model']->getModelFields();
                }
                if (!isset($params['multilang'])){
                    $this->_multilang = $params['model']->getIsMultilang();
                }
                if (!isset($params['upload_dir'])){
                    $this->_upload_dir = $params['model']->getUploadDir();
                }
                if (!$this->_primary){
                    $this->_primary = $params['model']->getPrimaryKey();
                }
            }
        } else {
            if (!$this->_primary){
                foreach ($this->_fields as $k => $v){
                    if ($v['primary']){
                        $this->_primary = $k;
                        break;
                    }
                }
            }

        }

        return $result;

    }

    /**
     * checking empty value
     * @param $value
     * @param bool $fullcheck set to true if full empty checking must be set, otherwise it was checking only for one value of the array
     * @param bool $is_integer set to true if value must be integer - then we always check for zero value
     * @return bool
     */
    public function checkEmpty($value, $fullcheck = false, $is_integer = false){

		if (is_array($value)){
			$is_empty = true;
			foreach ($value as $x){
				$cur = !$this->checkEmpty($x, false, $is_integer);
				if ($fullcheck && $cur){
					return false;
				} elseif (!$fullcheck && !$cur) {
					$is_empty = false;
				}
			}

			if (!$fullcheck && $is_empty){
				return false;
			}

			return true;
		} else {
			// no variant for modes
            //dump_file(array('empty' => array($is_integer, $value, is_numeric($value))));
			$valid = isset($value) && $value != '';
            if ($valid && $is_integer && is_numeric($value))
                $valid = $valid && $value != 0;
            return $valid;
		}
	}


	/**
	* checking multistore field for empty value
	*/
	public function checkMultistoreEmpty($value){

		if (is_array($value)){
			$is_empty = true;
			foreach ($value as $x){
				$is_empty = $is_empty && $this->checkMultistoreEmpty($x);
				if (!$is_empty) {
					return false;
				}
			}

			return true;
		} else {
			// no variany for modes
			return isset($value) && $value == '';
		}
	}


	/**
	* checking value for maximum length
	*/
	public function checkMaxMinValue($value, $cond = array()){

		if (is_array($value)){
			foreach ($value as $x){
				if (!$this->checkMaxMinValue($x, $cond)){
					return false;
				}
			}
			return true;
		} else {
			if (empty($cond))
				return true;

			$state = true;
			if (isset($cond['min']) && is_numeric($cond['min']) && $value < $cond['min'])
				$state = $state && false;

			if (isset($cond['max']) && is_numeric($cond['max']) && $value > $cond['max'])
				$state = $state && false;

			return $state;
		}
	}



	/**
	* checking value for maximum length
	*/
	public function checkMaxlength($value, $length){

		if (is_array($value)){
			foreach ($value as $x){
				if (!$this->checkMaxlength($x, $length)){
					return false;
				}
			}
			return true;
		} else {
			return !(mb_strlen($value, $this->_p->getVar('codepage')) > $length);
		}
	}


	/**
	* checking value for minimum length
	*/
	public function checkMinlength($value, $length){

		if (is_array($value)){
			foreach ($value as $x){
				if (!$this->checkMinlength($x, $length)){
					return false;
				}
			}
			return true;
		} else {
			return !(mb_strlen($value, $this->_p->getVar('codepage')) < $length);
		}
	}


	/**
	* checking value for minimum length
	*/
	public function checkRegular($value, $regexp){

		if (is_array($value)){
			foreach ($value as $x){
				if (!$this->checkRegular($x, $regexp)){
					return false;
				}
			}
			return true;
		} else {
			if ($value != '')
				return preg_match($regexp, $value);
			else
				return true;
		}
	}


    /**
     * method gets default value for selected field
     * @param $field
     * @return int|string
     */
    protected function _getDefaultValue($field){

        $ofield = $this->getFieldObject($field, array('field_id' => $field, 'owner_id' => $this->_fields[$field]['owner_id']));

        return $ofield->getDefaultValue();

    }


    /**
     * method update current field data with populating $data array with default field values
     */
    public function updateDefaultValues(){

        // if always some posted data - simply exit
        if (!empty($this->_data) && ($this->_data['real_id'] || $this->_data['id'])) return $this;

        if ($this->_fields && is_array($this->_fields) && !empty($this->_fields)){
            foreach ($this->_fields as $k => $v){

                if (!isset($this->_data[$k])){

                    $v['default_value'] = $this->_getDefaultValue($k);

                    if ($v['multilang']){
                        $value = array();
                        // collect values
                        foreach ($this->_p->getLangs() as $x => $y){

                            // check for defaule value of the field
                            $value[$x] = isset($v['default_value']) ? trim($v['default_value']) : '';
                        }

                    } else {

                        $value = isset($v['default_value']) ? trim($v['default_value']) : '';

                    }

                    $this->_data[$k] = $value;
                }

            }
        }

        return $this;
    }

    /**
     * validate data
     * @param array $params
     * @return array
     */
    public function validate($params = array()){

		$this->AddParams($params);

		$non_valid_fields = array();

		if ($this->_fields && is_array($this->_fields) && !empty($this->_fields)){
			foreach ($this->_fields as $k => $v){

				$check_result = true;

                // check for fields object
                $ofield = $this->getFieldObject($k, array('field_id' => $k, 'owner_id' => $v['owner_id']));

                // check if field is multilanguage
                $v['default_value'] = $this->_getDefaultValue($k);

				if ($v['multilang']){
					$value = array();
					// collect values
					foreach ($this->_p->getLangs() as $x => $y){

						// check helptext field value
						if (isset($v['helptext']) && $v['helptext'] != '' && trim($this->_data[$k][$x]) == $v['helptext'])
							$this->_data[$k][$x] = '';

						// check for default value of the field
						$use_def_value = empty($this->_p->query->request->getPost()) && !isset($this->_data[$k][$x]) && isset($v['default_value']);

						$value[$x] = $use_def_value ? trim($v['default_value']) : (!is_array($this->_data[$k][$x]) ? trim($this->_data[$k][$x]) : $this->_data[$k][$x]);
					}
				} else {
					if ($v['multistore'] && is_array($this->_data[$k])){
						$value = array();
						foreach ($this->_data[$k] as $x => $y){
							if (isset($v['helptext']) && $v['helptext'] != '' && trim($y) == $v['helptext'])
								$this->_data[$k][$x] = '';

							// check for defaule value of the field
							$use_def_value = empty($this->_p->query->request->getPost()) && !isset($this->_data[$k][$x]) && isset($v['default_value']);

							$value[$x] = $use_def_value ? trim($v['default_value']) : (!is_array($this->_data[$k][$x]) ? trim($this->_data[$k][$x]) : $this->_data[$k][$x]);

						}

					} else {
						if (isset($v['helptext']) && $v['helptext'] != '' && !is_array($this->_data[$k]) && trim($this->_data[$k]) == $v['helptext'])
							$this->_data[$k] = '';

						$value = empty($this->_p->query->request->getPost()) && !isset($this->_data[$k]) && isset($v['default_value']) ? trim($v['default_value']) : (!is_array($this->_data[$k]) ? trim($this->_data[$k]) : $this->_data[$k]);
					}

				}


				// check for unique value
				if (is_array($v['unique']) && !empty($value)){

					// checking in blacklist
					if (isset($v['unique']['black_list']) && is_array($v['unique']['black_list']) && in_array($value, $v['unique']['black_list'])){
						$this->_non_valid_details[$k]['unique_black_list'] = $check_result = false;
					}

					// if not in black list - then check unique by callback
                    $_data = array(
                        'field' => $k,
                        'value' => $value,
                        'exclude' => $v['unique']['exclude'],
                        'params' => array()
                    );

                    if (isset($v['unique']['params'])){
                        foreach ($v['unique']['params'] as $pk => $pv){
                            if ($pk == 'owner_field'){
                                $_data['params']['owner_id'] = $pv;
                            } else {
                                $_data['params'][$pk] = $pv;
                            }
                        }
                    }

                    if ($check_result && $this->_callbacks && isset($this->_callbacks['unique'])){

                        if (is_callable($this->_callbacks['unique'])){

                            $_data = $this->_callbacks['unique']($this->_model, $_data);

                        } else {

                            $_data = call_user_func_array($this->_callbacks['unique'], array($this->_model, $_data));

                        }

						if ($_data){
							$this->_non_valid_details[$k]['unique'] = $check_result = false;
						}
					} elseif ($check_result && method_exists($ofield, 'validateUnique')){

                        $this->_non_valid_details[$k]['unique'] = $check_result = $ofield->validateUnique($_data);

                    }

                    unset($_data);
				}

				// check for empty
				if ((isset($v['empty']) && !$v['empty']) || (isset($v['fullempty']) && !$v['fullempty'])){

                    if (method_exists($ofield, 'validateEmpty')){
                        $check_result = $check_result && ($this->_non_valid_details[$k]['empty'] = $ofield->validateEmpty($value));
                    } else {
                        $check_result = $check_result && ($this->_non_valid_details[$k]['empty'] = $this->checkEmpty($value, (isset($v['fullempty']) && !$v['fullempty']), ($v['type'] == 'integer' || $v['type'] == 'int')));
                    }

				}

				// check for max min value
				if (isset($v['minvalue']) || isset($v['maxvalue'])){

                    if (method_exists($ofield, 'validateMaxMinValue')){
                        $check_result = $check_result && ($this->_non_valid_details[$k]['maxvalue'] = $ofield->validateMaxMinValue($value));
                    } else {
                        $check_result = $check_result && ($this->_non_valid_details[$k]['maxvalue'] = $this->checkMaxMinValue($value, array('min' => $v['minvalue'], 'max' => $v['maxvalue'])));
                    }

				}


				// check for regular xpressions
				$empty = (!isset($v['empty']) || $v['empty']) && ($v['multistore'] ? $this->checkMultistoreEmpty($value) : $value == '') ;

				if (!$empty && isset($v['fieldReg']) && $v['fieldReg']){

                    if (method_exists($ofield, 'validateRegular')){
                        $check_result = $check_result && ($this->_non_valid_details[$k]['fieldReg'] = $ofield->validateRegular($value, isset($this->_p->getVar('regualars')[$v['fieldReg']]) ? $this->_p->getVar('regualars')[$v['fieldReg']] : null));
                    } else {
                        $check_result = $check_result && ($this->_non_valid_details[$k]['fieldReg'] = $this->checkRegular($value, isset($this->_p->getVar('regualars')[$v['fieldReg']]) ? $this->_p->getVar('regualars')[$v['fieldReg']] : $v['fieldReg']));
                    }

				}

				// check for max length
				if (isset($v['maxlength']) && $v['maxlength']){

                    if (method_exists($ofield, 'validateMaxlength')){
                        $check_result = $check_result && ($this->_non_valid_details[$k]['maxlength'] = $ofield->validateMaxlength($value));
                    } else {
					    $check_result = $check_result && ($this->_non_valid_details[$k]['maxlength'] = $this->checkMaxlength($value, $v['maxlength']));
                    }
				}

				// check for min length
				if (!$empty && isset($v['minlength']) && $v['minlength']){
                    if (method_exists($ofield, 'validateMinlength')){
                        $check_result = $check_result && ($this->_non_valid_details[$k]['minlength'] = $ofield->validateMinlength($value));
                    } else {
					    $check_result = $check_result && ($this->_non_valid_details[$k]['minlength'] = $this->checkMinlength($value, $v['minlength']));
                    }
				}

				// check for data type and applying filter
				if (isset($v['multistore']) && $v['multistore'] && is_array($value)){
					$values = $value;
				} else
					$values = array($value);

				foreach ($values as $tmp_value){

                    $this->_non_valid_details[$k]['controller'] = $ofield->check($tmp_value, $this->_data);
                    $check_result = $check_result && $this->_non_valid_details[$k]['controller']['valid'];

 				}

				if (!$check_result){
					$non_valid_fields[] = $k;
				}

				if (isset($this->_data[$k]) || (empty($this->_p->query->request->getPost()) && isset($v['default_value'])))
					$this->_data[$k] = $value;

			}

		}

		return array('non_valid' => $non_valid_fields, 'valid_data' => $this->_data, 'valid_details' => $this->_non_valid_details);

	}


	/**
     * method prepare field for saving by its type
     */
	public function getSaveField($field, &$full_row, &$old_data, $lang_id = null){

		// check access to field
		$access = true;
		if (isset($this->_fields[$field]['rules']) && is_array($this->_fields[$field]['rules'])){
			$access = $this->_p->user->checkFieldRules($this->_fields[$field]['rules'], array('edit', 'add'), $this->_p->getApplication());
		}

        // set primary key
        if (!$this->_primary){
            foreach ($this->_fields as $k => $v){
                if ($v['primary']){
                    $this->_primary = $k;
                    break;
                }
            }
        }

        /**
		* because boolean dont send any info from browser - remove it from checking
		* Readonly fields can be readonly only for old data
		*/

		if ((!isset($full_row[$field]) && $this->_fields[$field]['type'] != 'boolean') || !$access || ($this->_fields[$field]['readonly'] && $full_row[$this->_primary] != 'NULL')){
			$value = $this->_multilang && isset($old_data[$full_row['lang_id']]) ? $old_data[$full_row['lang_id']][$field] : (isset($old_data[$field]) ? $old_data[$field] : '');
			//echo "old---".$field.'---'.$value."---".$old_data[$full_row['lang_id']][$field]."---".$old_data[$field]."---".$full_row[$this->_primary]."<br>";
		} else {
			//$value = $this->_fields[$field]['multilang'] ? $full_row[$field][$full_row['lang_id']] : $full_row[$field];
			$value = $this->_fields[$field]['multilang'] ? (isset($full_row[$field][$full_row['lang_id']]) ? $full_row[$field][$full_row['lang_id']] : $old_data[$field]) : (isset($full_row[$field]) ? $full_row[$field] : ($this->_fields[$field]['type'] != 'boolean' ? $old_data[$field] : ''));
			//echo "new---".$field.'---'.$value."---".$full_row[$field]."----".$full_row[$field][$full_row['lang_id']]."---<br>";

            // checking for custom value
            if ($this->_fields[$field]['visual']['is_custom_value'] && $value == '_custom_'){
                $value = $full_row[$field.'_custom'];
            }
		}

		$values = $datas = array();
		if ($this->_fields[$field]['multistore']){
			if (!is_array($value))
				$values = explode($this->_p->getVar('forms')['fields_multistore_sep'], $value);
			else
				$values = $value;
		} else {
			$values[] = $value;
		}

		foreach ($values as $curr_value){

            // check helptext field value
            if (isset($this->_fields[$field]['helptext']) && $this->_fields[$field]['helptext'] != '' && trim($curr_value) == $this->_fields[$field]['helptext'])
                $curr_value = '';

            $ofield = $this->getFieldObject($field, array('field_id' => $field, 'owner_id' => $this->_fields[$field]['owner_id']));

			$data = $ofield->save($curr_value, $full_row, $old_data, $lang_id);

			$datas[] = $data;
		}

		$datas = $this->_fields[$field]['multistore'] ? join($this->_p->getVar('forms')['fields_multistore_sep'], $datas) : (!is_array($datas) ? join('', $datas) : $datas[0]);

		if ($this->_fields[$field]['null'] == true && $datas == '')
			$datas = 'NULL';

		return $datas;

	}

    /**
     * getter for custom fields tree
     * @return null
     */
    public function getCustomFieldsTree(){

        if (!$this->_p->getVar('custom_fields'))
            $this->initCustomFieldsTree();

        return $this->_p->getVar('custom_fields');

    }


	/**
	* initialize custom fields tree
     * TODO: refactor to dynamic fields tree generation
	*/
	public function initCustomFieldsTree(){

        if ($this->_p->getVar('custom_fields') && count($this->_p->getVar('custom_fields')) > 0)
            return $this;

        $this->_p->setVar('custom_fields', oTree::build($this->_p, array('model' => 'Field'), false, function(oPortal &$p, &$elem, &$params){

            $elem['type'] = $elem['field_type'];

            $elem['multilang'] = isset($elem['multilang']) && $elem['multilang'] ? $elem['multilang'] : false;
            $elem['empty'] = isset($elem['empty']) && $elem['empty'] ? false : true;
            $elem['fullempty'] = isset($elem['fullempty']) && $elem['fullempty'] ? false : true;

            $elem['default_value'] = $elem['default_value'] != '' ? $elem['default_value'] : null;
            $elem['helptext'] = $elem['helptext'] != '' ? $elem['helptext'] : null;
            $elem['maxlength'] = $elem['maxlength'] ? $elem['maxlength'] : null;
            $elem['max_input_size'] = $elem['max_input_size'] ? $elem['max_input_size'] : null;
            $elem['width'] = $elem['width'] ? $elem['width'] : null;
            $elem['height'] = $elem['height'] ? $elem['height'] : null;
            $elem['visual'] = array();
            $elem['custom'] = true;
            $elem['children'] = array();

            // parse regular expression
            if ($elem['fieldReg'] != ''){
                // it is always parsed
            }

            if ($elem['unique']){
                $elem['unique'] = array();
            } else {
                unset($elem['unique']);
            }

            // add array values for select
            if ($elem['arr_name'] != ''){
                $elem['visual']['source'] = array();
                //$values = explode("\r\n", $arr['arr_name']);
                $values = unserialize($elem['arr_name']);

                foreach ($values['items'] as $y){
                    //$fields[$v['nick']]['visual']['source']['arr_name'][$y] = $y;
                    //$this->_p->custom_fields[$arr['real_id']]['visual']['source']['arr_name'][$x+1] = $y;
                    if ($y['picture']){
                        $y['picture'] = unserialize($y['picture']);
                        //$this->_visual['target_dir'].calc_item_path($params['id'])
                        $y['picture'] = Images::get($p, $y['picture'], $p->getVar('files_dir').'fields/upload/', $elem['real_id']);
                    }
                    $elem['visual']['source']['arr_name'][$y['id']] = array('title' => $y['title'], 'weight' => $y['weight'], 'picture' => $y['picture']);

                }

            }

            // add array values for select
            if ($elem['tbl_name'] != '' && isset($p->getVar('tables')[$elem['tbl_name']])){
                $elem['visual']['source'] = array();
                $elem['visual']['source']['tbl_name'] = $elem['tbl_name'];
                // describe linker table and check for is_top field
                $desc_tbl = describe_table($p, $p->getVar('tables')[$elem['tbl_name']]);

                $fields_add = array();
                $order = $primary = null;
                if ($desc_tbl && !empty($desc_tbl)){

                    foreach ($desc_tbl as $field){
                        $fields_add[] = $field;
                        if ($field == 'real_id'){
                            $elem['visual']['source']['multilang'] = true;
                        }
                        if ($field == 'title'){
                            $order = $field;
                        }
                    }
                }

                // check if order is absent
                if (!$order){
                    if (in_array('sname', $fields_add))
                        $order = 'sname';

                    if (in_array('fname', $fields_add))
                        $order = 'fname';

                    if (in_array('mname', $fields_add))
                        $order = 'mname';
                }

                // hack :)
                $elem['visual']['source']['conditions']['where'][$order] = array('op' => '<>', 'value' => '');
                $elem['visual']['source']['conditions']['order'][$order] = 'asc';

            }

            if ($elem['visual_type'] != '')
                $elem['visual']['type'] = $elem['visual_type'];
            elseif (!$elem['field_type'])
                $elem['visual']['type'] = 'group';

            // some patching for visual fields
            if ($elem['visual']['type'] == 'range'){
                $elem['visual']['fields'] = array('value2');
            }

            if ($elem['visual_accept'] != '')
                $elem['visual']['accept'] = explode(',', str_replace(' ', '', $elem['visual_accept']));

            if ($elem['title'] != '')
                $elem['visual']['title'] = $elem['title'];

        }));

		if ($this->_p->getVar('is_debug')){
			$this->_p->debug->add("oForms: After get serialized custom fields");
		}

        return $this;

	}


    /**
     * method gets custom fields for current module and connect them with special flag with order
     * @param array $params array of parameters, like 'model' -  model name, 'link_id' - ID for linking
     * @return array
     */
    public function getCustomFields($params = array()){

		$fields = array();

        //echo $this->_p->getVar('tbl_adm_pages')."_".(int)$params['link_id']."_".$this->_p->getVar('tbl_fields')."<br>";
		if (!(int)$params['link_id'] || !$params['model'])
			return $fields;

		// connect linker
		if (($data = $this->_p->cache->getSerial($params['model']."_".(int)$params['link_id']."_".$this->_p->getVar('tbl_fields'))) === false){

			$oLinker = $this->_p->Module($this->_p->getVar('linker')['service'])->AddParams(array(
                'model'		=> $params['model'],
                'this_tbl_name' => $this->_p->getVar('tbl_fields'),
                'elem_id'		=>	(int)$params['link_id']
            ));
			$data = $oLinker->getData();
			unset($oLinker);

			// protect from requery
			if (!$data)
				$data = array();

            // protect from saving without id
            if ($params['link_id'])
			    $this->_p->cache->saveSerial($params['model']."_".(int)$params['link_id']."_".$this->_p->getVar('tbl_fields'), $data ? $data : array());
		}

		if (isset($data['fields'][$params['link_id']]) && !empty($data['fields'][$params['link_id']])){

			$this->initCustomFieldsTree();

			// getting all children for each level
			if (!function_exists('get_children_fields')){

				function get_children_fields($custom_fields, $owner_id, &$children){

					if (!empty($custom_fields[$owner_id]['children'])){

						$key = array_search($owner_id, $children);
						$children = array_merge(array_slice($children, 0, $key+1), $custom_fields[$owner_id]['children'], array_slice($children, $key+1));
						//print_r($children);
						foreach ($custom_fields[$owner_id]['children'] as $v){
							get_children_fields($custom_fields, $v, $children);
						}
					}
					//return $children;
				}

			}

			// collect all fields on all levels
			// restoring right order of the fields
            $tmp_copy = $this->_p->getVar('custom_fields')->getArrayCopy();
			$all_children = array_keys(array_intersect_key($tmp_copy, $data['fields'][$params['link_id']]));
			foreach ($data['fields'][$params['link_id']] as $k => $v)
				get_children_fields($tmp_copy, $k, $all_children);
			// now all children are collected
			$fields = array();
			foreach ($all_children as $v){
				$fields[$tmp_copy[$v]['nick']] = $tmp_copy[$v];
			}

            unset($tmp_copy);

		}
		unset($data);

		return $fields;
	}


    /**
     * method checks custom field
     * @param array $params
     * @return bool
     */
    public function checkCustomField($params = array()){

        $conditions = array(
            'no_array_key' => true,
            'limit' => 1,
            'where' => array('model' => $params['model'], 'field_nick' => $params['field'], 'value' => $params['value'])
        );

		if (isset($params['exclude'])){

			if (is_array($params['exclude']))
                $conditions['where']['real_id'] = array('op' => 'not in', 'value' => $params['exclude']);
			else
                $conditions['where']['real_id'] = array('op' => '<>', 'value' => $params['exclude']);

		}

		/**
		* TODO: fix this issue for linked values
		*/
		if (isset($params['params']) && isset($params['params']['owner_id'])){
			foreach ($params['params'] as $k => $v){
                $conditions['where'][$k] = $v;
			}
		}

        $sql = $this->_p->db->getQueryBuilder()->compile($this->_p->Model('FieldValue'), $conditions);

		$res = $this->_p->db->selectRow($sql);

		return $res ? true : false;

	}


	/**
	* method return custom values
	*/
	public function getCustomValues($params = array()){

		// getting values for this fields from table
		$arr = array();
		if (isset($params['elem_id']) && $params['elem_id']){


            $is_array = is_array($params['elem_id']);
            if (!$is_array){
                $params['elem_id'] = array($params['elem_id']);
            }

            $sql = $this->_p->db->getQueryBuilder()->compile($this->_p->Model('FieldValue'), array(
                'no_array_key' => true,
                'where' => array('model' => $params['model'], 'real_id' => array('op' => 'in', 'value' => $params['elem_id'])),
                'order' => array('weight' => 'asc')
            ));

			$result = $this->_p->db->select($sql);

			if ($result && !empty($result)){

				// converting output rows to standart format
				$rows = array();
                $extended_descr = array();
				foreach ($result as $arr){

                    // prepare multilanguage for items
                    if (!isset($extended_descr[$arr['real_id']]))
                        $extended_descr[$arr['real_id']] = array();

                    if (!isset($extended_descr[$arr['real_id']][$arr['field_nick']]))
                        $extended_descr[$arr['real_id']][$arr['field_nick']] = array();

                    $extended_descr[$arr['real_id']][$arr['field_nick']][$arr['lang_id']] = $arr['extended_descr'];

					$rows[$arr['real_id']][$arr['lang_id']][$arr['field_nick']] = $arr['value2'] !== '' ? array($arr['value'], $arr['value2']) : $arr['value'];
                    $rows[$arr['real_id']][$arr['lang_id']]['lang_id'] = $arr['lang_id'];

                    // create custom base structure - so we don't need to refactoring whole framework
                    if (!isset($rows[$arr['real_id']][$arr['lang_id']]['_custom_base_']))
                        $rows[$arr['real_id']][$arr['lang_id']]['_custom_base_'] = array();

                    // saving field nick for restoring after multilang operations
                    $rows[$arr['real_id']][$arr['lang_id']]['_custom_base_'][$arr['field_nick']] = $arr;

				}

                // update extended description field
                foreach ($extended_descr as $k => $v){
                    foreach ($rows[$k] as $lang_id => $values){
                        foreach ($values['_custom_base_'] as $fnick => $vvalues){
                            $rows[$k][$lang_id]['_custom_base_'][$fnick]['extended_descr'] = $v[$fnick];
                        }
                    }

                }

				// for array keys - return all values
				if ($is_array)
					return $rows;

                reset($rows);

				$rows = array_values(current($rows));

				// getting all rows
				if ($this->_multilang){
					//echo "<pre>", print_r($rows);//die();

					//get array fields for languages
					$arr_fields_lang = get_arr_fields_lang($rows, $params['multi_fields'], $this->_p->getLangTbl());

					//print_r($arr_fields_lang);
					if (!is_array($arr_fields_lang))
						$arr_fields_lang = array();

					$arr = $rows[0];

					// join multifields and simple fields
					$arr = array_merge($arr, $arr_fields_lang);

					//print_r($arr);

				} else {

					$arr = $rows[0];

				}

			}
			unset($result);

		}

		return $arr;

	}

    /**
     * method decode html natural values from its coded items. Work only for normal values
     */
    public function getValuesHTML($values = array()){

        $vals = array();
        $old_data = $this->_data;
        $this->_data = $values;

        foreach ($this->_fields as $k => $v){

            if (!$v['custom'] && isset($values[$k]) && $values[$k] !== ''){
                // create field object
                $f = $this->getField($k);
                $vals[$k] = $f['value_html'];
                $vals[$k.'_value'] = $f['value'];
            }

        }
        $this->_data = $old_data;

        return $vals;

    }


    /**
     * method decode html natural values from its coded items. Work only for custom values
     */
    public function getCustomValuesHTML($values = array()){

        $vals = array('');
        $old_data = $this->_data;
        $this->_data = $values;

        foreach ($this->_fields as $k => $v){

            if ($v['custom'] && isset($values[$k]) && $values[$k] !== ''){
                // create field object
                $f = $this->getField($k);
                $vals[$k] = $f['value_html'];
                $vals[$k.'_value'] = $f['value'];
            }

        }
        $this->_data = $old_data;

        return $vals;

    }


    /**
     * method saving custom fields for selected language (if multilang)
     * you MUST prepare all data for saving (such as use qstr)
     * @param array $params
     *         array $params[model] model for linking
     *         array $params[fields] array of the nicks of fields
     *         array $params[values] array of the field->value pairs
     *         int $params[real_id] identifier of the element for current data
     *         int $params[link_id] identifier of the linked elemement
     *         int $params[lang_id] current language identifier
     * @return $this
     */
    public function saveCustomValues($params = array()){

		if (isset($params['model'])){

            $datas = array();

			foreach ($params['values'] as $field => $v){
				// protect from saving group value
				// adding hint! if no value, or zero - then not saving!
				if ($this->_fields[$field]['type'] != 'list' && $v != '' && $v != '0'){

                    $data = array();
                    // checking for array in values
                    if (is_array($v)){
                        $max = 2;
                        $v = array_slice(array_values($v), 0, $max);

                        for ($e = 1; $e <= $max; $e++){
                            $data['value'.($e > 1 ? $e : '')] = $v[$e];
                        }

                    } else {
                        $data['value'] = $v;
                    }

                    //dump($values, false);
                    $data['model'] = isset($params['model']) ? (is_string($params['model']) ? $params['model'] : $params['model']->getModelName()) : '';
                    $data['field_nick'] = $field;
                    $data['elem_id'] = $params['link_id'];
                    $data['real_id'] = $params['real_id'];
                    $data['lang_id'] = $params['lang_id'];
                    $data['is_filter'] = $this->_fields[$field]['is_filter'];
                    $data['weight'] = isset($params['weights']) && $params['weights'][$field] ? (int)$params['weights'][$field] : '';
                    $data['category'] = $params['static_values']['category'];
                    $data['field_type'] = $this->_fields[$field]['type'];
                    $data['is_on'] = $params['static_values']['is_on'];

                    $datas[] = $data;
                }

			}

            $this->_p->db->transaction();
            $this->deleteCustomValues($params);

            if (!empty($datas)){

                $sql = $this->_p->db->getQueryBuilder()->compileInsert($this->_p->Model('FieldValue'), $datas, true);
			    $this->_p->db->query($sql);
            }
            $this->_p->db->commit();

            unset($datas);
            unset($data);
		}

        return $this;
	}


    /**
     * method delete custom fields for selected language (if multilang)
     * you MUST prepare all data for saving (such as use qstr)
     * @param array $params consists of 'real_id' and 'model'
     * @return int
     */
    public function deleteCustomValues($params = array()){

		// delete fields
        $conditions = array('where' => array('model' => isset($params['model']) ? ($params['model'] instanceof oModel ? $params['model']->getModelName() : $params['model']) : '', 'real_id' => $params['real_id']));
        if (isset($params['lang_id']) && $params['lang_id']){
            $conditions['where']['lang_id'] = $params['lang_id'];
        }

        $sql = $this->_p->db->getQueryBuilder()->compileDelete(
            $this->_p->Model('FieldValue'),
            $conditions
        );

		return (int)$this->_p->db->query($sql);

	}


	/**
	* function prepare and get cache filename
	*/
	protected function _getCacheFilename($field, $params = array()){

		$cachename = '';
		if (isset($this->_fields[$field]['visual']['source']['cache']['filename'])){
			$cachename = $this->_fields[$field]['visual']['source']['cache']['filename'];
		} else {
			if (isset($this->_fields[$field]['visual']['source']['arr_name'])){
				$cachename = $field.$params['field_name_add'].'_fields';
            } elseif (isset($this->_fields[$field]['visual']['source']['model'])){
                $cachename = $this->_p->getVar($this->_fields[$field]['visual']['source']['model']).$params['field_name_add'].'_fields';
			} elseif (isset($this->_fields[$field]['visual']['source']['tbl_name'])){
				$cachename = $this->_p->getVar($this->_fields[$field]['visual']['source']['tbl_name']).$params['field_name_add'].'_fields';
			}
		}
		if ($this->_fields[$field]['visual']['source']['multilang']){
			$cachename .= '.'.$this->_p->getLangNick();
		}

		return $cachename;

	}


    /**
     * get field's instance
     * @param $field
     * @param array $params
     * @return null|\webtFramework\Components\Form\oField
     * @throws \Exception
     */
    public function getFieldObject($field, $params = array()){
        // check field object in cache

        // serialize field parameters
        $tmp_field = $this->_fields[$field];
        if (isset($tmp_field['visual']) &&
            isset($tmp_field['visual']['source']) &&
            isset($tmp_field['visual']['source']['arr_name'])){
            unset($tmp_field['visual']['source']['arr_name']);
        }

        if (isset($tmp_field['children'])){
            unset($tmp_field['children']);
        }

        if (!isset($params['base_field_id'])){
            $params['base_field_id'] = $field;
        }

        // update default field type
        if (!isset($this->_fields[$field]['type']))
            $this->_fields[$field]['type'] = 'varchar';
        elseif (!isset($this->_aliases[$this->_fields[$field]['type']])){
            // try to find aliases for base field types
            foreach ($this->_aliases as $k => $v){
                if (in_array($this->_fields[$field]['type'], $v)){
                    $this->_fields[$field]['type'] = $k;
                    break;
                }
            }
        }

        $f = $field.serialize($tmp_field).$params['field_name_add'].$params['owner_id'].$params['base'];
        unset($tmp_field);
        //dump(array($field => $f), false);
        ///dump(array($field, $f, !$this->_fields_cache[$f], 'oField'.ucfirst($this->_fields[$field]['visual']['type']), class_exists('oField'.ucfirst($this->_fields[$field]['visual']['type']))), false);

        if (strpos($this->_fields[$field]['visual']['type'], ':') !== false){

            $type = explode(':', $this->_fields[$field]['visual']['type']);
            $bundle = $type[0];
            $type = ucfirst($type[1]);

        } else {
            $bundle = 'webtFramework';
            $type = ucfirst($this->_fields[$field]['visual']['type']);
        }

        if (!$this->_fields_cache[$f] &&
            $this->_fields[$field]['visual'] &&
            $this->_fields[$field]['visual']['type'] &&
            class_exists('\\'.$bundle.'\Components\Form\Fields\oField'.$type)){

            $class = '\\'.$bundle.'\Components\Form\Fields\oField'.$type;
            $this->_fields_cache[$f] = new $class($this->_p, $this, array_merge($this->_fields[$field], (array)$params));

        } elseif (!$this->_fields_cache[$f] &&
            $this->_fields[$field]['type'] &&
            class_exists('\\'.$bundle.'\Components\Form\Fields\oField'.ucfirst($this->_fields[$field]['type']))){

            $class = '\\'.$bundle.'\Components\Form\Fields\oField'.ucfirst($this->_fields[$field]['type']);
            $this->_fields_cache[$f] = new $class($this->_p, $this, array_merge($this->_fields[$field], (array)$params));

        } elseif (!$this->_fields_cache[$f]) {
            $this->_fields_cache[$f] = null;
        }

        if (!$this->_fields_cache[$f]){
            throw new \Exception($this->_p->trans('errors.forms.field_not_found').': '.$field);
        }

        return $this->_fields_cache[$f];

    }

    /**
     * method extract value from specified field
     * @param $field
     * @return null
     * @throws \Exception
     */
    public function getValue($field){

        if (!isset($this->_fields[$field]))
            throw new \Exception('errors.fields.no_field_found');

        $ofield = $this->getFieldObject($field, array(
            'owner_id' => $this->_fields[$field]['owner_id'],
        ));

        return $ofield->getValue($this->_data[$field]);


    }


    /**
     * generate field's array with html, value, errors
     * @param $field
     * @param null|int [$id]
     * @param string [$field_name_add]
     * @param array [$params]
     * @return array of array('value' => mixed, 'value_html' => string, 'html' => string, 'vars' => array())
     */
    public function getField($field, $id = null, $field_name_add = '', $params = array()){

        $error_html = $visual = $value_html = $width = $height = $style = '';

        // initialize other vars for the
        $vars = array();

		// check for field size
		if (isset($this->_fields[$field]['width']))
			$width = 'width: '.$this->_fields[$field]['width'].'px;';
		if (isset($this->_fields[$field]['height']))
			$height = 'height: '.$this->_fields[$field]['height'].'px;';

		$style = $width || $height ? $width.$height : '';

		if (!empty($this->_fields[$field]['style']))
			$style .= $this->_fields[$field]['style'];

		// check for multistore field
		$visuals = $values_html = $values = array();

		if ($this->_fields[$field]['multistore']){

			$fields_cnt = $this->_fields[$field]['multistore'];
            $use_def_value = empty($this->_data) && !isset($this->_data[$field]) && isset($$this->_fields['default_value']);
			$datas = $use_def_value ? : (is_array($this->_data[$field]) ? $this->_data[$field] : explode($this->_p->getVar('forms')['fields_multistore_sep'], $this->_data[$field]));

		} else {

			$fields_cnt = 1;
			$datas = is_array($this->_data) ? array($this->_data[$field]) : $this->_data;

		}

		// check for readonly
		if (isset($this->_fields[$field]['readonly']) && $this->_fields[$field]['readonly']/* && $this->_data[$this->_caller->primary]*/)
			$readonly = 'disabled="disabled"';
		else
			$readonly = '';


        // determine class
        $class = !empty($this->_p->getVar('forms')['default_classes']) ? $this->_p->getVar('forms')['default_classes'] : array('b-input', 'form-control');

        if (is_array($this->_non_valid_fields) && in_array($field, $this->_non_valid_fields)){
            $class[] = 'b-input-bad';
            // setting non valid field flag
            $field_non_valid = true;
        } else {
            $field_non_valid = false;
        }

        if ($this->_fields[$field]['class']){
            if (is_array($this->_fields[$field]['class'])){
                $class = array_keys(array_flip(array_merge($this->_fields[$field]['class'], $class)));
            } else
                $class[] = $this->_fields[$field]['class'];
        }

        // try to find visibility field
        if (!isset($this->_temp['found_visibility']) && is_array($this->_data)){

            $vf = null;
            foreach ($this->_fields as $k => $v){
                if ($v['type'] == 'visibility'){
                    $vf = $k;
                    break;
                }
            }
            if ($vf && $this->_data[$vf]){
                $vfo = $this->getFieldObject($vf, array('owner_id' => $this->_fields[$vf]['owner_id']));
                $this->_temp['visibility'] = $vfo->getValue($this->_data[$vf]);
                unset($vfo);
            } else {
                $this->_temp['visibility'] = array();
            }

            $this->_temp['found_visibility'] = true;

        }

        $field_id = $field.$this->_fields_postfix;

        $ofield = $this->getFieldObject($field, array(
            'base_field_id' => $field,
            'field_id' => $field_id,
            'owner_id' => $this->_fields[$field]['owner_id'],
            'class' => $class,
            'field_name_add' => $field_name_add
        ));

        // foreach store value prepare field
		for ($f_num = 0; $f_num < $fields_cnt; $f_num++){

			$visual = $value_html = $custom_value = '';

			//dump_file(array('Get field:', $field, $this->_non_valid_fields));
			if ($this->_fields[$field]['multistore'])
				$name_add = $field_name_add.'['.$f_num.']';
            else
                $name_add = $field_name_add;

            $class[] = 'field-'.$this->_normalizeAttr($field_id.$name_add);
            $class_compiled = $this->compileAttr('class', $class);
			// get current value
			$value = $datas[$f_num];


            $f = $ofield->get($value, array_merge((array)$params, array(
                'name_add' => $name_add,
                'id' => $id,
                'class_compiled' => $class_compiled,
                'style' => $style,
                'readonly' => $readonly,
                'non_valid_details' => $this->_non_valid_details[$field] ? $this->_non_valid_details[$field] : null
            )));

            extract($f, EXTR_OVERWRITE);
            $visual = &$html;


            // make weight block for custom fields
            if ($this->_fields[$field]['custom'] &&
                $this->_fields[$field]['visual'] &&
                $this->_fields[$field]['visual']['type'] != 'group' &&
                $this->_p->getVar('forms')['is_show_custom_fields_weight']){

                // create virtual weight field
                $weight_field = array(
                    $field => array(
                        'type' => 'integer',
                        'maxlength' => 5,
                        'default_value' => 0,
                        'helptext' => $this->_p->trans('fields.weight_custom_field_helptext'),
                        'visual' => array(
                            'type' => 'weight')
                        )
                );

                // get new oForms instance
                $newfc = $this->_p->Service('oForms')->AddParams(array(
                    'base' => 'weight',
                    'fields' => $weight_field,
                    'data' => $this->_p->query->request->getPost()['weight'] ? $this->_p->query->request->getPost()['weight'] : ($params['weight'] ? array($field => $params['weight']): array()),
                    'multilang' => false,
                    'fields_postfix' => '_weight'
                ));
                $wf = $newfc->getField($field, $id, $field_name_add);

                $visual = '<span class="b-field-data-values">'.$visual.'</span><span id="weight_'.$field.$name_add.'" class="b-field-weight">'.$wf['html'].'</span><del class="clr"></del>';

            }

            // make outer block for field
            $non_valid_info = array();
            if ($field_non_valid && $this->_non_valid_details[$field]){
                foreach ($this->_non_valid_details[$field] as $k => $v){
                    if (!$v){
                        $non_valid_info[] = $this->_p->trans('errors.detailed.'.$k);
                    }
                }
            }

            $error_html = ($field_non_valid && !empty($non_valid_info)? '<span class="b-input-error">'.join(', ', $non_valid_info).'</span>' : '');
            $visual = '<span class="b-field b-field-'.$this->_fields[$field]['type'].' '.($this->_fields[$field]['visual'] ? 'b-field-'.$this->_fields[$field]['visual']['type'] : '').' b-field-'.$field.$name_add.' '.($field_non_valid ? 'b-field-non-valid' : '').'">'.$visual.$error_html.((isset($this->_fields[$field]['empty']) && !$this->_fields[$field]['empty']) || (isset($v['fullempty']) && !$v['fullempty']) ? '<span class="b-field-non-empty"></span>' : '').'</span>';

            // check for visibility property
            if (isset($this->_fields[$field]['can_visible'])){

                $vvalue = $this->_fields[$field]['can_visible']['read_only'] ? $this->_fields[$field]['can_visible']['status'] : (isset($this->_temp['visibility'][$field.$name_add]) ? $this->_temp['visibility'][$field.$name_add] : $this->_fields[$field]['can_visible']['status']);
                $visual = '<span title="'.htmlspecialchars($this->_p->trans('fields.field_visibility'), ENT_QUOTES).'" id="'.$field.$name_add.'-vis-switcher" data-acceptor="'.htmlspecialchars($field.$name_add, ENT_QUOTES).'" class="b-field-visible'.(!$vvalue ? ' b-field-visible-none' : '').($this->_fields[$field]['can_visible']['read_only'] ? ' b-field-visible-readonly' : '').'"></span><input type="hidden" id="'.htmlspecialchars($field.$name_add, ENT_QUOTES).'-status" name="'.$this->_base.'[visibility]['.$field.']'.$name_add.'" value="'.(int)$vvalue.'"/>'.$visual;
                $vars['visibility'] = $vvalue;

            }

			$visuals[] = $visual;
			$values[] = $value;
			$values_html[] = $value_html;

		}

        unset($value);
        unset($visual);
        unset($value_html);

		return array('html' => join('', $visuals),
				'value' => $this->_fields[$field]['multistore'] ? $values : $values[0],
				'value_html' => $this->_fields[$field]['multistore'] ? join($this->_p->getVar('forms')['fields_multistore_sep'], $values_html) : $values_html[0],
                'error_html' => $error_html,
                'vars' => $vars
        );

	}


    /**
     * Method get table source data
     * @param string $field field name
     * @param array $tbl_source array of table source
     * @param array $params
     * @return array|bool|mixed|null|void
     */
    public function getTblSource($field, $tbl_source, $params = array()){

        $sql_add = $order_cond = $table_data = array();
        $conditions = array('where' => array(), 'order' => array());
        $model = null;

        $qb = $this->_p->db->getQueryBuilder();
        if (!(isset($tbl_source['model']) && ($model = $this->_p->Model($tbl_source['model'])))){
            $model = $qb->createModel($tbl_source['tbl_name']);
        }

        if ($model){
            if ($model->getIsMultilang())
                $conditions['where']['lang_id'] = $this->_p->getLangId();
            $primary = $model->getPrimaryKey();

        } else {
            // for backward compatibility
            if ($tbl_source['multilang']){
                $sql_add[] = 'lang_id='.(int)$this->_p->getLangId();
                $primary = 'real_id';
            } else {
                $primary = 'id';
            }
        }

        // add right conditions
        if (isset($tbl_source['conditions']) && is_array($tbl_source['conditions'])){

            if (isset($tbl_source['conditions']['order'])){
                foreach ($tbl_source['conditions']['order'] as $f => $v){

                    if ($model)
                        $conditions['order'][$f] = $v;
                    else
                        $order_cond[] = '`'.qstr($f).'` '.qstr($v).'';
                }
            }

            if (!$model)
                $sql_add = array_merge($sql_add, compile_where_array($tbl_source['conditions']['where'], $order_cond));
            elseif (isset($tbl_source['conditions']['where']))
                $conditions['where'] = array_merge_recursive_distinct($conditions['where'], $tbl_source['conditions']['where'], 'combine');

        }

        // describing table
        if ($model)
            $desc_tbl = $qb->describeTable($model);
        else
            $desc_tbl = describe_table($this->_p, $this->_p->getVar($tbl_source['tbl_name']));

        // getting sorting order
        if (isset($tbl_source['order'])){

            if ($model)
                $conditions['order'] = array_merge($conditions['order'], $tbl_source['order']);
            else
                $order_cond[] = compile_order_array($tbl_source['order']);
        }

        if ($model){

            if (!empty($desc_tbl)){

                if ($qb->isFieldExists($model, 'weight'))
                    $conditions['order']['weight'] = 'desc';
                if ($qb->isFieldExists($model, 'sname')){
                    $conditions['order']['sname'] = 'asc';
                    if ($qb->isFieldExists($model, 'fname')){
                        $conditions['order']['fname'] = 'asc';
                    }
                } elseif ($qb->isFieldExists($model, 'title')){
                    $conditions['order']['title'] = 'asc';
                }
            }

        } else {

            if (!empty($desc_tbl)){

                if (in_array('weight', $desc_tbl))
                    $order_cond[] = 'a.weight DESC';
                if (in_array('sname', $desc_tbl)){
                    $order_cond[] = 'a.sname ASC';
                    if (in_array('fname', $desc_tbl)){
                        $order_cond[] = 'a.fname ASC';
                    }
                } elseif (in_array('title', $desc_tbl)){
                    $order_cond[] = 'a.title ASC';
                }
            }
        }


        // if we have tree like style of the select
        if ($tbl_source['subtype'] == 'tree'){

            // getting possible owners
            if (!(isset($tbl_source['cache']) &&
                ($table_data = $this->_p->cache->getSerial($this->_getCacheFilename($field, $params))))){

                if ($model){
                    $sql = $qb->compile($model, $conditions);
                } else {
                    $sql = "SELECT a.".$primary." AS ARRAY_KEY,a.* FROM ".$this->_p->getVar($tbl_source['tbl_name'])." a
                                        WHERE 1=1 ".(!empty($sql_add) ? ' AND '.join(' AND ', $sql_add) : '')."
                                        ORDER BY ".(!empty($order_cond) ? join(',', $order_cond) : 'NULL');
                }

                $table_data = $this->_p->db->select($sql);

                if (isset($tbl_source['cache'])){
                    $this->_p->cache->saveSerial($this->_getCacheFilename($field, $params), $table_data);
                }

            }

        } else {

            if (!(isset($tbl_source['cache']) &&
                ($table_data = $this->_p->cache->getSerial($this->_getCacheFilename($field, $params))))){

                // get allowed fields for optimized query
                $allowed = array('id', 'real_id', 'title', 'email', 'fname', 'sname', 'usernick', 'altname');
                // check for specific row title
                if (isset($tbl_source['row_title'])){
                    $allowed[] = $tbl_source['row_title'];
                }
                $get_fields = array();

                if ($model){

                    $conditions['select'] = array('a' => array());
                    foreach ($desc_tbl as $v){
                        if (in_array($v, $allowed))
                            $conditions['select']['a'][] = $v;
                    }

                    $sql = $qb->compile($model, $conditions);

                    $table_data = $this->_p->db->select($sql);

                } elseif (is_array($desc_tbl)){

                    foreach ($desc_tbl as $v){
                        if (in_array($v, $allowed))
                            $get_fields[] = qstr($v);
                    }

                    $sql = 'SELECT '.join(',', $get_fields).' FROM '.qstr($this->_p->getVar($tbl_source['tbl_name'])).' a
                                            WHERE 1=1 '.(!empty($sql_add) ? ' AND '.join(' AND ', $sql_add) : '').'
                                            ORDER BY '.(!empty($order_cond) ? join(',', $order_cond) : 'NULL');

                    $table_data = $this->_p->db->select($sql);
                }

                if (isset($this->_fields[$field]['visual']['source']['cache'])){
                    $this->_p->cache->saveSerial($this->_getCacheFilename($field, $params), $table_data);
                }

            }
        }

        unset($model);
        unset($qb);
        unset($conditions);

        return $table_data;

    }


    public function getModel(){
        return $this->_model;
    }

    public function getData(){
        return $this->_data;
    }

    public function getFields(){
        return $this->_fields;
    }

    public function getPrimaryKey(){
        return $this->_primary;
    }

    public function getMultilang(){
        return $this->_multilang;
    }

    public function getUploadDir(){

        return $this->_upload_dir;

    }

    public function getCaller(){
        return $this->_caller;
    }

    public function getCallbacks(){
        return $this->_callbacks;
    }

    public function setDataValue($field, $value){

        if ($value === null){
            unset($this->_data[$field]);
        } else {
            $this->_data[$field] = $value;
        }

        return $this;

    }

    /**
     * Method compile attribute for a DOM element
     * @param $attr string attribute name
     * @param $value mixed
     * @return string compiled string
     */
    public function compileAttr($attr, $value){

        return $attr.'="'.join(' ', (array)$value).'"';
    }


    /**
     * method normalize attributes
     * @param $attr
     * @return mixed
     */
    protected function _normalizeAttr($attr){
        return str_replace(array('[', ']'), '__', $attr);
    }

    /**
     * return all properties
     * @param $name
     * @return mixed
     */
    public function __get($name){
        if (isset($this->$name))
            return $this->$name;
        else
            return null;
    }


}


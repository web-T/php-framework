<?php
/**
 * web-T::CMS field type classes
 *
 * Date: 18.12.12
 * Time: 08:40
 * @version 1.0
 * @author goshi
 * @package web-T[Forms]
 *
 * Changelog:
 *    1.0    18.12.2012/goshi
 */

namespace webtFramework\Components\Form;

use webtFramework\Core\oPortal;
use webtFramework\Interfaces\oBase;
use webtFramework\Services\oForms;
use webtFramework\Helpers\Text;

/**
 * Class determine simply field type object
 * @package web-T[share]
 */
abstract class oField extends oBase{

    /**
     * base structure for all fields in forms, you can change it in the oForms.inc.php
     * @var null
     */
    protected $_base = null;

    /**
     * @var string main field type. can be one of the integer (or int), float, datetime, date, time, unixtimestamp, boolean,
     * varchar, text, virtual (this type of the field cosnsist of subs by fields, declared in 'handlerNodes' property),
     * set, multi (for multifields)
     */
    protected $_type = 'varchar';

    /**
     * name or array of handler elements, using for 'virtual' field
     * @var string|null
     */
    protected $_handlerNodes = null;

    /**
     * external handlers for fields data
     * @var null
     */
    protected $_handlerExternal = null;

    /**
     * visual presentation of the field
     * array(
     *				'type' => 'select|multiselect|radio|tree|picture|translit|file|multicheckbox|weight|hidden|password', | def: by type - visual representation of the field, you can add your own field types by set Bundle:field
     *              'attr_type' => null,| customizes input attribute type
     *				'title' => 'field_name', | define field name for title (for picture only),
     *				'accept' => array('doc', 'xls', etc..), | def: '' - for file type allow filetypes (WARNING! files checking by extension,not by signature!!!)
     *				'file_save_linked' =>  true/false | def : false - save file linked in Uploads, if false - saving with additional settings
     *				'file_max_size' => float | def: '' - you can override server maximum file size in bytes,
     *				'file_help_hide' => true/false | def: false  - hide help text for file field,
     *				'file_download_hide' => true/false | def: false - hide download link (if you want to override it),
     *              'settings' => array, | def: null - various properties for current visual type (see concrete field controller)
     *				'source' => array(
     *					'empty' => 'true|false' | def: true
     *					'subtype' => 'list|tree', | def: list - type for select
     *					'tbl_name' => 'name of table source (for multifields it is additional foreign storage for multivalues)',
     *                  'foreign_key' => 'used only on multivalues. special foreing key for detecting each multivalue'
     *					'search' => 'true|false', | def: false	- search in linked data
     *					'arr_name' => 'name of array with values', | use like '$arr = ['visual']['source']['array']; foreach ( $$arr as $k => $v),
     *					'module' => 'name of module for getting data for tree'
     *					'conditions' => array of array('key' => 'field_name', 'value' => 'field_value', 'op' => '=|<|>|<>|NOT IN|etc.'), | def: '='
     *					'field' => 'FIELD_NICK_OF_SOURCE_DATA',
     *					'multilang' => true/false | def: false,
     *					'order' => '', | field for order, de: 'title',
     * 					'attributes' => list|null, | def: null - array of additional attributes for showing in each tag like attrib=value from source
     *                  'empty_helptext' => string, used for select source type and determine empty text for the first position
     *                  'row_title' => string, row title showed over tbl_data items
     *                  'is_clearly_add_no' => true/false | add to the select/multiselect explicity 'no' value
     *				),
     *              'min_values' => integer, used with 'multi' visual type for determine minimum rows of values
     *              'max_values' => integer, used with 'multi' visual type
     *              'is_custom_value' => boolean, used for select,radio fields for adding custom value
     *              'is_custom_title' => [string], used with 'is_custom' property for text in the custom field
     *              'no_title'  => boolean, used in checkbox like field - if set to true title of the field not showing
     *              'no_weight' => boolean, user with select/checkbox tree for not showing weights,
     *              'handler'   => string|array, name of method or array(controller => method) for handle and parse value (see concrete controller)
     *              'fields'    => array, additional fields nicks for saving/restore values
     *              'show_weights' => boolean, show weights in the select lists
     *
     *              images declaration:
     *              'img_dir' => string, path to images
     *              'picture_props' =>
     *              '
     * structure:
     * 	array(
     *	'ratio' => float,
     *	'size' => array('width' => integer, 'height' => integer)
     *	'can_resized' => true/false, | default = true
     *	'no_limits' => true/false, | default = true
     *	'watermark' => true/false, | default = true
     *	'src' => '',
     *	'secondary' => id|false, | default: false - owner id for the secondary image, you can define some primary and some secondary images for the module
     *	'crop' => true/false,
     *	'title' => '', | title for image,
     *	'quality' => [int], | default: '', quality from 0 to 100
     * )
     *
     * @var array
     */
    protected $_visual = null;


    /**
     * structures for define filters, which are used on get/save operations
     * filters list you can look at @see Text
     * Filters always depends on datatype of the field (for html field no html cleanup needed on input values)
     * @var array
     */
    protected $_filters = array(
        'save' => array(),
        'get' => array(),
    );

    /**
     * determine, if field can be 'NUll' in database, so core optimize it for NULL saving
     * @var bool
     */
    protected $_null = false;


    /**
     * @var string title of the field
     */
    protected $_title = '';

    /**
     * multilang property of the field
     * @var bool
     */
    protected $_multilang = false;

    /**
     * @var bool define to number of multifields, if you want to store multiple values in one field (see common.php for define separator (fields_multistore_sep)). Multistore fields can't use filters
     */
    protected $_multistore = false; // multistore property

    /**
     * @var int maximum length of the field in multibyte chars
     */
    protected $_maxlength = 0;

    /**
     * @var int minimum length of the field in multibyte chars
     */
    protected $_minlength = 0;


    /**
     * empty property of the field. one of the languages of field must be non empty
     * @var bool
     */
    protected $_empty = true;

    /**
     * fullfield empty property. all languages of field must be non empty
     * @var bool
     */
    protected $_fullempty = true;

    /**
     * property for readonly field - user can view it, but cannot edit
     * @var bool
     */
    protected $_readonly = false;

    /**
     * is this field primary
     * @var bool
     */
    protected $_primary = false;

    /**
     * special flag that determine, that field is a custom user field
     * @var bool
     */
    protected $_custom = false;

    /**
     * value for weight in custom fields order (for autogenerator)
     * @var int
     */
    protected $_weight = 0;

    /**
     * set to true, if field must be hidden
     * @var bool
     */
    protected $_hidden = false;


    /**
     * determine field datatype. Can be 'plain' or 'html'
     * @var string
     */
    protected $_datatype = 'plain';

    /**
     * set to true, if field must constists with WYSIWYG editor
     * @var bool
     */
    protected $_fulleditor = false;


    /**
     * only for HTML fields, you can define for TinyMCE rules for cleanup any markup data
     * @var string
     */
    protected $_extended_valid_elements = '';


    /**
     * regular expression to check the field
     * @var string
     */
    protected $_fieldReg = '';

    /**
     * if true - field will be unique in database
     * constists of array of properties:
     * array(
     *				'rest' => 'REST_QUERY FOR DATA', | def : current link
     *				'exclude' => 'FIELD_NAME',
     *				'black_list' => array()	| array of excluded black list of values
     *				'params' => array(
     *					'owner_field' => 'FIELD_NAME' | field name for owner,
     *					)
     * @var bool|array
     */
    protected $_unique = false;


    /**
     * array of rules nicks - for access to this field. example: array('admin')
     * @var null|array
     */
    protected $_rules = null;

    /**
     * if field type - multi, then we need to setup children of the field
     * @var null|array
     */
    protected $_children = null;


    /**
     * field's data - adding by CMS
     * @var string
     */
    protected $_value = '';


    /**
     * minimum field's value
     * @var null|float
     */
    protected $_minvalue = null;

    /**
     * maximum value of the field
     * @var null
     */
    protected $_maxvalue = null;


    /**
     * default value of the field
     * @var null
     */
    protected $_default_value = null;

    /**
     * if we need to increase field's value while duplicate item
     * @var bool
     */
    protected $_duplicate = false;

    /**
     * if field can be search in global search and appear in CMS search
     * @var bool
     */
    protected $_search = false;


    /**
     * show visibility flag after the field
     * you need special field, called 'visibility' in the table
     * @var bool
     */
    protected $_can_visible = false;

    /**
     * sorting visual property of the field. if true - in CMS list of items on the field there will be a sort button
     * @var bool
     */
    protected $_sort = false;

    /**
     * property for default sorting in CMS list
     * @var bool
     */
    protected $_default_sort = false;

    /**
     * default sorting order
     * @var string
     */
    protected $_order = 'asc';


    /**
     * width in percentes of the column in CMS list of items
     * @var boolean|int
     */
    protected $_in_list = false;


    /**
     * width in percentes of the column in CMS ajax lists of items
     * @var bool
     */
    protected $_in_ajx_list = false;


    /**
     * help text, that shows ONLY in text fields
     * @var null|string
     */
    protected $_helptext = null;

    /**
     * define size of the input field in chars
     * @var null|int
     */
    protected $_max_input_size = null;

    /**
     * define height of the field in pixels
     * @var null|int
     */
    protected $_height = null;

    /**
     * define width of the field in pixels
     * @var null|int
     */
    protected $_width = null;

    /**
     * define custom class for field
     * @var string
     */
    protected $_class = 'b-input';

    /**
     * define custom style for field
     * @var string
     */
    protected $_style = null;


    /**
     * define custom align for field, possible values 'left|right|center'
     * @var string
     */
    protected $_align = 'center';

    /**
     * property for determine bulk action for items (in the admin section, situated at the bottom)
     */
    protected $_bulk_action = false;

    /**
     * property cooked value of the field for future use. all cookies setuped only on the client part
     */
    protected $_cooked_value = false;

    /**
     * property flaged field like "export", which get to the export list
     */
    protected $_export = false;

    /**
     * current field error state
     * @var null|array
     */
    protected $_error = null;

    /*********************************** end of properties and start of private fields *******************************/

    /**
     * @var null
     */
    protected $_base_field_id = null;

    protected $_field_id = null;

    protected $_params_backup = null;

    protected $_non_valid_details = null;

    protected $_field_name_add = null;

    /****************************************/
    /**
     * base fields controller
     * @var null|oForms
     */
    protected $_oForms = null;


    public function __construct(oPortal &$p, oForms &$oForms = null, $params = array()){

        $this->_oForms = $oForms;
        $this->_params_backup = $params;

        // initialize base structure for forms
        if ($this->_oForms)
            $this->_base = $this->_oForms->getParam('base');

        parent::__construct($p, $params);

        // initialize base field id
        if (!$this->_base_field_id && $this->_field_id){
            $this->_base_field_id = $this->_field_id;
        }

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

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value){
        // ignore, since Foo::bar is read-only
    }

    /**
     * apply seelcted filters on value
     * @param $value
     * @param array $filters
     * @return mixed
     */
    protected function _applyFilters($value, $filters = array()){

        if (!empty($filters)){
            foreach ($filters as $filter => $filter_data){

                if (is_callable($filter_data)){

                    $value = $filter_data($value);

                } elseif (is_array($filter_data)){

                    $value = call_user_func_array(array('\webtFramework\Helpers\Text', $filter), array_merge(array($value), $filter_data));

                } else {
                    $value = Text::$filter_data($value);
                }

            }
        }

        return $value;

    }

    /**
     * get field html and values
     * @param null $data
     * @param array $params
     * @return array
     */
    public function get($data = null, $params = array()){

        // initialize base structure for forms
        if (!$this->_base)
            $this->_base = $this->_oForms->getParam('base');

        if (is_array($params)){
            if (isset($params['non_valid_details']))
                $this->_non_valid_details = $params['non_valid_details'];

        }

        return array();
    }

    /**
     * method return value from field
     *
     * @param null $data
     * @param array $params
     * @return null
     */
    public function getValue($data = null, $params = array()){

        return $this->_applyFilters($data, $this->_filters['get']);

    }

    /**
     * getter for default value
     * @return null
     */
    public function getDefaultValue(){

        return $this->_default_value;

    }

    /**
     * get save value
     * @param $value
     * @param array $row
     * @param $old_data
     * @param null $lang_id
     * @return mixed
     */
    public function save($value, &$row = array(), &$old_data, $lang_id = null){

        return $this->_applyFilters($value, $this->_filters['save']);

    }

    /**
     * validate field value
     * @param $data
     * @param array $full_row
     * @return array
     */
    public function check(&$data, $full_row = array()){

        return array('valid' => true);

    }

}


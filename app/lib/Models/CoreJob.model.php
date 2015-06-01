<?php
/**
 * Core Job model
 *
 * Date: 04.12.14
 * Time: 09:42
 * @version 1.0
 * @author goshi
 * @package web-T[models]
 * 
 * Changelog:
 *	1.0	04.12.2014/goshi 
 */

namespace webtFramework\Models;

use webtFramework\Interfaces\oModel;
use webtFramework\Core\oPortal;

class CoreJob extends oModel{

    protected $_fields = array(
        'id' => array(
            'type' => 'integer',
            'primary' => true,
            'sort' => true,
            'in_list' => 5,
            'order' => 'desc',
            //'search' => true
        ),
        'job' => array(
            'type' => 'text',
            'maxlength' =>  65535,
            'in_list' => 40,
            'filters' => array('save' => null)
        ),
        'date_start' => array(
            'type' => 'unixtimestamp',
            ),
        'date_start_job' => array(
            'type' => 'unixtimestamp',
        ),
        'date_end_job' => array(
            'type' => 'unixtimestamp',
        ),
        'event_type' => array(
            'type' => 'integer',
            'in_list' => 10,
            'sort' => true,
            //'search' => true,
         ),
        'is_send_email_on_done' => array(
            'type' => 'boolean'),
        'status' => array(
            'type' => 'integer',
            'in_list' => 10,
        ),
        'priority' => array(
            'type' => 'integer',
        ),
        'is_forced_background' => array(
            'type' => 'boolean',
            'in_list' => 5,
            'sort' => true,
        ),

    );

    protected $_isNoindex = true;
    protected $_isNoCacheClean = true;

    public function __construct(oPortal &$p){

        $this->setModelTable($p->getVar('tbl_core_jobs'));

        return parent::__construct($p);

    }

}
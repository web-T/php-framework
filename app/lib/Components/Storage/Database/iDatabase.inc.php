<?php
/**
 * Interface for database drivers
 *
 * Date: 16.01.15
 * Time: 01:05
 * @version 1.0
 * @author goshi
 * @package web-T[Storage]
 * 
 * Changelog:
 *	1.0	16.01.2015/goshi 
 */

namespace webtFramework\Components\Storage\Database;


interface iDatabase {

    public function init();

    public function close();

    public function getLastError();

    public function query($query);

    public function select($query);

    public function selectCell($query);

    public function selectRow($query);

    public function selectCol($query);

    public function escape($data);

    public function cleanupEscape($data);

    public function transaction($mode=null);

    public function commit();

    public function rollback();

} 
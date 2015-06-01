<?php
/**
 * Query Builder interface
 *
 * Date: 16.08.14
 * Time: 23:03
 * @version 1.0
 * @author goshi
 * @package web-T[Components]
 * 
 * Changelog:
 *	1.0	16.08.2014/goshi 
 */

namespace webtFramework\Components\Storage;

interface iQueryBuilder {

    public function compile($source, $conditions = array(), $where = null);
    public function compileConditions($source, $conditions = array());

    public function compileCount($source, $conditions = array(), $where = null);
    public function compileUpdate($source, $data = array(), $conditions = array(), $where = null);
    public function compileDelete($source, $conditions = array(), $where = null);
    public function compileInsert($source, $data = array(), $is_multi_insert = false);
    public function compileTruncate($source);
    public function compileOptimize($source);

    public function compileOr($source, $conditions = array());
    public function compileSearch($source, $sfields, $keywords, $wmode = '', $prefix = false, $fulltext = false, $innermode = 'or');

    public function describeTable($source, $no_cache = false);
    public function isFieldExists($source, $field);
    public function getPrimary($source);
    public function getSchema($source);

} 

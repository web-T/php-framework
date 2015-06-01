<?php
/**
 * Interface for assets
 *
 * Date: 31.01.15
 * Time: 14:35
 * @version 1.0
 * @author goshi
 * @package web-T[Asset]
 * 
 * Changelog:
 *	1.0	31.01.2015/goshi 
 */

namespace webtFramework\Components\Asset;


interface iAsset {

    public function build($version = null);

    public function addSource($source);

    public function addTarget($target);

    public function addFilters($filters);

}
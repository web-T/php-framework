<?php

/**
 * Interface for migrations
 *
 * Date: 23.01.15
 * Time: 01:05
 * @version 1.0
 * @author goshi
 * @package web-T[Storage]
 *
 * Changelog:
 *	1.0	23.01.2015/goshi
 */

namespace webtFramework\Components\Storage\Migration;

interface iMigration {

    public function up();

    public function down();
}

<?php

/**
* Standart config file with
*/
class osearch_sphinx_confClass{

	public static function getConfig($environment = 'production'){
	
		switch ($environment){
	
		case 'debug':
			 return array(
				'server' => '127.0.0.1',
				'port' => 9312,
				'sphinx_dir' => 'sudo -u USER /opt/local/sphinx/bin/',
				'config_file' => '/opt/local/sphinx/etc/sphinx.conf',
				'indexes' => array(
					'main' => 'main_index',
					'delta' => 'main_index_delta'),
			);
			break;
	
		case 'production':
		default:
			
			return array(
				'server' => '127.0.0.1',
				'port' => 9312,
				'sphinx_dir' => 'sudo -u USER /usr/bin/',
				'config_file' => '/etc/sphinx/sphinx.conf',
				'indexes' => array(
					'main' => 'main_index',
					'delta' => 'main_index_delta'),
			);
			break;
			
		}
		
	}
	
}

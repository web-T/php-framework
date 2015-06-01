<?php
/**
 * Array to XML converter
 * Original http://www.simplecoding.org/sozdanie-xml-failov-iz-php-massivov.html
 *
 * Date: 24.10.12
 * Time: 15:55
 * @version 1.0
 * @author  goshi
 * @package web-T[]
 *
 * Changelog:
 *    1.0    24.10.2012/goshi
 */

namespace webtFramework\Tools;

class Array2XML {

	private $writer;
	private $version = '1.0';
	private $encoding = 'UTF-8';
	private $rootName = 'root';

	function __construct() {
		$this->writer = new \XMLWriter();
	}

	public function convert($data) {
		$this->writer->openMemory();
		$this->writer->startDocument($this->version, $this->encoding);
		$this->writer->startElement($this->rootName);
		if (is_array($data)) {
			$this->getXML($data);
		}
		$this->writer->endElement();
		return $this->writer->outputMemory();
	}
	public function setVersion($version) {
		$this->version = $version;
	}
	public function setEncoding($encoding) {
		$this->encoding = $encoding;
	}
	public function setRootName($rootName) {
		$this->rootName = $rootName;
	}
	private function getXML($data) {
		foreach ($data as $key => $val) {
			if (is_numeric($key)) {
				$key = 'key'.$key;
			}
			if (is_array($val)) {
				$this->writer->startElement($key);
				$this->getXML($val);
				$this->writer->endElement();
			}
			else {
				$this->writer->writeElement($key, $val);
			}
		}
	}
}

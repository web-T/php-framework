<?php

namespace Test\Apps;

use webtFramework\Interfaces\oApp;

/**
 * @package Web-T CMS
 */
class test extends oApp{

	public function getSomething(&$params = array()){

		$tpl = 'index.html.tpl';
		return $this->_p->tpl->get($tpl);
	}
}

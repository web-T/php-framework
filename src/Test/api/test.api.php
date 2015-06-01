<?php

namespace Test\Api;

use webtFramework\Interfaces\oApi;
use webtFramework\Components\Response\oResponse;

class test extends oApi{

	public function doSomethingPost($data){

        // do something

        return new oResponse('All done', 200);
    }

}
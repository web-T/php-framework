<?php

namespace Test\Console;

use webtFramework\Interfaces\oConsole;
use webtFramework\Components\Console\iConsoleInput;
use webtFramework\Components\Console\iConsoleOutput;


class test extends oConsole{

    protected function configure(){
        $this->setTitle('test');
        $this->setDescription('Something testing');

    }

    public function run(iConsoleInput $input, iConsoleOutput $output){
    
    	$output->send('---- Start test ----');

		// ... something doing
        
        $output->send('---- End test ----');
    
	}
	
}
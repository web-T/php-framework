<?php
/**
 * Assets console commands
 *
 * Date: 01.02.15
 * Time: 13:15
 * @version 1.0
 * @author goshi
 * @package web-T[Console]
 * 
 * Changelog:
 *	1.0	01.02.2015/goshi 
 */

namespace webtFramework\Console;

use webtFramework\Interfaces\oConsole;
use webtFramework\Components\Console\iConsoleInput;
use webtFramework\Components\Console\iConsoleOutput;

class Assets extends oConsole {

    protected function configure(){

        $this->setTitle('assets');
        $this->setDescription('Assets maintenance tool');
        $this->addOption('--dump', null, null, 'dump assets for all applications from assets.conf.php files');

    }

    protected function _runDump(iConsoleInput $input, iConsoleOutput $output){

        $output->send('---- Start to dump assets ----');

        $bundles = scandir($this->_p->getVar('bundles_dir'));

        if ($bundles){
            foreach ($bundles as $bundle){
                if ($bundle != '.' && $bundle != '..' && file_exists($this->_p->getVar('bundles_dir').$bundle.WEBT_DS.$this->_p->getVar('config_dir').$this->_p->getVar('config_files')['assets'])){

                    require_once($this->_p->getVar('bundles_dir').$bundle.WEBT_DS.$this->_p->getVar('config_dir').$this->_p->getVar('config_files')['assets']);

                    if (isset($INFO['assets']['map']) && !empty($INFO['assets']['map'])){

                        $assets = $this->_p->getVar('assets');
                        $assets['map'] = $INFO['assets']['map'];
                        $this->_p->setVar('assets', $assets);

                        $output->send(' * Dumping assets for '.$bundle.' application...');

                        foreach ($INFO['assets']['map'] as $name => $asset){

                            $output->send('     * '.$name.' ver: '.$asset['version']);
                            $this->_p->Service('oAsset')->build($name);

                        }
                    }
                    $output->send(' ');

                }
            }
        }

        $output->send('---- End dumping assets ----');
    }


    public function run(iConsoleInput $input, iConsoleOutput $output){

        // check for external info
        if ($input->getOption('--dump')){

            $this->_runDump($input, $output);

        } else {

            $this->_runHelp($input, $output);

        }


    }

} 
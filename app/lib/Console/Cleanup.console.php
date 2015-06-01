<?php
/**
 * Framework cleaner
 *
 * Date: 17.01.15
 * Time: 18:11
 * @version 1.0
 * @author goshi
 * @package web-T[Console]
 * 
 * Changelog:
 *	1.0	17.01.2015/goshi 
 */

namespace webtFramework\Console;

use webtFramework\Interfaces\oConsole;
use webtFramework\Components\Console\iConsoleInput;
use webtFramework\Components\Console\iConsoleOutput;


class Cleanup extends oConsole {

    protected function configure(){

        $this->setTitle('cleanup');
        $this->setDescription('Cleanup system tool');
        $this->addOption('--svn', null, null, 'remove SVN files from project');
        $this->addOption('--git', null, null, 'remove GIT files from project');
        $this->addOption('--garbage', null, null, 'remove all garbage files, temporary dirs in DOC_ROOT, cached directories');

    }

    /**
     * recursive function for getting data from directory
     * @param iConsoleInput $input
     * @param iConsoleOutput $output
     * @param $dir
     * @param $inFound
     * @param $filenames
     * @param bool $removeAll
     * @return int
     */
    protected function _removeInDir(iConsoleInput $input, iConsoleOutput $output, $dir, &$inFound, $filenames, $removeAll = false) {

        $deleted = 0;

        if (!file_exists($dir))
            return $deleted;

        $d = dir($dir);

        if ($d){

            while (false !== ($r = $d->read())) {
                if($r!="." && $r!="..") {
                    // check for SVN
                    if ($removeAll || in_array($r, $filenames))
                        $inFound++;
                    // check for dir
                    if (is_dir($dir.$r)){
                        $deleted += $this->_removeInDir($input, $output, $dir.$r."/", $inFound, $filenames, $removeAll);
                    }

                    // check if we in .SVN dir
                    if ($inFound > 0){
                        @chmod($dir.$r, PERM_DIRS);

                        if (is_file($dir.$r)){
                            unlink($dir.$r);
                        } elseif (is_dir($dir.$r)){
                            $deleted += $this->_removeInDir($input, $output, $dir.$r."/", $inFound, $filenames, $removeAll);
                            rmdir($dir.$r);
                        }
                        $deleted++;
                        $output->send("* exists: ".$dir.$r.". Deleting...");
                    }

                    // check for SVN and delete it
                    if ($removeAll || in_array($r, $filenames))
                        $inFound--;

                }
            }
            $d->close();
        }

        return $deleted;
    }


    protected function _runSVN(iConsoleInput $input, iConsoleOutput $output){

        $output->send('---- Start to remove SVN files ----');

        $isFound = 0;
        $fname = array('.svn');

        $output->send("Removed: ".$this->_removeInDir($input, $output, $this->_p->getVar('BASE_APP_DIR'), $isFound, $fname));


        $output->send('---- End remove SVN files ----');

    }

    protected function _runGIT(iConsoleInput $input, iConsoleOutput $output){

        $output->send('---- Start to remove GIT files ----');

        $isFound = 0;
        $fname = array('.git');

        $output->send("Removed: ".$this->_removeInDir($input, $output, $this->_p->getVar('BASE_APP_DIR'), $isFound, $fname));


        $output->send('---- End remove GIT files ----');

    }

    protected function _runGarbage(iConsoleInput $input, iConsoleOutput $output){

        $output->send('---- Start to remove garbage ----');

        $config = array(
            'filenames' => array('.DS_Store', 'Thumbs.db'),
            'cleanup_dirs' => array(
                $this->_p->getVar('DOC_DIR').$this->_p->getVar('temp_dir'),
                $this->_p->getVar('cache')['queries_dir'],
                $this->_p->getVar('cache')['data_dir'],
                $this->_p->getVar('cache')['meta_dir'],
                $this->_p->getVar('cache')['tags_dir'],
                $this->_p->getVar('cache')['serial_dir'],
                $this->_p->getVar('cache')['static_dir'],
            )
        );

        $isBad = 0;

        // remove files
        $removed = $this->_removeInDir($input, $output, $this->_p->getVar('BASE_APP_DIR'), $isBad, $config['filenames']);

        // cleanup directories
        foreach ($config['cleanup_dirs'] as $v){
            $removed += $this->_removeInDir($input, $output, $this->_p->getVar('BASE_APP_DIR').$v.WEBT_DS, $isBad, null, true);
        }

        $output->send("Removed: ".$removed);


        $output->send('---- End remove garbage ----');

    }


    public function run(iConsoleInput $input, iConsoleOutput $output){

        // check for external info
        $found_option = false;

        if ($input->getOption('--svn')){
            $found_option = true;
            $this->_runSVN($input, $output);

        }

        if ($input->getOption('--git')){
            $found_option = true;
            $this->_runGIT($input, $output);

        }

        if ($input->getOption('--garbage')){
            $found_option = true;
            $this->_runGarbage($input, $output);

        }

        if (!$found_option) {

            $this->_runHelp($input, $output);

        }

    }


} 
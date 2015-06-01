<?php
/**
 * Search maintanance console app
 *
 * Date: 03.01.15
 * Time: 19:54
 * @version 1.0
 * @author goshi
 * @package web-T[Console]
 * 
 * Changelog:
 *	1.0	03.01.2015/goshi 
 */

namespace webtFramework\Console;

use webtFramework\Interfaces\oConsole;
use webtFramework\Components\Console\iConsoleInput;
use webtFramework\Components\Console\iConsoleOutput;
use webtFramework\Components\Event\oEvent;

class Search extends oConsole{
    
    protected $_lock_file = 'reindex.cron';
    

    protected function configure(){

        $this->setTitle('search');
        $this->setDescription('Maintenance search index application');
        $this->addOption('--reindex', null, null, 'run reindex of the search storage');
        $this->addOption('--lite', null, null, 'reindex only delta index (used with "--reindex" key)');
        $this->addOption('--remove-reindex-lock', null, null, 'remove reindex operation lock');

    }

    protected function _runReindex(iConsoleInput $input, iConsoleOutput $output){

        $output->send('---- Start reindex ----');

        if ($this->_p->getVar('search')['is_indexing']){

            if ($this->_p->lockFile($this->_lock_file, 300)){

                $output->send(' ');

                $this->_p->Module('oSearch')->index(array('no_common_index' => $input->getOption('--lite') ? true : false));

                $model = $this->_p->Model($this->_p->getVar('search')['indexing_model']);

                // generate event for reindexing
                $this->_p->events->dispatch(new oEvent(WEBT_CORE_SEARCH_REINDEX, $this));

                unset($model);

                $output->send(' ');

                $this->_p->unlockFile($this->_lock_file);

            } else {
                $output->send($this->_p->trans('Unfortunatelly, index file locked. Try to unlock it'));
            }

        } else {

            $output->send($this->_p->trans('Unfortunatelly, search index turned off'));

        }

        $output->send('---- End reindex ----');

    }

    protected function _runRemoveReindexLock(iConsoleInput $input, iConsoleOutput $output){

        $output->send('---- Start to remove migrate lock ----');

        $this->_p->unlockFile($this->_lock_file);

        $output->send('* Lock removed successfully');

        $output->send('---- End remove migrate lock ----');
    }


    public function run(iConsoleInput $input, iConsoleOutput $output){

        if ($input->getOption('--reindex')){

            $this->_runReindex($input, $output);

        } elseif ($input->getOption('--remove-reindex-lock')){

            $this->_runRemoveReindexLock($input, $output);

        } else {

            $this->_runHelp($input, $output);
        }


    }


}
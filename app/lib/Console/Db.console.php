<?php
/**
 * Core database console command
 *
 * Date: 16.01.15
 * Time: 00:30
 * @version 1.0
 * @author goshi
 * @package web-T[Console]
 * 
 * Changelog:
 *	1.0	16.01.2015/goshi 
 */

namespace webtFramework\Console;

use webtFramework\Interfaces\oConsole;
use webtFramework\Components\Console\iConsoleInput;
use webtFramework\Components\Console\iConsoleOutput;
use webtFramework\Helpers\Text;

class DB extends oConsole{

    protected $_lock_file = 'migrate';

    protected function configure(){

        $this->setTitle('db');
        $this->setDescription('Storage maintenance tool');
        $this->addOption('--migrate', null, null, 'migrate storage to the selected version. If you don\'t set option "--file", then tool migrates to the most recent version. You can specify direction for migrate (if possible - "up" or "down") as optional value. You can specify command, which migrate module must execute. The commands are:
            create - create new migrate file in updates directory, you can specify special "--name" option for migrate filename');
        $this->addOption('--file', null, null, 'used only with key "--migrate", defines which file to use for migrate (by default it use all files - but only for "up operation")');
        $this->addOption('--name', null, null, 'used only with key "--migrate create", defines additional migrate name');
        $this->addOption('--remove-migrate-lock', null, null, 'remove migrates lock');

    }

    protected function _migrateSQL(iConsoleInput $input, iConsoleOutput $output, $updates_dir, $file, &$errors, &$updated){

        $handle = @fopen($updates_dir.WEBT_DS.$file, "r");
        $query = '';
        if ($handle) {

            $output->send("* Migrate to file: ".$file." ...");
            while (!feof($handle)) {
                $query .= fgets($handle, 4096);
                if (substr(rtrim($query), -1) == ';') {
                    // we dont need to special loging queries, because all errors you can see in error.log
                    if ($this->_p->db->query(trim($query)) || !$this->_p->db->getLastError())
                        $output->send(str_pad("OK!", 10, ' ', STR_PAD_LEFT));
                    else {
                        $errors++;
                        $output->send("   ERROR or empty result");
                    }
                    $query = '';
                }
            }
            // checking for last work
            if (trim($query) != ''){
                if ($this->_p->db->query(trim($query)) || !$this->_p->db->getLastError())
                    $output->send(str_pad("OK!", 6, ' ', STR_PAD_LEFT));
                else {
                    $errors++;
                    $output->send("   ERROR or empty result");
                }
            }

            fclose($handle);
            $updated++;
        }

    }

    protected function _migratePHP(iConsoleInput $input, iConsoleOutput $output, $updates_dir, $file, &$errors, &$updated){

        // include file and read its
        include($updates_dir.WEBT_DS.$file);

        $class = '\webtApplication\Migrate\\'.str_replace('.php', '', $file);

        if (class_exists($class)){

            $class = new $class($this->_p);

            // detect direction
            if (is_string($input->getOption('--migrate')) &&  $input->getOption('--migrate') === 'down'){

                if (method_exists($class, 'down')){

                    try {
                        $output->send("* Migrate down to file: ".$file." ...");
                        $class->down();
                        $output->send(str_pad("OK!", 6, ' ', STR_PAD_LEFT));
                        $updated++;
                    } catch (\Exception $e){
                        $output->send("  ".$e->getMessage());
                    }

                } else {
                    $errors++;
                    $output->send("  ERROR: method 'down' not exists");
                }

            } else {

                if (method_exists($class, 'up')){

                    try {
                        $output->send("* Migrate up to file: ".$file." ...");
                        $class->up();
                        $output->send(str_pad("OK!", 6, ' ', STR_PAD_LEFT));
                        $updated++;
                    } catch (\Exception $e){
                        $output->send("  ".$e->getMessage());
                    }

                } else {
                    $errors++;
                    $output->send("  ERROR: method 'up' not exists");
                }


            }

        } else {

            $errors++;
            $output->send("  ERROR: wrong file format");

        }

        unset($class);

    }


    protected function _runMigrate(iConsoleInput $input, iConsoleOutput $output){

        $output->send('---- Start to migrate storages ----');

        // reading current database
        $history_file = $this->_p->getVar('var_dir').'migrate_history.webt.db';

        $updates_dir = $this->_p->getVar('var_dir').'updates';

        if (file_exists($history_file)){
            $migrate_history = json_decode(file_get_contents($history_file), true);
        } else {
            $migrate_history = array();
        }

        // setting up lock file
        if ($this->_p->lockFile($this->_lock_file, 300)){

            $errors = 0;
            $updated = 0;

            // reading _update folder list
            $dir = scandir($updates_dir);
            if (is_array($dir) && !empty($dir)){

                // if file defined
                if ($input->getOption('--file')){

                    if (in_array($input->getOption('--file'), $dir)){

                        // detect file type
                        if (preg_match('/^.*\.sql$/is', $input->getOption('--file'))){

                            $this->_migrateSQL($input, $output, $updates_dir, $input->getOption('--file'), $errors, $updated);

                        } elseif (preg_match('/^.*\.php$/is', $input->getOption('--file'))){

                            $this->_migratePHP($input, $output, $updates_dir, $input->getOption('--file'), $errors, $updated);

                        }

                        // write new file
                        @chmod($history_file, 0600);
                        $this->_p->filesystem->writeData($history_file, json_encode($migrate_history), 'w', 0400);


                    } else {

                        $output->send('Ooops. There is no file with this name');

                    }


                } else {

                    // if file not defined then read directory with updates and roll out on them

                    // making non intersection array
                    $updates = array_diff($dir, array_keys($migrate_history));

                    if ($updates){
                        foreach ($updates as $k => $v){
                            if ($v != '.' && $v != '..'){
                                $migrate_history[$v] = date('Y-m-d H:i:s', time());

                                // detect file type
                                if (preg_match('/^.*\.sql$/is', $v)){

                                    $this->_migrateSQL($input, $output, $updates_dir, $v, $errors, $updated);

                                } elseif (preg_match('/^.*\.php$/is', $v)){

                                    $this->_migratePHP($input, $output, $updates_dir, $v, $errors, $updated);

                                }

                            }
                        }

                        // write new file
                        @chmod($history_file, 0600);
                        $this->_p->filesystem->writeData($history_file, json_encode($migrate_history), 'w', 0400);
                    }

                }
            }

            $this->_p->unlockFile($this->_lock_file);
            $output->send(str_pad("Migrate done:", 3, ' ', STR_PAD_LEFT));
            $output->send(str_pad($updated, 8, ' ', STR_PAD_LEFT)." file(s) updated");
            if ($errors){
                $output->send(str_pad("Errors: ".$errors.". Please, check error log", 3, ' ', STR_PAD_LEFT));
            }

        } else {
            $output->send('* Migrate file is locked');
        }


        $output->send('---- End migrate storages ----');

    }

    protected function _runRemoveMigrateLock(iConsoleInput $input, iConsoleOutput $output){

        $output->send('---- Start to remove migrate lock ----');

        $this->_p->unlockFile($this->_lock_file);

        $output->send('* Lock removed successfully');

        $output->send('---- End remove migrate lock ----');
    }

    protected function _runCreateMigrate(iConsoleInput $input, iConsoleOutput $output){

        $output->send('---- Start to generate migrate file ----');

        $filename = date('Ymd_His');

        if ($this->_p->getVar('storages') && $this->_p->getVar('storages')['base']){
            $filename = Text::transliterate_field($this->_p->getVar('storages')['base']['db_name'], true, array('fieldReg' => $this->_p->getVar('regualars')['field_field_neg'])).'_'.$filename;
        }

        if ($input->getOption('--name')){

            $filename .= '_'.Text::transliterate_field($input->getOption('--name'), true, array('fieldReg' => $this->_p->getVar('regualars')['field_field_neg']));

        }

        $updates_dir = $this->_p->getVar('var_dir').'updates';

        if (!file_exists($updates_dir)){
           if (!$this->_p->filesystem->rmkdir($updates_dir)){
               $output->send("  Error: cannot create migrating directory");
           }
        }

        $this->_p->filesystem->writeData($updates_dir.WEBT_DS.$filename.'.php', "<?php
namespace webtApplication\\Migrate;

use webtFramework\\Components\\Storage\\Migration\\oMigration;

class ".$filename." extends oMigration{

    public function up(){

    }

    public function down(){

    }

}
        ", 'w');

        $output->send(' * Migrate file generated: '.$updates_dir.WEBT_DS.$filename.'.php');

        $output->send('---- End generate migrate file ----');

    }


    public function run(iConsoleInput $input, iConsoleOutput $output){

        // check for external info
        if ($input->getOption('--migrate')){

            if (is_string($input->getOption('--migrate'))){

                switch ($input->getOption('--migrate')){

                    case 'create':
                        $this->_runCreateMigrate($input, $output);
                        break;
                }

            } else{

                $this->_runMigrate($input, $output);

            }

        } elseif ($input->getOption('--remove-migrate-lock')){

            $this->_runRemoveMigrateLock($input, $output);

        } else {

            $this->_runHelp($input, $output);

        }


    }


}
<?php
/**
 * Core object, which works with threads/forks
 * Be careful when using this utility
 *
 * Date: 08.01.14
 * Time: 08:39
 * @version 1.0
 * @author goshi
 * @package web-T[core]
 *
 * Changelog:
 *	1.0	08.01.2014/goshi
 */

namespace webtFramework\Core;

class webtThreads {

    /**
     * @var oPortal
     */
    protected $_p;

    /**
     * default CPU count
     * @var int
     */
    protected $_default_cpu_count = 2;

    /**
     * flag if process is master
     * @var bool
     */
    protected $_is_master = false;

    /**
     * current process num
     * @var int
     */
    protected $_pnum = 0;

    /**
     * total processes
     * @var int
     */
    protected $_ptotal = 0;

    public function __construct(oPortal &$p){

        $this->_p = $p;

    }

    /**
     * method detect CPU count
     * @return int
     */
    protected function _getCPUCores(){

        // for Linux
        if (file_exists('/proc/cpuinfo') && ($res = @exec('cat /proc/cpuinfo | grep processor | wc -l 2>&1', $output)) && trim($res)){
            return (int)trim($res);
            // for MacOS/FreeBSD
        } elseif (($res = @exec("sysctl -a | grep 'hw.ncpu:' | cut -d ':' -f2"))) {
            return (int)trim($res);
        } else {
            // by default return two cores
            return $this->_default_cpu_count;
        }

    }

    /**
     * return is current process is master
     * @return bool
     */
    public function getIsMaster(){
        return $this->_is_master;
    }

    /**
     * return current process number
     * @return bool
     */
    public function getProcessNum(){
        return $this->_pnum;
    }

    /**
     * return current process total threads
     * @return bool
     */
    public function getProcessTotal(){
        return $this->_ptotal;
    }

    /**
     * forking current process
     * @param $func
     * @param $args
     * @param string $processes
     * @return array
     * @throws \Exception
     */
    public function fork($func, $args, $processes = 'auto'){

        // forking only when function/object exists
        if ($func){

            $function_exists = false;
            // object
            if (is_array($func) && class_exists($func[0]) && method_exists($func[0], $func[1])){
                $function_exists = true;
            } elseif (!is_array($func) && is_string($func) && function_exists($func)) {
                $function_exists = true;
            } elseif (!is_array($func) && is_object($func)) {
                $function_exists = true;
            }

            if ($function_exists){

                // detect processes count
                if ($processes == 'auto' || !$processes){
                    $processes = $this->_getCPUCores();
                }

                // close all database connections
                // for each processes we must reopen them
                $reinit_db_conn = false;
                if ($this->_p->db->isInit()){
                    // reinit
                    $this->_p->db->close();
                    $reinit_db_conn = true;
                }
                $pid = null;

                // forking...
                for ($proc_num = 0; $proc_num < $processes; $proc_num++) {
                    $this->_p->debug->log("Threads :: Forking process ".$proc_num);
                    $pid = pcntl_fork();
                    if ($pid < 0) {
                        $this->_p->debug->log($this->_p->m['errors']['threads']['cannot_fork'], 'error');
                        throw new \Exception($this->_p->m['errors']['threads']['cannot_fork']);
                    }
                    // if it is child process - break
                    if ($pid == 0) break;
                }

                // reinit database connection
                if ($reinit_db_conn){
                    $this->_p->db->init();
                }

                // for master process suspend execution until child exited
                if ($pid) {

                    $this->_is_master = true;
                    $this->_pnum = null;
                    $this->_ptotal = $processes;

                    for ($i = 0; $i < $processes; $i++) {
                        pcntl_wait($status);
                        $exitcode = pcntl_wexitstatus($status);

                        if ($exitcode){
                            // some error: Catchall for general errors (see http://tldp.org/LDP/abs/html/exitcodes.html)
                            $this->_p->debug->log($this->_p->m['errors']['threads']['general_error'], 'error');
                            throw new \Exception($this->_p->m['errors']['threads']['general_error']);
                            // exit(1);
                        }

                    }
                    return array('is_master' => true, 'pnum' => null, 'total' => $processes);

                } else {

                    $this->_is_master = false;
                    $this->_pnum = $proc_num;
                    $this->_ptotal = $processes;

                    // we dont need to send to args current process environment, because it can get all info from $p->threads object
                    call_user_func_array($func, $args);

                    return array('is_master' => false, 'pnum' => $proc_num, 'total' => $processes);

                }

            }

        }

        throw new \Exception($this->_p->m['errors']['threads']['no_function_exists']);

    }


}
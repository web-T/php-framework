<?php
/**
 * Console interface for web-T::CMS
 *
 * Date: 24.12.14
 * Time: 22:44
 * @version 1.0
 * @author goshi
 * @package web-T[Interfaces]
 * 
 * Changelog:
 *	1.0	24.12.2014/goshi 
 */

namespace webtFramework\Interfaces;

use webtFramework\Core\oPortal;
use webtFramework\Components\Console\iConsoleInput;
use webtFramework\Components\Console\iConsoleOutput;

abstract class oConsole extends oBase{

    /**
     * command name
     * @var
     */
    protected $_title;

    /**
     * command description
     * @var
     */
    protected $_descr;

    /**
     * command options
     * @var
     */
    protected $_options = array();

    /**
     * maximum string length for output
     * @var int
     */
    protected $_max_str_length = 80;


    public function __construct(oPortal &$p, $params = array()){

        // set flag that we use console app
        parent::__construct($p, $params);

        //$this->init();

    }

    /**
     * you need to call this method to initialize console streams
     */
    public function init(){

        //$this->_p->setVar('STREAM_TYPE', ST_CONSOLE);

        $this->configure();

        return $this;

    }

    /**
     * add option to command
     *
     * @param $name
     * @param null $shortcut
     * @param null $option_mode
     * @param string $description
     * @param null $default
     * @return $this
     */
    public function addOption($name, $shortcut = null, $option_mode = null, $description = '', $default = null){

        $this->_options[$name] = array(
            'shortcut' => $shortcut,
            'mode' => $option_mode,
            'descr' => $description,
            'default' => $default
        );

        return $this;
    }

    public function setTitle($title){

        $this->_title = $title;

        return $this;

    }

    public function setDescription($descr){

        $this->_descr = $descr;

        return $this;

    }

    public function getDescription(){

        return $this->_descr;

    }

    public function getTitle(){

        return $this->_title;

    }

    public function getOptions(){

        return $this->_options;

    }

    /**
     * method generate help output
     *
     * @param iConsoleInput $input
     * @param iConsoleOutput $output
     */
    protected function _runHelp(iConsoleInput $input, iConsoleOutput $output){

        $output->send('webT::Framework version '.WEBT_VERSION);
        $output->send('Environment: '.WEBT_ENV);
        $output->send('Command: '.$this->extractClassname());
        $output->send(' ');
        $output->send('Options:');
        $output->send(' ');
        foreach ($this->_options as $option => $op_data){

            // detect length of the string
            $string = '  '.str_pad($option, 30, ' ', STR_PAD_RIGHT).' '.$op_data['descr'];


            if (mb_strlen($string) > $this->_max_str_length){

                $new_string = array(mb_substr($string, 0, $this->_max_str_length));
                $string = str_replace($new_string[0], '', $string);
                while (mb_strlen($string) > 0){
                    $cut = mb_substr($string, 0, $this->_max_str_length - 30 - 2);
                    $new_string[] = str_pad('', 30 + 3, ' ', STR_PAD_LEFT).$cut;
                    $string = str_replace($cut, '', $string);
                }

                $string = join("\r\n", $new_string);

            }

            $output->send($string);
            $output->send(' ');
        }

    }

    /**
     * parse input arguments
     *
     * @param iConsoleInput $input
     * @param iConsoleOutput $output
     * @return bool|mixed
     */
    public function parse(iConsoleInput $input, iConsoleOutput $output){

        // detect helper
        if ($input->getOption('--help')){

            $this->_runHelp($input, $output);

        } else {

            return $this->run($input, $output);
        }

        return true;
    }

    /**
     * run console application
     * @param iConsoleInput $input
     * @param iConsoleoutput $output
     * @return mixed
     */
    abstract public function run(iConsoleInput $input, iConsoleOutput $output);


    /**
     * method need to add title, description and options
     * @return mixed
     */
    abstract protected function configure();

}
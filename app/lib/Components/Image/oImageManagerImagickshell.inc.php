<?php
/**
 * ...
 *
 * Date: 11.01.15
 * Time: 17:51
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	11.01.2015/goshi 
 */

namespace webtFramework\Components\Image;

use webtFramework\Core\oPortal;

class oImageManagerImagickshell extends oImageManagerAbstract{

    /**
     * Convert program path
     * @var string
     */
    protected $_im_convert;

    /**
     * Composite program path
     * @var string
     */
    protected $_im_composite;

    /**
     * flag of module init
     * @var bool
     */
    protected $_is_init = false;


    /**
     * initialize current GD object
     */
    protected function _init(){

        if ($this->_is_init) return;

        $os = $this->_p->server->getOsType();

        if ($os == 'win') {
            $this->_im_convert = $this->_p->getVar('image')['imagick_path'].'\convert.exe';
            $this->_im_composite = $this->_p->getVar('image')['imagick_path'].'\composite.exe';
        } else {
            $this->_im_convert = $this->_p->getVar('image')['imagick_path'].'/convert';
            $this->_im_composite = $this->_p->getVar('image')['imagick_path'].'/composite';
        }

        $this->_is_init = true;

    }

    /**
     * method test if we can use this driver
     * @param oPortal $p
     * @return bool
     */
    public static function isExists(oPortal &$p){

        $os = $p->server->getOsType();

        if ($os == 'win') {
            $path = $p->getVar('image')['imagick_path'].'\convert.exe';
        } else {
            $path = $p->getVar('image')['imagick_path'].'/convert';
        }

        // setting error level
        $ret = 128;
        if (function_exists('exec') && file_exists($path))
            @exec($path, $output, $ret);

        // for some uses like Ubuntu we need to up return value
        return ($os == 'nix' && $ret > 1) || ($os == 'win' && $ret == 1) ? false : true;

    }

    /**
     * execute image process
     * @return bool|null
     * @throws \Exception
     */
    public function execute(){

        if (!$this->_destination){
            throw new \Exception('error.image.execute_no_destination');
        }

        if (empty($this->_execution_line)){
            throw new \Exception('error.image.execute_empty_line');
        }

        if (!file_exists($this->_source)){

            throw new \Exception('error.image.execute_source_not_exists');
        }

        // init values
        $this->_init();

        list($width, $height, $type, ) = $thumbinfo = getimagesize($this->_source);

        // default parameters for conversion
        //$scale_x = $scale_y =
        $dim_x = $dim_y = 1;

        $dest_width = $width;
        $dest_height = $height;

        $options = array();

        // let's the music plays
        foreach ($this->_execution_line as $step){

            switch ($step['operation']) {

                // resize operation
                case 'resize':

                    // init sizes
                    if (!$step['width'])
                        $step['width'] = $step['height']*$dest_width/$dest_height;

                    if (!$step['height'])
                        $step['height'] = $step['width']*$dest_height/$dest_width;

                    if ($dest_width != $step['width'] || $dest_height != $step['height']){

                        $dim_x = $dest_width/$step['width'];
                        $dim_y = $dest_height/$step['height'];
                        $dest_width = $step['width'];
                        $dest_height = $step['height'];

                        $options[] = "-resize ".round($dest_width + 0)."x".round($dest_height + 0)."!";
                    }

                    break;


                // crop operation
                case 'crop':

                    if ($step['width'] > 0){
                        $crop_left = /*$dim_x**/$step['left'];
                        $crop_top = /*$dim_y**/$step['top'];
                        $crop_width = /*$dim_x**/$step['width'];
                        $crop_height = /*$dim_y**/$step['height'];

                        $crop_option = "-crop ".round($crop_width + 0)."x".round($crop_height + 0);

                        if ($crop_left > 0 || $crop_top > 0) {
                            $crop_option .= "+".round($crop_left + 0)."+".round($crop_top + 0);
                        } else {
                            $crop_option .= "+0+0";
                        }

                        $options[] = $crop_option;

                    }

                    break;

            }

        }

        $options[] = "+repage";

        // be careful - this option strips all GPS and Exif data, so image loosing its orientation information
        $options[] = "-strip";

        if ($this->_quality)
            $options[] = '-quality '.$this->_quality;

        // create directories
        $this->_p->filesystem->rmkdir(dirname($this->_destination));

        $command = $this->_im_convert.' -debug exception '.$this->_source.' '.join(' ', $options).' '.$this->_destination;

        if ($this->_p->getVar('is_debug')){
            $this->_p->debug->log($command);
        }

        passthru($command, $ret);

        if ($this->_p->getVar('is_debug')){
            $this->_p->debug->log($ret);
        }


        // if watermark
        if ($this->_watermark && file_exists($this->_watermark)){

            $gravity = null;
            switch ($this->_watermark_position){

                case self::WM_RB:
                    $gravity = 'SouthEast';
                    break;

                case self::WM_LT:
                    $gravity = 'NorthWest';
                    break;

                case self::WM_LB:
                    $gravity = 'SouthWest';
                    break;

                case self::WM_RT:
                default:
                    $gravity = 'NorthEast';
                    break;

            }

            $command = $this->_im_composite.' -dissolve 80% -gravity '.$gravity.' -quality 100 '.$this->_watermark.' '.$this->_destination.' '.$this->_destination;
            if ($this->_p->getVar('is_debug')){
                $this->_p->debug->log($command);
            }

            passthru($command);
        }

        return $ret;

    }

} 
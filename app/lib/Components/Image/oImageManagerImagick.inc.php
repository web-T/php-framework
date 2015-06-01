<?php
/**
 * ...
 *
 * Date: 11.01.15
 * Time: 18:30
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	11.01.2015/goshi 
 */

namespace webtFramework\Components\Image;

use webtFramework\Core\oPortal;

class oImageManagerImagick extends oImageManagerAbstract{

    /**
     * method test if we can use this driver
     * @param oPortal $p
     * @return bool
     */
    public static function isExists(oPortal &$p){

        // for some uses like Ubuntu we need to up return value
        return class_exists('\Imagick');

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

        list($width, $height, $type, ) = $thumbinfo = getimagesize($this->_source);

        // default parameters for conversion
        //$scale_x = $scale_y =
        $dim_x = $dim_y = 1;

        $dest_width = $width;
        $dest_height = $height;

        $ImagickObj = new \Imagick($this->_source);

        // let's the music plays
        foreach ($this->_execution_line as $step){

            switch ($step['operation']) {

                // resize operation
                case 'resize':

                    //dump_file(array('resize', $dest_width, $step['width'], $dest_height, $step['height']));

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

                        // for proper handling of transparency
                        if ($type == self::GD_GIF || $type == self::GD_PNG){
                            $ImagickObj->setImageOpacity(1.0);
                        }

                        $ImagickObj->resizeImage($dest_width, $dest_height, \Imagick::FILTER_LANCZOS, 1);

                    }

                    break;


                // crop operation
                case 'crop':

                    if ($step['width'] > 0){
                        $crop_left = /*$dim_x**/$step['left'];
                        $crop_top = /*$dim_y**/$step['top'];
                        $crop_width = /*$dim_x**/$step['width'];
                        $crop_height = /*$dim_y**/$step['height'];

                        //dump_file(array('crop', $crop_left, $crop_top, $crop_width, $crop_height));

                        $ImagickObj->cropImage($crop_width, $crop_height, $crop_left, $crop_top);
                        $ImagickObj->setImagePage($crop_width, $crop_height, 0, 0);

                    }

                    break;

            }

        }

        // be careful - this option strips all GPS and Exif data, so image loosing its orientation information
        $ImagickObj->stripImage();

        if ($this->_quality && $type == self::GD_JPG){
            $ImagickObj->setImageCompression(\Imagick::COMPRESSION_JPEG);
            $ImagickObj->setImageCompressionQuality($this->_quality);
        }

        // create directories
        $this->_p->filesystem->rmkdir(dirname($this->_destination));

        // if watermark
        if ($this->_watermark && file_exists($this->_watermark)){

            $watermark = new \Imagick();
            $watermark->readImage($this->_watermark);

            $pos_koeff = 0.1;
            $wmark_koef = 3.3;
            $w_wmark_base = $watermark->getImageWidth();
            $h_wmark_base = $watermark->getImageHeight();

            if ($w_wmark_base/$h_wmark_base > $dest_width/$dest_height){
                $w_wmark = $dest_width/$wmark_koef <= $w_wmark_base ? $dest_width/$wmark_koef : $w_wmark_base;
                $h_wmark = $h_wmark_base/$w_wmark_base*$w_wmark;
            } else {
                $h_wmark = $dest_height/$wmark_koef <= $h_wmark_base ? $dest_height/$wmark_koef : $h_wmark_base;
                $w_wmark = $w_wmark_base/$h_wmark_base*$h_wmark;
            }

            // setGravity not work, so...
            switch ($this->_watermark_position){

                case self::WM_RB:
                    $w_pos_x = $dest_width - $dest_width*$pos_koeff - $w_wmark;
                    $w_pos_y = $dest_height - $dest_height*$pos_koeff - $h_wmark;
                    break;

                case self::WM_LT:
                    $w_pos_x = $dest_width*$pos_koeff;
                    $w_pos_y = $dest_height*$pos_koeff;
                    break;

                case self::WM_LB:
                    $w_pos_x = $dest_width*$pos_koeff;
                    $w_pos_y = $dest_height - $dest_height*$pos_koeff - $h_wmark;
                    break;

                case self::WM_RT:
                default:
                    $w_pos_x = $dest_width - $dest_width*$pos_koeff - $w_wmark;
                    $w_pos_y = $dest_height*$pos_koeff;
                    break;

            }

            //Create an image that the alpha will be created in.
            $opacity = new \Imagick();
            //Create a 50% grey image
            $opacity->newPseudoImage($ImagickObj->getImageWidth(), $ImagickObj->getImageHeight(), "CANVAS:gray(80%)");
            $watermark->compositeImage($opacity, \Imagick::COMPOSITE_COPYOPACITY, 0, 0);
            $ImagickObj->compositeImage($watermark, \Imagick::COMPOSITE_ATOP, $w_pos_x, $w_pos_y);

            //$ImagickObj->compositeImage($watermark, \Imagick::COMPOSITE_DISSOLVE, $w_pos_x, $w_pos_y);

            // you can turn off flattening
            $ImagickObj->flattenImages();

        }

        //dump_file($this->_destination);

        $result = $ImagickObj->writeimage($this->_destination);

        //dump_file($result);

        return $result;

    }

} 
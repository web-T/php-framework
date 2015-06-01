<?php
/**
 * GD library driver for Image
 *
 * Date: 11.01.15
 * Time: 11:58
 * @version 1.0
 * @author goshi
 * @package web-T[Image]
 * 
 * Changelog:
 *	1.0	11.01.2015/goshi 
 */

namespace webtFramework\Components\Image;

use webtFramework\Core\oPortal;

class oImageManagerGd extends oImageManagerAbstract{

    /**
     * method test if we can use this driver
     * @param oPortal $p
     * @return bool
     */
    public static function isExists(oPortal &$p){

        return function_exists('imagecreatetruecolor');

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

        if (!$type)
            $type = self::GD_PNG;


        switch ($type){
            case self::GD_GIF:
                $src = imagecreatefromgif($this->_source);
                break;

            case self::GD_JPG:
                $src = imagecreatefromjpeg($this->_source);
                break;

            case self::GD_PNG:
                $src = imagecreatefrompng($this->_source);
                break;

            default:
                throw new \Exception('error.image.execute_image_type_not_allowed');
                break;
        }

        // default parameters for conversion
        // $scale_x = $scale_y =
        $dim_x = $dim_y = 1;

        $crop_left = $crop_top = 0;

        $crop_width = $dest_width = $width;
        $crop_height = $dest_height = $height;

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
                    }

                    break;


                // crop operation
                case 'crop':

                    if ($step['width'] > 0){
                        $crop_left = $dim_x*$step['left'];
                        $crop_top = $dim_y*$step['top'];
                        $crop_width = $dim_x*$step['width'];
                        $crop_height = $dim_y*$step['height'];

                        // update destination width and height
                        $dest_width = $step['width'];
                        $dest_height = $step['height'];

                    }

                    break;

            }

        }

        // create true color images
        if (!($dest = @imagecreatetruecolor($dest_width, $dest_height)))
            throw new \Exception('error.image.execute_bad_destination_stream');

        // saving transparency for PNG 24
        if ($type == self::GD_PNG && $thumbinfo['bits'] >= 8){

            imagealphablending($dest, false);
            imagesavealpha($dest, true);
            imageantialias($dest,true);
            $trans_colour = imagecolorallocatealpha($dest, 0, 0, 0, 127);
            imagefill($dest, 0, 0, $trans_colour);

        } else {

            $white = imagecolorallocate($dest, 255, 255, 255);
            imagefill($dest, 1, 1, $white);

        }

        // checking if first sizes are identical
        if ($width == $dest_width && $height == $dest_height){

            imagecopy($dest, $src, 0, 0, $crop_left, $crop_top, $crop_width, $crop_height);

        } else {

            imagecopyresampled($dest, $src, 0, 0, $crop_left, $crop_top, $dest_width, $dest_height, $crop_width, $crop_height);

        }

        // adding watermark
        // it is size must be no more, then 1/6 of the size
        if ($this->_watermark && file_exists($this->_watermark)){

            // getting minimum size
            $wmark_koef = 3.3;

            if (($wmarkthumb = getimagesize($this->_watermark))){

                if ($wmarkthumb[0]/$wmarkthumb[1] > $dest_width/$dest_height){
                    $w_wmark = $dest_width/$wmark_koef <= $wmarkthumb[0] ? $dest_width/$wmark_koef : $wmarkthumb[0];
                    $h_wmark = $wmarkthumb[1]/$wmarkthumb[0]*$w_wmark;
                } else {
                    $h_wmark = $dest_height/$wmark_koef <= $wmarkthumb[1] ? $dest_height/$wmark_koef : $wmarkthumb[1];
                    $w_wmark = $wmarkthumb[0]/$wmarkthumb[1]*$h_wmark;
                }

                $src_water = null;

                // position watermark
                switch ($wmarkthumb[2]){
                    case self::GD_GIF:
                        $src_water = imagecreatefromgif($this->_watermark);
                        break;
                    case self::GD_JPG:
                        $src_water = imagecreatefromjpeg($this->_watermark);
                        break;
                    case self::GD_PNG:
                        $src_water = imagecreatefrompng($this->_watermark);
                        break;
                }

                $pos_koeff = 0.1;

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


                if ($src_water){
                    imagecopyresampled($dest, $src_water, $w_pos_x, $w_pos_y, 0, 0, $w_wmark, $h_wmark, $wmarkthumb[0], $wmarkthumb[1]);
                }

            }
        }

        // for JPEG images updates gamma. Dont use for PNG - you will lost transparency
        if ($type == self::GD_JPG && function_exists('imagegammacorrect'))
            imagegammacorrect($dest, 1, 1.1);

        imageinterlace($dest, 1);

        // create directories
        $this->_p->filesystem->rmkdir(dirname($this->_destination));

        $return = null;

        switch ($type){
            case self::GD_GIF:
                $return = imagegif($dest, $this->_destination);
                break;

            case self::GD_JPG:
                $return = imagejpeg($dest, $this->_destination, $this->_quality);
                break;

            case self::GD_PNG:
                $return = imagepng($dest, $this->_destination);
                break;

        }

        imagedestroy($dest);
        imagedestroy($src);

        return $return;

    }

} 
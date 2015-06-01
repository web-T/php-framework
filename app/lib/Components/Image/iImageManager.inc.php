<?php
/**
 * Interface of the image manager
 *
 * Date: 11.01.15
 * Time: 10:53
 * @version 1.0
 * @author goshi
 * @package web-T[Image]
 * 
 * Changelog:
 *	1.0	11.01.2015/goshi 
 */

namespace webtFramework\Components\Image;

interface iImageManager {

    public function setSource($source);

    public function setDestination($destination);

    public function setQuality($quality);

    public function setWatermark($watermark);

    public function setWatermarkPosition($position);

    public function addResize($width, $height);

    public function addCrop($left, $top, $width, $height);

    public function execute();

}
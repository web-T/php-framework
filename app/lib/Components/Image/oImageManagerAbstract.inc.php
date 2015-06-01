<?php
/**
 * Abstract class for all Image's drivers
 *
 * Date: 11.01.15
 * Time: 10:54
 * @version 1.0
 * @author goshi
 * @package web-T[Image]
 * 
 * Changelog:
 *	1.0	11.01.2015/goshi 
 */

namespace webtFramework\Components\Image;

use webtFramework\Core\oPortal;

abstract class oImageManagerAbstract implements iImageManager {

    /**
     * watermark positions
     */
    const WM_LT = 1; // left top
    const WM_RT = 2; // right top
    const WM_RB = 3; // right bottom
    const WM_LB = 4; // left bottom

    /**
     * GD image types
     */
    const GD_GIF = 1;
    const GD_JPG = 2;
    const GD_PNG = 3;

    /**
     * image's source
     * @var string
     */
    protected $_source;

    /**
     * image's destination path
     * @var string
     */
    protected $_destination;

    /**
     * image's quality
     * @var int
     */
    protected $_quality = 85;

    /**
     * path to the watermark
     * @var null|string
     */
    protected $_watermark = null;

    /**
     * watermark position
     * @var int
     */
    protected $_watermark_position = self::WM_RB;

    /**
     * main execution line
     * @var array
     */
    protected $_execution_line = array();

    /**
     * low level object instance (if exists)
     * @var null
     */
    protected $_instance = null;


    /**
     * @var oPortal
     */
    protected $_p;


    public function __construct(oPortal &$p){

        $this->_p = $p;

    }

    /**
     * @param $source
     * @return oImageManagerAbstract
     */
    public function setSource($source){

        if ($source){
            $this->_source = $source;
        }

        return $this;

    }

    /**
     * @param $destination
     * @return oImageManagerAbstract
     */
    public function setDestination($destination){

        if ($destination){
            $this->_destination = $destination;
        }

        return $this;

    }

    /**
     * @param $quality
     * @return oImageManagerAbstract
     */
    public function setQuality($quality){

        if ($quality){
            $this->_quality = $quality;
        }

        return $this;

    }

    /**
     * @param $watermark
     * @return oImageManagerAbstract
     */
    public function setWatermark($watermark){

        $this->_watermark = $watermark;

        return $this;

    }

    /**
     * @param $position
     * @return oImageManagerAbstract
     */
    public function setWatermarkPosition($position){

        if ($position){

            $this->_watermark_position = $position;

        }

        return $this;

    }

    /**
     * method add resize operation to the line, you can avoid one of the sizes, then module autodetect other size
     *
     * @param int|null $width
     * @param int|null $height
     * @return oImageManagerAbstract
     */
    public function addResize($width = null, $height= null){

        if ($width || $height){
            $this->_execution_line[] = array(
                'operation' => 'resize',
                'width' => $width,
                'height' => $height
            );
        }

        return $this;

    }

    /**
     * @param $left
     * @param $top
     * @param $width
     * @param $height
     * @return oImageManagerAbstract
     */
    public function addCrop($left, $top, $width, $height){

        if ($width && $height){
            $this->_execution_line[] = array(
                'operation' => 'crop',
                'left' => $left,
                'top' => $top,
                'width' => $width,
                'height' => $height
            );
        }

        return $this;

    }

    /**
     * get lowlevel instance (if exists)
     * @return null
     */
    public function getInstance(){

        return $this->_instance;

    }

    abstract public function execute();


}
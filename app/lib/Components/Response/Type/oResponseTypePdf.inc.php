<?php
/**
 * PDF type response
 * You need to install 'psliwa/php-pdf' with composer
 *
 * Date: 05.03.15
 * Time: 22:28
 * @version 1.0
 * @author goshi
 * @package web-T[Response]
 * 
 * Changelog:
 *	1.0	05.03.2015/goshi 
 */

namespace webtFramework\Components\Response\Type;


class oResponseTypePdf implements iResponseType{

    /**
     * @param array|string $data if you want to send stylesheet with ODF, then simply send array('stylesheet' => string, 'tpl' => string)
     * @param int $code
     * @return mixed
     */
    public function fetch($data, $code = 200){

        //$documentXml and $stylesheetXml are strings contains XML documents, $stylesheetXml is optional
        if (!headers_sent())
            Header('Content-type: application/pdf', false, $code);


        return $this->render($data);

    }


    public function render($data){

        $css = $fonts = null;

        if (is_array($data)){
            $css = $data['stylesheet'];
            $fonts = $data['fonts'];
            $data = $data['data'];
        }

        //register the PHPPdf and vendor (Zend_Pdf and other dependencies) autoloaders
        //require_once 'vendor/PHPPdf/Autoloader.php';
        \PHPPdf\Autoloader::register();
        \PHPPdf\Autoloader::register('/path/to/library/lib/vendor/Zend/library');

        //if you want to generate graphic files
        //\PHPPdf\Autoloader::register('sciezka/do/biblioteki/lib/vendor/Imagine/lib');

        $loader = new \PHPPdf\Core\Configuration\LoaderImpl();

        if ($fonts)
            $loader->setFontFile($fonts);

        $builder = \PHPPdf\Core\FacadeBuilder::create($loader);
        $facade = $builder->build();


        return $facade->render($data, $css);

    }

} 
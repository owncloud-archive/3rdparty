<?php

/**
 * Create pages
 *
 * @category   Phpdocx
 * @package    elements
 * @copyright  Copyright (c) Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    LGPL
 * @version    3.0
 * @link       http://www.phpdocx.com
 * @since      File available since Release 3.0
 */
include_once dirname(__FILE__) . '/CreateElement.inc';

class CreatePage extends CreateElement
{

    /**
     * @access protected
     * @var string
     */
    protected $_xml;
    
    /**
     * @access private
     * @var CreatePage
     * @static
     */
    private static $_instance = NULL;

    /**
     * Construct
     *
     * @access public
     */
    public function __construct()
    {
        $this->pageWidthInches = 8.5;
        $this->pageHeightInches = 11;
        $this->marginTopTwips = 1417;
        $this->marginBottomTwips = 1417;
        $this->marginLeftTwips = 1701;
        $this->marginRightTwips = 1701;
        $this->marginHeaderTwips = 708;
        $this->marginFooterTwips = 708;
        $this->gutterTwips = 0;
    }

    /**
     * Destruct
     *
     * @access public
     */
    public function __destruct()
    {
        
    }

    /**
     *
     * @access public
     * @return string
     */
    public function __toString()
    {
        return $this->_xml;
    }

    /**
     *
     * @access public
     * @return CreatePage
     * @static
     */
    public static function getInstance()
    {
        if (self::$_instance == NULL) {
            self::$_instance = new CreatePage();
        }
        return self::$_instance;
    }

    /**
     * Create page
     */
    public function createPage()
    {
        $this->_xml = '';
    }

    /**
     * Gnerate pagebreak
     *
     * @access public
     * @param string $type
     */
    public function generatePageBreak($type)
    {
        if($type == 'line'){
            $type = 'textWrapping';
        }
        $this->_xml = '';
        $this->generateP();
        if ($type != '') {
            $this->generateR();
            $this->generateBR($type);
        }
        $this->cleanTemplate();
    }
        
    /**
     * Generate section
     *
     * @access public
     * @param array args[0]
     */
    public function createSection()
    {
        $this->_xml = '';
        $args = func_get_args();
        $this->generateP();
        $this->generatePPR();
        $this->generateSECTIONSECTPR();
        if (isset($args[0]['orient']) && $args[0]['orient'] == 'landscape') {
            $this->generatePGSZ($this->pageHeightInches*1440, $this->pageWidthInches*1440, 'landscape');
        } else {
            $this->generatePGSZ($this->pageWidthInches*1440, $this->pageHeightInches*1440);
        }
        $this->generatePGMAR($args);
        if (isset($args[0]['columns'])) {
            $this->generateCOLS($args[0]['columns']);
        } else {
            $this->generateCOLS();
        }
        if (isset($args[0]['titlePage'])) {
            $this->generateTITLEPG();
        }
        $this->generateDOCGRID();
    }
    
    /**
     * Create sectpr
     *
     * @access public
     * @param array args[0]
     */
    public function createSECTPR()
    {
        $this->_xml = '';
        $args = func_get_args();
        $this->generateSECTPR();
        if (isset($args[0]['orient']) && $args[0]['orient'] == 'landscape') {
            $this->generatePGSZ($this->pageHeightInches*1440, $this->pageWidthInches*1440, 'landscape');
        } else {
            $this->generatePGSZ($this->pageWidthInches*1440, $this->pageHeightInches*1440);
        }
        $this->generatePGMAR($args);
        if (isset($args[0]['columns'])) {
            $this->generateCOLS($args[0]['columns']);
        } else {
            $this->generateCOLS();
        }
        if (isset($args[0]['titlePage'])) {
            $this->generateTITLEPG();
        }
        $this->generateDOCGRID();
    }

    /**
     * Generate w:br
     *
     * @access protected
     * @param string $type
     */
    protected function generateBR($type = '')
    {
        $xml = '<' . CreateElement::NAMESPACEWORD . ':br ' .
            CreateElement::NAMESPACEWORD . ':type="' . $type . '"></' .
            CreateElement::NAMESPACEWORD . ':br>';
        $this->_xml = str_replace('__GENERATER__', $xml, $this->_xml);
    }

    /**
     * Generate w:col
     *
     * @access protected
     * @deprecated
     * @param string $w
     * @param string $space
     */
    protected function generateCOL($w = '', $space = '708')
    {

    }

    /**
     * Generate w:cols
     *
     * @access protected
     * @param string $num
     * @param string $sep
     * @param string $space
     * @param string $equalWidth
     */
    protected function generateCOLS($num = '1', $sep = '', $space = '708',
                                    $equalWidth = '')
    {
        $xml = '<' . CreateElement::NAMESPACEWORD . ':cols ' .
            CreateElement::NAMESPACEWORD . ':space="' . $space . '" ' .
            CreateElement::NAMESPACEWORD . ':num="' . $num . '"></' .
            CreateElement::NAMESPACEWORD . ':cols>__GENERATECOLS__';
        $this->_xml = str_replace('__GENERATEPGMAR__', $xml, $this->_xml);
    }

    /**
     * Generate w:docgrid
     *
     * @access protected
     * @param string $linepitch
     */
    protected function generateDOCGRID($linepitch = '360')
    {
        $xml = '<' . CreateElement::NAMESPACEWORD . ':docGrid ' .
            CreateElement::NAMESPACEWORD . ':linePitch="' .
            $linepitch . '"></' . CreateElement::NAMESPACEWORD . ':docGrid>';
        $this->_xml = str_replace('__GENERATECOLS__', $xml, $this->_xml);
    }

    /**
     * Generate w:pgmar
     *
     * @access protected
     * @param array $args[0]
     */
    protected function generatePGMAR()
    {
        $top = $this->marginTopTwips;
        $right = $this->marginRightTwips;
        $bottom = $this->marginBottomTwips;
        $left = $this->marginLeftTwips;
        $header = $this->marginHeaderTwips;
        $footer = $this->marginFooterTwips;
        $gutter = $this->gutterTwips;
        $args = func_get_args();
        if (isset($args[0][0]['top'])) {
            $top = $args[0][0]['top'];
        }
        if (isset($args[0][0]['bottom'])) {
            $bottom = $args[0][0]['bottom'];
        }
        if (isset($args[0][0]['right'])) {
            $right = $args[0][0]['right'];
        }
        if (isset($args[0][0]['left'])) {
            $left = $args[0][0]['left'];
        }

        $xml = '<' . CreateElement::NAMESPACEWORD . ':pgMar ' .
            CreateElement::NAMESPACEWORD . ':top="' . $top . '" ' .
            CreateElement::NAMESPACEWORD . ':right="' . $right . '" ' .
            CreateElement::NAMESPACEWORD . ':bottom="' . $bottom . '" ' .
            CreateElement::NAMESPACEWORD . ':left="' . $left . '" ' .
            CreateElement::NAMESPACEWORD . ':header="' . $header . '" ' .
            CreateElement::NAMESPACEWORD . ':footer="' . $footer . '" ' .
            CreateElement::NAMESPACEWORD . ':gutter="' . $gutter . '"></' .
            CreateElement::NAMESPACEWORD . ':pgMar>__GENERATEPGMAR__';
        $this->_xml = str_replace('__GENERATEPGSZ__', $xml, $this->_xml);
    }

    /**
     * Generate w:pgsz
     *
     * @access protected
     * @param string $w
     * @param string $h
     * @param string $orient
     */
    protected function generatePGSZ($w = '11906', $h = '16838', $orient = '')
    {
        $xmlAux = '<' . CreateElement::NAMESPACEWORD . ':pgSz ' .
            CreateElement::NAMESPACEWORD . ':w="' . $w . '" ' .
            CreateElement::NAMESPACEWORD . ':h="' . $h . '"';
        if ($orient != '') {
            $xmlAux .= ' ' . CreateElement::NAMESPACEWORD . ':orient="' .
                $orient . '"';
        }

        $xmlAux .= '></' . CreateElement::NAMESPACEWORD .
            ':pgSz>__GENERATEPGSZ__';

        $this->_xml = str_replace('__GENERATESECTPR__', $xmlAux, $this->_xml);
    }

    /**
     * Generate w:sectionsectpr
     *
     * @access protected
     * @param string $rId
     */
    protected function generateSECTIONSECTPR($rId = '00012240')
    {
        $xml = '<' . CreateElement::NAMESPACEWORD . ':sectPr ' .
            CreateDocx::NAMESPACEWORD . ':rsidR="' . $rId . '" ' .
            CreateDocx::NAMESPACEWORD . ':rsidRPr="' . $rId . '" ' .
            CreateDocx::NAMESPACEWORD . ':rsidSect="' . $rId .
            '">__GENERATEHEADERREFERENCE____GENERATEFOOTERREFERENCE____' .
            'GENERATESECTPR__</' . CreateElement::NAMESPACEWORD .
            ':sectPr>__GENERATEPPR__';
        $this->_xml = str_replace('__GENERATEPPR__', $xml, $this->_xml);
    }

    /**
     * Generate w:sectpr
     *
     * @access protected
     * @param string $rId
     */
    protected function generateSECTPR($rId = '00012240')
    {
        $this->_xml = '<' . CreateElement::NAMESPACEWORD . ':sectPr ' .
            CreateDocx::NAMESPACEWORD . ':rsidR="' . $rId . '" ' .
            CreateDocx::NAMESPACEWORD . ':rsidRPr="' . $rId . '" ' .
            CreateDocx::NAMESPACEWORD . ':rsidSect="' . $rId .
            '">__GENERATEHEADERREFERENCE____GENERATEFOOTERREFERENCE____' .
            'GENERATESECTPR__</' . CreateElement::NAMESPACEWORD .
            ':sectPr>';
    }

    /**
     * Generate w:titlepg
     *
     * @access protected
     */
    protected function generateTITLEPG()
    {
        $xml = '<' . CreateElement::NAMESPACEWORD . ':titlePg></' .
            CreateElement::NAMESPACEWORD . ':titlePg>__GENERATECOLS__';
        $this->_xml = str_replace('__GENERATECOLS__', $xml, $this->_xml);
    }

}

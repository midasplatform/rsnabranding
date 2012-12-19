<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/

class RSNABranding_Notification extends MIDAS_Notification
  {
  public $moduleName = 'rsnabranding';
  public $_moduleComponents = array();
  public $_models = array();

  /** init notification process*/
  public function init()
    {
    $this->addCallBack('CALLBACK_CORE_GET_FOOTER_HEADER', 'getHeader');
    $this->addCallBack('CALLBACK_CORE_GET_FOOTER_LAYOUT', 'getJs');
    }//end init

  public function getHeader()
    {
    $baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
    $cssPath = $baseUrl.'/modules/'.$this->moduleName.'/public/css/custom.layout.css';
    $jsPath = $baseUrl.'/modules/'.$this->moduleName.'/public/js/custom.layout.js';
    $cssHtml = '<link type="text/css" rel="stylesheet" href="'.$cssPath.'">';
    $jsHtml = '<script src="'.$jsPath.'"></script>';
    return $cssHtml;
    }

  public function getJs()
    {
    $baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
    $jsPath = $baseUrl.'/modules/'.$this->moduleName.'/public/js/custom.layout.js';
    $jsHtml = '<script src="'.$jsPath.'"></script>';
    return $jsHtml;
    }

  } //end class

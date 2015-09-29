<?php
/*=========================================================================
 Midas Server
 Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
 All rights reserved.
 For more information visit http://www.kitware.com/.

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

         http://www.apache.org/licenses/LICENSE-2.0.txt

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
=========================================================================*/

/** Notification manager for the rsnabranding module. */
class RSNABranding_Notification extends MIDAS_Notification
{
    public $moduleName = 'rsnabranding';
    public $_moduleComponents = array();
    public $_models = array();

    /** Initialize the notification process. */
    public function init()
    {
        $this->addCallBack('CALLBACK_CORE_GET_FOOTER_HEADER', 'getHeader');
        $this->addCallBack('CALLBACK_CORE_GET_FOOTER_LAYOUT', 'getJs');
        $this->addCallBack('CALLBACK_CORE_LAYOUT_TOPBUTTONS', 'getButton');
    }

    /** Return HTML link and script tags. */
    public function getHeader()
    {
        $baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
        $cssPath = $baseUrl.'/modules/'.$this->moduleName.'/public/css/custom.css';
        $cssHtml = '<link type="text/css" rel="stylesheet" href="'.$cssPath.'">';

        return $cssHtml;
    }

    /** Return an HTML script tag. */
    public function getJs()
    {
        $baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
        $jsPath = $baseUrl.'/modules/'.$this->moduleName.'/public/js/custom.layout.js';
        $jsHtml = '<script src="'.$jsPath.'"></script>';

        return $jsHtml;
    }

    /** Return HTML for rendering an additional button for login/register. */
    public function getButton()
    {
        $baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
        $html = '';
        if($this->userSession->Dao) {
            $html = '<li class="rsnaloginButton" title="Logout">'.
                '<a href="'.$baseUrl.'/user/logout">Logout</a></li>';
        } else {
            $html = '<li class="rsnaloginButton" title="Login">'.
                '<a href="'.$baseUrl.'/rsnabranding/auth/login">Login</a></li>'.
                '<li class="rsnaloginButton" title="Register">'.
                '<a href="'.$baseUrl.'/rsnabranding/auth/register">Register</a></li>';
        }
        return $html;
    }
}

<?php
/**
 * Magento Enterprise Edition
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magento Enterprise Edition License
 * that is bundled with this package in the file LICENSE_EE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.magentocommerce.com/license/enterprise-edition
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Reports
 * @copyright   Copyright (c) 2014 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */


/**
 * Configuration for reports
 *
 * @category   Mage
 * @package    Mage_Reports
 * @author      Magento Core Team <core@magentocommerce.com>
 */

 class Mage_Reports_Model_Config extends Varien_Object
 {
    public function getGlobalConfig( )
    {
        $dom = new DOMDocument();
        $dom -> load( Mage::getModuleDir('etc','Mage_Reports').DS.'flexConfig.xml' );

        $baseUrl = $dom -> createElement('baseUrl');
        $baseUrl -> nodeValue = Mage::getBaseUrl();

        $dom -> documentElement -> appendChild( $baseUrl );

        return $dom -> saveXML();
    }

    public function getLanguage( )
    {
        return file_get_contents( Mage::getModuleDir('etc','Mage_Reports').DS.'flexLanguage.xml' );
    }

    public function getDashboard( )
    {
        return file_get_contents( Mage::getModuleDir('etc','Mage_Reports').DS.'flexDashboard.xml' );
    }
 }


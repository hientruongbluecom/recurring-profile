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
 * @package     Mage_Shell
 * @copyright   Copyright (c) 2014 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

require_once 'abstract.php';

/**
 * Magento Log Shell Script
 *
 * @category    Mage
 * @package     Mage_Shell
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Shell_Test extends Mage_Shell_Abstract
{


    /**
     * Run script
     *
     */
    public function run()
    {
        $profileIds = array(59,58);
        $collection = Mage::getModel('sales/recurring_profile')->getCollection()
            ->addFieldToFilter('profile_id', array('in' => $profileIds));
        foreach ($collection as $profile) {
            $profileSchedule = '<span>'.$profile->getFieldLabel('start_datetime').': '.$profile->getData('start_datetime').'</span><br/>';
            $profileSchedule .= '<span>'.$profile->getFieldLabel('suspension_threshold').': '.$profile->getData('suspension_threshold').'</span><br/>';
            $schedule = $profile->exportScheduleInfo();
            foreach ($schedule as $i) {
                $info = $i->getSchedule();
                $_schedule = '';
                foreach ($info as $_info) {
                    $_schedule .= $_info;
                }
                $profileSchedule .= '<span>'.$i->getTitle().': '.$_schedule.'</span><br/>';
            }
//            echo nl2br($profileSchedule); die;
        }
        $numberDay = 2;
        $timeLimit = strtotime('-'.$numberDay.' days', Mage::getModel('core/date')->timestamp(time()));
//        echo date('Y-m-d h:i:s', $timeLimit);
//        echo Mage::helper('core')->formatTime('2016-06-25 12:13:16', 'short', true);
        echo $this->__('The recurring profile not input info card.');
        echo Mage::helper('bluecom_gmo')->__('Card Info deleted. Missing Payment.');
    }

}

$shell = new Mage_Shell_Test();
$shell->run();

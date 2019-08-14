<?php
/**
 * DataCash
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@datacash.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please refer to http://testserver.datacash.com/software/download.cgi
 * for more information. 
 *
 * @author Kristjan Heinaste <kristjan@ontapgroup.com>
 * @package DataCash
 **/
class DataCash_Dpg_Model_Source_RsgServiceTypes
{
    /**
     * Return a list of available options for 3DS behaviours
     *
     * @return array
     * @author Kristjan Heinaste <kristjan@ontapgroup.com>
     **/
    public function toOptionArray()
    {
        $options = array(
            array(
                'value' => 1,
                'label' => 'Pre-Auth Fraud Checking'
            ),
            array(
                'value' => 2,
                'label' => 'Post-Auth Fraud Checking'
            ),
            array(
                'value' => 't3m',
                'label' => 'Legacy T3M'
            ),
        );
        return $options;
    }
}
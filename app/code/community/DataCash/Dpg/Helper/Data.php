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
 * @author Alistair Stead
 * @version $Id$
 * @copyright DataCash, 11 April, 2011
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @package DataCash
 **/

/**
 * DataCash_Dpg_Helper_Data
 *
 * Helper class that provides a number of transformation functions for use within the module
 *
 * @package DataCash
 * @subpackage Helper
 * @author Alistair Stead
 */
class DataCash_Dpg_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_status_prefix = "status_";

    /**
     * Tries to get a user-friently version from translartion by status code
     * returns False when it does not find it. 
     *
     * @var $status string
     */
    public function getUserFriendlyStatus($status)
    {
        $translated = $this->__($this->_status_prefix.$status);
        if ($translated == $status) {
            return false;
        }
        return $translated;
    }

    /**
     * Return a label for the supplied key
     *
     * Used to transpose object data keys to correct UI labels and also add
     * translation functionality
     *
     * @return string
     * @author Alistair Stead
     **/
    public function getLabel($key)
    {
        $labels = array(
            'cc_avs_status' => 'CV2 Status',
            'cc_avs_address_result' => 'CV2 Address',
            'cc_avs_postcode_result' => 'CV2 Postcode',
            'cc_avs_cv2_result' => 'CV2 Number',
            'cc_trans_id' => 'DataCash Transaction Number',
            'cc_approval' => 'Auth Code',
            'cc_status' => 'Transaction Status Code',
            'cc_status_description' => 'Transaction Status',
            'mode' => 'DataCash Mode',
            't3m_score' => 'DataCash Fraud Services Score',
            't3m_recommendation' => 'DataCash Fraud Services Recommendation',
        );

        return (!empty($labels[$key]))? $this->__($labels[$key]) : $key;
    }

    /**
     * Calculate the device type being used to access the website
     *
     * @param string $userAgent the request user_agent string
     * @return int
     * @author Alistair Stead
     **/
    public function getDeviceType($userAgent)
    {
        $mobileAgents = array(
            "iPhone",           // Apple iPhone
            "iPod",            // Apple iPod touch
            "Android",        // 1.5+ Android
            "dream",          // Pre 1.5 Android
            "CUPCAKE",        // 1.5+ Android
            "blackberry",   // Blackberry
            "webOS",         // Palm Pre Experimental
            "incognito",        // Other iPhone browser
            "webmate",        // Other iPhone browser
            "s8000",           // Samsung Dolphin browser
            "bada"             // Samsung Dolphin browser
        );

        foreach ($mobileAgents as $mobileAgent) {
            if (strstr($mobileAgent, $userAgent)) {
                return '1';
            }
        }

        return '0';
    }
}


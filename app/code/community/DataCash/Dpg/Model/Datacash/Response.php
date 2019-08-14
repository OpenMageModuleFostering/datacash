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
 
class DataCash_Dpg_Model_Datacash_Response extends Varien_Object 
{
    /**
     * Was the transaction successfully transmitted to DataCash
     * for processing
     *
     * @return bool
     * @author Alistair Stead
     **/
    public function isSuccessful()
    {
        if ($this->getStatus() == DataCash_Dpg_Model_Code::SUCCESS) {
            return true;
        }
        return false;
    }
    
    /**
     * isMarkedForReview function.
     * 
     * @access public
     * @return string
     */
    public function isMarkedForReview()
    {
        return (string)$this->getData('Risk/action_response/screening_response/response_code') == DataCash_Dpg_Model_Code::REVIEW;
    }
    
    /**
     * Was the transaction judged to be fraudulent
     *
     * @return string
     * @author Alistair Stead
     **/
    public function isFraud()
    {
        return (string)$this->getData('Risk/action_response/screening_response/response_code') == DataCash_Dpg_Model_Code::FRAUD;
    }
    
    /**
     * getRiskResponse function.
     * 
     * @access public
     * @return array
     */
    public function getRiskResponse()
    {
        return $this->getData('Risk/action_response');
    }
    
    /**
     * Does the payment transaction require review
     *
     * @return bool
     * @author Alistair Stead
     **/
    public function isReviewRequired()
    {
        // This should be implemented in phase 2 when ReD is implemented
        return false;
    }
    
    /**
     * Proxy method to obtain the requently required response authcode
     * from the complex data structure
     *
     * @return string
     * @author Alistair Stead
     **/
    public function getAuthCode()
    {
        $cardTxn = $this->getData('CardTxn');

        return (isset($cardTxn['authcode']))? $cardTxn['authcode'] : '';
    }
    
    /**
     * Proxy method to obtain the frequently required CV2 response message
     * from the complex data structure
     *
     * @return string
     * @author Alistair Stead
     **/
    public function getCv2AvsStatus()
    {
        $cardTxn = $this->getData('CardTxn');

        $response = (isset($cardTxn['Cv2Avs']['cv2avs_status']))? $cardTxn['Cv2Avs']['cv2avs_status'] : '';
        
        return (is_array($response))? $response[0] : $response;
    }
    
    /**
     * Proxy method to obtain the frequently required CV2 response message
     * from the complex data structure
     *
     * @return array
     * @author Alistair Stead
     **/
    public function getCv2Avs()
    {
        $cardTxn = $this->getData('CardTxn');

        return (isset($cardTxn['Cv2Avs']))? $cardTxn['Cv2Avs'] : array();
    }

    /**
     * Internal method to validate extended policy
     * @return array
     */
    public function t3mToMappedArray($mapping)
    {
        $data = $this->getData();
        if (!isset($data['The3rdManRealtime'])) {
            return array();
        }
        $data = $data['The3rdManRealtime'];
        $t3m = array();
        foreach ($mapping as $i => $j) {
            if (isset($data[$j])) {
                $t3m[$i] = $data[$j];
            }
        }
        return $t3m;
    }
    
    /**
     * Get Token string
     * @return string
     * @author Kristjan Heinaste <kristjan@ontapgroup.com>
     */
    public function getToken()
    {
        $cardTxn = $this->getData('CardTxn');

        return (isset($cardTxn['token'])) ? (string)$cardTxn['token'] : null;
    }
    
}

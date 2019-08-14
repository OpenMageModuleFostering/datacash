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
class DataCash_Dpg_Model_Method_Apiprereg extends DataCash_Dpg_Model_Method_Abstract
{
    protected $_code  = 'datacash_apiprereg';
    protected $_formBlockType = 'dpg/form_apiprereg';
    protected $_infoBlockType = 'payment/info_cc';

    /**
     * Payment Method features
     * @var bool
     */
    protected $_isGateway                   = true;
    protected $_canOrder                    = true;
    protected $_canAuthorize                = true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = true;
    protected $_canRefund                   = true;
    protected $_canRefundInvoicePartial     = true;
    protected $_canVoid                     = true;
    protected $_canUseInternal              = true;
    protected $_canUseCheckout              = true;
    protected $_canUseForMultishipping      = true;
    protected $_isInitializeNeeded          = false;
    protected $_canFetchTransactionInfo     = true;
    protected $_canReviewPayment            = true;
    protected $_canCreateBillingAgreement   = true;
    protected $_canManageRecurringProfiles  = true;
    protected $_canCancelInvoice            = false;

    /**
     * This forces the method to be disabled at all times.
     * This payment method is DEPRICATED
     * the code is here only for backward-availability
     */
    public function isAvailable($quote = null) {
        return false;
    }

    public function assignData($data)
    {
        parent::assignData($data);
        $info = $this->getInfoInstance();
        $info->setAdditionalData($data->getCcTransId());
        $info->setCcTransId($data->getCcTransId());
        return $this;
    }
    /**
     * Initialize the data required be the API across all methods
     *
     * @return void
     * @author Alistair Stead
     **/
    protected function _initApi()
    {
        if (is_null($this->_api)) {
            $this->_api = Mage::getModel('dpg/api_directprereg');
        }
        $this->_api->setMerchantId($this->_getApiMerchantId());
        $this->_api->setMerchantPassword($this->_getApiPassword());

        $this->_api->setIsSandboxed($this->getConfig()->isMethodSandboxed($this->getCode()));
    }

    /**
     * Authorise the payment
     *
     * @param Varien_Object $payment 
     * @param string $amount 
     * @return DataCash_Dpg_Model_Method_Api
     * @author Alistair Stead
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        parent::authorize($payment, $amount);
        $this->_initApi();
        $this->_mapRequestDataToApi($payment, $amount);
        try {
            $this->_api->callPre();
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }     
        // Process the response
        $response = $this->_api->getResponse();
        if ($response->isSuccessful()) {
            // Map data to the payment
            $this->_mapResponseToPayment($response, $payment);
        } else {
            Mage::throwException($response->getReason());
        }
        
        return $this;
    }

    public function getCentinelValidator()
    {
        return $this->_getCentinelValidator('dpg/service_directprereg');
    }

    public function getCentinelValidationData()
    {
        // Transaction ID returned from service is stored in cc_trans_id property.
        // Transaction ID submitted in form is stored in additional_data property.
        $data = parent::getCentinelValidationData();
        $transid = $this->getInfoInstance()->getAdditionalData();
        if (!is_numeric($transid)) {
            Mage::throwException('DataCash Transaction ID not a numeric string');
        }
        $data->setDataCashCardReference($transid);
        return $data;
    }

    protected function _mapRequestDataToApi($payment, $amount)
    {
        parent::_mapRequestDataToApi($payment, $amount);
        // Set the object properties required to make the API call
        $transid = $payment->getAdditionalData();
        $this->_api->setDataCashCardReference($transid);
    }
    
/**
* Capture the payment
*
* @param Varien_Object $payment
* @param string $amount
* @return DataCash_Dpg_Model_Method_Api
* @author Alistair Stead
*/
    public function capture(Varien_Object $payment, $amount)
    {
        parent::capture($payment, $amount);
        $order = $payment->getOrder();
        $authTransaction = $payment->getAuthorizationTransaction();
        $this->_initApi();
        $this->_mapRequestDataToApi($payment, $amount);
        // If the payment has already been authorized we need to only call fullfill
        if ($authTransaction && $payment->getAmountAuthorized() > 0 && $payment->getAmountAuthorized() >= $amount) {
            try {
                $this->_api->callFulfill();
            } catch (Exception $e) {
                Mage::throwException($e->getMessage());
            }
        } else if ($authTransaction && $payment->getAmountAuthorized() > 0 && $payment->getAmountAuthorized() < $amount) {
            throw new Exception('This card has not been authorized for this amount');
        } else {
            try {
                $this->_api->callAuth();
            } catch (Exception $e) {
                Mage::throwException($e->getMessage());
            }
        }
        
        // Process the response
        $response = $this->_api->getResponse();
        if ($response->isSuccessful()) {
            $this->_mapResponseToPayment($response, $payment);
        } else {
            Mage::throwException($response->getReason());
        }
        
        return $this;
    } 
    /**
     * Overload the parent validation method
     *
     * This method is overloaded to allow the validation to be turned
     * off during testing as the DataCash magic numbers do not pass the validation
     *
     * @return Mage_Payment_Model_Abstract
     **/
    public function validate()
    {
        // Mage-Test is being used to test this module. It is not required in the production
        // environment. This variable is only set during testing
        if (isset($_SERVER['MAGE_TEST'])) {
            return $this;
        }
        
        if ($this->getIsCentinelValidationEnabled()) {
            $this->getCentinelValidator()->validate($this->getCentinelValidationData());
        }

        return $this;
    }
}

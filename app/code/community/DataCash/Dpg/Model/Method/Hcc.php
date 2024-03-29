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

class DataCash_Dpg_Model_Method_Hcc
    extends DataCash_Dpg_Model_Method_Hosted_Abstract
{
    protected $_code = 'datacash_hcc';
    protected $_formBlockType = 'dpg/form_iframe';
    protected $_infoBlockType = 'dpg/info_hcc';
    protected $_config = null;
    protected $_api = null;

    /**
    * Payment Method features
    * @var bool
    */
    protected $_isGateway = true;
    protected $_canOrder = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = true;
    protected $_isInitializeNeeded = false;
    protected $_canFetchTransactionInfo = true;
    protected $_canReviewPayment = true;
    protected $_canCreateBillingAgreement = true;
    protected $_canManageRecurringProfiles = true;

    /**
    * Initialize the data required by the API
    *
    * @return void
    * @author Hilary Boyce
    **/
    protected function _initApi()
    {
        if (is_null($this->_api)) {
            $this->_api = Mage::getModel('dpg/api_hcc');
        }
        $this->_api->setMerchantId($this->_getApiMerchantId());
        $this->_api->setMerchantPassword($this->_getApiPassword());

        $this->_api->setIsSandboxed($this->getConfig()->isMethodSandboxed($this->getCode()));

        if ($this->hasAdvancedVerification()) {
            $this->_api->setIsUseExtendedCv2(true);
            $this->_api->setCv2ExtendedPolicy($this->_extendedPolicy());
        }
        $this->_api->setIsUse3d($this->getIsCentinelValidationEnabled());
        // Set if line items should be transmitted
        $this->_api->setIsLineItemsEnabled($this->getConfig()->isLineItemsEnabled($this->getCode()));
    }

    // TODO: refactor to generic
    public function getTxnData($txn_id)
    {
        $this->_api = Mage::getModel('dpg/api_hcc');

        $this->_api->setMerchantId($this->_getApiMerchantId());
        $this->_api->setMerchantPassword($this->_getApiPassword());

        $this->_api->setIsSandboxed($this->getConfig()->isMethodSandboxed($this->getCode()));

        $this->_api->queryTxn($txn_id);

        $response = $this->_api->getResponse();

        if ($response->isSuccessful()) {
            return $response;
        } else {
            $message = Mage::helper('dpg')->getUserFriendlyStatus($response->getStatus());
            throw new Mage_Payment_Model_Info_Exception($message ? $message : $response->getReason());
        }
    }

    /**
    * initSession
    * Initialise session with DataCash for HCC
    *
    * @param Mage_Sales_Model_Quote $quote
    * @author Hilary Boyce
    */
    public function initSession(Mage_Sales_Model_Quote $quote)
    {
        $quote->setReservedOrderId(null);
        $orderId = $this->_getOrderNumber($quote);
        $returnUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB).'checkout/hosted/complete/';

        $this->_initApi();

        $amount = $quote->getBaseGrandTotal();
        if ($amount == 0) {
            $amount = 1;
        }

        // Set the object properties required to make the API call
        $this->_api->setOrderNumber($orderId)
            ->setAmount($amount)
            ->setCurrency($quote->getBaseCurrencyCode())
            ->setReturnUrl($returnUrl)
            ->setExpiryUrl($returnUrl)
            ->setPageSetId($this->getConfigData('page_set_id'))
            ->setToken($this->getToken());
        // Make the API call
        try {
            $this->_api->setUpHccSession();
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }

        // Process the response
        $response = $this->_api->getResponse();
        if ($response->isSuccessful()) {
            // Set the data returned from the response for later use
            $datacashSession = array(
                'HpsUrl' => $response->getData('HpsTxn/hps_url'),
                'SessionId' => $response->getData('HpsTxn/session_id'),
                'DatacashReference' => $response->getDatacashReference(),
            );
            $session = $this->_getDataCashSession();
            $session->setData($this->_code . '_session', $datacashSession);
            $session->setData($this->_code . '_save_token', $this->getSaveToken());
        } else {
            Mage::throwException($response->getReason());
        }
    }

    /**
     * Authorise the payment
     *
     * @param Varien_Object $payment
     * @param string $amount
     * @return DataCash_Dpg_Model_Method_Api
     * @author Alistair Stead, Norbert Nagy
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        parent::authorize($payment, $amount);
        $this->_initApi();
        $this->_mapRequestDataToApi($payment, $amount);

        try {
            if ($this->hasFraudScreening()) {
                $this->_api->setUseFraudScreening(true);
                $this->_api->setFraudScreeningPolicy($this->_fraudPolicy());
            }
            if ($this->getIsCentinelValidationEnabled()) {
                $validator = $this->getCentinelValidator();
                $this->_api->call3DAuthorization($validator);
            } else {
                $this->_api->callPre();
            }
            $t3mResponse = $this->_api->getResponse()->t3MToMappedArray($this->getConfig()->_t3mResponseMap);
            Mage::dispatchEvent('datacash_dpg_t3m_response', $t3mResponse);
        } catch (Exception $e) {
            throw new Mage_Payment_Model_Info_Exception($e->getMessage());
        }

        // Process the response
        $response = $this->_api->getResponse();
        if ($response->isSuccessful() || $response->isMarkedForReview()) {
            // Map data to the payment
            $this->mapT3mInfoToPayment($t3mResponse, $payment);
            $this->_mapResponseToPayment($response, $payment);
        } else {
            $message = Mage::helper('dpg')->getUserFriendlyStatus($response->getStatus());
            throw new Mage_Payment_Model_Info_Exception($message ? $message : $response->getReason());
        }

        return $this;
    }

    /**
     * Capture the payment
     *
     * @param Varien_Object $payment
     * @param string $amount
     * @return DataCash_Dpg_Model_Method_Api
     * @author Alistair Stead, Norbert Nagy
     */
    public function capture(Varien_Object $payment, $amount)
    {
        $authTransaction = $payment->getAuthorizationTransaction();
        $order = $payment->getOrder();

        $fulfill = (bool)$authTransaction;
        if (!$fulfill && $payment->getId()) {
            $collection = Mage::getModel('sales/order_payment_transaction')->getCollection()
                ->setOrderFilter($payment->getOrder())
                ->addPaymentIdFilter($payment->getId())
                ->addTxnTypeFilter(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
            $fulfill = $collection->count();
        }
        
        // Old T3M auth
        if ($this->getConfig()->getIsAllowedT3m($this->getCode())) {
            if ($this->getIsCallbackRequest() == 1) { // In case of callbacks, the order is already fulfilled by invoice->capture see Observer.php
                return $this;
            }
            // Must preauth card first (if not already done so) to make third
            // man realtime check.
            if (!$authTransaction) {
                if (!array_intersect_key($payment->getAdditionalInformation(), array('t3m_score'=>1, 't3m_recommendation'=>1))) {
                    $this->authorize($payment, $amount);
    
                    if ($this->_t3mRecommendation != 'Release') {
                        return $this;
                    }
    
                    $fulfill = true;
                }
                if ($this->_t3mRecommendation == 'Release') {
                    $this->_api->setRequest(Mage::getModel('dpg/datacash_request'));
                    $this->_api->getRequest()->addAuthentication($this->_api->getMerchantId(), $this->_api->getMerchantPassword());
    
                    $payment->setAmountAuthorized($amount);
                }
            }
        }
        parent::capture($payment, $amount);
        $this->_initApi();
        $this->_mapRequestDataToApi($payment, $amount);

        // If the payment has already been authorized we need to only call fullfill
        if ($fulfill && $payment->getAmountAuthorized() > 0 && $payment->getAmountAuthorized() >= $amount) {
            try {
                $this->_api->callFulfill();
            } catch (Exception $e) {
                throw new Mage_Payment_Model_Info_Exception($e->getMessage());
            }
        } else if ($fulfill && $payment->getAmountAuthorized() > 0 && $payment->getAmountAuthorized() < $amount) {
            throw new Exception('This card has not been authorized for this amount');
        } else {
            try {
                if ($this->hasFraudScreening()) {
                    $this->_api->setUseFraudScreening(true);
                    $this->_api->setFraudScreeningPolicy($this->_fraudPolicy());
                }

                if ($this->getIsCentinelValidationEnabled()) {
                    // if 3D secure check is turned on, we just have to authorize the previous calls
                    $validator = $this->getCentinelValidator();
                    $this->_api->call3DAuthorization($validator);
                } else {
                    $this->_api->callAuth();
                }
            } catch (Exception $e) {
                throw new Mage_Payment_Model_Info_Exception($e->getMessage());
            }
        }

        // Process the response
        $response = $this->_api->getResponse();
        if ($response->isSuccessful() || $response->isMarkedForReview()) {
            // Map data to the payment
            $this->_mapResponseToPayment($response, $payment);
        } else {
            $message = Mage::helper('dpg')->getUserFriendlyStatus($response->getStatus());
            throw new Mage_Payment_Model_Info_Exception($message ? $message : $response->getReason());
        }

        return $this;
    }

    /**
     * Instantiate centinel validator model
     *
     * @return Mage_Centinel_Model_Service
     */
    public function getCentinelValidator()
    {
        $validator = Mage::getSingleton('dpg/service_hcc');
        $validator
            ->setIsModeStrict($this->getConfigData('centinel_is_mode_strict'))
            ->setCustomApiEndpointUrl($this->getConfigData('centinel_api_url'))
            ->setCode($this->getCode())
            ->setStore($this->getStore())
            ->setIsPlaceOrder($this->_isPlaceOrder());

        if ($this->hasFraudScreening()) {
            $validator->setUseFraudScreening(true);
            $validator->setFraudScreeningPolicy($this->_fraudPolicy());
        }

        if ($this->hasAdvancedVerification()) {
            $validator->setIsUseExtendedCv2(true)
                      ->setCv2ExtendedPolicy($this->_extendedPolicy());
        }
        return $validator;
    }

    /**
     * HCC always has CV2 verification
     *
     * I've removed any reference in the datacash module to this method, but in case it is called
     * anywhere else from Magento I've forced it to true
     *
     * @return bool
     */
    public function hasVerification()
    {
        return true;
    }
}

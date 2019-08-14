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
 
abstract class DataCash_Dpg_Model_Method_Abstract extends Mage_Payment_Model_Method_Cc
{
    /**
     * Member variable that will hold reference to the API broker.
     * 
     * The broker class will handle all the external calls
     *
     * @var string
     **/
    protected $_api;
    
    protected $_config;
    protected $_token = null;
    
    /**
     *
     * @return DataCash_Dpg_Model_Method_Hcc
     */
    public function __construct($params = array())
    {
        parent::__construct($params);
        
        return $this;
    }
    
    /**
     * Add token to instance variable
     * @param $token DataCash_Dpg_Model_Tokencard
     */
    public function setToken(DataCash_Dpg_Model_Tokencard $token)
    {
        $this->_token = $token;
        return $this;
    }
    
    public function getToken()
    {
        return $this->_token;
    }
        
    /**
    * getMethodAuthoriseStartUrl
    * @return string url for placeform action for redirection to Datacash
    */
    public function getMethodAuthoriseStartUrl()
    {
          return Mage::getUrl('checkout/hosted/start', array('_secure' => true));
    }
    
    /**
     * Check if current quote is multishipping
     */
    protected function _isMultishippingCheckout() {
        if (Mage::getSingleton('admin/session')->isLoggedIn()) {
            return false;
        }
        
        return (bool) Mage::getSingleton('checkout/session')->getQuote()->getIsMultiShipping();
    }
    
    public function hasAdvancedVerification()
    {
        $configData = $this->getConfigData('useccv_advanced');
        if(is_null($configData)){
            return false;
        }
        return (bool) $configData;
    }
    
    public function hasFraudScreening()
    {
        $configData = $this->getConfigData('allow_fraud_screening');
        if(is_null($configData) || $configData=='0'){
            return false;
        }
        return (bool) $configData;
    }    
    
    /**
     * 
     * Gets Extended policy from config.
     * @returns array $extendedPolicy
     * @author Hilary Boyce
     */
    protected function _extendedPolicy()
    {
        $extendedPolicy = array(
                    'cv2_policy' => array(
                        'notprovided'  => $this->getConfigData('extpol_cv2policy_notprovided'),
                        'notchecked'   => $this->getConfigData('extpol_cv2policy_notchecked'),
                        'matched'      => $this->getConfigData('extpol_cv2policy_matched'),
                        'notmatched'   => $this->getConfigData('extpol_cv2policy_notmatched'),
                        'partialmatch' => $this->getConfigData('extpol_cv2policy_partialmatch')
                    ),
                    'postcode_policy' => array(
                        'notprovided'  => $this->getConfigData('extpol_postcode_notprovided'),
                        'notchecked'   => $this->getConfigData('extpol_postcode_notchecked'),
                        'matched'      => $this->getConfigData('extpol_postcode_matched'),
                        'notmatched'   => $this->getConfigData('extpol_postcode_notmatched'),
                        'partialmatch' => $this->getConfigData('extpol_postcode_partialmatch')
                    ),
                    'address_policy' => array(
                        'notprovided'  => $this->getConfigData('extpol_address_notprovided'),
                        'notchecked'   => $this->getConfigData('extpol_address_notchecked'),
                        'matched'      => $this->getConfigData('extpol_address_matched'),
                        'notmatched'   => $this->getConfigData('extpol_address_notmatched'),
                        'partialmatch' => $this->getConfigData('extpol_address_partialmatch')
                    )
                );
        return $extendedPolicy;
    }
    
    protected function _fraudPolicy()
    {
        $fraudPolicy = array(
            'customer' => array(
                'first_name'    => true, // XXX: this field is always included
                'surname'       => true, // XXX: same as above because one field controls both
                'ip_address'    => $this->getConfigData('rsg_data_customer_ip_address'),
                'email_address' => $this->getConfigData('rsg_data_customer_email_address'),
                'user_id'       => $this->getConfigData('rsg_data_customer_user_id'),
                'telephone'     => $this->getConfigData('rsg_data_customer_telephone'),
                'address_line1' => $this->getConfigData('rsg_data_customer_address_line1'),
                'address_line2' => $this->getConfigData('rsg_data_customer_address_line2'),
                'city'          => $this->getConfigData('rsg_data_customer_city'),
                'state_province'=> $this->getConfigData('rsg_data_customer_state_province'),
                'country'       => $this->getConfigData('rsg_data_customer_country'),
                'zip_code'      => $this->getConfigData('rsg_data_customer_zip_code'),
            ),
            'shipping' => array(
                'first_name'    => $this->getConfigData('rsg_data_shipping_first_name'),
                'surname'       => $this->getConfigData('rsg_data_shipping_surname'),
                'address_line1' => $this->getConfigData('rsg_data_shipping_address_line1'),
                'address_line2' => $this->getConfigData('rsg_data_shipping_address_line2'),
                'city'          => $this->getConfigData('rsg_data_shipping_city'),
                'state_province'=> $this->getConfigData('rsg_data_shipping_state_province'),
                'country'       => $this->getConfigData('rsg_data_shipping_country'),
                'zip_code'      => $this->getConfigData('rsg_data_shipping_zip_code'),            
            ),
            'payment' => array(
                'payment_method'=> $this->getConfigData('rsg_data_payment_payment_method'),
            ),
            'order' => array(
                'time_zone'     => $this->getConfigData('rsg_data_order_time_zone'),
                'discount_value'=> $this->getConfigData('rsg_data_order_discount_value'),
            ),
            'item' => array(
                'product_code'  => $this->getConfigData('rsg_data_item_product_code'),
                'product_description'=> $this->getConfigData('rsg_data_item_product_description'),
                'product_category'=> $this->getConfigData('rsg_data_item_product_category'),
                'order_quantity'=> $this->getConfigData('rsg_data_item_order_quantity'),
                'unit_price'    => $this->getConfigData('rsg_data_item_unit_price'),
            ),
            'billing' => array(
                'name'          => $this->getConfigData('rsg_data_billing_name'),
                'address_line1' => $this->getConfigData('rsg_data_billing_address_line1'),
                'address_line2' => $this->getConfigData('rsg_data_billing_address_line2'),
                'city'          => $this->getConfigData('rsg_data_billing_city'),
                'state_province'=> $this->getConfigData('rsg_data_billing_state_province'),
                'country'       => $this->getConfigData('rsg_data_billing_country'),
                'zip_code'      => $this->getConfigData('rsg_data_billing_zip_code'),
            ),
        );
        return $fraudPolicy;
    }
    
    public function setApi($value)
    {
        $this->_api = $value;
    }
    
    /**
     * Void the payment
     *
     * @param Varien_Object $payment 
     * @return DataCash_Dpg_Model_Method_Api
     * @author Alistair Stead
     */
    public function void(Varien_Object $payment)
    {
        parent::void($payment);
        $this->cancel($payment);
        
        return $this;
    } 
    
    /**
     * Refund the payment
     *
     * @param Varien_Object $payment 
     * @param string $amount 
     * @return void
     * @author Alistair Stead
     */
    public function refund(Varien_Object $payment, $amount)
    {
        parent::refund($payment, $amount);
        $this->_initApi();
        $this->_mapRequestDataToApi($payment, $amount);
        try {
            $this->_api->callTxnRefund();
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }
        // Process the response
        $response = $this->_api->getResponse();
        if ($response->isSuccessful()) {
            $payment->setTransactionId($response->getDatacashReference())
                ->setShouldCloseParentTransaction(false)
                ->setIsTransactionClosed(false);
        } else {
            Mage::throwException($response->getReason());
        }

        return $this;
    }
    
    /**
     * Cancel payment
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return Mage_Paypal_Model_Direct
     * @author Alistair Stead
     */
    public function cancel(Varien_Object $payment)
    {
        parent::cancel($payment);
        $this->_initApi();
        $this->_mapRequestDataToApi($payment, null);
        try {
            $this->_api->callCancel();
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }
        // Process the response
        $response = $this->_api->getResponse();
        if ($response->isSuccessful()) {
            $payment->setTransactionId($response->getDatacashReference())
                ->setShouldCloseParentTransaction(true)
                ->setIsTransactionClosed(true);
        } else {
            Mage::throwException($response->getReason());
        }

        return $this;
    }
    
    /**
     * Attempt to accept a pending payment
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return bool
     * @author Alistair Stead & Kristjan Heinaste
     */
    public function acceptPayment(Mage_Payment_Model_Info $payment)
    {
        parent::acceptPayment($payment);
        return $this->_reviewPayment($payment, DataCash_Dpg_Model_Code::PAYMENT_REVIEW_ACCEPT);
    }

    /**
     * Attempt to deny a pending payment
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return bool
     * @author Alistair Stead & Kristjan Heinaste
     */
    public function denyPayment(Mage_Payment_Model_Info $payment)
    {
        parent::denyPayment($payment);
        return $this->_reviewPayment($payment, DataCash_Dpg_Model_Code::PAYMENT_REVIEW_DENY);
    }
    
    /**
     * Generic payment review processor
     *
     * @author Kristjan Heinaste <kristjan@ontapgroup.com>
     */
    public function _reviewPayment(Mage_Payment_Model_Info $payment, $action)
    {
        //
        // XXX: When denied we leave it as is - no DC API support
        //
        if ($action == DataCash_Dpg_Model_Code::PAYMENT_REVIEW_DENY) {
            return true;
        }
    
        $this->_initApi();
        
        $this->_mapRequestDataToApi($payment, null);
        
        try {
            $this->_api->callReview($action);
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }
        // Process the response
        $response = $this->_api->getResponse();
        if ($response->isSuccessful()) {
            $responseMap = array(
              'cc_approval' => 'HistoricTxn/authcode',
              'cc_trans_id' => 'datacash_reference',
              'cc_status' => 'status',
              'cc_status_description' => 'reason',
            );
            foreach ($responseMap as $paymentKey => $responseKey) {
                if ($value = $response->getData($responseKey)) {
                    $payment->setData($paymentKey, $value);
                }
            }
            $responseTransactionInfoMap = array(
                'CardTxn' => 'CardTxn',
                'datacash_reference' => 'datacash_refrence',
                'mode' => 'mode',
                'reason' => 'reason',
                'status' => 'status',
                'time' => 'time',
                'authcode' => 'HistoricTxn/authcode',
            );
            foreach ($responseTransactionInfoMap as $paymentKey => $responseKey) {
                if ($value = $response->getData($responseKey)) {
                    $payment->setTransactionAdditionalInfo($paymentKey, $value);
                }
            }
            
            //
            // XXX: This section depends on $action
            // but because DC does not support deny action yet, we leave this as is.
            //
            $payment->setTransactionId($response->getDatacashReference())
                ->setShouldCloseParentTransaction(true)
                ->setIsTransactionClosed(true)
                ->setIsTransactionApproved(true);
        } else {
            Mage::throwException($response->getReason());
        }

        return true;        
    }    
    
    /**
     * Fetch transaction details info
     *
     * @param Mage_Payment_Model_Info $payment
     * @param string $transactionId
     * @return array
     * @author Alistair Stead
     */
    public function fetchTransactionInfo(Mage_Payment_Model_Info $payment, $transactionId)
    {
        // Add method body (Phase 2 requirement)
        parent::fetchTransactionInfo($payment, $transactionId);
        
        return $this;
    }
    
    /**
     * Return datacash config instance
     *
     * @return DataCash_Dpg_Model_Config
     */
    public function getConfig()
    {
        return Mage::getSingleton('dpg/config');
    }
    
    /**
     * Decrypt and return the Merchant password for the DataCash gateway
     *
     * @return string
     * @author Alistair Stead
     **/
    protected function _getApiPassword()
    {
        // Decrypt the marchant password in order to transmit it as part of the request
       return Mage::helper('core')->decrypt($this->getConfigData('merchant_password'));
    }
    
    /**
     * Return the Merchant ID for the DataCash gateway
     *
     * @return string
     * @author Alistair Stead
     **/
    protected function _getApiMerchantId()
    {
       return $this->getConfigData('merchant_id');
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
            $this->_api = Mage::getModel('dpg/api_direct');
        }
        $this->_api->setMerchantId($this->_getApiMerchantId());
        $this->_api->setMerchantPassword($this->_getApiPassword());
    }
    
    /**
     * Get an order number within different cart contexts
     *
     * @return int
     * @author Alistair Stead
     **/
    protected function _getOrderNumber($object = null)
    {
        if ($this->_isPlaceOrder($object)) {
            if ($object === NULL) {
                $object = $this->getInfoInstance()->getOrder();
            }
            return $object->getIncrementId();
        } else {
            if ($object === NULL) {
                $object = $this->getInfoInstance()->getQuote();
            }
            if (!$object->getReservedOrderId()) {
                $object->reserveOrderId();
            }
            return $object->getReservedOrderId();
        }
    }
    
    /**
     * Grand total getter
     *
     * @return string
     */
    protected function _getAmount()
    {
        $info = $this->getInfoInstance();
        if ($this->_isPlaceOrder()) {
            return (double)$info->getOrder()->getQuoteBaseGrandTotal();
        } else {
            return (double)$info->getQuote()->getBaseGrandTotal();
        }
    }

    /**
     * Currency code getter
     *
     * @return string
     */
    protected function _getCurrencyCode()
    {
        $info = $this->getInfoInstance();

        if ($this->_isPlaceOrder()) {
            return $info->getOrder()->getBaseCurrencyCode();
        } else {
            return $info->getQuote()->getBaseCurrencyCode();
        }
    }
    
    /**
     * Whether current operation is order placement
     *
     * @return bool
     */
    protected function _isPlaceOrder($object = null)
    {
        if ($object !== null) {
            if ($object instanceof Mage_Sales_Model_Quote) {
                return false;
            } elseif ($object instanceof Mage_Sales_Model_Order) {
                return true;
            }
        } else {
            $info = $this->getInfoInstance();
            if ($info instanceof Mage_Sales_Model_Quote_Payment) {
                return false;
            } elseif ($info instanceof Mage_Sales_Model_Order_Payment) {
                return true;
            }
        }
    }
    
    /**
     * Map data supplied by the request to the API
     * 
     * This method will set all possible data no exceptions will be thrown even if data is missing
     * If the data is not supplied the error will be thrown by the API
     *
     * @return void
     * @author Alistair Stead
     **/
    protected function _mapRequestDataToApi($payment, $amount)
    {
        $order = $payment->getOrder();
        $orderId = $this->_getOrderNumber();
        $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());

        $this->_api->setCustomerForename($customer->getFirstname());
        $this->_api->setCustomerSurname($customer->getLastname());
        $this->_api->setCustomerEmail($customer->getEmail());
        // Set the object properties required to make the API call
        $this->_api->setOrderNumber($orderId);
        $this->_api->setAmount($amount);
        $this->_api->setCurrency($order->getBaseCurrencyCode());
        $this->_api->setAddress($order->getShippingAddress());
        $this->_api->setBillingAddress($order->getBillingAddress());
        $this->_api->setShippingAddress($order->getShippingAddress());
        $this->_api->setShippingAmount($order->getShippingAmount());
        $this->_api->setShippingVatRate($order->getShippingVatPercent());
        if ($order->getCustomerIsGuest()) {
            $customerId = 'guest';
        } else {
            $customerId = $order->getCustomerId();
        }
        $this->_api->setCustomerCode($customerId);
        // add order line items
        $this->_api->setCartItems($order->getAllVisibleItems());
        // Add historic details
        if ($auth = $payment->getAuthorizationTransaction()) {
            $txnDetails = explode('-',$auth->getTxnId());
            $this->_api->setDataCashReference(array_shift($txnDetails));
        } else {
            $this->_api->setDataCashReference($payment->getCcTransId());
        }
        $this->_api->setAuthCode($payment->getCcApproval());
        $centinel = $this->getCentinelValidator();
        if ($centinel) {
            $state = $centinel->getValidationState();            
            if ($state) {
            	$bypass3DSecure = $state->getLookupBypass3dsecure();
            	$this->_api->setBypass3dsecure($bypass3DSecure);
            	if (!$bypass3DSecure) {
            		$this->_api->setMpiReference($state->getAuthenticateTransactionId());
            	}
            }
        }

    }

    /**
     * Protected method that will manage the transfer of data between the to objects
     *
     * @return void
     * @author Alistair Stead
     **/
    protected function _mapResponseToPayment($response, $payment)
    {
        $additionalInformationMap = array(
            'cc_avs_address_result' => 'CardTxn/Cv2Avs/address_result/0',
            'cc_avs_cv2_result' => 'CardTxn/Cv2Avs/cv2_result/0',
            'cc_avs_postcode_result' => 'CardTxn/Cv2Avs/postcode_result/0',
            'acs_url' => 'CardTxn/ThreeDSecure/acs_url',
            'pareq_message' => 'CardTxn/ThreeDSecure/pareq_message',
            'mode' => 'mode'
        );
        foreach ($additionalInformationMap as $paymentKey => $responseKey) {
            if ($value = $response->getData($responseKey)) {
                $payment->setAdditionalInformation($paymentKey, $value);
            }
        }
        $responseMap = array(
          'cc_avs_status' => 'CardTxn/Cv2Avs/cv2avs_status',
          'cc_approval' => 'CardTxn/authcode',
          'cc_trans_id' => 'datacash_reference',
          'cc_status' => 'status',
          'cc_status_description' => 'reason',
        );
        foreach ($responseMap as $paymentKey => $responseKey) {
            if ($value = $response->getData($responseKey)) {
                $payment->setData($paymentKey, $value);
            }
        }
        $responseTransactionInfoMap = array(
            'CardTxn' => 'CardTxn',
            'datacash_reference' => 'datacash_refrence',
            'mode' => 'mode',
            'reason' => 'reason',
            'status' => 'status',
            'time' => 'time',
            'authcode' => 'CardTxn/authcode',
        );
        foreach ($responseTransactionInfoMap as $paymentKey => $responseKey) {
            if ($value = $response->getData($responseKey)) {
                $payment->setTransactionAdditionalInfo($paymentKey, $value);
            }
        }
        if ($response->isFraud()) {
            $payment->setIsTransactionPending(true);
            $payment->setIsFraudDetected(true);
        }
        if ($response->isMarkedForReview()) {
            $payment->setIsTransactionPending(true);
        }
        $datacashReferenceMap = array(
            'datacash_reference',
            'QueryTxnResult/datacash_reference'
        );
        foreach ($datacashReferenceMap as $responseKey) {
            if ($value = $response->getData($responseKey)) {
                $payment->setTransactionId($value.'-'.time())
                    ->setShouldCloseParentTransaction(false)
                    ->setIsTransactionClosed(false);
            }
        }
        
        $session = $this->_getDataCashSession();
        $saveToken = $session->getData($this->getCode().'_save_token');
        if ($saveToken) {
            Mage::getModel('dpg/tokencard')->tokenizeResponse($payment, $response);
        }
    }

    protected function _getCentinelValidator($service)
    {
        $validator = Mage::getSingleton($service);
        $validator
            ->setIsModeStrict($this->getConfigData('centinel_is_mode_strict'))
            ->setCustomApiEndpointUrl($this->getConfigData('centinel_api_url'))
            ->setCode($this->getCode())
            ->setStore($this->getStore())
            ->setIsPlaceOrder($this->_isPlaceOrder());

        return $validator;
    }
    
    /**
     * Set the data cash session storage
     *
     * @param Varien_Object $value
     */
    public function setDataCashSession($value)
    {
        $this->_dataCashSession = $value;

        return $this;
    }

    /**
     * Get the DataCash session storage.
     *
     * @return Varien_Object
     */
    protected function _getDataCashSession()
    {
        if ($this->_dataCashSession === null) {
            $this->_dataCashSession = Mage::getSingleton('checkout/session');
        }
        return $this->_dataCashSession;
    }    
}

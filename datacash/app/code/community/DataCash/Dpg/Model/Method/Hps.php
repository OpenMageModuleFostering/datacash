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

class DataCash_Dpg_Model_Method_Hps
    extends DataCash_Dpg_Model_Method_Hosted_Abstract
{
    protected $_code  = 'datacash_hps';
    protected $_formBlockType = 'dpg/form_iframe';
    protected $_infoBlockType = 'dpg/info_hps';
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
    protected $_canUseForMultishipping = false;
    protected $_isInitializeNeeded = false;
    protected $_canFetchTransactionInfo = false;
    protected $_canReviewPayment = false;
    protected $_canCreateBillingAgreement = false;
    protected $_canManageRecurringProfiles = false;
    protected $_canCancelInvoice        = false;

    /**
     * This forces the method to be disabled at all times.
     * This payment method is DEPRICATED
     * the code is here only for backward-availability
     */
    public function isAvailable($quote = null) {
        return false;
    }

    /**
    * Initialize the data required by the API
    * @return void
    * @author Hilary Boyce
    **/
    protected function _initApi()
    {
        if (is_null($this->_api)) {
            $this->_api = Mage::getModel('dpg/api_hps');
        }
        $this->_api->setIsSandboxed($this->getConfig()->isMethodSandboxed($this->getCode()));
        $this->_api->setMerchantId($this->_getApiMerchantId());
        $this->_api->setMerchantPassword($this->_getApiPassword());
    }

    /**
    * initSession init an API request to DataCash
    * To create a unique session key for the iframe integration
    *
    * @param Mage_Sales_Model_Quote $payment
    * @param float $amount
    * @return void
    * @author Hilary Boyce
    * @author Alistair Stead
    */
    public function initSession(Mage_Sales_Model_Quote $quote)
    {
        $helper = Mage::helper('dpg');
        $quote->setReservedOrderId(null);
        $orderId = $this->_getOrderNumber($quote);
        $paymentAction = $this->getConfig()->getPaymentAction('datacash_hps');

        // The HPS method is using a two-step process, so only authorise the
        // transaction at the payment save step.
        $authMethod = 'pre';
        $returnUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . 'checkout/hosted/complete/';
        $request = Mage::app()->getRequest();
        $this->_initApi();
        $this->_api->setBaseUrl(Mage::getBaseUrl());
        // Set if CV2 data should be transmitted
        $this->_api->setIsUseCcv($this->getConfigData('useccv'));
        if($this->getConfigData('useccv_advanced')) {
            $this->_api->setIsUseExtendedCv2(true);
            $this->_api->setCv2ExtendedPolicy($this->_extendedPolicy());
        } else {
            $this->_api->setIsUseExtendedCv2(false);
        }
        $this->_api->setIsUse3DSecure($this->getConfigData('use3DSecure'));
        $this->_api->setAmount($quote->getBaseGrandTotal());
        $this->_api->setCurrency($quote->getBaseCurrencyCode());
        $this->_api->setAddress($quote->getShippingAddress());
        $this->_api->setBillingAddress($quote->getBillingAddress());
        $this->_api->setShippingAmount($quote->getShippingAmount());
        $this->_api->setShippingVatRate($quote->getShippingVatPercent());
        if ($quote->getCustomerIsGuest()) {
           $customerId = 'guest';
        } else {
           $customerId = $quote->getCustomer()->getIncrementId();
        }
        $this->_api->setCustomerCode($customerId);
        // Set the object properties required to make the API call
        $this->_api->setOrderNumber($orderId)
            ->setAuthMethod($authMethod)
            ->setReturnUrl($returnUrl)
            ->setExpiryUrl($returnUrl)
            ->setPurchaseDescription('Purchase from ' . Mage::app()->getStore()->getGroup()->getName())
            ->setPurchaseDateTime(date('Ymd H:i:s'))
            ->setBrowserData(
                    array(
                        'device_category' => $helper->getDeviceType($request->getHeader('USER_AGENT')),
                        'accept_headers' => $request->getHeader('ACCEPT'),
                        'user_agent' => $request->getHeader('USER_AGENT')
                    )
                )
            ->setPageSetId($this->getConfigData('page_set_id'));
        // Make the API call
        try {
            $this->_api->setUpHpsSession();
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
            $session = $this->_getDataCashSession();;
            $session->setData($this->_code . '_session', $datacashSession);
        } else {
            Mage::throwException($response->getReason());
        }
    }

    /**
    * requestAuthorisation
    * This does the equivalent of the payment authorize method
    * It is called by the HccController after the user has entered card details
    * on the datacash site
    * @param Mage_Sales_Model_Order_Payment $payment
    * @author hilary boyce
    */
    protected function _checkAuthorisation($payment)
    {
        $this->_initApi();

        $session = $this->_getDataCashSession();
        $datacashSession = $session->getData($this->_code . '_session');

        $reference = $datacashSession['DatacashReference'];
        // Set the object properties required to make the API call
        $this->_api->setDataCashCardReference($reference);

        try{
            $this->_api->callTransactionStatus();
            // Find the auth attempts array and make a second call for transaction status
            $authAttempts = $this->_api->getResponse()->getData('HpsTxn/AuthAttempts');
            if (is_array($authAttempts)) {
                foreach ($authAttempts as $attempt) {
                    if ($attempt['dc_response'] == 1) {
                        unset($this->_api);
                        $this->_initApi();
                        $this->_api->setDataCashCardReference($attempt['datacash_reference']);
                        $this->_api->callTransactionStatus();
                        continue;
                    }
                }
            }

        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }
        // Process the response
        $response = $this->_api->getResponse();
        if ($response->isSuccessful()) {
            $this->_mapResponseToPayment($response, $payment);
        } else {
            $message = sprintf('Datacash Authorisation Transaction: %s failed: %s ', $reference, $response->getReason());
            Mage::throwException($response->getReason());
        }
    } //requestAuthorisation

    /**
     * Authorise the payment
     *
     * @param Varien_Object $payment
     * @param string $amount
     * @return DataCash_Dpg_Model_Method_Api
     * @author Andy Thompson
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        $this->_checkAuthorisation($payment);
        parent::authorize($payment, $amount);

        return $this;
    }

    /**
     * Capture the payment
     *
     * @param Varien_Object $payment
     * @param string $amount
     * @return DataCash_Dpg_Model_Method_Api
     * @author Andy Thompson
     */
    public function capture(Varien_Object $payment, $amount)
    {
        parent::capture($payment, $amount);

        $order = $payment->getOrder();
        $authTransaction = $payment->getAuthorizationTransaction();
        $this->_initApi();
        $this->_mapRequestDataToApi($payment, $amount);

        // If the payment has already been authorized we need to only call fullfill
        if ($authTransaction && $payment->getAmountAuthorized() > 0 && $payment->getAmountAuthorized() < $amount) {
            throw new Exception('This card has not been authorized for this amount');
        } else {
            // if there was no previous Magento authorisation (i.e. capture not
            // at point-of-sale), check the state of the Hps authorisation
            if (!$authTransaction || $payment->getAmountAuthorized() == 0) {
                $this->_checkAuthorisation($payment);
                // reset api and data
                $this->_api = null;
                $this->_initApi();
        		$this->_mapRequestDataToApi($payment, $amount);
            }

            try {
                $this->_api->callFulfill();
            } catch (Exception $e) {
                Mage::throwException($e->getMessage());
            }
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

    /**
     * Protected method that will manage the transfer of data between the to objects
     *
     * @return void
     * @author Alistair Stead
     **/
    protected function _mapResponseToPayment($response, $payment)
    {
        $additionalInformationMap = array(
            'cc_avs_address_result' => 'QueryTxnResult/Cv2Avs/address_result/0',
            'cc_avs_cv2_result' => 'QueryTxnResult/Cv2Avs/cv2_result/0',
            'cc_avs_postcode_result' => 'QueryTxnResult/Cv2Avs/postcode_result/0',
            'acs_url' => 'QueryTxnResult/ThreeDSecure/acs_url',
            'pareq_message' => 'QueryTxnResult/ThreeDSecure/pareq_message',
            'mode' => 'mode',
            Mage_Centinel_Model_Service::CMPI_ECI => 'QueryTxnResult/ThreeDSecure/eci',
            Mage_Centinel_Model_Service::CMPI_CAVV => 'QueryTxnResult/ThreeDSecure/CAVV',
            Mage_Centinel_Model_Service::CMPI_XID => 'QueryTxnResult/ThreeDSecure/XID',
        );
        foreach ($additionalInformationMap as $paymentKey => $responseKey) {
            if ($value = $response->getData($responseKey)) {
                $payment->setAdditionalInformation($paymentKey, $value);
            }
        }


        //Determines the Enrolled status and adds it to the additional information object
        if ($cardholderRegistered = $response->getData('QueryTxnResult/ThreeDSecure/cardholder_registered')) {
            $this->_addEnrolledAndParesResult($cardholderRegistered, $payment);
        }

        $responseMap = array(
          // Hosted payment query
          'cc_avs_status' => 'QueryTxnResult/Cv2Avs/cv2avs_status',
          'cc_approval' => 'QueryTxnResult/authcode',
          'cc_trans_id' => 'QueryTxnResult/datacash_reference',
          'cc_status' => 'QueryTxnResult/status',
          'cc_status_description' => 'QueryTxnResult/reason'
        );
        foreach ($responseMap as $paymentKey => $responseKey) {
            if ($value = $response->getData($responseKey)) {
                $payment->setData($paymentKey, $value);
            }
        }
        $responseTransactionInfoMap = array(
            // Hosted payment query
            'datacash_reference' => 'QueryTxnResult/datacash_refrence',
            'reason' => 'QueryTxnResult/reason',
            'status' => 'QueryTxnResult/status',
            'authcode' => 'CardTxn/authcode'
        );
        foreach ($responseTransactionInfoMap as $paymentKey => $responseKey) {
            if ($value = $response->getData($responseKey)) {
                $payment->setTransactionAdditionalInfo($paymentKey, $value);
            }
        }
        if ($response->isFraud()) {
            $payment->setIsFraudDetected();
        }
        $datacashReferenceMap = array(
            'datacash_reference',
            'QueryTxnResult/datacash_reference'
        );
        foreach ($datacashReferenceMap as $responseKey) {
            if ($value = $response->getData($responseKey)) {
                $payment->setTransactionId($value)
                    ->setShouldCloseParentTransaction(false)
                    ->setIsTransactionClosed(false);
            }
        }

    }

    /**
     * Determines the Enrolled status and adds it to the additional information object
     *
     * @param type $cardholderRegistered The value of the QueryTxnResult/ThreeDSecure/cardholder_registered node in the
     * response object
     *
     * @param type $payment
     * @return DataCash_Dpg_Model_Method_Hps
     * @author Norbert Nagy <norbert.nagy@sessiondigital.com>
     */
    private function _addEnrolledAndParesResult($cardholderRegistered, $payment)
    {
        switch ($cardholderRegistered) {
            case 'yes':
                $enrolled = 'Y';
                $pares = 'Y';
                break;
            case 'attempted':
                $enrolled = 'Y';
                $pares = 'A';
                break;
            case 'no':
                $enrolled = 'N';
                $pares = 'N';
                break;
            case 'tx_status_u':
                $enrolled = 'U';
                $pares = 'U';
                break;
            default:
                $enrolled = $value;
                $pares = $value;
        }

        $payment->setAdditionalInformation(Mage_Centinel_Model_Service::CMPI_ENROLLED, $enrolled);
        $payment->setAdditionalInformation(Mage_Centinel_Model_Service::CMPI_PARES, $pares);

        return $this;
    }
}
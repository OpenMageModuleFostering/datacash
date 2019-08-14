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

class DataCash_Dpg_Model_Method_Api extends DataCash_Dpg_Model_Method_Abstract
{
    protected $_code  = 'datacash_api';
    protected $_formBlockType = 'dpg/form_api';
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

    public function getVerificationRegEx()
    {
        $verificationExpList = parent::getVerificationRegEx();
        $verificationExpList['DIN'] = '/^[0-9]{3}$/'; //Diners Club
        return $verificationExpList;
    }

    public function assignData($data)
    {
        parent::assignData($data);
        $info = $this->getInfoInstance();
        $info->setAdditionalData($data->getCcTransId());
        $info->setCcTransId($data->getCcTransId());
        $info->setData('additional_information', array(
            'tokencard' => $data->getCcTokencard(),
            'remember_card' => $data->getCcRememberCard() == "1",
        ));
        
        return $this;
    }
    
    // TODO: refactor to generic
    public function getTxnData($txn_id)
    {
        $this->_api = Mage::getModel('dpg/api_direct');
        
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

    protected $_t3mRecommendation           = null;

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
        $order = $payment->getOrder();
        try {
            if ($this->hasFraudScreening()) {
                $this->_api->setUseFraudScreening(true);
                $this->_api->setFraudScreeningPolicy($this->_fraudPolicy());
            }        
            $this->_api->callPre();
            $t3mResponse = $this->_api->getResponse()->t3MToMappedArray(array(
                't3m_score' => 'score',
                't3m_recommendation' => 'recommendation'));
            Mage::dispatchEvent('datacash_dpg_t3m_response', $t3mResponse);
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }

        // Process the response
        $response = $this->_api->getResponse();
        if ($response->isSuccessful() || $response->isMarkedForReview() || $response->isFraud()) {
            // Map data to the payment
            $this->_mapResponseToPayment($response, $payment);
        } else {
            $message = Mage::helper('dpg')->getUserFriendlyStatus($response->getStatus());
            throw new Mage_Payment_Model_Info_Exception($message ? $message : $response->getReason());
        }

        if ($this->getConfig()->getIsAllowedT3m()) {
            $t3mPaymentInfo = array('Release', 'Hold', 'Reject', 9 => 'Under Investigation');
            $t3mPaymentResponse = $t3mResponse;
            $t3mPaymentResponse['t3m_recommendation'] = $t3mPaymentInfo[$t3mResponse['t3m_recommendation']];
            $this->_t3mRecommendation = $t3mPaymentResponse['t3m_recommendation'];
            $oldInfo = $payment->getAdditionalInformation();
            $newInfo = array_merge($oldInfo, $t3mPaymentResponse);
            $payment->setAdditionalInformation($newInfo);

            if ($this->_t3mRecommendation != 'Release') {
                $payment->setIsTransactionPending(true);
                if ('Reject' == $this->_t3mRecommendation) {
                    $payment->setIsFraudDetected(true);
                }
            }
        }


        return $this;
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
        $authTransaction = $payment->getAuthorizationTransaction();
        $fulfill = (bool) $authTransaction;
           if (!$fulfill && $payment->getId()) {
               $collection = Mage::getModel('sales/order_payment_transaction')->getCollection()
                   ->setOrderFilter($payment->getOrder())
                   ->addPaymentIdFilter($payment->getId())
                   ->addTxnTypeFilter(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
               $fulfill = $collection->count();
           }

        if ($this->getConfig()->getIsAllowedT3m()) {
            // Must preauth card first (if not already done so) to make third
            // man realtime check.
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
        parent::capture($payment, $amount);
        $this->_initApi();
        $this->_mapRequestDataToApi($payment, $amount);

        // If the payment has already been authorized we need to only call fulfill
        if ($fulfill && $payment->getAmountAuthorized() > 0 && $payment->getAmountAuthorized() >= $amount) {
            try {
                $this->_api->callFulfill();
            } catch (Exception $e) {
                Mage::throwException($e->getMessage());
            }
        } else if ($fulfill && $payment->getAmountAuthorized() > 0 && $payment->getAmountAuthorized() < $amount) {
            throw new Exception('This card has not been authorized for this amount');
        } else {
            try {
                if ($this->hasFraudScreening()) {
                    $this->_api->setUseFraudScreening(true);
                    $this->_api->setFraudScreeningPolicy($this->_fraudPolicy());
                }            
                $this->_api->callAuth();
            } catch (Exception $e) {
                Mage::throwException($e->getMessage());
            }
        }

        // Process the response
        $response = $this->_api->getResponse();
        if ($response->isSuccessful() || $response->isMarkedForReview() || $response->isFraud()) {
            $this->_mapResponseToPayment($response, $payment);
        } else {
            $message = Mage::helper('dpg')->getUserFriendlyStatus($response->getStatus());
            throw new Mage_Payment_Model_Info_Exception($message ? $message : $response->getReason());
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
        
        // pass the validation when token is used
        if ($this->getConfig()->getIsAllowedTokenizer($this->getCode())) {
            $info = $this->getInfoInstance();
            $additionalInfo = $info->getAdditionalInformation();
            if (isset($additionalInfo['tokencard']) && $additionalInfo['tokencard'] != "0") {
                return $this;
            }
        }

        $info = $this->getInfoInstance();
        $errorMsg = false;
        $availableTypes = explode(',',$this->getConfigData('cctypes'));

        $ccNumber = $info->getCcNumber();

        // remove credit card number delimiters such as "-" and space
        $ccNumber = preg_replace('/[\-\s]+/', '', $ccNumber);
        $info->setCcNumber($ccNumber);

        $ccType = '';

        if (in_array($info->getCcType(), $availableTypes)){
            if ($this->validateCcNum($ccNumber)
                // Other credit card type number validation
                || ($this->OtherCcType($info->getCcType()) && $this->validateCcNumOther($ccNumber))) {

                $ccType = 'OT';
                $ccTypeRegExpList = array(
                    //Solo, Switch or Maestro. International safe
                    /*
                    // Maestro / Solo
                    'SS'  => '/^((6759[0-9]{12})|(6334|6767[0-9]{12})|(6334|6767[0-9]{14,15})'
                               . '|(5018|5020|5038|6304|6759|6761|6763[0-9]{12,19})|(49[013][1356][0-9]{12})'
                               . '|(633[34][0-9]{12})|(633110[0-9]{10})|(564182[0-9]{10}))([0-9]{2,3})?$/',
                    */
                    // Solo only
                    'SO' => '/(^(6334)[5-9](\d{11}$|\d{13,14}$))|(^(6767)(\d{12}$|\d{14,15}$))/',
                    'SM' => '/(^(5[0678])\d{11,18}$)|(^(6[^05])\d{11,18}$)|(^(601)[^1]\d{9,16}$)|(^(6011)\d{9,11}$)'
                            . '|(^(6011)\d{13,16}$)|(^(65)\d{11,13}$)|(^(65)\d{15,18}$)'
                            . '|(^(49030)[2-9](\d{10}$|\d{12,13}$))|(^(49033)[5-9](\d{10}$|\d{12,13}$))'
                            . '|(^(49110)[1-2](\d{10}$|\d{12,13}$))|(^(49117)[4-9](\d{10}$|\d{12,13}$))'
                            . '|(^(49118)[0-2](\d{10}$|\d{12,13}$))|(^(4936)(\d{12}$|\d{14,15}$))/',
                    // Visa
                    'VI'  => '/^4[0-9]{12}([0-9]{3})?$/',
                    // Master Card
                    'MC'  => '/^5[1-5][0-9]{14}$/',
                    // American Express
                    'AE'  => '/^3[47][0-9]{13}$/',
                    // Discovery
                    'DI'  => '/^6011[0-9]{12}$/',
                    // JCB
                    'JCB' => '/^(3[0-9]{15}|(2131|1800)[0-9]{11})$/',
                    'DIN' => '/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/', //Diners Club
                );

                foreach ($ccTypeRegExpList as $ccTypeMatch=>$ccTypeRegExp) {
                    if (preg_match($ccTypeRegExp, $ccNumber)) {
                        $ccType = $ccTypeMatch;
                        break;
                    }
                }

                if (!$this->OtherCcType($info->getCcType()) && $ccType!=$info->getCcType()) {
                    $errorMsg = Mage::helper('payment')->__('Credit card number mismatch with credit card type.');
                }
            }
            else {
                $errorMsg = Mage::helper('payment')->__('Invalid Credit Card Number');
            }

        }
        else {
            $errorMsg = Mage::helper('payment')->__('Credit card type is not allowed for this payment method.');
        }

        //validate credit card verification number
        if ($errorMsg === false && $this->hasVerification()) {
            $verifcationRegEx = $this->getVerificationRegEx();
            $regExp = isset($verifcationRegEx[$info->getCcType()]) ? $verifcationRegEx[$info->getCcType()] : '';
            if (!$info->getCcCid() || !$regExp || !preg_match($regExp ,$info->getCcCid())){
                $errorMsg = Mage::helper('payment')->__('Please enter a valid credit card verification number.');
            }
        }

        if ($ccType != 'SS' && !$this->_validateExpDate($info->getCcExpYear(), $info->getCcExpMonth())) {
            $errorMsg = Mage::helper('payment')->__('Incorrect credit card expiration date.');
        }

        if($errorMsg){
            Mage::throwException($errorMsg);
        }

        //This must be after all validation conditions
        if ($this->getIsCentinelValidationEnabled()) {
            $this->getCentinelValidator()->validate($this->getCentinelValidationData());
        }
        
        return $this;
    }

    /**
     * Overload the parent method getCanCapturePartial
     *
     * If the basket items have been sent you must capture the full amount
     *
     * @return bool
     * @author Alistair Stead
     **/
    public function getCanCapturePartial()
    {
        if (parent::getCanCapturePartial() && !$this->getConfigData('line_items_enabled')) {
            return true;
        }

        return false;
    }

    /**
     * Overload the parent method getCanRefundInvoicePartial
     *
     * If the basket items have been sent you must refund the full amount
     *
     * @return bool
     * @author Alistair Stead
     **/
    public function getCanRefundInvoicePartial()
    {
        return true;
        if (parent::getCanRefundInvoicePartial() && !$this->getConfigData('line_items_enabled')) {
            return true;
        }

        return false;
    }


    /**
     * Format the supplied dates to be sent to the API
     *
     * @return string 00/00
     * @author Alistair Stead
     **/
    protected function _formatDate($month, $year)
    {
        return sprintf(
            '%02d/%02d',
            substr($month, -2, 2),
            substr($year, -2, 2)
        );
    }

    /**
     * undocumented function
     *
     * @return void
     * @author Alistair Stead
     **/
    protected function _initApi()
    {
		parent::_initApi();
        // Set the sandboxed state
        $this->_api->setIsSandboxed($this->getConfig()->isMethodSandboxed($this->getCode()));
        $this->_api->setEndpoint($this->getConfig()->getEndpoint($this->getCode()));

        // Set if CV2 data should be transmitted
        $this->_api->setIsUseCcv($this->getConfig()->isUseCcv($this->getCode()));
		// If extended policy required, set it up.
        if ($this->getConfig()->isUseAdvancedCcv($this->getCode())){
            $this->_api->setIsUseExtendedCv2(true);
            $this->_api->setCv2ExtendedPolicy($this->_extendedPolicy());
        }
        $this->_api->setIsUse3d($this->getIsCentinelValidationEnabled());
        // Set if line items should be transmitted
        $this->_api->setIsLineItemsEnabled($this->getConfig()->isLineItemsEnabled($this->getCode()));
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
        parent::_mapRequestDataToApi($payment, $amount);
        $order = $payment->getOrder();
        $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
        
        // When using tokens, get the token object
        $tokencard = null;
        $additionalInfo = $payment->getMethodInstance()->getInfoInstance()->getData('additional_information');
        $tokencardId = $additionalInfo['tokencard'];
        if ($customer && $tokencardId && is_numeric($tokencardId)) {
            $tokencard = Mage::getModel('dpg/tokencard')
                ->getCollection()
                ->addCustomerFilter($customer)
                ->addIdFilter($tokencardId)
                ->getFirstItem();
        }        
        
        // Save the UI state to session for later use
        $session = $this->_getDataCashSession();
        $session->setData($this->_code . '_save_token', $additionalInfo['remember_card']);
        
        // Set the object properties required to make the API call
        $this->_api
            ->setCreditCardNumber($payment->getCcNumber())
            ->setCreditCardExpirationDate(
                $this->_formatDate(
                    $payment->getCcExpMonth(),
                    $payment->getCcExpYear()
                )
            )
            ->setCreditCardCvv2($payment->getCcCid())
            ->setMaestroSoloIssueNumber($payment->getCcSsIssue())
            ->setToken($tokencard);
            
        // Add the additional SM card details
        if ($payment->getCcSsStartMonth() && $payment->getCcSsStartYear()) {
            $this->_api->setMaestroSoloIssueDate(
                $this->_formatDate(
                    $payment->getCcSsStartMonth(),
                    $payment->getCcSsStartYear()
                )
            );
        }

        if ($this->getConfig()->getIsAllowedT3m()) {
            $this->_api->setForename($customer->getFirstName());
            $this->_api->setSurname($customer->getLastName());
            $this->_api->setCustomerEmail($customer->getEmail());
            $remoteIp = $order->getRemoteIp()? $order->getRemoteIp(): $order->getQuote()->getRemoteIp();
            $this->_api->setRemoteIp($remoteIp);
            $this->_api->setOrderItems($order->getAllItems());

            $orders = Mage::getModel('sales/order')
                ->getCollection()
                ->addAttributeToSelect('*')
                ->addFieldToFilter('customer_id', $customer->getId())
                ->setOrder('created_at', 'asc');
            $previousOrderTotal = 0;
            foreach ($orders as $order) {
                $previousOrderTotal += $order->getData('grand_total');
            }

            $this->_api->setPreviousOrders(array(
                'count' => count($orders->getData()),
                'total' => $previousOrderTotal,
                'first' => $orders->getSize() > 0?
                    substr($orders->getFirstItem()->getCreatedAt(), 0, 10) : NULL
            ));
        }
    }

    /**
     * Instantiate centinel validator model
     *
     * @return Mage_Centinel_Model_Service
     */
    public function getCentinelValidator()
    {
        return $this->_getCentinelValidator('dpg/service_direct');
    }

    /**
     * Whether centinel service is enabled
     *
     * @return bool
     */
    public function getIsCentinelValidationEnabled()
    {
        return false !== Mage::getConfig()->getNode('modules/Mage_Centinel') && 1 == $this->getConfigData('centinel');
    }
}

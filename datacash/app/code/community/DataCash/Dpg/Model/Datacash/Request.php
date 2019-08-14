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

class DataCash_Dpg_Model_Datacash_Request extends Varien_Object
{
    const XML_VERSION = '2';

    /**
     * Internal member variable that will hold the XML request
     *
     * @var Varien_Simplexml_Element
     **/
    protected $_request;

    /**
     * Varien construct
     *
     * @return DataCash_Dpg_Model_DataCash_Request
     * @author Alistair Stead
     **/
    public function _construct(Varien_Simplexml_Element $xmlElement = null)
    {
        if (is_null($xmlElement)) {
            $xmlElement = new Varien_Simplexml_Element('<Request></Request>');
            $xmlElement->addAttribute('version', self::XML_VERSION);
        }
        $this->_request = $xmlElement;

        return $this;
    }
    
    public function addToken($token)
    {
        if (!$this->getRequest()->Transaction) {
            throw new UnexpectedValueException('Parent node does not exist Transcation');
        }
        if (!$this->getRequest()->Transaction->HpsTxn) {
            throw new UnexpectedValueException('Parent node does not exist HpsTxn');
        }
        
        $txn = $this->getRequest()->Transaction->HpsTxn;
        $txn->addChild("cvv_only", "1");
        
        $card = $txn->addChild("Card");
        $pan = $card->addChild("pan", $token);
        $pan->addAttribute('type', "hps_token");
        
        return $this;
    }
    
    public function setupRsg($serviceMode="1")
    {
        if (!$this->getRequest()->Transaction) {
            throw new UnexpectedValueException('Parent node does not exist Transaction');
        }
        if (!$this->getRequest()->Transaction->TxnDetails) {
            throw new UnexpectedValueException('Parent node does not exist Transaction->TxnDetails');
        }
        
        $rsg = $this->getRequest()->Transaction->TxnDetails->addChild('Risk');
        $action = $rsg->addChild("Action");
        $action->addAttribute("service", $serviceMode);
        $mercantConfiguration = $action->addChild('MerchantConfiguration');
        $customerDetails = $action->addChild("CustomerDetails");
        $orderDetails = $customerDetails->addChild("OrderDetails");
    }
    
    public function setRsgItems($data)
    {
        $orderDetails = $this->getRequest()->Transaction->TxnDetails->Risk->Action->CustomerDetails->OrderDetails;
        $lineItems = $orderDetails->addChild("LineItems");
        foreach($data as $item_data) {
            if (sizeof(array_keys($item_data)) == 0) {
                continue;
            }
            $item = $lineItems->addChild("Item");
            foreach($item_data as $key => $value) {
                $item->addChild($key, $value);
            }
        }
    }
    
    public function setRsgOrder($data)
    {
        $orderDetails = $this->getRequest()->Transaction->TxnDetails->Risk->Action->CustomerDetails->OrderDetails;
        foreach($data as $key => $value) {
            $orderDetails->addChild($key, $value);
        }
    }
    
    public function setRsgBilling($data)
    {
        $orderDetails = $this->getRequest()->Transaction->TxnDetails->Risk->Action->CustomerDetails->OrderDetails;
        $billingDetails = $orderDetails->addChild("BillingDetails");
        
        foreach($data as $key => $value) {
            $billingDetails->addChild($key, $value);
        }
    }
    
    public function setRsgShipping($data)
    {
        $customerDetails = $this->getRequest()->Transaction->TxnDetails->Risk->Action->CustomerDetails;
        $shippingDetails = $customerDetails->addChild("ShippingDetails");
        
        foreach($data as $key => $value) {
            $shippingDetails->addChild($key, $value);
        }        
    }
    
    public function setRsgCustomer($data)
    {
        $customerDetails = $this->getRequest()->Transaction->TxnDetails->Risk->Action->CustomerDetails;
        
        $riskDetails = $customerDetails->addChild("RiskDetails");
        foreach(array('ip_address', 'user_id', 'email_address') as $key) {
            if (isset($data[$key])) {
                $riskDetails->addChild($key, $data[$key]);
            }
        }

        $personalDetails = $customerDetails->addChild("PersonalDetails");
        foreach(array('first_name', 'surname', 'telephone') as $key) {
            if (isset($data[$key])) {
                $personalDetails->addChild($key, $data[$key]);
            }
        }
        
        $addressDetails = $customerDetails->addChild("AddressDetails");
        foreach(array('address_line1', 'address_line2', 'city', 'state_province', 'country', 'zip_code') as $key) {
            if (isset($data[$key])) {
                $addressDetails->addChild($key, $data[$key]);
            }
        }
    }
    
    public function setRsgPayment($data)
    {
        $customerDetails = $this->getRequest()->Transaction->TxnDetails->Risk->Action->CustomerDetails;
        $paymentDetails = $customerDetails->addChild("PaymentDetails");
        
        foreach($data as $key => $value) {
            $paymentDetails->addChild($key, $value);
        }        
    }

    /**
     * Add the Authentication section of the request
     *
     * @param string $merchantId The merchant id supplied by DataCash (Required)
     * @param string $password The merchant password supplied by DataCash (Required)
     * @return DataCash_Dpg_Model_DataCash_Request
     * @author Alistair Stead
     **/
    public function addAuthentication($merchantId, $password)
    {
        $auth = $this->getRequest()->addChild('Authentication');
        $auth->addChild('client', $merchantId);
        $auth->addChild('password', $password);

        return $this;
    }

    /**
     * Add the Transaction section to the request
     *
     * @return DataCash_Dpg_Model_DataCash_Request
     * @author Alistair Stead
     **/
    public function addTransaction()
    {
        $this->getRequest()->addChild('Transaction');

        return $this;
    }

    /**
     * Add the CardTxn section to the request
     *
     * @param string $method
     * @param string $authcode
     * @param string $type Whether an api or hps request
     * @return DataCash_Dpg_Model_DataCash_Request
     * @author Alistair Stead/Hilary Boyce
     **/
    public function addCardTxn($method, $authcode = null, $type = 'api')
    {
        if (!$this->getRequest()->Transaction) {
            throw new UnexpectedValueException('Parent node does not exist');
        }
        $this->_validateCardMethod($method, $type);
        $cardTxn = $this->getRequest()->Transaction->addChild('CardTxn');
        $cardTxn->addChild('method', $method);
        if (!is_null($authcode)) {
            $cardTxn->addChild('authcode', $authcode);
        }

        return $this;
    }

    /**
     * Add the MpiTxn section to the Transaction
     *
     * @param string $method
     * @return DataCash_Dpg_Model_DataCash_Request
     * @author Alistair Stead
     **/
    public function addMpiTxn($method = 'mpi')
    {
        if (!$this->getRequest()->Transaction) {
            throw new UnexpectedValueException('Parent node does not exist');
        }
        $cardTxn = $this->getRequest()->Transaction->addChild('MpiTxn');
        $cardTxn->addChild('method', $method);

        return $this;
    }

    /**
     * Add the Card section to the MpiTxn
     *
     * @param string $number The card number (Required)
     * @param string $expiryDate The card expiry date (Required)
     * @param string $startDate The card start date (Mandatory)
     * @param string $issueNumber The card issue number (Mandatory)
     * @return DataCash_Dpg_Model_DataCash_Request
     * @author Alistair Stead
     **/
    public function addMpiCard($number, $expiryDate, $startDate = null, $issueNumber = null)
    {
        if (!$this->getRequest()->Transaction->MpiTxn) {
            throw new UnexpectedValueException('Parent node does not exist');
        }
        $card = $this->getRequest()->Transaction->MpiTxn->addChild('Card');
        // Required values
        $card->addChild('pan', $number);
        $card->addChild('expirydate', $expiryDate);
        // Mandatory values for some cards
        if (!is_null($startDate)) {
            $card->addChild('startdate', $startDate);
        }
        if (!is_null($issueNumber)) {
            $card->addChild('issuenumber', $issueNumber);
        }

        return $this;
    }

    /**
     * Add historic card details (or pre-registered card ref)
     * for MPI authentication
     *
     * @return DataCash_Dpg_Model_DataCash_Request
     * @author Alistair Stead
     **/
    public function addCardDetails($reference, $type = 'from_mpi', $txn = 'CardTxn')
    {
        if (!$this->getRequest()->Transaction->$txn) {
            throw new UnexpectedValueException('Parent node does not exist');
        }
        $cardDetails = $this->getRequest()->Transaction->$txn->addChild('card_details', $reference);
        $cardDetails->addAttribute('type', $type);

        return $this;
    }

    /**
     * Add a historic transaction node to the request
     *
     * @return DataCash_Dpg_Model_DataCash_Request
     * @author Alistair Stead
     **/
    public function addHistoricTxn($method, $reference, $authcode = null, $type = 'api', $paresMessage = null)
    {
        if (!$this->getRequest()->Transaction) {
            throw new UnexpectedValueException('Parent node does not exist');
        }
        $this->_validateHistoricMethod($method, $type, $type);
        $historicTxn = $this->getRequest()->Transaction->addChild('HistoricTxn');
        $historicTxn->addChild('method', $method);
        $historicTxn->addChild('reference', $reference);
        if (!is_null($authcode)) {
            $historicTxn->addChild('authcode', $authcode);
        }
        if (!is_null($paresMessage)) {
            $historicTxn->addChild('pares_message', $paresMessage);
        }

        return $this;
    }

    /**
     * Add the TxnDetails section to the request
     *
     * @param string $orderNumber The Magento order number (Required)
     * @param string $amount The value to be charged (Required)
     * @param string $currency The currency code (Mandatory)
     * @return DataCash_Dpg_Model_DataCash_Request
     * @author Alistair Stead
     **/
    public function addTxnDetails($orderNumber, $amount, $currency = null, $captureMethod = null)
    {
        if (!$this->getRequest()->Transaction) {
            throw new UnexpectedValueException('Parent node does not exist');
        }
        $this->_validateOrderNumber($orderNumber);
        $txnDetails = $this->getRequest()->Transaction->addChild('TxnDetails');
        $txnDetails->addChild('merchantreference', $orderNumber);
        $amount = $txnDetails->addChild('amount', $amount);
        if (!is_null($currency)) {
            $this->_validateCurrency($currency);
            $amount->addAttribute('currency', $currency);
        }

        if (!is_null($captureMethod)) {
             $txnDetails->addChild('capturemethod', $captureMethod);
        }

        return $this;
    }

    /**
     * Add line LineItemDetails node to the request
     *
     * @return DataCash_Dpg_Model_DataCash_Request
     * @author Alistair Stead
     **/
    public function addLineItemDetail($customerCode, $transactionVat, $transactionVatStatus = 1, $merchantVatNumber = null)
    {
        if (!$this->getRequest()->Transaction->TxnDetails) {
            throw new UnexpectedValueException('Parent node does not exist');
        }
        $lineItemDetail = $this->getRequest()->Transaction->TxnDetails->addChild('LineItemDetail');
        $lineItemDetail->addChild('customercode', $customerCode);
        $lineItemDetail->addChild('transactionVAT', $transactionVat);
        $lineItemDetail->addChild('transactionVATstatus', $transactionVatStatus);
        if (!is_null($merchantVatNumber)) {
            $lineItemDetail->addChild('merchantVATnumber', $merchantVatNumber);
        }

        return $this;
    }

    /**
     * Add backet items to the request sent to DataCash
     *
     * @return DataCash_Dpg_Model_DataCash_Request
     * @author Alistair Stead
     **/
    public function addItem($description, $unitMeasure, $unitCost, $vatRate, $quantity, $totalAmount,
        $productCode = null, $discountAmount = null, $commodityCode = null)
    {
        if (!$this->getRequest()->Transaction->TxnDetails->LineItemDetail) {
            throw new UnexpectedValueException('Parent node does not exist');
        }
        if (!$this->getRequest()->Transaction->TxnDetails->LineItemDetail->Items) {
            $items = $this->getRequest()->Transaction->TxnDetails->LineItemDetail->addChild('Items');
        } else {
            $items = $this->getRequest()->Transaction->TxnDetails->LineItemDetail->Items;
        }
        $item = $items->addChild('Item');
        $item->addChild('description', $description);
        $item->addChild('unitmeasure', $unitMeasure);
        $item->addChild('unitcost', $unitCost);
        $item->addChild('vatrate', $vatRate);
        $item->addChild('quantity', $quantity);
        $item->addChild('totalamount', $totalAmount);
        if (!is_null($productCode)) {
            $item->addChild('product_code', $productCode);
        }
        if (!is_null($commodityCode)) {
            $item->addChild('commoditycode', $commodityCode);
        }
        if (!is_null($discountAmount)) {
            $item->addChild('discountamount', $discountAmount);
        }

        return $this;
    }

    /**
     * Add order information to the request
     *
     * @return void
     * @author Alistair Stead
     **/
    public function addOrder()
    {
        if (!$this->getRequest()->Transaction->TxnDetails) {
            throw new UnexpectedValueException('Parent node does not exist');
        }
        $order = $this->getRequest()->Transaction->TxnDetails->addChild('Order');

        return $this;
    }

    /**
     * Add customer information to the request
     *
     * @return DataCash_Dpg_Model_DataCash_Request
     * @author Alistair Stead
     **/
    public function addCustomer($email, $forename, $surname, $ip)
    {
        if (!$this->getRequest()->Transaction->TxnDetails->Order) {
            throw new UnexpectedValueException('Parent node does not exist');
        }
        $customer = $this->getRequest()->Transaction->TxnDetails->Order->addChild('Customer');
        $customer->addChild('email', $email);
        $customer->addChild('forename', $forename);
        $customer->addChild('surname', $surname);
        $customer->addChild('ip_address', $ip);

        return $this;
    }

    /**
     * Add address to the request
     *
     * @return DataCash_Dpg_Model_DataCash_Request
     * @author Alistair Stead
     **/
    public function addAddress($streetAddress, $city, $country, $postcode)
    {
        if (!$this->getRequest()->Transaction->TxnDetails->Order->Customer) {
            throw new UnexpectedValueException('Parent node does not exist');
        }
        $address = $this->getRequest()->Transaction->TxnDetails->Order->Customer->addChild('Address');
        $address->addChild('streetaddress', $streetAddress);
        $address->addChild('city', $city);
        $address->addChild('city', $postcode);
        $address->addChild('country', $country);

        return $this;
    }

    /**
     * Add billing address information to the request
     *
     * @return DataCash_Dpg_Model_DataCash_Request
     * @author Alistair Stead
     **/
    public function addBillingAddress($streetAddress, $city, $country, $postcode)
    {
        if (!$this->getRequest()->Transaction->TxnDetails->Order) {
            throw new UnexpectedValueException('Parent node does not exist');
        }
        $billingAddress = $this->getRequest()->Transaction->TxnDetails->Order->addChild('BillingAddress');
        $billingAddress->addChild('streetaddress', $streetAddress);
        $billingAddress->addChild('city', $city);
        $billingAddress->addChild('city', $postcode);
        $billingAddress->addChild('country', $country);

        return $this;
    }

    /**
     * Add Shipping information to the Request
     *
     * @return DataCash_Dpg_Model_DataCash_Request
     * @author Alistair Stead
     **/
    public function addShipping($shippingAmount, $shippingVatRate)
    {
        if (!$this->getRequest()->Transaction->TxnDetails->LineItemDetail) {
            throw new UnexpectedValueException('Parent node does not exist');
        }
        $shipping = $this->getRequest()->Transaction->TxnDetails->LineItemDetail->addChild('Shipping');
        $shipping->addChild('shippingamount', $shippingAmount);
        $shipping->addChild('shippingVATrate', $shippingVatRate);

        return $this;
    }

    /**
     * Add the Card section to the request
     *
     * @param string $number The card number (Required)
     * @param string $expiryDate The card expiry date (Required)
     * @param string $startDate The card start date (Mandatory)
     * @param string $issueNumber The card issue number (Mandatory)
     * @return DataCash_Dpg_Model_DataCash_Request
     * @author Alistair Stead
     **/
    public function addCard($number, $expiryDate, $startDate = null, $issueNumber = null)
    {
        if (!$this->getRequest()->Transaction->CardTxn) {
            throw new UnexpectedValueException('Parent node does not exist');
        }
        $card = $this->getRequest()->Transaction->CardTxn->addChild('Card');
        // Required values
        $card->addChild('pan', $number);
        $card->addChild('expirydate', $expiryDate);
        // Mandatory values for some cards
        if (!is_null($startDate)) {
            $card->addChild('startdate', $startDate);
        }
        if (!is_null($issueNumber)) {
            $card->addChild('issuenumber', $issueNumber);
        }

        return $this;
    }
    
    /**
     * Basically the same as addCard just instead of card add a token
     */
    public function addTokencard($token, $expiryDate, $startDate = null, $issueNumber = null)
    {
        if (!$this->getRequest()->Transaction->CardTxn) {
            throw new UnexpectedValueException('Parent node does not exist');
        }
        $card = $this->getRequest()->Transaction->CardTxn->addChild('Card');

        // Required values
        $pan = $card->addChild('pan', $token);
        $pan->addAttribute('type', 'token');
        
        $card->addChild('expirydate', $expiryDate);
        
        // Mandatory values for some cards
        if (!is_null($startDate)) {
            $card->addChild('startdate', $startDate);
        }
        if (!is_null($issueNumber)) {
            $card->addChild('issuenumber', $issueNumber);
        }

        return $this;        
    }

    /**
     *  Add HPS details to the request
     *
     *  @param integer $pageSetId  The id of the hosted page set on datacash (Required)
     *  @param string $method Method requested from Datacash HCC, default 'setup' (Required)
     *  @param string $returnUrl Full url for return page on successful completion(Optional)
     *  @param string $expiryUrl Full url for return on error (Optional)
     *  @param string $type hcc|hps
     *
     * @return DataCash_Dpg_Model_DataCash_Request
     * @author Hilary Boyce
     */
    public function addHpsTxn($pageSetId, $method = 'setup', $returnUrl = '',$expiryUrl = '', $type='hcc'){
        if (!$this->getRequest()->Transaction) {
            throw new UnexpectedValueException('Parent node does not exist');
        }
        $this->_validateHPSMethod($method, $type);
        if (!$pageSetId){
            throw new InvalidArgumentException("Invalid page set id supplied {$pageSetId}");
        }

        $hpsTxn = $this->getRequest()->Transaction->addChild('HpsTxn');
        $hpsTxn->addChild('method', $method);
        $hpsTxn->addChild('page_set_id', $pageSetId);
        if($returnUrl){
            $this->_validateUrl($returnUrl);
            $hpsTxn->addChild('return_url', $returnUrl);
        }
        if($expiryUrl){
            $this->_validateUrl($expiryUrl);
            $hpsTxn->addChild('expiry_url', $expiryUrl);
        }

        return $this;
    }

    /**
     * Add dynamic data to the request
     *
     * @param array key/value pairs $dynamicData Upto 9 elements to replace dynamic data in the card capture page (Optional)
     *
     * @return DataCash_Dpg_Model_DataCash_Request
     * @author Hilary Boyce
     */
    public function addHpsDynamicData($data)
    {
        if (!is_array($data)){
            throw new InvalidArgumentException("Invalid dynamic data supplied");
        }
        if (!$this->getRequest()->Transaction->HpsTxn) {
            throw new UnexpectedValueException('Parent node does not exist');
        }
        if(count($data) > 0){
            $dynamicData = $this->getRequest()->Transaction->HpsTxn->addChild('DynamicData');
            foreach($data as $key => $value){
                $this->_validateDynamicDataItem($value);
                $dynamicData->addChild($key,$value);
            }
        }
        return $this;
    }


    /**
     * addThreeDSecure
     *
     * @param string $verify (yes|no) whether the service is required. If yes other params required
     * @param string $baseUrl
     * @param string $purchaseDesc 1-125 character description
     * @param string $purchaseDateTime format YYYY-MM-DD HH:MM:SS
     * @param array $browser array of key/value pairs
     *        device_category 0|1
     *        accept_headers
     *        user_agent
     * @param bool mpiRequest
     *
     * @return DataCash_Dpg_Model_DataCash_Request
     * @author Hilary Boyce
     */
    public function addThreeDSecure($verify, $baseUrl = null, $purchaseDesc = null, $purchaseDateTime = null, $browserData = array(), $mpiRequest = true)
    {
        if (!$this->getRequest()->Transaction->TxnDetails) {
            throw new UnexpectedValueException('Parent node does not exist');
        }
        $threeDSecure = $this->getRequest()->Transaction->TxnDetails->addChild('ThreeDSecure');
        if($verify === 'no'){
            $threeDSecure->addChild('verify', $verify);
        } elseif($verify == 'yes'){
            $this->_validateUrl($baseUrl);
            $this->_validatePurchaseDesc($purchaseDesc);
            $this->_validatePurchaseDatetime($purchaseDateTime);
            $this->_validateBrowser($browserData);
            //bugfix 0.1.14 verify needed for hps ThreeDSecure section in setup request but not for mpi requests
            if(!$mpiRequest){
                $threeDSecure->addChild('verify', $verify);
            }
            $threeDSecure->addChild('merchant_url', $baseUrl);
            $threeDSecure->addChild('purchase_desc', $purchaseDesc);
            $threeDSecure->addChild('purchase_datetime', str_replace('-','', $purchaseDateTime));
            $threeDSecure->addChild('Browser');
            $browser = $threeDSecure->Browser;
            foreach($browserData as $key => $value){
                $browser->addChild($key, $value);
            }
        } else {
            throw new InvalidArgumentException(
            "Invalid value for verify supplied {$verify}"
            );
        }
        return $this;
    }

    /**
     * addCv2Avs
     *
     * @param $streetAddress1 Optional
     * @param string $streetAddress2 Optional
     * @param string $streetAddress3 Optional
     * @param string $streetAddress4 Optional
     * @param string $postcode Optional
     * @param string $cv2 3 or 4 digit code Optional
     * @param string $policy Optional (1|2|3|5|6|7|
     * Only one of policy or extended policy can be provided (use addCv2AvsExtendedPolicy)
     *
     * @return DataCash_Dpg_Model_DataCash_Request
     * @author Hilary Boyce
     * @author Alistair Stead
     */
    public function addCv2Avs($streetAddress1 = null, $streetAddress2 = null, $streetAddress3 = null, $streetAddress4 = null,
                              $postcode = null, $cv2 = null, $policy = null)
    {
        if (!$this->getRequest()->Transaction) {
            throw new UnexpectedValueException('Parent Transaction node does not exist');
        }
        if (!$this->getRequest()->Transaction->CardTxn) {
            throw new UnexpectedValueException('Parent node CardTxn does not exist');
        }
        // either some address information or cv2 must be provided
        if (is_null($streetAddress1)&& is_null($streetAddress2)&& is_null($streetAddress3)&& is_null($streetAddress4)&&
             is_null($postcode)&& is_null($cv2)){
                 throw new InvalidArgumentException(
                    "Address or cv2 must be provided"
                 );
        }
        if($postcode) {
            $this->_validatePostcode($postcode);
        }
        if($cv2){
            $this->_validateCv2($cv2);
        }
        if($policy){
            $this->_validatePolicy($policy);
        }
        if (!$this->getRequest()->Transaction->CardTxn->Card) {
            $this->getRequest()->Transaction->CardTxn->addChild('Card');
        }
        $cv2Avs = $this->getRequest()->Transaction->CardTxn->Card->addChild('Cv2Avs');
        if($streetAddress1){
            $cv2Avs->addChild('street_address1', $streetAddress1);
        }
        if($streetAddress2){
            $cv2Avs->addChild('street_address2', $streetAddress2);
        }
        if($streetAddress3){
            $cv2Avs->addChild('street_address3', $streetAddress3);
        }
        if($streetAddress4){
            $cv2Avs->addChild('street_address4', $streetAddress4);
        }
        if($postcode){
            $cv2Avs->addChild('postcode', $postcode);
        }
        if($cv2){
            $cv2Avs->addChild('cv2', $cv2);
            $cv2Avs->addChild('cv2_present', '1');
        }
        if($policy){
            $cv2Avs->addChild('policy', $policy);
        }
        return $this;
    }

    /**
     * addCv2ExtendedPolicy
     *
     * @param array $policy with keys of cv2_policy, postcode_policy, address_policy and for each an array of required attributes
     *   eg. array('cv2_policy'     =>  array('notprovided'  => 'accept|reject',
     *                                        'notchecked'   => 'accept|reject',
     *                                        'matched'      => 'accept|reject',
     *                                        'notmatched'   => 'accept|reject',
     *                                        'partialmatch' => 'accept|reject'
     *                                       ),
     *             'postcode_policy' => array( etc...
     *
     * @return DataCash_Dpg_Model_DataCash_Request
     * @author Hilary Boyce
     */
    public function addCv2ExtendedPolicy(array $policy)
    {
        if (!isset($this->getRequest()->Transaction->CardTxn->Card->Cv2Avs)) {
            throw new UnexpectedValueException('Parent node does not exist');
        }

        // make sure there isn't already a policy element as there can't be both policy and extended policy
        if ($this->getRequest()->Transaction->CardTxn->Cv2Avs->policy) {
            throw new UnexpectedValueException('There is already a policy element');
        }
        $this->_validateExtendedPolicy($policy);
        $cv2Avs = $this->getRequest()->Transaction->CardTxn->Card->Cv2Avs;
        $extendedPolicy = $cv2Avs->addChild('ExtendedPolicy');
        foreach($policy as $elementName => $elementAttribute){
            $element = $extendedPolicy->addChild($elementName);
            foreach($elementAttribute as $attributeName => $attributeValue){
                $element->addAttribute($attributeName, $attributeValue);
            }
        }
        return $this;
    }

    /**
     * Add the TxnDetails section to the request  for HCC
     *
     * @param string $orderNumber The Magento order number (Required)
     * @param string $amount The value to be charged (Required)
     * @return DataCash_Dpg_Model_DataCash_Request
     * @author Hilary Boyce
     **/
    public function addHccTxnDetails($orderNumber, $amount)
    {
        if (!$this->getRequest()->Transaction) {
            throw new UnexpectedValueException('Parent node does not exist');
        }
        $this->_validateOrderNumber($orderNumber);
        $txnDetails = $this->getRequest()->Transaction->addChild('TxnDetails');
        $txnDetails->addChild('merchantreference', $orderNumber);
        $txnDetails->addChild('amount', $amount);
        $txnDetails->addChild('capturemethod', 'ecomm');
        return $this;
    }

    /**
     * addHccCardTxn
     * @return DataCash_Dpg_Model_DataCash_Request
     * @author Hilary Boyce
     */
    public function addHccCardTxn($reference, $method = 'auth')
    {
        if (!$this->getRequest()->Transaction) {
            throw new UnexpectedValueException('Parent node does not exist');
        }
        $this->_validateCardMethod($method, 'hcc');
        if (!is_numeric($reference) || (strlen($reference)!== 16)) {
            throw new InvalidArgumentException(
                    "Invalid datacash reference"
            );
        }
        $cardTxn = $this->getRequest()->Transaction->addChild('CardTxn');
        $cardTxn->addChild('method', $method);
        $cardDetails = $cardTxn->addChild('card_details', $reference);
        $cardDetails->addAttribute('type', 'from_hps');

        return $this;
    }

    /**
     * addThe3rdMan
     * @author David Marrs
     **/
    public function addThe3rdMan($vars)
    {
        extract($vars);
        $txn = $this->getRequest()->Transaction->TxnDetails;
        if ( ! $txn ) {
            throw new Exception('Parent node (Transaction->TxnDetails) does not exist');
        }

        $t3m = $txn->addChild('The3rdMan');
        $t3m->addAttribute('type', 'realtime');
        $customer = $t3m->addChild('CustomerInformation');
        $customer->addChild('customer_reference', NULL);
        $customer->addChild('order_number', $orderNumber);
        $customer->addChild('forename', $billingAddress->getFirstname());
        $customer->addChild('surname', $billingAddress->getLastname());
        $phone = $billingAddress->getTelephone();
        $customer->addChild('telephone', preg_match('/^[\(\)\-0-9 ]+$/', $phone)? $phone: '');
        $fax = $billingAddress->getFax();
        $customer->addChild('alt_telephone', preg_match('/^[\(\)\-0-9 ]+$/', $fax)? $fax: '');
        $customer->addChild('email', $billingAddress->getEmail());
        $customer->addChild('customer_dob', NULL);
        $customer->addChild('first_purchase_date', $previousOrders['first']);
        $customer->addChild('ip_address', $remoteIp);
        $prevPurchases = $customer->addChild('previous_purchases');
        $prevPurchases->addAttribute('count', $previousOrders['count']);
        $prevPurchases->addAttribute('value', $previousOrders['total']);

        if ($shippingAddress) {
            $this->addT3mAddress($t3m, 'DeliveryAddress', $shippingAddress);
        }
        $this->addT3mAddress($t3m, 'BillingAddress', $billingAddress);

        $order = $t3m->addChild('OrderInformation');
        $rt = $t3m->addChild('Realtime');
        $rt->addChild('real_time_callback', $callbackUrl);
        $rt->addChild('real_time_callback_format', 'HTTP');
        // XXX Field 'distribution_channel' is not specified as required so I'm
        // leaving it out.
        $order->addChild('distribution_channel', NULL); // XXX Where is this set in mage?
        $products = $order->addChild('Products');
        $products->addAttribute('count', count($orderItems));
        foreach ($orderItems as $item) {
            $product = $products->addChild('Product');
            $product->addChild('code', $item->getSku());
            $product->addChild('quantity', $item->getQtyOrdered());
            $product->addChild('price', $item->getPrice());
        }
    }

    private function addT3mAddress($node, $nodeName, $data) {
        $addr = $node->addChild($nodeName);
        $street = $data->getStreet();
        $street = array_shift($street);
        $addr->addChild('street_address_1', $street);
        $street = $data->getStreet();
        $street = array_shift($street);
        $addr->addChild('street_address_2', $street);
        $addr->addChild('city', $data->getCity());
        $addr->addChild('county', $data->getRegion());
        $addr->addChild('postcode', $data->getPostcode());
        // XXX Field 'country' needs to be numeric country code which isn't
        // in magento. The docs don't specify if this field is required or
        // not, so I'm leaving it out.
        // $addr->addChild('country', $data->getCountryId());
    }

    /**
     * Return the internal $_request object as an xml document
     *
     * @return string
     * @author Alistair Stead
     **/
    public function toXml(array $arrAttributes = array(), $rootName = 'item', $addOpenTag=false, $addCdata=true)
    {
        return $this->getRequest()->asXml();
    }

    /**
     * Return the internal $_request object as an associative array
     *
     * @return array
     * @author Alistair Stead
     **/
    public function toArray(array $arrAttributes = array())
    {
        return $this->getRequest()->asArray();
    }

    /**
     * Public getter for the internal $_request variable
     *
     * @return Varien_Simplexml_Element
     * @author Alistair Stead
     **/
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * Internal method to validate the currency value to be sent by the request
     *
     * @return void
     * @author Alistair Stead
     **/
    protected function _validateCurrency($currency)
    {
        if (strlen($currency) != 3) {
            throw new InvalidArgumentException(
                "Currency should be passed in the 3 character ISO 4217 Alphabetic format {$currency} geven"
            );
        }
    }

    /**
     * Internal method to validate the order number to be sent by the request
     *
     * @return void
     * @author Alistair Stead
     **/
    protected function _validateOrderNumber($number)
    {
        if ($number) {
            if (strpos($number, '-') !== false) {
                $parts = explode('-', $number);
                $number = $parts[0];
            }
            $length = strlen($number);
            if ( $length > 30 || $length < 6) {
                throw new InvalidArgumentException(
                    "Order number must be min 6, max 30 alphanumeric characters. {$number}"
                );
            }
        }
    }

    /**
     * Internal method to validate the method value to be sent in the API request
     *
     * @return void
     * @author Alistair Stead
     **/
    protected function _validateCardMethod($method, $type)
    {
        switch(strtolower($type)){
            case 'api':
                $transactionTypes = array('auth', 'pre', 'refund', 'erp');
                break;
            case 'hcc':
                $transactionTypes = array('auth', 'pre');
                break;
            case 'hps':
                $transactionTypes = array('auth', 'pre');
                break;
            default:
                throw new InvalidArgumentException(
                "Invalid transaction type supplied {$type}"
            );
        }
        if (!in_array($method, $transactionTypes)) {
            throw new InvalidArgumentException(
                "Invalid transaction method supplied {$method}"
            );
        }
    }

    /**
     * Internal method to validate the method value to be sent in the API request
     *
     * @return void
     * @author Alistair Stead
     **/
    protected function _validateHistoricMethod($method, $type)
    {
        switch(strtolower($type)){
            case 'api':
                $transactionTypes = array('fulfill', 'txn_refund', 'cancel', 'authorize_referr', 'al_request', 'accept_review');
                break;
            case '3d':
                $transactionTypes = array('threedsecure_validate_authentication', 'threedsecure_authorization_request');
                break;
            case 'hps':
                $transactionTypes = array('query');
                break;
            default:
                throw new InvalidArgumentException(
                "Invalid transaction type supplied {$type}"
            );
        }
        if (!in_array($method, $transactionTypes)) {
            throw new InvalidArgumentException(
                "Invalid transaction method supplied {$method}"
            );
        }
    }

    /**
     * Internal method to validate the method value to be sent in the HCC request
     *
     * @return void
     * @throws InvalidArgumentException
     * @author Hilary Boyce
     **/
    protected function _validateHPSMethod($method, $type)
    {
        $expectedMethods = array(
            'hcc' => 'setup',
            'hps' => 'setup_full'
        );

        if (!isset($expectedMethods[$type])){
            throw new InvalidArgumentException(
                "Invalid type supplied {$type}"
            );
        }
        if($expectedMethods[$type] !== $method) {
            throw new InvalidArgumentException(
                "Invalid method supplied {$method}"
            );
        }
    }

    /**
     *  Internal method to validate urls
     *
     *  @return void
     *  @throws InvalidArgumentException
     *  @author Hilary Boyce
     **/
    protected function _validateUrl($url)
    {
        if (!Zend_Uri::check($url)){
            throw new InvalidArgumentException(
                "Invalid url supplied {$url}"
            );
        }
    }

    /**
     *  Internal method to validate dynamic data
     *
     *  @return void
     *  @throws InvalidArgumentException
     *  @author Hilary Boyce
     **/
    protected function _validateDynamicDataItem($entry)
    {
        if (strlen($entry) > 2048){
            throw new InvalidArgumentException(
                "Invalid dynamic data entry supplied"
            );
        }
    }

    /**
     *  Internal method to validate purchase description
     *
     *  @return void
     *  @throws InvalidArgumentException
     *  @author Hilary Boyce
     **/
    protected function _validatePurchaseDesc($desc)
    {
        $length = strlen($desc);
        if ($length < 1 || $length > 125){
            throw new InvalidArgumentException(
                "Invalid purchase description entry supplied"
            );
        }
    }

    /**
     *  Internal method to validate purchase date
     *
     *  @return void
     *  @throws InvalidArgumentException
     *  @author Hilary Boyce
     **/
    protected function _validatePurchaseDatetime($datetime)
    {
        ;
        if(Zend_Date::isDate(trim($datetime))){
            throw new InvalidArgumentException("Invalid date entry supplied {$datetime}");
        }
    }

    /**
     *  Internal method to validate browser parameters
     *
     *  @return void
     *  @throws InvalidArgumentException
     *  @author Hilary Boyce
     */
    protected function _validateBrowser($browser)
    {
        if(!is_array($browser) || count($browser)!= 3){
            throw new InvalidArgumentException(
            "Invalid number of browser parameters supplied"
            );
        }
        if(!isset($browser['device_category'])|| ($browser['device_category'] < 0 || $browser['device_category'] > 1)){
            throw new InvalidArgumentException(
                "Invalid/missing browser device category"
            );
        }
        if(!isset($browser['accept_headers']) || strlen($browser['accept_headers']) == 0){
            throw new InvalidArgumentException(
                "Invalid/missing browser device accept headers"
            );
        }
        if(!isset($browser['user_agent']) || strlen($browser['user_agent']) == 0){
            throw new InvalidArgumentException(
                "Invalid/missing browser device user agent"
            );
        }
    }

    /**
     * Internal method to validate postcode length
     *
     * @return void
     * @throws InvalidArgementExeption
     * @author Hilary Boyce
     */
    protected function _validatePostcode($postcode)
    {
        if(strlen($postcode) > 9){
        throw new InvalidArgumentException(
                "Invalid postcode {$postcode}"
            );
        }
    }

    /**
     * Internal method to validate cv2 number
     *
     * @return void
     * @throws InvalidArgementExeption
     * @author Hilary Boyce
     */
    protected function _validateCv2($cv2)
    {
        if(!preg_match('/^[0-9]{3,4}$/', $cv2)){
        throw new InvalidArgumentException(
                "Invalid Cv2 number {$cv2}"
            );
        }
    }

    /**
     * Internal method to validate policy
     *
     * @param string $policy
     * @return void
     * @throws InvalidArgementExeption
     * @author Hilary Boyce
     */
    protected function _validatePolicy($policy)
    {
        $validPolicies = array('1', '2', '3', '5', '6', '7');
        if(!in_array($policy, $validPolicies)){
        throw new InvalidArgumentException(
                "Invalid policy {$policy}"
            );
        }
    }

    /**
     * Internal method to validate extended policy
     *
     * @param string $policy
     * @return void
     * @throws InvalidArgementExeption
     * @author Hilary Boyce
     */
    protected function _validateExtendedPolicy($policy)
    {
        $expectedElements = array('cv2_policy', 'postcode_policy', 'address_policy');
        $expectedAttributes = array('notprovided', 'notchecked','matched', 'notmatched', 'partialmatch');
        $expectedValues = array('accept', 'reject');

        //check they are the expected elements
        $actualElements = array_keys($policy);
        foreach($expectedElements as $element){
            if(!in_array($element,$actualElements)){
                throw new InvalidArgumentException(
                    "Invalid extended policy elements"
                );
            }
        }
        foreach($policy as $key => $element){
            if(!is_array($element)){
                throw new InvalidArgumentException(
                    "Invalid extended policy attributes"
                );
            }
            $actualAttributes = array_keys($element);
            foreach($expectedAttributes as $attribute){
                if(!in_array($attribute, $actualAttributes)){
                    throw new InvalidArgumentException(
                        "Invalid extended policy attributes"
                    );
                }
            }
            foreach($element as $name => $value){
                if(!in_array($value, $expectedValues)){
                    throw new InvalidArgumentException(
                    "Invalid policy attribute"
                    );
                }
            }


        }
    }
}

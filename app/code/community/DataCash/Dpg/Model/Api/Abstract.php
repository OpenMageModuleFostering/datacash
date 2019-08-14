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

abstract class DataCash_Dpg_Model_Api_Abstract extends Varien_Object
{
    /**
     * The internal member variable that will hold the client
     *
     * @var Zend_Http_Client
     **/
    protected $_client;

    /**
     * Internal member varaiable that will hold the request object
     *
     * @var DataCash_Dpg_Model_DataCash_Response
     **/
    protected $_response;

    /**
     * Internal member varaiable that will hold the request object
     *
     * @var Zend_Http_Response
     **/
    protected $_rawResponse;

    /**
     * Internal member varaiable that will hold the request object
     *
     * @var DataCash_Dpg_Model_DataCash_Request
     **/
    protected $_request;

    /**
     * Internal member variable that will hold the module config
     *
     * @var DataCash_Dpg_Model_Config
     **/
    protected $_config;

    /**
     * Fields that should be replaced in debug with '***'
     *
     * @var array
     */
    protected $_debugReplacePrivateDataKeys = array(
        'client',
        'password',
        'pan'
    );

    /**
     * API endpoint getter
     *
     * @return string
     */
    public function getApiEndpoint()
    {
       return $this->getConfig()->getEndpoint($this->getMethod());
       
    }

    /**
     * Get a unique-ish order number
     */
    public function getUniqueOrderNumber()
    {
        return $this->getOrderNumber().' - '.time();
    }

    /**
     * Return the method name
     *
     * @return string
     * @author Alistair Stead
     **/
    abstract function getMethod();
    
    /**
     * Get info about transaction
     * @var $ref string
     */
    public function queryTxn($ref)
    {
        $request = $this->getRequest()
            ->addTransaction()
            ->addHistoricTxn(
                'query',
                $ref,
                null,
                'hps',
                null
            );
        $this->call($request);
    }

    /**
     * Call the DataCash API to make a refund request
     *
     * @return void
     * @author Alistair Stead
     **/
    public function callRefund()
    {
        $request = $this->getRequest()
            ->addTransaction()
            ->addTxnDetails($this->getUniqueMercRef(), $this->getAmount(), $this->getCurrency())
            ->addCardTxn('refund', $this->getAuthCode());

        $this->call($request);
    }

    /**
     * Call the DataCash API to make an ERP request
     *
     * @return void
     * @author Alistair Stead
     **/
    public function callErp()
    {
        $request = $this->getRequest()
            ->addTransaction()
            ->addTxnDetails($this->getOrderNumber(), $this->getAmount(), $this->getCurrency())
            ->addCardTxn('erp');

        $this->call($request);
    }

    /**
     * Call the DataCash API to ACCEPT/DENY a payment review
     *
     * @return void
     * @author Kristjan Heinaste <kristjan@ontapgroup.com>
     **/
    public function callReview($action)
    {
        $request = $this->getRequest()
            ->addTransaction()
            ->addHistoricTxn($action, $this->getDataCashReference(), $this->getAuthCode());

        $this->call($request);
    }

    /**
     * Call the DataCash API to make a Cancel request
     *
     * @return void
     * @author Alistair Stead
     **/
    public function callCancel()
    {
        $request = $this->getRequest()
            ->addTransaction()
            // ->addTxnDetails($this->getOrderNumber(), $this->getAmount(), $this->getCurrency())
            ->addHistoricTxn('cancel', $this->getDataCashReference(), $this->getAuthCode());

        $this->call($request);
    }

    private function getUniqueMercRef()
    {
        return $this->getOrderNumber().'-'.time();
    }

    /**
     * Call the DataCash API to make a fulfill request
     *
     * @return void
     * @author Alistair Stead
     **/
    public function callFulfill()
    {
        $request = $this->getRequest()
            ->addTransaction()
            ->addTxnDetails($this->getUniqueMercRef(), $this->getAmount(), $this->getCurrency())
            ->addHistoricTxn('fulfill', $this->getDataCashReference(), $this->getAuthCode());

        $this->call($request);
    }

    /**
     * Call the DataCash API to make a TXN Refund request
     *
     * @return void
     * @author Alistair Stead
     **/
    public function callTxnRefund()
    {
        $request = $this->getRequest()
            ->addTransaction()
            ->addTxnDetails($this->getUniqueMercRef(), $this->getAmount())
            ->addHistoricTxn('txn_refund', $this->getDataCashReference(), $this->getAuthCode());

        $this->call($request);
    }

    /**
     * Call the DataCash API to make a Authorise Referral request
     *
     * @return void
     * @author Alistair Stead
     **/
    public function callAuthorizeReferralRequest()
    {
        $request = $this->getRequest()
            ->addTransaction()
            ->addTxnDetails($this->getUniqueMercRef(), $this->getAmount(), $this->getCurrency())
            ->addHistoricTxn('authorize_referral_request', $this->getDataCashReference(), $this->getAuthCode());

        $this->call($request);
    }

    /**
     * If configured to include the CV2 information add the data to the request
     *
     * @return void
     * @author Alistair Stead
     **/
    protected function _addCv2Avs()
    {
        if ($this->getIsUseCcv()) {
            $request = $this->getRequest();
            $billingAddress = $this->getBillingAddress();
            if (!$billingAddress) {
                throw new Exception('Billing address must be specified to addCv2Avs');
            }
            $request->addCv2Avs(
                $billingAddress->getStreet(1),
                $billingAddress->getStreet(2),
                $billingAddress->getCity(),
                $billingAddress->getRegionId(),
                $billingAddress->getPostcode(),
                $this->getCreditCardCvv2()
            );
            if ($this->getIsUseExtendedCv2()) {
                $request->addCv2ExtendedPolicy($this->getCv2ExtendedPolicy());
            }
        }
    }

    /**
     * If configured to include line items add them to the request
     *
     * @return void
     * @author Alistair Stead
     **/
    protected function _addLineItems()
    {
        if ((bool) $this->getIsLineItemsEnabled()) {
            $request = $this->getRequest();
            $request->addLineItemDetail($this->getCustomerCode(), $this->getTransactionVat());
            $request->addShipping($this->getShippingAmount(), $this->getShippingVatRate());
            foreach ($this->getCartItems() as $item) {
                // var_dump($item->getData());
                // Evaluate the QTY value
                $qty = ($item->getQtyOrdered() ? $item->getQtyOrdered() : ($item->getQty() ? $item->getQty() : 1));
                $request->addItem(
                    $item->getName(),
                    'unit',
                    $item->getOriginalPrice(),
                    $item->getTaxPercent(),
                    $qty,
                    $item->getRowTotalInclTax(),
                    $item->getSku(),
                    $item->getDiscountAmount(),
                    $item->getCommodityCode()
                );
            }
        }
    }

    /**
     * Add The Third Man information to the request
     *
     * @return void
     * @author Alistair Stead
     **/
    protected function _addT3m($vars)
    {
        if ($this->getConfig()->getIsAllowedT3m($this->getMethod())) {
            $vars['callbackUrl'] = $this->getConfig()->getT3mCallBackUrl($this->getMethod());
            $this->getRequest()->addThe3rdMan($vars);
        }
    }

    public function addFraudScreening()
    {
        if (!$this->getUseFraudScreening()) {
            return;
        }
        
        $policy = $this->getFraudScreeningPolicy();
        $req = $this->getRequest();
        
        $req->setupRsg($this->getConfig()->getFraudScreeningMode($this->getMethod()));
        
        $this->customerRsgDataToRequest($policy['customer'], $req);
        $this->billingRsgDataToRequest($policy['billing'], $req);
        $this->shippingRsgDataToRequest($policy['shipping'], $req);
        $this->paymentRsgDataToRequest($policy['payment'], $req);
        $this->orderRsgDataToRequest($policy['order'], $req);
        $this->itemRsgDataToRequest($policy['item'], $req);
    }
    
    /* @return void */
    private function shippingRsgDataToRequest($policy, $request)
    {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $shippingAddress = $quote->getShippingAddress();
        
        $map = array(
            'first_name' => new DataCash_Dpg_Helper_Cdata($shippingAddress->getFirstname()),
            'surname' => new DataCash_Dpg_Helper_Cdata($shippingAddress->getLastname()),
            'address_line1' => new DataCash_Dpg_Helper_Cdata($shippingAddress->getStreet(1)),
            'address_line2' => new DataCash_Dpg_Helper_Cdata($shippingAddress->getStreet(2)),
            'city' => new DataCash_Dpg_Helper_Cdata($shippingAddress->getCity()),
            'state_province' => $shippingAddress->getRegionCode(),
            'zip_code' => new DataCash_Dpg_Helper_Cdata($shippingAddress->getPostcode()),
            'country' => $shippingAddress->getCountryId(),
        );
        
        $rsgData = array();
        foreach($policy as $field => $is_enabled) {
            if (!isset($map[$field]) || $is_enabled != "1") {
                continue;
            }
            $rsgData[$field] = $map[$field];
        }
        $request->setRsgShipping($rsgData);        
    }
    
    /* @return void */
    private function itemRsgDataToRequest($policy, $request)
    {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $cartItems = $quote->getAllVisibleItems();
        
        $map = array();
        
        foreach ($cartItems as $item) {
            $itemData = array();
            foreach($policy as $field => $is_enabled) {
                if ($is_enabled != "1") {
                    continue;
                }
                $value = null;
                switch($field) {
                    case 'product_code':
                        $value = htmlentities($item->getProduct()->getSku());
                        break;
                    case 'product_description':
                        $product = $item->getProduct();
                        $desc = substr(strip_tags($product->getName()), 0, 50);
                        $value = new DataCash_Dpg_Helper_Cdata($desc);
                        break;
                    case 'product_category':
                        $product = $item->getProduct();
                        $parent = $item->getParentItem();
                        if ($parent) {
                            $product = $parent->getProduct();
                        }
                        $categoryIds = $product->getCategoryIds();
                        $values = array();
                        foreach ($categoryIds as $categoryId) {
                            $values[] = Mage::getModel('catalog/category')->load($categoryId)->getName();
                        }                     
                        $value = new DataCash_Dpg_Helper_Cdata(implode(',', $values));
                        break;
                    case 'order_quantity':
                        $value = (int)$item->getQty();
                        break;
                    case 'unit_price':
                        $value = intval(floatval($item->getPrice()) * 100);
                        break;
                    
                }
                $itemData[$field] = $value;
            }
            $map[] = $itemData;
        }
        $request->setRsgItems($map);         
    }
    
    /* @return void */
    private function orderRsgDataToRequest($policy, $request)
    {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $totals =  $quote->getTotals(); 
        $timezone = Mage::app()->getStore()->getConfig('general/locale/timezone');
        
        $map = array(
            //'time_zone' => (string)$timezone,
            'discount_value' => isset($totals["discount"]) ? intval(floatval($totals["discount"]->getValue()) * 100) : 0,
        );
        
        $rsgData = array();
        foreach($policy as $field => $is_enabled) {
            if (!isset($map[$field]) || $is_enabled != "1") {
                continue;
            }
            $rsgData[$field] = $map[$field];
        }
        $request->setRsgOrder($rsgData);         
    }
    
    /* @return void */
    private function paymentRsgDataToRequest($policy, $request)
    {
        $map = array(
            'payment_method' => 'CC' // TODO: double check
        );
        
        /*$rsgData = array();
        foreach($policy as $field => $is_enabled) {
            if (!isset($map[$field]) || $is_enabled != "1") {
                continue;
            }
            $rsgData[$field] = $map[$field];
        }*/
        $request->setRsgPayment($map);        
    }
    
    /* @return void */
    private function customerRsgDataToRequest($policy, $request)
    {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        
        $map = array(
            'ip_address' => $_SERVER['REMOTE_ADDR'],
        );
        
        $useBillingAddr = Mage::getStoreConfig('payment/datacash_hcc/rsg_data_customer_first_name') == "1" || $quote->isVirtual() ? true : false;
        $address = $useBillingAddr ? $quote->getBillingAddress() : $quote->getShippingAddress();
        
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            // Customer details
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            
            $map = array_merge($map, array(
                'first_name' => new DataCash_Dpg_Helper_Cdata($address->getFirstname()),
                'surname' => new DataCash_Dpg_Helper_Cdata($address->getLastname()),
                'user_id' => $customer->getId(),
                'email_address' => $customer->getEmail(),
            ));
            
            if ($address->getId()) {
                $map = array_merge($map, array(
                    'address_line1' => new DataCash_Dpg_Helper_Cdata($address->getStreet(1)),
                    'address_line2' => new DataCash_Dpg_Helper_Cdata($address->getStreet(2)),
                    'city' => new DataCash_Dpg_Helper_Cdata($address->getCity()),
                    'state_province' => $address->getRegionCode(),
                    'zip_code' => new DataCash_Dpg_Helper_Cdata($address->getPostcode()),
                    'country' => $address->getCountryId(),
                    'telephone' => new DataCash_Dpg_Helper_Cdata($address->getTelephone()),
                ));
            }
        } else {
            // Guest details
            $map = array_merge($map, array(
                'email_address' => $address->getEmail(),
                'first_name' => new DataCash_Dpg_Helper_Cdata($address->getFirstname()),
                'surname' => new DataCash_Dpg_Helper_Cdata($address->getLastname()),
                'address_line1' => new DataCash_Dpg_Helper_Cdata($address->getStreet(1)),
                'address_line2' => new DataCash_Dpg_Helper_Cdata($address->getStreet(2)),
                'city' => new DataCash_Dpg_Helper_Cdata($address->getCity()),
                'state_province' => $address->getRegionCode(),
                'zip_code' => new DataCash_Dpg_Helper_Cdata($address->getPostcode()),
                'country' => $address->getCountryId(),
            ));            
        }
        
        $rsgData = array();
        foreach($policy as $field => $is_enabled) {
            if (!isset($map[$field]) || $is_enabled != "1") {
                continue;
            }
            $rsgData[$field] = $map[$field];
        }

        $request->setRsgCustomer($rsgData);        
    }
    
    /* @return void */
    private function billingRsgDataToRequest($policy, $request)
    {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $billingAddress = $quote->getBillingAddress();
        
        $map = array(
            'name' => new DataCash_Dpg_Helper_Cdata("{$billingAddress->getFirstname()} {$billingAddress->getLastname()}"),
            'address_line1' => new DataCash_Dpg_Helper_Cdata($billingAddress->getStreet(1)),
            'address_line2' => new DataCash_Dpg_Helper_Cdata($billingAddress->getStreet(2)),
            'city' => new DataCash_Dpg_Helper_Cdata($billingAddress->getCity()),
            'state_province' => $billingAddress->getRegionCode(),
            'zip_code' => new DataCash_Dpg_Helper_Cdata($billingAddress->getPostcode()),
            'country' => $billingAddress->getCountryId(),
        );
        
        $rsgData = array();
        foreach($policy as $field => $is_enabled) {
            if (!isset($map[$field]) || $is_enabled != "1") {
                continue;
            }
            $rsgData[$field] = $map[$field];
        }
        $request->setRsgBilling($rsgData);
    }

    /**
     * Call the DataCash API to make a 3D auth request
     *
     * @return void
     * @author Alistair Stead
     **/
    public function call3DValidAuth()
    {
        $request = $this->getRequest()
            ->addTransaction()
            ->addHistoricTxn('threedsecure_validate_authentication', $this->getDataCashReference(), null, '3d', $this->getParesMessage());

        $this->call($request);
    }


    /**
     * Add the 3D Secure information to the request if enabled
     *
     * @param boolean $mpi Send the verify node or not. The verify node causes problem with mpi requests.
     * @return void
     * @author Alistair Stead, Norbert Nagy
     **/
    protected function _add3DSecure($mpi = true)
    {
        $request = $this->getRequest();
        $request->addThreeDSecure(
            'yes',
            $this->getBaseUrl(),
            $this->getPurchaseDescription(),
            $this->getPurchaseDateTime(),
            $this->getBrowserData(),
            $mpi
        );
    }

    /**
     * Do the API call final call method that will submit the request
     * to the DataCash service
     *
     * @param array $request
     * @return DataCash_Dpg_Model_Api_Abstract
     * @throws Mage_Core_Exception
     * @author Alistair Stead
     */
    public function call($request)
    {
        // Debugging calls
        $this->_debug(array('request' => $request->toArray()));

        try {
            // Build the HTTP client and set the request data
            $client = $this->getClient();
            $client->setUri($this->getApiEndpoint());
            $client->setMethod(Zend_Http_Client::POST);
            $client->setRawData($request->toXml(), 'text/xml');

            // Make the HTTP request and set the raw response
            $this->setRawResponse($client->request());

        } catch (Exception $e) {
            $debugData['http_error'] = array('error' => $e->getMessage(), 'code' => $e->getCode());
            $this->_debug($debugData);

            throw $e;
        }

        // Debugging calls
        $this->_debug(array('response' => $this->getResponse()));

        return $this;
    }

    /**
     * Set the internal config object
     *
     * @param DataCash_Dpg_Model_Config $config
     * @return void
     * @author Alistair Stead
     **/
    public function setConfig(DataCash_Dpg_Model_Config $config)
    {
        $this->_config = $config;
    }

    /**
     * Retreive the internal config object
     *
     * @return DataCash_Dpg_Model_Config
     * @author Alistair Stead
     **/
    public function getConfig()
    {
        if (is_null($this->_config)) {
            $this->_config = Mage::getSingleton('dpg/config');
        }
        return $this->_config;
    }

    /**
     * Return the HTTP client used to communicate with the API gateway
     *
     * @return Zend_Http_Client
     * @author Alistair Stead
     **/
    public function getClient()
    {
        if (is_null($this->_client)) {
            $config = array(
                'adapter'   => 'Zend_Http_Client_Adapter_Curl',
                'curloptions' => array(CURLOPT_FOLLOWLOCATION => true),
                'timeout' => 10,
                'maxredirects' => 5
            );
            $this->_client = new Zend_Http_Client('http://www.datacash.com', $config);
        }
        return $this->_client;
    }

    /**
     * Set the internal http client
     *
     * @return void
     * @author Alistair Stead
     **/
    public function setClient(Zend_Http_Client $client)
    {
        $this->_client = $client;
    }

    /**
     * Retrieve the internal request object
     *
     * @return DataCash_Dpg_Model_DataCash_Request
     * @author Alistair Stead
     **/
    public function getRequest()
    {
        if (is_null($this->_request)) {
            $this->_request = Mage::getModel('dpg/datacash_request');
            $this->_request->addAuthentication($this->getMerchantId(), $this->getMerchantPassword());
        }
        return $this->_request;
    }

    /**
     * Set the internal request object
     *
     * @return void
     * @author Alistair Stead
     **/
    public function setRequest(DataCash_Dpg_Model_DataCash_Request $request)
    {
        $this->_request = $request;
    }

    /**
     * Retrieve the internal response
     *
     * The HTTP response transposed into the DataCash_Response that
     * extends Varien_Object and provides the getData setData interface
     *
     * @return DataCash_Dpg_Model_DataCash_Response
     * @author Alistair Stead
     */
    public function getResponse()
    {
        if (is_null($this->_response)) {
            $this->_response = Mage::getModel('dpg/datacash_response');
        }
        return $this->_response;
    }

    /**
     * Retrieve the original raw HTTP response
     *
     * @return Zend_Http_Response
     * @author Alistair Stead
     **/
    public function getRawResponse()
    {
        return $this->_rawResponse;
    }

    /**
     * Set the internal _rawResponse property
     *
     * @return void
     * @author Alistair Stead
     **/
    public function setRawResponse(Zend_Http_Response $response)
    {
        $this->_rawResponse = $response;
        $body = new DataCash_Dpg_Model_Datacash_Simplexml_Element($this->_rawResponse->getBody());
        $this->getResponse()->addData($body->asArray());
    }

    /**
     * Debugging method for the payment gateway
     *
     * This can allow debugging in the live system as all exceptions will
     * be swallowed by Magento.
     *
     * @return void
     * @author Alistair Stead
     **/
    protected function _debug(array $debugData)
    {
        if ($this->getConfig()->isMethodDebug($this->getMethod())) {
            Mage::getModel('core/log_adapter', $this->getMethod() . '.log')
               ->setFilterDataKeys($this->_debugReplacePrivateDataKeys)
               ->log($debugData);
        }
    }
}

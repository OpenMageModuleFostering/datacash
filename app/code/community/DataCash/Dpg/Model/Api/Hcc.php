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

class DataCash_Dpg_Model_Api_Hcc extends DataCash_Dpg_Model_Api_Abstract
{

    const ADD_THREED_SECURE_SECTION = true;

    /**
     * @var DataCash_Dpg_Model_DataCash_Response
     */
    protected $_setupResponse;

    /**
     * Return the name of the method
     *
     * @return string
     * @author Alistair Stead
     **/
    public function getMethod()
    {
        return 'datacash_hcc';
    }
    
    private function useToken()
    {
        return $this->getToken() && $this->getToken()->getId();
    }
    
    private function getTokenString()
    {
        return $this->getToken()->getToken();
    }
    
    /**
     * If configured to include the CV2 information add the data to the request
     *
     * @return void
     * @author Alistair Stead
     **/
    protected function _addCv2Avs()
    {
        if ($this->getIsUseExtendedCv2()) {
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
            $request->addCv2ExtendedPolicy($this->getCv2ExtendedPolicy());
        }
    }
    
    /**
     * setUpHccSession
     * Prepares request for a Datacash HCC session setup request
     *
     * @author Hilary Boyce
     */
    public function setUpHccSession()
    {
        $request = $this->getRequest()
            ->addTransaction()
            ->addTxnDetails($this->getOrderNumber(), $this->getAmount(), $this->getCurrency())
            ->addHpsTxn($this->getPageSetId(),'setup', $this->getReturnUrl(), $this->getExpiryUrl(), 'hcc');
            
        if ($this->useToken()) {
            $request->addToken($this->getTokenString());
        }
        
        $this->call($request);
     }

    /**
     * requestPreAuth
     *
     * @param boolean $addThreeDSecureSection Adds the 3D secure section to the request. It is
     * required if 3D secure is turned on
     *
     * @author Hilary Boyce, Norbert Nagy
     */
     public function callPre($addThreeDSecureSection = false)
     {
        $request = $this->getRequest()
            ->addTransaction()
            ->addCardTxn('pre')
            ->addTxnDetails($this->getOrderNumber(), $this->getAmount(), $this->getCurrency(), 'ecomm');
        if ($this->getMpiReference()) {
            $request->addCardDetails($this->getMpiReference());
        } elseif ($this->getBypass3dsecure()) {
        	$request->addCardDetails($this->getDataCashCardReference(), 'from_mpi');
        } else {
            $request->addCardDetails($this->getDataCashCardReference(), 'from_hps');
        }

        if ($addThreeDSecureSection) {
            $this->_add3DSecure(false);
            $billingAddress = Mage::getSingleton('checkout/session')->getQuote()->getBillingAddress();
            $this->setBillingAddress($billingAddress);
        }

        $this->_addLineItems();
        $this->_addCv2Avs();
//        $this->_addRed();
        $this->addFraudScreening();

        $this->call($request);
     }

    /**
     * requestAuth
     *
     * @param boolean $addThreeDSecureSection Adds the 3D secure section to the request. It is
     * required if 3D secure is turned on     
     *
     * @author Hilary Boyce, Norbert Nagy
     */
     public function callAuth($addThreeDSecureSection = false)
     {
        $request = $this->getRequest()
            ->addTransaction()
            ->addCardTxn('auth')
            ->addTxnDetails($this->getOrderNumber(), $this->getAmount(), $this->getCurrency(), 'ecomm');
        if ($this->getMpiReference()) {
            $request->addCardDetails($this->getMpiReference());
        } elseif ($this->getBypass3dsecure()) {
        	$request->addCardDetails($this->getDataCashCardReference(), 'from_mpi');
        } else {
            $request->addCardDetails($this->getDataCashCardReference(), 'from_hps');
        }

        if ($addThreeDSecureSection) {
            $this->_add3DSecure(false);
            $billingAddress = Mage::getSingleton('checkout/session')->getQuote()->getBillingAddress();
            $this->setBillingAddress($billingAddress);
        }

        $this->_addLineItems();
        $this->_addCv2Avs();
//        $this->_addRed();
        $this->addFraudScreening();

        $this->call($request);
     }

    /**
     * Adds the Transaction and Historic TXN sections to the 3D Secure authorization request
     *
     * @param Dpg_Model_Service_Hcc $validator
     * @return void
     * @author Norbert Nagy
     */
    public function call3DAuthorization($validator)
    {
        $session = Mage::getSingleton('checkout/session');

        $state = $validator->getValidationState();

        $request = $this->getRequest()
            ->addTransaction()
            ->addHistoricTxn(
                'threedsecure_authorization_request',
                $state->getLookupTransactionId(),
                null,
                '3d',
                $state->getAuthenticatePaResPayload()
            );

        $this->call($request);
    }

    /**
     * Call the DataCash API to make an 3D Secure request
     *
     * @return void
     * @author Alistair Stead, Norbert Nagy
     **/
    public function call3DLookup()
    {
        $paymentAction = $this->getConfig()->getPaymentAction($this->getMethod());
        if ($paymentAction == Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE) {
            $this->callPre(self::ADD_THREED_SECURE_SECTION);
        } elseif ($paymentAction == Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE) {
            $this->callAuth(self::ADD_THREED_SECURE_SECTION);
        }    
    }
}

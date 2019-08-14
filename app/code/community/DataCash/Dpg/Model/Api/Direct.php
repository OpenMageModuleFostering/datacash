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

class DataCash_Dpg_Model_Api_Direct extends DataCash_Dpg_Model_Api_Abstract
{
    /**
     * Return the name of the method
     *
     * @return string
     * @author Alistair Stead
     **/
    public function getMethod()
    {
        return 'datacash_api';
    }

    /**
     * Call the DataCash API to make an authroization request
     *
     * @return void
     * @author Alistair Stead
     **/
    public function callAuth()
    {
        $this->_makeRequest('auth');
        Mage::dispatchEvent('datacash_dpg_model_api_direct_auth_response', array(
            'order_id' => $this->getOrderNumber(),
            'response' => $this->getResponse()));
    }

    /**
     * Call the DataCash API to make a pre auth request
     *
     * @return void
     * @author Alistair Stead
     **/
    public function callPre()
    {
        $this->_makeRequest('pre');
        Mage::dispatchEvent('datacash_dpg_model_api_direct_pre_response', array('response' => $this->getResponse()));
    }

    /**
     * Call the DataCash API to make a request of given transaction type.
     *
     * @param string $transactionMethod Transaction method to call.
     * @return void
     * @author Alistair Stead
     **/
    protected function _makeRequest($transactionMethod)
    {
        if (!in_array($transactionMethod, array('auth', 'pre'))) {
            throw new Exception('Card transaction type not recognised');
        }
        
        $orderNumber = $this->getUniqueOrderNumber();
        
        $request = $this->getRequest()
            ->addTransaction()
            ->addCardTxn($transactionMethod)
            ->addTxnDetails($orderNumber, $this->getAmount(), $this->getCurrency(), 'ecomm');

        if ($this->getMpiReference()) {
            $request->addCardDetails($this->getMpiReference());
        } else {
        	$cardRef = $this->getDataCashCardReference();
        	
        	if ($this->getToken()) {
            	$request->addTokencard(
            	    $this->getToken()->getToken(),
                    $this->getToken()->getExpiryDate(),
                    $this->getMaestroSoloIssueDate(),
                    $this->getMaestroSoloIssueNumber()
            	);
        	} else {
                $request->addCard(
                    $this->getCreditCardNumber(),
                    $this->getCreditCardExpirationDate(),
                    $this->getMaestroSoloIssueDate(),
                    $this->getMaestroSoloIssueNumber()
                );
        	}
        }

        $this->_addLineItems();
        $this->_addCv2Avs();

        $this->_addT3m(array(
            'previousOrders' => $this->getPreviousOrders(),
            'orderNumber' => $orderNumber,
            'orderItems' => $this->getOrderItems(),
            'forename' => $this->getForename(),
            'surname' => $this->getSurname(),
            'email' => $this->getCustomerEmail(),
            'remoteIp' => $this->getRemoteIp(),
            'orderItems' => $this->getOrderItems(),
            'billingAddress' => $this->getBillingAddress(),
            'shippingAddress' => $this->getShippingAddress()
        ));

        $this->addFraudScreening();

        $this->call($request);
    }

    /**
     * Call the DataCash API to make an 3D Secure request
     *
     * @return void
     * @author Alistair Stead
     **/
    public function call3DLookup()
    {
        $mpiMerchantReference = $this->getOrderNumber() . '-' . time();
        $request = $this->getRequest()
            ->addTransaction()
            ->addTxnDetails($mpiMerchantReference, $this->getAmount(), $this->getCurrency(), 'ecomm')
            ->addMpiTxn()
            ->addMpiCard(
                $this->getCreditCardNumber(),
                $this->getCreditCardExpirationDate(),
                $this->getMaestroSoloIssueDate(),
                $this->getMaestroSoloIssueNumber()
            );

        $this->_add3DSecure();

        $this->call($request);
    }
}

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
 * @author David Marrs
 * @version $Id$
 * @copyright DataCash, 11 April, 2011
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @package DataCash
 **/

class DataCash_Dpg_Model_Api_Directprereg extends DataCash_Dpg_Model_Api_Direct
{
    /**
     * Return the name of the method
     *
     * @return string
     * @author Alistair Stead
     **/
    public function getMethod()
    {
        return 'datacash_apiprereg';
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

        $request = $this->getRequest()
            ->addTransaction()
            ->addCardTxn($transactionMethod)
            ->addTxnDetails($this->getOrderNumber(), $this->getAmount(), $this->getCurrency(), 'ecomm');

        if ($this->getMpiReference()) {
            $request->addCardDetails($this->getMpiReference());
        } else {
        	$cardRef = $this->getDataCashCardReference();
            $request->addCardDetails($cardRef, 'preregistered');
        }

        $this->_addLineItems();
        $this->_addCv2Avs();

        $this->call($request);
    }


    public function call3DLookup()
    {
        $mpiMerchantReference = $this->getOrderNumber() . '-' . time();
        $request = $this->getRequest()
            ->addTransaction()
            ->addTxnDetails($mpiMerchantReference, $this->getAmount(), $this->getCurrency(), 'ecomm');

        $this->_add3DSecure();
        $request->addMpiTxn();
        $request->addCardDetails($this->getDataCashCardReference(), 'preregistered', 'MpiTxn');

        $this->call($request);
    }
}

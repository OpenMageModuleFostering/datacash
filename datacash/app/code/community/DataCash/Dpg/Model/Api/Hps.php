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

class DataCash_Dpg_Model_Api_Hps extends DataCash_Dpg_Model_Api_Abstract
{

    /**
     * Return the name of the method
     *
     * @return string
     * @author Alistair Stead
     **/
    public function getMethod()
    {
        return 'datacash_hps';
    }

    /**
     * setUpHpsSession
     * Prepares request for a Datacash HPS session setup request
     *
     * @author Hilary Boyce
     */
    public function setUpHpsSession()
    {
        $request = $this->getRequest()
            ->addTransaction()
            ->addTxnDetails($this->getOrderNumber(), $this->getAmount(), $this->getCurrency());
        $this->_add3DSecure();
        $request->addCardTxn($this->getAuthMethod(), null, 'hps');
        $this->_addCv2Avs();
        $request->addHpsTxn($this->getPageSetId(),'setup_full', $this->getReturnUrl(), $this->getExpiryUrl(), 'hps');

        $this->call($request);
    }

    /**
     * callTransactionStatus
     * Gets the status of the sent transaction
     *
     * @author Andy Thompson
     */
    public function callTransactionStatus()
    {
        $request = $this->getRequest()
            ->addTransaction()
            ->addHistoricTxn('query', $this->getDataCashCardReference(), null, 'hps');

        $this->call($request);
    }

    /**
     * Add the 3D Secure information to the request if enabled
     *
     * @param boolean $mpi Send the verify node or not. The verify node causes problem with mpi requests.
     * @return void
     * @author Alistair Stead/Hilary Boyce/Norbert Nagy
     **/
    protected function _add3DSecure($mpi = true)
    {
        $request = $this->getRequest();
        if ($this->getIsUse3DSecure()) {
            $request->addThreeDSecure(
                'yes',
                $this->getBaseUrl(),
                $this->getPurchaseDescription(),
                $this->getPurchaseDateTime(),
                $this->getBrowserData(),
                false
            );
        } else {
            $request->addThreeDSecure('no');
        }

    }


}
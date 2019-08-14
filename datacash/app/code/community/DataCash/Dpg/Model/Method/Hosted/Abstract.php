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

abstract class DataCash_Dpg_Model_Method_Hosted_Abstract
    extends DataCash_Dpg_Model_Method_Abstract
    implements DataCash_Dpg_Model_Method_Hosted_Interface
{
    /**
     * @var Varien_Object
     */
    protected $_dataCashSession = null;

    /**
     * Overload the parent validation method
     * This method is overloaded to allow the validation to be turned off
     * as card validation not required in magento for HCC
     *
     * @return DataCash_Dpg_Model_Method_Abstract
     *  @author Hilary Boyce
     **/
    public function validate()
    {
        //This must be after all validation conditions
        if ($this->getIsCentinelValidationEnabled()) {
            $this->getCentinelValidator()->validate($this->getCentinelValidationData());
        }
        return $this;
    }

    /**
     * Map data supplied by the request to the API
     *
     * This method will set all possible data no exceptions will be thrown even if data is missing
     * If the data is not supplied the error will be thrown by the API
     *
     * @return void
     * @author Andy Thompson
     **/
    protected function _mapRequestDataToApi($payment, $amount)
    {
        parent::_mapRequestDataToApi($payment, $amount);

        $session = $this->_getDataCashSession();
        $datacashSession = $session->getData($this->_code . '_session');

        $reference = $datacashSession['DatacashReference'];
        $this->_api->setDataCashCardReference($reference);
    }

    /**
     * Return data for Centinel validation
     *
     * @return Varien_Object
     */
    public function getCentinelValidationData()
    {
        $session = $this->_getDataCashSession();
        $datacashSession = $session->getData('datacash_hcc_session');
        $info = $this->getInfoInstance();
        $params = new Varien_Object();
        $params
            ->setPaymentMethodCode($this->getCode())
            ->setAmount($this->_getAmount())
            ->setCurrencyCode($this->_getCurrencyCode())
            ->setDataCashCardReference($datacashSession['DatacashReference'])
            ->setOrderNumber($this->_getOrderNumber())
            ->setIsUseCcv($this->hasVerification());

        if ($this->hasAdvancedVerification()) {
            $params->setIsUseExtendedCv2(true)
                   ->setCv2ExtendedPolicy($this->_extendedPolicy());
        }
        return $params;
    }
}
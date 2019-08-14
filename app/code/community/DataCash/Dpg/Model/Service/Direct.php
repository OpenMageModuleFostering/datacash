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
 
class DataCash_Dpg_Model_Service_Direct extends DataCash_Dpg_Model_Service_Abstract
{

    protected function _getValidationStateModel($cardType)
    {
        return Mage::getModel('dpg/service_state_datacash');
    }
    
    /**
     * Process lookup validation and init new validation state model
     *
     * @param Varien_Object $data
     */
    public function lookup($data)
    {
        $newChecksum = $this->_generateChecksum(
            $data->getPaymentMethodCode(),
            $data->getCardType(),
            $data->getCardNumber(),
            $data->getCardExpMonth(),
            $data->getCardExpYear(),
            $data->getAmount(),
            $data->getCurrencyCode()
        );

        $validationState = $this->_initValidationState('datacash', $newChecksum);

        $api = $this->_getApi();
        // Map the data onto the API object
        $api->setOrderNumber($data->getOrderNumber())
            ->setAmount($data->getAmount())
            ->setCreditCardNumber($data->getCardNumber())
            ->setCreditCardExpirationDate($this->_formatDate($data->getCardExpMonth(), $data->getCardExpYear()))
            ->setDataCashCardReference($data->getDataCashCardReference())
            ->setCurrency($data->getCurrencyCode());

        $this->_doLookup($api, $validationState);
    }
}


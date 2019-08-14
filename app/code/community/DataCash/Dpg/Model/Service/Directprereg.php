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
 
class DataCash_Dpg_Model_Service_Directprereg extends DataCash_Dpg_Model_Service_Direct 
{

    public function __construct()
    {
        $this->_apiType = 'dpg/api_directprereg';
    }
	 
    /**
     * Validate payment data
     *
     * This check is performed on payment information submission, as well as on placing order.
     * Workflow state is stored validation state model
     *
     * @param Varien_Object $data
     * @throws Mage_Core_Exception
     */
    public function validate($data)
    {
        $newChecksum = $this->_generateChecksum(
            $data->getDataCashCardReference(),
            $data->getAmount(),
            $data->getCurrencyCode(),
            '','','',''
        );

        $validationState = $this->_getValidationState('datacash');
        if (!$validationState) {
            $this->_resetValidationState();
            return;
        }

        // check whether is authenticated before placing order
        if ($this->getIsPlaceOrder()) {
            if ($validationState->getChecksum() != $newChecksum) {
                Mage::throwException(Mage::helper('centinel')->__('Payment information error. Please start over.'));
            }
            if ($validationState->isAuthenticateSuccessful() || $validationState->getLookupBypass3dsecure()) {
                return;
            }
            Mage::throwException(Mage::helper('centinel')->__('Please verify the card with the issuer bank before placing the order.'));
        } else {
            if ($validationState->getChecksum() != $newChecksum || !$validationState->isLookupSuccessful()) {
                $this->lookup($data);
                $validationState = $this->_getValidationState();
            }
            if ($validationState->isLookupSuccessful()|| $validationState->getLookupBypass3dsecure()) {
                return;
            }
            Mage::throwException(Mage::helper('centinel')->__('This card has failed validation and cannot be used.'));
        }
    }
	 
    /**
     * Process lookup validation and init new validation state model
     *
     * @param Varien_Object $data
     */
    public function lookup($data)
    {
        $newChecksum = $this->_generateChecksum(
            $data->getDataCashCardReference(),
            $data->getAmount(),
            $data->getCurrencyCode(),
            '','','',''
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


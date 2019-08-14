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

class DataCash_Dpg_Model_Service_Hcc extends DataCash_Dpg_Model_Service_Abstract
{
    /**
     * The API class to use
     *
     * @var string
     */
    protected $_apiType = 'dpg/api_hcc';

    /**
     * Create and return validation state model for card type
     *
     * @param string $cardType
     * @return Mage_Centinel_Model_StateAbstract
     */
    protected function _getValidationStateModel($cardType)
    {
        return Mage::getModel('dpg/service_state_datacash');
    }

    /**
     * Return validation state model
     *
     * @param string $cardType
     * @return Mage_Centinel_Model_StateAbstract
     */
    protected function _getValidationState($cardType = null)
    {
        $model = $this->_getValidationStateModel('datacash');
        $model->setDataStorage($this->_getSession());
        $this->_validationState = $model;
        return $this->_validationState;
    }

    /**
     * Process authenticate validation
     *
     * @param Varien_Object $data
     * @author Norbert Nagy
     */
    public function authenticate($data)
    {
        if (!$this->getIsUseCcv()) {
            parent::authenticate($data);
        } else {
            $validationState = $this->_getValidationState();
            if (!$validationState || $data->getTransactionId() != $validationState->getLookupTransactionId()) {
                throw new Exception('Authentication impossible: transaction id or validation state is wrong.');
            }

            $result = new Varien_Object();
            $result->setPaResPayload($data->getPaResPayload());

            $validationState->setAuthenticateResult($result);
            if (!$validationState->isAuthenticateSuccessful()) {
                $this->reset();
            }
        }
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
            $data->getPaymentMethodCode(),
            $data->getAmount(),
            $data->getDataCashCardReference(),
            null,
            null,
            null,
            null
        );

        $validationState = $this->_getValidationStateModel('datacash');
        $validationState->setDataStorage($this->_getSession());
        if (!$validationState) {
            $this->_resetValidationState();
            return;
        }

        // check whether is authenticated before placing order
        if ($this->getIsPlaceOrder()) {
            if ($validationState->getChecksum() != $newChecksum) {
                Mage::throwException(Mage::helper('centinel')->__('Payment information error. Please start over.'));
            }
        } else {
            if ($validationState->getChecksum() != $newChecksum) {
                $this->lookup($data);
            }
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
            $data->getPaymentMethodCode(),
            $data->getAmount(),
            $data->getDataCashCardReference(),
            null,
            null,
            null,
            null
        );

        $validationState = $this->_initValidationState('datacash', $newChecksum);

        $api = $this->_getApi();
        // Map the data onto the API object
        $api->setOrderNumber($data->getOrderNumber())
            ->setAmount($data->getAmount())
            ->setDataCashCardReference($data->getDataCashCardReference())
            ->setCurrency($data->getCurrencyCode())
            ->setIsUseCcv($this->getIsUseCcv());

        if ($data->getIsUseExtendedCv2()) {
            $api->setIsUseExtendedCv2(true)
                ->setCv2ExtendedPolicy($data->getCv2ExtendedPolicy());
        }

        $this->_doLookup($api, $validationState);
    }
}

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

class DataCash_Dpg_Model_Config extends Varien_Object
{
    const TRANSACTION_TYPES = 'global/datacash/transaction/types';

    const RSG_REJECT = 2;
    const RSG_RELEASE = 0;
    const RSG_HOLD = 1;
    const RSG_INV = 9;

    public $_t3mPaymentInfo = array(
        0 => 'Release',
        1 => 'Hold',
        2 => 'Reject',
        9 => 'Under Investigation',
    );
    
    public $_t3mResponseMap = array(
        't3m_score' => 'score',
        't3m_recommendation' => 'recommendation',
    );

    /**
     * Return the internal storeId
     *
     * @return int
     * @author Alistair Stead
     **/
    public function getStoreId()
    {
        if (is_null($this->getStorId())) {
            $this->setStoreId(Mage::app()->getStore()->getId());
        }

        return $this->getStorId();
    }

    protected function _getStoreFlag($method, $setting)
    {
        return Mage::getStoreConfigFlag("payment/{$method}/{$setting}", $this->getStoreId());
    }

    protected function _getStoreSetting($method, $setting)
    {
        return Mage::getStoreConfig("payment/{$method}/{$setting}", $this->getStoreId());
    }

    /**
     * Retrieve array of credit card types
     *
     * @return array
     */
    public function getTansactionTypes()
    {
        $_types = Mage::getConfig()->getNode(self::TRANSACTION_TYPES)->asArray();

        $types = array();
        foreach ($_types as $data) {
            if (isset($data['code']) && isset($data['name'])) {
                $types[$data['code']] = $data['name'];
            }
        }

        return $types;
    }

    /**
     * Check whether method active in configuration and supported for merchant country or not
     *
     * @param string $method Method code
     * @author Alistair Stead
     */
    public function isMethodActive($method)
    {
        if (Mage::getStoreConfigFlag("payment/{$method}/active", $this->getStoreId()))
        {
            return true;
        }
        return false;
    }

    /**
     * Is the payment method configured to run in sandbox mode
     *
     * @param string $method Method code
     * @return bool
     * @author Alistair Stead
     **/
    public function isMethodSandboxed($method)
    {
        $option = (int)Mage::getStoreConfig("payment/{$method}/sandbox", $this->getStoreId());
        return in_array($option, array(1, 2));
    }

    /**
     * Is the payment method configured to run in sandbox mode
     *
     * @param string $method Method code
     * @return bool
     * @author Alistair Stead
     **/
    public function getEndpoint($method)
    {
        $option = (int)Mage::getStoreConfig("payment/{$method}/sandbox", $this->getStoreId());
        $endpoints = array(
            $this->_getStoreSetting($method, 'live_endpoint'),
            $this->_getStoreSetting($method, 'testing_endpoint'), // test mode 1
            $this->_getStoreSetting($method, 'accreditation_cnp_endpoint'), // test mode 2
            $this->_getStoreSetting($method, 'accreditation_acq_endpoint'), // test mode 3
        );
        return $endpoints[$option];
    }

    /**
     * Is the debugging enabled
     *
     * @param string $method Method code
     * @return bool
     * @author Alistair Stead
     **/
    public function isMethodDebug($method)
    {
        if (Mage::getStoreConfigFlag("payment/{$method}/debug", $this->getStoreId()))
        {
            return true;
        }
        return false;
    }

    /**
     * Should order line items be transmitted
     *
     * @param string $method Method code
     * @return bool
     * @author Alistair Stead
     **/
    public function isLineItemsEnabled($method)
    {
        if ($method == "datacash_api") { // XXX: make it more elegant
            return false;
        }
        if (Mage::getStoreConfigFlag("payment/{$method}/line_items_enabled", $this->getStoreId()))
        {
            return true;
        }
        return false;
    }

    /**
     * Should CV2 information be transmitted
     *
     * @param string $method Method code
     * @return bool
     * @author Alistair Stead
     **/
    public function isUseCcv($method)
    {
        if (Mage::getStoreConfigFlag("payment/{$method}/useccv", $this->getStoreId()) == '1')
        {
            return true;
        }
        return false;
    }

    /**
     * Should CV2 Extended Policy information be transmitted
     *
     * @param string $method Method code
     * @return bool
     * @author Alistair Stead
     **/
    public function isUseAdvancedCcv($method)
    {
        if (Mage::getStoreConfigFlag("payment/{$method}/useccv_advanced", $this->getStoreId()) == '1')
        {
            return true;
        }
        return false;
    }

    /**
     * Should 3D Secure inormation be transmitted
     *
     * @param string $method Method code
     * @return bool
     * @author Alistair Stead
     **/
    public function getIsCentinelValidationEnabled($method)
    {
        if (Mage::getStoreConfigFlag("payment/{$method}/centinel", $this->getStoreId()) == '1')
        {
            return true;
        }
        return false;
    }

    /**
     * Decrypt and return the Merchant password for the DataCash gateway
     *
     * @return string
     * @author Alistair Stead
     **/
    public function getApiPassword($method)
    {
        $password = Mage::getStoreConfig("payment/{$method}/merchant_password", $this->getStoreId());
        // Decrypt the marchant password in order to transmit it as part of the request
       return Mage::helper('core')->decrypt($password);
    }

    /**
     * Return the Merchant ID for the DataCash gateway
     *
     * @return string
     * @author Alistair Stead
     **/
    public function getApiMerchantId($method)
    {
       return Mage::getStoreConfig("payment/{$method}/merchant_id", $this->getStoreId());
    }

    /**
     * Return the configured payment action
     *
     * @return string
     * @author Alistair Stead
     **/
    public function getPaymentAction($method)
    {
       return Mage::getStoreConfig("payment/{$method}/payment_action", $this->getStoreId());
    }

    /**
     * Should processing continue for 3DSecure response code
     *
     * @param string $method Method code
     * $param string $code DRG response code
     * @return bool
     * @author Hilary Boyce
     **/
    public function continueBehaviour($method, $code)
    {
        if (Mage::getStoreConfigFlag("payment/{$method}/threedsecure_behaviour_{$code}", $this->getStoreId()) == '1')
        {
            return true;
        }
        return false;
    }

    public function getIsAllowedFraudScreening($method = 'datacash_api')
    {
        if (Mage::getStoreConfigFlag("payment/{$method}/allow_fraud_screening", $this->getStoreId()))
        {
            return true;
        }
        return false;
    }

    public function getFraudScreeningMode($method = 'datacash_api')
    {
        return Mage::getStoreConfig("payment/{$method}/rsg_service_mode", $this->getStoreId());
    }

    public function getIsAllowedTokenizer($method = 'datacash_api')
    {
        if (Mage::getStoreConfigFlag("payment/{$method}/allow_tokenizer", $this->getStoreId()))
        {
            return true;
        }
        return false;
    }

    public function getIsAllowedT3m($method = 'datacash_api')
    {
        $mode = Mage::getStoreConfig("payment/{$method}/rsg_service_mode", $this->getStoreId());
        return $mode == 't3m';
    }

    public function getT3mCallBackUrl($method = 'datacash_api')
    {
        return Mage::getUrl('datacash/t3m/index', array('_secure' => true));
    }

    public function getAllowRsgCallback($method = 'datacash_api')
    {
        if (Mage::getStoreConfigFlag("payment/{$method}/rsg_callbacks", $this->getStoreId())) {
            return true;
        }
        return false;
    }
    
    public function getRsgCallBackUrl($method = 'datacash_api')
    {
        return Mage::getUrl('datacash/rsg/index', array('_secure' => true));
    }
    
    public function isRsgAutoUpdateEnabled($method = 'datacash_api')
    {
        if (Mage::getStoreConfigFlag("payment/{$method}/rsg_callbacks_autoupdate", $this->getStoreId())) {
            return true;
        }
        return false;
    }
}

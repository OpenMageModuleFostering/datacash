<?php
/**
 * 3D Secure Validation Model
 */
class DataCash_Dpg_Model_Service extends Mage_Centinel_Model_Service
{
    /**
     * Return validation api model
     *
     * @return Mage_Centinel_Model_Api
     */
    protected function _getApi()
    {
        if (!is_null($this->_api)) {
            return $this->_api;
        }

        // Chnage to using the DataCash Direct Api
        $this->_api = Mage::getSingleton('dpg/api_direct');
        $config = $this->_getDpgConfig();
        $this->_api->setMerchantId($config->getApiMerchantId($this->getCode()));
        $this->_api->setMerchantPassword($config->getApiPassword($this->getCode()));
        // Set the sandboxed state
        $this->_api->setIsSandboxed($config->isMethodSandboxed($this->getCode()));
        
        $request = Mage::app()->getRequest();
        $this->_api->setBaseUrl(Mage::getBaseUrl())
            ->setPurchaseDescription('Purchase from ' . Mage::app()->getStore()->getGroup()->getName())
            ->setPurchaseDateTime(date('Y-m-d H:i:s'))
            ->setBrowserData(
            array(
                'device_category' => '0', // TODO set the device category dynamically
                'accept_headers' => $request->getHeader('ACCEPT'),
                'user_agent' => $request->getHeader('USER_AGENT')
            )
        );

        return $this->_api;
    }
    
    /**
     * Public accessor for the 3D secure validation state
     *
     * @return Mage_Centinel_Model_StateAbstract
     * @author Alistair Stead
     **/
    public function getValidationState()
    {
        return $this->_validationState;
    }

    /**
     * Process authenticate validation
     *
     * @param Varien_Object $data
     */
    public function authenticate($data)
    {
        $validationState = $this->_getValidationState();
        if (!$validationState || $data->getTransactionId() != $validationState->getLookupTransactionId()) {
            throw new Exception('Authentication impossible: transaction id or validation state is wrong.');
        }
        $api = $this->_getApi();
        $api->setDataCashReference($data->getTransactionId());
        $api->setParesMessage($data->getPaResPayload());
        try {
            $api->call3DValidAuth();
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }
        
        $response = $api->getResponse();
        $result = new Varien_Object();
        // Map the response to the result
        $paResStatus = ($response->getData('CardTxn/ThreeDSecure/cardholder_registered') == 'yes')? 'Y' : 'N';
        $errorNumber = ($response->getStatus() == '1')? '0' : $response->getStatus();
        $result->setPaResStatus($paResStatus);
        $result->setEciFlag($response->getData('CardTxn/ThreeDSecure/eci'));
        $result->setXid($response->getData('CardTxn/ThreeDSecure/xid'));
        $result->setCavv($response->getData('CardTxn/ThreeDSecure/security_code'));
        $result->setErrorNo($errorNumber);
        $result->setSignatureVerification('Y');
        $result->setTransactionId($response->getDatacashReference());
        $result->setReason($response->getReason());
               
        $validationState->setAuthenticateResult($result);
        if (!$validationState->isAuthenticateSuccessful()) {
            $this->reset();
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
            $data->getCardType(),
            $data->getCardNumber(),
            $data->getCardExpMonth(),
            $data->getCardExpYear(),
            $data->getAmount(),
            $data->getCurrencyCode()
        );

        $validationState = $this->_initValidationState($data->getCardType(), $newChecksum);

        $api = $this->_getApi();
        // Map the data onto the API object
        $api->setOrderNumber($data->getOrderNumber())
            ->setAmount($data->getAmount())
            ->setCreditCardNumber($data->getCardNumber())
            ->setCreditCardExpirationDate($this->_formatDate($data->getCardExpMonth(), $data->getCardExpYear()))
            ->setCurrency($data->getCurrencyCode());
        try {
            $api->call3DLookup();
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }
        // Map the response data to the result for the centinel state objects
        $response = $api->getResponse();
        $enrolled = 'U';
        switch ($response->getStatus()) {
            case '150':
                $enrolled = 'Y';
                break;
                
            case '162':
                $enrolled = 'N';
                break;
            
            default:
                break;
        }

        $errorNo = ($response->getStatus() == '150')? '0' : $response->getStatus();
        $result = new Varien_Object();
        $result->setAcsUrl($response->getData('CardTxn/ThreeDSecure/acs_url'));
        $result->setPayload($response->getData('CardTxn/ThreeDSecure/pareq_message'));
        $result->setErrorNo($errorNo);
        $result->setErrorDesc($response->getReason());
        $result->setReason($response->getReason());
        $result->setMode($response->getMode());
        $result->setTransactionId($response->getDatacashReference());
        $result->setEnrolled($enrolled);
        
        $validationState->setLookupResult($result);
    }
    
    /**
     * Return value from section of centinel config
     *
     * @param string $path
     * @return string
     */
    protected function _getDpgConfig()
    {
        $config = Mage::getSingleton('dpg/config');
        return $config->setStore($this->getStore());
    }
    
    /**
     * Format the supplied dates to be sent to the API
     *
     * @return string 00/00
     * @author Alistair Stead
     **/
    protected function _formatDate($month, $year)
    {
        return sprintf(
            '%02d/%02d', 
            substr($month, -2, 2),
            substr($year, -2, 2)
        );
    }
}


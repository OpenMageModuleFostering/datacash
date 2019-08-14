<?php
abstract class DataCash_Dpg_Model_Service_Abstract extends Mage_Centinel_Model_Service
{
    /**
     * The API class to use
     * 
     * @var string
     */
    protected $_apiType = 'dpg/api_direct';

    /**
     * Public accessor for the 3D secure validation state
     *
     * @return Mage_Centinel_Model_StateAbstract
     * @author Alistair Stead
     **/
    public function getValidationState($cardType = null)
    {
        return $this->_validationState;
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
            $data->getCardType(),
            $data->getCardNumber(),
            $data->getCardExpMonth(),
            $data->getCardExpYear(),
            $data->getAmount(),
            $data->getCurrencyCode()
        );

      //  $validationState = $this->_getValidationState($data->getCardType());
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
            if ($validationState->isAuthenticateSuccessful()|| $validationState->getLookupBypass3dsecure()) {
                return;
            }
            Mage::throwException(Mage::helper('centinel')->__('Please verify the card with the issuer bank before placing the order.'));
        } else {
            if ($validationState->getChecksum() != $newChecksum || !$validationState->isLookupSuccessful()) {
            	$this->lookup($data);
                $validationState = $this->_getValidationState();
            }
            if ($validationState->isLookupSuccessful() || $validationState->getLookupBypass3dsecure()) {
                return;
            }
            Mage::throwException(Mage::helper('centinel')->__('This card has failed validation and cannot be used.'));
        }
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
        if (!$response->isSuccessful()) {
            Mage::throwException($response->getReason());
        }
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
     * Return validation api model
     *
     * @return DataCash_Dpg_Model_Api_Abstract
     */
    protected function _getApi()
    {
        if (!is_null($this->_api)) {
            return $this->_api;
        }
        $helper = Mage::helper('dpg');
        // Change to using the DataCash Direct Api
        $this->_api = Mage::getSingleton($this->_apiType);
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
                'device_category' => $helper->getDeviceType($request->getHeader('USER_AGENT')),
                'accept_headers' => $request->getHeader('ACCEPT'),
                'user_agent' => $request->getHeader('USER_AGENT')
            )
        );

        $this->_api->setUseFraudScreening($this->getUseFraudScreening());
        $this->_api->setFraudScreeningPolicy($this->getFraudScreeningPolicy());

        return $this->_api;
    }
    
    /**
     * Calls 3-D Secure MPI call and interrogates response.  
     * Action depends on configuration for some response values.
     * 
     * @param DataCash_Dpg_Model_Api_Abstract $api
     * @param Mage_Centinel_Model_StateAbstract $validationState
     */
    protected function _doLookup($api, $validationState) 
    {
        try {
            $api->call3DLookup();
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }
        // Map the response data to the result for the centinel state objects
        $response = $api->getResponse();
        
    	$enrolled = 'U';
        $continue = false;
        switch ($response->getStatus()) {
            case '150':
                $enrolled = 'Y';
                $continue = true;
                break;
                
            case '162':
                $enrolled = 'N';
                break;
            case '159':
            case '160':
            case '173':
            case '187':
            	// check if config set to allow processing to continue
            	$config = $this->_getDpgConfig();            	
            	$method = $api->getMethod();
            	if ($config->continueBehaviour($method, $response->getStatus())){
            		$continue = true;
            	} else {
            		Mage::throwException(sprintf("Unable to authenticate transaction - (%s) %s", $response->getStatus(), $response->getReason()));
            	}
                break;                
            case '161':
            case '186':
            	Mage::throwException(sprintf("Unable to authenticate transaction - (%s) %s", $response->getStatus(), $response->getReason()));
                break;    
            default:
                break;
        }
        
		$errorNo = ($continue) ? '0' : $response->getStatus();
        $result = new Varien_Object();

        $result->setAcsUrl($response->getData('CardTxn/ThreeDSecure/acs_url'));
	    $result->setPayload($response->getData('CardTxn/ThreeDSecure/pareq_message'));
	    $result->setErrorNo($errorNo);
	    $result->setErrorDesc($response->getReason());
	    $result->setReason($response->getReason());
	    $result->setMode($response->getMode());
	    $result->setTransactionId($response->getDatacashReference());
	    $result->setEnrolled($enrolled);
	        	
        if ($continue && $response->getStatus()!== '150'){
        	$result->setBypass3dsecure(true);        	
        } else {        	
	        $result->setBypass3dsecure(false);
        }        
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
<?php
class DataCash_Dpg_Model_Tokencard extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('dpg/tokencard');
    }
    
    public function getCardDisplay()
    {
        return "{$this->getPan()} {$this->getScheme()}";
    }
    
    public function tokenizeResponse(Mage_Sales_Model_Order_Payment $payment, DataCash_Dpg_Model_Datacash_Response $response)
    {
        $customer = $payment->getOrder()->getCustomer();
        if (!$customer || !$customer->getId()) {
            return;
        }
        
        $txn_id = $response->getData("datacash_reference");
        $method = $payment->getMethodInstance();
        
        $txnData = $method->getTxnData($txn_id);
        
        $tokenMap = array(
            'Card/expirydate' => 'expiry_date',
            'Card/pan' => 'pan',
            'Card/scheme' => 'scheme',
            'Card/token' => 'token',
            'Card/issuer' => 'issuer',
            'Card/country' => 'country',
            'Card/card_category' => 'card_category'
        );
        
        $this->setId(null);
        $this->setCustomerId($customer->getId());
        $this->setMethod($method->getCode());
        
        foreach($tokenMap as $responseKey => $modelKey) {
            $this->setData($modelKey, $txnData->getData('QueryTxnResult/'.$responseKey));
        }
        
        $this->save();
    }
}
<?php

class DataCash_Dpg_Block_Form_Iframe extends Mage_Payment_Block_Form
{

    /**
     * Varien constructor
     */
    protected function _construct()
    {
        $this->setTemplate('datacash/iframe/form.phtml');
        parent::_construct();
    }
    
    public function getFrameUrl()
    {
        switch ($method = $this->getMethodCode()) {
            case 'datacash_hcc':
                $method = 'hcc';
                break;
            case 'datacash_hps':
                $method = 'hps';
                break;
            default;
                Mage::throwException('Method "'.$method.'" not supported by DataCash iframe');
        }
        return Mage::getUrl('checkout/hosted/start', array('_secure' => true, 'method' => $method));
    }
    
    public function getAuthenticationStart()
    {
        return true;
    }
    
    public function canUseTokens()
    {
        $config = Mage::getSingleton('dpg/config');    
        return Mage::getSingleton('customer/session')->isLoggedIn() && $config->getIsAllowedTokenizer($this->getMethodCode());
    }
    
    public function getSessionTokens()
    {
        $session = Mage::getSingleton('checkout/session');
        
        if ($session && $session->getQuote() && $session->getQuote()->getCustomer()) {
            $customer = $session->getQuote()->getCustomer();
            
            return Mage::getModel('dpg/tokencard')
                ->getCollection()
                ->addCustomerFilter($customer)
                ->addMethodFilter($this->getMethodCode());
        }
        return null;
    }    
}

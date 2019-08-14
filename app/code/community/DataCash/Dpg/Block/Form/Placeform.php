<?php


class Datacash_Dpg_Block_Form_Placeform extends Mage_Core_Block_Template
{


    /**
     * Get checkout session namespace
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Datacash HCC payment APi instance
     *
     * @return DataCash_Dpg_Model_Api_Hcc
     * @author Hilary Boyce
     */
    protected function _getApi()
    {
        $api = Mage::getSingleton('dpg/api_hcc');
        var_dump("In placeform", $api);
        return $api;
    }

    /**
     * Return order instance with loaded information by increment id
     *
     * @return Mage_Sales_Model_Order
     */
    protected function _getOrder()
    {
        if ($this->getOrder()) {
            $order = $this->getOrder();
        } else if ($this->getCheckout()->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($this->getCheckout()->getLastRealOrderId());
        } else {
            return null;
        }
        return $order;
    }
    
    /**
     *  Return payment instance
     *  @param Mage_Sales_Model_Order
     *  
     */
    protected function _getPayment($order)
    {
        return $order->getPayment();    
    }

    /**
     * Get getHpsSessionId for form by using Hcc payment api
     *
     * @return string
     */
    public function getHpsSessionId()
    { 
        return $this->_getPayment($this->_getOrder())->getMethodInstance()->getInfoInstance()->getAdditionalInformation('SessionId');
    }

    /**
     * Getting gateway url
     *
     * @return string
     */
    public function getHpsUrl()
    {
        return $this->_getPayment($this->_getOrder())->getMethodInstance()->getInfoInstance()->getAdditionalInformation('HpsUrl');
    }
}

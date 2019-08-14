<?php
class DataCash_Dpg_ReviewController extends DataCash_Dpg_Controller_Abstract
{
    public function indexAction()
    {
        try {
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            if (!$quote || !$quote->getPayment() || !$quote->getPayment()->getMethodInstance()) {
                throw new Exception("Quote not available");
            }
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::getSingleton('checkout/session')->addError($e->getMessage());
            $this->_redirect('checkout/onepage/success');
            return;
        }
        $this->loadLayout()->renderLayout();
    }
}
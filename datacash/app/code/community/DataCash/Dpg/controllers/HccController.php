<?php
/**
* Stub controller that will handle the Hosted Card Capture return messages
*/
class DataCash_Dpg_HccController extends DataCash_Dpg_Controller_Abstract
{
    
    /**
     * Get singleton of Checkout Session Model
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }
    
    /**
     * placeformAction
     * Set up and display forwarding form, takes users to datacash card entry form 
     * 
     * @author Hilary Boyce
     */
    public function placeformAction()
    {
        $lastIncrementId = $this->_getCheckout()->getLastRealOrderId();
        if ($lastIncrementId) {
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($lastIncrementId);
            if ($order->getId()) {
                $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
                     Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
                    'The customer was redirected to Datacash HCC.');
                $order->save();
            }
        }

        $this->_getCheckout()->getQuote()->setIsActive(false)->save();

        $this->_getCheckout()->setDatacashQuoteId($this->_getCheckout()->getQuoteId());
        $this->_getCheckout()->setDatacashLastSuccessQuoteId($this->_getCheckout()->getLastSuccessQuoteId());
        $this->_getCheckout()->clear();
        
        $this->loadLayout();
        $this->renderLayout();
    }
    
    /**
     * processAction
     * Called on return from datacash card collection screen.  Need to authorise or authorise/capture next
     */
    public function processAction()
    {
        $orderId = $this->getRequest()->getParam('ref');
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($orderId);
        $payment = $order->getPayment();
        $method = $payment->getMethodInstance(); 
        $paymentAction = $this->getConfig()->getPaymentAction('datacash_hcc');       

        try{
            switch(trim($paymentAction)){
                case "authorize":
                    $method->requestAuthorisation($payment);
                    break;
                case "authorize_capture":
                    $method->requestCapture($payment);
                    break;
                default:
                    Mage::throwException("Invalid payment type: ".$paymentAction);       
            }
            $session = $this->_getCheckout();
            $quoteId = $order->getQuoteId();
            $session->setLastSuccessQuoteId($quoteId);
            $this->_redirect('checkout/onepage/success');
          //  return;
        } catch (Mage_Core_Exception $e) {
            Mage::log("DataCash_Dpg_HccController::exception ". $e->getMessage());
            $this->_getCheckout()->addError($e->getMessage());
            $order->cancel();
            $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, false, "Failed Datacash HccPayment ". $e->getMessage());
            $order->save();
            $this->_redirect('checkout/cart');
        }
        
    }
    
    /**
     * expiredAction
     * called from Datacash card collection screen if card collection expired
     * 
     * @author Hilary Boyce
     */
    public function expiredAction()
    {
        Mage::log("DataCash_Dpg_HccController::expired ");
        $session = $this->_getCheckout();
        $orderId = $this->getRequest()->getParam('ref');
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($orderId); 
        $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true, 'Datacash Session expired'); 

        $quoteId = $order->getQuoteId();
       // set quote active so basket items visible
        if ($quoteId) {
            $quote = Mage::getModel('sales/quote')->load($quoteId);
            if ($quote->getId()) {
                $quote->setIsActive(true)->save();
                $session->setQuoteId($quoteId);
            }
        }  
        $session->addError("The Datacash Payment Gateway session has expired, please try again");
        $this->_redirect('checkout/cart');
           
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

}

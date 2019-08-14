<?php
class DataCash_Dpg_Block_Centinel_Review extends Mage_Core_Block_Template
{
    protected $_quote = false;

    /**
     * Return checkout session object
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Return checkout quote object
     *
     * @return Mage_Sale_Model_Quote
     */
    public function getQuote()
    {
        if (!$this->_quote) {
            $this->_quote = $this->getCheckoutSession()->getQuote();
        }
        return $this->_quote;
    }

    /**
     * Return quote shipping address
     *
     * @return Mage_Sales_Model_Quote_Address
     */
    public function getShippingAddress()
    {
        if ($this->getQuote()->getIsVirtual()) {
            return false;
        }
        return $this->getQuote()->getShippingAddress();
    }

    /**
     * Return formatted shipping price
     *
     * @param float $price
     * @param bool $isInclTax
     *
     * @return bool
     */
    public function getShippingPrice($price, $isInclTax)
    {
        return $this->_formatPrice($this->helper('tax')->getShippingPrice($price, $isInclTax, $this->getShippingAddress()));
    }

    /**
     * Format price base on store convert price method
     *
     * @param float $price
     * @return string
     */
    protected function _formatPrice($price)
    {
        return $this->getQuote()->getStore()->convertPrice($price, true);
    }
}
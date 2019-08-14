<?php
class DataCash_Dpg_Block_Centinel_Review_Shipping extends Mage_Checkout_Block_Onepage_Shipping
{
    public function setAddressForReview($address)
    {
        $this->_address = $address;
        return $this;
    }
}
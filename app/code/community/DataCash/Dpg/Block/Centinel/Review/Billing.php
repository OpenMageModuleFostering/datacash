<?php
class DataCash_Dpg_Block_Centinel_Review_Billing extends Mage_Checkout_Block_Onepage_Billing
{
    public function setAddressForReview($address)
    {
        $this->_address = $address;
        return $this;
    }
}
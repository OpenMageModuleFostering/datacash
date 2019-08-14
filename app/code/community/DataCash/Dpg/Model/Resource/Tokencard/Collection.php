<?php
class DataCash_Dpg_Model_Resource_Tokencard_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract {

    protected function _construct()
    {
        $this->_init('dpg/tokencard');
    }

    public function addCustomerFilter($customer) {
        if ($customer instanceof Mage_Customer_Model_Customer) {
            $this->addFieldToFilter('customer_id', $customer->getId());
        } elseif (is_numeric($customer)) {
            $this->addFieldToFilter('customer_id', $customer);
        } elseif (is_array($customer)) {
            $this->addFieldToFilter('customer_id', $customer);
        }
        return $this;
    }
    
    public function addMethodFilter($method)
    {
    	$this->addFieldToFilter('method', $method);
    	return $this;
    }
    
	public function addIdFilter($tokencardId)
	{
    	$this->addFieldToFilter('id', $tokencardId);
    	return $this;
	}        
}
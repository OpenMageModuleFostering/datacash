<?php
class DataCash_Dpg_Block_Adminhtml_Sales_Order_Risk extends Mage_Adminhtml_Block_Template
{
    protected $_instanceCollection = null;

    /**
     * _construct function.
     * 
     * @access public
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('datacash/sales/order/risk.phtml');
    }
    
    /**
     * getRiskData function.
     * 
     * @access public
     * @return collection
     */
    public function getRiskData()
    {
        if ($this->_instanceCollection === null) {
            $orderId = $this->getOrder()->getId();
            $collection = Mage::getResourceModel('dpg/risk_collection')
                ->addFieldToFilter('order_id', $orderId);
            
            $this->_instanceCollection = array();
            foreach($collection as $item) {
                $this->_instanceCollection[] = Mage::getModel('dpg/risk')->getTypeInstanceFromItem($item);
            }
        }
        return $this->_instanceCollection;
    }
    
    /**
     * hasRiskData function.
     * 
     * @access public
     * @return boolean
     */
    public function hasRiskData()
    {
        return count($this->getRiskData()) > 0;
    }
}
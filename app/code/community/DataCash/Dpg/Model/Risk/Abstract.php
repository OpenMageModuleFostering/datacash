<?php
class DataCash_Dpg_Model_Risk_Abstract extends Mage_Core_Model_Abstract
{
    /**
     * _construct function.
     * 
     * @access protected
     * @return void
     */
    protected function _construct()
    {
        $this->_init('dpg/risk');
    }

    /**
     * getAdditionalMessages function.
     * 
     * @access public
     * @return array
     */
    public function getAdditionalMessages()
    {
        $messages = (array)json_decode($this->getData('messages'));
        if (count($messages) == 1) {
            $messages = array($messages);
        }
        return $messages;
    }

    /**
     * storeDataToOrder function.
     * 
     * @access public
     * @param Mage_Sales_Model_Order $order
     * @param array $riskData
     * @return object
     */
    public function storeDataToOrder(Mage_Sales_Model_Order $order, array $riskData)
    {
        $map = $this->getMappedFields();
        
        $this->setId(null);
        $this->setData('txn_type', $this->_typeId);
        $this->setData('order_id', $order->getId());
        
        foreach($map as $responseField => $toField) {
            if ($riskData[$responseField] !== null) {
                $value = $riskData[$responseField];
                $this->setData($toField, $value);
            }
        }
        
        $this->validateRiskData();
        $this->save();
        
        return $this;
    }
    
    /**
     * validateRiskData function.
     * 
     * @access protected
     * @return void
     */
    protected function validateRiskData()
    {
        if (!$this->getData('order_id')) {
            throw new Exception("order_id not supplied");
        }

        if ($this->getData('txn_type') === null) {
            throw new Exception("txn_type not supplied");
        }
    }    
}
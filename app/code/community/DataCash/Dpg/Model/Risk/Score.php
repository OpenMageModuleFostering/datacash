<?php
class DataCash_Dpg_Model_Risk_Score extends DataCash_Dpg_Model_Risk_Abstract
{
    //Example data
    /*<RealTimeResponse xmlns="T3MCallback">
    <aggregator_identifier/>
    <merchant_identifier>5567</merchant_identifier>
    <merchant_order_ref>12345</merchant_order_ref>
    <t3m_id>333333333</t3m_id>
    <score>0</score>
    <recommendation>1</recommendation>
    <message_digest></message_digest>*/

    /**
     * _typeId
     * 
     * (default value: 2)
     * 
     * @var int
     * @access protected
     */
    protected $_typeId = 2;
    
    /**
     * getMappedData function.
     * 
     * @access protected
     * @return void
     */
    protected function getMappedFields()
    {
        return array(
            't3m_score' => 'score',
            't3m_recommendation' => 'recommendation',
        );
    }

    /**
     * getRecommendationDisplay function.
     * 
     * @access public
     * @return array
     */
    public function getRecommendationDisplay()
    {
        $t3mPaymentInfo = Mage::getSingleton('dpg/config')->_t3mPaymentInfo;
        return $t3mPaymentInfo[$this->getData('item')->getData('recommendation')];
    }
    
    /**
     * loadByOrderId function.
     * 
     * @access public
     * @param string $orderId
     * @return risk object
     */
    public function loadByOrderId($orderId)
    {
        $risk = $this->getCollection()
            ->addFieldToFilter('order_id', $orderId)
            ->addFieldToFilter('txn_type', $this->_typeId)
            ->getFirstItem();
            
        return $risk;
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
        $txn = $this->getCollection()
            ->addFieldToFilter('order_id', $order->getId())
            ->addFieldToFilter('txn_type', $this->_typeId)
            ->getFirstItem();
        
        if (!$txn->getId()) {
            $txn = $this;
            $txn->setId(null);
        }
        
        $txn->setData('txn_type', $this->_typeId);
        $txn->setData('order_id', $order->getId());
        
        foreach($map as $responseField => $toField) {
            if ($riskData[$responseField] !== null) {
                $value = $riskData[$responseField];
                $txn->setData($toField, $value);
            }
        }
        
        $txn->validateRiskData();
        $txn->save();
        
        return $txn;
    }
}
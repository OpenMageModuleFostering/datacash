<?php
class DataCash_Dpg_Model_Risk extends DataCash_Dpg_Model_Risk_Abstract
{
    const TYPE_SCREENING = 'dpg/risk_screening';
    const TYPE_BANK = 'dpg/risk_bankresult';
    const TYPE_SCORE = 'dpg/risk_score';

    /**
     * responseToInstanceMap
     * 
     * @var mixed
     * @access private
     */
    private $responseToInstanceMap = array(
        'screening_response' => self::TYPE_SCREENING,
        'bankresult_response' => self::TYPE_BANK,
        'score_response' => self::TYPE_SCORE,
    );
    
    /**
     * typeToInstanceMap
     * 
     * @var mixed
     * @access private
     */
    private $typeToInstanceMap = array(
        '0' => self::TYPE_SCREENING,
        '1' => self::TYPE_BANK,
        '2' => self::TYPE_SCORE,
    );
    
    /**
     * storeRiskResponse function.
     * 
     * @access public
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param DataCash_Dpg_Model_Datacash_Response $response
     * @return void
     */
    public function storeRiskResponse(Mage_Sales_Model_Order_Payment $payment, DataCash_Dpg_Model_Datacash_Response $response)
    {
        $riskResponse = $response->getRiskResponse();
        if (!$riskResponse) {
            return;
        }
        foreach($riskResponse as $typeStr => $riskData) {
            $this->getTypeInstance($typeStr)->storeDataToOrder($payment->getOrder(), $riskData);
        }
        
    }
    
    /**
     * getTypeInstance function.
     * 
     * @access protected
     * @param string $typeId
     * @return void
     */
    protected function getTypeInstance($typeId)
    {
        if (!isset($this->responseToInstanceMap[$typeId]) || !$this->responseToInstanceMap[$typeId]) {
            throw new Exception("Could not find matching response model for type string '{$typeId}'");
        }
        return Mage::getModel($this->responseToInstanceMap[$typeId]);
    }
    
    /**
     * getTypeInstanceFromItem function.
     * 
     * @access public
     * @param DataCash_Dpg_Model_Risk $item
     * @return DataCash_Dpg_Model_Risk_Abstract instance
     */
    public function getTypeInstanceFromItem(DataCash_Dpg_Model_Risk $item)
    {
        if (!isset($this->typeToInstanceMap[$item->getData('txn_type')]) || !$this->typeToInstanceMap[$item->getData('txn_type')]) {
            throw new Exception("Could not find matching type model for item '{$item->getId()}'");
        }
        return Mage::getModel($this->typeToInstanceMap[$item->getData('txn_type')])->setData('item', $item);
    }
}
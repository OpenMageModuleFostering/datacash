<?php
class DataCash_Dpg_Model_Risk_Screening extends DataCash_Dpg_Model_Risk_Abstract
{
    // Example data
    /*[screening_response] => Array
    (
        [additional_messages] => Array
            (
                [message] =>
            )

        [cpi_value] =>
        [response_code] => 00
        [response_message] => Transaction Approved
        [transaction_id] => 3800900036209415
    )*/

    /**
     * _typeId
     * 
     * (default value: 0)
     * 
     * @var int
     * @access protected
     */
    protected $_typeId = 0;
    
    /**
     * getMappedData function.
     * 
     * @access protected
     * @return void
     */
    protected function getMappedFields()
    {
        return array(
            'cpi_value' => 'cpi_value',
            'response_code' => 'response_code',
            'response_message' => 'response_message',
            'transaction_id' => 'transaction_id',
            'additional_messages' => 'messages',
        );
    }
    
    /**
     * _beforeSave function.
     * 
     * @access protected
     * @return this
     */
    protected function _beforeSave()
    {
        parent::_beforeSave();
        if ($this->getData('messages')) {
            $this->setData('messages', json_encode($this->getData('messages')));
        }
        return $this;
    }    
}
<?php
class DataCash_Dpg_Model_Risk_Bankresult extends DataCash_Dpg_Model_Risk_Abstract
{
    // Example data
    /*[bankresult_response] => Array
    (
        [cpi_value] =>
        [response_code] => 00
        [response_message] => Successful
        [transaction_id] => 3800900036209415
    )*/

    /**
     * _typeId
     * 
     * (default value: 1)
     * 
     * @var int
     * @access protected
     */
    protected $_typeId = 1;

    /**
     * getMappedData function.
     * 
     * @access protected
     * @return array
     */
    protected function getMappedFields()
    {
        return array(
            'cpi_value' => 'cpi_value',
            'response_code' => 'response_code',
            'response_message' => 'response_message',
            'transaction_id' => 'transaction_id',
        );
    }
}
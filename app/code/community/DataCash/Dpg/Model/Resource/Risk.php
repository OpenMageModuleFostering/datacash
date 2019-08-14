<?php
class DataCash_Dpg_Model_Resource_Risk extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * _construct function.
     * 
     * @access protected
     * @return void
     */
    protected function _construct()
    {
        $this->_init('dpg/risk', 'id');
    }
}
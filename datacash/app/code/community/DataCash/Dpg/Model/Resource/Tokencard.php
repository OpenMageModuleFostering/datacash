<?php
class DataCash_Dpg_Model_Resource_Tokencard extends Mage_Core_Model_Resource_Db_Abstract
{
	protected function _construct()
	{
		$this->_init('dpg/tokencard', 'id');
	}
}
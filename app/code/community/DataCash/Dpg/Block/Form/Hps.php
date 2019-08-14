<?php
class DataCash_Dpg_Block_Form_Hps extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('datacash/hps/form.phtml');
    }
}
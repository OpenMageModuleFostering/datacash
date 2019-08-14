<?php

class DataCash_Dpg_Block_Form_Hcc extends Mage_Payment_Block_Form
{

    /**
     * Varien constructor
     */
    protected function _construct()
    {
        $this->setTemplate('datacash/hcc/form.phtml');
        parent::_construct();
    }

}

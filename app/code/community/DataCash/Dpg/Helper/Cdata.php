<?php
class DataCash_Dpg_Helper_Cdata
{
    private $value = null;
    public function __construct($value)
    {
        $this->value = $value;
    }
    public function getValue()
    {
        return $this->value;
    }
}
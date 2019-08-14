<?php
class DataCash_Dpg_Model_Datacash_Simplexml_Element extends Varien_Simplexml_Element
{
    public function addChildWithCDATA($name, $value = NULL) {
        $newChild = parent::addChild($name);
        if ($newChild !== NULL) {
            $node = dom_import_simplexml($newChild);
            $no = $node->ownerDocument;
            $node->appendChild($no->createCDATASection($value));
        }
        return $newChild;
    }
    
    public function addChild($name, $value=null, $namespace=null)
    {
        if (gettype($value) == "object" && $value instanceof DataCash_Dpg_Helper_Cdata) {
            return $this->addChildWithCDATA($name, $value->getValue());
        }
        return parent::addChild($name, $value, $namespace);
    }
}
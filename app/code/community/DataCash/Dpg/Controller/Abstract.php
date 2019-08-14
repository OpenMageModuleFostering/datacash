<?php
/**
 * DataCash
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@datacash.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please refer to http://testserver.datacash.com/software/download.cgi
 * for more information. 
 *
 * @author Alistair Stead
 * @version $Id$
 * @copyright DataCash, 11 April, 2011
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @package DataCash
 **/

/**
 * DataCash_Dpg_Controller_Abstract
 * 
 * Abstract Controller that all module controllers will extend
 *
 * @package DataCash
 * @subpackage Controller
 * @author Alistair Stead
 */
abstract class DataCash_Dpg_Controller_Abstract extends Mage_Core_Controller_Front_Action
{

    /**
     * Return datacash config instance
     *
     * @return DataCash_Dpg_Model_Config
     */
    public function getConfig()
    {
        return Mage::getSingleton('dpg/config');
    }
    
    /**
     * mapCallback function.
     * 
     * @access protected
     * @param array $request
     * @param array $indices
     * @return array
     */
    protected function mapCallback($request, $indices)
    {
        $mapped = array();
        foreach ($indices as $i => $j) {
            if ($request[$j] !== null) {
                $mapped[$i] = $request[$j];
            }
        }
        return $mapped;
    }
    
    /**
     * getInputStreamAsArray function.
     * 
     * @access protected
     * @return array
     */
    protected function getInputStreamAsArray()
    {
        $rawInput = file_get_contents('php://input');
        $keyValues = explode('&', $rawInput);
        
        $values = array();
        foreach($keyValues as $keyValue) {
            list($key, $value) = explode('=', $keyValue);
            $values[$key] = $value;
        }
        
        return $values;
    }
}

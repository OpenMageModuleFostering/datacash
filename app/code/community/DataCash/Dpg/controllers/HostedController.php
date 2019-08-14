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
 * DataCash_Dpg_HostedController
 * 
 * Controller that handles all of the hosted payment processes
 *
 * @package DataCash
 * @subpackage Controller
 * @author Alistair Stead
 */
class DataCash_Dpg_HostedController extends DataCash_Dpg_Controller_Abstract
{
    
    /**
     * Get singleton of Checkout Session Model
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }
    
    
    /**
     * Action that starts the interation between the DataCash gateway
     *
     * @return void
     * @author Alistair Stead
     **/
    public function startAction()
    {
        $this->loadLayout()->renderLayout();
    }
    
    /**
     * completeAction
     * Check the state of the transaction after being called-back from the gateway
     * 
     * @author Andy Thompson
     */
    public function completeAction()
    {
        $this->loadLayout()->renderLayout();
    }
}

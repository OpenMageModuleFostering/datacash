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
 * DataCash_Dpg_Block_Form_Apiprereg
 * 
 * Form block that displays the credit card data entry form for Direct API payments that
 * use pre-registered cards.
 *
 * @package DataCash
 * @subpackage Block
 * @author Alistair Stead
 */
class DataCash_Dpg_Block_Form_Apiprereg extends Mage_Payment_Block_Form_Cc
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('datacash/form/cc_prereg.phtml');
    }

    public function getExistingCardsForOrders($orders, $customerId)
    {
        $orders
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('created_at', array('gt' => date('Y-m-d H:i:s', time() - 9450000)));
                // 385 days ago. Ignore orders older than this.

        $payments = array();
        foreach ($orders as $o) {
            $p = $o->getPayment();
            if ($p->getCcTransId() && $p->getCcLast4()) {
                $payments[] = $p;
            }
        }

        return $payments;
    }

    public function getExistingCards()
    {
        $customerId = Mage::getSingleton('customer/session')->getCustomerId();
        $payments = $this->getExistingCardsForOrders(Mage::getResourceModel('sales/order_collection'), $customerId);

        return $payments;
    }
	
    /**
     * Return datacash config instance
     *
     * @return DataCash_Dpg_Model_Config
     */
    public function getConfig()
    {
        return Mage::getSingleton('dpg/config');
    }
}


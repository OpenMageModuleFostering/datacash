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
 
interface DataCash_Dpg_Model_Method_Hosted_Interface
{
    /**
    * getMethodAuthoriseStartUrl
    * @return string url for placeform action for redirection to Datacash
    */
    public function getMethodAuthoriseStartUrl();

    /**
     * _initSession init an API request to DataCash
     * To create a unique session key for the iframe integration
     * 
     * @param Mage_Sales_Model_Quote $payment
     * @param float $amount
     * @return void
     */
    public function initSession(Mage_Sales_Model_Quote $payment);
}
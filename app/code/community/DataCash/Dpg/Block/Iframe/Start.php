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
 * DataCash_Dpg_Block_Iframe
 * 
 * Iframe start page Block rendered for the hosted payment types
 *
 * @package DataCash
 * @subpackage Block
 * @author Alistair Stead
 */

class Datacash_Dpg_Block_Iframe_Start extends Mage_Core_Block_Template
{
    protected function _toHtml()
    {
        $method = $this->getRequest()->getParam('method');
        $tokencardId = $this->getRequest()->getParam('id');
        $rememberCard = $this->getRequest()->getParam('remember');
        
        if (!in_array($method, array('hcc', 'hps'))) {
            Mage::throwException('Method "' . $method . '" is not supported by iframe.');
        }
        $session = Mage::getSingleton('checkout/session');
        
        // Are we using any tokens?
        $tokencard = null;
        $customer = $session->getQuote()->getCustomer();
        if ($customer && $tokencardId && is_numeric($tokencardId)) {
            $tokencard = Mage::getModel('dpg/tokencard')
                ->getCollection()
                ->addCustomerFilter($customer)
                ->addIdFilter($tokencardId)
                ->getFirstItem();
        }
        
        $methodInfo = Mage::getModel('dpg/method_' . $this->getRequest()->getParam('method'));
        
        if ($tokencard && is_object($tokencard)) {
            $methodInfo->setToken($tokencard);
        }
        
        // Save the card for later use?
        $methodInfo->setSaveToken($rememberCard == "1");
        
        $methodInfo->initSession($session->getQuote());
        $session->getQuote()->save();
        $dataCashSession = $session->getData('datacash_' .$method . '_session');

        $this->setStartUrl($dataCashSession['HpsUrl']);
        $this->setSessionId($dataCashSession['SessionId']);

        return parent::_toHtml();
    }
}

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
 * DataCash_Dpg_Block_Iframe_Complete
 * 
 * Iframe complete page Block rendered for the hosted payment types
 *
 * @package DataCash
 * @subpackage Block
 * @author Alistair Stead
 */
class DataCash_Dpg_Block_Iframe_Complete extends Mage_Core_Block_Template
{
    /**
     * Get the quote for the current session
     * 
     * @return Mage_Sales_Model_Quote
     */
    protected function _getQuote()
    {
        return Mage::getSingleton('checkout/session')->getQuote();
    }

    public function isSuccessful()
    {
        $quote = $this->_getQuote();
        $messages = $quote->getMessages();

        return !isset($messages['error']);
    }

    public function getError()
    {
        $quote = $this->_getQuote();
        $messages = $quote->getMessages();

        return $messages['error'];
    }
    
    /**
     * Getting gateway url
     *
     * @return string
     */
    public function getFrameUrl()
    {
        return Mage::getUrl('checkout/hosted/start', array('_secure' => true, 'method' => $this->getRequest()->getParam('method')));
    }
}

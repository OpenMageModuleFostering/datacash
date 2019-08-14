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
 * DataCash_Dpg_RsgController
 *
 * Controller that handles all of the hosted payment processes
 *
 * @package DataCash
 * @subpackage Controller
 * @author OnTap Group
 */
class DataCash_Dpg_RsgController extends DataCash_Dpg_Controller_Abstract
{
    /**
     * indexAction function.
     * 
     * @access public
     * @return void
     */
    public function indexAction()
    {
        $values = $this->getInputStreamAsArray();
        if (count(array_keys($values))) {
            $response = $this->mapCallback(
                $values,
                array(
                    'merchant_identifier' => 'merchant_identifier',
                    'order_id' => 'merchant_order_ref',
                    't3m_id' => 't3m_id',
                    't3m_score' => 'score',
                    't3m_recommendation' => 'recommendation',
                )
            );            
            try {
                Mage::dispatchEvent('datacash_dpg_rsg_callback', array(
                    'response' => $response
                ));
                die('ok');
            } catch (Exception $e) {
                Mage::logException($e);
                if ($e->getMessage() == 'IP_restricted') {
                    $this->getResponse()->setHeader('HTTP/1.1','403 Forbidden');
                    $this->getResponse()->setBody('');
                    return;
                }
            }
        }
        die('FAIL');
    }
}
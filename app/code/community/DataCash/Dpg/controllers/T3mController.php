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
 * DataCash_Dpg_T3mController
 *
 * Controller that handles all of the hosted payment processes
 *
 * @package DataCash
 * @subpackage Controller
 * @author David Marrs
 */
class DataCash_Dpg_T3mController extends DataCash_Dpg_Controller_Abstract
{

    public function indexAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $config = Mage::getSingleton('dpg/config');
            $response = $this->_map(
                $request,
                array(
                    'merchant_identifier' => 'merchant_identifier',
                    'order_id' => 'merchant_order_ref',
                    't3m_id' => 't3m_id',
                    't3m_score' => 'score',
                    't3m_recommendation' => 'recommendation',
                )
            );

            if ($config->isMethodDebug('datacash_api')) {
                Mage::getModel('core/log_adapter', 'datacash_api.log')
                   ->log($response);
            }

            try {
                Mage::dispatchEvent(
                    'datacash_dpg_t3m_callback',
                    array(
                        'response' => $response
                    )
                );
                die('ok');
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        die('FAIL');
    }

    private function _map($request, $indices)
    {
        $t3m = array();
        foreach ($indices as $i => $j) {
            if ($request->getPost($j) !== null) {
                $t3m[$i] = $request->getPost($j);
            }
        }
        return $t3m;
    }
}

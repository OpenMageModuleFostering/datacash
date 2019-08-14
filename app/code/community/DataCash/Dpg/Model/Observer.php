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

class DataCash_Dpg_Model_Observer
{

    /**
     * Add DataCash data to info block
     *
     * Add 3D Secure, CV2 amd ReD information so that it can be seen in the admin view
     *
     * @param Varien_Object $observer
     * @return DataCash_Dpg_Model_Observer
     */
    public function paymentInfoBlockPrepareSpecificInformation($observer)
    {
        if ($observer->getEvent()->getBlock()->getIsSecureMode()) {
            return;
        }

        $payment = $observer->getEvent()->getPayment();
        $transport = $observer->getEvent()->getTransport();
        // var_dump($payment);
        $helper = Mage::helper('dpg');
        $data = array(
            'cc_trans_id',
            'cc_approval',
            'cc_status',
            'cc_status_description',
            'cc_avs_status',
        );
        foreach ($data as $key) {
            if ($value = $payment->getData($key)) {
                $transport->setData($helper->getLabel($key), $value);
            }
        }
        $info = array(
            'cc_avs_address_result',
            'cc_avs_postcode_result',
            'cc_avs_cv2_result',
            'mode',
            't3m_score',
            't3m_recommendation'
        );
        foreach ($info as $key) {
            if ($value = $payment->getAdditionalInformation($key)) {
                $transport->setData($helper->getLabel($key), $value);
            }
        }
        return $this;
    }

    /**
     * Validate that all the keys of $arr are present in $list.
     * @param array $arr
     * @param array $list
     * @return bool
     */
    private function _arrayKeysInList($arr, $list)
    {
        if (!is_array($arr)) {
            $id =  __METHOD__;
            Mage::throwException("$id was expecting an array.");
        }
        return count($arr) === count(array_intersect_key($arr, array_flip($list)));
    }

    public function saveT3mRecommendationToOrder($data)
    {
        $t3mKeys = array('merchant_identifier', 'order_id', 't3m_id', 't3m_score', 't3m_recommendation');
        $t3mPaymentInfo = array('Release', 'Hold', 'Reject', 9 => 'Under Investigation');
        if ( ! $this->_arrayKeysInList((array)$data['response'], $t3mKeys)) {
            Mage::throwException('Observer' . __METHOD__ . ' was expecting different data.');
        }
        $rsp = (array)$data['response'];
        $orders = Mage::getModel('sales/order')->getCollection();
        $orders->addFieldToFilter('increment_id', $rsp['order_id']);
        $order = $orders->getFirstItem();
        if (!$order->getId()) {
            Mage::throwException(__METHOD__ . ' could not find order ' . $rsp['order_id']);
        }

        if (Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW != $order->getState()) {
            throw new Exception(
                'order state was not expected (while expecting it to be payment_review), order id: '
                .$rsp['order_id'].'; '
                .'suggested new recommendation: '.$t3mPaymentInfo[$rsp['t3m_recommendation']]
            );
        }

        $payment = $order->getPayment();

        $oldInfo = $payment->getAdditionalInformation();
        $newInfo = array_merge($oldInfo, array(
            't3m_id' => $rsp['t3m_id'],
            't3m_score' => $rsp['t3m_score'],
            't3m_recommendation' => $t3mPaymentInfo[$rsp['t3m_recommendation']],
        ));
        $payment->setAdditionalInformation($newInfo);

        $order->save();

        switch($newInfo['t3m_recommendation']) {
            case 'Release':
                $payment->accept();
                $order->save();
                break;
            case 'Reject':
                $payment->setIsFraudDetected(true);
                $payment->deny();
                $order->setState(Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW, Mage_Sales_Model_Order::STATUS_FRAUD);
                $order->save();
                break;
            case 'Hold':
                $order->setState(Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW, true);
                $order->save();
                break;
            default:
                throw new Exception('Unknown response recommendation: '.$rsp['t3m_recommendation']);
        }
    }

    /**
     * event observer for quote submission, checks for third man response and automatically cancels the payment
     * if the recommendation is reject
     *
     * @param Varien_Event_Observer $observer
     *
     * @return null
     */
    public function afterOrderSubmit(Varien_Event_Observer $observer)
    {
        if (!Mage::getSingleton('dpg/config')->getIsAllowedT3m()) {
            return;
        }

        $order = $observer->getEvent()->getOrder();
        $payment = $order->getPayment();

        if (Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW == $order->getState() && $payment->getIsTransactionPending()) {
            $info = $payment->getAdditionalInformation();
            if (isset($info['t3m_recommendation']) && ('Reject' == $info['t3m_recommendation'])) {
                // load new payment to avoid payment::$_transactionsLookup caching of value 'false'
                $loadedPayment = Mage::getModel('sales/order_payment')->load($payment->getId());
                if ($loadedPayment->getId()) {
                    $loadedPayment->setOrder($order);
                    $loadedPayment->deny();
                    $order->save();
                }
            }
        }
    }
}

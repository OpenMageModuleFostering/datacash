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
 * @author Hilary Boyce
 * @version $Id$
 * @copyright DataCash, 30th August, 2011
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @package DataCash
 **/
 
class DataCash_Dpg_Model_Source_Acceptreject
{
    /**
     * Return a list of available options to accept or reject
     *
     * @return array
     * @author Hilary Boyce
     **/
    public function toOptionArray()
    {
        $options = array(array('value' => 'reject',
                               'label' => 'Reject'),
                         array('value' => 'accept',
                               'label' => 'Accept')
                        );


        return $options;
    }
}

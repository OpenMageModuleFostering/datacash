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
 
class DataCash_Dpg_Model_Code
{
    /* Statuses */
    const SUCCESS = 1;
    const SOCKET_WRITE_ERROR = 2;
    const FRAUD = 1126;
    const REVIEW = 1127;
    const PAYMENT_REVIEW_ACCEPT = 'accept_review';
    const PAYMENT_REVIEW_DENY = 'deny_review';
    
    /**
     * undocumented class variable
     *
     * @var string
     **/
    protected $_errors = array(
        self::SUCCESS => 'Transaction accepted and logged.',
        self::SOCKET_WRITE_ERROR => 'Communication was interrupted.',
        self::FRAUD => 'The transaction referenced was both referred by the acquiring bank, and challenged by the Retail Decisions (ReD) Service.'
    );
    
    /**
     * undocumented function
     *
     * @return void
     * @author Alistair Stead
     **/
    public static function massage($code)
    {
        return $this->_errors[$code];
    }
}

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
 
$installer = $this;
$installer->startSetup();

$installer->run("
	CREATE TABLE IF NOT EXISTS `{$this->getTable('dpg_tokencard')}` (
	  `id` int(10) unsigned NOT NULL auto_increment,
      `customer_id` int(10) unsigned NOT NULL,
	  `token` varchar(40),
	  `status` varchar(15),
	  `scheme` varchar(60),
	  `pan` varchar(24),
	  `country` varchar(3),
	  `expiry_date` varchar(5),
	  `issuer` varchar(128),
	  `card_category` varchar(24),
      `method` varchar(128),
      `is_default` tinyint(1) unsigned NOT NULL default '0',
	  PRIMARY KEY  (`id`)
	) ENGINE=MYISAM DEFAULT CHARSET=utf8;
");

$installer->endSetup();

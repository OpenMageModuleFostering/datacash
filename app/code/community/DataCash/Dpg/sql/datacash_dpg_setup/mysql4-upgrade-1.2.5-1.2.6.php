<?php
$installer = $this;
$installer->startSetup();

//Changing tables storage engine to avoid table locking, with InnoDB we get row locking.
$installer->run("
    ALTER TABLE `{$this->getTable('dpg_tokencard')}` ENGINE='InnoDB';
");

// txn_type definition
// 0 - screening_response
// 1 - bankresult_response
// 2 - score_response
$installer->run("
    CREATE TABLE IF NOT EXISTS `{$this->getTable('dpg_risk')}` (
      `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
      `txn_type` TINYINT(2) NOT NULL,
      `order_id` INT(11) UNSIGNED NOT NULL,
      `transaction_id` INT(11) UNSIGNED NULL,
      `response_code` VARCHAR(4) NULL,
      `response_message` VARCHAR(255) NULL,
      `cpi_value` TINYINT(2) NULL,
      `messages` TEXT NULL,
      `score` TINYINT(2) NULL,
      `recommendation` TINYINT(2) NULL,
      `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`) )
    ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->run("
    ALTER TABLE `{$this->getTable('dpg_risk')}` ADD INDEX `IDX_DATACASH_RISK_ORDER_ID` (`order_id`);
");

$installer->endSetup();
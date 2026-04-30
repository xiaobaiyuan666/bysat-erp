CREATE TABLE IF NOT EXISTS `fa_erp_update_migration` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration_id` varchar(120) NOT NULL DEFAULT '' COMMENT '迁移编号',
  `checksum` varchar(64) NOT NULL DEFAULT '' COMMENT '内容校验',
  `status` enum('applying','applied','failed') NOT NULL DEFAULT 'applying' COMMENT '执行状态',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '迁移说明',
  `source_ref` varchar(120) NOT NULL DEFAULT '' COMMENT '来源版本',
  `batch_no` varchar(40) NOT NULL DEFAULT '' COMMENT '执行批次',
  `error_message` text COMMENT '失败原因',
  `started_at` datetime DEFAULT NULL COMMENT '开始时间',
  `applied_at` datetime DEFAULT NULL COMMENT '完成时间',
  `execution_ms` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '执行耗时毫秒',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_migration_id` (`migration_id`),
  KEY `idx_status_applied_at` (`status`,`applied_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ERP 在线升级数据库迁移记录';

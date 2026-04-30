
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `fa_admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_admin` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `username` varchar(20) DEFAULT '' COMMENT '用户名',
  `nickname` varchar(50) DEFAULT '' COMMENT '昵称',
  `password` varchar(32) DEFAULT '' COMMENT '密码',
  `salt` varchar(30) DEFAULT '' COMMENT '密码盐',
  `avatar` varchar(255) DEFAULT '' COMMENT '头像',
  `email` varchar(100) DEFAULT '' COMMENT '电子邮箱',
  `mobile` varchar(11) DEFAULT '' COMMENT '手机号码',
  `loginfailure` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '失败次数',
  `logintime` bigint(16) DEFAULT NULL COMMENT '登录时间',
  `loginip` varchar(50) DEFAULT NULL COMMENT '登录IP',
  `createtime` bigint(16) DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) DEFAULT NULL COMMENT '更新时间',
  `token` varchar(59) DEFAULT '' COMMENT 'Session标识',
  `status` varchar(30) NOT NULL DEFAULT 'normal' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_admin` WRITE;
/*!40000 ALTER TABLE `fa_admin` DISABLE KEYS */;
INSERT INTO `fa_admin` VALUES (1,'admin','陈总','7d45c05c5a89c6b5e54ad50de08dc163','c2c18aa3','/assets/img/avatar.png','admin@yfsoft.local','13800000001',0,1777505014,'127.0.0.1',1491635035,1777505014,'8166d273-a18e-4dbf-8a50-bd3a8905b2e2','normal'),(2,'finance.li','李娜','f927c0e8b2499fd7b02ac6120a5b6519','6f68d9f5','','finance.li@yfsoft.local','13800000002',0,1774131048,'127.0.0.1',1774032311,1774131048,'a6a449c8-5f9a-49cd-9c58-376199c634f3','normal'),(3,'pm.zhang','张敏','4978a538d36e39543695512d8a193ecb','61ade4fd','','pm.zhang@yfsoft.local','13800000003',0,1774130963,'127.0.0.1',1774032311,1774130963,'baba5fd4-e165-4a49-a30b-9f1970d04cf5','normal'),(4,'ops.gu','顾宁','03d690852caa06439baf51665dd6eff3','312714e4','','ops.gu@yfsoft.local','13800000004',0,1774131047,'127.0.0.1',1774032311,1774131047,'f537af69-f130-4023-b810-16c33a9ddc36','normal'),(5,'product.wang','王越','72028c64f83aba60e09bf0d68c2f60d2','645c5b17','','product.wang@yfsoft.local','13800000005',0,1774131047,'127.0.0.1',1774032311,1774131047,'3dd45752-7222-4dba-ae69-2a2baa0c2c19','normal'),(6,'tech.zhou','周柯','4bceed22caf2e750526cd3645ebf082e','749f8b3e','','tech.zhou@yfsoft.local','13800000006',0,1774128984,'127.0.0.1',1774032311,1774128984,'580e9a53-f012-4d5e-9208-9e0cb745c599','normal'),(7,'service.liu','刘悦','8970bbb2bda950a3ccc5b1263ac5281d','a57002d2','','service.liu@yfsoft.local','13800000007',0,1774131047,'127.0.0.1',1774032311,1774131047,'f2bd3006-1608-45c9-b6d9-f77622a68686','normal'),(8,'leader.he','何浩','45c5ddd45af8fbfd96d211d058b66af4','699fdbeb','','leader.he@yfsoft.local','13800000008',0,1773919587,'',1774032311,1774032311,'','normal');
/*!40000 ALTER TABLE `fa_admin` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_admin_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_admin_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `username` varchar(30) DEFAULT '' COMMENT '管理员名字',
  `url` varchar(1500) DEFAULT '' COMMENT '操作页面',
  `title` varchar(100) DEFAULT '' COMMENT '日志标题',
  `content` longtext NOT NULL COMMENT '内容',
  `ip` varchar(50) DEFAULT '' COMMENT 'IP',
  `useragent` varchar(255) DEFAULT '' COMMENT 'User-Agent',
  `createtime` bigint(16) DEFAULT NULL COMMENT '操作时间',
  PRIMARY KEY (`id`),
  KEY `name` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=808 DEFAULT CHARSET=utf8mb4 COMMENT='管理员日志表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_admin_log` WRITE;
/*!40000 ALTER TABLE `fa_admin_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `fa_admin_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_ai_conversation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_ai_conversation` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role` enum('system','user','assistant') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'assistant' COMMENT '消息角色:system=系统,user=用户,assistant=助手',
  `content` text COLLATE utf8mb4_unicode_ci COMMENT '消息内容',
  `message_at` datetime DEFAULT NULL COMMENT '消息时间',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ai_conversation_message_at` (`message_at`)
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI会话';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_ai_conversation` WRITE;
/*!40000 ALTER TABLE `fa_ai_conversation` DISABLE KEYS */;
/*!40000 ALTER TABLE `fa_ai_conversation` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_ai_setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_ai_setting` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `provider_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模型供应商',
  `base_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '接口地址',
  `api_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'API Key',
  `model` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模型名称',
  `temperature` decimal(4,2) NOT NULL DEFAULT '0.20' COMMENT '温度',
  `system_prompt` text COLLATE utf8mb4_unicode_ci COMMENT '系统提示词',
  `workspace_json` text COLLATE utf8mb4_unicode_ci COMMENT '工作台配置JSON',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI配置';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_ai_setting` WRITE;
/*!40000 ALTER TABLE `fa_ai_setting` DISABLE KEYS */;
INSERT INTO `fa_ai_setting` VALUES (1,'OpenAI Compatible','','','',0.20,'你是企业 ERP AI 助手。请先在 AI 配置中填写服务地址、Key 和模型后再使用智能分析。','{"skip_ssl_verify":false}',1774032311,1774472287);
/*!40000 ALTER TABLE `fa_ai_setting` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_ai_task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_ai_task` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '管理员',
  `prompt` text COLLATE utf8mb4_unicode_ci COMMENT '原始问题',
  `focus` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'overview' COMMENT '分析范围',
  `preset_key` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '预设',
  `setting_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '模型配置',
  `quick_mode` tinyint(1) NOT NULL DEFAULT '1' COMMENT '快速模式',
  `status` enum('queued','processing','done','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'queued' COMMENT '任务状态',
  `result_json` longtext COLLATE utf8mb4_unicode_ci COMMENT '结果 JSON',
  `error_message` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '错误信息',
  `started_at` datetime DEFAULT NULL COMMENT '开始时间',
  `finished_at` datetime DEFAULT NULL COMMENT '完成时间',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ai_task_admin_status` (`admin_id`,`status`),
  KEY `idx_ai_task_createtime` (`createtime`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI 后台任务';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_ai_task` WRITE;
/*!40000 ALTER TABLE `fa_ai_task` DISABLE KEYS */;
/*!40000 ALTER TABLE `fa_ai_task` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_app_issue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_app_issue` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '旧系统ID',
  `ticket_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '问题单号',
  `source` enum('customer','training','sales','operations','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'customer' COMMENT '来源:customer=客户,training=培训,sales=销售,operations=运营,other=其他',
  `customer` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '客户名称',
  `contact_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '联系人',
  `contact_phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '联系电话',
  `channel` enum('wechat','phone','email','app','onsite','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'wechat' COMMENT '反馈渠道:wechat=微信,phone=电话,email=邮件,app=应用内,onsite=现场,other=其他',
  `category` enum('bug','usage','billing','feature','training','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'usage' COMMENT '问题分类:bug=Bug,usage=使用咨询,billing=账单,feature=功能建议,training=培训,other=其他',
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '问题标题',
  `summary` text COLLATE utf8mb4_unicode_ci COMMENT '问题概述',
  `status` enum('new','processing','waiting_customer','escalated','resolved','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new' COMMENT '处理状态:new=新建,processing=处理中,waiting_customer=待客户确认,escalated=已升级,resolved=已解决,closed=已关闭',
  `priority` enum('low','medium','high','urgent') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium' COMMENT '优先级:low=低,medium=中,high=高,urgent=紧急',
  `assignee` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '受理人',
  `assignee_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '受理人后台账号',
  `opened_at` datetime DEFAULT NULL COMMENT '创建时间',
  `last_follow_up_at` datetime DEFAULT NULL COMMENT '最近跟进',
  `resolve_due_at` datetime DEFAULT NULL COMMENT '承诺解决时间',
  `next_action` text COLLATE utf8mb4_unicode_ci COMMENT '下一步动作',
  `customer_notified` tinyint(1) NOT NULL DEFAULT '0' COMMENT '已回告客户',
  `customer_notified_to` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '回告对象',
  `customer_notified_channel` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '回告方式',
  `customer_notified_at` datetime DEFAULT NULL COMMENT '回告时间',
  `customer_feedback_result` text COLLATE utf8mb4_unicode_ci COMMENT '回告结果',
  `customer_confirmed` tinyint(1) NOT NULL DEFAULT '0' COMMENT '客户已确认',
  `customer_confirmed_at` datetime DEFAULT NULL COMMENT '客户确认时间',
  `customer_confirmation_note` text COLLATE utf8mb4_unicode_ci COMMENT '客户确认说明',
  `app_project_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'APP项目旧ID',
  `app_project_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '所属APP项目',
  `project_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '交付项目旧ID',
  `project_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联交付项目',
  `tech_ticket_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '研发待办旧ID',
  `tech_ticket_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联研发待办',
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT '内部备注',
  `record_created_at` datetime DEFAULT NULL COMMENT '业务创建时间',
  `record_updated_at` datetime DEFAULT NULL COMMENT '业务更新时间',
  `created_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '创建人旧ID',
  `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建人后台账号',
  `created_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '创建人',
  `updated_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '更新人旧ID',
  `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新人后台账号',
  `updated_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '更新人',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_app_issue_legacy_id` (`legacy_id`),
  UNIQUE KEY `uk_app_issue_ticket_no` (`ticket_no`),
  KEY `idx_app_issue_app_project_id` (`app_project_id`),
  KEY `idx_app_issue_project_id` (`project_id`),
  KEY `idx_app_issue_tech_ticket_id` (`tech_ticket_id`),
  KEY `idx_app_issue_assignee_admin_id` (`assignee_admin_id`),
  KEY `idx_app_issue_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='问题记录';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_app_issue` WRITE;
/*!40000 ALTER TABLE `fa_app_issue` DISABLE KEYS */;
INSERT INTO `fa_app_issue` VALUES (1,'service-1001','CS20260318001','customer','星环科技','王琳','13910000001','wechat','bug','注册试用后短信通知没有收到','客户在云帆 CRM App 内提交试用后，没有收到短信通知，已经影响销售跟进。','escalated','high','刘悦',7,'2026-03-18 10:15:00','2026-03-19 09:20:00','2026-03-20 18:00:00','等待研发修复后回访客户确认',0,'王琳','wechat',NULL,'',0,NULL,'','ops-1001',1,'prj-1001',1,'tech-1001',1,'客服已同步销售和运营，优先级拉高。','2026-03-18 10:15:00','2026-03-19 09:20:00','user-1007',7,'刘悦','user-1007',7,'刘悦',1774032311,1774032311),(2,'service-1002','CS20260318002','training','北京数据','赵敏','13910000002','phone','usage','续费提醒配置不会设置','客户希望分批次发送续费提醒，但不会配置策略，需要客服协助。','processing','medium','刘悦',7,'2026-03-18 14:30:00','2026-03-19 08:45:00','2026-03-21 12:00:00','安排远程演示并同步知识库说明',1,'赵敏','phone','2026-03-19 08:45:00','已电话回告客户先走远程演示和 FAQ 说明，客户接受当前安排。',1,'2026-03-19 09:00:00','客户确认按明天下午远程演示推进。','ops-1002',2,'prj-1002',2,'',0,'不需要研发介入，先由客服做陪跑。','2026-03-18 14:30:00','2026-03-19 08:45:00','user-1007',7,'刘悦','user-1007',7,'刘悦',1774032311,1774032311),(3,'service-1004','CS20260319002','sales','教育 SaaS 客户','林峰','13910000004','email','billing','续费账单明细需要补发','客户已收到付款提醒，但需要分项账单说明和上一期对账截图。','waiting_customer','medium','刘悦',7,'2026-03-19 11:25:00','2026-03-19 14:00:00','2026-03-22 18:00:00','等待客户确认账单明细后关闭工单',1,'林峰','email','2026-03-19 14:00:00','已补发账单分项和对账截图，客户回复先内部确认后再反馈。',0,NULL,'','ops-1002',2,'prj-1002',2,'',0,'如客户继续追问自动对账能力，再转成功能建议。','2026-03-19 11:25:00','2026-03-19 14:00:00','user-1007',7,'刘悦','user-1007',7,'刘悦',1774032311,1774032311);
/*!40000 ALTER TABLE `fa_app_issue` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_app_issue_followup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_app_issue_followup` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '旧系统ID',
  `issue_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '问题旧ID',
  `issue_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '所属问题',
  `type` enum('status','follow_up','internal','leader','release') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'follow_up' COMMENT '跟进类型:status=状态同步,follow_up=跟进,internal=内部备注,leader=领导反馈,release=版本回告',
  `visibility` enum('internal','customer','leader') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'internal' COMMENT '可见范围:internal=内部,customer=客户回告,leader=领导同步',
  `content` text COLLATE utf8mb4_unicode_ci COMMENT '跟进内容',
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '当前状态',
  `next_action` text COLLATE utf8mb4_unicode_ci COMMENT '下一步动作',
  `record_created_at` datetime DEFAULT NULL COMMENT '业务创建时间',
  `record_updated_at` datetime DEFAULT NULL COMMENT '业务更新时间',
  `created_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '创建人旧ID',
  `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建人后台账号',
  `created_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '创建人',
  `updated_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '更新人旧ID',
  `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新人后台账号',
  `updated_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '更新人',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_app_issue_followup_legacy_id` (`legacy_id`),
  KEY `idx_app_issue_followup_issue_id` (`issue_id`),
  KEY `idx_app_issue_followup_created_by_admin_id` (`created_by_admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='问题跟进';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_app_issue_followup` WRITE;
/*!40000 ALTER TABLE `fa_app_issue_followup` DISABLE KEYS */;
INSERT INTO `fa_app_issue_followup` VALUES (1,'service-update-1001','service-1001',1,'status','internal','已确认短信网关异常，问题转给研发排查，销售和运营已同步。','escalated','等待研发修复后安排客户回访。','2026-03-19 09:20:00','2026-03-19 09:20:00','user-1007',7,'刘悦','user-1007',7,'刘悦',1774032311,1774032311),(2,'service-update-1002','service-1002',2,'follow_up','customer','已与客户电话沟通，确认先由客服远程演示配置路径，无需立即转研发。','processing','发送 FAQ 文档并预约明天下午远程演示。','2026-03-19 08:45:00','2026-03-19 08:45:00','user-1007',7,'刘悦','user-1007',7,'刘悦',1774032311,1774032311),(3,'service-update-1003','service-1003',0,'leader','leader','经营负责人要求在明天上午演示前完成修复方案，并准备客户说明口径。','escalated','盯研发热补丁发布时间，准备发版后回告。','2026-03-19 09:50:00','2026-03-19 09:50:00','user-1008',8,'何浩','user-1008',8,'何浩',1774032311,1774032311),(4,'service-update-1004','service-1004',3,'internal','internal','财务已补充账单分项明细，客服等待客户确认是否还需要自动对账能力说明。','waiting_customer','收到客户确认后决定关闭还是转成功能建议。','2026-03-19 14:00:00','2026-03-19 14:00:00','user-1007',7,'刘悦','user-1007',7,'刘悦',1774032311,1774032311);
/*!40000 ALTER TABLE `fa_app_issue_followup` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_app_material`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_app_material` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '旧系统ID',
  `app_project_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'APP项目旧ID',
  `app_project_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '所属APP项目',
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '资料名称',
  `category` enum('manual','faq','training','script','report','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual' COMMENT '资料分类:manual=手册,faq=FAQ,training=培训,script=脚本,report=报告,other=其他',
  `owner` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '资料负责人',
  `owner_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '负责人后台账号',
  `version_tag` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '资料版本',
  `applicable_versions` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '适用版本',
  `expires_on` date DEFAULT NULL COMMENT '失效日期',
  `archive_status` enum('active','archived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active' COMMENT '归档状态:active=在用,archived=已归档',
  `replacement_material_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '替代资料旧ID',
  `replacement_material_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '替代资料',
  `download_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件名',
  `download_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '下载链接',
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件路径',
  `file_size` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '文件大小',
  `file_mime` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件类型',
  `updated_on` date DEFAULT NULL COMMENT '资料更新日',
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT '资料说明',
  `record_created_at` datetime DEFAULT NULL COMMENT '业务创建时间',
  `record_updated_at` datetime DEFAULT NULL COMMENT '业务更新时间',
  `created_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '创建人旧ID',
  `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建人后台账号',
  `created_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '创建人',
  `updated_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '更新人旧ID',
  `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新人后台账号',
  `updated_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '更新人',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_app_material_legacy_id` (`legacy_id`),
  KEY `idx_app_material_app_project_id` (`app_project_id`),
  KEY `idx_app_material_owner_admin_id` (`owner_admin_id`),
  KEY `idx_app_material_archive_status` (`archive_status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内部资料';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_app_material` WRITE;
/*!40000 ALTER TABLE `fa_app_material` DISABLE KEYS */;
INSERT INTO `fa_app_material` VALUES (1,'material-1001','ops-1001',1,'CRM 拉新投放SOP','manual','顾宁',4,'2026.03','v2.8.x',NULL,'active','',0,'crm-growth-playbook.txt','/downloads/crm-growth-playbook.txt','',0,'','2026-03-18','给运营和客服统一使用的拉新投放、线索回收与异常回告说明。','2026-03-18 15:00:00','2026-03-18 15:00:00','user-1004',4,'顾宁','user-1004',4,'顾宁',1774032311,1774032311),(2,'material-1002','ops-1002',2,'工单助手续费话术FAQ','faq','刘悦',7,'v3.2.2','v3.2.x','2026-03-18','active','',0,'ticket-assistant-faq.txt','/downloads/ticket-assistant-faq.txt','',0,'','2026-03-19','客服续费回访、异议处理和升级研发前的排查脚本。','2026-03-19 09:30:00','2026-03-19 09:30:00','user-1007',7,'刘悦','user-1007',7,'刘悦',1774032311,1774032311),(3,'material-1003','ops-1003',3,'BI 看板首发培训脚本','training','王越',5,'首发版','v1.0.0-beta',NULL,'archived','',0,'bi-onboarding-script.txt','/downloads/bi-onboarding-script.txt','',0,'','2026-03-19','试用客户演示顺序、Bug回告模板和发版后复访脚本。','2026-03-19 10:20:00','2026-03-19 10:20:00','user-1005',5,'王越','user-1005',5,'王越',1774032311,1774032311);
/*!40000 ALTER TABLE `fa_app_material` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_app_milestone`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_app_milestone` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '旧系统ID',
  `app_project_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'APP项目旧ID',
  `app_project_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '所属APP项目',
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '里程碑',
  `owner` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '负责人',
  `owner_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '负责人后台账号',
  `due_date` date DEFAULT NULL COMMENT '节点日期',
  `status` enum('pending','doing','review','done','blocked') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT '状态:pending=待开始,doing=进行中,review=待确认,done=已完成,blocked=阻塞',
  `progress` int(11) NOT NULL DEFAULT '0' COMMENT '进度%',
  `deliverable` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '交付物',
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT '备注',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_app_milestone_legacy_id` (`legacy_id`),
  KEY `idx_app_milestone_app_project_id` (`app_project_id`),
  KEY `idx_app_milestone_owner_admin_id` (`owner_admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='运营里程碑';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_app_milestone` WRITE;
/*!40000 ALTER TABLE `fa_app_milestone` DISABLE KEYS */;
INSERT INTO `fa_app_milestone` VALUES (1,'milestone-1001','ops-1001',1,'首批素材与投放计划确认','顾宁',4,'2026-03-20','doing',70,'素材排期表 / 渠道预算表','短视频素材已完成，搜索渠道文案还在确认。',1774032311,1774032311),(2,'milestone-1002','ops-1001',1,'线索转化页 AB 版本上线','林晨',0,'2026-03-28','pending',0,'AB 页面与埋点追踪','需等设计稿与埋点方案确认。',1774032311,1774032311),(3,'milestone-1003','ops-1002',2,'重点客户续费清单定版','沈悦',0,'2026-03-22','review',90,'客户续费节奏表','销售经理正在确认最终名单。',1774032311,1774032311),(4,'milestone-1004','ops-1003',3,'试用客户 onboarding 完成','王越',5,'2026-04-02','pending',10,'试用客户名单与培训材料','需先输出 onboarding 流程与演示材料。',1774032311,1774032311);
/*!40000 ALTER TABLE `fa_app_milestone` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_app_project`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_app_project` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '旧系统ID',
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '运营项目',
  `project_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'app' COMMENT '项目类型',
  `app_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'APP名称',
  `app_version` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '当前版本',
  `lifecycle_stage` enum('idea','validation','launch','growth','retention','mature','sunset') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'validation' COMMENT '生命周期:idea=构思,validation=验证,launch=上线,growth=增长,retention=留存,mature=成熟,sunset=下线',
  `business_line` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '业务线',
  `manager` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '运营负责人',
  `manager_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '运营负责人后台账号',
  `client_owner` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '业务负责人',
  `core_metric` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '核心指标',
  `status` enum('planning','running','paused','completed','archived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'planning' COMMENT '项目状态:planning=规划中,running=执行中,paused=暂停,completed=已完成,archived=已归档',
  `priority` enum('low','medium','high','urgent') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium' COMMENT '优先级:low=低,medium=中,high=高,urgent=紧急',
  `budget` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '预算',
  `actual_cost` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '实际成本',
  `start_date` date DEFAULT NULL COMMENT '开始日期',
  `end_date` date DEFAULT NULL COMMENT '结束日期',
  `target` text COLLATE utf8mb4_unicode_ci COMMENT '目标说明',
  `channel` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '运营渠道',
  `project_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '交付项目旧ID',
  `project_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联交付项目',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '项目说明',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_app_project_legacy_id` (`legacy_id`),
  KEY `idx_app_project_project_id` (`project_id`),
  KEY `idx_app_project_manager_admin_id` (`manager_admin_id`),
  KEY `idx_app_project_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='APP运营项目';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_app_project` WRITE;
/*!40000 ALTER TABLE `fa_app_project` DISABLE KEYS */;
INSERT INTO `fa_app_project` VALUES (1,'ops-1001','春季增长投放','app','云帆 CRM','v2.8.0','growth','增长运营','顾宁',4,'市场负责人','注册到试用转化率','running','high',150000.00,92000.00,'2026-03-01','2026-04-20','获取 1200 条有效销售线索，单条线索成本控制在 150 元以内','信息流 / 短视频 / 搜索广告','prj-1001',1,'围绕官网改版后的获客转化做联动运营，重点看线索成本、转化率和素材节奏。',1774032311,1774032311),(2,'ops-1002','SaaS 客户续费推进','app','工单助手','v3.2.1','retention','客户运营','沈悦',0,'客户成功负责人','30 日活跃率','running','high',60000.00,18000.00,'2026-03-05','2026-05-10','完成 3 个重点客户续费，续费金额不少于 50 万','客户拜访 / 续费脚本 / 运营培训','prj-1002',2,'聚焦重点客户续费与增购，协调销售、交付和客户成功的动作节奏。',1774032311,1774032311),(3,'ops-1003','BI 看板上线推广','app','BI 看板','v1.0.0','launch','产品运营','王越',5,'内部产品负责人','首周开通率','planning','medium',30000.00,5000.00,'2026-03-15','2026-05-25','完成首批 10 家试用客户上线并形成复盘素材','私域演示 / 客户试用 / 内容案例','prj-1003',3,'围绕 BI 看板首版上线做推广试运行，建立试用反馈闭环和案例素材。',1774032311,1774032311);
/*!40000 ALTER TABLE `fa_app_project` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_app_release`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_app_release` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '旧系统ID',
  `app_project_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'APP项目旧ID',
  `app_project_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '所属APP项目',
  `version` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '版本号',
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '发布标题',
  `status` enum('planned','ready','testing','released','rollback','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'planned' COMMENT '发布状态:planned=待排期,ready=待发布,testing=测试中,released=已发布,rollback=已回滚,closed=已关闭',
  `owner` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '发布负责人',
  `owner_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '负责人后台账号',
  `release_date` date DEFAULT NULL COMMENT '计划发布日期',
  `channel` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '发布范围',
  `tech_ticket_ids_json` text COLLATE utf8mb4_unicode_ci COMMENT '关联研发待办JSON',
  `service_ticket_ids_json` text COLLATE utf8mb4_unicode_ci COMMENT '关联问题记录JSON',
  `verification_summary` text COLLATE utf8mb4_unicode_ci COMMENT '验证结论',
  `customer_sync_status` enum('pending','done','skip') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT '客户回告:pending=待回告,done=已回告,skip=无需回告',
  `customer_sync_note` text COLLATE utf8mb4_unicode_ci COMMENT '客户回告说明',
  `release_result` text COLLATE utf8mb4_unicode_ci COMMENT '发布结果',
  `release_notes` text COLLATE utf8mb4_unicode_ci COMMENT '发布说明',
  `rollback_plan` text COLLATE utf8mb4_unicode_ci COMMENT '回滚方案',
  `rollback_ready` tinyint(1) NOT NULL DEFAULT '0' COMMENT '已备回滚方案',
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT '备注',
  `record_created_at` datetime DEFAULT NULL COMMENT '业务创建时间',
  `record_updated_at` datetime DEFAULT NULL COMMENT '业务更新时间',
  `created_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '创建人旧ID',
  `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建人后台账号',
  `created_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '创建人',
  `updated_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '更新人旧ID',
  `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新人后台账号',
  `updated_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '更新人',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_app_release_legacy_id` (`legacy_id`),
  KEY `idx_app_release_app_project_id` (`app_project_id`),
  KEY `idx_app_release_owner_admin_id` (`owner_admin_id`),
  KEY `idx_app_release_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='版本发布';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_app_release` WRITE;
/*!40000 ALTER TABLE `fa_app_release` DISABLE KEYS */;
INSERT INTO `fa_app_release` VALUES (1,'release-1001','ops-1001',1,'v2.8.1','拉新落地页埋点与线索回传','testing','周柯',6,'2026-03-24','灰度 30%','[\"tech-1001\",\"tech-1002\"]','[\"service-1001\"]','灰度环境已验证注册通知、线索回传和企业微信字段同步，待 30% 灰度观察。','pending','客服待灰度稳定后统一回告星环科技。','','补齐注册、试用、来源埋点，并同步企业微信线索字段。','保留 v2.8.0 配置并支持 10 分钟内回切。',1,'运营会在灰度阶段同步验证投放转化。','2026-03-18 11:00:00','2026-03-19 09:30:00','user-1006',6,'周柯','user-1004',4,'顾宁',1774032311,1774032311),(2,'release-1002','ops-1002',2,'v3.2.2','续费提醒分批策略上线','ready','周柯',6,'2026-03-21','全量发布','[\"tech-1003\"]','[\"service-1002\"]','预发布环境已完成策略脚本、发送节奏和模板校验，待生产窗口执行。','pending','版本上线后由客服同步新的续费提醒话术和配置说明。','','新增 7 天、3 天、当天三段提醒，并支持客户分层。','保留旧策略模板，必要时恢复单模板发送。',1,'客服会同步准备新版本使用话术。','2026-03-17 16:20:00','2026-03-19 10:10:00','user-1006',6,'周柯','user-1006',6,'周柯',1774032311,1774032311),(3,'release-1003','ops-1003',3,'v1.0.1','首发图表筛选修复热补丁','planned','周柯',6,'2026-03-20','指定试用客户','[\"tech-1004\"]','[\"service-1003\"]','修复补丁已在测试环境通过筛选切换、缓存清理和图表渲染验证。','pending','发布后第一时间回告首发试用客户，并跟进演示窗口。','','修复筛选切换后图表空白问题，并补充缓存逻辑。','保留热补丁开关，可按客户维度关闭。',1,'客服与运营需在发布后第一时间回访试用客户。','2026-03-19 09:40:00','2026-03-19 10:00:00','user-1006',6,'周柯','user-1008',8,'何浩',1774032311,1774032311);
/*!40000 ALTER TABLE `fa_app_release` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_app_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_app_report` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '旧系统ID',
  `app_project_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'APP项目旧ID',
  `app_project_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '所属APP项目',
  `report_date` date DEFAULT NULL COMMENT '周报日期',
  `owner` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '提交人',
  `owner_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '提交人后台账号',
  `summary` text COLLATE utf8mb4_unicode_ci COMMENT '本周概述',
  `result` text COLLATE utf8mb4_unicode_ci COMMENT '阶段结果',
  `next_actions` text COLLATE utf8mb4_unicode_ci COMMENT '下步动作',
  `blockers` text COLLATE utf8mb4_unicode_ci COMMENT '阻塞事项',
  `record_created_at` datetime DEFAULT NULL COMMENT '业务创建时间',
  `record_updated_at` datetime DEFAULT NULL COMMENT '业务更新时间',
  `created_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '创建人旧ID',
  `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建人后台账号',
  `created_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '创建人',
  `updated_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '更新人旧ID',
  `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新人后台账号',
  `updated_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '更新人',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_app_report_legacy_id` (`legacy_id`),
  KEY `idx_app_report_app_project_id` (`app_project_id`),
  KEY `idx_app_report_owner_admin_id` (`owner_admin_id`),
  KEY `idx_app_report_report_date` (`report_date`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='运营周报';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_app_report` WRITE;
/*!40000 ALTER TABLE `fa_app_report` DISABLE KEYS */;
INSERT INTO `fa_app_report` VALUES (1,'report-1001','ops-1001',1,'2026-03-18','顾宁00',0,'已确认两轮投放节奏，短视频素材 6 套完成 4 套。','预估本周可以开第一轮测试投放。','补齐搜索广告文案，联动开发确认转化埋点。','搜索广告落地页还未交付。',NULL,'2026-03-20 04:46:55','',0,'系统初始化','user-1001',1,'陈总',1774032311,1774032311),(2,'report-1002','ops-1002',2,'2026-03-17','沈悦',0,'3 个重点客户中已有 2 个进入商务沟通。','预计下周能锁定首个续费方案。','补充客户使用数据和续费案例。','其中 1 个客户仍在等交付侧验收说明。',NULL,NULL,'',0,'','',0,'',1774032311,1774032311),(3,'report-1003','ops-1003',3,'2026-03-16','王越',5,'已梳理试用客户名单，正在准备演示材料。','本周先完成 onboarding 话术与演示模板。','确定首批试用时间表。','产品页文案还未确认。',NULL,NULL,'',0,'','',0,'',1774032311,1774032311);
/*!40000 ALTER TABLE `fa_app_report` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_app_risk`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_app_risk` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '旧系统ID',
  `app_project_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'APP项目旧ID',
  `app_project_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '所属APP项目',
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '风险标题',
  `type` enum('risk','issue','change','dependency') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'risk' COMMENT '风险类型:risk=风险,issue=问题,change=变更,dependency=依赖',
  `level` enum('low','medium','high','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium' COMMENT '风险等级:low=低,medium=中,high=高,critical=严重',
  `status` enum('open','tracking','resolved','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open' COMMENT '处理状态:open=待处理,tracking=跟进中,resolved=已解决,closed=已关闭',
  `owner` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '负责人',
  `owner_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '负责人后台账号',
  `due_date` date DEFAULT NULL COMMENT '处理截止',
  `impact` text COLLATE utf8mb4_unicode_ci COMMENT '影响范围',
  `action_plan` text COLLATE utf8mb4_unicode_ci COMMENT '处理方案',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_app_risk_legacy_id` (`legacy_id`),
  KEY `idx_app_risk_app_project_id` (`app_project_id`),
  KEY `idx_app_risk_owner_admin_id` (`owner_admin_id`),
  KEY `idx_app_risk_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='运营风险';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_app_risk` WRITE;
/*!40000 ALTER TABLE `fa_app_risk` DISABLE KEYS */;
INSERT INTO `fa_app_risk` VALUES (1,'risk-1001','ops-1001',1,'搜索广告落地页交付延后','issue','high','tracking','林晨',0,'2026-03-21','首轮搜索投放无法按计划开启，可能影响线索目标。','先使用简化页兜底，正式版完成后切换。',1774032311,1774032311),(2,'risk-1002','ops-1002',2,'续费案例素材不完整','risk','medium','open','沈悦',0,'2026-03-24','销售沟通缺少说服材料，可能影响续费推进。','本周内补齐客户使用结果和案例摘要。',1774032311,1774032311),(3,'risk-1003','ops-1003',3,'试用客户需求口径存在变化','change','low','tracking','王越',5,'2026-03-27','试用方案和培训节奏需做调整。','先整理需求差异，评估是否拆成两个 onboarding 版本。',1774032311,1774032311);
/*!40000 ALTER TABLE `fa_app_risk` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_app_tech_ticket`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_app_tech_ticket` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '旧系统ID',
  `app_project_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'APP项目旧ID',
  `app_project_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '所属APP项目',
  `project_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '交付项目旧ID',
  `project_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联交付项目',
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '研发待办',
  `type` enum('bug','improvement','upgrade','task') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bug' COMMENT '待办类型:bug=Bug,improvement=优化,upgrade=升级,task=任务',
  `status` enum('pending','processing','testing','ready','done','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT '处理状态:pending=待处理,processing=处理中,testing=测试中,ready=待发布,done=已完成,closed=已关闭',
  `priority` enum('low','medium','high','urgent') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium' COMMENT '优先级:low=低,medium=中,high=高,urgent=紧急',
  `severity` enum('low','medium','high','blocker') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium' COMMENT '严重级别:low=低,medium=中,high=高,blocker=阻断',
  `source` enum('operations','product','customer','sales','service') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'operations' COMMENT '来源:operations=运营,product=产品,customer=客户,sales=销售,service=客服',
  `app_module` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'APP模块',
  `app_version` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '影响版本',
  `owner` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '负责人',
  `owner_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '负责人后台账号',
  `reporter` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '提出人',
  `reporter_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '提出人后台账号',
  `due_date` date DEFAULT NULL COMMENT '计划完成日期',
  `impact` text COLLATE utf8mb4_unicode_ci COMMENT '影响说明',
  `solution_plan` text COLLATE utf8mb4_unicode_ci COMMENT '解决方案',
  `estimate_hours` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT '预估工时',
  `actual_hours` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT '实际工时',
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT '备注',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_app_tech_ticket_legacy_id` (`legacy_id`),
  KEY `idx_app_tech_ticket_app_project_id` (`app_project_id`),
  KEY `idx_app_tech_ticket_project_id` (`project_id`),
  KEY `idx_app_tech_ticket_owner_admin_id` (`owner_admin_id`),
  KEY `idx_app_tech_ticket_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='研发联动';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_app_tech_ticket` WRITE;
/*!40000 ALTER TABLE `fa_app_tech_ticket` DISABLE KEYS */;
INSERT INTO `fa_app_tech_ticket` VALUES (1,'tech-1001','ops-1001',1,'prj-1001',1,'注册落地页埋点丢失导致转化数据不准','bug','pending','high','high','operations','注册转化页','v2.8.0','周柯',6,'顾宁',4,'2026-03-22','运营无法准确统计投放转化率，影响拉新预算分配。','补齐注册、试用、渠道来源埋点并回溯验证日志。',12.00,6.00,'需要和 BI 看板一起联调事件名称。',1774032311,1774032311),(2,'tech-1002','ops-1001',1,'prj-1001',1,'试用注册流程增加企业微信线索回填','bug','pending','high','medium','operations','注册流程','v2.8.0','周柯',6,'顾宁',4,'2026-03-29','线索入 CRM 依赖人工补录，运营跟进效率低。','新增企业微信字段回填与来源标签同步。',16.00,0.00,'等产品确认字段映射规则后进入开发。',1774032311,1774032311),(3,'tech-1003','ops-1002',2,'prj-1002',2,'续费提醒消息中心支持分批触达策略','improvement','testing','medium','medium','product','消息中心','v3.2.1','周柯',6,'王越',5,'2026-03-23','当前提醒方式单一，客户续费节奏难以分层管理。','增加 7 天、3 天、当天三段提醒模板与开关。',10.00,9.00,'测试完成后即可随 v3.2.2 发布。',1774032311,1774032311),(4,'tech-1004','ops-1003',3,'prj-1003',3,'筛选项切换后图表状态丢失','bug','pending','high','blocker','customer','图表首页','v1.0.0','周柯',6,'王越',5,'2026-03-21','首发客户无法连续查看核心指标，影响上线体验。','修复筛选状态缓存与图表重新渲染逻辑。',8.00,0.00,'需要优先于首批试用客户演示前修复。',1774032311,1774032311);
/*!40000 ALTER TABLE `fa_app_tech_ticket` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_area`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_area` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `pid` int(10) DEFAULT NULL COMMENT '父id',
  `shortname` varchar(100) DEFAULT NULL COMMENT '简称',
  `name` varchar(100) DEFAULT NULL COMMENT '名称',
  `mergename` varchar(255) DEFAULT NULL COMMENT '全称',
  `level` tinyint(4) DEFAULT NULL COMMENT '层级:1=省,2=市,3=区/县',
  `pinyin` varchar(100) DEFAULT NULL COMMENT '拼音',
  `code` varchar(100) DEFAULT NULL COMMENT '长途区号',
  `zip` varchar(100) DEFAULT NULL COMMENT '邮编',
  `first` varchar(50) DEFAULT NULL COMMENT '首字母',
  `lng` varchar(100) DEFAULT NULL COMMENT '经度',
  `lat` varchar(100) DEFAULT NULL COMMENT '纬度',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='地区表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_area` WRITE;
/*!40000 ALTER TABLE `fa_area` DISABLE KEYS */;
/*!40000 ALTER TABLE `fa_area` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_attachment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_attachment` (
  `id` int(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `category` varchar(50) DEFAULT '' COMMENT '类别',
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `url` varchar(255) DEFAULT '' COMMENT '物理路径',
  `imagewidth` int(10) unsigned DEFAULT '0' COMMENT '宽度',
  `imageheight` int(10) unsigned DEFAULT '0' COMMENT '高度',
  `imagetype` varchar(30) DEFAULT '' COMMENT '图片类型',
  `imageframes` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '图片帧数',
  `filename` varchar(100) DEFAULT '' COMMENT '文件名称',
  `filesize` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '文件大小',
  `mimetype` varchar(100) DEFAULT '' COMMENT 'mime类型',
  `extparam` varchar(255) DEFAULT '' COMMENT '透传数据',
  `createtime` bigint(16) DEFAULT NULL COMMENT '创建日期',
  `updatetime` bigint(16) DEFAULT NULL COMMENT '更新时间',
  `uploadtime` bigint(16) DEFAULT NULL COMMENT '上传时间',
  `storage` varchar(100) NOT NULL DEFAULT 'local' COMMENT '存储位置',
  `sha1` varchar(40) DEFAULT '' COMMENT '文件 sha1编码',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='附件表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_attachment` WRITE;
/*!40000 ALTER TABLE `fa_attachment` DISABLE KEYS */;
INSERT INTO `fa_attachment` VALUES (1,'',1,0,'/assets/img/qrcode.png',150,150,'png',0,'qrcode.png',21859,'image/png','',1491635035,1491635035,1491635035,'local','17163603d0263e4838b9387ff2cd4877e8b018f6');
/*!40000 ALTER TABLE `fa_attachment` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_auth_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_auth_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父组别',
  `name` varchar(100) DEFAULT '' COMMENT '组名',
  `rules` text NOT NULL COMMENT '规则ID',
  `createtime` bigint(16) DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) DEFAULT NULL COMMENT '更新时间',
  `status` varchar(30) DEFAULT '' COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COMMENT='分组表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_auth_group` WRITE;
/*!40000 ALTER TABLE `fa_auth_group` DISABLE KEYS */;
INSERT INTO `fa_auth_group` VALUES (1,0,'Admin group','*',1491635035,1774119951,'normal'),(2,1,'Second group','13,14,16,15,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,40,41,42,43,44,45,46,47,48,49,50,55,56,57,58,59,60,61,62,63,64,65,1,9,10,11,7,6,8,2,4,5',1491635035,1491635035,'normal'),(3,2,'Third group','1,4,9,10,11,13,14,15,16,17,40,41,42,43,44,45,46,47,48,49,50,55,56,57,58,59,60,61,62,63,64,65,5',1491635035,1491635035,'normal'),(4,1,'Second group 2','1,4,13,14,15,16,17,55,56,57,58,59,60,61,62,63,64,65',1491635035,1491635035,'normal'),(5,2,'Third group 2','1,2,6,7,8,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34',1491635035,1491635035,'normal'),(6,0,'ERP 财务组','1,2,13,23,24,25,26,27,28,88,89,90,91,106,112,113,114,115,116,117,118,119,120,121,122,123,124,200,201,202,203,212,213,214,215,216,217,218,219,220,221,222,223,224,225,226,227,228,229,230,231,232,233,236,237,238,239,240,241,242,243,244,245,246,247,248,249,250,251,252,253,254,255,256,257,258,259,260,261,262,263,264,265,266,267,268,269,270,271,272,273,274,276,277,278,279,280,281,282,283,284,285,286,287,288,289,290,291,292,293,294,295,296,297,298,299,300,301,302,303,304,305,306,307,308,309,310,311,312,313,314,315,316,317,318,320,321,322,323,328,334,335,336,337,338,339,340,341,355,356,357,388,389,390,391',1774032311,1777506018,'normal'),(7,0,'ERP 项目组','1,2,13,23,24,25,26,27,28,92,93,94,106,125,126,127,128,129,130,131,132,133,134,135,136,200,201,202,203,212,213,214,215,216,217,218,219,220,221,222,223,224,225,226,227,228,229,230,231,232,233,234,235,236,237,238,239,240,241,242,243,244,245,246,247,248,249,250,251,252,253,254,255,256,257,258,259,260,261,262,263,264,265,266,267,268,269,270,271,272,273,274,276,277,278,279,280,281,282,283,284,285,286,287,288,289,290,291,292,293,294,295,296,297,298,299,300,301,302,303,304,305,306,307,308,309,310,311,312,313,314,315,316,317,318,323,328,334,335,336,337,338,339,340,341,355,356,357',1774032311,1777506018,'normal'),(8,0,'ERP 运营组','1,2,13,23,24,25,26,27,28,95,96,97,98,99,100,101,102,103,104,105,106,137,138,139,140,141,142,143,144,145,146,147,148,149,150,151,152,153,154,155,156,157,158,159,160,161,162,163,164,165,166,167,168,169,170,171,172,173,174,175,176,177,178,179,180,181,182,183,184,185,186,187,188,189,190,191,200,201,202,203,212,213,214,215,216,217,218,219,220,221,222,223,224,225,226,227,228,229,230,231,232,233,236,237,238,239,240,241,242,243,244,245,246,247,248,249,250,251,252,253,254,255,256,257,258,259,260,261,262,263,264,265,266,267,268,269,270,271,272,273,274,276,277,278,279,280,281,282,283,284,285,286,287,288,289,290,291,292,293,294,295,296,297,298,299,300,301,302,303,304,305,306,307,308,309,310,311,312,313,314,315,316,317,318,323,328,334,335,336,337,338,339,340,341,355,356,357',1774032311,1777506018,'normal'),(9,0,'ERP 客服组','1,2,13,23,24,25,26,27,28,95,96,97,98,99,101,102,106,137,138,139,140,141,142,143,144,145,146,147,148,149,150,151,152,153,154,155,162,164,168,169,170,171,172,173,200,201,202,203,328,355,356,357',1774032311,1777506018,'normal'),(10,0,'ERP 技术组','1,13,92,94,95,96,98,99,100,101,106,131,132,133,134,135,136,137,144,145,146,147,148,149,150,151,152,153,154,155,156,157,158,159,160,161,162,163,164,165,166,167,200,201,202,203,234,235,355,356,357',1774032311,1777506018,'normal'),(11,0,'ERP 只读组','1,2,6,7,13,18,23,88,89,90,91,92,93,94,95,96,97,98,99,100,101,102,103,104,105,106,107,112,113,119,125,131,137,138,144,150,156,162,168,174,180,186,200,212,213,214,215,216,222,228,234,235,236,237,238,244,250,251,260,261,262,268,276,277,284,285,304,305,306,312,318,323,334,335',1774032311,1777506018,'normal'),(12,0,'ERP 公司组','1,2,5,6,7,8,9,10,11,12,13,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,86,88,89,90,91,92,93,94,95,96,97,98,99,100,101,102,103,104,105,106,107,108,109,110,111,112,113,114,115,116,117,118,119,120,121,122,123,124,125,126,127,128,129,130,131,132,133,134,135,136,137,138,139,140,141,142,143,144,145,146,147,148,149,150,151,152,153,154,155,156,157,158,159,160,161,162,163,164,165,166,167,168,169,170,171,172,173,174,175,176,177,178,179,180,181,182,183,184,185,186,187,188,189,190,191,192,193,194,195,196,197,198,199,200,201,202,203,204,205,206,207,208,209,210,211,212,213,214,215,216,217,218,219,220,221,222,223,224,225,226,227,228,229,230,231,232,233,234,235,236,237,238,239,240,241,242,243,244,245,246,247,248,249,250,251,252,253,254,255,256,257,258,259,260,261,262,263,264,265,266,267,268,269,270,271,272,273,274,275,276,277,278,279,280,281,282,283,284,285,286,287,288,289,290,291,292,293,294,295,296,297,298,299,300,301,302,303,304,305,306,307,308,309,310,311,312,313,314,315,316,317,318,319,320,321,322,323,324,325,326,327,328,329,330,331,332,333,334,335,336,337,338,339,340,341,342,343,344,345,346,347,354,355,356,357,386,387,388,389,390,391,392',1774481388,1777506018,'normal');
/*!40000 ALTER TABLE `fa_auth_group` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_auth_group_access`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_auth_group_access` (
  `uid` int(10) unsigned NOT NULL COMMENT '会员ID',
  `group_id` int(10) unsigned NOT NULL COMMENT '级别ID',
  UNIQUE KEY `uid_group_id` (`uid`,`group_id`),
  KEY `uid` (`uid`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='权限分组表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_auth_group_access` WRITE;
/*!40000 ALTER TABLE `fa_auth_group_access` DISABLE KEYS */;
INSERT INTO `fa_auth_group_access` VALUES (1,1),(2,6),(3,7),(4,8),(5,11),(6,10),(7,9),(8,8);
/*!40000 ALTER TABLE `fa_auth_group_access` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_auth_rule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_auth_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('menu','file') NOT NULL DEFAULT 'file' COMMENT 'menu为菜单,file为权限节点',
  `pid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父ID',
  `name` varchar(100) DEFAULT '' COMMENT '规则名称',
  `title` varchar(50) DEFAULT '' COMMENT '规则名称',
  `icon` varchar(50) DEFAULT '' COMMENT '图标',
  `url` varchar(255) DEFAULT '' COMMENT '规则URL',
  `condition` varchar(255) DEFAULT '' COMMENT '条件',
  `remark` varchar(255) DEFAULT '' COMMENT '备注',
  `ismenu` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否为菜单',
  `menutype` enum('addtabs','blank','dialog','ajax') DEFAULT NULL COMMENT '菜单类型',
  `extend` varchar(255) DEFAULT '' COMMENT '扩展属性',
  `py` varchar(30) DEFAULT '' COMMENT '拼音首字母',
  `pinyin` varchar(100) DEFAULT '' COMMENT '拼音',
  `createtime` bigint(16) DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) DEFAULT NULL COMMENT '更新时间',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重',
  `status` varchar(30) DEFAULT '' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`) USING BTREE,
  KEY `pid` (`pid`),
  KEY `weigh` (`weigh`)
) ENGINE=InnoDB AUTO_INCREMENT=393 DEFAULT CHARSET=utf8mb4 COMMENT='节点表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_auth_rule` WRITE;
/*!40000 ALTER TABLE `fa_auth_rule` DISABLE KEYS */;
INSERT INTO `fa_auth_rule` VALUES (1,'menu',0,'dashboard','AI 指挥台','fa fa-dashboard','','','企业 ERP AI 智能管理首页。',1,'','','','',1491635035,1777506018,120,'normal'),(2,'menu',0,'general','系统设置','fa fa-cogs','','','系统展示、模块开关、在线更新和附件管理。',1,'','','','',1491635035,1777506018,60,'normal'),(3,'file',0,'category','Category','fa fa-leaf','','','Category tips',0,NULL,'','flgl','fenleiguanli',1491635035,1777506018,119,'normal'),(4,'file',0,'addon','Addon','fa fa-rocket','','','Addon tips',0,NULL,'','cjgl','chajianguanli',1491635035,1777506018,0,'normal'),(5,'menu',0,'auth','系统权限','fa fa-lock','','','后台账号、权限组和规则节点。',1,'','','','',1491635035,1777506018,50,'normal'),(6,'menu',2,'general/config','基础设置','fa fa-cog','','','维护系统名称、Logo、官网入口和登录页展示。',1,NULL,'','xtpz','xitongpeizhi',1491635035,1777506018,30,'normal'),(7,'menu',2,'general/attachment','附件中心','fa fa-paperclip','','','查看票据、图片、附件和上传记录。',1,'','','','',1491635035,1777506018,10,'normal'),(8,'file',2,'general/profile','Profile','fa fa-user','','','',0,NULL,'','grzl','gerenziliao',1491635035,1777506018,34,'normal'),(9,'menu',5,'auth/admin','后台账号','fa fa-user-secret','','','维护后台登录账号和所属权限组。',1,'','','','',1491635035,1777506018,40,'normal'),(10,'menu',5,'auth/adminlog','登录日志','fa fa-sign-in','','','查看后台登录和访问轨迹。',1,'','','','',1491635035,1777506018,30,'normal'),(11,'menu',5,'auth/group','权限组','fa fa-object-group','','','按岗位维护模块权限。',1,'','','','',1491635035,1777506018,20,'normal'),(12,'menu',5,'auth/rule','规则节点','fa fa-list-alt','','','查看系统菜单和权限节点。',1,'','','','',1491635035,1777506018,10,'normal'),(13,'file',1,'dashboard/index','查看','','','','查看AI 指挥台',0,'','','','',1491635035,1777506018,0,'normal'),(14,'file',1,'dashboard/add','Add','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,135,'normal'),(15,'file',1,'dashboard/del','Delete','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,133,'normal'),(16,'file',1,'dashboard/edit','Edit','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,134,'normal'),(17,'file',1,'dashboard/multi','Multi','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,132,'normal'),(18,'file',6,'general/config/index','查看','','','','查看基础设置',0,NULL,'','','',1491635035,1777506018,0,'normal'),(19,'file',6,'general/config/add','Add','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,51,'normal'),(20,'file',6,'general/config/edit','Edit','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,50,'normal'),(21,'file',6,'general/config/del','Delete','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,49,'normal'),(22,'file',6,'general/config/multi','Multi','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,48,'normal'),(23,'file',7,'general/attachment/index','查看','','','','查看附件中心',0,'','','','',1491635035,1777506018,0,'normal'),(24,'file',7,'general/attachment/select','选择','','','','选择附件',0,'','','','',1491635035,1777506018,0,'normal'),(25,'file',7,'general/attachment/add','上传','','','','上传附件',0,'','','','',1491635035,1777506018,0,'normal'),(26,'file',7,'general/attachment/edit','编辑','','','','编辑附件',0,'','','','',1491635035,1774119951,0,'normal'),(27,'file',7,'general/attachment/del','删除','','','','删除附件',0,'','','','',1491635035,1777506018,0,'normal'),(28,'file',7,'general/attachment/multi','批量更新','','','','批量更新附件',0,'','','','',1491635035,1774119951,0,'normal'),(29,'file',8,'general/profile/index','View','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,33,'normal'),(30,'file',8,'general/profile/update','Update profile','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,32,'normal'),(31,'file',8,'general/profile/add','Add','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,31,'normal'),(32,'file',8,'general/profile/edit','Edit','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,30,'normal'),(33,'file',8,'general/profile/del','Delete','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,29,'normal'),(34,'file',8,'general/profile/multi','Multi','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,28,'normal'),(35,'file',3,'category/index','View','fa fa-circle-o','','','Category tips',0,NULL,'','','',1491635035,1491635035,142,'normal'),(36,'file',3,'category/add','Add','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,141,'normal'),(37,'file',3,'category/edit','Edit','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,140,'normal'),(38,'file',3,'category/del','Delete','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,139,'normal'),(39,'file',3,'category/multi','Multi','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,138,'normal'),(40,'file',9,'auth/admin/index','查看','','','','查看后台账号',0,'','','','',1491635035,1777506018,0,'normal'),(41,'file',9,'auth/admin/add','新增','','','','新增后台账号',0,'','','','',1491635035,1777506018,0,'normal'),(42,'file',9,'auth/admin/edit','编辑','','','','编辑后台账号',0,'','','','',1491635035,1777506018,0,'normal'),(43,'file',9,'auth/admin/del','删除','','','','删除后台账号',0,'','','','',1491635035,1777506018,0,'normal'),(44,'file',10,'auth/adminlog/index','查看','','','','查看登录日志',0,'','','','',1491635035,1777506018,0,'normal'),(45,'file',10,'auth/adminlog/detail','详情','','','','查看登录日志详情',0,'','','','',1491635035,1777506018,0,'normal'),(46,'file',10,'auth/adminlog/del','删除','','','','删除登录日志',0,'','','','',1491635035,1777506018,0,'normal'),(47,'file',11,'auth/group/index','查看','','','','查看权限组',0,'','','','',1491635035,1777506018,0,'normal'),(48,'file',11,'auth/group/add','新增','','','','新增权限组',0,'','','','',1491635035,1777506018,0,'normal'),(49,'file',11,'auth/group/edit','编辑','','','','编辑权限组',0,'','','','',1491635035,1777506018,0,'normal'),(50,'file',11,'auth/group/del','删除','','','','删除权限组',0,'','','','',1491635035,1777506018,0,'normal'),(51,'file',12,'auth/rule/index','查看','','','','查看规则节点',0,'','','','',1491635035,1777506018,0,'normal'),(52,'file',12,'auth/rule/add','新增','','','','新增规则节点',0,'','','','',1491635035,1777506018,0,'normal'),(53,'file',12,'auth/rule/edit','编辑','','','','编辑规则节点',0,'','','','',1491635035,1777506018,0,'normal'),(54,'file',12,'auth/rule/del','删除','','','','删除规则节点',0,'','','','',1491635035,1777506018,0,'normal'),(55,'file',4,'addon/index','View','fa fa-circle-o','','','Addon tips',0,NULL,'','','',1491635035,1491635035,0,'normal'),(56,'file',4,'addon/add','Add','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,0,'normal'),(57,'file',4,'addon/edit','Edit','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,0,'normal'),(58,'file',4,'addon/del','Delete','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,0,'normal'),(59,'file',4,'addon/downloaded','Local addon','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,0,'normal'),(60,'file',4,'addon/state','Update state','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,0,'normal'),(63,'file',4,'addon/config','Setting','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,0,'normal'),(64,'file',4,'addon/refresh','Refresh','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,0,'normal'),(65,'file',4,'addon/multi','Multi','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,0,'normal'),(66,'file',0,'user','User','fa fa-user-circle','','','',0,NULL,'','hygl','huiyuanguanli',1491635035,1777506018,0,'normal'),(67,'file',66,'user/user','User','fa fa-user','','','',0,NULL,'','hygl','huiyuanguanli',1491635035,1777506018,0,'normal'),(68,'file',67,'user/user/index','View','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,0,'normal'),(69,'file',67,'user/user/edit','Edit','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,0,'normal'),(70,'file',67,'user/user/add','Add','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,0,'normal'),(71,'file',67,'user/user/del','Del','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,0,'normal'),(72,'file',67,'user/user/multi','Multi','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,0,'normal'),(73,'file',66,'user/group','User group','fa fa-users','','','',0,NULL,'','hyfz','huiyuanfenzu',1491635035,1777506018,0,'normal'),(74,'file',73,'user/group/add','Add','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,0,'normal'),(75,'file',73,'user/group/edit','Edit','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,0,'normal'),(76,'file',73,'user/group/index','View','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,0,'normal'),(77,'file',73,'user/group/del','Del','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,0,'normal'),(78,'file',73,'user/group/multi','Multi','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,0,'normal'),(79,'file',66,'user/rule','User rule','fa fa-circle-o','','','',0,NULL,'','hygz','huiyuanguize',1491635035,1777506018,0,'normal'),(80,'file',79,'user/rule/index','View','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,0,'normal'),(81,'file',79,'user/rule/del','Del','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,0,'normal'),(82,'file',79,'user/rule/add','Add','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,0,'normal'),(83,'file',79,'user/rule/edit','Edit','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,0,'normal'),(84,'file',79,'user/rule/multi','Multi','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,0,'normal'),(85,'file',3,'category/dragsort','Dragsort','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,0,'normal'),(86,'file',12,'auth/rule/dragsort','拖拽排序','','','','调整规则节点排序',0,'','','','',1491635035,1777506018,0,'normal'),(87,'file',79,'user/rule/dragsort','Dragsort','fa fa-circle-o','','','',0,NULL,'','','',1491635035,1491635035,0,'normal'),(88,'menu',0,'finance','财务中心','fa fa-rmb','','','流水、应收应付和财务工作台。',1,'','','','',1774033664,1777506018,110,'normal'),(89,'menu',88,'finance/workbench','财务工作台','fa fa-dashboard','','','优先处理回款、付款和附件补传。',1,'','','','',1774033664,1777506018,40,'normal'),(90,'menu',88,'finance/transaction','资金流水','fa fa-exchange','','','记录收入、支出和资金往来。',1,'','','','',1774033664,1777506018,30,'normal'),(91,'menu',88,'finance/invoice','应收应付','fa fa-file-text-o','','','统一跟进回款、付款和逾期单据。',1,'','','','',1774033664,1777506018,20,'normal'),(92,'menu',0,'project','项目交付','fa fa-briefcase','','','项目台账和任务清单。',1,'','','','',1774033664,1777506018,100,'normal'),(93,'menu',92,'project/project','项目台账','fa fa-folder-open','','','查看项目状态、负责人和交付进度。',1,'','','','',1774033664,1777506018,30,'normal'),(94,'menu',92,'project/task','任务清单','fa fa-tasks','','','查看逾期、进行中和阻塞任务。',1,'','','','',1774033664,1777506018,20,'normal'),(95,'menu',0,'app','项目运营','fa fa-mobile','','','问题、研发联动、发版和资料中心。',1,'','','','',1774033664,1777506018,90,'normal'),(96,'menu',95,'app/workbench','项目运营工作台','fa fa-dashboard','','','问题记录、研发联动、版本发布统一入口。',1,'','','','',1774033664,1777506018,80,'normal'),(97,'menu',95,'app/project','项目台账','fa fa-table','','','查看项目生命周期、版本和负责人。',1,'','','','',1774033664,1777506018,70,'normal'),(98,'menu',95,'app/issue','问题记录','fa fa-bug','','','客服反馈、Bug 和产品意见统一收口。',1,'','','','',1774033664,1777506018,60,'normal'),(99,'menu',95,'app/issue_followup','问题跟进','fa fa-commenting-o','','','记录问题处理过程和客户回告。',0,'','','','',1774033664,1777506018,55,'normal'),(100,'menu',95,'app/tech_ticket','研发联动','fa fa-code-fork','','','Bug、升级和优化需求统一流转。',1,'','','','',1774033664,1777506018,50,'normal'),(101,'menu',95,'app/release','版本发布','fa fa-rocket','','','测试、发布、回滚和客户回告。',1,'','','','',1774033664,1777506018,40,'normal'),(102,'menu',95,'app/material','内部资料','fa fa-folder-open-o','','','资料下载、适用版本和归档状态。',1,'','','','',1774033664,1777506018,35,'normal'),(103,'menu',95,'app/milestone','里程碑','fa fa-flag-checkered','','','维护关键节点和交付节奏。',0,'','','','',1774033664,1777506018,30,'normal'),(104,'menu',95,'app/report','项目汇报','fa fa-line-chart','','','记录项目推进、阶段总结和下步动作。',0,'','','','',1774033664,1777506018,20,'normal'),(105,'menu',95,'app/risk','风险问题','fa fa-exclamation-triangle','','','统一跟踪运营风险和异常。',0,'','','','',1774033664,1777506018,10,'normal'),(106,'menu',0,'ai','AI 中枢','fa fa-comments-o','','','AI 工作台和模型配置。',1,'','','','',1774033664,1777506018,115,'normal'),(107,'menu',106,'ai/conversation','AI 工作台','fa fa-comments-o','','','结合财务、项目、客户和项目运营数据做分析。',1,'','','','',1774033664,1777506018,20,'normal'),(108,'menu',106,'ai/setting','AI 配置','fa fa-sliders','','','维护模型配置和联通测试。',1,'','','','',1774033664,1777506018,10,'normal'),(109,'menu',0,'staff','人员与权限','fa fa-users','','','员工档案、操作日志和权限管理。',1,'','','','',1774033664,1777506018,70,'normal'),(110,'menu',109,'staff/profile','员工档案','fa fa-id-card-o','','','维护员工账号、岗位、部门和权限组。',1,'','','','',1774033664,1777506018,20,'normal'),(111,'menu',109,'staff/audit','操作日志','fa fa-history','','','查看谁新增、修改和删除了什么。',1,'','','','',1774033664,1777506018,10,'normal'),(112,'file',89,'finance/workbench/index','查看','','','','查看财务工作台',0,'','','','',1774033664,1777506018,0,'normal'),(113,'file',90,'finance/transaction/index','查看','','','','查看资金流水',0,'','','','',1774033664,1777506018,0,'normal'),(114,'file',90,'finance/transaction/add','新增','','','','新增资金流水',0,'','','','',1774033664,1777506018,0,'normal'),(115,'file',90,'finance/transaction/edit','编辑','','','','编辑资金流水',0,'','','','',1774033664,1777506018,0,'normal'),(116,'file',90,'finance/transaction/del','删除','','','','删除资金流水',0,'','','','',1774033664,1777506018,0,'normal'),(117,'file',90,'finance/transaction/multi','批量操作','','','','批量处理资金流水',0,'','','','',1774033664,1777506018,0,'normal'),(118,'file',90,'finance/transaction/selectpage','选择数据','','','','资金流水下拉选择',0,'','','','',1774033664,1777506018,0,'normal'),(119,'file',91,'finance/invoice/index','查看','','','','查看应收应付',0,'','','','',1774033664,1777506018,0,'normal'),(120,'file',91,'finance/invoice/add','新增','','','','新增应收应付',0,'','','','',1774033664,1777506018,0,'normal'),(121,'file',91,'finance/invoice/edit','编辑','','','','编辑应收应付',0,'','','','',1774033664,1777506018,0,'normal'),(122,'file',91,'finance/invoice/del','删除','','','','删除应收应付',0,'','','','',1774033664,1777506018,0,'normal'),(123,'file',91,'finance/invoice/multi','批量操作','','','','批量处理应收应付',0,'','','','',1774033664,1777506018,0,'normal'),(124,'file',91,'finance/invoice/selectpage','选择数据','','','','应收应付下拉选择',0,'','','','',1774033664,1777506018,0,'normal'),(125,'file',93,'project/project/index','查看','','','','查看项目台账',0,'','','','',1774033664,1777506018,0,'normal'),(126,'file',93,'project/project/add','新增','','','','新增项目台账',0,'','','','',1774033664,1777506018,0,'normal'),(127,'file',93,'project/project/edit','编辑','','','','编辑项目台账',0,'','','','',1774033664,1777506018,0,'normal'),(128,'file',93,'project/project/del','删除','','','','删除项目台账',0,'','','','',1774033664,1777506018,0,'normal'),(129,'file',93,'project/project/multi','批量操作','','','','批量处理项目台账',0,'','','','',1774033664,1777506018,0,'normal'),(130,'file',93,'project/project/selectpage','选择数据','','','','项目台账下拉选择',0,'','','','',1774033664,1777506018,0,'normal'),(131,'file',94,'project/task/index','查看','','','','查看任务清单',0,'','','','',1774033664,1777506018,0,'normal'),(132,'file',94,'project/task/add','新增','','','','新增任务清单',0,'','','','',1774033664,1777506018,0,'normal'),(133,'file',94,'project/task/edit','编辑','','','','编辑任务清单',0,'','','','',1774033664,1777506018,0,'normal'),(134,'file',94,'project/task/del','删除','','','','删除任务清单',0,'','','','',1774033664,1777506018,0,'normal'),(135,'file',94,'project/task/multi','批量操作','','','','批量处理任务清单',0,'','','','',1774033664,1777506018,0,'normal'),(136,'file',94,'project/task/selectpage','选择数据','','','','任务清单下拉选择',0,'','','','',1774033664,1777506018,0,'normal'),(137,'file',96,'app/workbench/index','查看','','','','查看项目运营工作台',0,'','','','',1774033664,1777506018,0,'normal'),(138,'file',97,'app/project/index','查看','','','','查看项目台账',0,'','','','',1774033664,1777506018,0,'normal'),(139,'file',97,'app/project/add','新增','','','','新增项目台账',0,'','','','',1774033664,1777506018,0,'normal'),(140,'file',97,'app/project/edit','编辑','','','','编辑项目台账',0,'','','','',1774033664,1777506018,0,'normal'),(141,'file',97,'app/project/del','删除','','','','删除项目台账',0,'','','','',1774033664,1777506018,0,'normal'),(142,'file',97,'app/project/multi','批量操作','','','','批量处理项目台账',0,'','','','',1774033664,1777506018,0,'normal'),(143,'file',97,'app/project/selectpage','选择数据','','','','项目台账下拉选择',0,'','','','',1774033664,1777506018,0,'normal'),(144,'file',98,'app/issue/index','查看','','','','查看问题记录',0,'','','','',1774033664,1777506018,0,'normal'),(145,'file',98,'app/issue/add','新增','','','','新增问题记录',0,'','','','',1774033664,1777506018,0,'normal'),(146,'file',98,'app/issue/edit','编辑','','','','编辑问题记录',0,'','','','',1774033664,1777506018,0,'normal'),(147,'file',98,'app/issue/del','删除','','','','删除问题记录',0,'','','','',1774033664,1777506018,0,'normal'),(148,'file',98,'app/issue/multi','批量操作','','','','批量处理问题记录',0,'','','','',1774033664,1777506018,0,'normal'),(149,'file',98,'app/issue/selectpage','选择数据','','','','问题记录下拉选择',0,'','','','',1774033664,1777506018,0,'normal'),(150,'file',99,'app/issue_followup/index','查看','','','','查看问题跟进',0,'','','','',1774033664,1777506018,0,'normal'),(151,'file',99,'app/issue_followup/add','新增','','','','新增问题跟进',0,'','','','',1774033664,1777506018,0,'normal'),(152,'file',99,'app/issue_followup/edit','编辑','','','','编辑问题跟进',0,'','','','',1774033664,1777506018,0,'normal'),(153,'file',99,'app/issue_followup/del','删除','','','','删除问题跟进',0,'','','','',1774033664,1777506018,0,'normal'),(154,'file',99,'app/issue_followup/multi','批量操作','','','','批量处理问题跟进',0,'','','','',1774033664,1777506018,0,'normal'),(155,'file',99,'app/issue_followup/selectpage','选择数据','','','','问题跟进下拉选择',0,'','','','',1774033664,1777506018,0,'normal'),(156,'file',100,'app/tech_ticket/index','查看','','','','查看研发联动',0,'','','','',1774033664,1777506018,0,'normal'),(157,'file',100,'app/tech_ticket/add','新增','','','','新增研发联动',0,'','','','',1774033664,1777506018,0,'normal'),(158,'file',100,'app/tech_ticket/edit','编辑','','','','编辑研发联动',0,'','','','',1774033664,1777506018,0,'normal'),(159,'file',100,'app/tech_ticket/del','删除','','','','删除研发联动',0,'','','','',1774033664,1777506018,0,'normal'),(160,'file',100,'app/tech_ticket/multi','批量操作','','','','批量处理研发联动',0,'','','','',1774033664,1777506018,0,'normal'),(161,'file',100,'app/tech_ticket/selectpage','选择数据','','','','研发联动下拉选择',0,'','','','',1774033664,1777506018,0,'normal'),(162,'file',101,'app/release/index','查看','','','','查看版本发布',0,'','','','',1774033664,1777506018,0,'normal'),(163,'file',101,'app/release/add','新增','','','','新增版本发布',0,'','','','',1774033664,1777506018,0,'normal'),(164,'file',101,'app/release/edit','编辑','','','','编辑版本发布',0,'','','','',1774033664,1777506018,0,'normal'),(165,'file',101,'app/release/del','删除','','','','删除版本发布',0,'','','','',1774033664,1777506018,0,'normal'),(166,'file',101,'app/release/multi','批量操作','','','','批量处理版本发布',0,'','','','',1774033664,1777506018,0,'normal'),(167,'file',101,'app/release/selectpage','选择数据','','','','版本发布下拉选择',0,'','','','',1774033664,1777506018,0,'normal'),(168,'file',102,'app/material/index','查看','','','','查看内部资料',0,'','','','',1774033664,1777506018,0,'normal'),(169,'file',102,'app/material/add','新增','','','','新增内部资料',0,'','','','',1774033664,1777506018,0,'normal'),(170,'file',102,'app/material/edit','编辑','','','','编辑内部资料',0,'','','','',1774033664,1777506018,0,'normal'),(171,'file',102,'app/material/del','删除','','','','删除内部资料',0,'','','','',1774033664,1777506018,0,'normal'),(172,'file',102,'app/material/multi','批量操作','','','','批量处理内部资料',0,'','','','',1774033664,1777506018,0,'normal'),(173,'file',102,'app/material/selectpage','选择数据','','','','内部资料下拉选择',0,'','','','',1774033664,1777506018,0,'normal'),(174,'file',103,'app/milestone/index','查看','','','','查看里程碑',0,'','','','',1774033664,1777506018,0,'normal'),(175,'file',103,'app/milestone/add','新增','','','','新增里程碑',0,'','','','',1774033664,1777506018,0,'normal'),(176,'file',103,'app/milestone/edit','编辑','','','','编辑里程碑',0,'','','','',1774033664,1777506018,0,'normal'),(177,'file',103,'app/milestone/del','删除','','','','删除里程碑',0,'','','','',1774033664,1777506018,0,'normal'),(178,'file',103,'app/milestone/multi','批量操作','','','','批量处理里程碑',0,'','','','',1774033664,1777506018,0,'normal'),(179,'file',103,'app/milestone/selectpage','选择数据','','','','里程碑下拉选择',0,'','','','',1774033664,1777506018,0,'normal'),(180,'file',104,'app/report/index','查看','','','','查看项目汇报',0,'','','','',1774033664,1777506018,0,'normal'),(181,'file',104,'app/report/add','新增','','','','新增项目汇报',0,'','','','',1774033664,1777506018,0,'normal'),(182,'file',104,'app/report/edit','编辑','','','','编辑项目汇报',0,'','','','',1774033664,1777506018,0,'normal'),(183,'file',104,'app/report/del','删除','','','','删除项目汇报',0,'','','','',1774033664,1777506018,0,'normal'),(184,'file',104,'app/report/multi','批量操作','','','','批量处理项目汇报',0,'','','','',1774033664,1777506018,0,'normal'),(185,'file',104,'app/report/selectpage','选择数据','','','','项目汇报下拉选择',0,'','','','',1774033664,1777506018,0,'normal'),(186,'file',105,'app/risk/index','查看','','','','查看风险问题',0,'','','','',1774033664,1777506018,0,'normal'),(187,'file',105,'app/risk/add','新增','','','','新增风险问题',0,'','','','',1774033664,1777506018,0,'normal'),(188,'file',105,'app/risk/edit','编辑','','','','编辑风险问题',0,'','','','',1774033664,1777506018,0,'normal'),(189,'file',105,'app/risk/del','删除','','','','删除风险问题',0,'','','','',1774033664,1777506018,0,'normal'),(190,'file',105,'app/risk/multi','批量操作','','','','批量处理风险问题',0,'','','','',1774033664,1777506018,0,'normal'),(191,'file',105,'app/risk/selectpage','选择数据','','','','风险问题下拉选择',0,'','','','',1774033664,1777506018,0,'normal'),(192,'file',110,'staff/profile/index','查看','','','','查看员工档案',0,'','','','',1774033664,1777506018,0,'normal'),(193,'file',110,'staff/profile/add','新增','','','','新增员工档案',0,'','','','',1774033664,1777506018,0,'normal'),(194,'file',110,'staff/profile/edit','编辑','','','','编辑员工档案',0,'','','','',1774033664,1777506018,0,'normal'),(195,'file',110,'staff/profile/del','删除','','','','删除员工档案',0,'','','','',1774033664,1777506018,0,'normal'),(196,'file',110,'staff/profile/multi','批量操作','','','','批量处理员工档案',0,'','','','',1774033664,1777506018,0,'normal'),(197,'file',110,'staff/profile/selectpage','选择数据','','','','员工档案下拉选择',0,'','','','',1774033664,1777506018,0,'normal'),(198,'file',111,'staff/audit/index','查看','','','','查看操作日志',0,'','','','',1774033664,1777506018,0,'normal'),(199,'file',9,'auth/admin/selectpage','选择数据','','','','后台账号下拉选择',0,'','','','',1774033664,1774119951,0,'normal'),(200,'file',107,'ai/conversation/index','查看','','','','查看AI 工作台',0,'','','','',1774033664,1777506018,0,'normal'),(201,'file',107,'ai/conversation/bootstrap','工作台初始化','','','','获取 AI 工作台初始化数据',0,'','','','',1774033664,1777506018,0,'normal'),(202,'file',107,'ai/conversation/ask','发送提问','','','','向 AI 发起提问',0,'','','','',1774033664,1777506018,0,'normal'),(203,'file',107,'ai/conversation/clear','清空会话','','','','清空 AI 会话记录',0,'','','','',1774033664,1777506018,0,'normal'),(204,'file',108,'ai/setting/index','查看','','','','查看AI 配置',0,'','','','',1774033664,1777506018,0,'normal'),(205,'file',108,'ai/setting/add','新增','','','','新增AI 配置',0,'','','','',1774033664,1777506018,0,'normal'),(206,'file',108,'ai/setting/edit','编辑','','','','编辑AI 配置',0,'','','','',1774033664,1777506018,0,'normal'),(207,'file',108,'ai/setting/del','删除','','','','删除AI 配置',0,'','','','',1774033664,1777506018,0,'normal'),(208,'file',108,'ai/setting/multi','批量操作','','','','批量处理AI 配置',0,'','','','',1774033664,1777506018,0,'normal'),(209,'file',108,'ai/setting/setdefault','设为默认','','','','将模型设为默认',0,'','','','',1774033664,1777506018,0,'normal'),(210,'file',108,'ai/setting/ping','测试连接','','','','测试模型连通性',0,'','','','',1774033664,1777506018,0,'normal'),(211,'file',108,'ai/setting/discover','探测模型','','','','识别协议并加载模型列表',0,'','','','',1774033664,1777506018,0,'normal'),(212,'menu',0,'business','客户与合同','fa fa-handshake-o','','','客户、采购、合同与审批主线。',1,'','','','',1774035551,1777506018,105,'normal'),(213,'menu',212,'business/customer','客户档案','fa fa-address-book-o','','','维护客户资料和负责人。',1,'','','','',1774035551,1777506018,30,'normal'),(214,'menu',212,'business/contract','合同台账','fa fa-file-text-o','','','管理合同金额、状态和关联项目。',1,'','','','',1774035551,1777506018,19,'normal'),(215,'menu',212,'business/receivable_plan','回款计划','fa fa-calendar-check-o','','','跟进合同回款节点和到账情况。',1,'','','','',1774035551,1777506018,12,'normal'),(216,'file',213,'business/customer/index','查看','','','','查看客户档案',0,'','','','',1774035551,1777506018,0,'normal'),(217,'file',213,'business/customer/add','新增','','','','新增客户档案',0,'','','','',1774035551,1777506018,0,'normal'),(218,'file',213,'business/customer/edit','编辑','','','','编辑客户档案',0,'','','','',1774035551,1777506018,0,'normal'),(219,'file',213,'business/customer/del','删除','','','','删除客户档案',0,'','','','',1774035551,1777506018,0,'normal'),(220,'file',213,'business/customer/multi','批量操作','','','','批量处理客户档案',0,'','','','',1774035551,1777506018,0,'normal'),(221,'file',213,'business/customer/selectpage','选择数据','','','','客户档案下拉选择',0,'','','','',1774035551,1777506018,0,'normal'),(222,'file',214,'business/contract/index','查看','','','','查看合同台账',0,'','','','',1774035551,1777506018,0,'normal'),(223,'file',214,'business/contract/add','新增','','','','新增合同台账',0,'','','','',1774035551,1777506018,0,'normal'),(224,'file',214,'business/contract/edit','编辑','','','','编辑合同台账',0,'','','','',1774035551,1777506018,0,'normal'),(225,'file',214,'business/contract/del','删除','','','','删除合同台账',0,'','','','',1774035551,1777506018,0,'normal'),(226,'file',214,'business/contract/multi','批量操作','','','','批量处理合同台账',0,'','','','',1774035551,1777506018,0,'normal'),(227,'file',214,'business/contract/selectpage','选择数据','','','','合同台账下拉选择',0,'','','','',1774035551,1777506018,0,'normal'),(228,'file',215,'business/receivable_plan/index','查看','','','','查看回款计划',0,'','','','',1774035551,1777506018,0,'normal'),(229,'file',215,'business/receivable_plan/add','新增','','','','新增回款计划',0,'','','','',1774035551,1777506018,0,'normal'),(230,'file',215,'business/receivable_plan/edit','编辑','','','','编辑回款计划',0,'','','','',1774035551,1777506018,0,'normal'),(231,'file',215,'business/receivable_plan/del','删除','','','','删除回款计划',0,'','','','',1774035551,1777506018,0,'normal'),(232,'file',215,'business/receivable_plan/multi','批量操作','','','','批量处理回款计划',0,'','','','',1774035551,1777506018,0,'normal'),(233,'file',215,'business/receivable_plan/selectpage','选择数据','','','','回款计划下拉选择',0,'','','','',1774035551,1777506018,0,'normal'),(234,'menu',92,'project/workbench','项目工作台','fa fa-dashboard','','','优先处理逾期、阻塞和待验收任务。',1,'','','','',1774037745,1777506018,40,'normal'),(235,'file',234,'project/workbench/index','查看','','','','查看项目工作台',0,'','','','',1774037745,1777506018,0,'normal'),(236,'menu',212,'business/customer_followup','客户跟进','fa fa-commenting-o','','','记录沟通结果、下次跟进和回款推进。',1,'','','','',1774055120,1777506018,25,'normal'),(237,'menu',212,'business/payment_plan','付款计划','fa fa-credit-card','','','统一跟踪合同成本、供应商和费用付款。',1,'','','','',1774055120,1777506018,5,'normal'),(238,'file',236,'business/customer_followup/index','查看','','','','查看客户跟进',0,'','','','',1774055120,1777506018,0,'normal'),(239,'file',236,'business/customer_followup/add','新增','','','','新增客户跟进',0,'','','','',1774055120,1777506018,0,'normal'),(240,'file',236,'business/customer_followup/edit','编辑','','','','编辑客户跟进',0,'','','','',1774055120,1777506018,0,'normal'),(241,'file',236,'business/customer_followup/del','删除','','','','删除客户跟进',0,'','','','',1774055120,1777506018,0,'normal'),(242,'file',236,'business/customer_followup/multi','批量操作','','','','批量处理客户跟进',0,'','','','',1774055120,1777506018,0,'normal'),(243,'file',236,'business/customer_followup/selectpage','选择数据','','','','客户跟进下拉选择',0,'','','','',1774055120,1777506018,0,'normal'),(244,'file',237,'business/payment_plan/index','查看','','','','查看付款计划',0,'','','','',1774055120,1777506018,0,'normal'),(245,'file',237,'business/payment_plan/add','新增','','','','新增付款计划',0,'','','','',1774055120,1777506018,0,'normal'),(246,'file',237,'business/payment_plan/edit','编辑','','','','编辑付款计划',0,'','','','',1774055120,1777506018,0,'normal'),(247,'file',237,'business/payment_plan/del','删除','','','','删除付款计划',0,'','','','',1774055120,1777506018,0,'normal'),(248,'file',237,'business/payment_plan/multi','批量操作','','','','批量处理付款计划',0,'','','','',1774055120,1777506018,0,'normal'),(249,'file',237,'business/payment_plan/selectpage','选择数据','','','','付款计划下拉选择',0,'','','','',1774055120,1777506018,0,'normal'),(250,'menu',212,'business/approval','审批中心','fa fa-check-square-o','','','统一处理合同、费用、采购和付款审批。',1,'','','','',1774071346,1777506018,18,'normal'),(251,'file',250,'business/approval/index','查看','','','','查看审批中心',0,'','','','',1774071346,1777506018,0,'normal'),(252,'file',250,'business/approval/add','新增','','','','新增审批中心',0,'','','','',1774071346,1777506018,0,'normal'),(253,'file',250,'business/approval/edit','编辑','','','','编辑审批中心',0,'','','','',1774071346,1777506018,0,'normal'),(254,'file',250,'business/approval/del','删除','','','','删除审批中心',0,'','','','',1774071346,1777506018,0,'normal'),(255,'file',250,'business/approval/multi','批量操作','','','','批量处理审批中心',0,'','','','',1774071346,1777506018,0,'normal'),(256,'file',250,'business/approval/selectpage','选择数据','','','','审批中心下拉选择',0,'','','','',1774071346,1777506018,0,'normal'),(257,'file',250,'business/approval/approve','审批通过','','','','通过审批',0,'','','','',1774071346,1777506018,0,'normal'),(258,'file',250,'business/approval/reject','驳回审批','','','','驳回审批',0,'','','','',1774071346,1777506018,0,'normal'),(259,'file',250,'business/approval/cancel','撤销审批','','','','撤销审批',0,'','','','',1774071346,1777506018,0,'normal'),(260,'menu',212,'business/supplier','供应商档案','fa fa-truck','','','维护供应商资料和结算信息。',1,'','','','',1774073311,1777506018,24,'normal'),(261,'menu',212,'business/expense_request','费用申请','fa fa-money','','','提交采购、投放、外包等费用申请。',1,'','','','',1774073311,1777506018,8,'normal'),(262,'file',260,'business/supplier/index','查看','','','','查看供应商档案',0,'','','','',1774073311,1777506018,0,'normal'),(263,'file',260,'business/supplier/add','新增','','','','新增供应商档案',0,'','','','',1774073311,1777506018,0,'normal'),(264,'file',260,'business/supplier/edit','编辑','','','','编辑供应商档案',0,'','','','',1774073311,1777506018,0,'normal'),(265,'file',260,'business/supplier/del','删除','','','','删除供应商档案',0,'','','','',1774073311,1777506018,0,'normal'),(266,'file',260,'business/supplier/multi','批量操作','','','','批量处理供应商档案',0,'','','','',1774073311,1777506018,0,'normal'),(267,'file',260,'business/supplier/selectpage','选择数据','','','','供应商档案下拉选择',0,'','','','',1774073311,1777506018,0,'normal'),(268,'file',261,'business/expense_request/index','查看','','','','查看费用申请',0,'','','','',1774073311,1777506018,0,'normal'),(269,'file',261,'business/expense_request/add','新增','','','','新增费用申请',0,'','','','',1774073311,1777506018,0,'normal'),(270,'file',261,'business/expense_request/edit','编辑','','','','编辑费用申请',0,'','','','',1774073311,1777506018,0,'normal'),(271,'file',261,'business/expense_request/del','删除','','','','删除费用申请',0,'','','','',1774073311,1777506018,0,'normal'),(272,'file',261,'business/expense_request/multi','批量操作','','','','批量处理费用申请',0,'','','','',1774073311,1777506018,0,'normal'),(273,'file',261,'business/expense_request/selectpage','选择数据','','','','费用申请下拉选择',0,'','','','',1774073311,1777506018,0,'normal'),(274,'file',261,'business/expense_request/createpaymentplan','生成付款计划','','','','根据费用申请生成付款计划',0,'','','','',1774073311,1777506018,0,'normal'),(275,'file',108,'ai/setting/applyrecommended','应用推荐模型','','','','切换到推荐模型',0,'','','','',1774086182,1777506018,0,'normal'),(276,'menu',212,'business/purchase_order','采购单','fa fa-shopping-cart','','','管理采购、外包和付款联动。',1,'','','','',1774094687,1777506018,23,'normal'),(277,'file',276,'business/purchase_order/index','查看','','','','查看采购单',0,'','','','',1774094687,1777506018,0,'normal'),(278,'file',276,'business/purchase_order/add','新增','','','','新增采购单',0,'','','','',1774094687,1777506018,0,'normal'),(279,'file',276,'business/purchase_order/edit','编辑','','','','编辑采购单',0,'','','','',1774094687,1777506018,0,'normal'),(280,'file',276,'business/purchase_order/del','删除','','','','删除采购单',0,'','','','',1774094687,1777506018,0,'normal'),(281,'file',276,'business/purchase_order/multi','批量操作','','','','批量处理采购单',0,'','','','',1774094687,1777506018,0,'normal'),(282,'file',276,'business/purchase_order/selectpage','选择数据','','','','采购单下拉选择',0,'','','','',1774094687,1777506018,0,'normal'),(283,'file',276,'business/purchase_order/createpaymentplan','生成付款计划','','','','根据采购单生成付款计划',0,'','','','',1774094687,1777506018,0,'normal'),(284,'menu',212,'business/purchase_settlement','采购结算','fa fa-balance-scale','','','跟进采购对账、结算和票据状态。',1,'','','','',1774104261,1777506018,21,'normal'),(285,'file',284,'business/purchase_settlement/index','查看','','','','查看采购结算',0,'','','','',1774104261,1777506018,0,'normal'),(286,'file',284,'business/purchase_settlement/add','新增','','','','新增采购结算',0,'','','','',1774104261,1777506018,0,'normal'),(287,'file',284,'business/purchase_settlement/edit','编辑','','','','编辑采购结算',0,'','','','',1774104261,1777506018,0,'normal'),(288,'file',284,'business/purchase_settlement/del','删除','','','','删除采购结算',0,'','','','',1774104261,1777506018,0,'normal'),(289,'file',284,'business/purchase_settlement/multi','批量操作','','','','批量处理采购结算',0,'','','','',1774104261,1777506018,0,'normal'),(290,'file',284,'business/purchase_settlement/selectpage','选择数据','','','','采购结算下拉选择',0,'','','','',1774104261,1777506018,0,'normal'),(291,'menu',212,'business/approval_template','审批模板','fa fa-sitemap','','','配置多级审批模板和审批节点。',1,'','','','',1774118361,1777506018,17,'normal'),(292,'file',291,'business/approval_template/index','查看','','','','查看审批模板',0,'','','','',1774118361,1777506018,0,'normal'),(293,'file',291,'business/approval_template/add','新增','','','','新增审批模板',0,'','','','',1774118361,1777506018,0,'normal'),(294,'file',291,'business/approval_template/edit','编辑','','','','编辑审批模板',0,'','','','',1774118361,1777506018,0,'normal'),(295,'file',291,'business/approval_template/del','删除','','','','删除审批模板',0,'','','','',1774118361,1777506018,0,'normal'),(296,'file',291,'business/approval_template/multi','批量操作','','','','批量处理审批模板',0,'','','','',1774118361,1777506018,0,'normal'),(297,'file',291,'business/approval_template/selectpage','选择数据','','','','审批模板下拉选择',0,'','','','',1774118361,1777506018,0,'normal'),(298,'file',291,'business/approval_template_step/index','查看','','','','查看审批模板节点',0,'','','','',1774118361,1777506018,0,'normal'),(299,'file',291,'business/approval_template_step/add','新增','','','','新增审批模板节点',0,'','','','',1774118361,1777506018,0,'normal'),(300,'file',291,'business/approval_template_step/edit','编辑','','','','编辑审批模板节点',0,'','','','',1774118361,1777506018,0,'normal'),(301,'file',291,'business/approval_template_step/del','删除','','','','删除审批模板节点',0,'','','','',1774118361,1777506018,0,'normal'),(302,'file',291,'business/approval_template_step/multi','批量操作','','','','批量处理审批模板节点',0,'','','','',1774118361,1777506018,0,'normal'),(303,'file',291,'business/approval_template_step/selectpage','选择数据','','','','审批模板节点下拉选择',0,'','','','',1774118361,1777506018,0,'normal'),(304,'menu',212,'business/purchase_reconciliation','采购对账','fa fa-random','','','核对采购金额和供应商对账结果。',1,'','','','',1774119951,1777506018,22,'normal'),(305,'menu',212,'business/purchase_invoice','采购发票','fa fa-file-text','','','登记到票、验票和附件。',1,'','','','',1774119951,1777506018,20,'normal'),(306,'file',304,'business/purchase_reconciliation/index','查看','','','','查看采购对账',0,'','','','',1774119951,1777506018,0,'normal'),(307,'file',304,'business/purchase_reconciliation/add','新增','','','','新增采购对账',0,'','','','',1774119951,1777506018,0,'normal'),(308,'file',304,'business/purchase_reconciliation/edit','编辑','','','','编辑采购对账',0,'','','','',1774119951,1777506018,0,'normal'),(309,'file',304,'business/purchase_reconciliation/del','删除','','','','删除采购对账',0,'','','','',1774119951,1777506018,0,'normal'),(310,'file',304,'business/purchase_reconciliation/multi','批量操作','','','','批量处理采购对账',0,'','','','',1774119951,1777506018,0,'normal'),(311,'file',304,'business/purchase_reconciliation/selectpage','选择数据','','','','采购对账下拉选择',0,'','','','',1774119951,1777506018,0,'normal'),(312,'file',305,'business/purchase_invoice/index','查看','','','','查看采购发票',0,'','','','',1774119951,1777506018,0,'normal'),(313,'file',305,'business/purchase_invoice/add','新增','','','','新增采购发票',0,'','','','',1774119951,1777506018,0,'normal'),(314,'file',305,'business/purchase_invoice/edit','编辑','','','','编辑采购发票',0,'','','','',1774119951,1777506018,0,'normal'),(315,'file',305,'business/purchase_invoice/del','删除','','','','删除采购发票',0,'','','','',1774119951,1777506018,0,'normal'),(316,'file',305,'business/purchase_invoice/multi','批量操作','','','','批量处理采购发票',0,'','','','',1774119951,1777506018,0,'normal'),(317,'file',305,'business/purchase_invoice/selectpage','选择数据','','','','采购发票下拉选择',0,'','','','',1774119951,1777506018,0,'normal'),(318,'menu',212,'business/workbench','业务工作台','fa fa-dashboard','','','集中处理客户、采购和付款审批。',1,NULL,'','','',1774120930,1777506018,28,'normal'),(319,'file',108,'ai/setting/selectpage','选择数据','','','','AI 配置下拉选择',0,NULL,'','','',1774120930,1777506018,0,'normal'),(320,'file',89,'finance/workbench/smartbookbootstrap','智能记账初始化','','','','获取智能记账初始化数据',0,NULL,'','','',1774120930,1777506018,0,'normal'),(321,'file',89,'finance/workbench/smartbook','智能记账解析','','','','解析一句话记账内容',0,NULL,'','','',1774120930,1777506018,0,'normal'),(322,'file',89,'finance/workbench/smartbooksave','智能记账保存','','','','将智能记账草稿写入系统',0,NULL,'','','',1774120930,1777506018,0,'normal'),(323,'file',318,'business/workbench/index','查看','','','','查看业务工作台',0,NULL,'','','',1774120930,1777506018,0,'normal'),(324,'file',111,'staff/audit/add','新增','','','','新增日志记录',0,NULL,'','','',1774120930,1777506018,0,'normal'),(325,'file',111,'staff/audit/edit','编辑','','','','编辑日志记录',0,NULL,'','','',1774120930,1777506018,0,'normal'),(326,'file',111,'staff/audit/del','删除','','','','删除日志记录',0,NULL,'','','',1774120930,1777506018,0,'normal'),(327,'file',111,'staff/audit/multi','批量操作','','','','批量处理日志记录',0,NULL,'','','',1774120930,1777506018,0,'normal'),(328,'file',7,'general/attachment/classify','分类','','','','附件分类',0,NULL,'','','',1774120930,1777506018,0,'normal'),(329,'file',9,'auth/admin/multi','批量操作','','','','批量处理后台账号',0,NULL,'','','',1774120930,1777506018,0,'normal'),(330,'file',10,'auth/adminlog/add','新增','','','','新增登录日志',0,NULL,'','','',1774120930,1777506018,0,'normal'),(331,'file',10,'auth/adminlog/edit','编辑','','','','编辑登录日志',0,NULL,'','','',1774120930,1777506018,0,'normal'),(332,'file',10,'auth/adminlog/multi','批量操作','','','','批量处理登录日志',0,NULL,'','','',1774120930,1777506018,0,'normal'),(333,'file',11,'auth/group/multi','批量操作','','','','批量处理权限组',0,NULL,'','','',1774120930,1777506018,0,'normal'),(334,'menu',212,'business/payment_request','付款申请','fa fa-credit-card-alt','','','处理采购结算后的付款申请。',1,NULL,'','','',1774123215,1777506018,7,'normal'),(335,'file',334,'business/payment_request/index','查看','','','','查看付款申请',0,NULL,'','','',1774123215,1777506018,0,'normal'),(336,'file',334,'business/payment_request/add','新增','','','','新增付款申请',0,NULL,'','','',1774123215,1777506018,0,'normal'),(337,'file',334,'business/payment_request/edit','编辑','','','','编辑付款申请',0,NULL,'','','',1774123215,1777506018,0,'normal'),(338,'file',334,'business/payment_request/del','删除','','','','删除付款申请',0,NULL,'','','',1774123215,1777506018,0,'normal'),(339,'file',334,'business/payment_request/multi','批量操作','','','','批量处理付款申请',0,NULL,'','','',1774123215,1777506018,0,'normal'),(340,'file',334,'business/payment_request/selectpage','选择数据','','','','付款申请下拉选择',0,NULL,'','','',1774123215,1777506018,0,'normal'),(341,'file',334,'business/payment_request/markpaid','标记已付款','','','','将审批通过的付款申请标记为已付款',0,NULL,'','','',1774123215,1777506018,0,'normal'),(342,'menu',2,'general/upgrade','在线更新','fa fa-cloud-download','','','检查 GitHub 更新、自动备份并执行在线更新。',1,NULL,'','','',1774468938,1777506018,15,'normal'),(343,'file',342,'general/upgrade/index','查看','','','','查看在线更新中心',0,NULL,'','','',1774468938,1777506018,0,'normal'),(344,'file',342,'general/upgrade/overview','总览','','','','获取在线更新总览数据',0,NULL,'','','',1774468938,1777506018,0,'normal'),(345,'file',342,'general/upgrade/saveconfig','保存配置','','','','保存 GitHub 更新源配置',0,NULL,'','','',1774468938,1777506018,0,'normal'),(346,'file',342,'general/upgrade/checkupdate','检查更新','','','','检查 GitHub 是否有新版本',0,NULL,'','','',1774468938,1777506018,0,'normal'),(347,'file',342,'general/upgrade/startupdate','执行更新','','','','创建备份后应用在线更新',0,NULL,'','','',1774468938,1777506018,0,'normal'),(354,'menu',2,'general/module','模块中心','fa fa-puzzle-piece','','','控制项目运营等业务开关。',1,NULL,'','','',1774481388,1777506018,20,'normal'),(355,'file',107,'ai/conversation/submit','提交后台任务','','','','提交 AI 后台分析任务',0,NULL,'','','',1774481388,1777506018,0,'normal'),(356,'file',107,'ai/conversation/run','执行后台任务','','','','执行 AI 后台分析任务',0,NULL,'','','',1774481388,1777506018,0,'normal'),(357,'file',107,'ai/conversation/status','查询任务状态','','','','查询 AI 后台任务状态',0,NULL,'','','',1774481388,1777506018,0,'normal'),(386,'file',354,'general/module/index','查看','','','','查看模块中心',0,NULL,'','','',1774481388,1777506018,0,'normal'),(387,'file',354,'general/module/save','保存开关','','','','保存模块开关配置',0,NULL,'','','',1774481388,1777506018,0,'normal'),(388,'file',89,'finance/workbench/reportprint','打印报表','','','','打印财务汇总报表',0,NULL,'','','',1777421522,1777506018,0,'normal'),(389,'file',89,'finance/workbench/reportexport','导出报表','','','','导出财务统计 CSV',0,NULL,'','','',1777421522,1777506018,0,'normal'),(390,'file',90,'finance/transaction/printview','打印预览','','','','资金流水打印预览',0,NULL,'','','',1777421522,1777506018,0,'normal'),(391,'file',91,'finance/invoice/printview','打印预览','','','','应收应付账单打印预览',0,NULL,'','','',1777421522,1777506018,0,'normal'),(392,'file',342,'general/upgrade/rollback','回滚','','','','从更新备份回滚系统文件和数据库',0,NULL,'','','',1777421522,1777506018,0,'normal');
/*!40000 ALTER TABLE `fa_auth_rule` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_business_approval`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_business_approval` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `approval_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `object_type` enum('contract','payment_plan','expense_request','purchase_order','payment_request') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'contract' COMMENT '审批对象类型',
  `object_id` int(10) unsigned NOT NULL DEFAULT '0',
  `object_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `object_title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customer_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customer_id` int(10) unsigned NOT NULL DEFAULT '0',
  `customer_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `contract_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `contract_id` int(10) unsigned NOT NULL DEFAULT '0',
  `contract_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `payment_plan_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `payment_plan_id` int(10) unsigned NOT NULL DEFAULT '0',
  `payment_plan_title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `expense_request_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `expense_request_id` int(10) unsigned NOT NULL DEFAULT '0',
  `expense_request_title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `purchase_order_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `purchase_order_id` int(10) unsigned NOT NULL DEFAULT '0',
  `purchase_order_title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `payment_request_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `payment_request_id` int(10) unsigned NOT NULL DEFAULT '0',
  `payment_request_title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `template_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `template_id` int(10) unsigned NOT NULL DEFAULT '0',
  `template_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `current_step` int(10) unsigned NOT NULL DEFAULT '1',
  `total_steps` int(10) unsigned NOT NULL DEFAULT '1',
  `current_step_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `step_snapshot_json` longtext COLLATE utf8mb4_unicode_ci,
  `decision_log_json` longtext COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','approved','rejected','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `submit_reason` text COLLATE utf8mb4_unicode_ci,
  `decision_note` text COLLATE utf8mb4_unicode_ci,
  `applicant_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `applicant_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `approver_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `approver_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `applied_at` datetime DEFAULT NULL,
  `decided_at` datetime DEFAULT NULL,
  `record_created_at` datetime DEFAULT NULL,
  `record_updated_at` datetime DEFAULT NULL,
  `created_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_business_approval_legacy_id` (`legacy_id`),
  UNIQUE KEY `uk_business_approval_approval_no` (`approval_no`),
  KEY `idx_business_approval_object` (`object_type`,`object_id`),
  KEY `idx_business_approval_status` (`status`),
  KEY `idx_business_approval_customer_id` (`customer_id`),
  KEY `idx_business_approval_contract_id` (`contract_id`),
  KEY `idx_business_approval_payment_plan_id` (`payment_plan_id`),
  KEY `idx_business_approval_applicant_admin_id` (`applicant_admin_id`),
  KEY `idx_business_approval_approver_admin_id` (`approver_admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='审批中心';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_business_approval` WRITE;
/*!40000 ALTER TABLE `fa_business_approval` DISABLE KEYS */;
INSERT INTO `fa_business_approval` VALUES (2,'approval_20260321133857_49b18dd4','SP-20260321-306EC8','contract',7,'contract_smoke_20260321133857','HT-SMOKE-20260321133857 / 审批冒烟合同-20260321133857','customer_1001',1,'星环科技','contract_smoke_20260321133857',7,'审批冒烟合同-20260321133857','',0,'','',0,'','',0,'','',0,'','',0,'',1,1,'人工审批',NULL,'[]','approved','审批冒烟合同原因-20260321133857','审批冒烟通过',1,'陈总',1,'陈总','2026-03-21 13:38:57','2026-03-21 13:38:57','2026-03-21 13:38:57','2026-03-21 13:38:57','user-1001',1,'陈总','user-1001',1,'陈总',1774071537,1774071537);
/*!40000 ALTER TABLE `fa_business_approval` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_business_approval_template`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_business_approval_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `object_type` enum('contract','payment_plan','expense_request','purchase_order','payment_request') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'contract',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `is_default` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `min_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `max_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `step_count` int(10) unsigned NOT NULL DEFAULT '0',
  `description` text COLLATE utf8mb4_unicode_ci,
  `record_created_at` datetime DEFAULT NULL,
  `record_updated_at` datetime DEFAULT NULL,
  `created_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_business_approval_template_legacy_id` (`legacy_id`),
  KEY `idx_business_approval_template_object_type` (`object_type`),
  KEY `idx_business_approval_template_status` (`status`),
  KEY `idx_business_approval_template_is_default` (`is_default`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='审批模板';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_business_approval_template` WRITE;
/*!40000 ALTER TABLE `fa_business_approval_template` DISABLE KEYS */;
INSERT INTO `fa_business_approval_template` VALUES (1,'approval_template_contract_default','合同双级审批','contract','active',1,0.00,0.00,2,'适用于标准合同审批，先业务负责人确认，再由管理层终审。','2026-03-22 02:39:21','2026-03-22 04:00:15','user-1008',8,'何浩','user-1008',8,'何浩',1774118361,1774123215),(2,'approval_template_payment_default','付款双级审批','payment_plan','active',1,0.00,0.00,2,'适用于供应商付款和成本付款，先财务复核，再由负责人确认。','2026-03-22 02:39:21','2026-03-22 04:00:15','user-1008',8,'何浩','user-1008',8,'何浩',1774118361,1774123215),(3,'approval_template_expense_default','费用双级审批','expense_request','active',1,0.00,0.00,2,'适用于费用申请，先财务初审，再由负责人终审。','2026-03-22 02:39:21','2026-03-22 04:00:15','user-1008',8,'何浩','user-1008',8,'何浩',1774118361,1774123215),(4,'approval_template_purchase_default','采购双级审批','purchase_order','active',1,0.00,0.00,2,'适用于采购单审批，先财务评估，再由管理层确认。','2026-03-22 02:39:21','2026-03-22 04:00:15','user-1008',8,'何浩','user-1008',8,'何浩',1774118361,1774123215),(7,'approval_template_payment_request_default','付款申请双级审批','payment_request','active',1,0.00,0.00,2,'适用于采购结算后的付款申请，先财务复核，再由负责人确认付款。','2026-03-22 04:00:15','2026-03-22 04:00:15','user-1008',8,'何浩','user-1008',8,'何浩',1774123215,1774123215);
/*!40000 ALTER TABLE `fa_business_approval_template` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_business_approval_template_step`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_business_approval_template_step` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `template_id` int(10) unsigned NOT NULL DEFAULT '0',
  `template_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `template_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `object_type` enum('contract','payment_plan','expense_request','purchase_order','payment_request') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'contract',
  `step_no` int(10) unsigned NOT NULL DEFAULT '1',
  `step_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `approver_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `approver_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `approver_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `record_created_at` datetime DEFAULT NULL,
  `record_updated_at` datetime DEFAULT NULL,
  `created_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_business_approval_template_step_legacy_id` (`legacy_id`),
  UNIQUE KEY `uk_business_approval_template_step_order` (`template_id`,`step_no`),
  KEY `idx_business_approval_template_step_template_id` (`template_id`),
  KEY `idx_business_approval_template_step_status` (`status`),
  KEY `idx_business_approval_template_step_approver_admin_id` (`approver_admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='审批模板节点';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_business_approval_template_step` WRITE;
/*!40000 ALTER TABLE `fa_business_approval_template_step` DISABLE KEYS */;
INSERT INTO `fa_business_approval_template_step` VALUES (1,'approval_template_contract_default_step_1',1,'approval_template_contract_default','合同双级审批','contract',1,'业务复核',4,'user-1004','顾宁','active','确认合同范围、报价和交付边界。','2026-03-22 02:39:21','2026-03-22 04:00:15','user-1008',8,'何浩','user-1008',8,'何浩',1774118361,1774123215),(2,'approval_template_contract_default_step_2',1,'approval_template_contract_default','合同双级审批','contract',2,'管理终审',8,'user-1008','何浩','active','确认风险、回款条款和合同生效安排。','2026-03-22 02:39:21','2026-03-22 04:00:15','user-1008',8,'何浩','user-1008',8,'何浩',1774118361,1774123215),(3,'approval_template_payment_default_step_1',2,'approval_template_payment_default','付款双级审批','payment_plan',1,'财务复核',2,'user-1002','李娜','active','确认付款金额、票据和计划日期。','2026-03-22 02:39:21','2026-03-22 04:00:15','user-1008',8,'何浩','user-1008',8,'何浩',1774118361,1774123215),(4,'approval_template_payment_default_step_2',2,'approval_template_payment_default','付款双级审批','payment_plan',2,'负责人确认',8,'user-1008','何浩','active','确认付款必要性和预算占用。','2026-03-22 02:39:21','2026-03-22 04:00:15','user-1008',8,'何浩','user-1008',8,'何浩',1774118361,1774123215),(5,'approval_template_expense_default_step_1',3,'approval_template_expense_default','费用双级审批','expense_request',1,'财务初审',2,'user-1002','李娜','active','确认预算、税票和付款计划。','2026-03-22 02:39:21','2026-03-22 04:00:15','user-1008',8,'何浩','user-1008',8,'何浩',1774118361,1774123215),(6,'approval_template_expense_default_step_2',3,'approval_template_expense_default','费用双级审批','expense_request',2,'负责人终审',8,'user-1008','何浩','active','确认费用必要性和业务收益。','2026-03-22 02:39:21','2026-03-22 04:00:15','user-1008',8,'何浩','user-1008',8,'何浩',1774118361,1774123215),(7,'approval_template_purchase_default_step_1',4,'approval_template_purchase_default','采购双级审批','purchase_order',1,'采购评估',2,'user-1002','李娜','active','确认金额、账期和供应商结算条件。','2026-03-22 02:39:21','2026-03-22 04:00:15','user-1008',8,'何浩','user-1008',8,'何浩',1774118361,1774123215),(8,'approval_template_purchase_default_step_2',4,'approval_template_purchase_default','采购双级审批','purchase_order',2,'管理确认',8,'user-1008','何浩','active','确认采购必要性和最终执行。','2026-03-22 02:39:21','2026-03-22 04:00:15','user-1008',8,'何浩','user-1008',8,'何浩',1774118361,1774123215),(13,'approval_template_payment_request_default_step_1',7,'approval_template_payment_request_default','付款申请双级审批','payment_request',1,'财务复核',2,'user-1002','李娜','active','确认付款金额、结算单、票据和供应商信息。','2026-03-22 04:00:15','2026-03-22 04:00:15','user-1008',8,'何浩','user-1008',8,'何浩',1774123215,1774123215),(14,'approval_template_payment_request_default_step_2',7,'approval_template_payment_request_default','付款申请双级审批','payment_request',2,'负责人确认',8,'user-1008','何浩','active','确认付款必要性和实际支付安排。','2026-03-22 04:00:15','2026-03-22 04:00:15','user-1008',8,'何浩','user-1008',8,'何浩',1774123215,1774123215);
/*!40000 ALTER TABLE `fa_business_approval_template_step` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_business_contract`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_business_contract` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customer_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customer_id` int(10) unsigned NOT NULL DEFAULT '0',
  `customer_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `contract_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `category` enum('implementation','subscription','maintenance','custom','service','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'service',
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `signed_at` date DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('draft','review','active','delivering','completed','cancelled','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `approval_status` enum('none','pending','approved','rejected','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `approval_updated_at` datetime DEFAULT NULL,
  `owner` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `owner_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `project_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `project_id` int(10) unsigned NOT NULL DEFAULT '0',
  `app_project_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `app_project_id` int(10) unsigned NOT NULL DEFAULT '0',
  `invoice_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `received_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `pending_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `attachment_ids_json` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `record_created_at` datetime DEFAULT NULL,
  `record_updated_at` datetime DEFAULT NULL,
  `created_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_business_contract_legacy_id` (`legacy_id`),
  UNIQUE KEY `uk_business_contract_contract_no` (`contract_no`),
  KEY `idx_business_contract_customer_id` (`customer_id`),
  KEY `idx_business_contract_owner_admin_id` (`owner_admin_id`),
  KEY `idx_business_contract_project_id` (`project_id`),
  KEY `idx_business_contract_app_project_id` (`app_project_id`),
  KEY `idx_business_contract_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='合同台账';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_business_contract` WRITE;
/*!40000 ALTER TABLE `fa_business_contract` DISABLE KEYS */;
INSERT INTO `fa_business_contract` VALUES (1,'contract_1001','customer_1001',1,'星环科技','HT-2026-001','企业官网重构与年度运维服务','implementation',180000.00,'2026-01-10','2026-01-12','2026-12-31','delivering','none',NULL,'顾宁',4,'prj-1001',1,'',0,180000.00,90000.00,90000.00,'[]','包含官网重构、部署、运维和季度复盘服务','2026-03-21 03:39:11','2026-03-21 03:39:11','user-1004',4,'顾宁','user-1004',4,'顾宁',1774035551,1774035551),(2,'contract_1002','customer_1002',2,'北辰数据','HT-2026-002','客服工单 SaaS 年度订阅','subscription',96000.00,'2026-02-05','2026-02-10','2027-02-09','active','none',NULL,'李娜',2,'prj-1002',2,'ops-1002',2,96000.00,32000.00,64000.00,'[]','SaaS 年费合同，包含 50 个客服坐席和升级包','2026-03-21 03:39:11','2026-03-21 03:39:11','user-1004',4,'顾宁','user-1004',4,'顾宁',1774035551,1774035551),(3,'contract_1003','customer_1003',3,'海右医药','HT-2026-003','增长投放与数据分析服务','service',128000.00,'2026-03-05','2026-03-10','2026-08-31','review','none',NULL,'顾宁',4,'',0,'ops-1001',1,50000.00,0.00,128000.00,'[]','合同正在法务复核，启动款未到账','2026-03-21 03:39:11','2026-03-21 03:39:11','user-1004',4,'顾宁','user-1004',4,'顾宁',1774035551,1774035551),(7,'contract_smoke_20260321133857','customer_1001',1,'星环科技','HT-SMOKE-20260321133857','审批冒烟合同-20260321133857','service',12345.67,'2026-03-21','2026-03-21','2026-12-31','active','approved','2026-03-21 13:38:57','陈总',1,'',0,'',0,0.00,0.00,12345.67,'[]','审批冒烟测试合同','2026-03-21 13:38:57','2026-03-21 13:38:57','user-1001',1,'陈总','user-1001',1,'陈总',1774071537,1774071537);
/*!40000 ALTER TABLE `fa_business_contract` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_business_customer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_business_customer` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `company_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `short_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `industry` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customer_level` enum('a','b','c','d') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'b',
  `source` enum('direct','referral','channel','existing','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'direct',
  `stage` enum('lead','proposal','contracted','delivery','repeat','lost') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'lead',
  `status` enum('active','paused','lost') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `owner` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `owner_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `contact_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `contact_phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `contact_email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `city` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `last_follow_up_at` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `record_created_at` datetime DEFAULT NULL,
  `record_updated_at` datetime DEFAULT NULL,
  `created_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_business_customer_legacy_id` (`legacy_id`),
  KEY `idx_business_customer_owner_admin_id` (`owner_admin_id`),
  KEY `idx_business_customer_status` (`status`),
  KEY `idx_business_customer_company_name` (`company_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='客户档案';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_business_customer` WRITE;
/*!40000 ALTER TABLE `fa_business_customer` DISABLE KEYS */;
INSERT INTO `fa_business_customer` VALUES (1,'customer_1001','星环科技','星环','企业服务','a','existing','delivery','active','顾宁',4,'林涛','13800001111','lin@xinghuan.example','上海','2026-03-18 10:00:00','官网重构和年度运维的重点客户','2026-03-21 03:39:11','2026-03-21 09:07:20','user-1004',4,'顾宁','user-1004',4,'顾宁',1774035551,1774055240),(2,'customer_1002','北辰数据','北辰','SaaS','a','direct','contracted','active','李娜',2,'赵静','13900002222','zhao@beichen.example','杭州','2026-03-19 16:30:00','客服工单 SaaS 续费和二期增购在跟进','2026-03-21 03:39:11','2026-03-21 09:06:45','user-1004',4,'顾宁','user-1004',4,'顾宁',1774035551,1774055205),(3,'customer_1003','海右医药','海右','医药','b','referral','proposal','active','顾宁',4,'周主任','13700003333','zhou@haiyou.example','济南','2026-03-20 14:00:00','正在推进增长投放和数据分析服务合同','2026-03-21 03:39:11','2026-03-21 09:06:45','user-1004',4,'顾宁','user-1004',4,'顾宁',1774035551,1774055205);
/*!40000 ALTER TABLE `fa_business_customer` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_business_customer_followup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_business_customer_followup` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customer_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customer_id` int(10) unsigned NOT NULL DEFAULT '0',
  `customer_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `contract_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `contract_id` int(10) unsigned NOT NULL DEFAULT '0',
  `contract_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `followup_type` enum('call','wechat','meeting','visit','proposal','payment','service','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'meeting',
  `follow_up_at` datetime DEFAULT NULL,
  `next_follow_up_at` datetime DEFAULT NULL,
  `status` enum('planned','done','waiting','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'done',
  `owner` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `owner_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `contact_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `result_summary` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `record_created_at` datetime DEFAULT NULL,
  `record_updated_at` datetime DEFAULT NULL,
  `created_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_business_customer_followup_legacy_id` (`legacy_id`),
  KEY `idx_business_customer_followup_customer_id` (`customer_id`),
  KEY `idx_business_customer_followup_contract_id` (`contract_id`),
  KEY `idx_business_customer_followup_owner_admin_id` (`owner_admin_id`),
  KEY `idx_business_customer_followup_follow_up_at` (`follow_up_at`),
  KEY `idx_business_customer_followup_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='客户跟进记录';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_business_customer_followup` WRITE;
/*!40000 ALTER TABLE `fa_business_customer_followup` DISABLE KEYS */;
INSERT INTO `fa_business_customer_followup` VALUES (1,'followup_1001','customer_1001',1,'星环科技','contract_1001',1,'企业官网重构与年度运维服务','官网重构阶段周会确认','meeting','2026-03-18 10:00:00','2026-03-25 15:00:00','done','顾宁',4,'林涛','确认首页和产品页在 3 月底完成联调，尾款需要验收后发起。','项目团队和客户均已确认排期。','2026-03-21 09:05:20','2026-03-21 09:05:20','user-1004',4,'顾宁','user-1004',4,'顾宁',1774055120,1774055120),(2,'followup_1002','customer_1002',2,'北辰数据','contract_1002',2,'客服工单 SaaS 年度订阅','续费尾款回款推进','payment','2026-03-19 16:30:00','2026-03-24 11:00:00','waiting','李娜',2,'赵静','客户已提交内部付款流程，预计下周确认到账时间。','财务需在下周继续追单。','2026-03-21 09:05:20','2026-03-21 09:05:20','user-1004',4,'顾宁','user-1004',4,'顾宁',1774055120,1774055120),(3,'followup_1003','customer_1003',3,'海右医药','contract_1003',3,'增长投放与数据分析服务','法务意见反馈','proposal','2026-03-20 14:00:00','2026-03-23 10:30:00','planned','顾宁',4,'周主任','客户法务要求补充数据权限条款，方案版本待更新。','销售和法务需要共同确认修订版。','2026-03-21 09:05:20','2026-03-21 09:05:20','user-1004',4,'顾宁','user-1004',4,'顾宁',1774055120,1774055120);
/*!40000 ALTER TABLE `fa_business_customer_followup` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_business_expense_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_business_expense_request` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `request_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `expense_type` enum('procurement','travel','marketing','service','software','outsourcing','office','refund','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'procurement',
  `supplier_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `supplier_id` int(10) unsigned NOT NULL DEFAULT '0',
  `supplier_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customer_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customer_id` int(10) unsigned NOT NULL DEFAULT '0',
  `customer_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `contract_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `contract_id` int(10) unsigned NOT NULL DEFAULT '0',
  `contract_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `request_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `requested_at` datetime DEFAULT NULL,
  `expected_pay_date` date DEFAULT NULL,
  `status` enum('draft','pending_approval','approved','processing','paid','rejected','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `approval_status` enum('none','pending','approved','rejected','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `approval_updated_at` datetime DEFAULT NULL,
  `owner` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `owner_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `payment_plan_id` int(10) unsigned NOT NULL DEFAULT '0',
  `payment_plan_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `payment_plan_title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `attachment_ids_json` text COLLATE utf8mb4_unicode_ci,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `record_created_at` datetime DEFAULT NULL,
  `record_updated_at` datetime DEFAULT NULL,
  `created_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_business_expense_request_legacy_id` (`legacy_id`),
  UNIQUE KEY `uk_business_expense_request_request_no` (`request_no`),
  KEY `idx_business_expense_request_supplier_id` (`supplier_id`),
  KEY `idx_business_expense_request_customer_id` (`customer_id`),
  KEY `idx_business_expense_request_contract_id` (`contract_id`),
  KEY `idx_business_expense_request_owner_admin_id` (`owner_admin_id`),
  KEY `idx_business_expense_request_status` (`status`),
  KEY `idx_business_expense_request_approval_status` (`approval_status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='费用申请';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_business_expense_request` WRITE;
/*!40000 ALTER TABLE `fa_business_expense_request` DISABLE KEYS */;
INSERT INTO `fa_business_expense_request` VALUES (1,'expense_request_1001','FY-20260320-ALPHA','云资源季度续费','software','supplier_1001',1,'阿尔法云服务','customer_1001',0,'鏄熺幆绉戞妧','contract_1001',1,'浼佷笟瀹樼綉閲嶆瀯涓庡勾搴﹁繍缁存湇鍔?',12000.00,'2026-03-20 10:00:00','2026-03-28','processing','approved','2026-03-20 15:00:00','李娜',2,0,'payment_plan_1001','浜戜富鏈轰笌 CDN 瀛ｅ害浠樻','[]','网站资源季度续费，需要在续费日前完成付款','已由财务确认并生成付款计划','2026-03-21 14:08:31','2026-03-21 14:08:31','user-1004',4,'顾宁','user-1004',4,'顾宁',1774073311,1774073311),(2,'expense_request_1002','FY-20260321-QQPD','渠道返佣首期申请','marketing','supplier_1002',2,'青穹渠道伙伴','customer_1003',0,'娴峰彸鍖昏嵂','contract_1003',3,'澧為暱鎶曟斁涓庢暟鎹垎鏋愭湇鍔?',15000.00,'2026-03-21 09:30:00','2026-04-15','approved','approved','2026-03-21 11:00:00','顾宁',4,0,'','','[]','合同返佣条款已经确认，需在合同生效后支付首期返佣','待财务确认后生成付款计划','2026-03-21 14:08:31','2026-03-21 14:08:31','user-1004',4,'顾宁','user-1004',4,'顾宁',1774073311,1774073311),(3,'expense_request_1003','FY-20260321-YSFW','运营支持设计外包','outsourcing','supplier_1003',3,'云杉数字服务','customer_1002',0,'鍖楄景鏁版嵁','contract_1002',2,'瀹㈡湇宸ュ崟 SaaS 骞村害璁㈤槄',8600.00,'2026-03-21 14:20:00','2026-03-29','draft','none',NULL,'顾宁',4,0,'','','[]','项目二期交付前需要补一轮设计支持和素材整理','待项目经理确认范围后再发起审批','2026-03-21 14:08:31','2026-03-21 14:08:31','user-1004',4,'顾宁','user-1004',4,'顾宁',1774073311,1774073311);
/*!40000 ALTER TABLE `fa_business_expense_request` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_business_payment_plan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_business_payment_plan` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `contract_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `contract_id` int(10) unsigned NOT NULL DEFAULT '0',
  `contract_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customer_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customer_id` int(10) unsigned NOT NULL DEFAULT '0',
  `customer_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `purchase_order_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `purchase_order_id` int(10) unsigned NOT NULL DEFAULT '0',
  `purchase_order_title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `expense_request_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `expense_request_id` int(10) unsigned NOT NULL DEFAULT '0',
  `expense_request_title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `payee_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `plan_type` enum('supplier','implementation','commission','service','refund','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'supplier',
  `due_date` date DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` enum('pending','processing','paid','overdue','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `approval_status` enum('none','pending','approved','rejected','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `approval_updated_at` datetime DEFAULT NULL,
  `owner` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `owner_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `actual_paid_at` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `record_created_at` datetime DEFAULT NULL,
  `record_updated_at` datetime DEFAULT NULL,
  `created_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_business_payment_plan_legacy_id` (`legacy_id`),
  KEY `idx_business_payment_plan_contract_id` (`contract_id`),
  KEY `idx_business_payment_plan_customer_id` (`customer_id`),
  KEY `idx_business_payment_plan_owner_admin_id` (`owner_admin_id`),
  KEY `idx_business_payment_plan_due_date` (`due_date`),
  KEY `idx_business_payment_plan_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='付款计划';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_business_payment_plan` WRITE;
/*!40000 ALTER TABLE `fa_business_payment_plan` DISABLE KEYS */;
INSERT INTO `fa_business_payment_plan` VALUES (1,'payment_plan_1001','contract_1001',1,'企业官网重构与年度运维服务','customer_1001',1,'星环科技','purchase_order_1001',1,'云资源季度续费','expense_request_1001',1,'云资源季度续费','云主机与 CDN 季度付款','阿尔法云服务','supplier','2026-03-28',12000.00,'pending','none',NULL,'李娜',2,NULL,'网站上线前需要完成本季度资源续费。','2026-03-21 09:05:20','2026-03-21 09:05:20','user-1004',4,'顾宁','user-1004',4,'顾宁',1774055120,1774055120),(2,'payment_plan_1002','contract_1002',2,'客服工单 SaaS 年度订阅','customer_1002',2,'北辰数据','',0,'','',0,'','实施顾问驻场服务费','深蓝实施团队','implementation','2026-04-05',18000.00,'processing','none',NULL,'李娜',2,NULL,'待客户二期需求确认后付款。','2026-03-21 09:05:20','2026-03-21 09:05:20','user-1004',4,'顾宁','user-1004',4,'顾宁',1774055120,1774055120),(3,'payment_plan_1003','contract_1003',3,'增长投放与数据分析服务','customer_1003',3,'海右医药','',0,'','',0,'','渠道返佣首期付款','青禾渠道伙伴','commission','2026-04-15',15000.00,'pending','none',NULL,'顾宁',4,NULL,'待合同正式生效后启动返佣申请。','2026-03-21 09:05:20','2026-03-21 09:05:20','user-1004',4,'顾宁','user-1004',4,'顾宁',1774055120,1774055120),(7,'payment_smoke_20260321133857','contract_smoke_20260321133857',7,'审批冒烟合同-20260321133857','customer_1001',1,'星环科技','',0,'','',0,'','审批冒烟付款-20260321133857','审批冒烟供应商','supplier','2026-03-31',2345.67,'pending','none',NULL,'陈总',1,NULL,'审批冒烟测试付款计划','2026-03-21 13:38:57','2026-03-21 13:38:57','user-1001',1,'陈总','user-1001',1,'陈总',1774071537,1774071537),(28,'payment_plan_20260322044301_51bdd441','contract_1003',3,'增长投放与数据分析服务','customer_1003',3,'海右医药','purchase_order_1003',3,'增长投放素材制作','',0,'','采购付款 / 增长投放素材制作','青穹渠道伙伴','commission','2026-04-15',15000.00,'pending','none',NULL,'顾宁',4,NULL,'由采购单自动生成：PO-20260321-003 / 增长投放素材制作','2026-03-22 04:43:01','2026-03-22 04:43:01','user-1001',1,'陈总','user-1001',1,'陈总',NULL,NULL);
/*!40000 ALTER TABLE `fa_business_payment_plan` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_business_payment_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_business_payment_request` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `request_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `purchase_order_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `purchase_order_id` int(10) unsigned NOT NULL DEFAULT '0',
  `purchase_order_title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `settlement_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `settlement_id` int(10) unsigned NOT NULL DEFAULT '0',
  `settlement_title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `payment_plan_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `payment_plan_id` int(10) unsigned NOT NULL DEFAULT '0',
  `payment_plan_title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `supplier_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `supplier_id` int(10) unsigned NOT NULL DEFAULT '0',
  `supplier_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customer_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customer_id` int(10) unsigned NOT NULL DEFAULT '0',
  `customer_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `contract_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `contract_id` int(10) unsigned NOT NULL DEFAULT '0',
  `contract_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `request_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `paid_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `requested_at` datetime DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `status` enum('draft','pending_approval','approved','paid','rejected','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `approval_status` enum('none','pending','approved','rejected','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `approval_updated_at` datetime DEFAULT NULL,
  `owner` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `owner_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `attachment_ids_json` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `record_created_at` datetime DEFAULT NULL,
  `record_updated_at` datetime DEFAULT NULL,
  `created_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_business_payment_request_legacy_id` (`legacy_id`),
  UNIQUE KEY `uk_business_payment_request_request_no` (`request_no`),
  KEY `idx_business_payment_request_purchase_order_id` (`purchase_order_id`),
  KEY `idx_business_payment_request_settlement_id` (`settlement_id`),
  KEY `idx_business_payment_request_payment_plan_id` (`payment_plan_id`),
  KEY `idx_business_payment_request_supplier_id` (`supplier_id`),
  KEY `idx_business_payment_request_customer_id` (`customer_id`),
  KEY `idx_business_payment_request_contract_id` (`contract_id`),
  KEY `idx_business_payment_request_owner_admin_id` (`owner_admin_id`),
  KEY `idx_business_payment_request_status` (`status`),
  KEY `idx_business_payment_request_approval_status` (`approval_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='付款申请';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_business_payment_request` WRITE;
/*!40000 ALTER TABLE `fa_business_payment_request` DISABLE KEYS */;
/*!40000 ALTER TABLE `fa_business_payment_request` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_business_purchase_invoice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_business_purchase_invoice` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `invoice_no` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `purchase_order_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `purchase_order_id` int(10) unsigned NOT NULL DEFAULT '0',
  `purchase_order_title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `settlement_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `settlement_id` int(10) unsigned NOT NULL DEFAULT '0',
  `settlement_title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `supplier_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `supplier_id` int(10) unsigned NOT NULL DEFAULT '0',
  `supplier_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customer_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customer_id` int(10) unsigned NOT NULL DEFAULT '0',
  `customer_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `contract_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `contract_id` int(10) unsigned NOT NULL DEFAULT '0',
  `contract_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `invoice_type` enum('vat_special','vat_normal','service','electronic','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'vat_normal',
  `invoice_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `untaxed_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `invoiced_at` date DEFAULT NULL,
  `received_at` datetime DEFAULT NULL,
  `status` enum('pending','received','verified','returned','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `owner` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `owner_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `attachment_ids_json` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `record_created_at` datetime DEFAULT NULL,
  `record_updated_at` datetime DEFAULT NULL,
  `created_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_business_purchase_invoice_legacy_id` (`legacy_id`),
  KEY `idx_business_purchase_invoice_invoice_no` (`invoice_no`),
  KEY `idx_business_purchase_invoice_purchase_order_id` (`purchase_order_id`),
  KEY `idx_business_purchase_invoice_settlement_id` (`settlement_id`),
  KEY `idx_business_purchase_invoice_supplier_id` (`supplier_id`),
  KEY `idx_business_purchase_invoice_status` (`status`),
  KEY `idx_business_purchase_invoice_owner_admin_id` (`owner_admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='采购发票';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_business_purchase_invoice` WRITE;
/*!40000 ALTER TABLE `fa_business_purchase_invoice` DISABLE KEYS */;
INSERT INTO `fa_business_purchase_invoice` VALUES (1,'purchase_invoice_1001','FP-20260321-001','云资源季度续费发票','purchase_order_1001',1,'云资源季度续费','purchase_settlement_1001',1,'云资源季度续费结算','supplier_1001',1,'阿尔法云服务','customer_1001',1,'星环科技','contract_1001',1,'企业官网重构与年度运维服务','electronic',6000.00,5660.38,339.62,'2026-03-21','2026-03-21 18:20:00','received','李娜',2,'[]','示例到票记录，剩余发票待供应商补齐。','2026-03-22 03:05:51','2026-03-22 03:05:51','user-1002',2,'李娜','user-1002',2,'李娜',1774119951,1774119951);
/*!40000 ALTER TABLE `fa_business_purchase_invoice` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_business_purchase_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_business_purchase_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `order_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `purchase_type` enum('software','cloud','service','outsourcing','marketing','hardware','office','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'service',
  `supplier_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `supplier_id` int(10) unsigned NOT NULL DEFAULT '0',
  `supplier_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customer_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customer_id` int(10) unsigned NOT NULL DEFAULT '0',
  `customer_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `contract_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `contract_id` int(10) unsigned NOT NULL DEFAULT '0',
  `contract_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `order_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `ordered_at` datetime DEFAULT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `actual_delivery_at` datetime DEFAULT NULL,
  `status` enum('draft','pending_approval','approved','processing','completed','rejected','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `approval_status` enum('none','pending','approved','rejected','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `approval_updated_at` datetime DEFAULT NULL,
  `owner` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `owner_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `payment_plan_id` int(10) unsigned NOT NULL DEFAULT '0',
  `payment_plan_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `payment_plan_title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `reconciliation_id` int(10) unsigned NOT NULL DEFAULT '0',
  `reconciliation_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `reconciliation_title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `settlement_id` int(10) unsigned NOT NULL DEFAULT '0',
  `settlement_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `settlement_title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `attachment_ids_json` text COLLATE utf8mb4_unicode_ci,
  `purchase_content` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `record_created_at` datetime DEFAULT NULL,
  `record_updated_at` datetime DEFAULT NULL,
  `created_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_business_purchase_order_legacy_id` (`legacy_id`),
  UNIQUE KEY `uk_business_purchase_order_order_no` (`order_no`),
  KEY `idx_business_purchase_order_supplier_id` (`supplier_id`),
  KEY `idx_business_purchase_order_customer_id` (`customer_id`),
  KEY `idx_business_purchase_order_contract_id` (`contract_id`),
  KEY `idx_business_purchase_order_owner_admin_id` (`owner_admin_id`),
  KEY `idx_business_purchase_order_status` (`status`),
  KEY `idx_business_purchase_order_approval_status` (`approval_status`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='采购单';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_business_purchase_order` WRITE;
/*!40000 ALTER TABLE `fa_business_purchase_order` DISABLE KEYS */;
INSERT INTO `fa_business_purchase_order` VALUES (1,'purchase_order_1001','PO-20260320-001','云资源季度续费','cloud','supplier_1001',1,'阿尔法云服务','customer_1001',1,'星环科技','contract_1001',1,'企业官网重构与年度运维服务',12000.00,'2026-03-20 10:00:00','2026-03-28',NULL,'processing','approved','2026-03-20 15:00:00','李娜',2,1,'payment_plan_1001','云主机与 CDN 季度付款',1,'purchase_reconciliation_1001','云资源季度续费对账',1,'purchase_settlement_1001','云资源季度续费结算','[]','续费官网部署所需的云主机、对象存储和 CDN 资源。','已生成付款计划，等待财务打款。','2026-03-21 20:04:48','2026-03-21 20:04:48','user-1004',4,'顾宁','user-1004',4,'顾宁',1774094688,1774094688),(2,'purchase_order_1002','PO-20260321-002','实施顾问驻场支持','service','supplier_1003',3,'云杉数字服务','customer_1002',0,'北景数据','contract_1002',2,'客服工单 SaaS 年度订阅',8600.00,'2026-03-21 14:20:00','2026-03-29',NULL,'draft','none',NULL,'顾宁',4,0,'','',0,'','',0,'','','[]','补充项目二期交付需要的设计支持、素材整理和运营协作。','待项目经理确认采购范围后提交审批。','2026-03-21 20:04:48','2026-03-21 20:04:48','user-1004',4,'顾宁','user-1004',4,'顾宁',1774094688,1774094688),(3,'purchase_order_1003','PO-20260321-003','增长投放素材制作','marketing','supplier_1002',2,'青穹渠道伙伴','customer_1003',3,'海右医药','contract_1003',3,'增长投放与数据分析服务',15000.00,'2026-03-21 09:30:00','2026-04-15',NULL,'processing','approved','2026-03-21 11:00:00','顾宁',4,28,'payment_plan_20260322044301_51bdd441','采购付款 / 增长投放素材制作',0,'','',0,'','','[]','为增长投放准备首期素材、渠道对接和广告账户配置。','审批通过后可直接生成付款计划。','2026-03-21 20:04:48','2026-03-22 04:43:01','user-1004',4,'顾宁','user-1001',1,'陈总',1774094688,1774125781),(4,'purchase_order_20260321200503_86146de7','PO-20260321-45752E','smoke-purchase-20260321200503','service','supplier_20260321200503_a68f79f6',9,'smoke-po-supplier-20260321200503','customer_1001',1,'星环科技','contract_1001',1,'企业官网重构与年度运维服务',456.78,'2026-03-21 20:05:03','2026-03-26',NULL,'approved','none',NULL,'顾宁',4,0,'','',0,'','',0,'','','[]','smoke purchase order','smoke-test','2026-03-21 20:05:03','2026-03-21 20:05:03','user-1001',1,'陈总','user-1001',1,'陈总',1774094703,1774094703);
/*!40000 ALTER TABLE `fa_business_purchase_order` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_business_purchase_reconciliation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_business_purchase_reconciliation` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `reconcile_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `purchase_order_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `purchase_order_id` int(10) unsigned NOT NULL DEFAULT '0',
  `purchase_order_title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `payment_plan_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `payment_plan_id` int(10) unsigned NOT NULL DEFAULT '0',
  `payment_plan_title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `supplier_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `supplier_id` int(10) unsigned NOT NULL DEFAULT '0',
  `supplier_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customer_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customer_id` int(10) unsigned NOT NULL DEFAULT '0',
  `customer_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `contract_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `contract_id` int(10) unsigned NOT NULL DEFAULT '0',
  `contract_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `order_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `confirmed_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `variance_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `reconciled_at` datetime DEFAULT NULL,
  `status` enum('draft','reconciling','confirmed','disputed','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `owner` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `owner_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `attachment_ids_json` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `record_created_at` datetime DEFAULT NULL,
  `record_updated_at` datetime DEFAULT NULL,
  `created_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_business_purchase_reconciliation_legacy_id` (`legacy_id`),
  UNIQUE KEY `uk_business_purchase_reconciliation_reconcile_no` (`reconcile_no`),
  KEY `idx_business_purchase_reconciliation_purchase_order_id` (`purchase_order_id`),
  KEY `idx_business_purchase_reconciliation_payment_plan_id` (`payment_plan_id`),
  KEY `idx_business_purchase_reconciliation_supplier_id` (`supplier_id`),
  KEY `idx_business_purchase_reconciliation_status` (`status`),
  KEY `idx_business_purchase_reconciliation_owner_admin_id` (`owner_admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='采购对账';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_business_purchase_reconciliation` WRITE;
/*!40000 ALTER TABLE `fa_business_purchase_reconciliation` DISABLE KEYS */;
INSERT INTO `fa_business_purchase_reconciliation` VALUES (1,'purchase_reconciliation_1001','PR-20260321-001','云资源季度续费对账','purchase_order_1001',1,'云资源季度续费','payment_plan_1001',1,'云主机与 CDN 季度付款','supplier_1001',1,'阿尔法云服务','customer_1001',1,'星环科技','contract_1001',1,'企业官网重构与年度运维服务',12000.00,12000.00,0.00,'2026-03-21 16:00:00','confirmed','李娜',2,'[]','已和供应商确认季度续费金额及资源明细。','2026-03-22 03:05:51','2026-03-22 03:05:51','user-1002',2,'李娜','user-1002',2,'李娜',1774119951,1774119951);
/*!40000 ALTER TABLE `fa_business_purchase_reconciliation` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_business_purchase_settlement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_business_purchase_settlement` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `settlement_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `purchase_order_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `purchase_order_id` int(10) unsigned NOT NULL DEFAULT '0',
  `purchase_order_title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `payment_plan_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `payment_plan_id` int(10) unsigned NOT NULL DEFAULT '0',
  `payment_plan_title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `supplier_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `supplier_id` int(10) unsigned NOT NULL DEFAULT '0',
  `supplier_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customer_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customer_id` int(10) unsigned NOT NULL DEFAULT '0',
  `customer_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `contract_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `contract_id` int(10) unsigned NOT NULL DEFAULT '0',
  `contract_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `settlement_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `paid_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `invoiced_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `balance_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `invoice_status` enum('none','partial','received') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `invoice_no` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `invoiced_at` date DEFAULT NULL,
  `status` enum('draft','reconciling','confirmed','settled','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `owner` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `owner_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `settled_at` datetime DEFAULT NULL,
  `attachment_ids_json` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `record_created_at` datetime DEFAULT NULL,
  `record_updated_at` datetime DEFAULT NULL,
  `created_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_business_purchase_settlement_legacy_id` (`legacy_id`),
  UNIQUE KEY `uk_business_purchase_settlement_settlement_no` (`settlement_no`),
  KEY `idx_business_purchase_settlement_purchase_order_id` (`purchase_order_id`),
  KEY `idx_business_purchase_settlement_payment_plan_id` (`payment_plan_id`),
  KEY `idx_business_purchase_settlement_supplier_id` (`supplier_id`),
  KEY `idx_business_purchase_settlement_status` (`status`),
  KEY `idx_business_purchase_settlement_invoice_status` (`invoice_status`),
  KEY `idx_business_purchase_settlement_owner_admin_id` (`owner_admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='采购结算';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_business_purchase_settlement` WRITE;
/*!40000 ALTER TABLE `fa_business_purchase_settlement` DISABLE KEYS */;
INSERT INTO `fa_business_purchase_settlement` VALUES (1,'purchase_settlement_1001','PS-20260321-001','云资源季度续费结算','purchase_order_1001',1,'云资源季度续费','payment_plan_1001',1,'云主机与 CDN 季度付款','supplier_1001',1,'阿尔法云服务','customer_1001',1,'星环科技','contract_1001',1,'企业官网重构与年度运维服务',12000.00,0.00,6000.00,12000.00,'partial','FP-20260321-001','2026-03-21','reconciling','李娜',2,NULL,'[]','示例采购结算，用于展示采购到付款再到结算的完整链路','2026-03-21 22:44:22','2026-03-21 22:44:22','user-1002',2,'李娜','user-1002',2,'李娜',1774104262,1774104262);
/*!40000 ALTER TABLE `fa_business_purchase_settlement` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_business_receivable_plan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_business_receivable_plan` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `contract_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `contract_id` int(10) unsigned NOT NULL DEFAULT '0',
  `contract_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customer_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customer_id` int(10) unsigned NOT NULL DEFAULT '0',
  `customer_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `due_date` date DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` enum('pending','processing','received','overdue','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `owner` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `owner_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `actual_received_at` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `record_created_at` datetime DEFAULT NULL,
  `record_updated_at` datetime DEFAULT NULL,
  `created_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_business_receivable_plan_legacy_id` (`legacy_id`),
  KEY `idx_business_receivable_plan_contract_id` (`contract_id`),
  KEY `idx_business_receivable_plan_customer_id` (`customer_id`),
  KEY `idx_business_receivable_plan_owner_admin_id` (`owner_admin_id`),
  KEY `idx_business_receivable_plan_due_date` (`due_date`),
  KEY `idx_business_receivable_plan_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='回款计划';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_business_receivable_plan` WRITE;
/*!40000 ALTER TABLE `fa_business_receivable_plan` DISABLE KEYS */;
INSERT INTO `fa_business_receivable_plan` VALUES (1,'plan_1001','contract_1001',1,'企业官网重构与年度运维服务','customer_1001',1,'星环科技','首款','2026-01-15',90000.00,'received','李娜',2,'2026-01-18 11:00:00','已完成到账并开票','2026-03-21 03:39:11','2026-03-21 03:39:11','user-1004',4,'顾宁','user-1004',4,'顾宁',1774035551,1774035551),(2,'plan_1002','contract_1001',1,'企业官网重构与年度运维服务','customer_1001',1,'星环科技','验收尾款','2026-04-15',90000.00,'pending','李娜',2,NULL,'待项目验收完成后回款','2026-03-21 03:39:11','2026-03-21 03:39:11','user-1004',4,'顾宁','user-1004',4,'顾宁',1774035551,1774035551),(3,'plan_1003','contract_1002',2,'客服工单 SaaS 年度订阅','customer_1002',2,'北辰数据','首期订阅款','2026-02-10',32000.00,'received','李娜',2,'2026-02-12 09:30:00','已完成到账','2026-03-21 03:39:11','2026-03-21 03:39:11','user-1004',4,'顾宁','user-1004',4,'顾宁',1774035551,1774035551),(4,'plan_1004','contract_1002',2,'客服工单 SaaS 年度订阅','customer_1002',2,'北辰数据','续费尾款','2026-05-10',64000.00,'processing','李娜',2,NULL,'财务已发送付款提醒，等待客户流程审批','2026-03-21 03:39:11','2026-03-21 03:39:11','user-1004',4,'顾宁','user-1004',4,'顾宁',1774035551,1774035551),(5,'plan_1005','contract_1003',3,'增长投放与数据分析服务','customer_1003',3,'海右医药','启动款','2026-03-25',50000.00,'pending','顾宁',4,NULL,'待法务审签完成后发起付款申请','2026-03-21 03:39:11','2026-03-21 03:39:11','user-1004',4,'顾宁','user-1004',4,'顾宁',1774035551,1774035551);
/*!40000 ALTER TABLE `fa_business_receivable_plan` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_business_supplier`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_business_supplier` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `supplier_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `short_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `category` enum('software','cloud','service','marketing','outsourcing','hardware','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'service',
  `level` enum('strategic','core','normal','backup') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `status` enum('active','paused','blacklist') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `settlement_cycle` enum('advance','monthly','quarterly','on_delivery','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'monthly',
  `owner` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `owner_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `contact_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `contact_phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `contact_email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `city` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `bank_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `bank_account` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tax_no` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `record_created_at` datetime DEFAULT NULL,
  `record_updated_at` datetime DEFAULT NULL,
  `created_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_business_supplier_legacy_id` (`legacy_id`),
  KEY `idx_business_supplier_status` (`status`),
  KEY `idx_business_supplier_owner_admin_id` (`owner_admin_id`),
  KEY `idx_business_supplier_supplier_name` (`supplier_name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='供应商档案';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_business_supplier` WRITE;
/*!40000 ALTER TABLE `fa_business_supplier` DISABLE KEYS */;
INSERT INTO `fa_business_supplier` VALUES (1,'supplier_1001','阿尔法云服务','阿尔法云','cloud','core','active','monthly','李娜',2,'徐莉','13600001111','service@alpha-cloud.example','上海','招商银行上海分行','6225888888881001','91310000ALPHA1001','云主机、CDN 和对象存储的核心供应商','2026-03-21 14:08:31','2026-03-21 14:08:31','user-1004',4,'顾宁','user-1004',4,'顾宁',1774073311,1774073311),(2,'supplier_1002','青穹渠道伙伴','青穹渠道','marketing','core','active','quarterly','顾宁',4,'顾扬','13600002222','partner@qingqiong.example','杭州','杭州银行高新支行','6225888888881002','91330000QINGQIONG1002','投放返佣和渠道合作费用统一走这家供应商','2026-03-21 14:08:31','2026-03-21 14:08:31','user-1004',4,'顾宁','user-1004',4,'顾宁',1774073311,1774073311),(3,'supplier_1003','云杉数字服务','云杉服务','service','normal','active','on_delivery','顾宁',4,'许越','13600003333','ops@yunsan.example','南京','中国银行南京软件谷支行','6225888888881003','91320000YUNSAN1003','设计外包、数据清洗和运营支持服务供应商','2026-03-21 14:08:31','2026-03-21 14:08:31','user-1004',4,'顾宁','user-1004',4,'顾宁',1774073311,1774073311),(5,'supplier_20260321141226_e668ce37','测试供应商1774073546','测试供方','service','normal','active','monthly','张敏',3,'测试联系人','13912345678','vendor@example.com','上海','测试银行','6222000000000000','TESTTAX001','smoke','2026-03-21 14:12:26','2026-03-21 14:12:26','user-1001',1,'陈总','user-1001',1,'陈总',1774073546,1774073546),(6,'supplier_20260321141321_d6bf4e5e','smoke-supplier-1774073601','smoke','service','normal','active','monthly','张敏',3,'Smoke','13912345678','vendor@example.com','Shanghai','Smoke Bank','6222000000000000','TESTTAX001','smoke','2026-03-21 14:13:21','2026-03-21 14:13:21','user-1001',1,'陈总','user-1001',1,'陈总',1774073601,1774073601);
/*!40000 ALTER TABLE `fa_business_supplier` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父ID',
  `type` varchar(30) DEFAULT '' COMMENT '栏目类型',
  `name` varchar(30) DEFAULT '',
  `nickname` varchar(50) DEFAULT '',
  `flag` set('hot','index','recommend') DEFAULT '',
  `image` varchar(100) DEFAULT '' COMMENT '图片',
  `keywords` varchar(255) DEFAULT '' COMMENT '关键字',
  `description` varchar(255) DEFAULT '' COMMENT '描述',
  `diyname` varchar(30) DEFAULT '' COMMENT '自定义名称',
  `createtime` bigint(16) DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) DEFAULT NULL COMMENT '更新时间',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重',
  `status` varchar(30) DEFAULT '' COMMENT '状态',
  PRIMARY KEY (`id`),
  KEY `weigh` (`weigh`,`id`),
  KEY `pid` (`pid`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COMMENT='分类表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_category` WRITE;
/*!40000 ALTER TABLE `fa_category` DISABLE KEYS */;
INSERT INTO `fa_category` VALUES (1,0,'page','官方新闻','news','recommend','/assets/img/qrcode.png','','','news',1491635035,1491635035,1,'normal'),(2,0,'page','移动应用','mobileapp','hot','/assets/img/qrcode.png','','','mobileapp',1491635035,1491635035,2,'normal'),(3,2,'page','微信公众号','wechatpublic','index','/assets/img/qrcode.png','','','wechatpublic',1491635035,1491635035,3,'normal'),(4,2,'page','Android开发','android','recommend','/assets/img/qrcode.png','','','android',1491635035,1491635035,4,'normal'),(5,0,'page','软件产品','software','recommend','/assets/img/qrcode.png','','','software',1491635035,1491635035,5,'normal'),(6,5,'page','网站建站','website','recommend','/assets/img/qrcode.png','','','website',1491635035,1491635035,6,'normal'),(7,5,'page','企业管理软件','company','index','/assets/img/qrcode.png','','','company',1491635035,1491635035,7,'normal'),(8,6,'page','PC端','website-pc','recommend','/assets/img/qrcode.png','','','website-pc',1491635035,1491635035,8,'normal'),(9,6,'page','移动端','website-mobile','recommend','/assets/img/qrcode.png','','','website-mobile',1491635035,1491635035,9,'normal'),(10,7,'page','CRM系统 ','company-crm','recommend','/assets/img/qrcode.png','','','company-crm',1491635035,1491635035,10,'normal'),(11,7,'page','SASS平台软件','company-sass','recommend','/assets/img/qrcode.png','','','company-sass',1491635035,1491635035,11,'normal'),(12,0,'test','测试1','test1','recommend','/assets/img/qrcode.png','','','test1',1491635035,1491635035,12,'normal'),(13,0,'test','测试2','test2','recommend','/assets/img/qrcode.png','','','test2',1491635035,1491635035,13,'normal');
/*!40000 ALTER TABLE `fa_category` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) DEFAULT '' COMMENT '变量名',
  `group` varchar(30) DEFAULT '' COMMENT '分组',
  `title` varchar(100) DEFAULT '' COMMENT '变量标题',
  `tip` varchar(100) DEFAULT '' COMMENT '变量描述',
  `type` varchar(30) DEFAULT '' COMMENT '类型:string,text,int,bool,array,datetime,date,file',
  `visible` varchar(255) DEFAULT '' COMMENT '可见条件',
  `value` text COMMENT '变量值',
  `content` text COMMENT '变量字典数据',
  `rule` varchar(100) DEFAULT '' COMMENT '验证规则',
  `extend` varchar(255) DEFAULT '' COMMENT '扩展属性',
  `setting` varchar(255) DEFAULT '' COMMENT '配置',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COMMENT='系统配置';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_config` WRITE;
/*!40000 ALTER TABLE `fa_config` DISABLE KEYS */;
INSERT INTO `fa_config` VALUES (1,'name','basic','系统名称','登录页、后台标题和打印页使用的系统名称','string','','ERP AI 管理系统','','required','',''),(2,'beian','basic','备案号','如不需要展示可留空','string','','','','','',''),(3,'cdnurl','basic','Cdn url','如果全站静态资源使用第三方云储存请配置该值','string','','','','','',''),(4,'version','basic','Version','如果静态资源有变动请重新配置该值','string','','1.0.1','','required','',''),(5,'timezone','basic','Timezone','','string','','Asia/Shanghai','','required','',''),(6,'forbiddenip','basic','Forbidden ip','一行一条记录','text','','','','','',''),(7,'languages','basic','Languages','','array','','{\"backend\":\"zh-cn\",\"frontend\":\"zh-cn\"}','','required','',''),(8,'fixedpage','basic','Fixed page','请输入左侧菜单栏存在的链接','string','','dashboard','','required','',''),(9,'categorytype','dictionary','Category type','','array','','{\"default\":\"Default\",\"page\":\"Page\",\"article\":\"Article\",\"test\":\"Test\"}','','','',''),(10,'configgroup','dictionary','Config group','','array','','{\"basic\":\"Basic\",\"email\":\"Email\",\"dictionary\":\"Dictionary\",\"user\":\"User\",\"example\":\"Example\"}','','','',''),(11,'mail_type','email','Mail type','选择邮件发送方式','select','','1','[\"请选择\",\"SMTP\"]','','',''),(12,'mail_smtp_host','email','Mail smtp host','错误的配置发送邮件会导致服务器超时','string','','smtp.qq.com','','','',''),(13,'mail_smtp_port','email','Mail smtp port','(不加密默认25,SSL默认465,TLS默认587)','string','','465','','','',''),(14,'mail_smtp_user','email','Mail smtp user','（填写完整用户名）','string','','','','','',''),(15,'mail_smtp_pass','email','Mail smtp password','（填写您的密码或授权码）','password','','','','','',''),(16,'mail_verify_type','email','Mail vertify type','（SMTP验证方式[推荐SSL]）','select','','2','[\"无\",\"TLS\",\"SSL\"]','','',''),(17,'mail_from','email','Mail from','','string','','','','','',''),(18,'attachmentcategory','dictionary','Attachment category','','array','','{\"category1\":\"Category1\",\"category2\":\"Category2\",\"custom\":\"Custom\"}','','','',''),(20,'login_subtitle','basic','登录页副标题','显示在登录页系统名称下方，可留空','string','','综合型中小企业业务管理系统','','','',''),(21,'admin_logo_mini','basic','后台折叠 Logo','左侧菜单收起时显示，建议 2-4 个字','string','','ERP','','','',''),(22,'admin_logo_text','basic','后台完整 Logo','左上角完整系统名称','string','','ERP AI 管理系统','','','',''),(23,'site_home_url','basic','官网地址','顶部官网入口；留空则不显示','string','','','','','',''),(24,'site_home_label','basic','官网入口名称','顶部官网入口文字','string','','官网','','','',''),(25,'copyright','basic','版权说明','登录页、打印页和页脚展示；留空则不显示','string','','','','','',''),(28,'agreement','basic','协议说明','如不需要展示可留空','string','','','','','','');
/*!40000 ALTER TABLE `fa_config` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_ems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_ems` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `event` varchar(30) DEFAULT '' COMMENT '事件',
  `email` varchar(100) DEFAULT '' COMMENT '邮箱',
  `code` varchar(10) DEFAULT '' COMMENT '验证码',
  `times` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '验证次数',
  `ip` varchar(30) DEFAULT '' COMMENT 'IP',
  `createtime` bigint(16) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邮箱验证码表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_ems` WRITE;
/*!40000 ALTER TABLE `fa_ems` DISABLE KEYS */;
/*!40000 ALTER TABLE `fa_ems` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_erp_module`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_erp_module` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` varchar(64) NOT NULL DEFAULT '',
  `module_key` varchar(50) NOT NULL DEFAULT '',
  `module_name` varchar(100) NOT NULL DEFAULT '',
  `module_type` varchar(20) NOT NULL DEFAULT 'plugin',
  `icon` varchar(50) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `entry_route` varchar(100) NOT NULL DEFAULT '',
  `is_enabled` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `is_locked` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `sort_no` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created_by_name` varchar(50) NOT NULL DEFAULT '',
  `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_by_name` varchar(50) NOT NULL DEFAULT '',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_module_key` (`module_key`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_erp_module` WRITE;
/*!40000 ALTER TABLE `fa_erp_module` DISABLE KEYS */;
INSERT INTO `fa_erp_module` VALUES (1,'module_app','app','APP 运营插件','plugin','fa fa-mobile','适合需要跟进 APP 生命周期、问题、研发联动和版本发布的团队。','app/workbench/index',1,0,80,'2026-03-26 08:19:18',1,'系统管理员',1,'系统管理员',1774483389,1774484358);
/*!40000 ALTER TABLE `fa_erp_module` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_erp_update_migration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_erp_update_migration` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration_id` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `checksum` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `status` enum('applying','applied','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'applying',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `source_ref` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `batch_no` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `started_at` datetime DEFAULT NULL,
  `applied_at` datetime DEFAULT NULL,
  `execution_ms` int(10) unsigned NOT NULL DEFAULT '0',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_migration_id` (`migration_id`),
  KEY `idx_status_applied_at` (`status`,`applied_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ERP 在线升级数据库迁移记录';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_erp_update_migration` WRITE;
/*!40000 ALTER TABLE `fa_erp_update_migration` DISABLE KEYS */;
/*!40000 ALTER TABLE `fa_erp_update_migration` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_finance_invoice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_finance_invoice` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '旧系统ID',
  `kind` enum('receivable','payable') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'receivable' COMMENT '单据类型:receivable=应收,payable=应付',
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '单据标题',
  `counterparty` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '往来对象',
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '金额',
  `due_date` date DEFAULT NULL COMMENT '到期日期',
  `status` enum('pending','partial','paid','overdue','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT '单据状态:pending=待处理,partial=部分完成,paid=已完成,overdue=已逾期,cancelled=已作废',
  `project_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '项目旧ID',
  `project_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联项目',
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT '备注',
  `attachment_ids_json` text COLLATE utf8mb4_unicode_ci COMMENT '附件ID列表JSON',
  `record_created_at` datetime DEFAULT NULL COMMENT '业务创建时间',
  `record_updated_at` datetime DEFAULT NULL COMMENT '业务更新时间',
  `created_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '创建人旧ID',
  `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建人后台账号',
  `created_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '创建人',
  `updated_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '更新人旧ID',
  `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新人后台账号',
  `updated_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '更新人',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_finance_invoice_legacy_id` (`legacy_id`),
  KEY `idx_finance_invoice_project_id` (`project_id`),
  KEY `idx_finance_invoice_kind` (`kind`),
  KEY `idx_finance_invoice_due_date` (`due_date`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='应收应付';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_finance_invoice` WRITE;
/*!40000 ALTER TABLE `fa_finance_invoice` DISABLE KEYS */;
INSERT INTO `fa_finance_invoice` VALUES (1,'inv-1001','receivable','企业官网重构一期尾款','星环科技',50000.00,'2026-03-25','pending','prj-1001',1,'待客户验收后支付','[]',NULL,NULL,'',0,'','',0,'',1774032311,1774032311),(2,'inv-1002','payable','第三方测试服务费','曜石测试',8000.00,'2026-03-22','paid','prj-1002',2,'回归测试尾款','[]',NULL,NULL,'',0,'','',0,'',1774032311,1774032311),(3,'inv-1003','receivable','财务 BI 看板续约','教培 SaaS 客户',20000.00,'2026-04-01','partial','prj-1003',3,'已收定金，剩余款待续签','[]',NULL,NULL,'',0,'','',0,'',1774032311,1774032311);
/*!40000 ALTER TABLE `fa_finance_invoice` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_finance_transaction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_finance_transaction` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '旧系统ID',
  `transaction_date` date DEFAULT NULL COMMENT '记账日期',
  `type` enum('income','expense') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'expense' COMMENT '收支类型:income=收入,expense=支出',
  `category` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '科目分类',
  `counterparty` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '往来对象',
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '金额',
  `payment_method` enum('bank','wechat','alipay','cash','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bank' COMMENT '支付方式:bank=银行,wechat=微信,alipay=支付宝,cash=现金,other=其他',
  `project_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '项目旧ID',
  `project_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联项目',
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT '备注',
  `attachment_ids_json` text COLLATE utf8mb4_unicode_ci COMMENT '附件ID列表JSON',
  `record_created_at` datetime DEFAULT NULL COMMENT '业务创建时间',
  `record_updated_at` datetime DEFAULT NULL COMMENT '业务更新时间',
  `created_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '创建人旧ID',
  `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建人后台账号',
  `created_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '创建人',
  `updated_by_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '更新人旧ID',
  `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新人后台账号',
  `updated_by_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '更新人',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_finance_transaction_legacy_id` (`legacy_id`),
  KEY `idx_finance_transaction_project_id` (`project_id`),
  KEY `idx_finance_transaction_type` (`type`),
  KEY `idx_finance_transaction_transaction_date` (`transaction_date`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='财务流水';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_finance_transaction` WRITE;
/*!40000 ALTER TABLE `fa_finance_transaction` DISABLE KEYS */;
INSERT INTO `fa_finance_transaction` VALUES (1,'tx-1001','2026-01-08','income','项目预付款','星环科技',80000.00,'bank','prj-1001',1,'企业官网重构项目首付款到账','[]',NULL,NULL,'',0,'','',0,'',1774032311,1774032311),(2,'tx-1002','2026-01-12','expense','云资源','阿里云',6800.00,'bank','prj-1001',1,'预发环境与对象存储费用','[]',NULL,NULL,'',0,'','',0,'',1774032311,1774032311),(3,'tx-1003','2026-02-03','expense','工资发放','研发团队',48000.00,'bank','',0,'2 月上半月工资','[]',NULL,NULL,'',0,'','',0,'',1774032311,1774032311),(4,'tx-1004','2026-02-10','income','订阅收入','教培 SaaS 客户',16800.00,'bank','prj-1003',3,'标准版年费收款','[]',NULL,NULL,'',0,'','',0,'',1774032311,1774032311),(5,'tx-1005','2026-02-15','expense','市场投放','巨量引擎',9000.00,'bank','',0,'春季获客投放','[]',NULL,NULL,'',0,'','',0,'',1774032311,1774032311),(6,'tx-1006','2026-03-05','income','项目回款','北辰数据',60000.00,'bank','prj-1002',2,'客服工单 SaaS 一期回款','[]',NULL,NULL,'',0,'','',0,'',1774032311,1774032311),(7,'tx-1007','2026-03-08','expense','外包测试','曜石测试',12000.00,'bank','prj-1002',2,'兼容性与回归测试','[]',NULL,NULL,'',0,'','',0,'',1774032311,1774032311),(8,'tx-1008','2026-03-10','expense','办公支出','办公用品',3200.00,'wechat','',0,'工位与会议室物料补充','[]',NULL,NULL,'',0,'','',0,'',1774032311,1774032311),(9,'tx-20260319040639-4640','2026-03-19','expense','办公支出','晨光办公',100.00,'wechat','prj-1001',1,'今天给晨光办公付款100元，微信支付，买办公用品。','[]',NULL,NULL,'',0,'','',0,'',1774032311,1774032311),(10,'tx-20260320044359-6146','2026-03-19','expense','其他支出','傻逼骚猪',100.00,'other','prj-1003',3,'我给傻逼骚猪付了100块钱昨天','[]','2026-03-20 04:43:59','2026-03-20 04:43:59','user-1001',1,'陈总','user-1001',1,'陈总',1774032311,1774032311),(14,'finance_tx_20260321153922_d97c7ef2','2026-03-21','expense','顾问服务','前端技术',1000.00,'other','prj-1001',1,'【智能记账 / 大模型解析 / 建议复核】\n给前端技术1000块钱今天','','2026-03-21 15:39:22','2026-03-21 15:39:22','user-1001',1,'陈总','user-1001',1,'陈总',1774078762,1774078762);
/*!40000 ALTER TABLE `fa_finance_transaction` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_project`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_project` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '旧系统ID',
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '项目名称',
  `client` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '客户名称',
  `owner` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '项目负责人',
  `owner_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '负责人后台账号',
  `status` enum('planning','active','delivery','completed','paused','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'planning' COMMENT '项目状态:planning=规划中,active=执行中,delivery=交付中,completed=已完成,paused=暂停,closed=已关闭',
  `priority` enum('low','medium','high','urgent') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium' COMMENT '优先级:low=低,medium=中,high=高,urgent=紧急',
  `budget` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '预算金额',
  `start_date` date DEFAULT NULL COMMENT '开始日期',
  `due_date` date DEFAULT NULL COMMENT '截止日期',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '项目说明',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_project_legacy_id` (`legacy_id`),
  KEY `idx_project_owner_admin_id` (`owner_admin_id`),
  KEY `idx_project_status` (`status`),
  KEY `idx_project_due_date` (`due_date`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='项目台账';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_project` WRITE;
/*!40000 ALTER TABLE `fa_project` DISABLE KEYS */;
INSERT INTO `fa_project` VALUES (1,'prj-1001','企业官网重构','星环科技','林晨',0,'delivery','high',120000.00,'2026-01-05','2026-03-28','围绕品牌官网、招聘页和表单线索转化做重构升级。',1774032311,1774032311),(2,'prj-1002','客服工单 SaaS','北辰数据','张敏',3,'active','high',180000.00,'2026-02-18','2026-04-30','覆盖工单流转、知识库和客服绩效看板。',1774032311,1774032311),(3,'prj-1003','财务 BI 看板','内部产品线','王越',5,'planning','medium',90000.00,'2026-03-03','2026-05-20','沉淀收入、成本、回款和经营分析的可视化面板。',1774032311,1774032311);
/*!40000 ALTER TABLE `fa_project` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_project_task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_project_task` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '旧系统ID',
  `project_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '项目旧ID',
  `project_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '所属项目',
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '任务名称',
  `assignee` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '执行人',
  `assignee_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '执行人后台账号',
  `status` enum('todo','doing','review','done','blocked','overdue') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'todo' COMMENT '任务状态:todo=待处理,doing=进行中,review=待验收,done=已完成,blocked=阻塞,overdue=已逾期',
  `priority` enum('low','medium','high','urgent') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium' COMMENT '优先级:low=低,medium=中,high=高,urgent=紧急',
  `due_date` date DEFAULT NULL COMMENT '截止日期',
  `estimate_hours` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT '预估工时',
  `actual_hours` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT '实际工时',
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT '备注',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_project_task_legacy_id` (`legacy_id`),
  KEY `idx_project_task_project_id` (`project_id`),
  KEY `idx_project_task_assignee_admin_id` (`assignee_admin_id`),
  KEY `idx_project_task_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='项目任务';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_project_task` WRITE;
/*!40000 ALTER TABLE `fa_project_task` DISABLE KEYS */;
INSERT INTO `fa_project_task` VALUES (1,'task-1001','prj-1001',1,'交互稿确认','赵婷',0,'done','high','2026-02-05',18.00,20.00,'',1774032311,1774032311),(2,'task-1002','prj-1001',1,'前端联调','李俊',0,'doing','high','2026-03-20',30.00,22.00,'',1774032311,1774032311),(3,'task-1003','prj-1002',2,'需求梳理','张敏',3,'done','high','2026-02-25',12.00,10.00,'',1774032311,1774032311),(4,'task-1004','prj-1002',2,'权限模型设计','周岚',0,'todo','medium','2026-03-26',16.00,0.00,'',1774032311,1774032311),(5,'task-1005','prj-1003',3,'科目体系定义','王越',5,'doing','medium','2026-03-29',14.00,6.00,'',1774032311,1774032311),(6,'task-1006','prj-1003',3,'首版报表页面','刘珊',0,'todo','medium','2026-04-08',24.00,0.00,'',1774032311,1774032311);
/*!40000 ALTER TABLE `fa_project_task` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_sms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_sms` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `event` varchar(30) DEFAULT '' COMMENT '事件',
  `mobile` varchar(20) DEFAULT '' COMMENT '手机号',
  `code` varchar(10) DEFAULT '' COMMENT '验证码',
  `times` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '验证次数',
  `ip` varchar(30) DEFAULT '' COMMENT 'IP',
  `createtime` bigint(16) unsigned DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='短信验证码表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_sms` WRITE;
/*!40000 ALTER TABLE `fa_sms` DISABLE KEYS */;
/*!40000 ALTER TABLE `fa_sms` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_staff_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_staff_audit` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '旧系统ID',
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联后台账号',
  `actor_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '操作人后台账号',
  `actor_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '操作人',
  `module` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '所属模块',
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '动作',
  `object_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '对象类型',
  `object_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '对象旧ID',
  `content` text COLLATE utf8mb4_unicode_ci COMMENT '日志内容',
  `ip` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'IP',
  `useragent` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'UserAgent',
  `happened_at` datetime DEFAULT NULL COMMENT '发生时间',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_staff_audit_legacy_id` (`legacy_id`),
  KEY `idx_staff_audit_module` (`module`),
  KEY `idx_staff_audit_actor_admin_id` (`actor_admin_id`),
  KEY `idx_staff_audit_happened_at` (`happened_at`)
) ENGINE=InnoDB AUTO_INCREMENT=655 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='人员操作留痕';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_staff_audit` WRITE;
/*!40000 ALTER TABLE `fa_staff_audit` DISABLE KEYS */;
INSERT INTO `fa_staff_audit` VALUES (1,'log-20260319141044-2926',0,0,'','operations','create','','','','','',NULL,1774032311,1774032311),(2,'log-20260319141044-9252',0,0,'','staff','create','','','','','',NULL,1774032311,1774032311),(3,'log-20260319141044-7982',0,0,'','operations','delete','','','','','',NULL,1774032311,1774032311),(4,'log-20260319141044-8622',0,0,'','staff','delete','','','','','',NULL,1774032311,1774032311),(5,'log-20260319141831-9392',0,0,'','staff','switch_user','','','','','',NULL,1774032311,1774032311),(6,'log-20260319145047-7775',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(7,'log-20260319145049-9083',0,0,'','tech','create','','','','','',NULL,1774032311,1774032311),(8,'log-20260319145050-9197',0,0,'','tech','delete','','','','','',NULL,1774032311,1774032311),(9,'log-20260319145052-5720',0,0,'','auth','logout','','','','','',NULL,1774032311,1774032311),(10,'log-20260319145116-7747',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(11,'log-20260319145118-4366',0,0,'','tech','create','','','','','',NULL,1774032311,1774032311),(12,'log-20260319145119-1495',0,0,'','tech','delete','','','','','',NULL,1774032311,1774032311),(13,'log-20260319145121-2439',0,0,'','auth','logout','','','','','',NULL,1774032311,1774032311),(14,'log-20260319145140-7393',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(15,'log-20260319145142-8715',0,0,'','auth','logout','','','','','',NULL,1774032311,1774032311),(16,'log-20260319145200-1892',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(17,'log-20260319145202-2987',0,0,'','tech','create','','','','','',NULL,1774032311,1774032311),(18,'log-20260319145203-9324',0,0,'','tech','delete','','','','','',NULL,1774032311,1774032311),(19,'log-20260319145205-3056',0,0,'','auth','logout','','','','','',NULL,1774032311,1774032311),(20,'log-20260319145700-2253',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(21,'log-20260319145706-1745',0,0,'','staff','switch_user','','','','','',NULL,1774032311,1774032311),(22,'log-20260319145710-2011',0,0,'','staff','switch_user','','','','','',NULL,1774032311,1774032311),(23,'log-20260319150345-7313',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(24,'log-20260319150347-2760',0,0,'','auth','logout','','','','','',NULL,1774032311,1774032311),(25,'log-20260319153639-9624',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(26,'log-20260319153641-4494',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(27,'log-20260319153643-9418',0,0,'','service','create','','','','','',NULL,1774032311,1774032311),(28,'log-20260319153645-8923',0,0,'','service','delete','','','','','',NULL,1774032311,1774032311),(29,'log-20260319153701-8633',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(30,'log-20260319153703-7311',0,0,'','service','create','','','','','',NULL,1774032311,1774032311),(31,'log-20260319153705-7694',0,0,'','service','delete','','','','','',NULL,1774032311,1774032311),(32,'log-20260319153718-9513',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(33,'log-20260319153720-2972',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(34,'log-20260319153846-5889',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(35,'log-20260319192329-8852',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(36,'log-20260319192353-9411',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(37,'log-20260319192357-1674',0,0,'','service','delete','','','','','',NULL,1774032311,1774032311),(38,'log-20260319192413-9703',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(39,'log-20260319192605-8055',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(40,'log-20260319192627-9053',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(41,'log-20260319192630-7810',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(42,'log-20260319213136-1726',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(43,'log-20260319213139-9680',0,0,'','operations','create','','','','','',NULL,1774032311,1774032311),(44,'log-20260319213141-3596',0,0,'','service','create','','','','','',NULL,1774032311,1774032311),(45,'log-20260319213143-9286',0,0,'','service','delete','','','','','',NULL,1774032311,1774032311),(46,'log-20260319213145-3646',0,0,'','operations','delete','','','','','',NULL,1774032311,1774032311),(47,'log-20260319221855-7194',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(48,'log-20260319221857-3890',0,0,'','service','create','','','','','',NULL,1774032311,1774032311),(49,'log-20260319221911-1399',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(50,'log-20260319221913-1465',0,0,'','service','create','','','','','',NULL,1774032311,1774032311),(51,'log-20260319221928-7836',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(52,'log-20260319221930-8119',0,0,'','service','create','','','','','',NULL,1774032311,1774032311),(53,'log-20260319222006-5115',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(54,'log-20260319222008-2279',0,0,'','service','delete','','','','','',NULL,1774032311,1774032311),(55,'log-20260319222010-8251',0,0,'','service','delete','','','','','',NULL,1774032311,1774032311),(56,'log-20260319222012-2260',0,0,'','service','delete','','','','','',NULL,1774032311,1774032311),(57,'log-20260319222015-9291',0,0,'','service','create','','','','','',NULL,1774032311,1774032311),(58,'log-20260319222017-2323',0,0,'','service','update','','','','','',NULL,1774032311,1774032311),(59,'log-20260319222233-5223',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(60,'log-20260319222235-7572',0,0,'','service','delete','','','','','',NULL,1774032311,1774032311),(61,'log-20260319222237-2167',0,0,'','service','create','','','','','',NULL,1774032311,1774032311),(62,'log-20260319222239-2031',0,0,'','service','update','','','','','',NULL,1774032311,1774032311),(63,'log-20260319222314-7352',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(64,'log-20260319222316-6910',0,0,'','service','delete','','','','','',NULL,1774032311,1774032311),(65,'log-20260319222318-2468',0,0,'','service','create','','','','','',NULL,1774032311,1774032311),(66,'log-20260319222320-1621',0,0,'','service','update','','','','','',NULL,1774032311,1774032311),(67,'log-20260319222427-9156',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(68,'log-20260319222429-2184',0,0,'','service','delete','','','','','',NULL,1774032311,1774032311),(69,'log-20260319222431-5325',0,0,'','service','create','','','','','',NULL,1774032311,1774032311),(70,'log-20260319222433-4835',0,0,'','service','update','','','','','',NULL,1774032311,1774032311),(71,'log-20260319222436-3766',0,0,'','operations','create','','','','','',NULL,1774032311,1774032311),(72,'log-20260319222438-4201',0,0,'','operations','delete','','','','','',NULL,1774032311,1774032311),(73,'log-20260319222440-7240',0,0,'','service','delete','','','','','',NULL,1774032311,1774032311),(74,'log-20260319222442-3127',0,0,'','auth','logout','','','','','',NULL,1774032311,1774032311),(75,'log-20260319222457-8815',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(76,'log-20260319223822-5006',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(77,'log-20260319223824-2937',0,0,'','service','create','','','','','',NULL,1774032311,1774032311),(78,'log-20260319223826-7753',0,0,'','service','update','','','','','',NULL,1774032311,1774032311),(79,'log-20260319223829-2686',0,0,'','operations','create','','','','','',NULL,1774032311,1774032311),(80,'log-20260319223831-3778',0,0,'','operations','delete','','','','','',NULL,1774032311,1774032311),(81,'log-20260319223833-6921',0,0,'','service','delete','','','','','',NULL,1774032311,1774032311),(82,'log-20260319223835-4297',0,0,'','auth','logout','','','','','',NULL,1774032311,1774032311),(83,'log-20260319223846-7184',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(84,'log-20260320022343-8988',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(85,'log-20260320022345-7469',0,0,'','service','create','','','','','',NULL,1774032311,1774032311),(86,'log-20260320022347-2582',0,0,'','service','update','','','','','',NULL,1774032311,1774032311),(87,'log-20260320022349-8023',0,0,'','operations','create','','','','','',NULL,1774032311,1774032311),(88,'log-20260320022351-2905',0,0,'','operations','delete','','','','','',NULL,1774032311,1774032311),(89,'log-20260320022353-8145',0,0,'','service','delete','','','','','',NULL,1774032311,1774032311),(90,'log-20260320022356-8142',0,0,'','auth','logout','','','','','',NULL,1774032311,1774032311),(91,'log-20260320022407-5088',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(92,'log-20260320022409-6604',0,0,'','auth','logout','','','','','',NULL,1774032311,1774032311),(93,'log-20260320023339-1628',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(94,'log-20260320023341-7897',0,0,'','service','create','','','','','',NULL,1774032311,1774032311),(95,'log-20260320023343-6651',0,0,'','service','update','','','','','',NULL,1774032311,1774032311),(96,'log-20260320023345-8742',0,0,'','operations','create','','','','','',NULL,1774032311,1774032311),(97,'log-20260320023347-3393',0,0,'','operations','create','','','','','',NULL,1774032311,1774032311),(98,'log-20260320023350-8177',0,0,'','operations','delete','','','','','',NULL,1774032311,1774032311),(99,'log-20260320023352-6291',0,0,'','operations','delete','','','','','',NULL,1774032311,1774032311),(100,'log-20260320023354-4773',0,0,'','service','delete','','','','','',NULL,1774032311,1774032311),(101,'log-20260320023356-7713',0,0,'','auth','logout','','','','','',NULL,1774032311,1774032311),(102,'log-20260320025233-1680',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(103,'log-20260320025236-5100',0,0,'','service','create','','','','','',NULL,1774032311,1774032311),(104,'log-20260320025238-2162',0,0,'','operations','create','','','','','',NULL,1774032311,1774032311),(105,'log-20260320025240-8574',0,0,'','operations','delete','','','','','',NULL,1774032311,1774032311),(106,'log-20260320025242-9993',0,0,'','service','delete','','','','','',NULL,1774032311,1774032311),(107,'log-20260320025244-9344',0,0,'','auth','logout','','','','','',NULL,1774032311,1774032311),(108,'log-20260320025320-3373',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(109,'log-20260320025322-2701',0,0,'','service','create','','','','','',NULL,1774032311,1774032311),(110,'log-20260320025324-5437',0,0,'','operations','create','','','','','',NULL,1774032311,1774032311),(111,'log-20260320025326-9728',0,0,'','operations','delete','','','','','',NULL,1774032311,1774032311),(112,'log-20260320025328-8375',0,0,'','service','delete','','','','','',NULL,1774032311,1774032311),(113,'log-20260320025330-1884',0,0,'','auth','logout','','','','','',NULL,1774032311,1774032311),(114,'log-20260320025525-1147',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(115,'log-20260320030315-8152',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(116,'log-20260320031059-9912',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(117,'log-20260320031258-7743',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(118,'log-20260320031548-5643',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(119,'log-20260320033519-5724',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(120,'log-20260320035532-8530',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(121,'log-20260320035611-7941',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(122,'log-20260320035635-2226',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(123,'log-20260320035653-7645',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(124,'log-20260320040920-9114',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(125,'log-20260320043855-1699',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(126,'log-20260320043916-7689',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(127,'log-20260320043951-7668',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(128,'log-20260320044054-1936',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(129,'log-20260320044359-4759',0,0,'','finance','create','','','','','',NULL,1774032311,1774032311),(130,'log-20260320044534-1355',0,0,'','auth','logout','','','','','',NULL,1774032311,1774032311),(131,'log-20260320044547-1974',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(132,'log-20260320044553-3257',0,0,'','auth','logout','','','','','',NULL,1774032311,1774032311),(133,'log-20260320044558-8513',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(134,'log-20260320044655-8612',0,0,'','operations','update','','','','','',NULL,1774032311,1774032311),(135,'log-20260320045414-2935',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(136,'log-20260320050020-7347',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(137,'log-20260320050114-4857',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(138,'log-20260320050146-1956',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(139,'log-20260320050208-2126',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(140,'log-20260320051034-8494',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(141,'log-20260320051052-1792',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(142,'log-20260320051115-3815',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(143,'log-20260320051121-9993',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(144,'log-20260320051143-3607',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(145,'log-20260320051210-4283',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(146,'log-20260320051216-6817',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(147,'log-20260320051311-2910',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(148,'log-20260320053138-7152',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(149,'log-20260320053140-3785',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(150,'log-20260320053143-6384',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(151,'log-20260320053203-8771',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(152,'log-20260320053205-2641',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(153,'log-20260320053301-9294',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(154,'log-20260320053428-1876',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(155,'log-20260320072031-2267',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(156,'log-20260320072858-1507',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(157,'log-20260320072955-1711',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(158,'log-20260320072957-5983',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(159,'log-20260320072959-3422',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(160,'log-20260320073213-8761',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(161,'log-20260320074220-8604',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(162,'log-20260320075155-6424',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(163,'log-20260320080152-6674',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(164,'log-20260320081219-4782',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(165,'log-20260320153940-6136',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(166,'log-20260320165223-1132',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(167,'log-20260320201736-9429',0,0,'','auth','login','','','','','',NULL,1774032311,1774032311),(168,'audit_20260321034137_fc7516d7',1,1,'陈总','business_customer','add','客户档案','customer_20260321034137_473cd09f','新增客户档案：测试客户-SMOKE034137','127.0.0.1','','2026-03-21 03:41:37',1774035697,1774035697),(169,'audit_20260321034137_bdbb6b4f',1,1,'陈总','business_contract','add','合同台账','contract_20260321034137_e0957b64','新增合同：T-034137 / 测试合同-SMOKE034137','127.0.0.1','','2026-03-21 03:41:37',1774035697,1774035697),(170,'audit_20260321034137_fe4f32ab',1,1,'陈总','business_receivable_plan','add','回款计划','plan_20260321034137_64082ebe','新增回款计划：测试回款-SMOKE034137','127.0.0.1','','2026-03-21 03:41:37',1774035697,1774035697),(171,'audit_20260321034138_f40b7942',1,1,'陈总','business_receivable_plan','delete','回款计划','plan_20260321034137_64082ebe','删除回款计划：测试回款-SMOKE034137','127.0.0.1','','2026-03-21 03:41:38',1774035698,1774035698),(172,'audit_20260321034138_99b286c7',1,1,'陈总','business_contract','delete','合同台账','contract_20260321034137_e0957b64','删除合同：T-034137 / 测试合同-SMOKE034137','127.0.0.1','','2026-03-21 03:41:38',1774035698,1774035698),(173,'audit_20260321034138_adb961a0',1,1,'陈总','business_customer','delete','客户档案','customer_20260321034137_473cd09f','删除客户档案：测试客户-SMOKE034137','127.0.0.1','','2026-03-21 03:41:38',1774035698,1774035698),(174,'audit_20260321034244_402cdc95',1,1,'陈总','business_customer','add','客户档案','customer_20260321034244_88d93177','新增客户档案：测试客户-SMOKE034244','127.0.0.1','Codex-Smoke','2026-03-21 03:42:44',1774035764,1774035764),(175,'audit_20260321034244_97117b46',1,1,'陈总','business_contract','add','合同台账','contract_20260321034244_66bf0e7f','新增合同：T-034244 / 测试合同-SMOKE034244','127.0.0.1','Codex-Smoke','2026-03-21 03:42:44',1774035764,1774035764),(176,'audit_20260321034244_0e94e784',1,1,'陈总','business_receivable_plan','add','回款计划','plan_20260321034244_974838b2','新增回款计划：测试回款-SMOKE034244','127.0.0.1','Codex-Smoke','2026-03-21 03:42:44',1774035764,1774035764),(177,'audit_20260321034244_9b1fd226',1,1,'陈总','business_receivable_plan','delete','回款计划','plan_20260321034244_974838b2','删除回款计划：测试回款-SMOKE034244','127.0.0.1','Codex-Smoke','2026-03-21 03:42:44',1774035764,1774035764),(178,'audit_20260321034244_6037d2be',1,1,'陈总','business_contract','delete','合同台账','contract_20260321034244_66bf0e7f','删除合同：T-034244 / 测试合同-SMOKE034244','127.0.0.1','Codex-Smoke','2026-03-21 03:42:44',1774035764,1774035764),(179,'audit_20260321034244_ec9e02e4',1,1,'陈总','business_customer','delete','客户档案','customer_20260321034244_88d93177','删除客户档案：测试客户-SMOKE034244','127.0.0.1','Codex-Smoke','2026-03-21 03:42:44',1774035764,1774035764),(180,'audit_20260321040426_8ab7b059',1,1,'陈总','finance_transaction','add','财务流水','finance_tx_20260321040426_458d3f38','智能记账新增流水：智能记账供应商SM040408 / 123.45','127.0.0.1','Codex-Smoke','2026-03-21 04:04:26',1774037066,1774037066),(181,'audit_20260321040444_a690ef53',1,1,'陈总','finance_invoice','add','应收应付','finance_inv_20260321040444_b199caaa','智能记账新增单据：待确认回款方回款单 / 8,888.00','127.0.0.1','Codex-Smoke','2026-03-21 04:04:44',1774037084,1774037084),(182,'audit_20260321040444_c00f60ee',1,1,'陈总','finance_transaction','delete','财务流水','finance_tx_20260321040426_458d3f38','删除财务流水：智能记账供应商SM040408 / 123.45','127.0.0.1','Codex-Smoke','2026-03-21 04:04:44',1774037084,1774037084),(183,'audit_20260321040444_67195e52',1,1,'陈总','finance_invoice','delete','应收应付','finance_inv_20260321040444_b199caaa','删除单据：待确认回款方回款单 / 8888.00','127.0.0.1','Codex-Smoke','2026-03-21 04:04:44',1774037084,1774037084),(184,'audit_20260321040652_e35a011a',1,1,'陈总','finance_transaction','add','财务流水','finance_tx_20260321040652_f430413d','智能记账新增流水：智能记账供应商SM040646 / 123.45','127.0.0.1','Codex-Smoke','2026-03-21 04:06:52',1774037212,1774037212),(185,'audit_20260321040701_d58388ef',1,1,'陈总','finance_invoice','add','应收应付','finance_inv_20260321040701_5bbd89e8','智能记账新增单据：官网项目尾款 / 8,888.00','127.0.0.1','Codex-Smoke','2026-03-21 04:07:01',1774037221,1774037221),(186,'audit_20260321040701_c1e99fdb',1,1,'陈总','finance_transaction','delete','财务流水','finance_tx_20260321040652_f430413d','删除财务流水：智能记账供应商SM040646 / 123.45','127.0.0.1','Codex-Smoke','2026-03-21 04:07:01',1774037221,1774037221),(187,'audit_20260321040701_1def3d97',1,1,'陈总','finance_invoice','delete','应收应付','finance_inv_20260321040701_5bbd89e8','删除单据：官网项目尾款 / 8888.00','127.0.0.1','Codex-Smoke','2026-03-21 04:07:01',1774037221,1774037221),(188,'audit_20260321041752_3d3d68f8',1,1,'陈总','finance_transaction','add','财务流水','finance_tx_20260321041752_21595211','智能记账新增流水：智能记账供应商SM041744 / 123.45','127.0.0.1','Codex-Smoke','2026-03-21 04:17:52',1774037872,1774037872),(189,'audit_20260321041800_7677b0a1',1,1,'陈总','finance_invoice','add','应收应付','finance_inv_20260321041800_0a550aad','智能记账新增单据：企业官网重构尾款 / 8,888.00','127.0.0.1','Codex-Smoke','2026-03-21 04:18:00',1774037880,1774037880),(190,'audit_20260321041800_d69c517f',1,1,'陈总','finance_transaction','delete','财务流水','finance_tx_20260321041752_21595211','删除财务流水：智能记账供应商SM041744 / 123.45','127.0.0.1','Codex-Smoke','2026-03-21 04:18:00',1774037880,1774037880),(191,'audit_20260321041800_97df4636',1,1,'陈总','finance_invoice','delete','应收应付','finance_inv_20260321041800_0a550aad','删除单据：企业官网重构尾款 / 8888.00','127.0.0.1','Codex-Smoke','2026-03-21 04:18:00',1774037880,1774037880),(192,'audit_20260321090602_44bb1ff8',1,1,'陈总','business_customer_followup','add','客户跟进记录','customer_followup_20260321090602_0d2cb8d5','新增客户跟进：星环科技 / 冒烟客户跟进-20260321090602','127.0.0.1','Codex-Smoke','2026-03-21 09:06:02',1774055162,1774055162),(193,'audit_20260321090602_19a7efa0',1,1,'陈总','business_customer_followup','delete','客户跟进记录','customer_followup_20260321090602_0d2cb8d5','删除客户跟进：星环科技 / 冒烟客户跟进-20260321090602','127.0.0.1','Codex-Smoke','2026-03-21 09:06:02',1774055162,1774055162),(194,'audit_20260321090602_46307cbf',1,1,'陈总','business_payment_plan','add','付款计划','payment_plan_20260321090602_6d07de42','新增付款计划：冒烟付款计划-20260321090602','127.0.0.1','Codex-Smoke','2026-03-21 09:06:02',1774055162,1774055162),(195,'audit_20260321090602_bf6ad5a5',1,1,'陈总','business_payment_plan','delete','付款计划','payment_plan_20260321090602_6d07de42','删除付款计划：冒烟付款计划-20260321090602','127.0.0.1','Codex-Smoke','2026-03-21 09:06:02',1774055162,1774055162),(196,'audit_20260321090720_ad841992',1,1,'陈总','business_customer_followup','add','客户跟进记录','customer_followup_20260321090720_95c91152','新增客户跟进：星环科技 / 冒烟客户跟进-20260321090719','127.0.0.1','Codex-Smoke','2026-03-21 09:07:20',1774055240,1774055240),(197,'audit_20260321090720_3f71518c',1,1,'陈总','business_customer_followup','delete','客户跟进记录','customer_followup_20260321090720_95c91152','删除客户跟进：星环科技 / 冒烟客户跟进-20260321090719','127.0.0.1','Codex-Smoke','2026-03-21 09:07:20',1774055240,1774055240),(198,'audit_20260321090720_d9a30b72',1,1,'陈总','business_payment_plan','add','付款计划','payment_plan_20260321090720_d3511e18','新增付款计划：冒烟付款计划-20260321090720','127.0.0.1','Codex-Smoke','2026-03-21 09:07:20',1774055240,1774055240),(199,'audit_20260321090720_0f1f90e2',1,1,'陈总','business_payment_plan','delete','付款计划','payment_plan_20260321090720_d3511e18','删除付款计划：冒烟付款计划-20260321090720','127.0.0.1','Codex-Smoke','2026-03-21 09:07:20',1774055240,1774055240),(200,'audit_20260321133754_88502e77',1,1,'陈总','business_approval','add','审批中心','approval_20260321133754_0370f864','发起审批：SP-20260321-5044C6 / HT-SMOKE-20260321133753 / 审批冒烟合同-20260321133753','127.0.0.1','Codex-Smoke','2026-03-21 13:37:54',1774071474,1774071474),(201,'audit_20260321133754_95789e8c',1,1,'陈总','business_approval','approved','审批中心','approval_20260321133754_0370f864','审批通过：SP-20260321-5044C6 / HT-SMOKE-20260321133753 / 审批冒烟合同-20260321133753','127.0.0.1','Codex-Smoke','2026-03-21 13:37:54',1774071474,1774071474),(202,'audit_20260321133857_fdfbc602',1,1,'陈总','business_approval','add','审批中心','approval_20260321133857_49b18dd4','发起审批：SP-20260321-306EC8 / HT-SMOKE-20260321133857 / 审批冒烟合同-20260321133857','127.0.0.1','Codex-Smoke','2026-03-21 13:38:57',1774071537,1774071537),(203,'audit_20260321133857_e92c6bd0',1,1,'陈总','business_approval','approved','审批中心','approval_20260321133857_49b18dd4','审批通过：SP-20260321-306EC8 / HT-SMOKE-20260321133857 / 审批冒烟合同-20260321133857','127.0.0.1','Codex-Smoke','2026-03-21 13:38:57',1774071537,1774071537),(204,'audit_20260321134018_be943054',1,1,'陈总','business_approval','add','审批中心','approval_20260321134018_ff0b2b4b','发起审批：SP-20260321-23A86F / HT-SMOKE-20260321134018 / 审批测试合同-20260321134018','127.0.0.1','Codex-Smoke','2026-03-21 13:40:18',1774071618,1774071618),(205,'audit_20260321134018_3434b5ff',1,1,'陈总','business_approval','approved','审批中心','approval_20260321134018_ff0b2b4b','审批通过：SP-20260321-23A86F / HT-SMOKE-20260321134018 / 审批测试合同-20260321134018','127.0.0.1','Codex-Smoke','2026-03-21 13:40:18',1774071618,1774071618),(206,'audit_20260321134044_35b70232',1,1,'陈总','business_approval','add','审批中心','approval_20260321134044_27611a0b','发起审批：SP-20260321-4D610C / 审批测试付款-20260321134044','127.0.0.1','Codex-Smoke','2026-03-21 13:40:44',1774071644,1774071644),(207,'audit_20260321134044_85fee840',1,1,'陈总','business_approval','approved','审批中心','approval_20260321134044_27611a0b','审批通过：SP-20260321-4D610C / 审批测试付款-20260321134044','127.0.0.1','Codex-Smoke','2026-03-21 13:40:44',1774071644,1774071644),(208,'audit_20260321141138_b0d02dd9',1,1,'陈总','business_supplier','add','供应商档案','supplier_20260321141138_8defabc4','新增供应商：smoke-temp-supplier','127.0.0.1','curl/8.18.0','2026-03-21 14:11:38',1774073498,1774073498),(209,'audit_20260321141153_d877f14e',1,1,'陈总','business_supplier','delete','供应商档案','supplier_20260321141138_8defabc4','删除供应商：smoke-temp-supplier','127.0.0.1','curl/8.18.0','2026-03-21 14:11:53',1774073513,1774073513),(210,'audit_20260321141226_c7ac1f06',1,1,'陈总','business_supplier','add','供应商档案','supplier_20260321141226_e668ce37','新增供应商：测试供应商1774073546','127.0.0.1','curl/8.18.0','2026-03-21 14:12:26',1774073546,1774073546),(211,'audit_20260321141321_b2e9695b',1,1,'陈总','business_supplier','add','供应商档案','supplier_20260321141321_d6bf4e5e','新增供应商：smoke-supplier-1774073601','127.0.0.1','curl/8.18.0','2026-03-21 14:13:21',1774073601,1774073601),(212,'audit_20260321145521_30077ad6',1,1,'陈总','business_supplier','add','供应商档案','supplier_20260321145521_44f570e0','新增供应商：smoke-supplier-20260321145521','127.0.0.1','','2026-03-21 14:55:21',1774076121,1774076121),(213,'audit_20260321145521_d32f977d',1,1,'陈总','business_expense_request','add','费用申请','expense_request_20260321145521_94945010','新增费用申请：FY-20260321-FA014E / smoke-expense-20260321145521','127.0.0.1','','2026-03-21 14:55:21',1774076121,1774076121),(214,'audit_20260321145521_1e50ff20',1,1,'陈总','business_approval','add','审批中心','approval_20260321145521_f564f155','发起审批：SP-20260321-A4575F / FY-20260321-FA014E / smoke-expense-20260321145521','127.0.0.1','','2026-03-21 14:55:21',1774076121,1774076121),(215,'audit_20260321145521_46d1b557',1,1,'陈总','business_approval','approved','审批中心','approval_20260321145521_f564f155','审批通过：SP-20260321-A4575F / FY-20260321-FA014E / smoke-expense-20260321145521','127.0.0.1','','2026-03-21 14:55:21',1774076121,1774076121),(216,'audit_20260321145521_e369920f',1,1,'陈总','business_payment_plan','add','付款计划','payment_plan_20260321145521_2805f5b2','由费用申请生成付款计划：费用付款 / smoke-expense-20260321145521','127.0.0.1','','2026-03-21 14:55:21',1774076121,1774076121),(217,'audit_20260321145521_0b552de9',1,1,'陈总','business_expense_request','create_payment_plan','费用申请','expense_request_20260321145521_94945010','为费用申请生成付款计划：FY-20260321-FA014E / smoke-expense-20260321145521','127.0.0.1','','2026-03-21 14:55:21',1774076121,1774076121),(218,'audit_20260321145521_5cc0f2aa',1,1,'陈总','business_payment_plan','delete','付款计划','payment_plan_20260321145521_2805f5b2','删除付款计划：费用付款 / smoke-expense-20260321145521','127.0.0.1','','2026-03-21 14:55:21',1774076121,1774076121),(219,'audit_20260321145522_a8b4db32',1,1,'陈总','business_approval','delete','审批中心','approval_20260321145521_f564f155','删除审批：SP-20260321-A4575F / FY-20260321-FA014E / smoke-expense-20260321145521','127.0.0.1','','2026-03-21 14:55:22',1774076122,1774076122),(220,'audit_20260321145522_b9db7d2d',1,1,'陈总','business_expense_request','delete','费用申请','expense_request_20260321145521_94945010','删除费用申请：FY-20260321-FA014E / smoke-expense-20260321145521','127.0.0.1','','2026-03-21 14:55:22',1774076122,1774076122),(221,'audit_20260321145522_44455a93',1,1,'陈总','business_supplier','delete','供应商档案','supplier_20260321145521_44f570e0','删除供应商：smoke-supplier-20260321145521','127.0.0.1','','2026-03-21 14:55:22',1774076122,1774076122),(222,'audit_20260321145640_5826e711',1,1,'陈总','business_supplier','add','供应商档案','supplier_20260321145640_ce5131f5','新增供应商：smoke-supplier-20260321145640','127.0.0.1','','2026-03-21 14:56:40',1774076200,1774076200),(223,'audit_20260321145640_324a5ee5',1,1,'陈总','business_expense_request','add','费用申请','expense_request_20260321145640_abf4f415','新增费用申请：FY-20260321-961904 / smoke-expense-20260321145640','127.0.0.1','','2026-03-21 14:56:40',1774076200,1774076200),(224,'audit_20260321145641_713adcfa',1,1,'陈总','business_approval','add','审批中心','approval_20260321145641_be1e05b2','发起审批：SP-20260321-336ABA / FY-20260321-961904 / smoke-expense-20260321145640','127.0.0.1','','2026-03-21 14:56:41',1774076201,1774076201),(225,'audit_20260321145641_8749b341',1,1,'陈总','business_approval','approved','审批中心','approval_20260321145641_be1e05b2','审批通过：SP-20260321-336ABA / FY-20260321-961904 / smoke-expense-20260321145640','127.0.0.1','','2026-03-21 14:56:41',1774076201,1774076201),(226,'audit_20260321145641_e4fa2c62',1,1,'陈总','business_payment_plan','add','付款计划','payment_plan_20260321145641_7a616c1e','由费用申请生成付款计划：费用付款 / smoke-expense-20260321145640','127.0.0.1','','2026-03-21 14:56:41',1774076201,1774076201),(227,'audit_20260321145641_3122cec2',1,1,'陈总','business_expense_request','create_payment_plan','费用申请','expense_request_20260321145640_abf4f415','为费用申请生成付款计划：FY-20260321-961904 / smoke-expense-20260321145640','127.0.0.1','','2026-03-21 14:56:41',1774076201,1774076201),(228,'audit_20260321145641_c87a08ee',1,1,'陈总','business_payment_plan','delete','付款计划','payment_plan_20260321145641_7a616c1e','删除付款计划：费用付款 / smoke-expense-20260321145640','127.0.0.1','','2026-03-21 14:56:41',1774076201,1774076201),(229,'audit_20260321145641_7cd33046',1,1,'陈总','business_approval','delete','审批中心','approval_20260321145641_be1e05b2','删除审批：SP-20260321-336ABA / FY-20260321-961904 / smoke-expense-20260321145640','127.0.0.1','','2026-03-21 14:56:41',1774076201,1774076201),(230,'audit_20260321145641_9db88a40',1,1,'陈总','business_expense_request','delete','费用申请','expense_request_20260321145640_abf4f415','删除费用申请：FY-20260321-961904 / smoke-expense-20260321145640','127.0.0.1','','2026-03-21 14:56:41',1774076201,1774076201),(231,'audit_20260321145641_06b6beea',1,1,'陈总','business_supplier','delete','供应商档案','supplier_20260321145640_ce5131f5','删除供应商：smoke-supplier-20260321145640','127.0.0.1','','2026-03-21 14:56:41',1774076201,1774076201),(232,'audit_20260321153922_29b502d6',1,1,'陈总','finance_transaction','add','财务流水','finance_tx_20260321153922_d97c7ef2','智能记账新增流水：前端技术 / 1,000.00','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-21 15:39:22',1774078762,1774078762),(233,'audit_20260321200503_fa49a5c7',1,1,'陈总','business_supplier','add','供应商档案','supplier_20260321200503_a68f79f6','新增供应商：smoke-po-supplier-20260321200503','127.0.0.1','','2026-03-21 20:05:03',1774094703,1774094703),(234,'audit_20260321200503_029c45dc',1,1,'陈总','business_purchase_order','add','采购单','purchase_order_20260321200503_86146de7','新增采购单：PO-20260321-45752E / smoke-purchase-20260321200503','127.0.0.1','','2026-03-21 20:05:03',1774094703,1774094703),(235,'audit_20260321200503_8a08d92b',1,1,'陈总','business_approval','add','审批中心','approval_20260321200503_3ca60572','发起审批：SP-20260321-D464E7 / PO-20260321-45752E / smoke-purchase-20260321200503','127.0.0.1','','2026-03-21 20:05:03',1774094703,1774094703),(236,'audit_20260321200503_958d494f',1,1,'陈总','business_approval','approved','审批中心','approval_20260321200503_3ca60572','审批通过：SP-20260321-D464E7 / PO-20260321-45752E / smoke-purchase-20260321200503','127.0.0.1','','2026-03-21 20:05:03',1774094703,1774094703),(237,'audit_20260321200503_1eab319b',1,1,'陈总','business_payment_plan','add','付款计划','payment_plan_20260321200503_b02155bc','由采购单生成付款计划：采购付款 / smoke-purchase-20260321200503','127.0.0.1','','2026-03-21 20:05:03',1774094703,1774094703),(238,'audit_20260321200503_5266f370',1,1,'陈总','business_purchase_order','create_payment_plan','采购单','purchase_order_20260321200503_86146de7','为采购单生成付款计划：PO-20260321-45752E / smoke-purchase-20260321200503','127.0.0.1','','2026-03-21 20:05:03',1774094703,1774094703),(239,'audit_20260321200503_b1675264',1,1,'陈总','business_payment_plan','delete','付款计划','payment_plan_20260321200503_b02155bc','删除付款计划：采购付款 / smoke-purchase-20260321200503','127.0.0.1','','2026-03-21 20:05:03',1774094703,1774094703),(240,'audit_20260321200503_3bead107',1,1,'陈总','business_approval','delete','审批中心','approval_20260321200503_3ca60572','删除审批：SP-20260321-D464E7 / PO-20260321-45752E / smoke-purchase-20260321200503','127.0.0.1','','2026-03-21 20:05:03',1774094703,1774094703),(241,'audit_20260321200504_2abbf6af',1,1,'陈总','business_supplier','delete','供应商档案','supplier_20260321200503_a68f79f6','删除供应商：smoke-po-supplier-20260321200503','127.0.0.1','','2026-03-21 20:05:04',1774094704,1774094704),(242,'audit_20260321200528_abaf15ea',1,1,'陈总','business_supplier','add','供应商档案','supplier_20260321200528_123efa12','新增供应商：smoke-po-supplier-20260321200527','127.0.0.1','','2026-03-21 20:05:28',1774094728,1774094728),(243,'audit_20260321200528_323c0bf2',1,1,'陈总','business_purchase_order','add','采购单','purchase_order_20260321200528_6fae3693','新增采购单：PO-20260321-C23B37 / smoke-purchase-20260321200527','127.0.0.1','','2026-03-21 20:05:28',1774094728,1774094728),(244,'audit_20260321200528_a4f73b1b',1,1,'陈总','business_approval','add','审批中心','approval_20260321200528_0430d1a5','发起审批：SP-20260321-0DF3D0 / PO-20260321-C23B37 / smoke-purchase-20260321200527','127.0.0.1','','2026-03-21 20:05:28',1774094728,1774094728),(245,'audit_20260321200528_055d6fc0',1,1,'陈总','business_approval','approved','审批中心','approval_20260321200528_0430d1a5','审批通过：SP-20260321-0DF3D0 / PO-20260321-C23B37 / smoke-purchase-20260321200527','127.0.0.1','','2026-03-21 20:05:28',1774094728,1774094728),(246,'audit_20260321200528_1f1ffa62',1,1,'陈总','business_payment_plan','add','付款计划','payment_plan_20260321200528_9a4daa60','由采购单生成付款计划：采购付款 / smoke-purchase-20260321200527','127.0.0.1','','2026-03-21 20:05:28',1774094728,1774094728),(247,'audit_20260321200528_f19846c5',1,1,'陈总','business_purchase_order','create_payment_plan','采购单','purchase_order_20260321200528_6fae3693','为采购单生成付款计划：PO-20260321-C23B37 / smoke-purchase-20260321200527','127.0.0.1','','2026-03-21 20:05:28',1774094728,1774094728),(248,'audit_20260321200528_f825383a',1,1,'陈总','business_payment_plan','delete','付款计划','payment_plan_20260321200528_9a4daa60','删除付款计划：采购付款 / smoke-purchase-20260321200527','127.0.0.1','','2026-03-21 20:05:28',1774094728,1774094728),(249,'audit_20260321200528_48edf513',1,1,'陈总','business_approval','delete','审批中心','approval_20260321200528_0430d1a5','删除审批：SP-20260321-0DF3D0 / PO-20260321-C23B37 / smoke-purchase-20260321200527','127.0.0.1','','2026-03-21 20:05:28',1774094728,1774094728),(250,'audit_20260321200528_03c3f84d',1,1,'陈总','business_purchase_order','delete','采购单','purchase_order_20260321200528_6fae3693','删除采购单：PO-20260321-C23B37 / smoke-purchase-20260321200527','127.0.0.1','','2026-03-21 20:05:28',1774094728,1774094728),(251,'audit_20260321200528_e2497039',1,1,'陈总','business_supplier','delete','供应商档案','supplier_20260321200528_123efa12','删除供应商：smoke-po-supplier-20260321200527','127.0.0.1','','2026-03-21 20:05:28',1774094728,1774094728),(252,'audit_20260321200705_a7704013',1,1,'陈总','business_supplier','add','供应商档案','supplier_20260321200705_2789d51a','新增供应商：smoke-supplier-20260321200705','127.0.0.1','','2026-03-21 20:07:05',1774094825,1774094825),(253,'audit_20260321200706_84d94439',1,1,'陈总','business_expense_request','add','费用申请','expense_request_20260321200706_46651ac1','新增费用申请：FY-20260321-C6F7F5 / smoke-expense-20260321200705','127.0.0.1','','2026-03-21 20:07:06',1774094826,1774094826),(254,'audit_20260321200706_a3c68615',1,1,'陈总','business_approval','add','审批中心','approval_20260321200706_a5a0f8b0','发起审批：SP-20260321-993A39 / FY-20260321-C6F7F5 / smoke-expense-20260321200705','127.0.0.1','','2026-03-21 20:07:06',1774094826,1774094826),(255,'audit_20260321200706_b131f967',1,1,'陈总','business_approval','approved','审批中心','approval_20260321200706_a5a0f8b0','审批通过：SP-20260321-993A39 / FY-20260321-C6F7F5 / smoke-expense-20260321200705','127.0.0.1','','2026-03-21 20:07:06',1774094826,1774094826),(256,'audit_20260321200706_4306cd20',1,1,'陈总','business_payment_plan','add','付款计划','payment_plan_20260321200706_a715981e','由费用申请生成付款计划：费用付款 / smoke-expense-20260321200705','127.0.0.1','','2026-03-21 20:07:06',1774094826,1774094826),(257,'audit_20260321200706_020d9c56',1,1,'陈总','business_expense_request','create_payment_plan','费用申请','expense_request_20260321200706_46651ac1','为费用申请生成付款计划：FY-20260321-C6F7F5 / smoke-expense-20260321200705','127.0.0.1','','2026-03-21 20:07:06',1774094826,1774094826),(258,'audit_20260321200706_f75fb944',1,1,'陈总','business_payment_plan','delete','付款计划','payment_plan_20260321200706_a715981e','删除付款计划：费用付款 / smoke-expense-20260321200705','127.0.0.1','','2026-03-21 20:07:06',1774094826,1774094826),(259,'audit_20260321200706_cf9865b0',1,1,'陈总','business_approval','delete','审批中心','approval_20260321200706_a5a0f8b0','删除审批：SP-20260321-993A39 / FY-20260321-C6F7F5 / smoke-expense-20260321200705','127.0.0.1','','2026-03-21 20:07:06',1774094826,1774094826),(260,'audit_20260321200706_e872413b',1,1,'陈总','business_expense_request','delete','费用申请','expense_request_20260321200706_46651ac1','删除费用申请：FY-20260321-C6F7F5 / smoke-expense-20260321200705','127.0.0.1','','2026-03-21 20:07:06',1774094826,1774094826),(261,'audit_20260321200706_17202015',1,1,'陈总','business_supplier','delete','供应商档案','supplier_20260321200705_2789d51a','删除供应商：smoke-supplier-20260321200705','127.0.0.1','','2026-03-21 20:07:06',1774094826,1774094826),(262,'audit_20260321224440_47b9a65b',1,1,'陈总','business_supplier','add','供应商档案','supplier_20260321224440_f23335a8','新增供应商：smoke-ps-supplier-20260321224440','127.0.0.1','','2026-03-21 22:44:40',1774104280,1774104280),(263,'audit_20260321224440_045e979d',1,1,'陈总','business_supplier','add','供应商档案','supplier_20260321224440_664ff75b','新增供应商：smoke-po-supplier-20260321224440','127.0.0.1','','2026-03-21 22:44:40',1774104280,1774104280),(264,'audit_20260321224440_90d3ef5e',1,1,'陈总','business_supplier','add','供应商档案','supplier_20260321224440_22d97e7a','新增供应商：smoke-supplier-20260321224440','127.0.0.1','','2026-03-21 22:44:40',1774104280,1774104280),(265,'audit_20260321224440_977555de',1,1,'陈总','business_purchase_order','add','采购单','purchase_order_20260321224440_60ea2635','新增采购单：PO-20260321-D83962 / smoke-purchase-settlement-20260321224440','127.0.0.1','','2026-03-21 22:44:40',1774104280,1774104280),(266,'audit_20260321224441_b72e3347',1,1,'陈总','business_purchase_order','add','采购单','purchase_order_20260321224441_d05e6068','新增采购单：PO-20260321-2CEBF7 / smoke-purchase-20260321224440','127.0.0.1','','2026-03-21 22:44:41',1774104281,1774104281),(267,'audit_20260321224441_730a7c0f',1,1,'陈总','business_expense_request','add','费用申请','expense_request_20260321224441_2232de72','新增费用申请：FY-20260321-2B9313 / smoke-expense-20260321224440','127.0.0.1','','2026-03-21 22:44:41',1774104281,1774104281),(268,'audit_20260321224441_588a6048',1,1,'陈总','business_approval','add','审批中心','approval_20260321224441_afa687d6','发起审批：SP-20260321-C16FD7 / PO-20260321-D83962 / smoke-purchase-settlement-20260321224440','127.0.0.1','','2026-03-21 22:44:41',1774104281,1774104281),(269,'audit_20260321224441_749c69a5',1,1,'陈总','business_approval','add','审批中心','approval_20260321224441_e24ea465','发起审批：SP-20260321-A89EFA / PO-20260321-2CEBF7 / smoke-purchase-20260321224440','127.0.0.1','','2026-03-21 22:44:41',1774104281,1774104281),(270,'audit_20260321224441_e6b3735d',1,1,'陈总','business_approval','add','审批中心','approval_20260321224441_b81b3da6','发起审批：SP-20260321-28E0B2 / FY-20260321-2B9313 / smoke-expense-20260321224440','127.0.0.1','','2026-03-21 22:44:41',1774104281,1774104281),(271,'audit_20260321224441_eb52ccf9',1,1,'陈总','business_approval','approved','审批中心','approval_20260321224441_afa687d6','审批通过：SP-20260321-C16FD7 / PO-20260321-D83962 / smoke-purchase-settlement-20260321224440','127.0.0.1','','2026-03-21 22:44:41',1774104281,1774104281),(272,'audit_20260321224441_cab287a4',1,1,'陈总','business_payment_plan','add','付款计划','payment_plan_20260321224441_bb41fbc6','由采购单生成付款计划：采购付款 / smoke-purchase-settlement-20260321224440','127.0.0.1','','2026-03-21 22:44:41',1774104281,1774104281),(273,'audit_20260321224441_2b645190',1,1,'陈总','business_purchase_order','create_payment_plan','采购单','purchase_order_20260321224440_60ea2635','为采购单生成付款计划：PO-20260321-D83962 / smoke-purchase-settlement-20260321224440','127.0.0.1','','2026-03-21 22:44:41',1774104281,1774104281),(274,'audit_20260321224441_c860b0b9',1,1,'陈总','business_approval','approved','审批中心','approval_20260321224441_e24ea465','审批通过：SP-20260321-A89EFA / PO-20260321-2CEBF7 / smoke-purchase-20260321224440','127.0.0.1','','2026-03-21 22:44:41',1774104281,1774104281),(275,'audit_20260321224441_eb04f5a4',1,1,'陈总','business_approval','approved','审批中心','approval_20260321224441_b81b3da6','审批通过：SP-20260321-28E0B2 / FY-20260321-2B9313 / smoke-expense-20260321224440','127.0.0.1','','2026-03-21 22:44:41',1774104281,1774104281),(276,'audit_20260321224442_cdefa0e2',1,1,'陈总','business_purchase_settlement','add','采购结算','purchase_settlement_20260321224442_a4c98d16','新增采购结算：PS-20260321-B33C94 / smoke-settlement-20260321224440','127.0.0.1','','2026-03-21 22:44:42',1774104282,1774104282),(277,'audit_20260321224442_a64e7add',1,1,'陈总','business_payment_plan','add','付款计划','payment_plan_20260321224442_1c1fee0c','由采购单生成付款计划：采购付款 / smoke-purchase-20260321224440','127.0.0.1','','2026-03-21 22:44:42',1774104282,1774104282),(278,'audit_20260321224442_75fa9139',1,1,'陈总','business_purchase_order','create_payment_plan','采购单','purchase_order_20260321224441_d05e6068','为采购单生成付款计划：PO-20260321-2CEBF7 / smoke-purchase-20260321224440','127.0.0.1','','2026-03-21 22:44:42',1774104282,1774104282),(279,'audit_20260321224442_a2daadc2',1,1,'陈总','business_payment_plan','add','付款计划','payment_plan_20260321224442_f1360b4f','由费用申请生成付款计划：费用付款 / smoke-expense-20260321224440','127.0.0.1','','2026-03-21 22:44:42',1774104282,1774104282),(280,'audit_20260321224442_8079a1df',1,1,'陈总','business_expense_request','create_payment_plan','费用申请','expense_request_20260321224441_2232de72','为费用申请生成付款计划：FY-20260321-2B9313 / smoke-expense-20260321224440','127.0.0.1','','2026-03-21 22:44:42',1774104282,1774104282),(281,'audit_20260321224442_964ff2d3',1,1,'陈总','business_payment_plan','delete','付款计划','payment_plan_20260321224442_1c1fee0c','删除付款计划：采购付款 / smoke-purchase-20260321224440','127.0.0.1','','2026-03-21 22:44:42',1774104282,1774104282),(282,'audit_20260321224442_02c6b2d5',1,1,'陈总','business_purchase_settlement','edit','采购结算','purchase_settlement_20260321224442_a4c98d16','更新采购结算：PS-20260321-B33C94 / smoke-settlement-20260321224440','127.0.0.1','','2026-03-21 22:44:42',1774104282,1774104282),(283,'audit_20260321224442_857565ed',1,1,'陈总','business_payment_plan','delete','付款计划','payment_plan_20260321224442_f1360b4f','删除付款计划：费用付款 / smoke-expense-20260321224440','127.0.0.1','','2026-03-21 22:44:42',1774104282,1774104282),(284,'audit_20260321224442_8f348936',1,1,'陈总','business_approval','delete','审批中心','approval_20260321224441_e24ea465','删除审批：SP-20260321-A89EFA / PO-20260321-2CEBF7 / smoke-purchase-20260321224440','127.0.0.1','','2026-03-21 22:44:42',1774104282,1774104282),(285,'audit_20260321224442_2437431d',1,1,'陈总','business_purchase_settlement','delete','采购结算','purchase_settlement_20260321224442_a4c98d16','删除采购结算：PS-20260321-B33C94 / smoke-settlement-20260321224440','127.0.0.1','','2026-03-21 22:44:42',1774104282,1774104282),(286,'audit_20260321224442_7a0d713f',1,1,'陈总','business_purchase_order','delete','采购单','purchase_order_20260321224441_d05e6068','删除采购单：PO-20260321-2CEBF7 / smoke-purchase-20260321224440','127.0.0.1','','2026-03-21 22:44:42',1774104282,1774104282),(287,'audit_20260321224443_0804cab8',1,1,'陈总','business_approval','delete','审批中心','approval_20260321224441_b81b3da6','删除审批：SP-20260321-28E0B2 / FY-20260321-2B9313 / smoke-expense-20260321224440','127.0.0.1','','2026-03-21 22:44:43',1774104283,1774104283),(288,'audit_20260321224443_d8fda460',1,1,'陈总','business_supplier','delete','供应商档案','supplier_20260321224440_664ff75b','删除供应商：smoke-po-supplier-20260321224440','127.0.0.1','','2026-03-21 22:44:43',1774104283,1774104283),(289,'audit_20260321224443_7e72514d',1,1,'陈总','business_expense_request','delete','费用申请','expense_request_20260321224441_2232de72','删除费用申请：FY-20260321-2B9313 / smoke-expense-20260321224440','127.0.0.1','','2026-03-21 22:44:43',1774104283,1774104283),(290,'audit_20260321224443_bad71c32',1,1,'陈总','business_payment_plan','delete','付款计划','payment_plan_20260321224441_bb41fbc6','删除付款计划：采购付款 / smoke-purchase-settlement-20260321224440','127.0.0.1','','2026-03-21 22:44:43',1774104283,1774104283),(291,'audit_20260321224443_5797db72',1,1,'陈总','business_supplier','delete','供应商档案','supplier_20260321224440_22d97e7a','删除供应商：smoke-supplier-20260321224440','127.0.0.1','','2026-03-21 22:44:43',1774104283,1774104283),(292,'audit_20260321224443_c5297a74',1,1,'陈总','business_approval','delete','审批中心','approval_20260321224441_afa687d6','删除审批：SP-20260321-C16FD7 / PO-20260321-D83962 / smoke-purchase-settlement-20260321224440','127.0.0.1','','2026-03-21 22:44:43',1774104283,1774104283),(293,'audit_20260321224443_8ca8599d',1,1,'陈总','business_purchase_order','delete','采购单','purchase_order_20260321224440_60ea2635','删除采购单：PO-20260321-D83962 / smoke-purchase-settlement-20260321224440','127.0.0.1','','2026-03-21 22:44:43',1774104283,1774104283),(294,'audit_20260321224443_7bae93bb',1,1,'陈总','business_supplier','delete','供应商档案','supplier_20260321224440_f23335a8','删除供应商：smoke-ps-supplier-20260321224440','127.0.0.1','','2026-03-21 22:44:43',1774104283,1774104283),(295,'audit_20260322024031_b8879ae6',1,1,'陈总','business_approval_template','add','审批模板','approval_template_20260322024031_a903284d','新增审批模板：smoke-multistep-template-20260322024031','127.0.0.1','','2026-03-22 02:40:31',1774118431,1774118431),(296,'audit_20260322024031_a472116d',1,1,'陈总','business_supplier','add','供应商档案','supplier_20260322024031_fe97607c','新增供应商：smoke-supplier-20260322024031','127.0.0.1','','2026-03-22 02:40:31',1774118431,1774118431),(297,'audit_20260322024032_5c746580',1,1,'陈总','business_supplier','add','供应商档案','supplier_20260322024032_71ab91a1','新增供应商：smoke-po-supplier-20260322024031','127.0.0.1','','2026-03-22 02:40:32',1774118432,1774118432),(298,'audit_20260322024032_0d9bd98d',1,1,'陈总','business_approval_template_step','add','审批模板节点','approval_template_step_20260322024032_7a86e981','新增审批节点：smoke-multistep-template-20260322024031 / 第 1 级','127.0.0.1','','2026-03-22 02:40:32',1774118432,1774118432),(299,'audit_20260322024032_584a0e28',1,1,'陈总','business_expense_request','add','费用申请','expense_request_20260322024032_80f94bcd','新增费用申请：FY-20260322-2AA577 / smoke-expense-20260322024031','127.0.0.1','','2026-03-22 02:40:32',1774118432,1774118432),(300,'audit_20260322024032_448c5194',1,1,'陈总','business_approval_template_step','add','审批模板节点','approval_template_step_20260322024032_df2f60b0','新增审批节点：smoke-multistep-template-20260322024031 / 第 2 级','127.0.0.1','','2026-03-22 02:40:32',1774118432,1774118432),(301,'audit_20260322024032_15d845c8',1,1,'陈总','business_purchase_order','add','采购单','purchase_order_20260322024032_679dae00','新增采购单：PO-20260322-BFE8C7 / smoke-purchase-20260322024031','127.0.0.1','','2026-03-22 02:40:32',1774118432,1774118432),(302,'audit_20260322024032_47fbdc70',1,1,'陈总','business_approval','add','审批中心','approval_20260322024032_2d5a14ce','发起审批：SP-20260322-E643AC / FY-20260322-2AA577 / smoke-expense-20260322024031','127.0.0.1','','2026-03-22 02:40:32',1774118432,1774118432),(303,'audit_20260322024032_e178abea',1,1,'陈总','business_supplier','add','供应商档案','supplier_20260322024032_c93f19c2','新增供应商：smoke-multistep-supplier-20260322024031','127.0.0.1','','2026-03-22 02:40:32',1774118432,1774118432),(304,'audit_20260322024032_b4b13db9',1,1,'陈总','business_approval','add','审批中心','approval_20260322024032_c3afb3e0','发起审批：SP-20260322-613589 / PO-20260322-BFE8C7 / smoke-purchase-20260322024031','127.0.0.1','','2026-03-22 02:40:32',1774118432,1774118432),(305,'audit_20260322024033_56131387',1,1,'陈总','business_approval','approved','审批中心','approval_20260322024032_2d5a14ce','审批通过：SP-20260322-E643AC / FY-20260322-2AA577 / smoke-expense-20260322024031','127.0.0.1','','2026-03-22 02:40:33',1774118433,1774118433),(306,'audit_20260322024033_883cbce9',1,1,'陈总','business_expense_request','add','费用申请','expense_request_20260322024033_afe91e1d','新增费用申请：FY-20260322-A693B0 / smoke-multistep-expense-20260322024031','127.0.0.1','','2026-03-22 02:40:33',1774118433,1774118433),(307,'audit_20260322024033_5bed94f7',1,1,'陈总','business_approval','approved','审批中心','approval_20260322024032_c3afb3e0','审批通过：SP-20260322-613589 / PO-20260322-BFE8C7 / smoke-purchase-20260322024031','127.0.0.1','','2026-03-22 02:40:33',1774118433,1774118433),(308,'audit_20260322024033_4eeb857b',1,1,'陈总','business_payment_plan','add','付款计划','payment_plan_20260322024033_ee04f497','由费用申请生成付款计划：费用付款 / smoke-expense-20260322024031','127.0.0.1','','2026-03-22 02:40:33',1774118433,1774118433),(309,'audit_20260322024033_214ac2fe',1,1,'陈总','business_expense_request','create_payment_plan','费用申请','expense_request_20260322024032_80f94bcd','为费用申请生成付款计划：FY-20260322-2AA577 / smoke-expense-20260322024031','127.0.0.1','','2026-03-22 02:40:33',1774118433,1774118433),(310,'audit_20260322024033_6b656203',1,1,'陈总','business_approval','add','审批中心','approval_20260322024033_dd3cdaf5','发起审批：SP-20260322-665321 / FY-20260322-A693B0 / smoke-multistep-expense-20260322024031','127.0.0.1','','2026-03-22 02:40:33',1774118433,1774118433),(311,'audit_20260322024033_526a3929',1,1,'陈总','business_payment_plan','add','付款计划','payment_plan_20260322024033_3f0c5090','由采购单生成付款计划：采购付款 / smoke-purchase-20260322024031','127.0.0.1','','2026-03-22 02:40:33',1774118433,1774118433),(312,'audit_20260322024033_1bb9c558',1,1,'陈总','business_purchase_order','create_payment_plan','采购单','purchase_order_20260322024032_679dae00','为采购单生成付款计划：PO-20260322-BFE8C7 / smoke-purchase-20260322024031','127.0.0.1','','2026-03-22 02:40:33',1774118433,1774118433),(313,'audit_20260322024033_81f0dcfa',1,1,'陈总','business_approval','approve_step','审批中心','approval_20260322024033_dd3cdaf5','审批流转：SP-20260322-665321 / FY-20260322-A693B0 / smoke-multistep-expense-20260322024031 / 已进入第 2 级','127.0.0.1','','2026-03-22 02:40:33',1774118433,1774118433),(314,'audit_20260322024034_b4211c4c',1,1,'陈总','business_payment_plan','delete','付款计划','payment_plan_20260322024033_3f0c5090','删除付款计划：采购付款 / smoke-purchase-20260322024031','127.0.0.1','','2026-03-22 02:40:34',1774118434,1774118434),(315,'audit_20260322024034_ed7ef22b',1,1,'陈总','business_payment_plan','delete','付款计划','payment_plan_20260322024033_ee04f497','删除付款计划：费用付款 / smoke-expense-20260322024031','127.0.0.1','','2026-03-22 02:40:34',1774118434,1774118434),(316,'audit_20260322024034_0454973a',1,1,'陈总','business_approval','approved','审批中心','approval_20260322024033_dd3cdaf5','审批通过：SP-20260322-665321 / FY-20260322-A693B0 / smoke-multistep-expense-20260322024031','127.0.0.1','','2026-03-22 02:40:34',1774118434,1774118434),(317,'audit_20260322024034_3e991280',1,1,'陈总','business_approval','delete','审批中心','approval_20260322024032_c3afb3e0','删除审批：SP-20260322-613589 / PO-20260322-BFE8C7 / smoke-purchase-20260322024031','127.0.0.1','','2026-03-22 02:40:34',1774118434,1774118434),(318,'audit_20260322024034_5645ed6f',1,1,'陈总','business_approval','delete','审批中心','approval_20260322024032_2d5a14ce','删除审批：SP-20260322-E643AC / FY-20260322-2AA577 / smoke-expense-20260322024031','127.0.0.1','','2026-03-22 02:40:34',1774118434,1774118434),(319,'audit_20260322024034_a8adcf76',1,1,'陈总','business_purchase_order','delete','采购单','purchase_order_20260322024032_679dae00','删除采购单：PO-20260322-BFE8C7 / smoke-purchase-20260322024031','127.0.0.1','','2026-03-22 02:40:34',1774118434,1774118434),(320,'audit_20260322024034_7fc54047',1,1,'陈总','business_expense_request','delete','费用申请','expense_request_20260322024032_80f94bcd','删除费用申请：FY-20260322-2AA577 / smoke-expense-20260322024031','127.0.0.1','','2026-03-22 02:40:34',1774118434,1774118434),(321,'audit_20260322024035_25d49cd6',1,1,'陈总','business_supplier','delete','供应商档案','supplier_20260322024032_71ab91a1','删除供应商：smoke-po-supplier-20260322024031','127.0.0.1','','2026-03-22 02:40:35',1774118435,1774118435),(322,'audit_20260322024035_33405092',1,1,'陈总','business_supplier','delete','供应商档案','supplier_20260322024031_fe97607c','删除供应商：smoke-supplier-20260322024031','127.0.0.1','','2026-03-22 02:40:35',1774118435,1774118435),(323,'audit_20260322024035_dbe85544',1,1,'陈总','business_approval','delete','审批中心','approval_20260322024033_dd3cdaf5','删除审批：SP-20260322-665321 / FY-20260322-A693B0 / smoke-multistep-expense-20260322024031','127.0.0.1','','2026-03-22 02:40:35',1774118435,1774118435),(324,'audit_20260322024035_d5ae8e1a',1,1,'陈总','business_expense_request','delete','费用申请','expense_request_20260322024033_afe91e1d','删除费用申请：FY-20260322-A693B0 / smoke-multistep-expense-20260322024031','127.0.0.1','','2026-03-22 02:40:35',1774118435,1774118435),(325,'audit_20260322024035_3f2957f6',1,1,'陈总','business_supplier','delete','供应商档案','supplier_20260322024032_c93f19c2','删除供应商：smoke-multistep-supplier-20260322024031','127.0.0.1','','2026-03-22 02:40:35',1774118435,1774118435),(326,'audit_20260322024035_10cb5e3b',1,1,'陈总','business_approval_template_step','delete','审批模板节点','approval_template_step_20260322024032_7a86e981','删除审批节点：smoke-multistep-template-20260322024031 / 第 1 级','127.0.0.1','','2026-03-22 02:40:35',1774118435,1774118435),(327,'audit_20260322024035_bd2cf38c',1,1,'陈总','business_approval_template_step','delete','审批模板节点','approval_template_step_20260322024032_df2f60b0','删除审批节点：smoke-multistep-template-20260322024031 / 第 2 级','127.0.0.1','','2026-03-22 02:40:35',1774118435,1774118435),(328,'audit_20260322024035_f82ac3b5',1,1,'陈总','business_approval_template','delete','审批模板','approval_template_20260322024031_a903284d','删除审批模板：smoke-multistep-template-20260322024031','127.0.0.1','','2026-03-22 02:40:35',1774118435,1774118435),(329,'audit_20260322024110_be8dfcd1',1,1,'陈总','business_supplier','add','供应商档案','supplier_20260322024110_92102c47','新增供应商：smoke-ps-supplier-20260322024109','127.0.0.1','','2026-03-22 02:41:10',1774118470,1774118470),(330,'audit_20260322024110_6547244e',1,1,'陈总','business_purchase_order','add','采购单','purchase_order_20260322024110_bb43e1d7','新增采购单：PO-20260322-47DDB7 / smoke-purchase-settlement-20260322024109','127.0.0.1','','2026-03-22 02:41:10',1774118470,1774118470),(331,'audit_20260322024110_9cfea051',1,1,'陈总','business_approval','add','审批中心','approval_20260322024110_5989bbf5','发起审批：SP-20260322-0D5C75 / PO-20260322-47DDB7 / smoke-purchase-settlement-20260322024109','127.0.0.1','','2026-03-22 02:41:10',1774118470,1774118470),(332,'audit_20260322024110_5c9f7005',1,1,'陈总','business_approval','approved','审批中心','approval_20260322024110_5989bbf5','审批通过：SP-20260322-0D5C75 / PO-20260322-47DDB7 / smoke-purchase-settlement-20260322024109','127.0.0.1','','2026-03-22 02:41:10',1774118470,1774118470),(333,'audit_20260322024110_81d2aa15',1,1,'陈总','business_payment_plan','add','付款计划','payment_plan_20260322024110_e38bf89c','由采购单生成付款计划：采购付款 / smoke-purchase-settlement-20260322024109','127.0.0.1','','2026-03-22 02:41:10',1774118470,1774118470),(334,'audit_20260322024110_29df538c',1,1,'陈总','business_purchase_order','create_payment_plan','采购单','purchase_order_20260322024110_bb43e1d7','为采购单生成付款计划：PO-20260322-47DDB7 / smoke-purchase-settlement-20260322024109','127.0.0.1','','2026-03-22 02:41:10',1774118470,1774118470),(335,'audit_20260322024110_0e7dc372',1,1,'陈总','business_purchase_settlement','add','采购结算','purchase_settlement_20260322024110_d127f5b4','新增采购结算：PS-20260322-F055EE / smoke-settlement-20260322024109','127.0.0.1','','2026-03-22 02:41:10',1774118470,1774118470),(336,'audit_20260322024110_4137ec83',1,1,'陈总','business_purchase_settlement','edit','采购结算','purchase_settlement_20260322024110_d127f5b4','更新采购结算：PS-20260322-F055EE / smoke-settlement-20260322024109','127.0.0.1','','2026-03-22 02:41:10',1774118470,1774118470),(337,'audit_20260322024110_91a610c5',1,1,'陈总','business_purchase_settlement','delete','采购结算','purchase_settlement_20260322024110_d127f5b4','删除采购结算：PS-20260322-F055EE / smoke-settlement-20260322024109','127.0.0.1','','2026-03-22 02:41:10',1774118470,1774118470),(338,'audit_20260322024111_a3c623db',1,1,'陈总','business_payment_plan','delete','付款计划','payment_plan_20260322024110_e38bf89c','删除付款计划：采购付款 / smoke-purchase-settlement-20260322024109','127.0.0.1','','2026-03-22 02:41:11',1774118471,1774118471),(339,'audit_20260322024111_02d2842c',1,1,'陈总','business_approval','delete','审批中心','approval_20260322024110_5989bbf5','删除审批：SP-20260322-0D5C75 / PO-20260322-47DDB7 / smoke-purchase-settlement-20260322024109','127.0.0.1','','2026-03-22 02:41:11',1774118471,1774118471),(340,'audit_20260322024111_c623bf37',1,1,'陈总','business_purchase_order','delete','采购单','purchase_order_20260322024110_bb43e1d7','删除采购单：PO-20260322-47DDB7 / smoke-purchase-settlement-20260322024109','127.0.0.1','','2026-03-22 02:41:11',1774118471,1774118471),(341,'audit_20260322024111_533f9672',1,1,'陈总','business_supplier','delete','供应商档案','supplier_20260322024110_92102c47','删除供应商：smoke-ps-supplier-20260322024109','127.0.0.1','','2026-03-22 02:41:11',1774118471,1774118471),(342,'audit_20260322024828_5244eb45',1,1,'陈总','business_approval_template','add','审批模板','approval_template_20260322024828_99b26d71','新增审批模板：smoke-multistep-template-20260322024828','127.0.0.1','','2026-03-22 02:48:28',1774118908,1774118908),(343,'audit_20260322024828_93061804',1,1,'陈总','business_supplier','add','供应商档案','supplier_20260322024828_337e310c','新增供应商：smoke-supplier-20260322024828','127.0.0.1','','2026-03-22 02:48:28',1774118908,1774118908),(344,'audit_20260322024829_d55abe62',1,1,'陈总','business_supplier','add','供应商档案','supplier_20260322024828_3e92d491','新增供应商：smoke-ps-supplier-20260322024828','127.0.0.1','','2026-03-22 02:48:29',1774118909,1774118909),(345,'audit_20260322024829_38bf5c3c',1,1,'陈总','business_supplier','add','供应商档案','supplier_20260322024829_330ff583','新增供应商：smoke-po-supplier-20260322024828','127.0.0.1','','2026-03-22 02:48:29',1774118909,1774118909),(346,'audit_20260322024829_d893e1d1',1,1,'陈总','business_approval_template_step','add','审批模板节点','approval_template_step_20260322024829_8e5f0922','新增审批节点：smoke-multistep-template-20260322024828 / 第 1 级','127.0.0.1','','2026-03-22 02:48:29',1774118909,1774118909),(347,'audit_20260322024829_e2c03e4e',1,1,'陈总','business_approval_template_step','add','审批模板节点','approval_template_step_20260322024829_279f5d91','新增审批节点：smoke-multistep-template-20260322024828 / 第 2 级','127.0.0.1','','2026-03-22 02:48:29',1774118909,1774118909),(348,'audit_20260322024829_68440395',1,1,'陈总','business_expense_request','add','费用申请','expense_request_20260322024829_673db8f0','新增费用申请：FY-20260322-3BE6FB / smoke-expense-20260322024828','127.0.0.1','','2026-03-22 02:48:29',1774118909,1774118909),(349,'audit_20260322024829_d31c0c27',1,1,'陈总','business_purchase_order','add','采购单','purchase_order_20260322024829_ac6b132d','新增采购单：PO-20260322-D084F9 / smoke-purchase-settlement-20260322024828','127.0.0.1','','2026-03-22 02:48:29',1774118909,1774118909),(350,'audit_20260322024829_97b3c0c1',1,1,'陈总','business_purchase_order','add','采购单','purchase_order_20260322024829_17c8dae3','新增采购单：PO-20260322-10C833 / smoke-purchase-20260322024828','127.0.0.1','','2026-03-22 02:48:29',1774118909,1774118909),(351,'audit_20260322024829_9e2c35e1',1,1,'陈总','business_supplier','add','供应商档案','supplier_20260322024829_ccc0e87c','新增供应商：smoke-multistep-supplier-20260322024828','127.0.0.1','','2026-03-22 02:48:29',1774118909,1774118909),(352,'audit_20260322024829_2969e234',1,1,'陈总','business_approval','add','审批中心','approval_20260322024829_a217b6cc','发起审批：SP-20260322-30D530 / FY-20260322-3BE6FB / smoke-expense-20260322024828','127.0.0.1','','2026-03-22 02:48:29',1774118909,1774118909),(353,'audit_20260322024830_b1a4362a',1,1,'陈总','business_approval','add','审批中心','approval_20260322024830_eeae2f15','发起审批：SP-20260322-A8DDC3 / PO-20260322-D084F9 / smoke-purchase-settlement-20260322024828','127.0.0.1','','2026-03-22 02:48:30',1774118910,1774118910),(354,'audit_20260322024830_3ff8c041',1,1,'陈总','business_approval','add','审批中心','approval_20260322024830_a725ad68','发起审批：SP-20260322-B496FE / PO-20260322-10C833 / smoke-purchase-20260322024828','127.0.0.1','','2026-03-22 02:48:30',1774118910,1774118910),(355,'audit_20260322024830_f103dc27',1,1,'陈总','business_expense_request','add','费用申请','expense_request_20260322024830_c3c59407','新增费用申请：FY-20260322-AE6967 / smoke-multistep-expense-20260322024828','127.0.0.1','','2026-03-22 02:48:30',1774118910,1774118910),(356,'audit_20260322024830_05d145ae',1,1,'陈总','business_approval','approved','审批中心','approval_20260322024829_a217b6cc','审批通过：SP-20260322-30D530 / FY-20260322-3BE6FB / smoke-expense-20260322024828','127.0.0.1','','2026-03-22 02:48:30',1774118910,1774118910),(357,'audit_20260322024830_8a33f03b',1,1,'陈总','business_approval','approved','审批中心','approval_20260322024830_eeae2f15','审批通过：SP-20260322-A8DDC3 / PO-20260322-D084F9 / smoke-purchase-settlement-20260322024828','127.0.0.1','','2026-03-22 02:48:30',1774118910,1774118910),(358,'audit_20260322024830_2ec30b19',1,1,'陈总','business_approval','approved','审批中心','approval_20260322024830_a725ad68','审批通过：SP-20260322-B496FE / PO-20260322-10C833 / smoke-purchase-20260322024828','127.0.0.1','','2026-03-22 02:48:30',1774118910,1774118910),(359,'audit_20260322024830_da7e369b',1,1,'陈总','business_payment_plan','add','付款计划','payment_plan_20260322024830_4047c2e9','由采购单生成付款计划：采购付款 / smoke-purchase-settlement-20260322024828','127.0.0.1','','2026-03-22 02:48:30',1774118910,1774118910),(360,'audit_20260322024830_0b570cd5',1,1,'陈总','business_purchase_order','create_payment_plan','采购单','purchase_order_20260322024829_ac6b132d','为采购单生成付款计划：PO-20260322-D084F9 / smoke-purchase-settlement-20260322024828','127.0.0.1','','2026-03-22 02:48:30',1774118910,1774118910),(361,'audit_20260322024830_89d2af1d',1,1,'陈总','business_approval','add','审批中心','approval_20260322024830_e7da5da4','发起审批：SP-20260322-CD5493 / FY-20260322-AE6967 / smoke-multistep-expense-20260322024828','127.0.0.1','','2026-03-22 02:48:30',1774118910,1774118910),(362,'audit_20260322024831_54074786',1,1,'陈总','business_payment_plan','add','付款计划','payment_plan_20260322024831_e9ed1d96','由费用申请生成付款计划：费用付款 / smoke-expense-20260322024828','127.0.0.1','','2026-03-22 02:48:31',1774118911,1774118911),(363,'audit_20260322024831_a7b39976',1,1,'陈总','business_expense_request','create_payment_plan','费用申请','expense_request_20260322024829_673db8f0','为费用申请生成付款计划：FY-20260322-3BE6FB / smoke-expense-20260322024828','127.0.0.1','','2026-03-22 02:48:31',1774118911,1774118911),(364,'audit_20260322024831_23fa801d',1,1,'陈总','business_payment_plan','add','付款计划','payment_plan_20260322024831_bfc8dc88','由采购单生成付款计划：采购付款 / smoke-purchase-20260322024828','127.0.0.1','','2026-03-22 02:48:31',1774118911,1774118911),(365,'audit_20260322024831_191897de',1,1,'陈总','business_purchase_order','create_payment_plan','采购单','purchase_order_20260322024829_17c8dae3','为采购单生成付款计划：PO-20260322-10C833 / smoke-purchase-20260322024828','127.0.0.1','','2026-03-22 02:48:31',1774118911,1774118911),(366,'audit_20260322024831_077ea582',1,1,'陈总','business_purchase_settlement','add','采购结算','purchase_settlement_20260322024831_94c74ee1','新增采购结算：PS-20260322-4903F0 / smoke-settlement-20260322024828','127.0.0.1','','2026-03-22 02:48:31',1774118911,1774118911),(367,'audit_20260322024831_e767657d',1,1,'陈总','business_approval','approve_step','审批中心','approval_20260322024830_e7da5da4','审批流转：SP-20260322-CD5493 / FY-20260322-AE6967 / smoke-multistep-expense-20260322024828 / 已进入第 2 级','127.0.0.1','','2026-03-22 02:48:31',1774118911,1774118911),(368,'audit_20260322024831_24d63ed3',1,1,'陈总','business_payment_plan','delete','付款计划','payment_plan_20260322024831_bfc8dc88','删除付款计划：采购付款 / smoke-purchase-20260322024828','127.0.0.1','','2026-03-22 02:48:31',1774118911,1774118911),(369,'audit_20260322024831_4b4ce0c6',1,1,'陈总','business_payment_plan','delete','付款计划','payment_plan_20260322024831_e9ed1d96','删除付款计划：费用付款 / smoke-expense-20260322024828','127.0.0.1','','2026-03-22 02:48:31',1774118911,1774118911),(370,'audit_20260322024831_eaf4d175',1,1,'陈总','business_approval','approved','审批中心','approval_20260322024830_e7da5da4','审批通过：SP-20260322-CD5493 / FY-20260322-AE6967 / smoke-multistep-expense-20260322024828','127.0.0.1','','2026-03-22 02:48:31',1774118911,1774118911),(371,'audit_20260322024832_5a6ad846',1,1,'陈总','business_purchase_settlement','edit','采购结算','purchase_settlement_20260322024831_94c74ee1','更新采购结算：PS-20260322-4903F0 / smoke-settlement-20260322024828','127.0.0.1','','2026-03-22 02:48:32',1774118912,1774118912),(372,'audit_20260322024832_1e162e55',1,1,'陈总','business_approval','delete','审批中心','approval_20260322024830_a725ad68','删除审批：SP-20260322-B496FE / PO-20260322-10C833 / smoke-purchase-20260322024828','127.0.0.1','','2026-03-22 02:48:32',1774118912,1774118912),(373,'audit_20260322024832_e66d12d0',1,1,'陈总','business_approval','delete','审批中心','approval_20260322024829_a217b6cc','删除审批：SP-20260322-30D530 / FY-20260322-3BE6FB / smoke-expense-20260322024828','127.0.0.1','','2026-03-22 02:48:32',1774118912,1774118912),(374,'audit_20260322024832_4fc559ba',1,1,'陈总','business_purchase_order','delete','采购单','purchase_order_20260322024829_17c8dae3','删除采购单：PO-20260322-10C833 / smoke-purchase-20260322024828','127.0.0.1','','2026-03-22 02:48:32',1774118912,1774118912),(375,'audit_20260322024832_352b71ac',1,1,'陈总','business_expense_request','delete','费用申请','expense_request_20260322024829_673db8f0','删除费用申请：FY-20260322-3BE6FB / smoke-expense-20260322024828','127.0.0.1','','2026-03-22 02:48:32',1774118912,1774118912),(376,'audit_20260322024832_2d441969',1,1,'陈总','business_purchase_settlement','delete','采购结算','purchase_settlement_20260322024831_94c74ee1','删除采购结算：PS-20260322-4903F0 / smoke-settlement-20260322024828','127.0.0.1','','2026-03-22 02:48:32',1774118912,1774118912),(377,'audit_20260322024832_62e21987',1,1,'陈总','business_supplier','delete','供应商档案','supplier_20260322024829_330ff583','删除供应商：smoke-po-supplier-20260322024828','127.0.0.1','','2026-03-22 02:48:32',1774118912,1774118912),(378,'audit_20260322024832_63fb7c44',1,1,'陈总','business_approval','delete','审批中心','approval_20260322024830_e7da5da4','删除审批：SP-20260322-CD5493 / FY-20260322-AE6967 / smoke-multistep-expense-20260322024828','127.0.0.1','','2026-03-22 02:48:32',1774118912,1774118912),(379,'audit_20260322024832_1d21e920',1,1,'陈总','business_supplier','delete','供应商档案','supplier_20260322024828_337e310c','删除供应商：smoke-supplier-20260322024828','127.0.0.1','','2026-03-22 02:48:32',1774118912,1774118912),(380,'audit_20260322024832_0335ef25',1,1,'陈总','business_expense_request','delete','费用申请','expense_request_20260322024830_c3c59407','删除费用申请：FY-20260322-AE6967 / smoke-multistep-expense-20260322024828','127.0.0.1','','2026-03-22 02:48:32',1774118912,1774118912),(381,'audit_20260322024833_1097fecd',1,1,'陈总','business_payment_plan','delete','付款计划','payment_plan_20260322024830_4047c2e9','删除付款计划：采购付款 / smoke-purchase-settlement-20260322024828','127.0.0.1','','2026-03-22 02:48:33',1774118913,1774118913),(382,'audit_20260322024833_d0ca43b0',1,1,'陈总','business_supplier','delete','供应商档案','supplier_20260322024829_ccc0e87c','删除供应商：smoke-multistep-supplier-20260322024828','127.0.0.1','','2026-03-22 02:48:33',1774118913,1774118913),(383,'audit_20260322024833_f79f93f1',1,1,'陈总','business_approval_template_step','delete','审批模板节点','approval_template_step_20260322024829_8e5f0922','删除审批节点：smoke-multistep-template-20260322024828 / 第 1 级','127.0.0.1','','2026-03-22 02:48:33',1774118913,1774118913),(384,'audit_20260322024833_0fa0b7dd',1,1,'陈总','business_approval_template_step','delete','审批模板节点','approval_template_step_20260322024829_279f5d91','删除审批节点：smoke-multistep-template-20260322024828 / 第 2 级','127.0.0.1','','2026-03-22 02:48:33',1774118913,1774118913),(385,'audit_20260322024833_b006a32e',1,1,'陈总','business_approval','delete','审批中心','approval_20260322024830_eeae2f15','删除审批：SP-20260322-A8DDC3 / PO-20260322-D084F9 / smoke-purchase-settlement-20260322024828','127.0.0.1','','2026-03-22 02:48:33',1774118913,1774118913),(386,'audit_20260322024833_2b5dff7d',1,1,'陈总','business_approval_template','delete','审批模板','approval_template_20260322024828_99b26d71','删除审批模板：smoke-multistep-template-20260322024828','127.0.0.1','','2026-03-22 02:48:33',1774118913,1774118913),(387,'audit_20260322024833_1d03165c',1,1,'陈总','business_purchase_order','delete','采购单','purchase_order_20260322024829_ac6b132d','删除采购单：PO-20260322-D084F9 / smoke-purchase-settlement-20260322024828','127.0.0.1','','2026-03-22 02:48:33',1774118913,1774118913),(388,'audit_20260322024833_14b3f98c',1,1,'陈总','business_supplier','delete','供应商档案','supplier_20260322024828_3e92d491','删除供应商：smoke-ps-supplier-20260322024828','127.0.0.1','','2026-03-22 02:48:33',1774118913,1774118913),(389,'audit_20260322030926_79a0b119',1,1,'陈总','business_supplier','add','供应商档案','supplier_20260322030926_2351a805','新增供应商：smoke-trace-supplier-20260322030926','127.0.0.1','','2026-03-22 03:09:26',1774120166,1774120166),(390,'audit_20260322030926_077b6dbf',1,1,'陈总','business_purchase_order','add','采购单','purchase_order_20260322030926_fb790553','新增采购单：PO-20260322-F0C583 / smoke-trace-order-20260322030926','127.0.0.1','','2026-03-22 03:09:26',1774120166,1774120166),(391,'audit_20260322030927_f9442eb7',1,1,'陈总','business_approval','add','审批中心','approval_20260322030927_3a3f5597','发起审批：SP-20260322-1EB49B / PO-20260322-F0C583 / smoke-trace-order-20260322030926','127.0.0.1','','2026-03-22 03:09:27',1774120167,1774120167),(392,'audit_20260322030927_e1f47c4f',1,1,'陈总','business_approval','approved','审批中心','approval_20260322030927_3a3f5597','审批通过：SP-20260322-1EB49B / PO-20260322-F0C583 / smoke-trace-order-20260322030926','127.0.0.1','','2026-03-22 03:09:27',1774120167,1774120167),(393,'audit_20260322030927_7fe40ddd',1,1,'陈总','business_payment_plan','add','付款计划','payment_plan_20260322030927_acb4e756','由采购单生成付款计划：采购付款 / smoke-trace-order-20260322030926','127.0.0.1','','2026-03-22 03:09:27',1774120167,1774120167),(394,'audit_20260322030927_70fd8cad',1,1,'陈总','business_purchase_order','create_payment_plan','采购单','purchase_order_20260322030926_fb790553','为采购单生成付款计划：PO-20260322-F0C583 / smoke-trace-order-20260322030926','127.0.0.1','','2026-03-22 03:09:27',1774120167,1774120167),(395,'audit_20260322030927_01c60376',1,1,'陈总','business_purchase_reconciliation','add','采购对账','purchase_reconciliation_20260322030927_d90258fd','新增采购对账：PR-20260322-E53DA2 / smoke-trace-reconciliation-20260322030926','127.0.0.1','','2026-03-22 03:09:27',1774120167,1774120167),(396,'audit_20260322030927_a1011b65',1,1,'陈总','business_purchase_settlement','add','采购结算','purchase_settlement_20260322030927_d4cc2664','新增采购结算：PS-20260322-75C33D / smoke-trace-settlement-20260322030926','127.0.0.1','','2026-03-22 03:09:27',1774120167,1774120167),(397,'audit_20260322030927_38ff1840',1,1,'陈总','business_purchase_invoice','add','采购发票','purchase_invoice_20260322030927_e48395ab','新增采购发票：PI-SMOKE-20260322030926 / smoke-trace-invoice-20260322030926','127.0.0.1','','2026-03-22 03:09:27',1774120167,1774120167),(398,'audit_20260322030928_778cbe66',1,1,'陈总','business_purchase_invoice','delete','采购发票','purchase_invoice_20260322030927_e48395ab','删除采购发票：PI-SMOKE-20260322030926 / smoke-trace-invoice-20260322030926','127.0.0.1','','2026-03-22 03:09:28',1774120168,1774120168),(399,'audit_20260322030928_08a93c21',1,1,'陈总','business_purchase_settlement','delete','采购结算','purchase_settlement_20260322030927_d4cc2664','删除采购结算：PS-20260322-75C33D / smoke-trace-settlement-20260322030926','127.0.0.1','','2026-03-22 03:09:28',1774120168,1774120168),(400,'audit_20260322030928_5253354d',1,1,'陈总','business_purchase_reconciliation','delete','采购对账','purchase_reconciliation_20260322030927_d90258fd','删除采购对账：PR-20260322-E53DA2 / smoke-trace-reconciliation-20260322030926','127.0.0.1','','2026-03-22 03:09:28',1774120168,1774120168),(401,'audit_20260322030928_0c1831f4',1,1,'陈总','business_payment_plan','delete','付款计划','payment_plan_20260322030927_acb4e756','删除付款计划：采购付款 / smoke-trace-order-20260322030926','127.0.0.1','','2026-03-22 03:09:28',1774120168,1774120168),(402,'audit_20260322030928_68cbef63',1,1,'陈总','business_approval','delete','审批中心','approval_20260322030927_3a3f5597','删除审批：SP-20260322-1EB49B / PO-20260322-F0C583 / smoke-trace-order-20260322030926','127.0.0.1','','2026-03-22 03:09:28',1774120168,1774120168),(403,'audit_20260322030928_8ba63110',1,1,'陈总','business_purchase_order','delete','采购单','purchase_order_20260322030926_fb790553','删除采购单：PO-20260322-F0C583 / smoke-trace-order-20260322030926','127.0.0.1','','2026-03-22 03:09:28',1774120168,1774120168),(404,'audit_20260322030928_583a89f3',1,1,'陈总','business_supplier','delete','供应商档案','supplier_20260322030926_2351a805','删除供应商：smoke-trace-supplier-20260322030926','127.0.0.1','','2026-03-22 03:09:28',1774120168,1774120168),(405,'audit_20260322032227_a6aaa768',1,1,'陈总','business_supplier','add','供应商档案','supplier_20260322032227_8db21f7d','新增供应商：smoke-trace-supplier-20260322032227','127.0.0.1','','2026-03-22 03:22:27',1774120947,1774120947),(406,'audit_20260322032227_7dd9ba5e',1,1,'陈总','business_purchase_order','add','采购单','purchase_order_20260322032227_5542508f','新增采购单：PO-20260322-614EE5 / smoke-trace-order-20260322032227','127.0.0.1','','2026-03-22 03:22:27',1774120947,1774120947),(407,'audit_20260322032227_f705ecc5',1,1,'陈总','business_approval','add','审批中心','approval_20260322032227_a681beeb','发起审批：SP-20260322-730E11 / PO-20260322-614EE5 / smoke-trace-order-20260322032227','127.0.0.1','','2026-03-22 03:22:27',1774120947,1774120947),(408,'audit_20260322032228_56a5b504',1,1,'陈总','business_approval','approved','审批中心','approval_20260322032227_a681beeb','审批通过：SP-20260322-730E11 / PO-20260322-614EE5 / smoke-trace-order-20260322032227','127.0.0.1','','2026-03-22 03:22:28',1774120948,1774120948),(409,'audit_20260322032228_f4bf6c96',1,1,'陈总','business_payment_plan','add','付款计划','payment_plan_20260322032228_ddd31980','由采购单生成付款计划：采购付款 / smoke-trace-order-20260322032227','127.0.0.1','','2026-03-22 03:22:28',1774120948,1774120948),(410,'audit_20260322032228_d7e966d5',1,1,'陈总','business_purchase_order','create_payment_plan','采购单','purchase_order_20260322032227_5542508f','为采购单生成付款计划：PO-20260322-614EE5 / smoke-trace-order-20260322032227','127.0.0.1','','2026-03-22 03:22:28',1774120948,1774120948),(411,'audit_20260322032228_8a713d4e',1,1,'陈总','business_purchase_reconciliation','add','采购对账','purchase_reconciliation_20260322032228_5b671846','新增采购对账：PR-20260322-EC4EB5 / smoke-trace-reconciliation-20260322032227','127.0.0.1','','2026-03-22 03:22:28',1774120948,1774120948),(412,'audit_20260322032228_eb4da761',1,1,'陈总','business_purchase_settlement','add','采购结算','purchase_settlement_20260322032228_4ea5f947','新增采购结算：PS-20260322-89B9E8 / smoke-trace-settlement-20260322032227','127.0.0.1','','2026-03-22 03:22:28',1774120948,1774120948),(413,'audit_20260322032228_6322cae1',1,1,'陈总','business_purchase_invoice','add','采购发票','purchase_invoice_20260322032228_a6336777','新增采购发票：PI-SMOKE-20260322032227 / smoke-trace-invoice-20260322032227','127.0.0.1','','2026-03-22 03:22:28',1774120948,1774120948),(414,'audit_20260322032228_11b7a9d2',1,1,'陈总','business_purchase_invoice','delete','采购发票','purchase_invoice_20260322032228_a6336777','删除采购发票：PI-SMOKE-20260322032227 / smoke-trace-invoice-20260322032227','127.0.0.1','','2026-03-22 03:22:28',1774120948,1774120948),(415,'audit_20260322032228_f1a05f72',1,1,'陈总','business_purchase_settlement','delete','采购结算','purchase_settlement_20260322032228_4ea5f947','删除采购结算：PS-20260322-89B9E8 / smoke-trace-settlement-20260322032227','127.0.0.1','','2026-03-22 03:22:28',1774120948,1774120948),(416,'audit_20260322032229_aafc3cb4',1,1,'陈总','business_purchase_reconciliation','delete','采购对账','purchase_reconciliation_20260322032228_5b671846','删除采购对账：PR-20260322-EC4EB5 / smoke-trace-reconciliation-20260322032227','127.0.0.1','','2026-03-22 03:22:29',1774120949,1774120949),(417,'audit_20260322032229_d1a715d8',1,1,'陈总','business_payment_plan','delete','付款计划','payment_plan_20260322032228_ddd31980','删除付款计划：采购付款 / smoke-trace-order-20260322032227','127.0.0.1','','2026-03-22 03:22:29',1774120949,1774120949),(418,'audit_20260322032229_b4561227',1,1,'陈总','business_approval','delete','审批中心','approval_20260322032227_a681beeb','删除审批：SP-20260322-730E11 / PO-20260322-614EE5 / smoke-trace-order-20260322032227','127.0.0.1','','2026-03-22 03:22:29',1774120949,1774120949),(419,'audit_20260322032229_d70cea58',1,1,'陈总','business_purchase_order','delete','采购单','purchase_order_20260322032227_5542508f','删除采购单：PO-20260322-614EE5 / smoke-trace-order-20260322032227','127.0.0.1','','2026-03-22 03:22:29',1774120949,1774120949),(420,'audit_20260322032229_e8eb103f',1,1,'陈总','business_supplier','delete','供应商档案','supplier_20260322032227_8db21f7d','删除供应商：smoke-trace-supplier-20260322032227','127.0.0.1','','2026-03-22 03:22:29',1774120949,1774120949),(421,'audit_20260322032434_f274e258',1,1,'陈总','business_supplier','add','供应商档案','supplier_20260322032434_e58897bd','新增供应商：smoke-trace-supplier-20260322032433','127.0.0.1','','2026-03-22 03:24:34',1774121074,1774121074),(422,'audit_20260322032434_c67aa1c1',1,1,'陈总','business_purchase_order','add','采购单','purchase_order_20260322032434_cb3a3410','新增采购单：PO-20260322-19C935 / smoke-trace-order-20260322032433','127.0.0.1','','2026-03-22 03:24:34',1774121074,1774121074),(423,'audit_20260322032434_14207baa',1,1,'陈总','business_approval','add','审批中心','approval_20260322032434_612b51dd','发起审批：SP-20260322-130774 / PO-20260322-19C935 / smoke-trace-order-20260322032433','127.0.0.1','','2026-03-22 03:24:34',1774121074,1774121074),(424,'audit_20260322032434_c578ee4c',1,1,'陈总','business_approval','approved','审批中心','approval_20260322032434_612b51dd','审批通过：SP-20260322-130774 / PO-20260322-19C935 / smoke-trace-order-20260322032433','127.0.0.1','','2026-03-22 03:24:34',1774121074,1774121074),(425,'audit_20260322032434_eee39f0c',1,1,'陈总','business_payment_plan','add','付款计划','payment_plan_20260322032434_34bc492f','由采购单生成付款计划：采购付款 / smoke-trace-order-20260322032433','127.0.0.1','','2026-03-22 03:24:34',1774121074,1774121074),(426,'audit_20260322032434_035b1d9e',1,1,'陈总','business_purchase_order','create_payment_plan','采购单','purchase_order_20260322032434_cb3a3410','为采购单生成付款计划：PO-20260322-19C935 / smoke-trace-order-20260322032433','127.0.0.1','','2026-03-22 03:24:34',1774121074,1774121074),(427,'audit_20260322032434_a429f6de',1,1,'陈总','business_purchase_reconciliation','add','采购对账','purchase_reconciliation_20260322032434_404a0f6f','新增采购对账：PR-20260322-D7D011 / smoke-trace-reconciliation-20260322032433','127.0.0.1','','2026-03-22 03:24:34',1774121074,1774121074),(428,'audit_20260322032435_2f54c373',1,1,'陈总','business_purchase_settlement','add','采购结算','purchase_settlement_20260322032435_3022f525','新增采购结算：PS-20260322-DF316C / smoke-trace-settlement-20260322032433','127.0.0.1','','2026-03-22 03:24:35',1774121075,1774121075),(429,'audit_20260322032435_d1063b88',1,1,'陈总','business_purchase_invoice','add','采购发票','purchase_invoice_20260322032435_b12345d1','新增采购发票：PI-SMOKE-20260322032433 / smoke-trace-invoice-20260322032433','127.0.0.1','','2026-03-22 03:24:35',1774121075,1774121075),(430,'audit_20260322032435_0f61cd75',1,1,'陈总','business_purchase_invoice','delete','采购发票','purchase_invoice_20260322032435_b12345d1','删除采购发票：PI-SMOKE-20260322032433 / smoke-trace-invoice-20260322032433','127.0.0.1','','2026-03-22 03:24:35',1774121075,1774121075),(431,'audit_20260322032435_29a6da11',1,1,'陈总','business_purchase_settlement','delete','采购结算','purchase_settlement_20260322032435_3022f525','删除采购结算：PS-20260322-DF316C / smoke-trace-settlement-20260322032433','127.0.0.1','','2026-03-22 03:24:35',1774121075,1774121075),(432,'audit_20260322032435_1f70df32',1,1,'陈总','business_purchase_reconciliation','delete','采购对账','purchase_reconciliation_20260322032434_404a0f6f','删除采购对账：PR-20260322-D7D011 / smoke-trace-reconciliation-20260322032433','127.0.0.1','','2026-03-22 03:24:35',1774121075,1774121075),(433,'audit_20260322032435_e1a33cc3',1,1,'陈总','business_payment_plan','delete','付款计划','payment_plan_20260322032434_34bc492f','删除付款计划：采购付款 / smoke-trace-order-20260322032433','127.0.0.1','','2026-03-22 03:24:35',1774121075,1774121075),(434,'audit_20260322032435_d64f39ce',1,1,'陈总','business_approval','delete','审批中心','approval_20260322032434_612b51dd','删除审批：SP-20260322-130774 / PO-20260322-19C935 / smoke-trace-order-20260322032433','127.0.0.1','','2026-03-22 03:24:35',1774121075,1774121075),(435,'audit_20260322032436_b6b985df',1,1,'陈总','business_purchase_order','delete','采购单','purchase_order_20260322032434_cb3a3410','删除采购单：PO-20260322-19C935 / smoke-trace-order-20260322032433','127.0.0.1','','2026-03-22 03:24:36',1774121076,1774121076),(436,'audit_20260322032436_46779391',1,1,'陈总','business_supplier','delete','供应商档案','supplier_20260322032434_e58897bd','删除供应商：smoke-trace-supplier-20260322032433','127.0.0.1','','2026-03-22 03:24:36',1774121076,1774121076),(437,'audit_20260322040338_f644a1dc',1,1,'陈总','business_supplier','add','供应商档案','supplier_20260322040338_001c5b0b','新增供应商：smoke-payment-request-supplier-20260322040338','127.0.0.1','','2026-03-22 04:03:38',1774123418,1774123418),(438,'audit_20260322040338_47162e34',1,1,'陈总','business_purchase_order','add','采购单','purchase_order_20260322040338_a1f3b43e','新增采购单：PO-20260322-940CB1 / smoke-payment-request-order-20260322040338','127.0.0.1','','2026-03-22 04:03:38',1774123418,1774123418),(439,'audit_20260322040338_97fdd56e',1,1,'陈总','business_approval','add','审批中心','approval_20260322040338_5579d97f','发起审批：SP-20260322-0B25D9 / PO-20260322-940CB1 / smoke-payment-request-order-20260322040338','127.0.0.1','','2026-03-22 04:03:38',1774123418,1774123418),(440,'audit_20260322040338_a07b8dbc',1,1,'陈总','business_approval','approve_step','审批中心','approval_20260322040338_5579d97f','审批流转：SP-20260322-0B25D9 / PO-20260322-940CB1 / smoke-payment-request-order-20260322040338 / 已进入第 2 级','127.0.0.1','','2026-03-22 04:03:38',1774123418,1774123418),(441,'audit_20260322040338_00040868',1,1,'陈总','business_approval','approved','审批中心','approval_20260322040338_5579d97f','审批通过：SP-20260322-0B25D9 / PO-20260322-940CB1 / smoke-payment-request-order-20260322040338','127.0.0.1','','2026-03-22 04:03:38',1774123418,1774123418),(442,'audit_20260322040339_13af1d53',1,1,'陈总','business_payment_plan','add','付款计划','payment_plan_20260322040339_4ded2f8f','由采购单生成付款计划：采购付款 / smoke-payment-request-order-20260322040338','127.0.0.1','','2026-03-22 04:03:39',1774123419,1774123419),(443,'audit_20260322040339_6895c847',1,1,'陈总','business_purchase_order','create_payment_plan','采购单','purchase_order_20260322040338_a1f3b43e','为采购单生成付款计划：PO-20260322-940CB1 / smoke-payment-request-order-20260322040338','127.0.0.1','','2026-03-22 04:03:39',1774123419,1774123419),(444,'audit_20260322040339_9d76714e',1,1,'陈总','business_purchase_reconciliation','add','采购对账','purchase_reconciliation_20260322040339_4a12d46b','新增采购对账：PR-20260322-A5B21E / smoke-payment-request-reconciliation-20260322040338','127.0.0.1','','2026-03-22 04:03:39',1774123419,1774123419),(445,'audit_20260322040339_1820df8f',1,1,'陈总','business_purchase_settlement','add','采购结算','purchase_settlement_20260322040339_f250757a','新增采购结算：PS-20260322-6E4250 / smoke-payment-request-settlement-20260322040338','127.0.0.1','','2026-03-22 04:03:39',1774123419,1774123419),(446,'audit_20260322040339_c335ce72',1,1,'陈总','business_purchase_invoice','add','采购发票','purchase_invoice_20260322040339_bb7a2bfc','新增采购发票：PRI-SMOKE-20260322040338 / smoke-payment-request-invoice-20260322040338','127.0.0.1','','2026-03-22 04:03:39',1774123419,1774123419),(447,'audit_20260322040339_20fd2a81',1,1,'陈总','business_payment_request','add','付款申请','payment_request_20260322040339_a028e821','新增付款申请：PR-20260322-1602EE / smoke-payment-request-20260322040338','127.0.0.1','','2026-03-22 04:03:39',1774123419,1774123419),(448,'audit_20260322040339_1db9c69d',1,1,'陈总','business_approval','add','审批中心','approval_20260322040339_b819a780','发起审批：SP-20260322-0D32CF / PR-20260322-1602EE / smoke-payment-request-20260322040338','127.0.0.1','','2026-03-22 04:03:39',1774123419,1774123419),(449,'audit_20260322040340_64cac4fe',1,1,'陈总','business_approval','approve_step','审批中心','approval_20260322040339_b819a780','审批流转：SP-20260322-0D32CF / PR-20260322-1602EE / smoke-payment-request-20260322040338 / 已进入第 2 级','127.0.0.1','','2026-03-22 04:03:40',1774123420,1774123420),(450,'audit_20260322040340_c4cfcdd3',1,1,'陈总','business_approval','approved','审批中心','approval_20260322040339_b819a780','审批通过：SP-20260322-0D32CF / PR-20260322-1602EE / smoke-payment-request-20260322040338','127.0.0.1','','2026-03-22 04:03:40',1774123420,1774123420),(451,'audit_20260322040340_330ee670',1,1,'陈总','business_payment_request','paid','付款申请','payment_request_20260322040339_a028e821','标记付款完成：PR-20260322-1602EE / smoke-payment-request-20260322040338','127.0.0.1','','2026-03-22 04:03:40',1774123420,1774123420),(452,'audit_20260322040340_68536d09',1,1,'陈总','business_payment_request','delete','付款申请','payment_request_20260322040339_a028e821','删除付款申请：PR-20260322-1602EE / smoke-payment-request-20260322040338','127.0.0.1','','2026-03-22 04:03:40',1774123420,1774123420),(453,'audit_20260322040340_0594b9a4',1,1,'陈总','business_approval','delete','审批中心','approval_20260322040339_b819a780','删除审批：SP-20260322-0D32CF / PR-20260322-1602EE / smoke-payment-request-20260322040338','127.0.0.1','','2026-03-22 04:03:40',1774123420,1774123420),(454,'audit_20260322040340_996cf662',1,1,'陈总','business_purchase_invoice','delete','采购发票','purchase_invoice_20260322040339_bb7a2bfc','删除采购发票：PRI-SMOKE-20260322040338 / smoke-payment-request-invoice-20260322040338','127.0.0.1','','2026-03-22 04:03:40',1774123420,1774123420),(455,'audit_20260322040340_fd295f42',1,1,'陈总','business_purchase_settlement','delete','采购结算','purchase_settlement_20260322040339_f250757a','删除采购结算：PS-20260322-6E4250 / smoke-payment-request-settlement-20260322040338','127.0.0.1','','2026-03-22 04:03:40',1774123420,1774123420),(456,'audit_20260322040340_24a8ac3c',1,1,'陈总','business_purchase_reconciliation','delete','采购对账','purchase_reconciliation_20260322040339_4a12d46b','删除采购对账：PR-20260322-A5B21E / smoke-payment-request-reconciliation-20260322040338','127.0.0.1','','2026-03-22 04:03:40',1774123420,1774123420),(457,'audit_20260322040341_edea04af',1,1,'陈总','business_payment_plan','delete','付款计划','payment_plan_20260322040339_4ded2f8f','删除付款计划：采购付款 / smoke-payment-request-order-20260322040338','127.0.0.1','','2026-03-22 04:03:41',1774123421,1774123421),(458,'audit_20260322040341_1ebb7cad',1,1,'陈总','business_approval','delete','审批中心','approval_20260322040338_5579d97f','删除审批：SP-20260322-0B25D9 / PO-20260322-940CB1 / smoke-payment-request-order-20260322040338','127.0.0.1','','2026-03-22 04:03:41',1774123421,1774123421),(459,'audit_20260322040341_2ace6f0f',1,1,'陈总','business_purchase_order','delete','采购单','purchase_order_20260322040338_a1f3b43e','删除采购单：PO-20260322-940CB1 / smoke-payment-request-order-20260322040338','127.0.0.1','','2026-03-22 04:03:41',1774123421,1774123421),(460,'audit_20260322040341_e4a2109f',1,1,'陈总','business_supplier','delete','供应商档案','supplier_20260322040338_001c5b0b','删除供应商：smoke-payment-request-supplier-20260322040338','127.0.0.1','','2026-03-22 04:03:41',1774123421,1774123421),(461,'audit_20260322044301_eb3b82d5',1,1,'陈总','business_payment_plan','add','付款计划','payment_plan_20260322044301_51bdd441','由采购单生成付款计划：采购付款 / 增长投放素材制作','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-22 04:43:01',1774125781,1774125781),(462,'audit_20260322044301_e6d855d9',1,1,'陈总','business_purchase_order','create_payment_plan','采购单','purchase_order_1003','为采购单生成付款计划：PO-20260321-003 / 增长投放素材制作','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-22 04:43:01',1774125781,1774125781),(463,'audit_20260322044918_c64c4f2a',1,1,'陈总','business_supplier','add','供应商档案','supplier_20260322044918_5d50db32','新增供应商：smoke-payment-request-supplier-20260322044917','127.0.0.1','','2026-03-22 04:49:18',1774126158,1774126158),(464,'audit_20260322044918_48de01b1',1,1,'陈总','business_purchase_order','add','采购单','purchase_order_20260322044918_837c63c0','新增采购单：PO-20260322-3FA220 / smoke-payment-request-order-20260322044917','127.0.0.1','','2026-03-22 04:49:18',1774126158,1774126158),(465,'audit_20260322044918_05f5e531',1,1,'陈总','business_approval','add','审批中心','approval_20260322044918_e1d444fe','发起审批：SP-20260322-DDAB90 / PO-20260322-3FA220 / smoke-payment-request-order-20260322044917','127.0.0.1','','2026-03-22 04:49:18',1774126158,1774126158),(466,'audit_20260322044918_7f7b1015',1,1,'陈总','business_approval','approve_step','审批中心','approval_20260322044918_e1d444fe','审批流转：SP-20260322-DDAB90 / PO-20260322-3FA220 / smoke-payment-request-order-20260322044917 / 已进入第 2 级','127.0.0.1','','2026-03-22 04:49:18',1774126158,1774126158),(467,'audit_20260322044918_2611383d',1,1,'陈总','business_approval','approved','审批中心','approval_20260322044918_e1d444fe','审批通过：SP-20260322-DDAB90 / PO-20260322-3FA220 / smoke-payment-request-order-20260322044917','127.0.0.1','','2026-03-22 04:49:18',1774126158,1774126158),(468,'audit_20260322044918_457c66a4',1,1,'陈总','business_payment_plan','add','付款计划','payment_plan_20260322044918_5332065c','由采购单生成付款计划：采购付款 / smoke-payment-request-order-20260322044917','127.0.0.1','','2026-03-22 04:49:18',1774126158,1774126158),(469,'audit_20260322044918_1a305161',1,1,'陈总','business_purchase_order','create_payment_plan','采购单','purchase_order_20260322044918_837c63c0','为采购单生成付款计划：PO-20260322-3FA220 / smoke-payment-request-order-20260322044917','127.0.0.1','','2026-03-22 04:49:18',1774126158,1774126158),(470,'audit_20260322044919_ab836512',1,1,'陈总','business_purchase_reconciliation','add','采购对账','purchase_reconciliation_20260322044919_af79d938','新增采购对账：PR-20260322-6E409D / smoke-payment-request-reconciliation-20260322044917','127.0.0.1','','2026-03-22 04:49:19',1774126159,1774126159),(471,'audit_20260322044919_2b486c07',1,1,'陈总','business_purchase_settlement','add','采购结算','purchase_settlement_20260322044919_625e39af','新增采购结算：PS-20260322-7A3B53 / smoke-payment-request-settlement-20260322044917','127.0.0.1','','2026-03-22 04:49:19',1774126159,1774126159),(472,'audit_20260322044919_0fdbf74a',1,1,'陈总','business_purchase_invoice','add','采购发票','purchase_invoice_20260322044919_0c7f149f','新增采购发票：PRI-SMOKE-20260322044917 / smoke-payment-request-invoice-20260322044917','127.0.0.1','','2026-03-22 04:49:19',1774126159,1774126159),(473,'audit_20260322044919_dd0b2428',1,1,'陈总','business_payment_request','add','付款申请','payment_request_20260322044919_bf16ae09','新增付款申请：PR-20260322-AC6D63 / smoke-payment-request-20260322044917','127.0.0.1','','2026-03-22 04:49:19',1774126159,1774126159),(474,'audit_20260322044919_96a21a9e',1,1,'陈总','business_approval','add','审批中心','approval_20260322044919_cd29a46e','发起审批：SP-20260322-C32638 / PR-20260322-AC6D63 / smoke-payment-request-20260322044917','127.0.0.1','','2026-03-22 04:49:19',1774126159,1774126159),(475,'audit_20260322044919_1a19b027',1,1,'陈总','business_approval','approve_step','审批中心','approval_20260322044919_cd29a46e','审批流转：SP-20260322-C32638 / PR-20260322-AC6D63 / smoke-payment-request-20260322044917 / 已进入第 2 级','127.0.0.1','','2026-03-22 04:49:19',1774126159,1774126159),(476,'audit_20260322044920_36a80322',1,1,'陈总','business_approval','approved','审批中心','approval_20260322044919_cd29a46e','审批通过：SP-20260322-C32638 / PR-20260322-AC6D63 / smoke-payment-request-20260322044917','127.0.0.1','','2026-03-22 04:49:20',1774126160,1774126160),(477,'audit_20260322044920_ad5852bc',1,1,'陈总','business_payment_request','paid','付款申请','payment_request_20260322044919_bf16ae09','标记付款完成：PR-20260322-AC6D63 / smoke-payment-request-20260322044917','127.0.0.1','','2026-03-22 04:49:20',1774126160,1774126160),(478,'audit_20260322044920_22429595',1,1,'陈总','business_payment_request','delete','付款申请','payment_request_20260322044919_bf16ae09','删除付款申请：PR-20260322-AC6D63 / smoke-payment-request-20260322044917','127.0.0.1','','2026-03-22 04:49:20',1774126160,1774126160),(479,'audit_20260322044920_0265b40a',1,1,'陈总','business_approval','delete','审批中心','approval_20260322044919_cd29a46e','删除审批：SP-20260322-C32638 / PR-20260322-AC6D63 / smoke-payment-request-20260322044917','127.0.0.1','','2026-03-22 04:49:20',1774126160,1774126160),(480,'audit_20260322044920_55c394d6',1,1,'陈总','business_purchase_invoice','delete','采购发票','purchase_invoice_20260322044919_0c7f149f','删除采购发票：PRI-SMOKE-20260322044917 / smoke-payment-request-invoice-20260322044917','127.0.0.1','','2026-03-22 04:49:20',1774126160,1774126160),(481,'audit_20260322044920_5cd728de',1,1,'陈总','business_purchase_settlement','delete','采购结算','purchase_settlement_20260322044919_625e39af','删除采购结算：PS-20260322-7A3B53 / smoke-payment-request-settlement-20260322044917','127.0.0.1','','2026-03-22 04:49:20',1774126160,1774126160),(482,'audit_20260322044920_c981084a',1,1,'陈总','business_purchase_reconciliation','delete','采购对账','purchase_reconciliation_20260322044919_af79d938','删除采购对账：PR-20260322-6E409D / smoke-payment-request-reconciliation-20260322044917','127.0.0.1','','2026-03-22 04:49:20',1774126160,1774126160),(483,'audit_20260322044920_eae550d2',1,1,'陈总','business_payment_plan','delete','付款计划','payment_plan_20260322044918_5332065c','删除付款计划：采购付款 / smoke-payment-request-order-20260322044917','127.0.0.1','','2026-03-22 04:49:20',1774126160,1774126160),(484,'audit_20260322044920_7e3f5602',1,1,'陈总','business_approval','delete','审批中心','approval_20260322044918_e1d444fe','删除审批：SP-20260322-DDAB90 / PO-20260322-3FA220 / smoke-payment-request-order-20260322044917','127.0.0.1','','2026-03-22 04:49:20',1774126160,1774126160),(485,'audit_20260322044920_60d543f8',1,1,'陈总','business_purchase_order','delete','采购单','purchase_order_20260322044918_837c63c0','删除采购单：PO-20260322-3FA220 / smoke-payment-request-order-20260322044917','127.0.0.1','','2026-03-22 04:49:20',1774126160,1774126160),(486,'audit_20260322044921_0ad98c92',1,1,'陈总','business_supplier','delete','供应商档案','supplier_20260322044918_5d50db32','删除供应商：smoke-payment-request-supplier-20260322044917','127.0.0.1','','2026-03-22 04:49:21',1774126161,1774126161),(487,'audit_20260322045013_608fb88b',1,1,'陈总','business_supplier','add','供应商档案','supplier_20260322045013_7c02bd95','新增供应商：smoke-payment-request-supplier-20260322045013','127.0.0.1','','2026-03-22 04:50:13',1774126213,1774126213),(488,'audit_20260322045013_57ea737d',1,1,'陈总','business_purchase_order','add','采购单','purchase_order_20260322045013_c86bfce7','新增采购单：PO-20260322-931882 / smoke-payment-request-order-20260322045013','127.0.0.1','','2026-03-22 04:50:13',1774126213,1774126213),(489,'audit_20260322045014_94667c19',1,1,'陈总','business_approval','add','审批中心','approval_20260322045014_1bb345c3','发起审批：SP-20260322-1BE1E5 / PO-20260322-931882 / smoke-payment-request-order-20260322045013','127.0.0.1','','2026-03-22 04:50:14',1774126214,1774126214),(490,'audit_20260322045014_c686591a',1,1,'陈总','business_approval','approve_step','审批中心','approval_20260322045014_1bb345c3','审批流转：SP-20260322-1BE1E5 / PO-20260322-931882 / smoke-payment-request-order-20260322045013 / 已进入第 2 级','127.0.0.1','','2026-03-22 04:50:14',1774126214,1774126214),(491,'audit_20260322045014_9c2c3531',1,1,'陈总','business_approval','approved','审批中心','approval_20260322045014_1bb345c3','审批通过：SP-20260322-1BE1E5 / PO-20260322-931882 / smoke-payment-request-order-20260322045013','127.0.0.1','','2026-03-22 04:50:14',1774126214,1774126214),(492,'audit_20260322045014_7a380003',1,1,'陈总','business_payment_plan','add','付款计划','payment_plan_20260322045014_c0885ba3','由采购单生成付款计划：采购付款 / smoke-payment-request-order-20260322045013','127.0.0.1','','2026-03-22 04:50:14',1774126214,1774126214),(493,'audit_20260322045014_d69f54a7',1,1,'陈总','business_purchase_order','create_payment_plan','采购单','purchase_order_20260322045013_c86bfce7','为采购单生成付款计划：PO-20260322-931882 / smoke-payment-request-order-20260322045013','127.0.0.1','','2026-03-22 04:50:14',1774126214,1774126214),(494,'audit_20260322045014_a3264bd1',1,1,'陈总','business_purchase_reconciliation','add','采购对账','purchase_reconciliation_20260322045014_30cf3c9e','新增采购对账：PR-20260322-718ABD / smoke-payment-request-reconciliation-20260322045013','127.0.0.1','','2026-03-22 04:50:14',1774126214,1774126214),(495,'audit_20260322045014_23cf4aa4',1,1,'陈总','business_purchase_settlement','add','采购结算','purchase_settlement_20260322045014_ad3b7409','新增采购结算：PS-20260322-74E14F / smoke-payment-request-settlement-20260322045013','127.0.0.1','','2026-03-22 04:50:14',1774126214,1774126214),(496,'audit_20260322045014_4e5aa34f',1,1,'陈总','business_purchase_invoice','add','采购发票','purchase_invoice_20260322045014_28fdd594','新增采购发票：PRI-SMOKE-20260322045013 / smoke-payment-request-invoice-20260322045013','127.0.0.1','','2026-03-22 04:50:14',1774126214,1774126214),(497,'audit_20260322045015_cedeb20f',1,1,'陈总','business_payment_request','add','付款申请','payment_request_20260322045015_32d88bcd','新增付款申请：PR-20260322-81FF1C / smoke-payment-request-20260322045013','127.0.0.1','','2026-03-22 04:50:15',1774126215,1774126215),(498,'audit_20260322045015_0e3a7bd6',1,1,'陈总','business_approval','add','审批中心','approval_20260322045015_4160895a','发起审批：SP-20260322-53014F / PR-20260322-81FF1C / smoke-payment-request-20260322045013','127.0.0.1','','2026-03-22 04:50:15',1774126215,1774126215),(499,'audit_20260322045015_24577789',1,1,'陈总','business_approval','approve_step','审批中心','approval_20260322045015_4160895a','审批流转：SP-20260322-53014F / PR-20260322-81FF1C / smoke-payment-request-20260322045013 / 已进入第 2 级','127.0.0.1','','2026-03-22 04:50:15',1774126215,1774126215),(500,'audit_20260322045015_c8c49efa',1,1,'陈总','business_approval','approved','审批中心','approval_20260322045015_4160895a','审批通过：SP-20260322-53014F / PR-20260322-81FF1C / smoke-payment-request-20260322045013','127.0.0.1','','2026-03-22 04:50:15',1774126215,1774126215),(501,'audit_20260322045015_ba4ff3d8',1,1,'陈总','business_payment_request','paid','付款申请','payment_request_20260322045015_32d88bcd','标记付款完成：PR-20260322-81FF1C / smoke-payment-request-20260322045013','127.0.0.1','','2026-03-22 04:50:15',1774126215,1774126215),(502,'audit_20260322045016_ee1a470d',1,1,'陈总','business_payment_request','delete','付款申请','payment_request_20260322045015_32d88bcd','删除付款申请：PR-20260322-81FF1C / smoke-payment-request-20260322045013','127.0.0.1','','2026-03-22 04:50:16',1774126216,1774126216),(503,'audit_20260322045016_c2903c7e',1,1,'陈总','business_approval','delete','审批中心','approval_20260322045015_4160895a','删除审批：SP-20260322-53014F / PR-20260322-81FF1C / smoke-payment-request-20260322045013','127.0.0.1','','2026-03-22 04:50:16',1774126216,1774126216),(504,'audit_20260322045016_dba9fb4e',1,1,'陈总','business_purchase_invoice','delete','采购发票','purchase_invoice_20260322045014_28fdd594','删除采购发票：PRI-SMOKE-20260322045013 / smoke-payment-request-invoice-20260322045013','127.0.0.1','','2026-03-22 04:50:16',1774126216,1774126216),(505,'audit_20260322045016_c60b6c82',1,1,'陈总','business_purchase_settlement','delete','采购结算','purchase_settlement_20260322045014_ad3b7409','删除采购结算：PS-20260322-74E14F / smoke-payment-request-settlement-20260322045013','127.0.0.1','','2026-03-22 04:50:16',1774126216,1774126216),(506,'audit_20260322045016_17d6ec50',1,1,'陈总','business_purchase_reconciliation','delete','采购对账','purchase_reconciliation_20260322045014_30cf3c9e','删除采购对账：PR-20260322-718ABD / smoke-payment-request-reconciliation-20260322045013','127.0.0.1','','2026-03-22 04:50:16',1774126216,1774126216),(507,'audit_20260322045016_c3664b06',1,1,'陈总','business_payment_plan','delete','付款计划','payment_plan_20260322045014_c0885ba3','删除付款计划：采购付款 / smoke-payment-request-order-20260322045013','127.0.0.1','','2026-03-22 04:50:16',1774126216,1774126216),(508,'audit_20260322045016_54345839',1,1,'陈总','business_approval','delete','审批中心','approval_20260322045014_1bb345c3','删除审批：SP-20260322-1BE1E5 / PO-20260322-931882 / smoke-payment-request-order-20260322045013','127.0.0.1','','2026-03-22 04:50:16',1774126216,1774126216),(509,'audit_20260322045016_854108d4',1,1,'陈总','business_purchase_order','delete','采购单','purchase_order_20260322045013_c86bfce7','删除采购单：PO-20260322-931882 / smoke-payment-request-order-20260322045013','127.0.0.1','','2026-03-22 04:50:16',1774126216,1774126216),(510,'audit_20260322045016_ce72b5a5',1,1,'陈总','business_supplier','delete','供应商档案','supplier_20260322045013_7c02bd95','删除供应商：smoke-payment-request-supplier-20260322045013','127.0.0.1','','2026-03-22 04:50:16',1774126216,1774126216),(511,'audit_20260322052857_bca7b9d0',1,1,'陈总','business_supplier','add','供应商档案','supplier_20260322052857_c917160d','新增供应商：smoke-payment-request-supplier-20260322052857','127.0.0.1','','2026-03-22 05:28:57',1774128537,1774128537),(512,'audit_20260322052857_694b389f',1,1,'陈总','business_purchase_order','add','采购单','purchase_order_20260322052857_9f4a7077','新增采购单：PO-20260322-A11611 / smoke-payment-request-order-20260322052857','127.0.0.1','','2026-03-22 05:28:57',1774128537,1774128537),(513,'audit_20260322052858_17cc56a6',1,1,'陈总','business_approval','add','审批中心','approval_20260322052858_3a7aa632','发起审批：SP-20260322-6AE965 / PO-20260322-A11611 / smoke-payment-request-order-20260322052857','127.0.0.1','','2026-03-22 05:28:58',1774128538,1774128538),(514,'audit_20260322052858_9435d518',1,1,'陈总','business_approval','approve_step','审批中心','approval_20260322052858_3a7aa632','审批流转：SP-20260322-6AE965 / PO-20260322-A11611 / smoke-payment-request-order-20260322052857 / 已进入第 2 级','127.0.0.1','','2026-03-22 05:28:58',1774128538,1774128538),(515,'audit_20260322052858_dfe0ad5d',1,1,'陈总','business_approval','approved','审批中心','approval_20260322052858_3a7aa632','审批通过：SP-20260322-6AE965 / PO-20260322-A11611 / smoke-payment-request-order-20260322052857','127.0.0.1','','2026-03-22 05:28:58',1774128538,1774128538),(516,'audit_20260322052858_f3c0fecb',1,1,'陈总','business_payment_plan','add','付款计划','payment_plan_20260322052858_760d1136','由采购单生成付款计划：采购付款 / smoke-payment-request-order-20260322052857','127.0.0.1','','2026-03-22 05:28:58',1774128538,1774128538),(517,'audit_20260322052858_46bb7ecd',1,1,'陈总','business_purchase_order','create_payment_plan','采购单','purchase_order_20260322052857_9f4a7077','为采购单生成付款计划：PO-20260322-A11611 / smoke-payment-request-order-20260322052857','127.0.0.1','','2026-03-22 05:28:58',1774128538,1774128538),(518,'audit_20260322052858_75783a7a',1,1,'陈总','business_purchase_reconciliation','add','采购对账','purchase_reconciliation_20260322052858_14058ce7','新增采购对账：PR-20260322-6EA4E4 / smoke-payment-request-reconciliation-20260322052857','127.0.0.1','','2026-03-22 05:28:58',1774128538,1774128538),(519,'audit_20260322052858_486c98ac',1,1,'陈总','business_purchase_settlement','add','采购结算','purchase_settlement_20260322052858_5d5c9fe4','新增采购结算：PS-20260322-C22976 / smoke-payment-request-settlement-20260322052857','127.0.0.1','','2026-03-22 05:28:58',1774128538,1774128538),(520,'audit_20260322052858_f8b8751c',1,1,'陈总','business_purchase_invoice','add','采购发票','purchase_invoice_20260322052858_6fbceadc','新增采购发票：PRI-SMOKE-20260322052857 / smoke-payment-request-invoice-20260322052857','127.0.0.1','','2026-03-22 05:28:58',1774128538,1774128538),(521,'audit_20260322052859_b82aaa87',1,1,'陈总','business_payment_request','add','付款申请','payment_request_20260322052859_15593765','新增付款申请：PR-20260322-2A3C91 / smoke-payment-request-20260322052857','127.0.0.1','','2026-03-22 05:28:59',1774128539,1774128539),(522,'audit_20260322052859_24bf19c5',1,1,'陈总','business_approval','add','审批中心','approval_20260322052859_6ae408bf','发起审批：SP-20260322-9ECF1F / PR-20260322-2A3C91 / smoke-payment-request-20260322052857','127.0.0.1','','2026-03-22 05:28:59',1774128539,1774128539),(523,'audit_20260322052859_cea11f75',1,1,'陈总','business_approval','approve_step','审批中心','approval_20260322052859_6ae408bf','审批流转：SP-20260322-9ECF1F / PR-20260322-2A3C91 / smoke-payment-request-20260322052857 / 已进入第 2 级','127.0.0.1','','2026-03-22 05:28:59',1774128539,1774128539),(524,'audit_20260322052859_4d2bfba6',1,1,'陈总','business_approval','approved','审批中心','approval_20260322052859_6ae408bf','审批通过：SP-20260322-9ECF1F / PR-20260322-2A3C91 / smoke-payment-request-20260322052857','127.0.0.1','','2026-03-22 05:28:59',1774128539,1774128539),(525,'audit_20260322052859_68ecad78',1,1,'陈总','business_payment_request','paid','付款申请','payment_request_20260322052859_15593765','标记付款完成：PR-20260322-2A3C91 / smoke-payment-request-20260322052857','127.0.0.1','','2026-03-22 05:28:59',1774128539,1774128539),(526,'audit_20260322052900_b90fabf9',1,1,'陈总','business_payment_request','delete','付款申请','payment_request_20260322052859_15593765','删除付款申请：PR-20260322-2A3C91 / smoke-payment-request-20260322052857','127.0.0.1','','2026-03-22 05:29:00',1774128540,1774128540),(527,'audit_20260322052900_e31761dc',1,1,'陈总','business_approval','delete','审批中心','approval_20260322052859_6ae408bf','删除审批：SP-20260322-9ECF1F / PR-20260322-2A3C91 / smoke-payment-request-20260322052857','127.0.0.1','','2026-03-22 05:29:00',1774128540,1774128540),(528,'audit_20260322052900_10077398',1,1,'陈总','business_purchase_invoice','delete','采购发票','purchase_invoice_20260322052858_6fbceadc','删除采购发票：PRI-SMOKE-20260322052857 / smoke-payment-request-invoice-20260322052857','127.0.0.1','','2026-03-22 05:29:00',1774128540,1774128540),(529,'audit_20260322052900_bd394c36',1,1,'陈总','business_purchase_settlement','delete','采购结算','purchase_settlement_20260322052858_5d5c9fe4','删除采购结算：PS-20260322-C22976 / smoke-payment-request-settlement-20260322052857','127.0.0.1','','2026-03-22 05:29:00',1774128540,1774128540),(530,'audit_20260322052900_f48a8b23',1,1,'陈总','business_purchase_reconciliation','delete','采购对账','purchase_reconciliation_20260322052858_14058ce7','删除采购对账：PR-20260322-6EA4E4 / smoke-payment-request-reconciliation-20260322052857','127.0.0.1','','2026-03-22 05:29:00',1774128540,1774128540),(531,'audit_20260322052900_7b9132b7',1,1,'陈总','business_payment_plan','delete','付款计划','payment_plan_20260322052858_760d1136','删除付款计划：采购付款 / smoke-payment-request-order-20260322052857','127.0.0.1','','2026-03-22 05:29:00',1774128540,1774128540),(532,'audit_20260322052900_cfa41a33',1,1,'陈总','business_approval','delete','审批中心','approval_20260322052858_3a7aa632','删除审批：SP-20260322-6AE965 / PO-20260322-A11611 / smoke-payment-request-order-20260322052857','127.0.0.1','','2026-03-22 05:29:00',1774128540,1774128540),(533,'audit_20260322052900_97da7e79',1,1,'陈总','business_purchase_order','delete','采购单','purchase_order_20260322052857_9f4a7077','删除采购单：PO-20260322-A11611 / smoke-payment-request-order-20260322052857','127.0.0.1','','2026-03-22 05:29:00',1774128540,1774128540),(534,'audit_20260322052900_35147869',1,1,'陈总','business_supplier','delete','供应商档案','supplier_20260322052857_c917160d','删除供应商：smoke-payment-request-supplier-20260322052857','127.0.0.1','','2026-03-22 05:29:00',1774128540,1774128540),(535,'audit_20260322054347_459324db',1,1,'陈总','business_supplier','add','供应商档案','supplier_20260322054347_39fced73','新增供应商：smoke-payment-request-supplier-20260322054347','127.0.0.1','','2026-03-22 05:43:47',1774129427,1774129427),(536,'audit_20260322054348_ad67ad55',1,1,'陈总','business_purchase_order','add','采购单','purchase_order_20260322054348_59328819','新增采购单：PO-20260322-094E5D / smoke-payment-request-order-20260322054347','127.0.0.1','','2026-03-22 05:43:48',1774129428,1774129428),(537,'audit_20260322054348_676ecb9c',1,1,'陈总','business_approval','add','审批中心','approval_20260322054348_a3993361','发起审批：SP-20260322-1DC2AA / PO-20260322-094E5D / smoke-payment-request-order-20260322054347','127.0.0.1','','2026-03-22 05:43:48',1774129428,1774129428),(538,'audit_20260322054348_068c27a3',1,1,'陈总','business_approval','approve_step','审批中心','approval_20260322054348_a3993361','审批流转：SP-20260322-1DC2AA / PO-20260322-094E5D / smoke-payment-request-order-20260322054347 / 已进入第 2 级','127.0.0.1','','2026-03-22 05:43:48',1774129428,1774129428),(539,'audit_20260322054348_94c0c74c',1,1,'陈总','business_approval','approved','审批中心','approval_20260322054348_a3993361','审批通过：SP-20260322-1DC2AA / PO-20260322-094E5D / smoke-payment-request-order-20260322054347','127.0.0.1','','2026-03-22 05:43:48',1774129428,1774129428),(540,'audit_20260322054348_8a267cce',1,1,'陈总','business_payment_plan','add','付款计划','payment_plan_20260322054348_7681caff','由采购单生成付款计划：采购付款 / smoke-payment-request-order-20260322054347','127.0.0.1','','2026-03-22 05:43:48',1774129428,1774129428),(541,'audit_20260322054348_c0b5957c',1,1,'陈总','business_purchase_order','create_payment_plan','采购单','purchase_order_20260322054348_59328819','为采购单生成付款计划：PO-20260322-094E5D / smoke-payment-request-order-20260322054347','127.0.0.1','','2026-03-22 05:43:48',1774129428,1774129428),(542,'audit_20260322054348_d84310a0',1,1,'陈总','business_purchase_reconciliation','add','采购对账','purchase_reconciliation_20260322054348_7851e83e','新增采购对账：PR-20260322-AB789B / smoke-payment-request-reconciliation-20260322054347','127.0.0.1','','2026-03-22 05:43:48',1774129428,1774129428),(543,'audit_20260322054349_cdd31ca4',1,1,'陈总','business_purchase_settlement','add','采购结算','purchase_settlement_20260322054349_daa96896','新增采购结算：PS-20260322-A95B2E / smoke-payment-request-settlement-20260322054347','127.0.0.1','','2026-03-22 05:43:49',1774129429,1774129429),(544,'audit_20260322054349_c15f9883',1,1,'陈总','business_purchase_invoice','add','采购发票','purchase_invoice_20260322054349_9f1b5502','新增采购发票：PRI-SMOKE-20260322054347 / smoke-payment-request-invoice-20260322054347','127.0.0.1','','2026-03-22 05:43:49',1774129429,1774129429),(545,'audit_20260322054349_22a0cf51',1,1,'陈总','business_payment_request','add','付款申请','payment_request_20260322054349_c545114e','新增付款申请：PR-20260322-997966 / smoke-payment-request-20260322054347','127.0.0.1','','2026-03-22 05:43:49',1774129429,1774129429),(546,'audit_20260322054349_b21d18a2',1,1,'陈总','business_approval','add','审批中心','approval_20260322054349_fb461e04','发起审批：SP-20260322-40F827 / PR-20260322-997966 / smoke-payment-request-20260322054347','127.0.0.1','','2026-03-22 05:43:49',1774129429,1774129429),(547,'audit_20260322054349_4bb87117',1,1,'陈总','business_approval','approve_step','审批中心','approval_20260322054349_fb461e04','审批流转：SP-20260322-40F827 / PR-20260322-997966 / smoke-payment-request-20260322054347 / 已进入第 2 级','127.0.0.1','','2026-03-22 05:43:49',1774129429,1774129429),(548,'audit_20260322054350_b0d3a664',1,1,'陈总','business_approval','approved','审批中心','approval_20260322054349_fb461e04','审批通过：SP-20260322-40F827 / PR-20260322-997966 / smoke-payment-request-20260322054347','127.0.0.1','','2026-03-22 05:43:50',1774129430,1774129430),(549,'audit_20260322054350_32f9c7b3',1,1,'陈总','business_payment_request','paid','付款申请','payment_request_20260322054349_c545114e','标记付款完成：PR-20260322-997966 / smoke-payment-request-20260322054347','127.0.0.1','','2026-03-22 05:43:50',1774129430,1774129430),(550,'audit_20260322054350_e4286ca5',1,1,'陈总','business_payment_request','delete','付款申请','payment_request_20260322054349_c545114e','删除付款申请：PR-20260322-997966 / smoke-payment-request-20260322054347','127.0.0.1','','2026-03-22 05:43:50',1774129430,1774129430),(551,'audit_20260322054350_8d9b7f52',1,1,'陈总','business_approval','delete','审批中心','approval_20260322054349_fb461e04','删除审批：SP-20260322-40F827 / PR-20260322-997966 / smoke-payment-request-20260322054347','127.0.0.1','','2026-03-22 05:43:50',1774129430,1774129430),(552,'audit_20260322054350_cf5e3e53',1,1,'陈总','business_purchase_invoice','delete','采购发票','purchase_invoice_20260322054349_9f1b5502','删除采购发票：PRI-SMOKE-20260322054347 / smoke-payment-request-invoice-20260322054347','127.0.0.1','','2026-03-22 05:43:50',1774129430,1774129430),(553,'audit_20260322054350_69c07e91',1,1,'陈总','business_purchase_settlement','delete','采购结算','purchase_settlement_20260322054349_daa96896','删除采购结算：PS-20260322-A95B2E / smoke-payment-request-settlement-20260322054347','127.0.0.1','','2026-03-22 05:43:50',1774129430,1774129430),(554,'audit_20260322054350_673e2b8d',1,1,'陈总','business_purchase_reconciliation','delete','采购对账','purchase_reconciliation_20260322054348_7851e83e','删除采购对账：PR-20260322-AB789B / smoke-payment-request-reconciliation-20260322054347','127.0.0.1','','2026-03-22 05:43:50',1774129430,1774129430),(555,'audit_20260322054350_98565ecb',1,1,'陈总','business_payment_plan','delete','付款计划','payment_plan_20260322054348_7681caff','删除付款计划：采购付款 / smoke-payment-request-order-20260322054347','127.0.0.1','','2026-03-22 05:43:50',1774129430,1774129430),(556,'audit_20260322054350_07fa7133',1,1,'陈总','business_approval','delete','审批中心','approval_20260322054348_a3993361','删除审批：SP-20260322-1DC2AA / PO-20260322-094E5D / smoke-payment-request-order-20260322054347','127.0.0.1','','2026-03-22 05:43:50',1774129430,1774129430),(557,'audit_20260322054351_a4511fe8',1,1,'陈总','business_purchase_order','delete','采购单','purchase_order_20260322054348_59328819','删除采购单：PO-20260322-094E5D / smoke-payment-request-order-20260322054347','127.0.0.1','','2026-03-22 05:43:51',1774129431,1774129431),(558,'audit_20260322054351_3a2d3ca9',1,1,'陈总','business_supplier','delete','供应商档案','supplier_20260322054347_39fced73','删除供应商：smoke-payment-request-supplier-20260322054347','127.0.0.1','','2026-03-22 05:43:51',1774129431,1774129431),(559,'audit_20260322060602_0ff7bcab',1,1,'陈总','business_supplier','add','供应商档案','supplier_20260322060602_d9b88ef4','新增供应商：smoke-payment-request-supplier-20260322060601','127.0.0.1','','2026-03-22 06:06:02',1774130762,1774130762),(560,'audit_20260322060602_bdfea94c',1,1,'陈总','business_purchase_order','add','采购单','purchase_order_20260322060602_9b469123','新增采购单：PO-20260322-E6835E / smoke-payment-request-order-20260322060601','127.0.0.1','','2026-03-22 06:06:02',1774130762,1774130762),(561,'audit_20260322060602_ef268ff8',1,1,'陈总','business_approval','add','审批中心','approval_20260322060602_a32a0001','发起审批：SP-20260322-378542 / PO-20260322-E6835E / smoke-payment-request-order-20260322060601','127.0.0.1','','2026-03-22 06:06:02',1774130762,1774130762),(562,'audit_20260322060602_75c09cd2',1,1,'陈总','business_approval','approve_step','审批中心','approval_20260322060602_a32a0001','审批流转：SP-20260322-378542 / PO-20260322-E6835E / smoke-payment-request-order-20260322060601 / 已进入第 2 级','127.0.0.1','','2026-03-22 06:06:02',1774130762,1774130762),(563,'audit_20260322060602_83d5a72f',1,1,'陈总','business_approval','approved','审批中心','approval_20260322060602_a32a0001','审批通过：SP-20260322-378542 / PO-20260322-E6835E / smoke-payment-request-order-20260322060601','127.0.0.1','','2026-03-22 06:06:02',1774130762,1774130762),(564,'audit_20260322060602_0690e087',1,1,'陈总','business_payment_plan','add','付款计划','payment_plan_20260322060602_aebec093','由采购单生成付款计划：采购付款 / smoke-payment-request-order-20260322060601','127.0.0.1','','2026-03-22 06:06:02',1774130762,1774130762),(565,'audit_20260322060602_d5b1bcbb',1,1,'陈总','business_purchase_order','create_payment_plan','采购单','purchase_order_20260322060602_9b469123','为采购单生成付款计划：PO-20260322-E6835E / smoke-payment-request-order-20260322060601','127.0.0.1','','2026-03-22 06:06:02',1774130762,1774130762),(566,'audit_20260322060603_886ffa08',1,1,'陈总','business_purchase_reconciliation','add','采购对账','purchase_reconciliation_20260322060603_3dce8380','新增采购对账：PR-20260322-24C36D / smoke-payment-request-reconciliation-20260322060601','127.0.0.1','','2026-03-22 06:06:03',1774130763,1774130763),(567,'audit_20260322060603_9ee45440',1,1,'陈总','business_purchase_settlement','add','采购结算','purchase_settlement_20260322060603_d0554a1d','新增采购结算：PS-20260322-F1565F / smoke-payment-request-settlement-20260322060601','127.0.0.1','','2026-03-22 06:06:03',1774130763,1774130763),(568,'audit_20260322060603_e4c98913',1,1,'陈总','business_purchase_invoice','add','采购发票','purchase_invoice_20260322060603_982eea83','新增采购发票：PRI-SMOKE-20260322060601 / smoke-payment-request-invoice-20260322060601','127.0.0.1','','2026-03-22 06:06:03',1774130763,1774130763),(569,'audit_20260322060603_fe1967fb',1,1,'陈总','business_payment_request','add','付款申请','payment_request_20260322060603_dea12b7e','新增付款申请：PR-20260322-FF24A6 / smoke-payment-request-20260322060601','127.0.0.1','','2026-03-22 06:06:03',1774130763,1774130763),(570,'audit_20260322060603_2a68829c',1,1,'陈总','business_approval','add','审批中心','approval_20260322060603_aff01411','发起审批：SP-20260322-B2C835 / PR-20260322-FF24A6 / smoke-payment-request-20260322060601','127.0.0.1','','2026-03-22 06:06:03',1774130763,1774130763),(571,'audit_20260322060603_b6db930b',1,1,'陈总','business_approval','approve_step','审批中心','approval_20260322060603_aff01411','审批流转：SP-20260322-B2C835 / PR-20260322-FF24A6 / smoke-payment-request-20260322060601 / 已进入第 2 级','127.0.0.1','','2026-03-22 06:06:03',1774130763,1774130763),(572,'audit_20260322060604_63dc633f',1,1,'陈总','business_approval','approved','审批中心','approval_20260322060603_aff01411','审批通过：SP-20260322-B2C835 / PR-20260322-FF24A6 / smoke-payment-request-20260322060601','127.0.0.1','','2026-03-22 06:06:04',1774130764,1774130764),(573,'audit_20260322060604_a20d9cd3',1,1,'陈总','business_payment_request','paid','付款申请','payment_request_20260322060603_dea12b7e','标记付款完成：PR-20260322-FF24A6 / smoke-payment-request-20260322060601','127.0.0.1','','2026-03-22 06:06:04',1774130764,1774130764),(574,'audit_20260322060604_39fd6b56',1,1,'陈总','business_payment_request','delete','付款申请','payment_request_20260322060603_dea12b7e','删除付款申请：PR-20260322-FF24A6 / smoke-payment-request-20260322060601','127.0.0.1','','2026-03-22 06:06:04',1774130764,1774130764),(575,'audit_20260322060604_6660ba60',1,1,'陈总','business_approval','delete','审批中心','approval_20260322060603_aff01411','删除审批：SP-20260322-B2C835 / PR-20260322-FF24A6 / smoke-payment-request-20260322060601','127.0.0.1','','2026-03-22 06:06:04',1774130764,1774130764),(576,'audit_20260322060604_06877770',1,1,'陈总','business_purchase_invoice','delete','采购发票','purchase_invoice_20260322060603_982eea83','删除采购发票：PRI-SMOKE-20260322060601 / smoke-payment-request-invoice-20260322060601','127.0.0.1','','2026-03-22 06:06:04',1774130764,1774130764),(577,'audit_20260322060604_cb8ddf0c',1,1,'陈总','business_purchase_settlement','delete','采购结算','purchase_settlement_20260322060603_d0554a1d','删除采购结算：PS-20260322-F1565F / smoke-payment-request-settlement-20260322060601','127.0.0.1','','2026-03-22 06:06:04',1774130764,1774130764),(578,'audit_20260322060604_6d69d13d',1,1,'陈总','business_purchase_reconciliation','delete','采购对账','purchase_reconciliation_20260322060603_3dce8380','删除采购对账：PR-20260322-24C36D / smoke-payment-request-reconciliation-20260322060601','127.0.0.1','','2026-03-22 06:06:04',1774130764,1774130764),(579,'audit_20260322060604_8817b04d',1,1,'陈总','business_payment_plan','delete','付款计划','payment_plan_20260322060602_aebec093','删除付款计划：采购付款 / smoke-payment-request-order-20260322060601','127.0.0.1','','2026-03-22 06:06:04',1774130764,1774130764),(580,'audit_20260322060604_4597d09e',1,1,'陈总','business_approval','delete','审批中心','approval_20260322060602_a32a0001','删除审批：SP-20260322-378542 / PO-20260322-E6835E / smoke-payment-request-order-20260322060601','127.0.0.1','','2026-03-22 06:06:04',1774130764,1774130764),(581,'audit_20260322060604_a668d7f6',1,1,'陈总','business_purchase_order','delete','采购单','purchase_order_20260322060602_9b469123','删除采购单：PO-20260322-E6835E / smoke-payment-request-order-20260322060601','127.0.0.1','','2026-03-22 06:06:04',1774130764,1774130764),(582,'audit_20260322060605_708385dc',1,1,'陈总','business_supplier','delete','供应商档案','supplier_20260322060602_d9b88ef4','删除供应商：smoke-payment-request-supplier-20260322060601','127.0.0.1','','2026-03-22 06:06:05',1774130765,1774130765),(583,'audit_20260322061340_4dcd0a81',1,1,'陈总','business_supplier','add','供应商档案','supplier_20260322061340_d4925302','新增供应商：smoke-payment-request-supplier-20260322061340','127.0.0.1','','2026-03-22 06:13:40',1774131220,1774131220),(584,'audit_20260322061341_fd62e14b',1,1,'陈总','business_purchase_order','add','采购单','purchase_order_20260322061341_0b7b8e7f','新增采购单：PO-20260322-231AC1 / smoke-payment-request-order-20260322061340','127.0.0.1','','2026-03-22 06:13:41',1774131221,1774131221),(585,'audit_20260322061341_1a190c7f',1,1,'陈总','business_approval','add','审批中心','approval_20260322061341_9ff076eb','发起审批：SP-20260322-85450B / PO-20260322-231AC1 / smoke-payment-request-order-20260322061340','127.0.0.1','','2026-03-22 06:13:41',1774131221,1774131221),(586,'audit_20260322061341_08677741',1,1,'陈总','business_approval','approve_step','审批中心','approval_20260322061341_9ff076eb','审批流转：SP-20260322-85450B / PO-20260322-231AC1 / smoke-payment-request-order-20260322061340 / 已进入第 2 级','127.0.0.1','','2026-03-22 06:13:41',1774131221,1774131221),(587,'audit_20260322061341_4474c7d5',1,1,'陈总','business_approval','approved','审批中心','approval_20260322061341_9ff076eb','审批通过：SP-20260322-85450B / PO-20260322-231AC1 / smoke-payment-request-order-20260322061340','127.0.0.1','','2026-03-22 06:13:41',1774131221,1774131221),(588,'audit_20260322061341_19acd4e5',1,1,'陈总','business_payment_plan','add','付款计划','payment_plan_20260322061341_265500f7','由采购单生成付款计划：采购付款 / smoke-payment-request-order-20260322061340','127.0.0.1','','2026-03-22 06:13:41',1774131221,1774131221),(589,'audit_20260322061341_1406a2e9',1,1,'陈总','business_purchase_order','create_payment_plan','采购单','purchase_order_20260322061341_0b7b8e7f','为采购单生成付款计划：PO-20260322-231AC1 / smoke-payment-request-order-20260322061340','127.0.0.1','','2026-03-22 06:13:41',1774131221,1774131221),(590,'audit_20260322061341_e20b8dd5',1,1,'陈总','business_purchase_reconciliation','add','采购对账','purchase_reconciliation_20260322061341_faca2fcc','新增采购对账：PR-20260322-F4B826 / smoke-payment-request-reconciliation-20260322061340','127.0.0.1','','2026-03-22 06:13:41',1774131221,1774131221),(591,'audit_20260322061342_27ab2fd7',1,1,'陈总','business_purchase_settlement','add','采购结算','purchase_settlement_20260322061342_a9674a89','新增采购结算：PS-20260322-0E4707 / smoke-payment-request-settlement-20260322061340','127.0.0.1','','2026-03-22 06:13:42',1774131222,1774131222),(592,'audit_20260322061342_38b3a9a4',1,1,'陈总','business_purchase_invoice','add','采购发票','purchase_invoice_20260322061342_b0076a0b','新增采购发票：PRI-SMOKE-20260322061340 / smoke-payment-request-invoice-20260322061340','127.0.0.1','','2026-03-22 06:13:42',1774131222,1774131222),(593,'audit_20260322061342_e0937a75',1,1,'陈总','business_payment_request','add','付款申请','payment_request_20260322061342_dee13c14','新增付款申请：PR-20260322-FB07F4 / smoke-payment-request-20260322061340','127.0.0.1','','2026-03-22 06:13:42',1774131222,1774131222),(594,'audit_20260322061342_6047dc01',1,1,'陈总','business_approval','add','审批中心','approval_20260322061342_e2f39856','发起审批：SP-20260322-E2B325 / PR-20260322-FB07F4 / smoke-payment-request-20260322061340','127.0.0.1','','2026-03-22 06:13:42',1774131222,1774131222),(595,'audit_20260322061342_ff79c05d',1,1,'陈总','business_approval','approve_step','审批中心','approval_20260322061342_e2f39856','审批流转：SP-20260322-E2B325 / PR-20260322-FB07F4 / smoke-payment-request-20260322061340 / 已进入第 2 级','127.0.0.1','','2026-03-22 06:13:42',1774131222,1774131222),(596,'audit_20260322061342_9a4a5913',1,1,'陈总','business_approval','approved','审批中心','approval_20260322061342_e2f39856','审批通过：SP-20260322-E2B325 / PR-20260322-FB07F4 / smoke-payment-request-20260322061340','127.0.0.1','','2026-03-22 06:13:42',1774131222,1774131222),(597,'audit_20260322061343_1cfa5d20',1,1,'陈总','business_payment_request','paid','付款申请','payment_request_20260322061342_dee13c14','标记付款完成：PR-20260322-FB07F4 / smoke-payment-request-20260322061340','127.0.0.1','','2026-03-22 06:13:43',1774131223,1774131223),(598,'audit_20260322061343_5d7bcf98',1,1,'陈总','business_payment_request','delete','付款申请','payment_request_20260322061342_dee13c14','删除付款申请：PR-20260322-FB07F4 / smoke-payment-request-20260322061340','127.0.0.1','','2026-03-22 06:13:43',1774131223,1774131223),(599,'audit_20260322061343_37266335',1,1,'陈总','business_approval','delete','审批中心','approval_20260322061342_e2f39856','删除审批：SP-20260322-E2B325 / PR-20260322-FB07F4 / smoke-payment-request-20260322061340','127.0.0.1','','2026-03-22 06:13:43',1774131223,1774131223),(600,'audit_20260322061343_badc17ba',1,1,'陈总','business_purchase_invoice','delete','采购发票','purchase_invoice_20260322061342_b0076a0b','删除采购发票：PRI-SMOKE-20260322061340 / smoke-payment-request-invoice-20260322061340','127.0.0.1','','2026-03-22 06:13:43',1774131223,1774131223),(601,'audit_20260322061343_dc7e1645',1,1,'陈总','business_purchase_settlement','delete','采购结算','purchase_settlement_20260322061342_a9674a89','删除采购结算：PS-20260322-0E4707 / smoke-payment-request-settlement-20260322061340','127.0.0.1','','2026-03-22 06:13:43',1774131223,1774131223),(602,'audit_20260322061343_51470524',1,1,'陈总','business_purchase_reconciliation','delete','采购对账','purchase_reconciliation_20260322061341_faca2fcc','删除采购对账：PR-20260322-F4B826 / smoke-payment-request-reconciliation-20260322061340','127.0.0.1','','2026-03-22 06:13:43',1774131223,1774131223),(603,'audit_20260322061343_6a3f6ee9',1,1,'陈总','business_payment_plan','delete','付款计划','payment_plan_20260322061341_265500f7','删除付款计划：采购付款 / smoke-payment-request-order-20260322061340','127.0.0.1','','2026-03-22 06:13:43',1774131223,1774131223),(604,'audit_20260322061343_4c2699ef',1,1,'陈总','business_approval','delete','审批中心','approval_20260322061341_9ff076eb','删除审批：SP-20260322-85450B / PO-20260322-231AC1 / smoke-payment-request-order-20260322061340','127.0.0.1','','2026-03-22 06:13:43',1774131223,1774131223),(605,'audit_20260322061343_c1f94583',1,1,'陈总','business_purchase_order','delete','采购单','purchase_order_20260322061341_0b7b8e7f','删除采购单：PO-20260322-231AC1 / smoke-payment-request-order-20260322061340','127.0.0.1','','2026-03-22 06:13:43',1774131223,1774131223),(606,'audit_20260322061344_e1fabcf2',1,1,'陈总','business_supplier','delete','供应商档案','supplier_20260322061340_d4925302','删除供应商：smoke-payment-request-supplier-20260322061340','127.0.0.1','','2026-03-22 06:13:44',1774131224,1774131224),(607,'audit_20260322062844_621a2997',1,1,'陈总','business_supplier','add','供应商档案','supplier_20260322062844_56862d89','新增供应商：smoke-payment-request-supplier-20260322062844','127.0.0.1','','2026-03-22 06:28:44',1774132124,1774132124),(608,'audit_20260322062844_f58cfdb6',1,1,'陈总','business_purchase_order','add','采购单','purchase_order_20260322062844_2d42e69c','新增采购单：PO-20260322-BCBD9E / smoke-payment-request-order-20260322062844','127.0.0.1','','2026-03-22 06:28:44',1774132124,1774132124),(609,'audit_20260322062844_9ea81aa0',1,1,'陈总','business_approval','add','审批中心','approval_20260322062844_b6b60687','发起审批：SP-20260322-D2462D / PO-20260322-BCBD9E / smoke-payment-request-order-20260322062844','127.0.0.1','','2026-03-22 06:28:44',1774132124,1774132124),(610,'audit_20260322062845_a87a9fbe',1,1,'陈总','business_approval','approve_step','审批中心','approval_20260322062844_b6b60687','审批流转：SP-20260322-D2462D / PO-20260322-BCBD9E / smoke-payment-request-order-20260322062844 / 已进入第 2 级','127.0.0.1','','2026-03-22 06:28:45',1774132125,1774132125),(611,'audit_20260322062845_82b459d2',1,1,'陈总','business_approval','approved','审批中心','approval_20260322062844_b6b60687','审批通过：SP-20260322-D2462D / PO-20260322-BCBD9E / smoke-payment-request-order-20260322062844','127.0.0.1','','2026-03-22 06:28:45',1774132125,1774132125),(612,'audit_20260322062845_675a5190',1,1,'陈总','business_payment_plan','add','付款计划','payment_plan_20260322062845_dfbf1c38','由采购单生成付款计划：采购付款 / smoke-payment-request-order-20260322062844','127.0.0.1','','2026-03-22 06:28:45',1774132125,1774132125),(613,'audit_20260322062845_93e0e57c',1,1,'陈总','business_purchase_order','create_payment_plan','采购单','purchase_order_20260322062844_2d42e69c','为采购单生成付款计划：PO-20260322-BCBD9E / smoke-payment-request-order-20260322062844','127.0.0.1','','2026-03-22 06:28:45',1774132125,1774132125),(614,'audit_20260322062845_41221437',1,1,'陈总','business_purchase_reconciliation','add','采购对账','purchase_reconciliation_20260322062845_0b739020','新增采购对账：PR-20260322-F566C3 / smoke-payment-request-reconciliation-20260322062844','127.0.0.1','','2026-03-22 06:28:45',1774132125,1774132125),(615,'audit_20260322062845_b141e4f8',1,1,'陈总','business_purchase_settlement','add','采购结算','purchase_settlement_20260322062845_78ad7f89','新增采购结算：PS-20260322-C48B54 / smoke-payment-request-settlement-20260322062844','127.0.0.1','','2026-03-22 06:28:45',1774132125,1774132125),(616,'audit_20260322062845_17421c27',1,1,'陈总','business_purchase_invoice','add','采购发票','purchase_invoice_20260322062845_e54c4231','新增采购发票：PRI-SMOKE-20260322062844 / smoke-payment-request-invoice-20260322062844','127.0.0.1','','2026-03-22 06:28:45',1774132125,1774132125),(617,'audit_20260322062845_289ffde9',1,1,'陈总','business_payment_request','add','付款申请','payment_request_20260322062845_571137e8','新增付款申请：PR-20260322-4210F3 / smoke-payment-request-20260322062844','127.0.0.1','','2026-03-22 06:28:45',1774132125,1774132125),(618,'audit_20260322062846_a988bfa3',1,1,'陈总','business_approval','add','审批中心','approval_20260322062846_bfee250e','发起审批：SP-20260322-B0251F / PR-20260322-4210F3 / smoke-payment-request-20260322062844','127.0.0.1','','2026-03-22 06:28:46',1774132126,1774132126),(619,'audit_20260322062846_5c101352',1,1,'陈总','business_approval','approve_step','审批中心','approval_20260322062846_bfee250e','审批流转：SP-20260322-B0251F / PR-20260322-4210F3 / smoke-payment-request-20260322062844 / 已进入第 2 级','127.0.0.1','','2026-03-22 06:28:46',1774132126,1774132126),(620,'audit_20260322062846_a9065d7a',1,1,'陈总','business_approval','approved','审批中心','approval_20260322062846_bfee250e','审批通过：SP-20260322-B0251F / PR-20260322-4210F3 / smoke-payment-request-20260322062844','127.0.0.1','','2026-03-22 06:28:46',1774132126,1774132126),(621,'audit_20260322062846_b5e59644',1,1,'陈总','business_payment_request','paid','付款申请','payment_request_20260322062845_571137e8','标记付款完成：PR-20260322-4210F3 / smoke-payment-request-20260322062844','127.0.0.1','','2026-03-22 06:28:46',1774132126,1774132126),(622,'audit_20260322062846_70a69025',1,1,'陈总','business_payment_request','delete','付款申请','payment_request_20260322062845_571137e8','删除付款申请：PR-20260322-4210F3 / smoke-payment-request-20260322062844','127.0.0.1','','2026-03-22 06:28:46',1774132126,1774132126),(623,'audit_20260322062847_73ed5a12',1,1,'陈总','business_approval','delete','审批中心','approval_20260322062846_bfee250e','删除审批：SP-20260322-B0251F / PR-20260322-4210F3 / smoke-payment-request-20260322062844','127.0.0.1','','2026-03-22 06:28:47',1774132127,1774132127),(624,'audit_20260322062847_733df6e2',1,1,'陈总','business_purchase_invoice','delete','采购发票','purchase_invoice_20260322062845_e54c4231','删除采购发票：PRI-SMOKE-20260322062844 / smoke-payment-request-invoice-20260322062844','127.0.0.1','','2026-03-22 06:28:47',1774132127,1774132127),(625,'audit_20260322062847_1d5e359b',1,1,'陈总','business_purchase_settlement','delete','采购结算','purchase_settlement_20260322062845_78ad7f89','删除采购结算：PS-20260322-C48B54 / smoke-payment-request-settlement-20260322062844','127.0.0.1','','2026-03-22 06:28:47',1774132127,1774132127),(626,'audit_20260322062847_6dcbd3fd',1,1,'陈总','business_purchase_reconciliation','delete','采购对账','purchase_reconciliation_20260322062845_0b739020','删除采购对账：PR-20260322-F566C3 / smoke-payment-request-reconciliation-20260322062844','127.0.0.1','','2026-03-22 06:28:47',1774132127,1774132127),(627,'audit_20260322062847_856cf984',1,1,'陈总','business_payment_plan','delete','付款计划','payment_plan_20260322062845_dfbf1c38','删除付款计划：采购付款 / smoke-payment-request-order-20260322062844','127.0.0.1','','2026-03-22 06:28:47',1774132127,1774132127),(628,'audit_20260322062847_589af79a',1,1,'陈总','business_approval','delete','审批中心','approval_20260322062844_b6b60687','删除审批：SP-20260322-D2462D / PO-20260322-BCBD9E / smoke-payment-request-order-20260322062844','127.0.0.1','','2026-03-22 06:28:47',1774132127,1774132127),(629,'audit_20260322062847_2897abe5',1,1,'陈总','business_purchase_order','delete','采购单','purchase_order_20260322062844_2d42e69c','删除采购单：PO-20260322-BCBD9E / smoke-payment-request-order-20260322062844','127.0.0.1','','2026-03-22 06:28:47',1774132127,1774132127),(630,'audit_20260322062847_78f74382',1,1,'陈总','business_supplier','delete','供应商档案','supplier_20260322062844_56862d89','删除供应商：smoke-payment-request-supplier-20260322062844','127.0.0.1','','2026-03-22 06:28:47',1774132127,1774132127),(631,'audit_20260322065106_4d9e0ebf',1,1,'陈总','business_supplier','add','供应商档案','supplier_20260322065106_7d276d4d','新增供应商：smoke-payment-request-supplier-20260322065105','127.0.0.1','','2026-03-22 06:51:06',1774133466,1774133466),(632,'audit_20260322065106_6dd7b97b',1,1,'陈总','business_purchase_order','add','采购单','purchase_order_20260322065106_2f58a085','新增采购单：PO-20260322-7359E8 / smoke-payment-request-order-20260322065105','127.0.0.1','','2026-03-22 06:51:06',1774133466,1774133466),(633,'audit_20260322065106_5f6f5126',1,1,'陈总','business_approval','add','审批中心','approval_20260322065106_ba2e6ee1','发起审批：SP-20260322-61C140 / PO-20260322-7359E8 / smoke-payment-request-order-20260322065105','127.0.0.1','','2026-03-22 06:51:06',1774133466,1774133466),(634,'audit_20260322065106_e791ba69',1,1,'陈总','business_approval','approve_step','审批中心','approval_20260322065106_ba2e6ee1','审批流转：SP-20260322-61C140 / PO-20260322-7359E8 / smoke-payment-request-order-20260322065105 / 已进入第 2 级','127.0.0.1','','2026-03-22 06:51:06',1774133466,1774133466),(635,'audit_20260322065106_cd939d8f',1,1,'陈总','business_approval','approved','审批中心','approval_20260322065106_ba2e6ee1','审批通过：SP-20260322-61C140 / PO-20260322-7359E8 / smoke-payment-request-order-20260322065105','127.0.0.1','','2026-03-22 06:51:06',1774133466,1774133466),(636,'audit_20260322065106_2a162c53',1,1,'陈总','business_payment_plan','add','付款计划','payment_plan_20260322065106_1ddeac6b','由采购单生成付款计划：采购付款 / smoke-payment-request-order-20260322065105','127.0.0.1','','2026-03-22 06:51:06',1774133466,1774133466),(637,'audit_20260322065106_06911302',1,1,'陈总','business_purchase_order','create_payment_plan','采购单','purchase_order_20260322065106_2f58a085','为采购单生成付款计划：PO-20260322-7359E8 / smoke-payment-request-order-20260322065105','127.0.0.1','','2026-03-22 06:51:06',1774133466,1774133466),(638,'audit_20260322065107_d9886432',1,1,'陈总','business_purchase_reconciliation','add','采购对账','purchase_reconciliation_20260322065107_77a5204e','新增采购对账：PR-20260322-B124D2 / smoke-payment-request-reconciliation-20260322065105','127.0.0.1','','2026-03-22 06:51:07',1774133467,1774133467),(639,'audit_20260322065107_67366616',1,1,'陈总','business_purchase_settlement','add','采购结算','purchase_settlement_20260322065107_5b7985f9','新增采购结算：PS-20260322-3AC1E0 / smoke-payment-request-settlement-20260322065105','127.0.0.1','','2026-03-22 06:51:07',1774133467,1774133467),(640,'audit_20260322065107_23164f41',1,1,'陈总','business_purchase_invoice','add','采购发票','purchase_invoice_20260322065107_ed02437f','新增采购发票：PRI-SMOKE-20260322065105 / smoke-payment-request-invoice-20260322065105','127.0.0.1','','2026-03-22 06:51:07',1774133467,1774133467),(641,'audit_20260322065107_a66956ee',1,1,'陈总','business_payment_request','add','付款申请','payment_request_20260322065107_46c28a1e','新增付款申请：PR-20260322-281339 / smoke-payment-request-20260322065105','127.0.0.1','','2026-03-22 06:51:07',1774133467,1774133467),(642,'audit_20260322065107_1d20b4d0',1,1,'陈总','business_approval','add','审批中心','approval_20260322065107_8e832bf1','发起审批：SP-20260322-20E97C / PR-20260322-281339 / smoke-payment-request-20260322065105','127.0.0.1','','2026-03-22 06:51:07',1774133467,1774133467),(643,'audit_20260322065107_a46e6077',1,1,'陈总','business_approval','approve_step','审批中心','approval_20260322065107_8e832bf1','审批流转：SP-20260322-20E97C / PR-20260322-281339 / smoke-payment-request-20260322065105 / 已进入第 2 级','127.0.0.1','','2026-03-22 06:51:07',1774133467,1774133467),(644,'audit_20260322065108_1090f129',1,1,'陈总','business_approval','approved','审批中心','approval_20260322065107_8e832bf1','审批通过：SP-20260322-20E97C / PR-20260322-281339 / smoke-payment-request-20260322065105','127.0.0.1','','2026-03-22 06:51:08',1774133468,1774133468),(645,'audit_20260322065108_85e9bbe5',1,1,'陈总','business_payment_request','paid','付款申请','payment_request_20260322065107_46c28a1e','标记付款完成：PR-20260322-281339 / smoke-payment-request-20260322065105','127.0.0.1','','2026-03-22 06:51:08',1774133468,1774133468),(646,'audit_20260322065108_6841b27d',1,1,'陈总','business_payment_request','delete','付款申请','payment_request_20260322065107_46c28a1e','删除付款申请：PR-20260322-281339 / smoke-payment-request-20260322065105','127.0.0.1','','2026-03-22 06:51:08',1774133468,1774133468),(647,'audit_20260322065108_e174bf3f',1,1,'陈总','business_approval','delete','审批中心','approval_20260322065107_8e832bf1','删除审批：SP-20260322-20E97C / PR-20260322-281339 / smoke-payment-request-20260322065105','127.0.0.1','','2026-03-22 06:51:08',1774133468,1774133468),(648,'audit_20260322065108_f5d4f535',1,1,'陈总','business_purchase_invoice','delete','采购发票','purchase_invoice_20260322065107_ed02437f','删除采购发票：PRI-SMOKE-20260322065105 / smoke-payment-request-invoice-20260322065105','127.0.0.1','','2026-03-22 06:51:08',1774133468,1774133468),(649,'audit_20260322065108_c01c99ac',1,1,'陈总','business_purchase_settlement','delete','采购结算','purchase_settlement_20260322065107_5b7985f9','删除采购结算：PS-20260322-3AC1E0 / smoke-payment-request-settlement-20260322065105','127.0.0.1','','2026-03-22 06:51:08',1774133468,1774133468),(650,'audit_20260322065108_11b682b4',1,1,'陈总','business_purchase_reconciliation','delete','采购对账','purchase_reconciliation_20260322065107_77a5204e','删除采购对账：PR-20260322-B124D2 / smoke-payment-request-reconciliation-20260322065105','127.0.0.1','','2026-03-22 06:51:08',1774133468,1774133468),(651,'audit_20260322065108_653ce135',1,1,'陈总','business_payment_plan','delete','付款计划','payment_plan_20260322065106_1ddeac6b','删除付款计划：采购付款 / smoke-payment-request-order-20260322065105','127.0.0.1','','2026-03-22 06:51:08',1774133468,1774133468),(652,'audit_20260322065109_f8cd89ab',1,1,'陈总','business_approval','delete','审批中心','approval_20260322065106_ba2e6ee1','删除审批：SP-20260322-61C140 / PO-20260322-7359E8 / smoke-payment-request-order-20260322065105','127.0.0.1','','2026-03-22 06:51:09',1774133469,1774133469),(653,'audit_20260322065109_9ea652f1',1,1,'陈总','business_purchase_order','delete','采购单','purchase_order_20260322065106_2f58a085','删除采购单：PO-20260322-7359E8 / smoke-payment-request-order-20260322065105','127.0.0.1','','2026-03-22 06:51:09',1774133469,1774133469),(654,'audit_20260322065109_e0af01cf',1,1,'陈总','business_supplier','delete','供应商档案','supplier_20260322065106_7d276d4d','删除供应商：smoke-payment-request-supplier-20260322065105','127.0.0.1','','2026-03-22 06:51:09',1774133469,1774133469);
/*!40000 ALTER TABLE `fa_staff_audit` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_staff_profile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_staff_profile` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '旧系统ID',
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '后台账号',
  `account` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '登录账号',
  `employee_no` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '工号',
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '姓名',
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '岗位',
  `department` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '部门',
  `role_key` enum('admin','finance','project','operations','service','tech','viewer') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'viewer' COMMENT '角色组:admin=管理员,finance=财务,project=项目,operations=运营,service=客服,tech=技术,viewer=只读',
  `permissions_json` text COLLATE utf8mb4_unicode_ci COMMENT '附加权限JSON',
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '手机号',
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `hire_date` date DEFAULT NULL COMMENT '入职日期',
  `manager_legacy_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '直属上级旧ID',
  `manager_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '直属上级后台账号',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active' COMMENT '状态:active=在职,inactive=停用',
  `last_login_at` datetime DEFAULT NULL COMMENT '最近登录时间',
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT '备注',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_staff_profile_legacy_id` (`legacy_id`),
  UNIQUE KEY `uk_staff_profile_account` (`account`),
  KEY `idx_staff_profile_admin_id` (`admin_id`),
  KEY `idx_staff_profile_role_key` (`role_key`),
  KEY `idx_staff_profile_department` (`department`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='员工档案';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_staff_profile` WRITE;
/*!40000 ALTER TABLE `fa_staff_profile` DISABLE KEYS */;
INSERT INTO `fa_staff_profile` VALUES (1,'user-1001',1,'admin','A0001','陈总','系统管理员','管理层','admin','[]','13800000001','admin@yfsoft.local','2024-01-03','',0,'active','2026-03-20 20:17:36','',1774032311,1774032311),(2,'user-1002',2,'finance.li','F1001','李娜','财务主管','财务部','finance','[\"ai.manage\"]','13800000002','finance.li@yfsoft.local','2024-04-15','user-1001',1,'active','2026-03-20 05:32:05','',1774032311,1774032311),(3,'user-1003',3,'pm.zhang','P1001','张敏','项目经理','交付部','project','[]','13800000003','pm.zhang@yfsoft.local','2024-06-18','user-1001',1,'active',NULL,'',1774032311,1774032311),(4,'user-1004',4,'ops.gu','O1001','顾宁','运营负责人','运营部','operations','[]','13800000004','ops.gu@yfsoft.local','2024-08-12','user-1001',1,'active',NULL,'',1774032311,1774032311),(5,'user-1005',5,'product.wang','PD1001','王越','业务分析','产品部','viewer','[]','13800000005','product.wang@yfsoft.local','2024-09-01','user-1001',1,'active',NULL,'',1774032311,1774032311),(6,'user-1006',6,'tech.zhou','T1001','周柯','技术负责人','技术部','tech','[]','13800000006','tech.zhou@yfsoft.local','2024-05-09','user-1001',1,'active',NULL,'',1774032311,1774032311),(7,'user-1007',7,'service.liu','S1001','刘悦','客服主管','客服部','service','[]','13800000007','service.liu@yfsoft.local','2024-11-05','user-1001',1,'active','2026-03-20 05:34:28','',1774032311,1774032311),(8,'user-1008',8,'leader.he','M1001','何浩','经营负责人','经营管理部','operations','[]','13800000008','leader.he@yfsoft.local','2024-03-08','user-1001',1,'active','2026-03-19 19:26:27','',1774032311,1774032311);
/*!40000 ALTER TABLE `fa_staff_profile` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_test`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_test` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(10) DEFAULT '0' COMMENT '会员ID',
  `admin_id` int(10) DEFAULT '0' COMMENT '管理员ID',
  `category_id` int(10) unsigned DEFAULT '0' COMMENT '分类ID(单选)',
  `category_ids` varchar(100) DEFAULT NULL COMMENT '分类ID(多选)',
  `tags` varchar(255) DEFAULT '' COMMENT '标签',
  `week` enum('monday','tuesday','wednesday') DEFAULT NULL COMMENT '星期(单选):monday=星期一,tuesday=星期二,wednesday=星期三',
  `flag` set('hot','index','recommend') DEFAULT '' COMMENT '标志(多选):hot=热门,index=首页,recommend=推荐',
  `genderdata` enum('male','female') DEFAULT 'male' COMMENT '性别(单选):male=男,female=女',
  `hobbydata` set('music','reading','swimming') DEFAULT NULL COMMENT '爱好(多选):music=音乐,reading=读书,swimming=游泳',
  `title` varchar(100) DEFAULT '' COMMENT '标题',
  `content` text COMMENT '内容',
  `image` varchar(100) DEFAULT '' COMMENT '图片',
  `images` varchar(1500) DEFAULT '' COMMENT '图片组',
  `attachfile` varchar(100) DEFAULT '' COMMENT '附件',
  `keywords` varchar(255) DEFAULT '' COMMENT '关键字',
  `description` varchar(255) DEFAULT '' COMMENT '描述',
  `city` varchar(100) DEFAULT '' COMMENT '省市',
  `array` varchar(255) DEFAULT '' COMMENT '数组:value=值',
  `json` varchar(255) DEFAULT '' COMMENT '配置:key=名称,value=值',
  `multiplejson` varchar(1500) DEFAULT '' COMMENT '二维数组:title=标题,intro=介绍,author=作者,age=年龄',
  `price` decimal(10,2) unsigned DEFAULT '0.00' COMMENT '价格',
  `views` int(10) unsigned DEFAULT '0' COMMENT '点击',
  `workrange` varchar(100) DEFAULT '' COMMENT '时间区间',
  `startdate` date DEFAULT NULL COMMENT '开始日期',
  `activitytime` datetime DEFAULT NULL COMMENT '活动时间(datetime)',
  `year` year(4) DEFAULT NULL COMMENT '年',
  `times` time DEFAULT NULL COMMENT '时间',
  `refreshtime` bigint(16) DEFAULT NULL COMMENT '刷新时间',
  `createtime` bigint(16) DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint(16) DEFAULT NULL COMMENT '删除时间',
  `weigh` int(10) DEFAULT '0' COMMENT '权重',
  `switch` tinyint(1) DEFAULT '0' COMMENT '开关',
  `status` enum('normal','hidden') DEFAULT 'normal' COMMENT '状态',
  `state` enum('0','1','2') DEFAULT '1' COMMENT '状态值:0=禁用,1=正常,2=推荐',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='测试表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_test` WRITE;
/*!40000 ALTER TABLE `fa_test` DISABLE KEYS */;
INSERT INTO `fa_test` VALUES (1,1,1,12,'12,13','互联网,计算机','monday','hot,index','male','music,reading','我是一篇测试文章','<p>我是测试内容</p>','/assets/img/avatar.png','/assets/img/avatar.png,/assets/img/qrcode.png','/assets/img/avatar.png','关键字','我是一篇测试文章描述，内容过多时将自动隐藏','广西壮族自治区/百色市/平果县','[\"a\",\"b\"]','{\"a\":\"1\",\"b\":\"2\"}','[{\"title\":\"标题一\",\"intro\":\"介绍一\",\"author\":\"小明\",\"age\":\"21\"}]',0.00,0,'2020-10-01 00:00:00 - 2021-10-31 23:59:59','2017-07-10','2017-07-10 18:24:45',2017,'18:24:45',1491635035,1491635035,1491635035,NULL,0,1,'normal','1');
/*!40000 ALTER TABLE `fa_test` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '组别ID',
  `username` varchar(32) DEFAULT '' COMMENT '用户名',
  `nickname` varchar(50) DEFAULT '' COMMENT '昵称',
  `password` varchar(32) DEFAULT '' COMMENT '密码',
  `salt` varchar(30) DEFAULT '' COMMENT '密码盐',
  `email` varchar(100) DEFAULT '' COMMENT '电子邮箱',
  `mobile` varchar(11) DEFAULT '' COMMENT '手机号',
  `avatar` varchar(255) DEFAULT '' COMMENT '头像',
  `level` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '等级',
  `gender` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '性别',
  `birthday` date DEFAULT NULL COMMENT '生日',
  `bio` varchar(100) DEFAULT '' COMMENT '格言',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '余额',
  `score` int(10) NOT NULL DEFAULT '0' COMMENT '积分',
  `successions` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '连续登录天数',
  `maxsuccessions` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '最大连续登录天数',
  `prevtime` bigint(16) DEFAULT NULL COMMENT '上次登录时间',
  `logintime` bigint(16) DEFAULT NULL COMMENT '登录时间',
  `loginip` varchar(50) DEFAULT '' COMMENT '登录IP',
  `loginfailure` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '失败次数',
  `loginfailuretime` bigint(16) DEFAULT NULL COMMENT '最后登录失败时间',
  `joinip` varchar(50) DEFAULT '' COMMENT '加入IP',
  `jointime` bigint(16) DEFAULT NULL COMMENT '加入时间',
  `createtime` bigint(16) DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) DEFAULT NULL COMMENT '更新时间',
  `token` varchar(50) DEFAULT '' COMMENT 'Token',
  `status` varchar(30) DEFAULT '' COMMENT '状态',
  `verification` varchar(255) DEFAULT '' COMMENT '验证',
  PRIMARY KEY (`id`),
  KEY `username` (`username`),
  KEY `email` (`email`),
  KEY `mobile` (`mobile`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='会员表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_user` WRITE;
/*!40000 ALTER TABLE `fa_user` DISABLE KEYS */;
INSERT INTO `fa_user` VALUES (1,1,'admin','admin','a66078cb7a5ba25b6ed264b662f28823','d3b2c6','admin@example.com','13000000000','/assets/img/avatar.png',0,0,'2017-04-08','',0.00,0,1,1,1491635035,1491635035,'127.0.0.1',0,1491635035,'127.0.0.1',1491635035,0,1491635035,'','normal','');
/*!40000 ALTER TABLE `fa_user` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_user_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_user_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT '' COMMENT '组名',
  `rules` text COMMENT '权限节点',
  `createtime` bigint(16) DEFAULT NULL COMMENT '添加时间',
  `updatetime` bigint(16) DEFAULT NULL COMMENT '更新时间',
  `status` enum('normal','hidden') DEFAULT NULL COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='会员组表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_user_group` WRITE;
/*!40000 ALTER TABLE `fa_user_group` DISABLE KEYS */;
INSERT INTO `fa_user_group` VALUES (1,'默认组','1,2,3,4,5,6,7,8,9,10,11,12',1491635035,1491635035,'normal');
/*!40000 ALTER TABLE `fa_user_group` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_user_money_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_user_money_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '变更余额',
  `before` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '变更前余额',
  `after` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '变更后余额',
  `memo` varchar(255) DEFAULT '' COMMENT '备注',
  `createtime` bigint(16) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='会员余额变动表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_user_money_log` WRITE;
/*!40000 ALTER TABLE `fa_user_money_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `fa_user_money_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_user_rule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_user_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) DEFAULT NULL COMMENT '父ID',
  `name` varchar(50) DEFAULT NULL COMMENT '名称',
  `title` varchar(50) DEFAULT '' COMMENT '标题',
  `remark` varchar(100) DEFAULT NULL COMMENT '备注',
  `ismenu` tinyint(1) DEFAULT NULL COMMENT '是否菜单',
  `createtime` bigint(16) DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) DEFAULT NULL COMMENT '更新时间',
  `weigh` int(10) DEFAULT '0' COMMENT '权重',
  `status` enum('normal','hidden') DEFAULT NULL COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COMMENT='会员规则表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_user_rule` WRITE;
/*!40000 ALTER TABLE `fa_user_rule` DISABLE KEYS */;
INSERT INTO `fa_user_rule` VALUES (1,0,'index','Frontend','',1,1491635035,1491635035,1,'normal'),(2,0,'api','API Interface','',1,1491635035,1491635035,2,'normal'),(3,1,'user','User Module','',1,1491635035,1491635035,12,'normal'),(4,2,'user','User Module','',1,1491635035,1491635035,11,'normal'),(5,3,'index/user/login','Login','',0,1491635035,1491635035,5,'normal'),(6,3,'index/user/register','Register','',0,1491635035,1491635035,7,'normal'),(7,3,'index/user/index','User Center','',0,1491635035,1491635035,9,'normal'),(8,3,'index/user/profile','Profile','',0,1491635035,1491635035,4,'normal'),(9,4,'api/user/login','Login','',0,1491635035,1491635035,6,'normal'),(10,4,'api/user/register','Register','',0,1491635035,1491635035,8,'normal'),(11,4,'api/user/index','User Center','',0,1491635035,1491635035,10,'normal'),(12,4,'api/user/profile','Profile','',0,1491635035,1491635035,3,'normal');
/*!40000 ALTER TABLE `fa_user_rule` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_user_score_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_user_score_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `score` int(10) NOT NULL DEFAULT '0' COMMENT '变更积分',
  `before` int(10) NOT NULL DEFAULT '0' COMMENT '变更前积分',
  `after` int(10) NOT NULL DEFAULT '0' COMMENT '变更后积分',
  `memo` varchar(255) DEFAULT '' COMMENT '备注',
  `createtime` bigint(16) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='会员积分变动表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_user_score_log` WRITE;
/*!40000 ALTER TABLE `fa_user_score_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `fa_user_score_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_user_token`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_user_token` (
  `token` varchar(50) NOT NULL COMMENT 'Token',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `createtime` bigint(16) DEFAULT NULL COMMENT '创建时间',
  `expiretime` bigint(16) DEFAULT NULL COMMENT '过期时间',
  PRIMARY KEY (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='会员Token表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_user_token` WRITE;
/*!40000 ALTER TABLE `fa_user_token` DISABLE KEYS */;
/*!40000 ALTER TABLE `fa_user_token` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fa_version`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fa_version` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `oldversion` varchar(30) DEFAULT '' COMMENT '旧版本号',
  `newversion` varchar(30) DEFAULT '' COMMENT '新版本号',
  `packagesize` varchar(30) DEFAULT '' COMMENT '包大小',
  `content` varchar(500) DEFAULT '' COMMENT '升级内容',
  `downloadurl` varchar(255) DEFAULT '' COMMENT '下载地址',
  `enforce` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '强制更新',
  `createtime` bigint(16) DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) DEFAULT NULL COMMENT '更新时间',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重',
  `status` varchar(30) DEFAULT '' COMMENT '状态',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='版本表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fa_version` WRITE;
/*!40000 ALTER TABLE `fa_version` DISABLE KEYS */;
/*!40000 ALTER TABLE `fa_version` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

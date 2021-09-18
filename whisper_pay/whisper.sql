/*
 Navicat Premium Data Transfer

 Source Server         : 127.0.0.1
 Source Server Type    : MySQL
 Source Server Version : 50640
 Source Host           : 127.0.0.1
 Source Database       : whisper

 Target Server Type    : MySQL
 Target Server Version : 50640
 File Encoding         : utf-8

 Date: 09/14/2019 19:04:22 PM
*/

SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `ws_admins`
-- ----------------------------
DROP TABLE IF EXISTS `ws_admins`;
CREATE TABLE `ws_admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '密码',
  `last_login_ip` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '最后登录IP',
  `last_login_time` int(11) NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `status` int(1) NOT NULL DEFAULT '1' COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- ----------------------------
--  Records of `ws_admins`
-- ----------------------------
BEGIN;
INSERT INTO `ws_admins` VALUES ('1', 'admin', 'cbb2fc826b6cbb2305cca827529b739b', '127.0.0.1', '1567843077', '1'), ('2', '小白', 'cb78913de44f5a36ab63e8ffacde44b0', '', '0', '1');
COMMIT;

-- ----------------------------
--  Table structure for `ws_black_list`
-- ----------------------------
DROP TABLE IF EXISTS `ws_black_list`;
CREATE TABLE `ws_black_list` (
  `black_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(55) DEFAULT NULL COMMENT '访客名称',
  `customer_ip` varchar(15) DEFAULT NULL COMMENT '访客ip',
  `add_time` datetime DEFAULT NULL COMMENT '添加时间',
  PRIMARY KEY (`black_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `ws_chat_log`
-- ----------------------------
DROP TABLE IF EXISTS `ws_chat_log`;
CREATE TABLE `ws_chat_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_id` varchar(55) NOT NULL COMMENT '网页用户随机编号(仅为记录参考记录)',
  `from_name` varchar(255) NOT NULL COMMENT '发送者名称',
  `from_avatar` varchar(255) NOT NULL COMMENT '发送者头像',
  `to_id` varchar(55) NOT NULL COMMENT '接收方',
  `to_name` varchar(255) NOT NULL COMMENT '接受者名称',
  `content` text NOT NULL COMMENT '发送的内容',
  `time_line` int(10) NOT NULL COMMENT '记录时间',
  PRIMARY KEY (`id`),
  KEY `fromid` (`from_id`(4)) USING BTREE,
  KEY `toid` (`to_id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `ws_customer`
-- ----------------------------
DROP TABLE IF EXISTS `ws_customer`;
CREATE TABLE `ws_customer` (
  `cid` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` varchar(200) NOT NULL COMMENT '访客id',
  `customer_name` varchar(255) NOT NULL COMMENT '访客名称',
  `avatar` varchar(1024) NOT NULL COMMENT '头像',
  `ip` varchar(255) NOT NULL COMMENT '访客ip',
  `group_id` int(11) NOT NULL COMMENT '访问的群组id',
  `client_id` varchar(20) NOT NULL COMMENT '客户端标识',
  `add_time` datetime NOT NULL COMMENT '访问时间',
  `online` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 在线 2 离线',
  PRIMARY KEY (`cid`),
  UNIQUE KEY `id` (`customer_id`) USING BTREE,
  KEY `visiter` (`customer_id`) USING BTREE,
  KEY `time` (`add_time`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `ws_groups`
-- ----------------------------
DROP TABLE IF EXISTS `ws_groups`;
CREATE TABLE `ws_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '分组id',
  `name` varchar(255) NOT NULL COMMENT '分组名称',
  `status` tinyint(1) NOT NULL COMMENT '分组状态',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Records of `ws_groups`
-- ----------------------------
BEGIN;
INSERT INTO `ws_groups` VALUES ('1', '售前组', '1'), ('2', '售后组', '1');
COMMIT;

-- ----------------------------
--  Table structure for `ws_kf_config`
-- ----------------------------
DROP TABLE IF EXISTS `ws_kf_config`;
CREATE TABLE `ws_kf_config` (
  `id` int(11) NOT NULL,
  `max_service` int(11) NOT NULL COMMENT '每个客服最大服务的客户数',
  `change_status` tinyint(1) NOT NULL COMMENT '是否启用转接',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
--  Records of `ws_kf_config`
-- ----------------------------
BEGIN;
INSERT INTO `ws_kf_config` VALUES ('1', '10', '1');
COMMIT;

-- ----------------------------
--  Table structure for `ws_leave_msg`
-- ----------------------------
DROP TABLE IF EXISTS `ws_leave_msg`;
CREATE TABLE `ws_leave_msg` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(155) NOT NULL COMMENT '留言人名称',
  `phone` char(11) NOT NULL COMMENT '留言人手机号',
  `content` varchar(255) NOT NULL COMMENT '留言内容',
  `add_time` int(10) NOT NULL COMMENT '留言时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Records of `ws_leave_msg`
-- ----------------------------
BEGIN;
INSERT INTO `ws_leave_msg` VALUES ('3', '白云飞', '15695212893', '不错啊', '1538379331');
COMMIT;

-- ----------------------------
--  Table structure for `ws_notice`
-- ----------------------------
DROP TABLE IF EXISTS `ws_notice`;
CREATE TABLE `ws_notice` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Records of `ws_notice`
-- ----------------------------
BEGIN;
INSERT INTO `ws_notice` VALUES ('1', 'whisper v1.0.4 更新日志', '<p><span style=\"color: rgba(0, 0, 0, 0.87); font-family: &quot;Microsoft Yahei&quot;, &quot;Helvetica Neue&quot;, Arial, Helvetica, sans-serif; font-size: 15.96px; background-color: rgb(255, 255, 255);\">1、增加客服工作台手机版本</span><br/><span style=\"color: rgba(0, 0, 0, 0.87); font-family: &quot;Microsoft Yahei&quot;, &quot;Helvetica Neue&quot;, Arial, Helvetica, sans-serif; font-size: 15.96px; background-color: rgb(255, 255, 255);\">2、更新 globaldata 组件到 1.0.3 解决连接失败的问题</span></p>'), ('2', 'whisper v1.1.0 更新日志', '<p><span style=\"color: rgba(0, 0, 0, 0.87); font-family: &quot;Microsoft Yahei&quot;, &quot;Helvetica Neue&quot;, Arial, Helvetica, sans-serif; font-size: 15.96px; background-color: rgb(255, 255, 255);\">1、重写单进程 服务端，去除 globaldata 的使用。</span><br/><span style=\"color: rgba(0, 0, 0, 0.87); font-family: &quot;Microsoft Yahei&quot;, &quot;Helvetica Neue&quot;, Arial, Helvetica, sans-serif; font-size: 15.96px; background-color: rgb(255, 255, 255);\">2、更换了更美观的测试连接网页</span><br/><span style=\"color: rgba(0, 0, 0, 0.87); font-family: &quot;Microsoft Yahei&quot;, &quot;Helvetica Neue&quot;, Arial, Helvetica, sans-serif; font-size: 15.96px; background-color: rgb(255, 255, 255);\">3、修复客服点击下班没有维护 连接用户的 结束时间问题</span></p><p><span style=\"color: rgb(119, 119, 119); font-family: &quot;Microsoft Yahei&quot;, &quot;Helvetica Neue&quot;, Arial, Helvetica, sans-serif; font-size: 15.96px; background-color: rgb(255, 255, 255);\"><br/></span></p><p><span style=\"color: rgb(119, 119, 119); font-family: &quot;Microsoft Yahei&quot;, &quot;Helvetica Neue&quot;, Arial, Helvetica, sans-serif; font-size: 15.96px; background-color: rgb(255, 255, 255);\"><br/></span></p>');
COMMIT;

-- ----------------------------
--  Table structure for `ws_now_data`
-- ----------------------------
DROP TABLE IF EXISTS `ws_now_data`;
CREATE TABLE `ws_now_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `is_talking` int(5) NOT NULL DEFAULT '0' COMMENT '正在咨询的人数',
  `in_queue` int(5) NOT NULL DEFAULT '0' COMMENT '排队等待的人数',
  `online_kf` int(5) NOT NULL COMMENT '在线客服数',
  `success_in` int(5) NOT NULL COMMENT '成功接入用户',
  `total_in` int(5) NOT NULL COMMENT '今日累积接入的用户',
  `now_date` varchar(10) NOT NULL COMMENT '当前日期',
  PRIMARY KEY (`id`),
  KEY `now_date` (`now_date`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Records of `ws_now_data`
-- ----------------------------
BEGIN;
INSERT INTO `ws_now_data` VALUES ('1', '0', '0', '1', '7', '7', '2019-09-07');
COMMIT;

-- ----------------------------
--  Table structure for `ws_praise`
-- ----------------------------
DROP TABLE IF EXISTS `ws_praise`;
CREATE TABLE `ws_praise` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '用户id',
  `kf_id` int(11) NOT NULL COMMENT '客服id',
  `uuid` varchar(20) NOT NULL COMMENT '本次会话标识',
  `star` int(2) NOT NULL DEFAULT '0' COMMENT '分数',
  `add_time` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `ws_questions`
-- ----------------------------
DROP TABLE IF EXISTS `ws_questions`;
CREATE TABLE `ws_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Records of `ws_questions`
-- ----------------------------
BEGIN;
INSERT INTO `ws_questions` VALUES ('1', '现在的最新版本是哪个？', '<p>现在的最新版本是 v1.2.0</p>'), ('2', '如何获取授权版本？', '<p>联系qq：876337011。支付宝或者微信转账即可。</p>'), ('3', '商业授权版本的价格是多少？', '<p>目前商业授权版本的 价格是 ￥1499&nbsp;</p>'), ('4', '授权版本有哪些优势？', '<ul class=\" list-paddingleft-2\" style=\"list-style-type: disc;\"><li><p>授权可以拿到最新的 客服源码（免费的代码目前不更新了）</p></li><li><p>授权可以获得二开指导</p></li><li><p>授权是终身的，授权之后可一直获取后续的更新代码</p></li></ul>'), ('5', '如何搭建whisper客服系统？', '<p>具体的搭建步骤，请参考 http://doc.baiyf.com</p>');
COMMIT;

-- ----------------------------
--  Table structure for `ws_reply`
-- ----------------------------
DROP TABLE IF EXISTS `ws_reply`;
CREATE TABLE `ws_reply` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `word` varchar(255) NOT NULL COMMENT '自动回复的内容',
  `status` tinyint(1) NOT NULL DEFAULT '2' COMMENT '是否自动回复',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Records of `ws_reply`
-- ----------------------------
BEGIN;
INSERT INTO `ws_reply` VALUES ('1', '欢迎使用whisper', '2');
COMMIT;

-- ----------------------------
--  Table structure for `ws_service_data`
-- ----------------------------
DROP TABLE IF EXISTS `ws_service_data`;
CREATE TABLE `ws_service_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `is_talking` int(5) NOT NULL DEFAULT '0' COMMENT '正在咨询的人数',
  `in_queue` int(5) NOT NULL DEFAULT '0' COMMENT '排队等待的人数',
  `online_kf` int(5) NOT NULL COMMENT '在线客服数',
  `success_in` int(5) NOT NULL COMMENT '成功接入用户',
  `total_in` int(5) NOT NULL COMMENT '今日累积接入的用户',
  `add_date` varchar(10) NOT NULL COMMENT '写入的日期',
  `add_hour` varchar(2) NOT NULL COMMENT '写入的小时数',
  `add_minute` varchar(2) NOT NULL COMMENT '写入的分钟数',
  PRIMARY KEY (`id`),
  KEY `add_date,add_hour` (`add_date`,`add_hour`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `ws_service_log`
-- ----------------------------
DROP TABLE IF EXISTS `ws_service_log`;
CREATE TABLE `ws_service_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(55) NOT NULL COMMENT '会员的id',
  `client_id` varchar(20) NOT NULL COMMENT '会员的客户端标识',
  `user_name` varchar(255) NOT NULL COMMENT '会员名称',
  `user_avatar` varchar(155) NOT NULL COMMENT '会员头像',
  `user_ip` varchar(15) NOT NULL COMMENT '会员的ip',
  `kf_id` int(11) DEFAULT '0' COMMENT '服务的客服id',
  `start_time` datetime NOT NULL COMMENT '开始服务时间',
  `end_time` datetime DEFAULT '0000-00-00 00:00:00' COMMENT '结束服务时间',
  `group_id` int(11) NOT NULL COMMENT '服务的客服的分组id',
  `can_show` tinyint(1) NOT NULL DEFAULT '0' COMMENT '离线之后是否可以显示 0 显示 1不显示',
  `protocol` varchar(5) NOT NULL DEFAULT 'ws' COMMENT '来自什么类型的连接',
  PRIMARY KEY (`log_id`),
  KEY `user_id,client_id` (`user_id`,`client_id`) USING BTREE,
  KEY `kf_id,start_time,end_time` (`kf_id`,`start_time`,`end_time`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `ws_service_queue`
-- ----------------------------
DROP TABLE IF EXISTS `ws_service_queue`;
CREATE TABLE `ws_service_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kf_id` int(11) NOT NULL DEFAULT '0' COMMENT '客服id',
  `kf_name` varchar(255) NOT NULL COMMENT '客服名称',
  `customer_id` varchar(200) NOT NULL COMMENT '客户id',
  `group_id` int(11) NOT NULL COMMENT '分组id',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 在排队 2 在服务',
  PRIMARY KEY (`id`),
  KEY `kf_service_num` (`kf_id`,`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `ws_total_in`
-- ----------------------------
DROP TABLE IF EXISTS `ws_total_in`;
CREATE TABLE `ws_total_in` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `now_day` date NOT NULL COMMENT '日期',
  `total_in` int(11) DEFAULT '0' COMMENT '总计接入量',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `ws_users`
-- ----------------------------
DROP TABLE IF EXISTS `ws_users`;
CREATE TABLE `ws_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '客服id',
  `user_name` varchar(255) NOT NULL COMMENT '客服名称',
  `user_pwd` varchar(32) NOT NULL COMMENT '客服登录密码',
  `user_avatar` varchar(255) NOT NULL COMMENT '客服头像',
  `status` tinyint(1) NOT NULL COMMENT '用户状态',
  `online` tinyint(1) NOT NULL DEFAULT '2' COMMENT '1 在线 2 离线',
  `group_id` int(11) NOT NULL DEFAULT '0' COMMENT '所属分组id',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Records of `ws_users`
-- ----------------------------
BEGIN;
INSERT INTO `ws_users` VALUES ('1', '客服小白', 'cbb2fc826b6cbb2305cca827529b739b', '/static/admin/images/profile_small.jpg', '1', '1', '1'), ('2', '客服小美', '61fcb6b65f1d3da179b5b4cf397eda86', '/static/admin/images/profile_small.jpg', '1', '2', '2');
COMMIT;

-- ----------------------------
--  Table structure for `ws_words`
-- ----------------------------
DROP TABLE IF EXISTS `ws_words`;
CREATE TABLE `ws_words` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content` varchar(255) NOT NULL COMMENT '常用语内容',
  `add_time` datetime NOT NULL COMMENT '添加时间',
  `status` tinyint(1) NOT NULL COMMENT '是否启用',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Records of `ws_words`
-- ----------------------------
BEGIN;
INSERT INTO `ws_words` VALUES ('1', '欢迎来到whisper v1.0.0', '2017-10-25 12:51:09', '1');
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;

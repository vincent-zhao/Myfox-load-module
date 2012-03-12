
--DROP DATABASE IF EXISTS meta_myfox_config;
--CREATE DATABASE meta_myfox_config;

USE meta_myfox_config;

-- 系统状态表
DROP TABLE IF EXISTS dev_settings;
CREATE TABLE IF NOT EXISTS dev_settings (
	autokid int(10) unsigned not null auto_increment,
	cfgname varchar(32) not null default '',
	ownname varchar(32) not null default '',
	cfgvalue varchar(255) not null default '',
	addtime datetime not null default '0000-00-00 00:00:00',
	modtime datetime not null default '0000-00-00 00:00:00',
	PRIMARY KEY pk_setting_id (autokid),
	UNIQUE KEY uk_setting_name (ownname,cfgname)
) ENGINE = MyISAM DEFAULT CHARSET=UTF8;

-- 机器表
DROP TABLE IF EXISTS dev_host_list;
CREATE TABLE dev_host_list (
	host_id int(10) unsigned not null auto_increment,
	host_type tinyint(2) unsigned not null default 0,
	host_stat tinyint(2) unsigned not null default 0,
	host_pos int(10) unsigned not null default 0,
	host_name char(16) not null default '',
	addtime datetime not null default '0000-00-00 00:00:00',
	modtime datetime not null default '0000-00-00 00:00:00',
	conn_host varchar(64) not null default '',
	conn_port smallint(5) unsigned not null default 0,
	read_user varchar(64) not null default '',
	read_pass varchar(64) not null default '',
	write_user varchar(64) not null default '',
	write_pass varchar(64) not null default '',
	PRIMARY KEY pk_host_id (host_id),
	UNIQUE KEY uk_host_name (host_name),
	KEY idx_host_stat (host_stat, host_type),
	KEY idx_host_pos (host_pos)
) ENGINE = MyISAM DEFAULT CHARSET=UTF8;

INSERT INTO dev_host_list VALUES (1,1,0,0,'host1',NOW(),NOW(),'192.168.1.1',3306,'db_read','123456','db_write','123456');
INSERT INTO dev_host_list VALUES (2,1,0,0,'host2',NOW(),NOW(),'192.168.1.2',3306,'db_read','123456','db_write','123456');

UPDATE dev_host_list SET host_pos = INET_ATON(conn_host);

-- 配置表
DROP TABLE IF EXISTS dev_table_list;
CREATE TABLE dev_table_list (
	autokid int(10) unsigned not null auto_increment,
	addtime datetime not null default '0000-00-00 00:00:00',
	modtime datetime not null default '0000-00-00 00:00:00',
	backups tinyint(2) unsigned not null default 1,
	max_index_num tinyint(2) unsigned not null default 0,
	split_threshold int(10) unsigned not null default 0,
	split_drift decimal(5,2) unsigned not null default 0.00,
	load_type tinyint(2) unsigned not null default 0,
	route_type tinyint(2) unsigned not null default 0,
	table_name varchar(64) not null default '',
	table_desc varchar(128) not null default '',
	unique_key varchar(256) not null default '',
	table_sign varchar(32) not null default '',
	sql_import text not null default '',
	PRIMARY KEY pk_table_id (autokid),
	UNIQUE KEY uk_table_name (table_name)
) ENGINE = InnoDB DEFAULT CHARSET=UTF8;

INSERT INTO dev_table_list VALUES (1,NOW(),NOW(),2,5,1000,'0.20',1,0,'mirror_v2','测试镜像表','','','');
INSERT INTO dev_table_list VALUES (2,NOW(),NOW(),2,5,1000,'0.20',0,1,'numsplit_v2','测试切分表','thedate,cid','','');

-- 路由字段表
DROP TABLE IF EXISTS dev_table_route;
CREATE TABLE dev_table_route (
	autokid int(10) unsigned not null auto_increment,
	addtime datetime not null default '0000-00-00 00:00:00',
	modtime datetime not null default '0000-00-00 00:00:00',
	table_name varchar(64) not null default '',
	column_name varchar(64) not null default '',
	tidy_method varchar(64) not null default '',
	tidy_return varchar(20) not null default 'int',
	is_primary tinyint(2) unsigned not null default 0,
	PRIMARY KEY pk_auto_kid (autokid),
	UNIQUE KEY uk_table_column (table_name, column_name)
) ENGINE = InnoDB DEFAULT CHARSET=UTF8;

INSERT INTO dev_table_route VALUES (1,NOW(),NOW(),'numsplit_v2','thedate','','date',1);
INSERT INTO dev_table_route VALUES (2,NOW(),NOW(),'numsplit_v2','cid','','int',1);

-- 表字段配置表
DROP TABLE IF EXISTS dev_table_column;
CREATE TABLE dev_table_column (
	autokid int(10) unsigned not null auto_increment,
	column_order smallint(5) unsigned not null default 0,
	addtime int(10) unsigned not null default 0,
	modtime int(10) unsigned not null default 0,
	table_name varchar(64) not null default '',
	column_name varchar(64) not null default '',
	column_type varchar(64) not null default '',
	column_size varchar(64) not null default '',
	default_value varchar(64) not null default '',
	column_desc varchar(256) not null default '',
	PRIMARY KEY pk_column_id (autokid),
	UNIQUE KEY uk_column_name (table_name,column_name),
	KEY idx_column_order (table_name(10), column_order)
) ENGINE = MyISAM DEFAULT CHARSET=UTF8;
INSERT INTO dev_table_column VALUES (1,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'mirror_v2','cid','uint','10','0','类目ID');
INSERT INTO dev_table_column VALUES (2,2,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'mirror_v2','cname','char','255','','类目名字');
INSERT INTO dev_table_column VALUES (3,100,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'mirror_v2','autokid','autokid','10','0','自增键');
INSERT INTO dev_table_column VALUES (4,5,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'numsplit_v2','char1','char','32','','');
INSERT INTO dev_table_column VALUES (5,2,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'numsplit_v2','cid','uint','10','0','');
INSERT INTO dev_table_column VALUES (6,3,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'numsplit_v2','num1','uint','10','0','');
INSERT INTO dev_table_column VALUES (7,4,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'numsplit_v2','num2','float','20,14','0.00','');
INSERT INTO dev_table_column VALUES (8,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'numsplit_v2','thedate','date','','0000-00-00','');
INSERT INTO dev_table_column VALUES (9,100,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'numsplit_v2','autokid','autokid','10','0','');

DROP TABLE IF EXISTS dev_table_index;
CREATE TABLE dev_table_index (
	autokid int(10) unsigned not null auto_increment,
	addtime int(10) unsigned not null default 0,
	modtime int(10) unsigned not null default 0,
	create_type tinyint(2) unsigned not null default 0,
	table_name varchar(64) not null default '',
	index_name varchar(64) not null default '',
	index_text varchar(1024) not null default '',
	PRIMARY KEY pk_index_id (autokid),
	UNIQUE KEY uk_table_index (table_name, index_name)
) ENGINE = MyISAM DEFAULT CHARSET=UTF8;

INSERT INTO dev_table_index VALUES (1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),0,'numsplit_v2','idx_cid','cid');

-- 任务队列
DROP TABLE IF EXISTS dev_task_queque;
CREATE TABLE IF NOT EXISTS dev_task_queque (
	autokid bigint(20) unsigned not null auto_increment,
	agentpos int(10) not null default 0,
	openrace tinyint(1) unsigned not null default 1,
	priority smallint(5) unsigned not null default 0,
	trytimes smallint(5) unsigned not null default 0,
	addtime datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	begtime datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	endtime datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	task_flag smallint(5) unsigned not null default 0,
	task_type varchar(100) not null default '',
	adduser varchar(100) not null default '',
	last_error varchar(200) not null default '',
	tmp_status varchar(1000) not null default '',
	task_info text,
	PRIMARY KEY pk_queque_id (autokid),
	KEY idx_queque_flag (task_flag, trytimes, priority, agentpos),
	KEY idx_queque_time (addtime, task_flag)
) ENGINE = MyISAM DEFAULT CHARSET=UTF8;

-- 路由表
DROP TABLE IF EXISTS dev_route_info_0,dev_route_info_1,dev_route_info_2,dev_route_info_3;
DROP TABLE IF EXISTS dev_route_info_4,dev_route_info_5,dev_route_info_6,dev_route_info_7;
DROP TABLE IF EXISTS dev_route_info_8,dev_route_info_9,dev_route_info_a,dev_route_info_b;
DROP TABLE IF EXISTS dev_route_info_c,dev_route_info_d,dev_route_info_e,dev_route_info_f;
CREATE TABLE dev_route_info_0 (
	autokid int(10) unsigned not null auto_increment,
	addtime int(10) unsigned not null default 0,
	modtime int(10) unsigned not null default 0,
	hittime int(10) unsigned not null default 0,
	route_sign int(10) unsigned not null default 0,
	is_archive tinyint(2) unsigned not null default 0,
	route_flag smallint(5) unsigned not null default 0,
	table_name varchar(64) not null default '',
	real_table varchar(128) not null default '',
	hosts_list varchar(1024) not null default '',
	route_text varchar(1024) not null default '',
	unique_key varchar(1024) not null default '',
	PRIMARY KEY pk_route_id (autokid),
	KEY idx_route_sign (route_sign, route_flag),
	KEY idx_route_time (modtime, is_archive)
) ENGINE = MyISAM DEFAULT CHARSET=UTF8;

CREATE TABLE dev_route_info_1 LIKE dev_route_info_0;
CREATE TABLE dev_route_info_2 LIKE dev_route_info_0;
CREATE TABLE dev_route_info_3 LIKE dev_route_info_0;
CREATE TABLE dev_route_info_4 LIKE dev_route_info_0;
CREATE TABLE dev_route_info_5 LIKE dev_route_info_0;
CREATE TABLE dev_route_info_6 LIKE dev_route_info_0;
CREATE TABLE dev_route_info_7 LIKE dev_route_info_0;
CREATE TABLE dev_route_info_8 LIKE dev_route_info_0;
CREATE TABLE dev_route_info_9 LIKE dev_route_info_0;
CREATE TABLE dev_route_info_a LIKE dev_route_info_0;
CREATE TABLE dev_route_info_b LIKE dev_route_info_0;
CREATE TABLE dev_route_info_c LIKE dev_route_info_0;
CREATE TABLE dev_route_info_d LIKE dev_route_info_0;
CREATE TABLE dev_route_info_e LIKE dev_route_info_0;
CREATE TABLE dev_route_info_f LIKE dev_route_info_0;


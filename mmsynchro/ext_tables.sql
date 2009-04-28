#
# Table structure for table 'tx_mmsynchro_tables'
#
CREATE TABLE tx_mmsynchro_tables (
	uid int(11) NOT NULL auto_increment,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	config int(11) DEFAULT '0' NOT NULL,
	synchro_table tinytext NOT NULL,
	
	PRIMARY KEY (uid)
);

#
# Table structure for table 'tx_mmsynchro_pages'
#
CREATE TABLE tx_mmsynchro_pages (
	uid int(11) NOT NULL auto_increment,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	config int(11) DEFAULT '0' NOT NULL,
	synchro_pages tinytext NOT NULL,
	
	PRIMARY KEY (uid)
);

#
# Table structure for table 'tx_mmsynchro_files'
#
CREATE TABLE tx_mmsynchro_files (
	uid int(11) NOT NULL auto_increment,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	config int(11) DEFAULT '0' NOT NULL,
	synchro_files tinytext NOT NULL,
	
	PRIMARY KEY (uid)
);

#
# Table structure for table 'tx_mmsynchro_config'
#
CREATE TABLE tx_mmsynchro_config (
	uid int(11) NOT NULL auto_increment,
	pid int(11) default '0' NOT NULL,
	deleted tinyint(4) unsigned default '0',
	path_target tinytext NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	title tinytext NOT NULL,
	is_db tinyint(3) DEFAULT '0' NOT NULL,
	dbUsername tinytext,
	dbPassword tinytext,
	dbHost tinytext,
	dbDatabase tinytext,
	logPath tinytext,
	is_remote tinyint(3) DEFAULT '0' NOT NULL,
	remoteUsername tinytext,
	remotePassword tinytext,
	remoteHost tinytext,
	remotePort int(11) DEFAULT '22' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);
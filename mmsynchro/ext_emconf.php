<?php

########################################################################
# Extension Manager/Repository config file for ext: "mmsynchro"
#
# Auto generated 09-09-2008 12:01
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Synchronisation',
	'description' => '',
	'category' => 'module',
	'author' => 'Timo Webler',
	'author_email' => 'timo.webler@mediaman.de',
	'shy' => '',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'module' => 'mod1,mod2,mod3,cm1',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => 'L',
	'author_company' => 'mediaman Gesellschaft für Kommunikation mbH',
	'version' => '1.5.2',
	'constraints' => array(
		'depends' => array(
			'php' => '5.2.6-0.0.0',
			'typo3' => '4.2.1-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:31:{s:9:"ChangeLog";s:4:"9f2d";s:10:"README.txt";s:4:"9fa9";s:12:"ext_icon.gif";s:4:"8210";s:17:"ext_localconf.php";s:4:"ccb8";s:14:"ext_tables.php";s:4:"b3d7";s:14:"ext_tables.sql";s:4:"888b";s:7:"tca.php";s:4:"d251";s:30:"cli/class.tx_mmsynchro_cli.php";s:4:"b298";s:14:"doc/manual.sxw";s:4:"07cc";s:19:"doc/wizard_form.dat";s:4:"0846";s:20:"doc/wizard_form.html";s:4:"4ea3";s:16:"images/Thumbs.db";s:4:"80ae";s:26:"images/img_0003_mmlogo.gif";s:4:"9052";s:25:"images/img_mm_logo_02.gif";s:4:"f4bd";s:18:"Lang/locallang.xml";s:4:"6d50";s:25:"Lang/locallang_csh_db.xml";s:4:"294d";s:21:"Lang/locallang_db.xml";s:4:"e3f7";s:23:"Lang/locallang_mod1.xml";s:4:"ee2d";s:36:"lib/class.tx_mmsynchro_connectDB.php";s:4:"9392";s:32:"lib/class.tx_mmsynchro_crypt.php";s:4:"638e";s:40:"lib/class.tx_mmsynchro_dbalConnectDB.php";s:4:"2919";s:40:"lib/class.tx_mmsynchro_localPageTree.php";s:4:"176f";s:38:"lib/class.tx_mmsynchro_progressBar.php";s:4:"334c";s:33:"lib/class.tx_mmsynchro_remote.php";s:4:"10cb";s:34:"lib/class.tx_mmsynchro_synchro.php";s:4:"19e3";s:40:"lib/class.tx_mmsynchro_tcemainprocdm.php";s:4:"1bde";s:39:"lib/class.tx_mmsynchro_validatePath.php";s:4:"0a4d";s:14:"mod1/clear.gif";s:4:"cc11";s:13:"mod1/conf.php";s:4:"c5b5";s:14:"mod1/index.php";s:4:"16ae";s:19:"mod1/moduleicon.gif";s:4:"8074";}',
	'suggests' => array(
	),
);

?>
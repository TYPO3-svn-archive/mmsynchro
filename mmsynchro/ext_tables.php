<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE == 'BE')	{
	
	// CSH
	t3lib_extMgm::addLLrefForTCAdescr('tx_mmsynchro_config','EXT:mmsynchro/Lang/locallang_csh_db.xml');
	
	// Clickmen�
	$GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][]=array(
        'name' => 'tx_mmsynchro_cm1',
        'path' => t3lib_extMgm::extPath($_EXTKEY).'lib/class.tx_mmsynchro_cm1.php'
    );
		
	// Add modul
	t3lib_extMgm::addModule('txmmsynchroM2','','',t3lib_extMgm::extPath($_EXTKEY).'mod2/');
	t3lib_extMgm::addModule('txmmsynchroM2','txmmsynchroM1','',t3lib_extMgm::extPath($_EXTKEY).'mod1/');
	t3lib_extMgm::addModule('txmmsynchroM2','txmmsynchroM3','',t3lib_extMgm::extPath($_EXTKEY).'mod3/');
}

$TCA['tx_mmsynchro_config'] = Array(
	'ctrl' => Array(
		'title' => 'LLL:EXT:mmsynchro/Lang/locallang_db.xml:tx_mmsynchro_config',
		'label' => 'title',
		'default_sortby' => 'ORDER BY uid',
		'tstamp' => 'tstamp',
		'delete' => 'deleted',
		'crdate' => 'crdate',
		'rootLevel' => 1,
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'ext_icon.gif',
		'hideTable' => '1',
		'dividers2tabs' => '1',
		'requestUpdate' => 'is_remote,is_db',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php'
	)
);
?>
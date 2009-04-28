<?php

$TCA['tx_mmsynchro_config'] = Array (
	'ctrl' => $TCA['tx_mmsynchro_config']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'title,logPath,path_target,dbUsername,dbPassword,dbHost,dbDatabase',
		'always_description'  => true
		
	),
	'columns' => Array (
		'title' => Array(
			'label' => 'LLL:EXT:mmsynchro/Lang/locallang_db.xml:tx_mmsynchro_config.title',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '50',
				'eval' => 'required,trim,alphanum'
			)
		),
		'logPath' => Array(
			'label' => 'LLL:EXT:mmsynchro/Lang/locallang_db.xml:tx_mmsynchro_config.logPath',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '255',
				'eval' => 'required,trim,tx_mmsynchro_validatePath'
			)
		),
		'path_target' => Array(
			'label' => 'LLL:EXT:mmsynchro/Lang/locallang_db.xml:tx_mmsynchro_config.path_target',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '255',
				'eval' => 'required,trim,tx_mmsynchro_validatePath'
			)
		),
		'is_db' => Array (         
            'label' => 'LLL:EXT:mmsynchro/Lang/locallang_db.xml:tx_mmsynchro_config.is_db',        
            'config' => Array (
                'type' => 'check',
            )
        ),
		'dbUsername' => Array(
			'label' => 'LLL:EXT:mmsynchro/Lang/locallang_db.xml:tx_mmsynchro_config.dbUsername',
			'displayCond' => 'FIELD:is_db:REQ:true',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '50',
				'eval' => 'required,trim'
			)
		),
		'dbPassword' => Array(
			'label' => 'LLL:EXT:mmsynchro/Lang/locallang_db.xml:tx_mmsynchro_config.dbPassword',
			'displayCond' => 'FIELD:is_db:REQ:true',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '150',
				'eval' => 'password'
			)
		),
		'dbHost' => Array(
			'label' => 'LLL:EXT:mmsynchro/Lang/locallang_db.xml:tx_mmsynchro_config.dbHost',
			'displayCond' => 'FIELD:is_db:REQ:true',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '50',
				'default' => 'localhost',
				'eval' => 'required,trim'
			)
		),
		'dbDatabase' => Array(
			'label' => 'LLL:EXT:mmsynchro/Lang/locallang_db.xml:tx_mmsynchro_config.dbDatabase',
			'displayCond' => 'FIELD:is_db:REQ:true',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '50',
				'eval' => 'required,trim'
			)
		),
		'is_remote' => Array (         
            'label' => 'LLL:EXT:mmsynchro/Lang/locallang_db.xml:tx_mmsynchro_config.is_remote',        
            'config' => Array (
                'type' => 'check',
            )
        ),
        'remoteUsername' => Array(
			'label' => 'LLL:EXT:mmsynchro/Lang/locallang_db.xml:tx_mmsynchro_config.remoteUsername',
			'displayCond' => 'FIELD:is_remote:REQ:true',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '50',
				'eval' => 'required,trim'
			)
		),
		'remotePassword' => Array(
			'label' => 'LLL:EXT:mmsynchro/Lang/locallang_db.xml:tx_mmsynchro_config.remotePassword',
			'displayCond' => 'FIELD:is_remote:REQ:true',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '150',
				'eval' => 'required,password'
			)
		),
		'remoteHost' => Array(
			'label' => 'LLL:EXT:mmsynchro/Lang/locallang_db.xml:tx_mmsynchro_config.remoteHost',
			'displayCond' => 'FIELD:is_remote:REQ:true',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '50',
				'eval' => 'required,trim'
			)
		),
		'remotePort' => Array(
			'label' => 'LLL:EXT:mmsynchro/Lang/locallang_db.xml:tx_mmsynchro_config.remotePort',
			'displayCond' => 'FIELD:is_remote:REQ:true',
			'config' => Array (
				'type' => 'input',
				'size' => '5',
				'max' => '10',
				'default' => '22',
				'eval' => 'required,trim,int'
			)
		),
	),
	'types' => Array (
		'0' => Array('showitem' => '--div--;Basics,title,logPath,path_target,--div--;Database,is_db,dbUsername,dbPassword,dbHost,dbDatabase,--div--;Remote,is_remote,remoteUsername,remotePassword,remoteHost,remotePort')
	)
);
?>

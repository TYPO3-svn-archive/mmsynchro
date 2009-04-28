<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008 Timo Webler <timo.webler@mediaman.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**

* [CLASS/FUNCTION INDEX of SCRIPT]

*/

if (!defined('TYPO3_cliMode'))  die('You cannot run this script directly!');

// Include basis cli class
require_once(PATH_t3lib.'class.t3lib_cli.php');
require_once(t3lib_extMgm::extPath('mmsynchro').'lib/class.tx_mmsynchro_synchro.php');
require_once(t3lib_extMgm::extPath('mmsynchro').'lib/class.tx_mmsynchro_backup.php');
class tx_mmsynchro_cli extends t3lib_cli {

	/**
	 * Constructor
	 */
	function tx_mmsynchro_cli () {

		// Running parent class constructor
		parent::t3lib_cli();

		// Setting help texts:
		$this->cli_help['name'] = 'Synchronisation';
		$this->cli_help['synopsis'] = '###OPTIONS###';
		$this->cli_help['description'] = 'Synchronisation of database, folder and pagetree';
		$this->cli_help['options'] = 'first argument: ConfigId or "backup"'."\n".'second argument: SynMode (Classconstands bitoperation)';
		$this->cli_help['examples'] = 'cli_dispatch.phpsh mmsynchro 1 7 or cli_dispatch.phpsh mmsynchro backup ';
		$this->cli_help['author'] = 'Timo Webler';
	}

	/**
	 * CLI engine
	 *
	 * @param    array        Command line arguments
	 * @return    string
	 */
	function cli_main($argv) {
		 
		// get task (function)
		$task = (string)$this->cli_args['_DEFAULT'][1];

		if (!$task){
			$this->cli_validateArgs();
			$this->cli_help();
			exit;
		}

		// Backup starten
		if ($task == 'backup'){
			$this->startBackup();
			exit;
		}

		$mode = (string)$this->cli_args['_DEFAULT'][2];
		if ($mode){
			if (!t3lib_div::testInt($mode) || $mode <= 0){
				$this->cli_validateArgs();
				$this->cli_help();
				exit;
			}
		}

		if (t3lib_div::testInt($task)) {
			$test = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*',
            										'tx_mmsynchro_config',
            										'deleted = 0 AND uid = '.$task
			);
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($test) == 1){
				$this->startSynchro($task,$mode);
			}
		}
		else {
			$this->cli_validateArgs();
			$this->cli_help();
			exit;
		}
	}

	/**
	 * starts the synchronisation per cli
	 *
	 */
	private function startSynchro($id,$mode) {
		 
		// Output
		$this->cli_echo('Synchronisation start'."\n");
		 
		$sync = t3lib_div::makeInstance(tx_mmsynchro_synchro);
		$sync->init($id,$mode);
		$sync->start();
		 
		$this->cli_echo('Synchronisation end'."\n");
		 
	}

	private function startBackup(){
		// Output
		$this->cli_echo('Backup start'."\n");
		 
		$sync = t3lib_div::makeInstance(tx_mmsynchro_backup);
		$sync->init();
		$sync->export();
		 
		$this->cli_echo('Backup end'."\n");
	}
}

// Call the functionality
$cleanerObj = t3lib_div::makeInstance('tx_mmsynchro_cli');
$cleanerObj->cli_main($_SERVER['argv']);



?>
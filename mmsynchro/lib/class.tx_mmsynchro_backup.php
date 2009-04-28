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


/**
 * This class make the backup prozess
 *
 * @author	Timo Webler <timo.webler@mediaman.de>
 * @package	TYPO3
 * @subpackage	tx_mmsynchro
 */
class tx_mmsynchro_backup {

	//private $crypt = null;
	/**
	 * save the path to backup folder
	 *
	 * @var string
	 */
	private $path = null;
	/**
	 * save the log data
	 *
	 * @var string
	 */
	private $log = null;
	/**
	 * if display the progessBar
	 *
	 * @var boolean
	 */
	private $doBar = false;
	/**
	 * save the tx_mmsychro_progessBar instance
	 *
	 * @var tx_mmsynchro_progressBar
	 */
	private $bar = null;
	/**
	 * if function init is call and no fault is detected, then $test=true
	 *
	 * @var boolean
	 */
	private $test = false;

	/**
	 * Construktor
	 *
	 * @return	void
	 */
	public function tx_mmsynchro_synchro() {

	}

	/**
	 * initialising the class. must be call at first
	 *
	 * @return	boolean
	 */
	public function init($bar = false) {

		// error handler setzen
		set_error_handler(array($this,'error_handler'));
		error_reporting(E_ALL ^ E_NOTICE);


		// Test path
		$data = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mmsynchro']);
		if (is_array($data) && !empty($data['pfad'])){
			$value = t3lib_div::fixWindowsFilePath($data['pfad']);
			if (substr($value, -1) != '/'){
				$value .= '/';
			}
			
			if (!t3lib_div::validPathStr($value)){
				trigger_error('no valid Path in Configuration',E_USER_ERROR);
				return false;
			}
			
			if (!t3lib_div::isAbsPath($value)){
				trigger_error('no valid Path in Configuration',E_USER_ERROR);
				return false;
			}
			
			$this->path = $value;
		}
		else {
			trigger_error('no valid Path in Configuration',E_USER_ERROR);
			return false;
		}
		// Ausführungszeit abschalten. Funktioniert nur bei safe_mode = off
		if (ini_get('save_mode') == 0){
			set_time_limit(0);
		}
		$this->doBar = $bar;
		$this->log = '';
		$this->test = true;
		return true;
	}

	public function error_handler($fehlercode, $fehlertext, $fehlerdatei, $fehlerzeile){
		$error = array(
		E_ERROR => 'PHP Error',
		E_WARNING => 'PHP Warning',
		E_USER_ERROR => 'USER Error',
		E_USER_WARNING => 'USER Warning',
		E_USER_NOTICE => 'USER Notice',
		);

		if (array_key_exists($fehlercode, $error)){
			$this->log .= "\n".'######## ERROR BEGINN ########'."\n";
			$this->log .= $error[$fehlercode].': '.$fehlertext."\n";
			$this->log .= "\t".$fehlerdatei.': '.$fehlerzeile;
			$this->log .= "\n".'######## ERROR END ########'."\n";
		}
		if ($fehlercode == E_ERROR ||
		$fehlercode == E_USER_ERROR ||
		$fehlercode == E_COMPILE_ERROR ||
		$fehlercode == E_CORE_ERROR ||
		$fehlercode == E_RECOVERABLE_ERROR ){

			// LogDatei schreiben
			if ($this->path != '') {

				$file = $this->path.'mmsynchro_backup_error_'.time().'.txt';

				$file = t3lib_div::fixWindowsFilePath($file);

				t3lib_div::writeFile($file, $this->log);
			}

			return false;
		}
		else {
			return true;
		}
	}

	/**
	 * init the progressbar
	 *
	 * @param int $count	number of steps
	 * @param string $messages	display message
	 */
	private function getProgressBar($count, $messages) {

		require_once ('class.tx_mmsynchro_progressBar.php');
		$this->bar = t3lib_div :: makeInstance('tx_mmsynchro_progressBar');
		$this->bar->init();
		$this->bar->setMessage($messages);
		$this->bar->setAutohide(true);
		$this->bar->setSleepOnFinish(1);
		$this->bar->setBarLength(680);
		$this->bar->setPrecision(999999);
		$this->bar->setSleepOnFinish(1);
		//$this->bar->setStepElement('<div style="width:%spx;height:20px;background-color:%s;"></div>');
		$this->bar->setBackgroundColor('#D7DBE2');
		$this->bar->setForegroundColor('#E4E5F0');
		$this->bar->initialize($count); //print the empty bar
	}

	/**
	 * returns the log
	 *
	 * @return	string		Log
	 */
	public function getLog() {
		return $this->log;
	}
	

	public function export(){
		if (!$this->test){
			trigger_error('please use init bevor use this functin', E_USER_ERROR);
			return false;
		}
		$time = microtime(true);
		$this->log = '';
		// alle Tabellen auslesen
		$tables = $GLOBALS['TYPO3_DB']->admin_get_tables();
		$count = 0;
		if ($this->doBar){
			$this->getProgressBar((count($tables)*2),'Backup Database to .sql');
		}
		$this->log .= 'Backup Database to .sql'."\n";
		require_once (PATH_t3lib.'class.t3lib_install.php');
		$installObjeckt = t3lib_div :: makeInstance('t3lib_install');
		$tmp = $installObjeckt->getFieldDefinitions_database();
		$diff = $installObjeckt->getDatabaseExtra($tmp, array());
		$update_statements = $installObjeckt->getUpdateSuggestions($diff);

		$date = time();
		// Datei �ffnen
		$file = $this->path.'mmsynchro_backup_'.$date.'_.sql';
		$handle = fopen($file,'ab');
		if (!$handle){
			return false;
		}
		// CREATE TABLE statements
		$sql = '';

		foreach($update_statements['create_table'] as $key => $value){
			if ($this->doBar){
				$this->bar->setMessage(substr($value,0,strpos($value,'(')));
			}
			$count++;
			$sql .= $value."\n";
			$sql .= '#### MMSYNCHRO_BACKUP_NEXT ####'."\n\n";
			fwrite($handle, $sql);
			$this->log .= "\t".substr($value,0,strpos($value,'('))."\n";
			if ($this->doBar){
				$this->bar->increase();
			}
			$sql = '';
		}

		// INSERT statements
		foreach ($tables as $key => $value) {
			if ($this->doBar){
				$this->bar->setMessage('INSERT INTO '.$key);
			}
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $key, '');
			$sql = '';
			// Datensatz auslesen und schreiben
			$countData = 0;
			while ($data = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
				// insert
				$data = $GLOBALS['TYPO3_DB']->fullQuoteArray($data,$key);
				$fields = array_keys($data);
				$values = array_values($data);
				$sql .= 'INSERT INTO '.$key.' ('.implode(',',$fields).') VALUES ('.implode(',',$values).');'."\n";
				$sql .= '#### MMSYNCHRO_BACKUP_NEXT ####'."\n";
				fwrite($handle, $sql);
				$count++;
				$countData++;
				$sql = '';
			}
			$this->log .= "\t".'INSERT INTO '.$key.' - count: '.$countData."\n";
			$GLOBALS['TYPO3_DB']->sql_free_result($result);
			if ($this->doBar){
				$this->bar->increase();
			}
		}
		if ($this->doBar){
			$this->bar->stop();
			$this->bar = NULL;
		}
		// Schreibe Datei
		fclose($handle);
		rename($file,str_replace('_.sql','_'.$count.'.sql',$file));
		
		$time = microtime(true) - $time;
		$this->log .= "\n\n".t3lib_div::convertMicrotime($time).' ms';
		// log schreiben
		$file = $this->path.'mmsynchro_export_log_'.$date.'.txt';
		$file = t3lib_div::fixWindowsFilePath($file);
		t3lib_div::writeFile($file, $this->log);
		$this->log = '';
		//t3lib_div::writeFile($file,$sql);
	}

	public function import($file){
		if (!$this->test){
			trigger_error('please use inir bevor use this functin', E_USER_ERROR);
			return false;
		}

		if (!is_file($this->path.$file)){
			trigger_error('$file is no file', E_USER_WARNING);
			return false;
		}
		if (!is_readable($this->path.$file)){
			trigger_error('$file isn\'t readable', E_USER_WARNING);
			return false;
		}
		
		$time = microtime(true);
		$this->log = 'Import '.$file.' to database'."\n";
		$count = explode('_',$this->path.$file);
		$count = str_replace('.sql','',$count[3]);

		if ($this->doBar){
			$this->getProgressBar($count,'Import Database from .sql');
		}
		// Action
		
		$this->deleteDatabse();
		
		$sql = '';
		$handle = fopen($this->path.$file,'rb');
		while (!feof($handle)) {
			$line = fgets($handle);
			if ($line == '#### MMSYNCHRO_BACKUP_NEXT ####'."\n"){
				$GLOBALS['TYPO3_DB']->sql_query($sql);
				if ($this->doBar){
					$this->bar->increase();
				}

				$this->log .= "\t".'SQL: '.substr($sql,0,strpos($sql,'(')).' ...'."\n";
				$sql = '';
				continue;
			}

			$sql .= $line;
		}
		if ($this->doBar){
			$this->bar->stop();
			$this->bar = NULL;
		}
		
		$time = microtime(true) - $time;
		$this->log .= "\n\n".t3lib_div::convertMicrotime($time).' ms';
		// log schreiben
		$file = $this->path.'mmsynchro_import_log_'.time().'.txt';
		$file = t3lib_div::fixWindowsFilePath($file);
		t3lib_div::writeFile($file, $this->log);
		$this->log = '';
	}

	private function deleteDatabse(){
		$tables = $GLOBALS['TYPO3_DB']->admin_get_tables();
		foreach ($tables as $key => $value) {
			$GLOBALS['TYPO3_DB']->admin_query('DROP TABLE '.$key);
			$this->log .= "\t".'Drop table "'.$key.'"'."\n";
		}
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mmsynchro/lib/class.tx_mmsynchro_backup.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mmsynchro/lib/class.tx_mmsynchro_backup.php']);
}
?>

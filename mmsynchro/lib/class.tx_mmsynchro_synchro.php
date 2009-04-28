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
 * This class make the synchronizing prozess
 *
 * @author	Timo Webler <timo.webler@mediaman.de>
 * @package	TYPO3
 * @subpackage	tx_mmsynchro
 */
class tx_mmsynchro_synchro {
	
	/**
	 * Syncronisationmode TABLES
	 *
	 */
	const TABLES = 1;
	
	/**
	 * Syncronisationmode FILES
	 *
	 */
	const FILES = 2;
	
	/**
	 * Syncronisationmode PAGES
	 *
	 */
	const PAGES = 4;
	
	private $mode = null;
	private $id = null;
	private $config = null;
	private $log = null;
	private $tempDB = null;
	private $remote = null;
	private $bar	= null;
	private $doBar	= null;

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
	public function init($id = null, $mode = null, $bar=false) {

		// error handler setzen
		set_error_handler(array($this,'error_handler'));
		error_reporting(E_ALL ^ E_NOTICE);

		if ($mode != null) {
			if (t3lib_div :: testInt($mode) && $mode > 0) {
				$this->mode = $mode;
			} else {
				return false;
			}
		} else {
			$this->mode = self :: TABLES | self :: FILES | self :: PAGES;
		}
		if ($id != null && t3lib_div :: testInt($id) && $id > 0) {

			$this->id = $id;
			$this->config = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'tx_mmsynchro_config', 'deleted = 0 AND uid=' .
			$this->id);
			if (is_array($this->config) && sizeOf($this->config) > 0) {
				$this->config = $this->config[0];
			} else {
				$this->config = null;
				return false;
			}
		} else {
			return false;
		}
		 
		 
		// Überprüfen ob Remote und Klasse initialisieren
		if ($this->config['is_remote'] == 1) {
			require_once ('class.tx_mmsynchro_remote.php');
			$this->remote = t3lib_div :: makeInstance('tx_mmsynchro_remote');
			$this->remote->init($this->config);
		}

		// Ausführungszeit abschalten. Funktioniert nur bei safe_mode = off
		if (ini_get('save_mode') == 0){
			set_time_limit(0);
		}
		$this->doBar = $bar;
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
			if ($this->config['logPath'] != '') {

				$file = '/mmsynchro_log_' . t3lib_div::convUmlauts($this->config['title']) . '_' . time() . '.txt';

				$file = t3lib_div :: fixWindowsFilePath($this->config['logPath'] . $file);

				t3lib_div::writeFile($file, $this->log);
			}
				
			return false;
		}
		else {
			return true;
		}
	}

	/**
	 * starts the synchronisationsprozess
	 *
	 * @return	void
	 */
	public function start($tables = NULL, $files = NULL, $pages = NULL) {

		//trigger_error('test', E_USER_WARNING);

		// testen ob init() ausgef�hrt wurde
		if ($this->config != null) {

			$time = microtime(true);
			$this->log = '';


			// Connect Remote
			if ($this->config['is_remote'] == 1) {
				$this->log .= $this->remote->connect() . "\n";
			}

			if (($this->mode & self::TABLES) > 0) {
				$this->synchroDB($tables);
				$this->log .= "\n" . '-----------------------------------------' . "\n";
			}
			if (($this->mode & self::FILES) > 0) {
				$this->synchroFiles($files);
				$this->log .= "\n" . '-----------------------------------------' . "\n";
			}
			if (($this->mode & self::PAGES) > 0) {
				$this->synchroPage($pages);
			}

			// Disconnect Remote
			if ($this->config['is_remote'] == 1) {
				$this->log .= $this->remote->disconnect();
			}

			$time = microtime(true) - $time;

			$this->log .= "\n\n\n" . '-----------------------------------------' . "\n";
			$this->log .= 'Time: ' . t3lib_div::convertMicrotime($time) .' ms'. "\n";

			// LogDatei schreiben
			if ($this->config['logPath'] != '') {

				$file = '/mmsynchro_log_' . t3lib_div::convUmlauts($this->config['title']) . '_' . time() . '.txt';

				$file = t3lib_div :: fixWindowsFilePath($this->config['logPath'] . $file);

				t3lib_div::writeFile($file, $this->log);
			}

			if ($this->doBar){
				$this->bar->stop();
			}

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
		$this->bar->setPrecision(9999);
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

	/**
	 * switch the DB for synchronisation and back
	 *
	 * @return	boolean
	 */
	private function switchDB() {
		//global $TYPO3_CONF_VARS;

		// Datenbank wechsel
		if ($this->tempDB == null) {

			// Überprüfung ob Konfiguration vorhanden ist
			if ($this->config['dbUsername'] == null || $this->config['dbHost'] == null || $this->config['dbDatabase'] == null) {
				return false;
			}

			//t3lib_div::debug($GLOBALS['TYPO3_DB']);

			// Datenbankobjekt sichern
			$this->tempDB = clone $GLOBALS['TYPO3_DB'];

			// test ob dbal installiert
			if (t3lib_extMgm :: isLoaded('dbal')) {
				require_once ('class.tx_mmsynchro_dbalConnectDB.php');
				$GLOBALS['TYPO3_DB'] = t3lib_div :: makeInstance('tx_mmsynchro_dbalConnectDB');
			} else {
				require_once ('class.tx_mmsynchro_connectDB.php');
				$GLOBALS['TYPO3_DB'] = t3lib_div :: makeInstance('tx_mmsynchro_connectDB');

			}

			// DB objekt initialisieren
			$GLOBALS['TYPO3_DB']->init($this->config['dbHost'], $this->config['dbUsername'], $this->config['dbPassword'], $this->config['dbDatabase']);

			// Test der Datenbank verbindung
			//t3lib_div::debug($GLOBALS['TYPO3_DB']);
			if ($GLOBALS['TYPO3_DB']->connectDB()) {
				//t3lib_div::debug($GLOBALS['TYPO3_DB']);
				return true;
			}
			// Wenn keine Verbindung DB zur�ck setzen
			else {
				$this->switchDB();
				return false;
			}
		}
		// Datenbank zurück setzen
		else
		if ($this->tempDB != null) {

			$GLOBALS['TYPO3_DB'] = clone $this->tempDB;
			$this->tempDB = null;
			$GLOBALS['TYPO3_DB']->connectDB();
			return true;
		}
		// sonst false
		else {
			return false;
		}

	}

	/**
	 * synchronisation of the pagetree
	 *
	 * @return	void
	 */
	private function synchroPage($pages = NULL) {
		global $LANG, $TCA;
		if ($this->config['is_db'] != 1) {
			$this->log .= 'No Databsedata available';
			return false;
		}

		$this->log .= 'Sync Pages to ' . $this->config['dbDatabase'] . ' DB' . "\n";

		// Daten aus der DB auslesen bevor DB switch
		if ($pages == null){
			$pages = array ();
			$pages['pages'] = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid, synchro_pages', 'tx_mmsynchro_pages', 'config=' .
			$this->id);
		}


		// Wenn pages zum Sync vorhanden sind
		if (is_array($pages['pages']) && sizeOf($pages['pages']) > 0) {

			if ($this->doBar){
				// Progressbar
				$this->getProgressBar(sizeOf($pages['pages'])+2,'Sync Pages');
			}

			// export T3d
			require_once(t3lib_extMgm::extPath('impexp').'class.tx_impexp.php');
			$t3d = $this->exportPagesT3d($pages['pages']);


			// import T3d
			if ($this->doBar){
				$this->bar->setMessage('Sync Pages: import temporary t3d');
			}

			$this->importPagesT3d($t3d);

			if ($this->doBar){
				$this->bar->increase();
			}

			// sync upload folder
			$files = array();
			$files[] = array('synchro_files' => 'uploads');
			$this->listAllUploadFolder('uploads',$files);
			//echo t3lib_div::view_array($files);
			$this->synchroFiles($files);

		} else {
			$this->log .= "\t" . 'No pages to Sync' . "\n";
		}
	}

	/**
	 * list all uploads folder for page synchro
	 *
	 * @param string dir pathname
	 * @param array &$files array to save the folder
	 *
	 * @return array uplodas folder
	 */
	private function listAllUploadFolder($dir,&$files){
		$dir2 = t3lib_div::get_dirs(PATH_site.$dir);
		if (is_array($dir2)){
			foreach($dir2 as $value){
				$files[] = array('synchro_files' => $dir.'/'.$value);
				$this->listAllUploadFolder($dir.'/'.$value,$files);
			}
		}
	}

	/**
	 * import an .t3d file an delete the file
	 *
	 * @param string $t3d path to t3d
	 */
	private function importPagesT3d($t3d){
		$import = t3lib_div::makeInstance('tx_impexp');
		$import->init(0,'import');
		$import->update = true;
		$import->allowPHPScripts = true;
		$import->enableLogging = false;
		$this->log .= "\t" . 'Import t3d' . "\n";
		if($import->loadFile($t3d,1)){

			//echo '<pre>';
			//echo t3lib_div::view_array($import->dat['records']);
			//echo '</pre>';

			$tables = $import->dat['records'];
			//$files = $import->dat['files'];


			// Test ob DB wechsel geklappt hat
			if ($this->switchDB()) {

				$this->log .= "\t\t" . 'start import' . "\n";
				if (is_array($tables) && sizeOf($tables)> 0){
					foreach($tables as $key=>$value){
						$test = array();
						$test = explode(':',$key);
						if (sizeOf($test) == 2){
							if ($import->doesRecordExist($test[0],$test[1],'')){
								$import->import_mode[$key] = '0';
							}
							else {
								$import->import_mode[$key] = 'force_uid';
							}
						}
						else {
							$import->import_mode[$key] = '0';
						}
					}

					$import->importData('');
				}
				//echo t3lib_div::view_array($import->import_mode);

				$this->log .= "\t\t" . 'end import' . "\n";


				$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_pages','');
				$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_pagesection','');
				$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_hash','');

				// Datenbank zur�ck setzen
				$this->switchDB();
			} else {
				$this->log .= "\t\t" . 'don\'t connect do Database' . "\n";
			}


		}
		else {
			$this->log .= "\t" . 'can\'t open t3d' . "\n";
		}

		// Debug


		t3lib_div::unlink_tempfile($t3d);

	}

	/**
	 * export selected Pages in .t3d file
	 *
	 * @param array $pages selected pages
	 * @return string path of .t3d file
	 */
	private function exportPagesT3d($pages){
		global $TCA, $LANG;

		$this->log .= "\t" . 'Export' . "\n";

		$export = t3lib_div::makeInstance('tx_impexp');
		$export->init(0,'export');
		$export->setCharset($LANG->charSet);


		$export->showStaticRelations = true;
		$export->relOnlyTables = array('_ALL');
		$export->relStaticTables = array();

		$export->setHeaderBasics();
		$export->setMetaData(
			'Sync Pages',
			'',
			'',
		$GLOBALS['BE_USER']->user['username'],
		$GLOBALS['BE_USER']->user['realName'],
		$GLOBALS['BE_USER']->user['email']
		);

		// Daten auslesen
		$this->log .= "\t\t" . 'start export' . "\n";

		// PAGE TREE
		$pids = array();
		foreach ($pages as $value) {
			$pids[] = $value['synchro_pages'];
		}
		//var_dump($pids);
		require_once(PATH_t3lib.'class.t3lib_pagetree.php');
		$tree = t3lib_div::makeInstance('t3lib_pageTree');
		$tree->init('');

		$sPage = array(
			'uid' => 0,
			'title' => 'ROOT'
			);
			$HTML = t3lib_iconWorks::getIconImage('pages',$sPage,$GLOBALS['BACK_PATH'],'align="top"');
			$tree->tree[] = Array('row'=>$sPage,'HTML'=>$HTML);
			$tree->getTree(0,999,'');

			$idH = $tree->buffer_idH;
			unset($tree->buffer_idH);
			$tree->buffer_idH[0] = array('uid'=> 0,
			'subrow' => $idH);
			unset($idH);

			//echo t3lib_div::view_array($tree->buffer_idH);

			if (is_array($tree->buffer_idH))	{
				$flatList = $export->setPageTree($tree->buffer_idH);	// Sets the pagetree and gets a 1-dim array in return with the pages (in correct submission order BTW...)
				reset($flatList);
				//var_dump($flatList);
				foreach($flatList as $value)	{
					if (in_array($value,$pids)){
							
						if ($this->doBar){
							$this->bar->setMessage('Sync Pages: pages:'.$value);
						}
							
						$this->log .= "\t\t\t" . 'add pages:'.$value.'  to t3d' . "\n";
						$export->export_addRecord('pages',t3lib_BEfunc::getRecord('pages',$value));
						$this->addRecordsForPid($value,array('_ALL'),999,$export);
							
						if ($this->doBar){
							$this->bar->increase();
						}
					}
				}
			}

			$this->log .= "\t\t\t" . 'writing temporary t3d' . "\n";
			if ($this->doBar){
				$this->bar->setMessage('Sync Pages: writing temporary t3d');
			}

			for($a=0;$a<10;$a++)	{
				$addR = $export->export_addDBRelations($a);
				if (!count($addR)) break;
			}

			$export->export_addFilesFromRelations();

			/*
			 echo '<pre>';
			 echo htmlspecialchars($export->createXML());
			 echo '</pre>';

			 exit();
			 */

			$out = $export->compileMemoryToFileContent();

			$path = PATH_site.'typo3temp/mmsynchro/'.time().'.t3d';

			t3lib_div::writeFileToTypo3tempDir($path,$out);
			$this->log .= "\t\t" . 'stop export' . "\n";
			if ($this->doBar){
				$this->bar->increase();
			}

			return $path;
	}


	/**
	 * Function from tximpexp
	 * Adds records to the export object for a specific page id.
	 *
	 * @param	integer		Page id for which to select records to add
	 * @param	array		Array of table names to select from
	 * @param	integer		Max amount of records to select
	 * @return	void
	 */
	private function addRecordsForPid($k, $tables, $maxNumber, &$export)	{
		global $TCA;

		if (is_array($tables))	{
			reset($TCA);
			while(list($table)=each($TCA))	{
				if ($table!='pages'){
					if (!$TCA[$table]['ctrl']['is_static'])	{
						$res = $this->exec_listQueryPid($table,$k,t3lib_div::intInRange($maxNumber,1));
						while($subTrow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
							$this->log .= "\t\t\t" . 'add '.$table.':'.$subTrow['uid'].'  to t3d' . "\n";
							$export->export_addRecord($table,$subTrow);
						}
					}
				}
			}
		}
	}


	/**
	 * Function from tximpexp
	 * Selects records from table / pid
	 *
	 * @param	string		Table to select from
	 * @param	integer		Page ID to select from
	 * @param	integer		Max number of records to select
	 * @return	pointer		SQL resource pointer
	 */
	private function exec_listQueryPid($table,$pid,$limit)	{
		global $TCA, $LANG;

		$orderBy = $TCA[$table]['ctrl']['sortby'] ? 'ORDER BY '.$TCA[$table]['ctrl']['sortby'] : $TCA[$table]['ctrl']['default_sortby'];
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
		$table,
				'pid='.intval($pid).
		t3lib_BEfunc::deleteClause($table).
		t3lib_BEfunc::versioningPlaceholderClause($table),
				'',
		$GLOBALS['TYPO3_DB']->stripOrderBy($orderBy),
		$limit
		);

		return $res;
	}

	/**
	 * synchronisation of the database
	 * database defination are synchronized
	 * tables are droped and then filled
	 *
	 * @return	void
	 */
	private function synchroDB($tables = NULL) {

		//
		if ($this->config['is_db'] != 1) {
			$this->log .= 'No Databsedata available';
			return false;
		}

		require_once (PATH_t3lib . 'class.t3lib_install.php');
		$installObjeckt = t3lib_div :: makeInstance('t3lib_install');
		$tmp = $installObjeckt->getFieldDefinitions_database();

		//unset($installObjeckt);

		$this->log .= 'Sync Tables to ' . $this->config['dbDatabase'] . ' DB' . "\n";

		// Daten aus der DB auslesen bevor DB switch
		if ($tables == NULL){
			$tables = array ();
			$tables['tables'] = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid, synchro_table', 'tx_mmsynchro_tables', 'config=' .
			$this->id);
		}


		// Nur SQL result speichern. Sonst zu gro�er Speicher bedarf
		if (is_array($tables['tables']) && sizeOf($tables['tables']) > 0) {
			if ($this->doBar){
				// Progressbar
				$this->getProgressBar(sizeOf($tables['tables']),'Sync DB');
			}

			foreach ($tables['tables'] as $value) {
				$tables['Data'][$value['synchro_table']] = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $value['synchro_table'], '');

				$tables['structure'][$value['synchro_table']] = $tmp[$value['synchro_table']];
			}
		}

		//t3lib_div::debug($tables);
		unset ($tmp);

		// Test ob DB wechsel geklappt hat
		if ($this->switchDB()) {
			// Tabellen l�schen
			foreach ($tables['tables'] as $value) {
				if (isset ($tables['Data'][$value['synchro_table']])) {

					// drop table
					$GLOBALS['TYPO3_DB']->admin_query('DROP TABLE ' . $value['synchro_table']);
					$this->log .= "\t\t" . 'Drop table "' . $value['synchro_table'] . '"' . "\n";

				}
			}

			// �berpr�fung der Datenbank und eventuelle Anpassungen
			$tmp2 = $installObjeckt->getFieldDefinitions_database();

			$diff = $installObjeckt->getDatabaseExtra($tables['structure'], $tmp2);
			$update_statements = $installObjeckt->getUpdateSuggestions($diff);

			if (is_array($update_statements) && sizeOf($update_statements) > 0) {
				$this->log .= "\t" . 'Database operations' . "\n";

				foreach ((array) $update_statements['add'] as $string) {
					$GLOBALS['TYPO3_DB']->admin_query($string);
					$this->log .= "\t\t" . $string . "\n";
				}
				foreach ((array) $update_statements['change'] as $string) {
					$GLOBALS['TYPO3_DB']->admin_query($string);
					$this->log .= "\t\t" . $string . "\n";
				}
				foreach ((array) $update_statements['create_table'] as $string) {
					$GLOBALS['TYPO3_DB']->admin_query($string);
					$tmp = explode('(', $string);
					$this->log .= "\t\t" . $tmp[0] . "\n";
				}
			}

			// Aufr�umen
			unset ($tmp2);
			unset ($diff);
			unset ($update_statements);
			unset ($installObjeckt);
			unset ($tables['structure']);

			// Wenn Tabellen zum Sync vorhanden sind
			if (is_array($tables['tables'])) {

				$this->log .= "\t" . 'Data operations' . "\n";
				// Alle Daten in die DB schreiben
				foreach ($tables['tables'] as $value) {

					if ($this->doBar){
						$this->bar->setMessage('Sync Table: '.$value['synchro_table']);
					}

					if (isset ($tables['Data'][$value['synchro_table']])) {

						// Datensatz auslesen und schreiben
						while ($data = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($tables['Data'][$value['synchro_table']])) {

							//t3lib_div::debug($data);
							// insert
							$GLOBALS['TYPO3_DB']->exec_INSERTquery($value['synchro_table'], $data);

						}
						$GLOBALS['TYPO3_DB']->sql_free_result($tables['Data'][$value['synchro_table']]);
						$this->log .= "\t\t" . 'INSERT INTO ' . $value['synchro_table'] . ' all Data' . "\n";
						unset ($tables['Data'][$value['synchro_table']]);

					}
					if ($this->doBar){
						$this->bar->increase();
					}
				}
			}
			// Datenbank zur�ck setzen
			$this->switchDB();
		} else {
			$this->log .= "\t" . 'don\'t connect do Database' . "\n";
		}

	}

	/**
	 * synchronisation of the Folder
	 *
	 * @return	void
	 */
	private function synchroFiles($files = null) {

		clearstatcache();

		$this->log .= 'Synchro Data to ' . $this->config['path_target'] . "\n";

		if ($files == null){
			// Ordner zum Sync aus der DB auslesen
			$files = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid, synchro_files', 'tx_mmsynchro_files', 'config=' .
			$this->id, '', 'synchro_files');
		}

		//echo t3lib_div::view_array($files);

		// Überprüfung ob Dateien zum Sync vorhanden sind
		if (is_array($files) && sizeOf($files) > 0) {

			if ($this->doBar){
				// Progressbar
				$this->getProgressBar(sizeOf($files),'Sync Files');
			}

			// Ordner anlegen
			$this->log .= "\t" . 'Make new Directories :' . "\n";

			foreach ($files as $value) {

				// Überprüfen ob Remote
				if ($this->config['is_remote'] == 1) {
					$mode = fileperms(PATH_site . $value['synchro_files'] . '/');
					//$mode = substr(decoct($mode), 1);

					$tmp = $this->remote->remote_mkdir($this->config['path_target'], $value['synchro_files'], $mode);
					$this->log .= "\t\t" . $tmp . "\n";
				}
				// sonst lokal
				else {
					// Dateirechte auslesen
					$mode = fileperms(PATH_site . $value['synchro_files'] . '/');
					$mode = substr(decoct($mode), 1);
					// Ordner rechte setzen
					$tmpPermission = $GLOBALS['TYPO3_CONF_VARS']['BE']['folderCreateMask'];
					$GLOBALS['TYPO3_CONF_VARS']['BE']['folderCreateMask'] = $mode;
					$tmp = t3lib_div :: mkdir_deep($this->config['path_target'], $value['synchro_files']) . "\n";
					if (strlen($tmp) > 1) {
						$this->log .= "\t\t" . $tmp . "\n";
					}
					// Rechte zur�ck setzen
					$GLOBALS['TYPO3_CONF_VARS']['BE']['folderCreateMask'] = $tmpPermission;
				}

			}

			// Copy Data
			$this->log .= "\t" . 'Copy :' . "\n";

			foreach ($files as $value) {

				$files = t3lib_div :: getFilesInDir(PATH_site . $value['synchro_files'], '', 0, 1, '');

				if ($this->doBar){
					$this->bar->setMessage('Sync Folder: '.$value['synchro_files'].' with '.sizeOf($files).' Files');
				}

				// Ziel der Dateien
				$target = $this->config['path_target'] . $value['synchro_files'] . '/';

				$pfad = PATH_site . $value['synchro_files'] . '/';

				foreach ($files as $value2) {

					// �berpr�fen ob Remote
					if ($this->config['is_remote'] == 1) {
						$mode = fileperms($pfad . $value2);
						$mode = substr(decoct($mode), 2);
						if ($this->remote->remote_isfile($target . $value2)) {
							$fileInfo = $this->remote->remote_getFileInfo($target . $value2);
							// Bei remote verbindung wird die Dateigr��e anstatt der mtime verglichen
							// Probleme mit touch bei remote
							if ($fileInfo['size'] != filesize($pfad . $value2)) {
								$this->log .= "\t\t" . $this->remote->remote_copyFile($pfad . $value2, $target . $value2, $mode) . "\n";
							}
						} else {
							$this->log .= "\t\t" . $this->remote->remote_copyFile($pfad . $value2, $target . $value2, $mode) . "\n";
						}
					}
					// sonst lokal
					else {

						// Dateirechte auslesen
						$mode = fileperms($pfad . $value2);
						$mode = substr(decoct($mode), 2);
						// Dateirechte setzen
						$tmpPermission = $GLOBALS['TYPO3_CONF_VARS']['BE']['fileCreateMask'];
						$GLOBALS['TYPO3_CONF_VARS']['BE']['fileCreateMask'] = $mode;

						// �berpr�fen ob Datei schon vorhanden ist,
						// wenn �lter als aktuelle, alte l�schen
						// und neue copieren,
						// sonst mache nichts

						if (is_file($target . $value2)) {

							// Wenn Datei neue ist
							if (filemtime($target . $value2) < filemtime($pfad . $value2)) {
								t3lib_div :: upload_copy_move($pfad . $value2, $target . $value2);
								touch($target . $value2, filemtime($pfad . $value2), filemtime($pfad . $value2));
								$this->log .= "\t\t" . $value['synchro_files'] . '/' . $value2 . "\n";
							}
						}
						// Wenn Datei nicht vorhanden, copieren
						else {
							t3lib_div :: upload_copy_move($pfad . $value2, $target . $value2);
							touch($target . $value2, filemtime($pfad . $value2), filemtime($pfad . $value2));
							$this->log .= "\t\t" . $value['synchro_files'] . '/' . $value2 . "\n";
						}

						// Rechte zur�ck setzen
						$GLOBALS['TYPO3_CONF_VARS']['BE']['fileCreateMask'] = $tmpPermission;
					}
				}

				if ($this->doBar){
					$this->bar->increase();
				}
			}
		}
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mmsynchro/lib/class.tx_mmsynchro_synchro.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mmsynchro/lib/class.tx_mmsynchro_synchro.php']);
}
?>

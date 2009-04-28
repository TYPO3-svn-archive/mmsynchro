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

// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require_once('conf.php');
require_once($BACK_PATH.'init.php');
require_once($BACK_PATH.'template.php');

$LANG->includeLLFile('EXT:mmsynchro/Lang/locallang.xml');
require_once(PATH_t3lib.'class.t3lib_scbase.php');
$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.
// DEFAULT initialization of a module [END]



/**
 * Module 'synchronisation' for the 'mmsynchro' extension.
 *
 * @author	Timo Webler <timo.webler@mediaman.de>
 * @package	TYPO3
 * @subpackage	tx_mmsynchro
 */
class  tx_mmsynchro_module1 extends t3lib_SCbase {

	private $staticco = NULL;
	private $config = null;
	private $data = null;
	/**
	 * Initializes the Module
	 * @return	void
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		parent::init();

		/*
		 if (t3lib_div::_GP('clear_all_cache'))	{
			$this->include_once[] = PATH_t3lib.'class.t3lib_tcemain.php';
			}
			*/
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 * If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
	 *
	 * @return	void
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

		if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id) || ($BE_USER->user['ses_userid']))	{

			// Draw the header.
			$this->doc = t3lib_div::makeInstance('bigDoc');
			$this->doc->backPath = $BACK_PATH;
			$this->doc->docType = 'xhtml_trans';
			$this->doc->character_sets = 'utf-8';
			$this->doc->inDocStyles_TBEstyle = '
					td{
						vertical-align: top;			
					}
					ul {
						list-style-type: none;
						color: Black;
					}
					label{
						vertical-align: top !important;
						padding-bottom: 2px;
					}
					.warning {
						color:Red;
						margin: 5px;
						padding: 5px;
						font-size: 200%;
						text-align: center;
					}
					.test {
						width: 100%;
						padding: 20px;
						color: White;
						font-size: 150%; 
					}
					.progressbar {
						position: absolute;
						width: 700px;
						height: 300px;
						top: 5px;
						left: 10px;
						padding-left: 10px;
						padding-top: 50px;
						z-index: 100;
						background-color: White;
						border: 1px solid Black;
					}';
			$this->doc->form='<form action="" name="synchro" method="post">';

			// JavaScript
			$this->doc->JScode = '
				<script type="text/javascript" language="javascript">
					/*<![CDATA[*/
					script_ended = 0;
					function jumpToUrl(URL){
						document.location = URL;
					}
					function checkDelete(deleteId){
						check = confirm("'.$LANG->getLL('realyDelete',1).'");
						if (check == false){
							return false;
						}
						document.synchro.deleteId.value = deleteId;
						return true;
					}
					function checkConfig(configId){
						document.synchro.config.value = configId;
						return true;
					}
					function selectAllChilds(data,formElement){
						if (formElement.checked){
							tmp = true;
						}
						else {
							tmp = false;
						}
						var elements = document.synchro.elements;
						if (elements){
							for (i = 0; i < elements.length; i++){
								if (elements[i].type == "checkbox"){
									if (elements[i].name.indexOf(data) >= 0){
										elements[i].checked = tmp;	
									}
									
								}
							}
						}
					}
					function selectAll(formElement){
						if (formElement.checked){
							tmp = true;
						}
						else {
							tmp = false;
						}
						var elements = document.synchro.elements;
						if (elements){
							for (i = 0; i < elements.length; i++){
								if (elements[i].type == "checkbox"){
										elements[i].checked = tmp;
								}
							}
						}
					}
				/*]]>*/
				</script>';
			$this->doc->postCode='
				<script language="javascript" type="text/javascript">
					script_ended = 1;
					if (top.fsMod) top.fsMod.recentIds["web"] = 0;
				</script>
			';

				
				
			$headerSection = $this->doc->getHeader('pages',$this->pageinfo,$this->pageinfo['_thePath']).'<br />'.$LANG->sL('LLL:EXT:lang/locallang_core.xml:labels.path').': '.t3lib_div::fixed_lgd_pre($this->pageinfo['_thePath'],50);

			$this->content.=$this->doc->startPage($LANG->getLL('title'));
			$this->content.=$this->doc->header($LANG->getLL('title'));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->divider(5);

				
			// clean amp;  from GET for tabMenu
			foreach($GLOBALS['_GET'] as $key => $value) {
				if(preg_match('/amp;(.*)/', $key, $matches)) {
					$GLOBALS['_GET'][$matches[1]] = $value;
				}
			}
				
			// Variablen Testen
			$this->testConfig();
				
			// TableLayout
			$this->setTableLayout();
				
			// Aktion
			$this->doIt();
				
			// Render content:
			$this->content .= $this->moduleContent();

		} else {
			// If no access or if ID == zero

			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;

			$this->content.=$this->doc->startPage($LANG->getLL('title'));
			$this->content.=$this->doc->header($LANG->getLL('title'));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->spacer(10);
		}
	}

	/**
	 * test the _GP['config'] and safe ist to the mmsynchro session
	 *
	 *
	 * @return	void
	 */
	private function testConfig(){
		global $BE_USER;

		$session = $BE_USER->getModuleData('tools_txmmsynchroM1','ses');
		$BE_USER->gc();

		// Wenn Daten per _GP kommen
		if (t3lib_div::_GP('config') != '' ){
			$id = t3lib_div::_GP('config');
			// Wenn $id isInt und > 0
			if (t3lib_div::testInt($id) &&
			$id > 0){

				$test = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*',
            										'tx_mmsynchro_config',
            										'deleted = 0 AND uid = '.$id
				);
				// Wenn $id in der DB vorhanden
				if ($GLOBALS['TYPO3_DB']->sql_num_rows($test) == 1){
					$BE_USER->pushModuleData('tools_txmmsynchroM1',array('config'=>$id));
					$this->config = $id;
				}
				// Sonst auf null setzen
				else {
					$this->config = null;
					$BE_USER->pushModuleData('tools_txmmsynchroM1','');
				}
			}
			// Sonst auf null setzen
			else {
				$this->config = null;
				$BE_USER->pushModuleData('tools_txmmsynchroM1','');
			}
		}
		// Sonst Daten von der Session
		else if (isset($session['config']) &&
		t3lib_div::testInt($session['config']) &&
		$session['config'] > 0){
				
				
			$test = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*',
            										'tx_mmsynchro_config',
            										'deleted = 0 AND uid = '.$session['config']
			);
			// Wenn $id in der DB vorhanden
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($test) == 1){
				$this->config = $session['config'];
			}
			// Sonst auf null setzen
			else {
				$this->config = null;
				$BE_USER->pushModuleData('tools_txmmsynchroM1','');
			}
				
				
		}
		// Sonst auf null setzen
		else {
			$this->config = null;
			$BE_USER->pushModuleData('tools_txmmsynchroM1','');
		}
	}

	/**
	 * Prints out the module HTML
	 *
	 * @return	void
	 */
	function printContent()	{


		echo $this->content;
		$this->doSync();
		echo $this->doc->endPage();


	}

	/**
	 * Generates the module content
	 *
	 * @return	string	HTML Content
	 */
	private function moduleContent()	{
		global $LANG;
		$content = '';
		$tab = '';

		// Debug
		//$content .= t3lib_div::view_array($_GET);
		//$content .= t3lib_div::view_array($_POST);

		$content .= '<input type="hidden" name="config" value="'.$this->config.'" />'."\n";
		$content .= '<input type="hidden" name="deleteId" value="" />'."\n";

		if (t3lib_div::_GP('tabmenu') == '') {
			$tab = 0;
		} else {
			$tab = t3lib_div::_GP('tabmenu');
		}

		$warning = '';
		if ($this->config == null){
			$tab = 0;
			$warning .= '<p class="warning bgColor6">'.$LANG->getLL('warning',1).'</p>'."\n";
		}
		else{
			$this->data = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*',
											'tx_mmsynchro_config',
											'deleted = 0 AND uid='.$this->config
			);
			$this->data = $this->data[0];
			// Überprüfung ob Datenbankverbindung ausgewählt wurde
			if ($this->data['is_db'] != 1 && ($tab == 1 || $tab == 3)){
				$tab = 0;
			}
		}
		if (!$GLOBALS['BE_USER']->isAdmin() && ($tab == 1 || $tab == 2 || $tab == 3)){
			$tab = 0;
		}

		//$content .= t3lib_div::view_array($this->data);
		$content .= $this->getTabMenu($tab, $this->config);
		$content .= $warning;

		switch($tab)	{
			case 0:
				$content .= $this->getConfigContent();
				break;
			case 1:
				$content .= $this->getTablesContent();
				break;
			case 2:
				$content .= $this->getFilesContent();
				break;
			case 3:
				$content .= $this->getPageTreeContent();
				break;
			case 4:
				$content .= $this->getSynchroContent();
				break;
		}

		return $content;
	}

	/**
	 * generate the Tabmenu
	 *
	 * @return	string		HTML content
	 */
	private function getTabMenu ($tab, $config) {
		global $LANG;
		$mainParams = '';
		$elementName = 'tabmenu';

		$menuItems = array(
		$LANG->getLL('tab1',1),
		$LANG->getLL('tab2',1),
		$LANG->getLL('tab3',1),
		$LANG->getLL('tab4',1),
		$LANG->getLL('tab5',1)
		);
		return $this->doc->getTabMenu($mainParams,$elementName,$tab,$menuItems,'','&config='.$config);
	}

	/**
	 * generate the Configuration content
	 *
	 * @return	string		HTML content
	 */
	private function getConfigContent () {
		global $LANG, $BE_USER;
		$disable = false;
		if (!$GLOBALS['BE_USER']->isAdmin()){
			$disable = true;
		}

		$content = '';

		$configs = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*','tx_mmsynchro_config','deleted = 0');
			
		$content .= '<table>'."\n" .
				'<tr>' ."\n".
				'	<td></td>'."\n" .
				'	<td>'.($disable ? '' : $this->doc->t3Button(t3lib_BEfunc::editOnClick('&edit[tx_mmsynchro_config][0]=new',$GLOBALS['BACK_PATH']),
		$LANG->getLL('new',1))).'</td>'."\n" .
				'	<td>'.($disable ? '' : $this->doc->t3Button('return checkConfig(\'0\')',$LANG->getLL('clear',1))).'</td>'."\n" .
				'	<td></td>'."\n" .
				'	<td></td>'."\n" .
				'</tr>'."\n";
		if (is_array($configs)){
			foreach($configs as $value){
				$content .= '<tr>'."\n";
				$content .= '<td>'.( ($value['uid'] == $this->config) ? '<input type="checkbox" checked="checked" disabled="disabled" />' : '<input type="checkbox" disabled="disabled" />').'</td>'."\n";
				$content .=	'	<td>'.$value['title'].'</td>'."\n".
							'	<td>'.($disable ? '' : $this->doc->t3Button(t3lib_BEfunc::editOnClick('&edit[tx_mmsynchro_config]['.$value['uid'].']=edit',$GLOBALS['BACK_PATH']),
				$LANG->getLL('edit',1))).'</td>'."\n".
							'	<td>'.$this->doc->t3Button('return checkConfig(\''.$value['uid'].'\')',$LANG->getLL('select',1)).'</td>'."\n".
							'	<td>'.($disable ? '' : $this->doc->t3Button('return checkDelete(\''.$value['uid'].'\')',$LANG->getLL('delete',1))).'</td>'."\n" .
							'</tr>'."\n";
			}
			if ($this->data != null && $this->data['is_db'] == 1){
				$content .= '<tr>'."\n";
				$content .= '	<td colspan="5"><input type="submit" name="testDB" value="'.$LANG->getLL('testDB',1).'"/></td>'."\n";
				$content .= '</tr>'."\n";
			}
		}
		$content .= '</table>'."\n";
		if (t3lib_div::_GP('testDB')){
			$content .= '<div class="test bgColor6">';
			$content .= '<p>'.$this->testDB().'</p>';
			$content .= '</div>';
		}
		return $content;
	}

	/**
	 * testing the DB connection
	 *
	 * @return string Result
	 */
	private function testDB () {
		global $LANG;
		$return = '';
		 
		// if remote
		if ($this->data['is_remote'] == 1){
			require_once('../lib/class.tx_mmsynchro_remote.php');
			$remote = t3lib_div::makeInstance('tx_mmsynchro_remote');
			$remote->init($this->data);
			$return .= $remote->connect().'<br />';
		}
		$test = @mysql_connect($this->data['dbHost'], $this->data['dbUsername'], $this->data['dbPassword']);

		if ($this->data['is_remote'] == 1){
			$return .= $remote->disconnect().'<br />';
		}

		if (!$test){
			$return .= $LANG->getLL('noConnect',1);
		}
		else {
			mysql_close($test);
			$return .= $LANG->getLL('connect',1);
		}
		return $return;
	}

	/**
	 * generate the pagetree content
	 *
	 * @return	string		HTML content
	 */
	private function getPageTreeContent(){
		global $BACK_PATH, $LANG;
		$content = '';
		//require_once(PATH_t3lib.'class.t3lib_treeview.php');

		$selectedPages = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid, synchro_pages','tx_mmsynchro_pages','config='.$this->config);

		//t3lib_div::debug($selectedPages);


		
		require_once('../lib/class.tx_mmsynchro_localPageTree.php');
		$tree = t3lib_div::makeInstance('tx_mmsynchro_localPageTree');
		$tree->init('','',$selectedPages);
		$tree->ext_IconMode = false;
		$tree->thisScript = 'index.php';
		$tree->expandAll = 1;
		$tree->expandFirst = 1;
		
		$select = $this->doc->spacer(10).'<p style="text-align:center"><input type="submit" name="pageSelect" value="'.$LANG->getLL('select',1).'"/></p>';
		$content .= $select;
		$content .= '<p>'.$LANG->getLL('selectAll',1).': <input type="checkbox" id="all" onclick="selectAll(this)" /></p>';
		$content .= $tree->getBrowsableTree();

		//t3lib_div::debug($selectedPages);
		$content .= $select;
		$content .= $this->doc->spacer(10);
		
		// Datens�tze l�schen die nicht gefunden wurden
		if (is_array($selectedPages) && sizeOf($selectedPages) > 0){
			foreach($selectedPages as $value){
				$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_mmsynchro_pages','uid='.$value['uid']);
			}
		}

		return $content;
	}

	/**
	 * generate synchronisation content
	 *
	 * @return	string		HTML content
	 */
	private function getSynchroContent () {
		global $LANG;

		$content = '';

		$content .= '<table>';
		$content .= '<tr><th colspan="2">'.$LANG->getLL('syncTitle',1).'</th></tr>';
		if ($this->data['is_db'] == 1){
			$content .= '<tr><td>'.$LANG->getLL('tab2',1).'</td><td><input type="checkbox" name="synchro[1]"/></td></tr>';
		}
		$content .= '<tr><td>'.$LANG->getLL('tab3',1).'</td><td><input type="checkbox" name="synchro[2]"/></td></tr>';
		if ($this->data['is_db'] == 1){
			$content .= '<tr><td>'.$LANG->getLL('tab4',1).'</td><td><input type="checkbox" name="synchro[4]"/></td></tr>';
		}
		$content .= '<tr><td colspan="2"><input type="submit" name="synchroSelect" value="'.$LANG->getLL('select',1).'"/></td></tr>';
		$content .= '</table>';

		return $content;
	}

	/**
	 * generate the database content
	 *
	 * @return	string		HTML content
	 */
	private function getTablesContent () {
		global $LANG;
		$content = '';

		$tables = $GLOBALS['TYPO3_DB']->admin_get_tables();

		$selectedTables = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid, synchro_table','tx_mmsynchro_tables','config='.$this->config);
		//t3lib_div::debug($selectedTables);
		//t3lib_div::debug($tables);
		$tmpArray = array();
		$selected = '';
		if (is_array($tables)){
			$tmpArray[] = array('',$LANG->getLL('selectAll',1).': <input type="checkbox" id="all" onclick="selectAll(this)" />');
			foreach($tables as $key => $value){

				// sollte noch verbessert werden
				foreach($selectedTables as $key2 => $value2){
					if ( strcmp($key,$value2['synchro_table']) == 0 ){
						$selected = 'checked="checked"';
						// Wenn gefunden aus Array entfernen
						unset($selectedTables[$key2]);
						break 1;
					}
				}

				$tmpArray[] = array('<input type="checkbox" id="table_'.$key.'" name="tables['.$key.']" '.$selected.' />',
									'<label class="block" for="table_'.$key.'">'.htmlspecialchars($key).'</label>'
									);
									$selected = '';
			}
		}
		//t3lib_div::debug($selectedTables);

		// Datens�tze l�schen die nicht gefunden wurden
		if (is_array($selectedTables) && sizeOf($selectedTables) > 0){
			foreach($selectedTables as $value){
				$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_mmsynchro_tables','uid='.$value['uid']);
			}
		}
		$select = $this->doc->spacer(10).'<p style="text-align:center"><input type="submit" name="tableSelect" value="'.$LANG->getLL('select',1).'"/></p>';
		$content .= $select;
		$content .= $this->doc->table($tmpArray);
		$content .= $select;
		$content .= $this->doc->spacer(10);

		return $content;
	}

	/**
	 * generate the file content
	 * List folders in fileadmin, uploads, typo3conf/ext
	 *
	 * @return	string		HTML content
	 */
	private function getFilesContent () {
		global $BACK_PATH, $LANG,$TYPO3_CONF_VARS;
		$content = '';
		$pfade = array($TYPO3_CONF_VARS['BE']['fileadminDir'],
						'uploads',
						'typo3conf/ext'
		);
		
		// Weitere Ordner hinzufügen
		// RTE_imageStrageDir hinzufügen, wenn Pfad nicht gleich uploads/
		if ($TYPO3_CONF_VARS['BE']['RTE_imageStorageDir'] != 'uploads/'){
			$pfade[] = $TYPO3_CONF_VARS['BE']['RTE_imageStorageDir'];
		}
		// userHomePath hinzufügen, wenn gesetzt
		if ($TYPO3_CONF_VARS['BE']['userHomePath'] != ''){
				$pfade[] = $TYPO3_CONF_VARS['BE']['userHomePath'];
			}
		// groupHomePath hinzufügen, wenn gesetzt
		if ($TYPO3_CONF_VARS['BE']['groupHomePath'] != ''){
			$pfade[] = $TYPO3_CONF_VARS['BE']['groupHomePath'];
		}
		// userUploadDir hinzufügen, wenn gesetzt
		if ($TYPO3_CONF_VARS['BE']['userUploadDir'] != ''){
			$pfade[] = $TYPO3_CONF_VARS['BE']['userUploadDir'];
		}
		
		//$back = $BACK_PATH.'../';

		$selectedFiles = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid, synchro_files','tx_mmsynchro_files','config='.$this->config);

		$checked = '';



		foreach($pfade as $value){
			foreach ($selectedFiles as $key=>$value2){
				if (strcmp($value,$value2['synchro_files']) == 0 ){
					$checked = 'checked="checked"';
					// Wenn gefunden aus Array entfernen
					unset($selectedFiles[$key]);
					break 1;
				}
			}
			$labelID = str_replace('/','_',$value);
				
			if (($tmp = $this->listDir($value,$selectedFiles)) == ''){
				$contentArray[] = array('<input type="checkbox" '.$checked.' id="files_'.$labelID.'" name="files['.$value.']" /> ',
										'<label for="files_'.$labelID.'">'.$value.'</label>',
				$tmp);
			}
			else {
				$contentArray[] = array('<input type="checkbox" '.$checked.' id="files_'.$labelID.'" name="files['.$value.']" />' .
										'<input type="checkbox" onclick="selectAllChilds(\''.$value.'\',this)"/> ',
										'<label for="files_'.$labelID.'">'.$value.'</label>',
				$tmp);
			}
			$checked = '';
		}
		//t3lib_div::debug($selectedFiles);

		//Datensätze löschen die nicht gefunden wurden
		if (is_array($selectedFiles) && sizeOf($selectedFiles) > 0){
			foreach($selectedFiles as $value){
				$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_mmsynchro_files','uid='.$value['uid']);
			}
		}
		//t3lib_div::debug($contentArray);
		$select = $this->doc->spacer(10).'<p style="text-align:center"><input type="submit" name="fileSelect" value="'.$LANG->getLL('select',1).'"/></p>';
		$content .= $select;
		$content .=  $this->listUL($contentArray);
		//$content .=  $this->doc->table($contentArray);
		$content .= $select;
		$content .= $this->doc->spacer(10); 

		return $content;
	}

	/**
	 * is need to scan folder recursiv
	 *
	 * @param	string	$dir	dir to list
	 * @param	array	$dbData	Databasedata to check as reference
	 * @return	string	HTML content
	 */
	private function listDir($dir,&$dbData){

		$dir2 = t3lib_div::get_dirs(PATH_site.$dir);
		if (is_array($dir2)){
			$contentArray = array();
			foreach($dir2 as $value){
				$checked = '';
				foreach ($dbData as $key=>$value2){
					if (strcmp($dir.'/'.$value,$value2['synchro_files']) == 0 ){
						$checked = 'checked="checked"';
						// Wenn gefunden aus Array entfernen
						unset($dbData[$key]);
						break 1;
					}
				}

				$labelID = str_replace('/','_',$dir.'/'.$value);

				if (($tmp = $this->listDir($dir.'/'.$value,$dbData)) == ''){

					$contentArray[] = array('<input type="checkbox" '.$checked.' id="files_'.$labelID.'" name="files['.$dir.'/'.$value.']" /> ',
											'<label for="files_'.$labelID.'">'.$value.'</label>',
					$tmp
					);
				}
				else {
					$contentArray[] = array('<input type="checkbox" '.$checked.' id="files_'.$labelID.'" name="files['.$dir.'/'.$value.']" />' .
											'<input type="checkbox" onclick="selectAllChilds(\''.$dir.'/'.$value.'\',this)"/> ',
										'<label for="files_'.$labelID.'">'.$value.'</label>',
					$tmp
					);
				}
				$checked = '';
			}
		}
		if (is_array($contentArray)){
			return $this->listUL($contentArray);
			//return $this->doc->table($contentArray);
		}
		return '';
	}
	
	private function listUL($list){
		$ret = '';
		static $count = 0;
		if (!empty($list)){
			if ($count % 2){
				$class= 'class="bgColor2"';
			}
			else {
				$class= 'class="bgColor4"';
			}
			$ret = '<ul '.$class.'>'."\n";
			foreach ($list as $value){
				if (is_array($value)){
					$ret .= '<li>'.implode('',$value).'</li>'."\n";
				}
				else {
					$ret .= '<li>'.$value.'</li>'."\n";
				}
			}
			$count++;
			$ret .= '</ul>'."\n";
		}
		return $ret;
	}
	
	/**
	 * do all the action (excluded synchro)
	 *
	 * @return	void
	 */
	private function doIt(){
		global $BE_USER,$LANG;
		// ausgew�hlten Tabellen ablegen
		if (t3lib_div::_GP('tableSelect') == $LANG->getLL('select',0)){
			$tables = t3lib_div::_GP('tables');
				
			// Datenbank aufr�umen
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_mmsynchro_tables','config='.$this->config);
				
			if(is_array($tables)){
				// neue Datens�tze hinzuf�gen
				foreach($tables as $key=>$value){
					$field_values = array(
										'tstamp' => time(),
										'crdate' => time(),
										'cruser_id' => $BE_USER->user['uid'],
										'config'=> $this->config,
										'synchro_table' => $key
					);
					$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_mmsynchro_tables',$field_values);
				}
			}
		}
		// auswg�hlte Ordner ablegen
		else if (t3lib_div::_GP('fileSelect') == $LANG->getLL('select',0)){
			$files = t3lib_div::_GP('files');
				
			// Datenbank aufr�umen
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_mmsynchro_files','config='.$this->config);
				
			if(is_array($files)){
				// neue Datens�tze hinzuf�gen
				foreach($files as $key=>$value){
					$field_values = array(
										'tstamp' => time(),
										'crdate' => time(),
										'cruser_id' => $BE_USER->user['uid'],
										'config'=> $this->config,
										'synchro_files' => $key
					);
					$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_mmsynchro_files',$field_values);
				}
			}
		}
		// auswg�hlte Pages ablegen
		else if (t3lib_div::_GP('pageSelect') == $LANG->getLL('select',0)){
			$pages = t3lib_div::_GP('page');
				
			// Datenbank aufr�umen
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_mmsynchro_pages','config='.$this->config);
				
			if(is_array($pages)){
				// neue Datens�tze hinzuf�gen
				foreach($pages as $key=>$value){
					$field_values = array(
										'tstamp' => time(),
										'crdate' => time(),
										'cruser_id' => $BE_USER->user['uid'],
										'config'=> $this->config,
										'synchro_pages' => $key
					);
					$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_mmsynchro_pages',$field_values);
				}
			}
		}
		else if (t3lib_div::_GP('deleteId') != ''){
				
			$uid = t3lib_div::_GP('deleteId');
			if (t3lib_div::testInt($uid)){
				$field_values = array('deleted' => 1);
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_mmsynchro_config','uid = '.$uid,$field_values);
			}
		}
	}

	/**
	 * set the design for tables
	 *
	 * @return	void
	 */
	private function setTableLayout(){
		$this->doc->tableLayout = array(
			'table' => array('<table cellspacing="0" cellpadding="2" border="0" style="width:100%">','</table>'),
			'tr' => array('<tr>'."\n",'</tr>'."\n"),
			'defRow' => array(
				'defCol' => array('<td>'."\n",'</td>'."\n")
		),
			'defRowEven' => array(
				'0' => array('<td class="bgColor4" style="width:40px;">'."\n",'</td>'."\n"),
				'defCol' => array('<td class="bgColor4">'."\n",'</td>'."\n")
		),
			'defRowOdd' => array(
				'0' => array('<td class="bgColor2" style="width:40px;">'."\n",'</td>'."\n"),
				'defCol' => array('<td class="bgColor2">'."\n",'</td>'."\n")
		)
		);
	}

	/**
	 * start the sync prozess
	 */
	private function doSync(){
		global $LANG;
		// Synchronisation starten
		if (t3lib_div::_GP('synchroSelect') == $LANG->getLL('select',0)){
			$selectedSynchro = t3lib_div::_GP('synchro');
				
			if (is_array($selectedSynchro)){
				$selected = 0;
				foreach($selectedSynchro as $key=>$value){
					$selected |= $key;
				}
				require_once('../lib/class.tx_mmsynchro_synchro.php');
				$synchro = t3lib_div::makeInstance('tx_mmsynchro_synchro');
				$synchro->init($this->config,$selected,true);
				$synchro->start();
			}
				
		}
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mmsynchro/mod1/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mmsynchro/mod1/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_mmsynchro_module1');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>
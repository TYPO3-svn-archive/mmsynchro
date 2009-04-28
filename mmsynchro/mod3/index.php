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
class  tx_mmsynchro_module3 extends t3lib_SCbase {

	private $staticco = NULL;
	private $bar = NULL;
	private $pfad = NULL;
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
			$this->doc->character_sets = 'utf-8';
			$this->doc->docType = 'xhtml_trans';
			$this->doc->inDocStyles_TBEstyle = '
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
				}
				.warning {
					color:Red;
					margin: 5px;
					padding: 5px;
					font-size: 200%;
					text-align: center;
				}';


			$this->doc->form='<form action="" name="synchro" method="post">';

			// JavaScript
			$this->doc->JScode = '
				<script type="text/javascript" language="javascript">
					
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

			$this->checkConfig();

			if ($this->pfad != NULL){

				// Render content:
				$this->content .= $this->moduleContent();
			}

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
	 * Prints out the module HTML
	 *
	 * @return	void
	 */
	function printContent()	{
		global $LANG;

		echo $this->content;
		if ($this->pfad == NULL){
			t3lib_BEfunc::typo3PrintError($LANG->getLL('errorTitle',1),$LANG->getLL('errorText',1),'',0);
		}
		else {
			$this->doIt();
		}
		echo $this->doc->endPage();


	}

	/**
	 * Test the ext-config
	 *
	 */
	private function checkConfig() {
		$data = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mmsynchro']);
		if (is_array($data) && isset($data['pfad'])){
			
			$value = t3lib_div::fixWindowsFilePath($data['pfad']);
			if (substr($value, -1) != '/'){
				$value .= '/';
			}
			
			if (!t3lib_div::validPathStr($value)){
				return;
			}
			
			if (!t3lib_div::isAbsPath($value)){
				return;
			}
			
			$this->pfad = $value;
		}
	}


	/**
	 * Generates the module content
	 *
	 * @return	string	HTML Content
	 */
	private function moduleContent()	{
		global $LANG;
		$content = '';
		
		// Debug
		//$content .= t3lib_div::view_array($_GET);
		//$content .= t3lib_div::view_array($_POST);

		$content .= $this->doc->section($LANG->getLL('tab6',1),$this->getExportContent(), true, true);

		$content.=$this->doc->spacer(10);
		
		$content .= $this->doc->divider(5);
		
		$content .= $this->doc->section($LANG->getLL('tab7',1),$this->getImportContent(), true,true);
			

		return $content;
	}

	/**
	 * the content for the exprot tab
	 *
	 * @return unknown
	 */
	private function getExportContent () {
		global $LANG;
		$content = '';
		$content .= '<input type="submit" name="doExport" value="'.$LANG->getLL('doExport',1).'" />';

		return $content;
	}

	/**
	 * the content for the import tab
	 *
	 * @return content
	 */
	private function getImportContent () {
		global $LANG;
		$content = '';

		$content .= '
			<table>
				<tr>
					<td colspan="2">'.$LANG->getLL('sql',1).'</td>
				</tr>
				<tr>
					<td>'.$LANG->getLL('selectOne',1).'</td>
					<td>
						<select name="import_select" size="1">'."\n";

		$files = t3lib_div::getFilesInDir($this->pfad,'sql');
		arsort($files);
		if (is_array($files) && count($files) > 0){
			foreach ($files as $key => $value) {
				$time = explode('_',$value);
				$count = str_replace('.sql','',$time[3]);
				if (count($time) == 4){
					$time = date('d.m.Y - H:i:s',$time[2]);
					$content .= '					<option value="'.$value.'">Backup: '.$time.' - '.$count.' '.$LANG->getLL('elements',1).'</option>'."\n";
				}
			}
		}
		else {
			$content .= '					<option value="">'.$LANG->getLL('noSql',1).'</option>'."\n";
		}
		$content .=		'</select>
					</td>
				</tr>
				<tr>
					<td colspan="2"><input type="submit" name="doImport" value="'.$LANG->getLL('doImport',1).'" /></td>
				</tr>
			</table>';

		return $content;
	}

	/**
	 * do all the Action
	 *
	 */
	private function doIt(){
		global $LANG;
		require_once ('../lib/class.tx_mmsynchro_backup.php');
		if (t3lib_div::_GP('doExport') == $LANG->getLL('doExport',0)){
			$backup = t3lib_div::makeInstance('tx_mmsynchro_backup');
			$backup->init(true);
			$backup->export();
			/*$time = microtime(true);
			$this->export();
			$time = microtime(true) - $time;*/
			echo '<p class="warning bgColor6"> '.$LANG->getLL('endExport',0).'</p>';

		}
		if (t3lib_div::_GP('doImport') == $LANG->getLL('doImport',0)){
			$backup = t3lib_div::makeInstance('tx_mmsynchro_backup');
			$backup->init(true);
			$backup->import(t3lib_div::_GP('import_select'));
			/*
			$time = microtime(true);
			$this->import();
			$time = microtime(true) - $time;*/
			echo '<p class="warning bgColor6"> '.$LANG->getLL('endImport',0).'</p>';
		}
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mmsynchro/mod3/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mmsynchro/mod3/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_mmsynchro_module3');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>
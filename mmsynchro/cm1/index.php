<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008 Timo Webler <webler@mediaman.de>
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


// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require_once('conf.php');
require_once($BACK_PATH.'init.php');
require_once($BACK_PATH.'template.php');
$LANG->includeLLFile('EXT:mmsynchro/Lang/locallang_cm1.xml');
$LANG->includeLLFile('EXT:mmsynchro/Lang/locallang.xml');
require_once(PATH_t3lib.'class.t3lib_scbase.php');
// ....(But no access check here...)
// DEFAULT initialization of a module [END]



/**
 * mmsynchro module cm1
 *
 * @author    Timo Webler <timo.webler@mediaman.de>
 * @package    TYPO3
 * @subpackage  tx_mmsynchro
 */

class tx_mmsynchro_cm1 extends t3lib_SCbase {

	private $config = NULL;
	private $page = NULL;

	/**
	 * Main function of the module. Write the content to $this->content
	 *
	 * @return    [type]        ...
	 */
	public function main()    {
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		// Draw the header.
		$this->doc = t3lib_div::makeInstance('mediumDoc');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->docType = 'xhtml_trans';
		$this->doc->character_sets = 'utf-8';
		$this->doc->inDocStyles_TBEstyle = '
					td{
						vertical-align: top;			
					}
					.block {
						display: block;
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
		$this->doc->form = '<form action="" method="post">';

		// JavaScript
		$this->doc->JScode = '
                        <script language="javascript" type="text/javascript">
                            script_ended = 0;
                            function jumpToUrl(URL)    {
                                document.location = URL;
                            }
                        </script>
                    ';
		
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;
		if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id))    {
			if ($BE_USER->user['admin'] && !$this->id)    {
				$this->pageinfo = array(
                                    'title' => '[root-level]',
                                    'uid'   => 0,
                                    'pid'   => 0
				);
			}

			$headerSection = $this->doc->getHeader('pages',$this->pageinfo,$this->pageinfo['_thePath']).'<br />'
			.$LANG->sL('LLL:EXT:lang/locallang_core.xml:labels.path').': '.t3lib_div::fixed_lgd_pre($this->pageinfo['_thePath'], 50);

			$this->content.=$this->doc->startPage($LANG->getLL('title'));
			$this->content.=$this->doc->header($LANG->getLL('title'));
			$this->content.=$this->doc->spacer(5);
			//  $this->content.=$this->doc->section('',$this->doc->funcMenu($headerSection,t3lib_BEfunc::getFuncMenu($this->id,'SET[function]',$this->MOD_SETTINGS['function'],$this->MOD_MENU['function'])));
			$this->content.=$this->doc->divider(5);
				
			$this->testConfig();

			// Render content:
			$this->content .= $this->moduleContent();

		}
		$this->content.=$this->doc->spacer(10);
	}

	private function testConfig(){
		// Test PageID
		$id = t3lib_div::_GP('id');
		if ($id == ''){
			$this->page = NULL;
			return;
		}
		if (!t3lib_div::testInt($id)){
			$this->page = NULL;
			return;
		}
		$this->page = $id;

		// Test Config
		$config = t3lib_div::_GP('config');
		if ($id == ''){
			$this->config = NULL;
			return;
		}
		if (!t3lib_div::testInt($config)){
			$this->config = NULL;
			return;
		}
		$configs = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*','tx_mmsynchro_config','deleted = 0 AND uid = '.$config);
		if (count($configs) != 1){
			$this->config = NULL;
			return;
		}
		$this->config = $config;
	}

	/**
	 * [Describe function...]
	 *
	 * @return    [type]        ...
	 */
	public function printContent()    {

		echo $this->content;
		$this->doSync();
		echo $this->doc->endPage();
	}

	/**
	 * [Describe function...]
	 *
	 * @return    [type]        ...
	 */
	private function moduleContent()    {
		global $LANG;
		$content = '';

		// Debug
		//$content .= t3lib_div::view_array($_GET);
		//$content .= t3lib_div::view_array($_POST);

		if ($this->page == NULL){
			$content .= '<p class="warning bgColor6">'.$LANG->getLL('cm1_noPid',1).'</p>';
			return $content;
		}
		
		$content .= $this->getConfigContent();


		return $content;
	}
	
	private function doSync(){
		global $LANG;
		if (t3lib_div::_GP('doExport') != $LANG->getLL('cm1_start',0)){
			return false;
		}
		if ($this->config == NULL || $this->page == NULL){
			return false;
		}
		
		$page = array(
			'pages' => array(
				0 => array(
					'synchro_pages' => $this->page
				)
			)
		);
		require_once ('../lib/class.tx_mmsynchro_synchro.php');
		$sync = t3lib_div::makeInstance('tx_mmsynchro_synchro');
		$sync->init($this->config, tx_mmsynchro_synchro::PAGES, true);
		$sync->start(NULL,NULL,$page);
		echo '<p class="warning bgColor6">'.$LANG->getLL('cm1_end',1).'</p>';
		
	}
	
	private function getConfigContent() {
		global $LANG;
		$content = '';

		$content .='<table>
			<tr>
				<td>'.$LANG->getLL('cm1_pid',1).'</td>
				<td>'.$this->page.'</td>
			</tr>
			<tr>
				<td>'.$LANG->getLL('cm1_select',1).'</td>
				<td>
					<select name="config" size="1">';

		$configs = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*','tx_mmsynchro_config','deleted = 0');

		if (is_array($configs) && count($configs)>0){
			foreach ($configs as $value) {
				$content .= '<option value="'.$value['uid'].'">'.$value['title'].'</option>';
			}

		}
		else {
			$content .= '<option value="">'.$LANG->getLL('cm1_noConfig',1).'</option>';
		}

		$content .= '</select>
				</td>
			</tr>
			<tr>
				<td colspan="2"><input type="submit" name="doExport" value="'.$LANG->getLL('cm1_start',1).'" /></td>
			</tr>
		</table>';

		return $content;
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mmsynchro/cm1/index.php'])    {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mmsynchro/cm1/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_mmsynchro_cm1');
$SOBE->init();


$SOBE->main();
$SOBE->printContent();

?>
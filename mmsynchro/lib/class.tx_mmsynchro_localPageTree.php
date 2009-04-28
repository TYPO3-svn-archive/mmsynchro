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
 
require_once(PATH_t3lib.'class.t3lib_browsetree.php');


/**
 * This class display the page tree
 *
 * @author	Timo Webler <timo.webler@mediaman.de>
 * @package	TYPO3
 * @subpackage	tx_mmsynchro
 */ 
class tx_mmsynchro_localPageTree extends t3lib_browseTree {
	
	private $dbData = null;
	
	function init($clause='', $orderByFields='',&$dbData){
		$this->dbData = &$dbData;
		parent::init($clause,$orderByFields);
	}
	function wrapIcon($icon,$row)   {
		$cmd = '';
		//return '<a href="#"'.$cmd.'>'.$icon.'</a>';
		//$checkbox = '';
		
		$checked = '';
		foreach ($this->dbData as $key => $value){
			if ($value['synchro_pages'] == $this->getId($row)){
				$checked = 'checked="checked"';
				unset($this->dbData[$key]);
			}
		}
		$checkbox = '<input type="checkbox" '.$checked.' id="page_'.$this->getId($row).'" name="page['.$this->getId($row).']" /> ';
		return $checkbox.' '.$icon;
	}

	function wrapTitle($title,$row,$bank=0)	{
		$cmd = ' ';
		//$aOnClick = 'PageSelect(\''.$this->getId($row).'\',\''.$this->domIdPrefix.$row['uid'].'_'.$this->bank.'\');';
		return '<label for="page_'.$this->getId($row).'">'.$title.'</label>';
	}
	
	function PM_ATagWrap($icon, $cmd, $bMark = ''){
		return '&nbsp;';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mmsynchro/lib/class.tx_mmsynchro_localPageTree.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mmsynchro/lib/class.tx_mmsynchro_localPageTree.php']);
}
?>

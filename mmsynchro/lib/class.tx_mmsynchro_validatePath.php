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
 * Valid the Pathinput for table config
 * 
 * @author	Timo Webler <timo.webler@mediaman.de>
 * @package	TYPO3
 * @subpackage	tx_mmsynchro
 */
class tx_mmsynchro_validatePath {
	
	/**
	 * the JS validation
	 */
	function returnFieldJS() {	
		return '		
		return value;		
		';	
	}
	
	/**
	 * the PHP validation
	 */
	function evaluateFieldValue($value, $is_in, &$set) {	
		$value = t3lib_div::fixWindowsFilePath($value);
		if (substr($value, -1) != '/'){
			$value .= '/';
		}
		return $value;	
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mmsynchro/lib/class.tx_mmsynchro_validatePath.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mmsynchro/lib/class.tx_mmsynchro_validatePath.php']);
}
?>

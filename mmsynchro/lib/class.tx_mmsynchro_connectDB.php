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
 
require_once(PATH_t3lib.'class.t3lib_db.php');


/**
 * Database connection without dbal extension
 *
 * @author	Timo Webler <timo.webler@mediaman.de>
 * @package	TYPO3
 * @subpackage	tx_mmsynchro
 */  
class tx_mmsynchro_connectDB extends t3lib_DB {
	
	private $host	= 	null;
	private $user	= 	null;
	private	$passwd	= 	null;
	private $db		= 	null;
	
	public function init($host,$user,$passwd,$db){
		
		$this->host = $host;
		$this->user = $user;
		$this->passwd = $passwd;
		$this->db = $db;
		
	}
	
	function connectDB(){
		if ($this->sql_pconnect($this->host, $this->user, $this->passwd))	{
			if ($this->db == null)	{
				trigger_error('No database selected',E_USER_ERROR);
				exit;
			} elseif (!$this->sql_select_db($this->db))	{
				trigger_error('Cannot connect to the current database, "'.$this->db.'"',E_USER_ERROR);
				exit;
			}
		} else {
			trigger_error('The current username, password or host was not accepted when the connection to the database was attempted to be established!',E_USER_ERROR);
			exit;
		}
		return true;
	}
	
	function sql_select_db($TYPO3_db)	{
		return parent::sql_select_db($this->db);
	}
	
	function admin_get_tables()	{
		$whichTables = array();

		$tables_result = mysql_query('SHOW TABLE STATUS FROM `'.$this->db.'`', $this->link);
		if (!mysql_error())	{
			while ($theTable = mysql_fetch_assoc($tables_result)) {
				$whichTables[$theTable['Name']] = $theTable;
			}
		}

		return $whichTables;
	}
	
} 

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mmsynchro/lib/class.tx_mmsynchro_connectDB.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mmsynchro/lib/class.tx_mmsynchro_connectDB.php']);
}
?>

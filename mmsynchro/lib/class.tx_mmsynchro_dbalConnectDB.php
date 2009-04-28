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
 
require_once(t3lib_extMgm::extPath('dbal').'class.ux_t3lib_db.php');


/**
 * Database connection for dbal extension
 *
 * @author	Timo Webler <timo.webler@mediaman.de>
 * @package	TYPO3
 * @subpackage	tx_mmsynchro
 */  
class tx_mmsynchro_dbalConnectDB extends ux_t3lib_db {
	
	private $host	= 	null;
	private $user	= 	null;
	private	$passwd	= 	null;
	private $db		= 	null;
	
	public function init($host,$user,$passwd,$db){
		
		$this->host = $host;
		$this->user = $user;
		$this->passwd = $passwd;
		$this->db = $db;
		
		$handlerCfg = array (	// See manual.
		    '_DEFAULT' => array (
					'type' => 'native',
					'config' => array(
					    'username' => $user,
					    'password' => $passwd,
					    'host' => $host,
					    'database' => $db,
					)
		    ),
		);
		
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
		return parent::handler_init('_DEFAULT');
	}
	
	function admin_get_tables()	{
		$whichTables = array();

		// Getting real list of tables:
		switch($this->handlerCfg['_DEFAULT']['type'])	{
			case 'native':
				$tables_result = mysql_list_tables($this->db, $this->handlerInstance['_DEFAULT']['link']);
				if (!$this->sql_error())	{
					while ($theTable = $this->sql_fetch_assoc($tables_result)) {
						$whichTables[current($theTable)] = current($theTable);
					}
				}
				break;
			case 'adodb':
				$sqlTables = $this->handlerInstance['_DEFAULT']->MetaTables('TABLES');
				while (list($k, $theTable) = each($sqlTables)) {
					if(preg_match('/BIN\$/', $theTable)) continue; // skip tables from the Oracle 10 Recycle Bin
					$whichTables[$theTable] = $theTable;
				}
				break;
			case 'userdefined':
				$whichTables = $this->handlerInstance['_DEFAULT']->admin_get_tables();
				break;
		}

		// Check mapping:
		if (is_array($this->mapping) && count($this->mapping))	{

			// Mapping table names in reverse, first getting list of real table names:
			$tMap = array();
			foreach($this->mapping as $tN => $tMapInfo)	{
				if (isset($tMapInfo['mapTableName']))	$tMap[$tMapInfo['mapTableName']]=$tN;
			}

			// Do mapping:
			$newList=array();
			foreach($whichTables as $tN)	{
				if (isset($tMap[$tN]))	$tN = $tMap[$tN];
				$newList[$tN] = $tN;
			}

			$whichTables = $newList;
		}

		// Adding tables configured to reside in other DBMS (handler by other handlers than the default):
		if (is_array($this->table2handlerKeys))	{
			foreach($this->table2handlerKeys as $key => $handlerKey)	{
				$whichTables[$key] = $key;
			}
		}

		return $whichTables;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mmsynchro/lib/class.tx_mmsynchro_dbalConnectDB.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mmsynchro/lib/class.tx_mmsynchro_dbalConnectDB.php']);
}
?>

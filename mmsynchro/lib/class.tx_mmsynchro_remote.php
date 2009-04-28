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
 * This class make the connection to remote server
 *
 * @author	Timo Webler <timo.webler@mediaman.de>
 * @package	TYPO3
 * @subpackage	tx_mmsynchro
 */

class tx_mmsynchro_remote {
	private $is_test 	=	false;
	private $config 	=	null;
	private $ssh		= 	null;
	private $sftp		=	null;
	/**
	 * Construktor
	 *
	 * @return	void
	 */
	public function tx_mmsynchro_remote() {

	}

	/**
	 * initialising the class. must be call at first
	 *
	 * @return	boolean		
	 */
	public function init($config) {
		// Test ob ssh2 vorhanden ist
		if (extension_loaded('ssh2')) {
			// Test ob Remote ausgw�hlt wurde
			if ($config['is_remote'] != 1) {
				return false;
			}

			// Wenn test bestanden, variablen initialisieren
			$this->config = $config;
			
			
			$this->is_test = true;
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * connect to remote server
	 * 
	 */
	public function connect() {
		if (!$this->is_test) {
			return 'please use function init first';
		}
		if (!(@$this->ssh = ssh2_connect($this->config['remoteHost'], $this->config['remotePort']))) {
			$this->ssh = null;
			return 'unable to establish connection';
		}
		if(!@ssh2_auth_password($this->ssh, $this->config['remoteUsername'],$this->config['remotePassword'])) {
			$this->ssh = null;
			return 'unable to authenticate';
		}
		// allright, we're in!
		$this->sftp = ssh2_sftp($this->ssh);
		return 'connect to '.$this->config['remoteHost'];
	}
	
	/**
	 * disconnect from remote server
	 */
	public function disconnect(){
		if (!$this->is_test) {
			return 'please use function init first';
		}
		if ($this->ssh == null) {
			return 'you have no connection';
		}
		ssh2_exec($this->ssh,'logout');
		unset($this->ssh);
		return 'disconnected';
	}
	
	/**
	 * create an folder on remote server.
	 * create folder recursive
	 * 
	 * @param string $pfad base path
	 * @param string $dir folder to create
	 * @param long $mode create mode of folder. example: 0777
	 * 
	 * @return string result
	 */
	public function remote_mkdir($pfad,$dir,$mode){
		if (!$this->is_test) {
			return 'please use function init first';
		}
		if ($this->ssh == null) {
			return 'you have no connection';
		}
		// �berpr�fung ob verzeichnis vorhanden ist
		if (!is_dir('ssh2.sftp://'.$this->sftp.$pfad.$dir)){
			//Schreibrechte m�ssen noch definiert werden
			if(ssh2_sftp_mkdir($this->sftp, $pfad.$dir,$mode,true)){
				return 'create directory: '.$pfad.$dir;
			}
			else {
				return 'can not create directory: '.$pfad.$dir;
			}
		}
		return 'directory exists: '.$pfad.$dir;
	}
	
	/**
	 * copy an file on remote server
	 * 
	 * @param string $localFile path to local file
	 * @param string $remoteFile path to remote file
	 * @param long $mode create mode of file. example: 0777
	 * 
	 * @return string result
	 */
	public function remote_copyFile($localFile, $remoteFile, $mode){
		if (!$this->is_test) {
			return 'please use function init first';
		}
		if ($this->ssh == null) {
			return 'you have no connection';
		}
		if (ssh2_scp_send($this->ssh, $localFile, $remoteFile)){			
			return 'copy '.$localFile;
		}
		else {
			return 'can not copy '.$localFile;
		}
	}
	
	/**
	 * delete an file on remote server
	 * 
	 * @param string $remoteFile path to remote file
	 * 
	 * @return string result
	 */
	public function remote_deleteFile($remoteFile){
		if (!$this->is_test) {
			return 'please use function init first';
		}
		if ($this->ssh == null) {
			return 'you have no connection';
		}
		
		if (ssh2_sftp_unlink($this->sftp, $remoteFile)){
			return 'delete '.$remoteFile;
		}
		else {
			return 'can not delete '.$remoteFile;
		}
	}
	
	/**
	 * fileinfo(stat) from an file on remote server
	 * 
	 * @param string $remoteFile path to remote file
	 * 
	 * @return array result
	 */
	public function remote_getFileInfo($remoteFile){
		if (!$this->is_test) {
			return 'please use function init first';
		}
		if ($this->ssh == null) {
			return 'you have no connection';
		}
		return ssh2_sftp_stat($this->sftp,$remoteFile);
	}
	
	/**
	 * is_file for remote connection
	 * 
	 * @param string $remoteFile path to remote file
	 * 
	 * @return bool result
	 */
	public function remote_isfile($remoteFile){
		if (!$this->is_test) {
			return 'please use function init first';
		}
		if ($this->ssh == null) {
			return 'you have no connection';
		}
		return is_file('ssh2.sftp://'.$this->sftp.$remoteFile);
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mmsynchro/lib/class.tx_mmsynchro_remote.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mmsynchro/lib/class.tx_mmsynchro_remote.php']);
}
?>

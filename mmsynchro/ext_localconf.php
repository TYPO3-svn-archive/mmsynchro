<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

// Validations der Pfad Eingabefelder
$TYPO3_CONF_VARS['SC_OPTIONS']['tce']['formevals']['tx_mmsynchro_validatePath'] = 'EXT:mmsynchro/lib/class.tx_mmsynchro_validatePath.php';

// CLI Einbinden
$TYPO3_CONF_VARS['SC_OPTIONS']['GLOBAL']['cliKeys'][$_EXTKEY] = array('EXT:'.$_EXTKEY.'/cli/class.tx_mmsynchro_cli.php','_CLI_user');


?>

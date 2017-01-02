<?php
///////////////////////////////////////////////////////////////////////////////
//
// NagiosQL Service Pack 3
//
///////////////////////////////////////////////////////////////////////////////
//
// (c) 2016 by Fabio Lucchiari
//
// Project   : NagiosQL Service Pack 3
// Component : Configuration verification
// Website   : https://tecnologicasduvidas.blogspot.com.br
// Date      : $LastChangedDate: 2017-01-02 16:00:00 -0300$
//
///////////////////////////////////////////////////////////////////////////////
//
// Define common variables
// =======================
$prePageId			= 29;
$preContent   		= "admin/nagioscfg.tpl.htm";
$preAccess    		= 1;
$preFieldvars 		= 1;
$intRemoveTmp		= 0;
$strConfig 			= "";
//
// Include preprocessing files
// ===========================
require("../functions/prepend_adm.php");
require("../functions/prepend_content.php");
//
// Get configuration set ID
// ========================
$arrConfigSet   = $myConfigClass->getConfigSets();
$intConfigId    = $arrConfigSet[0];
$myConfigClass->getConfigData($intConfigId,"method",$intMethod);
$myConfigClass->getConfigData($intConfigId,"nagiosbasedir",$strBaseDir);
$strConfigfile 	= str_replace("//","/",$strBaseDir."/cgi.cfg");
$strLocalBackup	= str_replace("//","/",$strBaseDir."/cgi.cfg_old_").date("YmdHis",time());
//
// Convert Windows to UNIX 		
// =======================
$chkTaFileText = str_replace("\r\n","\n",$chkTaFileText);
//
// Process data
// ============
if ($chkTaFileText != "") {
	if ($intMethod == 1) {
    	if (file_exists($strBaseDir) && (is_writable($strBaseDir) && (is_writable($strConfigfile)))) {
			// Backup config file
			$intReturn = $myConfigClass->moveFile("nagiosbasic","cgi.cfg",$intConfigId);
			if ($intReturn == 1) {
				$myVisClass->processMessage($myConfigClass->strErrorMessage,$strErrorMessage);
			}
			// Write configuration
			$resFile = fopen($strConfigfile,"w");
			fputs($resFile,$chkTaFileText);
			fclose($resFile);
			$myVisClass->processMessage("<span style=\"color:green\">".translate('Configuration file successfully written!')."</span>",$strInfoMessage);
			$myDataClass->writeLog(translate('Configuration successfully written:')." ".$strConfigfile);
		} else {
			$myVisClass->processMessage(translate('Cannot open/overwrite the configuration file (check the permissions)!'),$strErrorMessage);
			$myDataClass->writeLog(translate('Configuration write failed:')." ".$strConfigfile);	
		}
	} else if (($intMethod == 2) || ($intMethod == 3)) {
		// Backup config file
		$intReturn = $myConfigClass->moveFile("nagiosbasic","cgi.cfg",$intConfigId);
		if ($intReturn == 1) {
			$myVisClass->processMessage($myConfigClass->strErrorMessage,$strErrorMessage);
		}
		// Write file to temporary
		$strFileName = tempnam($_SESSION['SETS']['path']['tempdir'], 'nagiosql_cgi');	
		$resFile = fopen($strFileName,"w");
		fputs($resFile,$chkTaFileText);
		fclose($resFile);
		// Copy configuration to remoty system
		$intReturn = $myConfigClass->configCopy($strConfigfile,$intConfigId,$strFileName,1);
		if ($intReturn == 0) {
			$myVisClass->processMessage("<span style=\"color:green\">".translate('Configuration file successfully written!')."</span>",$strInfoMessage);
			$myDataClass->writeLog(translate('Configuration successfully written:')." ".$strConfigfile);
			unlink($strFileName);			
		} else {
			$myVisClass->processMessage(translate('Cannot open/overwrite the configuration file (check the permissions on remote system)!'),$strErrorMessage);
			$myDataClass->writeLog(translate('Configuration write failed (remote):')." ".$strConfigfile);	
			unlink($strFileName);
		}
	}
}
//
// Include content
// ===============
$conttp->setVariable("TITLE",translate('CGI configuration file'));
$conttp->parse("header");
$conttp->show("header");
//
// Include input form
// ===================
$conttp->setVariable("ACTION_INSERT",filter_var($_SERVER['PHP_SELF'], FILTER_SANITIZE_STRING));
$conttp->setVariable("MAINSITE",$_SESSION['SETS']['path']['base_url']."admin.php");
foreach($arrDescription AS $elem) {
	$conttp->setVariable($elem['name'],$elem['string']);
} 
//
// Open configuration
// ==================
if ($intMethod == 1) {
	if (file_exists($strConfigfile) && is_readable($strConfigfile)) {
		$resFile = fopen($strConfigfile,"r");
		if ($resFile) {
			while(!feof($resFile)) {
				$strConfig .= fgets($resFile,1024);
			}
		}
	} else {
		$myVisClass->processMessage(translate('Cannot open the data file (check the permissions)!'),$strErrorMessage);
	}
} else if (($intMethod == 2) || ($intMethod == 3)) {
	// Write file to temporary
	$strFileName = tempnam($_SESSION['SETS']['path']['tempdir'], 'nagiosql_cgi');	
	// Copy configuration from remoty system
	$intReturn = $myConfigClass->configCopy($strConfigfile,$intConfigId,$strFileName,0);
	if ($intReturn == 0) {
		$resFile = fopen($strFileName,"r");
		if (is_resource($resFile)) {
			while(!feof($resFile)) {
				$strConfig .= fgets($resFile,1024);
			}
			unlink($strFileName);
		} else {
			$myVisClass->processMessage(translate('Cannot open the temporary file'),$strErrorMessage);
		}
	} else {
		$myVisClass->processMessage($myConfigClass->strErrorMessage,$strErrorMessage);
		$myDataClass->writeLog(translate('Configuration read failed (remote):')." ".$strErrorMessage);	
		if (file_exists($strFileName)) unlink($strFileName);
	}
}
$conttp->setVariable("DAT_NAGIOS_CONFIG",$strConfig);
if ($strErrorMessage != "") $conttp->setVariable("ERRORMESSAGE",$strErrorMessage);
$conttp->setVariable("INFOMESSAGE",$strInfoMessage);
// Check access rights for adding new objects
if ($myVisClass->checkAccGroup($prePageKey,'write') != 0) $conttp->setVariable("ADD_CONTROL","disabled=\"disabled\"");
$conttp->parse("naginsert");
$conttp->show("naginsert");
//
// Process footer
// ==============
$maintp->setVariable("VERSION_INFO","<a href='http://www.nagiosql.org' target='_blank'>NagiosQL</a> $setFileVersion");
$maintp->parse("footer");
$maintp->show("footer");
?>
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
$prePageId			= 30;
$preContent   		= "admin/verify.tpl.htm";
$preAccess    		= 1;
$preFieldvars 		= 1;
$intModus			= 0;
$strInfo			= "";
//
// Include preprocessing files
// ===========================
require("../functions/prepend_adm.php");
require("../functions/prepend_content.php");
//
// Get configuration set ID
// ========================
$arrConfigSet = $myConfigClass->getConfigSets();
$intConfigId  = $arrConfigSet[0];
$myConfigClass->getConfigData($intConfigId,"method",$intMethod);
//
// Process form variables
// ======================
$intProcessError = 0;
// Write monitoring data
if ($chkButValue1 != "") {
	$strNoData = translate('Writing of the configuration failed - no dataset or not activated dataset found')."::";
  	// Write host configuration
  	$strSQL  = "SELECT `id` FROM `tbl_host` WHERE `config_id` = $chkDomainId AND `active`='1'";
  	$myDBClass->getDataArray($strSQL,$arrData,$intDataCount);
  	$intError = 0;
  	if ($intDataCount != 0) {
    	foreach ($arrData AS $data) {
      		$intReturn = $myConfigClass->createConfigSingle("tbl_host",$data['id']);
			$intError += $intReturn;
    	}
  	}
  	if (($intError == 0) && ($intDataCount != 0)) {
		$myVisClass->processMessage(translate("Write host configurations")." ...",$strInfo);
		$myVisClass->processMessage("Hosts: ".translate("Configuration file successfully written!"),$strInfo);
  	} else if ($intDataCount != 0) {
		$myVisClass->processMessage("Hosts: ".translate("Cannot open/overwrite the configuration file (check the permissions)!"),$strErrorMessage);
		$intProcessError = 1;
  	} else {
		$myVisClass->processMessage("Hosts: ".translate("No configuration items defined!"),$strErrorMessage);
		$intProcessError = 1;
	}
  	// Write service configuration
  	$strSQL   = "SELECT `id`, `config_name` FROM `tbl_service` WHERE `config_id` = $chkDomainId AND `active`='1' GROUP BY `config_name`,`id`";
  	$myDBClass->getDataArray($strSQL,$arrData,$intDataCount);
  	$intError = 0;	
  	if ($intDataCount != 0) {
    	foreach ($arrData AS $data) {
      		$intReturn = $myConfigClass->createConfigSingle("tbl_service",$data['id']);
      		$intError += $intReturn;
    	}
  	}
  	if (($intError == 0) && ($intDataCount != 0)) {
		$myVisClass->processMessage(translate("Write service configurations")." ...",$strInfo);
		$myVisClass->processMessage("Services: ".translate("Configuration file successfully written!"),$strInfo);
  	} else if ($intDataCount != 0) {
    	$myVisClass->processMessage("Services: ".translate("Cannot open/overwrite the configuration file (check the permissions)!"),$strErrorMessage);
		$intProcessError = 1;
  	} else {
    	$myVisClass->processMessage("Services: ".translate("No configuration items defined!"),$strErrorMessage);
		$intProcessError = 1;
	}
	// Write hostgroup configuration
	$intReturn = $myConfigClass->createConfig("tbl_hostgroup");
	if ($intReturn == 0) {
		$myVisClass->processMessage(translate("Write")." hostgroups.cfg ...",$strInfo);
		$myVisClass->processMessage("Hostgroups: ".$myConfigClass->strInfoMessage,$strInfo);
	} else {
		if ($myConfigClass->strErrorMessage == $strNoData) {
			$myVisClass->processMessage(translate("Write")." hostgroups.cfg ...",$strInfo);
			$myVisClass->processMessage("Hostgroups: ".translate('No dataset or no activated dataset found - empty configuration written')."::",$strInfo);
		} else {
			$myVisClass->processMessage("Hostgroups: ".$myConfigClass->strErrorMessage,$strErrorMessage);
			$intProcessError = 1;
		}
	}
	// Write servicegroup configuration
	$intReturn = $myConfigClass->createConfig("tbl_servicegroup");
	if ($intReturn == 0) {
		$myVisClass->processMessage(translate("Write")." servicegroups.cfg ...",$strInfo);
		$myVisClass->processMessage("Servicegroups: ".$myConfigClass->strInfoMessage,$strInfo);
	} else {
		if ($myConfigClass->strErrorMessage == $strNoData) {
			$myVisClass->processMessage(translate("Write")." servicegroups.cfg ...",$strInfo);
			$myVisClass->processMessage("Servicegroups: ".translate('No dataset or no activated dataset found - empty configuration written')."::",$strInfo);
		} else {
			$myVisClass->processMessage("Servicegroups: ".$myConfigClass->strErrorMessage,$strErrorMessage);
			$intProcessError = 1;
		}
	}
	// Write hosttemplate configuration
	$intReturn = $myConfigClass->createConfig("tbl_hosttemplate");
	if ($intReturn == 0) {
		$myVisClass->processMessage(translate("Write")." hosttemplates.cfg ...",$strInfo);
		$myVisClass->processMessage("Hosttemplates: ".$myConfigClass->strInfoMessage,$strInfo);
	} else {
		if ($myConfigClass->strErrorMessage == $strNoData) {
			$myVisClass->processMessage(translate("Write")." hosttemplates.cfg ...",$strInfo);
			$myVisClass->processMessage("Hosttemplates: ".translate('No dataset or no activated dataset found - empty configuration written')."::",$strInfo);
		} else {
			$myVisClass->processMessage("Hosttemplates: ".$myConfigClass->strErrorMessage,$strErrorMessage);
			$intProcessError = 1;
		}
	}
	// Write servicetemplate configuration
	$intReturn = $myConfigClass->createConfig("tbl_servicetemplate");	
	if ($intReturn == 0) {
		$myVisClass->processMessage(translate("Write")." servicetemplates.cfg ...",$strInfo);
		$myVisClass->processMessage("Servicetemplates: ".$myConfigClass->strInfoMessage,$strInfo);
	} else {
		if ($myConfigClass->strErrorMessage == $strNoData) {
			$myVisClass->processMessage(translate("Write")." servicetemplates.cfg ...",$strInfo);
			$myVisClass->processMessage("Servicetemplates: ".translate('No dataset or no activated dataset found - empty configuration written')."::",$strInfo);
		} else {
			$myVisClass->processMessage("Servicetemplates: ".$myConfigClass->strErrorMessage,$strErrorMessage);
			$intProcessError = 1;
		}
	}
}
// Write additional data
if ($chkButValue2 != "") {
	$strNoData = translate('Writing of the configuration failed - no dataset or not activated dataset found')."::";
	// Write timeperiod configuration
	$intReturn = $myConfigClass->createConfig("tbl_timeperiod");
	if ($intReturn == 0) {
		$myVisClass->processMessage(translate("Write")." timeperiods.cfg ...",$strInfo);
		$myVisClass->processMessage("Timeperiods: ".$myConfigClass->strInfoMessage,$strInfo);
	} else {
		$myVisClass->processMessage("Timeperiods: ".$myConfigClass->strErrorMessage,$strErrorMessage);
		$intProcessError = 1;
	}
	// Write command configuration
	$intReturn = $myConfigClass->createConfig("tbl_command");
	if ($intReturn == 0) {
		$myVisClass->processMessage(translate("Write")." commands.cfg ...",$strInfo);
		$myVisClass->processMessage("Commands: ".$myConfigClass->strInfoMessage,$strInfo);
	} else {
		$myVisClass->processMessage("Commands: ".$myConfigClass->strErrorMessage,$strErrorMessage);
		$intProcessError = 1;
	}
	// Write contact configuration
	$intReturn = $myConfigClass->createConfig("tbl_contact");
	if ($intReturn == 0) {
		$myVisClass->processMessage(translate("Write")." contacts.cfg ...",$strInfo);
		$myVisClass->processMessage("Contacts: ".$myConfigClass->strInfoMessage,$strInfo);
	} else {
		$myVisClass->processMessage("Contacts: ".$myConfigClass->strErrorMessage,$strErrorMessage);
		$intProcessError = 1;
	}
	// Write contactgroup configuration
	$intReturn = $myConfigClass->createConfig("tbl_contactgroup");
	if ($intReturn == 0) {
		$myVisClass->processMessage(translate("Write")." contactgroups.cfg ...",$strInfo);
		$myVisClass->processMessage("Contactgroups: ".$myConfigClass->strInfoMessage,$strInfo);
	} else {
		$myVisClass->processMessage("Contactgroups: ".$myConfigClass->strErrorMessage,$strErrorMessage);
		$intProcessError = 1;
	}
	// Write contacttemplate configuration
	$intReturn = $myConfigClass->createConfig("tbl_contacttemplate");
	if ($intReturn == 0) {
		$myVisClass->processMessage(translate("Write")." contacttemplates.cfg ...",$strInfo);
		$myVisClass->processMessage("Contacttemplates: ".$myConfigClass->strInfoMessage,$strInfo);
	} else {
		if ($myConfigClass->strErrorMessage == $strNoData) {
			$myVisClass->processMessage(translate("Write")." contacttemplates.cfg ...",$strInfo);
			$myVisClass->processMessage("Contacttemplates: ".translate('No dataset or no activated dataset found - empty configuration written')."::",$strInfo);
		} else {
			$myVisClass->processMessage("Contacttemplates: ".$myConfigClass->strErrorMessage,$strErrorMessage);
			$intProcessError = 1;
		}
	}
	// Write servicedependency configuration
	$intReturn = $myConfigClass->createConfig("tbl_servicedependency");
	if ($intReturn == 0) {
		$myVisClass->processMessage(translate("Write")." servicedependencies.cfg ...",$strInfo);
		$myVisClass->processMessage("Servicedependencies: ".$myConfigClass->strInfoMessage,$strInfo);
	} else {
		if ($myConfigClass->strErrorMessage == $strNoData) {
			$myVisClass->processMessage(translate("Write")." servicedependencies.cfg ...",$strInfo);
			$myVisClass->processMessage("Servicedependencies: ".translate('No dataset or no activated dataset found - empty configuration written')."::",$strInfo);
		} else {
			$myVisClass->processMessage("Servicedependencies: ".$myConfigClass->strErrorMessage,$strErrorMessage);
			$intProcessError = 1;
		}
	}
	// Write hostdependency configuration
	$intReturn = $myConfigClass->createConfig("tbl_hostdependency");
	if ($intReturn == 0) {
		$myVisClass->processMessage(translate("Write")." hostdependencies.cfg ...",$strInfo);
		$myVisClass->processMessage("Hostdependencies: ".$myConfigClass->strInfoMessage,$strInfo);
	} else {
		if ($myConfigClass->strErrorMessage == $strNoData) {
			$myVisClass->processMessage(translate("Write")." hostdependencies.cfg ...",$strInfo);
			$myVisClass->processMessage("Hostdependencies: ".translate('No dataset or no activated dataset found - empty configuration written')."::",$strInfo);
		} else {
			$myVisClass->processMessage("Hostdependencies: ".$myConfigClass->strErrorMessage,$strErrorMessage);
			$intProcessError = 1;
		}
	}
	// Write serviceescalation configuration
	$intReturn = $myConfigClass->createConfig("tbl_serviceescalation");
	if ($intReturn == 0) {
		$myVisClass->processMessage(translate("Write")." serviceescalations.cfg ...",$strInfo);
		$myVisClass->processMessage("Serviceescalations: ".$myConfigClass->strInfoMessage,$strInfo);
	} else {
		if ($myConfigClass->strErrorMessage == $strNoData) {
			$myVisClass->processMessage(translate("Write")." serviceescalations.cfg ...",$strInfo);
			$myVisClass->processMessage("Serviceescalations: ".translate('No dataset or no activated dataset found - empty configuration written')."::",$strInfo);
		} else {
			$myVisClass->processMessage("Serviceescalations: ".$myConfigClass->strErrorMessage,$strErrorMessage);
			$intProcessError = 1;
		}
	}
	// Write hostescalation configuration
	$intReturn = $myConfigClass->createConfig("tbl_hostescalation");
	if ($intReturn == 0) {
		$myVisClass->processMessage(translate("Write")." hostescalations.cfg ...",$strInfo);
		$myVisClass->processMessage("Hostescalations: ".$myConfigClass->strInfoMessage,$strInfo);
	} else {
		if ($myConfigClass->strErrorMessage == $strNoData) {
			$myVisClass->processMessage(translate("Write")." hostescalations.cfg ...",$strInfo);
			$myVisClass->processMessage("Hostescalations: ".translate('No dataset or no activated dataset found - empty configuration written')."::",$strInfo);
		} else {
			$myVisClass->processMessage("Hostescalations: ".$myConfigClass->strErrorMessage,$strErrorMessage);
			$intProcessError = 1;
		}
	}
	// Write serviceextinfo configuration
	$intReturn = $myConfigClass->createConfig("tbl_serviceextinfo");
	if ($intReturn == 0) {
		$myVisClass->processMessage(translate("Write")." serviceextinfo.cfg ...",$strInfo);
		$myVisClass->processMessage("Serviceextinfo: ".$myConfigClass->strInfoMessage,$strInfo);
	} else {
		if ($myConfigClass->strErrorMessage == $strNoData) {
			$myVisClass->processMessage(translate("Write")." serviceextinfo.cfg ...",$strInfo);
			$myVisClass->processMessage("Serviceextinfo: ".translate('No dataset or no activated dataset found - empty configuration written')."::",$strInfo);
		} else {
			$myVisClass->processMessage("Serviceextinfo: ".$myConfigClass->strErrorMessage,$strErrorMessage);
			$intProcessError = 1;
		}
	}
	// Write hostextinfo configuration
	$intReturn = $myConfigClass->createConfig("tbl_hostextinfo");
	if ($intReturn == 0) {
		$myVisClass->processMessage(translate("Write")." hostextinfo.cfg ...",$strInfo);
		$myVisClass->processMessage("Hostextinfo: ".$myConfigClass->strInfoMessage,$strInfo);
	} else {
		if ($myConfigClass->strErrorMessage == $strNoData) {
			$myVisClass->processMessage(translate("Write")." hostextinfo.cfg ...",$strInfo);
			$myVisClass->processMessage("Hostextinfo: ".translate('No dataset or no activated dataset found - empty configuration written')."::",$strInfo);
		} else {
			$myVisClass->processMessage("Hostextinfo: ".$myConfigClass->strErrorMessage,$strErrorMessage);
			$intProcessError = 1;
		}
	}
}
// Check configuration
if ($chkButValue3 != "") {
  	$myConfigClass->getConfigData($intConfigId,"binaryfile",$strBinary);
  	$myConfigClass->getConfigData($intConfigId,"basedir",$strBaseDir);
  	$myConfigClass->getConfigData($intConfigId,"nagiosbasedir",$strNagiosBaseDir);
  	$myConfigClass->getConfigData($intConfigId,"conffile",$strConffile);
  	if ($intMethod == 1) {
    	if (file_exists($strBinary) && is_executable($strBinary)) {
      		$resFile = popen($strBinary." -v ".$strConffile,"r");
    	} else {
			$myVisClass->processMessage(translate('Cannot find the Nagios binary or no rights for execution!'),$strErrorMessage);
    	}
	} else if ($intMethod == 2) {
		$booReturn = 0;
		if (!isset($myConfigClass->resConnectId) || !is_resource($myConfigClass->resConnectId)) {
			$booReturn = $myConfigClass->getFTPConnection($intConfigId);
		}
		if ($booReturn == 1) {
      		$myVisClass->processMessage($myConfigClass->strErrorMessage,$strErrorMessage);
		} else {
			$intErrorReporting = error_reporting();
			error_reporting(0);
      		if (!($resFile = ftp_exec($myConfigClass->resConnectId,$strBinary.' -v '.$strConffile))) {
				$myVisClass->processMessage(translate('Remote execution (FTP SITE EXEC) is not supported on your system!'),$strErrorMessage);
      		}
      		ftp_close($conn_id);	
			error_reporting($intErrorReporting);
		}
  	} else if ($intMethod == 3) {
		$booReturn = 0;
		if (!isset($myConfigClass->resConnectId) || !is_resource($myConfigClass->resConnectId)) {
			$booReturn = $myConfigClass->getSSHConnection($intConfigId);
		}
		if ($booReturn == 1) {
      		$myVisClass->processMessage($myConfigClass->strErrorMessage,$strErrorMessage);
		} else {
			if (($strBinary != "") && ($strConffile != "") && (is_array($myConfigClass->sendSSHCommand('ls '.$strBinary))) && 
				(is_array($myConfigClass->sendSSHCommand('ls '.$strConffile)))) {
				$arrResult = $myConfigClass->sendSSHCommand($strBinary.' -v '.$strConffile,15000);
				if (!is_array($arrResult) || ($arrResult == false)) {
					$myVisClass->processMessage(translate('Remote execution of nagios verify command failed (remote SSH)!'),$strErrorMessage);
				}
			} else {
				$myVisClass->processMessage(translate('Nagios binary or configuration file not found (remote SSH)!'),$strErrorMessage);	
			}
		}
	}
}
// Restart nagios
if ($chkButValue4 != "") {
  	// Read config file
  	$myConfigClass->getConfigData($intConfigId,"commandfile",$strCommandfile);
	$myConfigClass->getConfigData($intConfigId,"binaryfile",$strBinary);
  	$myConfigClass->getConfigData($intConfigId,"pidfile",$strPidfile);
  	// Check state nagios demon
  	clearstatcache();
  	if ($intMethod == 1) {
	    if (substr_count(PHP_OS,"Linux") != 0) {
    		exec('ps -ef | grep '.basename($strBinary).' | grep -v grep',$arrExec);
		} else {
			$arrExec[0] = 1;
		}
		if (file_exists($strPidfile) && isset($arrExec[0])) {
      		if (file_exists($strCommandfile) && is_writable($strCommandfile)) {
        		$strCommandString = "[".time()."] RESTART_PROGRAM\n";
        		$timeout = 3;
        		$old = ini_set('default_socket_timeout', $timeout);
        		$resCmdFile = fopen($strCommandfile,"w");
        		ini_set('default_socket_timeout', $old);
        		stream_set_timeout($resCmdFile, $timeout);
        		stream_set_blocking($resCmdFile, 0);
        		if ($resCmdFile) {
          			fputs($resCmdFile,$strCommandString);
          			fclose($resCmdFile);
          			$myDataClass->writeLog(translate('Nagios daemon successfully restarted'));
					$myVisClass->processMessage(translate('Restart command successfully send to Nagios'),$strInfoMessage);	
					
        		} else {
          			$myDataClass->writeLog(translate('Restart failed - Nagios command file not found or no rights to execute'));
					$myVisClass->processMessage(translate('Nagios command file not found or no rights to write!'),$strErrorMessage);
						
        		}
      		} else {
				
        		$myDataClass->writeLog(translate('Restart failed - Nagios command file not found or no rights to execute'));
				$myVisClass->processMessage(translate('Restart failed - Nagios command file not found or no rights to execute'),$strErrorMessage);	
      		}
    	} else {
      		$myDataClass->writeLog(translate('Restart failed - Nagios daemon was not running'));
			$myVisClass->processMessage(translate('Nagios daemon is not running, cannot send restart command!'),$strErrorMessage);	
    	}
  	} else if ($intMethod == 2) {
      	$myDataClass->writeLog(translate('Restart failed - FTP restrictions'));
		$myVisClass->processMessage(translate('Nagios restart is not possible via FTP remote connection!'),$strErrorMessage);
  	} else if ($intMethod == 3) {
		$booReturn = 0;
		if (!isset($myConfigClass->resConnectId) || !is_resource($myConfigClass->resConnectId)) {
			$booReturn = $myConfigClass->getSSHConnection($intConfigId);
		}
		if ($booReturn == 1) {
      		$myVisClass->processMessage($myConfigClass->strErrorMessage,$strErrorMessage);
		} else {
			if (is_array($myConfigClass->sendSSHCommand('ls '.$strCommandfile))) {
				$strCommandString = "[".mktime()."] RESTART_PROGRAM;".mktime();
				$arrInfo = ssh2_sftp_stat($myConfigClass->resSFTP, $strCommandfile);
				$intFileStamp1 = $arrInfo['mtime'];
				$arrResult = $myConfigClass->sendSSHCommand('echo "'.$strCommandString.'" >> '.$strCommandfile);
				$arrInfo = ssh2_sftp_stat($myConfigClass->resSFTP, $strCommandfile);
				$intFileStamp2 = $arrInfo['mtime'];
				if ($intFileStamp2 <= $intFileStamp1) {
					$myVisClass->processMessage(translate('Restart failed - Nagios command file not found or no rights to execute (remote SSH)!'),$strErrorMessage);
				} else {
					$myDataClass->writeLog(translate('Nagios daemon successfully restarted (remote SSH)'));
					$myVisClass->processMessage(translate('Restart command successfully send to Nagios (remote SSH)'),$strInfoMessage);
				}
			} else {
				$myVisClass->processMessage(translate('Nagios command file not found (remote SSH)!'),$strErrorMessage);	
			}
		}
	}
}
//
// include content
// ===============
$conttp->setVariable("TITLE",translate('Check written configuration files'));
$conttp->parse("header");
$conttp->show("header");
$conttp->setVariable("CHECK_CONFIG",translate('Check configuration files:'));
$conttp->setVariable("RESTART_NAGIOS",translate('Restart Nagios:'));
$conttp->setVariable("WRITE_MONITORING_DATA",translate('Write monitoring data'));
$conttp->setVariable("WRITE_ADDITIONAL_DATA",translate('Write additional data'));
if (($chkButValue3 == "") && ($chkButValue4 == "")) $conttp->setVariable("WARNING",translate('Warning, always check the configuration files before restart Nagios!'));
$conttp->setVariable("MAKE",translate('Do it'));
$conttp->setVariable("IMAGE_PATH",$_SESSION['SETS']['path']['base_url']."images/");
$conttp->setVariable("ACTION_INSERT",filter_var($_SERVER['PHP_SELF'], FILTER_SANITIZE_STRING));
$strOutput = "";
if (isset($resFile) && ($resFile != false)){
	$intError   = 0;
	$intWarning = 0;
  	while(!feof($resFile)) {
    	$strLine = fgets($resFile,1024);
    	if ((substr_count($strLine,"Error:") != 0) || (substr_count($strLine,"Total Errors:") != 0)) {
      		$conttp->setVariable("VERIFY_CLASS","errormessage");
      		$conttp->setVariable("VERIFY_LINE",$strLine);
      		$conttp->parse("verifyline");
      		$intError++;
			if (substr_count($strLine,"Total Errors:") != 0) $intError--;
    	}
    	if ((substr_count($strLine,"Warning:") != 0) || (substr_count($strLine,"Total Warnings:") != 0)) {
      		$conttp->setVariable("VERIFY_CLASS","warnmessage");
      		$conttp->setVariable("VERIFY_LINE",$strLine);
      		$conttp->parse("verifyline");
      		$intWarning++;
			if (substr_count($strLine,"Total Warnings:") != 0) $intWarning--;
    	}
    	$strOutput .= $strLine."<br>";
  	}
  	$myDataClass->writeLog(translate('Written Nagios configuration checked - Warnings/Errors:')." ".$intWarning."/".$intError);
  	pclose($resFile);
  	if (($intError == 0) && ($intWarning == 0)) {
    	$conttp->setVariable("VERIFY_CLASS","greenmessage");
    	$conttp->setVariable("VERIFY_LINE","<b>".translate('Written configuration files are valid, Nagios can be restarted!')."</b>");
    	$conttp->parse("verifyline");
  	}	
  	$conttp->setVariable("DATA",$strOutput);
  	$conttp->parse("verifyline");
} else if (isset($arrResult) && is_array($arrResult)) {
	$intError   = 0;
	$intWarning = 0;
  	foreach ($arrResult AS $elem) {
    	if ((substr_count($elem,"Error:") != 0) || (substr_count($elem,"Total Errors:") != 0)) {
      		$conttp->setVariable("VERIFY_CLASS","errormessage");
      		$conttp->setVariable("VERIFY_LINE",$elem);
      		$conttp->parse("verifyline");
      		$intError++;
      		if (substr_count($elem,"Total Errors:") != 0) $intError--;
    	}
    	if ((substr_count($elem,"Warning:") != 0) || (substr_count($elem,"Total Warnings:") != 0)) {
      		$conttp->setVariable("VERIFY_CLASS","warnmessage");
      		$conttp->setVariable("VERIFY_LINE",$elem);
      		$conttp->parse("verifyline");
      		$intWarning++;
      		if (substr_count($elem,"Total Warnings:") != 0) $intWarning--;
    	}
    	$strOutput .= $elem."<br>";
  	}
  	$myDataClass->writeLog(translate('Written Nagios configuration checked - Warnings/Errors:')." ".$intWarning."/".$intError);
  	if (($intError == 0) && ($intWarning == 0)) {
    	$conttp->setVariable("VERIFY_CLASS","greenmessage");
    	$conttp->setVariable("VERIFY_LINE","<b>".translate('Written configuration files are valid, Nagios can be restarted!')."</b>");
    	$conttp->parse("verifyline");
  	}	
  	$conttp->setVariable("DATA",$strOutput);
  	$conttp->parse("verifyline");

}
if ($strErrorMessage != "") $conttp->setVariable("ERRORMESSAGE",$strErrorMessage);
$conttp->setVariable("INFOMESSAGE",$strInfoMessage);
if ($strInfo != "") {
  	$conttp->setVariable("VERIFY_CLASS","greenmessage");
  	$conttp->setVariable("VERIFY_LINE","<br>".$strInfo);
  	$conttp->parse("verifyline");
}
// Check access rights for adding new objects
if ($myVisClass->checkAccGroup($prePageKey,'write') != 0) $conttp->setVariable("ADD_CONTROL","disabled=\"disabled\"");
$conttp->parse("main");
$conttp->show("main");
//
// Insert footer
// =============
$maintp->setVariable("VERSION_INFO","<a href='http://www.nagiosql.org' target='_blank'>NagiosQL</a> $setFileVersion");
$maintp->parse("footer");
$maintp->show("footer");
?>
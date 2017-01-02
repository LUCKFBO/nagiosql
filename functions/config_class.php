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
///////////////////////////////////////////////////////////////////////////////////////////////
//
// Class: Configuration class
//
///////////////////////////////////////////////////////////////////////////////////////////////
//
// Includes all functions used for handling configuration files with NagiosQL
//
// Name: nagconfig
//
///////////////////////////////////////////////////////////////////////////////////////////////
class nagconfig {
  	// Define class variables
    var $arrSettings;       			// Array includes all global settings
  	var $intDomainId  		= 0;		// Domain id value
	var $resConnectId;			 		// Connection id for FTP and SSH connections
	var $resConnectType;		 		// Connection type for FTP and SSH connections
	var $resConnectServer;		 		// Connection server name for FTP and SSH connections
	var $resSFTP;				 		// SFTP ressource id
	var $arrRelData			= "";		// Relation data
	var $strRelTable		= "";		// Relation table name
	var $intNagVersion		= 0;		// Nagios version id
	var $strPicPath			= "none";	// Picture path string
    var $strErrorMessage 	= ""; 		// String including error messages
  	var $strInfoMessage   	= ""; 		// String including information messages

	///////////////////////////////////////////////////////////////////////////////////////////
  	//  Class constructor
  	///////////////////////////////////////////////////////////////////////////////////////////
  	//
  	//  Activities during initialisation
  	//
  	///////////////////////////////////////////////////////////////////////////////////////////
  	function __construct() {
    	if (isset($_SESSION) && isset($_SESSION['SETS'])) {
    		// Read global settings
    		$this->arrSettings = $_SESSION['SETS'];
    		if (isset($_SESSION['domain'])) $this->intDomainId = $_SESSION['domain'];
		}
  	}
	///////////////////////////////////////////////////////////////////////////////////////////
	//  Function: Get last change date of table and config files
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Determines the dates of the last data table change and the last modification to the 
	//  configuration files
	//
	//  Parameter:  		$strTableName   	Name of the data table
	//   
  	//  Return values:		0 = successful
	//						1 = error
	//						Status message is stored in message class variables
	//
	//						$arrTimeData   		Array with time data of table and all config files
	//            			$strCheckConfig		Information string (text message)
	//
	///////////////////////////////////////////////////////////////////////////////////////////
  	function lastModifiedFile($strTableName,&$arrTimeData,&$strCheckConfig) {
    	// Get configuration filename based on table name
    	switch($strTableName) {
      		case "tbl_timeperiod":      	$strFile = "timeperiods.cfg"; 			break;
      		case "tbl_command":       		$strFile = "commands.cfg"; 				break;
      		case "tbl_contact":       		$strFile = "contacts.cfg"; 				break;
      		case "tbl_contacttemplate":   	$strFile = "contacttemplates.cfg"; 		break;
      		case "tbl_contactgroup":    	$strFile = "contactgroups.cfg"; 		break;
      		case "tbl_hosttemplate":    	$strFile = "hosttemplates.cfg"; 		break;
      		case "tbl_servicetemplate":   	$strFile = "servicetemplates.cfg"; 		break;
      		case "tbl_hostgroup":     		$strFile = "hostgroups.cfg"; 			break;
      		case "tbl_servicegroup":    	$strFile = "servicegroups.cfg"; 		break;
      		case "tbl_servicedependency": 	$strFile = "servicedependencies.cfg"; 	break;
      		case "tbl_hostdependency":    	$strFile = "hostdependencies.cfg"; 		break;
      		case "tbl_serviceescalation": 	$strFile = "serviceescalations.cfg"; 	break;
      		case "tbl_hostescalation":    	$strFile = "hostescalations.cfg"; 		break;
      		case "tbl_hostextinfo":     	$strFile = "hostextinfo.cfg"; 			break;
      		case "tbl_serviceextinfo":    	$strFile = "serviceextinfo.cfg"; 		break;
    	}
		// Get table times
		$strCheckConfig = "";
		$arrTimeData 	= "";
		$arrTimeData['table'] = "unknown";
    	// Clear status cache
    	clearstatcache();
    	if (isset($_SESSION['domain'])) $this->intDomainId = $_SESSION['domain'];
		$this->getDomainData("enable_common",$strCommon);
    	// Get last change of date table
		if ($strCommon == 1) {
			$strSQL 	= "SELECT `updateTime` FROM `tbl_tablestatus` 
						   WHERE (`domainId`=".$this->intDomainId." OR `domainId`=0) AND `tableName`='".$strTableName."' ORDER BY `updateTime` DESC LIMIT 1";
		} else {
			$strSQL 	= "SELECT `updateTime` FROM `tbl_tablestatus` WHERE `domainId`=".$this->intDomainId." AND `tableName`='".$strTableName."'";
		}
		$booReturn 	= $this->myDBClass->getSingleDataset($strSQL,$arrDataset);
		if ($booReturn && isset($arrDataset['updateTime'])) {
			$arrTimeData['table'] = $arrDataset['updateTime'];
		} else {
			$strSQL = "SELECT `last_modified` FROM `".$strTableName."` WHERE `config_id`=".$this->intDomainId." ORDER BY `last_modified` DESC LIMIT 1";
			$booReturn = $this->myDBClass->getSingleDataset($strSQL,$arrDataset);
    		if (($booReturn == true) && isset($arrDataset['last_modified'])) {
      			$arrTimeData['table'] = $arrDataset['last_modified'];
			}
		}
		// Get config sets
		$arrConfigId = $this->getConfigSets();
		if ($arrConfigId != 1) {
			// Define variables
			$strTimeFile  	= "unknown";
			$intFileStamp   = time();
			foreach($arrConfigId AS $intConfigId) {
				// Get configuration file data
				$this->getConfigData($intConfigId,"target",$strTarget);
				$this->getConfigData($intConfigId,"basedir",$strBaseDir);
				$this->getConfigData($intConfigId,"method",$strMethod);
				$arrTimeData[$strTarget] = "unknown";
				$intFileStampTemp 		 = -1;
				// Lokal file system
				if (($strMethod == 1) && (file_exists($strBaseDir."/".$strFile))) {
					$intFileStampTemp 		 = filemtime($strBaseDir."/".$strFile);
					$arrTimeData[$strTarget] = date("Y-m-d H:i:s",$intFileStampTemp);
				// FTP file system
				} else if ($strMethod == 2) {
					// Check connection
					if (!isset($this->resConnectId) || !is_resource($this->resConnectId) || ($this->resConnectType != "FTP")) {
						$booReturn = $this->getFTPConnection($intConfigId);
						if ($booReturn == 1) return(1);
					}
					$intFileStampTemp = ftp_mdtm($this->resConnectId, $strBaseDir."/".$strFile);
					if ($intFileStampTemp != -1) $arrTimeData[$strTarget] = date("Y-m-d H:i:s",$intFileStampTemp);
					ftp_close($this->resConnectId);
				// SSH file system
				} else if ($strMethod == 3) {
					// Check connection
					if (!isset($this->resConnectId) || !is_resource($this->resConnectId)) {
						$booReturn = $this->getSSHConnection($intConfigId);
						if ($booReturn == 1) return(1); 
					}
					// Check file date
					if (is_array($this->sendSSHCommand('ls '.str_replace("//","/",$strBaseDir."/".$strFile)))) {
						$arrInfo 	  = ssh2_sftp_stat($this->resSFTP,str_replace("//","/",$strBaseDir."/".$strFile));
						$intFileStampTemp = $arrInfo['mtime'];
						if ($intFileStampTemp != -1) $arrTimeData[$strTarget] = date("Y-m-d H:i:s",$intFileStampTemp);
					}
				}
				if (isset($intFileStampTemp)) {
					if (strtotime($arrTimeData['table']) > $intFileStampTemp)  {
						$strCheckConfig = translate('Warning: configuration file is out of date!');
					}
				}
				if ($arrTimeData[$strTarget] == 'unknown') {
					$strCheckConfig = translate('Warning: configuration file is out of date!');
				}
			}
			return(0);
		} else {
			$strCheckConfig = translate('Warning: no configuration target defined!');
			return(0);
		}
  	}
	
	///////////////////////////////////////////////////////////////////////////////////////////
	//  Function: Get last change date of table and config file
	///////////////////////////////////////////////////////////////////////////////////////////
  	//
  	//  Determines the dates of the last data table change and the last modification to the 
	//  configuration file
  	//
	//  Parameter:  		$strTableName		Name of the datatable
	//						$strConfigName   	Name of the configuration file
  	//						$arrTimeData      	Array with timestamps of files/data item
  	//            			$intTimeStatus    	Time status value
	//											0 = all files are newer than the database item
	//											1 = some file are older than the database item
	//											2 = one file is missing
	//											3 = any files are missing
	//											4 = no configuration targets defined
  	//
  	//  Return values:		0 = successful
	//						1 = error
	//						Status message is stored in message class variables
    //
    ///////////////////////////////////////////////////////////////////////////////////////////
  	function lastModifiedDir($strTableName,$strConfigName,$intDataId,&$arrTimeData,&$intTimeInfo) {
		// Build file name
    	$strFile  = $strConfigName.".cfg";
		// Get table times
		$intTimeInfo = -1;
		$arrTimeData = "";
		$arrTimeData['table'] = "unknown";
    	// Clear status cache
    	clearstatcache();
    	// Get last change on dataset
    	if ($strTableName == "tbl_host") {
      		$arrTimeData['table'] 	= $this->myDBClass->getFieldData("SELECT DATE_FORMAT(`last_modified`,'%Y-%m-%d %H:%i:%s') FROM `tbl_host` 
																	  WHERE `host_name`='".$strConfigName."' AND `config_id`=".$this->intDomainId);
			$strActive 				= $this->myDBClass->getFieldData("SELECT `active` FROM `tbl_host` WHERE `host_name`='".$strConfigName."' 
																	  AND `config_id`=".$this->intDomainId);
    	} else if ($strTableName == "tbl_service") {
      		$arrTimeData['table']	= $this->myDBClass->getFieldData("SELECT DATE_FORMAT(`last_modified`,'%Y-%m-%d %H:%i:%s') FROM `tbl_service` 
																   	  WHERE `id`='".$intDataId."' AND `config_id`=".$this->intDomainId);
			$intServiceCount 		= $this->myDBClass->countRows("SELECT * FROM `$strTableName` WHERE `config_name`='".$strConfigName."' 
													  			   AND `config_id`=".$this->intDomainId." AND `active`='1'");
			if ($intServiceCount == 0) {$strActive = 0;} else {$strActive = 1;}
    	} else {
      		return(1);
    	}
		// Get config sets
		$arrConfigId = $this->getConfigSets();
		if ($arrConfigId != 1) {
			// Define variables
			$strTimeFile  	= "unknown";
			$intFileStamp   = time();
			foreach($arrConfigId AS $intConfigId) {
				// Get configuration file data
				$this->getConfigData($intConfigId,"target",$strTarget);
				$this->getConfigData($intConfigId,"method",$strMethod);
				// Get last change on dataset
				if ($strTableName == "tbl_host") {
					$booReturn 	= $this->getConfigData($intConfigId,"hostconfig",$strBaseDir);
				} else if ($strTableName == "tbl_service") {
					$booReturn 	= $this->getConfigData($intConfigId,"serviceconfig",$strBaseDir);
				}
				$arrTimeData[$strTarget] = "unknown";
				$intFileStampTemp 		 = -1;
				// Lokal file system
				if (($strMethod == 1) && (file_exists($strBaseDir."/".$strFile))) {
					$intFileStampTemp 		 = filemtime($strBaseDir."/".$strFile);
					$arrTimeData[$strTarget] = date("Y-m-d H:i:s",$intFileStampTemp);
				// FTP file system
				} else if ($strMethod == 2) {
					// Check connection
					if (!isset($this->resConnectId) || !is_resource($this->resConnectId) || ($this->resConnectType != "FTP")) {
						$booReturn = $this->getFTPConnection($intConfigId);
						if ($booReturn == 1) return(1);
					}
					$intFileStampTemp = ftp_mdtm($this->resConnectId, $strBaseDir."/".$strFile);
					if ($intFileStampTemp != -1) $arrTimeData[$strTarget] = date("Y-m-d H:i:s",$intFileStampTemp);
					ftp_close($this->resConnectId);
				// SSH file system
				} else if ($strMethod == 3) {
					// Check connection
					if (!isset($this->resConnectId) || !is_resource($this->resConnectId) || ($this->resConnectType != "SSH")) {
						$booReturn = $this->getSSHConnection($intConfigId);
					}
					// Check file date
					if (is_array($this->sendSSHCommand('ls '.str_replace("//","/",$strBaseDir."/".$strFile)))) {
						$arrInfo 	  = ssh2_sftp_stat($this->resSFTP,str_replace("//","/",$strBaseDir."/".$strFile));
						$intFileStampTemp = $arrInfo['mtime'];
						if ($intFileStampTemp != -1) $arrTimeData[$strTarget] = date("Y-m-d H:i:s",$intFileStampTemp);
					}
				}
				if (($intFileStampTemp == -1) && ($strActive == '1')) {
					$intTimeInfo = 2;
					return(0);
				}
				if ((strtotime($arrTimeData['table']) > $intFileStampTemp) && ($strActive == '1'))  {
					$intTimeInfo = 1;
					return(0);
				}				
			}
			$intItems    = count($arrTimeData) - 1;
			$intUnknown	 = 0;
			$intUpToDate = 0;
			foreach($arrTimeData AS $elem => $key) {
				if ($key == 'unknown') $intUnknown++;
				if (strtotime($arrTimeData['table']) < strtotime($key)) $intUpToDate++;
			}
			if ($intUnknown  == $intItems) $intTimeInfo = 3;
			if ($intUpToDate == $intItems) $intTimeInfo = 0;
			return(0);
		} else {
			$intTimeInfo = 4;
			return(0);
		}
  	}
	
	
	
	
  	///////////////////////////////////////////////////////////////////////////////////////////
  	//  Function: Move a config file to the backup directory
  	///////////////////////////////////////////////////////////////////////////////////////////
  	//
  	//  Moves an existing configuration file to the backup directory and removes then the
  	//  original file
  	//
  	//  Parameter:  	$strType    	Type of the configuration file
  	//					$strName    	Name of the configuration file
	//					$intConfigID	Configuration target ID
  	//
  	//  Return values:		0 = successful
	//						1 = error
	//						Status message is stored in message class variables
  	//
  	///////////////////////////////////////////////////////////////////////////////////////////
  	function moveFile($strType,$strName,$intConfigID) {
    	// Get directories
    	switch ($strType) {
      		case "host":    		$this->getConfigData($intConfigID,"hostconfig",$strConfigDir);
                					$this->getConfigData($intConfigID,"hostbackup",$strBackupDir);
                					break;
      		case "service": 		$this->getConfigData($intConfigID,"serviceconfig",$strConfigDir);
                					$this->getConfigData($intConfigID,"servicebackup",$strBackupDir);
                					break;
      		case "basic":   		$this->getConfigData($intConfigID,"basedir",$strConfigDir);
                					$this->getConfigData($intConfigID,"backupdir",$strBackupDir);
                					break;
      		case "nagiosbasic": 	$this->getConfigData($intConfigID,"nagiosbasedir",$strConfigDir);
                					$this->getConfigData($intConfigID,"backupdir",$strBackupDir);
                					break;
      		default:      			return(1);
    	}
    	// Get tranfer method
    	$this->getConfigData($intConfigID,"method",$strMethod);
    	// Local file system
		if ($strMethod == 1) {
      		// Save configuration file
      		if (file_exists($strConfigDir."/".$strName) && is_writable($strBackupDir) && is_writable($strConfigDir)) {
       			$strOldDate = date("YmdHis",time());
        		copy($strConfigDir."/".$strName,$strBackupDir."/".$strName."_old_".$strOldDate);
        		unlink($strConfigDir."/".$strName);
      		} else if (!is_writable($strBackupDir)) {
				$this->processClassMessage(translate('Cannot backup and delete the old configuration file (check the permissions)!')."::",$this->strErrorMessage);
        		return(1);
      		}
		// Remote file (FTP)
    	} else if ($strMethod == 2) {
			// Check connection
			if (!isset($this->resConnectId) || !is_resource($this->resConnectId) || ($this->resConnectType != "FTP")) {
				$booReturn = $this->getFTPConnection($intConfigID);
				if ($booReturn == 1) return(1); 
			}
        	// Save configuration file
        	$intFileStamp = ftp_mdtm($this->resConnectId, $strConfigDir."/".$strName);
        	if ($intFileStamp > -1) {
          		$strOldDate = date("YmdHis",time());
				$intErrorReporting = error_reporting();
				error_reporting(0);
          		$intReturn  = ftp_rename($this->resConnectId,$strConfigDir."/".$strName,$strBackupDir."/".$strName."_old_".$strOldDate);
          		if (!$intReturn) {
					$this->processClassMessage(translate('Cannot backup the old configuration file because the permissions are wrong (remote FTP)!')."::",$this->strErrorMessage);
					error_reporting($intErrorReporting);
					return(1);
          		}
				error_reporting($intErrorReporting);
			}
		// Remote file (SFTP)
    	} else if ($strMethod == 3) {
			// Check connection
			if (!isset($this->resConnectId) || !is_resource($this->resConnectId) || ($this->resConnectType != "SSH")) {
				$booReturn = $this->getSSHConnection($intConfigID);
				if ($booReturn == 1) return(1); 
			}
        	// Save configuration file
			if (is_array($this->sendSSHCommand('ls '.str_replace("//","/",$strConfigDir."/".$strName)))) {
				$arrInfo = ssh2_sftp_stat($this->resSFTP,str_replace("//","/",$strConfigDir."/".$strName));
				$intFileStamp = $arrInfo['mtime'];
        		if ($intFileStamp > -1) {
				
          			$strOldDate = date("YmdHis",time());
          			$intReturn  = ssh2_sftp_rename($this->resSFTP,$strConfigDir."/".$strName,$strBackupDir."/".$strName."_old_".$strOldDate);
					if (!$intReturn) {
						$this->processClassMessage(translate('Cannot backup the old configuration file because the permissions are wrong (remote SFTP)!')."::",$this->strErrorMessage);
						return(1);
          			}
				}
			}
		}
    	return(0);
  	}	

  	///////////////////////////////////////////////////////////////////////////////////////////
  	//  Function: Remove a config file
  	///////////////////////////////////////////////////////////////////////////////////////////
  	//
  	//  Parameter:  		$strType    	Filename including path to remove
	//						$intConfigID	Configuration target ID
  	//
  	//  Return values:		0 = successful
	//						1 = error
	//						Status message is stored in message class variables
  	//
  	///////////////////////////////////////////////////////////////////////////////////////////
  	function removeFile($strName,$intConfigID) {
    	// Get access method
    	$this->getConfigData($intConfigID,"method",$strMethod);
    	// Local file system
		if ($strMethod == 1) {
      		// Remove file if exists
      		if (file_exists($strName)) {
				if (is_writable($strName)) {
					unlink($strName);
				} else {
					$this->processClassMessage(translate('Cannot delete the file (wrong permissions)!').'::'.$strName."::",$this->strErrorMessage);
					return(1);
				}
      		} else {
				$this->processClassMessage(translate('Cannot delete the file (file does not exist)!').'::'.$strName."::",$this->strErrorMessage);
        		return(1);
      		}
		// Remote file (FTP)
    	} else if ($strMethod == 2) {
			// Check connection
			if (!isset($this->resConnectId) || !is_resource($this->resConnectId)) {
				$booReturn = $this->getFTPConnection($intConfigID);
				if ($booReturn == 1) return(1); 
			}
        	// Remove file if exists
        	$intFileStamp = ftp_mdtm($this->resConnectId, $strName);
        	if ($intFileStamp > -1) {
				$intErrorReporting = error_reporting();
				error_reporting(0);
          		$intReturn  = ftp_delete($this->resConnectId,$strName);
          		if (!$intReturn) {
					$this->processClassMessage(translate('Cannot delete file because the permissions are wrong (remote FTP)!')."::",$this->strErrorMessage);
					error_reporting($intErrorReporting);
					return(1);
          		}
				error_reporting($intErrorReporting);
        	} else {
				$this->processClassMessage(translate('Cannot delete file because it does not exists (remote FTP)!')."::",$this->strErrorMessage);
			}
		// Remote file (SSH)
    	} else if ($strMethod == 3) {
			// Check connection
			if (!isset($this->resConnectId) || !is_resource($this->resConnectId)) {
				$booReturn = $this->getSSHConnection($intConfigID);
				if ($booReturn == 1) return(1); 
			}
			// Remove file if exists
			if (is_array($this->sendSSHCommand('ls '.$strName))) {
				$intReturn = ssh2_sftp_unlink($this->resSFTP,$strName);
        		if (!$intReturn) {
					$this->processClassMessage(translate('Cannot delete file because the permissions are wrong (remote SFTP)!')."::",$this->strErrorMessage);
          		}
			} else {
				$this->processClassMessage(translate('Cannot delete file because it does not exists (remote SFTP)!')."::",$this->strErrorMessage);
			}
		}
    	return(0);
  	}
	
  	///////////////////////////////////////////////////////////////////////////////////////////
  	//  Function: Copy a config file
  	///////////////////////////////////////////////////////////////////////////////////////////
  	//
  	//  Parameter:  		$strFileRemote    	Remote file name
	//						$intConfigID		Configuration target id
	//						$strLocalFile		Local file name
	//						$intDirection		0 = from remote to local
	//											1 = from local to remote
  	//
  	//  Return values:		0 = successful
	//						1 = error
	//						Status message is stored in message class variables
  	//
  	///////////////////////////////////////////////////////////////////////////////////////////
	function configCopy($strFileRemote,$intConfigID,$strFileLokal,$intDirection=0) {
		// Get method
    	$this->getConfigData($intConfigID,"method",$strMethod);
		if ($strMethod == 2) {
			// Open ftp connection
			if (!isset($this->resConnectId) || !is_resource($this->resConnectId)) {
				$booReturn = $this->getFTPConnection($intConfigID);
				if ($booReturn == 1) return(1);
			}
			if ($intDirection == 0) {
				$intErrorReporting = error_reporting();
				error_reporting(0);
				if (!ftp_get($this->resConnectId,$strFileLokal,$strFileRemote,FTP_ASCII)) {
					$this->processClassMessage(translate('Cannot get the configuration file (FTP connection failed)!')."::",$this->strErrorMessage);
					ftp_close($this->resConnectId);
					error_reporting($intErrorReporting);
					return(1);
				}
				error_reporting($intErrorReporting);
			}
			if ($intDirection == 1) {
				$intErrorReporting = error_reporting();
				error_reporting(0);
				if (!ftp_put($this->resConnectId,$strFileRemote,$strFileLokal,FTP_ASCII)) {
					$this->processClassMessage(translate('Cannot write the configuration file (FTP connection failed)!')."::",$this->strErrorMessage);
					ftp_close($this->resConnectId);
					error_reporting($intErrorReporting);
					return(1);
				}
				error_reporting($intErrorReporting);
			}
			return(0);
		} else if ($strMethod == 3) {
			// Open ssh connection
			if (!isset($this->resConnectId) || !is_resource($this->resConnectId)) {
				$booReturn = $this->getSSHConnection($intConfigID);
				if ($booReturn == 1) return(1);
			}
			if ($intDirection == 0) {
				if (is_array($this->sendSSHCommand('ls '.$strFileRemote))) {	
					$intErrorReporting = error_reporting();
					error_reporting(0);
					if (!ssh2_scp_recv($this->resConnectId,$strFileRemote,$strFileLokal)) {
						$this->processClassMessage(translate('Cannot get the configuration file (SSH connection failed)!')."::",$this->strErrorMessage);
						error_reporting($intErrorReporting);
						return(1);
					}
					error_reporting($intErrorReporting);
				} else {
					$this->processClassMessage(translate('Cannot get the configuration file (remote file does not exist)!')."::",$this->strErrorMessage);
					return(1);
				}
				return(0);
			}
			if ($intDirection == 1) {
				$intErrorReporting = error_reporting();
				error_reporting(0);
				if (!ssh2_scp_send($this->resConnectId,$strFileLokal,$strFileRemote,0644)) {
					$this->processClassMessage(translate('Cannot write the configuration file (SSH connection failed)!')."::",$this->strErrorMessage);
					error_reporting($intErrorReporting);
					return(1);
				}
				error_reporting($intErrorReporting);
				return(0);
			}
		}
		return(1);
  	}

	///////////////////////////////////////////////////////////////////////////////////////////
  	//  Function: Write a config file (full version)
  	///////////////////////////////////////////////////////////////////////////////////////////
  	//
  	//  Writes a configuration file including all datasets of a configuration table or returns
  	//  the output as a text file for download.
  	//
  	//  Parameters:			$strTableName 	Table name
  	//  -----------			$intMode    	0 = Write file to filesystem
	//										1 = Return Textfile for download
  	//
  	//  Return value:		0 = successful
	//						1 = error
	//						Status message is stored in message class variables
  	//
  	///////////////////////////////////////////////////////////////////////////////////////////
  	function createConfig($strTableName,$intMode=0) {
    	// Do not create configs in common domain
		if ($this->intDomainId == 0) {
			$this->processClassMessage(translate('It is not possible to write config files directly from the common domain!')."::",$this->strErrorMessage);
			return(1);
		}
		// Get config strings
		$this->getConfigStrings($strTableName,$strFileString,$strOrderField);
		if ($strFileString == "") return 1;
		$strFile     = $strFileString.".cfg";
    	$setTemplate = $strFileString.".tpl.dat";
		// Get configuration targets
		$intFileWrite = 0;
		$arrConfigID  = $this->getConfigSets();
		if (($arrConfigID != 1) && is_array($arrConfigID)) {
			foreach($arrConfigID AS $intConfigID) {
				// Open configuration file in "write" mode
				if ($intMode == 0) {
					$booReturn = $this->getConfigFile($strFile,$intConfigID,0,$resConfigFile,$strConfigFile);
					if ($booReturn == 1) return 1;
				}
				// Load config template file
				$arrTplOptions = array('use_preg' => false);
				$configtp = new HTML_Template_IT($this->arrSettings['path']['base_path']."/templates/files/");
				$configtp->loadTemplatefile($setTemplate, true, true);
				$configtp->setOptions($arrTplOptions);
				$configtp->setVariable("CREATE_DATE",date("Y-m-d H:i:s",time()));
				$this->getConfigData($intConfigID,"version",$this->intNagVersion);
				$configtp->setVariable("NAGIOS_QL_VERSION",$this->arrSettings['db']['version']);
				if ($this->intNagVersion == 3) $strVersion = "Nagios 3.x config file";
				if ($this->intNagVersion == 2) $strVersion = "Nagios 2.9 config file";
				if ($this->intNagVersion == 1) $strVersion = "Nagios 2.x config file";
				$configtp->setVariable("VERSION",$strVersion);
				// Get config data from given table and define file name
				$this->getConfigData($intConfigID,"utf8_decode",$setUTF8Decode);
				$this->getDomainData("enable_common",$setEnableCommon);
				if ($setEnableCommon != 0) {
					$strDomainWhere = " (`config_id`=".$this->intDomainId." OR `config_id`=0) ";	
				} else {
					$strDomainWhere = " (`config_id`=".$this->intDomainId.") ";
				}
				$strSQL      = "SELECT * FROM `".$strTableName."` WHERE $strDomainWhere AND `active`='1' ORDER BY `".$strOrderField."`";
				$booReturn = $this->myDBClass->getDataArray($strSQL,$arrData,$intDataCount);
				if ($booReturn == false) {
					$this->processClassMessage(translate('Error while selecting data from database:')."::",$this->strErrorMessage);
					$this->processClassMessage($this->myDBClass->strErrorMessage,$this->strErrorMessage);
				} else if ($intDataCount != 0) {
					// Process every data set
					for ($i=0;$i<$intDataCount;$i++) {
						foreach($arrData[$i] AS $key => $value) {
							$intSkip = 0;
							if ($key == "id")     		$intDataId 	= $value;
							if ($key == "config_name") 	$key 		= "#NAGIOSQL_CONFIG_NAME";
							
							// UTF8 decoded vaules
							if ($setUTF8Decode == 1) $value = utf8_decode($value);
							
							// Pass special fields (NagiosQL data fields not used by Nagios itselves)
							if ($this->skipEntries($strTableName,$this->intNagVersion,$key,$value) == 1) continue;
					
							// Get relation data
							$intSkip = $this->getRelationData($strTableName,$configtp,$arrData[$i],$key,$value);
		
							// Rename field names
							$this->renameFields($strTableName,$intConfigID,$intDataId,$key,$value,$intSkip);
		
							// Inset data field
							if ($intSkip != 1) {
								// Insert fill spaces
								$strFillLen = (30-strlen($key));
								$strSpace = " ";
								for ($f=0;$f<$strFillLen;$f++) {
									$strSpace .= " ";
								}
								// Write key and value to template
								$configtp->setVariable("ITEM_TITLE",$key.$strSpace);
								// Short values
								if ((strlen($value) < 800) || ($this->intNagVersion != 3))  {
									$configtp->setVariable("ITEM_VALUE",$value);
									$configtp->parse("configline");
								// Long values
								} else {
									$arrValueTemp = explode(",",$value);
									$strValueNew  = "";
									$intArrCount  = count($arrValueTemp);
									$intCounter   = 0;
									$strSpace = " ";
									for ($f=0;$f<30;$f++) {
										$strSpace .= " ";
									}
									foreach($arrValueTemp AS $elem) {
										if (strlen($strValueNew) < 800) {
											$strValueNew .= $elem.",";
										} else {
											if (substr($strValueNew,-1) == ",") {
												$strValueNew = substr($strValueNew,0,-1);
											}
											if ($intCounter < $intArrCount) {
												$strValueNew = $strValueNew.",\\";
												$configtp->setVariable("ITEM_VALUE",$strValueNew);
												$configtp->parse("configline");
												$configtp->setVariable("ITEM_TITLE",$strSpace);
											} else {
												$configtp->setVariable("ITEM_VALUE",$strValueNew);
												$configtp->parse("configline");
												$configtp->setVariable("ITEM_TITLE",$strSpace);
											}
											$strValueNew = $elem.",";
										}
										$intCounter++;
									}
									if ($strValueNew != "") {
										if (substr($strValueNew,-1) == ",") {
											$strValueNew = substr($strValueNew,0,-1);
										}
										$configtp->setVariable("ITEM_VALUE",$strValueNew);
										$configtp->parse("configline");
										$strValueNew = "";
									}
								}
							}
						}
		
						// Special processing for time periods
						if ($strTableName == "tbl_timeperiod") {
							$strSQLTime = "SELECT `definition`, `range` FROM `tbl_timedefinition` WHERE `tipId` = ".$arrData[$i]['id'];
							$booReturn  = $this->myDBClass->getDataArray($strSQLTime,$arrDataTime,$intDataCountTime);
							if ($booReturn && $intDataCountTime != 0) {
								foreach ($arrDataTime AS $data) {
									// Skip other values than weekdays in nagios version below 3
									if ($this->intNagVersion != 3) {
										$arrWeekdays = array('monday','tuesday','wednesday','thursday','friday','saturday','sunday');
										if (!in_array($data['definition'],$arrWeekdays)) continue;
									}									
									// Insert fill spaces
									$strFillLen = (30-strlen($data['definition']));
									$strSpace = " ";
									for ($f=0;$f<$strFillLen;$f++) {
										$strSpace .= " ";
									}
									// Write key and value
									$configtp->setVariable("ITEM_TITLE",$data['definition'].$strSpace);
									$configtp->setVariable("ITEM_VALUE",$data['range']);
									$configtp->parse("configline");
								}
							}
						}
						// Write configuration set
						$configtp->parse("configset");
					}
				} else {
					$this->myDataClass->writeLog(translate('Writing of the configuration failed - no dataset or not activated dataset found'));
					$this->processClassMessage(translate('Writing of the configuration failed - no dataset or not activated dataset found')."::",$this->strErrorMessage);
					$configtp->parse();
					$booReturn 		= $this->writeConfigFile($configtp->get(),$strFile,0,$intConfigID,$resConfigFile,$strConfigFile);
					return(1);
				}
				$configtp->parse();
				// Write configuration file
				if ($intMode == 0) {
					$booReturn 		= $this->writeConfigFile($configtp->get(),$strFile,0,$intConfigID,$resConfigFile,$strConfigFile);
					$intFileWrite  += $booReturn;
				// Return configuration file (download)
				} else if ($intMode == 1) {
					$configtp->show();
					return(0);
				}
			}
			if ($intFileWrite != 0) return(1);
			return(0);
		} else {
			$strCheckConfig = translate('Warning: no configuration target defined!');
			return(1);
		}
	}
	
	///////////////////////////////////////////////////////////////////////////////////////////
  	//  Function: Write a config file (single dataset version)
  	///////////////////////////////////////////////////////////////////////////////////////////
  	//
  	//  Writes a configuration file including one single datasets of a configuration table or 
  	//  returns the output as a text file for download.
  	//
  	//  Parameters:			$strTableName 	Table name
  	//  -----------			$intDbId		Data ID
	//						$intMode    	0 = Write file to filesystem
	//										1 = Return Textfile fot download
  	//
  	//  Return value:		0 = successful
	//						1 = error
	//						Status message is stored in message class variables
  	//
  	///////////////////////////////////////////////////////////////////////////////////////////
	function createConfigSingle($strTableName,$intDbId = 0,$intMode = 0) {
		// Do not create configs in common domain
		if ($this->intDomainId == 0) {
			$this->processClassMessage(translate('It is not possible to write config files directly from the common domain!')."::",$this->strErrorMessage);
			return(1);
		}
    	// Get all data from table
		$this->getDomainData("enable_common",$setEnableCommon);
		if ($setEnableCommon != 0) {
			$strDomainWhere = " (`config_id`=".$this->intDomainId." OR `config_id`=0) ";	
		} else {
			$strDomainWhere = " (`config_id`=".$this->intDomainId.") ";
		}
		if ($intDbId == 0) {
    		$strSQL = "SELECT * FROM `".$strTableName."` WHERE $strDomainWhere AND `active`='1' ORDER BY `id`";
		} else {
    		$strSQL = "SELECT * FROM `".$strTableName."` WHERE $strDomainWhere AND `active`='1' AND `id`=$intDbId";
		}
    	$booReturn = $this->myDBClass->getDataArray($strSQL,$arrData,$intDataCount);
    	if (($booReturn != false) && ($intDataCount != 0)) {
      		$intError = 0;
			for ($i=0;$i<$intDataCount;$i++) {
        		// Process form POST variable
        		$strChbName = "chbId_".$arrData[$i]['id'];
        		// Check if this POST variable exists or the data ID parameter matches
        		if (isset($_POST[$strChbName]) || (($intDbId != 0) && ($intDbId == $arrData[$i]['id']))) {
					// Define variable names based on table name
					switch($strTableName) {
						case "tbl_host":
							$strConfigName = $arrData[$i]['host_name'];
							$intDomainId   = $arrData[$i]['config_id'];
							$setTemplate   = "hosts.tpl.dat";
							$intType 	   = 1;
							$strSQLData    = "SELECT * FROM `".$strTableName."` WHERE `host_name`='$strConfigName' AND `active`='1' AND `config_id`=$intDomainId";
							break;
						case "tbl_service":
							$strConfigName = $arrData[$i]['config_name'];
							$intDomainId   = $arrData[$i]['config_id'];
							$setTemplate   = "services.tpl.dat";
							$intType	   = 2;
							$strSQLData    = "SELECT * FROM `".$strTableName."` WHERE `config_name`='$strConfigName' AND `active`='1' AND `config_id`=$intDomainId 
											  ORDER BY `service_description`";
							break;
					}
					$strFile = $strConfigName.".cfg";					
					// Get configuration targets
					$arrConfigID  = $this->getConfigSets();
					if (($arrConfigID != 1) && is_array($arrConfigID)) {
						foreach($arrConfigID AS $intConfigID) {		
							$this->myDBClass->strErrorMessage = "";
							// Open configuration file in "write" mode
							if ($intMode == 0) {
								$booReturn = $this->getConfigFile($strFile,$intConfigID,$intType,$resConfigFile,$strConfigFile);
								if ($booReturn == 1) {
									return(1);
								}
							}
							// Load config template file
							$arrTplOptions = array('use_preg' => false);
							$configtp = new HTML_Template_IT($this->arrSettings['path']['base_path']."/templates/files/");
							$configtp->loadTemplatefile($setTemplate, true, true);
							$configtp->setOptions($arrTplOptions);
							$configtp->setVariable("CREATE_DATE",date("Y-m-d H:i:s",time()));
							if ($this->intNagVersion == 0) {
								$this->getConfigData($intConfigID,"version",$this->intNagVersion);
							}
							$configtp->setVariable("NAGIOS_QL_VERSION",$this->arrSettings['db']['version']);
							if ($this->intNagVersion == 3) $strVersion = "Nagios 3.x config file";
							if ($this->intNagVersion == 2) $strVersion = "Nagios 2.9 config file";
							if ($this->intNagVersion == 1) $strVersion = "Nagios 2.x config file";
							$configtp->setVariable("VERSION",$strVersion);
							
							// Alle passenden Datensätze holen
							$booReturn = $this->myDBClass->getDataArray($strSQLData,$arrDataConfig,$intDataCountConfig);
							if ($booReturn == false) {
								$this->processClassMessage(translate('Error while selecting data from database:')."::".$this->myDBClass->strErrorMessage."::",$this->strErrorMessage);
							} else if ($intDataCountConfig != 0) {
								// Process every data set
								for ($y=0;$y<$intDataCountConfig;$y++) {
									foreach($arrDataConfig[$y] AS $key => $value) {
										$intSkip = 0;
										if ($key == "id")     		 $intDataId 	= $value;
										if ($key == "config_name") 	 $key 			= "#NAGIOSQL_CONFIG_NAME";
										
										// UTF8 decoded vaules
										//if ($setUTF8Decode == 1) $value = utf8_decode($value);
										
										// Pass special fields (NagiosQL data fields not used by Nagios itselves)
										if ($this->skipEntries($strTableName,$this->intNagVersion,$key,$value) == 1) continue;
		
										// Get relation data
										$intSkip = $this->getRelationData($strTableName,$configtp,$arrDataConfig[$y],$key,$value);
		
										// Rename field names
										$this->renameFields($strTableName,$intConfigID,$intDataId,$key,$value,$intSkip);
		
										// Inset data field
										if ($intSkip != 1) {
											// Insert fill spaces
											$strFillLen = (30-strlen($key));
											$strSpace = " ";
											for ($f=0;$f<$strFillLen;$f++) {
												$strSpace .= " ";
											}
											// Write key and value to template
											$configtp->setVariable("ITEM_TITLE",$key.$strSpace);
											// Short values
											if ((strlen($value) < 800) || ($this->intNagVersion != 3))  {
												$configtp->setVariable("ITEM_VALUE",$value);
												$configtp->parse("configline");
											// Long values
											} else {
												$arrValueTemp = explode(",",$value);
												$strValueNew  = "";
												$intArrCount  = count($arrValueTemp);
												$intCounter   = 0;
												$strSpace = " ";
												for ($f=0;$f<30;$f++) {
													$strSpace .= " ";
												}
												foreach($arrValueTemp AS $elem) {
													if (strlen($strValueNew) < 800) {
														$strValueNew .= $elem.",";
													} else {
														if (substr($strValueNew,-1) == ",") {
															$strValueNew = substr($strValueNew,0,-1);
														}
														if ($intCounter < $intArrCount) {
															$strValueNew = $strValueNew.",\\";
															$configtp->setVariable("ITEM_VALUE",$strValueNew);
															$configtp->parse("configline");
															$configtp->setVariable("ITEM_TITLE",$strSpace);
														} else {
															$configtp->setVariable("ITEM_VALUE",$strValueNew);
															$configtp->parse("configline");
															$configtp->setVariable("ITEM_TITLE",$strSpace);
														}
														$strValueNew = $elem.",";
													}
													$intCounter++;
												}
												if ($strValueNew != "") {
													if (substr($strValueNew,-1) == ",") {
														$strValueNew = substr($strValueNew,0,-1);
													}
													$configtp->setVariable("ITEM_VALUE",$strValueNew);
													$configtp->parse("configline");
													$strValueNew = "";
												}
											}
										}
									}
									// Write configuration set
									$configtp->parse("configset");
								}
								$configtp->parse();
								// Write configuration file
								if ($intMode == 0) {
									$booReturn = $this->writeConfigFile($configtp->get(),$strFile,$intType,$intConfigID,$resConfigFile,$strConfigFile);
									if ($booReturn == 1) $intError++;
								// Return configuration file (download)
								} else if ($intMode == 1) {
									$configtp->show();
								}
							}
						}
					}
        		}
      		}
    	} else {
      		$this->myDataClass->writeLog(translate('Writing of the configuration failed - no dataset or not activated dataset found'));
			$this->processClassMessage(translate('Writing of the configuration failed - no dataset, not activated dataset found or you do not have write permission.')."::",$this->strErrorMessage);
      		return(1);
    	}
		if ($intError == 0) return(0);
		return(1);
  	}
 
    //3.1 HELP FUNCTIONS
	///////////////////////////////////////////////////////////////////////////////////////////
  	//  Help function: Get config parameters
  	///////////////////////////////////////////////////////////////////////////////////////////
	//  $strTableName		-> Table name
	//  $strFileString		-> File name string
	//  $strOrderField		-> Order field name 		(return value)
	///////////////////////////////////////////////////////////////////////////////////////////
  	function getConfigStrings($strTableName,&$strFileString,&$strOrderField) {
		switch($strTableName) {
      		case "tbl_timeperiod":      	$strFileString  = "timeperiods";
                      						$strOrderField  = "timeperiod_name";
                      						break;
      		case "tbl_command":       		$strFileString  = "commands";
                      						$strOrderField  = "command_name";
                      						break;
      		case "tbl_contact":       		$strFileString  = "contacts";
                      						$strOrderField  = "contact_name";
                      						break;
      		case "tbl_contacttemplate": 	$strFileString  = "contacttemplates";
                      						$strOrderField  = "template_name";
                      						break;
      		case "tbl_contactgroup":    	$strFileString  = "contactgroups";
                      						$strOrderField  = "contactgroup_name";
                      						break;
      		case "tbl_hosttemplate":    	$strFileString  = "hosttemplates";
                      						$strOrderField  = "template_name";
                      						break;
      		case "tbl_hostgroup":     		$strFileString  = "hostgroups";
                      						$strOrderField  = "hostgroup_name";
                      						break;
      		case "tbl_servicetemplate": 	$strFileString  = "servicetemplates";
                      						$strOrderField  = "template_name";
                      						break;
      		case "tbl_servicegroup":    	$strFileString  = "servicegroups";
                      						$strOrderField  = "servicegroup_name";
                      						break;
      		case "tbl_hostdependency":		$strFileString  = "hostdependencies";
                      						$strOrderField  = "dependent_host_name";
                      						break;
      		case "tbl_hostescalation": 	 	$strFileString  = "hostescalations";
                      						$strOrderField  = "host_name`,`hostgroup_name";
                      						break;
      		case "tbl_hostextinfo":     	$strFileString  = "hostextinfo";
                      						$strOrderField  = "host_name";
                      						break;
      		case "tbl_servicedependency": 	$strFileString  = "servicedependencies";
                      						$strOrderField  = "config_name";
                      						break;
      		case "tbl_serviceescalation": 	$strFileString  = "serviceescalations";
                      						$strOrderField  = "config_name";
                      						break;
      		case "tbl_serviceextinfo":    	$strFileString  = "serviceextinfo";
                      						$strOrderField  = "host_name";
                      						break;
      		default:            			$strFileString  = "";
                      						$strOrderField  = "";
    	}
	}
	///////////////////////////////////////////////////////////////////////////////////////////
  	//  Help function: Open configuration file
  	///////////////////////////////////////////////////////////////////////////////////////////
	//  $strFile			-> File name
	// 	$intConfigID		-> Configuration ID
	//  $intType			-> Type ID
	//  $resConfigFile		-> Temporary or configuration file ressource (return value)
	//  $strConfigFile		-> Configuration file name					 (return value)
	///////////////////////////////////////////////////////////////////////////////////////////
	function getConfigFile($strFile,$intConfigID,$intType,&$resConfigFile,&$strConfigFile) {
		// Get config data
		if ($intType == 1) {
            $this->getConfigData($intConfigID,"hostconfig",$strBaseDir);
            $this->getConfigData($intConfigID,"hostbackup",$strBackupDir);
			$strType = 'host';
		} else if ($intType == 2) {
			$this->getConfigData($intConfigID,"serviceconfig",$strBaseDir);
			$this->getConfigData($intConfigID,"servicebackup",$strBackupDir);
			$strType = 'service';
		} else {
			$this->getConfigData($intConfigID,"basedir",$strBaseDir);
			$this->getConfigData($intConfigID,"backupdir",$strBackupDir);
			$strType = 'basic';
		}
      	$booReturn = $this->getConfigData($intConfigID,"method",$strMethod);
		// Backup config file
		$intReturn = $this->moveFile($strType,$strFile,$intConfigID);
		if ($intReturn == 1) return(1);
      	// Method 1 - local file system
		if ($strMethod == 1) {
			// Open the config file
        	if (is_writable($strBaseDir."/".$strFile) || ((!file_exists($strBaseDir."/".$strFile) && (is_writable($strBaseDir))) )) {
				$strConfigFile = $strBaseDir."/".$strFile;
				$resConfigFile = fopen($strConfigFile,"w");
				chmod($strConfigFile, 0644);
        	} else {
          		$this->myDataClass->writeLog(translate('Configuration write failed:')." ".$strFile);
				$this->processClassMessage(translate('Cannot open/overwrite the configuration file (check the permissions)!')."::",$this->strErrorMessage);
          		return(1);
        	}
      	// Method 2 - ftp access
	  	} else if ($strMethod == 2) {
        	// Set up basic connection
        	$booReturn    		= $this->getConfigData($intConfigID,"server",$strServer);
        	$this->resConnectId = ftp_connect($strServer);
        	// Login with username and password
        	$booReturn    = $this->getConfigData($intConfigID,"user",$strUser);
        	$booReturn    = $this->getConfigData($intConfigID,"password",$strPasswd);
        	$login_result = ftp_login($this->resConnectId, $strUser, $strPasswd);
        	// Check connection
        	if ((!$this->resConnectId) || (!$login_result)) {
          		$this->myDataClass->writeLog(translate('Configuration write failed (FTP connection failed):')." ".$strFile);
				$this->processClassMessage(translate('Cannot open/overwrite the configuration file (FTP connection failed)!')."::",$this->strErrorMessage);
          		return(1);
        	} else {
				// Open the config file
				if (isset($this->arrSettings['path']) && isset($this->arrSettings['path']['tempdir'])) {
					$strConfigFile = tempnam($this->arrSettings['path']['tempdir'], 'nagiosql');
				} else {
					$strConfigFile = tempnam(sys_get_temp_dir(), 'nagiosql');
				}
				$resConfigFile = fopen($strConfigFile,"w");
			}
       	// Method 3 - ssh access
	  	} else if ($strMethod == 3) {
			// Check connection
			if (!isset($this->resConnectId) || !is_resource($this->resConnectId)) {
				$booReturn = $this->getSSHConnection();
				if ($booReturn == 1) return(1); 
			}
			// Open the config file
			if (isset($this->arrSettings['path']) && isset($this->arrSettings['path']['tempdir'])) {
				$strConfigFile = tempnam($this->arrSettings['path']['tempdir'], 'nagiosql');
			} else {
				$strConfigFile = tempnam(sys_get_temp_dir(), 'nagiosql');
			}
			$resConfigFile = fopen($strConfigFile,"w");
      	}
    }
	///////////////////////////////////////////////////////////////////////////////////////////
  	//  Help function: Write configuration file
  	///////////////////////////////////////////////////////////////////////////////////////////
	//  $strData			-> Data string
	//  $strFile			-> File name
	//  $intType			-> Type ID
	//  $intConfigID		-> Configuration target ID
	//  $resConfigFile		-> Temporary or configuration file ressource
	//  $strConfigFile		-> Configuration file name
	///////////////////////////////////////////////////////////////////////////////////////////
	function writeConfigFile($strData,$strFile,$intType,$intConfigID,$resConfigFile,$strConfigFile) {
		// Get config data
		if ($intType == 1) {
            $this->getConfigData($intConfigID,"hostconfig",$strBaseDir);
		} else if ($intType == 2) {
			$this->getConfigData($intConfigID,"serviceconfig",$strBaseDir);
		} else {
			$this->getConfigData($intConfigID,"basedir",$strBaseDir);
		}
		$booReturn = $this->getConfigData($intConfigID,"method",$strMethod);
		$strData  = str_replace("\r\n","\n",$strData);
		fwrite($resConfigFile,$strData);
		// Local filesystem
		if ($strMethod == 1) {
			fclose($resConfigFile);
		// FTP access
		} else if ($strMethod == 2) {
			// SSH Possible
			if (!function_exists('ftp_put')) {
				$this->processClassMessage(translate('FTP module not loaded!')."::",$this->strErrorMessage);
				return(1);
			}
			$intErrorReporting = error_reporting();
			error_reporting(0);
			if (!ftp_put($this->resConnectId,$strBaseDir."/".$strFile,$strConfigFile,FTP_ASCII)) {
				$arrError = error_get_last();
				error_reporting($intErrorReporting);
				$this->processClassMessage(translate('Cannot open/overwrite the configuration file (FTP connection failed)!')."::",$this->strErrorMessage);
				if ($arrError['message'] != "") $this->processClassMessage($arrError['message']."::",$this->strErrorMessage);
				ftp_close($this->resConnectId);
				fclose($resConfigFile);
				unlink($strConfigFile);
				return(1);
			}
			error_reporting($intErrorReporting);
			ftp_close($this->resConnectId);
			fclose($resConfigFile);
		// SSH access	
		} else if ($strMethod == 3) {
			// SSH Possible
			if (!function_exists('ssh2_scp_send')) {
				$this->processClassMessage(translate('SSH module not loaded!')."::",$this->strErrorMessage);
				return(1);
			}
			$intErrorReporting = error_reporting();
			error_reporting(0);
			if (!ssh2_scp_send($this->resConnectId,$strConfigFile,$strBaseDir."/".$strFile,0644)) {
				$arrError = error_get_last();
				error_reporting($intErrorReporting);
				$this->processClassMessage(translate('Cannot open/overwrite the configuration file (remote SFTP)!')."::",$this->strErrorMessage);
				if ($arrError['message'] != "") $this->processClassMessage($arrError['message']."::",$this->strErrorMessage);
				$this->resConnectId = null;
				return(1);
			}
			$arrError = error_get_last();
			error_reporting($intErrorReporting);
			fclose($resConfigFile);
			unlink($strConfigFile);
			$this->resConnectId = null;
		}
		$this->myDataClass->writeLog(translate('Configuration successfully written:')." ".$strFile);
		$this->processClassMessage(translate('Configuration file successfully written!')."::",$this->strInfoMessage);
		return(0);
	}
	///////////////////////////////////////////////////////////////////////////////////////////
  	//  Help function: Return related value
  	///////////////////////////////////////////////////////////////////////////////////////////
	//  $strTableName		-> Table name
	//  $resTemplate		-> Template ressource
	//  $arrData			-> Dataset array
	//  $strDataKey			-> Data key
	//  $strDataValue		-> Data value	(return value)
	///////////////////////////////////////////////////////////////////////////////////////////
	function getRelationData($strTableName,$resTemplate,$arrData,$strDataKey,&$strDataValue) {
		// Pass function for tbl_command
		if ($strTableName == 'tbl_command') return(0);
		// Get relation info and store the value in a class variable (speedup export)
		if 	($this->strRelTable != $strTableName) {
			$intReturn = $this->myDataClass->tableRelations($strTableName,$arrRelations);
			$this->strRelTable = $strTableName;
			$this->arrRelData  = $arrRelations;
		} else {
			$arrRelations = $this->arrRelData;
			$intReturn = 1;
		}
		if (($intReturn == 0) || (!is_array($arrRelations))) return(1);
		// Common domain is enabled?
		$this->getDomainData("enable_common",$intCommonEnable);
		if ($intCommonEnable == 1) {
			$strDomainWhere1 = " (`config_id`=".$this->intDomainId." OR `config_id`=0) ";
			$strDomainWhere2 = " (`tbl_service`.`config_id`=".$this->intDomainId." OR `tbl_service`.`config_id`=0) ";
		} else {
			$strDomainWhere1 = " `config_id`=".$this->intDomainId." ";
			$strDomainWhere2 = " `tbl_service`.`config_id`=".$this->intDomainId." ";
		}
		// Process relations
        foreach($arrRelations AS $elem) {
			if ($elem['fieldName'] == $strDataKey) {
                // Process normal 1:n relations (1 = only data / 2 = including a * value)
                if (($elem['type'] == 2) && (($strDataValue == 1) || ($strDataValue == 2))) {
                  	$strSQLRel = "SELECT `".$elem['tableName1']."`.`".$elem['target1']."`, `".$elem['linkTable']."`.`exclude` FROM `".$elem['linkTable']."`
                            	  LEFT JOIN `".$elem['tableName1']."` ON `".$elem['linkTable']."`.`idSlave` = `".$elem['tableName1']."`.`id`
                            	  WHERE `idMaster`=".$arrData['id']." AND `active`='1' AND $strDomainWhere1
                            	  ORDER BY `".$elem['tableName1']."`.`".$elem['target1']."`";
                  	$booReturn = $this->myDBClass->getDataArray($strSQLRel,$arrDataRel,$intDataCountRel);
					if ($booReturn && ($intDataCountRel != 0)) {
                    	// Rewrite $strDataValue with returned relation data
                    	if ($strDataValue == 2) {$strDataValue = "*,";} else {$strDataValue = "";}
                    	foreach ($arrDataRel AS $data) {
					  		if ($data['exclude'] == 0) {	
                      			$strDataValue .= $data[$elem['target1']].",";
					  		} else if ($this->intNagVersion >=3) {
								$strDataValue .= "!".$data[$elem['target1']].",";   
					  		}
                    	}
                    	$strDataValue = substr($strDataValue,0,-1);
						if ($strDataValue == "") return(1);
					} else {
						if ($strDataValue == 2) {$strDataValue = "*";} else {return(1);}
                	}
                // Process normal 1:1 relations
                } else if ($elem['type'] == 1) {
                  	if (($elem['tableName1'] == "tbl_command") && (substr_count($arrData[$elem['fieldName']],"!") != 0)) {
						$arrField   = explode("!",$arrData[$elem['fieldName']]);
                    	$strCommand = strchr($arrData[$elem['fieldName']],"!");
                    	$strSQLRel  = "SELECT `".$elem['target1']."` FROM `".$elem['tableName1']."`
                             		   WHERE `id`=".$arrField[0]."  AND `active`='1' AND $strDomainWhere1";
                  	} else {
                    	$strSQLRel  = "SELECT `".$elem['target1']."` FROM `".$elem['tableName1']."`
                                 	   WHERE `id`=".$arrData[$elem['fieldName']]."  AND `active`='1' AND $strDomainWhere1";
                  	}
                  	$booReturn = $this->myDBClass->getDataArray($strSQLRel,$arrDataRel,$intDataCountRel);
                  	if ($booReturn && ($intDataCountRel != 0)) {
                    	// Rewrite $strDataValue with returned relation data
                    	if (($elem['tableName1'] == "tbl_command") && (substr_count($strDataValue,"!") != 0)) {
							$strDataValue = $arrDataRel[0][$elem['target1']].$strCommand;
                    	} else {
                      		$strDataValue = $arrDataRel[0][$elem['target1']];
                    	}
                  	} else {
                    	if (($elem['tableName1'] == "tbl_command") && (substr_count($strDataValue,"!") != 0) && ($arrField[0] == -1)) {
							$strDataValue = "null";
						} else {
							return(1);
						}
                  	}
                // Process normal 1:n relations with special table and idSort (template tables)
                } else if (($elem['type'] == 3) && ($strDataValue == 1)) {
                  	$strSQLMaster = "SELECT * FROM `".$elem['linkTable']."` WHERE `idMaster` = ".$arrData['id']." ORDER BY idSort";
                  	$booReturn    = $this->myDBClass->getDataArray($strSQLMaster,$arrDataMaster,$intDataCountMaster);
					if ($booReturn && ($intDataCountMaster != 0)) {
						// Rewrite $strDataValue with returned relation data
                    	$strDataValue = "";
                    	foreach ($arrDataMaster AS $data) {
                      		if ($data['idTable'] == 1) {
                        		$strSQLName = "SELECT `".$elem['target1']."` FROM `".$elem['tableName1']."` WHERE `active`='1' AND $strDomainWhere1 AND `id` = ".$data['idSlave'];
                      		} else {
                        		$strSQLName = "SELECT `".$elem['target2']."` FROM `".$elem['tableName2']."` WHERE `active`='1' AND $strDomainWhere1 AND `id` = ".$data['idSlave'];
                      		}
                      		$strDataValue .= $this->myDBClass->getFieldData($strSQLName).",";
                    	}
                    	$strDataValue = substr($strDataValue,0,-1);
                  	} else {
                    	return(1);
                  	}
                // Process special 1:n:str relations with string values (servicedependencies)
                } else if (($elem['type'] == 6) && (($strDataValue == 1) || ($strDataValue == 2))) {
                  	$strSQLRel = "SELECT `".$elem['linkTable']."`.`strSlave`, `".$elem['linkTable']."`.`exclude` 
								  FROM `".$elem['linkTable']."`
								  LEFT JOIN `tbl_service` ON `".$elem['linkTable']."`.`idSlave`=`tbl_service`.`id`
								  WHERE `".$elem['linkTable']."`.`idMaster`=".$arrData['id']." AND `active`='1' AND $strDomainWhere1
                            	  ORDER BY `".$elem['linkTable']."`.`strSlave`";
                  	$booReturn = $this->myDBClass->getDataArray($strSQLRel,$arrDataRel,$intDataCountRel);
					if ($booReturn && ($intDataCountRel != 0)) {
                    	// Rewrite $strDataValue with returned relation data
                    	if ($strDataValue == 2) {$strDataValue = "*,";} else {$strDataValue = "";}
                    	foreach ($arrDataRel AS $data) {
					  		if ($data['exclude'] == 0) {	
								$strDataValue .= $data['strSlave'].",";
					  		} else if ($this->intNagVersion >=3) {
								$strDataValue .= "!".$data['strSlave'].","; 
					  		}
                    	}
                    	$strDataValue = substr($strDataValue,0,-1);
						if ($strDataValue == "") return(1);
					} else {
						if ($strDataValue == 2) {$strDataValue = "*";} else {return(1);}
                	}
                // Process special relations for free variables
                } else if (($elem['type'] == 4) && ($strDataValue == 1) && ($this->intNagVersion >= 3)) {
                  	$strSQLVar = "SELECT * FROM `tbl_variabledefinition` LEFT JOIN `".$elem['linkTable']."` ON `id` = `idSlave`
                          		  WHERE `idMaster`=".$arrData['id']." ORDER BY `name`";
                  	$booReturn = $this->myDBClass->getDataArray($strSQLVar,$arrDSVar,$intDCVar);
                  	if ($booReturn && ($intDCVar != 0)) {
                    	foreach ($arrDSVar AS $vardata) {
                      		// Insert fill spaces
							$strFillLen = (30-strlen($vardata['name']));
							$strSpace = " ";
							for ($f=0;$f<$strFillLen;$f++) {
								$strSpace .= " ";
							}
                      		$resTemplate->setVariable("ITEM_TITLE",$vardata['name'].$strSpace);
                      		$resTemplate->setVariable("ITEM_VALUE",$vardata['value']);
                      		$resTemplate->parse("configline");
                    	}
                  	}
                  	return(1);
                // Process special relations for service groups
                } else if (($elem['type'] == 5) && ($strDataValue == 1)) {
                  	$strSQLMaster = "SELECT * FROM `".$elem['linkTable']."` WHERE `idMaster` = ".$arrData['id'];
                  	$booReturn    = $this->myDBClass->getDataArray($strSQLMaster,$arrDataMaster,$intDataCountMaster);
                  	if ($booReturn && ($intDataCountMaster != 0)) {
						// Rewrite $strDataValue with returned relation data
                    	$strDataValue = "";
                    	foreach ($arrDataMaster AS $data) {
                      		if ($data['idSlaveHG'] != 0) {
                        			$strService = $this->myDBClass->getFieldData("SELECT `".$elem['target2']."` FROM `".$elem['tableName2'].
																			     "` WHERE `id` = ".$data['idSlaveS']);
									$strSQLHG1  = "SELECT `host_name` FROM `tbl_host` LEFT JOIN `tbl_lnkHostgroupToHost` ON `id`=`idSlave` 
											       WHERE `idMaster`=".$data['idSlaveHG']."  AND `active`='1' AND $strDomainWhere1";
                        			$booReturn  = $this->myDBClass->getDataArray($strSQLHG1,$arrHG1,$intHG1);
                        			if ($booReturn && ($intHG1 != 0)) {
                          				foreach ($arrHG1 AS $elemHG1) {
                            				if (substr_count($strDataValue,$elemHG1['host_name'].",".$strService) == 0) {
                              					$strDataValue .= $elemHG1['host_name'].",".$strService.",";
                            				}
                          				}
                        			}
                        			$strSQLHG2  = "SELECT `host_name` FROM `tbl_host` LEFT JOIN `tbl_lnkHostToHostgroup` ON `id`=`idMaster` 
									  		 	   WHERE `idSlave`=".$data['idSlaveHG']."  AND `active`='1' AND $strDomainWhere1";
                        			$booReturn  = $this->myDBClass->getDataArray($strSQLHG2,$arrHG2,$intHG2);
                        			if ($booReturn && ($intHG2 != 0)) {
                          				foreach ($arrHG2 AS $elemHG2) {
                            				if (substr_count($strDataValue,$elemHG2['host_name'].",".$strService) == 0) {
                              					$strDataValue .= $elemHG2['host_name'].",".$strService.",";
                            				}
                          				}
                        			}
                      			} else {
                        			$strHost   	 = $this->myDBClass->getFieldData("SELECT `".$elem['target1']."` FROM `".$elem['tableName1']."` ". 
																				  "WHERE `id` = ".$data['idSlaveH']."  AND `active`='1' AND $strDomainWhere1");
                        			$strService  = $this->myDBClass->getFieldData("SELECT `".$elem['target2']."` FROM `".$elem['tableName2']."` ".
																				  "WHERE `id` = ".$data['idSlaveS']."  AND `active`='1' AND $strDomainWhere1");
                        			if (($strHost != "") && ($strService != "")) {
                          				if (substr_count($strDataValue,$strHost.",".$strService) == 0) {
                            				$strDataValue .= $strHost.",".$strService.",";
                          			}
                        		}
                      		}
                    	}
                    	$strDataValue = substr($strDataValue,0,-1);
						if ($strDataValue == "") return(1);
                  	} else {
                    	return(1);
                  	}
                // Process "*"
                } else if ($strDataValue == 2) {
                  	$strDataValue = "*";
                } else {
                  	return(1);
                }
            }
		}
	}
	///////////////////////////////////////////////////////////////////////////////////////////
  	//  Help function: Rename field names 
  	///////////////////////////////////////////////////////////////////////////////////////////
	//  $strTableName		-> Table name
	//  $intConfigID		-> Configuration target ID
	//  $intDataId			-> Data ID
	//  $key				-> Data key		(return value)
	//  $value				-> Data value	(return value)
	//  $intSkip			-> Skip value	(return value)
	///////////////////////////////////////////////////////////////////////////////////////////
	function renameFields($strTableName,$intConfigID,$intDataId,&$key,&$value,&$intSkip) {
		if ($this->intNagVersion == 0) {
			$this->getConfigData($intConfigID,"version",$this->intNagVersion);
		}
		// Picture path
		if ($this->strPicPath == "none") {
			$this->getConfigData($intConfigID,"picturedir",$this->strPicPath);
		}
		if ($key == "icon_image") 		$value = $this->strPicPath.$value;
		if ($key == "vrml_image") 		$value = $this->strPicPath.$value;
		if ($key == "statusmap_image") 	$value = $this->strPicPath.$value;
		// Tables
		if ($strTableName == "tbl_host") {
		  	if ($key == "use_template")   	$key = "use";
		  	$strVIValues  = "active_checks_enabled,passive_checks_enabled,check_freshness,obsess_over_host,event_handler_enabled,";
		  	$strVIValues .= "flap_detection_enabled,process_perf_data,retain_status_information,retain_nonstatus_information,";
		  	$strVIValues .= "notifications_enabled";
		  	if (in_array($key,explode(",",$strVIValues))) {
				if ($value == -1)         	$value = "null";
				if ($value == 3)        	$value = "null";
		 	}
		  	if ($key == "parents")      	$value = $this->checkTpl($value,"parents_tploptions","tbl_host",$intDataId,$intSkip);
		  	if ($key == "hostgroups")   	$value = $this->checkTpl($value,"hostgroups_tploptions","tbl_host",$intDataId,$intSkip);
		  	if ($key == "contacts")     	$value = $this->checkTpl($value,"contacts_tploptions","tbl_host",$intDataId,$intSkip);
		  	if ($key == "contact_groups") 	$value = $this->checkTpl($value,"contact_groups_tploptions","tbl_host",$intDataId,$intSkip);
		  	if ($key == "use")        		$value = $this->checkTpl($value,"use_template_tploptions","tbl_host",$intDataId,$intSkip);
			if ($key == "check_command") 	$value = str_replace("\::bang::","\!",$value);
			if ($key == "check_command") 	$value = str_replace("::bang::","\!",$value);
		}
		if ($strTableName == "tbl_service") {
		  	if ($key == "use_template")   	$key = "use";
		  	if (($this->intNagVersion != 3) && ($this->intNagVersion != 2)) {
				if ($key == "check_interval")   $key = "normal_check_interval";
				if ($key == "retry_interval")   $key = "retry_check_interval";
		  	}
		  	$strVIValues  = "is_volatile,active_checks_enabled,passive_checks_enabled,parallelize_check,obsess_over_service,";
		  	$strVIValues .= "check_freshness,event_handler_enabled,flap_detection_enabled,process_perf_data,retain_status_information,";
		  	$strVIValues .= "retain_nonstatus_information,notifications_enabled";
		  	if (in_array($key,explode(",",$strVIValues))) {
				if ($value == -1)         	$value = "null";
				if ($value == 3)        	$value = "null";
		  	}
		  	if ($key == "host_name")    	$value = $this->checkTpl($value,"host_name_tploptions","tbl_service",$intDataId,$intSkip);
		  	if ($key == "hostgroup_name") 	$value = $this->checkTpl($value,"hostgroup_name_tploptions","tbl_service",$intDataId,$intSkip);
		  	if ($key == "servicegroups")  	$value = $this->checkTpl($value,"servicegroups_tploptions","tbl_service",$intDataId,$intSkip);
		  	if ($key == "contacts")     	$value = $this->checkTpl($value,"contacts_tploptions","tbl_service",$intDataId,$intSkip);
		  	if ($key == "contact_groups") 	$value = $this->checkTpl($value,"contact_groups_tploptions","tbl_service",$intDataId,$intSkip);
		  	if ($key == "use")        		$value = $this->checkTpl($value,"use_template_tploptions","tbl_service",$intDataId,$intSkip);
			if ($key == "check_command") 	$value = str_replace("\::bang::","\!",$value);
			if ($key == "check_command") 	$value = str_replace("::bang::","\!",$value);
		}
		if ($strTableName == "tbl_hosttemplate") {
			if ($key == "template_name")  	$key = "name";
			if ($key == "use_template")   	$key = "use";
			$strVIValues  = "active_checks_enabled,passive_checks_enabled,check_freshness,obsess_over_host,event_handler_enabled,";
			$strVIValues .= "flap_detection_enabled,process_perf_data,retain_status_information,retain_nonstatus_information,";
			$strVIValues .= "notifications_enabled";
			if (in_array($key,explode(",",$strVIValues))) {
				if ($value == -1)         	$value = "null";
				if ($value == 3)        	$value = "null";
			}
			if ($key == "parents")      	$value = $this->checkTpl($value,"parents_tploptions","tbl_hosttemplate",$intDataId,$intSkip);
			if ($key == "hostgroups")   	$value = $this->checkTpl($value,"hostgroups_tploptions","tbl_hosttemplate",$intDataId,$intSkip);
			if ($key == "contacts")     	$value = $this->checkTpl($value,"contacts_tploptions","tbl_hosttemplate",$intDataId,$intSkip);
			if ($key == "contact_groups") 	$value = $this->checkTpl($value,"contact_groups_tploptions","tbl_hosttemplate",$intDataId,$intSkip);
			if ($key == "use")        		$value = $this->checkTpl($value,"use_template_tploptions","tbl_hosttemplate",$intDataId,$intSkip);
		}
		if ($strTableName == "tbl_servicetemplate") {
			if ($key == "template_name")  	$key = "name";
			if ($key == "use_template")   	$key = "use";
			if (($this->intNagVersion != 3) && ($this->intNagVersion != 2)) {
				if ($key == "check_interval")   $key = "normal_check_interval";
				if ($key == "retry_interval")   $key = "retry_check_interval";
			}
			$strVIValues  = "is_volatile,active_checks_enabled,passive_checks_enabled,parallelize_check,obsess_over_service,";
			$strVIValues .= "check_freshness,event_handler_enabled,flap_detection_enabled,process_perf_data,retain_status_information,";
			$strVIValues .= "retain_nonstatus_information,notifications_enabled";
			if (in_array($key,explode(",",$strVIValues))) {
				if ($value == -1)         	$value = "null";
				if ($value == 3)        	$value = "null";
			}
			if ($key == "host_name")    	$value = $this->checkTpl($value,"host_name_tploptions","tbl_servicetemplate",$intDataId,$intSkip);
			if ($key == "hostgroup_name") 	$value = $this->checkTpl($value,"hostgroup_name_tploptions","tbl_servicetemplate",$intDataId,$intSkip);
			if ($key == "servicegroups")  	$value = $this->checkTpl($value,"servicegroups_tploptions","tbl_servicetemplate",$intDataId,$intSkip);
			if ($key == "contacts")     	$value = $this->checkTpl($value,"contacts_tploptions","tbl_servicetemplate",$intDataId,$intSkip);
			if ($key == "contact_groups") 	$value = $this->checkTpl($value,"contact_groups_tploptions","tbl_servicetemplate",$intDataId,$intSkip);
			if ($key == "use")        		$value = $this->checkTpl($value,"use_template_tploptions","tbl_servicetemplate",$intDataId,$intSkip);
		}
		if ($strTableName == "tbl_contact") {
			if ($key == "use_template")   	$key = "use";
			$strVIValues  = "host_notifications_enabled,service_notifications_enabled,can_submit_commands,retain_status_information,";
			$strVIValues  = "retain_nonstatus_information";             
			if (in_array($key,explode(",",$strVIValues))) {
				if ($value == -1)         	$value = "null";
				if ($value == 3)        	$value = "null";
			}
			if ($key == "contactgroups")  	$value = $this->checkTpl($value,"contactgroups_tploptions","tbl_contact",$intDataId,$intSkip);
			if ($key == "host_notification_commands") {  	
											$value = $this->checkTpl($value,"host_notification_commands_tploptions","tbl_contact",$intDataId,$intSkip);}
			if ($key == "service_notification_commands") {  	
											$value = $this->checkTpl($value,"service_notification_commands_tploptions","tbl_contact",$intDataId,$intSkip);}
			if ($key == "use")        		$value = $this->checkTpl($value,"use_template_tploptions","tbl_contact",$intDataId,$intSkip);
		}
		if ($strTableName == "tbl_contacttemplate") {
			if ($key == "template_name")  	$key = "name";
			if ($key == "use_template")   	$key = "use";
			$strVIValues  = "host_notifications_enabled,service_notifications_enabled,can_submit_commands,retain_status_information,";
			$strVIValues  = "retain_nonstatus_information";
			if (in_array($key,explode(",",$strVIValues))) {
				if ($value == -1)         	$value = "null";
				if ($value == 3)        	$value = "null";
			}
			if ($key == "contactgroups")  	$value = $this->checkTpl($value,"contactgroups_tploptions","tbl_contacttemplate",$intDataId,$intSkip);
			if ($key == "host_notification_commands") {
											$value = $this->checkTpl($value,"host_notification_commands_tploptions","tbl_contacttemplate",$intDataId,$intSkip);}
			if ($key == "service_notification_commands") {
											$value = $this->checkTpl($value,"service_notification_commands_tploptions","tbl_contacttemplate",$intDataId,$intSkip);}
			if ($key == "use")        		$value = $this->checkTpl($value,"use_template_tploptions","tbl_contacttemplate",$intDataId,$intSkip);
		}
		if (($strTableName == "tbl_hosttemplate") || ($strTableName == "tbl_servicetemplate") || ($strTableName == "tbl_contacttemplate")) {
			if ($key == "register")  		$value = "0";
		}
		if ($strTableName == "tbl_timeperiod") {
		  	if ($key == "use_template")   	$key = "use";
		}
	}
	///////////////////////////////////////////////////////////////////////////////////////////
  	//  Help function: Skip database values
  	///////////////////////////////////////////////////////////////////////////////////////////
	//  $strTableName		-> Table name
	//  $strVersionValue	-> NagiosQL version value 
	//  $key				-> Data key
	//  $value				-> Data value
	///////////////////////////////////////////////////////////////////////////////////////////
	function skipEntries($strTableName,$strVersionValue,$key,&$value) {
		// Common fields
		$strSpecial = "id,active,last_modified,access_rights,access_group,config_id,template,nodelete,command_type,import_hash";
		$arrOption  = array();
		// Fields for special tables
		if ($strTableName == "tbl_hosttemplate")  		$strSpecial .= ",parents_tploptions,hostgroups_tploptions,contacts_tploptions".
																   	   ",contact_groups_tploptions,use_template_tploptions";
		if ($strTableName == "tbl_servicetemplate") 	$strSpecial .= ",host_name_tploptions,hostgroup_name_tploptions,contacts_tploptions".
																   	   ",servicegroups_tploptions,contact_groups_tploptions,use_template_tploptions";
		if ($strTableName == "tbl_contact") 			$strSpecial .= ",use_template_tploptions,contactgroups_tploptions".
																   	   ",host_notification_commands_tploptions,service_notification_commands_tploptions";
		if ($strTableName == "tbl_contacttemplate") 	$strSpecial .= ",use_template_tploptions,contactgroups_tploptions".
																   	   ",host_notification_commands_tploptions,service_notification_commands_tploptions";
        if ($strTableName == "tbl_host") 				$strSpecial .= ",parents_tploptions,hostgroups_tploptions,contacts_tploptions".
																	   ",contact_groups_tploptions,use_template_tploptions";
        if ($strTableName == "tbl_service") 			$strSpecial .= ",host_name_tploptions,hostgroup_name_tploptions,servicegroups_tploptions".
																	   ",contacts_tploptions,contact_groups_tploptions,use_template_tploptions";
																   
		// Pass special fields based on nagios version
		if ($strVersionValue != 3) {
			// Timeperiod
			if ($strTableName == "tbl_timeperiod") 			$strSpecial .= ",use_template,exclude,name";
			// Contact
			if ($strTableName == "tbl_contact")  	  	{	$strSpecial .= ",host_notifications_enabled,service_notifications_enabled,can_submit_commands,".
																	   "retain_status_information,retain_nonstatus_information";
															$arrOption['host_notification_options'] 	= ",s";
															$arrOption['service_notification_options'] 	= ",s"; }								   
			// Contacttemplate
			if ($strTableName == "tbl_contacttemplate") { 	$strSpecial .= ",host_notifications_enabled,service_notifications_enabled,can_submit_commands,".
																	   	   "retain_status_information,retain_nonstatus_information";
															$arrOption['host_notification_options'] 	= ",s";
															$arrOption['service_notification_options'] 	= ",s"; }	
			// Contactgroup
			if ($strTableName == "tbl_contactgroup") 		$strSpecial .= ",contactgroup_members";
			// Hostgroup
			if ($strTableName == "tbl_hostgroup") 			$strSpecial .= ",hostgroup_members,notes,notes_url,action_url";
			// Servicegroup
			if ($strTableName == "tbl_servicegroup") 		$strSpecial .= ",servicegroup_members,notes,notes_url,action_url";
			// Hostdependencies
			if ($strTableName == "tbl_hostdependency") 		$strSpecial .= ",dependent_hostgroup_name,hostgroup_name,dependency_period";
			// Hostescalations
			if ($strTableName == "tbl_hostescalation") 		$strSpecial .= ",contacts";
			// Servicedependencies
			if ($strTableName == "tbl_servicedependency")	$strSpecial .= ",dependent_hostgroup_name,hostgroup_name,dependency_period,dependent_servicegroup_name".
																		   ",servicegroup_name";
			// Serviceescalations
			if ($strTableName == "tbl_serviceescalation")	$strSpecial .= ",hostgroup_name,contacts,servicegroup_name";
			// Hosts
			if ($strTableName == "tbl_host") {				$strSpecial .= ",initial_state,flap_detection_options,contacts,notes,notes_url,action_url".
																		   ",icon_image,icon_image_alt,vrml_image,statusmap_image,2d_coords,3d_coords";
															$arrOption['notification_options'] 	= ",s"; }
			// Services
			if ($strTableName == "tbl_service") {			$strSpecial .= ",initial_state,flap_detection_options,contacts,notes,notes_url,action_url".
																		   ",icon_image,icon_image_alt";
															$arrOption['notification_options'] 	= ",s"; }
			// Hosttemplates
			if ($strTableName == "tbl_hosttemplate") {		$strSpecial .= ",initial_state,flap_detection_options,contacts,notes,notes_url,action_url".
																		   ",icon_image,icon_image_alt,vrml_image,statusmap_image,2d_coords,3d_coords";
															$arrOption['notification_options'] 	= ",s"; }
			// Servicetemplates
			if ($strTableName == "tbl_servicetemplate") {	$strSpecial .= ",initial_state,flap_detection_options,contacts,notes,notes_url,action_url".
																		   ",icon_image,icon_image_alt"; 
															$arrOption['notification_options'] 	= ",s"; }
		}
		if ($strVersionValue == 3) {
			// Servicetemplate
			if ($strTableName == "tbl_servicetemplate") $strSpecial .= ",parallelize_check ";
			// Service
			if ($strTableName == "tbl_service") 		$strSpecial .= ",parallelize_check";
			
		}
		if ($strVersionValue == 1) {
			$strSpecial .= "";
		}
		// Reduce option values
		if ((count($arrOption) != 0) && array_key_exists($key,$arrOption)) {
			$value = str_replace($arrOption[$key],'',$value);
			$value = str_replace(str_replace(',','',$arrOption[$key]),'',$value);
			if ($value == '') return(1);
		}
		// Skip entries
		$arrSpecial = explode(",",$strSpecial);
		if (($value == "") || (in_array($key,$arrSpecial))) return(1);

		// Do not write config data (based on 'skip' option)
		$strNoTwo  = "active_checks_enabled,passive_checks_enabled,obsess_over_host,check_freshness,event_handler_enabled,flap_detection_enabled,";
		$strNoTwo .= "process_perf_data,retain_status_information,retain_nonstatus_information,notifications_enabled,parallelize_check,is_volatile,";
		$strNoTwo .= "host_notifications_enabled,service_notifications_enabled,can_submit_commands,obsess_over_service";
		$booTest = 0;
		foreach(explode(",",$strNoTwo) AS $elem){
			if (($key == $elem) && ($value == "2")) $booTest = 1;
			if (($this->intNagVersion != 3) && ($key == $elem) && ($value == "3")) $booTest = 1;
		}
		if ($booTest == 1) return(1);
		return(0);
	}
	///////////////////////////////////////////////////////////////////////////////////////////
  	//  Help function: Open an SSH connection
  	///////////////////////////////////////////////////////////////////////////////////////////
	function getSSHConnection($intConfigID) {
		// SSH Possible
		if (!function_exists('ssh2_connect')) {
			$this->processClassMessage(translate('SSH module not loaded!')."::",$this->strErrorMessage);
			return(1);
		}
		// Set up basic connection
		$this->getConfigData($intConfigID,"server",$strServer);
		$this->resConnectServer = $strServer;
		$this->resConnectType   = "SSH";
		$intErrorReporting  = error_reporting();
		error_reporting(0);
		$this->resConnectId = ssh2_connect($strServer);
		$arrError = error_get_last();
		error_reporting($intErrorReporting);
		// Check connection
		if (!$this->resConnectId) {
			$this->myDataClass->writeLog(translate('Connection to remote system failed (SSH2 connection):')." ".$strServer);
			$this->processClassMessage(translate('Connection to remote system failed (SSH2 connection):')." <b>".$strServer."</b>::",$this->strErrorMessage);
			if ($arrError['message'] != "") $this->processClassMessage($arrError['message']."::",$this->strErrorMessage);
			$this->resConnectServer = "";
			$this->resConnectType   = "none";
			return(1);
		}
		// Login with username and password
		$this->getConfigData($intConfigID,"user",$strUser);
		$this->getConfigData($intConfigID,"password",$strPasswd);
		$this->getConfigData($intConfigID,"ssh_key_path",$strSSHKeyPath);
		if ($strSSHKeyPath != "") {
			$strPublicKey = str_replace("//","/",$strSSHKeyPath."/id_rsa.pub");
			$strPrivatKey = str_replace("//","/",$strSSHKeyPath."/id_rsa");
			// Check if ssh key file are readable
			if (!file_exists($strPublicKey) || !is_readable($strPublicKey)) {
				$this->myDataClass->writeLog(translate('SSH public key does not exist or is not readable')." ".$strSSHKeyPath.$strPublicKey);
				$this->processClassMessage(translate('SSH public key does not exist or is not readable')." <b>".$strSSHKeyPath.$strPublicKey."</b>::",$this->strErrorMessage);
				return(1);
			}
			if (!file_exists($strPrivatKey) || !is_readable($strPrivatKey)) {
				$this->myDataClass->writeLog(translate('SSH private key does not exist or is not readable')." ".$strPrivatKey);
				$this->processClassMessage(translate('SSH private key does not exist or is not readable')." ".$strPrivatKey."::",$this->strErrorMessage);
				return(1);
			}
			$intErrorReporting  = error_reporting();
			error_reporting(0);
			if ($strPasswd == "") {
				$login_result = ssh2_auth_pubkey_file($this->resConnectId, $strUser, $strSSHKeyPath."/id_rsa.pub", $strSSHKeyPath."/id_rsa");
			} else {
				$login_result = ssh2_auth_pubkey_file($this->resConnectId, $strUser, $strSSHKeyPath."/id_rsa.pub", $strSSHKeyPath."/id_rsa",$strPasswd);
			}
			$arrError = error_get_last();
			error_reporting($intErrorReporting);
		} else {
			$intErrorReporting  = error_reporting();
			error_reporting(0);
			$login_result 	 = ssh2_auth_password($this->resConnectId,$strUser,$strPasswd);
			$arrError 		 = error_get_last();
			$strPasswordNote = "If you are using ssh2 with user/password - you have to enable PasswordAuthentication in your sshd_config";
			error_reporting($intErrorReporting);
		}
		// Check connection
		if ((!$this->resConnectId) || (!$login_result)) {
			$this->myDataClass->writeLog(translate('Connection to remote system failed (SSH2 connection):')." ".$strServer);
			$this->processClassMessage(translate('Connection to remote system failed (SSH2 connection):')." ".$strServer."::",$this->strErrorMessage);
			if ($arrError['message'] != "") $this->processClassMessage($arrError['message']."::",$this->strErrorMessage);
			if (isset($strPasswordNote))  	$this->processClassMessage($strPasswordNote."::",$this->strErrorMessage);
			$this->resConnectServer = "";
			$this->resConnectType   = "none";
        	$this->resConnectId 	= null;
			return(1);
		}
		// Etablish an SFTP connection ressource
		$this->resSFTP = ssh2_sftp($this->resConnectId);
		return(0);
	}
	///////////////////////////////////////////////////////////////////////////////////////////
  	//  Help function: Sends a command via SSH and stores the result in an array
  	///////////////////////////////////////////////////////////////////////////////////////////
	//  $strCommand			-> Command
	//  $intLines			-> Read max output lines
	//  This functions returs a result array or false in case of error
	///////////////////////////////////////////////////////////////////////////////////////////
  	function sendSSHCommand($strCommand,$intLines=100) {
		$intCount1 = 0;
		$intCount2 = 0;
		$booResult = false;
		if (is_resource($this->resConnectId)) {
			$resStream = ssh2_exec($this->resConnectId, $strCommand.'; echo "__END__";');
			if ($resStream) {
				stream_set_blocking($resStream,1);
				stream_set_timeout($resStream,2);
				do {
					$strLine = stream_get_line($resStream,1024,"\n");
					if ($strLine == "") {
						$intCount1++;
					} else if (substr_count($strLine,"__END__") != 1) {
						$arrResult[] = $strLine;
						$booResult   = true;
					}
					$intCount2++;
					$arrStatus = stream_get_meta_data($resStream);
				} while ($resStream && !(feof($resStream)) && ($intCount1 <= 10) && ($intCount2 <= $intLines) && ($arrStatus['timed_out'] != true));
				fclose($resStream);
				if ($booResult) {
					if ($arrStatus['timed_out'] == true) {
						//echo "timed_out".var_dump($arrResult)."<br>";
					}
					return $arrResult;
				} else {
					return true;
				}
			}
		}
		return false;
  	}
	
	///////////////////////////////////////////////////////////////////////////////////////////
  	//  Help function: Open an FTP connection
  	///////////////////////////////////////////////////////////////////////////////////////////
	function getFTPConnection($intConfigID) {
        // Set up basic connection
        $this->getConfigData($intConfigID,"server",$strServer);
		$this->resConnectServer = $strServer;
		$this->resConnectType   = "FTP";
        $this->resConnectId	= ftp_connect($strServer);
        // Login with username and password
		if ($this->resConnectId) {
			$this->getConfigData($intConfigID,"user",$strUser);
			$this->getConfigData($intConfigID,"password",$strPasswd);
			$intErrorReporting = error_reporting();
			error_reporting('0');
			$login_result = ftp_login($this->resConnectId,$strUser,$strPasswd);
			$arrError = error_get_last();
			error_reporting($intErrorReporting);
			if ($login_result == false) {
				$strFTPError = $arrError['message'];
				ftp_close($this->resConnectId);
				$this->resConnectServer = "";
				$this->resConnectType   = "none";
			}
		}
        // Check connection
        if ((!$this->resConnectId) || (!$login_result)) {
			$this->myDataClass->writeLog(translate('Connection to remote system failed (FTP connection):')." ".$strServer);
			$this->processClassMessage(translate('Connection to remote system failed (FTP connection):')." <b>".$strServer."</b>::",$this->strErrorMessage);
			if (isset($arrError) && ($arrError['message'] != "")) $this->processClassMessage($arrError['message']."::",$this->strErrorMessage);
          	return(1);
        }
		return(0);
	}
	///////////////////////////////////////////////////////////////////////////////////////////
  	//  Help function: Get configuration set IDs
  	///////////////////////////////////////////////////////////////////////////////////////////
    //  $strTableName		-> Configuration table name
	//  Return value		-> Array including configuration target IDs
	///////////////////////////////////////////////////////////////////////////////////////////
  	function getConfigSets($strTableName = '') {
		$arrConfigId = array();
		if ($strTableName == '') {
			$strSQL    = "SELECT `targets` FROM `tbl_datadomain` WHERE `id` = ".$this->intDomainId;
			$booReturn = $this->myDBClass->getDataArray($strSQL,$arrData,$intDataCount);
			if ($booReturn && ($intDataCount != 0)) {
            	foreach ($arrData AS $elem) {
					$arrConfigId[] = $elem['targets'];
				}
				return($arrConfigId);
			}
		}
		return(1);
  	}
	///////////////////////////////////////////////////////////////////////////////////////////
  	//  Help function: Get configuration domain parameters
  	///////////////////////////////////////////////////////////////////////////////////////////
	//  $intConfigId		-> Configuration ID
    //  $strConfigItem		-> Configuration key
	//  $strValue			-> Configuration value (return value)
	///////////////////////////////////////////////////////////////////////////////////////////
  	function getConfigData($intConfigId,$strConfigItem,&$strValue) {
    	$strSQL   = "SELECT `".$strConfigItem."` FROM `tbl_configtarget` WHERE `id` = ".$intConfigId;
    	$strValue = $this->myDBClass->getFieldData($strSQL);
    	if ($strValue != "" ) return(0);
    	return(1);
  	}
	///////////////////////////////////////////////////////////////////////////////////////////
  	//  Help function: Get data domain parameters
  	///////////////////////////////////////////////////////////////////////////////////////////
    //  $strConfigItem		-> Configuration key
	//  $strValue			-> Configuration value (return value)
	///////////////////////////////////////////////////////////////////////////////////////////
  	function getDomainData($strConfigItem,&$strValue) {
    	$strSQL   = "SELECT `".$strConfigItem."` FROM `tbl_datadomain` WHERE `id` = ".$this->intDomainId;
    	$strValue = $this->myDBClass->getFieldData($strSQL);
    	if ($strValue != "" ) return(0);
    	return(1);
  	}
	///////////////////////////////////////////////////////////////////////////////////////////
  	//  Help function: Process special settings based on template option
  	///////////////////////////////////////////////////////////////////////////////////////////
    //  $strValue			-> Original data value
	//  $strKeyField		-> Template option field name
    //  $strTable			-> Table name
	//  $intId				-> Dataset ID
	//  $intSkip			-> Skip value 	(return value)
	//  This function returns the manipulated data value
	///////////////////////////////////////////////////////////////////////////////////////////
  	function checkTpl($strValue,$strKeyField,$strTable,$intId,&$intSkip) {
    	if ($this->intNagVersion < 3) return($strValue);
		$strSQL   = "SELECT `".$strKeyField."` FROM `".$strTable."` WHERE `id` = $intId";
    	$intValue = $this->myDBClass->getFieldData($strSQL);
    	if ($intValue == 0) return("+".$strValue);
    	if ($intValue == 1) {
      		$intSkip = 0;
      		return("null");
    	}
    	return($strValue);
  	}
	///////////////////////////////////////////////////////////////////////////////////////////
  	//  Help function: Check directory for write access
  	///////////////////////////////////////////////////////////////////////////////////////////
    //  $path				-> Physical path
	//  This function returns true if writeable or false if not
	//  This is a 3rd party function and not written by the NagiosQL developper team
	///////////////////////////////////////////////////////////////////////////////////////////
	function dir_is_writable($path) {
		if ($path == "") return false;
		//will work in despite of Windows ACLs bug
		//NOTE: use a trailing slash for folders!!!
		//see http://bugs.php.net/bug.php?id=27609
		//see http://bugs.php.net/bug.php?id=30931
		if ($path{strlen($path)-1}=='/') // recursively return a temporary file path
			return $this->dir_is_writable($path.uniqid(mt_rand()).'.tmp');
		else if (is_dir($path))
			return $this->dir_is_writable($path.'/'.uniqid(mt_rand()).'.tmp');
		// check tmp file for read/write capabilities
		$rm = file_exists($path);
		$f = @fopen($path, 'a');
		if ($f===false)
			return false;
		fclose($f);
		if (!$rm)
			unlink($path);
		return true;
	}
	///////////////////////////////////////////////////////////////////////////////////////////
	//  Help function: Processing message strings
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Merge message strings and check for duplicate messages
	//
  	//  Parameters:  		$strNewMessage	Message to add
	//						$strSeparate	Separate string (<br> or \n)
	//
  	//  Return value:		&$strOldMessage	Modified message string
	//
	///////////////////////////////////////////////////////////////////////////////////////////
	function processClassMessage($strNewMessage,&$strOldMessage) {
		$strNewMessage = str_replace("::::","::",$strNewMessage);
		if (($strOldMessage != "") && ($strNewMessage != "")) {
			if (substr_count($strOldMessage,$strNewMessage) == 0) {
				$strOldMessage .= $strNewMessage;
			}
		} else {
			$strOldMessage .= $strNewMessage;
		}
		// Reset message variable (prevent duplicates)
		$strNewMessage = "";
	}
	///////////////////////////////////////////////////////////////////////////////////////////
	//  Help function: Add files of a given directory to an array
	///////////////////////////////////////////////////////////////////////////////////////////
	function DirToArray($sPath, $include, $exclude, &$output,&$errMessage) {
		while (substr($sPath,-1) == "/" OR substr($sPath,-1) == "\\") {
			$sPath=substr($sPath, 0, -1);
		}
		$handle = @opendir($sPath);
		if( $handle === false ) {
			if ($this->intDomainId != 0) {
				$errMessage .= translate('Could not open directory').": ".$sPath."<br>";
			}
		} else {
			while ($arrDir[] = readdir($handle)) {}
			closedir($handle);
			sort($arrDir);
			foreach($arrDir as $file) {
				if (!preg_match("/^\.{1,2}/", $file) and strlen($file)) {
					if (is_dir($sPath."/".$file)) {
						$this->DirToArray($sPath."/".$file, $include, $exclude, $output, $errMessage);
					} else {
						if (preg_match("/".$include."/",$file) && (($exclude == "") || !preg_match("/".$exclude."/", $file))) {
							if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
								$sPath=str_replace("/", "\\", $sPath);
								$output [] = $sPath."\\".$file;
							} else {
								$output [] = $sPath."/".$file;
							}
						}
					}
				}
			}
		}
	}
}
?>
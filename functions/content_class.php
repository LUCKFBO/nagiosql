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
// Class: Common content functions
//
///////////////////////////////////////////////////////////////////////////////////////////////
//
// Includes all functions used to display the application data
//
// Name: nagcontent
//
///////////////////////////////////////////////////////////////////////////////////////////////
class nagcontent {
  	// Define class variables
    var $arrSettings;       	// Array includes all global settings
	var $myVisClass;        	// NagiosQL visual class object 
	var $myDBClass;				// NagiosQL data base class object
	var $myConfigClass;			// NagiosQL configuration class object
	var $intLimit;				// Data limit value
	var $intVersion;			// Nagios version id
	var $strBrowser;			// Browser string
	var $intGroupAdm;			// Group admin enabled/disabled
	var $intWriteAccessId;		// Write access id
	var $intGlobalWriteAccess; 	// Global write access id
	var $arrDescription;		// Language field values from fieldvars.php
	var $strTableName;			// Data table name
	var $strSearchSession;		// Search session name
	var $intSortBy;				// Sort by field id
	var $strSortDir;			// SQL sort direction (ASC/DESC)
  	var $intDomainId  = 0;  	// Domain id value
	var $strErrorMessage = ""; 	// String including error messages

	///////////////////////////////////////////////////////////////////////////////////////////
  	//  Class constructor
  	///////////////////////////////////////////////////////////////////////////////////////////
  	//
  	//  Activities during class initialization
  	//
  	///////////////////////////////////////////////////////////////////////////////////////////
  	function __construct() {
    	// Read global settings
    	$this->arrSettings = $_SESSION['SETS'];
    	if (isset($_SESSION['domain'])) $this->intDomainId = $_SESSION['domain'];
  	}
	///////////////////////////////////////////////////////////////////////////////////////////
	//  Function: Single data form initialization
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Parameter:  		$objTemplate   		Form template object
	//						$strChbFields		Comma separated string of checkbox value names
	//   
  	//  Return values:		none
	//
	///////////////////////////////////////////////////////////////////////////////////////////
	function addFormInit($objTemplate,$strChbFields='') {
		// Language text replacements from fieldvars.php file
		foreach($this->arrDescription AS $elem) {
			$objTemplate->setVariable($elem['name'],$elem['string']);
		}
		// Some single replacements
		$objTemplate->setVariable("ACTION_INSERT",filter_var($_SERVER['PHP_SELF'], FILTER_SANITIZE_STRING));
		$objTemplate->setVariable("IMAGE_PATH",$this->arrSettings['path']['base_url']."images/");
		$objTemplate->setVariable("DOCUMENT_ROOT",$this->arrSettings['path']['base_url']);
		$objTemplate->setVariable("ACT_CHECKED","checked");
		$objTemplate->setVariable("REG_CHECKED","checked");
		$objTemplate->setVariable("MODUS","insert");
		$objTemplate->setVariable("VERSION",$this->intVersion);
		$objTemplate->setVariable("LIMIT",$this->intLimit);
		$objTemplate->setVariable("RELATION_CLASS","elementHide");
		$objTemplate->setVariable("IFRAME_SRC",$this->arrSettings['path']['base_url']."admin/commandline.php");
		// Some conditional replacements
		if ($this->strBrowser != "msie") $objTemplate->setVariable("MSIE_DISABLED","disabled=\"disabled\"");
		if ($this->intGroupAdm == 0) 	 $objTemplate->setVariable("RESTRICT_GROUP_ADMIN","class=\"elementHide\"");
		if ($this->arrSettings['common']['seldisable'] == 0) $objTemplate->setVariable("MSIE_DISABLED","");
		if ($this->arrSettings['common']['tplcheck']   == 0) $objTemplate->setVariable("CHECK_BYPASS","return true;");
		// Some replacements based on nagios version
		if ($this->intVersion == 3) {
			$objTemplate->setVariable("VERSION_20_VISIBLE","elementHide");
			$objTemplate->setVariable("VERSION_30_VISIBLE","elementShow");
			$objTemplate->setVariable("VERSION_20_MUST","");
			$objTemplate->setVariable("VERSION_30_MUST","inpmust");
			$objTemplate->setVariable("VERSION_20_STAR","");
			$objTemplate->setVariable("NAGIOS_VERSION","3");
		} else {
			$objTemplate->setVariable("VERSION_20_VISIBLE","elementShow");
			$objTemplate->setVariable("VERSION_30_VISIBLE","elementHide");
			$objTemplate->setVariable("VERSION_20_MUST","inpmust");
			$objTemplate->setVariable("VERSION_30_MUST","");
			$objTemplate->setVariable("VERSION_20_STAR","*");
			$objTemplate->setVariable("NAGIOS_VERSION","2");
		}
		// Checkbox and radio field value replacements
		if ($strChbFields != '') {
			foreach (explode(",",$strChbFields) AS $elem) {
				$objTemplate->setVariable("DAT_".$elem."0_CHECKED","");
				$objTemplate->setVariable("DAT_".$elem."1_CHECKED","");
				$objTemplate->setVariable("DAT_".$elem."2_CHECKED","checked");
			}
		}
	}
	///////////////////////////////////////////////////////////////////////////////////////////
	//  Function: Single data form - value insertions
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Parameter:  		$objTemplate   		Form template object
	//						$arrModifyData		Database values
	//						$intLocked			Data is locked (0=no / 1=yes)
	//						$strInfo			Information string
	//						$strChbFields		Comma separated string of checkbox value names
	//   
  	//  Return values:		none
	//
	///////////////////////////////////////////////////////////////////////////////////////////
	function addInsertData($objTemplate,$arrModifyData,$intLocked,$strInfo,$strChbFields = '') {
		// Insert text data values
		foreach($arrModifyData AS $key => $value) {
			if (($key == "active") || ($key == "register") || ($key == "last_modified") || ($key == "access_rights")) continue;
      		$objTemplate->setVariable("DAT_".strtoupper($key),htmlentities($value,ENT_QUOTES,'UTF-8'));
    	}
		// Insert checkbox data values
    	if ((isset($arrModifyData['active']))   && ($arrModifyData['active']   != 1)) $objTemplate->setVariable("ACT_CHECKED","");
		if ((isset($arrModifyData['register'])) && ($arrModifyData['register'] != 1)) $objTemplate->setVariable("REG_CHECKED","");
		// Deselect any checkboxes
		if ($strChbFields != '') {
			foreach (explode(",",$strChbFields) AS $elem) {
				$objTemplate->setVariable("DAT_".$elem."0_CHECKED","");
				$objTemplate->setVariable("DAT_".$elem."1_CHECKED","");
				$objTemplate->setVariable("DAT_".$elem."2_CHECKED","");
			}
		}
		// Change some status values in locked data sets
    	if ($intLocked != 0) {
      		$objTemplate->setVariable("ACT_DISABLED","disabled");
     		$objTemplate->setVariable("ACT_CHECKED","checked");
      		$objTemplate->setVariable("ACTIVE","1");
      		$objTemplate->setVariable("CHECK_MUST_DATA",$strInfo);
			$objTemplate->setVariable("RELATION_CLASS","elementShow");
    	}
		// Change mode to modify
    	$objTemplate->setVariable("MODUS","modify");
		// Check write permission
		if ($this->intWriteAccessId == 1) 	  $objTemplate->setVariable("DISABLE_SAVE","disabled=\"disabled\"");
		if ($this->intGlobalWriteAccess == 1) $objTemplate->setVariable("DISABLE_SAVE","disabled=\"disabled\"");
	}
	///////////////////////////////////////////////////////////////////////////////////////////
	//  Function: List view - form initialization
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Parameter:  		$objTemplate   		Form template object
	//   
  	//  Return values:		none
	//
	///////////////////////////////////////////////////////////////////////////////////////////
	function listViewInit($objTemplate) {
		// Language text replacements from fieldvars.php file
		foreach($this->arrDescription AS $elem) {
			$objTemplate->setVariable($elem['name'],$elem['string']);
		} 
		// Some single replacements
		$objTemplate->setVariable("LIMIT",$this->intLimit);
		$objTemplate->setVariable("ACTION_MODIFY",filter_var($_SERVER['PHP_SELF'], FILTER_SANITIZE_STRING));
		$objTemplate->setVariable("TABLE_NAME",$this->strTableName);
		$objTemplate->setVariable("DAT_SEARCH",$_SESSION['search'][$this->strSearchSession]);
  		$objTemplate->setVariable("MAX_ID","0");
  		$objTemplate->setVariable("MIN_ID","0");
	}
	///////////////////////////////////////////////////////////////////////////////////////////
	//  Function: List view - value insertions
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Parameter:  		$objTemplate   		Form template object
	//						$arrData			Database values
	//						$intDataCount		Total count of data lines for one page
	//						$intLineCount		Total count of data lines (all data)
	//						$strField1			Field name for data field 1
	//						$strField2			Field name for data field 2
	//						$intLimit2			Actual data char limit for field 2
	//   
  	//  Return values:		none
	//
	///////////////////////////////////////////////////////////////////////////////////////////
	function listData($objTemplate,$arrData,$intDataCount,$intLineCount,$strField1,$strField2,$intLimit2=0) {
		// Template block names
		$strTplPart = 'datatable';
		$strTplRow  = 'datarow';
		if ($this->strTableName == "tbl_host") {
			$strTplPart = 'datatablehost';
			$strTplRow  = 'datarowhost';
		}
		if ($this->strTableName == "tbl_service") {
			$strTplPart = 'datatableservice';
			$strTplRow  = 'datarowservice';
		}
		if (($this->strTableName == "tbl_user") || ($this->strTableName == "tbl_group") || ($this->strTableName == "tbl_datadomain") || ($this->strTableName == "tbl_configtarget")) {
			$strTplPart = 'datatablecommon';
			$strTplRow  = 'datarowcommon';
		}
		// Some single replacements
		$objTemplate->setVariable("IMAGE_PATH_HEAD",$this->arrSettings['path']['base_url']."images/");
		$objTemplate->setVariable("CELLCLASS_L","tdlb");
		$objTemplate->setVariable("CELLCLASS_M","tdmb");	
		$objTemplate->setVariable("DISABLED","disabled");
		$objTemplate->setVariable("DATA_FIELD_1",translate('No data'));
		$objTemplate->setVariable("DATA_FIELD_2","&nbsp;");
		$objTemplate->setVariable("DATA_REGISTERED","&nbsp;");
		$objTemplate->setVariable("DATA_ACTIVE","&nbsp;");
		$objTemplate->setVariable("DATA_FILE","&nbsp;");
		$objTemplate->setVariable("PICTURE_CLASS","elementHide");
		$objTemplate->setVariable("DOMAIN_SPECIAL","&nbsp;");
		$objTemplate->setVariable("SORT_BY",$this->intSortBy);
		// Inserting data values
		if ($intDataCount != 0) {
			for ($i=0;$i<$intDataCount;$i++) {
				// Get biggest and smalest value
				if ($i == 0) {$y = $arrData[$i]['id']; $z = $arrData[$i]['id'];}
				if ($arrData[$i]['id'] < $y) $y = $arrData[$i]['id'];
				if ($arrData[$i]['id'] > $z) $z = $arrData[$i]['id'];
				$objTemplate->setVariable("MAX_ID",$z);
				$objTemplate->setVariable("MIN_ID",$y);
				// Line colours
				$strClassL = "tdld"; $strClassM = "tdmd";
				if ($i%2 == 1) {$strClassL = "tdlb"; $strClassM = "tdmb";}
				if ((isset($arrData[$i]['register'])) && ($arrData[$i]['register'] == 0)) {$strRegister = translate('No');} else {$strRegister = translate('Yes');}
				if ($arrData[$i]['active']   == 0) {$strActive	 = translate('No');} else {$strActive	= translate('Yes');}
				// Get file date for hosts and services
				$intTimeInfo = 0;
				if ($this->strTableName == "tbl_host") {
     				$intReturn = $this->myConfigClass->lastModifiedDir($this->strTableName,$arrData[$i]['host_name'],$arrData[$i]['id'],$arrTimeData,$intTimeInfo);
					if ($intReturn == 1) $this->strErrorMessage = $this->myConfigClass->strErrorMessage;
				}
				if ($this->strTableName == "tbl_service") {
     				$intReturn = $this->myConfigClass->lastModifiedDir($this->strTableName,$arrData[$i]['config_name'],$arrData[$i]['id'],$arrTimeData,$intTimeInfo);
					if ($intReturn == 1) $this->strErrorMessage = $this->myConfigClass->strErrorMessage;
				}
				// Set datafields
				foreach($this->arrDescription AS $elem) {
					$objTemplate->setVariable($elem['name'],$elem['string']);
				}
				if ($arrData[$i][$strField1] == '') $arrData[$i][$strField1] = "NOT DEFINED - ".$arrData[$i]['id'];
				$objTemplate->setVariable("DATA_FIELD_1",htmlentities($arrData[$i][$strField1],ENT_COMPAT,'UTF-8'));
				$objTemplate->setVariable("DATA_FIELD_1S",addslashes(htmlentities($arrData[$i][$strField1],ENT_COMPAT,'UTF-8')));
				if ($strField2 == 'process_field') {
					$arrData[$i]['process_field'] = $this->processField2($arrData[$i],$this->strTableName);
				} else {
					$objTemplate->setVariable("DATA_FIELD_2S",addslashes(htmlentities($arrData[$i][$strField2],ENT_COMPAT,'UTF-8')));
				}
				if ($intLimit2 != 0) {
					if (strlen($arrData[$i][$strField2]) > $intLimit2) {$strAdd = " ...";} else {$strAdd = "";}
					$objTemplate->setVariable("DATA_FIELD_2",htmlentities(substr($arrData[$i][$strField2],0,$intLimit2),ENT_COMPAT,'UTF-8').$strAdd);
				} else {
					$objTemplate->setVariable("DATA_FIELD_2",htmlentities($arrData[$i][$strField2],ENT_COMPAT,'UTF-8'));
				}
				$objTemplate->setVariable("DATA_REGISTERED",$strRegister);
				if (substr_count($this->strTableName,'template') != 0) $objTemplate->setVariable("DATA_REGISTERED","-");
				$objTemplate->setVariable("DATA_ACTIVE",$strActive);
				$objTemplate->setVariable("DATA_FILE","<span class=\"redmessage\">".translate('out-of-date')."</span>");
				if ($intTimeInfo == 4) $objTemplate->setVariable("DATA_FILE",translate('no target'));
				if ($intTimeInfo == 3) $objTemplate->setVariable("DATA_FILE","<span class=\"greenmessage\">".translate('missed')."</span>");
				if ($intTimeInfo == 2) $objTemplate->setVariable("DATA_FILE","<span class=\"redmessage\">".translate('missed')."</span>");
				if ($intTimeInfo == 1) $objTemplate->setVariable("DATA_FILE","<span class=\"redmessage\">".translate('out-of-date')."</span>");
				if ($intTimeInfo == 0) $objTemplate->setVariable("DATA_FILE",translate('up-to-date'));
				$objTemplate->setVariable("LINE_ID",$arrData[$i]['id']);
				$objTemplate->setVariable("CELLCLASS_L",$strClassL);
				$objTemplate->setVariable("CELLCLASS_M",$strClassM);
				$objTemplate->setVariable("IMAGE_PATH",$this->arrSettings['path']['base_url']."images/");
				$objTemplate->setVariable("PICTURE_CLASS","elementShow");
				$objTemplate->setVariable("DOMAIN_SPECIAL","");
				$objTemplate->setVariable("DISABLED","");
				// Disable common domain objects
				if (isset($arrData[$i]['config_id'])) {
					if ($arrData[$i]['config_id'] != $this->intDomainId) {
						$objTemplate->setVariable("PICTURE_CLASS","elementHide");
						$objTemplate->setVariable("DOMAIN_SPECIAL"," [common]");
						$objTemplate->setVariable("DISABLED","disabled");
					} else {
						// Inactive items should not be written/downloaded
						if ($arrData[$i]['active'] == 0) $objTemplate->setVariable("ACTIVE_CONTROL","elementHide");	
					}
				}
				// Check access rights for list objects
				if (isset($arrData[$i]['access_group'])) {
					if ($this->myVisClass->checkAccGroup($arrData[$i]['access_group'],'write') != 0) $objTemplate->setVariable("LINE_CONTROL","elementHide");
				} else {
					if ($this->intGlobalWriteAccess != 0) $objTemplate->setVariable("LINE_CONTROL","elementHide");
				}
				// Check global access rights for list objects
				if ($this->intGlobalWriteAccess != 0) $objTemplate->setVariable("LINE_CONTROL","elementHide");
				$objTemplate->parse($strTplRow);
			}
		} else {
			$objTemplate->setVariable("IMAGE_PATH",$this->arrSettings['path']['base_url']."images/");
			$objTemplate->parse($strTplRow);
		}
		$objTemplate->setVariable("BUTTON_CLASS","elementShow");
		if ($this->intDomainId == 0) $objTemplate->setVariable("BUTTON_CLASS","elementHide");
		// Check access rights for adding new objects
		if ($this->intGlobalWriteAccess != 0) $objTemplate->setVariable("ADD_CONTROL","disabled=\"disabled\"");
		// Show page numbers
		$objTemplate->setVariable("PAGES",$this->myVisClass->buildPageLinks(filter_var($_SERVER['PHP_SELF'], FILTER_SANITIZE_STRING),$intLineCount,$this->intLimit,$this->intSortBy,$this->strSortDir));
		$objTemplate->parse($strTplPart);
		$objTemplate->show($strTplPart);
	}
	///////////////////////////////////////////////////////////////////////////////////////////
	//  Function: Display information messages
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Parameter:  		$objTemplate   		Form template object
	//						$strErrorMessage	String including error messages
	//						$strInfoMessage		String including information messages
	//						$strConsistMessage	String including consistency messages
	//						$arrTimeData		Array including time data
	//						$strTimeInfoString	String including time information message
	//						$intNoTime			Status value for showing time information (0=show time)
	//   
  	//  Return values:		none
	//
	///////////////////////////////////////////////////////////////////////////////////////////
	function showMessages($objTemplate,$strErrorMessage,$strInfoMessage,$strConsistMessage,$arrTimeData,$strTimeInfoString,$intNoTime=0) {
		// Display info messages
		if ($strInfoMessage != "") {
			$objTemplate->setVariable("INFOMESSAGE",$strInfoMessage);
			$objTemplate->parse('infomessage');
		}
		// Display error messages
		if ($strErrorMessage != "") {
			$objTemplate->setVariable("ERRORMESSAGE",$strErrorMessage);
			$objTemplate->parse('errormessage');
		}
		// Display time informations
		if (($this->intDomainId != 0) && ($intNoTime == 0)) {
			foreach($arrTimeData AS $key => $elem) {
				if ($key == 'table') {
					$objTemplate->setVariable("LAST_MODIFIED_TABLE",translate('Last database update:')." <b>".$elem."</b>");
					$objTemplate->parse('table_time');
				} else {
					$objTemplate->setVariable("LAST_MODIFIED_FILE",translate('Last file change of the configuration target ')." <i>".$key."</i>: <b>".$elem."</b>");
					$objTemplate->parse('file_time');
				}
			}
			if ($strTimeInfoString != "") {
				$objTemplate->setVariable("MODIFICATION_STATUS",$strTimeInfoString);
				$objTemplate->parse('modification_status');
			}
		}
		// Display consistency messages
		if ($strConsistMessage != "") {
			$objTemplate->setVariable("CONSIST_USAGE",$strConsistMessage);
			$objTemplate->parse('consistency');
		}
		$objTemplate->parse("msgfooter");
		$objTemplate->show("msgfooter");
	}
	///////////////////////////////////////////////////////////////////////////////////////////
	//  Function: Display page footer
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Parameter:  		$objTemplate   		Form template object
	//						$setFileVersion		NagiosQL version
	//   
  	//  Return values:		none
	//
	///////////////////////////////////////////////////////////////////////////////////////////
	function showFooter($objTemplate,$setFileVersion) {
		$objTemplate->setVariable("VERSION_INFO","<a href='http://www.nagiosql.org' target='_blank'>NagiosQL</a> $setFileVersion");
		$objTemplate->parse("footer");
		$objTemplate->show("footer");
	}
	///////////////////////////////////////////////////////////////////////////////////////////
	//  --- HELP functions ---
	///////////////////////////////////////////////////////////////////////////////////////////
	//  Function: Process list view field 2
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Parameter:  		$arrData   			Data array
	//						$strTableName		Table name
	//   
  	//  Return values:		String includung field 2 data
	//
	///////////////////////////////////////////////////////////////////////////////////////////
	function processField2($arrData,$strTableName) {
		$strField = "";
		// Hostdependency table
		if ($strTableName == "tbl_hostdependency") {
			if ($arrData['dependent_host_name'] != 0) {
				$strSQLHost = "SELECT `host_name`, `exclude` FROM `tbl_host` LEFT JOIN `tbl_lnkHostdependencyToHost_DH` ON `id`=`idSlave`
							   WHERE `idMaster`=".$arrData['id']." ORDER BY `host_name`";
				$booReturn 	= $this->myDBClass->getDataArray($strSQLHost,$arrDataHosts,$intDCHost);
				if ($intDCHost != 0) {
					foreach($arrDataHosts AS $elem) {
						if ($elem['exclude'] == 1) {
							$strField .= "H:!".$elem['host_name'].",";
						} else {
							$strField .= "H:".$elem['host_name'].",";
						}
					}
				}
			}
			if ($arrData['dependent_hostgroup_name'] != 0) {
				$strSQLHost = "SELECT `hostgroup_name`, `exclude` FROM `tbl_hostgroup` LEFT JOIN `tbl_lnkHostdependencyToHostgroup_DH` ON `id`=`idSlave`
							   WHERE `idMaster`=".$arrData['id']." ORDER BY `hostgroup_name`";
				$booReturn 	= $this->myDBClass->getDataArray($strSQLHost,$arrDataHostgroups,$intDCHostgroup);
				if ($intDCHostgroup != 0) {
					foreach($arrDataHostgroups AS $elem) {
						if ($elem['exclude'] == 1) {
							$strField .= "HG:!".$elem['hostgroup_name'].",";
						} else {
							$strField .= "HG:".$elem['hostgroup_name'].",";
						}
					}
				}
			}
		}
		// Hostescalation table
		if ($strTableName == "tbl_hostescalation") {
      		if ($arrData['host_name'] != 0) {
        		$strSQLHost = "SELECT `host_name` FROM `tbl_host` LEFT JOIN `tbl_lnkHostescalationToHost` ON `id`=`idSlave`
                 			   WHERE `idMaster`=".$arrData['id']." ORDER BY `host_name`";
        		$booReturn 	= $this->myDBClass->getDataArray($strSQLHost,$arrDataHosts,$intDCHost);
        		if ($intDCHost != 0) {
          			foreach($arrDataHosts AS $elem) {
            			$strField .= "H:".$elem['host_name'].",";
          			}
        		}
      		}
			if ($arrData['hostgroup_name'] != 0) {
        		$strSQLHost = "SELECT `hostgroup_name` FROM `tbl_hostgroup` LEFT JOIN `tbl_lnkHostescalationToHostgroup` ON `id`=`idSlave`
                 			   WHERE `idMaster`=".$arrData['id']." ORDER BY `hostgroup_name`";
        		$booReturn  = $this->myDBClass->getDataArray($strSQLHost,$arrDataHostgroups,$intDCHostgroup);
        		if ($intDCHostgroup != 0) {
          			foreach($arrDataHostgroups AS $elem) {
            			$strField .= "HG:".$elem['hostgroup_name'].",";
          			}
        		}
      		}
		}
		// Servicedependency table
		if ($strTableName == "tbl_servicedependency") {
			if ($arrData['dependent_service_description'] == 2) {
				$strField .= "*";
			} else if ($arrData['dependent_service_description'] != 0) {
        		$strSQLService 	= "SELECT `strSlave` FROM `tbl_lnkServicedependencyToService_DS` WHERE `idMaster`=".$arrData['id']." ORDER BY `strSlave`";				
        		$booReturn 		= $this->myDBClass->getDataArray($strSQLService,$arrDataService,$intDCService);
        		if ($intDCService != 0) {
          			foreach($arrDataService AS $elem) {
            			$strField .= $elem['strSlave'].",";
          			}
        		}
      		}
			if ($strField == "") {
        		$strSQLService 	= "SELECT `servicegroup_name` FROM `tbl_servicegroup`
								   LEFT JOIN `tbl_lnkServicedependencyToServicegroup_DS` ON `idSlave`=`id`
								   WHERE `idMaster`=".$arrData['id']." ORDER BY `servicegroup_name`";
        		$booReturn 		= $this->myDBClass->getDataArray($strSQLService,$arrDataService,$intDCService);
        		if ($intDCService != 0) {
          			foreach($arrDataService AS $elem) {
            			$strField .= $elem['servicegroup_name'].",";
          			}
        		}
			}
			print_r($strSQLService);
		}
		// Serviceescalation table
		if ($strTableName == "tbl_serviceescalation") {
      		if ($arrData['service_description'] == 2) {
				$strField .= "*";
			} else if ($arrData['service_description'] != 0) {
				$strSQLService 	= "SELECT `strSlave` FROM `tbl_lnkServiceescalationToService` WHERE `idMaster`=".$arrData['id'];
				$booReturn 		= $this->myDBClass->getDataArray($strSQLService,$arrDataServices,$intDCServices);
				if ($intDCServices != 0) {
					foreach($arrDataServices AS $elem) {
						$strField .= $elem['strSlave'].",";
					}
				}
			}
			if ($strField == "") {
        		$strSQLService 	= "SELECT `servicegroup_name` FROM `tbl_servicegroup`
								   LEFT JOIN `tbl_lnkServiceescalationToServicegroup` ON `idSlave`=`id`
								   WHERE `idMaster`=".$arrData['id']." ORDER BY `servicegroup_name`";
        		$booReturn 		= $this->myDBClass->getDataArray($strSQLService,$arrDataService,$intDCService);
        		if ($intDCService != 0) {
          			foreach($arrDataService AS $elem) {
            			$strField .= $elem['servicegroup_name'].",";
          			}
        		}
			}
		}
		// Some string manipulations - remove comma on line end
		if (substr($strField,-1) == ',') $strField = substr($strField,0,-1);
		return($strField);
	}
}
?>
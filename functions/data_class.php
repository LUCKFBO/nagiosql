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
// Class: Data processing class
//
///////////////////////////////////////////////////////////////////////////////////////////////
//
// Includes all functions used to manipulate the configuration data inside the database
//
// Name: nagdata
//
///////////////////////////////////////////////////////////////////////////////////////////////
class nagdata {
  	// Define class variables
    var $arrSettings;       		// Array includes all global settings
  	var $intDomainId  		= 0;	// Domain id value
  	var $myDBClass;					// NagiosQL database class object
  	var $myVisClass;				// NagiosQL visual class object
    var $strErrorMessage 	= ""; 	// String including error messages
  	var $strInfoMessage   	= ""; 	// String including information messages

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
  	//  Function: Write data to the database
  	///////////////////////////////////////////////////////////////////////////////////////////
  	//
  	//  Sends an SQL string to the database server
  	//
  	//  Parameters:  		$strSQL         SQL Command
  	//
  	//  Return value:     	$intDataID      Data ID of last inserted dataset
	//
  	//  Return value:		0 = successful
	//						1 = error
	//						Status message is stored in message class variables
  	//
  	///////////////////////////////////////////////////////////////////////////////////////////
  	function dataInsert($strSQL,&$intDataID) {
    	// Send the SQL command to the database server
		$booReturn = $this->myDBClass->insertData($strSQL);
		$intDataID = $this->myDBClass->intLastId;
    	// Was the SQL command processed successfully?
    	if ($booReturn) {
			$this->processClassMessage(translate('Data were successfully inserted to the data base!')."::",$this->strInfoMessage);
			return(0);
		} else {
			$this->processClassMessage(translate('Error while inserting the data to the data base:')."::".$this->myDBClass->strErrorMessage."::",$this->strErrorMessage);
			return(1);
		}
  	}
	
    ///////////////////////////////////////////////////////////////////////////////////////////
  	//  Function: Delete data from the database
  	///////////////////////////////////////////////////////////////////////////////////////////
  	//
	//	Removes one or more dataset(s) from a table. Optinal a single data ID can be passed 
	//  or the values will be processed through the POST variable $_POST['chbId_n'] where 'n'
	//  represents the data ID.
  	//
  	//  This function does not delete relation data!
  	//
  	//  Parameters:  		$strTableName 	Table name
  	//            			$_POST[]    	Form variable (Checkboxes "chbId_n" n=DBId)
  	//						$intDataId    	Single data ID
  	//						$intTableId   	Table id for special relations
  	//
  	//  Return value:		0 = successful
	//						1 = error
	//						Status message is stored in message class variables
  	//
  	///////////////////////////////////////////////////////////////////////////////////////////
  	function dataDeleteEasy($strTableName,$intDataId=0) {
    	// Special rule for tables with "nodelete" cells
    	if (($strTableName == "tbl_datadomain") || ($strTableName == "tbl_configtarget") || ($strTableName == "tbl_user")) {
      		$strNoDelete = "AND `nodelete` <> '1'";
    	} else {
      		$strNoDelete = "";
    	}
    	// Delete a single data set
    	if ($intDataId != 0) {
      		$strSQL 	= "DELETE FROM `".$strTableName."` WHERE `id` = $intDataId $strNoDelete";
      		$booReturn 	= $this->myDBClass->insertData($strSQL);
      		if ($booReturn == false) {
				$this->processClassMessage(translate('Delete failed because a database error:')."::".mysql_error()."::",$this->strInfoMessage);
        		return(1);
      		} else if ($this->myDBClass->intAffectedRows == 0) {
				$this->processClassMessage(translate('No data deleted. Probably the dataset does not exist or it is protected from delete.')."::",$this->strErrorMessage);
        		return(1);
      		} else {
        		$this->strInfoMessage .= translate('Dataset successfully deleted. Affected rows:')." ".$this->myDBClass->intAffectedRows."::";
        		$this->writeLog(translate('Delete dataset id:')." $intDataId ".translate('- from table:')." $strTableName ".translate('- with affected rows:')." ".$this->myDBClass->intAffectedRows);
				$this->updateStatusTable($strTableName);
        		return(0);
      		}
		// Delete data sets based on form POST parameter	
    	} else {
      		$strSQL 	= "SELECT `id` FROM `".$strTableName."` WHERE 1=1 $strNoDelete";
      		$booReturn 	= $this->myDBClass->getDataArray($strSQL,$arrData,$intDataCount);
      		if ($booReturn && ($intDataCount != 0)) {
        		$intDeleteCount = 0;
        		foreach ($arrData AS $elem) {
          			$strChbName = "chbId_".$elem['id'];
          			// Should this data id to be deleted?
          			if (isset($_POST[$strChbName]) && ($_POST[$strChbName] == "on")) {
            			$strSQL = "DELETE FROM `".$strTableName."` WHERE `id` = ".$elem['id'];
            			$booReturn = $this->myDBClass->insertData($strSQL);
            			if ($booReturn == false) {
							$this->processClassMessage(translate('Delete failed because a database error:')."::".mysql_error()."::",$this->strInfoMessage);
              				return(1);
            			} else {
              				$intDeleteCount = $intDeleteCount + $this->myDBClass->intAffectedRows;
            			}
          			}
        		}
        		// Process messsages
        		if ($intDeleteCount == 0) {
					$this->processClassMessage(translate('No data deleted. Probably the dataset does not exist or it is protected from delete.')."::",$this->strErrorMessage);
          			return(1);
        		} else {
					$this->processClassMessage(translate('Dataset successfully deleted. Affected rows:')." ".$intDeleteCount."::",$this->strInfoMessage);
          			$this->writeLog(translate('Delete data from table:')." $strTableName ".translate('- with affected rows:')." ".$this->myDBClass->intAffectedRows);
					$this->updateStatusTable($strTableName);
          			return(0);
        		}
      		} else {
				$this->processClassMessage(translate('No data deleted. Probably the dataset does not exist or it is protected from delete.')."::",$this->strErrorMessage);
        		return(1);
      		}
    	}
  	}
	
    ///////////////////////////////////////////////////////////////////////////////////////////
  	//  Function: Delete data from the database with relations
  	///////////////////////////////////////////////////////////////////////////////////////////
  	//
	//	Removes one or more dataset(s) from a table. Optinal a single data ID can be passed 
	//  or the values will be processed through the POST variable $_POST['chbId_n'] where 'n'
	//  represents the data ID.
  	//
  	//  This function does also delete relation data!
	//
  	//  Parameters:  		$strTableName 	Table name
  	//            			$_POST[]    	Form variable (Checkboxes "chbId_n" n=DBId)
  	//						$intDataId    	Single data ID
  	//						$intForce   	Force deletion (1=force, 1=no force)
  	//
  	//  Return value:		0 = successful
	//						1 = error
	//						Status message is stored in message class variables
  	//
  	///////////////////////////////////////////////////////////////////////////////////////////
  	function dataDeleteFull($strTableName,$intDataId=0,$intForce=0) {
		// Get write access groups
		$strAccess = $this->myVisClass->getAccGroups('write');
    	// Get all relations
    	$this->fullTableRelations($strTableName,$arrRelations);
    	// Delete a single data set
    	if ($intDataId != 0) {
      		$strChbName  = "chbId_".$intDataId;
      		$_POST[$strChbName] = "on";
    	}
		// Get all datasets
		if ($strTableName == 'tbl_group') {
			$strSQL = "SELECT `id` FROM `".$strTableName."`";
		} else {
    		$strSQL = "SELECT `id` FROM `".$strTableName."` WHERE `config_id`=".$this->intDomainId." AND `access_group` IN ($strAccess)";
		}
    	$booReturn = $this->myDBClass->getDataArray($strSQL,$arrData,$intDataCount);
    	if ($booReturn && ($intDataCount != 0)) {
      		$intDeleteCount = 0;
      		$strFileMessage = "";
      		foreach ($arrData AS $elem) {
        		$strChbName = "chbId_".$elem['id'];
				// Single ID
				if (($intDataId != 0) && ($intDataId != $elem['id'])) continue;
          		// Should this data id to be deleted?
        		if (isset($_POST[$strChbName]) && ($_POST[$strChbName] == "on")) {
					// Check if deletion is possible (relations)
          			if (($this->infoRelation($strTableName,$elem['id'],"id",1) == 0) || ($intForce == 1)) {
            			// Delete relations
						if (!is_array($arrRelations)) $arrRelations = array();
            			foreach($arrRelations AS $rel) {
              				$strSQL = "";					
              				// Process flags
              				$arrFlags = explode(",",$rel['flags']);
              				// Simple 1:n relation
							if ($arrFlags[3] == 1) {
                				$strSQL = "DELETE FROM `".$rel['tableName1']."` WHERE `".$rel['fieldName']."`=".$elem['id'];
              				}
							// Simple 1:1 relation
              				if ($arrFlags[3] == 0) {
								// Delete relation
                				if ($arrFlags[2] == 0) {
                  					$strSQL = "DELETE FROM `".$rel['tableName1']."` WHERE `".$rel['fieldName']."`=".$elem['id'];
                				// Set slave to 0
								} else if ($arrFlags[2] == 2) {
                  					$strSQL = "UPDATE `".$rel['tableName1']."` SET `".$rel['fieldName']."`=0 WHERE `".$rel['fieldName']."`=".$elem['id'];
                				}
              				}
							// Special 1:n relation for variables
              				if ($arrFlags[3] == 2) {
                				$strSQL   = "SELECT * FROM `".$rel['tableName1']."` WHERE `idMaster`=".$elem['id'];
                				$booReturn  = $this->myDBClass->getDataArray($strSQL,$arrData,$intDataCount);
                				if ($booReturn && ($intDataCount != 0)) {
                  					foreach ($arrData AS $vardata) {
                    					$strSQL   = "DELETE FROM `tbl_variabledefinition` WHERE `id`=".$vardata['idSlave'];
                    					$booReturn  = $this->myDBClass->insertData($strSQL);
                  					}
                				}
                				$strSQL    = "DELETE FROM `".$rel['tableName1']."` WHERE `idMaster`=".$elem['id'];
              				}
							// Special 1:n relation for time definitions
              				if ($arrFlags[3] == 3) {
                				$strSQL   = "DELETE FROM `tbl_timedefinition` WHERE `tipId`=".$elem['id'];
                				$booReturn  = $this->myDBClass->insertData($strSQL);
							}
              				if ($strSQL != "") {
                				$booReturn  = $this->myDBClass->insertData($strSQL);
              				}
            			}
            			// Delete host configuration file
            			if (($strTableName == "tbl_host") && ($this->intDomainId != 0)) {
              				$strSQL    = "SELECT `host_name` FROM `tbl_host` WHERE `id`=".$elem['id'];
              				$strHost   = $this->myDBClass->getFieldData($strSQL);
							$arrConfigId = $this->myConfigClass->getConfigSets();
							if ($arrConfigId != 1) {
								$intReturn = 0;
								foreach($arrConfigId AS $intConfigId) {
									$this->myConfigClass->resConnectType = "none";
									$this->resConnectId = "";
									$intReturn += $this->myConfigClass->moveFile("host",$strHost.".cfg",$intConfigId);
								}
								if ($intReturn == 0) {
									$this->processClassMessage(translate('The assigned, no longer used configuration files were deleted successfully!')."::",$strFileMessage);
									$this->writeLog(translate('Host file deleted:')." ".$strHost.".cfg");
								} else {
									$strFileMessage .=  translate('Errors while deleting the old configuration file - please check!:')."::".$this->myConfigClass->strErrorMessage."::";
								}								
							}
            			}
            			// Delete service configuration file
            			if (($strTableName == "tbl_service") && ($this->intDomainId != 0)) {
              				$strSQL     = "SELECT `config_name` FROM `tbl_service` WHERE `id`=".$elem['id'];
              				$strService = $this->myDBClass->getFieldData($strSQL);
              				$strSQL     = "SELECT * FROM `tbl_service` WHERE `config_name` = '$strService'";
              				$booReturn  = $this->myDBClass->getDataArray($strSQL,$arrData,$intDataCount);
              				if ($intDataCount == 1) {
								$arrConfigId = $this->myConfigClass->getConfigSets();
								if ($arrConfigId != 1) {
									$intReturn = 0;
									foreach($arrConfigId AS $intConfigId) {
										$this->myConfigClass->resConnectType = "none";
										$this->resConnectId = "";
                						$intReturn += $this->myConfigClass->moveFile("service",$strService.".cfg",$intConfigId);
									}
									if ($intReturn == 0) {
										$this->processClassMessage(translate('The assigned, no longer used configuration files were deleted successfully!')."::",$strFileMessage);
										$this->writeLog(translate('Host file deleted:')." ".$strService.".cfg");
									} else {
										$strFileMessage .=  translate('Errors while deleting the old configuration file - please check!:')."::".$this->myConfigClass->strErrorMessage."::";
									}
								}
              				}
            			}
            			// delete main entry
            			$strSQL = "DELETE FROM `".$strTableName."` WHERE `id`=".$elem['id'];
            			$booReturn  = $this->myDBClass->insertData($strSQL);
            			$intDeleteCount++;
          			}
        		}
      		}
      		// Process messages
      		if ($intDeleteCount == 0) {
				$this->processClassMessage(translate('No data deleted. Probably the dataset does not exist, it is protected from deletion, you do not have write permission or it has relations to other configurations which cannot be deleted. Use the "info" function for detailed informations about relations!')."::",$this->strErrorMessage);
        		return(1);
      		} else {
				$this->updateStatusTable($strTableName);
				$this->processClassMessage(translate('Dataset successfully deleted. Affected rows:')." ".$intDeleteCount."::",$this->strInfoMessage);
        		$this->writeLog(translate('Delete data from table:')." $strTableName ".translate('- with affected rows:')." ".$intDeleteCount);
				$this->processClassMessage($strFileMessage,$this->strInfoMessage);
        		return(0);
      		}
    	} else {
			$this->processClassMessage(translate('No data deleted. Probably the dataset does not exist, it is protected from deletion or you do not have write permission.')."::".$this->myDBClass->strErrorMessage,$this->strErrorMessage);
      		return(1);
    	}
  	}
	
	///////////////////////////////////////////////////////////////////////////////////////////
  	//  Function: Copy dataset
  	///////////////////////////////////////////////////////////////////////////////////////////
  	//
  	//  Copies one or more records in a data table. Alternatively, an individual record ID
	//	are specified, or the values of the $_POST['chbId_n'] variable is used where n 
	//	is the record ID.
  	//
  	//  Parameters:  	$strTableName 		Table name
  	//            		$strKeyField  		Key field of the table
  	//            		$_POST[]    		Form variable (check boxes "chbId_n" n=DBId)
  	//            		$intDataId    		Singe data ID to copy
	//					$intDomainId		Target domain ID
  	//
  	//  Return values:		0 = successful
	//						1 = error
	//						Status message is stored in message class variables
  	//
  	///////////////////////////////////////////////////////////////////////////////////////////
  	function dataCopyEasy($strTableName,$strKeyField,$intDataId=0,$intDomainId=-1) {
		// Get write access groups
		$strAccess = $this->myVisClass->getAccGroups('write');
    	// Declare variables
    	$intError     			= 0;
    	$intNumber      		= 0;
		if ($intDomainId == -1) $intDomainId = $this->intDomainId;
		// Get all data ID from target table
		$strAccWhere = "WHERE `access_group` IN ($strAccess)";
		if (($strTableName == "tbl_user") || ($strTableName == "tbl_group")) $strAccWhere = "";
    	$booReturn = $this->myDBClass->getDataArray("SELECT `id` FROM `".$strTableName."` $strAccWhere ORDER BY `id`",$arrData,$intDataCount);
		if ($booReturn == false) {
			$this->processClassMessage(translate('Error while selecting data from database:')."::".$this->myDBClass->strErrorMessage."::",$this->strErrorMessage);
      		return(1);
    	} else if ($intDataCount != 0) {
			for ($i=0;$i<$intDataCount;$i++) {
				// Skip common domain value
				if ($arrData[$i]['id'] == 0) continue;
        		// Build the name of the form variable
        		$strChbName = "chbId_".$arrData[$i]['id'];
        		// If a form variable with this name exists or a matching single data ID was passed
        		if ((isset($_POST[$strChbName]) && ($intDataId == 0)) || ($intDataId == $arrData[$i]['id'])) {
          			// Get all data of this data ID
          			$this->myDBClass->getSingleDataset("SELECT * FROM `".$strTableName."` WHERE `id`=".$arrData[$i]['id'],$arrData[$i]);
          			// Build a temporary config name
          			for ($y=1;$y<=$intDataCount;$y++) {
            			$strNewName = $arrData[$i][$strKeyField]." ($y)";
						if (($strTableName == "tbl_user") || ($strTableName == "tbl_group") || ($strTableName == "tbl_datadomain") || ($strTableName == "tbl_configtarget")) {
            				$booReturn = $this->myDBClass->getFieldData("SELECT `id` FROM `".$strTableName."` WHERE `".$strKeyField."`='$strNewName'");
						} else {
							$booReturn = $this->myDBClass->getFieldData("SELECT `id` FROM `".$strTableName."` WHERE `".$strKeyField."`='$strNewName' AND `config_id`=$intDomainId");
						}
            			// If the name is unused -> break the loop
            			if ($booReturn == false) break;
          			}
					// Manually overwrite new name for extinfo tables
					if ($strTableName == "tbl_hostextinfo")   	$strNewName="0";
					if ($strTableName == "tbl_serviceextinfo")  $strNewName="0";
          			// Build the INSERT command based on the table name
          			$strSQLInsert = "INSERT INTO `".$strTableName."` SET `".$strKeyField."`='$strNewName',";
          			foreach($arrData[$i] AS $key => $value) {
            			if (($key != $strKeyField) && ($key != "active") && ($key != "last_modified") && ($key != "id") && ($key != "config_id")) {
							// manually set some NULL values based on field names
							if (($key == "normal_check_interval")   	&& ($value == ""))  $value="NULL";
							if (($key == "retry_check_interval")  		&& ($value == ""))  $value="NULL";
							if (($key == "max_check_attempts")    		&& ($value == ""))  $value="NULL";
							if (($key == "low_flap_threshold")    		&& ($value == ""))  $value="NULL";
							if (($key == "high_flap_threshold")   		&& ($value == ""))  $value="NULL";
							if (($key == "freshness_threshold")   		&& ($value == ""))  $value="NULL";
							if (($key == "notification_interval")   	&& ($value == ""))  $value="NULL";
							if (($key == "first_notification_delay")	&& ($value == ""))  $value="NULL";
							if (($key == "check_interval")      		&& ($value == ""))  $value="NULL";
							if (($key == "retry_interval")      		&& ($value == ""))  $value="NULL";
							// manually set some NULL values based on table name
							if (($strTableName == "tbl_serviceextinfo") && ($key == "service_description"))   $value="0";
							// Do not copy the password in tbl_user
							if (($strTableName == "tbl_user") && ($key == "password"))        $value="xxxxxxx";
							// Do not copy nodelete and webserver authentification values in tbl_user
							if ($key == "nodelete")	$value="0";
							if ($key == "wsauth")   $value="0";
							// If the data value is not "NULL", add single quotes to the value
							if ($value != "NULL") {
								$strSQLInsert .= "`".$key."`='".addslashes($value)."',";
							} else {
								$strSQLInsert .= "`".$key."`=".$value.",";
							}
						}
            		}
					if (($strTableName == "tbl_user") || ($strTableName == "tbl_group") || ($strTableName == "tbl_datadomain") || ($strTableName == "tbl_configtarget")) {
          				$strSQLInsert .= "`active`='0', `last_modified`=NOW()";
					} else {
          				$strSQLInsert .= "`active`='0', `config_id`=$intDomainId, `last_modified`=NOW()";
					}
          			// Insert the master dataset
          			$intCheck    = 0;
          			$booReturn   = $this->myDBClass->insertData($strSQLInsert);
          			$intMasterId = $this->myDBClass->intLastId;
          			if ($booReturn == false) $intCheck++;

          			// Copy relations
          			if (($this->tableRelations($strTableName,$arrRelations) != 0) && ($intCheck == 0)){
						foreach ($arrRelations AS $elem) {
							// Normal 1:n relation
							if ($elem['type'] == "2") {
								if ($arrData[$i][$elem['fieldName']] != 0) {
									$strSQL 	= "SELECT `idSlave`, `exclude` FROM `".$elem['linkTable']."` WHERE `idMaster` = ".$arrData[$i]['id'];
									$booReturn 	= $this->myDBClass->getDataArray($strSQL,$arrRelData,$intRelDataCount);
									if ($booReturn && ($intRelDataCount != 0)) {
										foreach ($arrRelData AS $elem2) {
											$strSQLRel = "INSERT INTO `".$elem['linkTable']."` 
														  SET `idMaster`=$intMasterId, `idSlave`=".$elem2['idSlave'].", `exclude`=".$elem2['exclude'];
											$booReturn   = $this->myDBClass->insertData($strSQLRel);
											if ($booReturn == false) $intCheck++;
										}
									}
								}
							// 1:n relation for templates
							} else if ($elem['type'] == "3") {
								if ($arrData[$i][$elem['fieldName']] == 1) {
									$strSQL 	= "SELECT `idSlave`,`idSort`,`idTable` FROM `".$elem['linkTable']."` WHERE `idMaster`=".$arrData[$i]['id'];
									$booReturn 	= $this->myDBClass->getDataArray($strSQL,$arrRelData,$intRelDataCount);
									if ($booReturn && ($intRelDataCount != 0)) {
										foreach ($arrRelData AS $elem2) {
											$strSQLRel = "INSERT INTO `".$elem['linkTable']."` 
														  SET `idMaster`=$intMasterId, `idSlave`=".$elem2['idSlave'].", `idTable`=".$elem2['idTable'].", 
															  `idSort`=".$elem2['idSort'];
											$booReturn   = $this->myDBClass->insertData($strSQLRel);
											if ($booReturn == false) $intCheck++;
										}
									}
								}
							// Special relation for free variables
							} else if ($elem['type'] == "4") {
								if ($arrData[$i][$elem['fieldName']] != 0) {
									$strSQL 	= "SELECT `idSlave` FROM `".$elem['linkTable']."` WHERE `idMaster` = ".$arrData[$i]['id'];
									$booReturn 	= $this->myDBClass->getDataArray($strSQL,$arrRelData,$intRelDataCount);
									if ($booReturn && ($intRelDataCount != 0)) {
										foreach ($arrRelData AS $elem2) {
											// Copy variables and link them to the new master
											$strSQL_Var = "SELECT * FROM `tbl_variabledefinition` WHERE `id`=".$elem2['idSlave'];
											$booReturn 	= $this->myDBClass->getDataArray($strSQL_Var,$arrData_Var,$intDC_Var);
											if ($booReturn && ($intDC_Var != 0)) {
												$strSQL_InsVar 	= "INSERT INTO `tbl_variabledefinition` 
																   SET `name`='".addslashes($arrData_Var[0]['name'])."', `value`='".addslashes($arrData_Var[0]['value'])."',
																	   `last_modified`=NOW()";
												$booReturn   	= $this->myDBClass->insertData($strSQL_InsVar);	
												if ($booReturn == false) $intCheck++;
												$strSQLRel 		= "INSERT INTO `".$elem['linkTable']."` 
																   SET `idMaster`=$intMasterId, `idSlave`=".$this->myDBClass->intLastId;
												$booReturn   = $this->myDBClass->insertData($strSQLRel);
												if ($booReturn == false) $intCheck++;
											}
										}
									}
								}
							// 1:n relation for tbl_lnkServicegroupToService
							} else if ($elem['type'] == "5") {
								if ($arrData[$i][$elem['fieldName']] != 0) {
									$strSQL = "SELECT `idSlaveH`,`idSlaveHG`,`idSlaveS` 
											   FROM `".$elem['linkTable']."` WHERE `idMaster`=".$arrData[$i]['id'];
									$booReturn = $this->myDBClass->getDataArray($strSQL,$arrRelData,$intRelDataCount);
									if ($booReturn && ($intRelDataCount != 0)) {
										foreach ($arrRelData AS $elem2) {
											$strSQLRel = "INSERT INTO `".$elem['linkTable']."` SET `idMaster`=$intMasterId,
														  `idSlaveH`=".$elem2['idSlaveH'].",`idSlaveHG`=".$elem2['idSlaveHG'].",
														  `idSlaveS`=".$elem2['idSlaveS'];
											$booReturn   = $this->myDBClass->insertData($strSQLRel);
											if ($booReturn == false) $intCheck++;
										}
									}
								}
							// 1:n relation for services
							} else if ($elem['type'] == "6") {
								if ($arrData[$i][$elem['fieldName']] != 0) {
									$strSQL = "SELECT `idSlave`, `strSlave`, `exclude` 
											   FROM `".$elem['linkTable']."` WHERE `idMaster`=".$arrData[$i]['id'];
									$booReturn = $this->myDBClass->getDataArray($strSQL,$arrRelData,$intRelDataCount);
									if ($booReturn && ($intRelDataCount != 0)) {
										foreach ($arrRelData AS $elem2) {
											$strSQLRel = "INSERT INTO `".$elem['linkTable']."` 
														  SET `idMaster`=$intMasterId, `idSlave`=".$elem2['idSlave'].", 
														  	  `strSlave`='".addslashes($elem2['strSlave'])."', `exclude`=".$elem2['exclude'];
											$booReturn   = $this->myDBClass->insertData($strSQLRel);
											if ($booReturn == false) $intCheck++;
										}
									}
								}
							}
          				}
						// 1:n relation for time definitions
						if ($strTableName == "tbl_timeperiod") {
							$strSQL = "SELECT * FROM `tbl_timedefinition` WHERE `tipId`=".$arrData[$i]['id'];
							$booReturn = $this->myDBClass->getDataArray($strSQL,$arrRelDataTP,$intRelDataCountTP);
							if ($intRelDataCountTP != 0) {
								foreach ($arrRelDataTP AS $elem) {
									$strSQLRel = "INSERT INTO `tbl_timedefinition` (`tipId`,`definition`,`range`,`last_modified`)
												  VALUES ($intMasterId,'".addslashes($elem['definition'])."','".addslashes($elem['range'])."',now())";
									$booReturn   = $this->myDBClass->insertData($strSQLRel);
									if ($booReturn == false) $intCheck++;
								}
							}
						}
						// 1:n relation for groups
						if ($strTableName == "tbl_group") {
							$strSQL    = "SELECT * FROM `".$elem['linkTable']."` WHERE `idMaster`=".$arrData[$i]['id'];
							$booReturn = $this->myDBClass->getDataArray($strSQL,$arrRelDataTP,$intRelDataCountTP);
							if ($intRelDataCountTP != 0) {
								foreach ($arrRelDataTP AS $elem2) {
									$strSQLRel = "INSERT INTO `".$elem['linkTable']."` (`idMaster`,`idSlave`,`read`,`write`,`link`)
												  VALUES ($intMasterId,'".$elem2['idSlave']."','".$elem2['read']."','".$elem2['write']."','".$elem2['link']."')";
									$booReturn   = $this->myDBClass->insertData($strSQLRel);
									if ($booReturn == false) $intCheck++;
								}
							}
						}
						// 1:n relation fot service to host connections
						if ($strTableName == "tbl_host") {
							$strSQL    = "SELECT * FROM `tbl_lnkServiceToHost` WHERE `idSlave`=".$arrData[$i]['id'];
							$booReturn = $this->myDBClass->getDataArray($strSQL,$arrRelDataSH,$intRelDataCountSH);
							if ($intRelDataCountSH != 0) {
								foreach ($arrRelDataSH AS $elem2) {
									$strSQLRel = "INSERT INTO `tbl_lnkServiceToHost` (`idMaster`,`idSlave`,`exclude`)
												  VALUES ('".$elem2['idMaster']."',$intMasterId,'".$elem2['exclude']."')";
									$booReturn   = $this->myDBClass->insertData($strSQLRel);
									if ($booReturn == false) $intCheck++;
								}
							}
						}
					}
					// Write logfile
					if ($intCheck != 0) {
						// Error
						$intError++;
						$this->writeLog(translate('Data set copy failed - table [new name]:')." ".$strTableName." [".$strNewName."]");
						$this->processClassMessage(translate('Data set copy failed - table [new name]:')." ".$strTableName." [".$strNewName."]::",$this->strInfoMessage);
					} else {
						// Success
						$this->writeLog(translate('Data set copied - table [new name]:')." ".$strTableName." [".$strNewName."]");
						$this->processClassMessage(translate('Data set copied - table [new name]:')." ".$strTableName." [".$strNewName."]::",$this->strInfoMessage);
					}
					$intNumber++;
        		}
      		}
			// Error processing
			if ($intNumber > 0) {
				if ($intError == 0) {
					// Success
					$this->processClassMessage(translate('Data were successfully inserted to the data base!')."::",$this->strInfoMessage);
					$this->updateStatusTable($strTableName);
					return(0);
				} else {
					// Error
					$this->processClassMessage(translate('Error while inserting the data to the data base:')."::".$this->myDBClass->strErrorMessage,$this->strInfoMessage);
					return(1);
				}
			} else {
				$this->processClassMessage(translate('No dataset copied. Maybe the dataset does not exist or you do not have write permission.')."::",$this->strErrorMessage);
				return(1);
			}
		} else {
			$this->processClassMessage(translate('No dataset copied. Maybe the dataset does not exist or you do not have write permission.')."::",$this->strErrorMessage);
			return(1);
    	}
  	}
	
  	///////////////////////////////////////////////////////////////////////////////////////////
  	//  Function: Activate datasets
  	///////////////////////////////////////////////////////////////////////////////////////////
  	//
  	//  Activates one or many datasets in the table be setting 'active' to '1'. Alternatively,
  	//  a single record ID can be specified or evaluated by the values of $_POST['chbId_n']
  	//  passed parameters, where n is the record ID must match.
  	//
  	//  This function only modifies the data from a single table!
  	//
  	//  Parameters:			$strTableName		table name
  	//            			$_POST[]    		form output (checkboxes "chbId_n" n=DBId)
  	//            			$intDataId    		Individual record ID, which is to be activate
  	//
  	//  Return values:		0 = successful
	//						1 = error
	//						Status message is stored in message class variables
  	//
  	///////////////////////////////////////////////////////////////////////////////////////////
  	function dataActivate($strTableName,$intDataId = 0) {
		// Get write access groups
		$strAccess = $this->myVisClass->getAccGroups('write');
    	// Activate a single dataset
    	if ($intDataId != 0) {
      		$strChbName = "chbId_".$intDataId;
      		$_POST[$strChbName] = "on";
    	}
    	// Activate datasets
    	$strSQL 	= "SELECT `id` FROM `".$strTableName."` WHERE `config_id`=".$this->intDomainId." AND `access_group` IN ($strAccess)";
    	$booReturn 	= $this->myDBClass->getDataArray($strSQL,$arrData,$intDataCount);
    	if ($booReturn && ($intDataCount != 0)) {
      		$intActivateCount = 0;
      		foreach ($arrData AS $elem) {
        		$strChbName = "chbId_".$elem['id'];
        		// was the current record is marked for activate?
        		if (isset($_POST[$strChbName]) && ($_POST[$strChbName] == "on")) {
					// Update dataset
					if (($strTableName == "tbl_service") || ($strTableName == "tbl_host")) {
						$strSQL = "UPDATE `".$strTableName."` SET `active`='1', `last_modified`=now() WHERE `id`=".$elem['id'];
					} else {
						$strSQL = "UPDATE `".$strTableName."` SET `active`='1' WHERE `id`=".$elem['id']; 	
					}
					$booReturn  = $this->myDBClass->insertData($strSQL);
					$intActivateCount++;
				}
      		}
      		// Process informations
      		if ($intActivateCount == 0) {
				$this->processClassMessage(translate('No dataset activated. Maybe the dataset does not exist, no dataset was selected or you do not have write permission.')."::",$this->strErrorMessage);
				return(1);
     		} else {
				$this->updateStatusTable($strTableName);
				$this->processClassMessage(translate('Dataset successfully activated. Affected rows:')." ".$intActivateCount."::",$this->strInfoMessage);
				$this->writeLog(translate('Activate dataset from table:')." $strTableName ".translate('- with affected rows:')." ".$this->myDBClass->intAffectedRows);
				return(0);
      		}
    	} else {
			$this->processClassMessage(translate('No dataset activated. Maybe the dataset does not exist or you do not have write permission.')."::",$this->strErrorMessage);
			return(1);
    	}
  	}
	
  	///////////////////////////////////////////////////////////////////////////////////////////
  	//  Function: Deactivate datasets
  	///////////////////////////////////////////////////////////////////////////////////////////
  	//
  	//  Deactivates one or many datasets in the table be setting 'active' to '0'. Alternatively,
  	//  a single record ID can be specified or evaluated by the values of $_POST['chbId_n']
  	//  passed parameters, where n is the record ID must match.
  	//
  	//  This function only modifies the data from a single table!
  	//
  	//  Parameters:			$strTableName		table name
  	//            			$_POST[]    		form output (checkboxes "chbId_n" n=DBId)
  	//            			$intDataId    		Individual record ID, which is to be activate
  	//
  	//  Return values:		0 = successful
	//						1 = error
	//						Status message is stored in message class variables
  	//
  	///////////////////////////////////////////////////////////////////////////////////////////
  	function dataDeactivate($strTableName,$intDataId = 0) {
		// Get write access groups
		$strAccess = $this->myVisClass->getAccGroups('write');
    	// Dectivate a single dataset
    	if ($intDataId != 0) {
      		$strChbName = "chbId_".$intDataId;
      		$_POST[$strChbName] = "on";
    	}
    	// Activate datasets
    	$strSQL 	= "SELECT `id` FROM `".$strTableName."` WHERE `config_id`=".$this->intDomainId." AND `access_group` IN ($strAccess)";
    	$booReturn 	= $this->myDBClass->getDataArray($strSQL,$arrData,$intDataCount);
    	if ($booReturn && ($intDataCount != 0)) {
      		$intActivateCount = 0;
      		foreach ($arrData AS $elem) {
        		$strChbName = "chbId_".$elem['id'];
        		// was the current record is marked for activate?
        		if (isset($_POST[$strChbName]) && ($_POST[$strChbName] == "on")) {
          			// Verify that the dataset can be deactivated
          			if ($this->infoRelation($strTableName,$elem['id'],"id",1) == 0) {
						// Update dataset
						if (($strTableName == "tbl_service") || ($strTableName == "tbl_host")) {
							$strSQL = "UPDATE `".$strTableName."` SET `active`='0', `last_modified`=now() WHERE `id`=".$elem['id'];
						} else {
							$strSQL = "UPDATE `".$strTableName."` SET `active`='0' WHERE `id`=".$elem['id']; 	
						}
						$booReturn  = $this->myDBClass->insertData($strSQL);
						$intActivateCount++;
		  			}
				}
      		}
      		// Process informations
      		if ($intActivateCount == 0) {
				$this->processClassMessage(translate('No dataset deactivated. Maybe the dataset does not exist, it is protected from deactivation, no dataset was selected or you do not have write permission. Use the "info" function for detailed informations about relations!')."::",$this->strErrorMessage);
        		return(1);
      		} else {
				$this->updateStatusTable($strTableName);
				$this->processClassMessage(translate('Dataset successfully deactivated. Affected rows:')." ".$intActivateCount."::",$this->strInfoMessage);
        		$this->writeLog(translate('Activate dataset from table:')." $strTableName ".translate('- with affected rows:')." ".$this->myDBClass->intAffectedRows);
        		return(0);
      		}
    	} else {
			$this->processClassMessage(translate('No dataset deactivated. Maybe the dataset does not exist or you do not have write permission.')."::",$this->strErrorMessage);
      		return(1);
    	}
  	}

  	///////////////////////////////////////////////////////////////////////////////////////////
  	//  Function: Write log book
  	///////////////////////////////////////////////////////////////////////////////////////////
  	//
  	//  Saves a given string to the logbook
  	//
  	//  Parameters:			$strLogMessage				Message string
  	//            			$_SESSION['username'] 	User name
  	//
  	//  Return values:		0 = successful
	//						1 = error
	//						Status message is stored in message class variables
  	//
  	///////////////////////////////////////////////////////////////////////////////////////////
  	function writeLog($strLogMessage) {
    	// Write string to database
		if (isset($_SERVER) && isset($_SERVER["REMOTE_ADDR"])) {
			// Webinterface
			$strUserName = (isset($_SESSION['username']) && ($_SESSION['username'] != ""))  ? $_SESSION['username'] : "unknown";
			$strDomain   = $this->myDBClass->getFieldData("SELECT `domain` FROM `tbl_datadomain` WHERE `id`=".$this->intDomainId);
			$booReturn   = $this->myDBClass->insertData("INSERT INTO `tbl_logbook` SET `user`='".$strUserName."',`time`=NOW(), `ipadress`='".$_SERVER["REMOTE_ADDR"]."', `domain`='$strDomain', `entry`='".addslashes($strLogMessage)."'");
			if ($booReturn == false) return(1);
			return(0);
		} else {
			// Scriptinginterface
			$strUserName = "scripting";
			if (isset($_SERVER['USER'])) $strUserName .= " - ".$_SERVER['USER'];
			$strDomain   = $this->myDBClass->getFieldData("SELECT `domain` FROM `tbl_datadomain` WHERE `id`=".$this->intDomainId);
			if (isset($_SERVER["HOSTNAME"])) {
				$booReturn   = $this->myDBClass->insertData("INSERT INTO `tbl_logbook` SET `user`='".$strUserName."',`time`=NOW(), `ipadress`='".$_SERVER["HOSTNAME"]."', `domain`='$strDomain', `entry`='".addslashes($strLogMessage)."'");
			} else if (isset($_SERVER["SSH_CLIENT"])) {
				$arrSSHClient = explode(" ",$_SERVER["SSH_CLIENT"]);
				$booReturn   = $this->myDBClass->insertData("INSERT INTO `tbl_logbook` SET `user`='".$strUserName."',`time`=NOW(), `ipadress`='".$arrSSHClient[0]."', `domain`='$strDomain', `entry`='".addslashes($strLogMessage)."'");	
			} else {
				$booReturn   = $this->myDBClass->insertData("INSERT INTO `tbl_logbook` SET `user`='".$strUserName."',`time`=NOW(), `ipadress`='unknown', `domain`='$strDomain', `entry`='".addslashes($strLogMessage)."'");
			}
			if ($booReturn == false) return(1);
			return(0);
		}
  	}
	
    ///////////////////////////////////////////////////////////////////////////////////////////
  	//  Function: Write relations to the database
  	///////////////////////////////////////////////////////////////////////////////////////////
  	//
	//  Inserts any necessary dataset for an 1:n (optional 1:n:n) relation to the
	//  database table
  	//
  	//  Parameters:			$strTable		Database table name
  	//						$intMasterId  	Data ID from master table
  	//						$arrSlaveId   	Array with all data IDs from slave table
  	//						$intMulti   	0 = for 1:n relations
	//										1 = for 1:n:n relations
  	//
	//  Return values:		0 = successful
	//						1 = error
	//						Status message is stored in message class variables
  	//
  	///////////////////////////////////////////////////////////////////////////////////////////
  	function dataInsertRelation($strTable,$intMasterId,$arrSlaveId,$intMulti=0) {
    	// Walk through the slave data ID array
    	foreach($arrSlaveId AS $elem) {
	  		// Pass empty and '*' values
      		if ($elem == '0') continue;
      		if ($elem == '*') continue;
	  		// Process exclude values
	  		if (substr($elem,0,1) == "e") {
				$elem       = str_replace("e","",$elem);
				$intExclude = 1;
	  		} else {
				$intExclude = 0;
	  		}
      		// Define the SQL statement
      		if ($intMulti != 0) {
        		$arrValues 	= "";
        		$arrValues 	= explode("::",$elem);
        		$strSQL 	= "INSERT INTO `".$strTable."` SET `idMaster`=$intMasterId, `idSlaveH`=".$arrValues[0].", 
				   			   `idSlaveHG`=".$arrValues[1].", `idSlaveS`=".$arrValues[2].",  `exclude`=$intExclude";
     		} else {
				if (($strTable == 'tbl_lnkServicedependencyToService_DS') || 
					($strTable == 'tbl_lnkServicedependencyToService_S')  || 
					($strTable == 'tbl_lnkServiceescalationToService'))   {
					// Get service description
					$strService = $this->myDBClass->getFieldData("SELECT `service_description` FROM `tbl_service` WHERE id=$elem");
		  			$strSQL 	= "INSERT INTO `".$strTable."` SET `idMaster`=$intMasterId, `idSlave`=$elem, 
								   `strSlave`='".addslashes($strService)."', `exclude`=$intExclude";
				} else if (($strTable != 'tbl_lnkTimeperiodToTimeperiod') && ($strTable != 'tbl_lnkDatadomainToConfigtarget')) {
        			$strSQL = "INSERT INTO `".$strTable."` SET `idMaster`=$intMasterId, `idSlave`=$elem, `exclude`=$intExclude";
				} else {
        			$strSQL = "INSERT INTO `".$strTable."` SET `idMaster`=$intMasterId, `idSlave`=$elem";	
				}
      		}
      		// Insert data
	  		$intReturn = $this->dataInsert($strSQL,$intDataID);
      		if ($intReturn != 0) return(1);
    	}
    	return(0);
  	}

    ///////////////////////////////////////////////////////////////////////////////////////////
  	//  Function: Update relations in the database
  	///////////////////////////////////////////////////////////////////////////////////////////
  	//
	//  Update the datasets for 1:n (optional 1:n:m) relations in the database table
  	//
  	//  Parameters:			$strTable		Database table name
  	//						$intMasterId  	Data ID from master table
  	//						$arrSlaveId   	Array with all data IDs from slave table
  	//						$intMulti   	0 = for 1:n relations
	//										1 = for 1:n:n relations
  	//
	//  Return values:		0 = successful
	//						1 = error
	//						Status message is stored in message class variables
  	//
  	///////////////////////////////////////////////////////////////////////////////////////////
  	function dataUpdateRelation($strTable,$intMasterId,$arrSlaveId,$intMulti=0) {
    	// Remove any old relations
    	$intReturn1 = $this->dataDeleteRelation($strTable,$intMasterId);
    	if ($intReturn1 != 0) return(1);
    	// Insert the new relations
    	$intReturn2 = $this->dataInsertRelation($strTable,$intMasterId,$arrSlaveId,$intMulti);
    	if ($intReturn2 != 0) return(1);
    	return(0);
  	}

    ///////////////////////////////////////////////////////////////////////////////////////////
  	//  Function: Remove relations from the database
  	///////////////////////////////////////////////////////////////////////////////////////////
  	//
  	//  Removes any relation from the database
  	//
  	//  Parameters:			$strTable		Database table name
  	//						$intMasterId  	Data ID from master table
  	//
	//  Return values:		0 = successful
	//						1 = error
	//						Status message is stored in message class variables
  	//
  	///////////////////////////////////////////////////////////////////////////////////////////
  	function dataDeleteRelation($strTable,$intMasterId) {
    	// Define the SQL statement
    	$strSQL = "DELETE FROM `".$strTable."` WHERE `idMaster`=$intMasterId";
    	// Send statement
    	$intReturn = $this->dataInsert($strSQL,$intDataID);
    	if ($intReturn != 0) return(1);
    	return(0);
  	}
	
    ///////////////////////////////////////////////////////////////////////////////////////////
  	//  Function: Scan database for relations
  	///////////////////////////////////////////////////////////////////////////////////////////
  	//
	//  Searches any relation in the database and returns them as relation information
  	//
  	//  Parameters:			$strTable		Database table name
  	//						$intMasterId  	Data ID from master table
  	//						$strMasterfield Info field name from master table
  	//						$intReporting 	Output as text - 0=yes, 1=no
  	//
	//  Return values:		0 = successful
	//						1 = error
	//						Status message is stored in message class variables
  	//
  	///////////////////////////////////////////////////////////////////////////////////////////
  	function infoRelation($strTable,$intMasterId,$strMasterfield,$intReporting=0) {
    	$intReturn = $this->fullTableRelations($strTable,$arrRelations);
    	$intDeletion = 0;
    	if ($intReturn == 1) {
    		// Get master field data
			$strNewMasterfield 	= str_replace(',','`,`',$strMasterfield);
      		$strSQL  			= "SELECT `".$strNewMasterfield."` FROM `".$strTable."` WHERE `id` = $intMasterId";
      		$this->myDBClass->getSingleDataset($strSQL,$arrSource);
      		if (substr_count($strMasterfield,",") != 0) {
        		$arrTarget 	= explode(",",$strMasterfield);
        		$strName 	= $arrSource[$arrTarget[0]]."-".$arrSource[$arrTarget[1]];
      		} else {
        		$strName 	= $arrSource[$strMasterfield];
      		}
      		$this->strInfoMessage .= "<span class=\"blackmessage\">".translate("Relation information for <b>").$strName.translate("</b> of table <b>").$strTable.":</b></span>::";
      		$this->strInfoMessage .= "<span class=\"bluemessage\">";
			// Walk through relations
	  		foreach ($arrRelations AS $elem) {
        		// Process flags
        		$arrFlags = explode(",",$elem['flags']);
        		if ($elem['fieldName'] == "check_command") {
          			$strSQL   = "SELECT * FROM `".$elem['tableName1']."` WHERE SUBSTRING_INDEX(`".$elem['fieldName']."`,'!',1)= $intMasterId";
        		} else {
          			$strSQL   = "SELECT * FROM `".$elem['tableName1']."` WHERE `".$elem['fieldName']."`= $intMasterId";
        		}
        		$booReturn  = $this->myDBClass->getDataArray($strSQL,$arrData,$intDataCount);
        		// Take only used relations
        		if ($booReturn && ($intDataCount != 0)) {
          			// Relation type
          			if ($arrFlags[3] == 1) {
            			foreach ($arrData AS $data) {
              				if ($elem['fieldName'] == "idMaster") {
                				$strRef = "idSlave";
                				// Process special tables
								if ($elem['target1'] == "tbl_service") {
                  					if ($elem['tableName1'] == "tbl_lnkServicegroupToService") {
                    					$strRef = "idSlaveS";
                  					}
                				} else if ($elem['target1'] == "tbl_host") {
                  					if ($elem['tableName1'] == "tbl_lnkServicegroupToService") {
                    					$strRef = "idSlaveH";
                  					}
                				} else if ($elem['target1'] == "tbl_hostgroup") {
                  					if ($elem['tableName1'] == "tbl_lnkServicegroupToService") {
                    					$strRef = "idSlaveHG";
                  					}
                				}
              				} else {
                				$strRef = "idMaster";
              				}
              				// Get data
              				$strSQL = "SELECT * FROM `".$elem['tableName1']."`
                     				   LEFT JOIN `".$elem['target1']."` ON `".$strRef."` = `id`
                     				   WHERE `".$elem['fieldName']."` = ".$data[$elem['fieldName']]."
                       				   AND `".$strRef."`=".$data[$strRef];
              				$this->myDBClass->getSingleDataset($strSQL,$arrDSTarget);
              				if (substr_count($elem['targetKey'],",") != 0) {
                				$arrTarget = explode(",",$elem['targetKey']);
               					$strTarget = $arrDSTarget[$arrTarget[0]]."-".$arrDSTarget[$arrTarget[1]];
              				} else {
                				$strTarget = $arrDSTarget[$elem['targetKey']];
              				}
              				// If the field is market as "required", check for any other entries
              				if ($arrFlags[0] == 1) {
                				$strSQL = "SELECT * FROM `".$elem['tableName1']."`
                       					   WHERE `".$strRef."` = ".$arrDSTarget[$strRef];
                				$booReturn  = $this->myDBClass->getDataArray($strSQL,$arrDSCount,$intDCCount);
                				if ($booReturn && ($intDCCount > 1)) {
                  					$this->strInfoMessage .= translate("Relation to <b>").$elem['target1'].translate("</b>, entry <b>").$strTarget."</b> - <span style=\"color:#00CC00;\">".translate("deletion <b>possible</b>")."</span>::";
                				} else {
                  					$this->strInfoMessage .= translate("Relation to <b>").$elem['target1'].translate("</b>, entry <b>").$strTarget."</b> - <span style=\"color:#FF0000;\">".translate("deletion <b>not possible</b>")."</span>::";
                  					$intDeletion = 1;
                				}
              				} else {
                				$this->strInfoMessage .= translate("Relation to <b>").$elem['target1'].translate("</b>, entry <b>").$strTarget."</b> - <span style=\"color:#00CC00;\">".translate("deletion <b>possible</b>")."</span>::";
              				}
            			}
          			} else if ($arrFlags[3] == 0) {
            			// Fetch remote entry
            			$strSQL = "SELECT * FROM `".$elem['tableName1']."` WHERE `".$elem['fieldName']."`=$intMasterId";
            			$booReturn  = $this->myDBClass->getDataArray($strSQL,$arrDataCheck,$intDCCheck);
            			if ($booReturn && ($intDCCheck != 0)) {
							foreach ($arrDataCheck AS $data) {
              					if (substr_count($elem['targetKey'],",") != 0) {
                					$arrTarget = explode(",",$elem['targetKey']);
                					$strTarget = $data[$arrTarget[0]]."-".$data[$arrTarget[1]];
              					} else {
                					$strTarget = $data[$elem['targetKey']];
              					}
              					if ($arrFlags[0] == 1) {
                					$this->strInfoMessage .= translate("Relation to <b>").$elem['tableName1'].translate("</b>, entry <b>").$strTarget."</b> - <span style=\"color:#FF0000;\">".translate("deletion <b>not possible</b>")."</span>::";
                					$intDeletion = 1;
              					} else {
                					$this->strInfoMessage .= translate("Relation to <b>").$elem['tableName1'].translate("</b>, entry <b>").$strTarget."</b> - <span style=\"color:#00CC00;\">".translate("deletion <b>possible</b>")."</span>::";
              					}
            				}
						}
          			}
        		}
      		}
      		$this->strInfoMessage .= "</span>::";
    	}
		if ($intReporting == 1) $this->strInfoMessage = ""; 
    	return($intDeletion);
  	}

    ///////////////////////////////////////////////////////////////////////////////////////////
  	//  Function: Update the status table
  	///////////////////////////////////////////////////////////////////////////////////////////
  	//
	//  Update the date inside the status table (used for last modified date)
   	//
  	//  Parameters:			$strTable   	Table name
  	//
	//  Return values:		0 = successful
	//						1 = error
	//						Status message is stored in message class variables
  	//
  	///////////////////////////////////////////////////////////////////////////////////////////
  	function updateStatusTable($strTable) {
		// Does the entry exist
		$strSQL 	= "SELECT * FROM `tbl_tablestatus` WHERE `tableName`='$strTable' AND `domainId`=".$this->intDomainId;
		$booReturn  = $this->myDBClass->getDataArray($strSQL,$arrData,$intDC);
		if ($booReturn && ($intDC != 0)) {
			$strSQL 	= "UPDATE `tbl_tablestatus` SET `updateTime`=NOW() WHERE `tableName`='$strTable' AND `domainId`=".$this->intDomainId;
			$booReturn 	= $this->dataInsert($strSQL,$intDataID);
			if ($booReturn) return(0);
		} else if ($booReturn) {
			$strSQL 	= "INSERT INTO `tbl_tablestatus` SET `updateTime`=NOW(), `tableName`='$strTable', `domainId`=".$this->intDomainId;
			$booReturn 	= $this->dataInsert($strSQL,$intDataID);
			if ($booReturn) return(0);
		}
		return(1);
  	}

    ///////////////////////////////////////////////////////////////////////////////////////////
  	//  Function: Get relation data for a table
  	///////////////////////////////////////////////////////////////////////////////////////////
  	//
	//  Returns an array of all datafields of a table, which has an 1:1 or 1:n relation
	//  to anothe table.
   	//
  	//  Parameters:			$strTable   	Table name
	//  
  	//  Link type:			1 -> 1:1 Relation
  	//						2 -> 1:n Relation
  	//						3 -> 1:n Relation for templates
  	//						4 -> 1:n Relation for free variables
  	//
	//  Return values:		$arrRelations 	Array with relations
  	//  					0 = successful
	//						1 = error
	//						Status message is stored in message class variables
  	//
  	///////////////////////////////////////////////////////////////////////////////////////////
  	function tableRelations($strTable,&$arrRelations) {
		// Define variable
		$arrRelations 	= "";
		// Get relation data
		$strSQL 		= "SELECT * FROM tbl_relationinformation WHERE master='$strTable' AND fullRelation=0";
		$booReturn  	= $this->myDBClass->getDataArray($strSQL,$arrData,$intDC);
		if ($booReturn && ($intDC != 0)) {
			foreach ($arrData AS $elem) {
				$arrRelations[] = array('tableName1' => $elem['tableName1'],	'tableName2' => $elem['tableName2'],		
										'fieldName'  => $elem['fieldName'], 	'linkTable'  => $elem['linkTable'],			
										'target1'    => $elem['target1'], 		'target2'    => $elem['target2'], 					
										'type' 		 => $elem['type']);
			}
			return(1);
		} else {
			return(0);
		}
  	}
	
    ///////////////////////////////////////////////////////////////////////////////////////////
  	//  Function: Get full relation data for a table
  	///////////////////////////////////////////////////////////////////////////////////////////
  	//
	//  Returns an array with any data fields from a table with existing relations to another
	//  table. This function returns also passive relations which are not used in 
	//  configurations
    //
	//  This function is used for a full deletion of a configuration entry or to find out
	//  if a configuration is used in another way.
  	//
  	//  Parameters:			$strTable   	Table name
	//  
  	//  Data array:			tableName		Table include the relation data
	//						fieldName		Field name include the relation data
	//						flags			Pos 1 -> 0=Normal field / 1=Required field		(field type)
  	//										Pos 2 -> 0=delete / 1=keep data / 2=set to 0 	(normal deletion option)
  	//										Pos 3 -> 0=delete / 2=set to 0					(force deletion option)
  	//										Pos 4 -> 0=1:1 / 1=1:n / 						(relation type)
	//												 2=1:n (variables) / 3=1:n (timedef)
	//												 
  	//
	//  Return values:		$arrRelations 	Array with relations
  	//  					0 = no field with relation
	//						1 = at least one field with relation
	//						Status message is stored in message class variables
  	//
  	///////////////////////////////////////////////////////////////////////////////////////////
  	function fullTableRelations($strTable,&$arrRelations) {
		// Define variable
		$arrRelations 	= "";
		// Get relation data
		$strSQL 		= "SELECT * FROM tbl_relationinformation WHERE master='$strTable' AND fullRelation=1";
		$booReturn  	= $this->myDBClass->getDataArray($strSQL,$arrData,$intDC);
		if ($booReturn && ($intDC != 0)) {
			foreach ($arrData AS $elem) {
				$arrRelations[] = array('tableName1' => $elem['tableName1'], 	'fieldName'  => $elem['fieldName'],		
										'target1'    => $elem['target1'],  		'targetKey'  => $elem['targetKey'],	
										'flags' 	 => $elem['flags']);
				}
			return(1);
		} else {
			return(0);
		}
  	}
	
    ///////////////////////////////////////////////////////////////////////////////////////////
  	//  Function: Update configuration hash
  	///////////////////////////////////////////////////////////////////////////////////////////
  	//
	//  Updates the hash field im some configuration objects
  	//
  	//  Parameters:			$strTable   	Table name
	//						$intId			Data ID
	//  
	//  Return values:		0 = successful
	//						1 = error
	//						Status message is stored in message class variables
  	//
  	///////////////////////////////////////////////////////////////////////////////////////////
	function updateHash($strTable,$intId) {
		$strRawString  	= "";
		if ($strTable == "tbl_service") {
			// Get any hosts and host_groups
			$strSQL  = "SELECT `host_name` AS `item_name` FROM `tbl_host` LEFT JOIN `tbl_lnkServiceToHost` ON `idSlave`=`id` WHERE `idMaster`=".$intId;
			$strSQL .= " UNION SELECT `hostgroup_name` AS `item_name` FROM `tbl_hostgroup` LEFT JOIN `tbl_lnkServiceToHostgroup` ON `idSlave`=`id` 
					    WHERE `idMaster`=".$intId." ORDER BY `item_name`";
			$booRet  = $this->myDBClass->getDataArray($strSQL,$arrData,$intDC);
			if ($booRet && ($intDC != 0)) {
				foreach ($arrData AS $elem) {
					$strRawString .= $elem['item_name'].",";
				}
			}
			$strSQL = "SELECT * FROM `tbl_service` WHERE `id`=".$intId;
			$booRet  = $this->myDBClass->getDataArray($strSQL,$arrData,$intDC);
			if ($booRet && ($intDC != 0)) {
				if ($arrData[0]['service_description'] != "") $strRawString .= $arrData[0]['service_description'].",";
				if ($arrData[0]['display_name'] != "") 		  $strRawString .= $arrData[0]['display_name'].",";
				if ($arrData[0]['check_command'] != "") {
					$arrField   	= explode("!",$arrData[0]['check_command']);
					$strCommand 	= strchr($arrData[0]['check_command'],"!");
					$strSQLRel  	= "SELECT `command_name` FROM `tbl_command`
								   	   WHERE `id`=".$arrField[0];
					$strName 		= $this->myDBClass->getFieldData($strSQLRel);
					$strRawString  .= $strName.$strCommand.",";
				}
			}
		}
		if (($strTable == "tbl_hostdependency") || ($strTable == "tbl_servicedependency")) {
			// Get * values
			$strSQL = "SELECT * FROM `".$strTable."` WHERE `id`=".$intId;
			$booRet  = $this->myDBClass->getDataArray($strSQL,$arrData,$intDC);
			if ($booRet && ($intDC != 0)) {
				if (isset($arrData[0]['dependent_host_name']) 			&& ($arrData[0]['dependent_host_name'] == 2)) 				$strRawString .= "any,";
				if (isset($arrData[0]['dependent_hostgroup_name']) 		&& ($arrData[0]['dependent_hostgroup_name'] == 2)) 			$strRawString .= "any,";
				if (isset($arrData[0]['host_name']) 					&& ($arrData[0]['host_name'] == 2)) 						$strRawString .= "any,";
				if (isset($arrData[0]['hostgroup_name']) 				&& ($arrData[0]['hostgroup_name'] == 2)) 					$strRawString .= "any,";
				if (isset($arrData[0]['dependent_service_description']) && ($arrData[0]['dependent_service_description'] == 2)) 	$strRawString .= "any,";
				if (isset($arrData[0]['service_description']) 			&& ($arrData[0]['service_description'] == 2)) 				$strRawString .= "any,";
			}
			if ($strTable == "tbl_hostdependency") {
				// Get any hosts and host_groups
				$strSQL  = "SELECT `host_name` AS `item_name`, exclude FROM `tbl_host` 
							LEFT JOIN `tbl_lnkHostdependencyToHost_DH` ON `idSlave`=`id` WHERE `idMaster`=".$intId." ";
				$strSQL .= "UNION ALL SELECT `hostgroup_name` AS `item_name`, exclude FROM `tbl_hostgroup` 
							LEFT JOIN `tbl_lnkHostdependencyToHostgroup_DH` ON `idSlave`=`id` WHERE `idMaster`=".$intId." ";
				$strSQL .= "UNION ALL SELECT `host_name` AS `item_name`, exclude FROM `tbl_host` 
							LEFT JOIN `tbl_lnkHostdependencyToHost_H` ON `idSlave`=`id` WHERE `idMaster`=".$intId." ";
				$strSQL .= "UNION ALL SELECT `hostgroup_name` AS `item_name`, exclude FROM `tbl_hostgroup` 
							LEFT JOIN `tbl_lnkHostdependencyToHostgroup_H` ON `idSlave`=`id` WHERE `idMaster`=".$intId;
			}
			if ($strTable == "tbl_servicedependency") {
				// Get any hosts and host_groups
				$strSQL  = "SELECT `host_name` AS `item_name`, exclude FROM `tbl_host` 
							LEFT JOIN `tbl_lnkServicedependencyToHost_DH` ON `idSlave`=`id` WHERE `idMaster`=".$intId." ";
				$strSQL .= "UNION ALL SELECT `hostgroup_name` AS `item_name`, exclude FROM `tbl_hostgroup` 
							LEFT JOIN `tbl_lnkServicedependencyToHostgroup_DH` ON `idSlave`=`id` WHERE `idMaster`=".$intId." ";
				$strSQL .= "UNION ALL SELECT `host_name` AS `item_name`, exclude FROM `tbl_host` 
							LEFT JOIN `tbl_lnkServicedependencyToHost_H` ON `idSlave`=`id` WHERE `idMaster`=".$intId." ";
				$strSQL .= "UNION ALL SELECT `hostgroup_name` AS `item_name`, exclude FROM `tbl_hostgroup` 
							LEFT JOIN `tbl_lnkServicedependencyToHostgroup_H` ON `idSlave`=`id` WHERE `idMaster`=".$intId." ";
				$strSQL .= "UNION ALL SELECT `strSlave` AS `item_name`, exclude FROM `tbl_lnkServicedependencyToService_DS` 
							WHERE `idMaster`=".$intId." ";
				$strSQL .= "UNION ALL SELECT `strSlave` AS `item_name`, exclude FROM `tbl_lnkServicedependencyToService_S` 
							WHERE `idMaster`=".$intId." ";
			}
			$booRet  = $this->myDBClass->getDataArray($strSQL,$arrData,$intDC);
			if ($booRet && ($intDC != 0)) {
				foreach ($arrData AS $elem) {
					if ($elem['exclude'] == 0) {
						$strRawString .= $elem['item_name'].",";
					} else {
						$strRawString .= "not_".$elem['item_name'].",";
					}
				}
				$strRawString = substr($strRawString,0,-1);
			}
		}
		if (($strTable == "tbl_hostescalation") || ($strTable == "tbl_serviceescalation")) {
			// Get * values
			$strSQL = "SELECT * FROM `".$strTable."` WHERE `id`=".$intId;
			$booRet  = $this->myDBClass->getDataArray($strSQL,$arrData,$intDC);
			if ($booRet && ($intDC != 0)) {
				if (isset($arrData[0]['host_name']) 			&& ($arrData[0]['host_name'] == 2)) 			$strRawString .= "any,";
				if (isset($arrData[0]['hostgroup_name']) 		&& ($arrData[0]['hostgroup_name'] == 2)) 		$strRawString .= "any,";
				if (isset($arrData[0]['contacts']) 				&& ($arrData[0]['contacts'] == 2)) 				$strRawString .= "any,";
				if (isset($arrData[0]['contact_groups']) 		&& ($arrData[0]['contact_groups'] == 2)) 		$strRawString .= "any,";
				if (isset($arrData[0]['service_description'])	&& ($arrData[0]['service_description'] == 2))	$strRawString .= "any,";

			}
			// Get any hosts, host_groups, contacts and contact_groups
			if ($strTable == "tbl_hostescalation") {
				$strSQL  = "SELECT `host_name` AS `item_name`, exclude FROM `tbl_host` 
							LEFT JOIN `tbl_lnkHostescalationToHost` ON `idSlave`=`id` WHERE `idMaster`=".$intId." ";
				$strSQL .= "UNION ALL SELECT `hostgroup_name` AS `item_name`, exclude  FROM `tbl_hostgroup` 
							LEFT JOIN `tbl_lnkHostescalationToHostgroup` ON `idSlave`=`id` WHERE `idMaster`=".$intId." ";
				$strSQL .= "UNION ALL SELECT `contact_name` AS `item_name`, exclude  FROM `tbl_contact` 
							LEFT JOIN `tbl_lnkHostescalationToContact` ON `idSlave`=`id` WHERE `idMaster`=".$intId." ";
				$strSQL .= "UNION ALL SELECT `contactgroup_name` AS `item_name`, exclude  FROM `tbl_contactgroup` 
							LEFT JOIN `tbl_lnkHostescalationToContactgroup` ON `idSlave`=`id` WHERE `idMaster`=".$intId;
			}
			if ($strTable == "tbl_serviceescalation") {
				$strSQL  = "SELECT `host_name` AS `item_name`, exclude FROM `tbl_host` 
							LEFT JOIN `tbl_lnkServiceescalationToHost` ON `idSlave`=`id` WHERE `idMaster`=".$intId." ";
				$strSQL .= "UNION ALL SELECT `hostgroup_name` AS `item_name`, exclude  FROM `tbl_hostgroup` 
							LEFT JOIN `tbl_lnkServiceescalationToHostgroup` ON `idSlave`=`id` WHERE `idMaster`=".$intId." ";
				$strSQL .= "UNION ALL SELECT `contact_name` AS `item_name`, exclude  FROM `tbl_contact` 
							LEFT JOIN `tbl_lnkServiceescalationToContact` ON `idSlave`=`id` WHERE `idMaster`=".$intId." ";
				$strSQL .= "UNION ALL SELECT `contactgroup_name` AS `item_name`, exclude  FROM `tbl_contactgroup` 
							LEFT JOIN `tbl_lnkServiceescalationToContactgroup` ON `idSlave`=`id` WHERE `idMaster`=".$intId." ";
				$strSQL .= "UNION ALL SELECT `strSlave` AS `item_name`, exclude FROM `tbl_lnkServiceescalationToService` 
							WHERE `idMaster`=".$intId;
			}
			$booRet  = $this->myDBClass->getDataArray($strSQL,$arrData,$intDC);
			if ($booRet && ($intDC != 0)) {
				foreach ($arrData AS $elem) {
					if ($elem['exclude'] == 0) {
						$strRawString .= $elem['item_name'].",";
					} else {
						$strRawString .= "not_".$elem['item_name'].",";
					}
				}
				$strRawString = substr($strRawString,0,-1);
			}
		}
		if ($strTable == "tbl_serviceextinfo") {
			// Get any hosts and host_groups
			$strSQL  = "SELECT `tbl_host`.`host_name` AS `item_name` FROM `tbl_host` 
						LEFT JOIN `tbl_serviceextinfo` ON `tbl_host`.`id`=`tbl_serviceextinfo`.`host_name` WHERE `tbl_serviceextinfo`.`id`=".$intId." ";
			$strSQL .= "UNION SELECT `tbl_service`.`service_description` AS `item_name` FROM `tbl_service` 
						LEFT JOIN `tbl_serviceextinfo` ON `tbl_service`.`id`=`tbl_serviceextinfo`.`service_description` WHERE `tbl_serviceextinfo`.`id`=".$intId." 
						ORDER BY `item_name`";
			$booRet  = $this->myDBClass->getDataArray($strSQL,$arrData,$intDC);
			if ($booRet && ($intDC != 0)) {
				foreach ($arrData AS $elem) {
					$strRawString .= $elem['item_name'].",";
				}
				$strRawString = substr($strRawString,0,-1);
			}
		}		
		// Remove blanks
		while (substr_count($strRawString," ") != 0) {
			$strRawString = str_replace(" ","",$strRawString);
		}
		// Sort hash string
		$arrTemp = explode(",",$strRawString);
		sort($arrTemp);
		$strRawString = implode(",",$arrTemp);
		// Update has in database
		$strSQL 	= "UPDATE `".$strTable."` SET `import_hash`='".sha1($strRawString)."' WHERE `id`='$intId'";
		$intReturn 	= $this->dataInsert($strSQL,$intDataID);
		//echo "Hash: ".$strRawString." --> ".sha1($strRawString)."<br>";
		return($intReturn);
  	}
	///////////////////////////////////////////////////////////////////////////////////////////
	//  Function: Processing message strings
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
}
?>
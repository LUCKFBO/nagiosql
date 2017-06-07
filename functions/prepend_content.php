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
// Date      : $LastChangedDate: 2017-06-07 17:30:00 -0300$
//
///////////////////////////////////////////////////////////////////////////////
//
// Define common variables
// =======================
$intLineCount     	= 0;   // Database line count
$intWriteAccessId 	= 0;   // Write access to data id ($chkDataId)
$intReadAccessId	= 0;   // Read access to data id ($chkListId)
$intDataWarning 	= 0;   // Missing data indicator
$intNoTime			= 0;   // Show modified time list (0=show) 
$strSearchWhere 	= "";  // SQL WHERE addon for searching
$strSearchWhere2	= "";  // SQL WHERE addon for configuration selection list
//
// Define missing variables used in this prepend file
// ==================================================
if (!isset($preTableName)) 		$preTableName		= ""; // Predefined variable table name
if (!isset($preSearchSession)) 	$preSearchSession	= ""; // Predefined variable search session
//
// Store some variables to content class
// =====================================
$myContentClass->intLimit			= $chkLimit;
$myContentClass->intVersion			= $intVersion;
$myContentClass->strBrowser			= $preBrowser;
$myContentClass->intGroupAdm		= $chkGroupAdm;
$myContentClass->strTableName		= $preTableName;
$myContentClass->strSearchSession	= $preSearchSession;
$myContentClass->intSortBy			= $hidSortBy;
$myContentClass->strSortDir			= $hidSortDir;
//
// Process get parameters
// ======================
$chkFromLine  	= isset($_GET['from_line'])   	? filter_var($_GET['from_line'], FILTER_SANITIZE_NUMBER_INT)	: 0;
//
// Process post parameters
// =======================
$chkTfSearch	= isset($_POST['txtSearch'])	? $_POST['txtSearch']		: "";			// Search field
$chkSelAccGr	= isset($_POST['selAccGr'])		? $_POST['selAccGr']+0		: 0;			// Access group
$chkSelCnfName  = isset($_POST['selCnfName'])	? $_POST['selCnfName']      : "";			// Config name selection field
//
$chkTfValue1	= isset($_POST['tfValue1'])		? $_POST['tfValue1']		: "";			// Common text field value
$chkTfValue2	= isset($_POST['tfValue2'])		? $_POST['tfValue2']		: "";			// Common text field value
$chkTfValue3	= isset($_POST['tfValue3'])		? $_POST['tfValue3']		: "";			// Common text field value
$chkTfValue4	= isset($_POST['tfValue4'])		? $_POST['tfValue4']		: "";			// Common text field value
$chkTfValue5	= isset($_POST['tfValue5'])		? $_POST['tfValue5']		: "";			// Common text field value
$chkTfValue6	= isset($_POST['tfValue6'])		? $_POST['tfValue6']		: "";			// Common text field value
$chkTfValue7	= isset($_POST['tfValue7'])		? $_POST['tfValue7']		: "";			// Common text field value
$chkTfValue8	= isset($_POST['tfValue8'])		? $_POST['tfValue8']		: "";			// Common text field value
$chkTfValue9	= isset($_POST['tfValue9'])		? $_POST['tfValue9']		: "";			// Common text field value
$chkTfValue10	= isset($_POST['tfValue10'])	? $_POST['tfValue10']		: "";			// Common text field value
$chkTfValue11	= isset($_POST['tfValue11'])	? $_POST['tfValue11']		: "";			// Common text field value
$chkTfValue12	= isset($_POST['tfValue12'])	? $_POST['tfValue12']		: "";			// Common text field value
$chkTfValue13	= isset($_POST['tfValue13'])	? $_POST['tfValue13']		: "";			// Common text field value
$chkTfValue14	= isset($_POST['tfValue14'])	? $_POST['tfValue14']		: "";			// Common text field value
$chkTfValue15	= isset($_POST['tfValue15'])	? $_POST['tfValue15']		: "";			// Common text field value
$chkTfValue16	= isset($_POST['tfValue16'])	? $_POST['tfValue16']		: "";			// Common text field value
$chkTfValue17	= isset($_POST['tfValue17'])	? $_POST['tfValue17']		: "";			// Common text field value
$chkTfValue18	= isset($_POST['tfValue18'])	? $_POST['tfValue18']		: "";			// Common text field value
$chkTfValue19	= isset($_POST['tfValue19'])	? $_POST['tfValue19']		: "";			// Common text field value
$chkTfValue20	= isset($_POST['tfValue20'])	? $_POST['tfValue20']		: "";			// Common text field value
$chkTfArg1		= isset($_POST['tfArg1'])		? $_POST['tfArg1']			: "";			// Common argument text field value
$chkTfArg2		= isset($_POST['tfArg2'])		? $_POST['tfArg2']			: "";			// Common argument text field value
$chkTfArg3		= isset($_POST['tfArg3'])		? $_POST['tfArg3']			: "";			// Common argument text field value
$chkTfArg4		= isset($_POST['tfArg4'])		? $_POST['tfArg4']			: "";			// Common argument text field value
$chkTfArg5		= isset($_POST['tfArg5'])		? $_POST['tfArg5']			: "";			// Common argument text field value
$chkTfArg6		= isset($_POST['tfArg6'])		? $_POST['tfArg6']			: "";			// Common argument text field value
$chkTfArg7		= isset($_POST['tfArg7'])		? $_POST['tfArg7']			: "";			// Common argument text field value
$chkTfArg8		= isset($_POST['tfArg8'])		? $_POST['tfArg8']			: "";			// Common argument text field value
$chkMselValue1	= isset($_POST['mselValue1'])	? $_POST['mselValue1']		: array("");	// Common multi select field value
$chkMselValue2	= isset($_POST['mselValue2'])	? $_POST['mselValue2']		: array("");	// Common multi select field value
$chkMselValue3	= isset($_POST['mselValue3'])	? $_POST['mselValue3']		: array("");	// Common multi select field value
$chkMselValue4	= isset($_POST['mselValue4'])	? $_POST['mselValue4']		: array("");	// Common multi select field value
$chkMselValue5	= isset($_POST['mselValue5'])	? $_POST['mselValue5']		: array("");	// Common multi select field value
$chkMselValue6	= isset($_POST['mselValue6'])	? $_POST['mselValue6']		: array("");	// Common multi select field value
$chkMselValue7	= isset($_POST['mselValue7'])	? $_POST['mselValue7']		: array("");	// Common multi select field value
$chkMselValue8	= isset($_POST['mselValue8'])	? $_POST['mselValue8']		: array("");	// Common multi select field value
$chkChbValue1	= isset($_POST['chbValue1'])	? $_POST['chbValue1']+0		: 0;			// Common checkbox field value
$chkChbValue2	= isset($_POST['chbValue2'])	? $_POST['chbValue2']+0		: 0;			// Common checkbox field value
$chkDatValue1	= isset($_POST['datValue1'])  	? $_POST['datValue1'] 		: "";			// Common file selection field
$chkTaValue1	= isset($_POST['taValue1'])		? $_POST['taValue1']		: "";			// Common text area value
$chkTaFileText	= isset($_POST['taFileText'])	? $_POST['taFileText']		: "";			// Common text area value for file import (not SQL)
$chkSelValue1	= isset($_POST['selValue1'])	? $_POST['selValue1']+0		: 0;			// Common select field value
$chkSelValue2	= isset($_POST['selValue2'])	? $_POST['selValue2']+0		: 0;			// Common select field value
$chkSelValue3	= isset($_POST['selValue3'])	? $_POST['selValue3']+0		: 0;			// Common select field value
$chkSelValue4	= isset($_POST['selValue4'])	? $_POST['selValue4']+0		: 0;			// Common select field value
$chkSelValue5	= isset($_POST['selValue5'])	? $_POST['selValue5']+0		: 0;			// Common select field value
$chkRadValue1	= isset($_POST['radValue1'])	? $_POST['radValue1']+0		: 2;			// Common radio field value
$chkRadValue2	= isset($_POST['radValue2'])	? $_POST['radValue2']+0		: 2;			// Common radio field value
$chkRadValue3	= isset($_POST['radValue3'])	? $_POST['radValue3']+0		: 2;			// Common radio field value
$chkRadValue4	= isset($_POST['radValue4'])	? $_POST['radValue4']+0		: 2;			// Common radio field value
$chkRadValue5	= isset($_POST['radValue5'])	? $_POST['radValue5']+0		: 2;			// Common radio field value
$chkRadValue6	= isset($_POST['radValue6'])	? $_POST['radValue6']+0		: 2;			// Common radio field value
$chkRadValue7	= isset($_POST['radValue7'])	? $_POST['radValue7']+0		: 2;			// Common radio field value
$chkRadValue8	= isset($_POST['radValue8'])	? $_POST['radValue8']+0		: 2;			// Common radio field value
$chkRadValue9	= isset($_POST['radValue9'])	? $_POST['radValue9']+0		: 2;			// Common radio field value
$chkRadValue10	= isset($_POST['radValue10'])	? $_POST['radValue10']+0	: 2;			// Common radio field value
$chkRadValue11	= isset($_POST['radValue11'])	? $_POST['radValue11']+0	: 2;			// Common radio field value
$chkRadValue12	= isset($_POST['radValue12'])	? $_POST['radValue12']+0	: 2;			// Common radio field value
$chkRadValue13	= isset($_POST['radValue13'])	? $_POST['radValue13']+0	: 2;			// Common radio field value
$chkRadValue14	= isset($_POST['radValue14'])	? $_POST['radValue14']+0	: 2;			// Common radio field value
$chkRadValue15	= isset($_POST['radValue15'])	? $_POST['radValue15']+0	: 2;			// Common radio field value
$chkRadValue16	= isset($_POST['radValue16'])	? $_POST['radValue16']+0	: 2;			// Common radio field value
$chkRadValue17	= isset($_POST['radValue17'])	? $_POST['radValue17']+0	: 2;			// Common radio field value
$chkChbGr1a		= isset($_POST['chbGr1a'])		? $_POST['chbGr1a'].","		: "";			// Common checkbox group
$chkChbGr1b		= isset($_POST['chbGr1b'])		? $_POST['chbGr1b'].","		: "";			// Common checkbox group
$chkChbGr1c		= isset($_POST['chbGr1c'])		? $_POST['chbGr1c'].","		: "";			// Common checkbox group
$chkChbGr1d		= isset($_POST['chbGr1d'])		? $_POST['chbGr1d'].","		: "";			// Common checkbox group
$chkChbGr1e		= isset($_POST['chbGr1e'])		? $_POST['chbGr1e'].","		: "";			// Common checkbox group
$chkChbGr1f		= isset($_POST['chbGr1f'])		? $_POST['chbGr1f'].","		: "";			// Common checkbox group
$chkChbGr1g		= isset($_POST['chbGr1g'])		? $_POST['chbGr1g'].","		: "";			// Common checkbox group
$chkChbGr1h		= isset($_POST['chbGr1h'])		? $_POST['chbGr1h'].","		: "";			// Common checkbox group
$chkChbGr2a		= isset($_POST['chbGr2a'])		? $_POST['chbGr2a'].","		: "";			// Common checkbox group
$chkChbGr2b		= isset($_POST['chbGr2b'])		? $_POST['chbGr2b'].","		: "";			// Common checkbox group
$chkChbGr2c		= isset($_POST['chbGr2c'])		? $_POST['chbGr2c'].","		: "";			// Common checkbox group
$chkChbGr2d		= isset($_POST['chbGr2d'])		? $_POST['chbGr2d'].","		: "";			// Common checkbox group
$chkChbGr2e		= isset($_POST['chbGr2e'])		? $_POST['chbGr2e'].","		: "";			// Common checkbox group
$chkChbGr2f		= isset($_POST['chbGr2f'])		? $_POST['chbGr2f'].","		: "";			// Common checkbox group
$chkChbGr2g		= isset($_POST['chbGr2g'])		? $_POST['chbGr2g'].","		: "";			// Common checkbox group
$chkChbGr2h		= isset($_POST['chbGr2h'])		? $_POST['chbGr2h'].","		: "";			// Common checkbox group
$chkChbGr3a		= isset($_POST['chbGr3a'])		? $_POST['chbGr3a'].","		: "";			// Common checkbox group
$chkChbGr3b		= isset($_POST['chbGr3b'])		? $_POST['chbGr3b'].","		: "";			// Common checkbox group
$chkChbGr3c		= isset($_POST['chbGr3c'])		? $_POST['chbGr3c'].","		: "";			// Common checkbox group
$chkChbGr3d		= isset($_POST['chbGr3d'])		? $_POST['chbGr3d'].","		: "";			// Common checkbox group
$chkChbGr4a		= isset($_POST['chbGr4a'])		? $_POST['chbGr4a'].","		: "";			// Common checkbox group
$chkChbGr4b		= isset($_POST['chbGr4b'])		? $_POST['chbGr4b'].","		: "";			// Common checkbox group
$chkChbGr4c		= isset($_POST['chbGr4c'])		? $_POST['chbGr4c'].","		: "";			// Common checkbox group
$chkChbGr4d		= isset($_POST['chbGr4d'])		? $_POST['chbGr4d'].","		: "";			// Common checkbox group
$chkButValue1	= isset($_POST['butValue1'])	? $_POST['butValue1']		: "";			// Common button value
$chkButValue2	= isset($_POST['butValue2'])	? $_POST['butValue2']		: "";			// Common button value
$chkButValue3	= isset($_POST['butValue3'])	? $_POST['butValue3']		: "";			// Common button value
$chkButValue4	= isset($_POST['butValue4'])	? $_POST['butValue4']		: "";			// Common button value
$chkButValue5	= isset($_POST['butValue5'])	? $_POST['butValue5']		: "";			// Common button value
$chkTfNullVal1	= (isset($_POST['tfNullVal1']) && ($_POST['tfNullVal1'] != "")) ? $myVisClass->checkNull($_POST['tfNullVal1'])+0 : "NULL";	// Common text NULL field value
$chkTfNullVal2	= (isset($_POST['tfNullVal2']) && ($_POST['tfNullVal2'] != "")) ? $myVisClass->checkNull($_POST['tfNullVal2'])+0 : "NULL";	// Common text NULL field value
$chkTfNullVal3	= (isset($_POST['tfNullVal3']) && ($_POST['tfNullVal3'] != "")) ? $myVisClass->checkNull($_POST['tfNullVal3'])+0 : "NULL";	// Common text NULL field value
$chkTfNullVal4	= (isset($_POST['tfNullVal4']) && ($_POST['tfNullVal4'] != "")) ? $myVisClass->checkNull($_POST['tfNullVal4'])+0 : "NULL";	// Common text NULL field value
$chkTfNullVal5	= (isset($_POST['tfNullVal5']) && ($_POST['tfNullVal5'] != "")) ? $myVisClass->checkNull($_POST['tfNullVal5'])+0 : "NULL";	// Common text NULL field value
$chkTfNullVal6	= (isset($_POST['tfNullVal6']) && ($_POST['tfNullVal6'] != "")) ? $myVisClass->checkNull($_POST['tfNullVal6'])+0 : "NULL";	// Common text NULL field value
$chkTfNullVal7	= (isset($_POST['tfNullVal7']) && ($_POST['tfNullVal7'] != "")) ? $myVisClass->checkNull($_POST['tfNullVal7'])+0 : "NULL";	// Common text NULL field value
$chkTfNullVal8	= (isset($_POST['tfNullVal8']) && ($_POST['tfNullVal8'] != "")) ? $myVisClass->checkNull($_POST['tfNullVal8'])+0 : "NULL";	// Common text NULL field value
//
// Quote special characters
// ==========================
if (get_magic_quotes_gpc() == 0) {
  	$chkTfSearch	= addslashes($chkTfSearch);
  	$chkTfValue1    = addslashes($chkTfValue1);
  	$chkTfValue2    = addslashes($chkTfValue2);
	$chkTfValue3    = addslashes($chkTfValue3);
	$chkTfValue4    = addslashes($chkTfValue4);
	$chkTfValue5    = addslashes($chkTfValue5);
	$chkTfValue6    = addslashes($chkTfValue6);
	$chkTfValue7    = addslashes($chkTfValue7);
	$chkTfValue8    = addslashes($chkTfValue8);
	$chkTfValue9    = addslashes($chkTfValue9);
	$chkTfValue10   = addslashes($chkTfValue10);
	$chkTfValue11   = addslashes($chkTfValue11);
  	$chkTfValue12   = addslashes($chkTfValue12);
  	$chkTfValue13   = addslashes($chkTfValue13);
	$chkTfValue14   = addslashes($chkTfValue14);
	$chkTfValue15   = addslashes($chkTfValue15);
	$chkTfValue16   = addslashes($chkTfValue16);
	$chkTfValue17   = addslashes($chkTfValue17);
	$chkTfValue18   = addslashes($chkTfValue18);
	$chkTfValue19   = addslashes($chkTfValue19);
	$chkTfValue20   = addslashes($chkTfValue20);
	$chkTaValue1    = addslashes($chkTaValue1);
	$chkTfArg1		= addslashes($chkTfArg1);	
	$chkTfArg2		= addslashes($chkTfArg2);
	$chkTfArg3		= addslashes($chkTfArg3);
	$chkTfArg4		= addslashes($chkTfArg4);
	$chkTfArg5		= addslashes($chkTfArg5);
	$chkTfArg6		= addslashes($chkTfArg6);
	$chkTfArg7		= addslashes($chkTfArg7);
	$chkTfArg8		= addslashes($chkTfArg8);
	$chkTaFileText  = addslashes($chkTaFileText);
}
//
// Security function for text fields
// =================================
$chkTfSearch	= $myVisClass->tfSecure($chkTfSearch);
$chkTfValue1    = $myVisClass->tfSecure($chkTfValue1);
$chkTfValue2    = $myVisClass->tfSecure($chkTfValue2);
$chkTfValue3    = $myVisClass->tfSecure($chkTfValue3);
$chkTfValue4    = $myVisClass->tfSecure($chkTfValue4);
$chkTfValue5    = $myVisClass->tfSecure($chkTfValue5);
$chkTfValue6    = $myVisClass->tfSecure($chkTfValue6);
$chkTfValue7    = $myVisClass->tfSecure($chkTfValue7);
$chkTfValue8    = $myVisClass->tfSecure($chkTfValue8);
$chkTfValue9    = $myVisClass->tfSecure($chkTfValue9);
$chkTfValue10   = $myVisClass->tfSecure($chkTfValue10);
$chkTfValue11   = $myVisClass->tfSecure($chkTfValue11);
$chkTfValue12   = $myVisClass->tfSecure($chkTfValue12);
$chkTfValue13   = $myVisClass->tfSecure($chkTfValue13);
$chkTfValue14   = $myVisClass->tfSecure($chkTfValue14);
$chkTfValue15   = $myVisClass->tfSecure($chkTfValue15);
$chkTfValue16   = $myVisClass->tfSecure($chkTfValue16);
$chkTfValue17   = $myVisClass->tfSecure($chkTfValue17);
$chkTfValue18   = $myVisClass->tfSecure($chkTfValue18);
$chkTfValue19   = $myVisClass->tfSecure($chkTfValue19);
$chkTfValue20   = $myVisClass->tfSecure($chkTfValue20);
$chkTfArg1		= $myVisClass->tfSecure($chkTfArg1);	
$chkTfArg2		= $myVisClass->tfSecure($chkTfArg2);
$chkTfArg3		= $myVisClass->tfSecure($chkTfArg3);
$chkTfArg4		= $myVisClass->tfSecure($chkTfArg4);
$chkTfArg5		= $myVisClass->tfSecure($chkTfArg5);
$chkTfArg6		= $myVisClass->tfSecure($chkTfArg6);
$chkTfArg7		= $myVisClass->tfSecure($chkTfArg7);
$chkTfArg8		= $myVisClass->tfSecure($chkTfArg8);
$chkTaValue1    = $myVisClass->tfSecure($chkTaValue1);
$chkTaFileText  = stripslashes($chkTaFileText);
//
// Multiselect data processing
// ===========================
if (($chkMselValue1[0]	== "")  || ($chkMselValue1[0] == "0"))	{$intMselValue1	= 0;}  else {$intMselValue1 = 1;}
if ($chkMselValue1[0]	== "*") $intMselValue1 = 2;
if (($chkMselValue2[0]	== "")  || ($chkMselValue2[0] == "0"))	{$intMselValue2	= 0;}  else {$intMselValue2 = 1;}
if ($chkMselValue2[0]	== "*") $intMselValue2 = 2;
if (($chkMselValue3[0]	== "")  || ($chkMselValue3[0] == "0"))	{$intMselValue3	= 0;}  else {$intMselValue3 = 1;}
if ($chkMselValue3[0]	== "*") $intMselValue3 = 2;
if (($chkMselValue4[0]	== "")  || ($chkMselValue4[0] == "0"))	{$intMselValue4	= 0;}  else {$intMselValue4 = 1;}
if ($chkMselValue4[0]	== "*") $intMselValue4 = 2;
if (($chkMselValue5[0]	== "")  || ($chkMselValue5[0] == "0"))	{$intMselValue5	= 0;}  else {$intMselValue5 = 1;}
if ($chkMselValue5[0]	== "*") $intMselValue5 = 2;
if (($chkMselValue6[0]	== "")  || ($chkMselValue6[0] == "0"))	{$intMselValue6	= 0;}  else {$intMselValue6 = 1;}
if ($chkMselValue6[0]	== "*") $intMselValue6 = 2;
if (($chkMselValue7[0]	== "")  || ($chkMselValue7[0] == "0"))	{$intMselValue7	= 0;}  else {$intMselValue7 = 1;}
if ($chkMselValue7[0]	== "*") $intMselValue7 = 2;
if (($chkMselValue8[0]	== "")  || ($chkMselValue8[0] == "0"))	{$intMselValue8	= 0;}  else {$intMselValue8 = 1;}
if ($chkMselValue8[0]	== "*") $intMselValue8 = 2;
//
// Search/sort/filter - session data
// =================================
if (!isset($_SESSION['search']) || !isset($_SESSION['search'][$preSearchSession]))  $_SESSION['search'][$preSearchSession]  = "";
if (!isset($_SESSION['search']) || !isset($_SESSION['search']['config_selection'])) $_SESSION['search']['config_selection'] = "";
if (($chkModus == "checkform") || ($chkModus == "filter")) {
  	$_SESSION['search'][$preSearchSession]  = $chkTfSearch;
	$_SESSION['search']['config_selection'] = $chkSelCnfName;
}
//
// Process additional templates/variables
// ======================================
if (isset($_SESSION['templatedefinition']) && is_array($_SESSION['templatedefinition']) && (count($_SESSION['templatedefinition']) != 0)) {
  $intTemplates = 1;
} else {
  $intTemplates = 0;
}
if (isset($_SESSION['variabledefinition']) && is_array($_SESSION['variabledefinition']) && (count($_SESSION['variabledefinition']) != 0)) {
  $intVariables = 1;
} else {
  $intVariables = 0;
}
//
// Common SQL parts
// ================
if ($hidActive   == 1) $chkActive = 1;
if ($chkGroupAdm == 1) {$strGroupSQL = "`access_group`=$chkSelAccGr, ";} else {$strGroupSQL = "";}
$preSQLCommon1 = "$strGroupSQL `active`='$chkActive', `register`='$chkRegister', `config_id`=$chkDomainId, `last_modified`=NOW()";
$preSQLCommon2 = "$strGroupSQL `active`='$chkActive', `register`='0', `config_id`=$chkDomainId, `last_modified`=NOW()";
$intRet1=0;$intRet2=0;$intRet3=0;$intRet4=0;$intRet5=0;$intRet6=0;$intRet7=0;$intRet8=0;
//
// Check read and write access
// ===========================
if (isset($prePageKey)) {
	$intGlobalReadAccess  = $myVisClass->checkAccGroup($prePageKey,'read'); 	// Global read access (0 = access granted)
	$intGlobalWriteAccess = $myVisClass->checkAccGroup($prePageKey,'write');	// Global write access (0 = access granted)
	$myContentClass->intGlobalWriteAccess = $intGlobalWriteAccess;
}
if (!isset($preNoAccessGrp) || ($preNoAccessGrp == 0)) {
	if ($chkDataId != 0) {
		$strSQLWrite 		= "SELECT `access_group` FROM `$preTableName` WHERE id=$chkDataId";
		$intWriteAccessId 	= $myVisClass->checkAccGroup(($myDBClass->getFieldData($strSQLWrite)+0),'write');
		$myContentClass->intWriteAccessId = $intWriteAccessId;
	}
	if ($chkListId != 0) {
		$strSQLWrite 		= "SELECT `access_group` FROM `$preTableName` WHERE id=$chkListId";
		$intReadAccessId 	= $myVisClass->checkAccGroup(($myDBClass->getFieldData($strSQLWrite)+0),'read');
		$intWriteAccessId 	= $myVisClass->checkAccGroup(($myDBClass->getFieldData($strSQLWrite)+0),'write');
		$myContentClass->intWriteAccessId = $intWriteAccessId;
	}
}
//
// Data processing
// ===============
if (($chkModus == "make") && ($intGlobalWriteAccess == 0)) {
	$intError 	= 0;
	$intSuccess = 0;	
	// Get write access groups
	$strAccess = $myVisClass->getAccGroups('write');
	// Write configuration file
	if ($preTableName == 'tbl_host') {
		$strSQL    = "SELECT `id` FROM `$preTableName` WHERE $strDomainWhere AND `access_group` IN ($strAccess) AND `active`='1'";
		$booReturn = $myDBClass->getDataArray($strSQL,$arrData,$intDataCount);
		if ($booReturn == false) $myVisClass->processMessage($myDBClass->strErrorMessage,$strErrorMessage);
		if ($booReturn && ($intDataCount != 0)) {
			foreach ($arrData AS $data) {
				$intReturn = $myConfigClass->createConfigSingle("$preTableName",$data['id']);
				if ($intReturn == 1){ 
					$intError++;
					$myVisClass->processMessage($myConfigClass->strErrorMessage,$strErrorMessage);
				} else { 
					$intSuccess++; 
				}
			}
		} else {
			$myVisClass->processMessage(translate('Some configuration files were not written. Dataset not activated, not found or you do not have write permission!'),$strErrorMessage);
		}
		if ($intSuccess != 0) $myVisClass->processMessage(translate('Configuration files successfully written!'),$strInfoMessage);
		if ($intError   != 0) $myVisClass->processMessage(translate('Some configuration files were not written. Dataset not activated, not found or you do not have write permission!'),$strErrorMessage);
	} else if ($preTableName == 'tbl_service') {
  		$strSQL  = "SELECT `id`, `$preKeyField` FROM `$preTableName` WHERE $strDomainWhere AND `access_group` IN ($strAccess) AND `active`='1' GROUP BY `$preKeyField`, `id`";
  		$myDBClass->getDataArray($strSQL,$arrData,$intDataCount);
		if ($booReturn == false) $myVisClass->processMessage($myDBClass->strErrorMessage,$strErrorMessage);
  		if ($booReturn && ($intDataCount != 0)) {
    		foreach ($arrData AS $data) {
      			$intReturn = $myConfigClass->createConfigSingle("$preTableName",$data['id']);
				if ($intReturn == 1){ 
					$intError++;
					$myVisClass->processMessage($myConfigClass->strErrorMessage,$strErrorMessage);
				} else { 
					$intSuccess++; 
				}
    		}
		} else {
			$myVisClass->processMessage(translate('Some configuration files were not written. Dataset not activated, not found or you do not have write permission!'),$strErrorMessage);
		}
		if ($intSuccess != 0) $myVisClass->processMessage(translate('Configuration files successfully written!'),$strInfoMessage);
		if ($intError   != 0) $myVisClass->processMessage(translate('Some configuration files were not written. Dataset not activated, not found or you do not have write permission!'),$strErrorMessage);
	} else {
		$intReturn = $myConfigClass->createConfig($preTableName,0);
		if ($intReturn == 1) $myVisClass->processMessage($myConfigClass->strErrorMessage,$strErrorMessage);
		if ($intReturn == 0) $myVisClass->processMessage($myConfigClass->strInfoMessage,$strInfoMessage);
	}
  	$chkModus  = "display";
} else if (($chkModus == "checkform") && ($chkSelModify == "info")) {
	// Display additional relation information
	if ($preTableName == 'tbl_service') {
		$intReturn = $myDataClass->infoRelation($preTableName,$chkListId,"$preKeyField,service_description");
	} else {
  		$intReturn = $myDataClass->infoRelation($preTableName,$chkListId,$preKeyField);
	}
	$myVisClass->processMessage($myDataClass->strInfoMessage,$strConsistMessage);
  	$chkModus  = "display";
} else if (($chkModus == "checkform") && ($chkSelModify == "delete") && ($intGlobalWriteAccess == 0)) {
	// Delete selected datasets
	if (($preTableName == 'tbl_user') && ($chkTfValue5 == "Admin")) {
		$myVisClass->processMessage(translate("Admin can't be deleted"),$strErrorMessage);
		$intReturn = 0;
	} else if ((($preTableName == 'tbl_datadomain') || ($preTableName == 'tbl_configtarget')) && ($chkTfValue3 == "localhost")) {
		$myVisClass->processMessage(translate("Localhost can't be deleted"),$strErrorMessage);
		$intReturn = 0;
	} else if (($preTableName == 'tbl_user') || ($preTableName == 'tbl_datadomain') || ($preTableName == 'tbl_configtarget')) {
		$intReturn = $myDataClass->dataDeleteEasy($preTableName,$chkListId);
	} else {
		$intReturn = $myDataClass->dataDeleteFull($preTableName,$chkListId);
	}
	if ($intReturn == 1) $myVisClass->processMessage($myDataClass->strErrorMessage,$strErrorMessage);
	if ($intReturn == 0) $myVisClass->processMessage($myDataClass->strInfoMessage,$strInfoMessage);
  	$chkModus  = "display";
} else if (($chkModus == "checkform") && ($chkSelModify == "copy") && ($intGlobalWriteAccess == 0)) {
	// Copy selected datasets
  	$intReturn = $myDataClass->dataCopyEasy($preTableName,$preKeyField,$chkListId,$chkSelTargetDomain);
	if ($intReturn == 1) $myVisClass->processMessage($myDataClass->strErrorMessage,$strErrorMessage);
	if ($intReturn == 0) $myVisClass->processMessage($myDataClass->strInfoMessage,$strInfoMessage);
  	$chkModus = "display";
} else if (($chkModus == "checkform") && ($chkSelModify == "activate") && ($intGlobalWriteAccess == 0)) {
	// Activate selected datasets
	$intReturn = $myDataClass->dataActivate($preTableName,$chkListId);
	if ($intReturn == 1) $myVisClass->processMessage($myDataClass->strErrorMessage,$strErrorMessage);
	if ($intReturn == 0) $myVisClass->processMessage($myDataClass->strInfoMessage,$strInfoMessage);
	$chkModus  = "display";
} else if (($chkModus == "checkform") && ($chkSelModify == "deactivate") && ($intGlobalWriteAccess == 0)) {
	// Deactivate selected datasets
	$intReturn = $myDataClass->dataDeactivate($preTableName,$chkListId);
	if ($intReturn == 1) $myVisClass->processMessage($myDataClass->strErrorMessage,$strErrorMessage);
	if ($intReturn == 0) $myVisClass->processMessage($myDataClass->strInfoMessage,$strInfoMessage);
	// Remove deactivated files
	if ($preTableName == 'tbl_host') {
    	if ($chkListId != 0) {
      		$strChbName = "chbId_".$chkListId;
      		$_POST[$strChbName] = "on";
    	}
		// Get write access groups
		$strAccess = $myVisClass->getAccGroups('write');
    	// Getting data sets
    	$strSQL 	= "SELECT `id`, `host_name` FROM `".$preTableName."` WHERE `active`='0' AND `access_group` IN ($strAccess) AND `config_id`=".$chkDomainId;
    	$booReturn 	= $myDBClass->getDataArray($strSQL,$arrData,$intDataCount);
    	if ($booReturn && ($intDataCount != 0) && ($chkDomainId != 0)) {
			$arrConfigID  = $myConfigClass->getConfigSets();
			$intError   = 0;
			$intSuccess = 0;
			if (($arrConfigID != 1) && is_array($arrConfigID)) {
				foreach ($arrData AS $elem) {
					$strChbName = "chbId_".$elem['id'];
					// was the current record is marked for deactivate?
					if (isset($_POST[$strChbName]) && ($_POST[$strChbName] == "on")) {
						$intCount  = 0;
						$intReturn = 0;
						foreach($arrConfigID AS $intConfigID) {
							$intReturn += $myConfigClass->moveFile("host",$elem['host_name'].".cfg",$intConfigID);
							if ($intReturn == 0) {
								$myDataClass->writeLog(translate('Host file deleted:')." ".$elem['host_name'].".cfg");
								$intCount++;
							}
						}
						if ($intReturn == 0) $intSuccess++;
						if ($intReturn != 0) $intError++;
					}
				}
				if (($intSuccess != 0) && ($intCount != 0)) {
					$myVisClass->processMessage(translate('The assigned, no longer used configuration files were deleted successfully!').$intCount,$strInfoMessage);
				} 
				if ($intError != 0) {
					$myVisClass->processMessage(translate('Errors while deleting the old configuration file - please check!:'),$strErrorMessage);
				}
			}
		} else if ($chkDomainId == 0) {
			$myVisClass->processMessage(translate('Common files cannot be removed from target systems - please check manually'),$strErrorMessage);
		}
	} else if ($preTableName == 'tbl_service') {
    	if ($chkListId != 0) {
      		$strChbName = "chbId_".$chkListId;
      		$_POST[$strChbName] = "on";
    	}
		// Get write access groups
		$strAccess = $myVisClass->getAccGroups('write');
    	// Getting data sets
    	$strSQL 	= "SELECT `id`, `config_name` FROM `".$preTableName."` WHERE `active`='0' AND `access_group` IN ($strAccess) AND `config_id`=".$chkDomainId;
    	$booReturn 	= $myDBClass->getDataArray($strSQL,$arrData,$intDataCount);
    	if ($booReturn && ($intDataCount != 0) && ($chkDomainId != 0)) {
			$arrConfigID  = $myConfigClass->getConfigSets();
			$intError   = 0;
			$intSuccess = 0;
			if (($arrConfigID != 1) && is_array($arrConfigID)) {
				$intCount  = 0;
				foreach ($arrData AS $elem) {
					$strChbName = "chbId_".$elem['id'];
					// was the current record is marked for deactivate?
					if (isset($_POST[$strChbName]) && ($_POST[$strChbName] == "on")) {
						$intServiceCount = $myDBClass->countRows("SELECT * FROM `$preTableName` WHERE `$preKeyField`='".$elem['config_name']."' 
																  AND `config_id`=$chkDomainId AND `active`='1'");
						if ($intServiceCount == 0) {
							$intReturn = 0;
							foreach($arrConfigID AS $intConfigID) {
								$intReturn += $myConfigClass->moveFile("service",$elem['config_name'].".cfg",$intConfigID);
								if ($intReturn == 0) $myDataClass->writeLog(translate('Service file deleted:')." ".$elem['config_name'].".cfg");
								$intCount++;
							}
							if ($intReturn == 0) $intSuccess++;
							if ($intReturn != 0) $intError++;
						}
					}
				}
				if (($intSuccess != 0) && ($intCount != 0)) {
					$myVisClass->processMessage(translate('The assigned, no longer used configuration files were deleted successfully!'),$strInfoMessage);
				} 
				if ($intError != 0) {
					$myVisClass->processMessage(translate('Errors while deleting the old configuration file - please check!:'),$strErrorMessage);
				}
			}
		} else if ($chkDomainId == 0) {
			$myVisClass->processMessage(translate('Common files cannot be removed from target systems - please check manually'),$strErrorMessage);
		}	
	}
	$chkModus  = "display"; 
} else if (($chkModus == "checkform") && ($chkSelModify == "modify")) {
	// Open the dataset to modify
	if ($intReadAccessId == 0) {
		$booReturn = $myDBClass->getSingleDataset("SELECT * FROM `$preTableName` WHERE `id`=".$chkListId,$arrModifyData);
		if ($booReturn == false) {
			$myVisClass->processMessage(translate('Error while selecting data from database:'),$strErrorMessage);
			$myVisClass->processMessage($myDBClass->strErrorMessage,$strErrorMessage);
			$chkModus = "display";
		} else {
			$chkModus = "add";
		}
	} else {
		$myVisClass->processMessage(translate('No permission to open configuration!'),$strErrorMessage);
		$chkModus = "display";			
	}
} else if (($chkModus == "checkform") && ($chkSelModify == "config") && ($intGlobalWriteAccess == 0)) {
	// Write configuration file (hosts and services)
  	$intDSId  = (int)substr(array_search("on",$_POST),6);
  	if (isset($chkListId) && ($chkListId != 0)) $intDSId = $chkListId;
	$intValCount = 0;
	foreach($_POST AS $key => $elem) {
		if ($elem == "on") $intValCount++;
	}
	if ($intValCount > 1) $intDSId = 0;
   	$intReturn = $myConfigClass->createConfigSingle($preTableName,$intDSId);
	if ($intReturn == 1) $myVisClass->processMessage($myConfigClass->strErrorMessage,$strErrorMessage);
	if ($intReturn == 0) $myVisClass->processMessage($myConfigClass->strInfoMessage,$strInfoMessage);
  	$chkModus  = "display";
}
// 
// Some common list view functions
// ===============================
if ($chkModus != "add") {
  	// Get Group id's with READ
  	$strAccess = $myVisClass->getAccGroups('read');
	// Include domain list
	$myVisClass->insertDomainList($mastertp);
  	// Process filter string
}
?>

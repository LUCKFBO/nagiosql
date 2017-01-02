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
//error_reporting(E_ALL);
error_reporting(E_ALL & ~E_STRICT);
//
// Security Protection
// ===================
if (isset($_GET['SETS']) || isset($_POST['SETS'])) {
	$SETS = "";
}
//
// Timezone settings (>=PHP5.1)
// ============================
if(function_exists("date_default_timezone_set") and function_exists("date_default_timezone_get")) {
	@date_default_timezone_set(@date_default_timezone_get());
}
//
// Process post/get parameters
// ===========================
$chkInsName		= isset($_POST['tfUsername'])	? $_POST['tfUsername']  : "";
$chkInsPasswd	= isset($_POST['tfPassword'])	? $_POST['tfPassword']  : "";
$chkLogout		= isset($_GET['logout'])		? htmlspecialchars($_GET['logout'], ENT_QUOTES, 'utf-8')	: "rr";
//
// Define common variables
// =======================
$strErrorMessage	= "";  // All error messages (red)
$strInfoMessage		= "";  // All information messages (green)
$strConsistMessage  = "";  // Consistency message 
$tplHeaderVar   	= "";
$chkDomainId 		= 0;
$chkGroupAdm 		= 0;
$intError 			= 0;
$setDBVersion 		= "unknown";
$setFileVersion		= "3.2.0";
//
// Start PHP session
// =================
session_start();
//
// Check path settings
// ===================
if (!isset($_SESSION['SETS']['path']['base_url']) || !isset($_SESSION['SETS']['path']['base_path'])) {
	if (substr_count($_SERVER['SCRIPT_NAME'],"index.php") != 0) {
		$preBasePath = str_replace("//","/",dirname($_SERVER['SCRIPT_FILENAME'])."/");
		$preBaseURL  = str_replace("//","/",dirname($_SERVER['SCRIPT_NAME'])."/");
		$_SESSION['SETS']['path']['base_url']  = $preBaseURL;
		$_SESSION['SETS']['path']['base_path'] = $preBasePath;
	} else {
		header("Location: ../index.php");
		exit;
	}
} else {
	if (substr_count($_SERVER['SCRIPT_NAME'],"index.php") != 0) {
		$preBasePath_tmp = str_replace("//","/",dirname($_SERVER['SCRIPT_FILENAME'])."/");
		$preBaseURL_tmp  = str_replace("//","/",dirname($_SERVER['SCRIPT_NAME'])."/");
		if ($preBaseURL_tmp != $_SESSION['SETS']['path']['base_url']) {
			$_SESSION['SETS']['path']['base_url']  = $preBaseURL_tmp;
			$_SESSION['SETS']['path']['base_path'] = $preBasePath_tmp;	
		}
	}
	$preBasePath = $_SESSION['SETS']['path']['base_path'];
	$preBaseURL  = $_SESSION['SETS']['path']['base_url'];
}
//
// Start installer
// ===============
$preIniFile = $preBasePath.'config/settings.php';
if (!file_exists($preIniFile) OR ! is_readable($preIniFile)) {
	header("Location: ".$preBaseURL."install/index.php");
}
//
// Read file settings
// ==================
$SETS = parse_ini_file($preBasePath.'config/settings.php',true);
if (!isset($_SESSION['SETS']['db'])) $_SESSION['SETS']['db'] = $SETS['db'];
//
// Include external function/class files - part 1
// ==============================================
include("mysql_class.php");
require("translator.php");
//
// Initialize classes - part 1
// ===========================
$myDBClass = new mysqldb;
if ($myDBClass->error == true) {
  	$strErrorMessage .= translate('Error while connecting to database:')."::".$myDBClass->strErrorMessage;
  	$intError = 1;
	
}
//
// Get additional configuration from the table tbl_settings
// ========================================================
if ($intError == 0) {
	$strSQL    = "SELECT `category`,`name`,`value` FROM `tbl_settings`";	
	$booReturn = $myDBClass->getDataArray($strSQL,$arrDataLines,$intDataCount);	
	if ($booReturn == false) {
  		$strErrorMessage .= translate('Error while selecting data from database:')."::".$myDBClass->strErrorMessage;
		$intError 	 = 1;		
	} else if ($intDataCount != 0) {
		if (isset($_SESSION['SETS']['data']['locale']) && ($_SESSION['SETS']['data']['locale'] != "")) $strStoreLanguage = $_SESSION['SETS']['data']['locale'];		
		// Save additional configuration information
		for ($i=0;$i<$intDataCount;$i++) {
    		// We use the path settings from file
			if ($arrDataLines[$i]['name'] == 'base_url') continue;
			if ($arrDataLines[$i]['name'] == 'base_path') continue;
			$SETS[$arrDataLines[$i]['category']][$arrDataLines[$i]['name']] = $arrDataLines[$i]['value'];
  		}
		if (isset($strStoreLanguage) && ($strStoreLanguage != "")) $SETS['data']['locale'] = $strStoreLanguage;
	}
}
//
// Enable PHP gettext functionality
// ================================
if ($intError == 0) {
	$arrLocale = explode(".",$SETS['data']['locale']);
	$strDomain = $arrLocale[0];
	$strLocale = setlocale(LC_ALL, $SETS['data']['locale'], $SETS['data']['locale'].".utf-8", $SETS['data']['locale'].".utf-8", $SETS['data']['locale'].".utf8", "en_GB", "en_GB.utf-8", "en_GB.utf8");
	if (!isset($strLocale)) {
		$strErrorMessage .= translate("Error in setting the correct locale, please report this error with the associated output of  'locale -a' to bugs@nagiosql.org")."::";
		 $intError 	 = 1;
	}
	putenv("LC_ALL=".$SETS['data']['locale'].".utf-8");
	putenv("LANG=".$SETS['data']['locale'].".utf-8");
	bindtextdomain($strDomain, $preBasePath."config/locale");
	bind_textdomain_codeset($strDomain, $SETS['data']['encoding']);
	textdomain($strDomain);
}
// 
// Update class data
// =================
$myDBClass->arrSettings = $SETS;
//
// Include external function/class files
// =====================================
include("nag_class.php");
include("data_class.php");
include("config_class.php");
include("content_class.php");
require_once($preBasePath.'libraries/pear/HTML/Template/IT.php');
if (isset($preFieldvars) && ($preFieldvars == 1)) {
  	require($preBasePath.'config/fieldvars.php');
}
//
// Check path settings
// ===================
if (!isset($SETS['path']['base_path']) || ($preBasePath != $SETS['path']['base_path'])) {
	$SETS['path']['base_path'] = $preBasePath;	
}
if (!isset($SETS['path']['base_url']) || ($preBaseURL != $SETS['path']['base_url'])) {
	$SETS['path']['base_url'] = $preBaseURL;	
}
//
// Add data to the session
// =======================
$_SESSION['SETS'] 					= $SETS;
$_SESSION['strLoginMessage'] 		= "";
$_SESSION['startsite'] 				= $_SESSION['SETS']['path']['base_url']."admin.php";
if (!isset($_SESSION['logged_in'])) $_SESSION['logged_in'] = 0;
if (isset($chkLogout) && ($chkLogout == "yes")) {
  	$_SESSION = array();
	$_SESSION['SETS'] 				= $SETS;
  	$_SESSION['logged_in'] 			= 0;
	$_SESSION['userid']    			= 0;
	$_SESSION['groupadm']  			= 0;
	$_SESSION['strLoginMessage'] 	= "";
	$_SESSION['startsite'] 			= $_SESSION['SETS']['path']['base_url']."admin.php";
	// Get default language
	$strSQL    = "SELECT `value` FROM `tbl_settings` WHERE `category`='data' AND `name`='locale'";
	$strLocale = $myDBClass->getFieldData($strSQL);
	if ($strLocale != "") {
		$_SESSION['SETS']['data']['locale'] = $strLocale;
		$SETS['data']['locale'] 			= $strLocale;
	}
	$arrLocale = explode(".",$SETS['data']['locale']);
	$strDomain = $arrLocale[0];
	$strLocale = setlocale(LC_ALL, $SETS['data']['locale'], $SETS['data']['locale'].".utf-8", $SETS['data']['locale'].".utf-8", $SETS['data']['locale'].".utf8", "en_GB", "en_GB.utf-8", "en_GB.utf8");
	if (!isset($strLocale)) {
		$strErrorMessage .= translate("Error in setting the correct locale, please report this error with the associated output of  'locale -a' to bugs@nagiosql.org")."::";
		 $intError 	 = 1;
	}
	putenv("LC_ALL=".$SETS['data']['locale'].".utf-8");
	putenv("LANG=".$SETS['data']['locale'].".utf-8");
	bindtextdomain($strDomain, $preBasePath ."config/locale");
	bind_textdomain_codeset($strDomain, $SETS['data']['encoding']);
	textdomain($strDomain);
}
if (isset($_GET['menu']) && (htmlspecialchars($_GET['menu'], ENT_QUOTES, 'utf-8') == "visible"))   $_SESSION['menu'] = "visible";
if (isset($_GET['menu']) && (htmlspecialchars($_GET['menu'], ENT_QUOTES, 'utf-8') == "invisible")) $_SESSION['menu'] = "invisible";
//
// Initialize classes
// ==================
$myVisClass     = new nagvisual;
$myDataClass    = new nagdata;
$myConfigClass  = new nagconfig;
$myContentClass = new nagcontent;
//
// Propagating the classes themselves
// ==================================
$myVisClass->myDBClass    		=& $myDBClass;
$myVisClass->myDataClass  		=& $myDataClass;
$myVisClass->myConfigClass  	=& $myConfigClass;
$myDataClass->myDBClass   		=& $myDBClass;
$myDataClass->myVisClass  		=& $myVisClass;
$myDataClass->myConfigClass 	=& $myConfigClass;
$myConfigClass->myDBClass 		=& $myDBClass;
$myConfigClass->myVisClass  	=& $myVisClass;
$myConfigClass->myDataClass 	=& $myDataClass;
$myContentClass->myVisClass 	=& $myVisClass;
$myContentClass->myDBClass		=& $myDBClass;
$myContentClass->myConfigClass 	=& $myConfigClass;
if (isset($arrDescription)) $myContentClass->arrDescription = $arrDescription;
$strErrorMessage = str_replace("::","<br>",$strErrorMessage);
//
// Version management
// ==================
if ($intError == 0) {
	$setDBVersion = $SETS['db']['version'];
}
//
// Version check
// =============
if (version_compare($setFileVersion,$setDBVersion,'>') AND (file_exists($preBasePath."install") && is_readable($preBasePath."install"))) {
	header("Location: ". $_SESSION['SETS']['path']['base_url']."install/index.php");
}
// 
// Browser Check
// =============
$preBrowser = $myVisClass->browserCheck();
//
// Login process
// ==============
if (isset($_SERVER['REMOTE_USER']) && ($_SERVER['REMOTE_USER'] != "") && ($_SESSION['logged_in'] == 0) && 
    ($chkLogout != "yes") && ($chkInsName == "")) {
	$strSQL    = "SELECT * FROM `tbl_user` WHERE `username`='".$_SERVER['REMOTE_USER']."' AND `wsauth`='1' AND `active`='1'";
  	$booReturn = $myDBClass->getDataArray($strSQL,$arrDataUser,$intDataCount);
	if ($booReturn && ($intDataCount == 1)) {
		// Set session variables
		$_SESSION['username']  = $arrDataUser[0]['username'];
		$_SESSION['userid']    = $arrDataUser[0]['id'];
		$_SESSION['groupadm']  = $arrDataUser[0]['admin_enable'];
		$_SESSION['startsite'] = $_SESSION['SETS']['path']['base_url']."admin.php";
		$_SESSION['timestamp'] = time();
		$_SESSION['logged_in'] = 1;
		$_SESSION['domain']    = $arrDataUser[0]['domain'];
		// Update language settings
		$strSQL    	   = "SELECT `locale` FROM `tbl_language` WHERE `id`='".$arrDataUser[0]['language']."' AND `active`='1'";
		$strUserLocale = $myDBClass->getFieldData($strSQL);
		if ($strUserLocale != "") {
			$_SESSION['SETS']['data']['locale'] = $strUserLocale;
			$SETS['data']['locale'] 			= $strUserLocale;
		}
		// Update last login time
		$strSQLUpdate = "UPDATE `tbl_user` SET `last_login`=NOW() WHERE `username`='".$chkInsName."'";
		$booReturn    = $myDBClass->insertData($strSQLUpdate);
		$myDataClass->writeLog(translate('Webserver login successfull'));
		$_SESSION['strLoginMessage'] = ""; 
		// Redirect to start page
		header("Location: ".$_SESSION['SETS']['path']['protocol']."://".$_SERVER['HTTP_HOST'].$_SESSION['startsite']);
  	}
}
if (($_SESSION['logged_in'] == 0) && isset($chkInsName) && ($chkInsName != "") && ($intError == 0)) {

	$chkInsName   = $chkInsName;
	$chkInsPasswd = $chkInsPasswd;
	$strSQL    = "SELECT * FROM `tbl_user` WHERE `username`='".$chkInsName."' 
				  AND `password`=MD5('".$chkInsPasswd."') AND `active`='1'";
  	$booReturn = $myDBClass->getDataArray($strSQL,$arrDataUser,$intDataCount);	
  	if ($booReturn == false) {
    	$myVisClass->processMessage(translate('Error while selecting data from database:'),$strErrorMessage);
		$myVisClass->processMessage($myDBClass->strErrorMessage,$strErrorMessage);
    	$_SESSION['strLoginMessage'] = $strErrorMessage;
  	} else if ($intDataCount == 1) {
		// Set session variables
		$_SESSION['username']  = $arrDataUser[0]['username'];
		$_SESSION['userid']    = $arrDataUser[0]['id'];
		$_SESSION['groupadm']  = $arrDataUser[0]['admin_enable'];
		$_SESSION['startsite'] = $_SESSION['SETS']['path']['base_url'] ."admin.php";
		$_SESSION['timestamp'] = time();
		$_SESSION['logged_in'] = 1;
		$_SESSION['domain']    = $arrDataUser[0]['domain'];
		// Update language settings
		$strSQL    	   = "SELECT `locale` FROM `tbl_language` WHERE `id`='".$arrDataUser[0]['language']."' AND `active`='1'";
		$strUserLocale = $myDBClass->getFieldData($strSQL);
		if ($strUserLocale != "") {
			$_SESSION['SETS']['data']['locale'] = $strUserLocale;
			$SETS['data']['locale'] 			= $strUserLocale;
		}
    	// Update last login time		
    	$strSQLUpdate = "UPDATE `tbl_user` SET `last_login`=NOW() WHERE `username`='".$chkInsName."'";
    	$booReturn    = $myDBClass->insertData($strSQLUpdate);
    	$myDataClass->writeLog(translate('Login successfull'));
    	$_SESSION['strLoginMessage'] = "";
		// Redirect to start page
		header("Location: ".$_SESSION['SETS']['path']['protocol']."://".$_SERVER['HTTP_HOST'].$_SESSION['startsite']);
  	} else {
    	$_SESSION['strLoginMessage'] = translate('Login failed!');
    	$myDataClass->writeLog(translate('Login failed!')." - Username: ".$chkInsName);
    	$preNoMain = 0;
  	}
} 
if (($_SESSION['logged_in'] == 0) && (!isset($intPageID) || ($intPageID != 0)) && (!isset($chkInsName) || ($chkInsName == ""))) {
	header("Location: ".$_SESSION['SETS']['path']['protocol']."://".$_SERVER['HTTP_HOST'].$_SESSION['SETS']['path']['base_url']."index.php");
}
if (!isset($_SESSION['userid']) && ($_SESSION['logged_in'] == 1)) {
	$_SESSION['logged_in'] = 0;
	header("Location: ".$_SESSION['SETS']['path']['protocol']."://".$_SERVER['HTTP_HOST'].$_SESSION['SETS']['path']['base_url']."index.php");
}
//
// Review and update login
// =======================
if (($_SESSION['logged_in'] == 1) && ($intError == 0)) {
  	$strSQL  = "SELECT * FROM `tbl_user` WHERE `username`='".($_SESSION['username'])."'";
  	$booReturn = $myDBClass->getDataArray($strSQL,$arrDataUser,$intDataCount);
  	if ($booReturn == false) {
    	$myVisClass->processMessage(translate('Error while selecting data from database:'),$strErrorMessage);
		$myVisClass->processMessage($myDBClass->strErrorMessage,$strErrorMessage);
  	} else if ($intDataCount == 1) {
    	// Time expired?
    	if (time() - $_SESSION['timestamp'] > $_SESSION['SETS']['security']['logofftime']) {
      		// Force new login
      		$myDataClass->writeLog(translate('Session timeout reached - Seconds:')." ".(time() - $_SESSION['timestamp']." - User: ".$_SESSION['username']));
      		$_SESSION['logged_in'] = 0;
			
      		header("Location: ".$_SESSION['SETS']['path']['protocol']."://".$_SERVER['HTTP_HOST'].$_SESSION['SETS']['path']['base_url']."index.php");
    	} else {
      		// Check rights
      		if (isset($preAccess) && ($preAccess == 1) && (isset($prePageId) && ($prePageId != 0))) {
        		$strKey    = $myDBClass->getFieldData("SELECT `mnuGrpId` FROM `tbl_menu` WHERE `mnuId`=$prePageId");
        		$intResult = $myVisClass->checkAccGroup($strKey,'read');
        		// If no rights - redirect to index page
        		if ($intResult != 0) {
          			$myDataClass->writeLog(translate('Restricted site accessed:')." ".filter_var($_SERVER['PHP_SELF'], FILTER_SANITIZE_STRING));
					header("Location: ".$_SESSION['SETS']['path']['protocol']."://".$_SERVER['HTTP_HOST'].$_SESSION['SETS']['path']['base_url']."index.php");
        		}
      		}
      		// Update login time
      		$_SESSION['timestamp'] = time();
	  		if (isset($preContent) && ($preContent == "index.tpl.htm")) {
		  		header("Location: ".$_SESSION['SETS']['path']['protocol']."://".$_SERVER['HTTP_HOST'].$_SESSION['startsite']);
	  		}
    	}
  	} else {
    	// Force new login
    	$myDataClass->writeLog(translate('User not found in database'));
		$_SESSION['logged_in'] = 0;
    	header("Location: ".$_SESSION['SETS']['path']['protocol']."://".$_SERVER['HTTP_HOST'].$_SESSION['SETS']['path']['base_url']."index.php");
  	}
}
//
// Check access to current site
// ============================
if (isset($prePageId) && ($prePageId != 1)) {
    if (!isset($_SESSION['userid'])) {
    	header("Location: ".$_SESSION['SETS']['path']['protocol']."://".$_SERVER['HTTP_HOST'].$_SESSION['SETS']['path']['base_url']."index.php");
	}
	$strSQL     = "SELECT `mnuGrpId` FROM `tbl_menu` WHERE `mnuId`=$prePageId";
	$prePageKey = $myDBClass->getFieldData($strSQL)+0;
	if ($myVisClass->checkAccGroup($prePageKey,'read') != 0) {
		header("Location: ".$_SESSION['SETS']['path']['protocol']."://".$_SERVER['HTTP_HOST'].$_SESSION['startsite']);
	}
}
//
// Insert main template
// ====================
if (isset($preContent) && ($preContent != "") && (!isset($preNoMain) || ($preNoMain != 1))) {
	$arrTplOptions = array('use_preg' => false);
	$maintp = new HTML_Template_IT($preBasePath ."templates/");
	$maintp->loadTemplatefile("main.tpl.htm", true, true);
	$maintp->setOptions($arrTplOptions);
	$maintp->setVariable("META_DESCRIPTION","NagiosQL System Monitoring Administration Tool");
	$maintp->setVariable("AUTHOR","NagiosQL Team");
	$maintp->setVariable("LANGUAGE","de");
	$maintp->setVariable("PUBLISHER","www.nagiosql.org");
	if ($_SESSION['logged_in'] == 1) {
		$maintp->setVariable("ADMIN","<a href=\"". $_SESSION['SETS']['path']['base_url'] ."admin.php\" class=\"top-link\">".translate('Administration')."</a>");
		//$maintp->setVariable("PLUGINS","<a href=\"".$_SESSION['SETS']['path']['base_url']."/plugin.php\" class=\"top-link\">".translate('Plugins')."</a>");
	}
	$maintp->setVariable("BASE_PATH",$_SESSION['SETS']['path']['base_url']);
	$maintp->setVariable("ROBOTS","noindex,nofollow");
	$maintp->setVariable("PAGETITLE","NagiosQL - Version ".$setDBVersion);
	$maintp->setVariable("IMAGEDIR",$_SESSION['SETS']['path']['base_url'] ."images/");
	if (isset($prePageId) && ($intError == 0)) $maintp->setVariable("POSITION",$myVisClass->getPosition($prePageId,translate('Admin')));
	$maintp->parse("header");
	$tplHeaderVar = $maintp->get("header");
	//
	// Read domain list
	// ================
  	if (($_SESSION['logged_in'] == 1) && ($intError == 0)) {
    	$intDomain = isset($_POST['selDomain']) ? $_POST['selDomain'] : -1;
    	if ($intDomain != -1) {
			$_SESSION['domain'] 			= $intDomain;
			$myVisClass->intDomainId 		= $intDomain;
			$myDataClass->intDomainId 		= $intDomain;
			$myConfigClass->intDomainId 	= $intDomain;
			$myContentClass->intDomainId 	= $intDomain;
		}
    	$strSQL    = "SELECT * FROM `tbl_datadomain` WHERE `active` <> '0' ORDER BY `domain`";
    	$booReturn = $myDBClass->getDataArray($strSQL,$arrDataDomain,$intDataCount);
    	if ($booReturn == false) {
			$myVisClass->processMessage(translate('Error while selecting data from database:'),$strErrorMessage);
			$myVisClass->processMessage($myDBClass->strErrorMessage,$strErrorMessage);
    	} else {
      		$intDomain = 0;
			if ($intDataCount > 0) {
				foreach($arrDataDomain AS $elem) {
					$intIsDomain = 0;
					// Check access rights
					if ($myVisClass->checkAccGroup($elem['access_group'],'read') == 0) {
						$maintp->setVariable("DOMAIN_VALUE",$elem['id']);
						$maintp->setVariable("DOMAIN_TEXT",$elem['domain']);
						if (isset($_SESSION['domain']) && ($_SESSION['domain'] == $elem['id'])) {
							$maintp->setVariable("DOMAIN_SELECTED","selected");
							$intDomain 	 = $elem['id'];
							$intIsDomain = 1;
						}
						if ($intDomain == -1) {
							$intDomain   = $elem['id'];
							$intIsDomain = 1;
						}
						$maintp->parse("domainsel");
					}
					if ($intIsDomain == 0) {
						// Select available an domain
						$strDomAcc = $myVisClass->getAccGroups('read');
						$strSQL    = "SELECT id FROM `tbl_datadomain` WHERE `active` <> '0' AND `access_group` IN (".$strDomAcc.") ORDER BY domain LIMIT 1";
						$booReturn = $myDBClass->getDataArray($strSQL,$arrDataDomain,$intDataCount);
						if ($booReturn == false) {
							$myVisClass->processMessage(translate('Error while selecting data from database:'),$strErrorMessage);
							$myVisClass->processMessage($myDBClass->strErrorMessage,$strErrorMessage);
						} else {
							if ($intDataCount != 0) $intDomain = $arrDataDomain[0]['id'];
						}
					}
				}
				$maintp->setVariable("DOMAIN_INFO",translate("Domain").":");
				$maintp->parse("dselect");
				$tplHeaderVar .= $maintp->get("dselect");
			}
		}
	}
	//
	// Show login information
	// ======================
  	if ($_SESSION['logged_in'] == 1) {
    	$maintp->setVariable("LOGIN_INFO",translate('Logged in:')." ".$_SESSION['username']);
    	$maintp->setVariable("LOGOUT_INFO","<a href=\"".$_SESSION['SETS']['path']['base_url']."index.php?logout=yes\">".translate('Logout')."</a>");
  	} else {
    	$maintp->setVariable("LOGOUT_INFO","&nbsp;");
  	}
	//
	// Build content menu
	// ==================
	if (isset($prePageId) && ($prePageId != 0)) $maintp->setVariable("MAINMENU",$myVisClass->getMenu($prePageId));
  	$maintp->parse("header2");
  	$tplHeaderVar .= $maintp->get("header2");
  	if (!isset($preShowHeader) || $preShowHeader == 1) {
    	echo $tplHeaderVar;
  	}
}
//
// Insert content and master template
// ======================================
if (isset($preContent) && ($preContent != "")) {
	$arrTplOptions = array('use_preg' => false);
	if (!file_exists($preBasePath ."templates/".$preContent) || !is_readable($preBasePath ."templates/".$preContent)) {
		echo "<span style=\"color:#F00\">".translate('Warning - template file not found or not readable, please check your file permissions! - File: ');
		echo str_replace("//","/",$preBasePath ."templates/".$preContent)."</span><br>";
		exit;
	}
	$conttp = new HTML_Template_IT($preBasePath ."templates/");
	$conttp->loadTemplatefile($preContent, true, true);
	$conttp->setOptions($arrTplOptions);
	$strRootPath = $_SESSION['SETS']['path']['base_url'];
	if (substr($strRootPath,-1) != "/") {
		$conttp->setVariable("BASE_PATH",$strRootPath);
		$conttp->setVariable("IMAGE_PATH",$strRootPath."images/");
	} else {
		$conttp->setVariable("BASE_PATH",$strRootPath);
		$conttp->setVariable("IMAGE_PATH",$strRootPath."images/");
	}
	$mastertp = new HTML_Template_IT($preBasePath ."templates/");
	$mastertp->loadTemplatefile("admin/admin_master.tpl.htm", true, true);
	$mastertp->setOptions($arrTplOptions);
} elseif (isset($pluginTemplate) && ($pluginTemplate != "")) {
//
// Insert Plugin Template
// ======================
	$arrTplOptions = array('use_preg' => false);
	$conttp = new HTML_Template_IT($preBasePath ."plugins/".$pluginType."/".$pluginName."/templates/default/");
	$conttp->loadTemplatefile($pluginTemplate, true, true);
	$conttp->setOptions($arrTplOptions);
	$strRootPath = $_SESSION['SETS']['path']['base_url'];
	if (substr($strRootPath,-1) != "/") {
		$conttp->setVariable("BASE_PATH",$strRootPath."/plugins/".$pluginType."/".$pluginName."/");
		$conttp->setVariable("IMAGE_PATH",$strRootPath."/plugins/".$pluginType."/".$pluginName."/images/");
	} else {
		$conttp->setVariable("BASE_PATH",$strRootPath."/plugins/".$pluginType."/".$pluginName."/");
		$conttp->setVariable("IMAGE_PATH",$strRootPath."/plugins/".$pluginType."/".$pluginName."/images/");
	}
	$mastertp = new HTML_Template_IT($preBasePath ."templates/");
	$mastertp->loadTemplatefile("admin/admin_master.tpl.htm", true, true);
	$mastertp->setOptions($arrTplOptions);
}
//
// Process standard get/post parameters
// ====================================
$arrSortDir = array("ASC","DESC");
$arrSortBy  = array("1","2");
$chkModus     		= isset($_GET['modus'])     		? htmlspecialchars($_GET['modus'], ENT_QUOTES, 'utf-8')				: "display";
$chkModus     		= isset($_POST['modus'])    		? htmlspecialchars($_POST['modus'], ENT_QUOTES, 'utf-8')			: "display";
$chkHidModify   	= isset($_POST['hidModify'])  		? htmlspecialchars($_POST['hidModify'], ENT_QUOTES, 'utf-8')		: "";
$chkSelModify 		= isset($_POST['selModify'])  		? htmlspecialchars($_POST['selModify'], ENT_QUOTES, 'utf-8')		: "";
$hidSortDir 		= (isset($_POST['hidSortDir']) && in_array($_POST['hidSortDir'],$arrSortDir)) 	? $_POST['hidSortDir']	: "ASC";
$hidSortBy   		= (isset($_POST['hidSortBy'])  && in_array($_POST['hidSortBy'],$arrSortBy)) 	? $_POST['hidSortBy']	: 1;
$chkLimit     		= isset($_POST['hidLimit'])   		? $_POST['hidLimit']+0			: 0;
$chkSelTargetDomain	= isset($_POST['selTargetDomain'])	? $_POST['selTargetDomain']+0	: 0;
$chkListId      	= isset($_POST['hidListId'])  		? $_POST['hidListId']+0			: 0;
$chkDataId    		= isset($_POST['hidId'])    		? $_POST['hidId']+0				: 0;
$chkActive    		= isset($_POST['chbActive'])  		? $_POST['chbActive']+0			: 0;
$chkRegister   		= isset($_POST['chbRegister'])  	? $_POST['chbRegister']+0		: 0;
$hidActive   		= isset($_POST['hidActive'])  		? $_POST['hidActive']+0			: 0;
$hidSort   			= isset($_POST['hidSort'])  		? $_POST['hidSort']+0			: 0;
$chkStatus      	= isset($_POST['hidStatus'])      	? $_POST['hidStatus']+0   		: 0;
if (isset($_GET['orderby'])  && ($_GET['orderby'] != ""))	$hidSortBy 	= $_GET['orderby'];
if (isset($_GET['orderdir']) && ($_GET['orderdir'] != ""))	$hidSortDir = $_GET['orderdir'];
//
// Setting some variables
// ======================
if ($chkModus == "add")       				$chkSelModify 				  = "";
if ($chkHidModify != "")      				$chkSelModify 				  = $chkHidModify;
if (isset($_GET['limit']))    				$chkLimit 					  = htmlspecialchars($_GET['limit'], ENT_QUOTES, 'utf-8');
if (isset($_SESSION['domain'])) 			$chkDomainId 				  = $_SESSION['domain'];
if (isset($_SESSION['groupadm'])) 			$chkGroupAdm 			  	  = $_SESSION['groupadm'];
if (isset($_SESSION['strLoginMessage'])) 	$_SESSION['strLoginMessage'] .= $strErrorMessage;
$myConfigClass->getDomainData("version",$intVersion);
$myConfigClass->getDomainData("enable_common",$setEnableCommon);
if (isset($preTableName)) {
	if ($setEnableCommon != 0) {
		$strDomainWhere  = " (`$preTableName`.`config_id`=$chkDomainId OR `$preTableName`.`config_id`=0) ";
		$strDomainWhere2 = " (`config_id`=$chkDomainId OR `config_id`=0) ";
	} else {
		$strDomainWhere  = " (`$preTableName`.`config_id`=$chkDomainId) ";
		$strDomainWhere2 = " (`config_id`=$chkDomainId) ";
	}
}
// Row sort variables
if ($hidSortDir == "ASC") { $setSortDir = "DESC"; } else { $setSortDir = "ASC"; }
if (isset($preContent) && ($preContent != "")) {
	if ($hidSortBy == 2) {
		$mastertp->setVariable("SORT_IMAGE_1","");
	} else {
		$hidSortBy = 1;
		$mastertp->setVariable("SORT_IMAGE_2","");
	}
	$setSortPicture = $_SESSION['SETS']['path']['base_url']."images/sort_".strtolower($hidSortDir).".png";
	$mastertp->setVariable("SORT_DIR_".$hidSortBy,$setSortDir);
	$mastertp->setVariable("SORT_IMAGE_".$hidSortBy,"<img src=\"$setSortPicture\" alt=\"$hidSortDir\" title=\"$hidSortDir\" width=\"15\" height=\"14\" border=\"0\">");
	$mastertp->setVariable("SORT_DIR",$hidSortDir);
	$mastertp->setVariable("SORT_BY",$hidSortBy);
}
//
// Set class variables
// ===================
if (isset($preContent) && ($preContent != "")) {
	$myVisClass->myContentTpl 	= $conttp;
	$myVisClass->dataId 		= $chkListId;
}
?>
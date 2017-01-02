<?php
// Start session
// ===============

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


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
$preContent	= "templates/index.tpl.htm";
$preEncode	= 'utf-8';
$preLocale 	= "../config/locale";
$filConfig  = "../config/settings.php";
$preDBType  = "mysqli";
$strLangOpt = "";
$intError 	= 0;
$intUpdate  = 0;
//
// Include preprocessing file
// ==========================
require("functions/prepend_install.php");
require("../functions/translator.php");
//


//
// POST parameters
// ===============
$arrLocale	= array("zh_CN","de_DE","da_DK","en_GB","fr_FR","it_IT","ja_JP","nl_NL","pl_PL","pt_BR","ru_RU","es_ES");
$chkLocale 	= (isset($_POST['selLanguage']) && in_array($_POST['selLanguage'],$arrLocale)) ? $_POST['selLanguage'] : "no";
//
// Language settings
// =================
if (extension_loaded('gettext')) {
	if ($chkLocale == "no") {
		if (substr($_SERVER['HTTP_ACCEPT_LANGUAGE'],0,2) == "de") {
			$chkLocale = 'de_DE';
		} else {
			$chkLocale = 'en_GB';
		}
	}
	putenv("LC_ALL=".$chkLocale.".".$preEncode);
	putenv("LANG=".$chkLocale.".".$preEncode);
	// GETTEXT domain
	setlocale(LC_ALL, $chkLocale.".".$preEncode);
	bindtextdomain($chkLocale, $preLocale);
	bind_textdomain_codeset($chkLocale, $preEncode);
	textdomain($chkLocale);
	$arrTemplate['NAGIOS_FAQ'] = translate("Online Documentation");
	// Language selection field
	$arrTemplate['LANGUAGE'] = translate("Language");
	foreach(getLanguageData() AS $key => $elem) {
		$strLangOpt .= "<option value='".$key."' {sel}>".getLanguageNameFromCode($key,false)."</option>\n";
		if ($key != $chkLocale) { $strLangOpt = str_replace(" {sel}","",$strLangOpt); } else { $strLangOpt = str_replace(" {sel}"," selected",$strLangOpt); }
	}
	$arrTemplate['LANG_OPTION'] = $strLangOpt;
} else {
	$intError 			 = 1;
	$strErrorMessage 	.= "Installation cannot continue, please make sure you have the php-gettext extension loaded!";
}
//
// Checking current installation
// =============================
// Does the settings file exist?
$_SESSION['install']['dbtype'] = $preDBType;
if (file_exists($filConfig) && is_readable($filConfig)) {
	$preSettings = parse_ini_file($filConfig,true);
	// Are there any connection data?
	if (isset($preSettings['db']) && isset($preSettings['db']['server']) && isset($preSettings['db']['port']) &&
	    isset($preSettings['db']['database']) && isset($preSettings['db']['username']) && isset($preSettings['db']['password'])) {
		// Copy settings to session
		$_SESSION['SETS'] = $preSettings;
		// Existing postgres database?
		if (isset($preSettings['db']['dbtype']) && ($preSettings['db']['dbtype'] == "postgres")) {
			$preDBType = "pgsql";
			$_SESSION['install']['dbtype'] = $preDBType;
		}
		if ($preDBType == "mysqli") {
			if (extension_loaded('mysqli')) {
				// Include mysql class
				include("../functions/mysql_class.php");
				// Initialize mysql class
				$myDBClass = new mysqldb;
				if ($myDBClass->error == true) {
					$strErrorMessage .= translate("Database connection failed. Upgrade not available!")."<br>";
					$strErrorMessage .= translate('Error while connecting to database:')."<br>".$myDBClass->strErrorMessage."<br>";
				} else {
					$strSQL    = "SELECT `category`,`name`,`value` FROM `tbl_settings`";
					$booReturn = $myDBClass->getDataArray($strSQL,$arrDataLines,$intDataCount);
					if ($booReturn == false) {
						$strErrorMessage .= translate("Settings table not available or wrong. Upgrade not available!")."<br>";
						$strErrorMessage .= translate('Error while selecting data from database:')."<br>".$myDBClass->strDBError."<br>";
					} else if ($intDataCount != 0) {
						foreach ($arrDataLines AS $elem) {
							$preSettings[$elem['category']][$elem['name']] = $elem['value'];
						}
						$intUpdate = 1;
					}
				}
				$_SESSION['install']['dbtype'] = $preDBType;
			} else {
				$strErrorMessage .= translate("Installation cannot continue, please make sure you have the mysql extension loaded!");
				$intError 		  = 1;
			}
		} else if ($preDBType == "pgsql") {
			if (extension_loaded('pgsql')) {
				$strErrorMessage .= translate("Installation cannot continue, postgres is not yet available in beta!");
				$intError 		  = 1;
			} else {
				$strErrorMessage .= translate("Installation cannot continue, please make sure you have the pgsql extension loaded!");
				$intError 		  = 1;
			}	
		} else {
			$strErrorMessage .= translate("Database type in settings file is wrong (config/settings.php). Upgrade not available!");
		}
	} else {
		$strErrorMessage .= translate("Database values in settings file are missing (config/settings.php). Upgrade not available!");
	}
} else {
	$strErrorMessage .= translate("Settings file not found or not readable (config/settings.php). Upgrade not available!");
}
//
// Initial settings (new installation)
// ===================================
$filInit = "functions/initial_settings.php";
if (file_exists($filInit) && is_readable($filInit)) {
	$preInit = parse_ini_file($filInit,true);
	$_SESSION['init_settings'] = $preInit;
} else {
	$strErrorMessage .= translate("Default values file is not available or not readable (install/functions/initial_settings.php). Installation possible, but without predefined data!");
}
//
// Build content
// =============
$arrTemplate['PAGETITLE'] 		= "[NagiosQL] Installation Wizard";
$arrTemplate['MAIN_TITLE']  	= translate("Welcome to the NagiosQL Installation Wizard");
$arrTemplate['TEXT_PART_1'] 	= translate("This wizard will help you to install and configure NagiosQL.");
$arrTemplate['TEXT_PART_2'] 	= translate("For questions please visit");
$arrTemplate['TEXT_PART_3']		= translate("First let's check your local environment and find out if everything NagiosQL needs is available.");
$arrTemplate['TEXT_PART_4']		= translate("The basic requirements are:");
$arrTemplate['TEXT_PART_5']		= translate("PHP 5.2.0 or greater including:");
$arrTemplate['TEXT_PHP_REQ_1']	= translate("PHP Module:")." Session";
$arrTemplate['TEXT_PHP_REQ_2']	= translate("PHP Module:")." gettext";
$arrTemplate['TEXT_PHP_REQ_3']	= translate("PHP Module:")." filter";
//$arrTemplate['TEXT_PHP_REQ_4']	= translate("PHP Module:")." XML";
//$arrTemplate['TEXT_PHP_REQ_5']	= translate("PHP Module:")." SimpleXML";
$arrTemplate['TEXT_PHP_REQ_6']	= translate("PHP Module:")." MySQLi";
//$arrTemplate['TEXT_PHP_REQ_7']	= translate("PHP Module:")." PgSQL ".translate("(optional)");
$arrTemplate['TEXT_PHP_REQ_8']	= translate("PHP Module:")." FTP ".translate("(optional)");
$arrTemplate['TEXT_PHP_REQ_9']	= translate("PHP Module:")." curl ".translate("(optional)");
$arrTemplate['TEXT_PHP_REQ_10']	= translate("PECL Extension:")." SSH ".translate("(optional)");
$arrTemplate['TEXT_PART_6']		= translate("php.ini options").":";
$arrTemplate['TEXT_INI_REQ_1']	= translate("file_uploads on (for upload features)");
$arrTemplate['TEXT_INI_REQ_2']	= translate("session.auto_start needs to be off");
$arrTemplate['TEXT_PART_7']		= translate("A MySQL database server");
$arrTemplate['TEXT_PART_8']		= translate("Nagios 2.x/3.x/4.x");
$arrTemplate['LOCALE']			= $chkLocale;
$arrTemplate['ONLINE_DOC'] 		= translate("Online Documentation");
//
// New installation or upgrade
// ===========================
$arrTemplate['NEW_INSTALLATION'] = translate("START INSTALLATION");
$arrTemplate['UPDATE'] 			 = translate("START UPDATE");
$arrTemplate['DISABLE_NEW'] 	 = "";
$arrTemplate['UPDATE_ERROR']   	 = "<font style=\"color:red;\">".$strErrorMessage."</font>";
if ($intUpdate == 1) {
	$arrTemplate['DISABLE_UPDATE'] 	= "";
} else {
	$arrTemplate['DISABLE_UPDATE'] 	= "disabled=\disabled\"";
}
if ($intError == 1) {
	$arrTemplate['DISABLE_NEW'] 	= "disabled=\disabled\"";
	$arrTemplate['DISABLE_UPDATE'] 	= "disabled=\disabled\"";
}
//
// Write content
// =============
$strContent = $myInstClass->parseTemplate($arrTemplate,$preContent);
echo $strContent;
?>
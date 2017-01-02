<?php
session_start();
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
// Actual database files
// =====================
$preSqlNewInstall 	= "sql/nagiosQL_v32_db_mysql.sql";
$preSqlUpdateLast	= "sql/update_31x_320.sql";
$preNagiosQL_ver	= "3.2.0";
//
// Define common variables
// =======================
$preContent	= "templates/install.tpl.htm";
$preEncode	= 'utf-8';
$preLocale 	= "../config/locale";
$intError 	= 0;
$chkModus 	= "none";
//
// Include preprocessing file
// ==========================
require("functions/prepend_install.php");
require("../functions/translator.php");
//
// Process initial value
// =====================
$strInitDBserver 	= isset($_SESSION['SETS']['db']['server'])		? $_SESSION['SETS']['db']['server']		: $_SESSION['init_settings']['db']['server'];
$strInitDBname		= isset($_SESSION['SETS']['db']['database'])	? $_SESSION['SETS']['db']['database']	: $_SESSION['init_settings']['db']['database'];
$strInitDBuser		= isset($_SESSION['SETS']['db']['username'])	? $_SESSION['SETS']['db']['username']	: $_SESSION['init_settings']['db']['username'];
$strInitDBpass		= isset($_SESSION['SETS']['db']['password'])	? $_SESSION['SETS']['db']['password']	: $_SESSION['init_settings']['db']['password'];
$strInitDBport		= isset($_SESSION['SETS']['db']['port'])		? $_SESSION['SETS']['db']['port']		: $_SESSION['init_settings']['db']['port'];
//
// Init session parameters
// =======================
if (!isset($_SESSION['install']['jscript'])) 	$_SESSION['install']['jscript'] 	= "no";
if (!isset($_SESSION['install']['locale'])) 	$_SESSION['install']['locale']  	= "en_GB";
if (!isset($_SESSION['install']['dbserver'])) 	$_SESSION['install']['dbserver']  	= $strInitDBserver;
if (!isset($_SESSION['install']['localsrv'])) 	$_SESSION['install']['localsrv'] 	= "";
if (!isset($_SESSION['install']['dbname'])) 	$_SESSION['install']['dbname'] 	 	= $strInitDBname;
if (!isset($_SESSION['install']['dbuser'])) 	$_SESSION['install']['dbuser'] 	 	= $strInitDBuser;
if (!isset($_SESSION['install']['dbpass'])) 	$_SESSION['install']['dbpass'] 	 	= $strInitDBpass;
if (!isset($_SESSION['install']['admuser'])) 	$_SESSION['install']['admuser']  	= "root";
if (!isset($_SESSION['install']['admpass'])) 	$_SESSION['install']['admpass']  	= "";
if (!isset($_SESSION['install']['qluser'])) 	$_SESSION['install']['qluser']  	= "admin";
if (!isset($_SESSION['install']['qlpass'])) 	$_SESSION['install']['qlpass']  	= "";
if (!isset($_SESSION['install']['dbport'])) 	$_SESSION['install']['dbport']  	= $strInitDBport;
if (!isset($_SESSION['install']['dbdrop'])) 	$_SESSION['install']['dbdrop']  	= 0;
if (!isset($_SESSION['install']['sample']))		$_SESSION['install']['sample']  	= 0;
if (!isset($_SESSION['install']['version']))	$_SESSION['install']['version']  	= $preNagiosQL_ver;
if (!isset($_SESSION['install']['createpath']))	$_SESSION['install']['createpath']  = 0;
if (!isset($_SESSION['install']['qlpath'])) 	$_SESSION['install']['qlpath']  	= "/etc/nagiosql";
if (!isset($_SESSION['install']['nagpath'])) 	$_SESSION['install']['nagpath']  	= "/etc/nagios";
//
// POST parameters
// ===============
$arrStep 		= array(1,2,3);
$chkStep 		= isset($_POST['hidStep'])		? $_POST['hidStep']			: "1";
if (isset($_GET['step']) && in_array($_GET['step'],$arrStep)) $chkStep = $_GET['step']+0;
if (!in_array($chkStep,$arrStep)) $arrStep = 1;
// Session values
$_SESSION['install']['locale']		= isset($_POST['hidLocale']) 	? $_POST['hidLocale'] 		: $_SESSION['install']['locale'];
$_SESSION['install']['jscript']		= isset($_POST['hidJScript']) 	? $_POST['hidJScript'] 		: $_SESSION['install']['jscript'];
$_SESSION['install']['dbserver'] 	= isset($_POST['tfDBserver']) 	? $_POST['tfDBserver'] 		: $_SESSION['install']['dbserver'];
$_SESSION['install']['localsrv'] 	= isset($_POST['tfLocalSrv']) 	? $_POST['tfLocalSrv'] 		: $_SESSION['install']['localsrv'];
$_SESSION['install']['dbname'] 		= isset($_POST['tfDBname'])		? $_POST['tfDBname'] 		: $_SESSION['install']['dbname'];
$_SESSION['install']['dbuser'] 		= isset($_POST['tfDBuser'])		? $_POST['tfDBuser'] 		: $_SESSION['install']['dbuser'];
$_SESSION['install']['dbpass'] 		= isset($_POST['tfDBpass'])		? $_POST['tfDBpass'] 		: $_SESSION['install']['dbpass'];
$_SESSION['install']['admuser'] 	= isset($_POST['tfDBprivUser'])	? $_POST['tfDBprivUser']	: $_SESSION['install']['admuser'];
$_SESSION['install']['admpass'] 	= isset($_POST['tfDBprivPass'])	? $_POST['tfDBprivPass']	: $_SESSION['install']['admpass'];
$_SESSION['install']['qluser'] 		= isset($_POST['tfQLuser'])		? $_POST['tfQLuser']		: $_SESSION['install']['qluser'];
$_SESSION['install']['qlpass'] 		= isset($_POST['tfQLpass'])		? $_POST['tfQLpass']		: $_SESSION['install']['qlpass'];
$_SESSION['install']['dbport'] 		= isset($_POST['tfDBport']) 	? $_POST['tfDBport']+0 		: $_SESSION['install']['dbport'];
$_SESSION['install']['dbdrop'] 		= isset($_POST['chbDrop']) 		? $_POST['chbDrop']+0 		: $_SESSION['install']['dbdrop'];
$_SESSION['install']['sample'] 		= isset($_POST['chbSample']) 	? $_POST['chbSample']+0 	: $_SESSION['install']['sample'];
$_SESSION['install']['createpath']  = isset($_POST['chbPath']) 		? $_POST['chbPath']+0 		: $_SESSION['install']['createpath'];
$_SESSION['install']['qlpath']		= isset($_POST['tfQLpath'])		? $_POST['tfQLpath']		: $_SESSION['install']['qlpath'];
$_SESSION['install']['nagpath']		= isset($_POST['tfNagiosPath'])	? $_POST['tfNagiosPath']	: $_SESSION['install']['nagpath'];


if (isset($_POST['butNewInstall'])) $chkModus = "Installation";
if (isset($_POST['butUpgrade'])) 	$chkModus = "Update";
if (!isset($_SESSION['install']['mode']))	$_SESSION['install']['mode']	= $chkModus;
// 
// Store data to session parameters
// ================================

//
// Language settings
// =================
if (extension_loaded('gettext')) {
	putenv("LC_ALL=".$_SESSION['install']['locale'].$preEncode);
	putenv("LANG=".$_SESSION['install']['locale'].$preEncode);
	// GETTEXT domain
	setlocale(LC_ALL, $_SESSION['install']['locale'].".".$preEncode);
	bindtextdomain($_SESSION['install']['locale'], $preLocale);
	bind_textdomain_codeset($_SESSION['install']['locale'], $preEncode);
	textdomain($_SESSION['install']['locale']);
}
//
// Content in buffer laden
// =======================
ob_start();
include "step".$chkStep.".php";
$strContent = ob_get_contents();
ob_end_clean();
//
// Build content
// =============
$arrTemplate['PAGETITLE']	= "[NagiosQL] Installation Wizard";
$arrTemplate['MAIN_TITLE']	= translate("Welcome to the NagiosQL Installation Wizard");
$arrTemplate['CONTENT']		= $strContent;
//
// Write content
// =============
$myInstClass->filTemplate = $preContent;
$strContent = $myInstClass->parseTemplate($arrTemplate,$preContent);
echo $strContent;
?>
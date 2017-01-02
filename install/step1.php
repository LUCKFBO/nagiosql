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
// Prevent this file from direct access
// ====================================
if(preg_match('#' . basename(__FILE__) . '#', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'utf-8'))) {
  exit;
}
//
// Define common variables
// =======================
$preIncludeContent	= "templates/step1.tpl.htm";
$intError 			= 0;
//
// Define check arrays
// ===================
$arrRequiredExt = array (
	'Session'	=> 'session',
	'Gettext'	=> 'gettext',
	'Filter'	=> 'filter'
);
$arrOptionalExt = array (
	'FTP'		=> 'ftp',
	'SSH2'		=> 'ssh2'
);	
//$arrSupportedDBs = array (
//	'MySQL'		=> 'mysql',
//	'Postgres'	=> 'pgsql'
//);
$arrSupportedDBs = array (
	'MySQLi'		=> 'mysqli'
);

$arrIniCheck = array (
	'file_uploads'				=> 1,
	'session.auto_start'		=> 0,
	'suhosin.session.encrypt'	=> 0,
	'date.timezone'				=> '-NOTEMPTY-'
);					  
$arrSourceURLs = array(
    'Sockets'   	=> 'http://www.php.net/manual/en/book.sockets.php',
    'Session'   	=> 'http://www.php.net/manual/en/book.session.php',
    'PCRE'      	=> 'http://www.php.net/manual/en/book.pcre.php',
    'FileInfo'  	=> 'http://www.php.net/manual/en/book.fileinfo.php',
    'Mcrypt'    	=> 'http://www.php.net/manual/en/book.mcrypt.php',
    'OpenSSL'   	=> 'http://www.php.net/manual/en/book.openssl.php',
    'JSON'      	=> 'http://www.php.net/manual/en/book.json.php',
    'DOM'       	=> 'http://www.php.net/manual/en/book.dom.php',
    'Intl'      	=> 'http://www.php.net/manual/en/book.intl.php',
	'gettext'  		=> 'http://www.php.net/manual/en/book.gettext.php',
	'curl'			=> 'http://www.php.net/manual/en/book.curl.php',
	'Filter'    	=> 'http://www.php.net/manual/en/book.filter.php',
	'XML'       	=> 'http://www.php.net/manual/en/book.xml.php',
	'SimpleXML' 	=> 'http://www.php.net/manual/en/book.simplexml.php',
	'FTP'       	=> 'http://www.php.net/manual/en/book.ftp.php',
	'MySQL'     	=> 'http://php.net/manual/de/book.mysql.php',
	'PEAR'      	=> 'http://pear.php.net',
	'date.timezone' => 'http://www.php.net/manual/en/datetime.configuration.php#ini.date.timezone',
	'SSH2'      	=> 'http://pecl.php.net/package/ssh2'
);
//
// Build content
// =============
$arrTemplate['STEP1_BOX'] 		= translate('Requirements');
$arrTemplate['STEP2_BOX']		= translate($_SESSION['install']['mode']);
$arrTemplate['STEP3_BOX'] 		= translate('Finish');
$arrTemplate['STEP1_TITLE'] 	= "NagiosQL ".translate($_SESSION['install']['mode']).": ".translate("Checking requirements");
$arrTemplate['STEP1_SUBTITLE1'] = translate("Checking Client");
$arrTemplate['STEP1_SUBTITLE2'] = translate("Checking PHP version");
$arrTemplate['STEP1_SUBTITLE3'] = translate("Checking PHP extensions");
$arrTemplate['STEP1_SUBTITLE4'] = translate("Checking available database interfaces");
$arrTemplate['STEP1_SUBTITLE5'] = translate("Checking php.ini/.htaccess settings");
$arrTemplate['STEP1_SUBTITLE6'] = translate("Checking System Permission");
$arrTemplate['STEP1_TEXT3_1'] 	= translate("The following modules/extensions are <em>required</em> to run NagiosQL");
$arrTemplate['STEP1_TEXT3_2'] 	= translate("The next couple of extensions are <em>optional</em> but recommended");
$arrTemplate['STEP1_TEXT4_1'] 	= translate("Check which of the supported extensions are installed. At least one of them is required.");
$arrTemplate['STEP1_TEXT5_1'] 	= translate("The following settings are <em>required</em> to run NagiosQL");
//
// Conditional checks
// =======================
$strHTMLPart1 = "<img src=\"images/valid.png\" alt=\"valid\" title=\"valid\" class=\"textmiddle\"> ";
$strHTMLPart2 = "<img src=\"images/invalid.png\" alt=\"invalid\" title=\"invalid\" class=\"textmiddle\"> ";
$strHTMLPart3 = "<img src=\"images/warning.png\" alt=\"warning\" title=\"warning\" class=\"textmiddle\"> ";
$strHTMLPart4 = ": <span class=\"green\">";
$strHTMLPart5 = ": <span class=\"red\">";
$strHTMLPart6 = ": <span class=\"yellow\">";
$strHTMLPart7 = "<img src=\"images/onlinehelp.png\" alt=\"online help\" title=\"online help\" class=\"textmiddle\" style=\"border:none;\">";

// Javascript check
if ($_SESSION['install']['jscript'] == "yes") {
	$arrTemplate['CHECK_1_PIC'] = "valid"; 		$arrTemplate['CHECK_1_CLASS'] = "green";	$arrTemplate['CHECK_1_VALUE'] = translate("ENABLED");
} else {
	$arrTemplate['CHECK_1_PIC'] = "invalid"; 	$arrTemplate['CHECK_1_CLASS'] = "green";	$arrTemplate['CHECK_1_VALUE'] = translate("NOT ENABLED");
}
// PHP version check
define('MIN_PHP_VERSION', '5.2.0');
$arrTemplate['CHECK_2_TEXT'] = translate("Version");
if (version_compare(PHP_VERSION, MIN_PHP_VERSION, '>=')) {
	$arrTemplate['CHECK_2_PIC']  = "valid";		$arrTemplate['CHECK_2_CLASS'] = "green";	$arrTemplate['CHECK_2_VALUE'] = translate("OK");
	$arrTemplate['CHECK_2_INFO'] = "(PHP ". PHP_VERSION ." ".translate("detected").")";
} else {
	$arrTemplate['CHECK_2_PIC']  = "invalid"; 	$arrTemplate['CHECK_2_CLASS'] = "green";	$arrTemplate['CHECK_2_VALUE'] = "PHP ". PHP_VERSION ." ".translate("detected");
	$arrTemplate['CHECK_2_INFO'] = "(PHP ". MIN_PHP_VERSION ." ".translate("or greater is required").")";
	$intError = 1;
}
// PHP modules / extensions
$strExtPath = ini_get('extension_dir');
$strPrefix 	= (PHP_SHLIB_SUFFIX === 'dll') ? 'php_' : '';
$strHTML	= "";
foreach ($arrRequiredExt as $key => $elem) {
	if (extension_loaded($elem)) {
		$strHTML .= $strHTMLPart1.$key.$strHTMLPart4.translate("OK")."</span>\n";
	} else {
		$strPath = $strExtPath."/".$strPrefix.$elem.".".PHP_SHLIB_SUFFIX;
		$strMsg  = @is_readable($strPath) ? translate("Could be loaded. Please add in php.ini") : "<a href=\"".$arrSourceURLs[$key]."\" target=\"_blank\">".$strHTMLPart7."</a>";
		$strHTML .= $strHTMLPart2.$key.$strHTMLPart5.translate("NOT AVAILABLE")." (".$strMsg.")</span>\n";
		$intError = 1;
	}
	$strHTML .= "<br>\n";
}
$arrTemplate['CHECK_3_CONTENT_1'] = $strHTML;
$strHTML	= "";
foreach ($arrOptionalExt as $key => $elem) {
	if (extension_loaded($elem)) {
		$strHTML .= $strHTMLPart1.$key.$strHTMLPart4.translate("OK")."</span>\n";
	} else {
		$strPath = $strExtPath."/".$strPrefix.$elem.".".PHP_SHLIB_SUFFIX;
		$strMsg  = @is_readable($strPath) ? translate("Could be loaded. Please add in php.ini") : "<a href=\"".$arrSourceURLs[$key]."\" target=\"_blank\">".$strHTMLPart7."</a>";
		$strHTML .= $strHTMLPart3.$key.$strHTMLPart6.translate("NOT AVAILABLE")." (".$strMsg.")</span>\n";
		//$intError = 1;
	}
	$strHTML .= "<br>\n";
}
$arrTemplate['CHECK_3_CONTENT_2'] = $strHTML;
// PHP database interfaces
$strHTML = "";
$intTemp = 0;
foreach ($arrSupportedDBs as $key => $elem) {
	if (extension_loaded($elem)) {
		$strNewInstallOnly = "";
		if (($_SESSION['install']['dbtype'] != $elem) && ($_SESSION['install']['mode'] == "Update")) $strNewInstallOnly = " (".translate("New installation only - updates are only supported using the same database interface!").")";
		$strHTML .= $strHTMLPart1.$key.$strHTMLPart4.translate("OK")."</span>  $strNewInstallOnly\n";
		$intTemp++;
	} else {
		$strPath = $strExtPath."/".$strPrefix.$elem.".".PHP_SHLIB_SUFFIX;
		$strMsg  = @is_readable($strPath) ? translate("Could be loaded. Please add in php.ini") : "<a href=\"".$arrSourceURLs[$key]."\" target=\"_blank\">".$strHTMLPart7."</a>";
		$strHTML .= $strHTMLPart2.$key.$strHTMLPart5.translate("NOT AVAILABLE")." (".$strMsg.")</span>\n";
	}
	$strHTML .= "<br>\n";
}
$arrTemplate['CHECK_4_CONTENT_1'] = $strHTML;
if ($intTemp == 0) $intError = 1;
// PHP ini checks
$strHTML = "";
foreach ($arrIniCheck as $key => $elem) {
	$strStatus = ini_get($key);
	if ($elem === '-NOTEMPTY-') {
		if (empty($strStatus)) {
			$strHTML .= $strHTMLPart2.$key.$strHTMLPart5.translate("NOT AVAILABLE")." (".translate("cannot be empty and needs to be set").")</span>\n";
			$intError = 1;
		} else {
			$strHTML .= $strHTMLPart1.$key.$strHTMLPart4.translate("OK")."</span>\n";
		}
	} else {
		if ($strStatus == $elem) {
			$strHTML .= $strHTMLPart1.$key.$strHTMLPart4.translate("OK")."</span>\n";
		} else {
			$strHTML .= $strHTMLPart2.$key.$strHTMLPart5.$status." (".translate("should be")." ".$elem.")</span>\n";
			$intError = 1;
		}
	}
	$strHTML .= "<br>\n";
}
$arrTemplate['CHECK_5_CONTENT_1'] = $strHTML;
// File access checks
$strConfigFile = "../config/settings.php";
if (file_exists($strConfigFile) && is_readable($strConfigFile)) {
	$arrTemplate['CHECK_6_CONTENT_1'] = $strHTMLPart1.translate("Read test on settings file (config/settings.php)").$strHTMLPart4.translate("OK")."</span><br>\n";
} else if (file_exists($strConfigFile)&& !is_readable($strConfigFile)) {
	$arrTemplate['CHECK_6_CONTENT_1'] = $strHTMLPart2.translate("Read test on settings file (config/settings.php)").$strHTMLPart5.translate("failed")."</span><br>\n";
} elseif (!file_exists($strConfigFile)) {
	$arrTemplate['CHECK_6_CONTENT_1'] = $strHTMLPart3.translate("Settings file does not exists (config/settings.php)").$strHTMLPart6.translate("will be created")."</span><br>\n";
}
if(file_exists($strConfigFile) && is_writable($strConfigFile)) {
	$arrTemplate['CHECK_6_CONTENT_2'] = $strHTMLPart1.translate("Write test on settings file (config/settings.php)").$strHTMLPart4.translate("OK")."</span><br>\n";
} else if (is_writable("../config") && !file_exists($strConfigFile)) {
	$arrTemplate['CHECK_6_CONTENT_2'] = $strHTMLPart1.translate("Write test on settings directory (config/)").$strHTMLPart4.translate("OK")."</span><br>\n";
} else if (file_exists($strConfigFile) && !is_writable($strConfigFile)) {
	$arrTemplate['CHECK_6_CONTENT_2'] = $strHTMLPart2.translate("Write test on settings file (config/settings.php)").$strHTMLPart5.translate("failed")."</span><br>\n";
	$intError = 1;
} else {
	$arrTemplate['CHECK_6_CONTENT_2'] = $strHTMLPart2.translate("Write test on settings directory (config/)").$strHTMLPart5.translate("failed")."</span><br>\n";
	$intError = 1;
}
$strClassFile = "../functions/nag_class.php";
if(file_exists($strClassFile) && is_readable($strClassFile)) {
	$arrTemplate['CHECK_6_CONTENT_3'] = $strHTMLPart1.translate("Read test on a class file (functions/nag_class.php)").$strHTMLPart4.translate("OK")."</span><br>\n";
} else {
	$arrTemplate['CHECK_6_CONTENT_3'] = $strHTMLPart2.translate("Read test on a class file (functions/nag_class.php)").$strHTMLPart5.translate("failed")."</span><br>\n";
	$intError = 1;
}
$strFile = "../admin.php";
if(file_exists($strFile) && is_readable($strFile)) {
	$arrTemplate['CHECK_6_CONTENT_4'] = $strHTMLPart1.translate("Read test on startsite file (admin.php)").$strHTMLPart4.translate("OK")."</span><br>\n";
} else {
	$arrTemplate['CHECK_6_CONTENT_4'] = $strHTMLPart2.translate("Read test on startsite file (admin.php)").$strHTMLPart5.translate("failed")."</span><br>\n";
	$intError = 1;
}
$strFile = "../templates/index.tpl.htm";
if(file_exists($strFile) && is_readable($strFile)) {
	$arrTemplate['CHECK_6_CONTENT_5'] = $strHTMLPart1.translate("Read test on a template file (templates/index.tpl.htm)").$strHTMLPart4.translate("OK")."</span><br>\n";
} else {
	$arrTemplate['CHECK_6_CONTENT_5'] = $strHTMLPart2.translate("Read test on a template file (templates/index.tpl.htm)").$strHTMLPart5.translate("failed")."</span><br>\n";
	$intError = 1;
}
$strFile = "../templates/admin/admin_master.tpl.htm";
if(file_exists($strFile) && is_readable($strFile)) {
	$arrTemplate['CHECK_6_CONTENT_6'] = $strHTMLPart1.translate("Read test on a admin template file (templates/admin/admin_master.tpl.htm)").$strHTMLPart4.translate("OK")."</span><br>\n";
} else {
	$arrTemplate['CHECK_6_CONTENT_6'] = $strHTMLPart2.translate("Read test on a admin template file (templates/admin/admin_master.tpl.htm)").$strHTMLPart5.translate("failed")."</span><br>\n";
	$intError = 1;
}
$strFile = "../templates/files/contacts.tpl.dat";
if(file_exists($strFile) && is_readable($strFile)) {
	$arrTemplate['CHECK_6_CONTENT_7'] = $strHTMLPart1.translate("Read test on a file template (templates/files/contacts.tpl.dat)").$strHTMLPart4.translate("OK")."</span><br>\n";
} else {
	$arrTemplate['CHECK_6_CONTENT_7'] = $strHTMLPart2.translate("Read test on a file template (templates/files/contacts.tpl.dat)").$strHTMLPart5.translate("failed")."</span><br>\n";
	$intError = 1;
}
$strFile = "../images/pixel.gif";
if(file_exists($strFile) && is_readable($strFile)) {
	$arrTemplate['CHECK_6_CONTENT_8'] = $strHTMLPart1.translate("Read test on a image file (images/pixel.gif)").$strHTMLPart4.translate("OK")."</span><br>\n";
} else {
	$arrTemplate['CHECK_6_CONTENT_9'] = $strHTMLPart2.translate("Read test on a image file (images/pixel.gif)").$strHTMLPart5.translate("failed")."</span><br>\n";
	$intError = 1;
}
if ($intError != 0) {
	$arrTemplate['MESSAGE']	 = "<span class=\"red\">".translate("There are some errors - please check your system settings and read the requirements of NagiosQL!")."</span><br><br>\n";
	$arrTemplate['MESSAGE']	.= translate("Read the INSTALLATION file from NagiosQL to find out, how to fix them.") ."<br>\n";
	$arrTemplate['MESSAGE']	.= translate("After that - refresh this page to proceed") ."...<br>\n";
	$arrTemplate['DIV_ID']	 = "install-center";
	$arrTemplate['FORM_CONTENT']  = "<input type=\"image\" src=\"images/reload.png\" title=\"refresh\" value=\"Submit\" alt=\"refresh\" onClick=\"window.location.reload()\"><br>";
	$arrTemplate['FORM_CONTENT'] .= translate("Refresh")."\n";
} else {
	$arrTemplate['MESSAGE']  = "<span class=\"green\">".translate("Environment test sucessfully passed")."</span><br><br>\n";
	$arrTemplate['DIV_ID']   = "install-next";
	$arrTemplate['FORM_CONTENT']  = "<input type=\"hidden\" name=\"hidStep\" id=\"hidStep\" value=\"2\">\n";
	$arrTemplate['FORM_CONTENT'] .= "<input type=\"image\" src=\"images/next.png\" value=\"Submit\" title=\"next\" alt=\"next\"><br>".translate("Next")."\n";
}
//
// Write content
// =============
$strContent = $myInstClass->parseTemplate($arrTemplate,$preIncludeContent);
echo $strContent;
?>
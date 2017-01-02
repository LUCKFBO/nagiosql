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
// Class: Common visualization functions
//
///////////////////////////////////////////////////////////////////////////////////////////////
//
// Includes all functions used to display the application data
//
// Name: nagvisual
//
///////////////////////////////////////////////////////////////////////////////////////////////
class nagvisual {
  	// Define class variables
    var $arrSettings;       		// Array includes all global settings
  	var $intDomainId  		= 0;	// Domain id value
  	var $myDBClass;         		// NagiosQL database class object
	var $myContentTpl;				// Content template object
	var $dataId;					// Content data ID
	var $intPageId;					// Content page ID
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
    	// Read global settings
    	$this->arrSettings = $_SESSION['SETS'];
    	if (isset($_SESSION['domain'])) $this->intDomainId = $_SESSION['domain'];
  	}
	
    ///////////////////////////////////////////////////////////////////////////////////////////
    //  Function: Get menu position
    ///////////////////////////////////////////////////////////////////////////////////////////
    //
    //  Determines the actual position inside the menu tree and returns it as an info line
  	//
  	//  Parameters:  		$intPageId  Current content id
  	//            			$strTop     Label string for the root node
  	//
  	//  Return value:     	HTML info string
  	//
  	///////////////////////////////////////////////////////////////////////////////////////////
  	function getPosition($intPageId,$strTop="") {
    	$strPosition = "";
    	$strSQL    = "SELECT B.`mnuName` AS `mainitem`, B.`mnuLink` AS `mainlink`, A.`mnuName` AS `subitem`, A.`mnuLink` AS `sublink`
					  FROM `tbl_menu` AS A LEFT JOIN `tbl_menu` AS B ON A.`mnuTopId` = B.`mnuId`
					  WHERE A.`mnuId`=$intPageId";
    	$booReturn = $this->myDBClass->getDataArray($strSQL,$arrData,$intDataCount);
		if ($booReturn == false) $this->strErrorMessage .= $this->myDBClass->strErrorMessage;
    	if ($booReturn && ($intDataCount != 0)) {
      		$strMainLink 	= $this->arrSettings['path']['base_url'].$arrData[0]['mainlink'];
      		$strMain 		= $arrData[0]['mainitem'];
			$strSubLink 	= $this->arrSettings['path']['base_url'].$arrData[0]['sublink'];
        	$strSub 		= $arrData[0]['subitem'];
      		if ($strTop != "") {
        		$strPosition .= "<a href='".$this->arrSettings['path']['base_url']."admin.php'>".$strTop."</a> -> ";
      		}
			if ($strMain != "") {
      			$strPosition .= "<a href='".$strMainLink."'>".translate($strMain)."</a> -> <a href='".$strSubLink."'>".translate($strSub)."</a>";
			} else {
      			$strPosition .= "<a href='".$strSubLink."'>".translate($strSub)."</a>";
			}
    	}
    	return $strPosition;
  	}
	
    ///////////////////////////////////////////////////////////////////////////////////////////
  	//  Function: Display main menu
  	///////////////////////////////////////////////////////////////////////////////////////////
  	//
  	//  Build the main menu and display them
  	//
  	//  Parameters:  		$intPageId  Current content id
  	//            			$intCntId  	Menu group ID
  	//
  	//  Return value:     	HTML menu string
  	//
  	///////////////////////////////////////////////////////////////////////////////////////////
  	function getMenu($intPageId,$intCntId=1) {
		// Modify URL for visible/invisible menu
    	$strQuery = str_replace("menu=visible&","",$_SERVER['QUERY_STRING']);
    	$strQuery = str_replace("menu=invisible&","",$strQuery);
    	$strQuery = str_replace("menu=visible","",$strQuery);
    	$strQuery = str_replace("menu=invisible","",$strQuery);
    	if ($strQuery != "") {
      		$strURIVisible   = str_replace("&","&amp;",filter_var($_SERVER['PHP_SELF'], FILTER_SANITIZE_STRING)."?menu=visible&".$strQuery);
      		$strURIInvisible = str_replace("&","&amp;",filter_var($_SERVER['PHP_SELF'], FILTER_SANITIZE_STRING)."?menu=invisible&".$strQuery);
    	} else {
      		$strURIVisible   = filter_var($_SERVER['PHP_SELF'], FILTER_SANITIZE_STRING)."?menu=visible";
      		$strURIInvisible = filter_var($_SERVER['PHP_SELF'], FILTER_SANITIZE_STRING)."?menu=invisible";
    	}
		$this->intPageId = $intPageId;
		if (!(isset($_SESSION['menu'])) || ($_SESSION['menu'] != "invisible")) {
      		// Menu visible
      		$strMenuHTML  = "<td width=\"150\" align=\"center\" valign=\"top\">\n";
      		$strMenuHTML .= "<table cellspacing=\"1\" class=\"menutable\">\n";
			$this->getMenuRecursive(0,$strMenuHTML,'menu',$intCntId);
      		$strMenuHTML .=  "</table>\n";
      		$strMenuHTML .=  "<br><a href=\"$strURIInvisible\" class=\"menulinksmall\">[".translate('Hide menu')."]</a>\n";
			$strMenuHTML .=  "<div id=\"donate\"><a href=\"http://sourceforge.net/donate/index.php?group_id=134390\" ";
			$strMenuHTML .=  "target=\"_blank\"><img src=\"".$this->arrSettings['path']['base_url']."images/donate_2.png\" ";
			$strMenuHTML .=  "width=\"60\" height=\"24\" border=\"0\" alt=\"".translate('Donate for NagiosQL on sourceforge')."\"";
			$strMenuHTML .=  " title=\"".translate('Donate for NagiosQL on sourceforge')."\"></a></div>";
      		$strMenuHTML .=  "</td>\n";
    	} else {
      		// Menu invisible
      		$strMenuHTML  =  "<td valign=\"top\">\n";
      		$strMenuHTML .=  "<a href=\"$strURIVisible\"><img src=\"".$this->arrSettings['path']['base_url']."images/menu.gif\" alt=\"".translate('Show menu')."\" border=\"0\" ></a>\n";
      		$strMenuHTML .=  "</td>\n";
    	}
		return($strMenuHTML);
		
	}
	///////////////////////////////////////////////////////////////////////////////////////////
	//  Menu help functions
	///////////////////////////////////////////////////////////////////////////////////////////  
	// Recursive function to build the main menu
  	function getMenuRecursive($intTopId,&$strMenuHTML,$strCSS,$intCntId) {
		// Check depth
		$intLevel = substr_count($strCSS,'_sub') + 1;
		// Define SQL
		$strSQL = "SELECT mnuId, mnuName, mnuTopId, mnuLink FROM tbl_menu 
				   WHERE mnuTopId=$intTopId AND mnuCntId=$intCntId AND mnuActive <> 0 AND mnuGrpId IN (".$this->getAccGroups('read').") ORDER BY mnuOrderId";
		$booRet = $this->myDBClass->getDataArray($strSQL,$arrData,$intDataCount);
		if (($booRet != false) && ($intDataCount != 0)) {
			$strMenuHTMLTemp = "";
			$booReturn1      = false;
			// Menu items
			foreach ($arrData AS $elem) {
				$strName = translate($elem['mnuName']);
				$strLink = $this->arrSettings['path']['base_url'].$elem['mnuLink'];
				$strMenuHTMLTemp .= "  <tr>\n";
				if (($elem['mnuId'] == $this->intPageId) || ($this->checkMenuActive($elem['mnuId']) == true)) {
					$strMenuHTMLTemp .= "    <td class=\"".$strCSS."_act\">";
					$strMenuHTMLTemp .= "<a href=\"".$strLink."\">".$strName."</a></td>\n";
					$booReturn1 = true;
				} else {
					$strMenuHTMLTemp .= "    <td class=\"".$strCSS."\">";
					$strMenuHTMLTemp .= "<a href=\"".$strLink."\">".$strName."</a></td>\n";
				}
				$strMenuHTMLTemp .= "  </tr>\n";
				// Recursive call to get submenu items
				if (($elem['mnuId'] == $this->intPageId) || ($this->checkMenuActive($elem['mnuId']) == true)) {
					if ($this->getMenuRecursive($elem['mnuId'],$strMenuHTMLTemp,$strCSS."_sub",$intCntId) == true) $booReturn1 = true;
				}
				if ($intTopId == $this->intPageId) $booReturn1 = true;
			}
			if ($booReturn1 == true) {
				$strMenuHTML .= $strMenuHTMLTemp;
				return true;
			} else {
				if ($intLevel == 1) {
					$strMenuHTML .= $strMenuHTMLTemp;
				}
				return false;
			}
		} else {
			$this->strErrorMessage .= $this->myDBClass->strErrorMessage;
			return false;
		}
  	}
	// Function to find active top menu items
 	function checkMenuActive($intMenuId) {
    	$strSQL = "SELECT mnuTopId FROM tbl_menu WHERE mnuId=".$this->intPageId." AND mnuActive <> 0 AND mnuGrpId IN (".$this->getAccGroups('read').")";
    	$booRet = $this->myDBClass->getDataArray($strSQL,$arrData,$intDataCount);
		if (($booRet != false) && ($intDataCount != 0)) {	
			foreach ($arrData AS $elem) {
		   		if ($elem['mnuTopId'] == $intMenuId) return true;
			}
	    	return false;
		} else {
			$this->strErrorMessage .= $this->myDBClass->strErrorMessage;
			return false;
		}
 	 }

	///////////////////////////////////////////////////////////////////////////////////////////
  	//  Function: Process "null" values
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Replaces "NULL" with -1
	//
  	//  Parameters:  		$strKey		Process string
	//
  	//  Return value:		Modified process string
	//
	///////////////////////////////////////////////////////////////////////////////////////////
  	function checkNull($strKey) {
    	if (strtoupper($strKey) == "NULL") {
      		return("-1");
    	}
    	return($strKey);
  	}
	
	///////////////////////////////////////////////////////////////////////////////////////////
  	//  Function: Process text values
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Add security features to text values
	//
  	//  Parameters:  		$strKey		Process string
	//
  	//  Return value:		Modified process string
	//
	///////////////////////////////////////////////////////////////////////////////////////////
  	function tfSecure($strKey) {
		$strKey = stripslashes($strKey);
		$strKey = $strKey;
    	return($strKey);
  	}
	
	///////////////////////////////////////////////////////////////////////////////////////////
  	//  Function: Check browser
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Checks the remote browser
	//
  	//  Return value:		Browser String
	//
	///////////////////////////////////////////////////////////////////////////////////////////
  	function browserCheck() {
		if(stristr($_SERVER['HTTP_USER_AGENT'], 'msie')) {
    		return("msie");
    	} else if(stristr($_SERVER['HTTP_USER_AGENT'], 'firefox')) {
			return("firefox");
    	} else if(stristr($_SERVER['HTTP_USER_AGENT'], 'opera')) {
   			return("opera");
    	}
		return("unknown");
  	}

	///////////////////////////////////////////////////////////////////////////////////////////
	//  Function: Processing path strings
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Adds a "/" after a parh string and replaces double "//" with "/"
	//
  	//  Parameters:  		$strPath	Path string
	//
  	//  Return value:		Modified path string
	//
	///////////////////////////////////////////////////////////////////////////////////////////
  	function addSlash($strPath) {
    	if ($strPath == "") return("");
    	$strPath = $strPath."/";
		while(substr_count($strPath,"//") != 0) {
    		$strPath = str_replace("//","/",$strPath);
		}
    	return ($strPath);
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
	function processMessage($strNewMessage,&$strOldMessage,$strSeparate="<br>") {
		$strNewMessage = str_replace("::::","::",$strNewMessage);
		$strNewMessage = str_replace("::",$strSeparate,$strNewMessage);
		if (($strOldMessage != "") && ($strNewMessage != "")) {
			if (substr_count($strOldMessage,$strNewMessage) == 0) {
				if (substr_count(substr($strOldMessage,-5),$strSeparate) == 0) {
					$strOldMessage .= $strSeparate.$strNewMessage;
				} else {
					$strOldMessage .= $strNewMessage;
				}
			}
		} else {
			$strOldMessage .= $strNewMessage;
		}
		// Reset message variable (prevent duplicates)
		$strNewMessage = "";
	}

	///////////////////////////////////////////////////////////////////////////////////////////
	//  Function: Check account group
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Checks if an user has acces to an account group
	//
  	//  Parameters:  		$intGroupId		Group ID
	//						$strType		Access type (read,write,link)
	//
  	//  Return value:		0 = access granted
	//						1 = no access
	//
	///////////////////////////////////////////////////////////////////////////////////////////
	function checkAccGroup($intGroupId,$strType) {
		// Admin braucht keine Berechtigung
		if ($_SESSION['userid'] == 1)  return(0);
		// Gruppe 0 hat uneingeschränkte Rechte
		if ($intGroupId == 0) return(0);
		// Datenbank abfragen
		switch($strType) {
			case 'read': 	$strTypeValue = "`read`='1'";  break;
			case 'write': 	$strTypeValue = "`write`='1'"; break;
			case 'link': 	$strTypeValue = "`link`='1'";  break;
			default:		return(1);
		}	
		$strSQL    = "SELECT * FROM `tbl_lnkGroupToUser` WHERE `idMaster` = $intGroupId 
					  AND `idSlave`=".$_SESSION['userid']." AND $strTypeValue";
		$booReturn = $this->myDBClass->getDataArray($strSQL,$arrDataMain,$intDataCount);
		if ($booReturn == false) $this->strErrorMessage .= $this->myDBClass->strErrorMessage;
		if (($booReturn != false) && ($intDataCount != 0)) {
			return(0);
		}
		return(1);
	}
	
	///////////////////////////////////////////////////////////////////////////////////////////
	//  Function: Returns read groups
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Returns any group ID with read access for the submitted user id
	//
  	//  Parameters:  		$strType		Access type (read,write,link)
	//
  	//  Return value:		Comma separated string with group id's
	//
	///////////////////////////////////////////////////////////////////////////////////////////
	function getAccGroups($strType) {
		$strReturn = "0,";
		// Admin becomes rights to all groups
		if ($_SESSION['userid'] == 1) {
			$strSQL = "SELECT `id`FROM `tbl_group`";
			$booReturn = $this->myDBClass->getDataArray($strSQL,$arrData,$intCount);
			if ($booReturn == false) $this->strErrorMessage .= $this->myDBClass->strErrorMessage;
			if ($booReturn && ($intCount != 0)) { 
				foreach (  $arrData AS $elem ) {
					$strReturn .= $elem['id'].","; 	
				}
			}
			$strReturn = substr($strReturn,0,-1);
			return $strReturn;
		}
		switch($strType) {
			case 'read': 	$strTypeValue = "`read`='1'";  break;
			case 'write': 	$strTypeValue = "`write`='1'"; break;
			case 'link': 	$strTypeValue = "`link`='1'";  break;
			default:		$strTypeValue = "'1'='2'";
		}	
		$strSQL    = "SELECT `idMaster` FROM `tbl_lnkGroupToUser` WHERE `idSlave`=".$_SESSION['userid']." AND $strTypeValue";
		$booReturn = $this->myDBClass->getDataArray($strSQL,$arrData,$intCount);
		if ($booReturn == false) $this->strErrorMessage .= $this->myDBClass->strErrorMessage;
		if ($booReturn && ($intCount != 0)) { 
			foreach (  $arrData AS $elem ) {
				$strReturn .= $elem['idMaster'].","; 	
			}
		}
		$strReturn = substr($strReturn,0,-1);
		return $strReturn;
	}
  
	///////////////////////////////////////////////////////////////////////////////////////////
	//  Function: Build site numbers
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Build a string which contains links for additional pages. This is used in data lists
	//  with more items then defined in settings "lines per page limit"
	//
  	//  Parameters:  		$strSite    	Link to page
	//            			$intDataCount  	Sum of all data lines
	//            			$chkLimit   	Actual data limit
	//            			$strOrderBy  	OrderBy Field
	//						$strOrderDir	Order direction
	//
  	//  Return value:		HTML string
	//
	///////////////////////////////////////////////////////////////////////////////////////////
	function buildPageLinks($strSite,$intDataCount,$chkLimit,$strOrderBy="",$strOrderDir="") {
		$intMaxLines  = $this->arrSettings['common']['pagelines'];
		$intCount     = 1;
		$intCheck 	  = 0;
		$strSiteHTML  = "<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\">\n<tr>\n<td class=\"sitenumber\" ";
		$strSiteHTML .= "style=\"padding-left:7px; padding-right:7px;\">".translate('Page').": </td>\n";
		for ($i=0;$i<$intDataCount;$i=$i+$intMaxLines) {
			$strLink1 = "<a href=\"".$strSite."?limit=$i&amp;orderby=$strOrderBy&amp;orderdir=$strOrderDir\">"; 
			$strLink2 = "onclick=\"location.href='".$strSite."?limit=$i&amp;orderby=$strOrderBy&amp;orderdir=$strOrderDir'\""; 
			if ((!(($chkLimit >= ($i+($intMaxLines*5))) || ($chkLimit <= ($i-($intMaxLines*5))))) || ($i==0) || ($i>=($intDataCount-$intMaxLines))) {
				if ($chkLimit == $i) {
					$strSiteHTML .= "<td class=\"sitenumber-sel\">$intCount</td>\n";	
				} else {
					$strSiteHTML .= "<td class=\"sitenumber\" $strLink2>".$strLink1.$intCount."</a></td>\n";
				}
				$intCheck = 0;
			} else if ($intCheck == 0) {
				$strSiteHTML .= "<td class=\"sitenumber\">...</td>\n";
				$intCheck = 1;
			}
			$intCount++;
		}
		$strSiteHTML .= "</tr>\n</table>\n";
    	if ($intCount > 2) {
      		return($strSiteHTML);
    	} else {
      		return("");
    	}
  	}
	
	///////////////////////////////////////////////////////////////////////////////////////////
	//  Function: Insert Domain list
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Inserts the domain list to the list view template
	//
  	//  Parameters:  		$resTemplate    Template object
	//
  	//  Return value:		HTML string
	//
	///////////////////////////////////////////////////////////////////////////////////////////
	function insertDomainList($resTemplate) {
		$strSQL    = "SELECT * FROM `tbl_datadomain` WHERE `active` <> '0' ORDER BY `domain`";
		$booReturn = $this->myDBClass->getDataArray($strSQL,$arrDataDomain,$intDataCount);
		if ($booReturn == false) {
			$strErrorMessage .= translate('Error while selecting data from database:')."::".$myDBClass->strErrorMessage;
		} else {
			if ($intDataCount != 0) {
				foreach($arrDataDomain AS $elem) {
					// Check acces rights
					if ($this->checkAccGroup($elem['access_group'],'read') == 0) {
						$resTemplate->setVariable("DOMAIN_ID",$elem['id']);
						$resTemplate->setVariable("DOMAIN_NAME",$elem['domain']);
						if ($_SESSION['domain'] == $elem['id']) {
							$resTemplate->setVariable("DOMAIN_SEL","selected");
						}
						$resTemplate->parse("domainlist");
					}
				}
			}
		}
	}
	

	///////////////////////////////////////////////////////////////////////////////////////////
	//  Function: Parse selection field (simple)
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Builds a simple selection field inside a template
	//
  	//  Parameters:  		$strTable     	Table name (source data)
	//                		$strTabField  	Field name (source data)
	//						$strTemplKey  	Template key
	//			    		$intModeId    	0=only data, 1=with empty line at the beginning, 
	//							  			2=with empty line and 'null' line at the beginning
	//			    		$intSelId     	Selected data ID (from master table)
	//						$intExclId	  	Exclude ID
	//				
	//
  	//  Return value:		0 = successful
	//						1 = error
	//						Status message is stored in message class variables
	//
	///////////////////////////////////////////////////////////////////////////////////////////
  	function parseSelectSimple($strTable,$strTabField,$strTemplKey,$intModeId=0,$intSelId=-9,$intExclId=-9) {
    	// Compute option value
		$intOption = 0;
		if ($strTemplKey == 'hostcommand') 		$intOption = 1;
		if ($strTemplKey == 'servicecommand') 	$intOption = 1;
		if ($strTemplKey == 'eventhandler') 	$intOption = 2;
		if ($strTemplKey == 'service_extinfo') 	$intOption = 7;
    	// Get version
    	$this->myConfigClass->getDomainData("version",$intVersion);
		// Get link rights
		$strAccess = $this->getAccGroups('link');
		// Get raw data
		$booRaw = $this->getSelectRawdata($strTable,$strTabField,$arrData,$intOption);
    	if ($booRaw == 0) {
	  		// Insert an empty line in mode 1
	  		if (($intModeId == 1) || ($intModeId == 2)) {
				$this->myContentTpl->setVariable("SPECIAL_STYLE","");
      			$this->myContentTpl->setVariable('DAT_'.strtoupper($strTemplKey),"&nbsp;");
				$this->myContentTpl->setVariable('DAT_'.strtoupper($strTemplKey).'_ID',0);
				if ($intVersion != 3) $this->myContentTpl->setVariable("VERSION_20_MUST","inpmust");
      			$this->myContentTpl->parse($strTemplKey);
	  		}
	  		// Insert a 'null' line in mode 2
	  		if ($intModeId == 2) {
				$this->myContentTpl->setVariable("SPECIAL_STYLE","");
				$this->myContentTpl->setVariable('DAT_'.strtoupper($strTemplKey),"null");
				$this->myContentTpl->setVariable('DAT_'.strtoupper($strTemplKey).'_ID',-1);
				if ($intVersion != 3) $this->myContentTpl->setVariable("VERSION_20_MUST","inpmust");
				if ($intSelId == -1)  $this->myContentTpl->setVariable('DAT_'.strtoupper($strTemplKey)."_SEL","selected");
				$this->myContentTpl->parse($strTemplKey);
	  		}
			// Insert data sets
			foreach ($arrData AS $elem) {
				$this->myContentTpl->setVariable("SPECIAL_STYLE","");
				if ($elem['key'] == $intExclId) continue;
				if (isset($elem['active']) && $elem['active'] == 0) { 
					$strActive=' [inactive]'; 
					$this->myContentTpl->setVariable("SPECIAL_STYLE","inactive_option");
				} else { 
					$strActive = ""; 
				}
				if (isset($elem['config_id']) && $elem['config_id'] == 0) {
					$this->myContentTpl->setVariable('DAT_'.strtoupper($strTemplKey),htmlspecialchars($elem['value'],ENT_QUOTES,'UTF-8').' [common]'.$strActive);
				} else {
					$this->myContentTpl->setVariable('DAT_'.strtoupper($strTemplKey),htmlspecialchars($elem['value'],ENT_QUOTES,'UTF-8').$strActive);
				}
				$this->myContentTpl->setVariable('DAT_'.strtoupper($strTemplKey)."_ID",$elem['key']);
				if ($intVersion != 3) $this->myContentTpl->setVariable("VERSION_20_MUST","inpmust");
				if ($intSelId == $elem['key']) {
					$this->myContentTpl->setVariable('DAT_'.strtoupper($strTemplKey)."_SEL","selected");
				}
				$this->myContentTpl->parse($strTemplKey);
			}
	  		return(0);
  		}
		return(1);
  	}
	
	///////////////////////////////////////////////////////////////////////////////////////////
	//  Function: Parse selection field (multi)
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Builds a multi selection field inside a template
	//
  	//  Parameters:  		$strTable     	Table name (source data)
	//                		$strTabField  	Field name (source data)
	//						$strTemplKey  	Template key
	//						$intDataId	  	Data ID of master table
	//			    		$intModeId    	0 = only data 
	//							  			1 = with empty line at the beginning
	//							  			2 = with * line at the beginning
	//			    		$intTypeId    	Type ID (from master table)
	//						$intExclId	  	Exclude ID
	//						$strRefresh	  	Session token for refresh mode
	//				
	//
  	//  Return value:		0 = successful
	//						1 = error
	//						Status message is stored in message class variables
	//
	///////////////////////////////////////////////////////////////////////////////////////////
  	function parseSelectMulti($strTable,$strTabField,$strTemplKey,$strLinkTable,$intModeId=0,$intTypeId=-9,$intExclId=-9,$strRefresh='') {
    	// Compute option value
		$intOption  = 0;
		$intRefresh = 0;
		if ($strLinkTable == 'tbl_lnkContactToCommandHost')    			$intOption = 2;
		if ($strLinkTable == 'tbl_lnkContactToCommandService') 			$intOption = 2;
		if ($strLinkTable == 'tbl_lnkContacttemplateToCommandHost')    	$intOption = 2;
		if ($strLinkTable == 'tbl_lnkContacttemplateToCommandService') 	$intOption = 2;
		if ($strLinkTable == 'tbl_lnkServicegroupToService')   			$intOption = 3;
		if ($strLinkTable == 'tbl_lnkServicedependencyToService_DS')    $intOption = 4;
		if ($strLinkTable == 'tbl_lnkServicedependencyToService_S')    	$intOption = 5;
		if ($strLinkTable == 'tbl_lnkServiceescalationToService')    	$intOption = 6;
		if ($strTemplKey  == 'host_services')							$intOption = 8;
		// Get version
    	$this->myConfigClass->getDomainData("version",$intVersion);
		// Get raw data
		$booRaw = $this->getSelectRawdata($strTable,$strTabField,$arrData,$intOption);
		// Get selected data
		$booSel = $this->getSelectedItems($strLinkTable,$arrSelected,$intOption);
		// Get additional selected data
		if ($strLinkTable == 'tbl_lnkHostToHostgroup') {
			$booSelAdd = $this->getSelectedItems("tbl_lnkHostgroupToHost",$arrSelectedAdd,8);
		}
		if ($strLinkTable == 'tbl_lnkHostgroupToHost') {
			$booSelAdd = $this->getSelectedItems("tbl_lnkHostToHostgroup",$arrSelectedAdd,8);
		}
		// Get browser
		$strBrowser = $this->browserCheck();
		// Refresh processing (replaces selection array)
		if ($strRefresh != '') {
			if (isset($_SESSION['refresh']) && isset($_SESSION['refresh'][$strRefresh]) && is_array($_SESSION['refresh'][$strRefresh])) {
				$arrSelected = $_SESSION['refresh'][$strRefresh];
				$intRefresh  = 1;
				$booSel 	 = 0;
			}
		}
    	if ($booRaw == 0) {
	  		$intCount = 0;
			// Insert an empty line in mode 1
	  		if ($intModeId == 1) {
				$this->myContentTpl->setVariable("SPECIAL_STYLE","");
				$this->myContentTpl->setVariable("OPTION_DISABLED","");
				if (($strBrowser == "msie") && ($this->arrSettings['common']['seldisable'] != 0)) $this->myContentTpl->setVariable("OPTION_DISABLED","disabled=\"disabled\"");
				$this->myContentTpl->setVariable('DAT_'.strtoupper($strTemplKey),"&nbsp;");
				$this->myContentTpl->setVariable('DAT_'.strtoupper($strTemplKey).'_ID',0);
				if ($intVersion != 3) $this->myContentTpl->setVariable("VERSION_20_MUST","inpmust");
				$this->myContentTpl->parse($strTemplKey);
				$intCount++;
	  		}
			// Insert an * line in mode 2
			if ($intModeId == 2) {
				$this->myContentTpl->setVariable("SPECIAL_STYLE","");
				$this->myContentTpl->setVariable("OPTION_DISABLED","");
				if (($strBrowser == "msie") && ($this->arrSettings['common']['seldisable'] != 0)) $this->myContentTpl->setVariable("OPTION_DISABLED","disabled=\"disabled\"");
				$this->myContentTpl->setVariable('DAT_'.strtoupper($strTemplKey),"*");
				$this->myContentTpl->setVariable('DAT_'.strtoupper($strTemplKey).'_ID',"*");
				if ($intVersion != 3) $this->myContentTpl->setVariable("VERSION_20_MUST","inpmust");
				if ($intTypeId  == 2) $this->myContentTpl->setVariable('DAT_'.strtoupper($strTemplKey)."_SEL","selected");
				if (($intRefresh == 1) && (in_array('*',$arrSelected))) {
					$this->myContentTpl->setVariable("DAT_".strtoupper($strTemplKey)."_SEL","selected");
					$this->myContentTpl->setVariable("IE_".strtoupper($strTemplKey)."_SEL","ieselected");
				}
				$intCount++;
				$this->myContentTpl->parse($strTemplKey);
			}
			// Insert data sets
			foreach ($arrData AS $elem) {
				if ($elem['key'] == $intExclId) continue;
				if ($elem['value'] == "") continue;
				$intIsSelected = 0;
				$intIsExcluded = 0;
				$intIsForeign  = 0;
				$this->myContentTpl->setVariable("SPECIAL_STYLE","");
				$this->myContentTpl->setVariable("OPTION_DISABLED","");
				if (($strBrowser == "msie") && ($this->arrSettings['common']['seldisable'] != 0)) $this->myContentTpl->setVariable("OPTION_DISABLED","disabled=\"disabled\"");
				if (isset($elem['active']) && $elem['active'] == 0) { 
					$strActive=' [inactive]'; 
					$this->myContentTpl->setVariable("SPECIAL_STYLE","inactive_option");
				} else { 
					$strActive = ""; 
				}
				if (isset($elem['config_id']) && $elem['config_id'] == 0) {
					$this->myContentTpl->setVariable('DAT_'.strtoupper($strTemplKey),htmlspecialchars($elem['value'],ENT_QUOTES,'UTF-8').' [common]'.$strActive);
				} else {
					$this->myContentTpl->setVariable('DAT_'.strtoupper($strTemplKey),htmlspecialchars($elem['value'],ENT_QUOTES,'UTF-8').$strActive);
				}
				$this->myContentTpl->setVariable('DAT_'.strtoupper($strTemplKey)."_ID",$elem['key']);
				$this->myContentTpl->setVariable('CLASS_SEL',"");
				if ($intVersion != 3) $this->myContentTpl->setVariable("VERSION_20_MUST","inpmust");
				if (($booSel == 0) && (in_array($elem['key'],$arrSelected)))   $intIsSelected = 1;
				if (($booSel == 0) && (in_array($elem['value'],$arrSelected))) $intIsSelected = 1;
				if (isset($booSelAdd) && ($booSelAdd == 0) && (in_array($elem['key'],$arrSelectedAdd)))   $intIsForeign = 1;
				if (isset($booSelAdd) && ($booSelAdd == 0) && (in_array($elem['value'],$arrSelectedAdd))) $intIsForeign = 1;
				if (($intIsForeign == 1) && ($strActive == "")) {
					$this->myContentTpl->setVariable("SPECIAL_STYLE","foreign_option");
				}
				// Exclude rule
				if (($booSel == 0) && (in_array("e".$elem['key'],$arrSelected))) 		$intIsExcluded = 1;
				if (($booSel == 0) && (in_array("e"."::".$elem['value'],$arrSelected))) $intIsExcluded = 1; 
				if ($intIsExcluded == 1) {
					if (isset($elem['config_id']) && $elem['config_id'] == 0) {
						$this->myContentTpl->setVariable('DAT_'.strtoupper($strTemplKey),'!'.htmlspecialchars($elem['value'],ENT_QUOTES,'UTF-8').' [common]'.$strActive);
					} else {
						$this->myContentTpl->setVariable('DAT_'.strtoupper($strTemplKey),'!'.htmlspecialchars($elem['value'],ENT_QUOTES,'UTF-8').$strActive);
					}
					$this->myContentTpl->setVariable('DAT_'.strtoupper($strTemplKey)."_ID",'e'.$elem['key']);
				}
				if (($intIsSelected == 1) || ($intIsExcluded == 1)) {
					$this->myContentTpl->setVariable("DAT_".strtoupper($strTemplKey)."_SEL","selected");
					$this->myContentTpl->setVariable("IE_".strtoupper($strTemplKey)."_SEL","ieselected");
				}
				$intCount++;
				$this->myContentTpl->parse($strTemplKey);
			}
			if ($intCount == 0) {
				// Insert an empty line to create valid HTML select fields
				$this->myContentTpl->setVariable('DAT_'.strtoupper($strTemplKey),"&nbsp;");
				$this->myContentTpl->setVariable('DAT_'.strtoupper($strTemplKey).'_ID',0);
				$this->myContentTpl->parse($strTemplKey);
			}
	  		return(0);
  		}
		// Insert an empty line to create valid HTML select fields
		$this->myContentTpl->setVariable('DAT_'.strtoupper($strTemplKey),"&nbsp;");
		$this->myContentTpl->setVariable('DAT_'.strtoupper($strTemplKey).'_ID',0);
		$this->myContentTpl->parse($strTemplKey);
		return(1);
  	}

    //3.1 HELP FUNCTIONS
	///////////////////////////////////////////////////////////////////////////////////////////
  	//  Help function: Get raw data
  	///////////////////////////////////////////////////////////////////////////////////////////
	//  $strTable			-> Table name
	//  $strTabField		-> Data field name
	//  $arrData			-> Raw data array
	//  $intOption			-> Option value
	//	Return value		-> 0=successful / 1=error
	///////////////////////////////////////////////////////////////////////////////////////////
  	function getSelectRawdata($strTable,$strTabField,&$arrData,$intOption=0) {
		// Get link rights
		$strAccess = $this->getAccGroups('link');
		// Common domain is enabled?
		$this->myConfigClass->getDomainData("enable_common",$intCommonEnable);
		if ($intCommonEnable == 1) {
			$strDomainWhere1 = " (`config_id`=".$this->intDomainId." OR `config_id`=0) ";
			$strDomainWhere2 = " (`tbl_service`.`config_id`=".$this->intDomainId." OR `tbl_service`.`config_id`=0) ";
		} else {
			$strDomainWhere1 = " `config_id`=".$this->intDomainId." ";
			$strDomainWhere2 = " `tbl_service`.`config_id`=".$this->intDomainId." ";
		}
		// Define SQL commands
		if ($strTable == 'tbl_group') {
			$strSQL  = "SELECT `id` AS `key`, `".$strTabField."` AS `value`, `active` FROM `".$strTable."` WHERE `active`='1' 
					   	AND `".$strTabField."` <> '' AND `".$strTabField."` IS NOT NULL ORDER BY `".$strTabField."`"; 
		} else if (($strTable == 'tbl_configtarget') || ($strTable == 'tbl_datadomain') || ($strTable == 'tbl_language')) {
			$strSQL  = "SELECT `id` AS `key`, `".$strTabField."` AS `value`, `active` FROM `".$strTable."` WHERE `".$strTabField."` <> '' AND 
					    `".$strTabField."` IS NOT NULL ORDER BY `".$strTabField."`"; 
		} else if (($strTable == 'tbl_command') && ($intOption == 1)) {
		   	$strSQL  = "SELECT `id` AS `key`, `".$strTabField."` AS `value`, `config_id`, `active` FROM `".$strTable."` WHERE $strDomainWhere1
					   	AND `".$strTabField."` <> '' AND `".$strTabField."` IS NOT NULL AND `access_group` IN ($strAccess) 
					   	AND (`command_type` = 0 OR `command_type` = 1) ORDER BY `".$strTabField."`"; 
		} else if (($strTable == 'tbl_command') && ($intOption == 2)) {
		   	$strSQL  = "SELECT `id` AS `key`, `".$strTabField."` AS `value`, `config_id`, `active` FROM `".$strTable."` WHERE $strDomainWhere1
					   	AND `".$strTabField."` <> '' AND `".$strTabField."` IS NOT NULL AND `access_group` IN ($strAccess) 
					   	AND (`command_type` = 0 OR `command_type` = 2) ORDER BY `".$strTabField."`"; 
		} else if (($strTable == 'tbl_timeperiod') && ($strTabField == 'name')) {
		   	$strSQL  = "SELECT `id` AS `key`, `timeperiod_name` AS `value`, `config_id`, `active` FROM `tbl_timeperiod` WHERE $strDomainWhere1
					   	AND `timeperiod_name` <> '' AND `timeperiod_name` IS NOT NULL AND `access_group` IN ($strAccess)
					   	UNION
					   	SELECT `id` AS `key`, `name` AS `value`, `config_id`, `active` FROM `tbl_timeperiod` WHERE $strDomainWhere1
					   	AND `name` <> '' AND `name` IS NOT NULL AND `name` <> `timeperiod_name` AND `access_group` IN ($strAccess) 
					   	ORDER BY value"; 
		} else if (($strTable == 'tbl_service') && ($intOption == 3)) {
	   		// Service groups
			$strSQL  = "SELECT CONCAT_WS('::',`tbl_host`.`id`,'0',`tbl_service`.`id`) AS `key`, 
					   	CONCAT('H:',`tbl_host`.`host_name`,',',`tbl_service`.`service_description`) AS `value`, `tbl_service`.`active` FROM `tbl_service`
					   	LEFT JOIN `tbl_lnkServiceToHost` ON `tbl_service`.`id` = `tbl_lnkServiceToHost`.`idMaster`
					   	LEFT JOIN `tbl_host` ON `tbl_lnkServiceToHost`.`idSlave` = `tbl_host`.`id`
					   	WHERE $strDomainWhere2 AND `tbl_service`.`service_description` <> '' 
					   	AND `tbl_service`.`service_description` IS NOT NULL AND `tbl_service`.`host_name` <> 0 AND `tbl_service`.`access_group` IN ($strAccess)
					   	UNION
					   	SELECT CONCAT_WS('::','0',`tbl_hostgroup`.`id`,`tbl_service`.`id`) AS `key`, 
					   	CONCAT('HG:',`tbl_hostgroup`.`hostgroup_name`,',',`tbl_service`.`service_description`) AS `value`, `tbl_service`.`active` FROM `tbl_service`
					   	LEFT JOIN `tbl_lnkServiceToHostgroup` ON `tbl_service`.`id` = `tbl_lnkServiceToHostgroup`.`idMaster`
					   	LEFT JOIN `tbl_hostgroup` ON `tbl_lnkServiceToHostgroup`.`idSlave` = `tbl_hostgroup`.`id`
					   	WHERE $strDomainWhere2 AND `tbl_service`.`service_description` <> '' 
					   	AND `tbl_service`.`service_description` IS NOT NULL AND `tbl_service`.`hostgroup_name` <> 0  AND `tbl_service`.`access_group` IN ($strAccess)
						UNION
					   	SELECT CONCAT_WS('::',`tbl_host`.`id`,'0',`tbl_service`.`id`) AS `key`, 
					   	CONCAT('HHG:',`tbl_host`.`host_name`,',',`tbl_service`.`service_description`) AS `value`, `tbl_service`.`active` FROM `tbl_service`
					   	LEFT JOIN `tbl_lnkServiceToHostgroup` ON `tbl_service`.`id` = `tbl_lnkServiceToHostgroup`.`idMaster`
						LEFT JOIN `tbl_lnkHostgroupToHost` ON `tbl_lnkHostgroupToHost`.`idMaster` = `tbl_lnkServiceToHostgroup`.`idSlave`
					   	LEFT JOIN `tbl_host` ON `tbl_lnkHostgroupToHost`.`idSlave` = `tbl_host`.`id`
					   	WHERE $strDomainWhere2 AND `tbl_service`.`service_description` <> '' 
					   	AND `tbl_service`.`service_description` IS NOT NULL AND `tbl_service`.`hostgroup_name` <> 0  AND `tbl_service`.`access_group` IN ($strAccess)
						UNION
					   	SELECT CONCAT_WS('::',`tbl_host`.`id`,'0',`tbl_service`.`id`) AS `key`, 
					   	CONCAT('HGH:',`tbl_host`.`host_name`,',',`tbl_service`.`service_description`) AS `value`, `tbl_service`.`active` FROM `tbl_service`
					   	LEFT JOIN `tbl_lnkServiceToHostgroup` ON `tbl_service`.`id` = `tbl_lnkServiceToHostgroup`.`idMaster`
						LEFT JOIN `tbl_lnkHostToHostgroup` ON `tbl_lnkHostToHostgroup`.`idSlave` = `tbl_lnkServiceToHostgroup`.`idSlave`
					   	LEFT JOIN `tbl_host` ON `tbl_lnkHostToHostgroup`.`idMaster` = `tbl_host`.`id`
					   	WHERE $strDomainWhere2 AND `tbl_service`.`service_description` <> '' 
					   	AND `tbl_service`.`service_description` IS NOT NULL AND `tbl_service`.`hostgroup_name` <> 0  AND `tbl_service`.`access_group` IN ($strAccess)
					   	ORDER BY value";
		} else if (($strTable == 'tbl_service') && (($intOption == 4) || ($intOption == 5) || ($intOption == 6))) {
			// Define session variables
			if ($intOption == 6) {
				$strHostVar 	 = 'se_host';
				$strHostGroupVar = 'se_hostgroup';
			} else if ($intOption == 4) {
				$strHostVar 	 = 'sd_dependent_host';
				$strHostGroupVar = 'sd_dependent_hostgroup';
			} else {
				$strHostVar 	 = 'sd_host';
				$strHostGroupVar = 'sd_hostgroup';
			}
			$arrHosts 		= $_SESSION['refresh'][$strHostVar];
			$arrHostgroups  = $_SESSION['refresh'][$strHostGroupVar];
			$arrServices    = array();
			$arrServiceId   = array();
			if ((count($arrHosts) 	   == 1) && $arrHosts[0] 	  == "") $arrHosts 		= array();
			if ((count($arrHostgroups) == 1) && $arrHostgroups[0] == "") $arrHostgroups = array();
			if (isset($_SESSION['refresh']) && 
					(isset($_SESSION['refresh']['sd_dependent_service']) && is_array($_SESSION['refresh']['sd_dependent_service'])) ||
					(isset($_SESSION['refresh']['sd_service']) && is_array($_SESSION['refresh']['sd_service'])) ||
					(isset($_SESSION['refresh']['se_service']) && is_array($_SESSION['refresh']['se_service']))){
				// * Value in hosts -> disabled in NagiosQL 3.2
				if (in_array('*',$_SESSION['refresh'][$strHostVar])) {
					$strSQL 	= "SELECT id FROM tbl_host WHERE $strDomainWhere1 
								   AND `access_group` IN ($strAccess)";
					$booReturn = $this->myDBClass->getDataArray($strSQL,$arrDataHost,$intDCHost);
					if ($booReturn == false) $this->strErrorMessage .= $this->myDBClass->strErrorMessage;
					if ($booReturn && ($intDCHost != 0)) {
						$arrHostTemp = '';
						foreach ($arrDataHost AS $elem) {
							if (in_array("e".$elem['id'],$_SESSION['refresh'][$strHostVar])) continue;
							$arrHostTemp[] = $elem['id'];												  
						}
					}
					$strHosts = 1;
					$arrHosts = $arrHostTemp;
				} else {
					$strHosts = count($arrHosts)+0;
				}
				// * Value in host groups -> disabled in NagiosQL 3.2
				if (in_array('*',$_SESSION['refresh'][$strHostGroupVar])) {
					$strSQL 	= "SELECT id FROM tbl_hostgroup WHERE $strDomainWhere1 
								   AND `access_group` IN ($strAccess)";
					$booReturn = $this->myDBClass->getDataArray($strSQL,$arrDataHost,$intDCHost);
					if ($booReturn == false) $this->strErrorMessage .= $this->myDBClass->strErrorMessage;
					if ($booReturn && ($intDCHost != 0)) {
						$arrHostgroupTemp = '';
						foreach ($arrDataHost AS $elem) {
							if (in_array("e".$elem['id'],$_SESSION['refresh'][$strHostGroupVar])) continue;
							$arrHostgroupTemp[] = $elem['id'];												  
						}
					}
					$strHostsGroup = 1;
					$arrHostgroups = $arrHostgroupTemp;
				} else {
					$strHostsGroup = count($arrHostgroups)+0;
				}
				// Special method - only host_name or hostgroup_name selected
				if (($strHostVar == 'sd_dependent_host') && ($strHosts == 0) && ($strHostsGroup == 0)) {
					$arrHosts 		= $_SESSION['refresh']['sd_host'];
					$arrHostgroups  = $_SESSION['refresh']['sd_hostgroup'];
					if ((count($arrHosts) 	   == 1) && $arrHosts[0] 	  == "") $arrHosts 		= array();
					if ((count($arrHostgroups) == 1) && $arrHostgroups[0] == "") $arrHostgroups = array();
					$strHosts	   = count($arrHosts)+0;
					$strHostsGroup = count($arrHostgroups)+0;
				}	
				// If no hosts and hostgroups are selected show any service
				if (($strHosts == 0) && ($strHostsGroup == 0)) {
					$strSQL = "SELECT `id` AS `key`, `".$strTabField."` AS `value`, `active` FROM `tbl_service` 
							   WHERE $strDomainWhere1
							   AND `".$strTabField."` <> '' AND `".$strTabField."` IS NOT NULL AND `access_group` IN ($strAccess)
							   GROUP BY `value` ORDER BY `value`";
				} else {
					if ($strHosts != 0) {
						$intCounter = 0;
						foreach ($arrHosts AS $elem) {
							if (($intCounter != 0) && (count($arrServices) == 0)) continue;
							$arrTempServ   = array();
							$arrTempServId = array();
							$elem = str_replace("e","",$elem);
							$strSQLTmp = "SELECT `id`, `service_description` FROM `tbl_service` 
									      LEFT JOIN `tbl_lnkServiceToHost` ON `tbl_service`.`id` = `tbl_lnkServiceToHost`.`idMaster` 
									      WHERE $strDomainWhere1
									      AND `tbl_lnkServiceToHost`.`idSlave` = $elem
									      AND `service_description` <> '' AND `service_description` IS NOT NULL AND `access_group` IN ($strAccess)
										  UNION
										  SELECT `id`, `service_description` FROM `tbl_service`
										  LEFT JOIN `tbl_lnkServiceToHostgroup` ON `tbl_service`.`id` = `tbl_lnkServiceToHostgroup`.`idMaster`
										  LEFT JOIN `tbl_lnkHostToHostgroup` ON `tbl_lnkServiceToHostgroup`.`idSlave` = `tbl_lnkHostToHostgroup`.`idSlave`
										  WHERE $strDomainWhere1
										  AND `tbl_lnkHostToHostgroup`.`idMaster` = $elem
										  AND `service_description` <> '' AND `service_description` IS NOT NULL AND `access_group` IN ($strAccess)
										  UNION
										  SELECT `id`, `service_description` FROM `tbl_service`
										  LEFT JOIN `tbl_lnkServiceToHostgroup` ON `tbl_service`.`id` = `tbl_lnkServiceToHostgroup`.`idMaster`
										  LEFT JOIN `tbl_lnkHostgroupToHost` ON `tbl_lnkServiceToHostgroup`.`idSlave` = `tbl_lnkHostgroupToHost`.`idMaster`
										  WHERE $strDomainWhere1
										  AND `tbl_lnkHostgroupToHost`.`idSlave` = $elem
										  AND `service_description` <> '' AND `service_description` IS NOT NULL AND `access_group` IN ($strAccess)";
							$booReturn = $this->myDBClass->getDataArray($strSQLTmp,$arrDataTmp,$intDataTmp);
							if ($booReturn && ($intDataTmp != 0)) {
								foreach ($arrDataTmp AS $elem2) {
									if ($intCounter == 0) {
										$arrTempServ[]   = $elem2['service_description'];
										$arrTempServId[] = $elem2['id'];
									} else if (in_array($elem2['service_description'],$arrServices) && !in_array($elem2['service_description'],$arrTempServ)) {
										$arrTempServ[]   = $elem2['service_description'];
										$arrTempServId[] = $elem2['id'];
									}
								}
							}
							$arrServices   = $arrTempServ;
							$arrServicesId = $arrTempServId;
							$intCounter++;
						}
					}
					if ($strHostsGroup != 0) {
						if ($strHosts == 0) $intCounter = 0;
						foreach ($arrHostgroups AS $elem) {
							if (($intCounter != 0) && (count($arrServices) == 0)) continue;
							$arrTempServ   = array();
							$arrTempServId = array();
							$elem = str_replace("e","",$elem);
							$strSQLTmp = "SELECT `id`, `service_description` FROM `tbl_service` 
									      LEFT JOIN `tbl_lnkServiceToHostgroup` ON `tbl_service`.`id` = `tbl_lnkServiceToHostgroup`.`idMaster` 
									      WHERE $strDomainWhere1
									      AND `tbl_lnkServiceToHostgroup`.`idSlave` = $elem
									      AND `service_description` <> '' AND `service_description` IS NOT NULL AND `access_group` IN ($strAccess)";
							$booReturn = $this->myDBClass->getDataArray($strSQLTmp,$arrDataTmp,$intDataTmp);
							if ($booReturn && ($intDataTmp != 0)) {
								foreach ($arrDataTmp AS $elem2) {
									if ($intCounter == 0) {
										$arrTempServ[]   = $elem2['service_description'];
										$arrTempServId[] = $elem2['id'];
									} else if (in_array($elem2['service_description'],$arrServices) && !in_array($elem2['service_description'],$arrTempServ)) {
										$arrTempServ[]   = $elem2['service_description'];
										$arrTempServId[] = $elem2['id'];
									}
								}
							}
							$arrServices   = $arrTempServ;
							$arrServicesId = $arrTempServId;
							$intCounter++;
						}
					}				
					if (count($arrServices) != 0) {
						$strServices   = "'".implode("','",$arrServices)."'";
						$strServicesId = implode(",",$arrServicesId);
						$strSQL = "SELECT `id` AS `key`, `".$strTabField."` AS `value`, `active` FROM `tbl_service` 
								   LEFT JOIN `tbl_lnkServiceToHost` ON `tbl_service`.`id` = `tbl_lnkServiceToHost`.`idMaster` 
								   WHERE $strDomainWhere1
								   AND `tbl_service`.`service_description` IN ($strServices) 
								   AND `tbl_service`.`id` IN ($strServicesId) 
								   AND `".$strTabField."` <> '' AND `".$strTabField."` IS NOT NULL AND `access_group` IN ($strAccess) 
								   GROUP BY `value` 
								   UNION 
								   SELECT `id` AS `key`, `".$strTabField."` AS `value`, `active` FROM `tbl_service` 
								   LEFT JOIN `tbl_lnkServiceToHostgroup` ON `tbl_service`.`id` = `tbl_lnkServiceToHostgroup`.`idMaster` 
								   WHERE $strDomainWhere1
								   AND `tbl_service`.`service_description` IN ($strServices) 
								   AND `tbl_service`.`id` IN ($strServicesId) 
								   AND `".$strTabField."` <> '' AND `".$strTabField."` IS NOT NULL AND `access_group` IN ($strAccess) 
								   GROUP BY `value` 
								   UNION 
								   SELECT `id` AS `key`, `".$strTabField."` AS `value`, `active` FROM `tbl_service` 
								   WHERE $strDomainWhere1
								   AND `host_name`=2 OR  `hostgroup_name`=2
								   AND `".$strTabField."` <> '' AND `".$strTabField."` IS NOT NULL AND `access_group` IN ($strAccess) 
								   GROUP BY `value` ORDER BY `value`";
					} else {
						$strSQL = "";
					}
				}
			} else {
				$strSQL = "";	
			}
		} else if (($strTable == 'tbl_service') && ($intOption == 7)) {
			if (isset($_SESSION['refresh']) && isset($_SESSION['refresh']['se_host'])) {
				$strHostId = $_SESSION['refresh']['se_host'];
				$strSQL  = "SELECT `tbl_service`.`id` AS `key`, `tbl_service`.`".$strTabField."` AS `value`, `tbl_service`.`active` FROM `tbl_service`
							LEFT JOIN `tbl_lnkServiceToHost` ON `tbl_service`.`id` = `tbl_lnkServiceToHost`.`idMaster`
							WHERE $strDomainWhere1 AND `tbl_lnkServiceToHost`.`idSlave` = $strHostId
							AND `".$strTabField."` <> '' AND `".$strTabField."` IS NOT NULL AND `access_group` IN ($strAccess) ORDER BY `".$strTabField."`";
			} else {
				$strSQL = "";	
			}
		} else if (($strTable == 'tbl_service') && ($intOption == 8)) {	
			// Service selection inside Host definition
			$strSQL  = "SELECT `tbl_service`.`id` AS `key`, CONCAT(`tbl_service`.`config_name`, ' - ', `tbl_service`.`service_description`) AS `value`, `active` 
						FROM `tbl_service` WHERE $strDomainWhere1 AND `tbl_service`.`config_name` <> '' 
						AND `tbl_service`.`config_name` IS NOT NULL AND `tbl_service`.`service_description` <> '' AND `tbl_service`.`service_description` IS NOT NULL 
						AND `access_group` IN ($strAccess) ORDER BY `value`";
		} else {
	   		// Common statement
			$strSQL  = "SELECT `id` AS `key`, `".$strTabField."` AS `value`, `config_id`, `active` FROM `".$strTable."` WHERE $strDomainWhere1
				   		AND `".$strTabField."` <> '' AND `".$strTabField."` IS NOT NULL AND `access_group` IN ($strAccess) ORDER BY `".$strTabField."`";
						
		}
		// Process data		
		$booReturn = $this->myDBClass->getDataArray($strSQL,$arrDataRaw,$intDataCount);
		
		if (($booReturn == false) && ($strSQL != "")) $this->strErrorMessage .= $this->myDBClass->strErrorMessage;
		if ($strTable == 'tbl_group') {
			$arrTemp = "";
			$arrTemp['key']   = 0;
			$arrTemp['value'] = translate('Unrestricted access');
			$arrData[] = $arrTemp;
		}		
		if ($booReturn && ($intDataCount != 0)) {		
			foreach ($arrDataRaw AS $elem) {
				$arrData[] = $elem;				
			}
			return(0);
		} else {
			if ($strTable == 'tbl_group') return(0);
			$arrData = array('key' => 0, 'value' => 'no data');
			return(1);
		}
  	}
	///////////////////////////////////////////////////////////////////////////////////////////
  	//  Help function: Get selected data
  	///////////////////////////////////////////////////////////////////////////////////////////
	//  $strLinkTable		-> Link table name
	//  $arrSelect			-> Selected data array
	//  $intOption			-> Option parameter
	//	Return value		-> 0=successful / 1=error
	///////////////////////////////////////////////////////////////////////////////////////////
  	function getSelectedItems($strLinkTable,&$arrSelect,$intOption=0) {
		// Define SQL commands
		if ($intOption == 8) {
			$strSQL = "SELECT * FROM `".$strLinkTable."` WHERE `idSlave`=".$this->dataId;
		} else {
			$strSQL = "SELECT * FROM `".$strLinkTable."` WHERE `idMaster`=".$this->dataId;
		}		
		// Process data
		$booReturn  = $this->myDBClass->getDataArray($strSQL,$arrSelectedRaw,$intDataCount);	
		if ($booReturn == false) $this->strErrorMessage .= $this->myDBClass->strErrorMessage;
		if ($booReturn && ($intDataCount != 0)) {
		
			foreach($arrSelectedRaw AS $elem) {
				// Multi tables
				if ($strLinkTable == 'tbl_lnkServicegroupToService') {
					if (isset($elem['exclude']) && ($elem['exclude'] == 1)) {
						$arrSelect[] = "e".$elem['idSlaveH']."::".$elem['idSlaveHG']."::".$elem['idSlaveS'];
					} else {
						$arrSelect[] = $elem['idSlaveH']."::".$elem['idSlaveHG']."::".$elem['idSlaveS'];
					}
				// Servicedependencies and -escalations
				} else if (($strLinkTable == 'tbl_lnkServicedependencyToService_DS') || 
						   ($strLinkTable == 'tbl_lnkServicedependencyToService_S') ||
						   ($strLinkTable == 'tbl_lnkServiceescalationToService')) {
					if (isset($elem['exclude']) && ($elem['exclude'] == 1)) {
						$arrSelect[] = "e::".$elem['strSlave'];
					} else {
						$arrSelect[] = $elem['strSlave'];
					}	
				// Standard tables
				} else {
					if ($intOption == 8) {
						if (isset($elem['exclude']) && ($elem['exclude'] == 1)) {
							$arrSelect[] = "e".$elem['idMaster'];
						} else {
							$arrSelect[] = $elem['idMaster'];
						}
					} else {
						if (isset($elem['exclude']) && ($elem['exclude'] == 1)) {
							$arrSelect[] = "e".$elem['idSlave'];
						} else {
							$arrSelect[] = $elem['idSlave'];
						}
					}
				}
			}
			return(0);
		} else {
			return(1);
		}
  	}
}
?>
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
// Class: Common install functions
//
///////////////////////////////////////////////////////////////////////////////////////////////
//
// Includes all functions used by the installer
//
// Name: naginstall
//
///////////////////////////////////////////////////////////////////////////////////////////////
class naginstall {
  	// Define class variables
	var $filTemplate = "";		// template file

	///////////////////////////////////////////////////////////////////////////////////////////
  	//  Class constructor
  	///////////////////////////////////////////////////////////////////////////////////////////
  	//
  	//  Activities during class initialization
  	//
  	///////////////////////////////////////////////////////////////////////////////////////////
  	function __construct() {
		  $host;
		  $port;
		  $user;
		  $pass;		 
		  $mysqli;		

  	}
	///////////////////////////////////////////////////////////////////////////////////////////
	//  Function: Parse template
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Parameter:  		$arrTemplate   	Array including template replacements
	//						$strTplFile		Template file
	//						$intMode		Mode (0=admin user/1=NagiosQL user
	//   
  	//  Return values:		none
	//
	///////////////////////////////////////////////////////////////////////////////////////////
	function parseTemplate($arrTemplate,$strTplFile) {
		// Open template file
		if (file_exists($strTplFile) && is_readable($strTplFile)) {
			$strTemplate = "";
			$datTplFile = fopen($strTplFile,'r');
			while (!feof($datTplFile)) {
				$strTemplate .= fgets($datTplFile);
			}
			foreach ($arrTemplate AS $key => $elem) {
				if (substr_count($strTemplate,"{".$key."}") != 0) {
					$strTemplate = str_replace("{".$key."}",$elem,$strTemplate);
				}
			}
			return($strTemplate);
		} else {
			echo "File not found";	
		}
	}
	///////////////////////////////////////////////////////////////////////////////////////////
	//  Function: Connect to database server as administrator
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Parameter:  		strStatusMessage	Array variable for status message
	//						$strErrorMessage	Error string
	//						$intMode			Mode (0=admin user/1=NagiosQL user
	//   
  	//  Return values:		Status variable (0=ok,1=failed)
	//
	///////////////////////////////////////////////////////////////////////////////////////////
	function openAdmDBSrv(&$strStatusMessage,&$strErrorMessage,$intMode=0) {		
		if ($intMode == 1 ) {
			$this->host = $_SESSION['install']['dbserver'];
			$this->port = $_SESSION['install']['dbport'];
			$this->user = $_SESSION['install']['dbuser'];
			$this->pass = $_SESSION['install']['dbpass'];	
		} else {
			$this->host = $_SESSION['install']['dbserver'];
			$this->port = $_SESSION['install']['dbport'];
			$this->user = $_SESSION['install']['admuser'];
			$this->pass = $_SESSION['install']['admpass'];
		}		
		if ($_SESSION['install']['dbtype'] == "mysqli") {
			$intStatus  = 0;
			// Connect to database server
			$this->mysqli = new mysqli($this->host.":".$this->port, $this->user, $this->pass);				
			if ($this->mysqli->connect_error) {
				$strErrorMessage .= translate('Error while connecting to database:')."<br>".$this->mysqli->connect_errno."<br>\n";
				$intStatus = 1;					
			}					
		} else if ($_SESSION['install']['dbtype'] == "pgsql") {
			// Connect to database server
			if ($intMode == 1 ) {
				$resDBSLink = @pg_connect("host=".$_SESSION['install']['dbserver']." port=".$_SESSION['install']['dbport']." user=".$_SESSION['install']['dbuser']." password=".$_SESSION['install']['dbpass']);
			} else {
				$resDBSLink = @pg_connect("host=".$_SESSION['install']['dbserver']." port=".$_SESSION['install']['dbport']." user=".$_SESSION['install']['admuser']." password=".$_SESSION['install']['admpass']);
			}
			if (!$resDBSLink) {
				$strErrorMessage .= translate('Error while connecting to database:')."<br>".pg_last_error()."<br>\n";
				$intStatus = 1;
			}
		} else {
			$strErrorMessage .= translate("Database type not defined!")." (".$_SESSION['install']['dbtype'].")<br>\n";
			$strStatusMessage = "<span class=\"red\">".translate("failed")."</span>";
			return(1);	
		}
		if ($intStatus == 0) {
			$strStatusMessage = "<span class=\"green\">".translate("passed")."</span>";
			return(0);
		} else {
			$strStatusMessage = "<span class=\"red\">".translate("failed")."</span>";
			return(1);
		}
	}
	///////////////////////////////////////////////////////////////////////////////////////////
	//  Function: Connect to database as administrator
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Parameter:  		strStatusMessage	Array variable for status message
	//						$strErrorMessage	Error string
	//						$intMode			Mode (0=admin user/1=NagiosQL user
	//   
  	//  Return values:		Status variable (0=ok,1=failed)
	//
	///////////////////////////////////////////////////////////////////////////////////////////
	function openDatabase(&$strStatusMessage,&$strErrorMessage,$intMode=0) {   	
		if ($_SESSION['install']['dbtype'] == "mysqli") {
			$intStatus  = 0;			
			// Connect to database	
			$resDBId = $this->mysqli->select_db($_SESSION['install']['dbname']);	
			if (!$resDBId) {
				$strErrorMessage .= translate('Error while connecting to database:')."<br>".mysqli_error($this->mysqli)."<br>\n";				
				$intStatus = 1;						
			}
		} else if ($_SESSION['install']['dbtype'] == "pgsql") {
			// Connect to database
			if ($intMode == 1 ) {
				$resDBSLink = @pg_connect("host=".$_SESSION['install']['dbserver']." port=".$_SESSION['install']['dbport']." dbname=".$_SESSION['install']['dbname']." user=".$_SESSION['install']['dbuser']." password=".$_SESSION['install']['dbpass']);
			} else {
				$resDBSLink = @pg_connect("host=".$_SESSION['install']['dbserver']." port=".$_SESSION['install']['dbport']." dbname=".$_SESSION['install']['dbname']." user=".$_SESSION['install']['admuser']." password=".$_SESSION['install']['admpass']);
			}
			if (!$resDBSLink) {
				$strErrorMessage .= translate('Error while connecting to database:')."<br>".pg_last_error()."<br>\n";
				$intStatus = 1;
			}
		} else {
			$strErrorMessage .= translate("Database type not defined!")." (".$_SESSION['install']['dbtype'].")<br>\n";
			$strStatusMessage = "<span class=\"red\">".translate("failed")."</span>";			
			return(1);	
		}
		if ($intStatus == 0) {
			$strStatusMessage = "<span class=\"green\">".translate("passed")."</span>";
			return(0);			
		} else {
			$strStatusMessage = "<span class=\"red\">".translate("failed")."</span>";			
			return(1);			
		}
		
		
	}
	///////////////////////////////////////////////////////////////////////////////////////////
	//  Function: Check database version
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Parameter:  		strStatusMessage	Array variable for status message
	//						$strErrorMessage	Error string
	//						$strVersion			Database version
	//   
  	//  Return values:		Status variable (0=ok,1=failed)
	//
	///////////////////////////////////////////////////////////////////////////////////////////
	function checkDBVersion(&$strStatusMessage,&$strErrorMessage,&$setVersion) {
		// Read version string from DB
		if ($_SESSION['install']['dbtype'] == "mysqli") {
			$q = $this->mysqli->query("SHOW VARIABLES LIKE 'version'");
			while ($row = $q->fetch_array()) {
				$setVersion = $row['Value'];
			}			
			$strDBError = mysqli_error($this->mysqli);
			$intVersion = version_compare($setVersion,"4.1.0");				
		} else if ($_SESSION['install']['dbtype'] == "pgsql") {
			$setVersion = @pg_fetch_result(@pg_query("SHOW VARIABLES LIKE 'version'"),0,1);
			$strDBError = pg_last_error();
			$intVersion = version_compare($setVersion,"4.1.0");
		} else {
			$strErrorMessage .= translate("Database type not defined!")." (".$_SESSION['install']['dbtype'].")<br>\n";
			$strStatusMessage = "<span class=\"red\">".translate("failed")."</span>";
			return(1);	
		}
		if ($strDBError == "") {
			// Is the currrent version supported?
			if ($intVersion >=0) {
				$strStatusMessage = "<span class=\"green\">".translate("supported")."</span>";
				return(0);				
			} else {
				$strStatusMessage = "<span class=\"red\">".translate("not supported")."</span>";
				return(1);				
			}
		} else {
			$strErrorMessage .=	$strDBError."<br>\n";
			$strStatusMessage = "<span class=\"red\">".translate("failed")."</span>";
			$setVersion		  = "unknown";
			return(1);
		}		
	}
	///////////////////////////////////////////////////////////////////////////////////////////
	//  Function: Check NagiosQL version
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Parameter:  		strStatusMessage	Array variable for status message
	//						$strErrorMessage	Error string
	//						$arrUpdate			Array including all update files
	//						$setVersion			Current NagiosQL version string 
	//   
  	//  Return values:		Status variable (0=ok,1=failed)
	//
	///////////////////////////////////////////////////////////////////////////////////////////
	function checkQLVersion(&$strStatusMessage,&$strErrorMessage,&$arrUpdate,&$setVersion) {
		// Read version string from DB
		if ($_SESSION['install']['dbtype'] == "mysqli") {
			$setVersion = @mysql_result(@mysql_query("SELECT `value` FROM `tbl_settings` WHERE `category`='db' AND `name`='version'"),0,0);
			$strDBError = mysql_error();
		} else if ($_SESSION['install']['dbtype'] == "pgsql") {
			$setVersion = @pg_fetch_result(@pg_query("SELECT `value` FROM `tbl_settings` WHERE `category`='db' AND `name`='version'"),0,0);
			$strDBError = pg_last_error();
		} else {
			$strErrorMessage .= translate("Database type not defined!")." (".$_SESSION['install']['dbtype'].")<br>\n";
			$strStatusMessage = "<span class=\"red\">".translate("failed")."</span>";
			return(1);	
		}
		// Process result
		if (($strDBError == "") && ($setVersion != "")) {
			// NagiosQL version supported?
			$intVersionError = 0;
			switch($setVersion) {
				case '3.0.0': 	$arrUpdate[] = "sql/update_300_310.sql";
								$arrUpdate[] = "sql/update_310_320.sql";
								break;
				case '3.0.1': 	$arrUpdate[] = "sql/update_302_303.sql";
								$arrUpdate[] = "sql/update_304_310.sql";
								$arrUpdate[] = "sql/update_310_320.sql";
								break;
				case '3.0.2': 	$arrUpdate[] = "sql/update_302_303.sql";
								$arrUpdate[] = "sql/update_304_310.sql";
								$arrUpdate[] = "sql/update_310_320.sql";
								break;	
				case '3.0.3': 	$arrUpdate[] = "sql/update_304_310.sql";
								$arrUpdate[] = "sql/update_310_320.sql";
								break;	
				case '3.0.4': 	$arrUpdate[] = "sql/update_304_310.sql";
								$arrUpdate[] = "sql/update_310_320.sql";
								break;	
				case '3.1.0': 	$arrUpdate[] = "sql/update_310_320.sql";
								break;	
				case '3.1.1': 	$arrUpdate[] = "sql/update_311_320.sql";
								break;
				case '3.2.0': 	$intVersionError = 2;
								break;
				default:		$intVersionError = 1;
								break;
			}
			if ($intVersionError == 0) {
				$strStatusMessage = "<span class=\"green\">".translate("supported")."</span> (".$setVersion.")";
				return(0);
			} else if ($intVersionError == 2) {
				$strErrorMessage .=	translate("Your NagiosQL installation is up to date - no further actions are needed!")."<br>\n";
				$strStatusMessage = "<span class=\"green\">".translate("up-to-date")."</span> (".$setVersion.")";
				return(1);
			} else {
				$strErrorMessage .=	translate("Updates to NagiosQL 3.2 and above are only supported from NagiosQL 3.0.0 and above!")."<br>\n";
				$strStatusMessage = "<span class=\"red\">".translate("failed")."</span> (".$setVersion.")";
				return(1);
			}
		} else {
			$strErrorMessage .=	translate("Error while selecting settings table.")."<br>\n";
			$strErrorMessage .=	$strDBError."<br>\n";
			$strErrorMessage .=	translate("Updates to NagiosQL 3.2 and above are only supported from NagiosQL 3.0.0 and above!")."<br>\n";
			$strStatusMessage = "<span class=\"red\">".translate("failed")."</span>";
			return(1);
		}
	}
	///////////////////////////////////////////////////////////////////////////////////////////
	//  Function: Delete old NagiosQL database
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Parameter:  		strStatusMessage	Array variable for status message
	//						$strErrorMessage	Error string
	//   
  	//  Return values:		Status variable (0=ok,1=failed)
	//
	///////////////////////////////////////////////////////////////////////////////////////////
	function dropDB(&$strStatusMessage,&$strErrorMessage) {
		// Drop database
		if ($_SESSION['install']['dbtype'] == "mysqli") {
			$booReturn = $this->mysqli->query("DROP DATABASE `".$_SESSION['install']['dbname']."`");
			$strDBError = mysqli_error($this->mysqli);			
		} else if ($_SESSION['install']['dbtype'] == "pgsql") {
			$setVersion = @pg_query("DROP DATABASE `".$_SESSION['install']['dbname']."`");
			$strDBError = pg_last_error();
		} else {
			$strErrorMessage .= translate("Database type not defined!")." (".$_SESSION['install']['dbtype'].")<br>\n";
			$strStatusMessage = "<span class=\"red\">".translate("failed")."</span>";
			return(1);	
		}
		if ($booReturn) {
			$strStatusMessage = "<span class=\"green\">".translate("done")."</span> (".$_SESSION['install']['dbname'].")";
			return(0);
		} else {
			$strErrorMessage .=	$strDBError."<br>\n";
			$strStatusMessage = "<span class=\"red\">".translate("failed")."</span> (".$_SESSION['install']['dbname'].")";
			return(1);
		}
	}
	///////////////////////////////////////////////////////////////////////////////////////////
	//  Function: Create NagiosQL database
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Parameter:  		strStatusMessage	Array variable for status message
	//						$strErrorMessage	Error string
	//   
  	//  Return values:		Status variable (0=ok,1=failed)
	//
	///////////////////////////////////////////////////////////////////////////////////////////
	function createDB(&$strStatusMessage,&$strErrorMessage) {
		// Create database
		if ($_SESSION['install']['dbtype'] == "mysqli") {
			$booReturn = $this->mysqli->query("CREATE DATABASE `".$_SESSION['install']['dbname']."` DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_unicode_ci");
			$strDBError = mysqli_error($this->mysqli);		
		} else if ($_SESSION['install']['dbtype'] == "pgsql") {
			$setVersion = @pg_query("CREATE DATABASE `".$_SESSION['install']['dbname']."` DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_unicode_ci");
			$strDBError = pg_last_error();
		} else {
			$strErrorMessage .= translate("Database type not defined!")." (".$_SESSION['install']['dbtype'].")<br>\n";
			$strStatusMessage = "<span class=\"red\">".translate("failed")."</span>";
			return(1);	
		}
		if ($booReturn) {
			$strStatusMessage = "<span class=\"green\">".translate("done")."</span> (".$_SESSION['install']['dbname'].")";
			return(0);
		} else {
			$strErrorMessage .=	$strDBError."<br>\n";
			$strStatusMessage = "<span class=\"red\">".translate("failed")."</span> (".$_SESSION['install']['dbname'].")";
			return(1);
		}
	}	
	///////////////////////////////////////////////////////////////////////////////////////////
	//  Function: Grant user to database
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Parameter:  		strStatusMessage	Array variable for status message
	//						$strErrorMessage	Error string
	//   
  	//  Return values:		Status variable (0=ok,1=failed)
	//
	///////////////////////////////////////////////////////////////////////////////////////////
	function grantDBUser(&$strStatusMessage,&$strErrorMessage) {
		// Grant user
		if ($_SESSION['install']['dbtype'] == "mysqli") {
			// does the user exist?
			$intUserError = 0;
			$resQuery  = $this->mysqli->query("FLUSH PRIVILEGES");
			$resQuery1  = $this->mysqli->query("SELECT * FROM `mysql`.`user` WHERE `Host`='".$_SESSION['install']['localsrv']."' AND `User`='".$_SESSION['install']['dbuser']."'");
			if ($resQuery1 && (mysqli_num_rows($resQuery1) == 0)) {
				$resQuery2 = $this->mysqli->query("CREATE USER '".$_SESSION['install']['dbuser']."'@'".$_SESSION['install']['localsrv']."' IDENTIFIED BY '".$_SESSION['install']['dbpass']."'");
				if (!$resQuery2) {
					$intUserError 	= 1;
					$strDBError 	= mysqli_error($this->mysqli);
				}
			} else if (mysqli_error($this->mysqli) == ""){
				$intUserError = 2;
			} else {
				$intUserError 	= 1;
				$strDBError 	= mysqli_error($this->mysqli);
			}
			if ($intUserError != 1) {
				$resQuery = $this->mysqli->query("FLUSH PRIVILEGES");
				$resQuery = $this->mysqli->query("GRANT SELECT, INSERT, UPDATE, DELETE, LOCK TABLES ON `".$_SESSION['install']['dbname']."`.* TO '".$_SESSION['install']['dbuser']."'@'".$_SESSION['install']['localsrv']."'");
				if (!$resQuery) {
					$intUserError 	= 1;
					$strDBError 	= mysqli_error($this->mysqli);
				}
				$resQuery = $this->mysqli->query("FLUSH PRIVILEGES");
			}
		} else if ($_SESSION['install']['dbtype'] == "pgsql") {
			// does the user exist?
			$intUserError = 0;
			$resQuery  = @pg_query("FLUSH PRIVILEGES");
			$resQuery1 = @pg_query("SELECT * FROM `mysql`.`user WHERE `Host`='".$_SESSION['install']['localsrv']."' AND `User`='".$_SESSION['install']['dbuser']."'");
			if ($resQuery1 && (pg_num_rows($resQuery) != 0)) {
				$resQuery2 = @pg_query("CREATE USER '".$_SESSION['install']['dbuser']."'@'".$_SESSION['install']['localsrv']."' IDENTIFIED BY '".$_SESSION['install']['dbpass']."'");
				if (!$resQuery2) {
					$intUserError 	= 1;
					$strDBError 	= pg_last_error();
				}
			} else if (mysql_error() == ""){
				$intUserError = 2;
			} else {
				$intUserError 	= 1;
				$strDBError 	= pg_last_error();
			}
			if ($intUserError != 1) {
				$resQuery = @pg_query("FLUSH PRIVILEGES");
				$resQuery = @pg_query("GRANT SELECT, INSERT, UPDATE, DELETE, LOCK TABLES ON `".$_SESSION['install']['dbname']."`.* TO '".$_SESSION['install']['dbuser']."'@'".$_SESSION['install']['localsrv']."'");
				if (!$resQuery) {
					$intUserError 	= 1;
					$strDBError 	= pg_last_error();
				}
				$resQuery = @pg_query("FLUSH PRIVILEGES");
			}
		} else {
			$strErrorMessage .= translate("Database type not defined!")." (".$_SESSION['install']['dbtype'].")<br>\n";
			$strStatusMessage = "<span class=\"red\">".translate("failed")."</span>";
			return(1);	
		}
		if ($intUserError != 1) {
			if ($intUserError == 2) {
				$strStatusMessage = "<span class=\"green\">".translate("done")."</span> (".translate("Only added rights to existing user").": ".$_SESSION['install']['dbuser'].")";
			} else {
				$strStatusMessage = "<span class=\"green\">".translate("done")."</span>";
			}
			return(0);
		} else {
			$strErrorMessage .=	$strDBError."<br>\n";
			$strStatusMessage = "<span class=\"red\">".translate("failed")."</span>";
			return(1);
		}
	}	
	///////////////////////////////////////////////////////////////////////////////////////////
	//  Function: Update NagiosQL database
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Parameter:  		strStatusMessage	Array variable for status message
	//						$strErrorMessage	Error string
	//						$arrUpdate			Array including all update files
	//   
  	//  Return values:		Status variable (0=ok,1=failed)
	//
	///////////////////////////////////////////////////////////////////////////////////////////
	function updateQLDB(&$strStatusMessage,&$strErrorMessage,$arrUpdate) {
		if (is_array($arrUpdate) && (count($arrUpdate) != 0)) {
			$intUpdateOk 	= 0;
			$intUpdateError = 0;
			foreach($arrUpdate AS $elem) {
				if ($intUpdateError == 0) {
					if (is_readable($elem)) {	
						$filSqlNew = fopen($elem,"r");
						if ($filSqlNew) {
							$strSqlCommand = "";
							$intSQLError   = 0;
							$intLineCount  = 0;
							if ($_SESSION['install']['dbtype'] == "mysqli") $booReturn = $this->mysqli->query("SET NAMES `utf8`");
							if ($_SESSION['install']['dbtype'] == "pgsql") $booReturn = @pg_query("SET NAMES `utf8`");
							while (!feof($filSqlNew)) {
								$strLine = fgets($filSqlNew);
								$strLine = trim($strLine);								
								if ($intSQLError == 1)  continue;			// skip if an error was found
								$intLineCount++;
								if ($strLine     == "") continue; 			// skip empty lines
								if (substr($strLine,0,2) == "--") continue; // skip comment lines
								$strSqlCommand .= $strLine;								
								if (substr($strSqlCommand,-1) == ";") {
									if ($_SESSION['install']['dbtype'] == "mysqli") $booReturn = $this->mysqli->query($strSqlCommand);
									if ($_SESSION['install']['dbtype'] == "pgsql") $booReturn = @pg_query($strSqlCommand);
									if (!$booReturn) {
										$intSQLError = 1;
										if ($_SESSION['install']['dbtype'] == "mysqli") $strErrorMessage .= mysqli_error($this->mysqli)."<br>\n";
										if ($_SESSION['install']['dbtype'] == "pgsql") $strErrorMessage .= pg_last_error()."<br>\n";
										$intError = 1;
									}
									$strSqlCommand = "";
								}
							}
							if ($intSQLError == 0) {
								$intUpdateOk++;
							} else {
								$strStatusMessage = "<span class=\"red\">".translate("failed")."</span> (Line: ".$intLineCount." in file: ".$elem.")";
								$intUpdateError++;
							}
						} else {
							$strStatusMessage = "<span class=\"red\">".translate("failed")."</span>";
							$strErrorMessage .=	translate("SQL file is not readable or empty")." (".$elem.")<br>\n";
							$intUpdateError++;
						}
					} else {
						$strStatusMessage = "<span class=\"red\">".translate("failed")."</span>";
						$strErrorMessage .=	translate("SQL file is not readable or empty")." (".$elem.")<br>\n";
						$intUpdateError++;
					}
				}					
			} 
			if ($intUpdateError == 0) {
				$strStatusMessage = "<span class=\"green\">".translate("done")."</span>";
				return(0);
			} else {
				return(1);
			}
		} else {
			$strStatusMessage = "<span class=\"red\">".translate("failed")."</span>";
			$strErrorMessage .=	translate("No SQL update files available")."<br>\n";
			return(1);
		}
	}
	///////////////////////////////////////////////////////////////////////////////////////////
	//  Function: Create NagiosQL administrator
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Parameter:  		strStatusMessage	Array variable for status message
	//						$strErrorMessage	Error string
	//   
  	//  Return values:		Status variable (0=ok,1=failed)
	//
	///////////////////////////////////////////////////////////////////////////////////////////
	function createNQLAdmin(&$strStatusMessage,&$strErrorMessage) {
		// Create admin user
		$strSQL  = "SELECT `id` FROM `tbl_language` WHERE `locale`='".$_SESSION['install']['locale']."'";		
		$q = $this->mysqli->query($strSQL);
			while ($row = $q->fetch_array()) {
				$intLang = $row['id'];
			}		
		//$intLang = @mysql_result(@mysql_query($strSQL),0,0)+0;
		if ($intLang == 0) $intLang = 1;
		$strSQL  = "INSERT INTO `tbl_user` (`id`, `username`, `alias`, `password`, `admin_enable`, `wsauth`, `active`, `nodelete`, `language`, `domain`, `last_login`, `last_modified`)
					VALUES (1, '".$_SESSION['install']['qluser']."', 'Administrator', md5('".$_SESSION['install']['qlpass']."'), '1', '0', '1', '1', '".$intLang."', '1', '', NOW());";
		if ($_SESSION['install']['dbtype'] == "mysqli") {
			$booReturn  = $this->mysqli->query($strSQL);
			$strDBError = mysqli_error($this->mysqli);
		} else if ($_SESSION['install']['dbtype'] == "pgsql") {
			$setVersion = @pg_query($strSQL);
			$strDBError = pg_last_error();
		} else {
			$strErrorMessage .= translate("Database type not defined!")." (".$_SESSION['install']['dbtype'].")<br>\n";
			$strStatusMessage = "<span class=\"red\">".translate("failed")."</span>";
			return(1);	
		}
		if ($booReturn) {
			$strStatusMessage = "<span class=\"green\">".translate("done")."</span>";
			return(0);
		} else {
			$strErrorMessage .=	$strDBError."<br>\n";
			$strStatusMessage = "<span class=\"red\">".translate("failed")."</span>";
			return(1);
		}
	}	
	///////////////////////////////////////////////////////////////////////////////////////////
	//  Function: Update settings database
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Parameter:  		strStatusMessage	Array variable for status message
	//						$strErrorMessage	Error string
	//   
  	//  Return values:		Status variable (0=ok,1=failed)
	//
	///////////////////////////////////////////////////////////////////////////////////////////
	function updateSettingsDB(&$strStatusMessage,&$strErrorMessage) {
		// Checking initial settings
		$arrInitial[] = array('category'=>'db','name'=>'version','value'=>$_SESSION['install']['version']);
		$arrInitial[] = array('category'=>'db','name'=>'type','value'=>$_SESSION['install']['dbtype']);
		foreach ($_SESSION['init_settings'] AS $key=>$elem) {
			if ($key == 'db') continue; // do not store db values to database
			foreach ($elem AS $key2=>$elem2) {
				$arrInitial[] = array('category'=>$key,'name'=>$key2,'value'=>$elem2);
			}
		}
		foreach ($arrInitial AS $elem) {
			$strSQL1 = "SELECT `value` FROM `tbl_settings` WHERE `category`='".$elem['category']."' AND `name`='".$elem['name']."'";
			$strSQL2 = "INSERT INTO `tbl_settings` (`category`, `name`, `value`) VALUES ('".$elem['category']."', '".$elem['name']."', '".$elem['value']."')";
			if ($_SESSION['install']['dbtype'] == "mysqli") {
				$resQuery1 = $this->mysqli->query($strSQL1);
				if ($resQuery1 && (mysqli_num_rows($resQuery1) == 0)) {	
					$resQuery2 = $this->mysqli->query($strSQL2);
					if (!$resQuery2) {
						$strStatusMessage = "<span class=\"red\">".translate("failed")."</span>";
						$strErrorMessage .=	translate("Inserting initial data to settings database has failed:")."<br>".mysql_error()."<br>\n";
						return(1);
					}
				} else if (!$resQuery1) {
					$strStatusMessage = "<span class=\"red\">".translate("failed")."</span>";
					$strErrorMessage .=	translate("Inserting initial data to settings database has failed:")." ".mysql_error()."<br>\n";
					return(1);
				}
			} else if ($_SESSION['install']['dbtype'] == "pgsql") {
				$resQuery1 = @pg_query($strSQL1);
				if ($resQuery1 && (pg_num_rows($resQuery1) == 0)) {	
					$resQuery2 = @pg_query($strSQL2);
					if (!$resQuery2) {
						$strStatusMessage = "<span class=\"red\">".translate("failed")."</span>";
						$strErrorMessage .=	translate("Inserting initial data to settings database has failed:")."<br>".pg_last_error()."<br>\n";
						return(1);
					}
				} else if (!$resQuery1) {
					$strStatusMessage = "<span class=\"red\">".translate("failed")."</span>";
					$strErrorMessage .=	translate("Inserting initial data to settings database has failed:")." ".pg_last_error()."<br>\n";
					return(1);
				}
			}
		}
		// Update some values
		$arrSettings[] 	= array('category'=>'db','name'=>'version','value'=>$_SESSION['install']['version']);
		if (substr_count($_SERVER['SERVER_PROTOCOL'],"HTTPS") != 0) {
			$arrSettings[] = array('category'=>'path','name'=>'protocol','value'=>'https');
		} else {
			$arrSettings[] = array('category'=>'path','name'=>'protocol','value'=>'http');	
		}
		$strBaseURL  	= str_replace("install/install.php","",$_SERVER["PHP_SELF"]);
		$arrSettings[] 	= array('category'=>'path','name'=>'base_url','value'=>$strBaseURL);	
		$strBasePath	= substr(realpath('.'),0,-7);
		$arrSettings[] 	= array('category'=>'path','name'=>'base_path','value'=>$strBasePath);
		$arrSettings[] 	= array('category'=>'data','name'=>'locale','value'=>$_SESSION['install']['locale']);
		foreach ($arrSettings AS $elem) {
			$strSQL	= "UPDATE `tbl_settings` SET `value`='".$elem['value']."' WHERE `category` = '".$elem['category']."' AND `name` = '".$elem['name']."'";
			if ($_SESSION['install']['dbtype'] == "mysqli") {
				$resQuery = $this->mysqli->query($strSQL);
				if (mysqli_error($this->mysqli) != '') {
					$strStatusMessage = "<span class=\"red\">".translate("failed")."</span>";
					$strErrorMessage .=	translate("Inserting initial data to settings database has failed:")." ".mysql_error()."<br>\n";
					return(1);
				}
			} else if ($_SESSION['install']['dbtype'] == "pgsql") {
				$resQuery = @pg_query($strSQL);
				if (pg_last_error() != '') {
					$strStatusMessage = "<span class=\"red\">".translate("failed")."</span>";
					$strErrorMessage .=	translate("Inserting initial data to settings database has failed:")." ".pg_last_error()."<br>\n";
					return(1);
				}
			}
		}
		$strStatusMessage = "<span class=\"green\">".translate("done")."</span>";
		return(0);
	}
	///////////////////////////////////////////////////////////////////////////////////////////
	//  Function: Update settings file
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Parameter:  		strStatusMessage	Array variable for status message
	//						$strErrorMessage	Error string
	//   
  	//  Return values:		Status variable (0=ok,1=failed)
	//
	///////////////////////////////////////////////////////////////////////////////////////////
	function updateSettingsFile(&$strStatusMessage,&$strErrorMessage) {
		// open settings file
		$strBaseURL  = str_replace("install/install.php","",$_SERVER["PHP_SELF"]);
		$strBasePath = substr(realpath('.'),0,-7);
		$strE_ID 	 = error_reporting();
		error_reporting(0);
		$filSettings = fopen($strBasePath."config/settings.php","w");
		error_reporting($strE_ID);
		if ($filSettings) {
			// Write Database Configuration into settings.php
			fwrite($filSettings,"<?php\n");
			fwrite($filSettings,"exit;\n");
			fwrite($filSettings,"?>\n");
			fwrite($filSettings,";///////////////////////////////////////////////////////////////////////////////\n");
			fwrite($filSettings,";\n");
			fwrite($filSettings,"; NagiosQL\n");
			fwrite($filSettings,";\n");
			fwrite($filSettings,";///////////////////////////////////////////////////////////////////////////////\n");
			fwrite($filSettings,";\n");
			fwrite($filSettings,"; Project  : NagiosQL\n");
			fwrite($filSettings,"; Component: Database Configuration\n");
			fwrite($filSettings,"; Website  : http://www.nagiosql.org\n");
			fwrite($filSettings,"; Date     : ".date("F j, Y, g:i a")."\n");
			fwrite($filSettings,"; Version  : ".$_SESSION['install']['version']."\n");
			fwrite($filSettings,";\n");
			fwrite($filSettings,";///////////////////////////////////////////////////////////////////////////////\n");
			fwrite($filSettings,"[db]\n");
			fwrite($filSettings,"type         = ".$_SESSION['install']['dbtype']."\n");
			fwrite($filSettings,"server       = ".$_SESSION['install']['dbserver']."\n");
			fwrite($filSettings,"port         = ".$_SESSION['install']['dbport']."\n");
			fwrite($filSettings,"database     = ".$_SESSION['install']['dbname']."\n");
			fwrite($filSettings,"username     = ".$_SESSION['install']['dbuser']."\n");
			fwrite($filSettings,"password     = ".$_SESSION['install']['dbpass']."\n");
			fwrite($filSettings,"[path]\n");
			fwrite($filSettings,"base_url     = ".$strBaseURL."\n");
			fwrite($filSettings,"base_path    = ".$strBasePath."\n");
			fclose($filSettings);	
			$strStatusMessage = "<span class=\"green\">".translate("done")."</span>";
			return(0);
		} else {
			$strStatusMessage = "<span class=\"red\">".translate("failed")."</span>";
			$strErrorMessage .=	translate("Connot open/write to config/settings.php")."<br>\n";
			return(1);
		}
	}
	///////////////////////////////////////////////////////////////////////////////////////////
	//  Function: Update settings database
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Parameter:  		strStatusMessage	Array variable for status message
	//						$strErrorMessage	Error string
	//   
  	//  Return values:		Status variable (0=ok,1=failed)
	//
	///////////////////////////////////////////////////////////////////////////////////////////
	function updateQLpath(&$strStatusMessage,&$strErrorMessage) {
		// Update configuration target database
		$strNagiosQLpath	= str_replace("//","/",$_SESSION['install']['qlpath']."/");
		$strNagiosPath		= str_replace("//","/",$_SESSION['install']['nagpath']."/");
		$strSQL = "UPDATE `tbl_configtarget` SET 
					`basedir`='".$strNagiosQLpath."', 
					`hostconfig`='".$strNagiosQLpath."hosts/',
					`serviceconfig`='".$strNagiosQLpath."services/',
					`backupdir`='".$strNagiosQLpath."backup/',
					`hostbackup`='".$strNagiosQLpath."backup/hosts/',
					`servicebackup`='".$strNagiosQLpath."backup/services/',
					`nagiosbasedir`='".$strNagiosPath."',
					`importdir`='".$strNagiosPath."objects/',
					`conffile`='".$strNagiosPath."nagios.cfg',
					`last_modified`=NOW()
				   WHERE `target`='localhost'";
		if ($_SESSION['install']['dbtype'] == "mysqli") {
			$resQuery1 = $this->mysqli->query($strSQL);
			if (!$resQuery1) {	
				$strStatusMessage = "<span class=\"red\">".translate("failed")."</span>";
				$strErrorMessage .=	translate("Inserting path data to database has failed:")." ".mysqli_error($this->mysqli)."<br>\n";
				return(1);
			}
		} else if ($_SESSION['install']['dbtype'] == "pgsql") {
			$resQuery1 = @pg_query($strSQL);
			if (!$resQuery1) {
				$strStatusMessage = "<span class=\"red\">".translate("failed")."</span>";
				$strErrorMessage .=	translate("Inserting path data to database has failed:")." ".pg_last_error()."<br>\n";
				return(1);
			}
		}
		// Create real paths
		if ($_SESSION['install']['createpath'] == 1) {
			if (is_writable($strNagiosQLpath) && is_dir($strNagiosQLpath) && is_executable($strNagiosQLpath)) {
				if (!file_exists($strNagiosQLpath."hosts")) 			mkdir($strNagiosQLpath."hosts",0755);
				if (!file_exists($strNagiosQLpath."services")) 			mkdir($strNagiosQLpath."services",0755);
				if (!file_exists($strNagiosQLpath."backup")) 			mkdir($strNagiosQLpath."backup",0755);
				if (!file_exists($strNagiosQLpath."backup/hosts")) 		mkdir($strNagiosQLpath."backup/hosts",0755);
				if (!file_exists($strNagiosQLpath."backup/services")) 	mkdir($strNagiosQLpath."backup/services",0755);
				$strStatusMessage = "<span class=\"green\">".translate("done")."</span> (".translate("Check the permissions of the created paths!").")";
				return(0);
			} else {
				$strStatusMessage = "<span class=\"red\">".translate("failed")."</span>";
				$strErrorMessage .=	translate("NagiosQL config path is not writeable - only database values updated")."<br>\n";
				return(1);
			}
		}
		$strStatusMessage = "<span class=\"green\">".translate("done")."</span>";
		return(0);
	}
	
	
	
	
	
	
	///////////////////////////////////////////////////////////////////////////////////////////
	//  Function: Converting NagiosQL database to utf-8
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Parameter:  		strStatusMessage	Array variable for status message
	//						$strErrorMessage	Error string
	//   
  	//  Return values:		Status variable (0=ok,1=failed)
	//
	///////////////////////////////////////////////////////////////////////////////////////////
	function convQLDB(&$strStatusMessage,&$strErrorMessage) {
		// Read version string from DB
		if ($_SESSION['install']['dbtype'] == "mysqli") {
			$resQuery   = @mysql_query("ALTER DATABASE `".$_SESSION['install']['dbname']."` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci");
			$strDBError = mysql_error();
		} else if ($_SESSION['install']['dbtype'] == "pgsql") {
			$resQuery   = @pg_query("ALTER DATABASE `".$_SESSION['install']['dbname']."` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci");
			$strDBError = pg_last_error();
		} else {
			$strErrorMessage .= translate("Database type not defined!")." (".$_SESSION['install']['dbtype'].")<br>\n";
			$strStatusMessage = "<span class=\"red\">".translate("failed")."</span>";
			return(1);	
		}
		if ($strDBError == "") {
			$strStatusMessage = "<span class=\"green\">".translate("done")."</span>";
			return(0);
		} else {
			$strErrorMessage .= translate("Database errors while converting to utf-8:")."<br>".$strDBError."<br>\n";
			$strStatusMessage = "<span class=\"red\">".translate("failed")."</span>";
			return(1);
		}
	}
	///////////////////////////////////////////////////////////////////////////////////////////
	//  Function: Converting NagiosQL database tables to utf-8
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Parameter:  		strStatusMessage	Array variable for status message
	//						$strErrorMessage	Error string
	//   
  	//  Return values:		Status variable (0=ok,1=failed)
	//
	///////////////////////////////////////////////////////////////////////////////////////////
	function convQLDBTables(&$strStatusMessage,&$strErrorMessage) {
		// Read version string from DB
		if ($_SESSION['install']['dbtype'] == "mysqli") {
			$resQuery  = @mysql_query("SHOW TABLES FROM `".$_SESSION['install']['dbname']);
			$strDBError = mysql_error();
			if ($resQuery && ($strDBError == "")) {
				while ($elem = mysql_fetch_row($resQuery)) {
					if ($strDBError != "") continue;
					$resQueryTable  = @mysql_query("ALTER TABLE `".$elem[0]."` DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
					$strDBError    .= mysql_error();
				}
			}
		} else if ($_SESSION['install']['dbtype'] == "pgsql") {
			$resQuery  = @pg_query("SHOW TABLES FROM `".$_SESSION['install']['dbname']);
			$strDBError = pg_last_error();
			if ($resQuery && ($strDBError == "")) {
				while ($elem = pg_fetch_row($resQuery)) {
					if ($strDBError != "") continue;
					$resQueryTable  = @pg_query("ALTER TABLE `".$elem[0]."` DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
					$strDBError    .= pg_last_error();
				}
			}
		} else {
			$strErrorMessage .= translate("Database type not defined!")." (".$_SESSION['install']['dbtype'].")<br>\n";
			$strStatusMessage = "<span class=\"red\">".translate("failed")."</span>";
			return(1);	
		}
		if ($strDBError == "") {
			$strStatusMessage = "<span class=\"green\">".translate("done")."</span>";
			return(0);
		} else {
			$strErrorMessage .= translate("Database errors while converting to utf-8:")."<br>".$strDBError."<br>\n";
			$strStatusMessage = "<span class=\"red\">".translate("failed")."</span>";
			return(1);
		}
	}
	///////////////////////////////////////////////////////////////////////////////////////////
	//  Function: Converting NagiosQL database tables to utf-8
	///////////////////////////////////////////////////////////////////////////////////////////
	//
	//  Parameter:  		strStatusMessage	Array variable for status message
	//						$strErrorMessage	Error string
	//   
  	//  Return values:		Status variable (0=ok,1=failed)
	//
	///////////////////////////////////////////////////////////////////////////////////////////
	function convQLDBFields(&$strStatusMessage,&$strErrorMessage) {
		// Read version string from DB
		if ($_SESSION['install']['dbtype'] == "mysqli") {
			$resQuery  = @mysql_query("SHOW TABLES FROM `".$_SESSION['install']['dbname']);
			$strDBError = mysql_error();
			if ($resQuery && ($strDBError == "")) {
				while ($elem = mysql_fetch_row($resQuery)) {
					if ($strDBError != "") continue;
					$resQueryTable  = @mysql_query("SHOW FULL FIELDS FROM `".$elem[0]."` WHERE (`Type` LIKE '%varchar%' OR `Type` LIKE '%enum%' 
													OR `Type` LIKE '%text%') AND Collation <> 'utf8_unicode_ci'");
					$strDBError = mysql_error();
					if ($resQueryTable && ($strDBError == "")) {
						while ($elem2 = mysql_fetch_row($resQueryTable)) {
							if ($strDBError != "") continue;
							if (($elem2[5] === NULL) && ($elem2[3] == 'YES')){
								$strDefault = "DEFAULT NULL";
							} else if ($elem2[5] != '') {
								$strDefault = "DEFAULT '".$elem2[5]."'";
							} else {
								$strDefault = "";
							}
							if ($elem2[3] == 'YES') { $strNull = 'NULL'; } else { $strNull = 'NOT NULL'; }
							$strSQL = "ALTER TABLE `".$elem[0]."` CHANGE `".$elem2[0]."` `".$elem2[0]."` ".$elem2[1]." CHARACTER SET 'utf8' 
									   COLLATE 'utf8_unicode_ci' $strNull $strDefault";
							$resQueryField = @mysql_query($strSQL);
							$strMySQLError = mysql_error();
							if ($strMySQLError != "") {
								if (substr_count($strMySQLError,"Specified key was too long") == 0) {
									$strDBError .= "Table:".$elem[0]." - Field: ".$elem2[0]." ".mysql_error();
								}						
							}
						}
					}
				}
			}
		} else if ($_SESSION['install']['dbtype'] == "pgsql") {
			$resQuery  = @pg_query("SHOW TABLES FROM `".$_SESSION['install']['dbname']);
			$strDBError = pg_last_error();
			if ($resQuery && ($strDBError == "")) {
				while ($elem = pg_fetch_row($resQuery)) {
					if ($strDBError != "") continue;
					$resQueryTable  = @pg_query("SHOW FULL FIELDS FROM `".$elem[0]."` WHERE `Type` LIKE '%varchar%' AND Collation <> 'utf8_unicode_ci'");
					$strDBError = pg_last_error();
					if ($resQueryTable && ($strDBError == "")) {
						while ($elem2 = pg_fetch_row($resQueryTable)) {
							if (($elem2[5] === NULL) && ($elem2[3] == 'YES')){
								$strDefault = "DEFAULT NULL";
							} else if ($elem2[5] != '') {
								$strDefault = "DEFAULT '".$elem2[5]."'";
							} else {
								$strDefault = "";
							}
							if ($elem2[3] == 'YES') { $strNull = 'NULL'; } else { $strNull = 'NOT NULL'; }
							$strSQL = "ALTER TABLE `".$elem[0]."` CHANGE `".$elem2[0]."` `".$elem2[0]."` ".$elem2[1]." CHARACTER SET 'utf8' 
									   COLLATE 'utf8_unicode_ci' $strNull $strDefault";
							if ($strDBError != "") continue;
							$resQueryField = @pg_query($strSQL);
							$strDBError   .= pg_last_error();
						}
					}
					$strDBError .= pg_last_error();
				}
			}
		} else {
			$strErrorMessage .= translate("Database type not defined!")." (".$_SESSION['install']['dbtype'].")<br>\n";
			$strStatusMessage = "<span class=\"red\">".translate("failed")."</span>";
			return(1);	
		}
		if ($strDBError == "") {
			$strStatusMessage = "<span class=\"green\">".translate("done")."</span>";
			return(0);
		} else {
			$strErrorMessage .= translate("Database errors while converting to utf-8:")."<br>".$strDBError."<br>\n";
			$strStatusMessage = "<span class=\"red\">".translate("failed")."</span>";
			return(1);
		}
	}
}
?>
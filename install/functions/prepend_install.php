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
error_reporting(E_ALL);
//
// Define common variables
// =======================
$strErrorMessage	= "";  // All error messages (red)
$strInfoMessage		= "";  // All information messages (green)
//

// Include external function/class files
// =====================================
include("functions/install_class.php");
//
// Initialize class
// ================
$myInstClass = new naginstall;
?>
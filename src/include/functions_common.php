<?php
/*
	*********************************************************************
	* -> www.phplogcon.org <-											*
	* -----------------------------------------------------------------	*
	* Common needed functions											*
	*																	*
	* -> 		*
	*																	*
	* All directives are explained within this file						*
	*
	* Copyright (C) 2008 Adiscon GmbH.
	*
	* This file is part of phpLogCon.
	*
	* PhpLogCon is free software: you can redistribute it and/or modify
	* it under the terms of the GNU General Public License as published by
	* the Free Software Foundation, either version 3 of the License, or
	* (at your option) any later version.
	*
	* PhpLogCon is distributed in the hope that it will be useful,
	* but WITHOUT ANY WARRANTY; without even the implied warranty of
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	* GNU General Public License for more details.
	*
	* You should have received a copy of the GNU General Public License
	* along with phpLogCon. If not, see <http://www.gnu.org/licenses/>.
	*
	* A copy of the GPL can be found in the file "COPYING" in this
	* distribution.
	*********************************************************************
*/

// --- Avoid directly accessing this file! 
if ( !defined('IN_PHPLOGCON') )
{
	die('Hacking attempt');
	exit;
}
// --- 

// --- Basic Includes
include_once($gl_root_path . 'include/constants_general.php');
include_once($gl_root_path . 'include/constants_logstream.php');

include_once($gl_root_path . 'classes/class_template.php');
include_once($gl_root_path . 'include/functions_themes.php');
include_once($gl_root_path . 'include/functions_db.php');
include_once($gl_root_path . 'include/functions_config.php');
// --- 

// --- Define Basic vars
$RUNMODE = RUNMODE_WEBSERVER;
$DEBUGMODE = DEBUG_INFO;

// --- Change some runtime variables
// Disable ARGV setting @webserver!
@ini_set( "register_argc_argv", "Off" );

// Enable error tracking
@ini_set( "track_errors", "On" );
// --- 

// Default language
$LANG_EN = "en";	// Used for fallback
$LANG = "en";		// Default language

// Default Template vars
$content['BUILDNUMBER'] = "2.5.6";
$content['TITLE'] = "phpLogCon :: Release " . $content['BUILDNUMBER'];	// Default page title 
$content['BASEPATH'] = $gl_root_path;
$content['SHOW_DONATEBUTTON'] = true; // Default = true!
$content['EXTRA_METATAGS'] = "";
$content['EXTRA_JAVASCRIPT'] = "";
$content['EXTRA_STYLESHEET'] = "";
// --- 

// --- Check PHP Version! If lower the 5, phplogcon will not work proberly!
$myPhpVer = phpversion();
$myPhpVerArray = explode('.', $myPhpVer);
if ( $myPhpVerArray[0] < 5 )
	DieWithErrorMsg( 'Error, the PHP Version on this Server does not meet the installation requirements.<br> <A HREF="http://www.php.net"><B>PHP5</B></A> or higher is needed. Current installed Version is: <B>' . $myPhpVer . '</B>');
// ---

function InitBasicPhpLogCon()
{
	// Needed to make global
	global $gl_root_path, $content;

	// Check RunMode first!
	CheckAndSetRunMode();

	// Set the default line sep
	SetLineBreakVar();

	// Start the PHP Session
	StartPHPSession();

	// Init View Configs prior loading config.php!
	InitViewConfigs();
}

function InitUserSystemPhpLogCon()
{
	// global vars needed
	global $gl_root_path, $content;

	if ( GetConfigSetting("UserDBEnabled", false) )
	{
		// Include User Functions
		include($gl_root_path . 'include/functions_users.php');
	}
}

function CheckForInstallPhp()
{
	// Check for installscript!
	if ( file_exists($content['BASEPATH'] . "install.php") ) 
		$strinstallmsg = '<br><br>' 
						. '<center><b>Click <a href="' . $content['BASEPATH'] . 'install.php">here</a> to Install PhpLogCon!</b><br><br>'
//							. 'See the Installation Guides for more Details!<br>'
//							. '<a href="docs/installation.htm" target="_blank">English Installation Guide</a>&nbsp;|&nbsp;'
//							. '<a href="docs/installation_de.htm" target="_blank">German Installation Guide</a><br><br>' 
//							. 'Also take a look to the <a href="docs/readme.htm" target="_blank">Readme</a> for some basics around PhpLogCon!<br>'
						. '</center>';
	else
		$strinstallmsg = "";
	DieWithErrorMsg( 'Error, main configuration file is missing!' . $strinstallmsg );
}

function GetFileLength($szFileName)
{
	if ( is_file($szFileName) )
		return filesize($szFileName);
	else
		return 0;
}

function InitPhpLogCon()
{
	// Needed to make global
	global $gl_root_path, $content;

	// Init Basics which do not need a database
	InitBasicPhpLogCon();
	
	// Will init the config file!
	InitPhpLogConConfigFile();

	// Init UserDB related stuff!
	InitUserSystemPhpLogCon();

	// Establish DB Connection
	if ( GetConfigSetting("UserDBEnabled", false) )
		DB_Connect();

	// Now load the Page configuration values
	InitConfigurationValues();

	// Moved here, because we do not need if GZIP needs to be enabled before the config is loaded!
	InitRuntimeInformations();

	// Now Create Themes List because we haven't the config before!
	CreateThemesList();

	// Create Language List
	CreateLanguageList();

	// Init Predefined Searches List
	CreatePredefinedSearches();

	// Init predefined paging sizes
	CreatePagesizesList();

	// Init predefined reload times
	CreateReloadTimesList();

	// Init predefined reload times
	CreateExportFormatList();

	// --- Enable PHP Debug Mode 
	InitPhpDebugMode();
	// --- 
}

function CreateLogLineTypesList( $selectedType )
{
	global $content;

	// syslog
	$content['LOGLINETYPES']["syslog"]['type'] = "syslog";
	$content['LOGLINETYPES']["syslog"]['DisplayName'] = "Syslog / RSyslog";
	if ( $selectedType == $content['LOGLINETYPES']["syslog"]['type'] ) { $content['LOGLINETYPES']["syslog"]['selected'] = "selected"; } else { $content['LOGLINETYPES']["syslog"]['selected'] = ""; }

	// Adiscon Winsyslog
	$content['LOGLINETYPES']["winsyslog"]['type'] = "winsyslog";
	$content['LOGLINETYPES']["winsyslog"]['DisplayName'] = "Adiscon WinSyslog";
	if ( $selectedType == $content['LOGLINETYPES']["winsyslog"]['type'] ) { $content['LOGLINETYPES']["winsyslog"]['selected'] = "selected"; } else { $content['LOGLINETYPES']["winsyslog"]['selected'] = ""; }
}

function CreateSourceTypesList( $selectedSource )
{
	global $content;

	// SOURCE_DISK
	$content['SOURCETYPES'][SOURCE_DISK]['type'] = SOURCE_DISK;
	$content['SOURCETYPES'][SOURCE_DISK]['DisplayName'] = $content['LN_GEN_SOURCE_DISK'];
	if ( $selectedSource == $content['SOURCETYPES'][SOURCE_DISK]['type'] ) { $content['SOURCETYPES'][SOURCE_DISK]['selected'] = "selected"; } else { $content['SOURCETYPES'][SOURCE_DISK]['selected'] = ""; }

	// SOURCE_DB ( MYSQL NATIVE )
	$content['SOURCETYPES'][SOURCE_DB]['type'] = SOURCE_DB;
	$content['SOURCETYPES'][SOURCE_DB]['DisplayName'] = $content['LN_GEN_SOURCE_DB'];
	if ( $selectedSource == $content['SOURCETYPES'][SOURCE_DB]['type'] ) { $content['SOURCETYPES'][SOURCE_DB]['selected'] = "selected"; } else { $content['SOURCETYPES'][SOURCE_DB]['selected'] = ""; }

	// SOURCE_PDO ( PDO DB Wrapper)
	$content['SOURCETYPES'][SOURCE_PDO]['type'] = SOURCE_PDO;
	$content['SOURCETYPES'][SOURCE_PDO]['DisplayName'] = $content['LN_GEN_SOURCE_PDO'];
	if ( $selectedSource == $content['SOURCETYPES'][SOURCE_PDO]['type'] ) { $content['SOURCETYPES'][SOURCE_PDO]['selected'] = "selected"; } else { $content['SOURCETYPES'][SOURCE_PDO]['selected'] = ""; }
}

function CreateDBTypesList( $selectedDBType )
{
	global $content;

	// DB_MYSQL
	$content['DBTYPES'][DB_MYSQL]['type'] = DB_MYSQL;
	$content['DBTYPES'][DB_MYSQL]['typeastext'] = "DB_MYSQL";
	$content['DBTYPES'][DB_MYSQL]['DisplayName'] = $content['LN_GEN_DB_MYSQL'];
	if ( $selectedDBType == $content['DBTYPES'][DB_MYSQL]['type'] ) { $content['DBTYPES'][DB_MYSQL]['selected'] = "selected"; } else { $content['DBTYPES'][DB_MYSQL]['selected'] = ""; }

	// DB_MSSQL
	$content['DBTYPES'][DB_MSSQL]['type'] = DB_MSSQL;
	$content['DBTYPES'][DB_MSSQL]['typeastext'] = "DB_MSSQL";
	$content['DBTYPES'][DB_MSSQL]['DisplayName'] = $content['LN_GEN_DB_MSSQL'];
	if ( $selectedDBType == $content['DBTYPES'][DB_MSSQL]['type'] ) { $content['DBTYPES'][DB_MSSQL]['selected'] = "selected"; } else { $content['DBTYPES'][DB_MSSQL]['selected'] = ""; }

	// DB_ODBC
	$content['DBTYPES'][DB_ODBC]['type'] = DB_ODBC;
	$content['DBTYPES'][DB_ODBC]['typeastext'] = "DB_ODBC";
	$content['DBTYPES'][DB_ODBC]['DisplayName'] = $content['LN_GEN_DB_ODBC'];
	if ( $selectedDBType == $content['DBTYPES'][DB_ODBC]['type'] ) { $content['DBTYPES'][DB_ODBC]['selected'] = "selected"; } else { $content['DB_ODBC'][DB_ODBC]['selected'] = ""; }

	// DB_PGSQL
	$content['DBTYPES'][DB_PGSQL]['type'] = DB_PGSQL;
	$content['DBTYPES'][DB_PGSQL]['typeastext'] = "DB_PGSQL";
	$content['DBTYPES'][DB_PGSQL]['DisplayName'] = $content['LN_GEN_DB_PGSQL'];
	if ( $selectedDBType == $content['DBTYPES'][DB_PGSQL]['type'] ) { $content['DBTYPES'][DB_PGSQL]['selected'] = "selected"; } else { $content['DB_ODBC'][DB_PGSQL]['selected'] = ""; }

	// DB_OCI
	$content['DBTYPES'][DB_OCI]['type'] = DB_OCI;
	$content['DBTYPES'][DB_OCI]['typeastext'] = "DB_OCI";
	$content['DBTYPES'][DB_OCI]['DisplayName'] = $content['LN_GEN_DB_OCI'];
	if ( $selectedDBType == $content['DBTYPES'][DB_OCI]['type'] ) { $content['DBTYPES'][DB_OCI]['selected'] = "selected"; } else { $content['DB_ODBC'][DB_OCI]['selected'] = ""; }

	// DB_DB2
	$content['DBTYPES'][DB_DB2]['type'] = DB_DB2;
	$content['DBTYPES'][DB_DB2]['typeastext'] = "DB_DB2";
	$content['DBTYPES'][DB_DB2]['DisplayName'] = $content['LN_GEN_DB_DB2'];
	if ( $selectedDBType == $content['DBTYPES'][DB_DB2]['type'] ) { $content['DBTYPES'][DB_DB2]['selected'] = "selected"; } else { $content['DB_ODBC'][DB_DB2]['selected'] = ""; }

	// DB_FIREBIRD
	$content['DBTYPES'][DB_FIREBIRD]['type'] = DB_FIREBIRD;
	$content['DBTYPES'][DB_FIREBIRD]['typeastext'] = "DB_FIREBIRD";
	$content['DBTYPES'][DB_FIREBIRD]['DisplayName'] = $content['LN_GEN_DB_FIREBIRD'];
	if ( $selectedDBType == $content['DBTYPES'][DB_FIREBIRD]['type'] ) { $content['DBTYPES'][DB_FIREBIRD]['selected'] = "selected"; } else { $content['DB_ODBC'][DB_FIREBIRD]['selected'] = ""; }

	// DB_INFORMIX
	$content['DBTYPES'][DB_INFORMIX]['type'] = DB_INFORMIX;
	$content['DBTYPES'][DB_INFORMIX]['typeastext'] = "DB_INFORMIX";
	$content['DBTYPES'][DB_INFORMIX]['DisplayName'] = $content['LN_GEN_DB_INFORMIX'];
	if ( $selectedDBType == $content['DBTYPES'][DB_INFORMIX]['type'] ) { $content['DBTYPES'][DB_INFORMIX]['selected'] = "selected"; } else { $content['DB_ODBC'][DB_INFORMIX]['selected'] = ""; }

	// DB_SQLITE
	$content['DBTYPES'][DB_SQLITE]['type'] = DB_SQLITE;
	$content['DBTYPES'][DB_SQLITE]['typeastext'] = "DB_SQLITE";
	$content['DBTYPES'][DB_SQLITE]['DisplayName'] = $content['LN_GEN_DB_SQLITE'];
	if ( $selectedDBType == $content['DBTYPES'][DB_SQLITE]['type'] ) { $content['DBTYPES'][DB_SQLITE]['selected'] = "selected"; } else { $content['DB_ODBC'][DB_SQLITE]['selected'] = ""; }
}

function CreatePagesizesList()
{
	global $content;

	$tmpViewsPerPage = GetConfigSetting("ViewEntriesPerPage", 50, CFGLEVEL_USER);
	$iCounter = 0;
	$content['pagesizes'][$iCounter] = array( "ID" => $iCounter, "Selected" => "", "DisplayName" => $content['LN_GEN_PRECONFIGURED'] . " (" . $tmpViewsPerPage . ")", "Value" => $tmpViewsPerPage ); $iCounter++;
	$content['pagesizes'][$iCounter] = array( "ID" => $iCounter, "Selected" => "", "DisplayName" => " 25 " . $content['LN_GEN_RECORDSPERPAGE'], "Value" => 25 ); $iCounter++;
	$content['pagesizes'][$iCounter] = array( "ID" => $iCounter, "Selected" => "", "DisplayName" => " 50 " . $content['LN_GEN_RECORDSPERPAGE'], "Value" => 50 ); $iCounter++;
	$content['pagesizes'][$iCounter] = array( "ID" => $iCounter, "Selected" => "", "DisplayName" => " 75 " . $content['LN_GEN_RECORDSPERPAGE'], "Value" => 75 ); $iCounter++;
	$content['pagesizes'][$iCounter] = array( "ID" => $iCounter, "Selected" => "", "DisplayName" => " 100 " . $content['LN_GEN_RECORDSPERPAGE'], "Value" => 100 ); $iCounter++;
	$content['pagesizes'][$iCounter] = array( "ID" => $iCounter, "Selected" => "", "DisplayName" => " 250 " . $content['LN_GEN_RECORDSPERPAGE'], "Value" => 250 ); $iCounter++;
	$content['pagesizes'][$iCounter] = array( "ID" => $iCounter, "Selected" => "", "DisplayName" => " 500 " . $content['LN_GEN_RECORDSPERPAGE'], "Value" => 500 ); $iCounter++;
	
	// Set default selected pagesize
	$content['pagesizes'][ $_SESSION['PAGESIZE_ID'] ]["Selected"] = "selected";

	// The content variable will now contain the user selected oaging size
	$content["ViewEntriesPerPage"] = $content['pagesizes'][ $_SESSION['PAGESIZE_ID'] ]["Value"];
}

function CreateReloadTimesList()
{
	global $content;

	$iCounter = 0;	
	$content['reloadtimes'][$iCounter] = array( "ID" => $iCounter, "Selected" => "", "DisplayName" => $content['LN_AUTORELOAD_DISABLED'], "Value" => 0 ); $iCounter++;

	$tmpReloadSeconds = GetConfigSetting("ViewEnableAutoReloadSeconds", "", CFGLEVEL_USER);
	if ( $tmpReloadSeconds > 0 )
	{
		$content['reloadtimes'][$iCounter] = array( "ID" => $iCounter, "Selected" => "", "DisplayName" => $content['LN_AUTORELOAD_PRECONFIGURED'] . " (" . $tmpReloadSeconds . " " . $content['LN_AUTORELOAD_SECONDS'] . ") ", "Value" => $tmpReloadSeconds ); $iCounter++;
	}
	$content['reloadtimes'][$iCounter] = array( "ID" => $iCounter, "Selected" => "", "DisplayName" => " 5 " . $content['LN_AUTORELOAD_SECONDS'], "Value" => 5 ); $iCounter++;
	$content['reloadtimes'][$iCounter] = array( "ID" => $iCounter, "Selected" => "", "DisplayName" => " 10 " . $content['LN_AUTORELOAD_SECONDS'], "Value" => 10 ); $iCounter++;
	$content['reloadtimes'][$iCounter] = array( "ID" => $iCounter, "Selected" => "", "DisplayName" => " 15 " . $content['LN_AUTORELOAD_SECONDS'], "Value" => 15 ); $iCounter++;
	$content['reloadtimes'][$iCounter] = array( "ID" => $iCounter, "Selected" => "", "DisplayName" => " 30 " . $content['LN_AUTORELOAD_SECONDS'], "Value" => 30 ); $iCounter++;
	$content['reloadtimes'][$iCounter] = array( "ID" => $iCounter, "Selected" => "", "DisplayName" => " 60 " . $content['LN_AUTORELOAD_SECONDS'], "Value" => 60 ); $iCounter++;
	$content['reloadtimes'][$iCounter] = array( "ID" => $iCounter, "Selected" => "", "DisplayName" => " 5 " . $content['LN_AUTORELOAD_MINUTES'], "Value" => 300 ); $iCounter++;
	$content['reloadtimes'][$iCounter] = array( "ID" => $iCounter, "Selected" => "", "DisplayName" => " 10 " . $content['LN_AUTORELOAD_MINUTES'], "Value" => 600 ); $iCounter++;
	$content['reloadtimes'][$iCounter] = array( "ID" => $iCounter, "Selected" => "", "DisplayName" => " 15 " . $content['LN_AUTORELOAD_MINUTES'], "Value" => 900 ); $iCounter++;
	$content['reloadtimes'][$iCounter] = array( "ID" => $iCounter, "Selected" => "", "DisplayName" => " 30 " . $content['LN_AUTORELOAD_MINUTES'], "Value" => 1800 ); $iCounter++;

	// Set default selected autoreloadid
	$content['reloadtimes'][ $_SESSION['AUTORELOAD_ID'] ]["Selected"] = "selected";

	// The content variable will now contain the user selected oaging size
	$content["ViewEnableAutoReloadSeconds"] = $content['reloadtimes'][ $_SESSION['AUTORELOAD_ID'] ]["Value"];

}

function CreateExportFormatList()
{
	global $content;
	
	// Add basic formats!
	$content['EXPORTTYPES'][EXPORT_CVS] = array( "ID" => EXPORT_CVS, "Selected" => "", "DisplayName" => $content['LN_GEN_EXPORT_CVS'] );
	$content['EXPORTTYPES'][EXPORT_XML] = array( "ID" => EXPORT_XML, "Selected" => "", "DisplayName" => $content['LN_GEN_EXPORT_XML'] );

	// TODO: Add formats by loaded extensions
}

function CreatePredefinedSearches()
{
	global $CFG, $content;
	if ( isset($CFG['Search']) )
	{
		// Enable predefined searches 
		$content['EnablePredefinedSearches'] = true;
		
		// Loop through all predefined searches!
		foreach ($CFG['Search'] as $mykey => $mySearch)
		{
			// Copy configured searches into content array!
			$content['Search'][$mykey]["ID"] = $mykey;
			$content['Search'][$mykey]["Selected"] = false;

			// --- Set CSS Class
			if ( $mykey % 2 == 0 )
				$content['Search'][$mykey]['cssclass'] = "line1";
			else
				$content['Search'][$mykey]['cssclass'] = "line2";
			// --- 

		}
	}
	else	// Disable predefined searches 
		$content['EnablePredefinedSearches'] = false;
}

function InitPhpDebugMode()
{
	global $content;

	// --- Set Global DEBUG Level!
	if ( GetConfigSetting("MiscShowDebugMsg", 0, CFGLEVEL_USER) == 1 )
		ini_set( "error_reporting", E_ALL ); // ALL PHP MESSAGES!
	else
		ini_set( "error_reporting", E_ERROR ); // ONLY PHP ERROR'S!
	// --- 
}

function CheckAndSetRunMode()
{
	global $content, $RUNMODE, $MaxExecutionTime;
	// Set to command line mode if argv is set! 
	if ( !isset($_SERVER["GATEWAY_INTERFACE"]) )
		$RUNMODE = RUNMODE_COMMANDLINE;
	
	// Obtain max_execution_time
	$MaxExecutionTime = ini_get("max_execution_time");

	// Define and Inits Syslog variables now!
	define_syslog_variables();
	openlog("phpLogCon", LOG_PID, LOG_USER);
	
	// --- Check necessary PHP Extensions!
	$loadedExtensions = get_loaded_extensions();
	
	// Check for GD libary
	if ( in_array("gd", $loadedExtensions) ) 
		$content['GD_IS_ENABLED'] = true;
	else 
		$content['GD_IS_ENABLED'] = false;
	
	// Check MYSQL Extension
	if ( in_array("mysql", $loadedExtensions) ) { $content['MYSQL_IS_ENABLED'] = true; } else { $content['MYSQL_IS_ENABLED'] = false; }
	// Check PDO Extension
	if ( in_array("PDO", $loadedExtensions) ) { $content['PDO_IS_ENABLED'] = true; } else { $content['PDO_IS_ENABLED'] = false; }
	// --- 
}

function InitRuntimeInformations()
{
	global $content;

	// Enable GZIP Compression if enabled!
	if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false && GetConfigSetting("MiscEnableGzipCompression", 1, CFGLEVEL_USER) == 1 ) 
	{
		// This starts gzip compression!
		ob_start("ob_gzhandler");
		$content['GzipCompressionEnmabled'] = "yes";
	}
	else
		$content['GzipCompressionEnmabled'] = "no";
}

function CreateDebugModes()
{
	global $content;

	$content['DBGMODES'][0]['DisplayName'] = STR_DEBUG_ULTRADEBUG;
	if ( $content['parser_debugmode'] == $content['DBGMODES'][0]['DisplayName'] ) { $content['DBGMODES'][0]['selected'] = "selected"; } else { $content['DBGMODES'][0]['selected'] = ""; }
	$content['DBGMODES'][1]['DisplayName'] = STR_DEBUG_DEBUG;
	if ( $content['parser_debugmode'] == $content['DBGMODES'][1]['DisplayName'] ) { $content['DBGMODES'][1]['selected'] = "selected"; } else { $content['DBGMODES'][1]['selected'] = ""; }
	$content['DBGMODES'][2]['DisplayName'] = STR_DEBUG_INFO;
	if ( $content['parser_debugmode'] == $content['DBGMODES'][2]['DisplayName'] ) { $content['DBGMODES'][2]['selected'] = "selected"; } else { $content['DBGMODES'][2]['selected'] = ""; }
	$content['DBGMODES'][3]['DisplayName'] = STR_DEBUG_WARN;
	if ( $content['parser_debugmode'] == $content['DBGMODES'][3]['DisplayName'] ) { $content['DBGMODES'][3]['selected'] = "selected"; } else { $content['DBGMODES'][3]['selected'] = ""; }
	$content['DBGMODES'][4]['DisplayName'] = STR_DEBUG_ERROR;
	if ( $content['parser_debugmode'] == $content['DBGMODES'][4]['DisplayName'] ) { $content['DBGMODES'][4]['selected'] = "selected"; } else { $content['DBGMODES'][4]['selected'] = ""; }
}

function InitFrontEndVariables()
{
	global $content;

	$content['MENU_FOLDER_OPEN'] = $content['BASEPATH'] . "images/icons/folder_closed.png";
	$content['MENU_FOLDER_CLOSED'] = $content['BASEPATH'] . "images/icons/folder.png";
	$content['MENU_HOMEPAGE'] = $content['BASEPATH'] . "images/icons/home.png";
	$content['MENU_LINK'] = $content['BASEPATH'] . "images/icons/link.png";
	$content['MENU_LINK_VIEW'] = $content['BASEPATH'] . "images/icons/link_view.png";
	$content['MENU_VIEW'] = $content['BASEPATH'] . "images/icons/view.png";
	$content['MENU_PREFERENCES'] = $content['BASEPATH'] . "images/icons/preferences.png";
	$content['MENU_ADMINENTRY'] = $content['BASEPATH'] . "images/icons/star_blue.png";
	$content['MENU_ADMINLOGOFF'] = $content['BASEPATH'] . "images/icons/exit.png";
	$content['MENU_ADMINUSERS'] = $content['BASEPATH'] . "images/icons/businessman.png";
	$content['MENU_ADMINGROUPS'] = $content['BASEPATH'] . "images/icons/businessmen.png";
	$content['MENU_SEARCH'] = $content['BASEPATH'] . "images/icons/view.png";
	$content['MENU_SELECTION_DISABLED'] = $content['BASEPATH'] . "images/icons/selection.png";
	$content['MENU_SELECTION_ENABLED'] = $content['BASEPATH'] . "images/icons/selection_delete.png";
	$content['MENU_TEXT_FIND'] = $content['BASEPATH'] . "images/icons/text_find.png";
	$content['MENU_NETWORK'] = $content['BASEPATH'] . "images/icons/earth_network.png";
	$content['MENU_HELP'] = $content['BASEPATH'] . "images/icons/help.png";
	$content['MENU_KB'] = $content['BASEPATH'] . "images/icons/books.png";
	$content['MENU_DOCUMENTVIEW'] = $content['BASEPATH'] . "images/icons/document_view.png";
	$content['MENU_DATAEDIT'] = $content['BASEPATH'] . "images/icons/data_edit.png";
	$content['MENU_ADDUSER'] = $content['BASEPATH'] . "images/icons/businessman_add.png";
	$content['MENU_DELUSER'] = $content['BASEPATH'] . "images/icons/businessman_delete.png";
	$content['MENU_ADD'] = $content['BASEPATH'] . "images/icons/add.png";
	$content['MENU_EDIT'] = $content['BASEPATH'] . "images/icons/edit.png";
	$content['MENU_DELETE'] = $content['BASEPATH'] . "images/icons/delete.png";
	$content['MENU_GLOBAL'] = $content['BASEPATH'] . "images/icons/earth.png";
	$content['MENU_INTERNAL'] = $content['BASEPATH'] . "images/icons/gear.png";
	$content['MENU_EDIT_DISABLED'] = $content['BASEPATH'] . "images/icons/edit_disabled.png";
	$content['MENU_DELETE_DISABLED'] = $content['BASEPATH'] . "images/icons/delete_disabled.png";
	$content['MENU_MOVE_UP'] = $content['BASEPATH'] . "images/icons/nav_up_blue.png";
	$content['MENU_MOVE_DOWN'] = $content['BASEPATH'] . "images/icons/nav_down_blue.png";
	$content['MENU_SOURCE_DISK'] = $content['BASEPATH'] . "images/icons/document_text.png";
	$content['MENU_SOURCE_DB'] = $content['BASEPATH'] . "images/icons/data_table.png";
	$content['MENU_SOURCE_PDO'] = $content['BASEPATH'] . "images/icons/data_gear.png";
	$content['MENU_MAXIMIZE'] = $content['BASEPATH'] . "images/icons/table_selection_all.png";
	$content['MENU_NORMAL'] = $content['BASEPATH'] . "images/icons/table_selection_block.png";
	$content['MENU_USEROPTIONS'] = $content['BASEPATH'] . "images/icons/businessman_preferences.png";
	$content['MENU_EXPORT'] = $content['BASEPATH'] . "images/icons/export1.png";
	$content['MENU_CHARTS'] = $content['BASEPATH'] . "images/icons/line-chart.png";

	$content['MENU_PAGER_BEGIN'] = $content['BASEPATH'] . "images/icons/media_beginning.png";
	$content['MENU_PAGER_PREVIOUS'] = $content['BASEPATH'] . "images/icons/media_rewind.png";
	$content['MENU_PAGER_NEXT'] = $content['BASEPATH'] . "images/icons/media_fast_forward.png";
	$content['MENU_PAGER_END'] = $content['BASEPATH'] . "images/icons/media_end.png";
	$content['MENU_NAV_LEFT'] = $content['BASEPATH'] . "images/icons/navigate_left.png";
	$content['MENU_NAV_RIGHT'] = $content['BASEPATH'] . "images/icons/navigate_right.png";
	$content['MENU_NAV_CLOSE'] = $content['BASEPATH'] . "images/icons/navigate_close.png";
	$content['MENU_NAV_OPEN'] = $content['BASEPATH'] . "images/icons/navigate_open.png";
	$content['MENU_PAGER_BEGIN_GREY'] = $content['BASEPATH'] . "images/icons/grey/media_beginning.png";
	$content['MENU_PAGER_PREVIOUS_GREY'] = $content['BASEPATH'] . "images/icons/grey/media_rewind.png";
	$content['MENU_PAGER_NEXT_GREY'] = $content['BASEPATH'] . "images/icons/grey/media_fast_forward.png";
	$content['MENU_PAGER_END_GREY'] = $content['BASEPATH'] . "images/icons/grey/media_end.png";

	$content['MENU_BULLET_BLUE'] = $content['BASEPATH'] . "images/icons/bullet_ball_glass_blue.png";
	$content['MENU_BULLET_GREEN'] = $content['BASEPATH'] . "images/icons/bullet_ball_glass_green.png";
	$content['MENU_BULLET_RED'] = $content['BASEPATH'] . "images/icons/bullet_ball_glass_red.png";
	$content['MENU_BULLET_YELLOW'] = $content['BASEPATH'] . "images/icons/bullet_ball_glass_yellow.png";
	$content['MENU_BULLET_GREY'] = $content['BASEPATH'] . "images/icons/bullet_ball_glass_grey.png";

	$content['MENU_ICON_GOOGLE'] = $content['BASEPATH'] . "images/icons/googleicon.png";
}

// Lang Helper for Strings with ONE variable
function GetAndReplaceLangStr( $strlang, $param1 = "", $param2 = "", $param3 = "", $param4 = "", $param5 = "" )
{
	$strfinal = str_replace ( "%1", $param1, $strlang );
	if ( strlen($param2) > 0 )
		$strfinal = str_replace ( "%2", $param2, $strfinal );
	if ( strlen($param3) > 0 )
		$strfinal = str_replace ( "%3", $param3, $strfinal );
	if ( strlen($param4) > 0 )
		$strfinal = str_replace ( "%4", $param4, $strfinal );
	if ( strlen($param5) > 0 )
		$strfinal = str_replace ( "%5", $param5, $strfinal );
	
	// And return
	return $strfinal;
}

function InitConfigurationValues()
{
	global $content, $CFG, $LANG, $gl_root_path;
	
	// To avoid this code in case of conversion
	if ( !defined('IN_PHPLOGCON_CONVERT') )
	{
		// If Database is enabled, try to read from database!
		if ( GetConfigSetting("UserDBEnabled", false) )
		{
			// Get configuration variables 
			$result = DB_Query("SELECT * FROM " . DB_CONFIG . " WHERE is_global = true");
			
			if ( $result )
			{
				$rows = DB_GetAllRows($result, true);
				// Read results from DB and overwrite in $CFG Array!
				if ( isset($rows ) )
				{
					for($i = 0; $i < count($rows); $i++)
					{
						$CFG[ $rows[$i]['propname'] ] = $rows[$i]['propvalue'];
						$content[ $rows[$i]['propname'] ] = $rows[$i]['propvalue'];
					}
				}
			}
			else // Critical ERROR HERE!
				DieWithFriendlyErrorMsg( "Critical Error occured while trying to access the database in table '" . DB_CONFIG . "'" );

			// Database Version Checker! 
			if ( $content['database_internalversion'] > $content['database_installedversion'] )
			{	
				// Database is out of date, we need to upgrade
				$content['database_forcedatabaseupdate'] = "yes"; 
			}

			// Now we init the user session stuff
			InitUserSession();
			
			if ( !$content['SESSION_LOGGEDIN'] ) 
			{

				// Check if user needs to be logged in
				if ( GetConfigSetting("UserDBLoginRequired", false) )
				{
						// User needs to be logged in, redirect to login page
						if ( !defined("IS_NOLOGINPAGE") )
							RedirectToUserLogin();
				}
				else if ( defined('IS_ADMINPAGE') )
				{
					// Language System not initialized yet
					DieWithFriendlyErrorMsg( "You need to be logged in in order to access the admin pages." );
				}
			}

			// Load Configured Searches 
			LoadSearchesFromDatabase();

			// Load Configured Views
			LoadViewsFromDatabase();

			// Load Configured Sources
			LoadSourcesFromDatabase();
		}
		else
		{
			if ( defined('IS_ADMINPAGE') || defined("IS_NOLOGINPAGE") )	// Language System not initialized yet
				DieWithFriendlyErrorMsg( "The phpLogCon user system is currently disabled or not installed." );
		}
	}

	// --- Language Handling
	if ( isset($_SESSION['CUSTOM_LANG']) && VerifyLanguage($_SESSION['CUSTOM_LANG']) )
	{
		$content['user_lang'] = $_SESSION['CUSTOM_LANG'];
		$LANG = $content['user_lang'];
	}
	else if ( isset($content['gen_lang']) && VerifyLanguage($content['gen_lang']))
	{
		$content['user_lang'] = $content['gen_lang'];
		$LANG = $content['user_lang'];
	}
	else	// Failsave!
	{
		$content['user_lang'] = GetConfigSetting("ViewDefaultLanguage", "en", CFGLEVEL_USER) /*"en"*/;
		$LANG = $content['user_lang'];
		$content['gen_lang'] = $content['user_lang'];
	}
	// --- 

	// Paging Size handling!
	if ( !isset($_SESSION['PAGESIZE_ID']) )
	{
		// Default is 0! 
		$_SESSION['PAGESIZE_ID'] = 0;
	}

	// Auto reload handling!
	if ( !isset($_SESSION['AUTORELOAD_ID']) )
	{
		if ( GetConfigSetting("ViewEnableAutoReloadSeconds", 0, CFGLEVEL_USER) > 0 )
			$_SESSION['AUTORELOAD_ID'] = 1; // Autoreload ID will be the first item!
		else	// Default is 0, which means auto reload disabled
			$_SESSION['AUTORELOAD_ID'] = 0;
	}

	// Theme Handling
	if ( !isset($content['web_theme']) ) { $content['web_theme'] = GetConfigSetting("ViewDefaultTheme", "default", CFGLEVEL_USER); }
	if ( isset($_SESSION['CUSTOM_THEME']) && VerifyTheme($_SESSION['CUSTOM_THEME']) )
		$content['user_theme'] = $_SESSION['CUSTOM_THEME'];
	else
		$content['user_theme'] = $content['web_theme'];

	// Init Theme About Info ^^
	InitThemeAbout($content['user_theme']);

	// Init main langauge file now!
	IncludeLanguageFile( $gl_root_path . '/lang/' . $LANG . '/main.php' );

	// Init other things which are needed
	InitFrontEndVariables();
}

function SetDebugModeFromString( $facility )
{
	global $DEBUGMODE;

	switch ( $facility )
	{
		case STR_DEBUG_ULTRADEBUG:
			$DEBUGMODE = DEBUG_ULTRADEBUG;
			break;
		case STR_DEBUG_DEBUG:
			$DEBUGMODE = DEBUG_DEBUG;
			break;
		case STR_DEBUG_INFO:
			$DEBUGMODE = DEBUG_INFO;
			break;
		case STR_DEBUG_WARN:
			$DEBUGMODE = DEBUG_WARN;
			break;
		case STR_DEBUG_ERROR:
			$DEBUGMODE = DEBUG_ERROR;
			break;
	}
}


function InitPageRenderStats()
{
	global $gl_starttime, $querycount;
	$gl_starttime = microtime_float();
	$querycount = 0;
}

function FinishPageRenderStats( &$mycontent)
{
	global $gl_starttime, $querycount;

	$endtime = microtime_float();
	$mycontent['PAGERENDERTIME'] = number_format($endtime - $gl_starttime, 4, '.', '');
	$mycontent['TOTALQUERIES'] = $querycount;
}

function microtime_float()
{
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}

function SetLineBreakVar()
{
	// Used for some functions
	global $RUNMODE, $linesep;

	if		( $RUNMODE == RUNMODE_COMMANDLINE )
		$linesep = "\r\n";
	else if	( $RUNMODE == RUNMODE_WEBSERVER )
		$linesep = "<br>";
}

function CheckUrlOrIP($ip) 
{
	$long = ip2long($ip); 
	if ( $long == -1 ) 
		return false; 
	else
		return true; 
}

function DieWithErrorMsg( $szerrmsg )
{
	global $gl_root_path, $content;
	print("<html><title>phpLogCon :: Critical Error occured</title><head><link rel=\"stylesheet\" href=\"" . $gl_root_path . "admin/css/admin.css\" type=\"text/css\"></head><body>");
	print("<table width=\"600\" align=\"center\" class=\"with_border\"><tr><td><center><H3><font color='red'>Critical Error occured</font></H3><br></center>");
	print("<B>Errordetails:</B><BR>" .  $szerrmsg);
	print("</td></tr></table>");

	exit;
}

function DieWithFriendlyErrorMsg( $szerrmsg )
{
	//TODO: Make with template
	print("<html><body>");
	print("<center><H3><font color='red'>Error occured</font></H3><br></center>");
	print("<B>Errordetails:</B><BR>" .  $szerrmsg);
	exit;
}

/*
*	Helper function to initialize the page title!
*/
function InitPageTitle()
{
	global $content, $currentSourceID;

	$tmpTitle = GetConfigSetting("PrependTitle", "", CFGLEVEL_USER);
	if ( strlen($tmpTitle) > 0 )
		$szReturn = $tmpTitle . " :: ";
	else
		$szReturn = "";

	if ( !defined('IS_ADMINPAGE') )
	{
		if ( isset($currentSourceID) && isset($content['Sources'][$currentSourceID]['Name']) )
			$szReturn .= "Source '" . $content['Sources'][$currentSourceID]['Name'] . "' :: ";
	}

	// Append phpLogCon
	$szReturn .= "phpLogCon";

	if ( defined('IS_ADMINPAGE') )
		$szReturn .= " :: " . $content['LN_ADMIN_CENTER'] . " :: ";

	// return result
	return $szReturn;
}

function ReplaceLineBreaksInString($myStr)
{
	// Add linebreaks!
	return str_replace( "\n", "<br>", $myStr );
}

function GetStringWithHTMLCodes($myStr)
{

	// Replace all special characters with valid html representations
	return htmlentities($myStr);
}

function InitTemplateParser()
{
	global $page, $gl_root_path;
	// -----------------------------------------------
	// Create Template Object and set some variables for the templates
	// -----------------------------------------------
	$page = new Template();
	$page -> set_path ( $gl_root_path . "templates/" );
}

function VerifyLanguage( $mylang ) 
{ 
	global $gl_root_path;

	if ( is_dir( $gl_root_path . 'lang/' . $mylang ) )
		return true;
	else
		return false;
}

function IncludeLanguageFile( $langfile ) 
{
	global $LANG, $LANG_EN; 

	if ( file_exists( $langfile ) )
		include( $langfile );
	else
	{
		$langfile = str_replace( $LANG, $LANG_EN, $langfile );
		include( $langfile );
	}
}

function RedirectPage( $newpage )
{
	header("Location: $newpage");
	exit;
}

function RedirectResult( $szMsg, $newpage )
{
	header("Location: result.php?msg=" . urlencode($szMsg) . "&redir=" . urlencode($newpage));
	exit;
}

/*
*	GetEventTime
*
*	Helper function to parse and obtain a valid EventTime Array from the input string.
*	Return value: EventTime Array!
*
*/
function GetEventTime($szTimStr)
{
	// Sample: Mar 10 14:45:44
	if ( preg_match("/(...) ([0-9]{1,2}) ([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})/", $szTimStr, $out ) )
	{
		// RFC 3164 typical timestamp
		$eventtime[EVTIME_TIMESTAMP] = mktime($out[3], $out[4], $out[5], GetMonthFromString($out[1]), $out[2]);
		$eventtime[EVTIME_TIMEZONE] = date_default_timezone_get(); // WTF TODO!
		$eventtime[EVTIME_MICROSECONDS] = 0;

//			echo gmdate(DATE_RFC822, $eventtime[EVTIME_TIMESTAMP]) . "<br>";
//			print_r ( $eventtime );
//			exit;
	}
	// Sample: 2008-04-02T11:12:32+02:00
	else if ( preg_match("/([0-9]{4,4})-([0-9]{1,2})-([0-9]{1,2})T([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})\+([0-9]{1,2}):([0-9]{1,2})/", $szTimStr, $out ) )
	{
		// RFC 3164 typical timestamp
		$eventtime[EVTIME_TIMESTAMP] = mktime($out[4], $out[5], $out[6], $out[2], $out[3], $out[1]);
		$eventtime[EVTIME_TIMEZONE] = $out[7]; 
		$eventtime[EVTIME_MICROSECONDS] = 0;
	}
	// Sample: 2008-04-02T11:12:32.380449+02:00
	else if ( preg_match("/([0-9]{4,4})-([0-9]{1,2})-([0-9]{1,2})T([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})\.([0-9]{1,6})\+([0-9]{1,2}):([0-9]{1,2})/", $szTimStr, $out ) )
	{
		// RFC 3164 typical timestamp
		$eventtime[EVTIME_TIMESTAMP] = mktime($out[4], $out[5], $out[6], $out[2], $out[3], $out[1]);
		$eventtime[EVTIME_TIMEZONE] = $out[8]; 
		$eventtime[EVTIME_MICROSECONDS] = $out[7];
	}
	// Sample: 2008-04-02,15:19:06
	else if ( preg_match("/([0-9]{4,4})-([0-9]{1,2})-([0-9]{1,2}),([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})/", $szTimStr, $out ) )
	{
		// RFC 3164 typical timestamp
		$eventtime[EVTIME_TIMESTAMP] = mktime($out[4], $out[5], $out[6], $out[2], $out[3], $out[1]);
		$eventtime[EVTIME_TIMEZONE] = date_default_timezone_get(); // WTF TODO!
		$eventtime[EVTIME_MICROSECONDS] = 0;
	}
	// Sample: 2008-02-19 12:52:37
	else if ( preg_match("/([0-9]{4,4})-([0-9]{1,2})-([0-9]{1,2}) ([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})/", $szTimStr, $out ) )
	{
		// RFC 3164 typical timestamp
		$eventtime[EVTIME_TIMESTAMP] = mktime($out[4], $out[5], $out[6], $out[2], $out[3], $out[1]);
		$eventtime[EVTIME_TIMEZONE] = date_default_timezone_get(); // WTF TODO!
		$eventtime[EVTIME_MICROSECONDS] = 0;
	}
	// Sample: 2007-4-18T00:00:00
	else if ( preg_match("/([0-9]{4,4})-([0-9]{1,2})-([0-9]{1,2})T([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})/", $szTimStr, $out ) )
	{
		// RFC 3164 typical timestamp
		$eventtime[EVTIME_TIMESTAMP] = mktime($out[4], $out[5], $out[6], $out[2], $out[3], $out[1]);
		$eventtime[EVTIME_TIMEZONE] = date_default_timezone_get(); // WTF TODO!
		$eventtime[EVTIME_MICROSECONDS] = 0;
	}
	else
	{
		$eventtime[EVTIME_TIMESTAMP] = 0;
		$eventtime[EVTIME_TIMEZONE] = date_default_timezone_get(); // WTF TODO!
		$eventtime[EVTIME_MICROSECONDS] = 0;
		
		// Print Error!
		OutputDebugMessage("GetEventTime got an unparsable time '" . $szTimStr . "', returning 0", DEBUG_WARN);
	}

	// return result!
	return $eventtime;
}

/*
*	GetMonthFromString
*	
*	Simple Helper function to obtain the numeric represantation of the month
*/
function GetMonthFromString($szMonth)
{
	switch($szMonth)
	{
		case "Jan":
			return 1;
		case "Feb":
			return 2;
		case "Mar":
			return 3;
		case "Apr":
			return 4;
		case "May":
			return 5;
		case "Jun":
			return 6;
		case "Jul":
			return 7;
		case "Aug":
			return 8;
		case "Sep":
			return 9;
		case "Oct":
			return 10;
		case "Nov":
			return 11;
		case "Dez":
			return 12;
	}
}

/*
*	AddContextLinks
*/
function AddContextLinks(&$sourceTxt)
{
	global $szTLDDomains;
	
	// Return if not enabled!
	if ( GetConfigSetting("EnableIPAddressResolve", 0, CFGLEVEL_USER) == 1 )
	{
		// Search for IP's and Add Reverse Lookup first!
		$sourceTxt = preg_replace( '/([^\[])\b(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b/e', "'\\1\\2.\\3.\\4.\\5' . ReverseResolveIP('\\2.\\3.\\4.\\5', '<font class=\"highlighted\"> {', '} </font>')", $sourceTxt );
	}

	// Create if not set!
	if ( !isset($szTLDDomains) )
		CreateTopLevelDomainSearch();

	// Create Search Array
	$search = array 
				(
					'/\.([\w\d\_\-]+)\.(' . $szTLDDomains . ')([^a-zA-Z0-9\.])/e',
/* (?:127)| */		'/(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)/e',
				);

	// Create Replace Array
	$replace = array 
				(
					"'.' . InsertLookupLink(\"\", \"\\1.\\2\", \"\", \"\\3\")",
					"InsertLookupLink(\"\\1.\\2.\\3.\\4\", \"\", \"\", \"\")", 
				);
	
	// Replace and return!
	$sourceTxt = preg_replace( $search, $replace, $sourceTxt );

//echo $outTxt . " <br>" ;
//return $outTxt;
}

/*
*	Helper to create a Lookup Link!
*/
function InsertLookupLink( $szIP, $szDomain, $prepend, $append )
{
	// Create string
	$szReturn  = $prepend;
	if ( strlen($szIP) > 0 )
	{
		// Split IP into array
		$IPArray = explode(".", $szIP);

		if ( 
				(intval($IPArray[0]) == 10	) ||
				(intval($IPArray[0]) == 127 ) ||
				(intval($IPArray[0]) == 172 && intval($IPArray[1]) >= 16 && intval($IPArray[1]) <= 31) || 
				(intval($IPArray[0]) == 192	&& intval($IPArray[1]) == 168) ||
				(intval($IPArray[0]) == 255	)
			)
			// Do not create a LINK in this case!
			$szReturn .= '<b>' . $szIP . '</b>';
		else
			// Normal LINK!
			$szReturn .= '<a href="http://kb.monitorware.com/kbsearch.php?sa=whois&oid=ip&origin=phplogcon&q=' . $szIP . '" target="_top" class="contextlink">' . $szIP . '</a>';
	}
	else if ( strlen($szDomain) > 0 ) 
		$szReturn .= '<a href="http://kb.monitorware.com/kbsearch.php?sa=whois&oid=name&origin=phplogcon&q=' . $szDomain . '" target="_top" class="contextlink">' . $szDomain . '</a>';
	$szReturn .= $append;

	// return result
	return $szReturn;
}

/*
*	Reserve Resolve IP Address!
*/
function ReverseResolveIP( $szIP, $prepend, $append )
{
	global $gl_starttime, $MaxExecutionTime;

	// Substract 5 savety seconds!
	$scriptruntime = intval(microtime_float() - $gl_starttime);
	if ( $scriptruntime > ($MaxExecutionTime-5) )
		return "";

	// Abort if these IP's are postet
	if ( strpos($szIP, "0.0.0.0") !== false | strpos($szIP, "127.") !== false | strpos($szIP, "255.255.255.255") !== false ) 
		return "";
	else
	{
		// Resolve name if needed
		if ( !isset($_SESSION['dns_cache'][$szIP]) ) 
			$_SESSION['dns_cache'][$szIP] = gethostbyaddr($szIP);
		
		// Abort if IP and RESOLVED name are the same ^^!
		if ( $_SESSION['dns_cache'][$szIP] == $szIP || strlen($_SESSION['dns_cache'][$szIP]) <= 0 )
			return;

		// Create string
		$szReturn  = $prepend;
		$szReturn .= $_SESSION['dns_cache'][$szIP];
		$szReturn .= $append;

		// return result
		return $szReturn;
	}
}

/*
*	Helper function to create a top level domain search string ONCE per process!
*/
function CreateTopLevelDomainSearch()
{
	// Current list taken from http://en.wikipedia.org/wiki/List_of_Internet_top-level_domains!
	global $szTLDDomains;
	$szTLDDomains  = "co.th|com.au|co.uk|co.jp";
	$szTLDDomains .= "aero|asia|biz|cat|com|coop|edu|gov|info|int|jobs|mil|mobi|museum|name|net|org|pro|tel|travel|cTLD|ac|ad|ae|af|ag|ai|al|am|an|ao|aq|ar|as|at|au|aw|ax|az|ba|bb|bd|be|bf|bg|bh|bi|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|cr|cu|cv|cx|cy|cz|de|dj|dk|dm|do|dz|ec|ee|eg|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|im|in|io|iq|ir|is|it|je|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|me|mg|mh|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|mv|mw|mx|my|mz|na|nc|ne|nf|ng|ni|nl|no|np|nr|nu|nz|om|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|ps|pt|pw|py|qa|re|ro|rs|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|st|su|sv|sy|sz|tc|td|tf|tg|th|tj|tk|tl|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw";
}

/*
*	This Functions starts the main PHP Session if necessary
*/
function StartPHPSession()
{
	global $RUNMODE;
	if ( $RUNMODE == RUNMODE_WEBSERVER )
	{
		// This will start the session
		if (session_id() == "")
			session_start();

		if ( !isset($_SESSION['SESSION_STARTED']) )
			$_SESSION['SESSION_STARTED'] = "true";
	}
}

function PrintSecureUserCheck( $warningtext, $yesmsg, $nomsg )
{
	global $content, $page;

	// Copy properties
	$content['warningtext'] = $warningtext;
	$content['yesmsg'] = $yesmsg;
	$content['nomsg'] = $nomsg;

	// Handle GET and POST input!
	$content['form_url'] = $_SERVER['SCRIPT_NAME'] . "?";
	foreach ($_GET as $varname => $varvalue)
		$content['form_url'] .= $varname . "=" . $varvalue . "&";
	$content['form_url'] .= "verify=yes"; // Append verify!

	foreach ($_POST as $varname => $varvalue)
		$content['POST_VARIABLES'][] = array( "varname" => $varname, "varvalue" => $varvalue );

	// --- BEGIN CREATE TITLE
	$content['TITLE'] = InitPageTitle();
	$content['TITLE'] .= " :: Confirm Action";
	// --- END CREATE TITLE

	// --- Parsen and Output
	InitTemplateParser();
	$page -> parser($content, "admin/admin_securecheck.html");
	$page -> output(); 
	// --- 
	
	// Exit script execution
	exit;
}

function SaveGeneralSettingsIntoDB()
{
	WriteConfigValue( "ViewDefaultLanguage", true );
	WriteConfigValue( "ViewDefaultTheme", true );

	WriteConfigValue( "ViewUseTodayYesterday", true );
	WriteConfigValue( "ViewEnableDetailPopups", true );
	WriteConfigValue( "EnableIPAddressResolve", true );
	WriteConfigValue( "MiscShowDebugMsg", true );
	WriteConfigValue( "MiscShowDebugGridCounter", true );
	WriteConfigValue( "MiscShowPageRenderStats", true );
	WriteConfigValue( "MiscEnableGzipCompression", true );
	WriteConfigValue( "SuppressDuplicatedMessages", true );

	WriteConfigValue( "ViewMessageCharacterLimit", true );
	WriteConfigValue( "ViewEntriesPerPage", true );
	WriteConfigValue( "ViewEnableAutoReloadSeconds", true );

	WriteConfigValue( "PrependTitle", true );
	WriteConfigValue( "SearchCustomButtonCaption", true );
	WriteConfigValue( "SearchCustomButtonSearch", true );
	
	// Extra Fields
	WriteConfigValue( "DefaultViewsID", true );
	WriteConfigValue( "DefaultSourceID", true );
	
	// GLOBAL ONLY
	WriteConfigValue( "DebugUserLogin", true );
	WriteConfigValue( "MiscDebugToSyslog", true );
}

function SaveUserGeneralSettingsIntoDB()
{
	global $content;

	WriteConfigValue( "ViewDefaultLanguage", false, $content['SESSION_USERID']);
	WriteConfigValue( "ViewDefaultTheme", false, $content['SESSION_USERID'] );

	WriteConfigValue( "ViewUseTodayYesterday", false, $content['SESSION_USERID'] );
	WriteConfigValue( "ViewEnableDetailPopups", false, $content['SESSION_USERID'] );
	WriteConfigValue( "EnableIPAddressResolve", false, $content['SESSION_USERID'] );
	WriteConfigValue( "MiscShowDebugMsg", false, $content['SESSION_USERID'] );
	WriteConfigValue( "MiscShowDebugGridCounter", false, $content['SESSION_USERID'] );
	WriteConfigValue( "MiscShowPageRenderStats", false, $content['SESSION_USERID'] );
	WriteConfigValue( "MiscEnableGzipCompression", false, $content['SESSION_USERID'] );
	WriteConfigValue( "SuppressDuplicatedMessages", false, $content['SESSION_USERID'] );

	WriteConfigValue( "ViewMessageCharacterLimit", false, $content['SESSION_USERID'] );
	WriteConfigValue( "ViewEntriesPerPage", false, $content['SESSION_USERID'] );
	WriteConfigValue( "ViewEnableAutoReloadSeconds", false, $content['SESSION_USERID'] );

	WriteConfigValue( "PrependTitle", false, $content['SESSION_USERID'] );
	WriteConfigValue( "SearchCustomButtonCaption", false, $content['SESSION_USERID'] );
	WriteConfigValue( "SearchCustomButtonSearch", false, $content['SESSION_USERID'] );
	
	// Extra Fields
	WriteConfigValue( "DefaultViewsID", false, $content['SESSION_USERID'] );
	WriteConfigValue( "DefaultSourceID", false, $content['SESSION_USERID'] );
}


function GetConfigSetting($szSettingName, $szDefaultValue = "", $DesiredConfigLevel = CFGLEVEL_GLOBAL)
{
	global $content, $CFG, $USERCFG;

	if ( isset($CFG['UserDBEnabled']) && $CFG['UserDBEnabled'] )
	{
		if ( $DesiredConfigLevel == CFGLEVEL_USER )
		{
			// only use user settings if desired by the user
			if ( isset($USERCFG['UserOverwriteOptions']) && $USERCFG['UserOverwriteOptions'] == 1 ) 
			{
				// return user specific setting if available
				if ( isset($USERCFG[$szSettingName]) ) 
					return $USERCFG[$szSettingName];
			}
		}
	}

	// Either UserDB disabled, or global setting wanted - easier handling
	if ( isset($CFG[$szSettingName]) ) 
		return $CFG[$szSettingName];
	else
		return $szDefaultValue;
}

/*
*	Helper function to get the errorCode
*/
function GetErrorMessage($errorCode)
{
	global $content;

	switch( $errorCode )
	{
		case ERROR_FILE_NOT_FOUND: 
			return $content['LN_ERROR_FILE_NOT_FOUND'];
		case ERROR_FILE_NOT_READABLE: 
			return $content['LN_ERROR_FILE_NOT_READABLE'];
		case ERROR_FILE_EOF:
			return $content['LN_ERROR_FILE_EOF'];
		case ERROR_FILE_BOF:
			return $content['LN_ERROR_FILE_BOF'];
		case ERROR_FILE_CANT_CLOSE:
			return $content['LN_ERROR_FILE_CANT_CLOSE'];
		case ERROR_UNDEFINED:
			return $content['LN_ERROR_UNDEFINED'];
		case ERROR_EOS:
			return $content['LN_ERROR_EOS'];
		case ERROR_NOMORERECORDS:
			return $content['LN_ERROR_NORECORDS'];
		case ERROR_FILTER_NOT_MATCH:
			return $content['LN_ERROR_FILTER_NOT_MATCH'];
		case ERROR_DB_CONNECTFAILED:
			return $content['LN_ERROR_DB_CONNECTFAILED'];
		case ERROR_DB_CANNOTSELECTDB:
			return $content['LN_ERROR_DB_CANNOTSELECTDB'];
		case ERROR_DB_QUERYFAILED:
			return $content['LN_ERROR_DB_QUERYFAILED'];
		case ERROR_DB_NOPROPERTIES:
			return $content['LN_ERROR_DB_NOPROPERTIES'];
		case ERROR_DB_INVALIDDBMAPPING:
			return $content['LN_ERROR_DB_INVALIDDBMAPPING'];
		case ERROR_DB_INVALIDDBDRIVER:
			return $content['LN_ERROR_DB_INVALIDDBDRIVER'];
		case ERROR_DB_TABLENOTFOUND:
			return $content['LN_ERROR_DB_TABLENOTFOUND'];
		

		default:
			return GetAndReplaceLangStr( $content['LN_ERROR_UNKNOWN'], $errorCode );
	}
}

?>
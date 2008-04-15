<?php
/*
	*********************************************************************
	* -> www.phplogcon.org <-											*
	* -----------------------------------------------------------------	*
	* Theme specific functions											*
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

function CreateLanguageList()
{
	global $gl_root_path, $content;

	$alldirectories = list_directories( $gl_root_path . "lang/");
	for($i = 0; $i < count($alldirectories); $i++)
	{
		// --- gen_lang
		$content['LANGUAGES'][$i]['langcode'] = $alldirectories[$i];
		if ( $content['gen_lang'] == $alldirectories[$i] )
			$content['LANGUAGES'][$i]['selected'] = "selected";
		else
			$content['LANGUAGES'][$i]['selected'] = "";
		// ---

		// --- user_lang
		$content['USERLANG'][$i]['langcode'] = $alldirectories[$i];
		if ( $content['user_lang'] == $alldirectories[$i] )
			$content['USERLANG'][$i]['is_selected'] = "selected";
		else
			$content['USERLANG'][$i]['is_selected'] = "";
		// ---

	}
}

function CreateThemesList()
{
	global $gl_root_path, $content;

	$alldirectories = list_directories( $gl_root_path . "themes/");
	for($i = 0; $i < count($alldirectories); $i++)
	{
		// --- web_theme
		$content['STYLES'][$i]['StyleName'] = $alldirectories[$i];
		if ( $content['web_theme'] == $alldirectories[$i] )
			$content['STYLES'][$i]['selected'] = "selected";
		else
			$content['STYLES'][$i]['selected'] = "";
		// ---

		// --- user_theme
		$content['USERSTYLES'][$i]['StyleName'] = $alldirectories[$i];
		if ( $content['user_theme'] == $alldirectories[$i] )
			$content['USERSTYLES'][$i]['is_selected'] = "selected";
		else
			$content['USERSTYLES'][$i]['is_selected'] = "";
		// ---
	}
}

function list_directories($directory) 
{
	$result = array();
	if (! $directoryHandler = @opendir ($directory)) 
		DieWithFriendlyErrorMsg( "list_directories: directory \"$directory\" doesn't exist!");

	while (false !== ($fileName = @readdir ($directoryHandler))) 
	{
		if	( is_dir( $directory . $fileName ) && ( $fileName != "." && $fileName != ".." ))
			@array_push ($result, $fileName);
	}

	if ( @count ($result) === 0 ) 
		DieWithFriendlyErrorMsg( "list_directories: no directories in \"$directory\" found!");
	else 
	{
		sort ($result);
		return $result;
	}
}

function VerifyTheme( $newtheme ) 
{ 
	global $gl_root_path;

	if ( is_dir( $gl_root_path . "themes/" . $newtheme ) )
		return true;
	else
		return false;
}

?>

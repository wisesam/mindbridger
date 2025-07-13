<?PHP
/*VWMLDBM DISCLAIMER*==================================================
 Copyright (c) 2017 Sang Jin Han and other contributors, 
	http://wise4edu.com
 Released under the MIT license
 =================================================*VWMLDBM DISCLAIMER*/
/*
  Description : DB Connection
*/
if(!isset($_SESSION)) session_start();
// error_reporting(~E_ALL); // No error message

/// DB and Table Prefix
$VWMLDBM['DB']="_INSTALL_DB_name"; // to be updated by setup
$VWMLDBM['DB_user']="_INSTALL_DB_user"; // to be updated by setup
$VWMLDBM['TB_prefix']="_INSTALL_TB_prefix"; // to be updated by setup
$VWMLDBM['DB_pwd']="_INSTALL_DB_pwd"; // to be updated by setup
$VWMLDBM['VWMLDBM_RT']="_INSTALL_VWMLDBM_RT"; // to be updated by setup
$VWMLDBM['VWMLDBM_WWW_RT']="_INSTALL_VWMLDBM_WWW_RT"; // to be updated by setup
$VWMLDBM['MULTI_INST'] = false; 
$VWMLDBM['USER_TB']='users'; // the application's user table
$VWMLDBM['BOOK_TB']='book'; // the application's book table
$VWMLDBM['MAX_BOOK_DOWNLOAD']=5; // Max number of books to download at once

$GLOBALS['VWMLDBM']=$VWMLDBM;
$GLOBALS['DB']=$VWMLDBM['DB'];
$GLOBALS['TB_PRE']=$VWMLDBM['TB_prefix']; 
$GLOBALS['DTB_PRE']=$VWMLDBM['DB'].".".$GLOBALS['TB_PRE'];
$GLOBALS['conn']=null;
try {
	$GLOBALS['conn']=mysqli_connect("localhost",$VWMLDBM['DB_user'],$VWMLDBM['DB_pwd'],$VWMLDBM['DB']);
} catch(mysqli_sql_exception $e) {}

if(!$GLOBALS['conn'] && substr($GLOBALS['DB'],0,9)!="_INSTALL_") die("Cannot connect to Mysql."); // installation script will handle DB conn error
//else if(!$conn && isset($_POST['from_step'])==false) header("Location:install/");
$VWMLDBM['DB_pwd']=null; // for security

// multi-language(JSON): menus, texts, javascript, buttons.

if(file_exists($VWMLDBM['VWMLDBM_RT']."/mlang/10.json")==false) return; // no json files

$_GET['vwmldbm_lang'] = $_GET['vwmldbm_lang'] ?? null;
$_SESSION['vwmldbm_lang'] = $_SESSION['vwmldbm_lang'] ?? 10;

if($_GET['vwmldbm_lang']) $_SESSION['vwmldbm_lang'] = $_GET['vwmldbm_lang'];
$lang=$_SESSION['vwmldbm_lang'];

if(file_exists($VWMLDBM['VWMLDBM_RT']."/mlang/".$lang.".json"))
	$GLOBALS['wmlang'] = json_decode(file_get_contents($VWMLDBM['VWMLDBM_RT']."/mlang/".$lang.".json"), true);
else if(file_exists($VWMLDBM['VWMLDBM_RT']."/mlang/10.json"))
	$GLOBALS['wmlang'] = json_decode(file_get_contents($VWMLDBM['VWMLDBM_RT']."/mlang/10.json"), true); // load the default language: English
else echo"<script> alert('Your default language json file (English) do not exsit!');</script>";

if(isset($GLOBALS['wmlang']) && count($GLOBALS['wmlang'])>0) { // escape the single qoutation marks
	foreach($GLOBALS['wmlang'] as $key_arr => $arr) 
		foreach($arr as $key =>$val) $GLOBALS['wmlang'][$key_arr][$key]=addslashes($val);
}

// error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED); // enable error message again
?>
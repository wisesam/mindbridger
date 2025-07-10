<?PHP
/*VWMLDBM DISCLAIMER*==================================================
 Copyright (c) 2017 Sang Jin Han and other contributors, 
	http://wise4edu.com
 Released under the MIT license
 =================================================*VWMLDBM DISCLAIMER*/
namespace vwmldbm;
session_start();
set_time_limit(180); //  set the execution time limit in seconds

// ini_set('memory_limit','1024M');
ob_start();

require_once("../../../vwmldbm/config.php");

require $VWMLDBM['VWMLDBM_RT'].'../../vwmldbm/lib/code.php';
require $VWMLDBM['VWMLDBM_RT'].'../../vwmldbm/lib/file.php';
require $VWMLDBM['VWMLDBM_RT'].'../../vwmldbm/lib/img.php';
require $VWMLDBM['VWMLDBM_RT'].'../../lib/ebook.php';

if(!$_SESSION['lib_inst'] || $_SESSION['wlibrary_admin']!='A') die;

ob_end_clean();

// GET ebook list
$no=$_GET['no']; // number of bunddle of ebooks
$from_no=($no-1)*$VWMLDBM['MAX_BOOK_DOWNLOAD'];

$ebook_arr=array();
\ebook\get_ebook_list($ebook_arr,$from_no);

$num=count($ebook_arr);
foreach($ebook_arr as $key => $val) {
	$tmp=explode('_',$key);
	$rid=$tmp[0];
	$rfile=$tmp[1];
	$p=$VWMLDBM['VWMLDBM_UPLOAD_EBOOK_PATH'].$_SESSION['lib_inst']."/".$rid."/".$rfile;
	// echo "$p<br>";
	$e_arr[$rfile]['path']=$p;
	$e_arr[$rfile]['fname']=$val;

}

$zipName=date('Ymd_hmi')."($no)".".zip";

\vwmldbm\files\zipFilesAndDownload($e_arr,$zipName);

?>
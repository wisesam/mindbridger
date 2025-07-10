<?PHP
/*VWMLDBM DISCLAIMER*==================================================
 Copyright (c) 2017 Sang Jin Han and other contributors, 
	http://wise4edu.com
 Released under the MIT license
 =================================================*VWMLDBM DISCLAIMER*/
/**********************************
System environmental variables
**********************************/
namespace vwmldbm;
if(!isset($_SESSION)) session_start(); 

require_once("dbcon.php"); 
// require_once("lib/code.php"); 

if(!isset($conn)) return;
$_SESSION['lib_inst'] = $_SESSION['lib_inst'] ?? 1; // [TBD]
$inst=$_SESSION['lib_inst'];

if(!isset($_SESSION['vwmldbm_lang'])) $_SESSION['vwmldbm_lang']=10; // default English

$DB_list=array();  // include ones that you want to monitor and make the Relational Model (ER) diagram
$DB_list[]=$DB;
$is_DB_same_as_before=true;

$res=mysqli_query($conn,"select count(name) as num from $DTB_PRE"."_vwmldbm_db");

if($res) $rs=mysqli_fetch_array($res);
else if($rs['num']===null) {	  // initial DB creation was not done yet. 
	echo"<font color=red>You have not executed '<b>.sql</b>' yet, please first do that.</font><br>";
	die;
}
else if($rs['num']!=count($DB_list)) $is_DB_same_as_before=false;  // DB list has been changed 
else {
	$res2=mysqli_query($conn,"select name from $DTB_PRE"."_vwmldbm_db");
	if($res2) for($i=0;$i<$rs['num'];$i++){
		$rs2=mysqli_fetch_array($res2);
		if($rs2['name']!=$DB_list[$i]) {
			$is_DB_same_as_before=false;
			break;
		}
	}
}

if(!$rs['num'] || $is_DB_same_as_before==false) { // 1st time VWMLDBM or renew the table, wise2.wise2_wise_db
	echo"Renewing DBs again! Please press <b><font color=red>UPDATE</font></b> button!<br>";
	mysqli_query($conn,"delete from $DTB_PRE"."_vwmldbm_db");
	for($i=0;$i<count($DB_list);$i++){
		$sql="INSERT INTO $DTB_PRE"."_vwmldbm_db (name) values('".$DB_list[$i]."')"; 
		mysqli_query($conn,$sql);
	}
}
?>
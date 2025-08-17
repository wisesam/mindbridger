<?PHP
/*VWMLDBM DISCLAIMER*==================================================
 Copyright (c) 2017 Sang Jin Han and other contributors, 
	http://wise4edu.com
 Released under the MIT license
 =================================================*VWMLDBM DISCLAIMER*/

namespace vwmldbm;
require_once("config.php");
require_once("dbcon.php");
require_once("lib/code.php");

if(!system\isAdmin()) die("Try this again after login as an admin.");
$inst_no=$_SESSION['lib_inst'];
// Permission Control TBM
$perm['R']='Y';
$perm['A']='Y';
$perm['M']='Y';
$perm['D']='Y';

define('SYSADMIN',123); // TBM
$_SESSION['c_adm_role']=SYSADMIN;  // TBM

$en_only_cols=array(); // English only columns
$en_only_cols['code_c_utype']['max_book']=true;
$en_only_cols['code_c_utype']['max_book_rent_days']=true;
$en_only_cols['code_c_utype']['max_extend_times']=true;
$en_only_cols['code_c_utype']['w2_utype']=true;
$en_only_cols['code_c_utype']['default_utype_yn']=true;

$en_only_cols['code_c_rstatus']['direct_change_yn']=true;
$en_only_cols['code_c_rstatus']['available_yn']=true;

$en_only_cols['code_c_rent_status']['rental_terminated_yn']=true;
$en_only_cols['code_c_rent_status']['rstatus_code']=true;
$en_only_cols['code_c_rent_status']['rented_yn']=true;

$code_name=$_REQUEST['code_name']; // code name  TBM

$field_names=array(); // fields of the code
$sql="show columns from $DTB_PRE"."_".$code_name;

if($code_name =='vwmldbm_c_lang') $lang_sql=null;

$res=mysqli_query($conn,$sql);
if($res) while($rs=mysqli_fetch_array($res)) array_push($field_names,$rs['Field']);
$wasMod=false;	

$c_lang_arr=array(); // for the synchronization of all language 
code\get_code_name_all($c_lang_arr,'vwmldbm_c_lang','code',null,'ALL_LANG','Y');
unset($c_lang_arr['10']); // remove English


if($_POST['operation']=='Modify' && $perm['M']=='Y') { // modify(udpate) a code record.	
	foreach($_POST as $key => $val) $_POST[$key]=mysqli_real_escape_string($conn,$val);	
		
	foreach($field_names as $fdname){
		$t_field="h".$fdname;
		if($fdname=="code" || $fdname=="no" || $fdname=='inst' || $fdname=='c_lang') continue;
		if($code_name != 'vwmldbm_c_lang' && $fdname=="use_yn" && $_POST['c_lang']!=10) continue; // only English
		
		if($code_name=="vwmldbm_c_lang" && $fdname=="c_lang") continue;

		if($fdname=="use_yn") $_POST[$t_field]=strtoupper($_POST[$t_field]);
		
		if($code_name !='vwmldbm_c_lang') $lang_sql=" and c_lang='{$_POST['c_lang']}' ";
		$_POST_t_field = ($_POST[$t_field] ? "'".$_POST[$t_field]."'" : 'NULL');
		$sql="update $DTB_PRE"."_$code_name set $fdname={$_POST_t_field} where inst='$inst' and code='{$_POST['code']}' $lang_sql";		

		mysqli_query($conn,$sql);
		if(mysqli_affected_rows($conn)>0) $wasMod=true;
	}	
	//code info was successfully modified, so refresh the page
		
	// now modify other language use_yn	
	if($code_name == "vwmldbm_c_lang") {
		$sql="update $DTB_PRE"."_$code_name set use_yn='{$_POST['huse_yn']}' where inst='$inst' and code='{$_POST['code']}'";	
	} else $sql="update $DTB_PRE"."_$code_name set use_yn='{$_POST['huse_yn']}' where inst='$inst' and code='{$_POST['code']}' and c_lang<>'10'";	

	mysqli_query($conn,$sql);
	if(mysqli_affected_rows($conn)>0) $wasMod=true;
	// End of add other language use_yn		
	
	if($wasMod) {
		echo "<script>alert('[Success] {$_POST['code']} was modified. ');</script>";
		echo "<script>
			window.parent.document.form1.operation.value=window.parent.document.form1.operation.value;
			window.parent.document.form1.frame_op.value='Modify';
			window.parent.document.form1.code_name.value='{$_POST['code_name']}';
			window.parent.document.form1.submit();
			</script>"; 
	}
	else echo "<script>alert('No data modfied.');</script>";
}
else if($_POST['operation']=='Delete' && $perm['D']=='Y') { // delete a code record
	if($code_name !='vwmldbm_c_lang' && $_POST['c_lang']!=10) $lang_sql=" and c_lang='{$_POST['c_lang']}' ";
	$sql="delete from $DTB_PRE"."_$code_name where code='{$_POST['code']}' and inst='$inst' $lang_sql";

	mysqli_query($conn,$sql);
	if(mysqli_affected_rows($conn)>0) $wasMod=true;
	
	if(!$wasMod) echo "<script>alert('{$_POST['code']} was not deleted!');</script>";
	else { //code info was successfully deleted, so refresh the page
		echo "<script>alert('[Success] {$_POST['code']} was successfully deleted.');</script>";
		echo "<script>
			window.parent.document.form1.operation.value=window.parent.document.form1.operation.value;
			window.parent.document.form1.frame_op.value='Delete';
			window.parent.document.form1.code_name.value='{$_POST['code_name']}';
			window.parent.document.form1.submit();
			</script>"; 
	}
}
else if($_POST['operation']=='Add' && $perm['A']=='Y') { // Add a code record
	if($code_name=='vwmldbm_c_lang')
		$sql="insert into $DTB_PRE"."_$code_name values('$inst','{$_POST['n_code']}'";
	else $sql="insert into $DTB_PRE"."_$code_name values('$inst','{$_POST['n_code']}','10'";
	
	foreach($field_names as $val){
		if($val=='inst' || $val=='c_lang') continue;
		if($code_name=='vwmldbm_c_lang' && $val=='code') continue;
		if($val!="code") {
			$tname="n_".$val;
			if($val=="use_yn") $_POST[$tname]=strtoupper($_POST[$tname]);
			$sql.=",'".$_POST[$tname]."'";
		}
	}
	$sql.=")";

	mysqli_query($conn,$sql);
	if(mysqli_affected_rows($conn)>0) $wasMod=true;
	
	if(!$wasMod) echo "<script>alert('[Fail] {$_POST['n_code']} was not added!');</script>";
	else { //code info was successfully added, so refresh the page
		// now add other language records			
			if($code_name!='vwmldbm_c_lang') {
				foreach($c_lang_arr as $c) {
					if($c==10) continue;
					$sql="insert into $DTB_PRE"."_$code_name values('$inst','{$_POST['n_code']}','$c'";
					foreach($field_names as $val){
						if($val=='inst' || $val=='c_lang') continue;
						if($val!="code") {
							$tname="n_".$val;
							if($val=="use_yn") $_POST[$tname]=strtoupper($_POST[$tname]);
							$sql.=",'".$_POST[$tname]."'";
						}
					}
					$sql.=")";

					mysqli_query($conn,$sql);
				}
			}
		// End of add other language records
				
		echo "<script>alert('[Success] {$_POST['n_code']} was added.');</script>";
		echo "<script>
			window.parent.document.form1.operation.value=window.parent.document.form1.operation.value;
			window.parent.document.form1.frame_op.value='Add';
			window.parent.document.form1.code_name.value='{$_POST['code_name']}';
			window.parent.document.form1.submit();
			</script>"; 
	}
}
else if($_POST['operation']=='ADD_CODE_SET' && $perm['A']=='Y') {
	if($_POST['code_name']=='vwmldbm_c_lang') $code_add_success=add_langs();
	else $code_add_success=add_code_set($code_name);
	
	if($code_add_success) {
		echo "<script>alert('[Success] {$_POST['n_code']} added.');</script>";
		echo "<script>
			window.parent.document.form1.operation.value=window.parent.document.form1.operation.value;
			window.parent.document.form1.frame_op.value='Add';
			window.parent.document.form1.code_name.value='{$_POST['code_name']}';
			window.parent.document.form1.submit();
			</script>"; 
	}
}

// Create the codes whose language was added or enabled
if($code_name!='vwmldbm_c_lang') check_n_create_lang_code($code_name,$c_lang_arr);

	
// Update non visible fields in english only codes set same as the values of Enlgish code
if(isset($en_only_cols[$code_name])) {
	update_en_only_cols_non_visible_fields($code_name,$en_only_cols);
}

$codes=array(
	"vwmldbm_c_lang" =>"Language",
	"code_c_utype" =>"User Category",
	"code_c_rtype" =>"Resource Type",
	"code_c_genre" =>"Genre",
	"code_c_grade" =>"Grade",
	"code_c_category" =>"Category",
	"code_c_category2" =>"Category2",
	"code_c_rstatus" =>"Resource Status",
	"code_c_rent_status" =>"Rent Status",
	"code_c_code_set" =>"Code Setting"
);
?>
  
<html>
<head>
<title></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">

<link href="css/common.css" rel="stylesheet" type="text/css" />
</head>
  
<body style="text-align: center;">
<center><h3> Code Name: <?=$code_name?> (<?=$codes[$code_name]?>)</h3></center>
<?PHP
if($code_name=='code_c_code_set') {
	echo "<h3 style='color:red;'>If you don't know what this code does, do NOT touch except 'use_yn'!</h3>";
	echo "<h3 style='color:magenta;'>If you set 'user_yn' 'No', it will not be displayed in Book list</h3>";
	
}

?>
<form name="form1" method="POST"  onSubmit="return checkForm(this);">
  <input type='hidden' name="operation">
  <input type='hidden' name="frame_op">
  <input type='hidden' name="code_name">
  <input type='hidden' name="code" value="<?=$_REQUEST['code']?>">
  <input type='hidden' name="c_lang" value="<?=$_REQUEST['c_lang']?>">
<?php
	foreach($field_names as $val) if($val!="code" && $val!='inst' && $val!='c_lang') echo "<input type='hidden' name='h$val'>"; // print a hidden field for each field of the code except code itself
?>

<?php
	// show add default code set button when there is no code (for multi-insitution mode)
	if($VWMLDBM['MULTI_INST']) {
		$code_arr=array();
		code\get_code_name_all($code_arr,$code_name,null,null,'ALL_LANG');
		if(!count($code_arr)) { // no codes, so show the button
			echo "<p><button type='button' onclick='add_code_set()' style='background:magenta;color:white;padding:5px;'>Add default code set</button></p>";
			echo "
			<script>
				function add_code_set() {
					if(confirm('Do you want to add code sets?')){
						document.form1.operation.value='ADD_CODE_SET';
						document.form1.code_name.value='$code_name'; 
						document.form1.submit();
					}
				}					
			</script>
			";
		}
	}
?>
<table border="1" align="center" cellpadding="5" cellspacing="0" bgcolor="#DFDFDF" style="border:0px #333333 solid;border-top-width:1px;">

<?
// add a new code record
// field names
if($perm['A']=='Y' && strtoupper($_SESSION['c_adm_role'])==SYSADMIN) {
	echo "<tr>";
	echo "<th>".\vwmldbm\code\get_field_name("$code_name",'code')."</th>";
	echo "<th>".\vwmldbm\code\get_field_name("$code_name",'c_lang')."</th>";
	echo "<th>".\vwmldbm\code\get_field_name("$code_name",'use_yn')."</th>";
	
	foreach($field_names as $val) {
		if($val!='inst' && $val!='use_yn' && $val!='code' && $val!='c_lang') {
			echo "<th>".\vwmldbm\code\get_field_name("$code_name",$val)."</th>";
		}
	}
	
	echo "<th></th></tr>";
	echo "<tr>";
	
	echo "<td><input type=text name='n_code' size=8></td>";
	
	echo "<td>";
	\vwmldbm\code\print_lang(10,'n_c_lang','Y',null,'ONE');
	echo "<td>".\vwmldbm\code\print_c_yn($code_name,'Y','n_use_yn')."</td>";
	
	foreach($field_names as $val) {
		if($val=='name') $ln=20;
		else $ln=8; // default
		if(substr($val,-3,3)=='_yn' and $val!='use_yn'){ // Y/N code
			echo "<td>";
			echo \vwmldbm\code\print_c_yn($val,null,'n_'.$val);
			echo "</td>";
			continue;
		}
			
		if($val!='inst' && $val!='use_yn' && $val!='code' && $val!='c_lang')
			echo "<td><input type=text name='n_".$val."' size='$ln'></td>";	
	}

	echo "<td>";
	echo "<img src='img/add.png' class='img_button' ";
	echo "onClick=\" document.form1.operation.value='Add'; 
					 document.form1.code_name.value='$code_name'; 
					 document.form1.submit(); \"";
	echo "</td></tr>";
}
?>
</table>
</form>
<br>
<form name="form2" method="POST">
<table border="1" align="center" cellpadding="5" cellspacing="0" bgcolor="#DFDFDF" style="border:0px #333333 solid;border-top-width:1px;">
<?PHP
// retrieve code info
// field names

echo "<tr><th></th>";
echo "<th>".\vwmldbm\code\get_field_name($code_name,'use_yn')."</th>";
foreach($field_names as $val) if($val!='inst' && $val!='use_yn') {
	echo "<th>".code\get_field_name($code_name,$val)."</th>";
}
echo "</tr>";

// data
if($code_name=='vwmldbm_c_lang')
	$sql="select * from {$DTB_PRE}_{$code_name} where inst='$inst' order by code asc";
else $sql="select * from {$DTB_PRE}_{$code_name} where inst='$inst' order by c_lang, code asc";

$res=mysqli_query($conn,$sql);
$cnt = 0;
if($res)while($rs=mysqli_fetch_assoc($res)){
	echo "<tr>";
	$cnt++;
  // update a code record	
	echo "<td align='center'>";
	if($perm['M']=='Y' && strtoupper($_SESSION['c_adm_role'])==SYSADMIN) {
		echo "<img src='img/ok.png' class='img_button' 
	      onClick=\" document.form1.operation.value='Modify'; 
		  document.form1.code_name.value='$code_name'; document.form1.code.value='{$rs['code']}'; document.form1.c_lang.value='{$rs['c_lang']}';";
		foreach($field_names as $val_fd) {
			if($val_fd!='code' && $val_fd!='inst' && $val_fd!='c_lang') {
				if($val_fd=='use_yn' && $rs['c_lang']!=10 && $code_name!='vwmldbm_c_lang') continue; // non-English codes should be passed
				
				if($en_only_cols[$code_name][$val_fd]) echo "document.form1.h$val_fd.value=document.form2.".$val_fd."_".$rs['code']."_10.value;";
				else echo "document.form1.h$val_fd.value=document.form2.".$val_fd."_".$rs['code']."_{$rs['c_lang']}.value;";
			}
		}
		echo "document.form1.submit();\">";
	}
  // delete the code record	
  
	if(($code_name == 'vwmldbm_c_lang' || $rs['c_lang']=='10' || ($code_name!='vwmldbm_c_lang' && array_search($rs['c_lang'],$c_lang_arr)===false)) && $perm['D']=='Y' && strtoupper($_SESSION['c_adm_role'])==SYSADMIN) { // 10: Enlgish
		echo "<img src='img/delete.png' class='img_button' ";
		echo "onClick=\" document.form1.operation.value='Delete'; 
		  document.form1.code_name.value='$code_name'; document.form1.code.value='{$rs['code']}';  document.form1.c_lang.value='{$rs['c_lang']}'; del_confirm(); \"";
	}
	echo "</td>";
	
 // display the code record
	if($code_name == 'vwmldbm_c_lang') { // modification is possible in vwmldbm_c_lang
		echo "<td>";
		echo \vwmldbm\code\print_c_yn("use_yn_{$rs['code']}_",$rs['use_yn']);
		echo "</td>";
	}
	else if($rs['c_lang']=='10') { // modification is only possible for English
		echo "<td>";
		echo \vwmldbm\code\print_c_yn("use_yn_{$rs['code']}_10",$rs['use_yn']);
		echo "</td>";
	}
	else echo "<td></td>";
	
	foreach($rs as $key=>$val) {
		if($key=='use_yn') continue;
		$readonly="";
		$rs[$key]=htmlspecialchars($val);
		if(strlen($val)>0 && strlen($val)>3)$tlen=strlen($val)-2; // adjust the input box size
		else $tlen=4;
		if($key=='code' || $key=='c_lang') $readonly=" readonly";
		if($key!='inst') {
			if($rs['c_lang']!=10 && $en_only_cols[$code_name][$key]) echo "<td></td>";
			else {
				if(substr($key,-3,3)=='_yn'){ // Y/N code
					echo "<td>";
					echo \vwmldbm\code\print_c_yn($key,$val,"{$key}_{$rs['code']}_{$rs['c_lang']}");
					echo "</td>";
				}
				else if($key =='c_lang') {
					echo "<td>";
					\vwmldbm\code\print_lang($rs['c_lang'],"{$key}_{$rs['code']}_{$rs['c_lang']}",null,null,'ONE');
					echo "</td>";
				}
				else echo "<td><input type='text' name='{$key}_{$rs['code']}_{$rs['c_lang']}' size='$tlen' value='$val' $readonly></input></td>";
			}
		}
	}
	echo "</tr>";
}

?>
</table>
<div><b>Total : <?=$cnt?></b></div>
<!-- end of main table -->
</form>
<br>
<table width="500" border="0" align="center" cellpadding="0" cellspacing="0">
<tr> 
  <td align="center"><font color="#FF0000">*</font>Mandatory Field</td>
</tr>
</table>

<script>
function del_confirm(){
	if(confirm('Do you want to delete this code?')) document.form1.submit()
}

function checkForm(theForm) {
	theForm.operation.value="Modify";
	return true;
}
</script> 
</body>
</html>

<?PHP
function update_en_only_cols_non_visible_fields($code_name,$en_only_cols) {
	global $inst,$DTB_PRE,$conn;
	foreach($en_only_cols[$code_name] as $fd => $val ) {
		if($val) {
			$sql="select code,$fd from {$DTB_PRE}_{$code_name} where inst='$inst' and c_lang=10";
			$res=mysqli_query($conn,$sql);
			if($res) while($rs=mysqli_fetch_array($res)) {
				$rsFd = (isset($rs[$fd]) ? "'".$rs[$fd]."'" : 'NULL');
				$sql2="update {$DTB_PRE}_{$code_name} set $fd=$rsFd where inst='$inst' and code='{$rs['code']}' and c_lang<>10";		
				mysqli_query($conn,$sql2);
			}
		}
	}
}

function check_n_create_lang_code($code_name,$c_lang_arr) {
	global $inst,$DTB_PRE,$conn;
	
	// first get the code list of the mother code
	$code_list=array();
	$sql="select * from {$DTB_PRE}_{$code_name} where inst='$inst' and c_lang='10'";
	$res=mysqli_query($conn,$sql);
	if($res) while($rs=mysqli_fetch_array($res)){
		$code_list[$rs['code']]=$rs;
	}
	
	foreach($code_list as $c => $val) {
		foreach($c_lang_arr as $ln) {
			$sql="select * from {$DTB_PRE}_{$code_name} where inst='$inst' and code='$c' and c_lang='$ln'";
			$res2=mysqli_query($conn,$sql);
			if($res2) $rs2=mysqli_fetch_array($res2);
			if($code_name== "code_c_category") continue; // [TBM] pass the complex code 
			if(!$rs2['code']) { // not exist, so add one				
				$sql="insert into $DTB_PRE"."_$code_name (inst,code,c_lang,name,use_yn) 
					values('$inst','$c','$ln','{$val['name']}','{$val['use_yn']}')";
				mysqli_query($conn,$sql);
				//mysqli_affected_rows($conn);
			}
		}
	}	
}

function add_code_set($code_name) {
	global $DTB_PRE,$conn;
	$inst=$_SESSION['lib_inst'];
	$mod_ok=false;
	// first get the code list of the mother code
	$code_list=array();
	$sql="select * from {$DTB_PRE}_{$code_name} where inst='1'";
	$res=mysqli_query($conn,$sql);
	if($res) while($rs=mysqli_fetch_array($res)){
		$code_list[$rs['code']."__".$rs['c_lang']]=$rs;
	}
	foreach($code_list as $key => $val) {		
		$sql="insert into $DTB_PRE"."_$code_name (inst,code,c_lang,name,use_yn) 
			values('$inst','{$val['code']}','{$val['c_lang']}','{$val['name']}','{$val['use_yn']}')";
		mysqli_query($conn,$sql);
		if(mysqli_affected_rows($conn)) $mod_ok=true;
	}
	if($mod_ok) return true;
}

function add_langs() {
	global $DTB_PRE,$conn;
	$inst=$_SESSION['lib_inst'];
	$mod_ok=false;
	$code_name='vwmldbm_c_lang';
	// first get the code list of the mother code
	$code_list=array();
	$sql="select * from {$DTB_PRE}_{$code_name} where inst='1'";
	$res=mysqli_query($conn,$sql);
	if($res) while($rs=mysqli_fetch_array($res)){
		$code_list[$rs['code']]=$rs;
	}
	foreach($code_list as $key => $val) {		
		$sql="insert into $DTB_PRE"."_$code_name (inst,code,name,use_yn,n_name,ccode,priority) 
			values('$inst','$key','{$val['name']}','{$val['use_yn']}','{$val['n_name']}','{$val['ccode']}','{$val['priority']}')";
		mysqli_query($conn,$sql);
		$mod_ok=mysqli_affected_rows($conn);
	}
	
	if($mod_ok) return true;
}
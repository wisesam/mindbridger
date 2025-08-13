<?PHP
/*VWMLDBM DISCLAIMER*==================================================
 Copyright (c) 2017 Sang Jin Han and other contributors, 
	http://wise4edu.com
 Released under the MIT license
 =================================================*VWMLDBM DISCLAIMER*/
?>
<? 
namespace vwmldbm;
require_once("config.php");
require_once("dbcon.php");
require_once("lib/code.php");
require_once("lib/system.php"); 

if(!system\isAdmin()) die("Try this again after login as an admin.");
// Permission Control TBM
$perm['R']='Y';
$perm['A']='Y';
$perm['M']='Y';
$perm['D']='Y';

$inst_no=$_SESSION['lib_inst'];


$codes=array(		
	"Language"=>array("vwmldbm_c_lang","name","TBS"),
	"User Category"=>array("code_c_utype","name","TBS"),
	"Resource Type"=>array("code_c_rtype","name","TBS"),
	"Genre"=>array("code_c_genre","name","TBS"),
	"Grade"=>array("code_c_grade","name","TBS"),
	"Category"=>array("code_c_category","name","TBS"),
	"Category2"=>array("code_c_category2","name","TBS"),
	"Resource Status"=>array("code_c_rstatus","name","TBS"),
	"Rental Status"=>array("code_c_rent_status","name","TBS"),
	"Code Setting"=>array("code_c_code_set","name","TBS")
);

?>
<html>
<head>
<title></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">

<link href="css/common.css" rel="stylesheet" type="text/css" />
<link rel='stylesheet' href='lib/jquery/ui/1.12.1/jquery-ui.css'>
<script src='lib/jquery/jquery-3.2.1.min.js'></script>
<script src='lib/jquery/ui/1.12.1/jquery-ui.min.js'></script>
</head>
  
<body style="text-align: center;">
<center><h3>Code List</h3></center>

  <script>
  $(document).ready(function() { // jQuery Dialog: change the size as you want
    $( "#dialog" ).dialog({
		width:'100%',height:640,
		autoOpen: false,
	});
	<?PHP
	if($_POST['frame_op']=='Modify' || $_POST['frame_op']=='Delete' || $_POST['frame_op']=='Add') { // when  code record was modified- to keep opening the working code window
		if($_REQUEST['highlightID']) $hParam="highlightID=".$_REQUEST['highlightID']."&";	
		echo "$('#dialog').dialog('open'); 
		document.getElementById('iframe').src='code_manage.php?".$hParam."code_name=".$_REQUEST['code_name']."&inst=".$_POST['inst']."';
		";
	}
	else echo "$('#dialog').dialog('close');"
	?>
	$('.open_modify').click(function(){ // modify a code set
        $('#dialog').dialog('open');
		document.getElementById('iframe').src="code_manage.php?code_name="+document.form1.code_name.value;
    });	
  });
 <?PHP
  echo "
	  function open_modify(code,inst){
		$('#dialog').dialog('open');
		document.getElementById('iframe').src='code_manage.php?code_name='+code+'&inst='+inst;
	  }
  ";
 ?>
  </script>
<div id="dialog" title="Code Operations" style="display:none;"> <!-- jQuery Dialog: iframes-->
  <iframe id='iframe' frameborder=0 width='100%' height='100%'></iframe>
</div>

<form method="post" name="form1" style="margin:0px;" onsubmit="document.form1.operation.value='Retrieve';">
<p style="text-align:center;"><font color=red>* <?=$wmlang['txt']['to_be_setup']?></font></p>
<p align=center>
<table border=1>
<tr>
	<th><?=$wmlang['txt']['code']?> <?=$wmlang['txt']['name']?></th>
	<th><?=$wmlang['txt']['code']?></th>
	<th># <?=$wmlang['txt']['code']?></th>
	<th># Y</th>
	<th></th>
</tr>
<?PHP
$cnt=0;
foreach($codes as $key => $value){  // in-DB system codes
	if($installMode && ($value[0]=='code_c_utype')) continue; // skip User Type code in install mode [TBM]
	echo "<tr>";
		echo "<td>";
		if($value[2]=='TBS') // To be setup
			echo "<font color=red>*</font> ";
		
		if($value[0]=='code_c_code_set') echo "<font color='magenta'>$key</font></td>";
		else echo "$key</td>";
		
		echo "<td>";
			echo code\print_code($value[0],null,null,$value[1],null,null,null,'ALL_LANG');
		echo "</td>";
		
		echo "<td align=center>";
			$tnum=code\get_code_stat($value[0]);
			if($tnum<1) echo "<font color='red'><b>$tnum</b></font>";
			else echo $tnum;
		echo "</td>";		
		echo "<td align=center>";
			$ynum=code\get_code_stat($value[0],null,'USE_YN_Y');
			if($ynum<1) echo "<font color='red'><b>$ynum</b></font>";
			else echo $ynum;
		echo "</td>";
		
		echo "<td>";
		echo "<img class='open_modify' src='img/set.png' id='img_button_x' ";
		echo "onClick=\"document.form1.code_name.value='$value[0]'\">";
		echo "</td>";	
	echo "</tr>";	  
	$cnt++;
}
// in-library system codes

?>
</table>
<br>
<b><?=$wmlang['txt']['total']?> : <?=$cnt?></b>
</p>
	<input type='hidden' name='operation' value='<?=$_POST['operation']?>'>
	<input type='hidden' name='code_name'> <!-- for manage_code.php -->
	<input type='hidden' name='frame_op'> 
	<input type='hidden' name='inst'>
	<input type='hidden' name='highlightID'> 
</form>
</body>
</html>
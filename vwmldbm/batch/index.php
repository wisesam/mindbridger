<?PHP
/*VWMLDBM DISCLAIMER*==================================================
 Copyright (c) 2017 Sang Jin Han and other contributors, 
	http://wise4edu.com
 Released under the MIT license
 =================================================*VWMLDBM DISCLAIMER*/
namespace vwmldbm;
session_start();
set_time_limit(180); //  set the execution time limit in seconds

// error_reporting(E_ALL);
// ini_set('display_errors', 1);

require_once("../config.php");

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
require_once $VWMLDBM['VWMLDBM_RT'].'/lib/lib_batch_data.php';

require $VWMLDBM['VWMLDBM_RT'].'/lib/code.php';
require $VWMLDBM['VWMLDBM_RT'].'/lib/file.php';
require $VWMLDBM['VWMLDBM_RT'].'/lib/img.php';
require $VWMLDBM['VWMLDBM_RT'].'../../lib/ebook.php';

if(!$_SESSION['lib_inst'] || $_SESSION['wlibrary_admin']!='A') die;

// Check if the batch directory exists
if(!file_exists($VWMLDBM['VWMLDBM_UPLOAD_PATH']."/app/batch/".$_SESSION['lib_inst'])){
	mkdir($VWMLDBM['VWMLDBM_UPLOAD_PATH']."/app/batch/".$_SESSION['lib_inst']);
}

// File Size
$post_max_size=files\post_max_size('bytes');
$upload_max_filesize=files\upload_max_filesize('bytes');
$max_file_uploads=files\max_file_uploads();
$MAX_FSIZE=$upload_max_filesize;  // set this to limit size
	
$inst_var=new code\Inst_var();

$hdoc=new code\Hdoc();
$hdoc->print_body_tag();
$hdoc->print_head();
$hdoc->print_title($wmlang['menu']['batch_data']);

$perm['A']='Y'; // TBD
?>
<script src="../lib/jquery/jquery.table2excel.min.js?nocache=1"></script>

<center>

<form method="post" name="form1" style="margin:0px;" enctype="multipart/form-data">
 <div style="width:500px;text-align:cener;">
  <h2>1. <?=$wmlang['txt']['sel_target_data']?></h2>
  <fieldset>
    <input type="radio" name="radio-1" id="radio-1" value='user' onClick='enable_download();hide_bc_opt();'> 
    <label for="radio-1" > 1.<?=$wmlang['menu']['user']?></label>&nbsp;&nbsp;&nbsp;&nbsp; 
    
	<input type="radio" name="radio-1" id="radio-2" value='book' onClick='enable_download();hide_bc_opt();'>
	<label for="radio-2"> 2.<?=$wmlang['menu']['book']?></label>&nbsp;&nbsp;&nbsp;&nbsp; 
     
	<input type="radio" name="radio-1" id="radio-3" value='book_copy' onClick='show_book_copy_options();'>
	<label for="radio-3"> 3.<?=$wmlang['menu']['book_copy']?></label>
	
	<p id='bc_opt' style='display:none; border:solid gray 1px; width:300px;padding: 4px 4px 4px 4px'>
		<input type="radio" name="radio-bc" id="radio-bc-1" value="EXISTING" onClick='enable_download()'> 
		<label for="radio-bc-1" > 1.<?=$wmlang['txt']['existing']?></label>&nbsp;&nbsp;&nbsp;&nbsp;
		
		<input type="radio" name="radio-bc" id="radio-bc-2" value="TO_BE_ADDED" onClick='enable_download()'> 
		<label for="radio-bc-2" > 2.<?=$wmlang['txt']['to_be_added']?></label>&nbsp;&nbsp;&nbsp;&nbsp; 
		
		<input type="radio" name="radio-bc" id="radio-bc-3" value="ALL" onClick='enable_download()'> 
		<label for="radio-bc-3" > 3.<?=$wmlang['txt']['all']?></label>
	</p>
    
  </fieldset>
  <p>
	<button type='button' id='dbutton' disabled onClick='download_sheet();'><?=$wmlang['txt']['download']?></button>
  </p>
 </div>
 <br>
   <h2>2. <?=$wmlang['txt']['upload_check']?></h2>
  <div style="width:500px;text-align:cener;">
		
<?PHP
	if($perm['A']=='Y'){
		echo "<label for='upload'>".$wmlang['txt']['add_attach']."</label>";
		echo "<input id='upload' name='upload' type='file' />";

		echo "<input type='image' src='{$VWMLDBM['VWMLDBM_WWW_RT']}/img/add.png' class='img_button' id='add_button' style='border:0'
			onClick=\"
				if(document.form1.upload.value=='') {  
					alert('".$wmlang['js']['warning']." ".$wmlang['txt']['select_file']."'); 
					return false;
				}
				else {
					document.form1.operation.value='upload'; 
					document.form1.submit();
				}\">
			";
	}
?>
  </div>
  <div style="width:'98%';text-align:center;">
<?PHP
	if($_POST['operation']=='upload'){
		
		$file_ok=true;
		
		$check_arr=array();
		$check_arr['worksheet']=false;
		$check_arr['data']=false;
		$check_arr['target']=null;
		$check_arr['mod']=false; // if set true, proceed to execution
				
		$filename = $_FILES['upload']['name'];
		$file_basename = substr($filename, 0, strripos($filename, '.')); // get file extention
		$file_ext = substr($filename,-4,4); 
		$allowed_file_types = array('xlsx');	
		$filesize = $_FILES["upload"]["size"];
		
		if (in_array(strtolower($file_ext),$allowed_file_types)) {		
			//Get the temp file path
			$tmpFilePath = $_FILES['upload']['tmp_name'];
		}
		else $file_ok=false;
		
		if($filesize>$MAX_FSIZE){
			$file_ok=false;
		}
		
		if($file_ok) { // file upload checking was okay
			if($tmpFilePath != ""){            
				$destname = $_FILES['upload']['name'];
				
			  // this is a temporary file name	
				$filePath = $VWMLDBM['VWMLDBM_UPLOAD_PATH']."/app/batch/{$_SESSION['lib_inst']}/".$destname; 

				//Upload the file into the temp dir
				if(move_uploaded_file($tmpFilePath, $filePath)) {
					$msg= "<h3><font color='blue'>".$wmlang['js']['success'].$wmlang['txt']['upload']." $destname</font></h3>";
				} 
				else {
					$msg= "<h3><font color='red'>".$wmlang['js']['error'].$wmlang['txt']['upload']." $destname</font></h3>";
				}
				
				$msg.=batch\check_data($filePath,$check_arr);				
				if($check_arr['worksheet'] && $check_arr['data']){
			  // new file name		
					$newFname=$check_arr['target'].date('Ymd').img\genRandStr(20).".xlsx";
					$newFilePath = $VWMLDBM['VWMLDBM_UPLOAD_PATH']."/app/batch/{$_SESSION['lib_inst']}/".$newFname;
					if(rename($filePath,$newFilePath)){ // rename the file name
					  // remove all old files
						$dir = $VWMLDBM['VWMLDBM_UPLOAD_PATH']."/app/batch/{$_SESSION['lib_inst']}/"; // directory name to be read
						if($dir)$ar=scandir($dir); 
						if ($ar) {
							foreach ($ar as $key => $val) {
								if (substr($dir, -1) == '/') {
									$path_value = $dir . $val;
								} else {
									$path_value = $dir . "/" . $val;
								}
								if (mb_substr($val, 0, mb_strlen($check_arr['target'])) == $check_arr['target']) {
									if ($path_value == $newFilePath) continue; // leave the new one
									unlink($path_value); // delete the old one
									// echo $path_value."<br>";
								}
							}
						}
					}
				}
				else { // worksheet and/or data is not okay
					unlink($filePath); // remove the file
				}
			}
		}
		else $msg= "<h3><font color='red'>".$wmlang['js']['error'].$wmlang['txt']['upload']." $destname</font></h3>";
		echo $msg;
		
		if($check_arr['worksheet'] && $check_arr['data']===true && $check_arr['mod']==true) { // everything is okay. Ready to execute.
			echo "<h3 style='color:green'> ".$wmlang['txt']['data_ok']." <button class='button' type=button onClick='execute();'>".$wmlang['txt']['continue']."</button></h3>";
			echo "<script>
				function execute(){
					if(confirm(\"".$wmlang['js']['confirm_data_cont']."\")){
						document.form1.operation.value='EXECUTE'; 
						document.form1.submit();
					}
				}
			
			</script>";
			echo "<input type='hidden' name='target' value='".$check_arr['target']."'>";
		}
		else if($check_arr['data']==="NOT_MOD") echo "<h3 style='color:red;'>".$wmlang['txt']['cannot_mod']."</h3>";
		else if($check_arr['mod']==false) echo "<h3 style='color:magenta;'>".$wmlang['txt']['no_need4mod']."</h3>"; // no need to proceed;
		else echo "<h3 style='color:red;'>".$wmlang['txt']['wksheet_error']."</h3>";
	}
	else if($_POST['operation']=='EXECUTE' && $_POST['target']){
	  // 1. get the uploaded file name
		$dir = $VWMLDBM['VWMLDBM_UPLOAD_PATH']."/app/batch/{$_SESSION['lib_inst']}/"; // directory name to be read
		if($dir)$ar=scandir($dir);
		if ($ar) {
			foreach ($ar as $key => $val) {
				if (mb_substr($val, 0, mb_strlen($_POST['target'])) == $_POST['target']) {
					if (substr($dir, -1) == '/') {
						$filePath = $dir . $val;
					} else {
						$filePath = $dir . "/" . $val;
					}
					break; // Exit the loop once the target is found
				}
			}
		}

		
	  // 2. check the file
		$file_ok=true;
		
		$check_arr=array();
		$check_arr['worksheet']=false;
		$check_arr['data']=false;
		$check_arr['target']=null;
				
		$file_basename = substr($filePath, 0, strripos($filePath, '.')); // get file extention
		$file_ext = substr($filePath,-4,4);
		$allowed_file_types = array('xlsx');
				
		if (!in_array(strtolower($file_ext),$allowed_file_types))
			$file_ok=false;
	  
		if($file_ok){
			// echo $filePath;
			echo \vwmldbm\batch\update_data($filePath, $check_arr); // check the worksheet and data again
		}
		else {  // to extra make sure if the uploaded file is okay
			$msg= "<h3><font color='red'>".$wmlang['js']['error'].$wmlang['txt']['upload']." $val</font></h3>";
		}
		
		echo "<h3 style='color:magenta;'>{$wmlang['txt']['new_user_pwd']}</h3>";
	}

?>
  </div>
  
  <div class='container' style="width:500px; text-align:center; margin-top:10px;margin-down:10px;">
	<hr>
	<h2>
	e-Books
	<?PHP
	// GET ebook list
	$ebook_arr=array();
	\ebook\get_ebook_list($ebook_arr);
	$num=count($ebook_arr); // Maximum number files to download


	$total_fsize=number_format(\ebook\get_total_filesize($ebook_arr)/1000000,1)."MB";

	echo "Total <b>$num</b> files: <b>$total_fsize</b>";
	?>
	</h2>
	<p><a href="../../admin/ebook/"><button type='button' style='background:green;color:white;padding:5px;'>Back Up e-Books</button></a></p>
  </div>
 <input type='hidden' name='operation'>
</form>
</center>

<script>
function download_sheet(){
	var down_url="<?=$VWMLDBM['VWMLDBM_WWW_RT']?>/batch/batch_data_download.php?target="+$("input[name='radio-1']:checked").val()+"&bc_opt="+$("input[name='radio-bc']:checked").val();
	var rval=document.form1['radio-1'].value;
	window.open(down_url,"_blank");
}

function enable_download(){
	document.getElementById('dbutton').disabled=false;
}

function show_book_copy_options(){
	document.getElementById('bc_opt').style.display='block';
	if(document.form1['radio-bc'].value=='') 
		document.getElementById('dbutton').disabled=true;
}

function hide_bc_opt(){
	document.getElementById('bc_opt').style.display='none';
}

function zip_download_ebook() {
	
}
</script>
<?
$hdoc->print_foot();

<?PHP
/*VWMLDBM DISCLAIMER*==================================================
 Copyright (c) 2017 Sang Jin Han and other contributors, 
	http://wise4edu.com
 Released under the MIT license
 =================================================*VWMLDBM DISCLAIMER*/
namespace vwmldbm;
session_start();
set_time_limit(240); //  set the execution time limit in seconds

require_once("../../vwmldbm/config.php");

require $VWMLDBM['VWMLDBM_RT'].'../../vwmldbm/lib/code.php';
require $VWMLDBM['VWMLDBM_RT'].'../../vwmldbm/lib/file.php';
require $VWMLDBM['VWMLDBM_RT'].'../../vwmldbm/lib/img.php';
require $VWMLDBM['VWMLDBM_RT'].'../../lib/ebook.php';

if(!$_SESSION['lib_inst'] || $_SESSION['wlibrary_admin']!='A') die;

$inst_var=new code\Inst_var();

$hdoc=new code\Hdoc();
$hdoc->print_body_tag();
$hdoc->print_head();
$hdoc->print_title($wmlang['menu']['e-Books']);
$perm['A']='Y'; // TBD

?>

<div class='container' style="width:'98%'; text-align:center; margin-top:10px;margin-down:10px;">
<?PHP

// GET ebook list
$ebook_arr=array();
\ebook\get_ebook_list($ebook_arr);
$num=count($ebook_arr); // Maximum number files to download
// echo "<pre>";
// print_r($ebook_arr);
// echo "</pre>";

$total_fsize=number_format(\ebook\get_total_filesize($ebook_arr)/1000000,1)."MB";

echo "Total <b>$num</b> files: <b>$total_fsize</b>";

for($i=1;$i<=ceil($num/$VWMLDBM['MAX_BOOK_DOWNLOAD']);$i++) {
	$start_no=($i-1)*$VWMLDBM['MAX_BOOK_DOWNLOAD']+1;
	
	$end_no=$i*$VWMLDBM['MAX_BOOK_DOWNLOAD'];
	if($end_no>$num) $end_no=$end_no=$num;	
	
	echo "		
	<p><a href='zipdown/?no=$i'>
		<button type='button' style='background:blue;color:white;padding:5px;' onClick='loading_control();'>Zip & download $i ($start_no-$end_no)</button>
	</a></p>
	";
}
?>
	<p><a href="../../vwmldbm/batch/">
		<button type='button' style='background:gray;color:white;padding:5px;'>Back to Batch Processing</button>
	</a></p>
  </div>
 <input type='hidden' name='operation'>
</form>
</center>

<script>
	// Loading sign control
	var loadingHandle;
	
	function loading_control() {
		loadingHandle=setInterval(()=>{
			if(checkLoaded()) {
				$('#loading').hide();
				loadingHandle=null;
			}
		},5000);
	}
	
	
	(function(w) {
		//private variable
		var loaded = false;
		w.onload = function() {
			loaded = true;
		};

		w.checkLoaded = function() {
			return loaded;
		};
	})(window);
</script>
<?
$hdoc->print_foot();

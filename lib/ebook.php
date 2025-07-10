<?PHP
/*DISCLAIMER*==================================================
 WISE Library System
 Copyright (c) 2017 Sang Jin Han, 
	http://wise4edu.com
/*==============================================================
function list:

Classes:

  ============================================================*/
namespace ebook;

function get_ebook_list(&$arr,$offset=null,$opt=null){
	global $conn,$DTB_PRE,$VWMLDBM;
	$inst=$_SESSION['lib_inst'];
	
	$path=$VWMLDBM['VWMLDBM_UPLOAD_EBOOK_PATH']."{$_SESSION['lib_inst']}/";
	
	$maxBook=$VWMLDBM['MAX_BOOK_DOWNLOAD'];
	
	$cnt=0; // number of pdfs
	if($offset!==null) {
		$num=0; // for the specified records only
	}
	//if($offset!==null) $offset_txt=" limit $offset, {$VWMLDBM['MAX_BOOK_DOWNLOAD']}";
	
	$sql = "select id,rid,title,author,files,rfiles from {$DTB_PRE}_{$VWMLDBM['BOOK_TB']} where inst='$inst' and isnull(files)=false  $offset_txt";
	// echo($sql);
	$res = mysqli_query($conn,$sql);
	if ($res) while($rs=mysqli_fetch_assoc($res)) {
		// There could be more than one file and some file may not be pdf but movie		
		$f_arr=explode(';',$rs['files']);
		$r_arr=explode(';',$rs['rfiles']);
		foreach($f_arr as $key => $tf) {
			if(!file_exists($path.$rs['rid']."/".$r_arr[$key])) continue; // The actual file doesn't exist. It is an error condition
			$ext = strtolower(pathinfo($tf, PATHINFO_EXTENSION));	
			if($ext=='pdf') { 
				$cnt++;
				if($offset!==null) { // offset was specified
					if($cnt<=$offset) continue;					
					$arr[$rs['rid']."_".$r_arr[$key]]=$tf;
					$num++;
					if($num >= $maxBook) break;
				}	
				else { // offset was not specified
					$arr[$rs['rid']."_".$r_arr[$key]]=$tf;
				}
			}
		}
		if($num >= $maxBook) break; // break the outer loop as well
	}
}

function get_total_filesize($arr){
	global $VWMLDBM;
	$size=0;
	
	$path=$VWMLDBM['VWMLDBM_UPLOAD_EBOOK_PATH']."{$_SESSION['lib_inst']}/";
	foreach($arr as $key => $val) {
		$tmp=explode('_',$key);
		$rid=$tmp[0];
		$rfile=$tmp[1];

		$size+=filesize($path.$rid."/".$rfile). "<br>";		
	}
	return $size;
}
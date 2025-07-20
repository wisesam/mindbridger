<?PHP
/*VWMLDBM DISCLAIMER*==================================================
 Copyright (c) 2017 Sang Jin Han and other contributors, 
	http://wise4edu.com
 Released under the MIT license
 =================================================*VWMLDBM DISCLAIMER*/
 
/*
  Description : Batch Data Processing (Excel) Library
*/

/*==============================================================
class: BatchData
	functions:
		php_output($fname)
		save_excel($fname)
		
class: BatchUser
	functions:		
		get_code_list($c,$opt=null,$html=true)
		is_worksheet_valid($data)
		check_record($data,$row,$primary_idx,&$msg,$new_timetable_arr,$primary_idx2=null)
		update_record($data,$row,$primary_idx,&$msg,$new_timetable_arr,$id_idx2=null)
		check_required($fd,$val)
		check_code($fd,$val)
		check_foreign($fd,$val)
		check_yn($fd,$val)
		batch_update($actor,$actor,$std_ext_arr,$std_ext,$record,$field_name)
		batch_insert($actor,$std_ext,$record,$field_name)
		
class: BatchBook
	functions:

class: BatchBookCopy
	functions:
	
functions:	
	get_comment_width($str,$delimiter=PHP_EOL)
	get_comment_height($str,$delimiter=PHP_EOL)
	check_data($filePath,&$check_arr)
	update_data($filePath,&$check_arr)
	
 ============================================================*/
namespace vwmldbm\batch;

// Create new Spreadsheet object
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\IOFactory;

require $VWMLDBM['VWMLDBM_RT'].'/lib/PhpOffice/src/Bootstrap.php';
require $VWMLDBM['VWMLDBM_RT'].'/lib/etc.php';

class BatchData {
	public function __construct($target=null,$ss,$inst=null){
	// $target: target data (user,book,bookcopy)
		global $conn,$DTB_PRE;		
		$this->inst=($inst ? $inst : $_SESSION['lib_inst']);
		$this->target=$target;
		$this->spreadsheet=$ss;
		$this->writer = IOFactory::createWriter($ss, 'Xlsx');
		if($target=='user') $this->dObj=new BatchUser();
		else if($target=='book') $this->dObj=new BatchBook();
		else if($target=='book_copy') $this->dObj=new BatchBookCopy();
	
		// $fobj=(array)$this->dObj;
		// print_r($fobj);
		
		// Set document properties
		$this->spreadsheet->getProperties()->setCreator('VWMLDBM')
			->setLastModifiedBy('VWMLDBM')
			->setTitle('VWMLDBM Batch Data Processing: '.$target)
			->setDescription('Office 2007 XLSX, generated using PhpSpreadsheet.')
			->setSubject('')    
			->setKeywords('')
			->setCategory('');		
	}
	
	public function php_output($fname){
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment; filename="'.$fname.'"');
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Pragma: no-cache');
		ob_clean();
		flush(); 
		$this->writer->save("php://output");
	}
	
	public function save_excel($fname){
		global $VWMLDBM;
		if(empty($VWMLDBM['VWMLDBM_BATCH_UPLOAD'])) return; // illegal operation
		
		$this->writer->save($VWMLDBM['VWMLDBM_BATCH_UPLOAD']."/".$fname);
	}
}

// This is special code set for Wlibrary only. "SA" is omitted because it is Super Admin.
	$utype=array(
		"A"=>$wmlang['txt']['admin'],
		"M"=>$wmlang['txt']['member'],
		"G"=>$wmlang['txt']['guest']
	);
	
	$ustatus=array(
		"A"=>$wmlang['txt']['active'],
		"S"=>$wmlang['txt']['suspended'],
		"PS"=>$wmlang['txt']['pwd_suspended'],
		"T"=>$wmlang['txt']['terminated']
	);


class BatchUser {
	public function __construct($inst=null) {		
		$this->inst=($inst?$inst:$_SESSION['lib_inst']);
		$this->tb="users";
		$this->target="user";
		$this->primary_key="id";
		$this->fdBasic=array(); // fields that will apear in the beginning of the Excel file: important fields
		$this->fdNonBasic=array(); // fields that will apear in the end of the Excel file
		$this->fd=array(); // all fileds
		$this->fdCode=array(); // code fields
		$this->fdForeign=array(); // non-code, non-user forien fields
		$this->fdUser=array(); // user that needs to be checked if it exists, if it doens't violate the authority (school/dept admin)
		
		$this->fdReq=array(); // required fields: should not be empty
		$this->fdYn=array(); // Y/N field		
		$this->fdRdOnly=array(); // Readonly field		
		$this->fdPass=array(); // Don't update field		
		$this->fdDate=array(); // Date field- 2018-01-12		
		$this->fdDateTime=array(); // Date field- 2018-01-12 11:30:10		
		$this->fdRealCodeName=array(); // some codes may have different name compared with their original code name
		$this->fdUnique=array(); // should be unique
		
		$fd_list=['id','name','email','created_at','updated_at',
				  'utype','code_c_utype','ustatus','last_login'];

		foreach($fd_list as $val) $this->fd[$val]=''; // initialization
			
		$this->fdBasic["id"]='';
		$this->fdBasic["name"]='';
		$this->fdBasic["email"]='';
		$this->fdBasic["utype"]='';
				
		$this->fdCode["code_c_utype"]='';
		$this->fdCode["utype"]='';
		$this->fdCode["ustatus"]='';
			
		$this->fdReq["id"]='';
		$this->fdReq["email"]='';
		$this->fdReq["utype"]='';
		
		// $this->fdYn["e_resource_yn"]='';
						
		$this->fdPass["id"]='';
				
		// $this->fdDate["pub_date"]='';
				
		$this->fdDateTime["created_at"]='';
		$this->fdDateTime["updated_at"]='';
		$this->fdDateTime["last_login"]='';
		
	 // fdNonBasic
		foreach($this->fd as $key=>$val){
			$this->fdNonBasic[$key]='';
		}
		
		foreach($this->fdBasic as $key => $val){ 
			unset($this->fdNonBasic[$key]); // remove basic fd
		}
		
		$this->fdRealCodeName['code_c_utype']='c_utype';		
		
		$this->fdUnique['email']='';
		$this->uniqueArr=array();
	}
	
	public function get_list(&$arr,$opt=null) {
		// $opt: EXISTING, TO_BE_ADDED, ALL (in case of book_copy)
		global $conn,$DTB_PRE;
		if($this->target!='book_copy') { // user, book
			$sql = "select * from {$DTB_PRE}_{$this->tb} where inst='{$this->inst}' ";

			$res = mysqli_query($conn,$sql);
			if($res) while($rs=mysqli_fetch_assoc($res)){
				$arr[$rs['id']]=$rs;
			}
		}
		else { // book_copy
			if($opt=='TO_BE_ADDED' || $opt=='ALL') {  // to be added
				$sql = "select id,title from {$DTB_PRE}_book 
					where inst='{$this->inst}' and (e_resource_yn<>'Y' || ISNULL(e_resource_yn)) and id NOT IN(select distinct bid from {$DTB_PRE}_book_copy where inst='{$this->inst}')";

				$res = mysqli_query($conn,$sql);
				if($res) while($rs=mysqli_fetch_assoc($res)){
					$arr["NEW_".$rs['id']]=$rs;
				}
			}
			
			if($opt!='TO_BE_ADDED') { // existing
				$sql = "select bc.id,bc.bid,bc.barcode,bc.call_no,
						bc.location,bc.c_rstatus,bc.comment,b.title 
							from {$DTB_PRE}_{$this->tb} as bc
							,{$DTB_PRE}_book as b where bc.inst='{$this->inst}' and bc.bid=b.id";

				$res = mysqli_query($conn,$sql);
				if($res) while($rs=mysqli_fetch_assoc($res)){
					$arr[$rs['id']]=$rs;
				}
			}
		}
	}
	
	public function get_obj(&$arr,$id){
		global $conn,$DTB_PRE;
		$sql = "select * from {$DTB_PRE}_{$this->tb} where inst='{$this->inst}' and id='$id' ";

		$res = mysqli_query($conn,$sql);
		if($res) $arr=mysqli_fetch_assoc($res);		

	}
	
	public function is_unique($fd,$val) {
		global $conn,$DTB_PRE;
		$sql = "select count(`$fd`) as cnt from {$DTB_PRE}_{$this->tb} where inst='{$this->inst}' and `$fd`='$val'";

		$res = mysqli_query($conn,$sql);
		if($res) $rs=mysqli_fetch_assoc($res);
		if($rs['cnt']) return false;
		else {
			if(is_array($this->uniqueArr[$fd])){
				if(array_search($val,$this->uniqueArr[$fd])===false)
					return true; // okay
				else return false; // found it in the array
			}				
			else return true;
		}
	}
	
	public function get_code_list($c,$opt=null,$html=true) {
		global $conn,$DTB_PRE,$wmlang,$utype,$ustatus;
		$rval=null; // return value
		
		if(!isset($this->fdCode[$c])) return;
		if($this->fdRealCodeName[$c]) $c=$this->fdRealCodeName[$c];
		
		
		if($this->target=='user' && $c=='utype') { // Wlibrary only
			foreach($utype as $key => $val){
				$rval.="$key: $val\r\n";
			}
			return $rval;
		}
		else if($this->target=='user' && $c=='ustatus') { // Wlibrary only
			foreach($ustatus as $key => $val){
				$rval.="$key: $val\r\n";
			}
			return $rval;
		}
		
		
		$lang=($_SESSION['lang']?$_SESSION['lang']:10); // 10: default English
		
		if($c=='c_lang') {
			$sql = "select * from {$DTB_PRE}_vwmldbm_{$c} where inst='{$this->inst}' ";	
		}
		else {
			$sql = "select * from {$DTB_PRE}_code_{$c} where inst='{$this->inst}' and c_lang='{$lang}'";	
		}
		
		if($opt!='USE_YN_YN') 
			$sql.=" and (use_yn<>'N' OR ISNULL(use_yn)=true)";

		$res = mysqli_query($conn,$sql);
		if($res) while($rs=mysqli_fetch_array($res)){			
			if($html)
				$rval.= "<b>".$rs['code']."</b>:".$rs['name']."<br>";
			else $rval.= $rs['code'].":".$rs['name']."\r\n";
		}
		return $rval;
	}
	
	public function is_worksheet_valid($data){
		global $wmlang;
		$field_row=$data[1];
		$msg=null;
	  
	  // 1. Check if there is any missing basic field 
		foreach($this->fdBasic as $fd=>$val){
			if(array_search($fd,$field_row)===false){
				$msg.="<br>".$wmlang['txt']['miss_fd'].": $fd";
			}
		}
	  // 2. Check if worksheet doesn't have any unknown field
	    foreach($field_row as $wfd){
			if(!trim($wfd)) continue;
		}
	    
	  // 3. Check if the worksheet has data records
		if(count($data)<3) $msg.="<br><h3>".$wmlang['txt']['no_data']."</h3>";		
		
		if($msg===null) return true;
		else return $msg;
	}
	
	public function check_record($data,$row,$primary_idx,&$msg,$primary_idx2=null) {
		global $wmlang,$VWMLDBM;
		if($this->tb=='users') $user=true;
		else if($this->tb=='book') $book=true;
		else if($this->tb=='book_copy') $book_copy=true;
		else return false;
		
		$field_name=$data[1];
		$record=$data[$row];
		
		$error=false;
		$not_mod=false; // check if the semester is not modifiable
		$not_mod_error=false; // check if trying to modify unmodifiable semester's record
		
		$record[$primary_idx]=strtoupper(trim($record[$primary_idx]));
		
		$actor=array();
		$this->get_obj($actor,$record[$primary_idx]);		

		$is_empty=true;
		foreach($record as $val){
			if(trim($val)!=='') $is_empty=false;
		}
		if($is_empty) return;
		
		if(!$actor['id']) { // new record			
			if(!$book && !$book_copy && !$record[$primary_idx]){
				$is_new=true;
				$error=true;	
			}			
			else {
				$is_new=true;
				if($book || $book_copy){
					if(strtoupper(trim($record[$primary_idx]))=='NEW'){ // "new" or primary key was not provided => new record
						if($this->available_primary_key<=0)
							$this->available_primary_key=$this->get_available_id();
						else $this->available_primary_key++;
						$record[$primary_idx]=$this->available_primary_key;
					}
					else if(!$record[$primary_idx]) return;
				}
				
				if($user) {
					if(!\vwmldbm\etc\validate_username($record[$primary_idx])){
						$error=true;
						$msg_id= "<font color=red><b>(".$wmlang['js']['not_allowed_ch'].")</b></font>";
					}
				}
			}
		}
		else { // existing record
			$is_new=false;
		}
		
		
		// check if the primary_key was in the previous record
		for($i=2;$i<$row;$i++) {
			if(trim(strtoupper($record[$primary_idx]))==trim(strtoupper($data[$i][$primary_idx]))){
				$error=true;
				$msg_id= "<font color=red><b>(".$wmlang['txt']['dup_record'].")</b></font>";
			}
		}

		$to_be_updated_in_all=false;

		$mod=false; // for proceed button
		foreach($record as $key => $val){
			if($key>=count($this->fd)) break;
			 
			 $to_be_updated=false;			 
			 $is_code=false;
			 $bgcolor="";
			 $foreign_msg="";
			 $new_code_msg=""; // message for code field (new)
			 $old_code_msg=""; // message for code field (old)
			 $yn_msg="";
			 $rdonly_msg="";
			 $user_msg=""; // message for user authority checking	
			 $unique_msg="";
			 
			 if(isset($this->fd[$field_name[$key]])) // belong to actor
				$old_val=$actor[$field_name[$key]];
			
			 if($key==$primary_idx && $is_new) { // ID of new record
				if($error){
					$msg['txt'].= "<td><font color=red><b>(".$wmlang['txt']['new'].")</b></font> $val $grade_std_id_error_msg $msg_id</td>";
				}
				else $msg['txt'].= "<td><font color=green><b>(".$wmlang['txt']['new'].")</b></font> $val $msg_id</td>";
			 }
			 else { // ID and all other record of existing record
				if(isset($this->fdCode[$field_name[$key]])){
					$is_code=true;
					$check_code_result=$this->check_code($field_name[$key],$old_val);
					if($check_code_result===2) $old_code_msg="<font color=red>(".$wmlang['txt']['not_used_code'].")</font>";
				}
	
			  // old value != new value or new				
				
				if(($old_val!=$val && !isset($this->fdPass[$field_name[$key]])) || $is_new) { 
					$mod=true; // for proceed button
					
					if($not_mod) $not_mod_error=true;
					if(!$is_new){
						$bgcolor=" style='background-color:yellow;' ";
						$to_be_updated=true;
						$to_be_updated_in_all=true;
					}
					if(!$this->check_required($field_name[$key],$val)){
						$color="FF0000";
						$bgcolor=" style='background-color:FFCCCC;' ";
						$error=true;
					}					
					if($is_code){						
						$check_code_result=true;
						if($val) $check_code_result=$this->check_code($field_name[$key],$val);
						if($check_code_result===false){
							$color="FF0000";
							$error=true;
							$new_code_msg="<font color=red>(".$wmlang['txt']['not_exist'].")</font>";
						}
						else if($check_code_result===2) $new_code_msg="<font color=red>(".$wmlang['txt']['not_used_code'].")</font>";
					}					
					
					if($val && !$this->check_foreign($field_name[$key],$val)){
						$color="FF0000";
						$bgcolor=" style='background-color:FFCCCC;' ";
						$error=true;
						$foreign_msg="<font color=red>(".$wmlang['txt']['not_exist'].")</font>";
					
					}					
					
					if($val && !$this->check_yn($field_name[$key],$val)){
						$color="FF0000";
						$bgcolor=" style='background-color:FFCCCC;' ";
						$error=true;
						$yn_msg="<font color=red>(".$wmlang['txt']['not_exist'].")</font>";
					}
					
					if($val && isset($this->fdDate[$field_name[$key]]) && !\vwmldbm\etc\validate_date($val)){
						$color="FF0000";
						$bgcolor=" style='background-color:FFCCCC;' ";
						$error=true;
						$yn_msg="<font color=red>(".$wmlang['txt']['wrong_date'].")</font>";
					}
					else if($val && isset($this->fdDateTime[$field_name[$key]]) && !\vwmldbm\etc\validate_date($val,'Y-m-d H:i:s')){
						$color="FF0000";
						$bgcolor=" style='background-color:FFCCCC;' ";
						$error=true;
						$yn_msg="<font color=red>(".$wmlang['txt']['wrong_date'].")</font>";
					}
					
									
					if(!$is_new && isset($this->fdRdOnly[$field_name[$key]]) && !isset($this->fdPass[$field_name[$key]]) && $val!=$old_val  && !$grade){
						$color="FF0000";
						$bgcolor=" style='background-color:FFCCCC;' ";
						$error=true;
						$rdonly_msg="<font color=red>(".$wmlang['txt']['rdonly'].")</font>";
					}

					if(isset($this->fdUser[$field_name[$key]])) {
						$val=strtoupper(trim($val));		
						if($val) $user_check_result=$this->check_user_exist($val); // new value
						else if($old_val) $user_check_result=$this->check_user_exist($old_val); // old value
						else $user_check_result=true;
						
						if($user_check_result) $user_check_result=$this->check_user_exist($val); // new value
						
						if($val && $user_check_result===0){
							$color="FF0000";
							$bgcolor=" style='background-color:FFCCCC;' ";
							$error=true;
							$user_msg.="<font color=red>(".$wmlang['txt']['user_n_exist'].")</font>";
						}	
					}									
					
					if($val && isset($this->fdUnique[$field_name[$key]])) {
						$val=trim($val);
						
						if(!$this->is_unique($field_name[$key],$val)){
							$color="FF0000";
							$bgcolor=" style='background-color:FFCCCC;' ";
							$error=true;
							$unique_msg.="<font color=red>(".$wmlang['txt']['duplicate'].")</font>";
						}
						$this->uniqueArr[$field_name[$key]][]=$val;	
					}

					if(!$val && !$actor[$field_name[$key]] && !$std_arr_ext[$field_name[$key]]){ // both old and new are blank
						if($key==$score_idx) $the_score_error_msg=$score_error_msg;
						else $the_score_error_msg=null;
					}
					
					if(!$to_be_updated && !$is_new){
						$msg['txt'].= "<td></td>";
					}
					else if(isset($this->fdPass[$field_name[$key]])){ 
						$msg['txt'].= "<td>";
						
						if($is_new) $msg['txt'].=$val;
						else $msg['txt'].=$actor[$field_name[$key]];
						
						$msg['txt'].= "</td>";
					}
					else {						
						$msg['txt'].= "<td $bgcolor>";
						
						if(!$is_new) $msg['txt'].= "<b>".$actor[$field_name[$key]]." $old_code_msg</b> =>";
							
						$msg['txt'].= "<br> <b><font color='blue'>$val </font> $new_code_msg $foreign_msg $yn_msg $rdonly_msg $user_msg $unique_msg </b>";
						

						if($key==$primary_idx) $msg['txt'].= $msg_id;
												
						$msg['txt'].="</td>";
					}
				}
				else { // old value == new value OR new input			
					if($is_new) $mod=true; // for proceed button
					
					if($key==$primary_idx) $msg['txt'].= "<td>$val $old_code_msg $msg_id</td>";					
					else $msg['txt'].= "<td>$val $old_code_msg </td>";
				}
			 }
		}
		
		if($error) $bgcolor=" style='background-color:FFCCCC;' ";
		else if($to_be_updated_in_all || $is_new) $bgcolor=" style='background-color:yellow;' ";
		$msg['txt']= "<tr><td $bgcolor>".($row-1)."</td>".$msg['txt']."</tr>";
		
		if($error===true) return false;
		if($not_mod && $not_mod_error) return "NOT_MOD";
		else if($mod) return "MOD"; // used to display 'proceed to execute'
		else return true;
	}
	
	public function update_record($data,$row,$primary_idx,&$msg,$primary_idx2=null){
		global $wmlang,$VWMLDBM;
		if($this->tb=='users') $user=true;
		else if($this->tb=='book') $book=true;
		else if($this->tb=='book_copy') $book_copy=true;
		else return false;

		$field_name=$data[1];
		$record=&$data[$row];
		
		$error=false;
		$not_mod=false;

		// check if the primary_key was in the previous record
		if(trim($record[$primary_idx])=='') return; // skip the empty primary key row 2019/11/19

		if(strtoupper(trim($record[$primary_idx]))=='NEW'); // okay
		// 2019/11/19 if($record[$primary_idx]=='' || $record[$primary_idx]=='NEW'); // okay
		else if($grade){
				for($i=2;$i<$row;$i++) {
				if(trim(strtoupper($record[$primary_idx].$record[$primary_idx2]))==trim(strtoupper($data[$i][$primary_idx].$data[$i][$primary_idx2]))){
					$error=true;
					$msg_id= "<font color=red><b>(".$wmlang['txt']['dup_record'].")</b></font>";
				}
			}
		}
		else {
			for($i=2;$i<$row;$i++) {
				if(trim(strtoupper($record[$primary_idx]))==trim(strtoupper($data[$i][$primary_idx]))){
					$error=true;
					$msg_id= "<font color=red><b>(".$wmlang['txt']['dup_record'].")</b></font>";
				}
			}
		}
		
		$record[$primary_idx]=strtoupper(trim($record[$primary_idx]));
		
		$actor=array();
		$this->get_obj($actor,$record[$primary_idx]);	
	
		if(!isset($actor) || !$actor['id']) { // new record			
			if(!$book && !$book_copy && !$record[$primary_idx]){
				$is_new=true;
				$error=true;	
			}			
			else {
				$is_new=true;
				if($book || $book_copy){
					if(trim($record[$primary_idx])=='NEW'){ // "new" or primary key was not provided => new record
						if($this->available_primary_key<=0)
							$this->available_primary_key=$this->get_available_id();
						else $this->available_primary_key++;
						$record[$primary_idx]=$this->available_primary_key;
					}
					else if(!$record[$primary_idx]) return;
				}
				
				if($user) {
					if(!\vwmldbm\etc\validate_username($record[$primary_idx])){
						$error=true;
						$msg_id= "<font color=red><b>(".$wmlang['js']['not_allowed_ch'].")</b></font>";
					}
				}
			}
		}
		else { // existing record
			$is_new=false;
		}
		
		// check if the primary_key was in the previous record		
		for($i=2;$i<$row;$i++) {
			if(trim(strtoupper($record[$primary_idx]))==trim(strtoupper($data[$i][$primary_idx]))){
				$error=true;
				$msg_id= "<font color=red><b>(".$wmlang['txt']['dup_record'].")</b></font>";
			}
		}

		$to_be_updated_in_all=false;
		
		foreach($record as $key=>$val){
			 // echo "Memory: <font color=blue>".number_format(memory_get_usage()/1000)."KB</font><br>";
			 if($student) {
				 if($key>=(count($this->fd)+count($this->fdExt)))
					break;
			 }
			 else if($key>=count($this->fd)) break;
			
			$to_be_updated=false;			 
			$is_code=false;
			$bgcolor="";
			$foreign_msg="";
			$new_code_msg=""; // message for code field (new)
			$old_code_msg=""; // message for code field (old)
			$acad_dept_msg=""; // message for valid acade_dept (for school admin or dept admin)
			$yn_msg="";
			$rdonly_msg="";
			$user_msg=""; // message for user authority checking			
			$unique_msg="";
	
			if($subject && $field_name[$key]=='code' && !$val){ return;} 
			else if($field_name[$key]=='id' && !$val) { return;}  
			 
			if(isset($this->fd[$field_name[$key]])) // belong to obj
				$old_val=$actor[$field_name[$key]];
								
			if($key==$primary_idx && $is_new) { // ID of new record
				if($error)
					$msg['txt'].= "<td><font color=red><b>(".$wmlang['txt']['new'].")</b></font> $val $grade_std_id_error_msg $msg_id</td>";
				else $msg['txt'].= "<td><font color=green><b>(".$wmlang['txt']['new'].")</b></font> $val $msg_id</td>";
			}
			else { // ID and all other record of existing record
				if(isset($this->fdCode[$field_name[$key]])){					
					$is_code=true;
					$check_code_result=$this->check_code($field_name[$key],$old_val);
					if($check_code_result===2) $old_code_msg="<font color=red>(".$wmlang['txt']['not_used_code'].")</font>";
				}						
				if(($old_val!=$val && !isset($this->fdPass[$field_name[$key]])) || $is_new){ // old value != new value
				
					if($not_mod) $not_mod_error=true;
					if(!$is_new){
						$bgcolor=" style='background-color:yellow;' ";
						$to_be_updated=true;
						$to_be_updated_in_all=true;
						
						if($gpa->special_name_yn[$record[$c_grade_idx]]!='Y')
							$record[$c_grade_idx]=$c_grade_new; // the excel file doesn't have c_grade, so.
					}			
					
					if(!$this->check_required($field_name[$key],$val)){
						$color="FF0000";
						$bgcolor=" style='background-color:FFCCCC;' ";
						$error=true;
					}			
					
					if($is_code){						
						$check_code_result=true;
						if($val) $check_code_result=$this->check_code($field_name[$key],$val);
						if($check_code_result===false){
							$color="FF0000";
							$error=true;
							$new_code_msg="<font color=red>(".$wmlang['txt']['not_exist'].")</font>";
						}
						else if($check_code_result===2) $new_code_msg="<font color=red>(".$wmlang['txt']['not_used_code'].")</font>";
					}
					
					if($val && !$this->check_foreign($field_name[$key],$val)){
						$color="FF0000";
						$bgcolor=" style='background-color:FFCCCC;' ";
						$error=true;
						$foreign_msg="<font color=red>(".$wmlang['txt']['not_exist'].")</font>";
					}
					else if($course && $field_name[$key]=='aimed_cname' && $val && !$this->check_cname($actor,$val,$record[$c_acad_yr_idx],$record[$c_semester_idx])){
						$color="FF0000";
						$bgcolor=" style='background-color:FFCCCC;' ";
						$error=true;
						$foreign_msg="<font color=red>(".$wmlang['txt']['not_same_sem'].")</font>";
					}
					
					if($val && !$this->check_yn($field_name[$key],$val)){
						$color="FF0000";
						$bgcolor=" style='background-color:FFCCCC;' ";
						$error=true;
						$yn_msg="<font color=red>(".$wmlang['txt']['not_exist'].")</font>";
					}
					
					if($val && isset($this->fdDate[$field_name[$key]]) && !\vwmldbm\etc\validate_date($val)){
						$color="FF0000";
						$bgcolor=" style='background-color:FFCCCC;' ";
						$error=true;
						$yn_msg="<font color=red>(".$wmlang['txt']['wrong_date'].")</font>";
					}					
					else if($val && isset($this->fdDateTime[$field_name[$key]]) && !\vwmldbm\etc\validate_date($val,'Y-m-d H:i:s')){
						$color="FF0000";
						$bgcolor=" style='background-color:FFCCCC;' ";
						$error=true;
						$yn_msg="<font color=red>(".$wmlang['txt']['wrong_date'].")</font>";
					}
					
					if(isset($this->fdUser[$field_name[$key]])) {
						$val=strtoupper(trim($val));						
												
						if($val) $user_check_result=$this->check_user_exist($val); // new value
						else if($old_val) $user_check_result=$this->check_user_exist($old_val); // old value
						else $user_check_result=true;
						
						if($user_check_result) $user_check_result=$this->check_user_exist($val); // new value
												
						if($val && $user_check_result===0){
							$color="FF0000";
							$bgcolor=" style='background-color:FFCCCC;' ";
							$error=true;
							$user_msg="<font color=red>(".$wmlang['txt']['user_n_exist'].")</font>";
						}						
						else if($user_check_result===-1){
							$color="FF0000";
							$bgcolor=" style='background-color:FFCCCC;' ";
							$error=true;
							$user_msg="<font color=red>(".$wmlang['txt']['invalid_dept'].")</font>";
						}
					}
					
									
					if(!$is_new && isset($this->fdRdOnly[$field_name[$key]]) && $val!=$old_val){
						$color="FF0000";
						$bgcolor=" style='background-color:FFCCCC;' ";
						$error=true;
						$rdonly_msg="<font color=red>(".$wmlang['txt']['rdonly'].")</font>";
					}
					
					if($val && isset($this->fdUnique[$field_name[$key]])) {
						$val=trim($val);
						if(!$this->is_unique($field_name[$key],$val)){
							$color="FF0000";
							$bgcolor=" style='background-color:FFCCCC;' ";
							$error=true;
							$unique_msg.="<font color=red>(".$wmlang['txt']['duplicate'].")</font>";
						}
						$this->uniqueArr[$field_name[$key]][]=$val;						
					}
					
					$msg['txt'].= "<td $bgcolor>";
					
					if(!$is_new) $msg['txt'].= "<b>".$actor[$field_name[$key]]." $old_code_msg</b> =>";
					$msg['txt'].= "<br> <b><font color='blue'>$val</font> $new_code_msg $foreign_msg $yn_msg $user_msg </b>";
					
					if($key==$primary_idx) $msg['txt'].=$msg_id;						
					$msg['txt'].= "</td>";
				}
				else { // old value == new value with value present						
					
					if($key==$primary_idx) $msg['txt'].= "<td>$val $old_code_msg $msg_id</td>";
					else $msg['txt'].= "<td $bgcolor>$val $old_code_msg</td>";
				}
			}
		}
		
		if($error===true) return false;
		if($not_mod && $not_mod_error) return "NOT_MOD";
	
		if($is_new){
			$insert_result=$this->batch_insert($actor,$record,$field_name);
			if($insert_result>=1) return 10; // inserted
			else return 3; // error
		}		
		else if($to_be_updated_in_all){ // update
			$update_result=$this->batch_update($actor,$record,$field_name);		
			if($update_result>=1) return 1; // updated				
			else if($update_result===0) return 2; // no change
			else return 3; // error
		}
		else return 2; // no change (default)
	}
	
	public function check_required($fd,$val){
		$val=trim($val);
		if(isset($this->fdReq[$fd]) && ($val===null || $val==='')) return false; // required but empty
		else return true; // not required or empty, so okay
	}
	
	public function check_code($fd,$val){
		global $utype,$ustatus; // Wlibrary only
		if(isset($this->fdRealCodeName[$fd])){
			$real_fd=$this->fdRealCodeName[$fd];
		}
		else $real_fd=$fd;
		
		if($fd=='utype') { // Wlibrary only
			if(isset($utype[$val])) return true;
			else return false;
		}
		else if($fd=='ustatus') { // Wlibrary only
			if(isset($ustatus[$val])) return true;
			else return false;
		}
		
		if(isset($this->fdCode[$fd]) && trim($val)) { // code
			return(\vwmldbm\code\check_code($real_fd,$val)); // return 1: okay, 2: okay but use_yn='N', false: not okay
		}
		return true; // not a code or empty, so okay
	}
	
	public function check_foreign($fd,$val){
		global $TB_PRE;
		
		if(!isset($this->fdForeign[$fd])) return true; // not foreign field, so okay
		if(isset($this->fdForeign[$fd]) && trim($val)=='') return true; // foreign but empty, so okay
		return \vwmldbm\sysmon\foreign_exist($TB_PRE."_".$this->tb,$fd,$val);
	}
	
	public function check_yn($fd,$val){
		$val=strtoupper(trim($val));
		if($val==='' || $val===null || !isset($this->fdYn[$fd])) return true; // empty or not Y/N field, so okay
		if($val=='Y' || $val=='N') return true; // okay
		else return false; // neither 'Y' nor 'N', so not okay
	}
	
	public function check_user_exist($uid){ // check if the user exists		
		$u=new BatchUser($uid);
		if(!$u['id']) return 0; // user doesn't exist		
		return 1; // use exists
	}

	public function batch_update($actor,$record,$field_name){
		global $conn,$DTB_PRE;
		if($this->tb=='users') {
			$user=true;
			$pass_list=['id','remember_token','created_at','updated_at','last_login'];
		}
		else if($this->tb=='book') {
			$book=true;
			$pass_list=['id','reg_date','rid','files','rfiles'];
		}
		else if($this->tb=='book_copy') {
			$book_copy=true;
			$pass_list=['id'];
		}
		
		$update_cnt=0;
	
	  // 1. Update actor
		$sql="UPDATE $DTB_PRE"."_".$this->tb." SET ";
		
		foreach($this->fd as $key =>$tmp) {
			$value=addslashes(trim($record[array_search($key,$field_name)]));			
			if(array_search($key,$pass_list)!==false) continue;				

			if(isset($this->fdRdOnly[$key])) continue;
			if(isset($this->fdPass[$key])) continue;
			if(isset($this->fdYn[$key])) {
				$value=strtoupper($value); 
			}
			if(!isset($this->fdString[$key])){
				if($value=='') 
					$sql.="`$key`=null,";
				else if($actor[$key]=='' && $value=='')
					continue;
				else $sql.="`$key`=\"".$value."\",";
			}
			else $sql.="`$key`=\"".$value."\",";
		}
		
		$sql=substr($sql,0,strlen($sql)-1); // remove the last comma
		
		$sql.=" WHERE inst='{$this->inst}' and id='{$record[0]}'";
		
		mysqli_query($conn,$sql);
		if(mysqli_affected_rows($conn)>0)
			$update_cnt++;
			
		// echo $sql."<br>";
		return($update_cnt);
	}
	
	public function batch_insert($actor,$record,$field_name){
		global $conn,$DTB_PRE;
		if($this->tb=='users') {
			$user=true;
			$pass_list=['id','remember_token','created_at','updated_at','last_login'];
		}
		else if($this->tb=='book') {
			$book=true;
			$pass_list=['id','reg_date','rid','files','rfiles'];
		}
		else if($this->tb=='book_copy') {
			$book_copy=true;
			$pass_list=['id'];
		}
		$insert_cnt=0;

	  	mysqli_begin_transaction($conn); // transaction: all or nothing!
		
	  // 1. Insert actor
		if($user) $sql="INSERT INTO {$DTB_PRE}_{$this->tb} (inst,id,password,";
		else if($book) $sql="INSERT INTO {$DTB_PRE}_{$this->tb} (inst,id,rid,";
		else if($book_copy) $sql="INSERT INTO {$DTB_PRE}_{$this->tb} (inst,id,";
		
		foreach($this->fd as $key =>$tmp) {
			if(isset($this->fdPass[$key])) continue;			
			if(array_search($key,$pass_list)!==false) continue;				

			if(isset($this->fdOtherTbFd[$key])) continue;
			
			$value=trim($record[array_search($key,$field_name)]);			
			if($value==='' || $value===null) continue;
			$sql.="`$key`,";
		} 
		
		$sql=substr($sql,0,strlen($sql)-1).") "; // remove the last comma		
	
		if($user) {
			// $email_idx=array_search('email',$field_name);
			// $email_val=strtolower(trim($record[$email_idx]));
			// $record[$email_idx]=$email_val;
			// $hpwd=password_hash($email_val,PASSWORD_BCRYPT);
			
			$hpwd=password_hash($record[0],PASSWORD_BCRYPT); // Laravel 6 uses Bcrypt
			$sql.=" VALUES('{$this->inst}','{$record[0]}','{$hpwd}',"; // inst, ID 
		}
		if($book) {
			$primary_idx=array_search('id',$field_name);
			if($record[$primary_idx]) $bid=$record[$primary_idx];
			else $bid=BatchBook::get_new_bid($_SESSION['lib_inst']); // book id 
			
			$rid=BatchBook::get_new_rid($_SESSION['lib_inst']); // random id 
			$sql.=" VALUES('{$this->inst}','{$bid}','{$rid}',"; // inst, ID, rid 
		}
		else if($book_copy) {
			$primary_idx=array_search('id',$field_name);
			if($record[$primary_idx]) $id=$record[$primary_idx];
			else $id=BatchBookCopy::get_available_id($_SESSION['lib_inst']); // book id
			
			$sql.=" VALUES('{$this->inst}','{$id}',"; // inst, ID
		}
		
		foreach($this->fd as $key =>$tmp) {
			if(isset($this->fdPass[$key])) continue;
			
			if(array_search($key,$pass_list)!==false) continue;				

			if(isset($this->fdOtherTbFd[$key])) continue;
			
			$value=trim($record[array_search($key,$field_name)]);
			if($value==='' || $value===null) continue;			
			if(isset($this->fdYn[$key])) $value=strtoupper($value);
			
			$sql.="\"".addslashes($value)."\",";
		}
		$sql=substr($sql,0,strlen($sql)-1).") "; // remove the last comma

		mysqli_query($conn,$sql);
		if(mysqli_affected_rows($conn)>0){
			$insert_cnt++;
		}
		// else echo $sql."<br>";

		if($insert_cnt>0) {
			mysqli_commit($conn);	
		}
		else {
			mysqli_rollback($conn);			
		}
		
		return($insert_cnt);
	}
}

class BatchBook extends BatchUser {
	public function __construct($inst=null) {		
		$this->inst=($inst?$inst:$_SESSION['lib_inst']);
		$this->tb="book";
		$this->target="book";
		$this->primary_key="id";
		$this->fdBasic=array(); // fields that will apear in the beginning of the Excel file: important fields
		$this->fdNonBasic=array(); // fields that will apear in the end of the Excel file
		$this->fd=array(); // all fileds
		$this->fdCode=array(); // code fields
		$this->fdForeign=array(); // non-code, non-user forien fields
		$this->fdUser=array(); // user that needs to be checked if it exists, if it doens't violate the authority (school/dept admin)
		
		$this->fdReq=array(); // required fields: should not be empty
		$this->fdYn=array(); // Y/N field		
		$this->fdRdOnly=array(); // Readonly field		
		$this->fdPass=array(); // Don't update field		
		$this->fdDate=array(); // Date field- 2018-01-12		
		$this->fdDateTime=array(); // Date field- 2018-01-12 11:30:10		
		$this->fdRealCodeName=array(); // some codes may have different name compared with their original code name
		
		$fd_list=['id','rid','title','author','publisher','pub_date','c_lang','isbn','eisbn',
				  'cover_image','keywords','c_rtype',
				  'c_genre','e_resource_yn','rdonly_pdf_yn','rdonly_video_yn','hide_yn','price',
				  'desc','url','e_res_af_login_yn'];
				  
		
		foreach($fd_list as $val) $this->fd[$val]=''; // initialization
			
		$this->fdBasic["id"]='';
		$this->fdBasic["title"]='';
		$this->fdBasic["author"]='';
		$this->fdBasic["publisher"]='';
		$this->fdBasic["pub_date"]='';
		$this->fdBasic["c_lang"]='';
		$this->fdBasic["isbn"]='';
		$this->fdBasic["eisbn"]='';
		$this->fdBasic["c_rtype"]='';
		$this->fdBasic["c_genre"]='';
		$this->fdBasic["e_resource_yn"]='';
		
		$this->fdCode["c_lang"]='';
		$this->fdCode["c_rtype"]='';
		$this->fdCode["c_genre"]='';		
			
		$this->fdReq["id"]='';
		//$this->fdReq["fname"]='';
		$this->fdReq["title"]='';
		
		$this->fdYn["e_resource_yn"]='';
		$this->fdYn["rdonly_pdf_yn"]='';
		$this->fdYn["rdonly_video_yn"]='';
		$this->fdYn["hide_yn"]='';
		$this->fdYn["e_res_af_login_yn"]='';
				
		$this->fdPass["id"]='';
				
		$this->fdDate["pub_date"]='';
		
		$this->fdUnique["isbn"]='';
		$this->fdUnique["eisbn"]='';
		
		$this->uniqueArr=array();
		
		// $this->fdDateTime[""]='';
		
	 // fdNonBasic
		foreach($this->fd as $key=>$val){
			$this->fdNonBasic[$key]='';
		}
		
		foreach($this->fdBasic as $key => $val){ 
			unset($this->fdNonBasic[$key]); // remove basic fd
		}
		
		// $this->fdRealCodeName['c_lang']='vwmldbm_c_lang';
		
		$this->available_primary_key=0; // for new record's primary key (Auto increment)
	}
	
	public function get_available_id(){
		global $conn,$DTB_PRE;
		$sql="select max(id) as max from $DTB_PRE"."_book 
			where inst='{$_SESSION['lib_inst']}'";
		$res=mysqli_query($conn,$sql);
		if($res) $rs=mysqli_fetch_array($res);
		return $rs['max']+1;
	}
	
	public static function get_new_rid($inst=null) {
		if(!$inst) $inst=$_SESSION['lib_inst'];
		global $conn,$DTB_PRE;	
		while(true){
			$rid=rand(10000,1000000000); // random course id for security
			$sql = "select count(rid) as cnt from {$DTB_PRE}_book where inst='$inst' and rid='$rid'";
			$res=mysqli_query($conn,$sql);
			if($res) $rs=mysqli_fetch_array($res);
			if($rs['cnt']>0) ;
			else break;
		}
		return $rid;
	}
	
	public static function get_new_bid($inst=null) {
		if(!$inst) $inst=$_SESSION['lib_inst'];
		global $conn,$DTB_PRE;	

		$sql = "select max(id) as max from {$DTB_PRE}_book where inst='$inst'";
		$res=mysqli_query($conn,$sql);
		if($res) $rs=mysqli_fetch_array($res);
		return $rs['max']+1;
	}
}

class BatchBookCopy extends BatchUser {
	public function __construct($inst=null) {		
		$this->inst=($inst?$inst:$_SESSION['lib_inst']);
		$this->tb="book_copy";
		$this->target="book_copy";
		$this->primary_key="id";
		$this->fdBasic=array(); // fields that will apear in the beginning of the Excel file: important fields
		$this->fdNonBasic=array(); // fields that will apear in the end of the Excel file
		$this->fd=array(); // all fileds
		$this->fdCode=array(); // code fields
		$this->fdForeign=array(); // non-code, non-user forien fields
		$this->fdUser=array(); // user that needs to be checked if it exists, if it doens't violate the authority (school/dept admin)
		
		$this->fdReq=array(); // required fields: should not be empty
		$this->fdYn=array(); // Y/N field		
		$this->fdRdOnly=array(); // Readonly field		
		$this->fdPass=array(); // Don't update field		
		$this->fdDate=array(); // Date field- 2018-01-12		
		$this->fdDateTime=array(); // Date field- 2018-01-12 11:30:10		
		$this->fdRealCodeName=array(); // some codes may have different name compared with their original code name
		
		$fd_list=['id','bid','barcode','call_no','location','c_rstatus','comment','title'];
				  
		
		foreach($fd_list as $val) $this->fd[$val]=''; // initialization
			
		$this->fdBasic["id"]='';
		$this->fdBasic["bid"]='';
		$this->fdBasic["barcode"]='';
		$this->fdBasic["call_no"]='';
		$this->fdBasic["location"]='';
		$this->fdBasic["c_rstatus"]='';
				
		$this->fdCode["c_rstatus"]='';
			
		$this->fdReq["id"]='';
		$this->fdReq["bid"]='';
		
		// $this->fdYn["e_resource_yn"]='';
		
		$this->fdRdOnly["title"]='';				
		
		$this->fdPass["id"]='';
		$this->fdPass["title"]='';
				
		// $this->fdDate["pub_date"]='';
		
		$this->fdUnique["barcode"]='';
		$this->fdUnique["call_no"]='';
		
		$this->uniqueArr=array();
		
		// $this->fdDateTime[""]='';
		
	 // fdNonBasic
		foreach($this->fd as $key=>$val){
			$this->fdNonBasic[$key]='';
		}
		
		foreach($this->fdBasic as $key => $val){ 
			unset($this->fdNonBasic[$key]); // remove basic fd
		}
		
		// $this->fdRealCodeName['c_lang']='vwmldbm_c_lang';
		
		$this->available_primary_key=0; // for new record's primary key (Auto increment)
	}
	
	public function get_available_id(){
		global $conn,$DTB_PRE;
		$sql="select max(id) as max from $DTB_PRE"."_book_copy 
			where inst='{$_SESSION['lib_inst']}'";
		$res=mysqli_query($conn,$sql);
		if($res) $rs=mysqli_fetch_array($res);
		return $rs['max']+1;
	}
}


function get_comment_width($str,$delimiter=PHP_EOL){
	$token=explode($delimiter,$str);
	$max=mb_strlen($token[0]);
	for($i=1;$i<count($token);$i++)
		if(mb_strlen($token[$i])>$max) $max=mb_strlen($token[$i]);
	
	return $max;
}

function get_comment_height($str,$delimiter=PHP_EOL){
	$token=explode($delimiter,$str);
	return count($token);
}

function check_data($filePath,&$check_arr){
	global $wmlang;
	if(strtolower(substr($filePath,-4,4)!='xlsx')) return; // illegal file type
	$inputFileType = 'Xlsx';
	$inputFileName = $filePath;
	
	// Create a new Reader of the type defined in $inputFileType
	$reader = IOFactory::createReader($inputFileType);
	if(!$reader) return;
	
	// Load $inputFileName to a PhpSpreadsheet Object
	$spreadsheet = $reader->load($inputFileName);
	
	$spreadsheet->setActiveSheetIndex(0);
	$aSheet=$spreadsheet->getActiveSheet();
	
	$batch=new BatchData($aSheet->getTitle(),$spreadsheet);
	
	if(!$batch->dObj) { // file has problem(s), so stop
		return; 
	}
	
	$data=$aSheet->toArray(null,true,true,false); // whole data of the sheet
	
	// if($sys_var->sensitive_data_yn=='Y')
		// echo "<h3 style='color:magenta;'>".$wmlang['txt']['sen_data_warn']."</style></h3>";
	
	// if($sys_var->confidential_data_yn=='Y')
		// echo "<h3 style='color:red;'>".$wmlang['txt']['conf_data_warn']."</style></h3>";
	
	
	if(($msg=$batch->dObj->is_worksheet_valid($data))!==true) {
		$msg= "<h3 style='color:red;'>".$wmlang['js']['error'].$msg."</style></h3>";
		return $msg;
	}
	if($msg===true) $msg=""; // remove unwanted '1'
	
	$check_arr['worksheet']=true;
	$msg.= "<h3 style='color:blue;'>".$wmlang['js']['success']." ".$wmlang['txt']['wksheet_ok']."</style></h3>";

	$data_ok=true;	
  // Print the field names
	$msg.= "<font color=red><b>*</b></font> ".$wmlang['txt']['required'].", &nbsp;&nbsp;&nbsp;&nbsp;";
	$msg.= "<font color=blue><b>@</b></font> ".$wmlang['txt']['foreign_val'].", &nbsp;&nbsp;&nbsp;&nbsp;";
	$msg.= "<font color=purple><b>x</b></font> ".$wmlang['txt']['rdonly'].", &nbsp;&nbsp;&nbsp;&nbsp;";
	$msg.= "<font color=green><b>#</b></font> ".$wmlang['txt']['optional'].", &nbsp;&nbsp;&nbsp;&nbsp;";
	$msg.= "<span style='background-color:pink;'> ".$wmlang['txt']['red_box']." </span>: ".$wmlang['txt']['error']."&nbsp;&nbsp;&nbsp;&nbsp;";
	$msg.= "<span style='background-color:yellow;'> ".$wmlang['txt']['yellow_box']." </span>: ".$wmlang['txt']['change'];
	$msg.= "<center><table border=1 id='Gtable'><tr>";
 
 // printing table header	
	$msg.= "<th></th>";

	foreach($data[1] as $key=>$fd){
		if($key>=count($batch->dObj->fd)) break;
		
		if(!$fd) {
			$data_ok=false;
		}
		
		$sp_msg='';
		
		if($fd==$batch->dObj->primary_key) $id_idx=$key;  	// ** it was $key instead of $fd 2019/09/24
		else if($fd==$batch->dObj->primary_key2) $id_idx2=$key;
		
		if(isset($batch->dObj->fdReq[$fd])) 
			$sp_msg.= "<font color=red><b>*</b></font>";
		else if(isset($batch->dObj->fdForeign[$fd])) 
			$sp_msg.= "<font color=blue><b>@</b></font>";
		else if(isset($batch->dObj->fdRdOnly[$fd])) 
			$sp_msg.= "<font color=purple><b>x</b></font>";
		else if(isset($batch->dObj->fdOptional[$fd])) 
			$sp_msg.= "<font color=green><b>#</b></font>";
		
		if($sp_msg) $msg.= "<th>$sp_msg $fd</th>";
		else $msg.= "<th $color_tag>$fd</th>";
		
	}	
	$msg.= "</tr>";
 // End of printing table header

	for($i=2;$i<count($data);$i++){
		// if($check_arr['target']=='subject' && !$data[$i][$id_idx]) continue;
		// else if(!$data[$i][$id_idx]) continue;
				
		$check_record_msg['txt']=null;
		$check_record_result=$batch->dObj->check_record($data,$i,$id_idx,$check_record_msg,$id_idx2);
		if($check_record_result===false) $data_ok=false;
		else if($check_record_result==="MOD") $check_arr['mod']=true;
			
		$msg.=$check_record_msg['txt'];
	}	
	
	$msg.= "</table></center>";
	if($check_record_result==='NOT_MOD') $check_arr['data']="NOT_MOD";
	else if($data_ok) $check_arr['data']=true;
	$check_arr['target']= $aSheet->getTitle();
	return $msg;
}

function update_data($filePath,&$check_arr) {
	global $wmlang,$course;
	if(strtolower(substr($filePath,-4,4)!='xlsx')) return; // illegal file type
	$inputFileType = 'Xlsx';
	$inputFileName = $filePath;
	
	// Create a new Reader of the type defined in $inputFileType
	$reader = IOFactory::createReader($inputFileType);
	if(!$reader) return;
	
	// Load $inputFileName to a PhpSpreadsheet Object
	$spreadsheet = $reader->load($inputFileName);
	
	$spreadsheet->setActiveSheetIndex(0);
	$aSheet=$spreadsheet->getActiveSheet();
	
	$batch=new BatchData($aSheet->getTitle(),$spreadsheet);
	
	if(!$batch->dObj) { // file has problem(s), so stop
		return ; 
	}
	
	$data=$aSheet->toArray(null,true,true,false); // whole data of the sheet
	
	if(($msg=$batch->dObj->is_worksheet_valid($data))!==true) {
		$msg= "<h3 style='color:red;'>".$wmlang['js']['error'].$msg."</style></h3>";
		return $msg;
	}	
	
	if($msg===true) $msg=""; // remove unwanted '1'
	
	$check_arr['worksheet']=true;
	//$msg.= "<h3 style='color:blue;'>".$wmlang['js']['success']."Worksheet is Valid</style></h3>";

	$data_ok=true;	

  // Print the field names
	$msg.= "<font color=red><b>*</b></font> ".$wmlang['txt']['required'].", &nbsp;&nbsp;&nbsp;&nbsp;";
	$msg.= "<font color=blue><b>@</b></font> ".$wmlang['txt']['foreign_val'].", &nbsp;&nbsp;&nbsp;&nbsp;";
	$msg.= "<font color=purple><b>x</b></font> ".$wmlang['txt']['rdonly'].", &nbsp;&nbsp;&nbsp;&nbsp;";
	$msg.= "<font color=green><b>#</b></font> ".$wmlang['txt']['optional'].", &nbsp;&nbsp;&nbsp;&nbsp;";
	$msg.= "<span style='background-color:pink;'> ".$wmlang['txt']['red_box']." </span>: ".$wmlang['txt']['error']."&nbsp;&nbsp;&nbsp;&nbsp;";
	$msg.= "<span style='background-color:yellow;'> ".$wmlang['txt']['yellow_box']." </span>: ".$wmlang['txt']['change'];
	$msg.= "<center><table border=1 id='Gtable'><tr>";
	$msg.= "<th></th>";
	foreach($data[1] as $key=>$fd){		
		if($batch->target=='student'){
			if($key>=count($batch->dObj->fd)+count($batch->dObj->fdExt)) break;
		}
		else if($key>=count($batch->dObj->fd)) break;
		$sp_msg='';
		
		if($key==$batch->dObj->primary_key) $id_idx=$key;
		else if($fd==$batch->dObj->primary_key2) $id_idx2=$key;
		else if($fd==$batch->dObj->primary_key) $id_idx=$key; // 2025.2.26 Sam (it should be $fd!)
		
		if(isset($batch->dObj->fdReq[$fd])) 
			$sp_msg.= "<font color=red><b>*</b></font>";
		else if(isset($batch->dObj->fdForeign[$fd])) 
			$sp_msg.= "<font color=blue><b>@</b></font>";
		else if(isset($batch->dObj->fdRdOnly[$fd])) 
			$sp_msg.= "<font color=purple><b>x</b></font>";
		else if(isset($batch->dObj->fdOptional[$fd])) 
			$sp_msg.= "<font color=green><b>#</b></font>";
		
		if($sp_msg) $msg.= "<th>$sp_msg $fd</th>";
		else $msg.= "<th>$fd</th>";
		
	}
	$msg.="<th>".$wmlang['txt']['result']."</th></tr>";
	
	for($i=2;$i<count($data);$i++){
		// if($check_arr['target']=='subject' && !$data[$i][$id_idx]) continue;
		// else if(!$data[$i][$id_idx]) continue;

		$is_empty=true;
		foreach($data[$i] as $val){
			if(trim($val)!=='') $is_empty=false;
		}
		if($is_empty) continue; // if empty row, skip it
	

		$tmsg="<td>".($i-1)."</td>";
		$update_record_msg['txt']=null;

		$update_result=$batch->dObj->update_record($data,$i,$id_idx,$update_record_msg,$id_idx2);

		$tmsg.=$update_record_msg['txt'];
		if($update_result===1){ // update success
			$tmsg.= "<td><font color='blue'>".$wmlang['txt']['updated']."</font></td>";
			$bgcolor="style='background-color:CCFFCC;' ";
		}
		else if($update_result===10){ // insertion success
			$tmsg.= "<td><font color='blue'>".$wmlang['txt']['inserted']."</font></td>";
			$bgcolor="style='background-color:CCFFCC;' ";
		}
		else if($update_result===2){ // no change
			$bgcolor='';
			$tmsg.= "<td>".$wmlang['txt']['no_change']."</td>";
		}
		else {
			$data_ok=false; // update fail
			$tmsg.= "<td><font color=red><b>".$wmlang['txt']['error']."</b></font></td>";
			$bgcolor="style='background-color:FFCCCC;' ";
		}
		$msg.="<tr $bgcolor>$tmsg</tr>";
	}	
	
	$msg.= "</table></center>";
	if($data_ok) $check_arr['data']=true;
	$check_arr['target']= $aSheet->getTitle();
	return $msg;
}
?>
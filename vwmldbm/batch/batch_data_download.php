<?PHP
/*VWMLDBM DISCLAIMER*==================================================
 Copyright (c) 2017 Sang Jin Han and other contributors, 
	http://wise4edu.com
 Released under the MIT license
 =================================================*VWMLDBM DISCLAIMER*/
namespace vwmldbm;
session_start();
set_time_limit(180); //  set the execution time limit in seconds

require_once("../config.php");

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
require $VWMLDBM['VWMLDBM_RT'].'/lib/lib_batch_data.php';
require $VWMLDBM['VWMLDBM_RT'].'/lib/code.php';
require $VWMLDBM['VWMLDBM_RT'].'/lib/system.php';
require $VWMLDBM['VWMLDBM_RT'].'/lib/img.php';

if(!system\isAdmin()) die("You are not authorized to access this page.");

$target=$_GET['target'];

if(!isset($_SESSION['lang'])) $_SESSION['lang']=10; // default English

//error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED);
// ini_set("display_errors", 0);

$spreadsheet = new Spreadsheet();

$batch=new batch\BatchData($target,$spreadsheet);

$spreadsheet->setActiveSheetIndex(0);
$aSheet=$spreadsheet->getActiveSheet();
$aSheet->setTitle($target);
//$aSheet->setCellValueExplicitByColumnAndRow(3, 2, "Done by setCellValueExplicitByColumnAndRow","s");

$fname=$target.date("Ymd").img\genRandStr(20).".xlsx";

$curRow=1;
$curCol=0;
$dObj=$batch->dObj;

//// Print the legend: eg, Pink: required
$aSheet->getStyleByColumnAndRow(1,$curRow)->getFill()->setFillType(Fill::FILL_SOLID);
$aSheet->getStyleByColumnAndRow(1,$curRow)->getFill()->getStartColor()->setARGB('FFFFAAAA');
$aSheet->setCellValueExplicitByColumnAndRow(2,$curRow,": ".$wmlang['txt']['required'],"s");

// Basic
$aSheet->getStyleByColumnAndRow(4,$curRow)->getFill()->setFillType(Fill::FILL_SOLID);
$aSheet->getStyleByColumnAndRow(4,$curRow)->getFill()->getStartColor()->setARGB('FFFFFF88');
$aSheet->setCellValueExplicitByColumnAndRow(5,$curRow,": ".$wmlang['txt']['basic'],"s");

// Cannot be modified: eg, course that is not in the reg/curr semester
$aSheet->getStyleByColumnAndRow(7+$add,$curRow)->getFill()->setFillType(Fill::FILL_SOLID);
$aSheet->getStyleByColumnAndRow(7+$add,$curRow)->getFill()->getStartColor()->setARGB('FFDDDDDD');
$aSheet->setCellValueExplicitByColumnAndRow(8+$add,$curRow,": ".$wmlang['txt']['cannot_mod'],"s");

// Readonly
$aSheet->setCellValueExplicitByColumnAndRow(10+$add,$curRow,$wmlang['txt']['purple_font'],"s");
$aSheet->setCellValueExplicitByColumnAndRow(11+$add,$curRow,": ".$wmlang['txt']['rdonly'],"s");
$aSheet->getStyleByColumnAndRow(10+$add,$curRow)->getFont()->getColor()->setARGB('FF800080');

// Foreign key
$aSheet->setCellValueExplicitByColumnAndRow(13+$add,$curRow,$wmlang['txt']['blue_font'],"s");
$aSheet->setCellValueExplicitByColumnAndRow(14+$add,$curRow,": ".$wmlang['txt']['for_exist'],"s");
$aSheet->getStyleByColumnAndRow(13+$add,$curRow)->getFont()->getColor()->setARGB('FF0000FF');

// Optional key
$aSheet->setCellValueExplicitByColumnAndRow(16+$add,$curRow,$wmlang['txt']['green_font'],"s");
$aSheet->setCellValueExplicitByColumnAndRow(17+$add,$curRow,": ".$wmlang['txt']['optional'],"s");
$aSheet->getStyleByColumnAndRow(16+$add,$curRow)->getFont()->getColor()->setARGB('FF00FF00');

$curRow++;	

// thin black border outline around column
$borderStyle = [
	'borders' => [
		'outline' => [
			'borderStyle' => Border::BORDER_THIN,
			'color' => ['argb' => 'FF000000'],
		],
	],
];


//// Print the field names-Basic Field: row #1 

foreach($dObj->fdBasic as $fd => $val){
	$curCol++;
	print_field_names($aSheet,$dObj,$fd,$dObj->tb,$curCol,$curRow,$borderStyle);
}

//// Print the field names-NonBasic Fields(Rest of the fileds) : row #1 
foreach($dObj->fdNonBasic as $fd => $val){
	$curCol++;
	print_field_names($aSheet,$dObj,$fd,$dObj->tb,$curCol,$curRow,$borderStyle);
}

//// End of printing the 1st row, field names

//// print the existing records
$arr=array();
$dObj->get_list($arr,$_GET['bc_opt']);

foreach($arr as $key => $val){
	if($target=='user') {
		$arr_obj= $val;
	}
	else if($target=='book') {
		$arr_obj=$val;
	}
	else if($target=='book_copy') {
		if(substr($key,0,4)=='NEW_') $arr_obj=array('id'=>'NEW','bid'=>$val['id'],'title'=>$val['title']);
		else $arr_obj=$val;
	}
	
	$curCol=0;
	$curRow++;
	$curCol=print_record($arr_obj,$aSheet,$dObj,$dObj->fdBasic,$curRow,$curCol,$borderStyle);
	$curCol=print_record($arr_obj,$aSheet,$dObj,$dObj->fdNonBasic,$curRow,$curCol,$borderStyle);
}

//// End of printing the existing records

$aSheet->freezePane('A3');

// echo $aSheet->getCell('A1')."<br>";
// echo $aSheet->getCellByColumnAndRow(3,2);
// $batch->save_excel($fname);
$batch->php_output($fname);

function print_field_names(&$aSheet,$dObj,$fd,$tb,$curCol,$curRow,$borderStyle){
	$aSheet->setCellValueExplicitByColumnAndRow($curCol,$curRow,$fd,"s");
	
	if(isset($dObj->fdCode[$fd])){	// Code	
		// Add comment and TextRun
		if(isset($dObj->fdRealFdName[$fd]) && $dObj->fdRealFdName[$fd][0]!=''){
			$real_tb=$dObj->fdRealFdName[$fd][0];
			$real_fd=$dObj->fdRealFdName[$fd][1];
		}
		else $real_tb=$tb;
		
		if(isset($dObj->fdOtherTbFd[$fd]) && $dObj->fdOtherTbFd[$fd]!='') $real_fd=$dObj->fdOtherTbFd[$fd];
		else $real_fd=$fd;
		
		$commentRichText = $aSheet->getCommentByColumnAndRow($curCol,$curRow)->getText()->createTextRun(\vwmldbm\code\get_field_name($real_tb,$real_fd,"TWO_LANG"));

		$commentRichText->getFont()->setBold(true);		
		$code_list=$dObj->get_code_list($fd,null,false);
	
		$t_width=80+4*\vwmldbm\batch\get_comment_width($code_list);
		$t_height=16+16*\vwmldbm\batch\get_comment_height($code_list);
	
		$aSheet->getCommentByColumnAndRow($curCol,$curRow)->getText()->createTextRun("\r\n");
		$aSheet->getCommentByColumnAndRow($curCol,$curRow)->getText()->createTextRun($code_list);
	}
	else { // Normal Field (Not Code)
		if(isset($dObj->fdRealFdName[$fd]) && $dObj->fdRealFdName[$fd][0]!=''){
			$real_tb=$dObj->fdRealFdName[$fd][0];
			$real_fd=$dObj->fdRealFdName[$fd][1];
		}
		else if(isset($dObj->fdOtherTbFd[$fd]) && $dObj->fdOtherTbFd[$fd]!='') $real_fd=$dObj->fdOtherTbFd[$fd];
		else $real_fd=$fd;
		// Add comment and TextRun
	
		$txt=\vwmldbm\code\get_field_name($real_tb,$real_fd,"TWO_LANG");
	
		if(isset($dObj->fdYn[$fd])){			
			$txt.="\r\nY: ".\vwmldbm\code\get_c_yn('Y');
			$txt.="\r\nN: ".\vwmldbm\code\get_c_yn('N');
		}
		else if(isset($dObj->fdDate[$fd])){			
			$txt.="\r\nYYYY-MM-DD";
		}
		else if(isset($dObj->fdDateTime[$fd])){			
			$txt.="\r\nYYYY-MM-DD HH:MM:SS";
		}
		$commentRichText = $aSheet->getCommentByColumnAndRow($curCol,$curRow)->getText()->createTextRun(" ".$txt);
		$commentRichText->getFont()->setBold(true);
		
		$t_width=80+4*\vwmldbm\batch\get_comment_width($txt);
		$t_height=16+16*\vwmldbm\batch\get_comment_height($txt);
	}	
			
	$aSheet->getCommentByColumnAndRow($curCol,$curRow)->setWidth($t_width.'pt');
	$aSheet->getCommentByColumnAndRow($curCol,$curRow)->setHeight($t_height.'pt');
	$aSheet->getCommentByColumnAndRow($curCol,$curRow)->setMarginLeft('10pt');
	
	if(isset($dObj->fdReq[$fd])){ // if required field, highlight with pink
		$aSheet->getStyleByColumnAndRow($curCol,$curRow)->getFill()->setFillType(Fill::FILL_SOLID);
		$aSheet->getStyleByColumnAndRow($curCol,$curRow)->getFill()->getStartColor()->setARGB('FFFFAAAA');
	}
	else if(isset($dObj->fdBasic[$fd])){ // if basick field, highlight with yellow
		$aSheet->getStyleByColumnAndRow($curCol,$curRow)->getFill()->setFillType(Fill::FILL_SOLID);
		$aSheet->getStyleByColumnAndRow($curCol,$curRow)->getFill()->getStartColor()->setARGB('FFFFFF88');
	}
		
	if(isset($dObj->fdRdOnly[$fd])){ // if readonly field, highlight with purple font
		$aSheet->getStyleByColumnAndRow($curCol,$curRow)->getFont()->getColor()->setARGB('FF8B008B');
	}
	else if(isset($dObj->fdForeign[$fd])){ // if foreign field, highlight with blue font
		$aSheet->getStyleByColumnAndRow($curCol,$curRow)->getFont()->getColor()->setARGB('FF0000FF');
	}	
	else if(isset($dObj->fdOptional[$fd])){ // if optional field, highlight with green font
		$aSheet->getStyleByColumnAndRow($curCol,$curRow)->getFont()->getColor()->setARGB('FF00FF00');
	}
	
	// Set thin black border outline around column
	if($borderStyle) $aSheet->getStyleByColumnAndRow($curCol,$curRow)->applyFromArray($borderStyle);
}

function print_record($arr,&$aSheet,$dObj,$dObj_sub,$curRow,$curCol,$borderStyle) {
	foreach($dObj_sub as $fd => $val){
		if(isset($arr[$fd]) && $arr[$fd] && is_numeric($arr[$fd])) $dtype='n';
		else {
			$dtype='s';
		}
		if(!isset($arr[$fd])) {$curCol++; continue;}
		$aSheet->setCellValueExplicitByColumnAndRow(++$curCol,$curRow,$arr[$fd],$dtype);
		if(isset($dObj->fdDate[$fd])) $aSheet->getStyleByColumnAndRow($curCol,$curRow)->getNumberFormat()->setFormatCode('yyyy-mm-dd');
		else if(isset($dObj->fdDateTime[$fd])) $aSheet->getStyleByColumnAndRow($curCol,$curRow)->getNumberFormat()->setFormatCode('yyyy-mm-dd hh:mm:ss');
		
		// if(!isset($arr[$fd])) continue; 
		//if($borderStyle) $aSheet->getStyleByColumnAndRow($curCol,$curRow)->applyFromArray($borderStyle);
	}
	return $curCol;
}
?>

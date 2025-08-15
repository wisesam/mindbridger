<?PHP
/*============================================================
  This is a testing version. NO redistribution is allowed. 
  All rights reserved by wise4edu.com.
  
  Program : Library system private file upload
  Author  : WISE2.0 Development Team
  Date    : 2020.04.28	
  Action  : 
  Comment : 
  ===========================================================*/
namespace wlibrary;
session_start(); 
if(!$_SESSION['lib_inst']) { session_destroy(); die;}
if($_SERVER["HTTP_REFERER"]=="") {session_destroy();die;} // illegal access

require_once($_SESSION['app.root']."/app/Libraries/code.php");
require_once($_SESSION['app.root']."/app/Libraries/book.php");
require_once($_SESSION['app.root2']."/vwmldbm/dbcon.php");

ob_start(); 
ob_end_clean();

// File upload limit control:
// Admins: As defined in ".htaccess": eg, 500MB

$post_max_size=code\post_max_size('bytes');
$upload_max_filesize=code\upload_max_filesize('bytes');
$max_file_uploads=code\max_file_uploads();

$ADM_FILE_SIZE_LIMIT=520000000; // default is 110MB
if($post_max_size>$ADM_FILE_SIZE_LIMIT) {
	$post_max_size=$ADM_FILE_SIZE_LIMIT;
	$upload_max_filesize=$ADM_FILE_SIZE_LIMIT;		
}
$max_file_uploads=\wlibrary\code\max_file_uploads();

$MAX_FSIZE=$upload_max_filesize;  // set this to limit size
$num_already_files=0; // global variable for checking number of already uploaded files

$perm['M']='Y'; // TBD
$perm['A']='Y'; // TBD

if($_POST['operation']=='Modify') { // Modify
	if($perm['M']!='Y') exit;
	$id=$_POST['id'];
}
else die; // illegal access

$book=new book\Book($id);
$rid=$book->rid;

$file_ok=true;
$files=null; // file names to be stored in 'pi_cms_item' table
$rfiles=null; // random file names w/o extension to be stored in 'pi_cms_item' table

$fdir=$_SESSION['app.root']."/storage/app/ebook/{$_SESSION['lib_inst']}/$rid";
// Check if the directory exists with the rid
if (!file_exists($fdir)) {
    if (!mkdir($fdir, 0775, true)) {
        echo "Failed to create directory: $fdir";
        $file_ok = false;
    }
}


$wasMod=false;	
if($_FILES['openFile']['name'][0]) {	
	foreach ($_FILES['openFile']['name'] as $key=>$name ) {
		if(!$_FILES["openFile"]["tmp_name"][$key]) $file_ok=false;
		
		// echo "<br>".$name." : ";
				
		$filePath = "$fdir/$name"; // to be renamed
		$file_basename = mb_substr($name, 0, strripos($name, '.')); // get file extention
		$file_ext = strtolower(mb_substr($name, strripos($name, '.'))); 
		$not_allowed_file_types = array('.php');	
		$filesize = $_FILES["openFile"]["size"][$key];
		
		if (in_array(strtolower($file_ext),$not_allowed_file_types)){	
			// echo implode(', ',$not_allowed_file_types)." file types are not allowed";
			$file_ok=false;
			continue;
		}			
		if($filesize>$MAX_FSIZE){
			// echo "$name ".$wmlang['js']['fsize_too_big']."<br>";
			$file_ok=false;
		}
		if($book->file_exist($name)) {
			$file_ok=false;
			// echo "$name already exists!<br>";
		}
	}
	
	if($file_ok) {
		foreach ($_FILES['openFile']['name'] as $key=>$name ) {
			if (move_uploaded_file($_FILES["openFile"]["tmp_name"][$key], $filePath)) {
				$rfname=code\genRandStr(12);
				$dest_file= "$fdir/$rfname";
				rename($filePath,$dest_file);
				// echo "The file ". basename( $_FILES["fileToUpload"]["name"]). "$name was uploaded.<br>";
			// file name handling
				$files.=addslashes($name).";";
				$rfiles.=$rfname.";";
				
			} else {
				// echo "Sorry, there was an error uploading your file.";
				$file_ok=false;
			}
		}
		$new_files=$book->files.$files;
		$new_rfiles=$book->rfiles.$rfiles;
		if($book->update('files',$new_files)) $wasMod=true;	
		if($book->update('rfiles',$new_rfiles)) $wasMod=true;	
	}
}

if ($file_ok==true)	{ // including the case without file upload		
	if($_POST['operation']=='Modify') echo "MOD_SUCCESS";// okay
	
} else { // file upload failed 
	// if($_FILES['openFile']['name'][0])
		// book\remove_upfiles($rcid,$files,$rfiles); // remove any uploaded file [TBD]
	echo "MOD_FAIL";
}

?>
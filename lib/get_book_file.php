<?PHP
/*============================================================
  This is a testing version. NO redistribution is allowed. 
  All rights reserved by wise4edu.com.
  
  Program : Library system get private file
  Author  : WISE2.0 Development Team
  Date    : 2020.04.30	
  Action  : 
  Comment : 
  ===========================================================*/
namespace wlibrary;
session_start();
// error_reporting(1); // Disable all error reporting
// ini_set('display_errors', '1'); // Do not display errors
// if(!$_SESSION['lib_inst']) { session_destroy(); die;}
// if($_SERVER["HTTP_REFERER"]=="") {session_destroy();die;} // illegal access
require_once($_SESSION['app.root']."/app/Libraries/code.php");
require_once($_SESSION['app.root']."/app/Libraries/book.php");
require_once($_SESSION['app.root2']."/vwmldbm/dbcon.php");

ob_start(); 
ob_end_clean();

$inst=$_SESSION['lib_inst'];
$rid=$_REQUEST['rid'];
$rfname=$_REQUEST['rf'];

$book=new book\Book(null,$rid); 

//// User Permission Control from here TBD
$perm['R']!='Y';
/// End of User Permission Control

$fname=$book->get_file_name($rfname);
$fdir=$_SESSION['app.root']."/storage/app/ebook/{$_SESSION['lib_inst']}/$rid";

$rfilepath="$fdir/$rfname"; 

output_file($rfilepath,$fname);

//https://gist.github.com/drewwalton19216801/5997118
function output_file($file, $name, $mime_type=''){
    if(!is_readable($file)) die('File not found or inaccessible!');
    $size = filesize($file);
    $name = rawurldecode($name);
    $known_mime_types=array(
        "htm" => "text/html",
		"txt" => "text/plain",
        "html"=> "text/html",
        "exe" => "application/octet-stream",
        "zip" => "application/zip",
        "doc" => "application/msword",
        "jpg" => "image/jpg",
		"jpeg"=> "image/jpg",
        "php" => "text/plain",
        "xls" => "application/vnd.ms-excel",
        "ppt" => "application/vnd.ms-powerpoint",
        "gif" => "image/gif",
        "pdf" => "application/pdf",
        "png" => "image/png"
    );

    if($mime_type==''){
        $file_extension = strtolower(mb_substr(strrchr($file,"."),1));
		if($file_extension=='php')die; // by Sam
        if(array_key_exists($file_extension, $known_mime_types)){
            $mime_type=$known_mime_types[$file_extension];
        } else {
            $mime_type="application/force-download";
        };
    };
    @ob_end_clean();
    if(ini_get('zlib.output_compression'))
    ini_set('zlib.output_compression', 'Off');
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: attachment; filename="'.$name.'"');
    header("Content-Transfer-Encoding: binary");
    header('Accept-Ranges: bytes');

    if(isset($_SERVER['HTTP_RANGE']))
    {
        list($a, $range) = explode("=",$_SERVER['HTTP_RANGE'],2);
        list($range) = explode(",",$range,2);
        list($range, $range_end) = explode("-", $range);
        $range=intval($range);
        if(!$range_end) {
            $range_end=$size-1;
        } else {
            $range_end=intval($range_end);
        }

        $new_length = $range_end-$range+1;
        header("HTTP/1.1 206 Partial Content");
        header("Content-Length: $new_length");
        header("Content-Range: bytes $range-$range_end/$size");
    } else {
        $new_length=$size;
        header("Content-Length: ".$size);
    }

    $chunksize = 1*(1024*1024);
    $bytes_send = 0;
    if ($file = fopen($file, 'r'))
    {
        if(isset($_SERVER['HTTP_RANGE']))
        fseek($file, $range);

        while(!feof($file) &&
            (!connection_aborted()) &&
            ($bytes_send<$new_length)
        )
        {
            $buffer = fread($file, $chunksize);
            echo($buffer);
            flush();
            $bytes_send += strlen($buffer);
        }
        fclose($file);
    } else
        die('Error - can not open file.');
    die();
}
?>
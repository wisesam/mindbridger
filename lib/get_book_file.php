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
require_once($_SESSION['app.root2']."/vwmldbm/dbcon.php");
require_once($_SESSION['app.root']."/app/Libraries/book.php");
require_once($_SESSION['app.root']."/vendor/autoload.php"); // <- FPDI autoload

use setasign\Fpdi\Fpdi;

ob_start();
ob_end_clean();

$inst   = $_SESSION['lib_inst'];
$rid    = $_REQUEST['rid']  ?? null;   // book rid
$rfname = $_REQUEST['rf']   ?? null;   // rfile
$page   = $_REQUEST['page'] ?? null;   // legacy single page (optional)

// New: start/end, falling back to page
$start  = isset($_REQUEST['start']) ? (int)$_REQUEST['start'] : (int)($page ?? 0);
$end    = isset($_REQUEST['end'])   ? (int)$_REQUEST['end']   : $start;

// Resolve file path
$book   = new book\Book(null, $rid);
$fname  = $book->get_file_name($rfname);
$fdir   = $_SESSION['app.root']."/storage/app/ebook/{$_SESSION['lib_inst']}/$rid";
$rfilepath = "$fdir/$rfname";

// ---- Permission checks TBD (your existing logic) ----
// $perm['R'] != 'Y'; // etc.

// If no range explicitly requested (e.g., malformed input), fall back to original behavior
if (!is_readable($rfilepath)) {
    http_response_code(404);
    die('File not found or inaccessible!');
}

// If client didn’t ask a valid range, just send original file (keeps backward compat)
if ($start <= 0 || $end <= 0) {
    output_file($rfilepath, $fname);
    exit;
}

try {
    // Build subset PDF with FPDI
    $pdf = new FPDI();

    // Get total pages to clamp the range
    $pageCount = $pdf->setSourceFile($rfilepath);
    if ($start > $end) {
        // swap if reversed
        $tmp = $start; $start = $end; $end = $tmp;
    }
    if ($start < 1) $start = 1;
    if ($end > $pageCount) $end = $pageCount;

    // If range covers entire doc, serve original (saves CPU/mem)
    if ($start === 1 && $end === $pageCount) {
        output_file($rfilepath, $fname);
        exit;
    }

    // Re-init after setSourceFile used for counting
    $pdf = new FPDI();
    $pdf->setSourceFile($rfilepath);

    for ($i = $start; $i <= $end; $i++) {
        $tplId = $pdf->importPage($i);
        $size  = $pdf->getTemplateSize($tplId);

        // Keep original page size & orientation
        $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
        $pdf->AddPage($orientation, [$size['width'], $size['height']]);
        $pdf->useTemplate($tplId);
    }

    // Output inline so PDF.js displays it
    $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '_', pathinfo($fname, PATHINFO_FILENAME));
    $subsetName = $safeName . "_p{$start}-p{$end}.pdf";

    // Clear any buffered output before sending PDF
    while (ob_get_level()) { ob_end_clean(); }

    header('Content-Type: application/pdf');
    // inline (not attachment) so it opens in the viewer
    header('Content-Disposition: inline; filename="'.$subsetName.'"');
    // No range headers when generating a fresh file
    // Let FPDF send the bytes directly
    $pdf->Output('I', $subsetName);
    exit;

} catch (\Throwable $e) {
    // Fallback to original file on FPDI errors (e.g., encrypted PDFs without addon)
    // You can log the error for debugging
    // error_log('FPDI error: ' . $e->getMessage());
    output_file($rfilepath, $fname);
    exit;
}


// ---------- your original function (unchanged) ----------
function output_file($file, $name, $mime_type='') {
    if(!is_readable($file)) die('File not found or inaccessible!');
    $size = filesize($file);
    $name = rawurldecode($name);
    $known_mime_types=array(
        "htm"=>"text/html","txt"=>"text/plain","html"=>"text/html","exe"=>"application/octet-stream",
        "zip"=>"application/zip","doc"=>"application/msword","jpg"=>"image/jpg","jpeg"=>"image/jpg",
        "php"=>"text/plain","xls"=>"application/vnd.ms-excel","ppt"=>"application/vnd.ms-powerpoint",
        "gif"=>"image/gif","pdf"=>"application/pdf","png"=>"image/png"
    );
    if($mime_type==''){
        $file_extension = strtolower(mb_substr(strrchr($file,"."),1));
        if($file_extension=='php') die;
        $mime_type = $known_mime_types[$file_extension] ?? "application/force-download";
    }
    @ob_end_clean();
    if(ini_get('zlib.output_compression')) ini_set('zlib.output_compression', 'Off');
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: inline; filename="'.$name.'"'); // inline is nicer for PDF.js
    header("Content-Transfer-Encoding: binary");
    header('Accept-Ranges: bytes');

    // Byte-range support for static files
    if(isset($_SERVER['HTTP_RANGE'])) {
        list($a, $range) = explode("=", $_SERVER['HTTP_RANGE'], 2);
        list($range) = explode(",", $range, 2);
        list($range, $range_end) = explode("-", $range);
        $range = intval($range);
        $range_end = $range_end ? intval($range_end) : ($size-1);
        $new_length = $range_end - $range + 1;
        header("HTTP/1.1 206 Partial Content");
        header("Content-Length: $new_length");
        header("Content-Range: bytes $range-$range_end/$size");
    } else {
        header("Content-Length: ".$size);
        $range = 0; $new_length = $size;
    }

    $chunksize = 1*(1024*1024);
    $bytes_send = 0;
    if ($fh = fopen($file, 'r')) {
        if(isset($_SERVER['HTTP_RANGE'])) fseek($fh, $range);
        while(!feof($fh) && !connection_aborted() && ($bytes_send<$new_length)) {
            $buffer = fread($fh, $chunksize);
            echo $buffer;
            flush();
            $bytes_send += strlen($buffer);
        }
        fclose($fh);
    } else {
        die('Error - can not open file.');
    }
    die();
}

?>
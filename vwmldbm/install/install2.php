<?PHP
/*VWMLDBM DISCLAIMER*==================================================
 Copyright (c) 2017 Sang Jin Han and other contributors, 
	http://wise4edu.com
 Released under the MIT license
 =================================================*VWMLDBM DISCLAIMER*/
namespace vwmldbm;
require_once("../lib/install.php");
$cur_step=2;
if($_POST['from_step']==($cur_step-1) || $_POST['from_step']==($cur_step+1) ) ; // pass
else if($_POST['from_step']==$cur_step || $_POST['operation']=='update_config'); // pass
else die; // illegal access

$mode = $mode ?? null;
$data_path = $data_path ?? null;
$go_button_opt = $go_button_opt ?? null;

install\install_html_header($mode);
js();

echo "<input type='hidden' name='operation'>";
echo "<input type='hidden' name='from_step'>";
echo "<input type='hidden' name='mode' value='$mode'>";
echo "<input type='hidden' name='data_path' value='$data_path'>";
echo "<input type='hidden' name='DB' value='".$_POST['DB']."'>";
echo "<input type='hidden' name='DB_user' value='".$_POST['DB_user']."'>";
echo "<input type='hidden' name='dbpasswd' value='".$_POST['dbpasswd']."'>";
echo "<input type='hidden' name='TB_prefix' value='".$_POST['TB_prefix']."'>";

if($_POST['operation']=='update_config'){
	$update_ok=update_config_install(); /// Update config.php and generate configx.php 
	if($update_ok) {
		install\display_goto_next_tag($cur_step+1,$go_button_opt);
	}
}
else {
	install\display_goto_next_tag($cur_step+1,$go_button_opt);
}

echo "</form>";

install\install_html_footer();
?>

<?
function js(){
	global $cur_step;
	$next_step=$cur_step+1;
	echo "<script>";
	echo "
		function checkForm(){
			var obj=document.form1;
			obj.from_step.value='$cur_step';
			obj.action='install$next_step.php';
			obj.submit();
		}
	";	
	echo"
		function check_update_form(obj){
			if(obj.value=='' || !validateEmail(obj.value)){
				alert('Enter a valid email address!');
				obj.focus();
			}
			else {
				document.form1.operation.value='update_config';
				document.form1.submit();			
			}
		}
	";
	echo "</script>";
}

function update_config_install(){
    $update_ok = true;

    // 상위 디렉토리 dbcon.php 경로 (macOS Apache 환경에서 DocumentRoot 밖 접근 가능해야 함)
    $config_name = realpath(__DIR__ . '/../dbcon.php');
    if (!$config_name || !is_writable($config_name)) {
        echo "<font color=red>Cannot access or write to file ($config_name). Check Apache permissions.</font><br>";
        return false;
    }

    // dbcon.php 내용 읽기
    $config_contents = file_get_contents($config_name);
    if ($config_contents === false) {
        echo "<font color=red>Cannot read file ($config_name)</font><br>";
        return false;
    }

    $cnt = 0; 
    $tot_cnt = 0; 
    $total_config_var_to_update = 5; // 추가된 VWMLDBM_RT 포함

    // 변수 치환
    $config_contents = str_replace("_INSTALL_DB_name", $_POST['DB'], $config_contents, $cnt); $tot_cnt += $cnt;
    $config_contents = str_replace("_INSTALL_DB_user", $_POST['DB_user'], $config_contents, $cnt); $tot_cnt += $cnt;
    $config_contents = str_replace("_INSTALL_TB_prefix", $_POST['TB_prefix'], $config_contents, $cnt); $tot_cnt += $cnt;
    $config_contents = str_replace("_INSTALL_DB_pwd", $_POST['dbpasswd'], $config_contents, $cnt); $tot_cnt += $cnt;
    $config_contents = str_replace("_INSTALL_VWMLDBM_RT", addslashes(dirname(__DIR__)), $config_contents, $cnt); $tot_cnt += $cnt;

    // WWW root 경로
    $wwwRootDir = explode('/', trim($_SERVER['REQUEST_URI'], '/'))[0];
    $config_contents = str_replace("_INSTALL_VWMLDBM_WWW_RT", "/" . $wwwRootDir . "/vwmldbm", $config_contents, $cnt); 
    $tot_cnt += $cnt;

    // 업데이트 확인
    if ($tot_cnt < $total_config_var_to_update) {
        install\display_warn("Something wrong! " . ($total_config_var_to_update - $tot_cnt) . " config variables were not updated (or same as before).");
    }

    // 파일 쓰기
    if ($tot_cnt > 0) {
        if (!$handle = fopen($config_name, 'w')) {
            echo "<font color=red>Cannot open file ($config_name) for writing</font><br>";
            $update_ok = false;
        } elseif (fwrite($handle, $config_contents) === false) {
            echo "<font color=red>Cannot write to file ($config_name)</font><br>";
            $update_ok = false;
        } else {
            install\display_ok_msg("$config_name was updated.");
        }
        fclose($handle);
    }

    return $update_ok;
}


?>
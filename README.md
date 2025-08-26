# MindBridger 오픈 소스 AI 도서관 시스템에 오신 것을 환영합니다
# Welcome to MindBridger Open Source AI Library System (See below for English Version)

## 환경
  - Apache 웹 서버 2.4.* +
  - MariaDB(MySQL) 10.4.* +
  - PHP 8.0.2 +

## php.ini 설정
  - short_open_tag=On  
  - upload_max_filesize=200M (원하는 만큼 조정 가능)  
  - error_reporting=E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE & ~E_WARNING  

  다음 확장(extension)의 주석을 제거하세요:
  - extension=gd  
  - extension=zip  

## 가장 먼저 해야 할 일은 VWMLDBM 설치입니다:
### VWMLDBM 설치 방법
  1. public 디렉토리 아래에 클론 (예: "/htdocs/mindbridger/")  
  2. "/htdocs/mindbridger/vwmldbm/dbcon(default).php" 파일명을 "dbcon.php" 로 변경  
  3. "/htdocs/mindbridger/vwmldbm/config(default).php" 파일명을 "config.php" 로 변경  
  4. 설치 프로그램 실행: "http://localhost/mindbridger/vwmldbm/"  
  5. 코드 설정 완료  

### VWMLDBM 재설치 방법
  1. "/htdocs/mindbridger/vwmldbm/dbcon.php" 파일 삭제  
  2. "/htdocs/mindbridger/vwmldbm/dbcon(default).php" 파일명을 "dbcon.php" 로 변경  
  3. 설치 프로그램 실행: "http://localhost/mindbridger/vwmldbm/"  
     
### RESTful API 엔드포인트 설정
   - RESTful 라우팅이 작동하지 않을 경우 (예: http://localhost/mindbridger/login => Not Found)  
     - VirtualHost 설정 확인/추가 (예: /etc/httpd/httpd.conf)  
     - .htaccess 확인 (mindbridger/ 및 mindbridger-main/public 둘 다 확인 필요)  

## 다국어 사용 방법  
 A. 다국어 변경 리스트 박스를 사용하려면:
  1. 호스트 스크립트에서 VWMLDBM "config.php"를 include  
     예: 호스트 스크립트가 "/htdocs/mindbridger/customer/index.php" 이고  
     VWMLDBM 경로가 "/htdocs/mindbridger/vwmldbm/"일 경우,  
     호스트 스크립트에 `require_once("../vwmldbm/config.php");` 추가  

  2. `\vwmldbm\code\mlang_change_list();` 호출  
     예: `<?\vwmldbm\code\mlang_change_list();?>`  

B. 다국어 필드 이름을 사용하려면:
  1. "RMD"를 사용하여 필드 이름 입력  
  2. 호스트 스크립트에서 VWMLDBM "config.php"를 include  
     예: 호스트 스크립트가 "/htdocs/mindbridger/customer/index.php" 이고  
     VWMLDBM 경로가 "/htdocs/mindbridger/vwmldbm/"일 경우,  
     호스트 스크립트에 `require_once("../vwmldbm/config.php");` 추가  

  3. `\vwmldbm\code\get_field_name("table_name_without_prefix","field_name")` 호출  
     예: `<?PHP \vwmldbm\code\get_field_name("customer","first_name");?>`  

C. 다국어 텍스트(필드명이 아님)를 사용하려면:
  1. JSON 파일 수정 (예: 한국어는 "/htdocs/mindbridger/vwmldbm/mlang/30.json")  
  2. 호스트 스크립트에서 VWMLDBM "config.php"를 include  
     예: 호스트 스크립트가 "/htdocs/mindbridger/customer/index.php" 이고  
     VWMLDBM 경로가 "/htdocs/mindbridger/vwmldbm/"일 경우,  
     호스트 스크립트에 `require_once("../vwmldbm/config.php");` 추가  

  3. 코드 삽입: `$wmlang[menu][customer_list]`  
     예: `<?=$wmlang[menu][customer_list]?>`


# Welcome to MindBridger Open Source AI Library System
## Environment
  - Apache Web Server 2.4.* +
  - MariaDB(MySQL) 10.4.* +
  - PHP 8.0.2 +

## php.ini Setting
  - short_open_tag=On
  - upload_max_filesize=200M (As much as you want)
  - error_reporting=E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE & ~E_WARNING

  remove the comment on following extentions:
  - extension=gd 
  - extension=zip  

##  First thing to do is install VWMLDBM:
### To install VWMLDBM,
  1. Clone it under the public: eg, "/htdocs/mindbridger/"
  2. Rename "/htdocs/mindbridger/vwmldbm/dbcon(default).php" as "dbcon.php"
  3. Rename "/htdocs/mindbridger/vwmldbm/config(default).php" as "config.php"
  4. Run the installer: "http://localhost/mindbridger/vwmldbm/"
  5. Finish Code Settings

### To Reinstall VWMLDBM,
  1. delete "/htdocs/mindbridger/vwmldbm/dbcon.php"
  2. rename "/htdocs/mindbridger/vwmldbm/dbcon(default).php" as "dbcon.php"
  3. Run the installer: "http://localhost/mindbridger/vwmldbm/"
     
### RESTful API endpoints Setup
   - if the RESTful routing is not working (eg, http://localhost/mindbridger/login => Not Found ),
     - check(add) VirtualHost setting (eg, /etc/httpd/httpd.conf)
     - check .htaccess (in both mindbridger/ and mindbridger-main/public)  
  
## How to use Multi-lang  
 A. To use multi-lang change list box,
  1. include VWMLDBM "config.php" from the host script. 
	eg, suppose the host script is "/htdocs/mindbridger/customer/index.php"
		and VWMLDBM path is "/htdocs/mindbridger/vwmldbm/".	
		From the host script, " require_once("../vwmldbm/config.php"); "
  
  2. call "\vwmldbm\code\mlang_change_list();"
	eg, <?\vwmldbm\code\mlang_change_list();?>
	
	
B. To use multi-lang field names,
  1. Enter field names using "RMD"
  
  2. include VWMLDBM "config.php" from the host script. 
	eg, suppose the host script is "/htdocs/mindbridger/customer/index.php"
		and VWMLDBM path is "/htdocs/mindbridger/vwmldbm/".	
		From the host script, " require_once("../vwmldbm/config.php"); "
  
  3. call "\vwmldbm\code\get_field_name("table_name_without_prefix","field_name")"
		eg, <?PHP \vwmldbm\code\get_field_name("customer","first_name");?>
	
	
C. To use multi-lang Texts (not field names),
  1. Modify JSON files: eg, "/htdocs/mindbridger/vwmldbm/mlang/30.json" for Korean:
  2. include VWMLDBM "config.php" from the host script. 
	eg, suppose the host script is "/htdocs/mindbridger/customer/index.php"
		and VWMLDBM path is "/htdocs/mindbridger/vwmldbm/".	
		From the host script, " require_once("../vwmldbm/config.php"); "
  
  3. insert code: "$wmlang[menu][customer_list]"
		eg, <?=$wmlang[menu][customer_list]?>

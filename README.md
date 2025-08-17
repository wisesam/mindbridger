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

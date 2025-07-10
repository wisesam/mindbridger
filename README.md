Thank you for using Visual Web Multi-Lang DB Manager (VWMLDBM)
Environment
  - Apache Web Server (2.4.*+)
  - MariaDB(MySQL) 10.4.* +
  - PHP 8.0+

php.ini Setting
  - short_open_tag=On
  - upload_max_filesize=200M (As much as you want)
  - error_reporting=E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE & ~E_WARNING

  remove the comment on following extentions:
  - extension=gd 
  - extension=zip  

First thing to do is install VWMLDBM:
To install VWMLDBM,
  1. Check php.ini to see if the "short tag" is "on"
  2. Copy "vwmldbm" inside your host program: eg, "/htdocs/host_program/vwmldbm/"
  3. Run the installer: "/htdocs/host_program/vwmldbm/"

To Reinstall VWMLDBM,
  1. delete "/htdocs/host_program/vwmldbm/dbcon.php"
  2. rename "/htdocs/host_program/vwmldbm/dbcon(default).php" as "dbcon.php"
  3. Run the installer: "/htdocs/host_program/vwmldbm/"
  
  
How to use Multi-lang  
 A. To use multi-lang change list box,
  1. include VWMLDBM "config.php" from the host script. 
	eg, suppose the host script is "/htdocs/host_program/customer/index.php"
		and VWMLDBM path is "/htdocs/host_program/vwmldbm/".	
		From the host script, " require_once("../vwmldbm/config.php"); "
  
  2. call "\vwmldbm\code\mlang_change_list();"
	eg, <?\vwmldbm\code\mlang_change_list();?>
	
	
B. To use multi-lang field names,
  1. Enter field names using "RMD"
  
  2. include VWMLDBM "config.php" from the host script. 
	eg, suppose the host script is "/htdocs/host_program/customer/index.php"
		and VWMLDBM path is "/htdocs/host_program/vwmldbm/".	
		From the host script, " require_once("../vwmldbm/config.php"); "
  
  3. call "\vwmldbm\code\get_field_name("table_name_without_prefix","field_name")"
		eg, <?PHP \vwmldbm\code\get_field_name("customer","first_name");?>
	
	
C. To use multi-lang Texts (not field names),
  1. Modify JSON files: eg, "/htdocs/host_program/vwmldbm/mlang/30.json" for Korean:
  2. include VWMLDBM "config.php" from the host script. 
	eg, suppose the host script is "/htdocs/host_program/customer/index.php"
		and VWMLDBM path is "/htdocs/host_program/vwmldbm/".	
		From the host script, " require_once("../vwmldbm/config.php"); "
  
  3. insert code: "$wmlang[menu][customer_list]"
		eg, <?=$wmlang[menu][customer_list]?>
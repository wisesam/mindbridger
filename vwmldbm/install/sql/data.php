<?PHP
/*VWMLDBM DISCLAIMER*==================================================
 Copyright (c) 2017 Sang Jin Han and other contributors, 
	http://wise4edu.com
 Released under the MIT license
 =================================================*VWMLDBM DISCLAIMER*/
$data_sql['vwmldbm_inst']="
	INSERT INTO $DTB_PRE"."_vwmldbm_inst(no,inst_uname) 
		VALUES (1,'mindbridger');
";

$data_sql['vwmldbm_c_lang']="
	INSERT INTO $DTB_PRE"."_vwmldbm_c_lang(inst,code,name,n_name,use_yn,ccode) 
		VALUES 
	(1,10,'English','English','Y','EN'),
	(1,20,'Mongolian','Монгол хэл','N','MN'),
	(1,30,'Korean','한국어','Y','KR'),
	(1,40,'Russian','русский','N','RU'),
	(1,50,'Chinese','中文（普通话）','Y','CN');
";
?>
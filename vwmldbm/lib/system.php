<?php
namespace vwmldbm\system;

function isAuth() {
    if(empty($_SESSION['lib_inst']) || empty($_SESSION['uid'])) return false;
	return true;
}

function isAdmin() { // check if the user is admin
	if(isAuth() && isset($_SESSION['wlibrary_admin']) && ($_SESSION['wlibrary_admin']!='A' || $_SESSION['wlibrary_admin']!='SA')) return true;
	return false;
}

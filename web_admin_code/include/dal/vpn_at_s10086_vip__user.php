<?php
$dalTableuser = array();
$dalTableuser["id"] = array("type"=>3,"varname"=>"id");
$dalTableuser["user"] = array("type"=>200,"varname"=>"user");
$dalTableuser["server"] = array("type"=>200,"varname"=>"server");
$dalTableuser["passwd"] = array("type"=>200,"varname"=>"passwd");
$dalTableuser["text"] = array("type"=>200,"varname"=>"text");
	$dalTableuser["id"]["key"]=true;
	$dalTableuser["user"]["key"]=true;

$dal_info["vpn_at_s10086_vip__user"] = &$dalTableuser;
?>
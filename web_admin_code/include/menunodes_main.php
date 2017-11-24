<?php

function getMenuNodes_main($menuNodesObject)
{
	// create menu nodes arr
	$menuNodesObject->menuNodes["main"] = array();
		
	$menuNode = array();
	$menuNode["id"] = "1";
	$menuNode["name"] = "VPN账号管理";
	$menuNode["href"] = "mypage.htm";
	$menuNode["type"] = "Leaf";
	$menuNode["table"] = "user";
	$menuNode["style"] = "";
	$menuNode["params"] = "";
	$menuNode["parent"] = "0";
	$menuNode["nameType"] = "Text";
	$menuNode["linkType"] = "Internal";
	$menuNode["pageType"] = "List";//
	$menuNode["openType"] = "None";
	$menuNode["title"] = "VPN账号管理";
	$menuNodesObject->menuNodes["main"][] = $menuNode;
}
?>

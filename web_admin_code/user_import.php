<?php
@ini_set("display_errors","1");
@ini_set("display_startup_errors","1");
require_once("include/dbcommon.php");
header("Expires: Thu, 01 Jan 1970 00:00:01 GMT"); 

set_time_limit(600);

require_once("include/user_variables.php");
require_once("include/import_functions.php");
require_once('classes/importpage.php');

if( !Security::processPageSecurity( $strtablename, 'I' ) )
	return;




$layout = new TLayout("import2", "OfficeOffice", "MobileOffice");
$layout->version = 2;
$layout->blocks["top"] = array();
$layout->containers["import"] = array();
$layout->container_properties["import"] = array(  );
$layout->containers["import"][] = array("name"=>"importheader", 
	"block"=>"", "substyle"=>2  );

$layout->containers["import"][] = array("name"=>"errormessage", 
	"block"=>"", "substyle"=>1  );

$layout->containers["import"][] = array("name"=>"wrapper", 
	"block"=>"", "substyle"=>1 , "container"=>"importfields" );
$layout->containers["importfields"] = array();
$layout->container_properties["importfields"] = array(  );
$layout->containers["importfields"][] = array("name"=>"importheader_text", 
	"block"=>"", "substyle"=>1  );

$layout->containers["importfields"][] = array("name"=>"importfields", 
	"block"=>"", "substyle"=>1  );

$layout->containers["importfields"][] = array("name"=>"import_rawtext_control", 
	"block"=>"", "substyle"=>1  );

$layout->containers["importfields"][] = array("name"=>"import_preview", 
	"block"=>"", "substyle"=>1  );

$layout->containers["importfields"][] = array("name"=>"import_process", 
	"block"=>"", "substyle"=>1  );

$layout->containers["importfields"][] = array("name"=>"import_results", 
	"block"=>"", "substyle"=>1  );

$layout->containers["importfields"][] = array("name"=>"importbuttons", 
	"block"=>"", "substyle"=>2  );

$layout->skins["importfields"] = "fields";


$layout->skins["import"] = "1";

$layout->blocks["top"][] = "import";
$page_layouts["user_import"] = $layout;

$layout->skinsparams = array();
$layout->skinsparams["empty"] = array("button"=>"button2");
$layout->skinsparams["menu"] = array("button"=>"button1");
$layout->skinsparams["hmenu"] = array("button"=>"button1");
$layout->skinsparams["undermenu"] = array("button"=>"button1");
$layout->skinsparams["fields"] = array("button"=>"button1");
$layout->skinsparams["form"] = array("button"=>"button1");
$layout->skinsparams["1"] = array("button"=>"button1");
$layout->skinsparams["2"] = array("button"=>"button1");
$layout->skinsparams["3"] = array("button"=>"button1");



require_once('include/xtempl.php');
$xt = new Xtempl();

$id = postvalue("id");
$id = $id != "" ? $id : 1;

//an array of params for ImportPage constructor
$params = array();
$params["id"] = $id;
$params["xt"] = &$xt;
$params["tName"] = $strTableName;
$params["action"] = postvalue("a");
$params["pageType"] = PAGE_IMPORT;
$params["needSearchClauseObj"] = false;
$params["strOriginalTableName"] = $strOriginalTableName;

if( $params["action"] == "importPreview" )
{
	$params["importType"] = postvalue("importType");
	$params["importText"] = postvalue("importText");
	$params["useXHR"] = postvalue("useXHR");
} 
elseif( $params["action"] == "importData" )
{
	$params["importData"] = my_json_decode( postvalue("importData") );
}

$pageObject = new ImportPage($params);
$pageObject->init();

$pageObject->process();	

?>
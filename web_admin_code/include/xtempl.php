<?php
// menuItem class
include_once(getabspath("include/menuitem.php"));
include(getabspath("include/testing.php"));

$menuNodesIndex=0;
/**
  * Xlinesoft Template Engine
  */
class XTempl
{
	var $xt_vars=array();
	var $xt_stack;
	var $xt_events=array();
	var $template;
	var $template_file;
	var $charsets=array();
	var $testingFlag=false;
	var $eventsObject;
	var $hiddenBricks = array();
	var $preparedContainers = array();
	var $layout;
	
	/**
	 * $cssFiles
	 * Array of css files for page styles and layouts
	 * @var {array}
	 * @intellisense
	 */
	var $cssFiles = array();

	/**
	  * Returns variable by name.
      * @intellisense
      */
	function getVar($name)
	{
		return xt_getvar($this,$name);
	}

	function recTesting(&$arr)
	{
		global $testingLinks;
		foreach($arr as $k=>$v)
			if(is_array($v))
				$this->recTesting($arr[$k]);
			else
				if(isset($testingLinks[$k]))
					$arr[$k].=" func=\"".$testingLinks[$k]."\"";
	}
	
	function Testing()
	{
		if(!$this->testingFlag)
			return;
		$this->recTesting($this->xt_vars);
	}
	
	function report_error($message)
	{
		echo $message;
		exit();
	}
	
	/**
	 * @param Boolean hideAddedCharts (optional) #9607 1.
	 */
	function XTempl( $hideAddedCharts = false )
	{
		global $mlang_charsets;
		
		$this->xt_vars=array();
		$this->xt_stack=array();
		$this->xt_stack[]=&$this->xt_vars;
		if (!isMobile())
		{
			xtempl_include_header($this,"header","include/header.php");
			xtempl_include_header($this,"footer","include/footer.php");
		}
		else
		{
			xtempl_include_header($this,"header","include/mheader.php");
			xtempl_include_header($this,"footer","include/mfooter.php");
		}
		$this->assign_method("event",$this, "xt_doevent",array());
		$this->assign_function("label","xt_label",array());
		$this->assign_function("custom","xt_custom",array());
		$this->assign_function("caption","xt_caption",array());
		$this->assign_function("pagetitlelabel", "xt_pagetitlelabel", array());
		$this->assign_method("mainmenu",$this,"xt_displaymainmenu",array());
		$this->assign_method("menu",$this,"xt_displaymenu",array());
		$this->assign_function("TabGroup","xt_displaytabs",array());
		$this->assign_function("Section","xt_displaytabs",array());
		$this->assign_function("Step","xt_displaytabs",array());
		
		if( !$hideAddedCharts ) //#9607 1. Temporary fix
		{
		}
		
		$mlang_charsets=array();
		
$mlang_charsets["Chinese"]="GB18030";;
		$this->charsets = &$mlang_charsets;
		
		$html_attrs = '';
		if(isRTL())
		{
			$this->assign("RTL_block",true);
			$this->assign("rtlCSS",true);
			$html_attrs .= 'dir="RTL" ';
		}
		else
			$this->assign("LTR_block",true);
		if(mlang_getcurrentlang() == 'English')
				$html_attrs .= 'lang="en"';
		$this->assign("html_attrs",$html_attrs);	
		$this->assign("menu_block",true);	
	}
	

	/**
	  * Assign value to name.
	  * @intellisense
	  */
	function assign($name,$val)
	{
		$this->xt_vars[$name]=$val;
	}

	/**
	  * Assign value to name by reference.
	  * @intellisense
	  */
	function assignbyref($name,&$var)
	{
		$this->xt_vars[$name]=&$var;
	}

	function bulk_assign( $arr )
	{
		foreach($arr as $key => $value)
		{
			$this->xt_vars[$key] = $value;
		}
	}
	
	
	function enable_section($name)
	{
		if(!isset($this->xt_vars[$name]))
		{
			$this->xt_vars[$name] = true;
		}
		elseif($this->xt_vars[$name] == false)
		{
			$this->xt_vars[$name] = true;
		}
	}

	function assign_section($name,$begin,$end)
	{
		$arr = array();
		$arr["begin"]=$begin;
		$arr["end"]=$end;
		$this->xt_vars[$name]=&$arr;
	}

	function assign_loopsection($name,&$data)
	{
		$arr = array();
		$arr["data"]=&$data;
		$this->xt_vars[$name]=&$arr;
	}

	function assign_array($name,$innername,$_arr)
	{
		$arr = array();
		foreach($_arr as $a)
			$arr[] = array($innername => $a);
		$this->xt_vars[$name]=array("data" => $arr);
	}
	
	
	function assign_loopsection_byValue($name, $data)
	{
		$arr = array();
		$arr["data"] = $data;
		$this->xt_vars[$name] = &$arr;
	}

	function assign_function( $name, $func, $params )
	{
		$this->xt_vars[$name] = $this->create_function_assignment( $func,$params );
	}

	static function create_function_assignment($func,$params)
	{
		return array( "func" => $func, "params" => $params );
	}

	function assign_method($name,&$object,$method,$params)
	{
		$this->xt_vars[$name] = $this->create_method_assignment( $method, $object, $params );
	}

	static function create_method_assignment( $method, &$object, $params = null )
	{
		return array( "method"=>$method,
			"params"=>$params, 
			"object" => $object
		);
	}

	/**
	 * Remove assigned element
	 * @param string - name of assigned element
	 * @intellisense
	 */
	function unassign($name){
		unset($this->xt_vars[$name]);
	}

	function assign_event($name,&$object,$method,$params)
	{
		 $this->xt_events[$name]=array("method"=>$method,"params"=>$params);
		 $this->xt_events[$name]["object"]=&$object;
	}

	function xt_doevent($params)
	{
		if (isset($this->xt_events[@$params["custom1"]]))
		{
			$eventArr = $this->xt_events[@$params["custom1"]];
			
			if(isset($eventArr["method"]))
			{
				$params=array();
				if(isset($eventArr["params"]))
					$params=$eventArr["params"];
				$method=$eventArr["method"];
				$eventArr["object"]->$method($params);
				return;
			}
		}
		global $strTableName, $globalEvents;
		if($this->eventsObject)
			$eventObj = &$this->eventsObject;
		elseif(strlen($strTableName))
			$eventObj = getEventObject($strTableName);
		else
			$eventObj = &$globalEvents;
		if(!$eventObj)
			return;
		$eventName = $params["custom1"];
		if(!$eventObj->exists($eventName))
			return;
		$eventObj->$eventName($params);
	}
	
	function fetchVar($varName)
	{
		ob_start();
		$varParams = array();
		$this->processVar($this->getVar($varName), $varParams);	
		$out=ob_get_contents();
		ob_end_clean();
		return $out;
		
	}

	function fetch_loaded($filtertag="")
	{
		ob_start();
		$this->display_loaded($filtertag);
		$out=ob_get_contents();
		ob_end_clean();
		return $out;
	}

	function fetch_loaded_before($filtertag)
	{
		$pos1=strpos($this->template,"{BEGIN ".$filtertag."}");
		if($pos1===false)
			return;
		$str=substr($this->template,0,$pos1);
		ob_start();
		$this->Testing();
		xt_process_template($this,$str);
		$out=ob_get_contents();
		ob_end_clean();
		return $out;
	}

	function fetch_loaded_after($filtertag)
	{
		$pos2=strpos($this->template,"{END ".$filtertag."}");
		if($pos2===false)
			return;
		$str=substr($this->template,$pos2+strlen("{END ".$filtertag."}"));
		ob_start();
		$this->Testing();
		xt_process_template($this,$str);
		$out=ob_get_contents();
		ob_end_clean();
		return $out;
	}
	
	function call_func($var)
	{
		return xtempl_get_func_output($var);
	}

	function set_template($template)
	{
		global $page_layouts;
		//	read template file
		$templatesPath = "templates/";
		if (isMobile())
			$templatesPath = "mobile/";
		if(file_exists(getabspath($templatesPath.$template)))
			$this->template = myfile_get_contents(getabspath($templatesPath.$template));
		
		if (isMobile() && $this->template==''){
			$templatesPath = "templates/";
			$this->template = myfile_get_contents(getabspath($templatesPath.$template));
		}
		$this->template_file = basename($template,".htm");
		$this->layout =&$page_layouts[$this->template_file];
	}

	function prepare_template($template)
	{
		$this->prepareContainers();
	}

	function load_template($template)
	{
		$this->set_template($template);
		$this->prepareContainers();
	}

	function display_loaded($filtertag = "")
	{
		$str = $this->template;
		if($filtertag)
		{
			if( !isset($this->xt_vars[ $filtertag ]) || $this->xt_vars[ $filtertag ] === false )
				return;
				
			$pos1 = strpos($this->template, "{BEGIN ".$filtertag."}");
			$pos2 = strpos($this->template, "{END ".$filtertag."}");
			if($pos1 === false || $pos2 === false)
				return;
			
			$pos2 += strlen("{END ".$filtertag."}");
			$str = substr($this->template,$pos1,$pos2-$pos1);
		}
		$this->Testing();
		xt_process_template($this,$str);
	}
	
	function display($template)
	{
		$this->load_template($template);
		$this->Testing();
		xt_process_template($this,$this->template);
	}
	
	function displayPartial($template)
	{
		$savedTemplate = $this->template;
		$this->display( $template );
		$this->template = $savedTemplate;
	}
	
	function processVar(&$var, &$varparams)
	{
		if(!is_array($var))
		{
		//	just display a value
			echo $var;
		}
		elseif(isset($var["func"]))
		{
		//	call a function
			$params = array();
			if(isset($var["params"]))
				$params = $var["params"];
			$key=1;
			foreach($varparams as $val)
			{
				if( strlen($val) )
					$params["custom".($key++)] = $val;
			}
			$func = $var["func"];
			xtempl_call_func($func,$params);
		}
		elseif(isset($var["method"]))
		{
			$params = array();
			if(isset($var["params"]))
				$params = $var["params"];
			$key=1;
			foreach($varparams as $val)
			{
				if( strlen($val) )
					$params["custom".($key++)] = $val;
			}
			$method = $var["method"];
//			if(method_exists($var["object"],$method))
				$var["object"]->$method($params);
		}
		else
		{
			$this->report_error("Incorrect variable value");
			return;
		}
	}
	
	/**
	 * Display bricks with names listed in the arra passed as hidden
	 * @param Array bricks
	 */
	function displayBricksHidden($bricks)
	{
		foreach($bricks as $name)
		{
			$this->hiddenBricks[$name] = true;		
		}	
	}
	
	/**
	 * Display brick hidden
	 * @param {string} brick name
	 * @intellisense
	 */
	function displayBrickHidden($name)
	{
		$this->hiddenBricks[$name] = true;
	}

	/** 
	 * Hide All bricks on the page
	 * @param {array} of excepted bricks
	 * @intellisense
	 */
	function hideAllBricksExcept($arrExceptBricks){
		foreach($this->layout->containers as $cname=>$container)
		{
			foreach($container as $brick)
			{
				if (!in_array($brick["name"],$arrExceptBricks)){
					$this->assign($brick["block"],false);
				}	
			}
		}
	}
	
	/** 
	 * Show brick on the page
	 * @param {string} name of brick
	 * @intellisense
	 */
	function showBrick($name)
	{
		foreach($this->layout->containers as $cname=>$container)
		{
			foreach($container as $brick)
			{
				if ($brick["name"]==$name){
					$this->assign($brick["block"],true);
				}
			}
		}
	}
	
	/** 
	 * Check are bricks exist on page
	 * If not pass param "all" then check if any brick from array is exist
	 * @param {array} names of bricks
	 * @param {boolean} check all bricks on exist or not
	 * @return {boolean}
	 * @intellisense
	 */
	function isExistBricks($names, $all = false)
	{
		$exist = false;
		foreach($names as $name)
		{
			if($this->isExistBrick($name))
			{
				if(!$all)
				{
					return true;
				}	
				$exist = true;	
			}
			elseif($all)
			{
				$exist = false;
			}	
		}	
		return $exist;
	}
	
	/** 
	 * Check is brick exist on page
	 * @param {string} name of brick
	 * @return {boolean}
	 * @intellisense
	 */
	function isExistBrick($name)
	{
		foreach($this->layout->containers as $cname=>$container)
		{
			foreach($container as $brick)
			{
				if ($brick["name"] == $name)
				{
					return true;
				}
			}
		}
		return false;
	}
	private function setContainerDisplayed($cname, $show, $firstContainerSubstyle, $lastContainerSubstyle)
	{
		$this->prepareContainerAttrs( $cname );
		if( $show )
		{
			$styleString = $this->preparedContainers[ $cname ]["showString"];
			$this->unassign("wrapperclass_".$cname);
		}
		else
		{
			$styleString = $this->preparedContainers[ $cname ]["hideString"];
			$this->assign("wrapperclass_".$cname,"rnr-hiddencontainer");
		}
		$this->assign_section("container_".$cname,"<div ".$styleString.">","</div>");
		$this->assign("cheaderclass_".$cname,$firstContainerSubstyle);
		$this->assign("cfooterclass_".$cname,$lastContainerSubstyle);
	}

	private function getPageStyle()
	{
		global $bUseMobileStyleOnly;
		if(isMobile() && $bUseMobileStyleOnly)
		{
			return $this->layout->styleMobile;
		}
		else if(postvalue("pdf"))
		{
			return  $this->layout->pdfStyle();
		}
		return $this->layout->style;
	}

	private function prepareContainerAttrs( $cname )
	{
		$pageStyle = $this->getPageStyle();
		if( isset($this->preparedContainers[ $cname ]) )
			return;
		$this->preparedContainers[ $cname ] = array();
		$hiddenStyleString = "";
		$styleString = "";
		if(isset($this->layout->skins[$cname]))
		{
			$skin = @$this->layout->skins[$cname];
			
			$buttonsType = $this->layout->skinsparams[$skin]["button"];
			$buttonsClass = $buttonsType == "button2" ? " aslinks" : " asbuttons";
		
		// printing properties
			$printMode = $this->layout->container_properties[$cname]["print"];
			$printClass = "";
			
			if($printMode == "repeat")
				$printClass = " rp-repeat";
			else if($printMode == "none")
				$printClass = " rp-noprint";
			
			if($this->layout->version == 1) {
				$styleString = " class=\"rnr-cw-".$cname." runner-s-".$skin." ".$pageStyle;
			} else {
				$styleString = " class=\"rnr-cw-".$cname." rnr-s-".$skin.$buttonsClass." ".$pageStyle.$printClass;
			}
			$hiddenStyleString = $styleString . " rnr-hiddencontainer";
			$styleString .= "\"";
			$hiddenStyleString .= "\"";
			$this->preparedContainers[ $cname ] = array("showString" => $styleString, "hideString" => $hiddenStyleString );
		}
	}
	/** 
	 * Prepare containers for show on page
	 * @intellisense
	 */
	function prepareContainers()
	{
		if(!$this->layout)
			return;

		$pageStyle = $this->getPageStyle();
		
		$classPrefix = "rnr-";
		if($this->layout->version == 1)
		{
			$classPrefix = "runner-";
		}
		$this->assign("stylename",$pageStyle." page-".$this->layout->name);
		$this->assign("pageStyleName",$pageStyle);
		$displayed_containers = array();
		$hidden_containers = array();
		
		// run reverse loop for proper processing of nested containers  
		$containersNames = array_keys($this->layout->containers);
		$containersNames = array_reverse($containersNames);
		foreach($containersNames as $cname)
		{
			$container = $this->layout->containers[$cname];
			if(isset($this->xt_vars["container_".$cname]) && $this->xt_vars["container_".$cname] === false)
				continue;
			$firstContainerSubstyle = "";
			$lastContainerSubstyle = "";
			$show = false;
			$hide = true;
			foreach($container as $brick)
			{
				if(!strlen($brick["block"]))
				{
					$show = true;
				}
				elseif(!isset($this->xt_vars[$brick["block"]]))
				{
					continue;
				}
				elseif(!$this->xt_vars[$brick["block"]])
				{
					continue;
				}
				if(!$firstContainerSubstyle)
				{
					$firstContainerSubstyle = "runner-toprow style".$brick["substyle"];
					if($brick["name"] == "vmenu")
						$firstContainerSubstyle = "runner-toprow runner-vmenu";
				}
				$lastContainerSubstyle = "runner-bottomrow style".$brick["substyle"];
				if($brick["name"] == "vmenu")
					$lastContainerSubstyle = "runner-bottomrow runner-vmenu";
				$show = true;
				if($this->hiddenBricks[$brick["name"]] 
					|| $brick["name"] == "wrapper" 
						&& (isset($hidden_containers[$brick["container"]]) || !isset($displayed_containers[$brick["container"]]))){
					$this->assign("brickclass_".$brick["name"], $classPrefix."hiddenbrick");
				}else{
					$this->unassign("brickclass_".$brick["name"] );
					$hide = false;
				}
			}
			if($show)
			{
				if($hide)
				{
					$hidden_containers[$cname] = true;
				}
				$this->setContainerDisplayed( $cname, !$hide, $firstContainerSubstyle, $lastContainerSubstyle );
				$displayed_containers[$cname] = true;
				$this->unassign("wrapperclass_".$cname);
			}
			else 
			{
				$this->unassign("container_".$cname);
				$this->assign("wrapperclass_".$cname,$classPrefix."hiddencontainer");
			}			
		}
		//	display blocks
		foreach($this->layout->blocks as $bname=>$block)
		{
			$show = false;
			$hide = true;
			foreach($block as $cname)
			{
				if($displayed_containers[$cname])
				{
					$show = true;
					if(!$hidden_containers[$cname])
					{
						$hide = false;
						break;
					}
				}
			}
			if(!$show || $hide)
			{
				$this->assign("blockclass_".$bname,$classPrefix."hiddenblock");
			}
			else
			{
				$this->unassign( "blockclass_".$bname );
			}
		}
	}
	
	function hideField($fieldName)
	{
		if($this->layout->version == 1)
			$this->assign("fielddispclass_".GoodFieldName($fieldName), "runner-hiddenfield");
		else
			$this->assign("fielddispclass_".GoodFieldName($fieldName), "rnr-hiddenfield");
	}
	
	function showField($fieldName)
	{
		$this->assign("fielddispclass_".GoodFieldName($fieldName), "");
	}

	
	function xt_displaymenu($params)
	{
		global $strTableName, $pageName;	
		global $menuNodesIndex;
		$menuNodesIndex=0;
		$menuparams = array();
		foreach($params as $p)
		{
			$menuparams[] = $p;
		}
		$menuId = $menuparams[0];
		$ProjectSettings = new ProjectSettings();
		$menuName = $ProjectSettings->getMenuName($this->template_file, $menuId, $menuparams[1]);
		$menuStyle = $ProjectSettings->getMenuStyle($this->template_file, $menuId, $menuparams[1]);
		$isMobyleLayout = isMobile();
		array_shift($menuparams);
		global $pageObject;
		$pageType = "";
		$pageMode = 0;
		$menuNodes = array();
		$isAdminTable = false;
		
		if(isset($pageObject))
		{
			$pageObject->getMenuNodes();
			$pageType = $pageObject->pageType;
			$pageMode = $pageObject->mode;
			$isAdminTable = $pageObject->isAdminTable();
			if($isAdminTable)
				$menuName = "adminarea";
			$menuNodes = $pageObject->getMenuNodes($menuName);
		}	
			
		$xt = new Xtempl();
		$xt->assign("menuName", $menuName); 
		$xt->assign("menustyle", $menuStyle ? "second" : "main" );
		$quickjump = false;
		$horizontal = false;
		
		if(count($menuparams))
		{
			if($menuparams[0]=="horizontal")
				$horizontal = true;
			elseif($menuparams[0]=="quickjump")	
				$quickjump = true;
		}	
			
		if(!$isAdminTable){
			if(!$quickjump){
							if(!$isMobyleLayout)
					$xt->assign("simpleTypeMenu",true);
				else
					$xt->assign("treeLikeTypeMenu",true);
			}
			if($pageType == PAGE_MENU && IsAdmin() && !$isMobyleLayout)
					$xt->assign("adminarea_link",true);
		}else{
			//Admin Area menu items
			$xt->assign("adminAreaTypeMenu",true);
		}	
		
		// need to predefine vars
		$nullParent = NULL;
		$rootInfoArr = array("id"=>0, "href"=>"");
		// create treeMenu instance
		$menuNodesIndex = 0;
		$menuMap = array();
		$menuRoot = new MenuItem($rootInfoArr, $menuNodes, $nullParent, $menuMap);
		// call xtempl assign, set session params
		$menuRoot->setMenuSession();
		$menuRoot->assignMenuAttrsToTempl($xt);
		$menuRoot->setCurrMenuElem($xt);
		
		$xt->assign("mainmenu_block",true);
		
		$mainmenu = array();
		if(isEnableSection508()) 
			$mainmenu["begin"]="<a name=\"skipmenu\"></a>";
		$mainmenu["end"] = '';
		
		$countLinks = 0;
		$countGroups = 0;
		$showMenuCollapseExpandAll = false;
		foreach($menuRoot->children as $ind=>$val)
		{
			if($val->showAsLink)
				$countLinks++;
			if ($val->showAsGroup)
			{
				if (count($val->children))
					$showMenuCollapseExpandAll = true;
				$countGroups++;
			}
		}
		$xt->assign("menu_collapse_expand_all", $showMenuCollapseExpandAll);
		if(true || ($pageType == PAGE_MENU) || $countLinks>1 || $countGroups>0 || $menuName != "main")
		{
			$xt->assignbyref("mainmenu_block",$mainmenu);
				
			if($this->layout->version == 1)
			{
				$menuName = "old".$menuName;
			}
			if($quickjump)
				$xt->display($menuName."_"."mainmenu_quickjump.htm");
			else if($horizontal)
				$xt->display($menuName."_"."mainmenu_horiz.htm");
			else if($isMobyleLayout && $this->layout->version != 1)
				$xt->display($menuName."_"."mainmenu_m.htm");
			else
				$xt->display($menuName."_"."mainmenu.htm");
		}
	}

	function xt_displaymainmenu($params)
	{
		array_unshift($params, "main");
		return $this->xt_displaymenu($params);
	}
	
}



$tabparams = array();
// display tabs in group or simple section
function xt_displaytabs( $tabparams )
{
	global $pageObject;
	if(!isset( $pageObject ) || !isset( $tabparams[ "custom1" ] ) )
		return;
	
	$pageObject->displayTabsSections( $tabparams[ "custom1" ] );
}

//	BuildEditControl wrapper
function xt_buildeditcontrol(&$params)
{
	$pageObj = $params["pageObj"];
	$data = $pageObj->getFieldControlsData();
	
	$field = $params["field"];
	
	if($params["mode"] == "edit")
		$mode = MODE_EDIT;
	else if($params["mode"] == "add")
		$mode = MODE_ADD;
	else if($params["mode"]=="inline_edit")
		$mode = MODE_INLINE_EDIT;
	else if($params["mode"]=="inline_add")
		$mode = MODE_INLINE_ADD;
	else
		$mode = MODE_SEARCH;
	
	$fieldNum = 0;
	if(@$params["fieldNum"])
		$fieldNum = $params["fieldNum"];
	
	$id = "";
	if(@$params["id"] !== "")
		$id = $params["id"];
	
	$validate = array();
	if(count(@$params["validate"]))
		$validate = @$params["validate"];
	
	$additionalCtrlParams = array();
	if(count(@$params["additionalCtrlParams"]))
		$additionalCtrlParams = @$params["additionalCtrlParams"];
	
	$extraParams = array();
	if( count(@$params["extraParams"]) )
		$extraParams = @$params["extraParams"];	
	
	$pageObj->getControl($field, $id, $extraParams)->buildControl(@$params["value"], $mode, $fieldNum, $validate, $additionalCtrlParams, $data);
}

function xt_include($params)
{
	if(file_exists(getabspath($params["file"])))
		include(getabspath($params["file"]));
}



function xt_label($params)
{
	echo GetFieldLabel($params["custom1"],$params["custom2"]);
}

function xt_custom($params)
{
	echo GetCustomLabel($params["custom1"]);
}

function xt_caption($params)
{
	echo GetTableCaption($params["custom1"]);
}

function xt_pagetitlelabel($params)
{
	global $pageObject;
	
	$record = isset($params["record"]) ? $params["record"] : null;
	$settings = isset($params["settings"]) ? $params["settings"] : null;
	
	if( isset($params["custom2"]) )
		echo $pageObject->getPageTitle( $params["custom2"], $params["custom1"] , $record, $settings );
	else
		echo $pageObject->getPageTitle( $params["custom1"], "", $record, $settings );
}

function xtempl_get_func_output(&$var)
{
	if(!strlen(@$var["func"]))
		return "";
	ob_start();	
	$params=$var["params"];
	$func=$var["func"];
	xtempl_call_func($func,$params);
	$out=ob_get_contents();
	ob_end_clean();
	return $out;
}		
?>
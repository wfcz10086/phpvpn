<?php

class TLayout 
{
	var $containers = array();
	var $container_properties = array();
	var $blocks = array();
	var $name = ""; 
	var $version; 
	var $style = "";
	var $styleMobile = "";
	var $skins = array();
	var $skinsparams = array();
	
	function TLayout($name, $style, $styleMobile)
	{
		$this->name = $name;
		$this->style = $style;
		$this->styleMobile = $styleMobile;
	}
	
	function pdfStyle()
	{
		return "Pdf".substr($this->styleMobile,6);
	}
	
	function isBrickSet($brickName)
	{
		foreach($this->containers as $container)
		{
			foreach($container as $brick)
			{
				if($brick["name"] == $brickName)
				{
					return true;
				}
			}
		}
		return false;
	}
	
	function getBrickTableName($brickName)
	{
		foreach($this->containers as $container)
		{
			foreach($container as $brick)
			{
				if($brick["name"] == $brickName)
				{
					return $brick["table"];
				}
			}
		}
		return "";
	}
	/**
	*  Returns list of CSS files required for displaying the layout
	*
	*/
	public function getCSSFiles($rtl = false, $mobile = false, $pdf = false)
	{
		global $bUseMobileStyleOnly;
		$files = array();
		if($this->version == 1)
			$files[] = "styles/defaultOld.css";
		$pageStyle = $this->style;
		if($mobile && $bUseMobileStyleOnly)
			$pageStyle = $this->styleMobile;
		else if($pdf)
			$pageStyle = $this->pdfStyle();
		$files[] = "styles/".$pageStyle."/style".($rtl ? 'RTL' : '').".css";
		if($this->version == 1)
			$files[] = "styles/".$pageStyle."/style".($rtl ? 'RTL' : '')."Old.css";
		if($mobile)
			$files[] = "pagestyles/mobile/".$this->name.($rtl ? 'RTL' : '').".css";
		else
			$files[] = "pagestyles/".$this->name.($rtl ? 'RTL' : '').".css";
		
		return $files;
	}
};


?>
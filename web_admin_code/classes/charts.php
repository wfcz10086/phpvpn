<?php
class Chart
{
	var $strSQL;
	var $label2;
    var $numRecordsToShow;
	var $totalRecords;
    var $header;
    var $footer;
	var $y_axis_label;
    var $strLabel;

	var $arrDataLabels = array();
	var $arrDataSeries = array();
	var $arrDataColor = array();
	var $arrFormatCurrency = array();
	var $arrFormatDecimal = array();
	var $arrFormatCustomer = array();
	var $arrFormatCustomerStr = array();
	var $arrDataSize = array();
	var $arrAxesColor = array();
	var $arrGaugeColor = array();
	
	var $arrOHLC_high = array();
	var $arrOHLC_low = array();
	var $arrOHLC_open = array();
	var $arrOHLC_close = array();
	var $arrOHLC_candle = array();
	var $arrOHLC_color = array();
	var $arrOHLC_color_up = array();
	var $arrOHLC_color_down = array();
	
	var $sleg;
	var $scol;
	var $chrt_array = array();
	var $webchart;
	var $cname;
	var $gstrOrderBy;
	
	var $table_type;
	
	var $cipherer = null;
	
	var $pSet = null;
	
	var $hasFlash = true;
	
	var $sessionPrefix = "";
    
	var $detailTablesData = array();
	
	var $pageId;
	
	/**
	 * A flag helping to detect if to apply
	 * 'details' functionality to the chart
	 */
	var $showDetails = true;
	
	var $chartPreview = false;
	
	var $setFirstPoint = true;
	/**
	 * It indicates if chart is shown on a dashboard
	 */
	protected $dashChart = false;
	
	/**
	 * It indicates if first point selected
	 */
	protected $dashChartFirstPointSelected = false;
	
	protected $detailMasterKeys = "";	
	
	/**
	 * Dashboard table name
	 * It's set up if chart is shown on a dashboard only
	 */
	protected $dashTName = "";
	
	/**
	 * Dashboard element name
	 * It's set up if chart is shown on a dashboard only
	 */
	protected $dashElementName = "";	
	
	/**
	 * @type Connection
	 */
	protected $connection;
	
	
	function Chart(&$ch_array, $param)
	{
		global $field_labels;
		
		if($this->webchart)
			$this->chrt_array=Convert_Old_Chart($ch_array);
		else
			$this->chrt_array=$ch_array;
		

		$this->setConnection();
		
		$this->pSet = new ProjectSettings($this->chrt_array['tables'][0]);
		$this->showDetails = $param['showDetails'];
		if( $this->showDetails )
			$this->detailTablesData = $this->pSet->getDetailTablesArr();
		
		$this->hasFlash = $param["hasFlash"];
		$this->pageId = $param["pageId"];
		$this->chrt_array["appearance"]["autoupdate"] = false;
		
		$this->numRecordsToShow=$this->chrt_array['appearance']['maxbarscroll'];
		if($this->numRecordsToShow<1)
			$this->numRecordsToShow=1;
		$this->table_type=$this->chrt_array["table_type"];
		if(!$this->table_type)
			$this->table_type="project";
		$this->webchart=$param["webchart"];
		$this->cname=$param["cname"];
		
		$this->chartPreview = $param["chartPreview"];
		$this->dashChart = $param["dashChart"];
		
		$this->sessionPrefix = $this->chrt_array['tables'][0];
		if( $this->dashChart )
		{
			$this->dashTName = $param["dashTName"];
			$this->dashElementName = $param["dashElementName"];
			$this->sessionPrefix = $this->dashTName."_".$this->sessionPrefix;
		}
		
		$this->gstrOrderBy=$param["gstrOrderBy"];
		$TableName=GoodFieldName($this->chrt_array['tables'][0]);

		// #10461, $this->setConnection(); needs to be called after value is assigned to $this->webchart
		$this->setConnection();  
		
		if( $this->isProjectDB() )
			$this->cipherer = new RunnerCipherer($this->chrt_array['tables'][0]);

		$this->header = $this->chrt_array['appearance']['head'];
		$this->footer = $this->chrt_array['appearance']['foot'];    
		$this->y_axis_label = $this->chrt_array['appearance']['y_axis_label'];    

		for ( $i=0; $i<count($this->chrt_array['parameters'])-1; $i++) 
		{
			if(isset($this->chrt_array['parameters'][$i]['currencyFormat']))
				$this->arrFormatCurrency[]=$this->chrt_array['parameters'][$i]['currencyFormat'];
			else
			{
				if($this->chrt_array['appearance']['scur']=="false")
					$this->arrFormatCurrency[]="";
				else
					$this->arrFormatCurrency[]=$this->chrt_array['appearance']['scur'];
			}
			
			if(isset($this->chrt_array['parameters'][$i]['decimalFormat']))
				$this->arrFormatDecimal[]=$this->chrt_array['parameters'][$i]['decimalFormat'];
			else
				$this->arrFormatDecimal[]=$this->chrt_array['appearance']['dec'];
				
			$this->arrFormatCustomer[]=$this->chrt_array['parameters'][$i]['customFormat'];
			$this->arrFormatCustomerStr[]=$this->chrt_array['parameters'][$i]['customFormatStr'];
			
			if( $this->chrt_array["chart_type"]["type"] == "ohlc" || $this->chrt_array["chart_type"]["type"] == "candlestick")
			{
				$this->setOHLCParams( $this->chrt_array['parameters'][$i] );	
			}
			elseif ( $this->chrt_array['parameters'][$i]['name'] != "" ) 
			{
				if($this->table_type!="db")
				{
					$this->arrDataSeries[] = ($this->chrt_array['parameters'][$i]['agr_func']) ?
					$this->chrt_array['parameters'][$i]['label'] :
					$this->chrt_array['parameters'][$i]['name'];
					if($this->chrt_array["chart_type"]["type"]=="bubble")
						$this->arrDataSize[] = $this->chrt_array['parameters'][$i]['size'];
				}
				else
				{
					$this->arrDataSeries[] = ($this->chrt_array['parameters'][$i]['agr_func']) ?
					$this->chrt_array['parameters'][$i]['agr_func']."_".$this->chrt_array['parameters'][$i]['table']."_".$this->chrt_array['parameters'][$i]['name'] :
					$this->chrt_array['parameters'][$i]['table']."_".$this->chrt_array['parameters'][$i]['name'] ;
					if($this->chrt_array["chart_type"]["type"]=="bubble")
						$this->arrDataSize[] = $this->chrt_array['parameters'][$i]['table']."_".$this->chrt_array['parameters'][$i]['size'];
				}
				
				if(isset($this->chrt_array['appearance']['scolor'.($i+1).'1']))
					$this->arrDataColor[]=$this->chrt_array['appearance']['scolor'.($i+1).'1'];
				else
					$this->arrDataColor[] = $this->chrt_array['parameters'][$i]['series_color'];
									
				if($this->chrt_array["chart_type"]["type"]=="gauge")
				{
					for ($k=0;is_array($this->chrt_array["parameters"][$i]["gaugeColorZone"]) && $k<count($this->chrt_array["parameters"][$i]["gaugeColorZone"]);$k++) 
					{
						$beginColor=(float)@$this->chrt_array["parameters"][$i]["gaugeColorZone"][$k]["gaugeBeginColor"];
						$endColor=(float)@$this->chrt_array["parameters"][$i]["gaugeColorZone"][$k]["gaugeEndColor"];
						$gColor="#".@$this->chrt_array["parameters"][$i]["gaugeColorZone"][$k]["gaugeColor"];
						$this->arrGaugeColor[count($this->arrDataSeries)-1][]=array($beginColor,$endColor,$gColor);
					}
				}
			}
			
			$this->setDataLabels(  $this->chrt_array['parameters'][$i] );
		}
		
	
	    if($this->chrt_array["chart_type"]["type"]!="gauge")
	    {
			if($this->table_type!="db")
				$this->strLabel = $this->chrt_array['parameters'][count($this->chrt_array['parameters'])-1]['name'];
			else
			{
				$this->strLabel = ($this->chrt_array['parameters'][count($this->chrt_array['parameters'])-1]['agr_func']) ?
				$this->chrt_array['parameters'][count($this->chrt_array['parameters'])-1]['agr_func']."_".$this->chrt_array['parameters'][count($this->chrt_array['parameters'])-1]['table']."_".$this->chrt_array['parameters'][count($this->chrt_array['parameters'])-1]['name']:
				$this->chrt_array['parameters'][count($this->chrt_array['parameters'])-1]['table']."_".$this->chrt_array['parameters'][count($this->chrt_array['parameters'])-1]['name'];
			}
			for($j = 0; $j<count($this->chrt_array['fields']); $j++)
			{
				if($this->chrt_array['parameters'][count($this->chrt_array['parameters'])-1]['name']==$this->chrt_array['fields'][$j]['name'])
				{
					if($this->table_type=="project")
						$this->label2=$this->chart_xmlencode(GetFieldLabel($TableName,GoodFieldName($this->chrt_array['parameters'][count($this->chrt_array['parameters'])-1]['name'])));
					else
						$this->label2=$this->chart_xmlencode($this->chrt_array['parameters'][count($this->chrt_array['parameters'])-1]['name']);
				}
			}
		}
	    
		if($this->chrt_array["chart_type"]["type"]!="ohlc" && $this->chrt_array["chart_type"]["type"]!="candlestick")
		{
			foreach($this->arrDataColor as $ind=>$val)
			{
				if($ind==0)
					$this->arrAxesColor = "#000000";
				else
					$this->arrAxesColor = "#".$this->arrDataColor[$ind];
			}
		}
		else
		{
			foreach($this->arrOHLC_color as $ind=>$val)
			{
				if($ind==0)
					$this->arrAxesColor = "#000000";
				else
					$this->arrAxesColor = "#".$this->arrOHLC_color[$ind];
			}
		}		
		
		// prepare search params
		$gQuery = $this->pSet->getSQLQuery();

		$masterWhere = "";
		if( !$this->dashChart ) 
		{
			$masterTable = $_SESSION[$this->sessionPrefix."_mastertable"];
			$detailKeysByM = $this->pSet->getDetailKeysByMasterTable($masterTable);
			if(count($detailKeysByM)) 
			{
				for($i=0;$i<count($detailKeysByM);$i++) 
				{
					if($i != 0) 
						$masterWhere.= " and ";
						
					if($this->cipherer && isEncryptionByPHPEnabled())
						$mValue = $this->cipherer->MakeDBValue($detailKeysByM[$i], $_SESSION[$this->sessionPrefix."_masterkey".($i + 1)]);
					else 
						$mValue = make_db_value($detailKeysByM[$i], $_SESSION[$this->sessionPrefix."_masterkey".($i + 1)]);
					if(strlen($mValue) != 0)
					{
						$masterWhere.= RunnerPage::_getFieldSQLDecrypt( $detailKeysByM[$i], $this->connection, $this->pSet, $this->cipherer ) 
							."=".$mValue;
					}
					else 
						$masterWhere.= "1=0";
				}
			}
		}
		
		$strWhereClause = "";
		$searchHavingClause = "";
		$strSearchCriteria = "and";
		global $strTableName;
		// search where for basic charts
		if(!$this->webchart)
		{	
			if( !$this->chartPreview && isset($_SESSION[$this->sessionPrefix.'_advsearch']) )
			{
				$searchClauseObj = SearchClause::UnserializeObject($_SESSION[$this->sessionPrefix.'_advsearch']);
				include_once getabspath('classes/controls/EditControlsContainer.php');
				$editControls = new EditControlsContainer(null, $this->pSet, PAGE_SEARCH, $this->cipherer);
				
				$whereComponents = RunnerPage::sGetWhereComponents( $gQuery, $this->pSet, $searchClauseObj, $editControls, $this->connection);
				$strWhereClause = $whereComponents["searchWhere"];
				foreach($whereComponents["filterWhere"] as $fWhere)
				{
					$strWhereClause = whereAdd($strWhereClause, $fWhere);
				}
		
				$searchHavingClause = $whereComponents["searchHaving"];
				foreach($whereComponents["filterHaving"] as $fHaving)
				{
					$searchHavingClause = whereAdd($searchHavingClause, $fHaving);
				}
				
				$strSearchCriteria = $whereComponents["searchUnionRequired"] ? "or" : "and";
			}
		}
		else 
		{
			if($this->table_type!="project")
				$strTableName="webchart".$this->cname;
			$strWhereClause = CalcSearchParam($this->table_type!="project");
		}
		if ($strWhereClause) 
		{
			$this->chrt_array['where'] .= ($this->chrt_array['where']) ?
				" AND (" . $strWhereClause . ")" :
				" WHERE (" . $strWhereClause . ")";
		}	
		if($this->table_type=="project")
		{
			if(SecuritySQL("Search", $this->chrt_array['tables'][0]))
			{
				$strWhereClause = whereAdd($strWhereClause, SecuritySQL("Search", $strTableName ));
			}
			$this->strSQL = $gQuery->gSQLWhere($strWhereClause,$searchHavingClause, $strSearchCriteria);
		
			$strOrderBy = $this->gstrOrderBy;
			$this->strSQL.= " ".$strOrderBy;

			if( $masterWhere )
				$strWhereClause = whereAdd($strWhereClause, $masterWhere);
			
			$strSQLbak=$this->strSQL;
			if(tableEventExists("BeforeQueryChart",$strTableName)) 
			{
				$tstrSQL = $this->strSQL;
				$eventObj = getEventObject($strTableName);
				$eventObj->BeforeQueryChart($tstrSQL,$strWhereClause,$strOrderBy);
				$this->strSQL = $tstrSQL;
			}
			if($strSQLbak == $this->strSQL)
			{								
				$this->strSQL = $gQuery->gSQLWhere($strWhereClause, $searchHavingClause, $strSearchCriteria);
				$this->strSQL.= " ".$strOrderBy;
			}
		}
		

		if ($this->cname && $this->table_type=="db") 
			$this->strSQL = $this->chrt_array['sql'] . $this->chrt_array['where'] . $this->chrt_array['group_by'] . $this->chrt_array['order_by'];
		elseif ($this->cname && $this->table_type=="custom") 
		{
			if(!IsStoredProcedure($this->chrt_array['sql']))
			{
				$sql_query=$this->chrt_array['sql'];
				
				if( $this->connection->dbType == nDATABASE_MSSQLServer )
				{
					$pos=strrpos(strtoupper($sql_query),"ORDER BY");
					if($pos)
						$sql_query=substr($sql_query,0,$pos);
				}
				
				if( $this->connection->dbType != nDATABASE_Oracle )
					$this->strSQL = "select * from (".$sql_query.") as ".$this->connection->addFieldWrappers("custom_query").$this->chrt_array['where'];
				else
					$this->strSQL = "select * from (".$sql_query.")". $this->chrt_array['where'];
			}
			else
			{
				$this->strSQL = $this->chrt_array['sql'];
			}
		}
			
		if(tableEventExists("UpdateChartSettings",$strTableName))
		{
			$eventObj = getEventObject($strTableName);
			$eventObj->UpdateChartSettings($this);
		}
	}
	
	/**
	 * @param Array params
	 */
	protected function setOHLCParams( $params )
	{
		$this->arrOHLC_color[] = "#".$params['ohlcColor'];
		
		if( $this->chrt_array["chart_type"]["type"] == "candlestick" )
			$this->arrOHLC_candle[] = "#".$params['ohlcCandleColor'];	
			
		if($this->table_type != "db")
		{
			$this->arrOHLC_open[] = $params['ohlcOpen'];
			$this->arrOHLC_high[] = $params['ohlcHigh'];
			$this->arrOHLC_low[] = $params['ohlcLow'];
			$this->arrOHLC_close[] = $params['ohlcClose'];
			return;
		}

		if( $this->chrt_array['parameters'][$i]['agr_func'] )
		{
			$this->arrOHLC_open[] = $params['agr_func']."_".$params['table']."_".$params['ohlcOpen'];
			$this->arrOHLC_high[] = $params['agr_func']."_".$params['table']."_".$params['ohlcHigh'];
			$this->arrOHLC_low[] = $params['agr_func']."_".$params['table']."_".$params['ohlcLow'];
			$this->arrOHLC_close[] = $params['agr_func']."_".$params['table']."_".$params['ohlcClose'];
		}
		else
		{
			$this->arrOHLC_open[] = $params['table']."_".$params['ohlcOpen'];
			$this->arrOHLC_high[] = $params['table']."_".$params['ohlcHigh'];
			$this->arrOHLC_low[] = $params['table']."_".$params['ohlcLow'];
			$this->arrOHLC_close[] = $params['table']."_".$params['ohlcClose'];
		}
	}
	
	/**
	 * @param Array params
	 */
	protected function setDataLabels( $params )
	{
		$chartType = $this->chrt_array["chart_type"]["type"];
		
		if( $this->table_type == "project" && !$this->webchart )
		{
			if( $chartType != "candlestick" && $chartType != "ohlc" )
				$this->arrDataLabels[]=$this->chart_xmlencode( GetFieldLabel(GoodFieldName($TableName), GoodFieldName($params['name'])) );
			else
				$this->arrDataLabels[]=$this->chart_xmlencode( GetFieldLabel(GoodFieldName($TableName), GoodFieldName($params['ohlcOpen'])) );
		}
		else
		{
			if( !$this->chart_xmlencode($params['label']) )
			{
				if( $chartType != "candlestick" && $chartType != "ohlc" )
					$this->arrDataLabels[]=$this->chart_xmlencode( $params['name'] );
				else
					$this->arrDataLabels[]=$this->chart_xmlencode( $params['ohlcOpen'] );
			}
			else
				$this->arrDataLabels[]=$this->chart_xmlencode( $params['label'] );
		}	
	}
	
	/**
	 * Check for a web chart if it's based on the project table
	 */
	protected function isProjectDB()
	{
		if( !$this->webchart )
			return true;
			
		if("user" == $this->chrt_array['tables'][0])
			return true;
		return false;
	}
	
	/**
	 * Set the 'connection' property #9875
	 */
	protected function setConnection()
	{
		global $cman;
		
		if($this->isProjectDB())
			$this->connection = $cman->byTable( $this->chrt_array['tables'][0] );
		else
			$this->connection = $cman->getDefault();
	}		
	
	//-------------appearance change methods, usefull in UpdateChartSettings event----------
	function setFooter($name) 
	{
		$this->footer = $name;
	}
	
	function getFooter() 
	{
		return $this->footer;
	}
	
	
	
	function setHeader($name) 
	{
		$this->header = $name;
	}
	
	function getHeader() 
	{
		return $this->header;
	}
	
	function setLabelField($name) 
	{
		$this->strLabel = $name;
	}
	
	function getLabelField() 
	{
		return $this->strLabel;
	}
	
	
	function setSeriaColor($color, $index) 
	{
		$this->arrDataColor[$index] = $color;
	}
	
	function getSeriaColor($index) 
	{
		return $this->arrDataColor[$index];
	}
		
	
	function setScrollingState($scroll) 
	{
		$this->chrt_array["appearance"]["cscroll"] = $scroll;
	}
	
	function getScrollingState() 
	{
		return ($this->chrt_array["appearance"]["cscroll"] == "true");
	}
	
	function setMaxBarScroll($num) 
	{
		$this->numRecordsToShow = $num;
	}
	
	function getMaxBarScroll() 
	{
		return $this->numRecordsToShow;
	}
	
	//------------------------------------------
	
	function writeDetailsData()
	{
		if( !$this->dashChart )
			return;
		
		echo "<detail_page_id>" .$this->pageId. "</detail_page_id>"."\n";
		echo "<detail_master_keys>" .$this->detailMasterKeys. "</detail_master_keys>"."\n";
	}
	
	function write()
	{
		echo "<?xml version=\"1.0\" standalone=\"yes\"?>"."\n";
		echo "<anychart>"."\n";
		echo "<settings>"."\n";
		echo "<pdf_export use_title_as_file_name=\"true\" image_type=\"jpg\" />"."\n";
		echo "<image_export use_title_as_file_name=\"true\" />"."\n";
		if($this->chrt_array["appearance"]["sanim"] == "true" && $this->chrt_array["appearance"]["autoupdate"]!="true") 
		{
            echo "<animation enabled=\"True\" />"."\n";
        }
        else
		{
            echo "<animation enabled=\"False\" />"."\n";
        }
		echo "</settings>"."\n";
		echo "<charts>"."\n";
		
		$this->write_data();
		$this->write_dps();
		$this->write_chart_settings();
		
		echo "</chart>"."\n";
		echo "</charts>"."\n";
		$this->writeDetailsData();
		echo "</anychart>"."\n";
	}
	function write_legend()
	{
		if ( $this->chrt_array['appearance']['slegend'] == "true" && !$this->chartPreview) 
		{
			$this->write_legend_tag();
			$this->write_format();
			echo "<template></template>"."\n";
	         
			echo "<title enabled=\"true\">"."\n";
			echo "<text>".$this->footer."</text>"."\n";
			echo "<font color=\"#".$this->chrt_array["appearance"]["color111"]."\"/>"."\n";
			echo "</title>"."\n";
			echo "<columns_separator enabled=\"false\"/>"."\n";
			echo "<background>"."\n";
			echo "<inside_margin left=\"10\" right=\"10\"/>"."\n";
			echo "</background>"."\n";
			echo "<items>"."\n";
			echo "<item source=\"".$this->sleg."\"/>"."\n";
			echo "</items>"."\n";
			echo "</legend>"."\n";
		}
	}
	function write_format()
	{
		if($this->sleg=="Points")
		{
			echo "<format>{%Icon} {%Name} (".$this->valueFormat(0).")</format>"."\n";
		}
	}

	function write_data()
	{
	}
	function write_dps()
	{
	}
	function write_chart_settings()
	{
		echo "<chart_settings>"."\n";
		echo "<title enabled=\"true\" padding=\"15\">"."\n";
		echo "<text>".$this->header."</text>"."\n";
		echo "<font color=\"#".$this->chrt_array["appearance"]["color101"]."\"/>"."\n";
		echo "</title>"."\n";
		$this->write_legend();
		$this->write_axes();
		echo "<chart_background>"."\n";
		$this->write_chart_background();
		echo "</chart_background>"."\n";
		$this->write_plot_background();
		echo "</chart_settings>"."\n";
	}
	function formatCurrency($val,$series)
	{
		global $locale_info;
		if($this->arrFormatCurrency[$series])
		{
			switch($locale_info["LOCALE_ICURRENCY"])
			{
			case 0:
				return $locale_info["LOCALE_SCURRENCY"].$val;
			case 1:
				return $val.$locale_info["LOCALE_SCURRENCY"];
			case 2:
				return $locale_info["LOCALE_SCURRENCY"]." ".$val;
			case 3:
				return $val." ".$locale_info["LOCALE_SCURRENCY"];
			}
		}
		return $val;
	}
	function write_axes_custom()
	{
		global $useFlashChartLibrary;
		
		echo "<axes>"."\n";
		echo "<y_axis>"."\n";
		if ($this->chrt_array["appearance"]["saxes"] != "true" )
		{
			echo "<line thickness=\"1\" color=\"DarkColor(#".$this->chrt_array["appearance"]["color141"].")\" caps=\"None\"/>"."\n";
			echo "<major_tickmark thickness=\"1\" color=\"DarkColor(#".$this->chrt_array["appearance"]["color141"].")\" caps=\"None\" opacity=\"1\"/>"."\n";
			echo "<minor_tickmark thickness=\"1\" color=\"DarkColor(#".$this->chrt_array["appearance"]["color141"].")\" caps=\"None\" opacity=\"1\"/>"."\n";
		}
		else
		{
			echo "<line thickness=\"1\" color=\"DarkColor(".$this->arrAxesColor[0].")\" caps=\"None\"/>"."\n";
			echo "<major_tickmark thickness=\"1\" color=\"DarkColor(".$this->arrAxesColor[0].")\" caps=\"None\" opacity=\"1\"/>"."\n";
			echo "<minor_tickmark thickness=\"1\" color=\"DarkColor(".$this->arrAxesColor[0].")\" caps=\"None\" opacity=\"1\"/>"."\n";
		}
		
		echo "<title enabled=\"true\">"."\n";
		if(count($this->arrDataLabels)==1)
			echo "<text>".$this->arrDataLabels[0]."</text>"."\n";
		else
			echo "<text>".$this->y_axis_label."</text>"."\n";
			
		if ($this->chrt_array["appearance"]["saxes"] != "true" )
			echo "<font color=\"DarkColor(#".$this->chrt_array["appearance"]["color141"].")\"/>"."\n";
		else
			echo "<font color=\"DarkColor(".$this->arrAxesColor[0].")\"/>"."\n";

		echo "</title>"."\n";
		
		echo "<labels enabled=\"".$this->chrt_array["appearance"]["sval"]."\" align=\"Inside\">"."\n";
		echo "<format>".$this->valueFormat(0,true)."</format>"."\n";
		if ($this->chrt_array["appearance"]["saxes"] != "true" )
			echo "<font color=\"#".$this->chrt_array["appearance"]["color61"]."\" bold=\"false\" italic=\"false\" underline=\"false\" render_as_html=\"false\">"."\n";
		else
			echo "<font color=\"DarkColor(".$this->arrAxesColor[0].")\" bold=\"false\" italic=\"false\" underline=\"false\" render_as_html=\"false\">"."\n";
		
		echo "</font>"."\n";
		echo "</labels>"."\n";
        
		$this->write_Logarithmic();
		$this->write_Stack();
		$this->write_Grid();
        
		echo "</y_axis>"."\n";
		
        $this->write_get_x_axis();
		echo "<text>".$this->label2."</text>"."\n";
		echo "<font color=\"DarkColor(#".$this->chrt_array["appearance"]["color131"].")\"/>"."\n";
		echo "</title>"."\n";
		
		$scroll="false";
		if($this->chrt_array["appearance"]["cscroll"]=="true" && $this->totalRecords>$this->numRecordsToShow && $useFlashChartLibrary && $this->hasFlash)
			$scroll="true";
		echo "<zoom enabled=\"".$scroll."\" allow_drag=\"false\" visible_range=\"".$this->numRecordsToShow."\"/>"."\n";

		echo "<labels enabled=\"".$this->chrt_array["appearance"]["sname"]."\" display_mode=\"normal\">"."\n";
		echo "<font color=\"#".$this->chrt_array["appearance"]["color51"]."\" bold=\"false\" italic=\"false\" underline=\"false\" render_as_html=\"false\">"."\n";
		echo "</font>"."\n";
		echo "<background enabled=\"false\">"."\n";
		echo "<fill enabled=\"false\" />"."\n";
		echo "<border enabled=\"true\" />"."\n";
		echo "</background>"."\n";
		echo "</labels>"."\n";
		echo "</x_axis>"."\n";
	
		$this->write_extra();

		echo "</axes>"."\n";
	}
	function write_Logarithmic()
	{
		if($this->chrt_array["appearance"]["slog"] == "true" )
		{
			echo "<scale type=\"Logarithmic\" log_base=\"10\"/>"."\n";
		}
	}
	function write_Grid()
	{
		if($this->chrt_array["appearance"]["sgrid"] == "true") 
		{
			echo "<major_grid interlaced=\"True\">"."\n";
			echo "<line color=\"#".$this->chrt_array["appearance"]["color121"]."\" opacity=\"0.7\"/>"."\n";
			echo "<interlaced_fills>"."\n";
			echo "<even><fill color=\"#".$this->chrt_array["appearance"]["color121"]."\" opacity=\"0.1\"/></even>"."\n";
			echo "<odd><fill color=\"#".$this->chrt_array["appearance"]["color121"]."\" opacity=\"0\"/></odd>"."\n";
			echo "</interlaced_fills>"."\n";
			echo "</major_grid>"."\n";
			echo "<minor_grid enabled=\"false\"/>"."\n";
		}
	}
	function write_extra()
	{
		if ($this->chrt_array["appearance"]["saxes"] == "true" )
		{
			echo "<extra>"."\n";
			for ( $i=1; $i < count($this->arrDataSeries); $i++ ) 
			{
				$position = ( $i % 2 == 0 ) ? "Normal" : "Opposite";
				echo "<y_axis name=\"".$this->chart_xmlencode($this->arrDataSeries[$i])."\" position=\"".$position."\" enabled=\"true\">"."\n";
				echo "<line thickness=\"1\" color=\"DarkColor(".$this->arrAxesColor[$i].")\" caps=\"None\"/>"."\n";
				echo "<major_tickmark thickness=\"1\" color=\"DarkColor(".$this->arrAxesColor[$i].")\" opacity=\"1\"/>"."\n";
				echo "<minor_tickmark thickness=\"1\" color=\"DarkColor(".$this->arrAxesColor[$i].")\" opacity=\"1\"/>"."\n";
				echo "<minor_grid enabled=\"false\"/>"."\n";
				echo "<major_grid enabled=\"false\"/>"."\n";
				echo "<title enabled=\"true\" align=\"Center\">"."\n";
				echo "<text>".$this->arrDataLabels[$i]."</text>"."\n";
				echo "<font color=\"DarkColor(".$this->arrAxesColor[$i].")\"/>"."\n";
				echo "</title>"."\n";

				echo "<labels align=\"Inside\" enabled=\"".$this->chrt_array["appearance"]["sval"]."\">"."\n";
				echo "<font color=\"DarkColor(".$this->arrAxesColor[$i].")\"/>"."\n";
				echo "<format>".$this->valueFormat($i,true)."</format>"."\n";
				echo "</labels>"."\n";
				echo "</y_axis>"."\n";
			}
			echo "</extra>"."\n";
		}
	}
	function write_chart_background()
	{
		echo "<fill type=\"Gradient\">"."\n";
		echo "<gradient angle=\"90\">"."\n";
		echo "<key position=\"0\" color=\"#".$this->chrt_array["appearance"]["color71"]."\"/>"."\n";
		if($this->webchart)
			echo "<key position=\"1\" color=\"#".$this->chrt_array["appearance"]["color71"]."\" opacity=\"0.5\"/>"."\n";
		else
			echo "<key position=\"1\" color=\"#".$this->chrt_array["appearance"]["color81"]."\" opacity=\"0.5\"/>"."\n";
		echo "</gradient>"."\n";
		echo "</fill>"."\n";
		echo "<corners type=\"Square\"/>"."\n";
		echo "<border enabled=\"True\" thickness=\"2\" type=\"Gradient\">"."\n";
		echo "<gradient type=\"Linear\">"."\n";
		echo "<key position=\"0\" color=\"#".$this->chrt_array["appearance"]["color91"]."\" opacity=\"0.5\" />"."\n";
		echo "<key position=\"1\" color=\"DarkColor(#".$this->chrt_array["appearance"]["color91"].")\" opacity=\"1\" />"."\n";
		echo "</gradient>"."\n";
		echo "</border>"."\n";
	}
	function color_series($series)
	{
		if(count($this->arrDataSeries)>1)
		{
			$this->scol="color=\"#".$this->arrDataColor[$series]."\"";
			$this->sleg="Series";
		}
		else
		{
			$this->scol="palette=\"Default\"";
			$this->sleg="Points";
		}
	}
	
	function labelFormat($fieldName,$data)
	{
		$table = $this->sessionPrefix;
		if($this->table_type=="db" && count($this->chrt_array['customLabels']))		
			$fieldName=$this->chrt_array['customLabels'][$fieldName];	
		$strViewFormat = $this->pSet->getViewFormat($fieldName);
		$strEditFormat = $this->pSet->getEditFormat($fieldName);
			
		include_once getabspath('classes/controls/ViewControlsContainer.php');
		$viewControls = new ViewControlsContainer($this->pSet, PAGE_CHART);			
		$value = html_special_decode($viewControls->showDBValue($fieldName, $data));

		if(strlen($value)>50)
		{
			$value=substr($value,0,47)."...";
		}
		return $this->chart_xmlencode($value);
	}
	
	function getDefaultValue($series, $row)
	{
		if($this->chrt_array["chart_type"]["type"]=="ohlc" || $this->chrt_array["chart_type"]["type"]=="candlestick")
		{
			$res="O: ".$this->formatCurrency("{%Open}{numDecimals:".$this->arrFormatDecimal[$series]."}",$series)."\r";
			$res.="H: ".$this->formatCurrency("{%High}{numDecimals:".$this->arrFormatDecimal[$series]."}",$series)."\r";
			$res.="L: ".$this->formatCurrency("{%Low}{numDecimals:".$this->arrFormatDecimal[$series]."}",$series)."\r";
			$res.="C: ".$this->formatCurrency("{%Close}{numDecimals:".$this->arrFormatDecimal[$series]."}",$series)."\r";
		}
		elseif($this->chrt_array["chart_type"]["type"]=="bubble")
		{
			$res="Series: {%SeriesName}"."\r";
			$res.="Point Name: ".$this->labelFormat($this->strLabel,$row).""."\r";
			$res.="Value: ".$this->formatCurrency("{%Value}{numDecimals:".$this->arrFormatDecimal[$series]."}",$series)."\r";
			$res.="Bubble Size: {%BubbleSize}"."\r";
		}
		elseif($this->chrt_array["chart_type"]["type"]=="2d_bar" || $this->chrt_array["chart_type"]["type"]=="2d_column" || $this->chrt_array["chart_type"]["type"]=="area" || $this->chrt_array["chart_type"]["type"]=="funnel")
		{
			$res=$this->labelFormat($this->strLabel,$row)." - ".$this->formatCurrency("{%YValue}{numDecimals:".$this->arrFormatDecimal[$series]."}",$series);
		}
		elseif($this->chrt_array["chart_type"]["type"]=="line" || $this->chrt_array["chart_type"]["type"]=="combined")
		{
			$res="Series: {%SeriesName}"."\r";
			$res.="Point Name: ".$this->labelFormat($this->strLabel,$row)."\r";
			$res.="Value: ".$this->formatCurrency("{%Value}{numDecimals:".$this->arrFormatDecimal[$series]."}",$series)."\r";
		}
		elseif($this->chrt_array["chart_type"]["type"]=="2d_pie" || $this->chrt_array["chart_type"]["type"]=="2d_doughnut")
		{
			$res=$this->labelFormat($this->strLabel,$row)." - ".$this->formatCurrency("{%YValue}{numDecimals:".$this->arrFormatDecimal[$series]."}",$series)."\r";
			$res.="Percent: {%YPercentOfSeries}{numDecimals:".$this->arrFormatDecimal[$series]."}%";
		}
		else
		{
			$res=$this->formatCurrency("{%YValue}{numDecimals:".$this->arrFormatDecimal[$series]."}",$series);
		}
		
		if( count($this->detailTablesData) )
		{
			$tableCaption = GetTableCaption( $this->detailTablesData[0]['dDataSourceTable'] );
			$tableCaption = $tableCaption ?  $tableCaption : runner_htmlspecialchars( $this->detailTablesData[0]['dDataSourceTable'] );
			$res.= "\r Click to see ".$tableCaption." details";
		}
		
		return $res;
	}
	
	function valueFormat($series,$x_axis=false)
	{
		if(!$this->arrFormatCustomer[$series])
		{
			if($x_axis)
				$value=$this->formatCurrency("{%Value}{numDecimals:".$this->arrFormatDecimal[$series]."}",$series);
			else
				$value=$this->formatCurrency("{%YValue}{numDecimals:".$this->arrFormatDecimal[$series]."}",$series);
		}
		else
		{
			$value=$this->arrFormatCustomerStr[$series];
		}
		return $value;
	}
	function tooltipFormat($series,$row)
	{
		if(!$this->arrFormatCustomer[$series])
		{
			$value=$this->getDefaultValue($series,$row);
		}
		else
		{
			$value=$this->arrFormatCustomerStr[$series];
		}
		return $value;
	}
	
	/**
	 *
	 */
	function get_data($refr)
	{
		$arrSer = array();
		for ( $i=0; $i < count($this->arrDataSeries); $i++ ) 
		{
			$this->color_series($i);
			//$this->arrAxesColor[$i] = "#".$this->chrt_array["appearance"]["scolor".($i+1)."1"];
			$arrSer["series".$i]="<series id= \"".$this->chart_xmlencode($this->arrDataSeries[$i])."\" name=\"".$this->arrDataLabels[$i]."\" ".$this->scol." ".($i==0?"":(" y_axis=\"".$this->chart_xmlencode($this->arrDataSeries[$i])."\"")).">"."\n";
			if($this->chrt_array["chart_type"]["type"]!="2d_pie" && $this->chrt_array["chart_type"]["type"]!="2d_doughnut" && $this->chrt_array["chart_type"]["type"]!="funnel")
				$arrSer["series".$i].="<label enabled=\"".$this->chrt_array["appearance"]["sval"]."\"><format>".$this->valueFormat($i)."</format></label>"."\n";
		}
		
		$qResult = $this->connection->query( $this->strSQL );
		
		if( $this->cipherer != null )
			$row = $this->cipherer->DecryptFetchedArray( $qResult->fetchAssoc() );
		else 
			$row = $qResult->fetchAssoc();
			
		$j = 0;
		$recPerRow = $this->numRecordsToShow;			
		while ($row) 
		{
			$j++;
			
			if($this->chrt_array["appearance"]["cscroll"]=="true")
				$recPerRow++;
			
			if ( $j > $recPerRow && $recPerRow>0) 
				break;

			for ( $i=0; $i < count($this->arrDataSeries); $i++ ) 
			{
				$arrSer["series".$i].=$this->get_point($i,$row)."\n";
			}
			
			if( $this->cipherer != null )
				$row = $this->cipherer->DecryptFetchedArray( $qResult->fetchAssoc() );
			else 
				$row = $qResult->fetchAssoc();
		}
		
		$this->totalRecords = $j;
		for ( $i=0; $i < count($this->arrDataSeries); $i++ ) 
		{
			if($refr)
			{
				echo $this->arrDataSeries[$i]."\n";
				$arrSer["series".$i]=str_replace(array("\\","\n"),array("\\\\","\\n"),$arrSer["series".$i]);
			}
		
			if($j>0)
				echo $arrSer["series".$i] . "</series>";
			
			if(!$refr || $i<count($this->arrDataSeries)-1)
			{
				echo "\n";
			}
		}
		
		$this->connection->close();
	}
	
	function chart_xmlencode($str)
	{
		return str_replace(array("&","<",">","\""),array("&amp;","&lt;","&gt;","&quot;"),$str);
	}
	function write_plot_background()
	{
		if($this->chrt_array["appearance"]["color81"]!="")
		{
			echo "<data_plot_background>"."\n";
			if($this->webchart)
				echo "<fill enabled=\"true\" type=\"Solid\" color=\"#".$this->chrt_array["appearance"]["color81"]."\" opacity=\"1\"/>";
			else
				echo "<fill opacity=\"0.3\"/>"."\n";
			echo "</data_plot_background>"."\n";
		}
	}
	
	/**
	 * Get an 'actions' block for a particular 'point'
	 * @param Array data
	 */
	function getActions( $data, $seriesId, $pointId )
	{		
		global $strTableName, $useFlashChartLibrary;
		
		$delimiter = $useFlashChartLibrary ? '\n' : '';
		
		if ( $this->dashChart )
		{
			$masterKeysArr = array();
			foreach ( $this->detailTablesData as $detailId => $detail )
			{
				foreach( $detail['masterKeys'] as $idx => $mk ) 
				{
					$masterKeysArr[ $detail['dDataSourceTable'] ] = array( 'masterkey'.($idx + 1) => $data[$mk] );
				}
			}
			
			if (!$this->dashChartFirstPointSelected)
			{
				$this->dashChartFirstPointSelected = true;
				$this->detailMasterKeys = my_json_encode( $masterKeysArr );
			}
			$clickData = array( "masterKeys" => $masterKeysArr, "seriesId" => $seriesId, "pointId" => $pointId );
			
			return '<actions>'.$delimiter
				.'<action type="call" function="Runner.Charts.dispatchChartClick">'.$delimiter
				.'<arg>'.$this->pageId.'</arg>'.$delimiter
				.'<arg>' . my_json_encode( $clickData ) . '</arg>'.$delimiter
				.'</action>'.$delimiter
			.'</actions>'.$delimiter;
		}
		else
		{
			if( !count($this->detailTablesData) )
				return '';
			// The one detail table is allowed for a chart page only
			$detailTableData = $this->detailTablesData[0];
			$masterquery = "mastertable=".rawurlencode( $strTableName );
			foreach( $detailTableData['masterKeys'] as $idx => $mk ) 
			{
				$masterquery.= "&masterkey".($idx + 1)."=".rawurlencode( $data[$mk] );
			}
			
			$url = runner_htmlspecialchars( GetTableLink( $detailTableData['dShortTable'], $detailTableData['dType'], $masterquery ) );
			
			return '<actions>'.$delimiter
				.'<action type="navigateToURL" url="'.$url.'" target="_self"/>'.$delimiter
			.'</actions>'.$delimiter;
		}
	}
}

class Chart_Bar extends Chart
{
	var $stacked;
	var $_2d;
	var $bar;
	
	function Chart_Bar(&$ch_array, $param)
	{
		parent::Chart($ch_array, $param);
		$this->stacked=$param["stacked"];
		$this->_2d=$param["2d"];
		$this->bar=$param["bar"];
	}
	function write_data()
	{
		echo "<chart plot_type=\"".$this->plot_type_name()."\">"."\n";
        echo "<data>"."\n";
        $this->get_data(false);
        echo "</data>"."\n";
	}
	function write_dps()
	{
		echo "<data_plot_settings default_series_type=\"Bar\"".$this->series_3d_mode().">"."\n";
        echo "<bar_series group_padding=\"0.5\" ".$this->chart_style_type().">"."\n";
        echo $this->write_label_settings();
        echo "</bar_series>"."\n";
        echo "</data_plot_settings>"."\n";
	}
	function write_get_x_axis()
	{
		echo "<x_axis>"."\n";
		echo "<line thickness=\"1\" color=\"DarkColor(#".$this->chrt_array["appearance"]["color131"].")\" caps=\"None\"/>"."\n";
		echo "<major_tickmark thickness=\"1\" color=\"DarkColor(#".$this->chrt_array["appearance"]["color131"].")\" caps=\"None\" opacity=\"1\"/>"."\n";
		echo "<minor_tickmark thickness=\"1\" color=\"DarkColor(#".$this->chrt_array["appearance"]["color131"].")\" caps=\"None\" opacity=\"1\"/>"."\n";

		echo "<title enabled=\"true\" align=\"Center\">"."\n";
	}
	function plot_type_name()
	{
		if(!$this->bar)
		{
			return "CategorizedVertical";
		}
		else
		{
			return "CategorizedHorizontal";
		}
	}
	function series_3d_mode()
	{
		$str="";
		if(!$this->_2d)
		{
			$str= " enable_3d_mode=\"True\"";		
			if($this->bar)
			{
				$str.= " z_aspect=\"1.1\"";
			}
		}
		return $str;
	}
	function chart_style_type()
	{
//		if($this->_2d)
	//	{
			$str="";
			if($this->chrt_array["appearance"]["aqua"] == 1)
			{
				$str=" style=\"AquaLight\"";
			}
			elseif($this->chrt_array["appearance"]["aqua"] == 2)
			{
				$str=" style=\"AquaDark\"";
			}

			if($this->chrt_array["appearance"]["cview"] == 1)
			{
				$str.=" shape_type=\"Cone\"";
			}
			elseif($this->chrt_array["appearance"]["cview"] == 2)
			{
				$str.=" shape_type=\"Cylinder\"";
			}
			elseif($this->chrt_array["appearance"]["cview"] == 3)
			{
				$str.=" shape_type=\"Pyramid\"";
			}
			return $str;
		//}
	}
	function write_Stack()
	{
		if($this->stacked)
		{
			if ($this->chrt_array["appearance"]["sstacked"] == "true") 
			{
				echo "<scale mode=\"PercentStacked\" maximum=\"100\" major_interval=\"10\"/>"."\n";
			} 
			else 
			{
				echo "<scale mode=\"Stacked\"/>"."\n";
			}
		}
	}
	function write_label_settings()
	{
		$rotation="";
		$position="";
		$effect="";
		if($this->stacked)
		{
			$rotation=" rotation=\"0\"";
			$position="<position  anchor=\"Center\" halign=\"Center\" valign=\"Center\" padding=\"0\"/>";
			$effect="<font bold=\"False\" color=\"White\">"."\n";
            $effect.="<effects>"."\n";
			$effect.="<drop_shadow enabled=\"True\" opacity=\"0.5\" distance=\"2\" blur_x=\"1\" blur_y=\"1\"/>"."\n";
            $effect.="</effects>"."\n";
            $effect.="</font>"."\n";
            $effect.="<background enabled=\"False\"/>"."\n";
		}
			
		$str="<label_settings enabled=\"".$this->chrt_array["appearance"]["sval"]."\"".$rotation.">"."\n";
		$str.=$position."\n";
		$str.=$effect."\n";
        $str.="</label_settings>"."\n";
		return $str;
	}
	function get_point($series,$row)
	{
		$strLabelFormat=$this->labelFormat($this->strLabel, $row);
		if($this->table_type!="db" || !count($this->chrt_array['customLabels']))
			$strDataSeries = $row[$this->arrDataSeries[$series]];
		else
			$strDataSeries = $row[$this->chrt_array['customLabels'][$this->arrDataSeries[$series]]];
		//echo $this->arrDataSeries[$series]."<br>";
		//echo $this->strLabel."<br>";
		//print_r($row);
		$selected = $this->setFirstPoint ? "selected=\"true\"" : "";
		$str="<point id=\"" . $strLabelFormat . "\" name=\"" . $strLabelFormat . "\" y=\"". $this->chart_xmlencode(str_replace(",",".",$strDataSeries)+0). "\" ".$selected.">";
		$str.="<tooltip enabled=\"True\"><format>".$this->tooltipFormat($series,$row)."</format></tooltip>"."\n";
		$str.= $this->getActions( $row , $this->arrDataSeries[$series], $strLabelFormat );
		$str.="</point>"."\n";

		$this->setFirstPoint = false;

		return $str;

	}
	function write_axes()
	{
		$this->write_axes_custom();
	}
	function write_legend_tag()
	{
		$posit="";
		$padd="";
		$hgt="";
		$align="";
		if($this->_2d && !$this->bar && !$this->stacked)
		{
			$posit="Bottom";
			$align="align=\"Spread\"";
			$padd="padding=\"15\"";
			$hgt="height=\"20%\"";
	}
		else
		{
			$posit="Right";
		}
		echo "<legend enabled=\"true\" position=\"".$posit."\" ignore_auto_item=\"true\" ".$align." ".$padd." ".$hgt.">"."\n";
	}
}
class Chart_Line extends Chart
{
	var $type_line;
	function Chart_Line(&$ch_array, $param)
	{
		parent::Chart($ch_array, $param);
		$this->type_line=$param["type_line"];
	}
	
	function write_data()
	{
		echo "<chart plot_type=\"CategorizedVertical\">"."\n";
        echo "<data>"."\n";
        $this->get_data(false);
		echo "</data>"."\n";
	}
	
	function write_dps()
	{
		echo "<data_plot_settings default_series_type=\"".$this->write_series_type()."\">"."\n";
		echo "<line_series point_padding=\"0.2\" group_padding=\"1\">"."\n";
		echo "<label_settings enabled=\"".$this->chrt_array["appearance"]["sval"]."\">"."\n";
		echo "<background enabled=\"false\"/>"."\n";
		echo "<font color=\"Rgb(45,45,45)\" bold=\"true\" size=\"9\">"."\n";
		echo "<effects enabled=\"true\">"."\n";
		echo "<glow enabled=\"true\" color=\"White\" opacity=\"1\" blur_x=\"1.5\" blur_y=\"1.5\" strength=\"3\"/>"."\n";
		echo "</effects>"."\n";
		echo "</font>"."\n";
		echo "</label_settings>"."\n";
		echo "<marker_settings enabled=\"true\"/>"."\n";
		echo "<line_style>"."\n";
		echo "<line thickness=\"3\"/>"."\n";
		echo "</line_style>"."\n";
		echo "</line_series>"."\n";
		echo "</data_plot_settings>"."\n";
	}
	function get_point($series,$row)
	{
		$strLabelFormat=$this->labelFormat($this->strLabel, $row);
		if($this->table_type!="db" || !count($this->chrt_array['customLabels']))
			$strDataSeries = $row[$this->arrDataSeries[$series]];
		else
			$strDataSeries = $row[$this->chrt_array['customLabels'][$this->arrDataSeries[$series]]];

		$selected = $this->setFirstPoint ? "selected=\"true\"" : "";
		$str="<point id=\"" . $strLabelFormat . "\" name=\"" . $strLabelFormat . "\" y=\"". $this->chart_xmlencode(str_replace(",",".",$strDataSeries)+0). "\" ".$selected.">";
		$str.="<tooltip enabled=\"True\"><format>".$this->tooltipFormat($series,$row)."</format></tooltip>"."\n";
		$str.= $this->getActions( $row, $this->arrDataSeries[$series], $strLabelFormat );
		$str.="</point>"."\n";

		$this->setFirstPoint = false;

		return $str;
	}
	function color_series($series)
	{
		$this->scol="color=\"#".$this->arrDataColor[$series]."\"";
		if(count($this->arrDataSeries)>1)
		{
			$this->sleg="Series";
		}
		else
		{
			$this->sleg="Points";
		}
//		$this->sleg="Series";
	}
	function write_format()
	{
		if($this->sleg=="Points")
		{
			echo "<format>{%Icon} {%Name} (".$this->valueFormat(0).")</format>"."\n";
		}
	}
	function write_axes()
	{
		$this->write_axes_custom();
	}
	function write_series_type()
	{
		switch($this->type_line)
		{
			case "line": 
				return "Line";
				break;
			case "spline": 
				return "Spline";
				break;
			case "step_line": 
				return "StepLineForward";
				break;
		}
	}
	function write_legend_tag()
	{
		echo "<legend enabled=\"true\" position=\"Bottom\" ignore_auto_item=\"true\" align=\"Spread\" padding=\"15\" height=\"20%\">"."\n";
	}
	function write_get_x_axis()
	{
		echo "<x_axis tickmarks_placement=\"Center\">"."\n";
        echo "<title enabled=\"true\" align=\"Center\">"."\n";
	}
	function write_Stack()
	{
		return;
	}

}
class Chart_Area extends Chart
{
	var $stacked;
	function Chart_Area(&$ch_array, $param)
	{
		parent::Chart($ch_array, $param);
		$this->stacked=$param["stacked"];
	}
	
	function write_data()
	{
		echo "<chart plot_type=\"CategorizedVertical\">"."\n";
        echo "<data>"."\n";
		$this->get_data(false);
		echo "</data>"."\n";
	}
	
	function write_dps()
	{
		echo "<data_plot_settings default_series_type=\"Area\">"."\n";
		echo "<area_series point_padding=\"0.2\" group_padding=\"1\">"."\n";
		$this->write_label_settings();
		echo "<area_style>"."\n";
		echo "<line enabled=\"true\" thickness=\"2\" color=\"%Color\"/>"."\n";
		echo "<fill color=\"%Color\" opacity=\"0.5\"/>"."\n";
		echo "<states>"."\n";
		echo "<hover>"."\n";
		echo "<line enabled=\"true\" thickness=\"2\" color=\"LightColor(%Color)\"/>"."\n";
		echo "<fill color=\"LightColor(%Color)\" opacity=\"1.0\"/>"."\n";
		echo "</hover>"."\n";
		echo "</states>"."\n";
		echo "</area_style>"."\n";
		echo "<marker_settings enabled=\"True\">"."\n";
		echo "<marker type=\"Circle\" size=\"6\"/>"."\n";
		echo "</marker_settings>"."\n";
		echo "<tooltip_settings enabled=\"True\">"."\n";
		echo "<background>"."\n";
		echo "<border color=\"DarkColor(%Color)\"/>"."\n";
		echo "</background>"."\n";
		echo "<font color=\"DarkColor(%Color)\"/>"."\n";
		echo "</tooltip_settings>"."\n";
		echo "</area_series>"."\n";
		echo "</data_plot_settings>"."\n";
	}
	function get_point($series,$row)
	{
		$strLabelFormat=$this->labelFormat($this->strLabel, $row);
		if($this->table_type!="db" || !count($this->chrt_array['customLabels']))
			$strDataSeries = $row[$this->arrDataSeries[$series]];
		else
			$strDataSeries = $row[$this->chrt_array['customLabels'][$this->arrDataSeries[$series]]];
		$selected = $this->setFirstPoint ? "selected=\"true\"" : "";
		$str="<point id=\"" . $strLabelFormat . "\" name=\"" . $strLabelFormat . "\" y=\"". $this->chart_xmlencode(str_replace(",",".",$strDataSeries)+0). "\" ".$selected.">";
		$str.="<tooltip enabled=\"True\"><format>".$this->tooltipFormat($series,$row)."</format></tooltip>"."\n";
		$str.= $this->getActions( $row, $this->arrDataSeries[$series], $strLabelFormat );
		$str.="</point>"."\n";

		$this->setFirstPoint = false; 

		return $str;
	}
	function color_series($series)
	{
		$this->scol="color=\"#".$this->arrDataColor[$series]."\"";
		$this->sleg="Series";
	}
	function write_axes()
	{
		$this->write_axes_custom();
	}
	function write_label_settings()
	{
		echo "<label_settings enabled=\"true\">"."\n";
		echo "<position anchor=\"CenterBottom\"/>"."\n";
		echo "<background enabled=\"true\">"."\n";
		echo "<border enabled=\"false\"/>"."\n";
		echo "<fill enabled=\"true\" type=\"Solid\" color=\"DarkColor(%Color)\" opacity=\"0.8\"/>"."\n";
		echo "<effects enabled=\"false\"/>"."\n";
		echo "<inside_margin all=\"0\"/>"."\n";
		echo "<corners type=\"Rounded\" all=\"3\"/>"."\n";
		echo "</background>"."\n";
		echo "<font color=\"White\" bold=\"false\"/>"."\n";
		echo "</label_settings>"."\n";
	}
	function write_legend_tag()
	{
		echo "<legend enabled=\"true\" position=\"Bottom\" ignore_auto_item=\"true\" align=\"Spread\" padding=\"15\" height=\"20%\">"."\n";
	}
	function write_Stack()
	{
		if($this->stacked)
		{
			if ($this->chrt_array["appearance"]["sstacked"] == "true") 
			{
				echo "<scale mode=\"PercentStacked\" maximum=\"100\" major_interval=\"10\"/>"."\n";
			} 
			else 
			{
				echo "<scale mode=\"Stacked\"/>"."\n";
			}
		}
	}
	function write_get_x_axis()
	{
			echo "<x_axis>"."\n";
			echo "<line thickness=\"1\" color=\"DarkColor(#".$this->chrt_array["appearance"]["color131"].")\" caps=\"None\"/>"."\n";
			echo "<major_tickmark thickness=\"1\" color=\"DarkColor(#".$this->chrt_array["appearance"]["color131"].")\" caps=\"None\" opacity=\"1\"/>"."\n";
			echo "<minor_tickmark thickness=\"1\" color=\"DarkColor(#".$this->chrt_array["appearance"]["color131"].")\" caps=\"None\" opacity=\"1\"/>"."\n";
			echo "<title enabled=\"true\" align=\"Center\">"."\n";
	}
}
class Chart_Pie extends Chart
{
	var $pie;
	var $_2d;
	function Chart_Pie(&$ch_array, $param)
	{
		parent::Chart($ch_array, $param);
		$this->pie=$param["pie"];
		$this->_2d=$param["2d"];
	}
	function write_data()
	{
		echo "<chart plot_type=\"".$this->plot_type_name()."\">"."\n";
		$this->write_dps();
		echo "<data>"."\n";
		$this->get_data(false);
		echo "</data>"."\n";
	}
	function write_dps()
	{
		if($this->_2d)
			echo "<data_plot_settings enable_3d_mode=\"false\">"."\n";
		else
			echo "<data_plot_settings enable_3d_mode=\"true\">"."\n";
		echo "<pie_series>"."\n";
		$this->write_label_settings();
		echo "</pie_series>"."\n";
		echo "</data_plot_settings>"."\n";
	}
	function get_point($series,$row)
	{
		$strLabelFormat=$this->labelFormat($this->strLabel, $row);
		if($this->table_type!="db" || !count($this->chrt_array['customLabels']))
			$strDataSeries = $row[$this->arrDataSeries[$series]];
		else
			$strDataSeries = $row[$this->chrt_array['customLabels'][$this->arrDataSeries[$series]]];
		$selected = $this->setFirstPoint ? "selected=\"true\"" : "";
		$str="<point id=\"" . $strLabelFormat . "\" name=\"" . $strLabelFormat . "\" y=\"". $this->chart_xmlencode(str_replace(",",".",$strDataSeries)+0). "\" ".$selected.">";
		$str.="<tooltip enabled=\"True\"><format>".$this->tooltipFormat($series,$row)."</format></tooltip>"."\n";
		$showvalname="false";
		if($this->chrt_array["appearance"]["sval"]=="true" || $this->chrt_array["appearance"]["sname"]=="true")
			$showvalname="true";
		$formatvalname="";
		if($this->chrt_array["appearance"]["sval"]=="true")
			$formatvalname=$this->formatCurrency("{%YValue}{numDecimals:".$this->arrFormatDecimal[$series]."}",$series);
		if($this->chrt_array["appearance"]["sval"]=="true" && $this->chrt_array["appearance"]["sname"]=="true")
			$formatvalname.=chr(13);
		if($this->chrt_array["appearance"]["sname"]=="true")
			$formatvalname.=$strLabelFormat;
		$str.="<label enabled=\"".$showvalname."\"><format>".$formatvalname."</format></label>"."\n";
		$str.= $this->getActions( $row, $this->arrDataSeries[$series], $strLabelFormat );
		$str.="</point>"."\n";
		$this->setFirstPoint = false;
		return $str;
	}
	function write_axes()
	{
		return;
	}
	function plot_type_name()
	{
		if($this->pie)
		{
			return "Pie";
		}
		else
		{
			return "Doughnut";
		}
	}
	function write_label_settings()
	{
			$showvalname="false";
			if($this->chrt_array["appearance"]["sval"]=="true" || $this->chrt_array["appearance"]["sname"]=="true")
				$showvalname="true";
			$mode="";
			if(count($this->arrDataSeries)==1)
				$mode="mode=\"Outside\"";
			echo "<label_settings enabled=\"".$showvalname."\" ".$mode." multi_line_align=\"Center\">"."\n";
			echo "<font color=\"#".$this->chrt_array["appearance"]["color61"]."\" bold=\"false\" italic=\"false\" underline=\"false\" render_as_html=\"false\">"."\n";
			echo "</font>"."\n";
			echo "<background enabled=\"false\"/>"."\n";
			echo "<position anchor=\"Center\" valign=\"Center\" halign=\"Center\" padding=\"20\"/>"."\n";
			echo "<font bold=\"false\" />"."\n";
			echo "</label_settings>"."\n";
			echo "<connector color=\"Black\" opacity=\"0.4\"/>"."\n";
	}
	function write_legend_tag()
	{
		echo "<legend enabled=\"true\" position=\"Bottom\" ignore_auto_item=\"true\" align=\"Spread\" padding=\"15\" height=\"20%\">"."\n";
	}
	function color_series($series)
	{
		$this->scol="palette=\"Default\"";
		$this->sleg="Points";
	}
}
class Chart_Combined extends Chart
{
	function Chart_Combined(&$ch_array, $param)
	{
		parent::Chart($ch_array, $param);
	}
	function color_series($series)
	{
		if(count($this->arrDataSeries)>1)
		{
			$this->scol="color=\"#".$this->arrDataColor[$series]."\"";
			$this->sleg="Series";
		}
		else
		{
			$this->scol="palette=\"Default\"";
			$this->sleg="Points";
		}
	}
	function get_type_series($num_series)
	{
		if($num_series==0)
			return "Spline";
		elseif($num_series==1)
			return "SplineArea";
		else
			return "Bar";
	}
	
	/**
	 *
	 */
	function get_data($refr)
	{
		$arrSer = array();
		for ( $i=0; $i < count($this->arrDataSeries); $i++ ) 
		{
			$this->color_series($i);
			
			$arrSer["series".$i]="<series id= \"".$this->chart_xmlencode($this->arrDataSeries[$i])."\" name=\"".$this->arrDataLabels[$i]."\" ".$this->scol." ".($i==0?"":(" y_axis=\"".$this->chart_xmlencode($this->arrDataSeries[$i])."\""))." type=\"".$this->get_type_series($i)."\">"."\n";
			$arrSer["series".$i].="<label enabled=\"".$this->chrt_array["appearance"]["sval"]."\"><format>".$this->valueFormat($i)."</format></label>"."\n";
		}
		
		$qResult = $this->connection->query( $this->strSQL );
		
		if( $this->cipherer != null )
			$row = $this->cipherer->DecryptFetchedArray( $qResult->fetchAssoc() );
		else 
			$row = $qResult->fetchAssoc();
		
		$j = 0;
		$recPerRow = $this->numRecordsToShow;		
		while ($row) 
		{
			$j++;
			
			if($this->chrt_array["appearance"]["cscroll"]=="true")
				$recPerRow++;
			
			if ( $j > $recPerRow && $recPerRow>0) 
				break;

			for ( $i=0; $i < count($this->arrDataSeries); $i++ ) 
			{
				$arrSer["series".$i].=$this->get_point($i,$row)."\n";
			}
			
			if( $this->cipherer != null )
				$row = $this->cipherer->DecryptFetchedArray( $qResult->fetchAssoc() );
			else 
				$row = $qResult->fetchAssoc();
		}
		
		$this->totalRecords = $j;
		for ( $i=0; $i < count($this->arrDataSeries); $i++ ) 
		{
			if($refr)
			{
				echo $this->arrDataSeries[$i]."\n";
				$arrSer["series".$i]=str_replace(array("\\","\n"),array("\\\\","\\n"),$arrSer["series".$i]);
			}
			
			if( $j > 0 )
				echo $arrSer["series".$i] . "</series>";
			
			if( !$refr || $i < count($this->arrDataSeries) - 1 )
				echo "\n";
		}		
	}
	
	function get_point($series,$row)
	{
		$strLabelFormat=$this->labelFormat($this->strLabel, $row);
		if($this->table_type!="db" || !count($this->chrt_array['customLabels']))
			$strDataSeries = $row[$this->arrDataSeries[$series]];
		else
			$strDataSeries = $row[$this->chrt_array['customLabels'][$this->arrDataSeries[$series]]];

		$selected = $this->setFirstPoint ? "selected=\"true\"" : "";
		$str="<point id=\"" . $strLabelFormat . "\" name=\"" . $strLabelFormat . "\" y=\"". ($this->chart_xmlencode($strDataSeries)+0). "\" ".$selected.">";
		$str.="<tooltip enabled=\"True\"><format>".$this->tooltipFormat($series,$row)."</format></tooltip>"."\n";
		$str.= $this->getActions( $row, $this->arrDataSeries[$series], $strLabelFormat );
		$str.="</point>"."\n";
		$this->setFirstPoint = false;
		return $str;
	}
	function write_data()
	{
		
		echo "<chart plot_type=\"CategorizedVertical\">"."\n";
        echo "<data>"."\n";
		$this->get_data(false);		
        echo "</data>"."\n";
	}
	function write_dps()
	{
		echo "<data_plot_settings default_series_type=\"Bar\">"."\n";
		echo "<bar_series group_padding=\"0.3\">"."\n";
		echo "</bar_series>"."\n";
		echo "<line_series>"."\n";
		echo "<line_style>"."\n";
		echo "<line thickness=\"3\"/>"."\n";
		echo "</line_style>"."\n";
		echo "</line_series>"."\n";
		echo "<area_series>"."\n";
		echo "<area_style>"."\n";
		echo "<line enabled=\"true\" thickness=\"1\" color=\"DarkColor(%Color)\"/>"."\n";
		echo "<fill opacity=\"0.7\"/>"."\n";
		echo "<states>"."\n";
		echo "<hover>"."\n";
		echo "<fill opacity=\"0.9\"/>"."\n";
		echo "<hatch_fill enabled=\"true\" type=\"Checkerboard\" opacity=\"0.2\"/>"."\n";
		echo "</hover>"."\n";
		echo "</states>"."\n";
		echo "</area_style>"."\n";
		echo "</area_series>"."\n";
		echo "</data_plot_settings>"."\n";
	}
	function write_legend_tag()
	{
		echo "<legend enabled=\"true\" position=\"Bottom\" ignore_auto_item=\"true\" align=\"Spread\" padding=\"15\" height=\"20%\">"."\n";
	}
	function write_get_x_axis()
	{
		echo "<x_axis tickmarks_placement=\"Center\">"."\n";
        echo "<title enabled=\"true\" align=\"Center\">"."\n";
	}
	function write_axes()
	{
		$this->write_axes_custom();
	}
	function write_Stack()
	{
		return;
	}
}
class Chart_Funnel extends Chart
{
	var $ftype;
	var $inver;
	
	function Chart_Funnel(&$ch_array, $param)
	{
		parent::Chart($ch_array, $param);
		$this->ftype=$param["funnel_type"]; 
		$this->inver=$param["funnel_inv"]; 
	}
	function write_data()
	{
		echo "<chart plot_type=\"Funnel\">"."\n";
        echo "<data>"."\n";
        $this->get_data(false);
        echo "</data>"."\n";
	}
	function write_dps()
	{
		echo "<data_plot_settings ".$this->series_3d_mode().">"."\n";
		$this->funnel_series();
		if($this->chrt_array["appearance"]["sval"]=="true" || $this->chrt_array["appearance"]["sname"]=="true")
			echo "<connector enabled=\"true\" color=\"Black\" opacity=\"0.5\"/>"."\n";
    	echo "<label_settings enabled=\"true\">"."\n";
		echo "<animation enabled=\"true\" type=\"SideFromRight\" show_mode=\"Smoothed\" start_time=\"0.3\" duration=\"2\" interpolation_type=\"Back\"/>"."\n";
		echo "<position anchor=\"center\" padding=\"50\"/>"."\n";
		echo "<font bold=\"true\"/>"."\n";
		echo "</label_settings>"."\n";
		echo "<tooltip_settings enabled=\"true\">"."\n";
		echo "<background>"."\n";
		echo "<corners type=\"Rounded\" all=\"3\"/>"."\n";
		echo "</background>"."\n";
		echo "<font bold=\"false\"/>"."\n";
		echo "</tooltip_settings>"."\n";
		echo "<funnel_style>"."\n";
		echo "<states>"."\n";
		echo "<hover>"."\n";
		echo "<fill color=\"%Color\"/>"."\n";
		echo "<hatch_fill enabled=\"true\" type=\"Percent50\" color=\"White\" opacity=\"0.3\"/>"."\n";
		echo "</hover>"."\n";
		echo "<selected_hover>"."\n";
		echo "<fill color=\"%Color\"/>"."\n";
		echo "<hatch_fill type=\"Checkerboard\" color=\"#404040\" opacity=\"0.1\"/>"."\n";
		echo "</selected_hover>"."\n";
		echo "<selected_normal>"."\n";
		echo "<fill color=\"%Color\"/>"."\n";
		echo "<hatch_fill type=\"Checkerboard\" color=\"Black\" opacity=\"0.1\"/>"."\n";
		echo "</selected_normal>"."\n";
		echo "</states>"."\n";
		echo "</funnel_style>"."\n";
		echo "</funnel_series>"."\n";
        echo "</data_plot_settings>"."\n";
	}
	function series_3d_mode()
	{
		$str="";
		if($this->ftype>0) 
		{
			$str= " enable_3d_mode=\"True\"";		
		}
		return $str;
	}
	function get_point($series,$row)
	{
		$strLabelFormat=$this->labelFormat($this->strLabel, $row);
		if($this->table_type!="db" || !count($this->chrt_array['customLabels']))
			$strDataSeries = $row[$this->arrDataSeries[$series]];
		else
			$strDataSeries = $row[$this->chrt_array['customLabels'][$this->arrDataSeries[$series]]];

		$selected = $this->setFirstPoint ? "selected=\"true\"" : "";
		$str="<point id=\"" . $strLabelFormat . "\" name=\"" . $strLabelFormat . "\" y=\"". $this->chart_xmlencode(str_replace(",",".",$strDataSeries)+0). "\" ".$selected.">";
		$str.="<tooltip enabled=\"True\"><format>".$this->tooltipFormat($series,$row)."</format></tooltip>"."\n";
		$showvalname="false";
		if($this->chrt_array["appearance"]["sval"]=="true" || $this->chrt_array["appearance"]["sname"]=="true")
			$showvalname="true";
		$formatvalname="";
		if($this->chrt_array["appearance"]["sval"]=="true")
			$formatvalname=$this->formatCurrency("{%YValue}{numDecimals:".$this->arrFormatDecimal[$series]."}",$series);
		if($this->chrt_array["appearance"]["sval"]=="true" && $this->chrt_array["appearance"]["sname"]=="true")
			$formatvalname.=" - ";
		if($this->chrt_array["appearance"]["sname"]=="true")
			$formatvalname.=$strLabelFormat;
		$str.="<label enabled=\"".$showvalname."\"><format>".$formatvalname."</format></label>"."\n";
		$str.= $this->getActions( $row, $this->arrDataSeries[$series], $strLabelFormat );
		$str.="</point>"."\n";
		$this->setFirstPoint = false;
		return $str;
	}
	function write_axes()
	{
		return;
	}
	function write_legend_tag()
	{
		echo "<legend enabled=\"true\" position=\"Bottom\" ignore_auto_item=\"true\" align=\"Spread\" padding=\"15\" height=\"20%\">"."\n";
	}
	function color_series($series)
	{
		$this->scol="palette=\"Default\"";
		$this->sleg="Points";
	}
	function funnel_series()
	{
		if($this->inver)
			$inv="inverted=\"false\"";
		else
			$inv="inverted=\"true\"";
			
		if($this->ftype<2)
		{
			echo "<funnel_series ".$inv." neck_height=\"0\" min_width=\"0\" padding=\"0\" fit_aspect=\"0.9\">"."\n";
			echo "<animation enabled=\"true\" start_time=\"0.3\" duration=\"2\" type=\"SideFromLeft\" animate_opacity=\"false\" interpolation_type=\"Elastic\" show_mode=\"Smoothed\"/>"."\n";
		}
		else
		{
			echo "<funnel_series ".$inv." neck_height=\"0\" fit_aspect=\"1\" min_width=\"0\" padding=\"0\" mode=\"Square\">"."\n";
			echo "<animation enabled=\"true\" start_time=\"0.3\" duration=\"2\" type=\"SideFromTop\" animate_opacity=\"true\" interpolation_type=\"Bounce\" show_mode=\"Smoothed\" />"."\n";
		}
	}
}
class Chart_Bubble extends Chart
{
	var $_2d;
	var $oppos;
	
	function Chart_Bubble(&$ch_array, $param)
	{
		parent::Chart($ch_array, $param);
		//$this->strLabel="";
		$this->_2d=$param["2d"];
		$this->oppos=$param["oppos"];
	}
	function write_data()
	{
		echo "<chart ".$this->char_type().">"."\n";
        echo "<data>"."\n";
        $this->get_data(false);
        echo "</data>"."\n";
	}
	function write_dps()
	{
		echo "<data_plot_settings default_series_type=\"Bubble\">"."\n";
		echo "<bubble_series maximum_bubble_size=\"40%\" ".$this->style_chart().">"."\n";
		echo "<tooltip_settings enabled=\"true\">"."\n";
		echo "</tooltip_settings>"."\n";
		echo "<bubble_style>"."\n";
		
		$this->fill_oppos();
		
		echo "<states>"."\n";
		echo "<hover>"."\n";
		echo "<border thickness=\"2\"/>"."\n";
		echo "<fill color=\"LightColor(%Color)\"/>"."\n";
		echo "</hover>"."\n";
		echo "</states>"."\n";
		echo "</bubble_style>"."\n";
		echo "</bubble_series>"."\n";
		echo "</data_plot_settings>"."\n";
	}
	function write_legend_tag()
	{
		echo "<legend enabled=\"true\" position=\"Bottom\" ignore_auto_item=\"true\" align=\"Spread\" padding=\"15\" height=\"20%\">"."\n";
	}
	function char_type()
	{
		if($this->strLabel=="")
			$str="plot_type=\"CategorizedBySeriesHorizontal\"";
		else 
		{
			if($this->_2d) 
				$str="type=\"CategorizedVertical\"";
			else
				$str="type=\"Categorized\"";
		}
		return $str;
	}
	function fill_oppos()
	{
//		if($this->_2d) 
//		{
			echo "<fill opacity=\"".$this->oppos."\"/>"."\n";
			echo "<border thickness=\"2\"/>"."\n";
//		}
	}
	function style_chart()
	{
		if(!$this->_2d) 
			return "style=\"Aqua\"";
	
	}
	function get_point($series,$row)
	{
		$strLabelFormat=$this->labelFormat($this->strLabel, $row);
		if($this->table_type!="db" || !count($this->chrt_array['customLabels']))
			$strDataSeries = $row[$this->arrDataSeries[$series]];
		else
			$strDataSeries = $row[$this->chrt_array['customLabels'][$this->arrDataSeries[$series]]];
		$id_name=($strLabelFormat!="") ? "id=\"" . $strLabelFormat . "\" name=\"" . $strLabelFormat . "\"" : "";
		$selected = $this->setFirstPoint ? "selected=\"true\"" : "";
		$str="<point ".$id_name." y=\"". $this->chart_xmlencode($strDataSeries+0). "\" size=\"" . $this->chart_xmlencode(str_replace(",",".",$row[$this->arrDataSize[$series]])+0). "\" ".$selected.">";
		$str.="<tooltip enabled=\"True\"><format>".$this->tooltipFormat($series,$row)."</format></tooltip>"."\n";
		$str.= $this->getActions( $row, $this->arrDataSeries[$series], $strLabelFormat );
		$str.="</point>"."\n";
		$this->setFirstPoint = false;
		return $str;
	}
	function write_axes()
	{
		global $useFlashChartLibrary;
		echo "<axes>"."\n";
		echo "<y_axis position=\"Normal\">"."\n";
		echo "<line thickness=\"1\" color=\"DarkColor(#".$this->chrt_array["appearance"]["color141"].")\" caps=\"None\"/>"."\n";
		echo "<major_tickmark thickness=\"1\" color=\"DarkColor(#".$this->chrt_array["appearance"]["color141"].")\" caps=\"None\" opacity=\"1\"/>"."\n";
//		echo "<scale major_interval=\"1\" mode=\"Overlay\"/>"."\n";
		
		echo "<labels enabled=\"".$this->chrt_array["appearance"]["sval"]."\" align=\"Inside\">"."\n";
		echo "<font color=\"#".$this->chrt_array["appearance"]["color61"]."\" bold=\"false\" italic=\"false\" underline=\"false\" render_as_html=\"false\">"."\n";

		echo "</font>"."\n";
		echo "</labels>"."\n";		
		echo "<title enabled=\"true\">"."\n";
		if(count($this->arrDataLabels)==1)
			echo "<text>".$this->arrDataLabels[0]."</text>"."\n";
		else
			echo "<text>".$this->y_axis_label."</text>"."\n";
		echo "<font color=\"DarkColor(#".$this->chrt_array["appearance"]["color141"].")\"/>"."\n";
		echo "</title>"."\n";
		echo "<minor_grid enabled=\"false\"/>"."\n";
		echo "<major_grid enabled=\"true\"/>"."\n";
		echo "<minor_tickmark enabled=\"false\"/>"."\n";
		
		$this->write_Grid();

		echo "</y_axis>"."\n";
		echo "<x_axis tickmarks_placement=\"Center\">"."\n";
		$scroll="false";
		if($this->chrt_array["appearance"]["cscroll"]=="true" && $this->totalRecords>$this->numRecordsToShow && $useFlashChartLibrary && $this->hasFlash)
			$scroll="true";
		echo "<zoom enabled=\"".$scroll."\" allow_drag=\"false\" visible_range=\"".$this->numRecordsToShow."\"/>"."\n";
		echo "<line thickness=\"1\" color=\"DarkColor(#".$this->chrt_array["appearance"]["color131"].")\" caps=\"None\"/>"."\n";
		echo "<title enabled=\"true\" align=\"Center\">"."\n";
		echo "<text>".$this->label2."</text>"."\n";
		echo "<font color=\"DarkColor(#".$this->chrt_array["appearance"]["color131"].")\"/>"."\n";
		echo "</title>"."\n";
		echo "<labels enabled=\"".$this->chrt_array["appearance"]["sname"]."\" display_mode=\"normal\">"."\n";
		echo "<font color=\"#".$this->chrt_array["appearance"]["color51"]."\" bold=\"false\" italic=\"false\" underline=\"false\" render_as_html=\"false\">"."\n";
		echo "</font>"."\n";
		echo "<background enabled=\"false\">"."\n";
		echo "<fill enabled=\"false\" />"."\n";
		echo "<border enabled=\"true\" />"."\n";
		echo "</background>"."\n";
		echo "</labels>"."\n";
		echo "<scale inverted=\"True\"/>"."\n";
		echo "</x_axis>"."\n";
		echo "</axes>"."\n";
	}
}
class Chart_Gauge extends Chart
{
	var $type_gauge;
	var $orientation;
	var $start_angle;
	var $sweep_angle;
	var $scale_min;
	var $scale_max;
	var $major_interval;
	var $minor_interval;
	function Chart_Gauge(&$ch_array, $param)
	{
		parent::Chart($ch_array, $param);
		$this->type_gauge=$param["type_gauge"];
		$this->orientation=$param["orientation"];
	}
	function write()
	{
		echo "<?xml version=\"1.0\" standalone=\"yes\"?>"."\n";
		echo "<anychart>"."\n";
		echo "<settings>"."\n";
		if($this->chrt_array["appearance"]["sanim"] == "true" ) 
		{
            echo "<animation enabled=\"True\" />"."\n";
        }
        else
		{
            echo "<animation enabled=\"False\" />"."\n";
        }
		echo "</settings>"."\n";
		
		echo "<templates>"."\n";
		echo "<template name=\"gaugeTemplates\">"."\n";
		echo "<gauge>"."\n";
		$this->write_templates();
		echo "</gauge>"."\n";
		echo "</template>"."\n";
		echo "</templates>"."\n";
		
		
		
		echo "<gauges>"."\n";
		echo "<gauge template=\"gaugeTemplates\">"."\n";
		
		
		$this->write_data();
		
		echo "</gauge>"."\n";
		echo "</gauges>"."\n";
		$this->writeDetailsData();
		echo "</anychart>"."\n";
	}
	function write_templates()
	{
		$strwidth=100/count($this->arrDataSeries);
		for($t=0;$t<count($this->arrDataSeries);$t++)
		{
			echo "<".$this->type_gauge."_template width=\"".($strwidth-1)."\" x=\"".($t*$strwidth+1)."\" name=\"template_".$this->chart_xmlencode($this->arrDataSeries[$t])."\">"."\n";
			$this->gauge_style();
			$this->get_frame();
			$this->get_axis($t);
			echo "<pointers>"."\n";
			$this->pointer_label($t);
			echo "</pointers>"."\n";
			echo "</".$this->type_gauge."_template>"."\n";
		}
		
	}
	function gauge_style()
	{
		if($this->type_gauge!="circular")
		{
			echo "<styles>"."\n";
			echo "<color_range_style name=\"anychart_default\" align=\"Outside\" padding=\"3\" start_size=\"15\" end_size=\"15\">"."\n";
			echo "<fill type=\"Gradient\">"."\n";
			echo "<gradient>"."\n";
			echo "<key color=\"Blend(%Color,DarkColor(%Color),0.5)\"/>"."\n";
			echo "<key color=\"%Color\"/>"."\n";
			echo "<key color=\"Blend(%Color,DarkColor(%Color),0.5)\"/>"."\n";
			echo "</gradient>"."\n";
			echo "</fill>"."\n";
			echo "<border enabled=\"true\" color=\"DarkColor(%Color)\" opacity=\"0.8\"/>"."\n";
			echo "</color_range_style>"."\n";
			echo "</styles>"."\n";
		}
	}
	function write_data()
	{
		$this->write_chart_settings();
		$this->get_data(false);
	}
	function write_chart_settings()
	{
		echo "<chart_settings>"."\n";
		echo "<title enabled=\"true\" padding=\"15\">"."\n";
		echo "<text>".$this->header."</text>"."\n";
		echo "<font color=\"#".$this->chrt_array["appearance"]["color101"]."\"/>"."\n";
		echo "</title>"."\n";
		echo "<chart_background>"."\n";
		$this->write_chart_background();
		echo "</chart_background>"."\n";
		$this->write_plot_background();
		echo "</chart_settings>"."\n";
	}
	
	/**
	 *
	 */
	function get_data($refr)
	{		
		if( $this->table_type == "project" )
		{
			global $gSettings; 
			$g_orderindexes = $gSettings->GetTableData(".orderindexes");
			$i=0;
			
			$p=strpos(strtolower($this->strSQL),"order by");
			if($p>0 && count($g_orderindexes))
			{
				$ob="ORDER BY";
				foreach($g_orderindexes as $ind=>$val)
				{
					$ob.=" ".$val[0]." ";
					if($val[1]=="ASC") 
						$ob.="DESC";
					else
						$ob.="ASC";
					if($ind+1!=count($g_orderindexes))
						$ob.=",";
				}
				
				$this->strSQL=substr($this->strSQL,0,$p).$ob;
			}
		}
		
		$qResult = $this->connection->query( $this->strSQL );

		if( $this->cipherer != null )
			$row = $this->cipherer->DecryptFetchedArray( $qResult->fetchAssoc() );
		else 
			$row = $qResult->fetchAssoc();
			
		for($i = 0; $i < count($this->arrDataSeries); $i++)
		{
			$j = 0;
			if($row) 
			{
				$j=1;
				
				if($this->table_type!="db" || !count($this->chrt_array['customLabels']))
					$strDataSeries = $row[$this->arrDataSeries[$i]];
				else
					$strDataSeries = $row[$this->chrt_array['customLabels'][$this->arrDataSeries[$i]]];
				
				$arrSer["series".$i]="<".$this->type_gauge." template=\"template_".$this->chart_xmlencode($this->arrDataSeries[$i])."\"  orientation=\"".$this->orientation."\" name=\"".$this->chart_xmlencode($this->arrDataSeries[$i])."_gauge\">"."\n";
				$arrSer["series".$i].="<pointers>"."\n";
				$arrSer["series".$i].="<pointer name=\"".$this->chart_xmlencode($this->arrDataSeries[$i])."_point\" type=\"".$this->pointer_type()."\" value=\"".$this->chart_xmlencode($strDataSeries+0)."\" color=\"#75B7E1\">"."\n";
				$arrSer["series".$i].="<animation enabled=\"true\" start_time=\"0\" duration=\"1\" interpolation_type=\"Elastic\"/>"."\n";
				$arrSer["series".$i].=$this->pointer_style();
				$arrSer["series".$i].="</pointer>"."\n";
				$arrSer["series".$i].="</pointers>"."\n";
				$arrSer["series".$i].="</".$this->type_gauge.">"."\n";
			}

			if($refr)
			{
				echo $this->arrDataSeries[$i]."\n";
				echo $this->chart_xmlencode($strDataSeries+0);
			}
			else
			{
				if($j > 0)
					echo $arrSer["series".$i];
			}

			
			if( !$refr || $i < count($this->arrDataSeries) - 1)
				echo "\n";
		}
		
		$this->connection->close();
	}
	
	function pointer_type()
	{
		if($this->type_gauge=="circular")
			return "Needle";
		else
			return "Marker";
	}
	function pointer_label($series)
	{
		if($this->type_gauge=="circular")
			$y=90;
		elseif($this->orientation=="vertical")
			$y=99;
		else
			$y=80;
		echo "<label enabled=\"".$this->chrt_array["appearance"]["sval"]."\">"."\n";
		echo "<format>".$this->chart_xmlencode($this->arrDataSeries[$series]).": ".$this->valueFormat($series,true)."</format>"."\n";
		echo "<position placement_mode=\"ByPoint\" x=\"50\" y=\"".$y."\" valign=\"Center\" halign=\"Center\"/>"."\n";
		echo "<background>"."\n";
		echo "<fill type=\"Solid\" color=\"White\" opacity=\"0.8\"/>"."\n";
		echo "<border type=\"Solid\" color=\"Black\" opacity=\"0.2\"/>"."\n";
		echo "<corners type=\"Rounded\" all=\"5\"/>"."\n";
		echo "<effects enabled=\"false\"/>"."\n";
		echo "</background>"."\n";
		echo "</label>"."\n";
	}
	function pointer_style()
	{
		if($this->type_gauge=="circular")
		{
			$res= "<needle_pointer_style base_radius=\"-50\">"."\n";
			$res.= "<cap>"."\n";
			$res.= "<background>"."\n";
			$res.= "<fill type=\"Gradient\">"."\n";
			$res.= "<gradient type=\"Linear\" angle=\"45\">"."\n";
			$res.= "<key color=\"#D3D3D3\"/>"."\n";
			$res.= "<key color=\"#6F6F6F\"/>"."\n";
			$res.= "</gradient>"."\n";
			$res.= "</fill>"."\n";
			$res.= "<border color=\"Black\" opacity=\"0.9\"/>"."\n";
			$res.= "</background>"."\n";
			$res.= "<effects enabled=\"true\">"."\n";
			$res.= "<bevel enabled=\"true\" distance=\"2\" shadow_opacity=\"0.6\" highlight_opacity=\"0.6\"/>"."\n";
			$res.= "<drop_shadow enabled=\"true\" distance=\"1.5\" blur_x=\"2\" blur_y=\"2\" opacity=\"0.4\"/>"."\n";
			$res.= "</effects>"."\n";
			$res.= "</cap>"."\n";
			$res.= "</needle_pointer_style>"."\n";
		}
		else
		{
			$res = "<marker_pointer_style align=\"Outside\" padding=\"18.5\"/>"."\n";
		}
		return $res;
	}
	function get_frame()
	{
		if($this->type_gauge=="circular")
		{
			echo "<frame type=\"Rectangular\">"."\n";
			echo "<inner_stroke enabled=\"false\"/>"."\n";
			echo "<outer_stroke enabled=\"false\"/>"."\n";
			echo "<corners type=\"Rounded\" all=\"15\"/>"."\n";
			echo "<background>"."\n";
			echo "<border enabled=\"true\" color=\"".$this->chrt_array["appearance"]["color81"]."\" opacity=\"0.5\"/>"."\n";
			echo "</background>"."\n";
			echo "</frame>"."\n";
		}
	}
	function get_axis($series)
	{
		$this->start_angle=30;
		$this->sweep_angle=300;
		$this->scale_min=$this->chrt_array["parameters"][$series]["gaugeMinValue"];
		$this->scale_max=$this->chrt_array["parameters"][$series]["gaugeMaxValue"];
		if(!is_numeric($this->scale_min))
			$this->scale_min=0;
		if(!is_numeric($this->scale_max))
			$this->scale_max=100;
		$diff=$this->scale_max-$this->scale_min;
		$slog = floor(log10($diff));
		$this->major_interval = pow(10,$slog-2);
		$muls=array(1,2,3,5,10);

		while(true)
		{
		   foreach($muls as $m)
		   {
			 if($diff/($this->major_interval*$m)<=10)
			 {
				   $this->major_interval *=$m;
				   break; 
			 }
		   }
		   if($diff/($this->major_interval)<=10)
				break;
			
			$this->major_interval*=10;
		}
		$numDec=-floor(log10($this->major_interval));
		if($numDec<0)
			$numDec=0;
		$pos="";
		if($this->type_gauge=="circular")
		{
			$pos="align=\"Inside\" padding=\"40\"";
			echo "<axis start_angle=\"".$this->start_angle."\" sweep_angle=\"".$this->sweep_angle."\">"."\n";
		}
		else		
		{
			echo "<axis>"."\n";
		}
		echo "<scale minimum=\"".$this->scale_min."\" maximum=\"".$this->scale_max."\" major_interval=\"".$this->major_interval."\"/>"."\n";
		echo "<labels enabled=\"".$this->chrt_array["appearance"]["sval"]."\">"."\n";
		echo "<format>{%Value}{numDecimals:".$numDec."}</format>"."\n";
		echo "</labels>"."\n";
		$this->get_tickmark();
		
		if(count($this->arrGaugeColor)>0 && array_key_exists($series,$this->arrGaugeColor))
		{
			echo "<color_ranges>"."\n";
		
			foreach($this->arrGaugeColor[$series] as $ind=>$val)
			{
				echo "<color_range start=\"".$val[0]."\" end=\"".$val[1]."\" color=\"".$val[2]."\" ".$pos.">"."\n";
				if($this->type_gauge=="circular")
				{
					echo "<border enabled=\"true\" color=\"Black\" opacity=\"0.2\"/>"."\n";
					echo "<fill opacity=\"0.7\"/>"."\n";
				}
				echo "</color_range>"."\n";
			}
			
			echo "</color_ranges>"."\n";
		}
		
		$this->get_scale_bar();
		$this->get_labels();
		echo "</axis>"."\n";
	}
	function get_tickmark()
	{
		if($this->type_gauge!="circular")
		{
			echo "<major_tickmark shape=\"Rectangle\" width=\"1.3\" length=\"10\" align=\"Center\" padding=\"0\">"."\n";
			echo "<fill type=\"Solid\" color=\"White\"/>"."\n";
			echo "<border enabled=\"true\" color=\"#494949\" opacity=\"0.5\"/>"."\n";
			echo "</major_tickmark>"."\n";
			echo "<minor_tickmark shape=\"Line\" align=\"Center\" length=\"7\">"."\n";
			echo "<border enabled=\"true\" color=\"#494949\" opacity=\"1\"/>"."\n";
			echo "</minor_tickmark>"."\n";
			echo "<scale_bar enabled=\"false\"/>"."\n";
			echo "<scale_line enabled=\"false\"/>"."\n";
		}
	}
	function get_scale_bar()
	{
		if($this->type_gauge=="circular")
		{
			echo "<scale_bar>"."\n";
			echo "<fill color=\"Rgb(200,200,200)\"/>"."\n";
			echo "</scale_bar>"."\n";
		}
	}
	function get_labels()
	{
		if($this->type_gauge!="circular")
		{
			echo "<labels align=\"Inside\" padding=\"1\">"."\n";
			echo "<format>".$this->valueFormat(0,true)."</format>"."\n";
			echo "</labels>"."\n";
		}
	}
}	
class Chart_Ohlc extends Chart
{
	var $ohcl_type;
	function write()
	{
		echo "<?xml version=\"1.0\" standalone=\"yes\"?>"."\n";
		echo "<anychart>"."\n";
		echo "<charts>"."\n";
		$this->write_data();
		$this->write_dps();
		$this->write_chart_settings();
		
		echo "</chart>"."\n";
		echo "</charts>"."\n";
		$this->writeDetailsData();
		echo "</anychart>"."\n";
	}
	function Chart_Ohlc(&$ch_array, $param)
	{
		parent::Chart($ch_array, $param);
		$this->ohcl_type=$param["ohcl_type"];
		$this->sleg="Series";
	}
	function write_data()
	{
		echo "<chart plot_type=\"CategorizedVertical\">"."\n";
        echo "<data>"."\n";
        $this->get_data(false);
        echo "</data>"."\n";
	}
	function get_series_type()
	{
		if($this->ohcl_type=="ohcl")
			return "OHLC";
		else
			return "Candlestick";
		
	}
	function write_dps()
	{
		
		echo "<data_plot_settings default_series_type=\"".$this->get_series_type()."\">"."\n";
		$this->get_ohcl_tooltip();
        echo "</data_plot_settings>"."\n";
        $this->ohls_styles();
	}
	function ohls_styles()
	{
		echo "<styles>"."\n";
		for ( $i=0; $i < count($this->arrOHLC_open); $i++ ) 
		{
			if($this->ohcl_type=="ohcl")
			{
				echo "<ohlc_style name=\"style".($i+1)."\">"."\n";
				$attr="line thickness=\"1\"";
			}
			else
			{
				echo "<candlestick_style name=\"style".($i+1)."\">"."\n";
				$attr="fill";
			}
			echo "<up>"."\n";
			echo "<".$attr." color=\"".$this->arrOHLC_color_up[$i]."\"/>"."\n";
			echo "</up>"."\n";
			echo "<down>"."\n";
			echo "<".$attr." color=\"".$this->arrOHLC_color_down[$i]."\"/>"."\n";
			echo "</down>"."\n";
			echo "<states>"."\n";
			echo "<hover>"."\n";
			echo "<up>"."\n";
			echo "<".$attr." color=\"LightColor(".$this->arrOHLC_color_up[$i].")\"/>"."\n";
			echo "</up>"."\n";
			echo "<down>"."\n";
			echo "<".$attr." color=\"LightColor(".$this->arrOHLC_color_down[$i].")\"/>"."\n";
			echo "</down>"."\n";
			echo "</hover>"."\n";
			echo "</states>"."\n";
			if($this->ohcl_type=="ohcl")
				echo "</ohlc_style>"."\n";
			else
				echo "</candlestick_style>"."\n";
		}
		echo "</styles>"."\n";
	}
	function get_ohcl_tooltip()
	{
		if($this->ohcl_type=="ohcl")
			echo "<ohlc_series>"."\n";
		else
			echo "<candlestick_series>"."\n";
		echo "<tooltip_settings enabled=\"True\">"."\n";
		echo "</tooltip_settings>"."\n";
		if($this->ohcl_type=="ohcl")
			echo "</ohlc_series>"."\n";
		else
			echo "</candlestick_series>"."\n";
	}
	function write_chart_settings()
	{
		global $useFlashChartLibrary;
		echo "<chart_settings>"."\n";
		echo "<title enabled=\"true\" padding=\"15\">"."\n";
		echo "<text>".$this->header."</text>"."\n";
		echo "<font color=\"#".$this->chrt_array["appearance"]["color101"]."\"/>"."\n";
		echo "</title>"."\n";
		echo "<axes>"."\n";
		echo "<y_axis>"."\n";
//		echo "<labels enabled=\"".$this->chrt_array["appearance"]["sval"]."\">"."\n";
		echo "<title>"."\n";
		if ($this->chrt_array["appearance"]["saxes"] == "true" )
			echo "<font color=\"DarkColor(".$this->arrOHLC_color[0].")\"/>"."\n";
		else
			echo "<font color=\"DarkColor(".$this->arrAxesColor[0].")\"/>"."\n";
		echo "<text>".$this->arrDataLabels[0]."</text>"."\n";
		echo "</title>"."\n";
//		echo "</labels>"."\n";
		$this->write_Logarithmic();
		echo "</y_axis>"."\n";
		echo "<x_axis>"."\n";
		$scroll="false";
		if($this->chrt_array["appearance"]["cscroll"]=="true" && $this->totalRecords>$this->numRecordsToShow && $useFlashChartLibrary && $this->hasFlash)
			$scroll="true";
		echo "<zoom enabled=\"".$scroll."\" allow_drag=\"false\" visible_range=\"".$this->numRecordsToShow."\"/>"."\n";
		echo "<title>"."\n";
		echo "<text>".$this->label2."</text>"."\n";
		echo "</title>"."\n";
		echo "</x_axis>"."\n";
		$this->write_extra();
		echo "</axes>"."\n";
		$this->write_legend();
		echo "<chart_background>"."\n";
		$this->write_chart_background();
		echo "</chart_background>"."\n";
		$this->write_plot_background();
		echo "</chart_settings>"."\n";
	}
	
	/**
	 *
	 */
	function get_data($refr)
	{
		$arrSer = array();
		
		for ( $i=0; $i < count($this->arrOHLC_open); $i++ ) 
		{
			$this->arrOHLC_color_up[$i] = $this->arrOHLC_color[$i];
			if($this->chrt_array["chart_type"]["type"]=="candlestick")
				$this->arrOHLC_color_down[$i] = $this->arrOHLC_candle[$i];
			else
				$this->arrOHLC_color_down[$i] = $this->arrOHLC_color[$i];
			$arrSer["series".$i]="<series id=\"".$this->chart_xmlencode($this->arrOHLC_open[$i])."\" name=\"".$this->chart_xmlencode($this->arrDataLabels[$i])."\" color=\"".$this->arrOHLC_color_up[$i]."\" style=\"style".($i+1)."\">"."\n";
		}
		
		$qResult = $this->connection->query( $this->strSQL );

		if( $this->cipherer != null )
			$row = $this->cipherer->DecryptFetchedArray( $qResult->fetchAssoc() );
		else 
			$row = $qResult->fetchAssoc();
		
		$j = 0;
		$recPerRow=$this->numRecordsToShow;
		while ($row) 
		{
			$j++;
			if( $this->chrt_array["appearance"]["cscroll"] == "true" )
				$recPerRow++;
				
			if ( $j > $recPerRow && $recPerRow > 0 ) 
				break;

			for ( $i=0; $i < count($this->arrOHLC_open); $i++ ) 
			{
				$arrSer["series".$i].= $this->get_point($row,$i)."\n";
			}
			
			if( $this->cipherer != null )
				$row = $this->cipherer->DecryptFetchedArray( $qResult->fetchAssoc() );
			else 
				$row = $qResult->fetchAssoc();			
		}
		
		$this->totalRecords = $j;
		for ( $i=0; $i < count($this->arrOHLC_open); $i++ ) 
		{
			if($refr)
			{
				echo $this->arrOHLC_open[$i]."\n";
				$arrSer["series".$i] = str_replace(array("\\","\n"),array("\\\\","\\n"),$arrSer["series".$i]);
			}
			
			if($j > 0)
				echo $arrSer["series".$i] . "</series>\n";
		
			if( !$refr || $i < count($this->arrOHLC_open) - 1 )
			{
				echo "\n";
			}
		}
		
		$this->connection->close();
	}
	
	function get_point($row,$series)
	{
		$strLabelFormat=$this->labelFormat($this->strLabel, $row);
		if($this->table_type!="db" || !count($this->chrt_array['customLabels']))
		{
			$strDataSeriesHigh = $row[$this->arrOHLC_high[$series]];
			$strDataSeriesLow = $row[$this->arrOHLC_low[$series]];
			$strDataSeriesOpen = $row[$this->arrOHLC_open[$series]];
			$strDataSeriesClose = $row[$this->arrOHLC_close[$series]];
		}
		else
		{
			$strDataSeriesHigh = $row[$this->chrt_array['customLabels'][$this->arrOHLC_high[$series]]];
			$strDataSeriesLow = $row[$this->chrt_array['customLabels'][$this->arrOHLC_low[$series]]];
			$strDataSeriesOpen = $row[$this->chrt_array['customLabels'][$this->arrOHLC_open[$series]]];
			$strDataSeriesClose = $row[$this->chrt_array['customLabels'][$this->arrOHLC_close[$series]]];
		}
		$str="<point name=\"".$strLabelFormat."\" ";
		$str.="high=\"".$this->chart_xmlencode($strDataSeriesHigh+0)."\"  low=\"".$this->chart_xmlencode($strDataSeriesLow+0)."\" open=\"".$this->chart_xmlencode($strDataSeriesOpen+0)."\" close=\"".$this->chart_xmlencode(str_replace(",",".",$strDataSeriesClose)+0)."\">";
		$str.="<tooltip enabled=\"True\"><format>".$this->tooltipFormat($series,$row)."</format></tooltip>"."\n";
		$str.= $this->getActions( $row , "", "" );
		$str.="</point>"."\n";
		return $str;
	}
	function write_Logarithmic()
	{
		if($this->chrt_array["appearance"]["slog"] == "true" )
		{
			echo "<scale type=\"Logarithmic\" log_base=\"10\"/>"."\n";
		}
	}
	function write_legend_tag()
	{
		echo "<legend enabled=\"true\" position=\"Bottom\" ignore_auto_item=\"true\" align=\"Spread\" padding=\"15\" height=\"20%\">"."\n";
	}
	function write_extra()
	{
		if ($this->chrt_array["appearance"]["saxes"] == "true" )
		{
			echo "<extra>"."\n";
			for ( $i=1; $i < count($this->arrOHLC_open); $i++ ) 
			{
				$position = ( $i % 2 == 0 ) ? "Normal" : "Opposite";
				echo "<y_axis name=\"".$this->chart_xmlencode($this->arrOHLC_high[$i])."\" position=\"".$position."\" enabled=\"true\">"."\n";
				echo "<title enabled=\"true\" align=\"Center\">"."\n";
				echo "<text>".$this->arrDataLabels[$i]."</text>"."\n";
				echo "<font color=\"DarkColor(".$this->arrOHLC_color_up[$i].")\"/>"."\n";
				echo "</title>"."\n";
				echo "<labels align=\"Inside\" enabled=\"".$this->chrt_array["appearance"]["sval"]."\">"."\n";
				echo "<font color=\"DarkColor(".$this->arrOHLC_color_up[$i].")\" />"."\n";
				echo "<format>".$this->valueFormat($i,true)."</format>"."\n";
				echo "</labels>"."\n";
				echo "</y_axis>"."\n";
			}
			echo "</extra>"."\n";
		}
	}
}


?>

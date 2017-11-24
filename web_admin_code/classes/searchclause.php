<?php
class SearchClause
{
	/**
	 * Array with all session data
	 *
	 * @var array
	 */
	var $_where = array();
	
	/**
	 * Name of current table, for which instance of class was created
	 *
	 * @var string
	 */
	var $tName = "";
	/**
	 * Array of fields for basic search
	 *
	 * @var array
	 */
	var $searchFieldsArr = array();
	
	var $googleLikeFields = array();
	/**
	 * Type of search
	 *
	 * @var string
	 */
	var $srchType = 'integrated';
	
	/**
	 * Session vars pref
	 *
	 * @var string
	 */
	var $sessionPrefix = "";
	/**
	 * Indicator, if used search it will be true
	 *
	 * @var bool
	 */
	var $bIsUsedSrch = false;
	
	/**
	 * An indicator showing if filters functionality is activated 
	 * @var bool
	 */
	var $filtersActivated = false;
	
	/**
	 * Indicator, if started simple or google-like search" 
	 *
	 * @var bool
	 */
	var $simpleSearchActive = false;
	/**
	 * Indicator, if started search is Advanced or from Search Panel
	 *
	 * @var bool
	 */
	var $advancedSearchActive = false;
	/**
	 * Indicator, if request have agregate fields it will be true
	 *
	 * @var bool
	 */
	var $haveAgregateFields = false;
	
	var $panelSearchFields = array();
	
	var $cipherer = null;
	
	var $searchOptions = array();
	
	var $fieldDelimiterLeft = ')';
	var $fieldDelimiterRight = '(';
	var $valueDelimiter = '~';

	var $requiredSearchFields = array();
	var $fieldsUsedForSearch = array();
	/**
	 * Local instance of EditControlsContainer. Use only for compatibility with business templates
	 * @var {object}
	 */
	var $localEditControls = null;
	/**
	 * Indicator, if used "show basic search options" field it will be true
	 * 
	 * @var boolean
	 */
	var $isShowSimpleSrchOpt = false;
	
	/**
	 * The array to store search params ('q', 'qs', 'f')
	 * extracted from REQUEST to use them for search saving
	 * @type Array
	 */
	protected $searchParams = array();
	
	public $savedSearchIsRun = false;
	
	/**
	* The associative array containing the filtered fields data  
	* @var array
	*/	
	var $filteredFields = array(); 
	
	/**
	 * @type Boolean
	 */
	protected $searchSavingEnabled = false;
	
	protected $dashTName = "";
	
	protected $dashElementName = "";
	
	/**
	 *	If the whole Dashboard (combined) search is in effect
	 *	@type bool
	 */
	protected $wholeDashboardSearch = false;
	
	protected $dashboardSearchClause = null;
	
	function SearchClause(&$params)
	{
		global $strTableName;
		$this->searchOptions["contains"] = array("option" => "Contains", "not" => false);
		$this->searchOptions["equals"] = array("option" => "Equals", "not" => false);
		$this->searchOptions["startswith"] = array("option" => "Starts with", "not" => false);
		$this->searchOptions["morethan"] = array("option" => "More than", "not" => false);
		$this->searchOptions["lessthan"] = array("option" => "Less than", "not" => false);
		$this->searchOptions["between"] = array("option" => "Between", "not" => false);
		$this->searchOptions["empty"] = array("option" => "Empty", "not" => false);
		$this->searchOptions["notcontain"] = array("option" => "Contains", "not" => true);
		$this->searchOptions["notequal"] = array("option" => "Equals", "not" => true);
		$this->searchOptions["notstartwith"] = array("option" => "Starts with", "not" => true);
		
		$this->searchOptions["notmorethan"] = array("option" => "More than", "not" => true);
		$this->searchOptions["lessequal"] = array("option" => "More than", "not" => true);
		
		$this->searchOptions["notlessthan"] = array("option" => "Less than", "not" => true); 
		$this->searchOptions["moreequal"] = array("option" => "Less than", "not" => true); 
		
		$this->searchOptions["notbetween"] = array("option" => "Between", "not" => true);
		$this->searchOptions["notempty"] = array("option" => "Empty", "not" => true);

		$this->tName = ($params['tName'] ? $params['tName'] : $strTableName);
		$this->sessionPrefix = ($params['sessionPrefix'] ? $params['sessionPrefix'] : $this->tName);
		$this->searchFieldsArr = $params['searchFieldsArr'];
		$this->cipherer = $params['cipherer'];
		$settings = new ProjectSettings($this->tName, PAGE_SEARCH);
		$this->panelSearchFields = ($params['panelSearchFields'] ? $params['panelSearchFields'] : $settings->getPanelSearchFields());
		$this->googleLikeFields = ($params['googleLikeFields'] ? $params['googleLikeFields'] : $settings->getGoogleLikeFields());
		$this->requiredSearchFields = ($params['requiredSearchFields'] ? $params['requiredSearchFields'] : $settings->getSearchRequiredFields());
		$this->isShowSimpleSrchOpt = $settings->showSimpleSearchOptions();
		$this->searchSavingEnabled = $params['searchSavingEnabled'] ? $params['searchSavingEnabled'] : false;	
		$this->dashTName = $params['dashTName'] ? $params['dashTName'] : "";	
		$this->dashElementName = $params['dashElementName'] ? $params['dashElementName'] : "";	
	}
	
	/**
	 * Build where for united search
	 * Params are common for advanced search and search panel on list
	 * Use in new projects
	 * 
	 * @protected
	 * @return string
	 */
	function buildItegratedWhere($fieldsArr, $editControls = null) 
	{
		if (!count($fieldsArr))
			return '';
		
		if(is_null($editControls))
		{
			if(is_null($this->localEditControls))
			{
				include_once getabspath("classes/controls/EditControlsContainer.php");
				$this->localEditControls = new EditControlsContainer(null, new ProjectSettings($this->tName, PAGE_SEARCH), PAGE_SEARCH, $this->cipherer);
			}
			$editControls = $this->localEditControls;
		}
			
		// get global options
		$srchCriteriaCombineType = $this->getCriteriaCombineType();
		$srchFields = &$this->_where[$this->sessionPrefix."_srchFields"];
		$sWhere = '';
		if( !$this->haveAgregateFields || !$this->advancedSearchActive )
		{
			$simpleSrch = $this->_where[$this->sessionPrefix."_simpleSrch"];
			if (trim($simpleSrch) === '%')
			{
				$simpleSrch = '['.$simpleSrch.']';
			}
			$simpleSrchOption = $this->_where[$this->sessionPrefix."simpleSrchTypeComboOpt"];
			// build where for any field contains search
			if ($simpleSrch != null && strlen($simpleSrch) || $simpleSrchOption == "Empty")
			{			
				$simpleSrchField = $this->_where[$this->sessionPrefix."simpleSrchFieldsComboOpt"];
				if ($simpleSrch != null && strlen($simpleSrchField))
				{
					if (!in_array($simpleSrchField, $fieldsArr)	)
						return "";
					$where = $editControls->getControl( $simpleSrchField, SEARCHID_SIMPLE )->getSearchWhere($simpleSrch, $simpleSrchOption, "", "");
					if($where && $this->_where[$this->sessionPrefix."simpleSrchTypeComboNot"])
					{
						$where ="not (".$where.")";
					}
					$sWhere = $where;
				}
				else 
				{
					if($this->isShowSimpleSrchOpt)
						$simpleSrchArr = array($simpleSrch);
					else
						$simpleSrchArr = $this->googleLikeParseString($simpleSrch);
					
					$resWhereArr = array();
					foreach($simpleSrchArr as $ind => $simpleSrchItem)
					{
						$sWhere = '';
						for($i = 0; $i < count($this->searchFieldsArr); $i++)
						{
							if (in_array($this->searchFieldsArr[$i], $fieldsArr) &&
								in_array($this->searchFieldsArr[$i], $this->googleLikeFields))
							{
								$control = $editControls->getControl($this->searchFieldsArr[$i], SEARCHID_ALL + $ind);
								$where = $control->getSearchWhere($simpleSrchItem, $simpleSrchOption, "", "");
								// add not 
								if(trim($where) != "" && $this->_where[$this->sessionPrefix."simpleSrchTypeComboNot"])
								{
									$where ="not (".$where.")";
								}
								if(trim($where) != "")
								{
									if($sWhere)
										$sWhere.= " or ";
									$sWhere.= $where;
								}
							}
						}
						if(count($simpleSrchArr) == 1)
							$resWhereArr[] = $sWhere;
						elseif( $sWhere )
							$resWhereArr[] = "(".$sWhere.")";
					}
					$sWhere = implode(" and ", $resWhereArr);
				}
			}
		}
		
		$resWhere = whereAdd('', $sWhere);
		// if there are fields for build advanced where
		$sWhere = '';
		if (count($srchFields))
		{
			// prepare vars
			$sWhere = $srchCriteriaCombineType=="and" ? "(1=1" : "(1=0";
			$prevSrchFieldName = '';
			
			// build where
			foreach ($srchFields as $ind => $srchF)
			{	
				if (in_array($srchF['fName'], $fieldsArr))
				{
					$control = $editControls->getControl($srchF['fName'], SEARCHID_PANEL + $ind);
					$where = $control->getSearchWhere($srchF['value1'], $srchF['opt'], $srchF['value2'], $srchF['eType']);
					
					if($where)
					{
						// add not 
						if($srchF['not'])
						{
							$where="not (".$where.")";
						}
						// and|or depends on search type
						if($srchCriteriaCombineType=="and")
						{
							// add ( if we add new clause block for same field name
							$sWhere .= ($prevSrchFieldName != $srchF['fName'] ? ") and (" : " or ").$where;
						}
						else
						{
							$sWhere .= " or ".$where;
						}
					}
					$prevSrchFieldName = $srchF['fName'];
				}
			}
			// add ) to final field block clause
			$sWhere .= ')';
		}
		$resWhere = whereAdd($resWhere, $sWhere);
		
		return $resWhere;
	}
	/**
	 * return where clause
	 *
	 * @return string
	 */	
	public function getWhere($fieldsArr, $editControls = null)
	{
		switch ($this->srchType)
		{
			case 'showall' : 
				$sWhere = '';
				break;
			case 'integrated' :
				$sWhere = $this->buildItegratedWhere($fieldsArr, $editControls);
				break;
			default:
				$sWhere = '';
		}
		return $sWhere;
	}
	
	/**
	 * Get the controls' 'INNER JOIN' clauses combined in a one space separated string
	 * @param Object editControls		An instance of the EditControlsContainer class
	 * @return String
	 */
	function getCommonJoinFromParts($editControls)
	{
		$joinParts = $this->getJoinFromPartsArrayBasingOnSimpleSearch($editControls);
			
		$srchFields = &$this->_where[$this->sessionPrefix."_srchFields"];	
		if( !$srchFields )
			$srchFields = array();
		
		// add to an array of the simple search control's clauses the searh fields control's INNER JOIN clauses
		foreach($srchFields as $ind => $srchF)
		{
			if( $srchF['opt'] != "Contains" && $srchF['opt'] != "Starts with" )
				continue;
				
			$control = $editControls->getControl($srchF['fName'], SEARCHID_PANEL + $ind);
			$clausesData = $control->getSelectColumnsAndJoinFromPart( $srchF['value1'], $srchF['opt'], false);
			$joinParts[] = $clausesData["joinFromPart"];
		}
		
		return implode(" ", $joinParts);
	}	
	
	/**
	 * Get an array of the simle search controls' 'INNER JOIN' clauses
	 * @param Object editControls		An instance of the EditControlsContainer class 
	 * @return Array
	 */
	protected function getJoinFromPartsArrayBasingOnSimpleSearch($editControls)
	{
		$joinParts = array();
		$simpleSrch = $this->_where[$this->sessionPrefix."_simpleSrch"];
		$simpleSrchOpt = $this->_where[$this->sessionPrefix."simpleSrchTypeComboOpt"];

		if( $this->haveAgregateFields && $this->advancedSearchActive || !strlen($simpleSrch) || $simpleSrchOpt != "Contains" && $simpleSrchOpt != "Starts with" )
			return array();
		
		$simleSrchField = $this->_where[$this->sessionPrefix."simpleSrchFieldsComboOpt"];
		
		if( strlen($simleSrchField) )
		{
			$control = $editControls->getControl( $simleSrchField, SEARCHID_SIMPLE );
			$clausesData = $control->getSelectColumnsAndJoinFromPart($simpleSrch, $simpleSrchOpt, false);
			return array( $clausesData["joinFromPart"] );
		}
		
		if( $this->isShowSimpleSrchOpt )
			$simpleSrchArr = array($simpleSrch);
		else
			$simpleSrchArr = $this->googleLikeParseString($simpleSrch);
		
		$joinParts = array();
		
		foreach($simpleSrchArr as $ind => $simpleSrchItem)
		{
			foreach( $this->searchFieldsArr as $sField )
			{
				if( in_array($sField, $this->googleLikeFields) )
				{
					$control = $editControls->getControl($sField, SEARCHID_ALL + $ind);
					$clausesData = $control->getSelectColumnsAndJoinFromPart($simpleSrchItem, $simpleSrchOpt,false);
					$joinParts[] = $clausesData["joinFromPart"];
				}
			}							
		}

		return $joinParts;
	}
	
	/**
	* Get the filter's WHERE clause conditions and fill 
	* the filteredFields array with fieldes' values 
	* and WHERE clauses extracted
	*/
	function processFiltersWhere( $connection )
	{
		$this->filteredFields = array();		
		$strFieldWhere = array();
		$filtersParams = postvalue('f');	

		if( !$filtersParams && isset($_SESSION[$this->sessionPrefix."_filters"]) )
			$filtersParams = $_SESSION[$this->sessionPrefix."_filters"];
			
		if(!$filtersParams || $filtersParams == 'all')
			return;
			
		$filters = $this->parseStringToArray($filtersParams, true);
		foreach($filters as $filter)
		{
			$fName = $this->searchUnEscape( $filter[0] );
			$filterType = $filter[1];
			
			$fValue = $this->searchUnEscape( $filter[2] );
			$fValues = $this->getUnescapedFValues( $fValue );
			$parentValuesAdded = count($fValues) > 1;
			$fValue = $parentValuesAdded ? $fValues[0] : $fValue;	
			$parentValues = $parentValuesAdded ? array_slice($fValues, 1) : array();
			
			$sValue = $this->searchUnEscape( $filter[3] );
			
			$where = $this->getFilterWhereByType($filterType, $fName, $fValue , $sValue, $parentValues, $connection);
			if($where)
			{
				$strFieldWhere[$fName]["where"][] = $where;
				$strFieldWhere[$fName]["value"][] = $fValue;
				if($sValue)
					$strFieldWhere[$fName]["value"][] = $sValue;
					
				$strFieldWhere[$fName]["parentValues"][] = $parentValues;
			}					
		}
		
		foreach($strFieldWhere as $fName => $fData)
		{
			$fieldWhere = implode(" or ", $fData["where"]);
			$this->filteredFields[$fName] = array("values" => $fData["value"], "where" => $fieldWhere, "parentValues" => $fData["parentValues"] );
		}
	}
	
	/**
	 * In case the filter value contains the added partent filters values ("|"-separeated)
	 * it returns an array of filter's and its parent filters values. 
	 * Otherwise the returning array contains at most the own filter's value
	 * @param String fValue
	 * @return Array
	 */
	protected function getUnescapedFValues($fValue)
	{
		$start = 0;	
		$unescapedValues = array();
		$valueLength = strlen($fValue);
	
		if( !$valueLength )
			return $unescapedValues;
			
		for($i = 0; $i < $valueLength; $i++)
		{
			if( $fValue[$i] != "|" )
				continue;
			if( $i > 0 )
			{
				if( $fValue[$i - 1] == "\\" )
					continue;
			}
			$unescapedValues[] = str_replace( '\\|', '|', substr($fValue, $start, $i - $start) );
			$start = $i + 1;
		}

		if( $start < $valueLength )
			$unescapedValues[] = str_replace( '\\|', '|', substr($fValue, $start, $valueLength - $start) );
			
		return $unescapedValues;
	}
	
	/**
	* Get filter's WHERE clause condition basing on the filter's type
	* 
	* @param String filterType		A string representing the filter's type
	* @param String fName
	* @param String fValue
	* @param String dbType
	* @return String
	*/
	function getFilterWhereByType($filterType, $fName, $fValue, $sValue, $parentValues, $connection)
	{
		$pSet = new ProjectSettings($this->tName, PAGE_SEARCH);
		
		$fullFieldName = RunnerPage::_getFieldSQLDecrypt( $fName, $connection, $pSet, $this->cipherer );

		$fieldType = $pSet->getFieldType($fName);
		$dateField = IsDateFieldType($fieldType);
		$timeField = IsTimeType($fieldType);
		
		if($dateField || $timeField) 
		{			
			include_once getabspath("classes/controls/FilterControl.php");	
			include_once getabspath("classes/controls/FilterIntervalSlider.php");
			include_once getabspath("classes/controls/FilterIntervalDateSlider.php");
		}
		
		switch($filterType)
		{
			case 'interval':
				$intervalData = $pSet->getFilterIntervalDatabyIndex($fName, $fValue);
				if( !count($intervalData) )
					return "";
				
				include_once getabspath("classes/controls/FilterControl.php");	
				include_once getabspath("classes/controls/FilterIntervalList.php");
				return FilterIntervalList::getIntervalFilterWhere($fName, $intervalData, $pSet, $this->cipherer, $this->tName, $connection);

			case 'equals':
				if( !count($parentValues) )
					return $fullFieldName."=".$this->cipherer->MakeDBValue($fName, $fValue, "", true);
				
				$wheres = array();
				$wheres[] = $fullFieldName."=".$this->cipherer->MakeDBValue($fName, $fValue, "", true);
				$parentFiltersNames = $pSet->getParentFiltersNames($fName);
				
				foreach( $parentFiltersNames as $key => $parentName )
				{
					$wheres[] = RunnerPage::_getFieldSQLDecrypt($parentName, $connection, $pSet, $this->cipherer)."=".$this->cipherer->MakeDBValue($parentName, $parentValues[$key], "", true);
				}
				
				return "(".implode(" AND ", $wheres).")";
				
			case 'checked':
				if($fValue != "on" && $fValue != "off")
					return "";
					
				$bNeedQuotes = NeedQuotes($fieldType);
				
				include_once getabspath("classes/controls/Control.php");				
				include_once getabspath("classes/controls/CheckboxField.php");	
				return CheckboxField::constructFieldWhere($fullFieldName, $bNeedQuotes, $fValue == "on", $pSet->getFieldType($fName), $connection->dbType);
			
			case 'slider':
				if($dateField)
					return FilterIntervalDateSlider::getDateSliderWhere($fName, $pSet, $this->cipherer, $this->tName, $fValue, $sValue, $filterType, $fullFieldName);

				if($timeField)
				{
					include_once getabspath("classes/controls/FilterIntervalTimeSlider.php");
					return FilterIntervalTimeSlider::getTimeSliderWhere($fName, $pSet, $this->cipherer, $this->tName, $fValue, $sValue, $filterType, $fullFieldName);
				}
					
				return $this->cipherer->MakeDBValue($fName, $fValue, "", true)."<=".$fullFieldName." AND ".$fullFieldName."<=".$this->cipherer->MakeDBValue($fName, $sValue, "", true);
				
			case 'moreequal':
				if($dateField)
					return FilterIntervalDateSlider::getDateSliderWhere($fName, $pSet, $this->cipherer, $this->tName, $fValue, $sValue, $filterType, $fullFieldName);

				if($timeField)
				{
					include_once getabspath("classes/controls/FilterIntervalTimeSlider.php");
					return FilterIntervalTimeSlider::getTimeSliderWhere($fName, $pSet, $this->cipherer, $this->tName, $fValue, $sValue, $filterType, $fullFieldName);
				}	
				return $this->cipherer->MakeDBValue($fName, $fValue, "", true)."<=".$fullFieldName;
				
			case 'lessequal':
				if($dateField)
					return FilterIntervalDateSlider::getDateSliderWhere($fName, $pSet, $this->cipherer, $this->tName, $fValue, $sValue, $filterType, $fullFieldName);

				if($timeField)
				{
					include_once getabspath("classes/controls/FilterIntervalTimeSlider.php");
					return FilterIntervalTimeSlider::getTimeSliderWhere($fName, $pSet, $this->cipherer, $this->tName, $fValue, $sValue, $filterType, $fullFieldName);
				}
				return $fullFieldName."<=".$this->cipherer->MakeDBValue($fName, $fValue, "", true);
			
			default: 
				return "";
		}
	}
	
	/**
	 * Parse form with union search REQUEST (for new versions: 6.2 and newest)
	 * Params are common for advanced search and search panel on list
	 * Use in new projects
	 * 
	 * @protected
	 * @return string
	 */
	function parseItegratedRequest() 
	{
		$_SESSION[$this->sessionPrefix."_qs"] = postvalue('qs');
		$_SESSION[$this->sessionPrefix."_q"] = postvalue('q');

		if(postvalue('qs') == "" && postvalue('q') == "" && !$this->wholeDashboardSearch)
			return $this->parseItegratedRequestOld();
		
		global $suggestAllContent;
		// parse global options
		
		$this->fieldsUsedForSearch = array();
		
		$this->_where[$this->sessionPrefix."_simpleSrch"] = '';	
		$this->simpleSearchActive = false;
		$this->_where[$this->sessionPrefix."simpleSrchTypeComboOpt"] = $suggestAllContent ? "Contains" : "Starts with";
		$this->_where[$this->sessionPrefix."simpleSrchTypeComboNot"] = false;
		$this->_where[$this->sessionPrefix."simpleSrchFieldsComboOpt"] = '';

		$tempArr = $this->parseStringToArray(postvalue('qs'));
		$simpleQueryArr = $tempArr[0];
		if ($this->wholeDashboardSearch) 
			$simpleQueryArr = $this->getSimpleSearchFromDashboard();
		
		$this->_where[$this->sessionPrefix."_simpleSrch"] = $this->searchUnEscape($simpleQueryArr[0]);	
		$this->simpleSearchActive = $simpleQueryArr[0] != '';
		if($this->simpleSearchActive && $this->wholeDashboardSearch)
		{
			$this->googleLikeFields = $this->getGoogleLikeFieldsFromDashboard();
		}
		if(isset($this->searchOptions[$this->getArrayValueByIndex($simpleQueryArr, 2, true)]))
		{
			$simpleSrchTypeComboNot = $this->searchOptions[$simpleQueryArr[2]]["not"];
			$this->_where[$this->sessionPrefix."simpleSrchTypeComboOpt"] = $this->searchOptions[$simpleQueryArr[2]]["option"];
			if (!strlen($this->_where[$this->sessionPrefix."simpleSrchTypeComboOpt"]))
			{
				$this->_where[$this->sessionPrefix."simpleSrchTypeComboOpt"] = $suggestAllContent ? "Contains" : "Starts with";
			}
		}
		$fieldName = trim($this->getArrayValueByIndex($simpleQueryArr, 1, true));
		$this->_where[$this->sessionPrefix."simpleSrchFieldsComboOpt"] = $fieldName;
		if($fieldName)
		{
			$this->fieldsUsedForSearch[$fieldName] = true;
		}	
		
		$srchCriteriaCombineType=postvalue("criteria");
		if ($this->wholeDashboardSearch) 
			$srchCriteriaCombineType=$this->getCriteriaFromDashboard();
		
		if(!$srchCriteriaCombineType)
			$srchCriteriaCombineType="and";
		$this->_where[$this->sessionPrefix."_srchCriteriaCombineType"] = $srchCriteriaCombineType;
		$_SESSION[$this->sessionPrefix."_criteriaSearch"] = $this->getCriteriaCombineType();
		// prepare vars
		$this->_where[$this->sessionPrefix."_srchFields"] = array();
		// scan all srch fields
		$this->advancedSearchActive = false;
		$pSet = new ProjectSettings($this->tName, PAGE_SEARCH);
		
		$searchFieldsArr = $this->parseStringToArray(postvalue('q'), true);
		if ($this->wholeDashboardSearch) 
			$searchFieldsArr=$this->getSearchFieldsFromDashboard();
		
		foreach ($searchFieldsArr as $searchItemArr) 
		{
			if( count($searchItemArr) < 2 )
				continue;

			$fName = $this->searchUnEscape($searchItemArr[0]);
			if (false == in_array($fName, $this->searchFieldsArr))
				continue;

			$this->advancedSearchActive = true;

			$srchF = array();
			$srchF['fName'] = $fName;
			$srchF['eType'] = $this->getArrayValueByIndex($searchItemArr, 3);
			$srchF['value1'] = $this->getArrayValueByIndex($searchItemArr, 2, true);
			$opt = $this->getArrayValueByIndex($searchItemArr, 1);
			$srchF['not'] = false;
			if(isset($this->searchOptions[$opt]))
			{
				$srchF['not'] = $this->searchOptions[$opt]["not"]; 
				$srchF['opt'] =  $this->searchOptions[$opt]["option"];
			}
			else 
			{
				$srchF['opt'] = $this->getDefaultSearchTypeOption($fName, $pSet);
			}
			$srchF['value2'] = $this->getArrayValueByIndex($searchItemArr, 4, true);	
			$this->_where[$this->sessionPrefix."_srchFields"][] = $srchF;
			$this->fieldsUsedForSearch[$fName] = true;
		}
		
		// process srch panel attrs, better then use coockies. 
		$this->_where[$this->sessionPrefix."_srchOptShowStatus"]= postvalue('srchOptShowStatus')==='1';// || count($this->_where[$this->sessionPrefix."_srchFields"])>0;
		$this->_where[$this->sessionPrefix."_ctrlTypeComboStatus"]= postvalue('ctrlTypeComboStatus')==='1';
		$this->_where[$this->sessionPrefix."srchWinShowStatus"]= postvalue('srchWinShowStatus')==='1';
		
	}
	
	/**
	 * Get criteria from dashboard search clause
	 * @return String
	 */
	function getCriteriaFromDashboard()
	{
		if($this->dashboardSearchClause)
			return $this->dashboardSearchClause->_where[$this->dashTName.'_srchCriteriaCombineType'];
		else
			return null;
	}
	
	/**
	 * Get simple search from dashboard search clause
	 * @return Array
	 */
	function getSimpleSearchFromDashboard()
	{
		if($this->dashboardSearchClause)
			return array(0 => $this->dashboardSearchClause->_where[$this->dashTName.'_simpleSrch']);
		else
			return array(0 => null);
	}
	
	/**
	 * Get search fields from dashboard search clause
	 * @return Array
	 */
	function getSearchFieldsFromDashboard()
	{
		$result = array();
		if($this->dashboardSearchClause)
			$dashSearchFieldsSession = $this->dashboardSearchClause->_where[$this->dashTName.'_srchFields'];
		else
			$dashSearchFieldsSession = null;
		
		if ($dashSearchFieldsSession)
		{
			$dashSettings = new ProjectSettings($this->dashTName, PAGE_DASHBOARD);
			$dashSearchFields = $dashSettings->getDashboardSearchFields();
			
			foreach ($dashSearchFieldsSession as $i => $data)
			{
				foreach ($dashSearchFields[$data['fName']] as $j => $fData)
				{
					if ($fData['table'] == $this->tName)
					{
						$dashSearchField = $fData['field'];
					}
					else
					{
						continue;                            
					}
					
					$resutlData = array();
					$resutlData[0] = $dashSearchField;
					foreach ($this->searchOptions as $opt => $optData)
					{
						if ($data['opt'] == $optData['option'])
						{
							$resutlData[1] = $opt;
							break;
						}
					}
					$resutlData[2] = $data['value1'];
					if ($data['eType'])
					{
						$resutlData[3] = $data['eType'];
					}
					if ($data['value2'])
					{
						$resutlData[4] = $data['value2'];
					}
					
					$result[] = $resutlData;
				}
			}
		}
		
		return $result;
	}
	
	/**
	 * Get google like fields from dashboard
	 * @return Array
	 */
	function getGoogleLikeFieldsFromDashboard()
	{
		$result = array();
		$dashSettings = new ProjectSettings($this->dashTName, PAGE_DASHBOARD);
		$dashGoogleLikeFields = $dashSettings->getGoogleLikeFields();
		$dashSearchFields = $dashSettings->getDashboardSearchFields();
		
		foreach ($dashGoogleLikeFields as $i => $field)
		{
			foreach ($dashSearchFields[$field] as $j => $data)
			{
				if ($data['table'] != $this->tName)
				{
					continue;
				}
				
				$result[] = $data['field'];
			}
		}

		return $result;
	}
	
	/**
	 * @param String inputString
	 * @return String
	 */
	function searchUnEscape($inputString)
	{
		return str_replace("\\\\", "\\", 
			str_replace("\\".$this->valueDelimiter, $this->valueDelimiter, 
				str_replace("\\".$this->fieldDelimiterLeft.$this->fieldDelimiterRight,
					$this->fieldDelimiterLeft.$this->fieldDelimiterRight, $inputString)));
	}
	
	/**
	 * @param String inputString
     * @param Boolean advanced	 
	 * @return Array
	 */
	function parseStringToArray($inputString, $advanced = false)
	{
		if(0 == strlen($inputString))
			return array();
		$result = array();
		$valuesArray = array();
		$startPos = 0;
		if($advanced)
			$inputString = substr($inputString, 1, strlen($inputString) - 2);
		$strLength = strlen($inputString);
		for($i = 0; $i < $strLength; $i++)
		{
			if($inputString[$i] == $this->valueDelimiter)
				if($this->isDelimiter($inputString, $startPos, $i))
				{
					$valuesArray[] = substr($inputString, $startPos, $i - $startPos);
					$startPos = $i + 1;
				}
			if($i == $strLength - 1 || $inputString[$i] == $this->fieldDelimiterLeft)
				if($i == $strLength - 1 || $this->isDelimiter($inputString, $startPos, $i, true))
				{
					$valuesArray[] = substr($inputString, $startPos, $i - $startPos + ($i == $strLength - 1 ? 1 : 0));
					$result[] = $valuesArray;
					$valuesArray = array();
					$startPos = $i + 2;
					$i++;
				}
		}
		return $result;
	}
	
	/**
	 * @param &String inputString
	 * @param Number startPos
	 * @param Number currentPos
	 * @param Boolean isFieldDelimiter (optional)
	 * @return Boolean
	 */
	function isDelimiter(&$inputString, $startPos, $currentPos, $isFieldDelimiter = false)
	{
		$backSlahesCount = 0;
		for($i = $currentPos - 1; $i >= $startPos; $i--)
		{
			if($inputString[$i] != '\\')
				break;
			$backSlahesCount++;
		}
		$result = $backSlahesCount == 0 || $backSlahesCount % 2 == 0;
		if($result && $isFieldDelimiter && strlen($inputString) > $currentPos + 1)
		{
			return $inputString[$currentPos + 1] == $this->fieldDelimiterRight;
		}
		return $result; 
	}
	
	function getArrayValueByIndex(&$arr, $index, $isEncoded = false)
	{
		$result = "";
		if(isset($arr[$index]))
		{
			$result = $arr[$index];
			if($isEncoded)
				$result = $this->searchUnEscape($result);
		}
		return $result;
	}
	function getDefaultSearchTypeOption($fName, $pSet) 
	{
		$fType = $pSet->getEditFormat($fName);
		$option = "Equals";
		if($fType == EDIT_FORMAT_LOOKUP_WIZARD)
		{
			if ($pSet->multiSelect($fName))
				$option = "Contains";	
		}
		elseif ($fType == EDIT_FORMAT_TEXT_FIELD || $fType == EDIT_FORMAT_TEXT_AREA || $fType == EDIT_FORMAT_PASSWORD 
					|| $fType == EDIT_FORMAT_HIDDEN || $fType == EDIT_FORMAT_READONLY)
		{
			if(!$this->cipherer->isFieldPHPEncrypted($fName))
				$option = "Contains";
		}
		
		return $option;
	}
	/**
	 * Parse form with union search REQUEST (for old versions: 6.1 and older)
	 * Params are common for advanced search and search panel on list
	 * Use in new projects
	 * 
	 * @protected
	 * @return string
	 */
	function parseItegratedRequestOld() 
	{
		global $suggestAllContent;
		// parse global options
		$this->_where[$this->sessionPrefix."_simpleSrch"] = trim(postvalue("ctlSearchFor"));	
		$this->simpleSearchActive = $this->_where[$this->sessionPrefix."_simpleSrch"] != '';
		$this->_where[$this->sessionPrefix."simpleSrchTypeComboOpt"] = trim(postvalue("simpleSrchTypeComboOpt"));
		if (!strlen($this->_where[$this->sessionPrefix."simpleSrchTypeComboOpt"]))
		{
			$this->_where[$this->sessionPrefix."simpleSrchTypeComboOpt"] = $suggestAllContent ? "Contains" : "Starts with";
		}
		$this->_where[$this->sessionPrefix."simpleSrchTypeComboNot"] = trim(postvalue("simpleSrchTypeComboNot")) != '';
		$this->_where[$this->sessionPrefix."simpleSrchFieldsComboOpt"] = trim(postvalue("simpleSrchFieldsComboOpt"));
		
		
		$srchCriteriaCombineType = postvalue("criteria");
		if(!$srchCriteriaCombineType)
			$srchCriteriaCombineType="and";
		$_SESSION[$this->sessionPrefix."_criteria"] = $srchCriteriaCombineType;
		$this->_where[$this->sessionPrefix."_srchCriteriaCombineType"] = $srchCriteriaCombineType;
		$_SESSION[$this->sessionPrefix."_criteriaSearch"] = $this->getCriteriaCombineType();
		// prepare vars
		$this->_where[$this->sessionPrefix."_srchFields"] = array();
		$j = 1;
		// scan all srch fields
		$this->advancedSearchActive = false;
		while ($fName = postvalue('field'.$j)) 
		{
			// check if field in request exist in searchFieldsArray, for prevent SQL injection
			if (in_array($fName, $this->searchFieldsArr))
			{
				$this->advancedSearchActive = true;
				$srchF = array();
				$srchF['fName'] = trim($fName);
				$srchF['eType'] = trim(postvalue('type'.$j));
				$srchF['value1'] = trim(postvalue('value'.$j.'1'));
				$srchF['opt'] = (postvalue('option'.$j) ? postvalue('option'.$j) : 'Contains');
				$srchF['value2'] = trim(postvalue('value'.$j.'2'));	
				$srchF['not'] = postvalue('not'.$j) == 'on';
				$this->_where[$this->sessionPrefix."_srchFields"][] = $srchF;
			}
			$j++;
		}	
		
		// process srch panel attrs, better then use coockies. 
		$this->_where[$this->sessionPrefix."_srchOptShowStatus"]= postvalue('srchOptShowStatus')==='1';// || count($this->_where[$this->sessionPrefix."_srchFields"])>0;
		$this->_where[$this->sessionPrefix."_ctrlTypeComboStatus"]= postvalue('ctrlTypeComboStatus')==='1';
		$this->_where[$this->sessionPrefix."srchWinShowStatus"]= postvalue('srchWinShowStatus')==='1';
	}
	
	/**
	 *
	 */
	protected function removeSessionSearchVariables()
	{
		if ( @$_SESSION[$this->sessionPrefix."_qs"] )
			unset($_SESSION[$this->sessionPrefix."_qs"]);
		
		if ( @$_SESSION[$this->sessionPrefix."_q"] )
			unset($_SESSION[$this->sessionPrefix."_q"]);
		
		if ( @$_SESSION[$this->sessionPrefix."_criteriaSearch"] )
			unset($_SESSION[$this->sessionPrefix."_criteriaSearch"]);
	}
	
	/**
	 * Parse REQUEST
	 */
	public function parseRequest()
	{
		$this->wholeDashboardSearch = false;
		
		//set session if show all records
		if(@$_REQUEST["a"] == "showall")
		{
			$this->_where[$this->sessionPrefix."_search"] = 0;
			$this->srchType = 'showall';
			$this->bIsUsedSrch = false;
			$this->clearSearch();
			$_SESSION[$this->sessionPrefix."_pagenumber"] = 1;

			$this->removeSessionSearchVariables();

			$this->simpleSearchActive = false;
		}
		else if( isset($_REQUEST["q"]) || isset($_REQUEST["qs"]) || @$_REQUEST["a"] == "integrated" || @$_REQUEST["f"] )
		{
			$this->srchType = 'integrated';
			$this->parseItegratedRequest();
			$this->bIsUsedSrch = isset($_REQUEST["q"]) && $_REQUEST["q"] !== "" || isset($_REQUEST["qs"]) && $_REQUEST["qs"] !== "";
			$_SESSION[$this->sessionPrefix."_pagenumber"] = 1;
		}
		else if( $this->dashTName && isset($_SESSION[$this->dashTName.'_advsearch']) )
		{
			$this->dashboardSearchClause = SearchClause::UnserializeObject($_SESSION[$this->dashTName.'_advsearch']);
			$this->wholeDashboardSearch = $this->dashboardSearchClause->bIsUsedSrch;
			if( $this->wholeDashboardSearch ) 
			{
				$this->srchType = 'integrated';
				$this->parseItegratedRequest();
				$this->bIsUsedSrch = true;
			} 
			else if( $this->dashboardSearchClause->srchType == 'showall' )
			{
				$this->_where[$this->sessionPrefix."_search"] = 0;
				$this->srchType = 'showall';
				$this->bIsUsedSrch = false;
				$this->clearSearch();
				$this->simpleSearchActive = false;

				$this->removeSessionSearchVariables();		
			}
		}
				
		//set session for filters
		if( @$_REQUEST["f"] ) 
			$_SESSION[$this->sessionPrefix."_filters"] = $_REQUEST["f"];		
		$this->filtersActivated = isset( $_SESSION[$this->sessionPrefix."_filters"] ) && $_SESSION[$this->sessionPrefix."_filters"] != 'all';
		
		if( $this->searchSavingEnabled ) 
		{
			if( isset($_REQUEST["savedSearch"]) )
				$this->savedSearchIsRun = true;
			else if( ( $this->bIsUsedSrch || $this->filtersActivated ) && !$this->searchHasTheSameSearchParams() || $this->srchType == 'showall' )
				$this->savedSearchIsRun = false;
		}
	}
	
	/** 
	 * Fill the 'searchParams' array with extracted  from REQUEST's 'q', 'qs' 
	 * and 'f' params to use them then for a search saving process. 
	 */
	public function storeSearchParamsForLogging()
	{
		if( !$this->searchSavingEnabled ) 
			return;
				
		if( !isset($_REQUEST["saveSearch"]) && !isset($_REQUEST["deleteSearch"]) )
		{
			if( $this->srchType == 'showall' )
				// reset the simple search and search panel params 
				$this->searchParams = array( "f" => $this->searchParams["f"] );
			else if( !@$_REQUEST["goto"] && !@$_REQUEST["orderby"] && !@$_REQUEST["pagesize"] )
				// reset all stored params
				$this->searchParams = array();
		}
		
		if( isset( $_REQUEST["q"] ) )
		{
			$this->searchParams["q"] = $_REQUEST["q"];
			$this->searchParams["criteria"] = @$_REQUEST["criteria"];
		}
		
		if( isset( $_REQUEST["qs"] ) )
			$this->searchParams["qs"] = $_REQUEST["qs"];
		
		if( isset( $_REQUEST["f"] ) )
			$this->searchParams["f"] = $_REQUEST["f"];
	}
	
	/**
	 * Check if the current REQUEST search params are equal to stored save search params.
	 * When the pagination or sorting is activated and there are some stored search params
	 * the current and stored search params are deemed the same
	 * @return Boolean
	 */
	public function searchHasTheSameSearchParams() 
	{
		if( @$_REQUEST["goto"] || @$_REQUEST["orderby"] || @$_REQUEST["pagesize"] )
			return true;
		
		if( !count($this->searchParams) ) 
			return false;
			
		if( @$_REQUEST["q"] != $this->searchParams["q"] || @$_REQUEST["qs"] != $this->searchParams["qs"] || @$_REQUEST["f"] != $this->searchParams["f"])
			return false;

		return true;
	}
	
	/**
	 * @return Array
	 */
	public function getSearchParamsForSaving()
	{		
		return $this->searchParams;
	}
	
	/**
	 * Clears search params
	 */
	function clearSearch()
	{
		$this->_where[$this->sessionPrefix."_simpleSrch"] = '';
		$this->_where[$this->sessionPrefix."_srchCriteriaCombineType"] = "and";
		$this->_where[$this->sessionPrefix."simpleSrchTypeComboOpt"] = "Contains";
		$this->_where[$this->sessionPrefix."simpleSrchTypeComboNot"] = false;
		$this->_where[$this->sessionPrefix."simpleSrchFieldsComboOpt"] = '';
		// prepare vars
		$this->_where[$this->sessionPrefix."_srchFields"] = array();
		// process srch panel attrs, better then use coockies. 
		$this->_where[$this->sessionPrefix."_srchOptShowStatus"]= false;
		$this->_where[$this->sessionPrefix."_ctrlTypeComboStatus"]= false;
		$this->_where[$this->sessionPrefix."srchWinShowStatus"]= false;
		
		$this->fieldsUsedForSearch = array();
	}
	
	/**
	* deprecated
	* applyWhere - adds search Where and Having expressions to sql string presented as array
	*/
	function applyWhere(&$sql, $simpleFieldsArr, $aggFieldsArr, $editControls)
	{
		if (!count($simpleFieldsArr) && !count($aggFieldsArr)){
			return $sql;
		}
		
		$searchWhereClause = $this->getWhere($simpleFieldsArr, $editControls);
		$searchHavingClause = $this->getWhere($aggFieldsArr, $editControls);
		
		if($searchWhereClause)
		{
			if($sql[2])
			{
				$sql[2] = '('.$sql[2].') AND ';
			}
			
			$sql[2] .= '('.$searchWhereClause.') ';
		}
		
		if($searchHavingClause)
		{
			if($sql[4])
			{
				$sql[4] = '('.$sql[4].') AND ';
			}
			
			$sql[4] .= '('.$searchHavingClause.') ';
		}
		
		return $sql;
	}
	/**
	 * deprecated
	 *
	 * @return unknown
	 */
	function getTable()
	{
		return $this->_where;
	}
	
	function getSearchCtrlParams($fName)
	{
		$resArr = array();
		if ($this->_where[$this->sessionPrefix."_srchFields"])
		{
			foreach ($this->_where[$this->sessionPrefix."_srchFields"] as $srchField)
			{
				if (strtolower($srchField['fName']) == strtolower($fName))
				{
					$tField = $srchField;
					$tField["value1"] = prepare_for_db($tField["fName"], $tField["value1"], $tField["eType"], "", $this->tName);
					$tField["value2"] = prepare_for_db($tField["fName"], $tField["value2"], $tField["eType"], "", $this->tName);
					$resArr[] = $tField;					
				}
			}
		}
		return $resArr;
	}
	
	function getUsedCtrlsCount() {
		if ($this->_where[$this->sessionPrefix."_srchFields"]){
			return count($this->_where[$this->sessionPrefix."_srchFields"]);
		}else{
			return 0;
		}
	}
	/**
	 * Global search params: use and|or, srchType panel|adv and simple search value
	 *
	 * @return array
	 */	
	function getSearchGlobalParams() {
		return array('simpleSrch'=>$this->_where[$this->sessionPrefix."_simpleSrch"], 
					 'srchTypeRadio'=>$this->getCriteriaCombineType(),
					 'srchType'=>$this->srchType,
					 'simpleSrchTypeComboOpt' => $this->_where[$this->sessionPrefix."simpleSrchTypeComboOpt"],
					 'simpleSrchTypeComboNot' => $this->_where[$this->sessionPrefix."simpleSrchTypeComboNot"],
					 'simpleSrchFieldsComboOpt' => $this->_where[$this->sessionPrefix."simpleSrchFieldsComboOpt"]
		);
	}
	/**
	 * Search panel status indicators array. Open|closed etc
	 *
	 * @return array
	 */
	function getSrchPanelAttrs(){
		return array('srchOptShowStatus' => ($this->_where[$this->sessionPrefix."_srchOptShowStatus"] || count($this->panelSearchFields)),
					 'ctrlTypeComboStatus' => $this->_where[$this->sessionPrefix."_ctrlTypeComboStatus"],
					 'srchWinShowStatus' => $this->_where[$this->sessionPrefix."srchWinShowStatus"]
		);
	}
	/**
	 * Returns indicator is search was init
	 *
	 * @return unknown
	 */
	function isUsedSrch() 
	{
		return $this->bIsUsedSrch;
	}
	
	/**
	 * Returns indicator is show button 'Show All'
	 *
	 * @return unknown
	 */
	function isShowAll() 
	{
		return $this->bIsUsedSrch;
	}
	
	/**
	 * Check if search functionality is activated
	 *
     * @return Boolean
	 */
	function isSearchFunctionalityActivated()
	{
		return $this->bIsUsedSrch || $this->filtersActivated;
	}
	
	/**
	* Checks whether required search fields are used for the searching or not 
	*
	* @return {boolean}
	*/
	function isRequiredSearchRunning() 
	{
		if(!$this->bIsUsedSrch)
		{
			//the search isn't run
			return false;
		}

		foreach($this->requiredSearchFields as $fName)
		{
			if(!$this->fieldsUsedForSearch[$fName])
			{
				//a required search field isn't involved in the current search
				return false;
			}
		}

		return true;
	}
	
	/**
	 * Forms an array containing the search words and options
	 *
	 * @param String fname
	 * @param Array lookupParams
	 * @return array | false
	 */	
	function getSearchToHighlight($fname, $lookupParams = array()) 
	{
		// if not in search fields array
		if (!in_array($fname, $this->searchFieldsArr))
			return false;

		$options = array();
		
		//simple search processing
		$simpleSearch['fname'] = $this->_where[$this->sessionPrefix."simpleSrchFieldsComboOpt"];
		$opt = $this->_where[$this->sessionPrefix."simpleSrchTypeComboOpt"];
		
		if($this->isShowSimpleSrchOpt)
			$simpleSearch['value'] = array($this->_where[$this->sessionPrefix."_simpleSrch"]);
		else
			$simpleSearch['value'] = $this->googleLikeParseString($this->_where[$this->sessionPrefix."_simpleSrch"]);
			
		if( isset($simpleSearch['value']) && count($simpleSearch['value']) && (!$simpleSearch['fname'] || $simpleSearch['fname'] == $fname) )
		{
			foreach($simpleSearch['value'] as $simpleSearchValue)
			{
				if( strlen( trim($simpleSearchValue) ) )
					$options[$opt][$fname][] = $simpleSearchValue;	
			}
		}

		//integrated search processing
		$srchFields = $this->_where[$this->sessionPrefix."_srchFields"];	
		if( !$srchFields )
			$srchFields = array();
		
		$multiselect = $lookupParams["multiselect"];
		$needLookupProcessing = $lookupParams["needLookupProcessing"];
		
		foreach($srchFields as $srchFieldData)
		{
			if($srchFieldData['fName'] != $fname || $srchFieldData['not'])
			{
				continue;
			}
			
			$opt = $srchFieldData['opt'];
			if($opt != "Contains" && $opt != "Equals" && $opt != "Starts with")
			{
				continue;
			}
			

			if($needLookupProcessing && $opt == "Equals")
			{
				$options[$opt][$srchFieldData['fName']][] = implode(",", splitvalues( $srchFieldData['value1'] ));
				continue;
			}
			
			if(!$multiselect ||  $opt != "Contains")
			{
				$options[$opt][$srchFieldData['fName']][] = $srchFieldData['value1'];
				continue;
			}

			$values = splitvalues( $srchFieldData['value1'] );
			foreach($values as $value)
			{
				$options[$opt][$srchFieldData['fName']][] = $value;
			}				
		}		
		
		if($options['Equals'][$fname])
			return array("searchWords" => $options['Equals'][$fname], "option" => 'Equals');
				
		if($options['Starts with'][$fname])
			return array("searchWords" => $options['Starts with'][$fname], "option" => 'Starts with');
		 
		if($options['Contains'][$fname])
			return array("searchWords" => $options['Contains'][$fname], "option" => 'Contains');
		
		return false;
	}
	
	/**
	 * Forms an array containing the actual search word and option, if there is at least one word to highlight.
	 *
	 * @param String fname
	 * @param String value
	 * @param Boolean encoded	It indicates if runner_htmlspecialchars should be applied to the search words
	 * @param Array lookupParams	It contains the following propeties:
	 * 		String linkFieldValue			The value of the link field if the link field differs from the displayed field
	 * 	 	Boolean multiselect				An indicator showing if the lookup is multiselect
	 * 		Boolean needLookupProcessing	An indicator showing if the lookup is tablebased, multiselect, with
	 *										the same link and displayed fields			
	 * @return Array | false
	 */	
	public function getSearchHighlightingData($fname, $value, $encoded, $lookupParams)
	{
		global $useUTF8;
		
		$searchData = $this->getSearchToHighlight($fname, $lookupParams);
		if(!$searchData)
		{
			return false;
		}
		
		$flags = $useUTF8 ? "iu" : "i";
		$searchWordArr = array();		
		$searchOpt = $searchData['option'];

		
		foreach($searchData['searchWords'] as $searchWord)
		{
			$curSearchWord = $searchWord;
			
			//linkFieldValue and linkFieldValue params are set for lookup contols with distinct Link and Displayed fields only
			//originLinkValue param is set for multiselet lookups only
			if($searchOpt == 'Contains' &&  $lookupParams["originLinkValue"] == $searchWord || $searchOpt == 'Equals' && $lookupParams["linkFieldValue"] == $searchWord )
			{
				return array("searchWords" => array($value), "searchOpt" => $searchData['option']);
			}
			
			if($encoded)
			{
				$curSearchWord = runner_htmlspecialchars($curSearchWord);
			}
			
			$pattern = '/'.preg_quote($curSearchWord,"/").'/'.$flags;
			if($searchOpt == 'Starts with')
			{
				$pattern = '/^'.preg_quote($curSearchWord,"/").'/'.$flags;
			}
			
			$isMatched = preg_match($pattern, $value, $matches);
			if( $isMatched && ( $searchOpt != 'Equals' ||  $value == $matches[0] ) ) 
			{
				//get the actual search word contained in the $value string
				$curSearchWord = $matches[0];
				$searchWordArr[] = $curSearchWord;				
			}
		}
		
		if(count($searchWordArr))
		{
			return array("searchWords" => $searchWordArr, "searchOpt" => $searchOpt);
		}
		
		return false;		
	}

	/**
	 * Google-like parse search string
	 * Input: "a b" c d "e f"
	 * Output: array("a b", "c", "d", "e f")
	 *
	 * @param string $str search string
	 * @return array
	 */
	function googleLikeParseString($str)
	{
		$ret = array();
		$matches = array();
		if(preg_match_all('/(\"[^"]+\")|([^\s]+)/', $str, $matches))
		{
			foreach($matches[0] as $match)
			{
				$ret[] = ($match[0] == '"') ? substr($match, 1, -1) : $match;
			}
		}
		return array_unique($ret);
	}
	
	/**
	*  Informs how search criterions are combined.
	*  Returns "and" or "or";
	*
	*  @return string
	*/
	function getCriteriaCombineType()
	{
		if( $this->_where[$this->sessionPrefix."_srchCriteriaCombineType"] == "or" )
			return "or";	
		
		if( $this->simpleSearchActive && !count($this->_where[$this->sessionPrefix."_srchFields"]) )
			return "or";
		
		return "and";
	}
	
	static function UnserializeObject($str)
	{
		if(!$str)
			return null;
		$obj = unserialize($str);
		$pSet = new ProjectSettings($obj->tName, PAGE_SEARCH);
		$obj->cipherer = new RunnerCipherer($obj->tName, $pSet);
		return $obj;
	}
}
?>
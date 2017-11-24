<?php
/**
 * Search panel builder for LIST_SIMPLE mode
 *
 */
class SearchPanelSimple extends SearchPanel {

	var $srchPanelAttrs = array();
	
	var $isDisplaySearchPanel = true;
	
	var $isFlexibleSearch = true;
	
	var $searchOptions = array();
	
	function SearchPanelSimple(&$params) 
	{
		parent::SearchPanel($params);
		
		$this->isDisplaySearchPanel = $this->pSet->showSearchPanel();
		$this->isFlexibleSearch = $this->pSet->isFlexibleSearch();
	}
	
	function buildSearchPanel() 
	{
		parent::buildSearchPanel();
		if ($this->isDisplaySearchPanel)
		{
			$this->srchPanelAttrs = $this->searchClauseObj->getSrchPanelAttrs();
			$this->searchOptions = $this->pSet->getSearchPanelOptions();
			$this->DisplaySearchPanel();
		}
	}
	
	function searchAssign() 
	{	
		parent::searchAssign();
		
		$searchGlobalParams = $this->searchClauseObj->getSearchGlobalParams();	
		$searchPanelAttrs = $this->searchClauseObj->getSrchPanelAttrs();
		// show hide window	
		$this->xt->assign("showHideSearchWin_attrs", ' title="Floating window"');
		$searchOpt_mess = ($searchPanelAttrs['srchOptShowStatus'] ? "隐藏搜索选项。" : "显示搜索选项");
		$this->xt->assign("showHideSearchPanel_attrs", 'align="absmiddle" title="'.$searchOpt_mess.'" alt="'.$searchOpt_mess.'"');
		
		$searchforAttrs = "name=\"ctlSearchFor".$this->id."\" id=\"ctlSearchFor".$this->id."\"";
		if($this->isUseAjaxSuggest)
			$searchforAttrs .= "autocomplete=off ";		
	
		$searchforAttrs.= ' placeholder="'."搜索 初始页面加载后在搜索框中显示的消息。".'"';
		if($this->searchClauseObj->isUsedSrch())
		{
			$valSrchFor = $searchGlobalParams["simpleSrch"];
			$searchforAttrs.= " value=\"".runner_htmlspecialchars($valSrchFor)."\"";
		}

		$this->xt->assignbyref("searchfor_attrs", $searchforAttrs);
		$this->xt->assign('searchPanelTopButtons', $this->isDisplaySearchPanel);
		
		if ($this->pSet->showSimpleSearchOptions())
		{
			$simpleSearchTypeCombo = '<select id="simpleSrchTypeCombo'.$this->id.'" name="simpleSrchTypeCombo'.$this->id.'" size="1">';
			$simpleSearchTypeCombo .= $this->searchControlBuilder->getSimpleSearchTypeCombo($searchGlobalParams["simpleSrchTypeComboOpt"], $searchGlobalParams["simpleSrchTypeComboNot"]) ;
			$simpleSearchTypeCombo .= "</select>";
			
			$this->xt->assign('simpleSearchTypeCombo', $simpleSearchTypeCombo);
			
			$simpleSearchFieldCombo = '<select id="simpleSrchFieldsCombo'.$this->id.'" name="simpleSrchFieldsCombo'.$this->id.'" size="1">';
			$simpleSearchFieldCombo .= $this->searchControlBuilder->simpleSearchFieldCombo($this->allSearchFields, $searchGlobalParams["simpleSrchFieldsComboOpt"]) ;
			$simpleSearchFieldCombo .= "</select>";
			
			$this->xt->assign('simpleSearchFieldCombo', $simpleSearchFieldCombo);	
		}
	}
	
	/**
	 * Search panel on list template handler
	 */
	function DisplaySearchPanel()
	{
		$this->xt->assign('searchPanel', $this->isDisplaySearchPanel);	
		$this->xt->assign('id', $this->id);
		if( !$this->isFlexibleSearch )
			$this->xt->assign('controls_block_class', 'flexibleSearchPanel');
			
		// assign the search panel radio buttons 
		$searchRadio = $this->searchControlBuilder->getSearchRadio();
		$this->xt->assign_section("all_checkbox_label", $searchRadio['all_checkbox_label'][0], $searchRadio['all_checkbox_label'][1]);
		$this->xt->assign_section("any_checkbox_label", $searchRadio['any_checkbox_label'][0], $searchRadio['any_checkbox_label'][1]);
		$this->xt->assignbyref("all_checkbox",$searchRadio['all_checkbox']);
		$this->xt->assignbyref("any_checkbox",$searchRadio['any_checkbox']);
		
		// assign the 'Show/Hide options' button 
		$showHideOpt_mess = $this->srchPanelAttrs['ctrlTypeComboStatus'] ? "隐藏选项" : "显示选项";		
		$this->xt->assign("showHideOpt_mess", $showHideOpt_mess);
		$this->xt->assign("showHideCtrlsOpt_attrs", 'style="display: none;"');
		
		//hide the Search panel (by default) on the list, report or chart pages
//		if( $this->pageObj->pageType == PAGE_LIST || $this->pageObj->pageType == PAGE_REPORT || $this->pageObj->pageType == PAGE_CHART)
//			$this->xt->assign("srchOpt_attrs", 'style="display: none;"');
		
		if($this->searchClauseObj->getUsedCtrlsCount() <= 0)
			$this->xt->assign("bottomSearchButt_attrs", 'style="display: none;"');
	
		$this->assignSearchBlocks();
	}
	
	/**
	* Assign controls blocks for the search panel's,
	* added-by-user and cached fields	
	*/
	function assignSearchBlocks() 
	{
		global $gLoadSearchControls;
		
		$searchPanelFieldsBlocks = array();
		$otherFieldsBlocks = array();
		$notAddedFileds = array();
		$srchCtrlBlocksNumber = 0;
		
		$recId = $this->pageObj->genId();
		// Get the data about the user-added search panel controls
		$openFiletrsData = $this->getOpenFiltersData();
		
		// build search controls for each field, first we need to build used controls, because cached must have last index	
		foreach($this->allSearchFields as $searchField)
		{
			$this->pageObj->fillFieldToolTips($searchField);
			$srchFields = $this->searchClauseObj->getSearchCtrlParams($searchField);
			$isSrchPanelField = in_array($searchField, $this->panelSearchFields);

			if( !count($srchFields) )
			{
				$defaultValue = $this->pSet->getDefaultValue( $searchField );
				
				if( $openFiletrsData[$searchField] )
				{
					// add fields that user has added to the search panel
					for($i = 0; $i < $openFiletrsData[$searchField]; $i++)
						$srchFields[] = array('opt' => '', 'not' => '', 'value1' => $defaultValue, 'value2' => '');
				}
				if( $isSrchPanelField )
				{
					$opt = '';
					//set the field's option choosen for the inflexible search panel 
					if( !$this->isFlexibleSearch )
						$opt = $this->searchOptions[$searchField];

					// add a search panel field that should be always shown on the panel	
					$srchFields[] = array('opt' => $opt, 'not' => '', 'value1' => $defaultValue, 'value2' => '');
				}
			}
					
			if( count($srchFields) )
			{
				if($isSrchPanelField) 
					$srchFields[ count($srchFields) - 1 ]['immutable'] = true;

				foreach($srchFields as $srchField)
				{
					// build used ctrl
					$block = $this->searchControlBuilder->buildSearchCtrlBlockArr($recId, $searchField, 0, $srchField['opt'], $srchField['not'], 
						false, $srchField['value1'], $srchField['value2'], $isSrchPanelField, $this->isFlexibleSearch, $srchField['immutable']);
					
					if($isSrchPanelField)
						$searchPanelFieldsBlocks[$searchField][] = $block;
					else
						$otherFieldsBlocks[] = $block;
					
					$srchCtrlBlocksNumber++;
					$this->addSearchFieldToControlsMap($searchField, $recId);
				}
			} 
			else
				$notAddedFileds[] = $searchField;			
		}
			
		// assign search panel fields (default and added-by-user)		
		foreach($searchPanelFieldsBlocks as $name => $namedBlocks)
		{		
			$this->xt->assign_loopsection_byValue('searchCtrlBlock_'.GoodFieldName($name), $namedBlocks);
		}
			
		if(!$this->isFlexibleSearch)
			return;
		
		//add cached searhc fields
		if( $srchCtrlBlocksNumber > 0 && $srchCtrlBlocksNumber < $gLoadSearchControls )
		{
			$otherSearchControlsMaxNumber = $gLoadSearchControls - $srchCtrlBlocksNumber + count($otherFieldsBlocks);
			foreach($notAddedFileds as $searchField)
			{			
				$defaultValue = $this->pSet->getDefaultValue( $searchField );
				// add cached ctrl
				$otherFieldsBlocks[] = $this->searchControlBuilder->buildSearchCtrlBlockArr($recId, $searchField, 0, '', false, true, $defaultValue, '');
				$this->addSearchFieldToControlsMap($searchField, $recId);
				
				if( count($otherFieldsBlocks) >= $otherSearchControlsMaxNumber )
					break;
			}		
		}

		// assign cached and non search panel fields' blocks
		$this->xt->assign_loopsection('searchCtrlBlock', $otherFieldsBlocks);
	}
	
	/**
	* Add the search field block's data to the page's ControlsMap array
	* Generate the new value for the recId
	* @param String fName
	* @param &Number recId
	*/
	function addSearchFieldToControlsMap($fName, &$recId)
	{
		$isFieldNeedSecCtrl = $this->searchControlBuilder->isNeedSecondCtrl($fName);
		$searchBlock = array('fName'=>$fName, 'recId'=>$recId);
		$ctrlInd = 0;
		
		$searchBlock['ctrlsMap'][0] = $ctrlInd;
		if($isFieldNeedSecCtrl)
			$searchBlock['ctrlsMap'][1] = $ctrlInd + 1;

		if(!$this->isFlexibleSearch)
			$searchBlock['inflexSearchOption'] = $this->searchOptions[$fName];
		
		$this->pageObj->controlsMap["search"]["searchBlocks"][] = $searchBlock;
		$recId = $this->pageObj->genId();
	}
	
	/**
	* Extract the array containing the open search panel control's names
	* from the Search panel coockie
	* @return Array
	*/
	function getOpenFilters()
	{
		$panelsStates = my_json_decode(@$_COOKIE["searchPanel"]);
		if( !is_array($panelsStates) ) 
			return array();
			
		$panelKey = "panelState_".GoodFieldName( $this->pageObj->tName )."_".$this->pageObj->id;
		if( !array_key_exists($panelKey, $panelsStates) )	
			return array();

		$panelStateObj = $panelsStates[$panelKey];			
		return $this->refineOpenFilters( $panelStateObj["openFilters"] );
	}
	
	/**
	* Prepare the associative array, that key are
	* the searchable fields' names and the values
	* are the number of user-added Search panel controls
	* @return Array
	*/
	function getOpenFiltersData()
	{
		$openFiltersData = array();
	
		if( $this->searchClauseObj->isUsedSrch() )
			return $openFiltersData;
		
		$openFilters = $this->getOpenfilters();
		
		foreach($this->allSearchFields as $field)
		{
			$openFiltersData[$field] = 0;
			foreach($openFilters as $filter)
			{
				if($filter == $field)
					$openFiltersData[$field]++;
			}
		}
		return $openFiltersData;
	}

	/**
	* Refine the open seach panel fields array:
	* It removes all non search fields and each one 
	* of the always shown fields (search panel fields)
	* from the array.
	* @param Array openFilters
	* @return Array
	*/
	function refineOpenFilters($openFilters)
	{
		$openFiltersRefined = array();

		foreach($this->panelSearchFields as $panelFiled)
		{
			$key = array_search($panelFiled, $openFilters);
			if($key !== FALSE)
				array_splice($openFilters, $key, 1);
		}

		foreach($openFilters as $field)
		{
			if( in_array($field, $this->allSearchFields) ) 
				$openFiltersRefined[] = $field;
		}

		return $openFiltersRefined;
	}
}

?>
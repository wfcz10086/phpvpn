<?php

/**
 * Search control builder class for advanced search
 *
 */
class AdvancedSearchControl extends SearchControl 
{
	function AdvancedSearchControl($id, $tName, &$searchClauseObj, &$pageObj) {
		parent::SearchControl($id, $tName, $searchClauseObj, $pageObj);
		$this->getSrchPanelAttrs['ctrlTypeComboStatus'] = true;
	}
	
	function getCtrlSearchTypeOptions($fName, $selOpt, $not, $flexible = false, $both = false) 
	{
		$withNot = $both ? $not : false;
		return parent::getCtrlSearchTypeOptions($fName, $selOpt, $withNot, false, $both);
	}
}


?>
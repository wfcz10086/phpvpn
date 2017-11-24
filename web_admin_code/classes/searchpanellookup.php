<?php
/**
 * Search panel builder for LIST_LOOKUP mode
 */
class SearchPanelLookup extends SearchPanel {

	function SearchPanelLookup(&$params) 
	{
		parent::SearchPanel($params);
	}
	
	function searchAssign()
	{
		parent::searchAssign();
		
		$searchforAttrs = 'placeholder="'. "搜索 初始页面加载后在搜索框中显示的消息。".'"';
				
		$searchGlobalParams = $this->searchClauseObj->getSearchGlobalParams();
		if($this->searchClauseObj->isUsedSrch())
		{
			$valSrchFor = $searchGlobalParams["simpleSrch"];
			$searchforAttrs.= " value=\"".runner_htmlspecialchars($valSrchFor)."\"";
		}

		$searchforAttrs.= " size=\"15\" name=\"ctlSearchFor".$this->id."\" id=\"ctlSearchFor".$this->id."\"";
		
		$this->xt->assign("searchfor_attrs", $searchforAttrs);
	}
}

?>
<?php

class ListPage_Dashboard extends ListPage_Embed
{	
	/**
     * @constructor
     * @param array params
     */
	function ListPage_Dashboard(&$params)
	{
		parent::ListPage_Embed($params);
		$this->showAddInPopup = true;

		$this->formBricks["header"] = array( 
			array( "name" => "details_block", "align" => "right" ),
			array( "name" => "newrecord_controls_block", "align" => "right" ), 
			array( "name" => "record_controls_block", "align" => "right" ) 
		);	
		$this->formBricks["footer"] = array( "pagination_block" );	
		
		if( $this->mapRefresh )
			$this->pageSize = -1;
	}

	/**
	 * Assign seesion prefix
	 */
	protected function assignSessionPrefix() 
	{
		$this->sessionPrefix = $this->dashTName."_".$this->tName;
	}

	/**
	 * Fill table settings
	 * @intellisense
	 */
	protected function fillTableSettings( $table = "", $pSet = null )
	{
		parent::fillTableSettings( $table, $pSet );
		
		if( $this->addAvailable() )
			$this->jsSettings["tableSettings"][ $this->tName ]["showAddInPopup"] = true;

		if( $this->editAvailable() )
			$this->jsSettings["tableSettings"][ $this->tName ]["showEditInPopup"] = true;
			
		if( $this->viewAvailable() )
			$this->jsSettings["tableSettings"][ $this->tName ]["showViewInPopup"] = true;			
	}
	
	/**
	 * Checks if need to display grid
	 */
	function isDispGrid() 
	{
		return $this->permis[$this->tName]['search'];
	}
	
	function addCommonJs()
	{
		$this->addControlsJSAndCSS();
		$this->addButtonHandlers();
	}
	
	function commonAssign()
	{
		parent::commonAssign();
		$this->xt->assign("details_block", true);
		$this->xt->assign("widhtSelected", $this->inlineEditAvailable() || $this->deleteAvailable() );
	}
	
	/**
	 *
	 */
	function buildSQL()
	{	
		if( $this->mode == LIST_DASHBOARD && $this->hasMainDashMapElem() )
			$this->strWhereClause = $this->mapRefresh ? $this->getWhereByMap() : "1=0";
		
		parent::buildSQL();
	}	
	
	/**
	 *
	 */
	function showPage() 
	{
		$this->BeforeShowList();
		
		if( isMobile() )
			$bricksExcept = array("grid_mobile", "pagination", "details_found");
		else 
			$bricksExcept = array("grid", "pagination", "message", "recordcontrols_new", "recordcontrol", "details_found");
		
		$this->xt->hideAllBricksExcept( $bricksExcept );
		$this->xt->prepare_template($this->templatefile);
		$this->showPageAjax();
	}

	
	/**
	 * Display blocks after loaded template of page
	 */
	function showPageAjax() 
	{
		$this->addControlsJSAndCSS();
		$this->fillSetCntrlMaps();
		$returnJSON = array();
		$returnJSON['controlsMap'] = $this->controlsHTMLMap;
		$returnJSON['viewControlsMap'] = $this->viewControlsHTMLMap;
		$returnJSON['settings'] = $this->jsSettings;
		$this->xt->assign("header",false);
		$this->xt->assign("footer",false);

		if( $this->formBricks["header"] )
			$returnJSON['headerCont'] = $this->fetchBlocksList( $this->formBricks["header"], true );					
		
		//	prepend headerCont with the page title
		$returnJSON['headerCont'] = '<span class="rnr-dbebrick">' 
			. $this->getPageTitle( $this->pageType, GoodFieldName($this->tName) ) 
			. "</span>"
			. $returnJSON['headerCont'];

		if( $this->formBricks["footer"] )
			$returnJSON["footerCont"] = $this->fetchBlocksList( $this->formBricks["footer"], true );
			
		$this->assignFormFooterAndHeaderBricks(false);
		$this->xt->prepareContainers();
		$returnJSON["html"] = $this->xt->fetch_loaded("body");
		
		$returnJSON['idStartFrom'] = $this->flyId;
		$returnJSON['success'] = true;
		
		$returnJSON["additionalJS"] = $this->grabAllJsFiles();
		$returnJSON["CSSFiles"] = $this->grabAllCSSFiles();

		echo printJSON($returnJSON);
	}
		
	function fillSetCntrlMaps()
	{
		parent::fillSetCntrlMaps();
		$this->controlsHTMLMap[ $this->tName ][ $this->pageType ][ $this->id ]["pageNumber"] = $this->myPage;
		$this->controlsHTMLMap[ $this->tName ][ $this->pageType ][ $this->id ]["numberOfPages"] = $this->maxPages;
	}

	function fillCheckAttr(&$record, $data, $keyblock)
	{
		if($this->deleteAvailable() || $this->inlineEditAvailable()) 
			$record["checkbox"] = true;
		$record["checkbox_attrs"]= "name=\"selection[]\" value=\"".runner_htmlspecialchars($keyblock)."\" id=\"check".$this->id."_".$this->recId."\"";
	}

	function deleteAvailable() {
		return parent::deleteAvailable() && $this->dashElementData["deleteRecord"];
	}

	function editAvailable() {
		return parent::editAvailable() && $this->dashElementData["popupEdit"];
	}

	function addAvailable() {
		return parent::addAvailable() && $this->dashElementData["popupAdd"];
	}

	function inlineEditAvailable() {
		return parent::inlineEditAvailable() && $this->dashElementData["inlineEdit"]; 
	}
	function inlineAddAvailable() {
		return parent::inlineAddAvailable() && $this->dashElementData["inlineAdd"];
	}

	function viewAvailable() {
		return parent::viewAvailable() && $this->dashElementData["popupView"];
	}
	
	function detailsInGridAvailable()
	{
		return false;
	}
	
	/**
	 * @return Boolean
	 */
	protected function hasDependentDashMapElem() 
	{
		foreach( $this->dashSet->getDashboardElements() as $dElem ) 
		{
			if( $dElem["table"] == $this->tName && $dElem["type"] == DASHBOARD_MAP && !$dElem["updateMoved"] )
				return true;
		}
		
		return false;
	}
	
	/**
	 * @return Boolean
	 */	
	protected function hasMainDashMapElem()
	{
		foreach( $this->dashSet->getDashboardElements() as $dElem ) 
		{
			if( $dElem["table"] == $this->tName && $dElem["type"] == DASHBOARD_MAP && $dElem["updateMoved"] )
				return true;
		}
		
		return false;	
	}
	
	/**
	 * @return Boolean
	 */
	protected function hasBigMap() 
	{
		return parent::hasBigMap() || $this->hasDependentDashMapElem(); 
	}
	
	/**
	 * @param &Array data
	 * @param Array keys
	 * @param String editLink
	 */
	function addBigGoogleMapMarkers(&$data, $keys, $editLink = '')
	{	
		parent::addBigGoogleMapMarkers( $data, $keys, $editLink );
			
		foreach( $this->dashSet->getDashboardElements() as $dElem ) 
		{
			if( $dElem["elementName"] == $this->dashElementName || $dElem["table"] != $this->tName || $dElem["type"] != DASHBOARD_MAP || $dElem["updateMoved"] )
				continue;
				
			$markerData = array();
			
			$markerData["lat"] = str_replace( ",", ".", ($data[ $dElem["latF"] ] ? $data[ $dElem["latF"] ] : "") );
			$markerData["lng"] = str_replace( ",", ".", ($data[ $dElem["lonF"] ] ? $data[ $dElem["lonF"] ] : "") );
			$markerData["address"] = $data[ $dElem["addressF"] ] ? $data[ $dElem["addressF"] ] : "";
			$markerData["desc"] = $data[ $dElem["descF"] ] ? $data[ $dElem["descF"] ] : $markerData["address"];				
			$markerData["mapIcon"] = $dElem["iconF"];
			
			$markerData["recId"] = $this->recId;
			$markerData["keys"] = $keys; 
			
			$markerData["masterKeys"] = $this->getMarkerMasterKeys( $data );
			
			$mapId = GoodFieldName( $this->dashTName )."_".$dElem["elementName"]."_dashMap";
			
			if( !isset( $this->googleMapCfg["mapsData"][ $mapId ] ) )
			{
				$this->googleMapCfg["mapsData"][ $mapId ] = array();
				$this->googleMapCfg["mapsData"][ $mapId ]["skipped"] = true;
				$this->googleMapCfg["mapsData"][ $mapId ]["dashMap"] = true;
			}
			
			if( !isset( $this->googleMapCfg["mapsData"][ $mapId ]["markers"] ) )	
				$this->googleMapCfg["mapsData"][ $mapId ]["markers"] = array();				
			
			$this->googleMapCfg['mapsData'][ $mapId ]['markers'][] = $markerData;			
		}	
	}
	
	protected function isInlineAreaToSet()
	{
		if( $this->mode == LIST_DASHBOARD )
			return true;
			
		return parent::isInlineAreaToSet();
	}
	
	/**
	 * A stub
	 */
	function rulePRG()  {}
	
	/**
	 * A stub
	 */		
	function buildSearchPanel() {}	
}
?>
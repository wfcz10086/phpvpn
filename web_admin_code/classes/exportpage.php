<?php

class ExportPage extends RunnerPage
{
	/**
	 * Assign 'body' element
	 */
	public function addCommonHtml()
	{
		// assign body begin
		$this->body["begin"] = GetBaseScriptsForPage(false);
		// assign body end
		$this->body['end'] = XTempl::create_method_assignment( "assignBodyEnd", $this );

		$this->xt->assignbyref("body", $this->body);
	}	
	
	/**
	 * @param String type
	 * @param Mixed rs
	 * @param Number nPageSize
	 */
	public function exportTo( $type, $rs, $nPageSize)
	{
		global $locale_info;
		
		if( substr(@$type, 0, 5) == "excel" )
		{
			//	remove grouping
			$locale_info["LOCALE_SGROUPING"] = "0";
			$locale_info["LOCALE_SMONGROUPING"] = "0";
			ExportToExcel($rs, $nPageSize, $this->eventsObject, $this->cipherer, $this);
			
			return;
		}
		
		if( $type == "word" )
		{
			$this->ExportToWord($rs, $nPageSize);
			return;
		}
		
		if( $type == "xml" )
		{
			$this->ExportToXML($rs, $nPageSize);
			return;
		}
		
		if( $type == "csv" )
		{
			$locale_info["LOCALE_SGROUPING"] = "0";
			$locale_info["LOCALE_SDECIMAL"] = ".";
			$locale_info["LOCALE_SMONGROUPING"] = "0";
			$locale_info["LOCALE_SMONDECIMALSEP"] = ".";
			
			$this->ExportToCSV($rs, $nPageSize);
		}		
		
	}
	
	/**
	 * @param Mixed rs
	 * @param Number nPageSize
	 */
	public function ExportToWord($rs, $nPageSize)
	{
		global $cCharset;
		header("Content-Type: application/vnd.ms-word");
		header("Content-Disposition: attachment;Filename=".GetTableURL($this->tName).".doc");

		echo "<html>";
		echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$cCharset."\">";
		echo "<body>";
		echo "<table border=1>";

		$this->WriteTableData($rs, $nPageSize);

		echo "</table>";
		echo "</body>";
		echo "</html>";
	}

	/**
	 * @param Mixed rs
	 * @param Number nPageSize
	 */
	public function ExportToXML($rs, $nPageSize)
	{
		global $cCharset;
		
		$exportFields = $this->pSet->getExportFields();
		
		header("Content-Type: text/xml");
		header("Content-Disposition: attachment;Filename=".GetTableURL($this->tName).".xml");
		if($this->eventsObject->exists("ListFetchArray"))
			$row = $this->eventsObject->ListFetchArray($rs, $this);
		else
			$row = $this->cipherer->DecryptFetchedArray( $this->connection->fetch_array( $rs ) );
		
		echo "<?xml version=\"1.0\" encoding=\"".$cCharset."\" standalone=\"yes\"?>\r\n";
		echo "<table>\r\n";
		$i = 0;
		$this->viewControls->setForExportVar("xml");
		while((!$nPageSize || $i<$nPageSize) && $row)
		{
			$values = array();
			foreach( $exportFields as $field )
			{
				$fType = $this->pSet->getFieldType($field);
				if( IsBinaryType( $fType ) )
					$values[ $field ] = "二进制数据过长－无法显示";
				else
					$values[ $field ] = $this->getExportValue($field, $row);
			}
			
			$eventRes = true;
			if ($this->eventsObject->exists('BeforeOut'))
				$eventRes = $this->eventsObject->BeforeOut($row, $values, $this);
			
			if ($eventRes)
			{
				$i++;
				echo "<row>\r\n";
				foreach ($values as $fName => $val)
				{
					$field = runner_htmlspecialchars(XMLNameEncode($fName));
					echo "<".$field.">";
					echo $values[$fName];
					echo "</".$field.">\r\n";
				}
				echo "</row>\r\n";
			}
			
			
			if($this->eventsObject->exists("ListFetchArray"))
				$row = $this->eventsObject->ListFetchArray($rs, $this);
			else
				$row = $this->cipherer->DecryptFetchedArray( $this->connection->fetch_array( $rs ) );
		}
		echo "</table>\r\n";
	}

	/**
	 * @param Mixed rs
	 * @param Number nPageSize
	 */
	public function ExportToCSV($rs, $nPageSize)
	{
		$exportFields = $this->pSet->getExportFields();
		
		header("Content-Type: application/csv");
		header("Content-Disposition: attachment;Filename=".GetTableURL($this->tName).".csv");
		printBOM();
		
		if($this->eventsObject->exists("ListFetchArray"))
			$row = $this->eventsObject->ListFetchArray($rs, $this);
		else
			$row = $this->cipherer->DecryptFetchedArray( $this->connection->fetch_array( $rs ) );

		// write header
		$outstr = "";
		foreach( $exportFields as $field )
		{
			if($outstr != "")
				$outstr.= ",";
			$outstr.= "\"".$field."\"";
		}
		echo $outstr;
		echo "\r\n";

		// write data rows
		$iNumberOfRows = 0;
		$this->viewControls->setForExportVar( "csv" );
		while((!$nPageSize || $iNumberOfRows < $nPageSize) && $row)
		{
			$values = array();
			foreach( $exportFields as $field )
			{
				$fType = $this->pSet->getFieldType($field);
				if( IsBinaryType( $fType ) )
					$values[ $field ] = "二进制数据过长－无法显示";
				else
					$values[ $field ] = $row[ $field ];
			}

			$eventRes = true;
			if( $this->eventsObject->exists('BeforeOut') )
			{
				$eventRes = $this->eventsObject->BeforeOut($row,$values, $this);
			}
			if ($eventRes)
			{
				$outstr = "";
				foreach( $exportFields as $field )
				{				
					if($outstr != "")
						$outstr.= ",";
					$outstr.='"'.str_replace('"', '""', $values[ $field ]).'"';
				}
				echo $outstr;
			}
			
			$iNumberOfRows++;
			if( $this->eventsObject->exists("ListFetchArray") )
				$row = $this->eventsObject->ListFetchArray($rs, $this);
			else
				$row = $this->cipherer->DecryptFetchedArray( $this->connection->fetch_array( $rs ) );
				
			if(((!$nPageSize || $iNumberOfRows<$nPageSize) && $row) && $eventRes)
				echo "\r\n";
		}
	}

	/**
	 * @param Mixed rs
	 * @param Number nPageSize
	 */
	protected function WriteTableData($rs, $nPageSize)
	{	
		$exportFields = $this->pSet->getExportFields();
		$totalFieldsData = $this->pSet->getTotalsFields();
		
		if($this->eventsObject->exists("ListFetchArray"))
			$row = $this->eventsObject->ListFetchArray($rs, $this);
		else
			$row = $this->cipherer->DecryptFetchedArray( $this->connection->fetch_array( $rs ) );

		// write header
		echo "<tr>";
		if($_REQUEST["type"]=="excel")
		{
			foreach( $exportFields as $field )
			{
				echo '<td style="width: 100" x:str>'.PrepareForExcel( $this->pSet->label( $field ) ).'</td>';	
			}
		}
		else
		{
			foreach( $exportFields as $field )
			{
				echo "<td>".$this->pSet->label( $field )."</td>";
			}
		}
		echo "</tr>";
		
		$totals = array();
		$totalsFields = array();
		foreach( $totalFieldsData as $data )
		{
			if( !in_array( $data["fName"], $exportFields ) )
				continue;
				
			$totals[ $data["fName"] ] = array("value" => 0, "numRows" => 0);
			$totalsFields[] = array('fName' => $data["fName"], 'totalsType' => $data["totalsType"], 'viewFormat' => $this->pSet->getViewFormat( $data["fName"] ));
		}
		
		// write data rows
		$iNumberOfRows = 0;
		$this->viewControls->setForExportVar( "export" );
		while( (!$nPageSize || $iNumberOfRows < $nPageSize) && $row )
		{
			countTotals($totals, $totalsFields, $row);
			
			$values = array();
		
			foreach( $exportFields as $field )
			{
				$fType = $this->pSet->getFieldType($field);
				if( IsBinaryType( $fType ) )
					$values[ $field ] = "二进制数据过长－无法显示";
				else 
					$values[ $field ] = $this->getViewControl( $field )->getExportValue($row, "");
			}
			
			$eventRes = true;
			if( $this->eventsObject->exists('BeforeOut') )
			{
				$eventRes = $this->eventsObject->BeforeOut($row, $values, $this);
			}
			if ($eventRes)
			{
				$iNumberOfRows++;
				echo "<tr>";
				
				foreach( $exportFields as $field )
				{
					$fType = $this->pSet->getFieldType($field);
					if( IsCharType($fType) )
					{
						if($_REQUEST["type"]=="excel")
							echo '<td x:str>';
						else
							echo '<td>';
					}
					else
						echo '<td>';
				
					$editFormat = $this->pSet->getEditFormat( $field );
					if( $editFormat == EDIT_FORMAT_LOOKUP_WIZARD )
					{	
						if( $this->pSet->NeedEncode($field) )
						{						
							if( $_REQUEST["type"] == "excel" )
								echo PrepareForExcel( $values[ $field ] );
							else
								echo $values[ $field ];
						}
						else
							echo $values[ $field ];
					} 
					elseif( IsBinaryType( $fType ) )	
						echo $values[ $field ];
					else
					{
						if( $editFormat == FORMAT_CUSTOM || $this->pSet->isUseRTE( $field ) )
							echo $values[ $field ];
						elseif( NeedQuotes($field) )
						{						
							if($_REQUEST["type"] == "excel")
								echo PrepareForExcel( $values[ $field ] );
							else
								echo $values[ $field ];
						}
						else
							echo $values[ $field ];
					}
					echo '</td>';
				}			
				echo "</tr>";
			}
				
			if( $this->eventsObject->exists("ListFetchArray") )
				$row = $this->eventsObject->ListFetchArray($rs, $this);
			else
				$row = $this->cipherer->DecryptFetchedArray( $this->connection->fetch_array( $rs ) );
		}
		
		if( count($totalFieldsData) )
		{
			echo "<tr>";
			foreach( $totalFieldsData as $data )
			{
				if( !in_array( $data["fName"], $exportFields ) )
					continue;
			
				echo "<td>";
				if( strlen($data["totalsType"]) )
				{
					if( $data["totalsType"] == "COUNT" )
						echo "计数".": ";
					elseif( $data["totalsType"] == "TOTAL" )	
						echo "总计".": ";
					elseif( $data["totalsType"] == "AVERAGE" )
						echo "平均数".": ";

					echo runner_htmlspecialchars( GetTotals($data["fName"], 
						$totals[ $data["fName"] ]["value"], 
						$data["totalsType"], 
						$totals[ $data["fName"] ]["numRows"],
						$this->pSet->getViewFormat( $data["fName"] ), 
						PAGE_EXPORT,
						$this->pSet) );
				}
				echo "</td>";
			}
			echo "</tr>";
		}
	}	
	
	/**
	 * @deprecated
	 * @param Mixed rs
	 * @param Number nPageSize	 
	 */
	public function ExportToExcel_old($rs, $nPageSize)
	{
		global $cCharset;
		header("Content-Type: application/vnd.ms-excel");
		header("Content-Disposition: attachment;Filename=".GetTableURL($this->tName).".xls");

		echo "<html>";
		echo "<html xmlns:o=\"urn:schemas-microsoft-com:office:office\" xmlns:x=\"urn:schemas-microsoft-com:office:excel\" xmlns=\"http://www.w3.org/TR/REC-html40\">";
		
		echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$cCharset."\">";
		echo "<body>";
		echo "<table border=1>";

		$this->WriteTableData($rs, $nPageSize);

		echo "</table>";
		echo "</body>";
		echo "</html>";
	}	
}
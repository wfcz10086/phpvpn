<?php
class MSSQLWinConnection extends Connection
{	
	protected $host;
		
	protected $dbname;
	
	protected $user = "";
	
	protected $pwd = "";
	
	protected $options = "";
	
	protected $ODBCString;
	
	/**
	 * A date format string
	 * @type String
	 */
	protected $mssql_dmy;

	
	function MSSQLWinConnection( $params )
	{
		parent::Connection( $params );
	}

	/**
	 * Set db connection's properties
	 * @param Array params
	 */
	protected function assignConnectionParams( $params )
	{
		parent::assignConnectionParams( $params );
		
		$this->ODBCString = $params["ODBCString"];		
		$this->host = $params["connInfo"][0]; //strConnectInfo1
		
		if( $params["connInfo"][2] == "SSPI" )
		{
			$this->dbname = $params["connInfo"][1];  //strConnectInfo2
			$this->options = $params["connInfo"][2];  //strConnectInfo3
		}
		else
		{
			$this->user = $params["connInfo"][1];  //strConnectInfo2 
			$this->pwd = $params["connInfo"][2]; //strConnectInfo3 
			$this->dbname = $params["connInfo"][3];  //strConnectInfo4
		}
	}
	
	/**
	 * Open a connection to db
	 */
	public function connect()
	{
		global $cCodepage;
		
		$connStrings = array();
		if( $_SESSION["MSSQLConnString"] )
			$connStrings[] = $_SESSION["SQLConnString"];
		
		$connStrings[] = $this->ODBCString;
		
	//	SQLOLEDB provider
		if( $options == "SSPI" )
			$connStrings[] = "PROVIDER=SQLOLEDB;SERVER=".$this->host.";DATABASE=".$this->dbname.";Integrated Security=SSPI";
		else
			$connStrings[] = "PROVIDER=SQLOLEDB;SERVER=".$this->host.";UID=".$this->user.";PWD=".$this->pwd.";DATABASE=".$this->dbname;	
	//	SQLNCLI provider
		if( $options=="SSPI" )
			$connStrings[] = "PROVIDER=SQLNCLI;SERVER=".$this->host.";DATABASE=".$this->dbname.";Integrated Security=SSPI";
		else
			$connStrings[] = "PROVIDER=SQLNCLI;SERVER=".$this->host.";UID=".$this->user.";PWD=".$this->pwd.";DATABASE=".$this->dbname;	
	
	//	go thru connection strings one by one
		$errorString = "";
		foreach($connStrings as $connStr)
		{
			try 
			{
				$this->conn = new COM("ADODB.Connection", NULL, $cCodepage);
				$this->conn->Open( $connStr );
				$rs = $this->conn->Execute("select convert(datetime,'2000-11-22',121)");
			
				$str = $rs->Fields[0]->Value;
				$y = strpos($str, "2000");
				$m = strpos($str, "11");
				$d = strpos($str, "22");
			
				$this->mssql_dmy = "mdy";
				if( $y < $m && $m < $d )
					$this->mssql_dmy = "ymd";
				if( $d < $m && $m < $y )
					$this->mssql_dmy = "dmy";
					
				$_SESSION["MSSQLConnString"] = $connStr;
				return $this->conn;
			}
			catch(com_exception $e)
			{
				$errorString .= "<br>".$e->getMessage();
			}
		}
		trigger_error($errorString, E_USER_ERROR);
	}
	
	/**
	 * Close the db connection
	 */
	public function close()
	{
		$this->conn->Close();
	}
	
	/**	
	 * Send an SQL query
	 * @param String sql
	 * @return Mixed
	 */
	public function query( $sql )
	{
		$this->debugInfo($sql);
		
		try
		{
			$qHandle = $this->conn->Execute( $sql );
			return new QueryResult( $this, $qHandle );
		} 
		catch(com_exception $e)
		{
			trigger_error($e->getMessage(), E_USER_ERROR);
			return FALSE;
		}
	}
	
	/**	
	 * Execute an SQL query
	 * @param String sql
	 */
	public function exec( $sql )
	{
		$qResult = $this->query( $sql );
		if( $qResult )
			return $qResult->getQueryHandle();
		
		return FALSE;	
	}
	
	/**	
	 * Get a description of the last error
	 * @return String
	 */
	public function lastError()
	{
		if( $this->conn->Errors->Count )
			return $this->conn->Errors[ $this->conn->Errors->Count - 1 ]->Description;
		
		return '';
	}
	
	/**
	 * Fetch a result row as an array
	 * @param Mixed qHanle		The query handle
	 * @param Number assoc (optional)
	 * @return Array
	 */
	protected function _fetch_array($qHanle, $assoc = 1)
	{
		$ret = array();
		
		if( $qHanle->EOF() )
			  return $ret;
			   
		try {		
			for( $i = 0; $i < $this->num_fields($qHanle); $i++ )
			{
				if( IsBinaryType( $qHanle->Fields[$i]->Type ) && $qHanle->Fields[$i]->Type != 128 )
				{
					$str = "";
					if( $qHanle->Fields[$i]->ActualSize )
					{
						$val = $qHanle->Fields[$i]->GetChunk( $qHanle->Fields[$i]->ActualSize );
						$str = str_pad("", count($val));
						$j = 0;
						foreach($val as $byte)
						{
							$str[ $j++ ] = chr($byte);
						}
					}
					
					if( $assoc )
						$ret[ $qHanle->Fields[$i]->Name ] = $str;
					else
						$ret[ $i ] = $str;
				}
				else
				{
					$value = $qHanle->Fields[$i]->Value;
					if( is_null($value) )
						$val = NULL;	
					else
					{
						if( isdatefieldtype($qHanle->Fields[$i]->Type) )
							$value = localdatetime2db( (string)$qHanle->Fields[$i]->Value, $this->mssql_dmy );
						if( IsNumberType($qHanle->Fields[$i]->Type) )
							$val = floatval($value);
						else
							$val = strval($value);
					}
					if( $assoc )
						$ret[ $qHanle->Fields[$i]->Name ] = $val;
					else
						$ret[ $i ] = $val;
				}
			}
			$qHanle->MoveNext();
		} 
		catch(com_exception $e)
		{
			trigger_error($e->getMessage(), E_USER_ERROR);
		}

		return $ret;
	}	
	
	/**
	 * Fetch a result row as an associative array
	 * @param Mixed qHanle		The query handle
	 * @return Array
	 */
	public function fetch_array( $qHanle )
	{
		return $this->_fetch_array( $qHanle );
	}
	
	/**	
	 * Fetch a result row as a numeric array
	 * @param Mixed qHanle		The query handle	 
	 * @return Array
	 */
	public function fetch_numarray( $qHanle )
	{
		return $this->_fetch_array( $qHanle, 0 );
	}
	
	/**	
	 * Free resources associated with a query result set 
	 * @param Mixed qHanle		The query handle		 
	 */
	public function closeQuery( $qHanle )
	{
		$qHanle->Close();
	}

	/**	
	 * Get number of fields in a result
	 * @param Mixed qHanle		The query handle
	 * @return Number
	 */
	public function num_fields( $qHandle )
	{
		return $qHandle->Fields->Count;
	}	
	
	/**	
	 * Get the name of the specified field in a result
	 * @param Mixed qHanle		The query handle
	 * @param Number offset
	 * @return String
	 */	 
	public function field_name( $qHanle, $offset )
	{
		return $qHanle->Fields($offset)->Name;
	}
	
	/**
	 * @param Mixed qHandle
	 * @param Number pageSize
	 * @param Number page
	 */
	public function seekPage($qHandle, $pageSize, $page)
	{
		if( $page == 1 )
			return;
			
		if( $qHandle->EOF() )
			return;
			
	   $qHandle->Move($pageSize * ($page - 1));
	}
	
	/**
	 * Execute an SQL query with blob fields processing
	 * @param String sql
	 * @param Array blobs
	 * @param Array blobTypes
	 * @return Boolean
	 */
	public function execWithBlobProcessing( $sql, $blobs, $blobTypes = array() )
	{
		$this->debugInfo($sql);
		
		try
		{
			$this->conn->Execute( $sql );
			return true;
		} 
		catch(com_exception $e)
		{
			return false;
		}		
	}
}
?>
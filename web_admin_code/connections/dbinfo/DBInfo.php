<?php
/**
 * The base class containing methods 
 * extracting the database info
 */
class DBInfo
{
	/**
	 * @type Connection
	 */ 
	protected $connectionObj;
	
	
	function DBInfo( $connObj )
	{
		$this->connectionObj = $connObj;
	}
}
?>
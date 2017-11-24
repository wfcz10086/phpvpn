<?php
class RunnerCiphererDES
{
	/**
	 * Encryption key (8 characters maximum)
	 * @var string
	 */
	var $key = '';
	var $INITIALISATION_VECTOR = 'd27b358d';
	
	var $mcryptModule = null;
	
	function RunnerCiphererDES($key){
		$this->mcryptModule = mcrypt_module_open(MCRYPT_DES, '', MCRYPT_MODE_CBC, '');
		$this->key = substr($key, 0, mcrypt_enc_get_key_size($this->mcryptModule));
	}
	
	/**
	 * DESEncrypt
	 * Encrypt string with DES algorythm
	 * @param {string} plain value
	 * @return {string} encrypted value
	 */
	function DESEncrypt($source) {
		$result = '';
		if($source != '' && mcrypt_generic_init($this->mcryptModule, $this->key, $this->INITIALISATION_VECTOR) != -1) {
			$result = bin2hex (mcrypt_generic($this->mcryptModule, $source));
		}
		return $result;
	}
	
	/**
	 * DESDecrypt
	 * Decrypt string, ecncrypted with DES algorythm
	 * @param {string} encrypted value
	 * @return {string} decrypted value
	 */
	function DESDecrypt($source) {
		if(!is_string($source) || strlen($source) == 0 || strlen($source) % 2 > 0 || preg_match ("/[^0-9a-f]/", $source) == 1)
			return $source;
		$result = '';
		if($source != '' && mcrypt_generic_init($this->mcryptModule, $this->key, $this->INITIALISATION_VECTOR) != -1) {
			$result = mdecrypt_generic($this->mcryptModule, hex2bin($source));
			$result = str_replace("\0", '', $result);
		}
		return $result;
	}
	
	
}
?>
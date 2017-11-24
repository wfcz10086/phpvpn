<?php
require_once getabspath('classes/controls/TextControl.php');
class TextAreaField extends TextControl
{
	function TextAreaField($field, $pageObject, $id, $connection)
	{
		parent::EditControl($field, $pageObject, $id, $connection);
		$this->format = EDIT_FORMAT_TEXT_AREA;
	}
	
	function buildControl($value, $mode, $fieldNum, $validate, $additionalCtrlParams, $data)
	{
		parent::buildControl($value, $mode, $fieldNum, $validate, $additionalCtrlParams, $data);
		
		$nWidth = $this->pageObject->pSetEdit->getNCols($this->field);
		$nHeight = $this->pageObject->pSetEdit->getNRows($this->field);
		if($this->pageObject->pSetEdit->isUseRTE($this->field))
		{
			$value = $this->RTESafe($value);
		}
		else
		{
			echo '<textarea id="'.$this->cfield.'" '.(($mode==MODE_INLINE_EDIT || $mode==MODE_INLINE_ADD) && $this->is508==true ? 'alt="'
				.$this->strLabel.'" ' : '').'name="'.$this->cfield.'" style="';
			if (!isMobile())
				echo "width: ".($nWidth)."px;";
			echo 'height: '.$nHeight.'px;">'.runner_htmlspecialchars($value).'</textarea>';
		}
		
		$this->buildControlEnd($validate);
	}
	
	/**
	 * returns safe code for preloading in the RTE
	 * @intellisense
	 * @param String text
	 * @return String
	 */
	protected function RTESafe($text)
	{		
		$tmpString = trim($text);
		if(!$tmpString) 
			return "";
		
		//	convert all types of single quotes
		$tmpString = str_replace("'", "&#39;", $tmpString);
		
		//	replace carriage returns & line feeds
		$tmpString = str_replace( chr(10), " ", $tmpString );
		$tmpString = str_replace( chr(13), " ", $tmpString );
		
		return $tmpString;
	}

	/**
	 * @intellisense
	 */
	protected function CreateCKeditor($value)
	{
		echo '<div id="disabledCKE_'.$this->cfield.'"><textarea id="'.$this->cfield.'" name="'.$this->cfield.'" rows="8" cols="60">'.runner_htmlspecialchars($value).'</textarea>';
	}	
}
?>
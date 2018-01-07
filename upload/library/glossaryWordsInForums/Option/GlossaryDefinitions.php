<?php

class glossaryWordsInForums_Option_GlossaryDefinitions {
	public static function renderView(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit){
		$editLink = $view->createTemplateObject('option_list_option_editlink', array(
			'preparedOption' => $preparedOption,
			'canEditOptionDefinition' => $canEdit
		));
		return $view->createTemplateObject('kiror_option_template_glossary_definitions', array(
			'fieldPrefix' => $fieldPrefix,
			'listedFieldName' => $fieldPrefix . '_listed[]',
			'preparedOption' => $preparedOption,
			'formatParams' => $preparedOption['formatParams'],
			'editLink' => $editLink,
			
			'nextCounter' => count($preparedOption['option_value']),
			'choices' => $preparedOption['option_value'],
		));
	}
	
	public static function validate(&$fields, XenForo_DataWriter $dw, $fieldName){
		foreach($fields as $id=>$entry){
			if((!array_key_exists('entry',$entry)||(!array_key_exists('definition',$entry)))||(strlen($entry['entry'])<=0)||(strlen($entry['definition'])<=0)){
				unset($fields[$id]);
			}
		}
		usort($fields,function($a,$b){return strcmp($a['entry'],$b['entry']);});
		/*
		homeOrServer_DownloadHelper::sendAsDownload(
		json_encode(
		$fields
		,JSON_PRETTY_PRINT)
		,'a','',false);
		//*/
		
		return true;
	}
}

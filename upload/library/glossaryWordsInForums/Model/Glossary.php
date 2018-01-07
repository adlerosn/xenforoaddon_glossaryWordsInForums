<?php

class glossaryWordsInForums_Model_Glossary extends XenForo_Model {
	public function getAllEntries(){
		$entries = XenForo_Application::getOptions()->kiror_glossary_definitions;
		if(!is_array($entries))
			$entries = [];
		foreach($entries as $id=>$entry){
			if((!array_key_exists('entry',$entry)||(!array_key_exists('definition',$entry)))){
				unset($entries[$id]);
			}
		}
		usort($entries,function($a,$b){return strcmp($a['entry'],$b['entry']);});
		return $entries;
	}
	public function getAllEntriesKeyed(){
		$all = $this->getAllEntries();
		$k = [];
		foreach($all as $entry){
			$k[$entry['entry']]=$entry['definition'];
		}
		return $k;
	}
	public function getAllKeys(){
		return array_keys($this->getAllEntriesKeyed());
	}
}

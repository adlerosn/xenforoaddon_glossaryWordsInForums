<?php

class glossaryWordsInForums_Navigation_Tabs {
	public static function callback(array &$extraTabs, $selectedTabId){
		$extraTabs['kiror-glossary'] = [
			'position' => XenForo_Application::getOptions()->kiror_glossary_tab_position,
			'title' => new XenForo_Phrase('glossary'),
			'selected' => ($selectedTabId == 'kiror-glossary'),
			'href' => XenForo_Link::buildPublicLink('glossary'),
			'linksTemplate' => '',
		];
	}
}

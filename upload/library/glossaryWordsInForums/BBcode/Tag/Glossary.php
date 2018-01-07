<?php

class glossaryWordsInForums_BBcode_Tag_Glossary {
	public static function callback(array $tag, array $rendererStates, XenForo_BbCode_Formatter_Base $formatter){
		$inner = $formatter->renderTree($tag['children']);
		$outerbef = '<a class="OverlayTrigger glossaryLink" href="'.XenForo_Link::buildPublicLink('glossary','',['term'=>$inner]).'">';
		$outeraft = '</a>';
		if(
			$formatter instanceof XenForo_BbCode_Formatter_Text
			||
			$formatter instanceof XenForo_BbCode_Formatter_HtmlEmail
			||
			$formatter instanceof XenForo_BbCode_Formatter_Wysiwyg
		){
			$outerbef = '';
			$outeraft = '';
		}
		return $outerbef.$inner.$outeraft;
	}
}

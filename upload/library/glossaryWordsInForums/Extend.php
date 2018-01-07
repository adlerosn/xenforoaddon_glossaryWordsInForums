<?php

class glossaryWordsInForums_Extend {
	public static function getExtensions(){
		return [
			['XenForo_BbCode_Parser', 'glossaryWordsInForums_Extend_XfBbcParser'],
		];
	}
	public static function callback($class, array &$extend){
		$xtds = static::getExtensions();
		foreach($xtds as $xtd){
			$baseClass = $xtd[0];
			$toExtend = $xtd[1];
			if($class==$baseClass && !in_array($toExtend, $extend)){
				$extend[]=$toExtend;
			}
		}
	}
}

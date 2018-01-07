<?php

class glossaryWordsInForums_Extend_XfBbcParser extends XFCP_glossaryWordsInForums_Extend_XfBbcParser {
	public function parse($text){
		$definedWords = XenForo_Model::create('glossaryWordsInForums_Model_Glossary')->getAllKeys();
		//organize in length order
		usort($definedWords,function($a,$b){return strlen($a)-strlen($b);});
		$definedWords = array_reverse($definedWords);
		/*
		 * Splitting BBCode in tags and text
		 */
		$_pickRegex = (
		'~'.
		'\\[(\\/?)([a-zA-Z*]*?)\\]|\\[([a-zA-Z]*?)\\ *?=\\ *?"([^"]*?)"\\]|\\[([a-zA-Z]*?)\\ *?=\\ *?\\\'([^\\\']*?)\\\'\\]|\\[([a-zA-Z]*?)\\ *?=\\ *?([^\\]]*?)\\]'.
		'~s'
		);
		$bbtags = [];
		$matches = [];
		preg_match_all($_pickRegex, $text, $matches, PREG_SET_ORDER|PREG_OFFSET_CAPTURE);
		foreach($matches as &$match){
			for($i = 0; $i<=8; $i++){
				if(!array_key_exists($i,$match)){
					$match[$i]=[0=>'',1=>-1];
				}
			}
			$bbtag=[];
			$bbtag['type']='tag';
			$bbtag['closing']=$match[1][1]>0&&strlen($match[1][0]);
			$bbtag['original']=$match[0][0];
			$bbtag['pos_start']=$match[0][1];
			$bbtag['pos_end']=$match[0][1]+strlen($match[0][0]);
			$bbtag['tag']=$match[2][0].$match[3][0].$match[5][0].$match[7][0];
			$bbtag['param']=$match[4][0].$match[6][0].$match[8][0];
			$bbtags[]=$bbtag;
			unset($bbtag);
		}
		unset($match);
		unset($matches);
		$tokenized = [];
		if(count($bbtags)>0){
			$marks = [0,$bbtags[0]['pos_start']];
			$tokenized[]=['type'=>'text','original'=>substr($text,$marks[0],$marks[1]-$marks[0])];
			$tokenized[]=$bbtags[0];
			for($i = 1; $i<count($bbtags); $i++){
				$marks = [$bbtags[$i-1]['pos_end'],$bbtags[$i]['pos_start']];
				$tokenized[]=['type'=>'text','original'=>substr($text,$marks[0],$marks[1]-$marks[0])];
				$tokenized[]=$bbtags[$i];
			}
			$i=count($bbtags)-1;
			$marks = [$bbtags[$i]['pos_end'],strlen($text)];
			$tokenized[]=['type'=>'text','original'=>substr($text,$marks[0],$marks[1]-$marks[0])];
		}else{
			$tokenized[]=['type'=>'text','original'=>$text];
		}
		foreach($tokenized as &$token){
			if($token['type']=='tag'){
				//unneeded after tokenized
				unset($token['pos_start']);
				unset($token['pos_end']);
				//unneeded for this purpose
				unset($token['closing']);
				unset($token['tag']);
				unset($token['param']);
			}
		}
		unset($token);
		/*
		 * Removing previously inserted glossary tags
		 */
		$cnt = count($tokenized);
		$gtags=['[GLOSSARY]','[/GLOSSARY]'];
		for($i=0;$i<$cnt;$i++){
			$token = $tokenized[$i];
			if($token['type']=='tag' && in_array(strtoupper($token['original']),$gtags)){
				unset($tokenized[$i]);
			}
		}
		$tokenized = array_merge($tokenized);
		if(
		!	(
			$this->_formatter instanceof XenForo_BbCode_Formatter_HtmlEmail
			||
			$this->_formatter instanceof XenForo_BbCode_Formatter_Wysiwyg
			)
		){
			/*
			 * From the pure text, we want finding glossary entries and building glossary tags
			 * 
			 * Causes glitches with other add-ons
			 */
			/*
			$finished = False;
			do{
				$finished = True;
				$tokenized2 = [];
				foreach($tokenized as $token){
					if($token['type']=='text'){
						$original = $token['original'];
						$totalLen = strlen($original);
						$foundWord = False;
						foreach($definedWords as $entry){
							$pos = strpos(strtolower($original),strtolower($entry));
							if($pos!==false){
								$finished = False;
								$foundWord = True;
								$entryLen = strlen($entry);
								$tokenized2[]=['type'=>'text','original'=>substr($original,0,$pos)];
								$tokenized2[]=['type'=>'tag' ,'original'=>'[GLOSSARY]'.substr($original,$pos,$entryLen).'[/GLOSSARY]'];
								$tokenized2[]=['type'=>'text','original'=>substr($original,$pos+$entryLen)];
								break;
							}
						}
						if(!$foundWord){
							$tokenized2[]=$token;
						}
					}else{
						$tokenized2[]=$token;
					}
				}
				$tokenized = $tokenized2;
			}while(!$finished);
			*/
		}
		$newText = '';
		foreach($tokenized as $token){
			$newText.=$token['original'];
		}
		/*
		if(strlen($text)>700){
			header('Content-Type: text/plain');
			die(json_encode([$definedWords,$tokenized,$text,$newText],JSON_PRETTY_PRINT));
			die(print_r([$tokenized,$text],true));
		}//*/
		//$newText = $text;
		return parent::parse($newText);
	}
	public function render($text, array $extraStates = array()){
		//parsing, if not parsed
		if (is_array($text))
			$parsed = $text;
		else
			$parsed = $this->parse($text);
		//getting defined words
		$definedWords = XenForo_Model::create('glossaryWordsInForums_Model_Glossary')->getAllKeys();
		usort($definedWords,function($a,$b){return strlen($a)-strlen($b);});
		$definedWords = array_reverse($definedWords);
		//getting explorable tags; @TODO: put this in admin interface
		$allowedTags = ['b','i','u','s','color','font','size','list','left','center','right','quote','spoiler','indent','pluralsys'];
		//injecting glossary tags in node tree
		if(
		!	(
			$this->_formatter instanceof XenForo_BbCode_Formatter_HtmlEmail
			||
			$this->_formatter instanceof XenForo_BbCode_Formatter_Wysiwyg
			)
		){
			$this->_addGlossaryTags_AddOn($parsed,$definedWords,$allowedTags);
			//$this->_addonWriterDebug($parsed);
		}
		return parent::render($parsed, $extraStates);
	}
	protected function _addGlossaryTags_AddOn(array &$parsed, array &$definedWords = array(), array &$enterableTags = array()){
		$urlRegex = '#(?<=[^a-z0-9@-]|^)(https?://|www\.)[^\s"<>{}`]+#iu';
		$goAgain = False;
		for($id=0;$id<count($parsed);$id++){
			$segment = &$parsed[$id];
		//foreach($parsed as $id=>&$segment){
			//if is array, it's a tag; check if its tag is safe to touch its children
			if(is_array($segment) && array_key_exists('tag',$segment) && array_key_exists('children',$segment)){
				if(strtolower($segment['tag'])=='glossary'){
					true;
				}
				else{
					foreach($enterableTags as $enterableTag){
						if(strtolower($enterableTag)==strtolower($segment['tag'])){
							//as it's safe, do this recursively;
							//$this->_addonWriterDebug($segment);
							$this->_addGlossaryTags_AddOn($segment['children'],$definedWords,$enterableTags);
						}
					}
				}
			}
			else
			if(is_string($segment)){
				$urlMatches = [];
				$protectedZones = [];
				preg_match_all($urlRegex,$segment,$urlMatches,PREG_OFFSET_CAPTURE|PREG_PATTERN_ORDER);
				if(array_key_exists(0,$urlMatches)){
					foreach($urlMatches[0] as $zoneData){
						$protectedZones[]=[
							'begin' => $zoneData[1],
							 'end'  => $zoneData[1]+strlen($zoneData[0]),
						];
					}
				}
				//$this->_addonWriterDebug($protectedZones);
				for($i = 0; $i < count($definedWords); $i++){
					$entry = $definedWords[$i];
				//foreach($definedWords as $entry){
					$segmentlower = strtolower($segment);
					$entrylower   = strtolower($entry);
					$pos = strpos(strtolower($segment),strtolower($entry));
					if($pos!==false){
						$inProtectedZone = false;
						foreach($protectedZones as $protectedZone){
							if($protectedZone['begin']<=$pos && $pos<$protectedZone['end']){
								$inProtectedZone = true;
								break;
							}
						}
						if($inProtectedZone){
							continue;
						}
						//$goAgain = True;
						$entryLen = strlen($entry);
						$parsed2 = [];
						$parsed2[]= substr($segment,0,$pos);
						$parsed2[]=[
							'tag'=>'glossary',
							'option'=>'',
							'original'=>['',''],
							'children'=>[substr($segment,$pos,$entryLen)],
						];
						$parsed2[]= substr($segment,$pos+$entryLen);
						$parsed = array_merge(
							array_slice($parsed,0,$id),
							$parsed2,
							array_slice($parsed,$id+1)
						);
						$id--;
						break;
					}
				}
			}
			//if($goAgain) break;
			if($goAgain) $goAgain = False;
		}
		unset($segment);
		if($goAgain) return $this->_addGlossaryTags_AddOn($parsed,$definedWords,$enterableTags);
		return null;
	}
	protected function _addonWriterDebug($var){
		header('Content-type: text/plain');
		die(print_r($var,true));
	}
}

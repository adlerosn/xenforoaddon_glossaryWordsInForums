<?php

class glossaryWordsInForums_ControllerPublic_Glossary extends XenForo_ControllerPublic_Abstract {
	protected function _getGlossaryModel(){
		return $this->getModelFromCache('glossaryWordsInForums_Model_Glossary');
	}
	
	public function actionIndex(){
		$term = $this->_input->filterSingle('term',XenForo_Input::STRING);
		$entries = $this->_getGlossaryModel()->getAllEntries();
		if($term){
			$matched = $this->_getDefinitions($entries,$term);
			if(count($matched)>0){
				return $this->getViewForEntry($entries,$matched);
			}
		}
		$visited = [];
		$huge = [];
		foreach($entries as $entry){
			$term = strtolower($entry['entry']);
			if(!in_array($term,$visited)){
				$visited[]=$term;
				$huge[$term]=['entry'=>$entry['entry'],'definitions'=>[],'synonyms'=>[]];
			}
			foreach($this->_getDefinitions($entries,$term) as $definition){
				$huge[$term]['definitions'][]=$definition;
			}
			$huge[$term]['definitions']=array_unique($huge[$term]['definitions'], SORT_REGULAR);
			sort($huge[$term]['definitions']);
			$synonyms = $this->_findSynonymies($entries,$huge[$term]['definitions']);
			foreach($synonyms as $id=>$synonym){
				if($entry['entry']===$synonym){
					unset($synonyms[$id]);
				}
			}
			unset($id);
			unset($synonym);
			sort($synonyms);
			$synonymsRaw = $this->_buildRawHtmlLinksForSynonymies($synonyms);
			$synonymsRawMerged = implode(', ',$synonymsRaw);
			$huge[$term]['synonymsList'] = $synonyms;
			$huge[$term]['synonyms'] = $synonymsRawMerged;
		}
		//removing one level in array structure
		//  $huge[idX]['definitions'][idY]['definition'] => str
		// to
		//  $huge[idX]['definitions'][idY] => str
		foreach($huge as &$listItem){
			$newDef = [];
			foreach($listItem['definitions'] as $asseption){
				$newDef[]=$asseption['definition'];
			}
			sort($newDef);
			$listItem['definitions'] = array_merge($newDef);
		}
		unset($listItem);
		//Removing duplicate definitions, by definition text
		//criteria: strlen($huge_item['entry']); bigger is better
		//pass 1: quit based on synonymsList
		foreach($huge as $key1=>$arr1){
			//1 is current
			foreach($arr1['synonymsList'] as $synonym){
				//2 is other
				$key2 = strtolower($synonym);
				if(array_key_exists($key2,$huge)){
					$arr2 = $huge[$key2];
					if(
						(
							strlen($arr1['entry'])<=strlen($arr2['entry'])
							//current entry name is smaller than other
							&&
							$this->_isSubset($arr2['definitions'],$arr1['definitions'])
							//current definition set is a subset of the other definition
						)
						||
						(
							$this->_isSubsetNeq($arr2['definitions'],$arr1['definitions'])
							//current definition set is a subset of the other definition, but different
						)
					){
						//then there's no reason to display repeated data...
						// bye!
						unset($huge[$key1]);
						break;
					}
				}
			}
		}
		//$this->_addonWriterDebug($huge);
		//linking entries
		foreach($huge as $eid=>$entryItem){
			foreach($entryItem['definitions'] as $did=>$definition){
				$huge[$eid]['definitions'][$did] = $this->_provideInterDefinitionLinks($entries,$definition);
			}
		}
		//$this->_findSynonymies($entries,$entries);
		$viewParams = [
			'entries'=>$huge
		];
		return $this->responseView('XenForo_ViewPublic_Base','glossary_list',$viewParams);
	}
	
	protected function getViewForEntry(array $all, array $matched){
		sort($matched);
		$synonyms = $this->_findSynonymies($all,$matched);
		$synonymsRaw = $this->_buildRawHtmlLinksForSynonymies($synonyms);
		$synonymsRawMerged = implode(', ',$synonymsRaw);
		foreach($matched as &$match)
			$match['definition'] = $this->_provideInterDefinitionLinks($all,$match['definition']);
		$viewParams = [
			'entry'=>$matched[0]['entry'],
			'matches'=>$matched,
			'synonyms'=>$synonyms,
			'synonymsRaw'=>$synonymsRaw,
			'synonymsRawMerged'=>$synonymsRawMerged,
		];
		return $this->responseView('XenForo_ViewPublic_Base','glossary_entry',$viewParams);
	}
	
	protected function _getDefinitions(array $entries, $term){
		$matched = [];
		foreach($entries as $entry){
			if(strtolower($term) == strtolower($entry['entry'])){
				$matched[]=$entry;
			}
		}
		return array_unique($matched, SORT_REGULAR);
	}
	
	protected function _findSynonymies(array $all, array $matches){
		$synonyms = [];
		foreach($matches as $match){
			foreach($all as $each){
				if(strtolower($match['definition'])==strtolower($each['definition'])){
					$entry = $each['entry'];
					if(!in_array($entry,$synonyms)){
						$synonyms[]=$entry;
					}
				}
			}
		}
		return $synonyms;
	}
	
	protected function _buildLinksForSynonimy(array $synonyms){
		foreach($synonyms as &$synonym){
			$synonym = [
				'label' => $synonym,
				'url' => XenForo_Link::buildPublicLink('glossary','',['term'=>$synonym])
			];
		}
		return $synonyms;
	}
	
	protected function _buildRawHtmlLinksForSynonymies(array $synonyms, $class='avatar'){
		$synonyms = $this->_buildLinksForSynonimy($synonyms);
		$synonymsRaw = [];
		foreach($synonyms as $synonym){
			$synonymsRaw[]= '<a class="'.$class.'" href="'.$synonym['url'].'">'.htmlspecialchars($synonym['label']).'</a>';
		}
		return $synonymsRaw;
	}
	
	protected function _provideInterDefinitionLinks(array $all, $one){
		$definedWords = [];
		foreach($all as $each) array_push($definedWords,$each['entry']);
		usort($definedWords,function($a,$b){return strlen($a)-strlen($b);});
		$definedWords = array_reverse($definedWords);
		$lo = strtolower($one);
		$found = [];
		foreach($definedWords as $word){
			$lw = strtolower($word);
			if(strpos($lo,$lw)!==false){
				$defined = false;
				foreach($found as $w){
					if(strpos($w,$lw)!==false){
						unset($w);
						$defined = true;
						break;
					}
					unset($w);
				}
				if(!$defined) array_push($found,$lw);
				unset($defined);
			}
			unset($lw);
			unset($word);
		}
		$replaced = [$one];
		$finished = false;
		while(!$finished){
			$finished = true;
			$tmp = [];
			foreach($replaced as $segment){
				$foundWord = false;
				foreach($found as $word){
					$pos = strpos(strtolower($segment),$word);
					if($pos!==false && strtolower($segment)!=$word){
						$finished = false;
						$foundWord = true;
						$wordLen = strlen($word);
						$tmp[]=substr($segment,0,$pos);
						$tmp[]=substr($segment,$pos,$wordLen);
						$tmp[]=substr($segment,$pos+$wordLen);
					}
					if(!$finished)break;
				}
				if(!$foundWord) $tmp[]=$segment;
			}
			$replaced = $tmp;
		}
		foreach($replaced as $sid=>$segment){
			if(in_array(strtolower($segment),$found)){
				$replaced[$sid] = $this->_buildRawHtmlLinksForSynonymies([$segment], 'OverlayTrigger glossaryLink')[0];
			}
		}
		$replaced = implode('',$replaced);
		return $replaced;
	}
		
	protected function _addonWriterDebug($var){
		header('Content-type: text/plain');
		die(print_r($var,true));
	}
	
	protected function _isSubset(array $haystack, array $needle){
		foreach($needle as $item){
			if(!in_array($item,$haystack)){
				return false;
			}
		}
		return true;
	}
	
	protected function _isSubsetNeq(array $haystack, array $needle){
		$ss = $this->_isSubset($haystack,$needle);
		$neq = $haystack!=$needle;
		return ($ss && $neq);
	}
}

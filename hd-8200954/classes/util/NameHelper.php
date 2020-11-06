<?php

namespace util;

class NameHelper {

	public static function explodeName($name){
		$explodeName = array();
		$explode = preg_split('@(?!^)[\\-_]+@',$name,-1,PREG_SPLIT_NO_EMPTY);
		foreach ($explode as $namePart) {
			if(preg_match('@^_?(([a-z]+)|([A-Z]+))$@',$namePart)){
				$explodeName[] = strtolower($namePart);
				continue;
			}
			$match = array();
			preg_match_all('@_?([A-Z]|^)[a-z]*@',$namePart,$match);
			foreach ($match[0] as $n) {
				$explodeName[] = strtolower($n);
			}
		}
		return $explodeName;
	}

	public static function prepareName($name){
		$preparedName = '';
		foreach (NameHelper::explodeName($name) as $partName) {
			$preparedName.= ucfirst($partName);
		}
		return lcfirst($preparedName);
	}

	public static function toTableName($name){
		return 'tbl_'.implode('_',NameHelper::explodeName($name));
	}

	public static function toColumnName($name){
		return implode('_',NameHelper::explodeName($name));
	}

	public static function toFolderName($name){
		return implode('-',NameHelper::explodeName($name));
	}

	public static function toClassName($name){
		$explode = NameHelper::explodeName($name);
		$ucfirst = array_map(function($partName){
			return ucfirst($partName);
		},$explode);
		return implode('',$ucfirst);
	}

	public static function prepareArray($array,$toObject=false){
		$preparedArray = array();
		foreach($array as $key => $value){
			$newValue = is_array($value)?NameHelper::prepareArray($value,$toObject):$value;
			$newKey = is_numeric($key)?$key:NameHelper::prepareName($key);
			$preparedArray[$newKey] = $toObject?(object)$newValue:$newValue;
		}
		return $preparedArray;
	}

	public static function prepareArrayWithRecursiveLimit($array,$toObject=false,&$recursiveLimit=2){
		$recursiveLimit--;
		$preparedArray = array();
		foreach($array as $key => $value){
			if($recursiveLimit > 0)
				$newValue = is_array($value)?NameHelper::prepareArrayWithRecursiveLimit($value,$toObject,$recursiveLimit):$value;
			else
				$newValue = $value;
			$newKey = is_numeric($key)?$key:NameHelper::prepareName($key);
			$preparedArray[$newKey] = $toObject?(object)$newValue:$newValue;
		}
		return $preparedArray;
	}

}
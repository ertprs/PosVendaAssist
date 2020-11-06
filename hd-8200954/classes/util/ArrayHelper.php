<?php

namespace util;

class ArrayHelper {

	/**
	*	dataArray formart is
	*	array(
	*		key1 : [values...],
	*		key2 : [values...],
	*		.
	*		.
	*		.
	*		keyn : [values...]
	*	)
	*/

	/**
	*	objectArray format is
	*
	*	array(
	*		array(key1:value1,key2:value2 ... keyn:valuen),		
	*		array(key1:value1,key2:value2 ... keyn:valuen),		
	*		.
	*		.
	*		.
	*		array(key1:value1,key2:value2 ... keyn:valuen),		
	*	)
	*/


	public static function dataArrayToObjectArray($dataArray,$toObject = false){
		$max = 0;
		$objectKeys = array();
		foreach($dataArray as $key => $value){
			if(!is_array($value))
				throw new Exception('formato de DataArray Invalido');
			$objectKeys[] = $key;
			$count = count($value);
			$max = $max < $count?$count:$max;
		}
		$objectArray = array();
		for($i =0;$i<$max;$i++){
			$object = array();
			foreach($objectKeys as $key){
				if(!isset($dataArray[$key][$i]))
					continue;
				$object[$key] = $dataArray[$key][$i];
			}
			$object = $toObject?(object)$object:$object;
			$objectArray[] = $object;
		}
		return $objectArray;

	}

	public static function objectArrayToDataArray($objectArray){
		$dataArray = array();
		foreach ($objectArray as $index => $object) {
			if(!is_numeric($index))
				throw new Exception('formato de ObjectArray Invalido');
			$object = is_array($object)?$object:(array)$object;
			foreach ($object as $key => $value) {
				$dataArray[$key] = isset($dataArray[$key])?$dataArray[$key]:array();
				$dataArray[$key][$index] = $object[$key];
			}
		}
		return $dataArray;
	}

	public static function groupArray($toGroup,$groupColumn){
		$groupArray = array();
		foreach($toGroup as $line){
			$key = $line[$groupColumn];
			if(!isset($groupArray[$key])){
				$groupArray[$key] = array($line);
				continue;
			}
			$groupArray[$key][] = $line;
		}
		return $groupArray;
	}

	public static function toArray($object){
		$array = array();
		$reflectionClass = new ReflectionClass($object);
		foreach($reflectionClass->getProperties() as $property){
			$property->setAccessible(true);
			$array[$property->getName()] = $property->getValue($object);
		}
		return $array;
	}

	public static function getIfSet($array,$name,$elseValue=null){
		if(!is_array($name))
			$name = array($name);
		$value = $array;
		foreach($name as $key){
			//$possibleKeys = explode('|',$key);
			if(!isset($value[$key]))
				return $elseValue;
			$value = $value[$key];
		}
		return $value;
	}

	public static function getAnySet($array,$keys,$elseValue=null){
		foreach ($keys as $key) {
			if(!isset($array[$key]))
				continue;
			return $array[$key];
		}
		return $elseValue;
	}

	public function replaceAll(Array $array,$find,$replace){
		$newArray = array();
		foreach($array as $key => $value){
			$newKey = $key;
			$newValue = $value;
			if(is_string($key)){
				$newKey = str_replace($find,$replace,$key);
			}
			if(is_array($value)){
				$newValue = ArrayHelper::replaceAll($value,$find,$replace);
			}
			else if(is_string($value)){
				$newValue = str_replace($find,$replace,$value);
			}
			$newArray[$newKey] = $newValue;
		}	
		return $newArray;
	}

	public function isMapArray(Array $array){
		foreach($array as $key =>$value){
			if(is_string($key))
				return true;
		}
		return false;
	}

	public function findWithRegex($array,$regex){
		$findArray = array();
		foreach ($array as $key => $value) {
			if(!preg_match($regex,$key))
				continue;
			$findArray[$key] = $value;
		}
		return $findArray;
	}
}
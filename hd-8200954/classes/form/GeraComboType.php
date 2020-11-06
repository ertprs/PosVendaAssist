<?php 
class GeraComboType{

    private static $qtdeItens;
    private static $element;
    private static $index;
    private static $selectedValue;
    private static $nameId;
    private static $options;
    private static function isSelected($i){
	$optionValue = "Tipo {$i}";

	if($optionValue == self::$selectedValue){
	    return true;
	}else{
	    return false;
	}
    }
    
    private static function openTags(){
	if(self::$nameId===null){
	    self::$nameId = "type";
	}

	$class = (array_key_exists("class", self::$options)) ? "class='".self::$options["class"]."'" : "";

	self::$element .= "<select $class name='".self::$nameId . self::$index . "' id='".self::$nameId . self::$index . "' >";
    }

    private static function makeItens(){
	self::$element .= "<option value=''></option>";
	for($i = 1; $i <= self::$qtdeItens; $i++){
	    self::$element .= "<option ";
	    if(self::isSelected($i)){

		self::$element .= "selected ";
	    }
	    self::$element .= " value='Tipo ".$i."' >Tipo {$i} </option>";

	}
    }

    private static function closeTags(){
	self::$element .= "</select>";
    }
    public static function makeComboType($parametrosAdicionaisFabrica, $selectedValue, $nameId=null, $options=null){
	self::$element = "";

	self::$selectedValue = $selectedValue;
	self::$nameId = $nameId;

	self::$options = $options;

	self::$index = (array_key_exists("index", self::$options)) ? "_".$options["index"] : "";

	$objJson = $parametrosAdicionaisFabrica->getParametrosAdicionaisObject();
	if(!isset($objJson->type)){
	    throw new Exception("Combo Type não liberado para esta fábrica");
	}

	self::$qtdeItens = (int)$objJson->type;

	self::openTags();
	self::makeItens();
	self::closeTags();

    }

    public function getElement(){
	return self::$element;
    }

}


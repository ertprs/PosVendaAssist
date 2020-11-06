<?php

namespace util;

class StringHelper {

	public static function stripAccents($value) {   
	    $from = "ΰαβγδηθικλμνξορςστυφωϊϋόύÿΐΑΒΓΔΗΘΙΚΛΜΝΞΟΡÒΣΤΥΦΩΪΫάέ";
	    $to = "aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY";
	    $value = strtr($value,$from,$to);
	    return $value;
	}

	public static function like($str1,$str2){
		$str1 = strtoupper(StringHelper::stripAccents($str1));
		$str2 = strtoupper(StringHelper::stripAccents($str2));
		
		return $str1 == $str2;
	}

}

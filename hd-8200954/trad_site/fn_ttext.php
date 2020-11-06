<?php

/*  14/12/2009  MLG O programa vai conferir se existe um arquivo com nome padronizado para a traduзгo
					do programa, em base ao nome do arquivo (ex.: trad_index.php й o arquivo com o array
					de traduзгo do programa index.php)
*/
$prog_pos = strrpos($PHP_SELF, "/") + 1;
$prog_name= substr($PHP_SELF, $prog_pos);
$abs_path = substr(__FILE__, 0, strrpos(__FILE__, '/'));
$trad_name= "$abs_path/trad_".$prog_name;
if (file_exists($trad_name)) include_once $trad_name;
unset ($prog_name,$prog_pos,$trad_name);

if (!function_exists('ttext')) {
// -------------------------------------------------------------------------
/**
* Devolve o texto do array com coordenadas ($index,$idioma (default: $cook_idioma))
*
* Devolve o texto do array com as coordenadas ($index,$idioma (default: $cook_idioma)),
* ou $index/'pt-br' se nгo hб traduзгo, ou '$index' (com o '_' substituнdo por ' ')
* se nгo existe nem o нndice (ou estб vazio) e nem o portuguкs (ou estб vazio)
*
* @param array $array_trad
* @param string , $index
* @param string , $idioma (optional, defaults to "")
* @param 
* @param 
* @return string
*/
	function ttext ($array_trad, $index, $idioma = "") { // BEGIN function ttext
	    global $cook_idioma;
	    $idioma = ($idioma=="") ? $cook_idioma : $idioma;
		if ($idioma == '' or $array_trad[$index][$idioma] == "" or !isset($array_trad[$index][$idioma])) {
			if ($array_trad[$index]['pt-br'] == "" or !isset($array_trad[$index]['pt-br'])) {
			    return str_replace("_", " ", $index);
		    } else {
			    return $array_trad[$index]['pt-br'];
			}
		} else {
		    return $array_trad[$index][$idioma];
		}
	} // END function ttext
}
?>
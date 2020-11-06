<?php

//dbconfig que chama o autoload.php

/**
 * Método que carrega as classes "automaticamente"
 * no momento que ela é instanciada
 */
function __autoload($class) {
	$explodeClass = explode('\\',$class);
	$className = end($explodeClass);
	array_pop($explodeClass);
	$suffix = __DIR__.'/'.implode('/',$explodeClass);
	$file = $suffix.'/'.$className.'.php';
	if(file_exists($file)){
		require_once $file;
		return;
	}

	/**
	* O codigo abaixo foi mantido por garantia, com alguns ajuste para retirar o loop infinito
	*/
	$path = strtolower($class);
	$diretorio    = 'classes/';
	$subDiretorio = '';


	if (file_exists("helpers/$class.php")) {

		require_once "helpers/$class.php";

	} elseif (strpos($class, 'Mpdf') !== false) {
		$file = str_replace('\\', '/', substr($class, 5));
		require_once(__DIR__ . DIRECTORY_SEPARATOR . "Mpdf/src/$file.php");
	} else {
		for($i=0;$i<5;$i++){
	        if(file_exists($subDiretorio. $diretorio . $path . '.php')) {
	                require_once($subDiretorio. $diretorio . $path . '.php');
	                break;
	        }
	        $subDiretorio .= '../';
	        continue;
		}

	}

}

spl_autoload_register('__autoload');

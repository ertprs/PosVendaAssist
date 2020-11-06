<?php
class Data {
	var $timestamp;
	
	function Data($base = null) {
		
	}
	
	function converterParaTimestamp($base) {
		$base = str_replace('/','-',$base);
		
		if ( strpos($base,'.') !== false ) {
			// Remover o timestamp do banco
			list($base,$tmp) = explode('.',$base);
		}
		if ( strpos($base,' ') !== false ) {
			// Dividir hora data
			list($data,$hora) = explode(' ',$base);
		}
		// Normalizar os dados
		$data = ( isset($data) ) ? $data : $base ;
		$hora = ( isset($hora) ) ? $hora : '00:00:00' ;
		
			
	}
}
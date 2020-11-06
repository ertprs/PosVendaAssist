<?
#*****************************************************************************
#*       VALORES POR EXTENSO
#*****************************************************************************

function ext ($valor) {

	if ($valor == 0) {
		$ext = "";
		return ;
	}
	
	
	$array_unidade  = array ("", "UM ", "DOIS ", "TRES ", "QUATRO ", "CINCO ", "SEIS ", "SETE ", "OITO ", "NOVE ", "DEZ ", "ONZE ", "DOZE ", "TREZE ", "QUATORZE ", "QUINZE ", "DEZESSEIS ", "DEZESSETE ", "DEZOITO ", "DEZENOVE " ) ;
	$array_dezena   = array ("", "DEZ ", "VINTE ", "TRINTA ", "QUARENTA ", "CINQUENTA ", "SESSENTA ", "SETENTA ", "OITENTA ", "NOVENTA " ) ;
	$array_centena  = array ("", "CENTO ", "DUZENTOS ", "TREZENTOS ", "QUATROCENTOS ", "QUINHENTOS ", "SEISCENTOS ", "SETECENTOS ", "OITOCENTOS ", "NOVECENTOS " ) ;
	
	
	$valor = "00000000000" . trim (number_format ($valor,2,".",""));
	$valor = substr ($valor,strlen ($valor)-9);
	
	$ext = "";
	
	
	$parte_milhar   = substr ($valor,0,3);
	$parte_centena  = substr ($valor,3,3);
	$parte_centavos = substr ($valor,7,2);
	
	$centavos = "";
	$milhar   = "";
	$centena  = "";
	
	$parte = $parte_centavos;
	$x = intval ($parte);
	
	if ($x > 0) {
		$x = intval (substr ($parte,0,1));
		$centavos  = $array_dezena [$x];
		
		if (substr ($parte,1,1) > 0) {
			$x = intval (substr ($parte,1,1));
			
			if (strlen ($centavos) > 0)
				$centavos = $centavos . " e " ;
				$centavos .= $array_unidade [$x];
		}
	}
	
	$parte = $parte_centena;
	$x = intval ($parte);
	
	if ($x > 0) {
		$x = intval (substr ($parte,0,1));
		$centena  = $array_centena [$x];
		
		if (substr ($parte,1,1) > 0) {
			$x = intval (substr ($parte,1,1));
			
			if (strlen ($centena) > 0)
				$centena = $centena . " e " ;
				$centena .= $array_dezena [$x];
		}
		
		if (substr ($parte,2,1) > 0) {
			$x = intval (substr ($parte,2,1));
			
			if (strlen ($centena) > 0)
				$centena = $centena . " e " ;
				$centena .= $array_unidade [$x];
		}
	}
	
	$parte = $parte_milhar;
	$x = intval ($parte);
	
	if ($x > 0) {
		$x = intval (substr ($parte,0,1));
		$milhar = $array_centena [$x];
		
		if (substr ($parte,1,1) > 0) {
			$x = intval (substr ($parte,1,1));
			
			if (strlen ($milhar) > 0)
				$milhar = $milhar . " e " ;
				$milhar .= $array_dezena [$x];
		}
		
		if (substr ($parte,2,1) > 0) {
			$x = intval (substr ($parte,2,1));
			
			if (strlen ($milhar) > 0)
				$milhar = $milhar . " e " ;
				$milhar .= $array_unidade [$x];
		}
	}
	
	if (strlen ($milhar)   > 0) $milhar   .= " MIL ";
	if (strlen ($centena)  > 0) $centena  .= " REAIS ";
	if (strlen ($centavos) > 0) $centavos  = " e " . $centavos . " CENTAVOS";
	
	if (strlen ($milhar) > 0 AND (strlen ($centena) > 0 OR strlen ($centavos) > 0) ) 
		$milhar = $milhar . " e " ;
		$ext .= $milhar . $centena . $centavos;
	
	return $ext;
}
?>
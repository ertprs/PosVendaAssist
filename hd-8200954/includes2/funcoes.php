<?php
function mes_extenso ($mes) {
	switch ($mes) {
		case 1:
			return 'Janeiro';
			break;
		case 2:
			return 'Fevereiro';
			break;
		case 3:
			return 'Março';
			break;
		case 4:
			return 'Abril';
			break;
		case 5:
			return 'Maio';
			break;
		case 6:
			return 'Junho';
			break;
		case 7:
			return 'Julho';
			break;
		case 8:
			return 'Agosto';
			break;
		case 9:
			return 'Setembro';
			break;
		case 10:
			return 'Outubro';
			break;
		case 11:
			return 'Novembro';
			break;
		case 12:
			return 'Dezembro';
			break;
	}
}

function MW_MascaraString($expr,$mask){
    $ret = "";
    $j   = 0;
    $len = strlen($expr);
				    
    for ($i = 0; $i < $len; $i ++){
	if ( ( $mask[$j] != "9" ) and ( $mask[$j] != "X" ) ) {
		$ret.=$mask[$j];
		$j++;
	}
	
	$ret.=$expr[$i];
	$j++;
    }
    return $ret;
}

?>

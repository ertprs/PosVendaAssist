<?php
	function checa_cnpj($cnpj)
	{
		if ((!is_numeric($cnpj)) or (strlen($cnpj) <> 14))
		{
			return 2;
		}
		else
		{
			$i = 0;
			while ($i < 14)
			{
			$cnpj_d[$i] = substr($cnpj,$i,1);
			$i++;
			}
			$dv_ori = $cnpj[12] . $cnpj[13];
			$soma1 = 0;
			$soma1 = $soma1 + ($cnpj[0] * 5);
			$soma1 = $soma1 + ($cnpj[1] * 4);
			$soma1 = $soma1 + ($cnpj[2] * 3);
			$soma1 = $soma1 + ($cnpj[3] * 2);
			$soma1 = $soma1 + ($cnpj[4] * 9);
			$soma1 = $soma1 + ($cnpj[5] * 8);
			$soma1 = $soma1 + ($cnpj[6] * 7);
			$soma1 = $soma1 + ($cnpj[7] * 6);
			$soma1 = $soma1 + ($cnpj[8] * 5);
			$soma1 = $soma1 + ($cnpj[9] * 4);
			$soma1 = $soma1 + ($cnpj[10] * 3);
			$soma1 = $soma1 + ($cnpj[11] * 2);
			$rest1 = $soma1 % 11;
			if ($rest1 < 2)
			{
				$dv1 = 0;
			}
			else
			{
				$dv1 = 11 - $rest1;
			}
			$soma2 = $soma2 + ($cnpj[0] * 6);
			$soma2 = $soma2 + ($cnpj[1] * 5);
			$soma2 = $soma2 + ($cnpj[2] * 4);
			$soma2 = $soma2 + ($cnpj[3] * 3);
			$soma2 = $soma2 + ($cnpj[4] * 2);
			$soma2 = $soma2 + ($cnpj[5] * 9);
			$soma2 = $soma2 + ($cnpj[6] * 8);
			$soma2 = $soma2 + ($cnpj[7] * 7);
			$soma2 = $soma2 + ($cnpj[8] * 6);
			$soma2 = $soma2 + ($cnpj[9] * 5);
			$soma2 = $soma2 + ($cnpj[10] * 4);
			$soma2 = $soma2 + ($cnpj[11] * 3);
			$soma2 = $soma2 + ($dv1 * 2);
			$rest2 = $soma2 % 11;
			if ($rest2 < 2)
			{
				$dv2 = 0;
			}
			else
			{
				$dv2 = 11 - $rest2;
			}
			$dv_calc = $dv1 . $dv2;
			if ($dv_ori == $dv_calc)
			{
				return 0;
			}
			else
			{
				return 1;
			}
		}
	}

	$cnpj = checa_cnpj(10815996000147);

	if($cnpj==1){
		echo "CNPJ Inválido - ".$cnpj;
	}
	else{
		echo "CNPJ Válido - ".$cnpj;
	}

?>
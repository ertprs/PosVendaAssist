<?php
try {
	include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require_once dirname(__FILE__) . '/../funcoes.php';


	$fabrica = 3;
    //Verifica as fábricas e os tipos de pedidos válidos
    $sql_p = "SELECT tipo_pedido, tbl_fabrica.fabrica
				FROM tbl_tipo_pedido
					JOIN tbl_fabrica using(fabrica)
				WHERE ativo_fabrica = 't'
				AND (descricao ~* 'gar' or pedido_em_garantia = 't')
				AND  fabrica = $fabrica
				ORDER BY tbl_fabrica.fabrica;";
    $res_p = pg_query($con,$sql_p);

    if (pg_num_rows($res_p) > 0) {

    	for ($i=0; $i < pg_num_rows($res_p) ; $i++) {

    		$tipo_pedido  	=  pg_fetch_result($res_p, $i, tipo_pedido);
    		$fabrica  		=  pg_fetch_result($res_p, $i, fabrica);

    		$pedido_fabrica[$fabrica][] = $tipo_pedido;

    	}

    }

    foreach ($pedido_fabrica as $fabrica_key => $valores_pedidos) {

    	//Verifica os some dos pedidos gerados no dia da Semana.
    	$in_pedidos = implode(",", $valores_pedidos);

    	$sql_c = "SELECT distinct data::date, count(pedido)
						FROM tbl_pedido
						WHERE fabrica = $fabrica
							AND tipo_pedido in ($in_pedidos)
							AND data > current_timestamp - interval '60 days'
							AND data < current_date
						GROUP BY data::date
						ORDER BY data::date DESC LIMIT 15;";
		$res_c = pg_query($con,$sql_c);
		$resultados = pg_fetch_all($res_c);
		if (pg_num_rows($res_c) > 0) {
			$total = array();
			for ($i=0; $i < pg_num_rows($res_c) ; $i++) {
				$s_pedido = pg_fetch_result($res_c, $i, count);
				$total[] = $s_pedido;
			}

		}
			sort($total);
			$j=0;
			for($i=0;$i<5;$i++) {
				$total_menor += $total[$i];
				$j++;
			}
			$media_menor = $total_menor/$j*0.9;
			$media_menor = intval($media_menor);
			$total_pedido = count($total) - 1;
			$t = 0 ;
			for($i=$total_pedido;$i>$total_pedido - 5;$i--) {
				$total_maior += $total[$i];
				$t++;
			}

			$media_maior = $total_maior/$t*1.1;
			$media_maior = intval($media_maior);

		$sql_c = "SELECT count(pedido)
						FROM tbl_pedido
						WHERE fabrica = $fabrica
							AND tipo_pedido in ($in_pedidos)
							AND data > current_date ;";
		$res_c = pg_query($con,$sql_c);

		$pedido_dia = pg_fetch_result($res_c, 0, count);

    }


    	if ($pedido_dia < $media_menor or $pedido_dia > $media_maior) {

			$vet["dest"] = array("integracao.telecontrol@britania.com.br" ,"waldir@telecontrol.com.br","ronaldo@telecontrol.com.br","sergio@telecontrol.com.br","tulio@telecontrol.com.br","paulo@telecontrol.com.br");

    		$titulo_email = "Geração de Pedidos - Telecontrol " .date('d/m/Y');

			$msg = "Foi identificado pelo nosso sistema um volume de pedidos diferente do valor parametrizado de no mínimo $media_menor e máximo $media_maior de pedidos gerados. <br>
				Data: ".date('d/m/Y')." <br>
				Quantidade de pedidos gerados: $pedido_dia <br>";

    		Log::envia_email($vet,$titulo_email,$msg);

    	}

    foreach ($pedido_fabrica as $fabrica_key => $valores_pedidos) {

    	//Verifica os some dos pedidos gerados no dia da Semana.
    	$in_pedidos = implode(",", $valores_pedidos);

    	$sql_c = "SELECT distinct exportado::date, count(pedido)
						FROM tbl_pedido
						WHERE fabrica = $fabrica
						AND exportado > current_timestamp - interval '60 days'
						AND exportado < current_date
						GROUP BY exportado::date
						ORDER BY exportado::date DESC LIMIT 15;";
		$res_c = pg_query($con,$sql_c);
		$total = array();
		if (pg_num_rows($res_c) > 0) {
			for ($i=0; $i < pg_num_rows($res_c) ; $i++) {
				$s_pedido = pg_fetch_result($res_c, $i, count);
				$total[] = $s_pedido;
			}

		}
			sort($total);
			$j=0;
			for($i=0;$i<5;$i++) {
				$total_menor += $total[$i];
				$j++;
			}
			$media_menor = $total_menor/$j*0.9;
			$media_menor = intval($media_menor);
			$total_pedido = count($total) - 1;
			$t = 0 ;
			for($i=$total_pedido;$i>$total_pedido - 5;$i--) {
				$total_maior += $total[$i];
				$t++;
			}

			$media_maior = $total_maior/$t*1.1;
			$media_maior = intval($media_maior);

		$sql_c = "SELECT count(pedido)
						FROM tbl_pedido
						WHERE fabrica = $fabrica
						AND   exportado notnull
						AND exportado > current_date ;";
		$res_c = pg_query($con,$sql_c);

		$pedido_dia = pg_fetch_result($res_c, 0, count);

    }

    	if ($pedido_dia < $media_menor or $pedido_dia > $media_maior) {

			$vet["dest"] = array("integracao.telecontrol@britania.com.br" ,"waldir@telecontrol.com.br","ronaldo@telecontrol.com.br","sergio@telecontrol.com.br","tulio@telecontrol.com.br","paulo@telecontrol.com.br");

    		$titulo_email = "Exportação de Pedidos - Telecontrol " .date('d/m/Y');

			$msg = "Foi identificado pelo nosso sistema um volume de pedidos diferente do valor parametrizado de no mínimo $media_menor e máximo $media_maior de pedidos exportados. <br>
				Data: ".date('d/m/Y')." <br>
				Quantidade de pedidos exportados: $pedido_dia <br>";

    		Log::envia_email($vet,$titulo_email,$msg);

    	}
} catch (Exception $e) {
	$msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
	Log::envia_email($vet,APP, $msg );
}

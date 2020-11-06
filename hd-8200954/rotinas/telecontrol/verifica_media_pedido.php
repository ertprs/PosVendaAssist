<?php
try {
	include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require_once dirname(__FILE__) . '/../funcoes.php';

    $dia_semana = date('N');

    //Verifica as fábricas e os tipos de pedidos válidos
    $sql_p = "SELECT tipo_pedido, tbl_fabrica.fabrica
				FROM tbl_tipo_pedido
				JOIN tbl_fabrica using(fabrica)
				WHERE ativo_fabrica = 't'
				AND (descricao ~* 'gar' or pedido_em_garantia = 't')
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
						WHERE fabrica = $fabrica_key
							AND tipo_pedido in ($in_pedidos)
							AND EXTRACT(DOW from data) = $dia_semana
							AND data > current_timestamp - interval '60 days'
							AND data < current_date
						GROUP BY data::date
						ORDER BY data::date DESC LIMIT 5;";
		$res_c = pg_query($con,$sql_c);

		$soma_pedido = 0;
		if (pg_num_rows($res_c) > 0) {
			for ($i=0; $i < pg_num_rows($res_c) ; $i++) {
				$s_pedido = pg_fetch_result($res_c, $i, count);
				$soma_pedido = $soma_pedido + $s_pedido;
			}
		}

		$soma_pedido_fabrica[$fabrica_key] = $soma_pedido;

		//Verificando a soma dos pedidos de "HOJE"
		$sql_c = "SELECT count(pedido)
						FROM tbl_pedido
						WHERE fabrica = $fabrica_key
							AND tipo_pedido in ($in_pedidos)
							AND data > current_date ;";
		$res_c = pg_query($con,$sql_c);

		$pedido_dia = pg_fetch_result($res_c, 0, count);

		$dia_pedido_fabrica[$fabrica_key] = $pedido_dia;

    	$sql_c = "SELECT distinct exportado::date, count(pedido)
						FROM tbl_pedido
						WHERE fabrica = $fabrica_key
							AND EXTRACT(DOW from exportado) = $dia_semana
							AND exportado > current_timestamp - interval '60 days'
							AND exportado < current_date
						GROUP BY exportado::date
						ORDER BY exportado::date DESC LIMIT 5;";
		$res_c = pg_query($con,$sql_c);

		$total_exportado = 0;
		if (pg_num_rows($res_c) > 0) {
			for ($i=0; $i < pg_num_rows($res_c) ; $i++) {
				$exportado = pg_fetch_result($res_c, $i, count);
				$total_exportado += $exportado;
			}
		}

		$exportado_pedido_fabrica[$fabrica_key] = $total_exportado;

		//Verificando a soma dos pedidos de "HOJE"
		$sql_c = "SELECT count(pedido)
						FROM tbl_pedido
						WHERE fabrica = $fabrica_key
							AND exportado > current_date ;";
		$res_c = pg_query($con,$sql_c);

		$exportado_dia = pg_fetch_result($res_c, 0, count);

		$dia_pedido_exportado[$fabrica_key] = $exportado_dia;
    }

    //Realiza a comparação da media dos pedidos com os gerados no dia de "HOJE"
    foreach ($soma_pedido_fabrica as $key => $value) {
    	$resultado = intval(($value / 5) * 0.8);

    	if ($resultado > $dia_pedido_fabrica[$key]) {

    		$sql_f = "SELECT nome FROM tbl_fabrica WHERE fabrica = $key;";
    		$res_f = pg_query($con,$sql_f);
    		$nome_fabrica = pg_fetch_result($res_f, 0, nome);

    		$msg .= "Quantidade de pedidos gerados abaixo da média, para a fábrica <STRONG>".$nome_fabrica."</STRONG>.<br><br>
    				Média de Pedidos: ".$resultado."<br>
    				Quantidade Gerados: ".$dia_pedido_fabrica[$key]."<br><br>";
			$envia_email_geracao = true;
    	}

	}

	foreach ($exportado_pedido_fabrica as $key => $value) {
    	$resultado = intval(($value / 5) * 0.8);

    	if ($resultado > $dia_pedido_exportado[$key]) {

    		$sql_f = "SELECT nome FROM tbl_fabrica WHERE fabrica = $key;";
    		$res_f = pg_query($con,$sql_f);
    		$nome_fabrica = pg_fetch_result($res_f, 0, nome);

    		$msge .= "Quantidade de pedidos exportados abaixo da média, para a fábrica <STRONG>".$nome_fabrica."</STRONG>.<br><br>
    				Média de Pedidos: ".$resultado."<br>
    				Quantidade Exportados: ".$dia_pedido_exportado[$key]."<br><br>";
			$envia_email_exportado = true;
    	}

	}

	$vet['dest'] = array("suporte.fabricantes@telecontrol.com.br","helpdesk@telecontrol.com.br");
	if($envia_email_geracao) {
    	$titulo_email = "Pedido Gerado Abaixo da Média - ".date('d/m/Y');
		Log::envia_email($vet,$titulo_email,$msg);
	}

	if($envia_email_exportado) {
    	$titulo_email = "Pedido Exportado Abaixo da Média - ".date('d/m/Y');
		Log::envia_email($vet,$titulo_email,$msge);
	}
} catch (Exception $e) {
	$msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
	Log::envia_email($vet,APP, $msg );
}

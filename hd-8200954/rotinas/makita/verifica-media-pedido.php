<?php
try {
	include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require_once dirname(__FILE__) . '/../funcoes.php';

    $fabrica = 42;

    $sql_p = "	SELECT tipo_pedido
				FROM tbl_tipo_pedido
				WHERE ativo = 't'
				AND (descricao ~* 'gar' or pedido_em_garantia = 't')
				AND  fabrica =  $fabrica;";
    $res_p = pg_query($con,$sql_p);

    if (pg_num_rows($res_p) > 0) {
    	for ($i=0; $i < pg_num_rows($res_p) ; $i++) {
    		$tipo_pedido  	=  pg_fetch_result($res_p, $i, tipo_pedido);
    		$tipo[] = $tipo_pedido;
    	}
    }
	$in_tipo = implode(",", $tipo);
	$sql_c = "SELECT distinct data::date, count(pedido)
					FROM tbl_pedido
					WHERE fabrica = $fabrica
						AND tipo_pedido in ($in_tipo)
						AND data > current_timestamp - interval '60 days'
						AND data < current_date
					GROUP BY data::date
					ORDER BY data::date DESC;";
	$res_c = pg_query($con,$sql_c);
	
	$array_media_por_dia = pg_fetch_all($res_c);
	$com_registro = true;
	$data = date('Y-m-d');
	$array_encontrado_data = [];

	for ($i = 0; $i <= 8 ; $i++) {
		$datas[] = $data;
		$data = date('Y-m-d', strtotime('-7 days', strtotime($data)));
	}

	foreach ($datas as $data) {
		foreach ($array_media_por_dia as $media_por_dia) {
			if($media_por_dia['data'] == $data){
				$encontrado_data = $media_por_dia;
				break;
			}
		}		
		
		if (count($encontrado_data) != 0) {
			$array_encontrado_data[] = $encontrado_data;
		}
	}
	$pedido_dia = 0;
	foreach ($array_encontrado_data as $dados_pedidos) {
		if ($dados_pedidos['data'] == date('Y-m-d')) {
			$pedido_dia =  $dados_pedidos['count'];
			continue;
		}
		$soma += $dados_pedidos['count'];
		$qtd++;
	}
	$media_ultimos = $soma/$qtd;
	$maximo = $media_ultimos * 1.10;
	$minimo = $media_ultimos * 0.90;

	if ($pedido_dia > $maximo || $pedido_dia < $minimo) {
		$vet["dest"] = ["atsbc02@makita.com.br", "atsbc03@makita.com.br", "atsbc04@makita.com.br","helpdesk@telecontrol.com.br"];
		$titulo_email = "Media Pedido - Telecontrol " .date('d/m/Y');
		$msg = "Foi identificado pelo nosso sistema um volume de pedidos diferente do valor parametrizado de no mínimo $minimo e máximo $maximo de pedidos. <br>
			Data: ".date('d/m/Y')." <br>
			Quantidade de pedidos: $pedido_dia <br>";

		Log::envia_email($vet,$titulo_email,$msg);
	}	
} catch (Exception $e) {
	$msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
	Log::envia_email($vet,APP, $msg );
}

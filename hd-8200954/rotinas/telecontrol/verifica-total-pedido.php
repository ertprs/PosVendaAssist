<?php

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require_once dirname(__FILE__) . '/../funcoes.php';


	$sql = "SELECT distinct posto
		FROM tbl_pedido
		JOIN tbl_tipo_pedido using(tipo_pedido,fabrica)
		JOIN tbl_fabrica using(fabrica)
		WHERE (parametros_adicionais ~* 'telecontrol_distrib' or fabrica in (81,114))
		AND (pedido_faturado or upper(descricao) ='FATURADO')
		AND data::date=current_date - 1
		AND tbl_pedido.posto not in (4311,20682,6359)
		AND (status_pedido <> 14 or status_pedido isnull) ;";
	$res = pg_query($con,$sql);
	for($i = 0 ; $i < pg_num_rows($res) ;$i++){
		$posto = pg_fetch_result($res,$i,'posto');
		$sql = "SELECT sum(total) as total,tbl_posto.cnpj,tbl_posto.nome,tbl_pedido.pedido,tbl_fabrica.nome as fabrica
			FROM tbl_pedido
			JOIN tbl_tipo_pedido using(tipo_pedido,fabrica)
			JOIN tbl_fabrica using(fabrica)
			JOIN tbl_posto USING(posto)
			WHERE (parametros_adicionais ~* 'telecontrol_distrib' or fabrica in (81,114))
			AND (pedido_faturado or upper(descricao) ='FATURADO')
			AND data::date between current_date - 90 and current_date - 1
			AND tbl_pedido.posto = $posto
			AND (status_pedido <> 14 or status_pedido isnull)
			GROUP BY tbl_posto.cnpj, tbl_posto.nome,tbl_pedido.pedido,tbl_fabrica.nome	;";
		$resx = pg_query($con,$sql);
		if(pg_num_rows($resx) > 0) {
			$total = pg_fetch_result($resx,0,'total');
			$cnpj = pg_fetch_result($resx,0,'cnpj');
			$nome = pg_fetch_result($resx,0,'nome');
			$fabrica = pg_fetch_result($resx,0,'fabrica');
			if($total > 499){
				for($x=0;$x<pg_num_rows($resx);$x++){
					$pedido = pg_fetch_result($resx,$x,'pedido');
					$mensagem .= "Posto $cnpj - $nome => $pedido - $fabrica <br>";

				}

			}
		}
	}

	if(strlen($mensagem) > 0 ){

		$mensagem_header = "Prezado Admin,<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;O posto autorizado realizou o pedido abaixo e o total de pedidos de compra dos últimos 90 dias ultrapassou o limite estabelecido pelo departamento de crédito Telecontrol.<br> Pedidos: <br>";
		$mensagem = $mensagem_header . " " .$mensagem;
		$headers= "From: suporte@telecontrol.com.br \nContent-type: text/html\n";
		mail('ronaldo@telecontrol.com.br,celso.velanga@telecontrol.com.br,eduardo.oliveira@telecontrol.com.br,jader.abdo@telecontrol.com.br', utf8_encode('Pedido acima de R$ 500,00'), $mensagem, $headers);
	}

} catch (Exception $e) {
	$msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
	Log::envia_email($vet,APP, $msg );
}


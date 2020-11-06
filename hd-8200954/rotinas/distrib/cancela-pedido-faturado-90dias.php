<?php

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';

	$fabrica_distrib = 63;

	/*
	* Cron Class
	*/
	$phpCron = new PHPCron($fabrica_distrib, __FILE__);
	$phpCron->inicio();

	$data 	     = date('d-m-Y-H:i:s');

	/*$sql = "select tbl_fabrica.fabrica, tbl_tipo_pedido.tipo_pedido from tbl_fabrica 
			inner join tbl_tipo_pedido on tbl_tipo_pedido.fabrica = tbl_fabrica.fabrica
			where tbl_fabrica.parametros_adicionais ilike '%fabrica_usa_distrib_telecontrol".'":"'."t%' 
			and tbl_tipo_pedido.descricao = 'Faturado'";*/
	$sql = "select tbl_fabrica.fabrica, lower(tbl_fabrica.nome) as nome, tbl_tipo_pedido.tipo_pedido from tbl_fabrica 
			inner join tbl_tipo_pedido on tbl_tipo_pedido.fabrica = tbl_fabrica.fabrica
			where tbl_fabrica.parametros_adicionais ~* 'fabrica_usa_distrib_telecontrol' 
			and tbl_tipo_pedido.pedido_faturado IS TRUE";
	$res = pg_query($con, $sql);
	for($i=0; $i<pg_num_rows($res); $i++){
		$fabrica 		= pg_fetch_result($res, $i, 'fabrica');
		$tipo_pedido 	= pg_fetch_result($res, $i, 'tipo_pedido');
		$nome_fabrica 	= pg_fetch_result($res, $i, 'nome');

		$nome_fabrica 	= str_replace(" ", "", $nome_fabrica);

		$itens_cancelados 			= "";
		$itens_cancelados_total 	= "";
		$$msg 						= "";

		
		$sql_1 = "SELECT
					DISTINCT
					tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome AS posto_nome,
					tbl_pedido.posto,
					tbl_pedido.pedido,
					TO_CHAR(tbl_pedido.finalizado, 'dd/mm/YYYY') AS finalizado

					FROM
					tbl_pedido
					JOIN tbl_pedido_item USING(pedido)
					JOIN tbl_posto_fabrica USING(posto, fabrica)
					JOIN tbl_posto ON tbl_posto_fabrica.posto=tbl_posto.posto

					WHERE
					fabrica = $fabrica
					AND tipo_pedido = $tipo_pedido
					AND qtde <> qtde_faturada_distribuidor+qtde_cancelada
					AND finalizado IS NOT NULL
					AND finalizado + INTERVAL '90 day' < NOW()
					AND tbl_pedido.distribuidor = 4311";
		$res_1 = pg_query($con, $sql_1);
		if(pg_num_rows($res_1)>0){
			for($a=0; $a<pg_num_rows($res_1); $a++){

				$res_begin = pg_query($con,"BEGIN");

				$codigo_posto 	= pg_fetch_result($res_1, $a, 'codigo_posto');
				$nome_posto 	= pg_fetch_result($res_1, $a, 'nome_posto');
				$posto 			= pg_fetch_result($res_1, $a, 'posto');
				$pedido 		= pg_fetch_result($res_1, $a, 'pedido');
				$finalizado 	= pg_fetch_result($res_1, $a, 'finalizado');

				$sql_2 = "SELECT
					pedido_item,
					tbl_peca.referencia,
					tbl_peca.descricao,
					qtde-qtde_faturada_distribuidor-qtde_cancelada AS qtde_pendente

					FROM
					tbl_pedido_item
					JOIN tbl_peca ON tbl_pedido_item.peca=tbl_peca.peca
					
					WHERE
					tbl_pedido_item.pedido=$pedido
					AND qtde <> qtde_faturada_distribuidor+qtde_cancelada
					";
				$res_2 = pg_query($con, $sql_2);
				if(pg_num_rows($res_2) >0){
					for($b=0; $b<pg_num_rows($res_2); $b++){
						$pedido_item 		= pg_fetch_result($res_2, $b, 'pedido_item');
						$referencia			= pg_fetch_result($res_2, $b, 'referencia');
						$descricao			= pg_fetch_result($res_2, $b, 'descricao');
						$qtde_pendente		= pg_fetch_result($res_2, $b, 'qtde_pendente');

						if ($cor == "#CCCCCC") {
							$cor = "#FFFFFF";
						}
						else {
							$cor = "#CCCCCC";
						}

						$itens_cancelados .= "<tr bgcolor=$cor><td>$referencia - $descricao</td><td>$qtde_pendente</td></tr>";

						$log = "Pedido: $pedido | Item cancelado: $pedido_item - $qtde_pendente X $referencia - $descricao";
						$log .= "\n";


						$sql_3 = "INSERT INTO
								tbl_pedido_cancelado(pedido, posto, fabrica, peca, qtde, motivo, data)

								SELECT
								tbl_pedido.pedido,
								tbl_pedido.posto,
								tbl_pedido.fabrica,
								tbl_peca.peca,
								qtde-qtde_faturada_distribuidor-qtde_cancelada,
								'Cancelado pois excedeu o prazo de atendimento: 60 dias',
								current_date

								FROM
								tbl_pedido_item
								JOIN tbl_pedido ON tbl_pedido_item.pedido=tbl_pedido.pedido
								JOIN tbl_peca ON tbl_pedido_item.peca=tbl_peca.peca

								WHERE
								tbl_pedido_item.pedido_item=$pedido_item";
						$res_3 = pg_query($con, $sql_3);					

						if(strlen(trim(pg_last_error($con)))>0){
							$erro .= "Falha ao gravar na tbl_pedido_cancelado ". pg_last_error($con)."<br><br>";
						}

						$sql_4 = "UPDATE
						tbl_pedido_item						
						SET
						qtde_cancelada=qtde-qtde_faturada_distribuidor						
						WHERE 
						pedido_item=$pedido_item";
						$res_4 = pg_query($con, $sql_4);

						if(strlen(trim(pg_last_error($con)))>0){
							$erro   .= "Falha ao atualizar o pedido item ". pg_last_error($con)."<br><br>";;
						}
					}
				}else{
					$erro .= "Nenhum Pedido Item encontrado. <br><br>";
				}

				$itens_cancelados_total .= "<tr bgcolor=#444444><td colspan=2 align=center><font color=#FFFFFF>Pedido $pedido ($finalizado) - $codigo_posto - $posto_nome</font></td></tr>$itens_cancelados";

				$mensagem = "<font face=arial color=#000000 size=1><b>Informamos que alguns itens do pedido de compra $pedido, finalizado em $finalizado, foram cancelados devido a ter expirado o prazo para atendimento. Caso ainda necessite das peças, inseri-las novamente em um novo pedido</b>. Segue abaixo a lista com os itens cancelados:<table border=1 width=100% style=font-size:8pt;><tr><td>Componente</td><td>Qtde Cancelada</td></tr>$itens_cancelados</table></font>";

				$sql_5 = "INSERT INTO
				tbl_comunicado (
					mensagem,
					tipo,
					fabrica,
					posto,
					obrigatorio_site,
					ativo
				)

				VALUES (
					'$mensagem',
					'Comunicado',
					$fabrica,
					$posto,
					't',
					't'
				)";
				$res_5 = pg_query($con, $sql_5);			

				if(strlen(trim(pg_last_error($con)))>0){
					$erro .= "Falha ao gravar Comunicado ".pg_last_error($con)."<br><br>";
				}

				$sql_6 = "SELECT fn_atualiza_status_pedido($fabrica, $pedido)";
				$res_6 = pg_query($con, $sql_6);

				if(strlen(trim(pg_last_error($con)))>0){
					$erro .= "Falha ao atualizar status pedido ".pg_last_error($con)."<br><br>";;
				}		

			}
		}

		$itens_cancelados_total = "<table border=1 width=100%>" . $itens_cancelados_total . "</table>";

		if(strlen(trim($erro)) > 0){

			$msg = "Ocorreu um erro na rotina que cancela os pedidos faturados não atendidos com mais de 90 dias\n\n";
			$msg .= $erro;

			$header  = "MIME-Version: 1.0\n";
	        $header .= "Content-type: text/html; charset=iso-8859-1\n";
	    	$header .= "From: Telecontrol <telecontrol\@telecontrol.com.br>";
	    	$header .= "<font face='arial' color='#000000' size='2'>\n";	
		
			mail("helpdesk@telecontrol.com.br", "$nome_fabrica - TELECONTROL / Erros ao cancelar pedidos faturados 90 dias ({$data}) ", $msg, $header);
			
			$fp = fopen("/tmp/$nome_fabrica/$nome_fabrica-telecontrol-pedido-cancelado-90dias-$data.err","w");
			fwrite($fp, $erro);
			fclose($fp);
	    }else{

	    	$header  = "MIME-Version: 1.0\n";
	        $header .= "Content-type: text/html; charset=iso-8859-1\n";
	    	$header .= "From: Telecontrol <telecontrol\@telecontrol.com.br>";
	    	$header .= "<font face='arial' color='#000000' size='2'>\n";	
		
	    	$msg = "Prezado administrador,<br><br>Alguns itens de pedidos foram cancelados por excederem o prazo para atendimento. Segue abaixo relação:\n\n";

	    	$msg .= $itens_cancelados_total;


			mail("eduardo.miranda@telecontrol.com.br,eduardo.oliveira@telecontrol.com.br,luis.carlos@telecontrol.com.br,jader.abdo@telecontrol.com.br, helpdesk@telecontrol.com.br", "$nome_fabrica - TELECONTROL / Cancelamento de pedidos faturados 90 dias ({$data}) ", $msg, $header);

	    }

		if(strlen(trim($erro))>0){
			$res_begin = pg_query($con,"ROLLBACK");	
		}else{
			$res_begin = pg_query($con,"COMMIT");
		}

	}

/*
* Cron Término
*/
$phpCron->termino();

?>

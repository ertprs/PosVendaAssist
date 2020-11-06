<?php

	include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';
    require dirname(__FILE__) . '/../../funcoes.php';

	if ($_serverEnvironment == "production") {
            define("ENV", "prod");
    } else {
            define("ENV", "dev");
    }

	if (!empty($argv[1])) {
		$login_fabrica = $argv[1];

		$cond_fabrica  = " AND tbl_fabrica.fabrica = $login_fabrica";
	}

	$msg_erro      = array();

	function buscaHistorico($fabrica, $fats, $objetos, $fabrica_tc) {
		
		global $con; 
		$metodo 		= "buscaEventosLista";
		$soap = new SoapClient("http://webservice.correios.com.br/service/rastro/Rastro.wsdl", array("trace" => 1, "connection_timeout" => 30,
			'stream_context'=>stream_context_create(
				array('http'=>
				array(
					'protocol_version'=>'1.0',
					'header' => 'Connection: Close'
					)
				)
			)
		));

		$buscaEventos 	= (object) array("usuario" => "9912358441", "senha" => "P?WPP?VZ@O", "tipo" => "L", "resultado" => "T", "lingua" => 101,"objetos" => $objetos);
		sleep(1);
		$soapResult = $soap->__soapCall($metodo, array($buscaEventos));

		//necessário para buscar os dados de rastreio da menor data para a maior data
		$arrayEventosOrdenado = array_reverse($soapResult->return->objeto->evento);
		$soapResult->return->objeto->evento = $arrayEventosOrdenado;

		if(empty($arrayEventosOrdenado)){
			$arrayEventosOrdenado = array_reverse($soapResult->return->objeto);
			$soapResult->return->objeto = $arrayEventosOrdenado;	
		
		}


		foreach ($soapResult->return->objeto as $objetos) {
			$obs = "";

			$cod_conhecimento = $objetos->numero;

			foreach($objetos->evento as  $linha) {
				$local 		= $linha->local;
				$data 		= $linha->data;
				$hora 		= $linha->hora;
				$situacao 	= $linha->descricao;
				$cidade 	= $linha->cidade;

				if(empty($data)) continue;

				$local = $cidade." - ".$local;

				list($d, $m, $y) 	= explode("/", $data);
				$data 				= $y."-".$m."-".$d;
				$dataHora 			= $data ." ".$hora;

				if(strlen(trim($linha->comentario))>0){
					$obs 		= $linha->comentario;
				}

				if(isset($linha->destino)) {
					$destinoLocal  	= $linha->destino->local;
					$destinoCidade	= $linha->destino->cidade;
					$destinoCodigo	= $linha->destino->codigo;
					$destinoUf		= $linha->destino->uf;

					$obs .= "Código: $destinoCodigo Encaminhado para ".$destinoLocal."/".$destinoCidade."-".$destinoUf;
				}

				$faturamento = $fats[$cod_conhecimento];

				$sql_verifica = "SELECT data FROM tbl_faturamento_correio
					WHERE data = '$dataHora'
					AND fabrica = $fabrica
					AND conhecimento = '$cod_conhecimento' ";
				$res_verifica = pg_query($con, $sql_verifica);
				
					if(pg_num_rows($res_verifica)==0){
						if(!empty($faturamento) and $faturamento > 0) {
							if (in_array($fabrica, array(160))) {

								if (strpos($situacao, 'Objeto entregue') !== false) {

									$sql_recebimento = "UPDATE tbl_faturamento 
										SET conferencia = '$dataHora' 
										WHERE fabrica = 10
										AND faturamento = $faturamento";
									$res_recebimento = pg_query($con, $sql_recebimento);

								}

							}

							$sql_grava_rastreio = "INSERT INTO tbl_faturamento_correio (fabrica, local, conhecimento, faturamento, situacao, data, obs,numero_postagem)
								VALUES ($fabrica, '$local', '$cod_conhecimento', $faturamento, '$situacao', '$dataHora', '$obs', ' ' )";
							$res_grava_rastreio = pg_query($con, $sql_grava_rastreio);
							if($fabrica_tc == 't') {
								$sql_con = "UPDATE tbl_faturamento set conhecimento='$cod_conhecimento' where faturamento = $faturamento and conhecimento isnull ";
								pg_query($con, $sql_con);
							}
						}else{
							$sql_grava_rastreio = "INSERT INTO tbl_faturamento_correio (fabrica, local, conhecimento, situacao, data, obs,numero_postagem)
								VALUES ($fabrica, '$local', '$cod_conhecimento', '$situacao', '$dataHora', '$obs', ' ' )";
							$res_grava_rastreio = pg_query($con, $sql_grava_rastreio);
						}
					}

					if ($fabrica == 151 AND strpos($situacao, 'Objeto entregue') !== false and !empty($faturamento)) {

						$sql = "SELECT DISTINCT tbl_hd_chamado_item.hd_chamado, 
								tbl_pedido.troca, 
								tbl_hd_motivo_ligacao.descricao AS providencia,
								tbl_hd_chamado_extra.email      AS consumidor_email, 
								tbl_hd_chamado_extra.celular    AS consumidor_celular, 
								tbl_hd_chamado_extra.nome       AS consumidor_nome,
								tbl_hd_chamado.status
							FROM tbl_faturamento_item
							JOIN tbl_pedido ON tbl_pedido.pedido = tbl_faturamento_item.pedido AND tbl_pedido.troca IS TRUE AND tbl_pedido.fabrica = {$fabrica}
							JOIN tbl_os_item ON tbl_os_item.pedido = tbl_pedido.pedido
							JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
							JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.os = tbl_os_produto.os
							JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado 
							JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado 
							JOIN tbl_hd_motivo_ligacao ON tbl_hd_motivo_ligacao.hd_motivo_ligacao = tbl_hd_chamado_extra.hd_motivo_ligacao
							WHERE tbl_faturamento_item.faturamento = {$faturamento}
							UNION
							SELECT DISTINCT tbl_hd_chamado.hd_chamado, 
								tbl_pedido.troca, 
								tbl_hd_motivo_ligacao.descricao AS providencia,
								tbl_hd_chamado_extra.email      AS consumidor_email, 
								tbl_hd_chamado_extra.celular    AS consumidor_celular, 
								tbl_hd_chamado_extra.nome       AS consumidor_nome,
								tbl_hd_chamado.status
							FROM tbl_faturamento_item
							JOIN tbl_pedido ON tbl_pedido.pedido = tbl_faturamento_item.pedido AND tbl_pedido.tipo_pedido = 329 AND tbl_pedido.fabrica = {$fabrica}
							JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.pedido = tbl_pedido.pedido
							JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado  
							JOIN tbl_hd_motivo_ligacao ON tbl_hd_motivo_ligacao.hd_motivo_ligacao = tbl_hd_chamado_extra.hd_motivo_ligacao
							WHERE tbl_faturamento_item.faturamento = {$faturamento}";
						$res = pg_query($con, $sql);

						if (pg_num_rows($res) > 0) {
							
							$hd_chamado        = pg_fetch_result($res,0,"hd_chamado");
							$troca             = pg_fetch_result($res,0,"troca");
							$status            = pg_fetch_result($res,0,"status");
							$providencia_atual = pg_fetch_result($res,0,"providencia");
							$tipo_pedido       = pg_fetch_result($res,0,"tipo_pedido");
							$login_fabrica = $fabrica;

		
							require_once __DIR__ . '/../../class/ComunicatorMirror.php';
							require_once __DIR__ . '/../../class/sms/sms.class.php';
							require_once __DIR__ . '/../funcoes.php';
						
							$ComunicatorMirror = new ComunicatorMirror();
		
							$tipo_os = ($troca == "t") ? "PRODUTO ENTREGUE" : "PEÇA ENTREGUE";

							$query = "SELECT hd_motivo_ligacao, 
											descricao, 
											texto_email, 
											texto_sms
								  FROM tbl_hd_motivo_ligacao 
								  WHERE fabrica = {$fabrica} 
								  AND trim(descricao) like '" . $tipo_os . "%'";

							$res_providencia = pg_query($con,$query);

							if (pg_num_rows($res_providencia) > 0) {
								
								$hd_motivo_ligacao = pg_fetch_result($res_providencia, 0,"hd_motivo_ligacao");

								$nova_providencia  = pg_fetch_result($res_providencia, 0,"descricao");

								$texto_sms         = pg_fetch_result($res_providencia, 0,"texto_sms");

								$texto_email       = pg_fetch_result($res_providencia, 0,"texto_email");
								
								$consumidor_nome   = pg_fetch_result($res, 0, "consumidor_nome");

								$consumidor_email  = pg_fetch_result($res,0,"consumidor_email");

								$consumidor_celular = pg_fetch_result($res,0,"consumidor_celular");
								
								$numero_objeto = $cod_conhecimento;

								if (strlen($texto_email) > 0 && strlen($consumidor_email) > 0) {

									$texto_email_admin = textoProvidencia_new($texto_email,$hd_chamado, $consumidor_nome, $numero_objeto, $fabrica);
								}

								if (strlen($texto_sms) > 0 && strlen($consumidor_celular) > 0) {

									$texto_sms_admin = textoProvidencia_new($texto_sms,  $hd_chamado, $consumidor_nome, $numero_objeto, $fabrica);
								}

								############################################################
								# Ao constar o objeto entregue, o sistema dever interagir no protocolo com a interação.
 
								$sql = "UPDATE tbl_hd_chamado_extra 
										SET hd_motivo_ligacao = {$hd_motivo_ligacao} 
										WHERE hd_chamado = {$hd_chamado}";

								$res = pg_query($con,$sql);

								$comentario = 'Providencia alterada de ' . $providencia_atual . ' para ' . $nova_providencia;

								$sql = "INSERT INTO tbl_hd_chamado_item(hd_chamado,comentario,status_item,interno)
								VALUES ($hd_chamado, '$comentario', '$status', true)";

								$res = pg_query($con, $sql);
								$dataChegada = "SELECT TO_CHAR(data::DATE,'dd/mm/yyyy hh:mm') AS data
												FROM tbl_faturamento_correio 
												WHERE conhecimento LIKE '$cod_conhecimento' 
												AND situacao ILIKE '%Objeto entregue%'
												LIMIT 1";

								$resDataChegada = pg_query($con,$dataChegada);

								$dataChegada = pg_fetch_result($resDataChegada, 0, data);

								$comentario = $dataChegada . ' Objeto entregue ao destinatario';
								 
								$sql = "INSERT INTO tbl_hd_chamado_item(hd_chamado,comentario,status_item,interno)
								VALUES ($hd_chamado, '$comentario', 'Resolvido', true)";

								$res = pg_query($con, $sql);

								## ver se esta entrege, se sim, muda a providencia,
								$sql = "UPDATE tbl_hd_chamado SET status = 'Resolvido' WHERE hd_chamado = {$hd_chamado}";
								$res = pg_query($con, $sql);
								
								##################################################################
								try{
									$sms = new SMS($fabrica);

									$sms->enviarMensagem($consumidor_celular, $os_troca, date("d/m/Y"), $texto_sms_admin, $hd_chamado);

									$ComunicatorAccount = 'noreply@tc';

									$ComunicatorMirror->post(
										$consumidor_email,
										utf8_encode("Atendimento: " . $hd_chamado),
										utf8_encode($texto_email_admin),
										$ComunicatorAccount
									);
								}catch(Exception $e) {
									continue;
								}

							}
							
						}

					}

				
			}

			if ($fabrica_tc == 't' && !in_array($fabrica, array(147,153))) {
				$sql_entregue = "SELECT faturamento FROM tbl_faturamento_correio WHERE conhecimento = '$cod_conhecimento' AND fabrica = $fabrica AND situacao ~ 'Objeto entregue'";
				$res_entregue = pg_query($con, $sql_entregue);

				$atualiza_checkpoint = [];

				if (pg_num_rows($res_entregue) > 0) {
					$atualiza_checkpoint = [
						'faturamento' => pg_fetch_result($res_entregue, 0, 'faturamento'),
						'status_id' => '3',
						'status_desc' => 'Aguardando Conserto'
					];
				} else {
					$sql_em_transito = "SELECT faturamento FROM tbl_faturamento_correio WHERE conhecimento = '$cod_conhecimento' AND fabrica = $fabrica AND situacao ~ 'Objeto postado'";
					$res_em_transito = pg_query($con, $sql_em_transito);

					if (pg_num_rows($res_em_transito) > 0) {
						$atualiza_checkpoint = [
							'faturamento' => pg_fetch_result($res_em_transito, 0, 'faturamento'),
							'status_id' => '36',
							'status_desc' => 'Em transito'
						];
					}
				}

				if (!empty($atualiza_checkpoint)) {
					$faturamento = $atualiza_checkpoint['faturamento'];
					$status_id = $atualiza_checkpoint['status_id'];
					$status_desc = $atualiza_checkpoint['status_desc'];

					$sql_os_faturamento = "
						   SELECT DISTINCT tbl_os.os
						   FROM tbl_faturamento_item
						   JOIN tbl_faturamento ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
						   AND tbl_faturamento.faturamento = {$faturamento}
						   JOIN tbl_os ON tbl_faturamento_item.os = tbl_os.os
						   WHERE tbl_faturamento.faturamento = {$faturamento}
						   AND tbl_faturamento.fabrica in (10, {$fabrica})
						   AND tbl_os.finalizada IS NULL
						   AND tbl_os.status_checkpoint NOT IN ({$status_id},4,9)";
					$res_os_faturamento = pg_query($con, $sql_os_faturamento);

					while ($fat = pg_fetch_array($res_os_faturamento)) {
						$os = $fat['os'];
						atualiza_status_checkpoint($os, $status_desc);
					}
				}
			}
		}
	}
	
	$sql_senha = "select fabrica, fabrica_tc from (SELECT distinct fabrica,case when parametros_adicionais ~ 'telecontrol_distrib' then true else false end as fabrica_tc FROM tbl_fabrica_correios join tbl_fabrica using(fabrica) WHERE ativo and ativo_fabrica $cond_fabrica
				union
				select distinct fabrica, false as fabrica_tc from tbl_faturamento join tbl_fabrica using(fabrica)  where ativo_fabrica and conhecimento ~'BR' and fabrica not in (select fabrica from tbl_fabrica_correios) and fabrica <> 10 $cond_fabrica AND emissao >= CURRENT_DATE - interval '3 months' ) f   order by fabrica_tc desc";
	$res_senha = pg_query($con, $sql_senha);

	for($a=0; $a<pg_num_rows($res_senha); $a++){
		$fabrica 	= pg_fetch_result($res_senha, $a, 'fabrica');
		$fabrica_tc	= pg_fetch_result($res_senha, $a, 'fabrica_tc');

		//pegar código de rastreio.
		if($fabrica_tc == 't') {
			$sql_rastreio = "SELECT distinct conhecimento, faturamento
							FROM tbl_faturamento
							JOIN tbl_faturamento_item USING(faturamento)
							JOIN tbl_peca USING(peca)
							WHERE tbl_faturamento.fabrica in ($fabrica,10)
							AND tbl_peca.fabrica  = $fabrica
							AND conhecimento ~'BR'
							and faturamento not in (select faturamento from tbl_faturamento_correio where (situacao ~'entregue' or situacao ~'roubado' or situacao ~'devolvido') and faturamento notnull )
							AND emissao >= CURRENT_DATE - interval '3 months'
							union
							select distinct tbl_etiqueta_servico.etiqueta as conhecimento, faturamento
							 from tbl_etiqueta_servico
							 join tbl_embarque using(embarque)
							 JOIN tbl_faturamento using(embarque) 
							JOIN tbl_faturamento_item USING(faturamento)
							JOIN tbl_peca USING(peca)
							where   faturamento not in (select faturamento from tbl_faturamento_correio where (situacao ~'entregue' or situacao ~'roubado' or situacao~ 'devolvido') and faturamento notnull )
							and tbl_peca.fabrica =$fabrica 
							AND emissao >= CURRENT_DATE - interval '3 months'
							and conhecimento isnull";

		}elseif($fabrica == 151) {
			$sql_rastreio = "SELECT json_array_elements_text(conhecimento::json) as conhecimento, faturamento into temp tmp_mondial FROM tbl_faturamento
								JOIN tbl_faturamento_item USING(faturamento)
								WHERE fabrica = $fabrica
								AND conhecimento ~'BR'
								AND conhecimento !~'http'
								AND faturamento NOT IN (select faturamento from tbl_faturamento_correio where (situacao ~'entregue' or situacao ~'roubado' or situacao ~ 'devolvido') and faturamento notnull  and fabrica  = $fabrica)
								AND emissao >= CURRENT_DATE - interval '2 months' order by random() ; 
						delete from tmp_mondial using tbl_faturamento_correio f where f.conhecimento = tmp_mondial.conhecimento and situacao ~'entregue' and fabrica = $fabrica;
						select * from tmp_mondial; ";
	
		}elseif($login_fabrica == 80){
			$sql_rastreio = "SELECT conhecimento, faturamento FROM tbl_faturamento
				         WHERE fabrica = $fabrica
				         AND conhecimento ~'BR'
				         AND conhecimento !~'http'
				         AND faturamento not in (select faturamento from tbl_faturamento_correio where (situacao ~'entregue' or situacao ~'roubado' or situacao ~ 'devolvido') and faturamento notnull and fabrica = $fabrica )
					 AND emissao >= CURRENT_DATE - interval '2 months' 
					 UNION
					 SELECT tbl_os_extra.pac AS conhecimento, 0 AS faturamento
					 FROM tbl_os_extra
					 JOIN tbl_os USING(os)
					 WHERE tbl_os.fabrica = $fabrica
					 AND tbl_os.excluida IS NOT TRUE
					 AND tbl_os.data_abertura >= CURRENT_DATE - INTERVAL '2 months'
					 AND tbl_os_extra.pac IS NOT NULL
					 AND tbl_os_extra.pac not in (select conhecimento from tbl_faturamento_correio where (situacao ~'entregue' or situacao ~'roubado' or situacao ~ 'devolvido') and conhecimento = tbl_os_extra.pac and fabrica = $fabrica )
					 ";
		}else{
			$sql_rastreio = "SELECT conhecimento, faturamento FROM tbl_faturamento
								WHERE fabrica = $fabrica
								AND conhecimento ~'BR'
								AND conhecimento !~'http'
								and faturamento not in (select faturamento from tbl_faturamento_correio where (situacao ~'entregue' or situacao ~'roubado' or situacao ~ 'devolvido') and faturamento notnull and fabrica = $fabrica )
								AND emissao >= CURRENT_DATE - interval '2 months' order by random()";
		}

		$res_rastreio = pg_query($con, $sql_rastreio);
		$fat_objeto = array();
		$conhecimentos = array();

		for($i=0; $i<pg_num_rows($res_rastreio); $i++){
			$conhecimento 	= pg_fetch_result($res_rastreio, $i, conhecimento);
			$faturamento 	= pg_fetch_result($res_rastreio, $i, faturamento);
			
			if($fabrica == 35) {
				$conhecimento = str_replace(" ", "", $conhecimento);
				$tratar = 	explode("BR",$conhecimento); 
				foreach($tratar as $objetos) {
					if(strlen($objetos) > 0) {
						$objetos = $objetos."BR";
						if(!in_array($objetos, $conhecimentos)) {
							$conhecimentos[] = $objetos;
							$fat_objeto[$objetos] = $faturamento;
						}
					}
				}
		
			}else{
				if (preg_match("/^\[.+\]$/", $conhecimento)) {
					$conhecimento 	= json_decode($conhecimento, true);
				}else{
					if(!in_array($conhecimento, $conhecimentos)) {
						$conhecimentos[] = $conhecimento;
						$fat_objeto[$conhecimento] = $faturamento;
					}
				}
			}

			$conhecimentos = array_unique($conhecimentos);
			$fat_objeto = array_unique($fat_objeto);
			
			foreach($fat_objeto as $objs =>$fat) {
				$sql_verifica = "SELECT data FROM tbl_faturamento_correio
					WHERE situacao ~*'entregue'
					AND fabrica = $fabrica
					AND conhecimento = '$objs' ";

				$res_verifica = pg_query($con, $sql_verifica);

				if(pg_num_rows($res_verifica) > 0){
					unset($fat_objeto[$objs]);
				}
			}
			if(count($fat_objeto) == 0) continue;
			$j = $i+1;
			if(count($fat_objeto) == 20 or $j == pg_num_rows($res_rastreio)) {
				buscaHistorico($fabrica, $fat_objeto, $conhecimentos, $fabrica_tc);
				$fat_objeto = array();
				$conhecimentos = array();

			}
		}

	}

?>

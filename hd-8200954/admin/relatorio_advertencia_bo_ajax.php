<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include "autentica_admin.php";

include 'funcoes.php';



# Fábricas que tem permissão para esta tela
if(!in_array($login_fabrica, array(1))) {
	header("Location: menu_callcenter.php");
	exit;
}

if($_SERVER["REQUEST_METHOD"] == "POST") {

	$post = (object) $_POST;

	pg_query($con, "BEGIN TRANSACTION");

	# Verifica se a requisição foi uma interação
	if($post->acao == "interagir") {

		if($post->status == "finalizar" && !empty($post->data_finalizada)) {
			list($dia, $mes, $ano) = explode("/", $post->data_finalizada);
			$post->data_finalizada = "'$ano-$mes-$dia'";
		} else {
			$post->data_finalizada = "null";
		}

		$parametros_add = ["nivel_falha"=>$post->nivel_falha];

		if (!empty($post->acao_bo)) {
			$parametros_add['acao_bo'] = $post->acao_bo;
		}

		if (!empty($post->outros_explicacao)) {
			$parametros_add['outros_explicacao'] = (mb_check_encoding($post->outros_explicacao, "UTF-8")) ? $post->outros_explicacao : utf8_encode($post->outros_explicacao);
		}

		$parametros_add = json_encode($parametros_add);
		$parametros_add = str_replace("\\u", "\\\\u", $parametros_add);
		$sql = "UPDATE tbl_advertencia
				SET data_concluido = $post->data_finalizada,
					parametros_adicionais = coalesce(parametros_adicionais, '{}') || '$parametros_add'
				WHERE advertencia  = $post->advertencia;

				INSERT INTO tbl_advertencia_item (advertencia,
												  admin,
												  texto)
				VALUES ($post->advertencia,
						$login_admin,
						'$post->texto')";

		$res = pg_query($con, $sql);

		if(strlen(pg_last_error())) {
			print "Não foi possível gravar a interação. Favor entrar em contato com a Telecontrol.<br/>" . pg_last_error();
			pg_query($con, "ROLLBACK TRANSACTION");
		} else {
			pg_query($con, "COMMIT TRANSACTION");
		}

		 exit;

	} else if($post->acao == "procura_posto"){

		$sql = "SELECT tbl_posto_fabrica.posto, tbl_posto.nome as posto_nome
				FROM tbl_posto_fabrica
				JOIN tbl_posto USING (posto)
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica
				AND
				admin_sap = {$post->id}";

		$res = pg_query($con, $sql);

		$result_sap = pg_fetch_all($res);
		if(count($result_sap) > 0){

			for($i=0;$i<count($result_sap);$i++){
				$options .= "<option value='".$result_sap[$i]['posto']."'>".$result_sap[$i]['posto_nome']."</option>";
			}

			$resultado = $options;

		}
	} else {

		$post->data_inicial = preg_replace('/^(\d{2}).(\d{2}).(\d{4})$/', '$3-$2-$1', $post->data_inicial);
		$post->data_final 	= preg_replace('/^(\d{2}).(\d{2}).(\d{4})$/', '$3-$2-$1', $post->data_final);

		$nao_possui_data = empty($post->data_inicial) && empty($post->data_final);


		if($post->admin_sap != ""){
			$where_admin = " and tbl_posto_fabrica.admin_sap = ".$post->admin_sap;
		}else{
			$where_admin = "";
		}

		if($post->posto_sap != ""){
			$where_posto = " and tbl_posto_fabrica.posto = ".$post->posto_sap;

		}else{
			$where_posto = "";
		}

		 $sql = "SELECT posto
		 		FROM tbl_posto_fabrica
		 		WHERE codigo_posto = '$post->codigo_posto'
		 		AND fabrica = 1";

		$res = pg_query($con, $sql);
		$id_posto = pg_fetch_result($res, 0, 0);

		$sql = "SELECT advertencia,
					   to_char (tbl_advertencia.data_input, 'DD/MM/YYYY') as data_input,
					   to_char (tbl_advertencia.data_concluido, 'DD/MM/YYYY') as data_concluido,
					   tbl_posto_fabrica.codigo_posto,
					   tbl_posto.nome as posto_nome,
					   tbl_tipo_posto.descricao AS tipo_posto_desc,
					   tbl_tipo_ocorrencia.descricao,
					   tbl_admin.nome_completo,
					   tbl_advertencia.numero_sac,
					   tbl_advertencia.numero_advertencia,
					   tbl_advertencia.tipo_ocorrencia,
					   tbl_advertencia.contato_posto,
					   tbl_produto.referencia || '-' || tbl_produto.descricao AS produto_descricao,
					   tbl_os.os,
					   tbl_os.sua_os,
					   posto_os.codigo_posto AS codigo_posto_os,
					   tbl_advertencia.parametros_adicionais->>'nivel_falha' AS nivel_falha,
					   tbl_advertencia.parametros_adicionais->>'tipo_falha' AS tipo_falha,
					   tbl_advertencia.parametros_adicionais->>'tratativa_atendimento' AS tratativa_atendimento,
					   (SELECT count(1) FROM tbl_advertencia a WHERE a.posto = tbl_advertencia.posto AND a.fabrica = tbl_advertencia.fabrica) AS total_registros,
					   tbl_advertencia.parametros_adicionais->>'acao_bo' AS acao_bo,
					   tbl_advertencia.parametros_adicionais->>'outros_explicacao' AS outros_explicacao 
				FROM tbl_advertencia
				INNER JOIN tbl_posto_fabrica
					ON (tbl_advertencia.posto = tbl_posto_fabrica.posto AND tbl_advertencia.fabrica = tbl_posto_fabrica.fabrica)
				INNER JOIN tbl_posto
					ON (tbl_posto_fabrica.posto = tbl_posto.posto)
				LEFT JOIN tbl_os
					ON(tbl_advertencia.os = tbl_os.os)
				LEFT JOIN tbl_posto_fabrica AS posto_os
					ON (tbl_os.posto = posto_os.posto AND tbl_os.fabrica = posto_os.fabrica)
				LEFT JOIN tbl_tipo_posto 
					ON (tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto)
				LEFT JOIN tbl_produto
					ON(tbl_advertencia.produto = tbl_produto.produto)
				INNER JOIN tbl_admin
					ON (tbl_admin.admin = tbl_advertencia.admin)
				LEFT JOIN tbl_tipo_ocorrencia
					ON (tbl_tipo_ocorrencia.tipo_ocorrencia = tbl_advertencia.tipo_ocorrencia)
				WHERE " . (!$nao_possui_data ? "tbl_advertencia.data_input BETWEEN '$post->data_inicial 00:00:00' AND '$post->data_final 23:59:59'   ".$where_admin. $where_posto  :  "1=1");


		if(trim($post->advertencia) 	!= "") $sql .= "AND tbl_advertencia.advertencia  	= $post->advertencia ";
		if(trim($post->codigo_posto) 	!= "") $sql .= "AND tbl_advertencia.posto  			= $id_posto";
		if(trim($post->tipo_ocorrencia) != "") $sql .= "AND tbl_advertencia.tipo_ocorrencia = $post->tipo_ocorrencia ";
		if(trim($post->atendente) 		!= "") $sql .= "AND tbl_advertencia.admin 			= $post->atendente ";
		if(trim($post->statuss) 			!= "") $sql .= "AND tbl_advertencia.data_concluido	IS " . ($post->statuss ? "NOT NULL " : "NULL ");

		if(trim($post->tipo_relatorio) == "advertencia") :
			$sql .= "AND tbl_advertencia.tipo_ocorrencia IS NULL ";
		elseif(trim($post->tipo_relatorio) == "boletim") :
			$sql .= "AND tbl_advertencia.tipo_ocorrencia IS NOT NULL ";
		endif;
		$sql.= " AND tbl_advertencia.fabrica = $login_fabrica ";

		if ($post->nivel_falha != "") {
			$sql.= " AND tbl_advertencia.parametros_adicionais->>'nivel_falha' = '$post->nivel_falha' ";
		}

		if ($post->tipo_falha != "") {
			$sql.= " AND tbl_advertencia.parametros_adicionais->>'tipo_falha' = '$post->tipo_falha' ";
		}

		if ($post->tratativa_atendimento != "") {
			$sql.= " AND tbl_advertencia.parametros_adicionais->>'tratativa_atendimento' = '$post->tratativa_atendimento' ";
		}

		$sql .= " ORDER BY advertencia DESC,tbl_posto.nome, data_input";

		$res = pg_query($con, $sql);

		# Separa os resultados por posto em um array
		while($advertencia = pg_fetch_object($res)) {
			$advertencias[$advertencia->codigo_posto][] = $advertencia;
		}

		# Se houver resultados
		if(count($advertencias)) {

            $arrayNivelFalha = ["leve"=>"Leve", "medio"=>"Médio", "alto"=>"Alto"];
            $arrayTratativa  = ["devolucao"=>"Devolução de Valor", "reparo"=>"Reparo", "troca"=>"Troca do Produto"];
            $arrayFalha      = ["duvida_tecnica"=>"Falta de Comunicação C/ o Suporte Ref. à Dúvidas Técnicas", 
            					"pendencia_peca"=>"Falta de Comunicação C/ o Suporte Ref. à Pendência de Peça",
            					"telecontrol"=>"Falta de Comunicação C/ o Suporte Ref. à Dúvida na Utilização do Sistema Telecontrol",
            					"demora_analise"=>"Demora na Análise do Produto (Sem Pedido de Peças)",
            					"demora_realizar"=>"Demora em Realizar Pedido de Peças",
            					"procedimentos_incorretos"=>"Realização de Procedimentos Incorretos"
            				   ];
            $arrayBO 		 = ["acompanhamento"=>"Acompanhamento", 
								"orientacao_verbal"=>"Orientação Verbal", 
								"orientacao_escrita"=>"Orientação Escrita",
								"advertencia"=>"Advertência",
								"descredenciamento"=>"Descredenciamento",
								"outros"=>"Outros"
							   ];

			foreach ($advertencias as $posto => $advertencias) :

				foreach($advertencias as $advertencia):

					$nivel_falha = "";
					$tipo_falha = "";
					$tratativa_atendimento = "";
					$acao_bo = "";
					$bo_ad = "";

					$os = $advertencia->codigo_posto_os.$advertencia->sua_os;
					$osLink = 'os_press.php?os='.$advertencia->os;


					$status =  (empty($advertencia->data_concluido)) ? "Pendente" : "Finalizado";
					if(!empty($advertencia->tipo_ocorrencia)) {
						$bo_ad = "Boletim de Ocorrência";
					}

					$numero = $advertencia->advertencia;
					if(empty($advertencia->tipo_ocorrencia)) {
						$numero.= '-'.$advertencia->numero_advertencia;
						$bo_ad = "Advertência";
					}

					if (!empty($advertencia->nivel_falha)) {
						foreach ($arrayNivelFalha as $key => $value) {
							if ($advertencia->nivel_falha == $key) {
								$nivel_falha = $value;
								break;
							}
						}
					}

					if (!empty($advertencia->tipo_falha)) {
						foreach ($arrayFalha as $key => $value) {
							if ($advertencia->tipo_falha == $key) {
								$tipo_falha = $value;
								break;
							}	
						}
					}

					if (!empty($advertencia->tratativa_atendimento)) {
						foreach ($arrayTratativa as $key => $value) {
							if ($advertencia->tratativa_atendimento == $key) {
								$tratativa_atendimento = $value;
								break;
							}	
						}
					}

					if (!empty($advertencia->acao_bo)) {
						foreach ($arrayBO as $key => $value) {
							if ($advertencia->acao_bo == $key) {
								$acao_bo = $value;
								break;
							}	
						}
					}

					if ($advertencia->acao_bo == 'outros') {
						$outros_explicacao = $advertencia->outros_explicacao;
						$str       = str_replace('\u','u',$outros_explicacao);
						$outros_explicacao = preg_replace('/u([\da-fA-F]{4})/', '&#x\1;', $str);
						$outros_explicacao = (mb_check_encoding($outros_explicacao, "UTF-8")) ? utf8_decode($outros_explicacao) : $outros_explicacao;
						$acao_bo = $acao_bo ." - ".$outros_explicacao;
					}

					$corTd = "";
					if (in_array($advertencia->tipo_posto_desc, ["5SC","5SB","5SA"]) && $advertencia->total_registros > 1 ) {
						$corTd = "style = 'background-color:#FFF18D;'";
					} else if (in_array($advertencia->tipo_posto_desc, ["5SC","5SB","5SA"])) {
						$corTd = "style = 'background-color:#FFC176;'";
					} else if ($advertencia->total_registros > 1) {
						$corTd = "style = 'background-color:#FF9E9E;'";	
					}

					// condição de tipo_ocorrencia para pegar só b.o pois somente eles tem hd chamado
					$admin_resp = '';
					if (!empty($advertencia->numero_sac) && !empty($advertencia->tipo_ocorrencia)) {
						$hdId = explode("-", $advertencia->numero_sac);
						
						$sCondicoes = " AND tbl_hd_chamado.hd_chamado = ".$hdId[0];

						if (count($hdId) > 1) {
							$sCondicoes .=  " AND (tbl_hd_chamado.protocolo_cliente = '".$hdId[0]."' OR tbl_hd_chamado.hd_chamado = ".$hdId[0]." OR tbl_hd_chamado.hd_chamado_anterior = ".$hdId[0].")";
						}

						$sql_resp = "	SELECT nome_completo 
										FROM tbl_hd_chamado 
										JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.atendente 
										WHERE fabrica_responsavel = $login_fabrica
										$sCondicoes";
						$res_resp = pg_query($con, $sql_resp);
						if (pg_num_rows($res_resp) > 0) {
							$admin_resp = pg_fetch_result($res_resp, 0, 'nome_completo');
						}
					}

					$xcontato_posto = (mb_check_encoding($advertencia->contato_posto, "UTF-8")) ? utf8_decode($advertencia->contato_posto) : $advertencia->contato_posto;

					$resultado .=  "
							<tr id='$advertencia->advertencia'>
								<td class='tac' $corTd><a target='_blank' href='".$osLink."'>".$os."<a></td>
								<td class='tac' $corTd>".$bo_ad."</td>
								<td $corTd>".$advertencia->numero_sac."</td>
								<td class='tac' $corTd>".$advertencia->produto_descricao."</td>
								<td class='tac' $corTd>".$advertencia->tipo_posto_desc."</td>
								<td class='tal' $corTd>".$advertencias[0]->codigo_posto ."-". $advertencias[0]->posto_nome ."</td>
								<td class='tac' $corTd>".$xcontato_posto."</td>
								<td class='tac' $corTd>$advertencia->data_input</td>
								<td class='tac' $corTd name='advertencia'>".$numero."</td>
								<td class='tac' $corTd name='data_fechamento'>$advertencia->data_concluido</td>
								<td class='tac' $corTd>" . (empty($advertencia->descricao) ? "Advertência" : ($advertencia->descricao)) . "</td>
								<td class='tac' $corTd>".$advertencia->nome_completo."</td>
								<td class='tac' $corTd>".$admin_resp."</td>
								<td class='tac' $corTd name='statuss'>" .$status. "</td>
								<td class='tac' $corTd name='statuss'>" .$nivel_falha. "</td>
								<td class='tac' $corTd name='statuss'>" .$tratativa_atendimento. "</td>
								<td class='tac' $corTd name='statuss'>" .$tipo_falha. "</td>
								<td class='tac' $corTd name='statuss'>" .$acao_bo. "</td>
								<td class='tac' $corTd>
									<a href='#' name='acao'>" . (!empty($advertencia->data_concluido) ? "Ver histórico" : "Interagir") . "</a>
									<a href='cadastro_advertencia_bo.php?acao=alterar&advertencia=$advertencia->advertencia' target='_blank' name='alterar'>Alterar</a>
								</td>
							</tr>

							";
				endforeach;

			endforeach;

		# Se não houver resultados
		} else {

			$resultado = "false";
		}
	}

	echo $resultado;
	//echo (!empty($resultado)) ? utf8_decode($resultado):"";
	//echo (!empty($resultado)) ? retira_acentos($resultado):"";
	exit;
}

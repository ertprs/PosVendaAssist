<?php
// Não é require_once porque outros includes podem chamar ele, e DEVE ser exdecutado
// toda vez que for requerido!
if ($_SERVER['SCRIPT_FILENAME'] === __FILE__) {
	$desabilita_tela = "Este <i>script</i> não pode ser executado diretamente.";
}

if (PROGRAM_NAME !== 'opiniao_posto.php' and $login_data_input < '2017-01-10') {
	$resOpiniao = pg_query(
		$con,
		"SELECT * FROM tbl_opiniao_posto
		  WHERE fabrica = $login_fabrica
		    AND ativo IS TRUE"
	);
	if (pg_num_rows($resOpiniao) > 0) {
		$resOpiniaoArray     = pg_fetch_array($resOpiniao);
		$opiniao_posto       = $resOpiniaoArray['opiniao_posto'];
		$sqlOpiniao          = "
			SELECT OP.*
			  FROM tbl_opiniao_posto_resposta AS OPR
			  JOIN tbl_opiniao_posto_pergunta AS OPP
			    ON OPR.opiniao_posto_pergunta = OPP.opiniao_posto_pergunta
			  JOIN tbl_opiniao_posto          AS OP
			    ON OPP.opiniao_posto          = OP.opiniao_posto
			 WHERE OPR.posto                  = $login_posto
			   AND OP.fabrica                 = $login_fabrica
			   AND OPP.opiniao_posto          = $opiniao_posto";

		$resVerificaResposta = pg_query($con,$sqlOpiniao);
		if (pg_num_rows($resVerificaResposta) == 0) {
			header("Location: opiniao_posto.php");
			die;
		}
	}
}

	$sql_tipo_posto = "select categoria from tbl_posto_fabrica where fabrica = $login_fabrica and posto = $login_posto";
	$res_tipo_posto = pg_query($con, $sql_tipo_posto);

	if(pg_num_rows($res_tipo_posto)>0){
		$categoria_descricao = pg_fetch_result($res_tipo_posto, 0, 'categoria'); 
	}

	if(!empty($categoria_descricao) and !in_array($categoria_descricao, ['Locadora'])){
		// 23/05/2011 HD 417968
		$sql_frm = "SELECT posto,
						   CASE WHEN fantasia <> '' AND responsavel_cadastro <> ''
							   THEN TRUE
							   ELSE FALSE
						   END AS formulario_ok
					  FROM tbl_at_postos_black
					 WHERE posto = $login_posto";
		$res_frm = pg_query($con, $sql_frm);
		$formulario_ok = pg_fetch_result($res_frm, 0, 'formulario_ok')? : false;

		if (!$formulario_ok and !isset($black_frm_at['black_frm_at'])) {
			include(APP_DIR . 'posto_black_atualizacao_dados.php');
			exit;
		}
	}

		$complemento_black = " AND tbl_posto_bloqueio.pedido_faturado is false ";

		$sql = "SELECT desbloqueio, observacao
						FROM tbl_posto_bloqueio
						WHERE posto = $login_posto
						AND fabrica = $login_fabrica
						$complemento_black
						AND extrato IS not TRUE
						ORDER BY data_input DESC
						LIMIT 1";
		$res = pg_query($con,$sql);

		$pagina_atual = filter_input(INPUT_SERVER,'SCRIPT_NAME');
        $pagina_atual = substr(strrchr($pagina_atual,'/'),1);
		if(pg_num_rows($res) > 0){
			$desb = pg_fetch_result($res, 0, 'desbloqueio');
			$observacao = pg_fetch_result($res, 0, 'observacao');

			if ($desb == 'f' and in_array(PROGRAM_NAME, array('os_cadastro.php','os_cadastro_troca.php','os_revenda.php'))) {
				$desabilita_tela = "O SEU POSTO DE SERVIÇOS POSSUI OS'S SEM FECHAR HÁ MAIS DE 60 DIAS. ".
					"SOLICITAMOS QUE REALIZE O FECHAMENTO DAS OS'S PENDENTES PARA QUE A SUA TELA DE DIGITAÇÃO DE OS'S SEJA LIBERADA. ".
					"SE TIVER QUALQUER DÚVIDA ENTRE EM CONTATO COM O SEU SUPORTE. ".
					"<a href='os_aberta_mais_180.php' style='color:#Ffffff; font-weight:bold; text-decoration: underline;' target='_blank'>CLIQUE AQUI</a> PARA VERIFICAR AS OS.";             
            }

		}

		$sql = "SELECT desbloqueio, observacao
						FROM tbl_posto_bloqueio
						WHERE posto = $login_posto
						AND fabrica = $login_fabrica
						$complemento_black
						AND extrato IS TRUE
						ORDER BY data_input DESC
						LIMIT 1";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){
			$desb = pg_fetch_result($res, 0, 'desbloqueio');
			$observacao = pg_fetch_result($res, 0, 'observacao');

			if ($desb == 'f' and in_array(PROGRAM_NAME, array('os_cadastro.php'))) {

				$desabilita_tela =  "
					O SEU POSTO DE SERVIÇOS POSSUI EXTRATO EM ABERTO HÁ MAIS DE 60 DIAS.<br>
					SOLICITAMOS QUE VERIFIQUE SUAS PENDÊNCIAS PARA QUE A SUA TELA DE DIGITAÇÃO DE OSs SEJA LIBERADA.<br>
					SE TIVER QUALQUER DÚVIDA ENTRE EM CONTATO COM O SEU SUPORTE.
				".

				"<a href='os_extrato_blackedecker.php'>CLIQUE AQUI PARA VISUALIZAR ESSES EXTRATOS.</a>".

				"<br /> <br />";

			}
		}

				$desabilita_tela = utf8_decode($desabilita_tela);

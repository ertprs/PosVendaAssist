<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include "funcoes.php";

if ($login_fabrica == 7) {
    header ("Location: os_press_filizola.php?os=$os");
    exit;
}
//forçar o login do posto para distrib (consulta do embarque)
if(strlen($_GET['login_posto'])>0){
	$gambiara = "t";
	$xlogin_posto = $_GET['login_posto'];
}

#if ($login_fabrica == 11 AND $login_posto == 6359) {
#    header ("Location: os_press_20080515.php?os=$os");
#    exit;
#}

//--=== VALIDA REINCIDENCIA DA OS ==================================================
$sql = "SELECT tbl_extrato.extrato FROM tbl_os_extra JOIN tbl_extrato using(extrato) WHERE os = $os AND tbl_extrato.aprovado IS NOT NULL ; ";
$res2 = pg_exec ($con,$sql);
$reic_extrato = @pg_result($res2,0,0);

if(strlen($reic_extrato) == 0){
	//echo "Passou aqui.";
	if($login_fabrica <> 56){
		$sql = "SELECT fn_valida_os_reincidente($os,$login_fabrica)";
	}
	$res1 = pg_exec ($con,$sql);

	if(strlen($_GET['os'])>0){
		$os=$_GET['os'];
		$sql = "SELECT  motivo_atraso ,
						observacao    ,
						os_reincidente,
						obs_reincidencia
				FROM tbl_os
				WHERE os = $os
				AND fabrica = $login_fabrica
				and finalizada is null";
	/*takashi 22/10/07 colocou and finalizada is null pois OS ja fechada e paga estava entrando no motivo do atraso, acho que nao há necessidade, se tiver necessidade comente as alterações.*/
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$motivo_atraso    = pg_result($res,0,motivo_atraso);
			$observacao       = pg_result($res,0,observacao);
			$os_reincidente   = pg_result($res,0,os_reincidente);
			$obs_reincidencia = pg_result($res,0,obs_reincidencia);

			if($login_fabrica == 2){
				if($os_reincidente=='t' AND (strlen($obs_reincidencia) == 0))
					header ("Location: os_motivo_atraso.php?os=$os&justificativa=ok");
			}else{
				if($os_reincidente=='t' AND strlen($obs_reincidencia )==0 )
					header ("Location: os_motivo_atraso.php?os=$os&justificativa=ok");
			}
		}
	}
}


$interacao_os = $_POST['interacao_os'];
if(strlen($interacao_os) > 0){
	$interacao_msg              = $_POST['interacao_msg'];
	$interacao_msg2             = $_POST['interacao_msg2'];
	$interacao_exigir_resposta = $_POST['interacao_exigir_resposta'];

	if(strlen($interacao_msg) == 0){
		$msg_erro = traduz("por.favor.insira.algum.comentario",$con,$cook_idioma);
	}

	if($interacao_exigir_resposta <> 't'){
		$interacao_exigir_resposta = 'f';
	}
	if($login_fabrica == 3){
		$interacao_exigir_resposta = 't';
	}

	if($login_fabrica == 3 and (strlen($interacao_msg) == 0 or strlen($interacao_msg2) == 0)){
		$msg_erro = traduz("e.obrigatorio.preencher.os.2.campos.de.enviar.duvida.ao.suporte.tecnico",$con,$cook_idioma);
	}

	if(strlen($interacao_msg) > 0 AND strlen($interacao_msg2) > 0){
		$interacao_msg = traduz("duvidas",$con,$cook_idioma).": <br>".$interacao_msg . "<br><br>".traduz("pontos.verificados.pelo.tecnico",$con,$cook_idioma).": <br>".$interacao_msg2;
	}


	if(strlen($msg_erro) == 0){
		$sql = "INSERT INTO tbl_os_interacao(
								os             ,
								comentario     ,
								exigir_resposta
							)VALUES(
								$os              ,
								'$interacao_msg' ,
								'$interacao_exigir_resposta'
							)";
		$res = pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
		if(strlen($msg_erro) == 0){
			if ($login_fabrica == 45) { // HD 54576
				$sqlx = " SELECT contato_estado,
								 contato_email ,
								 codigo_posto
							FROM tbl_posto_fabrica
							WHERE fabrica = $login_fabrica
							AND   posto   = $login_posto   ";
				$resx = pg_exec($con,$sqlx);
				if (pg_numrows($resx) > 0) {
					$contato_estado = pg_result($resx,0,contato_estado);
					$contato_email  = pg_result($resx,0,contato_email);
					$codigo_posto   = pg_result($resx,0,codigo_posto);

					$destinatario1 = "Flávia <atendimento1@nksonline.com.br>";
					$lista_estado1 = array("RJ","RS","DF","AM","PE","SC");
					$destinatario2 = "Tatiana <atendimento2@nksonline.com.br>";
					$lista_estado2 = array("BA","SE","AL","GO","MT","AC","PR ","TO");
					$destinatario3 = "Ana Claudia <atendimento3@nksonline.com.br>";
					$lista_estado3 = array("ES","PA","PI","AP","MA","PB","CE","RN","RO");
					$destinatario4 = "Joseane <atendimento4@nksonline.com.br>";
					$lista_estado4 = array("SP");
					$destinatario5 = "Reijane <atendimento5@nksonline.com.br>";
					$lista_estado5 = array("MG","MS");

					if (strlen($contato_email) == 0) {
						$remetente    = "Suporte <suporte@telecontrol.com.br>";
					}else{
						$remetente    = "$codigo_posto <$contato_email>";
					}
					if (strlen($contato_estado) > 0) {
						if (in_array($contato_estado,$lista_estado1)) {
							$destinatario = $destinatario1;
						}elseif (in_array($contato_estado,$lista_estado2)) {
							$destinatario = $destinatario2;
						}elseif (in_array($contato_estado,$lista_estado3)) {
							$destinatario = $destinatario3;
						}elseif (in_array($contato_estado,$lista_estado4)) {
							$destinatario = $destinatario4;
						}elseif (in_array($contato_estado,$lista_estado5)) {
							$destinatario = $destinatario5;
						}
						$assunto      = "Interação na OS $os";
						$mensagem     .="Posto $codigo_posto colocou seguinte interação na OS $os:\n";
						$mensagem     .="<br>$interacao_msg\n";
						$headers="Return-Path: <suporte@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";
						if ( mail($destinatario,$assunto,$mensagem,$headers) ) {
							//$msg = "<br>Foi enviado um email para: ".$email_destino."<br>";
						}
					}
				}
			}
			header ("Location: $PHP_SELF?os=$os");
		}
	}
}



////////////// ADICIONADO POR FABIO 10/01/2007
function converte_data($date)
{
    $date = explode("-", str_replace('/', '-', $date));
    $date2 = ''.$date[2].'/'.$date[1].'/'.$date[0];
    if (sizeof($date)==3)
        return $date2;
    else return false;
}


#HD 44202 - intervenção OS aberta
$os= trim($_GET['os']);
if($login_fabrica==3 AND strlen(trim($_POST['btn_acao']))>0 AND $_POST['btn_acao']=='gravar_justificativa'){
	$txt_justificativa_os_aberta = $_POST['txt_justificativa_os_aberta'];

	$res = @pg_exec($con,"BEGIN TRANSACTION");

	$status_os = "";
	$sql = "SELECT status_os
			FROM  tbl_os_status
			WHERE os=$os
			AND status_os IN (120, 122, 123, 126)
			ORDER BY data DESC LIMIT 1";
	$res_intervencao = pg_exec($con, $sql);
	$msg_erro        = pg_errormessage($con);

	if (pg_numrows ($res_intervencao) > 0 ){
		$status_os = pg_result($res_intervencao,0,status_os);

		if(strlen($txt_justificativa_os_aberta )== 0){
			$msg_erro .= "É necessário preencher a Justificativa para OS aberta.";
		}else{
			if ($status_os=="120"){
				$sql = "INSERT INTO tbl_os_status
						(os,status_os,data,observacao)
						VALUES ($os,122,current_timestamp,'$txt_justificativa_os_aberta')";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}
		}
	}

	if (strlen($msg_erro)>0){
		$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
	}else {
		#$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		$res = @pg_exec ($con,"COMMIT TRANSACTION");
	}
}


#HD 12657 - Posto causa a intervenção
$inter = trim($_GET['inter']);
if($login_fabrica==2 AND $inter=='1'){

	$res = @pg_exec($con,"BEGIN TRANSACTION");

	$status_os = "";
	$sql = "SELECT status_os
			FROM  tbl_os_status
			WHERE os=$os
			AND status_os IN (62,64,65)
			ORDER BY data DESC LIMIT 1";
	$res_intervencao = pg_exec($con, $sql);
	$msg_erro        = pg_errormessage($con);
	if (pg_numrows ($res_intervencao) > 0){
		$status_os = pg_result($res_intervencao,0,status_os);
	}
	if (pg_numrows ($res_intervencao) == 0 OR $status_os!="62"){
		$sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao)
			VALUES ($os,62,current_timestamp,'Auto intervenção.')";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	$status_os = "";
	$sql = "SELECT status_os
			FROM  tbl_os_status
			WHERE os=$os
			AND status_os IN (62,64,65)
			ORDER BY data DESC LIMIT 1";
	$res_intervencao = pg_exec($con, $sql);
	if (pg_numrows ($res_intervencao) > 0){
		$status_os = pg_result($res_intervencao,0,status_os);
		if ($status_os=="62"){
			$sql = "INSERT INTO tbl_os_status
					(os,status_os,data,observacao)
					VALUES ($os,65,current_timestamp,'Reparo do produto deve ser feito pela fábrica.')";
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);

			$sql = "INSERT INTO tbl_os_retorno (os) VALUES ($os)";
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}

	if (strlen($msg_erro)>0){
		$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
	}else {
		#$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		$res = @pg_exec ($con,"COMMIT TRANSACTION");
	}
}

if (($login_fabrica==2 OR $login_fabrica==3 OR $login_fabrica==6 OR $login_fabrica==11) AND strlen(trim($_POST['btn_acao']))>0 AND $_POST['btn_acao']=='gravar'){

    $nota_fiscal_envio_p = trim($_POST['txt_nota_fiscal']);
    $numero_rastreio_p   = trim($_POST['txt_rastreio']);
    $data_envio_p        = trim($_POST['txt_data_envio']);

	// login_fabrica <> 6 -> HD chamado 4156
	if (strlen($nota_fiscal_envio_p)==0 OR (strlen($numero_rastreio_p)==0 AND $login_fabrica<>6) OR strlen($data_envio_p)!=10){
		$msg_erro.= traduz("informacoes.do.envio.a.fabrica.incorretos",$con,$cook_idioma);
	} else {
		$data_envio_x = converte_data($data_envio_p);
		if ($data_envio_x==false) $msg_erro.= traduz("data.no.formato.invalido",$con,$cook_idioma);
	}

	if (strlen($msg_erro)==0){
		$sql =  "UPDATE tbl_os_retorno
				SET nota_fiscal_envio = '$nota_fiscal_envio_p',
					data_nf_envio     = '$data_envio_x',
					numero_rastreamento_envio = '$numero_rastreio_p'
				WHERE os=$os";
		$res = @pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
		if (strlen($msg_erro)>0){
			$msg_erro = traduz("erro.ao.gravar.verifique.as.informacoes.digitadas",$con,$cook_idioma);
		}
	}
}

if (($login_fabrica==1 OR $login_fabrica==2 OR $login_fabrica==3 OR $login_fabrica==6 OR $login_fabrica==11) AND $_POST['btn_acao']=='confirmar'){
	$os_retorno = trim($_GET['chegada']);
	if (strlen($os_retorno)==0)
		$msg_erro .= traduz("os.invalida",$con,$cook_idioma).": $os_retorno";

	$data_chegada_retorno = trim($_POST['txt_data_chegada_posto']);
	if (strlen($data_chegada_retorno)!=10){
		$msg_erro.= strtoupper(traduz("data.invalida",$con,$cook_idioma));
	}
	else {
		$data_chegada_retorno = converte_data($data_chegada_retorno);
		if ($data_chegada_retorno==false) $msg_erro.= traduz("data.no.formato.invalido",$con,$cook_idioma);
	}

	$res = @pg_exec($con,"BEGIN TRANSACTION");

	if (strlen($msg_erro)==0){
		$sql =  "UPDATE tbl_os_retorno
				SET retorno_chegada='$data_chegada_retorno'
				WHERE os=$os";
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}
	if (strlen($msg_erro)==0){
		$sql =  "UPDATE tbl_os_status
				SET status_os=64
				WHERE os=$os";
		$sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao) values ($os,64,current_timestamp,'Produto com reparo realizado pela fábrica e recebido pelo posto')";
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}
	if (strlen($msg_erro)>0){
		$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
	}
	else {
		$res = @pg_exec ($con,"COMMIT TRANSACTION");
		header("Location: $PHP_SELF?os=$os&msg_erro=$msg_erro");
	}
}
////////////// FIM ////  ADICIONADO POR FABIO 10/01/2007


$sql = "SELECT  tbl_fabrica.os_item_subconjunto
        FROM    tbl_fabrica
        WHERE   tbl_fabrica.fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
    $os_item_subconjunto = pg_result ($res,0,os_item_subconjunto);
    if (strlen ($os_item_subconjunto) == 0) $os_item_subconjunto = 't';
}

if($login_fabrica==19){//hd 19833 3/6/2008
	$sql_revendas = "tbl_revenda.cnpj AS revenda_cnpj                                          ,
					 tbl_revenda.nome AS revenda_nome                                          ,
					 tbl_revenda.fone AS revenda_fone                                          ,";

	$join_revenda = "LEFT JOIN tbl_revenda ON tbl_revenda.revenda = tbl_os.revenda";
}else{//lpad 25/8/2008 HD 34515
	$sql_revendas = "tbl_os.revenda_nome                                                        ,
					lpad(tbl_os.revenda_cnpj, 14, '0') AS revenda_cnpj                          ,
					tbl_os.revenda_fone                                                         ,";
}

#------------ Le OS da Base de dados ------------#
$os = $HTTP_GET_VARS['os'];
if (strlen ($os) > 0) {

// HD31887
if ($login_posto != 4311) {
	$cond = "AND tbl_posto_fabrica.fabrica      = $login_fabrica";
}

	$sql = "SELECT  tbl_os.sua_os                                                               ,
					tbl_os.sua_os_offline                                                       ,
					tbl_admin.login                              AS admin                       ,
					troca_admin.login                            AS troca_admin       ,
					to_char(tbl_os.data_digitacao,'DD/MM/YYYY')  AS data_digitacao              ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura               ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento             ,
					to_char(tbl_os.finalizada,'DD/MM/YYYY')      AS finalizada                  ,
					to_char(tbl_os.data_nf_saida,'DD/MM/YYYY')   AS data_nf_saida               ,
					tbl_os.tipo_atendimento                                                     ,
					tbl_os.tecnico_nome                                                         ,
					tbl_tipo_atendimento.descricao                 AS nome_atendimento          ,
					tbl_tipo_atendimento.codigo                    AS codigo_atendimento        ,
					tbl_os.consumidor_nome                                                      ,
					tbl_os.consumidor_fone                                                      ,
					tbl_os.consumidor_celular                                                   ,
					tbl_os.consumidor_fone_comercial                                            ,
					tbl_os.consumidor_fone_recado                                               ,
					tbl_os.consumidor_endereco                                                  ,
					tbl_os.consumidor_numero                                                    ,
					tbl_os.consumidor_complemento                                               ,
					tbl_os.consumidor_bairro                                                    ,
					tbl_os.consumidor_cep                                                       ,
					tbl_os.consumidor_cidade                                                    ,
					tbl_os.consumidor_estado                                                    ,
					tbl_os.consumidor_cpf                                                       ,
					tbl_os.consumidor_email                                                     ,
					$sql_revendas
					tbl_os.nota_fiscal                                                          ,
					tbl_os.nota_fiscal_saida                                                    ,
					tbl_os.cliente                                                              ,
					tbl_os.revenda                                                              ,
					tbl_os.rg_produto                                                           ,
					tbl_os.defeito_reclamado_descricao       AS defeito_reclamado_descricao_os  ,
					tbl_marca.marca ,
					tbl_marca.nome as marca_nome,
					tbl_os.qtde_produtos as qtde                                                ,
					tbl_os.tipo_os                                                              ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf                     ,

					tbl_defeito_reclamado.defeito_reclamado      AS defeito_reclamado           ,
					tbl_defeito_reclamado.descricao              AS defeito_reclamado_descricao ,

					tbl_causa_defeito.descricao                  AS causa_defeito_descricao     ,

					tbl_defeito_constatado.defeito_constatado    AS defeito_constatado          ,
					tbl_defeito_constatado.descricao             AS defeito_constatado_descricao,
					tbl_defeito_constatado.codigo                AS defeito_constatado_codigo   ,

					tbl_causa_defeito.causa_defeito              AS causa_defeito               ,
					tbl_causa_defeito.descricao                  AS causa_defeito_descricao     ,
					tbl_causa_defeito.codigo                     AS causa_defeito_codigo        ,
					tbl_os.aparencia_produto                                                    ,
					tbl_os.acessorios                                                           ,
					tbl_os.consumidor_revenda                                                   ,
					tbl_os.obs                                                                  ,
					tbl_os.excluida                                                             ,
					tbl_produto.produto                                                         ,
					tbl_produto.referencia                                                      ,
					tbl_produto.descricao                                                       ,
					tbl_produto.voltagem                                                        ,
					tbl_produto.troca_obrigatoria                                               ,
					tbl_os.qtde_produtos                                                        ,
					tbl_os.serie                                                                ,
					tbl_os.codigo_fabricacao                                                    ,
					tbl_posto_fabrica.codigo_posto               AS posto_codigo                ,
					tbl_posto.nome                               AS posto_nome                  ,
					tbl_os.ressarcimento                                                        ,
					tbl_os.certificado_garantia                                                 ,
					tbl_os_extra.os_reincidente                                                 ,
					tbl_os_extra.orientacao_sac                                                 ,
					tbl_os.solucao_os                                                           ,
					tbl_os.posto                                                                ,
					tbl_os.promotor_treinamento                                                 ,
					tbl_os.fisica_juridica                                                      ,
					tbl_os.troca_garantia                                                       ,
					tbl_os.troca_garantia_admin                                                 ,
					tbl_os.troca_faturada                                                       ,
					tbl_os_extra.tipo_troca                                                     ,
					tbl_os.os_posto                                                             ,
					to_char(tbl_os.finalizada,'DD/MM/YYYY HH24:MI') as data_ressarcimento       ,
					serie_reoperado                                                             ,
					tbl_extrato.extrato                                                         ,
					to_char(tbl_extrato_pagamento.data_pagamento, 'dd/mm/yyyy') AS data_previsao,
					to_char(tbl_extrato_pagamento.data_pagamento, 'dd/mm/yyyy') AS data_pagamento                                                              ,
					tbl_os.fabricacao_produto                                                   ,
					tbl_os.qtde_km
			FROM       tbl_os
			JOIN       tbl_posto              ON tbl_posto.posto                       = tbl_os.posto
			JOIN       tbl_posto_fabrica      ON tbl_posto_fabrica.posto               = tbl_os.posto $cond
			LEFT JOIN  tbl_os_extra           ON tbl_os.os                             = tbl_os_extra.os
			LEFT JOIN  tbl_extrato            ON tbl_extrato.extrato                   = tbl_os_extra.extrato AND tbl_extrato.fabrica = $login_fabrica
			LEFT JOIN  tbl_extrato_pagamento ON tbl_extrato_pagamento.extrato        = tbl_extrato.extrato
			LEFT JOIN  tbl_admin              ON tbl_os.admin                          = tbl_admin.admin
			LEFT JOIN    tbl_admin troca_admin  ON tbl_os.troca_garantia_admin = troca_admin.admin
			LEFT JOIN  tbl_defeito_reclamado  ON tbl_os.defeito_reclamado              = tbl_defeito_reclamado.defeito_reclamado
			LEFT JOIN  tbl_defeito_constatado ON tbl_os.defeito_constatado             = tbl_defeito_constatado.defeito_constatado
			LEFT JOIN  tbl_causa_defeito      ON tbl_os.causa_defeito                  = tbl_causa_defeito.causa_defeito
			LEFT JOIN  tbl_produto            ON tbl_os.produto                        = tbl_produto.produto
			LEFT JOIN  tbl_tipo_atendimento   ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
			LEFT JOIN tbl_marca on tbl_produto.marca = tbl_marca.marca
			$join_revenda
			WHERE   tbl_os.os = $os ";
    if ($login_e_distribuidor == "t") {
#        $sql .= "AND (tbl_os_extra.distribuidor = $login_posto OR tbl_os.posto = $login_posto) ";
    }else{
        $sql .= "AND tbl_os.posto = $login_posto ";
    }
//echo nl2br($sql);
    $res = pg_exec ($con,$sql);

    if (pg_numrows ($res) > 0) {
		$sua_os                      = pg_result ($res,0,sua_os);
		$admin                       = pg_result ($res,0,admin);
		$data_digitacao              = pg_result ($res,0,data_digitacao);
		$data_abertura               = pg_result ($res,0,data_abertura);
		$data_fechamento             = pg_result ($res,0,data_fechamento);
		$data_finalizada             = pg_result ($res,0,finalizada);
		$data_nf_saida               = pg_result ($res,0,data_nf_saida);

		//--==== INFORMACOES DO CONSUMIDOR =================================================
		$consumidor_nome             = pg_result ($res,0,consumidor_nome);
		$consumidor_endereco         = pg_result ($res,0,consumidor_endereco);
		$consumidor_numero           = pg_result ($res,0,consumidor_numero);
		$consumidor_complemento      = pg_result ($res,0,consumidor_complemento);
		$consumidor_bairro           = pg_result ($res,0,consumidor_bairro);
		$consumidor_cidade           = pg_result ($res,0,consumidor_cidade);
		$consumidor_estado           = pg_result ($res,0,consumidor_estado);
		$consumidor_cep              = pg_result ($res,0,consumidor_cep);
		$consumidor_fone             = pg_result ($res,0,consumidor_fone);
		$consumidor_celular          = pg_result ($res,0,consumidor_celular);
		$consumidor_fone_comercial   = pg_result ($res,0,consumidor_fone_comercial);
		$consumidor_fone_recado      = pg_result ($res,0,consumidor_fone_recado);
		$consumidor_cpf              = pg_result ($res,0,consumidor_cpf);
		$consumidor_email            = pg_result ($res,0,consumidor_email);
		$fisica_juridica             = pg_result ($res,0,fisica_juridica);
		$data_ressarcimento          = pg_result ($res,0,data_ressarcimento);

		if($fisica_juridica=="F"){
			$fisica_juridica = traduz("pessoa.fisica",$con,$cook_idioma);
		}
		if($fisica_juridica=="J"){
			$fisica_juridica = traduz("pessoa.juridica",$con,$cook_idioma);
		}


		//--==== INFORMACOES DA REVENDA ====================================================
		$revenda_cnpj                = pg_result ($res,0,revenda_cnpj);
		$revenda_nome                = pg_result ($res,0,revenda_nome);
		$revenda_fone                = pg_result ($res,0,revenda_fone);
		$nota_fiscal                 = pg_result ($res,0,nota_fiscal);
		$nota_fiscal_saida           = pg_result ($res,0,nota_fiscal_saida);
		$data_nf                     = pg_result ($res,0,data_nf);
		$cliente                     = pg_result ($res,0,cliente);
		$revenda                     = pg_result ($res,0,revenda);
		$consumidor_revenda          = pg_result ($res,0,consumidor_revenda);

		//--==== INFORMACOES DO PRODUTO ====================================================
		$produto                      = pg_result ($res,0,produto);
		$aparencia_produto            = pg_result ($res,0,aparencia_produto);
		$acessorios                   = pg_result ($res,0,acessorios);
		$produto_referencia           = pg_result ($res,0,referencia);
		$produto_descricao            = pg_result ($res,0,descricao);
		$produto_voltagem             = pg_result ($res,0,voltagem);
		$serie                        = pg_result ($res,0,serie);
		$codigo_fabricacao            = pg_result ($res,0,codigo_fabricacao);
		$troca_obrigatoria            = pg_result ($res,0,troca_obrigatoria);
		$rg_produto                   = pg_result ($res,0,rg_produto);

		//--==== DEFEITOS RECLAMADOS =======================================================
		$defeito_reclamado            = pg_result ($res,0,defeito_reclamado);
		$defeito_reclamado_descricao  = pg_result ($res,0,defeito_reclamado_descricao);
		$defeito_reclamado_descricao_os= pg_result ($res,0,defeito_reclamado_descricao_os);
		$os_posto                     = pg_result ($res,0,os_posto);

		if (strlen($defeito_reclamado_descricao)==0){
			$defeito_reclamado_descricao = $defeito_reclamado_descricao_os;
		}

		//--==== DEFEITOS CONSTATADO =======================================================
		$defeito_constatado           = pg_result ($res,0,defeito_constatado);
		$defeito_constatado_codigo    = pg_result ($res,0,defeito_constatado_codigo);
		$defeito_constatado_descricao = pg_result ($res,0,defeito_constatado_descricao);

		//--==== CAUSA DO DEFEITO ==========================================================
		$causa_defeito                = pg_result ($res,0,causa_defeito);
		$causa_defeito_codigo         = pg_result ($res,0,causa_defeito_codigo);
		$causa_defeito_descricao      = pg_result ($res,0,causa_defeito_descricao);

		$posto_codigo                 = pg_result ($res,0,posto_codigo);
		$posto_nome                   = pg_result ($res,0,posto_nome);
		$obs                          = pg_result ($res,0,obs);
		$qtde_produtos                = pg_result ($res,0,qtde_produtos);
		$excluida                     = pg_result ($res,0,excluida);
		$os_reincidente               = trim(pg_result ($res,0,os_reincidente));
		$orientacao_sac               = trim(pg_result ($res,0,orientacao_sac));
		$sua_os_offline               = trim(pg_result ($res,0,sua_os_offline));
		$solucao_os                   = trim (pg_result($res,0,solucao_os));
		$posto_verificado             = trim(pg_result ($res,0,posto));
		$marca_nome         = trim(pg_result($res,0,marca_nome));
		$marca              = trim(pg_result($res,0,marca));

		$ressarcimento        = trim(pg_result($res,0,ressarcimento));
		$certificado_garantia = trim(pg_result($res,0,certificado_garantia));

		$troca_garantia = trim(pg_result($res,0,troca_garantia));
		$troca_faturada = trim(pg_result($res,0,troca_faturada));

		$troca_garantia_admin = trim(pg_result($res,0,troca_garantia_admin));
		$troca_admin    = trim(pg_result($res,0,troca_admin));

		$qtde                         = pg_result ($res,0,qtde);

		$tipo_os                      = pg_result ($res,0,tipo_os);
		$tipo_atendimento   = trim(pg_result($res,0,tipo_atendimento));
		$tecnico_nome       = trim(pg_result($res,0,tecnico_nome));
		$nome_atendimento   = trim(pg_result($res,0,nome_atendimento));
		$codigo_atendimento = trim(pg_result($res,0,codigo_atendimento));

		$tipo_troca         = trim(pg_result($res,0,tipo_troca));

		$numero_controle    = trim(pg_result($res,0,serie_reoperado)); //HD 56740

		//--==== AUTORIZAÇÃO CORTESIA =====================================
		//        $autorizacao_cortesia = trim(pg_result($res,0,autorizacao_cortesia));
		$promotor_treinamento = trim(pg_result($res,0,promotor_treinamento));

		//--==== Dados Extrato HD 61132 ====================================
		$extrato            = trim(pg_result($res,0,extrato));
		$data_previsao      = trim(pg_result($res,0,data_previsao));
		$data_pagamento     = trim(pg_result($res,0,data_pagamento));

		// HD 64152
		$fabricacao_produto = trim(pg_result($res,0,fabricacao_produto));
		$qtde_km            = trim(pg_result($res,0,qtde_km));
		if(strlen($qtde_km) == 0) $qtde_km = 0;


		if(strlen($promotor_treinamento)>0){
			$sql = "SELECT nome FROM tbl_promotor_treinamento WHERE promotor_treinamento = $promotor_treinamento";
			$res_pt = pg_exec($con,$sql);
			if (@pg_numrows($res_pt) >0) {
			$promotor_treinamento  = trim(@pg_result($res_pt,0,nome));
			}
		}

		//--=== Tradução para outras linguas ============================= Raphael HD:1212
		$sql_idioma = " SELECT * FROM tbl_produto_idioma
						WHERE produto     = $produto
						AND upper(idioma) = '$sistema_lingua'";
		$res_idioma = @pg_exec($con,$sql_idioma);
		if (@pg_numrows($res_idioma) >0) {
			$produto_descricao  = trim(@pg_result($res_idioma,0,descricao));
		}

		$sql_idioma = "SELECT * FROM tbl_defeito_constatado_idioma
						WHERE defeito_constatado = $defeito_constatado
						AND upper(idioma)        = '$sistema_lingua'";
		$res_idioma = @pg_exec($con,$sql_idioma);
		if (@pg_numrows($res_idioma) >0) {
			$defeito_constatado_descricao  = trim(@pg_result($res_idioma,0,descricao));
		}

		$sql_idioma = "SELECT * FROM tbl_defeito_reclamado_idioma
						WHERE defeito_reclamado = $defeito_reclamado
						AND upper(idioma)        = '$sistema_lingua'";
		$res_idioma = @pg_exec($con,$sql_idioma);
		if (@pg_numrows($res_idioma) >0) {
			$defeito_reclamado_descricao  = trim(@pg_result($res_idioma,0,descricao));
		}

		$sql_idioma = " SELECT * FROM tbl_causa_defeito_idioma
						WHERE causa_defeito = $causa_defeito
						AND upper(idioma)   = '$sistema_lingua'";
		$res_idioma = @pg_exec($con,$sql_idioma);
		if (@pg_numrows($res_idioma) >0) {
			$causa_defeito_descricao  = trim(@pg_result($res_idioma,0,descricao));
		}

		# HD 13940 - Ultimo Status para as Aprovações de OS
		$sql = "SELECT status_os, observacao
				FROM tbl_os_status
				WHERE os = $os
				AND status_os IN (92,93,94)
				ORDER BY data DESC
				LIMIT 1";
		$res_status = @pg_exec($con,$sql);
		if (@pg_numrows($res_status) >0) {
			$status_recusa_status_os  = trim(pg_result($res_status,0,status_os));
			$status_recusa_observacao = trim(pg_result($res_status,0,observacao));
			if($status_recusa_status_os == 94){
				$os_recusada = 't';
			}
		}


		# HD 44202 - Ultimo Status para as Aprovações de OS aberta a mais de 90 dias
		$sql = "SELECT status_os, observacao
				FROM tbl_os_status
				WHERE os = $os
				AND status_os IN (120,122,123,126)
				ORDER BY data DESC
				LIMIT 1";
		$res_status = @pg_exec($con,$sql);
		if (@pg_numrows($res_status) >0) {

			$status_os_aberta = trim(pg_result($res_status,0,status_os));
			$status_os_aberta_obs = trim(pg_result($res_status,0,observacao));
		}

		//--=== Tradução para outras linguas ================================================

		if (strlen($revenda) > 0) {
			$sql = "SELECT  tbl_revenda.endereco   ,
							tbl_revenda.numero     ,
							tbl_revenda.complemento,
							tbl_revenda.bairro     ,
							tbl_revenda.cep        ,
							tbl_revenda.email
					FROM    tbl_revenda
					WHERE   tbl_revenda.revenda = $revenda;";
			$res1 = pg_exec ($con,$sql);

			if (pg_numrows($res1) > 0) {
				$revenda_endereco    = strtoupper(trim(pg_result ($res1,0,endereco)));
				$revenda_numero      = trim(pg_result ($res1,0,numero));
				$revenda_complemento = strtoupper(trim(pg_result ($res1,0,complemento)));
				$revenda_bairro      = strtoupper(trim(pg_result ($res1,0,bairro)));
				$revenda_email       = trim(pg_result ($res1,0,email));
				$revenda_cep         = trim(pg_result ($res1,0,cep));
				$revenda_cep         = substr($revenda_cep,0,2) .".". substr($revenda_cep,2,3) ."-". substr($revenda_cep,5,3);
			}
		}
		if (strlen($revenda_cnpj) == 14){
			$revenda_cnpj = substr($revenda_cnpj,0,2) .".". substr($revenda_cnpj,2,3) .".". substr($revenda_cnpj,5,3) ."/". substr($revenda_cnpj,8,4) ."-". substr($revenda_cnpj,12,2);
		}elseif(strlen($consumidor_cpf) == 11){
			$revenda_cnpj = substr($revenda_cnpj,0,3) .".". substr($revenda_cnpj,3,3) .".". substr($revenda_cnpj,6,3) ."-". substr($revenda_cnpj,9,2);
		}

		if($aparencia_produto=='NEW'){
			$aparencia = traduz("bom.estado",$con,$cook_idioma);
			$aparencia_produto= $aparencia_produto.' - '.$aparencia;
		}
		if($aparencia_produto=='USL'){
			$aparencia = traduz("uso.intenso",$con,$cook_idioma);
			$aparencia_produto= $aparencia_produto.' - '.$aparencia;
		}
		if($aparencia_produto=='USN'){
			$aparencia = traduz("uso.normal",$con,$cook_idioma);
			$aparencia_produto= $aparencia_produto.' - '.$aparencia;
		}
		if($aparencia_produto=='USH'){
			$aparencia = traduz("uso.pesado",$con,$cook_idioma);
			$aparencia_produto= $aparencia_produto.' - '.$aparencia;
		}
		if($aparencia_produto=='ABU'){
			$aparencia = traduz("uso.abusivo",$con,$cook_idioma);
			$aparencia_produto= $aparencia_produto.' - '.$aparencia;
		}
		if($aparencia_produto=='ORI'){
			$aparencia = traduz("original.sem.uso",$con,$cook_idioma);
			$aparencia_produto= $aparencia_produto.' - '.$aparencia;
		}
		if($aparencia_produto=='PCK'){
			$aparencia = traduz("embalagem",$con,$cook_idioma);
			$aparencia_produto= $aparencia_produto.' - '.$aparencia;
		}
	}
}

if (strlen($sua_os) == 0) $sua_os = $os;

$title = traduz("confirmacao.de.ordem.de.servico",$con,$cook_idioma);

$layout_menu = 'os';
include "cabecalho.php";

?>
<style type="text/css">



body {
    margin: 0px;
}

.titulo {
    font-family: Arial;
    font-size: 7pt;
    text-align: right;
    color: #000000;
    background: #ced7e7;
}
.titulo2 {
    font-family: Arial;
    font-size: 7pt;
    text-align: center;
    color: #000000;
    background: #ced7e7;
}
.titulo3 {
    font-family: Arial;
    font-size: 10px;
    text-align: right;
    color: #000000;
    background: #ced7e7;
    height:16px;
    padding-left:5px
}

.titulo4 {
    font-family: Arial;
    font-size: 10px;
    text-align: left;
    color: #000000;
    background: #ced7e7;
    height:16px;
    padding-left:0px
}

.inicio {
    font-family: Arial;
    FONT-SIZE: 8pt;
    font-weight: bold;
    text-align: left;
    color: #FFFFFF;
}

.conteudo {
    font-family: Arial;
    FONT-SIZE: 8pt;
    font-weight: bold;
    text-align: left;
    background: #F4F7FB;
}

.justificativa{
    font-family: Arial;
    FONT-SIZE: 10px;
    background: #F4F7FB;
}

.Tabela{
    border:1px solid #d2e4fc;
    background-color:#485989;
    }

.subtitulo {
    font-family: Verdana;
    FONT-SIZE: 9px;
    text-align: left;
    background: #F4F7FB;
    padding-left:5px
}
.inpu{
    border:1px solid #666;
}

.conteudo2 {
	font-family: Arial;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-align: left;
	background: #FFDCDC;
}

.conteudo_sac {
    font-family: Arial;
    FONT-SIZE: 10pt;
    text-align: left;
    background: #F4F7FB;
}

</style>


<?
if (strlen($os_reincidente) > 0 OR $reincidencia =='t') {
	$sql = "SELECT  tbl_os_status.status_os,
					tbl_os_status.observacao
			FROM  tbl_os_extra JOIN tbl_os_status USING(os)
			WHERE tbl_os_extra.os = $os
			AND   tbl_os_status.status_os IN (67,68,70,86)";
	$res1 = pg_exec ($con,$sql);

	if (pg_numrows ($res1) > 0) {
		$status_os  = trim(pg_result($res1,0,status_os));
		$observacao  = trim(pg_result($res1,0,observacao));
	}

	//HD3646
	if($login_fabrica<>6){
		echo "<table style=' border: #D3BE96 1px solid; background-color: #FCF0D8 ' align='center' width='700'>";
		echo "<tr>";
		echo "<td align='center'><b><font size='1'>";
		echo strtoupper(traduz("atencao",$con,$cook_idioma));
		echo "</font></b></td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td align='center'><font size='1'>";

		if(strlen($os_reincidente)>0 ){
			$sql = "SELECT  tbl_os.sua_os,
							tbl_os.serie
					FROM    tbl_os
					WHERE   tbl_os.os = $os_reincidente;";
			$res1 = pg_exec ($con,$sql);
			$sos   =   trim(pg_result($res1,0,sua_os));
			$serie_r = trim(pg_result($res1,0,serie));

			if($login_fabrica==1) $sos=$posto_codigo.$sos;
		}else{
			//CASO NÃO TENHA A REINCIDENCIA NÃO TENHA SIDO APONTADA, PROCURA PELA REINCIDENCIA NA SERIE
			$sql = "SELECT os,sua_os,posto
					FROM tbl_os
					JOIN    tbl_produto ON tbl_produto.produto = tbl_os.produto
					WHERE   serie   =  '$serie_r'
					AND     os      <> $os
					AND     fabrica =  $login_fabrica
					AND     tbl_produto.numero_serie_obrigatorio IS TRUE ";
			$res2 = pg_exec ($con,$sql);

			echo strtoupper(traduz("ordem.de.servico.com.numero.de.serie.%.reincidente.ordem.de.servico.anterior",$con,$cook_idioma,array($serie_r))).":<br>";

			if (pg_numrows ($res2) > 0) {
				for ($i = 0 ; $i < pg_numrows ($res2) ; $i++) {
					$sos_reinc  = trim(pg_result($res2,$i,sua_os));
					$os_reinc   = trim(pg_result($res2,$i,os));
					$posto_reinc   = trim(pg_result($res2,$i,posto));
					if($posto_reinc == $login_posto){
						echo " <a href='os_press.php?os=$os_reinc' target='_blank'>» $sos_reinc</a><br>";
					}else{
						echo "» $sos_reinc<br>";
					}

				}
			}

		}

		if($status_os==67){

			echo strtoupper(traduz("ordem.de.servico.com.numero.de.serie.%.reincidente.ordem.de.servico.anterior",$con,$cook_idioma,array($serie_r))).":<br>";

			if ($login_fabrica == 11) {
				$sql = "SELECT os_reincidente
						FROM tbl_os_extra
						WHERE os= $os";
				$res2 = pg_exec($con,$sql);

				$osrein = pg_result($res2,0,os_reincidente);

				if (pg_numrows($res2) > 0) {
					$sql = "SELECT os,sua_os
							FROM tbl_os
							WHERE   serie   = '$serie'
							AND     os      = $osrein
							AND     fabrica = $login_fabrica";
				}
				$res2 = pg_exec($con,$sql);

				if (pg_numrows($res2) > 0) {
					$sua_osrein = pg_result($res2,0,sua_os);
					echo "<a href='os_press.php?os=$osrein' target='_blank'>» $sua_osrein</a>";
				}
			} else {
				$sql = "SELECT os,sua_os,posto
					FROM tbl_os
					JOIN    tbl_produto ON tbl_produto.produto = tbl_os.produto
					WHERE   serie   = '$serie'
					AND     os     <> $os
					AND     fabrica = $login_fabrica
					AND     tbl_produto.numero_serie_obrigatorio IS TRUE ";

				$res2 = pg_exec ($con,$sql);

				if (pg_numrows ($res2) > 0) {
					for ($i = 0 ; $i < pg_numrows ($res2) ; $i++) {
						$sos_reinc  = trim(pg_result($res2,$i,sua_os));
						$os_reinc   = trim(pg_result($res2,$i,os));
						$posto_reinc   = trim(pg_result($res2,$i,posto));
						if($posto_reinc == $login_posto){
							echo " <a href='os_press.php?os=$os_reinc' target='_blank'>» $sos_reinc</a><br>";
						}else{
							echo "» $sos_reinc<br>";
						}
					}
				}
			}
		}elseif($status_os==68){
			echo strtoupper(traduz("ordem.de.servico.com.mesma.revenda.e.nota.fiscal.reincidente.ordem.de.servico.anterior",$con,$cook_idioma)).": <a href='os_press.php?os=$os_reincidente' target='_blank'>$sos</a>";
		}elseif($status_os==70){
			echo strtoupper(traduz("ordem.de.servico.com.mesma.revenda.nota.fiscal.e.produto.reincidente.ordem.de.servico.anterior",$con,$cook_idioma)).": <a href='os_press.php?os=$os_reincidente' target='_blank'>$sos</a>";
		}elseif($status_os==95){
			echo strtoupper(traduz("ordem.de.servico.com.mesma.nota.fiscal.e.produto.reincidente.ordem.de.servico.anterior",$con,$cook_idioma)).": <a href='os_press.php?os=$os_reincidente' target='_blank'>$sos</a>";
		}else{
			echo traduz("os.reincidente",$con,$cook_idioma).":<a href='os_press.php?os=$os_reincidente' target = '_blank'>$sos</a>";
		}
		echo "";
		echo "</font></td>";
		echo "</tr>";
		echo "</table>";
	}
}


if ($consumidor_revenda == 'R')
	$consumidor_revenda = 'REVENDA';
else
	if ($consumidor_revenda == 'C')
		$consumidor_revenda = 'CONSUMIDOR';
?>
<?
 ##############################################
# se é um distribuidor da Britania consultando #
# exibe o posto                                #
 ##############################################

if ((strlen($tipo_atendimento)>0) and $login_fabrica==1) {
?>
<center>
<TABLE width="700" border="0" cellspacing="1" cellpadding="0" class='tabela'>
<TR>
	<TD class="inicio" height='20' width='110' nowrap>&nbsp;&nbsp;<? fecho("troca.de.produto",$con,$cook_idioma);?>: </TD>
	<TD class="conteudo" height='20' width='130' nowrap><? echo " &nbsp;&nbsp;$nome_atendimento"; ?></TD>
	<TD class="inicio" height='20' width='50' >&nbsp;&nbsp;<? fecho("motivo",$con,$cook_idioma);?>: </TD>
	<?  $sql_2 = "SELECT tbl_os_status.observacao FROM tbl_os_status JOIN tbl_os using(os) where os = '$os'; ";
		$res_2 = pg_exec($con,$sql_2);
		if(pg_numrows($res_2) > 0) $obs_status = pg_result($res_2,0,observacao);
	?>
	<TD class="conteudo" height='20'><? echo " &nbsp;&nbsp;$obs_status"; ?></TD>
</TR>
</TABLE>
</center>
<?
}
if($login_fabrica ==1 AND strlen($os) > 0){ // HD 17284
	$sql2="SELECT to_char(data,'DD/MM/YYYY') as data,
				  descricao,
				  observacao
			FROM tbl_os_status
			JOIN tbl_status_os using(status_os)
			WHERE os=$os
			AND status_os_troca IS TRUE";
	$res2=pg_exec($con,$sql2);
	if(pg_numrows($res2) > 0){
		echo "<TABLE width='700' border='0' align='center' cellspacing='0' cellpadding='0' class='Tabela'>";
		echo "<TR>";
		echo "<TD class='inicio' colspan='2' align='center'>".strtoupper(traduz("historico",$con,$cook_idioma))."</TD>";
		echo "</TR>";
		for ($i = 0 ; $i < pg_numrows ($res2) ; $i++) {
			$data             = pg_result($res2,$i,data);
			$descricao_status = pg_result($res2,$i,descricao);
			$observacao_status = pg_result($res2,$i,observacao);
			echo "<TR>";
			echo "<TD class='conteudo' colspan='2' align='center'>$data - $descricao_status</TD>";
			echo "</tr>";
			echo "<TR>";
			echo "<TD class='conteudo2' colspan='2' align='center'>".traduz("motivo",$con,$cook_idioma).": $observacao_status</TD>";
			echo "</TR>";
		}
		echo "</TABLE></center>";
	}
}
//OR $login_fabrica ==50
if($login_fabrica ==30 AND strlen($os) > 0){ // HD 17284
	$sql2="SELECT to_char(data,'DD/MM/YYYY') as data,
				  descricao,
				  observacao
			FROM tbl_os_status
			JOIN tbl_status_os using(status_os)
			WHERE os=$os
			AND status_os IN (98,99,100, 101)
			ORDER BY os_status desc
			limit 1";
	$res2=pg_exec($con,$sql2);
	if(pg_numrows($res2) > 0){
		echo "<TABLE width='700' border='0' align='center' cellspacing='0' cellpadding='0' class='Tabela'>";
		echo "<TR>";
		echo "<TD class='inicio' colspan='2' align='center'>".strtoupper(traduz("historico",$con,$cook_idioma))."</TD>";
		echo "</TR>";
		for ($i = 0 ; $i < pg_numrows ($res2) ; $i++) {
			$data             = pg_result($res2,$i,data);
			$descricao_status = pg_result($res2,$i,descricao);
			$observacao_status = pg_result($res2,$i,observacao);
			echo "<TR>";
			echo "<TD class='conteudo' colspan='2' align='center'>$data - $descricao_status</TD>";
			echo "</tr>";
			echo "<TR>";
			echo "<TD class='conteudo2' colspan='2' align='center'>$observacao_status</TD>";
			echo "</TR>";
		}
		echo "</TABLE></center>";
	}
}

if($login_fabrica ==35 AND strlen($os) > 0){ // HD 56418
	$sql2="SELECT to_char(data,'DD/MM/YYYY') as data,
				  descricao,
				  observacao
			FROM tbl_os_status
			JOIN tbl_status_os using(status_os)
			WHERE os=$os
			AND status_os IN (13,19,127)
			ORDER BY os_status desc
			limit 1";
	$res2=pg_exec($con,$sql2);
	if(pg_numrows($res2) > 0){
		echo "<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='0' class='Tabela'>";
		echo "<TR>";
		echo "<TD class='inicio' colspan='2' align='center'>".strtoupper(traduz("historico",$con,$cook_idioma))."</TD>";
		echo "</TR>";
		for ($i = 0 ; $i < pg_numrows ($res2) ; $i++) {
			$data             = pg_result($res2,$i,data);
			$descricao_status = pg_result($res2,$i,descricao);
			$observacao_status = pg_result($res2,$i,observacao);
			echo "<TR>";
			echo "<TD class='conteudo2' colspan='2' align='center'>$data - $descricao_status</TD>";
			echo "</tr>";
		}
		echo "</TABLE></center>";
	}
}


if ($excluida == "t") {
?>
<TABLE width="700" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela' >
<TR>
    <TD  bgcolor="#FFE1E1" height='20'>

	<h1>
	<?
	if ($login_fabrica==20 AND $os_recusada =='t'){
		#HD 13940
		echo strtoupper(traduz("os.recusada",$con,$cook_idioma ))." - ".$status_recusa_observacao;
	}else{
		echo strtoupper(traduz("ordem.de.servico.excluida",$con,$cook_idioma));
	}
	?>
	</h1></TD>
</TR>
</TABLE>
</center>
<?
}

// HD 11068 8/1/2008
############################################################################
if ($ressarcimento == "t") {
	echo "<TABLE width='700' border='0' cellspacing='1' align='center' cellpadding='0' class='Tabela'>";
	echo "<TR height='30'>";
	echo "<TD align='left' colspan='3'>";
	echo "<font family='arial' size='2' color='#ffffff'><b>";
	fecho ("ressarcimento.financeiro",$con,$cook_idioma);
	echo "</b></font>";
	echo "</TD>";
	echo "</TR>";

	//4/1/2008 HD 11068
	if($login_fabrica == 45 or $login_fabrica == 11){
		$sql = "SELECT
					observacao,descricao
				FROM tbl_os_troca
				LEFT JOIN tbl_causa_troca USING (causa_troca)
				WHERE tbl_os_troca.os = $os";
		$resY = pg_exec ($con,$sql);

		if (pg_numrows ($resY) > 0) {
			$troca_observacao = pg_result ($resY,0,observacao);
			$troca_causa      = pg_result ($resY,0,descricao);
		}
	}
	echo "<tr>";
	echo "<TD class='titulo2'  height='15' >".traduz("responsavel",$con,$cook_idioma)."</TD>";
	echo "<TD class='titulo2'  height='15' >".traduz("data",$con,$cook_idioma)."</TD>";
	if($login_fabrica == 45){
		echo "<TD class='titulo2'  height='15' >".traduz("observacao",$con,$cook_idioma)."</TD>";
	}elseif($login_fabrica == 11){
		echo "<TD class='titulo2'  height='15' >".traduz("causa",$con,$cook_idioma)."</TD>";
	}else{
		echo "<TD class='titulo3'  height='15' >&nbsp;</TD>";
	}
	echo "</tr>";

	echo "<tr>";
	echo "<TD class='conteudo' height='15'>";
	echo "&nbsp;&nbsp;&nbsp;";
	echo $troca_admin;
	echo "&nbsp;&nbsp;&nbsp;";
	echo "</td>";
	echo "<TD class='conteudo' height='15'>";
	echo "&nbsp;&nbsp;&nbsp;";
	if($login_fabrica ==11) { // HD 56237
		echo $data_ressarcimento;
	}else{
		echo $data_fechamento ;
	}
	echo "&nbsp;&nbsp;&nbsp;";
	echo "</td>";

	if($login_fabrica == 45){
		echo "<TD class='conteudo' height='15' width='80%'>$troca_observacao</td>";
	}elseif($login_fabrica == 11){
		echo "<TD class='conteudo'  height='15' >$troca_causa</TD>";
	}else{
		echo "<TD class='conteudo' height='15' width='80%'>&nbsp;</td>";
	}
	echo "</tr>";

	if($login_fabrica==11) { // hd 56237
		echo "<tr>";
		echo "<TD class='conteudo' height='15' colspan='100%'>OBS: $troca_observacao</td>";
		echo "</tr>";
	}

	echo "</table>";
}
############################################################################
// Verifica se o pedido de peça foi cancelado ou autorizado caso a peça esteja bloqueada para garantia
#Fabrica 25 - HD 14830
# HD 13618 - NKS
# HD 12657 - Dynacom
if ($login_fabrica==1 OR $login_fabrica==2 OR $login_fabrica==3 OR $login_fabrica==6 OR $login_fabrica==11 OR $login_fabrica==25 OR $login_fabrica==45 OR $login_fabrica==50){
	$sql_status = "SELECT    status_os,
				observacao,
				tbl_admin.login,
				to_char(tbl_os_status.data, 'dd/mm/yyyy') AS data,
				tbl_os_status.data as date
				FROM tbl_os_status
				LEFT JOIN tbl_admin USING(admin)
				WHERE os=$os
				AND status_os IN (72,73,62,64,65,87,88,98,99,100,101,102,103,104,116,117)
				ORDER BY date DESC LIMIT 1";
	$res_status = pg_exec($con,$sql_status);
	$resultado = pg_numrows($res_status);
	if ($resultado==1){
		$status_os          = trim(pg_result($res_status,0,status_os));
		$status_observacao  = trim(pg_result($res_status,0,observacao));
		$data_status        = trim(pg_result($res_status,0,data));
		$intervencao_admin  = trim(pg_result($res_status,0,login));

		if ($status_os==88){
			echo "<br>
				<center>
				<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
					<b style='font-size:14px;color:red;width:100%'>".traduz("pedido.de.peca.necessita.de.autorizacao",$con,$cook_idioma)."</b><br>
					<b style='font-size:11px'>$status_observacao</b>
				</div>
				</center><br>
			";
		}
		if ($status_os==87){
			echo "<br>
				<center>
				<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
					<b style='font-size:14px;color:red;width:100%'>".traduz("pedido.de.peca.necessita.de.autorizacao",$con,$cook_idioma)."</b><br>
					<b style='font-size:11px'>".traduz("a.peca.solicitada.necessita.de.autorizacao",$con,$cook_idioma).". ";
				if ($login_fabrica==1){
					echo "<br>".traduz("entrar.em.contato.com.o.suporte.de.sua.regiao",$con,$cook_idioma)."</b>";
				}else{
					echo "<br>".traduz("aguarde.a.fabrica.analisar.seu.pedido",$con,$cook_idioma)."</b>";
				}
			echo "</div>
				</center><br>
			";
			if ($login_fabrica==1){
				echo "<script language='JavaScript'>alert('".traduz("os.em.intervencao.gentileza.entre.em.contato.com.o.suporte.de.sua.regiao",$con,$cook_idioma)."');</script>";
			}
		}
		if ($status_os==72 or $status_os==116){
			echo "<br>
				<center>
				<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
					<b style='font-size:14px;color:red;width:100%'>".traduz("pedido.de.peca.necessita.de.autorizacao",$con,$cook_idioma)."</b><br>
					<b style='font-size:11px'>".traduz("a.peca.solicitada.necessita.de.autorizacao",$con,$cook_idioma).". <br>".traduz("aguarde.a.fabrica.analisar.seu.pedido",$con,$cook_idioma).".</b>
				</div>
				</center><br>
			";
		}
		if ($status_os==73 or $status_os==117){
			echo "<br>
				<center>
				<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
					<b style='font-size:14px;color:red;width:100%'>".traduz("pedido.de.peca.necessita.de.autorizacao",$con,$cook_idioma)."</b><br>
					<b style='font-size:11px'>$status_observacao</b>
				</div>
				</center><br>
			";
		}
		if ($status_os==62){

			echo "<br>
				<center>
				<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
					<b style='font-size:14px;color:red;width:100%'>".traduz("os.sob.intervencao.da.assistencia.tecnica.da.fabrica",$con,$cook_idioma)."</b><br>
					<b style='font-size:11px'>$status_observacao</b>
				</div>
				</center><br>
			";
		}
		if ($status_os==64){
			echo "<br>
				<center>
				<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
					<b style='font-size:14px;color:red;width:100%'>".traduz("os.liberada.da.assistencia.tecnica.da.fabrica",$con,$cook_idioma)."</b><br>
					<b style='font-size:11px'>$status_observacao</b>
				</div>
				</center><br>
			";
		}
		if($login_fabrica==50){
			# HD 42933 - Alterei para a Colormaq, não estava mostrando a
			#   última intervenção na OS
			/*if ($status_os==98 or $status_os==99 or $status_os==100 or $status_os==101 or $status_os==102 or $status_os==103 or $status_os==104){*/
				$sql_status = #"select descricao from tbl_status_os where status_os = $status_os";
							"SELECT
								tbl_os_status.status_os,
								tbl_os_status.observacao,
								tbl_admin.login,
								tbl_status_os.descricao,
								to_char(tbl_os_status.data, 'dd/mm/yyyy') AS data
							FROM tbl_os_status
							JOIN tbl_status_os USING (status_os)
							LEFT JOIN tbl_admin USING (admin)
							WHERE os = $os
							ORDER BY data DESC LIMIT 1";
				$res_status = pg_exec($con, $sql_status );
				if(pg_numrows($res_status)>0){
					$data_status = pg_result($res_status, 0, data);;
					$descricao_status = pg_result($res_status, 0, descricao);
					$intervencao_admin = pg_result($res_status, 0, login);
					$descricao_status = pg_result($res_status, 0, descricao);
					$status_observacao = pg_result($res_status, 0, observacao);

					echo "<table width='700' border='0' cellspacing='1' cellpadding='0' class='Tabela' align='center'>";
							echo "<TR>";
								echo "<TD class='inicio' background='imagens_admin/azul.gif' height='19px' colspan='4'>&nbsp;".strtoupper(traduz("status.os",$con,$cook_idioma ))."&nbsp;</TD>";
							echo "</TR>";
							echo "<TR>";
								echo "<TD class='inicio'>".strtoupper(traduz("data",$con,$cook_idioma ))."&nbsp;</TD>";
								echo "<TD class='inicio'>".strtoupper(traduz("admin",$con,$cook_idioma ))."&nbsp;</TD>";
								echo "<TD class='inicio'>".strtoupper(traduz("status",$con,$cook_idioma ))."&nbsp;</TD>";
								echo "<TD class='inicio'>".strtoupper(traduz("motivo",$con,$cook_idioma ))."&nbsp;</TD>";
							echo "</TR>";
							echo "<TR>";
								echo "<TD class='conteudo' width='10%'>&nbsp; $data_status </TD>";
								echo "<TD class='conteudo'>&nbsp;$intervencao_admin </TD>";
								echo "<TD class='conteudo'>&nbsp;$descricao_status </TD>";
								echo "<TD class='conteudo'>&nbsp;$status_observacao </TD>";
							echo "</TR>";
					echo "</TABLE>";
				}
		#}
		}
	}
}

if(strlen($extrato)>0 AND $login_fabrica==50){ //HD 61132
	echo "<table width='700' border='0' cellspacing='1' cellpadding='0' class='Tabela' align='center'>";
		echo "<TR>";
			echo "<TD class='inicio'>".strtoupper(traduz("extrato",$con,$cook_idioma ))."&nbsp;</TD>";
			echo "<TD class='inicio'>".strtoupper(traduz("previsao",$con,$cook_idioma ))."&nbsp;</TD>";
			echo "<TD class='inicio'>".strtoupper(traduz("pagamento",$con,$cook_idioma ))."&nbsp;</TD>";
		echo "</TR>";
		echo "<TR>";
			echo "<TD class='conteudo' width='33%'>&nbsp;$extrato </TD>";
			echo "<TD class='conteudo' width='33%'>&nbsp;$data_pagamento </TD>";
			echo "<TD class='conteudo' width='33%'>&nbsp;$data_previsao </TD>";
		echo "</TR>";
	echo "</TABLE>";
}


if($login_fabrica ==50 AND strlen($os) > 0){ // HD 37276
	# HD 42933 - Retirado o resultado da tela, deixado apenas um link
	#   que abre um pop-up mostrando todo o histórico da OS
	/*$sql2="SELECT to_char(data,'DD/MM/YYYY') as data,
				  descricao,
				  observacao
			FROM tbl_os_status
			JOIN tbl_status_os using(status_os)
			WHERE os=$os
			AND status_os IN (98,99,100, 101,102,103,104,116,117)
			ORDER BY os_status desc
			limit 1";
	$res2=pg_exec($con,$sql2);
	if(pg_numrows($res2) > 0){*/
		echo "<TABLE width='700' border='0' align='center' cellspacing='0' cellpadding='0' class='Tabela'>";
		echo "<TR>";
		echo "<TD class='inicio' colspan='2' align='center'>";
		?>
		<a style='cursor:pointer;' onclick="javascript:window.open('historico_os.php?os=<? echo $os ?>','mywindow','menubar=1,resizable=1,width=500,height=350')">&nbsp;<?php
		fecho("ver.historico.da.os",$con,$cook_idioma);?></a>
		<?php
		echo "</TD>";
		echo "</TR>";
		/*for ($i = 0 ; $i < pg_numrows ($res2) ; $i++) {
			$data             = pg_result($res2,$i,data);
			$descricao_status = pg_result($res2,$i,descricao);
			$observacao_status = pg_result($res2,$i,observacao);
			echo "<TR>";
			echo "<TD class='conteudo' colspan='2' align='center'>$data - $descricao_status</TD>";
			echo "</tr>";
			echo "<TR>";
			echo "<TD class='conteudo2' colspan='2' align='center'>$observacao_status</TD>";
			echo "</TR>";
		}*/
		echo "</TABLE></center>";
	#}
}

////////////////////////////// OS RETORNO  - FABIO 10/01/2007  - INICIO //////////////////////////////
// informações de postagem para envio do produto para a Fábrica
// ADICIONADO POR FABIO 03/01/2007
// Dynacom - HD 12657
if ($login_fabrica==2 OR $login_fabrica==3 OR $login_fabrica==6 OR $login_fabrica==11){
	$sql = "SELECT  nota_fiscal_envio,
				TO_CHAR(data_nf_envio,'DD/MM/YYYY')  AS data_nf_envio,
				numero_rastreamento_envio,
				TO_CHAR(envio_chegada,'DD/MM/YYYY')  AS envio_chegada,
				nota_fiscal_retorno,
				TO_CHAR(data_nf_retorno,'DD/MM/YYYY')  AS data_nf_retorno,
				numero_rastreamento_retorno,
				TO_CHAR(retorno_chegada,'DD/MM/YYYY')  AS retorno_chegada
			FROM tbl_os_retorno
			WHERE   os = $os;";
	$res = pg_exec ($con,$sql);
	if (@pg_numrows($res)==1){
		$retorno=1;
		$nota_fiscal_envio            = trim(pg_result($res,0,nota_fiscal_envio));
		$data_nf_envio                = trim(pg_result($res,0,data_nf_envio));
		$numero_rastreamento_envio    = trim(pg_result($res,0,numero_rastreamento_envio));
		$envio_chegada                = trim(pg_result($res,0,envio_chegada));
		$nota_fiscal_retorno          = trim(pg_result($res,0,nota_fiscal_retorno));
		$data_nf_retorno              = trim(pg_result($res,0,data_nf_retorno));
		$numero_rastreamento_retorno  = trim(pg_result($res,0,numero_rastreamento_retorno));
		$retorno_chegada              = trim(pg_result($res,0,retorno_chegada));
	} else{
		$retorno=0;
	}
}
if ($retorno==1 AND strlen($nota_fiscal_envio)==0){
	$sql_status = "SELECT status_os, observacao
					FROM tbl_os_status
					WHERE os=$os
					AND status_os IN (72,73,62,64,65,87,88)
					ORDER BY data DESC LIMIT 1";
	$res_status = pg_exec($con,$sql_status);
	$resultado = pg_numrows($res_status);
	if ($resultado==1){
		$status_os          = trim(pg_result($res_status,0,status_os));
		$status_observacao  = trim(pg_result($res_status,0,observacao));
		if ($status_os==65){
			if ($login_fabrica==3){
				echo "<br>
					<center>
					<b style='font-size:'15px''>".traduz("este.produto.deve.ser.enviado.para.a.assistencia.tecnica.da.fabrica",$con,$cook_idioma).".</b><br>
					<div style='font-family:verdana;border:1px dashed #666666;padding:10px;width:400px;align:center' align='center'>
						<b style='font-size:14px;color:red'>".strtoupper(traduz("urgente.produto.para.reparo",$con,$cook_idioma))."</b><br><br>
						<b style='font-size:14px'>BRITÂNIA ELETRODOMÉSTICOS LTDA</b>.<br>
						<b style='font-size:12px'>Rua Dona Francisca, 8300 Mod 4 e 5 Bloco A<br>
						Cep 89.239-270 - Joinville - SC<br>
						A/C ASSISTÊNCIA TÉCNICA</b>
					</div></center><br>
				";
			}else{
				echo "<br>
					<center>
					<b style='font-size:'15px''>".traduz("este.produto.deve.ser.enviado.para.a.assistencia.tecnica.da.fabrica",$con,$cook_idioma).".</b><br></center><br>
				";
			}
		}
		if ($status_os==72){
			echo "<br>
				<center>
				<b style='font-size:'15px''>".traduz("pedido.de.peca.necessita.de.autorizacao",$con,$cook_idioma)."</b><br>
				<div style='font-family:verdana;border:1px dashed #666666;padding:10px;width:400px;align:center' align='center'>
					<b style='font-size:12px'>".traduz("a.peca.solicitada.necessita.de.autorizacao",$con,$cook_idioma).". <br>".traduz("aguarde.a.fabrica.analisar.seu.pedido",$con,$cook_idioma).".</b>
				</div></center><br>
			";
		}
		if ($status_os==73){
			echo "<br>
				<center>
				<b style='font-size:'15px''>".traduz("pedido.de.peca.necessita.de.autorizacao",$con,$cook_idioma)."</b><br>
				<div style='font-family:verdana;border:1px dashed #666666;padding:10px;width:400px;align:center' align='center'>
					<b style='font-size:12px'>$status_observacao</b>
				</div></center><br>
			";
		}
	}
}

if ($retorno==1 AND strlen($msg_erro)>0){
	if (strpos($msg_erro,'date')){
		//$msg_erro = "Data de envio incorreto!";
	}
	echo "<center>
			<div style='font-family:verdana;width:400px;align:center;background-color:#FF0000' align='center'>
				<b style='font-size:14px;color:white'>".strtoupper(traduz("erro",$con,$cook_idioma))."<br>$msg_erro</b>
			</div></center>";
}else {
	if (strlen($msg)>0){
		echo "<center>
			<div style='font-family:verdana;width:400px;align:center;' align='center'>
				<b style='font-size:14px;color:black'>".strtoupper(traduz("erro",$con,$cook_idioma))."<br>$msg</b>
			</div></center>";
	}
}
if (strlen($msg_erro)>0){
	echo "<center>
			<div style='font-family:verdana;width:400px;align:center;background-color:#FF0000' align='center'>
				<b style='font-size:14px;color:white'>".strtoupper(traduz("erro",$con,$cook_idioma))."<br>$msg_erro</b>
			</div></center>";
}

if ($retorno==1 AND !$nota_fiscal_envio AND !$data_nf_envio AND (!$numero_rastreamento_envio OR $login_fabrica==6)) {
?>
<br>
<form name="frm_consulta" method="post" action="<?echo "$PHP_SELF?os=$os"?>">
    <TABLE width='400' border="1" cellspacing="2" cellpadding="0" align='center' style='border-collapse: collapse' bordercolor='#485989'>
			<TR>
				<TD class="inicio" background='admin/imagens_admin/azul.gif' height='19px'> &nbsp;<? echo strtoupper(traduz("envio.do.produto.a.fabrica",$con,$cook_idioma));?></TD>
			</TR>
			<TR>
				<TD class="subtitulo" height='19px'><? echo strtoupper(traduz("preencha.os.dados.do.envio.do.produto.a.fabrica",$con,$cook_idioma));?></TD>
			</TR>
			<TR>
				<TD class="titulo3"><br>
				<? echo strtoupper(traduz("numero.da.nota.fiscal",$con,$cook_idioma));?>&nbsp;<input class="inpu" type="text" name="txt_nota_fiscal" size="25" maxlength="6" value="<? echo 	$nota_fiscal_envio_p ?>">
				<br>
				<? strtoupper(traduz("data.da.nota.fiscal.do.envio",$con,$cook_idioma));?> &nbsp;<input class="inpu" type="text" name="txt_data_envio" size="25" maxlength="10" value="<? echo $data_envio_p ?>">
				<br>

				<?  if ($login_fabrica <> 6){ ?>
					<? strtoupper(traduz("numero.o.objeto.pac",$con,$cook_idioma ));?> &nbsp;<input class="inpu" type="text" name="txt_rastreio" size="25" maxlength="13" value="<? echo $numero_rastreio_p ?>"> <br>
					Ex.: SS987654321
					<br>
				<? } ?>

				<center><input type="hidden" name="btn_acao" value="">
				<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_consulta.btn_acao.value == '' ) { document.frm_consulta.btn_acao.value='gravar' ; document.frm_consulta.submit() } else { alert ('<?fecho ("aguarde.submissao",$con,$cook_idioma);?>') }" ALT="<?fecho("gravar.dados",$con,$cook_idioma);?>" id='botao_gravar' border='0' style="cursor:pointer;"></center><br>
				</TD>
			</TR>
    </TABLE>
</form><br><br>
<?
}

/***************************************************************************************************/
if ($login_fabrica==51){ //HD 48003
	$sql_status = "SELECT
				status_os,
				observacao,
				tbl_admin.login,
				to_char(tbl_os_status.data, 'dd/mm/yyyy') AS data
				FROM tbl_os_status
				LEFT JOIN tbl_admin USING(admin)
				WHERE os=$os
				AND status_os IN (72,73,62,64,65,87,88,98,99,100,101,102,103,104,116,117)
				ORDER BY data DESC LIMIT 1";

	$res_status = pg_exec($con,$sql_status);
	$resultado = pg_numrows($res_status);
	if ($resultado==1){
		$data_status        = trim(pg_result($res_status,0,data));
		$status_os          = trim(pg_result($res_status,0,status_os));
		$status_observacao  = trim(pg_result($res_status,0,observacao));
		$intervencao_admin  = trim(pg_result($res_status,0,login));

		if (strlen($intervencao_admin)>0 AND $login_fabrica<>50){
			$intervencao_admin = "<br><b>OS em intervenção colocada pela Fábrica ($intervencao_admin)</b>";
		}

		if ($status_os==72 or $status_os==116) {
			echo "<br>
				<center>
				<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
					<b style='font-size:14px;color:red;width:100%'>Pedido de peça necessita de autorização</b><br>
					<b style='font-size:11px'>A peça solicitada necessita de autorização. <br>O PA aguarda a fábrica analisar o pedido</b>
					$intervencao_admin
				</div>
				</center><br>
			";
		}
		if ($status_os==73 or $status_os==117) {
			echo "<br>
				<center>
				<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
					<b style='font-size:14px;color:red;width:100%'>Pedido de peça necessita de autorização</b><br>
					<b style='font-size:11px'>$status_observacao</b>
				</div>
				</center><br>
			";
		}
		if ($status_os==62){
			echo "<br>
				<center>
				<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
					<b style='font-size:14px;color:red;width:100%'>OS Sob Intervenção da Assistência Técnica da Fábrica</b><br>
					<b style='font-size:11px'>$status_observacao</b>
				</div>
				</center><br>
			";
		}
		if ($status_os==64){
			echo "<br>
				<center>
				<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
					<b style='font-size:14px;color:red;width:100%'>OS Liberada da Assistência Técnica da Fábrica</b><br>
					<b style='font-size:11px'>$status_observacao</b>
				</div>
				</center><br>
			";
		}
		if ($status_os==88){
			echo "<br>
				<center>
				<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
					<b style='font-size:14px;color:red;width:100%'>Pedido de peça necessita de autorização</b><br>
					<b style='font-size:11px'>$status_observacao</b>
				</div>
				</center><br>
			";
		}
		if ($status_os==87){
			echo "<br>
				<center>
				<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
					<b style='font-size:14px;color:red;width:100%'>Pedido de peça necessita de autorização</b><br>
					<b style='font-size:11px'>A peça solicitada necessita de autorização. ";
				if ($login_fabrica==1){
					echo "<br>Entrar em contato com o Suporte de sua região.</b>";
				}else{
					echo "<br>Aguarde a fábrica analisar seu pedido.</b>";
				}
			echo "</div>
				</center><br>
			";
		}

	}
}
/**********************************************************************************************/

#HD 44202 - intervenção OS aberta
if (strlen($status_os_aberta)>0 AND $login_fabrica==3) {
	$status_os_aberta_inter= "";
	if($status_os_aberta == 122) {
		$status_os_aberta_inter = "<br><b style='font-size:11px'>". traduz("os.com.intervencao.da.fabrica.aguardando.liberacao",$con,$cook_idioma). "</b>";
	}
	echo "<br>
		<center>
		<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
			<b style='font-size:14px;color:red;width:100%'>".traduz("status.os",$con,$cook_idioma)."</b>
			 $status_os_aberta_inter <br>
			<b style='font-size:11px'>$status_os_aberta_obs </b>
		</div>
		</center><br>";

	if ($status_os_aberta== 120) {
	?>
	<form name="frm_os_aberta" method="post" action="<?echo "$PHP_SELF?os=$os"?>">
		<TABLE width='400' border="1" cellspacing="2" cellpadding="0" align='center' style='border-collapse: collapse' bordercolor='#485989'>
			<TR>
				<TD class="inicio" background='admin/imagens_admin/azul.gif' height='19px'> &nbsp;<? echo strtoupper(traduz("os.em.intervencao",$con,$cook_idioma));?></TD>
			</TR>
			<TR>
				<TD class="subtitulo" height='19px'><? echo strtoupper(traduz("digite.a.justificativa",$con,$cook_idioma)) .":";?></TD>
			</TR>
			<TR>
				<TD class="titulo3"><br>
				<? echo strtoupper(traduz("justificativa",$con,$cook_idioma));?>&nbsp;<input class="inpu" type="text" name="txt_justificativa_os_aberta" size="60" maxlength="60" value="">
				<br>
				<center><input type="hidden" name="btn_acao" value="">
				<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_os_aberta.btn_acao.value == '' ) { document.frm_os_aberta.btn_acao.value='gravar_justificativa' ; document.frm_os_aberta.submit() } else { alert ('<?fecho ("aguarde.submissao",$con,$cook_idioma);?>') }" ALT="<?fecho("gravar.dados",$con,$cook_idioma);?>" id='botao_gravar_justificativa' border='0' style="cursor:pointer;"></center><br>
				</TD>
			</TR>
		</TABLE>
	</form>
	<?
	}
}


if ($retorno==1 AND $nota_fiscal_envio AND $data_nf_envio AND ($numero_rastreamento_envio OR $login_fabrica==6)) {
	if (strlen($envio_chegada)==0){
		echo "<BR><b style='font-size:14px;color:#990033'>".traduz("o.produto.foi.enviado.mas.a.fabrica.ainda.nao.confirmou.seu.recebimento",$con,$cook_idioma).".<br> .".traduz("aguarde.a.fabrica.confirmar.o.recebimento.efetuar.o.reparo.e.retornar.o.produto.ao.seu.posto",$con,$cook_idioma).".</b><BR>";
	}else {
		if (strlen($data_nf_retorno)==0){
			echo "<BR><b style='font-size:14px;color:#990033'>".traduz("o.produto.foi.recebido.pela.fabrica.em.%",$con,$cook_idioma,array($envio_chegada))."<br> ".traduz("aguarde.a.fabrica.efetuar.o.reparo.e.enviar.ao.seu.posto",$con,$cook_idioma).".</b><BR>";
		}
		else{
			if (strlen($retorno_chegada)==0){
				echo "<BR><b style='font-size:14px;color:#990033'>".traduz("o.reparo.do.produto.foi.feito.pela.fabrica.e.foi.enviado.ao.seu.posto.em.%",$con,$cook_idioma,array($data_nf_retorno))."<br>".traduz("confirme.apos.o.recebimento",$con,$cook_idioma)."</b><BR>";
			}
			else {
				#echo "<BR><b style='font-size:14px;color:#990033'>O REPARO DO PRODUTO FOI FEITO PELA FÁBRICA.</b><BR>";
			}
		}
	}
	?>
	<?
	if ($nota_fiscal_retorno AND $retorno_chegada=="") {?>
	<form name="frm_confirm" method="post" action="<?echo "$PHP_SELF?os=$os&chegada=$os"?>">
		<TABLE width='420' border="1" cellspacing="0" cellpadding="0" align='center' style='border-collapse: collapse' bordercolor='#485989'>
				<TR>
					<TD class="inicio" background='admin/imagens_admin/azul.gif' height='19px'> <?echo strtoupper(traduz("confirme.a.data.do.recebimento",$con,$cook_idioma));?></TD>
				</TR>
			<TR>
				<TD class="subtitulo" height='19px' colspan='2'><?echo strtoupper(traduz("o.produto.foi.enviado.para.seu.posto.confirme.seu.recebimento",$con,$cook_idioma));?></TD>
			</TR>
					<TD class="titulo3"><br>
					<?echo strtoupper(traduz("data.da.chegada.do.produto",$con,$cook_idioma));?>&nbsp;<input class="inpu" type="text" name="txt_data_chegada_posto" size="20" maxlength="10" value=""> <br><br>
					<center>
					<input type="hidden" name="btn_acao" value="">
					<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_confirm.btn_acao.value == '' ) { document.frm_confirm.btn_acao.value='confirmar' ; document.frm_confirm.submit() } else { alert ('<?fecho ("aguarde.submissao",$con,$cook_idioma);?>') }" ALT="<?fecho("gravar.dados",$con,$cook_idioma);?>" id='botao_gravar' border='0' style="cursor:pointer;"></center><br>
					</TD>
				</TR>
        </TABLE>
    </form>
    <?}?>

    <br>
    <TABLE width='420' border="1" cellspacing="0" cellpadding="0" align='center' style='border-collapse: collapse' bordercolor='#485989'>
			<TR>
				<TD class="inicio" background='admin/imagens_admin/azul.gif' height='19px' colspan='2'> &nbsp;<?echo strtoupper(traduz("envio.do.produto.a.fabrica",$con,$cook_idioma));?></TD>
			</TR>
			<TR>
				<TD class="subtitulo" height='19px' colspan='2'><?echo strtoupper(traduz("informacoes.do.envio.do.produto.a.fabrica",$con,$cook_idioma));?></TD>
			</TR>
			<TR>
				<TD class="titulo3"><?echo strtoupper(traduz("numero.da.nota.fiscal.de.envio",$con,$cook_idioma));?> &nbsp;</TD>
				<TD class="conteudo" >&nbsp;<? echo $nota_fiscal_envio ?></TD>
			</TR>
			<TR>
				<TD class="titulo3"><?echo strtoupper(traduz("data.da.nota.fiscal.do.envio",$con,$cook_idioma));?> &nbsp;</TD>
				<TD class="conteudo" >&nbsp;<? echo $data_nf_envio ?></TD>
			</TR>
			<?  if ($login_fabrica <> 6){ ?>
			<TR>
				<TD class="titulo3"><?echo strtoupper(traduz("numero.o.objeto.pac",$con,$cook_idioma));?> &nbsp;</TD>
				<TD class="conteudo" >&nbsp;<? echo "<a href='http://websro.correios.com.br/sro_bin/txect01$.QueryList?P_LINGUA=001&P_TIPO=001&P_COD_UNI=$numero_rastreamento_envio"."BR' target='_blank'>$numero_rastreamento_envio</a>" ?></TD>
			</TR>
			<? } ?>
			<TR>
				<TD class="titulo3"><?echo strtoupper(traduz("data.da.chegada.a.fabrica",$con,$cook_idioma));?> &nbsp;</TD>
				<TD class="conteudo" >&nbsp;<? echo $envio_chegada; ?></TD>
			</TR>
			<TR>
				<TD class="inicio" background='admin/imagens_admin/azul.gif' height='19px' colspan='2'> &nbsp;<?echo strtoupper(traduz("retorno.do.produto.da.fabrica.ao.posto",$con,$cook_idioma));?></TD>
			</TR>
			<TR>
				<TD class="subtitulo" height='19px' colspan='2'><?echo strtoupper(traduz("informacoes.do.retorno.do.produto.ao.posto",$con,$cook_idioma));?></TD>
			</TR>
			<TR>
				<TD class="titulo3"><?echo strtoupper(traduz("numero.da.nota.fiscal.do.retorno",$con,$cook_idioma));?> &nbsp;</TD>
				<TD class="conteudo" >&nbsp;<? echo $nota_fiscal_retorno ?></TD>
			</TR>
			<TR>
				<TD class="titulo3"><?echo strtoupper(traduz("data.do.retorno",$con,$cook_idioma));?> &nbsp;</TD>
				<TD class="conteudo" >&nbsp;<? echo $data_nf_retorno ?></TD>
			</TR>
			<?  if ($login_fabrica <> 6){ ?>
			<TR>
				<TD class="titulo3"><?echo strtoupper(traduz("numero.o.objeto.pac.de.retorno",$con,$cook_idioma));?> &nbsp;</TD>
				<TD class="conteudo" >&nbsp;<? echo ($numero_rastreamento_retorno)?"<a href='http://websro.correios.com.br/sro_bin/txect01$.QueryList?P_LINGUA=001&P_TIPO=001&P_COD_UNI=$numero_rastreamento_retorno"."BR' target='_blank'>$numero_rastreamento_retorno</a>":""; ?></TD>
			</TR>
			<? } ?>
			<TR>
				<TD class="titulo3" ><?echo strtoupper(traduz("data.da.chegada.ao.posto",$con,$cook_idioma));?>&nbsp;</TD>
				<TD class="conteudo" >&nbsp;<? echo $retorno_chegada ?></TD>
			</TR>
    </TABLE>
<br><br>
<?
}

//////////////// OS RETORNO - FABIO 10/01/2007  - FIM  ///////////////////////////////////

##########################################################################################
####################### INFORMÇÕES DE TROCA LENOXX HD 20774 04/06/2008 ###################
##########################################################################################
if($login_fabrica==11 or $login_fabrica==3){//HD 69245
	if ($ressarcimento <> "t") {
		if ($troca_garantia == "t") {
			echo "<TABLE width='700' border='0' cellspacing='1' align='center' cellpadding='0' class='Tabela'>";
			echo "<TR height='30'>";
			echo "<TD align='left' colspan='4'>";
			echo "<font family='arial' size='2' color='#ffffff'><b>";
			echo "&nbsp;".traduz("produto.trocado",$con,$cook_idioma);
			echo "</b></font>";
			echo "</TD>";
			echo "</TR>";

			echo "<tr>";
			if($login_fabrica<>3) echo "<TD align='left' class='titulo4'  height='15' >".traduz("responsavel",$con,$cook_idioma)."</TD>";
			echo "<TD align='left' class='titulo4'  height='15' >".traduz("data",$con,$cook_idioma)."</TD>";
			echo "<TD align='left' class='titulo4'  height='15' colspan='2'>".traduz("trocado.por",$con,$cook_idioma)."</TD>";
		#	echo "<TD class='titulo'  height='15' >&nbsp;</TD>";
			echo "</tr>";
			$sql = "SELECT TO_CHAR(data,'dd/mm/yyyy hh:mm') AS data            ,
							setor                                              ,
							situacao_atendimento                               ,
							tbl_os_troca.observacao                            ,
							tbl_peca.referencia             AS peca_referencia ,
							tbl_peca.descricao              AS peca_descricao  ,
							tbl_causa_troca.descricao       AS causa
					FROM tbl_os_troca
					JOIN tbl_peca        USING(peca)
					JOIN tbl_causa_troca USING(causa_troca)
					JOIN tbl_os          ON tbl_os_troca.os = tbl_os.os
					WHERE tbl_os_troca.os = $os
					AND  tbl_os.fabrica = $login_fabrica; ";
			$resX = pg_exec ($con,$sql);
			if (pg_numrows ($resX) > 0) {
				$troca_data           = pg_result ($resX,0,data);
				$troca_setor          = pg_result ($resX,0,setor);
				$troca_situacao       = pg_result ($resX,0,situacao_atendimento);
				$troca_observacao     = pg_result ($resX,0,observacao);
				$troca_peca_ref       = pg_result ($resX,0,peca_referencia);
				$troca_peca_des       = pg_result ($resX,0,peca_descricao);
				$troca_causa          = pg_result ($resX,0,causa);

				if($troca_situacao == 0) $troca_situacao = "Garantia";
				else                     $troca_situacao .= "% Faturado";

				echo "<tr>";
				if($login_fabrica<>3){
					echo "<TD class='conteudo' align='left' height='15' nowrap>";
					echo "&nbsp;&nbsp;&nbsp;";
					echo $troca_admin;
					echo "&nbsp;&nbsp;&nbsp;";
					echo "</td>";
				}

				echo "<TD class='conteudo' align='left' height='15' nowrap>";
				echo "&nbsp;&nbsp;&nbsp;";
				echo $troca_data;
				echo "&nbsp;&nbsp;&nbsp;";
				echo "</td>";
				echo "<TD colspan='2' class='conteudo' align='left' height='15' nowrap >";
				echo $troca_peca_ref . " - " . $troca_peca_des;
				echo "</td>";
				echo "</tr>";
				if($login_fabrica<>3){
					echo "<tr>";
					echo "<TD align='left' class='titulo4'  height='15' >".traduz("setor",$con,$cook_idioma)."</TD>";
					echo "<TD align='left' class='titulo4'  height='15' >".traduz("situacao.do.atendimento",$con,$cook_idioma)."</TD>";
					if($login_fabrica==11) {
						echo "<TD align='left' class='titulo4'  height='15' colspan='2'>".traduz("causa",$con,$cook_idioma)."</TD>";
					}else{
						echo "<TD align='left' class='titulo4'  height='15' colspan='2'>".traduz("causa.da.troca",$con,$cook_idioma)."</TD>";
					}
					echo "</tr>";
					echo "<tr>";
					echo "<TD class='conteudo' align='left' height='15' nowrap>";
					echo "&nbsp;&nbsp;&nbsp;";
					echo $troca_setor;
					echo "&nbsp;&nbsp;&nbsp;";
					echo "</td>";
					echo "<TD class='conteudo' align='left' height='15' nowrap>";
					echo "&nbsp;&nbsp;&nbsp;";
					echo $troca_situacao;
					echo "&nbsp;&nbsp;&nbsp;";
					echo "<TD class='conteudo' align='left' height='15' nowrap colspan='2'>";
					echo $troca_causa;
					echo "</td>";
					echo "</tr>";

					echo "<tr>";
					echo "<TD class='conteudo' align='left' height='15'  colspan='4'><b>OBS:</b>";
					echo $troca_observacao;
					echo "</td>";
					echo "</tr>";
			#		echo "<TD class='conteudo' height='15' width='80%'>&nbsp;</td>";
					echo "</tr>";
				}

			}else if($login_fabrica<>3) {
				$sql = "SELECT tbl_peca.referencia , tbl_peca.descricao
						FROM tbl_peca
						JOIN tbl_os_item USING (peca)
						JOIN tbl_os_produto USING (os_produto)
						JOIN tbl_os_extra USING (os)
						WHERE tbl_os_produto.os = $os
						AND   tbl_peca.produto_acabado IS TRUE ";
				$resX = pg_exec ($con,$sql);
				if (pg_numrows ($resX) > 0) {
					$troca_por_referencia = pg_result ($resX,0,referencia);
					$troca_por_descricao  = pg_result ($resX,0,descricao);
				}


				echo "<tr>";
				echo "<TD class='conteudo' align='left' height='15' nowrap>";
				echo "&nbsp;&nbsp;&nbsp;";
				echo $troca_admin;
				echo "&nbsp;&nbsp;&nbsp;";
				echo "</td>";
				echo "<TD class='conteudo' align='left' height='15' nowrap>";
				echo "&nbsp;&nbsp;&nbsp;";
				echo $data_fechamento;
				echo "&nbsp;&nbsp;&nbsp;";
				echo "</td>";
				echo "<TD class='conteudo' align='left' height='15' nowrap >";
				echo $troca_por_referencia . " - " . $troca_por_descricao;
				echo "</td>";

		#		echo "<TD class='conteudo' height='15' width='80%'>&nbsp;</td>";
				echo "</tr>";
			}
		}
	}
}
########################### INFORMÇÕES DE TROCA LENOXX - FIM #############################

?>

<?
// Mostra número do Extrato que esta OS's está - A pedido da Edina
// Fabio
// 29/12/2006
if ($login_fabrica==2){
	if (strlen(trim($data_finalizada))>0){
		$query = "SELECT extrato,
					to_char(data_pagamento,'DD/MM/YYYY')  AS data_pagamento,
						data_vencimento
				FROM tbl_os
				JOIN tbl_os_extra using(os)
				JOIN tbl_extrato using(extrato)
				LEFT JOIN tbl_extrato_pagamento using(extrato)
				WHERE tbl_os.os = $os
				AND tbl_os.fabrica = 2;";
		$result = pg_exec ($con,$query);
		if (pg_numrows ($result) > 0) {
			$extrato = pg_result ($result,0,extrato);
			$data_pg = pg_result ($result,0,data_pagamento);
			$data_vcto = pg_result ($result,0,data_vencimento);
			?>
			<TABLE width="700" border="0" cellspacing="1" align='center' cellpadding="0" class='Tabela' >
					<TR ><TD class='inicio' style='text-align:center;'  colspan='4'><?echo strtoupper(traduz("extrato",$con,$cook_idioma));?></td></tr>
					<tr>
						<TD class='titulo' style='padding:0px 5px;' width='120' ><?echo strtoupper(traduz("n.extrato",$con,$cook_idioma));?></td>
						<td    class='conteudo' style='padding:0px 5px;' width='226' >
							<a href='http://www.telecontrol.com.br/assist/os_extrato_detalhe.php?extrato=<? echo $extrato; ?>&posto=<? echo $login_posto; ?>' ><? echo $extrato; ?></a>
						</td>
						<td class='titulo' style='padding:0px 5px;' width='120' ><? echo strtoupper(traduz("data.do.pagamento",$con,$cook_idioma));?></td>
						<td class='conteudo' style='padding:0px 5px;' width='226' >	&nbsp;<b><? echo $data_pg; ?></b>
						</TD>
					</TR>
			</TABLE><br>
			<?
		}
	}
}// fim mostra número do Extrato

if ($login_fabrica == 3 AND $login_e_distribuidor == "t"){
?>
	<center>
	<TABLE width="700" border="0" cellspacing="1" cellpadding="0" class='Tabela' >
			<TR>
				<TD class="titulo" colspan="4"><?echo strtoupper(traduz("posto",$con,$cook_idioma));?>&nbsp;</TD>
			</TR>
			<TR>
				<TD class="conteudo" colspan="4"><? echo "$posto_codigo - $posto_nome"; ?></TD>
			</TR>
	</TABLE>
	</center>
<?
}
?>

<table width='700' border="0" cellspacing="1" cellpadding="0" class='Tabela' align='center'>
    <tr >
        <td rowspan='4' class='conteudo' width='300' ><center><?echo strtoupper(traduz("os.fabricante",$con,$cook_idioma));?><br>&nbsp;<b><FONT SIZE='6' COLOR='#C67700'>
            <?
		if ($login_fabrica == 1) echo $posto_codigo;
		if (strlen($consumidor_revenda) > 0) echo $sua_os ."</FONT> - ". $consumidor_revenda;
		else echo $sua_os;
		if($login_fabrica==3){ echo "<BR><font color='#D81005' SIZE='4' ><strong>$marca_nome</strong></font>";}
            ?>
            <?
		if(strlen($sua_os_offline)>0){
		echo "<table width='300' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
		echo "<tr >";
		echo "<td class='conteudo' width='300' height='25' align='center'><BR><center>";
		if($login_fabrica==20) fecho ("os.interna",$con,$cook_idioma);
		else                   fecho ("os.off.line",$con,$cook_idioma);
		echo " - $sua_os_offline";
		echo "</center></td>";
		echo "</tr>";
		echo "</table>";
}
?>
            </b></center>
        </td>
        <td class='inicio' height='15' colspan='4'>&nbsp;<?echo strtoupper(traduz("datas.da.os",$con,$cook_idioma));?></td>
    </TR>
    <TR>
        <td class='titulo' width='100' height='15'><?echo strtoupper(traduz("abertura",$con,$cook_idioma));?>&nbsp;</td>
        <td class='conteudo' width='100' height='15'>&nbsp;<?echo $data_abertura?></td>
        <td class='titulo' width='100' height='15'><?echo strtoupper(traduz("digitacao",$con,$cook_idioma));?>&nbsp;</td>
        <td class='conteudo' width='100' height='15'>&nbsp;<? echo $data_digitacao ?></td>
    </tr>
    <tr>
        <td class='titulo' width='100' height='15'><?echo strtoupper(traduz("fechamento",$con,$cook_idioma));?>&nbsp;</td>
        <td class='conteudo' width='100' height='15'>&nbsp;<? echo $data_fechamento ?></td>
        <td class='titulo' width='100' height='15'><?echo strtoupper(traduz("finalizada",$con,$cook_idioma));?>&nbsp;</td>
        <td class='conteudo' width='100' height='15'>&nbsp;<? echo $data_finalizada ?></td>

    </tr>
    <tr>
        <TD class="titulo"  height='15'><?echo strtoupper(traduz("data.da.nf",$con,$cook_idioma));?>&nbsp;</TD>
        <TD class="conteudo"  height='15'>&nbsp;<? echo $data_nf ?></TD>
        <td class='titulo' width='100' height='15'><?echo strtoupper(traduz("fechado.em",$con,$cook_idioma));?>

 &nbsp;</td>
        <td class='conteudo' width='100' height='15'>&nbsp;
        <?
        if(strlen($data_fechamento)>0 AND strlen($data_abertura)>0){
                        $sql_data = "SELECT SUM(data_fechamento - data_abertura)as final FROM tbl_os WHERE os=$os";
            $resD = pg_exec ($con,$sql_data);
            if (pg_numrows ($resD) > 0) {
                $total_de_dias_do_conserto = pg_result ($resD,0,final);
            }
            if($total_de_dias_do_conserto==0) {
				fecho("no.mesmo.dia",$con,$cook_idioma) ;
			}
            else echo $total_de_dias_do_conserto;
            if($total_de_dias_do_conserto==1) {
				echo " ".traduz("dia",$con,$cook_idioma) ;
			}
            if($total_de_dias_do_conserto>1) {
				echo " ".traduz("dias",$con,$cook_idioma);
			}
        }else{
            echo strtoupper(traduz("nao.finalizado",$con,$cook_idioma));
        }
        ?>
        </td>
    </tr>
	<? if($login_fabrica==11 or $login_fabrica ==3 or $login_fabrica==15 or $login_fabrica==20) { /*HD 13239 HD 14121 56101*/ ?>
		<tr>
		<td class='titulo' width='100' height='15'>
		<td class='titulo' width='100' height='15'><?echo strtoupper(traduz("consertado",$con,$cook_idioma));?>&nbsp; </td>
		<td class='conteudo' width='100' height='15' colspan ='1' >&nbsp;
		<?
				$sql_data_conserto = "SELECT to_char(tbl_os.data_conserto, 'DD/MM/YYYY HH24:MI' ) as data_conserto
										FROM tbl_os
										WHERE os=$os";
				$resdc = pg_exec ($con,$sql_data_conserto);
				if (pg_numrows ($resdc) > 0) {
					$data_conserto= pg_result ($resdc,0,data_conserto);
				}
				if(strlen($data_conserto)>0){
					echo $data_conserto;
				}else{
					echo "&nbsp;";
				}
			echo "</td>";
			echo "<td class='titulo' width='100'height='15'>&nbsp;</td>";
			echo "<td class='conteudo' width='100' height='15'> </tr>";

		 } ?>
</table>
<?
// CAMPOS ADICIONAIS SOMENTE PARA LORENZETTI
if($login_fabrica==19){
	if(strlen($tipo_os)>0){
		$sqll = "SELECT descricao from tbl_tipo_os where tipo_os=$tipo_os";
		$ress = pg_exec($con,$sqll);
		$tipo_os_descricao = pg_result($ress,0,0);
	}
?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
<TR>
    <TD class="titulo"  height='15' width='90'><?echo strtoupper(traduz("atendimento",$con,$cook_idioma));?>&nbsp;</TD>
    <TD class="conteudo" height='15'>&nbsp;<? echo $codigo_atendimento.' - '.$nome_atendimento ?></TD>
   	<TD class="titulo"  height='15' width='90'><? echo strtoupper(traduz("motivo",$con,$cook_idioma));?>&nbsp;</TD>
    <TD class="conteudo" height='15'>&nbsp;<? echo $tipo_os_descricao; ?></TD>
	<TD class="titulo" height='15' width='90'><?echo strtoupper(traduz("nome.do.tecnico",$con,$cook_idioma));?>&nbsp;</TD>
    <TD class="conteudo" height='15'>&nbsp;<? echo $tecnico_nome ?></TD>
</TR>
</TABLE>
<?
}//FIM DA PARTE EXCLUSIVA DA LORENZETTI

// CAMPOS ADICIONAIS SOMENTE PARA BOSCH
if($login_fabrica==20 OR ($login_fabrica==15 and strlen($tipo_atendimento)>0)){
	if($login_fabrica==20 AND $tipo_atendimento==13 AND $tipo_troca==1){
		$tipo_atendimento = 00;
		$nome_atendimento = "Troca em Cortesia Comercial";
	}
?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
<TR>
	<TD class="titulo"  height='15' width='90'><? echo strtoupper(traduz("atendimento",$con,$cook_idioma));?>&nbsp;</TD>
	<TD class="conteudo" height='15'>&nbsp;<? echo $tipo_atendimento.' - '.$nome_atendimento ?></TD>
	<?if( $tecnico_nome){?>
	<TD class="titulo" height='15'width='90'><?echo strtoupper(traduz("nome.do.tecnico",$con,$cook_idioma));?>&nbsp;</TD>
	<TD class="conteudo" height='15'>&nbsp;<? echo $tecnico_nome ?></TD>
	<?}?>
	<?if($tipo_atendimento=='15' or $tipo_atendimento=='16'){?>
			<TD class="titulo"  height='15' width='90'><?echo strtoupper(traduz("promotor",$con,$cook_idioma));?>&nbsp;</TD>
			<TD class="conteudo" height='15'>&nbsp;<? echo $promotor_treinamento ?></TD>
	<?}?>
</TR>
</TABLE>
<?
}//FIM DA PARTE EXCLUSIVA DA BOSCH
/*
			<TD class="titulo"  height='15' width='90'>AUTORIZAÇÃO&nbsp;</TD>
			<TD class="conteudo" height='15'>&nbsp;<? echo $autorizacao_cortesia ?></TD>
*/
?>

<table width='700' border="0" cellspacing="1" cellpadding="0" class='Tabela' align='center'>
    <?
    #######CONTEUDO ADICIONAL LENOXX - SÓ PARA O POSTO: 14254 - JUNDSERVICE    ###############
    if(($login_posto==14254)and($login_fabrica==11)){?>
        <tr >
            <TD class="titulo" colspan='2' height='15' ><?echo strtoupper(traduz("nota.fiscal.saida",$con,$cook_idioma));?>&nbsp;</TD>
            <TD class="conteudo" colspan='1' height='15' >&nbsp;<? echo $nota_fiscal_saida; ?></TD>
            <TD class="titulo" height='15' ><? echo strtoupper(traduz("data.nf.saida",$con,$cook_idioma));?>&nbsp;</TD>
            <TD class="conteudo" colspan='2' height='15' >&nbsp;<? echo $data_nf_saida; ?></TD>
        </tr>
    <?}
    ################  FIM CONTEUDO LENOXX ##################

	################## CONTEUDO LENOXX ##################
	if($login_fabrica==11){
		if(strlen($troca_garantia_admin)>0){
			$sql = "SELECT login,nome_completo
					FROM tbl_admin
					WHERE admin = $troca_garantia_admin";
			$res2 = pg_exec ($con,$sql);

			if (pg_numrows($res2) > 0) {
				$login                = pg_result ($res2,0,login);
				$nome_completo        = pg_result ($res2,0,nome_completo);
				?>
					<TR>
						<TD class="titulo"  height='15' ><?fecho("usuarios",$con,$cook_idioma);?>&nbsp;</TD>
						<TD class="conteudo" height='15' colspan='3'>&nbsp;<? if($nome_completo )echo $nome_completo; else echo $login;  ?></TD>
						<TD class="titulo" height='15'><?fecho("data",$con,$cook_idioma);?></TD>
						<TD class="conteudo" height='15'>
						<? echo $troca_garantia_data ?></TD>
					</TR>
					<TR>
						<TD class="conteudo"  height='15'colspan='6'>
						<?
						if($troca_garantia=='t')
							echo '<b><center>'.traduz("troca.direta",$con,$cook_idioma).'</center></b>';
						else
							echo '<b><center>'.traduz("troca.via.distribuidor",$con,$cook_idioma).'</center></b>';
						?>
						</TD>
					</TR>
		<?
			}
		}
	}
	################ FIM CONTEUDO LENOXX ##################
?>

    <tr>
        <td class='inicio' height='15' colspan='4'>&nbsp;<?echo strtoupper(traduz("informacoes.do.produto",$con,$cook_idioma));?>&nbsp;</td>
    </tr>
    <tr >
        <TD class="titulo" height='15' width='90'><?echo strtoupper(traduz("referencia",$con,$cook_idioma));?>&nbsp;</TD>
        <TD class="conteudo" height='15' >&nbsp;<? echo $produto_referencia ?></TD>
        <TD class="titulo" height='15' width='90'><?echo strtoupper(traduz("descricao",$con,$cook_idioma));?>&nbsp;</TD>
        <TD class="conteudo" height='15' >&nbsp;<? echo $produto_descricao ?></TD>
        <TD class="titulo" height='15' width='90'><?
			if($login_fabrica==35){
				echo "PO#";
			}else{
				echo strtoupper(traduz("n.de.serie",$con,$cook_idioma));
			}
			?>
			&nbsp;
		</TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $serie ?>&nbsp;</TD>
    <?if($login_fabrica==19){?>
        <TD class="titulo" height='15' width='90'><?echo strtoupper(traduz("qtde",$con,$cook_idioma));?>&nbsp;</TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $qtde ?>&nbsp;</TD>
    <?}?>
	<?if($login_fabrica<>14 AND ($login_posto==6359 OR $login_posto == 4311) OR ($login_fabrica==6 AND $login_posto==4262)){?>
        <TD class="titulo" height='15' width='90'><?echo strtoupper(traduz("rg.produto",$con,$cook_idioma));?>&nbsp;</TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $rg_produto ?>&nbsp;</TD>
	<?}?>
	<?if($login_fabrica==14 AND ($login_posto==6359 OR $login_posto == 7214)){?>
        <TD class="titulo" height='15' width='90'><?echo strtoupper(traduz("numero.controle",$con,$cook_idioma));?>&nbsp;</TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $numero_controle ?>&nbsp;</TD>
	<?}?>

    </tr>
    <? if ($login_fabrica == 1) { ?>
    <tr>
        <TD class="titulo" height='15' width='90'><?echo strtoupper(traduz("voltagem",$con,$cook_idioma));?>&nbsp;</TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $produto_voltagem ?></TD>
        <TD class="titulo" height='15' width='110'><?echo strtoupper(traduz("codigo.fabricacao",$con,$cook_idioma));?>&nbsp;</TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $codigo_fabricacao ?></TD>
        <TD class="conteudo" height='15' colspan='2'></TD>

    </tr>
    <? } ?>
</table>
<? if (strlen($aparencia_produto) > 0) { ?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'  class='Tabela'>
<TR>
    <td class='titulo' height='15' width='300'><?echo strtoupper(traduz("aparencia.geral.do.aparelho.produto",$con,$cook_idioma));?></td>
    <td class="conteudo">&nbsp;<? echo $aparencia_produto ?></td>
</TR>
</TABLE>
<? } ?>
<? if (strlen($acessorios) > 0) { ?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
<TR>
    <TD class='titulo' height='15' width='300'><?echo strtoupper(traduz("acessorios.deixados.junto.com.o.aparelho",$con,$cook_idioma));?></TD>
    <TD class="conteudo">&nbsp;<? echo $acessorios; ?></TD>
</TR>
</TABLE>
<? } ?>
<? if (strlen($defeito_reclamado) > 0) { ?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'class='Tabela'>
    <TR>
        <TD class='titulo' height='15'width='300'>&nbsp;<?echo strtoupper(traduz("informacoes.sobre.o.defeito",$con,$cook_idioma));?></TD>
        <TD class="conteudo" >&nbsp;
            <?
            if (strlen($defeito_reclamado) > 0) {
                $sql = "SELECT tbl_defeito_reclamado.descricao
                        FROM   tbl_defeito_reclamado
                        WHERE  tbl_defeito_reclamado.defeito_reclamado = '$defeito_reclamado'";


                $res = pg_exec ($con,$sql);

                if (pg_numrows($res) > 0) {
                    $descricao_defeito = trim(pg_result($res,0,descricao));

                    echo $descricao_defeito ." - ".$defeito_reclamado_descricao;
                }
            }
            ?>
        </TD>
    </TR>
</TABLE>
<? } ?>
<? if ($login_fabrica == 19 and (strlen($fabricacao_produto) > 0 or strlen($qtde_km) > 0)) { ?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'class='Tabela'>
	<TR>
		<TD class='titulo' height='15'width='300'>Mês e Ano de Fabricação do Produto&nbsp;</TD>
		<TD class="conteudo" >&nbsp;<?echo $fabricacao_produto;?>
		</TD>
		<TD class='titulo' height='15'width='100'>Quilometragem &nbsp;</TD>
		<TD class="conteudo" >&nbsp;<?echo $qtde_km;?>
		</TD>
	</TR>
</TABLE>
<? } ?>
<?if($login_fabrica==6 or $login_fabrica == 30){?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
	<TR>
		<TD class="titulo" height='15' width='300' align='right'><?
			if ($login_fabrica == 6) echo strtoupper(traduz("os.posto",$con,$cook_idioma));
			else                     echo strtoupper(traduz("os.revendedor",$con,$cook_idioma));?></TD>
		<TD class="conteudo" >&nbsp;<? echo $os_posto ?>&nbsp;</TD>
	</TR>
</table>
<?}?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
    <TR>
        <TD  height='15' class='inicio' colspan='4'>&nbsp;<?echo strtoupper(traduz("defeitos",$con,$cook_idioma));?></TD>
    </TR>
    <TR>
        <TD class="titulo" height='15' width='90'><?echo strtoupper(traduz("reclamado",$con,$cook_idioma));?></TD>
        <TD class="conteudo" height='15' width='200'> &nbsp;<?
			// HD 22820
			if($login_fabrica==1){
				if($troca_garantia=='t' or $troca_faturada=='t')	echo $descricao_defeito ;
				else echo $descricao_defeito ; if($defeito_reclamado_descricao)echo " - ".$defeito_reclamado_descricao;
			}elseif($login_fabrica == 19){
				$sql = "SELECT tbl_defeito_reclamado.codigo,tbl_defeito_reclamado.descricao
					FROM tbl_os_defeito_reclamado_constatado
					JOIN tbl_defeito_reclamado USING(defeito_reclamado)
					WHERE os=$os";
				$res = pg_exec ($con,$sql);

				$array_integridade_reclamado = array();

				if(@pg_numrows($res)>0){
					for ($i=0;$i<pg_numrows($res);$i++){
						$aux_defeito_reclamado = pg_result($res,$i,1);
						array_push($array_integridade_reclamado,$aux_defeito_reclamado);
					}
				}
				$lista_defeitos_reclamados = implode($array_integridade_reclamado,", ");
				echo "$lista_defeitos_reclamados";

			}else{

				echo $descricao_defeito ; if($defeito_reclamado_descricao)echo " - ".$defeito_reclamado_descricao;
			}
			?>
			</TD>
        <TD class="titulo" height='15' width='90'><? if($login_fabrica==20){echo strtoupper(traduz("reparo",$con,$cook_idioma));}else echo strtoupper(traduz("constatado",$con,$cook_idioma));?> &nbsp;</td>
        <td class="conteudo" height='15'>&nbsp;
			<?
			//HD 17683 - VÁRIOS DEFEITOS CONSTATADOS
			if($login_fabrica==30 or $login_fabrica ==19 or $login_fabrica == 43){

				$sql = "SELECT DISTINCT tbl_defeito_constatado.codigo,tbl_defeito_constatado.descricao
					FROM tbl_os_defeito_reclamado_constatado
					JOIN tbl_defeito_constatado USING(defeito_constatado)
					WHERE os=$os";

				$res = pg_exec ($con,$sql);

				$array_integridade = array();

				if(@pg_numrows($res)>0){
					for ($i=0;$i<pg_numrows($res);$i++){
						$aux_defeito_constatado = pg_result($res,$i,0).'-'.pg_result($res,$i,1);
						array_push($array_integridade,$aux_defeito_constatado);
					}
				}
				$lista_defeitos = implode($array_integridade,", ");
				echo "$lista_defeitos";
			}else{
			if( $login_fabrica==1){
					if($troca_garantia=='t' or $troca_faturada=='t'){
						echo $defeito_reclamado_descricao;
					}else{
						echo $defeito_constatado_descricao;
					}
				}else{
					if($login_fabrica==20)echo $defeito_constatado_codigo.' - ';
				echo $defeito_constatado_descricao;
				}
			}
			?>
        </TD>
    </TR>

    <TR>
        <TD class="titulo" height='15' width='90'>
            <?
            if($login_fabrica==6 or $login_fabrica==24 or $login_fabrica==43 or $login_fabrica==11 or $login_fabrica==15 or $login_fabrica==3)      echo strtoupper(traduz("solucao",$con,$cook_idioma));
            elseif($login_fabrica==20){
					echo strtoupper(traduz("defeito",$con,$cook_idioma));
			}else{
		          echo strtoupper(traduz("causa",$con,$cook_idioma));
			}
            ?>
        &nbsp;
        </td>
        <td class="conteudo" colspan='3' height='15'>&nbsp;
        <?
        if(($login_fabrica==24 or $login_fabrica==43) and strlen($solucao_os)>0){//takashi 30-11
            $sql="select descricao from tbl_solucao where solucao=$solucao_os and fabrica=$login_fabrica limit 1";
            $xres = pg_exec($con, $sql);
            $xsolucao = trim(pg_result($xres,0,descricao));
            echo "$xsolucao";
        }

        if($login_fabrica==6 OR $login_fabrica==11 or $login_fabrica==15 or $login_fabrica==3){
        if (strlen($solucao_os)>0){
            //chamado 1451 - não estava validando a data...
            $sql_data = "SELECT SUM(validada - '2006-11-05')as total_dias FROM tbl_os WHERE os=$os";
            $resD = pg_exec ($con,$sql_data);
            if (pg_numrows ($resD) > 0) {
                $total_dias = pg_result ($resD,0,total_dias);
            }
            //if($ip=="201.27.30.194") echo $total_dias;
            if ( ($total_dias > 0 AND $login_fabrica==6) OR ($login_fabrica==11)  or $login_fabrica==15 or $login_fabrica==3){
                $sql="select descricao from tbl_solucao where solucao=$solucao_os and fabrica=$login_fabrica limit 1";
                $xres = pg_exec($con, $sql);
                if (pg_numrows($xres)>0){
                    $xsolucao = trim(pg_result($xres,0,descricao));
                    echo "$xsolucao";
                }else{
                    $xsql="SELECT descricao from tbl_servico_realizado where servico_realizado= $solucao_os limit 1";
                    $xres = pg_exec($con, $xsql);
                    $xsolucao = trim(@pg_result($xres,0,descricao));
                    echo "$xsolucao";
                }
			//if($ip=="201.27.30.194") echo $sql;
            }else{
                $xsql="SELECT descricao from tbl_servico_realizado where servico_realizado= $solucao_os limit 1";
                $xres = pg_exec($con, $xsql);
                if (pg_numrows($xres)>0){
                    $xsolucao = trim(pg_result($xres,0,descricao));
                    echo "$xsolucao  - $data_digitacao";
                }else{
                    $sql="select descricao from tbl_solucao where solucao=$solucao_os and     fabrica=$login_fabrica limit 1";
                    $xres = pg_exec($con, $sql);
                    $xsolucao = trim(pg_result($xres,0,descricao));
                    echo "$xsolucao";
                }
            }
        }
        }else{
            if($login_fabrica==20)echo $causa_defeito_codigo.' - ' ;
            echo $causa_defeito_descricao;
            }
         ?>
        </TD>
    </TR>
    <?
    if($login_fabrica==20){
        if($solucao_os){
            $xsql="SELECT descricao from tbl_servico_realizado where servico_realizado= $solucao_os limit 1";
            $xres = pg_exec($con, $xsql);

            $xsolucao = trim(pg_result($xres,0,descricao));

            $sql_idioma = " SELECT * FROM tbl_servico_realizado_idioma
                            WHERE servico_realizado = $solucao_os
                            AND upper(idioma)   = '$sistema_lingua'";
            $res_idioma = @pg_exec($con,$sql_idioma);
            if (@pg_numrows($res_idioma) >0) $xsolucao  = trim(@pg_result($res_idioma,0,descricao));

            echo "<tr>";
            echo "<td class='titulo' height='15' width='90'>".strtoupper(traduz("identificacao",$con,$cook_idioma))."&nbsp;</td>";
            echo "<td class='conteudo'colspan='3' height='15'>&nbsp;$xsolucao</TD>";
            echo "</tr>";
        }
    }
    ?>
</TABLE>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
    <tr>
        <td class='inicio' colspan='4' height='15'>&nbsp;<?echo strtoupper(traduz("informacoes.sobre.o.consumidor",$con,$cook_idioma));?>&nbsp;</td>
    </tr>
    <TR>
        <TD class="titulo" height='15'><?echo strtoupper(traduz("nome",$con,$cook_idioma));?>&nbsp;</TD>
        <TD class="conteudo" height='15' width='300'>&nbsp;<? echo $consumidor_nome ?></TD>
        <TD class="titulo"><?echo strtoupper(traduz("fone",$con,$cook_idioma));?>&nbsp;</TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_fone ?></TD>
    </TR>
	<? if($login_fabrica==3 or $login_fabrica == 45){?>
    <TR>
        <TD class="titulo" height='15'><?echo strtoupper(traduz("telefone.celular",$con,$cook_idioma));?>"&nbsp;</TD>
        <TD class="conteudo" height='15' width='300'>&nbsp;<? echo $consumidor_celular ?></TD>
        <TD class="titulo"><?echo strtoupper(traduz("telefone.comercial",$con,$cook_idioma));?>&nbsp;</TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_fone_comercial ?></TD>
    </TR>
	<?}?>
    <TR>
        <TD class="titulo" height='15'><?echo strtoupper(traduz("cpf.consumidor",$con,$cook_idioma));?>&nbsp;</TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_cpf ?></TD>
        <TD class="titulo" height='15'><?echo strtoupper(traduz("cep",$con,$cook_idioma));?>&nbsp;</TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_cep ?></TD>
    </TR>
    <TR>
        <TD class="titulo" height='15'><?echo strtoupper(traduz("endereco",$con,$cook_idioma));?>&nbsp;</TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_endereco ?></TD>
        <TD class="titulo" height='15'><?echo strtoupper(traduz("numero",$con,$cook_idioma));?>&nbsp;</TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_numero ?></TD>
    </TR>
    <TR>
        <TD class="titulo" height='15'><?echo strtoupper(traduz("complemento",$con,$cook_idioma));?>&nbsp;</TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_complemento ?></TD>
        <TD class="titulo" height='15'><?echo strtoupper(traduz("bairro",$con,$cook_idioma));?>&nbsp;</TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_bairro ?></TD>
    </TR>
    <TR>
        <TD class="titulo"><?echo strtoupper(traduz("cidade",$con,$cook_idioma));?>&nbsp;</TD>
        <TD class="conteudo">&nbsp;<? echo $consumidor_cidade ?></TD>
        <TD class="titulo"><?echo strtoupper(traduz("estado",$con,$cook_idioma));?>&nbsp;</TD>
        <TD class="conteudo">&nbsp;<? echo $consumidor_estado ?></TD>
    </TR>
   <TR>
        <TD class="titulo"><?echo strtoupper(traduz("email",$con,$cook_idioma));?>&nbsp;</TD>
        <TD class="conteudo">&nbsp;<? echo $consumidor_email ?></TD>
		<?if($login_fabrica==1){?>
			<TD class="titulo"><?echo strtoupper(traduz("tipo.consumidor",$con,$cook_idioma));?></TD>
			<TD class="conteudo">&nbsp;<? echo $fisica_juridica ?></TD>
		<?}elseif($login_fabrica==11){?>
			<TD class="titulo"><? echo strtoupper(traduz("fone.rec",$con,$cook_idioma));?></TD>
			<TD class="conteudo">&nbsp;<? echo $consumidor_fone_recado ?></TD>
		<?}else{?>
			<TD class="titulo">&nbsp;</TD>
			<TD class="conteudo">&nbsp;</TD>
		<?}?>
    </TR>

</TABLE>



<?
/*COLORMAQ TEM 2 REVENDAS*/
if($login_fabrica==50){

	$sql = "SELECT
				cnpj,
				to_char(data_venda, 'dd/mm/yyyy') as data_venda
			FROM tbl_numero_serie
			WHERE serie = trim('$serie')";

	$res_serie = pg_exec ($con,$sql);

	if (pg_numrows ($res_serie) > 0) {


		$txt_cnpj       = trim(pg_result($res_serie,0,cnpj));
		$data_venda = trim(pg_result($res_serie,0,data_venda));

		$sql = "SELECT      tbl_revenda.nome              ,
							tbl_revenda.revenda           ,
							tbl_revenda.cnpj              ,
							tbl_revenda.cidade            ,
							tbl_revenda.fone              ,
							tbl_revenda.endereco          ,
							tbl_revenda.numero            ,
							tbl_revenda.complemento       ,
							tbl_revenda.bairro            ,
							tbl_revenda.cep               ,
							tbl_revenda.email             ,
							tbl_cidade.nome AS nome_cidade,
							tbl_cidade.estado
				FROM        tbl_revenda
				LEFT JOIN   tbl_cidade USING (cidade)
				LEFT JOIN   tbl_estado using(estado)
				WHERE       tbl_revenda.cnpj ='$txt_cnpj' ";

		$res_revenda = pg_exec ($con,$sql);

		# HD 31184 - Francisco Ambrozio (06/08/08) - detectei que pode haver
		#   casos em que o SELECT acima não retorna resultado nenhum.
		#   Acrescentei o if para que não dê erros na página.
		$msg_revenda_info = "";
		if (pg_numrows ($res_revenda) > 0) {
			$revenda_nome_1       = trim(pg_result($res_revenda,0,nome));
			$revenda_cnpj_1       = trim(pg_result($res_revenda,0,cnpj));

			$revenda_bairro_1     = trim(pg_result($res_revenda,0,bairro));
			$revenda_cidade_1     = trim(pg_result($res_revenda,0,cidade));
			$revenda_fone_1       = trim(pg_result($res_revenda,0,fone));
		}else{
			$msg_revenda_info = traduz("nao.foi.possivel.obter.informacoes.da.revenda.cliente.colormaq.nome.cnpj.e.telefone",$con,$cook_idioma);
		}

?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
    <tr>
        <td class='inicio' colspan='4' height='15'>&nbsp;<?if($sistema_lingua=='ES')echo strtoupper(traduz("informacoes.da.revenda",$con,$cook_idioma));else echo strtoupper(traduz("informacoes.da.revenda",$con,$cook_idioma ))."(CLIENTE COLORMAQ)";?></td>
    </tr>
	<? if (strlen($msg_revenda_info) > 0){
					echo "<tr>";
					echo "<td class='conteudo' colspan= '4' height='15'><center>$msg_revenda_info</center></td>";
					echo "</tr>";
				} ?>
    <TR>
        <TD class="titulo"  height='15' ><?echo strtoupper(traduz("nome",$con,$cook_idioma));?>&nbsp;</TD>
        <TD class="conteudo"  height='15' width='300'>&nbsp;<? echo $revenda_nome_1 ?></TD>
        <TD class="titulo"  height='15' width='80'><?echo strtoupper(traduz("cnpj.revenda",$con,$cook_idioma));?>&nbsp;</TD>
        <TD class="conteudo"  height='15'>&nbsp;<? echo $revenda_cnpj_1 ?></TD>
    </TR>
    <TR>
	<?//HD 6701 15529 Para posto 4260 Ivo Cardoso mostra a nota fiscal?>
        <TD class="titulo"  height='15'><?echo strtoupper(traduz("fone",$con,$cook_idioma));?>&nbsp;</TD>
        <TD class="conteudo"  height='15'>&nbsp;<? echo $revenda_fone_1  ?></TD>
        <TD class="titulo"  height='15'><?echo strtoupper(traduz("data.da.nf",$con,$cook_idioma));?>&nbsp;</TD>
        <TD class="conteudo"  height='15'>&nbsp;<?echo $data_venda; ?></TD>
    </TR>
</TABLE>
<?
	}
}
/*COLORMAQ TEM 2 REVENDAS - FIM*/
?>

<? // hd 45748
$sql = "SELECT os
		FROM tbl_os_troca_motivo
		WHERE os = $os ";
$res = pg_exec($con,$sql);
if($login_fabrica==20 AND pg_numrows($res)>0) {
	$motivo1 = "Não são fornecidas peças de reposição para este produto";
	$motivo2 = "Há peça de reposição, mas está em falta";
	$motivo3 = "Vicio do produto";
	$motivo4 = "Divergência de voltagem entre embalagem e produto";
	$motivo5 = "Informações adicionais";
	$motivo6 = "Informações complementares";
	$troca = true;
?>

<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
	<tr>
		<td class='inicio' colspan='4' height='15'>
<?
if($sistema_lingua=='ES')echo "Informaciónes sobre LA RAZÓN DE CAMBIO";
else {
	echo "Informações sobre o MOTIVO DA TROCA";
}
?>
<div id="container">
	<div id="page">
<?

		$sql = "SELECT  tbl_servico_realizado.descricao AS servico_realizado,
						tbl_causa_defeito.codigo        AS causa_codigo     ,
						tbl_causa_defeito.descricao     AS causa_defeito
				FROM   tbl_os_troca_motivo
				JOIN   tbl_servico_realizado USING(servico_realizado)
				JOIN   tbl_causa_defeito     USING(causa_defeito)
				WHERE os     = $os
				AND   motivo = '$motivo1'";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)==1){
			echo "OK";
			$identificacao1 = pg_result($res,0,servico_realizado);
			$causa_defeito1 = pg_result($res,0,causa_codigo)." - ".pg_result($res,0,causa_defeito);
		?>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft2" style="width: 200px; " nowrap>
					Data de entrada do produto na assistência técnica
				</div>
			</div>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft" style="width: 200px;font:75%">
					<? echo $data_abertura; ?>
				</div>
			</div>

			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft2" style="width: 200px; " nowrap>
					<br><? echo $motivo1; ?>
				</div>
			</div>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft2" style="width: 200px; " nowrap>
					Identificação do defeito
				</div>
				<div id="contentleft2" style="width: 250px; ">
					Defeito
				</div>
			</div>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft" style="width: 200px;font:75%">
					<? echo $identificacao1; ?>
				</div>
				<div id="contentleft" style="width: 250px;font:75%">
					<? echo $causa_defeito1; ?>
				</div>
			</div>
			<?
			}
			$sql = "SELECT
							TO_CHAR(data_pedido,'DD/MM/YYYY') AS data_pedido    ,
							pedido                                              ,
							PE.referencia                     AS peca_referencia,
							PE.descricao                      AS peca_descricao
					FROM   tbl_os_troca_motivo
					JOIN   tbl_peca            PE USING(peca)
					WHERE os     = $os
					AND   motivo = '$motivo2'";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res)==1){
				$peca_referencia = pg_result($res,0,peca_referencia);
				$peca_descricao  = pg_result($res,0,peca_descricao);
				$data_pedido     = pg_result($res,0,data_pedido);
				$pedido          = pg_result($res,0,pedido);

			?>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft2" style="width: 200px; " nowrap>
					<br><? echo $motivo2?>
				</div>
			</div>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft2" style="width: 200px; " nowrap>
					Código da Peça
				</div>
				<div id="contentleft2" style="width: 200px; ">
					Data do Pedido
				</div>
				<div id="contentleft2" style="width: 200px; ">
					Número do Pedido
				</div>
			</div>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft" style="width: 200px;font:75%">
					<? echo $peca_referencia."-".$peca_descricao; ?>
				</div>
				<div id="contentleft" style="width: 200px;font:75%">
					<? echo $data_pedido; ?>
				</div>
				<div id="contentleft" style="width: 200px;font:75%">
					<? echo $pedido; ?>
				</div>
			</div>
			<?
			}

			$sql = "SELECT  tbl_servico_realizado.descricao AS servico_realizado,
							tbl_causa_defeito.codigo        AS causa_codigo     ,
							tbl_causa_defeito.descricao     AS causa_defeito    ,
							observacao
					FROM   tbl_os_troca_motivo
					JOIN   tbl_servico_realizado USING(servico_realizado)
					JOIN   tbl_causa_defeito     USING(causa_defeito)
					WHERE os     = $os
					AND   motivo = '$motivo3'";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res)==1){
				$identificacao2 = pg_result($res,0,servico_realizado);
				$causa_defeito2 =  pg_result($res,0,causa_codigo)." - ".pg_result($res,0,causa_defeito);
				$observacao1    = pg_result($res,0,observacao);

			?>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft2" style="width: 200px; " nowrap>
					<br><? echo $motivo3?>
				</div>
			</div>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft2" style="width: 200px; " nowrap>
					Identificação do Defeito
				</div>
				<div id="contentleft2" style="width: 200px; ">
					Defeito
				</div>
				<div id="contentleft2" style="width: 200px; ">
					Quais as OS’s deste produto:
				</div>
			</div>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft" style="width: 200px;font:75%">
					<? echo $identificacao2; ?>
				</div>
				<div id="contentleft" style="width: 200px;font:75%">
					<? echo $causa_defeito2; ?>
				</div>
				<div id="contentleft" style="width: 200px;font:75%">
					<? echo $observacao1; ?>
				</div>
			</div>
			<?
			}

			$sql = "SELECT observacao
					FROM   tbl_os_troca_motivo
					WHERE os     = $os
					AND   motivo = '$motivo4'";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res)==1){
				$observacao2    = pg_result($res,0,observacao);
			?>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft2" style="width: 200px; " nowrap>
					<br><? echo $motivo4; ?>
				</div>
			</div>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft2" style="width: 650px; " nowrap>
					Qual a divergência:
				</div>
			</div>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft" style="width: 200px;font:75%">
					<? echo $observacao2; ?>
				</div>
			</div>
			<?
			}
			?>
		</h2>
	</div>
</div>
<?
	$sql = "SELECT observacao
			FROM   tbl_os_troca_motivo
			WHERE os     = $os
			AND   motivo = '$motivo5'";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)==1){
		$observacao3    = pg_result($res,0,observacao);
		?>
		<div id="container">
			<div id="page">
				<h2><?=$motivo5?>
				<div id="contentcenter" style="width: 650px;">
					<div id="contentleft" style="width: 650px;font:75%"><? echo $observacao3;?></div>
				</div>
				</h2>
			</div>
		</div>
		<?
	}
	/* HD 43302 - 26/9/2008 */
	$sql = "SELECT observacao
			FROM   tbl_os_troca_motivo
			WHERE os     = $os
			AND   motivo = '$motivo6'";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)==1){
		$observacao4    = pg_result($res,0,observacao);
		?>
		<div id="container">
			<div id="page">
				<h2><?fecho("informacoes.complementares",$con,$cook_idioma);?>
				<div id="contentcenter" style="width: 650px;">
					<div id="contentleft" style="width: 650px;font:75%"><? echo $observacao4;?></div>
				</div>
				</h2>
			</div>
		</div>
		<?
	}
}
?>
		</td>
	</tr>
</table>


<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
    <tr>
        <td class='inicio' colspan='4' height='15'>&nbsp;<?echo strtoupper(traduz("informacoes.da.revenda",$con,$cook_idioma)); if($login_fabrica==50){ echo " (".strtoupper(traduz("consumidor",$con,$cook_idioma)).")";}?></td>
    </tr>
    <TR>
        <TD class="titulo"  height='15' ><?echo strtoupper(traduz("nome",$con,$cook_idioma));?>&nbsp;</TD>
        <TD class="conteudo"  height='15' width='300'>&nbsp;<? echo $revenda_nome ?></TD>
        <TD class="titulo"  height='15' width='80'><?echo strtoupper(traduz("cnpj.revenda",$con,$cook_idioma));?>&nbsp;</TD>
        <TD class="conteudo"  height='15'>&nbsp;<? echo $revenda_cnpj ?></TD>
    </TR>
    <TR>
	<?//HD 6701 15529 Para posto 4260 Ivo Cardoso mostra a nota fiscal?>
        <TD class="titulo"  height='15'><?echo strtoupper(traduz("nf.numero",$con,$cook_idioma));?>&nbsp;</TD>
        <TD class="conteudo"  height='15'>&nbsp;<FONT COLOR="#FF0000"><? if($login_fabrica==6 and $login_posto==4260 and strlen($nota_fiscal_saida)>0) echo $nota_fiscal_saida ; else echo $nota_fiscal ?></FONT></TD>
        <TD class="titulo"  height='15'><?echo strtoupper(traduz("data.da.nf",$con,$cook_idioma));?>&nbsp;</TD>
        <TD class="conteudo"  height='15'>&nbsp;<? if($login_fabrica==6 and $login_posto==4260 and strlen($data_nf_saida)>0) echo $data_nf_saida ; else echo $data_nf; ?></TD>
    </TR>
    <TR>
        <TD class="titulo"  height='15' ><?echo strtoupper(traduz("fone",$con,$cook_idioma));?>&nbsp;</TD>
        <TD class="conteudo"  height='15' width='300'>&nbsp;<? echo $revenda_fone ?></TD>
		 <TD class="titulo"  height='15'>
		 <?if($login_fabrica==11) {
			echo strtoupper(traduz("email",$con,$cook_idioma));
		}?>&nbsp;</TD>
        <TD class="conteudo"  height='15'>&nbsp; <?if($login_fabrica==11) { echo $revenda_email; }?></TD>
    </TR>
</TABLE>

<?
	/* HD 26244 */
	if ($login_fabrica==30 AND strlen($certificado_garantia)>0){

		$sql_status = "	SELECT	status_os,
								observacao,
								to_char(data, 'DD/MM/YYYY')   as data_status
						FROM tbl_os_status
						WHERE os = $os
						AND status_os IN (105,106,107)
						ORDER BY tbl_os_status.data DESC
						LIMIT 1 ";
		$res_status = pg_exec($con,$sql_status);
		$resultado = pg_numrows($res_status);
		if ($resultado>0){
				$estendida_status_os   = trim(pg_result($res_status,0,status_os));
				$estendida_observacao  = trim(pg_result($res_status,0,observacao));
				$estendida_data_status = trim(pg_result($res_status,0,data_status));

				if ($estendida_status_os == 105){
					$estendida_observacao = "OS em auditoria";
				}
				if ($estendida_status_os == 106){
					$estendida_observacao = "OS Aprovada na Auditoria";
				}
				if ($estendida_status_os == 107){
					$estendida_observacao = "OS Recusada na Auditoria";
				}
			?>

		<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
			<tr>
				<td class='inicio' colspan='4' height='15'>&nbsp;GARANTIA ESTENDIDA </td>
			</tr>
			<TR>
				<TD class="titulo"  height='15' width='90'>LGI&nbsp;</TD>
				<TD class="conteudo"  height='15' width='300'>&nbsp;<? echo $certificado_garantia ?></TD>
				<TD class="titulo"  height='15' width='80'>STATUS ATUAL&nbsp;</TD>
				<TD class="conteudo"  height='15'>&nbsp;<? echo $estendida_observacao ?></TD>
			</TR>
		</TABLE>
<?
		}
	}
?>

<?
/*takashi compressores*/
if($login_fabrica==1){
    $sql = "SELECT     os                                  ,
                    to_char(data, 'DD/MM/YYYY') as  data,
                    to_char(hora_chegada_cliente, 'HH24:MI') as inicio      ,
                    to_char(hora_saida_cliente, 'HH24:MI')   as fim         ,
                    km_chegada_cliente   as km          ,
                    valor_adicional                     ,
                    justificativa_valor_adicional
            FROM tbl_os_visita
            WHERE os=$os";
    $res = pg_exec($con,$sql);
    if(pg_numrows($res)>0){

        echo "<table border='0' cellpadding='0' cellspacing='1' width='700px' align='center' class='Tabela'>";
        echo "<tr class='inicio'>";
        echo "<td width='100%' colspan='6'>&nbsp;".strtoupper(traduz("despesas.de.compressores",$con,$cook_idioma))."</td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td nowrap class='titulo2' rowspan='2'>
            <font size='1' face='Geneva, Arial, Helvetica, san-serif'>".traduz("data.da.visita",$con,$cook_idioma)."</font></td>";
        echo "<td nowrap class='titulo2' rowspan='2'>
            <font size='1' face='Geneva, Arial, Helvetica, san-serif'>".traduz("hora.inicio",$con,$cook_idioma)."</font></td>";
        echo "<td nowrap class='titulo2' rowspan='2'>
            <font size='1' face='Geneva, Arial, Helvetica, san-serif'>".traduz("hora.fim",$con,$cook_idioma)."</font></td>";
        echo "<td nowrap class='titulo2' rowspan='2'>
            <font size='1' face='Geneva, Arial, Helvetica, san-serif'>".traduz("km",$con,$cook_idioma)."</font></td>";
        echo "<td nowrap class='titulo2' colspan='2'>
            <font size='1' face='Geneva, Arial, Helvetica, san-serif'>".traduz("despesas.adicionais",$con,$cook_idioma)."</font></td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td nowrap class='titulo2'>
            <font size='1' face='Geneva, Arial, Helvetica, san-serif'>".traduz("valor",$con,$cook_idioma)."</font></td>";
        echo "<td nowrap class='titulo2'>
            <font size='1' face='Geneva, Arial, Helvetica, san-serif'>".traduz("justificativa",$con,$cook_idioma)."</font></td>";
        echo "</tr>";

        for($i=0;$i<pg_numrows($res);$i++){

            $data                          = pg_result($res,$i,data);
            $inicio                        = pg_result($res,$i,inicio);
            $fim                           = pg_result($res,$i,fim);
            $km                            = pg_result($res,$i,km);
            $valor_adicional               = pg_result($res,$i,valor_adicional);
            $justificativa_valor_adicional = pg_result($res,$i,justificativa_valor_adicional);

            echo "<tr class='conteudo'>";
            echo "<td align='center'>&nbsp;$data                         </td>";
            echo "<td align='center'>&nbsp;$inicio                       </td>";
            echo "<td align='center'>&nbsp;$fim                          </td>";
            echo "<td align='center'>&nbsp;$km                           </td>";
            echo "<td align='center'>&nbsp;".number_format($valor_adicional,2,",",".")."         </td>";
            echo "<td align='center'>&nbsp;$justificativa_valor_adicional</td>";
            echo "</tr>";
        }

        echo "</table>";

    }
}
 ?>

<?
    $sql = "SELECT  tbl_produto.referencia                                        ,
                    tbl_produto.descricao                                         ,
                    tbl_os_produto.serie                                          ,
                    tbl_os_produto.versao                                         ,
                    tbl_os_item.serigrafia                                        ,
                    tbl_os_item.pedido    AS pedido                               ,
                    tbl_os_item.peca                                              ,
                    TO_CHAR (tbl_os_item.digitacao_item,'DD/MM') AS digitacao_item,
                    tbl_defeito.descricao AS defeito                              ,
                    tbl_peca.referencia   AS referencia_peca                      ,
                    tbl_os_item_nf.nota_fiscal                                    ,
                    tbl_peca.descricao    AS descricao_peca                       ,
                    tbl_servico_realizado.descricao AS servico_realizado_descricao,
                    tbl_status_pedido.descricao     AS status_pedido              ,
                    tbl_produto.referencia          AS subproduto_referencia      ,
                    tbl_produto.descricao           AS subproduto_descricao       ,
                    tbl_lista_basica.posicao
            FROM    tbl_os_produto
            JOIN    tbl_os_item USING (os_produto)
            JOIN    tbl_produto USING (produto)
            JOIN    tbl_peca    USING (peca)
            JOIN    tbl_lista_basica       ON  tbl_lista_basica.produto = tbl_os_produto.produto
                                           AND tbl_lista_basica.peca    = tbl_peca.peca
            LEFT JOIN    tbl_defeito USING (defeito)
            LEFT JOIN    tbl_servico_realizado USING (servico_realizado)
            LEFT JOIN    tbl_os_item_nf    ON  tbl_os_item.os_item      = tbl_os_item_nf.os_item
            LEFT JOIN    tbl_pedido        ON  tbl_os_item.pedido       = tbl_pedido.pedido
            LEFT JOIN    tbl_status_pedido ON  tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
            WHERE   tbl_os_produto.os = $os
            ORDER BY tbl_peca.descricao";

    $sql = "(
			SELECT  tbl_produto.referencia                                         ,
                    tbl_produto.descricao                                          ,
                    tbl_os_produto.serie                                           ,
                    tbl_os_produto.versao                                          ,
                    tbl_os_item.os_item                                            ,
                    tbl_os_item.serigrafia                                         ,
                    tbl_os_item.pedido                                             ,
                    tbl_os_item.pedido_item                                        ,
                    tbl_os_item.peca                                               ,
                    tbl_os_item.posicao                                            ,
                    tbl_os_item.obs                                                ,
                    tbl_os_item.custo_peca                                         ,
                    tbl_os_item.servico_realizado AS servico_realizado_peca        ,
					tbl_os_item.peca_serie                                         ,
					tbl_os_item.peca_serie_trocada                                 ,
                    TO_CHAR (tbl_os_item.digitacao_item,'DD/MM') AS digitacao_item ,
                    case
						when tbl_pedido.pedido_blackedecker > 499999 then
							lpad ((tbl_pedido.pedido_blackedecker-500000)::text,5,'0')
						when tbl_pedido.pedido_blackedecker > 399999 then
							lpad ((tbl_pedido.pedido_blackedecker-400000)::text,5,'0')
						when tbl_pedido.pedido_blackedecker > 299999 then
							lpad ((tbl_pedido.pedido_blackedecker-300000)::text,5,'0')
						when tbl_pedido.pedido_blackedecker > 199999 then
							lpad ((tbl_pedido.pedido_blackedecker-200000)::text,5,'0')
						when tbl_pedido.pedido_blackedecker > 99999 then
							lpad ((tbl_pedido.pedido_blackedecker-100000)::text,5,'0')
                    else
                        lpad(tbl_pedido.pedido_blackedecker::text,5,'0')
                    end                                      AS pedido_blackedecker,
					tbl_pedido.seu_pedido                    AS seu_pedido         ,
                    tbl_pedido.distribuidor                                        ,
                    tbl_defeito.descricao           AS defeito                     ,
                    tbl_peca.referencia             AS referencia_peca             ,
                    tbl_peca.bloqueada_garantia     AS bloqueada_pc                ,
                    tbl_peca.peca_critica           AS peca_critica                ,
                    tbl_peca.retorna_conserto       AS retorna_conserto            ,
					tbl_peca.devolucao_obrigatoria  AS devolucao_obrigatoria       ,
                    tbl_os_item_nf.nota_fiscal                                     ,
                    TO_CHAR(tbl_os_item_nf.data_nf,'DD/MM/YYYY') AS data_nf        ,
                    tbl_peca.descricao              AS descricao_peca              ,
                    tbl_servico_realizado.descricao AS servico_realizado_descricao ,
                    tbl_status_pedido.descricao     AS status_pedido               ,
                    tbl_produto.referencia          AS subproduto_referencia       ,
                    tbl_produto.descricao           AS subproduto_descricao        ,
                    tbl_os_item.preco                                              ,
                    tbl_os_item.qtde                                               ,
					tbl_os_item.faturamento_item    AS faturamento_item
            FROM    tbl_os_produto
            JOIN    tbl_os_item USING (os_produto)
            JOIN    tbl_produto USING (produto)
            JOIN    tbl_peca    USING (peca)
            LEFT JOIN tbl_defeito USING (defeito)
            LEFT JOIN tbl_servico_realizado USING (servico_realizado)
            LEFT JOIN tbl_os_item_nf     ON tbl_os_item.os_item      = tbl_os_item_nf.os_item
            LEFT JOIN tbl_pedido         ON tbl_os_item.pedido       = tbl_pedido.pedido
            LEFT JOIN tbl_status_pedido  ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
            WHERE   tbl_os_produto.os = $os
            ORDER BY tbl_peca.descricao
		)UNION(
			SELECT  tbl_produto.referencia                                         ,
                    tbl_produto.descricao                                          ,
                    NULL                   AS  serie                               ,
                    NULL                   AS  versao                              ,
                    tbl_orcamento_item.orcamento_item                              ,
                    NULL                   AS serigrafia                           ,
                    tbl_orcamento_item.pedido                                      ,
                    tbl_orcamento_item.pedido_item                                 ,
                    tbl_orcamento_item.peca                                        ,
                    NULL AS posicao                                                ,
                    NULL AS obs                                                    ,
                    NULL as custo_peco                                             ,
                    tbl_orcamento_item.servico_realizado AS servico_realizado_peca ,
					tbl_os_item.peca_serie                                         ,
					tbl_os_item.peca_serie_trocada                                 ,
                    NULL AS digitacao_item                                         ,
                    CASE WHEN tbl_pedido.pedido_blackedecker > 99999 then
                         LPAD((tbl_pedido.pedido_blackedecker - 100000)::text,5,'0')
                    ELSE
                        LPAD(tbl_pedido.pedido_blackedecker::text,5,'0')
                    end                                      AS pedido_blackedecker,
					tbl_pedido.seu_pedido           AS seu_pedido                  ,
                    tbl_pedido.distribuidor                                        ,
                    tbl_defeito.descricao           AS defeito                     ,
                    tbl_peca.referencia             AS referencia_peca             ,
                    tbl_peca.bloqueada_garantia     AS bloqueada_pc                ,
                    tbl_peca.peca_critica           AS peca_critica                ,
                    tbl_peca.retorna_conserto       AS retorna_conserto            ,
					tbl_peca.devolucao_obrigatoria  AS devolucao_obrigatoria       ,
                    NULL AS nota_fiscal                                            ,
                    NULL AS data_nf                                                ,
                    tbl_peca.descricao              AS descricao_peca              ,
                    tbl_servico_realizado.descricao AS servico_realizado_descricao ,
                    tbl_status_pedido.descricao     AS status_pedido               ,
                    tbl_produto.referencia          AS subproduto_referencia       ,
                    tbl_produto.descricao           AS subproduto_descricao        ,
                    tbl_orcamento_item.preco                                       ,
                    tbl_orcamento_item.qtde                                        ,
					NULL AS faturamento_item
            FROM    tbl_os
			JOIN    tbl_orcamento ON tbl_orcamento.os = tbl_os.os
            JOIN    tbl_orcamento_item ON tbl_orcamento_item.orcamento = tbl_orcamento.orcamento
            JOIN    tbl_produto ON tbl_produto.produto = tbl_os.produto
            JOIN    tbl_peca    USING (peca)
            LEFT JOIN tbl_defeito USING (defeito)
            LEFT JOIN tbl_servico_realizado USING (servico_realizado)
            LEFT JOIN tbl_pedido         ON tbl_orcamento_item.pedido       = tbl_pedido.pedido
            LEFT JOIN tbl_status_pedido  ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
            WHERE   tbl_os.os = $os
            ORDER BY tbl_peca.descricao
		)";
	// Adicionei Este UNION - Fabio 09-10-2007
	$res = pg_exec($con,$sql);
	$total = pg_numrows($res);
	?>

	<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'  class='Tabela'>
	<TR>
		<TD colspan="<? if ($login_fabrica == 1) { echo "9"; }else{ echo "7"; } ?>" class='inicio'>
<?echo "&nbsp;".strtoupper(traduz("diagnosticos.componentes.manutencoes.executadas",$con,$cook_idioma));?>

</TD>
	</TR>
	<TR>
	<!--     <TD class="titulo">EQUIPAMENTO</TD> -->
		<?
		if($os_item_subconjunto == 't') {
			echo "<TD class=\"titulo2\">".strtoupper(traduz("subconjunto",$con,$cook_idioma))."</TD>";
			echo "<TD class=\"titulo2\">".strtoupper(traduz("posicao",$con,$cook_idioma))."</TD>";
		}
		?>
		<TD class="titulo2">
		<? echo strtoupper(traduz("componente",$con,$cook_idioma)); ?>
</TD>
		<TD class="titulo2">
		<? echo strtoupper(traduz("qtd",$con,$cook_idioma)); ?></TD>
		<? if ($login_fabrica == 1 and 1==2) echo "<TD class='titulo'>".strtoupper(traduz("preco",$con,$cook_idioma))."</TD>"; ?>
		<TD class="titulo2"><?echo strtoupper(traduz("digita",$con,$cook_idioma));?></TD>
		<TD class="titulo2">
		<? if($login_fabrica == 20){
				echo strtoupper(traduz("preco.bruto",$con,$cook_idioma));
			}else{
				echo strtoupper(traduz("defeito",$con,$cook_idioma));
			}
?></TD>
		<TD class="titulo2">
<? if($login_fabrica == 20){
				echo strtoupper(traduz("preco.liquido",$con,$cook_idioma));
			}else{
				echo strtoupper(traduz("servico",$con,$cook_idioma));
			}
?>

</TD>
		<TD class="titulo2"><?echo strtoupper(traduz("pedido",$con,$cook_idioma));?></TD>

		<?//chamado 141 - exibir nf do fabricante para distrib apenas britania?>
		<?if ($login_fabrica == 3) { /* ALTERADO TODA A ROTINA DE NF - HD 8973 */?>
			<TD class="titulo2" colspan='2' nowrap><?echo strtoupper(traduz("n.f.fabricante",$con,$cook_idioma));?></TD>
		<?}?>

		<TD class="titulo2"><?echo strtoupper(traduz("nota.fiscal",$con,$cook_idioma));?></TD>

		<?if ($login_fabrica <> 3) {?>
			<TD class="titulo2"><?echo strtoupper(traduz("emissao",$con,$cook_idioma));?></TD>
		<?}

		//Gustavo 12/12/2007 HD 9095
		if ($login_fabrica == 35) {?>
		<TD class="titulo2"><?echo strtoupper(traduz("conhecimento",$con,$cook_idioma));echo "</TD>";
		}
		//linha de informatica da Britania
		$sqllinha =	"SELECT tbl_linha.informatica
					FROM    tbl_os
					JOIN    tbl_produto USING (produto)
					JOIN    tbl_linha USING (linha)
					WHERE   tbl_os.fabrica = $login_fabrica
					AND     tbl_linha.informatica = 't'
					AND     tbl_os.os = $os";
		$reslinha = pg_exec($con,$sqllinha);

		if (pg_numrows($reslinha) > 0) {
			$linhainf = trim(pg_result($reslinha,0,informatica)); //linha informatica para britania
		}
		if ($linhainf == 't') {
			echo "<TD class='titulo2'>".traduz("serie.peca",$con,$cook_idioma)."</TD>";
			echo "<TD class='titulo2'>".traduz("serie.peca.trocada",$con,$cook_idioma)."</TD>";
		}
		?>
	</TR>

	<?
	# Exibe legenda de Peças de Retorno Obrigatório para a Gama
	$exibe_legenda = 0;
	for ($i = 0 ; $i < $total ; $i++) {
		$pedido                  = trim(pg_result($res,$i,pedido));
		$pedido_item             = trim(pg_result($res,$i,pedido_item));
		$pedido_blackedecker     = trim(pg_result($res,$i,pedido_blackedecker));
		$seu_pedido              = trim(pg_result($res,$i,seu_pedido));
		$os_item                 = trim(pg_result($res,$i,os_item));
		$peca                    = trim(pg_result($res,$i,peca));
		$faturamento_item        = trim(pg_result($res,$i,faturamento_item));
		//chamado 141 - britania - pega nota fiscal do distribuidor
		if ($login_fabrica == 3) {
			$nota_fiscal_distrib = trim(pg_result($res,$i,nota_fiscal));
			$data_nf_distrib     = trim(pg_result($res,$i,data_nf));
			$nota_fiscal         = "";
			$data_nf             = "";
			$link_distrib        = 0;
		} else {
			$nota_fiscal         = trim(pg_result($res,$i,nota_fiscal));
			$data_nf             = trim(pg_result($res,$i,data_nf));
		}
		$status_pedido           = trim(pg_result($res,$i,status_pedido));
		$obs_os_item             = trim(pg_result($res,$i,obs));
		$distribuidor            = trim(pg_result($res,$i,distribuidor));
		$digitacao               = trim(pg_result($res,$i,digitacao_item));
		$preco                   = trim(pg_result($res,$i,preco));
		$descricao_peca          = trim(pg_result($res,$i,descricao_peca));
		$preco                   = number_format($preco,2,',','.');

		$peca_serie              = trim(pg_result($res,$i,peca_serie));
		$peca_serie_trocada      = trim(pg_result($res,$i,peca_serie_trocada));

		/*Nova forma de pegar o número do Pedido - SEU PEDIDO  HD 34403 */
		if (strlen($seu_pedido)>0){
			$pedido_blackedecker = fnc_so_numeros($seu_pedido);
		}

		//--=== Tradução para outras linguas ============================= Raphael HD:1212
		$sql_idioma = "SELECT * FROM tbl_peca_idioma WHERE peca = $peca AND upper(idioma) = '$sistema_lingua'";

		$res_idioma = @pg_exec($con,$sql_idioma);
		if (@pg_numrows($res_idioma) >0) {
			$descricao_peca  = trim(@pg_result($res_idioma,0,descricao));
		}
		//--=== Tradução para outras linguas ===================================================================

		/*====--------- INICIO DAS NOTAS FISCAIS ----------===== */
		/* ALTERADO TODA A ROTINA DE NF - HD 8973 */
		/*############ BLACKEDECKER ############*/
		if ($login_fabrica == 1){
			if (strlen ($nota_fiscal) == 0) {
				if (strlen($pedido) > 0) {
					$sql  = "SELECT trim(nota_fiscal) As nota_fiscal ,
							TO_CHAR(data, 'DD/MM/YYYY') AS emissao
							FROM    tbl_pendencia_bd_novo_nf
							WHERE   posto        = $login_posto
							AND     pedido_banco = $pedido
							AND     peca         = $peca";
					$resx = pg_exec ($con,$sql);
					// HD22338
					if (pg_numrows ($resx) > 0 AND 1==2) {
						$nf   = trim(pg_result($resx,0,nota_fiscal));
						$link = 0;
						$data_nf = trim(pg_result($resx,0,emissao));
					}else{
						// HD 30781
						$sql  = "SELECT trim(nota_fiscal_saida) As nota_fiscal_saida ,
							TO_CHAR(data_nf_saida, 'DD/MM/YYYY') AS data_nf_saida
							FROM    tbl_os
							JOIN    tbl_os_produto USING (os)
							JOIN    tbl_os_item USING (os_produto)
							JOIN    tbl_peca USING(peca)
							WHERE   posto        = $login_posto
							AND     tbl_os_item.pedido= $pedido
							AND     tbl_os_item.peca         = $peca
							AND     tbl_peca.produto_acabado IS TRUE ";
						$resnf = pg_exec ($con,$sql);
						if(pg_numrows($resnf) >0){
							$nf   = trim(pg_result($resnf,0,nota_fiscal_saida));
							$link = 0;
							$data_nf = trim(pg_result($resnf,0,data_nf_saida));
						}else{
							$nf      = "Pendente";
							$data_nf = "";
							$link    = 1;
						}
					}
				}else{
					$nf = "";
					$data_nf = "";
					$link = 0;
				}
			}else{
				$nf = $nota_fiscal;
			}

		/*############ BRITANIA ############*/
		}elseif ($login_fabrica == 3){

			//Nota do fabricante para distribuidor
			//NF para BRITANIA (DISTRIBUIDORES E FABRICANTES chamado 141) =============================

			if (strlen($pedido) > 0) {
				if(strlen($distribuidor) > 0){

					$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
									TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
							FROM    tbl_faturamento
							JOIN    tbl_faturamento_item USING (faturamento)
							WHERE   tbl_faturamento_item.pedido  = $pedido
							AND     tbl_faturamento_item.peca    = $peca
							AND     tbl_faturamento.posto = $distribuidor";

					$resx = pg_exec ($con,$sql);
					if (pg_numrows ($resx) > 0) {
						$nf      = trim(pg_result($resx,0,nota_fiscal));
						$data_nf = trim(pg_result($resx,0,emissao));
						$link    = 0;
					} else {
						$nf      = 'Pendente'; #HD 16354
						$data_nf = '';
						$link    = 0;
					}

					if ($distribuidor == 4311) {
						$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
										TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
								FROM    tbl_faturamento
								JOIN    tbl_faturamento_item USING (faturamento)
								WHERE   tbl_faturamento_item.pedido  = $pedido
								/*AND     tbl_faturamento_item.peca  = $peca*/
								AND     tbl_faturamento_item.os_item = $os_item
								";
								//retirado por Samuel 4/12/2007 - Um nf do distrib atendendo 2 os não tem como gravar 2 os_item.
								// Coloquei AND     tbl_faturamento_item.os_item = $os_item - Fabio - HD 7591

						if ($login_posto != 4311) {
						if ($login_e_distribuidor == "t"){
							$sql .= "AND     tbl_faturamento.posto        = $posto_verificado ";
						} else {
							$sql .= "AND     tbl_faturamento.posto        = $login_posto ";
							}
						}

						$sql .= "AND     tbl_faturamento.distribuidor = 4311";

						$resx = pg_exec ($con,$sql);
						if (pg_numrows ($resx) > 0) {
							$nota_fiscal_distrib = trim(pg_result($resx,0,nota_fiscal));
							$data_nf_distrib     = trim(pg_result($resx,0,emissao));
							$link_distrib        = 1;
						} else {
							$nota_fiscal_distrib = "";
							$data_nf_distrib     = "";
							$link_distrib        = 0;
						}
					}

					if($distribuidor != 4311) {
						$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
										TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
								FROM    tbl_faturamento
								JOIN    tbl_faturamento_item USING (faturamento)
								WHERE   tbl_faturamento_item.pedido = $pedido
								AND     tbl_faturamento_item.peca   = $peca
								AND     tbl_faturamento.posto       <> $distribuidor;";
						$resx = pg_exec ($con,$sql);

						if (pg_numrows ($resx) > 0) {
							$nota_fiscal_distrib = trim(pg_result($resx,0,nota_fiscal));
							$data_nf_distrib     = trim(pg_result($resx,0,emissao));
							$link_distrib        = 1;
						} else {
							$nota_fiscal_distrib = "";
							$data_nf_distrib     = "";
							$link_distrib        = 0;
						}
					}
				}else{
					//(tbl_faturamento_item.os = $os) --> HD3709
					/*HD 72977*/
						$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
								TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
						FROM    tbl_faturamento
						JOIN    tbl_faturamento_item USING (faturamento)
						WHERE   tbl_faturamento_item.pedido = $pedido
						AND     tbl_faturamento_item.peca   = $peca
						AND     (length(tbl_faturamento_item.os::text) = 0 OR tbl_faturamento_item.os = $os";

						if($gambiara=='t'){
							$sql .= "OR tbl_faturamento_item.os_item = $os_item )
							AND     tbl_faturamento.posto       = $xlogin_posto";
						}else{
							$sql  .=  ")
							AND     tbl_faturamento.posto       = $login_posto";
						}
						$resx = pg_exec ($con,$sql);

					if (pg_numrows ($resx) > 0){
						$nf                  = trim(pg_result($resx,0,nota_fiscal));
						$data_nf             = trim(pg_result($resx,0,emissao));
						//se fabrica atende direto posto seta a mesma nota

						//hd 22576
						if ($login_posto <> 4311) {
							$nota_fiscal_distrib = trim(pg_result($resx,0,nota_fiscal));
							$data_nf_distrib     = trim(pg_result($resx,0,emissao));
							$link = 1;
						} else {
							$nota_fiscal_distrib = "";
							$data_nf_distrib     = "";
							$link                = 0;
						}
					}else{
						//HD 77790
						$sqly = "SELECT	tbl_faturamento.nota_fiscal                                         ,
													to_char (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao
									FROM tbl_faturamento_item
									JOIN   tbl_faturamento  ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento AND tbl_faturamento.fabrica = $login_fabrica
									JOIN   tbl_peca             ON tbl_faturamento_item.peca = tbl_peca.peca
									WHERE tbl_faturamento_item.pedido = $pedido
									AND     (
													(length(tbl_faturamento_item.os::text) = 0 OR tbl_faturamento_item.os IS NULL)  OR tbl_faturamento_item.os = $os";
						if($gambiara=='t'){
							$sqly .= "OR tbl_faturamento_item.os_item = $os_item )
							AND     tbl_faturamento.posto       = $xlogin_posto";
						}else{
							$sqly  .=  ")
							AND     tbl_faturamento.posto       = $login_posto";
						}
						$resy = pg_exec ($con,$sqly);

						if (pg_numrows ($resy) > 0){
							$nf                  = trim(pg_result($resy,0,nota_fiscal));
							$data_nf             = trim(pg_result($resy,0,emissao));
							//se fabrica atende direto posto seta a mesma nota

							//hd 22576
							if ($login_posto <> 4311) {
								$nota_fiscal_distrib = trim(pg_result($resy,0,nota_fiscal));
								$data_nf_distrib     = trim(pg_result($resy,0,emissao));
								$link = 1;
							} else {
								$nota_fiscal_distrib = "";
								$data_nf_distrib     = "";
								$link                = 0;
							}
						}else{
							$nf                  = "Pendente";
							$data_nf             = "";
							$nota_fiscal_distrib = "";
							$data_nf_distrib     = "";
							$link                = 0;
						}
					}
				}
			}else{
				$nf                  = "";
				$data_nf             = "";
				$nota_fiscal_distrib = "";
				$data_nf_distrib     = "";
				$link = 0;
			}

		/*############ LENOXX ############*/
		}elseif ($login_fabrica==11){
				 # Agora o pedido da peça ta amarrado no faturamento item: Fabio 09/08/2007
				if (strlen($faturamento_item)>0){
						$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
										TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
								FROM    tbl_faturamento
								JOIN    tbl_faturamento_item USING (faturamento)
								WHERE   tbl_faturamento.fabrica=$login_fabrica
								AND     tbl_faturamento_item.faturamento_item = $faturamento_item";
						$resx = pg_exec ($con,$sql);
						#echo nl2br($sql);
						if (pg_numrows ($resx) > 0) {
							$nf      = trim(pg_result($resx,0,nota_fiscal));
							$data_nf = trim(pg_result($resx,0,emissao));
							$link = 1;
						}else{
							$nf ="Pendente";
							$data_nf="";
							$link = 0;
						}
				}else{
					if (strlen($pedido) > 0) {
							$nf ="Pendente";
							$data_nf="";
							$link = 0;
					}else{
							$nf ="";
							$data_nf="";
							$link = 0;
					}
				}

		/*############ CADENCE ############*/
		}elseif ($login_fabrica==35) {
			if (strlen ($nota_fiscal) == 0){
				if (strlen($pedido) > 0) {
					$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
									TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
							FROM    tbl_faturamento
							JOIN    tbl_faturamento_item USING (faturamento)
							WHERE   tbl_faturamento.pedido    = $pedido
							AND     tbl_faturamento_item.peca = $peca;";
					$resx = pg_exec ($con,$sql);

					if (pg_numrows ($resx) > 0) {
						$nf      = trim(pg_result($resx,0,nota_fiscal));
						$data_nf = trim(pg_result($resx,0,emissao));
						$link = 1;
					}else{
						//cadence relaciona pedido_item na os_item
						$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal ,
										TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao,
										tbl_faturamento.posto,
										tbl_faturamento.conhecimento
								FROM    tbl_faturamento
								JOIN    tbl_faturamento_item USING (faturamento)
								WHERE   tbl_faturamento_item.pedido      = $pedido
								AND     tbl_faturamento_item.peca        = $peca
								AND     tbl_faturamento_item.pedido_item = $pedido_item";
						$resx = pg_exec ($con,$sql);

						if (pg_numrows ($resx) > 0) {
							$nf           = trim(pg_result($resx,0,nota_fiscal));
							$data_nf      = trim(pg_result($resx,0,emissao));
							$conhecimento = trim(pg_result($resx,0,conhecimento));
							$link         = 1;
						}else{
							$nf      = "Pendente";
							$data_nf = "";
							$link    = 1;
						}
					}
				}else{
					$nf = "";
					$data_nf = "";
					$link = 0;
				}
			}else{
				$nf = $nota_fiscal;
			}
		/*############ DEMAIS FABRICANTES ############*/
		}else{
			if (strlen ($nota_fiscal) == 0){
				if (strlen($pedido) > 0) {
					$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
									TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
							FROM    tbl_faturamento
							JOIN    tbl_faturamento_item USING (faturamento)
							WHERE   tbl_faturamento.pedido    = $pedido
							AND     tbl_faturamento_item.peca = $peca ";
					if($login_fabrica == 51) $sql.=" AND     tbl_faturamento_item.os_item = $os_item ";
					if ($login_fabrica == 2) {
						$sql = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal     ,
									TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
							FROM (SELECT * FROM tbl_pedido_item WHERE pedido = $pedido) tbl_pedido_item
							JOIN tbl_pedido_item_faturamento_item on tbl_pedido_item.pedido_item = tbl_pedido_item_faturamento_item.pedido_item
							JOIN tbl_faturamento_item ON tbl_pedido_item_faturamento_item.faturamento_item= tbl_faturamento_item.faturamento_item
							JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
							AND tbl_faturamento.fabrica = $login_fabrica
							WHERE    tbl_faturamento_item.peca = $peca";
					}
					$resx = pg_exec ($con,$sql);

					if (pg_numrows ($resx) > 0) {
						$nf      = trim(pg_result($resx,0,nota_fiscal));
						$data_nf = trim(pg_result($resx,0,emissao));
						$link = 1;
					}else{
						$condicao_01 = "";
						if (strlen ($distribuidor) > 0) {
							$condicao_01 = " AND tbl_faturamento.distribuidor = $distribuidor ";
						}
						$sql  = "SELECT
									trim(tbl_faturamento.nota_fiscal)                AS nota_fiscal ,
									TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY')   AS emissao,
									tbl_faturamento.posto                            AS posto
								FROM    tbl_faturamento
								JOIN    tbl_faturamento_item USING (faturamento)
								WHERE   tbl_faturamento_item.pedido = $pedido
								AND     tbl_faturamento_item.peca   = $peca
								$condicao_01 ";
						if($login_fabrica == 51) $sql.=" AND     tbl_faturamento_item.os_item = $os_item ";
						$resx = pg_exec ($con,$sql);

						if (pg_numrows ($resx) > 0) {
							$nf           = trim(pg_result($resx,0,nota_fiscal));
							$data_nf      = trim(pg_result($resx,0,emissao));
							$link         = 1;
						}else{
							$nf      = "Pendente";
							$data_nf = "";
							$link    = 1;
							if($login_fabrica==6 and strlen($data_finalizada)>0){ //hd 3437
								$nf = "Atendido";
							}
						}
					}
				}else{
					$nf = "";
					$data_nf = "";
					$link = 0;
				}
			}else{
				$nf = $nota_fiscal;
			}
		}
		//HD 18479
		if($fabrica==3){
			if((strlen($pedido)>0 AND strlen($peca)>0) AND $nf=="Pendente"){
				$sql = "SELECT motivo
						FROM   tbl_pedido_cancelado
						WHERE  pedido = $pedido
						AND    peca   = $peca
						AND    posto  = $login_posto;";
				$resx = pg_exec ($con,$sql);
				if (pg_numrows ($resx) > 0) {
					$motivo = pg_result($resx,0,motivo);
					$nf           = "<a href='#' title='$motivo'>".traduz("cancelada",$con,$cook_idioma)."</a>";
					$data_nf      = "-";
					$link         = 1;
				}
			}
			//HD 20787
			if(strlen(trim($nota_fiscal_distrib))==0 AND $nf<>'Pendente'){
				$sql = "SELECT motivo
						FROM   tbl_pedido_cancelado
						WHERE  pedido = $pedido
						AND    peca   = $peca
						AND    posto  = $login_posto;";
				$resx = pg_exec ($con,$sql);
				if (pg_numrows ($resx) > 0) {
					$motivo = pg_result($resx,0,motivo);
					$nota_fiscal_distrib = "<a href='#' title='$motivo'>".traduz("cancelada",$con,$cook_idioma)."</a>";
				}
			}
		}
		/*====--------- FIM DAS NOTAS FISCAIS ----------===== */

		// $status_os -> variavel pegada lá em cima
		$msg_peca_intervencao="";

		$bloqueada_pc           = pg_result($res,$i,bloqueada_pc);
		$peca_critica           = pg_result($res,$i,peca_critica);
		$servico_realizado_peca = pg_result($res,$i,servico_realizado_peca);
		$retorna_conserto       = pg_result($res,$i,retorna_conserto);

		$devolucao_obrigatoria  = pg_result($res,$i,devolucao_obrigatoria);

		if (($login_fabrica==1 OR $login_fabrica==3 OR $login_fabrica==6 OR $login_fabrica==11) AND ( $bloqueada_pc=='t' OR $retorna_conserto=='t' OR $peca_critica=='t')){

			if ($login_fabrica==11) {
				$id_servico_realizado			= 61;
				$id_servico_realizado_ajuste	= 498;
			}
			if ($login_fabrica==6) {
				$id_servico_realizado			= 1;
				$id_servico_realizado_ajuste	= 35;
			}
			if ($login_fabrica==3) {
				$id_servico_realizado			= 20;
				$id_servico_realizado_ajuste	= 96;
			}
			if ($login_fabrica==1) {
				$id_servico_realizado			= 62;
				$id_servico_realizado_ajuste	= 64;
			}

			$cor_intervencao = "#FF6666";

			if ($login_fabrica==1 AND $status_os=='87' AND $peca_critica=='t'){
				$cor_intervencao = "#FFFFFF";
			}

			if (($status_os=='62' OR $status_os=='87' OR $status_os=='72' OR $status_os=='116') AND $servico_realizado_peca==$id_servico_realizado){
				$msg_peca_intervencao=" <b style='font-weight:normal;color:$cor_intervencao;font-size:10px'>(".traduz("aguardando.autorizacao.da.fabrica",$con,$cook_idioma).")</b>";
			}

			if (($status_os=='64' OR $status_os=='73' OR $status_os=='88' OR $status_os=='117') AND $servico_realizado_peca==$id_servico_realizado){
				$msg_peca_intervencao=" <b style='font-weight:normal;color:#333333;font-size:10px'>(".traduz("autorizado.pela.fabrica",$con,$cook_idioma).")</b>";
				$cancelou_peca = "sim";
			}

			if (($status_os=='64' OR $status_os=='73' OR $status_os=='88' OR $status_os=='117') AND $servico_realizado_peca==$id_servico_realizado_ajuste){
				$msg_peca_intervencao=" <b style='font-weight:normal;color:#CC0000;font-size:10px'>(".traduz("pedido.cancelado.pela.fabrica",$con,$cook_idioma).")</b>";
				$cancelou_peca = "sim";
			}

			if (($status_os=='62' OR $status_os=='73' OR $status_os=='87' OR $status_os=='116') AND strlen($pedido) > 0 AND $servico_realizado_peca==$id_servico_realizado) {
				$msg_peca_intervencao=" <b style='font-weight:normal;color:#333333;font-size:10px'>(".traduz("autorizado.pela.fabrica",$con,$cook_idioma).")</b>";
				$cancelou_peca = "sim";
			}
		}

		$cor_linha_peca = "";
		if ($login_fabrica==1 AND $status_os=='87' AND $peca_critica=='t'){
			$cor_linha_peca = " ;background-color:#FF2D2D";
		}

		?>
		<TR class="conteudo"
		<?php
			if ($devolucao_obrigatoria == "t" and $login_fabrica == 51){
				$exibe_legenda++;
				echo " style='background-color:#FFC0D0'";
			}?>
		>
		<?
		if($os_item_subconjunto == 't') {
			echo "<TD style=\"text-align:left;\">".pg_result($res,$i,subproduto_referencia) . " - " . pg_result($res,$i,subproduto_descricao)."</TD>";
			echo "<TD style=\"text-align:center;\">".pg_result($res,$i,posicao)."</TD>";
		}
		?>
		<TD
		<?php
			if ($login_fabrica == 51){
				echo " nowrap ";
		}?>
		style="text-align:left;<?=$cor_linha_peca?>"><? echo pg_result($res,$i,referencia_peca) . " - " . $descricao_peca; echo $msg_peca_intervencao?></TD>
		<TD style="text-align:center;<?=$cor_linha_peca?>"><? echo pg_result($res,$i,qtde) ?></TD>
		<?
		if ($login_fabrica == 1 and 1==2) {
			echo "<TD style='text-align:center;'>";
			echo number_format (pg_result($res,$i,custo_peca),2,",",".");
			echo "</TD>";
		}
		if($login_fabrica==20){
			$sql = "SELECT preco FROM tbl_tabela_item WHERE peca = $peca AND tabela = (select tbl_posto_fabrica.tabela from tbl_posto_fabrica JOIN tbl_os USING (posto) WHERE tbl_os.os = $os AND tbl_posto_fabrica.fabrica = $login_fabrica)";
			$res2 = pg_exec ($con,$sql);
			$preco_bruto = number_format (pg_result($res2,0,preco),2,",",".");
		}
		?>
		<TD style="text-align:center;<?=$cor_linha_peca?>"><? echo pg_result($res,$i,digitacao_item) ?></TD>
		<TD
		<?php
			if ($login_fabrica == 51){
				echo " nowrap ";
		}?>
		style="text-align:right;<?=$cor_linha_peca?>"><?   if($login_fabrica == 20)echo $preco_bruto; else echo pg_result($res,$i,defeito); ?></TD>
		<TD
		<?php
			if ($login_fabrica == 51){
				echo " nowrap ";
		}?>
		style="text-align:right;<?=$cor_linha_peca?>"><?   if($login_fabrica == 20)echo $preco; else echo pg_result($res,$i,servico_realizado_descricao) ?></TD>
		<TD
		<?php
			if ($login_fabrica == 51){
				echo " nowrap ";
		}?>
		style="text-align:CENTER;<?=$cor_linha_peca?>">
		<? if(strtolower($nf) <> 'atendido'){?>
			<a href='pedido_finalizado.php?pedido=<? echo $pedido ?>' target='_blank'>
		<?}
			if ($login_fabrica == 1){
				echo $pedido_blackedecker;
			}else{
				echo $pedido;
			}?>
		<? if(strtolower($nf) <> 'atendido'){?>
			</a>
			<?}?>&nbsp;</TD>

		<TD style="text-align:CENTER;<?=$cor_linha_peca?>" nowrap <? if (strlen($data_nf)==0) echo "colspan='2'"; ?>>
		<?
		if (strtolower($nf) <> 'pendente' and strtolower($nf) <> 'atendido'){
			if ($link == 1) {
				echo "<a href='nota_fiscal_detalhe.php?nota_fiscal=".$nf."&peca=$peca' target='_blank'> $nf </a>";
			}else{
				echo "<acronym title='Nota Fiscal do fabricante.' style='cursor:help;'> $nf ";
			}
		}else{
			if($login_fabrica == 51) {
				if($login_posto== 4311) { // HD 52445
					$sql  = "SELECT tbl_embarque.embarque,
								to_char(liberado ,'DD/MM/YYYY') as liberado,
								to_char(embarcado ,'DD/MM/YYYY') as embarcado,
								faturar
						FROM tbl_embarque
						JOIN tbl_embarque_item USING (embarque)
						WHERE tbl_embarque_item.os_item = $os_item ";

					$resX = pg_exec ($con,$sql);
					if (pg_numrows ($resX) > 0) {
						$liberado  = pg_result($resX,0,liberado);
						$embarcado = pg_result($resX,0,embarcado);
						$faturar   = pg_result($resX,0,faturar);

						if(strlen($embarcado) > 0 and strlen($faturar) == 0){
							echo traduz("embarque",$con,$cook_idioma)." " . pg_result ($resX,0,embarque);
						} else {
							echo traduz("embarcada",$con,$cook_idioma)." ". pg_result($resX,0,liberado);
						}
					}else{
						echo "$nf &nbsp;";
					}
				}else{
					$sql  = "SELECT * FROM tbl_pedido_cancelado WHERE os=$os AND peca=$peca and pedido=$pedido";
					$resY = pg_exec ($con,$sql);
					if (pg_numrows ($resY) > 0) {
						echo "<acronym title='".pg_result ($resY,0,motivo)."'>Cancelado</acronym>" ;
					} else {
						if( strtolower($nf) <> 'atendido'){
						echo "<acronym title='".traduz("pendente.com.o.fabricante",$con,$cook_idioma).".' style='cursor:help;'>";
						}
						echo "$nf &nbsp;";
					}
				}
			}else{
				$sql  = "SELECT * FROM tbl_pedido_cancelado WHERE os=$os AND peca=$peca and pedido=$pedido";
				$resY = pg_exec ($con,$sql);
				if (pg_numrows ($resY) > 0) {
					echo "<acronym title='".pg_result ($resY,0,motivo)."'>Cancelado</acronym>" ;
				} else {
					if( strtolower($nf) <> 'atendido'){
					echo "<acronym title='".traduz("pendente.com.o.fabricante",$con,$cook_idioma).".' style='cursor:help;'>";
					}
					echo "$nf &nbsp;";
				}
			}
		}
		?>
		</TD>

		<?//incluido data de emissao por Wellington chamado 141 help-desk

		if (strlen($data_nf) > 0){
			echo "<TD style='text-align:CENTER;' nowrap>";
			echo "$data_nf ";
			echo "</TD>";
		}

		//Gustavo 12/12/2007 HD 9095
		if ($login_fabrica == 35){
			echo "<TD style='text-align:CENTER;' nowrap>";
			echo "<A HREF='http://websro.correios.com.br/sro_bin/txect01$.QueryList?P_LINGUA=001&P_TIPO=001&P_COD_UNI=$conhecimento' target = '_blank'>";
			 echo $conhecimento;
			echo "</A>";
			echo "</TD>";
		}

		?>



		<? //nf do distribuidor - chamado 141
		if ($login_fabrica==3) {
			echo "<TD style='text-align:CENTER;' nowrap>";

			if (strlen($nota_fiscal_distrib) > 0) {

				if ($link_distrib == 1) {
					echo "<acronym title='".traduz("nota.fiscal.do.distribuidor",$con,$cook_idioma).".' style='cursor:help;'><a href='nota_fiscal_detalhe.php?nota_fiscal=".$nota_fiscal_distrib."&peca=$peca' target='_blank'>$nota_fiscal_distrib  - $data_nf_distrib</a>";
				}else{
					echo "<acronym title='".traduz("nota.fiscal.do.distribuidor",$con,$cook_idioma).".' style='cursor:help;'> $nota_fiscal_distrib"." - ".$data_nf_distrib;
				}
			} else {
//				echo "a $nota_fiscal_distrib";
				//se não tiver nota do distrib verifica se está em embarque e exibe numero do embarque
				$sql  = "SELECT tbl_embarque.embarque,
								to_char(liberado ,'DD/MM/YYYY') as liberado,
								to_char(embarcado ,'DD/MM/YYYY') as embarcado,
								faturar
						FROM tbl_embarque
						JOIN tbl_embarque_item USING (embarque)
						WHERE tbl_embarque_item.os_item = $os_item ";

				// HD 7319 Paulo alterou para mostrar dia que liberou o embarque
				$resX = pg_exec ($con,$sql);
				if (pg_numrows ($resX) > 0) {
					$liberado  = pg_result($resX,0,liberado);
					$embarcado = pg_result($resX,0,embarcado);
					$faturar   = pg_result($resX,0,faturar);

					if(strlen($embarcado) > 0 and strlen($faturar) == 0){
						echo traduz("embarque",$con,$cook_idioma)." " . pg_result ($resX,0,embarque);
					} else {
						echo traduz("embarcada",$con,$cook_idioma)." ". pg_result($resX,0,liberado);
					}
				}else{
					//HD 20787
					if(strlen(trim($nota_fiscal_distrib))==0 and $nf<>'Pendente'){
						$sql = "SELECT motivo
								FROM   tbl_pedido_cancelado
								WHERE  pedido = $pedido
								AND    peca   = $peca
								;";
						$resx = @pg_exec ($con,$sql);
						if (@pg_numrows ($resx) > 0) {
							$motivo = pg_result($resx,0,motivo);
							echo  "<a href='#' title='$motivo'>".traduz("cancelada",$con,$cook_idioma)."</a>";
						}
					}
					// HD 7319 Fim
				}
			}
			echo "</TD>";
		}
		//linha de informatica da Britania
		if ($linhainf == 't'){
			echo "<TD style='text-align:CENTER;' nowrap>";
			echo "$peca_serie";
			echo "</TD>";
			echo "<TD style='text-align:CENTER;' nowrap>";
			echo "$peca_serie_trocada";
			echo "</TD>";
		}
		//linha de informatica da Britania

		?>
	</TR>

	<?
	// HD 8412
	if( ($login_fabrica==35 or $login_fabrica==3 ) and strlen($obs_os_item) >0) {
		echo "<tr>";
		echo "<td class='conteudo' colspan='100%'>";
		echo "Obs: $obs_os_item";
		echo "</td></tr>";
	}
	}//Chamado 2365
	if($login_fabrica == 1 AND ($tipo_atendimento == 17 OR $tipo_atendimento == 18 OR $tipo_atendimento == 35)){

		#HD 15198
		$sql  = "SELECT tbl_os_troca.ri                            AS pedido,
						tbl_os.nota_fiscal_saida                   AS nota_fiscal,
						TO_CHAR(tbl_os.data_nf_saida,'DD/MM/YYYY') AS data_nf
				FROM tbl_os_troca
				JOIN tbl_os USING(os)
				WHERE tbl_os.os      = $os
				AND   tbl_os.fabrica = $login_fabrica
				AND   tbl_os.posto   = $login_posto
				; ";
		$resX = pg_exec ($con,$sql);
		if (pg_numrows ($resX) > 0) {
			$Xpedido      = pg_result($resX,0,pedido);
			$Xnota_fiscal = pg_result($resX,0,nota_fiscal);
			$Xdata_nf     = pg_result($resX,0,data_nf);

			#HD 15198

			echo "<tr align='center'>";
				//hd 21461
				$sql = "SELECT descricao
						FROM tbl_produto
						JOIN tbl_os_troca USING(produto)
						WHERE os = $os";
				$res = pg_exec($con, $sql);

				if (pg_numrows($res) > 0) {
					echo "<td class='conteudo' align='center'><center>".pg_result($res,0,0)."</center></td>";
				} else {
					echo "<td class='conteudo' align='center'><center>$produto_descricao</center></td>";
				}

				echo "<td class='conteudo'></td>";
				echo "<td class='conteudo'></td>";
				echo "<td class='conteudo'></td>";
				echo "<td class='conteudo'></td>";
				echo "<td class='conteudo' align='center'><center>$Xpedido</center></td>";
				echo "<td class='conteudo' align='center'><center>$Xnota_fiscal</center></td>";
				echo "<td class='conteudo' align='center'><center>$Xdata_nf</center></td>";
			echo "<tr>";
		}

	}

	?>
</TABLE>

<?
if ($login_fabrica == 51 and $exibe_legenda > 0){
	echo "<BR>\n";
	echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'>\n";
	echo "<TR style='line-height: 12px'>\n";
	echo "<TD width='5' bgcolor='#FFC0D0'>&nbsp;</TD>\n";
	echo "<TD style='padding-left: 10px; font-size: 14px;'><strong>Peças de retorno obrigatório</strong></TD>\n";
	echo "</TR></TABLE>\n";
}

# adicionado por Fabio - 26/03/2007 - hd chamado 1392
# HD 14830 - HBTech
# HD 13618 - NKS
# HD 12657 - Dynacom
if ($login_fabrica==1 OR $login_fabrica==2 OR $login_fabrica==3 OR $login_fabrica==6 OR $login_fabrica==11 OR $login_fabrica==25 OR $login_fabrica==45 OR $login_fabrica==51) {
	$sql_status = "SELECT
						status_os,
						observacao,
						to_char(data, 'DD/MM/YYYY')   as data_status,
						admin
					FROM tbl_os_status
					WHERE os=$os
					AND status_os IN (72,73,62,64,65,87,88,116,117)
					ORDER BY data ASC";
	$res_status = pg_exec($con,$sql_status);
	$resultado = pg_numrows($res_status);
	if ($resultado>0){
		echo "<BR>\n";
		echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>\n";
		echo "<TR>\n";
		if ($login_fabrica==25){
			echo "<TD colspan='7' class='inicio'>&nbsp;".traduz("justificativa.do.pedido.de.peca",$con,$cook_idioma)."</TD>\n";
		}else{
			echo "<TD colspan='7' class='inicio'>&nbsp;".traduz("historico.de.intervencao",$con,$cook_idioma)."</TD>\n";
		}
		echo "</TR>\n";
		for ($j=0;$j<$resultado;$j++){
			$status_os          = trim(pg_result($res_status,$j,status_os));
			$status_observacao  = trim(pg_result($res_status,$j,observacao));
			$status_data        = trim(pg_result($res_status,$j,data_status));
			$status_admin       = trim(pg_result($res_status,$j,admin));

			if (($status_os==72 OR  $status_os==64) AND strlen($status_observacao)>0){
				$status_observacao = strstr($status_observacao,"Justificativa:");
				$status_observacao = str_replace("Justificativa:","",$status_observacao);
			}

			$status_observacao = trim($status_observacao);

			if (strlen($status_observacao)==0 AND $status_os==73) $status_observacao="Autorizado";
			if (strlen($status_observacao)==0 AND $status_os==72) $status_observacao="-";

			if ($login_fabrica==11 AND strlen($status_admin)>0){
				$status_observacao = trim(pg_result($res_status,$j,observacao));
			}

			echo "<TR>\n";
			echo "<TD  class='justificativa' width='100px'  align='center'><b>$status_data</b></TD>\n";

			if ($status_os==72){
				echo "<TD  class='justificativa' width='140px'  align='left' nowrap>&nbsp;<b>".traduz("justificativa.do.posto",$con,$cook_idioma)."</b>&nbsp;</TD>\n";
			}
			if ($status_os==73){
				echo "<TD  class='justificativa' width='140px' align='left' nowrap>&nbsp;<b>".traduz("resposta.da.fabrica",$con,$cook_idioma)."</b>&nbsp;</TD>\n";
			}
			if ($status_os==62){
				echo "<TD  class='justificativa' width='140px'  align='left' nowrap>&nbsp;<b>".traduz("os.em.intervencao",$con,$cook_idioma)."</b>&nbsp;</TD>\n";
			}
			if ($status_os==65){
				echo "<TD  class='justificativa' width='140px'  align='left' nowrap>&nbsp;<b>".traduz("os.em.reparo.na.fabrica",$con,$cook_idioma)."</b>&nbsp;</TD>\n";
			}
			if ($status_os==64){
				echo "<TD  class='justificativa' width='140px'  align='left' nowrap>&nbsp;<b>".traduz("resposta.da.fabrica",$con,$cook_idioma)."</b>&nbsp;</TD>\n";
			}
			if ($status_os==87 OR $status_os==116){
				echo "<TD  class='justificativa' width='140px'  align='left' nowrap>&nbsp;<b>".traduz("fabrica",$con,$cook_idioma)."</b>&nbsp;</TD>\n";
			}
			if ($status_os==88 OR $status_os==117){
				echo "<TD  class='justificativa' width='140px' align='left' nowrap>&nbsp;<b>".traduz("fabrica",$con,$cook_idioma)."</b>&nbsp;</TD>\n";
			}
			echo "<TD  class='justificativa' width='450px' align='left' colspan='5' >&nbsp;$status_observacao</TD>\n";
			echo "</TR>\n";
		}
		echo "</TABLE>\n";
	}
}
?>

<? //hd 24288
if ($login_fabrica==3) {
		$sql_status = "SELECT  tbl_os.os                            ,
								(SELECT tbl_status_os.descricao FROM tbl_status_os where tbl_status_os.status_os = tbl_os_status.status_os) AS status_os ,
								tbl_os_status.observacao              ,
								to_char(tbl_os_status.data, 'dd/mm/yyy') AS data
								FROM tbl_os
						LEFT JOIN tbl_os_status USING(os)
						WHERE tbl_os.os    = $os
						AND   tbl_os.posto = $login_posto
						AND   tbl_os_status.status_os IN(
								SELECT status_os
								FROM tbl_os_status
								WHERE tbl_os.os = tbl_os_status.os
								AND status_os IN (98,99,101) ORDER BY data DESC
						)";
	$res_km = pg_exec($con,$sql_status);

	if(pg_numrows($res_km)>0){
		echo "<BR>\n";
		echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>\n";
		echo "<TR>\n";
		echo "<TD colspan='7' class='inicio'>&nbsp;".traduz("historico.atendimento.domicilio",$con,$cook_idioma)."</TD>\n";
		echo "</TR>\n";

		for($x=0; $x<pg_numrows($res_km); $x++){
			$status_os    = pg_result($res_km, $x, status_os);
			$observacao   = pg_result($res_km, $x, observacao);
			$data         = pg_result($res_km, $x, data);

			echo "<tr>";
				echo "<td class='justificativa'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$status_os</font></td>";
				echo "<td class='justificativa'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$observacao</font></td>";
				echo "<td class='justificativa' align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$data</font></td>";
			echo "</tr>";
		}
		echo "</table>";
	}
}
?>


<?
# adicionado por Fabio
# HD 13940 - Bosch
if ($login_fabrica==20) {
	$sql_status = "SELECT
					tbl_os_status.status_os                                    ,
					tbl_os_status.observacao                                   ,
					to_char(tbl_os_status.data, 'DD/MM/YYYY')   as data_status ,
					tbl_os_status.admin                                        ,
					tbl_status_os.descricao                                    ,
					tbl_admin.nome_completo AS nome                            ,
					tbl_admin.email                                            ,
					tbl_promotor_treinamento.nome  AS nome_promotor            ,
					tbl_promotor_treinamento.email AS email_promotor
				FROM tbl_os
				JOIN tbl_os_status USING(os)
				LEFT JOIN tbl_status_os USING(status_os)
				LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os_status.admin
				LEFT JOIN tbl_promotor_treinamento ON tbl_os.promotor_treinamento = tbl_promotor_treinamento.promotor_treinamento
				WHERE os = $os
				AND status_os IN (92,93,94)
				ORDER BY data ASC";
	$res_status = pg_exec($con,$sql_status);
	$resultado = pg_numrows($res_status);
	if ($resultado>0){
		echo "<BR>\n";
		echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>\n";
		echo "<TR>\n";
		echo "<TD colspan='4' class='inicio'>&nbsp;">traduz("historico",$con,$cook_idioma)."</TD>\n";
		echo "</TR>\n";
		echo "<TR>\n";
		echo "<TD  class='titulo2' width='100px' align='center'><b>".traduz("data",$con,$cook_idioma)."</b></TD>\n";
		echo "<TD  class='titulo2' width='170px' align='left'><b>".traduz("status",$con,$cook_idioma)."</b></TD>\n";
		echo "<TD  class='titulo2' width='260px' align='left'><b>".traduz("observacao",$con,$cook_idioma)."</b></TD>\n";
		echo "<TD  class='titulo2' width='170px' align='left'><b>".traduz("promotor",$con,$cook_idioma)."</b></TD>\n";
		echo "</TR>\n";
		for ($j=0;$j<$resultado;$j++){
			$status_os          = trim(pg_result($res_status,$j,status_os));
			$status_observacao  = trim(pg_result($res_status,$j,observacao));
			$status_data        = trim(pg_result($res_status,$j,data_status));
			$status_admin       = trim(pg_result($res_status,$j,admin));
			$descricao          = trim(pg_result($res_status,$j,descricao));
			$nome               = trim(strtoupper(pg_result($res_status,$j,nome)));
			$email              = trim(pg_result($res_status,$j,email));
			$nome_promotor      = trim(strtoupper(pg_result($res_status,$j,nome_promotor)));
			$email_promotor     = trim(pg_result($res_status,$j,email_promotor));

			echo "<TR>\n";
			echo "<TD  class='justificativa' align='center'><b>".$status_data."</b></TD>\n";
			echo "<TD  class='justificativa' align='left' nowrap>".$descricao."</TD>\n";
			echo "<TD  class='justificativa' align='left'>".$status_observacao."</TD>\n";
			echo "<TD  class='justificativa' align='left' nowrap>";
			if($status_os == 92) { // HD 55196
				echo "<acronym title='".traduz("nome",$con,$cook_idioma).": ".$nome_promotor." - \nEmail:".$email_promotor."'>".$nome_promotor;
			}else{
				echo "<acronym title='".traduz("nome",$con,$cook_idioma).": ".$nome." - \nEmail:".$email."'>".$nome;
			}
			echo "</TD>\n";
			echo "</TR>\n";
		}
		echo "</TABLE>\n";
	}
}
?>


<?// adicionado por Fabio - 05/11/2007 - HD chamado 6525
if ($login_fabrica==3 AND $login_posto == 6359) {
	$sql = "SELECT orcamento
			FROM tbl_orcamento
			WHERE os = $os";
	$res_orca = pg_exec($con,$sql);
	$resultado = pg_numrows($res_orca);
	if ($resultado>0){
		$orcamento = trim(pg_result($res_orca,0,orcamento));
		$sql = "SELECT	tbl_hd_chamado_item.hd_chamado_item,
						TO_CHAR(tbl_hd_chamado_item.data,'DD/MM/YYYY') as data,
						tbl_hd_chamado_item.comentario
				FROM tbl_hd_chamado
				JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado =  tbl_hd_chamado.hd_chamado
				WHERE tbl_hd_chamado.orcamento = $orcamento
				ORDER BY tbl_hd_chamado_item.data ASC";
		$res_orca = pg_exec($con,$sql);
		$resultado = pg_numrows($res_orca);
		if ($resultado>0){
			echo "<BR>\n";
			echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>\n";
			echo "<TR>\n";
			echo "<TD colspan='2' class='inicio'>".traduz("historico.de.orcamento",$con,$cook_idioma)."</TD>\n";
			echo "</TR>\n";
			for ($j=0;$j<$resultado;$j++){
				$orca_hd_chamado_item = trim(pg_result($res_orca,$j,hd_chamado_item));
				$orca_data            = trim(pg_result($res_orca,$j,data));
				$orca_comentario      = trim(pg_result($res_orca,$j,comentario));

				echo "<TR>\n";
				echo "<TD  class='justificativa' width='100px' align='center'><b>$orca_data</b></TD>\n";
				echo "<TD  class='justificativa' width='450px' align='left'>$orca_comentario</TD>\n";
				echo "</TR>\n";
			}
			echo "</TABLE>\n";
		}
	}
}
?>

<?
/* Fabio - 09/11/2007 - HD Chamado 7452 */
$sql="SELECT orcamento,
			total_mao_de_obra,
			total_pecas,
			aprovado,
			TO_CHAR(data_aprovacao,'DD/MM/YYYY') AS data_aprovacao,
			TO_CHAR(data_reprovacao,'DD/MM/YYYY') AS data_reprovacao,
			motivo_reprovacao
		FROM tbl_orcamento
		WHERE empresa = $login_fabrica
		AND   os      = $os";
$resOrca = pg_exec ($con,$sql);
if (pg_numrows($resOrca)>0){
	$orcamento         = pg_result($resOrca,0,orcamento);
	$total_mao_de_obra = pg_result($resOrca,0,total_mao_de_obra);
	$total_pecas       = pg_result($resOrca,0,total_pecas);
	$aprovado          = pg_result($resOrca,0,aprovado);
	$data_aprovacao    = pg_result($resOrca,0,data_aprovacao);
	$data_reprovacao   = pg_result($resOrca,0,data_reprovacao);
	$motivo_reprovacao = pg_result($resOrca,0,motivo_reprovacao);

	$total_mao_de_obra = number_format($total_mao_de_obra,2,",",".");
	$total_pecas       = number_format($total_pecas,2,",",".");

	if ($aprovado=='t'){
		$msg_orcamento = traduz("orcamento.aprovado",$con,$cook_idioma).". ( ".traduz("data",$con,$cook_idioma).": $data_aprovacao )";
	}elseif ($aprovado=='f'){
		$msg_orcamento = traduz("orcamento",$con,$cook_idioma)." <b style='color:red'>".strtoupper(traduz("reprovado",$con,$cook_idioma))."</b>. ".traduz("motivo",$con,$cook_idioma).": $motivo_reprovacao ( ".traduz("data",$con,$cook_idioma).": $data_reprovacao )";
	}else{
		$msg_orcamento = traduz("orcamento.aguardando.aprovacao",$con,$cook_idioma).".";
	}
	echo "<BR>\n";
	echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>\n";
	echo "<TR>\n";
	echo "<TD colspan='2' class='inicio'>".traduz("orcamento",$con,$cook_idioma)."</TD>\n";
	echo "</TR>\n";
	echo "<TR>\n";
	echo "<TD  class='titulo' align='left'><b>".traduz("valor.mao.de.obra",$con,$cook_idioma)."</b></TD>\n";
	echo "<TD  class='justificativa' width='450px' align='left' style='padding-left:10px'>$total_mao_de_obra</TD>\n";
	echo "</TR>\n";
	echo "<TR>\n";
	echo "<TD  class='titulo' align='left'><b>".traduz("valor.pecas",$con,$cook_idioma)."</b></TD>\n";
	echo "<TD  class='justificativa' align='left' style='padding-left:10px'>$total_pecas</TD>\n";
	echo "</TR>\n";
	echo "<TR>\n";
	echo "<TD  class='titulo' align='left'><b>".traduz("aprovacao",$con,$cook_idioma)."</b></TD>\n";
	echo "<TD  class='justificativa' align='left' style='padding-left:10px'>$msg_orcamento</TD>\n";
	echo "</TR>\n";
	echo "</TABLE>\n";
}
?>


<?
//incluido por Welligton 29/09/2006 - Fabricio chamado 472

if (strlen($orientacao_sac) > 0){
	echo "<BR>";
	echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>";
	echo "<TR>";
	echo "<TD colspan=7 class='inicio'>&nbsp;".traduz("orientacoes.do.sac.ao.posto.autorizado",$con,$cook_idioma)."</TD>";
	echo "</TR>";
	echo "<TR>";
	echo "<TD class='conteudo_sac'>Obs: ".nl2br(trim(str_replace("|","<br/>",str_replace("<p>","<br/>",str_replace("</p>","<br/>",str_replace("</p><p>","<br/>",str_replace("null","<br />",$orientacao_sac)))))))."</TD>";
	echo "</TR>";
	echo "</TABLE>";
}
?>
<?
//incluido por takashi 19/10/2007 - hd4536
//qdo OS é fechada com peças ainda pedente o posto tem que informar o motivo, o motivo a gente mostra aqui
if ($login_fabrica == 3){
	$sql = "SELECT obs_fechamento from tbl_os_extra where os=$os";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		$motivo_fechamento = pg_result($res,0,0);
		if(strlen($motivo_fechamento)>0){
			echo "<BR>";
			echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>";
			echo "<TR>";
			echo "<TD colspan=7 class='inicio'>&nbsp;".traduz("justificativa.fechamento.de.os.com.peca.ainda.pendente",$con,$cook_idioma)."</TD>";
			echo "</TR>";
			echo "<TR>";
			echo "<TD class='conteudo'>$motivo_fechamento</TD>";
			echo "</TR>";
			echo "</TABLE>";
		}
	}
}
?>




<?
//Colocado por Fabio - HD 14344
//mostra o status da OS: acumulada ou resucasa
if ($login_fabrica == 25){
	$sql = "SELECT	TO_CHAR(data,'DD/MM/YYYY') AS data,
					tbl_os_status.status_os    AS status_os,
					tbl_os_status.observacao   AS observacao
			FROM tbl_os_extra
			JOIN tbl_os_status USING(os)
			WHERE os = $os
			AND tbl_os_status.status_os IN (13,14)
			AND tbl_os_extra.extrato IS NULL
			ORDER BY tbl_os_status.data DESC
			LIMIT 1";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		echo "<BR>";
		echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>";
		echo "<TR>";
		echo "<TD colspan=7 class='inicio'>".traduz("extrato",$con,$cook_idioma)." - ".traduz("status.da.os",$con,$cook_idioma)."</TD>";
		echo "</TR>";
		for ($i=0; $i<pg_numrows($res); $i++){
			$status_data       = pg_result($res,0,data);
			$status_status_os  = pg_result($res,0,status_os);
			$status_observacao = pg_result($res,0,observacao);

			if ($status_status_os==13){
				$status_status_os = "Recusada";
			}elseif ($status_status_os==14){
				$status_status_os = "Acumulada";
			}else{
				$status_status_os = "-";
			}

			echo "<TR>";
			echo "<TD class='conteudo'>$status_data</TD>";
			echo "<TD class='conteudo'>$status_status_os</TD>";
			echo "<TD class='conteudo' colspan=5>$status_observacao</TD>";
			echo "</TR>";
		}
		echo "</TABLE>";
	}
}
# 53003 - mostrar todas as ocorrências e o admin
if ($login_fabrica == 45){
	$sql = "SELECT	TO_CHAR(data,'DD/MM/YYYY') AS data,
					tbl_os_status.status_os    AS status_os,
					tbl_os_status.observacao   AS observacao,
					tbl_os_status.extrato,
					tbl_admin.nome_completo
			FROM tbl_os_extra
			JOIN tbl_os_status USING(os)
			LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os_status.admin
			WHERE os = $os
			AND tbl_os_status.status_os IN (13,14)
			AND tbl_os_extra.extrato IS NULL
			ORDER BY tbl_os_status.data DESC";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		echo "<BR>";
		echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>";
		echo "<TR>";
		echo "<TD colspan=7 class='inicio'>EXTRATO - STATUS DA OS</TD>";
		echo "</TR>";
		echo "<TR>";
		echo "<TD class='titulo2' align='center'>DATA</TD>";
		echo "<TD class='titulo2' align='center'>ADMIN</TD>";
		echo "<TD class='titulo2' align='center'>EXTRATO</TD>";
		echo "<TD class='titulo2' align='center'>STATUS</TD>";
		echo "<TD class='titulo2' align='center'>OBSERVAÇÃO</TD>";
		echo "</TR>";

		for ($i=0; $i<pg_numrows($res); $i++){
			$status_data       = pg_result($res,$i,data);
			$status_status_os  = pg_result($res,$i,status_os);
			$status_observacao = pg_result($res,$i,observacao);
			$zextrato          = pg_result($res,$i,extrato);
			$admin_nome        = pg_result($res,$i,nome_completo);

			if ($status_status_os==13){
				$status_status_os = "Recusada";
			}elseif ($status_status_os==14){
				$status_status_os = "Acumulada";
			}else{
				$status_status_os = "-";
			}

			echo "<TR>";
			echo "<TD class='conteudo' style='text-align: center'>$status_data</TD>";
			echo "<TD class='conteudo' style='text-align: center'>$admin_nome</TD>";
			echo "<TD class='conteudo' style='text-align: center'>$zextrato</TD>";
			echo "<TD class='conteudo' style='padding-left: 5px'>$status_status_os</TD>";
			echo "<TD class='conteudo' style='padding-left: 5px'>$status_observacao</TD>";
			echo "</TR>";
		}
		echo "</TABLE>";
	}
}
?>


<?

if (strlen($obs) > 0) {
    echo "<BR><TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'>";
    echo "<TR>";
    echo "<TD class='conteudo'><b>".traduz("obs",$con,$cook_idioma).":</b>&nbsp;$obs</TD>";
    echo "</TR>";
    echo "</TABLE>";
}
?>




<!--            Valores da OS           -->
<?
if ($login_fabrica == "20" or $login_fabrica == "30" or $login_fabrica=="50") {

	$pecas              = 0;
	$mao_de_obra        = 0;
	$tabela             = 0;
	$desconto           = 0;
	$desconto_acessorio = 0;

	$sql = "SELECT mao_de_obra
			FROM tbl_produto_defeito_constatado
			WHERE produto = (
				SELECT produto
				FROM tbl_os
				WHERE os = $os
			)
			AND defeito_constatado = (
				SELECT defeito_constatado
				FROM tbl_os
				WHERE os = $os
			)";

	/* HD 19054 */
	if ($login_fabrica==50){
		$sql = "SELECT mao_de_obra
				FROM tbl_os
				WHERE os = $os
				AND fabrica = $login_fabrica";
	}

	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) == 1) {
		$mao_de_obra = pg_result ($res,0,mao_de_obra);
	}

	$sql = "SELECT  tabela,
					desconto,
					desconto_acessorio
			FROM  tbl_posto_fabrica
			WHERE posto = $login_posto
			AND   fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 1) {
		$tabela             = pg_result ($res,0,tabela)            ;
		$desconto           = pg_result ($res,0,desconto)          ;
		$desconto_acessorio = pg_result ($res,0,desconto_acessorio);
	}

	if (strlen ($desconto) == 0) $desconto = "0";

	if (strlen ($tabela) > 0) {

		$sql = "SELECT SUM (tbl_tabela_item.preco * tbl_os_item.qtde) AS total
				FROM tbl_os
				JOIN tbl_os_produto USING (os)
				JOIN tbl_os_item    USING (os_produto)
				JOIN tbl_tabela_item ON tbl_os_item.peca = tbl_tabela_item.peca AND tbl_tabela_item.tabela = $tabela
				WHERE tbl_os.os = $os";
		$res = pg_exec ($con,$sql);

		if (pg_numrows ($res) == 1) {
			$pecas = pg_result ($res,0,0);
		}
	}else{
		$pecas = "0";
	}

	echo "<br><table cellpadding='10' cellspacing='0' border='1' align='center' style='border-collapse: collapse' bordercolor='#485989'>";
	echo "<tr style='font-size: 12px ; color:#53607F ' >";

	if ($login_fabrica==50 or $login_fabrica==30){
		/* HD 24461 - Francisco Ambrozio - ocultar campo Valor Deslocamento,
			caso este for igual a 0*/
		$sql = "SELECT tbl_os.qtde_km_calculada
				FROM tbl_os
				LEFT JOIN tbl_os_extra USING(os)
				WHERE tbl_os.os = $os
					AND tbl_os.fabrica = $login_fabrica";
		$res = pg_exec ($con,$sql);
		$qte_km_vd = pg_result ($res,0,qtde_km_calculada);
		if ($qte_km_vd<>0){
			echo "<td align='center' bgcolor='#E1EAF1'><b>";
			fecho ("valor.deslocamento",$con,$cook_idioma);
			echo "</b></td>";
		}
		if($login_fabrica == 30){
			echo "<td align='center' bgcolor='#E1EAF1'><b>";
			fecho ("valor.das.pecas",$con,$cook_idioma);
			echo "</b></td>";
		}

		echo "<td align='center' colspan='2' bgcolor='#E1EAF1'><b>".traduz("mao.de.obra",$con,$cook_idioma)."</b></td>";
		echo "<td align='center' bgcolor='#E1EAF1'><b>".traduz("total",$con,$cook_idioma)."</b></td>";

	}else{
		echo "<td align='center' bgcolor='#E1EAF1'><b>";
		fecho ("valor.das.pecas",$con,$cook_idioma);
		echo "</b></td>";
		echo "<td align='center' bgcolor='#E1EAF1'><b>";
		fecho ("mao.de.obra",$con,$cook_idioma);
		echo "</b></td>";
		if($sistema_lingua=='ES'){
			echo "<td align='center' bgcolor='#E1EAF1'><b>";
			fecho ("desconto.iva",$con,$cook_idioma);
			echo "</b></td>";
		}
		echo "<td align='center' bgcolor='#E1EAF1'><b>".traduz("total",$con,$cook_idioma)."</b></td>";
	}
	echo "</tr>";

	$valor_liquido = 0;

	if ($desconto > 0 and $pecas <> 0) {
		$sql = "SELECT produto FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) == 1) {
			$produto = pg_result ($res,0,0);
		}
		//echo 'peca'.$pecas;
		if( $produto == '20567' ){
			$desconto_acessorio = '0.2238';
			$valor_desconto = round ( (round ($pecas,2) * $desconto_acessorio ) ,2);

		}else{
			$valor_desconto = round ( (round ($pecas,2) * $desconto / 100) ,2);
		}

		$valor_liquido = $pecas - $valor_desconto ;

	}

	if($login_fabrica==20 ){
		$sql = "select pais from tbl_os join tbl_posto on tbl_os.posto = tbl_posto.posto where os = $os;";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) >0) {
			$sigla_pais = pg_result ($res,0,pais);
		}
	}

	$acrescimo = 0;

	if(strlen($sigla_pais)>0 and $sigla_pais <> "BR") {
		$sql = "select pecas,mao_de_obra  from tbl_os where os=$os";
		$res = pg_exec ($con,$sql);

		if (pg_numrows ($res) == 1) {
			$valor_liquido = pg_result ($res,0,pecas);
			$mao_de_obra   = pg_result ($res,0,mao_de_obra);
		}
		$sql = "select imposto_al  from tbl_posto_fabrica where posto=$login_posto and fabrica=$login_fabrica";
		$res = pg_exec ($con,$sql);

		if (pg_numrows ($res) == 1) {
			$imposto_al   = pg_result ($res,0,imposto_al);
			$imposto_al   = $imposto_al / 100;
			$acrescimo     = ($valor_liquido + $mao_de_obra) * $imposto_al;
		}
	}

	//Foi comentado HD chamado 17175 4/4/2008

	//HD 9469 - Alteração no cálculo da BOSCH do Brasil
	if($login_pais=="BR") {
		$sql = "select pecas,mao_de_obra  from tbl_os where os=$os";
		$res = pg_exec ($con,$sql);

		if (pg_numrows ($res) == 1) {
			$valor_liquido = pg_result ($res,0,pecas);
			//$mao_de_obra   = pg_result ($res,0,mao_de_obra);
		}
	}


	if($login_fabrica == 30){
		$sql = "select pecas,mao_de_obra  from tbl_os where os=$os";
		$res = pg_exec ($con,$sql);

		if (pg_numrows ($res) == 1) {
			$valor_liquido = pg_result ($res,0,pecas);
			$mao_de_obra   = pg_result ($res,0,mao_de_obra);
		}
	}

	/* HD 19054 */
	$valor_km = 0;
	if($login_fabrica == 50 or $login_fabrica == 30){
		$sql = "SELECT	tbl_os.mao_de_obra,
						tbl_os.qtde_km_calculada,
						tbl_os_extra.extrato
				FROM tbl_os
				LEFT JOIN tbl_os_extra USING(os)
				WHERE tbl_os.os = $os
				AND   tbl_os.fabrica = $login_fabrica";
		$res = pg_exec ($con,$sql);

		if (pg_numrows ($res) == 1) {
			$mao_de_obra   = pg_result ($res,0,mao_de_obra);
			$valor_km      = pg_result ($res,0,qtde_km_calculada);
			$extrato       = pg_result ($res,0,extrato);
		}
	}

	$total = $valor_liquido + $mao_de_obra + $acrescimo + $valor_km;

	$total          = number_format ($total,2,",",".")         ;
	$mao_de_obra    = number_format ($mao_de_obra ,2,",",".")  ;
	$acrescimo      = number_format ($acrescimo ,2,",",".")    ;
	$valor_desconto = number_format ($valor_desconto,2,",",".");
	$valor_liquido  = number_format ($valor_liquido ,2,",",".");
	$valor_km       = number_format ($valor_km ,2,",",".");

	echo "<tr style='font-size: 12px ; color:#000000 '>";
	/* HD 19054 */
	if ($login_fabrica==50 or $login_fabrica==30){
		/* HD 24461 - Francisco Ambrozio - ocultar campo Valor Deslocamento,
			caso este for igual a 0*/
		if ($valor_km<>0){
			echo "<td align='right'><font color='#333377'><b>$valor_km</b></td>";
		}
		if($login_fabrica == 30){
			echo "<td align='right'><font color='#333377'><b>$valor_liquido</b></td>" ;
		}
		echo "<td align='center' colspan='2'>$mao_de_obra</td>";
		echo "<td align='center' bgcolor='#E1EAF1'><font size='3' color='FF0000'><b>$total</b></td>";

	}else{
		echo "<td align='right'><font color='#333377'><b>$valor_liquido</b></td>" ;
		echo "<td align='center'>$mao_de_obra</td>";
		if($sistema_lingua=='ES')echo "<td align='center'>+ $acrescimo</td>";
		echo "<td align='center' bgcolor='#E1EAF1'><font size='3' color='FF0000'><b>$total</b></td>";
	}
	echo "</tr>";

	/* HD 19054 */
	if ($login_fabrica==50 and strlen($extrato)==0){
		echo "<tr style='font-size: 12px ; color:#000000 '>";
		echo "<td colspan='3'>";
		echo "<font color='#757575'>".traduz("valores.sujeito.a.alteracao.ate.fechamento.do.extrato",$con,$cook_idioma) ;
		echo "</td>";
		echo "</tr>";
	}
    echo "</table>";

}
?>

<?
	if ($login_fabrica==2 and strlen($data_finalizada)==0 and $login_posto==6359){
		$status_os = "";
		$sql_status = "SELECT status_os
						FROM tbl_os_status
						WHERE os = $os
						AND status_os IN (72,73,62,64,65,87,88)
						ORDER BY data DESC
						LIMIT 1";
		$res_status = pg_exec($con,$sql_status);
		if (pg_numrows($res_status) >0) {
			$status_os = pg_result ($res_status,0,status_os);
		}
		if ($status_os != "65"){
			echo "<br>";
			echo "<a href='".$PHP_SELF."?os=$os&inter=1'>".strtoupper(traduz("enviar.produto.para.centro.de.reparo",$con,$cook_idioma))."</a>";
			echo "<br>";
		}
	}
?>


<? if((($login_fabrica == 11 OR $login_fabrica == 10) AND $login_posto == 6359) OR $login_fabrica == 3 OR $login_fabrica == 45) {?>
	<br>
	<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>
	<TR>
		<TD><font size='2' color='#FFFFFF'><center><b><? if ($login_fabrica == 3) echo strtoupper(traduz("enviar.duvida.ao.suporte.tecnico",$con,$cook_idioma)); else echo strtoupper(traduz("interagir.na.os",$con,$cook_idioma)); ?></b></center></font></TD>
	</TR>
	<TR><TD class='conteudo'>

	<FORM NAME='frm_interacao' METHOD=POST ACTION="<? echo "$PHP_SELF?os=$os"; ?>">
	<TABLE width='600' align='center'>
	<TR>
		<TD>
		<TABLE align='center' border='0' cellspacing='0' cellpadding='5'>
		<? if($login_fabrica ==3 ){ // HD 17334 ?>
		<tr>
			<TD align='center'><?fecho ("duvidas",$con,$cook_idioma);?>:</td>
		</tr>
		<? } ?>

		<TR>
			<TD align='center'>
			<INPUT TYPE="text" NAME="interacao_msg" size='60' >&nbsp;
			<? if($login_fabrica <>3 ){ // HD 17334 ?>
			<INPUT TYPE="checkbox" NAME="interacao_exigir_resposta" value='t'>&nbsp;<font size='1'><?fecho ("enviar.p.o.fabricante",$con,$cook_idioma);?>.</font>
			<? } ?>
			</TD>
		</TR>
		<? if ($login_fabrica ==3) { // HD 17334 ?>
		<TR>
			<TD align='center' nowrap><?fecho ("pontos.verificados.pelo.tecnico",$con,$cook_idioma);?>:</td>
		</TR>
		<TR>
			<TD align='center'><INPUT TYPE="text" NAME="interacao_msg2" size='60'></TD>
		</TR>
		<? } ?>

		<TR align='center'>
			<TD><input type="hidden" name="interacao_os" value="">
				<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_interacao.interacao_os.value == '' ) { document.frm_interacao.interacao_os.value='gravar' ; document.frm_interacao.submit() } else { alert ('<?fecho("aguarde.submissao",$con,$cook_idioma);?>') }"
				<? if($login_fabrica == 3) { echo "alt='".traduz("enviar.duvida",$con,$cook_idioma)."'";} else { echo " 'ALT='".traduz("gravar.comentario",$con,$cook_idioma)."'";} ?> border='0' style="cursor:pointer;">
			</TD>
		</TR>
		</TABLE>
		</TD>
	</TR>
	</TABLE>
	</FORM>

	<?
	$sql = "SELECT os_interacao,
					to_char(data,'DD/MM/YYYY HH24:MI') as data,
					comentario,
					tbl_admin.nome_completo
				FROM tbl_os_interacao
				LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os_interacao.admin
				WHERE os = $os
				AND interno IS FALSE
				ORDER BY os_interacao DESC;";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res) > 0){

		for($i=0;$i<pg_numrows($res);$i++){
			$os_interacao     = pg_result($res,$i,os_interacao);
			$interacao_msg    = pg_result($res,$i,comentario);
			$interacao_data   = pg_result($res,$i,data);
			$interacao_nome   = pg_result($res,$i,nome_completo);
			if($i==0){
				echo "<br>";
				echo "<table width='100%' border='0' cellspacing='0' cellpadding='0' align='center'>";
				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#F3F5CF'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp;<b>".traduz("interacao.da.fabrica",$con,$cook_idioma)."</b></b></font></td>";
				echo "</tr>";
				echo "</table>";

				echo "<TABLE width='100%' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>";
				echo "<tr>";
				echo "<td class='titulo'><CENTER>".strtoupper(traduz("n",$con,$cook_idioma))."</CENTER></td>";
				echo "<td class='titulo'><CENTER>".strtoupper(traduz("data",$con,$cook_idioma))."</CENTER></td>";
				echo "<td class='titulo'><CENTER>".traduz("mensagem",$con,$cook_idioma)."</CENTER></td>";
				echo "<td class='titulo'><CENTER>".traduz("fabrica",$con,$cook_idioma)."</CENTER></td>";
				echo "</tr>";
			}
			if(strlen($interacao_nome) > 0){
				$cor = "style='font-family: Arial; FONT-SIZE: 8pt; font-weight: bold; text-align: left; background: #F3F5CF;'";
			}else{
				$cor = "class='conteudo'";
			}
			echo "<tr>";
			echo "<td width='25' $cor>"; echo pg_numrows($res) - $i; echo "</td>";
			echo "<td width='90' $cor nowrap>$interacao_data</td>";
			echo "<td $cor>$interacao_msg</td>";
			echo "<td $cor nowrap>$interacao_nome</td>";
			echo "</tr>";
		}
		echo "</TABLE><br>";
	}
	echo "</TD></TR></TABLE>";
}
?>

<?
if($login_fabrica == 45){ // HD 51709
	$arquivo = "nota_os/$os.jpg";
	if ($dh = opendir("nota_os/")) {
		if(file_exists($arquivo)){
		list($width,$height) = getimagesize($arquivo);
		echo "<div>";
		echo "<br>";
		echo "<h1>Nota da OS</h><br>";
		echo "Clique na foto para visualizar melhor<br>";
		echo "<IMG SRC='nota_os/".$os."_thumb.jpg' onclick=\"javascript:window.open('nota_os/$os.jpg','Nota','status=yes,scrollbars=no,width=$width,height=$height');\">";
		echo "</div>";
		}
	}
}
?>
<!-- hd 21896 - Francisco Ambrozio - inclusão do laudo técnico -->
<?
if ($login_fabrica == 1 or $login_fabrica == 19){
	$sql = "SELECT tbl_laudo_tecnico_os.*
			FROM tbl_laudo_tecnico_os
			WHERE os = $os
			ORDER BY ordem;";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res) > 0){
?>
		<BR>
		<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'  class='Tabela'>
		<TR>
		<TD colspan="9" class='inicio'>&nbsp;<?echo strtoupper(traduz("laudo.tecnico",$con,$cook_idioma));?></TD>
<?
		echo "<tr>";
		if ($login_fabrica==19) {
			echo "<td class='titulo' style='width: 30%'><CENTER>".strtoupper(traduz("questao",$con,$cook_idioma))."</CENTER></td>";
		} else {
			echo "<td class='titulo' style='width: 30%'><CENTER>".strtoupper(traduz("titulo",$con,$cook_idioma))."</CENTER></td>";
		}
		echo "</CENTER></td>";
		echo "<td class='titulo' style='width: 10%'><CENTER>".strtoupper(traduz("afirmativa",$con,$cook_idioma))."</CENTER></td>";
		echo "<td class='titulo' style='width: 60%'><CENTER>".strtoupper(traduz("observacao",$con,$cook_idioma))."</CENTER></td>";
		echo "</tr>";

		for($i=0;$i<pg_numrows($res);$i++){
			$laudo		 = pg_result($res,$i,laudo_tecnico_os);
			$titulo      = pg_result($res,$i,titulo);
			$afirmativa  = pg_result($res,$i,afirmativa);
			$observacao  = pg_result($res,$i,observacao);

			echo "<tr>";
			echo "<td class='conteudo' align='left' style='width: 30%'>&nbsp;$titulo</td>";
			if(strlen($afirmativa) > 0){
				echo "<td class='conteudo' style='width: 10%'><CENTER>"; if($afirmativa == 't'){ echo traduz("sim",$con,$cook_idioma)."</CENTER></td>";} else { echo traduz("nao",$con,$cook_idioma)."</CENTER></td>";}
			}else{
				echo "<td class='conteudo' style='width: 10%'>&nbsp;</td>";
			}
			if(strlen($observacao) > 0){
				echo "<td class='conteudo' style='width: 60%'><CENTER>$observacao</CENTER></td>";
			}else{
				echo "<td class='conteudo' style='width: 60%'>&nbsp;</td>";
			}
			echo "</tr>";
		}
?>
</TR>
</TABLE>
<?
	}
}
?>
<!-- Finaliza inclusão do laudo técnico -->

<BR><BR>
<!-- =========== FINALIZA TELA NOVA============== -->

<?
	$origem = $_GET['origem'];
?>

<table cellpadding='10' cellspacing='0' border='0' align='center'>
<tr>
<? if($sistema_lingua == "ES"){ ?>
	<td><a href="os_cadastro.php"><img src="imagens/btn_lanzarnovaos.gif"></a></td>
<? }elseif ($origem == 'troca'){ ?>
	<td><a href="os_cadastro_troca.php"><img src="imagens/btn_lancanovaos.gif"></a></td>
<? }else{?>
	<td><a href="os_cadastro.php"><img src="imagens/btn_lancanovaos.gif"></a></td>
<? } if($login_fabrica == 20){
		echo "<TD><a href='os_comprovante_servico_print.php?os=$os'><img src='imagens/";
		if($sistema_lingua=="ES")echo "es_";
		echo "btn_comprovante.gif'></a></TD>";

}?>
<td><a href="os_print.php?os=<? echo $os ?>" target="_blank"><img src="imagens/btn_imprimir.gif"></a></td>
</tr>
</table>

<!--
<div id='container'>
    <div id="contentleft2" style="width: 150px;">
        &nbsp;
    </div>

    <div id="contentleft2" style="width: 150px;">
        <a href="os_cadastro.php"><img
    </div>
    <div id="contentleft2" style="width: 150px;">
        <a href="os_print.php?os=<? echo $os ?>" target="_blank"><img
    </div>
</div>

<div id='container'>
    &nbsp;
</div>
-->



<? include "rodape.php"; ?>

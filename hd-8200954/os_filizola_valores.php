<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';
include 'token_cookie.php';

$token_cookie = $_COOKIE['sess'];
$cookie_login = get_cookie_login($token_cookie);

if ($login_fabrica != 7) {
	header("Location: menu_os.php");
	exit;
}

if ($S3_sdk_OK) {
	include_once S3CLASS;
	$s3_ge = new anexaS3('od', (int) $login_fabrica); //Anexo garantia estendida para Elgin
	$S3_online = is_object($s3_ge);
}

$qtde_item          = 30;
$qtde_item_visiveis = 5;

if ($_GET["ajax"] == "true" AND $_GET["buscaInformacoes"] == "true") {
	$referencia = trim($_GET["produto_referencia"]);
	$serie      = trim($_GET["serie"]);

	if (strlen($serie) > 0 AND strlen($referencia) > 0) {
		$sql = "SELECT
						tbl_os.capacidade      AS capacidade,
						tbl_os.divisao         AS divisao,
						tbl_os.versao          AS versao,
						tbl_produto.capacidade AS produto_capacidade,
						tbl_produto.divisao    AS produto_divisao
				FROM tbl_os
				JOIN tbl_produto USING(produto)
				WHERE fabrica  = $login_fabrica
				AND   posto    = $login_posto
				AND   tbl_produto.referencia = '$referencia'
				AND   serie    = '$serie' ;";
		$res = @pg_exec($con,$sql);
		if (pg_numrows($res)>0) {
			$capacidade         = trim(pg_result($res,0,capacidade));
			$divisao            = trim(pg_result($res,0,divisao));
			$versao             = trim(pg_result($res,0,versao));
			$produto_capacidade = trim(pg_result($res,0,produto_capacidade));
			$produto_divisao    = trim(pg_result($res,0,produto_divisao));
			echo "ok|$capacidade|$divisao|$versao";
			exit;
		}
	}
	echo "nao|nao";
	exit;
}

$msg_erro    = '';
$qtde_visita = 5;

if (strlen($_GET['os']) > 0)  $os = $_GET['os'];
if (strlen($_POST['os']) > 0) $os = $_POST['os'];

if (strlen($_GET['sua_os']) > 0)  $sua_os = $_GET['sua_os'];
if (strlen($_POST['sua_os']) > 0) $sua_os = $_POST['sua_os'];

if (strlen($_GET['reabrir']) > 0)  $reabrir = $_GET['reabrir'];
if (strlen($_POST['reabrir']) > 0) $reabrir = $_POST['reabrir'];

if (strlen($os) == 0) {
	header("Location: menu_os.php");
	exit;
}

if (strlen($reabrir) > 0) {

	$sql = "SELECT tbl_os.os
			FROM   tbl_os
			WHERE  tbl_os.os = $os
			AND    tbl_os.pedido_cliente IS NULL ";

	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {

		$sql = "UPDATE tbl_os SET data_fechamento = null, finalizada = null
				WHERE  tbl_os.os      = $os
				AND    tbl_os.fabrica = $login_fabrica
				AND    tbl_os.posto   = $login_posto;";

		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);

	} else {

		$msg_erro = "OS já gerou pedido. Não pode ser alterada";

	}

}

#HD 25608
$sql = "SELECT os
		  FROM tbl_os
		 WHERE fabrica = $login_fabrica
		   AND posto   = $login_posto
		   AND os      = $os
		   AND produto IS NULL";

$res = pg_exec($con,$sql);

if (pg_numrows($res) > 0) {
	header("Location: os_cadastro.php?os=$os");
	exit;
}

if (strlen($_POST['btn_acao']) > 0) $btn_acao = strtolower($_POST['btn_acao']);

if ($btn_acao == "gravar") {

	$tipo_atendimento         = trim($_POST['tipo_atendimento']);
	$nota_fiscal              = trim($_POST['nota_fiscal']);
	$data_nf                  = trim($_POST['data_nf']);
	$xdata_nf                 = fnc_formata_data_pg($data_nf);
	$capacidade               = trim($_POST['capacidade']);
	$capacidade               = str_replace(",",".",$capacidade);
	$versao                   = trim($_POST['versao']);
	$divisao                  = trim($_POST['divisao']);
	$divisao                  = str_replace(",",".",$divisao);
	$defeito_constatado       = trim($_POST ['defeito_constatado']);
	$solucao_os               = trim($_POST ['solucao_os']);
	$condicao                 = trim($_POST ['condicao']);
	$desconto_peca            = trim($_POST ['desconto_peca']);
	$mao_de_obra              = trim($_POST['mao_de_obra']);
	$natureza_servico         = trim($_POST['natureza_servico']);
	$laudo_tecnico            = trim($_POST['laudo_tecnico']);
	$laudo_tecnico            = str_replace(",",".",$laudo_tecnico);
	$qtde_horas               = trim($_POST['qtde_horas']);
	$anormalidades            = trim($_POST['anormalidades']);
	$causas                   = trim($_POST['causas']);
	$medidas_corretivas       = trim($_POST['medidas_corretivas']);
	$recomendacoes            = trim($_POST['recomendacoes']);
	$obs_extra                = trim($_POST['obs_extra']);
	$hd_chamado                = trim($_POST['hd_chamado']);
	//$faturamento_cliente_revenda = trim($_POST['faturamento_cliente_revenda']);
	$aparencia_produto        = trim($_POST['aparencia_produto']);
	$acessorios               = trim($_POST['acessorios']);
	$selo                     = trim($_POST['selo']);
	$lacre_encontrado         = trim($_POST['lacre_encontrado']);
	$lacre                    = trim($_POST['lacre']);
	$tecnico                  = trim($_POST['tecnico']);
	$representante            = trim($_POST['representante']);//HD 254633
	$classificacao_os         = trim($_POST['classificacao_os']);
	$assinou_os               = trim($_POST['assinou_os']);
	$serie                    = trim($_POST['serie']);

	$taxa_visita				= str_replace (",",".",trim ($_POST['taxa_visita']));
	$visita_por_km				= trim($_POST['visita_por_km']);
	$valor_por_km				= str_replace (",",".",trim ($_POST['valor_por_km']));
	$veiculo					= trim ($_POST['veiculo']);
	$deslocamento_km			= str_replace (",",".",trim ($_POST['deslocamento_km']));
	$valor_por_km_carro         = str_replace(',','.',trim($_POST['valor_por_km_carro']));
	$valor_por_km_caminhao      = str_replace(',','.',trim($_POST['valor_por_km_caminhao']));
	
	$hora_tecnica				= str_replace (",",".",trim ($_POST['hora_tecnica']));

	$regulagem_peso_padrao		= str_replace (".","", trim ($_POST['regulagem_peso_padrao']));
	$regulagem_peso_padrao		= str_replace (",",".",trim ($regulagem_peso_padrao));

	$certificado_conformidade	= str_replace (".","", trim ($_POST['certificado_conformidade']));
	$certificado_conformidade	= str_replace (",",".",trim ($certificado_conformidade));

	$valor_diaria				= str_replace (",",".",trim ($_POST['valor_diaria']));

	$cobrar_deslocamento		= trim ($_POST['cobrar_deslocamento']);
	$cobrar_hora_diaria			= trim ($_POST['cobrar_hora_diaria']);

	$desconto_deslocamento		= str_replace (",",".",trim ($_POST['desconto_deslocamento']));
	$desconto_hora_tecnica		= str_replace (",",".",trim ($_POST['desconto_hora_tecnica']));
	$desconto_diaria			= str_replace (",",".",trim ($_POST['desconto_diaria']));
	$desconto_regulagem			= str_replace (",",".",trim ($_POST['desconto_regulagem']));
	$desconto_certificado		= str_replace (",",".",trim ($_POST['desconto_certificado']));

	$cobrar_regulagem			= trim ($_POST['cobrar_regulagem']);
	$cobrar_certificado			= trim ($_POST['cobrar_certificado']);


	if (strlen($cobrar_regulagem)==0) {
		$cobrar_regulagem = 'f';
	}

	if (strlen($deslocamento_km) == 0) {
		$xdeslocamento_km = '0';
	} else {
		$xdeslocamento_km = $deslocamento_km;
	}

	if (strlen($nota_fiscal)>0){
		$xnota_fiscal = "'".$nota_fiscal."'";
	} else {
		$xnota_fiscal = " null ";
	}

	if (strlen($mao_de_obra) > 0)
		$xmao_de_obra = "'".$mao_de_obra."'";
	else
		$xmao_de_obra = '0';

	if (strlen($mao_de_obra_por_hora) > 0)
		$xmao_de_obra_por_hora = "'".$mao_de_obra_por_hora."'";
	else
		$xmao_de_obra_por_hora = "'f'";

	if (strlen($capacidade) == 0) {
		$xcapacidade = "null";
	} else {
		if (!is_numeric($capacidade)) {
			$msg_erro .= "Capacidade é somente número.";
		} else {
			$xcapacidade = $capacidade;
		}
	}

	if (strlen($versao) == 0) {
		$xversao = "null";
	} else {
		$xversao = "'".$versao."'";
	}

	if (strlen($divisao) == 0) {
		$xdivisao = "null";
	} else {
		if (!is_numeric($divisao)) {
			#$msg_erro .= "Divisão é somente número.";
			$xdivisao = "'".$divisao."'";
		} else {
			$xdivisao = "'".$divisao."'";
		}
	}

	if (strlen($defeito_constatado) == 0) {
		$msg_erro .= "Por favor preencher o campo defeito constatado.<BR>";
		$xdefeito_constatado = "null";
	} else {
		$xdefeito_constatado = $defeito_constatado;
	}

	if (strlen($solucao_os) == 0) {
		$msg_erro .= "Por favor preencher o campo solução.<BR>";
		$xsolucao_os = 'null';
	} else {
		$xsolucao_os = $solucao_os;
	}

	if (strlen($desconto_peca) == 0) {
		$xdesconto_peca = '0';
	} else {
		$xdesconto_peca = $desconto_peca;
	}

	if (strlen($desconto_peca)>0 AND $desconto_peca>100){
		$xdesconto_peca = 100;
	}

	if (strlen($condicao) == 0) {
		// HD 51454
		if($login_tipo_posto == 214 OR $login_tipo_posto == 215 OR $login_tipo_posto == 7 OR $login_tipo_posto == 224) {
			$msg_erro = "Por favor, selecione a condição de pagamento";
		} else {
			$xcondicao = 'null';
		}
	} else {
		$xcondicao = $condicao;
	}

	if (strlen($condicao) == 0) {
		$xtabela = 'null';
	} else {
		$sql = "SELECT tabela
				FROM tbl_condicao
				WHERE fabrica = $login_fabrica
				AND condicao = $condicao; ";
		$res = pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (pg_numrows($res) > 0) {
			$xtabela = pg_result($res,0,tabela);
		}
		if (strlen($xtabela) == 0) {
			$xtabela = "null";
		}
	}

	if ($login_tipo_posto == 214 or $login_tipo_posto == 215){
		if ($desconto_deslocamento>7){
			$msg_erro .= "O desconto máximo permitido para deslocamento é 7%.<br>";
		}
		if ($desconto_hora_tecnica>7){
			$msg_erro .= "O desconto máximo permitido para hora técnica é 7%.<br>";
		}
		if ($desconto_diaria>7){
			$msg_erro .= "O desconto máximo permitido para diára é 7%.<br>";
		}
		if ($desconto_regulagem>7){
			$msg_erro .= "O desconto máximo permitido para regulagem é 7%.<br>";
		}
		if ($desconto_certificado>7){
			$msg_erro .= "O desconto máximo permitido para o certificado é 7%.<br>";
		}
	}

	if (strlen($veiculo) == 0) {
		$xveiculo = "NULL";
	} else {
		$xveiculo = "'$veiculo'";
		if ($veiculo == 'carro'){
			$valor_por_km =  str_replace (",",".",trim ($_POST['valor_por_km_carro']));
		}
		if ($veiculo == 'caminhao'){
			$valor_por_km =  str_replace (",",".",trim ($_POST['valor_por_km_caminhao']));
		}
	}

	if (strlen($valor_por_km)>0){
		$xvalor_por_km = $valor_por_km;
		$xvisita_por_km = "'t'";
	} else {
		$xvalor_por_km = "0";
		$xvisita_por_km = "'f'";
	}

	if (strlen($taxa_visita)>0){
		$xtaxa_visita = $taxa_visita;
	} else {
		$xtaxa_visita = '0';
	}

	/* HD 29838 */
	if ($tipo_atendimento == 63){
		$cobrar_deslocamento = 'isento';
	}

	if ($cobrar_deslocamento == 'isento' OR strlen($cobrar_deslocamento) == 0) {
		$xvisita_por_km = "'f'";
		$xvalor_por_km = "0";
		$xtaxa_visita = '0';
		$xveiculo = "NULL";
	}elseif ($cobrar_deslocamento == 'valor_por_km'){
		$xvisita_por_km = "'t'";
		$xtaxa_visita = '0';
	}elseif ($cobrar_deslocamento == 'taxa_visita'){
		$xvisita_por_km = "'f'";
		$xvalor_por_km = "0";
	}

	if (strlen($valor_diaria) > 0){
		$xvalor_diaria = $valor_diaria;
	} else {
		$xvalor_diaria = '0';
	}

	if (strlen($hora_tecnica) > 0){
		$xhora_tecnica = $hora_tecnica;
	} else {
		$xhora_tecnica = '0';
	}

	if ($cobrar_hora_diaria == 'isento' OR strlen($cobrar_hora_diaria) == 0) {
		$xhora_tecnica = '0';
		$xvalor_diaria = '0';
	}elseif ($cobrar_hora_diaria == 'diaria'){
		$xhora_tecnica = '0';
	}elseif ($cobrar_hora_diaria == 'hora'){
		$xvalor_diaria = '0';
	}

	if (strlen($regulagem_peso_padrao) > 0 ){
		$xregulagem_peso_padrao = $regulagem_peso_padrao;
	} else {
		$xregulagem_peso_padrao = '0';
	}

	if (strlen($certificado_conformidade) > 0 and $cobrar_certificado == 't'){
		$xcertificado_conformidade = $certificado_conformidade;
	} else {
		$xcertificado_conformidade = "0";
	}

	/* Descontos */
	if (strlen($desconto_deslocamento) > 0){
		$desconto_deslocamento = $desconto_deslocamento;
	} else {
		$desconto_deslocamento = '0';
	}

	if (strlen($desconto_hora_tecnica) > 0){
		$desconto_hora_tecnica = $desconto_hora_tecnica;
	} else {
		$desconto_hora_tecnica = '0';
	}

	if (strlen($desconto_diaria) > 0){
		$desconto_diaria = $desconto_diaria;
	} else {
		$desconto_diaria = '0';
	}

	if (strlen($desconto_regulagem) > 0){
		$desconto_regulagem = $desconto_regulagem;
	} else {
		$desconto_regulagem = '0';
	}

	if (strlen($desconto_certificado) > 0){
		$desconto_certificado = $desconto_certificado;
	} else {
		$desconto_certificado = '0';
	}

	if (strlen($laudo_tecnico) > 0)
		$xlaudo_tecnico = "'".$laudo_tecnico."'";
	else
		$xlaudo_tecnico = 'null';

	if (strlen($natureza_servico) > 0)
		$xnatureza_servico = "'".$natureza_servico."'";
	else
		$xnatureza_servico = 'null';

	if (strlen($qtde_horas) > 0)
		$xqtde_horas = "'".$qtde_horas."'";
	else
		$xqtde_horas = 'null';

/*
	if (strlen($faturamento_cliente_revenda) > 0)
		$xfaturamento_cliente_revenda = "'".$faturamento_cliente_revenda."'";
	else
		$xfaturamento_cliente_revenda = 'null';
*/
	if (strlen($anormalidades) > 0)
		$xanormalidades = "'".$anormalidades."'";
	else
		$xanormalidades = 'null';

	if (strlen($causas) > 0)
		$xcausas = "'".$causas."'";
	else
		$xcausas = 'null';

	if (strlen($medidas_corretivas) > 0)
		$xmedidas_corretivas = "'".$medidas_corretivas."'";
	else
		$xmedidas_corretivas = 'null';

	if (strlen($recomendacoes) > 0)
		$xrecomendacoes = "'".$recomendacoes."'";
	else
		$xrecomendacoes = 'null';

	if (strlen($obs_extra) > 0)
		$xobs_extra = "'".$obs_extra."'";
	else
		$xobs_extra = 'null';

	if (strlen($aparencia_produto) > 0)
		$xaparencia_produto = "'".$aparencia_produto."'";
	else
		$xaparencia_produto = 'null';

	if (strlen($acessorios) > 0)
		$xacessorios = "'".$acessorios."'";
	else
		$xacessorios = 'null';

	if (strlen($selo) > 0)
		$xselo = "'".$selo."'";
	else
		$xselo = 'null';

	if (strlen($lacre_encontrado) > 0)
		$xlacre_encontrado = "'".$lacre_encontrado."'";
	else
		$xlacre_encontrado = 'null';

	if (strlen($lacre) > 0)
		$xlacre = "'".$lacre."'";
	else
		$xlacre = 'null';

	if (strlen($tecnico) > 0)
		$xtecnico = "'".$tecnico."'";
	else
		$xtecnico = 'null';

	if (strlen($representante) > 0)//HD 254633
		$xrepresentante = $representante;
	else
		$xrepresentante = 'null';

	if (strlen($classificacao_os) > 0){
		$xclassificacao_os = $classificacao_os;
	} else {
		$msg_erro = "Escolha a classificação da OS. ";
	}

	if (($login_tipo_posto == 7 OR $login_tipo_posto == 224) AND $classificacao_os == 6 AND ($cobrar_hora_diaria == 'isento' OR strlen($cobrar_hora_diaria) == 0 )) {
		$msg_erro = "Serviço cobrado deve ser preenchido com a Mão de Obra! ";
	}

	$sql = "SELECT tbl_os.os
			FROM   tbl_os
			WHERE  tbl_os.os = $os
			AND    tbl_os.pedido_cliente IS NULL ";
	$res = pg_exec($con,$sql);
	if (pg_numrows($res) > 0) {
		$sql = "UPDATE tbl_os SET data_fechamento = null, finalizada = null
				WHERE  tbl_os.os      = $os
				AND    tbl_os.fabrica = $login_fabrica
				AND    tbl_os.posto   = $login_posto;";
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
	} else {
		$msg_erro = "OS já gerou pedido. Não pode ser alterada";
	}

	/* Se for CANCELADA - HD 29838 */
	if ($xclassificacao_os == 5) {
		$sql = "SELECT tbl_os.os
				FROM   tbl_os
				WHERE  tbl_os.os      = $os
				AND    tbl_os.fabrica = $login_fabrica
				AND    tbl_os.pedido_cliente IS NULL ";
		$res = pg_exec($con,$sql);
		if (pg_numrows($res) > 0) {
			if ($xobs_extra <> 'null'){
				$res = pg_exec($con,"BEGIN TRANSACTION");

				$sql = "UPDATE tbl_os_extra SET
							classificacao_os            = $xclassificacao_os,
							obs                         = $xobs_extra
						WHERE os = $os ";
				$res = @pg_exec($con,$sql);
				$msg_erro = @pg_errormessage($con);

				$sql = "SELECT fn_valida_os($os, $login_fabrica)";
				$res = @pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);

				if (strlen($msg_erro) == 0){
					$res = pg_exec($con,"COMMIT TRANSACTION");
					header ("Location: os_press.php?os=$os");
					exit;
				} else {
					$res = pg_exec($con,"ROLLBACK TRANSACTION");
				}
			} else {
				$msg_erro = "Para OS cancelada é obrigatório inserir a justificativa
				no campo Observações";
			}
		} else {
			$msg_erro = "OS já gerou pedido. Não pode ser alterada";
		}
	}

	if (strlen($serie) > 0){
		$xserie = "'".$serie."'";
	} else {
		$msg_erro = " Digite o número de série. ";
	}

	/* SE OS JÁ GEROU PEDIDO, NAO DEIXA ALTERAR */
	$sql = "SELECT tbl_os.os
			FROM   tbl_os
			WHERE  tbl_os.os      = $os
			AND    tbl_os.fabrica = $login_fabrica
			AND    tbl_os.pedido_cliente IS NOT NULL ";
	$res = pg_exec($con,$sql);
	if (pg_numrows($res) > 0) {
		$msg_erro = "OS já gerou pedido. Não pode ser alterada.";
	}

	if (strlen($msg_erro) == 0){
		$res = pg_exec($con,"BEGIN TRANSACTION");

		$produto    = trim($_POST['produto']);
		$referencia = trim($_POST['produto_referencia']);

		$sql = "SELECT	tbl_os.produto,
						tbl_os.serie,
						tbl_os.consumidor_revenda,
						tbl_os.os_numero,
						tbl_os_revenda.os_manutencao
				FROM tbl_os
				LEFT JOIN   tbl_os_revenda  ON tbl_os_revenda.os_revenda = tbl_os.os_numero
						AND tbl_os_revenda.posto = tbl_os.posto
				WHERE tbl_os.fabrica = $login_fabrica
				AND   tbl_os.os      = $os
				";
		$res = pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (pg_numrows($res) > 0) {
			$produto            = pg_result($res,0,produto);
			$serie              = pg_result($res,0,serie);
			$consumidor_revenda = pg_result($res,0,consumidor_revenda);
			$os_numero          = pg_result($res,0,os_numero);
			$os_manutencao      = pg_result($res,0,os_manutencao);
		} else {
			$msg_erro .= "Produto não encontrado!";
		}

		/* Nao precisa ataulizar a OS com o produto */
		if (strlen($referencia)>0 and 1==2){
			$sql = "SELECT produto
					FROM tbl_produto
					JOIN tbl_linha USING(linha)
					WHERE tbl_produto.referencia_pesquisa = '$referencia'
					AND   tbl_linha.fabrica               = $login_fabrica ";
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);

			if (pg_numrows($res) > 0) {
				$produto = pg_result($res,0,produto);
			}

			$sql = "UPDATE tbl_os SET
						produto            = $produto
					WHERE os = $os
					AND   fabrica = $login_fabrica  ";
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
		}


		$sql = "UPDATE tbl_os SET
					defeito_constatado = $xdefeito_constatado,
					solucao_os         = $xsolucao_os,
					versao             = $xversao,
					capacidade         = $xcapacidade,
					divisao            = $xdivisao,
					aparencia_produto  = $xaparencia_produto,
					acessorios         = $xacessorios,
					serie              = $xserie,
					condicao           = $xcondicao,
					tabela             = $xtabela,
					nota_fiscal        = $xnota_fiscal,
					data_nf            = $xdata_nf,
					consumidor_nome_assinatura = '$assinou_os'
				WHERE os = $os
				AND   fabrica = $login_fabrica ";
		$res = pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);

		if ($os_manutencao == 't'){
			$sql = "UPDATE tbl_os_revenda SET
						condicao = $xcondicao
					WHERE os_revenda = $os_numero ";
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);

			$sql = "UPDATE tbl_os SET
						condicao = $xcondicao,
						tabela   = $xtabela
					WHERE os_numero  = $os_numero
					AND   posto      = $login_posto
					AND   fabrica    = $login_fabrica";
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		$os_visita_x = $os;
		$os_revenda  = ' NULL ';

		if ($os_manutencao == 't'){
			$os_visita_x = ' NULL ';
			$os_revenda  = $os_numero;
		}

		if (strlen($msg_erro) == 0){

			for($i=0; $i < $qtde_visita; $i++){
				$novo                 = trim($_POST['novo_'. $i]);
				$os_visita            = trim($_POST['os_visita_'. $i]);
				$data                 = trim($_POST['data_'. $i]);
				$hora_saida_sede      = trim($_POST['hora_saida_sede_'. $i]);
				$km_saida_sede        = trim($_POST['km_saida_sede_'. $i]);
				$hora_chegada_cliente = trim($_POST['hora_chegada_cliente_'. $i]);
				$km_chegada_cliente   = trim($_POST['km_chegada_cliente_'. $i]);
				$hora_saida_almoco    = trim($_POST['hora_saida_almoco_'. $i]);
				$km_saida_almoco      = trim($_POST['km_saida_almoco_'. $i]);
				$hora_chegada_almoco  = trim($_POST['hora_chegada_almoco_'. $i]);
				$km_chegada_almoco    = trim($_POST['km_chegada_almoco_'. $i]);
				$hora_saida_cliente   = trim($_POST['hora_saida_cliente_'. $i]);
				$km_saida_cliente     = trim($_POST['km_saida_cliente_'. $i]);
				$hora_chegada_sede    = trim($_POST['hora_chegada_sede_'. $i]);
				$km_chegada_sede      = trim($_POST['km_chegada_sede_'. $i]);

				if (strlen($msg_erro) == 0){
					if (strlen($data) == 0) {
						if (strlen($os_visita) > 0 AND $novo == 'f') {
							$sql = "DELETE FROM tbl_os_visita
									WHERE  tbl_os_visita.os_visita = $os_visita ";
							if ($os_manutencao == 't'){
								$sql .= " AND os_revenda = $os_revenda ";
							} else {
								$sql .= " AND os = $os ";
							}
							$res = pg_exec($con,$sql);
							$msg_erro = pg_errormessage($con);
						}
					}
				}

				if (strlen($msg_erro) == 0) {

					if (strlen($data) > 0){
						$fnc   = @pg_exec($con,"SELECT fnc_formata_data('$data')");
						$xdata = "'". @pg_result($fnc,0,0) ."'";
						$xxdata = @pg_result($fnc,0,0);

						if ($tipo_atendimento=='63'){
							$hora_saida_sede    = 'null';
							$km_saida_sede      = 'null';
							$km_chegada_cliente = 'null';
							$km_saida_cliente   = "null";
							$hora_chegada_sede  = 'null';
							$km_chegada_sede    = 'null';
						} else {
							$hora_saida_sede    = 'null';
							$km_saida_sede      = 'null';
							$km_chegada_cliente = 'null';
							$km_saida_cliente   = "null";
							$hora_chegada_sede  = 'null';
							$km_chegada_sede    = 'null';
						}

						if (strlen($hora_saida_sede) <> 5 AND $hora_saida_sede <> 'null'){
							$msg_erro = "Digite a hora de saída da sede no formato hh:mm";
						}
						if (strlen($hora_chegada_cliente) <> 5){
							$msg_erro = "Digite a hora de chegada ao cliente no formato hh:mm";
						}
						if (strlen($hora_saida_cliente) <> 5){
							$msg_erro = "Digite a hora de saída do cliente no formato hh:mm";
						}
						if (strlen($hora_chegada_sede) <> 5 AND $hora_chegada_sede <> 'null') {
							$msg_erro = "Digite a hora de chegada na sede no formato hh:mm";
						}

						if (strlen($msg_erro) == 0) {

							if (strlen($hora_saida_sede) > 0){
								if ($hora_saida_sede=='null'){
									$xhora_saida_sede = $hora_saida_sede;
								} else {
									$xhora_saida_sede = "'$xxdata ".$hora_saida_sede."'";
								}
							} else {
								$msg_erro = "Hora Saída Sede deve ser preenchida.";
							}

							if (strlen($km_saida_sede) > 0){
								$xkm_saida_sede = $km_saida_sede;
							} else {
								$xkm_saida_sede = 'null';
							}

							if (strlen($hora_chegada_cliente) > 0){
								$xhora_chegada_cliente = "'$xxdata ".$hora_chegada_cliente."'";
							} else {
								$xhora_chegada_cliente = 'null';
							}

							if (strlen($km_chegada_cliente) > 0){
								$xkm_chegada_cliente = $km_chegada_cliente;
							} else {
								$xkm_chegada_cliente = 'null';
							}

							if (strlen($hora_saida_almoco) > 0){
								if (strlen($hora_saida_almoco) < 5)
									$msg_erro = "Digite a hora de saída da sede no formato hh:mm";
								else
									$xhora_saida_almoco = "'$xxdata ".$hora_saida_almoco."'";
							} else {
								$xhora_saida_almoco = 'null';
							}

							if (strlen($km_saida_almoco) > 0){
								$xkm_saida_almoco = $km_saida_almoco;
							} else {
								$xkm_saida_almoco = 'null';
							}

							if (strlen($hora_chegada_almoco) > 0){
								if (strlen($hora_chegada_almoco) < 5)
									$msg_erro = "Digite a hora de chegada do almoço no formato hh:mm";
								else
									$xhora_chegada_almoco = "'$xxdata ".$hora_chegada_almoco."'";
							} else {
								$xhora_chegada_almoco = 'null';
							}

							if (strlen($km_chegada_almoco) > 0){
								$xkm_chegada_almoco = $km_chegada_almoco;
							} else {
								$xkm_chegada_almoco = 'null';
							}

							if (strlen($hora_saida_cliente) > 0){
								$xhora_saida_cliente = "'$xxdata ".$hora_saida_cliente."'";
							} else {
								$msg_erro = "Digite a hora de saída do cliente.";
							}

							if (strlen($km_saida_cliente) > 0){
								$xkm_saida_cliente = $km_saida_cliente;
							} else {
								$msg_erro = "Digite o KM de saída do cliente.";
							}

							if (strlen($hora_chegada_sede) > 0){
								if ($hora_chegada_sede=='null'){
									$xhora_chegada_sede = $hora_chegada_sede;
								} else {
									$xhora_chegada_sede = "'$xxdata ".$hora_chegada_sede."'";
								}
							} else {
								$msg_erro = "Digite a hora de chegada na sede.";
							}

							if (strlen($km_chegada_sede) > 0){
								$xkm_chegada_sede = $km_chegada_sede;
							} else {
								$msg_erro = "Digite o KM de chegada a sede.";
							}
						}

						if (strlen($xdata) > 0 AND strlen($msg_erro) ==0) {

							################################################################################################
							if (strlen($hora_saida_sede) > 0 AND strlen($hora_chegada_cliente) > 0){
								$horas_1[$i] = calcula_hora($hora_saida_sede, $hora_chegada_cliente);

								if (strlen($km_saida_sede) > 0 AND strlen($km_chegada_cliente) > 0)
									$km_1[$i] = $km_chegada_cliente - $km_saida_sede;
								else
									$km_1[$i] = 0;
							}

							if (strlen($hora_chegada_cliente) > 0 AND strlen($hora_saida_almoco) > 0){
								$horas_2[$i] = calcula_hora($hora_chegada_cliente, $hora_saida_almoco);

								if (strlen($km_chegada_cliente) > 0 AND strlen($km_saida_almoco) > 0)
									$km_2[$i] = $km_saida_almoco - $km_chegada_cliente;
								else
									$km_2[$i] = 0;
							} else {
								$km_2[$i] = 0;
							}

							if (strlen($hora_saida_almoco) > 0 AND strlen($hora_chegada_almoco) > 0){
								$horas_3[$i] = calcula_hora($hora_saida_almoco, $hora_chegada_almoco);

								if (strlen($km_saida_almoco) > 0 AND strlen($km_chegada_almoco) > 0)
									$km_3[$i] = $km_chegada_almoco - $km_saida_almoco;
								else
									$km_3[$i] = 0;
							} else {
								$km_3[$i] = 0;
							}

							if (strlen($hora_chegada_almoco) > 0 AND strlen($hora_saida_cliente) > 0){
								$horas_4[$i] = calcula_hora($hora_chegada_almoco, $hora_saida_cliente);

								if (strlen($km_chegada_almoco) > 0 AND strlen($km_saida_cliente) > 0)
									$km_4[$i] = $km_saida_cliente - $km_chegada_almoco;
								else
									$km_4[$i] = 0;
							} else {
								$horas_4[$i] = calcula_hora($hora_chegada_cliente, $hora_saida_cliente);
								$km_4[$i] = 0;
							}

							if (strlen($hora_saida_cliente) > 0 AND strlen($hora_chegada_sede) > 0){
								$horas_5[$i] = calcula_hora($hora_saida_cliente, $hora_chegada_sede);

								if (strlen($hora_saida_cliente) > 0 AND strlen($hora_chegada_sede) > 0)
									$km_5[$i] = $km_chegada_sede - $km_saida_cliente;
								else
									$km_5[$i] = 0;
							}

							$km_geral = $km_geral + ($km_1[$i] + $km_2[$i] + $km_3[$i] + $km_4[$i] + $km_5[$i]);
							$hora_geral = (calcula_hora_simples($horas_2[$i]) + calcula_hora_simples($horas_4[$i]));
							$aux_hora_geral = $aux_hora_geral + $hora_geral;

							$valor_total_horas = $valor_total_horas + ($hora_geral * $hora_tecnica);
							################################################################################################

							if($i == 0) { # HD 147992
								$sql = " UPDATE tbl_os set data_conserto = $xhora_chegada_cliente WHERE tbl_os.os = $os_visita_x ";
								$res = @pg_query($con,$sql);
							}

							if (strlen($os_visita) == 0 AND $novo == 't'){
								$sql = "INSERT INTO tbl_os_visita (
											os                  ,
											os_revenda          ,
											data                ,
											hora_saida_sede     ,
											km_saida_sede       ,
											hora_chegada_cliente,
											km_chegada_cliente  ,
											hora_saida_almoco   ,
											km_saida_almoco     ,
											hora_chegada_almoco ,
											km_chegada_almoco   ,
											hora_saida_cliente  ,
											km_saida_cliente    ,
											hora_chegada_sede   ,
											km_chegada_sede
										) VALUES (
											$os_visita_x          ,
											$os_revenda           ,
											$xdata                ,
											$xhora_saida_sede     ,
											$xkm_saida_sede       ,
											$xhora_chegada_cliente,
											$xkm_chegada_cliente  ,
											$xhora_saida_almoco   ,
											$xkm_saida_almoco     ,
											$xhora_chegada_almoco ,
											$xkm_chegada_almoco   ,
											$xhora_saida_cliente  ,
											$xkm_saida_cliente    ,
											$xhora_chegada_sede   ,
											$xkm_chegada_sede
										)";
							} else {
								$sql = "UPDATE tbl_os_visita set
											data                 = $xdata                ,
											hora_saida_sede      = $xhora_saida_sede     ,
											km_saida_sede        = $xkm_saida_sede       ,
											hora_chegada_cliente = $xhora_chegada_cliente,
											km_chegada_cliente   = $xkm_chegada_cliente  ,
											hora_saida_almoco    = $xhora_saida_almoco   ,
											km_saida_almoco      = $xkm_saida_almoco     ,
											hora_chegada_almoco  = $xhora_chegada_almoco ,
											km_chegada_almoco    = $xkm_chegada_almoco   ,
											hora_saida_cliente   = $xhora_saida_cliente  ,
											km_saida_cliente     = $xkm_saida_cliente    ,
											hora_chegada_sede    = $xhora_chegada_sede   ,
											km_chegada_sede      = $xkm_chegada_sede
										WHERE os_visita = $os_visita ";
								if ($os_manutencao == 't'){
									$sql .= " AND os_revenda = $os_revenda ";
								} else {
									$sql .= " AND os = $os ";
								}
							}
							#echo nl2br($sql);
							$res = @pg_exec($con,$sql);
							$msg_erro = @pg_errormessage($con);
						}
					}

					if (strlen($os) > 0 AND strlen($msg_erro) == 0) {
						$sqlv = "SELECT os_visita
								FROM tbl_os_visita
								WHERE ";
						if ($os_manutencao == 't') {
							$sqlv .= " os_revenda = $os_revenda ";
						} else {
							$sqlv .= " os = $os ";
						}

						$resv     = @pg_exec($con,$sqlv);
						$msg_erro = @pg_errormessage($con);

						if (pg_numrows($resv)==0) {
							$msg_erro = " Digite a linha de percurso da visita.";
							/*OFICINA MOSTRA DIFERENTE - HD 31226*/
							if($tipo_atendimento == 63){
								$msg_erro = " Digite data da realização do serviço";
							}
						}
					}
				}
			}
		}

		if (strlen($km_geral) == 0) $km_geral = '0';
		if (strlen($aux_hora_geral) == 0) $aux_hora_geral = '0';

		/* HD 38159 */
		$km_geral = $xdeslocamento_km;

		if (strlen($msg_erro) == 0){

			$pecas_na_os = $_POST['pecas_na_os'];

			if (strlen($pecas_na_os) == 0) {
				$pecas_na_os = 5;
			}

			for ($i = 0 ; $i < $pecas_na_os; $i++) {

				$os_item           = trim($_POST['os_item_'           . $i]);
				$os_produto        = trim($_POST['os_produto_'        . $i]);
				$referencia        = trim($_POST['referencia_'        . $i]);
				$qtde              = trim($_POST['qtde_'              . $i]);
				$qtde              = str_replace (",",".",$qtde);
				$defeito           = trim($_POST['defeito_'           . $i]);
				$servico_realizado = trim($_POST['servico_realizado_' . $i]);

				$referencia = str_replace ("." , "" , $referencia);
				$referencia = str_replace ("-" , "" , $referencia);
				$referencia = str_replace ("/" , "" , $referencia);
				$referencia = str_replace (" " , "" , $referencia);

				if (strlen($defeito) == 0)
					$xdefeito = 'null';
				else
					$xdefeito = "'".$defeito."'";

				if (strlen($servico_realizado) == 0)
					$xservico_realizado = 'null';
				else
					$xservico_realizado = "'".$servico_realizado."'";

				if (strlen($referencia) > 0) {

					if (strlen($qtde) == 0 or $qtde == 0) {
						$qtde = "1";
					}

					if (strlen($msg_erro) == 0) {

						$referencia = strtoupper ($referencia);

						if (strlen($os_produto) > 0 AND strlen($referencia) == 0) {
							$sql = "UPDATE tbl_os_produto SET
										os = 4836000
									WHERE os         = $os
									AND   os_produto = $os_produto";
							$res = @pg_exec($con,$sql);
							$msg_erro .= pg_errormessage($con);
						}

						if (strlen($referencia) > 0) {
							$sql = "SELECT tbl_peca.*
									FROM   tbl_peca
									WHERE  tbl_peca.referencia_pesquisa = '$referencia'
									AND    tbl_peca.fabrica = $login_fabrica;";
							$res = pg_exec($con,$sql);

							if (pg_numrows($res) == 0) {
								$msg_erro = "Peça $referencia não cadastrada";
								$linha_erro = $i;
							} else {
								$peca = pg_result($res,0,peca);
							}

							if (strlen($defeito) == 0)           $msg_erro = "Favor informar o defeito da peça";
							if (strlen($servico_realizado) == 0) $msg_erro = "Favor informar o serviço realizado";

							if (strlen($msg_erro) > 0) {
								break ;
							}

							if ((strlen($os_produto) == 0)){
								$sql = "INSERT INTO tbl_os_produto (
											os     ,
											produto,
											serie
										)VALUES(
											$os,
											$produto,
											$xserie
									);";
								$res = @pg_exec($con,$sql);
								$msg_erro .= pg_errormessage($con);

								$res = pg_exec($con,"SELECT CURRVAL ('seq_os_produto')");
								$os_produto  = pg_result($res,0,0);
							} else {
								$sql = "UPDATE tbl_os_produto SET
												os      = $os      ,
												produto = $produto,
												serie   = $xserie
										WHERE os_produto = $os_produto;";
								$res = @pg_exec($con,$sql);
								$msg_erro .= pg_errormessage($con);
							}

							if (strlen($msg_erro) > 0) {
								break ;
							}

							if (strlen($os_item) == 0){
								$sql = "INSERT INTO tbl_os_item (
											os_produto       ,
											peca             ,
											qtde             ,
											defeito          ,
											servico_realizado
										)VALUES(
											$os_produto,
											$peca             ,
											$qtde             ,
											$defeito          ,
											$servico_realizado
									);";
							} else {
								$sql = "UPDATE tbl_os_item SET
											os_produto        = $os_produto,
											peca              = $peca             ,
											qtde              = $qtde             ,
											defeito           = $defeito          ,
											servico_realizado = $servico_realizado
										WHERE os_item = $os_item";
							}
							$res = pg_exec($con,$sql);
							$msg_erro = pg_errormessage($con);
						}
					}
				} else {
					# HD 38833 - Não estava apagando itens.
					if (strlen($os_produto) > 0 AND strlen($referencia) == 0) {

						$sql = "SELECT tbl_os_produto.os_produto
								FROM   tbl_os_produto
								JOIN   tbl_os_item USING(os_produto)
								WHERE  tbl_os_produto.os         = $os
								AND    tbl_os_produto.os_produto = $os_produto
								AND    (tbl_os_item.pedido_cliente IS NOT NULL OR tbl_os_item.pedido IS NOT NULL)";
						$res = pg_exec($con,$sql);

						if (pg_numrows($res) >0) {
							$msg_erro .= "Item da OS já gerou pedido. Não é possível a exclusão!";
						}

						if (strlen($msg_erro) == 0){
							$sql = "UPDATE tbl_os_produto SET
										os = 4836000
									WHERE os         = $os
									AND   os_produto = $os_produto";
							$res = @pg_exec($con,$sql);
							$msg_erro .= pg_errormessage($con);
						}
					}
				}
			}
		}

		if (strlen($msg_erro) == 0){

			$sql = "SELECT *
					FROM	tbl_os_extra
					WHERE	os = $os";
			$res = @pg_exec($con,$sql);

			if (@pg_numrows($res) > 0){

				$sql = "UPDATE tbl_os_extra SET
							anormalidades        = $xanormalidades,
							causas               = $xcausas,
							medidas_corretivas   = $xmedidas_corretivas,
							recomendacoes        = $xrecomendacoes,
							obs                  = $xobs_extra,
							natureza_servico     = $xnatureza_servico,
							selo                 = $xselo,
							lacre_encontrado     = $xlacre_encontrado,
							lacre                = $xlacre,
							representante        = $xrepresentante,
							tecnico              = $xtecnico,
							laudo_tecnico        = $xlaudo_tecnico,
							classificacao_os     = $xclassificacao_os,
							mao_de_obra          = $xmao_de_obra,
							mao_de_obra_por_hora = $xmao_de_obra_por_hora,
							desconto_peca        = $xdesconto_peca,
							qtde_km              = $xdeslocamento_km
						WHERE os = $os ";
				$res = @pg_exec($con,$sql);
				$msg_erro = @pg_errormessage($con);

				/* ATUALIZACAO: Outros Serviços */
				$sql = "UPDATE tbl_os_extra SET
							certificado_conformidade    = $xcertificado_conformidade,
							desconto_certificado        = $desconto_certificado
						WHERE os = $os ";
				$res = @pg_exec($con,$sql);
				$msg_erro = @pg_errormessage($con);

				/* ATUALIZACAO: Deslocamento e Mao de Obra (do técnico) */
				if ($os_manutencao == 't'){

					/* GARANTIA não cobra VALORES - VALORES SOMENTE PARA FATURADO */
					/* Verifica se tem alguma OS faturada, se tiver, nao zera os valores. */
					if ($classificacao_os == '46'){
						$sql = "SELECT count(*) AS qtde_garantia
								FROM   tbl_os
								JOIN   tbl_os_extra USING(os)
								WHERE  tbl_os.fabrica   = $login_fabrica
								AND    tbl_os.posto     = $login_posto
								AND    tbl_os.os_numero = $os_revenda
								AND    tbl_os.excluida IS NOT NULL
								AND    tbl_os_extra.classificacao_os = 6 ";
						$res = pg_exec($con,$sql);
						if (pg_numrows($res) >0) {
							$qtde_garantia = pg_result($res,0,qtde_garantia);
							if ($qtde_garantia == 0) {
								$xregulagem_peso_padrao = '0'; 
								$xhora_tecnica			= '0';
								$xvalor_diaria			= '0';
							}
						}
					}

					$sql = "UPDATE tbl_os_revenda SET

								/* DESLOCAMENTO */
								taxa_visita                 = $xtaxa_visita,
								visita_por_km               = $xvisita_por_km,
								valor_por_km                = $xvalor_por_km,
								veiculo                     = $xveiculo,

								/* MAO-DE-OBRA */
								hora_tecnica                = $xhora_tecnica,
								valor_diaria                = $xvalor_diaria,

								/* OUTROS SERVIÇOS */
								regulagem_peso_padrao       = $xregulagem_peso_padrao,
								/*desconto_regulagem        = $desconto_regulagem, (nao é usado mais desconto, se precisar de desconto tem que criar o campo)*/

								/* DESCONTOS */
								desconto_deslocamento       = $desconto_deslocamento,
								desconto_hora_tecnica       = $desconto_hora_tecnica,
								desconto_diaria             = $desconto_diaria,

								deslocamento_km             = $km_geral,
								qtde_horas                  = round($aux_hora_geral::numeric,2)

							WHERE os_revenda = $os_revenda ";
							
				} else {
					$sql = "UPDATE tbl_os_extra SET

								/* DESLOCAMENTO */
								taxa_visita                 = $xtaxa_visita,
								visita_por_km               = $xvisita_por_km,
								valor_por_km                = $xvalor_por_km,
								veiculo                     = $xveiculo,

								/* MAO-DE-OBRA */
								hora_tecnica                = $xhora_tecnica,
								valor_diaria                = $xvalor_diaria,

								/* OUTROS SERVIÇOS */
								regulagem_peso_padrao       = $xregulagem_peso_padrao,
								desconto_regulagem          = $desconto_regulagem,
								cobrar_regulagem            = '$cobrar_regulagem',

								/* DESCONTOS */
								desconto_deslocamento       = $desconto_deslocamento,
								desconto_hora_tecnica       = $desconto_hora_tecnica,
								desconto_diaria             = $desconto_diaria,
								
								representante               = $xrepresentante,
								deslocamento_km             = $km_geral,
								qtde_horas                  = round($aux_hora_geral::numeric,2)

							WHERE os = $os ";
				}
				#echo nl2br($sql);
				$res = @pg_exec($con,$sql);
				$msg_erro = @pg_errormessage($con);

			} else {
				$msg_erro = "Não existe registro com o Nº de OS : $os em OS Extra";
			}
		}
	}

	if ($solucao_os == 528 and strlen($msg_erro) == 0) {
		$sql = "SELECT count(*)
				FROM tbl_os_item
				JOIN tbl_os_produto USING(os_produto)
				WHERE tbl_os_produto.os = $os ";
		$res      = @pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
		if (pg_result($res,0,0) == 0){
			$msg_erro .= "Informe a peça trocada.";
		}
	}

	if (strlen($msg_erro) == 0) {
		$sql = "SELECT fn_valida_os_item($os, $login_fabrica)";
		$res      = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}

	if (strlen($msg_erro) == 0) {
		$sql = "SELECT fn_valida_os($os, $login_fabrica)";
		$res = @pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}

	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}

	if (strlen($msg_erro) == 0) {
		$sql = "SELECT fn_calcula_os_filizola($os, $login_fabrica)";
		$res = @pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}

	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}

	#HD 22874
	if (strlen(trim($_POST['data_fechamento']))>0 AND strlen($msg_erro) == 0) {
		$xdata_fechamento = fnc_formata_data_pg(trim($_POST['data_fechamento']));

		if ($xdata_fechamento <> 'null'){

			if ($xdata_fechamento > "'".date("Y-m-d")."'") {
				$msg_erro = "Data fechamento maior que a data de hoje";
			}

			if (strlen($msg_erro) == 0) {
				$sql = "SELECT $xdata_fechamento < tbl_os.data_abertura
						FROM tbl_os
						WHERE os = $os";
				$res = pg_exec($con,$sql);
				if (pg_result($res,0,0) == 't'){
					$msg_erro = "Data de fechamento não pode ser anterior a data de abertura.";
				}
			}

			if (strlen($msg_erro) == 0) {
				$sql = "UPDATE tbl_os SET
								data_fechamento = $xdata_fechamento
						WHERE os    = $os
						AND fabrica = $login_fabrica
						AND posto   = $login_posto;";
				$res = @pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}

			if (strlen($msg_erro) == 0) {
				$sql = "SELECT fn_valida_os($os, $login_fabrica)";
				$res = @pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}

			if (strpos($msg_erro,"CONTEXT:")) {
				$x = explode('CONTEXT:',$msg_erro);
				$msg_erro = $x[0];
			}

			if (strlen($msg_erro) == 0) {
				$sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
				$res = @pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}

			if (strpos($msg_erro,"CONTEXT:")) {
				$x = explode('CONTEXT:',$msg_erro);
				$msg_erro = $x[0];
			}
		}
	}


	 
	if(strlen($msg_erro) == 0){		
    	if (is_array($_FILES['anexo_os']) and $_FILES['anexo_os']['name'] != '') {
    		$arquivo          = isset($_FILES["anexo_os"]) ? $_FILES["anexo_os"] : FALSE;
    	
    		if(!$s3_ge->uploadFileS3($os, $arquivo)){
    			$msg_erro = "O arquivo da OS não foi enviado!!! " . $s3_ge->_erro; // . $erroS3;
    		}
    	}else{
    		if($login_fabrica == 7 and !empty($hd_chamado)){
    			$msg_erro = "O Upload da OS digitalizada é obrigatório.";
    		}    		
    	}
	}
	
	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");
		header ("Location: os_press_filizola.php?os=$os");
		exit;
	} else {
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}

}


// #################################################################################//

if (strlen($os) > 0) {
	$sql = "SELECT  tbl_os.os,
					tbl_os.sua_os,
					tbl_os.posto,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')    AS data_abertura,
					TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY')  AS data_fechamento,
					tbl_os.quem_abriu_chamado,
					tbl_os.obs,
					tbl_os.serie,
					tbl_os.aparencia_produto,
					tbl_os.acessorios,
					tbl_os.nota_fiscal,
					tbl_os.hd_chamado,
					TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY')  AS data_nf,
					tbl_os.capacidade,
					tbl_os.versao,
					tbl_os.divisao,
					tbl_os.defeito_reclamado,
					tbl_os.defeito_reclamado_descricao,
					tbl_os.defeito_constatado,
					tbl_os.solucao_os,
					tbl_os.condicao,
					tbl_produto.produto,
					tbl_produto.referencia,
					tbl_produto.descricao,
					tbl_produto.nome_comercial,
					tbl_produto.linha,
					tbl_produto.familia,
					tbl_produto.capacidade AS produto_capacidade,
					tbl_produto.divisao    AS produto_divisao,
					tbl_os.tipo_atendimento,
					tbl_os.pedido_cliente,
					tbl_tipo_atendimento.descricao AS tipo_atendimento_descricao,
					tbl_os.cliente,
					tbl_os.consumidor_nome,
					tbl_os.consumidor_cpf,
					tbl_os.consumidor_endereco,
					tbl_os.consumidor_numero,
					tbl_os.consumidor_complemento,
					tbl_os.consumidor_bairro,
					tbl_os.consumidor_cep,
					tbl_os.consumidor_cidade,
					tbl_os.consumidor_fone,
					tbl_os.consumidor_estado,
					tbl_os.consumidor_email,
					tbl_posto_fabrica.contato_endereco,
					tbl_posto_fabrica.contato_numero,
					tbl_posto_fabrica.contato_cep,
					tbl_posto_fabrica.contato_cidade,
					tbl_posto_fabrica.contato_estado,
					tbl_posto_fabrica.contato_fone_comercial,
					tbl_posto_fabrica.tipo_posto,
					tbl_posto.cnpj,
					tbl_posto.ie,
					tbl_os_extra.deslocamento_km,
					tbl_os_extra.taxa_visita,
					tbl_os_extra.visita_por_km,
					tbl_os_extra.valor_por_km,
					tbl_os_extra.veiculo,
					tbl_os_extra.hora_tecnica,
					tbl_os_extra.mao_de_obra,
					tbl_os_extra.mao_de_obra_por_hora,
					tbl_os_extra.regulagem_peso_padrao,
					tbl_os_extra.cobrar_regulagem,
					tbl_os_extra.certificado_conformidade,
					tbl_os_extra.valor_diaria,
					tbl_os_extra.laudo_tecnico,
					tbl_os_extra.qtde_horas,
					tbl_os_extra.anormalidades,
					tbl_os_extra.causas,
					tbl_os_extra.medidas_corretivas,
					tbl_os_extra.recomendacoes,
					tbl_os_extra.obs AS obs_extra,
					tbl_os_extra.natureza_servico,
					tbl_os_extra.selo,
					tbl_os_extra.lacre_encontrado,
					tbl_os_extra.lacre,
					tbl_os_extra.tecnico,
					tbl_os_extra.representante,
					tbl_os_extra.classificacao_os,
					tbl_os_extra.desconto_deslocamento,
					tbl_os_extra.desconto_hora_tecnica,
					tbl_os_extra.desconto_diaria,
					tbl_os_extra.desconto_regulagem,
					tbl_os_extra.desconto_certificado,
					tbl_os_extra.desconto_peca,
					tbl_os_revenda.os_revenda,
					tbl_os_revenda.os_manutencao,
					tbl_os.consumidor_nome_assinatura
			FROM   tbl_os
			JOIN   tbl_produto              USING(produto)
			LEFT   JOIN tbl_os_extra        ON tbl_os_extra.os = tbl_os.os
			LEFT JOIN tbl_tipo_atendimento  ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
			JOIN   tbl_posto_fabrica        ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN   tbl_posto                ON tbl_posto_fabrica.posto = tbl_posto.posto
			LEFT JOIN tbl_os_revenda        ON tbl_os_revenda.os_revenda = tbl_os.os_numero AND tbl_os_revenda.posto = tbl_os.posto
			WHERE  tbl_os.os      = $os
			AND    tbl_os.fabrica = $login_fabrica
			AND    tbl_os.posto   = $login_posto";

	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0 ) {

		$os                          = pg_result($res, 0, 'os');
		$sua_os                      = pg_result($res, 0, 'sua_os');
		$posto                       = pg_result($res, 0, 'posto');
		$data_abertura               = pg_result($res, 0, 'data_abertura');
		$data_fechamento             = pg_result($res, 0, 'data_fechamento');
		$quem_abriu_chamado          = pg_result($res, 0, 'quem_abriu_chamado');
		$obs_os                      = pg_result($res, 0, 'obs');
		$obs_extra                   = pg_result($res, 0, 'obs_extra');
		$serie                       = pg_result($res, 0, 'serie');
		$aparencia_produto           = pg_result($res, 0, 'aparencia_produto');
		$acessorios                  = pg_result($res, 0, 'acessorios');
		$nota_fiscal                 = pg_result($res, 0, 'nota_fiscal');
		$hd_chamado                 = pg_result($res, 0, 'hd_chamado');
		$data_nf                     = pg_result($res, 0, 'data_nf');
		$capacidade                  = pg_result($res, 0, 'capacidade');
		$versao                      = pg_result($res, 0, 'versao');
		$divisao                     = pg_result($res, 0, 'divisao');
		$defeito_reclamado           = pg_result($res, 0, 'defeito_reclamado');
		$defeito_reclamado_descricao = pg_result($res, 0, 'defeito_reclamado_descricao');
		$defeito_constatado          = pg_result($res, 0, 'defeito_constatado');
		$solucao_os                  = pg_result($res, 0, 'solucao_os');
		$condicao                    = pg_result($res, 0, 'condicao');
		$produto                     = pg_result($res, 0, 'produto');
		$produto_referencia          = pg_result($res, 0, 'referencia');
		$produto_descricao           = pg_result($res, 0, 'descricao');
		$nome_comercial              = pg_result($res, 0, 'nome_comercial');
		$produto_familia             = pg_result($res, 0, 'familia');
		$produto_linha               = pg_result($res, 0, 'linha');
		$produto_capacidade          = pg_result($res, 0, 'produto_capacidade');
		$produto_divisao             = pg_result($res, 0, 'produto_divisao');
		$tipo_atendimento            = pg_result($res, 0, 'tipo_atendimento');
		$pedido_cliente              = pg_result($res, 0, 'pedido_cliente');
		$tipo_atendimento_descricao  = pg_result($res, 0, 'tipo_atendimento_descricao');
		$cliente                     = pg_result($res, 0, 'cliente');
		$cliente_nome                = pg_result($res, 0, 'consumidor_nome');
		$cliente_cpf                 = pg_result($res, 0, 'consumidor_cpf');
		$cliente_endereco            = pg_result($res, 0, 'consumidor_endereco');
		$cliente_numero              = pg_result($res, 0, 'consumidor_numero');
		$cliente_complemento         = pg_result($res, 0, 'consumidor_complemento');
		$cliente_bairro              = pg_result($res, 0, 'consumidor_bairro');
		$cliente_cep                 = pg_result($res, 0, 'consumidor_cep');
		$cliente_cidade              = pg_result($res, 0, 'consumidor_cidade');
		$cliente_fone                = pg_result($res, 0, 'consumidor_fone');
		$cliente_estado              = pg_result($res, 0, 'consumidor_estado');
		$consumidor_email            = pg_result($res, 0, 'consumidor_email');
		//$cliente_contrato          = pg_result($res, 0, 'cliente_contrato');
		$posto_endereco              = pg_result($res, 0, 'contato_endereco');
		$posto_numero                = pg_result($res, 0, 'contato_numero');
		$posto_cep                   = pg_result($res, 0, 'contato_cep');
		$posto_cidade                = pg_result($res, 0, 'contato_cidade');
		$posto_estado                = pg_result($res, 0, 'contato_estado');
		$posto_fone                  = pg_result($res, 0, 'contato_fone_comercial');
		$tipo_posto                  = pg_result($res, 0, 'tipo_posto');
		$posto_cnpj                  = pg_result($res, 0, 'cnpj');
		$posto_ie                    = pg_result($res, 0, 'ie');
		// impedir que valores do POST sejam reescritos
		// HD 114544
		$deslocamento_km             = (empty($deslocamento_km )) ? pg_result($res, 0, 'deslocamento_km') : $deslocamento_km;
		$taxa_visita                 = (empty($taxa_visita) )     ? pg_result($res, 0, 'taxa_visita')     : $taxa_visita;
		$visita_por_km               = (empty($visita_por_km))    ? pg_result($res, 0, 'visita_por_km')   : $visita_por_km;
		$valor_por_km                = (empty($valor_por_km))     ? pg_result($res, 0, 'valor_por_km')    : $valor_por_km;
		$veiculo                     = (empty($veiculo))          ? pg_result($res, 0, 'veiculo')         : $veiculo;
		$hora_tecnica                = (empty($hora_tecnica))     ? pg_result($res, 0, 'hora_tecnica')    : $hora_tecnica;
		$valor_diaria                = (empty($valor_diaria))     ? pg_result($res, 0, 'valor_diaria')    : $valor_diaria;
		// fim HD 114544
		$mao_de_obra                 = pg_result($res, 0, 'mao_de_obra');
		$mao_de_obra_por_hora        = pg_result($res, 0, 'mao_de_obra_por_hora');
		$regulagem_peso_padrao       = pg_result($res, 0, 'regulagem_peso_padrao');
		$cobrar_regulagem            = pg_result($res, 0, 'cobrar_regulagem');
		$certificado_conformidade    = pg_result($res, 0, 'certificado_conformidade');
		$natureza_servico            = pg_result($res, 0, 'natureza_servico');
		$laudo_tecnico               = pg_result($res, 0, 'laudo_tecnico');
		$qtde_horas                  = pg_result($res, 0, 'qtde_horas');
		$anormalidades               = pg_result($res, 0, 'anormalidades');
		$causas                      = pg_result($res, 0, 'causas');
		$medidas_corretivas          = pg_result($res, 0, 'medidas_corretivas');
		$recomendacoes               = pg_result($res, 0, 'recomendacoes');
		$selo                        = pg_result($res, 0, 'selo');
		$lacre_encontrado            = pg_result($res, 0, 'lacre_encontrado');
		$lacre                       = pg_result($res, 0, 'lacre');
		$tecnico                     = pg_result($res, 0, 'tecnico');
		$representante               = pg_result($res, 0, 'representante');
		$classificacao_os            = pg_result($res, 0, 'classificacao_os');
		$os_revenda                  = pg_result($res, 0, 'os_revenda');
		$os_manutencao               = pg_result($res, 0, 'os_manutencao');
		$desconto_deslocamento       = pg_result($res, 0, 'desconto_deslocamento');
		$desconto_hora_tecnica       = pg_result($res, 0, 'desconto_hora_tecnica');
		$desconto_diaria             = pg_result($res, 0, 'desconto_diaria');
		$desconto_regulagem          = pg_result($res, 0, 'desconto_regulagem');
		$desconto_certificado        = pg_result($res, 0, 'desconto_certificado');
		$desconto_peca               = pg_result($res, 0, 'desconto_peca');
		$assinou_os                  = pg_result($res, 0, 'consumidor_nome_assinatura');

		if (strlen($desconto_peca)==0 AND strlen($cliente_cpf) > 0) {
			$sql = "SELECT  tbl_posto_consumidor.contrato,
							tbl_posto_consumidor.desconto_peca
					FROM   tbl_posto_consumidor
					JOIN   tbl_posto ON tbl_posto.posto = tbl_posto_consumidor.posto AND tbl_posto_consumidor.fabrica = $login_fabrica
					WHERE  tbl_posto.cnpj = '$cliente_cpf' ";
			$res2 = pg_exec($con,$sql);
			if (pg_numrows($res2) > 0 ) {
				$contrato      = trim(pg_result($res2,0,contrato));
				$desconto_peca = trim(pg_result($res2,0,desconto_peca));

				if ($contrato != 't'){
					$desconto_peca = "0";
				}
			}
		}

		if ($os_manutencao == 't'){
			$sql = "SELECT  tbl_os_revenda.taxa_visita,
							tbl_os_revenda.visita_por_km,
							tbl_os_revenda.valor_por_km,
							tbl_os_revenda.deslocamento_km,
							tbl_os_revenda.veiculo,
							tbl_os_revenda.hora_tecnica,
							tbl_os_revenda.valor_diaria,
							tbl_os_revenda.qtde_horas,
							tbl_os_revenda.regulagem_peso_padrao,
							tbl_os_revenda.desconto_deslocamento,
							tbl_os_revenda.desconto_hora_tecnica,
							tbl_os_revenda.desconto_diaria
							/*tbl_os_revenda.desconto_regulagem*/

					FROM   tbl_os
					JOIN   tbl_os_revenda        ON tbl_os_revenda.os_revenda = tbl_os.os_numero AND tbl_os_revenda.posto = tbl_os.posto
					WHERE  tbl_os.os = $os
					AND    tbl_os.fabrica = $login_fabrica
					AND    tbl_os.posto   = $login_posto";
			$res2 = pg_exec($con,$sql);
			if (pg_numrows($res2) > 0 ) {
				// impedir sobrescrita de valores vindos do POST
				// HD 114544
				$valor_por_km_caminhao    = ( empty($valor_por_km_caminhao) ) ? trim(pg_result($res2,0,'valor_por_km')) : $valor_por_km_caminhao ;
				$valor_por_km_carro       = ( empty($valor_por_km_carro) ) ? trim(pg_result($res2,0,'valor_por_km')) : $valor_por_km_carro;
				$valor_por_km             = ( empty($valor_por_km) ) ? trim(pg_result($res2,0,'valor_por_km')) : $valor_por_km ;
				$deslocamento_km          = ( empty($deslocamento_km) ) ? trim(pg_result($res2,0,'deslocamento_km')) : $deslocamento_km ;
				$veiculo                  = ( empty($veiculo) ) ? trim(pg_result($res2,0,'veiculo')) : $veiculo ;
				$taxa_visita              = ( $taxa_visita ) ? trim(pg_result($res2,0,'taxa_visita')) : $taxa_visita ;
				$hora_tecnica             = ( empty($hora_tecnica) ) ? trim(pg_result($res2,0,'hora_tecnica')) : $hora_tecnica;
				$valor_diaria             = ( empty($valor_diaria) ) ? trim(pg_result($res2,0,'valor_diaria')) : $valor_diaria;
				// fim 114544
				$regulagem_peso_padrao    = trim(pg_result($res2,0,'regulagem_peso_padrao'));
				$desconto_deslocamento	= pg_result($res2,0,'desconto_deslocamento');
				$desconto_hora_tecnica	= pg_result($res2,0,'desconto_hora_tecnica');
				$desconto_diaria		= pg_result($res2,0,'desconto_diaria');
				#$desconto_regulagem	= pg_result($res2,0,desconto_regulagem);
			}
		}

		if ($certificado_conformidade > 0){
			$cobrar_certificado = 't';
		}
		
		if ( empty($cobrar_hora_diaria) ) { // HD 114544 (não sobrescrever valor caso tenha vindo do POST)
			if ($valor_diaria == 0 AND $hora_tecnica == 0){
				$cobrar_hora_diaria = "isento";
			}
			if ($valor_diaria > 0 AND $hora_tecnica == 0){
				$cobrar_hora_diaria = "diaria";
			}
			if ($valor_diaria == 0 AND $hora_tecnica > 0){
				$cobrar_hora_diaria = "hora";
			}
		}
		if ( empty($cobrar_deslocamento) ) { // HD 114544 (não sobrescrever valor case tenha vindo do POST)
			if ($valor_por_km == 0 AND $taxa_visita == 0){
				$cobrar_deslocamento = "isento";
			}
			if ($valor_por_km > 0 AND $taxa_visita == 0){
				$cobrar_deslocamento = "valor_por_km";
			}
			if ($valor_por_km == 0 AND $taxa_visita > 0){
				$cobrar_deslocamento = "taxa_visita";
			}
		}
/*
		$taxa_visita			= number_format($taxa_visita, 2, '.', ' ');
		$valor_diaria			= number_format($valor_diaria, 2, '.', ' ');
	*/

		if (strlen($versao) == 0) {
			$sql = "SELECT
							tbl_os.capacidade      AS capacidade,
							tbl_os.divisao         AS divisao,
							tbl_os.versao          AS versao,
							tbl_produto.capacidade AS produto_capacidade,
							tbl_produto.divisao    AS produto_divisao
					FROM tbl_os
					JOIN tbl_produto USING(produto)
					WHERE fabrica  = $login_fabrica
					AND   posto    = $login_posto
					AND   tbl_produto.referencia = '$produto_referencia'
					AND   serie    = '$serie' ;";
			$res = @pg_exec($con,$sql);
			if (pg_numrows($res)>0) {
				$versao             = trim(pg_result($res,0,versao));
			}
		}

		if ($os_manutencao != 't' or 1==1){

			$sql = "SELECT  tbl_familia_valores.taxa_visita,
							tbl_familia_valores.hora_tecnica,
							tbl_familia_valores.valor_diaria,
							tbl_familia_valores.valor_por_km_caminhao,
							tbl_familia_valores.valor_por_km_carro,
							tbl_familia_valores.regulagem_peso_padrao,
							tbl_familia_valores.certificado_conformidade
					FROM    tbl_os
					JOIN    tbl_produto         USING(produto)
					JOIN    tbl_familia_valores USING(familia)
					WHERE   tbl_os.os = $os
					AND     tbl_os.fabrica = $login_fabrica ";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) > 0) {

				if ($cobrar_deslocamento  == 'taxa_visita' or $cobrar_deslocamento == 'isento'){
					$valor_por_km_caminhao    = ( empty($valor_por_km_caminhao) ) ? trim(pg_result($res, 0, 'valor_por_km_caminhao')) : $valor_por_km_caminhao ;
					$valor_por_km_carro       = ( empty($valor_por_km_carro) ) ? trim(pg_result($res, 0, 'valor_por_km_carro')) : $valor_por_km_carro ;
				}

				if ($cobrar_deslocamento  == 'valor_por_km' or $cobrar_deslocamento == 'isento'){
					$taxa_visita                  = (empty($taxa_visita)) ? trim(pg_result($res, 0, 'taxa_visita')) : $taxa_visita ;
					if ($veiculo == 'carro'){
						$valor_por_km_caminhao    = ( empty($valor_por_km_caminhao) ) ? trim(pg_result($res, 0, 'valor_por_km_caminhao')) : $valor_por_km_caminhao ;
						$valor_por_km_carro       = ( empty($valor_por_km_carro) ) ? $valor_por_km : $valor_por_km_carro ;
					}
					if ($veiculo == 'caminhao'){
						$valor_por_km_carro       = (empty($valor_por_km_carro)) ? trim(pg_result($res, 0, 'valor_por_km_carro')) : $valor_por_km_carro ;
						$valor_por_km_caminhao    = (empty($valor_por_km_caminhao)) ? $valor_por_km : $valor_por_km_caminhao ;
					}
				}

				/**
				 * Corrigido para não sobrescrever os valores de 'hora tecnica' e 'valor diaria'
				 * caso estes dados já tenham vindo populados no POST; para que em eventuais erros
				 * os campos continuem preenchidos como o usuário deixou.
				 * HD 114544
				 *
				 * @author Augusto Pascutti <augusto.pascutti@telecontrol.com.br>
				 */
				if ($cobrar_hora_diaria == "diaria"){
					$hora_tecnica             = (empty($hora_tecnica)) ? trim(pg_result($res, 0, 'hora_tecnica')) : $hora_tecnica ;
				}
				if ($cobrar_hora_diaria == "hora"){
					$valor_diaria             = (empty($valor_diaria)) ? trim(pg_result($res, 0, 'valor_diaria')) : $valor_diaria; 
				}
				if ($cobrar_hora_diaria != 'diaria' AND $cobrar_hora_diaria != 'hora'){
					$hora_tecnica             = (empty($hora_tecnica)) ? trim(pg_result($res, 0, 'hora_tecnica')) : $hora_tecnica ;
					$valor_diaria             = (empty($valor_diaria)) ? trim(pg_result($res, 0, 'valor_diaria')) : $valor_diaria; 
				}
				// fim HD 114544
				if ($regulagem_peso_padrao == "0" or strlen($regulagem_peso_padrao) == 0) {
					$regulagem_peso_padrao    = trim(pg_result($res,0,regulagem_peso_padrao));
				}
				if ($cobrar_certificado != "t"){
					$certificado_conformidade = trim(pg_result($res,0,certificado_conformidade));
				}
			}

			/* HD 46784 */
			$sql = "SELECT  valor_regulagem, valor_certificado
					FROM    tbl_capacidade_valores
					WHERE   fabrica = $login_fabrica
					AND     capacidade_de <= (SELECT capacidade FROM tbl_os WHERE tbl_os.os = $os AND fabrica = $login_fabrica )
					AND     capacidade_ate >= (SELECT capacidade FROM tbl_os WHERE tbl_os.os = $os AND fabrica = $login_fabrica )";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) > 0) {
				if ($regulagem_peso_padrao=='0' or strlen($regulagem_peso_padrao) == 0) {
					$regulagem_peso_padrao    = trim(pg_result($res,0,valor_regulagem));
				}
				if ($cobrar_certificado != "t"){
					$certificado_conformidade = trim(pg_result($res,0,valor_certificado));
				}
			}
		}

		$certificado_conformidade	= number_format($certificado_conformidade, 2, '.', ' ');
		$regulagem_peso_padrao		= number_format($regulagem_peso_padrao, 2, '.', '');
		$mao_de_obra				= number_format($mao_de_obra, 2, '.', ' ');

	}
}

$title = "Ordem de Serviço - Valores";
$layout_menu = "os";
include 'cabecalho.php';

$imprimir        = $_GET['imprimir'];

if (strlen($imprimir) > 0 AND strlen($os) > 0 ) {
	echo "<script language='javascript'>";
	echo "window.open ('os_print_filizola.php?os=$os','os_print','resizable=yes,resize=yes,toolbar=no,location=yes,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0')";
	echo "</script>";
}
?>

<? include "javascript_pesquisas.php" ?>

<script type="text/javascript" src="js/jquery-1.2.1.pack.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>

<script type="text/javascript" charset="utf-8">

	function mostrarAjudaServico(){
		alert("Serviços realizados na peça: \n\n Substituição de peça: Não receberá desconto adicional de 10% na reposição de peças porque não recolheu a peça substituida do cliente! \n\n Sustituição com recolhimento da peça danificada: Você terá 10% de desconto adicional na reposição de peças porque recolheu do cliente a peça substituida! \n\n Troca de Peça c/recolhimento: O Cliente terá 5% de desconto na peça trocada, o você vai faturar a peça aplicada com 5% de desconto para Filizola.\n\n Empréstimo de peça: Utilize este serviço caso você não tenha a peça para reparo e necessite que a Filizola envie a peça URGENTE!. \n\n Consignação de peça: Utilize este serviço para informar que a peça utilizada no reparo é de consignação, para gerar outro pedido de peça consignado!");
	}

	function classificacaoGarantia (){
		var erro=0;

		$("input[@name=cobrar_regulagem]").each(function (){
			if (this.checked){
				erro++;
			}
		});

		$("input[@name=cobrar_hora_diaria]").each(function (){
			if (this.checked){
				erro++;
			}
		});

		if (erro>0){
			return true ;
		} else {
			return false;
		}
	}
	$(function()
	{
		$("input[@rel='data']").maskedinput("99/99/9999");
		$("input[@rel='hora']").maskedinput("99:99");
	});


	function atualizaValorKM(campo){
		if (campo.value == 'carro'){
			$('input[name=valor_por_km]').val( $('input[name=valor_por_km_carro]').val() );
		}
		if (campo.value == 'caminhao'){
			$('input[name=valor_por_km]').val( $('input[name=valor_por_km_caminhao]').val() );
		}
	}

	function atualizaCobraHoraDiaria(campo){
		if (campo.value == 'isento'){
			$('div[name=div_hora]').css('display','none');
			$('div[name=div_diaria]').css('display','none');
			$('div[name=div_desconto_hora_diaria]').css('display','none');
			$('input[name=hora_tecnica]').attr('disabled','disabled');
			$('input[name=valor_diaria]').attr('disabled','disabled');
		}
		if (campo.value == 'hora'){
			$('div[name=div_hora]').css('display','');
			$('div[name=div_diaria]').css('display','none');
			$('div[name=div_desconto_hora_diaria]').css('display','');
			$('#hora_tecnica').removeAttr("disabled")
			$('#valor_diaria').attr('disabled','disabled');
		}
		if (campo.value == 'diaria'){
			$('div[name=div_hora]').css('display','none');
			$('div[name=div_diaria]').css('display','');
			$('div[name=div_desconto_hora_diaria]').css('display','');
			$('#hora_tecnica').attr('disabled','disabled');
			$('#valor_diaria').removeAttr("disabled")
		}
	}

	function atualizaCobraDeslocamento(campo){
		if (campo.value == 'isento'){
			$('div[name=div_valor_por_km]').css('display','none');
			$('div[name=div_taxa_visita]').css('display','none');
			$('div[name=div_desconto_deslocamento]').css('display','none');
			$('input[name=valor_por_km]').attr('disabled','disabled');
			$('input[name=taxa_visita]').attr('disabled','disabled');
		}
		if (campo.value == 'valor_por_km'){
			$('div[name=div_valor_por_km]').css('display','');
			$('div[name=div_taxa_visita]').css('display','none');
			$('div[name=div_desconto_deslocamento]').css('display','');
			$('input[name=valor_por_km]').removeAttr("disabled")
			$('input[name=taxa_visita]').attr('disabled','disabled');

			$('input[name=veiculo]').each(function (){
				if (this.checked){
					atualizaValorKM(this);
				}
			});
		}
		if (campo.value == 'taxa_visita'){
			$('div[name=div_valor_por_km]').css('display','none');
			$('div[name=div_taxa_visita]').css('display','');
			$('div[name=div_desconto_deslocamento]').css('display','');
			$('input[name=valor_por_km]').attr('disabled','disabled');
			$('input[name=taxa_visita]').removeAttr("disabled")
		}
	}

	function verificaValorPorKm(campo){
		if (campo.checked){
			$('div[name=div_valor_por_km]').css('display','');
			$('div[name=div_taxa_visita]').css('display','none');
			$('input[name=taxa_visita]').attr("disabled", true);
		} else {
			$('div[name=div_valor_por_km]').css('display','none');
			$('div[name=div_taxa_visita]').css('display','');
			$('input[name=taxa_visita]').removeAttr("disabled");
		}
		$("input[@name='veiculo']").each( function (){
			if (this.checked){
				atualizaValorKM( this );
			}
		});
	}

	function qtdeItens(campo){
		var linha = 0;
		if (campo.value > 0){
			$(".tabela_item tr").each( function (){
				linha = parseInt( $(this).attr("rel") );
				linha++;
				if (linha  > campo.value) {
					$(this).css('display','none');
				} else {
					$(this).css('display','');
				}
			});
		}
	}

function listaSolucao(defeito_constatado, produto_linha,defeito_reclamado, produto_familia) {
//verifica se o browser tem suporte a ajax
		try {ajax = new ActiveXObject("Microsoft.XMLHTTP");}
	catch(e) { try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
	catch(ex) { try {ajax = new XMLHttpRequest();}
		catch(exc) {alert("Esse browser não tem recursos para uso do Ajax"); ajax = null;}
	}
	}
//se tiver suporte ajax
		if(ajax) {
	//deixa apenas o elemento 1 no option, os outros são excluídos
			document.forms[0].solucao_os.options.length = 1;
	//opcoes é o nome do campo combo
			idOpcao  = document.getElementById("opcoes");
	//	 ajax.open("POST", "ajax_produto.php", true);
	ajax.open("GET", "ajax_solucao.php?defeito_constatado="+defeito_constatado+"&defeito_reclamado="+defeito_reclamado+"&produto_linha="+produto_linha+"&produto_familia="+produto_familia);
	ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

	ajax.onreadystatechange = function() {
		if(ajax.readyState == 1) {idOpcao.innerHTML = "Carregando...!";}//enquanto estiver processando...emite a msg
		if(ajax.readyState == 4 ) {
			if(ajax.responseXML) {
				montaComboSolucao(ajax.responseXML);//após ser processado-chama fun
			} else {
				idOpcao.innerHTML = "Selecione o defeito constatado";//caso não seja um arquivo XML emite a mensagem abaixo
			}
		}
	}
	//passa o código do produto escolhido
			var params = "defeito_constatado="+defeito_constatado+"&defeito_reclamado="+defeito_reclamado+"&produto_linha="+produto_linha+"&produto_familia="+produto_familia;
	ajax.send(null);
		}
}

function fnc_pesquisa_lista_basica (produto_referencia, peca_referencia, peca_descricao, peca_preco, voltagem, tipo, peca_qtde) {
        var url = "";
        if (tipo == "tudo") {
                url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        }

        if (tipo == "referencia") {
                url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        }

        if (tipo == "descricao") {
                url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        }
        janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
        janela.produto          = produto_referencia;
        janela.referencia       = peca_referencia;
        janela.descricao        = peca_descricao;
        janela.preco            = peca_preco;
        janela.qtde             = peca_qtde;
        janela.focus();

}

function montaComboSolucao(obj){
	var dataArray   = obj.getElementsByTagName("produto");//pega a tag produto
			if(dataArray.length > 0) {//total de elementos contidos na tag cidade
				for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
					var item = dataArray[i];
		//contéudo dos campos no arquivo XML
				var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
					var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
					idOpcao.innerHTML = "";
		//cria um novo option dinamicamente
				var novo = document.createElement("option");
					novo.setAttribute("id", "opcoes");//atribui um ID a esse elemento
							novo.value = codigo;		//atribui um valor
									novo.text  = nome;//atribui um texto
											document.forms[0].solucao_os.options.add(novo);//adiciona o novo elemento
				}
			} else { idOpcao.innerHTML = "Nenhuma solução encontrada";//caso o XML volte vazio, printa a mensagem abaixo
			}
}

function createRequestObject(){
	var request_;
	var browser = navigator.appName;
	if(browser == "Microsoft Internet Explorer"){
		 request_ = new ActiveXObject("Microsoft.XMLHTTP");
	} else {
		 request_ = new XMLHttpRequest();
	}
	return request_;
}

var http5 = new Array();

function verificaProduto(produto,serie){
	referencia   = produto;
	numero_serie = serie.value;

	if (referencia.length > 0 || numero_serie.length > 0) {
		var curDateTime = new Date();
		http5[curDateTime] = createRequestObject();
		url = "<?=$PHP_SELF?>?ajax=true&buscaInformacoes=true&produto_referencia="+referencia+"&serie="+numero_serie+'&data='+curDateTime;
		http5[curDateTime].open('get',url);

		http5[curDateTime].onreadystatechange = function(){
			if (http5[curDateTime].readyState == 4){
				if (http5[curDateTime].status == 200 || http4[curDateTime].status == 304){
					//alert(http5[curDateTime].responseText);
					var results = http5[curDateTime].responseText.split("|");
					if (results[0] == 'ok') {
					/*	if (document.getElementById('capacidade')){
							document.getElementById('capacidade').value = results[1];
						}
						if (document.getElementById('divisao')){
							document.getElementById('divisao').value = results[2];
						}*/
						if (document.getElementById('versao')){
							document.getElementById('versao').value = results[3];
						}
					} else {
					/*	if (document.getElementById('capacidade')){
							document.getElementById('capacidade').value='';
						}
						if (document.getElementById('divisao')){
							document.getElementById('divisao').value='';
						}*/
						if (document.getElementById('versao')){
							//document.getElementById('versao').value='';
						}
					}
				}
			}
		}
		http5[curDateTime].send(null);
	}
}

	function atualizaServicos(valor){
		$("select[@name^='servico_realizado_']").each(function (){
			if (this.value.length>0){
				if (this.value != valor){
					this.value = valor;
				}
			}
		});
	}

	/*HD 47695 */
	function mostraServicos(linha) {
		<? if($login_posto == 6359){ #liberado somente para PA de teste - HD 47695?>
		$.ajax({
			type: "GET",
			url: "os_filizola_valores_servico_ajax.php",
			data: "referencia=" + $('#referencia_'+linha).val()+"&servico_realizado="+$('#servico_realizado_'+linha).val(),
			cache: false,
			beforeSend: function() {
				$('#servico_realizado_'+linha).html('<option>Aguarde....</option>');
			},
			success: function(txt) {
				$('#servico_realizado_'+linha).html(txt);
			},
			error: function(txt) {
				alert(txt);
			}
		});
		<?}?>
	}

</script>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.border {
	border: 1px solid #ced7e7;
}

.table_line {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

input {
	font-size: 10px;
}

.top_lst {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef
}

.line_lst {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff
}

input {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 09px;
	font-weight: normal;
	border: 1x solid #a0a0a0;
	background-color: #FFFFFF;
}

TEXTAREA {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 1x solid #a0a0a0;
	background-color: #FFFFFF;
}

fieldset.valores , fieldset.valores div{
	padding: 0.2em;
	font-size:10px;
	width:225px;
}

fieldset.valores label {
	float:left;
	width:43%;
	margin-right:0.2em;
	padding-top:0.2em;
	text-align:right;
}

fieldset.valores span {
	font-size:11px;
	font-weight:bold;
}


</style>

<? if (strlen($msg_erro) > 0){ ?>
<TABLE width='100%'>
<TR>
	<TD class='error'><? echo $msg_erro; ?></TD>
</TR>
</TABLE>
<?
}
//echo $msg_debug;



if (strlen($msg_erro) > 0){
	$nota_fiscal        = $_POST['nota_fiscal'];
	$data_nf            = $_POST['data_nf'];
	$serie              = $_POST['serie'];
	$capacidade         = $_POST['capacidade'];
	$versao             = $_POST['versao'];
	$divisao            = $_POST['divisao'];
	$classificacao_os   = $_POST['classificacao_os'];
	$defeito_constatado = $_POST['defeito_constatado'];
	$solucao_os         = $_POST['solucao_os'];
	$anormalidades      = $_POST['anormalidades'];
	$causas             = $_POST['causas'];
	$medidas_corretivas = $_POST['medidas_corretivas'];
	$recomendacoes      = $_POST['recomendacoes'];
	$obs_extra          = $_POST['obs_extra'];
	$selo               = $_POST['selo'];
	$lacre_encontrado   = $_POST['lacre_encontrado'];
	$lacre              = $_POST['lacre'];
	$tecnico            = $_POST['tecnico'];
	$aparencia_produto  = $_POST['aparencia_produto'];
	$acessorios         = $_POST['acessorios'];
	$assinou_os         = $_POST['assinou_os'];

}
?>

<form name='frm_os' action='<? echo $PHP_SELF; ?>' method="post" enctype='multipart/form-data'>
<input type="hidden" name="os"      value="<? echo $os; ?>">
<input type="hidden" name="sua_os"  value="<? echo $sua_os; ?>">

<input type="hidden" name="tipo_atendimento"  value="<? echo $tipo_atendimento; ?>">


<?
///////// se nao foi setado valor da OS
if (strlen($os) == 0) {
?>
<table class="border" width='500' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td width="50%" class="menu_top">DIGITE O NÚMERO DA SUA OS:</td>
		<TD class="table_line2"><INPUT TYPE="text" NAME="sua_os"></TD>
	</tr>
</table>
<br>
<input type='hidden' name='btn_acao' value=''>
<img src="imagens/btn_continuar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='continuar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Abre OS" border='0'>

<BR>

<?
} else {
?>

<table class="border" width='700' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td class="menu_top" width='20%'>NÚMERO DA OS</td>
		<TD class="table_line2"><?=$sua_os?></TD>
	<? if (strlen($pedido_cliente) > 0) {?>
		<td class="menu_top" width='20%'>PEDIDO FATURADO</td>
		<TD class="table_line2"><?=$pedido_cliente?></TD>
	<? }?>
	</tr>
</table>
<br>

<table class="border" width='700' align='center' border='0' cellpadding="1" cellspacing="3">
<?
	if (strlen(trim ($cliente_ie)) == 0) $cliente_ie = "&nbsp";

	switch (strlen(trim ($cliente_cpf))) {
		case 0:
			$cliente_cpf = "&nbsp";
		break;
		case 11:
			$cliente_cpf = substr ($cliente_cpf,0,3) . "." . substr ($cliente_cpf,3,3) . "." . substr ($cliente_cpf,6,3) . "-" . substr ($cliente_cpf,9,2);
		break;
		case 14:
			$cliente_cpf = substr ($cliente_cpf,0,2) . "." . substr ($cliente_cpf,2,3) . "." . substr ($cliente_cpf,5,3) . "/" . substr ($cliente_cpf,8,4) . "-" . substr ($cliente_cpf,12,2);
		break;
	}

?>
	<tr>
		<td class="menu_top">RAZÃO SOCIAL</td>
		<TD class="table_line2" nowrap colspan='2'><? echo $cliente_nome ?>&nbsp</TD>
		<td class="menu_top">CNPJ</td>
		<TD class="table_line2" nowrap><? echo $cliente_cpf ?>&nbsp</TD>
		<td class="menu_top">IE</td>
		<TD class="table_line2"><? echo $cliente_rg ?>&nbsp</TD>
	</tr>
	<tr>
		<td class="menu_top">ENDEREÇO</td>
		<TD class="table_line2" colspan='2'><? echo $cliente_endereco . ", " . $cliente_numero . " " . $cliente_complenento ?>&nbsp</TD>
		<td class="menu_top">CEP</td>
<?		$cliente_cep = substr ($cliente_cep,0,5) . "-" . substr ($cliente_cep,5,3); ?>
		<TD class="table_line2"><? echo $cliente_cep ?>&nbsp</TD>
		<td class="menu_top">TELEFONE</td>
		<TD class="table_line2"><? echo $cliente_fone ?>&nbsp</TD>
	</tr>
	<tr>
		<td class="menu_top">BAIRRO</td>
		<TD class="table_line2" colspan='2'><? echo $cliente_bairro ?>&nbsp</TD>
		<td class="menu_top">CIDADE</td>
		<TD class="table_line2"><? echo $cliente_cidade ?>&nbsp</TD>
		<td class="menu_top">ESTADO</td>
		<TD class="table_line2"><? echo $cliente_estado ?>&nbsp</TD>
	</tr>
	<tr>
		<td class="menu_top">CONTATO</td>
		<TD class="table_line2" colspan="2"><? echo $quem_abriu_chamado ?>&nbsp</TD>
		<td class="menu_top">EMAIL</td>
		<TD class="table_line2"><? echo $consumidor_email ?>&nbsp</TD>
		<td class="menu_top">DISTÂNCIA (KM)</td>
		<TD class="table_line2"><INPUT TYPE="text" NAME="deslocamento_km" id='deslocamento_km' VALUE="<?=$deslocamento_km?>" SIZE='9' MAXLENGTH='9'></TD>
	</tr>
	<tr>
		<td class="menu_top">OBS</td>
		<TD class="table_line2" colspan='5'><? echo $obs_os ?>&nbsp</TD>
	</tr>
</table>

<br>

<table class="border" width='700' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td class="menu_top">SÉRIE</td>
		<TD class="table_line2"><INPUT TYPE="text" NAME="serie" VALUE="<? echo $serie?>" SIZE='10' MAXLENGTH='20' onBlur="verificaProduto('<?=$produto_referencia?>',this)"></TD>
		<td class="menu_top">MODELO</td>
		<TD class="table_line2" COLSPAN='2'><? echo trim($produto_referencia); ?> - <? echo trim($produto_descricao); ?></TD>
	</tr>
	<tr>
		<TD class="menu_top">VERSÃO</TD>
		<TD class="table_line2">
			<INPUT TYPE="text" NAME="versao" id='versao' VALUE="<?=$versao?>" SIZE='10' MAXLENGTH='9' >
		</TD>
		<TD class="menu_top">CAPACIDADE</TD>
		<TD class="table_line2" width='80'>
			<? if (strlen($produto_capacidade)>0){
				echo "<INPUT TYPE='hidden' name='capacidade' id='capacidade' value='$produto_capacidade'>";
				echo "<INPUT TYPE='text' VALUE='$produto_capacidade' SIZE='9' onClick=\"alert('Não é possível alterar a capacidade')\" disabled>";
			} else {?>
				<INPUT TYPE="text" NAME="capacidade" id='capacidade' VALUE="<?=$capacidade?>" SIZE='9' MAXLENGTH='9'>
			<?}?>
		</TD>
		<TD class="menu_top">DIVISÃO</TD>
		<TD class="table_line2" width='80'>
			<? if (strlen($produto_divisao)>0){
				echo "<input type='hidden' name='divisao' value='$produto_divisao'>";
				echo "<INPUT TYPE='text' VALUE='$produto_divisao' SIZE='9' onClick=\"alert('Não é possível alterar a divisão')\" disabled>";
			} else {?>
				<INPUT TYPE="text" NAME="divisao" id='divisao' VALUE="<?=$divisao?>" SIZE='9' MAXLENGTH='9'>
			<?}?>
		</TD>
	</tr>
	<tr>
		<td class="menu_top">NOTA FISCAL </td>
		<TD class="table_line2"><INPUT TYPE="text" NAME="nota_fiscal" VALUE="<? echo $nota_fiscal?>" SIZE='10' MAXLENGTH='8' ></TD>
		<td class="menu_top">DATA NF</td>
		<TD class="table_line2" COLSPAN='2'><INPUT TYPE="text" NAME="data_nf" VALUE="<? echo $data_nf?>" SIZE='12' MAXLENGTH='10' rel='data'></TD>
	</tr>
</table>


<br>

<?if (strlen($tipo_atendimento_descricao)>0){?>
<table class="border" width='700' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td class="menu_top" width='20%'>NATUREZA</td>
		<TD class="table_line2"><?=$tipo_atendimento_descricao?></TD>
	</tr>
</table>
<br>
<?}?>

<?
echo "<INPUT TYPE='hidden' name='produto_referencia' value='$produto_referencia'>";
echo "<INPUT TYPE='hidden' name='xxproduto_linha' value='$produto_linha'>";
echo "<INPUT TYPE='hidden' name='xxproduto_familia' value='$produto_familia'>";
echo "<INPUT TYPE='hidden' name='voltagem' value=''>";
echo "<INPUT TYPE='hidden' name='hd_chamado' value='$hd_chamado'>";
?>

<table class="border" width='700' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td class="menu_top">DEFEITO RECLAMADO</td>
		<TD class="table_line2" colspan='5'>
			<?
			if($pedir_defeito_reclamado_descricao == 'f'){
				if (strlen($defeito_reclamado)>0){
					$sql = "SELECT defeito_reclamado,
									descricao as defeito_reclamado_descricao
							FROM tbl_defeito_reclamado
							WHERE defeito_reclamado= $defeito_reclamado";

					$res = pg_exec($con,$sql);
					if(pg_numrows($res)>0){
						$xdefeito_reclamado = pg_result($res,0,defeito_reclamado);
						$xdefeito_reclamado_descricao = pg_result($res,0,defeito_reclamado_descricao);
					}
				}
				echo $defeito_reclamado;
			} else {
				echo $defeito_reclamado_descricao;
			}

			echo "<INPUT TYPE='hidden' name='defeito_reclamado' value='$defeito_reclamado'>";
			?>

		&nbsp
		</TD>
	</tr>
	<tr>
		<td class="menu_top">DEFEITO CONSTATADO</td>
		<TD class="table_line2" colspan='2'><?php
			if ($pedir_defeito_reclamado_descricao == 'f') {
				$sql = "SELECT 	DISTINCT(tbl_diagnostico.defeito_constatado),
								tbl_defeito_constatado.descricao
						FROM tbl_diagnostico
						JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado
						WHERE tbl_diagnostico.linha = $produto_linha
						AND tbl_diagnostico.defeito_reclamado=$defeito_reclamado
						AND tbl_diagnostico.ativo='t' ";
				if (strlen($produto_familia)>0) $sql .=" AND tbl_diagnostico.familia = $produto_familia ";
				$sql.=" ORDER BY tbl_defeito_constatado.descricao";
			} else {
				$sql = "SELECT 	distinct(tbl_diagnostico.defeito_constatado),
								tbl_defeito_constatado.descricao
						FROM tbl_diagnostico
						JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado
						WHERE tbl_diagnostico.linha = $produto_linha
						AND tbl_diagnostico.ativo='t' ";
				if (strlen($produto_familia)>0) $sql .=" AND tbl_diagnostico.familia = $produto_familia ";
				$sql.=" ORDER BY tbl_defeito_constatado.descricao";
			}
			$res = pg_exec($con,$sql);

			echo "<select name='defeito_constatado' id='defeito_constatado' size='1' class='frm' onfocus='listaSolucao(document.frm_os.defeito_constatado.value, document.frm_os.xxproduto_linha.value, document.frm_os.defeito_reclamado.value,  document.frm_os.xxproduto_familia.value);'>";
			echo "<option value=''></option>";
			for ($y = 0 ; $y < pg_numrows($res) ; $y++ ) {
				$xxdefeito_constatado = pg_result($res,$y,defeito_constatado) ;
				$defeito_constatado_descricao = pg_result($res,$y,descricao) ;
				echo "<option value='$xxdefeito_constatado'"; if($defeito_constatado==$xxdefeito_constatado) echo "selected"; echo ">$defeito_constatado_descricao</option>";
			}
			echo "</select>";?>
		</TD>
		<td class="menu_top">SOLUÇÃO</td>
		<TD class="table_line2" colspan='2'>
			<select name='solucao_os' class='frm'  style='width:250px;' onfocus='listaSolucao(document.frm_os.defeito_constatado.value, document.frm_os.xxproduto_linha.value, document.frm_os.defeito_reclamado.value,  document.frm_os.xxproduto_familia.value);' >
			<?php
			if (strlen($solucao_os) > 0) {
				$sql = "SELECT 	solucao,
								descricao
						FROM tbl_solucao
						WHERE fabrica = $login_fabrica
						AND solucao = $solucao_os";
				$res = pg_exec($con, $sql);
				if (pg_numrows($res)>0){
					$solucao_descricao = pg_result($res,0,descricao);
					echo "<option id='opcoes' value='$solucao_os'>$solucao_descricao</option>";
				}
			}
			echo "<option id='opcoes' value=''></option>";
			?>
		</select>
		</TD>
	</tr>


</table>

<BR>
<?PHP
	if ($tipo_posto == 214 OR $tipo_posto == 215 OR $tipo_posto == 7 OR $tipo_posto == 224) {

		if ($tipo_posto != 214 AND $tipo_posto != 215){
			$valores_somente_leitura = 't'; 
		}
?>
<table class="border" width='700' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td colspan='9' class="menu_top">Valores Combinados na Abertura da OS</td>
	</tr>
	<tr valign='top'>
		<td colspan='3'>
				<fieldset class='valores' style='height:140px;'>
				<legend>Deslocamento</legend>
					<div>
					<!--<label for="cobrar_deslocamento">Isento:</label>
					<input type='radio' name='cobrar_deslocamento' value='isento' onClick='atualizaCobraDeslocamento(this)' <? if (strtolower($cobrar_deslocamento) == 'isento') echo "checked";?>>
					<br>
					-->
					<label for="cobrar_deslocamento">Por Km:</label>
					<input type='radio' name='cobrar_deslocamento' value='valor_por_km' <? if ($cobrar_deslocamento == 'valor_por_km') echo " checked " ?> onClick='atualizaCobraDeslocamento(this)'>
					<br />
					<label for="cobrar_deslocamento">Taxa de Visita:</label>
					<input type='radio' name='cobrar_deslocamento' value='taxa_visita' <? if ($cobrar_deslocamento == 'taxa_visita') echo " checked " ?> onClick='atualizaCobraDeslocamento(this)'>
					<br />
					</div>

					<div name='div_taxa_visita' <? if ($cobrar_deslocamento != 'taxa_visita') echo " style='display:none' "?>>
						<label for="taxa_visita">Valor:</label>
						<input type='text' name='taxa_visita' value='<? echo number_format($taxa_visita ,2,',','.') ?>' class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8' <? if($valores_somente_leitura == 't') {echo " READONLY='readonly'";} ?>>
						<br />
					</div>

					<div <? if ($cobrar_deslocamento != 'valor_por_km' or strlen($cobrar_deslocamento)==0) echo " style='display:none' " ?> name='div_valor_por_km'>
						<label for="veiculo">Carro:</label>
						<input type='radio' name='veiculo' value='carro' onClick='atualizaValorKM(this)' <? if (strtolower($veiculo) != 'caminhao') echo "checked";?>>
						<input type='text' name='valor_por_km_carro' value='<? echo number_format($valor_por_km_carro,2,',','.') ?>' class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8' <? if($valores_somente_leitura == 't') {echo " READONLY='readonly'";} ?>>
						<br>
						<label for="veiculo">Caminhão:</label>
						<input type='radio' name='veiculo' value='caminhao' onClick='atualizaValorKM(this)' <? if (strtolower($veiculo) == 'caminhao') echo "checked";?> >
						<input type='text' name='valor_por_km_caminhao' class='frm' value='<? echo number_format($valor_por_km_caminhao,2,',','.') ?>' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8' <? if($valores_somente_leitura == 't') {echo " READONLY='readonly'";} ?>>
						<input type='hidden' name='valor_por_km' value='<? echo $valor_por_km ?>' <? if($valores_somente_leitura == 't') {echo " READONLY='readonly'";} ?>>
					</div>

<?if  (1==2){ #HD 32483 ?>
					<div <? if ($cobrar_deslocamento == 'isento' OR strlen($cobrar_deslocamento)==0) echo " style='display:none' " ?> name='div_desconto_deslocamento'>
						<label>Desconto:</label>
						<input type='text' name='desconto_deslocamento' value="<? echo $desconto_deslocamento ?>" class='frm' onblur="this.className='frm';" onfocus="this.className='frm-on';" size='6' maxlength='6' <? if($valores_somente_leitura == 't') {echo " READONLY='readonly'";} ?>> %
					</div>
<?}?>
				</fieldset>
		</td>
		<td colspan='3'>
				<fieldset class='valores' style='height:140px;'>
					<legend>Mão de Obra</legend>
					<div>
					<label for="cobrar_hora_diaria">Diária:</label>
					<input type='radio' name='cobrar_hora_diaria' value='diaria' onClick='atualizaCobraHoraDiaria(this)' <? echo (strtolower($cobrar_hora_diaria) == 'diaria') ? 'checked="checked"' : '' ;?>>
					<br>
					<label for="cobrar_hora_diaria">Hora Técnica:</label>
					<input type='radio' name='cobrar_hora_diaria' value='hora' onClick='atualizaCobraHoraDiaria(this)' <? echo (strtolower($cobrar_hora_diaria) == 'hora') ? 'checked="checked"' : '' ;?>>
					<br>
					</div>
					<div <? if ($cobrar_hora_diaria != 'hora') echo " style='display:none' " ?> name='div_hora'>
						<label>Valor:</label>
						<input type='text' name='hora_tecnica' value='<? echo number_format($hora_tecnica,2,',','.') ?>' class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8' <? if($valores_somente_leitura == 't') {echo " READONLY='readonly'";} ?>>
						<br>
<?/*						<!--<br>
						<label>Desconto:</label>
						<input type='text' name='desconto_hora_tecnica' value="<? echo $desconto_hora_tecnica ?>" class='frm' onblur="this.className='frm';" onfocus="this.className='frm-on';" size='6' maxlength='6'> %-->
*/?>
					</div>
					<div <? if ($cobrar_hora_diaria != 'diaria') echo " style='display:none' " ?> name='div_diaria'>
						<label>Valor:</label>
						<input type='text' name='valor_diaria' value="<? echo number_format($valor_diaria,2,',','.') ?>" class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8' <? if($valores_somente_leitura == 't') {echo " READONLY='readonly'";} ?>>
						<br>
<?/*						<!--						<br>
						<label>Desconto:</label>
						<input type='text' name='desconto_diaria' value="<? echo $desconto_diaria ?>" class='frm' onblur="this.className='frm';" onfocus="this.className='frm-on';" size='6' maxlength='6'> %
-->
*/?>
					</div>
				</fieldset>
		</td>
		<td colspan='3'>
				<fieldset class='valores' style='height:140px;'>
					<legend>Outros Serviços</legend>
					<div>
						<label>Regulagem:</label>
						<input type="checkbox" name="cobrar_regulagem" value="t" <? if ($cobrar_regulagem=='t') echo "checked" ?>>
						<br />
						<label>Valor:</label>
						<?php 
						/**
						 * Manter o valor digitado em caso de erro ao invés de trazer o valor padrão
						 * HD 114544
						 *
						 * @author Augusto Pascutti <augusto.pascutti@telecontrol.com.br>
						 */
						$regulagem_peso_padrao = ( isset($xregulagem_peso_padrao) && ! empty($xregulagem_peso_padrao) ) ? $xregulagem_peso_padrao : $regulagem_peso_padrao; 
						?>
						<input type="text" name="regulagem_peso_padrao" value="<? echo number_format($regulagem_peso_padrao,2,',','.') ?>"  class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8' <? if($valores_somente_leitura == 't') {echo " READONLY='readonly'";} ?>>
						<br />
<?/*						<!--						<br />
						<label>Desconto:</label>
						<input type='text' name='desconto_regulagem' value="<? echo $desconto_regulagem ?>" class='frm' onblur="this.className='frm';" onfocus="this.className='frm-on';" size='6' maxlength='6'> %
						<br />
-->
*/?>
						<br />
						<label>Certificado:</label>
						<input type="checkbox" name="cobrar_certificado" value="t" <? if ($cobrar_certificado=='t') echo "checked" ?>>
						<br />
						<label>Valor:</label>
						
						<?php 
						/**
						 * Manter o valor digitado em caso de erro ao invés de trazer o valor padrão
						 * HD 114544
						 *
						 * @author Augusto Pascutti <augusto.pascutti@telecontrol.com.br>
						 */
						$certificado_conformidade = ( isset($xcertificado_conformidade) && ! empty($xcertificado_conformidade) ) ? $xcertificado_conformidade : $certificado_conformidade ; 
						?>
						<input type="text" name="certificado_conformidade" value="<? echo number_format($certificado_conformidade,2,',','.') ?>"  class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8' <? if($valores_somente_leitura == 't') {echo " READONLY='readonly'";} ?>>
						<br>
<?/*						<!--						<br />
						<label>Desconto:</label>
						<input type='text' name='desconto_certificado' value="<? echo $desconto_certificado ?>" class='frm' onblur="this.className='frm';" onfocus="this.className='frm-on';" size='6' maxlength='6'> %
-->
*/?>
						</div>
				</fieldset>
		</td>
	</tr>
	<tr>
		<td class="menu_top" colspan='2'>% DESCONTO PEÇAS</td>
		<TD class="table_line2"  colspan='2'>
			<input type='text' name='desconto_peca' class='frm' value='<?=$desconto_peca?>' size='6' maxlength='5' <? if($valores_somente_leitura == 't') {echo " READONLY='readonly'";} ?>>
		</TD>
		<td class="menu_top">CONDIÇÃO DE PAGAMENTO</td>
		<TD class="table_line2" colspan='5'>
			<?
			// hd 114537
			$condicao_enviada = ( isset($_POST['condicao']) && strlen($_POST['condicao']) > 0 ) ? $_POST['condicao'] : 1077 ;
			// fim hd 114537
			$sql = " SELECT condicao,
							codigo_condicao,
							descricao
					FROM tbl_condicao
					WHERE fabrica = $login_fabrica
						AND visivel is true";
			$res = pg_exec($con,$sql) ;
			echo "<SELECT NAME='condicao' class='frm'>";
			echo "<OPTION VALUE=''></OPTION>";

			for ($i = 0 ; $i < pg_numrows($res) ; $i++ ) { 
				// hd 114537
				$condicao_option  = pg_result($res,$i,'condicao');
				$condicao_descr   = pg_result($res,$i,'descricao');
				if ( ! is_null($condicao_enviada) && $condicao_enviada == $condicao_option ) {
					$selected = 'selected="selected"';
				} else if ( is_null($condicao_enviada) && $condicao_option == $condicao ) {
					$selected = 'selected="selected"';
				} else {
					$selected = '';
				}
			?>
				<option value="<?php echo $condicao_option; ?>" <?php echo $selected; ?>><?php echo $condicao_descr; ?></option>
				<?php
				// fim hd 114537
			}
			echo "</SELECT>";
			?>

		&nbsp
		</TD>
	</tr>

</table>

<BR>
<?PHP
	}
?>

<?
if ($os_manutencao != 't' or 1==1){

?>
		<? if ($tipo_atendimento == 63) {?>
		<table class="border" width='200' align='center' border='0' cellpadding="1" cellspacing="3">
		<tr>
			<td class="menu_top">&nbsp;</td>
			<td class="menu_top">Início</td>
			<td class="menu_top">Término</td>
		</tr>
		<tr>
			<td class="table_line">DATA</td>
			<td class="table_line">Hora</td>
			<td class="table_line">Hora</td>
		</tr>

		<?} else {?>
		<table class="border" width='300' align='center' border='0' cellpadding="1" cellspacing="3">
		<tr>
			<td class="menu_top">&nbsp;</td>
			<td class="menu_top" colspan='2'><!-- Chegada Cliente -->Serviço</td>
			<td class="menu_top" colspan='2'><!-- Chegada Almoço  -->Intervalo</td>
		</tr>
		<tr>
			<td class="table_line">DATA</td>
			<td class="table_line">Início</td>
			<td class="table_line">Término</td>
			<td class="table_line">Início</td>
			<td class="table_line">Término</td>
		</tr>
		<?}?>
	<?

	if ($os_manutencao == 't'){
		$condicao_os = " tbl_os_visita.os_revenda = $os_revenda ";
	} else {
		$condicao_os = " tbl_os_visita.os = $os ";
	}

	if (strlen($os) > 0) {
		$sql  = "SELECT tbl_os_visita.os_visita                                                       ,
						to_char(tbl_os_visita.data,'DD/MM/YYYY')               AS data                ,
						to_char(tbl_os_visita.hora_saida_sede, 'HH24:MI')      AS hora_saida_sede     ,
						tbl_os_visita.km_saida_sede                                                   ,
						to_char(tbl_os_visita.hora_chegada_cliente, 'HH24:MI') AS hora_chegada_cliente,
						tbl_os_visita.km_chegada_cliente                                              ,
						to_char(tbl_os_visita.hora_saida_almoco, 'HH24:MI')    AS hora_saida_almoco   ,
						tbl_os_visita.km_saida_almoco                                                 ,
						to_char(tbl_os_visita.hora_chegada_almoco, 'HH24:MI')  AS hora_chegada_almoco ,
						tbl_os_visita.km_chegada_almoco                                               ,
						to_char(tbl_os_visita.hora_saida_cliente, 'HH24:MI')   AS hora_saida_cliente  ,
						tbl_os_visita.km_saida_cliente                                                ,
						to_char(tbl_os_visita.hora_chegada_sede, 'HH24:MI')    AS hora_chegada_sede   ,
						tbl_os_visita.km_chegada_sede
				FROM    tbl_os_visita
				WHERE   $condicao_os
				ORDER BY tbl_os_visita.os_visita;";
		$vis = pg_exec($con,$sql);
		$qtde_visita_os = pg_numrows($vis);
	}

	if ($qtde_visita < $qtde_visita_os){
		$qtde_visita = $qtde_visita_os;

	}

	if ($tipo_atendimento == 63) {
		$qtde_visita = 1;
	}

	for($x=0; $x < $qtde_visita; $x++) {

		$novo                 = "t";
		$os_visita            = "";
		$data                 = "";
		$hora_saida_sede      = "";
		$km_saida_sede        = "";
		$hora_chegada_cliente = "";
		$km_chegada_cliente   = "";
		$hora_saida_almoco    = "";
		$km_saida_almoco      = "";
		$hora_chegada_almoco  = "";
		$km_chegada_almoco    = "";
		$hora_saida_cliente   = "";
		$km_saida_cliente     = "";
		$hora_chegada_sede    = "";
		$km_chegada_sede      = "";

		if ($x < $qtde_visita_os AND strlen($msg_erro) == 0) {
			$novo                 = 'f';
			$os_visita            = trim(pg_result($vis,$x,os_visita));
			$data                 = trim(pg_result($vis,$x,data));
			$hora_saida_sede      = trim(pg_result($vis,$x,hora_saida_sede));
			$km_saida_sede        = trim(pg_result($vis,$x,km_saida_sede));
			$hora_chegada_cliente = trim(pg_result($vis,$x,hora_chegada_cliente));
			$km_chegada_cliente   = trim(pg_result($vis,$x,km_chegada_cliente));
			$hora_saida_almoco    = trim(pg_result($vis,$x,hora_saida_almoco));
			$km_saida_almoco      = trim(pg_result($vis,$x,km_saida_almoco));
			$hora_chegada_almoco  = trim(pg_result($vis,$x,hora_chegada_almoco));
			$km_chegada_almoco    = trim(pg_result($vis,$x,km_chegada_almoco));
			$hora_saida_cliente   = trim(pg_result($vis,$x,hora_saida_cliente));
			$km_saida_cliente     = trim(pg_result($vis,$x,km_saida_cliente));
			$hora_chegada_sede    = trim(pg_result($vis,$x,hora_chegada_sede));
			$km_chegada_sede      = trim(pg_result($vis,$x,km_chegada_sede));
		}

		if (strlen($msg_erro) > 0) {
			$novo                 = $_POST['novo_'.$x];
			$os_visita            = $_POST['os_visita_'.$x];
			$data                 = $_POST['data_'.$x];
			$hora_saida_sede      = $_POST['hora_saida_sede_'.$x];
			$km_saida_sede        = $_POST['km_saida_sede_'.$x];
			$hora_chegada_cliente = $_POST['hora_chegada_cliente_'.$x];
			$km_chegada_cliente   = $_POST['km_chegada_cliente_'.$x];
			$hora_saida_almoco    = $_POST['hora_saida_almoco_'.$x];
			$km_saida_almoco      = $_POST['km_saida_almoco_'.$x];
			$hora_chegada_almoco  = $_POST['hora_chegada_almoco_'.$x];
			$km_chegada_almoco    = $_POST['km_chegada_almoco_'.$x];
			$hora_saida_cliente   = $_POST['hora_saida_cliente_'.$x];
			$km_saida_cliente     = $_POST['km_saida_cliente_'.$x];
			$hora_chegada_sede    = $_POST['hora_chegada_sede_'.$x];
			$km_chegada_sede      = $_POST['km_chegada_sede_'.$x];
		}

		$bgcor = "#ffffff";
		if ($tipo_atendimento == 63){
			echo "<TR>\n";
			echo "<TD bgcolor='#ced7e7' align='center'><INPUT TYPE='text' rel='data' NAME='data_$x'                 value='$data'                 size='12' maxlength='10'></TD>\n";
			echo "<TD bgcolor='#ffffff' align='center'><INPUT TYPE='text' rel='hora' NAME='hora_chegada_cliente_$x' value='$hora_chegada_cliente' size='06' maxlength='5'></TD>\n";
			echo "<TD bgcolor='#ffffff' align='center'><INPUT TYPE='text' rel='hora' NAME='hora_saida_cliente_$x'   value='$hora_saida_cliente'   size='06' maxlength='5'></TD>\n";
			echo "</TR>\n";
		} else {
			echo "<TR align='center'>\n";
			echo "<TD bgcolor='#ced7e7'><INPUT TYPE='text' rel='data' NAME='data_$x'                 value='$data'                 size='12' maxlength='10'></TD>\n";
			#echo "<TD bgcolor='#ffffff'><INPUT TYPE='text' rel='hora' NAME='hora_saida_sede_$x'      value='$hora_saida_sede'      size='06' maxlength='5'></TD>\n";
			#echo "<TD bgcolor='#ffffff'><INPUT TYPE='text'            NAME='km_saida_sede_$x'        value='$km_saida_sede'        size='06'></TD>\n";
			echo "<TD bgcolor='#ced7e7'><INPUT TYPE='text' rel='hora' NAME='hora_chegada_cliente_$x' value='$hora_chegada_cliente' size='06' maxlength='5'></TD>\n";
			#echo "<TD bgcolor='#ced7e7'><INPUT TYPE='text'            NAME='km_chegada_cliente_$x'   value='$km_chegada_cliente'   size='06'></TD>\n";
			echo "<TD bgcolor='#ffffff'><INPUT TYPE='text' rel='hora' NAME='hora_saida_cliente_$x'   value='$hora_saida_cliente'   size='06' maxlength='5'></TD>\n";
			#echo "<TD bgcolor='#ffffff'><INPUT TYPE='text'            NAME='km_saida_cliente_$x'     value='$km_saida_cliente'     size='06'></TD>\n";
			#echo "<TD bgcolor='#ced7e7'><INPUT TYPE='text' rel='hora' NAME='hora_chegada_sede_$x'    value='$hora_chegada_sede'    size='06' maxlength='5'></TD>\n";
			#echo "<TD bgcolor='#ced7e7'><INPUT TYPE='text'            NAME='km_chegada_sede_$x'      value='$km_chegada_sede'      size='06'></TD>\n";
			echo "<TD bgcolor='#ffffff'><INPUT TYPE='text' rel='hora' NAME='hora_saida_almoco_$x'    value='$hora_saida_almoco'    size='06' maxlength='5'></TD>\n";
			#echo "<TD bgcolor='#ffffff'><INPUT TYPE='text'            NAME='km_saida_almoco_$x'      value='$km_saida_almoco'      size='06'></TD>\n";
			echo "<TD bgcolor='#ced7e7'><INPUT TYPE='text' rel='hora' NAME='hora_chegada_almoco_$x'  value='$hora_chegada_almoco'  size='06' maxlength='5'></TD>\n";
			#echo "<TD bgcolor='#ced7e7'><INPUT TYPE='text'            NAME='km_chegada_almoco_$x'    value='$km_chegada_almoco'    size='06'></TD>\n";
			echo "</TR>\n";
		}

		echo "<input type='hidden' name='novo_$x' value='$novo'>\n";
		echo "<input type='hidden' name='os_visita_$x' value='$os_visita'>\n";
	}
}
?>
</table>

<BR>

<table class="border" width='700' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td class="menu_top">Anormalidades encontradas</td>
	</tr>
	<tr>
		<TD class="table_line"><TEXTAREA NAME="anormalidades" ROWS="2" COLS="122"><? echo $anormalidades; ?></TEXTAREA></TD>
	</tr>
</table>

<BR>

<table class="border" width='700' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td class="menu_top">Causa das anormalidades</td>
	</tr>
	<tr>
		<TD class="table_line"><TEXTAREA NAME="causas" ROWS="2" COLS="122"><? echo $causas; ?></TEXTAREA></TD>
	</tr>
</table>

<BR>

<table class="border" width='700' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td class="menu_top">Medidas corretivas</td>
	</tr>
	<tr>
		<TD class="table_line"><TEXTAREA NAME="medidas_corretivas" ROWS="2" COLS="122"><? echo $medidas_corretivas; ?></TEXTAREA></TD>
	</tr>
</table>

<BR>

<table class="border" width='700' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td class="menu_top">Recomendações aos clientes</td>
	</tr>
	<tr>
		<TD class="table_line"><TEXTAREA NAME="recomendacoes" ROWS="2" COLS="122"><? echo $recomendacoes; ?></TEXTAREA></TD>
	</tr>
</table>

<BR>

<table class="border" width='700' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td class="menu_top">Observações</td>
	</tr>
	<tr>
		<TD class="table_line"><TEXTAREA NAME="obs_extra" ROWS="2" COLS="122"><? echo $obs_extra; ?></TEXTAREA></TD>
	</tr>
</table>

<BR>


<? # -------------- Peças Substituídas ------------------------ ?>

<table class="border tabela_item" width='700' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
	<td colspan='6'></td>
	<td class='table_line'>
			Qtde Itens
			<select name='qtde_item_selecionado' onChange='qtdeItens(this)'>
			<option value='5'>5 Itens</option>
			<option value='10'>10 Itens</option>
			<option value='20'>20 Itens</option>
			<option value='30'>30 Itens</option>
			</select>
	</td>
	</tr>
	<tr>
		<td class="menu_top" rowspan='2'>#</td>
		<td class="menu_top" colspan='2'>Peça</td>
		<td class="menu_top" rowspan='2'>
			<acronym title=\"Clique para abrir a lista básica do produto.\">
			<a class='lnk' href='peca_consulta_por_produto.php?produto=<?=$produto?>' target='_blank'>LISTA<br>BÁSICA</a></acronym>
		</td>
		<td class="menu_top" rowspan='2'>Qtde</td>
		<td class="menu_top" rowspan='2'>Defeito</td>
		<td class="menu_top" rowspan='2'>Serviço</td>
	</tr>
	<tr>
		<td class="menu_top">Referência</td>
		<td class="menu_top">Descrição</td>
	</tr>

<?
	if (strlen($os) > 0){
		$sql = "SELECT	tbl_os_item.peca             ,
						tbl_os_item.os_item          ,
						tbl_os_item.os_produto       ,
						tbl_os_item.qtde             ,
						tbl_os_item.defeito          ,
						tbl_os_item.servico_realizado,
						tbl_os_item.pedido           ,
						tbl_peca.referencia          ,
						tbl_peca.descricao
				FROM	tbl_os_item
				JOIN	tbl_os_produto             USING (os_produto)
				JOIN	tbl_produto                USING (produto)
				JOIN	tbl_os                     USING (os)
				JOIN	tbl_peca                   USING (peca)
				WHERE	tbl_os.os      = $os
				AND		tbl_os.fabrica = $login_fabrica
				AND		tbl_os.posto   = $login_posto
				ORDER BY tbl_os_item.os_produto";
		$res = pg_exec($con,$sql) ;
		$qtde_item_os = pg_numrows($res);
	}

	if (strlen($_POST['qtde_item_selecionado'])>0){
		$qtde_item_os = $_POST['qtde_item_selecionado'];
	}

	if ($qtde_item < $qtde_item_os){
		$qtde_item = $qtde_item_os+5;
	}

	if ($qtde_item_visiveis < $qtde_item_os){
		$qtde_item_visiveis = $qtde_item_os+5 ;
	}

	$posicao = "";
	$item    = 0;

	for ($i = 0 ; $i < $qtde_item ; $i++) {

		$peca              = "";
		$os_item           = "";
		$os_produto        = "";
		$referencia        = "";
		$descricao         = "";
		$qtde              = "";
		$defeito           = "";
		$servico_realizado = "";
		$pedido            = "";
		$titulo_campo      = "";
		$disabled_gerou_pedido = "";


		if (strlen($os) > 0 AND $i < $qtde_item_os AND strlen($msg_erro) == 0) {
			$peca              = trim(pg_result($res,$i,peca));
			$os_item           = trim(pg_result($res,$i,os_item));
			$os_produto        = trim(pg_result($res,$i,os_produto));
			$referencia        = trim(pg_result($res,$i,referencia));
			$descricao         = trim(pg_result($res,$i,descricao));
			$qtde              = trim(pg_result($res,$i,qtde));
			$defeito           = trim(pg_result($res,$i,defeito));
			$servico_realizado = trim(pg_result($res,$i,servico_realizado));
			$pedido            = trim(pg_result($res,$i,pedido));
		}

		if ( strlen($msg_erro)>0 ) {
			$peca              = $_POST["peca_"              . $i];
			$os_item           = $_POST["os_item_"           . $i];
			$os_produto        = $_POST["os_produto_"        . $i];
			$referencia        = $_POST["referencia_"        . $i];
			$descricao         = $_POST["descricao_"         . $i];
			$qtde              = $_POST["qtde_"              . $i];
			$defeito           = $_POST["defeito_"           . $i];
			$servico_realizado = $_POST["servico_realizado_" . $i];
		}
		
		if (strlen($pedido)> 0){
			$titulo_campo = "Pedido Gerado: $pedido. Não é possível alterar a peça.";
			$disabled_gerou_pedido = " disabled ";
		}

		$ocultar_item = "";
		if ($i+1 > $qtde_item_visiveis){
			$ocultar_item = " style='display:none' ";
		}

		echo "<tr ".$ocultar_item." rel='$i'>\n";

		echo "<td align='center' class='table_line' >";
		echo "<input type='hidden' name='peca_$i' value='$peca'>\n";
		echo "<input type='hidden' name='os_item_$i' value='$os_item'>\n";
		echo "<input type='hidden' name='os_produto_$i' value='$os_produto'>\n";
		echo "<input type='hidden' name='preco_$i' value=''>\n";
		echo $i+1;
		echo "</td>\n";

		echo "<td align='center'>";

		echo "<input type='text' title= '$titulo_campo' name='referencia_$i' id='referencia_$i' size='12' maxlength='20' value='$referencia' onChange='mostraServicos($i)' $disabled_gerou_pedido>";
		/*NAO DEIXAR ALTERAR DEPOIS DE GERAR PEDIDO*/
		if (strlen($pedido) == 0) {
			echo "<img src='imagens/btn_lupa.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_peca (document.frm_os.referencia_$i, document.frm_os.descricao_$i, 'referencia')\"  alt='Clique para efetuar a pesquisa' style='cursor:pointer;' >";
		}
		echo "</td>\n";
		/*onclick='javascript: fnc_pesquisa_lista_basica (document.frm_os.produto_referencia.value , document.frm_os.referencia_$i , document.frm_os.descricao_$i , document.frm_os.preco_$i , document.frm_os.voltagem, \"referencia\",document.frm_os.qtde_$i)'*/

		echo "<td align='center'>";
		echo "<input type='text' title= '$titulo_campo' name='descricao_$i' size='20' maxlength='50' value='$descricao' onChange='mostraServicos($i)' $disabled_gerou_pedido> ";
		/*NAO DEIXAR ALTERAR DEPOIS DE GERAR PEDIDO*/
		if (strlen($pedido) == 0) {
			echo "<img src='imagens/btn_lupa.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_peca (document.frm_os.referencia_$i, document.frm_os.descricao_$i, 'descricao')\"  alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>";
		}
		echo "</td>\n";
		/* onclick='javascript: fnc_pesquisa_lista_basica (document.frm_os.produto_referencia.value , document.frm_os.referencia_$i , document.frm_os.descricao_$i , document.frm_os.preco_$i , document.frm_os.voltagem, \"descricao\",document.frm_os.qtde_$i)' */

		echo "<td align='center' nowrap>";
		/*NAO DEIXAR ALTERAR DEPOIS DE GERAR PEDIDO*/
		if (strlen($pedido) == 0) {
			echo "<img src='imagens/btn_lista.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_lista_basica (document.frm_os.produto_referencia.value , document.frm_os.referencia_$i , document.frm_os.descricao_$i , document.frm_os.preco_$i , document.frm_os.voltagem, \"referencia\",document.frm_os.qtde_$i)' alt='LISTA BÁSICA' style='cursor:pointer;'>";
		}
		echo "</td>\n";

		echo "<td align='center'>";
		echo "<input type='text' title= '$titulo_campo' name='qtde_$i' size='3' maxlength='3' value='$qtde' $disabled_gerou_pedido>";
		echo "</td>\n";

		echo "<td align='center'>";
		echo "<select size='1' title= '$titulo_campo' name='defeito_$i' $disabled_gerou_pedido>";
		echo "<option selected></option>";

		$sql = "SELECT	tbl_defeito.defeito,
						tbl_defeito.descricao
				FROM tbl_defeito
				WHERE tbl_defeito.fabrica = $login_fabrica
				ORDER BY descricao";
		$res0 = pg_exec($con,$sql) ;

		for ($x = 0 ; $x < pg_numrows($res0) ; $x++ ) {
			echo "<option ";
			if ($defeito == pg_result($res0,$x,defeito)) echo " selected ";
			echo " value='" . pg_result($res0,$x,defeito) . "'>" ;
			echo pg_result($res0,$x,descricao) ;
			echo "</option>";
		}

		echo "</select>";
		echo "</td>\n";

		echo "<td align='center'>";
		echo "<select size='1' title= '$titulo_campo' name='servico_realizado_$i' id='servico_realizado_$i' style='width:250px' onFocus='mostraServicos($i)' $disabled_gerou_pedido>";
		echo "<option selected></option>";

		$sql = "SELECT	tbl_servico_realizado.servico_realizado,
						tbl_servico_realizado.descricao
				FROM tbl_servico_realizado
				WHERE tbl_servico_realizado.fabrica = $login_fabrica ";
		if ($servico_realizado == '703'){ /* HD 42613 */
			$sql .= "AND ( tbl_servico_realizado.ativo IS TRUE OR tbl_servico_realizado.servico_realizado = $servico_realizado )";
		} else {
			if ($login_posto <> 6359){
				$sql .= "AND (tbl_servico_realizado.ativo IS TRUE)";
			}
		}
		$sql .= "ORDER BY descricao " ;
		$res0 = pg_exec($con,$sql) ;

		for ($x = 0 ; $x < pg_numrows($res0) ; $x++ ) {
			echo "<option ";
			if ($servico_realizado == pg_result($res0,$x,servico_realizado)) echo " selected ";
			echo " value='" . pg_result($res0,$x,servico_realizado) . "'>" ;
			echo pg_result($res0,$x,descricao) ;
			echo "</option>";
		}
		echo "</select>";
		if ($login_posto==6359) {echo "<img src='imagens/hint1.gif' style='border:none' onClick='mostrarAjudaServico()'>";}
		echo "</td>\n";
		echo "</tr>\n";
	}
	echo "<input type='hidden' name='pecas_na_os' value='$i'>\n";
?>


</table>

<BR>

<table class="border" width='700' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr> <? /* HD 25450 */?>
		<TD class='menu_top' colspan='2'>Aparência do Produto</TD>
		<TD class='menu_top' colspan='2'>Acessórios</TD>
	</tr>
	<tr>
		<TD class='table_line' colspan='2'>
			<INPUT TYPE='text' NAME='aparencia_produto' value='<? echo $aparencia_produto; ?>' size='50' maxlength='40'>
		</TD>
		<TD class='table_line' colspan='2'>
			<INPUT TYPE='text' NAME='acessorios' value='<? echo $acessorios; ?>' size='50' maxlength='40'>
		</TD>
	</tr>
	<tr>
		<TD class='menu_top'>Selo</TD>
		<TD class='menu_top'>Lacre encontrado</TD>
		<TD class='menu_top'>Lacre</TD>
		<TD class='menu_top'>Técnico</TD>
	</tr>
	<tr>
		<TD class='table_line'><INPUT TYPE='text' NAME='selo' value='<? echo $selo; ?>' size='20' maxlength=''></TD>
		<TD class='table_line'><INPUT TYPE='text' NAME='lacre_encontrado' value='<? echo $lacre_encontrado; ?>' size='20' maxlength=''></TD>
		<TD class='table_line'><INPUT TYPE='text' NAME='lacre' value='<? echo $lacre; ?>' size='20' maxlength=''></TD>
		<TD class='table_line'><INPUT TYPE='text' NAME='tecnico' value='<? echo $tecnico; ?>' size='20' maxlength=''></TD>
	</tr>
	<TR><?php
		if ($cookie_login['cook_login_tipo_posto'] == 214 || $cookie_login['cook_login_tipo_posto'] == 215) {//HD 254633
			$colspan = 2;
		} else {
			$colspan = 4;
		}?>
		<TD class='menu_top' colspan='<?=$colspan?>'>Assinatura:</TD><?php //HD 44796
		if ($cookie_login['cook_login_tipo_posto'] == 214 || $cookie_login['cook_login_tipo_posto'] == 215) {//HD 254633?>
			<TD class='menu_top' colspan='2'>Representante:</TD><?php
		}?>
	</TR>
	<TR>
		<TD class='table_line' colspan='<?=$colspan?>'>
			<INPUT TYPE='text' NAME='assinou_os' value='<? echo $assinou_os; ?>' size='50' maxlength='40'>
		</TD><?php
		if ($cookie_login['cook_login_tipo_posto'] == 214 || $cookie_login['cook_login_tipo_posto'] == 215) {//HD 254633?>
			<TD class='table_line' colspan='2'>
				<?php
					$representante = !empty($_POST['representante']) ? $_POST['representante'] : $representante;
				?>
				<select name="representante">
					<option value="">.:: Selecione um representante ::.</option><?php
					$sql = "SELECT tbl_representante.representante as cod_representante ,
								   tbl_representante.nome as representante
							  FROM tbl_posto_fabrica_representante
							  JOIN tbl_representante ON tbl_posto_fabrica_representante.representante = tbl_representante.representante
							  JOIN tbl_posto         ON tbl_posto_fabrica_representante.posto         = tbl_posto.posto
							 WHERE tbl_posto_fabrica_representante.fabrica = $login_fabrica
							   AND tbl_posto_fabrica_representante.posto   = $login_posto";

					$res   = @pg_exec($con, $sql);
					$total = @pg_numrows($res);

					for ($i = 0; $i < $total; $i++) {

						$cod_representante  = @trim(pg_result($res, $i, 'cod_representante'));
						$nome_representante = @trim(pg_result($res, $i, 'representante'));

						echo '<option value="'.$cod_representante.'" '.($cod_representante == $representante ? 'selected="selected"' : '').'>'.$nome_representante.'</option>';

					}?>
				</select>
			</TD><?php
		}?>
	</TR>
	<tr>
		<TD class='menu_top' colspan='4'>Classificação da OS</TD>
	</tr>
	<tr>
		<TD class='table_line' colspan='4'>
			<select name='classificacao_os'>
				<option <? if (strlen($classificacao_os)==0) {echo "selected";} ?>></option>
				<?

					if ($tipo_posto != 214 AND $tipo_posto != 215 AND $tipo_posto != 7 AND $tipo_posto <> 224) {
						$add = " AND (garantia IS TRUE OR classificacao_os = 5) ";
					}

					$sql = "SELECT	*
							FROM	tbl_classificacao_os
							WHERE	fabrica = $login_fabrica
							AND		ativo IS TRUE
							$add
							ORDER BY descricao";
					$res = @pg_exec($con,$sql);
					if(pg_numrows($res) > 0){
						for($i=0; $i < pg_numrows($res); $i++){
							echo "<option value='".pg_result($res,$i,classificacao_os)."'";
							if ($classificacao_os == pg_result($res,$i,classificacao_os)) echo " selected";
							echo ">".pg_result($res,$i,descricao)."</option>\n";
						}
					}
				?>
			</select>
		</TD>
	</tr>

	<tr>
		<TD class='menu_top' colspan='4'>Anexar cópia da OS</TD>
	</tr>
	<tr>
		<td class='table_line' colspan='4'>
			<label title="Inserir a imagem digitalizada da Ordem de Serviço, formatos JPG, JPEG, GIF, PNG, PDF, XML, DOC, DOCX. Máx. 3 Megapixels para imagens ou 2Mb para PDF, XML e DOC." style="position:relative;top:-3px;left:-10px;font-size:10px;font-family:verdana,arial,helvetica,sans-serif"> Anexar OS: </label>
			 <span title="Inserir a imagem digitalizada da Ordem de Serviço, formatos JPG, JPEG, GIF, PNG, PDF, XML, DOC, DOCX. Máx. 3 Megapixels para imagens ou 2Mb para PDF, XML e DOC." style="color:red;font-weight:bold"><img src="imagens/help.png"></span>
			<input type='file' name='anexo_os' class='frm' title="Inserir a imagem digitalizada da Ordem de Serviço, formatos JPG, JPEG, GIF, PNG, PDF, XML, DOC, DOCX.  Máx. 3 Megapixels para imagens ou 2Mb para PDF, XML e DOC.">
		</td>
	</tr>

	<? if ($tipo_posto == 214) { # 22874 ?>
	<tr>
		<TD class='menu_top' colspan='4'>Fechamento de OS</TD>
	</tr>
	<tr>
		<TD class='table_line' colspan='4'><INPUT TYPE='text' NAME='data_fechamento' value='<? echo $data_fechamento; ?>' rel='data' size='12' maxlength='10' <? echo (strlen($data_fechamento)>0)?"disabled":"" ?>></TD>
	</tr>
	<? } ?>
</table>
<BR>

<center>
<input type='hidden' name='btn_acao' value=''>
<?
/*IGOR HD: 47695 - 17/12/2008*/
if ($login_fabrica == 7){
	$sql = "SELECT	
					tbl_os_item.pedido
			FROM    tbl_os_item
			JOIN    tbl_os_produto             USING (os_produto)
			JOIN    tbl_os                     USING (os)
			JOIN    tbl_pedido                 ON tbl_os_item.pedido = tbl_pedido.pedido
			WHERE   tbl_os.os      = $os
			AND     tbl_os.fabrica = $login_fabrica
			AND     tbl_pedido.tipo_pedido <> 144;";

	$res_pedido = pg_exec($con, $sql);
	$msg_erro        = pg_errormessage($con);

	if (pg_numrows($res_pedido) > 0 ){
		$alterar_os = false;
	} else {
		$alterar_os = true;
	}
}

if($alterar_os){
	?>
	<img src="imagens/btn_gravar.gif" style="cursor: pointer;" onclick="javascript:

	if (document.frm_os.btn_acao.value == '' ) {
		if (document.frm_os.classificacao_os.value == 46){
			if(classificacaoGarantia()){
				if (!confirm('Ordem de Serviço em Garantia\n\nNão serão cobrados mão-de-obra, regulagem e peças.\n\nOK para continuar ou CANCELAR para voltar.'))
					return false;
			}
		}
		document.frm_os.btn_acao.value='gravar' ;
		document.frm_os.submit()
	} else {
		alert ('Aguarde submissão')
	}" ALT="Gravar formulário" border='0'>
<?} else {?>
	<img src="imagens/btn_gravar.gif" style="cursor: pointer;" ALT="Ordem de Serviço bloqueada para alteração por ter pedido gerado." border='0'>
<?
}?>


	<img src="imagens/btn_voltar.gif" style="cursor: pointer;" onclick="javascript:
	if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Voltar e digitar outra OS" border='0'>

</center><?php
} // fim do if q verifica se OS foi setada?>
<br />

</form>

<?
include 'rodape.php';
?>

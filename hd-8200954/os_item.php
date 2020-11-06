<?php

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_usuario.php';
include_once '_traducao_erro.php';
include_once 'funcoes.php';
include_once 'anexaNF_inc.php';
include_once 'funcao_explode_os_consumidor.php';

$token_cookie = $_COOKIE['sess'];
$cookie_login = get_cookie_login($token_cookie);
if($login_fabrica == 1){
    require "classes/ParametrosAdicionaisFabrica.php";
    $parametrosAdicionaisObject = new ParametrosAdicionaisFabrica($login_fabrica);

    require "classes/form/GeraComboType.php";
}

if($novaTelaOs) {
	header("Location: cadastro_os.php?os_id=$os");
}

if ($login_fabrica == 20) {
	$sql = "SELECT  posto
            FROM    tbl_posto_fabrica
            WHERE   posto       = $login_posto
            AND     fabrica     = $login_fabrica
            AND     atendimento = 'n'";

    $res = pg_query($con,$sql);

    if (pg_num_rows($res)) {
        $cond_os = empty($os) ? '' : '?os=' . $os;
        header('Location:os_cadastro_unico.php' . $cond_os);
        exit;
    }
}


if (strlen($_GET['os']) > 0) {
	if ($login_fabrica == 1) {
		$alterarOS = false;
		if (isset($_GET['alterar'])) {
			$alterarOS = $_GET['alterar'];	
		}
	}

	$sql  = "SELECT fn_valida_os_reincidente($os,$login_fabrica)";
	$res1 = pg_query ($con,$sql);

	$sql = "SELECT obs_reincidencia,os_reincidente FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
	$res = pg_query($con,$sql);

	$obs_reincidencia = pg_fetch_result($res,0,'obs_reincidencia');
	$os_reincidente   = pg_fetch_result($res,0,'os_reincidente');

	if ($os_reincidente == 't' AND (strlen($obs_reincidencia) == 0 or strlen($motivo_atraso ) == 27 OR strlen($observacao) == 27))
		header ("Location: os_motivo_atraso.php?os=$os&justificativa=ok");

}



if (strlen($_GET['reabrir']) > 0)  $reabrir = $_GET['reabrir'];
#HD 423001 - Organização do código para as fábricas que usam e não usam o programa "os_item_new".
if ($login_fabrica == 14 or $login_fabrica == 66) { # HD 17218 liberado para todos os Postos
	$os = $_GET['os'];
	header("Location: os_cadastro_intelbras_ajax.php?os=$os&reabrir=ok");
	exit;
} else if ($login_fabrica == 5) {
	$os = $_GET['os'];
	header("Location: os_item_new_mondial.php?os=$os&reabrir=$reabrir");
	exit;
} else if ($login_fabrica == 7) {
	$os = $_GET['os'];
	header("Location: os_filizola_valores.php?os=$os&reabrir=$reabrir");
	exit;
} else if ($login_fabrica == 1 || $login_fabrica == 20 || $login_fabrica == 8) {
	if($login_fabrica == 1){
		$os = $_GET['os'];
		$sql = "SELECT laudo_tecnico_os FROM tbl_laudo_tecnico_os WHERE fabrica = $login_fabrica AND os = $os and titulo !~'Pesquisa de satisfa'";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			$laudo = pg_fetch_result($res, 0, 'laudo_tecnico_os');
			header("Location: gerar_laudo_tecnico.php?os=$os&laudo=$laudo");
			exit;
		}
	}
} else {
	$os = $_GET['os'];
	header("Location: os_item_new.php?os=$os&reabrir=$reabrir");
	exit;
}

#HD 423001 FIM

$qtde_visita = 4;
//echo "$login_fabrica";

$msg_erro     = "";
$msg_previsao = "";


// fabio 17/01/2007 - verifica o status das OS da britania
if ( in_array($login_fabrica, array(1,3,6,11,172)) ) {

	$sql = "SELECT  status_os,observacao
			FROM    tbl_os_status
			WHERE   os = $os
			AND status_os IN (62,64,65,72,73,87,88)
			ORDER BY data DESC
			LIMIT 1";

	$res = pg_query ($con,$sql);

	if (@pg_num_rows($res) > 0) {

		$status = pg_fetch_result($res, 0, 'status_os');

		if ($status == '62' OR $status == '72' OR $status == '87') {
			header("Location: os_finalizada.php?os=$os");
			exit;
		}

		if ($status == '65') {
			header("Location: os_press.php?os=$os");
			exit;
		}

	}

}

$sql = "SELECT * FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = pg_query($con, $sql);

$pedir_causa_defeito_os_item      = pg_fetch_result($res, 0, 'pedir_causa_defeito_os_item');
$pedir_defeito_constatado_os_item = pg_fetch_result($res, 0, 'pedir_defeito_constatado_os_item');
$ip_fabricante                    = trim(pg_fetch_result($res, 0, 'ip_fabricante'));

$ip_acesso     = $_SERVER['REMOTE_ADDR'];
$os_item_admin = "null";

#if ($login_fabrica == 3 AND strpos ($ip_acesso,$ip_fabricante) !== false ) $os_item_admin = "273";
#if ($login_fabrica == 3 AND strpos ($ip_acesso,"201.0.9.216") !== false ) $os_item_admin = "273";

if (strlen($_POST['os']) > 0) $os = $_POST['os'];
else                          $os = $_GET['os'];

if (strlen($_POST['reabrir']) > 0) $reabrir = $_POST['reabrir'];
else                               $reabrir = $_GET['reabrir'];

//VERIFICA SE É COMPRESSOR -
if ($login_fabrica == 1) {

	$sql = "SELECT 	tipo_os_cortesia, tipo_os, os_numero
			FROM 	tbl_os
			WHERE 	fabrica = $login_fabrica
			AND   	os = $os;";

	$res = pg_query($con, $sql);

	if (pg_num_rows($res) == 1) {

		$tipo_os_cortesia = pg_fetch_result($res, 0, 'tipo_os_cortesia');
		$tipo_os          = pg_fetch_result($res, 0, 'tipo_os');
		$os_numero        = pg_fetch_result($res, 0, 'os_numero');

		if ($tipo_os_cortesia == "Compressor" OR $tipo_os == 10) {
			$compressor = 't';
		}

		/*PARA OS GEO, USA A OS REVENDA NA GRAVAÇÃO DE OS VISITA*/
		if ($tipo_os == 13) {

			$sql_aux_os = " os_revenda ";
			$aux_os     = $os_numero;

		} else {

			$sql_aux_os = " os ";
			$aux_os     = $os;

		}

	}

	#HD 11906
	$sql = "SELECT os FROM tbl_os_troca WHERE os=$os";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		header ("Location: os_press.php?os=$os");
		exit;
	}

}

$sql = "SELECT  tbl_os.sua_os,
				tbl_os.fabrica,
				tbl_os.tipo_atendimento
		FROM    tbl_os
		WHERE   tbl_os.os = $os";

$res = pg_query ($con,$sql) ;

if (@pg_num_rows($res) > 0) {

	if (pg_fetch_result ($res,0,fabrica) <> $login_fabrica) {
		header("Location: os_cadastro.php");
		exit;
	}

	if (in_array($login_fabrica,array(1,19))) {
		$tipo_atendimento = pg_fetch_result($res, 0, 'tipo_atendimento');
	}

	//validacao para bosch quando a OS for uma troca de produto não deverá vir para essa tela
	if ($login_fabrica == 20) {

		$tipo_atendimento = pg_fetch_result($res, 0, 'tipo_atendimento');

		if ($tipo_atendimento == 13 OR $tipo_atendimento == 66) {
			header ("Location: os_troca.php?os=$os");
			exit;
			echo $tipo_atendimento;
		}

	}
}

$sua_os = trim(pg_fetch_result($res,0,sua_os));

if (strlen($reabrir) > 0) {
	$sql = "UPDATE tbl_os SET data_fechamento = null, finalizada = null
			WHERE  tbl_os.os      = $os
			AND    tbl_os.fabrica = $login_fabrica
			AND    tbl_os.posto   = $login_posto;";
	$res = pg_query ($con,$sql);
	$msg_erro .= pg_last_error($con);
}

//adicionado por Fabio 29/11/2006 - numero de itens na OS
$qtde_itens_mostrar = "";

if (isset($_GET['n_itens']) AND strlen($_GET['n_itens']) > 0) {

	$qtde_itens_mostrar = $_GET['n_itens'];

	if ($qtde_itens_mostrar > 10) $qtde_itens_mostrar = 10;
	if ($qtde_itens_mostrar < 0)  $qtde_itens_mostrar = 3;

} else {

	$qtde_itens_mostrar = 3;

}

$numero_pecas_faturadas = 0;

// fim do numero de linhas - Fabio 29/11/2006


//modificado por Fernando 02/08/2006 - Exclusao do item na OS qdo o mesmo estiver abaixo dos 30%.
//verifica se tem os_item amarrado na os_produto se nao tiver ele apaga os_produto.

$os_item = trim($_GET ['os_item']);

if ($os_item > 0) {

	if ($os_item_old != $os_item) {

		$os_item_old = $os_item;
		//seleciona a os_produto que contem a os_item quem não geraam pedido
		$sql = "SELECT os_produto FROM tbl_os_item WHERE os_item = $os_item AND pedido IS NULL";

		$res = pg_query ($con,$sql);

		if(pg_num_rows($res) == 1){

			$os_produto = pg_fetch_result($res, 0, 'os_produto');

			#HD 15489
			$sql = "UPDATE tbl_os_produto SET os = 4836000 WHERE os_produto = $os_produto";
			$res = pg_query($con, $sql);

			#HD 15489
			if (1 == 2) {

				$sql = "DELETE FROM tbl_os_item WHERE os_item = $os_item ";
				$res = pg_query ($con,$sql);

				//verifica se tem os_item amarrada ao os_produto - caso nao tenha ele apaga o produto
				$sql = "SELECT count(os_produto) as os_produto_count FROM tbl_os_item WHERE os_produto = '$os_produto'; " ;
				$res = pg_query($con,$sql);

				$os_produto_count = pg_fetch_result($res,0,os_produto_count);

				if ($os_produto_count == 0) {

					$sql = "DELETE FROM tbl_os_produto WHERE os_produto = '$os_produto' AND os = '$os' ; ";
					$res = pg_query($con, $sql);

					$msg_erro_item .= "Item excluido com sucesso!";

				}

			}

		} else {

			$msg_erro_item .= "Não foi encontrado o item.";

		}

	} else {

		$msg_erro_item .= "Não foi encontrado o item.";

	}

}

$btn_acao = strtolower ($_POST['btn_acao']);

if (strlen($btn_acao) > 0 AND $login_fabrica == 1) {

	$sql = "SELECT os_status, status_os FROM tbl_os_status WHERE os = $os ORDER BY os_status DESC LIMIT 1";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {

		$os_pendente = pg_fetch_result($res, 0, 'status_os');

		if ($os_pendente == '91') {
			$msg_erro = "A OS foi recusada com Pendência de Documento e não pode sofrer alterações no lançamento de itens.";
		}

	}

}

if ($btn_acao == "gravar") {

	if ($login_fabrica == 1) {
		$lancouPeca = false;
	}

	//hd-2795821
    $sql_posto_fabrica = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE posto = $login_posto and fabrica = $login_fabrica";
    $res_posto_fabrica = pg_query($con, $sql_posto_fabrica);
    if(strlen(trim(pg_last_error($con)))== 0 ){
        if(pg_num_rows($res_posto_fabrica)>0){
            $parametros_adicionais = pg_fetch_result($res_posto_fabrica, 0, parametros_adicionais);
            $parametros_adicionais = json_decode($parametros_adicionais, true);
            
            $gera_pedido = (strlen(trim($parametros_adicionais['gera_pedido']))>0 )? $parametros_adicionais['gera_pedido'] : 'f';
        }
    }else{
        $msg_erro .= pg_last_error($con);
    }

	if(strlen($cookie_login['cook_login_unico']) > 0){ //HD-3165481
		$sqlOS = "SELECT fabrica FROM tbl_os WHERE os = $os";
		$resOS = pg_query($con, $sqlOS);
		if(pg_num_rows($resOS) > 0){
			$fabrica_os = pg_fetch_result($resOS, 0, 'fabrica');
			if($fabrica_os <> $login_fabrica){
				include_once "cabecalho.php";
	            echo '<table align="center" width="700">
	                    <tr style="background-color:#FF0000; font-size:16px; color:#FFFFFF; text-align:center;" >
	                        <td>OS: '.$os.' não pertence a essa Fábrica</td>
	                    </tr>
	            </table>';
	            exit;
			}
		}
	}

	$res = pg_query($con, "BEGIN TRANSACTION");

	$defeito_constatado = $_POST ['defeito_constatado'];
	$xtipo_atendimento  = $_POST ['tipo_atendimento'];

	//Samuel 18-08 a pedido do Fabricio da Britania o campo Defeito constatado e solucao passam a ser obrigatorios
	if ($login_fabrica == 3) {

		if (strlen($defeito_constatado) == 0) {
			$msg_erro .= "Informar o defeito constatado.<BR>";
		}

		if (strlen($solucao_os) == 0) {
			$msg_erro .= "Informar a solução.<BR>";
		}

	}

	//para a fabrica 11 é obrigatório aparencia_produto e acessorios, para as outras é mostrado na tela /os_cadastro.php
	if ( in_array($login_fabrica, array(11,172)) ) {

		//APARENCIA
		if (strlen(trim($aparencia_produto)) == 0) {

			$aparencia_produto = 'null';
			$msg_erro .= "Informar a Aparência do Produto.<BR>";

		} else {

			$aparencia_produto = "'".trim($aparencia_produto)."'";

			$sql = "UPDATE tbl_os SET aparencia_produto = $aparencia_produto
					WHERE  tbl_os.os    = $os
					AND    tbl_os.posto = $login_posto;";

			$res = @pg_query ($con,$sql);
			$msg_erro .= pg_last_error($con);

		}

		//ACESSORIOS
		if (strlen(trim($acessorios)) == 0) {

			$acessorios = 'null';
			$msg_erro .= "Informar os Acessórios do produto.<BR>";

		} else {

			$acessorios = "'".trim($acessorios)."'";

			$sql = "UPDATE tbl_os SET acessorios = $acessorios
					WHERE  tbl_os.os    = $os
					AND    tbl_os.posto = $login_posto;";

			$res = @pg_query ($con,$sql);
			$msg_erro .= pg_last_error($con);

		}

	}

	//Samuel 18-08 a pedido do Fabricio da Britania o campo Defeito constatado e solucao passam a ser obrigatorios

	if ($login_fabrica == 20) {

		$sql = "SELECT	tipo_atendimento
				FROM  	tbl_os
				WHERE	tbl_os.os    = $os
				AND		tbl_os.posto = $login_posto;";

		$res = @pg_query($con, $sql);

		$x_tipo_atendimento = @pg_fetch_result($res,0,0);

		if (strlen($solucao_os) == 0 AND ($x_tipo_atendimento == 10 OR $x_tipo_atendimento == 14 OR $x_tipo_atendimento == 15 OR $x_tipo_atendimento == 16)) {

			if ($sistema_lingua == "ES") $msg_erro = "Por favor informe la identificación";
			else                         $msg_erro = "Por favor informe a Identificação";

		}

	}
	if (strlen($xtipo_atendimento) > 0) { // HD 53926

		$sql = "UPDATE tbl_os SET tipo_atendimento = $xtipo_atendimento
				WHERE  tbl_os.os    = $os
				AND    tbl_os.posto = $login_posto;";

		$res = @pg_query($con, $sql);
		$msg_erro .= pg_last_error($con);

		if (strlen($msg_erro) == 0) {

			if ($xtipo_atendimento == 16 and $x_tipo_atendimento == 10) {

				$sql = "SELECT  tbl_posto.nome,
								tbl_posto_fabrica.codigo_posto,
								tbl_posto.email,
								tbl_os.consumidor_nome,
								tbl_produto.referencia,
								tbl_produto.descricao
						FROM	tbl_os
						JOIN	tbl_posto         USING(posto)
						JOIN	tbl_produto       USING(produto)
						JOIN	tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
						WHERE os = $os";

				$res = pg_query($con, $sql);

				if (pg_num_rows($res) > 0) {

					$posto_nome      = trim(pg_fetch_result($res, 0, 'nome'));
					$codigo_posto    = trim(pg_fetch_result($res, 0, 'codigo_posto'));
					$consumidor_nome = trim(pg_fetch_result($res, 0, 'consumidor_nome'));
					$produto_ref     = trim(pg_fetch_result($res, 0, 'referencia'));
					$produto_nome    = trim(pg_fetch_result($res, 0, 'descricao'));
					$email           = trim(pg_fetch_result($res, 0, 'email'));

					//ENVIA EMAIL PARA POSTO PRA CONFIRMAÇÃO
					$email_origem  = "pt.garantia@br.bosch.com";
					$email_destino = "edwing.diaz@co.bosch.com";
					$assunto       = "Nueva OS de Cortesía";

					$corpo  ="<br>Estimado Edwing Diaz,<br>\n\n";
					$corpo .="<br>El servicio autorizado <b>$codigo_posto - $posto_nome</b>, ha catastrado una cortesía comercial y necesita de su autorización.\n\n";
					$corpo .="<br>Cortesía para el cliente <b>$consumidor_nome</b> referenta a la herramienta: <b>$produto_ref - $produto_nome</b>\n";
					$corpo .="El número de OS es <b>$os</b>\n";

					$body_top  = "--Message-Boundary\n";
					$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
					$body_top .= "Content-transfer-encoding: 7BIT\n";
					$body_top .= "Content-description: Mail message body\n\n";

					if (@mail($email_destino, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), "From: ".$email_origem." \n $body_top ")) {
						$enviou = 'ok';
					}

				}

			}

		}

	}

	if (strlen($defeito_constatado) == 0) $defeito_constatado = 'null';

	if (strlen($defeito_constatado) > 0) {

		$sql = "UPDATE tbl_os SET defeito_constatado = $defeito_constatado
				WHERE  tbl_os.os    = $os
				AND    tbl_os.posto = $login_posto;";

		$res = @pg_query($con, $sql);
		$msg_erro .= pg_last_error($con);

	}

	if (strlen($msg_erro) == 0) {

		$xcausa_defeito = $_POST ['causa_defeito'];

		if (strlen($xcausa_defeito) == 0)
			$xcausa_defeito = "null";

		if (strlen($xcausa_defeito) > 0) {

			$sql = "UPDATE tbl_os SET causa_defeito = $xcausa_defeito
					WHERE  tbl_os.os    = $os
					AND    tbl_os.posto = $login_posto;";

			$res = @pg_query ($con,$sql);
			$msg_erro .= pg_last_error($con);

		}

	}

	if (strlen ($msg_erro) == 0) {

		$x_solucao_os = $_POST['solucao_os'];

		if (strlen($x_solucao_os) == 0) $x_solucao_os = 'null';
		else                            $x_solucao_os = "'".$x_solucao_os."'";

		$sql = "UPDATE tbl_os SET solucao_os = $x_solucao_os
				WHERE  tbl_os.os    = $os
				AND    tbl_os.posto = $login_posto;";

		$res = @pg_query($con,$sql);
		$msg_erro .= pg_last_error($con);

	}

	$obs = trim($_POST["obs"]);

	if (strlen($obs) > 0)  $obs = "'".str_replace("'","''",$obs)."'";
	else                   $obs = "null";

	if ($login_fabrica == 6) {

		if (strlen($obs) == 0) {
			$msg_erro .= "Por favor preencher o campo Observação<BR>";
		}

	}

	$tecnico_nome = trim($_POST["tecnico_nome"]);
	if (strlen($tecnico_nome) > 0) $tecnico_nome = "'".$tecnico_nome."'";
	else                           $tecnico_nome = "null";


	$valores_adicionais = trim($_POST["valores_adicionais"]);
	$valores_adicionais = str_replace (",",".",$valores_adicionais);

	if (strlen($valores_adicionais) == 0) $valores_adicionais = "0";

	$justificativa_adicionais = trim($_POST["justificativa_adicionais"]);

	if (strlen($justificativa_adicionais) > 0) $justificativa_adicionais = "'".$justificativa_adicionais."'";
	else                                       $justificativa_adicionais = "null";

	$qtde_km = trim($_POST["qtde_km"]);
	$qtde_km = str_replace (",",".",$qtde_km);
	if (strlen($qtde_km) == 0) $qtde_km = "0";

	$qtde_visitas = trim($_POST["qtde_visitas"]);
	if (strlen($qtde_visitas) == 0) $qtde_visitas = "0";

	$qtde_hora = trim($_POST["qtde_hora"]);
	$qtde_hora = str_replace (",",".",$qtde_hora);
	if (strlen($qtde_hora) == 0) $qtde_hora = "0";

	if ($login_fabrica == 1) {

		$sql = "SELECT  tbl_os.data_abertura   AS data_abertura,
						os_numero,
						tipo_atendimento
				FROM    tbl_os
				WHERE   tbl_os.os = $os";

		$res = pg_query($con, $sql) ;

		$data_abertura    = pg_fetch_result($res, 0, 'data_abertura');
		$os_numero        = pg_fetch_result($res, 0, 'os_numero');
		$tipo_atendimento = pg_fetch_result($res, 0, 'tipo_atendimento');

	}

	//HD 669169
	$data_conserto = trim($_POST["data_conserto"]);
	if (!empty($data_conserto)) {

		$aux_data_conserto = implode('-', array_reverse(explode('/', $data_conserto)));
		$tmp_dt            = explode('-', $aux_data_conserto);
		$sql_data_conserto = " data_conserto = '$aux_data_conserto', ";

		if (!checkdate($tmp_dt[1], $tmp_dt[2], $tmp_dt[0])) {
			$msg_erro .= 'Data de Conserto inválida!';
		}

		if (strtotime($aux_data_conserto) > strtotime(date('Y-m-d'))) {
			$msg_erro .= 'Data de conserto não pode ser maior que data de hoje!';
		}

		if (strtotime($data_abertura) > strtotime($aux_data_conserto)) {
			$msg_erro .= 'Data de conserto não pode ser menor que data de abertura!';
		}

	}

	if (empty($msg_erro)) {

		if ($login_fabrica == 20) {

			$sql = "UPDATE tbl_os SET obs = $obs
					WHERE  tbl_os.os    = $os
					AND    tbl_os.posto = $login_posto;";

			$res       = @pg_query($con, $sql);
			$msg_erro .= pg_last_error($con);

		}

		if ($login_fabrica <> 20) {

			$sql = "UPDATE	tbl_os SET
							obs                      = $obs,
							tecnico_nome             = $tecnico_nome,
							qtde_km                  = $qtde_km     ,
							qtde_hora                = $qtde_hora, ";

			if ($login_fabrica == 1) {//HD 669169
				$sql .= $sql_data_conserto;
			}

			$sql .= "		valores_adicionais       = $valores_adicionais,
							justificativa_adicionais = $justificativa_adicionais,
							qtde_visitas             = $qtde_visitas
					WHERE  tbl_os.os    = $os
					AND    tbl_os.posto = $login_posto;";

			$res = @pg_query ($con,$sql);
			$msg_erro .= pg_last_error($con);

		}

	}

	//ADICIONADO TIPO_OS 13  -  PARA OS_GEO
	if ($login_fabrica == 1 and ($compressor == 't' or $tipo_os == 13)) {

		for ($i = 0; $i < $qtde_visita; $i++) {

			$xos_visita               = trim($_POST['os_visita_'. $i]);
			$xdata                    = fnc_formata_data_pg(trim($_POST['visita_data_'. $i]));
			$xxdata                   = str_replace("'","",$xdata);
			$xhora_chegada_cliente    = trim($_POST['visita_hr_inicio_'. $i]);
			$xhora_saida_cliente      = trim($_POST['visita_hr_fim_'. $i]);
			$xkm_chegada_cliente      = trim($_POST['visita_km_'. $i]);
			$xkm_chegada_cliente      = str_replace (",",".",$xkm_chegada_cliente);
			$xqtde_produto_atendido   = trim($_POST['qtde_produto_atendido_'. $i]);
			$valores_adicionais       = trim($_POST['valores_adicionais_'. $i]);
			$justificativa_adicionais = trim($_POST['justificativa_adicionais_'. $i]);

			if ( $valores_adicionais > 0 && $login_fabrica == 1 ) {

				$exigeComprovante = true;

			}

			$xxkm_chegada_cliente = number_format($xkm_chegada_cliente,1,'.','');
			$xkm_chegada_cliente  = number_format($xkm_chegada_cliente,2,'.','');
			$km_conferencia       = number_format($_POST['km_conferencia_'.$i],1,'.','');

			if ($xxkm_chegada_cliente <> $km_conferencia and $xxkm_chegada_cliente > ($km_conferencia* 1.1) and $km_conferencia > 0) {

				$msg_erro = "Fizemos a verificação de deslocamento ida e volta (endereço do posto até o cliente) e encontramos ". str_replace (".",",",$km_conferencia) ."KM de deslocamento. Por isso faremos a correção para prosseguir com a conclusão da OS. Em caso de dúvida gentileza entrar em contato com o seu suporte.";

				$visita_km_erro = $km_conferencia * 1.1;

			}

			if (strlen($valores_adicionais) == 0) $valores_adicionais = "0";

			$valores_adicionais = str_replace(",",".",$valores_adicionais);
			if (strlen($justificativa_adicionais) > 0) $justificativa_adicionais = "'".$justificativa_adicionais."'";
			else                                       $justificativa_adicionais = "null";

			if ($tipo_os == 13) {

				$sql = "SELECT  count(*) as count_visita
						FROM    tbl_os_visita
						WHERE   tbl_os_visita.os_revenda= $os_numero";

				$res_vis = pg_query ($con,$sql) ;

				$count_visita= pg_fetch_result($res_vis,0,count_visita);

				if (strlen($count_visita) > 0 and $count_visita > 4) {
					$msg_erro .= "Quantidade de visitas maior que o permitido: $count_visita.<BR>";
				}

				if ($tipo_atendimento == 64 and $xkm_chegada_cliente > 0) {
					$msg_erro .= "Não é permitido a digitação de quilometragem para OS Geo Balcão.<BR>";
				} else if ($tipo_atendimento == 69 and $xkm_chegada_cliente > 100) {
					$msg_erro .= "Foi informado o tipo de atendimento OS GEO domicílio NA ÁREA DE ATUAÇÃO e para esse tipo de atendimento a quilometragem máxima permitida é de 100 Km.<BR>";
				}

				$sql = "SELECT  count(os_revenda_item) as qtde_itens_geo
						FROM    tbl_os_revenda_item
						WHERE   os_revenda= $os_numero ";

				$res = pg_query ($con,$sql) ;

				$qtde_itens_geo = pg_fetch_result($res, 0, 'qtde_itens_geo');

				if ($xqtde_produto_atendido > $qtde_itens_geo) {
					$msg_erro .= "Quantidade de produtos digitados está maior que a quantidade de produtos da OS.<BR>";
				}

			} else if ($xkm_chegada_cliente > 100) {
				$msg_erro .= "Quilometragem máxima permitida é de 100 Km.<BR>";
			}

			if ($xxdata < $data_abertura) {
				$msg_erro .= "Data de abertura é maior que a data da visita.<BR>";
			}

			if ($xxdata <> "null" and $xxdata > date('Y-m-d')) {
				$msg_erro .= "Data de visita futura (maior que a data de hoje).<BR> ";
			}

			# HD 165538
			if ($compressor == 't') {

				$hora_permitida = $xhora_saida_cliente - $xhora_chegada_cliente;

				if ($hora_permitida > 4) {

					$msg_erro = "De acordo com nossa engenharia o prazo para conserto (desmontagem e montagem) de um compressor desse modelo é de 2 a 4 horas. Sendo que, em serviços menos complexos o prazo é menor. Para os casos em que utilizar mais de 4 horas para conserto entre em contato com o seu suporte para avaliação da situação.";

				}
			}

			if (strlen($xos_visita) > 0) {
				$cond_os_visita = " AND os_visita< $xos_visita ";
			}

			if (strlen($xhora_chegada_cliente) > 0 and strlen($xhora_saida_cliente) > 0) {

				$xhora_chegada_cliente = "'$xxdata ".$xhora_chegada_cliente."'";
				$xhora_saida_cliente   = "'$xxdata ".$xhora_saida_cliente."'";

				$sql = " SELECT $xhora_chegada_cliente::timestamp > $xhora_saida_cliente::timestamp";
				$res = pg_query($con,$sql);

				if (pg_fetch_result($res,0,0) == 't') {
					$msg_erro .= "Hora de início é maior que a hora de fim na visita técnica.<BR> ";
				}

			} else {

				$xhora_chegada_cliente = "null";
				$xhora_saida_cliente   = "null";

			}

			/*hd:83010*/
			if ($tipo_os == 13 and strlen($xhora_chegada_cliente) > 0) {

				$sql = "SELECT 	os_visita
						FROM    tbl_os_visita
						WHERE   os_revenda = $os_numero
						AND 	hora_saida_cliente > $xhora_chegada_cliente
						$cond_os_visita ";

				$res_visita = pg_query ($con,$sql) ;

				if (pg_num_rows($res_visita) > 0) {
					$msg_erro .= "Horas de início é maior que a hora de fim na visita técnica.<BR> ";
				}

			}

			if (strlen($xqtde_produto_atendido) == 0) {
				$xqtde_produto_atendido = " 1 ";
			}

			//echo "$i data:$xxdata,inicio $xhora_chegada_cliente,fim $xhora_saida_cliente, km: $xkm_chegada_cliente os $xos_visita<BR>";

			if ($xdata <>'null' and (strlen($xkm_chegada_cliente) > 0) and (strlen($xos_visita) == 0) and (strlen($msg_erro) == 0)) {

				if ($tipo_os <> 13) {
					$campo_hora = ",hora_chegada_cliente , hora_saida_cliente";
					$valor_hora = ",$xhora_chegada_cliente ,$xhora_saida_cliente ";
				}

				if ($xkm_chegada_cliente == null) {
					$xkm_chegada_cliente = 0;
				}

				$sql = "INSERT INTO tbl_os_visita (
									$sql_aux_os          ,
									data                 ,
									km_chegada_cliente   ,
									hora_chegada_sede    ,
									hora_saida_sede      ,
									valor_adicional      ,
									justificativa_valor_adicional,
									qtde_produto_atendido
									$campo_hora
								) VALUES (
									$aux_os                ,
									$xdata                 ,
									$xkm_chegada_cliente   ,
									current_timestamp      ,
									current_timestamp      ,
									$valores_adicionais    ,
									$justificativa_adicionais,
									$xqtde_produto_atendido
									$valor_hora
								)";

				$res = @pg_query ($con,$sql);

			}

			if ($xdata <>'null' and (strlen($xkm_chegada_cliente) > 0) and (strlen($xos_visita) > 0 and strlen($msg_erro) == 0)) {

				if ($tipo_os <> 13) {
						$valor_hora = ",hora_chegada_cliente = $xhora_chegada_cliente ,
						hora_saida_cliente   = $xhora_saida_cliente";
				}

				$sql = "UPDATE tbl_os_visita set
								data                 = $xdata                 ,
								km_chegada_cliente   = $xkm_chegada_cliente   ,
								valor_adicional      = $valores_adicionais    ,
								justificativa_valor_adicional = $justificativa_adicionais,
								qtde_produto_atendido= $xqtde_produto_atendido
								$valor_hora
							WHERE $sql_aux_os = $aux_os
							AND   os_visita = $xos_visita";

				$res = pg_query($con,$sql);
				$msg_erro .= pg_last_error($con);

			}

			if ((strlen($xos_visita) > 0) and ($xxdata == "null")) {

				$sql = "DELETE FROM tbl_os_visita WHERE $sql_aux_os = $aux_os AND tbl_os_visita.os_visita = $xos_visita;";
				$res = pg_query($con,$sql);

				$msg_erro .= pg_last_error($con);

			}

		}

		/*hd: 83010*/
		if ($tipo_os == 13) {

			$sql = "SELECT  distinct km_chegada_cliente
					FROM    tbl_os_visita
					WHERE   os_revenda= $os_numero ";

			$res_visita = pg_query($con, $sql);

			if (pg_num_rows($res_visita) > 1) {
				$msg_erro .= "Não é permitido que cadastre km diferente para as visitas.<BR> ";
			}

		}

		//*coloquei 24-01*//
		$tecnico = trim($_POST['tecnico']);
		if (strlen ($tecnico) > 0) $tecnico = "'".$tecnico."'";
		else   $msg_erro .= "Relatório técnico obrigatório";

		if (strlen($msg_erro) == 0) {

			$sql = "UPDATE tbl_os_extra set
							valor_por_km = 0.65,
							valor_total_hora_tecnica = 0.4,
							tecnico    = $tecnico
					WHERE os=$os";

			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);

		}

	}

	if (strlen ($type) > 0) $type = "'".trim($_POST['type'])."'";
	else                    $type = 'null';

	if (strlen ($type) > 0) {

		$sql = "UPDATE tbl_os SET type = $type
				WHERE  tbl_os.os    = $os
				AND    tbl_os.posto = $login_posto;";

		$res = @pg_query ($con,$sql);
		$msg_erro .= pg_last_error($con);

	}

	if ($login_fabrica == 1) {

		$sql3 = "SELECT * FROM tbl_laudo_tecnico_os  WHERE fabrica = $login_fabrica AND os = $os and titulo !~'Pesquisa de satisfa'";
		$res3 = pg_query($con, $sql3);

		if (pg_num_rows($res3) == 0) {

			$sql = "SELECT produto, familia FROM tbl_produto JOIN tbl_os using(produto) WHERE os = $os";
			$res = pg_query($con,$sql);
			$laudo_produto = pg_fetch_result($res,0,produto);
			$laudo_familia = pg_fetch_result($res,0,familia);

			$sql = "SELECT *
						FROM tbl_laudo_tecnico
						WHERE tbl_laudo_tecnico.produto = $laudo_produto";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) == 0){
				$sql = "SELECT *
							FROM tbl_laudo_tecnico
							WHERE tbl_laudo_tecnico.familia = $laudo_familia";
				$res = pg_query($con,$sql);
			}
			if(pg_num_rows($res) > 0){
				for($i=0;$i<pg_num_rows($res);$i++){
					$laudo      = pg_fetch_result($res,$i,laudo_tecnico);
					$titulo     = pg_fetch_result($res,$i,titulo);
					$afirmativa = pg_fetch_result($res,$i,afirmativa);
					$observacao = pg_fetch_result($res,$i,observacao);

					if(strlen($msg_erro) == 0){
						if($afirmativa == 't'){
							$laudo_afirmativa = trim($_POST["afirmativa_$laudo"]);
							if(strlen($laudo_afirmativa) == 0) {
								$msg_erro = "Por favor, complete o Laudo Técnico.";
							}else{
								$laudo_afirmativa = "'".trim($_POST["afirmativa_$laudo"])."'";
							}
						}else{
							$laudo_afirmativa = 'null';
						}

						if ($observacao == 't') {

							$laudo_observacao = trim($_POST["observacao_$laudo"]);
							$laudo_observacao = str_replace("'","''",$laudo_observacao);

							if (strlen($laudo_observacao) == 0) {
								$msg_erro = "Por favor, complete o Laudo Técnico.";
							}

						} else {
							$laudo_observacao = '';
						}

						if (strlen($msg_erro) == 0) {

							$sql2 = "INSERT INTO tbl_laudo_tecnico_os(titulo        ,
																	os          ,
																	afirmativa  ,
																	observacao
														) VALUES (
																	'$titulo'          ,
																	'$os'              ,
																	$laudo_afirmativa,
																	'$laudo_observacao'
														)";

							$res2 = pg_query($con,$sql2);
							$msg_erro .= pg_last_error($con);

						}

					}

				}

			}

		}

	}

	$peca_negativa = array(); #HD 175171
	$latina_troca_peca = 'f';

	if($login_fabrica == 1){ //HD-3236684
		$sql_p_faturado = "SELECT tbl_posto_fabrica.pedido_faturado
							FROM tbl_posto_fabrica
							WHERE fabrica = $login_fabrica
							AND posto = $login_posto
							AND pedido_faturado IS TRUE ";
		$res_p_faturado = pg_query($con, $sql_p_faturado);

		if(pg_num_rows($res_p_faturado) > 0){
			$reposicao_estoque = "t";
		}

	}

	if (strlen ($msg_erro) == 0) {

		$qtde_item = $_POST['qtde_item'];
		$array_pecas_lancamento = [];

		for ($i = 0; $i < $qtde_item; $i++) {

			$xos_item        = $_POST['os_item_'        . $i];
			$xos_produto     = $_POST['os_produto_'     . $i];
			$xproduto        = $_POST['produto_'        . $i];
			$xserie          = $_POST['serie_'          . $i];
			$xposicao        = $_POST['posicao_'        . $i];
			$xpeca           = $_POST['peca_'           . $i];
			$xqtde           = $_POST['qtde_'           . $i];
			$xdefeito        = $_POST['defeito_'        . $i];
			$xservico        = $_POST['servico_'        . $i];
			$xpcausa_defeito = $_POST['pcausa_defeito_' . $i];
			$xadicional      = $_POST['adicional_peca_estoque_' . $i];
			$xadmin_peca      = $_POST["admin_peca_"     . $i]; //aqui

			if (strlen($xadmin_peca) == 0) $xadmin_peca ="null"; //aqui
			if ($xadmin_peca=="P")$xadmin_peca ="null"; //aqui

			$xproduto = str_replace ("." , "" , $xproduto);
			$xproduto = str_replace ("-" , "" , $xproduto);
			$xproduto = str_replace ("/" , "" , $xproduto);

			if ($login_fabrica == 1) $xproduto = str_replace ("," , "" , $xproduto); //HD 56874
			$xproduto = str_replace (" " , "" , $xproduto);

			$xpeca    = str_replace ("." , "" , $xpeca);
			$xpeca    = str_replace ("-" , "" , $xpeca);
			$xpeca    = str_replace ("/" , "" , $xpeca);
			$xpeca    = str_replace (" " , "" , $xpeca);

			if (strlen($xserie) == 0) $xserie = 'null';
			else                      $xserie = "'" . $xserie . "'";

			if (strlen($xposicao) == 0) $xposicao = 'null';
			else                        $xposicao = "'" . $xposicao . "'";

/*			if ($login_fabrica == 5 and strlen($causa_defeito) == 0)
				$msg_erro = "Selecione a causa do defeito";
			elseif ($login_fabrica <> 5 and strlen($causa_defeito) == 0)
				$causa_defeito = 'null';*/

			//ADICIONAL DE VALOR DE PEÇA PARA A COLOMBIA

			if ($login_fabrica == 20 AND $login_pais == 'CO') {

				echo $adicional[$i];

				if (strlen(trim($xadicional)) > 0) $xadicional = "'$xadicional'";
				else                               $xadicional = "'f'";

			} else
				$xadicional = 'null';

			if ($login_fabrica == 20) {

				//$admin_peca = "null";
				$xdefeito   = "141";  //Danificado
				$xservico   = "258";  //Troca de Peça

			}

			if ($login_fabrica == 1 && !empty($xpeca)) {
                if (in_array($xpeca, $array_pecas_lancamento)) {
                    $msg_erro .= "A peça $xpeca não pode ser lançada mais de uma vez, favor deixar somente uma e acrescentar na quantidade .";
                }

                $array_pecas_lancamento[] = $xpeca; 
            }

			if (strlen ($xos_produto) > 0 AND strlen($xpeca) == 0) {

				$sql = "DELETE FROM tbl_os_produto
						WHERE  tbl_os_produto.os         = $os
						AND    tbl_os_produto.os_produto = $xos_produto";

				#HD 15489
				$sql = "UPDATE tbl_os_produto SET
							os = 4836000
						WHERE os         = $os
						AND   os_produto = $xos_produto";

				$res = @pg_query ($con,$sql);
				$msg_erro .= pg_last_error($con);

			} else {

				if ($login_fabrica == 3 && strlen($xpeca) > 0) {

					$sqlX = "SELECT referencia, TO_CHAR (previsao_entrega,'DD/MM/YYYY') AS previsao
							 FROM tbl_peca
							 WHERE referencia_pesquisa = UPPER('$xpeca')
							 AND   fabrica = $login_fabrica
							 AND   previsao_entrega > date(current_date + INTERVAL '20 days');";

					$resX = pg_query($con,$sqlX);

					if (pg_num_rows($resX) > 0) {

						$peca_previsao = pg_fetch_result($resX, 0, 'referencia');
						$previsao      = pg_fetch_result($resX, 0, 'previsao');

						$msg_previsao  = "O pedido da peça $peca_previsao foi efetivado. A previsão de disponibilidade desta peça será em $previsao. A fábrica tomará as medidas necessárias par o atendimento ao consumidor.";

					}

				}

				if (strlen($xpeca) > 0 and strlen($msg_erro) == 0) {

					if ($login_fabrica == 1) {
						$lancouPeca = true;
					}

					$xpeca = strtoupper($xpeca);

					if (strlen ($xqtde) == 0) $xqtde = "1";

					if ($login_fabrica == 1 && intval($xqtde) == 0) {
						$msg_erro .= " O item $xpeca está sem quantidade, por gentileza informe a quantidade para este item. ";
					}

					if ($login_fabrica == 1 && intval($xqtde) > 0 && strlen($msg_erro) == 0) {
						$sql_ativo = "SELECT ativo
										FROM   tbl_peca
										WHERE  UPPER(tbl_peca.referencia_pesquisa) = UPPER('$xpeca')
										AND    tbl_peca.fabrica = $login_fabrica;";

						$res_ativo = pg_query($con, $sql_ativo);
						$ativo_p = pg_fetch_result($res_ativo, 0, ativo);

						if ($ativo_p == 'f') {
							if (!pecaInativaBlack($xpeca,$xqtde)) {
								$msg_erro .= " O item $xpeca não contém estoque ou o estoque é inferior o solicitado! ";
							}

						}
					}

					if (strlen ($xproduto) == 0) {

						$sql = "SELECT tbl_os.produto
								FROM   tbl_os
								WHERE  tbl_os.os      = $os
								AND    tbl_os.fabrica = $login_fabrica;";

						$res = pg_query ($con,$sql);

						if (pg_num_rows($res) > 0) {

							$xproduto = pg_fetch_result ($res, 0, 0);

						}

					} else {

						$sql = "SELECT tbl_produto.produto
								FROM   tbl_produto
								JOIN   tbl_linha USING (linha)
								WHERE  tbl_produto.referencia_pesquisa = '$xproduto'
								AND    tbl_linha.fabrica = $login_fabrica";

						$res = pg_query ($con,$sql);

						if (pg_num_rows ($res) == 0) {

							$msg_erro .= "Produto $xproduto não cadastrado";
							$linha_erro = $i;

						} else {

							$xproduto = pg_fetch_result($res, 0, 'produto');

						}

					}

					if (strlen ($msg_erro) == 0) {

						if (strlen($xos_produto) == 0) {

							$sql = "INSERT INTO tbl_os_produto (
										os     ,
										produto,
										serie
									)VALUES(
										$os     ,
										$xproduto,
										$xserie
								);";
							$res = @pg_query ($con,$sql);
							$msg_erro .= pg_last_error($con);

							$res = pg_query ($con,"SELECT CURRVAL ('seq_os_produto')");
							$xos_produto  = pg_fetch_result ($res,0,0);

						} else {

							$sql = "UPDATE tbl_os_produto SET
										os      = $os      ,
										produto = $xproduto,
										serie   = $xserie
									WHERE os_produto = $xos_produto;";

							$res = @pg_query($con,$sql);
							$msg_erro .= pg_last_error($con);

						}

						if (strlen ($msg_erro) > 0) {
							break ;
						} else {

							$xpeca = strtoupper ($xpeca);

							if (strlen($xpeca) > 0) {

								$sql = "SELECT tbl_peca.*
										FROM   tbl_peca
										WHERE  UPPER(tbl_peca.referencia_pesquisa) = UPPER('$xpeca')
										AND    tbl_peca.fabrica = $login_fabrica;";

								$res = pg_query($con, $sql);

								if (pg_num_rows($res) == 0) {

									$msg_erro   .= "Peça $xpeca não cadastrada";
									$linha_erro  = $i;

								} else {

									$xpeca                    = pg_fetch_result($res, 0, 'peca');
									$intervencao_fabrica_peca = pg_fetch_result($res, 0, 'retorna_conserto');
									$troca_obrigatoria_peca   = pg_fetch_result($res, 0, 'troca_obrigatoria');
									$peca_critica             = pg_fetch_result($res, 0, 'peca_critica');
									$produto_acabado          = pg_fetch_result($res, 0, 'produto_acabado');

								}

								/* Black nao permite produto na OS - HD 21946 - Fabio*/
								if ($login_fabrica == 1 AND $produto_acabado == 't') {
									$msg_erro .= "$xpeca é um produto. Não é permitido lançar produto na OS.";
								}

								if (strlen($xdefeito) == 0) $msg_erro .= "Informar o defeito da peça"; #$defeito = "null";
								if (strlen($xservico) == 0) $msg_erro .= "Informar o serviço realizado"; #$servico = "null";

								if ($login_fabrica == 1 && strlen($msg_erro) == 0) {
									$aux_sql = "SELECT garantia_peca FROM tbl_lista_basica WHERE fabrica = $login_fabrica AND produto = $xproduto AND peca = $xpeca LIMIT 1";
									$aux_res = pg_query($con, $aux_sql);
									$garantia_peca = (int) pg_fetch_result($aux_res, 0, 0);

									if (!empty($garantia_peca)) {
										$aux_sql = "SELECT data_abertura FROM tbl_os WHERE os = $os LIMIT 1";
										$aux_res = pg_query($con, $aux_sql);
										$data_ab = pg_fetch_result($aux_res, 0, 0);

										$aux_sql = "SELECT data_nf + CAST('$garantia_peca months' AS interval) FROM tbl_os WHERE os = $os LIMIT 1";
										$aux_res = pg_query($con, $aux_sql);
										$prazo_garantia = pg_fetch_result($aux_res, 0, 0);

										if ($data_ab > $prazo_garantia) {
											$aux_sql  = "SELECT referencia || ' - ' || descricao AS peca FROM tbl_peca WHERE fabrica = $login_fabrica AND peca = $xpeca";
											$aux_res  = pg_query($con, $aux_sql);
											$aux_peca = pg_fetch_result($aux_res, 0, 'peca');
											$msg_erro .= "<br>A peça \"$aux_peca\" está fora de garantia.<br>";
										}
									}
								}

								if ($login_fabrica == 14) {//chamado = 1636 as vezes vem a referencia outras o codigo do banco para a Intelbras.

									$sqlp = "SELECT tbl_peca.*
											FROM   tbl_peca
											WHERE  (UPPER(tbl_peca.referencia_pesquisa) = UPPER('$xpeca') OR tbl_peca.peca = '$xpeca')
											AND    tbl_peca.fabrica = $login_fabrica
											AND    UPPER(tbl_peca.descricao) ilike '%PLACA%';";

									$resp = pg_query($con,$sqlp);

									if (pg_num_rows($resp) > 0) $encontrou = 't';
									else                        $encontrou = 'f';

									if ($encontrou == "t" AND $xservico == 82) {
										$msg_erro = "Para a peça escolhida não pode haver troca de componente.";
									}

									if ($encontrou == "f" AND $xservico == 83) {
										$msg_erro = "Para a peça escolhida não pode haver troca de placa.";
									}

								}

								//if ($login_fabrica == 5 and strlen($xcausa_defeito) == 0) $msg_erro = "Selecione a causa do defeito.";
								//elseif(strlen($xcausa_defeito) == 0)					$xcausa_defeito = 'null';

								if (strlen($xpcausa_defeito) == 0) $xpcausa_defeito = 'null';

								if (strlen($xservico) > 0) {

									$sqlpp = "SELECT peca_estoque FROM tbl_servico_realizado WHERE servico_realizado = $xservico";
									$respp = pg_query($con,$sqlpp);

									if (pg_num_rows($respp) > 0) {
										$peca_estoque = pg_result($respp, 0, 'peca_estoque');
									}

								}

								if ($login_fabrica == 1 and $peca_estoque == 't') { // HD 175171

									$sqlr = "SELECT posto
											FROM 	tbl_posto_fabrica
											WHERE 	posto = $login_posto
											AND   	fabrica = $login_fabrica
											AND   	reembolso_peca_estoque IS NOT TRUE
											AND   	pedido_faturado IS TRUE ";

									$resr = pg_query($con, $sqlr);

									if (pg_num_rows($resr) > 0) {

										$sqlq = "SELECT qtde
												FROM 	tbl_estoque_posto
												WHERE 	tbl_estoque_posto.peca = $xpeca
												AND   	tbl_estoque_posto.posto = $login_posto
												AND   	tbl_estoque_posto.fabrica = $login_fabrica";

										$resq = pg_query($con, $sqlq);
										$qtde_estoque = (pg_num_rows($resq) > 0) ? pg_fetch_result($resq,0,'qtde') : 0;

                                        if (strlen($os) > 0) {
                                            $cond_os = "AND os <> $os";
                                        }

										$sqlq = "SELECT sum(qtde) as qtde
												FROM 	tbl_os
												JOIN 	tbl_os_produto USING(os)
												JOIN 	tbl_os_item USING(os_produto)
												JOIN 	tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
												WHERE 	tbl_os_item.peca = $xpeca
												AND  	tbl_os.os_fechada IS FALSE
												AND   	tbl_os.posto   = $login_posto
												AND   	tbl_os.fabrica = $login_fabrica
												AND 	tbl_servico_realizado.peca_estoque IS TRUE
												{$cond_os} ";

										$resq    = pg_query($con, $sqlq);
										$qtde_os = (pg_num_rows($resq) > 0) ? pg_fetch_result($resq,0,'qtde') : 0;
										if(empty($qtde_os)) $qtde_os = 0 ;

										$qtde_os += $xqtde;

										if ($qtde_estoque < $qtde_os or ($qtde_estoque == 0 and $qtde_os == 0)) {

											$sqle = "SELECT referencia FROM tbl_peca WHERE peca = $xpeca";
											$rese = pg_query($con,$sqle);

											if (pg_num_rows($rese) > 0) {
												$referencia_negativa = pg_fetch_result($rese,0,'referencia');
												array_push($peca_negativa,$referencia_negativa);
											}

										}

									}

								}

								if (strlen($msg_erro) == 0) {

									if (strlen($xos_item) == 0) {

										/*######## TAKASHI 06-12 ##############
										# CONFORME HD 853 -FABRICIO BRITANIA  #
										# Fica proibido o lançamento de peças #
										# em OS ha mais de 30 dias abertas.   #
										#####################################*/

										if ($login_fabrica == 3) {

											$sql_bloqueio = "SELECT SUM(current_date - data_abertura)as bloqueio FROM tbl_os WHERE os=$os";
											$res_bloqueio = pg_query($con, $sql_bloqueio);

											$dias_bloqueio = pg_fetch_result($res_bloqueio,0,bloqueio);

											if ($dias_bloqueio > 30 and $xservico == 20) {

												$sql = "SELECT  tbl_os.sua_os,
																to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura
														FROM    tbl_os
														WHERE   tbl_os.os = $os";

												$res = pg_query ($con,$sql);

												$sua_os        = pg_fetch_result($res, 0, 'sua_os');
												$data_abertura = pg_fetch_result($res, 0, 'data_abertura');

												$msg_bloqueio = "<center><font size='2' face='verdana'>OS ($sua_os) com data de abertura ($data_abertura) </font><font size='2' face='verdana' color='#000000'>superior a 30 dias</font><font size='2' face='verdana'>: para inserir peças, favor enviar e-mail para <B>assistenciatecnica@britania.com.br</b> informando o número da OS, código da peça e justificativa.</font></center>";

												$msg_erro .= " ";

											}

										}

										if($login_fabrica ==  1 AND $reposicao_estoque == 't'){ //HD-3236684
											$campo_insert = ', peca_reposicao_estoque';
											$value_insert = ", 't'";
										}

										if (strlen($msg_bloqueio) == 0) {

												$sql = "INSERT INTO tbl_os_item (
														os_produto        ,
														posicao           ,
														peca              ,
														qtde              ,
														defeito           ,
														causa_defeito     ,
														servico_realizado ,
														admin             ,
														liberacao_pedido  , 
														adicional_peca_estoque
														$campo_insert
													)VALUES(
														$xos_produto    ,
														$xposicao       ,
														$xpeca          ,
														$xqtde          ,
														$xdefeito       ,
														$xpcausa_defeito,
														$xservico       ,
														$xadmin_peca    ,
														'$gera_pedido'  , 
														$xadicional
														$value_insert
												) RETURNING os_item;";

											$res       = @pg_query($con, $sql);
											$msg_erro .= pg_last_error($con);
											$os_item_verificacao = pg_fetch_result($res,0,os_item);


											auditorLog($os,$antes,array(
                                                "os_produto" => $xos_produto,
                                                "peca" => $xpeca,
                                                "posicao" => $xposicao,
                                                "qtde" => $xqtde,
                                                "defeito" => $xdefeito,
                                                "causa_defeito" => $xpcausa_defeito,
                                                "servico_realizado" => $xservico,
                                                "admin" => $xadmin_peca,
                                                "adicional_peca_estoque" => $xadicional,

                                            ), 'tbl_os_item', '/os_item.php', "INSERT");

										}

										if($login_fabrica == 20 && $login_pais == "BR"){
                                            /**
                                             * HD-2400010 - Verificação de peça se o posto, ou a região
                                             * onde o posto se encontra deverá, obrigatoriamente,
                                             * devolver a peça à fábrica
                                             */

                                            $sqlVerifica = "
                                                SELECT  tbl_lgr_peca_solicitacao.peca
                                                FROM    tbl_lgr_peca_solicitacao
                                                WHERE   tbl_lgr_peca_solicitacao.fabrica    = $login_fabrica
                                                AND     tbl_lgr_peca_solicitacao.peca       = $xpeca
                                                AND     tbl_lgr_peca_solicitacao.concluida  IS NOT TRUE
                                            ";
                                            $resVerifica = pg_query($con,$sqlVerifica);

                                            if(pg_num_rows($resVerifica) > 0){
                                                /**
                                                 * - Uma vez feita a verificação da existência
                                                 * da obrigatoriedade da peça para devolução,
                                                 * deve-se verificar, seguindo uma hierarquia de
                                                 * região, se o posto deverá devolver a peça para a
                                                 * fábrica
                                                 */
                                                $achou = "";

                                                $sqlPosto = "
                                                    SELECT  tbl_lgr_peca_solicitacao.peca                   ,
                                                            tbl_lgr_peca_solicitacao.lgr_peca_solicitacao   ,
                                                            tbl_lgr_peca_solicitacao.qtde
                                                    FROM    tbl_lgr_peca_solicitacao
                                                    WHERE   tbl_lgr_peca_solicitacao.fabrica    = $login_fabrica
                                                    AND     tbl_lgr_peca_solicitacao.peca       = $xpeca
                                                    AND     tbl_lgr_peca_solicitacao.concluida  IS NOT TRUE
                                                    AND     tbl_lgr_peca_solicitacao.posto      = $login_posto
                                                ";
                                                $resPosto = pg_query($con,$sqlPosto);
                                                if(pg_num_rows($resPosto) > 0){
                                                    $achou = 1;
                                                    $peca_solicitacao       = pg_fetch_result($resPosto,0,lgr_peca_solicitacao);
                                                    $peca_solicitacao_qtde  = pg_fetch_result($resPosto,0,qtde);
                                                }else{
                                                    $sqlCidade = "
                                                        SELECT  tbl_lgr_peca_solicitacao.peca                   ,
                                                                tbl_lgr_peca_solicitacao.lgr_peca_solicitacao   ,
                                                                tbl_lgr_peca_solicitacao.qtde
                                                        FROM    tbl_lgr_peca_solicitacao
                                                        WHERE   tbl_lgr_peca_solicitacao.fabrica    = $login_fabrica
                                                        AND     tbl_lgr_peca_solicitacao.peca       = $xpeca
                                                        AND     tbl_lgr_peca_solicitacao.concluida  IS NOT TRUE
                                                        AND     tbl_lgr_peca_solicitacao.cod_ibge   = (
                                                                    SELECT  tbl_posto_fabrica.cod_ibge
                                                                    FROM    tbl_posto_fabrica
                                                                    WHERE   tbl_posto_fabrica.posto     = $login_posto
                                                                    AND     tbl_posto_fabrica.fabrica   = $login_fabrica
                                                                )
                                                        AND     tbl_lgr_peca_solicitacao.posto      IS NULL
                                                    ";
                                                    $resCidade = pg_query($con,$sqlCidade);
                                                    if(pg_num_rows($resCidade) > 0){
                                                        $achou = 1;
                                                        $peca_solicitacao       = pg_fetch_result($resCidade,0,lgr_peca_solicitacao);
                                                        $peca_solicitacao_qtde  = pg_fetch_result($resCidade,0,qtde);
                                                    }else{
                                                        $sqlEstado = "
                                                            SELECT  tbl_lgr_peca_solicitacao.peca                   ,
                                                                    tbl_lgr_peca_solicitacao.lgr_peca_solicitacao   ,
                                                                    tbl_lgr_peca_solicitacao.qtde
                                                            FROM    tbl_lgr_peca_solicitacao
                                                            WHERE   tbl_lgr_peca_solicitacao.fabrica    = $login_fabrica
                                                            AND     tbl_lgr_peca_solicitacao.peca       = $xpeca
                                                            AND     tbl_lgr_peca_solicitacao.concluida  IS NOT TRUE
                                                            AND     tbl_lgr_peca_solicitacao.estado = (
                                                                        SELECT  tbl_posto.estado
                                                                        FROM    tbl_posto
                                                                        WHERE   tbl_posto.posto = $login_posto
                                                                    )
                                                            AND     tbl_lgr_peca_solicitacao.cod_ibge   IS NULL
                                                            AND     tbl_lgr_peca_solicitacao.posto      IS NULL
                                                        ";
                                                        $resEstado = pg_query($con,$sqlEstado);
                                                        if(pg_num_rows($resEstado) > 0){
                                                            $achou = 1;

                                                            $peca_solicitacao       = pg_fetch_result($resEstado,0,lgr_peca_solicitacao);
                                                            $peca_solicitacao_qtde  = pg_fetch_result($resEstado,0,qtde);

                                                        }
                                                    }
                                                }

                                                if($achou == 1){
                                                    /**
                                                     * - A Segunda Verificação consiste em provar
                                                     * que a mesma peça já não tenha entrado na mesma OS em outra ocasião
                                                     */

                                                    $sqlVerII = "
                                                        SELECT  tbl_lgr_peca_devolucao.peca
                                                        FROM    tbl_lgr_peca_devolucao
                                                        WHERE   tbl_lgr_peca_devolucao.os   = $os
                                                        AND     tbl_lgr_peca_devolucao.peca = $xpeca
                                                    ";
                                                    $resVerII = pg_query($con,$sqlVerII);
                                                    if(pg_num_rows($resVerII) == 0){
                                                        /**
                                                         * - Feitas as verificações, será feita:
                                                         *
                                                         * 1 - A gravação da devolução da peça
                                                         * 2 - A dedudução da quantidade na solicitação
                                                         *
                                                         * Extra: Se a quantidade for a zero, ou negativar,
                                                         * será dada como concluída a solicitação da peça. Sendo
                                                         * que, se negativar, só será dada o retorno das peças até
                                                         * o zeramento.
                                                         */

                                                        $qtde_gravar = $peca_solicitacao_qtde - $xqtde;

                                                        if($qtde_gravar <= 0){
                                                            $sqlUp = "
                                                                UPDATE  tbl_lgr_peca_solicitacao
                                                                SET     qtde = 0,
                                                                        concluida = TRUE
                                                                WHERE   lgr_peca_solicitacao = $peca_solicitacao
                                                            ";
                                                        }else{
                                                            $sqlUp = "
                                                                UPDATE  tbl_lgr_peca_solicitacao
                                                                SET     qtde = $qtde_gravar
                                                                WHERE   lgr_peca_solicitacao = $peca_solicitacao;
                                                            ";
                                                        }
                                                        $resUp = pg_query($con,$sqlUp);
                                                        $msg_erro .= pg_last_error($con);

                                                        $sqlIns = "
                                                            INSERT INTO  tbl_lgr_peca_devolucao (
                                                                lgr_peca_solicitacao,
                                                                posto               ,
                                                                peca                ,
                                                                qtde                ,
                                                                os                  ,
                                                                os_item
                                                            ) VALUES (
                                                                $peca_solicitacao   ,
                                                                $login_posto        ,
                                                                $xpeca              ,
                                                                $xqtde              ,
                                                                $os                 ,
                                                                $os_item_verificacao
                                                            );
                                                        ";
                                                        $resIns = pg_query($con,$sqlIns);
                                                        $msg_erro .= pg_last_error($con);

                                                        /**
                                                         * @TODO Verificar como enviar a mensagem para o posto que a peça é de devolução obrigatória
                                                         */

                                                    }
                                                }
                                            }
										}

									} else {
										//AUDITOR
										$sqls = "SELECT os_produto, posicao,peca, qtde,defeito, causa_defeito, servico_realizado, admin, adicional_peca_estoque
												 FROM tbl_os_item WHERE os_item = $xos_item";
										$res = @pg_query ($con,$sqls);

                                        $res = pg_fetch_all($res);
                                        $antes = $res[0];
                                        //--------

										$sql = "UPDATE tbl_os_item SET
													os_produto        = $xos_produto    ,
													posicao           = $xposicao       ,
													peca              = $xpeca          ,
													qtde              = $xqtde          ,
													defeito           = $xdefeito       ,
													causa_defeito     = $xpcausa_defeito,
													servico_realizado = $xservico       ,
													admin             = $xadmin_peca     ,
													adicional_peca_estoque = $xadicional
												WHERE os_item = $xos_item;";

										$res       = @pg_query($con, $sql);
										$msg_erro .= pg_last_error($con);



										auditorLog($os,$antes,array(
                                                "os_produto" => $xos_produto,
                                                "peca" => $xpeca,
                                                "posicao" => $xposicao,
                                                "qtde" => $xqtde,
                                                "defeito" => $xdefeito,
                                                "causa_defeito" => $xpcausa_defeito,
                                                "servico_realizado" => $xservico,
                                                "admin" => $xadmin_peca,

                                            ), 'tbl_os_item', '/os_item.php', "UPDATE");

									}

									if ($login_fabrica == 15) {

										$sql_t = "SELECT troca_de_peca
													FROM tbl_servico_realizado
												   WHERE fabrica = $login_fabrica
												     AND servico_realizado = $xservico
													 AND troca_de_peca is true;";

										$res_t             = pg_query($con, $sql_t);
										$troca_peca_latina = @pg_fetch_result($res_t, 0, 0);

										if (pg_num_rows($res_t) > 0) {
											$latina_troca_peca = 't';
										}

									}

									// INTERVENÇÃO (SUPRIMENTOS)
									if ($login_fabrica == 1 AND ($xservico == "62" OR $xservico == "90") AND $peca_critica == 't') {

										$os_peca_critica = 't';
										$gravou_peca     = "sim";

									}

									if (strlen ($msg_erro) > 0) {

										break;

									}
								}
							}
						}
					}
				}
			}
			 // Array para Inserir na tbl_auditoria_os, posto aqui para pegar o ID das peças.
            // Foi feito uma CAST int, porque na hora de dar um encode estava adicionando aspas nos valores.  
            if (!empty($xpeca) && $xpeca != 0) {
            	$array_campos_adicionais[] = (int)$xpeca;
        	}
		}

		if (count($peca_negativa) > 0) {

			$lista_peca_negativa = implode($peca_negativa,",");
			$msg_erro = "Para a peça $lista_peca_negativa o seu saldo está negativo, ou seja, a quantidade de OS's abertas com a peça é maior que a quantidade de peças compradas com o fabricante ou não consta aquisição da peça em nosso sistema. Solicitamos que verifique se a peça informada está correta e até mesmo o modelo do produto, em caso de erro faça a exclusão da OS ou alteração do item. Se houver alguma dúvida ou divergência sobre essa informação entre em contato com o seu suporte.";

		}

		// HD 208462:
		// 1. Se a solução selecionada exigir troca de peça, exigir os_item com serviço que gere troca de peça
		// Totas as fábricas, autorizado por Samuel
		// 2. Se a solução selecionada não exigir troca de peça, não deixar cadastrar os_item com serviço
		// que gere troca de peça. Todas as fábricas, autorizado por Samuel
		if ($login_fabrica != 20 && $login_fabrica != 8) {

			if (strlen($x_solucao_os) > 0 && $x_solucao_os != 'null') {

				$sql_t = "SELECT troca_peca FROM tbl_solucao WHERE solucao = $x_solucao_os AND fabrica = $login_fabrica AND troca_peca IS TRUE;";
				$res_t = pg_query($con,$sql_t);

				if (pg_num_rows($res_t) > 0) {

					$sql_t = "SELECT COUNT(*)
									FROM tbl_os_item
									JOIN tbl_os_produto USING(os_produto)
									JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
								WHERE os = $os
								AND tbl_servico_realizado.troca_de_peca IS TRUE";

					$res_t = pg_query($con,$sql_t);

					if (pg_fetch_result($res_t,0,0) == 0) {

						$msg_erro .= "Para a solução escolhida, é necessário especificar a peça a ser trocada.";

					}

				} else {

					$sql_t = "SELECT COUNT(*)
									FROM tbl_os_item
									JOIN tbl_os_produto USING(os_produto)
									JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
								WHERE os = $os
								AND tbl_servico_realizado.troca_de_peca IS TRUE";

					$res_t = pg_query($con,$sql_t);

					if (pg_fetch_result($res_t,0,0) > 0) {
						$msg_erro .= "Para a solução escolhida, não pode existir serviço com troca de peça.";
					}

				}

			}

		}

		if ($login_fabrica == 15 AND $latina_troca_peca == 'f') {

			$sql_t = "SELECT troca_de_peca
			 			FROM tbl_servico_realizado
					   WHERE fabrica           = $login_fabrica
					     AND servico_realizado = $solucao_os
						 AND troca_de_peca     IS TRUE;";

			$res_t = @pg_query($con, $sql_t);

			if (pg_num_rows($res_t) > 0) {
				$msg_erro = "Especificar a peça que foi feita a troca. O serviço deve ser troca de peça.";
			}

		}

		//IGOR HD REABERTO 1754 Quando solução for TESTE ou RESET,
		//NÃO pode existir peças e qd for outras soluções é obrigatório a digitação de peças
		if ($login_fabrica == 14) {

			$sql = "SELECT	os_item
					FROM	tbl_os_item
					JOIN	tbl_os_produto on tbl_os_produto.os_produto = tbl_os_item.os_produto
					WHERE	os = $os";

			$res_solucao = @pg_query($con, $sql);

			if (strlen(pg_last_error($con)) > 0) {

				$msg_erro .= pg_last_error($con);

			} else {

				//solucao_os >> TESTE=79 ou RESET PROGRAMAÇÃO=81
				if ($solucao_os == 81 or $solucao_os == 79) {

					if (pg_num_rows($res_solucao) > 0) $msg_erro .= "Para essa Solução não existe peça ou subconjunto cadastrados!";

				} else {

					if ($solucao_os != 75) {

						if (pg_num_rows($res_solucao) == 0) $msg_erro .= "É obrigatório a digitação de peças!";

					}

				}

			}

			//IGOR HD 1848 Quando solução for troca de placa, deve existir pelo menos 1 serviço realizado como: troca de placa
			if ($solucao_os == 83) {//solucao_os >> troca de placa

				$sql = "SELECT os_item
						FROM tbl_os_item
						JOIN tbl_os_produto on tbl_os_produto.os_produto = tbl_os_item.os_produto
						WHERE os = $os and servico_realizado = 83  ";

				$res_solucao = @pg_query($con,$sql);

				if (strlen(pg_last_error($con)) == 0) {

					if (pg_num_rows($res_solucao) == 0) $msg_erro .= "O Serviço realizado deve ser troca de placa!";

				}

			}

			//IGOR HD 1848 Quando solução for troca de componente, nao pode existir o serviço troca de componente
			if ($solucao_os == 82) {//solucao_os >> troca de componente

				$sql = "SELECT	os_item
						FROM 	tbl_os_item
						JOIN	tbl_os_produto on tbl_os_produto.os_produto = tbl_os_item.os_produto
						WHERE	os = $os and servico_realizado = 83 ";

				$res_solucao = @pg_query($con, $sql);

				if (strlen(pg_last_error($con)) == 0) {

					if (pg_num_rows($res_solucao) > 0)
						$msg_erro .= "O Serviço realizado deve ser troca de componente!";
				}

			}

			//IGOR HD 1758 Quando solução for "atualização de software", deve existir no máximo 1 peça com o serviço sendo "atualização de software"
			/*if($solucao_os == 75 ){

				$sql = "
				SELECT os_item, servico_realizado
				FROM tbl_os_item
				JOIN tbl_os_produto on tbl_os_produto.os_produto = tbl_os_item.os_produto
				WHERE os = $os";

				$res_solucao = @pg_query($con,$sql);
				if(strlen(pg_last_error($con))==0){
					if(pg_num_rows($res_solucao) == 1){
						if(pg_fetch_result($res_solucao,0,servico_realizado) <> 75)
							$msg_erro .= "O Serviço realizado deve ser Atualização de Software!";

					}else{
						$msg_erro .= "É obrigatório inserir uma placa ou peça e selecionar o Serviço realizado: Atualização de Software!";
					}
				}else{
					$msg_erro .= pg_last_error($con);
				}
			}*/
		}

	}

	#Rotina da Intervenção
	if (strlen ($msg_erro) == 0 and $login_fabrica == 1 AND $gravou_peca == "sim") {

		if ($os_peca_critica == 't') {

			if ($login_posto == 6359) {

				$sql_intervencao = "SELECT *
									FROM  tbl_os_status
									WHERE os=$os
									AND status_os IN (87,88)
									ORDER BY data DESC LIMIT 1";

				$res_intervencao = pg_query($con,$sql_intervencao);
				$sql = "INSERT INTO tbl_os_status	(os,status_os,data,observacao)
						VALUES						($os,87,current_timestamp,'OS com intervenção de suprimentos')";

				if (pg_num_rows($res_intervencao)== 0) {

					$res = @pg_query($con, $sql);
					$msg_erro .= pg_last_error($con);

				} else {

					$status_os = pg_fetch_result($res_intervencao,0,status_os);

					if ($status_os != 87) {

						$res = @pg_query($con, $sql);
						$msg_erro .= pg_last_error($con);

					}

				}

				// envia email teste para avisar
				/* HD 5876 */
				$sql = "SELECT tbl_os.sua_os, codigo_posto, tbl_posto_fabrica.contato_estado
						FROM  tbl_os
						JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
						WHERE tbl_os.os      = $os
						AND   tbl_os.posto   = $login_posto
						AND   tbl_os.fabrica = $login_fabrica";

				$res_mail = pg_query($con,$sql);

				if (pg_num_rows($res_mail) > 0) {

					$sua_os         = pg_fetch_result($res_mail,0,sua_os);
					$codigo_posto   = pg_fetch_result($res_mail,0,codigo_posto);
					$contato_estado = pg_fetch_result($res_mail,0,contato_estado);

					$email_destino = 'acamilo@blackedecker.com.br, fabio@telecontrol.com.br';
					$email_origem  = 'helpdesk@telecontrol.com.br';

					/* Envia email para Anderson */
					if ($contato_estado == "RS" OR
						$contato_estado == "SC" OR
						$contato_estado == "PR" OR
						$contato_estado == "MS" OR
						$contato_estado == "MT" OR
						$contato_estado == "GO" OR
						$contato_estado == "DF" ) {
							$email_destino = 'acamilo@blackedecker.com.br';
					}
					/* Envia email para Fernanda */
					if ($contato_estado == "SP" OR
						$contato_estado == "RJ" OR
						$contato_estado == "ES" OR
						$contato_estado == "MG" ) {
							$email_destino = 'fernanda_silva@blackedecker.com.br';
					}
					/* Envia email para José Reinaldo */
					if ($contato_estado == "BA" OR
						$contato_estado == "SE" OR
						$contato_estado == "AL" OR
						$contato_estado == "PE" OR
						$contato_estado == "PB" OR
						$contato_estado == "RN" OR
						$contato_estado == "CE" OR
						$contato_estado == "MA" OR
						$contato_estado == "PI" OR
						$contato_estado == "PA" OR
						$contato_estado == "AP" OR
						$contato_estado == "RR" OR
						$contato_estado == "AM" OR
						$contato_estado == "AC" OR
						$contato_estado == "RO" OR
						$contato_estado == "TO" ) {
							$email_destino = 'jreinaldo@blackedecker.com.br';
					}

					$assunto       = "OS entrou em Intervenção";
					$corpo ="Prezado, \n\n A OS $codigo_posto$sua_os do posto $codigo_posto entrou em e aguarda uma análise para liberação.\n\n\nSuporte Telecontrol";
					@mail($email_destino, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), "From: ".$email_origem);

				}

			}

		}

	}

	if (strlen ($msg_erro) == 0) {

        if ($login_fabrica == 20 AND $tipo_atendimento == 12) {
            explodirOSConsumidor($os);
        }

        $sql = "SELECT fn_valida_os_item($os, $login_fabrica)";
        $res      = @pg_query ($con,$sql);
        $msg_erro .= pg_last_error($con);
	}
    if ($login_fabrica == 1 && $tipo_atendimento == 334 && $msg_erro == "") {
    	$sql_peca = "SELECT count(os_item) AS qtde_itens_os
					 FROM tbl_os_item
					 JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
					 WHERE os = $os";
		$res = @pg_query ($con, $sql_peca);
		$qtde_itens_os_p = pg_fetch_result($res, 0, 'qtde_itens_os');

		if(!empty($qtde_itens_os_p)){

	        $sqlVerAud = "
	            SELECT  COUNT(1) AS conta_aud
	            FROM    tbl_auditoria_os
	            WHERE   os                  = $os
	            AND     auditoria_status    = 4
	            AND     liberada IS NULL
	            AND     cancelada IS NULL
	            AND     reprovada IS NULL	            
	        ";
	        $resVerAud = pg_query($con,$sqlVerAud);

	        if (pg_fetch_result($resVerAud,0,conta_aud) == 0) {
	            $gravaAud = true;
	            $sqlReAud = "
	                SELECT  COUNT(1) AS reauditoria_os
	                FROM    tbl_auditoria_os
	                WHERE   os = $os
	                AND     auditoria_status    = 4
	                AND     liberada            IS NULL
	                AND     (
	                            reprovada       IS NOT NULL
	                        OR  auditoria_os    IS NULL
	                        )
	                AND     observacao          ILIKE 'Auditoria de Devolu%o de Pe%as'
	            ";
	            $resReAud       = pg_query($con,$sqlReAud);

	            if (pg_fetch_result($resVerAud,0,reauditoria_os) == 0) {
	            	$grava_auditoria = false;
	            	$sql_reprova_aud = " SELECT reprovada 
	            						 FROM tbl_auditoria_os 
	            						 WHERE os = $os 
	            						 AND auditoria_status = 4 
	            						 AND observacao ILIKE 'Auditoria de Devolu%o de Pe%as'
	            						 ORDER BY data_input DESC LIMIT 1";
	            	$res_reprova_aud = pg_query($con,$sql_reprova_aud);
	            	if (!empty(pg_fetch_result($res_reprova_aud,0,reprovada))) {
	        			$grava_auditoria = true;
	            	} else {
	                    $sql_campos_adicionais = "SELECT DISTINCT jsonb_array_elements(campos_adicionais->'peca') AS peca 
	                                              FROM tbl_auditoria_os WHERE os = $os LIMIT 1";
	                    $res_campos_adicionais = pg_query($con, $sql_campos_adicionais);
	                    if (pg_num_rows($res_campos_adicionais) > 0) { 
	                        for ($s=0; $s < pg_num_rows($res_campos_adicionais); $s++) { 
	                            $array_campos_adicionais_auditoria[] = pg_fetch_result($res_campos_adicionais, $s, 'peca');
	                        }
	                        foreach ($array_campos_adicionais as $peca_array) {
	                            if (!in_array($peca_array,$array_campos_adicionais_auditoria)) {
	                                $grava_auditoria = true;
	                            }
	                        }
	                    } else {
	                        $grava_auditoria = true;
	                    }
					}

                    if ($grava_auditoria) {
                        $pecas['peca'] = $array_campos_adicionais;
                        $campos_adicionais_peca = json_encode($pecas);
                        $sqlAud = "
                            INSERT INTO tbl_auditoria_os (
                                os,
                                auditoria_status,
                                observacao,
                                paga_mao_obra,
                                campos_adicionais
                            ) VALUES (
                                $os,
                                4,
                                'Auditoria de Devolução de Peças',
                                FALSE,
                                '$campos_adicionais_peca'
                            )
                        ";
                        $resAud = pg_query($con,$sqlAud);
                    }
	            } 
	        }
    	}
    }

	if ($login_fabrica == 20 and strlen($msg_erro) == 0) { //hd 88308 waldir

		$xdata_fechamento = $_POST['data_fechamento'];

		if (strlen($xdata_fechamento) > 0) {

			//HD 187792
			$xxdata_fechamento_hora = explode(" ",$xdata_fechamento);

			$xdata_fechamento_hora = $xxdata_fechamento_hora[0];
			$xdata_fechamento_hora = explode("/",$xdata_fechamento_hora);
			$xdata_fechamento_hora = $xdata_fechamento_hora[2] . "-" . $xdata_fechamento_hora[1] . "-" . $xdata_fechamento_hora[0];

			$data_fechamento_hora  = $xdata_fechamento_hora . " " . $xxdata_fechamento_hora[1];

			$sql = "SELECT '$data_fechamento_hora' > CURRENT_TIMESTAMP AS data_maior";
			$res = @pg_query($con,$sql);
			$msg_erro   = pg_last_error($con);

			if (strpos($msg_erro,"out of range") > 0) {
				$msg_erro = "A hora está incorreta";
			}

			if (strlen($msg_erro) == 0) $data_maior = pg_result($res, 0, 'data_maior');

			if ($data_maior == "t") {
				$msg_erro = "A data do fechamento não pode ser maior que a data atual";
			}

			if (strlen($msg_erro) == 0) {

				$dia = substr($xdata_fechamento,0,2);
				$mes = substr($xdata_fechamento,3,2);
				$ano = substr($xdata_fechamento,6,4);

				$xdata_fechamento = $ano.'-'.$mes.'-'.$dia;

				$sql = "UPDATE tbl_os set data_fechamento      = '$xdata_fechamento',
										  data_hora_fechamento = '$data_fechamento_hora'
						WHERE fabrica = $login_fabrica
						AND   os      = $os";

				$res = @pg_query($con,$sql);
				$msg_erro = pg_last_error($con);

				if (strlen ($msg_erro) == 0) {

					$sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
					$res = @pg_query($con, $sql);
					$msg_erro = pg_last_error($con);

				}

				if ($tipo_atendimento == 12) {

					$sql_hora_abertura = "SELECT data_hora_abertura FROM tbl_os where os=$os";
					$res_hora_abertura = pg_query($con,$sql_hora_abertura);

					if (pg_num_rows($res_hora_abertura)>0) {
						$data_hora_abertura = pg_fetch_result($res_hora_abertura, 0, 0);

						$sql_os_explodida = "SELECT os FROM tbl_os where data_hora_abertura='$data_hora_abertura' AND posto = $login_posto AND fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento";
						$res_os_explodida = pg_query($con,$sql_os_explodida);

						if (pg_num_rows($res_os_explodida)>0) {

							for ($u=0; $u < pg_num_rows($res_os_explodida); $u++) {

								$os_explodida = pg_fetch_result($res_os_explodida, $u, 'os');
								$sql = "UPDATE tbl_os set 	data_fechamento      = '$xdata_fechamento',
											  				data_hora_fechamento = '$data_fechamento_hora'
										WHERE fabrica = $login_fabrica
										AND   os      = $os_explodida";

								$res = @pg_query($con,$sql);
								$msg_erro = pg_errormessage($con);

								if (strlen ($msg_erro) == 0) {

									$sql = "SELECT fn_finaliza_os($os_explodida, $login_fabrica)";
									$res = @pg_query($con, $sql);
									if (pg_last_error($con)) {

										$msg_erro = pg_last_error($con);

									}

								}

							}

						}

					}

				}

			}

		}

	}

	if($login_fabrica == 20 and $login_pais =='BR' AND in_array($tipo_atendimento,array(10,11,12,13))){//hd_chamado=2808833
		$link_nf = temNF($os, 'count');
		if($link_nf == 0){
			if((empty($_FILES["foto_nf"]["name"]))){
	      		$msg_erro = "Por favor inserir anexo da Nota Fiscal";
	  		}
  		}
  	}

	if (strlen ($msg_erro) == 0) {

		if ($anexaNotaFiscal) {
			foreach (range(0, 4) as $idx) {
				if ($_FILES["foto_nf"]['tmp_name'][$idx] != '') {
	                $file = array(
	                    "name" => $_FILES["foto_nf"]["name"][$idx],
	                    "type" => $_FILES["foto_nf"]["type"][$idx],
	                    "tmp_name" => $_FILES["foto_nf"]["tmp_name"][$idx],
	                    "error" => $_FILES["foto_nf"]["error"][$idx],
	                    "size" => $_FILES["foto_nf"]["size"][$idx]
	                );

	                $anexou = anexaNF($os, $file);
					if ($anexou !== 0) $msg_erro .= (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou; // '0' é que executou OK
				}
            }

		}

		$sqlB = "UPDATE tbl_os SET status_checkpoint = fn_os_status_checkpoint_os(os)
				  WHERE os = $os
					AND	status_checkpoint<>fn_os_status_checkpoint_os(os)";

		$res = pg_query($con,$sqlB);
		$msg_erro .= pg_last_error($con);

		if ($login_fabrica == 1 && $tipo_atendimento != 334 && $lancouPeca) {

			require_once "classes/Posvenda/Fabricas/_1/CustoPeca.php";

			$custoPeca = new CustoPeca($os, $login_fabrica, $login_posto, $con);

        	$dadosCusto = $custoPeca->getCustoPeca();
			if(count($dadosCusto) > 0) {
				//vai pagar a MO de troca, diferente de MO da pecas
				$dadosMobraTroca = $custoPeca->getMObra($os, true);
				$Mobra = $dadosMobraTroca['mao_de_obra'];

				if($dadosCusto['linha'] == 199){
					$valorProduto['total_produto'] = ($dadosCusto['medioCr'] * 1.15) + $Mobra;
					$valorProduto['multiplicador'] = '15';
				}else{
					$valorProduto['total_produto'] = ($dadosCusto['medioCr'] * 1.10) + $Mobra;
					$valorProduto['multiplicador'] = '10';
				}
				$valorProduto['Mobra_produto'] = $Mobra;

				if($dadosCusto['custo_pecas'] >= ($valorProduto['total_produto'] * 1.2) ){
					if($dadosCusto['reembolso_peca_estoque'] == 't'){
						$msg_erro .= $custoPeca->GravarAuditoria($os, $dadosCusto['custo_pecas']);
					}
				}else{
					$msg_erro .= $custoPeca->RetiraAuditoria($os);
				}
				$msg_erro .= $custoPeca->GravarCampoExtra($dadosCusto, $valorProduto);
			}
		}

		if(strlen($msg_erro)==0) {
			$res = pg_query($con, "COMMIT TRANSACTION");

			if($login_fabrica == 1){
				header ("Location: os_press.php?os=$os");
			}else{
				header ("Location: os_finalizada.php?os=$os");
			}
			exit;
		}
	} else {
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}

}

if (strlen($os) > 0) {
	#----------------- Le dados da OS --------------
	$sql = "SELECT  tbl_os.*                       ,
					TO_CHAR(tbl_os.data_conserto, 'DD/MM/YYYY') as data_conserto2,
					tbl_produto.produto            ,
					tbl_produto.referencia         ,
					tbl_produto.familia            ,
					tbl_produto.descricao          ,
					tbl_produto.voltagem           ,
					tbl_produto.linha              ,
					tbl_linha.nome AS linha_nome   ,
					tbl_posto_fabrica.codigo_posto ,
					tbl_os_extra.orientacao_sac    ,
					tbl_os_extra.os_reincidente AS reincidente_os,
					tbl_os.cortesia,
					tbl_os.satisfacao
			FROM    tbl_os
			JOIN    tbl_os_extra USING (os)
			JOIN    tbl_posto USING (posto)
			JOIN    tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
									  AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN    tbl_produto USING (produto)
			LEFT JOIN    tbl_linha   ON tbl_produto.linha = tbl_linha.linha
			WHERE   tbl_os.os = $os";

	$res = pg_query ($con,$sql);

 	$defeito_constatado       = pg_fetch_result($res, 0, 'defeito_constatado');
	$aparencia_produto	      = pg_fetch_result($res, 0, 'aparencia_produto');
	$acessorios		          = pg_fetch_result($res, 0, 'acessorios');
	$causa_defeito            = pg_fetch_result($res, 0, 'causa_defeito');
	$linha                    = pg_fetch_result($res, 0, 'linha');
	$linha_nome               = pg_fetch_result($res, 0, 'linha_nome');
	$consumidor_nome          = pg_fetch_result($res, 0, 'consumidor_nome');
	$sua_os                   = pg_fetch_result($res, 0, 'sua_os');
	$type                     = pg_fetch_result($res, 0, 'type');
	$produto_os               = pg_fetch_result($res, 0, 'produto');
	$produto_referencia       = pg_fetch_result($res, 0, 'referencia');
	$produto_descricao        = pg_fetch_result($res, 0, 'descricao');
	$produto_familia          = pg_fetch_result($res, 0, 'familia');
	$produto_voltagem         = pg_fetch_result($res, 0, 'voltagem');
	$produto_serie            = pg_fetch_result($res, 0, 'serie');
	$troca_faturada           = pg_fetch_result($res, 0, 'troca_faturada');
	$qtde_produtos            = pg_fetch_result($res, 0, 'qtde_produtos');
	$obs                      = pg_fetch_result($res, 0, 'obs');
	$codigo_posto             = pg_fetch_result($res, 0, 'codigo_posto');
	$defeito_reclamado        = pg_fetch_result($res, 0, 'defeito_reclamado');
	$os_reincidente           = pg_fetch_result($res, 0, 'reincidente_os');
	$consumidor_revenda       = pg_fetch_result($res, 0, 'consumidor_revenda');
	$solucao_os               = pg_fetch_result($res, 0, 'solucao_os');
	$tecnico_nome             = pg_fetch_result($res, 0, 'tecnico_nome');
	$valores_adicionais       = pg_fetch_result($res, 0, 'valores_adicionais');
	$justificativa_adicionais = pg_fetch_result($res, 0, 'justificativa_adicionais');
	$qtde_km                  = pg_fetch_result($res, 0, 'qtde_km');
	$qtde_hora                = pg_fetch_result($res, 0, 'qtde_hora');
	$orientacao_sac	          = pg_fetch_result($res, 0, 'orientacao_sac');
	$qtde_visitas             = pg_fetch_result($res, 0, 'qtde_visitas');
	$revenda_cnpj             = pg_fetch_result($res, 0, 'revenda_cnpj');
	$cortesia                 = pg_fetch_result($res, 0, 'cortesia');
	$data_conserto            = pg_fetch_result($res, 0, 'data_conserto2');
	$satisfacao            	  = pg_fetch_result($res, 0, 'satisfacao');
	#$orientacao_sac = html_entity_decode ($orientacao_sac,ENT_QUOTES);
	#$orientacao_sac = str_replace ("<br />","",$orientacao_sac);
	//$laudo_tecnico	= pg_fetch_result ($res,0,laudo_tecnico);

	//--=== Tradução para outras linguas ============================= Raphael HD:1212
	$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto_os AND upper(idioma) = '$sistema_lingua'";
	$res_idioma = @pg_query($con,$sql_idioma);

	if (@pg_num_rows($res_idioma) > 0) {
		$produto_descricao  = trim(@pg_fetch_result($res_idioma,0,descricao));
	}

	if (strlen($os_reincidente) > 0) {

		$sql = "SELECT tbl_os.sua_os
				FROM   tbl_os
				WHERE  tbl_os.os      = $os_reincidente
				AND    tbl_os.fabrica = $login_fabrica
				AND    tbl_os.posto   = $login_posto;";

		$res = @pg_query ($con, $sql) ;

		if (pg_num_rows($res) > 0) $sua_os_reincidente = trim(pg_fetch_result($res, 0, 'sua_os'));

	}

	if($login_fabrica == 1){
		$sql = "SELECT count(os_item) AS qtde_itens_os
					FROM tbl_os_item
					JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
					WHERE os = $os";
		$res = @pg_query ($con, $sql);
		$qtde_itens_os = pg_fetch_result($res, 0, 'qtde_itens_os');
	}

}

#---------------- Carrega campos de configuração da Fabrica -------------
$sql = "SELECT  tbl_fabrica.os_item_subconjunto  ,
				tbl_fabrica.pergunta_qtde_os_item,
				tbl_fabrica.os_item_serie        ,
				tbl_fabrica.os_item_aparencia    ,
				tbl_fabrica.qtde_item_os
		FROM    tbl_fabrica
		WHERE   tbl_fabrica.fabrica = $login_fabrica;";

$resX = pg_query ($con,$sql);

//aterado por sono hd 3931
if (pg_num_rows($resX) > 0) {

	$os_item_subconjunto = pg_fetch_result ($resX,0,os_item_subconjunto);
	if (strlen ($os_item_subconjunto) == 0) $os_item_subconjunto = 't';

	$pergunta_qtde_os_item = pg_fetch_result ($resX,0,pergunta_qtde_os_item);
	if (strlen ($pergunta_qtde_os_item) == 0) $pergunta_qtde_os_item = 'f';

	$os_item_serie = pg_fetch_result ($resX,0,os_item_serie);
	if (strlen ($os_item_serie) == 0) $os_item_serie = 'f';

	$os_item_aparencia = pg_fetch_result ($resX,0,os_item_aparencia);
	if (strlen ($os_item_aparencia) == 0) $os_item_aparencia = 'f';

	$qtde_item = pg_fetch_result ($resX,0,qtde_item_os);
	if (strlen ($qtde_item) == 0) $qtde_item = 5;

	//Linha Dewalt HD 21428 11/6/2008
	if($linha==198 AND $login_fabrica==1)$qtde_item = 35;

	if(($tipo_atendimento ==11) OR ($tipo_atendimento ==12) AND $login_fabrica==20)$qtde_item = 1;

}

$resX = pg_query ($con,"SELECT item_aparencia FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica");
$posto_item_aparencia = pg_fetch_result ($resX,0,0);

if($sistema_lingua == 'ES') {
	$title = "Telecontrol - Servicio Técnico - Orden de Servicio";
} else {
	$title = "Telecontrol - Assistência Técnica - Ordem de Serviço";
}

$body_onload = "javascript: document.frm_os.defeito_constatado.focus()";
$layout_menu = 'os';

include "cabecalho.php";

$imprimir = $_GET['imprimir'];
if (strlen ($os) == 0) $os = $_GET['os'];

if (strlen ($imprimir) > 0 AND strlen ($os) > 0 ) {

	echo "<script language='javascript'>";
		echo "window.open ('os_print.php?os=$os','os_print','resizable=yes,resize=yes,toolbar=no,location=yes,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0')";
	echo "</script>";

}

include "javascript_pesquisas.php"
?>
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">
<script type="text/javascript" src="js/jquery-1.4.2.js"></script>
<script src="js/jquery.maskedinput.js"></script>
<script src="plugins/shadowbox/shadowbox.js"	type="text/javascript"></script>

<script type="text/javascript" src="js/jquery.blockUI_2.39.js"></script>
<script type="text/javascript" src="js/plugin_verifica_servidor.js"></script>
<script type="text/javascript" language="JavaScript">
	var urlPecaPesquisa = '<?=$url?>';

	Object.prototype.toUriString = function () {
		var p=[], idx = Object.keys(this), self=this;
		idx.forEach(function(i) {p.push(i+'='+self[i]);});
		return p.join('&');
	}

	function verificaDespesas() {

		anexarDespesa = false;

		$("input[name^=valores_adicionais]").each(function(e){

			var obj = $(this);
			console.log(obj);

			if (obj.val().length == 0 || obj.val() == 0) {
				return;
			}

			anexarDespesa = true;

		});

		if ( anexarDespesa === true ) {

			$("#anexaDespesa").fadeIn("slow");

		} else {

			$("#anexaDespesa").hide();

		}

	}

	$(function() {

		verificaDespesas();

		$("input[name^=valores_adicionais]").change(function(e){

			verificaDespesas();

		});

		$("#data_fechamento").maskedinput("99/99/9999");
		$("#data_fechamento_hora").maskedinput("99/99/9999 99:99");
		$("input[rel='data']").maskedinput("99/99/9999");
	});


window.onload = function(){
    Shadowbox.init({
        skipSetup   : true,
        enableKeys  : false,
        modal       : true
    });

    var fabrica             = "<?=$login_fabrica?>";
    var satisfacao          = "<?=$satisfacao?>";
    var qtde_itens_os       = "<?=$qtde_itens_os?>";
    var tipo_atendimento    = "<?=$tipo_atendimento?>";

    if (fabrica == 1 && satisfacao == 't' && qtde_itens_os == 0 && tipo_atendimento != 334) {
        setTimeout(efetuaConserto(),500);
    }
}


function efetuaConserto () {

	Shadowbox.open({
		content:"<div style='background:#FFFFFF;height:100%;text-align:center;'>\
					<p class='erro_onde' style='display: none; font-size:14px;font-weight:bold; background-color: #ff0000; color: #ffffff; width: 100%;'>INFORME UMA OPÇÃO<br></p>\
					<br>\
					<p class='efetuar_conserto' style='font-size:14px;font-weight:bold;'>EFETUAR CONSERTO?</p><br><br>\
					<p class='efetuar_conserto' style='font-weight:bold;'>\
						<input type='radio' name='conserto' value='t' checked> SIM\
						<input type='radio' name='conserto' value='f'> NÃO\
					</p>\
					<p class='efetuar_troca' style='font-weight:bold; display:none;'>\
						<input type='radio' name='onde' value='revenda'> Troca de produto na revenda (Caso de compra em loja física)<br><br>\
						<input type='radio' name='onde' value='posto'> Troca de produto no posto (Caso de compra pela internet)\
					</p>\
					<br><br>\
					<p>\
						<input class='efetuar_conserto' type='button' value='Prosseguir' onclick=\"javascript:consertarProduto();\">\
						<input class='efetuar_troca' type='button' value='Prosseguir' style='display:none;' onclick=\"javascript:ondeTrocar();\">\
					</p>\
				</div>",
		player:	"html",
		title:	"Efetuar Conserto",
		width:	500,
		height:	200,
		options: {onFinish: function(){
			$("#sb-nav-close").hide();
		},
				overlayColor:'#fcfcfc' }
	});
}


function consertarProduto(){
	var consertar = $("input[name='conserto']:checked").val();

	if(consertar == "t"){
		Shadowbox.close();
	}else{
		$(".efetuar_conserto").hide();
		$(".efetuar_troca").show();
	}
}

function ondeTrocar(){
	var onde = $("input[name='onde']:checked").val();
	console.log(onde);
	if (onde == '' || onde == undefined) {
		$('.erro_onde').show();
		return false;
	}
	
	var os = "<?=$os?>";
	if (onde == 'revenda') {
		window.location = "gerar_laudo_tecnico.php?os="+os;	
	} else {
		window.location = "os_cadastro_troca.php?os_troca_prod="+os;
	}
}

var referencia;
var produto;
var descricao;
var preco;
var qtde;
//funcao lista basica tectoy, posicao, serie inicial, serie final
function fnc_pesquisa_lista_basica2 (produto_referencia, peca_referencia, peca_descricao, peca_preco, voltagem, tipo, peca_qtde) {
        var url = "";
		if (tipo == "tudo") {
			url = "<? echo $url; ?>2.php?produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
		}

		if (tipo == "referencia") {
			url = "<? echo $url; ?>2.php?produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
		}

		if (tipo == "descricao") {
			url = "<? echo $url; ?>2.php?produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
		}
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
		produto          = produto_referencia;
		referencia       = peca_referencia;
		descricao        = peca_descricao;
		preco            = peca_preco;
		qtde                     = peca_qtde;
		janela.focus();

}

function fnc_pesquisa_lista_basica (produto_referencia, peca_referencia, peca_descricao, peca_preco, voltagem, tipo, peca_qtde) {
	var params = {
		produto: produto_referencia,
		tipo: tipo,
		voltagem: voltagem.value || '',
		exibe: document.location.pathname
	},
	jParams = "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0";

    // Adiciona o valor se existe o elemento input no formulário
    if (typeof document.frm_os.versao_produto !== 'undefined') {
        params.versao_produto = document.frm_os.versao_produto.value;
    }

	switch (tipo) {
		case 'tudo':
			params.descricao = peca_referencia.value;
		break;
		case 'referencia':
			params.peca = peca_referencia.value;
		break;
		case 'descricao':
			params.descricao = peca_descricao.value;
		break;
		default: // por referência
			params.peca = peca_referencia.value;
		break;
	}

	var url = urlPecaPesquisa + '.php?' + params.toUriString();
	var janela = window.open(url, "janela", jParams);

    produto    = produto_referencia;
    referencia = peca_referencia;
    descricao  = peca_descricao;
    preco      = peca_preco;
    qtde       = peca_qtde;
    janela.focus();

}



function fnc_pesquisa_peca_lista_sub (produto_referencia, peca_posicao, peca_referencia, peca_descricao) {
	var url = "";
	if (produto_referencia != '') {
		url = "peca_pesquisa_lista_subconjunto.php?produto=" + produto_referencia;
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
		produto		= produto_referencia;
		posicao		= peca_posicao;
		referencia	= peca_referencia;
		descricao	= peca_descricao;
		janela.focus();
	}
}

/* FUNÇÃO PARA INTELBRAS POIS TEM POSIÇÃO PARA SER PESQUISADA */
function fnc_pesquisa_peca_lista_intel (produto_referencia, peca_referencia, peca_descricao, peca_posicao, tipo) {
	var url = "";
	if (tipo == "tudo") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo + "&faturado=sim";
	}

	if (tipo == "referencia") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo + "&faturado=sim";
	}

	if (tipo == "descricao") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo + "&faturado=sim";
	}
	if (peca_referencia.value.length >= 4 || peca_descricao.value.length >= 4) {
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
		produto		= produto_referencia;
		referencia	= peca_referencia;
		descricao	= peca_descricao;
		posicao		= peca_posicao;
		janela.focus();
	}else{
		alert("Digite pelo menos 4 caracteres!");
	}
}


function limpa_campo(entrada,id){
	if(entrada.value==''){
		document.getElementById("descricao_"+id).value = '';
		if (document.getElementById("qtde_"+id)){
			document.getElementById("qtde_"+id).value='';
		}
	}
}

function formata_data_visita(cnpj, form, posicao){
	var mycnpj = '';
	mycnpj = mycnpj + cnpj;
	myrecord = "visita_data_" + posicao;
	myform = form;

	if (mycnpj.length == 2){
		mycnpj = mycnpj + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}
	if (mycnpj.length == 5){
		mycnpj = mycnpj + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}

}

function formata_cnpj(cnpj, form, posicao){
	var mycnpj = '';
	mycnpj = mycnpj + cnpj;
	myrecord = "visita_hr_inicio_" + posicao;
	myform = form;

	if (mycnpj.length == 2){
		mycnpj = mycnpj + ':';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}

}
function formata_cnpj2(cnpj, form, posicao){
	var mycnpj = '';
	mycnpj = mycnpj + cnpj;
	myrecord = "visita_hr_fim_" + posicao;
	myform = form;

	if (mycnpj.length == 2){
		mycnpj = mycnpj + ':';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}

}

function formata_valor(cnpj, form, posicao) {

	var mycnpj = '';
	mycnpj = mycnpj + cnpj;
	myrecord = "valores_adicionais_" + posicao;
	myform = form;

	if (mycnpj.length == 2){
		mycnpj = mycnpj + '.';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}


}

function FormataValor(campo,tammax,teclapres) {

    //uso: <input type="Text" name="fat_vr_bruto" maxlength="17" onKeyDown="FormataValor(this,17,event)">

    var tecla = teclapres.keyCode;
    vr = campo.value;
    vr = vr.replace( "/", "" );
    vr = vr.replace( "/", "" );
    vr = vr.replace( ",", "" );
    vr = vr.replace( ".", "" );
    vr = vr.replace( ".", "" );
    vr = vr.replace( ".", "" );
    vr = vr.replace( ".", "" );
    tam = vr.length;

    if (tam<tammax && tecla != 8){ tam = vr.length + 1 ; }

    if (tecla == 8 ){    tam = tam - 1 ; }

    if ( tecla == 8 || tecla >= 48 && tecla <= 57 || tecla >= 96 && tecla <= 105 ){
        if ( tam <= 2 ){
             campo.value = vr ; }
         if ( (tam>2) && (tam <= 5) ){
             campo.value = vr.substr( 0, tam - 2 ) + ',' + vr.substr( tam - 2, tam ) ; }
         if ( (tam >= 6) && (tam <= 8) ){
             campo.value = vr.substr( 0, tam - 5 ) + '.' + vr.substr( tam - 5, 3 ) + ',' + vr.substr( tam - 2, tam ) ; }
         if ( (tam >= 9) && (tam <= 11) ){
             campo.value = vr.substr( 0, tam - 8 ) + '.' + vr.substr( tam - 8, 3 ) + '.' + vr.substr( tam - 5, 3 ) + ',' + vr.substr( tam - 2, tam ) ; }
         if ( (tam >= 12) && (tam <= 14) ){
             campo.value = vr.substr( 0, tam - 11 ) + '.' + vr.substr( tam - 11, 3 ) + '.' + vr.substr( tam - 8, 3 ) + '.' + vr.substr( tam - 5, 3 ) + ',' + vr.substr( tam - 2, tam ) ; }
         if ( (tam >= 15) && (tam <= 17) ){
             campo.value = vr.substr( 0, tam - 14 ) + '.' + vr.substr( tam - 14, 3 ) + '.' + vr.substr( tam - 11, 3 ) + '.' + vr.substr( tam - 8, 3 ) + '.' + vr.substr( tam - 5, 3 ) + ',' + vr.substr( tam - 2, tam ) ;}
    }
}

function verPecaNegativa(peca,posto,linha){
	/*
	var curDateTime = new Date();
	window.open ('ajax_peca_negativa.php?ajax=true&peca='+ peca +'&data='+curDateTime,'os_print','resizable=yes,resize=yes,toolbar=no,location=yes,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0')
	*/
	var curDateTime = new Date();

	if(linha=='t'){
		$.ajax({
			type: "GET",
			url: "consulta_estoque_ajax.php",
			data: 'ajax=true&peca='+ peca +"&data="+curDateTime+"&ver_historico=t",
			complete: function(resposta) {
				results = resposta.responseText;
				linha = 0;
				$("#pecaNegativa_"+linha).html(results).css('display','block');
				document.getElementById('validaPecanegativa_'+linha).value = '1';
			}
		});
		return false;
	}

	if (document.getElementById('validaPecanegativa_'+linha).value=='0') {
		$.ajax({
			type: "GET",
			url: "consulta_estoque_ajax.php",
			data: 'ajax=true&peca='+ peca +"&data="+curDateTime ,
			complete: function(resposta) {
				results = resposta.responseText;
				$("#pecaNegativa_"+linha).html(results).css('display','block');
				document.getElementById('validaPecanegativa_'+linha).value = '1';
			}
		});
	}else{
		$("#pecaNegativa_"+linha).css('display','none');
			document.getElementById('validaPecanegativa_'+linha).value = '0';
	}
	return false;
}

function fechar(peca){
	$('#'+peca).hide();
	$("."+peca).show();
}

</script>

<style>
a.lnk:link{
	font-size: 10px;
	font-weight: bold;
	text-decoration: underline;
	color:#FFFF33;
}
/*label.obrigatorio { hd_chamado=2808833
	color: #CC0000;
}*/
a.lnk:visited{
	font-size: 10px;
	font-weight: bold;
	text-decoration: underline;
	color:#FFFF33;
}
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

.mensagem{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 18px;
	font-weight: bold;
	color:#FF0000;
}

</style><?php

$os_item = trim($_GET['os_item']);

if ($os_item > 0) {
	echo "<div style='width:700px;'><FONT COLOR=\"#FF0033\"><B>$msg_erro_item</B></FONT></div>";
	$msg_erro_item = 0;
}

if ($login_fabrica == 3 AND strlen($msg_intervencao) < 0) { // para nao mostrar - fabio
	echo "<table width='600' border='0' cellpadding='3' cellspacing='5' align='center' bgcolor='#ffCCCC'>";
		echo "<tr>";
			echo "<td valign='middle' align='center'>";
				echo "$msg_intervencao";
			echo "</td>";
		echo "</tr>";
	echo "</table><br>";
}

if (strlen ($msg_erro) > 0) {

	##### Recarrega Form em caso de erro #####
	$os                       = $_POST["os"];
	$defeito_reclamado        = $_POST["defeito_reclamado"];
	$causa_defeito            = $_POST["causa_defeito"];
	$obs                      = $_POST["obs"];
	$aparencia_produto		  = $_POST["aparencia_produto"];
	$acessorios				  = $_POST["acessorios"];
	$defeito_constatado       = $_POST["defeito_constatado"];
	$solucao_os               = $_POST["solucao_os"];
	$type                     = $_POST["type"];
	$tecnico_nome             = $_POST["tecnico_nome"];
	$valores_adicionais       = $_POST["valores_adicionais"];
	$justificativa_adicionais = $_POST["justificativa_adicionais"];
	$qtde_km                  = $_POST["qtde_km"];
	$tecnico                  = $_POST["tecnico"];
	//$laudo_tecnico                  = $_POST["laudo_tecnico"];

	if (strpos ($msg_erro,"Cannot insert a duplicate key into unique index tbl_os_sua_os") > 0){
		if($sistema_lingua=="ES"){
			$msg_erro = "Esta Orden de Servicio ya está registrada";
		}else{
			$msg_erro = "Esta ordem de serviço já foi cadastrada";
		}
	}
	if (strpos ($msg_erro,"Type informado") > 0){
		if($sistema_lingua=="ES"){
			$msg_erro ="TYPE informado para este producto no es válido";
		}else{
			$msg_erro = "Type informado para o produto não é válido";
		}
	}
	if (strpos ($msg_erro,"em quantidade superior à permitida.") >  0){
		if($sistema_lingua=="ES"){
			$msg_erro = "Repuesto en cantidad superior a la permitida";
		}else{
			$msg_erro = "Quantidade de peça superior a permitida";
		}
	}
	echo "<table width='700' border='0' cellpadding='0' cellspacing='0' align='center' bgcolor='#ffCCCC'>";
	echo "<tr>";
	echo "<td height='27' valign='middle' align='center'>";
	echo "<b><font face='Arial, Helvetica, sans-serif' style='font-size:14px;' color='#FF3333'>";

	if($sistema_lingua =='ES') $msg_erro = traducao_erro($msg_erro,$sistema_lingua);

	// retira palavra ERROR:
	if (strpos($msg_erro,"ERROR: ") !== false) {
        if($login_fabrica == 1 && strpos($msg_erro,"fora da garantia vencida em") !== false){
            $msg_erro = str_replace("fora da garantia vencida em","é de desgaste natural e a garantia da mesma terminou em",$msg_erro);
        }
		if($sistema_lingua=="ES") $erro = "Fue detectada la seguiente divergencia:<BR>";
		else $erro = "Foi detectada a seguinte divergência: <br>";
			$msg_erro = substr($msg_erro, 6);

	}

	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}
	echo $erro . $msg_erro . $msg_bloqueio;

	echo "</font></b>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";

}

if ($login_fabrica == 19) {//hd 3335

	echo "<table width='600' border='0' cellpadding='3' cellspacing='5' align='center' bgcolor='#B4D6E1'>";
		echo "<tr>";
			echo "<td valign='middle' align='center'>";
				echo "<b><font face='Arial, Helvetica, sans-serif' color='#465357' size='1'>Caso algum tipo de defeito Constatado não esteja relacionado nas opções, favor informar o Depto de Assistência Técnica através do e-mail osg@lorenzetti.com.br, informando qual o número da OS,<BR> o produto e qual o defeito que não consta na lista</font></b>";
			echo "</td>";
		echo "</tr>";
	echo "</table>";

}

if (strlen ($msg_previsao) > 0) {

	echo "<table width='600' border='0' cellpadding='0' cellspacing='0' align='center' bgcolor='#ffCCCC'>";
	echo "<tr>";
	echo "<td height='27' valign='middle' align='center'>";
	echo "<b><font face='Arial, Helvetica, sans-serif' color='##3333FF'>";

	echo $msg_previsao ;

	echo "</font></b>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
}

#------------ Pedidos via Distribuidor -----------#
$resX = pg_query ($con,"SELECT pedido_via_distribuidor FROM tbl_fabrica WHERE fabrica = $login_fabrica");
if (pg_fetch_result ($resX,0,0) == 't') {

	$resX = pg_query($con, "SELECT tbl_posto.nome FROM tbl_posto JOIN tbl_posto_linha ON tbl_posto_linha.distribuidor = tbl_posto.posto WHERE tbl_posto_linha.posto = $login_posto AND tbl_posto_linha.linha = $linha");

	if (pg_num_rows ($resX) > 0) {
		echo "<center>Atenção! Peças da linha <b>$linha_nome</b> serão atendidas pelo distribuidor.<br><font size='+1'>" . pg_fetch_result ($resX,0,nome) . "</font></center><p>";
	} else {
		echo "<center>Peças da linha <b>$linha_nome</b> serão atendidas pelo fabricante.</center><p>";
	}

}

if (strlen($sua_os_reincidente) > 0 and $login_fabrica == 6) {

	echo "<br><br>";

	echo "<table width='600' border='0' cellpadding='0' cellspacing='0' align='center' bgcolor='#ffCCCC'>";
		echo "<tr>";
			echo "<td valign='middle' align='center'>";
				echo "<font face='Verdana,Arial, Helvetica, sans-serif' color='#FF3333' size='2'><b>";
					echo "ESTA ORDEM DE SERVIÇO É REINCIDENTE MENOR QUE 30 DIAS.<br>
					O NÚMERO DE SÉRIE É O MESMO UTILIZADO NA ORDEM DE SERVIÇO: $sua_os_reincidente.<br>
					NÃO SERÁ PAGO O VALOR DE MÃO-DE-OBRA PARA A ORDEM DE SERVIÇO ATUAL.<BR>
					ELA SERVIRÁ APENAS PARA PEDIDO DE PEÇAS.";
				echo "</b></font>";
			echo "</td>";
		echo "</tr>";
	echo "</table>";

	echo "<br><br>";

}?>

<table width="750" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td><img height="1" width="20" src="imagens/spacer.gif"></td>
	<td valign="top" align="center">
		<!-- ------------- Formulário ----------------- -->
		<form name="frm_os" id="frm_os" method="post" action="" enctype="multipart/form-data">
		<input type="hidden" name="os"        value="<?echo $os?>">
		<input type="hidden" name="voltagem"  value="<?echo $produto_voltagem?>">
		<input type='hidden' name='qtde_item' value='<? echo $qtde_item ?>'>
		<p><?php

    if ($usa_versao_produto) {
        $versao_produto = serie_produto_versao($produto_os, $serie);
        echo "<input type='hidden' name='versao_produto' value='$versao_produto'>";
    }

	if ($compressor<>'t') {
		//echo "<input type='hidden' name='laudo_tecnico'        value='$laudo_tecnico'>";
	}

	if ($login_fabrica == 1) {

		if ($revenda_cnpj=="53296273000191") {

			echo "<script>alert('"._("Produtos de estoque de revenda deverão ser digitados na opção CADASTRO DE ORDEM DE SERVIÇO DE REVENDA. Se o produto em questão for de estoque de revenda, gentileza digitar nessa opção. Pois em caso de digitações incorretas, a B&D fará a exclusão da OS.")."')</script>";

		}?>

		<table border="0" cellspacing="0" cellpadding="0" align="center">
			<tr>
				<td nowrap>
					<a href="os_print.php?os=<?=$os?>" target="_blank" alt="Imprimir OS"><img src="imagens/btn_imprimir.gif"></a>
				</td>
			</tr>
		</table><?php

	}?>

	<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">OS</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
					<b><?php
						if ($login_fabrica == 1) echo $codigo_posto;
						echo $sua_os;?>
					</b>
				</font>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? if($sistema_lingua == 'ES') echo "Cliente";else echo "Consumidor";?></font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo $consumidor_nome ?></b>
				</font>
			</td>

			<? if ($login_fabrica == 19) { ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Qtde.Produtos</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b>
				<?
				echo $qtde_produtos;
				?>
				</b>
				</font>
			</td>
			<? } ?>

			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><?fecho('produto', $con);?></font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo "$produto_referencia - $produto_descricao"; ?></b>
				</font>
			<?
                if($login_fabrica == 1){
                    $sqlProduto = "
                        SELECT  inibir_lista_basica
                        FROM    tbl_produto
                        WHERE   fabrica_i = $login_fabrica
                        AND     produto = $produto_os
                    ";
                    $resProduto = pg_query($con,$sqlProduto);
                    $inibir_lista_basica = pg_fetch_result($resProduto,0,inibir_lista_basica);
                }
                if($inibir_lista_basica != 't'){
                    $sqlv = "SELECT tbl_comunicado.comunicado, extensao
                            FROM tbl_comunicado
                            LEFT JOIN tbl_comunicado_produto USING(comunicado)
                            WHERE fabrica = $login_fabrica
                                AND tipo = 'Vista Explodida'
                                AND (   tbl_comunicado.produto         = $produto_os
                                    OR tbl_comunicado_produto.produto = $produto_os)";


                    $resv = pg_query($con,$sqlv) ;

                    if (pg_num_rows($resv) > 0) {
                        $vcomunicado             = pg_fetch_result($resv, 0, 'comunicado');
                        $vextensao               = pg_fetch_result($resv, 0, 'extensao');

                        if ($S3_sdk_OK) {
                            include_once S3CLASS;

                            $s3 = new anexaS3('ve', (int) $login_fabrica, $vcomunicado);
                            $S3_online = is_object($s3);
                        }

                        $vista_explodida_produto = ($S3_online and $s3->temAnexo) ? $s3->url : "comunicados/$vcomunicado.$vextensao";
                        if ($vista_explodida_produto) {
                            echo "&nbsp;<a href='$vista_explodida_produto' target='_blank' style='vertical-align:top;font-size:9px;height:32px;line-height:16px;display:inline-block;width:100px'>
                                        <img src='imagens/botoes/vista_explodida_icone.png' style='float:left' height='32' alt='Vista Explodida' title='Vista Explodida do Produto' />
                                        <span >Vista<br />Explodida</span>
                                    </a>";
                        }
                    }
				}
				?>
			</td>

			<td nowrap><?php
			if ($login_fabrica == 1) {

				echo "<font size=\"1\" face=\"Geneva, Arial, Helvetica, san-serif\">Versão/Type</font>";
				echo "<br>";

				       GeraComboType::makeComboType($parametrosAdicionaisObject, $type,null,array("class"=>"frm"));
			               echo GeraComboType::getElement();

				       echo "&nbsp;";
		     } ?>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? if($sistema_lingua == 'ES') echo "N. Série";else echo "N. Série";?></font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo $produto_serie ?></b>
				</font>
			</td>
		</tr>
	</table>

	<table width="100%" border="0" cellspacing="5" cellpadding="0"><?php

		if ($login_fabrica == 14 AND $login_posto == 6359) {

			if (strlen($defeito_reclamado) > 0) {

				$sql = "SELECT descricao FROM tbl_defeito_reclamado WHERE defeito_reclamado=$defeito_reclamado";
				$res = pg_query($con,$sql);

				if (pg_num_rows($res) > 0) {
					$defeito_reclamado_descricao = pg_fetch_result($res, 0, 0);
				}

			}

			echo "<tr>";
				echo "<td>";
					echo '<font size="1" face="Geneva, Arial, Helvetica, san-serif">';
						echo "Defeito Reclamado";
					echo "</font>";
					echo "<br>";
					//echo "<b style='font-size:12px;'>$defeito_reclamado_descricao</b>";
					echo "<select  class='frm' disabled>";
						echo "<option value=''>$defeito_reclamado_descricao</option>";
					echo "</select>";
				echo "</td>";
			echo "</tr>";

		}

		if ( in_array($login_fabrica, array(11,172)) ) {
			//aparencia do produto
			echo "<tr>";
			echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Aparência do Produto<br></font><input class='frm' type='text' name='aparencia_produto' size='30' value='$aparencia_produto' onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on'; displayText('&nbsp;Texto livre com a aparência externa do aparelho deixado no balcão.');\" </font></td>";

			//acessórios
			echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Acessórios<br></font><input class='frm' type='text' name='acessorios' size='30' value='$acessorios' onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on'; displayText('&nbsp;Texto livre com a aparência externa do aparelho deixado no balcão.');\" </td>";
	 		echo "</tr>";
		}?>
		<tr><?php
		if ($login_fabrica == 20 AND ($tipo_atendimento == 11 OR $tipo_atendimento == 12)) {
		} else {
			if ($pedir_defeito_constatado_os_item <> 'f') {?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">
					<?
					if($login_fabrica=='20'){
						if($sistema_lingua == 'ES') echo "Reparación";else echo "Reparo";
					}else {
						echo "Defeito Constatado";
					}
					?>
				</font>
				<br>
				<select name="defeito_constatado" size="1" class="frm">
					<option selected></option><?php

				$sql = "SELECT defeito_constatado_por_familia,
								defeito_constatado_por_linha
						FROM tbl_fabrica
						WHERE fabrica = $login_fabrica";

				$res = pg_query ($con,$sql);
				$defeito_constatado_por_familia = pg_fetch_result ($res,0,0) ;
				$defeito_constatado_por_linha   = pg_fetch_result ($res,0,1) ;

				if ($defeito_constatado_por_familia == 't') {

					$sql = "SELECT familia FROM tbl_produto WHERE produto = $produto_os";
					$res = pg_query($con,$sql);

					$familia = pg_fetch_result($res, 0, 0) ;

					if ($login_fabrica == 1) {

						if ($linha == 199 or $linha == 200) {// Hd 54744
							$cond_1 = " AND tbl_defeito_constatado.defeito_constatado <> 6749 ";
						}

						$sql = "SELECT tbl_defeito_constatado.* FROM tbl_familia  JOIN   tbl_familia_defeito_constatado USING(familia) JOIN   tbl_defeito_constatado USING(defeito_constatado) ";

						//hd 6864 - HD 57308
						if ($linha == 198) $sql .= "LEFT JOIN tbl_produto_defeito_constatado ON tbl_produto_defeito_constatado.defeito_constatado = tbl_defeito_constatado.defeito_constatado AND tbl_produto_defeito_constatado.produto = $produto_os ";

						/*$sql .= " JOIN tbl_produto_defeito_constatado ON tbl_produto_defeito_constatado.defeito_constatado = tbl_defeito_constatado.defeito_constatado AND tbl_produto_defeito_constatado.produto = $produto_os ";
						*/
						$sql .= "
							WHERE  tbl_defeito_constatado.fabrica = $login_fabrica
							AND tbl_familia_defeito_constatado.familia = $familia
							AND tbl_defeito_constatado.defeito_constatado NOT IN (1505,1506,1507)
							$cond_1 ";
						//HD10730 - Fábio não aparecer troca
						if ($consumidor_revenda == 'C') $sql .= " AND tbl_defeito_constatado.codigo <> '1' ";

						//hd 6864
						//if ($linha == 198) $sql .= " AND tbl_produto_defeito_constatado.produto = $produto_os ";
						$sql .= " ORDER BY tbl_defeito_constatado.descricao";

					} else {

						$sql = "SELECT tbl_defeito_constatado.*
								FROM   tbl_familia
								JOIN   tbl_familia_defeito_constatado USING(familia)
								JOIN   tbl_defeito_constatado         USING(defeito_constatado)
								WHERE  tbl_defeito_constatado.fabrica         = $login_fabrica    AND tbl_defeito_constatado.ativo = 't'
								AND    tbl_familia_defeito_constatado.familia = $familia";

						if ($login_fabrica == 19) {
							//hd 3347
							$sql .= " AND tbl_defeito_constatado.defeito_constatado <> 10820 ";

							//hd 3470
							if ($linha <> 261) $sql .= " AND tbl_defeito_constatado.defeito_constatado <> 10823 ";
						}

						$sql .= " ORDER BY tbl_defeito_constatado.descricao";

					}

				} else {

					if ($defeito_constatado_por_linha == 't') {

						$sql   = "SELECT linha FROM tbl_produto WHERE produto = $produto_os";
						$res   = pg_query ($con,$sql);
						$linha = pg_fetch_result ($res,0,0) ;

						$sql = "SELECT tbl_defeito_constatado.*
								FROM   tbl_defeito_constatado
								JOIN   tbl_linha USING(linha)
								WHERE  tbl_defeito_constatado.fabrica         = $login_fabrica
								AND    ativo = 't'
								AND    tbl_linha.linha = $linha";

						if ($consumidor_revenda == 'C' AND $login_fabrica == 1) $sql .= " AND tbl_defeito_constatado.codigo <> '1' ";

						$sql .= " ORDER BY tbl_defeito_constatado.descricao";

					} else {

						$sql = "SELECT tbl_defeito_constatado.*
								FROM   tbl_defeito_constatado
								WHERE  tbl_defeito_constatado.fabrica = $login_fabrica
								AND    ativo = 't' ";

						if ($consumidor_revenda == 'C' AND $login_fabrica == 1) {
							$sql .= " AND tbl_defeito_constatado.codigo <> '1' ";
						}

						if ( in_array($login_fabrica, array(11,172)) ) {
							$sql .= " ORDER BY tbl_defeito_constatado.codigo";
						} else {
							$sql .= " ORDER BY tbl_defeito_constatado.descricao";
						}

					}

				}

				//adicionado para listar todos os defeitos constatados para a Latina
				//Modificado por Fernando
				if ($login_fabrica == "15") {

					$sql = "SELECT linha FROM tbl_diagnostico where linha = $linha and fabrica = 15 limit 1;";
					$res = pg_query($con,$sql);

					if (pg_num_rows($res) > 0) {
						$linha_diagnostico = pg_fetch_result($res,0,0);
					}

					$sql = "SELECT distinct tbl_defeito_constatado.*
							FROM tbl_defeito_constatado
							WHERE tbl_defeito_constatado.fabrica = $login_fabrica ";

					if (strlen($linha_diagnostico) > 0) {

						$sql = "SELECT distinct tbl_defeito_constatado.*
									FROM tbl_defeito_constatado
									JOIN tbl_diagnostico using(defeito_constatado)
									WHERE tbl_diagnostico.linha = $linha_diagnostico ";

					}

					$sql .= " ORDER BY tbl_defeito_constatado.descricao; ";

				}

				if ( in_array($login_fabrica, array(2,8,11,172)) ) {

					$sql = "SELECT tbl_defeito_constatado.*
							FROM tbl_defeito_constatado
							WHERE fabrica = $login_fabrica
							ORDER BY tbl_defeito_constatado.descricao";

				}

				if ($login_fabrica == 19 and $tipo_atendimento == 3) {

					$sql = "SELECT tbl_defeito_constatado.*
							FROM tbl_defeito_constatado
							WHERE fabrica = $login_fabrica and defeito_constatado in (10021,10546,10547,10548,10549,10550,10551,10552,10545)";

				}

				#--------- Bosch ----------
				if ($login_fabrica == "20") {
					$sql = "SELECT tbl_defeito_constatado.*
							FROM tbl_defeito_constatado
							JOIN tbl_produto_defeito_constatado
								ON  tbl_defeito_constatado.defeito_constatado = tbl_produto_defeito_constatado.defeito_constatado
								AND tbl_produto_defeito_constatado.produto = $produto_os
							WHERE fabrica = $login_fabrica
							ORDER BY tbl_defeito_constatado.descricao";
				}

                if ($login_fabrica == 1 && $tipo_atendimento == 334) {
                    $sql = "SELECT  tbl_defeito_constatado.defeito_constatado,
                                    tbl_defeito_constatado.descricao,
                                    tbl_defeito_constatado.codigo
                            FROM    tbl_defeito_constatado
                            WHERE   fabrica = $login_fabrica
                            AND     ativo IS TRUE
                      ORDER BY      codigo
                    ";
                }

				$res = pg_query ($con,$sql) ;

				for ($i = 0; $i < pg_num_rows($res); $i++) {

					$descricao_d = pg_fetch_result ($res, $i, 'descricao');

					//--=== Tradução para outras linguas ============================= Raphael HD:1212
					$sql_idioma = "SELECT * FROM tbl_defeito_constatado_idioma WHERE defeito_constatado = ".pg_fetch_result ($res,$i,defeito_constatado)." AND upper(idioma) = '$sistema_lingua'";

					$res_idioma = @pg_query($con,$sql_idioma);
					if (@pg_num_rows($res_idioma) >0) {
						$descricao_d  = trim(@pg_fetch_result($res_idioma,0,descricao));
					}
					//--=== Tradução para outras linguas ================================================

					echo "<option ";
					if ($defeito_constatado == pg_fetch_result ($res,$i,defeito_constatado) ) echo " selected ";
					echo " value='" . pg_fetch_result ($res,$i,defeito_constatado) . "'>" ;
					if($login_fabrica <> 15) echo pg_fetch_result ($res,$i,codigo) ." - ". $descricao_d ;
					else echo $descricao_d ;
					echo "</option>";
				}?>
				</select>
			</td><?php

			}
		}

		if ($pedir_causa_defeito_os_item <> 'f' and $login_fabrica <> 5) {?>
			<td nowrap><?php

				if ($login_fabrica == 1) {
					echo "<input type='hidden' name='name='causa_defeito' value='149'>";
				} else {?>

					<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? if($sistema_lingua == 'ES') echo "Defecto";else echo "Defeito";?></font>
					<br />
					<select name="causa_defeito" size="1" class="frm">
					<option selected></option><?php
					$sql = "SELECT * FROM tbl_causa_defeito WHERE fabrica = $login_fabrica ORDER BY codigo, descricao";
					$res = pg_query ($con,$sql) ;

					for ($i = 0; $i < pg_num_rows($res); $i++) {

						$descricao_d = pg_fetch_result($res, $i, 'descricao');

						//--=== Tradução para outras linguas ============================= Raphael HD:1212
						$sql_idioma = "SELECT * FROM tbl_causa_defeito_idioma WHERE causa_defeito = ".pg_fetch_result ($res,$i,causa_defeito)." AND upper(idioma) = '$sistema_lingua'";

						$res_idioma = @pg_query($con,$sql_idioma);
						if (@pg_num_rows($res_idioma) >0) {
							$descricao_d  = trim(@pg_fetch_result($res_idioma,0,descricao));
						}
						//--=== Tradução para outras linguas ================================================

						echo "<option ";
						if ($causa_defeito == pg_fetch_result ($res,$i,causa_defeito) ) echo " selected ";
						echo " value='" . pg_fetch_result ($res,$i,causa_defeito) . "'>" ;
						echo pg_fetch_result ($res,$i,codigo) . " - " . $descricao_d ;
						echo "</option>\n";

					}?>
				</select><?php
				}?>
			</td><?php
			} ?>

		</tr>
	</table><?php

//identificacao
if ($pedir_solucao_os_item <> 'f') {
?>
	<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td align="left" nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">
<?php
    if ($login_fabrica <> 20) {
        echo "Solução";
    } else {
        if($sistema_lingua == 'ES') echo "Identificación";else echo "Identificação";
    }
?>
				</font>
				<br>

				<select name="solucao_os" size="1" class="frm">
					<option value=""></option>
<?php

    if ($login_fabrica == 1 ) {

        if ($login_reembolso_peca_estoque == 't') {
            $sql_add1 = " AND ( descricao NOT ILIKE 'Troca de pe%' OR descricao ILIKE 'subst%' ) ";
        } else {
            $sql_add1 = " AND (descricao ILIKE 'troca%' OR descricao NOT ILIKE 'subst%') ";
        }

        if ($cortesia <> 't') {
            $sql .= " descricao NOT ILIKE 'Devolução de dinheiro%' ";
        }


        $sql = "SELECT  tbl_solucao.solucao,
                        tbl_solucao.descricao
                FROM    tbl_solucao

        ";

        if ($tipo_atendimento != 334) {
            $sql .= "
                JOIN    tbl_linha_solucao   ON  tbl_solucao.solucao     = tbl_linha_solucao.solucao
                                            AND tbl_linha_solucao.linha = $linha
            ";
        } else {
            $sql_add1 = "
                AND descricao ILIKE 'Substitui%o de pe%a gerando pedido'
            ";
        }

    }

    $sql .= " WHERE tbl_solucao.fabrica = $login_fabrica
                AND tbl_solucao.ativo IS TRUE
                $sql_add1
                ORDER BY tbl_solucao.descricao";

    if ($troca_faturada == 't') { #HD 12940

        $sql = "SELECT  tbl_solucao.solucao,
                        tbl_solucao.descricao
                FROM tbl_solucao
                JOIN tbl_linha_solucao ON tbl_solucao.solucao = tbl_linha_solucao.solucao AND tbl_linha_solucao.linha = $linha

                WHERE tbl_solucao.fabrica = $login_fabrica
                AND ( tbl_solucao.ativo IS TRUE OR tbl_solucao.descricao ilike '%roca de produto fatur%')
                $sql_add1
                ORDER BY tbl_solucao.descricao";

    }
    
    $res = pg_query($con, $sql);

    for ($x = 0; $x < pg_num_rows($res); $x++) {

        $aux_solucao_os    = pg_fetch_result($res, $x, 'solucao');
        $solucao_descricao = pg_fetch_result($res, $x, 'descricao');

        echo "<option id='opcoes' value='$aux_solucao_os' "; if($aux_solucao_os == $solucao_os) echo " SELECTED"; echo ">$solucao_descricao</option>";

    }

} else {

    $sql = "SELECT *
            FROM   tbl_servico_realizado
            WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";

    if ($login_pede_peca_garantia == 't' AND $login_fabrica <> 1 AND $login_fabrica <> 15) {
        $sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
    }

    if ($login_fabrica == 1) {

        if ($login_reembolso_peca_estoque == 't') {

            $sql .= "AND (tbl_servico_realizado.descricao NOT ILIKE 'Troca de pe%' ";
            $sql .= "OR tbl_servico_realizado.descricao ILIKE 'subst%') ";
            if (strlen($linha) > 0) $sql .= " AND (tbl_servico_realizado.linha = '$linha' OR tbl_servico_realizado.linha is null) ";

        } else {

            $sql .= "AND (tbl_servico_realizado.descricao ILIKE 'troca%' ";
            $sql .= "OR tbl_servico_realizado.descricao NOT ILIKE 'subst%') ";
            if (strlen($linha) > 0) $sql .= " AND (tbl_servico_realizado.linha = '$linha' OR tbl_servico_realizado.linha is null) ";
        }

        if ($cortesia <> 't') {
            $sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'Devolução de dinheiro%' ";
        }

    }

    if ($login_fabrica == 20) $sql .=" AND tbl_servico_realizado.solucao IS NOT TRUE ";

    /* HD: 39231 09/09/2008 */
    if ($login_fabrica == 20 AND $tipo_atendimento == 12) {
        $sql .= " AND tbl_servico_realizado.garantia_acessorio is true ";
    }else{
        $sql .= " AND tbl_servico_realizado.garantia_acessorio is not true ";
    }

    $sql .= " AND tbl_servico_realizado.ativo IS TRUE ORDER BY descricao ";
    $res  = pg_query ($con,$sql) ;

    if (pg_num_rows($res) == 0) {

        $sql = "SELECT *
                FROM   tbl_servico_realizado
                WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";

        if ($login_pede_peca_garantia == 't' AND $login_fabrica <> 1 AND $login_fabrica <> 15) {
            $sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
        }

        if ($login_fabrica == 1) {

            if ($login_reembolso_peca_estoque == 't') {

                $sql .= "AND (tbl_servico_realizado.descricao NOT ILIKE 'Troca de pe%' ";
                $sql .= "OR tbl_servico_realizado.descricao ILIKE 'subst%') ";

            } else {

                $sql .= "AND (tbl_servico_realizado.descricao ILIKE 'troca%' ";
                $sql .= "OR tbl_servico_realizado.descricao NOT ILIKE 'subst%') ";

            }

        }

        $sql .=	" AND tbl_servico_realizado.linha IS NULL
                AND tbl_servico_realizado.ativo IS TRUE ORDER BY descricao ";

        $res = pg_query($con, $sql);

    }

    for ($x = 0; $x < pg_num_rows($res); $x++) {

        $descricao_d = pg_fetch_result($res, $x, 'descricao');

        //--=== Tradução para outras linguas ============================= Raphael HD:1212
        $sql_idioma = "SELECT *
                            FROM tbl_servico_realizado_idioma
                        WHERE servico_realizado = ".pg_fetch_result($res, $x, 'servico_realizado')."
                            AND upper(idioma) = '$sistema_lingua'";

        $res_idioma = @pg_query($con, $sql_idioma);

        if (@pg_num_rows($res_idioma) >0) {
            $descricao_d  = trim(@pg_fetch_result($res_idioma, 0, 'descricao'));
        }
        //--=== Tradução para outras linguas ================================================

        echo "<option ";
        if ($solucao_os == pg_fetch_result ($res,$x,servico_realizado)) echo " selected ";
        echo " value='" . pg_fetch_result ($res,$x,servico_realizado) . "'>" ;
        echo $descricao_d ;
        if (pg_fetch_result ($res,$x,gera_pedido) == 't' AND $login_fabrica == 6) echo " - GERA PEDIDO DE PEÇA ";
        echo "</option>";

    }

}
?>
				</select>
			</td><?php

			if ($login_fabrica == 3) {?>

				<td align="right" nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Qtde. Itens</font><br>
					<select name="n_itens" size="1" class="frm" onchange='javascript:document.location.href="<? echo $PHP_SELF ?>?os=<? echo $os ?>&n_itens="+this.value'>
						<option value='3' <? if ($qtde_itens_mostrar==3)echo " selected"; ?>>3</option>
						<option value='5' <? if ($qtde_itens_mostrar==5)echo " selected"; ?>>5</option>
					</select>
				</td><?php

			}
?>
		</tr>

	</table>
<?
// SOMENTE LORENZETTI
if ($login_fabrica == 19) {
?>
	<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td align="left" nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome do Técnico</font>
				<br>
				<input type='text' name='tecnico_nome' size='20' maxlength='20' value='<? echo $tecnico_nome ?>'>
			</td>
		</tr>
	</table><?php
}

		### LISTA ITENS DA OS QUE POSSUEM PEDIDOS
		if (strlen($os) > 0) {

			$sql = "SELECT  tbl_os_item.pedido                                  ,
							case when tbl_pedido.pedido_blackedecker > 99999 then
								lpad((tbl_pedido.pedido_blackedecker - 100000)::text,5,'0')
							else
								lpad(tbl_pedido.pedido_blackedecker::text,5,'0')
							end                           AS pedido_blackedecker,
							tbl_os_item.posicao                                 ,
							tbl_os_item.qtde                                    ,
							tbl_os_item.causa_defeito                           ,
							tbl_os_item.admin  as admin_peca                    ,
							tbl_peca.referencia                                 ,
							tbl_peca.descricao                                  ,
							tbl_defeito.defeito                                 ,
							tbl_defeito.descricao AS defeito_descricao          ,
							tbl_causa_defeito.descricao AS causa_defeito_descricao,
							tbl_produto.referencia AS subconjunto               ,
							tbl_os_produto.produto                              ,
							tbl_os_produto.serie                                ,
							tbl_servico_realizado.servico_realizado             ,
							tbl_servico_realizado.descricao AS servico_descricao
					FROM    tbl_os
					JOIN   (SELECT os FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica) oss ON tbl_os.os = oss.os
					JOIN    tbl_os_produto             ON tbl_os.os = tbl_os_produto.os
					JOIN    tbl_os_item                ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					JOIN    tbl_produto                ON tbl_os_produto.produto = tbl_produto.produto
					JOIN    tbl_peca                   ON tbl_os_item.peca = tbl_peca.peca
					JOIN    tbl_pedido                 ON tbl_os_item.pedido       = tbl_pedido.pedido
					LEFT JOIN    tbl_defeito           USING (defeito)
					LEFT JOIN    tbl_causa_defeito     ON tbl_os_item.causa_defeito = tbl_causa_defeito.causa_defeito
					LEFT JOIN    tbl_servico_realizado USING (servico_realizado)
					WHERE   tbl_os.os      = $os
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_os_item.pedido NOTNULL
					ORDER BY tbl_os_item.os_item ASC;";
			$res = pg_query ($con,$sql) ;

			if(pg_num_rows($res) > 0) {
				//13670 20/2/2008
				if($login_fabrica == 1){
					echo "<table width='100%' border='0' cellspacing='2' cellpadding='0'>";
						echo "<tr height='20'>";
						echo "<td align='center' colspan='4'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#ff0000'>As peças que forem lançadas até as 11h30 nas ordens de serviço, o site emitirá o pedido de garantia e enviará para a fábrica no horário padrão das 11h45, todas as segundas, quartas e sextas-feira. Após este horário, acesse o menu PEDIDOS/CONSULTA DE PEDIDOS, para identificar o número do pedido que o site gerou.</font></td>";
						echo "</tr>";
					echo "</table>";
				}

				echo "<table width='100%' border='0' cellspacing='2' cellpadding='0'>";
				echo "<tr height='20' bgcolor='#666666'>";

				echo "<td align='center' colspan='4'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Pedidos já enviados ao fabricante</b></font></td>";

				echo "</tr>";
				echo "<tr height='20' bgcolor='#666666'>";

				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Pedido</b></font></td>";
				if ($login_fabrica == 14) {
					echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Posição</b></font></td>";
				}
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Referência</b></font></td>";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descrição</b></font></td>";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>";
if($sistema_lingua == 'ES'){
echo "Ctd";
}else{
echo "Qtde";
}

echo "</b></font></td>";


				echo "</tr>";

				$numero_pecas_faturadas=pg_num_rows($res);

				for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
						$faturado      = pg_num_rows($res);
						$fat_pedido    = pg_fetch_result($res,$i,pedido);
						$fat_pedido_blackedecker = pg_fetch_result($res,$i,pedido_blackedecker);
						$posicao       = pg_fetch_result($res,$i,posicao);
						$fat_peca      = pg_fetch_result($res,$i,referencia);
						$fat_descricao = pg_fetch_result($res,$i,descricao);
						$fat_qtde      = pg_fetch_result ($res,$i,qtde);

						echo "<tr height='20' bgcolor='#FFFFFF'>";

						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>";
						if ($login_fabrica == 1) echo $fat_pedido_blackedecker; else echo $fat_pedido;
						echo "</font></td>";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$fat_peca</font></td>";
						echo "<td align='left'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$fat_descricao</font></td>";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$fat_qtde</font></td>";

						echo "</tr>";
				}
				echo "</table>";
			}
		}

		### LISTA ITENS DA OS QUE ESTÃO COMO NÃO LIBERADAS PARA PEDIDO EM GARANTIA
		if(strlen($os) > 0){
			$sql = "SELECT  tbl_os_item.os_item                                 ,
							tbl_os_item.obs                                     ,
							tbl_os_item.qtde                                    ,
							tbl_os_item.posicao                                 ,
							tbl_os_item.admin  as admin_peca                    ,
							tbl_peca.referencia                                 ,
							tbl_peca.descricao                                  ,
							tbl_defeito.defeito                                 ,
							tbl_defeito.descricao AS defeito_descricao          ,
							tbl_produto.referencia AS subconjunto               ,
							tbl_os_produto.produto                              ,
							tbl_os_produto.serie                                ,
							tbl_servico_realizado.servico_realizado             ,
							tbl_servico_realizado.descricao AS servico_descricao
					FROM    tbl_os
					JOIN   (SELECT os FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica) oss ON tbl_os.os = oss.os
					JOIN    tbl_os_produto             ON tbl_os.os = tbl_os_produto.os
					JOIN    tbl_os_item                ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					JOIN    tbl_produto                ON tbl_os_produto.produto = tbl_produto.produto
					JOIN    tbl_peca                   ON tbl_os_item.peca = tbl_peca.peca
					LEFT JOIN    tbl_pedido            ON tbl_os_item.pedido       = tbl_pedido.pedido
					LEFT JOIN    tbl_defeito           USING (defeito)
					LEFT JOIN    tbl_servico_realizado USING (servico_realizado)
					WHERE   tbl_os.os      = $os
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_os_item.liberacao_pedido           IS FALSE
					AND     tbl_os_item.liberacao_pedido_analisado IS TRUE
					ORDER BY tbl_os_item.os_item ASC;";
			$res = pg_query ($con,$sql) ;

			if(pg_num_rows($res) > 0) {
				$col = 4;
				if($login_fabrica == 14){ $col = 6; }
				echo "<table width='100%' border='0' cellspacing='2' cellpadding='0'>\n";
				echo "<tr height='20' bgcolor='#666666'>\n";

				if ($login_fabrica <> 6) {
					echo "<td align='center' colspan='$col'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peças que não irão gerar pedido em garantia</b></font></td>\n";
				}else{
					echo "<td align='center' colspan='$col'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peças pendentes</b></font></td>\n";
				}

				echo "</tr>\n";
				echo "<tr height='20' bgcolor='#666666'>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Pedido</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Posição</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Referência</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descrição</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>";
if($sistema_lingua == 'ES'){
echo "Ctd";
}else{
echo "Qtde";
}

echo "</b></font></td>\n";
				if($login_fabrica == 14){ echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Excluir</b></font></td>\n";	}
				echo "</tr>\n";

				for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
						$recusado      = pg_num_rows($res);
						$rec_item      = pg_fetch_result($res,$i,os_item);
						$rec_obs       = pg_fetch_result($res,$i,obs);
						$posicao       = pg_fetch_result($res,$i,posicao);
						$rec_peca      = pg_fetch_result($res,$i,referencia);
						$rec_descricao = pg_fetch_result($res,$i,descricao);
						$rec_qtde      = pg_fetch_result($res,$i,qtde);

						echo "<tr height='20' bgcolor='#FFFFFF'>";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_obs</font></td>\n";
						if ($login_fabrica == 14) {
							echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$posicao</font></td>";
						}
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_peca</font></td>\n";
						echo "<td align='left'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_descricao</font></td>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_qtde</font></td>\n";
						if($login_fabrica == 14){ echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='$PHP_SELF?os=$os&os_item=$rec_item'><IMG SRC=\"imagens/btn_excluir.gif\" ALT=\"Excluir\"></font></a></td>";	}

						echo "</tr>\n";

				}
				echo "</table>\n";
			}
		}

		### LISTA ITENS DA OS FORAM LIBERADAS E AINDA NÃO POSSEM PEDIDO
		if(strlen($os) > 0){
			$sql = "SELECT  tbl_os_item.os_item                                 ,
							tbl_os_item.obs                                     ,
							tbl_os_item.qtde                                    ,
							tbl_os_item.admin  as admin_peca                    ,
							tbl_peca.referencia                                 ,
							tbl_peca.descricao                                  ,
							tbl_defeito.defeito                                 ,
							tbl_defeito.descricao AS defeito_descricao          ,
							tbl_produto.referencia AS subconjunto               ,
							tbl_os_produto.produto                              ,
							tbl_os_produto.serie                                ,
							tbl_servico_realizado.servico_realizado             ,
							tbl_servico_realizado.descricao AS servico_descricao
					FROM    tbl_os
					JOIN   (SELECT os FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica) oss ON tbl_os.os = oss.os
					JOIN    tbl_os_produto             ON tbl_os.os = tbl_os_produto.os
					JOIN    tbl_os_item                ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					JOIN    tbl_produto                ON tbl_os_produto.produto = tbl_produto.produto
					JOIN    tbl_peca                   ON tbl_os_item.peca = tbl_peca.peca
					LEFT JOIN    tbl_pedido            ON tbl_os_item.pedido       = tbl_pedido.pedido
					LEFT JOIN    tbl_defeito           USING (defeito)
					LEFT JOIN    tbl_servico_realizado USING (servico_realizado)
					WHERE   tbl_os.os      = $os
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_os_item.pedido           ISNULL
					AND     tbl_os_item.liberacao_pedido IS TRUE
					ORDER BY tbl_os_item.os_item ASC;";


			$res = pg_query ($con,$sql) ;

			if(pg_num_rows($res) > 0) {
				echo "<table width='100%' border='0' cellspacing='2' cellpadding='0'>\n";
				echo "<tr height='20' bgcolor='#666666'>\n";

				echo "<td align='center' colspan='$col'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peças aprovadas aguardando pedido</b></font></td>\n";

				echo "</tr>\n";
				echo "<tr height='20' bgcolor='#666666'>\n";

				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Pedido</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Referência</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descrição</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>";
				if($sistema_lingua=='ES'){
					echo "Ctd";
				}else{
					echo "Qtde";
				}

				echo "</b></font></td>\n";

				echo "</tr>\n";

				for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
						$recusado      = pg_num_rows($res);
						$rec_item      = pg_fetch_result($res,$i,os_item);
						$rec_obs       = pg_fetch_result($res,$i,obs);
						$rec_peca      = pg_fetch_result($res,$i,referencia);
						$rec_descricao = pg_fetch_result($res,$i,descricao);
						$rec_qtde      = pg_fetch_result($res,$i,qtde);

						echo "<tr height='20' bgcolor='#FFFFFF'>";

						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_obs</font></td>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_peca</font></td>\n";
						echo "<td align='left'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_descricao</font></td>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_qtde</font></td>\n";

						echo "</tr>\n";
				}
				echo "</table>\n";
			}
		}

		if(strlen($os) > 0 AND strlen ($msg_erro) == 0){
			if ($os_item_aparencia == 't' AND $posto_item_aparencia == 't' and $os_item_subconjunto == 'f') {
				$sql = "SELECT  tbl_peca.peca
						FROM    tbl_peca
						JOIN    tbl_lista_basica USING (peca)
						JOIN    tbl_produto      USING (produto)
						WHERE   tbl_produto.produto     = $produto_os
						AND     tbl_peca.fabrica        = $login_fabrica
						AND     tbl_peca.item_aparencia = 't'
						ORDER BY tbl_peca.referencia;";
				$resX = @pg_query($con,$sql);
				$inicio_itens = @pg_num_rows($resX);
			}else{
				$inicio_itens = 0;
			}
			$sql = "SELECT  tbl_os_item.os_item                                                ,
							tbl_os_item.pedido                                                 ,
							tbl_os_item.qtde                                                   ,
							tbl_os_item.causa_defeito                                          ,
							tbl_os_item.posicao                                                ,
							tbl_os_item.admin  as admin_peca                                   ,
							tbl_os_item.adicional_peca_estoque                                 ,
							tbl_peca.referencia                                                ,
							tbl_peca.descricao                                                 ,
							tbl_defeito.defeito                                                ,
							tbl_defeito.descricao                   AS defeito_descricao       ,
							tbl_causa_defeito.descricao             AS causa_defeito_descricao ,
							tbl_produto.referencia                  AS subconjunto             ,
							tbl_os_produto.os_produto                                          ,
							tbl_os_produto.produto                                             ,
							tbl_os_produto.serie                                               ,
							tbl_servico_realizado.servico_realizado                            ,
							tbl_servico_realizado.descricao         AS servico_descricao
					FROM    tbl_os
					JOIN   (SELECT os FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica) oss ON tbl_os.os = oss.os
					JOIN    tbl_os_produto             ON tbl_os.os = tbl_os_produto.os
					JOIN    tbl_os_item                ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					JOIN    tbl_produto                ON tbl_os_produto.produto = tbl_produto.produto
					JOIN    tbl_peca                   ON tbl_os_item.peca = tbl_peca.peca
					LEFT JOIN    tbl_pedido                 ON tbl_os_item.pedido       = tbl_pedido.pedido
					LEFT JOIN    tbl_defeito           USING (defeito)
					LEFT JOIN    tbl_causa_defeito     ON tbl_os_item.causa_defeito = tbl_causa_defeito.causa_defeito
					LEFT JOIN    tbl_servico_realizado USING (servico_realizado)
					WHERE   tbl_os.os      = $os
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_os_item.pedido           ISNULL
					AND     tbl_os_item.liberacao_pedido IS FALSE
					ORDER BY tbl_os_item.os_item;";
			$res = pg_query ($con,$sql) ;

			if (pg_num_rows($res) > 0) {
			#	$qtde_item = pg_num_rows($res);
				$fim_itens = $inicio_itens + pg_num_rows($res);
				$i = 0;
				$qtde= array();
				for ($k = $inicio_itens ; $k < $fim_itens ; $k++) {
					$os_item[$k]                 = pg_fetch_result($res,$i,os_item);
					$os_produto[$k]              = pg_fetch_result($res,$i,os_produto);
					$pedido[$k]                  = pg_fetch_result($res,$i,pedido);
					$peca[$k]                    = pg_fetch_result($res,$i,referencia);
					$qtde[$k]                    = pg_fetch_result($res,$i,qtde);
					$produto[$k]                 = pg_fetch_result($res,$i,subconjunto);
					$serie[$k]                   = pg_fetch_result($res,$i,serie);
					$posicao[$k]                 = pg_fetch_result($res,$i,posicao);
					$descricao[$k]               = pg_fetch_result($res,$i,descricao);
					$defeito[$k]                 = pg_fetch_result($res,$i,defeito);
					$pcausa_defeito[$k]          = pg_fetch_result($res,$i,causa_defeito);
					$causa_defeito_descricao[$k] = pg_fetch_result($res,$i,causa_defeito_descricao);
					$defeito_descricao[$k]       = pg_fetch_result($res,$i,defeito_descricao);
					$servico[$k]                 = pg_fetch_result($res,$i,servico_realizado);
					$servico_descricao[$k]       = pg_fetch_result($res,$i,servico_descricao);
					$adicional[$k]               = pg_fetch_result($res,$i,adicional_peca_estoque);
					$admin_peca[$k]              = pg_fetch_result($res,$i,admin_peca);
					if(strlen($admin_peca[$k])==0) { $admin_peca[$k]="P"; }
					$i++;

				}
			}else{
				for ($i = 0 ; $i < $qtde_item ; $i++) {
					$os_item[$i]        = $_POST["os_item_"        . $i];
					$os_produto[$i]     = $_POST["os_produto_"     . $i];
					$produto[$i]        = $_POST["produto_"        . $i];
					$serie[$i]          = $_POST["serie_"          . $i];
					$posicao[$i]        = $_POST["posicao_"        . $i];
					$peca[$i]           = $_POST["peca_"           . $i];
					$qtde[$i]           = $_POST["qtde_"           . $i];
					$defeito[$i]        = $_POST["defeito_"        . $i];
					$pcausa_defeito[$i] = $_POST["pcausa_defeito_" . $i];
					$servico[$i]        = $_POST["servico_"        . $i];
					$adicional[$i]      = $_POST["adicional_peca_estoque_"        . $i];
					$admin_peca[$i]     = $_POST["admin_peca_"     . $i];

					if (strlen($peca[$i]) > 0) {
						$sql = "SELECT  tbl_peca.referencia,
										tbl_peca.descricao
								FROM    tbl_peca
								WHERE   tbl_peca.fabrica    = $login_fabrica
								AND     tbl_peca.referencia = '$peca[$i]';";
						$resX = @pg_query ($con,$sql) ;

						if (@pg_num_rows($resX) > 0) {
							$descricao[$i] = trim(pg_fetch_result($resX,0,descricao));
						}
					}

				}
			}
		}else{
			for ($i = 0 ; $i < $qtde_item ; $i++) {
				$os_item[$i]        = $_POST["os_item_"        . $i];
				$os_produto[$i]     = $_POST["os_produto_"     . $i];
				$produto[$i]        = $_POST["produto_"        . $i];
				$serie[$i]          = $_POST["serie_"          . $i];
				$posicao[$i]        = $_POST["posicao_"        . $i];
				$peca[$i]           = $_POST["peca_"           . $i];
				$qtde[$i]           = $_POST["qtde_"           . $i];
				$defeito[$i]        = $_POST["defeito_"        . $i];
				$pcausa_defeito[$i] = $_POST["pcausa_defeito_" . $i];
				$servico[$i]        = $_POST["servico_"        . $i];
				$adicional[$i]      = $_POST["adicional_peca_estoque_"        . $i];
				$admin_peca[$i]     = $_POST["admin_peca_"     . $i];

				if (strlen($peca[$i]) > 0) {
					$sql = "SELECT  tbl_peca.referencia,
									tbl_peca.descricao
							FROM    tbl_peca
							WHERE   tbl_peca.fabrica    = $login_fabrica
							AND     tbl_peca.referencia = '$peca[$i]';";
					$resX = @pg_query ($con,$sql) ;
					if (@pg_num_rows($resX) > 0) {
						$descricao[$i] = trim(pg_fetch_result($resX,0,descricao));
					}
				}

			}
		}

		if ($login_fabrica == 3) {

			echo "<table width='100%' border='0' cellspacing='3' cellpadding='0'>";
			echo "<tr height='10' bgcolor='#666666'>";
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#00FF00'><b>Apenas uma peça. OS com pedido aprovado</b></font></td>";
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#FFFF00'><b>Duas peças. OS e pedidos sujeitos a análise</b></font></td>";
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#FF0000'><b>Três ou mais peças. OS e pedidos sujeitos a auditoria</b></font></td>";
		}

		echo "<table width='100%' border='0' cellspacing='2' cellpadding='0'>";
		echo "<tr height='20' bgcolor='#666666'>";

		if ($os_item_subconjunto == 't') {
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Subconjunto</b></font></td>";
		}

		if ($os_item_serie == 't' AND $os_item_subconjunto == 't') {
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>N. Série</b></font></td>";
		}

		if ($login_fabrica == 14) echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Posição</b></font></td>";

		echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>";
if($sistema_lingua == 'ES'){
echo "Código";
}else{
echo "Codigo";
}

echo "</b></font>";
        echo "</td>";
        if($inibir_lista_basica != 't'){
            echo "<td align='center'><acronym title=\"Clique para abrir a lista básica do produto.\"><a class='lnk' href='peca_consulta_por_produto";
		}
		if ($login_fabrica == 14) echo "_subconjunto";
		if($inibir_lista_basica != 't'){
            echo ".php?produto=$produto_os' target='_blank'>Lista Básica</a></acronym></td>";
        }


		echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>";
		echo ($sistema_lingua == 'ES') ? "Descripción" : "Descrição";
		echo "</b></font></td>";

		if ($pergunta_qtde_os_item == 't') {
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>";
			echo ($sistema_lingua=='ES') ? "Ctd" : "Qtde";
			echo "</b></font></td>";
			/*
			if($login_fabrica == 20  and $login_pais == 'CO'){
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Estoque</b></font></td>";
			}
			*/
		}

		if ($pedir_causa_defeito_os_item == 't' AND $login_fabrica<>20) {
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Causa</b></font></td>";
		}
		if($login_fabrica <> 20 ){
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Defeito</b></font></td>";
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Serviço</b></font></td>";
		}

		echo "</tr>";

		$loop = $qtde_item;

		if ($login_fabrica==3){
			$sql = "SELECT  count(*) as contador
					FROM    tbl_os
					JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
					JOIN tbl_os_item      ON tbl_os_item.os_produto = tbl_os_produto.os_produto
					WHERE   tbl_os.os  = $os
					AND     tbl_os.fabrica = $login_fabrica";
			$res = pg_query ($con,$sql);
			$num = pg_fetch_result($res,0,contador) - $numero_pecas_faturadas;
			$loop = $qtde_itens_mostrar - $numero_pecas_faturadas;
			if ($loop<$num)
				$loop = $num;
		}

#		if (strlen($faturado) > 0) $loop = $qtde_item - $faturado;

		$offset = 0;
		for ($i = 0 ; $i < $loop ; $i++) {

			$cor="";
			if ($login_fabrica==3){
				$cor=" bgcolor='#FF6666'";
				if ($i==0) {
					$cor=" bgcolor='#99FF99'";
					if ($numero_pecas_faturadas==1) $cor=" bgcolor='#FFFF99'";
				}
				if ($i==1){
					 $cor=" bgcolor='#FFFF99'";
					if ($numero_pecas_faturadas==1) $cor=" bgcolor='#FF6666'";
				}
				if ($numero_pecas_faturadas>=2) $cor=" bgcolor='#FF6666'";
			}
			//--=== Tradução para outras linguas ============================= Raphael HD:1212
			if (strlen($peca[$i])>0 and $cook_idioma != 'pt-br') {
				$sql_idioma = "SELECT * FROM tbl_peca_idioma LEFT JOIN tbl_peca using(peca) WHERE referencia = '".$peca[$i]. "' AND upper(idioma) = '$sistema_lingua'";

				$res_idioma = @pg_query($con,$sql_idioma);
				if (@pg_num_rows($res_idioma) >0) {
					$descricao[$i]  = trim(@pg_fetch_result($res_idioma,0,descricao));
				}
			}
			//--=== Tradução para outras linguas ==============================
			echo "<tr$cor>";
			echo "<input type='hidden' name='os_produto_$i' value='$os_produto[$i]'>\n";
			echo "<input type='hidden' name='os_item_$i'    value='$os_item[$i]'>\n";
			echo "<input type='hidden' name='descricao'>";
			echo "<input type='hidden' name='preco'>";
			echo "<input type='hidden' name='admin_peca_$i' value='$admin_peca[$i]'>";

			if ($os_item_subconjunto == 'f') {
				echo "<input type='hidden' name='produto_$i' value='$produto_referencia'>";
			}else{
				echo "<td align='center' nowrap>";
				echo "<select class='frm' size='1' name='produto_$i'>";
				#echo "<option></option>";

				$sql = "SELECT  tbl_produto.produto   ,
								tbl_produto.referencia,
								tbl_produto.descricao
						FROM    tbl_subproduto
						JOIN    tbl_produto ON tbl_subproduto.produto_filho = tbl_produto.produto
						WHERE   tbl_subproduto.produto_pai = $produto_os
						ORDER BY tbl_produto.referencia;";
				$resX = pg_query ($con,$sql) ;

				echo "<option value='$produto_referencia' ";
				if ($produto[$i] == $produto_referencia) echo " selected ";
				echo " >$produto_descricao</option>";

				for ($x = 0 ; $x < pg_num_rows ($resX) ; $x++ ) {
					$sub_produto    = trim (pg_fetch_result ($resX,$x,produto));
					$sub_referencia = trim (pg_fetch_result ($resX,$x,referencia));
					$sub_descricao  = trim (pg_fetch_result ($resX,$x,descricao));

					if ($login_fabrica == 14 AND substr ($sub_referencia,0,3) == "499" ){
						$sql = "SELECT  tbl_produto.produto   ,
										tbl_produto.referencia,
										tbl_produto.descricao
								FROM    tbl_subproduto
								JOIN    tbl_produto ON tbl_subproduto.produto_filho = tbl_produto.produto
								WHERE   tbl_subproduto.produto_pai = $sub_produto
								ORDER BY tbl_produto.referencia;";
						$resY = pg_query ($con,$sql) ;
						echo "<optgroup label='" . $sub_referencia . " - " . substr($sub_descricao,0,25) . "'>" ;
						for ($y = 0 ; $y < pg_num_rows ($resY) ; $y++ ) {
							$sub_produto    = trim (pg_fetch_result ($resY,$y,produto));
							$sub_referencia = trim (pg_fetch_result ($resY,$y,referencia));
							$sub_descricao  = trim (pg_fetch_result ($resY,$y,descricao));

							echo "<option ";
							if (trim ($produto[$i]) == $sub_referencia) echo " selected ";
							echo " value='" . $sub_referencia . "'>" ;
							echo $sub_referencia . " - " . substr($sub_descricao,0,25) ;
							echo "</option>";
						}
						echo "</optgroup>";
					}else{
						echo "<option ";
						if (trim ($produto[$i]) == $sub_referencia) echo " selected ";
						echo " value='" . $sub_referencia . "'>" ;
						echo $sub_referencia . " - " . substr($sub_descricao,0,25) ;
						echo "</option>";
					}
				}

				echo "</select>";
				if ($login_fabrica == 14) {
					echo " <img src='imagens/btn_lupa.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista_sub (document.frm_os.produto_$i.value, document.frm_os.posicao_$i, document.frm_os.peca_$i, document.frm_os.descricao_$i)' alt='Clique para abrir a lista básica do produto selecionado' style='cursor:pointer;'>";
				}
				echo "</td>\n";
			}

			if ($os_item_subconjunto == 'f') {
				$xproduto = $produto[$i];
				echo "<input type='hidden' name='serie_$i'>\n";
			}else{
				if ($os_item_serie == 't') {
					echo "<td align='center'><input class='frm' type='text' name='serie_$' size='9' value='$serie[$i]'></td>\n";
				}
			}

			if ($os_item_aparencia == 't' AND $posto_item_aparencia == 't' and $os_item_subconjunto == 'f') {
				$sql = "SELECT  tbl_peca.peca      ,
								tbl_peca.referencia,
								tbl_peca.descricao ,
								tbl_lista_basica.qtde
						FROM    tbl_peca
						JOIN    tbl_lista_basica USING (peca)
						JOIN    tbl_produto      USING (produto)
						WHERE   tbl_produto.produto     = $produto_os
						AND     tbl_peca.fabrica        = $login_fabrica
						AND     tbl_peca.item_aparencia = 't'
						ORDER BY tbl_peca.referencia
						LIMIT 1 OFFSET $offset;";
				$resX = @pg_query ($con,$sql) ;

				if (@pg_num_rows($resX) > 0) {
					$xpeca       = trim(pg_fetch_result($resX,0,peca));
					$xreferencia = trim(pg_fetch_result($resX,0,referencia));
					$xdescricao  = trim(pg_fetch_result($resX,0,descricao));
					$xqtde       = trim(pg_fetch_result($resX,0,qtde));



					if ($peca[$i] == $xreferencia)
						$check = " checked ";
					else
						$check = "";

					if ($login_posto == 427) $check = " checked ";

					echo "<td align='center'><input class='frm' type='checkbox' name='peca_$i' value='$xreferencia' $check>&nbsp;<font face='arial' size='-2' color='#000000'>$xreferencia</font></td>\n";

                   echo "<td width='60' align='center'>";
                                        //echo "<img src='imagens/btn_lista.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_lista_basica (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"referencia\")' alt='LISTA BÁSICA' style='cursor:pointer;'>";
                   echo "</TD>";


					echo "<td align='center'><font face='arial' size='-2' color='#000000'>$xdescricao</font></td>\n";
					echo "<td align='center'><font face='arial' size='-2' color='#000000'>$xqtde</font><input type='hidden' name='qtde_$i' value='$xqtde'></td>\n";

					if ($login_fabrica == 6) {
					    if (strlen ($defeito[$i]) == 0) $defeito[$i] = 78 ;
					    if (strlen ($servico[$i]) == 0) $servico[$i] = 1 ;
					}
				}else{

					echo "<td align='center' nowrap><input class='frm' type='text' name='peca_$i' size='15' value='$peca[$i]' id='peca_$i' onblur=\"javascript:limpa_campo(this,$i)\">&nbsp;<img src='imagens/btn_lupa.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"tudo\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></td>\n";


             	 	echo "<td width='60' align='center'><img src='imagens/btn_lista.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_lista_basica (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"referencia\")' alt='LISTA BÁSICA' style='cursor:pointer;'></TD>";

					echo "<td align='center' nowrap><input class='frm' type='text' name='descricao_$i' id='descricao_$i' size='25' value='$descricao[$i]'>&nbsp;<img src='imagens/btn_lupa.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"descricao\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></td>\n";
					if ($pergunta_qtde_os_item == 't') {
						echo "<td align='center'><input class='frm' type='text' name='qtde_$i' size='3' value='$qtde[$i]' id='qtde_$i'></td>\n";
					}
				}
			}else{
				if ($login_fabrica == 14) {
					echo "<td align='center'><input class='frm' type='text' name='posicao_$i' size='6' maxlength='6' value='$posicao[$i]'></td>\n";
				}else{
					echo "<input type='hidden' name='posicao_$i'>\n";
				}

				echo "<td align='center' nowrap><input class='frm' type='text' name='peca_$i' size='15' value='$peca[$i]' id='peca_$i' onblur=\"javascript:limpa_campo(this,$i)\">&nbsp;<img src='imagens/btn_lupa.gif' border='0' align='absmiddle'";
				if ($login_fabrica == 14) echo " onclick='javascript: fnc_pesquisa_peca_lista_intel (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.posicao_$i , \"referencia\")'";
				else echo " onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"referencia\")'";
				echo " alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>";

                 /*
 echo "<img src='imagens/btn_lista.gif' border='0' align='absmiddle'                         onclick='javascript: fnc_pesquisa_lista_basica (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"referencia\")' alt='LISTA BÁSICA' style='cursor:pointer;'>";*/




                echo "</td>\n";

				if($login_fabrica ==6){
					echo "<td width='60' align='center'><img src='imagens/btn_lista.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_lista_basica2(document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"referencia\")' alt='LISTA BÁSICA' style='cursor:pointer;'></TD>";
				}else{
                    if($inibir_lista_basica != 't'){
                        echo "<td width='60' align='center'><img src='imagens/btn_lista.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_lista_basica (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"referencia\")' alt='LISTA BÁSICA' style='cursor:pointer;'></TD>";
                    }
				}
				echo "<td align='center' nowrap><input class='frm' type='text' name='descricao_$i' size='25' value='$descricao[$i]'>&nbsp;<img src='imagens/btn_lupa.gif' border='0' align='absmiddle'";
				if ($login_fabrica == 14) echo " onclick='javascript: fnc_pesquisa_peca_lista_intel (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.posicao_$i , \"descricao\")'";
				else echo " onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"descricao\" )'";
				echo " alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></td>\n";
				if ($pergunta_qtde_os_item == 't') {
					echo "<td align='center'><input class='frm' type='text' name='qtde_$i' size='3' value='$qtde[$i]'></td>\n";
				}
			}

			#------------------- Causa do Defeito no Item --------------------
			if ($pedir_causa_defeito_os_item == 't' and $login_fabrica<>20) {
				echo "<td align='center'>";

				echo "<select class='frm' size='1' name='pcausa_defeito_$i'>";
				echo "<option selected></option>";

				$sql = "SELECT * FROM tbl_causa_defeito WHERE fabrica = $login_fabrica ORDER BY codigo, descricao";
				$res = pg_query ($con,$sql) ;

				for ($x = 0 ; $x < pg_num_rows ($res) ; $x++ ) {
					echo "<option ";
					if ($pcausa_defeito[$i] == pg_fetch_result ($res,$x,causa_defeito)) echo " selected ";
					echo " value='" . pg_fetch_result ($res,$x,causa_defeito) . "'>" ;
					echo pg_fetch_result ($res,$x,codigo) ;
					echo " - ";
					echo pg_fetch_result ($res,$x,descricao) ;
					echo "</option>";
				}

				echo "</select>";
				echo "</td>\n";
			}

			#------------------- Defeito no Item --------------------
			if($login_fabrica <> 20){
				echo "<td align='center'>";
				echo "<select class='frm' size='1' name='defeito_$i'>";
				echo "<option selected></option>";

				$sql = "SELECT *
						FROM   tbl_defeito
						WHERE  tbl_defeito.fabrica = $login_fabrica
						AND    tbl_defeito.ativo IS TRUE
						ORDER BY descricao";
				$res = pg_query ($con,$sql) ;

				for ($x = 0 ; $x < pg_num_rows ($res) ; $x++ ) {
					echo "<option ";
					if ($defeito[$i] == pg_fetch_result ($res,$x,defeito)) echo " selected ";
					echo " value='" . pg_fetch_result ($res,$x,defeito) . "'>" ;

					if (strlen (trim (pg_fetch_result ($res,$x,codigo_defeito))) > 0) {
						echo pg_fetch_result ($res,$x,codigo_defeito) ;
						echo " - " ;
					}
					echo pg_fetch_result ($res,$x,descricao) ;
					echo "</option>";
				}

				echo "</select>";
				echo "</td>\n";

				echo "<td align='center'>";
				echo "<select class='frm' size='1' name='servico_$i'>";
				echo "<option selected></option>";

				$sql = "SELECT *
						FROM   tbl_servico_realizado
						WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";

				if ($login_pede_peca_garantia == 't' AND $login_fabrica <> 1 AND $login_fabrica <> 15) {
					$sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
				}

				if ($login_fabrica == 1) {
					//hd 8704 - devolução de dinheiro
                    if ($tipo_atendimento == 334) {
                        $sql .= " AND tbl_servico_realizado.descricao ILIKE 'Substitui%o%'";
                    } else {
                        if ($login_reembolso_peca_estoque == 't') {
                            $sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'Devolução de dinheiro%' AND (tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
                            $sql .= "OR tbl_servico_realizado.descricao ILIKE 'subst%') ";
                            if (strlen($linha) > 0) $sql .= " AND (tbl_servico_realizado.linha = '$linha' OR tbl_servico_realizado.linha is null) ";
                        } else {
                            $sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'Devolução de dinheiro%' AND (tbl_servico_realizado.descricao ILIKE 'troca%' ";
                            $sql .= "OR tbl_servico_realizado.descricao NOT ILIKE 'subst%') ";
                            if (strlen($linha) > 0) $sql .= " AND (tbl_servico_realizado.linha = '$linha' OR tbl_servico_realizado.linha is null) ";
                        }
					}

					$sql .= " AND tbl_servico_realizado.descricao NOT ILIKE 'Troca de produto%' ";
				}
				if($login_fabrica==20) $sql .=" AND tbl_servico_realizado.solucao IS TRUE ";

				$sql .= " AND tbl_servico_realizado.ativo IS TRUE ORDER BY descricao ";
				$res = pg_query ($con,$sql) ;
	//if ($ip == '201.0.9.216') echo $sql;
	$teste=$sql;
				if (pg_num_rows($res) == 0) {
					$sql = "SELECT *
							FROM   tbl_servico_realizado
							WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";

					if ($login_pede_peca_garantia == 't' AND $login_fabrica <> 1 AND $login_fabrica <> 15) {
						$sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
					}

					if ($login_fabrica == 1) {
						if ($login_reembolso_peca_estoque == 't') {
							$sql .= "AND (tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
							$sql .= "OR tbl_servico_realizado.descricao ILIKE 'subst%') ";
						}else{
							$sql .= "AND (tbl_servico_realizado.descricao ILIKE 'troca%' ";
							$sql .= "OR tbl_servico_realizado.descricao NOT ILIKE 'subst%') ";
						}
					}
					if($login_fabrica==20) $sql .=" tbl_servico_realizado.solucao IS TRUE ";

					$sql .=	" AND tbl_servico_realizado.linha IS NULL
							AND tbl_servico_realizado.ativo IS TRUE ORDER BY descricao ";
	// echo $sql;
					$teste2=$sql;
					$res = pg_query ($con,$sql) ;
				}

				for ($x = 0 ; $x < pg_num_rows($res) ; $x++ ) {
					echo "<option ";
					if ($servico[$i] == pg_fetch_result ($res,$x,servico_realizado)) echo " selected ";
					echo " value='" . pg_fetch_result ($res,$x,servico_realizado) . "'>" ;
					echo pg_fetch_result ($res,$x,descricao) ;
					if (pg_fetch_result ($res,$x,gera_pedido) == 't' AND $login_fabrica == 6) echo " - GERA PEDIDO DE PEÇA ";
					echo "</option>";
				}

				echo "</select>";
				echo "</td>\n";
			}
			/*
			if($login_fabrica == 20  and $login_pais == 'CO'){
				echo "<td><input name='adicional_peca_estoque_$i' value='t' class='frm' type='checkbox' ";
				if($adicional[$i]=='t') echo "checked";

				echo "> <font size='1'>Repuesto del estoque</font></td>";
			}
*/
			echo "</tr>\n";

			if(strlen($peca[$i]) > 0 and strpos($lista_peca_negativa,$peca[$i])!==false) {
				echo "<tr>";
				echo "<td colspan='100%' align='center' nowrap><input type='button' value='Clique aqui para ver a relação de OS abertas com essa peça' onclick='javascript:verPecaNegativa(\"$peca[$i]\",$login_posto,$i); return false;' style='width:750px;'><div id='pecaNegativa_$i'  display:none; border: 1px solid #949494;background-color: #b8b7af;width:593px;'></div><input type='hidden' name='validaPecanegativa_$i' id='validaPecanegativa_$i' value='0'></td></tr>";
			}

			$offset = $offset + 1;
		}
// echo "$teste<BR>2: $teste2";
		echo "</table>";
		?>
	</td>

	<td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>
</table>

<?
//ADICIONADO OS_GEO 04/02/2009
if($compressor=='t' or ($tipo_os == 13 )){

//*coloquei 24-01*//
	// por km é 0,40 centavos
	// por hora é 24 reais, 0,40 por minuto
	//COMPRESSOR TEM UM DIFERENCIAL


	/*POR ALGUM MOTIVO ESTA PERDENDO O TIPO DE ATENDIMENTO*/
	$sql = "SELECT  tbl_os.tipo_atendimento
			FROM    tbl_os
			WHERE   tbl_os.os = $os";
	$res = pg_query ($con,$sql) ;


	if (@pg_num_rows($res) > 0) {
		$tipo_atendimento = pg_fetch_result($res,0,tipo_atendimento) ;
	}

	$sql_tec = "SELECT tecnico from tbl_os_extra where os=$os";
	$res_tec = pg_query($con,$sql_tec);
	$tecnico            = trim(@pg_fetch_result($res_tec,0,tecnico));

	if(strlen($msg_erro) >0) {
		$tecnico = $_POST['tecnico'];
	}


	if($compressor=='t' or ($tipo_os == 13 and ($tipo_atendimento == 65 or $tipo_atendimento == 69))) {
		$sql_posto = "SELECT contato_endereco AS endereco,
						contato_numero   AS numero  ,
						contato_bairro   AS bairro  ,
						contato_cidade   AS cidade  ,
						contato_estado   AS estado  ,
						contato_cep      AS cep     ,
						consumidor_endereco         ,
						consumidor_numero           ,
						consumidor_bairro           ,
						consumidor_cidade           ,
						consumidor_estado           ,
						consumidor_cep
					FROM tbl_os
					JOIN tbl_posto_fabrica USING(posto)
					WHERE tbl_os.posto   = $login_posto
					AND   tbl_os.os = $os
					AND   tbl_os.fabrica = $login_fabrica
					AND   tbl_posto_fabrica.fabrica = $login_fabrica";

		$res_posto = pg_query($con,$sql_posto);
		if(pg_num_rows($res_posto)>0) {
			$endereco_posto = pg_fetch_result($res_posto,0,endereco).', '.pg_fetch_result($res_posto,0,numero).' '.pg_fetch_result($res_posto,0,bairro).' '.pg_fetch_result($res_posto,0,cidade).' '.pg_fetch_result($res_posto,0,estado);
			$cep_posto = pg_fetch_result($res_posto,0,cep);
			$endereco_consumidor = pg_fetch_result($res_posto,0,consumidor_endereco).', '.pg_fetch_result($res_posto,0,consumidor_numero).' '.pg_fetch_result($res_posto,0,consumidor_bairro).' '.pg_fetch_result($res_posto,0,consumidor_cidade).' '.pg_fetch_result($res_posto,0,consumidor_estado);
			$cep_consumidor = pg_fetch_result($res_posto,0,consumidor_cep);
			if(strlen($distancia_km)==0) $distancia_km=0;
		}

		echo "<BR>";
		echo "<table width='600' border='1' align='center'  cellpadding='1' cellspacing='3 class='border'>";
			echo "<tr>";
			echo "<td nowrap colspan='6' class='menu_top'><B><font size='2' face='Geneva, Arial, Helvetica, san-serif'>OUTRAS DESPESAS</font></b></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td nowrap class='menu_top' rowspan='2'>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Data da visita</font></td>";
			if($tipo_os <> 13){
				echo "<td nowrap class='menu_top' rowspan='2'>
					<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Hora início</font></td>";
				echo "<td nowrap class='menu_top' rowspan='2'>
					<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Hora fim</font></td>";
			}
			echo "<td nowrap class='menu_top' rowspan='2'>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>KM</font></td>";
			if($tipo_os == 13){
				echo "<td nowrap class='menu_top' rowspan='2'>
					<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Qtd. Produto<br>Atendido</font></td>";
			}
			echo "<td nowrap class='menu_top' colspan='2'>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Despesas Adicionais</font></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td nowrap class='menu_top'>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Valor</font></td>";
			echo "<td nowrap class='menu_top'>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Justificativa</font></td>";
			echo "</tr>";
			$sql  = "SELECT tbl_os_visita.os_visita ,
					to_char(tbl_os_visita.data,'DD/MM/YYYY')               AS data             ,
					to_char(tbl_os_visita.hora_chegada_cliente, 'HH24:MI') AS hora_chegada_cliente ,
					to_char(tbl_os_visita.hora_saida_cliente, 'HH24:MI')   AS hora_saida_cliente   ,
					tbl_os_visita.km_chegada_cliente                                               ,
					tbl_os_visita.justificativa_valor_adicional                                    ,
					tbl_os_visita.valor_adicional                                                  ,
					tbl_os_visita.qtde_produto_atendido
				FROM    tbl_os_visita
				WHERE   $sql_aux_os      = $aux_os
				ORDER BY tbl_os_visita.os_visita;";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);


			for ($y=0;$qtde_visita>$y;$y++){
				$os_visita                = trim(@pg_fetch_result($res, $y, os_visita));
				$visita_data              = trim(@pg_fetch_result($res, $y, data));
				$hr_inicio                = trim(@pg_fetch_result($res, $y, hora_chegada_cliente));
				$hr_fim                   = trim(@pg_fetch_result($res, $y, hora_saida_cliente));
				$visita_km                = trim(@pg_fetch_result($res, $y, km_chegada_cliente));
				$qtde_produto_atendido    = trim(@pg_fetch_result($res, $y, qtde_produto_atendido));
				$justificativa_adicionais = trim(@pg_fetch_result($res, $y, justificativa_valor_adicional));
				$valores_adicionais       = trim(@pg_fetch_result($res, $y, valor_adicional));
				$qtde_produto_atendido    = trim(@pg_fetch_result($res, $y, qtde_produto_atendido));

				if(!empty($msg_erro)) {
					$os_visita                = $_POST['os_visita_'                . $y];
					$visita_data              = $_POST['visita_data_'              . $y];
					$hr_inicio                = $_POST['visita_hr_inicio_'         . $y];
					$hr_fim                   = $_POST['visita_hr_fim_'            . $y];
					$visita_km                = $_POST['visita_km_'                . $y];
					$qtde_produto_atendido    = $_POST['qtde_produto_atendido_'    . $y];
					$valores_adicionais       = $_POST['valores_adicionais_'       . $y];
					$justificativa_adicionais = $_POST['justificativa_adicionais_' . $y];
				}

				if(strlen($visita_km_erro) > 0) {
					if(strlen($_POST['visita_km_'.$y]) > 0) {
						$visita_km = $visita_km_erro;
					}
				}

				echo "<tr>";
				echo "<td nowrap align='center' width='200'>";
				echo "<INPUT TYPE='text' NAME='visita_data_$y' value='$visita_data' size='12' maxlength='10' class='frm' onKeyUp=\"formata_data_visita(this.value, 'frm_os', $y)\";>";
				echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>dd/mm/aaaa</font>";
				echo "</td>";
				if($tipo_os <> 13){ # HD 174998
					echo "<td nowrap align='center'>";
					echo "<INPUT TYPE='text' NAME='visita_hr_inicio_$y' value='$hr_inicio' size='5' maxlength='5' class='frm' onKeyUp=\"formata_cnpj(this.value, 'frm_os', $y)\";>";
					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>09:23</font>";
					echo " </td>";

					echo "<td nowrap align='center'>";
					echo "<INPUT TYPE='text' NAME='visita_hr_fim_$y' value='$hr_fim' size='5' maxlength='5' class='frm' onKeyUp=\"formata_cnpj2(this.value, 'frm_os', $y)\";>";
					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>14:51</font>";
					echo "</td>";
				}
				echo "<td nowrap align='center'>";
				echo "<input type='hidden' name='km_conferencia_$y' id='km_conferencia_$y'>";
				echo "<INPUT TYPE='text' NAME='visita_km_$y' id='visita_km_$y' onfocus=\"initialize('','visita_km_$y','km_conferencia_$y')\" value='$visita_km' size='4' maxlength='4' class='frm'>";
				echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Km</font>";
				echo "</td>";
				if($tipo_os ==13){
					$sql = "
						SELECT  count(os_revenda_item) as qtde_itens_geo
						FROM    tbl_os_revenda_item
						WHERE  $sql_aux_os      = $aux_os ";
					$res_count = pg_query ($con,$sql) ;

					$qtde_itens_geo = pg_fetch_result($res_count,0,qtde_itens_geo);
					/*HD: 72006 - primeiro registro se estiver vazio, então coloca a qtde de itens da OS_geo*/
					if($y == 0 and strlen($qtde_produto_atendido) ==0){
						$qtde_produto_atendido = $qtde_itens_geo;
					}

					echo "<td nowrap align='center'>";
					echo "<INPUT TYPE='text' NAME='qtde_produto_atendido_$y' value='$qtde_produto_atendido' size='4' maxlength='4' class='frm'>";
					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'></font>";
					echo "</td>";
				}

				echo "<td nowrap align='center'>";
				echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>R$ </font>";
				echo "<INPUT TYPE='text' onKeyDown=\"FormataValor(this,11, event)\"; NAME='valores_adicionais_$y' value='$valores_adicionais' size='5' maxlength='5' class='frm'>";
				echo "</td>";

				echo "<td nowrap align='center'>";
				echo "<INPUT TYPE='text' NAME='justificativa_adicionais_$y' value='$justificativa_adicionais' size='10' maxlength='50' class='frm'>";
				echo "</td>";

				echo "<input type='hidden' name='os_visita_$y' value='$os_visita'>";
				echo "</tr>";
			}
		echo "</table> <BR>";
	}
	include "gMapsKeys.inc";
?>
	<?php if ($login_fabrica == 1) : ?>

			<div style="width:300px; margin:auto; display:none;" id="anexaDespesa">

				<label for="foto_despesas" style="font-size:12px;">Anexar Comprovante de Despesas</label><br />
				<input type="file" name="foto_despesas" id="foto_despesas" />

			</div>
	<?php

		endif;
	?>

	<center>
	<div id="mapa3"></div><br>
	<div id="mapa2" style=" width:500px; height:10px;position:absolute;visibility:hidden;">
	<a href='javascript:escondermapa();'>Fechar Mapa</a>
	</div><br>
	<div id="mapa" style=" width:600px; height:400px;visibility:hidden;position:absolute;border: 1px #FF0000 solid; "></div>
	<div id="trajeto" style="width: 400px; text-align:right;position:static"></div>
	</center>
	<script src="http://maps.google.com/maps?file=api&v=3&key=<?=$gAPI_key?>" type="text/javascript"></script>
	<script language="javascript">
	var map;
	function initialize(busca_por,visita,confere){
		// Carrega o Google Maps
		$('#trajeto').html('');
		var visita = document.getElementById(visita);
		var confere = document.getElementById(confere);
		if (GBrowserIsCompatible()) {
			map = new GMap2(document.getElementById("mapa"));
			map.setCenter(new GLatLng(-25.429722,-49.271944), 11)
			map.addControl(new GLargeMapControl3D());
			gdir = new GDirections(map, document.getElementById("trajeto"));

			 var dir = new GDirections(map);

			var pt1 = '<?=$cep_posto?>';
			var pt2 = '<?=$cep_consumidor?>';

			if (pt1.length != 8 || pt2.length !=8) {
				busca_por = 'endereco';
			}else{
				pt1 = pt1.substr(0,5) + '-' + pt1.substr(5,3);
				pt2 = pt2.substr(0,5) + '-' + pt2.substr(5,3);
			}


			if (busca_por == 'endereco'){
				var pt1 = '<?=$endereco_posto?>';
				var pt2 = '<?=$endereco_consumidor?>';
			}

			dir.loadFromWaypoints([pt1,pt2], {locale:"pt-br", getSteps:true});
			GEvent.addListener(dir,"load", function() {
				for (var i=0; i<dir.getNumRoutes(); i++) {
						var route = dir.getRoute(i);
						var dist = route.getDistance()
						var x = dist.meters*2/1000;
						var y = x.toString().replace(".",",");
						var valor_calculado = parseFloat(x);

						if (valor_calculado==0 && busca_por != 'endereco'){
							initialize('endereco','','');
							return false;
						}

						if (valor_calculado==0 && busca_por == 'endereco'){
							$('#mapa3').html('');
							return false;
						}
						confere.value = x;
						$('#mapa3').html('Distância calculada <a href= "javascript:vermapa();">Ver mapa</a>').addClass('mensagem');
						setDirections(""+pt1, ""+pt2, "pt-br");
				 }
			});
			GEvent.addListener(dir,"error", function() {
				return false;
			});

		}
	}

	function vermapa(){
		document.getElementById("mapa").style.visibility="visible";
		document.getElementById("mapa2").style.visibility="visible";
	}
	function escondermapa(){
		document.getElementById("mapa").style.visibility="hidden";
		document.getElementById("mapa2").style.visibility="hidden";
	}

	function setDirections(fromAddress, toAddress, locale) {
		gdir.load("from: " + fromAddress + " to: " + toAddress,
		{ "locale": locale , "getSteps":true});
	}

	</script>


<?
	echo "<table class='border' width='620' align='center' border='1' cellpadding='1' cellspacing='3'>";
	echo "<tr>";
		echo "<td class='menu_top'>Relatório do Técnico</td>";
	echo "</tr>";
	echo "<tr>";
		echo "<TD class='table_line'><TEXTAREA NAME='tecnico' ROWS='5' COLS='85'>$tecnico</TEXTAREA></TD>";
	echo "</tr>";
	echo "</table>";
	echo "<br/>";
}

if ($login_fabrica == 1) {

$sql = "SELECT * FROM tbl_laudo_tecnico_os WHERE os = $os and titulo !~'Pesquisa de satis';";
$res = pg_query($con,$sql);

if (pg_num_rows($res) == 0) {

	$sql = "SELECT tbl_laudo_tecnico.*
			FROM tbl_laudo_tecnico
			WHERE produto = $produto_os
			ORDER BY ordem;";

	$res = pg_query($con, $sql);

	if (pg_num_rows($res) == 0) {
		if (strlen($produto_familia) > 0 ){
			$sql = "SELECT tbl_laudo_tecnico.* FROM tbl_laudo_tecnico WHERE familia = $produto_familia ORDER BY ordem;";
			$res = pg_query($con,$sql);
		}
	}

	if (pg_num_rows($res) > 0) {?>

		<br />

		<table align='center' border='1' class='border' cellpadding='1' cellspacing='3'>
			<tr>
				<td colspan='3' style='font-size: 12px' align='center' class='menu_top'><b>LAUDO TÉCNICO</b></td>
			</tr>
			<tr>
				<td class='menu_top'>CHECK LIST</td>
				<td class='menu_top'>AFIRMAÇÕES</td>
				<td class='menu_top'>RESPOSTAS</td>
			</tr><?php

			for ($i = 0; $i < pg_num_rows($res); $i++) {

				$laudo      = pg_fetch_result($res, $i, 'laudo_tecnico');
				$titulo     = pg_fetch_result($res, $i, 'titulo');
				$afirmativa = pg_fetch_result($res, $i, 'afirmativa');
				$observacao = pg_fetch_result($res, $i, 'observacao');

				#Recarrega o form
				$afirmativa_laudo = $_POST["afirmativa_$laudo"];
				$observacao_laudo = $_POST["observacao_$laudo"]; ?>

				<tr>

					<td align='left'><font size='-2'><b><? echo "$titulo";?></b></font></td><?php

					if ($afirmativa == 't') {?>
						<td align='left' nowrap><font size='-2'><INPUT TYPE="radio" NAME="<? echo "afirmativa_$laudo";?>" <? if($afirmativa_laudo == 't') echo "checked='checked'"; ?> value='t'>Sim <INPUT TYPE="radio" NAME="<? echo "afirmativa_$laudo";?>" <? if($afirmativa_laudo == 'f') echo "checked='checked'"; ?> value='f'> Não</font></td><?php
					} else {?>
						<td align='left'>&nbsp;</td><?php
					}

					if ($observacao == 't') {?>
						<td align='left'><font size='-2'><INPUT TYPE="text" size='50' value='<? echo $observacao_laudo; ?>' NAME="<? echo "observacao_$laudo";?>"></font></td><?php
					} else {?>
						<td align='left'>&nbsp;</td><?php
					}?>

				</tr><?php

			}?>

		</table><?php

	}

}

$sql = "SELECT tbl_laudo_tecnico_os.*
		FROM tbl_laudo_tecnico_os
		WHERE os = $os
		and titulo !~'Pesquisa de satis';";
$res = pg_query($con,$sql);
if(pg_num_rows($res) > 0){
	echo "<br>";
	echo "<table align='center' border='0'>";
	echo "<tr>";
	echo "<td colspan='3' style='font-size: 10px; color:#939393;' align='center'><b>Laudo Cadastrado</b></td>";
	echo "</tr>";
	for($i=0;$i<pg_num_rows($res);$i++){
		$laudo      = pg_fetch_result($res,$i,laudo_tecnico_os);
		$titulo     = pg_fetch_result($res,$i,titulo);
		$afirmativa = pg_fetch_result($res,$i,afirmativa);
		$observacao = pg_fetch_result($res,$i,observacao);

		$cor = "#F7F5F0";
		if ($i % 2 == 0)
		{
			$cor = '#F1F4FA';
		}

		echo "<tr bgcolor='$cor'>";
			echo "<td align='left'><font size='-2' color='#939393'><b>$titulo</b></font></td>";
			if(strlen($afirmativa) > 0){
				echo "<td align='left' nowrap><font size='-3'><INPUT TYPE='radio' NAME='afirmativa2_$laudo'"; if($afirmativa == 't') {echo "checked='checked'";} echo " value='t'>Sim <INPUT TYPE='radio' NAME='afirmativa2_$laudo'"; if($afirmativa == 'f') {echo "checked='checked'";} echo "value='f'> Não</font></td>";
			}else{
				echo "<td align='left'>&nbsp;</td>";
			}
			if(strlen($observacao) > 0){
				echo "<td align='left'><font size='-3'>$observacao</font></td>";
			}else{
				echo "<td align='left'>&nbsp;</td>";
			}
		echo "</tr>";
	}
}
echo "</table>";
}
	// INICIO ARQUIVOS ANEXOS
	if ($anexaNotaFiscal) {
		p_echo("&nbsp;");		
		$temImg = temNF($os, 'count');

		if($temImg) {
			echo temNF($os, 'link');
			echo $include_imgZoom;
		}
		if (($anexa_duas_fotos and $temImg < LIMITE_ANEXOS) or $temImg == 0) {
			echo "<div id='foto_nf'>";//hd_chamado=2808833
				echo $inputNotaFiscal;
			echo "</div>";//hd_chamado=2808833
		}
		p_echo("&nbsp;");
	}
	// FIM ARQUIVOS ANEXOS
	?>

<?php
	if($login_fabrica == 1){
	$sql =	"SELECT tbl_comunicado.comunicado, tbl_produto.referencia, tbl_produto.descricao
			FROM tbl_comunicado
			JOIN tbl_produto ON tbl_produto.produto = tbl_comunicado.produto
			JOIN tbl_os      ON tbl_os.produto = tbl_produto.produto
			WHERE tbl_comunicado.fabrica = $login_fabrica
			AND   tbl_os.os = $os
			AND   tbl_comunicado.obrigatorio_os_produto IS TRUE
			ORDER BY tbl_comunicado.data DESC;";

	$res_comun = pg_exec($con,$sql);

	if (pg_numrows($res_comun) > 0){
		$comunicado_black = pg_result($res_comun,0,comunicado);
		$produto_referencia = pg_result($res_comun,0,referencia);
		$produto_descricao = pg_result($res_comun,0,descricao);

		echo "<br><center><a href='comunicado_produto_consulta.php?opcao=1&produto_referencia=$produto_referencia&produto_descricao=$produto_descricao&btn_acao=consultar' target='_blank'>Comunicados do produto</a></center> <br>";
	}
}
?>
<table width='650' align='center' border='1' cellspacing='0' cellpadding='5'>
<? if ($login_fabrica == 19) { ?>
<tr>
	<?//retirado por Wellington - chamado 1572 (Natanael)?>
	<?/*
	<td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
		<br>
		<FONT SIZE="1">Valores Adicionais:</FONT>
		<br>
		<FONT SIZE="1">R$ </FONT>
		<INPUT TYPE="text" NAME="valores_adicionais" value="<? echo $valores_adicionais ?>" size="10" maxlength="10" class="frm">
		<br><br>
	</td>
	<td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
		<br>
		<FONT SIZE="1">Justificativa dos Valores Adicionais:</FONT>
		<br>
		<INPUT TYPE="text" NAME="justificativa_adicionais" value="<? echo $justificativa_adicionais ?>" size="30" maxlength="100" class="frm">
		<br><br>
	</td>
	*/?>
	<td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
		<br>
		<FONT SIZE="1">Quilometragem:</FONT>
		<br>
		<INPUT TYPE="text" NAME="qtde_km" value="<? echo $qtde_km ?>" size="5" maxlength="10" class="frm">
		<br><br>
	</td>
</tr>
<? } ?>

<tr>
	<td height="27" valign="middle" align="center" colspan="3" bgcolor="#FFFFFF">
		<br>
		<?
		/*OS GEO - BLOQUEAR O CAMPO OBS*/
		$readonly = "";
		if($tipo_os == 13){
			$readonly = " readonly = 'yes' ";
		}
		?>
		<FONT SIZE="1"><?if($sistema_lingua == 'ES') echo "Observación";else echo "Observação:";?></FONT> <INPUT TYPE="text" NAME="obs" value="<? echo $obs; ?>" size="70" maxlength="255" class="frm" <? echo $readonly ;?> >
		<br><br>
		<FONT SIZE="1" COLOR="#ff0000">
		<?if($sistema_lingua == 'ES'){
			echo "El campo \"Observación\" es solamente para el control del servicio autorizado<br>El fabricante no es responsable por los datos digitados";
		}else{
			if($login_fabrica<>19){//hd3335
			echo " campo \"Observação\" é somente para o controle do posto autorizado. <br>O fabricante não se responsabilizará pelos dados aqui digitados.";
			}
		}?>
		</FONT>
		<br><br>
	</td>
</tr>

<? if (strlen ($orientacao_sac) > 0) { ?>
<tr>
	<td valign="middle" align="center" colspan="3" bgcolor="#eeeeee">
		<FONT SIZE="1"><b>Orientação do SAC ao Posto Autorizado</b></FONT>
		<p>
		<? echo $orientacao_sac ?>
		<br><br>
	</td>
</tr>
<? } ?>

<tr>
	<td align='center'>
		<? if($login_fabrica == 20){ //hd 88308 waldir
		?>
		<font size=-1>
		<? if($sistema_lingua=='ES'){ ?>
			Cierre de OS
		<?}else{ ?>
			Fechamento de OS
		<? } ?>
		&nbsp;&nbsp;</font>
		<input type='text' maxlength='18' name='data_fechamento' id='data_fechamento_hora' value="<? echo $data_fechamento; ?>" >
		<?}?>
	</td>
</tr>
<tr>
	<td height="27" valign="middle" align="center" colspan="3" bgcolor="#FFFFFF">
		<input type="hidden" name="btn_acao" value="">
		<?php if ($sistema_lingua=='ES') {?>
				<img src='imagens/btn_guardar.gif' name="nome_frm_os" class="verifica_servidor" onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; }" ALT="Guardar itenes de la orden de servicio" border='0' style="cursor:pointer;">
		<?php } else { 
			  	if ($login_fabrica == 1 && $alterarOS == true) { ?>
					<img src='imagens/alterar_e_voltar.png' name="nome_frm_os" class="verifica_servidor" onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; }" ALT="Gravar itens da Ordem de Serviço" border='0' style="cursor:pointer;">	
		<?php	} else { ?> 
					<img src='imagens/btn_gravar.gif' name="nome_frm_os" class="verifica_servidor" onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; }" ALT="Gravar itens da Ordem de Serviço" border='0' style="cursor:pointer;">
		<?php 	} 
			  }?>
	</td>

</tr>

<? // adicionado por Fabio - Verifica se a OS está com status de intervencao, entao nao deixa mexer - 29/12/2006
if ($login_fabrica == 3) {

	$sql = "SELECT status_os
			FROM tbl_os_status
			WHERE os = $os
			ORDER BY data DESC LIMIT 1";

	$res = @pg_query ($con,$sql);

	if (pg_num_rows($res) > 0) {

		$status_os = pg_fetch_result($res, 0, 'status_os');
		//status de intervencao da fabrica ass. tec.. ENTÃO: Bloqueia os campos

		if ($status_os == '62') {

			echo '<script language="JavaScript">
					for (i=9;i<document.frm_os.elements.length;i++){
						//if(document.frm_os.elements[i].type == "checkbox")
						document.frm_os.elements[i].disabled="true";
					}
					//document.getElementById(\'botao_gravar\').style.visibility="hidden";
					var tmp = document.getElementsByTagName(\'img\');
					for (i=0;i<tmp.length;i++){
						if (tmp[i].src=="/assist/imagens/btn_lista.gif")
							tmp[i].style.visibility="hidden";
						if (tmp[i].src=="/assist/imagens/btn_lupa.gif")
							tmp[i].style.visibility="hidden";
					}
				</script>';

		}

	}

}?>

</form>

</table>

<? include "rodape.php";?>

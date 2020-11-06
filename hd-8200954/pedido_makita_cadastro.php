<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'token_cookie.php';

$token_cookie = $_COOKIE['sess'];
$cookie_login = get_cookie_login($token_cookie);

if($login_fabrica <> 42){
	header("Location: pedido_cadastro.php");
}

include_once 'class/communicator.class.php';
function validaRegramakita($condicao,$referencia,$posto,$linha) {
	$_GET['condicao'] = $condicao;
	$_GET['produto_referencia'] = $referencia;
	$_GET['posto'] = $posto;
	$_GET['linha_form'] = $linha;
	$_REQUEST['linha_form'] = $linha;
	ob_start();
	include "makita_valida_regras.php";
    ob_get_clean();
	$makita_preco = number_format ($makita_preco,2,".",".");
	return $makita_preco;
}

function enviaComunicadoFilial($email, $mensagem, $pedido) {
	global $externalId;

	$communicator = new TcComm($externalId, "noreply@telecontrol.com.br");
	try {
		$response = $communicator->sendMail(
			$email,
			utf8_encode("Telecontrol - Pedido de Peças #" . $pedido . " para Retirada"),
			$mensagem
		);
	} catch (Exception $e) {
		return false;
	}

	return true;
}

$login_bloqueio_pedido = $cookie_login['cook_bloqueio_pedido'];

$sql = "SELECT pedido_faturado FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
$res = pg_query ($con,$sql);
if (pg_fetch_result ($res,0,0) == 'f') {
	include "cabecalho.php";
	echo "<H4>CADASTRO DE PEDIDOS FATURADOS BLOQUEADO</H4>";
	include "rodape.php";
	exit;
}

#-------- Libera digitação de PEDIDOS pelo distribuidor ---------------
$posto = $login_posto ;
$limit_pedidos = 2;

/* Suggar liberou até 4 pedido em garantia - HD 22397 23862*/ // HD 33373 // HD 60077
$limite_posto = array(720,20235,476);

if($login_posto==2474){
	$limit_pedidos = 4;
}

if($login_posto==19566){
	$limit_pedidos = 99;
}

if (!empty($_POST) && array_key_exists('ajax', $_POST)) {
	switch ($_POST['ajax']) {
		case 'getTransporte':
			$filial = $_POST['filial'];

			$query_filial_transporte = "
				SELECT
					tbl_posto_filial.parametros_adicionais
				FROM tbl_posto_filial
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto_filial.posto
				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
				AND tbl_posto_filial.filial_posto = {$filial}
				AND tbl_posto_fabrica.posto = {$login_posto}
				LIMIT 1
			";

			$res_filial_transporte = pg_query($con, $query_filial_transporte);
			if (strlen(pg_last_error()) >= 1) {
				$response = ['exception' => pg_last_error()];
				break;
			}

			$parametros_filial = pg_fetch_result($res_filial_transporte, 0, 'parametros_adicionais');
			$response = json_decode($parametros_filial, true);

			break;
		case 'pagarFrete':
			$cook_pedido = $_POST['pedido'];

			$query_pedido = "
				SELECT obs
				FROM tbl_pedido
				WHERE fabrica = 42
				AND pedido = {$cook_pedido}
			";

			$res_pedido = pg_query($con, $query_pedido);
			if (strlen(pg_last_error()) >= 1) {
				$response = ['exception' => pg_last_error()];
				break;
			}

			$obs = pg_fetch_result($res_pedi, 0, "obs");
			$obs = json_decode($obs, true);

			$obs["transporte"] = "PADRAO";
			unset($obs["responsavel_retirada"]);

			$obs = json_encode($obs);

			$update_pedido = "
				UPDATE
					tbl_pedido
				SET obs = '{$obs}'
				WHERE fabrica = 42
				AND pedido = {$cook_pedido};
			";

			$res_update = pg_query($con, $update_pedido);
			if (strlen(pg_last_error()) >= 1) {
				$response = ['exception' => pg_last_error()];
				break;
			}

			$response = ["message" => "success"];
			break;

	}

	echo json_encode($response);
	exit;
}

if(!empty($_FILES['arquivo_txt']['name']) && !$_POST['envia_imagem']){
	$arquivo = $_FILES['arquivo_txt'];

	if($arquivo['erro'] > 0){
		echo "<div>Ocorreu um erro ao enviar esse arquivo, por favor tente de novo</div>";
	}else{
		$file = file($arquivo['tmp_name']);
		$total_arquivo = count($file);

		$dadosPedido[0] = $file[0];
		for($i=1;$i<$total_arquivo;$i++){

			#Se for o último do laço
			if($i == ($total_arquivo-1)){

				$array1 = array("\t","\n");
				$array2 = array("","");
				$file[$i] = str_replace($array1,$array2,$file[$i]);
				$dadosPedido[$i] = $file[$i];
				continue;
			}

			#Pega Referencia e quantidade para fazer a somatória
			list($referencia,$qtd) = explode(";",$file[$i]);

		}

		echo json_encode($file);
	}
	exit;
}

$btn_acao = strtolower ($_POST['btn_acao']);

$msg_erro = "";
$msg_debug = "";
$qtde_item = 40;

if($login_posto==2474){
	$qtde_item = 70;
}

if( !$_GET[ 'delete' ]) {
 $sql = "SELECT  tbl_pedido.pedido                                              ,
				tbl_pedido.pedido_blackedecker                                 ,
				tbl_pedido.condicao                                            ,
				tbl_pedido.pedido_cliente                                      ,
				tbl_pedido.tipo_pedido                                         ,
				tbl_tipo_pedido.descricao as tipo_pedido_descricao             ,
				tbl_condicao.descricao                                         ,
				tbl_pedido.seu_pedido,
				to_char(controle_exportacao,'DD/MM/YYYY') AS controle_exportacao
		FROM    tbl_pedido
		JOIN    tbl_condicao USING(condicao)
		JOIN    tbl_tipo_pedido USING(tipo_pedido)
		WHERE   tbl_pedido.exportado           IS NULL
		AND     tbl_pedido.admin               IS NULL
		AND     tbl_pedido.posto             = $login_posto
		AND     tbl_pedido.fabrica           = $login_fabrica
		AND     tbl_pedido.finalizado          IS NULL
		AND (tbl_pedido.status_pedido is null or tbl_pedido.status_pedido <>14);";

//echo nl2br($sql);
$res = pg_query ($con,$sql);

if (pg_num_rows($res) > 0) {
	$cook_pedido           = trim(pg_fetch_result($res,0,pedido));
	$condicao              = trim(pg_fetch_result($res,0,condicao));
	$descricao_condicao    = trim(pg_fetch_result($res,0,descricao));
	$pedido_cliente        = trim(pg_fetch_result($res,0,pedido_cliente));
	$tipo_pedido           = trim(pg_fetch_result($res,0,tipo_pedido));
	$tipo_pedido_descricao = trim(pg_fetch_result($res,0,tipo_pedido_descricao));
	$pedido_programado     = trim(pg_fetch_result($res,0,controle_exportacao));
}

}

if ($btn_acao == "gravar"){


	if ( strlen( $cook_pedido ) ==0 ){
		unset( $cook_pedido );
		setcookie ('cook_pedido');
		$cook_pedido = "";
	}
	$pedido            = $_POST['pedido'];
	$condicao          = $_POST['condicao'];
	$tipo_pedido       = $_POST['tipo_pedido'];
	$pedido_cliente    = $_POST['pedido_cliente'];
	$transportadora    = $_POST['transportadora'];
	$linha             = $_POST['linha'];
	$observacao_pedido = $_POST['observacao_pedido'];
	$qtde_item         = $_POST['qtde_item'];
	$filial_posto      = $_POST['filial_posto'];
	$pedido_programado = $_POST['pedido_programado'];
	$qtdes             = $_POST['qtde'];
	$precos            = $_POST['preco'];
	$peca_referencias  = $_POST['peca_referencia'];
	$transporte = $_POST['transporte_filial'];
	
	$observacao_pedido = ["observacao" => $observacao_pedido];

	if (empty($transporte)) {
		$msg_erro = "Selecione o tipo de transporte.";
	} else {
		$observacao_pedido["transporte"] = $transporte;
		if ($transporte === "RETIRA") {
			if (
				empty($_POST['transporte_responsavel_nome'])
				|| empty($_POST['transporte_responsavel_rg'])
				|| empty($_POST['transporte_responsavel_wapp'])
			) {
				$msg_erro = "Forneça os dados do responsável pela retirada."; 
			}

			$responsavel_retirada = [
				"nome" 	=> utf8_encode($_POST['transporte_responsavel_nome']),
				"rg" 	=> $_POST['transporte_responsavel_rg'],
				"wapp" 	=> $_POST['transporte_responsavel_wapp']
			];

			$observacao_pedido["responsavel_retirada"] = $responsavel_retirada;
		}
	}

	$observacao_pedido = json_encode($observacao_pedido);

	if(empty($pedido_programado)){
		$aux_pedido_programado = "null";
	}else{
		list($d,$m,$y) = explode("/",$pedido_programado);
		if(!checkdate($m, $d, $y)){
			$msg_erro = "Data inválida";
		}else{
			$aux_pedido_programado = "$y-$m-$d";
			if(strtotime(date('Y-m-d')) == strtotime($aux_pedido_programado)){
				$msg_erro = "Data não pode ser igual a data atual";
			}

			if(strtotime($aux_pedido_programado) < strtotime(date('Y-m-d'))){
				$msg_erro = "Data não pode ser inferior a data atual";
			}

			$aux_pedido_programado = "'$y-$m-$d'";
		}
	}

	/*
	 *
	 * Essa rotina foi incluida nesse ponto do script, para nao pertmitir que seja
	 * criado um cookie com conteúdo vazio, caso seja selecionada a opcao 'gravar' sem o preenchimento de nenhum item.
	 *
	 */

	for ( $i = 0; $i < 30 ; $i++ ) {
		$peca_descricao[$i] = trim( $_POST[ "peca_ref_descricao" ] );
		echo $peca_descricao[$i];
	}

	if( empty( $peca_descricao ) )
	{
		//	$msg_erro = "Não foi digitada a descrição ou referência do produto";
	}
	$qtde = trim( $_POST[ 'qtde_0' ] );
	if (strlen ($qtde) > 0 AND ( strlen($qtde) == 0 OR $qtde < 1 ) )	{
		$msg_erro = "Não foi digitada a quantidade para a Peça $peca_referencia.";
		$linha_erro = $i;
	}
	$aux_condicao = (strlen($condicao) == 0) ? "null" : $condicao ;
	if(strlen($condicao) == 0){
		$msg_erro = "Favor digitar a condição de pagamento!";
	}

	if (empty($filial_posto)){
		$msg_erro = "Escolha alguma filial";
	}

	$aux_pedido_cliente = (strlen($pedido_cliente) == 0) ? "null" : "'". $pedido_cliente ."'";
	$aux_transportadora = (strlen($transportadora) == 0) ? "null" : $transportadora ;
	$aux_observacao_pedido = (strlen($observacao_pedido) == 0) ? "null" : "'$observacao_pedido'" ;

	if (strlen($tipo_pedido) <> 0) {
		$aux_tipo_pedido = "'". $tipo_pedido ."'";
	}else{
		$sql = "SELECT	tipo_pedido
			FROM	tbl_tipo_pedido
			WHERE	descricao IN ('Faturado','Venda')
			AND		fabrica = $login_fabrica";
		$res = pg_query ($con,$sql);
		$aux_tipo_pedido = "'". pg_fetch_result($res,0,tipo_pedido) ."'";
	}

	if (strlen($linha) == 0) {
		$aux_linha = "null";
	}else{
		$aux_linha = $linha ;
	}

	#----------- PEDIDO digitado pelo Distribuidor -----------------
	$digitacao_distribuidor = "null";

	if ($distribuidor_digita == 't'){
		$codigo_posto = strtoupper (trim ($_POST['codigo_posto']));
		$codigo_posto = str_replace (" ","",$codigo_posto);
		$codigo_posto = str_replace (".","",$codigo_posto);
		$codigo_posto = str_replace ("/","",$codigo_posto);
		$codigo_posto = str_replace ("-","",$codigo_posto);

		if (strlen ($codigo_posto) > 0) {
			$sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$codigo_posto' AND credenciamento = 'CREDENCIADO'";
			$res = @pg_query($con,$sql);
			if (pg_num_rows ($res) <> 1) {
				$msg_erro = "Posto $codigo_posto não cadastrado";
				$posto = $login_posto;
			}else{
				$posto = pg_fetch_result ($res,0,0);
				if ($posto <> $login_posto) {
					$sql = "SELECT posto FROM tbl_posto_linha WHERE posto = $posto AND distribuidor = $login_posto AND linha = $linha";
					$res = @pg_query($con,$sql);
					if (pg_num_rows ($res) <> 1) {
						$msg_erro = "Posto $codigo_posto não pertence a sua região";
						$posto = $login_posto;
					}else{
						$posto = pg_fetch_result ($res,0,0);
						$digitacao_distribuidor = $login_posto;
					}
				}
			}
		}
	}
	if(strlen($msg_erro)==0){
		$res = pg_query ($con,"BEGIN TRANSACTION");
		if (strlen ($pedido) == 0 and strlen($cook_pedido)==0) {
			#-------------- insere pedido ------------
			$sql = "INSERT INTO tbl_pedido (
				posto          ,
				fabrica        ,
				condicao       ,
				pedido_cliente ,
				transportadora ,
				linha          ,
				tipo_pedido    ,
				digitacao_distribuidor,
				filial_posto,
				obs,
				controle_exportacao
				$sql_campo
			) VALUES (
				$posto              ,
				$login_fabrica      ,
				$aux_condicao       ,
				$aux_pedido_cliente ,
				$aux_transportadora ,
				$aux_linha          ,
				$aux_tipo_pedido    ,
				$digitacao_distribuidor,
				'$filial_posto',
				$aux_observacao_pedido,
				$aux_pedido_programado
				$sql_valor
			)";
			$res = @pg_query ($con,$sql);
			$msg_erro = pg_errormessage($con);

			if (strlen($msg_erro) == 0){
				$res = pg_query ($con,"SELECT CURRVAL ('seq_pedido')");
				$cook_pedido = pg_fetch_result ($res,0,0);
			}
		} else {
			$sql = "UPDATE tbl_pedido SET
				condicao       = $aux_condicao       ,
				pedido_cliente = $aux_pedido_cliente ,
				transportadora = $aux_transportadora ,
				linha          = $aux_linha          ,
				filial_posto   = '$filial_posto'     ,
				tipo_pedido    = $aux_tipo_pedido,
				controle_exportacao = $aux_pedido_programado,
				obs = $aux_observacao_pedido
				WHERE pedido  = $cook_pedido
				AND   posto   = $login_posto
				AND   fabrica = $login_fabrica";
			$res = @pg_query ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		if(empty($msg_erro) and !empty($cook_pedido)){
			$sql = "SELECT fn_valida_pedido($cook_pedido,$login_fabrica);";
			$res = pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}

	if ((strlen($pedido) === 0 || strlen($cook_pedido) === 0) && strlen($msg_erro) === 0 && $transporte == "RETIRA") {
		$query_email_filial = "
			SELECT
				contato_email
			FROM tbl_posto_fabrica
			WHERE fabrica = {$login_fabrica}
			AND posto = {$filial_posto}
		";

		$res_email_filial = pg_query($con, $query_email_filial);
		if (strlen(pg_last_error()) === 0) {
			$email_destino = pg_fetch_result($res_email_filial, 0, 'contato_email');

			$email_destino = filter_var($email_destino, FILTER_VALIDATE_EMAIL);
			if ($email_destino) {
				$email_pedido = (!empty($pedido)) ? $pedido : $cook_pedido;
				$mensagem = "
					Nome do Cliente: " . utf8_encode($login_nome) . "<br />
					Código do Cliente: " . $login_codigo_posto . "<br />
					Pedido: " . $email_pedido . "<br />
					Data do Pedido: " . date('d/m/Y') . "<br />
					Nome do Responsável: " . utf8_encode($responsavel_retirada['nome']) . "<br />
					RG do Responsável: " . $responsavel_retirada['rg'] . "<br />
					WhatsApp: " . $responsavel_retirada['wapp'] . "<br /><br />
					<b>Telecontrol</b>
				";

				$res_comunicado = enviaComunicadoFilial($email_destino, $mensagem, $email_pedido);
				if (!$res_comunicado) {
					$msg_erro = "Falha ao enviar comunicado para a filial.";
				}
			}
		}
	} 

	$peca_ref_descricaos = ($_POST['peca_ref_descricao']);


	if (strlen ($msg_erro) == 0) {
		$pedido_items     = ($_POST['pedido_item']);
		$peca_referencias = ($_POST['peca_referencia']);
		$peca_ref_descricaos = ($_POST['peca_ref_descricao']);
		$peca_descricaos = ($_POST['peca_descricao']);

		for ($i = 0 ; $i <= $qtde_item; $i++) {
			$pedido_item      =  $pedido_items[$i];
			$peca_referencia  =  $peca_referencias[$i];
			$peca_ref_descricao  =  trim($peca_ref_descricaos[$i]);
			$peca_descricao   =  $peca_descricaos[$i];
			$qtde             =  $qtdes[$i];
			$preco            =  $precos[$i];


			if (strlen($peca_referencia)>0 and !empty($peca_ref_descricao) and $qtde > 0 ) {
				unset($makita_preco);
				$peca_referencia;
				$preco = str_replace('.','',$preco);
				$_GET['condicao'] = $condicao;
				$_GET['produto_referencia'] = $peca_referencia;
				$_GET['posto'] = $login_posto;
				$_GET['linha_form'] = $i;
				$_REQUEST['linha_form'] = $i;
				ob_start();
				include "makita_valida_regras.php";
				ob_get_clean();
				$preco_valida = str_replace(",",".",$preco);
				$makita_preco = number_format ($makita_preco,2,".","");
				if ($preco_valida <> $makita_preco){
					$msg_erro = "Erro ao cadastrar $peca_referencia, preço diferente do que cadastrado";
					break;
				}
			}
			if ($peca_descricao == 'Não encontrado') {
				$peca_referencia = '';
			}


			if (strlen ($peca_referencia) > 0 AND (strlen($preco)==0 or $preco == '0,00') and $qtde > 0 ){
				$msg_erro = "Não foi encontrado preço para a Peça $peca_referencia ($preco). Favor pesquisar e colocar a quantidade novamente, caso persistir, entrar em contato com a Makita!";
				$linha_erro = $i;
				break;
			}

			$qtde_anterior = 0;
			$peca_anterior = "";
			if (strlen($pedido_item) > 0 AND $login_fabrica==3){
				$sql = "SELECT peca,qtde
					FROM tbl_pedido_item
					WHERE pedido_item = $pedido_item";
				$res = @pg_query ($con,$sql);
				$msg_erro .= pg_errormessage($con);
				if (pg_num_rows ($res) > 0){
					$peca_anterior = pg_fetch_result($res,0,peca);
					$qtde_anterior = pg_fetch_result($res,0,qtde);
				}
			}

			if (strlen ($pedido_item) > 0 AND strlen ($peca_referencia) == 0 || $_GET[ 'delete' ] ){
				//var_dump($pedido_item . '--' . $cook_pedido);
				//return;

				$sql = "UPDATE tbl_pedido_item SET pedido = 0 WHERE pedido_item = $pedido_item ";
				$res = pg_query ($con,$sql);

				$sql = "SELECT pedido from tbl_pedido_item where pedido = $cook_pedido";
				$res = pg_query ($con,$sql);

				if (pg_num_rows($res)==0) {
					$sql = "DELETE	FROM	tbl_pedido
						WHERE	pedido = $cook_pedido";
					$res = pg_query ($con,$sql);
				}

				setcookie ($cook_pedido, "", time() - 3600);
				unset($cook_pedido);
				header( "Location : $PHP_SELF" );
			}

			if (strlen ($peca_referencia) > 0 and $qtde > 0 ) {

				$sql = "SELECT  tbl_peca.peca   ,
					tbl_peca.origem ,
					tbl_peca.promocao_site,
					tbl_peca.qtde_disponivel_site ,
					tbl_peca.qtde_max_site,
					tbl_peca.multiplo_site
					FROM    tbl_peca
					WHERE   tbl_peca.referencia = '$peca_referencia'
					AND     tbl_peca.fabrica             = $login_fabrica";
				$res = pg_query ($con,$sql);

				if (pg_num_rows($res)==0){

					$peca_referencia = trim (strtoupper ($peca_referencia));
					$peca_referencia = str_replace ("-","",$peca_referencia);
					$peca_referencia = str_replace (".","",$peca_referencia);
					$peca_referencia = str_replace ("/","",$peca_referencia);
					$peca_referencia = str_replace (" ","",$peca_referencia);

					$sql = "SELECT  tbl_peca.peca   ,
						tbl_peca.origem ,
						tbl_peca.promocao_site,
						tbl_peca.qtde_disponivel_site ,
						tbl_peca.qtde_max_site,
						tbl_peca.multiplo_site
						FROM    tbl_peca
						WHERE   tbl_peca.referencia_pesquisa = '$peca_referencia'
						AND     tbl_peca.fabrica             = $login_fabrica";
				$res = pg_query ($con,$sql);

				}

				$peca          = pg_fetch_result ($res,0,peca);
				$promocao_site = pg_fetch_result ($res,0,promocao_site);
				$qtde_disp     = pg_fetch_result ($res,0,qtde_disponivel_site);
				$qtde_max      = pg_fetch_result ($res,0,qtde_max_site);
				$qtde_multi    = pg_fetch_result ($res,0,multiplo_site);

				if (pg_num_rows ($res) == 0) {
					$msg_erro = "Peça $peca_referencia não cadastrada";
					$linha_erro = $i;
					break;
				}else{
					$peca   = pg_fetch_result ($res,0,peca);
					$origem = trim(pg_fetch_result ($res,0,origem));
				}

				if (strlen ($preco) == 0) $preco = "null";
				$preco = str_replace (",",".",$preco);

				if (strlen ($msg_erro) == 0 AND strlen($peca) > 0) {
					if (strlen($pedido_item) == 0){
						$sql = "INSERT INTO tbl_pedido_item (
							pedido ,
							peca   ,
							qtde   ,
							preco
						) VALUES (
							$cook_pedido ,
							$peca   ,
							$qtde   ,
							$preco
						)";
					}else{
						$sql = "UPDATE tbl_pedido_item SET
							peca = $peca,
							qtde = $qtde
							WHERE pedido_item = $pedido_item";
					}
					$res = @pg_query ($con,$sql);
					$msg_erro = pg_errormessage($con);

					if (strlen($msg_erro) == 0 AND strlen($pedido_item) == 0) {
						$res         = pg_query ($con,"SELECT CURRVAL ('seq_pedido_item')");
						$pedido_item = pg_fetch_result ($res,0,0);
						$msg_erro = pg_errormessage($con);
					}

					if (strlen($msg_erro) == 0) {
						$sql = "SELECT fn_valida_pedido_item ($cook_pedido,$peca,$login_fabrica)";
						$res = @pg_query ($con,$sql);
						$msg_erro = pg_errormessage($con);
					}

					if (strlen ($msg_erro) > 0) {
						break ;
					}
				}
			}
		}
	}

	if (strlen ($msg_erro) == 0) {
		//$sql = "SELECT fn_pedido_finaliza ($pedido,$login_fabrica)";
		$res = @pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_query ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF");
		exit;
	}else{
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}
}

if ( $_GET[ 'delete' ] ){
	$pedido = $_GET[ 'pedido' ];
	$pedido_item = $_GET[ 'delete' ];
	//var_dump($pedido . '--' . $pedido_item);
	//return;

	$sql = "UPDATE tbl_pedido_item SET pedido = 0 WHERE pedido_item = $pedido_item ";
	$res = pg_query ($con,$sql);

	$sql = "SELECT pedido from tbl_pedido_item where pedido = $pedido";
	$res = pg_query ($con,$sql);

	if (pg_num_rows($res)==0) {
		$sql = "UPDATE tbl_pedido set fabrica = 0 WHERE pedido = $pedido";
		$res = pg_query ($con,$sql);
	}

	setcookie ($cook_pedido, "", time() - 3600);
	unset($cook_pedido);
	echo "<script>window.location.href='$PHP_SELF'</script>";
}


$btn_acao = $_GET['btn_acao'];

if (strlen($btn_acao=='Finalizar')) {

	if (strlen ($cook_pedido) > 0) {
		$res = pg_query ($con,"BEGIN TRANSACTION");
		$sql = "SELECT fn_pedido_finaliza ($cook_pedido,$login_fabrica)";
		$res = pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (strlen ($msg_erro) == 0) {
			$sql = "SELECT tbl_admin.email
				FROM tbl_admin
				JOIN tbl_posto_fabrica ON tbl_admin.admin = tbl_posto_fabrica.admin_sap
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica
				AND tbl_posto_fabrica.posto = $login_posto";
			$res = pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);
			if(pg_num_rows($res) > 0){
				$email_inspetor = pg_fetch_result($res,0,'email');
			}
		}

		if (strlen ($msg_erro) == 0) {
			$res = pg_query ($con,"COMMIT TRANSACTION");

			if ($login_fabrica == 42) {
				$array_pecas_monitoradas = [];

				$sql_monitorada = " SELECT JSON_FIELD('peca_monitorada', tbl_peca.parametros_adicionais) AS peca_monitorada,
										   JSON_FIELD('email_peca_monitorada', tbl_peca.parametros_adicionais) AS email_peca_monitorada,
										   tbl_pedido_item.qtde,
										   tbl_peca.referencia,
										   tbl_peca.descricao
									FROM tbl_pedido_item 
									JOIN tbl_peca USING(peca)
									JOIN tbl_pedido ON tbl_pedido_item.pedido = tbl_pedido.pedido
									WHERE tbl_pedido_item.pedido = $cook_pedido
									AND tbl_pedido.fabrica = $login_fabrica";
				$res_monitorada = pg_query($con, $sql_monitorada);
				if (pg_num_rows($res_monitorada) > 0) {
					for ($i = 0; $i < pg_num_rows($res_monitorada); $i++) {
						if (pg_fetch_result($res_monitorada, $i, 'peca_monitorada') != "t") {
							continue;
						}

						$email_peca_monitorada = pg_fetch_result($res_monitorada, $i, 'email_peca_monitorada');
						$peca_monitorada_ref   = pg_fetch_result($res_monitorada, $i, 'referencia');
						$peca_monitorada_desc  = pg_fetch_result($res_monitorada, $i, 'descricao');
						$peca_monitorada_qtde  = pg_fetch_result($res_monitorada, $i, 'qtde');

						$array_pecas_monitoradas["email"][$email_peca_monitorada][] = array("referencia" => $peca_monitorada_ref, "descricao" => $peca_monitorada_desc, "qtde" => $peca_monitorada_qtde);

					}
				}

				if (count($array_pecas_monitoradas) > 0) {

		            $msg = []; 
		            $msg_pronta = [];
		            
		            $sql_nome_posto = "SELECT tbl_posto.nome, 
		                                          tbl_posto.cnpj 
		                                   FROM tbl_posto 
		                                   JOIN tbl_posto_fabrica USING(posto) 
		                                   WHERE tbl_posto.posto = $login_posto 
		                                   AND tbl_posto_fabrica.fabrica = $login_fabrica";
		            $res_nome_posto = pg_query($con, $sql_nome_posto);
		            $nome_posto = pg_fetch_result($res_nome_posto, 0, 'nome');
		            $cnpj_posto = pg_fetch_result($res_nome_posto, 0, 'cnpj');

		            foreach ($array_pecas_monitoradas as $chave => $value_chave) {
		                if ($chave == 'email') {
		                    foreach ($value_chave as $nome_campo => $value_campo) {
		                        foreach ($value_campo as $nomes => $values) {                            
		                            $msg[$nome_campo][] = "Peça: ".$values['referencia'].", Descrição: ".$values['descricao']." e Quantidade: ".$values['qtde'];
		                        }
		                    }
		                }
		            }
		                      
		            foreach ($msg as $email => $vl) {
		                $ms = "Pedido: $cook_pedido<br><br>Posto: $nome_posto - CNPJ: $cnpj_posto<br><br>".implode("<br>", $vl)."<br><br>";
		                $msg_pronta[$email] = $ms;
		            }
		            
		            foreach ($msg_pronta as $key => $value) {
		                $email = $key;
		                $mailTc = new TcComm($externalId);
		                $res = $mailTc->sendMail(
		                    $email,
		                    utf8_encode('Telecontrol - Peças Monitoradas'),
		                    utf8_encode($value),
		                    'noreply@telecontrol.com.br'
		                );
		            }
				}
			}

			if (!empty($email_inspetor)) {
				$sql = "
                    SELECT  tbl_posto_fabrica.nome_fantasia,
                            tbl_pedido.pedido_cliente
                    FROM    tbl_posto_fabrica
                    JOIN    tbl_posto_filial    ON  tbl_posto_fabrica.posto         = tbl_posto_filial.filial_posto
                    JOIN    tbl_pedido          ON  tbl_posto_filial.filial_posto   = tbl_pedido.filial_posto
                                                AND tbl_pedido.fabrica              = $login_fabrica
                                                AND tbl_pedido.pedido               = $cook_pedido
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica
					AND tbl_posto_filial.posto = $login_posto
					AND tbl_posto_fabrica.filial is true
					order by tbl_posto_fabrica.posto";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					$filial_makita = pg_fetch_result($res, 0 , 'nome_fantasia');
					$pedido_cliente = pg_fetch_result($res, 0, 'pedido_cliente');
				}

        			$addresList = array($email_inspetor );


			        $mensagem = "Código do cliente:".$pedido_cliente." <br/>
					Nome do posto: ".$login_codigo_posto." - ".$login_nome." <br/>
					Hora do envio: ".date('H:i')." <br/>
					Número do pedido: ".$cook_pedido." <br/>
					Filial Makita: ".$filial_makita;

					$mailTc = new TcComm("smtp@posvenda");
					$res = $mailTc->sendMail(
						$email_inspetor,
						"Novo pedido faturado",
						$mensagem,
						"noreply@telecontrol.com.br"
					);

			}
			header ("Location: pedido_finalizado.php?pedido=$cook_pedido&loc=1");
		} else {
			pg_query ($con,"ROLLBACK TRANSACTION");
		}
	}
}

#------------ Le Pedido da Base de dados ------------#
$pedido = $_GET['pedido'];

if (strlen ($pedido) > 0) {

	$sql = "SELECT	TO_CHAR(tbl_pedido.data, 'DD/MM/YYYY')    AS data                 ,
					tbl_pedido.tipo_frete                                             ,
					tbl_pedido.transportadora                                         ,
					tbl_transportadora.cnpj                   AS transportadora_cnpj  ,
					tbl_transportadora.nome                   AS transportadora_nome  ,
					tbl_transportadora_fabrica.codigo_interno AS transportadora_codigo,
					tbl_pedido.pedido_cliente                                         ,
					tbl_pedido.tipo_pedido                                            ,
					tbl_pedido.produto                                                ,
					tbl_produto.referencia                    AS produto_referencia   ,
					tbl_produto.descricao                     AS produto_descricao    ,
					tbl_pedido.linha                                                  ,
					tbl_pedido.condicao                                               ,
					tbl_pedido.obs                                                    ,
					tbl_pedido.exportado                                              ,
					tbl_pedido.total_original                                         ,
					tbl_pedido.filial_posto                                           ,
					tbl_pedido.permite_alteracao                                      ,
					tbl_pedido.controle_exportacao
			FROM	tbl_pedido
			LEFT JOIN tbl_transportadora USING (transportadora)
			left JOIN tbl_transportadora_fabrica ON tbl_transportadora_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_produto        USING (produto)
			WHERE	tbl_pedido.pedido   = $pedido
			AND		tbl_pedido.posto    = $login_posto
			AND		tbl_pedido.fabrica  = $login_fabrica ";
	$res = pg_query ($con,$sql);

	if (pg_num_rows ($res) > 0) {
		$data                  = pg_fetch_result($res,0,'data');
		$transportadora        = pg_fetch_result($res,0,'transportadora');
		$transportadora_cnpj   = pg_fetch_result($res,0,'transportadora_cnpj');
		$transportadora_codigo = pg_fetch_result($res,0,'transportadora_codigo');
		$transportadora_nome   = pg_fetch_result($res,0,'transportadora_nome');
		$pedido_cliente        = pg_fetch_result($res,0,'pedido_cliente');
		$tipo_pedido           = pg_fetch_result($res,0,'tipo_pedido');
		$produto               = pg_fetch_result($res,0,'produto');
		$produto_referencia    = pg_fetch_result($res,0,'produto_referencia');
		$produto_descricao     = pg_fetch_result($res,0,'produto_descricao');
		$linha                 = pg_fetch_result($res,0,'linha');
		$condicao              = pg_fetch_result($res,0,'condicao');
		$exportado             = pg_fetch_result($res,0,'exportado');
		$total_original        = pg_fetch_result($res,0,'total_original');
		$permite_alteracao     = pg_fetch_result($res,0,'permite_alteracao');
		$filial_posto          = pg_fetch_result($res,0,'filial_posto');
		$observacao_pedido     = pg_fetch_result($res,0,'obs');
		$pedido_programado     = pg_fetch_result($res,0,'controle_exportacao');
	}
}

#---------------- Recarrega Form em caso de erro -------------
if (strlen ($msg_erro) > 0) {
	$pedido         = $_POST['pedido'];
	$condicao       = !empty($_POST['condicao']) ? $_POST['condicao'] : $condicao ;
	$tipo_pedido    = !empty($_POST['tipo_pedido']) ? $_POST['tipo_pedido'] : $tipo_pedido;
	$pedido_cliente = !empty($_POST['pedido_cliente']) ? $_POST['pedido_cliente'] : $pedido_cliente;
	$transportadora = $_POST['transportadora'];
	$linha          = $_POST['linha'];
	$codigo_posto   = $_POST['codigo_posto'];
	$filial_posto   = $_POST['filial_posto'];
	$pedido_programado = $_POST['pedido_programado'];
}



$title       = "CADASTRO DE PEDIDOS DE PEÇAS";
$layout_menu = 'pedido';

if(!empty($cook_pedido)) {
	$sql = "SELECT pedido
			FROM tbl_pedido
			WHERE pedido = $cook_pedido
			AND   fabrica = $login_fabrica";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) == 0 && $btn_acao != 'Finalizar'){
		unset( $cook_pedido );
		setcookie ('cook_pedido');
		$cook_pedido = "";
	}
}




include "cabecalho.php";

?>
<style type="text/css">
	#example-content-1, #example-content-2, #example-content-3 {
		display: none;        /* required */
		position: absolute;   /* required */
		padding: 10px;
		text-align:left;
		border: 1px solid black;
		background-color: white;
	}
</style>

<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<link rel="stylesheet" type="text/css" href="js/ajaxfileupload.css" />
<script type="text/javascript" src="js/jquery-1.3.2.js"></script>
<script type="text/javascript" src="js/jquery.ezpz_tooltip.min.js"></script>
<script type="text/javascript" src="js/php.default.min.js"></script>
<script type='text/javascript' src='js/jquery.bgiframe.js'></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<script type="text/javascript" src="js/ajaxfileupload.js"></script>
<script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<link type="text/css" href="plugins/jquery/datepick/telecontrol.datepick.css" rel="stylesheet" />

<script type="text/javascript">
	$(function(){
		$("#pedido_programado").datepick({startDate:'01/01/2000'});
		$("#pedido_programado").maskedinput("99/99/9999");
		$("input[name=transporte_responsavel_wapp]").maskedinput("(99) 99999-9999");
		$("#example-target-1,#example-target-2,#example-target-3").ezpz_tooltip({
			contentPosition: 'rightStatic'
	});

	$("#condicao").change(function() {
		if ($(this).val() != "") {
			$("#arquivo_pedido_txt").show();
		} else {
			$("#arquivo_pedido_txt").hide();
		}
	});

	$('.auto_comp').autocomplete("item_pesquisa_ajax_makita.php?q=" + $(this).val() , {
		minChars: 3,
		delay: 250,
		width: 350,
		scroll: true,
		scrollHeight: 500,
		matchContains: false,
		highlightItem: true,
		formatItem: function (row)   {return row[3]},
		formatResult: function(row)  {return row[2];}
		});

	    $('.auto_comp').result(function(event, data, formatted) {
	    	linha = $(this).attr('rel');
			$(this).val(data[2]);
			$('#peca_referencia_'+linha).val(data[1]);
			$('#qtde_'+linha).focus();
		});

		$("select[name=filial_posto]").change(function (e) {
			if ($(this).val().length === 0)
				return false;

			var previousSelected = $("#transporte").val();

			var filial = $(this).val();
			var selectTransporte = $("select[name=transporte_filial]");

			$($(selectTransporte).find('option')[1]).nextAll().remove();
			
			getTransporte(filial, function (res) {
				if (res.hasOwnProperty('exception')) {
					return alert('Falha ao buscar transportes. Por favor, entre em contato com o suporte.')
				}

				var options = [];

				if (res.sedex == 'TRUE') {
					$(selectTransporte).append('<option value="SEDEX">Sedex A Cobrar</option>');
				}
				
				if (res.retira == 'TRUE') {
					$(selectTransporte).append('<option value="RETIRA">Retirada</option>');
				}

				$.each($(selectTransporte).find('option'), function (i, e) {
					if ($(e).val() === previousSelected) {
						$(e).attr('selected', 'selected');
					}
				})

				if ($("select[name=transporte_filial]").val().length >= 1) {
					$("select[name=transporte_filial]").trigger("change");
				}
			})
		});

		if ($("#filial_posto").length === 1) {
			if ($("#filial_posto").val().length >= 1) {
				$("#filial_posto").trigger('change')
			}
		} else {
			var filial = $("input[name=filial_posto]").val();
			var selectTransporte = $("select[name=transporte_filial]");
			var previousSelected = $("#transporte").val();

			getTransporte(filial, function (res) {
				if (res.hasOwnProperty('exception')) {
					return alert('Falha ao buscar transportes. Por favor, entre em contato com o suporte.')
				}

				var options = [];

				if (res.sedex == 'TRUE') {
					$(selectTransporte).append('<option value="SEDEX">Sedex A Cobrar</option>');
				}
				
				if (res.retira == 'TRUE') {
					$(selectTransporte).append('<option value="RETIRA">Retirada</option>');
				}

				$.each($(selectTransporte).find('option'), function (i, e) {
					if ($(e).val() == previousSelected) {
						$(e).attr('selected', 'selected');
					}
				})

				if ($("select[name=transporte_filial]").val().length >= 1) {
					$("select[name=transporte_filial]").trigger("change");
				}
			})
		}

		$("#btn-finalizar").click(function (e) {
			e.preventDefault();

			var avisoTransporteFilial = $("#aviso-transporte-filial");

			/** head actions */
			$(avisoTransporteFilial).find(".header .close").click();
			$(avisoTransporteFilial).find(".header .close").click(function (e) {
				$(avisoTransporteFilial).hide();
			})

			/** body */
			var bodyMessage = $(avisoTransporteFilial).find(".body .message");

			/** footer actions */
			var buttonAlterar = $(avisoTransporteFilial).find(".foot button[name=alterar]").show();
			var buttonPagar = $(avisoTransporteFilial).find(".foot button[name=pagar]").show();
			var buttonOK = $(avisoTransporteFilial).find(".foot button[name=ok]").show();

			$(buttonAlterar).unbind('click');
			$(buttonAlterar).bind('click', function () {
				$(avisoTransporteFilial).hide();
			})

			$(buttonPagar).unbind('click');
			$(buttonPagar).bind('click', function () {
				pedido = '<?= $cook_pedido ?>'
				$.ajax({
					url: "pedido_makita_cadastro.php",
					type: 'POST',
					data: {
						ajax: 'pagarFrete',
						pedido: pedido
					},
					success: function () {
						return location = '<?= $_SERVER["PHP_SELF"] ?>?btn_acao=Finalizar';
					},
					error: function () {
						alert('Ocorreu uma falha ao atualizar o tipo de transporte. Por favor, contate o suporte.');
						$(avisoTransporteFilial).hide();
					}
				})
			})

			$(buttonOK).unbind('click');
			$(buttonOK).bind('click', function () {
				$(avisoTransporteFilial).hide();
			});

			var transporte = $("#transporte").val();
			var total = $("#total-pedido").val().replace(".", "").replace(",", ".");
			total = parseFloat(total)

			var message = "";

			var condFilial = filial == 37403 || filial == 148906;

			if (transporte == 'PADRAO' && condFilial && total < 600) {
				message = 'Pedido com valor abaixo de R$600,00. Seu pedido será enviado com frete a cobrar. Deseja alterar o pedido?'
				$(buttonOK).hide();
			} else if (transporte == 'PADRAO' && !condFilial && total < 200) {
				message = 'Pedido com valor abaixo de R$200,00. Seu pedido será enviado com frete a cobrar. Deseja alterar o pedido?'
				$(buttonOK).hide();
			} else if (total < 100) {
				message = 'Pedido mínimo: R$100,00. Acrescente mais itens para poder finalizar.';
				$(buttonPagar).hide();
				$(buttonAlterar).hide();
			} else {
				return location = '<?= $_SERVER["PHP_SELF"] ?>?btn_acao=Finalizar';
			}

			$(bodyMessage).html(message);
			$(avisoTransporteFilial).show();
		})

		$("select[name=transporte_filial]").change(function (e) {
			var cookPedido = '<?= $cook_pedido ?>';
			if ($(this).val().length === 0 || cookPedido.length >= 1) {
				return false;
			}

			var transporte = $(this).val();
			if (transporte === "RETIRA") {
				var avisoTransporteFilial = $("#aviso-transporte-filial");

				/** head actions */
				$(avisoTransporteFilial).find(".header .close").click();
				$(avisoTransporteFilial).find(".header .close").click(function (e) {
					$(avisoTransporteFilial).hide();
				});

				/** body */
				var bodyMessage = $(avisoTransporteFilial).find(".body .message");

				/** footer actions */
				var buttonAlterar = $(avisoTransporteFilial).find(".foot button[name=alterar]").show();
				var buttonPagar = $(avisoTransporteFilial).find(".foot button[name=pagar]").show();
				var buttonOK = $(avisoTransporteFilial).find(".foot button[name=ok]").show();

				$(buttonOK).unbind('click');
				$(buttonOK).bind('click', function () {
					$(avisoTransporteFilial).hide();
				});

				message = 'Você selecionou a opção de retirar seu pedido. Aguarde nosso contato por WhatsApp informando sobre a liberação.\
					Informe nos campos que serão exibidos os dados da pessoa responsável pela retirada do pedido.';
				$(bodyMessage).html(message);

				$(buttonAlterar).hide();
				$(buttonPagar).hide();

				$(buttonOK).unbind('click');
				$(buttonOK).bind('click', function () {
					$("#campos-transporte-retirada").show();
					$(avisoTransporteFilial).hide();
				});

				$(avisoTransporteFilial).show();
			} else {
				$("#campos-transporte-retirada").hide();
				$("#campos-transporte-retirada").find("input").val("");
			}
		})
	});

	function getTransporte(filial, callback) {
		$.ajax({
			url: 'pedido_makita_cadastro.php',
			type: 'POST',
			async: true,
			dataType: 'json',
			data: {
				ajax: 'getTransporte',
				filial: filial
			},
			success: function(data) {
				callback(data)
			}
		})
	}

	function recalcular(pedido){
		var condicaoPagamento = $("#troca_condicao_pagametno").val();
		$.ajax({
			url : "dados_pedido_makita.php",
			type: "POST",
			dataType: "json",
			data: {
				dados_pedido: true,
				pedido : pedido,
				posto : <?=$login_posto?>,
				fabrica : <?=$login_fabrica?>,
				condicao : condicaoPagamento
			},
			success: function(data) {
				if(data.retorno == 'ok'){
					alert(data.msg);
					window.location.reload();
				}else{
					alert(data.msg);
				}
			}
		});
	}


</script>
<?php
#HD 402721
?>
<script type="text/javascript">
	$(document).ready(function(){
		$("#arquivo_txt").change(function(){
			if($(this).val() != ""){
				$("#enviaImagem").val('1');
				ajaxFileUpload();

			}
			$(this).val('');
		});
	});


	function mudaDisplay(elemento){
		if($("#"+elemento).css('display') == 'none'){
			$("#"+elemento).fadeIn();
		}else{
			$("#"+elemento).fadeOut();
		}
	}

	function calculoDados(data,status,e){
		eval("dados = "+data+";");

		var cond = $("#condicao").val();
		var mensagem = "";

		if(cond == ""){
			$('#mensagem_upload').fadeIn();
			$("#mensagem_upload").html("Selecione uma condição de Pagamento");
			setTimeout("$('#mensagem_upload').fadeOut()",4000);
			$("#botao_enviar").show();
			return false;
		}

		var campo = document.getElementById('dados_txt');
		var texto = '';

		for(var i in dados){
			if(dados[i].length > 0){
				if(dados[i].indexOf(";") > 0) {
					dados[i] = dados[i].replace(/;$/,'');
					peca = dados[i].split(";");
					if(peca[1]) {
						texto += peca[0]+";";
						texto += peca[1]+"\n";
					}
				}else{
					peca = dados[i].split("\t");
					if(peca[1]) {
						texto += peca[0]+"\t";
						texto += peca[1];
					}
				}

			}

		}

		$("#botao_enviar").hide();
		$("#arquivo_txt").val('');

		campo.value=texto;
		importarPecas(campo);
	}

	function ajaxFileUpload(){

		$('#envia_imagem').val('');

		$.ajaxFileUpload({
			url:'<?php echo $PHP_SELF;?>',
			secureuri:false,
			fileElementId:'arquivo_txt',
			dataType: 'json',
			success: calculoDados,
			error: calculoDados
		});

		$('#envia_imagem').val('no');

		return false;

	}

	function abreImportaExcel(){
		$("#div_importa_excel").css('display','block');
	}

	function fechaImportaExcel(){
		$("#div_importa_excel").css('display','none');
	}
</script>

<script type='text/javascript'>

function autocompletar_item(campo1,campo2,conteudo,linha) {

//	alert("item_pesquisa_ajax_makita_test.php?q=" + conteudo + "&cache_bypass=$cache_bypass");
	$('#'+campo1).autocomplete("item_pesquisa_ajax_makita.php?q=" + conteudo , {
		minChars: 3,
		delay: 250,
		width: 350,
		scroll: true,
		scrollHeight: 500,
		matchContains: false,
		highlightItem: true,
		formatItem: function (row)   {return row[3]},
		formatResult: function(row)  {return row[2];}
	});

    $('#'+campo1).result(function(event, data, formatted) {
		$('#'+campo2).val(data[0]);
		$('#peca_referencia_'+linha).val(data[1]);
		$('#qtde_'+linha).blur().queue().focus();
	});
}


function confirmaCondicao(condicao) {
	var valida = $('#validacondicao');
	var condicaoanterior = $('#condicaoanterior');
	if(confirm('Atenção a condição de pagamento pode influenciar no preço das peças, tem certeza que deseja a condicao '+condicao+ '? Caso corfime se precisar alterar os dados digitados serão perdidos')==true) {
		if (valida.val()=='sim') {
			var qtde = $('#qtde_item').val();
			for (i=0;i<qtde;i++) {
				$('#preco_'+i).val(' ');
				$('#sub_total_'+i).val(' ');
			}
		}
		valida.val('sim');
		condicaoanterior.val($('#condicao').val());
	} else {
		if (valida.val()=='sim') {
			valida.val('nao');
			var seleciona = "option[value="+"'"+condicaoanterior.val()+"']";~
			$("#condicao "+seleciona).attr('selected', 'selected');
		} else {
			valida.val('nao');
		}
	}
}

function adicionarLinha(linha) {
	linha = parseInt(linha) + 1;
	if(linha > 600) {
		alert('Não é possível realizar o upload do arquivo pois a quantidade de linhas excede o limite de 600 linhas.');
		document.getElementById('divAguarde').style.display='none';
		location.reload();
		return false;
	}
		/*se ainda na criou a linha de item */
		if (!document.getElementById('peca_referencia_'+linha)) {
			var tbl = document.getElementById('tabela_itens');

			/*Criar TR - Linha*/
			var nova_linha = document.createElement('tr');
			nova_linha.setAttribute('rel', linha);

			/********************* COLUNA 1 ****************************/
			/*Cria TD */
			var celula = criaCelula('');
			celula.style.cssText = 'text-align: left;';

			var el = document.createElement('input');
			el.setAttribute('type', 'text');
			el.setAttribute('name', 'peca_ref_descricao[]' );
			el.setAttribute('id', 'peca_ref_descricao_' + linha);
			el.setAttribute('size', '40');
			el.onfocus = function(){
				autocompletar_item('peca_ref_descricao_'+linha,'peca_referencia_'+linha,this.value,linha);
			};
			celula.appendChild(el);

			nova_linha.appendChild(celula);

			var el = document.createElement('input');
			el.setAttribute('type', 'hidden');
			el.setAttribute('name', 'peca_referencia[]');
			el.setAttribute('id', 'peca_referencia_' + linha);
			el.setAttribute('size', '15');
			celula.appendChild(el);

			nova_linha.appendChild(celula);

			/*Cria TD */
			var celula = criaCelula('');
			celula.style.cssText = 'text-align: left;';

			var el = document.createElement('input');
			el.setAttribute('type', 'text');
			el.setAttribute('name', 'qtde[]');
			el.setAttribute('id', 'qtde_' + linha);
			el.setAttribute('size', '5');
			el.onblur = function() {
				fnc_makita_preco(linha);
				adicionarLinha(linha);
			}
			celula.appendChild(el);

			nova_linha.appendChild(celula);

			/*Cria TD */
			var celula = criaCelula('');
			var el = document.createElement('input');
			el.setAttribute('type', 'text');
			el.setAttribute('style', 'text-align: right;');
			el.setAttribute('name', 'preco[]' );
			el.setAttribute('id', 'preco_' + linha);
			el.setAttribute('size', '10');
			celula.appendChild(el);

			nova_linha.appendChild(celula);

			/*Cria TD */
			var celula = criaCelula('');
			var el = document.createElement('input');
			el.setAttribute('type', 'text');
			el.setAttribute('style', 'text-align: right;');
			el.setAttribute('name', 'sub_total[]');
			el.setAttribute('id', 'sub_total_' + linha);
			el.setAttribute('rel', 'total_pecas');
			el.setAttribute('size', '10');
			celula.appendChild(el);

			nova_linha.appendChild(celula);

			/************ FINALIZA LINHA DA TABELA ***********/
			var tbody = document.createElement('TBODY');
			tbody.appendChild(nova_linha);
			tbl.appendChild(tbody);

			$('#qtde_item').val(linha);
			adicionarLinha2(linha);

		};
}

function criaCelula(texto) {
	var celula = document.createElement('td');
	var textoNode = document.createTextNode(texto);
	celula.appendChild(textoNode);
	return celula;
}


function adicionarLinha2(linha) {

	linha = parseInt(linha);
		/*se ainda na criou a linha de item */
			var tbl = document.getElementById('tabela_itens');

			/*Criar TR - Linha*/
			var nova_linha = document.createElement('tr');

			/********************* COLUNA 1 ****************************/

			/*Cria TD */
			var celula = criaCelula('');
			celula.setAttribute('colspan','7');
			celula.style.cssText = 'text-align: left;';

			var el = document.createElement('div');
			el.setAttribute('name', 'mudou[]');
			el.setAttribute('id', 'mudou_' + linha);
			celula.appendChild(el);

			nova_linha.appendChild(celula);

			/************ FINALIZA LINHA DA TABELA ***********/
			var tbody = document.createElement('TBODY');
			tbody.appendChild(nova_linha);
			tbl.appendChild(tbody);
}

function Trim(s){
		var l=0;
		var r=s.length -1;

		while(l < s.length && s[l] == ' '){
			l++;
		}
		while(r > l && s[r] == ' '){
			r-=1;
		}
		return s.substring(l, r+1);
	}
function valorVazio(value){
	return value != "";
}

function importarPecas(campo) {
	var lote_pecas	= campo.value;
	var condicao	= $("#condicao").val(); //window.document.frm_pedido.condicao.value ;
	var posto		= <?= $login_posto ?>;
	var array_lote	= new Array();
	array_lote		= lote_pecas.split("\n");
	array_lote = array_lote.filter(valorVazio);
	var num_linhas  = array_lote.length;
	var erros = '';

	if (num_linhas > 600) {
		alert('Não é possível realizar o upload do arquivo pois a quantidade de linhas excede o limite de 600 linhas.');
		document.getElementById('divAguarde').style.display='none';
		location.reload();
		return false;
	}

	if (condicao.length==0) {
		alert('Selecione uma condição para fazer o upload');
		document.getElementById('divAguarde').style.display='none';
	} else {
		for (i = 0; i < array_lote.length; i++){

			var array_peca = new Array();
			if(array_lote[i].indexOf(";") > 0) {
				array_peca = array_lote[i].split(";");
			}else{
				array_peca = array_lote[i].split("\t");
			}

			var referencia = array_peca[0] ;
			referencia = Trim(referencia);
			var qtde       = array_peca[1];

			var retorno =adicionarLinha(i-1);
			if(retorno == false || referencia.length == 0) {
				return false;
			}

			linha = parseFloat(i);

			url = 'makita_valida_regras.php?linha_form=' + linha + '&posto=<?= $login_posto ?>&produto_referencia=' + referencia + '&condicao=' + condicao + '&cache_bypass=<?= $cache_bypass ?>';

			 var campos = $.ajax({
							type: "GET",
							url: url,
							cache: false,
							async: false
			 }).responseText;

			campos = campos.substring (campos.indexOf('<preco>')+7,campos.length);
			campos = campos.substring (0,campos.indexOf('</preco>'));
			campos_array = campos.split("|");

			preco		= campos_array[0] ;
			linha_form	= campos_array[1] ;
			descricao	= campos_array[2] ;
			mudou	= campos_array[3] ;
			de		= campos_array[4] ;
			referencia	= campos_array[5] ;
			fora = campos_array[13];

			if (mudou == 'SIM') {
				$('#linhadiv_'+linha_form).css('display','block');
				$('#mudou_'+linha_form).css('display','block');
				$('#mudou_'+linha_form).css('background-color','red');
				$('#mudou_'+linha_form).html('A peça acima entrou no lugar desta '+de +' que foi enviada no upload');
			}
		
			if (fora == 'SIM') {
				$('#peca_referencia_'+i).parents('tr').css('background-color','#FFCC33');
				$('#linhadiv_'+linha_form).css('display','block');
				$('#mudou_'+linha_form).css('display','block');
				$('#mudou_'+linha_form).css('background-color','#FFCC33').html('Peça acima está fora de linha');
			}

			if (descricao.length>0) {
				$('#peca_referencia_'+i).val(referencia);
				$('#qtde_'+i).val(qtde);
				//('#peca_descricao_'+i).val(descricao);
				$('#peca_ref_descricao_'+i).val(referencia+'-'+descricao);
			} else {
				var erros = erros +" Peça não encontrada:  " + referencia +" \n ";
				$('#peca_referencia_'+i).val(referencia);
				if(qtde > 0) {
					$('#qtde_'+i).val(qtde);
				}
				$('#peca_descricao_'+i).val('Não encontrado');
				$('#peca_referencia_'+i).parent().css('background-color','red');
			}

			campo_preco = 'preco_' + linha_form;
			if (descricao.length>0) {
				document.getElementById(campo_preco).value = preco;
				fnc_calcula_total(linha_form);
			} else {
				document.getElementById(campo_preco).value = ''
				$('#sub_total_'+linha_form).val('');
			}
		}
			if (erros.length>0) {
				alert(erros+' Caso não altere estas peças serão retiradas do pedido');
			}
	}
	document.getElementById('divAguarde').style.display='none';
}
</script>

<style type="text/css">
	body {
		font: 80% Verdana,Arial,sans-serif;
		background: #FFF;
	}

	.xTabela{
		font-family: Verdana, Arial, Sans-serif;
		font-size:12px;
		padding:3px;
	}

	#layout{
		width: 700px;
		margin:0 auto;
	}

	ul#split, ul#split li{
		margin:50px;
		margin:0 auto;
		padding:0;
		width:700px;
		list-style:none
	}

	ul#split li{
		float:left;
		width:700px;
		margin:0 10px 10px 0
	}

	ul#split h3{
		font-size:14px;
		margin:0px;
		padding: 5px 0 0;
		text-align:center;
		font-weight:bold;
		color:white;
	}

	ul#split h4{
		font-size:90%
		margin:0px;
		padding-top: 1px;
		padding-bottom: 1px;
		text-align:center;
		font-weight:bold;
		color:white;
	}

	ul#split p{
		margin:0;
		padding:5px 8px 2px;
	}

	ul#split div{
		background: #D9E2EF;
	}

	li#one{
		text-align:left;
	}

	li#one div{
		border:1px solid #D9E2EF
	}
	li#one h3{
		background: #D9E2EF
	}

	li#one h4{
		background: #D9E2EF
	}

	.coluna1{
		width:230px;
		font-weight:bold;
		font-size:11px;
		display: inline;
		float:left;

	}
	.titulo_tabela{
		background-color:#596d9b;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}


	.titulo_coluna{
		background-color:#596d9b;
		font: bold 11px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

	.msg_erro{
		background-color:#FF0000;
		font: bold 16px "Arial";
		color:#FFFFFF;
		text-align:center;
		margin: 0 auto;
	}
	.texto_avulso{
	   font: 14px Arial; color: rgb(89, 109, 155);
	   background-color: #d9e2ef;
	   text-align: center;
	   width:700px;
	   margin: 0 auto;
	   border:1px solid #596d9b;
	}
	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
	}

	.subtitulo{
		background-color: #7092BE;
		font:bold 11px Arial;
		color: #FFFFFF;
		text-align:center;
	}

	table.tabela tr td{
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}

	#mensagem_upload{
		padding: 10px;
		border: 1px solid #f00;
		font-size: 12px;
		display: none;
	}

</style>

<? include "javascript_pesquisas.php" ?>

<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>

<script type="text/javascript">
function fnc_makita_preco (linha_form) {
	condicao = $("#condicao").val();//window.document.frm_pedido.condicao.value ;
	posto    = <?= $login_posto ?>;

	if ((condicao.length)==0){
		alert("Por favor escolha uma condição de pagamento");
		return false;
	}
	$('#preco_' + linha_form).val('');

	peca_referencia = '#peca_referencia_' + linha_form;
	peca_referencia = $('#peca_referencia_' + linha_form).val();

	$.ajax({
		url : "makita_valida_regras.php",
		type: "GET",
		data: {
			linha_form : linha_form,
			posto : <?=$login_posto ?>,
			produto_referencia:  peca_referencia,
			condicao : condicao
		},
		complete: function(data) {
			var data_array = data.responseText.match(/<preco>(.+)<\/preco>/)[1].split("|"); // apenas o conteúdo da tag '<preco />'
			var preco      = data_array[0];
			var linha_form = data_array[1];

			$('#preco_' + linha_form).val(preco);
			fnc_calcula_total(linha_form);
		}
	});

}

function fnc_calcula_total(linha_form) {
	var total = 0;
	preco = document.getElementById('preco_'+linha_form).value;
	qtde = document.getElementById('qtde_'+linha_form).value;
	preco = preco.replace('.','');
	preco = preco.replace(',','.');
	//preco = preco.replace('.',',');

	var preco =  parseFloat(preco);
	if (qtde && preco) {
		total = qtde * preco;
		total = total.toFixed(2);
		total = total.replace( '.' , ',' );
	}

	document.getElementById('sub_total_'+linha_form).value = total;

	//Totalizador
	var total_pecas = 0;
	$("input[rel='total_pecas']").each(function(){
		if ($(this).val()){
			tot = $(this).val();
			tot = tot.replace( ',' , '.' );
			tot = parseFloat (tot);
			total_pecas += tot;
			//total_pecas = number_format( total_pecas, 2 , ',' );

		}
	});



	<?if (!in_array($login_fabrica,array(24,30))) { ?>
	var total_pecas_aux = document.getElementById('total_pecas').value;
		total_pecas_aux = total_pecas_aux.replace( '.' , '' );
		total_pecas_aux = total_pecas_aux.replace( ',' , '' );
		total_pecas_aux = number_format( total_pecas, 2 , ',','.' ); //total_pecas.toFixed(2);
		//total_pecas_aux = total_pecas.toFixed(2);
		document.getElementById('total_pecas').value = total_pecas_aux;
	<?}?>
}

function atualiza_proxima_linha(linha_form){
	var produto_referencia = document.getElementById('produto_referencia_'+linha_form).value;
	var produto_descricao  = document.getElementById('produto_descricao_'+linha_form).value;

	var proxima_linha = linha_form + 1;

	if (document.getElementById('produto_descricao_'+proxima_linha)){
		if (! document.getElementById('produto_descricao_'+proxima_linha).value){
			document.getElementById('produto_referencia_'+proxima_linha).value = produto_referencia;
			document.getElementById('produto_descricao_'+proxima_linha).value = produto_descricao;
		}
	}
}

$(function() {

	$('#total_pecas').each(function() {
		var total_pecas = $('#total_pecas').val().replace('.',',');
		$('#total_pecas').val(total_pecas);
	});

	$('.qtde').numeric();

	/*
		Esse script inserido trabalhalha com os campos das peças, ele apaga todos os campos
		quando a descrição não está inserida, não deixa multiplicar a quantidade por preço caso a quantidade não seja
		digitada e limpa todos os campos caso seja apagada a descrição da peça.
	*/
	$('.qtde').focus(function(e) {
		if (isNaN(parseInt($(this).val())) || parseInt($(this).val()) == 0) {
			var peca  = $(this).parents('tr').find('input[id^=peca_ref_desc]');
			var linha = $(peca).attr('id').replace(/\D/g, '');
			if ($(peca).val() != '' && $(peca).val() != null  && e.which  != 8 && e.which != 46) {
				$(this).val('1');
			}
			if (linha > 38)
				adicionarLinha(linha);

			fnc_makita_preco(linha);
		}
	}).change(function(e) {
		fnc_makita_preco(linha);
	});

	$('input[id^=peca_ref_desc]').blur(function() {
		var i = $(this).attr('id').replace(/\D/g, '');
		if ($(this).val() == '') {
			$(this).parents('tr').filter('input').each(function() {$(this).val('');});
			fnc_calcula_total(i);
		}
	});

} );
</script>
<style>
	#aviso-transporte-filial { display:none; }
	#aviso-transporte-filial .background{
		width: 100%;
		height: 100vh;
		top: 0;
		left: 0;
		position: fixed;
		background-color: #333;
		opacity: 0.2;
		z-index: 998;
	}

	#aviso-transporte-filial .content {
		width: 400px;
		background-color: #eaeaea;
		z-index: 999;
		position: fixed;
		left: 50%;
		top: 50%;
		margin-left: -200px;
		margin-top: -130px;
		text-align: left;
	}

	#aviso-transporte-filial .content .header,
	#aviso-transporte-filial .content .body {
		padding: 5px 10px;
		clear: both;
	}

	#aviso-transporte-filial .content .body {
		border-top: 1px solid #666;
		padding: 10px;
	}

	#aviso-transporte-filial .content .body p {
		background-color: inherit;
	}

	#aviso-transporte-filial .content .header h3 {
		font-weight: bold;
		float: left;
		margin: 0 0 7px 0;
		color: black;
	}

	#aviso-transporte-filial .content div span {
		float: right;
		font-size: 16px;
		font-weight: bold;
		cursor: pointer;
	}

	#aviso-transporte-filial .foot {
		padding: 10px;
		text-align: right;
		margin-bottom: 0;
	}
</style>

<div id="aviso-transporte-filial">
	<div class="background"></div>
	<div class="content">
		<div class="header">
			<h3>Aviso!</h3>
			<span class="close">&times;</span>
		</div>
		<div class="body">
			<p class="message"></p>
		</div>
		<div class="foot">
			<button type="button" name="alterar">Alterar Pedido</button>
			<button type="button" name="pagar">Pagar Frete</button>
			<button type="button" name="ok">OK</button>
		</div>
	</div>
</div>

<div id="example-content-1">
	** Critérios para condições de pagamento**<br /><br />
	- Faturamento Mínimo: R$50,00<br /><br />
	- De R$10,00 a R$99,99: Condições com um vencimento;<br /><br />
	- De R$100,00 a R$499,99: Condições com dois vencimentos;<br /><br />
	- De R$500,00 Acima: Condições com três vencimentos;<br /><br />
	Depto. Assistência Técnica
</div>
<div id="example-content-2">
	** Critérios para frete**<br /><br />
	- Pedidos de Peças/Acessórios: acima de R$ 500,00 = CIF<br /><br />
	- A baixo dos valores acima, os fretes serão FOB<br /><br />
</div>
<div id="example-content-3">
	Caso seja utilizado o 'Pedido Programado', o pedido só será enviado à Makita na data escolhida. <br />
	Para enviar um pedido na data atual, não preencha o campo.
</div>
<!--  Mensagem de Erro-->
<?
if (strlen ($msg_erro) > 0) {

	if (strpos ($msg_erro,"Cannot insert a duplicate key into unique index tbl_os_sua_os") > 0) {
		$msg_erro = "Esta ordem de serviço já foi cadastrada";
	}
	// retira palavra ERROR:
	if (strpos($msg_erro,"ERROR: ") !== false) {

		$erro = "Foi detectado o seguinte erro:<br>";
		$msg_erro = substr($msg_erro, 6);
	}
	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}
	?>
	<div id="layout">
	<div class="msg_erro">
	<? echo $msg_erro; ?>
	</div>
	</div>
<? } ?>

<?

$sql = "SELECT  tbl_condicao.*
		FROM    tbl_condicao
		JOIN    tbl_posto_condicao USING (condicao)
		JOIN    tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto_condicao.posto and tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE   tbl_posto_condicao.posto = $login_posto
		AND     tbl_condicao.fabrica     = $login_fabrica
		AND     tbl_condicao.visivel IS TRUE
		AND     tbl_condicao.descricao ilike '%garantia%'
		AND     (
					(tbl_condicao.codigo_condicao = 'OUT' and tbl_posto_fabrica.tipo_posto = 236)
						OR
					(tbl_condicao.codigo_condicao <> 'OUT' and tbl_posto_fabrica.tipo_posto <> 236)
				)
		ORDER BY lpad(trim(tbl_condicao.codigo_condicao)::text, 10,'0') ";
$res = pg_query ($con,$sql);

if (pg_num_rows ($res) > 0) {
	$frase = "Preencha seu Pedido de Compra/Garantia";
}else{
	$frase = "Preencha seu Pedido de Compra";
}
?>

<br>

<!-- OBSERVAÇÕES -->
<div id="layout">
	<div class="texto_avulso" style='width: 700px;'>
		Pedidos a Prazo Dependerão de Análise do Departamento de Crédito. <br />
		Valores aproximados. Podem haver pequenas variações. <br />
		Para pedidos antecipados, favor consultar o depto. financeiro antes de realizar o depósito. <br />
	</div>

</div>


<center>
<form name="frm_pedido" method="post" action="<?=$PHP_SELF?>" enctype="multipart/form-data">
<input type="hidden" name="pedido" value="<? echo $pedido; ?>">
<input type="hidden" name="envia_imagem" value="no">
<input type="hidden" name="voltagem" value="<? echo $voltagem; ?>">
<p>
<? if ($distribuidor_digita == 't' AND $ip == '201.0.9.216') { ?>
	<table width="100%" border="0" cellspacing="5" cellpadding="0">
	<tr valign='top' style='font-size:12px'>
		<td nowrap align='center'>
		Distribuidor pode digitar pedidos para seus postos.
		<br>
		Digite o código do posto
		<input type='text' name='codigo_posto' size='5' maxlength='10' value='<? echo $codigo_posto ?>'>
		ou deixe em branco para seus próprios pedidos.
		</td>
	</tr>
	</table>
<? } ?>

<br>

<!-- INICIA DIVISÃO -->

<ul id="split"  style="width:700px;" bgcolor="#D9E2EF" style="margin-left:50px;">
<li id="one">

<div class='formulario' style="margin-left:0px;width:100%">
	<table width='700' class='formulario' align='center'>
		<tr>
			<td class="titulo_tabela" width="100%">Cadastro de Pedido</td>
		</tr>
		<tr class='subtitulo'>
			<td align="center"><? echo $frase; ?> </td>
		</tr>
	</table>

<?#HD 401553
    if ($login_fabrica == 42){
?>

	<p><span class='coluna1'>Filial <!-- <img src="admin/imagens/help.png" alt="Ajuda" id="example-target-2" /> --></span>

		<?
		if (!strlen ($cook_pedido) > 0){?>

			<select name="filial_posto" id="filial_posto" class='frm'>
				<option value=""></option>
				<?


				$sql = "SELECT
							tbl_posto_fabrica.nome_fantasia,
							tbl_posto_filial.filial_posto
						FROM tbl_posto_fabrica
						JOIN tbl_posto_filial on tbl_posto_fabrica.posto = tbl_posto_filial.filial_posto
						WHERE tbl_posto_fabrica.fabrica=$login_fabrica
						AND tbl_posto_filial.posto = $login_posto
						AND tbl_posto_fabrica.filial IS TRUE
						AND tbl_posto_filial.faturado IS TRUE
						order by tbl_posto_fabrica.posto;
				";

				$res = pg_query($con,$sql);

				if (pg_num_rows($res)>0){
					for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
						$nome_fantasia = pg_fetch_result ($res,$i,'nome_fantasia');
						$posto_distribuidor = pg_fetch_result ($res,$i,'filial_posto');

						$selected_filial = ($filial_posto == $posto_distribuidor) ? "SELECTED" : null;

						echo "<option value='$posto_distribuidor' $selected_filial>";
							echo $nome_fantasia;
						echo "</option>";
					}
				}
				?>
			</select>
		<?}else{

			$sql = "SELECT
							tbl_posto_fabrica.nome_fantasia,
							tbl_posto_filial.filial_posto
						FROM tbl_posto_fabrica
						JOIN tbl_posto_filial on tbl_posto_fabrica.posto = tbl_posto_filial.filial_posto
						JOIN tbl_pedido on tbl_posto_fabrica.fabrica = tbl_pedido.fabrica AND tbl_posto_filial.filial_posto = tbl_pedido.filial_posto
						WHERE tbl_posto_fabrica.fabrica=$login_fabrica
						AND tbl_posto_filial.posto = $login_posto
						AND tbl_posto_fabrica.filial is true
						AND tbl_pedido.pedido = $cook_pedido
						order by tbl_posto_fabrica.posto;
				";
				$res = pg_query($con,$sql);

				if (pg_num_rows($res)>0){
					$nome_fantasia = pg_fetch_result ($res,0,'nome_fantasia');
					$posto_distribuidor = pg_fetch_result ($res,0,'filial_posto');
				}

		?>
			<input type="hidden" value="<?echo $posto_distribuidor?>" name="filial_posto" />

			<?echo $nome_fantasia . '&nbsp;<p></p>'?>
		<?}?>


	</p>

	<?
	}
	?>

	<p><span class='coluna1'>Ordem de Compra</span>
	<input class="frm" type="text" name="pedido_cliente" id="pedido_cliente" size="15" maxlength="20" value="<? echo $pedido_cliente ?>">
	</p>
	<?
	$res = pg_query ("SELECT pedido_escolhe_condicao FROM tbl_fabrica WHERE fabrica = $login_fabrica");

	#permite_alteracao - HD 47695
	if (pg_fetch_result ($res,0,0) == 'f' OR $permite_alteracao == 't') {
		echo "<input type='hidden' name='condicao' value=''>";
	} else { ?>
	<p><span class='coluna1'>Condição Pagamento <img src="admin/imagens/help.png" alt="Ajuda" id="example-target-1" /></span>
		<input type='hidden' id='validacondicao' name='validacondicao' value=''>
		<input type='hidden' id='condicaoanterior' name='condicaoanterior' value=''>
		<input type="hidden" name="btn_acao" value="">
		<select size='1' id='condicao' name='condicao' class='frm'  onchange='confirmaCondicao(this.options[this.selectedIndex].text)' >
			<option value="">- selecione</option>
		<?
			//echo "<option value=''></option>";
			 if (strlen($cook_pedido)==0) {
				 $sql1 = "SELECT  tbl_condicao.*
						FROM    tbl_condicao
						JOIN    tbl_posto_condicao USING (condicao)
						JOIN    tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto_condicao.posto and tbl_posto_fabrica.fabrica = $login_fabrica
						WHERE   tbl_posto_condicao.posto = $login_posto
						AND     tbl_condicao.fabrica     = $login_fabrica
						AND     tbl_condicao.visivel IS TRUE
						and tbl_posto_condicao.visivel
						ORDER BY lpad(trim(tbl_condicao.codigo_condicao)::text, 10,'0') ";
				$res = pg_query ($con,$sql1);

				/*if (pg_num_rows ($res) == 0 ) {
					$sql = "SELECT tbl_condicao.*
							FROM tbl_condicao
							WHERE tbl_condicao.fabrica = $login_fabrica
							AND tbl_condicao.visivel IS TRUE
							AND (
								(tbl_condicao.codigo_condicao = 'OUT' and $login_posto in (
									select posto
									from tbl_posto_fabrica
									where tipo_posto = 236 and tbl_posto_fabrica.posto = $login_posto
									and tbl_posto_fabrica.fabrica = $login_fabrica) )
								or
								(tbl_condicao.codigo_condicao != 'OUT')
							)
							ORDER BY lpad(trim(tbl_condicao.codigo_condicao)::text, 10,'0')";
					$res = pg_query ($con,$sql);

				}*/

				for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
					echo "<option value='" . pg_fetch_result ($res,$i,condicao) . "'";
					if (pg_fetch_result ($res,$i,condicao) == $condicao) echo " selected";
					echo ">" . pg_fetch_result ($res,$i,descricao) . "</option>";
				}
			} else {
					echo "<option value='$condicao' selected>$descricao_condicao</option>";
			}
		?>
		</select>
	</p>

<?php
	}

	if (strlen($cook_pedido) >= 1) {
		$query_transporte = "
			SELECT
				obs
			FROM tbl_pedido
			WHERE fabrica = {$login_fabrica}
			AND pedido = {$cook_pedido}
		";

		$res_transporte = pg_query($con, $query_transporte);
		if (strlen(pg_last_error()) === 0) {
			$obs = pg_fetch_result($res_transporte, 0, "obs");

			$obs = json_decode($obs, true);
			if (is_array($obs)) {
				$transporte = $obs['transporte'];

				if ($transporte == "RETIRA") {
					$res_nome 	= $obs["responsavel_retirada"]["nome"];
					$res_rg 	= $obs["responsavel_retirada"]["rg"];
					$res_wapp 	= $obs["responsavel_retirada"]["wapp"];
				}
			}
		}
	} else {
		$transporte = $_POST['transporte_filial'];

		$res_nome 	= $_POST["transporte_responsavel_nome"];
		$res_rg 	= $_POST["transporte_responsavel_rg"];
		$res_wapp 	= $_POST["transporte_responsavel_wapp"];
	}
?>
	<p>
		<span class='coluna1'>Transporte</span>
		<select size='1' name='transporte_filial' class='frm'>
			<option value=''>Selecione</option>
			<option value='PADRAO'>Padrão</option>
		</select>
		<input type="hidden" id="transporte" value="<?= $transporte ?>">
	</p>
	<br />
	<span id="campos-transporte-retirada" style="display:none">
		<p>
			<span class='coluna1'>Nome do Responsável</span>
			<input name='transporte_responsavel_nome' type='text' class='frm' maxlength="26" value='<?= $res_nome ?>'>
		</p>
		<p>
			<span class='coluna1'>RG do Responsável</span>
			<input name='transporte_responsavel_rg' type='text' class='frm' maxlength="9" value='<?= $res_rg ?>'>
		</p>
		<p>
			<span class='coluna1'>WhatsApp do Responsável</span>
			<input name='transporte_responsavel_wapp' type='text' class='frm' value='<?= $res_wapp ?>'>
		</p>
	</span>

		<?
		//VERIFICA SE POSTO PODE PEDIR PECA EM GARANTIA ANTECIPADA
		$sql = "SELECT garantia_antecipada FROM tbl_posto_fabrica
				WHERE fabrica=$login_fabrica AND posto=$login_posto";
		$res = pg_query ($con,$sql);
		if (pg_num_rows($res) > 0) {
			$garantia_antecipada = pg_fetch_result($res,0,0);
			if($garantia_antecipada <> "t") {
				$garantia_antecipada ="f";
			}
		}
		?>

		<p><span class='coluna1'>Tipo de Pedido</span>
		<?
		// se posto pode escolher tipo_pedido

		if (strlen($cook_pedido)==0) {
			$sql = "SELECT   *
					FROM     tbl_posto_fabrica
					WHERE    tbl_posto_fabrica.posto   = $login_posto
					AND      tbl_posto_fabrica.fabrica = $login_fabrica";
			$res = pg_query ($con,$sql);


				if($pedido_venda =='t' && ($pedido_consumo_proprio or $pedido_consumo == 't')) {
					$cond_locadora = "AND tbl_tipo_pedido.tipo_pedido in (187,136)";
				}elseif($pedido_venda =='t') {
					$cond_locadora = "AND tbl_tipo_pedido.tipo_pedido in (136)";
				}elseif($pedido_consumo_proprio or $pedido_consumo =='t'){
					$cond_locadora = "AND tbl_tipo_pedido.tipo_pedido in (187)";
				}else{
					$cond_locadora = "AND tbl_tipo_pedido.tipo_pedido in (136)";
				}


			if (pg_num_rows($res) > 0) {
				echo "<select size='1' name='tipo_pedido' class='frm'>";
				$sql = "SELECT   *
						FROM     tbl_tipo_pedido
						WHERE    fabrica = $login_fabrica
						$cond_locadora";
				$sql .= " ORDER BY tipo_pedido DESC ";
				$res = pg_query ($con,$sql);

				for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
					echo "<option value='" . pg_fetch_result($res,$i,tipo_pedido) . "'";
					if (pg_fetch_result ($res,$i,tipo_pedido) == $tipo_pedido){
						echo " selected";
					}
					echo ">" . pg_fetch_result($res,$i,descricao) . "</option>";
				}
				echo "</select>";
			}else{
				echo "<select size='1' name='tipo_pedido' class='frm'>";

				$sql = "SELECT   *
						FROM    tbl_tipo_pedido
						WHERE   (tbl_tipo_pedido.descricao ILIKE '%Faturado%'
						   OR	tbl_tipo_pedido.descricao ILIKE '%Venda%')
						AND     tbl_tipo_pedido.fabrica = $login_fabrica
						AND     (garantia_antecipada is false or garantia_antecipada is null)
						ORDER BY tipo_pedido;";

				#HD 47695
				if (strlen($tipo_pedido)> 0 AND $permite_alteracao == 't'){
					$sql = "SELECT   *
							FROM     tbl_tipo_pedido
							WHERE    fabrica = $login_fabrica ";
					if (strlen($tipo_pedido)>0){
						$sql .= " AND      tbl_tipo_pedido.tipo_pedido = $tipo_pedido ";
					}
					$sql .= " ORDER BY tipo_pedido;";
				}

				$res = pg_query ($con,$sql);

				for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
					echo "<option value='" . pg_fetch_result($res,$i,tipo_pedido) . "'";
					if (pg_fetch_result ($res,$i,tipo_pedido) == $tipo_pedido) echo " selected";
					echo ">" . pg_fetch_result($res,$i,descricao) . "</option>";
				}
				if($garantia_antecipada=="t"){
					$sql = "SELECT   *
							FROM     tbl_tipo_pedido
							WHERE    fabrica = $login_fabrica
							AND garantia_antecipada is true
							ORDER BY tipo_pedido ";
					$res = pg_query ($con,$sql);

					for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
						echo "<option value='" . pg_fetch_result($res,$i,tipo_pedido) . "'";
						if (pg_fetch_result ($res,$i,tipo_pedido) == $tipo_pedido){
							echo " selected";
						}
						echo ">" . pg_fetch_result($res,$i,descricao) . "</option>";
					}
				}
				echo "</select>";
			}
		} else {
			echo "<select size='1' name='tipo_pedido' class='frm'>";
			echo "<option value='$tipo_pedido'>$tipo_pedido_descricao</option>";
			echo "</select>";
		}
		?>
		</p>

		<?#-------------------- Transportadora -------------------

		#HD 47695 - Para pedidos a serem alterados, nao mostrar a transportadora.
		if ($permite_alteracao != 't'){
			$sql = "SELECT	tbl_transportadora.transportadora        ,
							tbl_transportadora.cnpj                  ,
							tbl_transportadora.nome                  ,
							tbl_transportadora_fabrica.codigo_interno
					FROM	tbl_transportadora
					JOIN	tbl_transportadora_fabrica USING(transportadora)
					JOIN	tbl_fabrica USING(fabrica)
					WHERE	tbl_transportadora_fabrica.fabrica        = $login_fabrica
					AND		tbl_transportadora_fabrica.ativo          = 't'
					AND		tbl_fabrica.pedido_escolhe_transportadora = 't'";
			$res = pg_query ($con,$sql);
			if (pg_num_rows ($res) > 0) {
			?>
				<p><span class='coluna1'>Transportadora</span>
				<?
					if (pg_num_rows ($res) <= 20) {
						echo "<select name='transportadora' class='frm'>";
						echo "<option selected></option>";
						for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
							echo "<option value='".pg_fetch_result($res,$i,transportadora)."' ";
							if ($transportadora == pg_fetch_result($res,$i,transportadora) ) echo " selected ";
							echo ">";
							echo pg_fetch_result($res,$i,codigo_interno) ." - ".pg_fetch_result($res,$i,nome);
							echo "</option>\n";
						}
						echo "</select>";
					}else{
						echo "<input type='hidden' name='transportadora' value='' value='$transportadora'>";
						echo "<input type='hidden' name='transportadora_cnpj' value='$transportadora_cnpj'>";

						echo "<input type='text' name='transportadora_codigo' size='5' maxlength='10' value='$transportadora_codigo' class='textbox' onblur='javascript: lupa_transportadora_codigo.click()'>&nbsp;<img id='lupa_transportadora_codigo' src='imagens/btn_lupa_novo.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_codigo,'codigo')\" style='cursor:pointer;'>";

						echo "<input type='text' name='transportadora_nome' size='35' maxlength='50' value='$transportadora_nome' class='textbox' >&nbsp;<img id='lupa_transportadora_nome' src='imagens/btn_lupa_novo.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_nome,'nome')\" style='cursor:pointer;'>";
					}
					?>
				</p>
		<? }
		}?>

		<?#-------------------- Linha do pedido -------------------

		#HD 47695 - Para alterar o pedido, mas nao a linha, por causa da tabela de preço
		if ($permite_alteracao == 't' and strlen($linha)>0){
			?><input type="hidden" name="linha" value="<? echo $linha; ?>"><?
		}else{
			$sql = "SELECT	tbl_linha.linha            ,
							tbl_linha.nome
					FROM	tbl_linha
					JOIN	tbl_fabrica USING(fabrica)
					JOIN	tbl_posto_linha  ON tbl_posto_linha.posto = $login_posto
											AND tbl_posto_linha.linha = tbl_linha.linha
					WHERE	tbl_fabrica.linha_pedido is true
					AND     tbl_linha.fabrica = $login_fabrica ";

			#permite_alteracao - HD 47695
			if (strlen($tipo_pedido)> 0 AND $permite_alteracao == 't' and strlen($linha)>0){
				$sql .= " AND tbl_linha.linha = $linha ";
			}
			$res = pg_query ($con,$sql);
			if (pg_num_rows ($res) > 0) {
			?>
				<p><span class='coluna1'>Linha</span>
						<?
						echo "<select name='linha' class='frm' ";
						echo ">";
						echo "<option selected></option>";
						for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
							echo "<option value='".pg_fetch_result($res,$i,linha)."' ";
							if ($linha == pg_fetch_result($res,$i,linha) ) echo " selected";
							echo ">";
							echo pg_fetch_result($res,$i,nome);
							echo "</option>\n";
						}
						echo "</select>";
						?>
				</p>
			<?
			}
		}
		?>
		<p>
			<span class='coluna1'>Pedido Programado <img src="admin/imagens/help.png" alt="Ajuda" id="example-target-3" /></span>
			<input class="frm" type="text" name="pedido_programado" size="9" id="pedido_programado" value="<?=$pedido_programado?>" />
		</p>
		<? $file_show = (!empty($cook_pedido)) ? "display: block" : "display: none" ; ?>
		<p id="arquivo_pedido_txt" style="<?=$file_show?>">
			<span class='coluna1'>Enviar pedido em arquivo TXT</span>
			<input class="frm" type="file" name="arquivo_txt" id="arquivo_txt" />
			<input type="hidden" name="enviaImagem" id="enviaImagem" value="" />
			<input type="hidden" name="dados_txt" id="dados_txt" value="" />
			<br>
			<span class='coluna1'>O arquivo deve ter no máximo 600 linhas</span>
			<span class='coluna2'><div id="mensagem_upload"></div></span>
			<br>

			<a href="javascript:void(0);" style="margin-left:25px;" class='coluna1' onclick="mudaDisplay('exemploTexto')">Formato de Arquivo de Exportação</a>
			<br>
			<br>
			<span id="exemploTexto" class='coluna1' style="display:none;width:80%;">

				O Formato do arquivo deve seguir o exemplo abaixo<br><br>

				<div style="border:1px solid #ccc;padding:5px;width:80%;">
					Referencia da Peça;Quantidade;<br>
					Referencia da Peça;Quantidade;<br>
					Referencia da Peça;Quantidade;
				</div>
			</span>
		</p>

		<table class='formulario' width='700px' align='center'>
			<tr class='subtitulo'>
				<td align="center"> Peças </td>
			</tr>
		</table>
		<input class="frm" type="hidden" name="produto_referencia" value="<? echo $produto_referencia; ?>">

		<!-- Peças -->
		<p class='formulario'>
		<table border="0" width='650' cellspacing="0" cellpadding="2" align="center" class='formulario'  name="tabela_itens" id="tabela_itens">
			<tr height="20" class='titulo_coluna'>
				<td align='left'>Ref. Componente/Descricao Componente</td>
				<td align='center'>Qtde</td>
				<td align='center'>Preço Unit.</td>
				<td align='center'>Total</td>
			</tr>

			<?
			$total_geral = 0;

			echo "<input type='hidden' name='qtde_item' value='$qtde_item' id='qtde_item'>";
			for ($i = 0 ; $i <= $qtde_item ; $i++) {


				if (strlen($pedido) > 0){	// AND strlen ($msg_erro) == 0
					$sql = "SELECT  tbl_pedido_item.pedido_item,
									tbl_peca.referencia        ,
									tbl_peca.descricao         ,
									tbl_pedido_item.qtde       ,
									tbl_pedido_item.preco
							FROM  tbl_pedido
							JOIN  tbl_pedido_item USING (pedido)
							JOIN  tbl_peca        USING (peca)
							WHERE tbl_pedido_item.pedido = $pedido
							AND   tbl_pedido.posto   = $login_posto
							AND   tbl_pedido.fabrica = $login_fabrica
							ORDER BY tbl_pedido_item.pedido_item";

					$res = pg_query ($con,$sql);

					if (pg_num_rows($res) > 0) {
						$pedido_item     = trim(@pg_fetch_result($res,$i,pedido_item));
						$peca_referencia = trim(@pg_fetch_result($res,$i,referencia));
						$peca_descricao  = trim(@pg_fetch_result($res,$i,descricao));
						$qtde            = trim(@pg_fetch_result($res,$i,qtde));
						$preco           = trim(@pg_fetch_result($res,$i,preco));
						if (strlen($preco) > 0) $preco = number_format($preco,2,',','.');

						$produto_referencia = '';
						$produto_descricao  = '';
					}else{
						$produto_referencia= $_POST["produto_referencia_"     . $i];
						$produto_descricao = $_POST["produto_descricao_" . $i];
						$pedido_item     = $_POST["pedido_item_"     . $i];
						$peca_referencia = $_POST["peca_referencia_" . $i];
						$peca_descricao  = $_POST["peca_descricao_"  . $i];
						$qtde            = $_POST["qtde_"            . $i];
						$preco           = $_POST["preco_"           . $i];
					}
				}else{
					$produto_referencia= $_POST["produto_referencia_"     . $i];
					$produto_descricao = $_POST["produto_descricao_" . $i];
					$pedido_item      =  $pedido_items[$i];
					$peca_referencia  =  $peca_referencias[$i];
					$peca_ref_descricao  =  $peca_ref_descricaos[$i];
					$peca_descricao   =  $peca_descricaos[$i];
					$qtde             =  $qtdes[$i];
					$preco            =  $precos[$i];
				}

				$peca_referencia = trim ($peca_referencia);

				#--------------- Valida Peças em DE-PARA -----------------#
				$tem_obs = false;
				$linha_obs = "";

				if (strlen ($peca_referencia) > 0) {
					$sql = "SELECT para FROM tbl_depara WHERE de = '$peca_referencia' AND fabrica = $login_fabrica";
					$resX = pg_query ($con,$sql);

					if (pg_num_rows ($resX) > 0) {
						$linha_obs = "Peça original " . $peca_referencia . " mudou para o código acima <br>&nbsp;";
						$peca_referencia = pg_fetch_result ($resX,0,0);
						$tem_obs = true;
					}

					#--------------- Valida Peças Fora de Linha -----------------#
					$sql = "SELECT * FROM tbl_peca_fora_linha WHERE referencia = '$peca_referencia' AND fabrica = $login_fabrica";

					$resX = pg_query ($con,$sql);
					if (pg_num_rows ($resX) > 0) {
						$libera_garantia = pg_fetch_result ($resX,0,libera_garantia);
						$linha_obs .= "Peça acima está fora de linha <br>&nbsp;";
						$tem_obs = true;
					}

					$sql = "SELECT descricao FROM tbl_peca WHERE referencia = '$peca_referencia' AND fabrica = $login_fabrica";
					$resX = pg_query ($con,$sql);
					if (pg_num_rows ($resX) > 0) {
						$peca_descricao = pg_fetch_result ($resX,0,0);
					}
				}

				$peca_descricao = trim ($peca_descricao);

				$cor="";
				if ($linha_erro == $i and strlen ($msg_erro) > 0) $cor='#ffcccc';
				if ($linha_erro == $i and strlen ($msg_erro) > 0) $cor='#ffcccc';
				if ($tem_obs) $cor='#FFCC33';
			?>
				<tr bgcolor="<? echo $cor ?>" nowrap>

					<td align='left'>
						<input type="hidden" name="pedido_item[]" size="15" value="<? echo $pedido_item; ?>">

						<?php
						//echo "<input class='frm auto_comp' type='text' id='peca_ref_descricao_$i' name='peca_ref_descricao_$i' size='60' onfocus='autocompletar_item(\"peca_ref_descricao_$i\",\"peca_referencia_$i\",this.value,$i)' value='$peca_descricao'>";
						echo "<input class='frm auto_comp' type='text' id='peca_ref_descricao_$i' name='peca_ref_descricao[]' size='53' rel='$i' value='$peca_ref_descricao'>";
						?>

						<input class="frm" type="hidden" name="peca_referencia[]" id="peca_referencia_<?=$i?>" size="15" value="<? echo $peca_referencia; ?>">
						<input type="hidden" name="posicao">
						<input class="frm" type="hidden" id="peca_descricao_<? echo $i ?>" name="peca_descricao[]" size="20" value="<? echo $peca_descricao ?>">
					</td>
					<td align='center'>
						<input class="frm qtde" type="text" name="qtde[]" id="qtde_<? echo $i ?>" size="5" maxlength='5' value="<? echo $qtde ?>"
					</td>

					<td align='center'>
						<input class="frm" id="preco_<? echo $i ?>" type="text" name="preco[]" size="10"  value="<? echo $preco ?>" readonly  style='text-align:right; color:#000;' >
					</td>

					<td align='center'>

						<input class="frm" name="sub_total[]" id="sub_total_<? echo $i ?>" type="text" size="10" rel='total_pecas' readonly  style='text-align:right; color:#000;' value='<?

								if ($qtde &&  $preco) {
									if( $preco == '' || $preco == 0 || $preco == null )
									{
										$preco = 1;
									}
									$preco = str_replace(',','.',$preco);
									$total_geral += $preco * $qtde;

									$preco = $preco * $qtde;
									$preco = number_format($preco,2,',','.');
									echo $preco;
								}
							?>'>
							<?php ?>
					</td>

				</tr>

				<?
				if ($tem_obs) {
					echo "<tr bgcolor='#FFCC33' style='font-size:12px'>";
					echo "<td colspan='4'>$linha_obs</td>";
					echo "</tr>";
				}
				echo "<tr>";
				echo "<td  colspan='7'><div id='mudou_$i' style='display: none; width=700'></div></td>";
				echo "</tr>";
				?>

			<?
			}
			?>
			</table>
			<?
				echo "<table border='0' cellspacing='0' cellpadding='2' align='center' class='xTabela' width='640px'>";
				echo "<tr style='font-size:12px' align='right'>";
				echo "<td colspan='7' allign='right'><b>Total</b>: <INPUT TYPE='text' size='10' style='text-align:right' id='total_pecas'";
					if(strlen($total_geral) > 0)
					{
						$total_geral = number_format($total_geral,2,',','.');
					    echo " value='$total_geral'";
					}
				echo "></td>";
				echo "</tr>";
				echo "</table>";
			?>
		</p>
		<p><center>
		<table>
			<tr>
				<td>
					<span name="limite_linhas_texto" class='coluna1'>
						O arquivo deve ter no máximo 600 linhas <br><br>
					</span>
				</td>
			</tr>
			<tr>
				<td>
					<center>
					<input type='button' value='Importa do Excel' onclick="javascript: abreImportaExcel(); frm_pedido.lote_pecas.value='' ; frm_pedido.lote_pecas.focus()">
				</center>
				</td>
			</tr>
		</table>
<br>


		<div id='div_importa_excel' style='display: none ; position: absolute ; top: 300px ; left: 10px ; background-color:#D9E2EF ; width: 600px ; border:solid 1px #330099 ' onkeypress="if(event.keyCode==27){fechaImportaExcel() ;}">
			<div id="div_lanca_peca_fecha" style="float:right ; align:center ; width:20px ; background-color:#FFFFFF " onclick="fechaImportaExcel() ;" onmouseover="this.style.cursor='pointer'">
				<center><b>X</b></center>
			</div>
			<br>
			<b>Importa Peças do Excel</b>
			<br>
			<font size='-1'>
			Para importar peças do Excel, formate uma planilha apenas com 2 colunas (código da peça e quantidade). Copíe e cole estas colunas no campo abaixo (não copie a linha de cabeçalho).
			</font>
			<br>
			<textarea name='lote_pecas' id='lote_pecas' cols='25' rows='10'></textarea>
			<br>
			<script language='javascript'>
			function ebano(){


				return true;
			}
			</script>
			<input type='button' value='Importar' onclick="javascript:  document.getElementById('divAguarde').style.display='block'; setTimeout('importarPecas(document.frm_pedido.lote_pecas);', 2000); fechaImportaExcel();">
		</div>
		<input type='button' id="btn_gravar" value='Gravar' onclick="javascript: if (document.frm_pedido.btn_acao.value == '' ) { document.frm_pedido.btn_acao.value='gravar' ; alert('Após inserir todos os Ítens desejados clique em Finalizar'); document.frm_pedido.submit();} else { alert ('Aguarde submissão') } " border='0' style='cursor: pointer'>
		</center>
		</p>
		</div>
		</li>
		</ul>
<!-- Fecha Divisão-->
</form>
<div id='divAguarde' style='position:absolute; display:none; top:500px; left:350px; background-color: #99CCFF; width: 300px; height:100px;'>
<center>
Aguarde Carregando...<br>
<img src='imagens/ajax-azul.gif'>
</center>
</div>
<br clear='both'>
<p>

<?
	$pedido = $cook_pedido;

if (!empty($pedido))
{
	$sql = "SELECT	a.oid    ,
					a.*      ,
					referencia,
					descricao
			FROM	tbl_peca
			JOIN	(
						SELECT	tbl_pedido_item.oid,tbl_pedido_item.*
						FROM	tbl_pedido_item
						JOIN    tbl_pedido USING(pedido)
						WHERE	pedido = $pedido
						AND     fabrica = $login_fabrica
					)
					a ON tbl_peca.peca = a.peca
					ORDER BY a.pedido_item";

	$res = @pg_query ($con,$sql);
	$total = 0;
	if( @pg_num_rows( $res ) > 0 )
	{


if (strlen ($cook_pedido) > 0 /*OR strlen($pedido)>0 */ ) {
?>
<table width="700" border="0" cellpadding="5" cellspacing="2" align="center" class='texto_avulso'>
<tr>
	<td align="center">
		<p>Ao terminar de inserir itens no pedido clique em finalizar, após finalizar será necessário abri um novo Pedido para os novos itens</p>
		<p>Para inserir novos itens neste pedido, basta gravar e em seguida inserir os demais itens desejados</p>
	</td>
</tr>

</form>
</table>



<br>
<?php if($login_fabrica == 42){ ?>
	<table>
		<tr>
			<td>Trocar Condição Pagamento</td>
			<td>
				<select id="troca_condicao_pagametno" name='troca_condicao_pagametno'>
					<option value=""> Selecione </option>
					<?php
						$sqlCond = "SELECT  tbl_condicao.*
							FROM    tbl_condicao
							JOIN    tbl_posto_condicao USING (condicao)
							JOIN    tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto_condicao.posto and tbl_posto_fabrica.fabrica = $login_fabrica
							WHERE   tbl_posto_condicao.posto = $login_posto
							AND     tbl_condicao.fabrica     = $login_fabrica
							AND     tbl_condicao.visivel IS TRUE
							and tbl_posto_condicao.visivel
							ORDER BY lpad(trim(tbl_condicao.codigo_condicao)::text, 10,'0') ";
						$resCond = pg_query ($con,$sqlCond);
						for ($i = 0 ; $i < pg_num_rows ($resCond) ; $i++ ) {
							echo "<option value='" . pg_fetch_result ($resCond,$i,condicao) . "'";

							echo ">" . pg_fetch_result ($resCond,$i,descricao) . "</option>";
						}

					?>
				</select>
			</td>
			<td>
			<button type="button" onclick="recalcular(<?=$pedido?>)">Recalcular</button>
			</td>
		</tr>
	</table>
<?php } ?>
<table width="700" border="0" cellpadding="3" class='tabela' cellspacing="1" align="center">
<tr>
	<td colspan="5" align="center" class='titulo_tabela'>
		Resumo do Pedido
	</td>
</tr>

<tr class='titulo_coluna'>

	<td width="25%" align='center'>
		Referência
	</td>
	<td width="40%" align='center'>
		Descrição
	</td>
	<td width="15%" align='center'>
		Quantidade
	</td>
	<td width="10%" align='center'>
		Preço
	</td>
	<td width="10%" align='center'>
		Ação
	</td>
</tr>

<?php
//var_dump($sql);
	for ($i = 0 ; $i < @pg_num_rows ($res) ; $i++) {

		$cor = ($i % 2 == 0) ? '#F1F4FA' : "#F7F5F0";

		echo "<tr bgcolor='$cor' linha='$i'>";

		echo "<td width='25%'>";
		echo pg_fetch_result ($res,$i,referencia);
		echo "</td>";

		echo "<td width='50%' align='left'>";
		echo pg_fetch_result ($res,$i,descricao);
		echo "</td>";

		echo "<td width='15%' align='center'>";
		echo pg_fetch_result ($res,$i,qtde);
		echo "</td>";

		echo "<td width='10%' align='right'>";
		$preco = number_format (pg_fetch_result ($res,$i,preco),2,",",".");
		//$preco = str_replace('.',',',$preco);
		echo $preco;
		echo "</td>";

		echo "<td width='10%' align='center' nowrap>";
		echo "<input type='button' value='Excluir' onclick=\"javascript:window.location='$PHP_SELF?delete=". pg_fetch_result ($res,$i,pedido_item)."&pedido=$pedido'\"/>";
		echo "</td>";

		echo "</tr>";

		$total = $total + (pg_fetch_result ($res,$i,preco) * pg_fetch_result ($res,$i,qtde));
	}
?>

<tr>
	<td align="center" colspan="4">
		T O T A L
	</td>
	<td align='right' style='text-align:right'>
		<b>
		<?php
			$total = number_format ($total,2,",",".");
			//$total = round($total,2);
			// $total = str_replace('.',',',$total);
			echo $total;
		?>
		</b>
		<input type="hidden" value="<?= $total ?>" id="total-pedido" />
	</td>
</tr>
</table>
<?php
}
?>


<!-- ============================ Botoes de Acao ========================= -->


<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td align='center'>
<?
?>
		<br><input type="button" id="btn-finalizar" value="Finalizar"><br><br>

	</td>
</tr>

</table>

<?
} //var_dump($cook_pedido);
}
?>


<? include "rodape.php"; ?>

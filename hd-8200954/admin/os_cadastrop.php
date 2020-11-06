<?php

//conforme chamado 474 (fabricio -  britania) na hr em que eram buscada as informacoes da OS, estava buscando na forma antiga, ou seja, estava buscando informacoes do cliente na tbl_cliente, com o novo metodo as info do consumidor sao gravados direto na tbl_os, com isso hr que estava buscando info do cliente estava buscando no local errado -  Takashi 31/09/2006

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "call_center,gerencia";

include 'autentica_admin.php';
include 'funcoes.php';

#HD 424887 - INICIO
/*

A variavel abaixo será para identificar as fábricas que terão o campo "Defeito_reclamado" sem integridade.
Por enquanto só a Fricon, quando precisar mais fábricas é só colocar adicionar nessa variável .

*/


$fabricas_defeito_reclamado_sem_integridade = array(52);

#HD 424887 - FIM

#HD 308346 - Função que anexa a nota fiscal e outras validações
include_once('../anexaNF_inc.php');

$vet_sem_preco = array(3,6,11,35,45,51,80);//HD 361213, 363345

#HD 418875 - Alert quando o produto estiver com peça obrigatória
#acesso somente via AJAX
$referencia_troca_obrigatoria = $_POST['referencia_troca_obrigatoria'];
if(strlen($referencia_troca_obrigatoria) > 0){
	$sql = "
		SELECT
			produto
		FROM
			tbl_produto
			JOIN tbl_linha USING(linha)
		WHERE
			fabrica = $login_fabrica
			AND referencia = '$referencia_troca_obrigatoria'
			AND troca_obrigatoria IS NOT NULL;";
	$res = pg_query($con,$sql); echo $sql;

	if(pg_num_rows($res)>0)
		echo 1;

	exit;
}

#HD 311414 - Atualização do campo "Causa Raiz" para TECTOY - INICIO
if ( $login_fabrica == 6){

	if ($_POST["causa_troca_select"]){

		$causa_troca_select = $_REQUEST["causa_troca_select"];

		$sql_item_causa_troca = "Select 				causa_troca_item,
																descricao,
																codigo
														FROM tbl_causa_troca_item
														WHERE causa_troca = $causa_troca_select
														AND tbl_causa_troca_item.ativo     IS TRUE
														ORDER BY codigo";
								// echo nl2br($sql_item_causa_troca);

		$res_item_causa_troca = pg_query($con,$sql_item_causa_troca);

		if ( pg_num_rows($res_item_causa_troca)>0 ){

			for ($i=0;$i < pg_num_rows($res_item_causa_troca); $i++ ){

				$item_id        = pg_fetch_result($res_item_causa_troca,$i,'causa_troca_item');
				$item_codigo    = pg_fetch_result($res_item_causa_troca,$i,'codigo');
				$item_descricao = pg_fetch_result($res_item_causa_troca,$i,'descricao');

				echo "<option value='$item_id'>$item_codigo - $item_descricao</option>";


			}

		}else{
			echo "<option value=''>Nenhum item ativo</option>";
		}
		exit;
	}

}

#HD 311414 - Atualização do campo "Causa Raiz" para TECTOY - FIM


//  Para testes da tela de pesquisa
if (preg_match('/os_cadastro(.*).php/', $PHP_SELF, $a_suffix)) {
	$suffix = $a_suffix[1];
	if (file_exists("pesquisa_numero_serie$suffix.php")) $ns_suffix = $suffix;
	if (file_exists("posto_pesquisa_2$suffix.php"))		 $pp_suffix = $suffix;
	if (file_exists("posto_pesquisa_km$suffix.php"))	 $pk_suffix = $suffix;
	if (file_exists("pesquisa_consumidor$suffix.php"))	 $pc_suffix = $suffix;
	if (file_exists("pesquisa_revenda$suffix.php"))		 $rv_suffix = $suffix;
}

if($ajax=='tipo_atendimento'){
	$sql = "SELECT tipo_atendimento,km_google
			FROM tbl_tipo_atendimento
			WHERE tipo_atendimento = $id
			AND   fabrica          = $login_fabrica";
	$res = pg_query($con,$sql); echo $sql;
	if(pg_num_rows($res)>0){

		$km_google = pg_fetch_result($res,0,km_google);
		echo ($km_google == 't') ? "ok|sim" : "no|não";
	exit;
	}
}

function validaCPF($cpf){
	global $con;
	$cpf = preg_replace('/\D/','', $cpf);
	$res = @pg_query($con, "SELECT fn_valida_cnpj_cpf('$cpf')");
	return(pg_last_error($con) == '');
}

# HD 33729 - Francisco Ambrozio (19/8/08)
#   Campos "Capacidade" e "Divisão" preenchidos por ajax.
if($_GET["ajax"]=="true" AND $_GET["buscaInformacoes"]=="true"){
	$referencia = trim($_GET["produto_referencia"]);
	$serie      = trim($_GET["serie"]);

	if(strlen($referencia)>0){
		$sql = "SELECT produto, capacidade, divisao
				FROM tbl_produto
				JOIN tbl_linha USING(linha)
				WHERE fabrica  = $login_fabrica
				AND referencia ='$referencia'";
		$res = @pg_query($con,$sql); echo $sql;

		if (pg_num_rows($res)>0){
			$produto    = trim(pg_fetch_result($res,0,produto));
			$capacidade = trim(pg_fetch_result($res,0,capacidade));
			$divisao    = trim(pg_fetch_result($res,0,divisao));

			echo "ok|$capacidade|$divisao|$versao";
			exit;
		}
	}
	echo "nao|nao";
	exit;
}

$fabricas_alteram_conserto = array(6,80);

/*IGOR HD: 44202 - 16/10/2008*/
if ($login_fabrica == 3){
	$xos = $_GET['os'];
	if (strlen($xos) == 0) {
		$xos = $_POST['os'];
	}
	if (strlen($xos) > 0) {
		$status_os = "";
		$sql = "SELECT status_os
				FROM  tbl_os_status
				WHERE os=$xos
				AND status_os IN (120, 122, 123, 126)
				ORDER BY data DESC LIMIT 1";
		$res_intervencao = pg_query($con, $sql);
		$msg_erro        = pg_errormessage($con);

		if (pg_num_rows ($res_intervencao) > 0 ){
			$status_os = pg_fetch_result($res_intervencao,0,status_os);
			if ($status_os=="122"){
				header ("Location: os_press.php?os=$xos");
				exit;
			}
		}
	}
}

// HD 31188
if($_GET["ajax"]=="true" AND $_GET["buscaValores"]=="true"){
	$referencia = trim($_GET["produto_referencia"]);

	if(strlen($referencia)>0){
		$sql = "SELECT produto, capacidade, divisao
				FROM tbl_produto
				JOIN tbl_linha USING(linha)
				WHERE fabrica  = $login_fabrica
				AND referencia ='$referencia'";
		$res = @pg_query($con,$sql); echo $sql;

		if (pg_num_rows($res)>0){
			$produto    = trim(pg_fetch_result($res,0,produto));

			$sql = "SELECT  taxa_visita,
							hora_tecnica,
							valor_diaria,
							valor_por_km_caminhao,
							valor_por_km_carro,
							regulagem_peso_padrao,
							certificado_conformidade
					FROM    tbl_familia_valores
					JOIN    tbl_produto USING(familia)
					WHERE   tbl_produto.produto = $produto";
			$res = pg_query ($con,$sql); echo $sql;
			if (pg_num_rows($res) > 0) {
				$taxa_visita              = number_format(trim(pg_fetch_result($res,0,taxa_visita)),2,',','.');
				$hora_tecnica             = number_format(trim(pg_fetch_result($res,0,hora_tecnica)),2,',','.');
				$valor_diaria             = number_format(trim(pg_fetch_result($res,0,valor_diaria)),2,',','.');
				$valor_por_km_caminhao    = number_format(trim(pg_fetch_result($res,0,valor_por_km_caminhao)),2,',','.');
				$valor_por_km_carro       = number_format(trim(pg_fetch_result($res,0,valor_por_km_carro)),2,',','.');
				$regulagem_peso_padrao    = number_format(trim(pg_fetch_result($res,0,regulagem_peso_padrao)),2,',','.');
				$certificado_conformidade = number_format(trim(pg_fetch_result($res,0,certificado_conformidade)),2,',','.');

				/* HD 46784 */
				$sql = "SELECT  valor_regulagem, valor_certificado
						FROM    tbl_capacidade_valores
						WHERE   fabrica = $login_fabrica
						AND     capacidade_de <= (SELECT capacidade FROM tbl_produto WHERE produto = $produto )
						AND     capacidade_ate >= (SELECT capacidade FROM tbl_produto WHERE produto = $produto ) ";
				$res = pg_query ($con,$sql); echo $sql;
				if (pg_num_rows($res) > 0) {
					$regulagem_peso_padrao    = number_format(trim(pg_fetch_result($res,0,valor_regulagem)),2,',','.');
					$certificado_conformidade = number_format(trim(pg_fetch_result($res,0,valor_certificado)),2,',','.');
				}

				echo "ok|$taxa_visita|$hora_tecnica|$valor_diaria|$valor_por_km_carro|$valor_por_km_caminhao|$regulagem_peso_padrao|$certificado_conformidade";
				exit;
			}
			exit;
		}
	}
	echo "nao|nao";
	exit;
}

//  HD 234135 - MLG - Para fazer com que uma fábrica use a tbl_revenda_fabrica, adicionar ao array
$usa_rev_fabrica = in_array($login_fabrica, array(3));

/*********************** FECHA OS LENOXX HD 52209 **************************/
if ($btn_acao=="fechar_os" AND $login_fabrica==11) {
	$msg_erro = "";
	$res = pg_query ($con,"BEGIN TRANSACTION");

	$sql_obs            = "SELECT orientacao_sac from tbl_os join tbl_os_extra using(os) where os = $os";
	$res_obs            = pg_query($con,$sql_obs);
	$orientacao_sac_aux = pg_fetch_result($res_obs,0,orientacao_sac);

	$sql_usario  = "SELECT login from tbl_admin where admin = $login_admin";
	$res_usuario = pg_query($con,$sql_usario);
	$usuario     = pg_fetch_result($res_usuario,0,login);

	$data_hoje = date("d/m/Y H:i:s");
	$orientacao_sac .= "<p>OS fechada pelo Admin: $usuario</p>";
	$orientacao_sac .= "<p>Data: $data_hoje</p>";
	$orientacao_sac .= $orientacao_sac_aux;

	$sql = "UPDATE  tbl_os_extra SET orientacao_sac = trim('$orientacao_sac')
	WHERE tbl_os_extra.os = $os;";
	$res = pg_query ($con,$sql); echo $sql;
	$msg_erro .= pg_errormessage($con);

	if (strlen ($msg_erro) == 0) { #HD 94416
	$sql = "UPDATE tbl_os_extra SET admin_paga_mao_de_obra = 't' WHERE os = $os";
	$res = pg_query ($con,$sql); echo $sql;
	$msg_erro .= pg_errormessage($con) ;
	}

	if (strlen ($msg_erro) == 0) {#HD 94361
		$sql = "UPDATE tbl_os SET data_fechamento = CURRENT_DATE, admin = $login_admin WHERE os = $os AND fabrica = $login_fabrica";
		$res = pg_query ($con,$sql); echo $sql;
		$msg_erro .= pg_errormessage($con) ;
	}

	if (strlen ($msg_erro) == 0 && $login_fabrica != 95) {
		$sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
		#echo $sql;
		$res = @pg_query ($con,$sql); echo $sql;
		$msg_erro = pg_errormessage($con) ;
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_query ($con,"COMMIT TRANSACTION");
		header("Location: os_cadastro.php?os=$os");
		exit;
	}else{
		$res = @pg_query ($con,"ROLLBACK TRANSACTION");
	}
}
/************************ FIM FECHA OS **************************/

$sql = "SELECT * FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = pg_query ($con,$sql); echo $sql;
$pedir_sua_os = pg_fetch_result ($res,0,pedir_sua_os);

if (strlen($_POST['os']) > 0){
	$os = trim($_POST['os']);
}

if (strlen($_GET['os']) > 0){
	$os = trim($_GET['os']);
}
if (strlen($_POST['os']) > 0 and strlen($_GET['os']) == 0) {
	$os = trim($_POST['os']);
}

if (strlen($_POST['sua_os']) > 0){
	$sua_os = trim($_POST['sua_os']);
}

if (strlen($_GET['sua_os']) > 0){
	$sua_os = trim($_GET['sua_os']);
}
$nosso_ip = include("../nosso_ip.php");
if(($ip=='201.43.245.148' OR ($ip==$nosso_ip) OR $login_fabrica == 15) AND $login_fabrica == 15){
	if($_GET["os"]) header("Location: os_cadastro_latina.php?os=$os");
	else            header("Location: os_cadastro_latinatec_ajax.php");
	exit;
}

if($gerar_pedido=='ok'){
	$sql = "BEGIN TRANSACTION";
	$res = pg_query($con,$sql); echo $sql;

	$sql = "UPDATE tbl_os_troca SET gerar_pedido = TRUE WHERE os = $os";
	$res = @pg_query($con,$sql); echo $sql;
	$msg_erro .= pg_errormessage($con);

	if(strlen($msg_erro)==0){
		$sql = "COMMIT TRANSACTION";
		$res = pg_query($con,$sql); echo $sql;
		header("Location: os_press.php?os=$os");
		exit;
	}else{
		$res = pg_query($con,"ROLLBACK TRANSACTION");
	}
}

$sql = "SELECT * FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = @pg_query($con,$sql); echo $sql;
$pedir_sua_os = pg_fetch_result ($res,0,pedir_sua_os);
$pedir_defeito_reclamado_descricao = pg_fetch_result ($res,0,pedir_defeito_reclamado_descricao);

$btn_cancelar = strtolower ($_POST['cancelar']);
if ($btn_cancelar == "cancelar") {
	$os                  = $_POST["os"];
	$motivo_cancelamento = trim($_POST["motivo_cancelamento"]);

	if(strlen($motivo_cancelamento)==0) $msg_erro = "Por favor digite o motivo do cancelamento da OS";
	if(strlen($msg_erro)==0){
		$sql = "SELECT DISTINCT pedido
				FROM tbl_os
				JOIN tbl_os_produto USING(os)
				JOIN tbl_os_item    USING(os_produto)
				WHERE tbl_os.fabrica = $login_fabrica
				AND   tbl_os.os      = $os
				AND   tbl_os_item.pedido IS NOT NULL";
		$res1 = @pg_query($con,$sql); echo $sql;
		if(pg_num_rows($res1)>0){
			for($i=0;$i<pg_num_rows($res1);$i++){
				$pedido = pg_fetch_result($res1,$i,0);
				$sql = "SELECT  PI.pedido_item,
						PI.qtde      ,
						PC.peca      ,
						PC.referencia,
						PC.descricao ,
						OP.os        ,
						PE.posto     ,
						PE.distribuidor
					FROM    tbl_pedido       PE
					JOIN    tbl_pedido_item  PI ON PI.pedido     = PE.pedido
					JOIN    tbl_peca         PC ON PC.peca       = PI.peca
					LEFT JOIN tbl_os_item    OI ON OI.peca       = PI.peca       AND OI.pedido = PE.pedido
					LEFT JOIN tbl_os_produto OP ON OP.os_produto = OI.os_produto
					WHERE   PI.pedido      = $pedido
					AND     PE.fabrica     = $login_fabrica
					AND     PE.exportado   IS NULL";
				$res2 = pg_query($con,$sql); echo $sql;
				if(pg_num_rows($res2)>0){
						$peca  = pg_fetch_result($res2,0,peca);
						$qtde  = pg_fetch_result($res2,0,qtde);
						$posto = pg_fetch_result($res2,0,posto);
						$sql = "UPDATE tbl_pedido_item SET qtde_cancelada = qtde WHERE pedido_item = $cancelar;";
						$res = pg_query ($con,$sql); echo $sql;
						$sql = "INSERT INTO tbl_pedido_cancelado (
									pedido,
									posto,
									fabrica,
									os,
									peca,
									qtde,
									motivo,
									data
								)VALUES(
									$pedido,
									$posto,
									$login_fabrica,
									$os,
									$peca,
									$qtde,
									'$motivo_cancelamento',
									current_date
								);";
						$res = pg_query ($con,$sql); echo $sql;
				}else{
					if($login_fabrica <> 45) $msg_erro= "OS não pode ser cancelada porque o pedido já foi exportado!";
				}
			}
		}
		if(strlen($msg_erro)==0){
			$sql = "BEGIN TRANSACTION";
			$res = pg_query($con,$sql); echo $sql;
			$sql = "INSERT INTO tbl_os_status (os,status_os,observacao) VALUES ($os,15,'$motivo_cancelamento');";
			$res = pg_query($con,$sql); echo $sql;
			$msg_erro .= pg_errormessage($con);
			$sql = "UPDATE tbl_os SET excluida = TRUE WHERE os = $os";
			$res = pg_query($con,$sql); echo $sql;
			$msg_erro .= pg_errormessage($con);

			#158147 Paulo/Waldir desmarcar se for reincidente
			$sql = "SELECT fn_os_excluida_reincidente($os,$login_fabrica)";
			$res = pg_query($con, $sql);

			if(strlen($msg_erro)==0){
				$sql = "COMMIT TRANSACTION";
				$res = pg_query($con,$sql); echo $sql;
				header("Location: os_press.php?os=$os");
				exit;
			}else{
				$res = pg_query($con,"ROLLBACK TRANSACTION");
			}
		}
	}

}


/*======= Troca em Garantia =========*/

$btn_troca = strtolower ($_POST['btn_troca']);

if ($btn_troca == "trocar") {

	// HD 410675 - Colocado para todas as fábricas
	$sql = "SELECT os FROM tbl_os_extra WHERE os = $os AND extrato IS NOT NULL";
	$res = pg_query($con,$sql); echo $sql;

	if (pg_num_rows($res)) {

		$msg_erro = "OS já entrou em extrato e não pode ser trocada. ";

	}

	if ($login_fabrica == 91) {//HD 702297

		$sql = "SELECT tbl_os.os
				FROM tbl_os
				JOIN tbl_solucao ON tbl_os.solucao_os = tbl_solucao.solucao AND tbl_solucao.troca_peca
				JOIN tbl_os_produto USING(os)
				JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto AND pedido IS NULL
				JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND gera_pedido AND tbl_servico_realizado.troca_de_peca
				WHERE tbl_os.fabrica = $login_fabrica
				AND tbl_os.os = $os";

		$res = pg_query($con,$sql); echo $sql;

		if (pg_num_rows($res)) {

			$msg_erro = 'Troca não pode ser efetuada, aguarde gerar o pedido da peça.';

		}

	}

	// HD 679319 - Fim
	if (isset($_POST["marca_troca"]) && strlen($_POST["marca_troca"]) == 0) {
		$msg_erro .= "Selecione a MARCA do Produto<br>";
	}
	else {
		$marca_troca = $_POST["marca_troca"];
	}

	if (isset($_POST["familia_troca"]) && strlen($_POST["familia_troca"]) == 0) {
		$msg_erro .= "Selecione a FAMÍLIA do Produto<br>";
	}
	else {
		$familia_troca = $_POST["familia_troca"];
	}

	if (isset($_POST["troca_garantia_produto"]) && strlen($_POST["troca_garantia_produto"]) == 0) {
		$msg_erro .= "Selecione o PRODUTO para troca<br>";
	}
	else {
		$troca_garantia_produto = $_POST["troca_garantia_produto"];
	}

	if (isset($_POST["causa_troca"]) && strlen($_POST["causa_troca"]) == 0) {
		$msg_erro .= "Selecione o CAUSA da troca<br>";
	}
	else {
		$causa_troca = $_POST["causa_troca"];
	}

	if ($login_fabrica == 6){

		if (isset($_POST["causa_raiz"]) && strlen($_POST["causa_raiz"]) == 0) {
			$msg_erro .= "Selecione a Causa Raiz<br>";
		}
		else {
			$causa_raiz = $_POST["causa_raiz"];
		}

	}

	if($login_fabrica==51){
		if (isset($_POST["coleta_postagem"]) && strlen($_POST["coleta_postagem"]) == 0) {
			$msg_erro .= "Informe o N° Coleta/Postagem<br>";
		}
		else {
			$coleta_postagem = $_POST["coleta_postagem"];
		}

		if (isset($_POST["data_postagem"]) && strlen($_POST["data_postagem"]) == 0) {
			$msg_erro .= "Informe a Data Solicitação<br>";
		}
		else {
			$data_postagem   = $_POST["data_postagem"];
		}
	}

	if (isset($_POST["observacao_pedido"]) && strlen($_POST["observacao_pedido"]) == 0) {
		$msg_erro .= "Informe uma OBSERVAÇÃO para NOTA FISCAL<br>";
	}
	else {
		$observacao_pedido = $_POST["observacao_pedido"];
	}

	if (isset($_POST["setor"]) && strlen($_POST["setor"]) == 0) {
		$msg_erro .= "Selecione o SETOR RESPONSÁVEL<br>";
	}
	else {
		$setor = $_POST["setor"];
	}

	if (isset($_POST["fabrica_distribuidor"]) && strlen($_POST["fabrica_distribuidor"]) == 0) {
		$msg_erro .= "Selecione EFETUAR TROCA POR: Fábrica ou Distribuidor<br>";
	}
	else {
		$fabrica_distribuidor = $_POST["fabrica_distribuidor"];
	}

	if (isset($_POST["envio_consumidor"]) && strlen($_POST["envio_consumidor"]) == 0) {
		$msg_erro .= "Selecione o DESTINO do produto<br>";
	}
	else {
		$envio_consumidor = $_POST["envio_consumidor"];
	}

	if (isset($_POST["modalidade_transporte"]) && strlen($_POST["modalidade_transporte"]) == 0) {
		$msg_erro .= "Selecione a MODALIDADE DO TRANSPORTE<br>";
	}
	else {
		$modalidade_transporte = $_POST["modalidade_transporte"];
	}

	if($login_fabrica == 51 or $login_fabrica == 81){
		if(strlen($_POST["fabrica_distribuidor"]) == 0) {
			$msg_erro .= "Atender via Distribuidor ou Fabricante?";
		}else{
			$fabrica_distribuidor = $_POST["fabrica_distribuidor"];
			if($fabrica_distribuidor == 'distribuidor'){
				$fabrica_distribuidor = '4311';
			}else{
				$fabrica_distribuidor = 'null';
			}
		}
	}else{
		$fabrica_distribuidor = 'null';
	}

//		echo "Fab.Distri: $fabrica_distribuidor<br><br>";
//		HD 79774 - Paulo César 10/03/2009 sempre gera pedido para fabrica 3
//		HD 83652 - IGOR - Retirar regra de gerar pedido sempre para Britania
	if($login_fabrica==3){
		if( strlen($_POST["gerar_pedido"])         == 0 ) $gerar_pedido = "'f'";
		else                                              $gerar_pedido = "'t'";
		if($_POST["envio_consumidor"]=='t')               $envio_consumidor = " 't' ";
		else                                              $envio_consumidor = " 'f' ";
	}else{
		if( strlen($_POST["gerar_pedido"])         == 0 ) $gerar_pedido = "'f'";
		else                                              $gerar_pedido = "'t'";
		if($_POST["envio_consumidor"]=='t')               $envio_consumidor = " 't' ";
		else                                              $envio_consumidor = " 'f' ";
	}


}

//Status da troca - Mallory
$troca_com_nota  = $_POST['troca_com_nota'];
$justificativanf = $_POST['justificativanf'];

if($troca_com_nota == 'sem_nota_sem_troca' and $login_fabrica==72 and strlen($msg_erro)==0 ){
	//Grava o status 154 -  Troca pendente
	$xstatus_os       = 154;
	$xjustificativanf = 'Troca pendente';

	if(strlen($os)>0){
		$sqlOS = "SELECT sua_os, posto FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
		$resOS = pg_exec($con, $sqlOS);

		if(pg_numrows($resOS)>0){
			$sua_os = pg_result($resOS,0,sua_os);
			$posto  = pg_result($resOS,0,posto);
		}
	}

	// Comunicado avisando o posto que a troca está pendente
	$sqlC = "INSERT INTO tbl_comunicado (
				descricao              ,
				mensagem               ,
				tipo                   ,
				fabrica                ,
				obrigatorio_os_produto ,
				obrigatorio_site       ,
				posto                  ,
				ativo
			) VALUES (
				'OS com troca pendente',
				'A OS $sua_os está pendente para troca, para regularizá-la anexe uma nota fiscal <a href=os_press.php?os=$os>[Anexar Nota]</a>',
				'Troca pendente',
				$login_fabrica,
				'f' ,
				't',
				$posto,
				't'
			);";
	//echo nl2br($sqlC);
	$resC = pg_exec($con, $sqlC);

	if(strlen($msg_erro)==0){
	$sqlStatus = "INSERT INTO tbl_os_status (
				 os            ,
				 status_os     ,
				 data          ,
				 admin         ,
				 fabrica_status,
				 observacao
				 ) VALUES (
				 $os              ,
				 $xstatus_os      ,
				 current_timestamp,
				 $login_admin     ,
				 $login_fabrica   ,
				 '$xjustificativanf'
				 );";
	//echo nl2br($sqlStatus);
	$resStatus = pg_exec($con, $sqlStatus);
	}

	if(strlen($msg_erro)==0){
		$sucesso = "Foi enviado um comunicado ao posto informando que a troca está pendente";
	}
}
//TROCA PENDENTE FIM


if ($btn_troca == "trocar" && strlen($msg_erro) == 0) {
	$msg_erro = "";

	//HD 341693 INICIO
	$troca_garantia_produto  = $_POST["troca_garantia_produto"];
	$os                      = $_POST["os"];
	if ($login_fabrica == 51) {
		$coleta_postagem         = $_POST["coleta_postagem"];
		$data_postagem           = $_POST["data_postagem"];
		$xdata_postagem			 = preg_replace('/^(\d{2}).(\d{2}).(\d{4})$/', '$3-$2-$1', $data_postagem);
		$xdata_postagem			 = "'".$xdata_postagem."'";
	}else{
		$coleta_postagem = 'null';
		$xdata_postagem   = 'null';
	}
	$observacao_pedido       = $_POST["observacao_pedido"];
	$qtde_itens              = $_POST["qtde_itens"];
	$troca_garantia_mao_obra = $_POST["troca_garantia_mao_obra"];
	$troca_garantia_mao_obra = str_replace(",",".",$troca_garantia_mao_obra);
	$troca_via_distribuidor  = $_POST['troca_via_distribuidor'];

	if (strlen($troca_via_distribuidor) == 0) $troca_via_distribuidor = "f";

	$sql = "SELECT produto, sua_os, posto FROM tbl_os WHERE os = $os;";
	$res = @pg_query($con,$sql); echo $sql;
	$msg_erro .= pg_errormessage($con);

	$produto = pg_fetch_result($res, 0, 'produto');
	$sua_os  = pg_fetch_result($res, 0, 'sua_os');
	$posto   = pg_fetch_result($res, 0, 'posto');

	if ($troca_garantia_produto != "-1" && $troca_garantia_produto != "-2") {

		$sql = "BEGIN TRANSACTION";
		$res = pg_query($con,$sql); echo $sql;

		$sql = "SELECT *
				  FROM tbl_produto
				  JOIN tbl_familia USING(familia)
				 WHERE produto = '$troca_garantia_produto'
				   AND fabrica = $login_fabrica;";

		$resProd   = @pg_query($con,$sql); echo $sql;
		$msg_erro .= pg_errormessage($con);

		if (@pg_num_rows($resProd) == 0) {
			$msg_erro .= "Produto informado não encontrado<br />";
		} else {
			$troca_produto    = pg_fetch_result($resProd, 0, 'produto');
			$troca_ipi        = pg_fetch_result($resProd, 0, 'ipi');
			$troca_referencia = pg_fetch_result($resProd, 0, 'referencia');
			$troca_descricao  = pg_fetch_result($resProd, 0, 'descricao');
			$troca_familia    = pg_fetch_result($resProd, 0, 'familia');
			$troca_linha      = pg_fetch_result($resProd, 0, 'linha');

			$troca_descricao = substr($troca_descricao,0,50);
		}

		if (strlen($msg_erro) == 0) {

			$sql = "SELECT *
					  FROM tbl_peca
					 WHERE referencia = '$troca_referencia'
					   AND fabrica    = $login_fabrica";
			if($login_fabrica == 59){
				$sql .="  AND produto_acabado IS TRUE";
			}
			$res = pg_query($con, $sql);
			$msg_erro .= pg_errormessage($con);

			if (pg_num_rows($res) == 0) {

				if (strlen($troca_ipi) == 0) $troca_ipi = 10;

				$sql = "SELECT peca
						  FROM tbl_peca
						 WHERE fabrica    = $login_fabrica
						   AND referencia = '$troca_referencia'
						 LIMIT 1;";

				$res = pg_query($con,$sql); echo $sql;
				$msg_erro .= pg_errormessage($con);

				if (pg_num_rows($res) > 0) {

					$peca = pg_fetch_result($res, 0, 0);

				} else {

					$sql = "INSERT INTO tbl_peca (
								fabrica,
								referencia,
								descricao,
								ipi,
								origem,
								produto_acabado
							) VALUES (
								$login_fabrica,
								'$troca_referencia',
								'$troca_descricao',
								$troca_ipi,
								'NAC',
								't'
							)";

					$res = pg_query($con,$sql); echo $sql;
					$msg_erro .= pg_errormessage($con);

					$sql = "SELECT CURRVAL ('seq_peca')";
					$res = pg_query($con,$sql); echo $sql;
					$msg_erro .= pg_errormessage($con);
					$peca = pg_fetch_result($res,0,0);

				}

				$sql = "INSERT INTO tbl_lista_basica (
							fabrica,
							produto,
							peca,
							qtde
						) VALUES (
							$login_fabrica,
							$produto,
							$peca,
							1
						);";

				$res = pg_query($con,$sql); echo $sql;
				$msg_erro .= pg_errormessage($con);

			} else {

				$peca = pg_fetch_result($res, 0, 'peca');

			}

		}

		if (strlen($msg_erro) > 0) {
			$res = pg_query($con,"ROLLBACK;");
			$msg_erro .= pg_errormessage($con);
		} else {
			$res = pg_query($con,"COMMIT;");
			$msg_erro .= pg_errormessage($con);
		}

		if (!in_array($login_fabrica, $vet_sem_preco)) {//HD 361213

			if ($login_fabrica == 14) {

				$sql_peca = "SELECT tbl_tabela_item.preco
							   FROM tbl_tabela_item
							   JOIN tbl_tabela      ON tbl_tabela_item.tabela = tbl_tabela.tabela
							   JOIN tbl_posto_linha ON tbl_tabela.tabela      = tbl_posto_linha.tabela
							  WHERE tbl_posto_linha.posto   = $posto
								AND tbl_tabela_item.peca    = $peca
								AND tbl_posto_linha.familia = $troca_familia";

			} else {

				$sql_peca = "SELECT tbl_tabela_item.preco
							   FROM tbl_tabela_item
							   JOIN tbl_tabela      ON tbl_tabela_item.tabela = tbl_tabela.tabela
							   JOIN tbl_posto_linha ON tbl_tabela.tabela      = tbl_posto_linha.tabela
							  WHERE tbl_posto_linha.posto = $posto
								AND tbl_tabela_item.peca  = $peca
								AND tbl_posto_linha.linha = $troca_linha";

			}

			$res = pg_query($con,$sql_peca);

			if (pg_num_rows($res) == 0) {
				$sql_peca2 = "SELECT tbl_tabela_item.preco
						   FROM tbl_tabela_item
						   JOIN tbl_tabela      ON tbl_tabela_item.tabela = tbl_tabela.tabela
						   WHERE tbl_tabela_item.peca  = $peca
						   AND   tbl_tabela.tabela_garantia
						   AND   tbl_tabela.fabrica = $login_fabrica";
				$res2 = pg_query($con,$sql_peca2);
				if (pg_num_rows($res2) == 0) {
					$msg_erro = "O produto $troca_referencia não tem preço na tabela de preço. Cadastre o preço para poder para dar continuidade na troca.";
				}
			}

		}

	}//HD 341693 FIM

	if ($login_fabrica != 51 && $login_fabrica != 81 && $login_fabrica != 6) {
		if (strlen($_POST['situacao_atendimento']) == 0) $msg_erro = '<br />Informe a Situação do Atendimento';
		else                                             $situacao_atendimento = $_POST['situacao_atendimento'];
	} else {
		$situacao_atendimento = 'null';
	}

	$sql = "BEGIN TRANSACTION";
	$res = pg_query($con,$sql); echo $sql;

	#Verifica se a OS tem nota e grava o status na OS - Mallory
	if ($login_fabrica == 72) {
		if ($temImg = temNF($os, 'bool')) {
			//Se tiver nota deixa gravar normal e grava o status 152 - Troca com nota
			$xstatus_os       = 152;
			$xjustificativanf = 'Troca com nota';
		}else if($troca_com_nota == 'sem_nota_com_troca'){
			//Grava o status 153 - Trocado sem nota
			$xstatus_os       = 153;
			$xjustificativanf = $justificativanf;

			if(strlen($justificativanf)==0){
				$msg_erro = "Informe a justificativa para troca de OS sem nota fiscal";
			}
		}

		if(strlen($msg_erro)==0){
			$sqlStatus = "INSERT INTO tbl_os_status (
						 os            ,
						 status_os     ,
						 data          ,
						 admin         ,
						 fabrica_status,
						 observacao
						 ) VALUES (
						 $os              ,
						 $xstatus_os      ,
						 current_timestamp,
						 $login_admin     ,
						 $login_fabrica   ,
						 '$xjustificativanf'
						 );";
			//echo nl2br($sqlStatus);
			$resStatus = pg_exec($con, $sqlStatus);
		}


	}
	//Status troca Mallory Fim

	if ($login_fabrica == 81 ) {
		$sqlressarcimento = "SELECT hd_chamado,ressarcimento from tbl_hd_chamado_troca join tbl_hd_chamado_extra using(hd_chamado) where os = $os";
		$resressarcimento = pg_exec($con,$sqlressarcimento);

		if (pg_num_rows($resressarcimento)>0) {
			$hd_chamado_troca    = pg_result($resressarcimento,0,0);
			$ressarcimento_troca = pg_result($resressarcimento,0,1);
			if ($ressarcimento_troca == 't' and $troca_garantia_produto <> '-1' ) {
				$msg_erro .= "Foi definido no callcenter que esta Ordem de Serviço é um ressarcimento, por favor escolha a opção ressarcimento<br>";
			} else {

			}
		}
	}

	#HD 51899
	$sql = "SELECT credenciamento
			FROM  tbl_posto_fabrica
			JOIN  tbl_os ON tbl_os.posto = tbl_posto_fabrica.posto
			WHERE tbl_os.fabrica            = $login_fabrica
			AND   tbl_os.os                 = $os
			AND   tbl_posto_fabrica.fabrica = $login_fabrica
			AND   tbl_posto_fabrica.credenciamento = 'DESCREDENCIADO';";
	$res = pg_query ($con,$sql); echo $sql;
	if(pg_num_rows($res)>0){
		$msg_erro .= "Este posto está DESCREDENCIADO. Não é possível efetuar a troca do produto.<br>";
	}

	$sql = " SELECT os FROM tbl_os WHERE os = $os and fabrica = $login_fabrica and data_fechamento IS NOT NULL and finalizada IS NOT NULL ";
	$res = pg_query($con,$sql); echo $sql;
	if(pg_num_rows($res) > 0){
		$os_fechada = pg_fetch_result($res,0,0);
	}
	//hd17603
	if ($login_fabrica != 35) {
		$sql = "UPDATE tbl_os SET data_fechamento = NULL,finalizada=null WHERE os = $os AND fabrica = $login_fabrica ";
		$res = pg_query($con,$sql); echo $sql;
		$msg_erro .= pg_errormessage($con);
	}

	$sql = "SELECT os_troca,peca,os FROM tbl_os_troca WHERE os = $os AND pedido IS NULL ";
	$res = pg_query ($con,$sql); echo $sql;
	if(pg_num_rows($res)>0){
		$troca_efetuada =  pg_fetch_result($res,0,os_troca);
		$troca_os       =  pg_fetch_result($res,0,os);
		$troca_peca     =  pg_fetch_result($res,0,peca);

		$sql = "DELETE FROM tbl_os_troca WHERE os_troca = $troca_efetuada";
		$sql = "UPDATE tbl_os_troca SET os = 4836000 WHERE os_troca = $troca_efetuada";
		$res = pg_query ($con,$sql); echo $sql;

		// HD 13229
		if(strlen($troca_peca) > 0) {
			$sql = "UPDATE tbl_os_produto set os = 4836000 FROM tbl_os_item WHERE tbl_os_item.os_produto=tbl_os_produto.os_produto AND os=$troca_os and peca = $troca_peca";
			$res = pg_query ($con,$sql); echo $sql;
		}
	}

	if (strlen($qtde_itens)==0){
		$qtde_itens = 0;
	}

	for ($i=0; $i<$qtde_itens; $i++) {
		$os_item_check = $_POST["os_item_".$i];
		if (strlen($os_item_check)>0){
			$sql = "UPDATE tbl_os_item SET originou_troca = 't' WHERE os_item = $os_item_check ";
			$res = pg_query($con,$sql); echo $sql;
			$msg_erro .= pg_errormessage($con);
		}
	}

// adicionado por Fabio - Altera o status para liberado da Assis. Tec. da Fábrica caso tenha intervencao.
	$sql = "SELECT status_os FROM tbl_os_status WHERE os=$os AND status_os IN (62,64,65,72,73,87,88,116,117,127) ORDER BY data DESC LIMIT 1";
	$res = pg_query($con,$sql); echo $sql;
	$qtdex = pg_num_rows($res);
	if ($qtdex>0){
		$statuss=pg_fetch_result($res,0,status_os);
		if ($statuss=='62' || $statuss=='65' || $statuss=='72' || $statuss=='87' || $statuss=='116' || $statuss=='127'){

			$proximo_status = "64";

			if ( $statuss == "72"){
				$proximo_status = "73";
			}
			if ( $statuss == "87"){
				$proximo_status = "88";
			}
			if ( $statuss == "116"){
				$proximo_status = "117";
			}

			$sql = "INSERT INTO tbl_os_status
					(os,status_os,data,observacao,admin)
					VALUES ($os,$proximo_status,current_timestamp,'OS Liberada',$login_admin)";
			$res = pg_query($con,$sql); echo $sql;
			$msg_erro .= pg_errormessage($con);

		}
	}

	/**
	 *
	 * @since HD 736525
	 * Inserido Houston e alterado para switch ao invés de vários if's
	 * Francisco Ambrozio - Fri Nov  4 11:14:50 BRST 2011
	 *
	 */
	switch ($login_fabrica) {
		case 1:
			$id_servico_realizado        = 62;
			$id_servico_realizado_ajuste = 64;
			break;
		case 3:
			$id_servico_realizado        = 20;
			$id_servico_realizado_ajuste = 96;
			$id_solucao_os               = 85;
			$defeito_constatado          = 10224;
			break;
		case 11:
			//HD 340425: Para a Lenoxx, se não tiver pedido, não deixa gerar
			$id_servico_realizado        = 61;
			$id_servico_realizado_ajuste = 498;
			$id_solucao_os               = "";
			$defeito_constatado          = "";
			break;
		case 24:
			$id_servico_realizado        = 504;
			$id_servico_realizado_ajuste = 503;
			$id_solucao_os               = 701;
			$defeito_constatado          = 13308;
			break;
		case 25:
			$id_servico_realizado        = 625;
			$id_servico_realizado_ajuste = 628;
			$id_solucao_os               = 210;
			$defeito_constatado          = 10536;
			break;
		case 35:
			$id_servico_realizado        = 571;
			$id_servico_realizado_ajuste = 573;
			$id_solucao_os               = 472;
			$defeito_constatado          = 11815;
			break;
		case 45:
			$id_servico_realizado        = 638;
			$id_servico_realizado_ajuste = 639;
			$id_solucao_os               = 397;
			$defeito_constatado          = 11250;
			break;
		case 51:
			$id_servico_realizado        = 671;
			$id_servico_realizado_ajuste = 670;
			$id_solucao_os               = 491;
			$defeito_constatado          = 12068;
			break;
		case 72:
			$id_servico_realizado        = 9383;
			$id_servico_realizado_ajuste = 9380;
			$id_solucao_os               = 3047;
			$defeito_constatado          = 16123;
			break;
		case 81:
			$id_servico_realizado        = 7455;
			$id_servico_realizado_ajuste = 7454;
			$id_solucao_os               = 2920;
			$defeito_constatado          = 15529;
			break;
		case 101:
			$id_servico_realizado        = 10577;
			$id_servico_realizado_ajuste = 10576;
			break;
		case 106:
			$id_servico_realizado        = 10600;
			$id_servico_realizado_ajuste = 10601;
			break;
	}

	if (strlen($id_servico_realizado_ajuste)>0 AND strlen($id_servico_realizado)>0){
		$sql =  "UPDATE tbl_os_item
				SET servico_realizado = $id_servico_realizado_ajuste
				WHERE os_item IN (
					SELECT os_item
					FROM tbl_os
					JOIN tbl_os_produto USING(os)
					JOIN tbl_os_item USING(os_produto)
					JOIN tbl_peca USING(peca)
					WHERE tbl_os.os       = $os
					AND tbl_os.fabrica    = $login_fabrica
					AND tbl_os_item.servico_realizado = $id_servico_realizado
					AND tbl_os_item.pedido IS NULL
				)";
		/* ************* retirado TRECHO DO SQL ABAIXO - hd: 50754 - IGOR ********** */
		/*AND tbl_peca.retorna_conserto IS TRUE*/
		/* Segundo Fábio, essa condição é desnecessária, pois todas peças devem ser canceladas*/

		$res = pg_query($con,$sql); echo $sql;
		$msg_erro .= pg_errormessage($con);
	}

	if (strlen($defeito_constatado)>0 AND strlen($id_solucao_os)>0){
		$sql = "UPDATE tbl_os
				SET solucao_os         = $id_solucao_os,
					defeito_constatado = $defeito_constatado
				WHERE os       = $os
				AND fabrica    = $login_fabrica
				AND solucao_os IS NULL
				AND defeito_constatado IS NULL";
		$res = pg_query($con,$sql); echo $sql;
		$msg_erro .= pg_errormessage($con);
	}


	//colocado por Wellington 29/09/2006 - Estava limpando o campo orientaca_sac qdo executava troca
	$orientacao_sac = trim ($_POST['orient_sac']);
	$orientacao_sac = htmlentities ($orientacao_sac,ENT_QUOTES);
	$orientacao_sac = nl2br ($orientacao_sac);
	if (strlen ($orientacao_sac) == 0) {
		$orientacao_sac  = "null";
	} else {
		$orientacao_sac  =  $orientacao_sac  ;
	}

	//hd 11083 7/1/2008
	if($login_fabrica == 3){
		if (strlen(trim($orientacao_sac))>0 AND trim($orientacao_sac)!='null'){
			$orientacao_sac =  date("d/m/Y H:i")." - ".$orientacao_sac;
			$sql = "UPDATE  tbl_os_extra SET
						orientacao_sac =  CASE WHEN orientacao_sac IS NULL OR orientacao_sac = 'null' THEN '' ELSE orientacao_sac || ' \n' END || trim('$orientacao_sac')
					WHERE tbl_os_extra.os = $os;";
		}
	}else{
		if ($login_fabrica == 11) {

			$sql_obs = "SELECT orientacao_sac from tbl_os_extra where os = $os";
			$res_obs = pg_query($con,$sql_obs);
			$orientacao_sac_aux         = pg_fetch_result($res_obs,0,orientacao_sac);
			$sql_usario = "SELECT login from tbl_admin where admin = $login_admin";
			$res_usuario = pg_query($con,$sql_usario);
			$usuario         = pg_fetch_result($res_usuario,0,login);

			$data_hoje = date("d/m/Y H:i:s");
			$orientacao_sac .= "<p>Usuário: $usuario</p>";
			$orientacao_sac .= "<p>Data: $data_hoje</p>";
			$orientacao_sac .= $orientacao_sac_aux;
		}

		$sql = "UPDATE  tbl_os_extra SET orientacao_sac = trim('$orientacao_sac')
		WHERE tbl_os_extra.os = $os;";
	}

	$res = pg_query ($con,$sql); echo $sql;
	$msg_erro .= pg_errormessage($con);

	if ( $login_fabrica == 94 ) { // HD 758032
		
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome
				FROM tbl_os
				JOIN tbl_posto USING(posto)
				WHERE os = $os";
		
		$res = pg_query($con,$sql); echo $sql;

		$posto_nome = pg_result($res,0,0);
		$posto_cnpj = pg_result($res,0,1);

		$sql = "INSERT INTO tbl_os(
					fabrica,
					posto,
					admin,
					produto,
					serie,
					nota_fiscal,
					data_digitacao,
					data_abertura,
					data_nf,
					defeito_constatado,
					defeito_reclamado_descricao,
					revenda_cnpj,
					revenda_nome,
					consumidor_nome,
					consumidor_cpf,
					consumidor_endereco,
					consumidor_cidade,
					consumidor_bairro,
					consumidor_numero,
					consumidor_complemento,
					consumidor_estado,
					consumidor_cep,
					consumidor_email,
					consumidor_fone,
					consumidor_celular,
					consumidor_fone_comercial,
					tipo_atendimento,
					acessorios,
					aparencia_produto,
					mao_de_obra
				)
				( SELECT 
					fabrica,
					114768,
					$login_admin,
					produto,
					serie,
					nota_fiscal,
					data_digitacao,
					data_abertura,
					data_nf,
					defeito_constatado,
					defeito_reclamado_descricao,
					'$posto_nome',
					'$posto_cnpj',
					consumidor_nome,
					consumidor_cpf,
					consumidor_endereco,
					consumidor_cidade,
					consumidor_bairro,
					consumidor_numero,
					consumidor_complemento,
					consumidor_estado,
					consumidor_cep,
					consumidor_email,
					consumidor_fone,
					consumidor_celular,
					consumidor_fone_comercial,
					tipo_atendimento,
					acessorios,
					aparencia_produto,
					mao_de_obra
									
				FROM tbl_os
				WHERE fabrica = $login_fabrica
				AND os = $os
				)

				RETURNING os";

		$res = pg_query($con,$sql); echo $sql;
		$os_interno = pg_result($res,0,0);

		$sql = "SELECT fn_valida_os($os_interno, $login_fabrica);

				INSERT INTO tbl_os_campo_extra(os,fabrica,os_troca_origem)
				VALUES($os_interno,$login_fabrica,$os)";

		$res = pg_query($con,$sql); echo $sql;

	}

	if ($troca_garantia_produto == "-1") {//resarcimento financeiro

		if ($login_fabrica == 81) {

			$cpf_ressarcimento = $_POST['cpf_ressarcimento'];
			$banco             = $_POST['banco'];
			$agencia           = $_POST['agencia'];
			$conta             = $_POST['conta'];
			$valor             = $_POST['valor'];
			$tipo_conta        = $_POST['tipo_conta'];
			$favorecido_conta = $_POST['favorecido_conta'];

			if (strlen($cpf_ressarcimento)==0) {
				$msg_erro .= "Para efetuar o ressarcimento digite o cpf do titular da conta<br>";
			}

			if (strlen($favorecido_conta)==0) {
				$msg_erro .= "Para efetuar o ressarcimento digite o nome do titular da conta<br>";
			}


			if (strlen($banco)==0) {
				$msg_erro .= "Para efetuar o ressarcimento escolha o banco do titular<br>";
			}

			if (strlen($agencia)==0) {
				$msg_erro .= "Para efetuar o ressarcimento digite a agencia titular<br>";
			}

			if (strlen($conta)==0) {
				$msg_erro .= "Para efetuar o ressarcimento digite a conta corrente do titular <br>";
			}

			if (strlen($valor)==0) {
				$msg_erro .= "Para efetuar o ressarcimento digite o valor<br>";
			} else {
				$valor = number_format($valor,2,'.','.');
			}

			if (strlen($msg_erro)==0) {
				$sqlressarcimento = "SELECT hd_chamado,ressarcimento from tbl_hd_chamado_troca join tbl_hd_chamado_extra using(hd_chamado) where os = $os";

				$resressarcimento = pg_exec($con,$sqlressarcimento);

				if (pg_num_rows($resressarcimento)>0) {
					$hd_chamado_troca    = pg_result($resressarcimento,0,0);
					$ressarcimento_troca = pg_result($resressarcimento,0,1);
					if ($ressarcimento_troca == 't') {
						$sqlatualiza = "UPDATE tbl_hd_chamado_extra_banco SET
												agencia = $agencia,
												contay = $conta,
												cpf_conta = $cpf_ressarcimento,
												favorecido_conta = '$favorecido_conta',
												banco = $banco,
												tipo_conta = '$tipo_conta',
												fabrica = $login_fabrica
											WHERE hd_chamado = $hd_chamado_troca";
						$resatualiza = pg_exec($con,$sqlatualiza);

						$sqlatualiza = "UPDATE tbl_hd_chamado_troca SET valor_produto = '$valor' where hd_chamado = $hd_chamado_troca";

						$resatualiza = pg_exec($con,$sqlatualiza);
					}
				} else {
					$sqlins = "INSERT INTO tbl_hd_chamado (
												admin,
												status,
												atendente,
												titulo,
												fabrica_responsavel,
												categoria,
												fabrica)
												values (
												$login_admin,
												'Aberto',
												$login_admin,
												'Atendimento Interativo',
												$login_fabrica,
												'ressarcimento',
												$login_fabrica)";
					//echo nl2br($sqlins);
					$resins = pg_exec($con,$sqlins);
					$res    = pg_query ($con,"SELECT CURRVAL ('seq_hd_chamado')");
					$hd_chamado = pg_fetch_result ($res,0,0);

					$sqlins = "INSERT INTO tbl_hd_chamado_extra (
											hd_chamado,
											os,
											produto,
											posto,
											data_nf,
											nota_fiscal,
											nome,
											endereco,
											numero,
											bairro,
											cep,
											fone)
											SELECT
											$hd_chamado,
											$os,
											produto,
											posto,
											data_nf,
											nota_fiscal,
											consumidor_nome,
											consumidor_endereco,
											consumidor_numero,
											consumidor_bairro,
											consumidor_cep,
											consumidor_fone
											FROM tbl_os
											WHERE os = $os;";

					$resins = pg_exec($con,$sqlins);

					$msg_erro .= pg_errormessage($con);

					$sqlins = "INSERT INTO tbl_hd_chamado_item (
												hd_chamado,
												comentario,
												admin,
												status_item )
												VALUES (
												$hd_chamado,
												'Foi cadastrado um ressarcimento no valor de R$ $valor e precisa ser efetivado pelo financeiro',
												$login_admin,
												'Aberto'
												)";
					$resins = pg_exec($con,$sqlins);

					$msg_erro .= pg_errormessage($con);


					$sqlins = "INSERT INTO tbl_hd_chamado_extra_banco (
											hd_chamado       ,
											banco            ,
											agencia          ,
											contay           ,
											tipo_conta       ,
											cpf_conta        ,
											favorecido_conta )
											VALUES (
											$hd_chamado,
											'$banco',
											'$agencia',
											'$conta',
											'$tipo_conta',
											'$cpf_ressarcimento',
											'$favorecido_conta')";

					$resins = pg_exec($con,$sqlins);
					$msg_erro .= pg_errormessage($con);

					$sqlins = "INSERT INTO tbl_hd_chamado_troca (
											hd_chamado,
											produto,
											valor_produto,
											ressarcimento)
											VALUES (
											$hd_chamado,
											(select produto from tbl_os where os = $os),
											$valor,
											't')";
					$resins = pg_exec($con,$sqlins);
					$msg_erro .= pg_errormessage($con);
				}

				if (strlen($msg_erro)==0) {

					$sql = "SELECT email from tbl_fabrica join tbl_admin ON tbl_fabrica.admin_ressarcimento = tbl_admin.admin where tbl_fabrica.fabrica = $login_fabrica";
					$res = pg_exec($con,$sql); echo $sql;

					if (pg_num_rows($res)>0) {

						$sqlbanco = "SELECT nome from tbl_banco where banco = $banco";
						$resbanco = pg_exec($con,$sqlbanco);
						if (pg_num_rows($resbanco)>0) {
							$nome_banco = pg_result($resbanco,0,0);
						}
						$message = "Foi cadastrado um novo ressarcimento financeiro e precisa ser baixado, acesse o sistema telecontrol e vá até a aba financeiro -><a href='http://www.telecontrol.com.br/assist/admin/relatorio_ressarcimento.php'> <b>Baixar Ressarcimento</a></b>
						<br><br>
						Admin Responsável: $login_login <br>
						<b>Os</b>: $os,<br>
						<b>Numero Atendimento</b>: $hd_chamado,<br>
						<b>Nome Favorecido</b>: $favorecido_conta<br>
						<b>Cpf/CNPJ</b>: $cpf_ressarcimento<br>
						<b>Banco</b>: $nome_banco<br>
						<b>Tipo Conta</b>: $tipo_conta<br>
						<b>Agencia</b>:$agencia<br>
						<b>Conta:</b>$conta<br>
						<b>Valor:</b>$valor";

						$assunto = "Novo Ressarcimento";
						$email = pg_result($res,0,0);

						$headers = "From: Telecontrol <telecontrol@telecontrol.com.br>\n";

						$headers .= "MIME-Version: 1.0\n";
						$headers .= "Content-type: text/html; charset=iso-8859-1\n";
						$headers .= "Cc: roberta@telecontrol.com.br,valeria@telecontrol.com.br,gabriel.rolon@telecontrol.com.br";

						if (mail("$email",$assunto,$message,$headers)) {

						}

					}
				}
			}
		}

		$sql = "SELECT data_fechamento FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica AND data_fechamento IS NOT NULL";
		$res = pg_query($con,$sql); echo $sql;
		$msg_erro .= pg_errormessage($con);

		if($login_fabrica == 3 AND pg_num_rows($res)==1 ) {
			$sql = "UPDATE tbl_os SET
					troca_garantia          = 't',
					ressarcimento           = 't',
					troca_garantia_admin    = $login_admin
					WHERE os = $os AND fabrica = $login_fabrica";
		}else{
			if($login_fabrica == 3){
				// HD 18558, 24198
				$sql = "UPDATE tbl_os SET
					troca_garantia          = 't',
					ressarcimento           = 't',
					troca_garantia_admin    = $login_admin,
					data_conserto           = CURRENT_TIMESTAMP,
					data_fechamento         = CURRENT_DATE,
					finalizada              = CURRENT_TIMESTAMP
					WHERE os = $os AND fabrica = $login_fabrica";
			}elseif($login_fabrica == 35){
				# HD 65952
				$sql = "UPDATE tbl_os SET
					troca_garantia          = 't',
					ressarcimento           = 't',
					troca_garantia_admin    = $login_admin
					WHERE os = $os AND fabrica = $login_fabrica";
			}elseif($login_fabrica == 11){
				# HD 163061
				$sql = "UPDATE tbl_os SET
					troca_garantia          = 't',
					ressarcimento           = 't',
					troca_garantia_admin    = $login_admin
					WHERE os = $os AND fabrica = $login_fabrica";
			}elseif($login_fabrica == 6){
				$sql = "UPDATE tbl_os SET
					troca_garantia = 't',
					ressarcimento = 't',
					troca_garantia_admin = $login_admin
					WHERE os = $os AND fabrica = $login_fabrica";
			 }else{
				$sql = "UPDATE tbl_os SET
					troca_garantia          = 't',
					ressarcimento           = 't',
					troca_garantia_admin    = $login_admin,
					data_fechamento         = CURRENT_DATE,
					finalizada              = CURRENT_TIMESTAMP
					WHERE os = $os AND fabrica = $login_fabrica";
			}
		}
		$res = pg_query($con,$sql); echo $sql;
		$msg_erro .= pg_errormessage($con);

		$sql = "UPDATE tbl_os_extra SET
				obs_nf                     = '$observacao_pedido'
				WHERE os = $os";
		$res = pg_query($con,$sql); echo $sql;
		$msg_erro .= pg_errormessage($con);

		//--== Novo Procedimento para Troca | Raphael Giovanini ===========

		if( strlen($_POST["causa_troca"])          == 0 ) $msg_erro .= "Escolha a causa da troca<br>";
		else                                              $causa_troca = $_POST["causa_troca"];
		if( strlen($_POST["setor"])                == 0 ) $msg_erro .= "Selecione o setor responsável<br>";
		else                                              $setor = $_POST["setor"];

		if($login_fabrica <> 51 AND $login_fabrica <> 81 && $login_fabrica <> 6){
			if( strlen($_POST["situacao_atendimento"]) == 0 ) $msg_erro .= "<br>Selecione a situação do atendimento";
			else                                              $situacao_atendimento = $_POST["situacao_atendimento"];
		} else {
			$situacao_atendimento = 'null';
		}

		//HD 211825: O código que estava aqui foi movido para fora dos IFs, na validação

		$ri = $_POST["ri"];

		if (( $setor=='Procon' OR $setor=='SAP' ) AND(strlen($ri)=="null")) $msg_erro .= "<br>Obrigatório o preenchimento do RI";

		if( strlen($_POST["ri"])                   == 0 ) $ri = "null";
		else                                              $ri = "'".$_POST["ri"]."'";

		$modalidade_transporte = $_POST["modalidade_transporte"];
		if(strlen($modalidade_transporte)==0)$xmodalidade_transporte = "''";
		if($login_fabrica==3 or $login_fabrica==81){
			if(strlen($modalidade_transporte)==0) $msg_erro .= "É obrigatório a escolha da modalidade de transporte<br>";
			else $xmodalidade_transporte = "'$modalidade_transporte'";
		}

//		echo "Fab.Distri: $fabrica_distribuidor<br><br>";
//		HD 79774 - Paulo César 10/03/2009 sempre gera pedido para fabrica 3
		if($login_fabrica==3){
			if(strlen($msg_erro) == 0 ){
				$sql = "INSERT INTO tbl_os_troca (
							setor                 ,
							situacao_atendimento  ,
							os                    ,
							admin                 ,
							observacao            ,
							causa_troca           ,
							gerar_pedido          ,
							ressarcimento         ,
							envio_consumidor      ,
							modalidade_transporte ,
							ri                    ,
							fabric                ,
							distribuidor
						)VALUES(
							'$setor'                ,
							$situacao_atendimento   ,
							$os                     ,
							$login_admin            ,
							'$observacao_pedido'    ,
							$causa_troca            ,
							't'           ,
							TRUE                    ,
							$envio_consumidor       ,
							$xmodalidade_transporte ,
							$ri                     ,
							$login_fabrica          ,
							$fabrica_distribuidor
						)";
				$res = @pg_query($con,$sql); echo $sql;
				$msg_erro .= pg_errormessage($con);
			}
		}else{
			if(strlen($msg_erro) == 0 ){

				if ($login_fabrica==6){

					$sql = "INSERT INTO tbl_os_troca (
								setor                 ,
								situacao_atendimento  ,
								os                    ,
								admin                 ,
								observacao            ,
								causa_troca           ,
								causa_troca_item      ,
								gerar_pedido          ,
								ressarcimento         ,
								envio_consumidor      ,
								modalidade_transporte ,
								ri                    ,
								fabric                ,
								distribuidor
							)VALUES(
								'$setor'                ,
								$situacao_atendimento   ,
								$os                     ,
								$login_admin            ,
								'$observacao_pedido'    ,
								$causa_troca            ,
								$causa_raiz             ,
								$gerar_pedido           ,
								TRUE                    ,
								$envio_consumidor       ,
								$xmodalidade_transporte ,
								$ri                     ,
								$login_fabrica          ,
								$fabrica_distribuidor
							)";
					$res = @pg_query($con,$sql); echo $sql;
					$msg_erro .= pg_errormessage($con);

				}else{

					$sql = "INSERT INTO tbl_os_troca (
								setor                 ,
								situacao_atendimento  ,
								os                    ,
								admin                 ,
								observacao            ,
								causa_troca           ,
								gerar_pedido          ,
								ressarcimento         ,
								envio_consumidor      ,
								modalidade_transporte ,
								ri                    ,
								fabric                ,
								distribuidor          ,
								coleta_postagem       ,
								data_postagem
							)VALUES(
								'$setor'                ,
								$situacao_atendimento   ,
								$os                     ,
								$login_admin            ,
								'$observacao_pedido'    ,
								$causa_troca            ,
								$gerar_pedido           ,
								TRUE                    ,
								$envio_consumidor       ,
								$xmodalidade_transporte ,
								$ri                     ,
								$login_fabrica          ,
								$fabrica_distribuidor   ,
								'$coleta_postagem'      ,
								$xdata_postagem
							)";
					$res = @pg_query($con,$sql); echo $sql;
					$msg_erro .= pg_errormessage($con);
				}

			}
		}

		# HD 11631
		if (($login_fabrica==3 or $login_fabrica==81) AND strlen($msg_erro)==0){
			 $sql = "INSERT INTO tbl_comunicado (
						descricao              ,
						mensagem               ,
						tipo                   ,
						fabrica                ,
						obrigatorio_os_produto ,
						obrigatorio_site       ,
						posto                  ,
						ativo
					) VALUES (
						'OS $sua_os - Ressarcimento Financeiro',
						'A Fábrica irá fazer o ressarcimento financeiro do produto da OS $sua_os',
						'OS Ressarcimento Financeiro',
						$login_fabrica,
						'f' ,
						't',
						$posto,
						't'
					);";
			$res = pg_query($con,$sql); echo $sql;
			$msg_erro .= pg_errormessage($con);
		}

		#HD 311414 - INICIO
		if (($login_fabrica==6) AND strlen($msg_erro)==0){
			 $sql = "INSERT INTO tbl_comunicado (
						descricao              ,
						mensagem               ,
						tipo                   ,
						fabrica                ,
						obrigatorio_os_produto ,
						obrigatorio_site       ,
						posto                  ,
						ativo
					) VALUES (
						'OS $sua_os - Ressarcimento de Produto',
						'A Fábrica irá Ressarcir o Produto, solicitamos para o Posto Autorizado <br />emitir Nota Fiscal com natureza de operação de Remessa para Conserto <br />e enviar preferêncialmente por e-mail ou pelo fax 11 3018-8055, <br />caso o produto esteja com acessório(s) faltante(s), <br />solicitamos para o Posto Autorizado, solicitar para o cliente os acessórios, <br />para posterior envio da Nota Fiscal.',
						'OS Ressarcimento de Produto',
						$login_fabrica,
						'f' ,
						't',
						$posto,
						't'
					);";
			$res = pg_query($con,$sql); echo $sql;
			$msg_erro .= pg_errormessage($con);
		}
		#HD 311414 _ FIM

	}
	//HD 211825: Opção de trocar através da revenda um produto
	elseif ($troca_garantia_produto == -2) {
		$sql = "
		UPDATE tbl_os SET
		troca_garantia          = 't',
		ressarcimento           = 'f',
		troca_garantia_admin    = $login_admin,
		data_fechamento         = CURRENT_DATE,
		finalizada              = CURRENT_TIMESTAMP
		WHERE os = $os AND fabrica = $login_fabrica";
		$res = pg_query($con,$sql); echo $sql;
		$msg_erro .= pg_errormessage($con);

		$sql = "
		UPDATE tbl_os_troca SET
		troca_revenda			= 't'
		WHERE os = $os AND fabric = $login_fabrica";
		$res = pg_query($con,$sql); echo $sql;
		$msg_erro .= pg_errormessage($con);

		$sql = "
		UPDATE tbl_os_extra SET
		obs_nf                     = '$observacao_pedido'
		WHERE os = $os";
		$res = pg_query($con,$sql); echo $sql;
		$msg_erro .= pg_errormessage($con);

		$sql = "
		INSERT INTO tbl_os_troca (
			setor                 ,
			situacao_atendimento  ,
			os                    ,
			admin                 ,
			observacao            ,
			causa_troca           ,
			gerar_pedido          ,
			ressarcimento         ,
			troca_revenda		  ,
			envio_consumidor      ,
			modalidade_transporte ,
			ri                    ,
			fabric                ,
			distribuidor          ,
			coleta_postagem       ,
			data_postagem
		)VALUES(
			'$setor'                ,
			$situacao_atendimento   ,
			$os                     ,
			$login_admin            ,
			'$observacao_pedido'    ,
			$causa_troca            ,
			$gerar_pedido           ,
			FALSE                   ,
			TRUE					,
			$envio_consumidor       ,
			'$modalidade_transporte',
			'$ri'                   ,
			$login_fabrica          ,
			$fabrica_distribuidor   ,
			'$coleta_postagem'      ,
			$xdata_postagem
		)";
		//echo "2<br />".nl2br($sql);exit;
		$res = @pg_query($con,$sql); echo $sql;
		$msg_erro .= pg_errormessage($con);

		$sql = "
		INSERT INTO tbl_comunicado (
			descricao              ,
			mensagem               ,
			tipo                   ,
			fabrica                ,
			obrigatorio_os_produto ,
			obrigatorio_site       ,
			posto                  ,
			ativo
		) VALUES (
			'OS $sua_os - AUTORIZAÇÃO DE DEVOLUÇÃO DE VENDA',
			'A Fábrica autorizou a fazer a devolução de venda do produto relativo à OS $sua_os. A Telecontrol coletará este produto no seu posto.',
			'AUTORIZAÇÃO DE DEVOLUÇÃO DE VENDA',
			$login_fabrica,
			'f' ,
			't',
			$posto,
			't'
		);";
		$res = pg_query($con,$sql); echo $sql;
		$msg_erro .= pg_errormessage($con);
	}
	else{
		if (strlen($msg_erro) == 0) {

			if ($login_fabrica<>6){
				$sql = "INSERT INTO tbl_os_produto (os, produto) VALUES ($os, $produto);";
				$res = pg_query($con,$sql); echo $sql;
				$msg_erro .= pg_errormessage($con);

				$sql = "SELECT CURRVAL ('seq_os_produto')";
				$res = pg_query($con,$sql); echo $sql;
				$msg_erro .= pg_errormessage($con);

				$os_produto = pg_fetch_result($res,0,0);
			}

			$sql = "
				SELECT *
				FROM   tbl_os_item
				JOIN   tbl_servico_realizado USING (servico_realizado)
				JOIN   tbl_os_produto        ON tbl_os_item.os_produto = tbl_os_produto.os_produto
				WHERE  tbl_os_produto.os = $os
				AND    tbl_servico_realizado.troca_de_peca
				AND    tbl_os_item.pedido NOTNULL " ;
			$res = pg_query($con,$sql); echo $sql;
			$msg_erro .= pg_errormessage($con);

			if ( pg_num_rows($res) > 0 ) {
				for($w = 0 ; $w < pg_num_rows($res) ; $w++ ) {
					$os_item = pg_fetch_result($res,$w,os_item);
					$qtde    = pg_fetch_result($res,$w,qtde);
					$pedido  = pg_fetch_result($res,$w,pedido);
					$pecaxx  = pg_fetch_result($res,$w,peca);

					//Verifica se está faturado, se esta embarcado devolve para estoque e cancela pedido para os itens da OS

					$sql = "SELECT DISTINCT
							tbl_pedido.pedido,
							tbl_peca.peca,
							tbl_peca.descricao,
							tbl_peca.referencia,
							tbl_pedido_item.qtde,
							tbl_pedido_item.pedido_item,
							tbl_pedido.exportado,
							tbl_pedido.posto,
							tbl_os_item.os_item
						FROM tbl_pedido
						JOIN tbl_pedido_item USING(pedido)
						JOIN tbl_peca        USING(peca) ";

				if($login_fabrica == 51 or $login_fabrica == 81){#HD52537 alterado apenas para a Gama pois não sei se as outras fábrica atualiza o pedido_item
					$sql .= " JOIN tbl_os_item     ON tbl_os_item.pedido_item   = tbl_pedido_item.pedido_item AND tbl_os_item.peca = tbl_pedido_item.peca ";
				}else{
					$sql .= " JOIN tbl_os_item     ON tbl_os_item.pedido        = tbl_pedido_item.pedido AND tbl_os_item.peca = tbl_pedido_item.peca ";
				}
					$sql .= " JOIN tbl_os_produto  ON tbl_os_produto.os_produto = tbl_os_item.os_produto
						WHERE tbl_pedido.pedido       = $pedido
						AND   tbl_peca.fabrica        = $login_fabrica
						AND   tbl_os_produto.os       = $os
						AND   tbl_pedido_item.peca    = $pecaxx";

					if($login_fabrica == 51 or $login_fabrica == 81){
						$sql .= " AND   tbl_pedido.distribuidor = 4311 ";
					}


					#HD 311414
					if($login_fabrica == 6){
						$sql .= " AND tbl_pedido.exportado IS NOT NULL ";
					}


					$res_dis = @pg_query($con,$sql); echo $sql;
					$msg_erro .= pg_errormessage($con);

					if (@pg_num_rows($res_dis) > 0) {
						for($x=0;$x<@pg_num_rows($res_dis);$x++){

							$pedido_pedido          = pg_fetch_result($res_dis,$x,pedido);
							$pedido_peca            = pg_fetch_result($res_dis,$x,peca);
							$pedido_item            = pg_fetch_result($res_dis,$x,pedido_item);
							$pedido_qtde            = pg_fetch_result($res_dis,$x,qtde);
							$pedido_peca_referencia = pg_fetch_result($res_dis,$x,referencia);
							$pedido_peca_descricao  = pg_fetch_result($res_dis,$x,descricao);
							$pedido_posto           = pg_fetch_result($res_dis,$x,posto);
							$pedido_os_item         = pg_fetch_result($res_dis,$x,os_item);

							if($pedido_posto==4311) $troca_distribuidor = "TRUE";

							$sql = "
								SELECT DISTINCT tbl_embarque.embarque
								FROM tbl_embarque
								JOIN tbl_embarque_item USING(embarque)
								WHERE pedido_item = $pedido_item
								AND   os_item     = $pedido_os_item
								AND   faturar IS NOT NULL";

							$res_x1 = @pg_query($con,$sql); echo $sql;
							$tem_faturamento = @pg_num_rows($res_x1);
							if($tem_faturamento>0) {
								$troca_distribuidor = "TRUE";
								$troca_faturado     = "TRUE";
							}

							$pecas_canceladas .= "$pedido_peca_referencia - $pedido_peca_descricao ($pedido_qtde UN.),";

							if($login_fabrica == 51 or $login_fabrica == 81){
								$distrib = 4311;
							} else {
								$distrib = 'null';
							}

							//HD 340425: Para a Lenoxx pedidos dos itens de uma OS que foi trocada são cancelados pelo integrador em Delphi.
							if ($login_fabrica == 11) {
							}
							else {
								$sql2 = "SELECT fn_pedido_cancela_garantia($distrib,$login_fabrica,$pedido_pedido,$pedido_peca,$pedido_os_item,'Troca de Produto',$login_admin); ";

								$res_x2 = pg_query($con,$sql2);

								$remetente    = "Telecontrol <telecontrol@telecontrol.com.br>";
								$destinatario = "helpdesk@telecontrol.com.br,";

								$assunto      = "Troca - Cancelamento de Pedido de Peça do Fabricante";
								$mensagem     = "$os trocada";
								$headers="Return-Path: <telecontrol@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";
								//Samuel tirou em 27/02/2009
								//mail($destinatario,$assunto,$mensagem,$headers);
								//Cancela a peça que ainda não teve o seu pedido exportado //Raphael Giovanini
								$sql = "UPDATE tbl_pedido_item SET qtde_cancelada = qtde_cancelada + $qtde
										FROM tbl_pedido
											WHERE tbl_pedido_item.pedido      = $pedido
											AND   pedido_item = $pedido_item
											AND   peca        = $pedido_peca
											AND   tbl_pedido_item.pedido = tbl_pedido.pedido
											AND   tbl_pedido.exportado IS NULL ;";
								$res3 = @pg_query($con,$sql); echo $sql;
								$msg_erro .= pg_errormessage($con);
							}
						}
					}
				}

			}
			if ($login_fabrica == 95) { // HD 684671

					$sql = "UPDATE tbl_os SET finalizada = null, data_fechamento = null WHERE os = $os;
							UPDATE tbl_os_item
							SET servico_realizado = (select servico_realizado
													 from tbl_servico_realizado
													 where ativo
													 and gera_pedido IS NOT TRUE
													 and fabrica = $login_fabrica
													 and troca_de_peca IS NOT TRUE
													 AND troca_produto IS NOT TRUE)
							FROM tbl_os, tbl_os_produto
							WHERE tbl_os.os = $os
							AND tbl_os.fabrica = $login_fabrica
							AND tbl_os.finalizada IS NULL
							AND tbl_os.os = tbl_os_produto.os
							AND tbl_os_produto.os_produto = tbl_os_item.os_produto";

					$res = pg_query($con,$sql); echo $sql;

			}
			// HD 132249
			if($login_fabrica == 35) {
				$sql="UPDATE tbl_os_item
						SET servico_realizado = 738
						WHERE os_item IN (
							SELECT os_item
							FROM tbl_os
							JOIN tbl_os_produto USING(os)
							JOIN tbl_os_item USING(os_produto)
							JOIN tbl_peca USING(peca)
							WHERE tbl_os.os       = $os
							AND tbl_os.fabrica    = $login_fabrica
						)";
				$res = pg_query($con,$sql); echo $sql;
				$msg_erro .= pg_errormessage($con);
			}
			$sql = "SELECT servico_realizado FROM tbl_servico_realizado WHERE troca_produto AND fabrica = $login_fabrica" ;
			$res = pg_query($con,$sql); echo $sql;
			$msg_erro .= pg_errormessage($con);
			if(pg_num_rows($res) > 0){
				$servico_realizado = pg_fetch_result($res,0,0);
			}
			if ($login_fabrica <> 6){
				if(strlen($servico_realizado)==0) $msg_erro .= "Não existe Serviço Realizado de Troca de Produto, favor cadastrar!<br>";
			}

			if ($login_fabrica == 24) {
				$aguardando_peca_reparo = 't';
			} else {
				$aguardando_peca_reparo = 'f';
			}

			if(strlen($msg_erro)==0){

				if ($login_fabrica <> 6){



					$sql = "INSERT INTO tbl_os_item (os_produto, peca, qtde, servico_realizado, admin,aguardando_peca_reparo) VALUES ($os_produto, $peca, 1,$servico_realizado, $login_admin,'$aguardando_peca_reparo')";

					$res = pg_query($con,$sql); echo $sql;
					$msg_erro .= pg_errormessage($con);

				}

				$sql = "SELECT data_fechamento FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica AND data_fechamento IS NOT NULL";
				$res = pg_query($con,$sql); echo $sql;
				$msg_erro .= pg_errormessage($con);


				if(($login_fabrica == 3 or $login_fabrica==45 or $login_fabrica==35 OR $login_fabrica==25) AND pg_num_rows($res)==1 ) {
					$sql = "UPDATE tbl_os SET
							troca_garantia          = 't',
							ressarcimento           = 'f',
							troca_garantia_admin    = $login_admin
							WHERE os = $os AND fabrica = $login_fabrica";
				}else{
					if($login_fabrica == 3){
						$sql = "UPDATE tbl_os SET
							troca_garantia          = 't',
							ressarcimento           = 'f',
							troca_garantia_admin    = $login_admin,
							data_conserto           = CURRENT_TIMESTAMP
							WHERE os = $os AND fabrica = $login_fabrica";
					} else if ($login_fabrica == 35 || $login_fabrica == 11 || $login_fabrica == 81 || $login_fabrica == 72 || $login_fabrica==6) {
						//HD 65952
						//HD 163061
						//HD 227564: Para a Salton a OS não deve ser fechada na troca
						//HD 324225
						$sql = "UPDATE tbl_os SET
									troca_garantia          = 't',
									ressarcimento           = 'f',
									troca_garantia_admin    = $login_admin
									WHERE os = $os AND fabrica = $login_fabrica";
					} else {
						$sql = "UPDATE tbl_os SET
							troca_garantia          = 't',
							ressarcimento           = 'f',
							troca_garantia_admin    = $login_admin,
							data_fechamento         = CURRENT_DATE
							WHERE os = $os AND fabrica = $login_fabrica";
					}
				}
				$res = @pg_query($con,$sql); echo $sql;
				$msg_erro .= pg_errormessage($con);

				$sql = "UPDATE tbl_os_extra SET
						obs_nf                     = '$observacao_pedido'
						WHERE os = $os;";

				$res = @pg_query($con,$sql); echo $sql;
				$msg_erro .= pg_errormessage($con);


				if(strlen($troca_garantia_mao_obra) > 0 ){
					$sql = "UPDATE tbl_os SET mao_de_obra = $troca_garantia_mao_obra WHERE os = $os AND fabrica = $login_fabrica";
					$res = pg_query($con,$sql); echo $sql;
					$msg_erro .= pg_errormessage($con);
				}

				$sql = "SELECT * FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica AND data_fechamento IS NULL";
				$res = @pg_query($con,$sql); echo $sql;
				$msg_erro .= pg_errormessage($con);


				//--== Novo Procedimento para Troca | Raphael Giovanini ===========

				if( strlen($_POST["causa_troca"])          == 0 ) $msg_erro .= "Escolha a causa da troca<br>";
				else                                              $causa_troca = $_POST["causa_troca"];
				if( strlen($_POST["setor"])                == 0 ) $msg_erro .= "Selecione o setor responsável<br>";
				else                                              $setor = $_POST["setor"];
				if($login_fabrica <> 51 and $login_fabrica <> 81 and $login_fabrica <> 6){
					if( strlen($_POST["situacao_atendimento"]) == 0 ) $msg_erro .= "<br>Selecione a situação do atendimento";
					else                                              $situacao_atendimento = $_POST["situacao_atendimento"];
				}else{
					$situacao_atendimento = 'null';
				}
				$gerar_pedido     = ( strlen($_POST["gerar_pedido"])         == 0 ) ? "'f'" : "'t'";
				$envio_consumidor = ($_POST["envio_consumidor"]=='t') ? " 't' " : " 'f' ";


				if($login_fabrica == 51 or $login_fabrica == 81){
					if(strlen($_POST["fabrica_distribuidor"]) == 0) {
						$msg_erro .= "<br>Atender via Distribuidor ou Fabricante?";
					}else{
						$fabrica_distribuidor = $_POST["fabrica_distribuidor"];
						$fabrica_distribuidor = ($fabrica_distribuidor == 'distribuidor') ? '4311' : 'null';
					}
				}else{
					$fabrica_distribuidor = 'null';
				}


				$ri = $_POST["ri"];

				if (( $setor=='Procon' OR $setor=='SAP' ) AND(strlen($ri)=="null"))
					$msg_erro .= "<br>Obrigatório o preenchimento do RI";

				if( strlen($_POST["ri"])                   == 0 ) $ri = "null";
				else                                              $ri = "'".$_POST["ri"]."'";

				$modalidade_transporte = $_POST["modalidade_transporte"];
				if(strlen($modalidade_transporte)==0)$xmodalidade_transporte = "''";
				if($login_fabrica==3 or $login_fabrica==81){
					if(strlen($modalidade_transporte)==0) $msg_erro .= "É obrigatório a escolha da modalidade de transporte<br>";
					else $xmodalidade_transporte = "'$modalidade_transporte'";
				}
				if($login_fabrica==3){
					if(strlen($msg_erro) == 0 ){
						$sql = "INSERT INTO tbl_os_troca (
									setor                 ,
									situacao_atendimento  ,
									os                    ,
									admin                 ,
									peca                  ,
									observacao            ,
									causa_troca           ,
									gerar_pedido          ,
									envio_consumidor      ,
									ri                    ,
									fabric                ,
									modalidade_transporte ,
									distribuidor
								)VALUES(
									'$setor'                 ,
									$situacao_atendimento    ,
									$os                      ,
									$login_admin             ,
									$peca                    ,
									'$observacao_pedido'     ,
									$causa_troca             ,
									$gerar_pedido            ,
									$envio_consumidor        ,
									$ri                      ,
									$login_fabrica           ,
									$xmodalidade_transporte  ,
									$fabrica_distribuidor
								)";
						$res = @pg_query($con,$sql); echo $sql;
						$msg_erro .= pg_errormessage($con);
					}
				}else{
					if(strlen($msg_erro) == 0 ){
						$sql = "INSERT INTO tbl_os_troca (
									setor                 ,
									situacao_atendimento  ,
									os                    ,
									admin                 ,
									peca                  ,
									observacao            ,
									causa_troca           ,
									gerar_pedido          ,
									envio_consumidor      ,
									ri                    ,
									fabric                ,
									modalidade_transporte ,
									distribuidor          ,
									coleta_postagem       ,
									data_postagem
								)VALUES(
									'$setor'                 ,
									$situacao_atendimento    ,
									$os                      ,
									$login_admin             ,
									$peca                    ,
									'$observacao_pedido'     ,
									$causa_troca             ,
									$gerar_pedido            ,
									$envio_consumidor        ,
									$ri                      ,
									$login_fabrica           ,
									$xmodalidade_transporte  ,
									$fabrica_distribuidor    ,
									'$coleta_postagem'       ,
									$xdata_postagem
								)";
						$res = @pg_query($con,$sql); echo $sql;
						$msg_erro .= pg_errormessage($con);
					}
				}
				if(strlen($msg_erro) == 0 ){
					if ($login_fabrica==25){
						$sql = "SELECT fn_pedido_troca($os,$login_fabrica)";
						$res = @pg_query($con,$sql); echo $sql;
						$msg_erro .= pg_errormessage($con);
					}
				}

				# HD 11631
				# HD 390696 - Gabriel Silveira - Adicionando Gamma Itally para a regra
				if (($login_fabrica==3 or $login_fabrica==81 or $login_fabrica == 51) AND strlen($msg_erro)==0){
					 $sql = "INSERT INTO tbl_comunicado (
								descricao              ,
								mensagem               ,
								tipo                   ,
								fabrica                ,
								obrigatorio_os_produto ,
								obrigatorio_site       ,
								posto                  ,
								ativo
							) VALUES (
								'OS $sua_os - Troca de Produto',
								'A Fábrica irá fazer a troca do produto da OS $sua_os',
								'OS Troca de Produto',
								$login_fabrica,
								'f' ,
								't',
								$posto,
								't'
							);";
					$res = pg_query($con,$sql); echo $sql;
					$msg_erro .= pg_errormessage($con);
				}


				#HD 311414 - INICIO
				if (($login_fabrica==6) AND strlen($msg_erro)==0){
					 $sql = "INSERT INTO tbl_comunicado (
								descricao              ,
								mensagem               ,
								tipo                   ,
								fabrica                ,
								obrigatorio_os_produto ,
								obrigatorio_site       ,
								posto                  ,
								ativo
							) VALUES (
								'OS $sua_os - Troca de Produto',
								'A Fábrica irá efetuar a troca do produto, solicitamos para o Posto Autorizado <br /> emitir Nota Fiscal com natureza de operação de Remessa para Conserto <br /> e enviar preferêncialmente por e-mail ou pelo fax 11 3018-8055, caso o produto <br />esteja com acessório(s) faltante(s), solicitamos para o Posto Autorizado, <br />solicitar para o cliente os acessórios, para posterior envio da Nota Fiscal.',
								'OS Troca de Produto',
								$login_fabrica,
								'f' ,
								't',
								$posto,
								't'
							);";
					$res = pg_query($con,$sql); echo $sql;
					$msg_erro .= pg_errormessage($con);
				}
				#HD 311414 _ FIM
			}
		}

		if (strlen ($msg_erro) == 0) {
            if ($login_fabrica <> 3 and $login_fabrica <> 35 AND $login_fabrica <> 11 AND $login_fabrica <> 81 AND $login_fabrica <> 72 AND $login_fabrica <> 6 AND $login_fabrica <> 95) {
				// HD 18558 - OS troca não pode ser finalizada.
				// HD 65952 - incluída a Cadence
				// HD 163061 - Incluido Lenoxx
				// HD 324225 - Incluí a Mallory
				$sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
				$res = @pg_query($con,$sql); echo $sql;
				$msg_erro .= pg_errormessage($con);
			}
		}
	}

		if($login_fabrica == 11) { # HD 175656
			if(strlen($os_fechada) > 0) {
				$sql = " UPDATE tbl_os set data_fechamento =current_date where os = $os_fechada ";
				$res = pg_query($con,$sql); echo $sql;
				$sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
				$res = @pg_query($con,$sql); echo $sql;
				$msg_erro .= pg_errormessage($con);
			}
		}
		// HD 38420
		if($login_fabrica == 3) {
			if (strlen ($msg_erro) == 0) {
				if($causa_troca == 1 or $causa_troca== 7 or $causa_troca==32) {
					$sql_ot = "SELECT count(originou_troca) as qtde_troca
								FROM  tbl_os_troca
								JOIN  tbl_os_produto USING (os)
								JOIN  tbl_os_item USING (os_produto)
								WHERE tbl_os_troca.os          = $os
								AND   tbl_os_troca.fabric      = $login_fabrica
								AND   tbl_os_troca.causa_troca = $causa_troca
								AND   tbl_os_item.originou_troca IS TRUE";
					$res_ot=@pg_query($con,$sql_ot);
					if(@pg_num_rows($res_ot) > 0){
						$qtde_troca=pg_fetch_result($res_ot,0,qtde_troca);
						if($qtde_troca == 0){
							$msg_erro .= "Para essa causa, deve informar a peça que gerou a troca.<br>";
						}
					}
				}
			}
		}
	if (strlen($msg_erro) == 0) {
		$res = pg_query($con,"COMMIT TRANSACTION");

		if($login_fabrica == 3 AND isset($troca_distribuidor)){
			$sql = "SELECT sua_os FROM tbl_os WHERE os=$os";
			$res = @pg_query($con,$sql); echo $sql;
			$pr_sua_os = @pg_fetch_result($res,0,0);

			$remetente    = "Telecontrol <telecontrol@telecontrol.com.br>";
			$destinatario = "ronaldo@telecontrol.com.br";
			$destinatario2 = "helpdesk@telecontrol.com.br";
			$assunto      = "Troca - Cancelamento de Pedido de Peça do Distribuidor";
			if($troca_faturado<>'TRUE'){
				$mensagem_distribuidor =  "At. Responsável,<br><br>O produto $pr_referencia - $pr_descricao da <a href='http://www.telecontrol.com.br/assist/os_press.php?os=$os'>OS $pr_sua_os</a> foi trocado pelo produto $troca_referencia - $troca_descricao
				<br>A(s) peça(s) $pecas_canceladas do pedido $pedido foram canceladas automaticamente pelo sistema de Troca<br>
				<br><br>Telecontrol Networking";
			}else{
				$mensagem_distribuidor =  "At. Responsável,<br><br>O produto $pr_referencia - $pr_descricao da <a href='http://www.telecontrol.com.br/assist/os_press.php?os=$os'>OS $pr_sua_os</a> foi trocado pelo produto $troca_referencia - $troca_descricao
				<br>A(s) peça(s) $pecas_canceladas do pedido $pedido  não foram canceladas automaticamente pelo sistema de Troca, porque já foram enviadas para o posto<br>
				<br><br>Telecontrol Networking";

			}

			$headers="Return-Path: <telecontrol@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";
			if(strlen($mensagem_distribuidor)>0) mail($destinatario,$assunto,$mensagem_distribuidor,$headers);
			if(strlen($mensagem_distribuidor)>0) mail($destinatario2,$assunto,$mensagem_distribuidor,$headers);
		}

		if ($login_fabrica == 24) {

			$sql_email = "SELECT email, nome from tbl_posto where posto = $posto";
			$res_email = @pg_query($con, $sql_mail);

			if (@pg_num_rows($res_email) > 0) {

				$email_posto = trim(pg_fetch_result($res_email,0,email));
				$xposto_nome = pg_fetch_result($res_email,0,nome);

				if (strlen($email_posto) > 0) {
					$remetente    = "Telecontrol <telecontrol@telecontrol.com.br>";
					$destinatario = $email_posto;
					$assunto      = "O fabricante Suggar abriu uma ordem de serviço para seu posto autorizado";
					$mensagem     = "Caro posto autorizado $xposto_nome,<BR>
					O fabricante Suggar abriu a ordem de serviço número $os para seu posto autorizado, por favor verificar.<BR><BR>
					Atenciosamente<BR> Dep. Assistência Técnica Suggar";
					$headers="Return-Path: <telecontrol@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";

					mail($destinatario,$assunto,$mensagem,$headers);

				}

			}

		}

		# HD 390696 - Gabriel Silveira - Gamma Itally Irá também enviar email de troca
		if($login_fabrica==51){

			$sql_email = "SELECT tbl_os.sua_os                 ,
								tbl_posto.nome                 ,
								tbl_posto_fabrica.contato_email
						FROM tbl_os
						JOIN tbl_posto USING(posto)
						JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
						AND tbl_posto_fabrica.fabrica = $login_fabrica
						WHERE tbl_os.os = $os";
			$res_email = pg_query($con,$sql_email);

			if(pg_num_rows($res_email)>0){

				$email_posto = trim(pg_fetch_result($res_email,0,contato_email));
				$tposto_nome = pg_fetch_result($res_email,0,nome);
				$sua_os      = pg_fetch_result($res_email,0,sua_os);
				if(strlen($email_posto)>0){
					$remetente    = "Telecontrol <helpdesk@telecontrol.com.br>";
					$destinatario = $email_posto;
					$assunto      = "Troca/reembolso OS: $sua_os - Gama Italy" ;
					$mensagem     = "MENSAGEM AUTOMÁTICA - NÃO RESPONDER A ESTE EMAIL<BR><BR>
					Prezado posto autorizado $tposto_nome,<BR><BR>
					Foi inserida uma ocorrência troca/reembolso na OS $os.<BR><BR>
					Favor verificar.<BR><BR>";
					$headers="Return-Path: <helpdesk@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";

					mail($destinatario,$assunto,$mensagem,$headers);

				}

			}
		}

		# HD 54581
		if($login_fabrica==45){
			$sql_email = "SELECT tbl_os.sua_os                 ,
								tbl_posto.nome                 ,
								tbl_posto_fabrica.contato_email
						FROM tbl_os
						JOIN tbl_posto USING(posto)
						JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
						AND tbl_posto_fabrica.fabrica = $login_fabrica
						WHERE tbl_os.os = $os";
			$res_email = pg_query($con,$sql_email);
			if(pg_num_rows($res_email)>0){
				$email_posto = trim(pg_fetch_result($res_email,0,contato_email));
				$tposto_nome = pg_fetch_result($res_email,0,nome);
				$sua_os      = pg_fetch_result($res_email,0,sua_os);
				if(strlen($email_posto)>0){
					$remetente    = "Telecontrol <helpdesk@telecontrol.com.br>";
					$destinatario = $email_posto;
					$assunto      = "Troca/reembolso OS: $sua_os - NKS";
					$mensagem     = "MENSAGEM AUTOMÁTICA - NÃO RESPONDER A ESTE EMAIL<BR><BR>
					Prezado posto autorizado $tposto_nome,<BR><BR>
					Foi inserida uma ocorrência troca/reembolso na OS $os.<BR><BR>
					Favor verificar.<BR><BR>";
					$headers="Return-Path: <helpdesk@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";

					mail($destinatario,$assunto,$mensagem,$headers);

				}
			}
		}

		if($login_fabrica == 72){
			if(strlen($msg_erro) == 0){
				$sql= "SELECT fn_atualiza_status_pedido($login_fabrica, $pedido)";
				$res = pg_exec($con,$sql); echo $sql;
			}
		}

		header("Location: $PHP_SELF?os=$os&ok=s&osacao=trocar&s=s");
		exit;
	}else{
		$res = pg_query($con,"ROLLBACK TRANSACTION");
	}
}

/*======= <PHP> FUNCOES DOS BOTOES DE ACAO =========*/

$btn_acao = strtolower ($_POST['btn_acao']);

if ($btn_acao == "continuar") {
	$msg_erro = "";

	if(in_array($login_fabrica,$fabricas_validam_campos_telecontrol) || $login_fabrica > 99){
		$msg_erro .= validaCamposOs($campos_telecontrol[$login_fabrica]['tbl_os'], $_POST,$login_fabrica);

	}


	$imprimir_os = $_POST["imprimir_os"];

	if (strlen (trim ($sua_os)) == 0 && !in_array($login_fabrica, array(101,104,105,87)) ) {
		$sua_os = 'null';
		if ($pedir_sua_os == 't') {
			$msg_erro .= " Digite o número da OS Fabricante.<br>";
		}
	}else{
		$expSua_os = explode("-",$sua_os);
		$sua_os = "'" . $sua_os . "'" ;
	}

	// explode a sua_os
	$fOsRevenda = 0;
	$sql = "SELECT sua_os
			FROM   tbl_os_revenda
			WHERE  sua_os = '$expSua_os[0]'
			AND    fabrica      = $login_fabrica";

	$res = pg_query ($con,$sql); echo $sql;

	if (pg_num_rows ($res) != 0) {
		$fOsRevenda = 1;
	}
		$data_nf =trim($_POST['data_nf']);

	if (strlen($msg_erro) == 0){
		#------------ Atualiza Dados do Consumidor ----------
		$cidade = strtoupper(trim($_POST['consumidor_cidade']));
		$estado = strtoupper(trim($_POST['consumidor_estado']));
		$nome	= trim ($_POST['consumidor_nome']) ;

		if (strtoupper(trim($_POST['consumidor_revenda'])) == 'C') {
			if (strlen($estado) == 0 AND $login_fabrica != 7) {
				$msg_erro .= " Digite o estado do consumidor. <br>";
			}else{
				$estado = ' NULL ' ;
			}

			if (strlen($cidade) == 0 AND $login_fabrica != 7) {
				$msg_erro .= " Digite a cidade do consumidor. <br>";
			}else{
				$cidade = ' NULL ' ;
			}

			if (strlen($nome) == 0 AND $login_fabrica != 7)   {
				$msg_erro .= " Digite o nome do consumidor. <br>";
			}else{
				$nome = ' NULL ' ;
			}
		}

		if ($login_fabrica == 1) {
			if (strlen(trim($_POST['fisica_juridica'])) == 0) {
				$msg_erro .= "Escolha o Tipo Consumidor.<BR> ";
			} else {
				$xfisica_juridica = "'".($_POST['fisica_juridica'])."'";
			}
		} else {
			$xfisica_juridica = "null";
		}

		$cpf			= trim($_POST['consumidor_cpf']) ;
		$rg				= trim($_POST['consumidor_rg']) ;
		$fone			= trim($_POST['consumidor_fone']) ;
		$fone_celular	= trim($_POST['consumidor_celular']) ;
		$fone_comercial	= trim($_POST['consumidor_fone_comercial']) ;
		$endereco		= trim($_POST['consumidor_endereco']) ;
		$numero         = trim($_POST['consumidor_numero']);
		$complemento    = trim($_POST['consumidor_complemento']) ;
		$bairro         = trim($_POST['consumidor_bairro']) ;
		$cep            = trim($_POST['consumidor_cep']) ;
		$deslocamento_km= trim($_POST['deslocamento_km']) ;
        $tipo_atendimento= trim($_POST['tipo_atendimento']) ;

		$deslocamento_km= str_replace(",",".",$deslocamento_km);

		if (strlen($cpf) > 0) {
			if (!validaCPF($cpf)) {
				$msg_erro = "CPF Inválido";
			} else {
	            $xcpf = (strlen($cpf) == 0) ? 'null' : "'".preg_replace('/\D/', '', $cpf)."'";
			}
		}

		$rg = (strlen($rg) == 0) ? "null" : "'$rg'";

		if ($login_fabrica == 2) {
			if (strlen($endereco) == 0) $msg_erro .= " Digite o endereço do consumidor. <br>";
		}

		

		if (strlen($complemento) == 0) $complemento = "null";
		else                           $complemento = "'" . $complemento . "'";

		if($_POST['consumidor_contrato'] == 't' ) $contrato	= 't';
		else                                      $contrato	= 'f';

		$cep = str_replace (".","",$cep);
		$cep = str_replace ("-","",$cep);
		$cep = str_replace ("/","",$cep);
		$cep = str_replace (",","",$cep);
		$cep = str_replace (" ","",$cep);
		$cep = substr ($cep,0,8);


		if (strlen($cep) == 0) $cep = "null";
		else                   $cep = "'" . $cep . "'";

		$monta_sql .= "2: $sql<br>$msg_erro<br><br>";

		if ($login_fabrica == 1 AND strlen ($cpf) == 0) {
			$cpf = 'null';
		}
	}

	$consumidor_email       = trim ($_POST['consumidor_email']) ;

	// HD 18051
	if(strlen($consumidor_email) ==0 ){		
		$consumidor_email = "";		
	}else{
		$consumidor_email = trim($_POST['consumidor_email']);
	}

	$classificacao_os = $_POST['classificacao_os'];

	if (strlen (trim ($classificacao_os)) == 0) {
		$classificacao_os = 'null';
		if ($login_fabrica == 7){
			$msg_erro .= " Classificação da OS é obrigatória. <br>";
		}
	}

	$tipo_atendimento = $_POST['tipo_atendimento'];

	if (strlen (trim ($tipo_atendimento)) == 0) {
		$tipo_atendimento = 'null';
		if ($login_fabrica == 7){
			$msg_erro .= " A natureza é obrigatória. <br>";
		}
	}

	$segmento_atuacao = $_POST['segmento_atuacao'];
	if (strlen (trim ($segmento_atuacao)) == 0) $segmento_atuacao = 'null';

	if($tipo_atendimento=='15' or $tipo_atendimento=='16'){
		if (strlen(trim($_POST['autorizacao_cortesia'])) == 0) $msg_erro .= 'Digite autorização cortesia. <br>';
		else           $autorizacao_cortesia = "'".trim($_POST['autorizacao_cortesia'])."'";
	}else{
		if (strlen(trim($_POST['autorizacao_cortesia'])) == 0) $autorizacao_cortesia = 'null';
		else           $autorizacao_cortesia = "'".trim($_POST['autorizacao_cortesia'])."'";
	}

	//--==== OS de Instalção ============================================
	$km_auditoria = "FALSE";
	$sql = "SELECT tipo_atendimento,km_google
			FROM tbl_tipo_atendimento
			WHERE tipo_atendimento = $tipo_atendimento";
	$res = pg_query($con,$sql); echo $sql;
	if(pg_num_rows($res)>0){
		$km_google = pg_fetch_result($res,0,km_google);

		if($km_google == 't'){
			$qtde_km  = str_replace (",",".",$_POST['distancia_km']); ;
			$qtde_km = number_format($qtde_km,3,'.','');
			$qtde_km2 = number_format($_POST['distancia_km_conferencia'],3,'.','') ;
			if($distancia_km_maps<>'maps' AND ($qtde_km <> $qtde_km2 AND $qtde_km > 0)){
				$km_auditoria = "TRUE";
			}else{
				//HD: 24813 - PARA
				if($login_fabrica ==50 AND $qtde_km> 50){
					$km_auditoria = "TRUE";
				}
				if($login_fabrica ==30 AND $qtde_km> 200){
					$km_auditoria = "TRUE";
				}
			}
		}else{
			if($login_fabrica <> 19) $qtde_produtos = 1;
		}
	}
	if(strlen($qtde_km)==0){
		$qtde_km = "NULL";
		$km_auditoria = "FALSE";
	}

	$posto_codigo = trim ($_POST['posto_codigo']);
	$posto_codigo = str_replace ("-","",$posto_codigo);
	$posto_codigo = str_replace (".","",$posto_codigo);
	$posto_codigo = str_replace ("/","",$posto_codigo);
	$posto_codigo = substr($posto_codigo,0,14);
	if(strlen($posto_codigo)>0){
		$res = pg_query ($con,"SELECT * FROM tbl_posto JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica WHERE tbl_posto_fabrica.codigo_posto = '$posto_codigo'");

		if (pg_num_rows($res)==0){
			$msg_erro .= "Posto Inválido. <br>";
		}else{
			$posto = @pg_fetch_result ($res,0,0);
		}

	}
	else{
		$msg_erro .= "Informe o Posto. <br>";
	}

	$data_abertura = trim($_POST['data_abertura']);
	$data_abertura = fnc_formata_data_pg($data_abertura);

	$hora_abertura = trim($_POST['hora_abertura']);
	if ($login_fabrica==7 AND strlen($hora_abertura)==0){
		$msg_erro .= " Digite a hora de abertura da OS. <br>";
	}
	if ($login_fabrica==7 AND strlen($posto) > 0){// HD 70398
		$sql = "SELECT credenciamento
			FROM  tbl_posto_fabrica
			WHERE tbl_posto_fabrica.posto = $posto
			AND   tbl_posto_fabrica.fabrica = $login_fabrica
			AND   tbl_posto_fabrica.credenciamento = 'DESCREDENCIADO';";
		$res = pg_query ($con,$sql); echo $sql;
		if(pg_num_rows($res)>0){
			$msg_erro .= "Este posto está DESCREDENCIADO. Não é possível cadastrar OS. <br>";
		}
	}
	if (strlen($msg_erro)==0){
		if (strlen($hora_abertura) > 0){
			$hora_abertura = "'".$hora_abertura."'";
		}else{
			$hora_abertura = " NULL ";
		}
	}

	$consumidor_nome   = str_replace ("'","",$_POST['consumidor_nome']);
	$consumidor_cidade = str_replace ("'","",$_POST['consumidor_cidade']);
	$consumidor_estado = $_POST['consumidor_estado'];
	$consumidor_fone   = $_POST['consumidor_fone'];
	$consumidor_celular = $_POST['consumidor_celular'];
	$consumidor_fone_comercial = $_POST['consumidor_fone_comercial'];

	$consumidor_cpf = trim($_POST['consumidor_cpf']);
	$consumidor_cpf = str_replace ("-","",$consumidor_cpf);
	$consumidor_cpf = str_replace (".","",$consumidor_cpf);
	$consumidor_cpf = str_replace ("/","",$consumidor_cpf);
	$consumidor_cpf = trim (substr ($consumidor_cpf,0,14));

	if($login_fabrica == 7 and strlen($consumidor_cpf) > 0){ // HD 46309
		$sql = "SELECT fn_valida_cnpj_cpf('$consumidor_cpf')";
		$res = @pg_query($con,$sql); echo $sql;
		$cpf_erro = pg_errormessage($con);
		if(strlen($cpf_erro) > 0){
			$msg_erro = "CPF/CNPJ do consumidor inválido <br>";
		}
	}

	if (strlen($consumidor_cpf) == 0) $xconsumidor_cpf = 'null';
	else                              $xconsumidor_cpf = "'".$consumidor_cpf."'";

	$consumidor_fone = strtoupper (trim ($_POST['consumidor_fone']));

	$revenda_cnpj = trim($_POST['revenda_cnpj']);
	$revenda_cnpj = preg_replace('/\D/', '', $revenda_cnpj);

	if($login_fabrica == 7 and strlen($revenda_cnpj) > 0){ // HD 46309
		$sql = "SELECT fn_valida_cnpj_cpf('$revenda_cnpj')";
		$res = @pg_query($con,$sql); echo $sql;
		$cnpj_erro = pg_errormessage($con);
		if(strlen($cnpj_erro) > 0){
			$msg_erro .="CNPJ da Revenda inválido <br>";
		}
	}

	// HD 17851
	if($login_fabrica ==1 and strlen($revenda_cnpj) == 0){
		$msg_erro.="Digite o cnpj da revenda<br>";
	}
	if (strlen($revenda_cnpj) == 0) $xrevenda_cnpj = 'null';
	else                            $xrevenda_cnpj = "'".$revenda_cnpj."'";

	// HD 17851
	if($login_fabrica == 1 and strlen($_POST['revenda_nome']) == 0){
		$msg_erro.="Digite o nome da revenda<br>";
	}

	$revenda_nome = str_replace ("'","",$_POST['revenda_nome']);
	$nota_fiscal  = $_POST['nota_fiscal'];

	if (strlen ($_POST['troca_faturada']) == 0) $xtroca_faturada = 'null';
	else        $xtroca_faturada = "'".trim($_POST['troca_faturada'])."'";

	$data_nf      = trim($_POST['data_nf']);
	$data_nf      = fnc_formata_data_pg($data_nf);

	$nota_fiscal_saida = $_POST['nota_fiscal_saida'];
	$data_nf_saida     = trim($_POST['data_nf_saida']);
	$data_nf_saida     = fnc_formata_data_pg($data_nf_saida);

	if ($data_nf == 'null' AND $xtroca_faturada <> 't' and $login_fabrica <> 7 and $login_fabrica <> 24) {
		$msg_erro .= " Digite a data de compra. <br />";
	} else {
		if (strlen(trim($data_nf)) <> 12 and $login_fabrica <> 7 and $login_fabrica <> 24) {
			$data_nf = "null";
			$msg_erro .= " Digite a data de compra. <br />";
		}
	}

	$produto_referencia = strtoupper (trim ($_POST['produto_referencia']));
	//BOSCH -  regra: caso ele escolho um dois tipos de atendimento abaixo o produto vai ser  sempre os designados
	if($login_fabrica ==20){
		if($tipo_atendimento==11){    //garantia de pe?as
			$produto_referencia='0000002';
		}
		if($tipo_atendimento==12){    //garantia de acess?rios
			$produto_referencia='0000001';
		}
	}
	$produto_referencia = str_replace ("-","",$produto_referencia);
	$produto_referencia = str_replace (" ","",$produto_referencia);
	$produto_referencia = str_replace ("/","",$produto_referencia);
	$produto_referencia = str_replace (".","",$produto_referencia);

	$produto_serie           = strtoupper (trim ($_POST['produto_serie']));

	if($login_fabrica == 94 AND strlen($produto_referencia) > 0 AND strlen($produto_serie) > 0){
		$sql = "SELECT serie
		            FROM tbl_numero_serie
				   WHERE serie = '$produto_serie'
				   AND referencia_produto = '$produto_referencia'
				   AND fabrica = $login_fabrica";
		$res = pg_query($con,$sql); echo $sql;

		if(pg_numrows($res) == 0){
			 $msg_erro .= 'Número de série inválido!<br />';
		}
	}

	$admin_paga_mao_de_obra = $_POST['admin_paga_mao_de_obra'];
	if ($admin_paga_mao_de_obra == 'admin_paga_mao_de_obra')
		$admin_paga_mao_de_obra = 't';
	else
		$admin_paga_mao_de_obra = 'f';
	$qtde_produtos           = strtoupper (trim ($_POST['qtde_produtos']));

	$aparencia_produto = strtoupper (trim ($_POST['aparencia_produto']));
	$acessorios        = strtoupper (trim ($_POST['acessorios']));

	$consumidor_revenda= str_replace ("'","",$_POST['consumidor_revenda']);

	$orientacao_sac    = trim ($_POST['orientacao_sac']);
	$orientacao_sac    = htmlentities ($orientacao_sac,ENT_QUOTES);
	$orientacao_sac    = nl2br ($orientacao_sac);

#	if (strlen ($consumidor_cpf) <> 0 and strlen ($consumidor_cpf) <> 11 and strlen ($consumidor_cpf) <> 14) $msg_erro .= "Tamanho do CPF/CNPJ do cliente inv?lido.";

#	if ($login_fabrica == 1 AND strlen($consumidor_cpf) == 0) $msg_erro .= " Tamanho do CPF/CNPJ do cliente inv?lido.";

	if(strlen($posto)>0){
		$sql = "select pais from tbl_posto where posto =$posto";
		$res = pg_query ($con,$sql) ;
		$pais = pg_fetch_result ($res, 0, pais);
	}

	/*IGOR HD 2935 - Quando pais for diferente de Brasil não tem CNPJ (bosch)*/
	if($pais == "BR"){
		if (strlen ($revenda_cnpj)   <> 0 and strlen ($revenda_cnpj)   <> 14) $msg_erro .= "Tamanho do CNPJ da revenda inválido. <br>";
	}else{
		if (strlen ($revenda_cnpj)   == 0 )
			$msg_erro .= "Tamanho do CNPJ da revenda inválido. <br>";
	}

	if (strlen ($produto_referencia) == 0) {
		if ($login_fabrica <> 7){
			$msg_erro .= " Digite o produto. <br>";
		}
	}

	$xquem_abriu_chamado = trim($_POST['quem_abriu_chamado']);

	if (strlen($xquem_abriu_chamado) == 0) {
		$xquem_abriu_chamado = 'null';
		if ($login_fabrica == 7){
			$msg_erro .= "Digite quem abriu o Chamado. <br>";
		}
	}else{
		$xquem_abriu_chamado = "'".$xquem_abriu_chamado."'";
	}

	$xobs = trim($_POST['obs']);
	if (strlen($xobs) == 0) $xobs = 'null';
	else                    $xobs = "'".$xobs."'";

	// Campos da Black & Decker
	if ($login_fabrica == 1) {
		if (strlen(trim($_POST['codigo_fabricacao'])) == 0) $codigo_fabricacao = 'null';
		else $codigo_fabricacao = "'".trim($_POST['codigo_fabricacao'])."'";

		if (strlen($_POST['satisfacao']) == 0) $satisfacao = "f";
		else                                   $satisfacao = "t";

		if (strlen($_POST['laudo_tecnico']) == 0) $laudo_tecnico = 'null';
		else                                      $laudo_tecnico = "'".trim($_POST['laudo_tecnico'])."'";

		if ($satisfacao == 't' AND strlen($_POST['laudo_tecnico']) == 0) {
			$msg_erro .= " Digite o Laudo Técnico. <br>";
		}
	}

	//HD 33095 13/08/2008
	if (strlen(trim($_POST['capacidade'])) == 0) $xproduto_capacidade = 'null';
	else                                         $xproduto_capacidade = "'".trim($_POST['capacidade'])."'";

	if (strlen(trim($_POST['divisao'])) == 0) $xdivisao = 'null';
	else                                      $xdivisao = "'".trim($_POST['divisao'])."'";

	$xproduto_capacidade = str_replace(",",".",$xproduto_capacidade);
	$xdivisao            = str_replace(",",".",$xdivisao);
	$defeito_reclamado = trim ($_POST['defeito_reclamado']);

	if (strlen ($defeito_reclamado) == 0) {
		$defeito_reclamado = "null";
	}

	if (($login_fabrica ==35 or $login_fabrica==28) AND $defeito_reclamado == '0') {
		$msg_erro .= "Selecione o defeito reclamado.<BR>";
	}

	if (strlen(trim($_POST['defeito_reclamado_descricao'])) == 0){
		$xdefeito_reclamado_descricao = 'null';
	}else{
		$xdefeito_reclamado_descricao = "'".trim($_POST['defeito_reclamado_descricao'])."'";
	}

	if (strlen($os)>0){

		$sql = "select
				tbl_defeito_reclamado.defeito_reclamado
				from tbl_os
				join tbl_defeito_reclamado on(tbl_os.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado and tbl_os.fabrica = tbl_defeito_reclamado.fabrica)
				where tbl_os.os=$os
				and tbl_os.fabrica=$login_fabrica
				and tbl_defeito_reclamado.ativo is true;";
		$res = pg_query($con,$sql); echo $sql;

		if (pg_num_rows($res)>0 && $login_fabrica == 86){
			$defeito_reclamado = pg_result($res,0,0);

		}

	}
	$defeito_reclamado_descricao = trim($_POST['defeito_reclamado_descricao']);

	// HD 413350 - Adicionar LeaderShip
	if (in_array($login_fabrica, array(28, 35, 50, 95,98,101,104,105)) and $pedir_defeito_reclamado_descricao == 't' and
		($defeito_reclamado_descricao == 'null' or strlen($defeito_reclamado_descricao) == 0)) {
		$msg_erro .= "Digite o defeito reclamado.<BR>";
	}

	//HD 172561 - Usar tbl_os.defeito_reclamado e tbl_os.defeito_reclamado_descricao para Fabrica 3 e Linha 528
	if ($login_fabrica == 3){
		$sql = "
		SELECT
		tbl_linha.linha

		FROM
		tbl_produto
		JOIN tbl_linha ON tbl_produto.linha=tbl_linha.linha

		WHERE
		tbl_linha.linha = 528
		AND tbl_produto.referencia='" . $_POST["produto_referencia"] . "'";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res)) {
			if ($xdefeito_reclamado_descricao == 'null' OR strlen($xdefeito_reclamado_descricao) == 0) {
				$msg_erro .= "Digite o defeito reclamado adicional.<BR>";
			}
		}
	}

	if (strlen ($data_abertura) <> 12) {
		$msg_erro .= " Digite a data de abertura da OS. <br>";
	}else{
		$cdata_abertura = str_replace("'","",$data_abertura);
	}

    //valida tipo de atendimento
    if($tipo_atendimento == 0  && in_array($login_fabrica, array(87))){
       $msg_erro .= " Selecione um tipo de atendimento<br>";
    }

	if (strlen ($qtde_produtos) == 0) $qtde_produtos = "1";


	$os_posto            = trim($_POST ['os_posto']);
	if (strlen ($os_posto) == 0) $os_posto = null;
	if($login_fabrica == 30){
		if (strlen($os_posto) > 0 AND strlen($os_posto) < 8) {
			$msg_erro .= 'O Número da "OS Posto" dever ter no mínimo 8 dígitos <br>';
		}
	}

    $horas_trabalhadas = $_POST['horas_trabalhadas'];
    //valida tipo de atendimento
    if(empty($horas_trabalhadas)  && in_array($login_fabrica, array(87))){
       $msg_erro .= " Digite as horas trabalhadas<br>";
    }

	// se ? uma OS de revenda
	if ($fOsRevenda == 1){

		if (strlen ($nota_fiscal) == 0){
			$nota_fiscal = "null";
			//$msg_erro = "Entre com o n?mero da Nota Fiscal";
		}else{
			$nota_fiscal = "'" . $nota_fiscal . "'" ;
			$nota_fiscal_saida = "'" . $nota_fiscal_saida . "'" ;
		}

		if (strlen ($aparencia_produto) == 0)
			$aparencia_produto  = "null";
		else
			$aparencia_produto  = "'" . $aparencia_produto . "'" ;

		if (strlen ($acessorios) == 0)
			$acessorios = "null";
		else
			$acessorios = "'" . $acessorios . "'" ;

		if (strlen($consumidor_revenda) == 0)
			$msg_erro .= " Selecione consumidor ou revenda. <br>";
		else
			$xconsumidor_revenda = "'".$consumidor_revenda."'";

		if (strlen ($orientacao_sac) == 0)
			$orientacao_sac  = "null";
		else
			$orientacao_sac  = $orientacao_sac ;

	}else{

		if (strlen ($nota_fiscal) == 0 and $login_fabrica<>7 and $login_fabrica<>24){
			//$nota_fiscal = "null";
			$msg_erro .= "Informe o Número da Nota Fiscal <br>";
		}
		else
			$nota_fiscal = "'" . $nota_fiscal . "'" ;


		if (strlen ($nota_fiscal) == 0){
			$nota_fiscal_saida = "'" . $nota_fiscal_saida . "'" ;
		}else{
			$nota_fiscal_saida = "'" . $nota_fiscal_saida . "'" ;
		}
		if (strlen ($aparencia_produto) == 0)
			$aparencia_produto  = "null";
		else
			$aparencia_produto  = "'" . $aparencia_produto . "'" ;

		if (strlen ($acessorios) == 0)
			$acessorios = "null";
		else
			$acessorios = "'" . $acessorios . "'" ;

		if (strlen($consumidor_revenda) == 0)
			$msg_erro .= " Selecione consumidor ou revenda. <br>";
		else
			$xconsumidor_revenda = "'".$consumidor_revenda."'";

		if (strlen ($orientacao_sac) == 0)
			$orientacao_sac  = "null";
		else
			$orientacao_sac  =  $orientacao_sac  ;

	}

	$res = pg_query ($con,"BEGIN TRANSACTION");

	$produto = 0;

	#HD 32668
	if (strlen($produto_referencia) > 0 OR $login_fabrica <> 7 ){
		$sql = "SELECT tbl_produto.produto
				FROM   tbl_produto
				JOIN   tbl_linha USING (linha)
				WHERE  UPPER (tbl_produto.referencia_pesquisa) = UPPER ('$produto_referencia')
				AND    tbl_linha.fabrica      = $login_fabrica ";

			// IGOR HD 3576 - Se a OS estiver cadastrada, permitir o pedido de peça.
			if($login_fabrica == 3 and strlen($os)> 0) {
				//echo "teste";
				//$msg_erro = "";
			}else{
				$sql .= "AND    tbl_produto.ativo IS TRUE";
			}
		$res = pg_query ($con,$sql); echo $sql;

		if (pg_num_rows ($res) == 0) {
			if($login_fabrica == 3 and strlen($os)> 0) {
				//$msg_erro = "";
			}else{
				if (strlen($produto_referencia)>0){
					$msg_erro .= "Produto $produto_referencia não cadastrado <br>";
				}else{
					$produto = " null ";
				}
			}
		}else{
			$produto = @pg_fetch_result ($res,0,0);
		}
	}else{
		$produto = " null ";
	}


	if ($xtroca_faturada <> "'t'") { // verifica troca faturada para a Black

		// se não é uma OS de revenda, entra
		if ($fOsRevenda == 0){
			$sql = "SELECT garantia FROM tbl_produto WHERE tbl_produto.produto = $produto";

			$res = @pg_query ($con,$sql); echo $sql;

			if (@pg_num_rows ($res) == 0) {
				//HD 3576 - Validar o produto somente na abertura da OS
				if($login_fabrica == 3 and strlen($os)> 0) {
					//$msg_erro = "";
				}else{
					if ($login_fabrica <> 7){
						$msg_erro .= "Produto $produto_referencia sem garantia <br>";
					}
				}
			}else{
				$garantia = trim(@pg_fetch_result($res,0,garantia));
			}

			if (strlen($garantia)>0){
				$sql = "SELECT ($data_nf::date + (($garantia || ' months')::interval))::date;";
				$res = @pg_query ($con,$sql); echo $sql;

				if (@pg_num_rows ($res) > 0) {
					$data_final_garantia = trim(pg_fetch_result($res,0,0));
				}
				// HD 23616
				if ($login_fabrica <> 3 and $login_fabrica <> 7 AND $login_fabrica <> 11 AND $login_fabrica <> 24  and $login_fabrica <> 6 and $login_fabrica <> 35  AND $login_fabrica <> 30 AND $login_fabrica <> 51) {
					if(strlen($$data_nf)>0){
						if ($data_final_garantia < $cdata_abertura) {
							$msg_erro .= "[ $data_nf ] - [ $data_final_garantia ] = [ $cdata_abertura ] Produto $produto_referencia fora da garantia, vencida em ". substr($data_final_garantia,8,2) ."/". substr($data_final_garantia,5,2) ."/". substr($data_final_garantia,0,4);
						}
					}
				}
			}
		}
	}

	if ($login_fabrica == 1) {
		$sql =	"SELECT tbl_familia.familia, tbl_familia.descricao
				FROM tbl_produto
				JOIN tbl_familia USING (familia)
				WHERE tbl_familia.fabrica = $login_fabrica
				AND   tbl_familia.familia = 347
				AND   tbl_produto.linha   = 198
				AND   tbl_produto.produto = $produto;";
		$res = @pg_query($con,$sql); echo $sql;
		if (pg_num_rows($res) > 0) {
			$xtipo_os_compressor = "10";
		}else{
			$xtipo_os_compressor = 'null';
		}
	}elseif($login_fabrica ==19){
		if (strlen($_POST['tipo_os']) > 0) {
			$xtipo_os_compressor = $_POST['tipo_os'];
		}else{
			$xtipo_os_compressor = 'null';
		}
	}else{
		$xtipo_os_compressor = 'null';
	}
	$os_reincidente = "'f'";

	##### Verifica??o se o n? de s?rie ? reincidente para a Tectoy #####
	/*
	HD 39676 10/9/2008 ( Gustavo )
	- Michelle solicitou que fossem tiradas as validações para o admin da tectoy.
	Obs HD: ... lembrando que no modo ADMIN não deve ter restrições ...
	*/
	if ($login_fabrica == 6 and 1==2) {
		$sqlX = "SELECT to_char (current_date - INTERVAL '30 days', 'YYYY-MM-DD')";
		$resX = pg_query($con,$sqlX);
		$data_inicial = pg_fetch_result($resX,0,0);

		$sqlX = "SELECT to_char (current_date, 'YYYY-MM-DD')";
		$resX = pg_query($con,$sqlX);
		$data_final = pg_fetch_result($resX,0,0);

		if (strlen($produto_serie) > 0) {
			$sql = "SELECT  tbl_os.os             ,
					tbl_os.sua_os         ,
					tbl_os.data_digitacao ,
					tbl_os_extra.extrato
					FROM    tbl_os
					JOIN    tbl_os_extra ON tbl_os_extra.os = tbl_os.os
					WHERE   tbl_os.serie   = '$produto_serie'
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_os.posto   = $posto ";
			if (strlen($os) > 0) $sql .= "AND     tbl_os.os     not in ($os) ";
			$sql .= "AND     tbl_os.data_digitacao::date BETWEEN '$data_inicial' AND '$data_final'
					ORDER BY tbl_os.data_digitacao DESC
					LIMIT 1";
			$res = pg_query($con,$sql); echo $sql;

			if (pg_num_rows($res) > 0) {
				$xxxos      = trim(pg_fetch_result($res,0,os));
				$xxxsua_os  = trim(pg_fetch_result($res,0,sua_os));
				$xxxextrato = trim(pg_fetch_result($res,0,extrato));

				if (strlen($xxxextrato) == 0) {
					$msg_erro .= "Nº de Série $produto_serie digitado é reincidente.<br>
					Favor reabrir a ordem de serviço $xxxsua_os e acrescentar itens.";
				}else{
					$os_reincidente = "'t'";
				}
			}
		}
	}

	##### Verifica??o se o n? de s?rie ? reincidente para a Brit?nia #####
	if ($login_fabrica == 3 and 1 == 2) {
		$sqlX = "SELECT to_char (current_date - INTERVAL '30 days', 'YYYY-MM-DD')";
		$resX = pg_query($con,$sqlX);
		$data_inicial = pg_fetch_result($resX,0,0);

		$sqlX = "SELECT to_char (current_date, 'YYYY-MM-DD')";
		$resX = pg_query($con,$sqlX);
		$data_final = pg_fetch_result($resX,0,0);

		if (strlen($produto_serie) > 0) {
			$sql = "SELECT  tbl_os.os            ,
							tbl_os.sua_os        ,
							tbl_os.data_digitacao
					FROM    tbl_os
					JOIN    tbl_produto ON tbl_produto.produto = tbl_os.produto
					WHERE   tbl_os.serie   = '$produto_serie'
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_produto.numero_serie_obrigatorio IS TRUE
					AND     tbl_os.data_digitacao::date BETWEEN '$data_inicial' AND '$data_final'
					ORDER BY tbl_os.data_digitacao DESC
					LIMIT 1";
			$res = pg_query($con,$sql); echo $sql;

			if (pg_num_rows($res) > 0) {
				$msg_erro .= "Nº de Série $produto_serie digitado é reincidente. Favor verificar.<br>
				Em caso de dúvida, entre em contato com a Fábrica.";
			}
		}
	}

	if(strlen($os) > 0 AND $login_fabrica == 11 ) {
		$sql = "SELECT os FROM tbl_os
				WHERE os = $os AND admin IS NULL";
		$res = pg_query($con,$sql); echo $sql;
		if(pg_num_rows($res)  >0 ) {
			if($login_fabrica == 11) {
				if (strlen ($msg_erro) == 0) { #HD 97504
					$sql = "UPDATE tbl_os_extra SET admin_paga_mao_de_obra = 't' WHERE os = $os";
					$res = pg_query ($con,$sql); echo $sql;
					$msg_erro .= pg_errormessage($con) ;
				}
			}
			$pagar_mao_de_obra = "sim";
		}
	}

	if (strlen ($msg_erro) == 0 ) {
		//  HD 234135 - MLG - Para fazer com que uma fábrica use a tbl_revenda_fabrica, adicionar ao array
		$usa_rev_fabrica = in_array($login_fabrica, array(3));

		if ($usa_rev_fabrica) {
			$subq_revenda = "(SELECT revenda
								FROM tbl_revenda
								JOIN tbl_revenda_fabrica ON tbl_revenda_fabrica.cnpj = tbl_revenda.cnpj
							   WHERE tbl_revenda_fabrica.cnpj    = $xrevenda_cnpj
								 AND tbl_revenda_fabrica.fabrica = $login_fabrica
							   LIMIT 1)";
		} else {
			$subq_revenda = "(SELECT revenda FROM tbl_revenda WHERE cnpj = $xrevenda_cnpj LIMIT 1)";
		}

		if (strlen($os) == 0) {

			/*================ INSERE NOVA OS =========================*/
		$sql = "INSERT INTO tbl_os (
						tipo_atendimento   ,
						segmento_atuacao   ,
						posto              ,
						admin              ,
						fabrica            ,
						sua_os             ,
						data_abertura      ,
						hora_abertura      ,
						cliente            ,
						revenda            ,
						consumidor_nome    ,
						consumidor_cpf     ,
						consumidor_cidade  ,
						consumidor_estado  ,
						consumidor_fone    ,
						consumidor_celular ,
						consumidor_fone_comercial ,
						consumidor_email   ,
						revenda_cnpj       ,
						revenda_nome       ,
						nota_fiscal        ,
						data_nf            ,
						produto            ,
						serie              ,
						qtde_produtos      ,
						aparencia_produto  ,
						acessorios         ,
						defeito_reclamado_descricao,
						defeito_reclamado  ,
						obs                ,
						quem_abriu_chamado ,
						consumidor_revenda ,
						troca_faturada     ,
						os_reincidente     ,
						qtde_km            ,
						autorizacao_cortesia,
						capacidade         ,
						divisao            ,
						os_posto            ";

			if ($login_fabrica == 1) {
				$sql .=	",codigo_fabricacao ,
						satisfacao          ,
						tipo_os             ,
						laudo_tecnico       ,
						fisica_juridica";
			}

			if ($login_fabrica == 19) { // hD 49849
				$sql .=	", tipo_os             ";
			}

			$sql .= ") VALUES (
						$tipo_atendimento                                               ,
						$segmento_atuacao                                               ,
						$posto                                                          ,
						$login_admin                                                    ,
						$login_fabrica                                                  ,
						trim ($sua_os)                                                  ,
						$data_abertura                                                  ,
						$hora_abertura                                                  ,
						(SELECT cliente FROM tbl_cliente WHERE cpf  = $xconsumidor_cpf) ,
						$subq_revenda												    ,
						trim ('$consumidor_nome')                                       ,
						trim ('$consumidor_cpf')                                        ,
						trim ('$consumidor_cidade')                                     ,
						trim ('$consumidor_estado')                                     ,
						trim ('$consumidor_fone')                                       ,
						trim ('$consumidor_celular')                                    ,
						trim ('$consumidor_fone_comercial')                             ,
						trim ('$consumidor_email')                                      ,
						trim ('$revenda_cnpj')                                          ,
						trim ('$revenda_nome')                                          ,
						trim ($nota_fiscal)                                             ,
						$data_nf                                                        ,
						$produto                                                        ,
						'$produto_serie'                                                ,
						$qtde_produtos                                                  ,
						trim ($aparencia_produto)                                       ,
						trim ($acessorios)                                              ,
						$xdefeito_reclamado_descricao                                   ,
						$defeito_reclamado                                              ,
						$xobs                                                           ,
						$xquem_abriu_chamado                                            ,
						'$consumidor_revenda'                                           ,
						$xtroca_faturada                                                ,
						$os_reincidente                                                 ,
						$qtde_km                                                        ,
						$autorizacao_cortesia                                           ,
						$xproduto_capacidade                                            ,
						$xdivisao                                                       ,
						'$os_posto'                                                      ";

			if ($login_fabrica == 1) {
				$sql .= ", $codigo_fabricacao ,
						'$satisfacao'         ,
						$xtipo_os_compressor  ,
						$laudo_tecnico        ,
						$xfisica_juridica";
			}
			if ($login_fabrica == 19) {
				$sql .= ",$xtipo_os_compressor ";
			}

			$sql .= ") RETURNING os;";
		}else{
			//hd17966
			if($login_fabrica==45){
				$sql = "SELECT finalizada,data_fechamento
						FROM   tbl_os
						JOIN   tbl_os_extra USING(os)
						WHERE  fabrica = $login_fabrica
						AND    os      = $os
						AND    extrato         IS     NULL
						AND    finalizada      IS NOT NULL
						AND    data_fechamento IS NOT NULL";
				$res = pg_query ($con,$sql); echo $sql;
				if(pg_num_rows($res)>0){
					$voltar_finalizada = pg_fetch_result($res,0,0);
					$voltar_fechamento = pg_fetch_result($res,0,1);
					$sql = "UPDATE tbl_os SET data_fechamento = NULL , finalizada = NULL
							WHERE os      = $os
							AND   fabrica = $login_fabrica";
					$res = pg_query ($con,$sql); echo $sql;
				}
			}
			/*================ ALTERA OS =========================*/
			$sql = "UPDATE tbl_os SET
						tipo_atendimento   = $tipo_atendimento           ,
						segmento_atuacao   = $segmento_atuacao           , ";
			if($login_fabrica<>3 and $login_fabrica<>6  and $login_fabrica<>24 and $login_fabrica <> 7 and $login_fabrica <> 1){//TAKASHI 01-11 - Angelica informou que OS aberta pelo posto paga um valor, os pelo admin outro valor. Qdo o admin atualiza qualquer informa??o grava o admin e na hora de calcular calcula como se fosse uma os de admin
				$sql .=" admin              = $login_admin                ,";
			}
				$sql .="admin_altera       = $login_admin                ,
						fabrica            = $login_fabrica              ,
						sua_os             = trim($sua_os)               ,
						data_abertura      = $data_abertura              ,
						hora_abertura      = $hora_abertura              ,
						consumidor_nome    = trim('$consumidor_nome')    ,
						consumidor_cpf     = trim('$consumidor_cpf')     ,
						consumidor_fone    = trim('$consumidor_fone')    ,
						consumidor_celular = trim('$consumidor_celular')    ,
						consumidor_fone_comercial = trim('$consumidor_fone_comercial') ,
						consumidor_endereco= trim('$consumidor_endereco'),
						consumidor_numero  = trim('$consumidor_numero'),
						consumidor_complemento= trim('$consumidor_complemento'),
						consumidor_bairro  = trim('$consumidor_bairro'),
						consumidor_cep     = $cep,
						consumidor_estado  = trim('$consumidor_estado'),
						consumidor_cidade  = trim('$consumidor_cidade'),
						consumidor_email   = trim('$consumidor_email') ,
						cliente            = (SELECT cliente FROM tbl_cliente WHERE cpf  = $xconsumidor_cpf) ,
						revenda_cnpj       = trim('$revenda_cnpj')       ,
						revenda_nome       = trim('$revenda_nome')       ,
						nota_fiscal        = trim($nota_fiscal)          ,
						data_nf            = $data_nf                    ,
						produto            = $produto                    ,
						serie              = '$produto_serie'            ,
						qtde_produtos      = $qtde_produtos              ,
						aparencia_produto  = trim($aparencia_produto)    ,
						acessorios         = trim($acessorios)           ,
						defeito_reclamado_descricao = $xdefeito_reclamado_descricao,
						quem_abriu_chamado = $xquem_abriu_chamado        ,
						obs                = $xobs                     ,
						consumidor_revenda = '$consumidor_revenda'       ,
						troca_faturada     = $xtroca_faturada            ,
						os_reincidente     = $os_reincidente             ,
						autorizacao_cortesia = $autorizacao_cortesia     ,
						qtde_km            = $qtde_km                    ,
						capacidade         = $xproduto_capacidade        ,
						divisao            = $xdivisao                   ,
						revenda            = $subq_revenda               ,
						os_posto           = '$os_posto'                 ,
						nota_fiscal_saida  = trim($nota_fiscal_saida)    ,
						data_nf_saida      = $data_nf_saida              ";


			if ($login_fabrica == 1) {
				$sql .=	", codigo_fabricacao = $codigo_fabricacao ,
						satisfacao           = '$satisfacao'      ,
						tipo_os              = $xtipo_os_compressor,
						laudo_tecnico        = $laudo_tecnico     ,
						fisica_juridica      = $xfisica_juridica";
			}
			if ($login_fabrica == 19) {
				$sql .=	", tipo_os              = $xtipo_os_compressor ";
			}
			if ($login_fabrica <> 14) {
				$sql .=	", defeito_reclamado  = $defeito_reclamado    ";
			}

			$sql .= " WHERE os      = $os
					AND   fabrica = $login_fabrica";
		}
		$res = @pg_query ($con,$sql); echo $sql;
		$msg_erro .= pg_errormessage($con);

		$msg_erro = substr($msg_erro,6);

		if (strlen ($msg_erro) == 0) {
			if (strlen($os) == 0) {
				$res = pg_query ($con,"SELECT CURRVAL ('seq_os')");
				$os  = pg_fetch_result ($res,0,0);

            if(in_array($login_fabrica, array(87))){
                $sql = "SELECT os FROM tbl_os_extra WHERE os = {$os}";
                $res = @pg_query($con, $sql);

                if(@pg_num_rows($res)==0){
                    $sql = "INSERT INTO tbl_os_extra (hora_tecnica, os) VALUES ({$horas_trabalhadas}, {$os});";
                    $res = pg_query($con, $sql);
                }
            }

				// HD 52202 comentei, não sei por que atualizar de novo depois de inserir
				/*
				$sql = "UPDATE tbl_os SET consumidor_nome = tbl_cliente.nome WHERE tbl_os.os = $os AND tbl_os.cliente IS NOT NULL AND tbl_os.cliente = tbl_cliente.cliente";
				$res = @pg_query ($con,$sql); echo $sql;*/

				$sql = "UPDATE tbl_os SET
							consumidor_cidade = tbl_cidade.nome  ,
							consumidor_estado = tbl_cidade.estado
						FROM  tbl_cliente
						JOIN  tbl_cidade on tbl_cliente.cidade = tbl_cidade.cidade
						WHERE tbl_os.os = $os
						AND   tbl_os.cliente IS NOT NULL
						AND   tbl_os.consumidor_cidade IS NULL
						AND   tbl_os.cliente = tbl_cliente.cliente ";

				$res = pg_query ($con,$sql); echo $sql;

				if (strlen ($consumidor_endereco)	== 0) {$consumidor_endereco		= "null";} else {$consumidor_endereco	= "'$consumidor_endereco'";}
				if (strlen ($consumidor_numero)		== 0) {$consumidor_numero		= "null";} else {$consumidor_numero		= "'$consumidor_numero'";}
				if (strlen ($consumidor_complemento)== 0) {$consumidor_complemento	= "null";} else {$consumidor_complemento= "'$consumidor_complemento'";}
				if (strlen ($consumidor_bairro)		== 0) {$consumidor_bairro		= "null";} else {$consumidor_bairro		= "'$consumidor_bairro'" ; }
				if (strlen ($consumidor_cep)		== 0) {$consumidor_cep			= "null";} else {$consumidor_cep		= "'" . preg_replace ('/\D/', '', $consumidor_cep) . "'";}
				if (strlen ($consumidor_cidade)		== 0) {$consumidor_cidade		= "null";} else { $consumidor_cidade	= "'$consumidor_cidade'";}
				if (strlen ($consumidor_estado)		== 0) {$consumidor_estado		= "null";} else { $consumidor_estado	= "'$consumidor_estado'";}

				$sql = "UPDATE tbl_os SET
							consumidor_endereco    = $consumidor_endereco       ,
							consumidor_numero      = $consumidor_numero         ,
							consumidor_complemento = $consumidor_complemento    ,
							consumidor_bairro      = $consumidor_bairro         ,
							consumidor_cep         = $consumidor_cep            ,
							consumidor_cidade      = $consumidor_cidade         ,
							consumidor_estado      = $consumidor_estado
						WHERE tbl_os.os = $os ";
				$res = pg_query($con,$sql); echo $sql;
				$msg_erro .= pg_last_error($con);
			}

			if(strlen($msg_erro)==0){
				//HD 23041 - Rotina de vários defeitos para uma única OS.
				if($login_fabrica==19) {
					// HD 28155
					if ($tipo_atendimento <> 6){
						$numero_vezes = 100;
						$array_integridade = array();
						for ($i=0;$i<$numero_vezes;$i++) {
							$int_reclamado = trim($_POST["integridade_defeito_reclamado_$i"]);
							if ( $i <> $int_reclamado and strlen($int_reclamado) >0){
								array_push($array_integridade,$int_reclamado);
							}
							if (!isset($_POST["integridade_defeito_reclamado_$i"])) continue;
							if (strlen($int_reclamado)==0) continue;

							$aux_defeito_reclamado = $int_reclamado;



							$sql = "SELECT defeito_constatado_reclamado
									FROM tbl_os_defeito_reclamado_constatado
									WHERE os                = $os
									AND   defeito_reclamado = $aux_defeito_reclamado";
							$res = @pg_query ($con,$sql); echo $sql;
							$msg_erro .= pg_errormessage($con);
							if(@pg_num_rows($res)==0){
								$sql = "INSERT INTO tbl_os_defeito_reclamado_constatado(
											os,
											defeito_reclamado
										)VALUES(
											$os,
											$aux_defeito_reclamado
										)
								";
								$res = @pg_query ($con,$sql); echo $sql;
								$msg_erro .= pg_errormessage($con);
							}
						}

						// HD 33303
						$lista_defeitos = implode($array_integridade,",");
						$sql = "DELETE FROM tbl_os_defeito_reclamado_constatado
								WHERE os = $os
								AND   defeito_reclamado NOT IN ($lista_defeitos) ";
						$res = @pg_query ($con,$sql); echo $sql;
						$msg_erro .= pg_errormessage($con);
						//o defeito reclamado recebe o primeiro defeito constatado.
						//verifica se já tem defeito cadastrado

						$sqld = "SELECT *
							FROM tbl_os_defeito_reclamado_constatado
							WHERE os = $os";
						$res = @pg_query ($con,$sqld);
						$msg = pg_errormessage($con);
						if(strlen($msg)>0)
							$msg_erro .= "Selecione o Defeito Reclamado. <br>";

						if(@pg_num_rows($res)==0){
							$msg_erro .= "Quando lançar o defeito reclamado é necessário clicar em adicionar defeito. <br>";
						}
					}
					if ($tipo_atendimento == 6 and $defeito_reclamado <> 0){
						$numero_vezes = 100;
						$array_integridade = array();
						for ($i=0;$i<$numero_vezes;$i++) {
							$int_reclamado = trim($_POST["integridade_defeito_reclamado_$i"]);
							if ( $i <> $int_reclamado and strlen($int_reclamado) >0){
								array_push($array_integridade,$int_reclamado);
							}
							if (!isset($_POST["integridade_defeito_reclamado_$i"])) continue;
							if (strlen($int_reclamado)==0) continue;

							$aux_defeito_reclamado = $int_reclamado;

							$sql = "SELECT defeito_constatado_reclamado
									FROM tbl_os_defeito_reclamado_constatado
									WHERE os                = $os
									AND   defeito_reclamado = $aux_defeito_reclamado";
							$res = @pg_query ($con,$sql); echo $sql;
							$msg_erro .= pg_errormessage($con);
							if(@pg_num_rows($res)==0){
								$sql = "INSERT INTO tbl_os_defeito_reclamado_constatado(
											os,
											defeito_reclamado
										)VALUES(
											$os,
											$aux_defeito_reclamado
										)
								";
								$res = @pg_query ($con,$sql); echo $sql;
								$msg_erro .= pg_errormessage($con);
							}
						}
						// HD 33303
						$lista_defeitos = implode($array_integridade,",");
						$sql = "DELETE FROM tbl_os_defeito_reclamado_constatado
								WHERE os = $os
								AND   defeito_reclamado NOT IN ($lista_defeitos) ";
						$res = @pg_query ($con,$sql); echo $sql;
						$msg_erro .= pg_errormessage($con);

						//o defeito reclamado recebe o primeiro defeito constatado.
						//verifica se já tem defeito cadastrado
						$sqld = "SELECT *
								   FROM tbl_os_defeito_reclamado_constatado
								  WHERE os = $os";
						$res = @pg_query ($con,$sqld);
						$msg_erro .= pg_errormessage($con);

						if (@pg_num_rows($res) == 0) {
							$msg_erro .= "Quando lançar o defeito reclamado é necessário clicar em adicionar defeito. <br>";
						}
					}
				}
			}

			if (strlen($msg_erro) == 0) {
				$sql      = "SELECT fn_valida_os($os, $login_fabrica)";//HD 256659
				$res      = @pg_query($con,$sql); echo $sql;
				$msg_erro .= pg_errormessage($con);
			}

			#--------- grava OS_EXTRA ------------------
			if (strlen ($msg_erro) == 0) {
				$taxa_visita				= str_replace (",",".",trim ($_POST['taxa_visita']));
				$visita_por_km				= trim ($_POST['visita_por_km']);
				$valor_por_km				= str_replace (",",".",trim ($_POST['valor_por_km']));

				$hora_tecnica				= str_replace (",",".",trim ($_POST['hora_tecnica']));

				$regulagem_peso_padrao		= str_replace (".","",trim ($_POST['regulagem_peso_padrao']));
				$regulagem_peso_padrao		= str_replace (",",".",$regulagem_peso_padrao);

				$certificado_conformidade	= str_replace (".","",trim ($_POST['certificado_conformidade']));
				$certificado_conformidade	= str_replace (",",".",$certificado_conformidade);

				$valor_diaria				= str_replace (".","",trim ($_POST['valor_diaria']));
				$valor_diaria				= str_replace (",",".",$valor_diaria);

				$condicao					= trim ($_POST['condicao']);

				#Hd 311411
				$data_conserto				= trim($_POST['data_conserto']);

				if(strlen($condicao)==0){
					if($login_fabrica ==7 ) {
						$msg_erro .= "Por favor selecione a condição de pagamento.<BR>";
					}else{
						$xcondicao = 'null';
						$xtabela   = 'null';
					}

				} else {

					$xcondicao = $condicao;

					$sql = "SELECT tabela
							FROM tbl_condicao
							WHERE fabrica = $login_fabrica
							AND condicao = $condicao; ";
					$res = pg_query($con,$sql); echo $sql;
					$msg_erro .= pg_errormessage($con);

					if (pg_num_rows($res) > 0) {
						$xtabela = pg_fetch_result($res,0,'tabela');
					}

					if (strlen($xtabela)==0){
						$xtabela = "null";
					}

				}

				if (strlen ($taxa_visita)				== 0) $taxa_visita					= '0';
				if (strlen ($visita_por_km)				== 0) $visita_por_km				= 'f';
				if (strlen ($valor_por_km)				== 0) $valor_por_km					= '0';
				if (strlen ($hora_tecnica)				== 0) $hora_tecnica					= '0';
				if (strlen ($regulagem_peso_padrao)		== 0) $regulagem_peso_padrao		= '0';
				if (strlen ($certificado_conformidade)	== 0) $certificado_conformidade		= '0';
				if (strlen ($valor_diaria)				== 0) $valor_diaria					= '0';

				$cobrar_deslocamento	= trim ($_POST['cobrar_deslocamento']);
				$cobrar_hora_diaria		= trim ($_POST['cobrar_hora_diaria']);

				$desconto_deslocamento	= str_replace (",",".",trim ($_POST['desconto_deslocamento']));
				$desconto_hora_tecnica	= str_replace (",",".",trim ($_POST['desconto_hora_tecnica']));
				$desconto_diaria		= str_replace (",",".",trim ($_POST['desconto_diaria']));
				$desconto_regulagem		= str_replace (",",".",trim ($_POST['desconto_regulagem']));
				$desconto_certificado	= str_replace (",",".",trim ($_POST['desconto_certificado']));
				$desconto_peca			= str_replace (",",".",trim ($_POST['desconto_peca']));

				$cobrar_regulagem		= trim ($_POST['cobrar_regulagem']);
				$cobrar_certificado		= trim ($_POST['cobrar_certificado']);

				$sqlt ="SELECT tipo_posto, consumidor_revenda, os_numero
						FROM tbl_os
						JOIN tbl_posto_fabrica USING(posto)
						WHERE tbl_os.os = $os
						AND   tbl_posto_fabrica.fabrica = $login_fabrica";
				$rest = pg_query($con,$sqlt);
				$tipo_posto         = pg_fetch_result($rest,0,tipo_posto);
				$consumidor_revenda = pg_fetch_result($rest,0,consumidor_revenda);
				$os_numero          = pg_fetch_result($rest,0,os_numero);

				if (strtoupper($consumidor_revenda) == 'R' and $login_fabrica == 7){
					$os_manutencao = 't';
				}

				if ($tipo_posto == 215 or $tipo_posto == 214){
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

				if (strlen($veiculo)==0){
					$xveiculo = "NULL";
				}else{
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
				}else{
					$xvalor_por_km = "0";
					$xvisita_por_km = "'f'";
				}

				if (strlen($taxa_visita)>0){
					$xtaxa_visita = $taxa_visita;
				}else{
					$xtaxa_visita = '0';
				}

				/* HD 29838 */
				if ($tipo_atendimento == 63){
					$cobrar_deslocamento = 'isento';
				}

				if ($cobrar_deslocamento == 'isento' OR strlen($cobrar_deslocamento)==0){
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

				if(strlen($valor_diaria) > 0){
					$xvalor_diaria = $valor_diaria;
				}else{
					$xvalor_diaria = '0';
				}

				if(strlen($hora_tecnica) > 0){
					$xhora_tecnica = $hora_tecnica;
				}else{
					$xhora_tecnica = '0';
				}

				if ($cobrar_hora_diaria == 'isento' OR strlen($cobrar_hora_diaria)==0){
					$xhora_tecnica = '0';
					$xvalor_diaria = '0';
				}elseif ($cobrar_hora_diaria == 'diaria'){
					$xhora_tecnica = '0';
				}elseif ($cobrar_hora_diaria == 'hora'){
					$xvalor_diaria = '0';
				}

                if($login_fabrica == 87){
                    $xhora_tecnica = $horas_trabalhadas;
                }

				if(strlen($regulagem_peso_padrao) > 0 and $cobrar_regulagem == 't'){
					$xregulagem_peso_padrao = $regulagem_peso_padrao;
				}else{
					$xregulagem_peso_padrao = '0';
				}

				if(strlen($certificado_conformidade) > 0 and $cobrar_certificado == 't'){
					$xcertificado_conformidade = $certificado_conformidade;
				}else{
					$xcertificado_conformidade = "0";
				}

				/* Descontos */
				if(strlen($desconto_deslocamento) > 0){
					$desconto_deslocamento = $desconto_deslocamento;
				}else{
					$desconto_deslocamento = '0';
				}

				if(strlen($desconto_hora_tecnica) > 0){
					$desconto_hora_tecnica = $desconto_hora_tecnica;
				}else{
					$desconto_hora_tecnica = '0';
				}

				if(strlen($desconto_diaria) > 0){
					$desconto_diaria = $desconto_diaria;
				}else{
					$desconto_diaria = '0';
				}

				if(strlen($desconto_regulagem) > 0){
					$desconto_regulagem = $desconto_regulagem;
				}else{
					$desconto_regulagem = '0';
				}

				if(strlen($desconto_certificado) > 0){
					$desconto_certificado = $desconto_certificado;
				}else{
					$desconto_certificado = '0';
				}

				if(strlen($desconto_peca) > 0){
					$desconto_peca = $desconto_peca;
				}else{
					$desconto_peca = '0';
				}

				//hd 11083 7/1/2008
				if($login_fabrica == 3){
					if (strlen(trim($orientacao_sac))>0 AND trim($orientacao_sac)!='null' ){
						$orientacao_sac =  date("d/m/Y H:i")." - ".$orientacao_sac;
						$sql = "UPDATE  tbl_os_extra SET
							orientacao_sac          =  CASE WHEN orientacao_sac IS NULL OR orientacao_sac = 'null' THEN '' ELSE orientacao_sac || ' \n' END || trim('$orientacao_sac') ,
							taxa_visita              = $xtaxa_visita                               ,
							visita_por_km            = $xvisita_por_km                             ,
							hora_tecnica             = $xhora_tecnica                              ,
							regulagem_peso_padrao    = $xregulagem_peso_padrao                     ,
							certificado_conformidade = $xcertificado_conformidade                  ,
							valor_diaria             = $xvalor_diaria                              ,
							admin_paga_mao_de_obra   = '$admin_paga_mao_de_obra' ";
					}
				}else{
					if ($login_fabrica == 11) {

						$sql_obs = "SELECT orientacao_sac from tbl_os_extra where os = $os";
						$res_obs = pg_query($con,$sql_obs);
						$orientacao_sac_aux         = pg_fetch_result($res_obs,0,orientacao_sac);
						$sql_usario = "SELECT login from tbl_admin where admin = $login_admin";
						$res_usuario = pg_query($con,$sql_usario);
						$usuario         = pg_fetch_result($res_usuario,0,login);


						$data_hoje = date("d/m/Y H:i:s");
						$orientacao_sac .= "<p>Usuário: $usuario</p>";
						$orientacao_sac .= "<p>Data: $data_hoje</p>";
						$orientacao_sac .= $orientacao_sac_aux;
					}
					$orientacao_sac = str_replace("'", "\'", $orientacao_sac);
					$sql = "UPDATE  tbl_os_extra SET
						orientacao_sac           = trim('$orientacao_sac')    ,
						classificacao_os         = $classificacao_os";
					if(strlen($pagar_mao_de_obra) == 0){ #97504
						$sql .= " , admin_paga_mao_de_obra   = '$admin_paga_mao_de_obra' ";
					}
				}

				if ($os_reincidente == "'t'") {
					$sql .= ", os_reincidente = $xxxos ";
				}

				$sql .= " WHERE tbl_os_extra.os = $os";

				$res = pg_query ($con,$sql); echo $sql;
				#if($ip=='187.39.215.117') echo nl2br($sql);
				$msg_erro .= pg_errormessage($con);

				if ($login_fabrica <> 3){

					if( $login_fabrica == 7 and strlen($condicao)>0) {
						$sql = "UPDATE tbl_os SET
										condicao = $condicao
								WHERE os      = $os
								AND   fabrica = $login_fabrica";
						$res = pg_query ($con,$sql); echo $sql;
						$msg_erro .= pg_errormessage($con);
					}

					if ($os_manutencao == 't' and strlen($os_numero)>0){
						$sql = "UPDATE tbl_os_revenda SET
									condicao = $condicao
								WHERE os_revenda = $os_numero ";
						$res = pg_query($con,$sql); echo $sql;
						$msg_erro .= pg_errormessage($con);

						$sql = "UPDATE tbl_os SET
									condicao = $xcondicao,
									tabela   = $xtabela
								WHERE os_numero  = $os_numero

								AND   fabrica    = $login_fabrica";
						$res = pg_query($con,$sql); echo $sql;
						$msg_erro .= pg_errormessage($con);

					}

					/* ATUALIZACAO: Outros Serviços */
					$sql = "UPDATE tbl_os_extra SET
									certificado_conformidade    = $xcertificado_conformidade,
									desconto_certificado        = $desconto_certificado,
									desconto_peca               = $desconto_peca
							WHERE os = $os ";
					$res = @pg_query($con,$sql); echo $sql;
					$msg_erro .= pg_errormessage($con);

					if (strlen($deslocamento_km)>0){
						$deslocamento_km = $deslocamento_km;
					}else{
						$deslocamento_km = '0';
					}

					#Se não for Filizola, nao alterar o valor do deslocamento
					if ($login_fabrica<>7){
						$deslocamento_km = ' deslocamento_km ';
					}

					/* ATUALIZACAO: Deslocamento e Mao de Obra (do técnico) */
					if ($os_manutencao == 't'){

						$sql = "UPDATE tbl_os_revenda SET

									/* DESLOCAMENTO */
									taxa_visita                 = $xtaxa_visita,
									visita_por_km               = $xvisita_por_km,
									valor_por_km                = $xvalor_por_km,
									veiculo                     = $xveiculo,
									deslocamento_km             = $deslocamento_km,

									/* MAO-DE-OBRA */
									hora_tecnica                = $xhora_tecnica,
									valor_diaria                = $xvalor_diaria,

									/* OUTROS SERVIÇOS */
									regulagem_peso_padrao       = $xregulagem_peso_padrao,
									/*desconto_regulagem        = $desconto_regulagem, (nao é usado mais desconto, se precisar de desconto tem que criar o campo)*/

									/* DESCONTOS */
									desconto_deslocamento       = $desconto_deslocamento,
									desconto_hora_tecnica       = $desconto_hora_tecnica,
									desconto_diaria             = $desconto_diaria

								WHERE os_revenda = $os_numero ";
					}else{
						$sql = "UPDATE tbl_os_extra SET

									/* DESLOCAMENTO */
									taxa_visita                 = $xtaxa_visita,
									visita_por_km               = $xvisita_por_km,
									valor_por_km                = $xvalor_por_km,
									veiculo                     = $xveiculo,
									deslocamento_km             = $deslocamento_km,

									/* MAO-DE-OBRA */
									hora_tecnica                = $xhora_tecnica,
									valor_diaria                = $xvalor_diaria,

									/* OUTROS SERVIÇOS */
									regulagem_peso_padrao       = $xregulagem_peso_padrao,
									desconto_regulagem          = $desconto_regulagem,

									/* DESCONTOS */
									desconto_deslocamento       = $desconto_deslocamento,
									desconto_hora_tecnica       = $desconto_hora_tecnica,
									desconto_diaria             = $desconto_diaria
								WHERE os = $os ";
					}

					$res = @pg_query($con,$sql); echo $sql;
					$msg_erro .= pg_errormessage($con);
				}

				if($login_fabrica==45 and strlen($voltar_fechamento)>0 AND strlen($voltar_finalizada)>0) {
					$sql = "UPDATE tbl_os SET data_fechamento = '$voltar_fechamento' , finalizada = '$voltar_finalizada'
							WHERE os      = $os
							AND   fabrica = $login_fabrica";
					$res = pg_query ($con,$sql); echo $sql;
				}

				#HD 311411
				if(strlen($msg_erro)==0 && $login_fabrica==6 && strlen($data_conserto)==0) {
					$sqlConserto = "UPDATE tbl_os SET data_conserto = NULL
									WHERE os      = $os
									AND   fabrica = $login_fabrica";
					$resConserto = pg_query ($con,$sqlConserto);
				#HD 311411 Fim
				}elseif(in_array($login_fabrica,$fabricas_alteram_conserto) && strlen($data_conserto)>0){

					if(strlen($msg_erro)==0){

						list($dc, $mc, $yc) = explode("/", $data_conserto);
						if(!checkdate($mc,$dc,$yc))
							$msg_erro = "Data de Conserto Inválida";

						if(strlen($msg_erro)==0){

							$data_conserto = fnc_formata_data_pg($data_conserto);
							$sqlConserto = "UPDATE tbl_os SET data_conserto = $data_conserto
											WHERE os      = $os
											AND   fabrica = $login_fabrica";
							$resConserto = pg_query ($con,$sqlConserto);
						}
					}
				}

				// HD 23217
				if(strlen($msg_erro) ==0 AND $login_fabrica==1){
					if(strlen($os) >0){
						$sql="SELECT fn_valida_os_reincidente ($os,$login_fabrica)";
						$res = pg_query ($con,$sql); echo $sql;
						$msg_erro .= pg_errormessage($con);
					}
				}

				// HD 32726
				if(strlen($msg_erro) ==0 AND $login_fabrica==7){
					if(strlen($os) >0){
						$sql="SELECT fn_calcula_os_filizola ($os,$login_fabrica)";
						$res = pg_query ($con,$sql); echo $sql;
						$msg_erro .= pg_errormessage($con);
					}
				}

				if(strlen($msg_erro) ==0 AND $login_fabrica==3){
					$sql_log = "insert into  tbl_os_log_admin  (os,admin) values ('$os','$login_admin')";
					$res_log = pg_query($con,$sql_log);
					$msg_erro .= pg_errormessage($con);

				}

				# HD - 725866 - Enviar e-mail para o posto informando a Orientação da Fábrica ORBIS

				if ($login_fabrica == 88 and strlen($orientacao_sac) > 0) {

					$sql = "select
							tbl_posto.email,
							tbl_posto_fabrica.contato_email,
							tbl_admin.email as email_admin,
							tbl_posto_fabrica.codigo_posto,
							tbl_posto_fabrica.nome_fantasia

						from tbl_os

						join tbl_posto_fabrica on (tbl_os.posto = tbl_posto_fabrica.posto
						and tbl_posto_fabrica.fabrica = $login_fabrica)

						join tbl_admin on tbl_posto_fabrica.fabrica = tbl_admin.fabrica

						join tbl_posto on tbl_posto_fabrica.posto = tbl_posto.posto

						where tbl_admin.fabrica = $login_fabrica
						and tbl_admin.admin = $login_admin
						and tbl_os.os = $os and tbl_os.fabrica=$login_fabrica";

					//echo nl2br($sql);
					$res = pg_query($con, $sql);

					if (pg_num_rows($res) > 0) {

						$email           = trim(pg_fetch_result($res,0,'email'));
						$contato_email   = trim(pg_fetch_result($res,0,'contato_email'));
						$email_remetente = trim(pg_fetch_result($res,0,'email_admin'));
						$codigo_posto    = trim(pg_fetch_result($res,0,'codigo_posto'));
						$nome_fantasia   = trim(pg_fetch_result($res,0,'nome_fantasia'));


						if (strlen($contato_email) > 0) {

							$email_destinatatio = $contato_email;

						} else if (strlen($email) > 0) {

							$email_destinatatio = $email;
						}

						if(strlen($email_destinatatio)>0){

							$remetente    = "$email_remetente";
							$destinatario = "$email_destinatatio";
							$assunto      = "Orientação da Fábrica";
							$message      = "O Fabricante fez uma orientação na OS - $os.<br><br>
							<b>Orientação:</b> $orientacao_sac<br> ";
							$headers      ="Return-Path: <$email_remetente>\nFrom:".$remetente."\nContent-type: text/html\n";

							mail($destinatario,$assunto,$message,$headers);



						} else {


							$remetente    = "$email_remetente";
							$destinatario = "$email_remetente";
							$assunto      = "Orientação da Fábrica não enviada";
							$message      = "O Posto $nome_fantasia - $codigo_posto não possuí e-mail cadastrado no Sistema Telecontrol<br><br>";
							$headers      ="Return-Path: <$email_remetente>\nFrom:".$remetente."\nContent-type: text/html\n";

							mail($destinatario,$assunto,$message,$headers);

						}

					}

				}

				if (strlen ($msg_erro) == 0) {


					$res = pg_query ($con,"COMMIT TRANSACTION");

					if ($imprimir_os == "imprimir") {
						header ("Location: os_item.php?os=$os&imprimir=1");
						exit;
					}

					if ($login_fabrica == 7){
						#HD 25608
						$sql = "SELECT os
								FROM tbl_os
								WHERE fabrica = $login_fabrica
								AND   os       = $os
								AND   produto IS NULL";
						$res = pg_query ($con,$sql); echo $sql;
						if (pg_num_rows($res) > 0) {
							header ("Location: os_press.php?os=$os");
							exit;
						}
					}
					header ("Location: os_item.php?os=$os");
					exit;
				}
			}
		}
	}

	if (strlen ($msg_erro) > 0) {

		if (strpos ($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_nf\"") > 0)
			$msg_erro = "Data da compra maior que a data da abertura da Ordem de Serviço.";

		if (strpos ($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_abertura\"") > 0)
			$msg_erro = " Data da abertura deve ser inferior ou igual a data de digitaÃ§Ã£o da OS no sistema (data de hoje).";

		if (strpos ($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_abertura_futura\"") > 0)
			$msg_erro = " Data da abertura deve ser inferior ou igual a data de hoje.";

		if (strpos($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_nf_superior_data_abertura\"") > 0)//HD 235182
			$msg_erro = " Data da Nota Fiscal deve ser inferior ou igual a data de digitação da OS no sistema (data de hoje).";


		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}
}

/* ====================  APAGAR  =================== */
$obs_exclusao = $_POST['obs_exclusao'];

if ($btn_acao == "apagar") {
	if(strlen($os) > 0){
		if ($login_fabrica == 1) {
			$sql =	"SELECT sua_os
					FROM tbl_os
					WHERE os = $os;";
			$res = @pg_query ($con,$sql); echo $sql;
			$msg_erro = pg_errormessage($con);
			if (@pg_num_rows($res) == 1) {
				$sua_os = @pg_fetch_result($res,0,0);
				$sua_os_explode = explode("-", $sua_os);
				$xsua_os = $sua_os_explode[0];
			}
		}

		if ($login_fabrica == 3){
			if(strlen($obs_exclusao)==0){
				$msg_erro .= "Informe o motivo da exclusão da OS. <br>";
			}

			if(strlen($msg_erro)==0){
				$sqlO = "SELECT obs FROM tbl_os where os = $os";
				$resO = pg_query($con, $sqlO);

				if(pg_numrows($resO)>0){
					$obs = pg_result($resO,0,obs);

					$obs_exclusao = $obs . " " . $obs_exclusao;
				}

				$sql = "UPDATE tbl_os SET excluida = 't' , admin_excluida = $login_admin, obs = '$obs_exclusao' WHERE os = $os AND fabrica = $login_fabrica";
				$res = @pg_query ($con,$sql); echo $sql;

				#158147 Paulo/Waldir desmarcar se for reincidente
				$sql = "SELECT fn_os_excluida_reincidente($os,$login_fabrica)";
				$res = pg_query($con, $sql);
			}
		}else{
			$sql = "SELECT fn_os_excluida($os,$login_fabrica,$login_admin);";
			$res = @pg_query ($con,$sql); echo $sql;
		}
		$msg_erro .= pg_errormessage($con);


		if (strlen($msg_erro) == 0 AND $login_fabrica == 1) {
			$sqlPosto = "SELECT tbl_posto.posto
						FROM tbl_posto
						JOIN tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
											   AND tbl_posto_fabrica.fabrica = $login_fabrica
						WHERE tbl_posto_fabrica.codigo_posto = '".trim($_POST['posto_codigo'])."'
						AND   tbl_posto_fabrica.fabrica      = $login_fabrica;";
			$resPosto = @pg_query($con,$sqlPosto);
			if (@pg_num_rows($res) == 1) {
				$xposto = pg_fetch_result($resPosto,0,0);
			}

			$sql = "SELECT tbl_os.sua_os
					FROM tbl_os
					WHERE sua_os ILIKE '$xsua_os-%'
					AND   posto   = $xposto
					AND   fabrica = $login_fabrica;";
			$res = @pg_query($con,$sql); echo $sql;
			$msg_erro = pg_errormessage($con);

			if (@pg_num_rows($res) == 0) {
				$sql = "DELETE FROM tbl_os_revenda
						WHERE  tbl_os_revenda.sua_os  = '$xsua_os'
						AND    tbl_os_revenda.fabrica = $login_fabrica
						AND    tbl_os_revenda.posto   = $xposto";
				$res = @pg_query($con,$sql); echo $sql;
				$msg_erro = pg_errormessage($con);
			}
		}

		if (strlen ($msg_erro) == 0) {
			header("Location: os_parametros.php");
			exit;
		}
	}
}

/*================ LE OS DA BASE DE DADOS =========================*/
//echo $os."parei"; exit;
if (strlen ($os) > 0) {

	 $sql = "SELECT  tbl_os.os                                           ,
					tbl_os.tipo_atendimento                                     ,
					tbl_os.segmento_atuacao                                     ,
					tbl_os.posto                                                ,
					tbl_posto.nome                             AS posto_nome    ,
					tbl_os.sua_os                                               ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura ,
					tbl_os.data_fechamento                                      ,
					tbl_os.hora_abertura                                        ,
					tbl_os.produto                                              ,
					tbl_produto.referencia                                      ,
					tbl_produto.descricao                                       ,
					tbl_os.serie                                                ,
					tbl_os.qtde_produtos                                        ,
					tbl_os.cliente                                              ,
					tbl_os.consumidor_nome                                      ,
					tbl_os.consumidor_cpf                                       ,
					tbl_os.consumidor_fone                                      ,
					tbl_os.consumidor_celular                                   ,
					tbl_os.consumidor_fone_comercial                            ,
					tbl_os.consumidor_cidade                                    ,
					tbl_os.consumidor_estado                                    ,
					tbl_os.consumidor_cep                                       ,
					tbl_os.consumidor_endereco                                  ,
					tbl_os.consumidor_numero                                    ,
					tbl_os.consumidor_complemento                               ,
					tbl_os.consumidor_bairro                                    ,
					tbl_os.consumidor_email                                     ,
					tbl_os.revenda                                              ,
					tbl_os.revenda_cnpj                                         ,
					tbl_os.revenda_nome                                         ,
					tbl_os.nota_fiscal                                          ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')       AS data_nf       ,
					tbl_os.aparencia_produto                                    ,
					tbl_os_extra.orientacao_sac                                 ,
					tbl_os_extra.admin_paga_mao_de_obra                         ,
					tbl_os_extra.obs_nf                     AS observacao_pedido,
					tbl_os.acessorios                                           ,
					tbl_os.fabrica                                              ,
					tbl_os.quem_abriu_chamado                                   ,
					tbl_os.obs                                                  ,
					tbl_os.consumidor_revenda                                   ,
					tbl_os.condicao                                             ,
					tbl_os_extra.extrato                                        ,
					tbl_posto_fabrica.codigo_posto             AS posto_codigo  ,
					tbl_posto_fabrica.contato_endereco       AS contato_endereco,
					tbl_posto_fabrica.contato_numero           AS contato_numero,
					tbl_posto_fabrica.contato_bairro           AS contato_bairro,
					tbl_posto_fabrica.contato_cidade           AS contato_cidade,
					tbl_posto_fabrica.contato_estado           AS contato_estado,
					tbl_posto_fabrica.contato_cep                               ,
					tbl_os.codigo_fabricacao                                    ,
					tbl_os.satisfacao                                           ,
					tbl_os.laudo_tecnico                                        ,
					tbl_os.troca_faturada                                       ,
					tbl_os.admin                                                ,
					tbl_os.troca_garantia                                       ,
					tbl_os. autorizacao_cortesia                                ,
					tbl_os.defeito_reclamado                                    ,
					tbl_os.defeito_reclamado_descricao                          ,
					tbl_os.fisica_juridica                                      ,
					tbl_os.quem_abriu_chamado                                   ,
					tbl_os.capacidade                 AS produto_capacidade     ,
					tbl_os.versao                     AS versao                 ,
					tbl_os.divisao                    AS divisao                ,
					tbl_os.qtde_km                                              ,
					tbl_os.tipo_os                                              ,
					tbl_os_extra.taxa_visita                                    ,
					tbl_os_extra.visita_por_km                                  ,
					tbl_os_extra.valor_por_km                                   ,
					tbl_os_extra.deslocamento_km                                ,
					tbl_os_extra.hora_tecnica                                   ,
					tbl_os_extra.regulagem_peso_padrao                          ,
					tbl_os_extra.certificado_conformidade                       ,
					tbl_os_extra.valor_diaria                                   ,
					tbl_os_extra.veiculo                                        ,
					tbl_os_extra.desconto_deslocamento                          ,
					tbl_os_extra.desconto_hora_tecnica                          ,
					tbl_os_extra.desconto_diaria                                ,
					tbl_os_extra.desconto_regulagem                             ,
					tbl_os_extra.desconto_certificado                           ,
					tbl_os_extra.desconto_peca                                  ,
					tbl_os_extra.classificacao_os                               ,
					tbl_os.os_posto                                             ,
					tbl_os.nota_fiscal_saida                                    ,
					to_char(tbl_os.data_nf_saida,'DD/MM/YYYY') as data_nf_saida
			FROM	tbl_os
			LEFT JOIN	tbl_produto          ON tbl_produto.produto       = tbl_os.produto
			JOIN	tbl_posto            ON tbl_posto.posto           = tbl_os.posto
			JOIN	tbl_fabrica          ON tbl_fabrica.fabrica       = tbl_os.fabrica
			JOIN	tbl_posto_fabrica    ON tbl_posto_fabrica.posto   = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
										AND tbl_fabrica.fabrica       = $login_fabrica
			LEFT JOIN	tbl_os_extra     ON tbl_os.os                 = tbl_os_extra.os
			WHERE	tbl_os.os      = $os
			AND		tbl_os.fabrica = $login_fabrica";
	$res = pg_query ($con,$sql); echo $sql;

	if (pg_num_rows ($res) == 1) {
		$os                              = pg_fetch_result ($res,0,os);
		$tipo_atendimento                = pg_fetch_result ($res,0,tipo_atendimento);
		$segmento_atuacao                = pg_fetch_result ($res,0,segmento_atuacao);
		$posto                           = pg_fetch_result ($res,0,posto);
		$posto_nome                      = pg_fetch_result ($res,0,posto_nome);
		$sua_os                          = pg_fetch_result ($res,0,sua_os);
		$data_abertura                   = pg_fetch_result ($res,0,data_abertura);
		$data_fechamento                 = pg_fetch_result ($res,0,data_fechamento);
		$hora_abertura                   = pg_fetch_result ($res,0,hora_abertura);
		$produto_referencia              = pg_fetch_result ($res,0,referencia);
		$produto_descricao               = pg_fetch_result ($res,0,descricao);
		$produto_serie                   = pg_fetch_result ($res,0,serie);
		$qtde_produtos                   = pg_fetch_result ($res,0,qtde_produtos);
		$cliente                         = pg_fetch_result ($res,0,cliente);
		$consumidor_nome                 = pg_fetch_result ($res,0,consumidor_nome);
		$consumidor_cpf                  = pg_fetch_result ($res,0,consumidor_cpf);
		$consumidor_fone                 = pg_fetch_result ($res,0,consumidor_fone);
		$consumidor_celular              = pg_fetch_result ($res,0,consumidor_celular);//15091
		$consumidor_fone_comercial       = pg_fetch_result ($res,0,consumidor_fone_comercial);
		$consumidor_cep                  = trim (pg_fetch_result ($res,0,consumidor_cep));
		$consumidor_endereco             = trim (pg_fetch_result ($res,0,consumidor_endereco));
		$consumidor_numero               = trim (pg_fetch_result ($res,0,consumidor_numero));
		$consumidor_complemento          = trim (pg_fetch_result ($res,0,consumidor_complemento));
		$consumidor_bairro               = trim (pg_fetch_result ($res,0,consumidor_bairro));
		$consumidor_cidade               = pg_fetch_result ($res,0,consumidor_cidade);
		$consumidor_estado               = pg_fetch_result ($res,0,consumidor_estado);
		$consumidor_email                = pg_fetch_result ($res,0,consumidor_email);
		$fisica_juridica                 = pg_fetch_result ($res,0,fisica_juridica);

		$revenda                     = pg_fetch_result ($res,0,revenda);
		$revenda_cnpj                = pg_fetch_result ($res,0,revenda_cnpj);
		$revenda_nome                = pg_fetch_result ($res,0,revenda_nome);
		$nota_fiscal                 = pg_fetch_result ($res,0,nota_fiscal);
		$data_nf                     = pg_fetch_result ($res,0,data_nf);
		$aparencia_produto           = pg_fetch_result ($res,0,aparencia_produto);
		$acessorios                  = pg_fetch_result ($res,0,acessorios);
		$fabrica                     = pg_fetch_result ($res,0,fabrica);
		$posto_codigo                = pg_fetch_result ($res,0,posto_codigo);
		/*DADOS DO POSTO PARA CALCULO KM*/
		$contato_endereco             = trim (pg_fetch_result ($res,0,contato_endereco));
		$contato_numero               = trim (pg_fetch_result ($res,0,contato_numero));
		$contato_bairro               = trim (pg_fetch_result ($res,0,contato_bairro));
		$contato_cidade               = pg_fetch_result ($res,0,contato_cidade);
		$contato_estado               = pg_fetch_result ($res,0,contato_estado);
		$contato_cep                  = pg_fetch_result ($res,0,contato_cep);

		$condicao                    = pg_fetch_result ($res,0,condicao);
		$extrato                     = pg_fetch_result ($res,0,extrato);
		$quem_abriu_chamado          = pg_fetch_result ($res,0,quem_abriu_chamado);
		$obs                         = pg_fetch_result ($res,0,obs);
		$observacao_pedido           = pg_fetch_result ($res,0,observacao_pedido);
		$consumidor_revenda          = pg_fetch_result ($res,0,consumidor_revenda);
		$codigo_fabricacao           = pg_fetch_result ($res,0,codigo_fabricacao);
		$satisfacao                  = pg_fetch_result ($res,0,satisfacao);
		$laudo_tecnico               = pg_fetch_result ($res,0,laudo_tecnico);
		$troca_faturada              = pg_fetch_result ($res,0,troca_faturada);
		$troca_garantia              = pg_fetch_result ($res,0,troca_garantia);
		$admin_os                    = trim(pg_fetch_result ($res,0,admin));
		$autorizacao_cortesia        = pg_fetch_result ($res,0, autorizacao_cortesia);

		$qtde_km				= pg_fetch_result ($res,0,qtde_km);//48818
		$versao					= pg_fetch_result ($res,0,versao);
		$divisao				= pg_fetch_result ($res,0,divisao);
		$produto_capacidade		= pg_fetch_result ($res,0,produto_capacidade);
		$taxa_visita			= pg_fetch_result ($res,0,taxa_visita);
		$visita_por_km			= pg_fetch_result ($res,0,visita_por_km);
		$valor_por_km			= pg_fetch_result ($res,0,valor_por_km);
		$deslocamento_km		= pg_fetch_result ($res,0,deslocamento_km);
		$hora_tecnica			= pg_fetch_result ($res,0,hora_tecnica);
        $horas_trabalhadas  	= pg_fetch_result ($res,0,hora_tecnica);
		$regulagem_peso_padrao	= pg_fetch_result ($res,0,regulagem_peso_padrao);
		$certificado_conformidade= pg_fetch_result ($res,0,certificado_conformidade);
		$valor_diaria			= pg_fetch_result ($res,0,valor_diaria);
		$veiculo				= pg_fetch_result ($res,0,veiculo);
		$desconto_deslocamento	= pg_fetch_result ($res,0,desconto_deslocamento);
		$desconto_hora_tecnica	= pg_fetch_result ($res,0,desconto_hora_tecnica);
		$desconto_diaria		= pg_fetch_result ($res,0,desconto_diaria);
		$desconto_regulagem		= pg_fetch_result ($res,0,desconto_regulagem);
		$desconto_certificado	= pg_fetch_result ($res,0,desconto_certificado);
		$desconto_peca			= pg_fetch_result ($res,0,desconto_peca);
		$classificacao_os		= pg_fetch_result ($res,0,classificacao_os);
		$os_posto				= pg_fetch_result ($res,0,os_posto);
		$nota_fiscal_saida		= pg_fetch_result ($res,0,nota_fiscal_saida);
		$data_nf_saida			= pg_fetch_result ($res,0,data_nf_saida);

		$orientacao_sac	= pg_fetch_result ($res,0,orientacao_sac);
		$orientacao_sac = html_entity_decode ($orientacao_sac,ENT_QUOTES);
		$orientacao_sac = str_replace ("<br />","",$orientacao_sac);
		$orientacao_sac = str_replace ("|","\n",$orientacao_sac);

		$tipo_os				= pg_fetch_result ($res,0,tipo_os);

		$admin_paga_mao_de_obra = pg_fetch_result ($res,0,admin_paga_mao_de_obra);

		if ($login_fabrica == 7 AND strlen($desconto_peca)==0 AND strlen($consumidor_cpf) > 0) {
			$sql = "SELECT  tbl_posto_consumidor.contrato,
							tbl_posto_consumidor.desconto_peca
					FROM   tbl_posto_consumidor
					JOIN   tbl_posto ON tbl_posto.posto = tbl_posto_consumidor.posto AND tbl_posto_consumidor.fabrica = $login_fabrica
					WHERE  tbl_posto.cnpj = '$consumidor_cpf' ";
			$res2 = pg_query ($con,$sql); echo $sql;
			if (pg_num_rows ($res2) > 0 ) {
				$contrato      = trim(pg_fetch_result($res2,0,contrato));
				$desconto_peca = trim(pg_fetch_result($res2,0,desconto_peca));

				if ($contrato != 't'){
					$desconto_peca = "0";
				}
			}
		}

		if ($consumidor_revenda == 'R'){
			$sql = "SELECT os_manutencao
					FROM tbl_os
					LEFT JOIN tbl_os_revenda ON tbl_os_revenda.os_revenda = tbl_os.os_numero
											AND tbl_os_revenda.posto = tbl_os.posto
					WHERE  tbl_os.os = $os
					AND    tbl_os.fabrica = $login_fabrica
					";
			$resRevenda = pg_query ($con,$sql); echo $sql;
			if (pg_num_rows ($resRevenda) > 0 ) {
				$os_manutencao = pg_fetch_result ($resRevenda,0,os_manutencao);
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
					";

			$res2 = pg_query ($con,$sql); echo $sql;
			if (pg_num_rows ($res2) > 0 ) {

				$valor_por_km_caminhao    = trim(pg_fetch_result($res2,0,valor_por_km));
				$valor_por_km_carro       = trim(pg_fetch_result($res2,0,valor_por_km));
				$valor_por_km             = trim(pg_fetch_result($res2,0,valor_por_km));
				$deslocamento_km          = trim(pg_fetch_result($res2,0,deslocamento_km));
				$veiculo                  = trim(pg_fetch_result($res2,0,veiculo));
				$taxa_visita              = trim(pg_fetch_result($res2,0,taxa_visita));
				$hora_tecnica             = trim(pg_fetch_result($res2,0,hora_tecnica));
				$valor_diaria             = trim(pg_fetch_result($res2,0,valor_diaria));

				$regulagem_peso_padrao    = trim(pg_fetch_result($res2,0,regulagem_peso_padrao));

				$desconto_deslocamento	= pg_fetch_result ($res2,0,desconto_deslocamento);
				$desconto_hora_tecnica	= pg_fetch_result ($res2,0,desconto_hora_tecnica);
				$desconto_diaria		= pg_fetch_result ($res2,0,desconto_diaria);
				#$desconto_regulagem	= pg_fetch_result ($res2,0,desconto_regulagem);
			}
		}

		if ($regulagem_peso_padrao > 0){
			$cobrar_regulagem = 't';
		}

		if ($certificado_conformidade > 0){
			$cobrar_certificado = 't';
		}

		if ($valor_diaria == 0 AND $hora_tecnica == 0){
			$cobrar_hora_diaria = "isento";
		}
		if ($valor_diaria > 0 AND $hora_tecnica == 0){
			$cobrar_hora_diaria = "diaria";
		}
		if ($valor_diaria == 0 AND $hora_tecnica > 0){
			$cobrar_hora_diaria = "hora";
		}

		if ($valor_por_km == 0 AND $taxa_visita == 0){
			$cobrar_deslocamento = "isento";
		}
		if ($valor_por_km > 0 AND $taxa_visita == 0){
			$cobrar_deslocamento = "valor_por_km";
		}
		if ($valor_por_km == 0 AND $taxa_visita > 0){
			$cobrar_deslocamento = "taxa_visita";
		}

		//HD 12606
		$defeito_reclamado_descricao = pg_fetch_result($res,0,defeito_reclamado_descricao);
		#if($login_fabrica==11 or $login_fabrica==19 or $login_fabrica==3) HD 242946
		$defeito_reclamado = pg_fetch_result($res,0,defeito_reclamado);

		$sql =	"SELECT tbl_os_produto.produto ,
						tbl_os_item.pedido
				FROM    tbl_os
				JOIN    tbl_produto using (produto)
				JOIN    tbl_posto using (posto)
				JOIN    tbl_fabrica using (fabrica)
				JOIN    tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto
										  AND tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
				JOIN    tbl_os_produto USING (os)
				JOIN    tbl_os_item
				ON      tbl_os_item.os_produto = tbl_os_produto.os_produto
				WHERE   tbl_os.os = $os
				AND     tbl_os.fabrica = $login_fabrica";
		$res = pg_query ($con,$sql); echo $sql;

		if(pg_num_rows($res) > 0){
			$produto = pg_fetch_result($res,0,produto);
			$pedido  = pg_fetch_result($res,0,pedido);
		}

		//SELECIONA OS DADOS DO CLIENTE PRA JOGAR NA OS
		if (strlen($consumidor_cidade)==0){
			if (strlen($cpf) > 0 OR strlen($cliente) > 0 ) {
				$sql = "SELECT
							tbl_cliente.cliente,
							tbl_cliente.nome,
							tbl_cliente.endereco,
							tbl_cliente.numero,
							tbl_cliente.complemento,
							tbl_cliente.bairro,
							tbl_cliente.cep,
							tbl_cliente.rg,
							tbl_cliente.fone,
							tbl_cliente.contrato,
							tbl_cidade.nome AS cidade,
							tbl_cidade.estado
						FROM tbl_cliente
						LEFT JOIN tbl_cidade USING (cidade)
						WHERE 1 = 1";
				if (strlen($cpf) > 0) $sql .= " AND tbl_cliente.cpf = '$cpf'";
				if (strlen($cliente) > 0) $sql .= " AND tbl_cliente.cliente = '$cliente'";

				$res = pg_query ($con,$sql); echo $sql;
				if (pg_num_rows ($res) == 1) {
					$consumidor_cliente		= trim (pg_fetch_result ($res,0,cliente));
					$consumidor_fone		= trim (pg_fetch_result ($res,0,fone));
					$consumidor_nome		= trim (pg_fetch_result ($res,0,nome));
					$consumidor_endereco	= trim (pg_fetch_result ($res,0,endereco));
					$consumidor_numero		= trim (pg_fetch_result ($res,0,numero));
					$consumidor_complemento	= trim (pg_fetch_result ($res,0,complemento));
					$consumidor_bairro		= trim (pg_fetch_result ($res,0,bairro));
					$consumidor_cep			= trim (pg_fetch_result ($res,0,cep));
					$consumidor_rg			= trim (pg_fetch_result ($res,0,rg));
					$consumidor_cidade		= trim (pg_fetch_result ($res,0,cidade));
					$consumidor_estado		= trim (pg_fetch_result ($res,0,estado));
					$consumidor_contrato	= trim (pg_fetch_result ($res,0,contrato));
				}
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
			$res = pg_query ($con,$sql); echo $sql;
			if (pg_num_rows($res) > 0) {

				if ($cobrar_deslocamento  == 'taxa_visita'){
					$valor_por_km_caminhao    = trim(pg_fetch_result($res,0,valor_por_km_caminhao));
					$valor_por_km_carro       = trim(pg_fetch_result($res,0,valor_por_km_carro));
				}

				if ($cobrar_deslocamento  == 'valor_por_km'){
					$taxa_visita                  = trim(pg_fetch_result($res,0,taxa_visita));
					if ($veiculo == 'carro'){
						$valor_por_km_caminhao    = trim(pg_fetch_result($res,0,valor_por_km_caminhao));
						$valor_por_km_carro       = $valor_por_km;
					}
					if ($veiculo == 'caminhao'){
						$valor_por_km_carro       = trim(pg_fetch_result($res,0,valor_por_km_carro));
						$valor_por_km_caminhao    = $valor_por_km;
					}
				}

				if ($cobrar_hora_diaria == "diaria"){
					$hora_tecnica             = trim(pg_fetch_result($res,0,hora_tecnica));
				}
				if ($cobrar_hora_diaria == "hora"){
					$valor_diaria             = trim(pg_fetch_result($res,0,valor_diaria));
				}
				if ($cobrar_regulagem != "t"){
					$regulagem_peso_padrao    = trim(pg_fetch_result($res,0,regulagem_peso_padrao));
				}
				if ($cobrar_certificado != "t"){
					$certificado_conformidade = trim(pg_fetch_result($res,0,certificado_conformidade));
				}
			}

			/* HD 46784 */
			$sql = "SELECT  valor_regulagem, valor_certificado
					FROM    tbl_capacidade_valores
					WHERE   fabrica = $login_fabrica
					AND     capacidade_de <= (SELECT capacidade FROM tbl_os WHERE tbl_os.os = $os AND fabrica = $login_fabrica )
					AND     capacidade_ate >= (SELECT capacidade FROM tbl_os WHERE tbl_os.os = $os AND fabrica = $login_fabrica )";
			$res = pg_query ($con,$sql); echo $sql;
			if (pg_num_rows($res) > 0) {
				if ($cobrar_regulagem != "t"){
					$regulagem_peso_padrao    = trim(pg_fetch_result($res,0,valor_regulagem));
				}
				if ($cobrar_certificado != "t"){
					$certificado_conformidade = trim(pg_fetch_result($res,0,valor_certificado));
				}
			}
		}
	}
}


/*============= RECARREGA FORM EM CASO DE ERRO ==================*/

if (strlen($msg_erro) > 0 and $btn_troca <> "trocar") {
	$os                 = $_POST['os'];
	$tipo_atendimento   = $_POST['tipo_atendimento'];
	$segmento_atuacao   = $_POST['segmento_atuacao'];
	$sua_os             = $_POST['sua_os'];
	$data_abertura      = $_POST['data_abertura'];
	$hora_abertura      = $_POST['hora_abertura'];
	$cliente            = $_POST['cliente'];
	$consumidor_nome    = $_POST['consumidor_nome'];
	$consumidor_cpf     = $_POST['consumidor_cpf'];
	$consumidor_fone    = $_POST['consumidor_fone'];
	$consumidor_celular = $_POST['consumidor_celular'];
	$consumidor_fone_comercial = $_POST['consumidor_fone_comercial'];
	$consumidor_email   = $_POST['consumidor_email'];
	$fisica_juridica    = $_POST['fisica_juridica'];

	$revenda            = $_POST['revenda'];
	$revenda_cnpj       = $_POST['revenda_cnpj'];
	$revenda_nome       = $_POST['revenda_nome'];
	$nota_fiscal        = $_POST['nota_fiscal'];
	$data_nf            = $_POST['data_nf'];
	$produto_referencia = $_POST['produto_referencia'];
	$cor                = $_POST['cor'];
	$acessorios         = $_POST['acessorios'];
	$aparencia_produto  = $_POST['aparencia_produto'];
	$obs                = $_POST['obs'];
	$observacao_pedido  = $_POST['observacao_pedido'];
	$orientacao_sac     = $_POST['orientacao_sac'];
	$consumidor_revenda = $_POST['consumidor_revenda'];
	$qtde_produtos      = $_POST['qtde_produtos'];
	$produto_serie      = $_POST['produto_serie'];
	$autorizacao_cortesia = $_POST['autorizacao_cortesia'];

	$codigo_fabricacao  = $_POST['codigo_fabricacao'];
	$satisfacao         = $_POST['satisfacao'];
	$laudo_tecnico      = $_POST['laudo_tecnico'];
	$troca_faturada     = $_POST['troca_faturada'];

	$quem_abriu_chamado       = $_POST['quem_abriu_chamado'];
	$taxa_visita              = $_POST['taxa_visita'];
	$visita_por_km            = $_POST['visita_por_km'];
	$deslocamento_km          = $_POST['deslocamento_km'];
	$hora_tecnica             = $_POST['hora_tecnica'];
	$regulagem_peso_padrao    = $_POST['regulagem_peso_padrao'];
	$certificado_conformidade = $_POST['certificado_conformidade'];
	$valor_diaria             = $_POST['valor_diaria'];

	 $sql =	"SELECT descricao
			FROM    tbl_produto
			JOIN    tbl_linha USING (linha)
			WHERE   tbl_produto.referencia = UPPER ('$produto_referencia')
			AND     tbl_linha.fabrica      = $login_fabrica
			AND     tbl_produto.ativo IS TRUE";
	$res = pg_query ($con,$sql); echo $sql;
	$produto_descricao = @pg_fetch_result ($res,0,0);
}

if ($orientacao_sac == "null") $orientacao_sac = "";
$body_onload = "javascript: document.frm_os.sua_os.focus()";

/* PASSA PAR?METRO PARA O CABE?ALHO (n?o esquecer ===========*/

/* $title = Aparece no sub-menu e no t?tulo do Browser ===== */
$title = "CADASTRO DE ORDEM DE SERVIÇO - ADMIN";

/* $layout_menu = Determina a aba em destaque do MENU ===== */
if($login_fabrica <> 108 and $login_fabrica <> 111){
$layout_menu = 'callcenter';
} else {
$layout_menu = 'gerencia';
}
include "cabecalho.php";

?>

<!--=============== <FUN??ES> ================================!-->


<? include "javascript_pesquisas.php" ?>

<!--========================= AJAX==================================.-->
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>
<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_cep.js'></script>
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<script type="text/javascript" src="../js/jquery.corner.js"></script>
<script type="text/javascript" src="../js/jquery.maskedinput.js"></script>
<script type="text/javascript" src="js/jquery.maskmoney.js"></script>

<script language="JavaScript">

function mascara(o,f){
    v_obj=o
    v_fun=f
    setTimeout("execmascara()",1)
}

function execmascara(){
    v_obj.value=v_fun(v_obj.value)
}

function soNumeros(campo){
    return campo.replace(/\D/g,"")
}

// valida numero de serie
function mostraEsconde(){
	$("div[@rel=div_ajuda]").toggle();
}

function createRequestObject(){
	var request_;
	var browser = navigator.appName;
	if(browser == "Microsoft Internet Explorer"){
		 request_ = new ActiveXObject("Microsoft.XMLHTTP");
	}else{
		 request_ = new XMLHttpRequest();
	}
	return request_;
}


var http4 = new Array();
function fn_verifica_garantia(){
	var produto_descricao  = document.getElementById('produto_descricao').value;
	var produto_referencia = document.getElementById('produto_referencia').value;
	var serie              = document.getElementById('produto_serie').value;
	var campo              = document.getElementById('div_estendida');
	var curDateTime = new Date();
	http4[curDateTime] = createRequestObject();

	url = "callcenter_interativo_ajax.php?ajax=true&origem=os_cadastro&garantia=tue&produto_nome=" + produto_descricao + "&produto_referencia=" + produto_referencia+"&serie="+serie+"&data="+curDateTime;
	http4[curDateTime].open('get',url);

	http4[curDateTime].onreadystatechange = function(){
		if(http4[curDateTime].readyState == 1) {
			campo.innerHTML = "<img src='imagens_admin/carregando_callcenter.gif' border='0'><font size='1' face='verdana'>Aguarde..</font>";
		}
		if (http4[curDateTime].readyState == 4){
			if (http4[curDateTime].status == 200 || http4[curDateTime].status == 304){
				//**
				alert(http4[curDateTime].responseText);
				var results = http4[curDateTime].responseText;
				campo.innerHTML   = results;
			}else {
				campo.innerHTML = "Erro";

			}
		}
	}
	http4[curDateTime].send(null);
}


var http_forn = new Array();

function verifica_atendimento(tipo_atendimento) {

	/*Verificacao para existencia de componente - HD 22891 */
	if (document.getElementById('div_mapa')){
		var ref = document.getElementById(tipo_atendimento).value;
		url = "<?=$PHP_SELF?>?ajax=tipo_atendimento&id="+ref;
		var curDateTime = new Date();
		http_forn[curDateTime] = createRequestObject();
		http_forn[curDateTime].open('GET',url,true);
		http_forn[curDateTime].onreadystatechange = function(){
			if (http_forn[curDateTime].readyState == 4)
			{
				if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304)
				{
					var response = http_forn[curDateTime].responseText.split("|");
					if (response[0]=="ok"){
						document.getElementById('div_mapa').style.visibility = "visible";
						document.getElementById('div_mapa').style.position = 'static';
					}else{
						document.getElementById('div_mapa').style.visibility = "hidden";
						document.getElementById('div_mapa').style.position = 'absolute';
					}
				}
			}
		}
		http_forn[curDateTime].send(null);
	}
}






//------------------------------

function VerificaSuaOS (sua_os){
	if (sua_os.value != "") {
		janela = window.open("pesquisa_sua_os.php?sua_os=" + sua_os.value,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=250,top=50,left=10");
		janela.focus();
	}
}
function fazer_troca(){

	<?php if ($login_fabrica == 81){?>


		var produto_atual;
		var produto_novo;

		produto_atual = $('#produto_os_troca_atual').val();
		produto_novo  = $('#troca_garantia_produto').val();

		if (produto_atual != produto_novo ){

			if (confirm ('O produto da troca é diferente do produto da OS, deseja continuar?')) {

				if (document.getElementById('gerar_pedido')){

					gerar_pedido = document.getElementById('gerar_pedido');
					if(gerar_pedido.checked) alert('Esta troca irá gerar pedido!');
					else                     alert('Esta troca NÃO irá gerar pedido!');

				}

				if (confirm ('Confirma Troca?')) {
					document.frm_troca.btn_troca.value='trocar';
					if (document.frm_troca.orient_sac !=""){
						document.frm_troca.orient_sac.value = document.frm_os.orientacao_sac.value;
					}

					document.frm_troca.submit();

				}

			}else{

				$('#troca_garantia_produto').val("");
				$('#marca_troca').val("");
				$('#familia_troca').val("");
				$('html, body').animate( { scrollTop: 0 }, 'fast');

			}

		}else{

			if (document.getElementById('gerar_pedido')){

					gerar_pedido = document.getElementById('gerar_pedido');
					if(gerar_pedido.checked) alert('Esta troca irá gerar pedido!');
					else                     alert('Esta troca NÃO irá gerar pedido!');

			}

			if (confirm ('Confirma Troca?')) {
				document.frm_troca.btn_troca.value='trocar';
				if (document.frm_troca.orient_sac !=""){
					document.frm_troca.orient_sac.value = document.frm_os.orientacao_sac.value;
				}

				document.frm_troca.submit();

			}

		}



	<?php }else{?>

	if (document.getElementById('gerar_pedido')){

		gerar_pedido = document.getElementById('gerar_pedido');
		if(gerar_pedido.checked) alert('Esta troca irá gerar pedido!');
		else                     alert('Esta troca NÃO irá gerar pedido!');

	}

	if (confirm ('Confirma Troca?')) {
		document.frm_troca.btn_troca.value='trocar';
		if (document.frm_troca.orient_sac !=""){
			document.frm_troca.orient_sac.value = document.frm_os.orientacao_sac.value;
		}

		document.frm_troca.submit();

	}

	<?php }?>
}

function cancelar_os() {

	if (confirm ('Cancelar esta OS?')) {
		document.frm_cancelar.cancelar.value = 'cancelar';
		document.frm_cancelar.submit();
	}

}

// ========= Funcao PESQUISA DE POSTO POR C?DIGO OU NOME ========= //
function fnc_pesquisa_posto2 (campo, campo2, tipo) {

	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2<?=$pp_suffix?>.php?campo=" + xcampo.value + "&tipo=" + tipo + "&proximo=t" + "&os=t";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;

		if ("<? echo $pedir_sua_os; ?>" == "t") {
			janela.proximo = document.frm_os.sua_os;
		}else{
			janela.proximo = document.frm_os.data_abertura;
		}
		janela.focus();

		/* janela.onbeforeunload = function(){
			$("#posto_nome").trigger("focus");
			$("#posto_codigo").trigger("focus");
		} */

		$(janela).attr('onunload', function(){
			$("#posto_nome").trigger("focus");
			$("#posto_codigo").trigger("focus");
		});


	}else{
		alert("Informar toda ou parte da informação para realizar a pesquisa!");
	}
}

function fnc_pesquisa_serie(campo) {//HD 256659

	if (campo.value.length == 15) {

		var url = "produto_serie_pesquisa_britania.php?serie=" + campo.value;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");

		janela.serie      = campo;
		janela.referencia = document.frm_os.produto_referencia;
		janela.descricao  = document.frm_os.produto_descricao;
		janela.focus();

	} else {

		alert("O número de serie deve conter 15 caracteres!");

	}

}

// ========= FUNCAO PESQUISA DE POSTO POR CODIGO OU NOME ========= //
function fnc_pesquisa_posto_km (campo, campo2, tipo) {

	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_km<?=$pk_suffix?>.php?campo=" + xcampo.value + "&tipo=" + tipo + "&proximo=t";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;

		janela.contato_endereco= document.frm_os.contato_endereco;


		janela.contato_numero  = document.frm_os.contato_numero  ;
		janela.contato_bairro  = document.frm_os.contato_bairro  ;
		janela.contato_cidade  = document.frm_os.contato_cidade  ;
		janela.contato_estado  = document.frm_os.contato_estado  ;
		janela.contato_cep     = document.frm_os.contato_cep     ;

		if ("<? echo $pedir_sua_os; ?>" == "t") {
			janela.proximo = document.frm_os.sua_os;
		}else{
			janela.proximo = document.frm_os.data_abertura;
		}
		janela.focus();
	}

	else{
		alert("Informar toda ou parte da informação para realizar a pesquisa!");
	}
}


// ========= Função PESQUISA DE PRODUTO POR REFERÊNCIA OU DESCRIÇÃO ========= //

function fnc_pesquisa_produto2 (campo, campo2, tipo, voltagem) {

	var campo3 = '';

	if (tipo == "referencia" ) {
		var xcampo = campo;
		if (document.frm_os.produto_descricao) {
			campo3 = document.frm_os.produto_descricao;
		}
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
		if (document.frm_os.produto_serie) {
			campo3 = document.frm_os.produto_serie;
		}
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2<?=$pr_suffix?>.php?campo=" + xcampo.value + "&tipo=" + tipo + "&proximo=t";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.referencia   = campo;
		janela.descricao    = campo2;
		janela.proximo      = campo3;
		if (voltagem != "") {
			janela.voltagem = voltagem;
		}
		janela.focus();
	}

	else{
		alert("Informar toda ou parte da informação para realizar a pesquisa!");
	}

}


function busca_atendimento_produto_familia() {
    var produto_referencia = jQuery.trim($("#produto_referencia").val());
    //var total_input = $("#tipo_atendimento option").size();

    if(produto_referencia.length > 0){
        $("#tipo_atendimento").html('<option value="0"> Aguarde</option>');
        $.ajax({
            url : 'ajax_os_cadastro_unico.php',
            type : "POST",
            data : "tipo=atendimento_pela_familia_produto&produto_referencia=" + produto_referencia,
            success : function(retorno) {
                $("#tipo_atendimento").html(retorno);
                return false;
            }
        });
    }
}

// ========= Função PESQUISA DE CONSUMIDOR POR NOME OU CPF ========= //

function fnc_pesquisa_consumidor (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_consumidor<?=$pc_suffix?>.php?nome=" + campo.value + "&tipo=nome&proximo=t";
	}
	if (tipo == "cpf") {
		url = "pesquisa_consumidor<?=$pc_suffix?>.php?cpf=" + campo.value + "&tipo=cpf&proximo=t";
	}
	if (campo.value != "") {
		if (campo.value.length >= 3) {
			janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
			janela.cliente		= document.frm_os.consumidor_cliente;
			janela.nome			= document.frm_os.consumidor_nome;
			janela.cpf			= document.frm_os.consumidor_cpf;
			janela.rg			= document.frm_os.consumidor_rg;
			janela.cidade		= document.frm_os.consumidor_cidade;
			janela.estado		= document.frm_os.consumidor_estado;
			janela.fone			= document.frm_os.consumidor_fone;
			janela.endereco		= document.frm_os.consumidor_endereco;
			janela.numero		= document.frm_os.consumidor_numero;
			janela.complemento	= document.frm_os.consumidor_complemento;
			janela.bairro		= document.frm_os.consumidor_bairro;
			janela.cep			= document.frm_os.consumidor_cep;
			janela.proximo		= document.frm_os.revenda_nome;
			janela.focus();
		}else{
			alert("Digite pelo menos 3 caracteres para efetuar a pesquisa!");
		}
	}
	else{
			alert("Digite pelo menos 3 caracteres para efetuar a pesquisa!");
		}


	$(janela).attr('onunload', function(){
		$("#consumidor_nome").trigger("focus");
		$("#consumidor_cpf").trigger("focus");
	});
}

// ========= Função PESQUISA DE REVENDA POR NOME OU CNPJ ========= //

function fnc_pesquisa_revenda(campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		<?php if($login_fabrica != 6){?>
			url = "pesquisa_revenda<?=$rv_suffix?>.php?nome=" + campo.value + "&tipo=nome&proximo=t";
		<?php }else{?>
			url = "pesquisa_revenda<?=$rv_suffix?>.php?nome=" + campo.value + "&tipo=nome";
		<? }?>
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda<?=$rv_suffix?>.php?cnpj=" + campo.value + "&tipo=cnpj&proximo=t";
	}
	if (campo.value != "") {
		if (campo.value.length >= 3) {
			janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
			janela.nome			= document.frm_os.revenda_nome;
			janela.cnpj			= document.frm_os.revenda_cnpj;
			janela.fone			= document.frm_os.revenda_fone;
			janela.cidade		= document.frm_os.revenda_cidade;
			janela.estado		= document.frm_os.revenda_estado;
			janela.endereco		= document.frm_os.revenda_endereco;
			janela.numero		= document.frm_os.revenda_numero;
			janela.complemento	= document.frm_os.revenda_complemento;
			janela.bairro		= document.frm_os.revenda_bairro;
			janela.cep			= document.frm_os.revenda_cep;
			janela.email		= document.frm_os.revenda_email;
			janela.proximo		= document.frm_os.nota_fiscal;
			janela.focus();
		}else{
			alert("Digite pelo menos 3 caracteres para efetuar a pesquisa!");
		}
	}else{
		alert("Digite pelo menos 3 caracteres para efetuar a pesquisa!");
	}


	$(janela).attr('onunload', function(){
		$("#revenda_nome").trigger("focus");
		$("#revenda_cnpj").trigger("focus");
	});
}

/* ============= Fun??o FORMATA CNPJ =============================
Nome da Fun??o : formata_cnpj (cnpj, form)
		Formata o Campo de CNPJ a medida que ocorre a digita??o
		Par?m.: cnpj (numero), form (nome do form)
=================================================================*/
function formata_cnpj(cnpj, form){
	var mycnpj = '';
		mycnpj = mycnpj + cnpj;
		myrecord = "revenda_cnpj";
		myform = form;

		if (mycnpj.length == 2){
			mycnpj = mycnpj + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 6){
			mycnpj = mycnpj + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 10){
			mycnpj = mycnpj + '/';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 15){
			mycnpj = mycnpj + '-';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
}


/* ============= Fun??o FORMATA CPF =============================
Nome da Fun??o : formata_cpf (cpf, form)
		Formata o Campo de CPF a medida que ocorre a digita??o
		Par?m.: cpf (numero), form (nome do form)
=================================================================*/
function formata_cpf(cpf, form){
	var mycpf = '';
		mycpf = mycpf + cpf;
		myrecord = "consumidor_cpf";
		myform = form;

		if (mycpf.length == 3){
			mycpf = mycpf + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycpf;
		}
		if (mycpf.length == 7){
			mycpf = mycpf + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycpf;
		}
		if (mycpf.length == 11){
			mycpf = mycpf + '-';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycpf;
		}
}



/* ========== Fun??o AJUSTA CAMPO DE DATAS =========================
Nome da Fun??o : ajustar_data (input, evento)
		Ajusta a formata??o da M?scara de DATAS a medida que ocorre
		a digita??o do texto.
=================================================================*/
function ajustar_data(input , evento)
{
	var BACKSPACE=  8;
	var DEL=  46;
	var FRENTE=  39;
	var TRAS=  37;
	var key;
	var tecla;
	var strValidos = "0123456789" ;
	var temp;
	tecla= (evento.keyCode ? evento.keyCode: evento.which ? evento.which : evento.charCode)

	if (( tecla == BACKSPACE )||(tecla == DEL)||(tecla == FRENTE)||(tecla == TRAS)) {
		return true;
			}
		if ( tecla == 13) return false;
		if ((tecla<48)||(tecla>57)){
			return false;
			}
		key = String.fromCharCode(tecla);
		input.value = input.value+key;
		temp="";
		for (var i = 0; i<input.value.length;i++ )
			{
				if (temp.length==2) temp=temp+"/";
				if (temp.length==5) temp=temp+"/";
				if ( strValidos.indexOf( input.value.substr(i,1) ) != -1 ) {
					temp=temp+input.value.substr(i,1);
			}
			}
					input.value = temp.substr(0,10);
				return false;
}

function fnc_num_serie_confirma(valor) {

	if(valor  =='sim'){
		document.getElementById('revenda_nome').readOnly =false;
		document.getElementById('revenda_cnpj').readOnly =true;
		document.getElementById('revenda_fixo').style.display='none';
		document.getElementById('revenda_fone').readOnly =true;
		document.getElementById('revenda_cidade').readOnly =true;
		document.getElementById('revenda_estado').readOnly =true;
		document.getElementById('revenda_endereco').readOnly =true;
		document.getElementById('revenda_numero').readOnly =true;
		document.getElementById('revenda_complemento').readOnly =true;
		document.getElementById('revenda_bairro').readOnly =true;
		document.getElementById('revenda_cep').readOnly =true;
	}else{
		document.getElementById('revenda_nome').readOnly =false;
		document.getElementById('revenda_cnpj').readOnly =false;
		document.getElementById('revenda_nome').value='';
		document.getElementById('revenda_cnpj').value='';
		document.getElementById('revenda_fixo').style.display='block';
		document.getElementById('revenda_fone').readOnly =false;
		document.getElementById('revenda_cidade').readOnly =false;
		document.getElementById('revenda_estado').readOnly =false;
		document.getElementById('revenda_endereco').readOnly =false;
		document.getElementById('revenda_numero').readOnly =false;
		document.getElementById('revenda_complemento').readOnly =false;
		document.getElementById('revenda_bairro').readOnly =false;
		document.getElementById('revenda_cep').readOnly =false;
		document.getElementById('revenda_fone').value='';
		document.getElementById('revenda_cidade').value='';
		document.getElementById('revenda_estado').value='';
		document.getElementById('revenda_endereco').value='';
		document.getElementById('revenda_numero').value='';
		document.getElementById('revenda_complemento').value='';
		document.getElementById('revenda_bairro').value='';
		document.getElementById('revenda_cep').value='';
	}
}

function fnc_pesquisa_numero_serie (campo, tipo) {
	var url = "";
	var revenda_fixo_url = "";

	if (document.getElementById('revenda_fixo')){
		revenda_fixo_url = "&revenda_fixo=1"
	}

	if (tipo == "produto_serie") {
		url = "pesquisa_numero_serie<?=$ns_suffix?>.php?produto_serie=" + campo.value + "&tipo=produto_serie"+revenda_fixo_url;
	}
	if (tipo == "cnpj") {
		url = "pesquisa_numero_serie<?=$ns_suffix?>.php?cnpj=" + campo.value + "&tipo=cnpj"+revenda_fixo_url;
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=650,height=400,top=18,left=0");
	janela.nome			= document.frm_os.revenda_nome;
	janela.cnpj			= document.frm_os.revenda_cnpj;
	janela.fone			= document.frm_os.revenda_fone;
	janela.cidade		= document.frm_os.revenda_cidade;
	janela.estado		= document.frm_os.revenda_estado;
	janela.endereco		= document.frm_os.revenda_endereco;
	janela.numero		= document.frm_os.revenda_numero;
	janela.complemento	= document.frm_os.revenda_complemento;
	janela.bairro		= document.frm_os.revenda_bairro;
	janela.cep			= document.frm_os.revenda_cep;
	janela.email		= document.frm_os.revenda_email;

	janela.txt_nome			= document.frm_os.txt_revenda_nome;
	janela.txt_cnpj			= document.frm_os.txt_revenda_cnpj;
	janela.txt_fone			= document.frm_os.txt_revenda_fone;
	janela.txt_cidade		= document.frm_os.txt_revenda_cidade;
	janela.txt_estado		= document.frm_os.txt_revenda_estado;
	janela.txt_endereco		= document.frm_os.txt_revenda_endereco;
	janela.txt_numero		= document.frm_os.txt_revenda_numero;
	janela.txt_complemento	= document.frm_os.txt_revenda_complemento;
	janela.txt_bairro		= document.frm_os.txt_revenda_bairro;
	janela.txt_cep			= document.frm_os.txt_revenda_cep;

	janela.txt_data_venda	= document.frm_os.txt_data_venda;
	if (document.getElementById('revenda_fixo')){
		janela.revenda_fixo		= document.getElementById('revenda_fixo');
	}

	//PRODUTO
	janela.produto_referencia = document.frm_os.produto_referencia;
	janela.produto_descricao  = document.frm_os.produto_descricao;
	janela.produto_voltagem	  = document.frm_os.produto_voltagem;
	janela.data_fabricacao	  = document.frm_os.data_fabricacao;
	janela.focus();
}

function peganome(valor){
	<?
		$xmarca = $aux_marca;
	?>
}

//HD 172561 - Usar tbl_os.defeito_reclamado e tbl_os.defeito_reclamado_descricao para Fabrica 3 e Linha 528
function mostraDefeitoDescricao(fabrica) {
	var referencia = document.frm_os.produto_referencia.value;
	var td = document.getElementById('td_defeito_reclamado_descricao');
	td.style.display = 'none';

	url = "os_cadastro_ajax.php?acao=sql&sql=SELECT tbl_linha.linha FROM tbl_produto JOIN tbl_linha ON tbl_produto.linha=tbl_linha.linha WHERE tbl_linha.fabrica="+fabrica+" AND tbl_produto.referencia='" + referencia + "'";

	requisicaoHTTP("GET", url, true, "trataDefeitoDescricao", fabrica);
}

//HD 172561 - Usar tbl_os.defeito_reclamado e tbl_os.defeito_reclamado_descricao para Fabrica 3 e Linha 528
function trataDefeitoDescricao(retorno, fabrica) {
	if (retorno == "528") {
		var td = document.getElementById('td_defeito_reclamado_descricao');
		td.style.display = 'block';
	}
	else {
		td.style.display = 'none';
	}
}

</script>

<script language='javascript' >


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


	var http5 = new Array();
	var http6 = new Array();

	function busca_valores(){
		referencia   = $("input[@name='produto_referencia']").val();

		if (referencia.length > 0) {
			var curDateTime = new Date();
			http5[curDateTime] = createRequestObject();
			url = "<?=$PHP_SELF?>?ajax=true&buscaValores=true&produto_referencia="+referencia+'&data='+curDateTime;
			http5[curDateTime].open('get',url);

			http5[curDateTime].onreadystatechange = function(){
				if (http5[curDateTime].readyState == 4){
					if (http5[curDateTime].status == 200 || http5[curDateTime].status == 304){
						var results = http5[curDateTime].responseText.split("|");

						if (results[0] == 'ok') {
							$('input[name=taxa_visita]').val(results[1]);
							$('#taxa_visita').html(results[1]);
							$('input[name=hora_tecnica]').val(results[2]);
							$('#hora_tecnica').html(results[2]);
							$('input[name=valor_diaria]').val(results[3]);
							$('#valor_diaria').html(results[3]);
							$('input[name=valor_por_km_carro]').val(results[4]);
							$('#valor_por_km_carro').html('R$ '+results[4]);
							$('input[name=valor_por_km_caminhao]').val(results[5]);
							$('#valor_por_km_caminhao').html('R$ '+results[5]);
							$('input[name=regulagem_peso_padrao]').val(results[6]);
							$('#regulagem_peso_padrao').html(results[6]);
							$('input[name=certificado_conformidade]').val(results[7]);
							$('#certificado_conformidade').html(results[7]);

							$('input[name=veiculo]').each(function (){
								if (this.checked){
									atualizaValorKM(this);
								}
							});
						}
					}
				}
			}
			http5[curDateTime].send(null);
		}
	}

	$(document).ready(function(){
		$("input[name=nota_fiscal]").numeric({allow:"CBWcbw-"});
		$("input[name=nota_fiscal_saida]").numeric({allow:"-"});
		$("#data_abertura").maskedinput("99/99/9999");
		$("#data_conserto").maskedinput("99/99/9999");
		$("#data_postagem").maskedinput("99/99/9999");
		$("#data_nf").maskedinput("99/99/9999");
		$("#data_nf_saida").maskedinput("99/99/9999");
		$("#data_fabricacao").maskedinput("99/99/9999");
		$("#consumidor_cep").maskedinput("99.999-999");
		$("#hora").maskedinput("99:99");
		$("#consumidor_fone").maskedinput("(99) 9999-9999");
		$("#txt_revenda_fone").maskedinput("(99) 9999-9999");
		$("#consumidor_fone_comercial").maskedinput("(99) 9999-9999");
		$("#consumidor_celular").maskedinput("(99) 9999-9999");
		$(".content").corner("dog 10px");
		$("#consumidor_cpf").numeric();
		$("#revenda_cnpj").numeric();
		$(".money").numeric();
		$(".money").maskMoney({symbol:"", decimal:".", thousands:'', precision:2, maxlength:15});

		$('input[name=troca_com_nota]').change(function () {
			var mostrar = ($('input[name=troca_com_nota]:checked').val()=='sem_nota_com_troca')?'block':'none';
			$('#id_justificativanf').css('display', mostrar);
		});
		$('#id_justificativanf').css('display', ($('input[name=troca_com_nota]:checked').val()=='sem_nota_com_troca')?'block':'none');

	});

function somenteNumeros(e)
{
        var tecla=new Number();
        if(window.event) {
                tecla = e.keyCode;
        }
        else if(e.which) {
                tecla = e.which;
        }
        else {
                return true;
        }
        if((tecla >= "97") && (tecla <= "122")){
                return false;
        }
}



	function verificaProduto(produto,serie){
		referencia   = produto.value;
		numero_serie = serie.value;

		if (referencia.length > 0 || numero_serie.length > 0) {
			var curDateTime = new Date();
			http6[curDateTime] = createRequestObject();
			url = "<?=$PHP_SELF?>?ajax=true&buscaInformacoes=true&produto_referencia="+referencia+"&serie="+numero_serie+'&data='+curDateTime;
			http6[curDateTime].open('get',url);

			http6[curDateTime].onreadystatechange = function(){
				if (http6[curDateTime].readyState == 4){
					if (http6[curDateTime].status == 200 || http4[curDateTime].status == 304){
						var results = http6[curDateTime].responseText.split("|");
						if (results[0] == 'ok') {
							if (document.getElementById('produto_capacidade')){
								document.getElementById('produto_capacidade').value = results[1];
							}
							if (document.getElementById('divisao')){
								document.getElementById('divisao').value            = results[2];
							}
							if (document.getElementById('versao')){
								document.getElementById('versao').value             = results[3];
							}
						}else{
							if (document.getElementById('produto_capacidade')){
								document.getElementById('produto_capacidade').value='';
							}
							if (document.getElementById('divisao')){
								document.getElementById('divisao').value='';
							}
							if (document.getElementById('versao')){
								document.getElementById('versao').value='';
							}
						}
					}
				}
			}
			http6[curDateTime].send(null);
		}
	}

	function createRequestObject(){
		var request_;
		var browser = navigator.appName;
		if(browser == "Microsoft Internet Explorer"){
			 request_ = new ActiveXObject("Microsoft.XMLHTTP");
		}else{
			 request_ = new XMLHttpRequest();
		}
		return request_;
	}

	function verificaValorPorKm(campo){
		if (campo.checked){
			$('div[name=div_valor_por_km]').css('display','');
			$('div[name=div_taxa_visita]').css('display','none');
			$('input[name=taxa_visita]').attr("disabled", true);
		}else{
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
	function valida_campo_ant(){
		<? if($login_fabrica==3){ ?>
			if(document.getElementById('marca_troca').value==""){
			alert("Preencha o Campo \"Marca do Produto\".");
			setFocus(document.getElementById('marca_troca'));
			}
		<?}?>
	}
	function buscaFamilia(marca) {
		//alert(marca);
		$.ajax({
			type: "GET",
			url: "ajax_busca_familia.php",
			data: "marca=" + marca,
			cache: false,
			beforeSend: function() {
				// enquanto a função esta sendo processada, você
				// pode exibir na tela uma
				// msg de carregando
			},
			success: function(txt) {
				// pego o id da div que envolve o select com
				// name="id_modelo" e a substituiu
				// com o texto enviado pelo php, que é um novo
				//select com dados da marca x
				$('#familia_troca').html(txt);
				//HD 215281: Deixar sem selecionar familia por padrão
				$('#familia_troca').val('');
			},
			error: function(txt) {
				alert(txt);
			}
		});
	}

	function listaProduto(valor,marca) {
	//verifica se o browser tem suporte a ajax
		try {ajax = new ActiveXObject("Microsoft.XMLHTTP");}
		catch(e) {
			try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
			catch(ex) {
				try {ajax = new XMLHttpRequest();}
				catch(exc) {alert("Esse browser nao tem recursos para uso do Ajax"); ajax = null;}
			}
		}
		if(ajax) {
			//deixa apenas o elemento 1 no option, os outros sÃ£o excluÃ­dos
			window.document.frm_troca.troca_garantia_produto.options.length = 1;

			//opcoes Ã© o nome do campo combo
			idOpcao  = document.getElementById("opcoes");

			//HD 83158
			<? if($login_fabrica==3 or $login_fabrica==81){ ?>
				var marca = document.getElementById("marca_troca").value;
			<?}?>

			ajax.open("GET", "ajax_produto_familia.php?familia="+valor+"&marca="+marca, true);
//			alert("ajax_produto_familia.php?familia="+valor);
			ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			ajax.onreadystatechange = function() {
				if(ajax.readyState == 1) {
					idOpcao.innerHTML = "Carregando...!";
				}//enquanto estiver processando...emite a msg
				if(ajax.readyState == 4 ) {
					if(ajax.responseXML) {
						montaCombo(ajax.responseXML);//após ser processado-chama função
					}else {
						//caso nÃ£o seja um arquivo XML emite a mensagem abaixo
						idOpcao.innerHTML = "Selecione a familia";
					}
				}
			}
		//passa o cÃ³digo do produto escolhido
		var params = "linha="+valor;
		ajax.send(null);
		}
	}

	function montaCombo(obj){
		var dataArray   = obj.getElementsByTagName("produto");//pega a tag produto
		if(dataArray.length > 0) {//total de elementos contidos na tag cidade
			for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
				var item = dataArray[i];
				//contÃ©udo dos campos no arquivo XML
				var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
				var nome      =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
				idOpcao.innerHTML = "Selecione o produto";
				//cria um novo option dinamicamente
				var novo = document.createElement("option");
				//			echo "<option value='-1' >RESSARCIMENTO FINANCEIRO</option>";

				novo.setAttribute("id", "opcoes"); //atribui um ID a esse elemento
				novo.value = codigo;               //atribui um valor
				novo.text  = nome;                 //atribui um texto
				window.document.frm_troca.troca_garantia_produto.options.add(novo);//adiciona o novo elemento
			}

		} else {
			//idOpcao.innerHTML = "Selecione a família";//caso o XML volte vazio, printa a mensagem abaixo
			idOpcao.innerHTML = "Nenhum produto";
		}
	}



//ajax defeito_reclamado
function listaDefeitos(valor) {
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
	document.forms[0].defeito_reclamado.options.length = 1;
	//opcoes é o nome do campo combo
	idOpcao  = document.getElementById("opcoes");
	//	 ajax.open("POST", "ajax_produto.php", true);
	ajax.open("GET", "ajax_produto.php?produto_referencia="+valor, true);
	ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

	ajax.onreadystatechange = function() {
		if(ajax.readyState == 1) {idOpcao.innerHTML = "Carregando...!";}//enquanto estiver processando...emite a msg
		if(ajax.readyState == 4 ) {if(ajax.responseXML) { montaComboDefeitoReclamado(ajax.responseXML);//após ser processado-chama fun
			} else {idOpcao.innerHTML = "Selecione o produto";//caso não seja um arquivo XML emite a mensagem abaixo
					}
		}
	}
	//passa o código do produto escolhido
	var params = "produto_referencia="+valor;
	ajax.send(null);
	}
}

function montaComboDefeitoReclamado(obj){
	var dataArray   = obj.getElementsByTagName("produto");//pega a tag produto
	if(dataArray.length > 0) {//total de elementos contidos na tag cidade
	for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
		 var item = dataArray[i];
		//contéudo dos campos no arquivo XML
		var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
		var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
		idOpcao.innerHTML = "Selecione o defeito";
		//cria um novo option dinamicamente
		var novo = document.createElement("option");
		novo.setAttribute("id", "opcoes");//atribui um ID a esse elemento
		novo.value = codigo;		//atribui um valor
		novo.text  = nome;//atribui um texto
		document.forms[0].defeito_reclamado.options.add(novo);//adiciona o novo elemento
		}
	} else { idOpcao.innerHTML = "Selecione o defeito";//caso o XML volte vazio, printa a mensagem abaixo
	}
}


</script>

<script type="text/javascript">

	var produto_troca = '';

	function fnc_pesquisa_troca_obrigatoria(referencia){
		var ref_pesquisa = referencia.value;
		var ref_descricao = document.frm_os.produto_descricao.value;

		if(ref_pesquisa.length > 0 && ref_descricao.length > 0 && produto_troca != ref_pesquisa){
			$.ajax({
				type: "POST",
				url: "<?=$PHP_SELF?>",
				data: "referencia_troca_obrigatoria=" + ref_pesquisa,
				success: function(retorno) {
					if(retorno == 1){
						produto_troca = ref_pesquisa;
						var pergunta = confirm("Atenção!\nProduto com troca obrigatória. Deseja continuar?");
						if (pergunta){
							produto_troca = ref_pesquisa;
							document.frm_os.produto_serie.focus();
						}else{
							produto_troca = '';
							document.frm_os.produto_referencia.value = '';
							document.frm_os.produto_descricao.value = '';
							document.frm_os.produto_referencia.focus();
						}
					}
				}
			});
		}
		return false;
	}

	function atuSac(str,os) {

		//alert('atualiza_sac_ajax.php?str='+str+'&os='+os);
		requisicaoHTTP('GET','atualiza_sac_ajax.php?str='+str+'&os='+os, true , 'div_detalhe_carrega');

	}


	function div_detalhe_carrega (campos) {
		campos_array = campos.split("|");
		var msg = campos_array [0];
		var os = campos_array [1];

		if (msg=='ok') {
			alert('Sac Atualizado');
			window.location = "<?=$PHP_SELF?>?os="+os;
		}

	}


	/* Função mostra o campo quando muda o select(combo)*/
	function MudaCampo(campo){
		//alert(campo.value);
		if (campo.value== '15' || campo.value== '16' ) {
			document.getElementById('autorizacao_cortesia').style.display='block';
		}else{
			document.getElementById('autorizacao_cortesia').style.display='none';
		}
	}


	/******************* INTEGRIDADE ***************************/

		function adicionaIntegridade() {

		if(document.getElementById('defeito_reclamado').value=="0") { alert('Selecione o defeito reclamado'); return false}

		var tbl = document.getElementById('tbl_integridade');
		var lastRow = tbl.rows.length;
		var iteration = lastRow;


		if (iteration>0){
			document.getElementById('tbl_integridade').style.display = "inline";
		}


		var linha = document.createElement('tr');
		linha.style.cssText = 'color: #000000; text-align: left; font-size:10px';

		// COLUNA 1 - LINHA
		var celula = criaCelula(document.getElementById('defeito_reclamado').options[document.getElementById('defeito_reclamado').selectedIndex].text);
		celula.style.cssText = 'text-align: left; color: #000000;font-size:10px';

		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'integridade_defeito_reclamado_' + iteration);
		el.setAttribute('id', 'integridade_defeito_reclamado_' + iteration);
		el.setAttribute('value',document.getElementById('defeito_reclamado').value);
		celula.appendChild(el);


		linha.appendChild(celula);

		// coluna 3 - DEFEITO RECLAMADO
		//var celula = criaCelula(document.getElementById('defeito_reclamado').options[document.getElementById('defeito_reclamado').selectedIndex].text);
		//celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
		//linha.appendChild(celula);


		// coluna 6 - botacao
		var celula = document.createElement('td');
		celula.style.cssText = 'text-align: right; color: #000000;font-size:10px';

		var el = document.createElement('input');
		el.setAttribute('type', 'button');
		el.setAttribute('value','Excluir');
		el.onclick=function(){removerIntegridade(this);};
		celula.appendChild(el);
		linha.appendChild(celula);

		// finaliza linha da tabela
		var tbody = document.createElement('TBODY');
		tbody.appendChild(linha);
		/*linha.style.cssText = 'color: #404e2a;';*/
		tbl.appendChild(tbody);

		//document.getElementById('solucao').selectedIndex=0;
	}

	function removerIntegridade(iidd){
		var tbl = document.getElementById('tbl_integridade');
		tbl.deleteRow(iidd.parentNode.parentNode.rowIndex);

	}

	function criaCelula(texto) {
		var celula = document.createElement('td');
		var textoNode = document.createTextNode(texto);
		celula.appendChild(textoNode);
		return celula;
	}

	function BloqueiaNumeros(e){
		var tecla=new Number();
		if(window.event) {
			tecla = e.keyCode;
		}
		else if(e.which) {
			tecla = e.which;
		}
		else {
			return true;
		}
		if((tecla >= "48") && (tecla <= "57")){
			return false;
		}
	}
</script>

<? include "javascript_valida_campos_obrigatorios.php"; ?>
<!-- ============= <PHP> VERIFICA DUPLICIDADE DE OS  =============
		Verifica a exist?ncia de uma OS com o mesmo n?mero e em
		caso positivo passa a mensagem para o usu?rio.
=============================================================== -->
<?
if (strlen ($msg_erro) > 0) {
	if (strpos ($msg_erro,"tbl_os_sua_os") > 0) $msg_erro = "Esta ordem de serviço já foi cadastrada";
?>

<!-- ============= <HTML> COME?A FORMATA??O ===================== -->

<table border="0" cellpadding="1" cellspacing="1" align="center"  width = '700'>
<tr>
	<td valign="middle" align="center" class='error' id='erro_msg_'>
<?
	// retira palavra ERROR:
//echo $msg_erro;
	if (strpos($msg_erro,"ERROR: ") !== false) {
		$msg_erro = substr($msg_erro, 6, strlen($msg_erro)-6);
	}

	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}
	echo $erro . $msg_erro;
?>
	</td>
</tr>
</table>

<? }else{
echo $msg_debug ;
?>
<table border="0" cellpadding="1" cellspacing="1" align="center"  width = '700' style="display:none" id="tbl_erro_msg">
<tr>
	<td valign="middle" align="center" class='error' id='erro_msg_'>
	</td>
</tr>
</table>
<?
}
$sql = "SELECT TO_CHAR (current_timestamp , 'DD/MM/YYYY' )";
$res = pg_query ($con,$sql); echo $sql;
$hoje = pg_fetch_result ($res,0,0);
?>
<style>
.Conteudo{
	font-family: Verdana;
	font-size: 10px;
	color: #333333;
}
.Caixa{
	FONT: 8pt Arial ;
	BORDER-RIGHT:     #6699CC 1px solid;
	BORDER-TOP:       #6699CC 1px solid;
	BORDER-LEFT:      #6699CC 1px solid;
	BORDER-BOTTOM:    #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}

fieldset.valores , fieldset.valores div{
	padding: 0.2em;
	font-size:10px;
	width:200px;
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

table.bordasimples {border-collapse: collapse;}

table.bordasimples tr td {border:1px solid #000000;}

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

/*table tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #FFFFFF;
}*/

.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.sucesso{
	background-color:green;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;


}

.subtitulo{

	background-color: #7092BE;
	font: bold 13px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.frm_obrigatorio{
	background-color: #FCC;
	border: #888 1px solid;
	font:bold 8pt Verdana;
}

</style>

<?php

if ($_GET["osacao"] == "trocar") {
	$display_frm_os = "none";
	$display_frm_troca = "block";
}
else {
	$display_frm_os = "block";
	$display_frm_troca = "none";
}

?>
<!-- ------------- Formul?rio ----------------- -->
<form style="MARGIN: 0px; WORD-SPACING: 0px" id="frm_os" name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
<table border="0"  class="formulario" cellpadding="1" cellspacing="1" align="center" width="700" style="display: <? echo $display_frm_os; ?>">
	<tr class="titulo_tabela"><td>Cadastrar Ordem de Serviço</td></tr>
<!-- HD 194731: Coloquei o formulário da OS inteiro dentro de uma tag table para dar
display:none quando ele não deve estar disponível -->
	<tr>
		<td>
			<table border="0" width="700" align="center">
				<tr>
					<td valign="top" align="left" >

						<?
						if (strlen ($msg_erro) > 0) {

							$consumidor_cidade		= $_POST['consumidor_cidade'];
							$consumidor_estado		= $_POST['consumidor_estado'];
							$consumidor_email		= trim ($_POST['consumidor_email']) ;
							$consumidor_nome		= trim ($_POST['consumidor_nome']) ;
							$consumidor_fone		= trim ($_POST['consumidor_fone']) ;
							$consumidor_endereco	= trim ($_POST['consumidor_endereco']) ;
							$consumidor_numero		= trim ($_POST['consumidor_numero']) ;
							$consumidor_complemento	= trim ($_POST['consumidor_complemento']) ;
							$consumidor_bairro		= trim ($_POST['consumidor_bairro']) ;
							$consumidor_cep			= trim ($_POST['consumidor_cep']) ;
							$consumidor_rg			= trim ($_POST['consumidor_rg']) ;

						}
						?>



						<input class="frm" type="hidden" name="os" value="<? echo $os ?>">
						<? if (strlen($pedido) > 0) { ?>
							<input class="frm" type="hidden" name="produto_referencia" id="produto_referencia" value="<? echo $produto_referencia ?>">
							<input class="frm" type="hidden" name="produto_descricao"  id="produto_descricao" value="<? echo $produto_descricao ?>">
						<?}?>
					</td>
				</tr>
				<? if ($login_fabrica == 19 OR $login_fabrica == 20 OR $login_fabrica == 30 OR $login_fabrica == 50 OR $login_fabrica == 7) { ?>
						<tr class="subtitulo" >
							<td colspan="100%" width='650'>Informações do Atendimento</td>
						</tr>
				<? } ?>
				<? if ($login_fabrica==7) { // HD 75762 para Filizola ?>
					<tr>
						<td>
							<div style='font: 11px; Arial;
								color:#333333; width:650px;' class='CaixaMensagem' >
								Classificação da OS
								<select name='classificacao_os' id='classificacao_os' class="frm">
									<option <? if (strlen($classificacao_os)==0) {echo "selected";} ?>></option><?php
										$sql = "SELECT	*
												FROM	tbl_classificacao_os
												WHERE	fabrica = $login_fabrica
												AND		ativo IS TRUE
												ORDER BY descricao";
										$res = @pg_query($con,$sql); echo $sql;
										if (pg_num_rows($res) > 0) {
											for ($i = 0; $i < pg_num_rows($res); $i++) {
												$xclassificacao_os = pg_fetch_result($res,$i,'classificacao_os');
												if ($xclassificacao_os == 5 and $classificacao_os != 5) {
													continue;
												}
												echo "<option value='$xclassificacao_os'";
												if ($classificacao_os == $xclassificacao_os) echo " selected";
												echo ">".pg_fetch_result($res,$i,descricao)."</option>\n";
											}
										}?>
								</select>
							</div>
							</td>
						</tr>
				<? } ?>

				<? if ($login_fabrica == 19 OR $login_fabrica == 20 OR $login_fabrica == 30 OR $login_fabrica == 50 OR $login_fabrica == 7 OR $login_fabrica==40) { ?>
						<tr><td colspan="1">


						<? if ($login_fabrica==7) { ?>
							<font size="1" face="Geneva, Arial, Helvetica, san-serif">Natureza&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</font>
						<?}else{?>
							Tipo de Atendimento
						<?}?>
						<select name="tipo_atendimento" id="tipo_atendimento" size="1" class="frm"
							<? if ($login_fabrica==20) {
									echo "onChange='MudaCampo(this)'";
								}else{
									if ($login_fabrica==30 OR $login_fabrica==50) {
										?> onchange="javascript:verifica_atendimento('tipo_atendimento')"; <?
									}
								};?>>
							<option selected></option>
							<?

							//IGOR  - HD 2909  | Garantía de repuesto - Não tem | Garantía de accesorios - Não tem | Garantía de reparación - Não tem
							$wr = "";
							if($login_fabrica == 20 ){
								if(strlen($posto)>0){
									$sql = "select pais from tbl_posto where posto =$posto";
									$res = pg_query ($con,$sql) ;
									$pais = pg_fetch_result ($res, 0, pais);

									if($pais == "PE"){
										$wr = "AND tbl_tipo_atendimento.tipo_atendimento NOT IN(11, 12, 14) ";

									}
								}
							}

							/*HD:22505- COLORMAQ - Tipo atendimento de deslocamento só aparece se o posto tem km cadastrado(maior que 0)*/
							$sql_deslocamento = " ";
							if($login_fabrica==50){
								/*$sql_deslocamento = " 	AND tipo_atendimento NOT IN (
															SELECT
																CASE WHEN valor_km > 0
																	Then 0
																	Else 55
															END as tipo_atendimento
															FROM tbl_posto_fabrica
															WHERE fabrica =
																AND posto = $posto
														) ";*/
							}

							$sql = "SELECT *
									FROM tbl_tipo_atendimento
									WHERE fabrica = $login_fabrica
									AND   ativo IS TRUE
									$wr
									$sql_deslocamento
									ORDER BY tipo_atendimento";
							$res = pg_query ($con,$sql) ;


							for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
								echo "<option ";
								if ($tipo_atendimento == pg_fetch_result ($res,$i,tipo_atendimento) ) echo " selected ";
								echo " value='" . pg_fetch_result ($res,$i,tipo_atendimento) . "'" ;
								echo " > ";
								echo pg_fetch_result ($res,$i,codigo) . " - " . pg_fetch_result ($res,$i,descricao) ;
								echo "</option>\n";
							}
							?>
						</select>
						<?
						if($login_fabrica == 20){
				/*
							echo "&nbsp;&nbsp;&nbsp;&nbsp;Segmento de atuação <select name='segmento_atuacao' size='1' class='frm'>";
							echo "<option selected></option>";

							$sql = "SELECT *
								FROM tbl_segmento_atuacao
								WHERE fabrica = $login_fabrica
								ORDER BY descricao";
							$res = pg_query ($con,$sql) ;

							for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
								$descricao_segmento = pg_fetch_result ($res,$i,descricao);
								$x_segmento_atuacao = pg_fetch_result ($res,$i,segmento_atuacao);

								//--=== Tradução para outras linguas ============================= Raphael HD:1356

								$sql_idioma = "SELECT * FROM tbl_segmento_atuacao_idioma WHERE segmento_atuacao = $x_segmento_atuacao AND upper(idioma) = '$sistema_lingua'";

								$res_idioma = @pg_query($con,$sql_idioma);

								if (@pg_num_rows($res_idioma) >0) $descricao_segmento  = trim(@pg_fetch_result($res_idioma,0,descricao));


								//--=== Tradução para outras linguas ================================================

								echo "<option ";
								if ($segmento_atuacao == $x_segmento_atuacao ) echo " selected ";
								echo " value='$x_segmento_atuacao'>" ;
								echo $descricao_segmento  ;
								echo "</option>\n";
							}
							echo "</select>";
				*/

							echo "<br><font style='color:#FF0000;'>";
							if($sistema_lingua)
								echo "En caso de garantía  de repuestos o accesorios no es necesario informar del producto en la OS";
							else
								echo "Garantia de Peças ou Acessórios não necessitam de Produtos na OS";
							echo "</FONT><br>";
						}
						?>
						</font>
						</td></tr>
					<?
					}

		echo "<tr><td colspan='1'>";
		if ($login_fabrica == 20) {
			//alterado gustavo HD 5909
			/*#####################################*/
			if($tipo_atendimento==15 or $tipo_atendimento==16) $mostrar = "display:block";
			else                                               $mostrar = "display:none";
		?>
		<div id='autorizacao_cortesia' style="<?echo $mostrar;?>; width:600px;" >


				<?
					if($sistema_lingua)
						echo "Autorización Cortesía";
					else
						echo "Autorização Cortesia";
				?>
				&nbsp;<INPUT TYPE='text' NAME='autorizacao_cortesia' value='<? echo $autorizacao_cortesia; ?>' size='40' class="frm">

			<br />
			<font style="color:#FF0000;">
			<?
				if ($sistema_lingua)
					echo "En el caso de comerciales o técnicos cortesía está obligado a informar el nombre de la persona que aprobó y la fecha de su aprobación";
				else
					echo 'Cortesia Comercial ou Técnica, é obrigatório informar o aprovante e a "data de aprovação"';
			?>
			</FONT>






		</div>

		<?
		}
		?>
		</td></tr>
	</table>
		<table width="650" border="0" cellspacing="5" cellpadding="0" align="center">
		<tr class="subtitulo"><td colspan="6">Informações do Posto</td></tr>
		<tr>
			<? if ($login_fabrica == 6){ ?>
			<td nowrap colspan="2">
				Número de Série
				<br>
				<input class="frm" type="text" name="produto_serie" id="produto_serie" size="20" maxlength="20" value="<? echo $produto_serie ?>" >
				&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto_serie (document.frm_os.produto_serie,'frm_os')"  style='cursor: pointer'>
				<script>
				<!--
				function fnc_pesquisa_produto_serie (campo,form) {
					if (campo.value != "") {
						var url = "";
						url = "produto_serie_pesquisa.php?campo=" + campo.value + "&form=" + form ;
						janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
						janela.focus();
					}

					else{
						alert("Informe toda ou parte da informação para realizar a pesquisa!");
					}
				}
				-->
				</script>
			</td>
			</tr>
			<tr>
			<? } ?>

			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">

					<span rel="posto_codigo">
						Código do Posto
					</span>
				</font>
				<br>
					<input class="frm" id="posto_codigo" type="text" name="posto_codigo"  size="15" value="<? echo $posto_codigo ?>"
						<?
							if (($login_fabrica == 5) or ($login_fabrica == 15)) { ?>
								onblur="fnc_pesquisa_posto2(document.frm_os.posto_codigo,document.frm_os.posto_nome,'codigo')"
						<?	} ?>>&nbsp;

					<img src='imagens/lupa.png' border='0' align='absmiddle'
						<?
						/*HD 2159-24601- PESQUISA DIFERENTE PARA CALCULO KM*/
						if (($login_fabrica == 30) or ($login_fabrica == 50)) { ?>
							onclick="javascript: fnc_pesquisa_posto_km(document.frm_os.posto_codigo,document.frm_os.posto_nome,'codigo')"
						<?}else{ ?>
							onclick="javascript: fnc_pesquisa_posto2(document.frm_os.posto_codigo,document.frm_os.posto_nome,'codigo')"
						<?}?>
					style="cursor:pointer" >
					</A>
					<input type='hidden' name='contato_endereco' id='contato_endereco' value='<?echo $contato_endereco;?>'>
					<input type='hidden' name='contato_numero' id='contato_numero' value='<?echo $contato_numero;?>'>
					<input type='hidden' name='contato_cep' id='contato_cep' value='<?echo $contato_cep;?>'>
					<input type='hidden' name='contato_bairro' id='contato_bairro' value='<?echo $contato_bairro;?>'>
					<input type='hidden' name='contato_cidade' id='contato_cidade' value='<?echo $contato_cidade;?>'>
					<input type='hidden' name='contato_estado' id='contato_estado' value='<?echo $contato_estado;?>'>
			</td>

			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">
					<span rel="posto_nome">
						Nome do Posto
					</span>
				</font>
				<br>
					<input class="frm" id="posto_nome" type="text" name="posto_nome" size="50" value="<? echo $posto_nome ?>"
					<? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?>
					<? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm_os.posto_codigo,document.frm_os.posto_nome,'nome')" 	<? } ?>>&nbsp;
					<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle'
						<?
						/*HD 2159-24601- PESQUISA DIFERENTE PARA CALCULO KM*/
						if (($login_fabrica == 30) or ($login_fabrica == 50)) { ?>
							onclick="javascript: fnc_pesquisa_posto_km(document.frm_os.posto_codigo,document.frm_os.posto_nome,'nome')"
						<?}else{ ?>
							onclick="javascript: fnc_pesquisa_posto2 (document.frm_os.posto_codigo,document.frm_os.posto_nome,'nome')"
						<?}?>
					style="cursor:pointer;"></A>
			</td>

		</tr>
		</table>



		<table width="650" border="0" cellspacing="5" cellpadding="0" align="center">
		<tr class="subtitulo"><td colspan="4">Informações do Fabricante</td></tr>
		<tr valign="top">
			<td nowrap width="170" >
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">
					<span rel="data_abertura">
						Data Abertura
					</span>
				</font>
				<br>
				<input name="data_abertura" id='data_abertura' size="12" maxlength="10" value="<? echo $data_abertura ?>" type="text" class="frm" tabindex="0" >
			</td>

			<? if ($login_fabrica == 7){ #HD 49336 ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Hora Abertura</font>
				<br>
				<?
				if (strlen($hora_abertura)==0){
					#$hora_abertura = date("H:i"); //Vazio para forçar o preenchimento
				}else{
					$hora_abertura = substr($hora_abertura,0,5);
				}
				?>
				<input name="hora_abertura" size="7" maxlength="5" id='hora' value="<? echo $hora_abertura ?>" type="text" class="frm" tabindex="0" >
			</td>
			<?}?>

			<?php
			# HD 311411

			if(in_array($login_fabrica,$fabricas_alteram_conserto) && $os > 0){
				$sqlConserto = "SELECT to_char(tbl_os.data_conserto,'DD/MM/YYYY') AS data_conserto FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica AND data_conserto IS NOT NULL";
				$resConserto = pg_query($con,$sqlConserto);
				if(pg_num_rows($resConserto) > 0){
					$data_conserto = pg_fetch_result($resConserto,0,'data_conserto');
					if($login_fabrica == 6){
						$readonly_conserto = ' readonly="readonly"';
						$botao_limpar_conserto = '<input type="button" value="Limpar" onclick="$(\'#data_conserto\').val(\'\');"/>';
					}
					?>
					<td nowrap>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data do Conserto</font>
						<br />
						<input name="data_conserto" id="data_conserto" size="12" value="<? echo $data_conserto ?>" <?=$readonly_conserto;?> type="text" class="frm">
						<?=$botao_limpar_conserto;?>
					</td>
				<?php
				}
			}?>


			<td nowrap>
				<? if ($pedir_sua_os == 't' && !in_array($login_fabrica,array(101,104,105,87))) { ?>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='sua_os'>OS Fabricante</font>
				<br>
				<input  name     ="sua_os"
						id       ="sua_os"
						class    ="frm"
						type     ="text"
						size     ="20"
						<?
						if ($login_fabrica == 5) {echo "maxlength='6'  ";} else { echo "maxlength='20'  ";}
						?>
						value    ="<? echo $sua_os ?>"
						onblur   ="VerificaSuaOS(this); this.className='frm'; displayText('&nbsp;');"
						onfocus  ="this.className='frm-on';displayText('&nbsp;Digite aqui o número da OS do Fabricante.');">
				<?
				} else {
					echo "&nbsp;";
					if (strlen($sua_os) > 0) {
						echo "<input type='hidden' name='sua_os' value='$sua_os'>";
					}else{
						echo "<input type='hidden' name='sua_os'>";
					}

				}
                 if($login_fabrica == 87){?>
                        <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='produto_serie'>Número de Série</span></font>
                        <br />
                        <input class="frm" type="text" name="produto_serie" id="produto_serie" size="15" maxlength="20" value="<?=$produto_serie?>" onchange=" this.value = this.value.toUpperCase();" />
                        <img src='imagens/lupa.png' border='0' align='absmiddle' onclick='fnc_pesquisa_numero_serie(document.frm_os.produto_serie, "produto_serie")' style='cursor: pointer' />
                <? }
                ?>

			</td><?php

			if (trim (strlen ($data_abertura)) == 0 AND ($login_fabrica == 7 OR $login_fabrica == 50)) {
				$data_abertura = $hoje;
			}

            if($login_fabrica == 87){
                echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'><span rel='horas_trabalhadas'>Horas Trabalhadas</span></font><BR>";
                   echo "<input type='text' class='frm' name='horas_trabalhadas' id='horas_trabalhadas' value='$horas_trabalhadas' size='9' maxlength='5'>";
                echo "</td>";
            }

			if ($login_fabrica == 50) {?>

				<td nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='produto_serie'>Número de Série</span></font>
					<br />
					<input class="frm" type="text" name="produto_serie" id="produto_serie" size="15" maxlength="20" value="<?=$produto_serie?>" onchange=" this.value = this.value.toUpperCase();" />
					<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='fnc_pesquisa_numero_serie(document.frm_os.produto_serie, "produto_serie")' style='cursor: pointer' />
				</td>
                <?php
			} else {?>

				<td>&nbsp;</td>

			<? } ?>

			<? if ($login_fabrica == 19) { ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Qtde.Produtos</font>
				<br>
				<input name="qtde_produtos" id="qtde_produtos" onkeypress="return somenteNumeros(event)" size="2" maxlength="3"value="<? echo $qtde_produtos ?>" type="text" class="frm" tabindex="0" >
				<INPUT TYPE="hidden" NAME="qtde_km" value="<? echo $qtde_km; ?>">
			</td>
			<? } ?>
			</tr>
			<tr>
			<td nowrap>
				<?
				if ($login_fabrica == 3) {
					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Código do Produto</font>";
				}else{
					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'> <span rel='produto_referencia'>Referência do Produto</span></font>";
				}
				?>
				<br>

				<?	if (strlen($pedido) > 0) { ?>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo $produto_referencia ?></b>
				</font>
				<?	}else{	?>
				<input class="frm" type="text" name="produto_referencia" id="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>"
				<? if($login_fabrica==50){?>onChange="javascript:this.value=this.value.toUpperCase();"<?}?>
				<? if ($login_fabrica == 5) { ?>  onblur="fnc_pesquisa_produto2 (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'referencia')" <? } ?>
				<?if($login_fabrica==7) {?> onblur="busca_valores(); verificaProduto(document.frm_os.produto_referencia,this)"; <?} ?>
				>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto2 (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'referencia')">
				<?	}	?>
			</td>
			<td nowrap width="260">
				<?
				if ($login_fabrica == 3) {
					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Modelo do Produto</font>";
				}else{
					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'><span rel='produto_descricao'>Descrição do Produto</span></font>";
				}
				?>
				<br>
				<?	if (strlen($pedido) > 0) { ?>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo $produto_descricao ?></b>
				</font>
				<? }else{ ?>
				<input class="frm" type="text" name="produto_descricao" id="produto_descricao" size="30" value="<? echo $produto_descricao ?>"
				<? if (in_array($login_fabrica,array(98,106,108,111))) { ?>  onblur="fnc_pesquisa_troca_obrigatoria (document.frm_os.produto_referencia)" <? } ?>
				<? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?>
				<? if (($login_fabrica == 5) or ($login_fabrica == 15)) { ?> onblur="fnc_pesquisa_produto2(document.frm_os.produto_referencia,document.frm_os.produto_descricao,'descricao')" <? } ?>
				<?if($login_fabrica==7) {?> onblur="busca_valores(); verificaProduto(document.frm_os.produto_referencia,this)"; <?} ?>
				>&nbsp;<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript:fnc_pesquisa_produto2 (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'descricao')"></A>
				<?	}	?>
			</td>
			<? if ($login_fabrica <> 6 AND $login_fabrica <> 50 AND $login_fabrica <> 87 ){ ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><?php
				if ($login_fabrica == 35) {
					echo 'PO#';
				} else {
					echo '<span rel="produto_serie">Número de Série</span>';
				}?></font>
				<br />
				<input class="frm produto_serie" type="text" name="produto_serie" id="produto_serie" size="15" <? if ($login_fabrica == 35) {?> maxlength="12" <?} else if( $login_fabrica == 94){ ?>maxlength="6" <? } else {?> maxlength="20" <?}?> value="<?=$produto_serie?>" <? if ($login_fabrica == 50) {?>onchange="this.value = this.value.toUpperCase();"<?}?> <? if (in_array($login_fabrica,array(98,106,108,111))) { ?>  onblur="fnc_pesquisa_troca_obrigatoria (document.frm_os.produto_referencia)" <? } ?> /><?php
				if ($login_fabrica == 25) {?>
					&nbsp;<input type="button" onclick='fn_verifica_garantia();' name='Verificar' value='Verificar' /><?php
				}
				if ($login_fabrica == 50 || $login_fabrica == 3) {//HD 256659?>
					<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='<?php if ($login_fabrica == 50) {?>fnc_pesquisa_numero_serie(document.frm_os.produto_serie, "produto_serie")<?php } else { ?> fnc_pesquisa_serie(document.frm_os.produto_serie) <?php }?>' style='cursor: pointer' /><?php
				}?>
			</td><?php
			}
			if ($login_fabrica == 11) {
				if ((strlen($admin_os) > 0 and strlen($os) > 0) or (strlen($os)==0)) {
					echo "<td>&nbsp;&nbsp;<BR>";
					if ($login_admin == 532) { //HD 106085
						echo "<input type='checkbox' name='admin_paga_mao_de_obra' value='admin_paga_mao_de_obra'";
						if ($admin_paga_mao_de_obra == 't') echo "checked";
						echo ">";
					} else if ($login_admin <> 532 AND $admin_paga_mao_de_obra == 'f') {
						echo "<input type='checkbox' name='admin_paga_mao_de_obra' value='admin_paga_mao_de_obra' disabled>";
					} else {
						echo "<input type='hidden' name='admin_paga_mao_de_obra' value='admin_paga_mao_de_obra'>";
						echo "<input type='checkbox' name='admin_paga_mao_de_obra' value='admin_paga_mao_de_obra' checked disabled>";
					}
					echo "<br><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Pagar Mão-de-Obra</font>";
					echo "</td>";
				}
			} ?>
		</tr><?php
            if($login_fabrica == 87){
                echo "<tr>";
                    echo "<td>";
                        if($login_fabrica == 87){
                            echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'><span rel='tipo_atendimento'>Tipo de Atendimento</span></font><br>";
                            echo "<select name='tipo_atendimento' id='tipo_atendimento' class='frm'>";

                            if(empty($produto_referencia))
                                echo "<option value='0'> - informe um produto</option>";
                            else{
                                $sql = "
                                    SELECT
                                        tbl_produto.familia
                                    FROM
                                        tbl_produto
                                            JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
                                    WHERE
                                        tbl_produto.referencia = '$produto_referencia'
                                        AND tbl_linha.fabrica = $login_fabrica;";
                                $res = pg_query($con, $sql);

                                if(pg_num_rows($res) == 1){
                                    $familia = pg_fetch_result($res, 0, "familia");
                                    $sql = "SELECT tipo_atendimento, descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND familia = $familia";
                                    $res = pg_query($con, $sql);

                                    if(pg_num_rows($res) > 0){
                                        //echo "<option value='0' selected>selecione um atendimento</option>";
                                        for($i = 0; $i < pg_num_rows($res); $i++) {
                                            //extract(pg_fetch_array($res));
                                            $cod_tipo_atendimento = pg_fetch_result($res,$i,'tipo_atendimento');
                                            $descricao = pg_fetch_result($res,$i,'descricao');

                                            $selected = ($tipo_atendimento == $cod_tipo_atendimento) ? " selected " : "";

                                            echo "<option value='$cod_tipo_atendimento' label='$descricao' $selected>$descricao</option>";
                                        }
                                    }
                                }
                            }

                            echo "</select>";
                        }
                    echo "</td>";

                    echo "<td colspan='3'><font size='1' face='Geneva, Arial, Helvetica, san-serif'><span rel='defeito_reclamado_descricao'>Defeito Reclamado</span></font><BR>";
                        echo "<INPUT TYPE='text' id='defeito_reclamado_descricao' name='defeito_reclamado_descricao' class='frm' value='$defeito_reclamado_descricao' size='50' >";
                    echo "</td>";
                echo "</tr>";
            }

		if ($login_fabrica == 7) {?>
		<tr>
			<td nowrap  valign='top'>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Capacidade</font>
				<br>
				<? if (strlen($produto_capacidade)>0){
					echo "<INPUT TYPE='hidden' name='capacidade' class='frm' id='capacidade' value='$produto_capacidade'>";
					echo "<INPUT TYPE='text' VALUE='$produto_capacidade' class='frm' SIZE='9' onClick=\"alert('Não é possível alterar a capacidade')\" disabled>";
				}else{?>
					<INPUT TYPE="text" NAME="capacidade" class='frm'  id='produto_capacidade' VALUE="<?=$produto_capacidade?>" SIZE='9' MAXLENGTH='9'>
				<?}?>
			</td>
			<td nowrap  valign='top'>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Divisão</font>
				<br>
				<? if (strlen($produto_divisao)>0){
					echo "<input type='hidden' name='divisao' class='frm' value='$produto_divisao'>";
					echo "<INPUT TYPE='text' VALUE='$produto_divisao' id='produto_divisao' class='frm' SIZE='9' onClick=\"alert('Não é possível alterar a divisão')\" disabled>";
				}else{?>
					<INPUT TYPE="text" NAME="divisao" class='frm' id='divisao' VALUE="<?=$divisao?>" SIZE='9' MAXLENGTH='9'>
				<?}?>
			</td>
			<td nowrap>
			</td>
		</tr>
		<? } ?>
		</table>

			<?
				//hbtech 3/3/2008 14824
				if($login_fabrica == 25){
			?>
				<div id='div_estendida' style='text-align:center'>
					<?if(strlen($produto_serie)>0){
							include "conexao_hbtech.php";

							$sql = "SELECT	idNumeroSerie  ,
											idGarantia     ,
											revenda        ,
											cnpj
									FROM numero_serie
									WHERE numero = '$produto_serie'";
							$res = mysql_query($sql) or die("Erro no Sql:".mysql_error());

							if(mysql_num_rows($res)>0){
								$idNumeroSerie = mysql_result($res,0,idNumeroSerie);
								$idGarantia    = mysql_result($res,0,idGarantia);
								$es_revenda       = mysql_result($res,0,revenda);
								$es_cnpj          = mysql_result($res,0,cnpj);

								if(strlen($idGarantia)==0){
									echo "Número de série não encontrado nas vendas";

								}
							}
						}
					?>
				</div>
				<?
				}//fim hbtech

				/*	HD: 110888 - LIBERADO PARA SUGGAR
					HD 413350 - Adicionar LeaderShip*/
				$aExibirDefeitoReclamado = array(3,7,19,24,28,30,35,50,59,81,85,95,52,98,99,101);
				//if ($login_fabrica == 7 or $login_fabrica == 28 or $login_fabrica == 35 or $login_fabrica == 19 or $login_fabrica == 50 or $login_fabrica == 30 or $login_fabrica ==59){
				 if (in_array($login_fabrica, $aExibirDefeitoReclamado) || $login_fabrica > 101) {
				?>

			<table width="650" border="0" cellspacing="5" cellpadding="0" align="center">
			<tr valign='top'>
				<td valign='top' align='left'>
				<span rel="defeito_reclamado_descricao">Defeito Reclamado</span><br>
				<?
				if($pedir_defeito_reclamado_descricao == 't'){
						if($login_fabrica==50){
							$onchange= "onChange=\"javascript: this.value=this.value.toUpperCase();\"";
						}
					echo "<INPUT TYPE='text' id='defeito_reclamado_descricao' name='defeito_reclamado_descricao' class='frm' value='$defeito_reclamado_descricao' size='50' $onchange>";
				}else{

					#HD 424887 - INICIO

							/* ESTA VERIFICAÇÃO ESTÁ SENDO FEITA PORQUE PARA AS FABRICAS QUE ESTÃO NESTE ARRAY
							A CHAMADA DESTA FUNÇÃO "listaDefeitos" SERÁ FEITA NO ONBLUR DO PRODUTO, POIS NÃO
							HAVERÁ INTEGRIDADE COM O DEFEITO_RECLAMADO - by: gabriel silveira */

							if (!in_array($login_fabrica,$fabricas_defeito_reclamado_sem_integridade)){
								$onfocus_integridade_def_reclamado = "onfocus='listaDefeitos(document.frm_os.produto_referencia.value);'";

							}else{
								$onfocus_integridade_def_reclamado = null;
							}
					#HD 424887 - FIM

					//HD 172561 - Usar tbl_os.defeito_reclamado e tbl_os.defeito_reclamado_descricao para Fabrica 3 e Linha 528
					if ($login_fabrica == 3) { $defeito_reclamado_onchange = "onchange='mostraDefeitoDescricao($login_fabrica)'"; }
					echo "<select name='defeito_reclamado' class='frm' id='defeito_reclamado' style='width: 220px;' $onfocus_integridade_def_reclamado $defeito_reclamado_onchange >";

					if(strlen($defeito_reclamado) > 0 || strlen($defeito_reclamado) == 0) {

						if (in_array($login_fabrica,$fabricas_defeito_reclamado_sem_integridade)){

							$sql = " SELECT defeito_reclamado, descricao
							FROM tbl_defeito_reclamado
							WHERE fabrica=$login_fabrica
							AND ativo is TRUE";

							$res = pg_query($con,$sql); echo $sql;

							if(pg_num_rows($res) > 0){

								for ($y = 0; $y < pg_num_rows($res); $y++){
									$xdefeito_reclamado  = pg_fetch_result($res,$y,defeito_reclamado);
									$reclamado_descricao = pg_fetch_result($res,$y,descricao);

									echo "<option id='opcoes_$y' value='$xdefeito_reclamado'";
									if($defeito_reclamado==$xdefeito_reclamado) echo "selected";
									echo ">$reclamado_descricao</option>";
								}

							}

						}else{

						$sql = " SELECT defeito_reclamado, descricao
								FROM tbl_defeito_reclamado
								WHERE defeito_reclamado = $defeito_reclamado";
						$res = pg_query($con,$sql); echo $sql;
						if(pg_num_rows($res) > 0){
							$xdefeito_reclamado  = pg_fetch_result($res,0,defeito_reclamado);
							$reclamado_descricao = pg_fetch_result($res,0,descricao);
						}
						echo "<option id='opcoes' value='$defeito_reclamado'";
						#HD 242946
						if($defeito_reclamado==$xdefeito_reclamado) echo "selected";
						echo ">$reclamado_descricao</option>";

						}

					}else{

						echo "<option id='opcoes' value='0'></option>";

					}

					echo "</select>";
				}
				echo "</td>";

				//HD 172561 - Usar tbl_os.defeito_reclamado e tbl_os.defeito_reclamado_descricao para Fabrica 3 e Linha 528
				if ($login_fabrica == 3){
					echo "<td style='display:none;' nowrap valign='top' id='td_defeito_reclamado_descricao'>Defeito Reclamado Adicional<br><INPUT TYPE='text' name='defeito_reclamado_descricao' class='frm' value='$defeito_reclamado_descricao' size='30' class='frm'></td>
					<script language='javascript'>
					mostraDefeitoDescricao($login_fabrica);
					</script>
					";
				}

				if ($login_fabrica == 19) { // HD 49849
					echo "<td valign='top' align='left'>
						Motivo";
					echo "<br>";
					echo "<SELECT NAME='tipo_os' style='width:150px' class='frm'>";
					echo "<OPTION VALUE=''></OPTION>";
					$sql = " SELECT tipo_os,
									descricao
							FROM tbl_tipo_os
							WHERE tipo_os in (11,12)
							ORDER BY tipo_os ";
					$res = pg_query ($con,$sql) ;
					for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
						echo "<option ";
						if ($tipo_os== pg_fetch_result ($res,$i,tipo_os) ) echo " selected ";
						echo " value='" . pg_fetch_result ($res,$i,tipo_os) . "'>" ;
						echo pg_fetch_result ($res,$i,descricao) ;
						echo "</option>";
					}
					echo "</SELECT>";
					echo "</td>";
				}
				if ($login_fabrica == 30) {
					echo "<td valign='top' align='left'>
						<font size='1' face='Geneva, Arial, Helvetica, san-serif'>OS Posto</font>";
						echo "<br>";
					echo "<INPUT TYPE='text' name='os_posto' class='frm' value='$os_posto' size='12' maxlength='20'>";
					echo "</td>";
				}
				if ($login_fabrica == 50) {
					echo "<td valign='top' align='left'>
						<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Data Fabricação</font>";
						echo "<br>";
					echo "<INPUT TYPE='text' name='data_fabricacao' id='data_fabricacao' class='frm' value='$data_fabricacao' size='12' maxlength='20'>";
					echo "</td>";
				}
				?>

			</tr>
			</table>
			<?
		}elseif ($login_fabrica==11){
				echo "<input type='hidden' name='defeito_reclamado' value='$defeito_reclamado'>";
		}

		if($login_fabrica==19){
			echo "<center><font size='-2'>Antes de Gravar a OS Adicione os Defeitos</font></center>";
			echo "<center><input type='button' onclick=\"javascript: adicionaIntegridade()\" value='Adicionar Defeito' name='btn_adicionar'></center><br>";
			echo "
			<table class='tabela' style='display:none; margin-left:25px;' align='center' width='650' border='0' id='tbl_integridade' cellspacing='3' cellpadding='3'>
			<thead>
			<tr bgcolor='#596D9B' style='color:#FFFFFF;'>
			<td align='center' nowrap><b>Defeito Reclamado</b></td>
			<td align='center' nowrap><b>Defeito Constatado</b></td>
			<td align='center'><b>Ações</b></td>
			</tr>
			</thead>
			<tbody>";
			if(strlen($os)>0){
				$sql_cons = "SELECT distinct
								tbl_defeito_reclamado.defeito_reclamado ,
								tbl_defeito_reclamado.descricao  AS dr_descricao   ,
								tbl_defeito_reclamado.codigo     AS dr_codigo
						FROM tbl_os_defeito_reclamado_constatado
						JOIN tbl_defeito_reclamado USING(defeito_reclamado)
						LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado=tbl_os_defeito_reclamado_constatado.defeito_constatado
						WHERE os = $os";
				$res_dr = pg_query($con, $sql_cons);
				if(pg_num_rows($res_dr) > 0){
					for($x=0;$x<pg_num_rows($res_dr);$x++){
						$dr_defeito_reclamado = pg_fetch_result($res_dr,$x,defeito_reclamado);
						$dr_descricao         = pg_fetch_result($res_dr,$x,dr_descricao);
						$dr_codigo            = pg_fetch_result($res_dr,$x,dr_codigo);
						$aa = $x+1;
						if($x % 2 == 0) $cor = "#F7F5F0"; else $cor = "#F1F4FA";
						echo "<tr bgcolor='$cor'>";
						echo "<td nowrap><font size='1'><input type='hidden' name='integridade_defeito_reclamado_$aa' value='$dr_defeito_reclamado'>$dr_codigo-$dr_descricao</font></td>";
						// HD 33303
						echo "<td align='left' nowrap>";
						if(strlen($dr_defeito_reclamado) >0){
							$sql_dc="SELECT
										tbl_defeito_constatado.descricao         ,
										tbl_defeito_constatado.codigo
								FROM tbl_os_defeito_reclamado_constatado
								LEFT JOIN tbl_defeito_constatado USING(defeito_constatado)
								WHERE os = $os
								and   tbl_os_defeito_reclamado_constatado.defeito_reclamado = $dr_defeito_reclamado";
							$res_dc = pg_query($con, $sql_dc);
							if(pg_num_rows($res_dc) > 0){
								for($y=0;$y<pg_num_rows($res_dc);$y++){
									$dc_descricao = pg_fetch_result($res_dc,$y,descricao);
									$dc_codigo    = pg_fetch_result($res_dc,$y,codigo);
									if(strlen($dc_descricao) >0 ){
										echo "<font size='-2'>$dc_descricao</font><br>";
									}
								}
							}
						}
						echo "</td>";
						echo "<td align='center'><input type='button' onclick='removerIntegridade(this);' value='Excluir'></td>";
						echo "</tr>";
					}
					echo "<script>document.getElementById('tbl_integridade').style.display = \"inline\";</script>";
				}
			}
			echo "</tbody></table>";
		}
		?>


		<? if ($login_fabrica == 1) { ?>
		<table width="700" border="0" align="center" cellspacing="5" cellpadding="0">
		<tr valign='top'>
			<td width="20">&nbsp;</td>
			<td nowrap width="170">
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Código Fabricação</font>
				<br>
				<input  name ="codigo_fabricacao" class ="frm" type ="text" size ="15" maxlength="20" value ="<? echo $codigo_fabricacao ?>" onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o número do Código de FabricaÃ§Ã£o.');">
			</td>
			<td nowrap width="260"><?// HD15589?>
				<br>
				<input name ="satisfacao" class ="frm" type ="checkbox" value="t" <? if ($satisfacao == 't') echo "checked"; ?>>&nbsp;
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">30 dias Satisfação DeWALT/Porter Cable</font>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Laudo técnico</font>
				<br>
				<input  name ="laudo_tecnico" class ="frm" type ="text" size ="20" maxlength="50" value ="<? echo $laudo_tecnico; ?>" onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o laudo t?cnico.');">
			</td>
		</tr>
		</table>
		<? } ?>



		<input type="hidden" name="consumidor_cliente">
<?
//		<input type="hidden" name="consumidor_cep">
?>
		<input type="hidden" name="consumidor_rg">

		<table width="650" border="0" cellspacing="5" cellpadding="0" align="center">
		<tr class="subtitulo"><td colspan="3">Informações do Consumidor</td></tr>
		<tr>
			<td width="330">
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">
					<span rel="consumidor_nome">Nome Consumidor</span>
				</font>
				<br>
				<input class="frm" id="consumidor_nome" type="text" name="consumidor_nome" size="35" maxlength="50" value="<? echo $consumidor_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?>  <? if ($login_fabrica == 5) { ?> onblur=" fnc_pesquisa_consumidor (document.frm_os.consumidor_nome, 'nome'); displayText('&nbsp;');" <? } ?> onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';  displayText('&nbsp;Insira aqui o nome do Cliente.');">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_nome, "nome")'  style='cursor: pointer'>
			</td>

			<td>
				<? if($login_fabrica==1 or $login_fabrica==7){
					echo '<font size="1" face="Geneva, Arial, Helvetica, san-serif">CPF/CNPJ</font>';
				}
				else{
					echo '<font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="consumidor_cpf">CPF</span></font>';
				}
				?>
				<br>
				<input class="frm" type="text" name="consumidor_cpf" id="consumidor_cpf"<? if($login_fabrica!=1 and $login_fabrica!=7){ echo "onkeyup=\"formata_cpf(this.value,'frm_os');\"";}?>  size="12" maxlength="14" value="<? echo $consumidor_cpf ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_consumidor (document.frm_os.consumidor_cpf,'cpf'); this.className='frm'; displayText('&nbsp;');" <? } ?> onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';  displayText('&nbsp;Digite o CPF do consumidor. Pode ser digitado diretamente, ou separado com pontos e tra?os.');">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_cpf,"cpf")'  style='cursor: pointer'>
			</td>

			<? if($login_fabrica == 1){?>
			<td width="170">
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Tipo Consumidor</font>
				<br>
				<SELECT NAME="fisica_juridica" class="frm"><?php //HD 235182 - Tinha BUG aqui para a Black ?>
					<OPTION VALUE="F" <?php if($fisica_juridica=="F") echo "SELECTED"; ?>>Pessoa Física</OPTION>
					<OPTION VALUE="J" <?php if($fisica_juridica=="J") echo "SELECTED"; ?>>Pessoa Jurídica</OPTION>
				</SELECT>
			</td>
			<?}?>
			<? if($login_fabrica <> 1){?>
				<td width="140">&nbsp;</td>
			<? } ?>
			</tr>
			<tr>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="consumidor_fone">Fone</span></font>
				<br>
				<? // HD 31122 ?>
				<input class="frm" id="consumidor_fone" type="text" name="consumidor_fone" id='consumidor_fone' size="15" maxlength="20" value="<? echo $consumidor_fone ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='consumidor_cep'>CEP</span></font>
				<br>
				<input class="frm" id="consumidor_cep" type="text" name="consumidor_cep" id="consumidor_cep"   size="10" maxlength="10" value="<? echo $consumidor_cep ?>" onblur="this.className='frm'; displayText('&nbsp;'); buscaCEP(this.value, document.frm_os.consumidor_endereco, document.frm_os.consumidor_bairro, document.frm_os.consumidor_cidade, document.frm_os.consumidor_estado) ;" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o CEP do consumidor.');">
			</td>
			<td>&nbsp;</td>

		</tr>
		</table>

		<table width='650' align='center' border='0' cellspacing='2' cellpadding='2'>
<tr class="top">
	<td width="300"><font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="consumidor_endereco">Endereço</span></font></td>

	<td width="170"><font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="consumidor_numero">Número</span></font></td>

	<td><font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="consumidor_complemento">Complemento</span></font></td>

</tr>

<tr>
	<td width="300">
		<input class="frm" id="consumidor_endereco" type="text" name="consumidor_endereco" id="consumidor_endereco"  size="30" maxlength="60" value="<? echo $consumidor_endereco ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o endere?o do consumidor.');">
	</td>

	<td width="69">
		<input class="frm" id="consumidor_numero" type="text" name="consumidor_numero" id="consumidor_numero"  size="10" maxlength="20" value="<? echo $consumidor_numero ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o número do endere?o do consumidor.');">
	</td>

	<td>
		<input class="frm" id="consumidor_complemento" type="text" name="consumidor_complemento"  id="consumidor_complemento" size="15" maxlength="30" value="<? echo $consumidor_complemento ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o complemento do endere?o do consumidor.');">
	</td>


</tr>

<tr>
	<td><font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="consumidor_bairro">Bairro</span></font></td>

	<td><font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="consumidor_cidade">Cidade</span></font></td>

	<td><font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="consumidor_estado">Estado</span></font></td>
</tr>

<tr>
	<td>
		<input class="frm" id="consumidor_bairro" type="text" name="consumidor_bairro" id="consumidor_bairro"  size="15" maxlength="30" value="<? echo $consumidor_bairro ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o bairro do consumidor.');">
	</td>

	<td>
		<input class="frm" id="consumidor_cidade" type="text" name="consumidor_cidade" id="consumidor_cidade"  size="15" maxlength="50" value="<? echo $consumidor_cidade ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite a cidade do consumidor.');" onkeypress="return  BloqueiaNumeros(event)">
	</td>

	<td>
		<!--<input class="frm" type="text" name="consumidor_estado" id="consumidor_estado"  size="2" maxlength="2" value="<? echo $consumidor_estado ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o estado do consumidor.');" onkeypress="return  BloqueiaNumeros(event)"> -->

		<select name="consumidor_estado" class='frm' id="consumidor_estado">
			<option value=""   <? if (strlen($consumidor_estado) == 0)    echo " selected "; ?>></option>
			<option value="AC" <? if ($consumidor_estado == "AC") echo " selected "; ?>>AC</option>
			<option value="AL" <? if ($consumidor_estado == "AL") echo " selected "; ?>>AL</option>
			<option value="AM" <? if ($consumidor_estado == "AM") echo " selected "; ?>>AM</option>
			<option value="AP" <? if ($consumidor_estado == "AP") echo " selected "; ?>>AP</option>
			<option value="BA" <? if ($consumidor_estado == "BA") echo " selected "; ?>>BA</option>
			<option value="CE" <? if ($consumidor_estado == "CE") echo " selected "; ?>>CE</option>
			<option value="DF" <? if ($consumidor_estado == "DF") echo " selected "; ?>>DF</option>
			<option value="ES" <? if ($consumidor_estado == "ES") echo " selected "; ?>>ES</option>
			<option value="GO" <? if ($consumidor_estado == "GO") echo " selected "; ?>>GO</option>
			<option value="MA" <? if ($consumidor_estado == "MA") echo " selected "; ?>>MA</option>
			<option value="MG" <? if ($consumidor_estado == "MG") echo " selected "; ?>>MG</option>
			<option value="MS" <? if ($consumidor_estado == "MS") echo " selected "; ?>>MS</option>
			<option value="MT" <? if ($consumidor_estado == "MT") echo " selected "; ?>>MT</option>
			<option value="PA" <? if ($consumidor_estado == "PA") echo " selected "; ?>>PA </option>
			<option value="PB" <? if ($consumidor_estado == "PB") echo " selected "; ?>>PB</option>
			<option value="PE" <? if ($consumidor_estado == "PE") echo " selected "; ?>>PE</option>
			<option value="PI" <? if ($consumidor_estado == "PI") echo " selected "; ?>>PI</option>
			<option value="PR" <? if ($consumidor_estado == "PR") echo " selected "; ?>>PR</option>
			<option value="RJ" <? if ($consumidor_estado == "RJ") echo " selected "; ?>>RJ</option>
			<option value="RN" <? if ($consumidor_estado == "RN") echo " selected "; ?>>RN</option>
			<option value="RO" <? if ($consumidor_estado == "RO") echo " selected "; ?>>RO</option>
			<option value="RR" <? if ($consumidor_estado == "RR") echo " selected "; ?>>RR</option>
			<option value="RS" <? if ($consumidor_estado == "RS") echo " selected "; ?>>RS</option>
			<option value="SC" <? if ($consumidor_estado == "SC") echo " selected "; ?>>SC</option>
			<option value="SE" <? if ($consumidor_estado == "SE") echo " selected "; ?>>SE</option>
			<option value="SP" <? if ($consumidor_estado == "SP") echo " selected "; ?>>SP</option>
			<option value="TO" <? if ($consumidor_estado == "TO") echo " selected "; ?>>TO</option>
		</select>
	</td>
</tr>

<tr class="top">
	<td><font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="consumidor_email">E-mail</span></font>
		<BR>
		<input class="frm" id="consumidor_email" type="text" name="consumidor_email"  size="30" maxlength="50" value="<? echo $consumidor_email ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';">
	</td>
<? if ($login_fabrica==7) { ?>

	<td>
		<font size="1" face="Geneva, Arial, Helvetica, san-serif">Distância Cliente (KM)</font>
		<br>
		<input class="frm" type="text" name="deslocamento_km"   size="14" id='deslocamento_km' maxlength="7" value="<? echo $deslocamento_km ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';">
	</td>
	<td>
	</td>
<?}?>
<? if ($login_fabrica==3 or $login_fabrica == 45 or $login_fabrica == 74) { ?>
	<td>
		<font size="1" face="Geneva, Arial, Helvetica, san-serif">Telefone Celular</font>
		<br>
		<input class="frm" type="text" name="consumidor_celular" id="consumidor_celular"  size="15" maxlength="20" value="<? echo $consumidor_celular ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
	</td>
	<td>
		<font size="1" face="Geneva, Arial, Helvetica, san-serif">Telefone Comercial</font>
		<br>
		<input class="frm" type="text" name="consumidor_fone_comercial"  id="consumidor_fone_comercial" size="15" maxlength="20" value="<? echo $consumidor_fone_comercial ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
	</td>
<?}?>
</tr>
</table>

<?


if( /*HD21590 - O POSTO DEVE ESTAR CARREGADO, SENÃO VAI APRESENTAR ERRO NA BUSCA EM BRANCO*/
	(strlen($posto)> 0) AND
	(
		($login_fabrica==1 AND $posto==6359) OR
		$login_fabrica==30 OR
		($login_fabrica==15 AND $posto==6359) OR
		$login_fabrica==56 OR
		$login_fabrica==57 OR
		$login_fabrica==50 OR
		$login_fabrica==46
	)
	){
	//--== Calculo de Distância com Google MAPS =========================================

	$sql_posto = "SELECT contato_endereco AS endereco,
						 contato_numero   AS numero  ,
						 contato_bairro   AS bairro  ,
						 contato_cidade   AS cidade  ,
						 contato_estado   AS estado
					FROM tbl_posto_fabrica
					WHERE posto   = $posto
					AND   fabrica = $login_fabrica ";

	$res_posto = pg_query($con,$sql_posto);
	if(pg_num_rows($res_posto)>0) {
		$endereco_posto = pg_fetch_result($res_posto,0,endereco).', '.pg_fetch_result($res_posto,0,numero).' '.pg_fetch_result($res_posto,0,bairro).' '.pg_fetch_result($res_posto,0,cidade).' '.pg_fetch_result($res_posto,0,estado);
		if(strlen($distancia_km)==0)$distancia_km=0;
	}

	if(strlen($tipo_atendimento)>0){
		$sql = "SELECT tipo_atendimento,km_google
				FROM tbl_tipo_atendimento
				WHERE tipo_atendimento = $tipo_atendimento";
		$resa = pg_query($con,$sql); echo $sql;
		if(pg_num_rows($resa)>0){
			$km_google = pg_fetch_result($resa,0,km_google);
		}
	}
}
//HD 406478 - MLG - API-Key para o domínio telecontrol.NET.br
//HD 678667 - MLG - Adicionar mais uma Key. Alterado para um include que gerencia as chaves.
include '../gMapsKeys.inc';
?>
<div id="mapa2" style=" width:500px; height:10px;visibility:hidden;position:absolute; ">
<a href='javascript:escondermapa();'>Fechar Mapa</a>
</div><br>
<div id="mapa" style=" width:500px; height:300px;visibility:hidden;position:absolute;border: 1px #FF0000 solid; "></div>
<script src="http://maps.google.com/maps?file=api&v=2&key=<?=$gAPI_key?>" type="text/javascript"></script>
<script language="javascript">
var map;
function initialize(busca_por){
	// Carrega o Google Maps
	if (GBrowserIsCompatible()) {

		map = new GMap2(document.getElementById("mapa"));
		map.setCenter(new GLatLng(-25.429722,-49.271944), 11)

		// Cria o objeto de roteamento
		 var dir = new GDirections(map);

		//hd 40389
		var pt1 = document.getElementById("contato_cep").value;
		var pt2 = document.getElementById("consumidor_cep").value;

		pt1 = pt1.replace('-','');
		pt2 = pt2.replace('-','');

		if (pt1.length != 8 || pt2.length !=8) {
			//alert ('CEP inválido');
			busca_por = 'endereco';
		}else{
			pt1 = pt1.substr(0,5) + '-' + pt1.substr(5,3);
			pt2 = pt2.substr(0,5) + '-' + pt2.substr(5,3);
		}

		if (busca_por == 'endereco'){
			//alert('por endereco');
			//var pt1 = document.getElementById("ponto1").value
			var pt1 = document.getElementById("contato_endereco").value + ", "+ document.getElementById("contato_numero").value + " " + document.getElementById("contato_bairro").value + " " + document.getElementById("contato_cidade").value + " " + document.getElementById("contato_estado").value;

			var pt2 = document.getElementById("consumidor_endereco").value + ", "+ document.getElementById("consumidor_numero").value + " " + document.getElementById("consumidor_bairro").value + " " + document.getElementById("consumidor_cidade").value + " " + document.getElementById("consumidor_estado").value;
		}
		//document.getElementById("ponto1").value = pt1 +"CONSUMIDOR: "+pt2;

		//alert (pt1);
		//alert (pt2);


		document.getElementById('div_end_posto').innerHTML= '<B>Endereço do posto: </b><u>'+ pt1+'</u>';

		 // Carrega os pontos dados os endereços
		dir.loadFromWaypoints([pt1,pt2], {locale:"pt-br", getSteps:true});
		// O evento load do GDirections é executado quando chega o resultado do geocoding.
		GEvent.addListener(dir,"load", function() {
			//alert('entrou...');
			for (var i=0; i<dir.getNumRoutes(); i++) {
					var route = dir.getRoute(i);
					var dist = route.getDistance()
					var x = dist.meters*2/1000;
					var y = x.toString().replace(".",",");
					document.getElementById('distancia_km_conferencia').value = x;
					document.getElementById('distancia_km').value             = y;
					document.getElementById('distancia_km_maps').value        ='maps';
					document.getElementById('div_mapa_msg').innerHTML='Distância calculada <a href= "javascript:vermapa();">Ver mapa</a>';
			 }
		});
		GEvent.addListener(dir,"error", function() {
			//alert('Nao encontrou ou deu erro');
			initialize('endereco');
		});
	}
}
function compara(campo1,campo2){
	var num1 = campo1.value.replace(".",",");
	var num2 = campo2.value.replace(".",",");
	if(num1!=num2){
		document.getElementById('div_mapa_msg').style.visibility = "visible";
		document.getElementById('div_mapa_msg').innerHTML = 'A distância percorrida pelo técnico estará sujeito a auditoria';
	}else{
		document.getElementById('div_mapa_msg').style.visibility = "visible";
		document.getElementById('div_mapa_msg').innerHTML='Distância calculada <a href= "javascript:vermapa();">Ver mapa</a>';
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

$(document).ready(function() {
    <?php
        if($login_fabrica == 87){
            echo "$('#tipo_atendimento').focus(function() {busca_atendimento_produto_familia();});";
        }
    ?>

	$("#causa_troca").change(function(){

		var causa_troca_id = $("select#causa_troca").val();

		// alert(causa_troca_id);
		if ( causa_troca_id.length > 0 ){

			$.post("<?php echo $PHP_SELF ?>",{causa_troca_select:causa_troca_id},
				function(resposta){
					$("#causa_raiz").html(resposta);
				}
			);

		}

	});

 });

</script>

<div id='div_mapa' style='background:#efefef;border:#999999 1px solid;font-size:10px;padding:5px;<?if($km_google<>'t') echo "visibility:hidden;position:absolute;";?>' >
<b>Para Calcular a distância percorrida pelo técnico para execução do serviço(ida e volta):<br>
Preencha todos os campos de endereço acima ou preencha o campo de distância</b>
<br><br>
<input  type="hidden" id="ponto1" value="<?=$endereco_posto?>" >
<input  type="hidden" id="distancia_km_maps"  value="" >
<input type='hidden' name='distancia_km_conferencia' id='distancia_km_conferencia' value='<?=$distancia_km_conferencia?>'>

Distância: <input type='text' name='distancia_km' id='distancia_km' value='<?=$distancia_km?>' size='5' onchange="javascript:compara(distancia_km,distancia_km_conferencia)"> Km
<input  type="button" onclick="initialize('')" value="Calcular Distância" size='5' >
<div id='div_mapa_msg' style='color:#FF0000'></div>
<br>
<div id='div_end_posto' style='color:#000000'>
<B>Endereço do posto:</b>
<u>
	<?if(strlen($posto)>0){?>
		<?=$endereco_posto?>
	<?}?>
</u>
</div>
<br>&nbsp;
</div>

<p>

<?
		if ($login_fabrica == 50) {
?>
		<div id='revenda_fixo' style='display:none; background:#efefef; border:#999999 1px solid; width:700px;'>
		<table  width="650' border="0" cellspacing="5" cellpadding="0" align="center">
		<tr class="subtitulo"><td colspan="4">Informações da Revenda</td></tr>
			<tr valign='top'>
				<td width="300">
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome Revenda</font>
					<br>
					<input class="frm" type="text" name="txt_revenda_nome" id="txt_revenda_nome" size="50" maxlength="50" value="" onkeyup="somenteMaiusculaSemAcento(this)" readonly>
				</td>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">CNPJ Revenda</font>
					<br>
					<input class="frm" type="text" name="txt_revenda_cnpj" id="txt_revenda_cnpj" size="20" maxlength="18" id="txt_revenda_cnpj" value="" onkeyup="re = /\D/g; this.value
					= this.value.replace(re, '');" readonly>
				</td>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Fone</font>
				<br>
					<input class="frm" type="text" name="txt_revenda_fone" id="txt_revenda_fone" size="15" maxlength="15"  value="" readonly>
				</td>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Cep</font>
					<br>
					<input class="frm" type="text" name="txt_revenda_cep" id="txt_revenda_cep"  size="10" maxlength="10" value="" readonly>
				</td>
			</tr>
		</table>

		<table  width="650'  border="0" cellspacing="5" cellpadding="0">
		<tr valign='top'>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Cód. EAN</font>
				<br>
				<input class="frm" type="text" name="txt_cod_ean" id="txt_cod_ean" size="30" maxlength="50" value="" readonly>
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data do Faturamento</font>
				<br>
				<input class="frm" type="text" name="txt_data_venda" id="txt_data_venda" size="12" maxlength="10" value="" readonly>

				<input class="frm" type="hidden" name="txt_revenda_endereco"    id="txt_revenda_endereco" value="">
				<input class="frm" type="hidden" name="txt_revenda_numero"      id="txt_revenda_numero" value="">
				<input class="frm" type="hidden" name="txt_revenda_complemento" id="txt_revenda_complemento"  value="">
				<input class="frm" type="hidden" name="txt_revenda_bairro"      id="txt_revenda_bairro"  value="">
				<input class="frm" type="hidden" name="txt_revenda_cidade"      id="txt_revenda_cidade" value="">
				<input class="frm" type="hidden" name="txt_revenda_estado"      id="txt_revenda_estado"  value="" >
				<input class="frm" type="hidden" name="produto_voltagem"      id="produto_voltagem"  value="" >

			</td>
		</tr>
		</table>

		<table  width="650'  border="0" cellspacing="5" cellpadding="0">
			<tr valign='top'>
				<td>
					<font size="2" face="Geneva, Arial, Helvetica, san-serif" color='red'>
					AS INFORMAÇÕES AUTOMÁTICAS QUE ESTÃO ACIMA SÃO AS MESMAS DA NOTA FISCAL DO CONSUMIDOR?
					</font>
				</td>
				<td>
					<input class="frm" type="radio" name="nf_confirma_num_serie" onclick="fnc_num_serie_confirma('sim');" value="sim"> Sim
				</td>
				<td>
					<input class="frm" type="radio" name="nf_confirma_num_serie" onclick="fnc_num_serie_confirma('nao');" value="nao"> Não
				</td>

			</tr>
		</table>
		</div>
<?
		}
?>

		<table width="650" border="0" cellspacing="5" cellpadding="0" align="center">
			<tr class="subtitulo"><td colspan="4">Informações da Revenda</td></tr>
		<tr valign="top">
			<td width="300">
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="revenda_nome">Nome Revenda</span></font>
				<br>
				<input class="frm" type="text" name="revenda_nome" id="revenda_nome" size="30" maxlength="50" value="<? echo $revenda_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o nome da REVENDA onde foi adquirido o produto.');">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, "nome")' style='cursor: pointer' >
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="revenda_cnpj">CNPJ Revenda</span></font>
				<br>
				<input class="frm" type="text" name="revenda_cnpj" size="20" maxlength="18" id="revenda_cnpj" value="<? echo $revenda_cnpj ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o número no Cadastro Nacional de Pessoa Jurídica.'); " onKeyUp="formata_cnpj(this.value, 'frm_os');">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, "cnpj")' style='cursor: pointer'>
			</td>
		</tr>

		<tr>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="nota_fiscal">Nota Fiscal</span></font>
				<br>
				<input class="frm" type="text" name="nota_fiscal" id="nota_fiscal" size="20"  maxlength="20" id="nota_fiscal" value="<? echo $nota_fiscal ?>"
				<?php
				if($login_fabrica==45){?>
					onkeypress="mascara(this,soNumeros);"
				<?php } ?>>
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="data_nf">Data Compra</span></font>
				<br>
				<input class="frm" type="text" name="data_nf"  id="data_nf"  size="12" maxlength="10" value="<? echo $data_nf ?>" tabindex="0" >
			</td>
		</tr>
		<? if($login_fabrica == 6) { ?>
		<tr valign="top">

			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">NF Saída</font>
				<br>
				<input class="frm" type="text" name="nota_fiscal_saida" id="nota_fiscal" size="8"  maxlength="8" id="nota_fiscal" value="<? echo $nota_fiscal_saida ?>">
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data NF Saída</font>
				<br>
				<input class="frm" type="text" name="data_nf_saida"  id="data_nf_saida" size="12" maxlength="10" value="<? echo $data_nf_saida ?>" tabindex="0" >
			</td>
			<td colspan='2'>&nbsp;</td>
		</tr>
		<? } ?>
		</table>

        <?php if($login_fabrica == 87){?>
            <input type='hidden' name='consumidor_revenda' value='C' />
        <?php }else{?>
            <table width="650" border="0" cellspacing="5" cellpadding="2" align="center">
                <tr>
                    <td width="295">
                        <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="aparencia_produto">Aparência do Produto</span></font>
                        <br>
                        <? if ($login_fabrica == 20) {
                            echo "<input type='hidden' name='defeito_reclamado' value='$defeito_reclamado' />";
                            echo "<input type='hidden' name='segmento_atuacao' value='$segmento_atuacao' />";


                            echo "<select name='aparencia_produto' size='1' class='frm'>";
                            echo "<option value=''></option>";

                            echo "<option value='NEW' ";
                            if ($aparencia_produto == "NEW") echo " selected ";
                            echo "> Bom Estado </option>";

                            echo "<option value='USL' ";
                            if ($aparencia_produto == "USL") echo " selected ";
                            echo "> Uso intenso </option>";

                            echo "<option value='USN' ";
                            if ($aparencia_produto == "USN") echo " selected ";
                            echo "> Uso Normal </option>";

                            echo "<option value='USH' ";
                            if ($aparencia_produto == "USH") echo " selected ";
                            echo "> Uso Pesado </option>";

                            echo "<option value='ABU' ";
                            if ($aparencia_produto == "ABU") echo " selected ";
                            echo "> Uso Abusivo </option>";

                            echo "<option value='ORI' ";
                            if ($aparencia_produto == "ORI") echo " selected ";
                            echo "> Original, sem uso </option>";

                            echo "<option value='PCK' ";
                            if ($aparencia_produto == "PCK") echo " selected ";
                            echo "> Embalagem </option>";

                            echo "</select>";
                        }else if($login_fabrica==50){
                            echo "<input class='frm' type='text' name='aparencia_produto' size='30' value='$aparencia_produto' onChange=\"javascript: this.value=this.value.toUpperCase();\" onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on'; displayText('&nbsp;Texto livre com a apar?ncia externa do aparelho deixado no balc?o.');\">";
                        }else{
                            echo "<input class='frm' id='aparencia_produto' type='text' name='aparencia_produto' size='30' value='$aparencia_produto' onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on'; displayText('&nbsp;Texto livre com a apar?ncia externa do aparelho deixado no balc?o.');\">";
                        }
                        ?>
                    </td>
                    <td>
                        <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="acessorios">Acessórios</span></font>
                        <br>
                        <input class="frm" id="acessorios" type="text" name="acessorios" size="30" value="<? echo $acessorios ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Texto livre com os acess?rios deixados junto ao produto.');">
                    </td>
        <? if ($login_fabrica == 1 AND 1==2) { # retirado por Fabio a pedido da Lilian - 28/12/2007 - Cadastro de troca somente na OS TROCA?>
                    <td>
                        <font size="1" face="Geneva, Arial, Helvetica, san-serif">Troca faturada</font><br>
                        <input class="frm" type="checkbox" name="troca_faturada" value="t"<? if ($troca_faturada == 't') echo " checked";?>>
                    </td>
        <? } ?>
                </tr>

                <tr>
                    <? if ($login_fabrica == 7) { # HD 32143 ?>
                            <input type='hidden' name="consumidor_revenda" value='<? if (strlen($consumidor_revenda)==0 or strlen($os)==0) {echo 'C';}else{echo $consumidor_revenda;}?>'>
                    <?}else{?>
                    <td COLSPAN="2">
                        <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="consumidor_revenda">Consumidor</span></font>&nbsp;<input class='consumidor_revenda' type="radio" name="consumidor_revenda" value='C' <? if (strlen($consumidor_revenda) == 0 OR $consumidor_revenda == 'C') echo "checked"; ?> <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?>>
                        <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="consumidor_revenda">Revenda</span></font>&nbsp;<input class='consumidor_revenda' type="radio" name="consumidor_revenda" value='R' <? if ($consumidor_revenda == 'R') echo " checked"; ?>>&nbsp;&nbsp;</td>
                    <? } ?>
                </tr>
            </table>
        <?php }?>


		<p >

		<center>
		<? if ($login_fabrica == 3){ #Chamado 11083 - Fabio ?>
			<? if($orientacao_sac!="null" AND strlen($orientacao_sac)>0) { ?>

				<div style="width:650px;" class="subtitulo">Orientações do SAC ao Posto Autorizado</div>

				<br>
				<textarea name='orientacao_sac_anterior' rows='4' cols='100' readonly='readonly' style='background-color:#FBFBFB;border:1px solid #4D4D4D' class="frm"><? echo trim($orientacao_sac); ?></textarea>
				<br />
			<? } ?>

			<div style="width:650px;" class="subtitulo">Adicionar Orientação do SAC ao Posto Autorizado</div>

			<br/>
			<?
			if ($login_fabrica == 3 and strlen($os) > 0){

				$sql = "SELECT
								tbl_os_troca.os
						FROM   tbl_os_troca
						WHERE   tbl_os_troca.os      = $os
						AND     tbl_os_troca.fabric = $login_fabrica
						AND     tbl_os_troca.pedido IS NOT NULL;";
				$res_troca = pg_query($con, $sql);
				$msg_erro        = pg_errormessage($con);
				if (pg_num_rows ($res_troca) > 0 ){
					$alterar_os = 'f';
				}
			}
			?>

			<textarea name='orientacao_sac' rows='4' cols='100' class="frm"></textarea>
			<? if ($alterar_os == 'f') {?>
			<br><img src='imagens/btn_gravar.gif' style onclick="atuSac(document.frm_os.orientacao_sac.value,<? echo $os;?>);">
			<?}?>

			<? if(strlen($os)>0){ ?>
			<BR><BR>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Motivo da Exclusão</font>
			<BR><INPUT TYPE="text" SIZE="50" NAME="obs_exclusao" VALUE="<? echo $obs_exclusao; ?>">
			<? } ?>
		<? }else{
			if ($login_fabrica == 11) {?>
				<div style="width:650px;" class="subtitulo">
				Orientações do SAC ao Posto Autorizado
				</div>
				<br>
				<textarea name='orientacao_sac' rows='4' cols='75' class="frm"></textarea>
			<? } else {?>
				<div style="width:650px;" class="subtitulo">
				Orientações do SAC ao Posto Autorizado
				</div>
				<br>
				<textarea name='orientacao_sac' rows='4' cols='75' class="frm" id="orientacao_sac"><? echo trim($orientacao_sac); ?></textarea>
			<? }?>
		<? } ?>
		<br />
		</center>
		<? if ($os != '') { ?>
		<br/>
		<table width='650' align='center' border='0' cellspacing='2' cellpadding='2'>
			<tr >
				<td>Histórico de Orientações do SAC ao Posto Autorizado  </td>
			</tr>
		</table>

		<table width='640' border="0" align="center" cellspacing="5" cellpadding="3" bgcolor='#CCCCCC' class="bordasimples">
			<tr>
				<td>
					<? echo trim($orientacao_sac);?>
				</td>
			</tr>
		<table>
		<? }?>
<!--		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
		<hr>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Apar?ncia do Produto</font>
				<br>
				<input class="frm" type="text" name="aparencia_produto" size="35" value="<? echo $aparencia_produto ?>" >
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Acess?rios</font>
				<br>
				<input class="frm" type="text" name="acessorios" size="35" value="<? echo $acessorios ?>" >
			</td>
		</tr>
		</table>-->

		<?
		if ($login_fabrica <> 7) {
			echo "<input class='frm' type='hidden' name='obs' size='50' value='$obs'>";
			echo "<!-- ";
		}
		?>

		<table width="650" border="0" cellspacing="5" cellpadding="0" align="center">
			<tr class="subtitulo"><td colspan="3">Informações do Chamado</td></tr>
		<tr>

			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Chamado Aberto por</font>
				<br>
				<input class="frm" type="text" name="quem_abriu_chamado" size="20" maxlength="30" value="<? echo $quem_abriu_chamado ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Nome do funcionário do cliente que abriu este chamado.');">
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Observações</font>
				<br>
				<input class="frm" type="text" name="obs" size="50" value="<? echo $obs ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;ObservaçÕes e dados adicionais desta OS.');">
			</td>
		</tr>
		</table>


		<table width="650" border="0" cellspacing="5" cellpadding="0" align="center" class="formulario">
		<tr valign='top'>
			<td valign='top'>
				<fieldset class='valores' style='height:140px;'>
				<legend>Deslocamento</legend>
					<div>
					<?	/*HD: 55895*/
					if ($login_fabrica <> 7) {?>
						<label for="cobrar_deslocamento">Isento:</label>
						<input type='radio' name='cobrar_deslocamento' value='isento' onClick='atualizaCobraDeslocamento(this)' <? if (strtolower($cobrar_deslocamento) == 'isento') echo "checked";?>>
						<br>
					<?}?>
					<label for="cobrar_deslocamento">Por Km:</label>
					<input type='radio' name='cobrar_deslocamento' value='valor_por_km' <? if ($cobrar_deslocamento == 'valor_por_km') echo " checked " ?> onClick='atualizaCobraDeslocamento(this)'>

					<br />
					<label for="cobrar_deslocamento">Taxa de Visita:</label>
					<input type='radio' name='cobrar_deslocamento' value='taxa_visita' <? if ($cobrar_deslocamento == 'taxa_visita') echo " checked " ?> onClick='atualizaCobraDeslocamento(this)'>
					<br />
					</div>

					<div name='div_taxa_visita' <? if ($cobrar_deslocamento != 'taxa_visita') echo " style='display:none' "?>>
						<label for="taxa_visita">Valor:</label>
						<input type='text' name='taxa_visita' value='<? echo number_format($taxa_visita ,2,',','.') ?>' class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8'>
						<br />
					</div>

					<div <? if ($cobrar_deslocamento != 'valor_por_km' or strlen($cobrar_deslocamento)==0) echo " style='display:none' " ?> name='div_valor_por_km'>
						<label for="veiculo">Carro:</label>
						<input type='radio' name='veiculo' value='carro' onClick='atualizaValorKM(this)' <? if (strtolower($veiculo) != 'caminhao') echo "checked";?>>
						<input type='text' name='valor_por_km_carro' value='<? echo number_format($valor_por_km_carro,2,',','.') ?>' class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8' >
						<br>
						<label for="veiculo">Caminhão:</label>
						<input type='radio' name='veiculo' value='caminhao' onClick='atualizaValorKM(this)' <? if (strtolower($veiculo) == 'caminhao') echo "checked";?> >
						<input type='text' name='valor_por_km_caminhao' class='frm' value='<? echo number_format($valor_por_km_caminhao,2,',','.') ?>' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8'>
						<input type='hidden' name='valor_por_km' value='<? echo $valor_por_km ?>'>
					</div>

<?if  (1==2){ #HD 32483 ?>
					<div <? if ($cobrar_deslocamento == 'isento' OR strlen($cobrar_deslocamento)==0) echo " style='display:none' " ?> name='div_desconto_deslocamento'>
						<label>Desconto:</label>
						<input type='text' name='desconto_deslocamento' value="<? echo $desconto_deslocamento ?>" class='frm' onblur="this.className='frm';" onfocus="this.className='frm-on';" size='6' maxlength='6'> %
					</div>
<?}?>
				</fieldset>
			</td>
			<td>
				<fieldset class='valores' style='height:140px;'>
					<legend>Mão de Obra</legend>
					<div>
					<label for="cobrar_hora_diaria">Diária:</label>
					<input type='radio' name='cobrar_hora_diaria' value='diaria' onClick='atualizaCobraHoraDiaria(this)' <? if (strtolower($cobrar_hora_diaria) == 'diaria') echo "checked";?>>
					<br>
					<label for="cobrar_hora_diaria">Hora Técnica:</label>
					<input type='radio' name='cobrar_hora_diaria' value='hora' onClick='atualizaCobraHoraDiaria(this)' <? if (strtolower($cobrar_hora_diaria) == 'hora') echo "checked";?>>
					<br>
					</div>
					<div <? if ($cobrar_hora_diaria != 'hora') echo " style='display:none' " ?> name='div_hora'>
						<label>Valor:</label>
						<input type='text' name='hora_tecnica' value='<? echo number_format($hora_tecnica,2,',','.') ?>' class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8'>
						<br>
<?/*						<!--<br>
						<label>Desconto:</label>
						<input type='text' name='desconto_hora_tecnica' value="<? echo $desconto_hora_tecnica ?>" class='frm' onblur="this.className='frm';" onfocus="this.className='frm-on';" size='6' maxlength='6'> %-->
*/?>
					</div>
					<div <? if ($cobrar_hora_diaria != 'diaria') echo " style='display:none' " ?> name='div_diaria'>
						<label>Valor:</label>
						<input type='text' name='valor_diaria' value="<? echo number_format($valor_diaria,2,',','.') ?>" class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8'>
						<br>
<?/*						<!--						<br>
						<label>Desconto:</label>
						<input type='text' name='desconto_diaria' value="<? echo $desconto_diaria ?>" class='frm' onblur="this.className='frm';" onfocus="this.className='frm-on';" size='6' maxlength='6'> %
-->
*/?>
					</div>
				</fieldset>
			</td>
			<td>
				<fieldset class='valores' style='height:140px;'>
					<legend>Outros Serviços</legend>
					<div>
						<label>Regulagem:</label>
						<input type="checkbox" name="cobrar_regulagem" value="t" <? if ($cobrar_regulagem=='t') echo "checked" ?>>
						<br />
						<label>Valor:</label>
						<input type="text" name="regulagem_peso_padrao" value="<? echo number_format($regulagem_peso_padrao,2,',','.') ?>"  class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8'>
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
						<input type="text" name="certificado_conformidade" value="<? echo number_format($certificado_conformidade,2,',','.') ?>"  class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8'>
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
		<tr style='font-size:10px' valign='top'>
			<td nowrap  valign='top'>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>% DESCONTO PEÇAS</font><BR>
				<input type='text' name='desconto_peca' class='frm' value='<?=$desconto_peca?>' size='10' maxlength='5'>
			</td>
			<td nowrap  valign='top'>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Condição de Pagamento</font><BR>
				<SELECT NAME='condicao' style='width:150px' class="frm">
				<OPTION VALUE=''></OPTION>
				<?
				$sql = " SELECT condicao,
								codigo_condicao,
								descricao
						FROM tbl_condicao
						WHERE fabrica = $login_fabrica
						AND visivel is true
						ORDER BY codigo_condicao ";
				$res = pg_query ($con,$sql) ;
				for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
					echo "<option ";
					if ($condicao== pg_fetch_result ($res,$i,condicao) ) echo " selected ";
					echo " value='" . pg_fetch_result ($res,$i,condicao) . "'>" ;
					echo pg_fetch_result ($res,$i,descricao) ;
					echo "</option>";
				}
				?>
				</SELECT>
			</td>
				<?
				echo "<td valign='bottom'>";
				if ($login_fabrica == 7) {
					echo "<input type='checkbox' name='imprimir_os' id='imprimir_os' value='imprimir'> <font size='1' face='Geneva, Arial, Helvetica, san-serif'>Imprimir OS</font>";
				}
				echo "</td>";
			?>
		</tr>
	</table>

		<?
		if ($login_fabrica <> 7) {
			echo " --> ";
		}
		?>

	</td>

</tr>
</table>

<table width="700" border="0" class="formulario" cellspacing="5" cellpadding="0" align="center">
<tr>
	<td height="27" valign="middle" align="center" >

		<input type="hidden" name="btn_acao" value="">
<?


/*IGOR HD: 47695 - 17/12/2008*/
if ($login_fabrica == 7 AND strlen($os) > 0){
	$sql = "SELECT
					tbl_os_item.pedido
			FROM    tbl_os_item
			JOIN    tbl_os_produto             USING (os_produto)
			JOIN    tbl_os                     USING (os)
			JOIN    tbl_pedido                 ON tbl_os_item.pedido = tbl_pedido.pedido
			WHERE   tbl_os.os      = $os
			AND     tbl_os.fabrica = $login_fabrica
			AND     tbl_pedido.tipo_pedido <> 144;";

	$res_pedido = pg_query($con, $sql);
	$msg_erro        = pg_errormessage($con);

	if (pg_num_rows ($res_pedido) > 0 ){
		$alterar_os = false;
	}else{
		$alterar_os = true;
	}
}
// HD 68376
if ($login_fabrica == 3 and strlen($os) > 0){
	$sql = "SELECT
					tbl_os_troca.os
			FROM   tbl_os_troca
			WHERE   tbl_os_troca.os      = $os
			AND     tbl_os_troca.fabric = $login_fabrica
			AND     tbl_os_troca.pedido IS NOT NULL;";
	$res_troca = pg_query($con, $sql);
	$msg_erro        = pg_errormessage($con);
	if (pg_num_rows ($res_troca) > 0 ){
		$alterar_os = 'f';
	}
}

if (strlen ($os) > 0) {

		echo "<a href='" . $PHP_SELF . "?os=$os&osacao=trocar' title='Clique para abrir a tela de troca de produto'><img src='imagens/btn_trocarcinza.gif' style='cursor:pointer' border='0'></a>&nbsp;";
		echo "<img src='imagens/btn_alterarcinza.gif' style='cursor:pointer' ";
		/* HD: 47695 */
		if($login_fabrica<> 7  OR ($login_fabrica== 7 AND $alterar_os)){
			if ($login_fabrica == 3 and $alterar_os =='f') { // HD68376
				echo " onclick=\"javascript: alert('Produto já trocado, não pode alterar'); return false;\"";
			}else{
				if (in_array($login_fabrica,$fabricas_validam_campos_telecontrol)){
					echo " onclick=\"func_submit_os()\" ";
				}else{
					echo " onclick=\"javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='continuar' ;  document.frm_os.submit() } else { alert ('Dados já gravados. Se você clicou em Voltar no seu browser ou clicou mais de uma vez no botão, acesse novamente a tela pelos Menus do sistema.') }\" ";
				}
			}
			echo " ALT='Alterar os itens da Ordem de Serviço' ";
		}else{
			echo " ALT='Ordem de Serviço bloqueada para alteração por ter pedido gerado.' ";
		}
		echo "border='0'>";

		if($login_fabrica==11 AND strlen($data_fechamento)==0){
		?>
			<img src='imagens_admin/btn_fechar3.gif' style="cursor:pointer" onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { if(confirm('Deseja realmente fechar esta OS?') == true) { document.frm_os.btn_acao.value='fechar_os'; document.frm_os.submit(); }else{ return; }; } else { alert ('Dados já gravados. Se você clicou em Voltar no seu browser ou clicou mais de uma vez no botão, acesse novamente a tela pelos Menus do sistema.') }" ALT="Fechar Ordem de Serviço" border='0'>
		<? } ?>

		<img src='imagens_admin/btn_apagar.gif' style="cursor:pointer" onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { if(confirm('Deseja realmente apagar esta OS?') == true) { document.frm_os.btn_acao.value='apagar'; document.frm_os.submit(); }else{ return; }; } else { alert ('Dados já gravados. Se você clicou em Voltar no seu browser ou clicou mais de uma vez no botão, acesse novamente a tela pelos Menus do sistema.') }" ALT="Apagar a Ordem de Serviço" border='0'>
<?
}else{
	if (!$validacao_dados_telecontrol){
?>
		<input type="button" style='background:url(imagens_admin/btn_continuar.gif); width:95px; cursor:pointer;' value="&nbsp;"  onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='continuar' ; document.frm_os.submit() } else { alert ('Dados já gravados. Se você clicou em Voltar no seu browser ou clicou mais de uma vez no botão, acesse novamente a tela pelos Menus do sistema.') } return false;" ALT="Continuar com Ordem de Serviço" border='0'>
<?
	}else{
?>
		<input type="button" style='background:url(imagens_admin/btn_continuar.gif); width:95px; cursor:pointer;' value="&nbsp;"  onclick="func_submit_os()" ALT="Continuar com Ordem de Serviço" border='0'>
<?
	}
}
?>
	</td>
</tr>
<!-- HD 194731: Coloquei o formulário da OS inteiro dentro de uma tag table para dar
display:none quando ele não deve estar disponível -->
<?php
	if(strlen($os)>0){
?>
<tr><td>
	<table width='700 align='center' border='0' cellspacing='0' style='border:solid 1px;' class='formulario'>
	<tr >
	<td align='center' style='color: #3300cc;'>
	 O recurso TROCAR está disponível através do botão TROCAR na tela de consulta de OS ou através do botão de ação nesta tela
	</td>
	</tr>
	</table>
</td></tr>
<?php } ?>
</table></td>

</tr>

</table>

<input type='hidden' name = 'revenda_fone'>
<input type='hidden' name = 'revenda_cidade'>
<input type='hidden' name = 'revenda_estado'>
<input type='hidden' name = 'revenda_endereco'>
<input type='hidden' name = 'revenda_numero'>
<input type='hidden' name = 'revenda_complemento'>
<input type='hidden' name = 'revenda_bairro'>
<input type='hidden' name = 'revenda_cep'>
<input type='hidden' name = 'revenda_email'>

</form>

<p>

<?
if(strlen($os) > 0) {
	if($login_fabrica == 11 OR $login_fabrica == 45 ){
/*		echo "<form method='post' name='frm_cancelar' action='$PHP_SELF?os=$os'>";
		echo "<table width='600' align='center' border='2' cellspacing='0' bgcolor='#F7D7D7' style='' class=''>";
		echo "<input type='hidden' name='os' value='$os'>";
		echo "<input type='hidden' name='cancelar' value=''>";
		echo "<tr>";
		echo "<td align='center' style='color: #F7D7D7'> ";
		echo "<font color='#3300CC' size='+1'> <b>Cancelar OS?</b> </font> ";
			echo "<table border='0' cellspacing='0' width='600'>";
			echo "<tr bgcolor='#F7D7D7' class='Conteudo'>";
			echo "<td align='left'><b>Motivo:</b></td>";
			echo "<td align='left'><textarea name='motivo_cancelamento' cols='100' rows='3' class='Caixa'>$motivo_cancelamento</textarea></td>";
			echo "</tr>";
			echo "</table>";
		echo "<input type='button' value='Cancelar' name='btn_cancelar' id='btn_cancelar' onclick=\"javascript: cancelar_os();\">";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		echo "</form>";
*/

	#HD 308346
	if(strlen($os)>0){
		$sqlStatus = "SELECT to_char(data, 'dd/mm/yyyy') AS data,
						(select descricao from tbl_status_os where tbl_status_os.status_os = tbl_os_status.status_os) AS status_os ,
						observacao,
						(select nome_completo from tbl_admin where tbl_admin.admin = tbl_os_status.admin) AS admin,
						max(os_status)
					FROM tbl_os_status
					WHERE tbl_os_status.os = $os
					GROUP BY data,
					status_os    ,
					observacao   ,
					admin";
		$resStatus = pg_exec($con, $sqlStatus);

		if(pg_numrows($resStatus)>0){
			$data       = pg_result($resStatus,0,data);
			$status_os  = pg_result($resStatus,0,status_os);
			$observacao = pg_result($resStatus,0,observacao);
			$admin      = pg_result($resStatus,0,admin);

			echo '<br>';
			echo "<table width='500' align='center' border='0' cellspacing='1' cellpadding='0' class='Tabela'>";
			echo "<tr class='titulo_tabela'>";
				echo "<td colspan='4'>Status da OS</td>";
			echo '</tr>';
			echo "<tr class='subtitulo'>";
				echo '<td>Data</td>';
				echo '<td>Status</td>';
				echo '<td>Obs</td>';
				echo '<td>Admin</td>';
			echo '</tr>';
			echo "<tr style='background-color: #ffffff; font-size: 9pt;'>";
				echo "<td>$data</td>";
				echo "<td>$status_os</td>";
				echo "<td>$observacao</td>";
				echo "<td>$admin</td>";
			echo '</tr>';
			echo '</table>';
			echo '<br>';
		}
	}
	#HD 308346 - Fim

		#HD49669 . Efetuando este cancelamento não verifica se está em extrato e se tiver não faz o recalculo
		$sql2 = "SELECT extrato FROM tbl_extrato JOIN tbl_os_extra using(extrato) WHERE tbl_os_extra.os = $os AND tbl_os_extra.extrato IS NOT NULL;";
		$res2 = pg_query($con,$sql2);
		//HD 194731: Coloquei nas tables abaixo um display com variável para ocultar quando for troca
		if(pg_num_rows($res2) == 0){

			echo "<table width='500' align='center' border='2' cellspacing='0' bgcolor='#FFDDDD' style='border:#660000 1px solid; display: <? echo $display_frm_os; ?>' >";
			echo "<tr>";
			echo "<td align='center' style='color: #ffffff'> ";
			echo "<a href='os_cancelar.php?os=$os' style='font-size:12px'>Cancelar esta OS e informar o motivo</a>";
			echo "</td>";
			echo "</tr>";
			echo "</table>";
			echo "<center><br>";

		}else{
			$extrato_rec = pg_fetch_result($res2,0,0);
			echo "<table width='500' align='center' border='2' cellspacing='0' bgcolor='#FFDDDD' style='border:#660000 1px solid; display: <? echo $display_frm_os; ?>' >";
			echo "<tr>";
			echo "<td align='center' style='color: #ffffff'> ";
			echo "<b><FONT SIZE='3' COLOR='#330000'>Esta OS não pode ser cancelada pois está no extrato $extrato_rec. Para cancelar acesse o extrato correspondente.</FONT></b>";
			echo "</td>";
			echo "</tr>";
			echo "</table>";
			echo "<center><br>";
		}

	}

	if ($login_fabrica == 81) {
		$cond_pedido_cancelado = "OR tbl_pedido.status_pedido=14";
	}

	$sql = "
	SELECT
	os_troca

	FROM
	tbl_os_troca
	LEFT JOIN tbl_pedido ON tbl_os_troca.pedido=tbl_pedido.pedido

	WHERE
	os=$os
	AND (tbl_os_troca.pedido IS NULL
	$cond_pedido_cancelado)
	";
	$res = pg_query ($con,$sql); echo $sql;

	if (pg_num_rows($res)) {
		$display_frm_troca = "block";
		$troca_efetuada = false;
	}

	//HD 205476: Permitir trocar novamente produtos que tenham todos os itens da OS cancelados no pedido, sem
	//necessariamente cancelar o pedido total (status_pedido == 14)
	if ($login_fabrica == 81) {
		$sql = "
		SELECT
		COUNT(*)

		FROM
		tbl_os_item
		JOIN tbl_os_produto ON tbl_os_item.os_produto=tbl_os_produto.os_produto
		JOIN tbl_os ON  tbl_os_produto.os=tbl_os.os
		JOIN tbl_pedido_item ON tbl_os_item.pedido_item=tbl_pedido_item.pedido_item
		JOIN tbl_pedido ON tbl_pedido_item.pedido=tbl_pedido.pedido

		WHERE
		tbl_os.os=$os
		AND tbl_os.fabrica=$login_fabrica
		AND tbl_pedido_item.qtde=tbl_pedido_item.qtde_cancelada
		";
		$res_item_cancelado = pg_query($con, $sql);

		$sql = "
		SELECT
		COUNT(tbl_os_item.os_item)

		FROM
		tbl_os
		JOIN tbl_os_produto ON tbl_os.os=tbl_os_produto.os
		JOIN tbl_os_item ON tbl_os_produto.os_produto=tbl_os_item.os_produto

		WHERE
		tbl_os.os=$os
		AND tbl_os_item.pedido_item IS NOT NULL
		AND tbl_os.fabrica=$login_fabrica
		";
		$res_total_item = pg_query($con, $sql);

		$total_cancelados = pg_result($res_item_cancelado, 0, 0);
		$total_itens_os = pg_result($res_total_item, 0, 0);

		if ($total_cancelados == $total_itens_os && $total_cancelados > 0) {
			$display_frm_troca = "block";
			$troca_efetuada = false;
		}

	}

	//HD 194731: Coloquei o formulário da TROCA DE OS inteiro dentro de uma tag table para dar
	//display:none quando ele não deve estar disponível
	?>

	<table width="100%" border="0">
	<table style="display:<?=$display_frm_troca?>" align="center" width="700">

		<tr>
			<td>
	<?
	$s = $_GET['s'];
	if(pg_num_rows($res)>0 ) {
		if (!$s){
			echo "<table width='700' align='center' cellspacing='0'  class='sucesso'>";
			echo "<tr>";
			echo "<td align='center' > ";
				echo "";
				echo "Produto já trocado anteriormente!";
				if ($ok != 's') {
					echo "<br>Preencha o formulário abaixo para trocar o produto novamente!";
				}
				echo "";
			echo "</td>";
			echo "</tr>";
			echo "</table>";
		}
	}
	else {
		$xsql = "SELECT  os_troca    ,
						gerar_pedido ,
						referencia   ,
						descricao    ,
						ressarcimento
				FROM   tbl_os_troca
				LEFT JOIN   tbl_peca USING(peca)
				WHERE  os = $os
				AND    gerar_pedido IS NOT TRUE";
		$xres = pg_query ($con,$xsql);
		if(pg_num_rows($xres)>0) {
			$troca_efetuada      =  pg_fetch_result($xres,0,os_troca);
			$troca_referencia    =  pg_fetch_result($xres,0,referencia);
			$troca_descricao     =  pg_fetch_result($xres,0,descricao);
			$troca_ressarcimento = pg_fetch_result($xres,0,ressarcimento);
			echo "<table width='500' align='center' border='2' cellspacing='0' bgcolor='#E8EEFF' style='border:#3300CC 1px solid;' >";
			echo "<tr>";
			if($troca_ressarcimento=='t') echo "<td align='center' ><h1>Ressarcimento Financeiro</h1> ";
			else                          echo "<td align='center' ><h1>Troca pelo produto: $troca_referencia - $troca_descricao</h1> ";
			echo "<a href='$PHP_SELF?os=$os&gerar_pedido=ok' style='font-size:12px'>Esta troca não irá gerar pedido!<br> Clique aqui para que esta troca gere pedido </A>";
			echo "</td>";
			echo "</tr>";
			echo "</table><br>";
		}
	}

	//HD 194731: Informações do produto e posto
	$sql = "
	SELECT
	tbl_produto.produto,
	tbl_produto.referencia,
	tbl_produto.descricao,
	tbl_posto.nome

	FROM
	tbl_os
	JOIN tbl_produto ON tbl_os.produto=tbl_produto.produto
	JOIN tbl_posto ON tbl_os.posto=tbl_posto.posto

	WHERE
	tbl_os.os=$os
	AND tbl_os.fabrica=$login_fabrica
	";
	$res_produto = pg_query($con,$sql); echo $sql;

	if (($troca_garantia == 't' AND !isset($troca_efetuada))) {
		echo "<table width='700' align='center' border='2' cellspacing='0' bgcolor='#3366FF' style='' class=''>";
		echo "<tr>";
		echo "<td align='center' style='color: #ffffff'> ";
		echo "<font color='#ffffff' size='+1'> <b> Produto já trocado </b> </font> </a> ";
		echo '<br>OS <a href="os_press.php?os=' . $os . '" target="_blank" style="color:#FFFFFF" title="Cliique para ver a OS em outra janela"><b>' . $sua_os . '</b></a>';
		echo "<br><font style='font-size: 10pt;'>
		Produto da OS: <b>[" . pg_fetch_result($res_produto, 0, referencia) . "] " . pg_fetch_result($res_produto, 0, descricao) . "</b><br>
		Posto: " . pg_fetch_result($res_produto, 0, nome) . "
		</font>";
		echo "</td>";
		echo "</tr>";
		echo "</table>";

	}else{
		if ($login_fabrica <> 7 and $login_fabrica <> 50){

		if($login_fabrica==3){ //HD 72857
			$sqlRT = "SELECT * FROM tbl_programa_restrito
					  WHERE fabrica  = $login_fabrica
					  AND   admin    = $login_admin
					  AND   programa = '$PHP_SELF'";
					// echo nl2br($sqlRT);
			$resRT = pg_query($con, $sqlRT);
			if(pg_num_rows($resRT)>0){
				$permissao="t";
			}
		}


		if( $login_fabrica <> 3 or ($login_fabrica == 3  AND $permissao=='t') ){


		if ($s){

		?>

			<table class="sucesso" width="700px">
				<tr>
					<td>Produto Trocado com Sucesso!</td>
				</tr>
			</table>

		<?}
		if ($sucesso){
		?>
			<table class="sucesso" width="700px">
				<tr>
					<td><?=$sucesso?></td>
				</tr>
			</table>

		<?
		}
		?>

		<form method='post' name='frm_troca' action="<? echo $PHP_SELF . "?os=$os&osacao=trocar"; ?>">
		<input type='hidden' name='os' value='<? echo $os ?>'>
		<!-- colocado por Wellington 29/09/2006 - Estava limpando o campo orientaca_sac qdo executava troca -->
		<input type='hidden' name='orient_sac' value=''>

		<tr>
			<td align="center">
		<table width='700px' align='center' border='0' cellspacing='0'  class='formulario'>
			<tr class='titulo_tabela'>
				<td align='center' colspan="100%" >
				Trocar Produto em Garantia <?=$total1?>
				</td>
			</tr>

			<tr class="subtitulo">
				<td colspan="100%">
					Informações do Produto para Troca
				</td>
			</tr>

			<tr><td>&nbsp;</td></tr>

			<tr>
				<td colspan="100%" align="center">

					<table align="center" width="600px" border="0">
						<tr>
							<td width="14%" align='left'><?php
								//HD 194731: Informações do produto e posto
								echo "Produto da OS: ";
							echo "</td>";

							echo "<td align='left'>";

								$produto_os_troca = pg_result($res_produto,0, 'produto');

								echo "<input type='hidden' name='produto_os_troca_atual' id='produto_os_troca_atual' value='$produto_os_troca'>";

								echo "<b>[" . @pg_fetch_result($res_produto, 0, 'referencia') . "] " . @pg_fetch_result($res_produto, 0, 'descricao') . "</b>";
							echo "</td>";
						echo "</tr>";

						echo "<tr>";
							echo "<td align='left'>";
								echo "Posto: ";
							echo "</td>";

							echo "<td align='left'>";
								echo "<b>";
								echo @pg_fetch_result($res_produto, 0, 'nome');
								echo "</b>";
								if ($login_fabrica == 81 ) {
									$sqlressarcimento = "SELECT hd_chamado,ressarcimento from tbl_hd_chamado_troca join tbl_hd_chamado_extra using(hd_chamado) where os = $os";
									$resressarcimento = pg_exec($con,$sqlressarcimento);

									if (pg_num_rows($resressarcimento)>0) {
										$hd_chamado_troca    = pg_result($resressarcimento,0,0);
										$ressarcimento_troca = pg_result($resressarcimento,0,1);
										if ($ressarcimento_troca == 't') {
											echo "<br>Foi Aberto o Chamado $hd_chamado_troca solicitando o ressarcimento deste produto, para efetivar o ressarcimento complete os campos abaixo";
										}
									}

								} ?>
							</td>
						</tr>

						<tr>
							<td align='left'>
								N° OS:
							</td>
							<td align='left'>
								<b><a href="os_press.php?os=<? echo $os; ?>" target="_blank" title="Cliique para ver a OS em outra janela" style='color:RoyalBlue; text-decoration:underline; '><?=$sua_os?></a></b>
							</td>
						</tr>

						<tr>
							<td align='left'>
								Responsável:
							</td>
							<td align='left'>
								 <b><?=$login_login?></b>
							</td>
						</tr>
					</table>

					<br />
					<table align="center" width="600" border="0">

							<?
								if (in_array($login_fabrica,array(3,6,45,35,11,25,51,14,66,24,19,81,40,80,72,10,59)) or $login_fabrica > 80) {
									if ($login_fabrica == 3 or $login_fabrica == 81) {

							?>

										<tr>
											<td align='left'><b>Marca do Produto</td>
										</tr>
										<tr>
											<td align='left'>
									<?
									$sql = "SELECT  tbl_marca.nome, tbl_marca.marca
											FROM      tbl_marca
											WHERE     tbl_marca.fabrica = $login_fabrica
											ORDER BY tbl_marca.nome;";

										$res = pg_query ($con,$sql); echo $sql;
										if (pg_num_rows($res) > 0) {
											echo "<select class='frm' style='width:200px' name='marca_troca' id='marca_troca' onChange='buscaFamilia(this.value);'>\n";
											echo "<option value=''>ESCOLHA</option>\n";

											for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
												$aux_marca     = trim(pg_fetch_result($res,$x,marca));
												$aux_descricao  = trim(pg_fetch_result($res,$x,nome));
												if ($marca_troca == $aux_marca) {
													$selected = "selected";
												}
												else {
													$selected = "";
												}
												echo "<option $selected value='$aux_marca'>$aux_descricao</option>\n";
											}

											echo "</select>";
										}
										else {
											$aux_marca = '001';
										}
										echo "</td>";
										echo "</tr>";
									}else{
										echo "<tr>";
										#echo "<td align='left'><b>Marca do Produto</td>";
											echo "<td align='left'>";
											$sql = "SELECT  tbl_marca.*
												FROM      tbl_os
												JOIN      tbl_produto USING(produto)
												JOIN      tbl_marca ON tbl_produto.marca = tbl_marca.marca
												WHERE     tbl_marca.fabrica = $login_fabrica
												AND       tbl_os.os = $os
												ORDER BY tbl_marca.nome;";

											$res = pg_query ($con,$sql); echo $sql;
											if (pg_num_rows($res) > 0) {
												for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
													$aux_marca     = trim(pg_fetch_result($res,$x,marca));
													$aux_descricao = trim(pg_fetch_result($res,$x,nome));

													echo "<input type='hidden' name='marca' id='marca' value='$aux_marca'> <b><font color='#990000'>$aux_descricao</font></b><br>";
												}
											}else $aux_marca = '001';
											echo "</td>";
										echo "</tr>";
									}
								?>

						<tr>
							<td width='40%' align='left'>Família do Produto</td>
							<td width="20%"></td>
							<td width='40%' align='left'>Número de Registro</td>

						</tr>

						<tr>
							<td width='40%' align="left">
								<?
									if (($login_fabrica == 3 or $login_fabrica == 81) && (strlen($marca_troca))) {
										$sql = "
										SELECT
										DISTINCT
										tbl_familia.familia,
										tbl_familia.descricao

										FROM
										tbl_familia
										JOIN tbl_produto USING(familia)

										WHERE
										tbl_familia.fabrica = $login_fabrica
										AND tbl_produto.marca='$marca_troca'

										ORDER BY tbl_familia.descricao
										";
										$res = pg_query ($con, $sql);
									}else{
										$sql = "SELECT  *
										FROM    tbl_familia
										WHERE   tbl_familia.fabrica = $login_fabrica
										ORDER BY tbl_familia.descricao;";
										$res = pg_query ($con, $sql);
									}
									if (pg_num_rows($res) > 0) {
									?>
										<select class='frm' style='width:100%' name='familia_troca' id='familia_troca' onChange='listaProduto(this.value,<?="$aux_marca"?>);valida_campo_ant();'>
											<option value=''>ESCOLHA</option>

									<?
											for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
												$aux_familia   = trim(pg_fetch_result($res,$x,familia));
												$aux_descricao = trim(pg_fetch_result($res,$x,descricao));

												if ($familia_troca == $aux_familia) {
													$selected = "selected";
												}
												else {
													$selected = "";
												}

												echo "<option $selected value='$aux_familia'>$aux_descricao</option>\n";
											}
										echo "</select>\n";
									}
									?>
							</td>
							<td width="20%"></td>

							<?
							echo "<td align='left' width='40%'>";
								echo "<input type='text' name='ri' value='" . $_POST[ri] . "' style='width:100%;' maxlength='10' class='frm'>";
							echo "</td>";
							?>

						</tr>

						<?
						if ($login_fabrica == 3){?>
							<tr>

								<td nowrap width='40%' align='left'>Trocar pelo produto/Ressarc.:</td>

							</tr>



						<?
						}else{
						?>

							<tr>
								<td nowrap width='40%' align='left'>Trocar pelo produto/Ressarc.:</td>
								<td width="20%"></td>

								<td nowrap width='40%' align='left'>Causa da Troca/Ressarcimento</td>

							</tr>

						<?
						}
						?>



						<?if ($login_fabrica == 3){?>
							<tr>

								<td  colspan="3" align="left">

									<?
									echo "<select name='troca_garantia_produto' id='troca_garantia_produto' style='width:100%' class='frm' "; if ($login_fabrica == '81') { echo "onchange='javascript: if (this.value == \"-1\") {document.getElementById(\"dados_ressarcimento\").style.display = \"block\"} else {document.getElementById(\"dados_ressarcimento\").style.display = \"none\"}'";} echo ">";
									echo "<option id='opcoes' value=''></option>";
									if (strlen($familia_troca)) {
										if(strlen($marca_troca) && ($login_fabrica==3 || $login_fabrica==81)){
											$sql_marca = " AND tbl_produto.marca = $marca_troca ";
										}

										$sql ="
										SELECT
										tbl_produto.referencia,
										tbl_produto.descricao,
										tbl_produto.produto

										FROM
										tbl_produto
										JOIN tbl_familia USING(familia)

										WHERE
										tbl_produto.familia = $familia_troca
										AND tbl_familia.fabrica = $login_fabrica
										AND tbl_produto.lista_troca IS TRUE
										$sql_marca
										ORDER BY tbl_produto.referencia
										";
										$res = pg_query($con, $sql);

										switch($troca_garantia_produto) {
											case -1:
												$selected_ressarcimento = "selected";
											break;

											case -2:
												$selected_troca_revenda= "selected";
											break;
										}

										echo "<option $selected_ressarcimento value='-1'>RESSARCIMENTO FINANCEIRO</option>";
										//HD 211825: Opção de trocar através da revenda um produto
										if ($login_fabrica == 81) {
											echo "<option $selected_troca_revenda value='-2'>AUTORIZAÇÃO DE DEVOLUÇÃO DE VENDA</option>";
										}

										for($p = 0; $p < pg_num_rows($res); $p++) {
											$aux_referencia = pg_fetch_result($res, $p, referencia);
											$aux_descricao = pg_fetch_result($res, $p, descricao);
											$aux_produto = pg_fetch_result($res, $p, produto);

											if ($troca_garantia_produto == $aux_produto) {
												$selected = "selected";
											}
											else {
												$selected = "";
											}

											echo "<option value='$aux_produto' $selected>$aux_referencia - $aux_descricao</option>";
										}
									}
									?>

										</select>
								</td>

							</tr>

							<tr>
								<td nowrap width='40%' align='left'>Causa da Troca/Ressarcimento</td>

							</tr>

							<tr>

								<td width='40%' align="left">

									<?
										$sql = "SELECT  tbl_causa_troca.causa_troca,
														tbl_causa_troca.codigo     ,
														tbl_causa_troca.descricao
												FROM tbl_causa_troca
												WHERE tbl_causa_troca.fabrica = $login_fabrica
												AND tbl_causa_troca.ativo     IS TRUE
												ORDER BY tbl_causa_troca.codigo,tbl_causa_troca.descricao";
										$resTroca = pg_query ($con,$sql); echo $sql;
										echo "<select name='causa_troca' id='causa_troca' size='1' class='frm' style='width:100%'>";
											echo "<option value='' ></option>";
											for ($i = 0 ; $i < pg_num_rows($resTroca) ; $i++) {
												$aux_causa_troca = pg_fetch_result ($resTroca,$i,causa_troca);

												if ($causa_troca == $aux_causa_troca) {
													$selected = "selected";
												}
												else {
													$selected = "";
												}

												echo "<option $selected value='" . $aux_causa_troca . "'";
												echo ">" . pg_fetch_result ($resTroca,$i,codigo) . " - " . pg_fetch_result ($resTroca,$i,descricao) . "</option>";
											}
									?>
										</select>
								</td>

							</tr>


						<?}else{?>
							<tr>

								<td width='40%'  align="left">

									<?
									echo "<select name='troca_garantia_produto' id='troca_garantia_produto'  class='frm' style='width:100%' "; if ($login_fabrica == '81') { echo "onchange='javascript: if (this.value == \"-1\") {document.getElementById(\"dados_ressarcimento\").style.display = \"block\"} else {document.getElementById(\"dados_ressarcimento\").style.display = \"none\"}'";} echo ">";
									echo "<option id='opcoes' value=''></option>";
									if (strlen($familia_troca)) {
										if(strlen($marca_troca) && ($login_fabrica==3 || $login_fabrica==81)){
											$sql_marca = " AND tbl_produto.marca = $marca_troca ";
										}

										$sql ="
										SELECT
										tbl_produto.referencia,
										tbl_produto.descricao,
										tbl_produto.produto

										FROM
										tbl_produto
										JOIN tbl_familia USING(familia)

										WHERE
										tbl_produto.familia = $familia_troca
										AND tbl_familia.fabrica = $login_fabrica
										AND tbl_produto.lista_troca IS TRUE
										$sql_marca
										ORDER BY tbl_produto.referencia
										";
										$res = pg_query($con, $sql);

										switch($troca_garantia_produto) {
											case -1:
												$selected_ressarcimento = "selected";
											break;

											case -2:
												$selected_troca_revenda= "selected";
											break;
										}

										echo "<option $selected_ressarcimento value='-1'>RESSARCIMENTO FINANCEIRO</option>";
										//HD 211825: Opção de trocar através da revenda um produto
										if ($login_fabrica == 81) {
											echo "<option $selected_troca_revenda value='-2'>AUTORIZAÇÃO DE DEVOLUÇÃO DE VENDA</option>";
										}

										for($p = 0; $p < pg_num_rows($res); $p++) {
											$aux_referencia = pg_fetch_result($res, $p, referencia);
											$aux_descricao = pg_fetch_result($res, $p, descricao);
											$aux_produto = pg_fetch_result($res, $p, produto);

											if ($troca_garantia_produto == $aux_produto) {
												$selected = "selected";
											}
											else {
												$selected = "";
											}

											echo "<option value='$aux_produto' $selected>$aux_referencia - $aux_descricao</option>";
										}
									}
									?>

										</select>
								</td>
								<td width="20%"></td>

								<td width='40%' align="left">

									<?
										$sql = "SELECT  tbl_causa_troca.causa_troca,
														tbl_causa_troca.codigo     ,
														tbl_causa_troca.descricao
												FROM tbl_causa_troca
												WHERE tbl_causa_troca.fabrica = $login_fabrica
												AND tbl_causa_troca.ativo     IS TRUE
												ORDER BY tbl_causa_troca.codigo,tbl_causa_troca.descricao";
										$resTroca = pg_query ($con,$sql); echo $sql;
										echo "<select name='causa_troca' id='causa_troca' size='1' class='frm' style='width:100%'>";
											echo "<option value='' ></option>";
											for ($i = 0 ; $i < pg_num_rows($resTroca) ; $i++) {
												$aux_causa_troca = pg_fetch_result ($resTroca,$i,causa_troca);

												if ($causa_troca == $aux_causa_troca) {
													$selected = "selected";
												}
												else {
													$selected = "";
												}

												echo "<option $selected value='" . $aux_causa_troca . "'";
												echo "title='". pg_fetch_result ($resTroca,$i,descricao) . "'";
												echo ">" . pg_fetch_result ($resTroca,$i,codigo) . " - " . pg_fetch_result ($resTroca,$i,descricao) . "</option>";
											}
									?>
										</select>
								</td>


							</tr>
						<?}?>


						<?php if ($login_fabrica <> 6){?>

						<? if($login_fabrica==51){ #HD 390687 ?>
						<tr>
							<td colspan='2' align='left'>N° Coleta/Postagem</td>
							<td align='left'>Data Solicitação</td>
						</tr>
						<tr>
							<td colspan='2' align='left'><input type="text" name="coleta_postagem" value="<? echo $coleta_postagem; ?>" style="width:65%;" maxlength="20" class='frm'></td>
							<td align='left'><input type="text" name="data_postagem" id="data_postagem" value="<? echo $data_postagem; ?>" style="width:39%;" size="11" class='frm'></td>
						</tr>
						<? } ?>
						<tr>
							<td>&nbsp;</td>
						</tr>
						<tr>
							<td colspan="3" align="left">
								Observação para nota fiscal
							</td>
						</tr>


						<tr>
							<td colspan="3" align="left">
							<?
								echo "<textarea style='width:100%' name='observacao_pedido' rows='5' class='frm'>".  $_POST["observacao_pedido"] . "</textarea><br>";
							?>
							</td>
						</tr>

						<?php } else {?>

						<tr>
							<td>Causa Raiz</td>
						</tr>
						<tr>
							<td>
								<select style="width:100%" class='frm' name="causa_raiz" id="causa_raiz">
									<option value=""></option>
								</select>

							</td>
						</tr>

						<?php }?>
					</table>
					<br />
				</td>
			</tr>

			<tr class="subtitulo">
				<td colspan="100%">
					Informações Adicionais
				</td>
			</tr>

			<tr>
				<td colspan="100%">
					<?
						$sql = "SELECT tbl_os_item.os_item,
										tbl_peca.referencia,
										tbl_peca.descricao,
										tbl_os_item.qtde
								FROM tbl_os_item
								JOIN tbl_os_produto USING (os_produto)
								JOIN tbl_peca       USING (peca)
								WHERE os = $os";
						$resTroca = pg_query ($con,$sql); echo $sql;
						$qtde_itens = pg_num_rows($resTroca);
						echo "<input type='hidden' name='qtde_itens' value='$qtde_itens'>";
						if(pg_num_rows($resTroca)>0) {

							echo "<br><table border='0' class='tabela' cellspacing='1' cellpadding='1' width='600' align='center'>";
							echo "<tr class='titulo_coluna'>";
							echo "<td></td>";
							echo "<td align='left'> <b>Referência</td>";
							echo "<td align='left'> <b>Peça</td>";
							echo "<td align='right'> <b>Qtde</td>";
							echo "</tr>";
							for ($i = 0 ; $i < pg_num_rows($resTroca) ; $i++) {
								$os_item         = pg_fetch_result ($resTroca,$i,os_item) ;
								$peca_referencia = pg_fetch_result ($resTroca,$i,referencia) ;
								$peca_descricao  = pg_fetch_result ($resTroca,$i,descricao) ;
								$peca_qtde       = pg_fetch_result ($resTroca,$i,qtde)      ;
								if($cor == "#F1F4FA") $cor = '#F7F5F0';
								else                  $cor = '#F1F4FA';

								if($_POST["os_item_$i"] == $os_item) {
									$checked = "checked";
								}
								else {
									$checked = "";
								}

								echo "<tr style='background-color:$cor'>";
								echo "<td align='left'> <input type='checkbox' $checked value='$os_item' name='os_item_$i'></td>";
								echo "<td align='left'> $peca_referencia</td>";
								echo "<td align='left'> $peca_descricao</td>";
								echo "<td align='right'> $peca_qtde</td>";
								echo "</tr>";
							}

							echo "<tr class='titulo_coluna'>";
							echo "<td align='left' colspan='4'>&nbsp;<img src='imagens/seta_checkbox.gif'> Se o motivo da troca foi peça, selecione a peça que originou a troca</td>";
							echo "</tr>";
							echo "<tr class='texto_avulso'>";
							echo "<td align='center' colspan='4'><u> Em caso de troca TODAS as peças acima serão canceladas</td>";
							echo "</tr>";

							echo "</table><br>";
						}
						?>
						<br>
						<table align='center' border='0' cellspacing='0' width='600'>

							<tr>
								<td width="50px">&nbsp;</td>
								<td align='left' rowspan="2">
									<fieldset style="width:150px">
										<legend>Setor Responsável</legend>
										<?


										switch($_POST["setor"]) {
										case "Revenda":
											$revenda_checked = "checked";
										break;

										case "Carteira":
											$carteira_checked = "checked";
										break;

										case "SAC":
											$sac_checked = "checked";
										break;

										case "Procon":
											$procon_checked = "checked";
										break;

										case "SAP":
											$sap_checked = "checked";
										break;
										}

										echo "<input type='radio' name='setor' id='setor_revenda' $revenda_checked value='Revenda'> <label for='setor_revenda' style='cursor:pointer'> Revenda </label> <br>";
										if ($login_fabrica<>6){
										echo "<input type='radio' name='setor' id='setor_carteira' $carteira_checked value='Carteira'> <label for='setor_carteira' style='cursor:pointer'>Carteira</label> <br>";
										}
										echo "<input type='radio' name='setor' id='setor_sac' $sac_checked value='SAC'> <label for='setor_sac' style='cursor:pointer'>SAC</label> <br>";
										echo "<input type='radio' name='setor' id='setor_procon'$procon_checked value='Procon'> <label for='setor_procon' style='cursor:pointer'>Procon</label><br>";
										echo "<input type='radio' name='setor' id='setor_sap' $sap_checked value='SAP'> <label for='setor_sap' style='cursor:pointer'>SAP</label> <br>";
										?>
									</fieldset>
								</td>

								<td width="50px">&nbsp;</td>

								<td>
								<?if ($login_fabrica <> 6)
								{?>

									<fieldset style="width:150px">
										<?php
										if($login_fabrica <> 51 and $login_fabrica <> 81 and $login_fabrica <> 6){
											echo "<legend>Situação do Atendimento</legend>";
										}else{
											echo "<legend>Efetuar Troca Por</legend>";
										}

										if($login_fabrica <> 51 and $login_fabrica <> 81){
											switch($_POST["situacao_atendimento"]) {
												case "0":
													$checked_0 = "checked";
												break;

												case "50":
													$checked_50 = "checked";
												break;

												case "100":
													$checked_100 = "checked";
												break;
											}

											echo "<input type='radio' name='situacao_atendimento' id='situacao_produto_garantia' $checked_0 value='0'> <label for='situacao_produto_garantia' style='cursor:pointer'>Produto em garantia</label> <br>";
											echo "<input type='radio' name='situacao_atendimento' id='situacao_faturado_50' $checked_50 value='50'> <label for='situacao_faturado_50' style='cursor:pointer'>Faturado 50%</label> <br>";
											echo "<input type='radio' name='situacao_atendimento' id='situacao_faturado_100' $checked_100 value='100'> <label for='situacao_faturado_100' style='cursor:pointer'>Faturado 100%</label> <br>";
										}else{
											switch($_POST["fabrica_distribuidor"]) {
												case "fabrica":
													$fabrica_checked = "checked";
												break;

												case "distribuidor":
													$distribuidor_checked = "checked";
												break;
											}

											echo "<input type='hidden' name='situacao_atendimento' value='0'>";
											echo "<input type='radio' name='fabrica_distribuidor' id='fabrica_fabrica' $fabrica_checked value='fabrica'> <label for='fabrica_fabrica' style='cursor:pointer'>Fábrica</label><br>";
											echo "<input type='radio' name='fabrica_distribuidor' id='fabrica_distrib' $distribuidor_checked value='distribuidor'> <label for='fabrica_distrib' style='cursor:pointer'> Distribuidor </label>  <br>";
										}
										?>
									</fieldset>
								<?
								}
								?>
								</td>
								<td width="50px">&nbsp;</td>
							</tr>
							<tr>
								<td></td>
								<td></td>
								<td>
							<?

								//HD 79774 - Paulo César 10/03/2009 esta como disable pois oi travado geração de pedido automatica para  fabrica=3
								//HD 83652 - IGOR - Retirar regra de gerar pedido sempre para Britania
								if ($login_fabrica <> 14) {
									if($login_fabrica==3 and 1==2){
										echo "<input type='checkbox' name='gerar_pedido' id='gerar_pedido' value='t' checked='checked' disabled> <label for='gerar_pedido' style='cursor:pointer'> Gerar pedido </label> ";
									}else{
										if ($_POST["gerar_pedido"]) {
											$checked = "checked";
										}
										$display_gerar_pedido = ($login_fabrica==6) ? "style='display:none;cursor:pointer'" : null;
										echo "<input type='checkbox' $display_gerar_pedido $checked name='gerar_pedido' id='gerar_pedido' value='t'> <label for='gerar_pedido' $display_gerar_pedido> Gerar pedido </label> ";
									}
								}

							?>
							</td>


							<?
							echo "</tr>";
							if($login_fabrica==3 or $login_fabrica==81) {
							?>


							<tr>

								<td width="50px">&nbsp;</td>

								<td>
								<fieldset style="width:150px">
									<legend>Destino</legend>
									<?


										switch($_POST["envio_consumidor"]) {
											case "t":
												$t_checked = "checked";
											break;

											case "f":
												$f_checked = "checked";
											break;
										}

									echo "<input type='radio' name='envio_consumidor' id='envio_direto_cons' $t_checked value='t'> <label for='envio_direto_cons' style='cursor:pointer'> Direto ao Consumidor </label> <br>";
									echo "<input type='radio' name='envio_consumidor' id='envio_posto' $f_checked value='f'> <label for='envio_posto' style='cursor:pointer'> Para o Posto </label> <br>";
									?>

								</fieldset>
								</td>
								<td width="50px">&nbsp;</td>
								<td align='left' >
									<fieldset style="width:150px">
										<legend>Modalidade de Transporte</legend>
										<?
										switch($_POST["modalidade_transporte"]) {
											case "urgente":
												$urgente_checked = "checked";
											break;

											case "normal":
												$normal_checked = "checked";
											break;
										}

										echo "<input type='radio' name='modalidade_transporte' id='modalidade_ri_urgente' $urgente_checked value='urgente'> <label for='modalidade_ri_urgente' style='cursor:pointer'> RI Urgente </label> <br>";
										echo "<input type='radio' name='modalidade_transporte' id='modalidade_ri_normal' $normal_checked value='normal'> <label for='modalidade_ri_normal' style='cursor:pointer'> RI Normal </label>";


										?>
									</fieldset>
								</td>
							</tr>

						<?
						}

						//Status troca Mallory
						if($login_fabrica==72){
							if ($temImg = temNF($os, 'bool')) {
								//OK OS com nota fiscal
							}else{
							?>
								<tr>
									<td style='width:50px'>&nbsp;</td>
									<td colspan="3">
										<fieldset>
											<legend>OS sem Nota Fiscal anexada</legend>



							<?		echo "<input type='radio' name='troca_com_nota' id='sem_nota_sem_troca' value='sem_nota_sem_troca'";
									if(strlen($troca_com_nota)==0 or $troca_com_nota=='sem_nota_sem_troca') echo 'checked';
									echo ">&nbsp; <label for='sem_nota_sem_troca' style='cursor:pointer'> Comunicar posto para anexar nota fiscal e não fazer a troca </label><br />";
									echo "<input type='radio' name='troca_com_nota' id='sem_nota_com_troca' value='sem_nota_com_troca'";
									if($troca_com_nota=='sem_nota_com_troca') echo 'checked';
									echo ">&nbsp;<label for='sem_nota_com_troca' style='cursor:pointer'> Proceder com a troca sem a nota fiscal </label>";
									?>
										</fieldset>
									</td>
									<td style='width:50px'>&nbsp;</td>
								</tr>
								<tr>
									<td style='width:50px'>&nbsp;</td>

									<td colspan='3' align='center'>
											<div id='id_justificativanf' class='Conteudo' style='display: none;'>
												<br>Justificativa<br>

												<?
													echo "<textarea name='justificativanf' rows='4' cols='60'>$justificativanf</textarea>";

												?>
											</div>
									</td>

									<td style='width:50px'>&nbsp;</td>
								</tr>
								<?
							}
						}
						//Status troca Mallory - Fim
						echo "</table>";

						if ($login_fabrica == 81) {
							$sql = "SELECT	hd_chamado,
											cpf_conta,
											banco,
											agencia,
											tipo_conta,
											favorecido_conta,
											valor_produto,
											contay
										FROM
										tbl_hd_chamado_extra_banco
										LEFT JOIN tbl_hd_chamado_extra USING(hd_chamado)
										LEFT JOIN tbl_hd_chamado_troca USING(hd_chamado)
										where os = $os";

							$res = pg_exec($con,$sql); echo $sql;

							if (pg_num_rows($res)>0) {

								$cpf_ressarcimento      = pg_result($res,0,cpf_conta);
								$agencia                = pg_result($res,0,agencia);
								$conta                = pg_result($res,0,contay);
								$banco                  = pg_result($res,0,banco);
								$tipo_conta             = pg_result($res,0,tipo_conta);
								$valor                = pg_result($res,0,valor_produto);
								$favorecido_conta     = pg_result($res,0,favorecido_conta);

								$valor = number_format($valor,2,',','.');
							}

							if ($troca_garantia_produto == '-1') {
								$display = 'block';
							} else {
								$display = 'none';
							}
							echo "<br><div id='dados_ressarcimento' style='display:$display;width:100%;'>
										<div class='subtitulo' style='width:100%'>
											Informações Bancárias para o Ressarcimento
										</div>
									<table border='0' width='600px' align='center'>
										<tr>
											<td>CPF/CNPJ do Titular</td>
											<td>Nome Favorecido</td>

										</tr>
										<tr>
											<td><input type='text' name='cpf_ressarcimento' id='cpf_ressarcimento' class='frm' value='$cpf_ressarcimento' maxlength='14'></td>
											<td><input type='text' maxlength='50' name='favorecido_conta' class='frm' value='$favorecido_conta'></td>
										</tr>
										<tr>
											<td colspan='2'>Banco</td>
											<td>Tipo Conta</td>
										</tr>
										<tr>";

											$sql = "SELECT banco,codigo,nome from tbl_banco order by nome";
											$res = pg_exec($con,$sql); echo $sql;

											echo "<td colspan='2'>
														<select name='banco' id='banco' class='frm'>
														<option>- escolha</option>";
														for ($i=0;$i<pg_num_rows($res);$i++) {
															$xbanco = pg_result($res,$i,banco);
															$codigo = pg_result($res,$i,codigo);
															$nome = pg_result($res,$i,nome);

															if ($banco == $xbanco) {
																$selected = "SELECTED";
															}
															echo "<option value='$xbanco' $selected>$codigo-$nome</option>";
															$selected = '';
														}
												echo "</select>
												</td>
												<td><select class='frm' name='tipo_conta' id='tipo_conta'><option>Conta corrente</option> <option>Poupança</option></select></td>
										</tr>
										<tr>
											<td>Agência</td>
											<td>Conta Corrente</td>
											<td>Valor</td>
										</tr>

										<tr>
											<td><input type='30' maxlength='10' name='agencia' id='agencia' class='frm' value='$agencia'></td>
											<td><input type='30' maxlength='15' name='conta' id='conta' class='frm' value='$conta'></td>
											<td><input style='text-align:right' type='30' name='valor' id='valor' class='frm money' value='$valor'></td>
										</tr>
									</table>
							</div>";
						}

					}else{
						echo "<p>Trocar pelo Produto ";
						echo "<input type='text' name='troca_garantia_produto' size='15' maxlength='15' value='$troca_garantia_produto' class='frm'>" ;
						echo "&nbsp;&nbsp;&nbsp;";
						if($login_fabrica==20) echo "<b>Valor para Troca</b>";
						else echo "Mão-de-Obra para Troca";
						echo" <input type='text' name='troca_garantia_mao_obra' size='5' maxlength='10' value='$troca_garantia_mao_obra' class='frm'>";
						echo "<br>";
						echo "(deixe em branco para pagar valor padrão)";
						echo "<br>";
						echo "<input type='radio' name='troca_via_distribuidor' value='f' ";
						if ($troca_via_distribuidor == 'f') echo " checked " ;
						echo "> Troca Direta ";
						echo "&nbsp;&nbsp;&nbsp;";
						echo "<input type='radio' name='troca_via_distribuidor' value='t' ";
						if ($troca_via_distribuidor == 't') echo " checked " ;
						echo "> Via Distribuidor";
						echo "<br>";
					}
					echo "<p>";
					echo "<input type='hidden' name='btn_troca' value=''>";
					//colocado por Wellington 29/09/2006 - Estava limpando o campo orientaca_sac qdo executava troca
					//colocado "document.frm_troca.orient_sac.value = document.frm_os.orientacao_sac.value"

					?>
				</td>
			</tr>

			<tr class="formulario">
				<td colspan="100%" align="center"   >
					<input type='button' value="Efetuar Troca" onclick="javascript: fazer_troca(); ">
				</td>
			</tr>
		</table>
		</table>
				</td>
			</tr>



		<?
		if ($login_fabrica == 66 or $login_fabrica == 43 or $login_fabrica == 14) {

		echo "<tr class='Conteudo'><td align='center'><a href='#'><img src='imagens/btn_solicitar_coleta.gif'></a></td></tr>";

		}

		?>
		</table>

		</td>
		</tr>
		</form>
		</table>

<?
		}
	}
	}
}

?>

<p>

<? include "rodape.php";?>

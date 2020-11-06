<?php
#echo "<h1> Programa em Manutencao </h1>";
#exit;

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";

// if ($ip<> include('../nosso_ip.php')) {
// 	echo "<h2> Em manutenção. Aguarde alguns minutos.</h2>";exit();
// }

$admin_privilegios = "gerencia,call_center";
include "autentica_admin.php";

if ($login_fabrica!=3 AND $login_fabrica!=11 AND $login_fabrica!=10 and $login_fabrica != 172){
	header("Location: menu_callcenter.php");
	exit();
}

$sql = "SELECT qtde_dias_intervencao_sap
		FROM tbl_fabrica
		WHERE fabrica = $login_fabrica ";
$resd = pg_exec($con, $sql);
$qtde_dias_intervencao_sap = pg_result($resd,0,qtde_dias_intervencao_sap);

if ($login_fabrica == 3) {
	$qtde_dias_intervencao_sap = 9999;
}

$msg = "";
$msg_erro = "";

if ($login_fabrica == 172) {
    $id_servico_realizado = 11287;
	$id_servico_realizado_ajuste = 11283;
}
if ($login_fabrica==11) {
	$id_servico_realizado = 61;
	$id_servico_realizado_ajuste = 498;
}
if ($login_fabrica==3) {
	$id_servico_realizado = 20;
	$id_servico_realizado_ajuste = 96;
}

if (strlen($id_servico_realizado) == 0) { # padrao BRITANIA
	$id_servico_realizado = 20;
	$id_servico_realizado_ajuste = 96;
}

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

function converte_data($date){
	$date = explode("-", str_replace('/', '-', $date));
	if (sizeof($date)==3)	return ''.$date[2].'/'.$date[1].'/'.$date[0];
	else			return false;
}

if (isset($_GET['os']) && strlen($_GET['os'])>0)		$os = $_GET['os'];
if (isset($_GET['msg']) && strlen($_GET['msg'])>0)		$msg = $_GET['msg'];
if (isset($_GET['msg_erro']) && strlen($_GET['msg_erro'])>0)	$msg_erro=$_GET['msg_erro'];

if (strlen(trim($_POST['atualizar'])) > 0)	$atualizar = trim($_POST['atualizar']);
else                                  		$atualizar = trim($_GET["atualizar"]);


if (strlen(trim($_POST['btnacao'])) > 0)	$btnacao = trim($_POST['btnacao']);
else                                  		$btnacao = trim($_GET["btnacao"]);

// Britania Justificativa SAP

if($_REQUEST['ajax_justificar'] == "ok"){

	$os = $_REQUEST['os'];
	$justificativa = $_REQUEST['justificativa'];

	if(!empty($os)){

			$sql = "SELECT posto,sua_os,finalizada FROM tbl_os where os={$os}";
			$res = pg_query($con,$sql);

			$posto = trim(pg_fetch_result($res,0,posto));
			$sua_os = trim(pg_fetch_result($res,0,sua_os));
			$finalizada = trim(pg_fetch_result($res,0,finalizada));


			pg_query($con, "BEGIN TRANSACTION");

			if(strlen($finalizada) > 0 ){
				$sql = "UPDATE tbl_os SET finalizada = NULL WHERE os = {$os}";
				$res = pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);
			}

		if(strlen($msg_erro) == 0){
			/*$sql = "DELETE FROM tbl_os_status 
			WHERE os_status = (SELECT os_status 
			FROM tbl_os_status 
			WHERE status_os IN (72,73) 
			AND os = {$os} 
			AND tbl_os_status.fabrica_status = {$login_fabrica} 
			ORDER BY data DESC LIMIT 1)";
			$res = pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);*/

			if(strlen($msg_erro) == 0){

				$msg_posto= "Pedido de Peças Autorizado Pela Fábrica.";//" Justificativa: ".utf8_decode($justificativa);

				$sql = "INSERT INTO tbl_os_status 
				(os,status_os,data,observacao,admin)
				VALUES ({$os},73,current_timestamp,'{$msg_posto}',{$login_admin})";
				$res = pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);
			}

			if(strlen($msg_erro) == 0){
				$sql = "UPDATE tbl_os_item SET admin = {$login_admin} 
				FROM tbl_os_produto
				WHERE tbl_os_item.os_produto = tbl_os_produto.os_produto 
				AND tbl_os_produto.os = {$os}";

				if ($login_fabrica <> 3) {
					$sql .= "
					AND tbl_os_item.admin IS NULL
					AND tbl_os_item.servico_realizado <> {$id_servico_realizado_ajuste}";
				}

				$res = pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}elseif($acao == "cancelar" AND strlen($msg_erro) == 0){

			$sql = "UPDATE tbl_os_item SET 
			servico_realizado = {$id_servico_realizado_ajuste},
			admin = {$login_admin} ,
			liberacao_pedido = FALSE,
			liberacao_pedido_analisado = FALSE,
			data_liberacao_pedido = NULL
			FROM tbl_os_produto
			WHERE tbl_os_item.os_produto = tbl_os_produto.os_produto 
			AND tbl_os_produto.os = {$os}
			AND tbl_os_item.admin IS NULL
			AND tbl_os_item.servico_realizado <> {$id_servico_realizado_ajuste}";
			$res = pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);

			if(strlen($msg_erro) == 0){
				//'Seu pedido de peças referente a OS $sua_os foi <b>cancelado</b> pela fábrica. <br><br>Justificativa da Fábrica: $justificativa',
				$sql = "INSERT INTO tbl_comunicado (
				descricao ,
				mensagem ,
				tipo ,
				fabrica ,
				obrigatorio_os_produto ,
				obrigatorio_site ,
				posto ,
				ativo
				) VALUES (
				'Pedido de Peças CANCELADO' ,
				'Seu pedido de peças referente a OS $sua_os foi <b>cancelado</b> pela fábrica. <br>',
				'Pedido de Peças',
				$login_fabrica,
				'f' ,
				't',
				$posto,
				't'
				);";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);

				if(strlen($msg_erro) == 0){

					$msg_posto= "Pedido de Peças Cancelado Pela Fábrica.";//" Justificativa: ".utf8_decode($justificativa);

					$sql = "INSERT INTO tbl_os_status 
					(os,status_os,data,observacao,admin)
					VALUES ({$os},73,current_timestamp,'{$msg_posto}',{$login_admin})";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

				}
			}
		}

		if (strlen($msg_erro) == 0 AND strlen($finalizada) > 0){

			$sql = "UPDATE tbl_os SET finalizada = '$finalizada' WHERE os = {$os}";
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		if(strlen($msg_erro)>0){
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
			echo "erro|$msg_erro";
		}else{
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			$msg = "A OS foi liberada para o posto"; 
			echo "ok|$msg";
		}
	}
	exit;
}

// FIM

if ($btnacao == 'filtrar'  ) {

	if (strlen($_POST['posto_codigo']) > 0) $posto_codigo = $_POST['posto_codigo'];
	else                              	$posto_codigo = $_GET["posto_codigo"];

	if (strlen($_POST['posto_nome']) > 0) 	$posto_nome = $_POST['posto_nome'];
	else                               	$posto_nome = strtoupper($_GET["posto_nome"]);

	if (strlen($_POST['produto']) > 0) 	$produto = $_POST['produto'];
	else                               	$produto = $_GET["produto"];

	if (strlen($_POST['referencia']) > 0) 	$referencia = $_POST['referencia'];
	else                                 	$referencia = $_GET["referencia"];

	if (strlen($_POST['descricao']) > 0)	$descricao = $_POST['descricao'];
	else                                  	$descricao = $_GET["descricao"];

	if (strlen($_POST['os_a_autorizar']) > 0){ //HD 800106

		$os_a_autorizar = $_POST['os_a_autorizar'];

		$join_os_a_autorizar = "
			JOIN tbl_os_produto USING(os)
			JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto AND tbl_os_item.fabrica_i=$login_fabrica
			JOIN tbl_peca ON  tbl_peca.peca = tbl_os_item.peca and tbl_peca.fabrica=$login_fabrica
			JOIN (SELECT max(data) as data_status, os
			FROM tbl_os_status
			WHERE fabrica_status = $login_fabrica AND status_os IN (72,73) group by os) x ON x.os = tbl_os.os
		";

		$cond_os_a_autorizar = "
			AND tbl_os_item.pedido ISNULL
			AND servico_realizado = $id_servico_realizado
			AND (bloqueada_garantia or x.data_status::date - tbl_os.data_abertura >=$qtde_dias_intervencao_sap)
		";

	}

	if (strlen($posto_codigo)>0 OR strlen($posto_nome)>0 OR strlen($produto)>0 OR strlen($referencia)>0){

		if (strlen($posto_codigo)>0 OR strlen($posto_nome)>0){
			if (strlen($posto_codigo)>0){
				$sql_adicional = " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo' ";
				$join_sql_adicional = "JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica=$login_fabrica";
			}
			else{
				$sql_adicional = " AND tbl_posto.nome like '%$posto_nome%' ";
				$join_sql_adicional = "JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto";
			}

		}

		$filtro = "<b style='font-size:14px;padding-right:15px'>Pesquisando por:";
		if (strlen($sql_adicional)>0)
			$filtro .= "<br>Posto: <i>$posto_codigo - $posto_nome</i>";
		if (strlen($sql_adicional_2)>0)
			$filtro .= "<br>Produto: <i>$referencia - $descricao </i>";
		$filtro .= "</b> (<a href='$PHP_SELF' style='font-size:12px;'>Mostrar Todos</a>)";
	}
}

//liberação da OS
if ($_GET['ajax']=='ok' && $_GET['liberar']=='ok'){

	$os=trim($_GET['os']);

	if (strlen($_GET['posto_codigo']) > 0){
		$posto_codigo = $_GET['posto_codigo'];
	}

	if (strlen($_GET['posto_nome']) > 0){
		$posto_nome = $_GET['posto_nome'];
	}

	$os_sem_peca = ( trim($_GET['os_sem_peca']) ) ? trim($_GET['os_sem_peca']) : null ;


	if(strlen($os_sem_peca)>0 AND trim($os_sem_peca)==$os){

		$res = pg_query($con,"BEGIN TRANSACTION");
		$sql = "DELETE FROM tbl_os_status WHERE os_status= (SELECT os_status FROM tbl_os_status WHERE status_os IN (72,73) AND os=$os AND tbl_os_status.fabrica_status=$login_fabrica ORDER BY data DESC LIMIT 1)";

		$sql = "INSERT INTO tbl_os_status
				(os,status_os,data,observacao,admin)
				VALUES ($os,73,current_timestamp,'OS liberada',$login_admin)";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);

		if (strlen($msg_erro)>0){
			$res = pg_query ($con,"ROLLBACK TRANSACTION");
			$msg = "Ocorreu um erro durante a liberação da OS '$sua_os', tente novamente";
			echo "erro|$msg";
		}else {
			//$res = @pg_exec ($con,"ROLLBACK TRANSACTION"); //teste
			$res = pg_query ($con,"COMMIT TRANSACTION");
			$msg = "A OS $sua_os foi liberada para o posto";
			echo "ok|$msg";
		}

		exit();

	} // jah para aki se nao tiver peca
}

// cancela ou autoriza o pedido das peças
if ($_REQUEST['ajax']=='ok' && $_REQUEST['verificar']=='ok') {
	$os=trim($_REQUEST['os']);
	$justificativa=trim($_REQUEST['justificativa']);

	if (strlen($_REQUEST['btnacao']) > 0) {
		$btnacao = $_REQUEST['btnacao'];
	}

	if (strlen($_REQUEST['posto_codigo']) > 0){
		$posto_codigo = $_REQUEST['posto_codigo'];
	}

	if (strlen($_REQUEST['posto_nome']) > 0){
		$posto_nome = $_REQUEST['posto_nome'];
	}

	if (strlen($os)>0 AND strlen($msg_erro)==0){

		$sql = "SELECT posto,sua_os,finalizada FROM tbl_os where os=$os";
		$res = pg_exec($con,$sql);
		$posto = trim(pg_result($res,0,posto));
		$sua_os = trim(pg_result($res,0,sua_os));
		$o_finalizadas = trim(pg_result($res,0,finalizada));
/*
		$sql_peca = "SELECT     tbl_os_item.os_item AS item,
					tbl_peca.troca_obrigatoria AS troca_obrigatoria,
					tbl_peca.retorna_conserto AS retorna_conserto,
					tbl_peca.bloqueada_garantia As bloqueada_garantia,
					tbl_peca.referencia AS referencia,
					tbl_peca.descricao AS descricao,
					tbl_peca.peca AS peca
					FROM tbl_os_produto
					JOIN tbl_os_item USING(os_produto)
					JOIN tbl_peca USING(peca)
					WHERE tbl_os_produto.os=$os
					AND tbl_os_item.servico_realizado=$id_servico_realizado
					AND tbl_os_item.pedido IS NULL";

		$res_peca = pg_exec($con,$sql_peca);

		$resultado = pg_numrows($res_peca);
*/
		#if ($resultado>0){
			$res = pg_exec($con,"BEGIN TRANSACTION");

			if (strlen($o_finalizadas)>0){
				$sql = "UPDATE tbl_os SET finalizada=null WHERE os=$os";
				$res = pg_exec($con,$sql);
			}
			$qtde_cancelado=0;
			$itens_os = explode(',', $_REQUEST['itens']);
			$os_itens = explode(',', $_REQUEST['os_itens']);
			$qtde = count($os_itens);

			for($j=0;$j<$qtde;$j++){
				$item_alterar     = $itens_os[$j];
				$os_item_alterar  = $os_itens[$j];

				if(!empty($os_item_alterar)) {
					$sql_peca = "   SELECT  tbl_peca.bloqueada_garantia AS bloqueada_garantia   ,
								tbl_peca.referencia         AS referencia           ,
								tbl_peca.descricao          AS descricao
							FROM    tbl_peca
							JOIN    tbl_os_item USING(peca)
							WHERE   tbl_os_item.os_item = $os_item_alterar
					";

					$res_peca = pg_exec($con,$sql_peca);
					$peca_referencia  = trim(pg_result($res_peca,0,referencia));
					$peca_descricao   = trim(pg_result($res_peca,0,descricao));
					$bloqueada_garant = trim(pg_result($res_peca,0,bloqueada_garantia));

					if (strlen($item_alterar)>0){

						if ($item_alterar=='cancelar'){
							$sql =  "   UPDATE  tbl_os_item
								    SET     servico_realizado           = $id_servico_realizado_ajuste  ,
									    admin                       = $login_admin                  ,
									    liberacao_pedido            = FALSE,
									    liberacao_pedido_analisado  = FALSE,
									    data_liberacao_pedido       = null
								    WHERE   os_item = $os_item_alterar";

							$res = pg_exec($con,$sql);
							$msg_erro .= pg_errormessage($con);
							if (strlen($msg_erro)==0){
								if (strlen($justificativa)==0)
									$msg_erro="Informe a justificativa do cancelamento";
							}
							if (strlen($msg_erro)==0){
								$qtde_cancelado++;

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
											'Pedido de Peças CANCELADO'           ,
											'Seu pedido da peça $peca_referencia - $peca_descricao referente a OS $sua_os foi <b>cancelado</b> pela fábrica. <br><br>Justificativa da Fábrica: $justificativa',
											'Pedido de Peças',
											$login_fabrica,
											'f' ,
											't',
											$posto,
											't'
										);";
								$res = pg_exec($con,$sql);
								$msg_erro .= pg_errormessage($con);
								#echo nl2br($sql);
							}
						}
						if ($item_alterar=='autorizar'){
							$sql =  "   UPDATE  tbl_os_item
							    SET     admin = $login_admin
							    WHERE   os_item = $os_item_alterar 	";
							$res = pg_exec($con,$sql);
							$msg_erro .= pg_errormessage($con);
						}
					}
				}
			}

			if (strlen($msg_erro)==0){
				if ($qtde_cancelado>0){
					if ($qtde_cancelado==$resultado)
						$msg_posto= "Pedido de Peças Cancelado Pela Fábrica. Justificativa: ".utf8_decode($justificativa);
					else	$msg_posto= "Pedido de Peças Autorizado Pela Fábrica. Justificativa: ".utf8_decode($justificativa);
				}
				else{
					$msg_posto= "Pedido de Peças Autorizado Pela Fábrica. Justificativa: ".utf8_decode($justificativa);
				}
				$sql = "INSERT INTO tbl_os_status
						(os,status_os,data,observacao,admin)
						VALUES ($os,73,current_timestamp,'$msg_posto',$login_admin)";
				$res = pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
			}

			if (strlen($msg_erro)==0 AND strlen($o_finalizadas)>0){
					$sql = "UPDATE tbl_os SET finalizada='$o_finalizadas' WHERE os=$os";
					$res = pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);
			}

			if (strlen($msg_erro)>0){
				$res = pg_exec ($con,"ROLLBACK TRANSACTION");
				echo "erro|$msg_erro";
			}
			else {
				//$res = @pg_exec ($con,"ROLLBACK TRANSACTION"); //teste
				$res = pg_exec ($con,"COMMIT TRANSACTION");
				/*$msg = "A OS foi liberada para o posto";
				echo "ok|$msg";*/
			}
		#}
	}
	exit();
}

# no caso de troca, soh redireiciona para a OS cadastro onde sera feito a troca
if (strlen($_GET['trocar']) > 0 && strlen($os) > 0) {
	$sua_os=trim($_GET['trocar']);

	header("Location: os_cadastro.php?os=$os");
	exit();

	$res = @pg_exec($con,"BEGIN TRANSACTION");
	$sql = "INSERT INTO tbl_os_status
			(os,status_os,data,observacao)
			VALUES ($os,73,current_timestamp,'Troca do Produto')";

	#$res = pg_exec($con,$sql);
	$msg_erro = pg_errormessage($con);
	if (strlen($msg_erro)>0){
		$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		header("Location: $PHP_SELF?msg_erro=$msg_erro");
		exit();
	}
	else {
		//$res = @pg_exec ($con,"COMMIT TRANSACTION");
		$res = @pg_exec ($con,"COMMIT TRANSACTION");
		header("Location: os_cadastro.php?os=$os");
		exit();
	}
}


$layout_menu = "callcenter";
$title = "OS's com intevenção da Fábrica";
include "cabecalho.php";
?>

<style type="text/css">
.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
}
.Erro{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#CC3300;
	font-weight: bold;
	background-color:#FFFFFF;
}
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.inpu{
	border:1px solid #666;
	font-size:9px;
	height:12px;
}
.botao2{
	border:1px solid #666;
	font-size:9px;
}
.butt{
	border:1px solid #666;
	background-color:#ccc;
	font-size:9px;
	height:16px;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155) !important;
    background-color: #eff5ff;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
}

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
    padding:5px;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.titulo_justificar {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 16px;
	font-weight: bold;
	color: #000000;	
}
.bg_cancelado {
    background-color:#fbd9c8 !important;
}
.bg_autorizado {
    background-color:#d0e1f9 !important;
}
.btn {
  display: inline-block;
  *display: inline;
  padding: 4px 12px;
  margin-bottom: 0;
  *margin-left: .3em;
  font-size: 14px;
  line-height: 20px;
  *line-height: 20px;
  color: #333333;
  text-align: center;
  text-shadow: 0 1px 1px rgba(255, 255, 255, 0.75);
  vertical-align: middle;
  cursor: pointer;
  background-color: #f5f5f5;
  *background-color: #e6e6e6;
  background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#ffffff), to(#e6e6e6));
  background-image: -webkit-linear-gradient(top, #ffffff, #e6e6e6);
  background-image: -o-linear-gradient(top, #ffffff, #e6e6e6);
  background-image: linear-gradient(to bottom, #ffffff, #e6e6e6);
  background-image: -moz-linear-gradient(top, #ffffff, #e6e6e6);
  background-repeat: repeat-x;
  border: 1px solid #bbbbbb;
  *border: 0;
  border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.25);
  border-color: #e6e6e6 #e6e6e6 #bfbfbf;
  border-bottom-color: #a2a2a2;
  -webkit-border-radius: 4px;
     -moz-border-radius: 4px;
          border-radius: 4px;
  filter: progid:dximagetransform.microsoft.gradient(startColorstr='#ffffffff', endColorstr='#ffe6e6e6', GradientType=0);
  filter: progid:dximagetransform.microsoft.gradient(enabled=false);
  *zoom: 1;
  -webkit-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.05);
     -moz-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.05);
          box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.05);
}

.btn:hover,
.btn:active,
.btn.active,
.btn.disabled,
.btn[disabled] {
  color: #333333;
  background-color: #e6e6e6;
  *background-color: #d9d9d9;
}

.btn:active,
.btn.active {
  background-color: #cccccc \9;
}

.btn:first-child {
  *margin-left: 0;
}

.btn:hover {
  color: #333333;
  text-decoration: none;
  background-position: 0 -15px;
  -webkit-transition: background-position 0.1s linear;
     -moz-transition: background-position 0.1s linear;
       -o-transition: background-position 0.1s linear;
          transition: background-position 0.1s linear;
}

.btn:focus {
  outline: thin dotted #333;
  outline: 5px auto -webkit-focus-ring-color;
  outline-offset: -2px;
}

.btn.active,
.btn:active {
  background-image: none;
  outline: 0;
  -webkit-box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.15), 0 1px 2px rgba(0, 0, 0, 0.05);
     -moz-box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.15), 0 1px 2px rgba(0, 0, 0, 0.05);
          box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.15), 0 1px 2px rgba(0, 0, 0, 0.05);
}

.btn.disabled,
.btn[disabled] {
  cursor: default;
  background-image: none;
  opacity: 0.65;
  filter: alpha(opacity=65);
  -webkit-box-shadow: none;
     -moz-box-shadow: none;
          box-shadow: none;
}
.btn {
  border-color: #c5c5c5;
  border-color: rgba(0, 0, 0, 0.15) rgba(0, 0, 0, 0.15) rgba(0, 0, 0, 0.25);
}

.btn-info {
  color: #ffffff;
  text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.25);
  background-color: #49afcd;
  *background-color: #2f96b4;
  background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#5bc0de), to(#2f96b4));
  background-image: -webkit-linear-gradient(top, #5bc0de, #2f96b4);
  background-image: -o-linear-gradient(top, #5bc0de, #2f96b4);
  background-image: linear-gradient(to bottom, #5bc0de, #2f96b4);
  background-image: -moz-linear-gradient(top, #5bc0de, #2f96b4);
  background-repeat: repeat-x;
  border-color: #2f96b4 #2f96b4 #1f6377;
  border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.25);
  filter: progid:dximagetransform.microsoft.gradient(startColorstr='#ff5bc0de', endColorstr='#ff2f96b4', GradientType=0);
  filter: progid:dximagetransform.microsoft.gradient(enabled=false);
}

.btn-info:hover,
.btn-info:active,
.btn-info.active,
.btn-info.disabled,
.btn-info[disabled] {
  color: #ffffff;
  background-color: #2f96b4;
  *background-color: #2a85a0;
}

.btn-info:active,
.btn-info.active {
  background-color: #24748c \9;
}
.btn-small {
  padding: 2px 10px;
  font-size: 11.9px;
  -webkit-border-radius: 3px;
     -moz-border-radius: 3px;
          border-radius: 3px;
}

.btn-small [class^="icon-"],
.btn-small [class*=" icon-"] {
  margin-top: 0;
}
input{
    font-size: 12px !important;
    padding: 5px !important;
}
</style>

<? include "javascript_pesquisas.php"; ?>


<script type="text/javascript" src="js/jquery-1.8.3.min.js"></script>
<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" />
<script type="text/javascript">

function MostraEsconde(dados)
{
	if (document.getElementById)
	{
		var style2 = document.getElementById(dados);
		if (style2==false) return;
		if (style2.style.display=="block"){
			style2.style.display = "none";
			}
		else{
			style2.style.display = "block";
		}
 	}
}


function alterarCorLinha(os,evento,posicao){

    if(evento == 'cancelar'){
    	$(".btn-cancelar-"+posicao).remove();
    	$(".btn-autorizar-"+posicao).remove();
        var cor = "bg_cancelado";

    } else {
        var cor = "bg_autorizado";
    }
    $('.tr-'+posicao).find("td").addClass(cor);
}

function fnc_pesquisa_posto(campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
}
function fnc_pesquisa_produto (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&lbm=1" ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.produto		= document.frm_consulta.produto;
		janela.focus();
	}
}

function justificar_cancelameto(){
	var justificativa= prompt('Informe a justificativa do cancelamento.', '');

	if ( (justificativa=='') || (justificativa==null) || justificativa.length==0){
		alert('Justificativa não informada!\n\nPedido de peça não foi cancelado.');
		return false;
	}
	else{
		return justificativa;
	}
}

function verificar(botao,formulario,sua_os,posto_codigo,posto_nome,os,qtde_itens){
	//eval("var form = document."+formulario+".");

	var form = "document."+formulario+".";
	var cancelar=0;
	var autorizar=0;
	var itens_os='';
	var os_itens='';
	var just_cancelamento='';
	var justific='';

	if (qtde_itens == 1){
        var valorSelect = $('select[name=alterar_'+os+'_'+qtde_itens+']').val();
        var dividir = valorSelect.split('_');
		if ( dividir[1] == 'cancelar'){
			cancelar++;
		}else if (dividir[1] == 'autorizar'){
			autorizar++;
		}else if (dividir[1] == '' || dividir[1] == null ){
			alert('Selecione uma opção para a peça');
			return false;
		}

		itens_os += dividir[1]+',';
        os_itens += dividir[0]+',';
	}else{
/*		for (var i = 1; i < qtde_itens; i++) {

			if ($('select[name=alterar_'+os+'_'+i+']').val() == 'cancelar'){

				cancelar++;

			}else if ($('select[name=alterar_'+os+'_'+i+']').val() == 'autorizar'){

				autorizar++;

			}else if ($('select[name=alterar_'+os+'_'+i+']').val() == '' || $('select[name=alterar_'+os+'_'+i+']').val() == null ){

				alert('Selecione uma opção para a peça');
				return false;

			}

			if ($('select[name=alterar_'+os+'_'+i+']').val() != '' || $('select[name=alterar_'+os+'_'+i+']').val() != null){

				itens_os += $('select[name=alterar_'+os+'_'+i+']').val() + ',';

			}

		}*/

		var itens_os_arr = new Array();
		var os_itens_arr = new Array();

		var stop = false;

		$("select[name^=alterar_"+os+"_]").each(function(){

			var value = $(this).val();
            var dividir = value.split('_');
			if (dividir[1] == 'cancelar') {
				cancelar++;
			} else if (dividir[1] == 'autorizar') {
				autorizar++;
			} else if (dividir[1].length == 0 || dividir[1] == null) {
				alert('Selecione uma opção para a peça');
				stop = true;
				return false;
			}

			itens_os_arr.push(dividir[1]);
			os_itens_arr.push(dividir[0]);


		});

		if (stop == true) {
			return false;
		}

		itens_os = itens_os_arr.join(",");
		os_itens = os_itens_arr.join(",");
	}

	if (cancelar>0){
		var just = prompt('Informe a justificativa do cancelamento','');
		if ( (just=='') || (just==' ') || (just==null) || just.length==0){
			alert('A justificativa é obrigatória');
			return false;
		}
		$('input[name=justificativa]').val(just);

		justific = $('input[name=justificativa]').val();
	}

	if (cancelar==0 && autorizar>0){

		var just = prompt('Informe a justificativa da autorização (opcional)','');
		if ( just==null){
			return false;
		}
		$('input[name=justificativa]').val(just);

		justific = $('input[name=justificativa]').val();

	}

	if (confirm('Deseja continuar?\n\nOS: '+sua_os)){

		$.ajax({
			url: '<?echo "$PHP_SELF" ?>',
			data:{
                ajax:'ok'                       ,
                verificar:'ok'                  ,
                posto_codigo:posto_codigo       ,
                posto_nome:posto_nome           ,
                os:os                           ,
                itens:itens_os                  ,
                os_itens:os_itens               ,
                justificativa:justific
            }
        })
        .done(function(data) {

            results = data.split('|');

            if (results[0]=='ok'){
                alert(results[1]);
                $('tr[rel='+sua_os+']').hide();
                $('tr.tabela_sem_peca_'+os).show();

            }else if (results[0]=='erro'){
                alert(results[1]);
            }
		});
		// form.submit();

	}

}

function liberar(sua_os,posto_codigo,posto_nome,os){
	//eval("var form = document."+formulario+".");

	os_sem_peca = $('#os_sem_peca_'+os).val();
	if (confirm('Deseja continuar?\n\nOS: '+sua_os)){

		$.ajax({

			url: "<?echo $PHP_SELF;?>?ajax=ok&liberar=ok&posto_codigo="+posto_codigo+"&posto_nome="+posto_nome+"&os="+os+"&os_sem_peca="+os_sem_peca,
			success: function(data) {

				results = data.split('|');

				if (results[0]=='ok'){
					alert(results[1]);
					$('tr[rel='+sua_os+']').hide();
					$('tr.nao_hide_'+os).hide();
					$('tr.tirinha_'+os).hide();
				}else if (results[0]=='erro'){
					alert(results[1]);
				}

			}

		});
		// form.submit();

	}

}

function verificar_antigo(botao,formulario,sua_os){
	//eval("var form = document."+formulario+".");
	eval("var form = document."+formulario);
	var cancelar=0;
	for( var i = 0 ; i < form.length; i++ ){
		if (form.elements[ i ].type=='select-one'){
			if (form.elements[ i ].value=='cancelar'){
				cancelar++;
			}
			if (form.elements[ i ].value==''){
				alert('Selecione uma opção para a peça');
				return false;
			}
		}
	}
	if (cancelar>0){
		var just = prompt('Informe a justificativa do cancelamento','');
		if ( (just=='') || (just==' ') || (just==null) || just.length==0){
			alert('A justificativa é obrigatória');
			return false;
		}
		form.justificativa.value=just;
	}
	if (confirm('Deseja continuar?\n\nOS: '+sua_os))
		botao.alt='Aguarde';
		form.submit();
}

$(function(){

	$(".btn_ocultar_mostrar").click(function(){
        var btn = $(this);
        $(btn).parents("tr").find(".divpecas").each(function(){

            if($(this).is(":visible")){
                $(this).hide();
                $(btn).text("Mostrar Peças");
            }else{
                $(this).show();
                $(btn).text("Ocultar Peças");
            }
        });
    });
    /*$(".btn_cancelar").click(function() { 
        var os      = $(this).attr('rel');
        var posicao = $(this).data('posicao');

        Shadowbox.init({
            modal: true     
        });
        Shadowbox.open({
            content: "os_cancelar_sap.php?posicao="+posicao+"&janela=sim&tipo=cancelar_laudo_tecnico&os="+os+"&TB_iframe=true",
            player: "iframe",
            width:  700,                                
            height: 500       
        });

    });*/

	/*$("img[name=btn_cancelar]").click(function() {
      var obj = $(this).parent("td").parent("tr");

      if(confirm('Cancelar troca do produto? Esta OS será cancelada.')){
        $.ajax({
          url: '<?="$PHP_SELF?os='+this.id+'&cancelar=sim&ajax=cancelar_os" ?>',
          success: function(result) {
            if(result == "Os Cancelada com sucesso") {
              if(obj.next().attr('class') == 'justificativa') {
                obj.next().remove();
              }

              var tbody = obj.parents("tbody");

              obj.remove();

              if(tbody.html() == '') {
                $("tr[name=nenhum_os_intervencao]").show();
                $("p[name=quantidade_os_intervencao]").hide();
              }

            }
          }
        });
      }
	})*/
});

$(document).ready(function(){
	$(".divpecas").show();
})

$(function() {
    Shadowbox.init({
    	modal: true    	
    });
    $(".btn-autorizar").click(function(){
            var os = $(this).attr('rel');
            var posicao = $(this).data('posicao');
            enviar_justificativa(os, posicao);
            /*Shadowbox.open({
                content: "ajax_justificativa_autorizacao.php?ajax_carrega_justificativa=true&os="+os+"&posicao="+posicao,
                player: "iframe",
                width:  700,                                
                height: 500       
            });*/
        });
});

function enviar_justificativa(os, posicao = '', justificativa = ''){		


	$.ajax({
		url: "<? echo $PHP_SELF;?>",
		type: "get", 
		data:{
            ajax_justificar:'ok'            ,
            os:os                           ,
            justificativa:justificativa
        },
        complete: function(data){
        	var retorno = data.responseText.split("|");
        	if(retorno[0] = "ok"){
        		$(".tr-"+posicao).css({
					background: '#d0e1f9'
				});     
				$(".btn-cancelar-"+posicao).hide();
				$(".btn-autorizar-"+posicao).hide();
        		alert(retorno[1]);        		
        		Shadowbox.close();
        	} else {
        		alert(retorno[1]);
        		Shadowbox.close();
        	}
        }
    });
}
</script>
<br>

<?
	$dias = 5;
	/*HD: 93726*/
	if($login_fabrica ==3){
		$dias = 1;
	}
	#HD 14331
	echo "<div class='texto_avulso'>";
	echo "<p style='font-size:12px;text-align:left;padding:10px;'><b>ATENÇÃO: </b>As OSs em intervenção serão desconsideradas da INTERVENÇÃO automaticamente pelo sistema se não forem analisadas no prazo de $dias dias! O objetivo desta rotina é que o fabricante ajude o posto autorizado, e se isto não acontecer a OS sai da intervenção. <br >TELECONTROL</p>";

	echo "</div>";
	echo "<br>";
?>


<?
if(strlen($msg_erro)>0){
	echo "<table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
	echo "<tr >";
	echo "<td bgcolor='FFFFFF' width='60'class='Erro'><img src='imagens/proibido2.jpg' align='middle'></td><td  class='Erro' bgcolor='FFFFFF' align='left'> $msg_erro</td>";
	echo "</tr>";
	echo "</table><br>";
}

if(strlen($msg)>0){
	echo "<center><b style='font-size:12px;border:1px solid #999;padding:10px;background-color:#dfdfdf'>$msg</b></center><br>";
}


echo "<FORM METHOD='POST' NAME='frm_consulta' ACTION=\"$PHP_SELF\">";
?>
<input type='hidden' name='btnacao'>
<table class='formulario' width='700' cellspacing='0'  cellpadding='0' align='center'>
	<tr >
		<td class="titulo_tabela">Pesquisa por Posto</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>
			<TABLE style='margin: 0 auto;' width='90%' align='center' border='0' cellspacing='0' cellpadding='2'>
					<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
						<td align='left' style="font-size: 12px;">Código do Posto&nbsp;</td>
						<td><input type="text" name="posto_codigo" size="16" value="<? echo $posto_codigo ?>" class="frm">
							<img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.posto_codigo, document.frm_consulta.posto_nome, 'codigo')"></td>
						<td align='left' style="font-size: 12px;">Nome do Posto&nbsp;</td>
						<td>
							<input type="text" name="posto_nome" size="30"  value="<?echo $posto_nome?>" class="frm">
							<img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.posto_codigo, document.frm_consulta.posto_nome, 'nome')">
						</td>
					</tr>

					<tr>
						<td colspan="100%">&nbsp;</td>
					</tr>
					<tr>
						<td align="left"  style="font-size: 12px;">
							OS a Autorizar &nbsp;</td>
						<td colspan="2">
							<?php
								$checked = ($os_a_autorizar == 't') ? 'CHECKED' : "";
							?>
							<input type="checkbox" name="os_a_autorizar" id="os_a_autorizar" value="t" <?php echo $checked ?> >
						</td>
					</tr>

					<tr>
						<td colspan='100%' bgcolor="#D9E2EF" align='center'>
							<br>
							<input type="button" class="btn" value="Filtrar" onclick="javascript: document.frm_consulta.btnacao.value='filtrar' ; document.frm_consulta.submit() " ALT="Consultar OS's" style="cursor:pointer;width: 100px" />

							<br>
						</td>
					</tr>
                    <tr>
                        <td colspan="100%">&nbsp;</td>
                    </tr>
			</TABLE>
		</td>
	</tr>
	<tr>
		<td>
		</td>
	</tr>

</table>
</form>
<br>
<?
// if($ip<>'201.13.180.14' AND 1==2){
// echo "<p align='center'>Programa em manutenção, aguarde...</p>";
// exit;
// }

if ($btnacao=='filtrar'){
	//echo $filtro;
	$sql =  "SELECT tbl_os.os
			FROM tbl_os
			JOIN tbl_os_status USING(os)
			WHERE tbl_os.fabrica = $login_fabrica
			AND tbl_os.excluida IS NOT TRUE
			AND (status_os = 72 OR status_os = 73)
			AND (SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (72,73) AND tbl_os_status.fabrica_status=$login_fabrica ORDER BY data DESC LIMIT 1) = 72
			";

	$sql = "SELECT interv.os
			FROM (
			SELECT
			ultima.os,
			(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.fabrica_status=$login_fabrica AND status_os IN (72,73) AND tbl_os_status.os = ultima.os ORDER BY data DESC LIMIT 1) AS ultimo_status
			FROM (SELECT DISTINCT os FROM tbl_os_status WHERE status_os IN (72,73) AND data < current_timestamp-interval'5 minutes' AND tbl_os_status.fabrica_status=$login_fabrica) ultima
			) interv
			WHERE interv.ultimo_status = 72";


	$res_status = pg_exec($con,$sql);
	$os_array = array();
	$total=pg_numrows($res_status);
	for ($t = 0 ; $t < $total ; $t++) {
		array_push($os_array,pg_result($res_status,$t,os));
	}
	array_unique($os_array);

	if (count($os_array)>0){
		$os_array = "AND tbl_os.os IN (".implode(',',$os_array).")";
	}else{
		$os_array = "";
	}

		## QUERY NOVA (APARENTEMENTE ESTA MAIS RAPIDA)
		$sql =  "
				SELECT distinct tbl_os.os                                             ,
					tbl_os.sua_os                                                     ,
					LPAD(tbl_os.sua_os,20,'0')                   AS ordem             ,
					TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')  AS digitacao         ,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura          ,
					tbl_os.data_abertura                         AS abertura2         ,
					tbl_os.data_abertura   AS abertura_os       ,
					tbl_os.serie                                                      ,
					tbl_os.consumidor_nome                                            ,
					tbl_os.obs                                            ,
					tbl_os.admin                                                      ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')  as data_nf,
					tbl_posto_fabrica.codigo_posto                                    ,
					tbl_posto.nome                              AS posto_nome         ,
					tbl_posto.fone                              AS posto_fone         ,
					tbl_produto.referencia                      AS produto_referencia ,
					tbl_produto.descricao                       AS produto_descricao  ,
					tbl_produto.troca_obrigatoria               AS troca_obrigatoria  ,
					tbl_os_retorno.nota_fiscal_envio,
					TO_CHAR(tbl_os_retorno.data_nf_envio,'DD/MM/YYYY')      AS data_nf_envio        ,
					tbl_os_retorno.numero_rastreamento_envio,
					TO_CHAR(tbl_os_retorno.envio_chegada,'DD/MM/YYYY hh:mm:ss')      AS envio_chegada        ,
					tbl_os_retorno.nota_fiscal_retorno,
					TO_CHAR(tbl_os_retorno.data_nf_retorno,'DD/MM/YYYY')      AS data_nf_retorno        ,
					tbl_os_retorno.numero_rastreamento_retorno,
					TO_CHAR(tbl_os_retorno.retorno_chegada,'DD/MM/YYYY hh:mm:ss')      AS retorno_chegada,
					tbl_os_retorno.admin_recebeu AS admin_recebeu,
					tbl_os_retorno.admin_enviou AS admin_enviou,
					(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (72,73) AND tbl_os_status.fabrica_status=$login_fabrica ORDER BY data DESC LIMIT 1) AS status_os,
					(SELECT descricao FROM tbl_os_status join tbl_status_os using (status_os) WHERE tbl_os.os = tbl_os_status.os AND status_os IN (72,73) AND tbl_os_status.fabrica_status=$login_fabrica ORDER BY data DESC LIMIT 1) AS status_descricao,
					(SELECT observacao FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (72,73) AND tbl_os_status.fabrica_status=$login_fabrica ORDER BY data DESC LIMIT 1) AS status_observacao
				FROM tbl_os
					$join_os_a_autorizar
					JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = $login_fabrica
					LEFT JOIN tbl_os_retorno on tbl_os_retorno.os = tbl_os.os
					WHERE tbl_os.fabrica = $login_fabrica
					AND tbl_os.excluida IS NOT TRUE
					$os_array
					$sql_adicional
					$sql_adicional_2
					$cond_os_a_autorizar
				ORDER BY abertura_os ASC";
		//$sqlT = str_replace ("\n"," ",$sql) ;
		//$sqlT = str_replace ("\t"," ",$sqlT) ;
		//$resT = @pg_exec ($con,"/* QUERY -> $sqlT  */");
		//flush();
		//$res = pg_exec($con,$sql);
		//$resultados = pg_numrows($res);
		$sqlCount  = "SELECT count(*) FROM (";
		$sqlCount .= $sql;
		$sqlCount .= ") AS count";
		if (!$os_a_autorizar){
			// ##### PAGINACAO ##### //
			require "_class_paginacao.php";
			//		exit;
			// definicoes de variaveis
			$max_links = 11;	// máximo de links à serem exibidos
			$max_res   = 30;	// máximo de resultados à serem exibidos por tela ou pagina
			$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
			$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

			if (strlen($os_array)>0){
				$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");
			}
			// ##### PAGINACAO ##### //
		}else{
			$res = pg_query($con,$sql);
		}
		//echo "<br>";

         if ($login_fabrica == 3) {
            echo "
            <div style='font-size:13px;margin: 0 auto; width: 300px;text-align:center;border:solid 1px #ddd'>
                <table border='0' cellpadding='2' cellspacing='2' width='100%'>
                    <tr height='20' >
                        <td colspan='2' bgcolor='#dddddd'>Legenda</td>
                    </tr>
                    <tr height='20' >
                        <td width='20' bgcolor='#fbd9c8'></td>
                        <td align='left'> Cancelado</td>
                    </tr>
                    <tr height='20' >
                        <td width='20' bgcolor='#d0e1f9'></td>
                        <td align='left'> Autorizado</td>
                    </tr>
                </table>
            </div><br><br>
            ";
        }
		echo "<center><table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#E6E8FA' width='98%'>";
		echo "<tr class='Titulo' height='25' >";
		echo "<td width='70'>OS</td>";
		echo "<td>AB</td>";
		echo "<td>PEDIDO</td>";
		echo "<td>NF</td>";
		echo "<td>POSTO</td>";
		echo "<td width='75'>FONE POSTO</td>";
		echo "<td>CONSUMIDOR</td>";
		echo "<td width='75'>PRODUTO</td>";
		echo "<td>AÇÕES</td></tr>";

		if (strlen($os_array)>0){
			$total=pg_numrows($res);
		}else{
			$total = 0;
		}
		$achou=0;
		$cores=0;

		for ($i = 0 ; $i < $total ; $i++) {
			$os                 = trim(pg_result($res,$i,os));
			$sua_os             = trim(pg_result($res,$i,sua_os));
			$digitacao          = trim(pg_result($res,$i,digitacao));
			$abertura           = trim(pg_result($res,$i,abertura));
			$abertura2          = trim(pg_result($res,$i,abertura2));
			$obs				= trim(pg_result($res,$i,obs));
			$serie              = trim(pg_result($res,$i,serie));
			$data_nf              = trim(pg_result($res,$i,data_nf));
			$consumidor_nome    = trim(pg_result($res,$i,consumidor_nome));
			$codigo_posto       = trim(pg_result($res,$i,codigo_posto));
			$posto_nome_bd      = trim(pg_result($res,$i,posto_nome));
			$produto_referencia = trim(pg_result($res,$i,produto_referencia));
			$produto_descricao  = trim(pg_result($res,$i,produto_descricao));
			$produto_troca_obrigatoria   = trim(pg_result($res,$i,troca_obrigatoria));
			$posto_fone			= substr(trim(pg_result($res,$i,posto_fone)),0,17);
			$status_os			= trim(pg_result($res,$i,status_os));
			$status_descricao	= trim(pg_result($res,$i,status_descricao));
			$status_observacao	= trim(pg_result($res,$i,status_observacao));
			$admin_recebeu		= trim(pg_result($res,$i,admin_recebeu));
			$admin_enviou		= trim(pg_result($res,$i,admin_recebeu));

			$nota_fiscal_envio	= trim(pg_result($res,$i,nota_fiscal_envio));
			$data_nf_envio		= trim(pg_result($res,$i,data_nf_envio));
			$numero_rastreamento_envio = trim(pg_result($res,$i,numero_rastreamento_envio));
			$envio_chegada		= trim(pg_result($res,$i,envio_chegada));
			$nota_fiscal_retorno = trim(pg_result($res,$i,nota_fiscal_retorno));
			$data_nf_retorno	= trim(pg_result($res,$i,data_nf_retorno));
			$numero_rastreamento_retorno = trim(pg_result($res,$i,numero_rastreamento_retorno));
			$retorno_chegada	= trim(pg_result($res,$i,retorno_chegada));

			if ($login_fabrica == 3 && strlen($os_a_autorizar) > 0 && strpos($status_observacao, "Cancelado Pela") !== false) {
				continue;
			}
			//$admin_chegada = trim(pg_result($res,$i,admin_chegada));
			//$admin_reparo = trim(pg_result($res,$i,admin_reparo));

			$sql_status  = "SELECT TO_CHAR(data,'DD/MM/YYYY') AS data, data as data_pedido_bd
							FROM tbl_os_status
							WHERE tbl_os_status.os= $os
							AND tbl_os_status.fabrica_status=$login_fabrica
							ORDER BY tbl_os_status.data DESC LIMIT 1";
			$res_status = pg_exec($con,$sql_status);
			$data_pedido = trim(pg_result($res_status,0,0));
			$data_pedido_bd = trim(pg_result($res_status,0,1));


			if ($status_os=="73") continue; //volta ao laço "for"
			$achou=1;

			if ($cores++ % 2 == 0) $cor   = "#F1F4FA";
			else 		        $cor   = "#FFF5F0";

//			if ($status_os == "72") $cor = "#F1F4FA";
			if ($status_os == "73") $cor = "#FFFF99";

            if ($login_fabrica == 3) {


                if ($status_os == 72 && strpos($status_observacao, "Cancelado Pela") !== false){
                    $cor = "#fbd9c8";
                } elseif ($status_os == 73 && strpos($status_observacao, "Cancelado Pela") === false){
                    $cor = "#d0e1f9";
                } else {
                	$cor = "#ffffff";
                }
            }

			if (strlen($sua_os) == 0) $sua_os = $os;

			$justif=""; // POG -> Vou refazer
			if (strlen($status_observacao)>0){
				$justif = trim(str_replace('Peça bloqueada para garantia.','',$status_observacao));
				$justif = trim(str_replace('Peça da OS bloqueada para garantia.','',$justif));
 				$justif = trim(str_replace('Peça bloqueada para garantia','',$justif));
 				$justif = trim(str_replace('Peça da OS bloqueada para garantia','',$justif));
 				$justif = trim(str_replace('Justificativa:','',$justif));
			}
			if (strlen($justif)==0){
				$justif = trim(str_replace('Peça bloqueada para garantia.','',$obs));
				$justif = trim(str_replace('Peça da OS bloqueada para garantia.','',$justif));
				$justif = trim(str_replace('Peça bloqueada para garantia','',$justif));
				$justif = trim(str_replace('Peça da OS bloqueada para garantia','',$justif));
				$justif = trim(str_replace('Justificativa:','',$justif));
			}
			if (strlen($justif)==0) $justif = "<b style='color:red;font-weight:normal'>Posto não informou a justificativa</b>";
			else                 $justif = "<i>$justif</i>";

			echo "<FORM METHOD='POST' NAME='frm_atualizar_$os' ACTION=\"$PHP_SELF?btnacao=$btnacao&posto_codigo=$posto_codigo&posto_nome=$posto_nome\"  style='display:block'>";
			echo "<input type='hidden' name='os' value='$os'>";
			echo "<input type='hidden' name='atualizar' value='$os'>";
			echo "<input type='hidden' name='justificativa' value=''>";

			echo "<tr class='Conteudo tr-$i nao_hide_$os' height='20' bgcolor='$cor' bordercolor='#E6E8FA' align='left'  >";
			echo "<td nowrap ><a href='os_press.php?os=$os' target='_blank' style='font-size:13px'>$sua_os</a></td>";
			echo "<td nowrap >$abertura</td>";
			echo "<td nowrap >$data_pedido</td>";
			echo "<td nowrap >$data_nf</td>";
			echo "<td nowrap title='$codigo_posto - $posto_nome_bd'>$codigo_posto - ".substr($posto_nome_bd,0,20)."</td>";
			echo "<td nowrap>$posto_fone</td>";
			echo "<td nowrap title='$consumidor_nome'>".substr($consumidor_nome,0,15)."</td>";
			$produto = $produto_referencia . " - " . $produto_descricao;
			echo "<td nowrap title='Referência: $produto_referencia \nDescrição: $produto_descricao'>".substr($produto,0,40)."</td>";
			echo "<td align='center' style='font-size:9px' nowrap >";
			if($login_fabrica != 3) {
				echo "<img src='imagens/btn_trocar.gif' ALT='Efetuar a troca do Produto' border='0' style='cursor:pointer;' onClick=\"javascript: if(confirm('Deseja realizar a troca deste produto pela Fábrica? Esta OS será liberada'))  document.location='$PHP_SELF?os=$os&trocar=$sua_os';\">&nbsp;&nbsp;";
			} else {
                if (strpos($status_observacao, "Cancelado Pela") === false) {
				    echo "<img src='imagens/btn_autorizar.gif' ALT='G' border='0' rel='$os' style='cursor:pointer;' data-posicao='{$i}' class='btn-autorizar btn-autorizar-{$i}'>";
                }
			}

			echo "</td>\n";
			echo "</tr>";
			$pecas="";

			$sql_peca = "SELECT  tbl_os_item.os_item AS item,
						tbl_peca.troca_obrigatoria AS troca_obrigatoria,
						tbl_peca.retorna_conserto AS retorna_conserto,
						tbl_peca.referencia AS referencia,
						tbl_peca.descricao AS descricao,
						tbl_peca.peca AS peca,
						tbl_peca.bloqueada_garantia,
						tbl_os_item.digitacao_item
						FROM tbl_os_produto
						JOIN tbl_os_item USING(os_produto)
						JOIN tbl_peca USING(peca)
						WHERE tbl_os_produto.os=$os
						AND tbl_os_item.servico_realizado=$id_servico_realizado
						AND tbl_peca.bloqueada_garantia = 't'
						AND tbl_os_item.pedido IS NULL";

			$res_peca = pg_exec($con,$sql_peca);
			$resultado = pg_numrows($res_peca);
			
			$sqld = "SELECT '$data_pedido_bd'::date - '$abertura2'::date";
			$resd = pg_exec($con,$sqld);
			$retorno = pg_result($resd,0,0);

			if ($resultado>0){

				$rowspan=0;

				for($q=0;$q<$resultado;$q++){
					$bloqueada_garantia = trim(pg_result($res_peca,$q,bloqueada_garantia));
					if ($retorno >= $qtde_dias_intervencao_sap OR $bloqueada_garantia == 't') {
						$rowspan++;
					}
				}

				$entrou=0;
				$z = 0;

				for($j=0;$j<$resultado;$j++){

					$item               = trim(pg_result($res_peca,$j,item));
					$peca_referencia    = trim(pg_result($res_peca,$j,referencia));
					$peca_descricao     = trim(pg_result($res_peca,$j,descricao));
					$bloqueada_garantia = trim(pg_result($res_peca,$j,bloqueada_garantia));
					$digitacao_item     = trim(pg_result($res_peca,$j,digitacao_item));
					$peca               = trim(pg_result($res_peca,$j,peca));					

					if ($retorno >= $qtde_dias_intervencao_sap OR $bloqueada_garantia == 't') {
						$z++;
						echo "<tr rel='$sua_os' class='Conteudo tr-$i' height='20' bgcolor='$cor' align='left' valign='top'>\n";
						if ($entrou==0){
							echo "<td align='center' rowspan='$rowspan'>\n";
							echo "Justificativa:";
							echo "</td>";
							echo "<td colspan='4' align='left' rowspan='$rowspan'>\n";
							if ($retorno >= $qtde_dias_intervencao_sap){
								echo "<b>(OS aberta a mais de ".$qtde_dias_intervencao_sap." dias)</b><br>";
							}
							echo "$justif</td>\n";
							echo "<td align='right' rowspan='$rowspan' valign='middle'><b style='color:gray;font-weight:normal' >Peças Solicitada</b></td>\n";
						}	

						if($login_fabrica == 3 and $j == 0){														
							echo "<td colspan='2' rowspan='$rowspan' align='center'>";
							echo "<br>
                            <button type='button' name='btn_ocultar_mostrar' class='btn_ocultar_mostrar btn btn-small btn-info' id='btn_ocultar_mostrar'>Ocultar Peças</button>
                            <br><br>";

							$sql_pecax = "SELECT  tbl_os_item.os_item AS item,
										tbl_peca.troca_obrigatoria AS troca_obrigatoria,
										tbl_peca.retorna_conserto AS retorna_conserto,
										tbl_peca.referencia AS referencia,
										tbl_peca.descricao AS descricao,
										tbl_peca.peca AS peca,
										tbl_peca.bloqueada_garantia,
										tbl_os_item.digitacao_item
										FROM tbl_os_produto
										JOIN tbl_os_item USING(os_produto)
										JOIN tbl_peca USING(peca)
										WHERE tbl_os_produto.os=$os
										AND tbl_os_item.servico_realizado=$id_servico_realizado
										AND tbl_peca.bloqueada_garantia = 't'
										AND tbl_os_item.pedido IS NULL";

							$res_pecax = pg_exec($con,$sql_pecax);
							$resultadox = pg_numrows($res_pecax);

							for($xpecas=0;$xpecas<$resultadox;$xpecas++){								
								$xitem               = trim(pg_result($res_pecax,$xpecas,item));
								$xpeca_referencia    = trim(pg_result($res_pecax,$xpecas,referencia));
								$xpeca_descricao     = trim(pg_result($res_pecax,$xpecas,descricao));
								$xbloqueada_garantia = trim(pg_result($res_pecax,$xpecas,bloqueada_garantia));
								$xdigitacao_item     = trim(pg_result($res_pecax,$xpecas,digitacao_item));	
								$pecasx              = trim(pg_result($res_pecax,$xpecas,peca));
						
								echo "<div name='divpecas_" . $xpecas . "' id='divpecas' style='display:none; color: #FFFFFF;' class='divpecas'><a href='peca_cadastro.php?peca=$pecasx' target='_blank'>". substr("$xpeca_referencia - $xpeca_descricao",0,40)."</a><input type='hidden' value='". $id_servico_realizado ."' class='id_servico_realizado' /></div>";									
							}							
							echo "</td>\n";
						}

						if($login_fabrica != 3){
							echo "<td colspan='2' align='right' nowrap>";
							echo "<a href='peca_cadastro.php?peca=$peca' target='_blank'>". substr("$peca_referencia - $peca_descricao",0,40)."</a>&nbsp;&nbsp;&nbsp;&nbsp;";
							echo "</td>\n";

							echo "<select name='alterar_".$os."_".$z."' style='font-weight:bold;font-size:12px'>";
							echo "<option value='' size='20'></option>";
							echo "<option value='".$item."_autorizar' style='color:blue; font-weight:bold;'>Autorizar</option>";
							echo "<option value='".$item."_cancelar'  style='color:red; font-weight:bold;'>Cancelar</option>";
							echo "</select>&nbsp;&nbsp;&nbsp;";
						}						

						if ($entrou==0){
							echo "<td align='center' rowspan='$rowspan' valign='middle'>\n";
							if($login_fabrica != 3) {														
								echo "<img src='imagens/btn_gravar.gif' height='16px' ALT='Gravar alteração.' border='0' style='cursor:pointer;' onClick=\"javascript:if (this.alt=='Aguarde'){alert('Aguarde submissão'); return false;}  verificar(this,'frm_atualizar_$os','$sua_os','$posto_codigo','$posto_nome','$os','".$resultado."');\" >\n";
								//echo "<img src='imagens/btn_autorizar.gif' ALT='G' border='0' style='cursor:pointer;' onClick=\"javascript: verificar('frm_atualizar_$os','$sua_os');\">";								
							} else {
				                if (strpos($status_observacao, "Cancelado Pela") === false) {
                                    //echo "<input type='button' name='btn_cancelar_ocultar_pecas' id='btn_cancelar_ocultar_pecas' class='btn_cancelar_ocultar_pecas' value='Cancelar e Ocultar Peças' rel='$os' />";       
                                    echo '<a  href="os_cancelar_sap.php?posicao='.$i.'&janela=sim&tipo=cancelar_laudo_tecnico&os='.$os.'&TB_iframe=true" data-posicao="'.$i.'" class="thickbox btn-cancelar-'.$i.'" >';
                                    echo "<img src='imagens/btn_cancelar.gif' ALT='G' rel='$os' border='0' style='cursor:pointer;'></a>";
                                    //echo "<img src='imagens/btn_cancelar.gif' ALT='G' border='0' style='cursor:pointer;' class='btn_cancelar_ocultar_pecas' rel='$os'>";
                                }
							}

							echo "</td>\n";
						}
						$entrou++;						
						echo "</tr>\n";

					}

				}

			}

			if ($entrou==0){

					echo "<tr class='Conteudo' rel='$sua_os' height='20' bgcolor='$cor' align='left' valign='top'>";
					echo "<td align='center' colspan='8'>";
					echo "<input type='hidden' name='os_sem_peca' id='os_sem_peca_$os' value='$os'>";
					if ($login_fabrica==3){
						echo "<b style='color:red;font-weight:normal'>Esta OS não possui mais peças bloqueadas para garantia. ADMIN alterou a OS ou cadastro da peça foi alterado.</b>";
					}
					if ($login_fabrica==11 or $login_fabrica == 172){
						echo "<b style='color:red;font-weight:normal'>$status_observacao</b>";
					}
					echo "</td>\n";
					echo "<td align='center' valign='middle'>";
					if($login_fabrica != 3){
						echo "<a href=\"javascript: liberar('$sua_os','$posto_codigo','$posto_nome','$os'); \" title='Liberar'>LIBERAR OS</a>";
					}
					echo "</td>";
					echo "</tr>";

			}

			//esta tabela fica oculta por default para quando autorizar/cancelar uma OS, ela substituir as TR's que fazem parte da autorização/cancelamento de OS.
			echo "<tr class='Conteudo tabela_sem_peca_$os' style='display:none' rel='$sua_os' height='20' bgcolor='$cor' align='left' valign='top'>";
				echo "<td align='center' colspan='8'>";
				echo "<input type='hidden' name='os_sem_peca' id='os_sem_peca_$os' value='$os'>";
				if ($login_fabrica==3){
					echo "<b style='color:red;font-weight:normal'>Esta OS não possui mais peças bloqueadas para garantia. ADMIN alterou a OS ou cadastro da peça foi alterado.</b>";
				}
				if ($login_fabrica==11 or $login_fabrica == 172){
					echo "<b style='color:red;font-weight:normal'>$status_observacao</b>";
				}
				echo "</td>\n";
				echo "<td align='center' valign='middle'>";
				if($login_fabrica != 3){
					echo "<a href=\"javascript: liberar('$sua_os','$posto_codigo','$posto_nome','$os'); \" title='Liberar'>LIBERAR OS</a>";
				}
				echo "</td>";
			echo "</tr>";

			echo "<tr  bgcolor='#596D9B' class='tirinha_$os'  height='3'>";
			echo "<td colspan='9'>";
			echo "</td>";
			echo "</tr>";
			echo "</form>";

		}
		if ($achou==0){
			echo "<tr class='Conteudo' height='20' bgcolor='#FFFFCC' align='left'>
				<td colspan='12' style='padding:10px'>NENHUMA OS COM INTERVENÇÃO DO SAP</td>
				</tr>";
			echo "</table></center>";
		}
		else{
			echo "</table></center>";

			if (!$os_a_autorizar){
				// ##### PAGINACAO ##### //
				// links da paginacao
				echo "<br>";
				echo "<div>";

				if($pagina < $max_links) {
					$paginacao = pagina + 1;
				}else{
					$paginacao = pagina;
				}

				// paginacao com restricao de links da paginacao
				// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
				$todos_links = $mult_pag->Construir_Links("strings", "sim");

				// função que limita a quantidade de links no rodape
				$links_limitados = $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

				for ($n = 0; $n < count($links_limitados); $n++) {
					echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
				}

				echo "</div>";

				$resultado_inicial = ($pagina * $max_res) + 1;
				$resultado_final   = $max_res + ( $pagina * $max_res);
				$registros         = $mult_pag->Retorna_Resultado();

				$valor_pagina   = $pagina + 1;
				$numero_paginas = intval(($registros / $max_res) + 1);

				if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

				if ($registros > 0){
					echo "<br>";
					echo "<div>";
					echo "Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.";
					echo "<font color='#cccccc' size='1'>";
					echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
					echo "</font>";
					echo "</div>";
				}
				// ##### PAGINACAO ##### //

			}
		}

	//}
}

include "rodape.php"

?>

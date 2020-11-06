<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="call_center";
include "autentica_admin.php";
include "funcoes.php";

$msg = "";
$msg_erro = "";
// pega os valores das variaveis dadas como parametros de pesquisa e coloca em um cookie
setcookie("cookredirect", $_SERVER["REQUEST_URI"]); // expira qdo fecha o browser

$os             = $_GET["excluir"];
$posto_exclusao = $_GET["posto_exclusao"];
$codigo_posto_exclusao = $_GET["codigo_posto_exclusao"];

if ($_POST["excluir_os_selecionadas"]) {
	$motivo_exclui_os = trim($_POST["motivo_exclui_os"]);

	if (empty($motivo_exclui_os)) {
		$msg_erro = "Informe o motivo para excluir as OSs selecionadas";
	} else {
		foreach ($_POST["exclui_os_revenda"] as $key => $value) {
			$os_exclui = $value;
			$os_matriz = $_POST["os_matriz"][$key];

			if ($os_matriz == "f") {
				$sql = "SELECT tbl_os.sua_os, tbl_os.posto, tbl_posto_fabrica.codigo_posto FROM tbl_os INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica WHERE tbl_os.fabrica = {$login_fabrica} AND tbl_os.os = {$os_exclui}";
				$res = pg_query($con, $sql);

				$sua_os = pg_fetch_result($res, 0, "sua_os");
				$posto  = pg_fetch_result($res, 0, "posto");
				$codigo_posto = pg_fetch_result($res, 0, "codigo_posto");

				$sql = "SELECT fn_os_excluida({$os_exclui}, {$login_fabrica}, {$login_admin})";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					$msg_erro .= "Erro ao excluir a OS: {$codigo_posto}{$sua_os}, ".pg_last_error()."<br />";
				} else {
					$sql = "UPDATE tbl_os_excluida SET motivo_exclusao = '{$motivo_exclui_os}' WHERE os = {$os_exclui} AND fabrica = {$login_fabrica}";
					$res = pg_query($con, $sql);

					/*$sql = "INSERT INTO tbl_comunicado (
								descricao              ,
								mensagem               ,
								tipo                   ,
								fabrica                ,
								obrigatorio_os_produto ,
								obrigatorio_site       ,
								posto                  ,
								ativo
							) VALUES (
								'Ordem de Serviço EXCLUÍDA pela fábrica',
								'A ordem de serviço {$codigo_posto}{$sua_os} foi excluída pela fábrica. <br><br>$motivo_exclui_os',
								'Ordem de Serviço',
								$login_fabrica,
								'f' ,
								't',
								$posto,
								't'
							);";
					$res = pg_query($con,$sql);*/

					$msg_success .= "OS {$codigo_posto}{$sua_os} foi excluída<br />";

					unset($_POST["exclui_os_revenda"][$key], $_POST["os_matriz"][$key]);
				}
			} else {
				$sql = "UPDATE tbl_os_revenda SET excluida = TRUE WHERE fabrica = {$login_fabrica} AND os_revenda = {$os_exclui}";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					$msg_erro .= "Erro ao excluir a OS: {$os_exclui}<br />";
				} else {
					$msg_success .= "OS {$os_exclui} foi excluída<br />";

					unset($_POST["exclui_os_revenda"][$key], $_POST["os_matriz"][$key], $motivo_exclui_os);
				}
			}
		}
	}
}

if (strlen($os) > 0) {
	$sql =	"SELECT sua_os, posto
			FROM tbl_os
			WHERE os = $os;";
	$res = @pg_exec($con,$sql);
	$msg_erro .= pg_errormessage($con);
	if (@pg_numrows($res) == 1) {
		$sua_os = @pg_result($res,0,0);
		$sua_os_explode = explode("-", $sua_os);
		$xsua_os = $sua_os_explode[0];
	}
	$login_posto = $posto_exclusao;

	if (!strlen($login_posto)) {
		$login_posto = pg_fetch_result($res, 0, "posto");
	}

	$res = @pg_query($con,"BEGIN TRANSACTION");

	if ($login_fabrica == 3) {
		$sql = "UPDATE tbl_os SET excluida = 't' , admin_excluida = $login_admin WHERE os = $os AND fabrica = $login_fabrica";
		$res = @pg_exec($con,$sql);

		#158147 Paulo/Waldir desmarcar se for reincidente
		$sql = "SELECT fn_os_excluida_reincidente($xxos,$login_fabrica)";
		$res = pg_exec($con, $sql);

	} else {
		$sql = "SELECT os
			FROM tbl_os
			WHERE os = $os
			AND   fabrica = $login_fabrica
			AND   posto = $login_posto ";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0) {
			$sql = "SELECT fn_os_excluida($os,$login_fabrica,$login_admin);";
			$res = pg_exec($con,$sql);
		}
	}

	if (pg_errormessage($con)){
                $erro = explode("ERROR:",pg_errormessage($con));
                $msg_erro .= $erro[1];
        }

	$xsua_os = strtoupper($xsua_os);
	if (strlen($msg_erro) == 0) {
		$sql =	"SELECT sua_os
				FROM tbl_os
				WHERE sua_os LIKE '$xsua_os-%'
				AND   posto   = $login_posto
				AND   fabrica = $login_fabrica;";
		$res = @pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);



		if (@pg_numrows($res) == 0) {
			$sql = "DELETE FROM tbl_os_revenda
					WHERE  tbl_os_revenda.sua_os  = '$xsua_os'
					AND    tbl_os_revenda.fabrica = $login_fabrica
					AND    tbl_os_revenda.posto   = $login_posto";
			$res = @pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
			echo pg_last_error();
		}


	}

	//Exclusão de OS Revenda Blackedecker HD 301414
	if($login_fabrica==1){
		$sql = "SELECT tbl_os_revenda.sua_os, tbl_posto_fabrica.codigo_posto
				FROM  tbl_os_revenda
				JOIN  tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os_revenda.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_os_revenda.os_revenda = $os
				AND   tbl_os_revenda.fabrica    = $login_fabrica";
		#echo nl2br($sql);
		$res = @pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);

		if (@pg_numrows($res) > 0) {
			$sua_os_revenda = pg_result($res,0,sua_os);
			$codigo_posto   = pg_result($res,0,codigo_posto);

			$sua_os_black = $codigo_posto.$sua_os_revenda;

			$sql = "SELECT tbl_os.sua_os
					FROM tbl_os
					WHERE sua_os LIKE '$sua_os_revenda-%'
					AND   posto   = $posto_exclusao
					AND   fabrica = $login_fabrica
					AND   excluida IS NOT TRUE;";
			echo nl2br($sql);
			$res = @pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
			echo pg_last_error();

			if (@pg_numrows($res) == 0) {
				$sql = "UPDATE tbl_os_revenda SET excluida = 't' WHERE os_revenda = $os";
				#echo nl2br($sql);
				$res = @pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}else{
				$msg_erro .= "Não foi possível excluir a OS de revenda $sua_os_black pois ainda existem OS relacionadas (explodidas) a ela que não foram excluídas. Clique <A HREF='os_consulta_lite.php?sua_os=$sua_os_revenda&codigo_posto=$codigo_posto_exclusao&btn_acao=PESQUISAR' target='_blank'>aqui</A> para consultar estas OS.";
			}
		}
	}

	if (strlen ($msg_erro) == 0) {
		$res = @pg_query ($con,"COMMIT TRANSACTION");

		$url = $_COOKIE["cookredirect"];
		header("Location: $url");
		exit;
	}else{
		$res = @pg_query ($con,"ROLLBACK TRANSACTION");
	}
}

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);
if (strlen($_GET["acao"]) > 0)  $acao = strtoupper($_GET["acao"]);

if ($acao == "PESQUISAR") {
	if (strlen(trim($_POST["opcao1"])) > 0)  $opcao1 = trim($_POST["opcao1"]);
	if (strlen(trim($_POST["opcao2"])) > 0)  $opcao2 = trim($_POST["opcao2"]);
	if (strlen(trim($_POST["opcao3"])) > 0)  $opcao3 = trim($_POST["opcao3"]);
	if (strlen(trim($_POST["opcao4"])) > 0)  $opcao4 = trim($_POST["opcao4"]);
	if (strlen(trim($_POST["opcao5"])) > 0)  $opcao5 = trim($_POST["opcao5"]);
	if (strlen(trim($_POST["opcao6"])) > 0)  $opcao6 = trim($_POST["opcao6"]);
	if (strlen(trim($_POST["opcao7"])) > 0)  $opcao7 = trim($_POST["opcao7"]);
	if (strlen(trim($_POST["opcao8"])) > 0)  $opcao8 = trim($_POST["opcao8"]);
	if (strlen(trim($_POST["opcao9"])) > 0)  $opcao9 = trim($_POST["opcao9"]);

	if (strlen($opcao1) == 0 && strlen($opcao2) == 0 && strlen($opcao3) == 0 && strlen($opcao4) == 0 && strlen($opcao5) == 0 && strlen($opcao6) == 0) {
		$msg .= " Selecione Pelo Menos uma Opção para Realizar a Pesquisa. ";
	}

	//validação Data Black
	if ($login_fabrica == 1){
		if (strlen($erro) == 0 && strlen($opcao1) > 0) {

			$data_inicial = $_POST['data_inicial'];
		    if (strlen($data_inicial)==0){
		        $data_inicial = trim($_GET['data_inicial']);
		    }
		    $data_final   = $_POST['data_final'];
		    if (strlen($data_final)==0){
		        $data_final = trim($_GET['data_final']);
		    }

		    if(!empty($data_inicial) OR !empty($data_final)){
		        list($di, $mi, $yi) = explode("/", $data_inicial);
		        if(!checkdate($mi,$di,$yi))
		            $msg = "Data inicial inválida";

		        list($df, $mf, $yf) = explode("/", $data_final);
		        if(!checkdate($mf,$df,$yf))
		            $msg = "Data final inválida";

		        if(strlen($msg)==0){
		            $aux_data_inicial = "$yi-$mi-$di";
		            $aux_data_final = "$yf-$mf-$df";

		            if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
		                $msg = "Data inicial maior do que a data final";
		            }

		            if (strtotime($aux_data_inicial. '+ 6 months' ) < strtotime($aux_data_final)){
					$msg = 'O intervalo entre as datas não pode ser maior que 6 meses.';
					}
		        }
		    }
		}
	}
	//Fim validação DAta Black
	if ($login_fabrica != 1){
		if (strlen($erro) == 0 && strlen($opcao1) > 0) {
			if (strlen($_POST["mes"]) > 0)  $mes = $_POST["mes"];
			if (strlen($_POST["ano"]) > 0)  $ano = $_POST["ano"];

			if (strlen($mes) == 0) $msg = " Selecione o mês para realizar a pesquisa. ";
			if (strlen($ano) == 0 AND strlen($msg)==0) $msg = " Selecione o ano para realizar a pesquisa. ";
			if (strlen($opcao2) == 0 AND strlen($opcao3) == 0 AND strlen($opcao4) == 0 AND strlen($msg) == 0)  $msg = " Informe mais Parâmetros para Pesquisar ";

		} else {
			$mes = "";
			$ano = "";
		}
	}

	if ($login_fabrica == 11 or $login_fabrica == 172){
		if (strlen($opcao8)>0 and ( strlen($opcao5) == 0 or strlen($opcao2) == 0 ) ) $msg = "Para efetuar a pesquisa por extrato deve ser preenchido o número da OS de Revenda e Posto";
	}

	if (strlen($opcao2) > 0 ) {

		if ($login_fabrica <> 11 and $login_fabrica <> 172 and $login_fabrica <> 1){
			if(strlen($_POST["numero_os"]) == 0){
				if (strlen($mes) == 0) $msg = " Selecione o mês para realizar a pesquisa1. ";
				if (strlen($ano) == 0 and strlen($msg)==0) $msg = " Selecione o ano para realizar a pesquisa. ";
			}

		}
		if($login_fabrica <> 1 or $login_fabrica == 11){

			if (!$opcao8){

				if (strlen($mes) == 0) $msg = " Selecione o mês para realizar a pesquisa2. ";
				if (strlen($ano) == 0 and strlen($msg)==0) $msg = " Selecione o ano para realizar a pesquisa. ";

			}

		}

		if (strlen($_POST["posto_codigo"]) > 0) $posto_codigo = "'".trim($_POST["posto_codigo"])."'";
		if (strlen($_POST["posto_nome"]) > 0)   $posto_nome = trim($_POST["posto_nome"]);

		if (strlen($posto_codigo) > 0 && strlen($posto_nome) > 0) {
			$sql =	"SELECT tbl_posto_fabrica.posto        ,
							tbl_posto_fabrica.codigo_posto ,
							tbl_posto.nome
					FROM tbl_posto
					JOIN tbl_posto_fabrica USING (posto)
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica
					AND   tbl_posto_fabrica.codigo_posto = $posto_codigo;";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) == 1) {
				$posto        = pg_result($res,0,posto);
				$posto_codigo = pg_result($res,0,codigo_posto);
				$posto_nome   = pg_result($res,0,nome);
			} else {
				$msg .= " Posto não encontrado. ";
			}
		}
	} else {
		$posto        = "";
		$posto_codigo = "";
		$posto_nome   = "";
	}

	if (strlen($opcao3) > 0) {

		if ($login_fabrica <> 11 and $login_fabrica <> 172){
			if ($login_fabrica <> 1){
				if (strlen($mes) == 0 ) $msg = " Selecione o mês para realizar a pesquisa. ";
				if (strlen($ano) == 0 and strlen($msg)==0) $msg = " Selecione o ano para realizar a pesquisa. ";
			}else{
				if((strlen($data_inicial)==0) or (strlen($data_inicial)==0 ) ) $msg = " Selecione o período para realizar a pesquisa";
			}

		}else{
			if (!$opcao8){
				if (strlen($mes) == 0) $msg = " Selecione o mês para realizar a pesquisa. ";
				if (strlen($ano) == 0 and strlen($msg)==0) $msg = " Selecione o ano para realizar a pesquisa. ";
			}
		}

		if (strlen($_POST["revenda_cnpj"]) > 0)  $revenda_cnpj = trim($_POST["revenda_cnpj"]);
		if (strlen($_POST["revenda_nome"]) > 0)  $revenda_nome = trim($_POST["revenda_nome"]);

		if (strlen($revenda_cnpj) > 0 && strlen($revenda_nome) > 0) {
			$sql =	"SELECT revenda , cnpj , nome
					FROM tbl_revenda
					WHERE cnpj = '$revenda_cnpj';";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) == 1) {
				$revenda      = pg_result($res,0,revenda);
				$revenda_cnpj = pg_result($res,0,cnpj);
				$revenda_nome = pg_result($res,0,nome);
			} else {
				$msg .= " Revenda não encontrada. ";
			}
		}
	} else {
		$revenda = "";
		$revenda_cnpj = "";
		$revenda_nome = "";
	}

	if (strlen($opcao4) > 0) {

		if ($login_fabrica <> 11 and $login_fabrica <> 172){
			if ($login_fabrica <> 1){

			    if (strlen($mes) == 0) $msg = " Selecione o mês para realizar a pesquisa. ";
				if (strlen($ano) == 0 and strlen($msg)==0) $msg = " Selecione o ano para realizar a pesquisa. ";
			}else{
				if((strlen($data_inicial)==0) or (strlen($data_inicial)==0 ) ) $msg = " Selecione o período para realizar a pesquisa";
			}
		}else{
			if (!$opcao8){
				if (strlen($mes) == 0) $msg = " Selecione o mês para realizar a pesquisa. ";
				if (strlen($ano) == 0 and strlen($msg)==0) $msg = " Selecione o ano para realizar a pesquisa. ";
			}
		}


		if (strlen($_POST["produto_referencia"]) > 0)  $produto_referencia = trim($_POST["produto_referencia"]);
		if (strlen($_POST["produto_descricao"]) > 0)   $produto_descricao  = trim($_POST["produto_descricao"]);
		if (strlen($_POST["produto_voltagem"]) > 0)    $produto_voltagem   = trim($_POST["produto_voltagem"]);

		if (strlen($produto_referencia) > 0 && strlen($produto_descricao) > 0) {
			$sql =	"SELECT tbl_produto.produto    ,
							tbl_produto.referencia ,
							tbl_produto.descricao  ,
							tbl_produto.voltagem
					FROM tbl_produto
					JOIN tbl_linha USING (linha)
					WHERE tbl_linha.fabrica    = $login_fabrica
					AND   tbl_produto.referencia = '$produto_referencia'";
			if ($login_fabrica == 1) $sql .= " AND tbl_produto.voltagem = '$produto_voltagem';";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) == 1) {
				$produto            = pg_result($res,0,produto);
				$produto_referencia = pg_result($res,0,referencia);
				$produto_descricao  = pg_result($res,0,descricao);
				$produto_voltagem   = pg_result($res,0,voltagem);
			} else {
				$msg .= " Produto não encontrado. ";
			}
		}
	} else {
		$produto = "";
		$produto_referencia = "";
		$produto_descricao  = "";
		$produto_voltagem   = "";
	}

	if (strlen($opcao5) > 0) {
		if (strlen($_POST["numero_os"]) > 0)  $numero_os = trim($_POST["numero_os"]);

		if (strlen($numero_os) > 0 && strlen($numero_os) < 3) $msg .= " Digite o número da OS com o mínimo de 3 números. ";

		if((strlen($numero_os)>0) and (strlen($posto_codigo)==0))$msg="Informe o número da OS e o Posto Autorizado para fazer a pesquisa";
	} else {
		$numero_os = "";
	}

	if (strlen($opcao6) > 0) {
		if (strlen($_POST["numero_serie"]) > 0)  $numero_serie = trim($_POST["numero_serie"]);

		if (strlen($numero_serie) > 0 && strlen($numero_serie) < 3) $msg .= " Digite o número de série com o mínimo de 3 números. ";
	} else {
		$numero_serie = "";
	}

	if ($login_fabrica == 11 or $login_fabrica == 172) {
		if ($opcao8){
			$extrato = $_POST['extrato'];
		}
	}
	if ($login_fabrica == 1) {
		if (strlen($opcao9) > 0) {
		if (strlen($_POST["nf_compra"]) > 0)  $nf_compra = trim($_POST["nf_compra"]);

		if (strlen($nf_compra) > 0 && strlen($nf_compra) < 3) $msg .= " Digite o número da Nota Fiscal com no mínimo de 3 números. ";

		if((strlen($nf_compra)>0) and (strlen($posto_codigo)==0))$msg="Informe o número da Nota Fiscal e o Posto Autorizado para fazer a pesquisa";
	} else {
		$nf_compra = "";
	}
	}
}

$layout_menu = "callcenter";
$title       = "RELAÇÃO DE ORDENS DE SERVIÇO DE REVENDA LANÇADAS";

include "cabecalho.php";
?>
<?php include "javascript_cabecalho_new.php"; ?>
<?php
    include_once '../js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist - HD 969678 */
?>

<script language="JavaScript">

$().ready(function(){
	$('#data_inicial').datepick({startDate:'01/01/2000'});
    $('#data_final').datepick({startDate:'01/01/2000'});
    $("#data_inicial").mask("99/99/9999");
    $("#data_final").mask("99/99/9999");

	$('input[name=extrato]').numeric();

	$("#img_help_extrato").click(function(){
		alert("Disponibiliza a opção de imprimir os produtos das OSs que entraram no extrato consultado");
	});

	$("#selecionar_todas").click(function() {
		if ($(this).find("input[type=checkbox]").is(":checked")) {
			$(this).find("input[type=checkbox]").removeAttr("checked");

			$("input[name^=exclui_os_revenda][type=checkbox]").removeAttr("checked");
		} else {
			$(this).find("input[type=checkbox]").attr("checked", "checked");

			$("input[name^=exclui_os_revenda][type=checkbox]").attr("checked", "checked");
		}
	});
});

function fnc_pesquisa_revenda (campo, tipo) {

	var url = "";

	if (tipo == "nome") {extrato
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome";
	}

	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj";
	}

	if (campo.value!="") {

		janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
		janela.nome			= document.frm_pesquisa.revenda_nome;
		janela.cnpj			= document.frm_pesquisa.revenda_cnpj;
		janela.fone			= document.frm_pesquisa.revenda_fone;
		janela.cidade		= document.frm_pesquisa.revenda_cidade;
		janela.estado		= document.frm_pesquisa.revenda_estado;
		janela.endereco		= document.frm_pesquisa.revenda_endereco;
		janela.numero		= document.frm_pesquisa.revenda_numero;
		janela.complemento	= document.frm_pesquisa.revenda_complemento;
		janela.bairro		= document.frm_pesquisa.revenda_bairro;
		janela.cep			= document.frm_pesquisa.revenda_cep;
		janela.email		= document.frm_pesquisa.revenda_email;
		janela.focus();

	} else{

		alert("Informe toda ou parte da informação para realizar a pesquisa!");

	}

}

</script>

<style type="text/css">

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
}

.msg_success{
	background-color:#008200;
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
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
</style>

<? include "javascript_pesquisas.php"; ?>

<?
if(strlen($msg_erro)>0){
	echo "<div class='msg_erro'>$msg_erro</div>";
}

if(strlen($msg_success)>0){
	echo "<br /><div class='msg_success'>$msg_success</div>";
}
?>


<br>

<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">

<table width="700" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
	<? if (strlen($msg) > 0) { ?>
		<tr class="msg_erro">
			<td colspan="6"><?echo $msg?></td>
		</tr>

	<? } ?>
	<tr class="titulo_tabela">
		<td colspan="6">Parâmetros de Pesquisa</td>
	</tr>
	<tr>
		<td colspan="6">&nbsp;</td>
	</tr>

	<!-- -->
	<!-- Alteração Chamado 1960096-->
	<? if($login_fabrica==1){ ?>
	<tr>
		<td width="50">&nbsp;</td>
		<td align="left"><input type="checkbox" name="opcao1" value="1" class="frm" <? if (strlen($opcao1) > 0) echo "checked"; ?>> Período </td>
		<td align="left">Data Inicial</td>
        <td align="left">&nbsp;Data Final</td>
        <td align="left">&nbsp;</td>
    </tr>

    <tr>
    	<td width="50">&nbsp;</td>
    	<td width="108">&nbsp;</td>
        <td>
            <input type="text" name="data_inicial" id="data_inicial" size="13" maxlength="10" value="<? echo (strlen($data_inicial) > 0) ? substr($data_inicial,0,10) : ""; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
        </td>
        <td>
            <input type="text" name="data_final" id="data_final" size="13" maxlength="10" value="<? if (strlen($data_final) > 0) echo substr($data_final,0,10); else echo ""; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
        </td>
    </tr>

	<? } ?>
	<!-- Fim da Alteração Chamado 1960096-->
	<!-- -->
	<? if($login_fabrica!=1){ ?>
	<tr>
		<td width="50">&nbsp;</td>
		<td align="left"><input type="checkbox" name="opcao1" value="1" class="frm" <? if (strlen($opcao1) > 0) echo "checked"; ?>> Período </td>
		<td>Mês</td>
		<td colspan="2">Ano</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>
			<select name="mes" size="1" class="frm">
			<option value=''></option><?php
			for ($i = 1 ; $i <= count($meses) ; $i++) {
				echo "<option value='$i'";
				if ($mes == $i) echo " selected";
				echo ">" . $meses[$i] . "</option>";
			}?>
			</select>
		</td>
		<td colspan="2">
			<select name="ano" size="1" class="frm">
			<option value=''></option><?php
			for ($i = 2003 ; $i <= date("Y") ; $i++) {
				echo "<option value='$i'";
				if ($ano == $i) echo " selected";
				echo ">$i</option>";
			}?>
			</select>
		</td>
		<td>&nbsp;</td>
	</tr>
	<? } ?>
	<!-- -->
	<tr>
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td align="left"><input type="checkbox" name="opcao2" value="2" class="frm" <? if (strlen($opcao2) > 0) echo "checked"; ?>> Posto</td>
		<td>Código do Posto</td>
		<td colspan="2">Razão Social</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>
			<input type="text" name="posto_codigo" size="12" value="<?echo $posto_codigo?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.posto_codigo, document.frm_pesquisa.posto_nome, 'codigo')">
		</td>
		<td colspan="2">
			<input type="text" name="posto_nome" size="30" value="<?echo $posto_nome?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.posto_codigo, document.frm_pesquisa.posto_nome, 'nome')">
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td align="left"><input type="checkbox" name="opcao3" value="3" class="frm" <? if (strlen($opcao3) > 0) echo "checked"; ?>> Revenda</td>
		<td>CNPJ da Revenda</td>
		<td colspan="2">Nome da Revenda</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>
			<input type="text" name="revenda_cnpj" size="12" value="<?echo $revenda_cnpj?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar revendas pelo código" onclick="javascript: fnc_pesquisa_revenda (document.frm_pesquisa.revenda_cnpj, 'cnpj');">
		</td>
		<td colspan="2">
			<input type="text" name="revenda_nome" size="40" value="<?echo $revenda_nome?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar pelo nome da revenda." onclick="javascript: fnc_pesquisa_revenda (document.frm_pesquisa.revenda_nome, 'nome');">
		</td>
		<td>
			&nbsp;
			<input type='hidden' name = 'revenda_fone'>
			<input type='hidden' name = 'revenda_cidade'>
			<input type='hidden' name = 'revenda_estado'>
			<input type='hidden' name = 'revenda_endereco'>
			<input type='hidden' name = 'revenda_numero'>
			<input type='hidden' name = 'revenda_complemento'>
			<input type='hidden' name = 'revenda_bairro'>
			<input type='hidden' name = 'revenda_cep'>
			<input type='hidden' name = 'revenda_email'>
		</td>
	</tr>
	<tr>
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td align="left"><input type="checkbox" name="opcao4" value="4" class="frm" <? if (strlen($opcao4) > 0) echo "checked"; ?>> Produto</td>
		<td>Referência</td>
		<td>Descrição</td>
		<td>Voltagem</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td><input type="text" name="produto_referencia" size="12" value="<?echo $produto_referencia?>" class="frm"> <img src="imagens/lupa.png"  style="cursor: pointer;" align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_descricao, 'referencia', document.frm_pesquisa.produto_voltagem)"></td>
		<td><input type="text" name="produto_descricao" size="40" value="<?echo $produto_descricao?>" class="frm"> <img src="imagens/lupa.png"   style="cursor: pointer;" align='absmiddle' alt="Clique aqui para pesquisas pela referência do aparelho." onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_descricao, 'descricao', document.frm_pesquisa.produto_voltagem)"></td>
		<td><input type='text' name='produto_voltagem' size='5' value="<?echo $produto_voltagem?>" class="frm"></td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="6"><hr color="#EEEEEE"></td>
	</tr>
	<tr>
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td align="left" colspan="2"><input type="checkbox" name="opcao5" value="5" class="frm" <? if (strlen($opcao5) > 0) echo "checked"; ?>> Número da OS Revenda</td>
		<td colspan="2"><input type="text" name="numero_os" size="15" value="<?echo $numero_os?>" class="frm"></td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td align="left" colspan="2"><input type="checkbox" name="opcao6" value="6" class="frm" <? if (strlen($opcao6) > 0) echo "checked"; ?>> Número Série</td>
		<td colspan="2"><input type="text" name="numero_serie" size="15" value="<?echo $numero_serie?>" class="frm"></td>
		<td>&nbsp;</td>
	</tr>
	<? if($login_fabrica==19){ ?>
	<tr>
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td align="left" colspan="4">
			<input type="checkbox" name="opcao7" value="7" class="frm" <? if (strlen($opcao7) > 0) echo "checked"; ?>> Somente OS não Efetivadas</td>
		<td>&nbsp;</td>
	</tr>
<? } ?>
	<tr>
		<td colspan="6">&nbsp;</td>
	</tr>
	<?php if ($login_fabrica == 11 or $login_fabrica == 172) {?>

	<tr>
		<td>&nbsp;</td>

		<td align="left" colspan="2">
			<input type="checkbox" name="opcao8" value="8" class="frm" <? if (strlen($opcao8) > 0) echo "checked"; ?> > Extrato
		</td>

		<td colspan="2">
			<input type="text" name="extrato" id="extrato" size="15" value="<?echo $extrato?>" class="frm">
			<img src="../imagens/help.png" title="Disponibiliza a opção de imprimir os produtos das OSs que entraram no extrato consultado" id="img_help_extrato" style="cursor:pointer" >
		</td>

		<td>&nbsp;</td>
	</tr>

	<tr>
		<td colspan="6">&nbsp;</td>
	</tr>
	<?php } ?>

	<!-- Alteração Chamado 1960096-->
	<? if($login_fabrica==1){ ?>

	<tr>
		<td>&nbsp;</td>

		<td align="left" colspan="2">
			<input type="checkbox" name="opcao9" value="9" class="frm" <? if (strlen($opcao9) > 0) echo "checked"; ?> > NF Compra
		</td>

		<td colspan="2">
			<input type="text" name="nf_compra" id="nf_compra" size="15" value="<?echo $nf_compra?>" class="frm"></td>

		<td>&nbsp;</td>
	</tr>

	<tr>
		<td colspan="6">&nbsp;</td>
	</tr>
	<? } ?>
	<!-- Fim da Alteração Chamado 1960096-->


	<tr>
		<td colspan="6" align="center">
			<input type="button" style="background:url(imagens_admin/btn_pesquisar_400.gif); width:400px; cursor:pointer;" value="&nbsp;" onclick="document.frm_pesquisa.acao.value='PESQUISAR'; document.frm_pesquisa.submit();" alt="Preencha as opções e clique aqui para pesquisar">

		</td>
	</tr>
</table>

</form>

<br>

<?
if (strlen($acao) > 0 && strlen($msg) == 0) {
	if (strlen($mes) > 0 && strlen($ano) > 0) {
		$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
	}



	if ($login_fabrica == 1) {
        $sql =	"SELECT DISTINCT
						A.os_revenda ,
						A.abertura                ,
						A.sua_os              ,
						SUBSTRING(A.sua_os,1,5) as sub_sua_os ,
						A.revenda_nome        ,
						A.revenda_cnpj        ,
						A.explodida           ,
						A.cortesia            ,
						A.consumidor_revenda  ,
						A.data_fechamento     ,
						A.motivo_atraso       ,
						A.impressa            ,
						A.extrato             ,
						A.excluida            ,
						A.posto               ,
						A.codigo_posto        ,
						A.qtde_item         ,
                        A.produto_referencia,
                        A.produto_descricao,
                        A.posto_nome

				FROM (
				(
					SELECT  DISTINCT
							tbl_os_revenda.os_revenda                                                ,
							tbl_os_revenda.sua_os                                                    ,
							tbl_os_revenda.explodida                                                 ,
							tbl_os_revenda.cortesia                                                  ,
							TO_CHAR(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS abertura           ,
							tbl_os_revenda.digitacao                           AS digitacao          ,
							tbl_revenda.nome                                   AS revenda_nome       ,
							tbl_revenda.cnpj                                   AS revenda_cnpj       ,
							NULL                                               AS consumidor_revenda ,
							current_date                                       AS data_fechamento    ,
							TRUE                                               AS excluida           ,
							NULL                                               AS motivo_atraso      ,
							tbl_os_revenda_item.serie                                                ,
							tbl_os_revenda_item.produto                                              ,
							tbl_os_revenda.posto                                                     ,
                            tbl_posto_fabrica.codigo_posto                                           ,
							tbl_posto.nome                                      AS posto_nome       ,
							tbl_produto.referencia                             AS produto_referencia ,
							tbl_produto.descricao                              AS produto_descricao  ,
							current_date                                       AS impressa           ,
							0                                                  AS extrato            ,
							0                                                  AS qtde_item
					FROM      tbl_os_revenda
					JOIN      tbl_os_revenda_item ON  tbl_os_revenda_item.os_revenda = tbl_os_revenda.os_revenda
					JOIN      tbl_produto         ON  tbl_produto.produto            = tbl_os_revenda_item.produto AND tbl_produto.fabrica_i=$login_fabrica
					LEFT JOIN tbl_revenda         ON  tbl_revenda.revenda            = tbl_os_revenda.revenda
					JOIN tbl_posto                ON  tbl_posto.posto                = tbl_os_revenda.posto
					JOIN tbl_posto_fabrica        ON  tbl_posto_fabrica.posto        = tbl_posto.posto
												  AND tbl_posto_fabrica.fabrica      = $login_fabrica
					WHERE tbl_os_revenda.fabrica = $login_fabrica
					AND   tbl_os_revenda.os_manutencao IS FALSE
					AND   tbl_os_revenda.os_geo        IS FALSE
					and   tbl_os_revenda.excluida      IS FALSE ";

		if (strlen($data_inicial) > 0 && strlen($data_final) > 0) $sql .= " AND tbl_os_revenda.digitacao BETWEEN '$aux_data_inicial' AND '$aux_data_final'";

		if (strlen($revenda) > 0) {
			if (strlen($revenda_cnpj) > 0) $sql .= " AND tbl_revenda.cnpj = '$revenda_cnpj'";
			if (strlen($revenda_nome) > 0) $sql .= " AND tbl_revenda.nome = '$revenda_nome'";
		}

		if (strlen($produto) > 0) {
			$sql .= " AND tbl_os_revenda_item.produto = $produto";
		}

		if (strlen($posto) > 0) {
			$sql .= " AND tbl_os_revenda.posto = $posto";
		}

		if (strlen($numero_os) > 0) {

			# HD 51628
			if (strlen($posto) == 0 and strlen($numero_os) > 9) {
				$cod_posto = substr($numero_os,0,5);
				$sqlP = "SELECT posto FROM tbl_posto_fabrica
						WHERE fabrica = $login_fabrica
						AND codigo_posto = '$cod_posto'";
				$resP = pg_exec($con,$sqlP);

				if (pg_numrows($resP) > 0){
					$aux_posto = pg_result($resP,0,0);
				}

				$sql .= " AND tbl_os_revenda.posto = $aux_posto";
			}

			$pos = strpos($numero_os, "-");

			if ($pos === false) {
				$pos = strlen($numero_os) - 5;
				if (strlen($numero_os) > 5) $numero_os = substr($numero_os, $pos, strlen($numero_os));
			} else {
				$pos = $pos - 5;
				if (strlen($numero_os) > 7) $numero_os = substr($numero_os, $pos, strlen($numero_os));
			}
			$numero_os = strtoupper($numero_os);


			$sql .= " AND tbl_os_revenda.sua_os LIKE '%$numero_os'";
		}
		//Validação Nota Fiscal Black
		if (strlen($nf_compra) > 0 and strlen($posto) > 0) {

			$nf_compra = (int)$nf_compra;

			$sql .= " AND tbl_os_revenda.nota_fiscal = '$nf_compra'";

		}
		//Fim validação Nota Fiscal Black

		$numero_serie = strtoupper($numero_serie);
		if (strlen($numero_serie) > 0) $sql .= " AND tbl_os_revenda_item.serie LIKE '%$numero_serie%'";

				$sql .= " ) UNION (
					SELECT  tbl_os.os                                  AS os_revenda         ,
							tbl_os.sua_os                                                    ,
							NULL                                       AS explodida          ,
							tbl_os.cortesia                                                  ,
							TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS abertura           ,
							tbl_os.data_digitacao                      AS digitacao          ,
							tbl_os.revenda_nome                                              ,
							tbl_os.revenda_cnpj                                              ,
							tbl_os.consumidor_revenda                                        ,
							tbl_os.data_fechamento                                           ,
							tbl_os.excluida                                                  ,
							tbl_os.motivo_atraso                                             ,
							tbl_os.serie                                                     ,
							tbl_os.produto                                                   ,
							tbl_os.posto                                                     ,
							tbl_posto_fabrica.codigo_posto                                   ,
							tbl_posto.nome                             AS posto_nome            ,
							tbl_produto.referencia                     AS produto_referencia ,
							tbl_produto.descricao                      AS produto_descricao  ,
							tbl_os_extra.impressa                                            ,
							tbl_os_extra.extrato                                             ,
							(
								SELECT COUNT(tbl_os_item.*) AS qtde_item
								FROM   tbl_os_item
								JOIN   tbl_os_produto USING (os_produto)
								WHERE  tbl_os_produto.os = tbl_os.os
							)                                          AS qtde_item
					FROM tbl_os
					JOIN tbl_os_extra       ON  tbl_os_extra.os           = tbl_os.os
					JOIN tbl_produto        ON  tbl_produto.produto       = tbl_os.produto AND tbl_produto.fabrica_i=$login_fabrica
					JOIN tbl_posto          ON  tbl_posto.posto           = tbl_os.posto
					JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto
											AND tbl_posto_fabrica.fabrica = $login_fabrica
					WHERE tbl_os.fabrica = $login_fabrica
					AND   (tbl_os.tipo_os is null or tbl_os.tipo_os <> 13)
					AND   tbl_os.consumidor_revenda = 'R'
					AND   tbl_os.excluida IS FALSE";

		if (strlen($data_inicial) > 0 && strlen($data_final) > 0) $sql .= " AND tbl_os.data_digitacao BETWEEN '$aux_data_inicial' AND '$aux_data_final'";

		if (strlen($revenda) > 0) {
			if (strlen($revenda_cnpj) > 0) $sql .= " AND tbl_os.revenda_cnpj = '$revenda_cnpj'";
			if (strlen($revenda_nome) > 0) $sql .= " AND tbl_os.revenda_nome = '$revenda_nome'";
		}

		if (strlen($produto) > 0) {
			$sql .= " AND tbl_os.produto = $produto";
		}

		if (strlen($posto) > 0) {
			$sql .= " AND tbl_os.posto = $posto";
		}

		//Validação Nota Fiscal Black
		if (strlen($nf_compra) > 0 and strlen($posto) > 0) {

			$nf_compra = (int)$nf_compra;

			$sql .= " AND tbl_os.nota_fiscal LIKE '%$nf_compra%'";

		}
		//Fim validação Nota Fiscal Black


		if (strlen($numero_os) > 0) {

			# HD 51628
			if (strlen($aux_posto) > 0 and strlen($posto) == 0) {
				$sql .= " AND tbl_os.posto = $aux_posto";
			}

			$pos = strpos($numero_os, "-");

			if ($pos === false) {
				$pos = strlen($numero_os) - 5;
				if (strlen($numero_os) > 5) $numero_os = substr($numero_os, $pos, strlen($numero_os));
			} else {
				$pos = $pos - 5;
				if (strlen($numero_os) > 7) $numero_os = substr($numero_os, $pos, strlen($numero_os));
			}
			$numero_os = strtoupper($numero_os);
			$sql .= " AND tbl_os.sua_os LIKE '%$numero_os'";
		}
		$numero_serie = strtoupper($numero_serie);
		if (strlen($numero_serie) > 0) $sql .= " AND tbl_os.serie LIKE '%$numero_serie%'";



				$sql .= " )
			) AS A
			WHERE (1=1 ) ORDER BY SUBSTRING(A.sua_os,1,5) ASC, A.os_revenda ASC ;";

/*TAKASHI ALTEROU O SQL 16-05-07*/
	} else {
		$sql =	"SELECT DISTINCT
						tbl_os_revenda.os_revenda                                          ,
						tbl_os_revenda.sua_os                                              ,
						tbl_os_revenda.explodida                                           ,
						TO_CHAR(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS abertura     ,
						tbl_os_revenda.revenda                                             ,
						tbl_os_revenda.posto                                               ,
						tbl_revenda.cnpj                                   AS revenda_cnpj ,
						tbl_revenda.nome                                   AS revenda_nome ,
						tbl_posto_fabrica.codigo_posto 						AS codigo_posto,
                        tbl_posto.nome as nome_posto
			FROM		tbl_os_revenda
			LEFT JOIN	tbl_os_revenda_item ON  tbl_os_revenda_item.os_revenda = tbl_os_revenda.os_revenda
			LEFT JOIN	tbl_produto         ON  tbl_produto.produto            = tbl_os_revenda_item.produto
			LEFT JOIN	tbl_revenda         ON  tbl_revenda.revenda            = tbl_os_revenda.revenda
			JOIN tbl_posto on tbl_posto.posto = tbl_os_revenda.posto
			JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto.posto and tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_os_revenda.fabrica = $login_fabrica
			AND   tbl_os_revenda.os_manutencao IS FALSE ";

		if (strlen($data_inicial) > 0 && strlen($data_final) > 0) $sql .= " AND tbl_os_revenda.digitacao BETWEEN '$data_inicial' AND '$data_final'";

		if (strlen($posto) > 0) $sql .= " AND tbl_os_revenda.posto = $posto";

		if (strlen($revenda) > 0) $sql .= " AND tbl_os_revenda.revenda = $revenda";

		if (strlen($produto) > 0) $sql .= " AND tbl_os_revenda_item.produto = $produto";

		$numero_os = strtoupper($numero_os);
		if (strlen($numero_os) > 0) $sql .= " AND tbl_os_revenda.sua_os LIKE '%$numero_os%'";

		$numero_serie = strtoupper($numero_serie);
		if (strlen($numero_serie) > 0) $sql .= " AND tbl_os_revenda_item.serie LIKE '%$numero_serie%'";

		if(strlen($opcao7)>0 AND $login_fabrica==19) $sql .= " AND tbl_os_revenda.explodida is null ";

		$sql .= " ORDER BY tbl_os_revenda.os_revenda DESC;";
	}

	$res = pg_exec($con,$sql);
//if (getenv("REMOTE_ADDR") == "200.228.76.93") echo nl2br($sql);
// echo nl2br($sql);
if (pg_numrows($res) > 0) {
		$total_registro = pg_numrows($res);
		if ($login_fabrica == 1) {
			echo "<table width='700' border='0' cellspacing='2' cellpadding='0' align='center'>";
			echo "<tr>";
			echo "<td align='center' width='10' bgcolor='#FFE1E1'>&nbsp;</td>";
			echo "<td align='left'><font size='1'>&nbsp; Excluídas do sistema</font></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td align='center' width='10' bgcolor='#91C8FF'>&nbsp;</td>";
			echo "<td align='left'><font size='1'>&nbsp; OSs sem fechamento há mais de 20 dias, informar \"Motivo\"</font></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td align='center' width='10' bgcolor='#FFCC66'>&nbsp;</td>";
			echo "<td align='left'><font size='1'>&nbsp; OSs sem lancamento de itens há mais de 5 dias, efetue o lançamento</font></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td align='center' width='10' bgcolor='#FF0000'>&nbsp;</td>";
			echo "<td align='left'><font size='1'>&nbsp; OSs que excederam o prazo limite de 30 dias para fechamento, informar \"Motivo\"</font></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td align='center' width='10' bgcolor='#99FF66'>&nbsp;</td>";
			echo "<td align='left'><font size='1'>&nbsp; OSs de Troca</font></td>";
			echo "</tr>";
			echo "</table>";
			echo "<br>";
		}

		if (in_array($login_fabrica, array(1))) {
			$mostra_checkbox = false;

			if (!empty($data_inicial) && !empty($data_final) && !empty($posto)) {
				$mostra_checkbox = true;
			}
		}

		if (in_array($login_fabrica, array(1)) && $mostra_checkbox === true) {
			echo "<form method='post' >";
		}

		echo "<table border='0' cellpadding='2' align='center' cellspacing='1' class='tabela'>";



		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			if ($i == 0) {
				echo "<tr class='titulo_coluna' height='15'>";
				if (in_array($login_fabrica, array(1)) && $mostra_checkbox === true) {
					echo "<td><span id='selecionar_todas' style='cursor: pointer;'>Todas <input type='checkbox' disabled='disabled' /></span></td>";
				}
				echo "<td>OS</td>";
				echo "<td>Data</td>";
				echo "<td>Revenda</td>";
				if ($login_fabrica == 1) {
                    echo "<td>Posto</td>";
                    echo "<td>Produto</td>";
					echo "<td>Item</td>";
					echo "<td><img border='0' src='imagens/img_impressora.gif' alt='OS que já foi impressa'></td>";
					$colspan = "5";
				}elseif($login_fabrica==19){
					echo "<td>Código Posto</td>";
					echo "<td>Nome Posto</td>";
					$colspan = "5";
				}else{
					$colspan = "3";
				}
				echo "<td colspan='100%'>Ações</td>";

				if (($login_fabrica == 11 or $login_fabrica == 172) and $extrato){
					echo "<td colspan='$colspan'>OS do Extrato</td>";
				}
				echo "</tr>";
			}

			$os_revenda   = trim(pg_result($res,$i,os_revenda));
			$sua_os       = trim(pg_result($res,$i,sua_os));
			$explodida    = trim(pg_result($res,$i,explodida));
			$abertura     = trim(pg_result($res,$i,abertura));
			$revenda_cnpj = trim(pg_result($res,$i,revenda_cnpj));
			$revenda_nome = trim(pg_result($res,$i,revenda_nome));

			$xxposto        = trim(pg_result($res,$i,posto));/*TAKASHI ALTEROU O SQL 16/05/07*/

			$sql_explodida = "SELECT os_revenda
								FROM tbl_os_revenda_item
								JOIN tbl_os ON tbl_os_revenda_item.os_lote = tbl_os.os
								WHERE
								tbl_os_revenda_item.os_revenda = $os_revenda
								AND tbl_os.fabrica = $login_fabrica
								AND tbl_os.excluida IS NOT TRUE
								LIMIT 1
								";
			$res_sql_ex		  = pg_query($con,$sql_explodida);
			if(pg_numrows($res_sql_ex) > 0)
				$explodida_filhas = 1;
			else
				$explodida_filhas = 0;

			if ($login_fabrica == 1) {
				$consumidor_revenda = trim(pg_result($res,$i,consumidor_revenda));
				$data_fechamento    = trim(pg_result($res,$i,data_fechamento));
				$motivo_atraso      = trim(pg_result($res,$i,motivo_atraso));
				$impressa           = trim(pg_result($res,$i,impressa));
				$extrato            = trim(pg_result($res,$i,extrato));
				$excluida           = trim(pg_result($res,$i,excluida));
				$qtde_item          = trim(pg_result($res,$i,qtde_item));
                $posto_codigo       = trim(pg_result($res,$i,codigo_posto));
                $nome_posto         = trim(pg_result($res,$i,posto_nome));
                $produto_referencia       = trim(pg_result($res,$i,produto_referencia));
				$produto_descricao       = trim(pg_result($res,$i,produto_descricao));
				$cortesia   = trim(pg_result($res,$i,'cortesia'));

				if (strlen($consumidor_revenda) > 0) {
					if ($excluida == "t") $cor = "#FFE1E1";

					// verifica se nao possui itens com 5 dias de lancamento...
					$aux_data_abertura = fnc_formata_data_pg($abertura);

					$sqlX = "SELECT to_char (current_date + INTERVAL '5 days', 'YYYY-MM-DD')";
					$resX = pg_exec($con,$sqlX);
					$data_hj_mais_5 = pg_result($resX,0,0);

					$sqlX = "SELECT to_char ($aux_data_abertura::date + INTERVAL '5 days', 'YYYY-MM-DD')";
					$resX = pg_exec($con,$sqlX);
					$data_consultar = pg_result($resX,0,0);

					$sql = "SELECT COUNT(tbl_os_item.*) as total_item
							FROM tbl_os_item
							JOIN tbl_os_produto on tbl_os_produto.os_produto = tbl_os_item.os_produto
							JOIN tbl_os on tbl_os.os = tbl_os_produto.os
							WHERE tbl_os.os = $os_revenda
							AND tbl_os.data_abertura::date >= '$data_consultar'";
					$resItem = pg_exec($con,$sql);

					$itens = pg_result($resItem,0,total_item);

					if ($itens == 0 and $data_consultar > $data_hj_mais_5) $cor = "#FFCC66";

					$mostra_motivo = 2;

					// verifica se está sem fechamento ha 20 dias ou mais da data de abertura...
					if (strlen($data_fechamento) == 0 AND $mostra_motivo == 2 AND $login_fabrica == 1) {
						$aux_data_abertura = fnc_formata_data_pg($abertura);

						$sqlX = "SELECT to_char ($aux_data_abertura::date + INTERVAL '20 days', 'YYYY-MM-DD')";
						$resX = pg_exec($con,$sqlX);
						$data_consultar = pg_result($resX,0,0);

						$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
						$resX = pg_exec($con,$sqlX);
						$data_atual = pg_result ($resX,0,0);

						if ($data_consultar < $data_atual AND strlen($data_fechamento) == 0) {
							$mostra_motivo = 1;
							$cor = "#91C8FF";
						}
					}

					// Se estiver acima dos 30 dias, nao exibira os botoes...
					if (strlen($data_fechamento) == 0 AND $login_fabrica == 1) {
						$aux_data_abertura = fnc_formata_data_pg($abertura);

						$sqlX = "SELECT to_char ($aux_data_abertura::date + INTERVAL '30 days', 'YYYY-MM-DD')";
						$resX = pg_exec($con,$sqlX);
						$data_consultar = pg_result($resX,0,0);

						$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
						$resX = pg_exec($con,$sqlX);
						$data_atual = pg_result($resX,0,0);

						if ($data_consultar < $data_atual AND strlen($data_fechamento) == 0) {
							$mostra_motivo = 1;
							$cor = "#ff0000";
						}
					}

				}
			} else {

				$nome_posto          = trim(pg_result($res,$i,nome_posto));
				$codigo_posto          = trim(pg_result($res,$i,codigo_posto));

			}

			if ($i % 2 == 0) {
				$cor   = "#F1F4FA";
				$botao = "azul";
			} else {
				$cor   = "#F7F5F0";
				$botao = "amarelo";
			}

			$sua_os = strtoupper($sua_os);
			/*$sql =	"SELECT *
					FROM tbl_os
					WHERE sua_os LIKE '$sua_os-%' ";*/
/*TAKASHI ALTEROU O SQL 16/05/07*/
			$sql =	"SELECT tbl_os.os,
							tbl_os.sua_os
					FROM tbl_os
					WHERE fabrica = $login_fabrica
					AND posto = $xxposto
					AND (sua_os = '$sua_os' OR ";
					for ($x=1;$x<=40;$x++) {
						$sql .= " tbl_os.sua_os = '$sua_os-$x' OR ";
					}
			$sql .= "1=2 )";
			$resX = pg_exec($con, $sql);
			/*TAKASHI ALTEROU O SQL 16/05/07*/
			if ($login_fabrica == 1) {
				$sua_os = $sua_os;
			}
			echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
			if (in_array($login_fabrica, array(1)) && $mostra_checkbox === true) {

				if (!empty($consumidor_revenda)) {
					$query = "
						SELECT tbl_os.os
						FROM tbl_os
						INNER JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
						WHERE tbl_os.fabrica = {$login_fabrica}
						AND tbl_os.os = {$os_revenda}
						AND tbl_defeito_constatado.defeito_constatado = 11
						AND tbl_os.finalizada IS NULL
					";
					$result = pg_query($con, $query);

					$ajuste_sem_troca = (pg_num_rows($result) > 0) ? true : false;

					$query = "
						SELECT tbl_os.os
						FROM tbl_os
						INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
						INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
						INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
						WHERE tbl_os.fabrica = {$login_fabrica}
						AND tbl_os.os = {$os_revenda}
						AND tbl_os.finalizada IS NULL
						AND tbl_servico_realizado.gera_pedido IS TRUE
					";
					$result = pg_query($con, $query);

					$reparo_sem_pedido = (!pg_num_rows($result)) ? true : false;

					$query = "
						SELECT tbl_os_troca.os
						FROM tbl_os_troca
						WHERE tbl_os_troca.os = {$os_revenda}
						AND tbl_os_troca.pedido IS NULL
					";
					$result = pg_query($con, $query);

					$troca_sem_pedido = (pg_num_rows($result) > 0) ? true : false;
				} else if (!empty($explodida)) {
					$query = "
						SELECT tbl_os_revenda.os_revenda
						FROM tbl_os_revenda
						INNER JOIN tbl_os_revenda_item ON tbl_os_revenda_item.os_revenda = tbl_os_revenda.os_revenda
						INNER JOIN tbl_os ON tbl_os.os = tbl_os_revenda_item.os_lote
						LEFT JOIN tbl_os_excluida ON tbl_os_excluida.os = tbl_os.os
						WHERE tbl_os_revenda.fabrica = {$login_fabrica}
						AND tbl_os_revenda.os_revenda = {$os_revenda}
						AND tbl_os_excluida.os_excluida IS NULL
					";
					$result = pg_query($con, $query);

					$matriz_sem_os = (!pg_num_rows($result)) ? true : false;
				}

				if ($verifica_ajuste_sem_troca === true || $reparo_sem_pedido === true || $troca_sem_pedido === true || $matriz_sem_os === true) {
					if (!empty($consumidor_revenda)) {
						$os_matriz = 'f';
					} else {
						$os_matriz = 't';
					}

					$checked = (!empty($_POST["exclui_os_revenda"][$i])) ? "checked" : "";

					echo "<td>
						<input type='checkbox' name='exclui_os_revenda[{$i}]' value='{$os_revenda}' {$checked} />
						<input type='hidden' name='os_matriz[{$i}]' value='{$os_matriz}' />
					</td>";
				} else {
					echo "<td>&nbsp;</td>";
				}
			}

			echo "<td nowrap>";
			if ($login_fabrica == 1) echo $posto_codigo;
			echo "<a href='os_revenda_explodida.php?sua_os=$sua_os&posto=$xxposto' target='_blank'>$sua_os</a></td>";
			echo "<td nowrap>" . $abertura . "</td>";
			echo "<td nowrap><acronym title='CNPJ: $revenda_cnpj\nRAZÃO SOCIAL: $revenda_nome' style='cursor: help;'>" . substr($revenda_nome,0,20) . "</acronym></td>";
            if($login_fabrica <> 19){
	            echo "<td nowrap><acronym title='Posto: $posto_codigo - $nome_posto' style='cursor:help;'>" .substr($nome_posto,0,20)."</acronym></td>";
            }
            if ($login_fabrica == 1) echo "<td nowrap><acronym title='Produto: $produto_referencia - $produto_descricao' style='cursor:help;'>" .substr($produto_referencia ." - ". $produto_descricao,0,20)."</acronym></td>";
			if ($login_fabrica != 1) {

				if($login_fabrica == 19){
				echo "<td>$codigo_posto</td>";
				echo "<td>$nome_posto</td>";
				}
				echo "<td width='80' align='center'>";
				if (pg_numrows($resX) == 0 || strlen($explodida) == 0) echo "<a href='os_revenda.php?os_revenda=$os_revenda' target='_blank'><img border='0' src='imagens/btn_alterar_".$botao.".gif'></a>";
				else                                                   echo "&nbsp;";
				echo "</td>";
				echo "<td width='80' align='center'>";
				if (pg_numrows($resX) == 0 || strlen($explodida) == 0){
					echo "<a href='os_revenda_finalizada.php?os_revenda=$os_revenda&btn_acao=explodir' target='_blank'><img border='0' src='imagens/btn_explodir";
					if($login_fabrica==19){echo "_2";}
					echo ".gif'></a>";
				} else {
					echo "&nbsp;";
				}
				echo "</td>";

				echo "<td width='80' align='center'>";

				echo "<a href='os_revenda_print.php?os_revenda=$os_revenda' target='_blank'><img border='0' src='imagens/btn_imprimir_".$botao.".gif'></a>";
				echo "</td>";

				if (($login_fabrica == 11 or $login_fabrica == 172) and $opcao8) {

					echo "<td width='80' align='center'>";
					echo "<a href='os_revenda_print.php?os_revenda=$os_revenda&e=$extrato' target='_blank'><img border='0' src='imagens/btn_imprimir_".$botao.".gif'></a>";
					echo "</td>";
				}

			} else {
				if (strlen($consumidor_revenda) == 0) {
                    echo "<td nowrap>&nbsp</td>";
					echo "<td nowrap>&nbsp</td>";
					// verifica se existem OS geradas pela OS Revenda
					$sua_os = strtoupper($sua_os);

					/*$sql = "SELECT *
							FROM   tbl_os
							WHERE  sua_os LIKE '$sua_os-%'";*/

		/*TAKASHI ALTEROU O SQL 16/05/07*/
					$sql =	"SELECT tbl_os.os,
									tbl_os.sua_os
							FROM tbl_os
							WHERE fabrica = $login_fabrica
							AND posto = $xxposto
							AND (sua_os = '$sua_os' OR ";
							for ($x=1;$x<=40;$x++) {
								$sql .= " tbl_os.sua_os = '$sua_os-$x' OR ";
							}
					$sql .= "1=2 )";
					$resX = pg_exec($con, $sql);
		/*TAKASHI ALTEROU O SQL 16/05/07*/
					$resX = pg_exec($con,$sql);


					echo "<td width='80' align='center'>";
					if (pg_numrows($resX) == 0 && strlen($explodida) == 0) echo "<a href='os_revenda.php?os_revenda=$os_revenda' target='_blank'><img src='imagens/btn_alterar_".$botao.".gif'></a>";
					else                                                   echo "&nbsp;";
					echo "</td>\n";

					echo "<td width='80' align='center'>";
					if (strlen($explodida) == 0) echo "<a href='os_revenda_finalizada.php?os_revenda=$os_revenda&btn_acao=explodir' target='_blank'><img src='imagens/btn_explodir.gif'></a>";
					else                                                   echo "&nbsp;";
					echo "</td>\n";

					echo "<td width='80' align='center'><a href='os_revenda_print.php?os_revenda=$os_revenda' target='_target'><img src='imagens/btn_imprimir_" . $botao . ".gif' alt='Imprimir Revenda'></a></td>\n";

					echo "<td width='80' align='center'><a href='os_revenda_finalizada.php?os_revenda=$os_revenda' target='_target'><img src='imagens/btn_consultar_" . $botao . ".gif' alt='Consultar Black & Decker'></a></td>\n";
				} else {

					echo "<td width='30' align='center'>";
					if ($qtde_item > 0) echo"<img border='0' src='imagens/img_ok.gif' alt='OS com item'>";
					else                echo"&nbsp;";
					echo "</td>\n";

					echo "<td width='30' align='center'>";
					if (strlen($impressa) > 0) echo"<ilogin_fabricamg border='0' src='imagens/img_ok.gif' alt='OS que já foi impressa'>";
					else                       echo"<img border='0' src='imagens/img_impressora.gif' alt='Imprimir OS'>";
					echo "</td>\n";

					echo "<td width='80' align='center'>";
					if ($excluida == "f" || strlen($excluida) == 0) echo "<a href='os_press.php?os=$os_revenda' target='_blank'><img src='imagens/btn_consulta.gif'></a>";
					else                                            echo "&nbsp;";
					echo "</td>\n";

					echo "<td width='80' align='center'>";
					if (($excluida == "f" || strlen($excluida) == 0) && strlen($data_fechamento) == 0) echo "<a href='os_cadastro.php?os=$os_revenda' target='_blank'><img src='imagens/btn_alterar_cinza.gif' target='_blank'></a>";
					else                                                                               echo "&nbsp;";
					echo "</td>\n";

					echo "<td width='80' align='center'>";
					if ($excluida == "f" || strlen($excluida) == 0) echo "<a href='os_print.php?os=$os_revenda' target='_blank'><img src='imagens/btn_imprime.gif'></a>";
					else                                            echo "&nbsp;";
					echo "</td>";

					echo "<td width='80' align='center'>";
					$link_os = "os_item.php";
					if($login_fabrica == 1) {
						if ($cortesia =='t')								$link_os = "os_cortesia_item.php";
// 						elseif (!empty($tipo_atendimento)) $link_os = "os_cadastro_troca_black.php";
					if ($mostra_motivo == 1) {
						if ($excluida == "f" || strlen($excluida) == 0) {
							echo "<a href='$link_os?os=$os_revenda'  target='_blank'><img src='imagens/btn_lanca.gif'></a>";
						}
					}elseif (strlen($data_fechamento) == 0) {
						if ($excluida == "f" || strlen($excluida) == 0) {
							echo "<a href='$link_os?os=$os_revenda' target='_blank'><img src='imagens/btn_lanca.gif'></a>";
						}
					}elseif (strlen($data_fechamento) > 0 && strlen($extrato) == 0) {
						if ($excluida == "f" || strlen($excluida) == 0) {
							echo "<a href='$link_os?os=$os_revenda&reabrir=ok' target='_blank'><img src='imagens/btn_reabriros.gif' target='_blank'></a>";
						}
					}
				  }
				  else {
					if ($mostra_motivo == 1) {
						if ($excluida == "f" || strlen($excluida) == 0) {
							echo "<a href='os_item.php?os=$os_revenda'  target='_blank'><img src='imagens/btn_lanca.gif'></a>";
						}
					}elseif (strlen($data_fechamento) == 0) {
						if ($excluida == "f" || strlen($excluida) == 0) {
							echo "<a href='os_item.php?os=$os_revenda' target='_blank'><img src='imagens/btn_lanca.gif'></a>";
						}
					}elseif (strlen($data_fechamento) > 0 && strlen($extrato) == 0) {
						if ($excluida == "f" || strlen($excluida) == 0) {
							echo "<a href='os_item.php?os=$os_revenda&reabrir=ok' target='_blank'><img src='imagens/btn_reabriros.gif' target='_blank'></a>";
						}
					}
				  }
					/*echo "</td>\n";
					$sqlz = "SELECT tbl_os.os
  							 FROM tbl_os_revenda_item
							 JOIN tbl_os ON tbl_os_revenda_item.os_lote=tbl_os.os
							 AND tbl_os.fabrica=1 AND tbl_os.excluida IS NOT TRUE
							 WHERE os_revenda = $os_revenda;";
					$resz = pg_query($con,$sqlz);
					echo "<td width='80' align='center'>";
					if (strlen($data_fechamento) == 0 && strlen($pedido) == 0 && pg_numrows($resz)==0) {
						if ($excluida == "f" || strlen($excluida) == 0) {
							$sua_os_black = $posto_codigo.$sua_os;
							echo "<a href=\"javascript: if (confirm ('Deseja realmente excluir OS $sua_os_black ?') == true) { window.location='$PHP_SELF?excluir=$os_revenda' }\"><img src='imagens/btn_excluir.gif'></A>";
						} else {
							echo "&nbsp;";
						}
					} else {
						echo "&nbsp;";
					}
					echo "</td>\n";*/
				}
			}

			echo "</tr>";
		}

		if (in_array($login_fabrica, array(1)) && $mostra_checkbox === true) {
			echo "<tr class='titulo_coluna'>
				<td colspan='11' style='text-align: left;'>
					Motivo: <input type='text' name='motivo_exclui_os' class='frm' style='width: 680px;' value='{$motivo_exclui_os}' ><input type='submit' id='excluir_os_selecionadas' name='excluir_os_selecionadas' value='Excluir selecionadas' />
				</td>
			</tr>";
		}

		echo "</table>";
		echo "<p align='center'><b>Total de $total_registro registro(s).</b></p>";

		if (in_array($login_fabrica, array(1)) && $mostra_checkbox === true) {
			foreach ($_POST as $key => $value) {
				if (in_array($key, array("motivo_exclui_os", "os_matriz", "excluir_os_selecionadas", "exclui_os_revenda"))) {
					continue;
				}

				echo "<input type='hidden' name='{$key}' value='{$value}' />";
			}

			echo "</form>";
		}
	} else {
		echo "<table border='0' align='center'>";
		echo "<tr>";
		echo "<td><img border='0' src='imagens/atencao.gif'></td>";
		echo "<td> &nbsp; <b>Não foi encontrado nenhuma OS nessa pesquisa.</b></td>";
		echo "</tr>";
		echo "</table>";
	}
}

?>

<br>

<? include "rodape.php" ?>

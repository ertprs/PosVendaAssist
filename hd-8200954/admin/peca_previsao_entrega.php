<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';

$btn_acao = trim (strtolower ($_POST['btn_acao']));
$msg_erro = "";

if ($btn_acao == "gravar") {
	$qtde_item = $_POST['qtde_item'];

	for ($i = 0 ; $i < $qtde_item ; $i++) {
		$peca_referencia  = $_POST['peca_referencia_'  . $i];
		$previsao_entrega = $_POST['previsao_entrega_' . $i];
		$dat = explode ("/", $previsao_entrega);//tira a barra
		$d = $dat[0];
		$m = $dat[1];
		$y = $dat[2];
		if( ($previsao_entrega!="") && (!checkdate($m,$d,$y)) ){
			$campo = $i + 1;
			$msg_erro .="Data Inválida no campo ".$campo.".<br>";
		}
		else{
			if(strlen($peca_referencia) > 0) {
				if(strlen($previsao_entrega) == 0) {
					$previsao_entrega = "null";
				}else{
					$previsao_entrega = str_replace ("/","",$previsao_entrega);
					$previsao_entrega = str_replace ("-","",$previsao_entrega);
					$previsao_entrega = str_replace (".","",$previsao_entrega);
					$previsao_entrega = str_replace (" ","",$previsao_entrega);

					switch (strlen ($previsao_entrega)) {
						case 6:
							$previsao_entrega = "'20" . substr ($previsao_entrega,4,2) . "-" . substr ($previsao_entrega,2,2) . "-" . substr ($previsao_entrega,0,2) . "'";
							break;
						case 8:
							$previsao_entrega = "'" . substr ($previsao_entrega,4,4) . "-" . substr ($previsao_entrega,2,2) . "-" . substr ($previsao_entrega,0,2) . "'";
							break;
						default:

							break;
					}
				}

				if (strlen ($msg_erro) == 0) {
					$sql = "UPDATE tbl_peca SET previsao_entrega = $previsao_entrega WHERE tbl_peca.referencia = '$peca_referencia' AND tbl_peca.fabrica = $login_fabrica";
					$res = pg_exec ($con,$sql);
				}
			}
		}
	}

	if (strlen ($msg_erro) == 0) {
		header ("Location: $PHP_SELF?msg=Gravado com Sucesso!");
		exit;
	}
}

$layout_menu = "cadastro";
$title       = "PREVISÃO DE ENTREGA DE PEÇAS CRÍTICAS";
$body_onload = "javascript: document.frm_pedido.peca_referencia_0.focus()";
$msg         = $_GER['msg'];

include 'cabecalho.php';
include '../js/js_css.php'; // include "javascript_calendario.php";
?>

<script type="text/javascript" charset="utf-8">
	$(function(){

		for (var i = 0; i < document.getElementById('total_qtde_item').value; i++) {
			var nome = '#previsao_entrega_' + i;
			$(nome).datepick({ startDate:'01/01/2000' });
			$(nome).mask('00/00/0000');
		}

	});
</script>

<script language="JavaScript">

function fnc_pesquisa_peca (campo, campo2, tipo) {
    var referencia;
    var descricao;
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "peca_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		peca_referencia	= campo;
		peca_descricao	= campo2;
	}
	else{
		alert("Preencha toda ou parte da informação para realizar a pesquisa!");
	}
}
</script>

<style type="text/css">
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	text-align: center;
	border: 1px solid #d9e2ef;
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	background-color: #CED7e7;
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

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

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
}
</style>

<!-- Peças com Previsão de Entrega -->
<?
$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, TO_CHAR (previsao_entrega,'DD/MM/YYYY') AS previsao , (SELECT SUM (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada) FROM tbl_pedido JOIN tbl_pedido_item USING (pedido) WHERE tbl_pedido_item.peca = tbl_peca.peca AND tbl_pedido.fabrica = $login_fabrica AND tbl_pedido.status_pedido NOT IN (3,4) AND (tbl_pedido.tipo_pedido = 3 OR (tbl_pedido.distribuidor IS NOT NULL AND tbl_pedido.posto <> tbl_pedido.distribuidor))) AS pendencia FROM tbl_peca WHERE previsao_entrega IS NOT NULL AND fabrica = $login_fabrica ORDER BY previsao_entrega";
$res = pg_exec ($con,$sql);

if (pg_numrows ($res) > 0) {
	echo "<table width='700' align='center' border='0' class='tabela'>";
	echo "<tr class='titulo_coluna'>";
	echo "<td>Referência</td>";
	echo "<td>Descrição</td>";
	echo "<td>Previsão</td>";
	echo "<td>Pendências</td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		if($i%2==0) $cor = "#F7F5F0"; else $cor = "#F1F4FA";

		echo "<tr bgcolor='$cor'>";

		echo "<td align='left'>";
		echo pg_result ($res,$i,referencia);
		echo "</td>";

		echo "<td align='left'>";
		echo pg_result ($res,$i,descricao);
		echo "</td>";

		echo "<td align='left'>";
		echo pg_result ($res,$i,previsao);
		echo "</td>";

		echo "<td align='right'>&nbsp;";
		echo pg_result ($res,$i,pendencia);
		echo "</td>";

		echo "</tr>";
	}
	echo "</table>";
}


?>
<br>
<table align="center" width="700">
<tr bgcolor="#d9e2ef" style="font: 14px Aria; color:#596d9b;">
	<td>
		Para alterar a pendência de uma peça lance novamente seu código e a nova previsão.
		<br>
		Para cancelar uma previsão digite o código da peça e não preencha a data.
	</td>
</tr>
</table>
<!-- ------------- Formulário ----------------- -->

<form name="frm_pedido" method="post" action="<? echo $PHP_SELF ?>">

<p>
		<table width="700" border="0" class="formulario" cellspacing="5" cellpadding="0" align='center'>
		<? if($msg_erro != ""){ ?>
			<tr class="msg_erro"><td colspan="4"><? echo $msg_erro; ?></td></tr>
		<? } ?>

		<? if($msg != ""){ ?>
			<tr class="sucesso"><td colspan="4"><? echo $msg; ?></td></tr>
		<? } ?>

		<tr height="20" class="titulo_tabela">
			<td align='center' nowrap width="160">Referência</td>
			<td align='center' nowrap width="400">Descrição</td>
			<td align='center' nowrap>Data Prevista</td>
		</tr>

		<?
		$qtde_item = 30;

		echo "<input class='frm' type='hidden' name='qtde_item' value='$qtde_item'>";

		for ($i = 0 ; $i < $qtde_item ; $i++) {
			$peca_referencia = $_POST["peca_referencia_"  . $i];
			$peca_descricao  = $_POST["peca_descricao_"   . $i];
			$previsao_entrega= $_POST["previsao_entrega_" . $i];

			if($i % 2 == 0) $cor = "#F7F5F0"; else $cor = "#F1F4FA";

		?>
			<tr <? if ($linha_erro == $i and strlen ($msg_erro) > 0) echo "bgcolor='#ffcccc'"; ?>>
				<td align='left'><input class="frm" type="text" name="peca_referencia_<? echo $i ?>" size="13" value="<? echo $peca_referencia ?>" <? if ($login_fabrica == 5) { echo " onblur='document.frm_pedido.lupa_peca_referencia_$i.click()' " ; } ?> ><img id='lupa_peca_referencia_<? echo $i ?>' src='../imagens/lupa.png' alt="Clique para pesquisar por referência do componente" border='0' hspace='5' align='absmiddle' onclick="fnc_pesquisa_peca (window.document.frm_pedido.peca_referencia_<? echo $i ?> , window.document.frm_pedido.peca_descricao_<? echo $i ?> , 'referencia')" style="cursor:pointer;"></td>
				<td align='left'><input class="frm" type="text" name="peca_descricao_<? echo $i ?>" size="43" value="<? echo $peca_descricao ?>" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_peca ( window.document.frm_pedido.peca_referencia_<? echo $i ?> , window.document.frm_pedido.peca_descricao_<? echo $i ?>,'descricao')" <? } ?>><img id='lupa_peca_descricao_<? echo $i ?>' src='../imagens/lupa.png' alt="Clique para pesquisar por descrição do componente" border='0' hspace='5' align='absmiddle' onclick="fnc_pesquisa_peca ( window.document.frm_pedido.peca_referencia_<? echo $i ?> , window.document.frm_pedido.peca_descricao_<? echo $i ?>,'descricao')" style="cursor:pointer;"></td>
				<td align='left'><input class="frm" type="text" name="previsao_entrega_<? echo $i ?>" id="previsao_entrega_<? echo $i ?>"size="11" maxlength="10" value="<? echo $previsao_entrega ?>" ></td>
			</tr>
		<?
		}
		?>
		<tr><td colspan="3">&nbsp;</td></tr>
		<tr>
			<td height="27" valign="middle" align="center" colspan="3" >
				<input type='hidden' name='btn_acao' value=''>
				<input type="button" style="background:url(imagens_admin/btn_gravar.gif); width:75px; cursor:pointer;" value="&nbsp;" onclick="javascript: if (document.frm_pedido.btn_acao.value == '' ) { document.frm_pedido.btn_acao.value='gravar' ; document.frm_pedido.submit() } else { alert ('Aguarde submissão') } return false;" ALT="Gravar formulário" border='0' >
			</td>
		</tr>
		<input type="hidden" name="total_qtde_item" id="total_qtde_item" value="<?=$qtde_item?>" />
		</table>
	</td>

</tr>


</form>

</table>

<p>

<? include "rodape.php"; ?>

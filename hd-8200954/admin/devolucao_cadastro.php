<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';
include 'funcoes.php';

$btn_acao = strtolower ($HTTP_POST_VARS['btn_acao']);

$msg_erro = "";

if ($btn_acao == "gravar") {

	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$qtde_item = $HTTP_POST_VARS['qtde_item'];

	for ($i = 0 ; $i < $qtde_item ; $i++) {
		$cnpj_posto	= $HTTP_POST_VARS['cnpj_posto_'    . $i];
		$linha		= $HTTP_POST_VARS['linha_' . $i];
		$nota_fiscal= $HTTP_POST_VARS['nota_fiscal_' . $i];
		$serie		= $HTTP_POST_VARS['serie_' . $i];
		$data_emissao=$HTTP_POST_VARS['data_emissao_' . $i];
		$valor_total= $HTTP_POST_VARS['valor_total_' . $i];

		if (strlen ($cnpj_posto) > 0) {
			$cnpj_posto = str_replace (".","",$cnpj_posto);
			$cnpj_posto = str_replace (" ","",$cnpj_posto);
			$cnpj_posto = str_replace ("-","",$cnpj_posto);
			$cnpj_posto = str_replace ("/","",$cnpj_posto);

			$res = pg_exec ($con,"SELECT posto FROM tbl_posto_fabrica JOIN tbl_posto USING (posto) WHERE tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto.cnpj = '$cnpj_posto'");
			if (pg_numrows ($res) == 0) {
				$msg_erro = "Posto $posto não cadastrado";
				$linha_erro = $i;
				break;
			}else{
				$posto = pg_result ($res,0,posto);
			}

			if (strlen ($data_emissao) == 0)
				$msg_erro = "Data Inválida";

			if(strlen($msg_erro)==0){
				list($di, $mi, $yi) = explode("/", $data_emissao);
				if(!checkdate($mi,$di,$yi)) 
					$msg_erro = "Data Inválida";
			}

			if (strlen ($msg_erro) == 0) {

				$data_emissao = formata_data ($data_emissao);
				$valor_total  = str_replace (",",".",$valor_total);

				$sql = "INSERT INTO tbl_devolucao (posto, linha, nota_fiscal, serie, data_emissao, valor_total) VALUES ($posto, $linha, LPAD ('$nota_fiscal',6,'0') , SUBSTR (TRIM ('$serie'),0,3) , '$data_emissao', $valor_total )";
				$res = @pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
				if (strlen ($msg_erro) > 0) {
					break;
				}
				
			}
		}
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: devolucao_cadastro.php?msg=Gravado com Sucesso!");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}
?>

<?
	$msg = $_GET['msg'];
	$layout_menu = "financeiro";
	$title = "CADASTRO DE NOTAS DE DEVOLUÇÃO";
	include 'cabecalho.php';
?>
<head>
	<link href="css/estilo_cad_prod.css" rel="stylesheet" type="text/css" />
	<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />
</head>

<html>
<body onload='javascript: document.frm_devolucao.cnpj_posto_0.focus()'>

<script language="JavaScript">

//INICIO DA FUNCAO DATA
function date_onkeydown() {
  if (window.event.srcElement.readOnly) return;
  var key_code = window.event.keyCode;
  var oElement = window.event.srcElement;
  if (window.event.shiftKey && String.fromCharCode(key_code) == "T") {
        var d = new Date();
        oElement.value = String(d.getMonth() + 1).padL(2, "0") + "/" +
                         String(d.getDate()).padL(2, "0") + "/" +
                         d.getFullYear();
        window.event.returnValue = 0;
    }
    if (!window.event.shiftKey && !window.event.ctrlKey && !window.event.altKey) {
        if ((key_code > 47 && key_code < 58) ||
          (key_code > 95 && key_code < 106)) {
            if (key_code > 95) key_code -= (95-47);
            oElement.value =
                oElement.value.replace(/[dma]/, String.fromCharCode(key_code));
        }
        if (key_code == 8) {
            if (!oElement.value.match(/^[dma0-9]{2}\/[dma0-9]{2}\/[dma0-9]{4}$/))
                oElement.value = "dd/mm/aaaa";
            oElement.value = oElement.value.replace(/([dma\/]*)[0-9]([dma\/]*)$/,
                function ($0, $1, $2) {
                    var idx = oElement.value.search(/([dma\/]*)[0-9]([dma\/]*)$/);
                    if (idx >= 5) {
                        return $1 + "a" + $2;
                    } else if (idx >= 2) {
                        return $1 + "m" + $2;
                    } else {
                        return $1 + "d" + $2;
                    }
                } );
            window.event.returnValue = 0;
        }
    }
    if (key_code != 9) {
        event.returnValue = false;
    }
}

function formata_cnpj(campo){
	var cnpj = campo.value.length;
	if (cnpj == 2 || cnpj == 6) campo.value += '.';
	if (cnpj == 10) campo.value += '/';
	if (cnpj == 15) campo.value += '-';

}

</script>


<!--=============== <FUNÇÕES> ================================!-->
<!--  XIN´S POP UP CALENDAR -->

<script language="javascript" src="js/cal2.js">
/*
Xin's Popup calendar script-  Xin Yang (http://www.yxscripts.com/)
Script featured on/available at http://www.dynamicdrive.com/
This notice must stay intact for use
*/
</script>

<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" src="js/jquery.js"></script>
<script language="javascript" src="js/jquery.maskedinput.js"></script>
<script language="javascript" src="js/jquery.maskmoney.js"></script>

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

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
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

<? 
if (strlen ($msg_erro) > 0) {
	if (strpos ($msg_erro,"Cannot insert a duplicate key into unique index tbl_os_sua_os") > 0) $msg_erro = "Esta ordem de serviço já foi cadastrada";

?>
<? if(strlen($msg) > 0) { ?>
	<div class="sucesso" style="width:700px;margin:auto"><?=$msg?></div>
<? } ?>
<table width="700" border="0" cellpadding="0" cellspacing="1" align="center" class='msg_erro'>
<tr>
	<td height="27" valign="middle" align="center">
		<? echo $msg_erro ?>
	</td>
</tr>
</table>
<? } ?>
<script type="text/javascript">
	$(document).ready(function(){
		$('input[name^=data_emissao_]').maskedinput("99/99/9999");
		$('input[name^=cnpj_posto_]').maskedinput("99.999.999/9999-99");
		$("input[name^=valor_total_]").maskMoney({symbol:"R$", decimal:",", thousands:"."});
	});
</script>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" >

<tr>

	<td><img height="1" width="20" src="imagens/spacer.gif"></td>

	<td valign="top" align="center">


		<!-- ------------- Formulário ----------------- -->

		<form name="frm_devolucao" method="post" action="<? echo $PHP_SELF ?>">

		<p>

		<table width="700" border="0" cellspacing="1" cellpadding="0" align='center' class='tabela'>
		<tr height="20" class='titulo_coluna'>
			<td>CNPJ Posto</td>
			<td>Linha</td>
			<td>Nota</td>
			<td>Série</td>
			<td>Data Emissão</td>
			<td>Valor Total</td>
		</tr>

		<?
		$sql = "SELECT * FROM tbl_linha WHERE fabrica = $login_fabrica";
		$res_linha = pg_exec ($con,$sql);

		$qtde_item = 10;
		echo "<input class='frm' type='hidden' name='qtde_item' value='$qtde_item'>";

		for ($i = 0 ; $i < $qtde_item ; $i++) {
			if (strlen ($msg_erro) > 0) {
				$cnpj_posto		= $HTTP_POST_VARS["cnpj_posto_"    . $i];
				$linha			= $HTTP_POST_VARS["linha_"    . $i];
				$nota_fiscal	= $HTTP_POST_VARS["nota_fiscal_" . $i];
				$serie			= $HTTP_POST_VARS["serie_" . $i];
				$data_emissao	= $HTTP_POST_VARS["data_emissao_" . $i];
				$valor_total	= $HTTP_POST_VARS["valor_total_" . $i];
			}

			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
		?>
		<tr <? if ($linha_erro == $i and strlen ($msg_erro) > 0) echo "bgcolor='#ffcccc' height='30' "; else echo "bgcolor='$cor'"; ?>>
			<td>
				<input class='frm' type="text" name="cnpj_posto_<? echo $i ?>" size="20" value="<? echo $cnpj_posto ?>" maxlength='18'>
			</td>
			<td>
				<select class='frm' size='1' name='linha_<? echo $i ?>'>
				<option value=''></option>
				<?
				for ($j = 0 ; $j < pg_numrows ($res_linha) ; $j++) {
					echo "<option value='" . pg_result ($res_linha,$j,linha) . "'";
					if ($linha == pg_result ($res_linha,$j,linha)) echo " selected " ;
					echo ">";
					echo pg_result ($res_linha,$j,nome);
					echo "</option>";
				}
				?>
				</select>
			</td>
			<td>
				<input class='frm' type="text" name="nota_fiscal_<? echo $i ?>" size="7" value="<? echo $nota_fiscal ?>">
			</td>
			<td>
				<input class='frm' type="text" name="serie_<? echo $i ?>" size="5" value="<? echo $serie ?>">
			</td>
			<td>
				<input class='frm' type="text" name="data_emissao_<? echo $i ?>" size="12" tabindex="0" onkeydown="date_onkeydown()" value="" class="data_emissao">

			</td>
			<td>
				<input class='frm' type="text" name="valor_total_<? echo $i ?>" size="10" value="<? echo $valor_total ?>">
			</td>
		</tr>
		<?
		}
		?>
		</table>
	</td>
	<td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>

<tr>
	<td height="27" valign="botton" align="center" colspan="3">
		<p>
		
		<input type="hidden" name="btn_acao" value="">

		<input type='button' value='Gravar' style="cursor:pointer" onclick="javascript: if (document.frm_devolucao.btn_acao.value == '' ) { document.frm_devolucao.btn_acao.value='gravar' ;  document.frm_devolucao.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar" border='0'>
		
	</td>
</tr>

</form>

</table>

<? include "rodape.php" ?>
<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="financeiro";
include "autentica_admin.php";

$layout_menu = "financeiro";
$title = "CONSULTA E MANUTENÇÃO DE EXTRATOS";

$btnacao           = $_POST['btnacao'];

if(strlen($btnacao)>0){
	$extrato           = $_POST['extrato'];

	$valor_total       = $_POST['valor_total'];
	$acrescimo         = $_POST['acrescimo'];
	$desconto          = $_POST['desconto'];
	$valor_liquido     = $_POST['valor_liquido'];
	$nf_autorizacao    = $_POST['nf_autorizacao'];
	$autorizacao_pagto = $_POST['autorizacao_pagto'];
	$data_vencimento   = $_POST['data_vencimento'];
	$data_pagamento    = $_POST['data_pagamento'];
	$obs               = $_POST['obs'];

	if(strlen($valor_total) > 0)
		$xvalor_total = "'".str_replace(",",".",$valor_total)."'";
	else
		$xvalor_total = 'NULL';

	$acrescimo = trim($_POST["acrescimo"]) ;
	if(strlen($acrescimo) > 0)
		$xacrescimo = "'".str_replace(",",".",$acrescimo)."'";
	else
		$xacrescimo = 'NULL';

	$desconto = trim($_POST["desconto"]) ;
	if(strlen($desconto) > 0)
		$xdesconto = "'".str_replace(",",".",$desconto)."'";
	else
		$xdesconto = 'NULL';

	$valor_liquido = trim($_POST["valor_liquido"]) ;
	if(strlen($valor_liquido) > 0)
		$xvalor_liquido = "'".str_replace(",",".",$valor_liquido)."'";
	else
		$xvalor_liquido = 'NULL';

	$nf_autorizacao = trim($_POST["nf_autorizacao"]) ;
	if(strlen($nf_autorizacao) > 0)
		$xnf_autorizacao = "'$nf_autorizacao'";
	else
		$xnf_autorizacao = 'NULL';

	$autorizacao_pagto = trim($_POST["autorizacao_pagto"]) ;
	if(strlen($nf_autorizacao) > 0)
		$xautorizacao_pagto = "'$autorizacao_pagto'";
	else
		$xautorizacao_pagto = 'NULL';

	if (strlen($_POST["data_vencimento"]) > 0) {
		$data_pagamento = trim($_POST["data_pagamento"]) ;
		$xdata_pagamento = str_replace ("/","",$data_pagamento);
		$xdata_pagamento = str_replace ("-","",$xdata_pagamento);
		$xdata_pagamento = str_replace (".","",$xdata_pagamento);
		$xdata_pagamento = str_replace (" ","",$xdata_pagamento);

		$dia = trim (substr ($xdata_pagamento,0,2));
		$mes = trim (substr ($xdata_pagamento,2,2));
		$ano = trim (substr ($xdata_pagamento,4,4));

		if (strlen ($ano) == 2) 
			$ano = "20" . $ano;
		
		$verifica = checkdate($mes,$dia,$ano);
		if ( $verifica ==1){
			$xdata_pagamento = $ano . "-" . $mes . "-" . $dia ;
			$xdata_pagamento = "'" . $xdata_pagamento . "'";
		}else{
			$msg_erro="A Data de Pagamento não está em um formato válido";
		}

	}else{
		$xdata_pagamento = "NULL";
	}
	
	if (strlen($_POST["data_vencimento"]) > 0) {

		$data_vencimento = trim($_POST["data_vencimento"]) ;
		$xdata_vencimento = str_replace ("/","",$data_vencimento);
		$xdata_vencimento = str_replace ("-","",$xdata_vencimento);
		$xdata_vencimento = str_replace (".","",$xdata_vencimento);
		$xdata_vencimento = str_replace (" ","",$xdata_vencimento);

		$dia = trim (substr ($xdata_vencimento,0,2));
		$mes = trim (substr ($xdata_vencimento,2,2));
		$ano = trim (substr ($xdata_vencimento,4,4));
		if (strlen ($ano) == 2) $ano = "20" . $ano;
		$verifica = checkdate($mes,$dia,$ano);

		if ( $verifica ==1){
			$xdata_vencimento = $ano . "-" . $mes . "-" . $dia ;
			$xdata_vencimento = "'" . $xdata_vencimento . "'";
		}else{
			$msg_erro .="<br>A Data de Vencimento não está em um formato válido<br>";
		}

	}else{
		$xdata_vencimento = "NULL";
	}

	if (strlen($_POST["obs"]) > 0) {
		$obs = trim($_POST["obs"]) ;
		$xobs = "'" . $obs . "'";
	}else{
		$xobs = "NULL";
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if (strlen($extrato) > 0) {
			$sql = "SELECT extrato FROM tbl_extrato WHERE fabrica = $login_fabrica AND extrato = $extrato";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res) == 0) $msg_erro = "Erro ao cadastrar baixa. Extrato não pertence à esta fábrica.";
		}
		if (strlen($extrato) > 0 AND strlen($msg_erro) == 0) {
			$sql = "SELECT extrato_pagamento FROM tbl_extrato_pagamento WHERE extrato = $extrato";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res) > 0) $msg_erro = "Extrato já foi pago";
		}
		if (strlen($msg_erro) == 0) {
			if (strlen($extrato) > 0) {
				$sql = "INSERT INTO tbl_extrato_pagamento (
							extrato           ,
							valor_total       ,
							acrescimo         ,
							desconto          ,
							valor_liquido     ,
							nf_autorizacao    ,
							data_vencimento   ,
							data_pagamento    ,
							autorizacao_pagto ,
							obs               ,
							admin
						)VALUES(
							$extrato           ,
							$xvalor_total      ,
							$xacrescimo        ,
							$xdesconto         ,
							$xvalor_liquido    ,
							$xnf_autorizacao   ,
							$xdata_vencimento  ,
							$xdata_pagamento   ,
							$xautorizacao_pagto,
							$xobs              ,
							$login_admin
						)";
			}
// echo $sql;
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			header ("Location: extrato_baixa.php");
			exit;
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}

}

include "cabecalho.php";
?>
<br>

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
.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
}
.espaco{
	padding-left:130px;
}
.subtitulo{
    background-color: #7092BE;
    font:bold 14px Arial;
    color: #FFFFFF;
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
</style>

<? include "javascript_calendario.php"; ?>
<script language='javascript' src='../ajax.js'></script>
<script language='javascript' src='js/jquery.maskmoney.js'></script>
<script language='javascript' src='ajax_extrato.js'></script>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

<script type="text/javascript">
$(document).ready(function(){
	$(".data").maskedinput("99/99/9999");
	$(".valor").maskMoney({
		decimal:",",
		thousands:".",
		showSymbol:false
	});
	$(".numero").maskMoney({
		decimal:"",
		thousands:"",
		precision:0,
		showSymbol:false
	});
});	
</script>

<script language="javascript">
function calculaLiquido(){
	var valor_total = document.frm_extrato.valor_total.value;
	var	acrescimo = document.frm_extrato.acrescimo.value;
	var	desconto = document.frm_extrato.desconto.value;
	
	if (valor_total.length>0){
		if (acrescimo.length==0){acrescimo = 0;}
		if (desconto.length==0){desconto = 0;}
		document.frm_extrato.valor_liquido.value = (valor_total + acrescimo) - desconto;

	}
}
</script>
<script type="text/javascript">
</script>
<?
if (strlen($msg_erro) > 0) {
	echo '<table align="center" class="formulario" width="700" border="0">';
	echo "<tr class='msg_erro'>";
	echo "<td>$msg_erro</td>";
	echo "</tr>";
	echo "</table>\n";
}
?>
<form name='frm_extrato' method='post' action='<?php echo $PHP_SELF;?>'>
	<input type='hidden' name='btnacao' value=''>
		
	<table align="center" class="formulario" width="700" border="0">

		<tr class="titulo_tabela">
			<td colspan="5">Pagamento</td>
		</tr>

		<tr>
			<td align='left'>Extrato</td>
			<td align='left'>Posto</td>
			<td align='left'>Data</td>
			<td align='left'>Valor</td>
			<td align='left'>Qtde. OS</td>
		</tr>
		<tr>
			<td align='left'>
				<input type='text' class='frm' name='extrato' value="<?php echo $extrato;?>" size='5'  maxlength='6' onblur='buscaExtrato(document.frm_extrato.extrato,document.frm_extrato.codigo_posto,document.frm_extrato.data_extrato,document.frm_extrato.valor_extrato,document.frm_extrato.qtde_os)'>
			</td>
			<td align='left'>
				<input type='text' class='frm' name='codigo_posto' value="<?php echo $codigo_posto;?>" size='30' disabled>
			</td>
			<td align='left'>
				<input type='text' class='frm data' name='data_extrato' value="<?php echo $data_extrato;?>" size='8' disabled>
			</td>
			<td align='left'>
				<input type='text' class='frm valor' name='valor_extrato' value="<?php echo $valor_extrato;?>" size="10" disabled>
			</td>
			<td align='left'>
				<input type='text' class='frm' name='qtde_os' value="<?php echo $qtde_os;?>" size='4' disabled>
			</td>
		</tr>

		<tr>
			<td align="left">Valor Total (R$)</td>
			<td align="left">Acréscimo (R$)</td>
			<td align="left">Desconto (R$)</td>
			<td align="left" colspan="2">Valor Líquido (R$)</td>
		</tr>

		<tr>
			<td align='left'>
				<input type='text' name='valor_total' size='10' maxlength='10' value="<?php echo $valor_total;?>" style='text-align:right' class='frm valor'>
			</td>
			<td align='left'>
				<input type='text' name='acrescimo' size='10' maxlength='10' value="<?php echo $acrescimo;?>" style='text-align:right' class='frm valor'>
			</td>
			<td align='left'>
				<input type='text' name='desconto' size='10' maxlength='10' value="<?php echo $desconto ;?>" style='text-align:right' class='frm valor'>
			</td>
			<td align='left' colspan="2">
				<input type='text' name='valor_liquido' size='10' maxlength='10' value="<?php echo $valor_liquido;?>" style='text-align:right' class='frm valor'>
			</td>
		</tr>

		<tr>
			<td align='left'>Data de Vencimento</td>
			<td align='left'>Número Nota Fiscal</td>
			<td align='left'>Data de Pagamento</td>
			<td align='left' colspan="2">Autorização Número</td>
		</tr>

		<tr>
			<td align='left'>
				<input type='text' name="data_vencimento" size='10' maxlength='10' value="<?php echo $data_vencimento;?>" class="frm data">
			</td>
			<td align='left'>
				<input type='text' name='nf_autorizacao'  size='10' maxlength='20' value="<?php echo $nf_autorizacao;?>" class="frm">
			</td>
			<td align='left'>
				<input type='text' name='data_pagamento' size='10' maxlength='10' value="<?php echo $data_pagamento;?>" class='frm data'>
			</td>
			<td align='left' colspan="2">
				<input type='text' name='autorizacao_pagto' size='10' maxlength='20' value="<?php echo $autorizacao_pagto;?>" class='frm'>
			</td>
		</tr>

		<tr>
			<td align='center' colspan='5'>Observação</td>
		</tr>
		<tr>
			<td align='center' colspan='5'>
				<textarea name='obs' cols="100" rows="5" class="frm"><?php echo $obs;?></textarea>
			</td>
		</tr>

		<tr>
			<td align='center' colspan='5'>
				<input type="button" value="Baixar" onclick="if (document.frm_extrato.btnacao.value == '' ) { document.frm_extrato.btnacao.value='baixar' ; document.frm_extrato.submit() } else { alert ('Aguarde submissão') }" title='Baixar' style='cursor:pointer;'>
			</td>
		</tr>
		<tr>
			<td colspan='5'>&nbsp;</td>
		</tr>

	</table>
</form>
<?
include "rodape.php";
?>
<?

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$array_estado = array(" "=>" ","AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas",
  "AP"=>"AP - Amapá", "BA"=>"BA - Bahia", "CE"=>"CE - Ceará","DF"=>"DF - Distrito Federal",
  "ES"=>"ES - Espírito Santo", "GO"=>"GO - Goiás","MA"=>"MA - Maranhão","MG"=>"MG - Minas Gerais",
  "MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Pará","PB"=>"PB - Paraíba",
  "PE"=>"PE - Pernambuco","PI"=>"PI - Piauí","PR"=>"PR - Paraná","RJ"=>"RJ - Rio de Janeiro",
  "RN"=>"RN - Rio Grande do Norte","RO"=>"RO - Rondônia","RR"=>"RR - Roraima",
  "RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe",
  "SP"=>"SP - São Paulo","TO"=>"TO - Tocantins");

$btn_acao = strtolower ($_POST['btn_acao']);

if (strlen($_POST['extrato_tipo_nota']) > 0) $extrato_tipo_nota = trim($_POST['extrato_tipo_nota']);
if (strlen($_GET['extrato_tipo_nota']) > 0)  $extrato_tipo_nota = trim($_GET['extrato_tipo_nota']);

if (strlen($_POST['estado']) > 0) $estado = trim($_POST['estado']);
if (strlen($_GET['estado']) > 0)  $estado = trim($_GET['estado']);

$msg_sucesso = ( trim($_POST["msg_sucesso"]) ) ?  trim( $_POST["msg_sucesso"] ) : trim( $_GET["msg_sucesso"] )  ;
if ($btn_acao == "apagar"){
	$res = pg_query ($con,"BEGIN TRANSACTION");

	if(!empty($estado)) {
		$sql = "DELETE FROM tbl_extrato_tipo_nota_excecao
			WHERE  tbl_extrato_tipo_nota_excecao.extrato_tipo_nota   = $extrato_tipo_nota AND estado ='$estado';";
		$res = @pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}else{
		$sql = "DELETE FROM tbl_extrato_tipo_nota
				WHERE  tbl_extrato_tipo_nota.fabrica      = $login_fabrica
				AND    tbl_extrato_tipo_nota.extrato_tipo_nota   = $extrato_tipo_nota;";
		$res = @pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
	if (strlen ($msg_erro) == 0) {
		$res = pg_query ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF");
		exit;
	}else{
		$descricao      = $_POST["descricao"];
		$debito_credito = $_POST["debito_credito"];
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}
}

if ($btn_acao == "gravar") {
	
	$descricao           = trim($_POST['descricao']);
	$cfop                = trim($_POST['cfop']);
	$codigo_item         = trim($_POST['codigo_item']);
	$estado              = trim($_POST['estado']);
	$excecao_cfop        = trim($_POST['excecao_cfop']);
	$excecao_codigo_item = trim($_POST['excecao_codigo_item']);

	if (strlen($cfop) == 0) $msg_erro = "Digite o CFOP.";
	else                              $xcfop = "'". trim($_POST["cfop"]) ."'";

	if (strlen($descricao) == 0)      $msg_erro = "Digite a descricao de tipo de nota.";
	else                              $xdescricao = "'". trim(strtoupper($_POST["descricao"])) ."'";

	if (strlen($codigo_item) == 0)      $msg_erro = "Digite o código de item.";
	else                              $xcodigo_item = "'". trim($_POST["codigo_item"]) ."'";

	if(!empty($estado)) {
		if (strlen($excecao_cfop) == 0) $msg_erro = "Digite o CFOP para exceção.";
		else                              $xexcecao_cfop = "'". trim($_POST["excecao_cfop"]) ."'";

	
		if (strlen($excecao_codigo_item) == 0)      $msg_erro = "Digite o código de item para exceção.";
		else                              $xexcecao_codigo_item = "'". trim($_POST["excecao_codigo_item"]) ."'";
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_query($con,"BEGIN TRANSACTION");

		if (strlen ($extrato_tipo_nota) == 0) {
			$sql = "INSERT INTO tbl_extrato_tipo_nota (
						fabrica         ,
						descricao       ,
						cfop            ,
						codigo_item
					) VALUES (
						$login_fabrica  ,
						$xdescricao     ,
						$xcfop,
						$xcodigo_item
					)";
		}else{
			$sql = "UPDATE tbl_extrato_tipo_nota SET
						descricao       = $xdescricao   ,
						cfop            = $xcfop        ,
						codigo_item     = $xcodigo_item
					WHERE extrato_tipo_nota   = $extrato_tipo_nota";
		}
		$res = pg_query ($con,$sql);
		$msg_erro = pg_last_error($con);
		$msg_erro = substr($msg_erro,6);

		if(!empty($estado) and !empty($extrato_tipo_nota)) {
			$sql = "SELECT estado
					FROM tbl_extrato_tipo_nota_excecao
					WHERE extrato_tipo_nota = $extrato_tipo_nota
					AND   estado = '$estado'";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) == 0){
				$sql = "INSERT INTO tbl_extrato_tipo_nota_excecao (
						extrato_tipo_nota,
						estado         ,
						cfop            ,
						codigo_item
					) VALUES (
						$extrato_tipo_nota,
						'$estado'         ,
						$xexcecao_cfop,
						$xexcecao_codigo_item
					)";
			}else{
				$sql = "UPDATE tbl_extrato_tipo_nota_excecao SET
						cfop            = $xexcecao_cfop,
						codigo_item     = $xexcecao_codigo_item
						WHERE extrato_tipo_nota = $extrato_tipo_nota
						AND   estado = '$estado'";
			}
			$res = pg_query ($con,$sql);
			$msg_erro = pg_last_error($con);
			$msg_erro = substr($msg_erro,6);
		}
	}
	
	if (strlen ($msg_erro) == 0) {
		$res = pg_query ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF?msg_sucesso=Gravado com sucesso");
		exit;
	}else{
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}
}

/*================ LE DA BASE DE DADOS =========================*/
if (strlen ($extrato_tipo_nota) > 0) {
	$sql = "SELECT	*
			FROM	tbl_extrato_tipo_nota
			WHERE	extrato_tipo_nota = $extrato_tipo_nota";
	$res = pg_query ($con,$sql);

	if (pg_num_rows ($res) == 1) {
		$extrato_tipo_nota = pg_fetch_result ($res,0,'extrato_tipo_nota');
		$descricao         = pg_fetch_result ($res,0,'descricao');
		$cfop              = pg_fetch_result ($res,0,'cfop');
		$codigo_item       = pg_fetch_result ($res,0,'codigo_item');
	}

	if(!empty($estado)) {
		$sqle = "SELECT	*
			FROM	tbl_extrato_tipo_nota_excecao
			WHERE	extrato_tipo_nota = $extrato_tipo_nota
			AND     estado = '$estado'";
		$rese = pg_query ($con,$sqle);

		if (pg_num_rows ($res) == 1) {
			$estado              = pg_fetch_result ($rese,0,'estado');
			$excecao_cfop        = pg_fetch_result ($rese,0,'cfop');
			$excecao_codigo_item = pg_fetch_result ($rese,0,'codigo_item');
		}	
	}
}


/*============= RECARREGA FORM EM CASO DE ERRO ==================*/
if (strlen ($msg_erro) > 0) {
	$descricao            = $_POST['descricao'];
	$cfop                 = $_POST['cfop'];
	$codigo_item          = $_POST['codigo_item'];
	$estado               = $_POST['estado'];
	$excecao_cfop         = $_POST['excecao_cfop'];
	$excecao_codigo_item  = $_POST['excecao_codigo_item'];
}

$title       = "CADASTRO DE TIPO DE NOTA DO EXTRATO";
$layout_menu = 'cadastro';

include "cabecalho.php";

?>
<? include "javascript_calendario_new.php"; ?>


<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />
<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->

<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script> 
<script>
$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio").tablesorter();

});
</script>
<style type="text/css">

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

.menu_top {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 14px;
	font-weight: bold;
	border: 1px solid;
	color:#000000;
	background-color: #d9e2ef
}

.border {
	border: 1px solid #ced7e7;
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
}

.msg_sucesso{
background-color: green;
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
    font:bold 14px Arial;
    color: #FFFFFF;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
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

<table width='700' align='center' class='texto_avulso'>
	<tr>
		<td>
			Para cadastrar exceção de tipo de nota, deve primeiramente cadastrar o tipo de nota e depois selecioná-lo para aparecer o cadastro da exceção
		</td>
	</tr>
</table>
<br>
<? 
if ( strlen( $msg_sucesso ) > 0 && strlen( $msg_erro ) <= 0 ) {
?>
<table class='formulario' cellpadding="2" cellspacing="0" align="center" width='700px'>
<tr>
	<td align="center" class='msg_sucesso'>
		<? echo $msg_sucesso ?>
	</td>
</tr>
</table>
<? } ?>

<? 
if (strlen ($msg_erro) > 0) {
	if(strpos($msg_erro,"violates foreign key constraint")) $msg_erro = "Não é possível excluir este registro porque está sendo usado em outras partes do sistema";
?>
<table class='msg_erro' align="center" width='700' >
<tr>
	<td align="center" >
		<? echo $msg_erro ?>
	</td>
</tr>
</table>
<? } ?>



<form style="MARGIN: 0px; WORD-SPACING: 0px" name="frm_tipo_nota" method="post" action="<? echo $PHP_SELF ?>?msg_sucesso=Gravado com Sucesso!">
<table width='700px' cellpadding="2" cellspacing="0" align="center" class='formulario' border='0'>

<input class="frm" type="hidden" name="extrato_tipo_nota" value="<? echo $extrato_tipo_nota; ?>">

<tr>
	<td colspan='5' class='titulo_tabela'> Cadastro </td>
</tr>
	<tr>	<td>&nbsp;</td>	   </tr>
	
	<tr>
		<td width='5%'>&nbsp;</td>
		<td>Descrição</td>
		
		<td>CFOP</td>
		
	</tr>
	
	<tr>
		<td width='7%'>&nbsp;</td>
		<td width='280'>
			<input type="text" class="frm" name="descricao" size="40" maxlength="20" value="<? echo $descricao ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';">
		</td>
		
		<td>
			<input type="text" class="frm" name="cfop" size="20" maxlength="10" value="<? echo $cfop ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';">
		</td>
	</tr>
	
	<tr>	<td>&nbsp;</td>		</tr>
	
	<tr>
		<td width='7%'>&nbsp;&nbsp;</td>
		<td>Código Item</td>
		
	</tr>
	
	<tr>
		<td width='7%'>&nbsp;&nbsp;</td>
		<td>
			<input type="text" class="frm" name="codigo_item" size="40" maxlength="15" value="<? echo $codigo_item ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';">
		</td>
	</tr>
	
	<?php if(!empty($extrato_tipo_nota)) { ?>
	<tr>
		<td colspan="5" align='center'>
			<table width='700px' border="0" cellpadding="0" cellspacing="0" align="center" class='formulario'>
			<tr>	<td>&nbsp;</td>		</tr>
			<tr>
				<td colspan='5' class='subtitulo' align='center'> Exceção </td>
			</tr>
			<tr>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td width='7%'>&nbsp;</td>
				<td width='280'>Estado</td>
				
				<td>CFOP</td>
				
			</tr>
			
			<tr>
				<td width='7%'>&nbsp;&nbsp;</td>
				
				<td width='280'>
					<select name="estado" class="frm" id="estado" style="width:200px;"><?php
					foreach ($array_estado as $k => $v) {
					echo '<option value="'.$k.'"'.($estado == $k ? ' selected="selected"' : '').'>'.$v."</option>\n";
					}?>
					</select>
				</td>
				
				<td>
					<input type="text" class="frm" name="excecao_cfop" size="20" maxlength="10" value="<? echo $excecao_cfop ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';">
				</td>
			</tr>
			
			<tr>	<td>&nbsp;</td>		</tr>
			
			<tr>
				<td width='7%'>&nbsp;&nbsp;</td>
				<td>Código Item</td>
				
			</tr>
			
			<tr>
				<td width='7%'>&nbsp;&nbsp;</td>
				
				<td><input type="text" class="frm" name="excecao_codigo_item" style="width:197px;" maxlength="15" value="<? echo $excecao_codigo_item ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';"></td>
			</tr>
			
			<tr>	<td>&nbsp;</td>		</tr>
			</table>
		</td>
	</tr>

	<?php } ?>
	<TR>
		<td colspan="5" align='center'>
			<input type='hidden' name='btn_acao' value=''>
			<input type="button"  value="Gravar" ONCLICK="javascript: if (document.frm_tipo_nota.btn_acao.value == '' ) { document.frm_tipo_nota.btn_acao.value='gravar' ; document.frm_tipo_nota.submit() } else { alert ('Aguarde submissão') } return false;" ALT="Gravar formulário" border='0' >
			<input type="button" value="Apagar" ONCLICK="javascript: if (document.frm_tipo_nota.btn_acao.value == '' ) { document.frm_tipo_nota.btn_acao.value='apagar' ; document.frm_tipo_nota.submit() } else { alert ('Aguarde submissão') } return false;" ALT="Apagar Informação" border='0' >
			<input type="button" value="Limpar" ONCLICK="javascript: window.location='<? echo $PHP_SELF ?>'; return false;" ALT="Limpar campos" border='0' >
	</TR>

</TABLE>
<p>

</form>

<p>

<?

$sql = "SELECT  *
	FROM    tbl_extrato_tipo_nota
	WHERE   tbl_extrato_tipo_nota.fabrica = $login_fabrica
	ORDER BY tbl_extrato_tipo_nota.descricao;";
$res = pg_query($con,$sql);
if (pg_num_rows($res) > 0){
	echo "<center><div style='width:700px;'><TABLE width='700px' border='0' cellspacing='2' cellpadding='2' align='center' name='relatorio' id='relatorio'  class='tabela'>";

	echo "<tr class='titulo_coluna'>";
	echo "<td align='left' >&nbsp; Descrição</td>";
	echo "<td align='left' >&nbsp; CFOP</td>";
	echo "<td align='left' >&nbsp; Código Item</td>";
	echo "</tr>";	
	$var1 = 0;
	for ($i = 0; $i < pg_num_rows($res); $i++){

        $cor = ($var1 % 2) ? "#F1F4FA" : "#F7F5F0";

		$extrato_tipo_nota  = pg_fetch_result($res,$i,'extrato_tipo_nota');
		$descricao          = pg_fetch_result($res,$i,'descricao');
		$cfop               = pg_fetch_result($res,$i,'cfop');
		$codigo_item        = pg_fetch_result($res,$i,'codigo_item');


		echo "<tr bgcolor='$cor'>";
		echo "<td align='left'>&nbsp; <a href='$PHP_SELF?extrato_tipo_nota=$extrato_tipo_nota'>$descricao</a></td>";
		echo "<td align='left'>&nbsp; $cfop</td>";
		echo "<td align='left'>&nbsp; $codigo_item</td>";
		echo "</tr>";

		$sql = "SELECT 
					estado,
					cfop,
					codigo_item
				FROM tbl_extrato_tipo_nota_excecao
				WHERE extrato_tipo_nota = $extrato_tipo_nota
				ORDER BY estado";
		$rese = pg_query($con,$sql);
		$var1 = $i;
		if(pg_num_rows($rese) > 0){
			for($j =0;$j<pg_num_rows($rese);$j++) {
				$estado              = pg_fetch_result($rese,$j,'estado');
				$excecao_cfop        = pg_fetch_result($rese,$j,'cfop');
				$excecao_codigo_item = pg_fetch_result($rese,$j,'codigo_item');
				
				$cor2 = ($var1 % 2) ? "#F7F5F0" : "#F1F4FA";
				$var1 ++;		
				echo "<tr bgcolor='$cor2'>";
				echo "<td align='left'>&nbsp; <a href='$PHP_SELF?extrato_tipo_nota=$extrato_tipo_nota&estado=$estado'>$descricao - $estado</a></td>";
				echo "<td align='left'>&nbsp; $excecao_cfop</td>";
				echo "<td align='left'>&nbsp; $excecao_codigo_item</td>";
				echo "</tr>";
			}
			$var1 ++;
		}
	}

	echo "</table>";
	echo "</div>";
}
?>
<? include "rodape.php"; ?>
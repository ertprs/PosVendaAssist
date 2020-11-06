
<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "gerencia,call_center";
include "autentica_admin.php";

include "funcoes.php";

$erro = "";

if (strlen($_POST["botao"]) > 0) $botao = strtoupper($_POST["botao"]);

if (strtoupper($btnacao) == "BUSCAR") {
	$x_data_inicial = trim($_POST["data_inicial"]);
	$x_data_final   = trim($_POST["data_final"]);
	$linha          = $_POST["linha"];
	$estado         = $_POST["estado"];
	$ordem          = $_POST["ordem"];
	$ordem1         = $_POST["ordem1"];

	if (strlen($x_data_inicial) == 0) $erro = " Data Inválida ";
	if (strlen($x_data_final) == 0)   $erro = " Data Inválida ";
	
	if(strlen($erro) == 0){
		list($d, $m, $y) = explode("/", $x_data_inicial);
		if(!checkdate($m,$d,$y)) $erro = "Data Inválida";
	}
	if(strlen($erro) == 0){
		list($d, $m, $y) = explode("/", $x_data_final);
		if(!checkdate($m,$d,$y)) $erro = "Data Inválida";
	}
	if (strlen($erro) == 0) {
		$x_data_inicial = fnc_formata_data_pg($x_data_inicial);
		$x_data_final   = fnc_formata_data_pg($x_data_final);
		
		if ($x_data_inicial != "null") {
			$data_inicial = substr($x_data_inicial,9,2) . "/" . substr($x_data_inicial,6,2) . "/" . substr($x_data_inicial,1,4);
		}else{
			$data_inicial = "";
			$erro = " Data Inválida ";
		}
		
		if ($x_data_final != "null") {
			$data_final = substr($x_data_final,9,2) . "/" . substr($x_data_final,6,2) . "/" . substr($x_data_final,1,4);
		}else{
			$data_final = "";
			$erro = " Data Inválida ";
		}
	}
	
	$xdata_i = str_replace("'","",$x_data_inicial);
	$xdata_f = str_replace("'","",$x_data_final);
	
	if (strlen($erro) == 0) {
		if($x_data_inicial > $x_data_final){
			$erro = "Data inválida";
		}
	}
	if (strlen($erro) > 0) {
		//$msg = "Foi detectado o seguinte erro:<br>";
		//$msg .= $erro;
	}else{
		$relatorio = "gerar";
	}
	
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
}

$layout_menu = "auditoria";

$title = "CÓDIGO DE FABRICAÇÃO DOS PRODUTOS EM OS";
include 'cabecalho.php';

include "javascript_calendario_new.php";
include "../js/js_css.php";

?>

<style type="text/css">
<!--
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

.BotaoEspecial {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #596D9B;
	background-color: #FFFFFF;
	border-color: #596D9B;
	
}

-->
</style>


<script LANGUAGE="JavaScript">
	function Redirect(produto, data_i, data_f, mobra) {
		window.open('rel_new_visao_geral_peca.php?produto=' + produto + '&data_i=' + data_i + '&data_f=' + data_f + '&mobra=' + mobra,'1', 'height=400,width=750,location=no,scrollbars=yes,menubar=no,toolbar=no,resizable=no')
	}
</script>

<script LANGUAGE="JavaScript">
	function Redirect1(produto, data_i, data_f) {
		window.open('rel_new_visao_os.php?produto=' + produto + '&data_i=' + data_i + '&data_f=' + data_f + '&estado=<? echo $estado; ?>','1', 'height=400,width=750,location=no,scrollbars=yes,menubar=no,toolbar=no,resizable=no')
	}

	$(function(){
		$("#data_inicial").datepick({startDate:'01/01/2010'});
		$("#data_final").datepick({startDate:'01/01/2010'});
		$("#data_inicial").mask("99/99/9999");
		$("#data_final").mask("99/99/9999");
	});
</script>

<!--
<script language="JavaScript" src="js/cal2.js"></script>
<script language="JavaScript" src="js/cal_conf2.js"></script>
-->
<p>

<? if (strlen($erro) > 0) { ?>
<table width="700" border="0" cellpadding="2" cellspacing="0" align="center" class="msg_erro">
	<tr>
		<td><?echo $erro?></td>
	</tr>
</table>
<? } ?>



<form method="POST" action="<?echo $PHP_SELF?>" name="frm_os_aprovada">

<table width="700" border="0" cellpadding="2" cellspacing="2" align="center" class="formulario">
<tr class="titulo_tabela"><td colspan="4">Apenas das OS Aprovadas</td></tr>
<tr>
	<td width="100">&nbsp;</td>
	<td>
		Data Início<br>
		<input type="text" name="data_inicial" id="data_inicial" size="11" maxlength="10" value="<? echo $data_inicial ?>" class="frm">
	</td>
	<td>
		Data Final<br>
		<input type="text" name="data_final" id="data_final" size="11" maxlength="10" value="<? echo $data_final ?>" class="frm">
	</td>
	<td width="50">&nbsp;</td>
</tr>
<tr>
	<td width="20">&nbsp;</td>
	<td >
	Linha <br>
	<?
	$sql = "SELECT   linha,
					 nome
			FROM     tbl_linha
			where    fabrica = $login_fabrica
			ORDER BY nome;";
	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows($res) > 0) {
		echo "<select name='linha' class='frm'>\n";
		echo "<option value=''></option>\n";
		
		for ($x = 0 ; $x < pg_numrows($res) ; $x++){
			$aux_linha = trim(pg_result($res,$x,linha));
			$aux_nome  = trim(pg_result($res,$x,nome));
			
			echo "<option value='$aux_linha'"; if ($linha == $aux_linha) echo " SELECTED "; echo ">$aux_nome</option>\n";
		}
		
		echo "</select>\n";
	}
	?>
	</td>
<?
if($login_fabrica == 1){
?>
    <td>
        Marca<br />
        <select name="marca" class="frm">
            <option value=''>Todas</option>
<?
    $sqlMarca = "
        SELECT  marca,
                nome
        FROM    tbl_marca
        WHERE   fabrica = $login_fabrica;
    ";
    $resMarca = pg_query($con,$sqlMarca);
    $marcas = pg_fetch_all($resMarca);

    foreach($marcas as $chave => $valor){
?>
            <option value="<?=$valor['marca']?>" <?=($valor['marca'] == $_GET['marca']) ? "selected='selected'" : "" ?>><?=$valor['nome']?></option>
<?
    }
?>
            </select>
        </td>
        <td width="20">&nbsp;</td>
    </tr>
    <tr>
        <td width="20">&nbsp;</td>
<?
}
?>
	<td <?=($login_fabrica == 1) ? "colspan='2'" : ""?>>
		Agrupar por Estado <br>
		<select name="estado" size="1" class="frm">
			<option value=""   <? if (strlen($estado) == 0)    echo " selected "; ?>>UF</option>
			<option value="AC" <? if ($estado == "AC") echo " selected "; ?>>AC - Acre</option>
			<option value="AL" <? if ($estado == "AL") echo " selected "; ?>>AL - Alagoas</option>
			<option value="AM" <? if ($estado == "AM") echo " selected "; ?>>AM - Amazonas</option>
			<option value="AP" <? if ($estado == "AP") echo " selected "; ?>>AP - Amapá</option>
			<option value="BA" <? if ($estado == "BA") echo " selected "; ?>>BA - Bahia</option>
			<option value="CE" <? if ($estado == "CE") echo " selected "; ?>>CE - Ceará</option>
			<option value="DF" <? if ($estado == "DF") echo " selected "; ?>>DF - Distrito Federal</option>
			<option value="ES" <? if ($estado == "ES") echo " selected "; ?>>ES - Espírito Santo</option>
			<option value="GO" <? if ($estado == "GO") echo " selected "; ?>>GO - Goiás</option>
			<option value="MA" <? if ($estado == "MA") echo " selected "; ?>>MA - Maranhão</option>
			<option value="MG" <? if ($estado == "MG") echo " selected "; ?>>MG - Minas Gerais</option>
			<option value="MS" <? if ($estado == "MS") echo " selected "; ?>>MS - Mato Grosso do Sul</option>
			<option value="MT" <? if ($estado == "MT") echo " selected "; ?>>MT - Mato Grosso</option>
			<option value="PA" <? if ($estado == "PA") echo " selected "; ?>>PA - Pará</option>
			<option value="PB" <? if ($estado == "PB") echo " selected "; ?>>PB - Paraíba</option>
			<option value="PE" <? if ($estado == "PE") echo " selected "; ?>>PE - Pernambuco</option>
			<option value="PI" <? if ($estado == "PI") echo " selected "; ?>>PI - Piauí</option>
			<option value="PR" <? if ($estado == "PR") echo " selected "; ?>>PR - Paraná</option>
			<option value="RJ" <? if ($estado == "RJ") echo " selected "; ?>>RJ - Rio de Janeiro</option>
			<option value="RN" <? if ($estado == "RN") echo " selected "; ?>>RN - Rio Grande do Norte</option>
			<option value="RO" <? if ($estado == "RO") echo " selected "; ?>>RO - Rondônia</option>
			<option value="RR" <? if ($estado == "RR") echo " selected "; ?>>RR - Roraima</option>
			<option value="RS" <? if ($estado == "RS") echo " selected "; ?>>RS - Rio Grande do Sul</option>
			<option value="SC" <? if ($estado == "SC") echo " selected "; ?>>SC - Santa Catarina</option>
			<option value="SE" <? if ($estado == "SE") echo " selected "; ?>>SE - Sergipe</option>
			<option value="SP" <? if ($estado == "SP") echo " selected "; ?>>SP - São Paulo</option>
			<option value="TO" <? if ($estado == "TO") echo " selected "; ?>>TO - Tocantins</option>
		</select>
	</td>
	
	<td width="20">&nbsp;</td>
</tr>
<tr>
	<td width="20">&nbsp;</td>
	
	<td colspan='2'>
		<fieldset style="width:230px;">
			<legend>Ordernar por</legend>
			<table width="100%">
				<tr>
					<td  width="100%" colspan='2'>
						<input type="radio" name="ordem" value="ocorrencia" <?php if ($ordem == "ocorrencia" OR strlen($ordem) == 0) echo " checked "?> >Ocorrência
						<input type="radio" name="ordem" value="produto"    <?php if ($ordem == "produto"    OR strlen($ordem) == 0) echo " checked "?> >Código de Fabricação
						<!--&nbsp;
						<input type="radio" name="ordem" value="soma_total" <?php if ($ordem == "soma_total") echo " checked "?> >Total-->
					</td>
				</tr>
			</table>
		</fieldset>
		
	</td>
	
	<td width="20">&nbsp;</td>
</tr>

<tr>
	<td width="20">&nbsp;</td>
	
	<td  align="center" colspan="2">
		<input type="submit" value="BUSCAR" name="btnacao" style="width:100px">
	</td>
	
	<td width="20">&nbsp;</td>
</tr>
</table>

</form>

<?
echo "<table width='650' border='0' cellpadding='2' cellspacing='2' align='center'>";
echo "<tr>";

echo "<td bgcolor='#FFFFFF' align='center' width='100%'>";
echo "<font face='Verdana, Arial, Helvetica, sans' color='$css' size='2'>$msg</font>";
echo "</td>";

echo "</tr>";
echo "</table>";


if ($relatorio == "gerar") {

	$sql = "select 	count(tbl_os.codigo_fabricacao) AS ocorrencia,
					tbl_os.codigo_fabricacao
			from 	tbl_os
			JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
			where 	tbl_os.fabrica = $login_fabrica
			and 	tbl_os.data_digitacao:: date  between '$xdata_i' and '$xdata_f' ";

    if (strlen($linha) > 0) $sql .= "AND tbl_produto.linha = '$linha' ";
	if (strlen($marca) > 0) $sql .= "AND tbl_produto.marca = '$marca' ";

	if (strlen($estado) > 0) $sql .= "AND tbl_os.consumidor_estado = '$estado' ";

	$sql .= "GROUP BY tbl_os.codigo_fabricacao ";

	
	if (trim($ordem) == "produto") 	$sql .= "ORDER BY tbl_os.codigo_fabricacao ASC";
	else							$sql .= "ORDER BY ocorrencia DESC";

	$res = pg_exec ($con,$sql);
	
//if (getenv("REMOTE_ADDR") == "201.0.9.216") { echo nl2br($sql)."<br><BR><BR>".pg_numrows($res)."<br><br>"; exit; }
	
	if (pg_numrows($res) > 0) {

echo '<table width="700" border="0" cellpadding="2" cellspacing="1" align="center" class="tabela">';
echo '<tr class="titulo_coluna">';
echo '	<td >';
echo '		Código';
echo '	</td>';
echo '	<td >';
echo '		Quantidade';
echo '	</td>';
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$ocorrencia			= pg_result($res,$x,ocorrencia);
			$codigo_fabricacao 	= pg_result($res,$x,codigo_fabricacao);



			$cor = '#F7F5F0';
			
			if ($x % 2 == 0) $cor = '#F1F4FA';
			
			echo "<tr bgcolor='$cor'>";
			
			echo "<td align='left' nowrap>";
			echo $codigo_fabricacao;
			echo "</td>";
			
			echo "<td align='left' nowrap>";;
			echo $ocorrencia;
			echo "</td>";

			echo "</tr>";
			
			
		}
		echo "</table>";
		
	}

	else{
		echo "<center>Nenhum Resultado Encontrado para esta Pesquisa</center>";
	}
}


echo "<p>";

if (strlen($meu_grafico) > 0) {
	echo $meu_grafico;
}

echo "<p>";

include 'rodape.php';
?>

<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$msg_erro = "";
if($_GET['btn_confirma']){
	$mes = $_GET['mes'];
	$ano = $_GET['ano'];

	if(strlen($mes)==0 OR strlen($ano)==0){
		$msg_erro = "Informe o mês e o ano para pesquisar.";
	}
}

$layout_menu = "gerencia";
$title = "RELATÓRIO DE TEMPO DE PERMANÊNCIA EM CONSERTO";

include "cabecalho.php";

?>

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

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.espaco td{
	padding:10px 0 10px;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

</style>

<SCRIPT LANGUAGE="JavaScript">
function fnc_pesquisa_produto2 (campo, campo2, tipo, voltagem) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.referencia   = campo;
		janela.descricao    = campo2;
		if (voltagem != "") {
			janela.voltagem = voltagem;
		}
		janela.focus();
	}

	else{
		alert("Preencha toda ou parte da informação para efetuar a pesquisa!");
	}
}
</SCRIPT>

<table width='700'align='center' border='0' cellspacing='1' cellpadding='0' class='formulario'>
<form name='frm_percentual' method='get' action='<? echo $PHP_SELF ?>'>
<? if(strlen($msg_erro)>0){ ?>
	<tr bgcolor='#ff0000' style='font:bold 16px Arial; color:#ffffff;'>
		<td align='center' colspan='5'><? echo $msg_erro; ?></td>
	</tr>
<? } ?>
<tr bgcolor='#596D9B' style='font:bold 14px Arial; color:#ffffff;'>
	<td align='center' colspan='5'>Parâmetros de Pesquisa</td>
</tr>
<tr>
	<td style="width:5%;">&nbsp;</td>
	<td align='left'>
		Mês<br />
		<?
		/*--------------------------------------------------------------------------------
		selectMesSimples()
		Cria ComboBox com meses de 1 a 12
		--------------------------------------------------------------------------------*/
		function selectMesSimples($selectedMes){
			for($dtMes=1; $dtMes <= 12; $dtMes++){
				$dtMesTrue = ($dtMes < 10) ? "0".$dtMes : $dtMes;

				echo "<option value=$dtMesTrue ";
				if ($selectedMes == $dtMesTrue) echo "selected";
				echo ">$dtMesTrue</option>\n";
			}
		}
		?>
		<select name='mes' class='frm'>
			<option value=''></option>
			<? selectMesSimples($mes); ?>
		</select>
	</td>
	<td>
		Ano<br />
		<?
		function selectAnoSimples($ant,$pos,$dif=0,$selectedAno)
		{
			$startAno = date("Y"); // ano atual
			for($dtAno = $startAno - $ant; $dtAno <= $startAno + ($pos - $dif); $dtAno++){
				echo "<option value=$dtAno ";
				if ($selectedAno == $dtAno) echo "selected";
				echo ">$dtAno</option>\n";
			}
		}
		?>
		<select name='ano' class="frm">
			<option value=''></option>
			<? selectAnoSimples(1,0,'',$ano) ?>
		</select>
	</td>
	<?if ($login_fabrica==20){?>
	<td>
		País<br />
		<select name='pais' size='1' class="frm">
			<option value=''   <?  if ($pais == '') echo ' selected ' ?> ></option>
			<option value='BR' <? if ($pais == 'BR') echo ' selected ' ?> >Brasil</option>
			<option value='AR' <? if ($pais == 'AR') echo ' selected ' ?> >Argentina</option>
		</select>
	</td>
	<?}?>
	<td align='left'>
		Estado<br />
		<select name="estado" size="1" class="frm">
			<option value=""   <? if (strlen($estado) == 0)    echo " selected "; ?>>TODOS OS ESTADOS</option>
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
</tr>
<tr><td>&nbsp;</td>
	<td align='left'>Família</td>
	<td align='left'>Referência</td>
	<td align='left' colspan="2">Descrição</td>
</tr>
<tr><td>&nbsp;</td>
	<td>
		<?
		$sqlf = "SELECT  *
				FROM    tbl_familia
				WHERE   tbl_familia.fabrica = $login_fabrica
				ORDER BY tbl_familia.descricao;";
		$resf = pg_exec ($con,$sqlf);

		if (pg_numrows($resf) > 0) {
			echo "<select class='frm' style='width:auto;' name='familia'>\n";
			echo "<option value=''>ESCOLHA</option>\n";

			for ($x = 0 ; $x < pg_numrows($resf) ; $x++){
				$aux_familia = trim(pg_result($resf,$x,familia));
				$aux_descricao  = trim(pg_result($resf,$x,descricao));

				echo "<option value='$aux_familia'"; if ($familia == $aux_familia) echo " SELECTED "; echo ">$aux_descricao</option>\n";
			}
			echo "</select>\n";
		}
		?>
		</td>
		<td nowrap>
		<input class="frm" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto2 (document.frm_percentual.produto_referencia,document.frm_percentual.produto_descricao,'referencia')">
		</td>
		<td nowrap colspan="2">
		<input class="frm" type="text" name="produto_descricao" size="30" value="<? echo $produto_descricao ?>">&nbsp;<img
		src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript:
		fnc_pesquisa_produto2 (document.frm_percentual.produto_referencia,document.frm_percentual.produto_descricao,'descricao')"></A>
		</td>
</tr>
<tr><td colspan='4'>&nbsp;</td></tr>
<tr>
		<td colspan="5" align="center">
			<input type='hidden' name='btn_confirma' value='confirma'>
			<input type='button' style="background:url('imagens_admin/btn_confirmar.gif'); cursor:pointer; width:95px;height:20px;" onclick='javascript: frm_percentual.submit();' />
		</td>
</tr>
<tr><td colspan='4'>&nbsp;</td></tr>
</form>
</table>

<br>

<?
if (strlen($mes) > 0 AND strlen($ano) > 0){
	$pais = $_GET["pais"];
	$col = $mes+2;
	echo "<table align='center' border='0' cellspacing='1' cellpadding='0' width='700' class='tabela'>";
	echo "<tr class='titulo_coluna'>\n";
	echo "<td align='center' colspan=".$col.">Quantidade média de dias que o produto permanceu no PA</td>";
	echo "</tr>";
	$nomemes = array(1=> "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

	echo "<tr class='titulo_coluna'>\n";
	echo "<td >#</td>";
	for ($i=1; $i <= $mes; $i++){
		echo "<td>$nomemes[$i]</td>";
	}
	echo "<td>Média/$ano</td>";
	echo "</tr>";

	echo "<tr class='table_line'>\n";
	echo "<td><a href='relatorio_tempo_conserto_postos.php?mes=$mes&ano=$ano&estado=$estado&pais=$pais&familia=$familia&produto_referencia=$produto_referencia'>POSTOS</a></td>";
	for ($i=1; $i <= $mes; $i++){
		if ($i < 10)
			$iMes = "0" .intval($i);
		else
			$iMes = intval($i);

		$sql = "SELECT fn_dias_mes('$ano-$iMes-01',0)";
		$res3 = pg_exec($con,$sql);
		$data_inicial = pg_result($res3,0,0);

		$sql = "SELECT fn_dias_mes('$ano-$iMes-01',1)";
		$res3 = pg_exec($con,$sql);
		$data_final = pg_result($res3,0,0);
/*
		$sql = "SELECT	count(*) AS total                                       ,
				SUM((data_fechamento - data_abertura)) AS data_diferenca
			FROM    tbl_os
			WHERE   fabrica = $login_fabrica
			AND     data_abertura BETWEEN '$data_inicial' AND '$data_final'
			AND     data_fechamento NOTNULL ";

		$sql = "SELECT	count(tbl_os.*) AS total,
				SUM((tbl_os.data_fechamento - tbl_os.data_abertura)) AS data_diferenca
			FROM	tbl_os
			WHERE   tbl_os.fabrica = $login_fabrica
			AND     tbl_os.data_abertura BETWEEN '$data_inicial' AND '$data_final'
			AND     tbl_os.data_fechamento NOTNULL
			AND     tbl_os.finalizada      NOTNULL
			AND     tbl_os.excluida IS NOT TRUE ";

		if(strlen($_GET["pais"]) > 0 AND $login_fabrica==20){
			$estado = trim($_GET["pais"]);
			$sql .= "AND tbl_os.posto IN (SELECT posto FROM tbl_posto JOIN tbl_posto_fabrica USING(posto) WHERE tbl_posto.pais = '$pais' and fabrica=$login_fabrica)";
		}
		if(strlen($_GET["estado"]) > 0){
			$estado = trim($_GET["estado"]);
			$sql .= "AND tbl_os.posto IN (SELECT posto FROM tbl_posto JOIN tbl_posto_fabrica USING(posto) WHERE tbl_posto.estado = '$estado' and fabrica=$login_fabrica)";
		}
*/
		$sql = "SELECT	os,data_abertura,data_fechamento
				INTO TEMP temp_rtc_$iMes
			FROM	tbl_os
			WHERE   tbl_os.fabrica = $login_fabrica
			AND     tbl_os.data_abertura BETWEEN '$data_inicial' AND '$data_final'
			AND     tbl_os.data_fechamento NOTNULL
			AND     tbl_os.finalizada      NOTNULL
			AND     tbl_os.excluida IS NOT TRUE ";

		if(strlen($_GET["pais"]) > 0 AND $login_fabrica==20){
			$estado = trim($_GET["pais"]);
			$sql .= "AND tbl_os.posto IN (SELECT posto FROM tbl_posto JOIN tbl_posto_fabrica USING(posto) WHERE tbl_posto.pais = '$pais' and fabrica=$login_fabrica)";
		}
		if(strlen($_GET["estado"]) > 0){
			$estado = trim($_GET["estado"]);
			$sql .= "AND tbl_os.posto IN (SELECT posto FROM tbl_posto JOIN tbl_posto_fabrica USING(posto) WHERE tbl_posto.estado = '$estado' and fabrica=$login_fabrica)";
		}
		if(strlen($_GET["familia"]) > 0 or strlen($_GET["produto_referencia"]) > 0){

			if(strlen($_GET["familia"]) > 0){
				$familia = trim($_GET["familia"]);
				$join   ="JOIN tbl_familia USING(familia)";
				$cond1  ="tbl_produto.familia  = '$familia' and fabrica=$login_fabrica";
			}else{
				$cond1 = "1=1";
			}

			if(strlen($_GET["produto_referencia"]) > 0){
				$produto_referencia = trim($_GET["produto_referencia"]);
				$cond2  ="tbl_produto.referencia   = '$produto_referencia'";
			}else{
				$cond2 = "1=1";
			}

			$sql .= "AND tbl_os.produto IN ( SELECT produto FROM tbl_produto $join WHERE $cond1 AND $cond2)";
		}

		if ($login_fabrica == 30) {
			$sql.= "; ALTER TABLE temp_rtc_$iMes ADD dias float;";
			$sql.= "update temp_rtc_$iMes set dias = case when data_fechamento = data_abertura then 0 else (select count(1) from fn_calendario(data_abertura,data_fechamento) where nome_dia not in ('Domingo') and data <> data_abertura) end ;";
			$sql.= "SELECT count(os) AS total, sum(dias) AS data_diferenca FROM temp_rtc_$iMes;";
		} else {
			$sql .= ";SELECT count(os) AS total,
					SUM((data_fechamento - data_abertura)) AS data_diferenca
				FROM temp_rtc_$iMes;";
		}

		$res2 = pg_exec($con,$sql);


		//echo "Total: ".pg_result($res2,0,0) ." || Diferenca: ". pg_result($res2,0,1)."<br>";

		$total_ocorrencias += @pg_result($res2,0,0);
		$total_diferenca   += @pg_result($res2,0,1);

		if (@pg_numrows($res2) > 0) {
			### monta linha de nome dos produtos
			echo "<td align='center'>";
			if (@pg_result($res2,0,0) > 0)
				$total_sem_formatacao = pg_result($res2,0,1) / pg_result($res2,0,0);
				$numero_formatado = number_format($total_sem_formatacao,2,'.','');
				echo $numero_formatado;
				//echo round(@pg_result($res2,0,1) / @pg_result($res2,0,0));
				flush();
			echo "</td>\n";
		}
		flush();
	}

	if ($total_ocorrencias > 0) $total = $total_diferenca / $total_ocorrencias;

	echo "<td align='center'>";
	$total = number_format($total,2,'.','');
	echo $total;
	//echo round($total);
	echo "</td>";
	echo "</tr>\n";

	echo "</table>";
}

include "rodape.php";

?>

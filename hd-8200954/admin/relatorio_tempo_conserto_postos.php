<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$msg_erro = "";

$layout_menu = "gerencia";
$title = "Relatório de Tempo de Permanência em Conserto / Posto";

include "cabecalho.php";

?>

<p>

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
.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
}
table.tabela{
empty-cells:show;
border-spacing: 1px;
border:1px solid #596d9b;
}

table input {
    background-color: #F0F0F0;
    border-color: #888888;
    border-right: 1px solid #888888;
    border-style: solid;
    border-width: 1px;
    font-family: Verdana;
    font-size: 8pt;
    font-weight: bold;
}

select{
    background-color: #F0F0F0;
    border-color: #888888;
    border-right: 1px solid #888888;
    border-style: solid;
    border-width: 1px;
    font-family: Verdana;
    font-size: 8pt;
    font-weight: bold;


}
</style>
<!--[if lt IE 8]>
<style>
table.tabela{
empty-cells:show;
border-collapse:collapse;

}
</style>
<![endif]-->

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
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia   = campo;
		janela.descricao    = campo2;
		if (voltagem != "") {
			janela.voltagem = voltagem;
		}
		janela.focus();
	}
	else
		alert('Preencha toda ou parte da informação para realizar a pesquisa!');
}
</SCRIPT>
<table align='center' border='0' cellspacing='1' cellpadding='0' width='700' class="formulario">
<form name='frm_percentual' action='<? echo $PHP_SELF ?>' method='get'>
<tr><td class="titulo_tabela" colspan="4">Parâmetros de Pesquisa</td></tr>
<tr>
	<td width="14%">&nbsp;</td>
	<td width="180px">
		Selecione o Mês<br />
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
		<select name='mes'>
			<option value=''></option>
			<? selectMesSimples($mes); ?>
		</select>
	</td>
	<td>
		Selecione o Ano<br />
		<?
		/*--------------------------------------------------------------------------------
		selectAnoSimples($ant,$pos,$dif,$selectedAno)
		// $ant = qtdade de anos retroceder
		// $pos = qtdade de anos posteriores
		// $dif = ve qdo ano termina
		// $selectedAno = ano já setado
		Cria ComboBox com Anos
		--------------------------------------------------------------------------------*/
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
				<select name='ano'>
					<option value=''></option>
		<? selectAnoSimples(1,0,'',$ano) ?>
				</select>
	</td>
	<?if ($login_fabrica==20){?>
</tr>
<tr>
	<td>&nbsp</td>
	<td>
		País<br />
		<select name='pais' size='1'>
			<option value=''   <?  if ($pais == '') echo ' selected ' ?> ></option>
			<option value='BR' <? if ($pais == 'BR') echo ' selected ' ?> >Brasil</option>
			<option value='AR' <? if ($pais == 'AR') echo ' selected ' ?> >Argentina</option>
		</select>
	</td>
	<?}?>
	<td>
		Estado<br />
		<select name="estado" size="1">
			<option value=""   <? if (strlen($estado) == 0)    echo " selected "; ?>>TODOS OS ESTADOS</option>
			<option value="centro-oeste" <? if ($estado == "centro-oeste") echo " selected "; ?>>Região Centro-Oeste (GO,MT,MS,DF)</option>
			<option value="nordeste"     <? if ($estado == "nordeste")     echo " selected "; ?>>Região Nordeste (MA,PI,CE,RN,PB,PE,AL,SE,BA)</option>
			<option value="norte"        <? if ($estado == "norte")        echo " selected "; ?>>Região Norte (AC,AM,RR,RO,PA,AP,TO)</option>
			<option value="sudeste"      <? if ($estado == "sudeste")      echo " selected "; ?>>Região Sudeste (MG,ES,RJ,SP)</option>
			<option value="sul"          <? if ($estado == "sul")          echo " selected "; ?>>Região Sul (PR,SC,RS)</option>
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

<tr>
	<td>&nbsp</td>
	<td>
		Família<br />
		<?
		$sqlf = "SELECT  *
				FROM    tbl_familia
				WHERE   tbl_familia.fabrica = $login_fabrica
				ORDER BY tbl_familia.descricao;";
		$resf = pg_exec ($con,$sqlf);

		if (pg_numrows($resf) > 0) {
			echo "<select style='width:auto;' name='familia'>\n";
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
					Referência<br />
					<input  type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto2 (document.frm_percentual.produto_referencia,document.frm_percentual.produto_descricao,'referencia')">
				</td>
				<td nowrap>
					Descrição<br />
					<input  type="text" name="produto_descricao" size="30" value="<? echo $produto_descricao ?>">&nbsp;<img
					src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript:
					fnc_pesquisa_produto2 (document.frm_percentual.produto_referencia,document.frm_percentual.produto_descricao,'descricao')">
	</td>
</tr>
<tr>
	<td colspan="4" align="center"><input type="button" onclick='javascript: submit();' style='cursor:pointer' value="Pesquisar" /></td>
</tr>
</form>
</table>

<br>

<?
$mes    = trim($_GET['mes']);
$ano    = trim($_GET['ano']);
$estado = trim($_GET['estado']);
$pais = trim($_GET['pais']);


if (strlen($mes) > 0 AND strlen($ano) > 0){

	if($login_fabrica == 163){
		$join_163 = " JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica} ";
		$cond_163 = " AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE ";
	}

	echo "<table align='center' border='0' cellspacing='1' cellpadding='0' class='tabela'>";

	$nomemes = array(1=> "JAN", "FEV", "MAR", "ABR", "MAI", "JUN", "JUL", "AGO", "SET", "OUT", "NOV", "DEZ");

	echo "<tr class='titulo_coluna'>\n";
	echo '<td>Código</td>',
		 '<td align="left">Nome do Posto</td>';
	for ($x=1; $x <= $mes; $x++){
		echo "<td>Ocorrências</td>";
		echo "<td>$nomemes[$x]</td>";
	}
	echo "<td>MÉDIA</td>";
	echo "</tr>";

	// seleciona os postos
	$sql = "SELECT  tbl_posto.posto,
			tbl_posto.nome ,
			tbl_posto.estado,
			tbl_posto_fabrica.codigo_posto,
			tbl_os.os,
			tbl_os.data_fechamento,
			tbl_os.data_abertura,
			tbl_os.finalizada,
			tbl_os.fabrica,
			tbl_os.excluida,
			tbl_os.produto
		INTO TEMP tmp_posto_os_$login_admin
		FROM	tbl_posto
		JOIN    tbl_os on tbl_posto.posto = tbl_os.posto and tbl_os.fabrica = $login_fabrica
		JOIN	tbl_posto_fabrica ON tbl_posto_fabrica.posto=tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		$join_163
		WHERE	tbl_os.data_abertura between '$ano-01-01' AND '$ano-12-31'
			AND tbl_os.data_fechamento NOTNULL
			AND tbl_os.finalizada      NOTNULL
			AND tbl_os.excluida        IS NOT TRUE
			$cond_163";
	if($estado == "centro-oeste") $sql .= " AND tbl_posto.estado in ('GO','MT','MS','DF') ";
	if($estado == "nordeste")     $sql .= " AND tbl_posto.estado in ('MA','PI','CE','RN','PB','PE','AL','SE','BA') ";
	if($estado == "norte")        $sql .= " AND tbl_posto.estado in ('AC','AM','RR','RO','PA','AP','TO') ";
	if($estado == "sudeste")      $sql .= " AND tbl_posto.estado in ('MG','ES','RJ','SP') ";
	if($estado == "sul")          $sql .= " AND tbl_posto.estado in ('PR','SC','RS') ";
	if(strlen($estado) == 2)      $sql .= " AND tbl_posto.estado = '$estado' ";
	if(strlen($pais) > 0)         $sql .= " AND tbl_posto.pais = '$pais'";

	$sql .= "; CREATE INDEX  tmp_posto_os_fabrica_posto_$login_admin ON  tmp_posto_os_$login_admin(fabrica,posto); ";


	if ($login_fabrica == 30) {
		$sql.= "; ALTER TABLE tmp_posto_os_$login_admin ADD dias float;";
		$sql.= "update tmp_posto_os_$login_admin set dias = case when data_fechamento = data_abertura then 0 else (select count(1) from fn_calendario(data_abertura,data_fechamento) where nome_dia not in ('Domingo') and data <> data_abertura) end ;";
	}

	$sql.= "

		SELECT DISTINCT tmp_posto_os_$login_admin.posto,
				tmp_posto_os_$login_admin.nome,
				tmp_posto_os_$login_admin.estado,
				tmp_posto_os_$login_admin.codigo_posto
		FROM   tmp_posto_os_$login_admin
		ORDER BY tmp_posto_os_$login_admin.nome ASC; ";
//	echo $sql; exit;
	$resX = @pg_exec($con,$sql);

	#echo nl2br($sql);

	$media_geral = 0;

	for ($z=0; $z < @pg_numrows($resX) ; $z++){

		$posto = @pg_result($resX,$z,0);
		$nome  = @pg_result($resX,$z,1);
		$estado= @pg_result($resX,$z,2);
		$codigo_posto= @pg_result($resX,$z,3);

//		echo "<br>{ [ $z ] - $posto - $nome - $estado }<br>";

		$cor = ($z % 2 == 0) ? "#F7F5F0" : "#F1F4FA";
		echo "<tr class='table_line' bgcolor='$cor'>\n";
		echo "<td align='left'><a href='relatorio_tempo_conserto_os.php?posto=$posto&mes=$mes&ano=$ano&estado=$estado&pais=$pais&familia=$familia&produto_referencia=$produto_referencia'>$codigo_posto</td><td align='left'><a href='relatorio_tempo_conserto_os.php?posto=$posto&mes=$mes&ano=$ano&estado=$estado&pais=$pais&familia=$familia&produto_referencia=$produto_referencia'>$nome</td>\n";

		$total_diferenca   = 0;
		$total_ocorrencias = 0;
		$exibetotal        = 0; // valor total ($total_diferenca/$total_ocorrencias)
		$total             = 0; // valor acumulado de $total
		$divide            = 0; // valor de meses a ser dividido o valor de $exibetotal
		$media             = 0;

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

			if ($login_fabrica == 30) {
				$sql = "SELECT count(os) AS total,
								sum(dias) AS data_diferenca
						FROM	tmp_posto_os_$login_admin
						WHERE	tmp_posto_os_$login_admin.fabrica = $login_fabrica
						AND	tmp_posto_os_$login_admin.posto   = $posto
						AND	tmp_posto_os_$login_admin.data_abertura   BETWEEN '$data_inicial' AND '$data_final'
						AND	tmp_posto_os_$login_admin.data_fechamento NOTNULL
						AND	tmp_posto_os_$login_admin.finalizada      NOTNULL
						AND	tmp_posto_os_$login_admin.excluida        IS NOT TRUE";
			} else {

				$sql = "SELECT	count(tmp_posto_os_$login_admin.*) AS total,
						SUM((tmp_posto_os_$login_admin.data_fechamento - tmp_posto_os_$login_admin.data_abertura)) AS data_diferenca
						FROM	tmp_posto_os_$login_admin
						WHERE	tmp_posto_os_$login_admin.fabrica = $login_fabrica
						AND	tmp_posto_os_$login_admin.posto   = $posto
						AND	tmp_posto_os_$login_admin.data_abertura   BETWEEN '$data_inicial' AND '$data_final'
						AND	tmp_posto_os_$login_admin.data_fechamento NOTNULL
						AND	tmp_posto_os_$login_admin.finalizada      NOTNULL
						AND	tmp_posto_os_$login_admin.excluida        IS NOT TRUE ";
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

			$sql .= "AND tmp_posto_os_$login_admin.produto IN ( SELECT produto FROM tbl_produto $join WHERE tbl_produto.fabrica_i=$login_fabrica AND $cond1 AND $cond2)";
		}

			#echo nl2br($sql);
			$res2 = pg_exec($con,$sql);

#	echo $sql."<br>";
#	echo "{ $i } Total: ".pg_result($res2,0,0) ." || Diferenca: ". pg_result($res2,0,1)."<br><br>";

			if (pg_numrows($res2) > 0) {

				$total_ocorrencias = pg_result($res2,0,total);
				$total_diferenca   = pg_result($res2,0,data_diferenca);

				$xtotal_ocorrencias += $total_ocorrencias;
				$xtotal_diferenca   += $total_diferenca;

				echo "<td align='center'>";
				if ($total_ocorrencias > 0) echo $total_ocorrencias;
				echo "</td>";

				echo "<td align='right'>";
				if ($total_ocorrencias > 0) {
					$exibetotal = ($total_diferenca / $total_ocorrencias);
					$exibetotalX = number_format($exibetotal,2,'.','');
					echo $exibetotalX;
					//echo round($exibetotal, 2);
					$total += $exibetotal;
					$divide++;
					if ($i == $mes) $divide_geral++;

				}
				echo "</td>\n";
			}

		}

		echo "<td align='right'>";
		if ($divide > 0){
			$media = ($total / $divide);
			$media_geral += $media;
		}

		$mediaX = number_format($media,2,'.','');
		echo $mediaX;
		//echo round($media, 2);
		echo "</td>";

		echo "</tr>\n";
	}

	echo "</table>";

	echo "<table>";
	echo "<tr>";

	if (strlen($divide_geral) > 0) $exibe_geral = $xtotal_diferenca / $xtotal_ocorrencias;
	else                           $exibe_geral = 0;

	echo "<td align='center' style='padding-right:10'>Média dos Postos: <b>";
	$exibe_geral = number_format($exibe_geral,2,'.','');
	echo $exibe_geral;
	echo "</b></td>";
	echo "</tr>";
	echo "</table>";

}

echo "<br><br>";

include "rodape.php";

?>

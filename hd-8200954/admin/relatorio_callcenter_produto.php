<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';


$msg_erro = "";

if (strlen($POST["btn_acao"]) > 0) $btn_acao = $_POST["btn_acao"];

$layout_menu = "gerencia";
$title = "CALL-CENTER - RELATÓRIO DE PRODUTOS";

include "cabecalho.php";

function RemoveAcentos($Msg) 
{ 
  $a = array( 
            '/[ÂÀÁÄÃ]/'=>'A', 
            '/[âãàáä]/'=>'a', 
            '/[ÊÈÉË]/'=>'E', 
            '/[êèéë]/'=>'e', 
            '/[ÎÍÌÏ]/'=>'I', 
            '/[îíìï]/'=>'i', 
            '/[ÔÕÒÓÖ]/'=>'O', 
            '/[ôõòóö]/'=>'o', 
            '/[ÛÙÚÜ]/'=>'U', 
            '/[ûúùü]/'=>'u', 
            '/ç/'=>'c', 
            '/Ç/'=>'C'); 
    // Tira o acento pela chave do array                         
    return preg_replace(array_keys($a), array_values($a), $Msg); 
} 

?>
<script language="javascript">

function informacoes(btn_acao,data_inicial,data_final,anual) {
	var url = "";
        url = "imagem_relatorio_callcenter.php?btn_acao=" + btn_acao + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&anual=" + anual;
        janela = window.open(url,"_blank","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=750,height=600,top=18,left=0");
        janela.focus();
}

function informacoes2(btn_acao,data_inicial,data_final,anual) {
	var url = "";
        url = "imagem_relatorio_callcenter2.php?btn_acao=" + btn_acao + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&anual=" + anual;
        janela = window.open(url,"_blank","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=750,height=600,top=18,left=0");
        janela.focus();
}

</script>


<p>

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

a:link.top{
	color:#ffffff;
}
a:visited.top{
	color:#ffffff;
}
a:hover.top{
	color:#ffffff;
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


<form name='frm_relatorio' action='<? echo $PHP_SELF ?>' method='POST'>
<input type='hidden' name='btn_acao' value=''>
<table width='700' align='center' border='0' cellspacing='1' cellpadding='2' class="formulario">
	<tr class="titulo_tabela"><td colspan="5">Parâmetros de Pesquisa</td></tr>
	<tr><td colspan="5">&nbsp;</td></tr>
	<tr>
		<td width="150">&nbsp;</td>
		<td align="right">Mês</td>
		<td width="100">
		<?
		$meses = array(0 => 'Anual', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro');
		if (strlen ($mes) == 0) $mes = date('m');
		?>
		<select name='mes' size='1' class='frm'>
			<?
			for ($i = 0 ; $i <= 12 ; $i++) {
				echo "<option value='$i'";
				if ($mes == $i) echo " selected";
				echo ">$meses[$i]</option>\n";
			}
			?>
		</select>
		</td>
		<td align="right">Ano</td>
		<td ><input type="text" name='ano' size='13' maxlength="4" class='frm' value='<? echo date('Y'); ?>'></td>
	</tr>
</table>

<table width="700" border="0" cellpadding="2" cellspacing="0" align="center" class='formulario'>
	<tr><td>&nbsp;</td></tr>
	<tr>
		<td align="center" nowrap>
			<input type="button" onclick="javascript: if (document.frm_relatorio.btn_acao.value == '' ) { document.frm_relatorio.btn_acao.value='consultar' ; document.frm_relatorio.submit() } else { alert ('Aguarde submissão') }" value="Consultar">
		</td>
	</tr>
</table>
</form>


<br>

<?
$mes_pesquisa = $_POST["mes"];
$ano_pesquisa = $_POST["ano"];


if (strlen($mes_pesquisa) > 0 AND strlen($ano_pesquisa) == 4 AND $btn_acao == 'consultar' OR $btn_acao == 'consulta_anual') {

	if (strlen($mes_pesquisa) == 1) $mes_pesquisa = "0".$mes_pesquisa;

	$data_inicial = $ano_pesquisa . "-" . $mes_pesquisa. "-01";
	
	$data_final   = date("Y-m-t", mktime(0, 0, 0, $mes_pesquisa, 1, $ano_pesquisa));
	

	$anual = $_POST["ano"];


	$data_inicial_D = substr($data_inicial,0,10);
	$data_final_D   = substr($data_final,0,10);


	$sql_8 = "SELECT  tbl_callcenter.natureza                                      ,
					tbl_produto.nome_comercial                                   ,
					tbl_produto.produto                                          ,
					tbl_defeito_reclamado.descricao         AS defeito_descricao ,
					tbl_defeito_reclamado.defeito_reclamado AS defeito_reclamado ,
					count(*)                                AS qtde              
			INTO TEMP TABLE temp_callcenter_$login_fabrica
			FROM    tbl_callcenter
			LEFT JOIN tbl_produto           ON tbl_callcenter.produto           = tbl_produto.produto
			LEFT JOIN tbl_defeito_reclamado ON tbl_callcenter.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
			WHERE     tbl_callcenter.fabrica = $login_fabrica
			AND tbl_callcenter.excluida IS NOT TRUE ";

//	AND       tbl_callcenter.produto NOT IN('8059','8042','11159','1027','1042','1039','1056')

	if($mes_pesquisa == 0){
		$sql_8 .= " AND       tbl_callcenter.data BETWEEN '$anual-01-01 00:00:00'  AND '$anual-12-31 23:59:59' ";
	}else{
		$anual = "0";
		$sql_8 .= " AND       tbl_callcenter.data BETWEEN '$data_inicial 00:00:00'  AND '$data_final 23:59:59' ";
	}
	$sql_8 .= " GROUP BY  tbl_callcenter.natureza, tbl_defeito_reclamado.descricao, tbl_produto.nome_comercial, tbl_produto.produto, tbl_defeito_reclamado.defeito_reclamado
			ORDER BY  tbl_callcenter.natureza, tbl_defeito_reclamado.descricao, tbl_produto.nome_comercial; ";
//echo "$sql_8";
//exit;
	$res = pg_exec ($con,$sql_8);

if(strlen($anual) == 0) $anual =0;

	$sql = "UPDATE temp_callcenter_$login_fabrica SET defeito_descricao = '<b>SEM DEFEITO LANÇADO</b>' WHERE defeito_descricao IS NULL; ";
	$resX = pg_exec ($con,$sql);

	$sql = "UPDATE temp_callcenter_$login_fabrica SET defeito_descricao = '<b>SEM DEFEITO LANÇADO</b>' WHERE LENGTH (TRIM (defeito_descricao)) = 0;";
	$resX = pg_exec ($con,$sql);

	$sql = "UPDATE temp_callcenter_$login_fabrica SET nome_comercial = 'XXX' WHERE LENGTH (TRIM (nome_comercial)) = 0;";
	$resX = pg_exec($con,$sql);
//
	$sql = "UPDATE temp_callcenter_$login_fabrica SET nome_comercial = 'FORA DE LINHA' WHERE produto in('8059','8042','11159','1027','1042','1039','1056','8040','1043','1064','7494');";
	$resX = pg_exec($con,$sql);

	$sql = "UPDATE temp_callcenter_$login_fabrica SET nome_comercial = 'XXX' WHERE nome_comercial is null; ";
	$resX = pg_exec($con,$sql);

	$sql2 = "SELECT distinct nome_comercial FROM temp_callcenter_$login_fabrica ; ";
	$res2 = pg_exec($con,$sql2);
	for($i=0; $i<pg_numrows($res2); $i++){
		$nome_comercial[$i] = pg_result($res2,$i,nome_comercial);
	}

	$total_email = 0;//contagem de e-mails

	$sqlM = "SELECT distinct natureza FROM temp_callcenter_$login_fabrica ; ";
	$resM = pg_exec($con,$sqlM);
	for($i=0; $i<pg_numrows($resM); $i++){
		$natureza[$i] = pg_result($resM,$i,natureza);
	}


if(pg_numrows($resM) > 0){
	//GERAÇÂO DO GRÁFICO E TABELA COM TOTAIS.
	echo "<p style='font-size: 14px'>";
	echo "<a href= \"javascript: informacoes('$btn_acao','$data_inicial','$data_final','$anual')\">Gerar Relatório (Gráfico)</a>&nbsp;&nbsp;<br>";
	echo "<a href= \"javascript: informacoes2('$btn_acao','$data_inicial','$data_final','$anual')\">Gerar Relatório (Informação x Reclamação)</a>&nbsp;&nbsp;";
	echo "</p>";

	//FIM
}else{
	echo "<p style='font-size: 14px'>";
	echo "Nenhum resultado encontrado.";
	echo "</p>";
}


	for($m=0; $m<pg_numrows($resM); $m++){
	//CABEÇALHO=============================================
		echo "<br>";
		echo "<table border= '1' class='tabela' align='center'>";
		echo "<tr class='titulo_tabela'>";
			$colspan = pg_numrows($res2) + 1;
			echo "<td colspan='$colspan' align='left' style='font-size:16px'><b>$natureza[$m]</b><td>";
		echo "</tr>";
		for($i=0; $i<1; $i++){
			echo "<tr class='titulo_coluna'>";
				echo "<td width='250' nowrap>Descrição</td>";
				for($x=0; $x<pg_numrows($res2);$x++){
					if($nome_comercial[$x] == "XXX") echo "<td>Outros</td>";
					else echo "<td nowrap>$nome_comercial[$x]</td>";
				}
			echo "</tr>";
		}
		$sql3 = "SELECT distinct defeito_descricao FROM temp_callcenter_$login_fabrica WHERE natureza = '$natureza[$m]' ORDER BY defeito_descricao;";
		$res3 = pg_exec($con,$sql3);
		
		for($z=0;$z<pg_numrows($res3);$z++){
			$cor = ($z % 2 == 0) ? "#F7F5F0": "#F1F4FA";
			echo "<tr bgcolor='$cor'>";
			$defeito_descricao[$z] = pg_result($res3,$z,defeito_descricao);
			echo "<td nowrap align='left' width='250' bgcolor='#D5DAE1'>$defeito_descricao[$z]</td>";
			for($i=0;$i<pg_numrows($res2);$i++){
				$sql4 = "SELECT sum(qtde) AS qtde, defeito_reclamado, natureza, produto, nome_comercial FROM temp_callcenter_$login_fabrica WHERE defeito_descricao = '$defeito_descricao[$z]' AND natureza = '$natureza[$m]' AND nome_comercial = '$nome_comercial[$i]' group by defeito_reclamado, natureza, produto, nome_comercial ; ";
				$res4 = pg_exec($con,$sql4);
				
				if(pg_numrows($res4) > 1){
					for($s=0;$s<pg_numrows($res4);$s++) {
						$qtde_0 = pg_result($res4,$s,qtde);
						$qtde = $qtde_0 + $qtde;
					}
				}else{ $qtde = @pg_result($res4,0,qtde); }

				$produto = @pg_result($res4,0,produto);
				$nome_comercial_0 = @pg_result($res4, 0, nome_comercial);
				$defeito_reclamado_0 = @pg_result($res4, 0, defeito_reclamado);

				if($nome_comercial_0 == "XXX") $outro = "outro";
				else $outro = "0";
				
				echo "<td>";
				if(pg_numrows($res4)>0 ){
					if(strlen($anual) == 0) $anual = 0;

					echo "<a href='relatorio_callcenter_produto_observacao.php?produto=$produto&natureza=$natureza[$m]&nome_comercial=$nome_comercial[$i]&defeito_descricao=$defeito_descricao[$z]&data_inicial=$data_inicial&data_final=$data_final&outro=$outro&defeito_reclamado=$defeito_reclamado_0&anual=$anual' target='_blank'>$qtde</a>";

					$qtde_natureza = $qtde_natureza + $qtde;
					if($natureza[$m] == "Email") $total_email = $total_email + $qtde;
					$qtde = "0";
				}else{ 
					echo "-";
				}
				$produto = '';
				echo "</td>";
			}
			echo "</tr>";
		}
		echo "<tr bgcolor='#6699FF' style='font-size:10px ; font-color:#ffffff '>";
			echo "<td colspan='$colspan' align='left' style='color:#ffffff ; font-weight:bold ; font-size:16px'> Total de Chamados: $qtde_natureza</td>";
		echo "</tr>";
		echo "</table>";
		$total_geral = $qtde_natureza + $total_geral;
		$qtde_natureza = "0";
	}
	$total_geral = $total_geral - $total_email;
	if($total_geral>0){
		echo "<br><table border = '1'>";
		echo "<tr bgcolor='#6699FF' style='font-size:10px ; font-color:#ffffff '>";
			echo "<td colspan='$colspan' align='left' style='color:#ffffff ; font-weight:bold ; font-size:16px'>TOTAL GERAL DE CHAMADAS (exceto $total_email email(s)): $total_geral</td>";
		echo "</tr>";
		echo "</table>";
	}

}

 include "rodape.php"; ?>
<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';

$msg_erro = "";

if (strlen($POST["btn_acao"]) > 0) $btn_acao = $_POST["btn_acao"];

$layout_menu = "gerencia";
$title = "Call-Center - Relatório de Produtos";

include "cabecalho.php";

?>

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

.table_line {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}

</style>

<br>

<form name='frm_relatorio' action='<? echo $PHP_SELF ?>' method='POST'>
<input type='hidden' name='btn_acao' value=''>
<table width='150' align='center' border='0' cellspacing='1' cellpadding='2'>
	<tr>
		<td class='menu_top'>Mês</td>
		<td class='table_line' bgcolor='#F1F4FA'>
		<?
		$meses = array(1 => 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro');
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
	</tr>
	<tr>
		<td class='menu_top'>Ano</td>
		<td class='table_line' bgcolor='#F1F4FA'><input type="text" name='ano' size='13' maxlength="4" class='frm' value='<? echo date('Y'); ?>'></td>
	</tr>
</table>
<br>
<table width="120" border="0" cellpadding="2" cellspacing="0" align="center">
	<tr>
		<td bgcolor="#F0F0F0" align="center" nowrap onmouseover="this.style.backgroundColor='#BBBBBB';this.style.cursor='pointer'" onmouseout="this.style.backgroundColor='#F0F0F0';this.style.cursor='normal'">
			<a href="javascript: if (document.frm_relatorio.btn_acao.value == '' ) { document.frm_relatorio.btn_acao.value='consultar' ; document.frm_relatorio.submit() } else { alert ('Aguarde submissão') }" title="Consultar">
			<font face="Verdana, Tahoma, Arial" size="1" color="#000000"><b>CONSULTAR</b></font>
			</a>
		</td>
	</tr>
</table>
</form>

<br>

<?
$mes_pesquisa = $_POST["mes"];
$ano_pesquisa = $_POST["ano"];

if (strlen($mes_pesquisa) > 0 AND strlen($ano_pesquisa) == 4 AND $btn_acao == 'consultar') {

	if (strlen($mes_pesquisa) == 1) $mes_pesquisa = "0".$mes_pesquisa;

	$data_inicial = $ano_pesquisa . "-" . $mes_pesquisa. "-01";
	//$sql = "SELECT '$data_inicial'::date + interval '1 month'";
	//$res = pg_exec ($con,$sql);
	//$data_final = pg_result ($res,0,0);
	
	$data_final   = date("Y-m-t", mktime(0, 0, 0, $mes_pesquisa, 1, $ano_pesquisa));
	
	$data_inicial_D = substr($data_inicial,0,10);
	$data_final_D   = substr($data_final,0,10);

	$sql = "SELECT  tbl_callcenter.natureza                                      ,
					tbl_produto.nome_comercial                                   ,
					tbl_produto.produto                                          ,
					tbl_defeito_reclamado.descricao         AS defeito_descricao ,
					tbl_defeito_reclamado.defeito_reclamado AS defeito_reclamado ,
					count(*)                                AS qtde              
			INTO TEMP TABLE x_$login_fabrica
			FROM    tbl_callcenter
			LEFT JOIN tbl_produto           ON tbl_callcenter.produto           = tbl_produto.produto
			LEFT JOIN tbl_defeito_reclamado ON tbl_callcenter.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
			WHERE     tbl_callcenter.fabrica = $login_fabrica
			AND       tbl_callcenter.data BETWEEN '$data_inicial 00:00:00'  AND '$data_final 23:59:59' 
			
			GROUP BY  tbl_callcenter.natureza, tbl_defeito_reclamado.descricao, tbl_produto.nome_comercial, tbl_produto.produto, tbl_defeito_reclamado.defeito_reclamado
			ORDER BY  tbl_callcenter.natureza, tbl_defeito_reclamado.descricao, tbl_produto.nome_comercial";
	$res = pg_exec ($con,$sql);
echo "$sql<BR>";
	$sql = "UPDATE x_$login_fabrica SET nome_comercial = 'XXX' WHERE nome_comercial IS NULL";
	$resX = pg_exec ($con,$sql);

	$sql = "UPDATE x_$login_fabrica SET nome_comercial = 'XXX' WHERE LENGTH (TRIM (nome_comercial)) = 0";
	$resX = pg_exec ($con,$sql);

	$sql = "UPDATE x_$login_fabrica SET defeito_descricao = '<b>SEM DEFEITO LANÇADO</b>' WHERE defeito_descricao IS NULL";
	$resX = pg_exec ($con,$sql);

	$sql = "UPDATE x_$login_fabrica SET defeito_descricao = '<b>SEM DEFEITO LANÇADO</b>' WHERE LENGTH (TRIM (defeito_descricao)) = 0";
	$resX = pg_exec ($con,$sql);
echo "<BR>2: $sql";
	$sql = "SELECT  natureza                                           ,
					nome_comercial                                     ,
					produto                                            ,
					defeito_descricao                                  ,
					defeito_reclamado                                  ,
					sum (qtde)              AS qtde
			INTO TEMP TABLE y_$login_fabrica
			FROM    x_$login_fabrica
			GROUP BY  natureza, defeito_descricao, nome_comercial, produto, defeito_reclamado
			ORDER BY  natureza, defeito_descricao, nome_comercial";
	$res = pg_exec ($con,$sql);
echo "<BR>3: $sql";
	$sql = "SELECT COUNT(*) FROM (SELECT DISTINCT nome_comercial FROM y_$login_fabrica) x ";
	$resX = pg_exec ($con,$sql);
	$qtde_produto = pg_result ($resX,0,0);
	$natureza          = "*";
	$defeito_descricao = "*";
	$soma_email = 0;
echo "<BR>4: $sql";
	$sql = "SELECT * FROM y_$login_fabrica";
	$res = pg_exec ($con,$sql);
echo "<BR>5: $sql";
	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
		if ($natureza <> pg_result($res,$i,natureza) ) {
			if (strlen ($natureza) <> "*") {
				$total_chamadas += $soma_qtde;
				
				if ($natureza == 'Email') $soma_email = $soma_qtde ;
				
				echo "<tr bgcolor='#6699FF' style='font-size:10px ; font-color:#ffffff '>";
				
				echo "<td align='left' colspan='$span' style='color:#ffffff ; font-weight:bold ; font-size:16px'>";
				echo "TOTAL DE CHAMADAS: ".$natureza." - ".$soma_qtde;
				echo "</td>";
				
				echo "</tr>";
				echo "</table><br>";
				
				$soma_qtde = 0;
				flush();
			}
			echo "<table border='1' width='650'>";
			echo "<tr bgcolor='#6699FF' style='font-size:10px ; font-color:#ffffff '>";

			$natureza          = pg_result($res,$i,natureza);
			$defeito_descricao = "#";

			$span = $qtde_produto+1;

			echo "<td align='left' colspan='$span' style='color:#ffffff ; font-weight:bold ; font-size:16px'>";
			echo $natureza;
			echo "&nbsp;";
			echo "</td>";

			echo "</tr>";



			$sql = "SELECT nome_comercial FROM (SELECT DISTINCT nome_comercial FROM x_$login_fabrica) x ORDER BY nome_comercial";
//echo $sql; exit;
			$resProd = pg_exec ($con,$sql);

			echo "<tr bgcolor='#ddeeFF' style='font-size:10px ; font-color:#ffffff '>";
			echo "<td align='center' nowrap>";
			echo "Descrição";
			echo "</td>";

			for ($x = 0 ; $x < pg_numrows ($resProd) ; $x++) {
				echo "<td align='center' nowrap>&nbsp;";
				if (pg_result ($resProd,$x,nome_comercial) == "XXX") {
					echo "Outros";
				}else{
					echo pg_result ($resProd,$x,nome_comercial);
				}
				echo "&nbsp;";
				echo "</td>";
			}
		
			echo "</tr>";
		}


		if ($defeito_descricao <> pg_result($res,$i,defeito_descricao) OR strlen ($defeito_descricao) == 0) {
			if ($defeito_descricao <> "*") {
				echo "</tr>";
			}

			$defeito_descricao = pg_result($res,$i,defeito_descricao);

			echo "<tr>";
			echo "<td nowrap align='left' width='250' bgcolor='#D5DAE1'>&nbsp;";
			if (strlen ($defeito_descricao) > 0) {
				echo $defeito_descricao;
			}else{
				echo "<b>Outros Defeitos</b>";
			}
			echo "</td>";
			$ultimo_nome_comercial = "";

		}

		$nome_comercial    = pg_result($res,$i,nome_comercial);
		$qtde              = pg_result($res,$i,qtde);
		$soma_qtde         += $qtde;
		$produto           = pg_result($res,$i,produto);
		echo "<BR> aa: $sql<BR>";
		for ($x = 0 ; $x < pg_numrows ($resProd) ; $x++) {
			if ($nome_comercial < pg_result ($resProd,$x,nome_comercial) ) {
				break;
			}
//if ($ip == '201.0.9.216') echo "$x -> $nome_comercial :: ".pg_result($resProd,$x,nome_comercial)."<br><br>";
			if ($nome_comercial == pg_result ($resProd,$x,nome_comercial) ) {
				$defeito_reclamado = pg_result($res,$i,defeito_reclamado);
				
				echo "<td nowrap>&nbsp;";
				echo "<a href=\"relatorio_callcenter_produto_observacao.php?produto=$produto&defeito_reclamado=$defeito_reclamado&natureza=$natureza&data_inicial=$data_inicial_D&data_final=$data_final_D\" target='_blank'>$qtde</a>";
						echo "&nbsp;";
				#echo "<br>";
				#echo $nome_comercial;
				echo "</td>";
				$ultimo_nome_comercial = $nome_comercial;
			}

			if (pg_result ($resProd,$x,nome_comercial) > $ultimo_nome_comercial) {
				echo "<td nowrap>&nbsp;";
				echo "&nbsp;";
#				echo "<br>";
#				echo pg_result ($resProd,$x,nome_comercial);
#				echo "<br>";
#				echo $ultimo_nome_comercial;
#				echo "<br>";
#				echo $x;
#				echo $nome_comercial;
#				echo "<br>";
				echo "</td>";
			}
		}



#		echo $duvida_reclamacao;
#		echo "<br>";

#		echo "<td>";
#		echo $defeito_descricao;
#		echo "</td>";

#		echo $nome_comercial;
#		echo "<br>";

#		echo $produto;
#		echo "<br>";

#		echo $qtde;
#		echo "<br>";

#		echo "<hr>";
	}
	echo "<tr bgcolor='#6699FF' style='font-size:10px ; font-color:#ffffff '>";
	
	echo "<td align='left' colspan='$span' style='color:#ffffff ; font-weight:bold ; font-size:16px'>";
	echo "TOTAL DE CHAMADAS: $natureza - $soma_qtde";
	echo "</td>";
	

	if ($natureza == 'Email') $soma_email = $soma_qtde ;
				
	echo "</tr>";
	echo "<tr bgcolor='#6699FF' style='font-size:10px ; font-color:#ffffff '>";
	
	$total_chamadas    += $soma_qtde - $soma_email;
	
	echo "<td align='center' colspan='$span' style='color:#ffffff ; font-weight:bold ; font-size:16px'>";
	echo "TOTAL GERAL DE CHAMADAS (exceto $soma_email emails): $total_chamadas";
	echo "</td>";
	
	echo "</tr>";
	echo "</table>";

}
?>

<br><br>

<? include "rodape.php"; ?>
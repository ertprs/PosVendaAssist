<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

include "funcoes.php";

$admin_privilegios = "gerencia";
include "autentica_admin.php";

$msg = "";

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if (strlen($_POST['btn_acao']) > 0 ) {
	$mes = trim (strtoupper ($_POST['mes']));
	$ano = trim (strtoupper ($_POST['ano']));

	$codigo_posto       = trim(strtoupper($_POST['codigo_posto']));
	$posto_nome         = trim(strtoupper($_POST['posto_nome']));
	$produto_referencia = trim(strtoupper($_POST['produto_referencia']));

	if ( strlen($mes)==0 OR strlen($ano)==0 )  {
		$msg = "Selecione o mês e o ano para fazer a pesquisa";
	}

	if (strlen($msg)==0 AND strlen($codigo_posto)==0 )  {
		$msg = "Informe o código do posto para pesquisar";
	}

	if (strlen($msg) == 0) {
		$sql = "SELECT posto from tbl_posto_fabrica where fabrica=$login_fabrica and codigo_posto='$codigo_posto'";
		$res = pg_exec($con, $sql);
		if (pg_numrows($res) <> 1) $msg = "Posto $codigo_posto não encontrado. Utilize a lupa para pesquisar.";
	}

	if (strlen($msg)==0 AND strlen($mes)>0) {
		$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
	}

	if (strlen($msg)==0 AND strlen($produto_referencia)>0) {
		$sql = "SELECT produto from tbl_produto join tbl_linha using(linha) where fabrica=$login_fabrica and upper(referencia) = upper('$produto_referencia')";
		$res = pg_exec($con, $sql);
		if (pg_numrows($res) <> 1) $msg = "Produto $produto_referencia não encontrado. Utilize a lupa para pesquisar.";
	}
}

$layout_menu = "gerencia";
$title = "Quantidade de produtos atendidos por posto";
include "cabecalho.php";
?>

<style type="text/css">
.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
}
.Erro{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#CC3300;
	font-weight: bold;
	background-color:#FFFFFF;
}
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
</style>

<? include "javascript_pesquisas.php"; ?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" src="js/assist.js"></script>
<br>



<?
if(strlen($msg)>0){
	echo "<table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
	echo "<tr >";
	echo "<td bgcolor='FFFFFF' width='60'class='Erro'><img src='imagens/proibido2.jpg' align='middle'></td><td  class='Erro' bgcolor='FFFFFF' align='left'> $msg</td>";
	echo "</tr>";
	echo "</table><br>";
}


if (strlen($_POST['btn_acao']) > 0 AND strlen($msg) == 0) {

	$join_especifico = "";
	$especifica_mais_1 = "1=1";
	$especifica_mais_2 = "1=1";

	if (strlen ($data_inicial) > 0) {
		if (strlen ($produto_referencia) > 0) {
			$sql = "SELECT produto 
					FROM tbl_produto 
					JOIN tbl_linha USING (linha) 
					WHERE tbl_linha.fabrica = $login_fabrica 
					AND tbl_produto.referencia = '$produto_referencia'";
			$res = pg_exec ($con,$sql);
			$produto = pg_result ($res,0,0);
			$especifica_mais_1 = "tbl_os.produto = $produto";
		}

		if (strlen ($codigo_posto) > 0) {
			$sql = "SELECT posto 
					FROM tbl_posto_fabrica 
					WHERE fabrica = $login_fabrica 
					AND codigo_posto = '$codigo_posto'";
			$res = pg_exec ($con,$sql);
			$posto = pg_result ($res,0,0);
			$especifica_mais_2 = "tbl_os.posto = $posto";
		}

		$join_especifico = "JOIN (  SELECT os 
									FROM tbl_os 
									WHERE fabrica = $login_fabrica 
									AND   tbl_os.finalizada NOTNULL
									AND   $especifica_mais_1
									AND   $especifica_mais_2
							) oss ON tbl_os.os = oss.os ";
	}

	$sql = "SELECT  tbl_produto.produto, 
					tbl_produto.referencia, 
					tbl_produto.descricao, 
					count(tbl_os.os) as qtd
			FROM (SELECT * from tbl_os where fabrica = {$login_fabrica} ) tbl_os 
			JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
			LEFT JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
			LEFT JOIN tbl_posto_fabrica ON tbl_os.fabrica = tbl_posto_fabrica.fabrica 
			$join_especifico ";

	if (strlen($mes) > 0) {
		$sql .= " AND tbl_os.finalizada BETWEEN '$data_inicial' AND '$data_final'";
	}

	if (strlen($codigo_posto) > 0) {
		$sql .= " AND tbl_posto_fabrica.codigo_posto = '$codigo_posto'";
	}

	if (strlen($produto_referencia) > 0) {
		$sql .= " AND tbl_os.produto in ( select produto from tbl_produto join tbl_linha using(linha) where fabrica = $login_fabrica and upper(referencia) = upper('$produto_referencia') ) ";
	}

	$sql.= "GROUP BY  tbl_produto.produto, tbl_produto.referencia, tbl_produto.descricao
			ORDER by  tbl_produto.descricao";

	$res = pg_exec($con,$sql);
	$resultados = pg_numrows($res);

	if (pg_numrows($res) > 0) {
		echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#C2D2ED'  align='center' width='50%'>";
		echo "<tr class='Titulo' height='15'><TD COLSPAN='2'>TOTAL DE OSs FINALIZADAS NO MÊS DE ".strtoupper($meses[$mes])." PELO POSTO<BR>$codigo_posto - $posto_nome</TD></tr>";
		echo "<tr class='Titulo' height='15'>";
		echo "<td>PRODUTO</td>";
		echo "<td>QTD.</td>";

		$total_geral = 0;

		for ($i=0 ; $i<pg_numrows($res) ; $i++) {
			$referencia  = trim(pg_result($res,$i,referencia));
			$descricao   = trim(pg_result($res,$i,descricao));
			$qtd         = trim(pg_result($res,$i,qtd));
			$total_geral =  $total_geral + $qtd;
			
			if ($i % 2 == 0) {
				$cor   = "#F1F4FA";
				$botao = "azul";
			}else{
				$cor   = "#F7F5F0";
				$botao = "amarelo";
			}
			
			echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
			echo "<td nowrap align='left'>" .$referencia." - ".$descricao. "</td>";
			echo "<td nowrap align='center'>" . $qtd . "</td>";
			echo "</tr>";
		}
		echo "<tr class='Conteudo' height='15' bgcolor='#FFFFFF' align='center'>";
		echo "<td nowrap>TOTAL GERAL</td>";
		echo "<td nowrap>" . $total_geral . "</td>";
		echo "</tr>";
		echo "</table>";
	}

	echo "<h1>Resultado: $resultados registro(s).</h1>";
}
?>


<?
$mes = trim (strtoupper ($_POST['mes']));
$ano = trim (strtoupper ($_POST['ano']));

$codigo_posto    = trim (strtoupper ($_POST['codigo_posto']));
$posto_nome      = trim (strtoupper ($_POST['posto_nome']));
?>


<form name="frm_consulta" method="post" action="<?echo $PHP_SELF?>">

	<input type="hidden" name="acao">

	<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
		<tr class="Titulo" height="30">
			<td align="center">Selecione os parâmetros para a pesquisa.</td>
		</tr>
	</table>

	<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td> * Mês</td>
			<td> * Ano</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td>
				<select name="mes" size="1" class="frm">
				<option value=''></option>
				<?
				for ($i = 1 ; $i <= count($meses) ; $i++) {
					echo "<option value='$i'";
					if ($mes == $i) echo " selected";
					echo ">" . $meses[$i] . "</option>";
				}
				?>
				</select>
			</td>
			<td>
				<select name="ano" size="1" class="frm">
				<option value=''></option>
				<?
				for ($i = 2003 ; $i <= date("Y") ; $i++) {
					echo "<option value='$i'";
					if ($ano == $i) echo " selected";
					echo ">$i</option>";
				}
				?>
				</select>
			</td>
		</tr>

		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td> * Posto</td>
			<td> * Nome do Posto</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td>
				<input type="text" name="codigo_posto" size="8" value="<? echo $codigo_posto ?>" class="frm">
				<img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'codigo')">
			</td>
			<td>
				<input type="text" name="posto_nome" size="30" value="<?echo $posto_nome?>" class="frm">
				<img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'nome')">
			</td>
		</tr>

		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td>Ref. Produto</td>
			<td>Descrição Produto</td>
		</tr>

		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td>
			<input class="frm" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" > 
			&nbsp;
			<img src='imagens/btn_lupa.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_consulta.produto_referencia, document.frm_consulta.produto_descricao,'referencia')">
			</td>

			<td>
			<input class="frm" type="text" name="produto_descricao" size="30" value="<? echo $produto_descricao ?>" >
			&nbsp;
			<img src='imagens/btn_lupa.gif'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_consulta.produto_referencia, document.frm_consulta.produto_descricao,'descricao')">
		</tr>
	</table>
		
	<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
		<tr class="Conteudo" bgcolor="#D9E2EF">
			<td colspan='2' align='center'><br><input type="submit" name="btn_acao" value="Pesquisar"></td>
		</tr>
	</table>

</form>

<? include "rodape.php" ?>
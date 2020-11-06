<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "includes/funcoes.php";

$admin_privilegios="gerencia";
include 'autentica_admin.php';
?>

<script language="JavaScript">
function FuncMouseOver (linha, cor) {
	linha.style.cursor = "hand";
	linha.style.backgroundColor = cor;
}
function FuncMouseOut (linha, cor) {
	linha.style.cursor = "default";
	linha.style.backgroundColor = cor;
}

function FuncVisualizarProduto (produto, data_inicial, data_final, qtde) {
	var largura  = 500;
	var tamanho  = 400;
	var lar      = largura / 2;
	var tam      = tamanho / 2;
	var esquerda = (screen.width / 2) - lar;
	var topo     = (screen.height / 2) - tam;
	var link = '<?echo $PHP_SELF?>?acao=VISUALIZAR&produto=' + produto + '&data_inicial=' + data_inicial + '&data_final=' + data_final + '&qtde=' + qtde;
	window.open(link, "janela", "toolbar=no, location=no, status=yes, menubar=no, scrollbars=no, directories=no, resizable=no, width=" + largura + ", height=" + tamanho + ", top=" + topo + ", left=" + esquerda + "");

}

function FuncVisualizarDefeito (produto, serie_inicial, serie_final,mes,ano) {
	var largura  = 500;
	var tamanho  = 400;
	var lar      = largura / 2;
	var tam      = tamanho / 2;
	var esquerda = (screen.width / 2) - lar;
	var topo     = (screen.height / 2) - tam;
	var link = 'relatorio_producao_serie_defeito.php?produto=' + produto + '&serie_inicial=' + serie_inicial + '&serie_final=' + serie_final + '&mes=' + mes+ '&ano=' + ano;
	window.open(link, "janela", "toolbar=no, location=no, status=yes, menubar=no, scrollbars=no, directories=no, resizable=no, width=" + largura + ", height=" + tamanho + ", top=" + topo + ", left=" + esquerda + "");

}
</script>


<?
$erro = "";

$meses = array (1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if (strlen($_GET["acao"]) > 0) $acao = strtoupper($_GET["acao"]);

if ($acao == "VISUALIZAR") {
	$produto      = $_GET["produto"];
	$data_inicial = $_GET["data_inicial"];
	$data_final   = $_GET["data_final"];
	$qtde         = $_GET["qtde"];


	echo "<html>\n";
	echo "<head>\n";
	echo "<title>RELATÓRIO DE PRODUÇÃO</title>\n";
	echo "</head>\n";
	echo "<style type='text/css'>";
	echo ".Titulo { text-align: center; font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif; font-size: 12px; font-weight: bold; color: #FFFFFF; background-color: #596D9B; }";
	echo ".Conteudo { font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif; font-size: 12px; font-weight: normal; }";
	echo"	.BotaoNovo {border: 1px solid #596D9B;font-size: 11px;font-family: Arial, Helvetica, sans-serif;color: #596D9B; background-color: #FFFFFF;font-weight: bold;	};";
	echo "</style>";
	echo "<body>\n";
	
	//CABEÇALHO ONDE VAI O NOME DO PRODUTO
	$sql="SELECT referencia, descricao
			FROM tbl_produto
			WHERE produto=$produto";
	$res = pg_exec ($con,$sql);
	if (pg_numrows($res) > 0) {
		 $descricao            = trim(pg_result($res,0,descricao));
		 $referencia           = trim(pg_result($res,0,referencia));
		 echo '<CENTER>PRODUTO: <span classclass="Titulo"><b>'.$referencia.' - '.$descricao.'</b></span><br><BR></CENTER>';
	}



	$sql =	"SELECT tbl_producao.mes                                                         ,
					tbl_producao.ano                                                         ,
					tbl_producao.serie_inicial                                               ,
					tbl_producao.serie_final                                                 ,
					(tbl_producao.serie_final - tbl_producao.serie_inicial) AS qtde_producao
			FROM tbl_producao
			JOIN tbl_produto USING (produto)
			JOIN tbl_linha USING (linha)
			WHERE tbl_linha.fabrica   = $login_fabrica
			AND   tbl_produto.produto = $produto
			ORDER BY tbl_producao.ano ASC, tbl_producao.mes ASC;";
//if ($ip=='201.0.9.216')	echo $sql;
	$res = pg_exec ($con,$sql);
//	echo pg_numrows($res);
	if (pg_numrows($res) > 0) {
		echo "\n<table width='100%' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
		echo "\n<tr height='15' class='Titulo'>";
		echo "\n<td>MÊS</td>";
		echo "\n<td>QTDE PRODUÇÃO</td>";
		echo "\n<td>QTDE DEFEITO</td>";
		echo "\n<td>% DEFEITO</td>";
		echo "\n</tr>";
		
		$qtde_defeito_total = 0;
		
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$mes           = trim(pg_result($res,$x,mes));
			$ano           = trim(pg_result($res,$x,ano));
			$serie_inicial = trim(pg_result($res,$x,serie_inicial));
			$serie_final   = trim(pg_result($res,$x,serie_final));
			$qtde_producao = trim(pg_result($res,$x,qtde_producao));
			
			$cor = ($x % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
			echo "\n<tr height='15' class='Conteudo' onclick=\"javascript: FuncVisualizarDefeito('$produto', '$serie_inicial', '$serie_final','$mes','$ano');\" onmouseover=\"javascript: FuncMouseOver(this, '#FFCC99');\" onmouseout=\"javascript: FuncMouseOut(this, '$cor');\">";
//			echo "<tr height='15' class='Conteudo' bgcolor='$cor'>";
			echo "\n<td align='center'>" . $meses[$mes] . " / " . $ano . "</td>";
			echo "\n<td align='right'><acronym title='Série Inicial: $serie_inicial\nSérie Final: $serie_final' style='cursor: help;'>" . $qtde_producao . "</acronym></td>";
			
			$sqlX =	"\nSELECT COUNT(tbl_os.os) AS qtde_defeito
					FROM tbl_os
					WHERE tbl_os.fabrica = $login_fabrica
					AND   tbl_os.produto = $produto
					AND   tbl_os.data_digitacao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
					AND   tbl_os.serie BETWEEN '$serie_inicial' AND '$serie_final';";
			$resX = pg_exec($con,$sqlX);
			
			$qtde_defeito = trim(pg_result($resX,0,qtde_defeito));
			if ($qtde_producao > 0) $porcentagem_defeito = ($qtde_defeito * 100) / $qtde_producao;
			else                    $porcentagem_defeito = 0;
			echo "<td align='right'>" . $qtde_defeito . "</td>";
			echo "<td align='right'>" . round($porcentagem_defeito,2) . " %</td>";
			
			$qtde_defeito_total += $qtde_defeito;
			
			echo "</tr>";
		}
		echo "<tr height='15' class='Titulo'>";
		echo "<td colspan='4'>Qtde de total de DEFEITO: $qtde</td>";
		echo "</tr>";
		echo "<tr height='15' class='Titulo'>";
		echo "<td colspan='4'>Qtde de defeito (PRODUÇÃO): $qtde_defeito_total</td>";
		echo "</tr>";
		$qtde_sem_serie = $qtde - $qtde_defeito_total;
		echo "<tr height='15' class='Titulo'>";
		echo "<td colspan='4'>Qtde de defeito (SEM SÉRIE): $qtde_sem_serie</td>";
		echo "</tr>";
		echo "</table>";
		echo "<br>";
		echo "<center><button type='button' name='botao' title='Fechar' onclick='javascript: window.close();'class='BotaoNovo'>FECHAR</button></center>";
	}else{
//		echo 'nao entrou no if';exit;
		echo "<script language='JavaScript'>";
		echo "window.close();";
		echo "</script>";
	}
	echo "</body>";
	echo "</html>";
	exit;
}

if ($acao == "PESQUISAR") {
	$mes     = trim($_GET["mes"]);
	$ano     = trim($_GET["ano"]);
	$linha   = trim($_GET["linha"]);
	$familia = trim($_GET["familia"]);
	
	if (strlen($mes) == 0) $erro .= " Favor informar o mês. ";
	if (strlen($ano) == 0) $erro .= " Favor informar o ano. ";
	if (strlen($linha) == 0 && strlen($familia) == 0) $erro .= " Favor informar a Linha ou a Família. ";
}

$layout_menu = "gerencia";
$title = "RELATÓRIO DE PRODUÇÃO";

include "cabecalho.php";
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
}
</style>




<? if (strlen($erro) > 0) { ?>
<br>
<table width="700" border="0" cellpadding="2" cellspacing="2" align="center" class="error">
	<tr>
		<td><? echo $erro; ?></td>
	</tr>
</table>
<? } ?>

<br>

<form name="frm_pesquisa" method="get" action="<? echo $PHP_SELF ?>">

<input type="hidden" name="acao">

<table width="400" border="0" cellspacing="0" cellpadding="2" align="center">
	<tr>
		<td colspan="4" class="Titulo">SELECIONE OS PARÂMETROS PARA A PESQUISA</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td>Mês</td>
		<td>Ano</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
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
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td colspan="2">Linha</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td colspan="2">
			<select name="linha" size="1" class="frm">
				<option value=''></option>
				<?
				$sql = "SELECT linha, nome
						FROM tbl_linha
						WHERE fabrica = $login_fabrica;";
				$res = pg_exec($con,$sql);
				if (pg_numrows($res) > 0) {
					for ($x = 0 ; $x < pg_numrows($res) ; $x++) {
						$x_linha = pg_result($res,$x,linha);
						$x_nome  = pg_result($res,$x,nome);
						echo "<option value='$x_linha'";
						if ($linha == $x_linha) echo " selected";
						echo ">$x_nome</option>";
					}
				}
				?>
			</select>
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td colspan="2">Família</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td colspan="2">
			<select name="familia" size="1" class="frm">
				<option value=''></option>
				<?
				$sql = "SELECT familia, descricao
						FROM tbl_familia
						WHERE fabrica = $login_fabrica;";
				$res = pg_exec($con,$sql);
				if (pg_numrows($res) > 0) {
					for ($x = 0 ; $x < pg_numrows($res) ; $x++) {
						$x_familia   = pg_result($res,$x,familia);
						$x_descricao = pg_result($res,$x,descricao);
						echo "<option value='$x_familia'";
						if ($familia == $x_familia) echo " selected";
						echo ">$x_descricao</option>";
					}
				}
				?>
			</select>
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4" align="center"><img src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: document.frm_pesquisa.acao.value='PESQUISAR'; document.frm_pesquisa.submit();" style="cursor: hand;" alt="Clique AQUI para pesquisar"></td>
	</tr>
</table>

</form>

<?
flush();
if (strlen($acao) > 0 && strlen($erro) == 0) {
	$data_inicial = date("Y-m-01", mktime(0, 0, 0, $mes, 1, $ano));
	$data_final   = date("Y-m-t", mktime(0, 0, 0, $mes, 1, $ano));
	
/*
SELECT ANTIGO
	$sql = "SELECT tbl_os.produto                 ,
					tbl_produto.referencia         ,
					tbl_produto.descricao          ,
					COUNT(tbl_os.os)       AS qtde
			FROM tbl_os
			JOIN tbl_produto USING (produto)
			WHERE tbl_os.fabrica    = $login_fabrica
			AND   tbl_os.data_digitacao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'";
	if (strlen($linha) > 0) $sql .= " AND tbl_produto.linha = $linha";
	if (strlen($familia) > 0) $sql .= " AND tbl_produto.familia = $familia";
	$sql .= " GROUP BY  tbl_os.produto         ,
						tbl_produto.referencia ,
						tbl_produto.descricao
			ORDER BY tbl_produto.referencia ASC, tbl_produto.descricao ASC;";
*/
//echo $sql;


$sql = 	   "SELECT    tbl_os.produto ,
						tbl_produto.referencia ,
						tbl_produto.descricao ,
						COUNT(tbl_os.os) AS qtde
			FROM      tbl_os JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
			JOIN      tbl_producao ON tbl_producao.produto = tbl_produto.produto";
if (strlen($linha) > 0) $sql .=" JOIN      tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.linha = $linha";
if (strlen($familia) > 0) $sql .= " JOIN      tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.familia = $familia";
$sql .= "	WHERE     tbl_os.fabrica = $login_fabrica
			AND       tbl_os.data_digitacao
			BETWEEN   '$data_inicial 00:00:00' AND '$data_final 23:59:59'
			GROUP BY  tbl_os.produto , tbl_produto.referencia , tbl_produto.descricao
		ORDER BY  tbl_produto.referencia ASC, tbl_produto.descricao ASC";

///echo $sql;


	$res = pg_exec ($con,$sql);
	
#	if (getenv("REMOTE_ADDR") == "201.0.9.216") { echo nl2br($sql)."<br>".pg_numrows($res)."<br><br>"; }
	
	if (pg_numrows($res) > 0) {
		echo "<h3>Clique na linha do produto p/ verificar o defeito por produção.</h3>";
		echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
		echo "<tr height='15' class='Titulo'>";
		echo "<td> PRODUTO </td>";
		echo "<td> QTDE </td>";
		echo "</tr>";
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$produto    = trim(pg_result($res,$x,produto));
			$referencia = trim(pg_result($res,$x,referencia));
			$descricao  = trim(pg_result($res,$x,descricao));
			$qtde       = trim(pg_result($res,$x,qtde));
			
			$cor = ($x % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
			
			echo "<tr height='15' bgcolor='$cor' class='Conteudo' onclick=\"javascript: FuncVisualizarProduto('$produto', '$data_inicial', '$data_final', '$qtde');\" onmouseover=\"javascript: FuncMouseOver(this, '#FFCC99');\" onmouseout=\"javascript: FuncMouseOut(this, '$cor');\">";
			echo "<td>" . $referencia . " - " . $descricao . "</td>";
			echo "<td>" . $qtde . "</td>";
			echo "</tr>";
		}
		echo "</table>";
	}
}

include "rodape.php";
?>

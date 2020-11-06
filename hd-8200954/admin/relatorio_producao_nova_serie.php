<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "includes/funcoes.php";

$admin_privilegios="gerencia";
include 'autentica_admin.php';
?>


<?
$msg_erro = "";

$meses = array (1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

















if (strlen($_GET["acao"]) > 0) $acao = strtoupper($_GET["acao"]);





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
	font-family: Verdana;
 	font-size: 10px; 
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Comeco {
	text-align: center;
	font-family: Verdana;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Arial;
	font-size: 10px;
	font-weight: normal;
}

</style>




<? if (strlen($erro) > 0) { ?>
<br>
<table width="700" border="0" cellpadding="2" cellspacing="2" align="center" class="error">
	<tr>
		<td><? echo $msg_erro; ?></td>
	</tr>
</table>
<? } ?>



<?
if (trim($acao) =='EXPLODIR') {
	$mes       = trim($_GET["mes"]);
	$ano       = trim($_GET["ano"]);
	$produto   = trim($_GET["produto"]);
	$serie     = trim($_GET["serie"]);


	$data_inicial = date("Y-m-01", mktime(0, 0, 0, $mes, 1, $ano));
	$data_final   = date("Y-m-t", mktime(0, 0, 0, $mes, 1, $ano));
	

	if(strlen($msg_erro)==0){

	$sql = "SELECT  SUM(tbl_os_item.qtde) AS total_pecas
		FROM    tbl_os
		JOIN       tbl_os_produto  ON tbl_os_produto.os      = tbl_os.os
		JOIN       tbl_os_item     ON tbl_os_item.os_produto = tbl_os_produto.os_produto
		JOIN       tbl_peca        ON tbl_os_item.peca       = tbl_peca.peca 
		JOIN tbl_produto           ON tbl_produto.produto    = tbl_os.produto
		LEFT JOIN  tbl_os_status   ON tbl_os_status.os       = tbl_os.os 
		WHERE   tbl_os.data_digitacao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
		AND     tbl_os.fabrica      = $login_fabrica
		AND     tbl_produto.produto = $produto
		AND     tbl_os.serie        = '$serie'
		AND     (tbl_os_status.status_os NOT IN (13,15) OR tbl_os_status.status_os IS NULL)";


	$res = pg_exec ($con,$sql);
	if(strlen(pg_numrows)>0){
		$total_pecas     = trim(pg_result($res,0,total_pecas));
	}
	$sql = "SELECT  SUM(tbl_os_item.qtde) AS ocorrencia   ,
			tbl_peca.peca                         ,
			tbl_peca.referencia                   ,
			tbl_peca.descricao
		FROM    tbl_os
		JOIN       tbl_os_produto  ON tbl_os_produto.os      = tbl_os.os
		JOIN       tbl_os_item     ON tbl_os_item.os_produto = tbl_os_produto.os_produto
		JOIN       tbl_peca        ON tbl_os_item.peca       = tbl_peca.peca 
		JOIN tbl_produto           ON tbl_produto.produto    = tbl_os.produto
		LEFT JOIN  tbl_os_status   ON tbl_os_status.os       = tbl_os.os 
		WHERE   tbl_os.data_digitacao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
		AND     tbl_os.fabrica      = $login_fabrica
		AND     tbl_produto.produto = $produto 
		AND     tbl_os.serie        = '$serie'
		AND     (tbl_os_status.status_os NOT IN (13,15) OR tbl_os_status.status_os IS NULL)
		GROUP BY tbl_peca.peca, tbl_peca.descricao, tbl_peca.referencia
		ORDER BY ocorrencia DESC";


		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			echo "\n<br><table width='700' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
			echo "<tr height='15' class='Comeco'><td colspan='6'>MÊS: $meses[$mes] / $ano</td></tr>";
			echo "\n<tr height='15' class='Titulo'>";
			echo "\n<td>REFERENCIA</td>";
			echo "\n<td>DESCRIÇÃO</td>";
			echo "\n<td>QTDE</td>";
			echo "\n<td>%</td>";
			echo "\n</tr>";
			
			$qtde_defeito_total = 0;
			
			for ($x = 0; $x < pg_numrows($res); $x++) {

				$descricao     = trim(pg_result($res,$x,descricao));
				$referencia    = trim(pg_result($res,$x,referencia));
				$ocorrencia    = trim(pg_result($res,$x,ocorrencia));
				$peca     = trim(pg_result($res,$x,peca));
				
				$cor = ($x % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
				
				echo "<tr height='15' class='Conteudo' bgcolor='$cor' align='center'>";
				echo "\n<td>$referencia</a></td>";
				echo "\n<td>$descricao</td>";
				echo "\n<td>$ocorrencia</td>";


				if ($ocorrencia > 0) $porcentagem_defeito = ($ocorrencia * 100) / $total_pecas;
				else                    $porcentagem_defeito = 0;

				echo "<td>" . round($porcentagem_defeito,2) . " %</td>";
				$qtde_defeito_total += $qtde_defeito;
				echo "</tr>";
			}
			echo "</table>";
			echo "\n<br><table width='700' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
			echo "<tr height='15'  >";
			echo "<td colspan='5' class='Titulo'>Qtde de total de DEFEITO</td><td><b>$total_pecas</b></td>";
			echo "</tr>";
			echo "<tr height='15' >";
			echo "<td colspan='5' class='Titulo'>Qtde de defeito (PRODUÇÃO)</td><td><b>$qtde_defeito_total</b></td>";
			echo "</tr>";
			echo "</table>";
			echo "<br>";

		}else{echo "<CENTER>NENHUM CASO ENCONTRADO<BR><a href='javascript:back();'>Voltar</a></CENTER>";}
	}
}else{


?>
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
		<td colspan="4" align="center"><img src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: document.frm_pesquisa.acao.value='PESQUISAR'; document.frm_pesquisa.submit();" style="cursor: hand;" alt="Clique AQUI para pesquisar"></td>
	</tr>
</table>

</form>
<?

if (strlen($_GET["acao"]) > 0) $acao = strtoupper($_GET["acao"]);


	if (strlen($_GET["acao"]) > 0) {
		$mes     = trim($_GET["mes"]);
		$ano     = trim($_GET["ano"]);
		$linha   = trim($_GET["linha"]);
		$familia = trim($_GET["familia"]);
	
	
	
		$data_inicial = date("Y-m-01", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final   = date("Y-m-t", mktime(0, 0, 0, $mes, 1, $ano));
		
		if (strlen($mes) == 0)   $msg_erro .= " Favor informar o mês. ";
		if (strlen($ano) == 0)   $msg_erro .= " Favor informar o ano. ";
		if (strlen($linha) == 0) $msg_erro .= " Favor informar a Linha ou a Família. ";
	
		if(strlen($msg_erro)==0){
			
			$sql =	"SELECT tbl_producao.mes                   ,
					tbl_producao.ano                   ,
					tbl_producao.serie_inicial         ,
					tbl_producao.serie_final           ,
					tbl_producao.qtde                  ,
					tbl_produto.produto                ,
					tbl_produto.referencia             ,
					tbl_produto.descricao
				FROM tbl_producao
				JOIN tbl_produto USING (produto)
				JOIN tbl_linha USING (linha)
				WHERE tbl_linha.fabrica   = $login_fabrica
				AND   tbl_produto.linha   = $linha
				AND   tbl_producao.qtde > 0
				ORDER BY tbl_producao.ano ASC, tbl_producao.mes ASC;";
	
			$res = pg_exec ($con,$sql);
	
			if (pg_numrows($res) > 0) {
				echo "\n<br><table width='700' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
				echo "<tr height='15' class='Comeco'><td colspan='6'>MÊS: $meses[$mes] / $ano</td></tr>";
				echo "\n<tr height='15' class='Titulo'>";
				echo "\n<td>PRODUTO</td>";
				echo "\n<td>SÉRIE</td>";
				echo "\n<td>QTDE<br>PRODUZIDA</td>";
				echo "\n<td>QTDE<br>DEFEITO</td>";
				echo "\n<td>% DEFEITO</td>";
				echo "\n<td>QTDE<br>DEFEITOS<br>PRODUTO</td>";
				echo "\n</tr>";
				
				$qtde_defeito_total = 0;
				
				for ($x = 0; $x < pg_numrows($res); $x++) {
	
					$mes           = trim(pg_result($res,$x,mes));
					$ano           = trim(pg_result($res,$x,ano));
					$serie_final   = trim(pg_result($res,$x,serie_final));
					$qtde          = trim(pg_result($res,$x,qtde));
					$produto       = trim(pg_result($res,$x,produto));
					$referencia    = trim(pg_result($res,$x,referencia));
					$descricao     = trim(pg_result($res,$x,descricao));
					
					$cor = ($x % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
					
					echo "<tr height='15' class='Conteudo' bgcolor='$cor' align='center'>";
					echo "\n<td><a href='$PHP_SELF?produto=$produto&serie=$serie&mes=$mes&ano=$ano&acao=explodir'>$referencia - $descricao</a></td>";
					echo "\n<td>$serie_final</td>";
					echo "\n<td>$qtde</td>";
	
					$sqlY =	"\nSELECT COUNT(tbl_os.os)     AS qtde_defeito 
							FROM tbl_os
							JOIN  tbl_produto  USING(produto)
							WHERE tbl_os.fabrica = $login_fabrica
							AND   tbl_produto.produto = $produto
							AND   tbl_os.data_digitacao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59';";
					$resY = pg_exec($con,$sqlY);
	
					if(strlen(pg_numrows($resY))>0) $qtde_defeito_mes = trim(pg_result($resY,0,qtde_defeito));
	
					
					$sqlX =	"\nSELECT COUNT(tbl_os.os)     AS qtde_defeito 
							FROM tbl_os
							JOIN  tbl_produto  USING(produto)
							WHERE tbl_os.fabrica = $login_fabrica
							AND   tbl_produto.produto = $produto
							AND   tbl_os.data_digitacao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
							AND   tbl_os.serie = '$serie_final';";
	
					$resX = pg_exec($con,$sqlX);
	
					if(strlen(pg_numrows($resX))>0) $qtde_defeito = trim(pg_result($resX,0,qtde_defeito));
	
					if ($qtde_producao > 0) $porcentagem_defeito = ($qtde_defeito * 100) / $qtde;
					else                    $porcentagem_defeito = 0;
	
					echo "<td>" . $qtde_defeito . "</td>";
					echo "<td>" . round($porcentagem_defeito,2) . " %</td>";
					echo "<td>" . $qtde_defeito_mes . "</td>";
					
					$qtde_defeito_total += $qtde_defeito;
					
					echo "</tr>";
				}
				echo "</table>";
				echo "\n<br><table width='700' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
				echo "<tr height='15'  >";
				echo "<td colspan='5' class='Titulo'>Qtde de total de DEFEITO</td><td><b>$qtde_defeito</b></td>";
				echo "</tr>";
				echo "<tr height='15' >";
				echo "<td colspan='5' class='Titulo'>Qtde de defeito (PRODUÇÃO)</td><td><b>$qtde_defeito_total</b></td>";
				echo "</tr>";
				$qtde_sem_serie = $qtde - $qtde_defeito_total;
				echo "</table>";
				echo "<br>";
	
			}
		}
	}

}
include "rodape.php";
?>

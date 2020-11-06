<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
include "funcoes.php";

$msg = "";

if (strlen($_GET["acao"]) > 0) $acao = strtoupper($_GET["acao"]);

if (strlen($_GET["pedido"]) > 0) $pedido = $_GET["pedido"];

if (strlen($_GET["nota"]) > 0) $nota = $_GET["nota"];

$layout_menu = "pedido";
$title = "Consulta NF´s emitidas pelo Fabricante";
include "cabecalho.php";

if (strlen($acao) > 0 && $acao == "PESQUISAR") {
	if (strlen(trim($_GET["numero_pedido"])) > 0) $numero_pedido = trim($_GET["numero_pedido"]);
	if (strlen(trim($_GET["numero_nf"])) > 0)     $numero_nf = trim($_GET["numero_nf"]);

	if (strlen($numero_pedido) == 0 && strlen($numero_nf) == 0) {
		$msg .= "Preencha um campo para realizar a pesquisa. ";
	}
}
?>

<style type="text/css">
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

<br>

<? if (strlen($msg) > 0) { ?>
<table width="600" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr>
		<td class="error"><?echo $msg?></td>
	</tr>
</table>
<br>
<? } ?>

<? if (strlen($pedido) == 0) { ?>
<form name="frm_pesquisa" method="get" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">

<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Titulo">
		<td colspan="4" align="center">PREENCHA OS CAMPOS PARA REALIZAR A PESQUISA</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td>Número do Pedido</td>
		<td><input type="text" name="numero_pedido" size="17" value="<? echo $numero_pedido; ?>"></td>
		<td>&nbsp;</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td>Número da Nota Fiscal</td>
		<td><input type="text" name="numero_nf" size="17" value="<? echo $numero_nf; ?>"></td>
		<td>&nbsp;</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan="4"><img src="imagens/btn_pesquisar_400.gif" onClick="document.frm_pesquisa.acao.value='PESQUISAR'; document.frm_pesquisa.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
	</tr>
</table>

</form>

<a href="<? echo $PHP_SELF; ?>?acao=LISTAR">Clique aqui para listar todos pedidos</a>

<br><br>
<? } ?>

<?
if (($acao == "LISTAR" || $acao == "PESQUISAR") && strlen($msg) == 0) {
	$sql =	"SELECT DISTINCT
					tbl_pedido.pedido                                              ,
					TO_CHAR(tbl_pedido.data,'DD/MM/YYYY')       AS data            ,
					tbl_faturamento.pedido_fabricante                              ,
					LPAD(tbl_faturamento.pedido_fabricante,10,'0') AS pedido_ordem ,
					(
						SELECT tbl_status.descricao AS status
						FROM   tbl_pedido_status
						JOIN   tbl_status USING (status)
						WHERE  tbl_pedido_status.pedido = tbl_pedido.pedido
						ORDER BY tbl_pedido_status.data DESC
						LIMIT 1
					) AS pedido_status                                            
			FROM      tbl_pedido
			JOIN      tbl_faturamento   ON tbl_faturamento.pedido   = tbl_pedido.pedido
			LEFT JOIN tbl_pedido_status ON tbl_pedido_status.pedido = tbl_pedido.pedido
			WHERE   tbl_pedido.fabrica = $login_fabrica
			AND     tbl_pedido.posto   = $login_posto";


	if (strlen ($numero_pedido) > 0 OR 1==1) {
		$sql =	"SELECT DISTINCT tbl_pedido.pedido                                              ,
						TO_CHAR(tbl_pedido.data,'DD/MM/YYYY')       AS data            ,
						tbl_status_pedido.descricao                 AS status          ,
						(SELECT pedido_fabricante FROM tbl_faturamento WHERE tbl_faturamento.pedido = tbl_pedido.pedido LIMIT 1) AS pedido_fabricante
				FROM      tbl_pedido
				LEFT JOIN tbl_status_pedido ON tbl_status_pedido.status_pedido = tbl_pedido.status_pedido
				WHERE   tbl_pedido.fabrica = $login_fabrica
				AND     tbl_pedido.posto   = $login_posto";
	}

#				AND     tbl_pedido.pedido_blackedecker = $numero_pedido";

	if (strlen ($nota_fiscal) > 0) {
		$sql =	"SELECT DISTINCT tbl_pedido.pedido                                              ,
						TO_CHAR(tbl_pedido.data,'DD/MM/YYYY')       AS data            ,
						tbl_status_pedido.descricao                 AS status
				FROM      tbl_pedido
				LEFT JOIN tbl_status_pedido ON tbl_status_pedido.status_pedido = tbl_pedido.status_pedido
				WHERE   tbl_pedido.fabrica = $login_fabrica
				AND     tbl_pedido.posto   = $login_posto";
	}


//echo nl2br($sql);
		if (strlen($numero_pedido) > 0) {
			$sql .= " AND tbl_faturamento.pedido_fabricante = '$numero_pedido'";
		}
		
		if (strlen($numero_nf) > 0) {
			$numero_nf = str_replace(".","",$numero_nf);
			$numero_nf = str_replace("-","",$numero_nf);
			$numero_nf = str_replace("/","",$numero_nf);
			$sql .= " AND tbl_faturamento.nota_fiscal = '$numero_nf'";
		}
		
/*		$sql .=	" GROUP BY  tbl_pedido.pedido                 ,
							tbl_pedido.data                   ,
							tbl_faturamento.pedido_fabricante ,
							tbl_status.descricao";*/
		$sql .= "ORDER BY pedido DESC";
				
//if ($ip == "201.43.246.49" ) echo $sql;

	$res = pg_exec($con,$sql);
	
#	if (getenv("REMOTE_ADDR") == "201.0.9.216") echo nl2br($sql) . "<br>" . pg_numrows($res);
	
	$resultado = pg_numrows($res);

	if (pg_numrows($res) > 0) {
		echo "<table width='400' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
		echo "<tr class='Titulo' height='15'>";
		echo "<td colspan='3'>POSIÇÃO DE PEDIDOS</td>";
		echo "</tr>";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$pedido            = trim(pg_result($res,$i,pedido));
			$pedido_fabricante = trim(pg_result($res,$i,pedido_fabricante));
			$status            = trim(pg_result($res,$i,status));
			$data              = trim(pg_result($res,$i,data));
			
			if ($i == 0) {
				echo "<tr class='Titulo' height='15'>";
				echo "<td>PEDIDO</td>";
				echo "<td>STATUS</td>";
				echo "<td>DATA</td>";
				echo "</tr>";
			}
			
			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
			
			echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
			echo "<td nowrap align='center'><a href='$PHP_SELF?pedido=$pedido'>" . $pedido_fabricante . "</a></td>";
			echo "<td nowrap>" . $status . "</td>";
			echo "<td nowrap align='center'>" . $data . "</td>";
			echo "</tr>";
		}
		echo "</table>";
	}
	echo "<h3><b><center>Resultado: $resultado registro(s).</center></b></h3>";
}

if (strlen($pedido) > 0 && strlen($acao) == 0) {
	$sql =	"SELECT DISTINCT tbl_faturamento.pedido                                                     ,
					tbl_faturamento.pedido_fabricante                                          ,
					tbl_faturamento.nota_fiscal                                                ,
					TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY')          AS emissao          ,
					TO_CHAR(tbl_faturamento.previsao_chegada,'DD/MM/YYYY') AS previsao_chegada ,
					tbl_faturamento.total_nota                                                 ,
					tbl_faturamento.transp                                 AS transportadora
			FROM      tbl_faturamento
			LEFT JOIN tbl_transportadora ON tbl_transportadora.transportadora = tbl_faturamento.transportadora
			WHERE tbl_faturamento.posto   = $login_posto
			AND   tbl_faturamento.fabrica = $login_fabrica
			AND   tbl_faturamento.pedido  = $pedido
			AND   tbl_faturamento.pedido NOTNULL;";
	$res = pg_exec($con,$sql);
	
	
	$resultado = pg_numrows($res);
	
	if (pg_numrows($res) > 0) {
		echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$pedido            = trim(pg_result($res,$i,pedido));
			$pedido_fabricante = trim(pg_result($res,$i,pedido_fabricante));
			$nota_fiscal       = trim(pg_result($res,$i,nota_fiscal));
			$emissao           = trim(pg_result($res,$i,emissao));
			$total_nota        = trim(pg_result($res,$i,total_nota));
			$previsao_chegada  = trim(pg_result($res,$i,previsao_chegada));
			$transportadora    = trim(pg_result($res,$i,transportadora));
			
			if ($i == 0) {
				echo "<tr class='Titulo' height='15'>";
				echo "<td colspan='5'>DESDOBRAMENTO DO PEDIDO $pedido_fabricante</td>";
				echo "</tr>";
				echo "<tr class='Titulo' height='15'>";
				echo "<td>Nº NOTA</td>";
				echo "<td>EMISSÃO</td>";
				echo "<td>VALOR</td>";
				echo "<td>PREVISÃO</td>";
				echo "<td>TRANSPORTADORA</td>";
				echo "</tr>";
			}

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

			echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
			echo "<td nowrap align='center'><a href='$PHP_SELF?pedido=$pedido&nota=$nota_fiscal'>" . $nota_fiscal . "</a></td>";
			echo "<td nowrap align='center'>" . $emissao . "</td>";
			echo "<td nowrap align='right'>R$ " . number_format($total_nota,2,",",".") . "</td>";
			echo "<td nowrap align='center'>" . $previsao_chegada . "</td>";
			echo "<td nowrap>" . $transportadora . "</td>";
			echo "</tr>";
		}
		echo "</table>";
	}
	echo "<h3><b><center>Resultado: $resultado registro(s).</center></b></h3>";
}

if (strlen($pedido) > 0 && strlen($nota) > 0 && strlen($acao) == 0) {
	$sql =	"SELECT DISTINCT tbl_peca.referencia       AS peca_referencia ,
					tbl_peca.descricao        AS peca_descricao  ,
					tbl_peca.ipi              AS peca_ipi        ,
					tbl_faturamento_item.qtde AS peca_qtde       ,
					tbl_faturamento_item.preco
			FROM tbl_faturamento
			JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
									 AND tbl_faturamento_item.pedido      = tbl_faturamento.pedido
			JOIN tbl_peca             ON tbl_peca.peca                    = tbl_faturamento_item.peca
			WHERE tbl_faturamento.posto       = $login_posto
			AND   tbl_faturamento.fabrica     = $login_fabrica
			AND   tbl_faturamento.pedido      = $pedido
			AND   tbl_faturamento.nota_fiscal = $nota
			AND   tbl_faturamento.pedido NOTNULL;";
	$res = pg_exec($con,$sql);

#	if (getenv("REMOTE_ADDR") == "201.0.9.216") echo nl2br($sql) . "<br>" . pg_numrows($res);
	
	$resultado = pg_numrows($res);
	
	if (pg_numrows($res) > 0) {
		echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$peca_referencia = trim(pg_result($res,$i,peca_referencia));
			$peca_descricao  = trim(pg_result($res,$i,peca_descricao));
			$peca_qtde       = trim(pg_result($res,$i,peca_qtde));
			$peca_ipi        = trim(pg_result($res,$i,peca_ipi));
			$preco           = trim(pg_result($res,$i,preco));

			if ($i == 0) {
				echo "<tr class='Titulo' height='15'>";
				echo "<td colspan='7'>DESDOBRAMENTO DA NOTA FISCAL $nota</td>";
				echo "</tr>";
				echo "<tr class='Titulo' height='15'>";
				echo "<td>PEÇA REFERÊNCIA</td>";
				echo "<td>PEÇA DESCRIÇÃO</td>";
				echo "<td>QTDE</td>";
				echo "<td>IPI</td>";
				echo "<td>UNITÁRIO</td>";
				echo "<td NOWRAP>TOTAL COM IPI</td>";
				echo "</tr>";
			}

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

			echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
			echo "<td nowrap align='center'>" . $peca_referencia . "</td>";
			echo "<td nowrap> " . $peca_descricao . " </td>";
			echo "<td nowrap align='center'>" . $peca_qtde . "</td>";
			echo "<td nowrap align='center'>" . $peca_ipi . "</td>";
			echo "<td nowrap align='center'>" . number_format ($preco,2,",",".") . "</td>";
			
			if (strlen ($peca_ipi) == 0) $peca_ipi = 0 ;
			$total = $preco * (1 + ($peca_ipi / 100) ) * $peca_qtde ;
			echo "<td nowrap align='center'>" . number_format ($total,2,",",".") . "</td>";

			echo "</tr>";
		}
		echo "</table>";
	}
	echo "<h3><b><center>Resultado: $resultado registro(s).</center></b></h3>";
}

echo "<a href='javascript: history.back();'>VOLTAR</a>";
echo "<br>";

include "rodape.php";
?>

<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include 'funcoes.php';



$liberar_preco = true ;
if ($login_fabrica == 3 and $login_e_distribuidor <> true and ($login_distribuidor == 1007 or $login_distribuidor == 560)) $liberar_preco = false;


$title = "Tabela de Preços";

$layout_menu = 'preco';
include "cabecalho.php";

?>

<? include "javascript_pesquisas.php" ?>


<style>
.menu {
	font-size: 10px;
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-weight: bold;
	text-align: center;
	color: #FFFFFF;
	background-color: #596D9B
}

.conteudo {
	font-size: 10px;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-weight: normal;
	color: #000000;
}
</style>

<br>

<?
$sql ="SELECT   trim(tbl_tipo_posto.descricao)  AS descricao,
				tbl_tipo_posto.acrescimo_tabela_base        ,
				tbl_tipo_posto.acrescimo_tabela_base_venda  ,
				tbl_condicao.acrescimo_financeiro           ,
				((100 - tbl_icms.indice) / 100) AS icms     ,
				tbl_posto_fabrica.pedido_em_garantia
		FROM    tbl_posto
		JOIN    tbl_posto_fabrica    on tbl_posto_fabrica.posto   = tbl_posto.posto
									and tbl_posto_fabrica.fabrica = $login_fabrica
		JOIN    tbl_fabrica          on tbl_fabrica.fabrica       = tbl_posto_fabrica.fabrica
		JOIN    tbl_tipo_posto       on tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
		JOIN    tbl_condicao         on tbl_condicao.fabrica      = $login_fabrica
									and tbl_condicao.condicao     = 50
		JOIN    tbl_icms             on tbl_icms.estado_destino   = tbl_posto.estado
		WHERE   tbl_fabrica.estado        = tbl_icms.estado_origem
		AND     tbl_posto_fabrica.posto   = $login_posto
		AND     tbl_posto_fabrica.fabrica = $login_fabrica;";
$res = pg_exec($con,$sql);

if (pg_numrows($res) > 0) {
	$icms                        = pg_result($res, 0, icms);
	$descricao                   = pg_result($res, 0, descricao);
	$acrescimo_tabela_base       = pg_result($res, 0, acrescimo_tabela_base);
	$acrescimo_tabela_base_venda = pg_result($res, 0, acrescimo_tabela_base_venda);
	$acrescimo_financeiro        = pg_result($res, 0, acrescimo_financeiro);
	$pedido_em_garantia          = pg_result($res, 0, pedido_em_garantia);
	
	if (1 == 2) {
		$sql = "SELECT  tbl_posto_condicao.acrescimo_tabela_base      ,
						tbl_posto_condicao.acrescimo_tabela_base_venda,
						tbl_posto_condicao.acrescimo_financeiro
				FROM    tbl_posto
				JOIN    tbl_posto_fabrica    on tbl_posto_fabrica.posto   = tbl_posto.posto
											and tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN    tbl_fabrica          on tbl_fabrica.fabrica       = tbl_posto_fabrica.fabrica
				JOIN    tbl_posto_condicao   on tbl_posto_condicao.posto  = tbl_posto.posto
				JOIN    tbl_condicao         on tbl_condicao.condicao     = tbl_posto_condicao.condicao
											and tbl_condicao.fabrica      = $login_fabrica
				WHERE   tbl_posto_fabrica.posto   = $login_posto
				AND     tbl_posto_fabrica.fabrica = $login_fabrica
				AND     tbl_posto_condicao.acrescimo_tabela_base NOTNULL
				AND     tbl_posto_condicao.acrescimo_financeiro  NOTNULL;";
		$resx = pg_exec($con,$sql);
		
		if (pg_numrows($resx) > 0) {
			$acrescimo_tabela_base       = pg_result($resx, 0, acrescimo_tabela_base);
			$acrescimo_tabela_base_venda = pg_result($resx, 0, acrescimo_tabela_base_venda);
			$acrescimo_financeiro        = pg_result($resx, 0, acrescimo_financeiro);
		}
	}
	
	
	switch ( substr($descricao,0,3) ) {
		case "Dis" :
			$sql =	"SELECT y.peca                                                                                                                       ,
							y.referencia                                                                                                                 ,
							y.descricao                                                                                                                  ,
							y.ipi                                                                                                                        ,
							(tbl_tabela_item.preco / $icms)                                                                                    AS preco  ,
							(y.compra / $icms) * (1 + (y.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro                    AS distrib,
							(tbl_tabela_item.preco / $icms) * (1 + (y.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro / 0.7 AS venda
					FROM (
							SELECT  tbl_peca.peca                  ,
									tbl_peca.referencia            ,
									tbl_peca.descricao             ,
									tbl_peca.ipi                   ,
									tbl_tabela_item.preco AS compra
							FROM tbl_tabela_item
							JOIN tbl_tabela ON tbl_tabela.tabela = tbl_tabela_item.tabela
							JOIN tbl_peca   ON tbl_peca.peca     = tbl_tabela_item.peca
							WHERE tbl_tabela.fabrica = $login_fabrica
							AND   tbl_tabela.sigla_tabela = 'BASE2'
					) AS y
					JOIN tbl_tabela_item ON tbl_tabela_item.peca = y.peca
					JOIN tbl_tabela      ON tbl_tabela.tabela    = tbl_tabela_item.tabela
					JOIN tbl_peca        ON tbl_peca.peca        = tbl_tabela_item.peca
					WHERE tbl_tabela.fabrica      = $login_fabrica
					AND   tbl_tabela.sigla_tabela = 'BASE2'
					ORDER BY y.referencia;";
			//if ($ip == "201.0.9.216") echo $sql;
			$res = pg_exec($con,$sql);
			
			if (pg_numrows($res) > 0) {
				$data = date ("d-m-Y-H-i");
				
				echo `mkdir /tmp/blackedecker`;
				echo `chmod 777 /tmp/blackedecker`;
				echo `rm /var/www/assist/www/download/distribuidor-tabela-$data-$login_posto.xls`;
				$fp = fopen ("/tmp/blackedecker/distribuidor-tabela-$data-$login_posto.html","w");
				
				fputs ($fp,"<html>");
				fputs ($fp,"<head>");
				fputs ($fp,"<title>TABELA - $data - $nome_fabrica");
				fputs ($fp,"</title>");
				fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
				fputs ($fp,"</head>");
				fputs ($fp,"<body>");
				
				fputs ($fp,"<table width='500' border='0' cellpadding='2' cellspacing='1' align='center'>");
				fputs ($fp,"<tr class='Menu'>");
				fputs ($fp,"<td nowrap colspan='5'>". strtoupper($descricao) ."</td>");
				fputs ($fp,"</tr>");
				
				fputs ($fp,"<tr class='Menu'>");
				fputs ($fp,"<td nowrap>REFERÊNCIA</td>");
				fputs ($fp,"<td nowrap>DESCRIÇÃO</td>");
				fputs ($fp,"<td nowrap>IPI</td>");
				fputs ($fp,"<td nowrap>COMPRA<br>Sem IPI</td>");
				fputs ($fp,"<td nowrap>DISTRIBUIÇÃO<br>Com IPI</td>");
				fputs ($fp,"<td nowrap>PREÇO<br>SUGERIDO<br>Com IPI</td>");
				fputs ($fp,"</tr>");
				
				echo "<table width='500' border='0' cellpadding='2' cellspacing='1' align='center'>";
				echo "<tr class='Menu'>";
				echo "<td nowrap colspan='6'>PARA LOCALIZAR UMA PEÇA, TECLE \"CTRL + F\" E INFORME A REFERÊNCIA DA PEÇA</td>";
				echo "</tr>";
				echo "<tr class='Menu'>";
				echo "<td nowrap colspan='6'>". strtoupper($descricao) ."</td>";
				echo "</tr>";
				
				for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
					if ($i % 20 == 0 or $zz == 1) {
						echo "<tr class='Menu'>";
						echo "<td nowrap>REFERÊNCIA</td>";
						echo "<td nowrap>DESCRIÇÃO</td>";
						echo "<td nowrap>IPI</td>";
						echo "<td nowrap>COMPRA<br>Sem IPI</td>";
						echo "<td nowrap>DISTRIBUIÇÃO<br>Com IPI</td>";
						echo "<td nowrap>PREÇO<br>SUGERIDO<br>Com IPI</td>";
						echo "</tr>";
					}
					
					$peca             = pg_result($res, $i, peca);
					$peca_referencia  = pg_result($res, $i, referencia);
					$peca_descricao   = pg_result($res, $i, descricao);
					$ipi              = pg_result($res, $i, ipi);
					$preco            = pg_result($res, $i, preco);
					$preco_distrib    = pg_result($res, $i, distrib);
					$preco_venda      = pg_result($res, $i, venda);
					
					$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
					
					fputs ($fp,"<tr class='Conteudo' bgcolor='$cor'>");
					fputs ($fp,"<td nowrap>'$peca_referencia</td>");
					fputs ($fp,"<td nowrap>$peca_descricao</td>");
					fputs ($fp,"<td nowrap align='right'>$ipi%</td>");
					fputs ($fp,"<td nowrap align='right'>R$ " . number_format($preco,2,",", ".") . "</td>");
					fputs ($fp,"<td nowrap align='right'>R$ " . number_format($preco_distrib,2,",", ".") . "</td>");
					fputs ($fp,"<td nowrap align='right'>R$ " . number_format($preco_venda,2,",", ".") . "</td>");
					fputs ($fp,"</tr>");
					
					echo "<tr class='Conteudo' bgcolor='$cor'>";
					echo "<td nowrap>$peca_referencia</td>";
					echo "<td nowrap>$peca_descricao</td>";
					echo "<td nowrap align='right'>$ipi%</td>";
					echo "<td nowrap align='right'>R$ " . number_format($preco,2,",", ".") . "</td>";
					echo "<td nowrap align='right'>R$ " . number_format($preco_distrib,2,",", ".") . "</td>";
					echo "<td nowrap align='right'>R$ " . number_format($preco_venda,2,",", ".") . "</td>";
					echo "</tr>";
				}
				
				echo "</table>";
				
				fputs ($fp,"</table>");
				fputs ($fp,"</body>");
				fputs ($fp,"</html>");
				fclose ($fp);
				
				echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /var/www/assist/www/download/distribuidor-tabela-$data-$login_posto.xls /tmp/blackedecker/distribuidor-tabela-$data-$login_posto.html`;
				
				echo "<br>";
				
				echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
				echo"<tr>";
				echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font>";
				echo "<a href='download/distribuidor-tabela-$data-$login_posto.xls'>";
				echo "<font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em XLS</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
				echo "</tr>";
				echo "</table>";
			}
		break;
		case "Vip" :
			$sql =	"SELECT y.peca                                                                            ,
							y.referencia                                                                      ,
							y.descricao                                                                       ,
							y.ipi                                                                             ,
							(tbl_tabela_item.preco * $acrescimo_tabela_base / $icms)                 AS preco ,
							(tbl_tabela_item.preco / $icms) * (1 + (y.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro / 0.7 AS venda
					FROM (
							SELECT  tbl_peca.peca                  ,
									tbl_peca.referencia            ,
									tbl_peca.descricao             ,
									tbl_peca.ipi                   ,
									tbl_tabela_item.preco AS compra
							FROM tbl_tabela_item
							JOIN tbl_tabela ON tbl_tabela.tabela = tbl_tabela_item.tabela
							JOIN tbl_peca   ON tbl_peca.peca     = tbl_tabela_item.peca
							WHERE tbl_tabela.fabrica = $login_fabrica
							AND   tbl_tabela.sigla_tabela = 'BASE2'
					) AS y
					JOIN tbl_tabela_item ON tbl_tabela_item.peca = y.peca
					JOIN tbl_tabela      ON tbl_tabela.tabela    = tbl_tabela_item.tabela
					JOIN tbl_peca        ON tbl_peca.peca        = tbl_tabela_item.peca
					WHERE tbl_tabela.fabrica      = $login_fabrica
					AND   tbl_tabela.sigla_tabela = 'BASE2'
					ORDER BY y.referencia;";
			//if ($ip == "201.0.9.216") echo $sql;
			$res = pg_exec($con,$sql);
			
			if (pg_numrows($res) > 0) {
				$data = date ("d-m-Y-H-i");
				
				echo `mkdir /tmp/blackedecker`;
				echo `chmod 777 /tmp/blackedecker`;
				echo `rm /var/www/assist/www/download/vip-tabela-$data-$login_posto.xls`;
				$fp = fopen ("/tmp/blackedecker/vip-tabela-$data-$login_posto.html","w");
				
				fputs ($fp,"<html>");
				fputs ($fp,"<head>");
				fputs ($fp,"<title>TABELA - $data - $nome_fabrica");
				fputs ($fp,"</title>");
				fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
				fputs ($fp,"</head>");
				fputs ($fp,"<body>");
				
				fputs ($fp,"<table width='500' border='0' cellpadding='2' cellspacing='1' align='center'>");
				fputs ($fp,"<tr class='Menu'>");
				fputs ($fp,"<td nowrap colspan='5'>". strtoupper($descricao) ."</td>");
				fputs ($fp,"</tr>");
				
				fputs ($fp,"<tr class='Menu'>");
				fputs ($fp,"<td nowrap>REFERÊNCIA</td>");
				fputs ($fp,"<td nowrap>DESCRIÇÃO</td>");
				fputs ($fp,"<td nowrap>IPI</td>");
				fputs ($fp,"<td nowrap>COMPRA<br>Sem IPI</td>");
				fputs ($fp,"<td nowrap>PREÇO<br>SUGERIDO<br>Com IPI</td>");
				fputs ($fp,"</tr>");
				
				echo "<table width='500' border='0' cellpadding='2' cellspacing='1' align='center'>";
				echo "<tr class='Menu'>";
				echo "<td nowrap colspan='5'>PARA LOCALIZAR UMA PEÇA, TECLE \"CTRL + F\" E INFORME A REFERÊNCIA DA PEÇA</td>";
				echo "</tr>";
				echo "<tr class='Menu'>";
				echo "<td nowrap colspan='5'>". strtoupper($descricao) ."</td>";
				echo "</tr>";
				
				for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
					if ($i % 20 == 0 or $zz == 1) {
						echo "<tr class='Menu'>";
						echo "<td nowrap>REFERÊNCIA</td>";
						echo "<td nowrap>DESCRIÇÃO</td>";
						echo "<td nowrap>IPI</td>";
						echo "<td nowrap>COMPRA<br>Sem IPI</td>";
						echo "<td nowrap>PREÇO<br>SUGERIDO<br>Com IPI</td>";
						echo "</tr>";
					}
					
					$peca             = pg_result($res, $i, peca);
					$peca_referencia  = pg_result($res, $i, referencia);
					$peca_descricao   = pg_result($res, $i, descricao);
					$ipi              = pg_result($res, $i, ipi);
					$preco            = pg_result($res, $i, preco);
					$preco_venda      = pg_result($res, $i, venda);
					
					$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
					
					fputs ($fp,"<tr class='Conteudo' bgcolor='$cor'>");
					fputs ($fp,"<td nowrap>'$peca_referencia</td>");
					fputs ($fp,"<td nowrap>$peca_descricao</td>");
					fputs ($fp,"<td nowrap align='right'>$ipi%</td>");
					fputs ($fp,"<td nowrap align='right'>R$ " . number_format($preco,2,",", ".") . "</td>");
					fputs ($fp,"<td nowrap align='right'>R$ " . number_format($preco_venda,2,",", ".") . "</td>");
					fputs ($fp,"</tr>");
					
					echo "<tr class='Conteudo' bgcolor='$cor'>";
					echo "<td nowrap>$peca_referencia</td>";
					echo "<td nowrap>$peca_descricao</td>";
					echo "<td nowrap align='right'>$ipi%</td>";
					echo "<td nowrap align='right'>R$ " . number_format($preco,2,",", ".") . "</td>";
					echo "<td nowrap align='right'>R$ " . number_format($preco_venda,2,",", ".") . "</td>";
					echo "</tr>";
				}
				
				echo "</table>";
				
				fputs ($fp,"</table>");
				fputs ($fp,"</body>");
				fputs ($fp,"</html>");
				fclose ($fp);
				
				echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /var/www/assist/www/download/vip-tabela-$data-$login_posto.xls /tmp/blackedecker/vip-tabela-$data-$login_posto.html`;
				
				echo "<br>";
				
				echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
				echo"<tr>";
				echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font>";
				echo "<a href='download/vip-tabela-$data-$login_posto.xls'>";
				echo "<font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em XLS</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
				echo "</tr>";
				echo "</table>";
			}
		break;
		case "Loc" :
			$sql =	"SELECT y.peca                                                                            ,
							y.referencia                                                                      ,
							y.descricao                                                                       ,
							y.ipi                                                                             ,
							(tbl_tabela_item.preco * $acrescimo_tabela_base / $icms)                  AS preco
					FROM (
							SELECT  tbl_peca.peca                  ,
									tbl_peca.referencia            ,
									tbl_peca.descricao             ,
									tbl_peca.ipi                   ,
									tbl_tabela_item.preco AS compra
							FROM tbl_tabela_item
							JOIN tbl_tabela ON tbl_tabela.tabela = tbl_tabela_item.tabela
							JOIN tbl_peca   ON tbl_peca.peca     = tbl_tabela_item.peca
							WHERE tbl_tabela.fabrica = $login_fabrica
							AND   tbl_tabela.sigla_tabela = 'BASE2'
					) AS y
					JOIN tbl_tabela_item ON tbl_tabela_item.peca = y.peca
					JOIN tbl_tabela      ON tbl_tabela.tabela    = tbl_tabela_item.tabela
					JOIN tbl_peca        ON tbl_peca.peca        = tbl_tabela_item.peca
					WHERE tbl_tabela.fabrica      = $login_fabrica
					AND   tbl_tabela.sigla_tabela = 'BASE2'
					ORDER BY y.referencia;";
			//if ($ip == "201.0.9.216") echo $sql;
			$res = pg_exec($con,$sql);
			
			if (pg_numrows($res) > 0) {
				$data = date ("d-m-Y-H-i");
				
				echo `mkdir /tmp/blackedecker`;
				echo `chmod 777 /tmp/blackedecker`;
				echo `rm /var/www/assist/www/download/locador-tabela-$data-$login_posto.xls`;
				$fp = fopen ("/tmp/blackedecker/locador-tabela-$data-$login_posto.html","w");
				
				fputs ($fp,"<html>");
				fputs ($fp,"<head>");
				fputs ($fp,"<title>TABELA - $data - $nome_fabrica");
				fputs ($fp,"</title>");
				fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
				fputs ($fp,"</head>");
				fputs ($fp,"<body>");
				
				fputs ($fp,"<table width='500' border='0' cellpadding='2' cellspacing='1' align='center'>");
				fputs ($fp,"<tr class='Menu'>");
				fputs ($fp,"<td nowrap colspan='5'>". strtoupper($descricao) ."</td>");
				fputs ($fp,"</tr>");
				
				fputs ($fp,"<tr class='Menu'>");
				fputs ($fp,"<td nowrap>REFERÊNCIA</td>");
				fputs ($fp,"<td nowrap>DESCRIÇÃO</td>");
				fputs ($fp,"<td nowrap>IPI</td>");
				fputs ($fp,"<td nowrap>COMPRA<br>Sem IPI</td>");
				fputs ($fp,"</tr>");
				
				echo "<table width='500' border='0' cellpadding='2' cellspacing='1' align='center'>";
				echo "<tr class='Menu'>";
				echo "<td nowrap colspan='4'>PARA LOCALIZAR UMA PEÇA, TECLE \"CTRL + F\" E INFORME A REFERÊNCIA DA PEÇA</td>";
				echo "</tr>";
				echo "<tr class='Menu'>";
				echo "<td nowrap colspan='4'>". strtoupper($descricao) ."</td>";
				echo "</tr>";
				
				for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
					if ($i % 20 == 0 or $zz == 1) {
						echo "<tr class='Menu'>";
						echo "<td nowrap>REFERÊNCIA</td>";
						echo "<td nowrap>DESCRIÇÃO</td>";
						echo "<td nowrap>IPI</td>";
						echo "<td nowrap>COMPRA<br>Sem IPI</td>";
						echo "</tr>";
					}
					
					$peca             = pg_result($res, $i, peca);
					$peca_referencia  = pg_result($res, $i, referencia);
					$peca_descricao   = pg_result($res, $i, descricao);
					$ipi              = pg_result($res, $i, ipi);
					$preco            = pg_result($res, $i, preco);
					
					$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
					
					fputs ($fp,"<tr class='Conteudo' bgcolor='$cor'>");
					fputs ($fp,"<td nowrap>'$peca_referencia</td>");
					fputs ($fp,"<td nowrap>$peca_descricao</td>");
					fputs ($fp,"<td nowrap align='right'>$ipi%</td>");
					fputs ($fp,"<td nowrap align='right'>R$ " . number_format($preco,2,",", ".") . "</td>");
					fputs ($fp,"</tr>");
					
					echo "<tr class='Conteudo' bgcolor='$cor'>";
					echo "<td nowrap>$peca_referencia</td>";
					echo "<td nowrap>$peca_descricao</td>";
					echo "<td nowrap align='right'>$ipi%</td>";
					echo "<td nowrap align='right'>R$ " . number_format($preco,2,",", ".") . "</td>";
					echo "</tr>";
				}
				
				echo "</table>";
				
				fputs ($fp,"</table>");
				fputs ($fp,"</body>");
				fputs ($fp,"</html>");
				fclose ($fp);
				
				echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /var/www/assist/www/download/locador-tabela-$data-$login_posto.xls /tmp/blackedecker/locador-tabela-$data-$login_posto.html`;
				
				echo "<br>";
				
				echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
				echo"<tr>";
				echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font>";
				echo "<a href='download/locador-tabela-$data-$login_posto.xls'>";
				echo "<font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em XLS</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
				echo "</tr>";
				echo "</table>";
			}
		break;
		default :
			$sql =	"SELECT y.peca                                                                                             ,
							y.referencia                                                                                       ,
							y.descricao                                                                                        ,
							y.ipi                                                                                              ,
							(tbl_tabela_item.preco / $icms)                                                           AS preco ,
							(y.compra / $icms) * (1 + (y.ipi / 100)) * $acrescimo_tabela_base * $acrescimo_financeiro AS compra,
							(tbl_tabela_item.preco / $icms) * (1 + (y.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro / 0.7 AS venda
					FROM (
							SELECT  tbl_peca.peca                  ,
									tbl_peca.referencia            ,
									tbl_peca.descricao             ,
									tbl_peca.ipi                   ,
									tbl_tabela_item.preco AS compra
							FROM tbl_tabela_item
							JOIN tbl_tabela ON tbl_tabela.tabela = tbl_tabela_item.tabela
							JOIN tbl_peca   ON tbl_peca.peca     = tbl_tabela_item.peca
							WHERE tbl_tabela.fabrica = $login_fabrica
							AND   tbl_tabela.sigla_tabela = 'BASE2'
					) AS y
					JOIN tbl_tabela_item ON tbl_tabela_item.peca = y.peca
					JOIN tbl_tabela      ON tbl_tabela.tabela    = tbl_tabela_item.tabela
					JOIN tbl_peca        ON tbl_peca.peca        = tbl_tabela_item.peca
					WHERE tbl_tabela.fabrica      = $login_fabrica
					AND   tbl_tabela.sigla_tabela = 'BASE2'
					ORDER BY y.referencia;";
			//if ($ip == "201.0.9.216") echo $sql;
			$res = pg_exec($con,$sql);
			
			if (pg_numrows($res) > 0) {
				$data = date ("d-m-Y-H-i");
				
				echo `mkdir /tmp/blackedecker`;
				echo `chmod 777 /tmp/blackedecker`;
				echo `rm /var/www/assist/www/download/autorizado-tabela-$data-$login_posto.xls`;
				$fp = fopen ("/tmp/blackedecker/autorizado-tabela-$data-$login_posto.html","w");
				
				fputs ($fp,"<html>");
				fputs ($fp,"<head>");
				fputs ($fp,"<title>TABELA - $data - $nome_fabrica");
				fputs ($fp,"</title>");
				fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
				fputs ($fp,"</head>");
				fputs ($fp,"<body>");
				
				fputs ($fp,"<table width='500' border='0' cellpadding='2' cellspacing='1' align='center'>");
				fputs ($fp,"<tr class='Menu'>");
				fputs ($fp,"<td nowrap colspan='4'>". strtoupper($descricao) ."</td>");
				fputs ($fp,"</tr>");
				
				fputs ($fp,"<tr class='Menu'>");
				fputs ($fp,"<td nowrap>REFERÊNCIA</td>");
				fputs ($fp,"<td nowrap>DESCRIÇÃO</td>");
				//fputs ($fp,"<td nowrap>IPI</td>");
				fputs ($fp,"<td nowrap>COMPRA<br>Com IPI</td>");
				fputs ($fp,"<td nowrap>PREÇO<br>SUGERIDO<br>Com IPI</td>");
				fputs ($fp,"</tr>");
				
				echo "<table width='500' border='0' cellpadding='2' cellspacing='1' align='center'>";
				echo "<tr class='Menu'>";
				echo "<td nowrap colspan='4'>PARA LOCALIZAR UMA PEÇA, TECLE \"CTRL + F\" E INFORME A REFERÊNCIA DA PEÇA</td>";
				echo "</tr>";
				echo "<tr class='Menu'>";
				echo "<td nowrap colspan='5'>". strtoupper($descricao) ."</td>";
				echo "</tr>";
				
				for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
					if ($i % 20 == 0 or $zz == 1) {
						echo "<tr class='Menu'>";
						echo "<td nowrap>REFERÊNCIA</td>";
						echo "<td nowrap>DESCRIÇÃO</td>";
						//echo "<td nowrap>IPI</td>";
						echo "<td nowrap>COMPRA<br>Com IPI</td>";
						echo "<td nowrap>PREÇO<br>SUGERIDO<br>Com IPI</td>";
						echo "</tr>";
					}
					
					$peca             = pg_result($res, $i, peca);
					$peca_referencia  = pg_result($res, $i, referencia);
					$peca_descricao   = pg_result($res, $i, descricao);
					$ipi              = pg_result($res, $i, ipi);
					$preco            = pg_result($res, $i, preco);
					$preco_compra     = pg_result($res, $i, compra);
					$preco_venda      = pg_result($res, $i, venda);
					
					$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
					
					fputs ($fp,"<tr class='Conteudo' bgcolor='$cor'>");
					fputs ($fp,"<td nowrap>'$peca_referencia</td>");
					fputs ($fp,"<td nowrap>$peca_descricao</td>");
					//fputs ($fp,"<td nowrap align='right'>$ipi%</td>");
					fputs ($fp,"<td nowrap align='right'>R$ " . number_format($preco_compra,2,",", ".") . "</td>");
					fputs ($fp,"<td nowrap align='right'>R$ " . number_format($preco_venda,2,",", ".") . "</td>");
					fputs ($fp,"</tr>");
					
					echo "<tr class='Conteudo' bgcolor='$cor'>";
					echo "<td nowrap>$peca_referencia</td>";
					echo "<td nowrap>$peca_descricao</td>";
					//echo "<td nowrap align='right'>$ipi%</td>";
					echo "<td nowrap align='right'>R$ " . number_format($preco_compra,2,",", ".") . "</td>";
					echo "<td nowrap align='right'>R$ " . number_format($preco_venda,2,",", ".") . "</td>";
					echo "</tr>";
				}
				
				echo "</table>";
				
				fputs ($fp,"</table>");
				fputs ($fp,"</body>");
				fputs ($fp,"</html>");
				fclose ($fp);
				
				echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /var/www/assist/www/download/autorizado-tabela-$data-$login_posto.xls /tmp/blackedecker/autorizado-tabela-$data-$login_posto.html`;
				
				echo "<br>";
				
				echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
				echo"<tr>";
				echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font>";
				echo "<a href='download/autorizado-tabela-$data-$login_posto.xls'>";
				echo "<font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em XLS</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
				echo "</tr>";
				echo "</table>";
			}
		break;
	}
}
?>

<br>

<? include "rodape.php"; ?>

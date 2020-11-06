<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$tipo        = $_GET['tipo'];
$nota_fiscal = $_GET['nota_fiscal'];
$data_nf     = $_GET['data_nf'];


$title = "DETALHAMENTO DE NOTA FISCAL";
$layout_menu = 'pedido';

include "cabecalho.php";
?>
<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 0px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line1 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.link{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
}

</style>

<style type="text/css">
a.dica{
position:relative; 
font:10px arial, verdana, helvetica, sans-serif; 
padding:0;
color:#333399;
text-decoration:none;
cursor:help; 
z-index:24;
}

a.dica:hover{
background:transparent;
z-index:25; 
}

a.dica span{display: none}
a.dica:hover span{ 
display:block;
position:absolute;
width:180px; 
text-align:justify;
left:0;
font: 10px arial, verdana, helvetica, sans-serif; 
padding:5px 10px;
border:1px solid #000099;
background:#FFCC00; 
color:#330066;
}
</style>
<?


if (strlen($nota_fiscal) > 0 and strlen($data_nf) > 0 and strlen($tipo) > 0) {
	if ($tipo == "Garantia") {
		$sql = "SELECT  tbl_os.os                                              ,
						tbl_os.sua_os                                          ,
						tbl_os_item.pedido                                     ,
						tbl_peca.referencia||' - '||tbl_peca.descricao as peca ,
						tbl_os_item_nf.qtde_nf                                 ,
						TO_CHAR(tbl_os_item_nf.data_nf,'DD/MM/YYYY') as emissao,
						tbl_os_item_nf.nota_fiscal
				FROM tbl_os
				JOIN tbl_os_produto using(os)
				JOIN tbl_os_item using(os_produto)
				JOIN tbl_os_item_nf using(os_item)
				JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca
				WHERE tbl_os_item_nf.nota_fiscal = '$nota_fiscal'
				AND   tbl_os_item_nf.data_nf     = '$data_nf'
				AND   tbl_os.fabrica             = $login_fabrica
				AND   tbl_os.posto               = $login_posto
				ORDER BY sua_os, peca";
		$res = pg_exec ($con,$sql);
	}

	if ($tipo == "Faturado") {
		$sql = "SELECT  tbl_pedido_item.pedido                                     ,
						tbl_peca.referencia||' - '||tbl_peca.descricao as peca     ,
						tbl_pedido_item_nf.qtde_nf                                 ,
						TO_CHAR(tbl_pedido_item_nf.data_nf,'DD/MM/YYYY') as emissao,
						tbl_pedido_item_nf.nota_fiscal
				FROM tbl_pedido
				JOIN tbl_pedido_item using(pedido)
				JOIN tbl_pedido_item_nf using(pedido_item)
				JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca
				WHERE tbl_pedido_item_nf.nota_fiscal = '$nota_fiscal'
				AND   tbl_pedido_item_nf.data_nf     = '$data_nf'
				AND   tbl_pedido.fabrica             = $login_fabrica
				AND   tbl_pedido.posto               = $login_posto
				ORDER BY peca";
		$res = pg_exec ($con,$sql);
	}


	if (pg_numrows($res) > 0) {
		$emissao		= trim(pg_result($res,0,emissao));
		$nota_fiscal	= trim(pg_result($res,0,nota_fiscal));
		$cond_pg		= "$tipo";

		echo "<br>";

		echo "<table width='650' border='0' cellspacing='1' cellpadding='3' align='center'>\n";
		echo "<tr>\n";
		echo "<td class='menu_top'>NOTA FISCAL</td>\n";
		echo "<td class='menu_top'>EMISSÃO</td>\n";
		echo "<td class='menu_top'>COND.PG.</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$nota_fiscal</font></td>\n";
		echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$emissao</font></td>";
		echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$cond_pg</font></td>";
		echo "</tr>\n";
		echo "</table>\n";

		if (pg_numrows($res) > 0) {
			echo "<table width='650' border='1' cellspacing='1' cellpadding='3' align='center'>\n";
			echo "<tr>\n";
			echo "<td class='menu_top'>#</td>\n";
			echo "<td class='menu_top'>PEÇA</td>\n";
			echo "<td class='menu_top'>QTDE</td>\n";
			echo "<td class='menu_top'>PEDIDO</td>\n";
			
			if ($tipo == "Garantia") {
				echo "<td class='menu_top'>O.S.</td>\n";
			}

			echo "</tr>\n";
			
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$peca        = trim(pg_result($res,$i,peca));
				$qtde        = trim(pg_result($res,$i,qtde_nf));
				$pedido      = trim(pg_result($res,$i,pedido));
				
				if ($tipo == "Garantia") {
					$sua_os      = trim(pg_result($res,$i,sua_os));
					$os          = trim(pg_result($res,$i,os));
				}

				$cor = "#ffffff";
				if ($i % 2 == 0) $cor = "#DDDDEE";

				echo "<tr style='font-size: 10px' bgcolor='$cor'>\n";
				echo "<td align='left' nowrap>" . ($i+1) . "&nbsp;&nbsp;&nbsp;</td>\n";
				echo "<td align='left' nowrap>$peca</td>\n";
				echo "<td align='right'>$qtde</font></td>\n";
				echo "<td align='left'>$pedido</td>\n";

				if ($tipo == "Garantia") {
					echo "<td nowrap align='center'><a href='os_press.php?os=$os' target='_blank'>".$sua_os."</a></td>\n";
				}

				echo "</tr>\n";
			}
			
			echo "</table>\n";
		}
	}
}
?>

<p>

<? include "rodape.php"; ?>
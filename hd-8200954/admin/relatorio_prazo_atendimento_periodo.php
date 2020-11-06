<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "gerencia";
include 'autentica_admin.php';

$layout_menu = "gerencia";
$title = "Relatório de Prazos de Atendimento";

include "cabecalho.php";
/*TAKASHI ALTEROU 16-04-07 MONTOU O PERL QUE GERA TODA NOITE A TABELA, PERL TINHA SIDO PERDIDO*/
?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B;
}

a:link.top   { color:#ffffff; }
a:visited.top{ color:#ffffff; }
a:hover.top  { color:#ffffff; }

.table_linex {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}

</style>

<?
echo "<br>";

include "imagem_relatorio.php"; 

echo "<p>";

if (pg_numrows($res) > 0) {
	$soma_5  = $nv_5 + $sp_5 + $pne_5 + $nf_5;
	$soma_10 = $nv_10 + $sp_10 + $pne_10 + $nf_10;
	$soma_15 = $nv_15 + $sp_15 + $pne_15 + $nf_15;
	$soma_20 = $nv_20 + $sp_20 + $pne_20 + $nf_20;
	$soma_25 = $nv_25 + $sp_25 + $pne_25 + $nf_25;
	$soma_30 = $nv_30 + $sp_30 + $pne_30 + $nf_30;
	
	echo "<table width='700' border='1' cellpadding='0' cellspacing='0' align='center'>\n";
	echo "<tr>\n";
	
	echo "<td width='30%'>&nbsp;</td>\n";
	echo "<td>5 dias</td>\n";
	echo "<td>10 dias</td>\n";
	echo "<td>15 dias</td>\n";
	echo "<td>20 dias</td>\n";
	echo "<td>25 dias</td>\n";
	echo "<td>30 dias</td>\n";
	
	echo "</tr>\n";
	echo "<tr>\n";
	
	echo "<td width='30%' bgcolor='#FFFF33'><font color='#000000' size='2'><b>OS´s não finalizadas</b></font></td>\n";
	echo "<td><a href='relatorio_prazo_atendimento_periodo.php?status=3&dia=5'>$nf_5</a></td>\n";
	echo "<td><a href='relatorio_prazo_atendimento_periodo.php?status=3&dia=10'>$nf_10</a></td>\n";
	echo "<td><a href='relatorio_prazo_atendimento_periodo.php?status=3&dia=15'>$nf_15</a></td>\n";
	echo "<td><a href='relatorio_prazo_atendimento_periodo.php?status=3&dia=20'>$nf_20</a></td>\n";
	echo "<td><a href='relatorio_prazo_atendimento_periodo.php?status=3&dia=25'>$nf_25</a></td>\n";
	echo "<td><a href='relatorio_prazo_atendimento_periodo.php?status=3&dia=30'>$nf_30</a></td>\n";
	
	echo "</tr>\n";
	echo "<tr>\n";
	
	echo "<td width='30%' bgcolor='#339900'><font color='#000000' size='2'><b>OS´s sem peças enviadas</b></font></td>\n";
	echo "<td><a href='relatorio_prazo_atendimento_periodo.php?status=2&dia=5'>$pne_5</a></td>\n";
	echo "<td><a href='relatorio_prazo_atendimento_periodo.php?status=2&dia=10'>$pne_10</a></td>\n";
	echo "<td><a href='relatorio_prazo_atendimento_periodo.php?status=2&dia=15'>$pne_15</a></td>\n";
	echo "<td><a href='relatorio_prazo_atendimento_periodo.php?status=2&dia=20'>$pne_20</a></td>\n";
	echo "<td><a href='relatorio_prazo_atendimento_periodo.php?status=2&dia=25'>$pne_25</a></td>\n";
	echo "<td><a href='relatorio_prazo_atendimento_periodo.php?status=2&dia=30'>$pne_30</a></td>\n";
	
	echo "</tr>\n";
	echo "<tr>\n";
	
	echo "<td width='30%' bgcolor='#66CCFF'><font color='#000000' size='2'><b>OS´s sem peças</b></font></td>\n";
	echo "<td><a href='relatorio_prazo_atendimento_periodo.php?status=1&dia=5'>$sp_5</a></td>\n";
	echo "<td><a href='relatorio_prazo_atendimento_periodo.php?status=1&dia=10'>$sp_10</a></td>\n";
	echo "<td><a href='relatorio_prazo_atendimento_periodo.php?status=1&dia=15'>$sp_15</a></td>\n";
	echo "<td><a href='relatorio_prazo_atendimento_periodo.php?status=1&dia=20'>$sp_20</a></td>\n";
	echo "<td><a href='relatorio_prazo_atendimento_periodo.php?status=1&dia=25'>$sp_25</a></td>\n";
	echo "<td><a href='relatorio_prazo_atendimento_periodo.php?status=1&dia=30'>$sp_30</a></td>\n";
	
	echo "</tr>\n";
	echo "<tr>\n";
	
	echo "<td width='30%' bgcolor='#FF0000'><font color='#000000' size='2'><b>OS´s não vistas</b></font></td>\n";
	echo "<td><a href='relatorio_prazo_atendimento_periodo.php?status=0&dia=5'>$nv_5</a></td>\n";
	echo "<td><a href='relatorio_prazo_atendimento_periodo.php?status=0&dia=10'>$nv_10</a></td>\n";
	echo "<td><a href='relatorio_prazo_atendimento_periodo.php?status=0&dia=15'>$nv_15</a></td>\n";
	echo "<td><a href='relatorio_prazo_atendimento_periodo.php?status=0&dia=20'>$nv_20</a></td>\n";
	echo "<td><a href='relatorio_prazo_atendimento_periodo.php?status=0&dia=25'>$nv_25</a></td>\n";
	echo "<td><a href='relatorio_prazo_atendimento_periodo.php?status=0&dia=30'>$nv_30</a></td>\n";
	
	echo "</tr>\n";
	echo "<tr>\n";
	
	echo "<td width='30%' bgcolor='#FFFFFF'><b>TOTAL</b></td>\n";
	echo "<td><b>". number_format($soma_5,1,".","") ."</b></td>\n";
	echo "<td><b>". number_format($soma_10,1,".","") ."</b></td>\n";
	echo "<td><b>". number_format($soma_15,1,".","") ."</b></td>\n";
	echo "<td><b>". number_format($soma_20,1,".","") ."</b></td>\n";
	echo "<td><b>". number_format($soma_25,1,".","") ."</b></td>\n";
	echo "<td><b>". number_format($soma_30,1,".","") ."</b></td>\n";
	
	echo "</tr>\n";
	echo "</table>\n";
}

$status = trim ($_GET['status']);
$dia    = trim ($_GET['dia']);

if (strlen($status) > 0) {
	switch ($status) {
		case 0:
			$titulo = "OS´s não vistas";
			$cor    = "#FF0000";
			$sql = "SELECT      tbl_os.os                                               ,
								tbl_os.sua_os                                           ,
								to_char (tbl_os.data_digitacao,'DD/MM/YYYY') AS data    ,
								to_char (tbl_os.data_abertura ,'DD/MM/YYYY') AS abertura,
								tbl_os.serie
					FROM        tbl_os
					JOIN        tbl_os_extra USING (os) ";
			if ($dia == 30) $sql .= " WHERE tbl_os.data_digitacao <= (current_date - interval '$dia days')::date ";
			else            $sql .= " WHERE tbl_os.data_digitacao BETWEEN (current_date - interval '$dia days')::date AND current_date ";
			$sql .= "AND        tbl_os.finalizada     ISNULL
					AND         tbl_os_extra.impressa ISNULL
					AND         tbl_os.fabrica = $login_fabrica
					ORDER BY    tbl_os.data_digitacao DESC;";
		break;
		
		case 1:
			$titulo = "OS´s sem peças";
			$cor    = "#66CCFF";
			$sql = "SELECT      tbl_os.os                                               ,
								tbl_os.sua_os                                           ,
								to_char (tbl_os.data_digitacao,'DD/MM/YYYY') AS data    ,
								to_char (tbl_os.data_abertura ,'DD/MM/YYYY') AS abertura,
								tbl_os.serie
					FROM        tbl_os
					JOIN        tbl_os_extra   ON tbl_os_extra.os        = tbl_os.os
					JOIN        tbl_os_produto ON tbl_os_produto.os      = tbl_os.os
					LEFT JOIN   tbl_os_item    ON tbl_os_item.os_produto = tbl_os_produto.os_produto ";
			if ($dia == 30) $sql .= " WHERE tbl_os.data_digitacao <= (current_date - interval '$dia days')::date ";
			else            $sql .= " WHERE tbl_os.data_digitacao BETWEEN (current_date - interval '$dia days')::date AND current_date ";
			$sql .= "AND        tbl_os.finalizada      ISNULL
					AND         tbl_os_item.os_produto ISNULL
					AND         tbl_os.fabrica = $login_fabrica
					ORDER BY    tbl_os.data_digitacao DESC;";
		break;
		
		case 2:
			$titulo = "OS´s sem peças enviadas";
			$cor    = "#339900";
			$sql = "SELECT      tbl_os.os                                               ,
								tbl_os.sua_os                                           ,
								to_char (tbl_os.data_digitacao,'DD/MM/YYYY') AS data    ,
								to_char (tbl_os.data_abertura ,'DD/MM/YYYY') AS abertura,
								tbl_os.serie
					FROM        tbl_os
					JOIN        tbl_os_extra    ON tbl_os_extra.os        = tbl_os.os
					JOIN        tbl_os_produto  ON tbl_os_produto.os      = tbl_os.os
					JOIN        tbl_os_item     ON tbl_os_item.os_produto = tbl_os_produto.os_produto
					JOIN        tbl_pedido      ON tbl_pedido.pedido      = tbl_os_item.pedido 
 					LEFT JOIN   tbl_faturamento ON tbl_faturamento.pedido = tbl_pedido.pedido ";
			if ($dia == 30) $sql .= " WHERE tbl_os.data_digitacao <= (current_date - interval '$dia days')::date ";
			else            $sql .= " WHERE tbl_os.data_digitacao BETWEEN (current_date - interval '$dia days')::date AND current_date ";
			$sql .= "AND        tbl_os.finalizada      ISNULL ";
		if($login_fabrica<>14){ $sql .= " AND         tbl_faturamento.pedido ISNULL ";}
					$sql .= " AND         tbl_os.fabrica = $login_fabrica
					ORDER BY    tbl_os.data_digitacao DESC;";
		break;
		
		case 3:
			$titulo = "OS´s não finalizadas";
			$cor    = "#FFFF33";
			$sql = "SELECT      tbl_os.os                                               ,
								tbl_os.sua_os                                           ,
								to_char (tbl_os.data_digitacao,'DD/MM/YYYY') AS data    ,
								to_char (tbl_os.data_abertura ,'DD/MM/YYYY') AS abertura,
								tbl_os.serie
					FROM        tbl_os
					JOIN        tbl_os_extra    ON tbl_os_extra.os        = tbl_os.os
					JOIN        tbl_os_produto  ON tbl_os_produto.os      = tbl_os.os
					JOIN        tbl_os_item     ON tbl_os_item.os_produto = tbl_os_produto.os_produto
					JOIN        tbl_pedido      ON tbl_pedido.pedido      = tbl_os_item.pedido ";
		if($login_fabrica<>14){ $sql .= " JOIN        tbl_faturamento ON tbl_faturamento.pedido = tbl_pedido.pedido ";}
			if ($dia == 30) $sql .= " WHERE tbl_os.data_digitacao <= (current_date - interval '$dia days')::date ";
			else            $sql .= " WHERE tbl_os.data_digitacao BETWEEN (current_date - interval '$dia days')::date AND current_date ";
			$sql .= "AND        tbl_os.finalizada      ISNULL
					AND         tbl_os.fabrica = $login_fabrica
					ORDER BY    tbl_os.data_digitacao DESC;";
		break;
	}
//echo $sql;exit;
	$res0 = pg_exec ($con,$sql);
	
	if (pg_numrows($res0) > 0) {
	echo "<center><font color='#000000' size='2'>Total de registro: ". pg_numrows($res0) . " </font></center>";
		echo "<p>";
		echo "<table width='700' border='1' cellpadding='0' cellspacing='0' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td colspan='4' bgcolor='$cor'><b><font color='#000000' size='2'>$titulo</font></b></td>\n";
		
		echo "</tr>\n";
		echo "<tr>\n";
		
		echo "<td>OS</td>\n";
		echo "<td>DIGITAÇÃO</td>\n";
		echo "<td>ABERTURA</td>\n";
		echo "<td>SÉRIE</td>\n";
		
		echo "</tr>\n";
		
		for ($i = 0; $i < pg_numrows($res0); $i++) {
			$os       = trim(pg_result($res0,$i,os));
			$sua_os   = trim(pg_result($res0,$i,sua_os));
			$data     = trim(pg_result($res0,$i,data));
			$abertura = trim(pg_result($res0,$i,abertura));
			$serie    = trim(pg_result($res0,$i,serie));
			
			echo "<tr>\n";
			
			echo "<td><a href='os_press.php?os=$os' target='_blank'>$sua_os</a>&nbsp;</td>\n";
			echo "<td>$data&nbsp;</td>\n";
			echo "<td>$abertura&nbsp;</td>\n";
			echo "<td>$serie&nbsp;</td>\n";
			
			echo "</tr>\n";
		}
		echo "</table>\n";
	}
}

include "rodape.php"; 

?>
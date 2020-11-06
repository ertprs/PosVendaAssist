<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$peca = $_GET['peca'];
?>
<style type='text/css'>
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
</style>

<?

if(isset($_GET['peca'])) {
	$sql = " SELECT peca,referencia,descricao
			FROM tbl_peca
			WHERE fabrica = $login_fabrica
			AND   referencia = '$peca' limit 1";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
		$aux_peca = pg_fetch_result($res,0,'peca');

		$sql2 = " SELECT os,
					codigo_posto,
					tbl_os.sua_os      ,
					to_char(data_abertura,'DD/MM/YYYY') as data_abertura,
					to_char(data_fechamento,'DD/MM/YYYY') as data_fechamento,
					tbl_os_item.qtde
			FROM tbl_os
			JOIN tbl_os_produto USING(os)
			JOIN tbl_os_item USING(os_produto)
			JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_os.fabrica = tbl_posto_fabrica.fabrica
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_os.posto = $login_posto
			AND   tbl_os.data_fechamento IS NULL
			AND   tbl_os.excluida IS NOT TRUE
			AND   tbl_os_item.peca = $aux_peca";
		$res2 = pg_query($con,$sql2);
		
		if(pg_num_rows($res2) > 0){
			echo "<table border='0' width='100%' cellpadding='4' cellspacing='1' align='rigth'><tr style='background-color:#596d9b; font: bold 14px Arial; color:#FFFFFF; text-align:center;'><td colspan='4'>". pg_result ($res,0,referencia) . " - " . pg_result ($res,0,descricao) . "</td></tr></table>";
			echo "<table border='0' cellpadding='4' cellspacing='1' width='100%' bgcolor='#596D9B' align='center' style='font-family: verdana; font-size: 9px'>";
			echo "<tr class='titulo_coluna''>";
			echo "<td>OS</td>";
			echo "<td>Data Abertura</td>";
			echo "<td>Data Fechamento</td>";
			echo "<td>Qtde Peça</td>";
			echo "</tr>";

			for($i =0;$i<pg_num_rows($res2);$i++) {
				$cor = ($i % 2 == 0) ? '#d2d7e1' : "#efeeea"; 
				echo "<tr style='background-color:$cor; text-align:center'>";
				echo "<td><a href='os_press.php?os=".pg_fetch_result($res2,0,os)."' target='_blank'>".pg_fetch_result($res2,0,codigo_posto)."".pg_fetch_result($res2,0,sua_os)."</a></td>";
				echo "<td>".pg_fetch_result($res2,0,data_abertura)."</td>";
				echo "<td>".pg_fetch_result($res2,0,data_fechamento)."</td>";
				echo "<td>".pg_fetch_result($res2,0,qtde)."</td>";
				echo "</tr>";
			}
			echo "</table>";
		}else{
			echo "<h2>Nenhuma OS encontrada para a peça:", pg_result ($res,0,referencia) . " - " . pg_result ($res,0,descricao),"</h2>";
		}
	
	}


	
	
}

?>

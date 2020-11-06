<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$peca          = $_GET['peca'];

if(strlen($_GET['ver_historico'])>0) 
	$ver_historico = $_GET['ver_historico'];
else
	$ver_historico = "f";
?>
<style>
.tabela_ajax table, .tabela_ajax td {
	border: 1px solid #E6E6E6;
	border-collapse: collapse;
	text-align: center;
	font-size: 12px;
}

</style>
<?

if(isset($_GET['peca'])) {

	$data_inicial = date('Y-m-d',strtotime(date('Y-m-d'). ' -6 month'));

	if(strlen($peca)>0){
		$sqlP = "SELECT peca,
						referencia,
						descricao
				FROM tbl_peca
				WHERE referencia = '$peca'
				AND fabrica      = $login_fabrica";
		#echo nl2br($sqlP); #exit;
		$resP = pg_exec($con, $sqlP);

		if(pg_numrows($resP)>0){
			$peca            = pg_result($resP,0,peca);
			$peca_referencia = pg_result($resP,0,referencia);
			$peca_descricao  = pg_result($resP,0,descricao);
			echo "<table border='0' width='100%' cellpadding='2' cellspacing='1' align='rigth' class='tabela_ajax'>";
				echo "<tr style='background-color:#596d9b; font: bold 12px Arial; color:#FFFFFF; text-align:center;'>";
					echo "<td colspan='4'>". $peca_referencia . " - " . $peca_descricao . "</td>";
				echo "</tr>";
			echo "</table>";
		}
	}

	if($ver_historico=='f'){
		### SALDO ANTERIOR ###
		$sqlAT = "SELECT SUM(tbl_estoque_posto_movimento.qtde_entrada) AS compra, 
						 SUM(tbl_estoque_posto_movimento.qtde_saida) AS qtde_utilizada 
				FROM tbl_estoque_posto_movimento 
				WHERE tbl_estoque_posto_movimento.posto = $login_posto 
				AND tbl_estoque_posto_movimento.peca    = $peca
				AND tbl_estoque_posto_movimento.fabrica = $login_fabrica 
				AND (tbl_estoque_posto_movimento.qtde_entrada > 0 OR tbl_estoque_posto_movimento.qtde_entrada IS NULL)
				AND tbl_estoque_posto_movimento.data < '$data_inicial';";
		#echo nl2br($sqlAT); #exit;
		$resAT = pg_exec($con,$sqlAT);

		if(pg_numrows($resAT)>0){
			$sa_compra         = pg_result($resAT,0,compra);
			$sa_qtde_utilizada = pg_result($resAT,0,qtde_utilizada);
		}
	}

	$sqlAN = "SELECT tbl_os.sua_os                                                 ,
					 tbl_estoque_posto_movimento.os                                ,
					 to_char(tbl_estoque_posto_movimento.data,'DD/MM/YYYY') as data,
					 tbl_estoque_posto_movimento.qtde_entrada AS compra            ,
					 tbl_estoque_posto_movimento.qtde_saida AS qtde_utilizada      ,
					 codigo_posto
			FROM tbl_estoque_posto_movimento
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_estoque_posto_movimento.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_os ON tbl_estoque_posto_movimento.os = tbl_os.os AND tbl_os.fabrica = $login_fabrica
			WHERE tbl_estoque_posto_movimento.posto = $login_posto
			AND tbl_estoque_posto_movimento.peca    = $peca
			AND tbl_estoque_posto_movimento.fabrica = $login_fabrica
			AND (tbl_estoque_posto_movimento.qtde_entrada > 0 OR tbl_estoque_posto_movimento.qtde_entrada IS NULL)";
			if($ver_historico=='f')
				$sqlAN .= "AND tbl_estoque_posto_movimento.data >= '$data_inicial'";
			$sqlAN .= "ORDER BY tbl_estoque_posto_movimento.data;";
	#echo nl2br($sqlAN); #exit;

	$resAN = pg_exec($con, $sqlAN);

	if(pg_numrows($resAN)>0){

		if ( $login_fabrica <> 30 && $ver_historico=='f' && strlen($sa_qtde_utilizada)> 0 and strlen($sa_compra) > 0){
			echo "<table border='0' width='100%' cellpadding='2' cellspacing='1' align='rigth' class='tabela_ajax'>";
				echo "<tr style='background-color:#596d9b; font: bold 12px Arial; color:#FFFFFF; text-align:center;'>";
					echo "<td colspan='4'>Mostrando movimentação dos últimos 6 meses, para ver movimentação completa, <a href='#' style='color: #CCCCFF' onclick='javascript:verPecaNegativa(\"$peca_referencia\",$login_posto,\"t\"); return false;'>clique aqui</a></td>";
				echo "</tr>";
			echo "</table>";
		}

		echo "<table border='0' width='100%' cellpadding='4' cellspacing='1' align='rigth' class='tabela_ajax'>";
		echo "<tr style='background-color:#596d9b; font: bold 14px Arial; color:#FFFFFF; text-align:center;'>";
			echo "<td>Data</td>";
			echo "<td>Compra</td>";
			echo "<td>OS</td>";
			echo "<td>Qtde Utilizada</td>";
			echo "<td>Saldo</td>";
		echo "</tr>";

		if($ver_historico=='f'){
			
			if(pg_numrows($resAT)>0){
				$sa_compra         = pg_result($resAT,0,compra);
				$sa_qtde_utilizada = pg_result($resAT,0,qtde_utilizada);

				if(strlen($sa_compra)==0)         $sa_compra = 0;
				if(strlen($sa_qtde_utilizada)==0) $sa_qtde_utilizada = 0;

				$sa_total = $sa_compra - $sa_qtde_utilizada;

				echo "<tr bgcolor='$cor'>";
					echo "<td>Saldo Anterior</td>";
					echo "<td>$sa_compra&nbsp;</td>";
					echo "<td>&nbsp;</td>";
					echo "<td>$sa_qtde_utilizada&nbsp;</td>";
					echo "<td>$sa_total&nbsp;</td>";
				echo "</tr>";
			}
			### SALDO ANTERIOR ###
		}

		$entrada_total = $sa_compra;
		$saida_total   = $sa_qtde_utilizada;

		for($x=0; $x<pg_numrows($resAN); $x++){
			$codigo_posto   = pg_result($resAN,$x,codigo_posto);
			$os             = pg_result($resAN,$x,os);
			$sua_os         = pg_result($resAN,$x,sua_os);
			$compra         = pg_result($resAN,$x,compra);
			$qtde_utilizada = pg_result($resAN,$x,qtde_utilizada);
			$data           = pg_result($resAN,$x,data);

			$entrada_total = $entrada_total + $compra;
			$saida_total   = $saida_total + $qtde_utilizada;
			$qtde_total    = $entrada_total - $saida_total;

			$cor = ($x % 2 == 0) ? '#d2d7e1' : "#efeeea"; 

			echo "<tr bgcolor='$cor'>";
				echo "<td>$data&nbsp;</td>";
				echo "<td>$compra&nbsp;</td>";
				echo "<td><a href='os_press.php?os=$os' target='_blank'>";
					if(strlen($sua_os)>0 and $login_fabrica <> 30) {
						echo $codigo_posto . $sua_os;
					}else{
						echo $sua_os;
					}
				echo "</a>&nbsp;</td>";
				echo "<td>$qtde_utilizada&nbsp;</td>";
				echo "<td>$qtde_total&nbsp;</td>";
			echo "</tr>";
		}

	$cond_os = ($login_fabrica==30) ? "tbl_servico_realizado.troca_de_peca IS TRUE" : "tbl_servico_realizado.peca_estoque IS TRUE";
	
	$sqlOS = "SELECT tbl_os.os                                                ,
					 tbl_os.sua_os                                            ,
					 tbl_os_item.qtde,
					 codigo_posto
			FROM tbl_os
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_os_produto USING(os)
			JOIN tbl_os_item USING(os_produto)
			JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_os.fabrica = tbl_servico_realizado.fabrica
			WHERE tbl_os.fabrica = $login_fabrica
			AND tbl_os.posto     = $login_posto
			AND tbl_os.data_fechamento IS NULL
			AND tbl_os.excluida IS NOT TRUE
			AND tbl_os_item.servico_realizado IS NOT NULL
			AND $cond_os
			AND tbl_os_item.peca = $peca;";
	#echo nl2br($sqlOS); #exit;
	$resOS = pg_exec($con, $sqlOS);
	if(pg_numrows($resOS)>0){
		$total = $qtde_total;
		for($y=0; $y<pg_numrows($resOS); $y++){
			$codigo_posto    = pg_result($resOS,$y,codigo_posto);
			$os              = pg_result($resOS,$y,os);
			$sua_os          = pg_result($resOS,$y,sua_os);
			$qtde            = pg_result($resOS,$y,qtde);

			$tqtde = $tqtde + $qtde;
			$total = $total - $qtde;
			$cor = ($y % 2 == 0) ? '#d2d7e1' : "#efeeea"; 

			echo "<tr bgcolor='$cor'>";
				echo "<td>OS não finalizada&nbsp;</td>";
				echo "<td>&nbsp;</td>";
				if ( $login_fabrica <> 30 ){
					echo "<td><a href='os_press.php?os=$os' target='_blank'> $codigo_posto$sua_os</a>&nbsp;</td>";
				}else{
					echo "<td><a href='os_press.php?os=$os' target='_blank'>$sua_os</a>&nbsp;</td>";
				}
				echo "<td>$qtde&nbsp;</td>";
				echo "<td>$total&nbsp;</td>";
			echo "</tr>";
		}
		
	}

	$saida_total   = $saida_total + $tqtde;
	$qtde_total    = $entrada_total - $saida_total;

	echo "<tr style='background-color:#596d9b; font: bold 14px Arial; color:#FFFFFF; text-align:center;'>";
		echo "<td>Total</td>";
		echo "<td>$entrada_total&nbsp;</td>";
		echo "<td>$qtde_os&nbsp;</td>";
		echo "<td>$saida_total&nbsp;</td>";
		echo "<td>$qtde_total&nbsp;</td>";
	echo "</tr>";
	echo "</table>";
	}
}
?>

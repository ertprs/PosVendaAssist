<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include "../funcoes.php";


$distrib_lote = $_POST['distrib_lote'];
if (strlen($distrib_lote) == 0) $distrib_lote = $_GET['distrib_lote'];

$excluir = $_GET['excluir'];

if (strlen ($distrib_lote) > 0) {
	$sql = "SELECT fabrica FROM tbl_distrib_lote WHERE distrib_lote = $distrib_lote";
	$res = pg_exec ($con,$sql);
	$fabrica = pg_result ($res,0,0);
}
//$fabrica = 15;

if (strlen($excluir) > 0) {

	$res = pg_exec ($con,"BEGIN;");
	$sql = "DELETE FROM tbl_distrib_lote_posto
			WHERE distrib_lote = $distrib_lote
			AND posto = (SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto='$excluir' AND fabrica=$fabrica)";
	$res = pg_exec ($con,$sql);
	if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);

	if (strlen($msg_erro) == 0) {
		$sql = "DELETE FROM tbl_distrib_lote_os
				WHERE distrib_lote = $distrib_lote
				AND os IN (SELECT os FROM tbl_os WHERE posto = (SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto='$excluir' AND fabrica=$fabrica) 
				AND fabrica=$fabrica)";
		$res = pg_exec ($con,$sql);
		if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);
	}
	
	if (strlen($msg_erro) > 0) {
		$res = pg_exec ($con,"ROLLBACK;");
		echo "$msg_erro";
	} else {
		$res = pg_exec ($con,"COMMIT;");
	}
}



echo "<form method='post' name='frm_lote' action='$PHP_SELF'>";
$sql = "SELECT distrib_lote, LPAD (lote::text,6,'0') AS lote, TO_CHAR (fechamento,'DD/MM/YYYY') AS fechamento
		FROM tbl_distrib_lote
		ORDER BY distrib_lote DESC";
$res = pg_exec ($con,$sql);


echo "<select name='distrib_lote' size='1'>";
for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	echo "<option value='" . pg_result ($res,$i,distrib_lote) . "'>" . pg_result ($res,$i,lote) . " - " . pg_result ($res,$i,fechamento) . "</option>";
}
echo "</select>";

echo "<input type='submit' name='btn_acao' value='Imprimir Lote'>";

echo "</form>";



if (strlen ($distrib_lote) > 0) {

	$sql = "SELECT  tbl_posto_fabrica.codigo_posto ,
					tbl_posto.nome                 ,
					qtde.med_qtde_os               ,
					media.med_qtde_pecas           ,
					custo.med_custo                ,
					lote.qtde_os                   ,
					lote.mao_de_obra               ,
					lote.mobra_total
			FROM    tbl_posto
			JOIN    tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $fabrica
			JOIN   (SELECT tbl_os.posto, tbl_produto.mao_de_obra, SUM (tbl_produto.mao_de_obra) AS mobra_total, COUNT (tbl_os.os) AS qtde_os
					FROM tbl_os
					JOIN tbl_distrib_lote_os ON tbl_os.os = tbl_distrib_lote_os.os
					JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
					WHERE tbl_distrib_lote_os.distrib_lote = $distrib_lote
					GROUP BY tbl_os.posto , tbl_produto.mao_de_obra
			) lote ON tbl_posto.posto = lote.posto
			LEFT JOIN   (SELECT tbl_os.posto, COUNT (tbl_os.os) AS med_qtde_os
					FROM tbl_os
					JOIN tbl_distrib_lote_os ON tbl_os.os = tbl_distrib_lote_os.os
					WHERE tbl_distrib_lote_os.distrib_lote = $distrib_lote
					GROUP BY tbl_os.posto
			) qtde  ON tbl_posto.posto = qtde.posto
			LEFT JOIN   (SELECT tbl_os.posto, SUM (tbl_os_item.qtde) AS med_qtde_pecas
					FROM tbl_os
					JOIN tbl_distrib_lote_os ON tbl_os.os = tbl_distrib_lote_os.os
					JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
					JOIN tbl_os_item    ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					WHERE tbl_distrib_lote_os.distrib_lote = $distrib_lote
					GROUP BY tbl_os.posto
			) media ON tbl_posto.posto = media.posto
			LEFT JOIN   (SELECT tbl_os.posto, SUM (tbl_os_item.qtde * tbl_tabela_item.preco) AS med_custo
					FROM tbl_os
					JOIN tbl_distrib_lote_os ON tbl_os.os = tbl_distrib_lote_os.os
					JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
					JOIN tbl_os_item    ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					JOIN tbl_produto    ON tbl_os.produto = tbl_produto.produto
					JOIN tbl_posto_linha ON tbl_os.posto = tbl_posto_linha.posto AND tbl_produto.linha = tbl_posto_linha.linha
					JOIN tbl_tabela_item ON tbl_posto_linha.tabela = tbl_tabela_item.tabela AND tbl_os_item.peca = tbl_tabela_item.peca
					WHERE tbl_distrib_lote_os.distrib_lote = $distrib_lote
					GROUP BY tbl_os.posto
			) custo ON tbl_posto.posto = custo.posto
			ORDER BY tbl_posto.nome	";
	$res = pg_exec ($con,$sql);

	$sql = "SELECT LPAD (lote::text,6,'0') AS lote , TO_CHAR (fechamento,'DD/MM/YYYY') AS fechamento FROM tbl_distrib_lote WHERE distrib_lote = $distrib_lote";
	$resX = pg_exec ($con,$sql);

	echo "<center><h1>Lote " . pg_result ($resX,0,lote) . " de " . pg_result ($resX,0,fechamento) . "</h1></center>";

	echo "<table border='1' cellspacing='0' cellpadding='2'>";
	echo "<tr align='center' bgcolor='#6666FF'>";
	echo "<td nowrap><b>Código</b></td>";
	echo "<td nowrap><b>Nome</b></td>";
	echo "<td nowrap><b>Peças por OS</b></td>";
	echo "<td nowrap><b>Custo Médio</b></td>";

	$sql = "SELECT DISTINCT tbl_produto.mao_de_obra
			FROM tbl_os
			JOIN tbl_distrib_lote_os ON tbl_os.os = tbl_distrib_lote_os.os
			JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
			ORDER BY tbl_produto.mao_de_obra ";

	$resX = pg_exec ($con,$sql);
	for ($i = 0 ; $i < pg_numrows ($resX) ; $i++) {
		echo "<td nowrap><b> R$ " . number_format (pg_result ($resX,$i,mao_de_obra),2,",",".") . "</b></td>";
		$array_mo[$i][1] = pg_result ($resX,$i,mao_de_obra) ;
		$array_mo[$i][2] = 0 ;
		$array_mo[$i][3] = 0 ;
	}
	$qtde_cab = $i;

	echo "<td nowrap><b>MObra Total</b></td>";
	echo "</tr>";


	$qtde_total_os = 0 ;
	$mobra_total   = 0 ;
	$mobra_posto   = 0 ;
	$total_total   = 0 ;


	$codigo_posto_ant = pg_result ($res,0,codigo_posto);
	$nome_ant         = pg_result ($res,0,nome);
	if (pg_result ($res,0,med_qtde_os) > 0) {
		$media_pecas_ant = pg_result ($res,0,med_qtde_pecas) / pg_result ($res,0,med_qtde_os);
		$custo_ant       = pg_result ($res,0,med_custo)      / pg_result ($res,0,med_qtde_os);
	}else{
		$media_pecas_ant = 0;
		$custo_ant       = 0;
	}


	for ($i = 0 ; $i < pg_numrows ($res) +1; $i++) {
		if ($i == pg_numrows ($res) ) $codigo_posto = "*";
		
		if ($codigo_posto <> "*") {
			$codigo_posto = pg_result ($res,$i,codigo_posto);
			$nome         = pg_result ($res,$i,nome);
			if (pg_result ($res,$i,med_qtde_os) > 0) {
				$media_pecas = pg_result ($res,$i,med_qtde_pecas) / pg_result ($res,$i,med_qtde_os);
				$custo       = pg_result ($res,$i,med_custo)      / pg_result ($res,$i,med_qtde_os);
			}else{
				$media_pecas = 0;
				$custo       = 0;
			}
		}
		

		if ($codigo_posto_ant <> $codigo_posto) {
			echo "<tr style='font-size:10px'>";

			echo "<td nowrap>";
			echo $codigo_posto_ant;
			echo "</td>";

			echo "<td nowrap>";
			echo $nome_ant;
			echo "</td>";

			echo "<td nowrap align='center'>";
			echo number_format ($media_pecas_ant,1,",",".");
			echo "</td>";

			echo "<td nowrap align='center'>";
			echo number_format ($custo_ant,2,",",".");
			echo "</td>";

			for ($x = 0 ; $x < $qtde_cab ; $x++) {
				echo "<td align='right'>";
				$qtde_os = $array_mo [$x][2];
				if ($qtde_os > 0) {
					echo $qtde_os ;
				}else{
					echo "&nbsp;";
				}
				echo "</td>";
				
				$array_mo[$x][3] = $array_mo[$x][3] + $array_mo[$x][2];

				$array_mo[$x][2] = 0;
			}
			
			echo "<td align='right'><b>";
			echo number_format ($mobra_posto,2,",",".");
			echo "</b></td>";

			echo "<td>";
			echo "<a href=\"javascript: if (confirm('Deseja realmente excluir do lote o posto $codigo_posto_ant - $nome_ant?') == true) { window.location='$PHP_SELF?excluir=$codigo_posto_ant&distrib_lote=$distrib_lote'; } \">Excluir</A>";
			echo "</td>";


			$total_total += $mobra_posto ;
			$mobra_posto = 0 ;
			
			if ($codigo_posto == "*") break ;
			
			$codigo_posto_ant = $codigo_posto ;
			$nome_ant         = $nome ;
			$media_pecas_ant  = $media_pecas ;
			$custo_ant        = $custo ;
		}

		$mao_de_obra = pg_result ($res,$i,mao_de_obra);
		$qtde_os     = pg_result ($res,$i,qtde_os);
		
		$mobra_posto = $mobra_posto + ($qtde_os * $mao_de_obra) ;
		
		for ($x = 0 ; $x < $qtde_cab ; $x++) {
			if ($mao_de_obra == $array_mo [$x][1]) {
				$array_mo [$x][2] = $qtde_os ;
			}
		}
	}

	echo "<tr align='center' bgcolor='#6666FF'>";
	echo "<td colspan='2'><b>Qtde Total de OS</b></td>";

	echo "<td></td>";
	echo "<td></td>";


	for ($x = 0 ; $x < $qtde_cab ; $x++) {
		echo "<td align='right'>";
		$qtde_os = $array_mo [$x][3];
		if ($qtde_os > 0) {
			echo $qtde_os ;
		}else{
			echo "&nbsp;";
		}
		echo "</td>";
	}

	echo "<td align='right'><b>" . number_format ($total_total,2,",",".") . "</b></td>";
	echo "</tr>";

	echo "</table>";

}

?>

<? #include "rodape.php"; ?>

</body>
</html>

<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "gerencia,call_center";
include "autentica_admin.php";

include "funcoes.php";

$layout_menu = "auditoria";

$title = "Visão geral por produto";
#include 'cabecalho.php';

?>

<style type="text/css">
<!--
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
-->
</style>


<p>

<? if (strlen($erro) > 0) { ?>
<table width="420" border="0" cellpadding="2" cellspacing="0" align="center" class="error">
	<tr>
		<td><?echo $erro?></td>
	</tr>
</table>
<br>
<? } ?>

<br>

<?
$x_data_inicial = $_GET['data_inicial'];
$x_data_final   = $_GET['data_final'];
$produto        = $_GET['produto'];
$referencia     = $_GET['referencia'];
$voltagem       = $_GET['voltagem'];
$linha          = $_GET['linha'];
$estado         = $_GET['estado'];
$temp_table = "tmp_black_" . $login_admin ;

$cond_linha     = "1=1";
if (strlen ($linha) > 0) $cond_linha = " $temp_table.linha = $linha ";

$cond_estado    = "1=1";
if (strlen ($estado) > 0) {
	$cond_estado  = " $temp_table.estado = '$estado' ";
}



$sql = "SELECT referencia, voltagem, descricao, produto
		FROM tbl_produto 
		WHERE tbl_produto.referencia_fabrica = '$referencia' ";
$res2 = pg_exec ($con,$sql);
//echo $sql;
for ($y = 0; $y < pg_numrows($res2); $y++) {
	$produto    = pg_result($res2,$y,produto);
	$referencia = pg_result($res2,$y,referencia);
	$voltagem   = pg_result($res2,$y,voltagem);

	if ($relatorio == "gerar" OR 1==1 ) {
		
		flush();
		
		$sql = "SELECT  tbl_posto_fabrica.codigo_posto     ,
						tbl_posto.nome                     ,
						tbl_os.os                          ,
						tbl_os.sua_os                      ,
						tbl_os.codigo_fabricacao           ,
						tbl_os.serie                       ,
						TO_CHAR (tbl_os.data_abertura  ,'DD/MM/YYYY') AS abertura ,
						TO_CHAR (tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento
				FROM    tbl_os
				JOIN    tbl_os_extra   ON tbl_os.os              = tbl_os_extra.os
				JOIN    tbl_produto    ON tbl_produto.produto    = tbl_os.produto
				JOIN    tbl_posto      ON tbl_posto.posto        = tbl_os.posto
				JOIN    tbl_extrato    ON tbl_os_extra.extrato   = tbl_extrato.extrato
				JOIN    tbl_extrato_financeiro ON tbl_os_extra.extrato = tbl_extrato_financeiro.extrato
				JOIN    tbl_posto_fabrica      ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_os.fabrica = tbl_posto_fabrica.fabrica
				WHERE   tbl_os.fabrica = $login_fabrica
				AND     tbl_extrato_financeiro.data_envio BETWEEN '$x_data_inicial' AND '$x_data_final'
				AND     tbl_produto.produto = $produto
				AND     $cond_linha
				AND     $cond_estado
				ORDER BY tbl_os.codigo_fabricacao
				";
		$sql = "SELECT  tbl_posto_fabrica.codigo_posto     ,
						tbl_posto.nome                     ,
						tbl_os.os                          ,
						tbl_os.sua_os                      ,
						tbl_os.codigo_fabricacao           ,
						tbl_os.serie                       ,
						TO_CHAR (tbl_os.data_abertura  ,'DD/MM/YYYY') AS abertura ,
						TO_CHAR (tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento
				FROM $temp_table
				JOIN tbl_os on tbl_os.os = $temp_table.os
				JOIN    tbl_os_extra   ON tbl_os.os              = tbl_os_extra.os
				JOIN    tbl_produto    ON tbl_produto.produto    = tbl_os.produto
				JOIN    tbl_posto      ON tbl_posto.posto        = tbl_os.posto
				JOIN    tbl_extrato    ON tbl_os_extra.extrato   = tbl_extrato.extrato
				JOIN    tbl_extrato_financeiro ON tbl_os_extra.extrato = tbl_extrato_financeiro.extrato
				JOIN    tbl_posto_fabrica      ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_os.fabrica = tbl_posto_fabrica.fabrica
				WHERE   tbl_os.fabrica = $login_fabrica
				AND     $temp_table.produto = $produto
				AND     $cond_linha
				AND     $cond_estado
				ORDER BY tbl_os.codigo_fabricacao
			";

//echo $sql;
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			?>

			<center>
			<font face='arial' color='<? echo $cor_forte ?>'><b><?= "$referencia - $voltagem - " . pg_result ($res2,0,descricao) ?></b></font>
			<center>

			<?

			echo "<table width='700' border='0' cellpadding='2' cellspacing='2' align='center'>";
			echo "<tr>";
			
			echo "<td bgcolor='#B6DADA' align='center'>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>P.A.</b></font>";
			echo "</td>";
			
			echo "<td bgcolor='#B6DADA' align='center' nowrap >";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>Posto Autorizado</b></font>";
			echo "</td>";
			
			echo "<td bgcolor='#B6DADA' align='center' nowrap >";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>O.S.</b></font>";
			echo "</td>";
			
			echo "<td bgcolor='#B6DADA' align='center' nowrap >";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>Cód. Fabricação</b></font>";
			echo "</td>";

			echo "<td bgcolor='#B6DADA' align='center' nowrap >";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>Série</b></font>";
			echo "</td>";
			
			echo "<td bgcolor='#B6DADA' align='center'>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>Abertura</b></font>";
			echo "</td>";
			
			echo "<td bgcolor='#B6DADA' align='center'>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>Fechamento</b></font>";
			echo "</td>";
			
			echo "</tr>";
			
			for ($x = 0; $x < pg_numrows($res); $x++) {
				$os                = pg_result($res,$x,os);
				$codigo_posto      = pg_result($res,$x,codigo_posto);
				$nome              = pg_result($res,$x,nome);
				$sua_os            = pg_result($res,$x,sua_os);
				$codigo_fabricacao = pg_result($res,$x,codigo_fabricacao);
				$serie             = pg_result($res,$x,serie);
				$abertura          = pg_result($res,$x,abertura);
				$fechamento        = pg_result($res,$x,fechamento);
				
			
				$cor = '#EFF5F5';
				
				if ($x % 2 == 0) $cor = '#B6DADA';
				
				echo "<tr>";
				
				echo "<td bgcolor='$cor' align='left' nowrap>";
				echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
				echo $codigo_posto;
				echo "</font>";
				echo "</td>";
				
				echo "<td bgcolor='$cor' align='left' nowrap>";
				echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2' nowrap>";
				echo $nome ;
				echo "</font>";
				echo "</td>";
				
				echo "<td bgcolor='$cor' align='left'>";
				echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
				echo "<a href='os_press.php?os=$os' target='_blank'>";
				echo $sua_os ;
				echo "</a>";
				echo "</td>";
				
				echo "<td bgcolor='$cor' align='left'>";
				echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
				echo $codigo_fabricacao ;
				echo "</td>";
				
				echo "<td bgcolor='$cor' align='left'>";
				echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
				echo $serie ;
				echo "</font>";
				echo "</td>";
				
				echo "<td bgcolor='$cor' align='left'>";
				echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
				echo $abertura ;
				echo "</font>";
				echo "</td>";
				
				echo "<td bgcolor='$cor' align='left'>";
				echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
				echo $fechamento;
				echo "</font>";
				echo "</td>";
				
				echo "</tr>";
				
			}

			echo "</table>";
			
		}
	}
}

echo "<p>";


#include 'rodape.php';
?>

<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro";
include "autentica_admin.php";

$posto   = $_GET['posto'];
$extrato = $_GET['extrato'];

if(strlen($extrato)>0){
	$sqlUp = "  UPDATE tbl_extrato SET
				admin_libera_pendencia = $login_admin     ,
				data_libera_pendencia  = current_timestamp
				WHERE tbl_extrato.extrato = $extrato";
	$resUp = @pg_exec($con,$sqlUp);
	$msg_erro = pg_last_error($con);
}

?>
<style>
.Titulo {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-weight: bold;
	color:#ffffff;
	font-size: 11px;
	border: 1px solid;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 14px;
	font-weight: bold;
	background-color: #FFFFFF;
	text-align: center;
}
.Link{
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	background-color: #FFFFFF;
	text-align: center;
	text-decoration: none;
}
.Erro{
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
	background-color: #FF0000;
	text-align: center;
	color: #FFFFFF;
}
</style>
<?

if(strlen($msg_erro)>0){
	echo "<div class='Erro'>$msg_erro</div>";
}

if(strlen($posto)>0){
	$sql = "SELECT  DISTINCT tbl_extrato.extrato                        ,
					tbl_extrato.total                                   ,
					to_char(data_geracao,'DD/MM/YYYY') as data_geracao  ,
					tbl_posto.nome as posto_nome                        ,
					tbl_posto_fabrica.codigo_posto                      ,
					tbl_posto.posto
			FROM tbl_extrato
			LEFT JOIN tbl_extrato_conferencia ON tbl_extrato.extrato = tbl_extrato_conferencia.extrato
			JOIN tbl_posto ON tbl_extrato.posto = tbl_posto.posto
			JOIN tbl_posto_fabrica ON tbl_extrato.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE   tbl_extrato.fabrica = $login_fabrica
			AND     tbl_extrato.posto   = $posto
			AND     tbl_extrato_conferencia.cancelada IS NOT TRUE
			AND     tbl_extrato.data_geracao > '2008-09-01 00:00:00'
			AND     tbl_extrato_conferencia.nota_fiscal IS NULL
			AND     tbl_extrato_conferencia.caixa IS NULL
			AND     (current_date - tbl_extrato.data_geracao::date) >= 60
			AND     tbl_extrato.admin_libera_pendencia IS NULL
			ORDER BY tbl_extrato.extrato DESC";
			#echo nl2br($sql);
			$res = pg_exec($con,$sql);
			
			echo "<P class='Conteudo'>Extratos Pendentes</P>";
			echo "<TABLE width='600' border='0' cellpadding='2' cellspacing='2' align='center'>";
			echo "<TR class='Titulo'>";
				echo "<TD>Posto</TD>";
				echo "<TD>Extrato</TD>";
				echo "<TD>Data Geração</TD>";
				echo "<TD>Total</TD>";
				echo "<TD>Tirar Pendência</TD>";
			echo "</TR>";

			for($i=0; $i<pg_numrows($res); $i++){
				$extrato      = pg_result($res,$i,extrato);
				$total        = pg_result($res,$i,total);
				$data_geracao = pg_result($res,$i,data_geracao);
				$posto_nome   = pg_result($res,$i,posto_nome);
				$codigo_posto = pg_result($res,$i,codigo_posto);
				$posto        = pg_result($res,$i,posto);

				$cor = ($i % 2 == 0) ? '#F7F5F0' : '#F1F4FA';

				$xtotal = number_format($total,2,",",".");

				echo "<TR bgcolor='$cor' style='font-size:11px;'>";
					echo "<TD>$codigo_posto - $posto_nome</TD>";
					echo "<TD align='center'>$extrato</TD>";
					echo "<TD align='center'>$data_geracao</TD>";
					echo "<TD align='center'>$xtotal</TD>";
					echo "<TD align='center'>
							<A HREF='$PHP_SELF?extrato=$extrato&posto=$posto' class='Link'>OK</A>
						 </TD>";
				echo "</TR>";
			}
			echo "</TABLE>";
}

?>
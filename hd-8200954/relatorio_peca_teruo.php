<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$msg_erro = "";

$layout_menu = "tecnica";
$title = "Relatório de Peças";

include "cabecalho.php";

?>

<p>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF;
}

</style>

<TABLE width='700' align='center' border='0' cellspacing='2' cellpadding='2'>
	<TR class='table_line'>
		<TD colspan="3" height="30" align='center'><font size='2'>Para exibir um relatório, clique em um dos tipos abaixo:</font></TD>
	</TR>
	<TR class='table_line'>
		<td align='center' background='#D9E2EF' width="33%">
			<a href='<? echo $PHP_SELF; ?>?rel=depara'>DE - PARA</a>
		</td>
		<td align='center' background='#D9E2EF' width="34%">
			<a href='<? echo $PHP_SELF; ?>?rel=alternativa'>PEÇAS ALTERNATIVAS</a>
		</td>
		<td align='center' background='#D9E2EF' width="33%">
			<a href='<? echo $PHP_SELF; ?>?rel=foradelinha'>PEÇAS FORA DE LINHA</a>
		</td>
	</TR>
	<TR>
		<TD colspan="3">&nbsp;</TD>
	</TR>
</TABLE>

<?

if (strlen($_GET["rel"]) > 0) {

	$rel = trim($_GET["rel"]);

	switch ($rel) {
		case "depara":
			$sql = "SELECT  tbl_depara.depara,
							tbl_depara.de    ,
							tbl_depara.para  ,
							(
							SELECT tbl_peca.descricao
							FROM   tbl_peca
							WHERE  tbl_peca.referencia = tbl_depara.de
							AND tbl_peca.fabrica = $login_fabrica
							) AS descricao_de,
							(
							SELECT tbl_peca.descricao
							FROM   tbl_peca
							WHERE  tbl_peca.referencia = tbl_depara.para
							AND tbl_peca.fabrica = $login_fabrica
							) AS descricao_para
					FROM    tbl_depara
					WHERE   tbl_depara.fabrica = $login_fabrica
					ORDER BY tbl_depara.depara;";
			$area = "DE - PARA";
		break;
		
		case "alternativa":
			$sql = "SELECT  tbl_peca_alternativa.peca_alternativa,
							tbl_peca_alternativa.de    ,
							tbl_peca_alternativa.para  ,
							(
								SELECT tbl_peca.descricao
								FROM   tbl_peca
								WHERE  tbl_peca.referencia = tbl_peca_alternativa.de
								AND tbl_peca.fabrica = $login_fabrica
							) AS descricao_de,
							(
								SELECT tbl_peca.descricao
								FROM   tbl_peca
								WHERE  tbl_peca.referencia = tbl_peca_alternativa.para
								AND tbl_peca.fabrica = $login_fabrica
							) AS descricao_para
					FROM    tbl_peca_alternativa
					WHERE   tbl_peca_alternativa.fabrica = $login_fabrica
					ORDER BY tbl_peca_alternativa.peca_alternativa";
			$area = "PEÇAS ALTERNATIVAS";
		break;
		
		case "foradelinha":
			$sql = "SELECT  tbl_peca_fora_linha.peca_fora_linha,
							tbl_peca_fora_linha.referencia     ,
							(
								SELECT tbl_peca.descricao
								FROM   tbl_peca
								WHERE  tbl_peca.referencia = tbl_peca_fora_linha.referencia
								AND    tbl_peca.fabrica = $login_fabrica
							) AS descricao
					FROM    tbl_peca_fora_linha
					WHERE   tbl_peca_fora_linha.fabrica = $login_fabrica
					ORDER BY tbl_peca_fora_linha.peca_fora_linha";
			$area = "PEÇAS FORA DE LINHA";
		break;
	}
	$res = pg_exec($con,$sql);

#echo "<br>".nl2br($sql)."<br><br>";


	if (pg_numrows($res) == 0) {
		echo "<TABLE width='700' height='50'><TR><TD align='center'>Nenhuma peça encontrada em $area.</TD></TR></TABLE>";
	}else{
		echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
		echo "<TR class='menu_top'>\n";
		echo "<TD colspan=8>Relatório de $area</TD>\n";
		echo "</TR>\n";
		echo "</TABLE>";

		switch ($rel) {
			case "depara":
				echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='2'>\n";

				echo "<TR class='menu_top'>\n";
				echo "<TD COLSPAN='2' width='50%'>DE</TD>\n";
				echo "<TD COLSPAN='2'>PARA</TD>\n";
				echo "</TR>\n";

				echo "<TR class='menu_top'>\n";
				echo "<TD>REF.</TD>\n";
				echo "<TD>DESCRIÇÃO</TD>\n";
				echo "<TD>REF.</TD>\n";
				echo "<TD>DESCRIÇÃO</TD>\n";
				echo "</TR>\n";

				for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
					$referencia_de   = trim(pg_result($res,$y,de));
					$descricao_de    = trim(pg_result($res,$y,descricao_de));
					$referencia_para = trim(pg_result($res,$y,para));
					$descricao_para  = trim(pg_result($res,$y,descricao_para));

					$cor = "#F7F5F0"; 
					if ($i % 2 == 0) 
						$cor = '#F1F4FA';

					echo "<TR class='table_line' style='background-color: $cor;'>\n";
					echo "<TD align='center'>$referencia_de</TD>\n";
					echo "<TD>$descricao_de</TD>\n";
					echo "<TD align='center'>$referencia_para</TD>\n";
					echo "<TD>$descricao_para</TD>\n";
					echo "</TR>\n";
				}
			break;

			case "alternativa":
				echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='2'>\n";

				echo "<TR class='menu_top'>\n";
				echo "<TD COLSPAN='2' width='50%'>DE</TD>\n";
				echo "<TD COLSPAN='2'>ALTERNATIVA</TD>\n";
				echo "</TR>\n";

				echo "<TR class='menu_top'>\n";
				echo "<TD>REF.</TD>\n";
				echo "<TD>DESCRIÇÃO</TD>\n";
				echo "<TD>REF.</TD>\n";
				echo "<TD>DESCRIÇÃO</TD>\n";
				echo "</TR>\n";

				for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
					$referencia_de   = trim(pg_result($res,$y,de));
					$descricao_de    = trim(pg_result($res,$y,descricao_de));
					$referencia_para = trim(pg_result($res,$y,para));
					$descricao_para  = trim(pg_result($res,$y,descricao_para));

					$cor = "#F7F5F0"; 
					if ($i % 2 == 0) 
						$cor = '#F1F4FA';

					echo "<TR class='table_line' style='background-color: $cor;'>\n";
					echo "<TD align='center'>$referencia_de</TD>\n";
					echo "<TD>$descricao_de</TD>\n";
					echo "<TD align='center'>$referencia_para</TD>\n";
					echo "<TD>$descricao_para</TD>\n";
					echo "</TR>\n";
				}
			break;

			case "foradelinha":
				echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='2'>\n";

				echo "<TR class='menu_top'>\n";
				echo "<TD>REFERÊNCIA</TD>\n";
				echo "<TD>DESCRIÇÃO</TD>\n";
				echo "</TR>\n";

				for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
					$referencia  = trim(pg_result ($res,$i,referencia));
					$descricao   = trim(pg_result ($res,$i,descricao));

					$cor = "#F7F5F0"; 
					if ($i % 2 == 0) 
						$cor = '#F1F4FA';

					echo "<TR class='table_line' style='background-color: $cor;'>\n";
					echo "	<TD align='center'>$referencia</TD>\n";
					echo "	<TD>$descricao</TD>\n";
					echo "</TR>\n";
				}
			break;
		}
	}
		echo "</TABLE>\n";

}

echo "<BR><BR>";

include "rodape.php"; 

?>
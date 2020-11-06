<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$msg_erro = "";

$layout_menu = "tecnica";
$title = "RELATÓRIO DE PEÇAS";
if($sistema_lingua == "ES") $title = "Reporte de piezas";

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

<TABLE width='700' align='center' border='0' cellspacing='2' cellpadding='2'>
	<TR class='table_line'>
		<TD colspan="5" height="30" align='center'><font size='2'><? if($sistema_lingua == "ES") echo "Para exhibir un reporte, haga un clic en un de los tipos abajo:";else echo "Para exibir um relatório, clique em um dos tipos abaixo:";?></font></TD>
	</TR>
	<TR class='table_line'>
		<td align='center' background='#D9E2EF' width="20%">
			<a href='<? echo $PHP_SELF; ?>?rel=depara'><? if($sistema_lingua == "ES") echo "DE - PARA";else echo "DE - PARA";?></a>
		</td>
		<td align='center' background='#D9E2EF' width="20%">
			<a href='<? echo $PHP_SELF; ?>?rel=alternativa'><? if($sistema_lingua == "ES") echo "PIEZAS ALTERNATIVAS";else echo "PEÇAS ALTERNATIVAS";?></a>
		</td>
		<td align='center' background='#D9E2EF' width="20%">
			<a href='<? echo $PHP_SELF; ?>?rel=foradelinha'><? if($sistema_lingua == "ES") echo "PIEZAS DISCONTINUADAS";else echo "PEÇAS FORA DE LINHA";?></a>
		</td>
		<td align='center' background='#D9E2EF' width="20%">
			<a href='<? echo $PHP_SELF; ?>?rel=devolucao_obrigatoria'><? if($sistema_lingua == "ES") echo "DEVOLUCIÓN ";else echo "DEVOLUÇÃO OBRIGATÓRIA";?></a>
		</td>
		<td align='center' background='#D9E2EF' width="20%">
			<a href='<? echo $PHP_SELF; ?>?rel=bloqueada_garantia'><? if($sistema_lingua == "ES") echo "BLOQUEADA PARA GARANTIA";else echo "BLOQUEADA PARA GARANTIA";?></a>
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
							LIMIT 1
							) AS descricao_de,
							(
							SELECT tbl_peca.descricao
							FROM   tbl_peca
							WHERE  tbl_peca.referencia = tbl_depara.para
							AND tbl_peca.fabrica = $login_fabrica
							LIMIT 1
							) AS descricao_para
					FROM    tbl_depara
					WHERE   tbl_depara.fabrica = $login_fabrica
					ORDER BY tbl_depara.depara;";
			if($sistema_lingua == "ES") $area = "DE - PARA";
			else                        $area = "DE - PARA";
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
			if($sistema_lingua == "ES") $area = "PIEZAS ALTERNATIVAS";
			else                        $area = "PEÇAS ALTERNATIVAS";
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
			if($sistema_lingua == "ES") $area = "PIEZAS FUERA DE LÍNEA";
			else                        $area = "PEÇAS FORA DE LINHA";
		break;

		case "bloqueada_garantia":
			$sql = "SELECT  peca                  ,
							referencia            ,
							descricao             ,
							origem                ,
							unidade               ,
							peso                  ,
							garantia_diferenciada ,
							item_aparencia
					FROM	tbl_peca
					WHERE	fabrica               = $login_fabrica
					AND bloqueada_garantia IS true
					ORDER BY descricao ASC, referencia ASC";
			if($sistema_lingua == "ES") $area = "BLOQUEADA PARA GARANTIA";
			else                        $area = "BLOQUEADA PARA GARANTIA";
		break;

		case "devolucao_obrigatoria":
			$sql = "SELECT  peca                  ,
							referencia            ,
							descricao             ,
							origem                ,
							unidade               ,
							peso                  ,
							garantia_diferenciada ,
							item_aparencia
					FROM	tbl_peca
					WHERE	fabrica               = $login_fabrica
					AND devolucao_obrigatoria IS true
					ORDER BY descricao ASC, referencia ASC";
			if($sistema_lingua == "ES") $area = "DEVOLUCIÓN OBLIGATÓRIA";
			else                        $area = "DEVOLUÇÃO OBRIGATÓRIA";
		break;
	}
	$res = @pg_exec($con,$sql);

#echo "<br>".nl2br($sql)."<br><br>";


	if (@pg_numrows($res) == 0) {
		echo "<TABLE width='700' height='50'><TR><TD align='center'>";
		if($sistema_lingua == "ES") echo "Ninguna pieza encuentarda en ";
		else                        echo "Nenhuma peça encontrada em ";
		echo "$area.";
		echo "</TD></TR></TABLE>";
	}else{
		echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
		echo "<TR class='titulo_tabela'>\n";
		echo "<TD colspan=8>";
		if($sistema_lingua == "ES") echo "REPORTE DE";
		else                        echo "RELATÓRIO DE ";
		echo " $area</TD>\n";
		echo "</TR>\n";
		echo "</TABLE>";

		switch ($rel) {
			case "depara":
				echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='2'>\n";

				echo "<TR class='titulo_coluna'>\n";
				echo "<TD COLSPAN='2' width='50%'>";
				if($sistema_lingua ==  "ES") echo "DE";
				else                         echo "DE";
				echo "</TD>\n";
				echo "<TD COLSPAN='2'>";
				if($sistema_lingua ==  "ES") echo "PARA";
				else                         echo "PARA";
				echo "</TD>\n";
				echo "</TR>\n";

				echo "<TR class='titulo_coluna'>\n";
				echo "<TD>";
				if($sistema_lingua ==  "ES") echo "Referencia";
				else                         echo "Referência";
				echo "</TD>\n";
				echo "<TD>";
				if($sistema_lingua ==  "ES") echo "Descripción";
				else                         echo "Descrição";
				echo "</TD>\n";
				echo "<TD>";
				if($sistema_lingua ==  "ES") echo "Referencia";
				else                         echo "Referência";
				echo "</TD>\n";
				echo "<TD>";
				if($sistema_lingua ==  "ES") echo "Descripción";
				else                         echo "Descrição";
				echo "</TD>\n";
				echo "</TR>\n";

				for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
					$referencia_de   = trim(pg_result($res,$i,de));
					$descricao_de    = trim(pg_result($res,$i,descricao_de));
					$referencia_para = trim(pg_result($res,$i,para));
					$descricao_para  = trim(pg_result($res,$i,descricao_para));

					//--=== Tradução para outras linguas ============================= Raphael HD:1212
					$sql_idioma = "SELECT * FROM tbl_peca_idioma LEFT JOIN tbl_peca using(peca) WHERE referencia ='$referencia_de' AND upper(idioma) = '$sistema_lingua'";


					$res_idioma = @pg_exec($con,$sql_idioma);
					if (@pg_numrows($res_idioma) >0) {
						$descricao_de  = trim(@pg_result($res_idioma,0,descricao));
					}
					$sql_idioma = "SELECT * FROM tbl_peca_idioma LEFT JOIN tbl_peca using(peca) WHERE referencia ='$referencia_para' AND upper(idioma) = '$sistema_lingua'";


					$res_idioma = @pg_exec($con,$sql_idioma);
					if (@pg_numrows($res_idioma) >0) {
						$descricao_para  = trim(@pg_result($res_idioma,0,descricao));
					}
					//--=== Tradução para outras linguas ==============================



					$cor = "#F7F5F0";
					if ($i % 2 == 0)
						$cor = '#F1F4FA';

					echo "<TR class='table_line' style='background-color: $cor;'>\n";
					echo "<TD align='center' nowrap>$referencia_de</TD>\n";
					echo "<TD>$descricao_de</TD>\n";
					echo "<TD align='center' nowrap>$referencia_para</TD>\n";
					echo "<TD>$descricao_para</TD>\n";
					echo "</TR>\n";
				}
				echo "</table>";
			break;

			case "alternativa":
				echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='2'>\n";

				echo "<TR class='titulo_coluna'>\n";
				echo "<TD COLSPAN='2' width='50%'>DE</TD>\n";
				echo "<TD COLSPAN='2'>ALTERNATIVA</TD>\n";
				echo "</TR>\n";

				echo "<TR class='titulo_coluna'>\n";
				echo "<TD>";
				if($sistema_lingua ==  "ES") echo "Referencia";
				else                         echo "Referência";
				echo "</TD>\n";
				echo "<TD>";
				if($sistema_lingua ==  "ES") echo "Descripción";
				else                         echo "Descrição";
				echo "</TD>\n";
				echo "<TD>";
				if($sistema_lingua ==  "ES") echo "Referencia";
				else                         echo "Referência";
				echo "</TD>\n";
				echo "<TD>";
				if($sistema_lingua ==  "ES") echo "Descripción";
				else                         echo "Descrição";
				echo "</TD>\n";
				echo "</TR>\n";

				for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
					$referencia_de   = trim(pg_result($res,$i,de));
					$descricao_de    = trim(pg_result($res,$i,descricao_de));
					$referencia_para = trim(pg_result($res,$i,para));
					$descricao_para  = trim(pg_result($res,$i,descricao_para));

					//--=== Tradução para outras linguas ============================= Raphael HD:1212
					$sql_idioma = "SELECT * FROM tbl_peca_idioma LEFT JOIN tbl_peca using(peca) WHERE referencia ='$referencia_de' AND upper(idioma) = '$sistema_lingua'";


					$res_idioma = @pg_exec($con,$sql_idioma);
					if (@pg_numrows($res_idioma) >0) {
						$descricao_de  = trim(@pg_result($res_idioma,0,descricao));
					}
					$sql_idioma = "SELECT * FROM tbl_peca_idioma LEFT JOIN tbl_peca using(peca) WHERE referencia ='$referencia_para' AND upper(idioma) = '$sistema_lingua'";


					$res_idioma = @pg_exec($con,$sql_idioma);
					if (@pg_numrows($res_idioma) >0) {
						$descricao_para  = trim(@pg_result($res_idioma,0,descricao));
					}
					//--=== Tradução para outras linguas ==============================

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
				echo "</table>";
			break;

			case "foradelinha":
				echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='2'>\n";

				echo "<TR class='titulo_coluna'>\n";
				echo "<TD>";
				if($sistema_lingua ==  "ES") echo "Referencia.";
				else                         echo "Referência";
				echo "</TD>\n";
				echo "<TD>";
				if($sistema_lingua ==  "ES") echo "Descripción";
				else                         echo "Descrição";
				echo "</TD>\n";
				echo "</TR>\n";

				for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
					$referencia  = trim(pg_result ($res,$i,referencia));
					$descricao   = trim(pg_result ($res,$i,descricao));
					//--=== Tradução para outras linguas ============================= Raphael HD:1212
					$sql_idioma = "SELECT * FROM tbl_peca_idioma LEFT JOIN tbl_peca using(peca) WHERE referencia ='$referencia' AND upper(idioma) = '$sistema_lingua'";


					$res_idioma = @pg_exec($con,$sql_idioma);
					if (@pg_numrows($res_idioma) >0) {
						$descricao  = trim(@pg_result($res_idioma,0,descricao));
					}

					//--=== Tradução para outras linguas ==============================
					$cor = "#F7F5F0";
					if ($i % 2 == 0)
						$cor = '#F1F4FA';

					echo "<TR class='table_line' style='background-color: $cor;'>\n";
					echo "	<TD align='center'>$referencia</TD>\n";
					echo "	<TD>$descricao</TD>\n";
					echo "</TR>\n";
				}
			break;

			case "bloqueada_garantia":
				echo "<table width='700' align='center' border='0' class='conteudo' cellpadding='0' cellspacing='1'>";
				echo "<tr class='titulo_coluna' height='20'>";
				echo "<td align='center'><b>";
				if($sistema_lingua ==  "ES") echo "Referencia";
				else                         echo "Referência";
				echo "</b></td>";
				echo "<td align='center'><b>";
				if($sistema_lingua ==  "ES") echo "Descripción";
				else                         echo "Descrição";
				echo "</b></td>";
				echo "<td align='center'><b>";
				if($sistema_lingua ==  "ES") echo "Origen";
				else                         echo "Origem";
				echo "</b></td>";
				echo "<td align='center'><b>";
				if($sistema_lingua ==  "ES") echo "Unid";
				else                         echo "Unid";
				echo "</b></td>";
				echo "<td align='center'><b>";
				if($sistema_lingua ==  "ES") echo "Peso";
				else                         echo "Peso";
				echo "</b></td>";
				echo "</tr>";

				for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

					$referencia = trim(pg_result ($res,$i,referencia));
					$descricao  = pg_result ($res,$i,descricao);
					//--=== Tradução para outras linguas ============================= Raphael HD:1212
					$sql_idioma = "SELECT * FROM tbl_peca_idioma LEFT JOIN tbl_peca using(peca) WHERE referencia ='$referencia' AND upper(idioma) = '$sistema_lingua'";


					$res_idioma = @pg_exec($con,$sql_idioma);
					if (@pg_numrows($res_idioma) >0) {
						$descricao  = trim(@pg_result($res_idioma,0,descricao));
					}

					//--=== Tradução para outras linguas ==============================
					$bg = ($i%2 == 0) ? '#F7F5F0' : '#F1F4FA';
					echo "<tr class='table_line' height='18'>";
					echo "<td align='left' bgcolor='$bg'>".$referencia."</td>";
					echo "<td align='left' bgcolor='$bg'>".$descricao."</td>";
					echo "<td align='center' bgcolor='$bg'>".pg_result ($res,$i,origem)."</td>";
					echo "<td align='center' bgcolor='$bg'>".pg_result ($res,$i,unidade)."</td>";
					echo "<td align='right' bgcolor='$bg' style='padding-right:5px'>".pg_result ($res,$i,peso)."</td>";
					echo "</tr>";
				}

				echo "</table>";
			break;

			case "devolucao_obrigatoria":
				echo "<table width='700' align='center' border='0' class='conteudo' cellpadding='0' cellspacing='1'>";
				echo "<tr class='titulo_coluna' height='20'>";
				echo "<td align='center'><b>";
				if($sistema_lingua ==  "ES") echo "Referencia";
				else                         echo "Referência";
				echo "</b></td>";
				echo "<td align='center'><b>";
				if($sistema_lingua ==  "ES") echo "Descripción";
				else                         echo "Descrição";
				echo "</b></td>";
				echo "<td align='center'><b>";
				if($sistema_lingua ==  "ES") echo "Origen";
				else                         echo "Origem";
				echo "</b></td>";
				echo "<td align='center'><b>";
				if($sistema_lingua ==  "ES") echo "Unid";
				else                         echo "Unid";
				echo "</b></td>";
				echo "<td align='center'><b>";
				if($sistema_lingua ==  "ES") echo "Peso";
				else                         echo "Peso";
				echo "</b></td>";
				echo "</tr>";

				for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

					$referencia = trim(pg_result ($res,$i,referencia));
					$descricao  = pg_result ($res,$i,descricao);
					//--=== Tradução para outras linguas ============================= Raphael HD:1212
					$sql_idioma = "SELECT * FROM tbl_peca_idioma LEFT JOIN tbl_peca using(peca) WHERE referencia ='$referencia' AND upper(idioma) = '$sistema_lingua'";


					$res_idioma = @pg_exec($con,$sql_idioma);
					if (@pg_numrows($res_idioma) >0) {
						$descricao  = trim(@pg_result($res_idioma,0,descricao));
					}

					//--=== Tradução para outras linguas ==============================
					$bg = ($i%2 == 0) ? '#F7F5F0' : '#F1F4FA';
					echo "<tr class='table_line' height='18'>";
					echo "<td align='left' bgcolor='$bg'>".$referencia."</td>";
					echo "<td align='left' bgcolor='$bg'>".$descricao."</td>";
					echo "<td align='center' bgcolor='$bg'>".pg_result ($res,$i,origem)."</td>";
					echo "<td align='center' bgcolor='$bg'>".pg_result ($res,$i,unidade)."</td>";
					echo "<td align='right' bgcolor='$bg' style='padding-right:5px'>".pg_result ($res,$i,peso)."</td>";
					echo "</tr>";
				}

				echo "</table>";
			break;

		}
	}
		echo "</TABLE>\n";

}

echo "<BR><BR>";

include "rodape.php";

?>
<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$title = " F O R U M ";
$layout_menu = 'tecnica';

$btn_acao = $_POST['btn_acao'];
if (strlen ($btn_acao) > 0 ) {
	$qtde_forum = $_POST['qtde_forum'];
	for ($i = 0 ; $i < $qtde_forum ; $i++) {
		$forum    = $_POST['forum_' . $i];
		$liberado = $_POST['liberado_' . $i];

		if (strlen ($liberado) == 0) $liberado = 'f';
		if ($liberado == "on") $liberado = 't';

		$sql = "UPDATE tbl_forum SET liberado = '$liberado' WHERE forum = $forum";
		$res = pg_exec ($con,$sql);
	}
}


include "cabecalho.php";
?>

<style type='text/css'>

.forum_cabecalho {
	padding: 5px;
	background-color: #FFCC00;
	font-family: arial;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	text-align: center;
	}

.texto {
	padding: 5px;
	font-family: arial;
	font-size: 12px;
	font-weight: bold;
	color: #596D9B;
	text-align: justify;
	}

.corpo {
	padding: 5px;
	font-family: arial;
	font-size: 12px;
	color: #596D9B;
	text-align: justify;
	}

.forum_claro {
	padding: 3px;
	background-color: #CED7E7;
	color: #596D9B;
	text-align: center;
	}


.forum_escuro {
	padding: 3px;
	background-color: #D9E2EF;
	color: #596D9B;
	text-align: center;
	}

a:link.menu {
	color: #63798D;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: bold;
}

a:visited.menu {
	color: #63798D;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: bold;
	text-decoration: none;
}

a:hover.menu {
	color: #FFCC00;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: bold;
}

a:link.forum {
	color: #63798D;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
}

a:visited.forum {
	color: #63798D;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
	text-decoration: none;
}

a:hover.forum {
	color: #0000FF;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
}

a:link.botao {
	padding: 20px,20px,20px,20px;
	background-color: #ffcc00;
	color: #000000;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
}

a:visited.botao {
	padding: 20px,20px,20px,20px;
	background-color: #ffcc00;
	color: #000000;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
	text-decoration: none;
}

a:hover.botao {
	padding: 20px,20px,20px,20px;
	background-color: #596d9b;
	color: #ffffff;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
}

</style>
<br>


<form name='forum' action='<? $PHP_SELF ?>' method='post'>

<?
$sql = "SELECT tbl_forum.forum                                , 
				to_char (tbl_forum.data,'DD/MM/YYYY') AS data , 
				tbl_forum.liberado                            , 
				tbl_forum.titulo                              , 
				tbl_forum.mensagem                            , 
				tbl_posto.nome 
		FROM    tbl_forum 
		JOIN    tbl_posto USING (posto) 
		WHERE   tbl_forum.fabrica = $login_fabrica 
		AND     tbl_forum.data > CURRENT_DATE - INTERVAL '60 days'
		AND tbl_posto.pais = '$login_pais'
		ORDER BY tbl_forum.data DESC";
$res = pg_exec ($con,$sql);

echo "<table border= '0' cellspacing='1' width='400' align='center'>";
echo "<tr bgcolor='#D9E2EF' style='font-size:12px' align='center'>";
echo "<td>Aprobar</td>";
echo "<td>Fecha</td>";
echo "<td>Título</td>";
echo "<td>Mensaje</td>";
echo "<td>Servicio</td>";

for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	echo "<tr style='font-size:12px' align='left'>";

	echo "<input type='hidden' name='forum_$i' value='" . pg_result ($res,$i,forum) . "'>";

	echo "<td>";
	echo "<input type='checkbox' name='liberado_$i' value='t' " ;
	if (pg_result ($res,$i,liberado) == 't') echo " checked ";
	echo ">";
	echo "</td>";

	echo "<td>";
	echo pg_result ($res,$i,data);
	echo "</td>";

	echo "<td>";
	echo pg_result ($res,$i,titulo);
	echo "</td>";

	echo "<td>";
	echo pg_result ($res,$i,mensagem);
	echo "</td>";

	echo "<td>";
	echo pg_result ($res,$i,nome);
	echo "</td>";

	echo "</tr>";
}
echo "</table>";

echo "<input type='hidden' name='qtde_forum' value='$i'>";

?>

<p>

<input type='submit' name='btn_acao' value='Aprobar Mensajes'>
</form>

<? include "rodape.php"; ?>
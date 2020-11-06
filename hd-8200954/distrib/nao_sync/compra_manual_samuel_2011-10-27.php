<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';


echo "<html>";
echo "<head>";
echo "<title>Compra Manual</title>";
?>
<link rel="stylesheet" href="../js/tinybox2/style.css" />
<link type="text/css" rel="stylesheet" href="css/css.css">
<style type='text/css' screen='all'>
.frm {
	background-color:#F0F0F0;
	border:1px solid #888888;
	font-family:Verdana;
	font-size:8pt;
	font-weight:bold;
}
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
	padding: 3px 0;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;

}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
		padding: 2px;
}

.espaco{padding:0 0 0 80px; }
</style>
<script src='js/jquery.js'></script>
<script src='http://www.telecontrol.com.br/js/shortcut.js'></script>
</head>
<body>

<?
include 'menu.php' ;
    function comparaSelect($valor1, $valor2){
        $valor1 == $valor2 ? $select = " selected style='color: #f00' " : $select = "";	
	
	    return $select;
    }
    
	$res_fabrica = pg_exec($con,"SELECT fabrica, nome FROM tbl_fabrica WHERE fabrica IN(51,81)");
	$fabrica = $_POST['fabrica'];
	if(pg_numrows($res_fabrica)){
		while($dado_fabrica = pg_fetch_array($res_fabrica))
			$combo .= "<option value='{$dado_fabrica[0]}' ".comparaSelect($dado_fabrica[0],$fabrica).">{$dado_fabrica[1]}</option>\n";
?>
<br><br>
<table align='center' class='formulario' width='700px' border='0'>
	<tr>
		<td class='titulo_tabela'>Filtro de Fábrica&nbsp;
			<img src="../imagens/help.png" alt="Ajuda da Tela"  onclick='showHelp()'
				style='vertical-align:middle;cursor:help;'
				title='clique ou digite "h" para ajuda sobre as colunas.' />
		</td>
	</tr>
	<tr>
		<td style='padding: 20px; text-align: center; font-weight: normal;'>
		<form method='post'>Fábrica &nbsp;
				<select name='fabrica' class='frm' style='width: 350px;'>
					<option value='' <?=comparaSelect('0',$fabrica)?>>Selecione o Fabricante</option>
					<option value='0' <?=comparaSelect('0',$fabrica)?>>Todos</option>
					<?=$combo?>
				</select>
				<br><br>
				<input type='submit' value=' Filtrar Dados ' />
			</form>
 	   </td>
	</tr>
</table>
<br>
<?	} 
	// Fim Ederson
?>
<script type="text/javascript" src='../js/tinybox2/tinybox.js'></script>
<script type="text/javascript">
	function showHelp() {
		TINY.box.show({iframe:'manual_compra_manual.html',width:600,height:550});
	}
	shortcut.add(
		'h',
		function() {
			showHelp();
	});
</script>
<?
if($fabrica == '' or $fabrica == '0') 
	$sql = "
            SELECT tbl_peca.referencia, tbl_peca.descricao, x_pedido_51.*
              FROM x_pedido_51
              JOIN tbl_peca USING (peca)
             UNION 
            SELECT tbl_peca.referencia, tbl_peca.descricao, x_pedido_81.*
              FROM x_pedido_81
              JOIN tbl_peca USING (peca)
             ORDER BY maior";
else
    $sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, x_pedido_$fabrica.*  FROM x_pedido_$fabrica JOIN tbl_peca USING (peca) ORDER BY x_pedido_$fabrica.maior";

$res  = pg_exec ($con,$sql);

if(pg_num_rows($res)==0) {
	echo "<br><center><b>Não existem peças para compra manual</center></b>";
	include "rodape.php";
	exit;
}?>

<script>
	function ocultar_mostrar(){ 
		$('.ocultar').toggle();
	} 
</script>

<table id='tabela' align='center' border='0' cellspacing='1' cellpadding='1' class='tabela'>
	<tr>
		<td colspan='16' style='text-align: right; padding: 2px; border: none;'>
			<a href='#' class='mostrar' id='campos_mostrar' onclick='ocultar_mostrar();' >MOSTRAR / OCULTAR CAMPOS DE CÁLCULOS</a>
		</td>
	</tr>
	<tr class='titulo_tabela'>
		<td colspan='16'>Peças para Compra Manual</td>
	</tr>
	<tr class='titulo_coluna'>
		<td rowspan='2' valign='middle'>Referência</td>
		<td rowspan='2' valign='middle'>Descrição</td>
		<td rowspan='2' valign='middle'>Comprar</td>
		<td class='ocultar' colspan='8'>Cálculos</td>
		<td rowspan='2' valign='middle'>Mínimo</td>
		<td rowspan='2' valign='middle'>Pedido</td>
		<td rowspan='2' valign='middle'>Estoque</td>
		<td rowspan='2' valign='middle'>Fábrica</td>
		<td rowspan='2' valign='middle'>Transp.</td>
	</tr>
	
<tr class='titulo_coluna'>
	<td class='ocultar'>90d</td>
	<td class='ocultar'>60d</td>
	<td class='ocultar'>30d</td>
	<td class='ocultar'>maior</td>
	<td class='ocultar'>qtdeX</td>
	<td class='ocultar'>media</td>
	<td class='ocultar'>desvio</td>
	<td class='ocultar'>media OK</td>
</tr>
<?
	for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {

		$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

		echo "<tr bgcolor='$cor'>";

		    echo "<td title='Referência'>";				
		        echo pg_result ($res,$i,referencia);
		    echo "</td>";

		    echo "<td title='Descrição'>";
		        echo pg_result ($res,$i,descricao);
		    echo "</td>";

		$estoque_minimo = round (pg_result ($res,$i,media_ok) * pg_result ($res,$i,qtdeX) / 2 , 0);
		$qtde_comprometida = $estoque_minimo + pg_result ($res,$i,qtde);
		$qtde_vindo = pg_result ($res,$i,estoque) + pg_result ($res,$i,fabrica) + pg_result ($res,$i,transp) ;
		$comprar = $qtde_comprometida - $qtde_vindo;
		$comprar_title = "Total comprar: $comprar";
		if ($comprar <= 0) {
			$comprar = '0';
		} else {
			$cor = "#FF9999";
		}

		echo "<td align='right' bgcolor='$cor' title='$comprar_title'>";
		echo $comprar ;
		echo "</td>";




		echo "<td align='right' title='90d' bgcolor='#0099FF' class='ocultar'>";
		echo pg_result ($res,$i,dias90);
		echo "</td>";

		echo "<td align='right' title='60d' bgcolor='#0099FF' class='ocultar'>";
		echo pg_result ($res,$i,dias60);
		echo "</td>";

		echo "<td align='right' title='30d' bgcolor='#0099FF' class='ocultar'>";
		echo pg_result ($res,$i,dias30);
		echo "</td>";

		echo "<td align='right' title='maior' class='ocultar'>";
		echo pg_result ($res,$i,maior);
		echo "</td>";

		echo "<td align='right' title='qtdex' class='ocultar'>";
		echo pg_result ($res,$i,qtdex);
		echo "</td>";

		echo "<td align='right' title='media' class='ocultar'>";
		echo pg_result ($res,$i,media);
		echo "</td>";

		echo "<td align='right' title='desvio' class='ocultar'>";
		echo pg_result ($res,$i,desvio);
		echo "</td>";

		echo "<td align='right' title='media OK' class='ocultar'>";
		echo pg_result ($res,$i,media_ok);
		echo "</td>";

		echo "<td align='right' title='Estoque Mínimo'>";
		echo $estoque_minimo;
		echo "</td>";

		echo "<td align='right' title='Pedido' bgcolor='#33CC00'>";
		echo pg_result ($res,$i,qtde);
		echo "</td>";

		echo "<td align='right' title='Estoque'>";
		echo pg_result ($res,$i,estoque);
		echo "</td>";

		echo "<td align='right' title='Fábrica'>";
		echo pg_result ($res,$i,fabrica);
		echo "</td>";

		echo "<td align='right' title='Transp.'>";
		echo pg_result ($res,$i,transp);
		echo "</td>";


		echo "</tr>";
	}

	echo "</table>";
	flush();

	include "rodape.php"; ?>

</body>
</html>

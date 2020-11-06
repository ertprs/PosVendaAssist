<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include "cabecalho.php";

?>

<html>
<head>
<title>Itens da NF de Entrada</title>
</head>

<body>

<? include 'menu.php' ?>
<?
include_once '../js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist - HD 969678 */
?>
<script>
$(document).ready(function()
    {
        $('#data_inicial').datepick({startDate:'01/01/2000'});
        $('#data_final').datepick({startDate:'01/01/2000'});
});

</script>
<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 0px solid;
	color:#ffffff;
	background-color: #596D9B
}

.link{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
}

</style>

<center><h1>OS</h1></center>

<?

if (strlen(trim($_POST["btn_acao"])) > 0) $btn_acao = trim($_POST["btn_acao"]);
if (strlen(trim($_GET["btn_acao"])) > 0)  $btn_acao = trim($_GET["btn_acao"]);

if (strlen(trim($_POST["data_inicial"])) > 0) $data_inicial = trim($_POST["data_inicial"]);
if (strlen(trim($_GET["data_inicial"])) > 0)  $data_inicial = trim($_GET["data_inicial"]);

if (strlen(trim($_POST["data_final"])) > 0) $data_final = trim($_POST["data_final"]);
if (strlen(trim($_GET["data_final"])) > 0)  $data_final = trim($_GET["data_final"]);

$fabrica = $_POST['fabrica'];

if (strlen($msg_erro) > 0) { ?>
	<table width="600" border="0" cellspacing="0" cellpadding="2" align="center" class="Error">
		<tr>
			<td><?echo $msg_erro?></td>
		</tr>
	</table>
	<br>
	<? 
} 
?>
	<center>
	<form name='frm_estoque' action='<? echo $PHP_SELF ?>' method='POST'>
	<table>

		<tr>
			<td align='right'>Data Inicial</td>
			<td><input type='text' size='11' name='data_inicial' id='data_inicial' class="frm" value="<? echo $_REQUEST["data_inicial"]; ?>"></td>
			<td align='right'>Data Final</td>
			<td><input type='text' size='11' name='data_final'   id='data_final' class="frm"  value="<? echo $_REQUEST["data_final"]; ?>"></td>
					<td align='right'>Fábrica</td>
			<td align='left'>
			<?
			echo "<select style='width:120px;' name='fabrica' id='fabrica' class='frm'>";
				$sql = "SELECT fabrica,nome FROM tbl_fabrica WHERE fabrica IN ($telecontrol_distrib) ORDER BY nome";
				$res = pg_exec($con,$sql);
				if(pg_numrows($res)>0){
					for($x = 0; $x < pg_numrows($res);$x++) {
						$aux_fabrica = pg_fetch_result($res,$x,fabrica);
						$aux_nome    = pg_fetch_result($res,$x,nome);
						echo "<option value='$aux_fabrica'" ;if($fabrica==$aux_fabrica) echo "selected"; echo ">$aux_nome</option>";
					}
				}
			echo "</select>";
			?>
			</td>
		</tr>
		<tr>
			<td align='center' colspan='6'><input type='submit' name='btn_acao' id='btn_acao' value='Pesquisar'></td>
		</tr>

	</table>
<?
if (strlen($btn_acao)>0 and strlen($msg_erro)==0) {
$sql = "
				SELECT 	distinct tbl_os.sua_os, 
						tbl_fabrica.nome,
						tbl_os.os
				FROM tbl_os
				JOIN tbl_os_produto		ON tbl_os_produto.os		 = tbl_os.os
				JOIN tbl_os_item		ON tbl_os_item.os_produto	 = tbl_os_produto.os_produto
				JOIN tbl_peca			ON tbl_peca.peca			 = tbl_os_item.peca 
				JOIN tbl_posto_estoque  ON tbl_posto_estoque.peca    = tbl_peca.peca
				JOIN tbl_os_produto		a ON a.os		 = tbl_os.os
				JOIN tbl_os_item		b ON b.os_produto	 = a.os_produto
				JOIN tbl_peca			c ON c.peca			 = b.peca 
				JOIN tbl_posto_estoque  d ON d.peca    = c.peca
				JOIN tbl_fabrica on tbl_os.fabrica = tbl_fabrica.fabrica and tbl_fabrica.parametros_adicionais ~* 'telecontrol_distrib'
				LEFT JOIN tbl_pedido_item ON tbl_os_item.pedido = tbl_pedido_item.pedido and tbl_os_item.peca= tbl_pedido_item.peca
				LEFT JOIN tbl_embarque_item ON tbl_embarque_item.pedido_item = tbl_pedido_item.pedido_item
				WHERE tbl_os_item.os_item <> b.os_item
				AND   tbl_posto_estoque.posto <> d.posto
				AND   tbl_embarque_item.embarque isnull
				and tbl_os.fabrica = $fabrica
				and tbl_os.data_abertura between '$data_inicial' and '$data_final'
				AND   excluida is not true
				";
	$res = pg_exec ($con,$sql);

	echo "<table width='200' border='1' cellspacing='1' cellpadding='3' align='center'>\n";
	echo "<tr>\n";
	echo "<td class='menu_top' width='20'>Fábrica</td>\n";
	echo "<td class='menu_top'>OS</td>\n";
	echo "</tr>\n";

	if (pg_numrows($res) > 0) {

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

			$os				= trim(pg_result($res,$i,os));
			$sua_os			= trim(pg_result($res,$i,sua_os)) ;
			$nome			= trim(pg_result($res,$i,nome)) ;
			echo "<tr>";
				echo "<td nowrap align='left'>$nome</td>\n";
				echo "<td align='left'><a href='../os_press.php?os=$os' target='_blank' class='link'>$sua_os</a></td>\n";
			echo "<tr>";
		}

	}
	echo "</table>\n";
}
	echo "</table>\n";
?>
</body>
<p>
<? include "rodape.php"; ?>

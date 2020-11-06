<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

if ($login_fabrica <> 6) {
	header ("Location: menu_callcenter.php");
	exit;
}



$layout_menu = "gerencia";
$title = "Manutenção de Itens de OS para Pedidos";
include 'cabecalho.php';
?>

<style type='text/css'>
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
</style>

<br>

<? if (strlen($msg_erro) > 0) { ?>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center">
	<tr>
		<td valign="middle" align="center" class='error'><?echo $msg_erro?></td>
	</tr>
</table>
<br>
<? } ?>

<br>

<?
$sql =	"SELECT tbl_pedido.pedido                                , 
				to_char(tbl_pedido.data,'DD/MM/YYYY') as data    , 
				tbl_peca.referencia                              , 
				tbl_peca.descricao                               , 
				tbl_pedido_item.qtde                             , 
				tbl_posto.nome                                   , 
				tbl_posto_fabrica.codigo_posto                   , 
				tbl_pedido_item.pedido_item
		FROM tbl_pedido 
		JOIN tbl_pedido_item using(pedido) 
		JOIN tbl_peca using(peca) 
		JOIN tbl_posto on tbl_pedido.posto = tbl_posto.posto
		JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto 
		AND  tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_pedido.fabrica = $login_fabrica
		AND tbl_pedido.finalizado notnull
		AND tbl_pedido.exportado is null 
		AND tbl_pedido.controle_exportacao is null
		AND tbl_pedido.tipo_pedido = 4
		order by codigo_posto, pedido, referencia";
echo $sql;
$res = pg_exec($con,$sql);

if (pg_numrows($res) > 0) {
	echo "<form name='frm_os_item' method='post' action='$PHP_SELF'>\n";
	echo "<input type='hidden' name='btn_acao'>\n";
	echo "<table border='0' cellpadding='2' cellspacing='1'  align='center'>\n";
	$qtde_liberar_inicio = 0;
	$qtde_liberar_final  = 0;
	$cont = 0;

	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
		$pedido               = trim(pg_result($res,$i,pedido));
		$data                 = trim(pg_result($res,$i,data));
		$peca_referencia      = trim(pg_result($res,$i,referencia));
		$peca_descricao       = trim(pg_result($res,$i,descricao));
		$qtde                 = trim(pg_result($res,$i,qtde));
		$posto_codigo         = trim(pg_result($res,$i,codigo_posto));
		$posto_nome           = trim(pg_result($res,$i,nome));
		$pedido_item          = trim(pg_result($res,$i,pedido_item));
		
		$cor = ($i % 2 == 0) ? "#d2d7e1" : "#efeeea";

		if($posto_codigo_anterior != $posto_codigo){
			echo "<tr class='Titulo'>\n";
			echo "<td colspan='7'><b>Posto: $posto_codigo - $posto_nome</b></td>\n";
			echo "</tr>\n";

			echo "<tr class='Titulo'>\n";
			echo "<td>Liberar</td>\n";
			echo "<td>Recusar</td>\n";
			echo "<td>Pedido</td>\n";
			echo "<td>Data</td>\n";
			echo "<td>Peça</td>\n";
			echo "<td>Qtde</td>\n";
			echo "</tr>\n";
				
		}

		echo "<tr class='Conteudo' bgcolor='$cor' >\n";
		echo "<td  bgcolor='#4c664b'>\n";
		echo "<input type='checkbox' name='liberar_$i' value='$pedido_item'class='frm'>\n";
		echo "</td>\n";
		echo "<td  bgcolor='#dcc6c6'>\n";
		echo "<input type='checkbox' name='recusar_$i' value='$pedido_item'class='frm'>\n";
		echo "</td>\n";
		echo "<td><a href='pedido_cadastro.php?pedido=$pedido' target='blank'>$pedido</a></td>";
		echo "<td>$data</td>";
		echo "<td align='left'>$peca_referencia - $peca_descricao</td>";
		echo "<td>$qtde</td>";
		echo "</tr>\n";
		if($posto_codigo_anterior != $posto_codigo and $i<>0){
			echo "<tr class='Titulo'>\n";
			echo "<td colspan='7'><b>Posto: $posto_codigo - $posto_nome</b></td>\n";
			echo "</tr>\n";

			echo "<tr class='Titulo'>\n";
		echo "<td  bgcolor='#4c664b'>\n";
		echo "<input type='checkbox' name='liberar_$i' value='$pedido_item'class='frm'>\n";
		echo "</td>\n";
		echo "<td  bgcolor='#dcc6c6'>\n";
		echo "<input type='checkbox' name='recusar_$i' value='$pedido_item'class='frm'>\n";
		echo "</td>\n";
			echo "<td colspan='4'>Clique no campo ao lado para selecionar todos</td>\n";

			echo "</tr>\n";
				
		}

		$posto_codigo_anterior = $posto_codigo;
	}
	
}

?>

</form>

<? include "rodape.php"; ?>

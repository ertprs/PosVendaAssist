<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$login_fabrica = ($_GET['fabrica'] > 0) ? $_GET['fabrica'] : 10;
$login_posto  = 4311;


#------------ Le Pedido da Base de dados ------------#
$pedido = (strlen($_GET['pedido'])>0) ? $_GET['pedido'] : $_POST['pedido'];

if (strlen ($pedido) > 0) {
	$sql = "SELECT  tbl_pedido.pedido                                ,
					tbl_pedido.condicao                              ,
					tbl_pedido.tabela                                ,
					tbl_pedido.distribuidor                          ,
					tbl_pedido.pedido_cliente                        ,
					to_char(tbl_pedido.recebido_posto,'DD/MM/YYYY') AS recebido_posto            ,
					to_char(tbl_pedido.data,'DD/MM/YYYY')   AS pedido_data ,
					tbl_pedido.data                         AS pedido_data2 ,
					tbl_condicao.descricao            AS condicao_descricao,
					tbl_tipo_pedido.descricao         AS tipo_descricao    ,
					tbl_tabela.tabela                                      ,
					tbl_tabela.descricao              AS tabela_descricao  ,
					tbl_posto_fabrica.codigo_posto                         ,
					tbl_posto.nome                    AS posto_nome        ,
					distrib.nome_fantasia             AS distrib_fantasia  ,
					distrib.nome                      AS distrib_nome      ,
					COALESCE(tbl_pedido.desconto, 0) AS pedido_desconto
			FROM    tbl_pedido
			JOIN    tbl_posto           ON tbl_pedido.posto            = tbl_posto.posto
			JOIN    tbl_posto_fabrica   ON tbl_posto.posto             = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica 
			LEFT JOIN tbl_condicao      ON tbl_condicao.condicao       = tbl_pedido.condicao
			LEFT JOIN tbl_tipo_pedido   ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido AND tbl_tipo_pedido.fabrica = $login_fabrica
			LEFT JOIN tbl_tabela        ON tbl_tabela.tabela           = tbl_pedido.tabela
			LEFT JOIN tbl_posto distrib ON tbl_pedido.distribuidor     = distrib.posto
			WHERE   tbl_pedido.pedido  = $pedido
			AND     tbl_pedido.fabrica = $login_fabrica;";
	$res = @pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		$pedido           = trim(pg_fetch_result($res, 0, 'pedido'));
		$condicao         = trim(pg_fetch_result($res, 0, 'condicao_descricao'));
		$distribuidor     = trim(pg_fetch_result($res, 0, 'distribuidor'));
		$tipo_pedido      = trim(pg_fetch_result($res, 0, 'tipo_descricao'));
		$tabela           = trim(pg_fetch_result($res, 0, 'tabela'));
		$tabela_descricao = trim(pg_fetch_result($res, 0, 'tabela_descricao'));
		$pedido_cliente   = trim(pg_fetch_result($res, 0, 'pedido_cliente'));
		$pedido_data      = trim(pg_fetch_result($res, 0, 'pedido_data'));
		$pedido_data2     = trim(pg_fetch_result($res, 0, 'pedido_data2'));
		$distrib_fantasia = trim(pg_fetch_result($res, 0, 'distrib_fantasia'));
		$codigo_posto     = trim(pg_fetch_result($res, 0, 'codigo_posto'));
		$posto_nome       = trim(pg_fetch_result($res, 0, 'posto_nome'));
		$data_recebido    = trim(pg_fetch_result($res, 0, 'recebido_posto'));
		if (strlen ($distrib_fantasia) == 0) $distrib_fantasia = substr (trim(pg_fetch_result($res, 0, 'distrib_nome')),0,15);
		if (strlen ($distrib_fantasia) == 0) $distrib_fantasia = '<b>Fabrica</b>';
		$pedido_desconto  = trim(pg_fetch_result($res, 0, 'pedido_desconto'));
	}
}

$title = "CONFIRMAÇÃO DE PEDIDO DE PEÇAS";
$layout_menu = 'pedido';

include "menu.php";
?>

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

.table_line1 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

.table_line1_pendencia {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color: #FF0000;
}

.error {
	background:#ED1B1B;
	width: 600px;
	text-align: center;
	padding: 2px 2px; 
	margin: 1em 0.25em;
	color:#FFFFFF;
	font-size:12px;
}

.error h1 {
	color:#FFFFFF;
	font-size:14px;
	font-size:normal;
	text-transform: capitalize;
}

</style>
<br/><br/>
<table width="700px" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td valign="top" align="center">
		<table width="100%" border="0" cellspacing="1" cellpadding="3" align='center'>
		<tr>
			<td class='menu_top'>Pedido</td>
			<td class='menu_top'>Data</td>
			<td class='menu_top'>Tipo Pedido</td>
			<td class='menu_top'>Tabela de Preços</td>
			<td class='menu_top'>Atendido Por</td>
		</tr>
		<tr>
			<td align='center' class='table_line1'><?echo $pedido?></td>
			<td align='center' class='table_line1'><?echo $pedido_data?></td>
			<td align='center' class='table_line1'><?echo $tipo_pedido?></td>
			<td align='center' class='table_line1'><?echo $tabela_descricao?></td>
			<td align='center' class='table_line1'><?echo $distrib_fantasia?></td>
		</tr>
		</table>
		<br>
		<table width="<? echo $tamanho;?>" border="0" cellspacing="1" cellpadding="3" align='center'>
		<tr height="20">
			<td class='menu_top'>Componente</td>
			<td class='menu_top'>Qtde Pedida</td>
			<td class='menu_top'>Qtde Cancelada</td>
			<td class='menu_top'>Qtde Faturada</td>
			<td class='menu_top'>Preço (R$)</td>
			<td class='menu_top'>IPI (%)</td>
			<td class='menu_top'>Total c/ IPI (R$)</td>
		</tr>
		
		<?
		$sql = "SELECT  sum(tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100))) as total_pedido
				FROM  tbl_pedido
				JOIN  tbl_pedido_item USING (pedido)
				JOIN  tbl_peca        USING (peca)
				WHERE tbl_pedido_item.pedido = $pedido
				and tbl_pedido.fabrica = $login_fabrica
				GROUP BY tbl_pedido.pedido";
		$res = @pg_query ($con,$sql);

		$total_pedido = (pg_num_rows($res) > 0) ? pg_fetch_result($res, 0, 'total_pedido') : 0;


			$sql = "SELECT  tbl_pedido_item.peca           ,
							tbl_peca.referencia            ,
							tbl_peca.descricao             ,
							tbl_peca.ipi                   ,
							to_char(tbl_peca.previsao_entrega,'DD/MM/YYYY') AS previsao_entrega    ,
							tbl_pedido_item.pedido_item    ,
							tbl_pedido_item.qtde           ,
							tbl_pedido_item.qtde_faturada  ,
							tbl_pedido_item.qtde_faturada_distribuidor  ,
							tbl_pedido_item.qtde_cancelada ,
							tbl_pedido_item.preco          ,
							tbl_pedido.desconto            ,
							tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100)) as total,
							tbl_pedido_item_nf.qtde_nf as qtde_faturada_outros           ,
							tbl_pedido_item.obs
					FROM  tbl_pedido
					JOIN  tbl_pedido_item           USING (pedido)
					JOIN  tbl_peca                  USING (peca)
					LEFT JOIN    tbl_pedido_item_nf USING (pedido_item)
					WHERE tbl_pedido_item.pedido = $pedido
					AND   tbl_pedido.fabrica     = $login_fabrica
					ORDER BY tbl_pedido_item.pedido_item;  ";

		$res = @pg_query ($con,$sql);
		
		for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
			$cor = ($i % 2 == 0) ? '#F1F4FA':'#FFFFFF';

			$peca                        = pg_fetch_result($res, $i, 'peca');
			$qtde                        = pg_fetch_result($res, $i, 'qtde');
			$qtde_faturada               = pg_fetch_result($res, $i, 'qtde_faturada');
			$qtde_faturada_distribuidor  = pg_fetch_result($res, $i, 'qtde_faturada_distribuidor');
			$qtde_faturada_outros        = pg_fetch_result($res, $i, 'qtde_faturada_outros');
			$qtde_cancelada              = pg_fetch_result($res, $i, 'qtde_cancelada');
			$pedido_item                 = pg_fetch_result($res, $i, 'pedido_item');
			$preco                       = pg_fetch_result($res, $i, 'preco');
			$desconto                    = pg_fetch_result($res, $i, 'desconto');
			$ipi                         = pg_fetch_result($res, $i, 'ipi');
			$total                       = pg_fetch_result($res, $i, 'total');
			$previsao_entrega            = pg_fetch_result($res, $i, 'previsao_entrega');
			$obs_pedido_item             = pg_fetch_result($res, $i, 'obs');
			$peca_descricao              = pg_fetch_result($res, $i, 'referencia') . " - " . pg_fetch_result($res, $i, 'descricao');

			$preco = number_format ($preco,2,",",".") ;
			$total = number_format ($total,2,",",".") ;

		?>
		<tr bgcolor="<?=$cor?>" >
			<td class='table_line1' <? echo ($login_fabrica != 50) ? " nowrap" : "";?>><?=$peca_descricao?></td>
			<td class='table_line1' align='right'><?=$qtde?></td>
			<td class='table_line1' align='right' style='color:#FF0000; font-weight:bold;'>
				<?
				echo ($qtde_cancelada == 0 OR strlen($qtde_cancelada) == 0) ? "&nbsp;" : $qtde_cancelada;
				?>
			</td>

			<td class='table_line1' align='right'><?=$qtde_faturada?></td>
			<? 
				echo "<td class='table_line1' align='right'> $preco</td>";
				echo "<td class='table_line1' align='right'> $ipi </td>";
				echo "<td class='table_line1' align='right'> $total</td>";
			?>
		</tr>
		<?		$peca_anterior = $peca; }?>
		<tr>
			<td colspan='6' align='center' class='menu_top'>TOTAL</td>
			<td align='right' class='table_line1' style='font-weight:bold'>
			<? echo ($login_fabrica <> 14) ? number_format ($total_pedido,2,",",".") : str_replace (".",",",$total_pedido); ?>
			</td>
		</tr>

		</table>
		
	</td>
	<td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>
<tr>
	<td height="27" valign="middle" align="center" colspan="3">
		<br>
		&nbsp;&nbsp;
		<a href="pedido_cadastro_new.php"><img src='../imagens/btn_lancarnovopedido.gif'></a>
		&nbsp;&nbsp;
	</td>
</tr>

</form>
</table>

<?	
	$sql = "SELECT	distinct tbl_faturamento.faturamento , 
					tbl_faturamento.nota_fiscal , 
					to_char (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao ,
					tbl_faturamento.conhecimento, 
					tbl_faturamento_item.faturamento_item,
					tbl_faturamento_item.peca , 
					tbl_faturamento_item.qtde , 
					tbl_peca.peca ,
					tbl_peca.referencia ,
					tbl_peca.descricao
			FROM    (SELECT * FROM tbl_pedido_item WHERE pedido = $pedido) tbl_pedido_item
			JOIN    tbl_faturamento_item ON tbl_pedido_item.pedido = tbl_faturamento_item.pedido AND tbl_pedido_item.peca = tbl_faturamento_item.peca
			JOIN    tbl_faturamento      ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
										AND tbl_faturamento.fabrica = $login_fabrica
			JOIN    tbl_peca             ON tbl_pedido_item.peca = tbl_peca.peca
			ORDER   BY tbl_peca.descricao";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
		echo "<h2 style='font-size:15px ; color:#000000 ; text-align:center ' >Notas Fiscais que atenderam a este pedido</h2>";
		echo "<table width='450' align='center' border='0' cellspacing='3'>";

		echo "<tr bgcolor='#663399' style='color: #FFFFFF ; font-weight:bold ; text-align:center ' >";
		echo "<td>Nota Fiscal</td>";
		echo "<td>Data</td>";
		echo "<td>Peça</td>";
		echo "<td>Qtde</td>";
		echo "</tr>";

		for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
			//Gustavo 12/12/2007 HD 9095
			if ($login_fabrica==35) {
				$conhecimento = trim(pg_fetch_result($res, $i, 'conhecimento'));
			}

			echo "<tr style='font-size:9px ; color: #000000 ; text-align:left' >";
			echo "<td>" . pg_fetch_result($res, $i, 'nota_fiscal') . "</td>";
			echo "<td>" . pg_fetch_result($res, $i, 'emissao') . "</td>";
			echo "<td nowrap>" . pg_fetch_result($res, $i, 'referencia') . " - " . pg_fetch_result($res, $i, 'descricao') . "</td>";
			echo "<td align='right'>" . pg_fetch_result($res, $i, 'qtde') . "</td>";
			echo "</tr>";
		}
		echo "</table>";
	}
?>

<!-- ########## PEDIDO CANCELADO ########## -->
<?


$sql =	"SELECT tbl_peca.referencia         ,
				tbl_peca.descricao          ,
				tbl_pedido_cancelado.qtde   ,
				tbl_pedido_cancelado.motivo ,
				to_char (tbl_pedido_cancelado.data,'DD/MM/YYYY') AS data ,
				tbl_os.sua_os               
		FROM tbl_pedido_cancelado
		JOIN tbl_peca USING (peca)
		LEFT JOIN tbl_os ON tbl_pedido_cancelado.os = tbl_os.os
		WHERE tbl_pedido_cancelado.pedido  = $pedido
		AND   tbl_pedido_cancelado.fabrica = $login_fabrica";

	$res = @pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		echo "<h2 style='font-size:15px ; color:#000000 ; text-align:center ' >Pedidos cancelados que pertencem a este pedido</h2>";
		for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
			$cor = ($i % 2 == 0) ? '#F1F4FA' : "#FFFFFF";
			if ($i == 0) {
				echo "<table width='600' align='center' border='0' cellspacing='3'>";
				echo "<tr bgcolor='#663399' style='color:#FFFFFF; font-weight:bold; text-align:center'>";
				echo "<td>OS</td>";
				echo "<td>Data</td>";
				echo "<td>Peça</td>";
				echo "<td>Qtde</td>";
				echo "</tr>";
				echo "<tr bgcolor='#663399' style='color:#FFFFFF; font-weight:bold; text-align:center'>";
				echo "<td colspan='4'>Motivo</td>";
				echo "</tr>";
			}
			echo "<tr bgcolor='$cor' style='font-size:9px; color:#000000; text-align:left'>";
			echo "<td nowrap align='center' rowspan='2'>".pg_fetch_result($res, $i, 'sua_os')."</td>";
			echo "<td nowrap align='center'>".pg_fetch_result($res, $i, 'data')."</td>";
			echo "<td nowrap>".pg_fetch_result($res, $i, 'referencia')." - ".pg_fetch_result($res, $i, 'descricao')."</td>";
			echo "<td nowrap align='right'>".pg_fetch_result($res, $i, 'qtde')."</td>";
			echo "</tr>";
			echo "<tr bgcolor='$cor' style='font-size:9px; color:#000000; text-align:left'>";
			echo "<td colspan='3' nowrap>".pg_fetch_result($res, $i, 'motivo')."</td>";
			echo "</tr>";
		}
		echo "</table>";
	}
?>

<p>

<? include "rodape.php"; ?>
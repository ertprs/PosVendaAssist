<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';


$title='Movimento de Peças';



	include 'autentica_usuario.php';

include "cabecalho.php";



if (strlen(trim($_POST["btn_acao"])) > 0) $btn_acao = trim($_POST["btn_acao"]);
if (strlen(trim($_GET["btn_acao"])) > 0)  $btn_acao = trim($_GET["btn_acao"]);

if (strlen(trim($_POST["referencia"])) > 0) $referencia = mb_strtoupper(trim($_POST["referencia"]));
if (strlen(trim($_GET["referencia"])) > 0)  $referencia = mb_strtoupper(trim($_GET["referencia"]));

if (strlen(trim($_POST["descricao"])) > 0) $descricao = trim($_POST["descricao"]);
if (strlen(trim($_GET["descricao"])) > 0)  $descricao = trim($_GET["descricao"]);


?>


<html>
<head>
<title>Movimento de Peças</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>

<body>

<? include 'menu.php' ?>


<center><h1>Movimento de Peças</h1></center>

<?


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


<p>

<center>
<form name='frm_estoque' action='<? echo $PHP_SELF ?>' method='post'>

Referência da Peça <input type='text' size='10' name='referencia' class='frm' onFocus="this.className='frm-on'; " onBlur="this.className='frm';" value='<?=$referencia?>'>
Descrição da Peça <input type='text' size='30' name='descricao'class='frm'onFocus="this.className='frm-on'; " onBlur="this.className='frm';" value='<?=$descricao?>'>
<br>
<input type='submit' name='btn_acao' value='Pesquisar' class='frm'>

</form>
</center>


<?

flush();

if (strlen($btn_acao)>0 and strlen($msg_erro)==0){
	if (strlen ($descricao) > 2) {
		$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, tbl_posto_estoque.qtde, fabrica.qtde_fabrica, transp.qtde_transp, para.referencia AS para_referencia, para.descricao AS para_descricao, tbl_posto_estoque_localizacao.localizacao
				FROM   tbl_peca
				LEFT JOIN tbl_posto_estoque             ON tbl_peca.peca = tbl_posto_estoque.peca AND tbl_posto_estoque.posto = $login_posto
				LEFT JOIN tbl_posto_estoque_localizacao ON tbl_peca.peca = tbl_posto_estoque_localizacao.peca AND tbl_posto_estoque_localizacao.posto = $login_posto
				LEFT JOIN tbl_depara ON tbl_peca.peca = tbl_depara.peca_de
				LEFT JOIN tbl_peca para ON tbl_depara.peca_para = para.peca
				LEFT JOIN (SELECT peca, SUM (qtde - qtde_faturada - qtde_cancelada) AS qtde_fabrica FROM tbl_pedido_item JOIN tbl_pedido USING (pedido)

				WHERE (

				(tbl_pedido.posto = $login_posto AND tbl_pedido.tipo_pedido = 2)
				OR (tbl_pedido.distribuidor = $login_posto AND tbl_pedido.tipo_pedido = 3 )
				OR tbl_pedido.fabrica in (3,25,51,81,10)
				)
				AND tbl_pedido.fabrica in (3,25,51, 81, 10) GROUP BY tbl_pedido_item.peca) fabrica ON tbl_peca.peca = fabrica.peca
				LEFT JOIN (SELECT peca, SUM (qtde) AS qtde_transp FROM tbl_faturamento_item JOIN tbl_faturamento USING (faturamento) WHERE tbl_faturamento.posto = $login_posto AND tbl_faturamento.fabrica in (3,25,51,81,10) AND tbl_faturamento.conferencia IS NULL GROUP BY tbl_faturamento_item.peca) transp ON tbl_peca.peca = transp.peca
				WHERE  (tbl_posto_estoque.posto = $login_posto OR tbl_posto_estoque.posto IS NULL)
				AND    (tbl_peca.descricao ILIKE '%$descricao%' OR para.descricao ILIKE '%$descricao%')
				AND    tbl_peca.fabrica in (3,25,51,81,10)
				ORDER BY tbl_peca.descricao";
		$res = pg_exec ($con,$sql);

		if(pg_numrows ($res)>0){
			echo "<table align='center' border='0' cellspacing='1' cellpadding='1'  bordercolor='#000000' >";
			echo "<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
			echo "<td>Referência</td>";
			echo "<td>Descrição</td>";
			echo "<td>Estoque</td>";
			echo "<td>Fábrica</td>";
			echo "<td>Transp.</td>";
			echo "<td>Localização</td>";
			echo "</tr>";

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

				$cor = "#eeeeee";
				if (strlen (trim (pg_result ($res,$i,para_referencia))) > 0) $cor = '#cccccc';

				echo "<tr bgcolor='$cor'>";

				echo "<td>";
				echo pg_result ($res,$i,referencia);
				if (strlen (trim (pg_result ($res,$i,para_referencia))) > 0) echo "<br>" . pg_result ($res,$i,para_referencia);
				echo "</td>";

				echo "<td>";
				echo pg_result ($res,$i,descricao);
				if (strlen (trim (pg_result ($res,$i,para_referencia))) > 0) echo "<br>" . pg_result ($res,$i,para_descricao);
				echo "</td>";

				echo "<td align='right'>&nbsp;";
				echo pg_result ($res,$i,qtde);
				echo "</td>";

				$qtde_fabrica = pg_result ($res,$i,qtde_fabrica);
				if ($qtde_fabrica < 0) $qtde_fabrica = 0;

				echo "<td align='right'>&nbsp;";
				echo $qtde_fabrica;
				echo "</td>";

				echo "<td align='right'>&nbsp;";
				echo pg_result ($res,$i,qtde_transp);
				echo "</td>";

				echo "<td align='left'>&nbsp;";
				echo pg_result ($res,$i,localizacao);
				echo "</td>";

				echo "</tr>";
			}
			echo "</table>";
			exit;
		}else echo "<center><b><span class='vermelho'>$descricao </span> - NENHUM PRODUTO COM ESSA DESCRIÇÃO FOI ENCONTRADO</center></b>";
	}

	//SE ENTRAR COM O CÓDIGO DE REFERENCIA IRA FAZER OS COMANDOS ABAIXO
	if (strlen ($referencia) > 2 ) {
		$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao
				FROM tbl_peca
				WHERE tbl_peca.referencia = '$referencia'
				AND    tbl_peca.fabrica in (3,25,51,81, 10)
				ORDER BY tbl_peca.descricao";

		$res = pg_exec ($con,$sql);
		if(pg_numrows ($res)==0){
			echo "<center><b><span class='vermelho'>$referencia </span>- CÓDIGO DE PEÇA NÃO CADASTRADO</center></b><br>";
			exit;
		}else
			echo '<center><b><h3>'.pg_result ($res,0,referencia).' - '.pg_result ($res,0,descricao).'</h3></center></b>';

	//************ENTRADA DE PEÇAS*************//


		$sql = "SELECT  tbl_faturamento.nota_fiscal,tbl_faturamento.cfop,
						SUM (tbl_faturamento_item.qtde) AS qtde,
						SUM (tbl_faturamento_item.qtde_estoque) AS qtde_estoque,
						SUM (tbl_faturamento_item.qtde_quebrada) AS qtde_quebrada,
						TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY')as emissao,
						TO_CHAR(tbl_faturamento.conferencia,'DD/MM/YYYY')as conferencia
				FROM tbl_faturamento
				JOIN tbl_faturamento_item using (faturamento)
				JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca
				WHERE tbl_faturamento.fabrica in (3,25,51,81,10)
				AND tbl_faturamento.distribuidor is null
				AND tbl_faturamento.posto = $login_posto
				AND tbl_peca.referencia = '$referencia'
				AND tbl_faturamento.cancelada IS NULL
				GROUP BY tbl_faturamento.nota_fiscal,tbl_faturamento.cfop,tbl_faturamento.emissao, tbl_faturamento.conferencia
				ORDER BY tbl_faturamento.emissao,tbl_faturamento.nota_fiscal ASC";
		$sql = "SELECT  tbl_faturamento.nota_fiscal,tbl_faturamento.cfop,
						SUM (tbl_faturamento_item.qtde) AS qtde,
						SUM (tbl_faturamento_item.qtde_estoque) AS qtde_estoque,
						SUM (tbl_faturamento_item.qtde_quebrada) AS qtde_quebrada,
						TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY')as emissao,
						TO_CHAR(tbl_faturamento.conferencia,'DD/MM/YYYY')as conferencia
				FROM tbl_faturamento
				JOIN tbl_faturamento_item using (faturamento)
				JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca
				WHERE  tbl_faturamento.distribuidor = $login_posto
				AND (
					tbl_faturamento.distribuidor IN (
						/* Seleciona apenas os distribuidores da condição nova, descartando quando o posto entrava como
						distribuidor (LRG Britania)*/
						SELECT DISTINCT distribuidor FROM tbl_faturamento WHERE fabrica = 10 AND posto = 4311)
					OR
					tbl_faturamento.fabrica in (3,25,51,81,10)
					AND tbl_faturamento.distribuidor is null
				)
				AND tbl_peca.referencia = '$referencia'
				AND tbl_faturamento.cancelada IS NULL
				GROUP BY tbl_faturamento.nota_fiscal,tbl_faturamento.cfop,tbl_faturamento.emissao, tbl_faturamento.conferencia
				ORDER BY tbl_faturamento.emissao,tbl_faturamento.nota_fiscal ASC";
		$res = pg_exec ($con,$sql);

		$total_qtde = 0;
		$total_qtde_estoque = 0;
		$total_qtde_quebrada = 0;

		if(pg_numrows ($res)>0){

			echo "<br><table align='center'border='0' cellspacing='1' cellpaddin='1' >";
			echo"<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'><td colspan='6'>MOVIMENTO DE ENTRADA DE PEÇAS</td></tr>";
			echo "<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
			echo "<td>Nota Fiscal</td>";
			echo "<td>Emissão</td>";
			echo "<td>Conferencia</td>";
			echo "<td>Qtde</td>";
			echo "<td>Qtde Estoque</td>";
			echo "<td>Qtde Quebrada</td>";
			echo "</tr>";

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

				$total_qtde += pg_result($res, $i, qtde);
				$total_qtde_estoque += pg_result($res, $i, qtde_estoque);;
				$total_qtde_quebrada += pg_result($res, $i, qtde_quebrada);;
				$cor = "#eeeeee";
				if (($i%2) == 0) $cor = '#cccccc';

				echo "<tr bgcolor='$cor'>";

				echo "<td title='Número da nota fiscal'>";
				echo pg_result ($res,$i,nota_fiscal);
				echo "-";
				echo pg_result ($res,$i,cfop);
		//		if (strlen (trim (pg_result ($res,$i,para_referencia))) > 0) echo "<br>" . pg_result ($res,$i,para_referencia);
				echo "</td>";

				echo "<td title='Data emissão'>";
				echo pg_result ($res,$i,emissao);
		//		if (strlen (trim (pg_result ($res,$i,para_referencia))) > 0) echo "<br>" . pg_result ($res,$i,para_descricao);
				echo "</td>";

				echo "<td align='right'title='Data conferência'>&nbsp;";
				echo pg_result ($res,$i,conferencia);
				echo "</td>";

				$qtde_fabrica = pg_result ($res,$i,qtde);
				if ($qtde_fabrica < 0) $qtde_fabrica = 0;

				echo "<td align='center' title='Quantidade'>&nbsp;";
				echo pg_result ($res,$i,qtde);
				echo "</td>";
				echo "<td align='center'title='Quantidade em Estoque'>&nbsp;";
				echo pg_result ($res,$i,qtde_estoque);
				echo "</td>";

				echo "<td align='center'title='Quantidade Quebrada'>&nbsp;";
				echo pg_result ($res,$i,qtde_quebrada);
				echo "</td>";

				echo "</tr>";
			}
			echo "<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
			echo "<td>TOTAIS</td>";
			echo "<td></td>";
			echo "<td></td>";
			echo "<td>$total_qtde</td>";
			echo "<td>$total_qtde_estoque</td>";
			echo "<td>$total_qtde_quebrada</td>";
			echo "</tr>";
			echo "</table>";
		}else{
			echo "<br><center><b> MOVIMENTO DE ENTRADA DE PEÇAS</center></b>";
			echo "<CENTER><span class='vermelho'> Não foi encontrado movimento de ENTRADA de Peças </span></CENTER>";
			}


	//**************SAIDA DE PEÇAS*************//


		$sql = "select  
						tbl_faturamento.nota_fiscal,
						tbl_faturamento.cfop,
						TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao,
						tbl_tipo_pedido.descricao AS tipo_pedido_descricao,
						SUM(tbl_faturamento_item.qtde) AS qtde
				FROM tbl_faturamento
				JOIN tbl_faturamento_item using (faturamento)
				JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca
				JOIN tbl_tipo_pedido ON tbl_faturamento.tipo_pedido=tbl_tipo_pedido.tipo_pedido
				WHERE tbl_faturamento.fabrica in (3,25,51,81,10)
				AND tbl_faturamento.distribuidor = $login_posto
				AND tbl_peca.referencia = '$referencia'
				GROUP BY
				tbl_faturamento.nota_fiscal,
				tbl_faturamento.cfop,
				tbl_faturamento.emissao,
				tbl_tipo_pedido.descricao,
				tbl_faturamento_item.peca
				ORDER BY tbl_faturamento.emissao,tbl_faturamento.nota_fiscal ASC";
		$res = pg_exec ($con,$sql);


		if(pg_numrows ($res)>0){
			echo "<br><table align='center' border='0' cellspacing='1' cellpaddin='1'>";
			echo "<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'><td colspan='4'>MOVIMENTO DE SAÍDA DE PEÇAS</td></tr>";
			echo "<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
			echo "<td>Nota Fiscal</td>";
			echo "<td>Emissão</td>";
			echo "<td>Tipo Pedido</td>";
			echo "<td>Qtde</td>";
			echo "</tr>";

			$total_qtde = 0;

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$total_qtde += pg_result($res, $i, qtde);

				$cor = "#eeeeee";
				if (($i%2) == 0) $cor = '#cccccc';

				echo "<tr bgcolor='$cor'>";

				echo "<td title='Número da nota fiscal'>";
				echo pg_result ($res,$i,nota_fiscal);
				echo "-";
				echo pg_result ($res,$i,cfop);
		//		if (strlen (trim (pg_result ($res,$i,para_referencia))) > 0) echo "<br>" . pg_result ($res,$i,para_referencia);
				echo "</td>";

				echo "<td title='Data emissão'>";
				echo pg_result ($res,$i,emissao);
		//		if (strlen (trim (pg_result ($res,$i,para_referencia))) > 0) echo "<br>" . pg_result ($res,$i,para_descricao);
				echo "</td>";

				echo "<td align='center' title='Tipo do pedido'>&nbsp;";
				echo pg_result ($res,$i,tipo_pedido_descricao);
				echo "</td>";

				$qtde_fabrica = pg_result ($res,$i,qtde);
				if ($qtde_fabrica < 0) $qtde_fabrica = 0;


				echo "<td align='center' title='Quantidade'>&nbsp;";
				echo pg_result ($res,$i,qtde);
				echo "</td>";

				echo "</tr>";
			}

			//HD 211681: Totalizar as saídas
			echo "
			<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>
			<td>TOTAIS</td>
			<td></td>
			<td></td>
			<td>$total_qtde</td>
			</tr>";
			echo "</table>";

		}else{
			echo "<br><center><b> MOVIMENTO DE SAÍDA DE PEÇAS</center></b>";
			echo "<CENTER><span class='vermelho'> Não foi encontrado Movimento de SAÍDA de Peças </span></CENTER>";

		}


	//**************ACERTO************//
		$sql = "SELECT  tbl_posto_estoque_acerto.posto_estoque_acerto,
						tbl_posto_estoque_acerto.qtde,
						tbl_posto_estoque_acerto.motivo,
						TO_CHAR(tbl_posto_estoque_acerto.data,'DD/MM/YYYY')AS data
				FROM tbl_posto_estoque_acerto
				JOIN tbl_peca ON tbl_peca.peca = tbl_posto_estoque_acerto.peca
				WHERE tbl_peca.referencia = '$referencia'
				ORDER BY tbl_posto_estoque_acerto.data ASC";
		$res = pg_exec ($con,$sql);

		if(pg_numrows ($res)>0){
			echo "<br><table align='center' border='0' cellspacing='1' cellpaddin='1'>";
			echo "<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'><td colspan='4'>MOVIMENTO DE ACERTO DE PEÇAS</td></tr>";
			echo "<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
			echo "<td>Registro</td>";
			echo "<td>Qtde</td>";
			echo "<td>Motivo</td>";
			echo "<td>Data</td>";
			echo "</tr>";

			$total_qtde = 0;

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$total_qtde += pg_result($res, $i, qtde);

				$cor = "#eeeeee";
				if (($i%2) == 0) $cor = '#cccccc';

				echo "<tr bgcolor='$cor'>";

				echo "<td align='center' title='Código da peça'>&nbsp;";
				echo pg_result ($res,$i,posto_estoque_acerto);
				echo "</td>";

				$qtde_fabrica = pg_result ($res,$i,qtde);
				if ($qtde_fabrica < 0) $qtde_fabrica = 0;

				echo "<td align='center' title='Quantidade'>&nbsp;";
				echo pg_result ($res,$i,qtde);
				echo "</td>";

				echo "<td align='left' title='Motivo'>&nbsp;";
				echo pg_result ($res,$i,motivo);
				echo "</td>";

				echo "<td align='center' title='Data'>&nbsp;";
				echo pg_result ($res,$i,data);
				echo "</td>";


				echo "</tr>";
			}
			echo "
			<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>
			<td>TOTAIS</td>
			<td>$total_qtde</td>
			<td></td>
			<td></td>
			</tr>";
			echo "</table>";
		}else{
			echo "<br><center><b> MOVIMENTO ACERTO</center></b>";
			echo "<span class='vermelho'><CENTER>Não foi encontrado Movimento de ACERTO de Peças </CENTER></span>";
		}
	}
}

?>


<? #include "rodape.php"; ?>

</body>
</html>
<?
include'rodape.php';
?>

<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';


$agrupada = "";
for ($i = 0 ; $i < $_POST['qtde_nf'] ; $i++) {
	$nf = trim ($_POST['agrupada_' . $i]);
	if (strlen ($nf) > 0) {
		$agrupada .= $nf . ",";
	}
}
$agrupada = substr ($agrupada,0,strlen ($agrupada)-1);

$btn_acao = trim ($_POST['btn_acao']);
if (strlen ($btn_acao) > 0) {
	$res = pg_query ($con,"BEGIN TRANSACTION");

	#-------------- Confirma conferência atual ----------#
	$qtde_item   = $_POST['qtde_item'];
	$faturamento = $_POST['faturamento'];
	$agrupada    = $_POST['agrupada'];

	$faturamentos =(!empty($faturamento)) ? $faturamento : $agrupada;
	# LOG
	$arquivo  = fopen ("log_nf_entrada.txt", "a+");
	fwrite($arquivo, "\n\n INICIO ---------------\n ".date("d/m/Y H:i:s")."\n\n [ POST ]\n");

	$sql="UPDATE tbl_faturamento_item SET
				qtde_estoque = 0 ,
				qtde_quebrada = 0
			WHERE tbl_faturamento_item.faturamento in ($faturamentos) ";
	$res = pg_query ($con,$sql);

	for ($i = 0 ; $i < $qtde_item ; $i++) {
		$peca             = $_POST['peca_' . $i];
		$qtde_estoque     = $_POST['qtde_estoque_'  . $i];
		$qtde_quebrada    = $_POST['qtde_quebrada_' . $i];
		$localizacao      = $_POST['localizacao_'   . $i];

		fwrite($arquivo, "Peça: $peca - Qtde Estoque: $qtde_estoque - Qtde Quebrada: $qtde_quebrada \n");

		$localizacao = strtoupper (trim ($localizacao));

		if (strlen ($qtde_estoque)  == 0) $qtde_estoque  = "0";
		if (strlen ($qtde_quebrada) == 0) $qtde_quebrada = "0";

		$sql = "SELECT	faturamento_item,
						faturamento     ,
						qtde
				FROM tbl_faturamento_item
				WHERE faturamento in ( $faturamentos ) 
				AND peca = $peca
				ORDER BY qtde,faturamento_item";
		$res = pg_query ($con,$sql);
		if(pg_num_rows($res) > 0){
			$total_qtde = $qtde_estoque;
			for($j =0;$j<pg_num_rows($res);$j++) {
				$aux_faturamento  = pg_fetch_result($res,$j,faturamento);
				$faturamento_item = pg_fetch_result($res,$j,faturamento_item);
				$qtde             = pg_fetch_result($res,$j,qtde);
				
				if(($total_qtde - $qtde) >= 0) {
					$sqlu="UPDATE tbl_faturamento_item SET
								qtde_estoque  = $qtde         
							WHERE tbl_faturamento_item.peca = $peca
							AND tbl_faturamento_item.faturamento = $aux_faturamento
							AND tbl_faturamento_item.faturamento_item = $faturamento_item";
					$resu = pg_query ($con,$sqlu);
				}else{
					$sqlu="UPDATE tbl_faturamento_item SET
								qtde_estoque  = $total_qtde   
							WHERE tbl_faturamento_item.peca = $peca
							AND tbl_faturamento_item.faturamento = $aux_faturamento
							AND tbl_faturamento_item.faturamento_item = $faturamento_item";
					$resu = pg_query ($con,$sqlu);
				}
				$total_qtde -=$qtde;
				if($total_qtde <= 0) {
					break;
				}
			}
		}

		$sql = "SELECT	faturamento_item,
						faturamento     ,
						qtde
				FROM tbl_faturamento_item
				WHERE faturamento in ( $faturamentos ) 
				AND peca = $peca
				AND qtde_estoque = 0
				ORDER BY qtde,faturamento_item";
		$res = pg_query ($con,$sql);
		if(pg_num_rows($res) > 0){
			$total_qtde_quebrada = $qtde_quebrada;

			for($j =0;$j<pg_num_rows($res);$j++) {
				$aux_faturamento  = pg_fetch_result($res,$j,faturamento);
				$faturamento_item = pg_fetch_result($res,$j,faturamento_item);
				$qtde             = pg_fetch_result($res,$j,qtde);
				
				if(($total_qtde_quebrada - $qtde) >= 0) {
					$sqlu="UPDATE tbl_faturamento_item SET
								qtde_quebrada  = $qtde         
							WHERE tbl_faturamento_item.peca = $peca
							AND tbl_faturamento_item.faturamento = $aux_faturamento
							AND tbl_faturamento_item.faturamento_item = $faturamento_item";
					$resu = pg_query ($con,$sqlu);
				}else{
					$sqlu="UPDATE tbl_faturamento_item SET
								qtde_quebrada  = $total_qtde_quebrada   
							WHERE tbl_faturamento_item.peca = $peca
							AND tbl_faturamento_item.faturamento = $aux_faturamento
							AND tbl_faturamento_item.faturamento_item = $faturamento_item";
					$resu = pg_query ($con,$sqlu);
				}
				$total_qtde_quebrada -=$qtde;
				if($total_qtde_quebrada <= 0) {
					break;
				}
			}
		}

		$sqlp = "SELECT  pedido
					FROM tbl_pedido
					WHERE fabrica in (10,51,81)
					AND   tbl_pedido.data >'2010-01-08 00:00:00'
					AND   (tbl_pedido.status_pedido in (1,2,5,7,9,11,
					12) OR tbl_pedido.status_pedido IS NULL)
					AND   distribuidor in (58810,26907)
					ORDER BY data ASC ";
		$resp = pg_query($con,$sqlp);
		$total_qtde = $qtde_estoque;
		if(pg_num_rows($resp) > 0){
			for($k =0;$k<pg_num_rows($resp);$k++) {
				$pedido        = pg_fetch_result($resp,$k,pedido);

				$sqli = " SELECT pedido_item,
								qtde,
								qtde_faturada
						FROM tbl_pedido_item
						WHERE peca   = $peca
						AND   pedido = $pedido
						AND   (qtde > (qtde_faturada + qtde_cancelada) or qtde_faturada+ qtde_cancelada =0)";
				$resi = pg_query($con,$sqli);
				if(pg_num_rows($resi) > 0){
					for($l =0;$l<pg_num_rows($resi);$l++) {
						$pedido_item   = pg_fetch_result($resi,$l,pedido_item);
						$qtde_peca     = pg_fetch_result($resi,$l,qtde);
						$qtde_faturada = pg_fetch_result($resi,$l,qtde_faturada);

						if(($total_qtde - $qtde_peca) >= 0) {
							$qtde_faturada_atualiza = $qtde_peca - $qtde_faturada;
							$sqlq = " SELECT fn_atualiza_pedido_item($peca,$pedido,$pedido_item,$qtde_faturada_atualiza) ";
							$resq = pg_query($con,$sqlq);
						}else{
							$sqlq = " SELECT fn_atualiza_pedido_item($peca,$pedido,$pedido_item,$total_qtde) ";
							$resq = pg_query($con,$sqlq);
						}

						$sqlq = " SELECT fn_atualiza_status_pedido($fabrica,$pedido)";
						$resq = pg_query($con,$sqlq);

						$total_qtde -=$qtde_peca;
						if($total_qtde <= 0) {
							break;
						}
					}
				}
			}
		}
						

		fwrite($arquivo, "\n\nSQL : $sql \n\n");

		$sql = "UPDATE tbl_posto_estoque_localizacao SET
					localizacao = '$localizacao'
				WHERE posto = $login_posto
				AND peca = $peca ";
		$res = pg_query ($con,$sql);
	}

	$sql = "UPDATE tbl_faturamento SET 
				conferencia = current_timestamp
			WHERE faturamento in ($faturamentos) ";
	$res = pg_query ($con,$sql);

	#----------- Cria Embarque Vinculado a NF -------------
	$sql = "SELECT	tbl_os_item.os_item,
					tbl_pedido_item.pedido_item,
					tbl_os_item.qtde,
					tbl_pedido.posto,
					tbl_faturamento_item.peca,
					tbl_faturamento_item.qtde_estoque
			FROM tbl_faturamento_item
			JOIN tbl_os_produto  ON tbl_faturamento_item.os = tbl_os_produto.os
			JOIN tbl_os_item     ON tbl_faturamento_item.peca = tbl_os_item.peca AND tbl_os_produto.os_produto = tbl_os_item.os_produto
			JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_os_item.pedido AND tbl_pedido_item.peca = tbl_os_item.peca
			JOIN tbl_pedido      ON tbl_pedido_item.pedido = tbl_pedido.pedido
			WHERE tbl_faturamento_item.faturamento in ( $faturamentos )
			AND   tbl_pedido_item.qtde > (tbl_pedido_item.qtde_faturada_distribuidor + tbl_pedido_item.qtde_cancelada) ";
	$res = pg_query ($con,$sql);

	fwrite($arquivo, "\n\nSQL : $sql \n\n");

	for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
		$posto       = pg_fetch_result ($res,$i,posto);
		$peca        = pg_fetch_result ($res,$i,peca);
		$qtde        = pg_fetch_result ($res,$i,qtde);
		$pedido_item = pg_fetch_result ($res,$i,pedido_item);
		$os_item     = pg_fetch_result ($res,$i,os_item);

		if (strlen ($os_item) == 0) $os_item = "null";

		$sql = "SELECT fn_embarca_item ($login_posto, $posto, $peca, $qtde::float, $pedido_item, $os_item)";
		$resX = pg_query ($con,$sql);
		fwrite($arquivo, "\n\n --> SQL ITEM (Posto $posto | Peça: $peca | Qtde: $qtde | Ped.Item: $pedido_item | OS Item: $os_item ) -> \n\n $sql \n\n");
	}

	fclose ($arquivo);

	$res = pg_query ($con,"COMMIT TRANSACTION");
	header ("Location: nf_entrada.php");
	exit;
}


?>

<html>
<head>
<title>Itens da NF de Entrada</title>
</head>
<script>
	function verificaQtde(peca,qtde_estoque,qtde_quebrada) {
		var peca_qtde = document.getElementById(peca).value;

		if (qtde_quebrada.value.length ==0 || qtde_quebrada.value == '') {
			qtde_quebrada.value = 0 ;
		}
		
		if (qtde_estoque.value.length ==0 || qtde_estoque.value == '') {
			qtde_estoque.value = 0 ;
		}

		var total_qtde = parseInt(qtde_estoque.value) + parseInt(qtde_quebrada.value);

		if(total_qtde > peca_qtde){
			alert('Por favor, fazer acerto de estoque com as peças que vieram a mais no recebimento da mercadoria.');

			qtde_quebrada.value = "";
			qtde_estoque.value = "";
			qtde_estoque.focus();
		}
	}

</script>

<body>

<? include 'menu.php' ?>


<?
$faturamento = $_GET['faturamento'];
if (strlen ($faturamento) > 0) {
	$sql = "SELECT nota_fiscal,
			TO_CHAR (conferencia,'DD/MM/YYYY') AS conferencia,
			TO_CHAR (emissao,'DD/MM/YYYY') AS emissao ,
			TO_CHAR (conferencia - emissao , 'DD') AS trafego
			FROM tbl_faturamento WHERE faturamento = $faturamento
			AND posto = $login_posto";
	$res = pg_query ($con,$sql);
	$nota_fiscal = pg_fetch_result ($res,0,nota_fiscal);
	$emissao     = pg_fetch_result ($res,0,emissao);
	$conferencia = pg_fetch_result ($res,0,conferencia);
	$trafego     = pg_fetch_result ($res,0,trafego);
}

if (strlen ($agrupada) > 0) {
	$sql = "SELECT nota_fiscal
			FROM tbl_faturamento
			WHERE faturamento IN ($agrupada)
			AND posto = $login_posto";
	$res = pg_query ($con,$sql);
	$nota_fiscal = "";
	for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
		$nota_fiscal .= pg_fetch_result ($res,$i,nota_fiscal) . " - " ;
	}
	$nota_fiscal = substr ($nota_fiscal,0,strlen ($nota_fiscal)-3);
	$faturamento = $agrupada;
}


?>

<center><h1>Itens da NF de Entrada - <? echo $nota_fiscal ?></h1></center>

<?
if (strlen ($emissao) > 0) {
	echo "<center>NF Emitida em $emissao </center>";
}
if (strlen ($conferencia) > 0) {
	echo "<center>Mercadoria recebida em $conferencia ($trafego dias de tráfego). </center>";
}
?>

<p>

<table width='600' align='center'>
<tr bgcolor='#FF9933' style='color:#ffffff ; font-weight:bold'>
	<td align='center'>Peça</td>
	<td align='center'>Descrição</td>
	<td align='center'>Qtde NF</td>
	<td align='center'>Qtde ESTOQUE</td>
	<td align='center'>Localização</td>
	<td align='center'>Qtde Quebrada</td>
</tr>


<?


$sql = "SELECT	tbl_peca.peca,
				tbl_peca.referencia,
				tbl_peca.descricao,
				fat.qtde,
				fat.qtde_estoque,
				tbl_posto_estoque_localizacao.localizacao,
				fat.qtde_quebrada
		FROM (
			SELECT tbl_faturamento_item.peca,
					SUM (tbl_faturamento_item.qtde) AS qtde,
					SUM (tbl_faturamento_item.qtde_estoque) AS qtde_estoque,
					SUM (tbl_faturamento_item.qtde_quebrada) AS qtde_quebrada
			FROM tbl_faturamento_item
			JOIN tbl_faturamento USING (faturamento)
			WHERE tbl_faturamento.faturamento IN ($faturamento)
			AND   tbl_faturamento.posto       = $login_posto
			GROUP BY tbl_faturamento_item.peca
		) fat
		JOIN tbl_peca ON fat.peca = tbl_peca.peca
		LEFT JOIN tbl_posto_estoque_localizacao ON fat.peca = tbl_posto_estoque_localizacao.peca AND tbl_posto_estoque_localizacao.posto = $login_posto
		ORDER BY tbl_peca.referencia";
$res = pg_query ($con,$sql);

echo "<form method='post' action='$PHP_SELF' name='frm_nf_entrada_item'>";
echo "<input type='hidden' name='faturamento' value='$faturamento'>";
echo "<input type='hidden' name='agrupada' value='$agrupada'>";

for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
	$referencia       = trim(pg_fetch_result($res,$i,referencia)) ;
	$descricao        = trim(pg_fetch_result($res,$i,descricao));
	$peca             = trim(pg_fetch_result($res,$i,peca));
	$qtde             = trim(pg_fetch_result($res,$i,qtde));
	$qtde_estoque     = trim(pg_fetch_result($res,$i,qtde_estoque));
	$qtde_quebrada    = trim(pg_fetch_result($res,$i,qtde_quebrada));
	$localizacao      = trim(pg_fetch_result($res,$i,localizacao));

	if (strlen ($msg_erro) > 0) $qtde_estoque = $_POST['qtde_estoque_' . $i];
	if (strlen ($msg_erro) > 0) $localizacao  = $_POST['localizacao_' . $i];

	$cor = "#ffffff";
	if ($i % 2 == 0) $cor = "#FFEECC";

	echo "<input type='hidden' name='peca_$i' value='$peca'>";
	echo "<input type='hidden' id='$peca' value='$qtde'>";

	echo "<tr style='font-size: 12px' bgcolor='$cor'>\n";
	echo "<td align='left' nowrap><font size='3'><b>$referencia</b></font></td>\n";
	echo "<td align='left' nowrap>$descricao</td>\n";

	if ($qtde_estoque == 0) $qtde_estoque = "";
	if ($qtde_quebrada == 0) $qtde_quebrada = "";

	echo "<td align='right' nowrap><font size='3'><b>$qtde</b></font></td>\n";
	echo "<td align='right' nowrap><input type='text' name='qtde_estoque_$i'  value='$qtde_estoque' id='qtde_estoque_$i' size='5'  maxlength='5' onblur='verificaQtde($peca,this,document.getElementById(\"qtde_quebrada_$i\"))'></td>\n";
	echo "<td align='right' nowrap><input type='text' name='localizacao_$i'   value='$localizacao'   size='10' maxlength='15'></td>\n";
	echo "<td align='right' nowrap><input type='text' name='qtde_quebrada_$i' value='$qtde_quebrada' size='5'  maxlength='5' id='qtde_quebrada_$i' onblur='verificaQtde($peca,document.getElementById(\"qtde_estoque_$i\"),this)'></td>\n";
	echo "</tr>\n";
}


echo "<tr>";
echo "<td colspan='5' align='center'>";
echo "<input type='hidden' name='qtde_item' value='$i'>";
echo "<input type='hidden' name='btn_acao'   value=''>";
echo "<input type='button' name='btn_conferir' value='Conferida !' OnClick=\"
			javascript:
			if (document.frm_nf_entrada_item.btn_acao.value == ''){
				document.frm_nf_entrada_item.btn_acao.value='Conferida !';
				document.frm_nf_entrada_item.submit();
			}else{
				alert('Aguarde submissão.');
			}
			\">";
echo "</form>";
echo "</td>";
echo "</tr>";
echo "</table>\n";

?>

<p>

</body>
</html>
<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include '../funcoes.php';

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
	
	$qtde_item   = $_POST['qtde_item'];
	
	for ($i = 0 ; $i < $qtde_item ; $i++) {
		$localizacao      = $_POST['localizacao_'   . $i];
		$qtde_estoque     = $_POST['qtde_estoque_'  . $i];

		if(!valida_mascara_localizacao($localizacao) and strlen($qtde_estoque) > 0){
			$msg_erro .= "Localização $localizacao inválida. <br>";
		}

	}

if(strlen(trim($msg_erro)) == 0){

	$res = pg_query ($con,"BEGIN TRANSACTION");

	#-------------- Confirma conferência atual ----------#	
	$faturamento = $_POST['faturamento'];
	$agrupada    = $_POST['agrupada'];

	$faturamentos =(!empty($faturamento)) ? $faturamento : $agrupada;
	# LOG
	$arquivo  = fopen ("../nfephp2/log_nf_entrada_item.txt", "a+");
	fwrite($arquivo, "\n\n INICIO ---------------\n ".date("d/m/Y H:i:s")."\n\n [ POST ]\n");

	if(!empty($agrupada)) {
		$sql="UPDATE tbl_faturamento_item SET
					qtde_estoque = 0 ,
					qtde_quebrada = 0
				WHERE tbl_faturamento_item.faturamento in ($faturamentos) and qtde_estoque isnull and qtde_quebrada isnull ";
		$res = pg_query ($con,$sql);
	}
	for ($i = 0 ; $i < $qtde_item ; $i++) {
		$peca             = $_POST['peca_' . $i];
		$preco            = $_POST['preco_' . $i];
		$qtde_estoque     = $_POST['qtde_estoque_'  . $i];
		$qtde_quebrada    = $_POST['qtde_quebrada_' . $i];
		$localizacao      = $_POST['localizacao_'   . $i];

		fwrite($arquivo, "Peça: $peca - Qtde Estoque: $qtde_estoque - Qtde Quebrada: $qtde_quebrada \n");

		$localizacao = strtoupper (trim ($localizacao));

		if(strlen($qtde_estoque) == 0)  continue ; 
		if(empty($peca))  continue ; 

		if (strlen ($qtde_estoque)  == 0) $qtde_estoque  = "0";
		if (strlen ($qtde_quebrada) == 0) $qtde_quebrada = "0";

		$sql = "SELECT	faturamento_item,
						faturamento     ,
						qtde
				FROM tbl_faturamento_item
				WHERE faturamento in ( $faturamentos ) 
				AND peca = $peca
				AND preco = '$preco'
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
								qtde_estoque  = case when qtde > $qtde then qtde_estoque + $qtde else $qtde end
							WHERE tbl_faturamento_item.peca = $peca
							AND tbl_faturamento_item.faturamento = $aux_faturamento
							AND tbl_faturamento_item.faturamento_item = $faturamento_item ; 

						    INSERT INTO tbl_posto_estoque_movimento(
                                posto,
                                peca,
                                qtde_entrada,
                                faturamento,
                                faturamento_item,
                                data,
                                nf, 
                                login_unico
                            ) SELECT 
                                4311,
                                $peca,
                                $qtde,
								faturamento,
								$faturamento_item,
                                now(),
                                nota_fiscal, 
                                $login_unico
                                FROM tbl_faturamento WHERE faturamento = $aux_faturamento ;
";
					$resu = pg_query ($con,$sqlu);

				}else{
					$sqlu="UPDATE tbl_faturamento_item SET
								qtde_estoque  = case when qtde > $total_qtde then qtde_estoque + $total_qtde else $total_qtde end
							WHERE tbl_faturamento_item.peca = $peca
							AND tbl_faturamento_item.faturamento = $aux_faturamento
							AND tbl_faturamento_item.faturamento_item = $faturamento_item ; 

						    INSERT INTO tbl_posto_estoque_movimento(
                                posto,
                                peca,
                                qtde_entrada,
                                faturamento,
                                faturamento_item,
                                data,
                                nf, 
                                login_unico
                            ) SELECT 
                                4311,
                                $peca,
                                $total_qtde,
								faturamento,
								$faturamento_item,
                                now(),
                                nota_fiscal, 
                                $login_unico
                                FROM tbl_faturamento WHERE faturamento = $aux_faturamento 
";
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

		$sqlp = "SELECT  pedido,
						fabrica
					FROM tbl_pedido
					WHERE fabrica in (10,51,81,122)
					AND   tbl_pedido.data >'2010-01-08 00:00:00'
					AND   (tbl_pedido.status_pedido in (1,2,5,7,9,11,12) OR tbl_pedido.status_pedido IS NULL)
					AND   distribuidor in (58810,26907)
					ORDER BY data ASC ";
		$resp = pg_query($con,$sqlp);
		$total_qtde = $qtde_estoque;
		$total_qtde = number_format($total_qtde,0,".","");
		if(pg_num_rows($resp) > 0){
			for($k =0;$k<pg_num_rows($resp);$k++) {
				$pedido        = pg_fetch_result($resp,$k,pedido);
				$fabrica       = pg_fetch_result($resp,$k,fabrica);

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

						$sqlq = "SELECT fn_atualiza_status_pedido($fabrica,$pedido)";
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

		$sql_fab_peca = "SELECT fabrica, referencia FROM tbl_peca WHERE peca = $peca";
		$res_fab_peca = pg_query($con, $sql_fab_peca);
		$fab_peca = pg_fetch_result($res_fab_peca, 0, 'fabrica');
		$ref_peca = pg_fetch_result($res_fab_peca, 0, 'referencia');

		if (in_array($fab_peca, [11,172])) {
			atualiza_localizacao_lenoxx($peca, $localizacao, $login_posto);
		} else {
			$sql = "UPDATE tbl_posto_estoque_localizacao SET
						localizacao = '$localizacao', posto = $login_posto
					WHERE peca = $peca ";
			$res = pg_query ($con,$sql);
		}
	}

	$sql = "
			with item as ( select count(1) as qtde, faturamento  from tbl_faturamento_item where faturamento in ( $faturamentos) group by faturamento),
			 conferencia as(select count(1) as conferencia, faturamento  from tbl_faturamento_item where faturamento in ($faturamentos)  and (qtde_estoque > 0 or qtde_quebrada > 0) group by faturamento)
			 update tbl_faturamento set conferencia = now() from item join conferencia using(faturamento) where item.faturamento = tbl_faturamento.faturamento and qtde = conferencia.conferencia and tbl_faturamento.faturamento in ($faturamentos) ;

";
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

}


?>

<html>
<style>
	#tbl_nfentrada tbody tr td{
		border-bottom: 4px solid #333;		
	}
</style>
<head>
<title>Itens da NF de Entrada</title>
<script type="text/javascript" src="../js/jquery-1.8.3.min.js"></script>
</head>
<script type="text/javascript">
	function alteraMaiusculo(valor){
		var novoTexto = valor.value.toUpperCase();
		valor.value = novoTexto;
	}

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
			alert('Por favor, faça a entrada com a mesma quantidade da NF e fazer acerto de estoque com as peças que vieram a mais no recebimento da mercadoria.');
			qtde_quebrada.value = "";
			qtde_estoque.value = "";
			qtde_estoque.focus();
		}else{
		var qtde_divergente = peca_qtde - parseInt(qtde_estoque.value);
			qtde_quebrada.value = qtde_divergente;
		}
	}

</script>

<body>

<? include 'menu.php' ?>


<?
$faturamento = $_GET['faturamento'];
if(strlen($faturamento)==0) $faturamento = $_POST['faturamento'];
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

 if(strlen(trim($msg_erro))>0){ 
	echo "<div class='msg_erro' style='width:600px; margin:10px auto; font-weight:bold; color:#ffffff; background:red;'> $msg_erro </div>";
 } 
$esconde = $_POST['esconde'];
if($esconde=="nao"){
	$esconde = "display:block";
}else{
	$esconde = "display:none";
}
//echo $esconde;
?>

<p>
<form method='post' name='frm_nf_entrada_item'>

<table width='600' align='center' id='tbl_nfentrada'>
<tr bgcolor='#FF9933' style='color:#ffffff ; font-weight:bold'>
	<td align='center'>Peça</td>
	<td align='center'>Referência Fábrica</td>
	<td align='center'>Descrição</td>
	<td align='center' title='Preço Unitário'>Preço Unitário</td>
	<td align='center' title='Quantidade de produtos/peças que vieram destacada na NF'>Qtde NF</td>
	<td align='center' title='Valor faturado vezes a quantidade'>     </td>
	<td align='center' title='Quantidade de peças conferidas para entrar no Estoque'>Qtde ESTOQUE</td>
	<td align='center' title='Local onde o produto/peça será armazenado'>Localização</td>
	<td align='center' title='Anotar as divergências das peças a menor para cobrança' class='noprint'>Qtde Divergente</td>
	<? // <td align='center' title='Pedidos que foram baixados com esta(s) NF(s)' class='noprint'>Pedidos Baixados</td> ?>
	<td align='center' style="<?echo $esconde;?>" title='Pedidos que foram baixados com esta(s) NF(s)' ><div style="<?echo $esconde;?>">Pedido</div></td>
	<td align='center' style="<?echo $esconde;?>" ><div style="<?echo $esconde;?>">Qtde Baixada</div></td>
</tr>

<?
$sql = "SELECT	distinct tbl_peca.peca,
				tbl_peca.referencia,
				tbl_peca.descricao,
				tbl_peca.referencia_fabrica,
				fat.qtde,
				fat.qtde_estoque,
				tbl_posto_estoque_localizacao.localizacao,
				fat.qtde_quebrada,
				fat.preco_faturamento,
				fat.preco,
				fat.conferencia
		FROM (
			SELECT  tbl_faturamento_item.peca,
					tbl_faturamento_item.preco,
					tbl_faturamento.conferencia,
					SUM (tbl_faturamento_item.qtde) AS qtde,
					SUM (tbl_faturamento_item.qtde_estoque) AS qtde_estoque,
					SUM (tbl_faturamento_item.qtde_quebrada) AS qtde_quebrada,
					SUM (tbl_faturamento_item.preco * tbl_faturamento_item.qtde) AS preco_faturamento
			FROM tbl_faturamento_item
			JOIN tbl_faturamento USING (faturamento)
			WHERE tbl_faturamento.faturamento IN ($faturamento)
			AND   tbl_faturamento.posto       = $login_posto
			GROUP BY tbl_faturamento_item.peca, tbl_faturamento_item.preco, tbl_faturamento.conferencia
		) fat
		JOIN tbl_peca ON fat.peca = tbl_peca.peca
		LEFT JOIN tbl_posto_estoque_localizacao ON fat.peca = tbl_posto_estoque_localizacao.peca 
		ORDER BY tbl_peca.referencia";
$res = pg_query ($con,$sql);


echo "<input type='hidden' name='faturamento' value='$faturamento'>";
echo "<input type='hidden' name='agrupada' value='$agrupada'>";

for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
	$referencia       	= trim(pg_fetch_result($res,$i,referencia)) ;
	$descricao        	= trim(pg_fetch_result($res,$i,descricao));
	$referencia_fabrica     = trim(pg_fetch_result($res,$i,referencia_fabrica));
	$peca             	= trim(pg_fetch_result($res,$i,peca));
	$qtde             	= trim(pg_fetch_result($res,$i,qtde));
	$preco_faturamento	= number_format(trim(pg_fetch_result($res,$i,preco_faturamento)),2,',',' ');
	$preco				= number_format(trim(pg_fetch_result($res,$i,preco)),2,',',' ');
	$xpreco				= pg_fetch_result($res,$i,preco);
	$qtde_estoque     	= trim(pg_fetch_result($res,$i,qtde_estoque));
	$qtde_quebrada    	= trim(pg_fetch_result($res,$i,qtde_quebrada));
	$localizacao      	= trim(pg_fetch_result($res,$i,localizacao));
	$conferencia      	= trim(pg_fetch_result($res,$i,'conferencia'));

	if(($qtde_estoque+$qtde_quebrada) == $qtde and empty($conferencia)) continue;

	if (strlen ($msg_erro) > 0) $qtde_estoque = $_POST['qtde_estoque_' . $i];
	if (strlen ($msg_erro) > 0) $localizacao  = $_POST['localizacao_' . $i];

	$cor = "#ffffff";
	if ($i % 2 == 0) $cor = "#FFEECC";

	echo "<input type='hidden' name='peca_$i' value='$peca'>";
	echo "<input type='hidden' name='preco_$i' value='$xpreco'>";
	echo "<input type='hidden' id='$peca-$i' value='$qtde'>";

	$sql_pb = "SELECT pedido, qtde_baixada 
				FROM  tbl_faturamento_item_baixa_pedido
				WHERE faturamento_item in (select faturamento_item from tbl_faturamento_item where faturamento in ($faturamento) and peca = $peca)";
	$res_pb = pg_query($con,$sql_pb);
	if(pg_num_rows($res_pb)>0){
		if($esconde=="display:none") {
			$rowspan=1;
		}else{
			$rowspan = pg_num_rows($res_pb);
		}
	}else{
		$rowspan = 1;
	}


	echo "<tr style='font-size: 12px' bgcolor='$cor'>\n";
	echo "<td align='left' rowspan='{$rowspan}' nowrap><font size='3' class='Matricial'><b>$referencia</b></font></td>\n";
	echo "<td align='left' rowspan='{$rowspan}' nowrap><font size='3' class='Matricial'><b>$referencia_fabrica</b></font></td>\n";
	echo "<td align='left' rowspan='{$rowspan}' nowrap class='descricaoprint'>$descricao</td>\n";

	if ($qtde_estoque == 0) $qtde_estoque = "";
	if ($qtde_quebrada == 0) $qtde_quebrada = "";

	echo "<td align='right' rowspan='{$rowspan}' nowrap><font size='3' class='qtdeimpressao'  style='font-size: 12px;'>&nbsp;R$ $preco&nbsp;</font></td>\n";
	echo "<td align='right' rowspan='{$rowspan}' nowrap><font size='3' class='qtdeimpressao'  style='font-size: 12px;'>&nbsp;$qtde&nbsp;</font></td>\n";
	echo "<td align='right' rowspan='{$rowspan}' nowrap><font size='3' class='qtdeimpressao' style='font-size: 12px;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</font></td>\n";
	echo "<td align='right' rowspan='{$rowspan}' nowrap class='qtdeimpressao'><input type='text' name='qtde_estoque_$i'  value='$qtde_estoque' class='qtdeimpressao' id='qtde_estoque_$i' size='5'  maxlength='5' onblur='verificaQtde(\"$peca-$i\",this,document.getElementById(\"qtde_quebrada_$i\"))'></td>\n";
	echo "<td align='right' rowspan='{$rowspan}' nowrap><input type='text' onkeyup='alteraMaiusculo(this)' name='localizacao_$i' class='localizacao' value='$localizacao' size='10' maxlength='15' pattern='[A-Z][A-Z]-[A-Z]\\d\\d-[A-Z]\\d\\d|[A-Z]\\d\\d-[A-Z]\\d\\d|[A-Z]{3}-[A-Z]\\d\\d\\d|[A-Z]\\d\\d\\d-[A-Z]\\d\\d' ></td>\n";
	echo "<td align='center' rowspan='{$rowspan}' nowrap class='noprint'><input type='text' name='qtde_quebrada_$i' value='$qtde_quebrada'  size='5'  maxlength='5' id='qtde_quebrada_$i' onblur='verificaQtde(\"$peca-$i\",document.getElementById(\"qtde_estoque_$i\"),this)'></td>\n";
	//echo "<td align='center' nowrap >";
	//	$sql_pb = "SELECT pedido, qtde_baixada 
	//			FROM  tbl_faturamento_item_baixa_pedido
	//			WHERE faturamento_item in (select faturamento_item from tbl_faturamento_item where faturamento in ($faturamento) and peca = $peca)";
	//$res_pb = pg_query($con,$sql_pb);
	if(pg_num_rows($res_pb)>0){
		//echo "<table>";
		//echo "<tr bgcolor='#FF9933' style='color:#ffffff ; font-weight:bold'><td align='center'>Pedido</td><td align='center'>Qtde Baixada</td></tr>";
		//for($j = 0; $j < pg_num_rows($res_pb); $j++){
			$pedido_pb = pg_fetch_result($res_pb,0,pedido);
			$qtde_baixada_pb = pg_fetch_result($res_pb,0,qtde_baixada);
			//echo "<tr>";
			echo "<td style=\"$esconde\" align='center' >$pedido_pb</td><td style=\"$esconde\" align='center'>$qtde_baixada_pb</td>";
			//echo "</tr>";
		//}
		//echo "</table>";
	}
	//echo "</td>\n";
	echo "</tr>\n";

	if(pg_num_rows($res_pb)>0){
		//echo "<table>";
		//echo "<tr bgcolor='#FF9933' style='color:#ffffff ; font-weight:bold'><td align='center'>Pedido</td><td align='center'>Qtde Baixada</td></tr>";
		for($j = 1; $j < $rowspan; $j++){
			$pedido_pb = pg_fetch_result($res_pb,$j,pedido);
			$qtde_baixada_pb = pg_fetch_result($res_pb,$j,qtde_baixada);
			echo "<tr>";
			echo "<td style=\"$esconde\" align='center'>$pedido_pb</td><td style=\"$esconde\" align='center'>$qtde_baixada_pb</td>";
			echo "</tr>";
		}
		//echo "</table>";
	}



}


echo "<tr>";
echo "<td colspan='6' align='center' style='border:none'>";
echo "<input type='hidden' name='faturamento' value='$faturamento'>";

echo "<input type='hidden' name='qtde_item' value='$i'>";
echo "<input type='hidden' name='btn_acao'   value=''>";
echo "<input type='button' name='btn_conferir' value='Conferida !' OnClick=\"
			javascript:
			if (document.frm_nf_entrada_item.btn_acao.value == ''){
				if(!this.form.checkValidity() ){
					alert('É preciso que o formato corresponda ao exigido: \\nFormato Válido: LL-LNN-LNN, LNN-LNN, LLL-LNNN, LNNN-LNN');
					return true;
				}
				document.frm_nf_entrada_item.btn_acao.value='Conferida !';				
				document.frm_nf_entrada_item.submit();
			}else{
				alert('Aguarde submissão.');
			}
			\">";
echo "<input type='hidden' name='esconde' value=''>";
echo "<input type='button' name='btn_esconde' value='Esconde Pedido' OnClick=\"
			javascript:
			if (document.frm_nf_entrada_item.esconde.value == ''){
				document.frm_nf_entrada_item.esconde.value='esconde';
				document.frm_nf_entrada_item.submit();
			}else{
				alert('Aguarde submissão.');
			}
			\">";
echo "<input type='button' name='btn_nao_esconde' value='Mostra Pedido' OnClick=\"
			javascript:
			if (document.frm_nf_entrada_item.esconde.value == ''){
				document.frm_nf_entrada_item.esconde.value='nao';
				document.frm_nf_entrada_item.submit();
			}else{
				alert('Aguarde submissão.');
			}
			\">";


echo "</td>";
echo "</tr>";
echo "</table>\n";
echo "</form>";
?>

<p>

<style>
@media print {
	body, a, td, th, h1, h6, form, input, .Matricial {
		font-family: verdana;
		font-size: 11pt;
		font-weight: normal;
	}

	td {
		padding-left: 5px;
	}

	input {
		border: none;
		overflow: visible;
	}

	.descricaoprint {
		font-size: 9pt;
	}

	.qtdeimpressao {
		font-size: 12pt;
	}

	.Matricial{
	}

	.noprint {
		display: none;
	}
}
</style>

</body>
</html>

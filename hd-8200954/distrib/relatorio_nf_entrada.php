<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';


$data_inicial = $_POST['data_inicial'];
$data_final = $_POST['data_final'];
$fabrica = $_POST['fabrica'];
$tipo = $_POST['tipo'];


if(!empty($_POST['ajax'])) {
	if($_POST['ajax'] == 'faturamento') {
			$faturamento = $_POST['faturamento'];

			$sql = "SELECT	tbl_peca.referencia,
							tbl_peca.descricao,
							tbl_peca.peca,
							tbl_faturamento_item.qtde,
							tbl_faturamento_item.preco,
							tbl_faturamento_item.aliq_ipi,
							tbl_faturamento_item.valor_ipi
				FROM tbl_faturamento_item
				JOIN tbl_peca USING(peca)
				WHERE tbl_faturamento_item.faturamento = $faturamento
				ORDER BY tbl_peca.referencia	";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) >0) {
				$resultado .= "<tr><td colspan='100%'>
						<table align='center' width='600'  border='0' cellspacing='1' cellpadding='1' id='table_$faturamento'>
						<tr bgcolor='#08088A' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>
								<td>Referência</td>
								<td>Descrição</td>
								<td>Qtde</td>
								<td>Preço</td>
								<td>Aliq IPI</td>
								<td>IPI</td>
								</tr>
								";
				for($x = 0; $x < pg_numrows($res);$x++) {

					$referencia  = pg_fetch_result($res,$x,'referencia');
					$peca 	     = pg_fetch_result($res,$x,'peca');
					$qtde     	 = pg_fetch_result($res,$x,'qtde');
					$descricao 	 = pg_fetch_result($res,$x,'descricao');
					$preco   	 = pg_fetch_result($res,$x,'preco');
					$aliq_ipi 	 = pg_fetch_result($res,$x,'aliq_ipi');
					$valor_ipi 	 = pg_fetch_result($res,$x,'valor_ipi');
					$resultado .= "<tr id='".$faturamento."_$peca'><td align='center'><a href='javascript: mostraPecaFaturamento(\"$peca\",\"$faturamento\",\"$qtde\")'>$referencia</td>";
					$resultado .= "<td align='center'>$descricao</td>";
					$resultado .= "<td align='right'>$qtde</td>";
					$resultado .= "<td align='right'>$preco</td>";
					$resultado .= "<td align='right'>$aliq_ipi</td>";
					$resultado .= "<td align='right'>$valor_ipi</td></tr>";
				}
				$resultado.="</table></td></tr>";
				echo $resultado;
			}
	}

	if($_POST['ajax'] == 'peca') {
			$faturamento = $_POST['faturamento'];
			$peca        = $_POST['peca'];
			$qtde 		 = $_POST['qtde'];
			$sql = "SELECT data_input,emissao FROM tbl_faturamento where faturamento = $faturamento";
			$res = pg_query($con,$sql);
			$data_input = pg_fetch_result($res,0,0);
			$emissao  = pg_fetch_result($res,0,1);
			$data = (empty($data_input)) ? $emissao: $data_input;
			$sql = "
					SELECT nota_fiscal,
							cfop,
							emissao,
							faturamento,
							qtde,
							os
					FROM (
							SELECT	distinct tbl_faturamento.nota_fiscal::text as nota_fiscal,
									tbl_faturamento.cfop	,
									to_char(tbl_faturamento.emissao,'DD/MM/YYYY') as emissao,
									tbl_faturamento.faturamento,
									tbl_faturamento_item.qtde,
									case when tbl_faturamento_item.os notnull then tbl_faturamento_item.os else tbl_os_produto.os end as os
							FROM tbl_faturamento
							JOIN tbl_faturamento_item USING (faturamento)
							JOIN tbl_peca USING(peca)
							LEFT JOIN tbl_os_item USING(pedido_item)
							LEFT JOIN tbl_os_produto USING(os_produto)
							WHERE tbl_faturamento.faturamento > $faturamento
							AND   tbl_faturamento_item.peca = $peca
							AND   tbl_faturamento.fabrica = 10
							AND   tbl_faturamento.posto not  in (4311,376542)
							AND   tbl_faturamento.distribuidor in (4311,376542)
							UNION
							SELECT motivo::text	as nota_fiscal,
								null as cfop,
								to_char(data,'DD/MM/YYYY') as emissao,
								null as faturamento,
								qtde*-1,
								null as os
							FROM tbl_posto_estoque_acerto
							WHERE data > '$data'
							AND   qtde < 0
							AND   peca = $peca
						) X
						order by emissao
							";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) >0) {
				$resultado .= "<tr><td colspan='100%'>
						<table align='center' width='600'  border='1' cellspacing='1' cellpadding='1' id='table_$peca'>
						<tr bgcolor='#071914' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>
								<td>Nota Fiscal</td>
								<td>CFOP</td>
								<td>Emissão</td>
								<td>OS</td>
								<td>Qtde</td>
								</tr>
								";
				$qtde_total = 0;
				for($i = 0;$i< pg_num_rows($res);$i++){
					$qtde_total += pg_fetch_result($res,$i,'qtde');
				}
				if($qtde_total < $qtde) {
					for($x=0;$x<pg_numrows($res);$x++) {
							$nota_fiscal = pg_fetch_result($res,$x,'nota_fiscal');
							$cfop 	     = pg_fetch_result($res,$x,'cfop');
							$qtde_peca 	 = pg_fetch_result($res,$x,'qtde');
							$emissao 	 = pg_fetch_result($res,$x,'emissao');
							$os    	 = pg_fetch_result($res,$x,'os');
							$resultado .= "<tr><td align='center'>$nota_fiscal</td>";
							$resultado .= "<td align='center'>$cfop</td>";
							$resultado .= "<td align='right'>$emissao</td>";
							$resultado .= "<td align='right'>$os</td>";
							$resultado .= "<td align='right'>$qtde_peca</td></tr>";
					}
				}else{
						while($qtde > 0 ) {
							$nota_fiscal = pg_fetch_result($res,$x,'nota_fiscal');
							$cfop 	     = pg_fetch_result($res,$x,'cfop');
							$qtde_peca 	 = pg_fetch_result($res,$x,'qtde');
							$emissao 	 = pg_fetch_result($res,$x,'emissao');
							$os    	 = pg_fetch_result($res,$x,'os');
							$resultado .= "<tr><td align='center'>$nota_fiscal</td>";
							$resultado .= "<td align='center'>$cfop</td>";
							$resultado .= "<td align='right'>$emissao</td>";
							$resultado .= "<td align='right'>$os</td>";
							$resultado .= "<td align='right'>$qtde_peca</td></tr>";
							$qtde -= $qtde_peca;
							$x++;
						}
				}
				$resultado.="</table></td></tr>";
				echo $resultado;
			}else{
				echo "Nenhum resultado encontrado";
			}
	}
	exit;
}
$title = 'Relatório de NF de entrada';

?>

<html>
<head>
<title><?echo $title;?></title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>
<?
include_once '../js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist - HD 969678 */
?>
<script>
$(document).ready(function()
    {
        $('#data_inicial').datepick({startDate:'01/01/2000'});
        $('#data_final').datepick({startDate:'01/01/2000'});
});

function mostraFaturamento(faturamento){
	if($('#table_'+faturamento).length >0) {
		$('#table_'+faturamento).toggle();
	}else{

		$.ajax({
			url: '<?$PHP_SELF?>',
			cache: false,
			type: "POST",
			data:{
				faturamento : faturamento,
				ajax : 'faturamento'
			},
			complete: function(retorno){
				$('#'+faturamento).after(retorno.responseText);
			}
		});
	}
}


function mostraPecaFaturamento(peca,faturamento,qtde){
	if($('#table_'+peca).length >0) {
		$('#table_'+peca).toggle();
	}else{
		$.ajax({
			url: '<?$PHP_SELF?>',
			cache: false,
			type: "POST",
			data:{
				faturamento : faturamento,
				peca: peca,
				qtde: qtde,
				ajax : 'peca'
			},
			complete: function(retorno){
				$('#'+faturamento+"_"+peca).after(retorno.responseText);
			}
		});
	}
}

</script>
<body>

<? include 'menu.php' ;

?>
		<center><h1><?echo $title;?></h1></center>

<p>
<?
		if (strlen($msg_erro) > 0) {
			echo "<div style='border: 1px solid #DD0000; background-color: #FFDDDD; color: #DD0000; font-size: 11pt; margin-bottom: 10px; padding: 5px;'>$msg_erro</div><p>";
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
			<td align='center' colspan='6'>Tipo <input type='radio' name='tipo' value='geral'>Geral <input type='radio' name='tipo' value='detalhado'>Detalhado <input type='radio' name='tipo' value='fifo'>Fifo</td>
		</tr>
		<tr>
			<td align='center' colspan='6'><input type='submit' name='btn_acao' id='btn_acao' value='Pesquisar'></td>
		</tr>
	</table>
	<br/>
<?
if(!empty($btn_acao) and empty($msg_erro) ) {
	if($tipo=='geral') {
		if(!empty($data_inicial) and !empty($data_final)){
			$cond = " AND tbl_faturamento.emissao between '$data_inicial' and '$data_final' ";
		}

		$sql = "SELECT  DISTINCT tbl_faturamento.nota_fiscal,
						tbl_faturamento.total_nota,
						tbl_faturamento.cfop,
						tbl_faturamento.faturamento,
						TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY')as emissao,
						tbl_faturamento.emissao as emis,
						tbl_fabrica.nome
				FROM tbl_faturamento
				JOIN tbl_faturamento_item using (faturamento)
				JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca
				JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_peca.fabrica
				WHERE  tbl_faturamento.posto in ( 4311,376542)
				AND (
					tbl_faturamento.distribuidor IN (
						/* Seleciona apenas os distribuidores da condição nova, descartando quando o posto entrava como
						distribuidor (LRG Britania)*/
						SELECT DISTINCT distribuidor FROM tbl_faturamento WHERE fabrica = 10 AND posto = 4311
						and distribuidor is not null and distribuidor <> 4311)
					OR
					tbl_faturamento.fabrica in (10)
					AND tbl_faturamento.distribuidor is null
				)
				AND tbl_faturamento.cancelada IS NULL
				AND tbl_faturamento.fabrica <> 0
				AND (tbl_faturamento.tipo_nf = 0 or tbl_faturamento.tipo_nf IS NULL)
				AND tbl_fabrica.fabrica = $fabrica
				and tbl_faturamento.emissao > current_date - interval '5 years'
				$cond
				ORDER BY tbl_faturamento.emissao,tbl_faturamento.nota_fiscal ASC;";
	$res = pg_exec ($con,$sql);

	echo "<table align='center' border='0' cellspacing='1' cellpadding='1'>";
	echo "<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
	echo "<td>Nota Fiscal</td>";
	echo "<td>Total</td>";
	echo "<td>CFOP</td>";
	echo "<td>Emissão</td>";
	echo "<td>Fábrica</td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$nota_fiscal         = pg_fetch_result($res,$i,'nota_fiscal');
		$faturamento        = pg_fetch_result($res,$i,'faturamento');
		$cfop = pg_fetch_result($res,$i,'cfop');
		$emissao         = pg_fetch_result($res,$i,'emissao');
		$nome         = pg_fetch_result($res,$i,'nome');
		$total_nota     = pg_fetch_result($res,$i,'total_nota');
		$cor = "cccccc";
		if ($i % 2 == 0) $cor = '#eeeeee';

		echo "<tr bgcolor='$cor' style='font-size:11px' id='$faturamento'>";

		echo "<td>";
		echo "<a href='javascript: mostraFaturamento(\"$faturamento\")'>$nota_fiscal</a>";
		echo "</td>";

		echo "<td>";
		echo number_format($total_nota,2,',','.') ;
		echo "</td>";


		echo "<td>";
		echo $cfop;
		echo "</td>";

		echo "<td>";
		echo $emissao;
		echo "</td>";

		echo "<td align='right'>";
		echo $nome;
		echo "</td>";


		echo "</tr>";
	}

	echo "</table>";
	}elseif($tipo=='detalhado'){
		if(!empty($data_inicial) and !empty($data_final)){
			$cond = " AND tbl_faturamento.emissao between '$data_inicial' and '$data_final' ";
		}

		$sql = "SELECT  DISTINCT tbl_faturamento.nota_fiscal,
				tbl_peca.referencia,
				tbl_peca.descricao,
				tbl_faturamento.total_nota,
				to_char(emissao,'DD/MM/YYYY') as emissao,
				tbl_faturamento_item.preco,
				tbl_faturamento_item.qtde,
				tbl_faturamento_item.qtde_estoque,
				tbl_faturamento_item.faturamento_item,
				tbl_faturamento_item.valor_ipi as ipi,
				tbl_faturamento_item.aliq_ipi,
				tbl_faturamento_item.peca,
				tbl_faturamento.faturamento
				FROM tbl_faturamento
				JOIN tbl_faturamento_item using (faturamento)
				JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca
				JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_peca.fabrica
				WHERE  tbl_faturamento.posto in ( 4311,376542)
				AND (
					tbl_faturamento.distribuidor IN (
						/* Seleciona apenas os distribuidores da condição nova, descartando quando o posto entrava como
						distribuidor (LRG Britania)*/
						SELECT DISTINCT distribuidor FROM tbl_faturamento WHERE fabrica = 10 AND posto = 4311
						and distribuidor is not null and distribuidor <> 4311)
					OR
					tbl_faturamento.fabrica in (10)
					AND tbl_faturamento.distribuidor is null
				)
				AND tbl_faturamento.cancelada IS NULL
				AND tbl_faturamento.fabrica <> 0
				AND (tbl_faturamento.tipo_nf = 0 or tbl_faturamento.tipo_nf IS NULL)
				AND tbl_fabrica.fabrica = $fabrica
				and tbl_faturamento.emissao > current_date - interval '5 years'
				$cond
				ORDER BY tbl_peca.referencia,tbl_faturamento.faturamento";
	$res = pg_exec ($con,$sql);

	echo "<table align='center' border='0' cellspacing='1' cellpadding='1'>";
	echo "<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
	echo "<td>Peça</td>";
	echo "<td>Descrição</td>";
	echo "<td>Nota Fiscal</td>";
	echo "<td>Emissão</td>";
	echo "<td>Qtde NF</td>";
	echo "<td>Qtde Rec.</td>";
	echo "<td>Unitário</td>";
	echo "<td>Valor Mercadoria</td>";
	echo "<td>Unitário IPI</td>";
	echo "<td>Total IPI</td>";
	echo "<td>Total</td>";
	echo "<td>Unitário c/IPI</td>";
	echo "<td>Saída Garantia</td>";
	echo "<td>Total Garantia</td>";
	echo "<td>Saída Faturada</td>";
	echo "<td>Total Faturada</td>";
	echo "<td>Saída Devolução</td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$nota_fiscal         = pg_fetch_result($res,$i,'nota_fiscal');
		$emissao         = pg_fetch_result($res,$i,'emissao');
		$total_nota         = pg_fetch_result($res,$i,'total_nota');
		$referencia = pg_fetch_result($res,$i,'referencia');
		$descricao= pg_fetch_result($res,$i,'descricao');
		$faturamento_item= pg_fetch_result($res,$i,'faturamento_item');
		$faturamento= pg_fetch_result($res,$i,'faturamento');
		$preco= pg_fetch_result($res,$i,'preco');
		$qtde= pg_fetch_result($res,$i,'qtde');
		$qtde_estoque= pg_fetch_result($res,$i,'qtde_estoque');
		$ipi= pg_fetch_result($res,$i,'ipi');
		$aliq_ipi= pg_fetch_result($res,$i,'aliq_ipi');
		$peca = pg_fetch_result($res,$i,'peca');

		$qtde_saida = 0 ;
		$qtde_saida_faturada = 0 ;
		$qtde_saida_devolucao = 0 ;
				$sql = "
							SELECT sum(qtde)
							FROM (
									SELECT	sum(tbl_faturamento_fifo.qtde) as qtde
									FROM tbl_faturamento
									JOIN tbl_faturamento_item USING (faturamento)
									JOIN tbl_faturamento_fifo ON tbl_faturamento_item.faturamento_item = tbl_faturamento_fifo.faturamento_item_devolucao
									JOIN tbl_peca USING(peca)
									WHERE tbl_faturamento_item.peca = $peca
									AND   tbl_faturamento.fabrica = 10
									and tbl_faturamento_fifo.faturamento_item_entrada = $faturamento_item 
									and tbl_faturamento.cfop in ('5949','6949')
							) X";
				$resx = pg_query($con,$sql);
				if(pg_num_rows($resx) > 0) {
					$qtde_saida = pg_fetch_result($resx,0,0);
				}else{
					$qtde_saida = 0 ;
				}
				$sql = "
							SELECT sum(qtde)
							FROM (
									SELECT	sum(tbl_faturamento_fifo.qtde) as qtde
									FROM tbl_faturamento
									JOIN tbl_faturamento_item USING (faturamento)
									JOIN tbl_faturamento_fifo ON tbl_faturamento_item.faturamento_item = tbl_faturamento_fifo.faturamento_item_devolucao
									JOIN tbl_peca USING(peca)
									WHERE tbl_faturamento_item.peca = $peca
									AND   tbl_faturamento.fabrica = 10
									and tbl_faturamento_fifo.faturamento_item_entrada = $faturamento_item 
									and tbl_faturamento.cfop not in ('5949','6949','5202')
							) X";
				$resx = pg_query($con,$sql);
				if(pg_num_rows($resx) > 0) {
					$qtde_saida_faturada = pg_fetch_result($resx,0,0);
				}else{
					$qtde_saida_faturada = 0 ;
				}

				$sql = "
							SELECT sum(qtde)
							FROM (
									SELECT	sum(tbl_faturamento_fifo.qtde) as qtde
									FROM tbl_faturamento
									JOIN tbl_faturamento_item USING (faturamento)
									JOIN tbl_faturamento_fifo ON tbl_faturamento_item.faturamento_item = tbl_faturamento_fifo.faturamento_item_devolucao
									JOIN tbl_peca USING(peca)
									WHERE tbl_faturamento_item.peca = $peca
									AND   tbl_faturamento.fabrica = 10
									and tbl_faturamento_fifo.faturamento_item_entrada = $faturamento_item 
									and tbl_faturamento.cfop in ('5202')
							) X";
				$resx = pg_query($con,$sql);
				if(pg_num_rows($resx) > 0) {
					$qtde_saida_devolucao = pg_fetch_result($resx,0,0);
				}else{
					$qtde_saida_devolucao = 0 ;
				}
		if($peca <> $peca_ant) {
			echo "<tr bgcolor='cyan'>
					<td colspan='4'> Soma:</td>
					<td >".$total_qtde."</td>
					<td >".$total_qtde_estoque."</td>
					<td ></td>
					<td >".number_format($total_valor,2,",",".")."</td>
					<td ></td>
					<td >".number_format($total_ipi,2,",",".")."</td>
					<td >".number_format($total_valor_ipi,2,",",".")."</td>
					<td ></td>
					<td >$total_garantia</td>
					<td >".number_format($total_saida_garantia,2,",",".")."</td>
					<td >$total_faturada</td>
					<td >".number_format($total_saida_faturada,2,",",".")."</td>
					<td >$total_devolucao</td>
					</tr>";
			$total_qtde = 0;
			$total_qtde_estoque = 0;
			$total_valor = 0 ;
			$total_ipi = 0 ;
			$total_valor_ipi = 0 ;
			$total_garantia = 0 ; 
			$total_saida_garantia = 0 ; 
			$total_saida_faturada = 0 ; 
			$total_faturada = 0 ; 
			$total_devolucao = 0 ; 
		}

		$cor = "cccccc";
		if ($i % 2 == 0) $cor = '#eeeeee';
		echo "<tr bgcolor='$cor' style='font-size:11px' id='$faturamento'>";

		echo "<td>";
		echo "$referencia";
		echo "</td>";

		echo "<td>";
		echo "$descricao";
		echo "</td>";

		echo "<td>";
		echo "$nota_fiscal";
		echo "</td>";

		echo "<td>";
		echo "$emissao";
		echo "</td>";


		echo "<td>";
		echo $qtde;
		echo "</td>";

		echo "<td>";
		echo $qtde_estoque;
		echo "</td>";

		echo "<td>";
		echo number_format($preco,2,',','.') ;
		echo "</td>";

		echo "<td>";
		echo number_format($preco*$qtde,2,',','.') ;
		echo "</td>";

		echo "<td>";
		echo number_format($ipi/$qtde,2,',','.');
		echo "</td>";
		
		echo "<td>";
		echo number_format($ipi,2,',','.');
		echo "</td>";

		echo "<td>";
		echo number_format($preco*$qtde + $ipi,2,',','.');
		echo "</td>";

		echo "<td>";
		echo number_format(($preco*$qtde + $ipi)/$qtde,2,',','.');
		echo "</td>";



		echo "<td>";
		echo $qtde_saida;
		echo "</td>";

		echo "<td>";
		echo number_format(($preco*$qtde + $ipi)/$qtde*$qtde_saida,2,',','.');
		echo "</td>";


		echo "<td>";
		echo $qtde_saida_faturada;
		echo "</td>";

		echo "<td>";
		echo number_format(($preco*$qtde + $ipi)/$qtde*$qtde_saida_faturada,2,',','.');
		echo "</td>";


		echo "<td>";
		echo $qtde_saida_devolucao;
		echo "</td>";

		echo "</tr>";

		$total_qtde += $qtde;
		$total_qtde_estoque += $qtde_estoque;
		$total_valor += $preco*$qtde;
		$total_valor_ipi += $preco* $qtde+$ipi;
		$total_ipi += $ipi;
		$nota_fiscal_ant = $nota_fiscal;

		$total_saida_garantia += 	($preco*$qtde + $ipi)/$qtde*$qtde_saida;
		$total_saida_faturada += 	($preco*$qtde + $ipi)/$qtde*$qtde_saida_faturada;
		$totais_saida_garantia += 	($preco*$qtde + $ipi)/$qtde*$qtde_saida;
		$totais_saida_faturada += 	($preco*$qtde + $ipi)/$qtde*$qtde_saida_faturada;

		$totais_valor += $preco*$qtde;
		$totais_valor_ipi += $preco* $qtde+$ipi;
		$totais_ipi += $ipi;
			
		$total_garantia += $qtde_saida;
		$total_faturada += $qtde_saida_faturada;
		$total_devolucao += $qtde_saida_devolucao;
		$peca_ant = $peca; 
	}
			echo "<tr bgcolor='cyan'>
					<td colspan='4'> Soma:</td>
					<td >".$total_qtde."</td>
					<td >".$total_qtde_estoque."</td>
					<td ></td>
					<td >".number_format($total_valor,2,",",".")."</td>
					<td ></td>
					<td >".number_format($total_ipi,2,",",".")."</td>
					<td >".number_format($total_valor_ipi,2,",",".")."</td>
					<td ></td>
					<td >$total_garantia</td>
					<td >".number_format($total_saida_garantia,2,",",".")."</td>
					<td >$total_faturada</td>
					<td >".number_format($total_saida_faturada,2,",",".")."</td>
					<td >$total_devolucao</td>
				</tr>";

	echo "<tr bgcolor='pink'>
					<td colspan='4'> Soma Total:</td>
					<td ></td>
					<td ></td>
					<td ></td>
					<td >".number_format($totais_valor,2,",",".")."</td>
					<td ></td>
					<td >".number_format($totais_ipi,2,",",".")."</td>
					<td >".number_format($totais_valor_ipi,2,",",".")."</td>
					<td colspan='2'></td>
					<td >".number_format($totais_saida_garantia,2,",",".")."</td>
					<td ></td>
					<td >".number_format($totais_saida_faturada,2,",",".")."</td>
					<td ></td>
					</tr>";
	echo "</table>";

	}else{
		if(!empty($data_inicial) and !empty($data_final)){
			$cond = " AND tbl_faturamento.emissao between '$data_inicial' and '$data_final' ";
		}

		$sql = "SELECT  DISTINCT tbl_faturamento.nota_fiscal,
				tbl_peca.referencia,
				tbl_peca.descricao,
				tbl_faturamento.total_nota,
				to_char(emissao,'DD/MM/YYYY') as emissao,
				tbl_faturamento_item.preco,
				tbl_faturamento_item.qtde,
				tbl_faturamento_item.qtde_estoque,
				tbl_faturamento_item.faturamento_item,
				tbl_faturamento_item.valor_ipi as ipi,
				tbl_faturamento_item.aliq_ipi,
				tbl_faturamento_item.peca,
				tbl_faturamento.faturamento
				FROM tbl_faturamento
				JOIN tbl_faturamento_item using (faturamento)
				JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca
				JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_peca.fabrica
				WHERE  tbl_faturamento.posto in ( 4311,376542)
				AND (
					tbl_faturamento.distribuidor IN (
						/* Seleciona apenas os distribuidores da condição nova, descartando quando o posto entrava como
						distribuidor (LRG Britania)*/
						SELECT DISTINCT distribuidor FROM tbl_faturamento WHERE fabrica = 10 AND posto = 4311
						and distribuidor is not null and distribuidor <> 4311)
					OR
					tbl_faturamento.fabrica in (10)
					AND tbl_faturamento.distribuidor is null
				)
				AND tbl_faturamento.cancelada IS NULL
				AND tbl_faturamento.fabrica <> 0
				AND (tbl_faturamento.tipo_nf = 0 or tbl_faturamento.tipo_nf IS NULL)
				AND tbl_fabrica.fabrica = $fabrica
				and tbl_faturamento.emissao > current_date - interval '5 years'
				$cond
				ORDER BY tbl_faturamento.nota_fiscal";
	$res = pg_exec ($con,$sql);

	echo "<table align='center' border='0' cellspacing='1' cellpadding='1'>";
	echo "<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
	echo "<td>Peça</td>";
	echo "<td>Descrição</td>";
	echo "<td>Nota Fiscal</td>";
	echo "<td>Emissão</td>";
	echo "<td>Qtde NF</td>";
	echo "<td>Qtde Rec.</td>";
	echo "<td>Unitário</td>";
	echo "<td>Valor Mercadoria</td>";
	echo "<td>Unitário IPI</td>";
	echo "<td>Total IPI</td>";
	echo "<td>Total</td>";
	echo "<td>Unitário c/IPI</td>";
	echo "<td>Saída Garantia</td>";
	echo "<td>Saída Faturada</td>";
	echo "<td>Saída Devolução</td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$nota_fiscal         = pg_fetch_result($res,$i,'nota_fiscal');
		$emissao         = pg_fetch_result($res,$i,'emissao');
		$total_nota         = pg_fetch_result($res,$i,'total_nota');
		$referencia = pg_fetch_result($res,$i,'referencia');
		$descricao= pg_fetch_result($res,$i,'descricao');
		$faturamento_item= pg_fetch_result($res,$i,'faturamento_item');
		$faturamento= pg_fetch_result($res,$i,'faturamento');
		$preco= pg_fetch_result($res,$i,'preco');
		$qtde= pg_fetch_result($res,$i,'qtde');
		$qtde_estoque= pg_fetch_result($res,$i,'qtde_estoque');
		$ipi= pg_fetch_result($res,$i,'ipi');
		$aliq_ipi= pg_fetch_result($res,$i,'aliq_ipi');
		$peca = pg_fetch_result($res,$i,'peca');

		$qtde_saida = 0 ;
		$qtde_saida_faturada = 0 ;
				$sql = "
							SELECT sum(qtde)
							FROM (
									SELECT	sum(tbl_faturamento_fifo.qtde) as qtde
									FROM tbl_faturamento
									JOIN tbl_faturamento_item USING (faturamento)
									JOIN tbl_faturamento_fifo ON tbl_faturamento_item.faturamento_item = tbl_faturamento_fifo.faturamento_item_devolucao
									JOIN tbl_peca USING(peca)
									WHERE tbl_faturamento_item.peca = $peca
									AND   tbl_faturamento.fabrica = 10
									and tbl_faturamento_fifo.faturamento_item_entrada = $faturamento_item 
									and tbl_faturamento.cfop in ('5949','6949')
							) X";
				$resx = pg_query($con,$sql);
				if(pg_num_rows($resx) > 0) {
					$qtde_saida = pg_fetch_result($resx,0,0);
				}else{
					$qtde_saida = 0 ;
				}
				$sql = "
							SELECT sum(qtde)
							FROM (
									SELECT	sum(tbl_faturamento_fifo.qtde) as qtde
									FROM tbl_faturamento
									JOIN tbl_faturamento_item USING (faturamento)
									JOIN tbl_faturamento_fifo ON tbl_faturamento_item.faturamento_item = tbl_faturamento_fifo.faturamento_item_devolucao
									JOIN tbl_peca USING(peca)
									WHERE tbl_faturamento_item.peca = $peca
									AND   tbl_faturamento.fabrica = 10
									and tbl_faturamento_fifo.faturamento_item_entrada = $faturamento_item 
									and tbl_faturamento.cfop not in ('5949','6949','5202')
							) X";
				$resx = pg_query($con,$sql);
				if(pg_num_rows($resx) > 0) {
					$qtde_saida_faturada = pg_fetch_result($resx,0,0);
				}else{
					$qtde_saida_faturada = 0 ;
				}

				$sql = "
							SELECT sum(qtde)
							FROM (
									SELECT	sum(tbl_faturamento_fifo.qtde) as qtde
									FROM tbl_faturamento
									JOIN tbl_faturamento_item USING (faturamento)
									JOIN tbl_faturamento_fifo ON tbl_faturamento_item.faturamento_item = tbl_faturamento_fifo.faturamento_item_devolucao
									JOIN tbl_peca USING(peca)
									WHERE tbl_faturamento_item.peca = $peca
									AND   tbl_faturamento.fabrica = 10
									and tbl_faturamento_fifo.faturamento_item_entrada = $faturamento_item 
									and tbl_faturamento.cfop in ('5202')
							) X";
				$resx = pg_query($con,$sql);
				if(pg_num_rows($resx) > 0) {
					$qtde_saida_devolucao = pg_fetch_result($resx,0,0);
				}else{
					$qtde_saida_devolucao = 0 ;
				}

		$cor = "cccccc";
		if ($i % 2 == 0) $cor = '#eeeeee';
		if($nota_fiscal <> $nota_fiscal_ant or empty($nota_fiscal_ant)) {	
				$sql = "
						SELECT sum(qtde*preco+tbl_faturamento_item.valor_ipi),
								sum(qtde*preco),
								sum(tbl_faturamento_item.valor_ipi)
									FROM tbl_faturamento
									JOIN tbl_faturamento_item USING (faturamento)
									JOIN tbl_peca USING(peca)
									WHERE tbl_faturamento.faturamento=$faturamento
									AND   tbl_faturamento.fabrica = 10";
				$resx = pg_query($con,$sql);
				$total = pg_fetch_result($resx,0,0);
				$total_s_ipi = pg_fetch_result($resx,0,1);
				$total_ipi = pg_fetch_result($resx,0,2);
				echo "<tr bgcolor='cyan'>
						<td colspan='3'>$nota_fiscal</td>
						<td colspan='4' nowrap>Total Nota: ".number_format($total_nota,2,",",".")." </td>
						<td  nowrap>".number_format($total_s_ipi,2,",",".")." </td>
						<td ></td>
						<td >".number_format($total_ipi,2,",",".")."</td>
						<td  nowrap>".number_format($total,2,",",".")." </td>
						<td colspan='4'></td>
					</tr>";
		}

		echo "<tr bgcolor='$cor' style='font-size:11px' id='$faturamento'>";

		echo "<td>";
		echo "$referencia";
		echo "</td>";

		echo "<td>";
		echo "$descricao";
		echo "</td>";

		echo "<td>";
		echo "$nota_fiscal";
		echo "</td>";

		echo "<td>";
		echo "$emissao";
		echo "</td>";


		echo "<td>";
		echo $qtde;
		echo "</td>";

		echo "<td>";
		echo $qtde_estoque;
		echo "</td>";

		echo "<td>";
		echo number_format($preco,2,',','.') ;
		echo "</td>";

		echo "<td>";
		echo number_format($preco*$qtde,2,',','.') ;
		echo "</td>";

		echo "<td>";
		echo number_format($ipi/$qtde,2,',','.');
		echo "</td>";
		
		echo "<td>";
		echo number_format($ipi,2,',','.');
		echo "</td>";

		echo "<td>";
		echo number_format($preco*$qtde + $ipi,2,',','.');
		echo "</td>";

		echo "<td>";
		echo number_format(($preco*$qtde + $ipi)/$qtde,2,',','.');
		echo "</td>";



		echo "<td>";
		echo $qtde_saida;
		echo "</td>";

		echo "<td>";
		echo $qtde_saida_faturada;
		echo "</td>";

		echo "<td>";
		echo $qtde_saida_devolucao;
		echo "</td>";

		echo "</tr>";

		$nota_fiscal_ant = $nota_fiscal;
		$totais_valor += $preco*$qtde;
		$totais_valor_ipi += $preco* $qtde+$ipi;
		$totais_ipi += $ipi;

	}

	echo "<tr bgcolor='pink'>
					<td colspan='4'> Soma:</td>
					<td ></td>
					<td ></td>
					<td ></td>
					<td >".number_format($totais_valor,2,",",".")."</td>
					<td ></td>
					<td >".number_format($totais_ipi,2,",",".")."</td>
					<td >".number_format($totais_valor_ipi,2,",",".")."</td>
					<td colspan='4'></td>
					</tr>";
	echo "</table>";


	echo "</table>";

	
		
		
	}
}
 include "rodape.php"; ?>

</body>
</html>

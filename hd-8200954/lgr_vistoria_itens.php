<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

if( isset($_GET['ajax']) AND isset($_GET['faturamento'] ) ) {

	$fat = (int) $_GET['faturamento'];
	$peca = (int) $_GET['peca'];
	$preco =  $_GET['preco'];
	$linha = $_GET['resp'];

	if( $_GET['ajax'] != 'sim' || empty($_GET['faturamento']) || empty($_GET['peca']))
		return traduz('Erro com os parâmetros. Verifique os dados na url.');

	$resp = '';


	if($preco > 0) {
		$cond = " and tbl_faturamento_item.preco = '$preco' ";
	}
	if(in_array($login_fabrica,array(104,105))){
		$sql = "SELECT CASE WHEN tbl_os.consumidor_revenda = 'R' THEN tbl_os.sua_os END AS sua_os,
				tbl_os.os, 
				tbl_produto.referencia,
				tbl_produto.descricao 
				FROM tbl_faturamento 
				JOIN tbl_faturamento_item using(faturamento)
				JOIN tbl_os_item ON tbl_os_item.pedido = tbl_faturamento_item.pedido
				JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
				JOIN tbl_os ON tbl_os.os = tbl_os_produto.os
				JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto 
				WHERE faturamento = $fat 
				AND tbl_os.fabrica = $login_fabrica
				AND tbl_os_item.peca = $peca 
				GROUP by tbl_produto.referencia, 
				tbl_os.os, 
				data_abertura, 
				data_fechamento,
				tbl_produto.descricao, 
				tbl_os.consumidor_revenda, 
				tbl_os.sua_os;";
	} else {
		$sql = "SELECT 
				CASE WHEN tbl_os.consumidor_revenda = 'R' THEN tbl_os.sua_os
				END
				AS sua_os, tbl_os.os,
				tbl_produto.referencia,tbl_produto.descricao
				FROM 
				tbl_faturamento 
				JOIN tbl_faturamento_item using(faturamento) 
				JOIN tbl_os ON tbl_faturamento_item.os = tbl_os.os
				JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
				JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
				JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto 
				WHERE faturamento = $fat AND tbl_faturamento_item.os is not null 
				AND tbl_os.fabrica = $login_fabrica
				AND tbl_os_item.peca = $peca
				$cond
				GROUP by tbl_produto.referencia, tbl_os.os, data_abertura, data_fechamento,tbl_produto.descricao, tbl_os.consumidor_revenda, tbl_os.sua_os;";
	}
	//echo $sql; die;
	$query = pg_query($con,$sql);
	$xColspan = 3;
	if ($login_fabrica) {
		$xColspan = 6;
	}
	
	if(pg_num_rows($query) == 0) {
		$resp = '<tr><td>'.traduz('Nenhuma OS Encontrada').'</td></tr>';
	}
	else {
		$resp = '

				<tr class="resp'.$linha.' subtitulo">
					<td>OS</td>
					<td>Referência</td>
					<td colspan="'.$xColspan.'">Descrição</td>
				</tr>
			';

		for($i=0;$i<pg_num_rows($query); $i++) {
		
			extract(pg_fetch_array($query));
			$resp .= '
				<tr class="resp'.$linha.'">
					<td><a href="os_press.php?os='.$os.'" target="_blank">'.( !empty($sua_os) ? $sua_os : $os ).'</a>&nbsp;</td>
					<td>'.$referencia.'&nbsp;</td>
					<td colspan="'.$xColspan.'">'.$descricao.'&nbsp;</td>
				</tr>';

		}
		$resp .= '<tr class="resp'.$linha.'"><td style="border:none;">&nbsp;</td></tr>';
		echo $resp;
	}

	return;

}
$msg_erro="";
$msg="";

#Para estes postos devem ser mostrados somente os produto - HD 13651
/*RETIRADO CONFORME SOLICITADO PELO TULIO (VISITA A BRITANIA) 01/06/2009 -  CONVERSA NO CHAT COM IGOR*/
//$postos_permitidos_novo_processo = array(0 => 'LIXO',1 => '6976', 2 => '20397', 3 => '4044', 4 => '1267', 5 => '6458', 6 => '710', 7 => '5037', 8 => '1752', 9 => '4311', 10 => '1537',11 => '6359');
$postos_permitidos_novo_processo = array(0 => 'LIXO');

$layout_menu = "os";
$title = traduz("Peças Retornáveis do Extrato");

include "cabecalho.php";
?>

<style type="text/css">
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
table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
	border:1px solid #596d9b;
}
.subtitulo{
    background-color: #7092BE;
    font:bold 11px Arial;
    color: #FFFFFF;
}
</style>

<center>

<br>
<TABLE width="700px" align="center" border="0" cellspacing="1" cellpadding="0" name="dados">

<TR>
	<TD colspan='8' class="texto_avulso">
		<b><?= traduz('ATENÇÃO') ?></b><br />
		<?= traduz('As peças listadas abaixo devem ser armazenadas no seu posto autorizado para vistoria. Podem ser descartadas após') ?> 
		<?php 
		if (in_array($login_fabrica, array(164))) {
			$dias = "120";
		} elseif (in_array($login_fabrica, array(176))) {
			$dias = "365";
		} else {
			$dias = "90";
		}
		echo $dias;
		?> <?= traduz('dias da data do extrato.') ?>
	</TD>
</TR>

</table>
<br />
<div class="texto_avulso" style="width:700px; margin:auto;"><?= traduz('Clique em um registro para consultar as ordens de serviço da peça escolhida') ?></div>

<? 

		if ($login_fabrica == 176) {
			$colspan_1 = 5;
			$colspan_2 = 8;
		}
		if ($resumo == "sim"){
			$colspan_1 = 5;
			$colspan_2 = 7;
		}else{
			$colspan_1 = 2;
			$colspan_2 = 4;
			if ($login_fabrica == 51){
				$colspan_1 = 1;
				$colspan_2 = 3;
			}
		}

		$sql = "SELECT	tbl_posto_fabrica.codigo_posto, 
						tbl_posto.nome
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
				WHERE fabrica = $login_fabrica
				AND posto     = $login_posto";
		$res = pg_query ($con,$sql);

		if (pg_num_rows($res)>0){
			$posto_codigo = pg_fetch_result($res,0,codigo_posto);
			$posto_nome   = pg_fetch_result($res,0,nome);
		}

		$topo .= "<table border='0' cellspacing='1' cellpadding='0'  class='tabela' width='700'>\n";

		# Pega os três ultimos extratos
		$sql = "SELECT extrato,
					TO_CHAR(data_geracao,'DD/MM/YYYY') AS data_geracao
				FROM tbl_extrato
				WHERE fabrica = $login_fabrica
				AND   posto   = $login_posto
				ORDER BY tbl_extrato.data_geracao DESC
				LIMIT 3";
		if ($login_fabrica == 51){
			$sql = "SELECT	tbl_extrato.extrato,
							TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao
					FROM tbl_extrato
					WHERE tbl_extrato.fabrica = $login_fabrica
					AND   tbl_extrato.posto   = $login_posto
					
					ORDER BY tbl_extrato.data_geracao DESC
					LIMIT 3";
		}

		$res = pg_query ($con,$sql);
		$numero_de_extrato = pg_num_rows($res);
		$y = 0;
		echo $topo;
		$cont = 0;
		$meses = array('01' => 'Janeiro','02' => 'Fevereiro','03' => 'Março','04' => 'Abril','05' => 'Maio',	'06' => 'Junho','07' => 'Julho','08' => 'Agosto','09' => 'Setembro','10' => 'Outubro',	'11' => 'Novembro','12' => 'Dezembro'
			);
		for ($i=0; $i<$numero_de_extrato; $i++){
		
			$extrato_numero = pg_fetch_result($res,$i,extrato);
			$extrato_data   = pg_fetch_result($res,$i,data_geracao);
			$data = DateTime::createFromFormat("d/m/Y",$extrato_data);
			$data_corte = DateTime::createFromFormat("d/m/Y","01/09/2018");

			$faturamento_emissao_cond = '';

			if ($login_fabrica == 3 and $data >= $data_corte) {
				$faturamento_emissao_cond = " AND tbl_faturamento.emissao >= '2018-01-01' ";
			}

			$sqlm = "SELECT to_char(CURRENT_DATE - INTERVAL '$i MONTHS','YYYY-MM')";
			$resm = pg_query($con,$sqlm);
			$extrato_mes = pg_fetch_result($resm,0,0);
			$mes_extrato = explode('-',$extrato_mes);
			
			if(in_array($login_fabrica,array(51,94,98,104,105,106,149)))

				foreach($meses as $num => $mes)
					if($num == $mes_extrato[1]) {
						$extrato_mes_print = $mes . ' - ' . $mes_extrato[0];
						break;
					}
			echo '<tr><td style="border:none;" colspan="100%">&nbsp;</td></tr>';
			echo "<tr align='center'>\n";
			echo "<td colspan='100%' class='titulo_tabela'>";
			echo "<b>".traduz('Peças para Vistoria')." - ";

			echo (in_array($login_fabrica,array(51,94,98,104,105,106))) ?  $extrato_mes_print : ($i+1);
			echo "</b>";
			echo "</td>\n";
			if(in_array($login_fabrica,array(51,94,98,104,105,106))){
				$estilo = "style='display:none;'";
			}
			echo "<tr $estilo><td style='font-size:14px' colspan='100%'>".traduz('Extrato')." Nº $extrato_numero - $extrato_data </td></tr>";
			echo "</tr>\n";

			echo "<tr align='center' class='titulo_coluna'>\n";
			if(in_array($login_fabrica,array(51,94,98,104,105,106,151,157))) {

				echo "<td><b>".traduz('Nota Fiscal')."</b></td>\n";
			}
			echo "<td><b>".traduz('Código')."</b></td>\n";
			echo "<td><b>".traduz('Descrição')."</b></td>\n";

			if ($resumo == "sim"  || $inspecaoPeca == 't'){
				echo "<td><b>".traduz("Preço").".</b></td>\n";
				echo "<td><b>".traduz("Qtde").".</b></td>\n";
				echo "<td><b>ICMS</b></td>\n";
				echo "<td><b>IPI</b></td>\n";
				echo "<td><b>".traduz("Total")."</b></td>\n";
			}else{
				echo "<td><b>".traduz("Qtde").".</b></td>\n";
				echo "<td><b>".traduz("Qtde").".<br />".traduz("Vistoriada")."</b></td>\n";
			}
			echo "</tr>\n";
			if($login_fabrica == 51) {
				$campos = " ,nota_fiscal ";
				$group = " ,nota_fiscal ";
				$cond = " 
				AND tbl_faturamento.fabrica             = 10
				AND tbl_peca.fabrica = $login_fabrica
				AND to_char(tbl_faturamento.emissao,'YYYY-MM') = '$extrato_mes' ";
				$order = 'nota_fiscal';
			}else{
				$order = 'tbl_peca.descricao';
				$cond = " AND tbl_faturamento.fabrica = $login_fabrica";

				if(!in_array($login_fabrica,array(94,98,104,105,106,157,164,176))){
					$cond .=" AND tbl_faturamento.extrato_devolucao = $extrato_numero ";
				}
			}

			if(in_array($login_fabrica,array(94,104,105,106,157))){
				$campos = " ,nota_fiscal ";
				$group = " ,nota_fiscal ";
			}

			if($login_fabrica == 98){
				$campos = " ,nota_fiscal ";
				$group = " ,nota_fiscal ";
			}

			$sql = "SELECT  
						tbl_peca.peca, 
						tbl_peca.referencia, 
						tbl_peca.descricao, 
						tbl_peca.ipi, 
						tbl_faturamento.faturamento,
						CASE WHEN tbl_peca.produto_acabado IS TRUE THEN 'TRUE' ELSE 'NOT TRUE' END AS produto_acabado,
						tbl_peca.devolucao_obrigatoria,
						tbl_faturamento_item.aliq_icms,
						tbl_faturamento_item.aliq_ipi,
						tbl_faturamento_item.preco,
						SUM (tbl_faturamento_item.qtde) as qtde,
						sum(tbl_faturamento_item.qtde_inspecionada) AS qtde_inspecionada,
						SUM (tbl_faturamento_item.base_icms) AS base_icms, 
						SUM (tbl_faturamento_item.valor_icms) AS valor_icms,
						SUM (tbl_faturamento_item.base_ipi) AS base_ipi,
						SUM (tbl_faturamento_item.valor_ipi) AS valor_ipi
						$campos
					FROM tbl_faturamento
					JOIN tbl_faturamento_item USING (faturamento)
					JOIN tbl_peca             USING (peca)
					WHERE tbl_faturamento.posto             = $login_posto
						$cond $faturamento_emissao_cond";
						/*AND tbl_faturamento.extrato_devolucao = $extrato_numero*/
			if ($login_fabrica != 163) {
				$sql .= "AND (tbl_faturamento.cfop IN ('694921','694922','594919','594920','594921','594922','594923','5949','6949','6959') or tbl_faturamento.cfop ~'5949|6949') 
						"; 
			}
						
			if ($resumo=="sim"){
				$sql .=" AND tbl_faturamento_item.qtde - 
							CASE WHEN tbl_faturamento_item.qtde_inspecionada IS NULL
								THEN 0
								ELSE tbl_faturamento_item.qtde_inspecionada
							END
							> 0";
			}

			if ($login_fabrica == 3 AND $extrato_numero > 240000 AND array_search($login_posto, $postos_permitidos_novo_processo)>0) {
				$sql .=" AND tbl_peca.produto_acabado       IS NOT TRUE ";
			}else{
				if($login_fabrica != 51)
					$sql .=" AND tbl_peca.devolucao_obrigatoria IS NOT TRUE ";
				else
					$sql .=" AND tbl_peca.devolucao_obrigatoria ";
				$sql .= "AND tbl_peca.produto_acabado IS NOT TRUE ";
			}
			
			if(in_array($login_fabrica,array(94,98,104,105,106,149,151,157,163,176))){

				$sql .=" AND tbl_peca.aguarda_inspecao IS TRUE AND to_char(tbl_faturamento.emissao,'YYYY-MM') = '$extrato_mes' ";
			}

			$sql .="
					GROUP BY
						tbl_peca.peca, 
						tbl_peca.referencia, 
						tbl_peca.descricao,
						tbl_peca.devolucao_obrigatoria, 
						tbl_peca.produto_acabado, 
						tbl_peca.ipi,
						tbl_faturamento.faturamento,
						tbl_faturamento_item.aliq_icms,
						tbl_faturamento_item.preco,
						tbl_faturamento_item.aliq_ipi
						$group
					ORDER BY $order";
//echo nl2br($sql); exit;
			$resX = pg_query ($con,$sql);

			$notas_fiscais = array();
			$qtde_peca  = 0;
			$base_icms  = 0;
			$base_ipi   = 0;
			$valor_icms = 0;
			$valor_ipi  = 0;
			$total_nota = 0;

			for ($x = 0 ; $x < pg_num_rows ($resX) ; $x++) {
				
				$cont++;

				$peca                = pg_fetch_result ($resX,$x,peca);
				$peca_referencia     = pg_fetch_result ($resX,$x,referencia);
				$peca_descricao      = pg_fetch_result ($resX,$x,descricao);
				$peca_produto_acabado= pg_fetch_result ($resX,$x,produto_acabado);
				$peca_devolucao_obrigatoria = pg_fetch_result ($resX,$x,devolucao_obrigatoria);

				$aliq_icms           = pg_fetch_result ($resX,$x,aliq_icms);
				$aliq_ipi            = pg_fetch_result ($resX,$x,aliq_ipi);
				
				$qtde                = pg_fetch_result ($resX,$x,qtde);
				$qtde_inspecionada   = pg_fetch_result ($resX,$x,qtde_inspecionada);
				$faturamento		 = pg_fetch_result ($resX,$x,faturamento);
				$preco           = pg_fetch_result ($resX,$x,preco);
				$preco =  $preco ?? 0 ;
				if ($inspecaoPeca == 't') {
					$total           = $qtde * $preco;
				}
				$qtde_peca_pendente = $qtde - $qtde_inspecionada;
				if (strlen($qtde_peca_pendente)==0){
					$qtde_peca_pendente = 0;
				}

				if ($resumo=="sim"){

					$qtde = $qtde_peca_pendente;
					$total = $preco * $qtde;

					$total_nota += $total;

					if (strlen($aliq_icms)==0){
						$aliq_icms = 0;
					}
					if (strlen($aliq_ipi)==0){
						$aliq_ipi = 0;
					}

					if ($aliq_icms>0){
						$base_icms += $total;
						$valor_icms += $preco * $qtde * $aliq_icms/100;
					}

					if ($aliq_ipi>0){
						$base_ipi  += $total;
						$valor_ipi += $preco * $qtde * $aliq_ipi/100;
					}

					
				}
				

				$sql_nf = "SELECT tbl_faturamento_item.nota_fiscal_origem
						FROM tbl_faturamento_item 
						JOIN tbl_faturamento      USING (faturamento)
						WHERE tbl_faturamento.fabrica           = $login_fabrica
						AND   tbl_faturamento.distribuidor      = $login_posto
						AND   tbl_faturamento.posto             = $login_posto
						AND   tbl_faturamento.extrato_devolucao = $extrato
						AND   tbl_faturamento.faturamento       = $faturamento_nota
						ORDER BY tbl_faturamento.nota_fiscal";
				#$resNF = pg_query ($con,$sql_nf);
				#for ($y = 0 ; $y < pg_num_rows ($resNF) ; $y++) {
				#	array_push($notas_fiscais,pg_fetch_result ($resNF,$y,nota_fiscal_origem));
				#}
				#$notas_fiscais = array_unique($notas_fiscais);
				#asort($notas_fiscais);

				$cor = ($cont % 2) ? "#F7F5F0" : "#F1F4FA";

				echo "<tr bgcolor='$cor' style='font-color:#000000 ; align:left ; font-size:10px ; cursor:pointer; ' id='dados$cont' onclick='exibe_os(".$faturamento.",".$peca.",".$cont.",".$preco.");'>\n";

				if(in_array($login_fabrica,array(51,94,98,104,105,106,157))){

					echo "<td align='center'>" . pg_fetch_result($resX,$x,'nota_fiscal') . "</td>\n";
				}
				echo "<td align='left'>";
				echo "$peca_referencia";
				echo "</td>\n";
				echo "<td align='left'>$peca_descricao</td>\n";

				$desabilitar = "";
				if ( $qtde - $qtde_inspecionada == 0){
					$desabilitar = " DISABLED style='background-color:#DFDFDF'";
				}
				if ( $qtde_inspecionada == 0 AND strlen($qtde_inspecionada)>0){
					$desabilitar = " DISABLED style='background-color:#FECBCB'";
				}
				
				$qtde_cor = $qtde;
				if ($qtde_cor == 0){
					$qtde_cor = "<span style='color:red'>$qtde</span>";
				}

				
				if ($qtde_inspecionada == $qtde){
					$cor_celula = "#D8FEDA";
				}elseif (($qtde_inspecionada=="0" OR $qtde_inspecionada < $qtde) AND strlen($qtde_inspecionada)>0){
					$cor_celula = "#FDE7DF";
				}else{
					$cor_celula = "#FAE7A5";
				}

				if ($resumo == "sim" || $inspecaoPeca == 't'){
					echo "<td align='right'>".number_format($preco,2,",",".")."</td>\n";
					echo "<td align='center'>$qtde</td>\n";
					echo "<td align='center'>$aliq_icms</td>\n";
					echo "<td align='center'>$aliq_ipi</td>\n";
					echo "<td align='right'>".number_format($total,2,",",".")."</td>\n";
				}else{
					echo "<td align='center'>$qtde_cor</td>\n";
					echo "<td align='center' bgcolor='$cor_celula'>$qtde_inspecionada &nbsp;</td>\n";
				}

				echo "</tr>\n";

				$y++;

				flush();
			}
			if ( $resumo == "sim" AND $x > 0){
				$total_nota = $total_nota + $valor_ipi;
				echo "<tr>\n";
				echo "<td colspan='$colspan_2'>";
				echo "<table  border='0' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='100%' >";
					echo "<tr>";
					echo "<td align='left'>Base ICMS<br><b>".number_format($base_icms,2,",",".")."</b></td>";
					echo "<td align='left'>Valor ICMS<br><b>".number_format($valor_icms,2,",",".")."</b></td>";
					echo "<td align='left'>Base IPI<br><b>".number_format($base_ipi,2,",",".")."</b></td>";
					echo "<td align='left'>Valor IPI<br><b>".number_format($valor_ipi,2,",",".")."</b></td>";
					echo "<td align='left'>Total Nota<br><b>".number_format($total_nota,2,",",".")."</b></td>";
					echo "</tr>";
				echo "</table>";
				echo "<td>";
				echo "</tr>\n";
			}
		}

		if ($numero_de_extrato == 0){
			echo "<tr align='center'>\n";
			echo "<td colspan='$colspan_2' align='left' bgcolor='#E3E4E6' style='font-size:12px'>";
			echo "<b>".traduz('Não há peças para vistoria.')."</b>";
			echo "</td>\n";
			echo "</tr>\n";
		}

		echo "</table>\n";
		echo "<br><br>";
?>
</form>

<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript">

	cont = new Array();

	function exibe_os(fat,peca,linha, preco) {

		if (cont[linha] === false) {
			$(".resp"+linha).hide();
			cont[linha] = true;
		}
		else {

			cont[linha] = false;
			url = '<?php echo $_SERVER[PHP_SELF]; ?>?ajax=sim&faturamento='+fat+'&peca='+peca+'&resp='+linha+'&preco='+preco;
			loading = '<tr border:none;" class="loading"><td colspan="6">Carregando..</td></tr>';
			$("#dados" + linha).after(loading);
			$.get(url, function(data) {
				$(".loading").remove();
				$("#dados" + linha).after(data);
			});
		}
	}

</script>

<p><p>

<? include "rodape.php"; ?>

<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";

if( isset($_GET['ajax']) AND isset($_GET['faturamento'] ) ) {
	
	$fat = (int) $_GET['faturamento'];
	$peca = (int) $_GET['peca'];
	$linha = $_GET['resp'];

	if( $_GET['ajax'] != 'sim' || empty($_GET['faturamento']) || empty($_GET['peca'])) {
		echo 'Erro com os parâmetros. Verifique os dados na url.';
		return;
	}

	$resp = '';
	
	if(in_array($login_fabrica,array(104,105,106,142))){
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
				AS sua_os,tbl_os.os, 
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
				GROUP by tbl_produto.referencia, tbl_os.os, data_abertura, data_fechamento,tbl_produto.descricao, tbl_os.consumidor_revenda, tbl_os.sua_os;";
	}
	//echo $sql;
	$query = pg_query($con,$sql);

	if(pg_num_rows($query) == 0) {
		$resp = '<tr><td>Nenhuma OS Encontrada</td></tr>';
		return;
	}
	else {
		$resp = '
				<tr class="resp'.$linha.' subtitulo">
					<td>OS</td>
					<td>Referência</td>
					<td colspan="4">Descrição</td>
				</tr>
			';

		for($i=0;$i<pg_num_rows($query); $i++) {

			extract(pg_fetch_array($query));
			$resp .= '
				<tr class="resp'.$linha.'">
					<td>
						<a href="os_press.php?os='.$os.'" target="_blank">'.(!empty($sua_os) ? $sua_os : $os) . '</a>
					</td>
					<td>'.$referencia.'</td>
					<td colspan="4">'.$descricao.'</td>
				</tr>';

		}

		$resp .= '<tr class="resp'.$linha.'"><td style="border:none;">&nbsp;</td></tr>';

		echo $resp;
	}
	
	return;

}

unset($msg_erro);
$msg="";

#Para estes postos devem ser mostrados somente os produto - HD 13651
/*RETIRADO CONFORME SOLICITADO PELO TULIO (VISITA A BRITANIA) 01/06/2009 -  CONVERSA NO CHAT COM IGOR*/
//$postos_permitidos_novo_processo = array(0 => 'LIXO',1 => '6976', 2 => '20397', 3 => '4044', 4 => '1267', 5 => '6458', 6 => '710', 7 => '5037', 8 => '1752', 9 => '4311', 10 => '1537',11 => '6359');
$postos_permitidos_novo_processo = array(0 => 'LIXO');

#$layout_menu = "auditoria";
$title = "Peças Retornáveis do Extrato";

include "cabecalho_new.php";

$plugins = array(
	"datepicker",
	"shadowbox",
	"dataTable",
	"alphanumeric",
	"autocomplete"
);

include "plugin_loader.php";

/* requisição para vistoriar itens */

	if(isset($_POST['vistoriar'])) {
	
		$qtde 				= $_POST['qtde_vistoria'];
		$faturamento		= $_POST['faturamento'];
		$peca				= $_POST['peca'];
		
		for( $i = 0; $i < count($faturamento); $i++ ) {
		
			if(strlen($qtde[$i]) == 0)
				continue;
				
			$qt = ($qtde[$i] < 0) ? 0 : $qtde[$i];

			$sql = "SELECT peca FROM tbl_peca WHERE referencia = '" . $peca[$i] . "'";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) == 0)
				continue;
				
			$peca_id = pg_fetch_result($res,0,0);
				
			$sql = 'SELECT sum(qtde) FROM tbl_faturamento_item WHERE faturamento = ' . $faturamento[$i] . ' AND peca = ' . $peca_id;
			$res = pg_query($con,$sql);
			$qtd_itens = @pg_fetch_result($res,0,0);
			
		
			if ($qt > $qtd_itens) {
				$msg_erro[] = 'A quantidade vistoriada da peça ' . $peca[$i] . ' não pode ser maior que o total para vistoria.';
				continue;
			}
			
			if(empty($msg_erro)){
				$sql = 'UPDATE tbl_faturamento_item SET qtde_inspecionada = ' . $qt . ' WHERE faturamento = ' . $faturamento[$i] . ' AND peca = ' . $peca_id;
				$res = pg_query($con,$sql);
				//echo $sql;
			}

		}
		if(empty($msg_erro))
			$msg = "Gravado com Sucesso";
		
	}

/* fim requisição vistoria de itens */ 

if(isset($_POST['codigo_posto']) && isset($_POST['descricao_posto']))
{
	if((strlen(trim($_POST["codigo_posto"])) <= 0) && (strlen(trim($_POST["descricao_posto"])) <= 0)){
		$msg_erro["msg"][]    = "Preencha todos os campos obrigatórios.";
		$msg_erro["campos"][] = "posto";
	}
}
?>
<script>
	$(function(){
	
		$(".inspeciona").numeric();

		$(".vistoria > input").click(function(e){
			return false;
		});

		Shadowbox.init();
		
		$.autocompleteLoad(Array("produto", "peca", "posto"));
		
		$("span[rel=lupa]").click(function () {
	      $.lupa($(this));
	    });
	});

	function retorna_posto(retorno){
	$("#codigo_posto").val(retorno.codigo);
	$("#descricao_posto").val(retorno.nome);
	}
</script>

<?

 if(isset($msg_erro)) { ?>

	<div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
	</div>

<?php } ?>
<?php if(strlen($msg) > 0) { ?>

	<div class="alert alert-success">
	<h4><?php echo $msg; ?></h4>
	</div>

<?php } ?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form action="<?php echo $PHP_SELF; ?>" method="POST" name="frm_pesquisa" class='form-search form-inline tc_formulario' >
<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
<br>
<div class='row-fluid'>
	<div class='span2'></div>
	<div class='span4'>
		<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
			<label class='control-label' for='codigo_posto'>Código Posto</label>
			<div class='controls controls-row'>
				<div class='span7 input-append'>
					<h5 class='asteristico'>*</h5>
					<input type="text" id="codigo_posto" name="codigo_posto" value="<? echo $codigo_posto ?>" class="span12">
					<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
					<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
				</div>
			</div>
		</div>
	</div>
	<div class='span4'>
		<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
			<label class='control-label' for='descricao_posto'>Nome Posto</label>
			<div class='controls controls-row'>
				<div class='span12 input-append'>
					<h5 class='asteristico'>*</h5>
					<input type="text" id="descricao_posto" name="descricao_posto" size="30" value="<?echo $descricao_posto?>" class="frm">
					<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
					<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
				</div>
			</div>
		</div>
	</div>
	<div class='span2'></div>
</div>

<p><br/>
		<input class='btn' type="submit" name="enviar" value="Pesquisar" />
</p><br/>
</form>
<form action="<?=$PHP_SELF?>" method="POST" >
<?php
	if ( isset( $_POST['codigo_posto'] ) ) {
		$posto_codigo	= $_POST['codigo_posto'];
		$sql = "SELECT posto 
				FROM tbl_posto_fabrica 
				WHERE codigo_posto = '".$posto_codigo."'
				AND fabrica = $login_fabrica;";
		$query = pg_query($con,$sql);
		if(pg_num_rows($query) > 0) {
			$posto_login = trim(pg_fetch_result($query,0,posto));
			$posto = $posto_login;
			$login_posto = $posto;
		}
		else
			$msg_erro = 'Posto não Encontrado';
	}
	if (isset($login_posto)) { 
?>
<input type="hidden" name="codigo_posto" size="8"  value="<? echo $posto_codigo; ?>" class="frm">
<input type="hidden" name="descricao_posto" size="8"  value="<? echo $_POST['descricao_posto']; ?>" class="frm">
<br>
<!--
<TABLE width="700" align="center" border="0" cellspacing="1" cellpadding="0" name="dados">
<TR>
	<TD colspan='8' class="texto_avulso">
		<b>ATENÇÃO</b><br />
		As peças listadas abaixo devem ser armazenadas no seu posto autorizado para vistoria. Podem ser descartadas após 90 dias da data do extrato.
	</TD>
</TR>

</table>
-->
<? 
		if ($resumo == "sim"){
			$colspan_1 = 5;
			$colspan_2 = 7;
		}else{
			$colspan_1 = 2;
			$colspan_2 = 4;
			$colspan_3 = 6;
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
				AND posto     = $posto";
		$res = pg_query ($con,$sql);

		if (pg_num_rows($res)>0){
			$posto_codigo = pg_fetch_result($res,0,codigo_posto);
			$descricao_posto   = pg_fetch_result($res,0,nome);
		}
	
		echo '<br /><div><span class="label label-info">Clique em um registro para consultar as ordens de serviço da peça escolhida</span></div>';

		$topo ="<br />";
		$topo .= "<table class='table table-striped table-fixed' >\n";

		$topo .=  "<caption class='titulo_tabela'>Vistoria de Peças</caption>\n";
	
		$topo .=  "<thead>\n";

		$topo .=  "<tr>\n";
		$topo .=  "<td class='tal'><b>Código</b>: $posto_codigo <br/> <b>Razão Social</b>: $descricao_posto</td>\n";

		if ($resumo=="sim"){
			$topo .=  "<td class='tal'>Peças<br><a href='$PHP_SELF?posto=$posto'>TODAS PEÇAS</a></td>\n";
		}else{
		}
		$topo .=  "</tr>\n";
		$topo .=  "</thead>\n";
		$topo .=  "<tbody>\n";

/*
		$topo .=  "</thead>\n";
		$topo .=  "</table>\n";

		$topo .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
*/

		# Pega os três ultimos extratos
		$sql = "SELECT extrato,
					TO_CHAR(data_geracao,'DD/MM/YYYY') AS data_geracao
				FROM tbl_extrato
				WHERE fabrica = $login_fabrica
				AND   posto   = $login_posto
				ORDER BY tbl_extrato.data_geracao DESC
				LIMIT 3";

		$res = pg_query ($con,$sql);
		$numero_de_extrato = ($login_fabrica == 51) ? 3 : pg_num_rows($res);
		$y = 0;
		echo $topo;
		$cont = 0;
		$meses = array('01' => 'Janeiro','02' => 'Fevereiro','03' => 'Março','04' => 'Abril','05' => 'Maio',	'06' => 'Junho','07' => 'Julho','08' => 'Agosto','09' => 'Setembro','10' => 'Outubro',	'11' => 'Novembro','12' => 'Dezembro'
			);
		for ($i=0; $i<$numero_de_extrato; $i++){
		
			if($login_fabrica != 51 ) {
				$extrato_numero = pg_fetch_result($res,$i,'extrato');
				$extrato_data   = pg_fetch_result($res,$i,'data_geracao');
			}

			$sqlm = "SELECT to_char(CURRENT_DATE - INTERVAL '$i MONTHS','YYYY-MM')";
			$resm = pg_query($con,$sqlm);
			$extrato_mes = pg_fetch_result($resm,0,0);
			$mes_extrato = explode('-',$extrato_mes);


			if(in_array($login_fabrica,array(51,94,98,104,105)))

				foreach($meses as $num => $mes)
					if($num == $mes_extrato[1]) {
						$extrato_mes_print = $mes . ' de ' . $mes_extrato[0]. ' - ';
						break;
					}

			#echo '<tr><td>&nbsp;</td></tr>';
			echo "<tr class='titulo_coluna'>\n";
			echo "<th colspan='$colspan_3'>";

			echo (in_array($login_fabrica,array(51,94,98,104,105,106,142))) ?  $extrato_mes_print : ($i+1);
			echo "</b>";
			echo (!in_array($login_fabrica,array(51,94,98,104,105,106,142))) ? "" : "Extrato :  $extrato_numero - $extrato_data";

			echo "</th>\n";
			echo "</tr>\n";

			echo "<tr class='titulo_coluna'>\n";

			if(in_array($login_fabrica,array(51,94,98,104,105,106,142))) {
				echo "<th>Nota Fiscal</th>\n";
				if(!in_array($login_fabrica,array(51))){
					#$estilo = "style='display:none'";
				}
				echo "<th $estilo>Emissão</th>\n";

			}
			echo "<th>Código</th>\n";
			echo "<th>Descrição</th>\n";

			if ($resumo == "sim"){
				echo "<th>Preço.</th>\n";
				echo "<th>Qtde.</th>\n";
				echo "<th>ICMS</th>\n";
				echo "<th>IPI</th>\n";
				echo "<th>Total</th>\n";
			}else{
				echo "<th>Qtde.</th>\n";
				echo "<th>Qtde. Vistoriada</th>\n";
			}
			echo "</tr>\n";
			echo "<tr><th> </th></tr>";

			if(in_array($login_fabrica,array(94,98,104,105,106,142))) {

				$campos = " ,nota_fiscal ";
				$group = " ,nota_fiscal ";
			}

			if($login_fabrica == 51) {
				$campos = " ,nota_fiscal, to_char(emissao,'DD/MM/YYYY') as emissao ";
				$group = " ,nota_fiscal, emissao ";
				$cond = " 
				AND tbl_faturamento.fabrica             = 10
				AND tbl_peca.fabrica = $login_fabrica
				AND to_char(tbl_faturamento.emissao,'YYYY-MM') = '$extrato_mes' ";
				$order = 'nota_fiscal';
			}else{
				$order = 'tbl_peca.descricao';
				$cond = " AND tbl_faturamento.fabrica = $login_fabrica";

				if(!in_array($login_fabrica,array(94,98,104,105,106,142))) {

					$cond .=" AND tbl_faturamento.extrato_devolucao = $extrato_numero ";
				}
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
						/*tbl_faturamento_item.faturamento_item,*/
						SUM (tbl_faturamento_item.qtde) as qtde,
						tbl_faturamento_item.qtde_inspecionada AS qtde_inspecionada,
						SUM (tbl_faturamento_item.base_icms) AS base_icms, 
						SUM (tbl_faturamento_item.valor_icms) AS valor_icms,
						SUM (tbl_faturamento_item.base_ipi) AS base_ipi,
						SUM (tbl_faturamento_item.valor_ipi) AS valor_ipi
						$campos
					FROM tbl_faturamento
					JOIN tbl_faturamento_item USING (faturamento)
					JOIN tbl_peca             USING (peca)
					WHERE tbl_faturamento.posto             = $login_posto
						$cond
						AND tbl_faturamento.cfop IN ('694921','694922','594919','594920','594921','594922','594923','5949','6949','6959')
						";
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
			}elseif(!in_array($login_fabrica,array(94,98,104,105,106,142,151))) {
				if($login_fabrica != 51)
					$sql .=" AND tbl_peca.devolucao_obrigatoria IS NOT TRUE ";
				else
					$sql .=" AND tbl_peca.devolucao_obrigatoria ";
				$sql .= "AND tbl_peca.produto_acabado IS NOT TRUE";
			}
			

			if(in_array($login_fabrica,array(94,98,104,105,106,142,151))) {

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
						/*tbl_faturamento_item.faturamento_item,*/
						tbl_faturamento_item.aliq_icms,
						tbl_faturamento_item.aliq_ipi,
						tbl_faturamento_item.qtde_inspecionada
						$group
					ORDER BY $order";
			// echo nl2br($sql);
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
				//$preco               = pg_fetch_result ($resX,$x,preco);
				//$total               = pg_fetch_result ($resX,$x,total);
				$qtde                = pg_fetch_result ($resX,$x,qtde);
				$qtde_inspecionada   = pg_fetch_result ($resX,$x,qtde_inspecionada);
				$faturamento		 = pg_fetch_result ($resX,$x,faturamento);

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

				echo "<tr id='dados$cont' onclick='exibe_os(".$faturamento.",".$peca.",".$cont.");' >\n";

				if(in_array($login_fabrica,array(51,94,98,104,105,106,142))) {
					echo "<td class='tac'>".pg_fetch_result($resX,$x,'nota_fiscal'). "</td>\n";
					if(!in_array($login_fabrica,array(94,104,105,106,142))){
						echo "<td class='tac' $estilo>".pg_fetch_result($resX,$x,'emissao')."</td>\n";	
					}

				}
				echo "<td class='tal'>$peca_referencia</td>";
				echo "<td class='tal'>$peca_descricao</td>\n";

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

				if ($resumo == "sim"){
					echo "<td class='tac'>".number_format($preco,2,",",".")."</td>\n";
					echo "<td class='tac'>$qtde</td>\n";
					echo "<td class='tac'>$aliq_icms</td>\n";
					echo "<td class='tac'>$aliq_ipi</td>\n";
					echo "<td class='tac'".number_format($total,2,",",".")."</td>\n";
				}else{
					echo "<td class='tac'>$qtde_cor</td>\n";
					echo "<td class='tac'>
							<input type='hidden' name='faturamento[]' value='".$faturamento."' />
							<input type='hidden' name='peca[]' value='".$peca_referencia."' />
							<input type='text' name='qtde_vistoria[]' value='".$qtde_inspecionada."' size='3'/>
						  </td>\n";
				}

				echo "</tr>\n";

				$y++;

				flush();
			}
		}
		if ($numero_de_extrato == 0){
			echo "<tr class='tac'>\n";
			echo "<td class='tac'";
			echo "Não há peças para vistoria.";
			echo "</td>\n";
			echo "</tr>\n";
		}
		echo "</tbody>\n";
		echo "</table>\n";
		echo "<br><br>";
	if($cont > 0) {
?>
		<input type="submit" name="vistoriar" value="Marcar como Vistoriados" style="cursor:pointer;" />
<? } ?>
</form>

<script type="text/javascript">


	cont = new Array();

	function exibe_os(fat,peca,linha) {

		if (cont[linha] === false) {
			$(".resp"+linha).hide();
			cont[linha] = true;
		}
		else {
			
			cont[linha] = false;
			url = '<?php echo $_SERVER[PHP_SELF]; ?>?ajax=sim&faturamento='+fat+'&peca='+peca+'&resp='+linha;
			loading = '<tr style="border:none;" class="loading"><td colspan="6">Carregando..</td></tr>';
			$("#dados" + linha).after(loading);
			$.get(url, function(data) {
			  $(".loading").fadeOut("slow");
			  $("#dados" + linha).after(data);
			});
		}
	}	  

</script>

<p><p>

<? } include "rodape.php"; ?>

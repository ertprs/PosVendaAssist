<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';
include_once "../class/tdocs.class.php";
$tDocs = new TDocs($con, $login_fabrica);

	$caminho = "imagens_pecas";
	if($login_fabrica<>10){
	$caminho = $caminho."/".$login_fabrica;

	}

if (strlen($_POST["btn_busca"]) > 0) {
	$btnacao = trim($_POST["btn_busca"]);
}
$peca = $_GET['peca'];
if(strlen($peca)==0) $peca = $_POST['peca'];
if($btnacao=="Buscar"){
	$referencia = $_POST['referencia'];
	
	if(strlen($referencia)==0)
		$msg_erro = traduz("Digite um campo para pesquisa");
}


$layout_menu = "callcenter";
$title = traduz("DADOS CADASTRAIS DA PEÇA");
include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");

?>

<script language="JavaScript">

$(function() {
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});
	});

	function retorna_peca(retorno){
        $("#peca_referencia").val(retorno.referencia);
		$("#peca_descricao").val(retorno.descricao);
    }

function fnc_pesquisa_peca_fora_linha (campo, tipo, controle) {

	if (campo.value != "") {
		var url = "";
		url = "peca_fora_linha_pesquisa.php?controle=" + controle + "&campo=" + campo + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.referencia= document.frm_peca_fora_linha.referencia;
		janela.descricao = document.frm_peca_fora_linha.descricao;
		janela.focus();
	}
	else
		alert('<?=traduz("Informar toda ou parte da informação para realizar a pesquisa!")?>');
}
</script>

<body>

<div id="wrapper">
<?php if(strlen($msg_erro) > 0){ ?>
		<div class="alert alert-danger"><h4><?php echo $msg_erro; ?></h4></div>
<?php } ?>
<form class='form-search form-inline tc_formulario' name="frm_peca_fora_linha" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="peca" value="<? echo $peca ?>">
<div class="titulo_tabela"><?=traduz('Parâmetros de Pesquisa')?></div>
<br />
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<label class='control-label'><?=traduz('Referência')?></label>
				<div class='controls controls-row input-append'>
					<div class='span8'>
						<h5 class='asteristico'>*</h5>
						<input type="text" id="peca_referencia" name="referencia" value="<? echo $referencia; ?>" size="15" maxlength="20" onfocus="if (document.getElementById('erro_fora_linha')) {  document.getElementById('erro_fora_linha').innerHTML = ''; }" class="frm"><span class='add-on' rel="lupa"><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" />
					</div>
				</div>	
			</div>
			<div class='span4'>
				<label class='control-label'><?=traduz('Descrição')?></label>
				<div class='controls controls-row input-append'>
					<div class='span8'>
						<h5 class='asteristico'>*</h5>
						<input type="text" id="peca_descricao" name="descricao"  value="<? echo $descricao; ?>"  size="30" maxlength="50"  onfocus="if (document.getElementById('erro_fora_linha')) { document.getElementById('erro_fora_linha').innerHTML = ''; }" class="frm"><span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" />
					</div>
				</div>
			</div>
		<div class='span2'></div>
	</div>
	<br />
		<INPUT class="btn" TYPE="submit" name='btn_busca' value='<?=traduz("Buscar")?>'>
		<br /><br />
</form>

<?php 

	if($login_fabrica == 1){
		?>
		<p align="center">
			<form method="POST" action="relatorio_pecas_lista_basicas.php" target="_blank">
				<input type="hidden" name="gerar_excel" value="true" />
				<input type="hidden" name="peca_consulta_dados" value="sim" />
				<input type="submit" value='<?=traduz("Gerar Excel - Peças que Constam em Lista Básica de Produtos")?>' />
			</form>
		</p>
		<?php
	}

?>

<?



//FAZ A PESQUISA DA PEÇA PRA SABER SE A MESMA ESTÁ CADASTRADA NO NOSSO BANCO DE DADOS.
if( $btnacao == 'Buscar'){
	$referencia = trim($_POST['referencia']);
	if(($referencia > 0) OR ($referencia == 0)) {
	//OBTEM O CODIGO DA PEÇA DO BANCO(SEQUENCE)
		$sql = "SELECT peca FROM tbl_peca
					WHERE	referencia = '$referencia'
					AND		fabrica = $login_fabrica limit 30";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) > 0){
			$peca  = trim(pg_fetch_result($res,0,peca));
		}else{ echo "<FONT COLOR=\"#FF0000\"><B>".traduz("Peça não encontrada").".</B></FONT>"; exit; }
	}
}


if(strlen($msg_erro)==0){
  if (strlen($peca) > 0){

	if( $btnacao == 'Buscar' OR strlen($peca) >0){

		if($login_fabrica == 35){
			$campos_pecas = ", tbl_peca.estoque
							 , tbl_peca.origem 
							 , tbl_peca.unidade
							 , tbl_peca.classificacao_fiscal
							 , tbl_peca.multiplo
							 , tbl_peca.bloqueada_venda
							 , tbl_peca.promocao_site
							 , tbl_peca.peca_critica_venda
							 , tbl_peca.preco_anterior
							 , TO_CHAR(tbl_peca.data_inicial_liquidacao, 'DD/MM/YYYY') AS data_inicial
							 , tbl_peca.qtde_max_site AS qtde_max_posto
							 , tbl_peca.qtde_disponivel_inicial_site AS qtde_inicial_disponivel
							 , tbl_peca.multiplo_site
							 , tbl_peca.qtde_disponivel_site
							 , tbl_peca.posicao_site
							 , tbl_peca.liquidacao
							 , tbl_peca.informacoes ";
		}

		###CARREGA REGISTRO
		if (strlen($peca) > 0) {
			$sql = "SELECT tbl_peca.peca                   ,
							tbl_peca.referencia_fabrica    ,
							tbl_peca.referencia            ,
							tbl_peca.descricao             ,
							tbl_peca.ipi                   ,
							tbl_peca.garantia_diferenciada ,
							tbl_peca.devolucao_obrigatoria ,
							tbl_peca.item_aparencia        ,
							tbl_peca.bloqueada_garantia    ,
							tbl_peca.acessorio             ,
							tbl_peca.aguarda_inspecao      ,
							tbl_peca.peca_critica          ,
							tbl_peca.produto_acabado       ,
							tbl_peca.retorna_conserto      ,
							tbl_peca.peso                  ,
							tbl_peca.ativo
							$campos_pecas
					FROM    tbl_peca
					WHERE   tbl_peca.peca = '$peca'
					AND     fabrica = $login_fabrica limit 30 ";
			$res = pg_exec ($con,$sql);			

			if (pg_numrows($res) > 0) {
				$referencia_fabrica       = trim(pg_result($res,0,referencia_fabrica));

				if ($login_fabrica == 171) {
					$referenciaFabrica = " / " . $referencia_fabrica;
				}
				$peca                     = trim(pg_result($res,0,peca));
				$referencia               = trim(pg_result($res,0,referencia));
				$descricao                = trim(pg_result($res,0,descricao));
				$ipi                      = trim(pg_result($res,0,ipi));
				$garantia_diferenciada    = trim(pg_result($res,0,garantia_diferenciada));
				$devolucao_obrigatoria    = trim(pg_result($res,0,devolucao_obrigatoria));
				$item_aparencia           = trim(pg_result($res,0,item_aparencia));
				$bloqueada_garantia       = trim(pg_result($res,0,bloqueada_garantia));
				$acessorio                = trim(pg_result($res,0,acessorio));
				$aguarda_inspecao         = trim(pg_result($res,0,aguarda_inspecao));
				$peca_critica             = trim(pg_result($res,0,peca_critica));
				$produto_acabado          = trim(pg_result($res,0,produto_acabado));
				$peso                     = trim(pg_result($res,0,peso));
				$peso 					  = number_format($peso,3,',','.');
				$retorna_conserto         = trim(pg_result($res,0,retorna_conserto));
				$ativo                    = trim(pg_result($res,0,ativo));
				if($ativo=="t"){
					$ativo = traduz("Ativo");
				}else{
					$ativo = traduz("Inativo");
				}

				if($login_fabrica == 35){
					$estoque 					= trim(pg_result($res,0,estoque));
					$origem 					= trim(pg_result($res,0,origem));
					$unidade 					= trim(pg_result($res,0,unidade));
					$classificacao_fiscal 		= trim(pg_result($res,0,classificacao_fiscal));
					$multiplo 					= trim(pg_result($res,0,multiplo));					
					$bloqueada_venda			= trim(pg_result($res,0,bloqueada_venda));
					$promocao_site				= trim(pg_result($res,0,promocao_site));
					$peca_critica_venda			= trim(pg_result($res,0,peca_critica_venda));
					$preco_anterior				= trim(pg_result($res,0,preco_anterior));
					$preco_anterior 			= number_format($preco_anterior,2,',','.');
					$data_inicial				= trim(pg_result($res,0,data_inicial));
					$qtde_max_posto				= trim(pg_result($res,0,qtde_max_posto));
					$qtde_inicial_disponivel 	= trim(pg_result($res,0,qtde_inicial_disponivel));
					$multiplo_site 				= trim(pg_result($res,0,multiplo_site));
					$qtde_disponivel_site		= trim(pg_result($res,0,qtde_disponivel_site));
					$posicao_site				= trim(pg_result($res,0,posicao_site));
					$liquidacao					= trim(pg_result($res,0,liquidacao));
					$informacoes				= trim(pg_result($res,0,informacoes));
				}

			}
		}
?>

		<!-- VERIFICA SE A PECA ESTA FORA DE LINHA - CASO ESTEJA, MOSTRA MENSAGEM -->
		<? $sql = "SELECT peca,libera_garantia FROM tbl_peca_fora_linha
					WHERE	peca = $peca
					AND		fabrica = $login_fabrica limit 30 ";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) > 0){
			$mlibera_garantia = pg_fetch_result($res,0,'libera_garantia');
		?>
		<table width='500' align='center' border='0' cellpadding='2' cellspacing='1'  bgcolor='#FF0000' id='erro_fora_linha'>
		<tr  style='font-size:14px'>
			<td class='alert alert-error'>
			<? if ($mlibera_garantia == "t"){ ?>
				<?=traduz('Peça fora de linha - Atendimento somente para garantia')?>
			<?}else{?>
				<?=traduz('Peça fora de linha')?>
			<?}?>
				</td>
		</tr>
		</table><br>
		<? } ?>
		<!-- FIM DA VERIFICACAO -->

		<!-- TOPO DAS INFORMACOES SOBRE A PECA -->
		<br>
		<table class='table table-striped table-bordered table-large'>
		<tr class="titulo_coluna">
			<th  height='25' style='font-size: 14px;' colspan='8'><?=traduz('Informações sobre a peça')?>: <? echo "$referencia"; ?> <? echo $referenciaFabrica; ?></th>
		</tr>
		<tr  class='titulo_coluna'>
			<th colspan='2'><?=traduz('Garantia Diferenciada (meses)')?></th>
			<th><?=traduz('Devolução Obrigatória')?></th>
			<th><?=traduz('Item de Aparência')?></th>
			<th><?=traduz('Bloqueada para Garantia')?></th>
			<?php if($login_fabrica == 35){
					echo '<th>'.traduz('Estoque').'</th>';
					echo '<th>'.traduz('Origem').'</th>';
					echo '<th>'.traduz('Unidade').'</th>';
				}
			?>
		</tr>
		<tr  bgcolor='#F7F5F0'>
			<td align='center' colspan='2'>
				<? echo $garantia_diferenciada ?>
			</td>

			<td align='center'>
				<? if ($devolucao_obrigatoria == 't' ) {echo " <B>X</B> ";}else{echo " - ";} ?>
			</td>

			<td align='center'>
				<? if ($item_aparencia == 't' ) {echo " <B>X</B> ";}else{echo " - ";} ?>
			</td>

			<td align='center'>
				<? if ($bloqueada_garantia == 't' ) {echo " <B>X</B> ";}else{echo " - ";} ?>
			</td>
			<?	if($login_fabrica == 35){
					echo '<td>' . $estoque . '</td>';
					echo '<td>' . $origem . '</td>';
					echo '<td>' . $unidade . '</td>';
				}
			?>
		</tr>
		<tr class="titulo_coluna">
			<th>IPI (*)</th>
			<th><?=traduz('Acessório')?></th>
			<th><?=traduz('Aguarda Inspeção')?></th>
			<th><?=traduz('Peça Crítica')?></th>
			<th><?=traduz('Produto Acabado')?></th>
			<?php if ($login_fabrica == 35) { ?>
				<th><?=traduz('Peso Kg');?></th>
				<th><?=traduz('Classif. Fiscal');?></th>
				<th><?=traduz('Múltiplo');?></th>
			<?php } ?>
		</tr>
		<tr  bgcolor='#F7F5F0'>

			<td align='center'>
				<? echo "$ipi"; ?>
			</td>

			<td align='center'>
				<? if ($acessorio == 't' ) {echo " <B>X</B> ";}else{echo " - ";} ?>
			</td>

			<td align='center'>
				<? if ($aguarda_inspecao == 't' ) {echo " <B>X</B> ";}else{echo " - ";} ?>
			</td>

			<td align='center'>
				<? if ($peca_critica == 't' ) {echo " <B>X</B> ";}else{echo " - ";} ?>
			</td>

			<td align='center'>
				<? if ($produto_acabado == 't' ) {echo " <B>X</B> ";}else{echo " - ";} ?>
			</td>
			<?	if($login_fabrica == 35){
					echo '<td>' . $peso . '</td>';
					echo '<td>' . $classificacao_fiscal . '</td>';
					echo '<td>' . $multiplo . '</td>';
				}
			?>			
		</tr>
		</table>
		<?if($login_fabrica == 3){?>
		<table class='table table-striped table-bordered table-large'>
		<tr class='titulo_coluna'>
			<th><?=traduz('Intervenção')?></th>
			<th><?=traduz('Peso')?></th>
		</tr>
		<tr  bgcolor='#F7F5F0'>
			<td align='center'>
				<?if ($retorna_conserto == 't' ) {echo " <B>X</B> ";}else{echo " - ";} ?>
			</td>

			<td align='center'>
				<? echo $peso; ?>
			</td>
		</tr>
		</table>		
		<?}?>

		<? if($login_fabrica == 35) { 
			$centraliza = " style='text-align: center;'";
			$col 		= ' colspan="2"';
		?>
			<br>
			<table class='table table-striped table-bordered table-large'>
				<tr class="titulo_coluna">
					<th>Retorna para Conserto</th>
					<th>Bloqueada para Venda</th>
					<!-- <th>Peça Crítica Garantia</th> -->
					<th>Obrigatório PO-Peça</th>
					<th>Peça Crítica Venda</th>
					<th>Ativo</th>
					<th<?=$col ?>>Preço Anterior</th>
				</tr>
				<tr bgcolor='#F7F5F0'>
					<td <?=$centraliza ?>><? if($retorna_conserto == 't') echo 'Sim'; else echo 'Não'; ?></td>
					<td <?=$centraliza ?>><? if($bloqueada_venda == 't') echo 'Sim'; else echo 'Não'; ?></td>
					<!-- <td <?=$centraliza ?>><? if($peca_critica == 't') echo 'Sim'; else echo 'Não'; ?></td></td> -->
					<td <?=$centraliza ?>><? if($promocao_site == 't') echo 'Sim'; else echo 'Não'; ?></td></td>
					<td <?=$centraliza ?>><? if($peca_critica_venda == 't') echo 'Sim'; else echo 'Não'; ?></td></td>
					<td <?=$centraliza ?>><? if($ativo == 'Ativo') echo 'Sim'; else echo 'Não'; ?></td></td>
					<td <?=$centraliza.$col ?>>R$ <?=$preco_anterior ?></td>
				</tr>
				<tr class="titulo_coluna">
					<th>Data Inicial</th>
					<!-- <th>Loja Virtual</th> -->
					<th>Qtde Máxima por Posto</th>
					<th>Qtde Inicial Disponível</th>
					<th>Qtde Múltipla por Peça</th>
					<th>Prioridade no Site</th>
					<th>Qtde Disponível</th>
					<th>Peça em Liquidação</th>
				</tr>
				<tr bgcolor='#F7F5F0'>
					<td <?=$centraliza ?>><?=$data_inicial ?></td>
					<!-- <td <?=$centraliza ?>><? if($promocao_site == 't') echo 'Sim'; else echo 'Não'; ?></td></td> -->
					<td <?=$centraliza ?>><?=$qtde_max_posto ?></td></td>
					<td <?=$centraliza ?>><?=$qtde_inicial_disponivel ?></td></td>
					<td <?=$centraliza ?>><?=$multiplo_site ?></td></td>
					<td <?=$centraliza ?>><?=$posicao_site ?></td></td>
					<td <?=$centraliza ?>><?=$qtde_disponivel_site ?></td></td>
					<td <?=$centraliza ?>><? if($liquidacao == 't') echo 'Sim'; else echo 'Não'; ?></td></td>
				</tr>
				<tr class="titulo_coluna">
					<th colspan="7">Informações Referentes à Loja virtual</th>
				</tr>
				<tr bgcolor='#F7F5F0'>
					<td colspan="7"><textarea name="informacoes" rows="10" cols="200" class='frm' style="margin: 0px 0px 10px; width: 813px; height: 178px;" readonly><?=$informacoes ?></textarea></td>
				</tr>
			</table>
		<? } ?>
		<!-- FIM DAS INFORMACOES SOBRE A PECA -->
		<?


		if($peca > 0){
			//FAZ A VERIFICACAO SE A PECA FOI SUBSTITUIDA - DE-PARA

			$sql = "SELECT  tbl_peca.descricao, tbl_peca.referencia
					FROM tbl_peca
					JOIN tbl_depara ON tbl_depara.para = tbl_peca.referencia
					WHERE tbl_depara.fabrica = $login_fabrica
					AND tbl_depara.peca_de = '$peca' limit 30";

			$res = pg_query($con,$sql);
			$contador_res = pg_num_rows ($res);

			//FIM

			if (pg_num_rows($res) > 0){
				//SE ENCONTROU REGISTRO DE "DE - PARA" EXIBE TABELA COM AS INFORMAÇÕES SOBRE A PEÇA PELA QUAL FOI TROCADA
				echo "<br><br>";
				echo "<TABLE class='table table-striped table-bordered table-large'>";
				echo "<TR class='titulo_coluna'>";
				echo "	<TH COLSPAN='2' align='center' style='font-size:14px;'>".traduz("Peça Substituida por")."</TH>";
				echo "</TR>";

				echo "<TR class='titulo_coluna' align='center'>";
				echo "<TH width='100'>".traduz("Referência")."</TH>";
				echo "<TH >".traduz("Descrição")."</TH>";
				echo "</TR>";

				for ($i = 0 ; $i < $contador_res; $i++){
					$referencia_para   = trim(pg_fetch_result($res,$i,referencia));
					$descricao_para = trim(pg_fetch_result($res,$i,descricao));

					$cor = '#F1F4FA';

					echo "<TR align='center' bgcolor='#F1F4FA'>";
					echo "<TD >$referencia_para</TD>";
					echo "<TD >$descricao_para</TD>";
					echo "</TR>";
				}echo "</TABLE>";
				//FIM DA TABELA - "DE - PARA"
			}

?>
			<!-- INICIO DA TABELA  DE IMAGEM -->
			<?//HD 48138, foi colocado fabrica 3 no if
			if($login_fabrica ==1 or $login_fabrica ==4 or $login_fabrica ==5 or $login_fabrica ==35 or $login_fabrica ==45 or $login_fabrica ==3){

				$xpecas  = $tDocs->getDocumentsByRef($peca, "peca");
				if (!empty($xpecas->attachListInfo)) {

					$a = 1;
					foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
					    $fotoPeca = $vFoto["link"];
					    if ($a == 1){break;}
					}
					echo "<table class='table table-striped table-bordered table-large'>
						<tr  class='titulo_coluna'>
							<th align='center' style='font-size:14px'>
								".traduz("Imagem da peça")." - ".$referencia."
							</th>
						</tr>
						<tr bgcolor='#F1F4FA' >
							<td align='center'>
								<img width='180' src='".$fotoPeca."' border='0'>
							</td>
						</tr>
					</table>";
				} else {

					$contador=0;
					if ($dh = opendir("../".$caminho."/pequena/")) {
						while (false !== ($filename = readdir($dh))) {
							if($contador == 1) break;
							$xpeca = $peca.'.';
							if (strpos($filename,$peca) !== false){
								$po = strlen($xpeca);
								if(substr($filename, 0,$po)==$xpeca){
									$contador++;
								?>
								<table class='table table-striped table-bordered table-large'>
								<tr  class='titulo_coluna'>
								<th align='center' style='font-size:14px'><?=traduz('Imagem da peça')?> - <?echo $referencia ;?></th>
								</tr>
								<tr bgcolor='#F1F4FA' >
								<td align='center'>
								<img src='../<?echo $caminho;?>/media/<?echo $filename; ?>' border='0'>
								</td></tr>
								</table>
					<?			}
							}
						}

						if($contador == 0){
							if ($dh = opendir("../".$caminho."/pequena/")) {
								$Xreferencia = str_replace(" ", "_",$referencia);
								while (false !== ($filename = readdir($dh))) {
									if($contador == 1) break;
									if (strpos($filename,$Xreferencia) !== false){

										//$peca_referencia = ntval($peca_referencia);
										$po = strlen($Xreferencia);
										if(substr($filename, 0,$po)==$Xreferencia){
											$contador++;
										?>
										<table class='table table-striped table-bordered table-large'>
										<tr  class='titulo_coluna'>
										<th align='center' style='font-size:14px'><b><?=traduz('Imagem da peça')?> - <?echo $referencia ;?></b></th>
										</tr>
										<tr class='Conteudo' bgcolor='#F1F4FA' >
										<td align='center'>
										<img src='../<?echo $caminho;?>/media/<?echo $filename; ?>' border='0'>
										</td></tr>
										</table>
							<?			}
									}
								}
							}
						}
					}
				}
			}
			?>

			<!-- INICIO DA TABELA  DE IMAGEM -->
			<?

			//BUSCA TODAS AS TABELAS E MOSTRA OS PRECOS DA PECA EM CADA TABELA
			$sql = "SELECT  tbl_tabela.sigla_tabela    ,
							tbl_tabela_item.preco
					FROM    tbl_tabela
					JOIN    tbl_tabela_item USING (tabela)
					JOIN    tbl_peca        ON tbl_peca.peca = tbl_tabela_item.peca
					WHERE   tbl_tabela_item.peca = '$peca'
					AND     tbl_tabela.fabrica   = $login_fabrica limit 30 ";
			$res = pg_query ($con,$sql);
			$contador_res_2 = pg_num_rows($res);

			if (pg_num_rows($res) > 0) {
				//INICIO DA TABELA
				echo "<br><br><TABLE class='table table-striped table-bordered table-large'>\n";
				echo "<TR class='titulo_coluna'>\n";
				echo "	<TH style='font-size: 14px'colspan='2' height='20'> ".traduz("Tabela e Preço da Peça")." </TH>\n";
				echo "</TR>";
				echo "<TR class='titulo_coluna'>\n";
				echo "	<TH> ".traduz("Tabela")."</TH>";
				echo "	<TH> ".traduz("Preço")." </TH>";
				echo "</TR>";

				for ($y = 0 ; $y < $contador_res_2; $y++){
					$sigla           = trim(pg_fetch_result($res,$y,sigla_tabela));
					$preco           = trim(pg_fetch_result($res,$y,preco));
					
					if ($y % 2 == 0) $cor="#F7F5F0"; 
					else $cor="#F1F4FA";
					
					if ($y % 2 == 0)
					{
						echo "<TR bgcolor='$cor'>";
						echo "	<TD align='center'><B>$sigla</B></TD>";
						echo "	<TD align='center'><B>". $real . number_format($preco,2,",",".");
						echo "	</B>";
					}else{
						$sigla           = trim(pg_fetch_result($res,$y,sigla_tabela));
						$preco           = trim(pg_fetch_result($res,$y,preco));
						echo "	<TD align='center'><B>$sigla</B></TD>";
						echo "	<TD align='center'><B>" . $real . number_format($preco,2,",",".");
						echo "	</B>";
					}
					echo "</TR>";
				}echo "</TABLE>";//FIM DA TABELA DOS PRECOS E TABELAS
			}//FIM DA BUSCA DE PRECOS NA TABELA

			//MOSTRA TODOS OS PRODUTOS QUE CONTEM A PECA
			if($login_fabrica == 3 ) $adicional = " AND tbl_produto.ativo IS TRUE";
			if ($login_fabrica != 3) { $cond_limit = "limit 30";}
			$sql = "SELECT DISTINCT tbl_produto.referencia, tbl_produto.descricao, tbl_produto.parametros_adicionais 
							FROM tbl_lista_basica
							JOIN tbl_produto USING (produto)
							WHERE tbl_lista_basica.fabrica = $login_fabrica
							$adicional
							AND   tbl_lista_basica.peca = '$peca' $cond_limit ";

			$res = pg_query ($con,$sql);
			$contador_res_3 = pg_num_rows($res);

			if(pg_num_rows($res) > 0)
			{
				//INICIO DA TABELA
				echo "<br><br><TABLE class='table table-striped table-bordered table-large'>";
				echo "<tr class='titulo_coluna'>";
				echo "	<tH colspan='2' style='font-size: 14px'>".traduz("Produto(s) que contém a peca")."</tH>";
				echo "</tr>";
				echo "<tr class='titulo_coluna'>";
				echo "	<th>Referência</th>";
				echo "	<th>Descrição</th>";
				echo " 	<th>Data Descontinuação</th>";
				echo "</tr>";

				for ($i = 0 ; $i < $contador_res_3; $i++){
					$produto           = trim(pg_fetch_result($res,$i,'referencia'));
					$descricao         = trim(pg_fetch_result($res,$i,'descricao'));

					$parametros_adicionais = pg_fetch_result($res, $i, 'parametros_adicionais');
					$parametros_adicionais = json_decode($parametros_adicionais, true);

					$data_descontinuado = $parametros_adicionais['data_descontinuado'];

					if ($i % 2 == 0) $cor="#F7F5F0"; else $cor="#F1F4FA";

					echo "<tr bgcolor='$cor'>";
					echo "	<td align='center'>$produto<br>";
					echo "	<td align='left'>$descricao<br>";
					echo "	<td align='left'>$data_descontinuado<br>";
					echo "</tr>";
				}echo "</table>";//FIM DA TABELA
			}// FIM DA LISTA DE PRODUTOS
		}

		if($login_fabrica==1){ //21161 29/5/2008
			echo "<br><br><TABLE width='700' border='0' cellspacing='1' cellpadding='2' class='tabela' align='center'>";
			echo "<tr class='titulo_coluna'>";
			echo "	<td>".traduz("Status")."</td>";
			echo "</tr>";
			echo "<tr bgcolor='#F7F5F0'>";
			
			/*HD-4074490*/
			if (strlen($peca) > 0) {
				$aux_sql = "SELECT informacoes FROM tbl_peca WHERE peca = $peca AND fabrica = $login_fabrica LIMIT 1";
				$aux_res = pg_query($con, $aux_sql);

				if (pg_num_rows($aux_res) > 0) {
					$aux_ativo = pg_fetch_result($aux_res, 0, 0);
				}

				if (strlen($aux_ativo) > 0) {
					$ativo = strtoupper($aux_ativo);
				} else {
					$ativo = "ATIVO";
				}
			}
			echo "	<td>$ativo</td>";
			echo "</tr>";
			echo "</table>";
		}
	}
}
}
echo "<BR><BR>";

include "rodape.php";

?>



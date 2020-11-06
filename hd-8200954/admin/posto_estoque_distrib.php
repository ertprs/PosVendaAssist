<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "gerencia";

include "autentica_admin.php";
include "funcoes.php";



if ($_POST["btn_acao"] == "submit") {
	$peca_referencia = $_POST["peca_referencia"];
	$peca_descricao  = $_POST["peca_descricao"];


	if (strlen($peca_referencia) > 0 && strlen($peca_descricao) > 0) {
		$sql = "SELECT tbl_peca.peca
				FROM tbl_peca
				WHERE fabrica = $login_fabrica and (
					UPPER(tbl_peca.referencia) = UPPER('{$peca_referencia}')
				)";
		$res = pg_query($con, $sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Peça não encontrada";
			$msg_erro["campos"][] = "peca";
		} else {
			 $peca = pg_fetch_result($res, 0, "peca");
		}
	} else {
		unset($peca_referencia, $peca_descricao);
	}
}

if (in_array($login_fabrica, [11,172])):
	if ($_POST['btn_acao'] == 'get_movimentacao_peca' and strlen($_POST['peca']) > 0):
		$get_peca = addslashes($_POST['peca']);

		if ($entradaSaidaAcertoPeca != false and $entradaSaidaAcertoPeca != ''):
			echo $entradaSaidaAcertoPeca;
			exit;	
		else:
			echo getMovimentacaoPeca($peca, $login_fabrica);
			exit;
		endif;
	endif;
endif;

$layout_menu = "gerencia";
$title = "ESTOQUE DISTRIB";

include "cabecalho_new.php";

$plugins = array(
	"autocomplete",
	"datepicker",
	"maskedinput",
	"shadowbox",
	"alphanumeric"
);

include "plugin_loader.php";

?>

<script>

$(function() {
	Shadowbox.init();
	$.autocompleteLoad(["peca"]);

	$("span[rel=lupa]").click(function() {
		$.lupa($(this));
	});


	$("a[id^=peca_]").on("click",function(){
		var linha = this.id.replace(/\D/g, "");
		$("#peca_referencia").val($("#peca_referencia_"+linha).val());
		$("#peca_descricao").val($("#peca_descricao_"+linha).val());
		$("#btn_acao").click();
	});

	<?php if (in_array($login_fabrica, [11,172])): ?>
		$(".btn-movimentacao").on('click', function() {
			var peca = $(this).attr('data-peca');

			$.ajax({
				type: "POST",
				url:  "<?$PHP_SELF?>",
				data: {btn_acao: 'get_movimentacao_peca', peca: peca},
				success: function(resposta){
					Shadowbox.open({
				        content: resposta,
				        player: 'html',
				        title: "Movimentação de Peça",
				        displayNav: false,
				        height: 600,
				        width: 1200
				    });

					//***** NÃO REMOVER *****//
				    $("#sb-body").css({'background' : '#ffffff'});
				}
			})
		});
	<?php endif; ?>
});

function retorna_peca(retorno) {
	$("#peca_referencia").val(retorno.referencia);
	$("#peca_descricao").val(retorno.descricao);
}


</script>

<?php if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error" >
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php } ?>

<?php if (count($msg_erro["msg"]) == 0 && strlen($msg_ok) > 0) { ?>
    <div class="alert alert-success" >
		<h4>Arquivo Enviado com Sucesso</h4>
    </div>
<?php } ?>



<form name="frm_estoque" method="POST" class="form-search form-inline tc_formulario" >
	<div class="titulo_tabela" >Parâmetros de Pesquisa</div>
	<br />


	<div class="row-fluid" >
		<div class="span2" ></div>

		<div class="span4" >
			<div class="control-group <?=(in_array('peca', $msg_erro['campos'])) ? 'error' : ''?>" >
				<label class="control-label" for="peca_referencia" >Referência Peça</label>

				<div class="controls controls-row" >
					<div class="span7 input-append" >
						<input type="text" name="peca_referencia" id="peca_referencia" class="span12" value="<? echo $peca_referencia ?>" />
						<span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
						<input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" />
					</div>
				</div>
			</div>
		</div>

		<div class="span4" >
			<div class="control-group <?=(in_array('peca', $msg_erro['campos'])) ? 'error' : ''?>" >
				<label class="control-label" for="peca_descricao" >Descrição Peça</label>

				<div class="controls controls-row" >
					<div class="span12 input-append" >
						<input type="text" name="peca_descricao" id="peca_descricao" class="span12" value="<? echo $peca_descricao ?>" />
						<span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
						<input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" />
					</div>
				</div>
			</div>
		</div>

		<div class="span2" ></div>
	</div>

	<p>
		<br/>
		<button class="btn" id="btn_acao" type="button" onclick="submitForm($(this).parents('form'));" >Pesquisar</button>
		<input type="hidden" id="btn_click" name="btn_acao" />
	</p>

	<br />

</form>


<?php

if ($_POST["btn_acao"] == "submit" && !count($msg_erro["msg"])) {

	if (!empty($peca)) {
		$cond = " and tbl_peca.referencia='$peca_referencia' ";
	}

	if(isset($fabricasAulik)) {
		$cond_f = " and tbl_peca.fabrica in ($fabricasAulik) ";
	}else{
		$cond_f = " and tbl_peca.fabrica in ($login_fabrica) ";
	}
	  
	 
	if(isset($fabricasAulik) || $login_fabrica == 123) {
    	 $qtd_estoque = "qtde >= 0";
    }else{
     	 $qtd_estoque = "qtde > 0";
    }	

		$sql = "SELECT 
					tbl_peca.peca,
					tbl_peca.referencia AS peca_referencia, 
					tbl_peca.descricao AS peca_descricao,
					sum(tbl_posto_estoque.qtde) as qtde,
					tbl_peca.fabrica 
				FROM tbl_posto_estoque
				INNER JOIN tbl_peca ON tbl_peca.peca = tbl_posto_estoque.peca $cond_f
				WHERE $qtd_estoque
				$cond
				group by 1, 2 
				order by tbl_peca.descricao";
				//die(nl2br($sql));
		$res = pg_query($con, $sql);
		?>

		<table id="estoque_movimentacao" class="table table-striped table-bordered" >
			<thead>
				<tr class="titulo_coluna" >
					<th>Peça</th>

					<th>Estoque</th>

					<?php if(isset($fabricasAulik)) { ?>
						 	<th>Entrada</th>
							<th>Saida</th>
				    <? 
				       	 }
					     
					     if($login_fabrica == 123) { 
					?>
						 	<th>Disponibilidade</th>
					<?php
					     }
					?>

				    <?php if (in_array($login_fabrica, [11,172])): ?>
				    	<th>Ações</th>
				    <?php endif; ?>
				</tr>
			</thead>
			<tbody>
				<?php
				if (pg_num_rows($res) > 0) {
					$rows = pg_num_rows($res);

					if(isset($fabricasAulik)) {
					// XLS
					$fileName = "posto_estoque.xls";
					$file = fopen("xls/$fileName", "w");	
					
					$excel_mov_peca = '';
         			$end            = '';
					$header  = '<table border="0" cellpadding="10">
								<tr>';
					$header .= "<td>
								<table border='0'>
								   	<thead>
	    								<tr bgcolor='#B8BAB8' align='center'>
	    									<td colspan='5'><b>ESTOQUE GERAL</b></td>
										</tr>
	    								<tr align='center'>
		        						  	<td><b>Peça Referência</b></td>
		        						  	<td><b>Peça Descrição</b></td>
		        					      	<td><b>Estoque</td>
		        						  	<td><b>Entrada</b></td>	
		        						  	<td><b>Saída</b></td>
	    						  		</tr>
								    </thead>
								    <tbody>";
					}   

					for ($i = 0; $i < $rows; $i++) { 

						if (in_array($login_fabrica, [11,172])) {
							$id_peca     = pg_fetch_result($res, $i, "peca");
							$cond_fabrica = " and tbl_peca.fabrica in (11,172) ";
						}else{
							$cond_fabrica = " and tbl_peca.fabrica in ($login_fabrica) ";
						}

						$peca_referencia = pg_fetch_result($res, $i, "peca_referencia");
						$peca_descricao  = pg_fetch_result($res, $i, "peca_descricao");
						$qtde            = pg_fetch_result($res, $i, "qtde");
						$peca_id         = pg_fetch_result($res, $i, "peca_id");
						$row_fabrica     = (int)pg_fetch_result($res, $i, "fabrica");
						if ($login_fabrica == 123) {
							$disponibilidade = ($qtde > 0) ? "Disponível" : "Indisponível";
						}

						if ($login_fabrica == $row_fabrica) { 
							if(isset($fabricasAulik)) {

							$sql2 = "SELECT SUM (tbl_faturamento_item.qtde_estoque) AS qtde_entrada
								    FROM   tbl_faturamento
										   JOIN tbl_fabrica ON tbl_faturamento.fabrica = tbl_fabrica.fabrica
									       JOIN tbl_faturamento_item using (faturamento)
											JOIN tbl_peca USING(peca)
									WHERE  tbl_faturamento.posto in (4311,376542)
									AND (
										tbl_faturamento.distribuidor IN (
											SELECT DISTINCT distribuidor FROM tbl_faturamento WHERE fabrica = 10 AND posto in (4311,376542))
										OR
										tbl_fabrica.parametros_adicionais::jsonb->>'telecontrol_distrib' = 't'
										AND tbl_faturamento.distribuidor is null
									)
									AND tbl_peca.referencia='$peca_referencia'
									AND tbl_faturamento.cancelada IS NULL
									AND tbl_faturamento.fabrica <> 0
									$cond_fabrica
									AND (tbl_faturamento.tipo_nf = 0 or tbl_faturamento.tipo_nf IS NULL)";
									//die(nl2br($sql2));
									$res2 = pg_query($con, $sql2);  
									$qtde_entrada = (int) pg_fetch_result($res2, 0, "qtde_entrada");
									$saida = ($qtde_entrada - $qtde);
									
											//HD-6977955							
											$body .=	"<tr>
								                          <td>{$peca_referencia}</td>
								                          <td>{$peca_descricao}</td>
	                        							  <td>{$qtde}</td>
	                        							  <td>{$qtde_entrada}</td>
	                        							  <td>{$saida}</td>
	                    							  	</tr>";
							} ?>
							<tr>
								<td>									
									<?="{$peca_referencia} - {$peca_descricao}"?>
									<input type="hidden" id="peca_referencia_<?=$i?>" value="<?=$peca_referencia?>">
									<input type="hidden" id="peca_descricao_<?=$i?>" value="<?=$peca_descricao?>">
									
								</td>

								 <td class="tac" ><?=$qtde?></td>

								 <?php if(isset($fabricasAulik)) { ?>
						 	      <td class="tac" ><?=$qtde_entrada?></td>
								  <td class="tac" ><?=$saida?></td>	
					             <? } ?>

					            <?php if($login_fabrica == 123) { ?>
										 <td class="tac" ><?=$disponibilidade?></td>	
								<?php } ?>
					            		
					            <?php if(in_array($login_fabrica, [11,172])): ?>
					             		<td class="tac"><a class='btn-movimentacao' data-peca='<?=$peca_referencia?>' style='cursor: pointer !important;'>Movimentação</a></td>
			         			<?php endif; ?>		
							</tr>
					<?php
						}
					}

					 if(isset($fabricasAulik)) {

					 	if(in_array($login_fabrica, [11,172])):
					 		if (!empty($peca)):
					 			$excel_mov_peca = getMovimentacaoPeca($peca, $login_fabrica, true);
		         				$end            = "</tr> </table>";
				 			else:
				 				$excel_mov_peca = '';
		         				$end            = '';
				 			endif;
					 	endif;

						$footer = "</tbody> </table> </td>";
						$arq_excel = $header . $body . $footer . $excel_mov_peca . $end;				        
						fwrite($file, $arq_excel);  
					    fclose($file);
	            	    flush();
						echo "<center>
								<br><p id='id_download2'><a href='xls/$fileName'><img src='../imagens/excel.gif'><br><font color='#3300CC'>Fazer download do relatório do Estoque</font></a></p><br>
							</center>";            	  							
					}	
				} else {
				?>
					<tr>
						<th colspan="<?= (in_array($login_fabrica, [11,172]) ? 5 : 4) ?>" >
							<div class="alert alert-danger" style="margin-bottom: 0px;" >
								<h4>Nenhuma peça com estoque registrada</h4>
							</div>
						</th>
					</tr>
				<?php
				}
				?>
				  
			</tbody>
		</table>
	<?php
}

function getMovimentacaoPeca(int $get_peca=null, $login_fabrica, $xls=false) {
	global $con, $login_fabrica;

	$content = '';

	if(in_array($login_fabrica, array(11,172))){
		if($_POST['btn_acao'] == 'get_movimentacao_peca'){
			$where_peca = " AND tbl_peca.referencia =  '".$_POST['peca']."'";
		}else{

			$where_peca = " AND tbl_peca.referencia = '" . $_POST["peca_referencia"] . "'";
		}

		$where_peca .= " and tbl_peca.fabrica in (11,172) ";	 
	 } else {
		 $where_peca = (empty($get_peca) || is_null($get_peca)) ? '' : 'AND tbl_peca.peca = '.$get_peca.'';
		 $where_peca .= " and tbl_peca.fabrica in ($login_fabrica) ";	 
	 }

	//************ENTRADA DE PEÇAS*************//
	$pr_sql = "SELECT  tbl_faturamento.nota_fiscal,tbl_faturamento.cfop,
					SUM (tbl_faturamento_item.qtde) AS qtde,
					SUM (tbl_faturamento_item.qtde_estoque) AS qtde_estoque,
					SUM (tbl_faturamento_item.qtde_quebrada) AS qtde_quebrada,
					TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY')as emissao,
					TO_CHAR(tbl_faturamento.conferencia,'DD/MM/YYYY')as conferencia,
					substr(tbl_posto.nome,1,30) as nome,
					tbl_peca.peca
			FROM tbl_faturamento
			JOIN tbl_faturamento_item using (faturamento)
			JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca
			JOIN tbl_posto ON tbl_faturamento.distribuidor = tbl_posto.posto
			WHERE  tbl_faturamento.posto in (4311,376542)
			AND (
				tbl_faturamento.distribuidor IN (
					SELECT DISTINCT distribuidor FROM tbl_faturamento WHERE fabrica = 10 AND posto in (4311,376542)
					and distribuidor is not null and distribuidor not in (4311,376542))
				OR
				tbl_faturamento.fabrica in ($login_fabrica)
				AND tbl_faturamento.distribuidor is null
			)
			$where_peca
			AND tbl_faturamento.cancelada IS NULL
			AND tbl_faturamento.fabrica <> 0
			AND (tbl_faturamento.tipo_nf = 0 or tbl_faturamento.tipo_nf IS NULL)
			GROUP BY tbl_faturamento.nota_fiscal,tbl_faturamento.cfop,tbl_faturamento.emissao, tbl_faturamento.conferencia,tbl_posto.nome,tbl_peca.peca
			ORDER BY tbl_faturamento.emissao,tbl_faturamento.nota_fiscal ASC";
			//die(var_dump($pr_sql));
	$pr_res = pg_exec ($con,$pr_sql);
	
	$total_qtde = 0;
	if (pg_num_rows($pr_res) > 0):

		if (!$xls): 
			$content .= '<table border="0" cellpadding="10"><tr>';
			$border   = '1';
		else:
			$border = '0';
		endif;

		$content .= "<td style='vertical-align: baseline !important;'> 
						<table align='center' border='".$border."'>
							<tr bgcolor='#0EB527' align='center'>
								<td colspan='7'><b>MOVIMENTO DE ENTRADA DE PEÇAS</b></td>
							</tr>
							<tr align='center'>
								<td><b>Distribuidor</b></td>
								<td><b>Nota Fiscal</b></td>
								<td><b>Emissão</b></td>
								<td><b>Conferencia</b></td>
								<td><b>Qtde</b></td>
								<td><b>Qtde Estoque</b></td>
								<td><b>Qtde Quebrada</b></td>
							</tr>";

		for ($i = 0 ; $i < pg_numrows ($pr_res) ; $i++) {
			$total_qtde += pg_result($pr_res, $i, qtde);
			$total_qtde_estoque += pg_result($pr_res, $i, qtde_estoque);;
			$total_qtde_quebrada += pg_result($pr_res, $i, qtde_quebrada);;

			//  bgcolor='#36E150'
			$content .= "<tr>
						<td title='Distribuidor'>
							" . pg_result ($pr_res,$i,nome) . "
						</td>
						<td title='Número da nota fiscal'>
							" . pg_result ($pr_res,$i,nota_fiscal) . "-" . pg_result ($pr_res,$i,cfop) . "
						</td>
						<td title='Data emissão'>
							" . pg_result ($pr_res,$i,emissao) . "
						</td>
						<td align='right' title='Data conferência'>&nbsp;
							" . pg_result ($pr_res,$i,conferencia) ."
						</td>";

			
			if ($qtde_fabrica < 0) $qtde_fabrica = 0;

			$content .= "<td title='Quantidade'>&nbsp;
							" . pg_result ($pr_res,$i,qtde) . "
						</td>
						<td title='Quantidade em Estoque'>&nbsp;
							" . pg_result ($pr_res,$i,qtde_estoque). "
						</td>
						<td title='Quantidade Quebrada'>&nbsp;
							" . pg_result ($pr_res,$i,qtde_quebrada). "
						</td>
					</tr>";
		}

		// bgcolor='#0EB527'
		$content .= "<tr align='center'>
						<td colspan='4'>TOTAIS</td>
						<td>$total_qtde</td> 
						<td style='vertical-align: baseline !important;'>$total_qtde_estoque</td>
						<td>$total_qtde_quebrada</td>
					</tr>
					</table> 
				</td>";
	else:
		$content .= "<td>
						<table align='center' border='".$border."'>
							<tr bgcolor='#0EB527' align='center'>
								<td colspan='7'><b>MOVIMENTO DE ENTRADA DE PEÇAS</b></td>
							</tr>
							<tr align='center'>
								<td colspan='7'><b>Não foi encontrado movimento de ENTRADA de Peças</b></td>
							</tr>
						</table>
					</td>";	
	endif;


	//************SAIDA DE PEÇAS*************//
	$ps_sql = "SELECT  
					tbl_faturamento.nota_fiscal,
					tbl_faturamento.cfop,
					tbl_faturamento.chave_nfe,
					TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao,
					tbl_tipo_pedido.descricao AS tipo_pedido_descricao,
					tbl_faturamento_item.preco,
					SUM(tbl_faturamento_item.qtde) AS qtde,
					tbl_posto.nome, 
					tbl_faturamento.natureza
			FROM tbl_faturamento
				JOIN tbl_faturamento_item using (faturamento)
				JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca
				JOIN tbl_posto ON tbl_posto.posto = tbl_faturamento.distribuidor
				LEFT JOIN tbl_pedido ON tbl_faturamento_item.pedido = tbl_pedido.pedido
				LEFT JOIN tbl_tipo_pedido ON tbl_faturamento.tipo_pedido=tbl_tipo_pedido.tipo_pedido
			WHERE tbl_faturamento.fabrica in (SELECT fabrica FROM tbl_fabrica WHERE JSON_FIELD('telecontrol_distrib', parametros_adicionais) = 't')
				AND tbl_faturamento.distribuidor in (4311,376542)
				 
				$where_peca
				AND (tbl_faturamento.status_nfe='100' or tbl_faturamento.status_nfe isnull)
			GROUP BY
				tbl_faturamento.nota_fiscal,
				tbl_faturamento.cfop,
				tbl_faturamento.chave_nfe,
				tbl_faturamento.emissao,
				tbl_tipo_pedido.descricao,
				tbl_faturamento_item.peca,
				tbl_faturamento_item.preco,
				tbl_posto.nome, 
				tbl_faturamento.natureza
			ORDER BY tbl_faturamento.emissao,tbl_faturamento.nota_fiscal ASC";

	$ps_res = pg_exec ($con,$ps_sql);

	if (pg_num_rows($ps_res) > 0):


		if (!$xls):
			$td_pdf   = '<td><b>PDF</b></td>';
			/*$td_preco = '<td><b>Preço</b></td>';
			$td_total = '<td><b>Total</b></td>';*/
			$colspan1 = '9';
			$colspan2 = '6';
		else:
			$td_pdf = '';
			/*$td_preco = '';
			$td_total = '';*/
			$colspan1 = '6';
			$colspan2 = '5';
		endif;

		$content .= "<td style='vertical-align: baseline !important;'> 
						<table align='center' border='".$border."' cellspacing='1' cellpaddin='1'>
							<tr bgcolor='#596D9B' align='center'>
								<td colspan='".$colspan1."'><b>MOVIMENTO DE SAÍDA DE PEÇAS</b></td>
							</tr>
							<tr align='center'>
								<td><b>Distribuidor</b></td>
								<td><b>OS</b></td>
								<td><b>Nota Fiscal</b></td>
								<td><b>Emissão</b></td>
								$td_pdf
								<td><b>Tipo Pedido</b></td>
								<td><b>Qtde</b></td>
							</tr>";

		$total_qtde = 0;
		$contadorPS = pg_num_rows ($ps_res);


		for ($i = 0 ; $i < $contadorPS; $i++) {
			$arr_nota_fiscal[] = trim(pg_fetch_result($ps_res, $i, 'nota_fiscal'));
			$total_qtde  += pg_result($ps_res, $i, qtde);
			$total_preco += pg_result($ps_res, $i, preco);
			$chave_nfe    = pg_fetch_result($ps_res, $i, 'chave_nfe');
			$nota_fiscal  = pg_fetch_result($ps_res, $i, 'nota_fiscal');
			$natureza     = pg_fetch_result($ps_res, $i, 'natureza');

			$sql_for_oss = "SELECT tbl_os.os, tbl_os.sua_os
                            FROM tbl_faturamento
	                            JOIN tbl_faturamento_item using (faturamento)
	                            JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca
	                            JOIN tbl_tipo_pedido ON tbl_faturamento.tipo_pedido=tbl_tipo_pedido.tipo_pedido
	                            LEFT JOIN tbl_os_item ON tbl_faturamento_item.os_item = tbl_os_item.os_item
	                            LEFT JOIN tbl_os_produto USING(os_produto)
	                            LEFT JOIN tbl_os  ON tbl_os_produto.os = tbl_os.os
                            WHERE tbl_faturamento.fabrica in (SELECT fabrica FROM tbl_fabrica WHERE JSON_FIELD('telecontrol_distrib', parametros_adicionais) = 't')
                            	AND tbl_faturamento.distribuidor in (4311,376542)
                            	$where_peca
                            	AND tbl_faturamento.nota_fiscal = $1
					    	UNION
							    SELECT tbl_os.os, tbl_os.sua_os
							    FROM tbl_faturamento
								    JOIN tbl_faturamento_item USING(faturamento)
		                            JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca
								    JOIN tbl_os USING(os)
							    WHERE tbl_faturamento.fabrica in (SELECT fabrica FROM tbl_fabrica WHERE JSON_FIELD('telecontrol_distrib', parametros_adicionais) = 't')
								    AND tbl_faturamento.distribuidor in (4311,376542)
								    AND tbl_faturamento.posto IS NULL
							    	$where_peca
							    	AND tbl_faturamento.nota_fiscal = $1";
//die(nl2br($sql_for_oss));
			$execute = pg_query_params($con, $sql_for_oss, array($nota_fiscal));
			echo pg_last_error();
			$oss     = null;
			$oss     = array();

			if (pg_num_rows($execute) > 0) {
			    while ($fetch = pg_fetch_assoc($execute)) {
					$oss[] = $fetch['sua_os'];
			    }
			}

			// <tr bgcolor='#D9E2EF'>
			$content .= "<tr>
							<td title='Data emissão'>
								". pg_result ($ps_res,$i,nome) . "
							</td>
							<td title='OS' align='center'>
								" . implode('<br/>', $oss) . "
							</td>
							<td title='Número da nota fiscal'>
								" . pg_result ($ps_res,$i,nota_fiscal) . "-" . pg_result ($ps_res,$i,cfop) . "
							</td>
							<td title='Data emissão'>" . pg_result ($ps_res,$i,emissao) . "</td>";
							

			if (!$xls):
				$content .= "<td title='Visualizar NF' align='center' style='height: 25px'>";
				
				if (!empty($chave_nfe) and file_exists("../nfephp2/arquivos/producao/pdf/$chave_nfe.pdf")) {
				    $content .= '<a target="_blank" href="http://ww2.telecontrol.com.br/assist/nfephp2/arquivos/producao/pdf/' . $chave_nfe . '.pdf">
				    		<img src="../admin/imagens/pdf_icone.gif" height="25" weight="25" />
				    	</a>';
				} else {
				    $content .= '&nbsp';
				}
				
				$content    .= "</td>";
			endif;

			$tipo_pedido = (count($oss) > 0 and !empty($oss[0]) ) ? 'Garantia' : pg_fetch_result($ps_res,$i,'tipo_pedido_descricao');
			$tipo_pedido = ($natureza == 'DEVOLUCAO') ? 'DEVOLUÇÃO' :  $tipo_pedido; 
			$content    .= "<td align='center' title='Tipo do pedido'>&nbsp;" . $tipo_pedido . "</td>";

			$qtde_fabrica = pg_result ($ps_res,$i,qtde);
			if ($qtde_fabrica < 0) $qtde_fabrica = 0;

			$qtde_item  = pg_result ($ps_res,$i,qtde);
			$preco      = pg_result ($ps_res,$i,preco);
			$total_item = $qtde_item * $preco ;
			$ttl_item  += $total_item;
			
			$content .= "<td title='Quantidade'>&nbsp;
								" . pg_result ($ps_res,$i,qtde) . "
							</td>";
			
/*			if (!$xls):
				$content .= "
							<td title='preco'>&nbsp;" . pg_result ($ps_res,$i,preco) . "</td>
							<td>$total_item</td>
						</tr>";
			else:
				$content .= "</tr>";
			endif; */

			$content .= "</tr>";
		}

		/*$show_total_saida = (!$xls) ? $show_total_saida = "<td>$total_preco</td> <td>$ttl_item</td>" : '';*/

		// bgcolor='#596D9B' 
		$content .= "<tr align='center'>
						<td colspan='".$colspan2."' align='center' style='padding-right: 10px;'>TOTAIS</td>
						<td>$total_qtde</td>
					</tr>
				</table> 
			</td>";
	else:
		$content .= "<td style='vertical-align: baseline !important;'>
						<table align='center' border='".$border."'>
							<tr bgcolor='#596D9B' align='center'>
								<td colspan='9'><b>MOVIMENTO DE SAÍDA DE PEÇAS</b></td>
							</tr>
							<tr align='center'>
								<td colspan='9'><b>Não foi encontrado movimento de SAÍDA de Peças</b></td>
							</tr>
						</table>
					</td>";
	endif;

	if (!$xls):
		$content .= "</tr>
				</table>
				<table border='0' cellpadding='10'>
					<tr>";
	endif;

	if(in_array($login_fabrica, array(11,172))){
	         if($_POST['btn_acao'] == 'get_movimentacao_peca'){
        	         $where_peca = " AND tbl_peca.referencia =  '".$_POST['peca']."'";
	         }else{
 
        	         $where_peca = " AND tbl_peca.referencia = '" . $_POST["peca_referencia"] . "'";
         	}
 
	         } else {
        	         $where_peca = (empty($get_peca) || is_null($get_peca)) ? '' : 'AND tbl_peca.peca = '.$get_peca.'';
         }


	//**************ACERTO************//
	$ac_sql = "SELECT  tbl_posto_estoque_acerto.posto_estoque_acerto,
					tbl_posto_estoque_acerto.qtde,
					tbl_posto_estoque_acerto.motivo,
					TO_CHAR(tbl_posto_estoque_acerto.data,'DD/MM/YYYY')AS data,
					tbl_peca.peca,
					tbl_posto.nome,
					tbl_login_unico.nome as login_unico_nome
			FROM tbl_posto_estoque_acerto
			JOIN tbl_posto USING (posto)
			JOIN tbl_peca ON tbl_peca.peca = tbl_posto_estoque_acerto.peca
			LEFT JOIN tbl_login_unico USING (login_unico) 
			WHERE tbl_peca.fabrica = $login_fabrica
			$where_peca
			AND motivo NOT LIKE 'Localização DE:%'
			ORDER BY tbl_posto_estoque_acerto.data ASC";
	$ac_res = pg_exec ($con,$ac_sql);

	if (pg_num_rows($ac_res) > 0):
		$content .= "<td> 
						<table align='center' border='".$border."' cellspacing='1' cellpaddin='1'>
							<tr bgcolor='#FF7643' align='center'>
								<td colspan='6'>MOVIMENTO DE ACERTO DE PEÇAS</td>
							</tr>
							<tr align='center'>
								<td>Distribuidor</td>
								<td>Registro</td>
								<td>Qtde</td>
								<td>Motivo</td>
								<td>Data</td>
								<td>Admin</td>
							</tr>";

		$total_qtde = 0;

		for ($i = 0 ; $i < pg_numrows ($ac_res) ; $i++) {

			$nome             = pg_fetch_result($ac_res, $i, nome);
			$login_unico_nome = pg_fetch_result($ac_res, $i, login_unico_nome);
			$motivo           = pg_fetch_result($ac_res, $i, motivo);
			$peca             = pg_fetch_result($ac_res, $i, peca);
			$exp_motivo       = explode(" ", $motivo);
			$arr_motivo       = array_map("clean", $exp_motivo);
			$k                = array_search("OS", $arr_motivo);

			if ($k !== false) {
				$v_os = $arr_motivo[$k + 1];
				$pexec = pg_execute($con, "check_os", array($v_os, $peca));
				if (pg_num_rows($pexec) > 0) {
					$vnf = trim(pg_fetch_result($pexec, 0, 'nota_fiscal'));
					if (in_array($vnf, $arr_nota_fiscal)) {
						continue;
					}
				}
			}

			$total_qtde += pg_result($ac_res, $i, qtde);
			// bgcolor='#FFB89D'
			$content    .= "<tr>
							<td align='center' title='Quantidade'>&nbsp;" . $nome . "</td>
							<td align='center' title='Código da peça'>&nbsp;
								" . pg_result ($ac_res,$i,posto_estoque_acerto) . "
							</td>";

			$qtde_fabrica = pg_result ($ac_res,$i,qtde);
			if ($qtde_fabrica < 0) $qtde_fabrica = 0;

			$content .= "<td title='Quantidade'>&nbsp;" . pg_result ($ac_res,$i,qtde) . "</td>
						<td align='left' title='Motivo'>&nbsp;" . $motivo . "</td>
						<td align='center' title='Data'>&nbsp;" . pg_result ($ac_res,$i,data) . "</td>
						<td align='center' title='Admin'>&nbsp;" . $login_unico_nome . "</td>
					</tr>";
		}

		// bgcolor='#FF7643' 
		$content .= "<tr align='center'>
						<td colspan='2'>TOTAIS</td>
						<td>$total_qtde</td>
						<td></td>
						<td></td>
						<td></td>
					</tr>
				</table> 
			</td>";
	else:
		$content .= "<td style='vertical-align: baseline !important;'>
						<table align='center' border='".$border."'>
							<tr bgcolor='#FF7643' align='center'>
								<td colspan='7'><b>MOVIMENTO ACERTO</b></td>
							</tr>
							<tr align='center'>
								<td colspan='7'><b>Não foi encontrado movimento de ACERTO de Peças</b></td>
							</tr>
						</table>
					</td>";
	endif;

	if (!$xls):
		$content .= "</tr></table>";
	endif;

	return $content;
}

include "rodape.php";

?>

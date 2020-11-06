<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'includes/funcoes.php';
	include "autentica_admin.php";
?>

<?php 
	//validações
	if(isset($_POST['gerar']) && strlen($_POST['gerar']) > 0) {

		$data_inicial	 = $_POST['data_inicial'];
		$data_final		 = $_POST['data_final'];
		$situacao		 = $_POST['situacao_os'];
		$estado			 = $_POST['estado'];
		$prod_referencia = $_POST['produto_referencia'];
		$ref_posto		 = $_POST['codigo_posto'];

		if( strlen($data_inicial) > 0 && strlen($data_final) > 0 ) {

			list($di, $mi, $yi) = explode("/", $data_inicial);
			if(!checkdate($mi,$di,$yi)) {
				$msg_erro["msg"][]    = "Data Inválida";
				$msg_erro["campos"][] = "data";
			}  
				
			list($df, $mf, $yf) = explode("/", $data_final);
			if( !checkdate($mf,$df,$yf) ) {
				$msg_erro["msg"][]    = "Data Inválida";
				$msg_erro["campos"][] = "data";
			} 

			if(strlen($msg_erro)==0){
				$aux_data_inicial = "$yi-$mi-$di";
				$aux_data_final = "$yf-$mf-$df";
			}

			if(strlen($msg_erro)==0){
				if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
					$msg_erro["msg"][]    = "Data Inválida";
					$msg_erro["campos"][] = "data";
				}
			}

			if( strlen($msg_erro) == 0 ) {

				$data_inicial = implode("-", array_reverse(explode("/", $data_inicial)));
				$data_final   = implode("-", array_reverse(explode("/", $data_final)));

				if( $_POST['filtro_data'] == 'abertura' )
					$campo = 'data_abertura';
				else if( $_POST['filtro_data'] == 'digitacao' ) {
					$campo			= 'data_digitacao';
					$data_inicial	= $data_inicial . ' 00:00:00';
					$data_final		= $data_final . ' 23:59:59';
				}
				else
					$campo = 'data_fechamento';


				if( $data_inicial != $data_final )
					$cond_data = "AND tbl_os.".$campo." BETWEEN '".$data_inicial."' AND '".$data_final."'";
				else
					$cond_data = "AND tbl_os.".$campo." = '".$data_inicial."'";
			}

		} else {
			$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
			$msg_erro["campos"][] = "data";
		}

		if($situacao_os == 'finalizada')
				$cond_situacao = 'AND tbl_os.finalizada IS NOT NULL';

		if( strlen($ref_posto) > 0 ) {

			$sql_posto = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '".$ref_posto."'";
			$res_posto = pg_query($con,$sql_posto);
			if( pg_num_rows($res_posto) ) {
				$cod_posto = pg_fetch_result($res_posto,0,posto);
				$cond_posto  = ' AND tbl_os.posto = ' . $cod_posto . '';
			} else {
				$msg_erro["msg"][]    = "Posto não encontrado";
				$msg_erro["campos"][] = "posto";
			}

		}
		else
			$cond_posto = 'AND tbl_os.posto NOT IN (6359)';

		if( strlen($prod_referencia) > 0 ) {

			$sql_prod = "SELECT produto 
						 FROM tbl_produto 
						 JOIN tbl_linha USING (linha)
						 WHERE referencia = '".$prod_referencia."'
						 AND fabrica = ".$login_fabrica."";
			$res_prod = pg_query($con,$sql_prod);

			if( pg_num_rows($res_prod) > 0 ) {

				$produto	= pg_fetch_result($res_prod, 0, 'produto');
				$cond_prod	= 'AND tbl_os.produto = '.$produto;

			} else {
				$msg_erro["msg"][]    = "Produto não encontrado";
				$msg_erro["campos"][] = "produto";
			}

		}

		if(strlen($estado)>0)
			$cond_regiao = "AND tbl_posto.estado = '".$estado."'";

		if (count($msg_erro) == 0) {
			$sql = "
				SELECT	serie,
				tbl_produto.referencia || '-' || tbl_produto.descricao as produto,
			        tbl_os.defeito_reclamado_descricao,	
				tbl_defeito_constatado.descricao, 
				tbl_os.sua_os, 
				tbl_os.os, 
				tbl_posto.nome, 
				tbl_os.mao_de_obra,
				tbl_os_extra.taxa_visita,
				qtde_km_calculada AS valor_km,			
				pecas
				FROM tbl_os
				JOIN tbl_produto USING(produto)
				JOIN tbl_defeito_constatado USING(defeito_constatado)
				JOIN tbl_posto USING(posto)
				JOIN tbl_os_extra USING (os)
				JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = ".$login_fabrica."
				WHERE tbl_os.fabrica = ".$login_fabrica."
				$cond_posto
				$cond_data
				$cond_situacao
				$cond_regiao
				$cond_prod
				AND tbl_os.excluida IS NOT NULL
			";

			//die(nl2br($sql));

			$res_pesquisa = pg_query($con, $sql);
			$contador_res_pesquisa = pg_num_rows($res_pesquisa);

			if ($_POST["gerar_excel"]) {

				if (pg_num_rows($res)>0) {
					$data = date("d-m-Y-H-i");
					$fileName = "relatorio_os_serie_custo_{$data}.csv";
					$file = fopen("/tmp/{$fileName}", "w");

					if($login_fabrica == 90){
						$head = "Nº Série;Descrição;Defeito Reclamado;Defeito Constatado;Nº da OS;Posto;Custo M.O;Custo Peças;Custo KM;M.O + Peças + KM;\r\n";
					} else {
						$head = "Nº Série;Descrição;Defeito Reclamado;Defeito Constatado;Nº da OS;Posto;Custo M.O;Custo Peças;M.O + Peças;\r\n";
					}

					fwrite($file, $head);
					$body = '';


					for($i = 0; $i < $contador_res_pesquisa; $i++) {
						$serie			= pg_result($res_pesquisa,$i,'serie');
						$produto		= pg_result($res_pesquisa,$i,'produto');
						$def_reclamado	= pg_result($res_pesquisa,$i,'defeito_reclamado_descricao');
						$def_descricao	= pg_result($res_pesquisa,$i,'descricao');
						$os				= pg_result($res_pesquisa,$i,'os');
						$sua_os			= pg_fetch_result($res_pesquisa,$i,'sua_os');
						$posto_nome		= pg_result($res_pesquisa,$i,'nome');
						$mao_de_obra		= pg_result($res_pesquisa,$i,'mao_de_obra');
						$taxa_visita	= pg_result($res_pesquisa,$i,'taxa_visita');
						$peca			= pg_result($res_pesquisa,$i,'pecas');
						$valor_km		= pg_result($res_pesquisa,$i,'valor_km');

						$mao_de_obra = !is_null($mao_de_obra) ? $mao_de_obra : 0;
						
						$total_peca     += $peca;						

						if($login_fabrica == 90){
							$mao_de_obra = $mao_de_obra + $taxa_visita;
							$total_valor_km += $valor_km;
							$total = $peca + $mao_de_obra + $valor_km;
							$total_mo += $total;
						
							$body .= $serie.";".$produto.";".$def_reclamado.";".$def_descricao.";".$sua_os.";".$posto_nome.";".number_format($mao_de_obra,2,',','.').";".number_format($peca,2,',','.').";".number_format($valor_km,2,',','.').";".number_format($total,2,',','.');
						} else {
							$mao_de_obra = $mao_de_obra + $taxa_visita + $valor_km;
							$total = $peca + $mao_de_obra;

							$body .= $serie.";".$produto.";".$def_reclamado.";".$def_descricao.";".$sua_os.";".$posto_nome.";".number_format($mao_de_obra,2,',','.').";".number_format($peca,2,',','.').";".number_format($total,2,',','.');							
						}

						$total_mao_de_obra += $mao_de_obra;
						$body .= "\r\n";
					}

					if($login_fabrica == 90){
						$body .= "; ; ; ; ;Total ;".number_format($total_mao_de_obra,2,',','.').";".number_format($total_peca,2,',','.').";".number_format($total_valor_km,2,',','.').";".number_format($total_mo,2,',','.'); 
					} else {
						$body .= "; ; ; ; ;Total ;".number_format($total_mao_de_obra,2,',','.').";".number_format($total_peca,2,',','.')."; "; 
					}
				
					$body = $body;
				    fwrite($file, $body);
				    fclose($file);
				    if (file_exists("/tmp/{$fileName}")) {

		                system("mv /tmp/{$fileName} xls/{$fileName}");

		                echo "xls/{$fileName}";
					}
				}
				exit;
			}
		}

	}

	$layout_menu = "gerencia";
	$title = "RELATÓRIO DE OS - CUSTO - SÉRIE";

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
<script type="text/javascript">
	$(function(){
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("produto", "posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function() {
			$.lupa($(this));
		});
	});

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
    }

    function retorna_produto (retorno) {
		$("#produto_referencia").val(retorno.referencia);
		$("#produto_descricao").val(retorno.descricao);
	}
</script>
	<?php
	if (count($msg_erro["msg"]) > 0) {
	?>
	    <div class="alert alert-error">
			<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
	    </div>
	<?php
	}
	?>
	<div class="row">
		<b class="obrigatorio pull-right"> * Campos obrigatórios </b>
	</div>
	<form action="<?=$_SERVER['PHP_SELF'];?>" method="POST" name="frm_busca" class='form-search form-inline tc_formulario'>
		<div class="titulo_tabela">Parâmetros de Pesquisa</div>
		<br />
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Data Inicial</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="data_inicial" class="span12" id="data_inicial" value="<?=isset($_POST['data_inicial'])?$_POST['data_inicial'] : '' ?>" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_final'>Data Final</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="data_final" class="span12" id="data_final" value="<?=isset($_POST['data_final'])?$_POST['data_final'] : ''?>"/>
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<br />
		<div class='row-fluid'>
			<div class='row'>
				<div class='span2'></div>
				<div class='span3'>
					<strong>Filtrar pela Data</strong>
				</div>
			</div>
			<div class="row">
				<div class='span2'></div>	
				<div class='span3'>
					 <label class="radio">
				        <input type="radio" name="filtro_data" value="abertura" id="abertura" 
						<?php if($_POST['filtro_data'] == 'abertura' || strlen($_POST['abertura'] ==0 ) ) echo 'checked'; ?>/>
						Abertura da OS
				    </label>
				</div>
				<div class='span3'>
				    <label class="radio">
				        <input type="radio" name="filtro_data" value="digitacao" id="digitacao" 
						<?php if($_POST['filtro_data'] == 'digitacao' ) echo 'checked'; ?> />
						Digitação da OS
				    </label>
				</div>
				<div class='span3'>
				    <label class="radio">
				        <input type="radio" name="filtro_data" value="finaliza" id="finaliza" 
						<?php if($_POST['filtro_data'] == 'finaliza' ) echo 'checked'; ?> />
						Finalização da OS
				    </label>
				</div>
			</div>
			<div class='span1'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_referencia'>Ref. Produto</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" id="produto_referencia" name="produto_referencia" class="span12" maxlength="20" value="<? echo $prod_referencia ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_descricao'>Descrição Produto</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" id="produto_descricao" name="produto_descricao" class="span12" value="<? echo $_POST['produto_descricao'] ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'>Código Posto</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="codigo_posto" class="span12" id="codigo_posto" size="10" value="<? echo $ref_posto ?>" >
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
							<input type="text" name="posto_nome" class="span12" id="descricao_posto" size="30" value="<? echo $_POST['posto_nome'] ?>">
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'>Estado</label>
					<div class='controls controls-row'>
						<select name="estado" id="estado" style="width:130px; font-size:9px" class="frm">
							<option value="" <?php if (strlen($estado) == 0) echo " selected ";?> >TODOS OS ESTADOS</option>
							<option value="AC" <?php if ($estado == "AC") echo " selected "; ?>>AC - Acre</option>
							<option value="AL" <?php if ($estado == "AL") echo " selected "; ?>>AL - Alagoas</option>
							<option value="AM" <?php if ($estado == "AM") echo " selected "; ?>>AM - Amazonas</option>
							<option value="AP" <?php if ($estado == "AP") echo " selected "; ?>>AP - Amapá</option>
							<option value="BA" <?php if ($estado == "BA") echo " selected "; ?>>BA - Bahia</option>
							<option value="CE" <?php if ($estado == "CE") echo " selected "; ?>>CE - Ceará</option>
							<option value="DF" <?php if ($estado == "DF") echo " selected "; ?>>DF - Distrito Federal</option>
							<option value="ES" <?php if ($estado == "ES") echo " selected "; ?>>ES - Espírito Santo</option>
							<option value="GO" <?php if ($estado == "GO") echo " selected "; ?>>GO - Goiás</option>
							<option value="MA" <?php if ($estado == "MA") echo " selected "; ?>>MA - Maranhão</option>
							<option value="MG" <?php if ($estado == "MG") echo " selected "; ?>>MG - Minas Gerais</option>
							<option value="MS" <?php if ($estado == "MS") echo " selected "; ?>>MS - Mato Grosso do Sul</option>
							<option value="MT" <?php if ($estado == "MT") echo " selected "; ?>>MT - Mato Grosso</option>
							<option value="PA" <?php if ($estado == "PA") echo " selected "; ?>>PA - Pará</option>
							<option value="PB" <?php if ($estado == "PB") echo " selected "; ?>>PB - Paraíba</option>
							<option value="PE" <?php if ($estado == "PE") echo " selected "; ?>>PE - Pernambuco</option>
							<option value="PI" <?php if ($estado == "PI") echo " selected "; ?>>PI - Piauí</option>
							<option value="PR" <?php if ($estado == "PR") echo " selected "; ?>>PR - Paraná</option>
							<option value="RJ" <?php if ($estado == "RJ") echo " selected "; ?>>RJ - Rio de Janeiro</option>
							<option value="RN" <?php if ($estado == "RN") echo " selected "; ?>>RN - Rio Grande do Norte</option>
							<option value="RO" <?php if ($estado == "RO") echo " selected "; ?>>RO - Rondônia</option>
							<option value="RR" <?php if ($estado == "RR") echo " selected "; ?>>RR - Roraima</option>
							<option value="RS" <?php if ($estado == "RS") echo " selected "; ?>>RS - Rio Grande do Sul</option>
							<option value="SC" <?php if ($estado == "SC") echo " selected "; ?>>SC - Santa Catarina</option>
							<option value="SE" <?php if ($estado == "SE") echo " selected "; ?>>SE - Sergipe</option>
							<option value="SP" <?php if ($estado == "SP") echo " selected "; ?>>SP - São Paulo</option>
							<option value="TO" <?php if ($estado == "TO") echo " selected "; ?>>TO - Tocantins</option>
						</select>
					</div>
				</div>
			</div>
			<div class="span4">
				<div class="row">
					<strong>Situação da OS</strong>
				</div>	
				<div class="row">
					<div class='span6'>
					    <label class="radio">
					        <input type="radio" name="situacao_os" value="finalizada" id="finalizada" <? echo $situacao == 'finalizada' ? 'checked' : '' ?>/>
							Finalizada
					    </label>
					</div>
					<div class='span6'>
					    <label class="radio">
					        <input type="radio" name="situacao_os" value="todas" id="todas" <? echo $situacao == 'todas' ? 'checked' : '' ?> />
							Todas
					    </label>
					</div>
				</div>
			</div>
		</div>
		<br />
		<input type="submit" class="btn" name="gerar" value="Pesquisar" />
		<br /><br />
	</form>
<br />
<!-- resultado da requisição -->
<?php 
	if (isset($_POST['gerar']) && count($msg_erro) == 0) {

		/* Fim da paginacao */

		if(pg_num_rows($res_pesquisa) > 0) { //exibe os dados
?>
		</div>
			<table id="tabela" class="table table-bordered tabled-large table-striped">
				<thead>
					<tr class="titulo_coluna">
						<th>Nº Série</th>
						<th>Descrição</th>
						<th>Defeito Reclamado</th>
						<th>Defeito Constatado</th>
						<th>Nº da OS</th>
						<th>Posto</th>	
						<th align="center" class="money_column">Custo M.O  </th>
						<th align="center" class="money_column">Custo Peças</th>
						<? if($login_fabrica == 90) { ?>
							<th align="center" class="money_column">Custo KM</th>
							<th align="center" class="money_column">M.O + Peças + KM</th>
						<? } else { ?>
							<th align="center" class="money_column">M.O + Peças</th>
						<? } ?>
					</tr>
				</thead>
				<tbody>
				<?php
					//loop results					
					for($x = 0; $x < $contador_res_pesquisa; $x++) {

						$serie			= pg_fetch_result($res_pesquisa,$x,'serie');
						$produto		= pg_fetch_result($res_pesquisa,$x,'produto');
						$def_reclamado	= pg_fetch_result($res_pesquisa,$x,'defeito_reclamado_descricao');
						$def_descricao	= pg_fetch_result($res_pesquisa,$x,'descricao');
						$os				= pg_fetch_result($res_pesquisa,$x,'os');
						$sua_os			= pg_fetch_result($res_pesquisa,$x,'sua_os');
						$posto_nome		= pg_fetch_result($res_pesquisa,$x,'nome');
						$mao_de_obra		= pg_fetch_result($res_pesquisa,$x,'mao_de_obra');
						$taxa_visita		= pg_fetch_result($res_pesquisa,$x,'taxa_visita');
						$peca			= pg_fetch_result($res_pesquisa,$x,'pecas');
						$valor_km		= pg_fetch_result($res_pesquisa,$x,'valor_km');


						$mao_de_obra = !is_null($mao_de_obra) ? $mao_de_obra : 0;
						
						$total_mao_de_obra += $mao_de_obra;
						$total_peca     += $peca;

						if($login_fabrica == 90){
							$mao_de_obra = $mao_de_obra + $taxa_visita;
							$total = $peca + $mao_de_obra + $valor_km;	
							$col_custo_km = "<td align='right'>".number_format($valor_km,2,',','.')."</td>";
						} else {
							$mao_de_obra = $mao_de_obra + $taxa_visita + $valor_km;
							$total = $peca + $mao_de_obra ;	
						}						
						
						$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

						echo '
						<tr bgcolor='.$cor.'>
							<td>'.$serie.'</td>
							<td>'.$produto.'</td>
							<td>'.$def_reclamado.'</td>
							<td>'.$def_descricao.'</td>
							<td><a href="os_press.php?os='.$os.'" target="_blank">'.$sua_os.'</a></td>
							<td>'.$posto_nome.'</td>
							<td align="right">'.number_format($mao_de_obra,2,',','.').'</td>
							<td align="right">'.number_format($peca,2,',','.').'</td>'.
							$col_custo_km
							.'<td align="right">'.number_format($total,2,',','.').'</td>
						</tr>
						';
					} 
					
				?>
				
				</tbody>
			</table>
			<br />
			<script>
			jQuery.extend(jQuery.fn.dataTableExt.oSort, {
		        "currency-pre": function (a) {
		            a = (a === "-") ? 0 : a.replace(/[^\d\-\.]/g, "");
		            return parseFloat(a);
		        },
		        "currency-asc": function (a, b) {
		            return a - b;
		        },
		        "currency-desc": function (a, b) {
		            return b - a;
		        }
		    });

	        var tds = $('#tabela').find(".titulo_coluna");
	        var colunas = [];
	        $(tds).find("th").each(function(){
	            if ($(this).attr("class") == "date_column") {
	                colunas.push({"sType":"date"});
	            }if ($(this).attr("class") == "money_column") {
	                colunas.push({"sType":"numeric"});
	            } else {
	                colunas.push(null);
	            }
	        });

			$.dataTableLoad({ table: "#tabela",aoColumns:colunas });
			</script>
			<?php
				$jsonPOST = excelPostToJson($_POST);
			?>

			<div id='gerar_excel' class="btn_excel">
				<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
				<span><img src='imagens/excel.png' /></span>
				<span class="txt">Gerar Excel</span>
			</div>
		<?php	
		} else { ?>
			<div class="alert alert-warning"><h4>Não Foram Encontrados Resultados para esta Pesquisa</h4></div>
		<?php
		}

	}
include 'rodape.php'; ?>

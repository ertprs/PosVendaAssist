<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$admin_privilegios	= "gerencia";
$layout_menu 		= "gerencia";
$title 				= "RELATÓRIO DE RASTREABILIDADE";

include "cabecalho_new.php";
$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
	);

include("plugin_loader.php");

$msg_erro = array();
$msgErrorPattern01 = "Preencha os campos obrigatórios corretamente.";
$msgErrorPattern02 = "A data de consulta deve ser no máximo de 1 meses.";

if ($_POST['btn_pesquisar'] == "Pesquisar"){
	//Inicio validacao Data
	$data_inicial = $_POST['data_inicial'];
	$data_final   = $_POST['data_final'];

	if(!$data_inicial OR !$data_final){
		$msg_erro["msg"][]    = $msgErrorPattern01;
		$msg_erro["campos"][] = "data";
	}
	if(strlen($msg_erro)==0){
		list($di, $mi, $yi) = explode("/", $data_inicial);
		if(!checkdate($mi,$di,$yi)){
			$msg_erro["msg"][]    = $msgErrorPattern01;
			$msg_erro["campos"][] = "data";
		}
	}

	if(strlen($msg_erro)==0){
		list($df, $mf, $yf) = explode("/", $data_final);
		if(!checkdate($mf,$df,$yf))		{
			$msg_erro["msg"][]    = $msgErrorPattern01;
			$msg_erro["campos"][] = "data";
		}
	}

	if(strlen($msg_erro)==0){
		$aux_data_inicial = $yi."-".$mi."-".$di;
		$aux_data_final = "$yf-$mf-$df";
	}

	if(strlen($msg_erro)==0)	{
		if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
			$msg_erro["msg"][]    = $msgErrorPattern01;
			$msg_erro["campos"][] = "data";
		}
	}

	if(strlen($msg_erro)==0)	{
		if (strtotime($aux_data_inicial) < strtotime($aux_data_final . ' -3 month')){
			$msg_erro["msg"][]    = $msgErrorPattern02;
			$msg_erro["campos"][] = "data";
		}
	}
	//Fim validação Data
	//inicio validacao CNPJ
	$revenda_referencia = $_POST['revenda_referencia'];
	if (strlen($revenda_referencia)>0) {
		$retira = array("-",".","/");
		$revenda_referencia = str_replace($retira, "", $revenda_referencia);
		//echo strlen($revenda_referencia);
		//$res_cpf = @pg_query($con,"SELECT fn_valida_cnpj_cpf('$revenda_referencia')");
		//if ($res_cpf === false) {
		if (strlen($revenda_referencia) != 14) {
			//echo "erro";
			$msg_erro["msg"][]    = "CNPJ Inválido";
			$msg_erro["campos"][] = "cnpj";
		}
	}
	//Fim validacao CNPJ
	// echo "<pre>";
	// print_r($_POST);
	// echo "</pre>";
	if(count($msg_erro["msg"]) == 0){
		$somente_peca = true;

		$familia_p = $_POST['familia'];
		if (strlen($familia_p)>0) {

			$query_familia = " AND tbl_produto.familia = $familia_p ";
			//$somente_peca = false;
		}else{

			$query_familia = '';
		}

		$fornecedor_p = $_POST['fornecedor'];
		if (strlen($fornecedor_p)>0) {

			$query_fornecedor = " AND tbl_ns_fornecedor_peca.nome = '$fornecedor_p' ";
			$join_fornecedor = "LEFT JOIN tbl_ns_fornecedor ON tbl_numero_serie.numero_serie = tbl_ns_fornecedor.numero_serie
									and tbl_ns_fornecedor.fabrica = $login_fabrica
							LEFT JOIN tbl_ns_fornecedor_peca ON tbl_ns_fornecedor.ns_fornecedor_peca = tbl_ns_fornecedor_peca.ns_fornecedor_peca and tbl_ns_fornecedor_peca.fabrica = $login_fabrica";
			$somente_peca = false;
		}else{

			$query_fornecedor = "";
				$join_fornecedor = "LEFT JOIN tbl_ns_fornecedor ON tbl_numero_serie.numero_serie = tbl_ns_fornecedor.numero_serie
									 and tbl_ns_fornecedor.fabrica = $login_fabrica
							LEFT JOIN tbl_ns_fornecedor_peca ON tbl_ns_fornecedor.ns_fornecedor_peca = tbl_ns_fornecedor_peca.ns_fornecedor_peca and tbl_ns_fornecedor_peca.fabrica = $login_fabrica";
		}

		if (strlen($revenda_referencia) > 0) {
			$query_revenda = " AND tbl_numero_serie.cnpj = '$revenda_referencia' ";
		}else{
			$query_revenda = "";
			$join_revenda = "";
		}

		$produto_p = $_POST['produto'];
		if (count($produto_p) > 0) {
			for ($y=0; $y < count($produto_p) ; $y++) {
				$sql_p = "SELECT produto FROM tbl_produto WHERE fabrica_i = $login_fabrica AND referencia = '$produto_p[$y]';";
				$res_p = pg_query($con,$sql_p);
				if (pg_num_rows($res_p) > 0) {
					$produto_p[$y] = pg_fetch_result($res_p, 0, produto);
				}
			}
			$produto_p = implode(",",  $produto_p);

		 	$query_produto = " AND tbl_numero_serie.produto in ($produto_p) ";
		 	$somente_peca = false;
		}else{
			$query_produto = "";
		}

		$peca_p = $_POST['peca'];
		if (count($peca_p) > 0) {
			for ($y=0; $y < count($peca_p) ; $y++) {
				$sql_peca = "SELECT peca FROM tbl_peca WHERE fabrica = $login_fabrica AND referencia = '$peca_p[$y]';";
				$res_peca = pg_query($con,$sql_peca);
				if (pg_num_rows($res_peca) > 0) {
					$peca_p[$y] = pg_fetch_result($res_peca, 0, peca);
				}
			}
			$peca_p = implode(",",  $peca_p);

		 	$query_peca = " AND tbl_ns_fornecedor.peca in ($peca_p) ";

		}else{
			$query_peca = "";
			$join_peca = "";
			//$somente_peca = false;
		}

		if ($somente_peca === false) {
			$sql_q = "SELECT 	tbl_revenda.nome,
								tbl_revenda.cnpj,
								count(distinct serie) as total_produto
						FROM tbl_numero_serie
						JOIN tbl_revenda on tbl_numero_serie.cnpj = tbl_revenda.cnpj
						$join_peca
						$join_fornecedor
						JOIN tbl_produto ON tbl_numero_serie.produto = tbl_produto.produto
						WHERE tbl_numero_serie.fabrica = $login_fabrica
						AND tbl_numero_serie.data_venda between '$aux_data_inicial' AND '$aux_data_final'
						$query_fornecedor
						$query_familia
						$query_revenda
						$query_produto
						$query_peca
						GROUP BY tbl_revenda.cnpj, tbl_revenda.nome;";
		}else{
			$sql_q = "SELECT 	tbl_ns_fornecedor_peca.nome as nome_fornecedor,
								tbl_produto.produto,
								tbl_produto.referencia,
								tbl_produto.descricao,
								tbl_ns_fornecedor_peca.nome,
								count(distinct serie) as qtd_prod
						  	FROM tbl_numero_serie 
							$join_fornecedor
						  	JOIN tbl_produto on tbl_produto.produto = tbl_numero_serie.produto
						WHERE tbl_numero_serie.fabrica = $login_fabrica
						AND tbl_numero_serie.data_venda BETWEEN '$aux_data_inicial' AND '$aux_data_final'
						$query_peca
						$query_familia
						$query_revenda
						$query_produto
						$query_fornecedor
						GROUP BY 	tbl_ns_fornecedor_peca.nome,
								 	tbl_produto.produto,
									tbl_produto.referencia,
									tbl_produto.descricao,
									tbl_ns_fornecedor_peca.nome
						ORDER BY 4 DESC;";
		}
		$res_q = pg_query($con,$sql_q);
		//echo pg_last_error($con);
	}
}

?>
<script type="text/javascript" charset="utf-8">

	$(function() {
		$.dataTableLoad();
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("produto", "peca", "posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {	$.lupa($(this));});
		$("#revenda_referencia").mask("99.999.999/9999-99");


		$.dataTableLoad({
			table: "#gridRelatorioPosto"
	 	});

	 	$('#add_produto').click(function(){
            var produto = $('#produto_descricao').val();
            var produto_id = $("#produto_referencia").val();

            if (produto_id && produto) {
                var option = '<option value="' + produto + '" class="' + produto_id + '">'+ produto_id+' - '+ produto + '</option>';
                var hidden = '<input type="hidden" name="produto[]" id="' + produto_id + '" value="' + produto_id + '" />';
                $('#produtos').append(option);
                $('#produtos').append(hidden);
                $("#produto_descricao").val('');
                $("#produto_referencia").val('');
            }else{
            	alert("Favor preencha os campos Referência e Descrição");
            }
        });

        $('#rm_produto').click(function(){
            $("select[name=produtos] option:selected").each(function () {
                var hidden = $(this).attr("class");

                $(this).remove();
                $('input[value="'+ hidden +'"]').remove();
            });
        });

        $('#add_peca').click(function(){
            var peca = $('#peca_descricao').val();
            var peca_id = $("#peca_referencia").val();

            if (peca_id && peca) {
            	var option = '<option value="' + peca + '" class="' + peca_id + '">' + peca_id + ' - ' + peca + '</option>';
                var hidden = '<input type="hidden" name="peca[]" id="' + peca_id + '" value="' + peca_id + '" />';
                $('#pecas').append(option);
                $('#pecas').append(hidden);
                $("#peca_referencia").val('');
                $("#peca_descricao").val('');
            }else{
            	alert("Favor preencha os campos Referência e Descrição");
            }
        });

        $('#rm_peca').click(function(){
            $("select[name=pecas] option:selected").each(function () {
                var hidden = $(this).attr("class");
                $(this).remove();
                $('input[value="'+ hidden +'"]').remove();
            });
        });
	});

	function rastreabilidade(data_in, data_f, cnpj, familia, fornecedor, posto, produto, peca) {

        if (!fornecedor) {
            fornecedor = '';
        }

        if (!data_in) {
            data_in = '';
        }

        if (!data_f) {
            data_f = '';
        }

        if (!familia) {
            familia = '';
        }

        if (!produto) {
            produto = '';
        }

        if (!peca) {
            peca = '';
        }

        if (!posto) {
            posto = '';
        }

        if (!cnpj) {
            cnpj = '';
        }

		var url="rastreabilidade_revenda.php?dti=" + data_in + "&dtf=" + data_f + "&fon=" + fornecedor + "&fa=" + familia + "&pro=" + produto + "&pec=" + peca + "&pos=" + posto + "&cnpj=" + cnpj;


		window.open (url, "rastreabilidade_revenda", "height=320,width=640,scrollbars=1");
	}

	function retorna_posto(retorno){
		$("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
	}
	function retorna_produto (retorno) {
		$("#produto_referencia").val(retorno.referencia);
		$("#produto_descricao").val(retorno.descricao);
	}
	function retorna_peca (retorno) {
		$("#peca_referencia").val(retorno.referencia);
		$("#peca_descricao").val(retorno.descricao);
	}
	function retorna_revenda (retorno) {
		$("#revenda_referencia").val(retorno.cnpj);
		$("#revenda_descricao").val(retorno.razao);
	}

</script>

<?php if (count($msg_erro["msg"]) > 0) {	?>
    <div class="alert alert-error"> <h4><?php echo $msg_erro["msg"][0]; ?></h4> </div>
<?php 	}	?>

<div class="row"> <b class="obrigatorio pull-right">  * Campos obrigatórios </b> </div>
<form name='frm_relatorio' method='post' id='condicoes_cadastradas' action="<?=$PHP_SELF?>" align='center' class="form-search form-inline tc_formulario">
	<div class="titulo_tabela">Parâmetros de Pesquisa</div>
	<br />
	<div class="container tc_container">

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Data Inicial</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
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
							<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for="familia">Família</label>
					<div class='controls controls-row'>
						<select name="familia" id="familia" class='span12'>
							<option value=""></option>
							<?php
							$qry_familias = pg_query($con, "SELECT familia, descricao FROM tbl_familia WHERE fabrica = $login_fabrica AND ativo = 't' ORDER BY descricao");
							if (pg_num_rows($qry_familias) > 0) {
								while ($fetch = pg_fetch_assoc($qry_familias)) {
									echo '<option value="' , $fetch['familia'] , '"';
									if ($familia == $fetch['familia']) {
										echo ' SELECTED="SELECTED"';
									}
									echo '>' , $fetch['descricao'] , '</option>';
								}
							}
							?>
						</select>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for="fornecedor">Fornecedor</label>
					<div class='controls controls-row'>
						<select name="fornecedor" id="fornecedor" class='span12'>
							<option value=""></option>
							<?php
							$qry_fornecedores = pg_query($con, "SELECT distinct(nome_fornecedor) as fornecedor, nome_fornecedor as descricao FROM tbl_ns_fornecedor WHERE fabrica = $login_fabrica ORDER BY nome_fornecedor");
							if (pg_num_rows($qry_fornecedores) > 0) {
								while ($fetch = pg_fetch_assoc($qry_fornecedores)) {
									echo '<option value="' , $fetch['fornecedor'] , '"';
									if ($fornecedor == $fetch['fornecedor']) {
										echo ' SELECTED="SELECTED"';
									}
									echo '>' , $fetch['descricao'] , '</option>';
								}
							}
							?>
						</select>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span3'>
				<div class='control-group'>
					<label class='control-label' for='produto_referencia'>Ref. Produto</label>
					<div class='controls controls-row'>
						<div class='span10 input-append'>
							<input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='produto_descricao'>Descrição Produto</label>
					<div class='controls controls-row'>
						<div class='span11 input-append'>
							<input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
						</div>
					</div>
				</div>
			</div>
			<div class='span1'>
				<div class='control-group'>
					<label class='control-label'></label>
					<div class='controls controls-row'>
						<div>
							<input type="button" id="add_produto" class='btn' value="Adicionar" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>

		<div class='container'>
			<div class='row-fluid'>

				<div class='span2'></div>
				<div class='span7'>
					<div class='control-group'>
						<label class='control-label' for='produto_referencia'></label>
						<div class='controls controls-row'>
							<select name="produtos" id="produtos" multiple class="span12">
							<?
								if (!empty($produto)) {
									foreach ($produto as $key => $value) {
										$sqlProds = "SELECT produto, referencia, descricao FROM tbl_produto where referencia IN ('$value') AND fabrica_i = $login_fabrica";
										$qryProds = pg_query($con, $sqlProds);
										if (pg_num_rows($qryProds)> 0) {
											while ($fetch = pg_fetch_assoc($qryProds)) {
		                                    	echo '<option value="' , $fetch['referencia'] , '" class="' , $fetch['referencia'] , '">' , $fetch['referencia'] , ' - ', $fetch['descricao'] , '</option>';
		                                	}
										}
									}
		                            foreach ($produto as $prod) {
		                                echo '<input type="hidden" name="produto[]" id="' , $prod , '" value="' , $prod , '" >';
		                            }
		                        }
	                        ?>
		                    </select>
						</div>
					</div>
				</div>
				<div class='span1'>
					<div class='control-group'>
						<label class='control-label'></label>
						<div class='controls controls-row'>
							<div>
								<input type="button" id="rm_produto" class="btn" value="Remover" />
							</div>
						</div>
					</div>
				</div>
				<div class='span2'></div>
			</div>
		</div>
		<br>
		<br>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span3'>
				<div class='control-group'>
					<label class='control-label' for='peca_referencia'>Referência Peça</label>
					<div class='controls controls-row'>
						<div class='span10 input-append'>
							<input type="text" name="peca_referencia" id="peca_referencia" class='span12' value="<? echo $peca_referencia ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='peca_descricao'>Descrição Peça</label>
					<div class='controls controls-row'>
						<div class='span11 input-append'>
							<input type="text" name="peca_descricao" id="peca_descricao" class='span12' value="<? echo $peca_descricao ?>">
							<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" />
						</div>
					</div>
				</div>
			</div>
			<div class='span1'>
				<div class='control-group'>
					<label class='control-label'></label>
					<div class='controls controls-row'>
						<div>
							<input type="button" id="add_peca" class='btn' value="Adicionar" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<br>
		<div class='container'>
			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span7'>
					<div class='control-group'>
						<label class='control-label' for='produto_referencia'></label>
						<div class='controls controls-row'>
							<select name="pecas" id="pecas" multiple class="span12">
							<?
								if (!empty($peca)) {
									foreach ($peca as $key => $value) {
										$sqlPecas = "SELECT peca, referencia, descricao FROM tbl_peca where referencia IN ('$value') AND fabrica = $login_fabrica";
										$qryPecas = pg_query($con, $sqlPecas);
										if (pg_num_rows($qryPecas)> 0) {
											while ($fetch = pg_fetch_assoc($qryPecas)) {
		                                    	echo '<option value="' , $fetch['referencia'] , '" class="' , $fetch['referencia'] , '">' , $fetch['referencia'] , ' - ', $fetch['descricao'] , '</option>';
		                                	}
										}
									}
		                            foreach ($peca as $pecaid) {
		                                echo '<input type="hidden" name="peca[]" id="' , $pecaid , '" value="' , $pecaid , '" >';
		                            }
		                        }
	                        ?>
		                    </select>
						</div>
					</div>
				</div>
				<div class='span1'>
					<div class='control-group'>
						<label class='control-label'></label>
						<div class='controls controls-row'>
							<div>
								<input type="button" id="rm_peca" class="btn" value="Remover" />
							</div>
						</div>
					</div>
				</div>
				<div class='span2'></div>
			</div>
		</div>
		<br>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("cnpj", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='revenda_referencia'>Revenda CNPJ</label>
					<div class='controls controls-row'>
						<div class='span10 input-append'>
							<input type="text" name="revenda_referencia" id="revenda_referencia" class='span12' value="<? echo $revenda_referencia ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="revenda" parametro="cnpj" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='revenda_descricao'>Revenda Razão Social</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" name="revenda_descricao" id="revenda_descricao" class='span12' value="<? echo $revenda_descricao ?>">
							<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="revenda" parametro="razao_social" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<br />
		<center>
			<input type='submit' class='btn' name='btn_pesquisar' value='Pesquisar' />
		</center>
		<br />
		</div>
</form>
<?php
if (isset($res_q)) {
	if (pg_num_rows($res_q)> 0) {
		if($somente_peca === false){
		?>
			<div id="DataTables_Table_0_wrapper" class="dataTables_wrapper form-inline" role="grid" >
			<table class='table table-striped table-bordered table-hover table-fixed'>
				<thead>
					<tr class='titulo_coluna'>
						<th>Nome Revenda</th>
						<th>CNPJ Revenda</th>
						<th>Produto</th>
				    </tr>
			    </thead>
		    	<tbody>
				<?
				for ($i=0; $i < pg_num_rows($res_q) ; $i++) {
					$nome_revenda 	= pg_fetch_result($res_q, $i, nome);
					$revenda_cnpj	= pg_fetch_result($res_q, $i, cnpj );
					$total_produto		= pg_fetch_result($res_q, $i, total_produto );
					?>
					<tr>
					<?
					echo '<td style="cursor: pointer" onClick="rastreabilidade(\'' . $aux_data_inicial . '\', \'' . $aux_data_final . '\', \'' . $revenda_cnpj . '\', \'' . $familia_p . '\', \'' . $fornecedor_p . '\', \'' . $posto_p . '\', \'' . $produto_p . '\', \'' . $peca_p . '\')">'.$nome_revenda.'</td>';

					?>
					<!--
					<td>
					  <a href="#" onclick="window.open('rastreabilidade_revenda.php?revenda=$revenda_cnpj&data_inicio=$aux_data_inicial&data_fim=$aux_data_final&familia_p=$familia_p', 'Pagina', 'STATUS=NO, TOOLBAR=NO, LOCATION=NO, DIRECTORIES=NO, RESISABLE=NO, SCROLLBARS=YES, TOP=10, LEFT=10, WIDTH=770, HEIGHT=400');">Clique para abrir a janela POP-up</a>
					</td>
						-->
					<?
					echo "<td>$revenda_cnpj</td>";
					echo "<td>$total_produto</td>";
					echo "</tr>";
				}
				?>
				</tbody>
			</table>
			</div>
			<?php
		}else{
		?>
			<div id="DataTables_Table_0_wrapper" class="dataTables_wrapper form-inline" role="grid" >
			<table class='table table-striped table-bordered table-hover table-fixed'>
				<thead>
				<tr class='titulo_coluna'>
					<th>Produto</th>
					<th>Fornecedor</th>
					<th>Total</th>
			    </tr>
			    </thead>
		    	<tbody>
				<?
				for ($i=0; $i < pg_num_rows($res_q) ; $i++) {
					$produto = pg_fetch_result($res_q, $i, produto);
					$fornecedor_t = pg_fetch_result($res_q, $i, nome_fornecedor);
			    	$referencia = pg_fetch_result($res_q, $i, referencia);
			    	$descricao = pg_fetch_result($res_q, $i, descricao);
			    	$total_prod = pg_fetch_result($res_q, $i, qtd_prod);
					?>
			    	<tr>
					<?
					//echo "<td><a href='rastreabilidade_produto_revenda.php?revenda=$revenda_cnpj&data_inicio=$data_inicio&data_fim=$data_fim&produto=$produto' target='_blank'>$referencia - $descricao</a></td>";
																			//data_in, 				data_f, 					cnpj, 						produto, 				familia, 				fornecedor, 			posto, 				peca
					echo '<td>'.$referencia.' - '.$descricao.'</td>';
					echo "<td>$fornecedor_t</td>";
					echo "<td>$total_prod</td>";
					?>
					</tr>
					<?
				}
				?>
				</tbody>
			</table>
			</div>
		<?php
		}
	}else{
		?>
		<div class="alert alert-warning"> <h4>Nenhum resultado encontrado.</h4> </div>
		<?
	}
}

include "rodape.php";

?>

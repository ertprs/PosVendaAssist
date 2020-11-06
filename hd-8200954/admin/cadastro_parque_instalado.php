<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	$admin_privilegios="call_center";
	include 'autentica_admin.php';
	include 'funcoes.php';

	$btn_acao = (isset($_REQUEST["btn_acao"])) ? trim($_REQUEST["btn_acao"]) : "";
	$btn_option = (isset($_REQUEST["btn_option"])) ? trim($_REQUEST["btn_option"]) : "";
	$id_producao = (isset($_REQUEST["id_producao"])) ? trim($_REQUEST["id_producao"]) : "";

	if(strlen($id_producao) > 0){

		$sql = "SELECT produto, mes, ano, qtde_venda, data_alteracao, admin FROM tbl_producao WHERE producao = {$id_producao}";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res) > 0){

			$produto 		= pg_fetch_result($res, 0, "produto");
			$ano 			= pg_fetch_result($res, 0, "ano");
			$mes 			= pg_fetch_result($res, 0, "mes");
			$qtde_vendida 	= pg_fetch_result($res, 0, "qtde_venda");
			$data_alteracao = pg_fetch_result($res, 0, "data_alteracao");
			$admin 			= pg_fetch_result($res, 0, "admin");

			if(strlen($data_alteracao) > 0){

				list($data, $hora) = explode(" ", $data_alteracao);

				list($ano_d, $mes_d, $dia_d) = explode("-", $data);

				list($hora1, $hora2) = explode(".", $hora);

				$data_alteracao = $dia_d."/".$mes_d."/".$ano_d." ".$hora1;

			}

			$sql_produto = "SELECT referencia, descricao FROM tbl_produto WHERE produto = {$produto}";
			$res_produto = pg_query($con, $sql_produto);

			$produto_referencia = pg_fetch_result($res_produto, 0, "referencia");
			$produto_descricao = pg_fetch_result($res_produto, 0, "descricao");

			$sql_admin = "SELECT login FROM tbl_admin WHERE admin = {$admin}";
			$res_admin = pg_query($con, $sql_admin);

			$usuario = pg_fetch_result($res_admin, 0, "login");

		}else{
			$msg_erro["msg"][] = "Produção não localizada";
		}

	}

	if(strlen($btn_acao) > 0 && strlen($btn_option) > 0){

		$id_producao 			= $_REQUEST['id_producao'];
		$mes 					= $_REQUEST['mes'];
		$ano 					= $_REQUEST['ano'];
		$produto_referencia 	= $_REQUEST['produto_referencia'];
		$produto_descricao 		= $_REQUEST['produto_descricao'];
    	$qtde_vendida  			= $_REQUEST['qtde_vendida'];

		if($btn_acao == "submit" && $btn_option == "gravar"){

			if(strlen($mes) == 0){
	    		$msg_erro["msg"][]    = "Mês é obrigatório";
	        	$msg_erro["campos"][] = "mes";
	    	}

	    	if(strlen($ano) == 0){
	    		$msg_erro["msg"][]    = "Ano é obrigatório";
	        	$msg_erro["campos"][] = "ano";
	    	}

			if(strlen($produto_referencia) == 0){
	    		$msg_erro["msg"][]    = "A refêrencia do produto é obrigatório";
	        	$msg_erro["campos"][] = "produto";
	    	}

	    	if(strlen($qtde_vendida) == 0){
	    		$msg_erro["msg"][]    = "A quantidade vendida é obrigatório";
	        	$msg_erro["campos"][] = "qtde_vendida";
	    	}

	    	if(strlen($produto_referencia)>0){
		        $sql = "SELECT produto FROM tbl_produto WHERE referencia = '$produto_referencia' AND fabrica_i = {$login_fabrica} LIMIT 1";
		        $res = pg_exec($con,$sql);
		        if(pg_numrows($res)>0){
		            $produto = pg_result($res, 0, 0);
		            $cond_1 = " AND tbl_hd_chamado_extra.produto = $produto ";
		        }
		    }

		    if(strlen($id_producao) == 0){

		    	/* Verifica se o produto já esta com informação inserida para aquele mês e ano */
			    $sql_produto_inserido = "SELECT produto FROM tbl_producao WHERE produto = {$produto} AND mes = {$mes} AND ano = {$ano}";
			    $res_produto_inserido = pg_query($con, $sql_produto_inserido);
			    if(pg_num_rows($res_produto_inserido) > 0){
			    	$msg_erro["msg"][] = "Produto com Informações já cadastradas para este mês e ano";
			    }

		    }

		    if(count($msg_erro["msg"]) == 0 && strlen($id_producao) == 0){

		    	$sql = "INSERT INTO tbl_producao (produto, mes, ano, qtde_venda, admin) VALUES 
						($produto, $mes, $ano, $qtde_vendida, $login_admin)";
				$res = pg_query($con, $sql);

				$erro = pg_last_error($con);

				if(strlen($erro) == 0){
					$msg = "Informações gravadas com Sucesso";

					$mes = "";
// 					$ano = "";
					$produto_referencia = "";
					$produto_descricao = "";
					$qtde_vendida = "";

					$sql_pesquisa = "SELECT mes, SUM(qtde_venda) AS total FROM tbl_producao WHERE ano = {$ano} GROUP BY mes ORDER BY mes ASC";
                    $res_pesquisa = pg_query($con, $sql_pesquisa);
				}else{
					$msg = $erro;
				}

		    }else if(count($msg_erro["msg"]) == 0 && strlen($id_producao) > 0){

		    	$sql = "UPDATE tbl_producao SET 
		    				produto = $produto,
		    				mes = $mes,
		    				ano = $ano,
		    				qtde_venda = $qtde_vendida,
		    				admin = $login_admin,
		    				data_alteracao = CURRENT_TIMESTAMP 
		    			WHERE producao = {$id_producao}";
		    	$res = pg_query($con, $sql);

		    	$erro = pg_last_error($con);

		    	if(strlen($erro) == 0){
					$msg = "Informações alteradas com Sucesso";

					$id_producao = "";
					$mes = "";
// 					$ano = "";
					$produto_referencia = "";
					$produto_descricao = "";
					$qtde_vendida = "";

					$sql_pesquisa = "SELECT mes, SUM(qtde_venda) AS total FROM tbl_producao WHERE ano = {$ano} GROUP BY mes ORDER BY mes ASC";
                    $res_pesquisa = pg_query($con, $sql_pesquisa);

				}else{
					$msg = $erro;
				}


		    }

		}

		if($btn_acao == "submit" && $btn_option == "pesquisar"){

			$mes_set = "";

			if(strlen($ano) == 0){
	    		$msg_erro["msg"][]    ="Ano é obrigatório";
	        	$msg_erro["campos"][] = "ano";
	    	}

	    	if(strlen($mes) > 0){
	    		$mes_set = $mes;
	    		$cond_mes = "AND mes = {$mes}";
	    	}

	    	if(strlen($produto_referencia) > 0){

	    		$sql = "SELECT produto from tbl_produto where referencia = '$produto_referencia' limit 1";
		        $res = pg_exec($con,$sql);
		        if(pg_numrows($res)>0){
		            $produto = pg_result($res, 0, 0);
		            $cond_produto = "AND produto = $produto";
		        }
	    	}

	    	if(strlen($qtde_vendida) > 0){
	    		$cond_qtde_vendida = "AND qtde_venda = {$qtde_vendida}";
	    	}

	    	$sql_pesquisa = "SELECT mes, SUM(qtde_venda) AS total FROM tbl_producao WHERE ano = {$ano} {$cond_mes} {$cond_produto} {$cond_qtde_vendida} GROUP BY mes ORDER BY mes ASC";
	    	$res_pesquisa = pg_query($con, $sql_pesquisa);

		}

	}


	$layout_menu = "cadastro";
	$title = "CADASTRO DE PARQUE GARANTIA";

	include "cabecalho_new.php";

	$plugins = array(
	    "autocomplete",
	    "shadowbox",
	    "mask",
	    "dataTable"
	);

	include("plugin_loader.php");
?>

	<script type="text/javascript">
	    $(function() {

	    	$.autocompleteLoad(Array("produto"));

	        Shadowbox.init();

	        $("#qtde_vendida").mask("9999999999");

	        $("span[rel=lupa]").click(function () {
	            $.lupa($(this));
	        });

	    });

	    function retorna_produto (retorno) {
            $("#produto_referencia").val(retorno.referencia);
            $("#produto_descricao").val(retorno.descricao);
        }

        function alterar(id_producao){

        	Shadowbox.close();

        	location.href = "cadastro_parque_instalado.php?id_producao="+id_producao;

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

	<? if (strlen($msg) > 0 AND count($msg_erro["msg"])==0) { ?>
	    <div class="alert alert-success">
	        <h4><? echo $msg; ?></h4>
	    </div>
	<? } ?>

	<div class="row">
		<strong class="obrigatorio pull-right">  * Campos obrigatórios </strong> <br />
		<strong class="obrigatorio pull-right">  * Para Pesquisar somente o campo Ano é obrigatório </strong> 
	</div>

	<!-- Form -->
	<form name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>

	    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>

	    <br/>

	    <input type="hidden" name="id_producao" id="id_producao" value="<?php echo $id_producao; ?>" />

	    <div class='row-fluid'>
	        <div class='span2'></div>

	        <div class='span4'>
	        	<div class='control-group <?=(in_array("mes", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='mes'>Mês</label>
                    <div class='controls controls-row'>
                        <div class='span4'>
                            <h5 class='asteristico'>*</h5>
                            <select name="mes" id="mes" class="span12">
                            	<option></option>
                            	<?php
                            		for($i = 1; $i <= 12; $i++){
                            			$selected = ($mes == $i) ? "selected" : "";
                            			echo "<option value='".$i."' {$selected}>".$i."</option>";
                            		}
                            	?>
                            </select>
                        </div>
                    </div>
                </div>
	        </div>

	        <div class='span4'>
	        	<div class='control-group <?=(in_array("ano", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='ano'>Ano</label>
                    <div class='controls controls-row'>
                        <div class='span4'>
                            <h5 class='asteristico'>*</h5>
                            <select name="ano" id="ano" class="span12">
                            	<option></option>
                            	<?php
									for($i = date('Y'); $i >= 2013; $i--){
                            			$selected = ($ano == $i) ? "selected" : "";
                            			echo "<option value='".$i."' {$selected}>".$i."</option>";
                            		}
                            	?>
                            </select>
                        </div>
                    </div>
                </div>
	        </div>

	        <div class='span2'></div>
	    </div>

	    <div class='row-fluid'>
	        <div class='span2'></div>

	        <div class='span4'>
	            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
	                <label class='control-label' for='produto_referencia'>Ref. Produto</label>
	                <div class='controls controls-row'>
	                    <div class='span7 input-append'>
	                    	<h5 class='asteristico'>*</h5>
	                        <input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
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
	                    	<h5 class='asteristico'>*</h5>
	                        <input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
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
	        	<div class='control-group <?=(in_array("qtde_vendida", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='qtde_vendida'>Quantidade Vendida</label>
                    <div class='controls controls-row'>
                        <div class='span4'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" id="qtde_vendida" name="qtde_vendida" class='span12' maxlength="10" value="<? echo $qtde_vendida ?>" >
                        </div>
                    </div>
                </div>
	        </div>
	        
	        <div class='span2'></div>
	    </div>

	    <p>

	    	<br />

	    	<?php if(!isset($_GET["id_producao"])){ ?>
	        	<button class='btn btn-primary' id="btn_acao" type="button" onclick="$('#btn_option').val('pesquisar'); submitForm($(this).parents('form'));">Pesquisar</button> &nbsp; &nbsp;
	        <?php } ?>

	        <button class='btn btn-success' id="btn_acao" type="button" onclick="$('#btn_option').val('gravar'); submitForm($(this).parents('form'));"><?php echo (isset($_GET["id_producao"])) ?  "Alterar" : "Gravar"; ?></button>

	        <input type='hidden' id="btn_click" name='btn_acao' value='' />

	        <input type='hidden' id="btn_option" name='btn_option' value='' />

	    </p>

	    <br />

	</form>

	<?php
	if(strlen($id_producao) > 0 && strlen($data_alteracao) > 0){
		?>
		<div class="alert alert-warning text-center">
			<strong>ÚLTIMA ATUALIZAÇÃO:</strong> 
			<?php echo $usuario; ?> - 
			<?php echo $data_alteracao; ?>
		</div>
		<br />
		<?php
	}
	?>

	<?php

    if(isset($res_pesquisa) && pg_num_rows($res) > 0){
?>
			<table class="table tabela_item table-striped table-bordered table-hover table-large" style="margin: 0 auto;">
				<thead>
					<tr class="titulo_tabela">
						<th style="font-size: 16px;">Mês/Ano</th>
						<?php

						for($i = 1; $i <= 12; $i++){
							$i = ($i < 10) ? "0".$i : $i;
							echo "<th class='tac' style='font-size: 13px;'>{$i}/{$ano}</th>";
						}

						?>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td class="tac">Qtd. PQ</td>
					<?php

						$total_venda_ano = 0;

						for($j = 1; $j <= 12; $j++){

							$existe_mes = 0;

							echo "<td class='tac'>";

								for($i = 0; $i < pg_num_rows($res_pesquisa); $i++){

									$mes = pg_fetch_result($res_pesquisa, $i, "mes");
									$total = pg_fetch_result($res_pesquisa, $i, "total");

									if($j == $mes){
										$total_venda = "<a href='parque_instalado_detalhe.php?mes={$mes}&ano={$ano}' rel='shadowbox; width = 900; height = 450;'>".$total."<a>";
										$existe_mes = 1;
										$total_venda_ano += $total;
									}

								}

								echo ($existe_mes == 0) ? $existe_mes : $total_venda;

							echo "</td>";

						}

					?>
					</tr>
				</tbody>
			</table>

			<br />

			<div class="alert alert-info taca">Total Vendido no <?php echo (strlen($mes_set) > 0) ? "mês de ".$mes." do " : ""; ?> Ano de <?php echo $ano; ?>: <strong><?php echo $total_venda_ano; ?></strong></div>

			<?php

    }else{
        if(strlen($btn_option) > 0 && $btn_option == "pesquisar"){
			?> <div class="alert alert-warning text-center"><h4>Nenhum resultado encontrado</h4></div> <?php
        }
	}
	?>

	<? include "rodape.php" ?>

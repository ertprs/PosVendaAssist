<?php

	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	$admin_privilegios="call_center";
	include 'autentica_admin.php';
	include 'funcoes.php';

	/* Gera relatório */
	if ($_POST["btn_acao"] == "submit" || $_POST["gerar_excel"] == true) {

        $data_inicial       = filter_input(INPUT_POST,"data_inicial");
        $data_final         = filter_input(INPUT_POST,"data_final");
        $linha_post         = filter_input(INPUT_POST,"linha",FILTER_UNSAFE_RAW,FILTER_REQUIRE_ARRAY);
        $marca_post         = filter_input(INPUT_POST,"marca",FILTER_UNSAFE_RAW,FILTER_REQUIRE_ARRAY);
        $regiao             = filter_input(INPUT_POST,"regiao");
        $produto_referencia = filter_input(INPUT_POST,"produto_referencia");
        $produto_descricao  = filter_input(INPUT_POST,"produto_descricao");
        $codigo_posto       = filter_input(INPUT_POST,"codigo_posto");
        $descricao_posto    = filter_input(INPUT_POST,"descricao_posto");
        $relatorio          = filter_input(INPUT_POST,"relatorio");
        $tipo_os            = filter_input(INPUT_POST,"tipo_os");
        $envio_garantia     = filter_input(INPUT_POST,"envio_garantia");

        $filtrar_por        = filter_input(INPUT_POST,"filtrar_por");
        $ordernar_por       = filter_input(INPUT_POST,"ordernar_por");

		if (strlen($data_inicial) == 0 || strlen($data_final) == 0) {
    		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
        	$msg_erro["campos"][] = "data";
    	}

    	if (count($linha_post) == 0) {
    		if (count($msg_erro["msg"]) == 0) {
    			$msg_erro["msg"][] = "Preencha os campos obrigatórios";
    		}
        	$msg_erro["campos"][] = "linha";
    	}

    	if ($login_fabrica != 186) {
	    	if (count($marca_post) == 0){
	    		if (count($msg_erro["msg"]) == 0) {
	    			$msg_erro["msg"][] = "Preencha os campos obrigatórios";
	    		}
	        	$msg_erro["campos"][] = "marca";
	    	}
	    }

    	if (strlen($data_inicial) > 0 && strlen($data_final) > 0) {

    		list($dia, $mes, $ano) = explode("/", $data_inicial);
			$xdata_inicial = $ano."-".$mes."-".$dia;

			list($dia, $mes, $ano) = explode("/", $data_final);
			$xdata_final = $ano."-".$mes."-".$dia;

			if ($xdata_inicial > $xdata_final) {
				$msg_erro["msg"][]    ="Data Inicial maior que final";
	        	$msg_erro["campos"][] = "data_inicial";
			}

			if (strtotime($xdata_final) > strtotime($xdata_inicial . ' +12 month')) {
				$msg_erro["msg"][]    = "O período não pode maior que 12 meses";
			}

    	}

    	if (count($msg_erro["msg"]) == 0) {

	    	if (strlen($filtrar_por) > 0) {
				switch ($filtrar_por) {
					case 2:
						$cond_filtro = " AND tbl_os.data_fechamento IS NULL AND tbl_os.finalizada IS NULL ";
						break;
					case 3:
						$cond_filtro = " AND tbl_os.data_fechamento IS NOT NULL AND tbl_os.finalizada IS NOT NULL ";
						break;
					default:
						$cond_filtro = "";
				}
			} else {
				$cond_filtro = "";
			}

			if(strlen($ordernar_por) > 0){
				switch ($ordernar_por) {
					case 1:
						$cond_ordena = " ORDER BY data_digitacao ASC ";
						break;
					case 2:
						$cond_ordena = " ORDER BY data_fechamento ASC ";
						break;
					case 3:
						$cond_ordena = " ORDER BY data_abertura ASC ";
						break;
					case 4:
						$cond_ordena = " ORDER BY data_conserto ASC ";
						break;
					default:
						$cond_ordena = " ORDER BY data_digitacao ASC ";
						break;
				}
			}else{
				$cond_ordena = " ORDER BY tbl_os.data_digitacao ASC ";
			}

			if(strlen($produto_referencia) > 0){
		        $sql_produto = "SELECT produto FROM tbl_produto WHERE referencia = '$produto_referencia' AND fabrica_i = {$login_fabrica} LIMIT 1";
		        $res_produto = pg_query($con, $sql_produto);
		        if(pg_num_rows($res_produto) > 0){
		            $produto = pg_fetch_result($res_produto, 0, "produto");
		            $cond_produto = " AND tbl_os.produto = {$produto} ";
		        }
		    }

		    if(strlen($codigo_posto) > 0){
		        $sql_posto = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '$codigo_posto' AND fabrica = {$login_fabrica} LIMIT 1";
		        $res_posto = pg_query($con, $sql_posto);
		        if(pg_num_rows($res_posto) > 0){
		            $posto = pg_fetch_result($res_posto, 0, "posto");
		            $cond_posto = " AND tbl_os.posto = {$posto} ";
		        }
		    }

		    if(strlen($regiao)){

		    	switch ($regiao) {
		    		case 1:
		                $consulta_estado = "'AC','AP','AM','PA','RO','RR','TO'";
		            	break;
		            case 2:
		                $consulta_estado = "'AL','BA','CE','MA','PB','PE','PI','RN','SE'";
		            	break;
		            case 3:
		                $consulta_estado = "'DF','GO','MT','MS'";
		            	break;
		            case 4:
		                $consulta_estado = "'ES','MG','RJ','SP'";
		            	break;
		            case 5:
		                $consulta_estado = "'PR','RS','SC'";
		            	break;
		    	}

		    	$cond_regiao = " AND tbl_os.consumidor_estado IN ({$consulta_estado}) ";

		    }

            switch ($tipo_os) {
                case "reparo":
                    $condTipoOs = " AND tbl_solucao.troca_peca IS TRUE ";
                    break;
                case "troca":
                    $condTipoOs = "\nAND tbl_os.tipo_atendimento IS NOT NULL\n";
                    break;
                case "ajuste":
                    $condTipoOs = "\nAND tbl_os.tipo_atendimento IS NULL\n";
                    break;
                case "devolucao_90_dias":
                    $condTipoOs = "\n AND tbl_os.tipo_atendimento = 334 \n";
                    break;
                default:
                    $condTipoOs = "AND (tbl_os.tipo_atendimento <> 334 OR tbl_os.tipo_atendimento is null)";
                    break;
            }

            switch ($envio_garantia) {
                case "sim":
                    $condGarantia = "\nAND tbl_posto_fabrica.reembolso_peca_estoque IS TRUE\n";
                    break;
                case "nao":
                    $condGarantia = "\nAND tbl_posto_fabrica.reembolso_peca_estoque IS NOT TRUE\n";
                    break;
                default:
                    $condGarantia = "";
                    break;
            }

            if ($relatorio == "total") {
                $tempItem = ",
                    tbl_os_produto.os_produto,
                    peca,
                    tbl_os_item.qtde,
                    round(tbl_os_item.custo_peca::numeric,2) as custo_peca
                ";

                $joinTempItem = "
                    LEFT JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
                    LEFT JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto AND tbl_os_item.fabrica_i = {$login_fabrica}
                ";

                $pecas = ",
                    tbl_peca.referencia ,
                    qtde * custo_peca::numeric(12,2) AS total_pecas
                ";

                $indexProduto = "CREATE index tmp_produto_os_produto on tmp_produto_garantia_$login_admin(os_produto);";

                $joinPecas = " LEFT JOIN tbl_peca ON tbl_peca.peca = tmp_produto_garantia_$login_admin.peca and tbl_peca.fabrica = {$login_fabrica}\n";
            }

            if (count($marca_post_in) > 0) {
		    	$marca_post_in = " (".implode(",", $marca_post).") ";
		    	$selectMarca = "tbl_marca.nome AS marca,";
		    	$joinMarca = "JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca AND tbl_marca.fabrica = {$login_fabrica}";
		    	$whereMarca = "AND tbl_produto.marca IN {$marca_post_in}";
		    }
		    $linha_post_in = " (".implode(",", $linha_post).") ";

		    $limit = " LIMIT 500 ";

		    if($_POST["gerar_excel"] == true){
		    	$limit = "";
		    }

			$sql_os = "
				SELECT (tbl_posto_fabrica.codigo_posto || tbl_os.sua_os) AS os,
				tbl_posto_fabrica.codigo_posto,
				tbl_posto.nome AS nome_posto,
				tbl_tipo_posto.descricao AS tipo_posto,
				tbl_os.os as os_id,
				tbl_os.produto,
				type,
				codigo_fabricacao,
				tbl_os.serie,
				tbl_os.defeito_constatado,
				tbl_os.tipo_atendimento,
				tbl_solucao.troca_peca AS troca_peca_solucao,
				CASE
				   	WHEN tbl_os.consumidor_revenda = 'C' THEN
				        tbl_os.consumidor_nome
				   	WHEN tbl_os.consumidor_revenda = 'R' THEN
				        tbl_os.revenda_nome
				END AS cliente,
				TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
				TO_CHAR(tbl_os.data_conserto,'DD/MM/YYYY') AS data_conserto,
				TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento
				$tempItem
			   	INTO TEMP tmp_os_garantia_$login_admin
				FROM tbl_os
				JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
				JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto AND tbl_posto.posto = tbl_posto_fabrica.posto
				JOIN tbl_tipo_posto ON tbl_tipo_posto.fabrica = tbl_posto_fabrica.fabrica AND tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
				$joinTempItem
				LEFT JOIN tbl_solucao ON tbl_os.solucao_os = tbl_solucao.solucao AND tbl_solucao.fabrica = {$login_fabrica}

				WHERE tbl_os.fabrica = {$login_fabrica}
				AND tbl_os.excluida IS NOT TRUE
				AND tbl_os.posto <> 6359
				AND data_digitacao BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59'
				$cond_filtro
				$cond_produto
				$cond_posto
				$cond_regiao
                $condTipoOs
                $condGarantia;

				CREATE index tmp_os_os on tmp_os_garantia_$login_admin(os);
				CREATE index tmp_os_produto on tmp_os_garantia_$login_admin(produto);

				SELECT tmp_os_garantia_$login_admin.*,
						tbl_produto.referencia as referencia_produto,
						tbl_produto.referencia_fabrica,
						tbl_produto.code_convention,
						tbl_linha.nome AS linha,
						{$selectMarca}
						tbl_produto.descricao
				INTO temp tmp_produto_garantia_$login_admin
				FROM tmp_os_garantia_$login_admin
				JOIN tbl_produto ON tbl_produto.produto = tmp_os_garantia_$login_admin.produto
				JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha AND tbl_linha.fabrica = {$login_fabrica}
				{$joinMarca}
				WHERE tbl_produto.linha IN {$linha_post_in}
				{$whereMarca};

                $indexProduto

				SELECT tmp_produto_garantia_$login_admin.*,
				tbl_defeito_constatado.descricao AS falha
                $pecas
				FROM tmp_produto_garantia_$login_admin
                $joinPecas
				LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tmp_produto_garantia_$login_admin.defeito_constatado
				WHERE 1=1
				$cond_ordena
				$limit ";
			$res_os = pg_query($con, $sql_os);

			if ($_POST["gerar_excel"] == true) {

				if (pg_num_rows($res_os) > 0) {

					$file     = "xls/relatorio-total-garantia-{$login_fabrica}.xls";
					$fileTemp = "/tmp/relatorio-total-garantia-{$login_fabrica}.xls";

                    $colspan = ($relatorio == "total") ? 19 : 12;

		        	$head = "
		        		<table border='1'>
		                    <thead>
		                        <tr>
		                            <th bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' colspan='$colspan' >RELATÓRIO TOTAL GARANTIA</th>
		                        </tr>
		                        <tr>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>OS</th>";
					if ($login_fabrica == 1) {
						$head .=" 	
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Tipo OS</th>";	
					}
						$head .="	
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Produto</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Type</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Descrição</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Linha</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Marca</th>";
                    if ($relatorio == "total") {
                        $head .= "
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Código Fabricação</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nº de Série</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Falha</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Peça trocada</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Quantidade</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Preço</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Preço total</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Code convention</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Tipo de atendimento</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cliente</th>
                        ";
                    } else if ($relatorio == "kpi") {
                        $head .= "
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Código Posto</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nome Posto</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Tipo Posto</th>
                        ";
                    }
                    $head .= "
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data da Ordem</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Reparação</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Fechamento</th>
		                        </tr>
		                    </thead>
		                    <tbody>";

                    while ($resExcel = pg_fetch_object($res_os)) {

                    	if($resExcel->tipo_atendimento == 334){
							$tipo_atendimento = "Devolução 90 dias";
						}elseif (!empty($resExcel->tipo_atendimento)) {
							$tipo_atendimento = "TROCA";
						} else if($resExcel->troca_peca_solucao == "t") {
							$tipo_atendimento = "REPARO";
						} else {
							$tipo_atendimento = "AJUSTE";
						}
						$body .= "<tr>";
						$body .= "<td>".$resExcel->os."</td>";
						if ($login_fabrica == 1) {
							$body .= "<td>".$tipo_atendimento."</td>";	
						}
						$body .= "<td>".$resExcel->referencia_produto."</td>";
						$body .= "<td>".$resExcel->type."</td>";
						$body .= "<td>".$resExcel->descricao."</td>";
						$body .= "<td>".$resExcel->linha."</td>";
						$body .= "<td>".$resExcel->marca."</td>";
                        if ($relatorio == "total") {
                            $body .= "<td>".$resExcel->codigo_fabricacao."</td>";
                            $body .= "<td>".$resExcel->serie."</td>";
                            $body .= "<td>".$resExcel->falha."</td>";
                            $body .= "<td>".$resExcel->referencia."</td>";
                            $body .= "<td>".$resExcel->qtde."</td>";
                            $body .= "<td>".$resExcel->custo_peca."</td>";
                            $body .= "<td>".$resExcel->total_pecas."</td>";
                            $body .= "<td>".$resExcel->code_convention."</td>";
                            $body .= "<td>".$tipo_atendimento."</td>";
                            $body .= "<td>".$resExcel->cliente."</td>";
                        } else if ($relatorio == "kpi") {
                            $body .= "<td >".$resExcel->codigo_posto."</td>";
                            $body .= "<td >".$resExcel->nome_posto."</td>";
                            $body .= "<td>".$resExcel->tipo_posto."</td>";
                        }
						$body .= "<td >".$resExcel->data_digitacao."</td>";
						$body .= "<td >".$resExcel->data_conserto."</td>";
						$body .= "<td>".$resExcel->data_fechamento."</td>";
						$body .= "</tr>";

			        }

                    $body .= "</tbody></table>";

                    $fp = fopen($fileTemp,"w");

                    fwrite($fp, $head);
			        fwrite($fp, $body);
			        fclose($fp);

			        if(file_exists($fileTemp)){
			            system("cp $fileTemp $file");

			            if(file_exists($file)){
			                echo $file;
			            }
			        }

			    }

		        exit;

			}

	    }

    }

	$layout_menu = "callcenter";
	$title = "RELATÓRIO TOTAL GARANTIA";

	include "cabecalho_new.php";

	$plugins = array(
	    "autocomplete",
	    "shadowbox",
	    "mask",
	    "dataTable",
	    "multiselect"
	);

	include("plugin_loader.php");

?>

	<script type="text/javascript">

	    $(function() {
	        $.datepickerLoad(Array("data_final", "data_inicial"));
	        $.autocompleteLoad(Array("produto", "posto"));
	        Shadowbox.init();

	        $("span[rel=lupa]").click(function () {
	            $.lupa($(this));
	        });

	        $("#linha").multiselect({
			   selectedText: "# de # opções"
			});

			$("#marca").multiselect({
			   selectedText: "# de # opções"
			});
	    });

	    function retorna_produto (retorno){
	        $("#produto_referencia").val(retorno.referencia);
	        $("#produto_descricao").val(retorno.descricao);
	    }

	    function retorna_posto(retorno){
	        $("#codigo_posto").val(retorno.codigo);
	        $("#descricao_posto").val(retorno.nome);
	    }

	    function close(){
	    	Shadowbox.close();
	    }

	</script>

	<style>
		#listagem_wrapper{
			margin: 0 auto;
			padding: 10px;
		}
	</style>

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

	<!-- FORM -->

	<div class="row">
		<strong class="obrigatorio pull-right">  * Campos obrigatórios </strong>
	</div>

	<!-- Form -->
	<form name="frm_relatorio" method="POST" action="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>

		<div class='titulo_tabela '>Parâmetros de Pesquisa</div>

		<br />

		<!-- Data Inicial/ Data Final -->
		<div class='row-fluid'>
	        <div class='span2'></div>

            <div class='span4'>
                <div class='control-group <?=(in_array("data", $msg_erro["campos"]) || in_array("data_inicial", $msg_erro["campos"])) ? "error" : ""?>'>
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

	    <!-- Linha / Natureza -->
	    <div class='row-fluid'>
	        <div class='span2'></div>

	        <div class='span4'>
	            <div class='control-group <?=(in_array("linha", $msg_erro["campos"])) ? "error" : ""?>'>
	                <label class='control-label' for='linha'>Linha</label>
	                <div class='controls controls-row'>
	                    <div class='span4'>
	                    	<h5 class='asteristico'>*</h5>
	                        <select name="linha[]" id="linha" multiple="multiple">
	                            <?php
	                            	$sqlx = "SELECT linha, codigo_linha, nome
	                                    FROM tbl_linha
	                                    WHERE fabrica = $login_fabrica AND ativo = 't'
	                                    ORDER BY nome";

	                            	$resx = pg_exec($con,$sqlx);

	                            	foreach (pg_fetch_all($resx) as $key) {
	                                	$selected_natureza = (isset($linha_post) and (in_array($key['linha'], $linha_post))) ? "SELECTED" : '' ;
	                            ?>
	                                	<option value="<?php echo $key['linha']?>" <?php echo $selected_natureza ?> >
	                                    	<?php echo $key['codigo_linha']." - ".$key['nome']; ?>
	                                	</option>
	                            <?php
	                            	}
	                            ?>
	                        </select>
	                    </div>
	                </div>
	            </div>
	        </div>
	        <?php if ($login_fabrica != 186) { ?>
		        <div class='span4'>
		            <div class='control-group <?=(in_array("marca", $msg_erro["campos"])) ? "error" : ""?>'>
		                <label class='control-label' for='marca'>Marca</label>
		                <div class='controls controls-row'>
		                    <div class='span4'>
		                    	<h5 class='asteristico'>*</h5>
		                        <select name="marca[]" id="marca" multiple="multiple">
		                            <?php
		                                $sql = "SELECT marca, nome FROM tbl_marca WHERE fabrica = $login_fabrica AND visivel = 't' ORDER BY nome ASC";
		                                $res = pg_exec($con,$sql);
		                                foreach (pg_fetch_all($res) as $key) {
		                                    $selected_status = (isset($marca_post) and (in_array($key['marca'], $marca_post))) ? "SELECTED" : '' ;
		                                ?>
		                                    <option value="<?php echo $key['marca']?>" <?php echo $selected_status ?> >
		                                        <?php echo $key['nome']?>
		                                    </option>
		                                <?php
		                                }

		                            ?>
		                        </select>
		                    </div>
		                </div>
		            </div>
		        </div>
		    <?php } ?>

	        <div class='span2'></div>
	    </div>

	    <!-- Cód Produto / Nome produto -->
	    <div class='row-fluid'>
	        <div class='span2'></div>
	        <div class='span4'>
	            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
	                <label class='control-label' for='produto_referencia'>Ref. Produto</label>
	                <div class='controls controls-row'>
	                    <div class='span7 input-append'>
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
	                        <input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
	                        <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
	                        <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
	                    </div>
	                </div>
	            </div>
	        </div>
	        <div class='span2'></div>
	    </div>

	    <!-- Cód. Posto / Nome Posto -->
	    <div class='row-fluid'>
	        <div class='span2'></div>
	        <div class='span4'>
	            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
	                <label class='control-label' for='codigo_posto'>Código Posto</label>
	                <div class='controls controls-row'>
	                    <div class='span7 input-append'>
	                        <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
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
	                        <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
	                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
	                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
	                    </div>
	                </div>
	            </div>
	        </div>
	        <div class='span2'></div>
	    </div>

	    <!-- Filtrar Pos / Ordernar Por -->
	   	<!-- Região -->
	    <div class='row-fluid'>
	        <div class='span2'></div>

	        <div class='span4'>
	            <div class='control-group '>
	                <label class='control-label' for='filtrar_por'>Filtrar por</label>
	                <div class='controls controls-row'>
	                    <div class='span4'>
	                        <select name="filtrar_por" id="filtrar_por">
	                            <option value="1" <?php echo ($filtrar_por == 1) ? "SELECTED" : ""; ?>>Todas as OSs</option>
	                            <option value="2" <?php echo ($filtrar_por == 2) ? "SELECTED" : ""; ?>>OSs abertas</option>
	                            <option value="3" <?php echo ($filtrar_por == 3) ? "SELECTED" : (strlen($filtrar_por) == 0) ? "SELECTED" : ""; ?>>OSs fechadas</option>
	                        </select>
	                    </div>
	                </div>
	            </div>
	        </div>

	        <div class='span4'>
	            <div class='control-group '>
	                <label class='control-label' for='ordernar_por'>Ordenar por</label>
	                <div class='controls controls-row'>
	                    <div class='span4'>
	                        <select name="ordernar_por" id="ordernar_por">
	                            <option value="1" <?=($ordernar_por == 1) ? "SELECTED" : (strlen($ordernar_por) == 0) ? "SELECTED" : ""; ?>>Data de digitação da OS</option>
	                            <option value="2" <?=($ordernar_por == 2) ? "SELECTED" : ""; ?>>Data de fechamento da OS</option>
	                            <option value="3" <?=($ordernar_por == 3) ? "SELECTED" : ""; ?>>Data de abertura da OS</option>
	                            <option value="4" <?=($ordernar_por == 4) ? "SELECTED" : ""; ?>>Data de conserto da OS</option>
	                        </select>
	                    </div>
	                </div>
	            </div>
	        </div>

	        <div class='span2'></div>
	    </div>

	   	<!-- Região -->
        <div class='row-fluid'>
            <div class='span2'></div>

            <div class='span4'>
                <div class='control-group '>
                    <label class='control-label' for='regiao'>Região</label>
                    <div class='controls controls-row'>
                        <div class='span4'>
                            <select name="regiao" id="regiao">
                                <option value=""></option>
                                <option value="1" <?php echo ($regiao == 1) ? "SELECTED" : ""; ?>>Norte</option>
                                <option value="2" <?php echo ($regiao == 2) ? "SELECTED" : ""; ?>>Nordeste</option>
                                <option value="3" <?php echo ($regiao == 3) ? "SELECTED" : ""; ?>>Centro-Oeste</option>
                                <option value="4" <?php echo ($regiao == 4) ? "SELECTED" : ""; ?>>Sudeste</option>
                                <option value="5" <?php echo ($regiao == 5) ? "SELECTED" : ""; ?>>Sul</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class='span4'>
                <div class='control-group '>
                    <label class='control-label' for='relatorio'>Tipo Relatório</label>
                    <div class='controls controls-row'>
                        <div class='span4'>
                            <select name="relatorio" id="relatorio">
                                <option value="total" <?=($relatorio == 'total') ? "SELECTED" : ""; ?>>Total Garantia</option>
                                <option value="kpi" <?=($relatorio == 'kpi' || empty($relatorio)) ? "SELECTED" : ""; ?>>Repair KPI</option>
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
                <div class='control-group '>
                    <label class='control-label' for='tipo_os'>Tipo OS</label>
                    <div class='controls controls-row'>
                        <div class='span4'>
                            <select name="tipo_os" id="tipo_os">
                                <option value=""></option>
                                <option value="reparo" <?=($tipo_os == 'reparo' || empty($tipo_os)) ? "SELECTED" : ""; ?>>OS Reparo</option>
                                <option value="ajuste" <?=($tipo_os == 'ajuste') ? "SELECTED" : ""; ?>>OS Ajuste</option>
                                <option value="troca"  <?=($tipo_os == 'troca')  ? "SELECTED" : ""; ?>>OS Troca</option>
                                <option value="devolucao_90_dias"  <?=($tipo_os == 'devolucao_90_dias')  ? "SELECTED" : ""; ?>>Devolução 90 Dias</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class='span4'>
                <div class='control-group '>
                    <label class='control-label' for='envio_garantia'>Envio Garantia</label>
                    <div class='controls controls-row'>
                        <div class='span4'>
                            <select name="envio_garantia" id="envio_garantia">
                                <option value=""></option>
                                <option value="sim" <?=($envio_garantia == 'sim') ? "SELECTED" : ""; ?>>Sim</option>
                                <option value="nao" <?=($envio_garantia == 'nao' || empty($relatorio)) ? "SELECTED" : ""; ?>>Não</option>
                            </select>
                        </div>
                    </div>
                </div>

            </div>
            <div class='span2'></div>
        </div>

	    <!-- Botão Action -->
	    <p>
	    <br />
	    <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));" value="Gravar">Pesquisar</button>
	    <input type='hidden' id="btn_click" name='btn_acao' value='' />
	    </p>
	    <br/>

	</form>
	<!-- FORM -->

</div>

	<br />

	<?php

	if($_POST["btn_acao"] == "submit"){

		if(pg_num_rows($res_os) > 0){

			$rows = pg_num_rows($res_os);
?>
<table id='listagem' class='tabela_item table table-striped table-bordered table-large table-hover'>
    <thead class='titulo_coluna'>
        <tr>
            <th nowrap>OS</th>
            <? if ($login_fabrica == 1) { ?>
            	<th nowrap>Tipo OS</th>	
        	<? }?>
            <th nowrap>Produto</th>
            <th nowrap>Type</th>
            <th nowrap>Descrição</th>
            <th nowrap>Linha</th>
            <th nowrap>Marca</th>
<?php
            if ($relatorio == "total") {
?>
            <th nowrap>Código Fabricação</th>
            <th nowrap>Nº de Série</th>
            <th nowrap >Falha</th>
            <th nowrap>Peça trocada</th>
            <th nowrap>Quantidade</th>
            <th nowrap>Preço</th>
            <th nowrap>Preço total</th>
            <th nowrap>Code convention</th>
            <th nowrap>Tipo de atendimento</th>
            <th nowrap>Cliente</th>
<?php
            } else if ($relatorio == "kpi") {
?>
            <th nowrap>Código Posto</th>
            <th nowrap>Nome Posto</th>
            <th nowrap>Tipo Posto</th>
<?php
            }
?>
            <th nowrap>Data da Ordem</th>
            <th nowrap>Data Conserto</th>
            <th nowrap>Fechamento</th>
        </tr>
    </head>
    <tbody>
<?php
            for($i = 0; $i < $rows; $i++){

                $os                 = pg_fetch_result($res_os, $i, 'os');
                $referencia_produto = pg_fetch_result($res_os, $i, 'referencia_produto');
                $type               = pg_fetch_result($res_os, $i, 'type');
                $descricao_produto  = pg_fetch_result($res_os, $i, 'descricao');
                $linha              = pg_fetch_result($res_os, $i, 'linha');
                $marca              = pg_fetch_result($res_os, $i, 'marca');
                $posto_codigo       = pg_fetch_result($res_os, $i, 'codigo_posto');
                $nome_posto         = pg_fetch_result($res_os, $i, 'nome_posto');
                $tipo_posto         = pg_fetch_result($res_os, $i, 'tipo_posto');
                $codigo_fabricacao  = strtoupper(pg_fetch_result($res_os, $i, 'codigo_fabricacao'));
                $serie              = pg_fetch_result($res_os, $i, 'serie');
                $falha              = pg_fetch_result($res_os, $i, 'falha');
                $referencia         = pg_fetch_result($res_os, $i, 'referencia');
                $qtde               = pg_fetch_result($res_os, $i, 'qtde');
                $custo_peca         = pg_fetch_result($res_os, $i, 'custo_peca');
                $total_pecas        = pg_fetch_result($res_os, $i, 'total_pecas');
                $code_convention    = pg_fetch_result($res_os, $i, 'code_convention');
                $cliente            = pg_fetch_result($res_os, $i, 'cliente');
                $tipo_atendimento   = pg_fetch_result($res_os, $i, 'tipo_atendimento');
                $revenda_nome       = pg_fetch_result($res_os, $i, 'revenda_nome');
                $data_digitacao     = pg_fetch_result($res_os, $i, 'data_digitacao');
                $data_conserto      = pg_fetch_result($res_os, $i, 'data_conserto');
                $data_fechamento    = pg_fetch_result($res_os, $i, 'data_fechamento');
                $troca_peca_solucao = pg_fetch_result($res_os, $i, 'troca_peca_solucao');

				if($tipo_atendimento == 334){
					$tipo_atendimento = "Devolução 90 dias";
				}elseif(!empty($tipo_atendimento)){
					$tipo_atendimento = "TROCA";
				}else if($troca_peca_solucao == "t"){
					$tipo_atendimento = "REPARO";
				}else{
					$tipo_atendimento = "AJUSTE";
				}
				
?>
        <tr>
            <td nowrap><?=$os?></td>
            <? if ($login_fabrica == 1) { ?>
            	<td nowrap><?=$tipo_atendimento?></td>
            <? } ?>
            <td nowrap><?=$referencia_produto?></td>
            <td><?=$type?></td>
            <td nowrap><?=$descricao_produto?></td>
            <td nowrap><?=$linha?></td>
            <td nowrap><?=$marca?></td>
<?php
                if ($relatorio == "total") {
?>
            <td nowrap><?=$codigo_fabricacao?></td>
            <td nowrap><?=$serie?></td>
            <td nowrap><?=$falha?></td>
            <td class='tac'><?=$referencia?></td>
            <td class='tac'><?=$qtde?></td>
            <td class='tac'><?=$custo_peca?></td>
            <td class='tac'><?=$total_pecas?></td>
            <td nowrap class='tac'><?=$code_convention?></td>
            <td nowrap class='tac'><?=$tipo_atendimento?></td>
            <td nowrap><?=$cliente?></td>
<?php
                } else if ($relatorio == "kpi") {
?>
            <td class='tac'><?=$posto_codigo?></td>
            <td class='tac'><?=$nome_posto?></td>
            <td class='tac'><?=$tipo_posto?></td>
<?php
                }
?>
            <td class='tac'><?=$data_digitacao?></td>
            <td class='tac'><?=$data_conserto?></td>
            <td class='tac'><?=$data_fechamento?></td>
        </tr>

<?php
            }
?>
    </tbody>
</table>
<?php
            if ($rows > 50) {
?>
	            <script>
	                $.dataTableLoad({
	                    table : "#listagem"
	                });
	            </script>
<?php
            }

	        $dados_form = array(
                "data_inicial"          => $data_inicial,
                "data_final"            => $data_final,
                "linha"                 => $linha_post,
                "marca"                 => $marca_post,
                "regiao"                => $regiao,
                "produto_referencia"    => $produto_referencia,
                "produto_descricao"     => $produto_descricao,
                "codigo_posto"          => $codigo_posto,
                "descricao_posto"       => $descricao_posto,
                "filtrar_por"           => $filtrar_por,
                "ordernar_por"          => $ordernar_por,
                "relatorio"             => $relatorio     ,
                "tipo_os"               => $tipo_os       ,
                "envio_garantia"        => $envio_garantia,
                "gerar_excel"           => true
            );

	        ?>
	        <br/>
		    <div id='gerar_excel' class="btn_excel">
		        <input type="hidden" id="jsonPOST" value='<?=json_encode($dados_form)?>' />
		        <span><img src='imagens/excel.png' /></span>
		        <span class="txt">Gerar Arquivo Excel</span>
		    </div>
	        <?php

		}else{
			?>
			<div class="container">
				<div class="alert alert-warning text-center">
		            <h4>Nenhum resultado encontrado</h4>
		        </div>
		    </div>
			<?php
		}
	}

	?>

	<br />

<? include "rodape.php" ?>

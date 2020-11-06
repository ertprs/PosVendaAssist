<?php

	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'funcoes.php';

	$admin_privilegios = "financeiro";

	$gera_automatico = trim($_GET["gera_automatico"]);

	if ($gera_automatico != 'automatico'){
		include "autentica_admin.php";
	}
	if($login_fabrica == 117){
	    header("location:extrato_pagamento_elgin.php");
	}

	$msg_erro = array();

	//include "gera_relatorio_pararelo_include.php";

	if (strlen(trim($_REQUEST["btn_acao"])) > 0) $btn_acao             = trim($_REQUEST["btn_acao"]);
	if (strlen(trim($_REQUEST["posto_codigo"])) > 0) $posto_codigo     = trim($_REQUEST["posto_codigo"]);
	if (strlen(trim($_REQUEST["data_inicial"])) > 0) $data_inicial     = trim($_REQUEST["data_inicial"]);
	if (strlen(trim($_REQUEST["data_final"])) > 0) $data_final         = trim($_REQUEST["data_final"]);
	if (strlen(trim($_REQUEST["agrupar"])) > 0) $agrupar               = trim($_REQUEST["agrupar"]);
	if (strlen(trim($_REQUEST["nota_sem_baixa"])) > 0) $nota_sem_baixa = trim($_REQUEST["nota_sem_baixa"]);
	if (strlen(trim($_REQUEST["nota_com_baixa"])) > 0) $nota_com_baixa = trim($_REQUEST["nota_com_baixa"]);

	if (strlen(trim($_REQUEST["tipo_atendimento"])) > 0) $tipo_atendimento     = trim($_REQUEST["tipo_atendimento"]);

	if (strlen(trim($_REQUEST["produto_id"])) > 0) $produto_id = trim($_REQUEST["produto_id"]);
	if (strlen(trim($_REQUEST["produto_referencia"])) > 0) $produto_referencia = trim($_REQUEST["produto_referencia"]);

	if (strlen(trim($_REQUEST["extrato_pago"])) > 0) $extrato_pago = trim($_REQUEST["extrato_pago"]);
	if (strlen(trim($_REQUEST["extrato_nao_pago"])) > 0) $extrato_nao_pago = trim($_REQUEST["extrato_nao_pago"]);

	if (strlen($btn_acao) > 0) {

		if (strlen($posto_codigo) > 0) {
			$cond1 = " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo'";
		}

		if(strlen(trim($tipo_atendimento))>0){
			$cond_tipo_atendimento = " AND tbl_os.tipo_atendimento = $tipo_atendimento  ";
		}

		if(strlen(trim($produto_referencia)) > 0){
			$cond_produto = " AND tbl_os.produto = $produto_id ";
		}

		if(strlen(trim($tipo_atendimento))> 0  OR strlen(trim($produto_id))> 0 ){
			$cond_tbl_os = " JOIN tbl_os_extra ON tbl_os_extra.extrato = tbl_extrato.extrato
							 JOIN tbl_os ON tbl_os.os = tbl_os_extra.os AND tbl_os.fabrica = $login_fabrica ";
		}

		if(strlen($data_inicial) > 0 && strlen($data_final) > 0){

			if($data_inicial){
				$dat = explode ("/", $data_inicial);
				$d = $dat[0];
				$m = $dat[1];
				$y = $dat[2];
				if( (!checkdate($m,$d,$y)) ){
					$msg_erro["msg"][] = traduz("Data Inválida");
					$msg_erro["campos"][] = "data";
				}
			}

			if($data_final){
				$dat = explode ("/", $data_final);
				$d = $dat[0];
				$m = $dat[1];
				$y = $dat[2];
				if((!checkdate($m,$d,$y)) ){
					$msg_erro["msg"][] = traduz("Data Inválida");
					$msg_erro["campos"][] = "data";
				}
			}

		}else{
			$msg_erro["msg"][] = traduz("Preencha todos os campos obrigatórios");
			$msg_erro["campos"][] = "data";
		}

		if(count($msg_erro["msg"]) == 0){
			$data_inicial = str_replace (" " , "" , $data_inicial);
			$data_inicial = str_replace ("-" , "" , $data_inicial);
			$data_inicial = str_replace ("/" , "" , $data_inicial);
			$data_inicial = str_replace ("." , "" , $data_inicial);

			$data_final   = str_replace (" " , "" , $data_final)  ;
			$data_final   = str_replace ("-" , "" , $data_final)  ;
			$data_final   = str_replace ("/" , "" , $data_final)  ;
			$data_final   = str_replace ("." , "" , $data_final)  ;

			if (strlen ($data_inicial) == 6) $data_inicial = substr ($data_inicial,0,4) . "20" . substr ($data_inicial,4,2);
			if (strlen ($data_final)   == 6) $data_final   = substr ($data_final  ,0,4) . "20" . substr ($data_final  ,4,2);

			if (strlen ($data_inicial) > 0)  $data_inicial = substr ($data_inicial,0,2) . "/" . substr ($data_inicial,2,2) . "/" . substr ($data_inicial,4,4);
			if (strlen ($data_final)   > 0)  $data_final   = substr ($data_final,0,2)   . "/" . substr ($data_final,2,2)   . "/" . substr ($data_final,4,4);
		}
	}

	$layout_menu = "financeiro";
	$title = traduz("RELATÓRIO DE VALORES DE EXTRATOS");

	include 'cabecalho_new.php';

	$plugins = array(
		"mask",
		"datepicker",
		"shadowbox",
		"dataTable",
		"autocomplete"
	);

	include "plugin_loader.php";

	?>

	<script>
		$(function() {
			$.datepickerLoad(Array("data_final", "data_inicial"));
			$.autocompleteLoad(Array("posto","peca"));
			Shadowbox.init();

			$("span[rel=descricao_posto]").click(function () {
				$.lupa($(this));
			});

			$("span[rel=codigo_posto]").click(function () {
				$.lupa($(this));
			});

			$("span[rel=lupa]").click(function () {
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
	        $("#produto_id").val(retorno.produto);
	    }


	</script>

	<?php

	/* if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {
		include "gera_relatorio_pararelo.php";
	} */

	/* if ($gera_automatico != 'automatico' and strlen($msg_erro) == 0) {
	 include "gera_relatorio_pararelo_verifica.php";
	} */

	?>

	<?php
	if (count($msg_erro["msg"]) > 0) {
	?>
		<br />
		<div class="alert alert-error"><h4><?=implode("<br />", $msg_erro["msg"])?></h4></div>
	<?php
	} else {
		echo "<br />";
	}

	?>
	<div class="alert"><h4><?=traduz('O relatório considera a data de geração do extrato.')?></h4></div>

	<div class="row"> <b class="obrigatorio pull-right"> * <?=traduz('Campos obrigatórios')?> </b> </div>

	<!-- FORMULÁRIO DE PESQUISA -->
	<form name='frm_relatorio' method='POST' action='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>

		<div class='titulo_tabela'><?=traduz('Parâmetros de Pesquisa')?></div> <br/>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span3'>
		        <div class='control-group <?=(in_array('data', $msg_erro['campos'])) ? "error" : "" ?>'>
		            <label class='control-label' for='data_inicial'><?=traduz('Data Início')?></label>
		            <div class='controls controls-row'>
		                <div class='span10  '>
		                    <h5 class='asteristico'>*</h5>
		                    <input type='text' id='data_inicial' name='data_inicial' class='span12 ' maxlength='10' value="<?php echo $data_inicial; ?>" />
		                </div>
		            </div>
		        </div>
		    </div>
	        <div class='span5'>
		        <div class='control-group <?=(in_array('data', $msg_erro['campos'])) ? "error" : "" ?>'>
		            <label class='control-label' for='data_final'><?=traduz('Data Final')?></label>
		            <div class='controls controls-row'>
		                <div class='span6  '>
		                    <h5 class='asteristico'>*</h5>
		                    <input type='text' id='data_final' name='data_final' class='span12 ' maxlength='10' value="<?php echo $data_final; ?>" />
		                </div>
		            </div>
		        </div>
		    </div>
		</div>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span3'>
	            <div class='control-group '>
	                <label class='control-label' for='codigo_posto'><?=traduz('Código Posto')?></label>
	                <div class='controls controls-row'>
	                    <div class='span10  input-append'>
	                        <input type='text' id='codigo_posto' name='posto_codigo' class='span12 ' value="<?php echo $posto_codigo; ?>" />
	                        <span class='add-on' rel='codigo_posto' ><i class='icon-search'></i></span>
	                        <input type='hidden' name='lupa_config' tipo='posto' parametro='codigo'  />
	                    </div>
	                </div>
	            </div>
	        </div>
	        <div class='span5'>
	            <div class='control-group '>
	                <label class='control-label' for='descricao_posto'><?=traduz('Posto')?></label>
	                <div class='controls controls-row'>
	                    <div class='span10  input-append'>
	                        <input type='text' id='descricao_posto' name='posto_nome' class='span12' value="<?php echo $posto_nome; ?>" />
	                        <span class='add-on' rel='descricao_posto' ><i class='icon-search'></i></span>
	                        <input type='hidden' name='lupa_config' tipo='posto' parametro='nome'  />
	                    </div>
	                </div>
	            </div>
	        </div>
	    </div>
	<? if(in_array($login_fabrica, array(148))){ ?>
	    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span3'>
            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='produto_referencia'><?=traduz('Ref. Produto')?></label>
                <div class='controls controls-row'>
                    <div class='span10 input-append'>
                        <input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span5'>
            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='produto_descricao'><?=traduz('Descrição Produto')?></label>
                <div class='controls controls-row'>
                    <div class='span10 input-append'>
                        <input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
                        <input type="hidden" id="produto_id" name="produto_id" class='span12' value="<? echo $produto_id ?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <? } if(in_array($login_fabrica, array(148))){ ?>
    <div class='row-fluid'>
			<div class='span2'></div>
			<div class='span3'>
	            <div class='control-group '>
	                <label class='control-label' for='tipo_atendimento'><?=traduz('Tipo Atendimento')?></label>
	                <div class='controls controls-row'>
	                   <select class='form-control span10' name='tipo_atendimento' id="tipo_atendimento" >
							<option></option>
							<?php
							$sql = " select descricao, tipo_atendimento from tbl_tipo_atendimento where fabrica = $login_fabrica";
							$res = pg_query($con, $sql);
							for($i=0; $i<pg_num_rows($res); $i++){
								$tipo_atendimento = pg_fetch_result($res, $i, tipo_atendimento);
								$descricao = pg_fetch_result($res, $i, descricao);

								echo "<option value='$tipo_atendimento'>$descricao</option>";
							}
							?>
						</select>
	                </div>
	            </div>
	        </div>
	    </div>

	    <?php
	}
	    if(in_array($login_fabrica, array(142))){

	    ?>

	    <div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
	            <div class='control-group '>
	                <label class='control-label' for='estado'>Estado</label>
	                <div class='controls controls-row'>
	                   <select class='form-control span10' name='estado' id="estado" >
							<option></option>
							<?php
							$arrayEstados = array(
												"AC" => "Acre",
												"AL" => "Alagoas",
												"AM" => "Amazonas",
												"AP" => "Amapá",
												"BA" => "Bahia",
												"CE" => "Ceará",
												"DF" => "Distrito Federal",
												"ES" => "Espírito Santo",
												"GO" => "Goiás",
												"MA" => "Maranhão",
												"MG" => "Minas Gerais",
												"MS" => "Mato Grosso do Sul",
												"MT" => "Mato Grosso",
												"PA" => "Pará",
												"PB" => "Paraíba",
												"PE" => "Pernambuco",
												"PI" => "Piauí",
												"PR" => "Paraná",
												"RJ" => "Rio de Janeiro",
												"RN" => "Rio Grande do Norte",
												"RO" => "Rondônia",
												"RR" => "Roraima",
												"RS" => "Rio Grande do Sul",
												"SC" => "Santa Catarina",
												"SE" => "Sergipe",
												"SP" => "São Paulo",
												"TO" => "Tocantins"
											);

							foreach ($arrayEstados as $sigla => $nome) {
								$selected = ($sigla == $_POST["estado"]) ? "selected" : "";
								echo "<option value='{$sigla}' {$selected} >{$nome}</option>";
							}
							?>
						</select>
	                </div>
	            </div>
	        </div>
	    </div>

	    <?php

	    }

	    ?>

	    <div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
	            <div class='control-group checkbox'>
	            	<label>
	            		<input type="checkbox" name="agrupar" value='sim' <?if(strlen($agrupar)>0) echo "checked"?> > <?=traduz('Agrupar por Posto')?>
	           		</label>
	           	</div>
	        </div>
	        <div class='span4'>
	            <div class='control-group checkbox'>
	            	<label>
	            		<input type="checkbox" name="pago" value='sim' <?if(strlen($pago)>0) echo "checked"?> > <?=traduz('Aprovados para Pagamento')?>
	            	</label>
	           	</div>
	        </div>
	        <div class='span2'></div>

	    <?php

	    if(in_array($login_fabrica, array(45))){

	    ?>

			<div class='span2'></div>
			<div class='span4' style="margin-left: 0px;">
	            <div class='control-group checkbox'>
	            	<label>
	            		<input type="checkbox" name="nota_sem_baixa" value='sim' <?if(strlen($nota_sem_baixa) > 0) echo "checked"?> > Extratos com Nota Fiscal sem baixa
	           		</label>
	           	</div>
	        </div>
	        <div class='span4'>
	            <div class='control-group checkbox'>
	            	<label>
	            		<input type="checkbox" name="nota_com_baixa" value='sim' <?if(strlen($pago) > 0 )echo "checked"?> > Extratos com Nota Fiscal baixado
	            	</label>
	           	</div>
	        </div>

	    <?php

	    }

	     ?>

	    </div>

		<?php if (in_array($login_fabrica, array(101))) {?>
	    <div class='row-fluid' style="margin-top: -60px;">
			<div class='span2'></div>
			<div class='span4'>
	            <div class='control-group checkbox'>
	            	<label>
	            		<input type="checkbox" name="extrato_pago" value='sim' <?php if ($extrato_pago == 'sim') echo "checked";?>> Extratos Pagos
	           		</label>
	           	</div>
	        </div>
	        <div class='span4'>
	            <div class='control-group checkbox'>
	            	<label>
	            		<input type="checkbox" name="extrato_nao_pago" value='sim' <?php if ($extrato_nao_pago == 'sim') echo "checked";?>> Extratos não Pagos
	            	</label>
	           	</div>
	        </div>
			<div class='span2'></div>
	    </div>
		<?php }?>


		<p>
			<button class='btn' id="btn_acao" type="submit" ><?=traduz('Pesquisar')?></button>
			<input type='hidden' name='btn_acao' value='consultar' />
		</p>

		<br/> <br>

	</form>

</div>

<?php
//--=== RESULTADO DA PESQUISA ====================================================--\\
	flush();

	if (strlen($btn_acao) > 0 AND strlen($msg_erro) == 0) {

		$min_width = (in_array($login_fabrica, array(104))) ? "1200px" : "920px";

		/*nao agrupado  takashi 21-12 HD 916*/
		if (strlen($data_inicial) > 0 AND strlen($data_final) > 0 AND $agrupar <> 'sim') {

			if (strlen ($data_inicial) < 8)
				$data_inicial = date ("d/m/Y");

			$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);

			if (strlen ($data_final) < 8)
				$data_final = date ("d/m/Y");

			$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);

			$sql = "SELECT  tbl_posto.nome,
							tbl_posto_fabrica.contato_estado,
							tbl_posto_fabrica.contato_cidade,
							tbl_posto_fabrica.codigo_posto,";

			//DADOS BANCARIOS NKS HD 8190 Gustavo 29/11/2007
			if ($login_fabrica == 45 || $login_fabrica == 40) {
				$sql .= "tbl_posto.cnpj,
						 tbl_posto_fabrica.banco,
						 tbl_posto_fabrica.agencia,
						 tbl_posto_fabrica.conta,
						 tbl_posto_fabrica.favorecido_conta,
						 tbl_posto_fabrica.conta_operacao,";
			}

			if(in_array($login_fabrica, array(104))){

				$sql .= "
					CASE
					WHEN tbl_marca.nome = 'DWT' THEN
						'DWT'
					ELSE
						'OVD'
					END AS marca,
					tbl_extrato_pagamento.extrato_pagamento,
				";

			}

			//HD-15422
			if ($login_fabrica == 20) {
				$sql.= "tbl_escritorio_regional.descricao AS escritorio_regional,";
			}
			if (in_array($login_fabrica, array(91,148,158,183))) {
				$sql.= "tbl_extrato.deslocamento AS total_km,";
			}

			if (in_array($login_fabrica, [85,104])) {

				$dataPagamentoCampo = "TO_CHAR(tbl_extrato_pagamento.data_pagamento ,'DD/MM/YYYY') AS data_pagemento,";
			}

			$sql.=  "tbl_posto_fabrica.reembolso_peca_estoque,
					TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao,
					tbl_extrato.extrato,
					tbl_extrato.protocolo,
					tbl_extrato.mao_de_obra,
					tbl_extrato.valor_adicional,
					tbl_extrato.pecas,
					tbl_extrato.avulso,
					tbl_extrato.total,
					tbl_extrato.deslocamento,
					{$dataPagamentoCampo}
					(0) AS total_os
				INTO TEMP tmp_extrato_pagamento /* hd 39502 */
				FROM tbl_extrato
				JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato
				JOIN tbl_posto         ON tbl_posto.posto           = tbl_extrato.posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto   = tbl_extrato.posto
							AND tbl_posto_fabrica.fabrica = $login_fabrica
							$cond1
							$cond_tbl_os


				";			
			//HD-15422
			if ($login_fabrica == 20) {
				$sql .= "LEFT JOIN tbl_escritorio_regional using (escritorio_regional)    ";
			}

			if ($login_fabrica == 85) {

				$sql .= " LEFT JOIN tbl_extrato_pagamento ON tbl_extrato_pagamento.extrato = tbl_extrato.extrato ";
			}

			if($login_fabrica == 104){

				$sql .= "
				LEFT JOIN tbl_os_extra ON tbl_os_extra.extrato = tbl_extrato.extrato
				JOIN tbl_os ON tbl_os_extra.os = tbl_os.os AND tbl_os.fabrica = $login_fabrica
				JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $login_fabrica
				JOIN tbl_marca ON tbl_produto.marca = tbl_marca.marca AND tbl_marca.fabrica = $login_fabrica
				LEFT JOIN tbl_extrato_pagamento ON tbl_extrato_pagamento.extrato = tbl_extrato.extrato
				";

			}

			if ($login_fabrica == 101 && strlen(trim($extrato_pago)) > 0) {
				$sql .= " LEFT JOIN tbl_extrato_pagamento ON tbl_extrato_pagamento.extrato = tbl_extrato.extrato ";
			}

			if ($login_fabrica == 101 && strlen(trim($extrato_nao_pago)) > 0) {
				$sql .= " LEFT JOIN tbl_extrato_pagamento ON tbl_extrato_pagamento.extrato = tbl_extrato.extrato ";
			}

			if ($login_fabrica == 45) {
				$sql .= "LEFT JOIN tbl_extrato_pagamento ON tbl_extrato_pagamento.extrato = tbl_extrato.extrato    ";
			}

			$sql .=  "WHERE tbl_extrato.fabrica = $login_fabrica
			$cond_tipo_atendimento
			$cond_produto ";


			if ($login_fabrica == 101 && strlen(trim($extrato_pago)) > 0) {
				$sql .= " AND tbl_extrato_pagamento.data_pagamento IS NOT NULL ";
			}

			if ($login_fabrica == 101 && strlen(trim($extrato_nao_pago)) > 0) {
				$sql .= " AND tbl_extrato_pagamento.data_pagamento IS NULL ";
			}

			if (strlen($pago) > 0)
				$sql .= " AND tbl_extrato.aprovado IS NOT NULL";

			if (strlen ($data_inicial) < 8)
				$data_inicial = date ("d/m/Y");

			$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);

			if (strlen ($data_final) < 8)
				$data_final = date ("d/m/Y");

			$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);

			if ($login_fabrica == 45) {

				if ($nota_sem_baixa=='sim') {
					$sql .= " AND tbl_extrato_pagamento.nf_autorizacao IS NOT NULL
							  AND tbl_extrato_pagamento.data_pagamento IS NULL    ";
				} elseif ($nota_com_baixa=='sim') {
					$sql .= " AND tbl_extrato_pagamento.nf_autorizacao IS NOT NULL
							  AND tbl_extrato_pagamento.data_pagamento IS NOT NULL    ";
				} else {
					$sql .= " AND tbl_extrato_pagamento.nf_autorizacao IS NOT NULL    ";
					$sql .= " AND tbl_extrato_pagamento.data_pagamento IS NULL    ";
				}

			}

			if ($login_fabrica == 142 && !empty($_POST["estado"])) {
				$estado = strtoupper($_POST["estado"]);
				$sql .= " AND UPPER(tbl_posto_fabrica.contato_estado) = '{$estado}' ";
			}

			if ($login_fabrica <> 20) {
				if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
					$sql .= " AND tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
			} else {
				if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
					$sql .= " AND tbl_extrato_extra.exportado BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
			}

			$sql .= " ORDER BY tbl_posto.nome";
			@$res = pg_query($con, $sql);

			/* hd 39502 */
			if ($login_fabrica == 20) {
				$sql = "ALTER table tmp_extrato_pagamento add column total_cortesia double precision";
				@$res = pg_query ($con,$sql);

				$sql = "UPDATE tmp_extrato_pagamento SET
							total_cortesia = (
								SELECT sum(tbl_os.mao_de_obra) + sum(tbl_os.pecas)
								FROM tbl_os
								JOIN tbl_os_extra USING(os)
								WHERE extrato = tmp_extrato_pagamento.extrato
								AND   tbl_os.tipo_atendimento = 16
							)";
				@$res = pg_query ($con,$sql);
			}

			$sql = "SELECT distinct * FROM tmp_extrato_pagamento";
			$res = pg_query ($con,$sql);

			/*SELECT DO TOTAL GERAL DOS EXTRATOS 21/12/2007 HD 9983
			****************************************/
			if ($login_fabrica == 5) {

				$sqlx .= "SELECT
						SUM(tbl_extrato.total) AS total_geral
					FROM tbl_extrato
					JOIN tbl_extrato_extra
					ON tbl_extrato_extra.extrato = tbl_extrato.extrato
					JOIN tbl_posto ON tbl_posto.posto = tbl_extrato.posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_extrato.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					WHERE tbl_extrato.fabrica = $login_fabrica";

				if (strlen ($data_inicial) < 8)
					$data_inicial = date ("d/m/Y");

				$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);

				if (strlen($data_final) < 8)
					$data_final = date ("d/m/Y");

				$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);

				if ($login_fabrica <> 20) {
					if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
						$sqlx .= " AND tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
				} else {
					if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
						$sqlx .= " AND tbl_extrato_extra.exportado BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
				}
				//echo "<br>".$sqlx;
				$samuel = pg_query($con,"/* QUERY -> $sqlx */");
				$resx = pg_query($con, $sqlx);

				if (pg_num_rows($resx) > 0) {
					$total_geral = trim(pg_fetch_result($resx,0,total_geral));
					$total_geral = number_format($total_geral,2,",",".");
				}

			}
			/****************************************/

				$codigo_aux = (in_array($login_fabrica, array(104))) ? " / CNPJ " : "";

				if (pg_num_rows($res) > 0) {

					$data = date ("dmY");

					echo "<div id='id_download' class='tac'><img src='imagens/excell.gif'> <a href='xls/relatorio_pagamento_posto-$login_fabrica.$data.xls'>".traduz("Clique aqui para fazer o download do arquivo em EXCEL")."</a></div> <br />";

					/*
					echo "<br><center><a href='extrato_pagamento.php?agrupar=sim&btnacao=filtrar&data_inicial=$data_inicial&data_final=$data_final'><font face='Arial, Verdana, Times, Sans' size='1' color='#000000'>Agrupar por posto</font></a></center>";
					*/
					echo "<table id='resultado' class='table table-striped table-bordered' style='min-width: {$min_width}; margin: 0 auto;'>";

					echo "<thead>";
					//HD 9983 26/12/2007
					if ($login_fabrica == 5) {
						echo "<tr class='titulo_tabela' align='center'>";
						echo "<td colspan='8' align='right'>Valor Total</td>";
						echo "<td colspan='2'>$total_geral</td>";
						echo "</tr>";
					}

					echo "<tr class='titulo_coluna'>";
					echo "<td >".traduz("Código")." {$codigo_aux}</td>";
					echo "<td nowrap>".traduz("Nome")."</td>";

					//DADOS BANCARIOS NKS HD 8190 Gustavo 29/11/2007
					if ($login_fabrica == 45 || $login_fabrica == 40) {
						echo "<td >CNPJ</td>";
						echo "<td >Banco</td>";
						echo "<td >Agência</td>";
						echo "<td >Conta</td>";
						echo "<td >Favorecido</td>";
						echo "<td >Operação</td>";
					}

					//HD-15422

					if(in_array($login_fabrica, array(104))){
						echo "<td>Cidade</td>";
						echo "<td>Data da baixa</td>";
					}

					echo ($login_fabrica == 20 ) ? "<td>ER</td>" : "<td >UF</td>";

					echo ($login_fabrica == 1 ) ? "<td>Pedido<BR>Garantia</td>" : "";

					echo "<td >".traduz("Extrato")."</td>";
					echo "<td>Geração</td>";
					if (in_array($login_fabrica, array(91,148,158,183))) {
						if($login_fabrica == 91){
							echo "<td>KM</td>";
						}
						echo "<td>Valor KM</td>";
					}

					echo "<td nowrap>".traduz("Valor")." de M.O</td>";
					if (!in_array($login_fabrica, array(157,158)) && $extrato_sem_peca != "t") {
						echo "<td nowrap>".traduz("Valor de Peças")."</td>";
						echo "<td nowrap>Avulso</td>";
					}

					if (in_array($login_fabrica, array(158))) {
						$avulso = empty($avulso) ? "0,00" : $avulso;
						echo "<td nowrap>Valor Adicional</td>";
						echo "<td nowrap>".traduz("Avulso")."</td>";
					}

					if(in_array($login_fabrica, array(104))){
						echo "<td>Empresa</td>";
					}

					if (in_array($login_fabrica, array(52,74,157))) echo  '<td>KM</td>';
					//hd 39502
					if ($login_fabrica == 20) {
						echo "<td nowrap>TOTAL Cortesia</td>";
						echo "<td nowrap>Total Geral</td>";
					} else {
						echo "<td nowrap>".traduz("Valor Total")."</td>";
					}

					if(!in_array($login_fabrica, array(104))){
						echo "<td nowrap>".traduz("Total OS")."</td>";
					}

					if ($login_fabrica == 85) echo "<td>Data</td>";

					if(in_array($login_fabrica, array(104))){
						echo "<td>Status</td>";
					}

					echo "</tr>";

					echo "</thaed>";

					flush();

					echo `rm /tmp/assist/relatorio_pagamento_posto-$login_fabrica.xls`;

					$fp = fopen ("/tmp/assist/relatorio_pagamento_posto-$login_fabrica.html","w");

					fputs($fp,"<html>");
					fputs($fp,"<head>");
					fputs($fp,"<title>RELATÓRIO DE VALORES DE EXTRATOS - $data");
					fputs($fp,"</title>");
					fputs($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
					fputs($fp,"</head>");
					fputs($fp,"<body>");

					fputs($fp,"<br><table border='0' cellpadding='2' cellspacing='0'class='formulario' align='center'>");
					fputs($fp,"<tr class='titulo_coluna'>");
					fputs($fp,"<td >Código {$codigo_aux} </td>");
					fputs($fp,"<td >Nome Posto</td>");

					//DADOS BANCARIOS NKS HD 8190 Gustavo 29/11/2007
					if ($login_fabrica == 45 || $login_fabrica == 40) {
						fputs($fp,"<td >CNPJ</td>");
						fputs($fp,"<td >Banco</td>");
						fputs($fp,"<td >Agência</td>");
						fputs($fp,"<td >Conta</td>");
						fputs($fp,"<td >Favorecido</td>");
						fputs($fp,"<td >Operação</td>");
					}

					//HD-15422
					if ($login_fabrica == 20) {
						fputs($fp,"<td >ER</td>");
					} else {
						if(in_array($login_fabrica, array(104))){
							fputs($fp,"<td >Cidade</td>");
							fputs($fp,"<td >Data da baixa</td>");
						}
						fputs($fp,"<td >UF</td>");
					}

					if($login_fabrica == 1 ) fputs($fp,"<td>Pedido<BR>Garantia</td>");

					fputs($fp,"<td>Extrato</td>");

					fputs($fp,"<td>Geração</td>");
					if (in_array($login_fabrica, array(91,148,158,183))) {
						if($login_fabrica == 91){
							fputs($fp,"<td>KM</td>");
						}
						fputs($fp,"<td>Valor KM</td>");
					}
					fputs($fp,"<td nowrap>Valor de M.O</td>");
					if (!in_array($login_fabrica, array(157,158)) && $extrato_sem_peca != "t") {
						fputs($fp,"<td nowrap>Valor de Peças</td>");
						fputs($fp,"<td nowrap>Avulso</td>");
					}

					if (in_array($login_fabrica, array(158))) {
						$avulso = empty($avulso) ? "0,00" : $avulso;
						fputs ($fp,'<td>Valor Adicional</td>') ;
						fputs($fp,"<td nowrap>Avulso</td>");
					}

					if(in_array($login_fabrica, array(104))){
						fputs($fp,"<td nowrap>Empresa</td>");
					}

					if (in_array($login_fabrica, array(52,74,157))) {
						fputs ($fp,'<td>KM</td>') ;
					}
					//hd 39502
					if ($login_fabrica == 20) {
						fputs($fp,"<td nowrap>Total Cortesia</td>");
						fputs($fp,"<td nowrap>Total Geral</td>");
					} else {
						fputs($fp,"<td nowrap>Valor Total</td>");
					}

					if(!in_array($login_fabrica, array(104))){
						fputs($fp,"<td nowrap>Total OS</td>");
					}

					if ($login_fabrica == 85) fputs($fp,"<td>Data</td>");

					if(in_array($login_fabrica, array(104))){
						fputs($fp,"<td>Status</td>");
					}

					fputs($fp,"</tr>");

					echo "<tbody>";

					for ($i = 0; $i < pg_num_rows($res); $i++) {

						$nome = trim(pg_fetch_result($res,$i,nome));

						//HD-15422
						if ($login_fabrica == 20) {
							$escritorio_regional = trim(pg_fetch_result($res,$i,escritorio_regional));
						}

						$estado       = trim(pg_fetch_result($res,$i,contato_estado));
						$codigo_posto = trim(pg_fetch_result($res,$i,codigo_posto));

						if ($login_fabrica == 85) {

							$dataPagamento = trim(pg_fetch_result($res, $i, "data_pagemento"));
						}

						if(in_array($login_fabrica, array(104))){
							$cidade    = trim(pg_fetch_result($res, $i, "contato_cidade"));
							$dataBaixa = trim(pg_fetch_result($res, $i, "data_pagemento"));
							$empresa   = trim(pg_fetch_result($res, $i, "marca"));
							$status    = trim(pg_fetch_result($res, $i, "extrato_pagamento"));
							$status    = (strlen($status) == 0) ? "Pendente" : "Pago";
						}

						//DADOS BANCARIOS NKS HD 8190 Gustavo 29/11/2007
						if ($login_fabrica == 45 || $login_fabrica == 40) {
							$cnpj = trim(pg_fetch_result($res,$i,cnpj));
							if (strlen($cnpj) == 14)
								$cnpj = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
							if (strlen($cnpj) == 11)
								$cnpj = substr($cnpj,0,3) .".". substr($cnpj,3,3) .".". substr($cnpj,6,3) ."-". substr($cnpj,9,2);
							$banco            = trim(pg_fetch_result($res,$i,banco));
							$agencia          = trim(pg_fetch_result($res,$i,agencia));
							$conta            = trim(pg_fetch_result($res,$i,conta));
							$favorecido_conta = trim(pg_fetch_result($res,$i,favorecido_conta));
							$conta_operacao   = trim(pg_fetch_result($res,$i,conta_operacao));
						}

						$extrato         = trim(pg_fetch_result($res,$i,extrato));
						$protocolo       = trim(pg_fetch_result($res,$i,protocolo));
						$data_geracao    = trim(pg_fetch_result($res,$i,data_geracao));
						$mao_de_obra     = trim(pg_fetch_result($res,$i,mao_de_obra));
						$pecas           = trim(pg_fetch_result($res,$i,pecas));
						$valor_adicional = trim(pg_fetch_result($res,$i,valor_adicional));
						$avulso          = trim(pg_fetch_result($res,$i,avulso));
						$deslocamento    = trim(pg_fetch_result($res,$i,deslocamento));

						if ($login_fabrica == 20) {
							$total_cortesia = trim(pg_fetch_result($res,$i,total_cortesia));
						}

						$total              = trim(pg_fetch_result($res,$i,total));
						$total_os           = trim(pg_fetch_result($res,$i,total_os));
						$pedido_em_garantia = trim(pg_fetch_result($res,$i,reembolso_peca_estoque));
						$total_km           = trim(pg_fetch_result($res,$i, total_km));

						$pedido_em_garantia = ($pedido_em_garantia=='t') ? "Sim" : "Não";

						$sql1 = "SELECT count(*) AS total_os
								 FROM tbl_os_extra
								 WHERE tbl_os_extra.extrato = $extrato";
						$res1 = pg_query($con, $sql1);

						$total_os        = trim(pg_fetch_result($res1,0,total_os));
						$pecas           = (!empty($pecas)) ? number_format($pecas,2,",",".") : "0,00";
						$mao_de_obra     = number_format($mao_de_obra,2,",",".");
						$valor_adicional = number_format($valor_adicional,2,",",".");
						$avulso          = number_format($avulso,2,",",".");
						$deslocamento    = number_format($deslocamento,2,",",".");
						$auxTotal        = number_format($total,2,",",".");
						$total_km        = number_format($total_km,2,",",".");

						if ($login_fabrica == 20) {
							$total_cortesia = number_format($total_cortesia,2,",",".");
						}

						echo "<tr align='center'>";
						echo "<td>$codigo_posto</td>";
						echo "<td align='left' title='$nome'>".substr($nome,0,20)."</td>";

						//DADOS BANCARIOS NKS HD 8190 Gustavo 29/11/2007
						if ($login_fabrica == 45 || $login_fabrica == 40) {
							echo "<td>$cnpj</td>";
							echo "<td>$banco</td>";
							echo "<td>$agencia</td>";
							echo "<td>$conta</td>";
							echo "<td>$favorecido_conta</td>";
							echo "<td>$conta_operacao</td>";
						}

						if(in_array($login_fabrica, array(104))){
							echo "<td> {$cidade} </td>";
							echo "<td> {$dataBaixa} </td>";
						}

						//HD-15422
						echo "<td>";
						echo ($login_fabrica == 20) ? $escritorio_regional : $estado;
						echo "</td>";
						echo ($login_fabrica == 1) ? "<td>$pedido_em_garantia</td>" : "";
						echo "<td>";
						echo ($login_fabrica == 1) ? $protocolo : $extrato;
						echo "</td>";

						echo "<td>$data_geracao</td>";
						if (in_array($login_fabrica, array(91,148,158,183))) {
							$qtde_km    = 0;
							$sql_km = "SELECT sum(tbl_os.qtde_km) as qtde_km
										FROM tbl_os join tbl_os_extra using(os)
										WHERE extrato = $extrato;";
							$res_km = pg_query($con,$sql_km);

							$qtde_km    = trim(pg_fetch_result($res_km,0,qtde_km));
							if (!strlen($qtde_km)) {
								$qtde_km    = 0;
							}
							if($login_fabrica == 91){
								echo "<td>$qtde_km</td>";
							}
							echo "<td> $real $total_km </td>";
						}
						echo "<td align='right'>$real $mao_de_obra</td>";
						if (!in_array($login_fabrica, array(157,158)) && $extrato_sem_peca != "t") {

							echo "<td align='right'>$real  $pecas</td>";

							echo "<td align='right'>$real $avulso</td>";
						}
						if (in_array($login_fabrica, array(158))) {
							$avulso = empty($avulso) ? "0,00" : $avulso;
							echo "<td align='right'>$real $valor_adicional</td>";
							echo "<td align='right'>$real $avulso</td>";
						}

						if(in_array($login_fabrica, array(104))){
							echo "<td> {$empresa} </td>";
						}

						if (in_array($login_fabrica, array(52,74,157))) echo "<td align='right'>$real $deslocamento</td>";
						//hd 39502

						echo ($login_fabrica==20) ? "<td align='right'>$real $total_cortesia</td>" : "";

						echo "<td align='right'>$real $auxTotal</td>";

						if(!in_array($login_fabrica, array(104))){
							echo "<td class='tac'>$total_os</td>";
						}

						if ($login_fabrica == 85) echo "<td> $dataPagamento </td>";

						if(in_array($login_fabrica, array(104))){
							echo "<td> $status </td>";
						}

						echo "</tr>";

						$total_mo   += $mao_de_obra;
						$total_todo += $total;

						fputs($fp,"<tr class='Conteudo'>");
						fputs($fp,"<td >$codigo_posto</td>");
						fputs($fp,"<td align='left' title='nome' nowrap>".substr($nome,0,20)."</td>");

						//DADOS BANCARIOS NKS HD 8190 Gustavo 29/11/2007
						if ($login_fabrica == 45 || $login_fabrica == 40) {
							fputs($fp,"<td>$cnpj</td>");
							fputs($fp,"<td>$banco</td>");
							fputs($fp,"<td>$agencia</td>");
							fputs($fp,"<td>$conta</td>");
							fputs($fp,"<td>$favorecido_conta</td>");
							fputs($fp,"<td>$conta_operacao</td>");
						}

						//HD-15422
						if ($login_fabrica == 20) {
							fputs($fp,"<td>$escritorio_regional</td>");
						} else {
							if(in_array($login_fabrica, array(104))){
								fputs($fp,"<td>$cidade</td>");
								fputs($fp,"<td>$dataBaixa</td>");
							}
							fputs($fp,"<td>$estado</td>");
						}
						if($login_fabrica == 1 ) fputs($fp,"<td >$pedido_em_garantia</td>");

						fputs($fp,"<td>");
						if ($login_fabrica == 1) fputs($fp,$protocolo);
						else                     fputs($fp,$extrato);
						fputs($fp,"</td>");


						fputs($fp,"<td>$data_geracao</td>");
						if (in_array($login_fabrica, array(91,148,158,183))) {
							if($login_fabrica == 91){
								fputs($fp,"<td>$qtde_km</td>");
							}
							fputs($fp,"<td>$real $total_km</td>");
						}
						fputs($fp,"<td align='right' nowrap>$real $mao_de_obra</td>");
						if (!in_array($login_fabrica, array(157,158))) {
							fputs($fp,"<td align='right' nowrap>$real $pecas</td>");
							fputs($fp,"<td align='right' nowrap>$real $avulso</td>");
						}
						if (in_array($login_fabrica, array(158))) {
							$avulso = empty($avulso) ? "0,00" : $avulso;
							fputs($fp,"<td align='right' nowrap>$real  $valor_adicional</td>");
							fputs($fp,"<td align='right' nowrap>$real  $avulso</td>");
						}
						if (in_array($login_fabrica, array(52, 74, 157))) {
							fputs($fp,"<td align='right' nowrap>$real  $deslocamento</td>");
						}
						//hd 39502
						if ($login_fabrica==20) {
							fputs($fp,"<td align='right' nowrap>$real $total_cortesia</td>");
						}

						if(in_array($login_fabrica, array(104))){
							fputs($fp,"<td> {$empresa} </td>");
						}

						fputs($fp,"<td align='right' nowrap>$real $auxTotal</td>");

						if(!in_array($login_fabrica, array(104))){
							fputs($fp,"<td align='center'>$total_os</td>");
						}

						if ($login_fabrica == 85) fputs($fp,"<td> $dataPagamento </td>");

						if($login_fabrica == 104){
							fputs($fp,"<td> $status </td>");
						}

						fputs($fp,"</tr>");
						flush();

						$tot_dtde_km     = $tot_dtde_km + $qtde_km;
						$tot_total_km    = $tot_total_km + $total_km;
						$tot_mao_de_obra = $tot_mao_de_obra +$mao_de_obra;
						$tot_pecas       = $tot_pecas + $pecas;
						$tot_avulso      = $tot_avulso + $avulso;
						$tot_total       = $tot_total + $total;
						$tot_total_os    = $tot_total_os + $total_os;

					}

					echo "</tbody>";

					$tot_total_km    = number_format($tot_total_km,2,",",".");
					$tot_mao_de_obra = number_format($tot_mao_de_obra,2,",",".");
					$tot_pecas       = number_format($tot_pecas,2,",",".");
					$tot_avulso      = number_format($tot_avulso,2,",",".");
					$tot_total       = number_format($tot_total,2,",",".");

					if ($login_fabrica == 91) {
						fputs($fp,"<tr class='titulo_coluna'>");
						fputs($fp,"<td colspan='5' align='left'> Totais: </td>");
						fputs($fp,"<td>$tot_dtde_km</td>");
						fputs($fp,"<td>$real $tot_total_km</td>");
						fputs($fp,"<td>$real $tot_mao_de_obra</td>");
						fputs($fp,"<td>$real $tot_pecas</td>");
						fputs($fp,"<td>$real $tot_avulso</td>");
						fputs($fp,"<td>$real $tot_total</td>");
						fputs($fp,"<td>$tot_total_os</td>");
						fputs($fp,"</tr>");

						echo "<tr class='titulo_coluna'>";
						echo "<td colspan='5' align='left'> Totais: </td>";
						echo "<td>$tot_dtde_km</td>";
						echo "<td>$real $tot_total_km</td>";
						echo "<td>$real $tot_mao_de_obra</td>";
						echo "<td>$real $tot_pecas</td>";
						echo "<td>$real $tot_avulso</td>";
						echo "<td>$real $tot_total</td>";
						echo "<td class='tac'>$tot_total_os</td>";
						echo "</tr>";
					}

					if ($login_fabrica == 50) { // HD 49535
						echo "<tfoot>";
						echo "<tr><td colspan='5' class='titulo_coluna' align='center'>Total</td>";
						echo "<td align='right' nowrap> " . $real .number_format($total_mo,2,",",".")."</td>";
						echo "<td colspan='2'>&nbsp;</td>";
						echo "<td align='right' nowrap> ". $real .number_format($total_todo,2,",",".")."</td>";
						echo "<td>&nbsp;</td>";
						echo "</tr>";
						echo "</tfoot>";
					}

					echo "</table>";

					fputs($fp,"</table>");
					fputs($fp,"</body>");
					fputs($fp,"</html>");
					fclose ($fp);


					echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f xls/relatorio_pagamento_posto-$login_fabrica.$data.xls /tmp/assist/relatorio_pagamento_posto-$login_fabrica.html`;
					echo "<script language='javascript'>";
					echo "document.getElementById('id_download').style.display='block';";
					echo "</script>";

					echo '<script>
							$.dataTableLoad({ table: "#resultado", aoColumns:[null,null,null,null,{"sType":"date"},{"sType":"numeric"},{"sType":"numeric"},{"sType":"numeric"},{"sType":"numeric"},{"sType":"numeric"}], aaSorting: [[5, "asc" ]] });
						</script>';

				}  else {
					echo "
						<div class='container'>
							<div class='alert alert-warning'><h4>Nenhum resultado encontrado!</h4></div>
						</div>
						<br />";
				}

			}
			/*nao agrupado  takashi 21-12 HD 916*/

			/*agrupado takashi 21-12 HD 916*/
			if (strlen($data_inicial) > 0 AND strlen($data_final) > 0 AND $agrupar == 'sim') {
			    $sql = "SELECT  distinct tbl_posto.posto                           AS id  ,
									tbl_posto_fabrica.codigo_posto as posto          ,
									tbl_posto_fabrica.reembolso_peca_estoque         ,
									tbl_posto.nome                            AS nome,";
				//DADOS BANCARIOS NKS HD 8190 Gustavo 29/11/2007
				if ($login_fabrica == 45 || $login_fabrica == 40) {
					$sql .= "		tbl_posto.cnpj                                   ,
									tbl_posto_fabrica.banco                          ,
									tbl_posto_fabrica.agencia                        ,
									tbl_posto_fabrica.conta                          ,
									tbl_posto_fabrica.favorecido_conta               ,
									tbl_posto_fabrica.conta_operacao                 ,";}
				//HD-15422
				if ($login_fabrica == 20) {
					$sql.= "		tbl_escritorio_regional.descricao as escritorio_regional,";
				} else {
					$sql.= "		tbl_posto_fabrica.contato_estado, tbl_posto_fabrica.contato_cidade, ";
				}
				if(in_array($login_fabrica, array(91, 148, 157, 158,183))) $sql.= "tbl_extrato.deslocamento as total_km,";


				if(in_array($login_fabrica, array(104))){

					$sql .= "
						CASE
						WHEN tbl_marca.nome = 'DWT' THEN
							'DWT'
						ELSE
							'OVD'
						END AS marca,
						tbl_extrato_pagamento.extrato_pagamento,
					";

				}

				$sql.= "			tbl_tipo_posto.descricao as tipo_posto,
									tbl_extrato.mao_de_obra as mao_de_obra,
									tbl_extrato.valor_adicional,
									tbl_extrato.pecas as pecas,
									tbl_extrato.avulso as avulso,
									tbl_extrato.total as total,
									(0) as total_os,
									tbl_extrato.extrato
						INTO TEMP tmp_val_ext_$login_fabrica
						FROM tbl_extrato
						JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato
						JOIN tbl_posto ON tbl_posto.posto = tbl_extrato.posto
						JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_extrato.posto
						AND tbl_posto_fabrica.fabrica = $login_fabrica
						$cond1
						JOIN tbl_tipo_posto on tbl_posto_fabrica.tipo_posto=tbl_tipo_posto.tipo_posto ";

				//HD-15422
				if ($login_fabrica == 20) {
					$sql.= " LEFT JOIN tbl_escritorio_regional using (escritorio_regional) ";
				}

				if($login_fabrica == 104){

					$sql .= "
					LEFT JOIN tbl_os_extra ON tbl_os_extra.extrato = tbl_extrato.extrato
					JOIN tbl_os ON tbl_os_extra.os = tbl_os.os AND tbl_os.fabrica = $login_fabrica
					JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $login_fabrica
					JOIN tbl_marca ON tbl_produto.marca = tbl_marca.marca AND tbl_marca.fabrica = $login_fabrica
					LEFT JOIN tbl_extrato_pagamento ON tbl_extrato_pagamento.extrato = tbl_extrato.extrato
					";

				}

				if ($login_fabrica == 45) {
					$sql.= " LEFT JOIN tbl_extrato_pagamento ON tbl_extrato_pagamento.extrato = tbl_extrato.extrato ";
				}

				$sql.= "WHERE tbl_extrato.fabrica = $login_fabrica ";

			if ($login_fabrica == 45) {

				if ($nota_sem_baixa == 'sim') {
					$sql .= " AND tbl_extrato_pagamento.nf_autorizacao IS NOT NULL
							  AND tbl_extrato_pagamento.data_pagamento IS NULL    ";
				} elseif ($nota_com_baixa == 'sim') {
					$sql .= " AND tbl_extrato_pagamento.nf_autorizacao IS NOT NULL
							  AND tbl_extrato_pagamento.data_pagamento IS NOT NULL    ";
				} else {
					$sql .= " AND tbl_extrato_pagamento.nf_autorizacao IS NOT NULL ";
				}

			}

			if (strlen ($data_inicial) < 8)
				$data_inicial = date ("d/m/Y");

			$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);

			if (strlen ($data_final) < 8)
				$data_final = date ("d/m/Y");

			$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);

			if ($login_fabrica <> 20) {
				if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
					$sql .= " AND tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'  ";
			} else {
				if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
					$sql .= " AND tbl_extrato_extra.exportado BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59' ";
			}


			$sql .= ";  ALTER table tmp_val_ext_$login_fabrica ADD COLUMN total_cortesia double precision;

				CREATE INDEX tmp_val_ext_extrato_$login_fabrica ON tmp_val_ext_$login_fabrica(extrato);

				SELECT os.mao_de_obra,os.pecas,os.os ,ext.extrato
				into temp tmp_ext_os_$login_fabrica
				FROM tbl_os os
				JOIN tbl_os_extra ose USING(os)
				JOIN tmp_val_ext_$login_fabrica ext USING(extrato)
				WHERE os.tipo_atendimento = 16
				AND   os.fabrica = $login_fabrica;

				UPDATE tmp_val_ext_$login_fabrica set total_cortesia = total_soma
				FROM (
				SELECT (sum(os.mao_de_obra) + sum(os.pecas)) as total_soma,extrato
				FROM tmp_ext_os_$login_fabrica os
				WHERE 1 = 1
				GROUP BY extrato
				) os
				WHERE os.extrato = tmp_val_ext_$login_fabrica.extrato ;

				SELECT 	X.id                       ,
						X.posto                    ,
						X.nome                     ,";

				//DADOS BANCARIOS NKS HD 8190 Gustavo 29/11/2007
				if ($login_fabrica == 45 || $login_fabrica == 40) {
					$sql .= "X.cnpj,
							 X.banco                    ,
							 X.agencia                  ,
							 X.conta                    ,
							 X.favorecido_conta         ,
							 X.conta_operacao           ,";
				}

				//HD-15422
				$sql .= ($login_fabrica == 20) ? "X.escritorio_regional           ," : "X.contato_estado, X.contato_cidade, ";

				if($login_fabrica == 104){
					$sql .= " X.marca, X.extrato_pagamento, ";
				}

				if(in_array($login_fabrica, array(91, 148, 157, 158, 183))) $sql .= ($agrupar == "sim") ? "SUM(X.total_km) as total_km ," : "X.total_km as total_km , X.extrato as extrato ,";
				$sql.= "X.tipo_posto               ,
						X.reembolso_peca_estoque       ,
						sum(X.mao_de_obra) as mao  ,
						sum(X.pecas) as pecas      ,
						sum(X.valor_adicional) AS valor_adicional,
						sum(X.avulso) as avulso    ,
						sum(X.total) as total      ,
						sum(X.total_os) as total_os,
						sum(X.total_cortesia) as total_cortesia
					FROM tmp_val_ext_$login_fabrica X
				GROUP BY id,posto, nome,";

			//HD-15422
			$sql.= ($login_fabrica == 20) ? " escritorio_regional," : " contato_estado, contato_cidade,";

			if($login_fabrica == 104){
				$sql .= " marca, extrato_pagamento, ";
			}

			if(in_array($login_fabrica, array(91,148,157,158,183)) && $agrupar != "sim") $sql .=  " total_km, extrato,";

			//DADOS BANCARIOS NKS HD 8190 Gustavo 29/11/2007
			if ($login_fabrica == 45 || $login_fabrica == 40) {
				$sql .= "cnpj,
						banco,
						agencia,
						conta,
						favorecido_conta,
						conta_operacao,";
			}

			$sql.= "tipo_posto,
					reembolso_peca_estoque
					order by nome";

			$res = pg_query($con, $sql);

			/*SELECT DO TOTAL GERAL DOS EXTRATOS 26/12/2007 HD 9983
			****************************************/
			if ($login_fabrica == 5) {

				$sqlx .= "SELECT
						SUM(tbl_extrato.total) AS total_geral
					FROM tbl_extrato
					JOIN tbl_extrato_extra
					ON tbl_extrato_extra.extrato = tbl_extrato.extrato
					JOIN tbl_posto ON tbl_posto.posto = tbl_extrato.posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_extrato.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					WHERE tbl_extrato.fabrica = $login_fabrica";

				if (strlen ($data_inicial) < 8)
					$data_inicial = date ("d/m/Y");

				$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);

				if (strlen ($data_final) < 8)
					$data_final = date ("d/m/Y");

				$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);

				if ($login_fabrica <> 20) {
					if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
						$sqlx .= " AND tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
				} else {
					if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
						$sqlx .= " AND tbl_extrato_extra.exportado BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
				}
				$resx = pg_query($con, $sqlx);

				if (pg_num_rows($resx) > 0) {
					$total_geral = trim(pg_fetch_result($resx,0,total_geral));
					$total_geral = number_format($total_geral,2,",",".");
				}

			}
			/****************************************/

			$codigo_aux = (in_array($login_fabrica, array(104))) ? " / CNPJ" : "";

			if (pg_num_rows($res) > 0) {

				$data = date ("dmY");

				echo "<div id='id_download' class='tac'><img src='imagens/excell.gif'> <a href='xls/relatorio_pagamento_posto_agrupado-$login_fabrica.$data.xls'> Clique aqui para fazer o download do arquivo em EXCEL</a> </div>";

				echo "

				<br />

				<div class='tac'>
					<a href='extrato_pagamento.php?btn_acao=filtrar&data_inicial=$data_inicial&data_final=$data_final&posto_codigo=$posto_codigo&posto_nome=$posto_nome' class='btn btn-primary btn-small'>
						Desagrupar
					</a>
				</div>

				<br />

				<table id='resultado' class='table table-striped table-bordered' style='min-width: {$min_width}; margin: 0 auto;' >";

				echo "<thead>";

				//HD 9983 26/12/2007
				if ($login_fabrica == 5) {
					echo "<tr class='titulo_coluna'>";
					echo "<td colspan='8' align='right'>Valor Total</td>";
					echo "<td colspan='2'>$total_geral</td>";
					echo "</tr>";
				}

				echo "<tr class='titulo_coluna'>";
				echo "<td >Código {$codigo_aux}</td>";
				echo "<td >Nome Posto</td>";

				//DADOS BANCARIOS NKS HD 8190 Gustavo 29/11/2007
				if ($login_fabrica == 45 || $login_fabrica == 40) {
					echo "<td >CNPJ</td>";
					echo "<td >Banco</td>";
					echo "<td >Agência</td>";
					echo "<td >Conta</td>";
					echo "<td >Favorecido</td>";
					echo "<td >Operação</td>";
				}

				//HD 15422 - Jean
				if(in_array($login_fabrica, array(104))){
					echo "<td>Cidade</td>";
				}
				echo ($login_fabrica == 20) ? "<td>ER</td>" : "<td>UF</td>";

				echo "<td >Tipo Posto</td>";
				if ($login_fabrica == 1) echo "<td>Pedido Garantia</td>";
				if (in_array($login_fabrica, array(91,148,157,158,183))) {
					if ($login_fabrica == 91) {
						echo "<td>KM</td>";
					}
					echo "<td>Valor KM</td>";
				}
				echo "<td >Valor de M.O</td>";
				if (!in_array($login_fabrica, array(157,158))) {
					echo "<td >Valor de Peças</td>";
					echo "<td >Avulso</td>";
				}

				if(in_array($login_fabrica, array(104))){
					echo "<td>Empresa</td>";
				}

				if (in_array($login_fabrica, array(158))) {
					echo "<td >Valor Adicional</td>";
					echo "<td >Avulso</td>";

				}

				//hd 39502
				if ($login_fabrica == 20) {
					echo "<td >Total Cortesia</td>";
					echo "<td >Total Geral</td>";
				} else {
					echo "<td >Valor Total</td>";
				}

				if(!in_array($login_fabrica, array(104))){
					echo "<td nowrap>Total OS</td>";
				}

				if(in_array($login_fabrica, array(104))){
					echo "<td>Status</td>";
				}

				echo "</tr>";

				echo "</head>";

				echo `rm /tmp/assist/relatorio_pagamento_posto_agrupado-$login_fabrica.xls`;

				$fp = fopen ("/tmp/assist/relatorio_pagamento_posto_agrupado-$login_fabrica.html","w");

				fputs($fp,"<html>");
				fputs($fp,"<head>");
				fputs($fp,"<title>RELATÓRIO DE VALORES DE EXTRATOS - $data");
				fputs($fp,"</title>");
				fputs($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
				fputs($fp,"</head>");
				fputs($fp,"<body>");

				fputs($fp,"<br><table border='1' cellpadding='2' cellspacing='0' class='formulario' align='center'>");
				fputs($fp,"<tr class='titulo_coluna'>");
				fputs($fp,"<td >Código {$codigo_aux} </td>");
				fputs($fp,"<td >Nome Posto</td>");

				//DADOS BANCARIOS NKS HD 8190 Gustavo 29/11/2007
				if ($login_fabrica == 45 || $login_fabrica == 40) {
					fputs($fp,"<td >CNPJ</td>");
					fputs($fp,"<td >Banco</td>");
					fputs($fp,"<td >Agência</td>");
					fputs($fp,"<td >Conta</td>");
					fputs($fp,"<td >Favorecido</td>");
					fputs($fp,"<td >Operação</td>");
				}

				//HD 15422 - Jean
				if($login_fabrica == 20){
					fputs($fp,"<td>ER</td>");
				} else {
					if(in_array($login_fabrica, array(104))){
						fputs($fp,"<td>Cidade</td>");
					}
					fputs($fp,"<td>UF</td>");
				}

				fputs($fp,"<td >Tipo Posto</td>");
				if($login_fabrica == 1) fputs($fp,"<td >Pedido Garantia</td>");
				if (in_array($login_fabrica, array(91, 157,158,183))) {
					if ($login_fabrica == 91) {
						fputs($fp,"<td nowrap>KM</td>");
					}
					fputs($fp,"<td nowrap>Valor KM</td>");
				}
				fputs($fp,"<td nowrap>Valor de M.O</td>");
				if (!in_array($login_fabrica, array(157,158))) {
					fputs($fp,"<td nowrap>Valor de Peças</td>");
					fputs($fp,"<td nowrap>Avulso</td>");
				}

				if (in_array($login_fabrica, array(158))) {
					fputs($fp,"<td nowrap>Valor Adicional</td>");
					fputs($fp,"<td nowrap>Avulso</td>");
				}

				if (in_array($login_fabrica, array(104))) {
					fputs($fp,"<td >Empresa</td>");
				}

				//hd 39502
				if ($login_fabrica==20) {
					fputs($fp,"<td nowrap>Total Cortesia</td>");
					fputs($fp,"<td nowrap>Total Geral</td>");
				} else {
					fputs($fp,"<td nowrap>Valor Total</td>");
				}

				if(!in_array($login_fabrica, array(104))){
					fputs($fp,"<td >Total OS</td>");
				}

				if(in_array($login_fabrica, array(104))){
					fputs($fp,"<td >Status</td>");
				}

				fputs($fp,"</tr>");

				if ($login_fabrica <> 20) {
					if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
						$sql_data .= " AND tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
				} else {
					if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
						$sql_data .= " AND tbl_extrato_extra.exportado BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
				}

				echo "<tbody>";

				for ($i = 0; $i < pg_num_rows($res); $i++) {

					$id   = trim(pg_fetch_result($res,$i,id));
					$nome = trim(pg_fetch_result($res,$i,nome));

					if ($login_fabrica == 20) {
						$escritorio_regional = trim(pg_fetch_result($res,$i,escritorio_regional));
					} else {
						$estado = trim(pg_fetch_result($res,$i,contato_estado));
					}

					if(in_array($login_fabrica, array(104))){
						$cidade  = trim(pg_fetch_result($res, $i, "contato_cidade"));
						$empresa = trim(pg_fetch_result($res, $i, "marca"));
						$status  = trim(pg_fetch_result($res, $i, "extrato_pagamento"));
						$status  = (strlen($status) == 0) ? "Pendente" : "Pago";
					}

					$codigo_posto = trim(pg_fetch_result($res,$i,posto));
					//DADOS BANCARIOS NKS HD 8190 Gustavo 29/11/2007
					if ($login_fabrica == 45 || $login_fabrica == 40) {

						$cnpj = trim(pg_fetch_result($res,$i,cnpj));

						if (strlen($cnpj) == 14)//CNPJ
							$cnpj = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
						if (strlen($cnpj) == 11)//CPF
							$cnpj = substr($cnpj,0,3) .".". substr($cnpj,3,3) .".". substr($cnpj,6,3) ."-". substr($cnpj,9,2);

						$banco            = trim(pg_fetch_result($res,$i,banco));
						$agencia          = trim(pg_fetch_result($res,$i,agencia));
						$conta            = trim(pg_fetch_result($res,$i,conta));
						$favorecido_conta = trim(pg_fetch_result($res,$i,favorecido_conta));
						$conta_operacao   = trim(pg_fetch_result($res,$i,conta_operacao));

					}

					$mao_de_obra     = trim(pg_fetch_result($res,$i,mao));
					$valor_adicional = trim(pg_fetch_result($res,$i,valor_adicional));
					$pecas           = trim(pg_fetch_result($res,$i,pecas));
					$avulso          = trim(pg_fetch_result($res,$i,avulso));
					$total           = trim(pg_fetch_result($res,$i,total));

					if ($login_fabrica == 20) {
						$total_cortesia = trim(pg_fetch_result($res,$i,total_cortesia));
					}

					$total_os           = trim(pg_fetch_result($res,$i,total_os));
					$tipo_posto         = trim(pg_fetch_result($res,$i,tipo_posto));
					$pedido_em_garantia = trim(pg_fetch_result($res,$i,reembolso_peca_estoque));
					$total_km			= trim(pg_fetch_result($res,$i, total_km));
					$extrato			= trim(pg_fetch_result($res,$i, extrato));
					if ($pedido_em_garantia == 't') {
						$pedido_em_garantia = "Sim";
					} else {
						$pedido_em_garantia = "Não";
					}

					$cor = ($i%2 ) ? '#F7F5F0' : '#F1F4FA';

					$sql1 = "SELECT tbl_extrato.extrato
							INTO TEMP tmp_extrato_pagamento_$i
							FROM tbl_extrato
							JOIN tbl_extrato_extra USING(extrato)
							WHERE posto = $id
							AND  tbl_extrato.fabrica = $login_fabrica
							$sql_data;
							CREATE INDEX tmp_extrato_pagamento_extrato_$i ON tmp_extrato_pagamento_$i(extrato);";

					$res1 = pg_query($con, $sql1);

					$sql1 = "SELECT count(*) AS total_os
							FROM tbl_os_extra
							JOIN tmp_extrato_pagamento_$i USING(extrato);";

					$res1 = pg_query($con, $sql1);
					$total_os = trim(pg_fetch_result($res1,0,total_os)) ;

					$pecas           = number_format($pecas,2,",",".");
					$mao_de_obra     = number_format($mao_de_obra,2,",",".");
					$valor_adicional = number_format($valor_adicional,2,",",".");
					$avulso          = number_format($avulso,2,",",".");
					$total           = number_format($total,2,",",".");
					$total_km        = number_format($total_km,2,",",".");

					if ($login_fabrica == 20) {
						$total_cortesia = number_format($total_cortesia,2,",",".");
					}

					echo "<tr class='Conteudo'>";
					echo "<td >$codigo_posto</td>";
					echo "<td align='left' title='nome' nowrap>".substr($nome,0,20)."</td>";

					//DADOS BANCARIOS NKS HD 8190 Gustavo 29/11/2007
					if ($login_fabrica == 45 || $login_fabrica == 40) {
						echo "<td >$cnpj</td>";
						echo "<td >$banco</td>";
						echo "<td >$agencia</td>";
						echo "<td >$conta</td>";
						echo "<td >$favorecido_conta</td>";
						echo "<td >$conta_operacao</td>";
					}

					//HD 15422 - Jean
					if ($login_fabrica == 20) {
						echo "<td align='left'>$escritorio_regional</td>";
					} else {
						if(in_array($login_fabrica, array(104))){
							echo "<td>{$cidade}</td>";
						}
						echo "<td align='left'>$estado</td>";
					}

					echo "<td align='left'>$tipo_posto</td>";
					if ($login_fabrica == 1) echo "<td align='left'>$pedido_em_garantia</td>";
					if (in_array($login_fabrica, array(91, 148,157,158,183))) {

						if($login_fabrica == 91){

							$qtde_km    = 0;
								$sql_km = "SELECT sum(tbl_os.qtde_km) as qtde_km
											FROM tbl_os join tbl_os_extra using(os)
											JOIN tmp_val_ext_$login_fabrica using(extrato)
											WHERE fabrica = $login_fabrica
											AND id = $id";
								$res_km = pg_query($con,$sql_km);
								#echo $sql_km;exit;
								$qtde_km    = trim(pg_fetch_result($res_km,0,qtde_km));

								if (!strlen($qtde_km)) {
									$qtde_km    = 0;
								}


							echo "<td>$qtde_km</td>";
						}
						echo "<td>$real $total_km</td>";
					}
					echo "<td align='right' nowrap>$real $mao_de_obra</td>";
					if (!in_array($login_fabrica, array(157,158))) {
						echo "<td align='right' nowrap>$real $pecas</td>";
						echo "<td align='right' nowrap>$real $avulso</td>";
					}

					if (in_array($login_fabrica, array(158))) {
						$avulso = empty($avulso) ? "0,00" : $avulso;
						echo "<td align='right' nowrap>$real $valor_adicional</td>";
						echo "<td align='right' nowrap>$real $avulso</td>";
					}

					//hd 39502
					if ($login_fabrica == 20) {
						echo "<td align='right' nowrap>$ $total_cortesia</td>";
					}

					if($login_fabrica == 104){
						echo "<td> $empresa </td>";
					}

					echo "<td align='right' nowrap>$real $total</td>";

					if(!in_array($login_fabrica, array(104))){
						echo "<td class='tac'>$total_os</td>";
					}

					if($login_fabrica == 104){
						echo "<td> $status </td>";
					}

					echo "</tr>";
					flush();

					fputs($fp,"<tr class='Conteudo'>");
					fputs($fp,"<td >$codigo_posto</td>");
					fputs($fp,"<td align='left' title='nome'>".substr($nome,0,20)."</td>");

					//DADOS BANCARIOS NKS HD 8190 Gustavo 29/11/2007
					if ($login_fabrica == 45 || $login_fabrica == 40) {
						fputs($fp,"<td >$cnpj</td>");
						fputs($fp,"<td >$banco</td>");
						fputs($fp,"<td >$agencia</td>");
						fputs($fp,"<td >$conta</td>");
						fputs($fp,"<td >$favorecido_conta</td>");
						fputs($fp,"<td >$conta_operacao</td>");
					}

					//HD 15422 - Jean
					if ($login_fabrica == 20) {
						fputs($fp,"<td >$escritorio_regional</td>");
					} else {
						if(in_array($login_fabrica, array(104))){
							fputs($fp,"<td >$cidade</td>");
						}
						fputs($fp,"<td >$estado</td>");
					}

					fputs($fp,"<td >$tipo_posto</td>");
					if ($login_fabrica == 1) fputs($fp,"<td >$pedido_em_garantia</td>");
					if (in_array($login_fabrica, array(91, 148, 157, 158,183))) {
						if ($login_fabrica == 91) {
							fputs($fp,"<td >$qtde_km</td>");
						}
						fputs($fp,"<td >$real $total_km</td>");
					}
					fputs($fp,"<td align='right' nowrap>$real $mao_de_obra</td>");
					if (!in_array($login_fabrica, array(157,158))) {
						fputs($fp,"<td align='right' nowrap>$real $pecas</td>");
						fputs($fp,"<td align='right' nowrap>$real $avulso</td>");
					}

					if (in_array($login_fabrica, array(158))) {
						$avulso = empty($avulso) ? "0,00" : $avulso;
						fputs($fp,"<td align='right' nowrap>$real $valor_adicional</td>");
						fputs($fp,"<td align='right' nowrap>$real $avulso</td>");
					}

					//hd 39502
					if ($login_fabrica == 20) {
						fputs($fp,"<td align='right'>$real $total_cortesia</td>");
					}

					if ($login_fabrica == 104) {
						fputs($fp,"<td> $empresa </td>");
					}

					fputs($fp,"<td align='right' nowrap>$real $total</td>");

					if(!in_array($login_fabrica, array(104))){
						fputs($fp,"<td align='center'>$total_os</td>");
					}

					if($login_fabrica == 104){
						fputs($fp,"<td> $status </td>");
					}

					$tot_dtde_km     = $tot_dtde_km + $qtde_km;
					$tot_total_km    = $tot_total_km + $total_km;
					$tot_mao_de_obra = $tot_mao_de_obra +$mao_de_obra;
					$tot_pecas       = $tot_pecas + $pecas;
					$tot_avulso      = $tot_avulso + $avulso;
					$tot_total       = $tot_total + $total;
					$tot_total_os    = $tot_total_os + $total_os;

				}

				if ($login_fabrica == 91) {

					$tot_total_km    = number_format($tot_total_km,2,",",".");
					$tot_mao_de_obra = number_format($tot_mao_de_obra,2,",",".");
					$tot_pecas       = number_format($tot_pecas,2,",",".");
					$tot_avulso      = number_format($tot_avulso,2,",",".");
					$tot_total       = number_format($tot_total,2,",",".");

					fputs($fp,"<tr class='titulo_coluna'>");
					fputs($fp,"<td colspan='4' align='left'> Totais: </td>");
					fputs($fp,"<td>$tot_dtde_km</td>");
					fputs($fp,"<td>$real $tot_total_km</td>");
					fputs($fp,"<td>$real .$tot_mao_de_obra</td>");
					fputs($fp,"<td>$real $tot_pecas</td>");
					fputs($fp,"<td>$real $tot_avulso</td>");
					fputs($fp,"<td>$real $tot_total</td>");
					fputs($fp,"<td>$tot_total_os</td>");
					fputs($fp,"</tr>");

					echo "<tr class='titulo_coluna'>";
					echo "<td colspan='4' align='left'> Totais: </td>";
					echo "<td>$tot_dtde_km</td>";
					echo "<td>$real $tot_total_km</td>";
					echo "<td>$real $tot_mao_de_obra</td>";
					echo "<td>$real $tot_pecas</td>";
					echo "<td>$real $tot_avulso</td>";
					echo "<td>$real $tot_total</td>";
					echo "<td class='tac'>$tot_total_os</td>";
					echo "</tr>";

				}

				fputs($fp,"</table>");
				fputs($fp,"</body>");
				fputs($fp,"</html>");
				fclose ($fp);

				echo "</tbody>";

				echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f xls/relatorio_pagamento_posto_agrupado-$login_fabrica.$data.xls /tmp/assist/relatorio_pagamento_posto_agrupado-$login_fabrica.html`;

				echo "</table>";

				echo "<script language='javascript'>";
					echo "document.getElementById('id_download').style.display='block';";
				echo "</script>";

				echo '<script>
							$.dataTableLoad({ table: "#resultado", aoColumns:[null,null,null,null,{"sType":"date"},{"sType":"numeric"},{"sType":"numeric"},{"sType":"numeric"},{"sType":"numeric"},{"sType":"numeric"}], aaSorting: [[5, "asc" ]] });
					</script>';

			} else {
				echo "
					<div class='container'>
						<div class='alert alert-warning'><h4>".traduz("Nenhum resultado encontrado!")."</h4></div>
					</div>
					<br />";
			}

		}

	}

echo "<br />";

include 'rodape.php';

?>

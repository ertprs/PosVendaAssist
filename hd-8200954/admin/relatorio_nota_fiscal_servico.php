<?php

	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	$admin_privilegios = "call_center";
	include 'autentica_admin.php';
	include 'funcoes.php';
    include_once "class/tdocs.class.php";
    $tDocs   = new TDocs($con, $login_fabrica);

	if ($_POST["btn_acao"] == "submit") {

		$data_inicial    = $_POST['data_inicial'];
		$data_final      = $_POST['data_final'];
		$codigo_posto    = $_POST['codigo_posto'];
		$descricao_posto = $_POST['descricao_posto'];
		$status          = $_POST['status'];
		$centro_distribuicao = $_POST['centro_distribuicao'];

		if(strlen($data_inicial) == 0 || strlen($data_final) == 0){
			$msg_erro["msg"][]    ="Por favor insira as Datas";
			$msg_erro["campos"][] = "data";
		}else{

			if(strlen($data_inicial) > 0 && $data_inicial <> "dd/mm/aaaa"){
				$xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
				$xdata_inicial = str_replace("'","",$xdata_inicial);
			}else{
				$msg_erro["msg"][]    ="Data Inicial Inválida";
				$msg_erro["campos"][] = "data";
			}

			if(strlen($data_final) > 0 && $data_final <> "dd/mm/aaaa"){
				$xdata_final =  fnc_formata_data_pg(trim($data_final));
				$xdata_final = str_replace("'","",$xdata_final);
			}else{
				$msg_erro["msg"][]    ="Data Final Inválida";
				$msg_erro["campos"][] = "data";
			}

			if($xdata_inicial > $xdata_final){
				$msg_erro["msg"][]    ="Data Inicial maior que final";
				$msg_erro["campos"][] = "data";
			}

			$data1 = new DateTime($xdata_inicial);
				$data2 = new DateTime($xdata_final);
				$qtde_dias = $data1->diff($data2)->format('%a');

			if (strlen($codigo_posto) > 0 AND strlen($descricao_posto) > 0) {
				if($qtde_dias > 366){
						$msg_erro["msg"][]    ="O intervalo entre as datas não pode ser maior do que 1 ano";
					$msg_erro["campos"][] = "data";
				}
			}else{
				if($qtde_dias > 90){
					$msg_erro["msg"][]    ="O intervalo entre as datas não pode ser maior do que 3 meses";
				$msg_erro["campos"][] = "data";
				}
			}

			if(count($msg_erro["msg"]) == 0){

				if(strlen($descricao_posto) > 0){
					$sql = "SELECT posto
						      FROM tbl_posto_fabrica
							  JOIN tbl_posto USING(posto)
							 WHERE fabrica      = $login_fabrica
							   --AND TRIM(nome)   = '$descricao_posto'
							   AND codigo_posto = '$codigo_posto'";
					$res = pg_query($con, $sql);
					$posto = pg_fetch_result($res, 0, "posto");
					if(strlen($posto) > 0){
						$cond_posto = " AND tbl_extrato.posto = {$posto} ";
					}
				}

				$cond_status = ($status == "pendentes")
					? " AND tbl_extrato_pagamento.nf_autorizacao IS NOT NULL AND tbl_extrato.nf_recebida IS NOT TRUE "
					: " AND tbl_extrato_pagamento.nf_autorizacao IS NOT NULL AND tbl_extrato.nf_recebida IS TRUE ";

		        if($login_fabrica == 151){
		            if($centro_distribuicao != "mk_vazio"){
		                $campo_p_adicionais = ",tbl_produto.parametros_adicionais::json->>'centro_distribuicao' AS centro_distribuicao";
		                $p_adicionais = " AND tbl_produto.parametros_adicionais::json->>'centro_distribuicao' = '$centro_distribuicao'";
		                $distinct_P_adicionais = " DISTINCT ";
		                $join_p_adicionais = " JOIN tbl_produto ON tbl_produto.fabrica_i = {$login_fabrica}";
		            }

		            $campo_agrupado = ", tbl_extrato_agrupado.codigo";
		            $join_agrupado  = " LEFT JOIN tbl_extrato_agrupado ON tbl_extrato.extrato = tbl_extrato_agrupado.extrato";
		        }


				$sql = "SELECT {$distinct_P_adicionais}
							tbl_extrato.extrato,
							tbl_posto.posto,
							tbl_posto_fabrica.codigo_posto,
							tbl_posto.nome,
							tbl_extrato.data_geracao,
							tbl_extrato.total,
							tbl_extrato.previsao_pagamento,
							tbl_extrato_pagamento.serie_nf,
							tbl_extrato_pagamento.nf_autorizacao
							{$campo_p_adicionais}
							{$campo_agrupado}
						FROM tbl_extrato
						JOIN tbl_posto             ON tbl_posto.posto               = tbl_extrato.posto
						JOIN tbl_posto_fabrica     ON tbl_posto_fabrica.posto       = tbl_posto.posto
							                      AND tbl_posto_fabrica.fabrica     = {$login_fabrica}
						JOIN tbl_extrato_pagamento ON tbl_extrato_pagamento.extrato = tbl_extrato.extrato
						{$join_p_adicionais}
						{$join_agrupado}
						WHERE
							tbl_extrato.data_geracao BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59'
							AND tbl_extrato.fabrica = {$login_fabrica}
							{$cond_status}
							{$cond_posto}
							{$p_adicionais}";

				//die(nl2br($sql));
				
				$result = pg_query($con, $sql);
			}

		}

	}

	$layout_menu = "financeiro";
	$title = "RELATÓRIO DE NOTA FISCAL DE SERVIÇO";

	include "cabecalho_new.php";

	$plugins = array(
	    "autocomplete",
	    "datepicker",
	    "shadowbox",
	    "fancyzoom",
	    "mask",
	    "dataTable"
	);

	include("plugin_loader.php");

?>

<script type="text/javascript">

$(function() {
    $("#data_previsao").mask("99/99/9999").datepicker();
    var data_previsao = $("#data_previsao").val();

    $.datepickerLoad(Array("data_final", "data_inicial"));
    $.autocompleteLoad(Array("produto", "posto"));

    Shadowbox.init();
    setupZoom();
    $("span[rel=lupa]").click(function () {
        $.lupa($(this));
    });

    $(".marcar_todos").click(function() {
    	if ($(this).is(":checked")) {
    		$(".check_seleciona").prop("checked", true);
    	} else {
    		$(".check_seleciona").prop("checked", false);
    	}
    });

    $(".cadastrar_pagamento").click(function(){
        var array_extrato = [];

        $("#callcenter_relatorio_atendimento > tbody > tr").find("input[name^=chec_]:checked").each(function() {
            array_extrato.push($(this).val());
        });

        var data_previsao = $("#data_previsao").val();

        if(typeof array_extrato != "undefined" && array_extrato.length == 0){
            alert("Por favor insira o Extrato");
            return;
        }

        //console.log(data_previsao);
        if(typeof data_previsao != "undefined" && data_previsao.length == 0){
            alert("Por favor insira a Data de Previsão");
            $("#data_previsao").focus();
            return;
        }

        $.ajax({
            url : "gravar_previsao_pagamento.php",
            type : "POST",
            dataType:"JSON",
            data : {
                grava_previsao_array: true,
                data_previsao: data_previsao,
                array_extrato: array_extrato
            },
        }).always(function(data){
            if (typeof DEV !== 'undefined' && DEV===true)
                console.log(data);

            if (data.error === 0 ) {
                alert("Previsão cadastrada com Sucesso");
                return true;
            }
            if (data.error > 0) {
                alert("Ocorreu um erro, Por favor verifique as informações do Extrato:\nData de Previsão de Pagamento");
                $('.response').addClass('alert alert-danger');
                $('.response').html(data.errorMsg.join('<br />'));

                document.getElementsByClassName('response')[0].scrollIntoView();
                return true;
            }

            alert("Erro naõ esperado.");
        });
    });

    $("div[id^=ver_os]").click(function () {
        var aux = $(this).attr("id");
        var aux2 = aux.split("_");
        var extrato = aux2[2];

        Shadowbox.open({
            content:"pecas_nota_fiscal_servico.php?extrato="+extrato,
            player:"iframe",
            width:850,
            height:500,
            options:{
                modal:true
            }
        });
    });

    $("#xlsTudo").click(function(){
        var data_inicial    = $("input[name=data_inicial]").val();
        var data_final      = $("input[name=data_final]").val();
        var codigo_posto    = $("input[name=codigo_posto]").val();
        var status          = $("select[name=status]").val();
        var centro_distrib 	= $("select[id=centro_distribuicao]").val();
		
        $.ajax({
            url:"relatorio_nota_fiscal_servico_processa_excel.php",
            type:"POST",
            dataType:"html",
            data:{
                download:true,
                data_inicial:data_inicial,
                data_final:data_final,
                codigo_posto:codigo_posto,
                status:status,
                centro_distrib:centro_distrib
            },
            beforeSend:function(){
                $("div.loading").show();
            }
        })
        .done(function(data){
            console.log(data);
            $("#result").append(data);
            document.getElementById('link').click();
            $("#link").remove();
            $("div.loading").hide();

        })
        .fail(function(){
            alert("Não foi possível criar a planilha de dados.");
        });
    });
});

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
    $("#descricao_posto").val(retorno.nome);
}

</script>
	<style type="text/css">
	.verOs {
	  color:#08C;
	  cursor:pointer;
	  text-decoration:none;
	  text-align:center;
	}
	#xlsTudo {
	  color:#08C;
	  cursor:pointer;
	}
	table#callcenter_relatorio_atendimento tbody > tr:hover > td:nth-of-type(4) {
	  text-overflow: unset;
	  white-space: normal;
	}
	table#callcenter_relatorio_atendimento tbody > tr > td:nth-of-type(4) {
	  word-wrap: break-word;
	  white-space: nowrap;
	  max-width: 200px;
	  overflow: hidden;
	  text-overflow: ellipsis;
	}
	 #ZoomBox{
        background-color: #ffffff !important;
        z-index: 9999999;
    }
</style>
<?php if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php } ?>

	<div class="row">
	    <b class="obrigatorio pull-right">* Campos obrigatórios </b>
	</div>

	<form name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>
	    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	    <br/>

		<div class='row-fluid'>
	        <div class='span2'></div>
	            <div class='span4'>
	                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
	                    <label class='control-label' for='data_inicial'>Data Inicial</label>
	                    <div class='controls controls-row'>
	                        <div class='span5'>
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
	                    <div class='span5'>
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

	    <div class='row-fluid'>
	        <div class='span2'></div>
	        <div class='span4'>
	            <div class='control-group '>
	                <label class='control-label' for='status'>Status</label>
	                <div class='controls controls-row'>
	                    <div class='span4'>
	                        <select name="status" id="status">
	                            <option value="pendentes" <?php echo ($status == "pendentes") ? "SELECTED" : ""; ?> >Pendentes de Confirmação</option>
	                            <option value="confirmados" <?php echo ($status == "confirmados") ? "SELECTED" : ""; ?> >Confirmados</option>
	                        </select>
	                    </div>
	                    <div class='span2'></div>
	                </div>
	            </div>
	        </div>
		    <?php if($login_fabrica == 151){ ?>	                                             
	            <div class='span4'>
	                <div class='control-group'>
	                    <label class='control-label' for='centro_distribuicao'>Centro Distribuição</label>
	                    <div class='controls controls-row'>
	                        <div class='span12 input-append'>
	                            <select name="centro_distribuicao" id="centro_distribuicao">
	                                <option value="mk_vazio" name="mk_vazio" <?php echo ($centro_distribuicao == "mk_vazio") ? "SELECTED" : ""; ?> >ESCOLHA</option>
	                                <option value="mk_nordeste" name="mk_nordeste" <?php echo ($centro_distribuicao == "mk_nordeste") ? "SELECTED" : ""; ?> >MK Nordeste</option>
	                                <option value="mk_sul" name="mk_sul" <?php echo ($centro_distribuicao == "mk_sul") ? "SELECTED" : ""; ?> >MK Sul</option>    
	                            </select>
	                        </div>                          
	                    </div>                      
	                </div>
	            </div>		        
		    <?php } ?>
	    </div>

	    <p>
	    	<br />
        	<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
        	<input type='hidden' id="btn_click" name='btn_acao' value='' />
    	</p>

    	<br />

	</form>
    <div class="response"></div>
	<?php

	if(isset($result)){

		if(pg_num_rows($result) > 0){

			$cont = pg_num_rows($result);

			?>

			<table id="callcenter_relatorio_atendimento" class='table table-striped table-bordered table-hover table-large' style="min-width: 850px;">
    			<thead>
    				<tr class="titulo_coluna">
    					<?php if ($login_fabrica == 151) { ?>
    						<th><input type="checkbox" name="marcar_todos" class="marcar_todos"></th>
    						<th>Extrato Agrupado</th>
    					<?php } else { ?>
    						<th></th>
    					<?php } ?>
    					<th>Detalhe</th>
    					<th>Extrato</th>
    					<th>Posto</th>
    					<th>Data Geração</th>
    					<th>Total Extrato</th>
    					<?php
    					if($status == "confirmados"){
    						echo "<th>Previsão Pagto</th>";
    					}
    					?>
    					<th>NF Serviço</th>
    					<?php if($login_fabrica == 151) { ?>
                			<th>Série</th>
                			<th>Centro Distribuição</th>
            			<? }
					
					if ($status == "pendentes") {
					?>
    						<th>Ações</th>
					<?php
					}
					?>
    				</tr>
    			</thead>

    			<tbody>
<?php
	
					$mostra_inserir = true;
    				$cod_array_old = [];

					for ($i = 0; $i < $cont; $i++) {

						$extrato               = pg_fetch_result($result, $i, "extrato");
						$posto                 = pg_fetch_result($result, $i, "posto");
						$codigo_posto          = pg_fetch_result($result, $i, "codigo_posto");
						$nome_posto            = pg_fetch_result($result, $i, "nome");
						$data                  = pg_fetch_result($result, $i, "data_geracao");
						$total                 = pg_fetch_result($result, $i, "total");
						$previsao_pagamento    = pg_fetch_result($result, $i, "previsao_pagamento");
						$nf_servico            = pg_fetch_result($result, $i, "nf_autorizacao");
						$serie_nf              = pg_fetch_result($result, $i, "serie_nf");
						$parametros_adicionais = pg_fetch_result($result, $i, "centro_distribuicao");

						$total = number_format($total, 2);

						list($data, $hota) = explode(" ", $data);
						list($ano, $mes, $dia) = explode("-", $data);
						$data = $dia."/".$mes."/".$ano;

						if(strlen($previsao_pagamento) > 0){
							list($ano, $mes, $dia) = explode("-", $previsao_pagamento);
							$previsao_pagamento = $dia."/".$mes."/".$ano;
						}

						$desc_posto = $codigo_posto." - ".$nome_posto;

						$total = number_format(str_replace(",", "", $total), 2, ",", ".");

						if ($login_fabrica == 151) {
							$codigo = pg_fetch_result($result, $i, "codigo");							
							if (!in_array($codigo, $cod_array_old) && !empty($codigo)) {
								$cod_array_old[] = $codigo;
								$mostra_inserir = true;
							} else if (empty($codigo)) {
								$mostra_inserir = true;
							} else {
								$mostra_inserir = false;
							}
						}
?>
						<tr>
							<?php if ($login_fabrica != 151 || ($login_fabrica == 151 && $mostra_inserir)) { ?>
								<td class="tac"><input type="checkbox" class="check_seleciona" name="chec_<?=$extrato?>" value="<?=$extrato?>"></td>
							<?php } else { ?>
								<td class="tac"></td>
							<?php } ?>
							<?php if ($login_fabrica == 151) { ?>
									<td class="tac"><?=$codigo?></td>
							<?php } ?>
							<td class="tac"><div id="ver_os_<?=$extrato?>" class="verOs">Ver OS</div></td>
							<td class="tac"><a href='extrato_consulta_os.php?extrato=<?=$extrato?>' target='_blank'><?=$extrato?></a></td>
							<td ><?=$desc_posto?></td>
							<td class="tac"><?=$data?></td>
							<td class="tac"><?=$total?></td>
<?php
                        if($status == "confirmados"){
?>
                            <td><?=$previsao_pagamento?></td>
<?php
                        }
?>
							<td class="tac">
							<?php
								if ($login_fabrica == 101) {
									 $xxAnexo = $tDocs->getDocumentsByRef($extrato, "lgr", "nfservico")->url;
	                                if (!empty($xxAnexo)) {
										echo '<a href="'.$xxAnexo.'">'.$nf_servico.'</a>'; 
									} else {
										echo $nf_servico; 
									}
								} else {
									echo $nf_servico; 
								}
							?>									
							</td>
							<?php
								if($login_fabrica == 151){
								?>
					            	<td><?=$serie_nf?></td>
					            <?php
				                    echo "<td>";				                    
				                    if($parametros_adicionais == "mk_nordeste"){
				                        echo "MK Nordeste";
				                    }else if($parametros_adicionais == "mk_sul") {
				                        echo "MK Sul";    
				                    } else{
				                        echo "&nbsp;";    
				                    }
				                    echo "</td>";
				                }
							?>
<?php
                        if ($status == "pendentes") {

                        	if ($login_fabrica != 151 || ($login_fabrica == 151 && $mostra_inserir)) {
?>
	                            <td class="tac" nowrap id="td_extrato_<?=$extrato?>" >
	                                <a href="gravar_previsao_pagamento.php?extrato=<?=$extrato?>&posto=<?=$desc_posto?>" class="btn btn-info" rel="shadowbox; width = 500; height = 350;">Inserir Previsão Pagto</a>
	                            </td>
<?php
                        	} else {
?>
								<td>&nbsp;</td>
<?php
                        	}
                        }
?>
						</tr>
<?php
					}

					if($login_fabrica == 151) {
						$cspan = 10;
					} else {
						$cspan = 7;
					}
?>
				</tbody>
				<tfoot>
					<tr>
						<td colspan='<?=$cspan?>'>Data Previsão Pagamento:
							<input type="text" name="data_previsao" id="data_previsao" class="span2" />
						</td>
						<td align="justify" class="tac">
							<button type="button" class="btn btn-success cadastrar_pagamento">Cadastrar</button>
						</td>
					</tr>
					<tr>
                        <td colspan='<?=$cspan?>' class="tac">
                            <div class="loading tac" style="display: none;" ><img src="imagens/loading_img.gif" /></div>
                            <div id="result" style="display: none;"></div>
                            <span id="xlsTudo" >
                            <img src="imagens/excel.gif" alt="Download Excel" />
                            Gerar Arquivo Excel
                            </span>
                        </td>
					</tr>
				</tfoot>
			</table>
			<br />
			<?php

		}else{
			echo "<div class='container'>
            		<div class='alert'>
                    	<h4>Nenhum resultado encontrado</h4>
            		</div>
            	</div>";
		}

	}


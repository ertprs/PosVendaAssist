<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "gerencia,call_center";

include 'autentica_admin.php';
include 'funcoes.php';
include_once '../helpdesk/mlg_funciones.php';

if($_GET['buscaCidade']){
    $uf = $_GET['estados'];

    $sql_cidade = "SELECT 
    					cidade,
    					nome, 
    					estado, 
    					pais 
    				FROM tbl_cidade 
    				WHERE estado = '{$uf}'
    				ORDER BY nome";

    $res_cidade = pg_query($con,$sql_cidade);

    if(pg_num_rows($res_cidade) > 0){        
        for($i = 0; $i < pg_num_rows($res_cidade); $i++){
        	$cidade 	 = pg_fetch_result($res_cidade, $i, 'cidade');           
            $nome_cidade = pg_fetch_result($res_cidade, $i, 'nome');           

            echo "<option value='$nome_cidade'>$nome_cidade</option>";

        }
    } else {
        echo "<option value=''>Cidade não encontrada</option>";
    }    
    exit;
}

$layout_menu = "callcenter";
$title = "EXPORTAR PEDIDOS EM LOTE";
include "cabecalho_new.php";

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");

if($_POST) {	
	$data_inicial 		= $_POST['data_inicial'];
	$data_final 		= $_POST['data_final'];
	$pedido 			= $_POST['pedido'];
	$os 				= $_POST['os'];
	$protocolo 			= $_POST['protocolo'];
	$uf 				= $_POST['estados'];
	$cidade 			= $_POST['cidade'];
	$linha 				= $_POST['linha'];
	$familia 			= $_POST['familia'];
	$produto 			= $_POST['produto'];
	$descricao_produto 	= $_POST['descricao_produto'];
	$cpf_cnpj 			= $_POST['cpf_cnpj'];
	$obj_cpf_cnpj 		= $_POST['opt_cpf_cnpj'];
	$classificacao 		= $_POST['classificacao'];
	$origem 			= $_POST['origem'];
	$centro_distrib 	= $_POST['centro_distribuicao'];
	$codigo_posto 		= $_POST['codigo_posto'];
	$descricao_posto 	= $_POST['descricao_posto'];

	if (strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){
		$sql = "SELECT tbl_posto_fabrica.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
				AND (
					(UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}'))
					OR
					(TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$descricao_posto}'), 'LATIN-9'))
				)";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Posto não encontrado";
			$msg_erro["campos"][] = "posto";
		} else {
			$posto = pg_fetch_result($res, 0, "posto");
		}
	}

	if ((!strlen($data_inicial) or !strlen($data_final)) && empty($pedido)) {
		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "data";
	} else if (strlen($data_inicial) > 0 && strlen($data_final) > 0) {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		//$sql_datas = " AND tbl_pedido.data BETWEEN '{$data_inicial} 00:00:00' and '{$data_final} 23:59:59'";

		if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
			$msg_erro["msg"][]    = "Data Inválida";
			$msg_erro["campos"][] = "data";
		} else {
			$aux_data_inicial = "{$yi}-{$mi}-{$di}";
			$aux_data_final   = "{$yf}-{$mf}-{$df}";
			
			$sql_datas = " AND tbl_pedido.data BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59'";
			
			if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
				$msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
				$msg_erro["campos"][] = "data";
			} 
		}		
	}
}

?>

<style>
.truncate {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

table#resultado tr:nth-child(2n + 1) td {
    border-top-width: 4px !important;
    border-top-color: grey !important;

}
</style>

<script type="text/javascript">
	$(function(){
		$("#data_inicial, #data_final").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");

		$.datepickerLoad(["data_final", "data_inicial"]);
		$.autocompleteLoad(["posto", "descricao_posto", "produto", "descricao_produto"]);		
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		$("#item_selecionado_todos").change(function() {
			if ($(this).is(":checked")) {
				$("input[type=checkbox][name^='item_selecionado_']").each(function() {
					$(this)[0].checked = true;
				});
			} else {
				$("input[type=checkbox][name^='item_selecionado_']").each(function() {
					$(this)[0].checked = false;
				});
			}
		});
	});

	function retorna_posto(retorno){
		$("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
	}

	function retorna_produto(retorno){
		$("#produto").val(retorno.referencia);		
		$("#descricao_produto").val(retorno.descricao);
	}

	function montaComboCidade(estados){		
	    $.ajax({
            url: "<?php echo $_SERVER['PHP_SELF']; ?>?buscaCidade=1&estados="+estados,
            cache: false,
            success: function(data) {            	                
                $('#cidade').html(data);                
            }
        });
	}

	function selecionarCpfCnpj(cpf_cnpj){
		$.ajax({
            cpf_cnpj: cpf_cnpj,            
            success: function(data) {            	                
            	if(cpf_cnpj == 'cnpj'){            		
            		$("#cpf_cnpj").val("00.000.000/000-00");
                	$("#cpf_cnpj").mask("99.999.999/9999-99",{placeholder:""});
                } else {
                	$("#cpf_cnpj").val("000.000.000-00");
					$("#cpf_cnpj").mask("999.999.999-99",{placeholder:""});
				}
            }
        });	
	}

	window.addEventListener("load", function() {
		[].forEach.call(document.querySelectorAll("button.exportar_pedido"), function(e) {
			e.addEventListener("click", function() {
				var pedido = e.dataset.pedido;

				$.ajax({
					async: false,
					url: "os_cadastro_unico/fabricas/<?=$login_fabrica?>/ajax_exporta_pedido_manual.php",
					type: "get",
					dataType:"JSON",
					data: { exporta_pedido_manual: true, pedido: pedido },
					beforeSend: function() {
						e.disabled = true;
						e.innerHTML = "Exportando...";
					}
				})
				.done(function(data) {
					if (data.erro) {
						alert(data.erro);
						e.disabled = false;
						e.innerHTML = "Exportar";
					} else if (data.SdErro && data.SdErro.ErroCod != 0) {
						alert(data.SdErro.ErroDesc);
						e.disabled = false;
						e.innerHTML = "Exportar";
					} else {
						e.innerHTML = "Exportado";
					}
				});
			});
		});
	});

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
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
		<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
		<br/>
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
				<div class='control-group <?=(in_array("pedido", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='pedido'>Pedido</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="pedido" id="pedido" class='span12' value="<? echo $pedido ?>" >
						</div>
					</div>
				</div>
			</div>						
			<div class='span4'>
				<div class='control-group <?=(in_array("tipo_pedido", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='tipo_pedido'>Tipo Pedido</label>
					<div class='controls controls-row'>
					<?
						$sql_tipo_pedido = "SELECT
												tipo_pedido, 
												descricao 
											FROM tbl_tipo_pedido 
											WHERE fabrica = {$login_fabrica} 
											AND ativo = true
											ORDER BY descricao";

						$res_tipo_pedido = pg_query($con, $sql_tipo_pedido);

					?>
						<div class='span7 input-append'>
			            <?  if (pg_numrows($res_tipo_pedido) > 0) {
			                    echo "<select name='tipo_pedido' id='tipo_pedido' class='parametros_tabela'>\n";
			                        echo "<option value=''>Selecione</option>";

			                        for ($i = 0 ; $i < pg_numrows ($res_tipo_pedido) ; $i++){
			                            $aux_tipo      = trim(pg_result($res_tipo_pedido,$i,tipo_pedido));
			                            $aux_descricao = trim(pg_result($res_tipo_pedido,$i,descricao));

			                            echo "<option value='$aux_tipo'";
			                            //if ($aux_tipo == $tipo) echo " selected";
			                            echo ">$aux_descricao</option>\n";
			                        }
			                    echo "</select>";
			                } ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("os", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='os'>Ordem de Serviço</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="os" id="os" class='span12' value="<? echo $os ?>" >
						</div>
					</div>
				</div>
			</div>	
			<div class='span4'>
				<div class='control-group <?=(in_array("protocolo", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='protocolo'>Protocolo</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="protocolo" id="protocolo" class='span12' value="<? echo $protocolo ?>" >
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("estados", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='estados'>Estados</label>
					<div class='controls controls-row'>	
					<?
						$sql_estados = "SELECT
											estado,
											nome
										FROM tbl_estado 
										WHERE pais = 'BR' 
										AND visivel = 't'
										ORDER BY nome";

						$res_estados = pg_query($con, $sql_estados);
						if (pg_numrows($res_estados) > 0) {
		                    echo "<select name='estados' id='estados' class='span8 controls inptc6' onchange='montaComboCidade(this.value)'>\n";
		                        echo "<option value='00'>Selecione</option>";

		                        for ($i = 0 ; $i < pg_numrows ($res_estados) ; $i++){
		                            $aux_estado  = trim(pg_result($res_estados,$i,estado));
		                            $aux_nome	 = trim(pg_result($res_estados,$i,nome));	                            

		                            echo "<option value='$aux_estado'";		                            
		                            echo ">$aux_nome</option>\n";
		                        }
		                    echo "</select>";
		                }
		            ?>
					</div>
				</div>
			</div>					
			<div class='span4'>
				<div class='control-group <?=(in_array("cidade", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='cidade'>Cidade</label>
					<div class='controls controls-row'>	
						<select name='cidade' id='cidade' class='parametros_tabela'>
							<option>Selecione um estado</option>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("linha", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='linha'>Linha</label>
					<div class='controls controls-row'>	
					<?
						$sql_linha = "SELECT
											nome,
											linha
										FROM tbl_linha 
										WHERE fabrica = {$login_fabrica} 
										AND ativo = true
										ORDER BY nome";

						$res_linha = pg_query($con, $sql_linha);
						if (pg_numrows($res_linha) > 0) {
		                    echo "<select name='linha' id='linha' class='span8 controls inptc6'>\n";
		                        echo "<option value='00'>Selecione</option>";

		                        for ($i = 0 ; $i < pg_numrows ($res_linha) ; $i++){
		                            $aux_codigo_linha   = trim(pg_result($res_linha,$i,linha));
		                            $aux_nome	 		= trim(pg_result($res_linha,$i,nome));	                            

		                            echo "<option value='$aux_codigo_linha'";
		                            echo ">$aux_nome</option>\n";
		                        }
		                    echo "</select>";
		                }
		            ?>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("familia", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='familia'>Família</label>
					<div class='controls controls-row'>	
					<?
						$sql_familia = "SELECT
											familia,
											descricao
										FROM tbl_familia
										WHERE fabrica = {$login_fabrica} 
										AND ativo = true
										ORDER BY descricao";

						$res_familia = pg_query($con, $sql_familia);
						if (pg_numrows($res_familia) > 0) {
		                    echo "<select name='familia' id='familia' class='span10 controls inptc6'>\n";
		                        echo "<option value='00'>Selecione</option>";

		                        for ($i = 0 ; $i < pg_numrows ($res_familia) ; $i++){
		                            $aux_codigo_familia   = trim(pg_result($res_familia,$i,familia));
		                            $aux_nome	 		= trim(pg_result($res_familia,$i,descricao));	                            

		                            echo "<option value='$aux_codigo_familia'";
		                            echo ">$aux_nome</option>\n";
		                        }
		                    echo "</select>";
		                }
		            ?>
					</div>
				</div>
			</div>
		</div>	
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto'>Produto</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="produto" id="produto" class='span12' value="<? echo $produto ?>">
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("descricao_produto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_produto'>Descrição</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="descricao_produto" id="descricao_produto" class='span12' value="<? echo $descricao_produto ?>">
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
						</div>
					</div>
				</div>
			</div>			
		</div>				
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("cpf_cnpj", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='cpf'>CPF</label>
					<input type="radio" id="cpf" name="opt_cpf_cnpj" value="cpf" onchange="selecionarCpfCnpj(this.value);"/>&nbsp;
					<label class='control-label' for='cnpj'>CNPJ</label>
					<input type="radio" id="cnpj" name="opt_cpf_cnpj" value="cnpj" onchange="selecionarCpfCnpj(this.value);"/>&nbsp;
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="cpf_cnpj" id="cpf_cnpj" class="span12" value="<? echo $cpf_cnpj ?>" onclick="document.getElementById('cpf_cnpj').value = '';">
						</div>
					</div>
				</div>
			</div>					
			<div class='span4'>
				<div class='control-group <?=(in_array("classificacao", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='classificacao'>Classificação</label>
					<div class='controls controls-row'>	
					<?
						$sql_classificacao = "SELECT 
													descricao,
													hd_classificacao
													FROM tbl_hd_classificacao
													WHERE fabrica = {$login_fabrica}
													AND ativo = true
													ORDER BY descricao";

						$res_classificacao = pg_query($con, $sql_classificacao);
						if (pg_numrows($res_linha) > 0) {
		                    echo "<select name='classificacao' id='classificacao' class='span8 controls inptc6'>\n";
		                        echo "<option value='00'>Selecione</option>";

		                        for ($i = 0 ; $i < pg_numrows ($res_classificacao) ; $i++){
		                            $aux_hd_classificacao   = trim(pg_result($res_classificacao,$i,hd_classificacao));
		                            $aux_descricao	 		= trim(pg_result($res_classificacao,$i,descricao));	                            

		                            echo "<option value='$aux_hd_classificacao'";
		                            echo ">$aux_descricao</option>\n";
		                        }
		                    echo "</select>";
		                }
		            ?>
					</div>
				</div>
			</div>			
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("origem", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='origem'>Origem</label>
					<div class='controls controls-row'>	
					<?
						$sql_origem = "SELECT 
											descricao,
											hd_chamado_origem
											FROM tbl_hd_chamado_origem
											WHERE fabrica = {$login_fabrica}
											AND ativo = true";

						$res_origem = pg_query($con, $sql_origem);
						if (pg_numrows($res_origem) > 0) {
		                    echo "<select name='origem' id='origem' class='span8 controls inptc6'>\n";
		                        echo "<option value='00'>Selecione</option>";

		                        for ($i = 0 ; $i < pg_numrows ($res_origem) ; $i++){
		                            $aux_chamado_origem  	= trim(pg_result($res_origem,$i,hd_chamado_origem));
		                            $aux_descricao	 		= trim(pg_result($res_origem,$i,descricao));	                            

		                            echo "<option value='$aux_chamado_origem'";
		                            echo ">$aux_descricao</option>\n";
		                        }
		                    echo "</select>";
		                }
		            ?>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("centro_distrib", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='centro_distribuicao'>Centro Distribuição</label>
					<div class='controls controls-row'>	
	                    <div class='span12'>
	                        <select name="centro_distribuicao" id="centro_distribuicao">
	                                <option value="mk_vazio" name="mk_vazio" <?php echo ($centro_distrib == "mk_vazio") ? "SELECTED" : ""; ?>>Selecione</option>
	                                <option value="mk_nordeste" name="mk_nordeste" <?php echo ($centro_distrib == "mk_nordeste") ? "SELECTED" : ""; ?>>MK Nordeste</option>
	                                <option value="mk_sul" name="mk_sul" <?php echo ($centro_distrib == "mk_sul") ? "SELECTED" : ""; ?>>MK Sul</option>
	                        </select>
	                    </div>
					</div>
				</div>			
			</div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'>Código Posto</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>">
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
		<p><br />
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br />		
</form>
<?php

if(!empty($data_inicial) AND !empty($data_final) OR !empty($pedido)) {		
	if(in_array($login_fabrica, array(151))) {				
		if(!empty($_POST['posto'])){
			$sql = "SELECT posto 
						FROM tbl_posto_fabrica 
						WHERE fabrica = {$login_fabrica} AND codigo_posto = '{$codigo_posto}'";

			$res = pg_query($con, $sql);
			$posto = pg_fetch_result($res, 0, "posto");
		}

		if(!empty($pedido)){
			$sql_pedido = " AND tbl_pedido.pedido = {$pedido}";
		}

		if(!empty($tipo_pedido)){
			$sql_x_tipo_pedido = " AND tbl_tipo_pedido.tipo_pedido = {$tipo_pedido}";
		}

		if(!empty($protocolo)){
			$sql_x_protocolo  = " AND tbl_hd_chamado.hd_chamado = {$protocolo}";			
		}

		if(!empty($uf) AND $uf != '00'){
			$sql_x_uf = " AND tbl_posto.estado = '{$uf}'";			
		}

		if(!empty($cidade) AND $cidade != 'Selecione um estado'){
			$sql_x_cidade = " AND tbl_posto.cidade ILIKE '{$cidade}%'";
		}

		if(!empty($familia) AND $familia != '00'){
			$sql_x_familia  = " AND (tbl_familia.familia = {$familia} OR fhd.familia = {$familia})";			
		}

		if(!empty($linha) AND $linha != '00'){
			$sql_x_linha  = " AND (tbl_linha.linha = {$linha} OR lhd.linha = {$linha})";			
		}
		
		if(!empty($produto) AND !empty($descricao_produto)){
			$sql_x_prod_desc = "AND tbl_produto.produto = {$produto} AND tbl_produto.descricao ILIKE '{$descricao_produto}%'";			
		}

		if($obj_cpf_cnpj == "cnpj"){
			$sql_x_cpf_cnpj = " AND (tbl_os.revenda_cnpj = '{$cpf_cnpj}' OR tbl_hd_chamado_extra.cpf = '{$cpf_cnpj}')";			
		} else if($obj_cpf_cnpj == "cpf") {
			$sql_x_cpf_cnpj = " AND (tbl_os.consumidor_cpf = '{$cpf_cnpj}' OR tbl_hd_chamado_extra.cpf = '{$cpf_cnpj}')";
		}

		if(!empty($os)){
			$sql_x_os = " AND tbl_os.os = {$os}";
		}

		if(!empty($codigo_posto) && !empty($descricao_posto)){
			$sql_posto = " AND tbl_pedido.posto = {$posto} AND tbl_posto.nome LIKE '{$descricao_posto}%'";
		}else if(!empty($codigo_posto) && empty($descricao_posto)){
			$sql_posto = " AND tbl_pedido.posto = {$posto}";
		}else if(empty($codigo_posto) && !empty($descricao_posto)){
			$sql_posto = " AND tbl_posto.nome LIKE '{$descricao_posto}%'";
		}

		if(!empty($classificacao) AND $classificacao != '00'){
			$sql_x_classificacao = " AND tbl_hd_chamado.hd_classificacao = {$classificacao}";
		}

		if(!empty($origem) AND $origem != '00'){
			$sql_x_origem  = " AND tbl_hd_chamado_origem.hd_chamado_origem = {$origem}";			
		}

        if($centro_distrib != 'mk_vazio') {
	        $sql_x_cd  = " AND tbl_produto.parametros_adicionais::json->>'centro_distribuicao' = '$centro_distrib'";	        	        	        
	    }
		
		$sql = "SELECT
				tbl_pedido.pedido,
				to_char(tbl_pedido.data,'DD/MM/YYYY') AS data,
				tbl_posto_fabrica.codigo_posto,
				tbl_posto.nome,
				tbl_posto.estado,
				tbl_posto.cidade,
				tbl_hd_chamado.hd_chamado,
				tbl_hd_chamado.hd_classificacao,
				tbl_hd_classificacao.descricao,
				tbl_os.consumidor_cpf,
				tbl_os.os,
				tbl_tipo_pedido.descricao AS tipo_pedido,
				CASE WHEN tbl_produto.referencia IS NOT NULL THEN tbl_produto.referencia ELSE phd.referencia END AS referencia_produto,
				CASE WHEN tbl_produto.descricao IS NOT NULL THEN tbl_produto.descricao ELSE phd.descricao END AS descricao_produto,
				CASE WHEN tbl_produto.parametros_adicionais IS NOT NULL THEN tbl_produto.parametros_adicionais::json->>'centro_distribuicao' ELSE phd.parametros_adicionais::json->>'centro_distribuicao' END AS centro_distribuicao,
				CASE WHEN tbl_linha.nome IS NOT NULL THEN tbl_linha.nome ELSE lhd.nome END as linha,
				CASE WHEN tbl_familia.descricao IS NOT NULL THEN tbl_familia.descricao ELSE fhd.descricao END AS familia,
				tbl_hd_chamado_origem.descricao AS origem
				FROM tbl_pedido
				JOIN tbl_posto ON tbl_posto.posto = tbl_pedido.posto
				JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
				JOIN tbl_tipo_pedido ON tbl_pedido.tipo_pedido = tbl_tipo_pedido.tipo_pedido
				LEFT JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.pedido = tbl_pedido.pedido
				LEFT JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
				LEFT JOIN tbl_hd_chamado_origem ON tbl_hd_chamado_extra.hd_chamado_origem = tbl_hd_chamado_origem.hd_chamado_origem
				LEFT JOIN tbl_hd_classificacao ON tbl_hd_classificacao.hd_classificacao = tbl_hd_chamado.hd_classificacao
				LEFT JOIN tbl_os_item ON tbl_pedido.pedido = tbl_os_item.pedido
				LEFT JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
				LEFT JOIN tbl_os ON tbl_os.os = tbl_os_produto.os
				LEFT JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto
				LEFT JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha
				LEFT JOIN tbl_familia ON tbl_produto.familia = tbl_familia.familia
				LEFT JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado_item.produto IS NOT NULL
				LEFT JOIN tbl_produto phd ON tbl_hd_chamado_item.produto = phd.produto AND tbl_hd_chamado_item.produto IS NOT NULL
				LEFT JOIN tbl_linha lhd ON phd.linha = lhd.linha
				LEFT JOIN tbl_familia fhd ON phd.familia = fhd.familia
				WHERE tbl_pedido.fabrica = {$login_fabrica}
				AND tbl_pedido.exportado IS NULL
				AND tbl_pedido.status_pedido = 1
				{$sql_pedido}
				{$sql_x_tipo_pedido}
				{$sql_x_protocolo}
				{$sql_x_uf}
				{$sql_x_cidade}
				{$sql_x_familia}
				{$sql_x_linha}				
				{$sql_x_prod_desc}
				{$sql_x_classificacao}
				{$sql_x_origem}				
				{$sql_x_cpf_cnpj}
				{$sql_x_os}
				{$sql_x_cd}
				{$sql_posto}				
				{$sql_datas}";

				//die(nl2br($sql));

		$res = pg_query($con, $sql);		
	}

	//Gerar Excel
	if (isset($res)) {			
		if (pg_num_rows($res) > 0) {
	
			$dir_excel = '/tmp/';
			$arq_excel = 'exportar_pedidos_lote_' . $login_fabrica . '.xls';

			$fp = fopen("{$dir_excel}{$arq_excel}", "w");

			fwrite($fp,"<table style='max-width: 100%; table-layout: fixed' id='resultado' class='table table-striped table-bordered table-large' >
								<thead>
									<tr class='titulo_coluna' >
										<th>Pedido</th>
										<th>Tipo Pedido</th>
										<th>Data Pedido</th>
										<th>Código do Posto</th>
										<th>Nome do Posto</th>	
										<th>UF</th>
										<th>Cidade</th>
										<th>Protocolo</th>
										<th>Classificação</th>										
										<th>CPF/CNPJ</th>
										<th>OS</th>							
										<th>Código Produto</th>
										<th>Nome Produto</th>
										<th>Centro Distribuição</th>																				
										<th>Linha</th>
										<th>Família</th>										
										<th>Origem</th>										
									</tr>
								</thead>
								<tbody>");

			for ($i = 0; $i < pg_num_rows($res); $i++) {
				$pedido 			= pg_fetch_result($res, $i, "pedido");
				$tipo_pedido 		= pg_fetch_result($res, $i, "tipo_pedido");	
				$data 				= pg_fetch_result($res, $i, "data");
				$codigo_posto 		= pg_fetch_result($res, $i, "codigo_posto");
				$nome_posto			= pg_fetch_result($res, $i, "nome");				
				$uf 				= pg_fetch_result($res, $i, "estado");
				$cidade  			= pg_fetch_result($res, $i, "cidade");				
				$hd_chamado 		= pg_fetch_result($res, $i, "hd_chamado");
				$hd_classificacao 	= pg_fetch_result($res, $i, "hd_classificacao");
				$hd_descricao 		= pg_fetch_result($res, $i, "descricao");
				$x_cpf_cnpj			= pg_fetch_result($res, $i, "consumidor_cpf");
				$os 				= pg_fetch_result($res, $i, "os");
				$produto 			= pg_fetch_result($res, $i, "referencia_produto");
				$pd_descricao		= pg_fetch_result($res, $i, "descricao_produto");
				$cd 				= pg_fetch_result($res, $i, "centro_distribuicao");				
				$linha 				= pg_fetch_result($res, $i, "linha");
				$familia 			= pg_fetch_result($res, $i, "familia");						
				$origem				= pg_fetch_result($res, $i, "origem");					

				if($cd == "mk_sul"){
					$x_cd = "MK Sul";
				} else if ($cd == "mk_nordeste"){
					$x_cd = "MK Nordeste";
				} else {
					$x_cd = "";
				}

				fwrite($fp, "<tr>
								<td>" . $pedido 			. "</td>
								<td>" . $tipo_pedido 		. "</td>
								<td>" . $data 				. "</td>
								<td>" . $codigo_posto 		. "</td>
								<td>" . $nome_posto 		. "</td>
								<td>" . $uf 				. "</td>
								<td>" . $cidade 			. "</td>
								<td>" . $hd_chamado 		. "</td>								
								<td>" . $hd_descricao 		. "</td>
								<td>" . $x_cpf_cnpj 		. "</td>
								<td>" . $os 				. "</td>
								<td>" . $produto 			. "</td>
								<td>" . $pd_descricao 		. "</td>
								<td>" . $x_cd 				. "</td>								
								<td>" . $linha 				. "</td>
								<td>" . $familia 			. "</td>							
								<td>" . $origem 			. "</td>								
							</tr>");		
			}

			fwrite($fp, "</tbody></table>");
			fclose($fp);

			system("mv {$dir_excel}{$arq_excel} xls/{$arq_excel}");				

			echo "<br />";
	?>
	</div>
</div>
		<div class="container w-100" style="width: 95%;">
			<div class="row w-100">
				<div class="col w-100">
					<table width="100%" class='table table-striped table-bordered'>
						<thead>
							<tr class='titulo_coluna' >
								<th>
									<input type="checkbox" id="item_selecionado_todos" name="item_selecionado_todos" />
								</th>
								<th>Pedido</th>
								<th>Tipo Pedido</th>
								<th>Data Pedido</th>
								<th>Código do Posto</th>
								<th>Nome do Posto</th>	
								<th>UF</th>
								<th>Cidade</th>
								<th>Protocolo</th>
								<th>Classificação</th>								
								<th>CPF/CNPJ</th>
								<th>OS</th>							
								<th>Código Produto</th>
								<th>Nome Produto</th>
								<th>Centro Distribuição</th>																				
								<th>Linha</th>
								<th>Família</th>										
								<th>Origem</th>
								<th>Ações</th>				
							</tr>
						</thead>
						<tbody>

							<?php								
							for ($i = 0; $i < pg_num_rows($res); $i++) {
								$pedido 			= pg_fetch_result($res, $i, "pedido");
								$tipo_pedido 		= pg_fetch_result($res, $i, "tipo_pedido");
								$data 				= pg_fetch_result($res, $i, "data");
								$codigo_posto 		= pg_fetch_result($res, $i, "codigo_posto");
								$nome_posto			= pg_fetch_result($res, $i, "nome");				
								$uf 				= pg_fetch_result($res, $i, "estado");
								$cidade  			= pg_fetch_result($res, $i, "cidade");				
								$hd_chamado 		= pg_fetch_result($res, $i, "hd_chamado");
								$hd_classificacao 	= pg_fetch_result($res, $i, "hd_classificacao");
								$hd_descricao 		= pg_fetch_result($res, $i, "descricao");
								$x_cpf_cnpj			= pg_fetch_result($res, $i, "consumidor_cpf");
								$os 				= pg_fetch_result($res, $i, "os");
								$produto 			= pg_fetch_result($res, $i, "referencia_produto");
								$pd_descricao		= pg_fetch_result($res, $i, "descricao_produto");
								$cd 				= pg_fetch_result($res, $i, "centro_distribuicao");				
								$linha 				= pg_fetch_result($res, $i, "linha");
								$familia 			= pg_fetch_result($res, $i, "familia");						
								$origem				= pg_fetch_result($res, $i, "origem");										

								if($cd == "mk_sul"){
									$x_cd = "MK Sul";
								} else if ($cd == "mk_nordeste"){
									$x_cd = "MK Nordeste";
								} else {
									$x_cd = "";
								}

							?>

							<tr>
								<td style="text-align: center;">
									<input type="checkbox" name="item_selecionado_<?=$pedido?>[]" />
								</td>
								<td><?=$pedido?></td>
								<td><?=$tipo_pedido?></td>
								<td><?=$data?></td>
								<td><?=$codigo_posto?></td>
								<td><?=$nome_posto?></td>
								<td><?=$uf?></td>
								<td><?=$cidade?></td>
								<td><?=$hd_chamado?></td>								
								<td><?=$hd_descricao?></td>
								<td><?=$x_cpf_cnpj?></td>
								<td><?=$os?></td>
								<td><?=$produto?></td>
								<td><?=$pd_descricao?></td>
								<td><?=$x_cd?></td>								
								<td><?=$linha?></td>
								<td><?=$familia?></td>							
								<td><?=$origem?></td>
								<td>
									<button type='button' class='btn exportar_pedido' data-pedido='<?=$pedido?>'>Exportar</button>
								</td>
							</tr>
							<?php
							}
							?>
						</tbody>		
					</table>
				</div>
			</div>
		</div><br />
			<div id='exportar_todos' style='text-align: center;'>
				<button type='button' class='btn exportar_pedido' data-pedido='<?=$pedido?>'>Exportar Pedidos Selecionados</button>
			</div><br />	
			<?
				$jsonPOST = excelPostToJson($_REQUEST);
				$jsonPOST = utf8_decode($jsonPOST);
			?>
			<div id='gerar_arquivo_excel' style='text-align: center;' class='btn_excel'>				
				<?php echo "<a href='xls/{$arq_excel}' target='_blank'>"; ?>
				<span><img src='imagens/excel.png' height='20px' width='20px' align='absmiddle'></span>
				<span class="txt">Gerar Arquivo Excel</span></a>
			</div>
			<?php				
		}else{
		?>
			<div class="container">
				<div class="alert"><h4>Nenhum resultado encontrado</h4></div>
			</div>
		<?php
		}
	}
}
?>	
<?php include "rodape.php"; ?>

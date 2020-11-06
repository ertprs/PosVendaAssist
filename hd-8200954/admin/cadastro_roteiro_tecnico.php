<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';
include_once '../class/AuditorLog.php';
include_once "../class/tdocs.class.php";
$tDocs       = new TDocs($con, $login_fabrica);
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
if ($login_fabrica == 42) {
	include 'cadastro_roteiro_tecnico_new.php';
	exit;
}
use html\HtmlBuilder;
use model\ModelHolder;
if(!empty($_GET["roteiro"])){
	$roteiro = getRoteiro($_GET["roteiro"]);
	$roteiro = $roteiro[$_GET["roteiro"]];
}
if(isset($_POST["action"]) && $_POST["action"] == "deleteRoteiro" && !empty($_POST["roteiro"])){
	$modelRoteiro = ModelHolder::init("Roteiro");		
	if($modelRoteiro->delete($_POST["roteiro"]) > 0 ){
		die(json_encode(array('success' => "true" )));
	}else{
		die(json_encode(array('success' => "false" )));
	}
	
}
if(isset($_POST["action"]) && $_POST["action"] == "getEstados" && !empty($_POST["estado"])){
	
    $result = "<select id='cidade' name='cidade[]' multiple='multiple' class='span12' >";
    $result .= "<option value=''></option>";
    $get_cidades = getCidades($_POST["estado"]);
    if (!empty($_POST["arraicidade"])) {
    	$cidades = str_replace("\\", "", $_POST["arraicidade"]);
    	$cidades = json_decode($cidades,true);
    }else{
    	$cidades = array();
    }
    if (!empty($_POST["array_posto_cidade"])) {
    	$cidades_p = str_replace("\\", "", $_POST["array_posto_cidade"]);    	
    	$cidades_p = json_decode($cidades_p,true);
    }
    foreach ($get_cidades as $key => $value) {
    	$selected = "";	
    	if (!empty($cidades)) {
    		$selected = (in_array($value['cod_ibge'], $cidades)) ? "SELECTED":"";
    	}
    	if (!empty($cidades_p)) {
    		foreach ($cidades_p as $key => $conteudo) {
    			$sqlRoteiroPosto = "SELECT posto,cidade FROM tbl_roteiro_posto WHERE roteiro_posto = {$key};";
    			$resRoteiroPosto = pg_query($con,$sqlRoteiroPosto);    			
        		if (pg_num_rows($resRoteiroPosto) > 0) {
        			$postoRoteiro = pg_fetch_result($resRoteiroPosto, 0, posto);
        			$cidadeRoteiro = pg_fetch_result($resRoteiroPosto, 0, cidade);
        			if (!empty($postoRoteiro) ) {
        				//pesquiso pelo estado do posto
        				$sqlPostoFabrica = "SELECT cod_ibge 
        										FROM tbl_posto_fabrica        										
        										WHERE fabrica = {$login_fabrica} AND posto = {$postoRoteiro}";
        				$resPostoFabrica = pg_query($con,$sqlPostoFabrica);
        				$estadoPosto = pg_fetch_result($resPostoFabrica, 0, cod_ibge);
        				
        				if ($value['cod_ibge'] == $estadoPosto) {
				
							$selected = "SELECTED";												
						}
        			}
        			if (!empty($cidadeRoteiro)) {
        				//pesquiso pelo estado da cidade
        				$sqlCidade = "SELECT cod_ibge FROM tbl_cidade WHERE cidade = {$cidadeRoteiro}";
        				$resCidade = pg_query($con,$sqlCidade);
        				$estadoCidade = pg_fetch_result($resCidade, 0, cod_ibge);
        		
        				if ($value['cod_ibge'] == $estadoCidade) {
				
							$selected = "SELECTED";												
						}
        			}
        		}			                        	
    		}    		
    	}
    	$result .= "<option value='".$value['cod_ibge']."'".$selected.">".$value['cidade']."</option>";
    }
    $result .= "</select>";
    echo $result;
  	exit;
}
if(isset($_POST["btn_acao"]) && $_POST["btn_acao"] == "save"){
	$roteiro = array();
	try{
		$validation = validateFields();
		$dataInicio  = DateTime::createFromFormat("d/m/Y", $_POST["data_inicio"]);
		$dataTermino = DateTime::createFromFormat("d/m/Y", $_POST["data_termino"]);
		$roteiro["roteiro"] = (empty($_POST["roteiro"])) ? NULL : $_POST['roteiro'];
		$roteiro["data_inicio"] =  $_POST["data_inicio"];
		$roteiro["data_termino"] = $_POST["data_termino"];		
		$roteiro["tipo_roteiro"] = $_POST["tipo_roteiro"];
		$roteiro["status_roteiro"] = $_POST["status_roteiro"];
		$roteiro["solicitante"] = substr($_POST["solicitante"], 0, 20);
		$roteiro["qtde_dias"] = $_POST['qtde_dias'];
		$roteiro["excecoes"] = $_POST["excecoes"];
		$roteiro["ativo"] = $_POST["ativo"];
		$roteiro["fabrica"] = $login_fabrica;
		
		$list["estado"] = $_POST["estado"];
		$list["cidade"] = $_POST["cidade"];
		$list["postos"] = $_POST["posto"];
		$list["tecnicos"] = $_POST["tecnico"];
		$postos_fabrica = "'".implode("','", $list["postos"])."'";
		//pegar as cidades do posto
		$sql_posto = "SELECT tbl_cidade.cod_ibge
						FROM tbl_posto_fabrica
						JOIN tbl_cidade on tbl_posto_fabrica.contato_cidade = tbl_cidade.nome
						WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
						AND tbl_posto_fabrica.codigo_posto in ({$postos_fabrica})";
		$res_posto = pg_query($con,$sql_posto);
		
		// verificar se existe a cidade do posto no array de cidades, se existir excluir do array
		if(pg_num_rows($res_posto) > 0){
			for ($i=0; $i < pg_num_rows($res_posto); $i++) { 
				$cod_cidade_posto = pg_fetch_result($res_posto, $i, cod_ibge);
				if (in_array($cod_cidade_posto, $list['cidade'])) {
					
					$excluir_cidade=array_search($cod_cidade_posto,$list['cidade']);
					
					unset($list['cidade'][$excluir_cidade]);
				}
			}
		}
		if(empty($validation)){
			$dataInicio  = DateTime::createFromFormat("d/m/Y", $_POST["data_inicio"]);
			$dataTermino = DateTime::createFromFormat("d/m/Y", $_POST["data_termino"]);
			$roteiro["data_inicio"] = $dataInicio->format(DateTime::ISO8601);
			$roteiro["data_termino"] = $dataTermino->format(DateTime::ISO8601);
			if(empty($roteiro["roteiro"])){
				unset($roteiro["roteiro"]);
				$modelRoteiro = ModelHolder::init("Roteiro");
				$roteiroId = $modelRoteiro->insert($roteiro);
			} else{				
				$roteiroId= $roteiro["roteiro"];
				//unset($roteiro["roteiro"]);
				$modelRoteiro = ModelHolder::init("Roteiro");
				$modelRoteiro->update($roteiro, $roteiroId);
				//deletar as informações das tabelas existentes para depois inserir novamente
				deletar_tabelas("tbl_roteiro_posto",$roteiroId);
				//exit;
				deletar_tabelas("tbl_roteiro_tecnico",$roteiroId);
				
			}
			//Inicio List
			//update list postos
				foreach ($list["postos"] as $key => $value) {
					//pegar o id do posto
					$modelPostoFabrica = ModelHolder::init("PostoFabrica");
					$posto = $modelPostoFabrica->find(array("codigo_posto"=>$value, 
															  "fabrica"=>$login_fabrica), array("posto"));
					$postoId = $posto[0]["posto"];
					//verifica se o posto existe na tabela para este roteiro
					$modelRoteiroPosto = ModelHolder::init("RoteiroPosto");					
					$roteiroPosto = $modelRoteiroPosto->find(array( "roteiro"=>$roteiroId,
																	  "posto"=>$postoId),array("roteiro_posto"));
					$roteiroPostoId = $roteiroPosto[0]["roteiroPosto"];
					
					if (empty($roteiroPostoId)) {
						//insere
						$modelRoteiroPosto->insert(array("roteiro"=> $roteiroId, "posto"=>$postoId));
					}else{
						//update
						$modelRoteiroPosto->update(array("roteiro"=> $roteiroId, "posto"=>$postoId));
					}
				}
				//update list cidade
				foreach ($list['cidade'] as $key => $valueCidade) {
					
					//pegar o idCidade na tabela tbl_cidade passando o cod_ibge					
					$modelCidade = ModelHolder::init("Cidade");
					$cidade = $modelCidade ->find(array("cod_ibge"=>$valueCidade),array("cidade")); 
					$cidadeId = $cidade[0]["cidade"];
					
					//verifica se a cidade  existe na tabela para este roteiro
					$modelRoteiroCidade = ModelHolder::init("RoteiroPosto");					
					$roteiroCidade = $modelRoteiroCidade->find(array( "roteiro"=>$roteiroId,
																	  "cidade"=>$cidadeId),array("roteiro_posto"));
					$roteiroCidadeId = $roteiroCidade[0]["roteiroPosto"];
					if (empty($roteiroCidadeId)) {
						//insere
						$modelRoteiroCidade -> insert(array("roteiro" => $roteiroId, "cidade" =>$cidadeId ));	
					}else{
						//update
						$modelRoteiroCidade -> update(array("roteiro" => $roteiroId, "cidade" =>$cidadeId ));						
					}
				}
				//update tecnicos
				foreach ($list["tecnicos"] as $tecnico) {
					$modelTecnico = ModelHolder::init("Tecnico");
					$tecnico = $modelTecnico->find(array("cpf"=>$tecnico, 
															  "fabrica"=>$login_fabrica), array("tecnico"));
					$tecnicoId = $tecnico[0]["tecnico"];
					$modelRoteiroTecnico = ModelHolder::init("RoteiroTecnico");
					$roteiroTecnico = $modelRoteiroTecnico->find(array( "roteiro"=>$roteiroId,
																		  "tecnico"=>$cidadeId),array("roteiro_tecnico"));
					$roteiroTecnicoId = $roteiroTecnico[0]["roteiroTecnico"];
					if (empty($roteiroTecnicoId)) {
						//insere
						$modelRoteiroTecnico->insert(array("roteiro"=> $roteiroId, "tecnico"=>$tecnicoId));
					}else{
						//update
						$modelRoteiroTecnico->update(array("roteiro"=> $roteiroId, "tecnico"=>$tecnicoId));
					}
				}
			//Fim insere tabelas-
			$msg = (empty($roteiro["roteiro"])) ? "Cadastrado com sucesso" : "Alterado com sucesso";
			unset($_POST);
			unset($_REQUEST);
			unset($roteiro);
			unset($list);
			$htmlBuilder = HtmlBuilder::getInstance();
			$htmlBuilder->setValues(array());
		}else{
			$msg_erro = $validation;
	
		}
	}catch(Exception $ex){
		echo $ex->getMessage();exit;
	}
}
$htmlBuilder = HtmlBuilder::getInstance();
$layout_menu = "tecnica";
$title = "Cadastro de Roteiros";
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

<link href="../plugins/jquery_multiselect/css/multi-select.css" media="screen" rel="stylesheet" type="text/css">
<script src="plugins/jquery_multiselect/js/jquery.multi-select.js" type="text/javascript"></script>
<script src="../plugins/quicksearch-master/jquery.quicksearch.js" type="text/javascript"></script>
<script type="text/javascript">
	function getCidades(el){
		$("#cidade").html("");
		var estado = $(el).val() || [];
		var arraicidade = $("#array_cidade").val();
		var array_posto_cidade = $("#array_posto_cidade").val();
		//alert(arraicidade);
		if (estado != "") {
			$.ajax({
				url: '<?php echo $_SERVER['PHP_SELF']?>',
				type: "post",
				data:{
					estado: estado,
					action: "getEstados",
					arraicidade: arraicidade,
					array_posto_cidade: array_posto_cidade
				},
				complete : function(response){
					response = response.responseText;
					console.log(response);					
					if(response == ""){
	                	$(".box-cidade").html("<select id='cidade' name='cidade[]' multiple='multiple' class='span12' ></select>");
						$('#cidade').multiSelect();
	                }else{
	                	$(".box-cidade").html(response);
	                  	$('#cidade').multiSelect({
	            		  selectableHeader: "<input type='text' class='search-input span12' autocomplete='off' placeholder='Pesquisar..'>",
						  selectionHeader: "<input type='text' class='search-input span12' autocomplete='off' placeholder='Pesquisar..'>",
						  afterInit: function(ms){
						    var that = this,
						        $selectableSearch = that.$selectableUl.prev(),
						        $selectionSearch = that.$selectionUl.prev(),
						        selectableSearchString = '#'+that.$container.attr('id')+' .ms-elem-selectable:not(.ms-selected)',
						        selectionSearchString = '#'+that.$container.attr('id')+' .ms-elem-selection.ms-selected';
						    that.qs1 = $selectableSearch.quicksearch(selectableSearchString)
						    .on('keydown', function(e){
						      if (e.which === 40){
						        that.$selectableUl.focus();
						        return false;
						      }
						    });
						    that.qs2 = $selectionSearch.quicksearch(selectionSearchString)
						    .on('keydown', function(e){
						      if (e.which == 40){
						        that.$selectionUl.focus();
						        return false;
						      }
						    });
						  },
						  afterSelect: function(){
						    this.qs1.cache();
						    this.qs2.cache();
						  },
						  afterDeselect: function(){
						    this.qs1.cache();
						    this.qs2.cache();
						  }
	                	});
	                }
	                
				}
			});
		}else{			
			$(".box-cidade").html("<select id='cidade' name='cidade[]' multiple='multiple' class='span12' ></select>");
			$('#cidade').multiSelect();
		}		
	}
	$(function() {
		var datePickerConfig = {maxDate: null, dateFormat: "dd/mm/yy",dayNames: ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'], dayNamesMin: ['D','S','T','Q','Q','S','S','D'], dayNamesShort: ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb','Dom'], monthNames: ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'], monthNamesShort: ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'], nextText: 'Próximo', prevText: 'Anterior'};
		
		$("#data_inicio").datepicker(datePickerConfig).mask("99/99/9999");
		$("#data_termino").datepicker(datePickerConfig).mask("99/99/9999");
		$.autocompleteLoad(Array("posto","tecnico"));
		
		Shadowbox.init();
		//Inicio multi-select
		$('#estado').multiSelect({
		  selectableHeader: "<input type='text' class='search-input span12' autocomplete='off' placeholder='Pesquisar..'>",
		  selectionHeader: "<input type='text' class='search-input span12' autocomplete='off' placeholder='Pesquisar..'>",
		  afterInit: function(ms){
		    var that = this,
		        $selectableSearch = that.$selectableUl.prev(),
		        $selectionSearch = that.$selectionUl.prev(),
		        selectableSearchString = '#'+that.$container.attr('id')+' .ms-elem-selectable:not(.ms-selected)',
		        selectionSearchString = '#'+that.$container.attr('id')+' .ms-elem-selection.ms-selected';
		    that.qs1 = $selectableSearch.quicksearch(selectableSearchString)
		    .on('keydown', function(e){
		      if (e.which === 40){
		        that.$selectableUl.focus();
		        return false;
		      }
		    });
		    that.qs2 = $selectionSearch.quicksearch(selectionSearchString)
		    .on('keydown', function(e){
		      if (e.which == 40){
		        that.$selectionUl.focus();
		        return false;
		      }
		    });
		  },
		  afterSelect: function(){
		    this.qs1.cache();
		    this.qs2.cache();
		  },
		  afterDeselect: function(){
		    this.qs1.cache();
		    this.qs2.cache();
		  }
		});	
		$('#cidade').multiSelect();		
		//fim multi-select
		$('#data_termino').change(function(){
			var dataini = $('#data_inicio').val();
			var datater = $('#data_termino').val();
			if (dataini != '' && datater != '') {
				DAY = 1000 * 60 * 60  * 24;
				var nova1 = dataini.toString().split('/');
				Nova1 = nova1[1]+"/"+nova1[0]+"/"+nova1[2];
				var nova2 = datater.toString().split('/');
				Nova2 = nova2[1]+"/"+nova2[0]+"/"+nova2[2];
				d1 = new Date(Nova1)
				d2 = new Date(Nova2)
				days_passed = Math.round((d2.getTime() - d1.getTime()) / DAY) + 1 ;
				$('#qtde_dias').val(days_passed);
			}else{
				$('#qtde_dias').val('');
			}			
		});
		$('#data_inicio').change(function(){
			var dataini = $('#data_inicio').val();
			var datater = $('#data_termino').val();
			if (dataini != '' && datater != '') {
				DAY = 1000 * 60 * 60  * 24;
				var nova1 = dataini.toString().split('/');
				Nova1 = nova1[1]+"/"+nova1[0]+"/"+nova1[2];
				var nova2 = datater.toString().split('/');
				Nova2 = nova2[1]+"/"+nova2[0]+"/"+nova2[2];
				d1 = new Date(Nova1)
				d2 = new Date(Nova2)
				days_passed = Math.round((d2.getTime() - d1.getTime()) / DAY) + 1 ;
				$('#qtde_dias').val(days_passed);
			}else{
				$('#qtde_dias').val('');
			}			
		});
		$('#add_posto').click(function(){
            var posto = $('#descricao_posto').val();
            var posto_id = $("#codigo_posto").val();
            if (posto_id && posto) {
                var option = '<option value="' + posto + '" class="' + posto_id + '">'+ posto_id+' - '+ posto + '</option>';
                var hidden = '<input type="hidden" name="posto[]" id="' + posto_id + '" value="' + posto_id + '" />';
                $('#postos').append(option);
                $('#postos').append(hidden);
                $("#descricao_posto").val('');
                $("#codigo_posto").val('');
            }else{
            	alert("Favor preencha os campos Código Posto e Nome Posto");
            }
        });
        $('#rm_posto').click(function(){
        	$("select[name=postos] option:selected").each(function () {
            	var hidden = $(this).attr("class");
                 
                $(this).remove();
                $('input[value="'+ hidden +'"]').remove();
            });
        });
        $('#add_tecnico').click(function(){
            var tecnico = $('#nome_tecnico').val();
            var tecnico_id = $("#cpf_tecnico").val();
            if (tecnico_id && tecnico) {
                var option = '<option value="' + tecnico + '" class="' + tecnico_id + '">'+ tecnico_id+' - '+ tecnico + '</option>';
                var hidden = '<input type="hidden" name="tecnico[]" id="' + tecnico_id + '" value="' + tecnico_id + '" />';
                $('#tecnicos').append(option);
                $('#tecnicos').append(hidden);
                $("#nome_tecnico").val('');
                $("#cpf_tecnico").val('');
            }else{
            	alert("Favor preencha os campos CPF e Técnico Nome");
            }
        });
        $('#rm_tecnico').click(function(){
        	$("select[name=tecnicos] option:selected").each(function () {
            	var hidden = $(this).attr("class");
                 
                $(this).remove();
                $('input[value="'+ hidden +'"]').remove();
            });
        });
		$("span[rel=lupa]").click(function () {
			var estado = $("#estado").val();
			var cidade = $("#cidade").val();
			$("input[name=lupa_config]").attr({"estado": estado, "cidade":cidade})
			$.lupa($(this), ["estado", "cidade"]);
		});
		$("span[rel=lupa_tecnico]").click(function () {
			var estado = $("#estado").val();
			var cidade = $("#cidade").val();
			$("input[name=lupa_config]").attr({"estado": estado, "cidade":cidade})
			$.lupa($(this), ["estado", "cidade" , "tipotecnico"]);
		});
		setTimeout(getCidades($("#estado")),500);
	});
	function retorna_tecnico(retorno){		
		$("#cpf_tecnico").val(retorno.cpf);
		$("#nome_tecnico").val(retorno.nome);
	}
	function retorna_posto(retorno){
		$("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
	}
</script>
<style>
	.AutoListModel {
		display: none;
	}
	.ms-container{
	  background: transparent no-repeat 50% 50%;
	  width: 300px !important;
	}
</style>
<?php
if (count($msg_erro["msg"]) > 0) {
	
	?>
	<div class="alert alert-error">
		<h4><?php echo implode("<br/>", $msg_erro["msg"]);?></h4>
		<h4><?php echo implode("<br/>", $msg_erro["msg"]["obg"]);?></h4>
	</div>
	<?php
}
if (strlen($msg) > 0 AND count($msg_erro["msg"])==0) { ?>
    <div class="alert alert-success">
        <h4><? echo $msg; ?></h4>
    </div>
<? } 
?>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_roteiro_tecnico' METHOD='POST' ACTION='<?php echo $PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
	<input type="hidden" name="roteiro" value="<?php echo $roteiro['roteiro'] ?>"/>
	<div class='titulo_tabela '>Cadastro de Roteiro</div>
	<br/>

	<div class='row-fluid'>
		<div class='span1'></div>
		<div class='span3'>
			<div class='control-group <?php echo (in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_inicial'>Data Início</label>
				<div class='controls controls-row'>
					<div class='span8'>
						<h5 class='asteristico'>*</h5>
						<input type="text" name="data_inicio" id="data_inicio" size="12" maxlength="10" class='span12' value= "<?php echo $roteiro['data_inicio']?>">
					</div>
				</div>
			</div>
		</div>
		<div class='span3'>
			<div class='control-group <?php echo (in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_final'>Data Término</label>
				<div class='controls controls-row'>
					<div class='span8'>
						<h5 class='asteristico'>*</h5>
						<input type="text" name="data_termino" id="data_termino" size="12" maxlength="10" class='span12' value="<?php echo $roteiro['data_termino']?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?php echo (in_array("tipo_roteiro", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='tipo_roteiro'>Tipo de Roteiro</label>
				<div class='controls controls-row'>
					<div class='span10'>
						<h5 class='asteristico'>*</h5>
						<select id="tipo_roteiro" name="tipo_roteiro" class='span12' >
							<option value="">Selecione o tipo do Roteiro</option>
							<?php $tiporoteiro = array('RA' => 'Roteiro Administrativo' , 'RT' => 'Roteiro Técnico' ); 
								foreach ($tiporoteiro as $key => $value) {
									$selected = ($key==$roteiro["tipo_roteiro"]) ? "SELECTED" : "";
									?>
									<option <?php echo $selected; ?> value="<?php echo $key; ?>">
										<?php echo $value; ?>
									</option>
							<?	}?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class='span1'></div>
	</div>
	<div class="container">
		<div class='row-fluid'>
			<div class='span1'></div>
			<div class='span5'>
				<div class='control-group <?php echo (in_array("estado", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='estado'>Estado</label>
					<div class='controls controls-row'>
						<div class='span2 input-append'>
						<h5 class='asteristico'>*</h5>
							<select id="estado" name="estado[]" class='span2' multiple='multiple' onchange="getCidades(this);" >								
								<?php 								
								$estados = getEstados();
								$ar_estado = $_POST['estado'];								
								foreach($estados as $item){
									if (in_array($item['estado'], $ar_estado)) {
										$selected = "SELECTED";												
									}else{
									
										$selected = "";	
									}
									
									foreach ($roteiro["posto"] as $key => $valorkey) {
										$keyRoteiroPosto = $valorkey['roteiro_posto'];
										$sqlEstado = "SELECT cidade FROM tbl_roteiro_posto WHERE roteiro_posto = {$keyRoteiroPosto};";
										$resEstado = pg_query($con,$sqlEstado);
										$value = pg_fetch_result($resEstado, 0, cidade);
										if (count($value) > 0) {
											
											$sqlUF = "SELECT estado FROM tbl_cidade WHERE cidade = {$value};";
											$resUF = pg_query($con,$sqlUF);
											$UFCidade = pg_fetch_result($resUF, 0, estado);
											if ($item["estado"] == $UFCidade) {
											
												$selected = "SELECTED";												
											}else{
											
												$selected = "";	
											}
										}
									}
									if (array_key_exists('posto', $roteiro)) {
										$selected = "";
			                        	foreach ($roteiro['posto'] as $key => $value) {
			                        		$id_roteiro_posto = $roteiro['posto'][$key]['roteiro_posto'];
			                        		$sqlRoteiroPosto = "SELECT posto,cidade FROM tbl_roteiro_posto WHERE roteiro_posto = {$id_roteiro_posto};";
			                        		$resRoteiroPosto = pg_query($con,$sqlRoteiroPosto);
			                        		if (pg_num_rows($resRoteiroPosto) > 0) {
			                        			$postoRoteiro = pg_fetch_result($resRoteiroPosto, 0, posto);
			                        			$cidadeRoteiro = pg_fetch_result($resRoteiroPosto, 0, cidade);
			                        			if (!empty($postoRoteiro) ) {
			                        				//pesquiso pelo estado do posto
			                        				$sqlPostoFabrica = "SELECT contato_estado FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND posto = {$postoRoteiro}";
			                        				$resPostoFabrica = pg_query($con,$sqlPostoFabrica);
			                        				$estadoPosto = pg_fetch_result($resPostoFabrica, 0, contato_estado);
			                        				if ($item["estado"] == $estadoPosto) {
											
														$selected = "SELECTED";
													}
			                        			}
			                        			if (!empty($cidadeRoteiro)) {
			                        				//pesquiso pelo estado da cidade
			                        				$sqlCidade = "SELECT estado FROM tbl_cidade WHERE cidade = {$cidadeRoteiro}";
			                        				$resCidade = pg_query($con,$sqlCidade);
			                        				$estadoCidade = pg_fetch_result($resCidade, 0, estado);
			                        				
			                        				if ($item["estado"] == $estadoCidade) {
											
														$selected = "SELECTED";												
													}
			                        			}
			                        		}
			                        	}
			                        }
		                        ?>
									<option value="<?php echo $item['estado']; ?>" <?php echo $selected; ?>>
										<?php echo $item["nome"]; ?>
									</option>

								<?php
									
								}
								?>

								</select>
							</div>
						</div>
					</div>
				</div>
				<div class='span5'>
					<div class='control-group'>
						<label class='control-label' for='cidade'>Cidade</label>
						<input type='hidden' id='array_cidade' value='<?php echo json_encode($_POST["cidade"]); ?>'>
						<input type='hidden' id='array_posto_cidade' value='<?php echo json_encode($roteiro["posto"]); ?>'>
						<div class='controls controls-row'>
	 						<div class='span12 input-append box-cidade'>	 							
								<select id="cidade" name="cidade" class='span12' >
								<?
									
		                        ?>	 
		                        <?php
									foreach ($roteiro["posto"] as $key => $valorkey) {
										
										$keyRoteiroPosto = $valorkey['roteiro_posto'];
										$sqlEstado = "SELECT cidade FROM tbl_roteiro_posto WHERE roteiro_posto = {$keyRoteiroPosto};";
										$resEstado = pg_query($con,$sqlEstado);
										$value = pg_fetch_result($resEstado, 0, cidade);
										if (count($value) > 0) {
											
											$sqlUF = "SELECT cidade,nome,estado FROM tbl_cidade WHERE cidade = {$value};";
											$resUF = pg_query($con,$sqlUF);
											$UFCidade = pg_fetch_result($resUF, 0, estado);
											$NomeCidade = pg_fetch_result($resUF, 0, nome);
											$KeyCidade = pg_fetch_result($resUF, 0, cidade);
											?>
											<option value="<?php echo $KeyCidade; ?> SELECTED" >
												<?php echo $UFCidade." ".$NomeCidade; ?>
											</option>
											<?php
										}
									}
								?>
								</select>
							</div>
						</div>
					</div>
				</div>
				<div class='span1'></div>
			</div>
		</div>		
		<br>
		<div class='row-fluid'>
			<div class='span1'></div>
			<div class='span3'>
				<div class='control-group <?php echo (in_array("status_roteiro", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='tipo_roteiro'>Status Roteiro</label>
					<div class='controls controls-row'>
						<h5 class='asteristico'>*</h5>
						<select name="status_roteiro" id="status_roteiro" class='span12' >
							<option value="">Selecione Status do Roteiro</option>
							<?php $statusRoteiro = getStatusRoteiro();
							foreach($statusRoteiro as $item){ 
								$selected = ($item["status_roteiro"]==$roteiro["status_roteiro"]) ? "SELECTED" : "";
								?>
								<option <?php echo $selected; ?> value="<?php echo $item['status_roteiro']; ?>">
									<?php echo $item["descricao"]; ?>
								</option>

								<?}?>
						</select>
					</div>
				</div>
			</div>
			<div class="span1"></div>			
			<div class="span6">
				<div class="control-gru">
					<div class='control-group <?php echo (in_array("solicitante", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='solicitante'>Solicitante</label>
						<div class='controls controls-row'>
							<div class='span11 input-append'>
								<h5 class='asteristico'>*</h5>
								<input type="text" id="solicitante" name="solicitante" maxlength="20" class='span12' value="<?php echo $roteiro['solicitante'] ?>" >
							</div>
						</div>
					</div>					
				</div>				
			</div>
			<div class='span1'></div>
		</div>
		<div class="row-fluid">
			<div class="span1"></div>
			<div class='span3'>
				<div class='control-group'>
					<label class='control-label' for='ativo'>Ativo</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<select name="ativo" id="ativo" class='span12' >
								<option value=""></option>
								<option value="t" <?php echo $selected = ($roteiro["ativo"] == 't') ? "SELECTED" : ""; ?> >SIM</option>
								<option value="f" <?php echo $selected = ($roteiro["ativo"] == 'f') ? "SELECTED" : ""; ?> >NÃO</option>								
							</select>
						</div>
					</div>
				</div>
			</div>

			
			<div class="span7"></div>			
			<div class="span1"></div>
		</div>
		<div class="row-fluid">

			<div class="span1"></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='codigo_posto'>Código Posto</label>
					<div class='controls controls-row'>
						<div class='span10 input-append'>
							<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<?php echo $codigo_posto ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" cidade="" estado=""/>
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='descricao_posto'>Nome Posto</label>
					<div class='controls controls-row'>
						<div class='span11 input-append'>
							<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<?php echo $codigo_posto ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" cidade="" estado=""/>
						</div>
					</div>
				</div>
			</div>
			<div class='span2'>
				<div class='control-group'>
					<label class='control-label'></label>			
					<div class='controls controls-row'>
						<div>
							<input type="button" id="add_posto" class='btn' value="Adicionar" />	
						</div>
					</div>
				</div>
			</div>
			<div class="span1"></div>
		</div>
		<div class='container'>
			<div class='row-fluid'>
			
				<div class='span1'></div>
				<div class='span8'>
					<div class='control-group <?php echo (in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='posto_referencia'></label>
						<div class='controls controls-row'>
							<select name="postos" id="postos" multiple class="span12">
							<?
								
								if (count($_POST['posto']) > 0){
									$ar_posto = $_POST['posto'];
									foreach ($ar_posto as $key => $value) {
										$sqlProds = "SELECT tbl_posto.posto, 
															tbl_posto.cnpj, 
															tbl_posto.nome, 
															tbl_posto_fabrica.codigo_posto
														FROM tbl_posto 
															JOIN tbl_posto_fabrica USING (posto) 
														WHERE
															UPPER(tbl_posto_fabrica.codigo_posto) ILIKE UPPER('{$value}')
														AND tbl_posto_fabrica.fabrica = {$login_fabrica}
														ORDER BY tbl_posto.nome ";
										$qryProds = pg_query($con, $sqlProds);
										if (pg_num_rows($qryProds)> 0) {
											$desc_p = pg_fetch_result($qryProds, 0, nome);
											$cod_p = pg_fetch_result($qryProds, 0, codigo_posto);
											echo '<option value="' , $desc_p , '" class="' , $cod_p , '">' , $cod_p , ' - ', $desc_p , '</option>';
										}
									}
									foreach ($ar_posto as $key => $value) {
		      							echo '<input type="hidden" name="posto[]" id="' , $value , '" value="' , $value , '" >';
		      						}									
								}
		      					if (array_key_exists('posto', $roteiro)) {
		      						foreach ($roteiro['posto'] as $key => $value) {
		      							if (!empty($roteiro['posto'][$key]['codigo_posto'])){
		      								echo '<option value="' , $roteiro['posto'][$key]['descricao_posto'] , '" class="' , $roteiro['posto'][$key]['codigo_posto'] , '">' , $roteiro['posto'][$key]['codigo_posto'] , ' - ', $roteiro['posto'][$key]['descricao_posto'] , '</option>';
		      								//echo '<input type="hidden" name="posto[]" id="' , $roteiro['posto'][$key]['codigo_posto'] , '" value="' , $roteiro['posto'][$key]['codigo_posto'] , '" >';
		      							}
		      						}
		      						foreach ($roteiro['posto'] as $key => $value) {
		      							if (!empty($roteiro['posto'][$key]['codigo_posto'])){
		      								//echo '<option value="' , $roteiro['posto'][$key]['descricao_posto'] , '" class="' , $roteiro['posto'][$key]['codigo_posto'] , '">' , $roteiro['posto'][$key]['codigo_posto'] , ' - ', $roteiro['posto'][$key]['descricao_posto'] , '</option>';
		      								echo '<input type="hidden" name="posto[]" id="' , $roteiro['posto'][$key]['codigo_posto'] , '" value="' , $roteiro['posto'][$key]['codigo_posto'] , '" >';
		      							}
		      						}
		      					}
	                        ?>	                    	
		                    </select>                    
						</div>
					</div>
				</div>
				<div class='span2'>
					<div class='control-group'>
						<label class='control-label'></label>			
						<div class='controls controls-row'>
							<div>
								<input type="button" id="rm_posto" class="btn" value="Remover" />
							</div>
						</div>
					</div>
				</div>
				<div class='span1'></div>
			</div>
		</div>
		<br>
		<div class="row-fluid">

			<div class="span1"></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='cpf_tecnico'>CPF</label>
					<div class='controls controls-row'>
						<div class='span10 input-append'>
							<input type="text" name="cpf_tecnico" id="cpf_tecnico" tipotecnico="TF" rel="cpf" class='span12' value="" >
							<span class='add-on' rel="lupa_tecnico"><i class='icon-search' ></i></span> 
							<input type="hidden" name="lupa_config" tipotecnico="TF" tipo="tecnico" parametro="cpf"/>
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='nome_tecnico'>Responsável pelo Roteiro</label>
					<div class='controls controls-row'>
						<div class='span11 input-append'>
							<input type="text" name="nome_tecnico" id="nome_tecnico" tipotecnico="TF" rel="desc" class='span12' value="" >
							<span class='add-on' rel="lupa_tecnico"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipotecnico="TF" tipo="tecnico" parametro="nome"/>
						</div>
					</div>
				</div>
			</div>
			<div class='span2'>
				<div class='control-group'>
					<label class='control-label'></label>			
					<div class='controls controls-row'>
						<div>
							<input type="button" id="add_tecnico" class='btn' value="Adicionar" />	
						</div>
					</div>
				</div>
			</div>
			<div class="span1"></div>
		</div>
		<div class='container'>
			<div class='row-fluid'>
			
				<div class='span1'></div>
				<div class='span8'>
					<div class='control-group'>
						<label class='control-label' for='tecnico_referencia'></label>
						<div class='controls controls-row'>
							<select name="tecnicos" id="tecnicos" multiple class="span12">
							<?
								if (count($_POST['tecnico']) > 0){
									$ar_tecnico = $_POST['tecnico'];
									print_r($ar_tecnico);
									foreach ($ar_tecnico as $key => $value) {
										$sqlProds = "SELECT cpf, nome
														FROM tbl_tecnico
													WHERE tbl_tecnico.fabrica = {$login_fabrica}
														AND tbl_tecnico.cpf ILIKE '{$value}'
														AND tbl_tecnico.tipo_tecnico = 'TF'	
													ORDER BY tbl_tecnico.nome";
										
										$qryProds = pg_query($con, $sqlProds);
										//echo $sqlProds;
										if (pg_num_rows($qryProds)> 0) {
											//echo "aki2";
											$desc_p = pg_fetch_result($qryProds, 0, nome);
											$cod_p = pg_fetch_result($qryProds, 0, cpf);
											echo '<option value="' , $desc_p , '" class="' , $cod_p , '">' , $cod_p , ' - ', $desc_p , '</option>';
										}
									}
									foreach ($ar_tecnico as $key => $value) {
		      							echo '<input type="hidden" name="posto[]" id="' , $value , '" value="' , $value , '" >';
		      						}									
								}
		                        if (array_key_exists('tecnico', $roteiro)) {
		                        	foreach ($roteiro['tecnico'] as $keyTec => $valueTec) {
		                        		echo '<option value="'.$roteiro['tecnico'][$keyTec]['nome'].'" class="'.$roteiro['tecnico'][$keyTec]['cpf'].'">'.$roteiro['tecnico'][$keyTec]['cpf'].' - '.$roteiro['tecnico'][$keyTec]['nome'].'</option>';
		                        		//echo '<input type="hidden" name="tecnico[]" id="'.$roteiro['tecnico'][$keyTec]['cpf'].'" value="'.$roteiro['tecnico'][$keyTec]['cpf'].'" >';
		                        	}
		                        	foreach ($roteiro['tecnico'] as $keyTec => $valueTec) {
		                        		//echo '<option value="'.$roteiro['tecnico'][$keyTec]['nome'].'" class="'.$roteiro['tecnico'][$keyTec]['cpf'].'">'.$roteiro['tecnico'][$keyTec]['cpf'].' - '.$roteiro['tecnico'][$keyTec]['nome'].'</option>';
		                        		echo '<input type="hidden" name="tecnico[]" id="'.$roteiro['tecnico'][$keyTec]['cpf'].'" value="'.$roteiro['tecnico'][$keyTec]['cpf'].'" >';
		                        	}
		                        }
	                        ?>
		                    </select>
						</div>
					</div>
				</div>
				<div class='span2'>
					<div class='control-group'>
						<label class='control-label'></label>			
						<div class='controls controls-row'>
							<div>
								<input type="button" id="rm_tecnico" class="btn" value="Remover" />
							</div>
						</div>
					</div>
				</div>
				<div class='span1'></div>
			</div>
		</div>
		<br>

		<div class='row-fluid'>
			<div class='span1'></div>
			<div class='span3'>
				<div class='control-group <?php echo (in_array("qtde_dias", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_inicial'>Qtde Dias</label>
					<div class='controls controls-row'>
						<div class='span5'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="qtde_dias" id="qtde_dias" size="12" maxlength="10" class='span12'  readonly="true" value= "<?php echo $roteiro['qtde_dias']?>">
						</div>
					</div>
				</div>
			</div>
			<div class="span7">
				<div class='control-group '>
					<label class='control-label' for='descricao_posto'>Comentários Internos</label>
					<div class='controls controls-row'>
						<textarea name="excecoes" class="span11" > <?php echo $roteiro["excecoes"] ?> </textarea> 
					</div>
				</div>
			</div>

			<div class='span1'></div>
		</div>
		<br/>
		<p><br/>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'), 'save');">Salvar</button>
			<button class='btn btn-warning' id="btn_acao" type="button"  onclick="window.location = '<?php echo $_SERVER['PHP_SELF'];?>'">Limpar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br/>
	</form>
</div>
<div class="container">
    <div class="row-fluid">
        <div class="span12">
            <div class="control-group">
                <div class="controls controls-row  tac">
                    <button type='button' class="btn" onclick="window.location = '<? echo $PHP_SELF; ?>?action=list'">Listar Todos os Roteiros</button>
                </div>
            </div>
        </div>
    </div>
</div><br/>

<?php
if (isset($_GET["action"]) && $_GET["action"] == 'list') {
    $roteiros = getRoteiroList();         ?>
        <table id="roteiros-list" class='table table-striped table-bordered table-hover table-fixed' >
            <thead>
                <tr class='titulo_coluna' >
                	<th>Roteiro</th>
                	<th>Tipo Roteiro</th>
                    <th>Data Início</th>
                    <th>Data Término</th>
                    <th>Estado</th>
                    <th>Cidade</th>
                    <th>Ativo</th>
                    <th>Status</th>
                    <th>Solicitante</th>
                    <th>Qtde Dias</th>
                </tr>
            </thead>
    
            <tbody>
                <?php
                foreach ($roteiros as $item) { 
                	$sql_t = "SELECT posto,cidade FROM tbl_roteiro_posto WHERE roteiro = {$item['roteiro']}";
                	$res_t = pg_query($con,$sql_t);
                	$estadoTabela = array();
                	$cidadeTabela = array();
                	for ($t=0; $t < pg_num_rows($res_t) ; $t++) { 
                		$posto_t = pg_fetch_result($res_t, $t, posto);
                		$cidade_t = pg_fetch_result($res_t, $t, cidade);
                		
                		if (!empty($posto_t)) {
                			$sql_tp = "SELECT contato_estado,contato_cidade FROM tbl_posto_fabrica WHERE posto = {$posto_t} AND fabrica = {$login_fabrica}; ";
                			$res_tp = pg_query($con,$sql_tp);
                			$estadoPostoTabela = pg_fetch_result($res_tp, 0, contato_estado);
                			$cidadePostoTabela = pg_fetch_result($res_tp, 0, contato_cidade);
                			if(!in_array($cidadePostoTabela, $cidadeTabela)){
                				$cidadeTabela[]=$cidadePostoTabela;
                			}
                			if (!in_array($estadoPostoTabela, $estadoTabela)) {
                				$estadoTabela[]=$estadoPostoTabela;
                			}
                		}
                		if (!empty($cidade_t)) {
                			$sql_tp = "SELECT estado,nome FROM tbl_cidade WHERE cidade = {$cidade_t};";
                			$res_tp = pg_query($con,$sql_tp);
                			$estadoCidadeTabela = pg_fetch_result($res_tp, 0, estado);
                			$cidadeCidadeTabela = pg_fetch_result($res_tp, 0, nome);
                			if (!in_array($cidadeCidadeTabela, $cidadeTabela)) {
                				$cidadeTabela[]=$cidadeCidadeTabela;
                			}
                			if (!in_array($estadoCidadeTabela, $estadoTabela)) {
                				$estadoTabela[]=$estadoCidadeTabela;
                			}               			
                		}
                	}
                	
                	$estadoTabela = implode(" / ", $estadoTabela);
                	$cidadeTabela = implode(" / ", $cidadeTabela);
                	if ($item['ativo'] == 't') {
                		$ativoTabela = 'Ativo';
                	}else{
                		$ativoTabela = 'Inativo';
                	}
                	?>
    
                    <tr>
                        <td style="text-align:'center';"><a href="<?php echo $_SERVER['PHP_SELF'] . '?roteiro=' . $item['roteiro'] ?>"><?php echo $item['roteiro'] ?><a></td>
                        <?php
                        $roteiroTipo = $item['tipo_roteiro'] == "RA" ? "Roteiro Administrativo" : "Roteiro Técnico";
                        
                        ?>
                        <td style="text-align:'center';"><?php echo $roteiroTipo ?></td>
                        <td style="text-align:'center';"><?php echo $item['data_inicio'] ?></td>
                        <td style="text-align:'center';"><?php echo $item['data_termino'] ?></td>
                        <td style="text-align:'center';"><?php echo $estadoTabela ?></td>
                        <td style="text-align:'center';"><?php echo $cidadeTabela ?></td>
                        <td style="text-align:'center';"><?php echo $ativoTabela ?></td>
                        <td style="text-align:'center';"><?php echo $item['status_descricao'] ?></td>
                        <td style="text-align:'center';"><?php echo $item['solicitante'] ?></td>
                        <td style="text-align:'center';"><?php echo $item['qtde_dias'] ?></td>
                    </tr>         
             <? } ?>
    
                            </tbody>
                        </table>
    
                        <?php
                    }?>
                    <script type="text/javascript">
                    	$.dataTableLoad({
							table : "#roteiros-list",
							type: "full",
							"aaSorting": []
                    	});
                    </script>

<?
function deletar_tabelas($tblRoteiro,$idRoteiro){
	global $con;
	$sql = "DELETE FROM {$tblRoteiro} WHERE roteiro = {$idRoteiro} ";
	$res = pg_query($con,$sql);
}
function getEstados(){
	global $con;
	$sql = "SELECT DISTINCT tbl_estado.estado, nome 
	FROM tbl_ibge 
	INNER JOIN tbl_estado ON tbl_estado.estado = tbl_ibge.estado
	ORDER BY nome ASC";
	$res = pg_query($con, $sql);
	return pg_fetch_all($res);
}
function getStatusRoteiro(){
	global $con;
	$sql = "SELECT status_roteiro, descricao
	FROM tbl_status_roteiro 
	ORDER BY status_roteiro ASC";
	$res = pg_query($con, $sql);
	return pg_fetch_all($res);
}
function getCidades($estado){
	global $con;
	$estado_in = implode("','", $estado);
	$estado_in ="'".$estado_in."'";
	$sql = "SELECT cod_ibge, estado||' - '||cidade as cidade 
	FROM tbl_ibge 
	WHERE estado in ( $estado_in ) AND 
	cidade IS NOT NULL
	ORDER BY cidade ASC";
	$res = pg_query($con, $sql);
	return pg_fetch_all($res);
}
function validateFields(){
	$msg_erro=null;	
	if(empty($_POST["data_inicio"])){
		$msg_erro["campos"][] = "data";
		$msg_erro["msg"]["obg"] =  "Preencha os campos obrigatórios";
	}
	if(empty($_POST["data_termino"])){
		$msg_erro["campos"][] = "data";
		$msg_erro["msg"]["obg"] =  "Preencha os campos obrigatórios";
	}
	if (empty($_POST["tipo_roteiro"])) {
		$msg_erro["campos"][] = "tipo_roteiro";
		$msg_erro["msg"]["obg"] =  "Preencha os campos obrigatórios";
	}
	if (empty($_POST["solicitante"])) {
		$msg_erro["campos"][] = "solicitante";
		$msg_erro["msg"]["obg"] =  "Preencha os campos obrigatórios";
	}		
	if (empty($_POST["status_roteiro"])) {
		$msg_erro["campos"][] = "status_roteiro";
		$msg_erro["msg"]["obg"] =  "Preencha os campos obrigatórios";
	}
	if (empty($_POST["qtde_dias"])) {
		$msg_erro["campos"][] = "qtde_dias";
		$msg_erro["msg"]["obg"] =  "Preencha os campos obrigatórios";
	}
	
	if (empty($_POST["posto"])) {
		$msg_erro["campos"][] = "posto";
		$msg_erro["msg"]["obg"] =  "Preencha os campos obrigatórios";
	} 
	if (empty($_POST["tecnico"])) {
		$msg_erro["campos"][] = "tecnico";
		$msg_erro["msg"]["obg"] =  "Preencha os campos obrigatórios";
	}
	$dataInicio  = DateTime::createFromFormat("d/m/Y", $_POST["data_inicio"]);
	$dataTermino = DateTime::createFromFormat("d/m/Y", $_POST["data_termino"]);
	if($dataInicio > $dataTermino){
		$msg_erro["campos"][] = "data";
		$msg_erro["msg"][] =  "Data Inicial deve ser menor que Data Final";	
	}
	return $msg_erro;
}
function getRoteiroList(){
	global $con;
	global $login_fabrica;
	$sql = "SELECT 	tbl_roteiro.roteiro,
					tbl_roteiro.tipo_roteiro,
					tbl_roteiro.ativo,
					data_inicio,
					data_termino,
					tbl_status_roteiro.status_roteiro,
					tbl_status_roteiro.descricao as status_descricao,
					solicitante,
					qtde_dias,
					excecoes,
					tbl_roteiro_posto.roteiro_posto,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome as descricao_posto,
					tbl_roteiro_tecnico.roteiro_tecnico,
					tbl_tecnico.cpf,
					tbl_tecnico.nome
			FROM tbl_roteiro
			INNER JOIN tbl_roteiro_posto ON tbl_roteiro_posto.roteiro = tbl_roteiro.roteiro
			INNER JOIN tbl_roteiro_tecnico ON tbl_roteiro_tecnico.roteiro = tbl_roteiro.roteiro
			INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_roteiro_posto.posto AND
											tbl_posto_fabrica.fabrica = tbl_roteiro.fabrica
			INNER JOIN tbl_status_roteiro ON tbl_status_roteiro.status_roteiro = tbl_roteiro.status_roteiro
			INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
			INNER JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_roteiro_tecnico.tecnico AND
									  tbl_tecnico.fabrica = tbl_roteiro.fabrica
			  WHERE tbl_roteiro.fabrica = $1
				ORDER BY data_inicio								 ";
	$res = pg_query_params($con, $sql, array($login_fabrica));
	return groupResults(pg_fetch_all($res));
}
function getRoteiro($roteiro){
	global $con;
	global $login_fabrica;
	$sql = "SELECT 	tbl_roteiro.roteiro,
					tbl_roteiro.tipo_roteiro,
					tbl_roteiro.ativo,
					data_inicio,
					data_termino,
					tbl_status_roteiro.status_roteiro,
					tbl_status_roteiro.descricao as status_descricao,
					solicitante,
					qtde_dias,
					tbl_roteiro_posto.cidade,
					excecoes,
					tbl_roteiro_posto.roteiro_posto,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome as descricao_posto,
					tbl_roteiro_tecnico.roteiro_tecnico,
					tbl_tecnico.cpf,
					tbl_tecnico.nome
			FROM tbl_roteiro
			LEFT JOIN tbl_roteiro_posto ON tbl_roteiro_posto.roteiro = tbl_roteiro.roteiro
			LEFT JOIN tbl_roteiro_tecnico ON tbl_roteiro_tecnico.roteiro = tbl_roteiro.roteiro
			LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_roteiro_posto.posto AND
											tbl_posto_fabrica.fabrica = tbl_roteiro.fabrica
			LEFT JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
			LEFT JOIN tbl_status_roteiro ON tbl_status_roteiro.status_roteiro = tbl_roteiro.status_roteiro
			LEFT JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_roteiro_tecnico.tecnico AND
									  tbl_tecnico.fabrica = tbl_roteiro.fabrica
			WHERE tbl_roteiro.roteiro = $1 ";
	$res = pg_query_params($con, $sql, array($roteiro));
	return groupResults(pg_fetch_all($res));
}
function groupResults($data){
	$roteiro = array();
	
	foreach($data as $item){
		$roteiro[$item["roteiro"]]["roteiro"] = $item["roteiro"];
		$roteiro[$item["roteiro"]]["tipo_roteiro"] = $item["tipo_roteiro"];
		$roteiro[$item["roteiro"]]["ativo"] = $item["ativo"];
		$roteiro[$item["roteiro"]]["tipo_roteiro"] = $item["tipo_roteiro"];
		$dataInicio = new DateTime($item["data_inicio"]);
		$roteiro[$item["roteiro"]]["data_inicio"] = $dataInicio->format("d/m/Y");
		$dataTermino = new DateTime($item["data_termino"]);
		$roteiro[$item["roteiro"]]["data_termino"] = $dataTermino->format("d/m/Y");
		$roteiro[$item["roteiro"]]["solicitante"] = $item["solicitante"];
		$roteiro[$item["roteiro"]]["qtde_dias"] = $item["qtde_dias"];
		$roteiro[$item["roteiro"]]["status_roteiro"] = $item["status_roteiro"];
		$roteiro[$item["roteiro"]]["status_descricao"] = $item["status_descricao"];
		
		$roteiro[$item["roteiro"]]["excecoes"] = $item["excecoes"];
		
		$roteiro[$item["roteiro"]]["tecnico"][$item["roteiro_tecnico"]] = groupTecnicos($item);
		$roteiro[$item["roteiro"]]["posto"][$item["roteiro_posto"]] = groupPostos($item);
	}
	return $roteiro;
}
function groupTecnicos($item){
	return array("roteiro_tecnico"=> $item["roteiro_tecnico"],
				 "cpf"=> $item["cpf"],
				 "nome"=> $item["nome"] );
}
function groupPostos($item){
return array("roteiro_posto"=> $item["roteiro_posto"],
				 "codigo_posto"=> $item["codigo_posto"],
				 "descricao_posto"=> $item["descricao_posto"],
				 "cidade"=> $item["cidade"], );	
}
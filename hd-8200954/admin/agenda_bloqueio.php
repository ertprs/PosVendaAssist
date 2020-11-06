<?include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST['ajax'] == 'sim' and $_POST['acao'] == 'consulta_cidades') {

	$estados = $_POST['estados'];
	
	if(array_key_exists('estados', $_POST)){

		$estados = $_POST['estados'];

		foreach ($estados as $key => $value) {
			$estados[$key] = "'".trim($value)."'";
		}
		$estados = implode(',',$estados);

		$sql = "SELECT cidade, nome FROM tbl_cidade WHERE UPPER(estado) IN (SP) ORDER BY nome ASC";
		$res = pg_exec($con,$sql);
		
		for ($i=0; $i < pg_num_rows($res); $i++) {
			$cidades[] = array("cidade" => utf8_encode(pg_result($res,$i,nome)),"codigo" => pg_result($res,$i,cidade));
		}

		echo json_encode($cidades);
		exit;
	}else{
		echo json_encode(array("messageError" => utf8_encode("Informe uma região ou estado")));
		exit;
	}
}

if($_POST['ajax'] == 'sim' && $_POST['acao'] == 'buscar_linha'){
	$codigo_posto    = $_POST["codigo_posto"];
	$descricao_posto = $_POST["descricao_posto"];

	$sql = "SELECT tbl_linha.linha, tbl_linha.nome FROM tbl_posto_fabrica
			INNER JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto_fabrica.posto
			INNER JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha 
				AND tbl_linha.fabrica = {$login_fabrica}
		WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
			AND UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}')";
	$res = pg_exec($con,$sql);
	
	if(pg_num_rows($res) == 0){
		echo json_encode(array(
			"success" => false,
			"message" => "Não foi encontrado nenhuma linha para o posto {$descricao_posto}" 
		));
		exit;
	} else {
		for ($i=0; $i < pg_num_rows($res); $i++) {
			$linhas[utf8_encode(pg_result($res,$i,linha))] = utf8_encode(pg_result($res,$i,nome));
		}

		echo json_encode(array(
			"success" => true,
			"linhas"  => $linhas
		));
		exit;
	}
}

if ($_POST['ajax'] == 'sim' and $_POST['acao'] == 'excluir_bloqueio') {
	$id_bloqueio = $_POST["id_bloqueio"];

	$sql_delete = "DELETE FROM tbl_tecnico_agenda_bloqueio WHERE tecnico_agenda_bloqueio = {$id_bloqueio} AND fabrica = {$login_fabrica}";
	$res_delete = pg_query($con, $sql_delete);

	if (strlen(pg_last_error()) > 0){
		echo json_encode(array("error" => "ok", "messageError" => utf8_encode("Erro ao excluir registro, entre em contato com o suporte da Telecontrol.")));
		exit;
	}else{
		echo json_encode(array("success" => "ok", "messageSuccess" => utf8_encode("Registro excluido com sucesso.")));
		exit;
	}
}

if ($_POST["btn_acao"] == "submit") {
	$data_inicial 			= $_POST['data_inicial'];
	$data_final   			= $_POST['data_final'];
	$descricao    			= $_POST['descricao'];
	$dados_postos 			= $_POST['dados_postos'];
	$estado 				= $_POST['estado'];
	$cidade 				= $_POST['cidade'];
	$estado_cidade_posto 	= $_POST['estado_cidade_posto'];

	$descricao   = pg_escape_string(utf8_encode($descricao));
	$msg_success = array();

	if (!strlen($data_inicial) or !strlen($data_final)) {
		$msg_erro["msg"][]    = "Preencha os campos data inicial e data final";
		$msg_erro["campos"][] = "data";
	} else {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
			$msg_erro["msg"][]    = "Data Inválida";
			$msg_erro["campos"][] = "data";
		} else {
			$aux_data_inicial = "{$yi}-{$mi}-{$di}";
			$aux_data_final   = "{$yf}-{$mf}-{$df}";

			if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
				$msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
				$msg_erro["campos"][] = "data";
			}
		}
	}

	if (empty($descricao)){
		$msg_erro["msg"][] = "Preencha o campo descrição do bloqueio";
		$msg_erro["campos"][] = "descricao";
	}
	
	if ($dados_postos != ""){
		$dados_postos  = json_decode("[{$dados_postos}]");
		$id_postos     = array();
		$linhas_postos = array();

		foreach ($dados_postos as $key => $value) {
			$sql = "SELECT tbl_posto_fabrica.posto,
						   tbl_posto.nome
					FROM tbl_posto
						JOIN tbl_posto_fabrica USING(posto)
					WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
						AND UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$value->codigo_posto}') ";
			$res = pg_query($con ,$sql);

			if (pg_num_rows($res) == 0){
				$msg_erro["msg"][] = "Posto autorizado cod. $value->codigo_posto não encontrado";
			}else{
				$posto 		= pg_fetch_result($res, 0, 'posto');
				$posto_nome = pg_fetch_result($res, 0, 'nome');

				$id_postos[$posto]     = $posto_nome;
				$linhas_postos[$posto] = $value->linhas;
			}
		}
	}

	if (count($estado) > 0){
		$xestado = array();
		foreach ($estado as $key => $value) {
			$xestado[] = "'$value'";
		}
		$xestado =  implode(',', $xestado);
	}

	if (count($cidade) > 0){
		$xcidade = implode(',', $cidade);
	}

	if (!count($msg_erro["msg"])) {

		if (empty($estado_cidade_posto) OR $estado_cidade_posto == "geral"){/***INSERT POR DATA/DESCRIÇÃO***/
			$sql = "
				SELECT tbl_tecnico_agenda_bloqueio.tecnico_agenda_bloqueio
				FROM tbl_tecnico_agenda_bloqueio
				WHERE tbl_tecnico_agenda_bloqueio.fabrica = {$login_fabrica}
				AND tbl_tecnico_agenda_bloqueio.data_inicio = '{$aux_data_inicial}'
				AND tbl_tecnico_agenda_bloqueio.data_final = '{$aux_data_final}'
				AND tbl_tecnico_agenda_bloqueio.posto IS NULL 
				AND tbl_tecnico_agenda_bloqueio.cidade IS NULL
				AND tbl_tecnico_agenda_bloqueio.estado IS NULL ";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) == 0){
				$sql = "
					INSERT INTO 
						tbl_tecnico_agenda_bloqueio (
							fabrica,
							descricao,
							data_inicio,
							data_final,
							admin
						) VALUES(
							{$login_fabrica},
							'{$descricao}',
							'{$aux_data_inicial}',
							'{$aux_data_final}',
							{$login_admin}
						)";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0){
					$msg_erro["msg"][] = "Erro ao gravar registro";
				}else{
					$msg_success["msg"]  = "Registro gravado com sucesso";
				}
			}else{
				$msg_erro["msg"][] = "Registro já cadastrado no sistema";
			}
		} else if ($estado_cidade_posto == "estado"){/***INSERT POR ESTADO***/
			foreach ($estado as $key => $value) {
				if (!empty($value)){
					$id_estado = $value;
					
					$sql = "
						SELECT tbl_tecnico_agenda_bloqueio.tecnico_agenda_bloqueio
						FROM tbl_tecnico_agenda_bloqueio
						WHERE tbl_tecnico_agenda_bloqueio.fabrica = {$login_fabrica}
						AND tbl_tecnico_agenda_bloqueio.data_inicio = '{$aux_data_inicial}'
						AND tbl_tecnico_agenda_bloqueio.data_final = '{$aux_data_final}'
						AND tbl_tecnico_agenda_bloqueio.estado = '{$id_estado}'
						AND tbl_tecnico_agenda_bloqueio.posto IS NULL 
						AND tbl_tecnico_agenda_bloqueio.cidade IS NULL ";
					$res = pg_query($con, $sql);

					if (pg_num_rows($res) == 0){
						$sql = "
							INSERT INTO 
								tbl_tecnico_agenda_bloqueio(
									fabrica,
									descricao,
									data_inicio,
									data_final,
									estado,
									admin
								)VALUES (
									{$login_fabrica},
									'{$descricao}',
									'$aux_data_inicial',
									'$aux_data_final',
									'{$id_estado}',
									{$login_admin}
								)";
						$res = pg_query($con, $sql);

						if (strlen(pg_last_error()) > 0){
							$msg_erro["msg"][] = "Erro ao gravar registro para o estado: $id_estado";
						}else{
							$msg_success["msg"]  = "Registro gravado com sucesso";
						}
					}else{
						$msg_erro["msg"][] = "Registro já cadastrado no sistema para o Estado: $id_estado";
					}
				}
			}
		} else if ($estado_cidade_posto == "cidade"){/***INSERT POR CIDADE***/
			foreach ($cidade as $key => $value) {
				if (!empty($value)){
					$id_cidade = $value;

					$sql_cidade = "SELECT cidade, nome FROM tbl_cidade WHERE cidade = {$id_cidade} ";
					$res_cidade = pg_query($con,$sql_cidade);

					if (pg_num_rows($res_cidade) > 0){
						$cidade_nome = pg_fetch_result($res_cidade, 0, 'nome');
					}

					$sql = "
						SELECT 
							tbl_tecnico_agenda_bloqueio.tecnico_agenda_bloqueio
						FROM tbl_tecnico_agenda_bloqueio
						WHERE tbl_tecnico_agenda_bloqueio.fabrica = {$login_fabrica}
						AND tbl_tecnico_agenda_bloqueio.data_inicio = '{$aux_data_inicial}'
						AND tbl_tecnico_agenda_bloqueio.data_final = '{$aux_data_final}'
						AND tbl_tecnico_agenda_bloqueio.cidade = '{$id_cidade}'
						AND tbl_tecnico_agenda_bloqueio.posto IS NULL 
						AND tbl_tecnico_agenda_bloqueio.estado IS NULL ";
					$res = pg_query($con, $sql);
					
					if (pg_num_rows($res) == 0){
						$sql = "
							INSERT INTO 
								tbl_tecnico_agenda_bloqueio(
									fabrica,
									descricao,
									data_inicio,
									data_final,
									cidade,
									admin
								) VALUES (
									{$login_fabrica},
									'{$descricao}',
									'{$aux_data_inicial}',
									'{$data_final}',
									{$id_cidade},
									{$login_admin}
								)";
						$res = pg_query($con, $sql);

						if (strlen(pg_last_error()) > 0){
							$msg_erro["msg"][] = "Erro ao gravar registro para a cidade: $cidade_nome";
						}else{
							$msg_success["msg"]  = "Registro gravado com sucesso";
						}
					}else{
						$msg_erro["msg"][] = "Registro já cadastrado no sistema para a Cidade: $cidade_nome";
					}
				}
			}
		} else if ($estado_cidade_posto == "posto"){/***INSERT POR POSTO***/

			if (count($id_postos) == 0) {
				$msg_erro["msg"][] = "É necessário adicionar ao menos um posto para fazer o bloqueio";
			} else {
				foreach ($id_postos as $key => $value) {
					if (!empty($value)){
						$xid_posto   = $key;
						$xnome_posto = $value;

						$sql = "
							SELECT tbl_tecnico_agenda_bloqueio.tecnico_agenda_bloqueio
							FROM tbl_tecnico_agenda_bloqueio
							WHERE tbl_tecnico_agenda_bloqueio.fabrica   	= {$login_fabrica}
								AND tbl_tecnico_agenda_bloqueio.data_inicio = '{$aux_data_inicial}'
								AND tbl_tecnico_agenda_bloqueio.data_final  = '{$aux_data_final}'
								AND tbl_tecnico_agenda_bloqueio.posto       = {$xid_posto}
								AND tbl_tecnico_agenda_bloqueio.cidade IS NULL
								AND tbl_tecnico_agenda_bloqueio.estado IS NULL ";
						$res = pg_query($con, $sql);
					
						if (pg_num_rows($res) == 0){
							$linhas = $linhas_postos[$key] != "" ? '{ "linhas": "'.$linhas_postos[$key].'" }' : "{}";
							
							$sql = "INSERT INTO tbl_tecnico_agenda_bloqueio (
									fabrica,
									descricao,
									data_inicio,
									data_final,
									posto,
									admin,
									linha
								) VALUES (
									{$login_fabrica},
									'{$descricao}',
									'{$aux_data_inicial}',
									'{$aux_data_final}',
									{$xid_posto},
									{$login_admin},
									'{$linhas}'
								)";
							$res = pg_query($con, $sql);

							if (strlen(pg_last_error()) > 0){
								$msg_erro["msg"][] = "Erro ao gravar registro para o Posto: $xnome_posto";
							}else{
								$msg_success["msg"]  = "Registro gravado com sucesso";
							}
						}else{
							$msg_erro["msg"][] = "Registro já cadastrado no sistema para o Posto: $xnome_posto";
						}
					}
				}
			}
		}

		if(!count($msg_erro["msg"])){
			$data_inicial        = "";
			$data_final          = "";
			$dados_postos        = "";
			$descricao           = "";
			$estado_cidade_posto = "";
		}
	}
}

$sql_pesquisa = "SELECT 
					tbl_tecnico_agenda_bloqueio.tecnico_agenda_bloqueio,
					tbl_tecnico_agenda_bloqueio.admin,
					tbl_tecnico_agenda_bloqueio.descricao,
					TO_CHAR(tbl_tecnico_agenda_bloqueio.data_inicio, 'DD/MM/YYYY') AS data_inicio,
					TO_CHAR(tbl_tecnico_agenda_bloqueio.data_final, 'DD/MM/YYYY') AS data_final,
					tbl_tecnico_agenda_bloqueio.posto,
					tbl_tecnico_agenda_bloqueio.estado,
					tbl_tecnico_agenda_bloqueio.linha,
					tbl_tecnico_agenda_bloqueio.ativo,
					TO_CHAR(tbl_tecnico_agenda_bloqueio.data_input, 'DD/MM/YYYY') AS data_cadastro,
					tbl_tecnico_agenda_bloqueio.cidade,
					(
						SELECT nome FROM tbl_cidade WHERE cidade = tbl_tecnico_agenda_bloqueio.cidade
					) AS cidade_nome,
					(
						SELECT nome FROM tbl_posto_fabrica JOIN tbl_posto using(posto) 
						WHERE posto = tbl_tecnico_agenda_bloqueio.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
					) AS posto_nome,
					(
						SELECT nome_completo FROM tbl_admin WHERE fabrica = {$login_fabrica}
						AND tbl_admin.admin = tbl_tecnico_agenda_bloqueio.admin
					) AS nome_admin
				FROM tbl_tecnico_agenda_bloqueio
				WHERE tbl_tecnico_agenda_bloqueio.fabrica = {$login_fabrica} ";
$res_pesquisa = pg_query($con, $sql_pesquisa);

$layout_menu = "callcenter";
$title = "CADASTRO AGENDA BLOQUEIO";
include 'cabecalho_new.php';

$plugins = array(
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable",
	"select2"
);
include("plugin_loader.php");
?>

<script type="text/javascript">
	var hora = new Date();
	var engana = hora.getTime();

	$("select").select2();

	$(function() {
		$("#linha_posto").select2();
		
		$("#data_inicial").datepicker({dateFormat: "dd/mm/yy", minDate: 0 }).mask("99/99/9999");
        $("#data_final").datepicker({dateFormat: "dd/mm/yy", minDate: 0 }).mask("99/99/9999");

		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		$("#radio_estado").click(function(){
			if (confirm('Caso tenha dados selecionados nos campos Cidade ou Posto os mesmos serão perdidos. Deseja continuar ?')) {
	           	$("#row_estado_cidade").show();
				$("#div_estado").show();
				$("#div_cidade").hide();
				$("#row_posto").hide();

				$('#cidade').val(null).trigger("change");
				$("#integracao").find("tbody").remove();
				$("#integracao").hide();
	        }else{
	        	$(this).attr('checked',false);
	        	return ;
	        }
		});

		$("#radio_cidade").click(function(){
			if (confirm('Caso tenha dados selecionados no campo Posto os mesmos serão perdidos. Deseja continuar ?')) {
				$("#row_estado_cidade").show();
				$("#div_estado").show();
				$("#div_cidade").show();
				$("#row_posto").hide();

				$("#integracao").find("tbody").remove();
				$("#integracao").hide();
				//$('#cidade').val(null).trigger("change");
			}else{
				$(this).attr('checked',false);
	        	return ;
			}
		});

		$("#radio_posto").click(function(){
			if (confirm('Caso tenha dados selecionados nos campos Estado/Cidade os mesmos serão perdidos. Deseja continuar ?')) {
				$("#row_posto").show();
				$("#row_estado_cidade").hide();

				$('#cidade').val(null).trigger("change");
				$('#estado').val(null).trigger("change");
			}else{
				$(this).attr('checked',false);
	        	return ;
			}
		});

		$("#radio_geral").click(function(){
			if (confirm('Caso tenha dados selecionados nos campos Estado/Cidade e Posto os mesmos serão perdidos. Deseja continuar ?')) {
				$("#row_estado_cidade").hide();
				$("#row_posto").hide();

				$('#cidade').val(null).trigger("change");
				$('#estado').val(null).trigger("change");
			
				$("#integracao").find("tbody").remove();
				$("#integracao").hide();
			}else{
				$(this).attr('checked',false);
	        	return ;
			}
		});

		<?php if(strlen($xestado) > 0){ ?>
			$("#estado").val([<?=$xestado?>]).select2();
		<?php }else{ ?>
			$("#estado").select2();
		<?php } ?>

		<?php if(strlen($xcidade) > 0){ ?>
			$("#cidade").val([<?=$xcidade?>]).select2();
		<?php }else{ ?>
			$("#cidade").select2();
		<?php } ?>
	});

	$(document).on("change","#estado", function(){
  		lista_cidade();
  	});

  	$(document).on("click","#btn_excluir", function(){
  		loading_button($(this), true, "Excluindo...");
		var id_bloqueio = $(this).data('id_bloqueio');
		var tr          = $("#"+id_bloqueio);

  		if (confirm('Deseja realmente excluir esse registro ?')) {
  			$.ajax({
	            method: "POST",
	            url: "<?=$PHP_SELF?>",
	            data: {ajax: "sim", acao: "excluir_bloqueio", id_bloqueio: id_bloqueio},
	            timeout: 8000
	        }).fail(function(){
  				loading_button($(this), false, "Excluir");
				$("#erro").show().find('h4').html("Ocorreu um erro ao tentar exlcuir o Registro! Recarregue a pagina...");
	        }).done(function(data){
	            data = JSON.parse(data);
	            loading_button($(this), false, "Excluir");
	           	
	           	if (data != null && data.success == "ok"){
	           		alert(data.messageSuccess);
	           		tr.remove();
	           	}else{
	           		alert(data.messageError);
	           	}
	        });
  		}else{
  			loading_button($(this), false, "Excluir");
  			return;
  		}
  	});

	function lista_cidade(participante){
		var estado = $("#estado").val();
	  	$('#cidade').val(null).trigger("change");
	  	if ($.isArray(estado)){
	  		$.ajax({
	            method: "POST",
	            url: "<?=$PHP_SELF?>",
	            data: {ajax: "sim", acao: "consulta_cidades", estados: estado},
	            timeout: 8000
	        }).fail(function(){
				$("#erro").show().find('h4').html("Ocorreu um erro ao tentar listar as cidades, tempo esgotado! Recarregue a pagina...");
	        }).done(function(data){
	            data = JSON.parse(data);
	           	
	           	if (data != null && data.messageError == undefined) {
	                var option = "";
	                $.each(data,function(index,obj){
	                    option += "<option value='"+obj.codigo+"'>"+obj.cidade+"</option>";
	                });
                	$('#cidade').html(option);
	            }else{
					$("#erro").show().find('h4').html("Ocorreu um erro ao tentar listar as cidades! Recarregue a pagina...");
	            }
	        });
	    }else{
	        $('#cidade').html("");
	    }
  	}

  	var i = 0;
  	
  	function addPosto() {
  		var btn_adiciona = $(this);
  		loading_button(btn_adiciona, false, "Adicionando...");
		var error             = true;
		var codigo_adiciona   = $('#codigo_adiciona').val();
		var descricao_adicona = $("#descricao_adicona").val();
		limpar_mensagem();

		if (verificar_vazio(codigo_adiciona) || verificar_vazio(descricao_adicona)) {
			loading_button(btn_adiciona, true, "Adicionar");
			adicionar_mensagem("alert-warning", "Selecione um posto!");
			return false;
		}
		
		$("#integracao").find('tr').each(function(idx, element){
			if (element != undefined && $(element).data('codigo') != undefined){
				if ($(element).data('codigo') == codigo_adiciona){
					loading_button(btn_adiciona, true, "Adicionar");
					adicionar_mensagem("alert-warning", "Posto já adicionado na lista, selecione um novo posto!");
					error = false;
					return false;
				}
			}
		});

		if (error){
			$(".lista_linha").hide();
			var cor = (i % 2) ? "#F7F5F0" : "#F1F4FA";

			var html_input = '<tr id="' + i + '" data-codigo="'  + codigo_adiciona   + '"  bgcolor="' + cor + '">';
			html_input     += '<td><input type="hidden" value="' + codigo_adiciona   + '" name="xcodigo_posto[' + i + ']" class="cod_posto" />' + codigo_adiciona + '</td>';
			html_input     += '<td><input type="hidden" value="' + descricao_adicona + '" name="xnome_posto['   + i + ']" />' + descricao_adicona + '</td>';
			html_input     += '<td data-linha="' + get_value_linha() + '">' + gerar_linhas_selecionadas() + '</td>';
			html_input     += '<td class="tac"><button type="button" onclick="deletaposto(' + i + ')" class="btn">Remover</button></td>';
			html_input     += '</tr>';

			i++;
			$("#integracao").show();
	    		
	    	$("#tabela").css("display","block");
			$(html_input).appendTo("#integracao");

			loading_button(btn_adiciona, true, "Adicionar");
			$('#codigo_adiciona').val('');
			$("#descricao_adicona").val('');
			$("#codigo_posto").val('');
			$("#descricao_posto").val('');
		}
	}

	function deletaposto(posicao){
		var posto = $("#integracao > tbody").find('tr');
		var linha_tr = $("#"+posicao);
		linha_tr.remove();

		if (posto.length == 1){
			$("#integracao").hide();
		}
	}

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
		$("#codigo_adiciona").val(retorno.codigo);
		$("#descricao_adicona").val(retorno.nome);
		$(".lista_linha").hide();
		limpar_mensagem();
    }

    function get_value_linha(){
    	var linhas_selecionadas = $("#linha_posto :selected").get();
    	var linhas = "";

    	if(!verificar_vazio(linhas_selecionadas)){
	    	$.each(linhas_selecionadas, function(key, linha){
	    		linhas == "" ? linhas = linha.value : linhas += "," + linha.value;
			});
    	}

		return linhas;
    }

    function gerar_linhas_selecionadas(){
    	var linhas_selecionadas = $("#linha_posto :selected").get();

    	if(verificar_vazio(linhas_selecionadas)){
    		return "";
    	} else {
    		var ul_li = "<ul>";

    		$.each(linhas_selecionadas, function(key, linha){
    			ul_li += "<li>" + linha.text + "</li>";
    		});
    		
    		ul_li += "</ul>";
    		return ul_li;
    	}
    }

    function limpar_dados(){
    	loading_button($(this), true, "Limpando...");
    	window.location.href = "agenda_bloqueio.php";
    }

    function verificar_vazio(campo){
    	if (campo == "" || campo == " " || campo == null || campo.length == 0){
    		return true;
    	} else {
    		return false;
    	}
    }

    function limpar_mensagem(){
    	$(".msg_info").removeClass("alert-error alert-success alert-warning").html("").hide();
    }

    function adicionar_mensagem(tipo_class = "", text = ""){
    	limpar_mensagem();
    	$(".msg_info").addClass(tipo_class).html("<h4>" + text + "</h4>").show();
    }

    $(document).on("click",".btn_linha",function(){
    	loading_button($(this), true, "Carregando...");
		var codigo_posto    = $("#codigo_posto").val();
		var descricao_posto = $("#descricao_posto").val();

		if(verificar_vazio(codigo_posto) || verificar_vazio(descricao_posto)){
			loading_button($(this), false, "Mostrar Linha");
			adicionar_mensagem("alert-warning", "Posto não informado!");
		} else {
			$('#linha_posto').val(null).trigger("change").html("");

	    	if($(".lista_linha").is(":visible")){
	    		$(".lista_linha").hide();
	    	}else{
	    		$.ajax({
		            method: "POST",
		            url: "<?=$PHP_SELF?>",
		            data: {
		            	ajax: 			 "sim",
		            	acao: 			 "buscar_linha",
		            	codigo_posto: 	 codigo_posto,
		            	descricao_posto: descricao_posto
		            },
		            timeout: 8000
		        }).fail(function(data){
					adicionar_mensagem("alert-error", data.message);
					loading_button($(this), false, "Mostrar Linha");
		        }).done(function(data){
		            data = JSON.parse(data);
		            loading_button($(this), false, "Mostrar Linha");

		            $.each(data.linhas, function(key, value){
		            	$("#linha_posto").append($('<option>', {value: key, text: value} ));
		            });
		            $(".lista_linha").show();
		        });
	    	}
	    	loading_button($(this), false, "Mostrar Linha");
	    }
    });

    function loading_button(button, action, message){
    	$(button).attr("disabled", action).html(message);
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

if (count($msg_success["msg"]) > 0) {
?>
    <div class="alert alert-success">
		<h4><?=$msg_success["msg"]?></h4>
	</div>
<?php
}

function consulta_linha_posta($linhas, $login_fabrica, $con){
	$html_linhas = "";

	if($linhas != ""){
		$sql = "SELECT tbl_linha.linha, 
					   tbl_linha.nome 
			    FROM tbl_linha 
			    WHERE linha IN ({$linhas}) 
			    	AND fabrica = {$login_fabrica}";
    	$res_linha = pg_query($con, $sql);
    	
    	if(pg_num_rows($res_linha) > 0){
    		$html_linhas = "<ul>";

    		for($i = 0; $i < pg_num_rows($res_linha); $i++){
				$codigo_linha = pg_fetch_result($res_linha, $i, 'linha');
				$nome         = pg_fetch_result($res_linha, $i, 'nome');

				$html_linhas .= "<li>{$nome}</li>";
    		}
    		$html_linhas .= "</ul>";
    	}
	}
	return $html_linhas;
}

?>

<style type="text/css">
	.novo_asteristico {
		color:            #B94A48;           
		background-color: inherit;
		margin-bottom:    0;         
		margin-top:       0px;          
		margin-right:     3px;        
		float:            left;              
	}
</style>

<div class="msg_info alert" style="display: none;">
	<h4 class="msg_info_text"></h4>
</div>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
	<div class='titulo_tabela '>Parâmetros para Cadastro</div>
	<br/>
	
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<h5 class='novo_asteristico'>*</h5>
					<label class='control-label' for='data_inicial'>Data Inicial</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
						</div>
					</div>
				</div>
			</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<h5 class='novo_asteristico'>*</h5>
				<label class='control-label' for='data_final'>Data Final</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	
	<div class="row-fluid" style="min-height: 40px !important; padding-top: 10px;">
		<div class="span2"></div>
		<div class="span8">
			<div class="control-group <?=(in_array("estado_cidade_posto", $msg_erro["campos"])) ? "error" : ""?>">
				<label class='control-label' for='data_inicial'>Selecione</label>
				<div class='controls controls-row'>
					<div class="span2">
						<label class="radio">	
							<input type='radio' id="radio_estado" <?= ($estado_cidade_posto == "estado") ? "checked" : ""?> name='estado_cidade_posto' value='estado'/>Estado
						</label>
					</div>
					<div class="span2">
						<label class="radio">
							<input type='radio' id="radio_cidade" <?= ($estado_cidade_posto == "cidade") ? "checked" : ""?> name='estado_cidade_posto' value='cidade'/>Cidade
						</label>
					</div>
					<div class="span2">
						<label class="radio">
							<input type='radio' id="radio_posto" <?= ($estado_cidade_posto == "posto") ? "checked" : ""?> name='estado_cidade_posto' value='posto'/>Posto
						</label>
					</div>
					<div class="span2">
						<label class="radio">
							<input type='radio' id="radio_geral" <?= ($estado_cidade_posto == "geral") ? "checked" : ""?> name='estado_cidade_posto' value='geral'/>Geral
						</label>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Estado/Cidade -->
	<?php 
		if ($estado_cidade_posto == "estado" OR $estado_cidade_posto == "cidade"){
			$display_estado_cidade = "";
		}else{
			$display_estado_cidade = "style='display:none';";
		}
	?>
	<div class="row-fluid" id="row_estado_cidade" <?=$display_estado_cidade?> >
		<div class="span2"></div>
		<div class="span4" id="div_estado" <?=$display_estado_cidade?> >
			<div class='control-group <?=(in_array("estado", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='estado'>Estado</label>
				<div class='controls controls-row'>
					<div class="span4">
						<select id="estado" name="estado[]" multiple="multiple">
							<?php
							foreach ($array_estados() as $key => $value) {
								?><option value="<?=$key?>"><?=$value?></option><?php
							}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<?php 
			if ($estado_cidade_posto == "cidade"){
				$display_cidade = "";
			}else{
				$display_cidade = "style='display:none';";
			}
		?>
		<div class="span4" id="div_cidade" <?=$display_cidade?> >
			<div class='control-group <?=(in_array("cidade", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='cidade'>Cidade</label>
				<div class='controls controls-row'>
					<div class="span4">
						<select id="cidade" name="cidade[]" multiple="multiple">
						<?php
							if (count($estado) > 0){
								foreach ($estado as $key => $value) {
									$id_estados[$key] = "'".trim($value)."'";
								}
								$id_estados = implode(",", $id_estados);
								$sql = "SELECT cidade, nome FROM tbl_cidade WHERE UPPER(estado) IN ($id_estados) ORDER BY nome ASC";
								$res = pg_query($con,$sql);

								if (pg_num_rows($res) > 0){
									$dados_cidades = pg_fetch_all($res);
									foreach ($dados_cidades as $key => $value) {
									?>
										<option value="<?=$value['cidade']?>"><?=$value['nome']?></option>
									<?php
									}
								}
							}
						?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class="span2"></div>
	</div>
	<!-- Fim Estado/Cidade -->

	<!-- Pesquisa posto -->
	<?php 
		if ($estado_cidade_posto == "posto"){
			$display_posto = "";
		}else{
			$display_posto = "style='display:none;'";
		}
 	?>
	<div id="row_posto" <?=$display_posto?> >
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
						<div class='span11 input-append'>
							<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class="row-fluid">
			<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
				<div class='controls controls-row'>
					<div class='span2'></div>
					<div class='span4'>
						<input type="button" class="btn btn-success" value="Adicionar" onclick="addPosto()" />	
					</div>
					<div class='span4'>
						<input type="button" class="btn btn-primary btn_linha" value="Mostrar Linha" />	
					</div>
				</div>
			</div>

			<!-- <div class="span4">
				<p>
					<input type="button" class="btn" value="Adicionar" onclick="addPosto()" />
				</p>
			</div> -->
			<div class="span2"></div>
		</div>
		<div class="row-fluid lista_linha" style="display: none;">
			<div class='controls controls-row'>
				<div class='span2'></div>
				<div class='span8'>
					<div class='control-group <?=(in_array("linha_posto", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='lista_linha'>Linhas do Posto</label>
						<select name="linha_posto[]" id="linha_posto" multiple="multiple" style="width: 100% !important;"></select> 
					</div>
				</div>
				<div class="span2"></div>
			</div>
		</div>
		</br>
		<?php
		if(isset($_POST["codigo_adiciona"]) && isset($_POST["descricao_adicona"])){
			$codigo_adiciona   = $_POST["codigo_adiciona"];
			$descricao_adicona = $_POST["descricao_adicona"];
		}
		?>
		<input type="hidden" name="dados_postos" id="dados_postos" value='<?=$dados_postos?>'>
		<input type="hidden" id="codigo_adiciona" name="codigo_adiciona" value="<?=$codigo_adiciona?>">
		<input type="hidden" id="descricao_adicona" name="descricao_adicona" value="<?=$descricao_adicona?>">
		<?php 
			if ($dados_postos != ""){ 
				$display = "";
			}else{
				$display = "display: none;";
			}
		?>
		<table id="integracao" class="table table-bordered" style="width: 580px; <?=$display?>" >
			<thead>
				<tr class="titulo_coluna">
					<th>Código do Posto</th>
					<th>Nome do Posto</th>
					<th>Linha</th>
					<th>Ações</th>
				</tr>
			</thead>
			<tbody>
				<?php
					if (count($dados_postos) > 0){
						foreach ($dados_postos as $key => $value) {
							$sql = "
								SELECT 
									tbl_posto_fabrica.codigo_posto,
									tbl_posto.nome,
									tbl_posto.posto
								FROM tbl_posto
								JOIN tbl_posto_fabrica USING(posto)
								WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
								AND UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$value->codigo_posto}')";
							$res = pg_query($con ,$sql);
							
							if (pg_num_rows($res) > 0){
								$cor = ($key % 2) ? "#F7F5F0" : "#F1F4FA";
								$posto_codigo 	= pg_fetch_result($res, 0, 'codigo_posto');
								$posto_nome 	= pg_fetch_result($res, 0, 'nome');
								$posto_id 		= pg_fetch_result($res, 0, 'posto');
							?>
								<tr id="<?=$key?>" data-codigo='<?=$posto_codigo?>' bgcolor="<?=$cor?>">
									<td><input class="cod_posto" type="hidden" value="<?=$posto_codigo?>" name="xcodigo_posto[<?=$key?>]" /><?=$posto_codigo?></td>
									<td><input type="hidden" value="<?=$posto_nome?>" name="xnome_posto[<?=$i?>]" /><?=$posto_nome?></td>
									<td data-linha='{$value->linhas}'><?php echo consulta_linha_posta($value->linhas, $login_fabrica, $con); ?></td>
									<td class="tac"><button type="button" onclick="deletaposto('<?=$key?>')" class="btn">Remover</button></td>
								</tr>
							<?php
							}
						}
					}
				?>
			</tbody>
		</table>
	</div>
	<!-- Fim Pesquisa Posto -->
	<!-- Descrição do bloqueio -->
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class="span8">
			<div class='control-group <?=(in_array("descricao", $msg_erro["campos"])) ? "error" : ""?>'>
				<h5 class='novo_asteristico'>*</h5>
				<label class='control-label'>Descrição do bloqueio</label>
				<TEXTAREA NAME='descricao' ROWS='7' id="descricao" COLS='60' class='frm span12'><?if (strlen($descricao) > 0) echo utf8_decode($descricao); ?></TEXTAREA>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<!-- Fim Descrição do bloqueio -->
	<p><br/>
		<button class='btn' id="btn_acao" type="button" >Gravar</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	
		<button class="btn btn-primary btn-small" onclick="limpar_dados();" type="button">Limpar dados</button>
	</p><br/>
</form>
</div>
<?php  
	if (pg_num_rows($res_pesquisa) > 0){
?>
	<div class="container-fluid">
		<table id="resultado_os_atendimento" class='table table-striped table-bordered table-hover table-fixed' >
			<thead>
				<tr class='titulo_coluna' >
					<th>Data Inicial Bloqueio</th>
					<th>Data Final Bloqueio</th>
					<th>Nome Posto</th>
	                <th>Estado do Bloqueio</th>
	                <th>Cidade do Bloqueio</th>
	                <th>Linhas Bloqueadas</th>
					<th>Admin Cadastro</th>
					<th>Data Cadastro</th>
					<th>Descrição</th>
					<th>Ação</th>
				</tr>
			</thead>
			<tbody>
			<?php
				for ($i=0; $i < pg_num_rows($res_pesquisa); $i++) { 
					$tecnico_agenda_bloqueio = pg_fetch_result($res_pesquisa, $i, 'tecnico_agenda_bloqueio');
					$admin                   = pg_fetch_result($res_pesquisa, $i, 'admin');
					$descricao               = pg_fetch_result($res_pesquisa, $i, 'descricao');
					$data_inicio             = pg_fetch_result($res_pesquisa, $i, 'data_inicio');
					$data_final              = pg_fetch_result($res_pesquisa, $i, 'data_final');
					$posto                   = pg_fetch_result($res_pesquisa, $i, 'posto');
					$cidade_nome             = pg_fetch_result($res_pesquisa, $i, 'cidade_nome');
					$cidade                  = pg_fetch_result($res_pesquisa, $i, 'cidade');
					$posto_nome              = pg_fetch_result($res_pesquisa, $i, 'posto_nome');
					$estado                  = pg_fetch_result($res_pesquisa, $i, 'estado');
					$nome_admin              = pg_fetch_result($res_pesquisa, $i, 'nome_admin');
					$data_cadastro           = pg_fetch_result($res_pesquisa, $i, 'data_cadastro');
					$linha                   = pg_fetch_result($res_pesquisa, $i, 'linha');
					$linha                   = json_decode($linha);
			?>
					<tr id="<?=$tecnico_agenda_bloqueio?>">
						<td><?=$data_inicio?></td>
						<td><?=$data_final?></td>
						<td><?=$posto_nome?></td>
						<td><?=$estado?></td>			
						<td><?=$cidade_nome?></td>
						<td data-linha='{$value->linhas}'><?php echo consulta_linha_posta($linha->linhas, $login_fabrica, $con); ?></td>
						<td><?=$nome_admin?></td>
						<td><?=$data_cadastro?></td>
						<td><?= utf8_decode($descricao); ?></td>
						<td><button type="button" id="btn_excluir" data-id_bloqueio='<?=$tecnico_agenda_bloqueio?>' class="btn btn-danger btn-small">Excluir</button></td>
					</tr>
			<?php
				}
			?>
			</tbody>
		</table>
	</div>
	<script>
		$.dataTableLoad({ table: "#resultado_os_atendimento" });
	</script>
	<?php
	}else{
		echo "<div class='container'>
			<div class='alert'>
				    <h4>Nenhum resultado encontrado</h4>
			</div>
			</div>";
	}
?>
<script type="text/javascript">
	$(function(){
		var arr_posto = [];
		$("#btn_acao").click(function(){
    		loading_button($(this), true, "Gravando...");

			$("#integracao").find('tr').each(function(idx, element){
				if (element != undefined && $(element).data('codigo') != undefined){
					arr_posto.push('{"codigo_posto": "' + $(element).data('codigo') + '", "linhas": "' + $(element).find("td[data-linha]").data("linha") + '"}');
				}
			});
			$("#dados_postos").val(arr_posto);
			submitForm($(this).parents('form'));
		});
	});
</script>
<?php
include 'rodape.php';?>

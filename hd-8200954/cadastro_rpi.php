<?php

$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include __DIR__.'/dbconfig.php';
include __DIR__.'/includes/dbconnect-inc.php';

if ($areaAdmin === true) {
    $admin_privilegios = "call_center";
    include __DIR__.'/admin/autentica_admin.php';
} else {
    include __DIR__.'/autentica_usuario.php';
}

include_once 'helpdesk/mlg_funciones.php';
include __DIR__.'/funcoes.php';

include_once "classes/Posvenda/Fabricas/_{$login_fabrica}/Os.php";

$array_estados = $array_estados();
$array_estados = array_map(function($e) {
    return $e;
}, $array_estados);

if (isset($_POST["ajax_busca_cidade"]) && !empty($_POST["estado"])) {
    $estado = strtoupper($_POST["estado"]);

    if (array_key_exists($estado, $array_estados)) {
        $sql = "
    		SELECT
    			UPPER(fn_retira_especiais(nome)) AS cidade,
    			cidade AS cidade_id
    		FROM tbl_cidade
    		WHERE UPPER(estado) = UPPER('{$estado}')
            ORDER BY cidade ASC";
        $res = pg_query($con, $sql);
        if (pg_num_rows($res) > 0) {
            $array_cidades 		= array();
            $array_cidades_id 	= array();

            $i = 0;
            while ($result = pg_fetch_object($res)) {
                #$array_cidades[] 	= $result->cidade;
                $array_cidades[$i]['cidade']= $result->cidade;
	    		$array_cidades[$i]['cidade_id']= $result->cidade_id;
                $i++;
            }
            $retorno = array("cidades" => $array_cidades);
        } else {
            $retorno = array("error" => utf8_encode("nenhuma cidade encontrada para o estado: {$estado}"));
        }
    } else {
        $retorno = array("error" => utf8_encode("estado não encontrado"));
    }
    exit(json_encode($retorno));
}

if ($_REQUEST['gravar'] == "Gravar") {
	$responsavel 				= $_POST['responsavel'];
	$funcao  					= $_POST['funcao'];
	$data_partida  				= $_POST['data_partida'];
	$consumidor_nome  			= $_POST['consumidor_nome'];
	$consumidor_cpf_cnpj  		= $_POST['consumidor_cpf'];
	$consumidor_cep  			= $_POST['consumidor_cep'];
	$consumidor_estado  		= $_POST['consumidor_estado'];
	$consumidor_cidade  		= $_POST['consumidor_cidade'];
	$consumidor_bairro  		= $_POST['consumidor_bairro'];
	$consumidor_endereco  		= $_POST['consumidor_endereco'];
	$consumidor_numero  		= $_POST['consumidor_numero'];
	$consumidor_complemento 	= $_POST['consumidor_complemento'];
	$consumidor_celular  		= $_POST['consumidor_celular'];
	$consumidor_telefone  		= $_POST['consumidor_telefone'];
	$consumidor_contato  		= $_POST['consumidor_contato'];
	$informacoes  				= $_POST['informacoes'];
	$produto_produtos  			= $_POST['produto_produtos'];

	if (!strlen(trim($data_partida))){
		$msg_erro["campos"][] = "data_partida";
	}else{
		list($di, $mi, $yi) = explode("/", $data_partida);
		$data_partidax = new DateTime("$yi-$mi-$di");
		$data_atual = new DateTime(date('Y-m-d'));

		if (!checkdate($mi, $di, $yi) or $data_partidax > $data_atual) {
			$msg_erro["msg"][]    = "Data Inválida";
			$msg_erro["campos"][] = "data_partida";
		}else{
			$aux_data_partida = "{$yi}-{$mi}-{$di}";
		}
	}

	$sql_posto = "
		SELECT
			tbl_posto_fabrica.codigo_posto,
			tbl_posto.cidade,
			tbl_posto.nome
		FROM tbl_posto
		JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			AND tbl_posto_fabrica.fabrica = {$login_fabrica}
		WHERE tbl_posto.posto = {$login_posto} ";
	$res_posto = pg_query($con, $sql_posto);

	if (pg_num_rows($res_posto) > 0){
		$codigo_posto 	= pg_fetch_result($res_posto, 0, 'codigo_posto');
		$nome_posto 	= pg_fetch_result($res_posto, 0, 'nome');
		$cidade_posto 	= pg_fetch_result($res_posto, 0, 'cidade');
	}

	if (!strlen(trim($consumidor_cpf_cnpj))){
		$msg_erro["campos"][] = "consumidor_cpf";
	}else{
		$consumidor_cpf_cnpj = preg_replace("/\D/", "", $consumidor_cpf_cnpj);
        $sql = "SELECT fn_valida_cnpj_cpf('$consumidor_cpf_cnpj')";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
        	$msg_erro["msg"][]    = "CPF/CNPJ inválido";
			$msg_erro["campos"][] = "consumidor_cpf";
        }

		if (strlen($consumidor_cpf_cnpj) == 11 ){
			$xconsumidor_cpf_cnpj = "cpf";
		} else if (strlen($consumidor_cpf_cnpj) == 14){
			$xconsumidor_cpf_cnpj = "cnpj";
		}else {
			$consumidor_cpf_cnpj = "";
		}
	}

	if (!strlen(trim($consumidor_nome))){
		$msg_erro["campos"][] = "consumidor_nome";
	}

	if (!strlen(trim($responsavel))){
		$msg_erro["campos"][] = "responsavel";
	}

	$consumidor_cep 		= preg_replace("/\D/", "", $consumidor_cep);
	$consumidor_telefone 	= preg_replace("/\D/", "", $consumidor_telefone);
	$consumidor_celular 	= preg_replace("/\D/", "", $consumidor_celular);

	if (empty($consumidor_endereco)){
		$msg_erro["campos"][] = "consumidor_endereco";
	}

	if (empty($consumidor_bairro)){
		$msg_erro["campos"][] = "consumidor_bairro";
	}

	if (empty($consumidor_cep)){
		$msg_erro["campos"][] = "consumidor_cep";
	}

	if (empty($consumidor_cidade)){
		$msg_erro["campos"][] = "consumidor_cidade";
	}else{
		$sql = "
			SELECT nome, estado
			FROM tbl_cidade
			WHERE cidade = {$consumidor_cidade} ";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0){
			$cidade_nome = pg_fetch_result($res, 0, 'nome');
			$consumidor_estado = pg_fetch_result($res, 0, 'estado');
		}
	}

	if (empty($consumidor_estado)){
		$msg_erro["campos"][] = "consumidor_estado";
	}

	if (!strlen(trim($consumidor_telefone)) AND !strlen(trim($consumidor_celular))){
		$msg_erro["campos"][] = "consumidor_telefone";
		$msg_erro["campos"][] = "consumidor_celular";
	}

	if (count($msg_erro["campos"])){
		if (count($msg_erro["msg"])){
			foreach ($msg_erro["msg"] as $key => $value) {
				if ($value != 'Data Inválida' AND $value != 'CPF/CNPJ inválido'){
					$msg_erro["msg"][] = "Preencha os campos obrigatórios";
				}
			}
		}else{
			$msg_erro["msg"][] = "Preencha os campos obrigatórios";
		}
	}

	if (!count($msg_erro["msg"])) {
		$campos = "";
		$values = "";
		$campos_update = "";

		if (strlen(trim($funcao)) > 0){
			if (in_array($login_fabrica, [169,170]) && !empty($rpi)) {
				$campos_update .= ", responsavel_funcao = ' $funcao'"; 
			} else {
				$campos .= ", responsavel_funcao";
				$values .= ",' $funcao'";
			}
		}
		if (strlen(trim($consumidor_numero)) > 0){
			if (in_array($login_fabrica, [169,170]) && !empty($rpi)) {
				$campos_update .= ", consumidor_numero = '$consumidor_numero'"; 
			} else {
				$campos .= ",consumidor_numero";
				$values .= ",'$consumidor_numero'";
			}
		}
		if (strlen(trim($consumidor_complemento)) > 0){
			if (in_array($login_fabrica, [169,170]) && !empty($rpi)) {
				$campos_update .= ", consumidor_complemento = '$consumidor_complemento'"; 
			} else {
				$campos .= ",consumidor_complemento";
				$values .= ",'$consumidor_complemento'";
			}
		}
		if (strlen(trim($consumidor_celular)) > 0 AND strlen(trim($consumidor_telefone)) == 0){
			$xconsumidor_telefone = $consumidor_celular;
		}else {
			$xconsumidor_telefone = $consumidor_telefone;
		}

		if (strlen(trim($consumidor_contato)) > 0){
			if (in_array($login_fabrica, [169,170]) && !empty($rpi)) {
				$campos_update .= ", consumidor_contato = E'".addslashes($consumidor_contato)."'"; 
			} else {
				$campos .= ",consumidor_contato";
				$values .= ",E'".addslashes($consumidor_contato)."'";
			}
		}
		if (strlen(trim($informacoes)) > 0){
			if (in_array($login_fabrica, [169,170]) && !empty($rpi)) {
				$campos_update .= ", obs = E'".addslashes($informacoes)."'"; 
			} else {
				$campos .= ",obs";
				$values .= ",E'".addslashes($informacoes)."'";
			}
		}

		$className = '\\Posvenda\\Fabricas\\_' . $login_fabrica . '\\Os';
        $classOs = new $className($login_fabrica, null, $con);

		foreach ($produto_produtos as $key => $value) {
			$array_dados = array();
			$array_dados_valida = array();
			$error = "";

			if (strlen(trim($value['id'])) > 0){
				$produto = $value['id'];

				if (strlen(trim($value['id'])) > 0 AND $value['id'] == $produto_anterior){
					$msg_erro["msg"][]    = "Produto já lançado no RPI";
					$msg_erro["campos"][] = "produto_$key";
					$error =  "true";
				}

				if (strlen(trim($value['descricao'])) == 0 AND strlen(trim($value['referencia'])) == 0  AND strlen(trim($value['serie'])) == 0 AND $tem_produto != "true"){
					$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
					$msg_erro["campos"][] = "produto_$key";
					$error =  "true";
				}else{
					$tem_produto = "true";
					$produto_anterior = $value['id'];
				}

				if (strlen(trim($value['serie'])) == 0 AND (strlen(trim($value['descricao'])) > 0 OR strlen(trim($value['referencia'])) > 0)){
					$msg_erro["msg"][]    = "Número de série do produto: ".$value['referencia']." é obrigatório";
					$msg_erro["campos"][] = "produto_$key";
					$error =  "true";
				}else{
					if (strlen(trim($value['serie'])) > 0){
						$sql_serie = "
							SELECT serie
							FROM tbl_numero_serie
							WHERE fabrica = $login_fabrica
							AND serie ='".$value['serie']."'
							AND produto = $produto";
						$res_serie = pg_query($con, $sql_serie);

						if(pg_num_rows($res_serie) == 0){
							$msg_erro["msg"][]    = "Número de série : ".$value['serie']." não encontrado.";
							$msg_erro["campos"][] = "produto_$key";
							$error =  "true";
						}else{
							$serie = pg_fetch_result($res_serie, 0, 'serie');
						}
					}
				}

				if (strlen(trim($value['referencia'])) > 0 AND strlen(trim($value['descricao'])) > 0 AND strlen(trim($value['serie'])) > 0){
					if (!in_array($login_fabrica, [169,170]) || (in_array($login_fabrica, [169,170]) && empty($rpi))) {
						$sql = "
							SELECT tbl_rpi_produto.produto, tbl_rpi.rpi
							FROM tbl_rpi_produto
							JOIN tbl_rpi ON tbl_rpi.rpi = tbl_rpi_produto.rpi
								AND tbl_rpi.fabrica = {$login_fabrica}
							WHERE tbl_rpi_produto.fabrica = {$login_fabrica}
							AND tbl_rpi_produto.produto = {$produto}
							AND tbl_rpi_produto.serie = '{$serie}'
							AND tbl_rpi.cancelado IS NULL;
						";
						$res = pg_query($con, $sql);

						if (pg_num_rows($res) > 0){
							$msg_erro["msg"][] 	  = "RPI já cadastrado para o produto: ".$value['referencia'].' Série: '.$serie;
							$msg_erro["campos"][] = "produto_$key";
							$error =  "true";
						}
					}
				}
				if ($error != "true"){
					$array_dados_valida[$serie] = array(
                        'MATNR' => $value['referencia'],
                        'SERNR' => $serie
                    );

					$valida_rpi = $classOs->validaRPI($array_dados_valida);
					$verifica_update = false;

				if (in_array($login_fabrica, [169,170]) && !empty($rpi)) {
					if ($areaAdmin) {
						$sql = "
							UPDATE tbl_rpi SET 
								responsavel         = E'".addslashes($responsavel)."',
								data_partida        = '{$aux_data_partida}',
								consumidor_nome     = E'".addslashes($consumidor_nome)."',
								consumidor_cpf      = '{$consumidor_cpf_cnpj}',
								consumidor_cep      = '{$consumidor_cep}', 
								consumidor_cidade   = {$consumidor_cidade},
								consumidor_bairro   = E'".addslashes($consumidor_bairro)."', 
								consumidor_endereco = E'".addslashes($consumidor_endereco)."',
								consumidor_telefone = '{$xconsumidor_telefone}'
								$campos_update
							WHERE fabrica = {$login_fabrica}
							AND rpi = {$rpi}";

						$res = pg_query($con, $sql);

		          		if (empty($rpi)) {
		          			$msg_erro["msg"][] = "Erro ao gravar RPI #001";
							$error = "true";
		          		}

						$sql_update = "
							UPDATE tbl_rpi_produto SET
								produto = {$produto},
								serie   = '{$serie}'
							WHERE fabrica = {$login_fabrica}
							AND rpi = {$rpi}
						";

						$res_update = pg_query($con, $sql_update);
						$verifica_update = true;
					} else {
						$msg_erro["msg"][] = "Não é possível alterar as informações do RPI";
						$error = "true";
					}
				} else {

					$sql = "
						INSERT INTO tbl_rpi (
							fabrica,
							posto,
							responsavel,
							data_partida,
							consumidor_nome,
							consumidor_cpf,
							consumidor_cep,
							consumidor_cidade,
							consumidor_bairro,
							consumidor_endereco,
							consumidor_telefone
							$campos
						)VALUES(
							{$login_fabrica},
							{$login_posto},
							E'".addslashes($responsavel)."',
							'{$aux_data_partida}',
							E'".addslashes($consumidor_nome)."',
							'{$consumidor_cpf_cnpj}',
							'{$consumidor_cep}',
							{$consumidor_cidade},
							E'".addslashes($consumidor_bairro)."',
							E'".addslashes($consumidor_endereco)."',
							'{$xconsumidor_telefone}'
							$values
						) RETURNING rpi;
					";
					$res = pg_query($con, $sql);
	          		$rpi = pg_fetch_result($res, 0, 0);

	          		if (empty($rpi)) {
	          			$msg_erro["msg"][] = "Erro ao gravar RPI #001";
						$error = "true";
	          		}

					$sql_insert = "
						INSERT INTO tbl_rpi_produto (
							fabrica,
							rpi,
							produto,
							serie
						)VALUES(
							{$login_fabrica},
							{$rpi},
							{$produto},
							'{$serie}'
						);
					";

					$res_insert = pg_query($con, $sql_insert);

				}
					if (pg_last_error()){
						$msg_erro["msg"][] = "Erro ao gravar RPI #002";
						$error = "true";
					}

					if ($error != "true") {
						if ($valida_rpi == false) {
							try {
								$dadosRPI = $classOs->getDadosRPIExport($rpi, $serie);
								$exportRPI = $classOs->exportRPI($dadosRPI);

								if ($verifica_update == true) {
									$msg_success["msg_success"][] = "RPI Alterado com sucesso";
									$msg_success["campos"][] = "produto_{$key}";	
								} else {
									$msg_success["msg_success"][] = "RPI Cadastrado com sucesso";
									$msg_success["campos"][] = "produto_{$key}";
								}
								
								if ($exportRPI !== true && $areaAdmin) {
									throw new Exception("O RPI não foi exportado, entre em contato com a fábrica");
								}


							} catch(Exception $e) {
								$msg_erro["msg"][] = $e->getMessage();
								$msg_erro["campos"][] = "produto_{$key}";
							}
						}else{
							if ($verifica_update == true) {
								$msg_success["msg_success"][] = "RPI Alterado com sucesso";
								$msg_success["campos"][] = "produto_{$key}";	
							} else {
								$msg_success["msg_success"][] = "RPI Cadastrado com sucesso";
								$msg_success["campos"][] = "produto_{$key}";
							}
						}
					}else{
						$msg_erro["msg"][] = "Ocorreu um erro cadastrando as informações do RPI";
						$msg_erro["campos"][] = "produto_{$key}";
                	}
				}
			}
		}

		if (strlen(trim($produto)) == 0){
			$msg_erro["msg"][] = 'Informe pelo menos 1 Equipamento';
			$msg_erro["campos"][] = "produto_0";
		}

		if (count($msg_erro['msg_export'])){
			$msg_erro["msg"][] = 'RPI já cadastrado para os produtos';
		}
	}
}

if (in_array($login_fabrica, [169,170]) && isset($_GET['rpi']) && $_GET['rpi'] != "") {
	if ($areaAdmin) {
		$rpi = $_GET['rpi'];
		$sql = "SELECT * FROM tbl_rpi 
				JOIN tbl_rpi_produto USING(rpi) WHERE rpi = {$rpi}";
		$res = pg_query($con, $sql);
		if (pg_num_rows($res) > 0) {
			$responsavel 			 = pg_fetch_result($res, 0, 'responsavel'); 
			$funcao  				 = pg_fetch_result($res, 0, 'responsavel_funcao');	
			$data_partida  			 = date('d/m/Y', strtotime(pg_fetch_result($res, 0, 'data_partida')));
			$consumidor_nome  		 = pg_fetch_result($res, 0, 'consumidor_nome');	
			$consumidor_cpf          = pg_fetch_result($res, 0, 'consumidor_cpf');	
			$xconsumidor_cpf_cnpj     = 'cpf';
			if (strlen(trim($consumidor_cpf)) > 11) {
				$xconsumidor_cpf_cnpj = 'cnpj';
			}
			$consumidor_cep  		 = pg_fetch_result($res, 0, 'consumidor_cep');		
			$consumidor_cidade  	 = pg_fetch_result($res, 0, 'consumidor_cidade');
			if (!empty($consumidor_cidade)) {
				$sql_cidade = "SELECT estado FROM tbl_cidade WHERE cidade = {$consumidor_cidade}";
				$res_cidade = pg_query($con, $sql_cidade);
				$consumidor_estado   = pg_fetch_result($res_cidade, 0, 'estado');		
			}		
			$consumidor_bairro       = pg_fetch_result($res, 0, 'consumidor_bairro');  		
			$consumidor_endereco  	 = pg_fetch_result($res, 0, 'consumidor_endereco');  			
			$consumidor_numero  	 = pg_fetch_result($res, 0, 'consumidor_numero');  			
			$consumidor_complemento  = pg_fetch_result($res, 0, 'consumidor_complemento');  			
			$consumidor_telefone  	 = pg_fetch_result($res, 0, 'consumidor_telefone');  				
			$consumidor_contato  	 = pg_fetch_result($res, 0, 'consumidor_contato');  				
			$informacoes  			 = pg_fetch_result($res, 0, 'obs');  	
			$produto_id              = pg_fetch_result($res, 0, 'produto');
			if (!empty($produto_id)) {
				$sql_produto = "SELECT referencia_pesquisa, descricao 
								FROM tbl_produto 
								WHERE produto = {$produto_id} 
								AND fabrica_i = {$login_fabrica}";
				$res_produto = pg_query($con, $sql_produto);
				$produto_ref = pg_fetch_result($res_produto, 0, 'referencia_pesquisa');
				$produto_des = pg_fetch_result($res_produto, 0, 'descricao'); 
			}
			$produto_serie           = pg_fetch_result($res, 0, 'serie');  	
			$produto_produtos[0]['serie']      = $produto_serie;
			$produto_produtos[0]['referencia'] = $produto_ref;
			$produto_produtos[0]['descricao']  = $produto_des;
			$produto_produtos[0]['id']         = $produto_id;	  				
		} else {
			$msg_erro["msg"][] = 'RPI não encontrado';
		}
	} else {
		$msg_erro["msg"][] = 'Não é possível alterar as informações do RPI';
	} 
}

$title = "CADASTRO DE RPI";

if ($areaAdmin === true) {
	$layout_menu = 'callcenter';
	include __DIR__.'/admin/cabecalho_new.php';
} else {
    $layout_menu = 'os';
    include __DIR__.'/cabecalho_new.php';
}

$plugins = array(
   "datepicker",
   "shadowbox",
   "maskedinput",
   "alphanumeric"
);
include __DIR__.'/admin/plugin_loader.php';
?>

	<style type="text/css">
		#modelo_produto{
			display: none;
		}
	</style>
	<script type="text/javascript">
		$(function() {
			/**
		     * Inicia o shadowbox, obrigatório para a lupa funcionar
		     */
		    Shadowbox.init();

			$("#data_partida").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");

			$("#consumidor_cep").mask("99999-999",{placeholder:""});

			$("input[name='consumidor_cpf_cnpj']").change(function(){
		        var tipo = $(this).val();
		        $("#consumidor_cpf").unmask();
		        if(tipo == 'cnpj'){
		            $("#consumidor_cpf").mask("99.999.999/9999-99",{placeholder:""});
		        }else{
		            $("#consumidor_cpf").mask("999.999.999-99",{placeholder:""});
		        }
		    });

			$("#limpar_form").click(function(){
				if (confirm('Deseja realmente limpar os dados da tela ?')) {
			    	window.location='cadastro_rpi.php';
			    }else{
			    	return false;
			    }
			});

			$("#consumidor_cep").blur(function() {
		        if ($(this).attr("readonly") == undefined) {
		            busca_cep($(this).val(), 'consumidor');
		        }
		    });

			$("#consumidor_telefone").mask("(99) 9999-9999",{placeholder:""});
		    $("#consumidor_celular").mask("(99) 99999-9999",{placeholder:""});

		    setTimeout(function(){
                $(".msg_success").hide();
            }, 5000);

		    /**
		     * Evento que adiciona uma nova linha de peça
		     */
		    $("button[name=adicionar_linha]").click(function() {
	            var nova_linha = $("#modelo_produto").clone();
	            var posicao = $("div.row-fluid[name^=produto_][name!=produto___modelo__]").length;

	            $("#produto_produtos").append($(nova_linha).html().replace(/__modelo__/g, posicao).replace(/disabled\=['"]disabled['"]/g, ""));

	            $("div.row-fluid[name=produto_"+posicao+"]").find(".numeric").numeric();
		    });


		    /**
		     * Evento que limpa uma linha de peça
		    */
		    $(document).on("click", "button[name=remove_produto]", function() {
		        var posicao = $(this).attr("rel");
		        $("input[name='produto_produtos["+posicao+"][serie]']").val("").removeAttr("readonly");
	            $("input[name='produto_produtos["+posicao+"][referencia]']").val("").removeAttr("readonly");
	            $("input[name='produto_produtos["+posicao+"][descricao]']").val("").removeAttr("readonly");
	            $("input[name='produto_produtos["+posicao+"][id]']").val("");

	            $("div[name=produto_"+posicao+"]").prop('style', 'background-color: none');
	            $("div[name=produto_"+posicao+"]").find("span[rel=lupa_produto]").show();

	            $("#cadastrado").hide();
	            $(this).removeClass('btn-success');
	            $(this).addClass('btn-danger');
	            $(this).hide();
		    });

		    $("#form_submit").on("click", function(e) {
		        e.preventDefault();

		        var submit = $(this).data("submit");
		        if (submit.length == 0) {
		            $(this).data({ submit: true });
		            $("input[name=gravar]").val('Gravar');
		            $(this).parents("form").submit();
		        } else {
		           alert("Não clique no botão voltar do navegador, utilize somente os botões da tela");
		        }
		    });

		    $(document).on("click", "span[rel=lupa_produto]", function() {
		        var parametros_lupa_produto = ["posto", "ativo", "posicao", "codigo_validacao_serie"];
	            $.lupa($(this), parametros_lupa_produto);
		    });
		});

		function retorna_produto(retorno) {
			$("input[name='produto_produtos["+retorno.posicao+"][id]']").val(retorno.produto).attr({ readonly: "readonly" });
            $("input[name='produto_produtos["+retorno.posicao+"][referencia]']").val(retorno.referencia).attr({ readonly: "readonly" });
            $("input[name='produto_produtos["+retorno.posicao+"][descricao]']").val(retorno.descricao).attr({ readonly: "readonly" });
            $("input[name='produto_produtos["+retorno.posicao+"][serie]']").val(retorno.serie_produto).attr({ readonly: "readonly" });
            $("div[name=produto_"+retorno.posicao+"]").find("span[rel=lupa_produto]").hide();
            $("div[name=produto_"+retorno.posicao+"]").find("button[name=remove_produto]").show();
		}

		/**
		 * Função que faz um ajax para buscar o cep nos correios
		 */
		function busca_cep(cep, consumidor_revenda, method) {
		    if (cep.length > 0) {
		        var img = $("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } });

		        if (typeof method == "undefined" || method.length == 0) {
                    method = "webservice";

                    $.ajaxSetup({
                        timeout: 3000
                    });
                } else {
                    $.ajaxSetup({
                        timeout: 5000
                    });
                }

		        $.ajax({
		            async: true,
		            url: "ajax_cep.php",
		            type: "GET",
		            data: { cep: cep, method: method },
		            beforeSend: function() {
		                $("#"+consumidor_revenda+"_estado").next("img").remove();
                        $("#"+consumidor_revenda+"_cidade").next("img").remove();
                        $("#"+consumidor_revenda+"_bairro").next("img").remove();
                        $("#"+consumidor_revenda+"_endereco").next("img").remove();

		                $("#"+consumidor_revenda+"_estado").hide().after(img.clone());
		                $("#"+consumidor_revenda+"_cidade").hide().after(img.clone());
		                $("#"+consumidor_revenda+"_bairro").hide().after(img.clone());
		                $("#"+consumidor_revenda+"_endereco").hide().after(img.clone());
		            },
		            error: function(xhr, status, error) {
                        busca_cep(cep, consumidor_revenda, "database");
                    },
		            success: function(data) {
		                results = data.split(";");

		                if (results[0] != "ok") {
		                    alert(results[0]);
		                    $("#"+consumidor_revenda+"_cidade").show().next().remove();
		                } else {
		                    $("#"+consumidor_revenda+"_estado").val(results[4]);

		                    busca_cidade(results[4], consumidor_revenda);
		                    results[3] = results[3].replace(/[()]/g, '');

		                    $("#"+consumidor_revenda+"_cidade").val( $('option:contains("'+results[3].unaccent().toUpperCase()+'")').val() );

		                    if (results[2].length > 0) {
		                        $("#"+consumidor_revenda+"_bairro").val(results[2]);
		                    }

		                    if (results[1].length > 0) {
		                        $("#"+consumidor_revenda+"_endereco").val(results[1]);
		                    }
		                }

		                $("#"+consumidor_revenda+"_estado").show().next().remove();
		                $("#"+consumidor_revenda+"_bairro").show().next().remove();
		                $("#"+consumidor_revenda+"_endereco").show().next().remove();

		                if ($("#"+consumidor_revenda+"_bairro").val().length == 0) {
		                    $("#"+consumidor_revenda+"_bairro").focus();
		                } else if ($("#"+consumidor_revenda+"_endereco").val().length == 0) {
		                    $("#"+consumidor_revenda+"_endereco").focus();
		                } else if ($("#"+consumidor_revenda+"_numero").val().length == 0) {
		                    $("#"+consumidor_revenda+"_numero").focus();
		                }

		                $.ajaxSetup({
		                    timeout: 0
		                });
		            }
		        });
		    }
		}

		/**
		 * Função que busca as cidades do estado e popula o select cidade
		 */
		function busca_cidade(estado, consumidor_revenda, cidade) {
		    $("#"+consumidor_revenda+"_cidade").find("option").first().nextAll().remove();

		    if (estado.length > 0) {
		        $.ajax({
		            async: false,
		            url: "cadastro_rpi.php",
		            type: "POST",
		            data: { ajax_busca_cidade: true, estado: estado },
		            beforeSend: function() {
		                if ($("#"+consumidor_revenda+"_cidade").next("img").length == 0) {
		                    $("#"+consumidor_revenda+"_cidade").hide().after($("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } }));
		                }
		            },
		            complete: function(data) {
		                data = $.parseJSON(data.responseText);

		                if (data.error) {
		                    alert(data.error);
		                } else {
		                    $.each(data.cidades, function(key, value) {
		                    	var option = $("<option></option>", { value: value.cidade_id, text: value.cidade });
		                    	$("#"+consumidor_revenda+"_cidade").append(option);
		                    });
		                }

		                $("#"+consumidor_revenda+"_cidade").show().next().remove();
		            }
		        });
		    }

		    if(typeof cidade != "undefined" && cidade.length > 0){

		        $("#consumidor_cidade option[value='"+cidade+"']").attr('selected','selected');

		    }

		}
	</script>

	<?php
	if (count($msg_erro["msg"]) > 0) {
	?>
	    <div class="alert alert-error">
			<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
	    </div>
	<?php
	} else if (count($msg_success["msg_success"]) > 0) { 
		?>
	    <div class="alert alert-success">
			<h4><?=implode("<br />", $msg_success["msg_success"])?></h4>
	    </div>
	<?php
	}
	?>

	<form name="frm_os" id="frm_os" method="POST" class="form-search form-inline" enctype="multipart/form-data" >
		<?php if ($areaAdmin === true){  ?>
		<input type="hidden" name="id_posto" value='<?=$login_posto?>'>
		<input type="hidden" name="rpi" value="<?=$rpi?>">
		<?php } ?>
		<div id="div_informacoes_instalador" class="tc_formulario">
	        <div class="titulo_tabela">Informações do Instalador <?=$instalador_nome?></div>
	        <br />
	        <div class="row-fluid">
	        	<div class="span1"></div>
	        	<div class="span4">
	        		<h5 class='asteristico'>*</h5>
	        		<div class="control-group <?=(in_array('responsavel', $msg_erro['campos'])) ? "error" : "" ?>">
	        			<label class="control-label" for="responsavel">Responsável</label>
	        			<div class="controls controls-row">
                            <div class="span12">
                            	<input id="responsavel" name="responsavel" class="span12" type="text" value="<?=$responsavel?>" />
                            </div>
                        </div>
	        		</div>
	        	</div>
	        	<div class="span4">
	        		<div class="control-group <?=(in_array('funcao', $msg_erro['campos'])) ? "error" : "" ?>">
	        			<label class="control-label" for="funcao">Função</label>
	        			<div class="controls controls-row">
                            <div class="span12">
                            	<input id="funcao" name="funcao" class="span12" type="text" value="<?=$funcao?>" />
                            </div>
                        </div>
	        		</div>
	        	</div>
	        	<div class='span2'>
	        		<h5 class='asteristico'>*</h5>
	        		<div class='control-group <?=(in_array('data_partida', $msg_erro['campos'])) ? "error" : "" ?>'>
                        <label class="control-label" for="data_partida">Data Partida</label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <input id="data_partida" name="data_partida" class="span12" type="text" value="<?=$data_partida?>"/>
                            </div>
                        </div>
                    </div>
	        	</div>
	        	<div class="span1"></div>
	        </div>
	    </div>

	    <div id="div_informacoes_cliente" class="tc_formulario">
	        <div class="titulo_tabela">Informações do Cliente <?=$consumidor_nome?></div>
	        <br />

	        <div class="row-fluid">
                <div class="span1"></div>
                <div class="span3">
                	  <h5 class='asteristico'>*</h5>
                    <div class='control-group <?=(in_array('consumidor_nome', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="consumidor_nome">Nome</label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <input id="consumidor_nome" name="consumidor_nome" class="span12" type="text" value="<?=$consumidor_nome?>" maxlength="50" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="span3">
                	  <h5 class='asteristico'>*</h5>
                    <div class='control-group <?=(in_array('consumidor_cpf', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="consumidor_cpf">
                                CPF <input type="radio" id="cpf_cnpj" name="consumidor_cpf_cnpj" <?= ($xconsumidor_cpf_cnpj == "cpf") ? 'checked="checked"': ''; ?> value="cpf" />
                                /CNPJ <input type="radio" id="cnpj_cpf" name="consumidor_cpf_cnpj" <?= ($xconsumidor_cpf_cnpj == "cnpj") ? 'checked="checked"': ''; ?> value="cnpj" />
                        </label>
                        <div class="controls controls-row">
                            <div class="span12 input-append">
                                <input id="consumidor_cpf" name="consumidor_cpf" class="span12" type="text" value="<?=$consumidor_cpf?>"/>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="span2">
                    <h5 class='asteristico'>*</h5>
                    <div class='control-group <?=(in_array('consumidor_cep', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="consumidor_cep">CEP</label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <input id="consumidor_cep" name="consumidor_cep" class="span12" type="text" value="<?=$consumidor_cep?>" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="span2">
                    <h5 class='asteristico'>*</h5>
                    <div class="control-group <?=(in_array('consumidor_estado', $msg_erro['campos'])) ? "error" : "" ?>">
                        <label class="control-label" for="consumidor_estado">Estado</label>
                        <div class="controls controls-row">
                            <div class="span12">
                               <select id="consumidor_estado" name="consumidor_estado" class="span12" >
                                    <option value="" >Selecione</option>
                                    <?php
                                    foreach ($array_estados as $sigla => $nome_estado) {
                                        $selected = ($sigla == $consumidor_estado) ? "selected" : "";
                                        echo "<option value='{$sigla}' {$selected} >" . utf8_decode($nome_estado) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row-fluid">
                <div class="span1"></div>
                <div class="span3">
                    <h5 class='asteristico'>*</h5>
                    <div class="control-group <?=(in_array('consumidor_cidade', $msg_erro['campos'])) ? "error" : "" ?>">
                        <label class="control-label" for="consumidor_cidade">Cidade</label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <select id="consumidor_cidade" name="consumidor_cidade" class="span12" />
                                    <option value="" >Selecione</option>
                                    <?php
                                    if (strlen($consumidor_estado) > 0) {
                                    	$sql = "
                                    		SELECT
                                				UPPER(fn_retira_especiais(nome)) AS cidade,
                                				cidade AS cidade_id
                            				FROM tbl_cidade
                            				WHERE UPPER(estado) = UPPER('".$consumidor_estado."')
                                            ORDER BY cidade ASC";
                                        $res = pg_query($con, $sql);
                                        if (pg_num_rows($res) > 0) {

                                            while ($result = pg_fetch_object($res)) {
                                                $selected  = (trim($result->cidade_id) == trim($consumidor_cidade)) ? "SELECTED" : "";

                                                echo "<option value='{$result->cidade_id}' {$selected} >{$result->cidade} </option>";
                                            }
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="span3">
                    <h5 class='asteristico'>*</h5>
                    <div class='control-group <?=(in_array('consumidor_bairro', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="consumidor_bairro">Bairro</label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <input id="consumidor_bairro" name="consumidor_bairro" class="span12" type="text" value="<?=$consumidor_bairro?>" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="span3">
                    <h5 class='asteristico'>*</h5>
                    <div class='control-group <?=(in_array('consumidor_endereco', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="consumidor_endereco">Endereço</label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <input id="consumidor_endereco" name="consumidor_endereco" class="span12" type="text" value="<?=$consumidor_endereco?>" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="span1">
                    <div class='control-group <?=(in_array('consumidor_numero', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="consumidor_numero">Número</label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <input id="consumidor_numero" name="consumidor_numero" class="span12" type="text" value="<?=$consumidor_numero?>" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row-fluid">
                <div class="span1"></div>

                <div class="span3">
                    <div class='control-group <?=(in_array('consumidor_complemento', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="consumidor_complemento">Complemento</label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <input id="consumidor_complemento" name="consumidor_complemento" class="span12" type="text" value="<?=$consumidor_complemento?>" maxlength="20" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="span2">
                	  <h5 class='asteristico'>*</h5>
                    <div class="control-group <?=(in_array('consumidor_celular', $msg_erro['campos'])) ? "error" : "" ?>">
                        <label class="control-label" for="consumidor_celular">Celular</label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <input id="consumidor_celular" name="consumidor_celular" class="span12" type="text" value="<?=$consumidor_celular?>" maxlength="20" placeholder="(99) 99999-9999" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="span2">
                    <h5 class='asteristico'>*</h5>
                    <div class="control-group <?=(in_array('consumidor_telefone', $msg_erro['campos'])) ? "error" : "" ?>">
                        <label class="control-label" for="consumidor_telefone">Telefone</label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <input id="consumidor_telefone" name="consumidor_telefone" class="span12" type="text" value="<?=$consumidor_telefone?>" maxlength="20" placeholder="(99) 9999-9999" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="span3">
                    <div class='control-group <?=(in_array('consumidor_contato', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="consumidor_contato">Contato</label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <input id="consumidor_contato" name="consumidor_contato" class="span12" type="text" value="<?=$consumidor_contato?>" maxlength="20" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="span1"></div>
            </div>
	    </div>

	    <div id='div_informacoes' class='tc_formulario'>
	    	<div class='titulo_tabela'>Informações</div>
	    	<br/>
	    	<div class="row-fluid">
                <div class="span1"></div>
                <div class="span10">
                    <div class='control-group <?=(in_array('informacoes', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <div class="controls controls-row">
                            <div class="span12">
                                <textarea id='informacoes' name="informacoes" class='span12' ><?=$informacoes?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span1"></div>
            </div>
	    </div>

	    <div id="div_equipamentos" class="tc_formulario">

	    	<div class="titulo_tabela">Equipamentos</div>
	    	<br/>
	    	<div id="modelo_produto">
                <div class="row-fluid" name="produto___modelo__">
                    <div class="span1">
                    	<br/>
                        <div class='control-group'>
                            <div class="controls controls-row">
                                <div class="span12 tac" >
                                    <input type="hidden" name="produto_produtos[__modelo__][id]" rel="produto_id" value="" disabled="disabled" />
                                    <button type="button" class="btn btn-mini btn-danger" name="remove_produto" rel="__modelo__" style="display: none;" >X</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="span3" id="produto_serie___modelo__">
	                    <div class='control-group'>
	                        <label class="control-label" for="serie">Número de Série</label>
	                        <div class="controls controls-row">
	                            <div class="span12 input-append">
	                                <input name="produto_produtos[__modelo__][serie]" class="span10 produto_serie" type="text" value="" maxlength="30" disabled="disabled" />
	                                <span class="add-on" rel="lupa_produto" style='cursor: pointer;'>
	                                     <i class='icon-search'></i>
	                                </span>
	                                <input type="hidden" name="lupa_config" posto="<?=$login_posto?>" tipo="produto" codigo_validacao_serie="true" ativo="t" posicao="__modelo__" mascara='true' grupo-atendimento='' fora-garantia='' km-google='' parametro="numero_serie" />
	                            </div>
	                        </div>
	                    </div>
	                </div>

                    <div class="span3" id="produto_referencia___modelo__">
                        <div class='control-group' >
                            <label class="control-label">Referência</label>
                            <div class="controls controls-row">
                                <div class="span10 input-append">
                                    <input  name="produto_produtos[__modelo__][referencia]" class="span12 produto_referencia" type="text" value="" <?=$placeholder?> disabled="disabled" />
                                    <span class="add-on" rel="lupa_produto">
                                        <i class="icon-search"></i>
                                    </span>
                                    <input type="hidden" name="lupa_config" posto="<?=$login_posto?>" tipo="produto" ativo="t" codigo_validacao_serie="true" parametro="referencia" posicao="__modelo__" mascara='true' grupo-atendimento='' fora-garantia='' km-google='' />
	                            </div>
                            </div>
                        </div>
                    </div>
                    <div class="span4" id="produto_descricao___modelo__">
                        <div class='control-group'>
                            <label class="control-label" >Descrição</label>
                            <div class="controls controls-row">
                                <div class="span11 input-append">
                                    <input name="produto_produtos[__modelo__][descricao]" class="span12 produto_descricao" type="text" value="" disabled="disabled" />
                                    <span class="add-on" rel="lupa_produto" >
                                        <i class="icon-search"></i>
                                    </span>
                                    <input type="hidden" name="lupa_config" posto="<?=$login_posto?>" ativo="t" tipo="produto" codigo_validacao_serie="true" parametro="descricao" mascara='true' grupo-atendimento='' fora-garantia='' km-google='' posicao="__modelo__" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="produto_produtos">
            	<?php

            	if (count($produto_produtos)){
            		$qtde_produtos = count($produto_produtos);
            	}else{
            		$qtde_produtos = 1;
            	}
                for ($i = 0; $i < $qtde_produtos; $i++) {
                	$display_cadastrado = "style='display:none;'";
                	$class_btn = "btn-danger";
                	if (count($msg_erro["msg"]) > 0 && in_array("produto_$i", $msg_erro["campos"]) && $tem_produto != "true") {
                        $bgcolor = "style='background-color: red;'";
                    } else if(count($msg_erro["msg"]) > 0 && in_array("produto_$i", $msg_erro["campos"]) && $tem_produto == "true"){
                    	$bgcolor = "style='background-color: red;'";
                    } else if (count($msg_success["msg_success"]) > 0 && in_array("produto_$i", $msg_success["campos"])){
                    	$bgcolor = "style='background-color: #62c462;'";
                    	#$campo_disabled = "disabled";
                    	$display_cadastrado = "";
                    	$class_btn = "btn-success";
                    } else {
                    	#unset($campo_disabled);
                        unset($bgcolor);
                    }

                    if (strlen(trim($produto_produtos[$i]['id'])) > 0){
                    	$style_display = "style='display:inline;'";
                    	$readonly = "readonly";
                    	$peca_esconde_lupa = "style='display:none;'";
                    }else{
                    	$style_display = "style='display:none;'";
                    	$readonly = "";
                    	$peca_esconde_lupa = "";
                    }
                ?>
		          	<div class="row-fluid" name="produto_<?=$i?>" <?=$bgcolor?>>
                    	<div class="span1">
                    		<br/>
            	            <div class='control-group'>
                                <div class="controls controls-row">
                                    <div class="span12 tac">
                                        <input type="hidden" name="produto_produtos[<?=$i?>][id]" rel="produto_id" value="<?=$produto_produtos[$i]['id']?>" />
                                        <button type="button" class="btn btn-mini <?=$class_btn?>" name="remove_produto" rel="<?=$i?>" <?=$style_display?> >X</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="span3" id="produto_serie_<?= $i; ?>">
		                    <div class='control-group <?=(in_array('consumidor_serie_$i', $msg_erro['campos'])) ? "error" : "" ?>' >
		                        <label class="control-label" for="serie">Número de Série</label>
		                        <div class="controls controls-row">
		                            <div class="span12 input-append">
		                                <input name="produto_produtos[<?=$i?>][serie]" class="span10 produto_serie" type="text" <?=$campo_disabled?>  <?=$readonly?> value="<?=$produto_produtos[$i]['serie']?>" maxlength="30" />
		                                <span class="add-on" rel="lupa_produto" <?=$peca_esconde_lupa?> readonly="readonly" >
		                                     <i class='icon-search'></i>
		                                </span>
		                                <input type="hidden" name="lupa_config" posto="<?=$login_posto?>" tipo="produto" ativo="t" codigo_validacao_serie="true" posicao="<?=$i?>" mascara='true' grupo-atendimento='' fora-garantia='' km-google='' parametro="numero_serie" />
		                            </div>
		                        </div>
		                    </div>
		                </div>

                        <div class="span3" id="produto_referencia_<?= $i; ?>">
                            <div class='control-group' >
                                <label class="control-label">Referência</label>
                                <div class="controls controls-row">
                                    <div class="span10 input-append">
                                        <input  name="produto_produtos[<?=$i?>][referencia]" class="span12 produto_referencia" <?=$campo_disabled?> <?=$readonly?> type="text" value="<?=$produto_produtos[$i]['referencia']?>" />
                                        <span class="add-on" rel="lupa_produto" <?=$peca_esconde_lupa?> readonly="readonly" >
                                            <i class="icon-search"></i>
                                        </span>
                                        <input type="hidden" name="lupa_config" posto="<?=$login_posto?>" tipo="produto" codigo_validacao_serie="true" ativo="t" parametro="referencia" mascara='true' grupo-atendimento='' fora-garantia='' km-google='' produto="" posicao="<?=$i?>" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="span4" id="produto_descricao_<?= $i; ?>">
                            <div class='control-group' >
                                <label class="control-label" >Descrição</label>
                                <div class="controls controls-row">
                                    <div class="span11 input-append">
                                        <input name="produto_produtos[<?=$i?>][descricao]" class="span12 produto_descricao" type="text" <?=$campo_disabled?> <?=$readonly?> value="<?=$produto_produtos[$i]['descricao']?>" />
                                        <span class="add-on" rel="lupa_produto" <?=$peca_esconde_lupa?> readonly="readonly" >
                                            <i class="icon-search"></i>
                                        </span>
                                        <input type="hidden" name="lupa_config" posto="<?=$login_posto?>" tipo="produto" ativo="t" parametro="descricao" produto="" codigo_validacao_serie="true" mascara='true' grupo-atendimento='' fora-garantia='' km-google='' posicao="<?=$i?>" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id='cadastrado' <?=$display_cadastrado?> style=" float: right; margin-top: -25px; margin-right: 12px;">
							<span class="label label-success">Cadastrado</span>
						</div>
                    </div>
            	<?php } ?>
            </div>
            <br/>
        	<button type="button" name="adicionar_linha" class="btn btn-primary tac" >Adicionar nova linha</button>
        	<br/><br/>
        </div>

        <div class='row-fluid'>
        	<div class='tac'>
        		<p><br/>
					<input type='hidden' name="gravar" />
        			<input type="button" class="btn btn-large" value="Gravar" id="form_submit" data-submit="" />

        			<input type="button" class="btn btn-large btn-info" value="Limpar dados" id="limpar_form" />
				</p><br/>
        	</div>
        </div>
	</form>

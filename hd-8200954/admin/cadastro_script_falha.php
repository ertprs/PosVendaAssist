<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$get_script 	= $_GET['script_falha'];
$get_familia 	= $_GET['familia'];
$get_defeito 	= $_GET['defeito_reclamado'];
$get_produto	= $_GET['produto'];
$get_linha      = $_GET['linha'];
$get_duplicar   = $_GET['duplicar'];

if (isset($_POST['searchProd'])) {
	$familia = $_POST['familia'];

	$queryProd = "SELECT produto,
						 descricao
				  FROM tbl_produto
				  WHERE fabrica_i = {$login_fabrica}
				  AND familia = {$familia}
				  AND ativo IS TRUE
				  ORDER BY descricao ASC";
	$result = pg_query($con, $queryProd);
	$response = pg_fetch_all($result);

	$newResponse = array_map(function ($r) {
		return ['produto' => $r['produto'], 'descricao' => iconv('ISO-8859-1', 'UTF-8', $r['descricao'])];
	}, $response);


	echo json_encode($newResponse);
	exit;
}

if ($_POST['ajax'] && $_POST['linha']) {
	$linha = $_POST['linha'];
	$result = array();

	$sql = "SELECT 	tbl_defeito_reclamado.defeito_reclamado,
					tbl_defeito_reclamado.descricao
			FROM 	tbl_defeito_reclamado
			JOIN 	tbl_diagnostico ON tbl_diagnostico.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
				AND tbl_diagnostico.fabrica = {$login_fabrica}
			WHERE tbl_defeito_reclamado.fabrica = {$login_fabrica}
			AND tbl_defeito_reclamado.ativo IS TRUE
			AND tbl_diagnostico.linha = {$linha} ";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res) > 0){
        for ($i=0; $i < pg_num_rows($res); $i++) {
            $result[] = array("defeito_reclamado" => utf8_encode(pg_fetch_result($res, $i, defeito_reclamado)), "descricao" => utf8_encode(pg_result($res,$i,descricao)));
        }
        exit(json_encode(array("ok" => $result)));
    }else{
        exit(json_encode(array("no" => 'false')));
    }
}

if ($_POST['ajax'] && $_POST['familia']) {

	$familia = $_POST['familia'];
	$result = array();

	$sql = "SELECT 	tbl_defeito_reclamado.defeito_reclamado,
					tbl_defeito_reclamado.descricao
			FROM 	tbl_defeito_reclamado
			JOIN 	tbl_diagnostico ON tbl_diagnostico.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
				AND tbl_diagnostico.fabrica = {$login_fabrica}
			WHERE tbl_defeito_reclamado.fabrica = {$login_fabrica}
			AND tbl_defeito_reclamado.ativo IS TRUE
			AND tbl_diagnostico.familia = {$familia} ";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res) > 0){
        for ($i=0; $i < pg_num_rows($res); $i++) {
            $result[] = array("defeito_reclamado" => utf8_encode(pg_fetch_result($res, $i, defeito_reclamado)), "descricao" => utf8_encode(pg_result($res,$i,descricao)));
        }
        exit(json_encode(array("ok" => $result)));
    }else{
        exit(json_encode(array("no" => 'false')));
    }
}

if ($_POST["btn_acao"] == "gravar") {

	$familia 				= $_POST['familia'];
	$linha                  = $_POST['linha'];
	$defeito_reclamado  	= $_POST['defeito_reclamado'];
	$defeito_reclamado 		= $_POST['defeito_reclamado_id'];
	$json_do_script 		= $_POST['json_do_script'];
	$json_execucao_script	= $_POST['json_execucao_script'];
	$script_falha 			= $_POST['script_falha'];
	$produto				= $_POST['produto'];

	$json_do_script = str_replace('\n', '<br/>', $json_do_script);
	$json_execucao_script = str_replace('\n', '<br/>', $json_execucao_script);

	$xjson_do_script = utf8_encode($json_do_script);
	$xjson_do_script = json_decode($xjson_do_script);

	if(!count($xjson_do_script->cells)){
		$msg_erro["msg"][] = "Script de falha é obrigatório";
	}

	if (!in_array($login_fabrica, [175])) {
		if(strlen($familia)) {
			$sql = "SELECT familia FROM tbl_familia WHERE fabrica = {$login_fabrica} AND familia = {$familia}";
			$res = pg_query($con ,$sql);

			if (!pg_num_rows($res)) {
				$msg_erro["msg"][]    = "Familia não encontrada";
				$msg_erro["campos"][] = "familia";
			}
		} else {
			$msg_erro["campos"][] = "familia";
		}
	} else {

		if(strlen($linha)) {
			$sql = "SELECT produto,familia FROM tbl_linha
					JOIN tbl_produto USING(linha)
				    WHERE fabrica = {$login_fabrica} AND linha = {$linha}
				    LIMIT 1";
			$res = pg_query($con ,$sql);

			if (!pg_num_rows($res)) {
				$msg_erro["msg"][]    = "Linha/Produto não encontrado (a)";
				$msg_erro["campos"][] = "linha";
			} else {
				$produto_linha = pg_fetch_result($res, 0, 'produto');
				$familia       = pg_fetch_result($res, 0, 'familia');
			}
		} else {
			$msg_erro["campos"][] = "linha";
		}

	}

	if (!in_array($login_fabrica, [174])) {
		if(strlen($defeito_reclamado)){
			$sql = "SELECT 	tbl_defeito_reclamado.defeito_reclamado,
							tbl_defeito_reclamado.descricao
					FROM 	tbl_defeito_reclamado
					JOIN 	tbl_diagnostico ON tbl_diagnostico.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
				 		AND tbl_diagnostico.fabrica = {$login_fabrica}
					WHERE 	tbl_defeito_reclamado.fabrica = {$login_fabrica}
					AND 	tbl_defeito_reclamado.ativo IS TRUE
					AND 	tbl_defeito_reclamado.defeito_reclamado = {$defeito_reclamado}";
			$res = pg_query($con, $sql);
			if (!pg_num_rows($res)) {
				$msg_erro["msg"][]    = "Defeito Reclamado não encontrado";
				$msg_erro["campos"][] = "defeito_reclamado";
			}
		}else{
			$msg_erro["campos"][] = "defeito_reclamado";
		}
		if (!in_array($login_fabrica, [175])) {
			if(strlen(trim($defeito_reclamado == 0)) OR strlen(trim($familia)) == 0){
				$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
			}
		} else {
			if(strlen(trim($defeito_reclamado == 0)) OR strlen(trim($linha)) == 0){
				$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
			}
		}
	
	}

	if (!count($msg_erro["msg"])) {
		$condSql   = "";
		$fieldSql  = "";
		$valueSql  = "";
		$updateSql = "";

		if (in_array($login_fabrica, [174]) AND !empty($produto)) {
			$condSql   = " AND produto = {$produto} ";
			$fieldSql  = " , produto ";
			$valueSql  = " , {$produto} ";
			$updateSql = " , produto = {$produto} ";
		} elseif (in_array($login_fabrica, [174]) AND empty($produto)) {
			$updateSql = " , produto = null";
		} else {
			$condSql   = " AND defeito_reclamado = {$defeito_reclamado} ";
			$fieldSql  = " , defeito_reclamado ";
			$valueSql  = " , {$defeito_reclamado}  ";
			$updateSql = " , defeito_reclamado = {$defeito_reclamado} ";
		}

		if(strlen(trim($script_falha)) > 0){

			if (in_array($login_fabrica, [175])) {

				$condProdFamilia = ", produto = {$produto_linha}, familia = {$familia}";

				$sql = "SELECT script_falha
						FROM tbl_script_falha
						JOIN tbl_produto ON tbl_script_falha.produto = tbl_produto.produto
						AND tbl_produto.linha = {$linha}
						WHERE tbl_script_falha.fabrica = {$login_fabrica}
						{$condSql}";

			} else {

				$condProdFamilia = ", familia = {$familia}";

				$sql = "SELECT script_falha
						FROM tbl_script_falha
						WHERE fabrica = {$login_fabrica}
						AND familia = {$familia}
						$condSql";
			}

			
			$res = pg_query($con, $sql);

			if(pg_num_rows($res) > 0){
				$script_falha_existente = pg_fetch_result($res, 0, 'script_falha');

				if($script_falha != $script_falha_existente){
					$msg_erro["msg"][]    = (in_array($login_fabrica, [175])) ? "Já existe um Script de falha cadastrado para Linha/Defeito selecionado" : "Já existe um Script de falha cadastrado para Familia/Defeito selecionado";
				}

				if (!count($msg_erro["msg"])) {
					$sqlUp = "UPDATE 	tbl_script_falha
								SET 	json_script = '$json_do_script',
										json_execucao_script = '$json_execucao_script'
										{$condProdFamilia}
										$updateSql
								WHERE 	fabrica = {$login_fabrica}
								AND script_falha = {$script_falha} ";
					$resUp = pg_query($con, $sqlUp);

					if (pg_last_error()){
						$msg_erro["msg"][]    = "Erro ao atualizar Script de Falha.";
					}else{
						$msg_sucess = "Script atualizado com sucesso.";
					}
				}
			}else{
				$sqlUp = "UPDATE 	tbl_script_falha
							SET 	json_script = '$json_do_script',
									json_execucao_script = '$json_execucao_script'
									{$condProdFamilia}
									$updateSql
							WHERE 	fabrica = {$login_fabrica}
							AND script_falha = {$script_falha} ";
				$resUp = pg_query($con, $sqlUp);

				if (pg_last_error()){
					$msg_erro["msg"][]    = "Erro ao atualizar Script de Falha.";
				}else{
					$msg_sucess = "Script atualizado com sucesso.";
				}
			}
		}else{

			if (in_array($login_fabrica, [175])) {

				$campoProdFamilia = ", produto, familia";
				$valorProdFamilia = ", {$produto_linha}, {$familia}";

				$sql = "SELECT script_falha
						FROM tbl_script_falha
						JOIN tbl_produto ON tbl_script_falha.produto = tbl_produto.produto
						AND tbl_produto.linha = {$linha}
						WHERE tbl_script_falha.fabrica = {$login_fabrica}
						{$condSql}";

			} else {

				$campoProdFamilia = ", familia";
				$valorProdFamilia = ", {$familia}";

				$sql = "SELECT script_falha
						FROM tbl_script_falha
						WHERE fabrica = {$login_fabrica}
						AND familia = {$familia}
						$condSql";
			}

			$res = pg_query($con, $sql);

			if(pg_num_rows($res) == 0){
				$sql = "INSERT INTO tbl_script_falha (
								fabrica,
								json_script,
								json_execucao_script
								$fieldSql
								$campoProdFamilia
							)VALUES(
								$login_fabrica,
								'$json_do_script', 
								'$json_execucao_script'
								$valueSql
								$valorProdFamilia
							)RETURNING script_falha";

				$res = pg_query($con, $sql);
				$script_falha = pg_result($res,0,script_falha);
				if(pg_last_error()) {
		        	$msg_erro["msg"][] = "Erro ao cadastrar Script falha";
		        }else{
		        	//unset($script_falha);
		        	$msg_sucess = "Script gravado com sucesso";
		        }
			}else{
				$msg_erro["msg"][] = "Já existe um script de falha para essa Familia e Defeito Reclamado";
			}
		}
	}
}
//if ($_POST['btn_acao'] == "pesquisar" OR (strlen(trim($get_familia)) > 0 AND strlen(trim($get_defeito)) > 0)) {

if (
	(
		!in_array($login_fabrica, [174])
		AND strlen(trim($get_familia)) > 0 
		AND strlen(trim($get_defeito)) > 0
	)
	OR
	(
		in_array($login_fabrica, [174])
		AND strlen(trim($get_familia)) > 0
	)
	OR
	(
		in_array($login_fabrica, [174])
		AND strlen(trim($get_familia)) > 0
		AND strlen(trim($get_produto)) > 0
	)
) {

	if(strlen(trim($get_familia)) > 0){
		$familia = $get_familia;
	}else{
		$familia = $_POST['familia'];
	}

	if(strlen(trim($get_linha)) > 0){
		$linha = $get_linha;
	}else{
		$linha = $_POST['linha'];
	}

	if(strlen(trim($get_defeito)) > 0){
		$defeito_reclamado = $get_defeito;
		$condSql = " AND tbl_script_falha.defeito_reclamado = {$defeito_reclamado} ";
	}else{
		$defeito_reclamado = $_POST['defeito_reclamado'];
	}

	if (strlen(trim($get_produto))) {
		$produto = $get_produto;
		$condSql = " AND tbl_script_falha.produto = {$produto} ";
	} else {
		$produto = $_POST['produto'];
	}

	if (strlen(trim($get_familia)) > 0 AND in_array($login_fabrica, [174]) AND strlen(trim($get_produto)) == 0) {
		$condSql = " AND tbl_produto.produto IS NULL ";
	}

	if (in_array($login_fabrica, [175])) {
		if(strlen($linha)) {
			$sql = "SELECT produto,familia FROM tbl_linha
					JOIN tbl_produto USING(linha)
				    WHERE fabrica = {$login_fabrica} AND linha = {$linha}
				    LIMIT 1";
			$res = pg_query($con ,$sql);

			if (!pg_num_rows($res)) {
				$msg_erro["msg"][]    = "Linha/Produto não encontrado (a)";
				$msg_erro["campos"][] = "linha";
			} else {
				$produto_linha = pg_fetch_result($res, 0, 'produto');
				$familia       = pg_fetch_result($res, 0, 'familia');
			}
		} else {
			$msg_erro["campos"][] = "linha";
		}
	} else {
		if(strlen($familia)) {
			$sql = "SELECT familia FROM tbl_familia WHERE fabrica = {$login_fabrica} AND familia = {$familia}";
			$res = pg_query($con ,$sql);

			if (!pg_num_rows($res)) {
				$msg_erro["msg"][]    = "Familia não encontrada";
				$msg_erro["campos"][] = "familia";
			}
		}else{
			$msg_erro["campos"][] = "familia";
		}
	}

	if (!in_array($login_fabrica, [174])) {
		if(strlen($defeito_reclamado)){
			$sql = "SELECT 	tbl_defeito_reclamado.defeito_reclamado,
							tbl_defeito_reclamado.descricao
					FROM 	tbl_defeito_reclamado
					JOIN 	tbl_diagnostico ON tbl_diagnostico.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
				 		AND tbl_diagnostico.fabrica = {$login_fabrica}
					WHERE 	tbl_defeito_reclamado.fabrica = {$login_fabrica}
					AND 	tbl_defeito_reclamado.ativo IS TRUE
					AND 	tbl_defeito_reclamado.defeito_reclamado = {$defeito_reclamado}";
			$res = pg_query($con, $sql);
			if (!pg_num_rows($res)) {
				$msg_erro["msg"][]    = "Defeito Reclamado não encontrado";
				$msg_erro["campos"][] = "defeito_reclamado";
			}
		}else{
			$msg_erro["campos"][] = "defeito_reclamado";
		}
	} elseif (!empty($produto)) {
		if (strlen($produto)) {
			$sql = "SELECT 	tbl_produto.produto,
							tbl_produto.descricao,
							tbl_produto.referencia
					FROM tbl_produto
					WHERE tbl_produto.fabrica_i = {$login_fabrica}
					AND tbl_produto.produto = {$produto}";
			$res = pg_query($con, $sql);
			if (!pg_num_rows($res)) {
				$msg_erro['msg'][] = "Produto não encontrado";
				$msg_erro['campos'][] = "produto";
			}
		} else {
			$msg_erro['campos'][] = "produto";
		}
	}

	if (!in_array($login_fabrica, [174])) {

		if (!in_array($login_fabrica, [175])) {
			if(strlen(trim($defeito_reclamado == 0)) OR strlen(trim($familia)) == 0){
				$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
			}
		} else {
			if(strlen(trim($defeito_reclamado == 0)) OR strlen(trim($linha)) == 0){
				$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
			}
		}

	} else {
		if(strlen(trim($familia)) == 0 AND strlen(trim($produto)) == 0) {
			$msg_erro['msg'][] = "Preencha os campos obrigatórios.";
		}
	}

	if (!count($msg_erro["msg"])) {
		$sql = "SELECT 	tbl_script_falha.script_falha,
						tbl_script_falha.defeito_reclamado,
						tbl_script_falha.familia,
						tbl_script_falha.json_script,
						tbl_script_falha.json_execucao_script,
						tbl_produto.linha
				FROM tbl_script_falha
				LEFT JOIN tbl_produto ON tbl_script_falha.produto = tbl_produto.produto
				WHERE tbl_script_falha.fabrica = {$login_fabrica}
				AND tbl_script_falha.familia = {$familia}
				$condSql";
		$res = pg_query($con, $sql);
		if(pg_last_error()) {
            $msg_erro["msg"][] = "Erro ao buscar Script de falha.";
        }

        if (pg_num_rows($res) > 0){
        	$script_falha 			= pg_fetch_result($res, 0, 'script_falha');
        	$defeito_reclamado 		= pg_fetch_result($res, 0, 'defeito_reclamado');
        	$familia 				= pg_fetch_result($res, 0, 'familia');
        	$linha 				    = pg_fetch_result($res, 0, 'linha');
        	$json_do_script 		= pg_fetch_result($res, 0, 'json_script');
        	$json_execucao_script 	= pg_fetch_result($res, 0, 'json_execucao_script');

        	//$json_do_script = str_replace("<br/>", '\n', $json_do_script);
        	//$json_execucao_script = str_replace("<br/>", '\n', $json_execucao_script);

        }else{
        	$script_falha = "";
        	$msg_info = "Nenhum resultado encontrado.";
        }
    }
}

if ($_POST['btn_acao'] == "deletar") {
	$familia 			= $_POST['familia'];
	$defeito_reclamado 	= $_POST['defeito_reclamado'];
	$xscript_falha 		= $_POST['script_falha'];
	$produto 			= $_POST['produto'];
	$linha              = $_POST['linha'];

	if (in_array($login_fabrica, [175])) {
		$sql = "SELECT produto, familia FROM tbl_linha
				JOIN tbl_produto USING(linha)
			    WHERE fabrica = {$login_fabrica} AND linha = {$linha}
			    LIMIT 1";
		$res = pg_query($con ,$sql);

		$produto = pg_fetch_result($res, 0, 'produto');
		$familia = pg_fetch_result($res, 0, 'familia');

	}

	if (!in_array($login_fabrica, [174])) {
		$fieldSql = " , tbl_script_falha.defeito_reclamado ";
		$condSql  = " AND tbl_script_falha.defeito_reclamado = {$defeito_reclamado} ";
	} elseif (in_array($login_fabrica, [174,175]) AND  !empty($produto)) {
		$fieldSql = " , tbl_script_falha.produto ";
		$condSql  = " AND tbl_script_falha.produto = {$produto} ";
	}

	$sql = "SELECT 	tbl_script_falha.script_falha,
					tbl_script_falha.familia,
					tbl_script_falha.json_script,
					tbl_script_falha.json_execucao_script
					$fieldSql
			FROM tbl_script_falha
			WHERE tbl_script_falha.fabrica = {$login_fabrica}
			AND tbl_script_falha.familia = {$familia}
			$condSql
			AND tbl_script_falha.script_falha = {$xscript_falha}";
	$res = pg_query($con, $sql);

	if(pg_last_error()) {
        $msg_erro["msg"][] = "Erro ao buscar Script de falha.";
    }

	if (!count($msg_erro["msg"])) {
		if(pg_num_rows($res) > 0){
			$sqlDelete = "DELETE FROM tbl_script_falha WHERE fabrica = {$login_fabrica} AND script_falha = {$xscript_falha}";
			$resDelete = pg_query($con, $sqlDelete);

			if(pg_last_error()) {
		        $msg_erro["msg"][] = "Erro ao excluir Script de falha.";
		    }else{
		    	unset($script_falha);
		    	$msg_sucess = "Script excluido com sucesso";
		    }

		}else{
			$msg_erro["msg"][] = "Erro ao excluir Script de falha.";
		}
	}
}

$layout_menu = "cadastro";
$title = "SCRIPT DE FALHA";
include 'cabecalho_new.php';

?>
<link rel="stylesheet" type="text/css" href="../plugins/rappid/build/rappid.min.css">
<link href="../plugins/rappid/css/header.css" rel="stylesheet"/>
<link href="../plugins/rappid/css/toolbar.css" rel="stylesheet"/>
<link href="../plugins/rappid/css/statusbar.css" rel="stylesheet"/>
<link href="../plugins/rappid/css/paper.css" rel="stylesheet"/>
<link href="../plugins/rappid/css/preview.css" rel="stylesheet"/>
<link href="../plugins/rappid/css/tooltip.css" rel="stylesheet"/>
<link href="../plugins/rappid/css/snippet.css" rel="stylesheet"/>
<link href="../plugins/rappid/css/dialog.css" rel="stylesheet"/>
<link href="../plugins/rappid/css/index.css" rel="stylesheet"/>

<script type="text/javascript">
	$(function () {
		$("select").select2();
		
		var hora = new Date();
		var engana = hora.getTime();
	});
</script>

<?php if (!in_array($login_fabrica, [174])) { ?>
<script type="text/javascript">
	// SVGElement.prototype.getTransformToElement = SVGElement.prototype.getTransformToElement || function (toElement) {
 //    	return toElement.getScreenCTM().inverse().multiply(this.getScreenCTM());
 //  	};
	$(function() {
		$("#familia").change(function(){
			var familia = $(this).val();
			if(familia.length > 0){
				var defeito_reclamado_id = $("#defeito_reclamado_id").val();
				$.ajax({
		            url: window.location,
		            type: "POST",
		            data: {ajax: 'sim', familia: familia},
		            timeout: 7000
		        }).fail(function(){
		            alert('fail');
		        }).done(function(data){
		            console.log(data);

		            data = JSON.parse(data);
		            if (data.ok !== undefined) {
		                var option = "<option value=''>Escolha o Defeito Reclamado</option>";
		                $.each(data.ok, function (key, value) {
		                	if(value.defeito_reclamado == defeito_reclamado_id){
		                       var selecionar = "selected";
		                    }
		                    option += "<option value='"+value.defeito_reclamado+"' "+selecionar+" >"+value.descricao+"</option>";
		                });

		                $('#defeito_reclamado').html(option);
		            }
		        });
		   	}else{
		   		$("#defeito_reclamado").html("<option>Selecione</option>");
		   	}
		});

		$("#linha").change(function(){
			var linha = $(this).val();
			if(linha.length > 0){
				var defeito_reclamado_id = $("#defeito_reclamado_id").val();
				$.ajax({
		            url: window.location,
		            type: "POST",
		            data: {ajax: 'sim', linha: linha},
		            timeout: 7000
		        }).fail(function(){
		            alert('fail');
		        }).done(function(data){
		            console.log(data);

		            data = JSON.parse(data);
		            if (data.ok !== undefined) {
		                var option = "<option value=''>Escolha o Defeito Reclamado</option>";
		                $.each(data.ok, function (key, value) {
		                	if(value.defeito_reclamado == defeito_reclamado_id){
		                       var selecionar = "selected";
		                    }
		                    option += "<option value='"+value.defeito_reclamado+"' "+selecionar+" >"+value.descricao+"</option>";
		                });

		                $('#defeito_reclamado').html(option);
		            }
		        });
		   	}else{
		   		$("#defeito_reclamado").html("<option>Selecione</option>");
		   	}
		});

		$("#linha").change();

		$("#defeito_reclamado").change(function(){
			var defeito_reclamado_id = $(this).val();
			$("#defeito_reclamado_id").val(defeito_reclamado_id);
		});

	});
</script>
<?php } else { ?>
<script type="text/javascript">
	$(function () {
		$("#familia").on("change", function () {
			$("#produto").html("<option value=''>Selecione</option>");
			var familia = $(this).val();

			if (familia.length > 0) {
				$.ajax('cadastro_script_falha.php', {
					async: true,
					type: 'POST',
					data: {
						searchProd: true,
						familia: familia
					}
				}).done(function (response) {
					response = JSON.parse(response);
					$(response).each(function (index, element) {
						var option = $("<option></option>");
						$(option).val(element.produto);
						$(option).text(element.descricao);

						$("#produto").append(option);
					});
				});
			}
		});
	});
</script>
<?php } ?>

<style type="text/css">
	.btn {
		background-image: none;
	}
	text{
		font-size: 14px;
	}
	.container_script {
	    width: 1024px;
		margin: 0 auto;
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
if(strlen(trim($msg_sucess)) > 0){
?>
	<div class="alert alert-success">
		<h4><?=$msg_sucess?></h4>
    </div>
<?php
}
if(strlen(trim($msg_info)) > 0){
?>
	<div class="alert">
	    <h4><?=$msg_info?></h4>
	</div>
<?php
}
?>


<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='form_script' id="form_script" METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
		<input type="hidden" name="script_falha" id="script_falha" value="<?=$script_falha?>">
		<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
		<br/>

		<!-- FAMILIA/DEFEITOS -->
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
			<?php
				if (in_array($login_fabrica, [175])) { ?>
					<div class='control-group <?=(in_array("linha", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='linha'>Linha</label>
						<div class='controls controls-row'>
							<div class='span4'>
								<h5 class='asteristico'>*</h5>
								<select name="linha" id="linha">
									<option value="">Selecione</option>
									<?php
										$sql = "SELECT linha, nome FROM tbl_linha WHERE fabrica = $login_fabrica and ativo order by nome";
										$res = pg_query($con,$sql);
										foreach(pg_fetch_all($res) as $key) {
											$selected_linha = ( isset($linha) and ($linha == $key['linha']) ) ? "SELECTED" : '' ;
										?>
											<option value="<?php echo $key['linha']?>" <?php echo $selected_linha ?> >
												<?php echo $key['nome']?>
											</option>
										<?php
										}
									?>
								</select>
							</div>
							<div class='span2'></div>
						</div>
					</div>
				<?php
				} else { ?>
					
						<div class='control-group <?=(in_array("familia", $msg_erro["campos"])) ? "error" : ""?>'>
							<label class='control-label' for='familia'>Familia</label>
							<div class='controls controls-row'>
								<div class='span4'>
									<h5 class='asteristico'>*</h5>
									<select name="familia" id="familia">
										<option value="">Selecione</option>
										<?php
											$sql = "SELECT familia, descricao FROM tbl_familia WHERE fabrica = $login_fabrica and ativo order by descricao";
											$res = pg_query($con,$sql);
											foreach(pg_fetch_all($res) as $key) {
												$selected_familia = ( isset($familia) and ($familia == $key['familia']) ) ? "SELECTED" : '' ;
											?>
												<option value="<?php echo $key['familia']?>" <?php echo $selected_familia ?> >
													<?php echo $key['descricao']?>
												</option>
											<?php
											}
										?>
									</select>
								</div>
								<div class='span2'></div>
							</div>
						</div>

				<?php 
				} ?>
			</div>
			<?php
			if (!in_array($login_fabrica, [174])) { ?>
			<div class='span4'>
				<div class='control-group <?=(in_array("defeito_reclamado", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='defeito_reclamado'>Defeito Reclamado</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
							<select name="defeito_reclamado" id="defeito_reclamado">
								<option value="">Selecione</option>
								<?php
									if(strlen(trim($familia)) > 0){

										if (in_array($login_fabrica, [175])) {
											$condLinhaFamilia = "AND tbl_diagnostico.linha = {$linha}";
										} else {
											$condLinhaFamilia = "AND tbl_diagnostico.familia = {$familia}";
										}

										$sql = "SELECT 	tbl_defeito_reclamado.defeito_reclamado,
														tbl_defeito_reclamado.descricao
												FROM 	tbl_defeito_reclamado
												JOIN 	tbl_diagnostico ON tbl_diagnostico.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
													AND tbl_diagnostico.fabrica = {$login_fabrica}
												WHERE tbl_defeito_reclamado.fabrica = {$login_fabrica}
												AND tbl_defeito_reclamado.ativo IS TRUE
												AND tbl_diagnostico.familia = {$familia}";
										$res = pg_query($con,$sql);
										foreach(pg_fetch_all($res) as $key) {
											$selected_defeito_reclamado = ( isset($defeito_reclamado) and ($defeito_reclamado == $key['defeito_reclamado']) ) ? "SELECTED" : '' ;
										?>
											<option value="<?php echo $key['defeito_reclamado']?>" <?php echo $selected_defeito_reclamado ?> >
												<?php echo $key['descricao']?>
											</option>
										<?php
										}
									}
									?>
							</select>
						</div>
						<input type="hidden" name="defeito_reclamado_id" id="defeito_reclamado_id" value="<?=$defeito_reclamado?>">
					</div>
				</div>
			</div>
			<?php } else { ?>
			<div class="span4">
				<div class="control-group <?=(in_array('produto', $msg_erro['campos'])) ? 'error' : ''?>'">
					<label class="control-label" for="produto">Produto</label>
					<div class="controls controls-row">
						<div class="span4">
							<select name="produto" id="produto">
								<option value="">Selecione</option>
								<?php
								if (strlen(trim($familia)) > 0) {
									$sql = "SELECT tbl_produto.produto,
												   tbl_produto.descricao,
												   tbl_produto.referencia
											FROM tbl_produto
											WHERE tbl_produto.fabrica_i = {$login_fabrica}
											AND tbl_produto.familia = {$familia}";
									$res = pg_query($con, $sql);
									foreach (pg_fetch_all($res) as $prod) {
										$selected_produto = (isset($produto) AND $produto == $prod['produto']) ? "SELECTED" : "";
									?>
										<option value="<?= $prod['produto'] ?>" <?= $selected_produto ?>>
											<?= $prod['descricao'] ?>
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
			<?php } ?>
			<div class='span2'></div>
		</div>
		<input type="hidden" name="json_do_script" id="json_do_script" value="">
		<input type="hidden" name="json_execucao_script" id="json_execucao_script" value="">

		<p><br/>
			<input type='hidden' name='btn_acao' id="btn_acao" value=''>
			<?php
				if(strlen(trim($script_falha)) > 0){
					$display_cancelar = "";
				}else{
					$display_cancelar = "style='display:none;'";
				}
			?>
			<button type='button' class='btn' onclick="submitForm();">Gravar</button>
			<button type='button' class='btn btn-danger' <?=$display_cancelar?> onclick="deletar();">Excluir Script</button>
			<button type='button' class='btn btn-primary' onclick="limpar();">Novo Script</button>
			<!--
			<button type='button' class='btn btn-info' onclick="pesquisar();">Pesquisar</button>
			<button type='button' class='btn btn-warning' onclick="limpar();">Limpar dados</button>
			-->

		</p><br/>
</form>
</div>
<div class='container-fluid'>
<div class='container_script'>
<section id="app">
	<div id="toolbar">
    	<button class="btn add-question">Adicionar Pergunta</button>
    	<button class="btn add-answer">Adicionar Instrução</button>
    	<button class="btn preview-dialog">Executar Script</button>
    	<!--
    	<button class="btn execution-script-json">Json de execução do script</button>
    	<button class="btn script-json">Json do script</button>
		-->
	</div>
  	<div id="paper"></div>
  	<div id="preview" class="preview">
	</div>
</section>
</div>
</div>

<!-- Rappid/JointJS dependencies: -->
	<!--
		/* FAVOR NÃO REMOVER O ARQUIVO plugins/rappid/node_modules/jquery/dist/jquery.js
		 * O MESMO CARREGA A VERSÃO 3.1 DO JQUERY
		 * NECESSARIA PARA UTILIZAÇÃO DO PLUGIN
		 * RAPPID.JS
		*/
	-->
    <script src="../plugins/rappid/node_modules/jquery/dist/jquery.js"></script>
    <script src="../plugins/rappid/node_modules/lodash/index.js"></script>
    <script src="../plugins/rappid/node_modules/backbone/backbone.js"></script>

    <script src="../plugins/rappid/build/rappid.min.js"></script>

    <script src="../plugins/rappid/src/joint.shapes.qad.js"></script>
    <script src="../plugins/rappid/src/selection.js"></script>
    <script src="../plugins/rappid/src/factory.js"></script>
    <script src="../plugins/rappid/src/snippet.js"></script>
    <script src="../plugins/rappid/src/app.js"></script>
    <script src="../plugins/rappid/src/index.js"></script>
    <script>joint.setTheme('modern');</script>
    <?php
    	$plugins = array(
			"mask",
			"select2"
		);
    	include("plugin_loader.php");
    ?>
     <script type="text/javascript">
    	var app = app || {};

		window.appView = new app.AppView;

		$(document).on( "click", ".preview-dialog", function() {
		  appView.previewDialog();
		});

		$(function() {
			<?php if ($get_duplicar == true) { ?>
				$("#script_falha").val('');
				$("#familia").prop('selectedIndex',-1);
				$("#defeito_reclamado").prop('selectedIndex',-1);
			<?php } ?>

			$("select").select2();
		});

		function submitForm(){
			var script_falha = $("#script_falha").val();

			if(script_falha.length > 0){
				if (!confirm('Deseja alterar o Script de falha ?')) {
					return false;
				}
			}
			var script_json_value = appView.getScriptJson();
			var execution_script_json_value = appView.getExecutionScriptJson();

			$("#btn_acao").val("gravar");
			$("#json_do_script").val(script_json_value);
			$("#json_execucao_script").val(execution_script_json_value);
			$("#form_script").submit();
		}

		function preview_teste (){
			appView.previewDialog();
		}

		function pesquisar(){
			$("#btn_acao").val("pesquisar");
			$("#form_script").submit();
		}

		function deletar(){
			if (!confirm('Deseja excluir o Script de falha ?')) {
				return false;
			}else{
				$("#btn_acao").val("deletar");
				$("#form_script").submit();
			}
		}

		function limpar(){
			window.location='<?=$PHP_SELF?>';
		}


		<?php if(strlen(trim($json_do_script)) > 0){ ?>
			var dados = '<?=$json_do_script?>';
			dados = dados.replace(/\<br\/\>/gi, '\\n');
  			appView.loadScriptFalha(dados);
		<?php } ?>
    </script>


<?php include 'rodape.php';?>

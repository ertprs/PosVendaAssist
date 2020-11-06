<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios = "cadastros";
include 'autentica_admin.php';
include_once "../helpdesk.inc.php";
include_once '../class/AuditorLog.php';

function existeAdminEstado($admin,$estado,$classificacao,$login_fabrica){
	global $con;
	$sql = "SELECT admin_atendente_estado FROM tbl_admin_atendente_estado WHERE admin = $admin AND estado = '$estado' AND fabrica = $login_fabrica AND cod_ibge is null";

	$res = pg_query($con, $sql);

	if(pg_num_rows($res)){
		return true;
	}else{
		return false;
	}
}

if (filter_input(INPUT_POST,"verProvidencia",FILTER_VALIDATE_BOOLEAN)) {
    $classificacao = filter_input(INPUT_POST,"classificacao");

    $sqlProv = "
                SELECT  tbl_hd_motivo_ligacao.hd_motivo_ligacao AS providencia,
                        tbl_hd_motivo_ligacao.descricao
                FROM    tbl_hd_motivo_ligacao
                JOIN    tbl_hd_classificacao USING(fabrica,hd_classificacao)
                WHERE   fabrica             = $login_fabrica
                AND     hd_classificacao    = $classificacao
          ORDER BY      tbl_hd_motivo_ligacao.descricao
                ";
    $resProv = pg_query($con,$sqlProv);

    $selected = "<option value=''>Selecione</option>";

    while ($providencia = pg_fetch_object($resProv)) {
        $selected .= "<option value='".$providencia->providencia."'>".$providencia->descricao."</option>";
    }

    echo $selected;
    exit;
}

if ($_POST["buscaCidade"] == "true") {
	$estado = strtoupper($_POST["estado"]);
	$arrayEstado = array();

	if (strlen($estado) > 0) {
		$sql = "SELECT cod_ibge, cidade FROM tbl_ibge WHERE UPPER(estado) = '{$estado}'";
		$res = pg_query($con, $sql);
		$rows = pg_num_rows($res);
		if ($rows > 0) {
			for ($i = 0; $i < $rows; $i++) {
				$xarrayEstados[] = array(
					"cod_ibge" => pg_fetch_result($res, $i, "cod_ibge"),
					"cidade"   => utf8_encode(pg_fetch_result($res, $i, "cidade"))
				);
			}
			echo json_encode($xarrayEstados);
		}
	}
	exit;
}

if ($_POST["apagarAtendente"] == "true") {
	$admin_atendente_estado = $_POST["admin_atendente_estado"];

	if (strlen($admin_atendente_estado) > 0) {

		$sql = "SELECT tbl_admin_atendente_estado.admin, tbl_admin_atendente_estado.estado, upper( fn_retira_especiais(tbl_ibge.cidade) ) as cidade, tbl_admin.nome_completo
				FROM tbl_admin_atendente_estado
				LEFT JOIN tbl_ibge ON tbl_admin_atendente_estado.cod_ibge = tbl_ibge.cod_ibge
				INNER JOIN tbl_admin ON tbl_admin.admin = tbl_admin_atendente_estado.admin
				WHERE admin_atendente_estado = {$admin_atendente_estado}
				and tbl_admin_atendente_estado.fabrica = {$login_fabrica}";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {

			$admin_a    = pg_fetch_result($res, 0, "admin");
			$estado   = strtoupper(pg_fetch_result($res, 0, "estado"));
			$cidade = pg_fetch_result($res, 0, "cidade");
			$nome_completo_anterior = pg_fetch_result($res, 0, "nome_completo");

			pg_query($con,'BEGIN');

			$auditorLog = new AuditorLog();

			$ibge = "";
			$join_ibge = "";
			if ((!empty($cidade)) && ($cidade != 'null')) {
				$ibge = ", tbl_ibge.cidade";
				$join_ibge = "JOIN tbl_ibge ON tbl_admin_atendente_estado.cod_ibge = tbl_ibge.cod_ibge";
			}
			$sqlLog = "SELECT tbl_admin_atendente_estado.admin_atendente_estado, tbl_admin.nome_completo AS Admin, tbl_admin_atendente_estado.estado $ibge
				FROM tbl_admin_atendente_estado
				JOIN tbl_admin ON tbl_admin_atendente_estado.admin = tbl_admin.admin
				$join_ibge
				WHERE tbl_admin_atendente_estado.fabrica = $login_fabrica
				AND tbl_admin_atendente_estado.admin_atendente_estado = $admin_atendente_estado";
			$auditorLog->retornaDadosSelect($sqlLog);

			$sql = "DELETE FROM tbl_admin_atendente_estado
					WHERE admin_atendente_estado = {$admin_atendente_estado}
					AND fabrica = {$login_fabrica}";
			$res = pg_query($con, $sql);

			$auditorLog->retornaDadosSelect()->enviarLog('delete', 'tbl_admin_atendente_estado', $login_fabrica);

			if ($login_fabrica == 1) {

				if (strlen($estado)> 0) {
					$cond_uf = "AND tbl_posto_fabrica.contato_estado = '$estado'";
				}
				if (strlen($cidade) > 0) {
					$cond_cidade = "AND tbl_posto_fabrica.contato_cidade = '$cidade' ";
				}

				$sql_d = "SELECT DISTINCT tbl_hd_chamado.categoria,
										  tbl_hd_chamado.posto,
										  tbl_hd_chamado.hd_chamado,
										  tbl_hd_chamado.status
							FROM tbl_hd_chamado
							JOIN tbl_posto_fabrica ON tbl_hd_chamado.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
							WHERE tbl_hd_chamado.fabrica = $login_fabrica
								AND tbl_hd_chamado.atendente = $admin_a
								$cond_uf
								$cond_cidade
								AND status not in ('Resolvido','Resolvido Posto','Cancelado')";
				$res_d = pg_query($con,$sql_d);

				if (pg_num_rows($res_d) > 0) {
					for ($i=0; $i < pg_num_rows($res_d) ; $i++) {
						$posto_u = pg_fetch_result($res_d, $i, posto);
						$hd_chamado_u = pg_fetch_result($res_d, $i, hd_chamado);
						$status_u = pg_fetch_result($res_d, $i, status);

						$atendente_u = $categorias[$categoria_u]['atendente'];
						$atendente_u = (is_numeric($atendente_u)) ? $atendente_u : hdBuscarAtendentePorPosto($posto_u,$categoria_u);

						//verificação se o atendente novo é igual o antigo, fazer o updade somente se for diferente.
						if($atendente_u != $admin_a){

							$sql_nome_novo_atendente = "select nome_completo from tbl_admin where admin = $atendente_u and fabrica = $login_fabrica";
							$res_nome_novo_atendente = pg_query($con, $sql_nome_novo_atendente);
							if(pg_num_rows($res_nome_novo_atendente) > 0 ){
								$nome_completo_novo = pg_fetch_result($res_nome_novo_atendente, 0, 'nome_completo');
							}

							$sql_u = "UPDATE tbl_hd_chamado SET
									atendente = {$atendente_u}
									WHERE atendente = {$admin_a}
										AND fabrica = {$login_fabrica}
										AND posto = {$posto_u}
										AND hd_chamado = {$hd_chamado_u}
										AND status not in ('Resolvido','Resolvido Posto','Cancelado');";
							$res_u = pg_query($con,$sql_u);

							if(strlen(trim(pg_last_error($con)))==0){
								$frase_transferencia = "Chamado transferido automaticamente: de ". $nome_completo_anterior ." para ". $nome_completo_novo ." <br>Atendente anterior excluído!";
							}

							$hd_chamado_item_u = hdCadastrarResposta($hd_chamado_u, $frase_transferencia,true, $status_u, $login_admin);

						}
					}
				}
			}

			if (strlen(pg_last_error()) > 0) {
				$msg_retorno = "erro";
				pg_query($con,'ROLLBACK');
			}else{
				pg_query($con,'COMMIT');
			}
		} else {
			$msg_retorno = "erro";
		}
	} else {
		$msg_retorno = "erro";
	}

	echo $msg_retorno;
	exit;
}

$arrayEstados = array("AC" => "Acre",			"AL" => "Alagoas",			"AM" => "Amazonas",
				 "AP" => "Amapá",			"BA" => "Bahia",			"CE" => "Ceará",
				 "DF" => "Distrito Federal","ES" => "Espírito Santo",	"GO" => "Goiás",
				 "MA" => "Maranhão",		"MG" => "Minas Gerais",		"MS" => "Mato Grosso do Sul",
				 "MT" => "Mato Grosso",		"PA" => "Pará",				"PB" => "Paraíba",
				 "PE" => "Pernambuco",		"PI" => "Piauí",			"PR" => "Paraná",
				 "RJ" => "Rio de Janeiro",	"RN" => "Rio Grande do Norte","RO"=>"Rondônia",
				 "RR" => "Roraima",			"RS" => "Rio Grande do Sul","SC" => "Santa Catarina",
				 "SE" => "Sergipe",			"SP" => "São Paulo",		"TO" => "Tocantins");


if(in_array($login_fabrica, array(30))){
	$sql = "SELECT descricao, estados_regiao FROM tbl_regiao WHERE fabrica = 30 AND ativo IS TRUE;";
	$res = pg_query($con, $sql);

	if(!pg_last_error($con)){
		$arrayRegioes = pg_fetch_all($res);
	}
}

if ($_POST["btn_acao"] == "submit") {

	$admin_atendente_estado = $_POST["admin_atendente_estado"];
	$atendente              = $_POST["atendente"];
	$estado                 = strtoupper($_POST["estado"]);
	$cidade                 = (!strlen($_POST["cidade"])) ? "null" : $_POST["cidade"];
    $providencia            = filter_input(INPUT_POST,"providencia");

    if (empty($providencia)) {
        $providencia = 'NULL';
    }

	if (in_array($login_fabrica, array(30))) {
		if ($_POST['regiao']) {
			$regiao = explode(",", $_POST['regiao']);
		} else {
			$regiao = null;
		}
	}

	if (in_array($login_fabrica,array(30,151)) OR $moduloProvidencia OR $classificacaoHD) {
		$classificacao		= $_POST['classificacao'];

		if (empty($classificacao)) {
			$msg_erro['msg']['obg'] = 'Preencha os campos obrigatórios';
			$msg_erro['campos'][]   = 'classificacao';
		}
	}

	if (empty($atendente)) {
		$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][]   = "atendente";
	}

	if (!strlen($estado) && !in_array($login_fabrica,array(30,151)) && !$moduloProvidencia) {
		$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][]   = "estado";
	}

	if (count($msg_erro) == 0) {
		if ($cidade == "null") {
			$sqlWhere = " AND cod_ibge IS NULL ";
		} else {
			$sqlWhere = " AND cod_ibge = {$cidade} ";
		}

		pg_query($con, "BEGIN");

		$auditorLog = new AuditorLog;

		if (empty($admin_atendente_estado)) {
			$updateEstado = false;
			if (in_array($login_fabrica, array(30))) {
				if ($regiao != null) {
					foreach ($regiao as $value) {
						$value = trim($value);

						if ($cidade == "null") {
							if (existeAdminEstado($atendente,$value,$classificacao,$login_fabrica)) {
								$msg_erro["msg"][] = "Atendente e Estado $value já cadastrados";
							}
						}

						$sql = "INSERT INTO tbl_admin_atendente_estado
								(admin, estado, cod_ibge, fabrica, hd_classificacao)
								VALUES
								({$atendente}, '{$value}', {$cidade}, {$login_fabrica}, {$classificacao})";

						$res = pg_query($con, $sql);
						if (pg_last_error()) {
							if (strlen(pg_last_error()) > 0) {
								$msg_erro["msg"][] = "Erro ao gravar as informações do atendente";
							}
							$rollback = pg_query($con, "ROLLBACK");

						}
					}
				} else {

					if ($cidade == "null") {
						if(existeAdminEstado($atendente,$estado,$classificacao,$login_fabrica)){
							$msg_erro["msg"][] = "Atendente e Estado $estado já cadastrados";
						}
					}

					$sql = "INSERT INTO tbl_admin_atendente_estado
						(admin, estado, cod_ibge, fabrica, hd_classificacao)
						VALUES
						({$atendente}, '{$estado}', {$cidade}, {$login_fabrica}, {$classificacao})";

					$res = pg_query($con, $sql);
					if (pg_last_error()) {
						$rollback = pg_query($con, "ROLLBACK");
						if (strlen(pg_last_error()) > 0) {
							$msg_erro["msg"][] = "Erro ao gravar as informações do atendente";
						}

					}
				}
			} else if (in_array($login_fabrica,array(151)) OR $moduloProvidencia OR $classificacaoHD) {
				$sql = "INSERT INTO tbl_admin_atendente_estado
						(admin, estado, cod_ibge, fabrica, hd_classificacao,hd_motivo_ligacao)
						VALUES
						({$atendente}, '{$estado}', {$cidade}, {$login_fabrica}, {$classificacao},$providencia)";

			} else {

				$sql = "INSERT INTO tbl_admin_atendente_estado
						(admin, estado, cod_ibge, fabrica)
						VALUES
						({$atendente}, '{$estado}', {$cidade}, {$login_fabrica})";

			}
		} else {
			$updateEstado = true;
			if (in_array($login_fabrica, array(1))) {
				$sql_valida = "	SELECT estado,cod_ibge
									FROM tbl_admin_atendente_estado
									WHERE admin_atendente_estado = {$admin_atendente_estado}
										AND fabrica = {$login_fabrica}
										AND estado   = '{$estado}'
										$sqlWhere;";
				$res_valida = pg_query($con,$sql_valida);

				if (pg_num_rows($res_valida) > 0) {
					if ($login_fabrica == 151 OR $moduloProvidencia OR $classificacaoHD) {

						$sql = "UPDATE tbl_admin_atendente_estado
								SET
									admin    = {$atendente},
									estado   = '{$estado}',
									cod_ibge = {$cidade},
									hd_classificacao = $classificacao
								WHERE admin_atendente_estado = {$admin_atendente_estado}
								AND fabrica = {$login_fabrica}";
					} else if ($login_fabrica == 1) {
						$sql_at = "	SELECT admin,tbl_admin_atendente_estado.estado,tbl_ibge.cod_ibge,cidade
	                                    FROM tbl_admin_atendente_estado
	                                        LEFT JOIN tbl_ibge ON tbl_admin_atendente_estado.cod_ibge = tbl_ibge.cod_ibge
										WHERE admin_atendente_estado = {$admin_atendente_estado}
											AND fabrica = {$login_fabrica};";
						$res_at = pg_query($con,$sql_at);

						if (pg_num_rows($res_at) > 0) {
							$atendente_atual = pg_fetch_result($res_at, 0, admin);
		                    $estado_atual = pg_fetch_result($res_at, 0, estado);
		                    $cidade_atual = pg_fetch_result($res_at, 0, cidade);
		                    if (strlen($cidade_atual) > 0) {
	                            $cond_ibge = " AND UPPER( fn_retira_especiais(tbl_posto_fabrica.contato_cidade)) = UPPER( fn_retira_especiais('{$cidade_atual}')) ";
	                        }
							if ($atendente_atual != $atendente) {
								$sql_up = " UPDATE tbl_hd_chamado
	                                            SET
	                                                atendente = {$atendente}
	                                        WHERE fabrica = {$login_fabrica}
	                                            AND atendente = $atendente_atual
	                                            AND status ilike 'Ag.%'
	                                            AND hd_chamado in (
	                                                SELECT DISTINCT tbl_hd_chamado.hd_chamado
	                                                    FROM tbl_hd_chamado
	                                                        JOIN tbl_posto_fabrica ON tbl_hd_chamado.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = {$login_fabrica}
	                                                    WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
	                                                        AND contato_estado = '{$estado_atual}'
	                                                        $cond_ibge
	                                                );";
								$res_up = pg_query($con,$sql_up);
							}
						}
						if (strlen(pg_last_error($con)) > 0) {
							$msg_erro["msg"]["obg"] = "Erro ao Atualizar Atendente nos Chamados !";
						}

						$sql = "UPDATE tbl_admin_atendente_estado
									SET
										admin    = {$atendente},
										estado   = '{$estado}',
										cod_ibge = {$cidade}
									WHERE admin_atendente_estado = {$admin_atendente_estado}
									AND fabrica = {$login_fabrica}";

					} else {

						$sql = "UPDATE tbl_admin_atendente_estado
								SET
									admin    = {$atendente},
									estado   = '{$estado}',
									cod_ibge = {$cidade}
								WHERE admin_atendente_estado = {$admin_atendente_estado}
								AND fabrica = {$login_fabrica}";

					}
				} else {
					$msg_erro["msg"]["obg"] = "Só é possível alterar o Atendente";
				}
			} else if (in_array($login_fabrica, array(151)) || $moduloProvidencia || $classificacaoHD) {
				$sql = "UPDATE  tbl_admin_atendente_estado
                        SET     admin               = {$atendente},
                                estado              = '{$estado}',
                                cod_ibge            = {$cidade},
                                hd_classificacao    = $classificacao,
                                hd_motivo_ligacao   = $providencia
                        WHERE   admin_atendente_estado  = {$admin_atendente_estado}
                        AND     fabrica                 = {$login_fabrica}";
			} else {
				$sql = "UPDATE tbl_admin_atendente_estado
						SET
							admin    = {$atendente},
							estado   = '{$estado}',
							cod_ibge = {$cidade}
						WHERE admin_atendente_estado = {$admin_atendente_estado}
						AND fabrica = {$login_fabrica}";

			}
		}

		if (!count($msg_erro["msg"])) {
			//Para fabricas que não possuem combo de regiões
			if(!in_array($login_fabrica, array(30)) || $updateEstado == true){
				$ibge = "";
				$join_ibge = "";
				$x_admin_atendente_estado = "null";
				$acao = substr(trim($sql), 0, 1);
				if (strtoupper($acao) == 'I') {
					if ((!empty($cidade)) && ($cidade != 'null')) {
						$ibge = ", tbl_ibge.cidade";
						$join_ibge = "JOIN tbl_ibge ON tbl_admin_atendente_estado.cod_ibge = tbl_ibge.cod_ibge";
					}
					$sqlLog = "SELECT tbl_admin_atendente_estado.admin_atendente_estado, tbl_admin.nome_completo AS Admin, tbl_admin_atendente_estado.estado $ibge
						FROM tbl_admin_atendente_estado
						JOIN tbl_admin ON tbl_admin_atendente_estado.admin = tbl_admin.admin
						$join_ibge
						WHERE tbl_admin_atendente_estado.fabrica = $login_fabrica
						AND tbl_admin_atendente_estado.admin_atendente_estado = $x_admin_atendente_estado";
					$auditorLog->retornaDadosSelect($sqlLog);
					$res = pg_query($con, $sql);
					$res = pg_query($con,"SELECT CURRVAL('seq_admin_atendente_estado')");
                	$x_admin_atendente_estado = pg_result($res,0,0);
                	$Log = "SELECT tbl_admin_atendente_estado.admin_atendente_estado, tbl_admin.nome_completo AS Admin, tbl_admin_atendente_estado.estado $ibge
						FROM tbl_admin_atendente_estado
						JOIN tbl_admin ON tbl_admin_atendente_estado.admin = tbl_admin.admin
						$join_ibge
						WHERE tbl_admin_atendente_estado.fabrica = $login_fabrica
						AND tbl_admin_atendente_estado.admin_atendente_estado = $x_admin_atendente_estado";

					$auditorLog->retornaDadosSelect($Log)->enviarLog('insert', 'tbl_admin_atendente_estado', $login_fabrica);
				} elseif (strtoupper($acao) == 'U') {
					if ((!empty($cidade)) && ($cidade != 'null')) {
						$ibge = ", tbl_ibge.cidade";
						$join_ibge = "JOIN tbl_ibge ON tbl_admin_atendente_estado.cod_ibge = tbl_ibge.cod_ibge";
					}
					$sqlLog = "SELECT tbl_admin_atendente_estado.admin_atendente_estado, tbl_admin.nome_completo AS Admin, tbl_admin_atendente_estado.estado $ibge
						FROM tbl_admin_atendente_estado
						JOIN tbl_admin ON tbl_admin_atendente_estado.admin = tbl_admin.admin
						$join_ibge
						WHERE tbl_admin_atendente_estado.fabrica = $login_fabrica
						AND tbl_admin_atendente_estado.admin_atendente_estado = $admin_atendente_estado";
                	$auditorLog->retornaDadosSelect($sqlLog);
                	$res = pg_query($con, $sql);
					$auditorLog->retornaDadosSelect()->enviarLog('update', 'tbl_admin_atendente_estado', $login_fabrica);
				} else {
					$res = pg_query($con, $sql);
				}

			}

			if (!pg_last_error()) {
				$msg_success = true;
				unset($_POST);
				unset($admin_atendente_estado);
				pg_query($con, "COMMIT");

			} else {
				$rollback = pg_query($con, "ROLLBACK");
				if (strlen(pg_last_error()) > 0) {
					$msg_erro["msg"][] = "Erro ao gravar as informações do atendente";
				}
			}
		} else {
			pg_query($con, "ROLLBACK");
		}
	}
}

if (!empty($_GET["admin_atendente_estado"])) {
	$_RESULT["admin_atendente_estado"] = $_GET["admin_atendente_estado"];

	$sql = "SELECT admin, estado, cod_ibge, hd_classificacao as classificacao, hd_motivo_ligacao AS providencia
			FROM tbl_admin_atendente_estado
			WHERE fabrica = {$login_fabrica}
			AND admin_atendente_estado = {$_RESULT['admin_atendente_estado']}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$_RESULT["atendente"]     = pg_fetch_result($res, 0, "admin");
		$_RESULT["estado"]        = pg_fetch_result($res, 0, "estado");
		$_RESULT["cidade"]        = pg_fetch_result($res, 0, "cod_ibge");
		$_RESULT["classificacao"] = pg_fetch_result($res, 0, "classificacao");
		$_RESULT["providencia"]   = pg_fetch_result($res, 0, "providencia");
	}
}

$layout_menu = "cadastro";

$title = ($login_fabrica == 183 )? "ATENDENTE PROVIDÊNCIA" : "ATENDENTE MANUTENÇÃO";


$title_page  = "Cadastro";
if ($_GET["admin_atendente_estado"]) {
	$title_page = "Alteração de Cadastro";
}

include "cabecalho_new.php";
$plugins = array("dataTable","shadowbox");
include "plugin_loader.php";

?>

<script type="text/javascript">
	$(function () {
		var admin_atendente_estado = $('#admin_atendente_estado').val();
		if (admin_atendente_estado.length > 0 ) {

			$("#cidade").selectreadonly(true);
			$("#estado").selectreadonly(true);
		} else {
			$("#cidade").selectreadonly(false);
			$("#estado").selectreadonly(false);
		}

		var cod_ibge = "<?=getValue('cidade')?>";
		var fabrica = "<?=$login_fabrica?>";

		$("select[name=estado]").change(function () {
			if (fabrica == "30") {
				$("select[name=regiao]").val("");
			}

			$("select[name=cidade]").find("option[rel!=default]").remove();

			if ($(this).val().length > 0) {
				if (ajaxAction()) {
					$.ajax({
						url: "atendente_cadastro.php",
						type: "POST",
						data: { buscaCidade: true, estado: $(this).val() },
						beforeSend: function () {
							loading("show");
						},
						complete: function (data) {
							data = data.responseText;

							if (data.length > 0) {
								data = $.parseJSON(data);

								$.each(data, function (key, value) {
									var option = $("<option></option>");
									option.val(value.cod_ibge);
									option.text(value.cidade);

									if (value.cod_ibge == cod_ibge) {
										option.attr({ "selected": "selected" });
									}

									$("select[name=cidade]").append(option);
								});
							}

							loading("hide");
						}
					});
				}
			}
		});

		if (fabrica == "30") {
			$("select[name=regiao]").change(function(){
				$("select[name=estado]").val("");
				$("select[name=cidade]").val("");
			});
		}

		$("select[name=estado]").change();

		$("button[name=apagar]").click(function () {
			var tr                     = $(this).parents("tr");
			var admin_atendente_estado = $(this).parent("td").find("input[name=admin_atendente_estado_resultado]").val();

			if (admin_atendente_estado.length > 0) {
				if (ajaxAction()) {
					$.ajax({
						url: "atendente_cadastro.php",
						type: "POST",
						data: { apagarAtendente: true, admin_atendente_estado: admin_atendente_estado },
						beforeSend: function () {
							loading("show");
						},
						complete: function (data) {
							if (data.responseText == "erro") {
								alert("Erro ao deletar o atendente");
							}
							// if (data.responseText == "hd_chamado") {
							// 	alert("Admin possui chamados, favor alterar o admin");
							// }
							//if(data.responseText != "erro" && data.responseText != "hd_chamado") {
							if(data.responseText != "erro") {
								$(tr).remove();
								alert("Atendente apagado com sucesso");
							}

							loading("hide");
						}
					});
				}
			}
		});

		if ($.inArray(fabrica,["169","170","183"]) != -1) {
            $("select[name=classificacao]").change(function(){
                var classificacao = $(this).val();

                if (classificacao == "") {
                    $("select[name=providencia]").html("<option value=''>Selecione</option>");
                } else {
                    $.ajax({
                        url:"atendente_cadastro.php",
                        type:"POST",
                        dataType:"HTML",
                        data:{
                            verProvidencia:true,
                            classificacao:classificacao
                        }
                    })
                    .done(function(data){
                        $("select[name=providencia]").html(data);
                    });
                }
            });
		}
	});
</script>

<?php
if ($msg_success) {
?>
    <div class="alert alert-success">
		<h4>Atendente gravado com sucesso</h4>
    </div>
<?php
}

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

<form name='frm_atendente_manutencao' METHOD='POST' ACTION='<?=$PHP_SELF?>' class='form-search form-inline tc_formulario'>
	<input type="hidden" name="admin_atendente_estado" id="admin_atendente_estado" value="<?=getValue('admin_atendente_estado')?>" />

	<div class='titulo_tabela '><?=$title_page?></div>

	<br />

	<div class='row-fluid'>
<?php
    if ($login_fabrica == 90) {
        $span = "span3";
    } else {
        $span = "span2";
    }
?>
		<div class='<?=$span?>'></div>

		<div class='span4'>
			<div class='control-group <?=(in_array("atendente", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='atendente'>Atendente</label>
				<div class='controls controls-row'>
					<div class='span12'>
						<h5 class='asteristico'>*</h5>
						<select name='atendente' class='span12' >
							<option></option>
							<?php
							$cond = ($login_fabrica == 1) ? " AND admin_sap IS TRUE " : " AND (callcenter_supervisor IS TRUE OR atendente_callcenter IS TRUE) ";

							$sql = "SELECT admin, nome_completo
									FROM tbl_admin
									WHERE fabrica = {$login_fabrica}
									$cond
									AND ativo IS TRUE
									ORDER BY nome_completo";
							$res = pg_query($con, $sql);

							if (pg_num_rows($res) > 0) {
								$value = getValue("atendente");

								for ($i = 0; $i < pg_num_rows($res); $i++) {
									$admin = pg_fetch_result($res, $i, "admin");
									$nome_completo = pg_fetch_result($res, $i, "nome_completo");

									$selected = ($admin == $value) ? "selected" : "";

									echo "<option value='{$admin}' {$selected}>{$nome_completo}</option>";
								}
							}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>

<?php
		if ($moduloProvidencia OR in_array($login_fabrica,array(30,151)) OR $classificacaoHD) {
?>
        <div class='span2'>
            <div class='control-group <?=(in_array('patams_filiais_makita', $msg_erro['campos'])) ? 'error' : ''?>'>
                <label class='control-label' for='patams_filiais_makita'>Classificação</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <h5 class="asteristico">*</h5>
                        <select name="classificacao" class="span12" >
                            <option value="" >Selecione</option>
                            <?php
                            $sql = "SELECT hd_classificacao, descricao FROM tbl_hd_classificacao WHERE fabrica = {$login_fabrica} AND ativo IS TRUE ORDER BY descricao";
                            $res = pg_query($con, $sql);

                            if (pg_num_rows($res) > 0) {
                                while ($classificacao = pg_fetch_object($res)) {
                                    $selected = (getValue("classificacao") == $classificacao->hd_classificacao) ? "selected" : "";

                                    echo "<option value='{$classificacao->hd_classificacao}' {$selected} >{$classificacao->descricao}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
<?php
        }
        if (in_array($login_fabrica,array(169,170,183))) {
?>
        <div class='span2'>
            <div class='control-group <?=(in_array('patams_filiais_makita', $msg_erro['campos'])) ? 'error' : ''?>'>
                <label class='control-label' for='patams_filiais_makita'>Providência</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <select name="providencia" class="span12" >
                            <option value="" >Selecione </option>
<?php
                $sqlProv = "
                    SELECT  tbl_hd_motivo_ligacao.hd_motivo_ligacao AS providencia,
                            tbl_hd_motivo_ligacao.descricao
                    FROM    tbl_hd_motivo_ligacao
                    JOIN    tbl_hd_classificacao USING(fabrica,hd_classificacao)
                    WHERE   fabrica             = $login_fabrica
                    AND     hd_classificacao    = ".getValue("classificacao")."
            			ORDER BY      tbl_hd_motivo_ligacao.descricao
                    ";
                $resProv = pg_query($con,$sqlProv);

                while ($providencia = pg_fetch_object($resProv)) {
                    $selected = (getValue("providencia") == $providencia->providencia) ? "selected" : "";
    ?>
                                <option value="<?=$providencia->providencia?>" <?=$selected?>><?=$providencia->descricao?></option>
    <?php
                }
?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
<?php
        } if ($login_fabrica != 1) {
?>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
<?php
        }
        if(!in_array($login_fabrica, [90,183])){ ?>
		<div class='span2'>
			<div class='control-group <?=(in_array("estado", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='estado'>Estado</label>
				<div class='controls controls-row'>
					<div class='span12'>
						<?php
						if (!in_array($login_fabrica,array(30,151)) AND !$moduloProvidencia) {
						?>
							<h5 class='asteristico'>*</h5>
						<?php
						}
						?>
						<select name="estado" id="estado" class='span12'>
							<option></option>
							<?php
							$value = getValue("estado");

							foreach ($arrayEstados as $sigla => $nome) {
								$selected  = ($sigla == $value)  ? "selected" : "";

								echo "<option value='{$sigla}' {$selected}>{$nome}</option>";
							}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>

		<div class='span2'>
			<div class='control-group'>
				<label class='control-label' for='cidade'>Cidade</label>
				<div class='controls controls-row'>
					<div class='span12'>
						<select name="cidade" id="cidade" class='span12'>
							<option rel="default"></option>
						</select>
					</div>
				</div>
			</div>
		</div>
<?php
    } if (!in_array($login_fabrica, array(30))) {
?>
		<div class='span1'></div>
	</div>

	<?php
	 } else {
	?>
        <div class='span4'>
            <div class='control-group <?=(in_array("regiao", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='regiao'>Região</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <select name="regiao" class='span12' >
                            <option></option>
                            <?php
                            $value = getValue("regiao");

                            foreach ($arrayRegioes as $value) {
                                $selected  = ($sigla == $value)  ? "selected" : "";

                                echo "<option value='".$value['estados_regiao']."' {$selected}>".$value['descricao']." - ".$value['estados_regiao']."</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='span1'></div>
	</div>
	<?php
	}
	?>

	<br />

	<p>
		<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
		<?php
		if (strlen($_GET["admin_atendente_estado"]) > 0) {
		?>
			<button class='btn btn-warning' type="button"  onclick="window.location = '<?=$_SERVER["PHP_SELF"]?>';">Limpar</button>
		<?php
		}
		?>
	</p><br/>
</form>

<br />

<table id="atendente_cadastrados" class='table table-striped table-bordered table-hover table-fixed' >
	<thead>
		<tr class="titulo_coluna" >
			<th>Atendente</th>
<?php
			if (in_array($login_fabrica,array(30,151)) OR $moduloProvidencia  OR $classificacaoHD) {
?>
				<th>Classificação</th>
<?php
			}
            if (in_array($login_fabrica,array(169,170,183))) {
?>
                <th>Providência</th>
<?php
            }
?>
			<?if(!in_array($login_fabrica, [90,183])){ ?>
			<th>Estado</th>
			<th>Cidade</th>
			<?php } ?>
			<th>Ações</th>
		</tr>
	</thead>
	<tbody>
		<?php
		if (in_array($login_fabrica,array(30,151)) OR $moduloProvidencia  OR $classificacaoHD) {
			$cond = "AND tbl_admin_atendente_estado.hd_classificacao IS NOT NULL";
		}

		if (!in_array($login_fabrica, array(169,170))) {
			$condEstado = "AND tbl_admin_atendente_estado.estado IS NOT NULL";
		}

		$sql = "
            SELECT  tbl_admin_atendente_estado.admin_atendente_estado,
                    tbl_admin_atendente_estado.estado,
                    tbl_ibge.cidade,
                    tbl_admin.nome_completo,
                    tbl_admin.nao_disponivel ,
                    tbl_hd_classificacao.descricao  AS classificacao,
                    tbl_hd_motivo_ligacao.descricao AS providencia
            FROM    tbl_admin
            JOIN    tbl_admin_atendente_estado  ON  tbl_admin.admin                         = tbl_admin_atendente_estado.admin
       LEFT JOIN    tbl_ibge                    ON  tbl_ibge.cod_ibge                       = tbl_admin_atendente_estado.cod_ibge
       LEFT JOIN    tbl_hd_classificacao        ON  tbl_hd_classificacao.hd_classificacao   = tbl_admin_atendente_estado.hd_classificacao
                                                AND tbl_admin.fabrica                       = {$login_fabrica}
       LEFT JOIN    tbl_hd_motivo_ligacao       ON  tbl_hd_motivo_ligacao.hd_motivo_ligacao = tbl_admin_atendente_estado.hd_motivo_ligacao
            WHERE   (
                        tbl_admin_atendente_estado.categoria IS NULL
                    OR  LENGTH(tbl_admin_atendente_estado.categoria) = 0
                    )
            AND     tbl_admin_atendente_estado.tipo_solicitacao IS NULL
            AND     tbl_admin.ativo IS TRUE
            AND     tbl_admin_atendente_estado.fabrica = {$login_fabrica}
            {$condEstado}
            {$cond}
      ORDER BY      tbl_admin_atendente_estado.estado,
                    tbl_admin.nome_completo
        ";
		$res = pg_query($con, $sql);

		$rows = pg_num_rows($res);

		if ($rows > 0) {
			for ($i = 0; $i < $rows; $i++) {
				$admin_atendente_estado = pg_fetch_result($res, $i, "admin_atendente_estado");
				$nome_completo          = pg_fetch_result($res, $i, "nome_completo");
				$estado                 = $arrayEstados[pg_fetch_result($res, $i, "estado")];
				$nao_disponivel         = pg_fetch_result($res, $i, "nao_disponivel");
				$cidade                 = pg_fetch_result($res, $i, "cidade");
				$classificacao          = pg_fetch_result($res, $i, "classificacao");
                $providencia            = pg_fetch_result($res, $i, "providencia");

?>

				<tr>
					<td><a href="<?=$_SERVER['PHP_SELF']?>?admin_atendente_estado=<?=$admin_atendente_estado?>" ><?=$nome_completo?></a> <?=(!empty($nao_disponivel)?'(indisponível)':'')?></td>
<?php
					if (in_array($login_fabrica,array(30,151)) OR $moduloProvidencia  OR $classificacaoHD) {
?>
						<td><?=$classificacao?></td>
<?php
					}
                    if (in_array($login_fabrica,array(169,170,183))) {
?>
                        <td><?=$providencia?></td>

<?php
                    }
?>
					<?if(!in_array($login_fabrica, [90,183])){ ?>
					<td><?=$estado?></td>
					<td><?=$cidade?></td>
					<?php } ?>
					<td class="tac" >
						<input type="hidden" name="admin_atendente_estado_resultado" value="<?=$admin_atendente_estado?>" />
						<button type='button' name='apagar' class='btn btn-small btn-danger' title='Apagar o atendente' >Apagar</button>
					</td>
				</tr>
			<?php
			}
		}
		?>
	</tbody>
</table>
<!-- Visivel somente para a fábrica 1, mas pode funcionar para as fab com os mesmos campos da fab 1 -->
<?php if (in_array($login_fabrica,[1])) { ?>
<br />
<div class='tac'>
    <a rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_admin_atendente_estado&titulo=CADASTRO DE ADMINISTRADORES POR ESTADO'>Visualizar Log Auditor</a>
</div>
<br />

<script>
    $(function(){
        Shadowbox.init();
        $.dataTableLoad({ table: "#defeito_reclamado" });
    });
</script>
<? } ?>
<?php

include "rodape.php";

?>

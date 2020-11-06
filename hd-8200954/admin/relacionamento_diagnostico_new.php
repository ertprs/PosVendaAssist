<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "cadastros";

include "autentica_admin.php";
include "funcoes.php";
include_once '../class/tdocs.class.php';

if ($_POST["lista_familia"] == true) {
	$linha = $_POST["linha"];

	if (strlen($linha) > 0) {
		$sql = "SELECT linha FROM tbl_linha WHERE fabrica = {$login_fabrica} AND linha = {$linha}";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$sql  = "SELECT DISTINCT tbl_familia.familia, tbl_familia.descricao
					 FROM tbl_produto
					 JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = {$login_fabrica}
					 JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica}
					 WHERE tbl_produto.fabrica_i = {$login_fabrica}
					 AND tbl_linha.linha = {$linha}
					 ORDER BY tbl_familia.descricao";
			$res  = pg_query($con, $sql);
			$rows = pg_num_rows($res);

			if ($rows > 0) {
				$familias = array();

				for ($i = 0; $i < $rows; $i++) {
					$familia   = pg_fetch_result($res, $i, "familia");
					$descricao = pg_fetch_result($res, $i, "descricao");

					$familias[$familia] = utf8_encode($descricao);
				}

				$retorno = array("familias" => $familias);
			} else {
				$retorno = array("erro" => utf8_encode("Nenhuma família encontrada para a linha selecionada"));
			}
		} else {
			$retorno = array("erro" => utf8_encode("Linha não informada"));
		}
	} else {
		$retorno = array("erro" => utf8_encode("Linha não informada"));
	}

	echo json_encode($retorno);

	exit;
}

if ($_POST["salvar"] == true) {
	$diagnostico         = $_POST["diagnostico"];
	$mao_de_obra         = $_POST["mao_de_obra"];
	$mao_de_obra_revenda = $_POST["mao_de_obra_revenda"];

	if (strlen($diagnostico) > 0) {
		$sql = "SELECT diagnostico FROM tbl_diagnostico WHERE fabrica = {$login_fabrica} AND diagnostico = {$diagnostico}";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$sql = "UPDATE tbl_diagnostico
					SET
						mao_de_obra         = {$mao_de_obra},
						mao_de_obra_revenda = {$mao_de_obra_revenda},
						admin               = {$login_admin},
						data_atualizacao    = CURRENT_TIMESTAMP
					WHERE fabrica = {$login_fabrica}
					AND diagnostico = {$diagnostico}";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				$retorno = array("erro" => pg_last_error().utf8_encode("Erro ao salvar alterações do diagnóstico"));
			} else {
				$retorno = array("ok" => true);
			}
		} else {
			$retorno = array("erro" => utf8_encode("Diagnóstico não informado"));
		}
	} else {
		$retorno = array("erro" => utf8_encode("Diagnóstico não informado"));
	}

	echo json_encode($retorno);

	exit;
}

if ($_POST["inativar"] == true) {
	$diagnostico = $_POST["diagnostico"];

	if (is_array($diagnostico)) {
		if (count($diagnostico) > 0) {
			pg_query($con, "BEGIN");

			foreach ($diagnostico as $key => $value) {
				$sql = "SELECT diagnostico FROM tbl_diagnostico WHERE fabrica = {$login_fabrica} AND diagnostico = {$value}";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res) > 0) {
					$sql = "UPDATE tbl_diagnostico SET ativo = FALSE WHERE fabrica = {$login_fabrica} AND diagnostico = {$value}";
					$res = pg_query($con, $sql);

					if (strlen(pg_last_error()) > 0) {
						$retorno = array("erro" => utf8_encode("Erro ao inativar diagnóstico"));
						break;
					}
				} else {
					$retorno = array("erro" => utf8_encode("Diagnóstico não informado"));
					break;
				}
			}

			if (isset($retorno["erro"])) {
				pg_query($con, "ROLLBACK");
			} else {
				$retorno = array("ok" => true);
				pg_query($con, "COMMIT");
			}
		} else {
			$retorno = array("erro" => utf8_encode("Diagnóstico não informado"));
		}
	} else {
		if (strlen($diagnostico) > 0) {
			$sql = "SELECT diagnostico FROM tbl_diagnostico WHERE fabrica = {$login_fabrica} AND diagnostico = {$diagnostico}";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				$sql = "UPDATE tbl_diagnostico SET ativo = FALSE WHERE fabrica = {$login_fabrica} AND diagnostico = {$diagnostico}";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					$retorno = array("erro" => utf8_encode("Erro ao inativar diagnóstico"));
				} else {
					$retorno = array("ok" => true);
				}
			} else {
				$retorno = array("erro" => utf8_encode("Diagnóstico não informado"));
			}
		} else {
			$retorno = array("erro" => utf8_encode("Diagnóstico não informado"));
		}
	}

	echo json_encode($retorno);

	exit;
}

if ($_POST["btn_acao"] == "submit") {
	$linha              = $_POST["linha"];
	$familia            = $_POST["familia"];
	$defeito_constatado = $_POST["defeito_constatado"];
	$solucao            = $_POST["solucao"];


    if (!strlen($familia)) {
        $msg_erro["msg"][]    = "Selecione uma família";
        $msg_erro["campos"][] = "familia";
    }
	if (!strlen($defeito_constatado)) {
		$msg_erro["msg"][]    = "Selecione um defeito constatado";
		$msg_erro["campos"][] = "defeito_constatado";
	}

    if ($login_fabrica != 115) {
        if (!strlen($linha)) {
            $msg_erro["msg"][]    = "Selecione uma linha";
            $msg_erro["campos"][] = "linha";
        }

        if (!strlen($solucao)) {
            $msg_erro["msg"][]    = "Selecione uma solução";
            $msg_erro["campos"][] = "solucao";
        }
    } else {
        $linha      = 'NULL';
        $solucao    = 'NULL';
    }

    if ($login_fabrica != 115) {
        $cond = "
            AND linha   = {$linha}
            AND solucao = {$solucao}
        ";
    }

    if (!count($msg_erro["msg"])) {
        $sql = "SELECT  diagnostico
                FROM    tbl_diagnostico
                WHERE   fabrica           = {$login_fabrica}
                AND     familia             = {$familia}
                AND     defeito_constatado  = {$defeito_constatado}
                $cond
        ";
        $res = pg_query($con, $sql);

        $diagnostico = pg_fetch_result($res, 0, "diagnostico");

        if (pg_num_rows($res) > 0) {

			$sqlDiag = "UPDATE tbl_diagnostico SET ativo = TRUE WHERE fabrica = {$login_fabrica} AND diagnostico = {$diagnostico}";
		} else {
			$sqlDiag = "INSERT INTO tbl_diagnostico (
						fabrica, linha, familia, defeito_constatado, solucao, ativo
					) VALUES (
						{$login_fabrica}, {$linha}, {$familia}, {$defeito_constatado}, {$solucao}, TRUE
					)RETURNING diagnostico";
		}
        $resDiag = pg_query($con, $sqlDiag);

        $diagnostico = (pg_num_rows($res) == 0) ? pg_fetch_result($resDiag, 0, "diagnostico") : $diagnostico;

		if (isset($_FILES) && count($_FILES) > 0 && !empty($_FILES['anexo']['tmp_name'])) {

			$tDocs = new TDocs($con, $login_fabrica);

			$anexoID = $tDocs->uploadFileS3($_FILES['anexo'],$diagnostico, true, 'diagnostico');

			if ($anexoID) {
				  // Se ocorrer algum erro, o anexo está salvo:
				  $_POST['anexo'] = json_encode($tDocs->sentData);
				  if (!is_null($idExcluir)) {
				      $tDocs->deleteFileById($idExcluir);
				  }
			} else {
			  	$msg_erro["msg"] = 'Erro ao salvar o arquivo!';
			}
		}

		if (!pg_last_error() and strlen(trim($msg_erro["msg"]))==0) {
			pg_query($con, "COMMIT");
			$msg_success = true;
			unset($_POST);
			unset($diagnostico);
		} else {
			$msg_erro["msg"] = "Erro ao gravar diagnóstico";
		}
	}
}

if ($login_fabrica == 117) {
    include_once('carrega_macro_familia.php');
}

$layout_menu = "cadastro";
$title       = "CADASTRO DE DIAGNÓSTICOS";
include "cabecalho_new.php";

$plugins = array(
	"price_format",
	"shadowbox"
);
include "plugin_loader.php";

?>

<style>
	th.titulo_coluna {
		padding-left: 18px;
		padding-right: 18px;
		background-color: #596D9B !important;
	}
</style>

<script>
	$(function () {
		Shadowbox.init();

		$(".anexo").on("click", function(){
			var diagnostico = $(this).data("diagnostico");

			Shadowbox.open({
				content:"upload_anexo_diagnostico.php?diagnostico="+diagnostico,
				player:"iframe",
				width:550,
				height:450
			})
		});

		<?php
		if ($login_fabrica == 45) {
		?>
			$("#linha").change(function () {
				if ($(this).val().length > 0) {
					var linha = $(this).val();

					$.ajax({
						url: "relacionamento_diagnostico_new.php",
						type: "POST",
						data: { lista_familia: true, linha: linha },
						beforeSend: function () {
							$("#familia").hide();
							$("#familia_loading").show();
						},
						complete: function (data) {
							data = $.parseJSON(data.responseText);

							$("#familia > option").remove();
							$("#familia").html("<option value=''>Selecione</option>");

							if (data.erro) {
								alert(data.erro);
							} else {
								$.each(data.familias, function (id, nome) {
									$("#familia").append("<option value='"+id+"' >"+nome+"</option>");
								});
							}

							$("#familia").show();
							$("#familia_loading").hide();
						}
					});
				} else {
					$("#familia > option").remove();
					$("#familia").html("<option value=''>Selecione</option>");
				}
			});
		<?php
		}
		if ($login_fabrica != 115) {
		?>

		$("tr[name=linha][rel!=title]").click(function () {

			if ($(this).nextAll("tr[name=linha]").length > 0) {
				if ($(this).nextUntil("tr[name=linha]", "tr[name=familia]:visible").length > 0) {
					$(this).nextUntil("tr[name=linha]", "tr[name=solucao], tr[name=defeito_constatado], tr[name=familia]").hide();
				} else {
					$(this).nextUntil("tr[name=linha]", "tr[name=familia]").show();
				}
			} else {
				if ($(this).nextAll("tr[name=familia]:visible").length > 0) {
					$(this).nextAll("tr[name=solucao], tr[name=defeito_constatado], tr[name=familia]").hide();
				} else {
					$(this).nextAll("tr[name=familia]").show();
				}
			}
        });
<?php
        }
?>
        $("tr[name=familia][rel!=title]").click(function () {
<?php
        if ($login_fabrica == 115) {
?>
            if ($(this).nextAll("tr[name=familia]").length > 0) {
                if ($(this).nextUntil("tr[name=familia]", "tr[name=defeito_constatado]:visible").length > 0) {
                    $(this).nextUntil("tr[name=familia]", "tr[name=defeito_constatado], tr[name=mao_de_obra]").hide();
                } else {
                    $(this).nextUntil("tr[name=familia]", "tr[name=defeito_constatado]").show();
                }
            } else {
                if ($(this).nextAll("tr[name=defeito_constatado]:visible").length > 0) {
                    $(this).nextAll("tr[name=defeito_constatado], tr[name=mao_de_obra] ").hide();
                } else {
                    $(this).nextAll("tr[name=defeito_constatado]").show();
                }
            }
<?php
        } else {
?>


			if ($(this).nextAll("tr[name=familia]").length > 0) {
				if ($(this).nextUntil("tr[name=familia]", "tr[name=defeito_constatado]:visible").length > 0) {
					$(this).nextUntil("tr[name=familia]", "tr[name=solucao], tr[name=defeito_constatado]").hide();
				} else {
					$(this).nextUntil("tr[name=familia]", "tr[name=defeito_constatado]").show();
				}
			} else {
				if ($(this).nextAll("tr[name=defeito_constatado]:visible").length > 0) {
					$(this).nextAll("tr[name=solucao], tr[name=defeito_constatado]").hide();
				} else {
					$(this).nextAll("tr[name=defeito_constatado]").show();
				}
			}
<?php
        }
?>
		});

<?php
        if ($login_fabrica != 115) {
?>
		$("tr[name=defeito_constatado][rel!=title]").click(function () {
			if ($(this).nextAll("tr[name=defeito_constatado]").length > 0) {
				if ($(this).nextUntil("tr[name=defeito_constatado]", "tr[name=solucao]:visible").length > 0) {
					$(this).nextUntil("tr[name=defeito_constatado]", "tr[name=solucao]").hide();
				} else {
					$(this).nextUntil("tr[name=defeito_constatado]", "tr[name=solucao]").show();
				}
			} else {
				if ($(this).nextAll("tr[name=solucao]:visible").length > 0) {
					$(this).nextAll("tr[name=solucao]").hide();
				} else {
					$(this).nextAll("tr[name=solucao]").show();
				}
			}
		});
<?php
        } else {
?>
        $("tr[name=defeito_constatado][rel!=title]").click(function () {
            if ($(this).nextAll("tr[name=defeito_constatado]").length > 0) {
                if ($(this).nextUntil("tr[name=defeito_constatado]", "tr[name=mao_de_obra]:visible").length > 0) {
                    $(this).nextUntil("tr[name=defeito_constatado]", "tr[name=mao_de_obra]").hide();
                } else {
                    $(this).nextUntil("tr[name=defeito_constatado]", "tr[name=mao_de_obra]").show();
                }
            } else {
                if ($(this).nextAll("tr[name=mao_de_obra]:visible").length > 0) {
                    $(this).nextAll("tr[name=mao_de_obra]").hide();
                } else {
                    $(this).nextAll("tr[name=mao_de_obra]").show();
                }
            }
        });
<?php
        }
?>
		$("button[name=inativar_diagnostico]").click(function () {
			if (confirm("Deseja realmente inativar o diagnóstico ?")) {
				var diagnostico = $(this).attr("rel");
				var th = $(this).parents("th");

				if (diagnostico.length > 0) {
					$.ajax({
						url: "relacionamento_diagnostico_new.php",
						type: "POST",
						data: { inativar: true, diagnostico: diagnostico },
						beforeSend: function () {
							$(th).find("span[name=button]").hide();
							$(th).find("span[name=loading]").show();
						},
						complete: function (data) {
							data = $.parseJSON(data.responseText);

							if (data.erro) {
								alert(data.erro);

								$(th).find("span[name=button]").show();
								$(th).find("span[name=loading]").hide();
							} else {
								$(th).parent("tr").remove();
							}
						}
					});
				}
			}
		});

		$("button[name=inativar_diagnosticos_selecionados]").click(function () {
			if (ajaxAction() == true) {
				var diagnosticos = [];

				$("input[name=inativar_diagnostico]:checked").each(function () {
					diagnosticos.push($(this).val());
				});

				if (diagnosticos.length > 0) {
					if (confirm("Deseja realmente inativar os diagnósticos selecionados ?")) {
						$.ajax({
							url: "relacionamento_diagnostico_new.php",
							type: "POST",
							data: { inativar: true, diagnostico: diagnosticos },
							beforeSend: function () {
								loading("show");
							},
							complete: function (data) {
								data = $.parseJSON(data.responseText);

								if (data.erro) {
									alert(data.erro);
								} else {
									$("input[name=inativar_diagnostico]:checked").each(function () {
										$(this).parents("tr").remove();
									});
								}

								loading("hide");
							}
						});
					}
				} else {
					alert("Nenhum diagnóstico foi selecionado");
				}
			}
		});

		$("button[name=salvar_diagnostico]").click(function () {
			var diagnostico = $(this).attr("rel");
			var th = $(this).parents("th");

			var mao_de_obra         = $(th).parents("tr").find("input[name=mao_de_obra]").val();
			var mao_de_obra_revenda = $(th).parents("tr").find("input[name=mao_de_obra_revenda]").val();

			if (mao_de_obra == undefined || mao_de_obra.length == 0) {
				mao_de_obra = 0;
			} else {
				mao_de_obra = mao_de_obra.replace(".", "");
				mao_de_obra = mao_de_obra.replace(",", ".");
			}

			if (mao_de_obra_revenda == undefined || mao_de_obra_revenda.length == 0) {
				mao_de_obra_revenda = 0;
			} else {
				mao_de_obra_revenda = mao_de_obra_revenda.replace(".", "");
				mao_de_obra_revenda = mao_de_obra_revenda.replace(",", ".");
			}

			if (diagnostico.length > 0) {
				$.ajax({
					url: "relacionamento_diagnostico_new.php",
					type: "POST",
					dataType:"JSON",
					data: {
                        salvar: true,
                        diagnostico: diagnostico,
                        mao_de_obra: mao_de_obra,
                        mao_de_obra_revenda: mao_de_obra_revenda
                    },
					beforeSend: function () {
						$(th).find("span[name=button]").hide();
						$(th).find("span[name=loading]").show();
					}
                })
				.done(function (data) {

                    if (data.erro) {
                        alert(data.erro);

                        $(th).find("span[name=button]").show();
                        $(th).find("span[name=loading]").hide();
                    } else {
                        $(th).find("span[name=button]").show();
                        $(th).find("span[name=loading]").hide();
                    }
				});
			}
		});
	});
</script>

<?php
if ($msg_success) {
?>
    <div class="alert alert-success">
		<h4>Diagnóstico, gravado com sucesso</h4>
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

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' enctype="multipart/form-data">
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>

	<br/>

	<div class='row-fluid'>

		<div class='span2'></div>
<?php
                        if ($login_fabrica != 115) {
?>
		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["linha"])) ? "error" : ""?>'>
				<label class='control-label' for='linha'><?php echo ($login_fabrica == 117)?"Macro - Família":"Linha"; ?></label>
				<div class='controls controls-row'>
					<div class='span12'>
						<h5 class='asteristico'>*</h5>
						<select id="linha" name="linha" >
							<option value="">Selecione</option>
							<?php
							if ($login_fabrica == 117) {
                                $sql = "SELECT DISTINCT tbl_linha.linha, tbl_linha.nome, tbl_linha.codigo_linha
                                        FROM tbl_linha
                                                JOIN tbl_macro_linha_fabrica ON tbl_linha.linha = tbl_macro_linha_fabrica.linha
                                                JOIN tbl_macro_linha ON tbl_macro_linha_fabrica.macro_linha = tbl_macro_linha.macro_linha
                                        WHERE tbl_macro_linha_fabrica.fabrica = {$login_fabrica}
                                                AND tbl_linha.ativo IS TRUE
                                                AND tbl_macro_linha.ativo IS TRUE
                                        ORDER BY nome";
                                $res  = pg_query($con, $sql);
                                $rows = pg_num_rows($res);

                                if ($rows > 0) {
                                        for ($i = 0; $i < $rows; $i++) {
                                                $linha        = pg_fetch_result($res, $i, "linha");
                                                $nome         = pg_fetch_result($res, $i, "nome");
                                                $codigo_linha = pg_fetch_result($res, $i, "codigo_linha");

                                                $selected = ($_POST["linha"] == $linha) ? "selected" : "";

                                                echo "<option value='{$linha}' {$selected} >{$nome}</option>";
                                        }
                                }
                        } else {
							$sql  = "SELECT linha, nome, codigo_linha FROM tbl_linha WHERE fabrica = {$login_fabrica} AND ativo IS TRUE ORDER BY nome";
							$res  = pg_query($con, $sql);
							$rows = pg_num_rows($res);


							if ($rows > 0) {
								for ($i = 0; $i < $rows; $i++) {
									$linha        = pg_fetch_result($res, $i, "linha");
									$nome         = pg_fetch_result($res, $i, "nome");
									$codigo_linha = pg_fetch_result($res, $i, "codigo_linha");

									$selected = ($_POST["linha"] == $linha) ? "selected" : "";

									echo "<option value='{$linha}' {$selected} >{$codigo_linha} - {$nome}</option>";
								}
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
?>
		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["familia"])) ? "error" : ""?>'>
				<label class='control-label' for='familia'>Familia</label>
				<div class='controls controls-row'>
					<div class='span12'>
						<h5 class='asteristico'>*</h5>
						<select id="familia" name="familia" >
							<option value="">Selecione</option>
                                <?php
							if ($login_fabrica != 45) {
								$sql  = "SELECT familia, descricao, codigo_familia FROM tbl_familia WHERE fabrica = {$login_fabrica} AND ativo";
								$res  = pg_query($con, $sql);
								$rows = pg_num_rows($res);


								if ($rows > 0) {
									for ($i = 0; $i < $rows; $i++) {
										$familia        = pg_fetch_result($res, $i, "familia");
										$descricao      = pg_fetch_result($res, $i, "descricao");
										$codigo_familia = pg_fetch_result($res, $i, "codigo_familia");

										$selected = ($_POST["familia"] == $familia) ? "selected" : "";

										echo "<option value='{$familia}' {$selected} >{$codigo_familia} - {$descricao}</option>";
									}
								}
							}
							?>

						</select>

						<?php
						if ($login_fabrica == 45) {
							echo "<img id='familia_loading' src='imagens/loading_img.gif' style='height: 28px; width: 28px; display: none;' />";
						}
						?>
					</div>
				</div>
			</div>
		</div>
<?php
                        if ($login_fabrica != 115) {
?>
		<div class='span2'></div>

	</div>

	<div class='row-fluid'>

		<div class='span2'></div>
<?php
                        }
?>

		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["defeito_constatado"])) ? "error" : ""?>'>
				<label class='control-label' for='defeito_constatado'>Defeito Constatado</label>
				<div class='controls controls-row'>
					<div class='span12'>
						<h5 class='asteristico'>*</h5>
						<select id="defeito_constatado" name="defeito_constatado" >
							<option value="">Selecione</option>
							<?php
							$sql  = "SELECT defeito_constatado, descricao, codigo FROM tbl_defeito_constatado WHERE fabrica = {$login_fabrica} AND ativo IS TRUE ORDER BY descricao";
							$res  = pg_query($con, $sql);
							$rows = pg_num_rows($res);


							if ($rows > 0) {
								for ($i = 0; $i < $rows; $i++) {
									$defeito_constatado = pg_fetch_result($res, $i, "defeito_constatado");
									$descricao          = pg_fetch_result($res, $i, "descricao");
									$codigo             = pg_fetch_result($res, $i, "codigo");

									$selected = ($_POST["defeito_constatado"] == $defeito_constatado) ? "selected" : "";

									echo "<option value='{$defeito_constatado}' {$selected} >{$codigo} - {$descricao}</option>";
								}
							}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
<?php
                        if ($login_fabrica != 115) {
?>
		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["solucao"])) ? "error" : ""?>'>
				<label class='control-label' for='solucao'>Solução</label>
				<div class='controls controls-row'>
					<div class='span12'>
						<h5 class='asteristico'>*</h5>
						<select id="solucao" name="solucao" >
							<option value="">Selecione</option>
							<?php
							$sql  = "SELECT solucao, descricao, codigo FROM tbl_solucao WHERE fabrica = {$login_fabrica} AND ativo IS TRUE ORDER BY descricao";
							$res  = pg_query($con, $sql);
							$rows = pg_num_rows($res);


							if ($rows > 0) {
								for ($i = 0; $i < $rows; $i++) {
									$solucao   = pg_fetch_result($res, $i, "solucao");
									$descricao = pg_fetch_result($res, $i, "descricao");
									$codigo    = pg_fetch_result($res, $i, "codigo");

									$selected = ($_POST["solucao"] == $solucao) ? "selected" : "";

									echo "<option value='{$solucao}' {$selected} >{$codigo} - {$descricao}</option>";
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
?>
		<div class='span2'></div>

	</div>
<?php
                        if(in_array($login_fabrica, array(85))){
?>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class="form-group">
			    <label>Anexo</label>
			    <input type="file" name="anexo" id="exampleInputFile">
			</div>
		</div>
		<div class='span2'></div>
	</div>
<?php
                        }
                        if ($login_fabrica == 115) {
?>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span8'>
            <div class='control-group'>
                <label class='control-label' for='mao_de_obra'>Mão-de-obra</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <input type="text" name="mao_de_obra" id="mao_de_obra" price='true' />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
<?php
                        }
?>

	<p><br />
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p><br />

</form>

<div class="container">

	<table id="resultado_os_atendimento" class="table table-striped table-bordered table-large" >
		<tbody>
			<tr>
				<th class="titulo_coluna tal" name='linha' rel='title' colspan="<?=($login_fabrica == 85) ? '7' : '5'?>" ><?=($login_fabrica == 117) ? 'Macro-Família' : (($login_fabrica == 115) ? 'Famílias' : 'Linhas') ?></th>
			</tr>
<?php

            if ($login_fabrica != 115) {
                $campos = "

                    tbl_diagnostico.linha,
                    tbl_diagnostico.solucao,
                    tbl_linha.nome                          AS linha_nome,
                    tbl_linha.codigo_linha                  AS linha_codigo,
                    tbl_solucao.descricao AS solucao_nome,
                    tbl_solucao.codigo AS solucao_codigo,
                    tbl_solucao.ativo AS solucao_ativo,
                ";
                $join = "
                    JOIN    tbl_linha   ON  tbl_linha.linha                             = tbl_diagnostico.linha
                                        AND tbl_linha.fabrica                           = {$login_fabrica}
                                        AND tbl_linha.ativo                             IS TRUE
                    JOIN    tbl_solucao ON  tbl_solucao.solucao = tbl_diagnostico.solucao
                                        AND tbl_solucao.fabrica = {$login_fabrica}
                ";
            }

            $sql = "
                SELECT  tbl_diagnostico.diagnostico,
                        tbl_diagnostico.familia,
                        tbl_familia.descricao                   AS familia_nome,
                        tbl_familia.codigo_familia              AS familia_codigo,
                        tbl_diagnostico.defeito_constatado,
                        tbl_defeito_constatado.descricao        AS defeito_constatado_nome,
                        tbl_defeito_constatado.codigo           AS defeito_constatado_codigo,
                        tbl_defeito_constatado.ativo            AS defeito_constatado_ativo,
                        $campos
                        COALESCE(tbl_diagnostico.mao_de_obra,0) AS mao_de_obra,
                        tbl_diagnostico.mao_de_obra_revenda
           INTO TEMP    tmp_diagnostico
                FROM    tbl_diagnostico
                JOIN    tbl_familia             ON  tbl_familia.familia = tbl_diagnostico.familia
                                                AND tbl_familia.fabrica = {$login_fabrica}
                                                AND tbl_familia.ativo   IS TRUE
                JOIN    tbl_defeito_constatado  ON  tbl_defeito_constatado.defeito_constatado   = tbl_diagnostico.defeito_constatado
                                                AND tbl_defeito_constatado.fabrica              = {$login_fabrica}
                $join
                WHERE   tbl_diagnostico.fabrica = {$login_fabrica}
                AND     tbl_diagnostico.ativo ";

            $res = pg_query($con, $sql);

            if ($login_fabrica != 115) {
                $sql_linha  = "SELECT DISTINCT linha, linha_nome, linha_codigo FROM tmp_diagnostico ORDER BY linha_nome";
                $res_linha  = pg_query($con, $sql_linha);
                $rows_linha = pg_num_rows($res_linha);

                if ($rows_linha > 0) {
                    for ($l = 0; $l < $rows_linha; $l++) {
                        $linha        = pg_fetch_result($res_linha, $l, "linha");
                        $linha_nome   = pg_fetch_result($res_linha, $l, "linha_nome");
                        $linha_codigo = pg_fetch_result($res_linha, $l, "linha_codigo");

                        echo "<tr name='linha' >
                            <td class='tal' colspan='".(($login_fabrica == 85) ? "7" : "5")."' style='cursor: pointer;' >{$linha_codigo} - {$linha_nome}</td>
                        </tr>";


                            $sql_familia  = "SELECT DISTINCT familia, familia_nome, familia_codigo FROM tmp_diagnostico WHERE linha = {$linha} ORDER BY familia_nome";
                            $res_familia  = pg_query($con, $sql_familia);
                            $rows_familia = pg_num_rows($res_familia);

                            if ($rows_familia > 0) {
                                echo "<tr name='familia' rel='title' style='display: none;' >
                                    <th>&nbsp;</th>
                                    <th class='titulo_coluna tal' colspan='".(($login_fabrica == 85) ? "6" : "4")."' >Famílias</th>
                                </tr>";

                                for ($f = 0; $f < $rows_familia; $f++) {
                                    $familia        = pg_fetch_result($res_familia, $f, "familia");
                                    $familia_nome   = pg_fetch_result($res_familia, $f, "familia_nome");
                                    $familia_codigo = pg_fetch_result($res_familia, $f, "familia_codigo");

                                    echo "<tr name='familia' style='display: none;' >
                                        <th>&nbsp;</th>
                                        <th class='tal' colspan='".(($login_fabrica == 85) ? "6" : "4")."' style='cursor: pointer;' >{$familia_codigo} - {$familia_nome}</th>
                                    </tr>";

                                    $sql_defeito_constatado  = "SELECT DISTINCT defeito_constatado, defeito_constatado_nome, defeito_constatado_codigo, defeito_constatado_ativo FROM tmp_diagnostico WHERE linha = {$linha} AND familia = {$familia} ORDER BY defeito_constatado_nome";
                                    $res_defeito_constatado  = pg_query($con, $sql_defeito_constatado);
                                    $rows_defeito_constatado = pg_num_rows($res_defeito_constatado);

                                    if ($rows_defeito_constatado > 0) {
                                        echo "<tr name='defeito_constatado' rel='title' style='display: none;' >
                                            <th>&nbsp;</th>
                                            <th>&nbsp;</th>
                                            <th class='titulo_coluna tal' colspan='".(($login_fabrica == 85) ? "6" : "3")."' >Defeitos Constatados</th>
                                        </tr>";

                                        for ($dc = 0; $dc < $rows_defeito_constatado; $dc++) {
                                            $defeito_constatado        = pg_fetch_result($res_defeito_constatado, $dc, "defeito_constatado");
                                            $defeito_constatado_nome   = pg_fetch_result($res_defeito_constatado, $dc, "defeito_constatado_nome");
                                            $defeito_constatado_codigo = pg_fetch_result($res_defeito_constatado, $dc, "defeito_constatado_codigo");
                                            $defeito_constatado_ativo  = pg_fetch_result($res_defeito_constatado, $dc, "defeito_constatado_ativo");

                                            $inativo = ($defeito_constatado_ativo == "f") ? "<span style='color: #FF0000;'>(inativo)</span>" : "";

                                            echo "<tr name='defeito_constatado' style='display: none;' >
                                                <th>&nbsp;</th>
                                                <th>&nbsp;</th>
                                                <th class='tal' colspan='".(($login_fabrica == 85) ? "6" : "3")."' style='cursor: pointer;' >{$defeito_constatado_codigo} - {$defeito_constatado_nome} {$inativo}</th>
                                            </tr>";

                                            $sql_solucao  = "SELECT diagnostico, solucao, solucao_nome, solucao_codigo, solucao_ativo, mao_de_obra, mao_de_obra_revenda FROM tmp_diagnostico WHERE linha = {$linha} AND familia = {$familia} AND defeito_constatado = {$defeito_constatado} ORDER BY solucao_nome";
                                            $res_solucao  = pg_query($con, $sql_solucao);
                                            $rows_solucao = pg_num_rows($res_solucao);

                                            if ($rows_solucao > 0) {
                                                echo "<tr name='solucao' rel='title' style='display: none;' >
                                                    <th>&nbsp;</th>
                                                    <th>&nbsp;</th>
                                                    <th>&nbsp;</th>
                                                    <th class='titulo_coluna tal' ".(($login_fabrica != 85) ? "colspan='2'" : "")." >Soluções</th>";

                                                    if ($login_fabrica == 85) {
                                                        echo "<th class='titulo_coluna tac'>Mão de Obra/Tabela 1</th>
                                                        <th class='titulo_coluna tac'>Mão de Obra/Tabela 2</th>
                                                        <th class='titulo_coluna' >&nbsp;</th>
                                                        <th class='titulo_coluna' >Anexo</th>
                                                        <th class='titulo_coluna' >&nbsp;</th>";
                                                    }

                                                echo "</tr>";

                                                for ($s = 0; $s < $rows_solucao; $s++) {
                                                    $diagnostico    = pg_fetch_result($res_solucao, $s, "diagnostico");
                                                    $solucao        = pg_fetch_result($res_solucao, $s, "solucao");
                                                    $solucao_nome   = pg_fetch_result($res_solucao, $s, "solucao_nome");
                                                    $solucao_codigo = pg_fetch_result($res_solucao, $s, "solucao_codigo");
                                                    $solucao_ativo  = pg_fetch_result($res_solucao, $s, "solucao_ativo");

                                                    if ($login_fabrica == 85) {
                                                        $mao_de_obra         = number_format(pg_fetch_result($res_solucao, $s, "mao_de_obra"), 2, ",", ".");
                                                        $mao_de_obra_revenda = number_format(pg_fetch_result($res_solucao, $s, "mao_de_obra_revenda"), 2, ",", ".");
                                                    }

                                                    $inativo = ($solucao_ativo == "f") ? "<span style='color: #FF0000;'>(inativo)</span>" : "";

                                                    echo "<tr name='solucao' style='display: none;' >
                                                        <th>&nbsp;</th>
                                                        <th>&nbsp;</th>
                                                        <th>&nbsp;</th>
                                                        <th class='tal' >{$solucao_codigo} - {$solucao_nome} {$inativo}</th>";

                                                        if ($login_fabrica == 85) {
                                                            echo "<th class='tac' ><input type='text' class='span1' name='mao_de_obra' price='true' value='{$mao_de_obra}' /></th>
                                                            <th class='tac' ><input type='text' class='span1' name='mao_de_obra_revenda' price='true' value='{$mao_de_obra_revenda}' /></th>";
                                                        }

                                                        if ($login_fabrica == 15) {
                                                            echo "<th class='tac' ><input type='checkbox' name='inativar_diagnostico' value='{$diagnostico}' /> Inativar</th>";
                                                        } else if ($login_fabrica == 85) {
                                                            echo "<th class='tac' nowrap >
                                                                <span name='button' >
                                                                    <button name='salvar_diagnostico' class='btn btn-small' rel='{$diagnostico}' >Salvar</button>
                                                                    <button name='inativar_diagnostico' class='btn btn-small btn-danger' rel='{$diagnostico}' >Inativar</button>
                                                                </span>
                                                                <span name='loading' style='display: none;'>
                                                                    <img src='imagens/loading_img.gif' style='height: 32px; width: 32px;' />
                                                                </span>
                                                            </th>";
                                                            $tDocs = new TDocs($con, $login_fabrica);
                                                            $caminho = $tDocs->getdocumentsByRef($diagnostico, 'diagnostico')->url;
        //../helpdesk/imagem/clips.gif
                                                            echo "<th id='anexo_$diagnostico'>";
                                                            if(strlen(trim($caminho))>0){
                                                                echo "<a href='$caminho' target='_blank'>".
                                                                    "<img src='../helpdesk/imagem/clips.gif' width='35px' height='35px'>".
                                                                    "</a>";
                                                            }else{
                                                                echo "&nbsp;";
                                                            }

                                                            echo "</th>";
                                                            $nome_botao = (strlen(trim($caminho))>0)? "Alterar" : "Inserir";
                                                            echo "<th>
                                                                <button class='anexo' data-diagnostico='$diagnostico'>$nome_botao</button>

                                                            </th>";
                                                        } else {
                                                            echo "<th class='tac' >
                                                                <span name='button'>
                                                                    <button name='inativar_diagnostico' class='btn btn-small btn-danger' rel='{$diagnostico}' >Inativar</button>
                                                                </span>
                                                                <span name='loading' style='display: none;'>
                                                                    <img src='imagens/loading_img.gif' style='height: 32px; width: 32px;' />
                                                                </span>
                                                            </th>";
                                                        }
                                                    echo "</tr>";
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                } else {
                    $sql_familia  = "SELECT DISTINCT familia, familia_nome, familia_codigo FROM tmp_diagnostico ORDER BY familia_nome";
                    $res_familia  = pg_query($con, $sql_familia);
                    $rows_familia = pg_num_rows($res_familia);
                    if ($rows_familia > 0) {

                        for ($f = 0; $f < $rows_familia; $f++) {
                            $familia        = pg_fetch_result($res_familia, $f, "familia");
                            $familia_nome   = pg_fetch_result($res_familia, $f, "familia_nome");
                            $familia_codigo = pg_fetch_result($res_familia, $f, "familia_codigo");

                            echo "<tr name='familia' >
                                <th class='tal' colspan='5' style='cursor: pointer;' >{$familia_codigo} - {$familia_nome}</th>
                            </tr>";
                            $sql_defeito_constatado  = "
                                SELECT  DISTINCT
                                        defeito_constatado,
                                        defeito_constatado_nome,
                                        defeito_constatado_codigo,
                                        defeito_constatado_ativo
                                FROM    tmp_diagnostico
                                WHERE   familia = {$familia}
                          ORDER BY      defeito_constatado_nome";

                            $res_defeito_constatado  = pg_query($con, $sql_defeito_constatado);
                            $rows_defeito_constatado = pg_num_rows($res_defeito_constatado);

                            if ($rows_defeito_constatado > 0) {
                                echo "<tr name='defeito_constatado' rel='title' style='display: none;' >
                                    <th>&nbsp;</th>
                                    <th class='titulo_coluna tal' colspan='4' >Defeitos Constatados</th>
                                </tr>";

                                for ($dc = 0; $dc < $rows_defeito_constatado; $dc++) {
                                    $defeito_constatado        = pg_fetch_result($res_defeito_constatado, $dc, "defeito_constatado");
                                    $defeito_constatado_nome   = pg_fetch_result($res_defeito_constatado, $dc, "defeito_constatado_nome");
                                    $defeito_constatado_codigo = pg_fetch_result($res_defeito_constatado, $dc, "defeito_constatado_codigo");
                                    $defeito_constatado_ativo  = pg_fetch_result($res_defeito_constatado, $dc, "defeito_constatado_ativo");

                                    $inativo = ($defeito_constatado_ativo == "f") ? "<span style='color: #FF0000;'>(inativo)</span>" : "";

                                    echo "<tr name='defeito_constatado' style='display: none;' >
                                        <td>&nbsp;</td>
                                        <td class='tal' colspan='4' style='cursor: pointer;' >{$defeito_constatado_codigo} - {$defeito_constatado_nome} {$inativo}</td>
                                    </tr>";

                                    $sql_mo = "
                                        SELECT  diagnostico,
                                                mao_de_obra
                                        FROM    tmp_diagnostico
                                        WHERE   familia             = {$familia}
                                        AND     defeito_constatado  = {$defeito_constatado}
                                    ";
//                                     echo nl2br($sql_mo);

                                    $res_mo     = pg_query($con, $sql_mo);
                                    $rows_mo    = pg_num_rows($res_mo);

                                    if ($rows_mo > 0) {
                                        echo "<tr name='mao_de_obra' rel='title' style='display: none;' >
                                            <th>&nbsp;</th>
                                            <th>&nbsp;</th>
                                            <th class='titulo_coluna tal' colspan='3' >Mão-de-Obra</th>";


                                        echo "</tr>";

                                        for ($s = 0; $s < $rows_mo; $s++) {
                                            $diagnostico    = pg_fetch_result($res_mo, $s, "diagnostico");
                                            $mao_de_obra    = number_format(pg_fetch_result($res_mo, $s, "mao_de_obra"), 2, ",", ".");


                                            echo "<tr name='mao_de_obra'  style='display: none;' >
                                                <th>&nbsp;</th>
                                                <th>&nbsp;</th>
                                                <th class='tac' ><input type='text' class='span1' name='mao_de_obra' price='true' value='{$mao_de_obra}' /></th>";

                                            echo "<th class='tac' >
                                                <span name='button'>
                                                    <button name='salvar_diagnostico' class='btn btn-small btn-success' rel='{$diagnostico}' >Salvar</button>
                                                    <button name='inativar_diagnostico' class='btn btn-small btn-danger' rel='{$diagnostico}' >Inativar</button>
                                                </span>
                                                <span name='loading' style='display: none;'>
                                                    <img src='imagens/loading_img.gif' style='height: 32px; width: 32px;' />
                                                </span>
                                            </th></tr>";
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

			?>
		</tbody>
	</table>

	<?php
	if ($login_fabrica == 15) {
		echo "<p style='text-align: center;' ><button name='inativar_diagnosticos_selecionados' class='btn btn-danger' >Inativar diagnósticos selecionados</button></p>";
	}
	?>

</div>

<?php
include "rodape.php";
?>

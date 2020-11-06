<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "auditoria";
include 'autentica_admin.php';

include 'funcoes.php';

$layout_menu = "auditoria";
$title = "CADASTRO DE CHECKLIST DE VISITA";

if (isset($_POST["ajax_busca_cidade"]) && !empty($_POST["estado"])) {
    $estado = strtoupper($_POST["estado"]);

    if (array_key_exists($estado, $array_estados)) {
        $sql = "SELECT DISTINCT * FROM (
                    SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('{$estado}')
                    UNION (
                        SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('{$estado}')
                    )
                ) AS cidade
                ORDER BY cidade ASC";
        $res = pg_query($con, $sql);
        if (pg_num_rows($res) > 0) {
            $array_cidades = array();

            while ($result = pg_fetch_object($res)) {
                $array_cidades[] = $result->cidade;
            }

            $retorno = array("cidades" => $array_cidades);
        } else {
            $retorno = array("error" => utf8_encode(traduz("nenhuma.cidade.encontrada.para.o.estado") . ": {$estado}"));
        }
    } else {
        $retorno = array("error" => utf8_encode(traduz("estado.nao.encontrado")));
    }

    exit(json_encode($retorno));
}

include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable",
);

$array_estados = $array_estados();

include "plugin_loader.php";

if (isset($_POST["submit"])) {

	$data_inicial 		= formata_data(trim($_POST['data_inicial']));
	$data_final   		= formata_data(trim($_POST['data_final']));
	$codigo_posto 		= trim($_POST['codigo_posto']);
	$descricao_posto    = trim($_POST['descricao_posto']);
	$estado             = trim($_POST['estado']);
	$cidade             = trim($_POST['cidade']);

	if (!empty($estado)) {
		$condEstado = "AND UPPER(pf.contato_estado) = UPPER('{$estado}')";
	}

	if (!empty($cidade)) {
		$condCidade = "AND UPPER(pf.contato_cidade) = UPPER('{$cidade}')";
	}

	if (empty($data_final) || empty($data_inicial)) {
		$msg_erro["msg"][]    = "Preenha os campos obrigatórios";
		$msg_erro["campos"][] = "data";
	}

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

	if (!empty($posto)) {
		$condPosto = "AND vs.posto = {$posto}";
	}

	if (count($msg_erro) == 0) {
		$sqlVisita = "SELECT vs.visita_posto, pf.codigo_posto, p.nome, TO_CHAR(vs.data, 'DD/MM/YYYY') AS data
						FROM tbl_visita_posto vs
						INNER JOIN tbl_posto_fabrica pf ON pf.posto = vs.posto AND pf.fabrica = $login_fabrica
						INNER JOIN tbl_posto p ON p.posto = pf.posto
						WHERE vs.fabrica = $login_fabrica
						AND (vs.data BETWEEN '$data_inicial' AND '$data_final')
						{$condPosto}
						{$condCidade}
						{$condEstado}
						ORDER BY vs.data DESC, vs.visita_posto ASC";
		$resVisita = pg_query($con, $sqlVisita);
	}
}

?>
<script>
	$(function() {
		$.autocompleteLoad(Array("posto"));
		$.datepickerLoad(Array("data_final", "data_inicial"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		$("#estado").change(function() {
	        busca_cidade($(this).val());
	    });

	});

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
		$("#id_posto").val(retorno.posto);
    }

    function busca_cidade(estado) {
	    $("#cidade").find("option").first().nextAll().remove();

	    if (estado.length > 0) {
	        $.ajax({
	            async: false,
	            url: "cadastro_os.php",
	            type: "POST",
	            data: { ajax: true, ajax_busca_cidade: true, estado: estado },
	            beforeSend: function() {
	                if ($("#cidade").next("img").length == 0) {
	                    $("#cidade").hide().after($("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } }));
	                }
	            },
	            complete: function(data) {
	                data = $.parseJSON(data.responseText);

	                if (data.error) {
	                    alert(data.error);
	                } else {
	                    $.each(data.cidades, function(key, value) {
	                        var option = $("<option></option>", { value: value, text: value });
	                        $("#cidade").append(option);
	                    });
	                }

	                $("#cidade").show().next().remove();
	            }
	        });
	    }

	    if(typeof cidade != "undefined" && cidade.length > 0){

	        $("#cidade option[value='"+cidade+"']").attr('selected','selected');

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
}
?>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_checklist' METHOD='POST' ACTION='<?= $_SERVER['PHP_SELF'] ?>' align='center' class='form-search form-inline'>
	<div id="lupa_posto" class="tc_formulario">
		<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
		<br />
		<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='data_inicial'>Data Inicial</label>
						<div class='controls controls-row'>
							<div class='span4'>
								<h5 class='asteristico'>*</h5>
									<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?= $_POST['data_inicial'] ?>">
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
								<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?= $_POST['data_final'] ?>" >
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
			<input type="hidden" name="posto" id="id_posto" />
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
				<div class='control-group <?=(in_array("estado", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'>Estado</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<select id="estado" name="estado" class="span12">
                                <option value="" ><?php echo traduz('selecione');?></option>
                                <?php
                                #O $array_estados está no arquivo funcoes.php
                                foreach ($array_estados as $sigla => $nome_estado) {
                                    $selected = ($sigla == $estado) ? "selected" : "";

                                    echo "<option value='{$sigla}' {$selected} >" . $nome_estado . "</option>";
                                }
                                ?>
                            </select>
						</div>
					</div>
				</div>
			</div>
			<input type="hidden" name="posto" id="id_posto" />
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'>Cidade</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<select id="cidade" name="cidade" class="span12" <?=$disabled?>>
                                <option value="" ><?php echo traduz('selecione');?></option>

                                <?php
                                $cidade = $_POST['cidade'];

                                if (strlen($estado) > 0) {
                                    $sql = "SELECT DISTINCT * FROM (
                                                SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('".$estado."')
                                                UNION (
                                                    SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('".$estado."')
                                                )
                                            ) AS cidade
                                            ORDER BY cidade ASC";
                                    $res = pg_query($con, $sql);

                                    if (pg_num_rows($res) > 0) {
                                        while ($result = pg_fetch_object($res)) {
                                            $selected  = (trim($result->cidade) == trim($cidade)) ? "SELECTED" : "";

                                            echo "<option value='{$result->cidade}' {$selected} >{$result->cidade} </option>";
                                        }
                                    }
                                }
                                ?>
                            </select>
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<br />
		<div class='row-fluid tac'>
			<input type="submit" class="btn" name="submit" value="Pesquisar" />
		</div>
	</div>
	<br />
</form>
<?php
	if (isset($_POST['submit']) && pg_num_rows($resVisita) > 0) { ?>
		<table id="checklist_tabela" class='table table-striped table-bordered table-hover table-fixed' >
			<thead>
				<tr class="titulo_tabela">
					<th colspan="5">Relatório de Checklists</th>
				</tr>
				<tr class='titulo_coluna'>
					<th>Nº Checklist</th>
					<th>Código Posto</th>
					<th>Descrição Posto</th>
					<th>Data</th>
					<th>Ações</th>
				</tr>
			</thead>
			<tbody>
				<?php
				while($result = pg_fetch_object($resVisita)) { ?>

					<tr>
						<td class="tac"><a href="checklist_print.php?visita_posto=<?= $result->visita_posto ?>&exibicao=true"><?= $result->visita_posto ?></a></td>
						<td class="tac"><?= $result->codigo_posto ?></td>
						<td><?= $result->nome ?></td>
						<td class="tac"><?= $result->data ?></td>
						<td class="tac">
							<a href="checklist_print.php?visita_posto=<?= $result->visita_posto ?>" target="_blank">
								<button class="btn btn-info btn-small">Imprimir</button>
							</a>
						</td>
					</tr>

				<?php
				}
				?>
			</tbody>
		</table>
<?php
	} else if (isset($_POST['submit'])) { ?>
		<div class="alert alert-warning">
			<h4>Nenhum resultado encontrado</h4>
		</div>
<?php
	}
?>
<script>
	$.dataTableLoad({ table: "#checklist_tabela" });
</script>
<?php
include "rodape.php"; ?>
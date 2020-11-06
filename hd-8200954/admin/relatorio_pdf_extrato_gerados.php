<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";
include "funcoes.php";
$layout_menu = "financeiro";
$title = "Relatório de PDF gerados";
include "cabecalho_new.php";
$plugins = array(
    "autocomplete",
    "shadowbox",
    "datepicker",
    "dataTable",
    "mask"
);

include("plugin_loader.php");
if ($_POST) {
	$data_incial     = $_REQUEST["data_inicial"];
    $data_final      = $_REQUEST["data_final"];
    $extrato         = trim($_REQUEST["extrato"]);
    $codigo_posto  	 = $_REQUEST["codigo_posto"];

    if (((strlen($data_incial) == 0) and (strlen($data_final) == 0)) and (strlen($extrato) == 0)) {
        $msg_erro["msg"][]    = "Preencha os campos obrigatórios, Data e/ou Nota Fiscal";
        $msg_erro["campos"][] = "data";
        $msg_erro["campos"][] = "extrato";
    }

    if (strlen($data_incial) > 0) {
        list($di, $mi, $yi) = explode("/", $data_incial);
        if (!checkdate($mi,$di,$yi)){
            $msg_erro["msg"][]    = "Data Inicial inválida";
            $msg_erro["campos"][] = "data";
        }
    }

    if (strlen($data_final) > 0) {
        list($df, $mf, $yf) = explode("/", $data_final);
        if (!checkdate($mf,$df,$yf)){
            $msg_erro["msg"][]    = "Data Final inválida";
            $msg_erro["campos"][] = "data";
        }
    }

    if (count($msg_erro['msg']) == 0 and strlen($data_incial) > 0 and strlen($data_final) > 0) {
        $aux_data_inicial = "$yi-$mi-$di";
        $aux_data_final = "$yf-$mf-$df";
    }

   if (count($msg_erro['msg']) == 0 and strlen($data_incial) > 0 and strlen($data_final) > 0) {
        if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
            $msg_erro["msg"][]    = "Data Final menor do que a Data Inicial";
            $msg_erro["campos"][] = "data";
        }
    }

    if (count($msg_erro['msg']) == 0 and strlen($data_incial) > 0 and strlen($data_final)>  0 ){
        if (strtotime($aux_data_inicial) < strtotime($aux_data_final . ' - 3 month')) {
            $msg_erro["msg"][]    = "O intervalo entre as datas não pode ser maior do que 3 mês";
            $msg_erro["campos"][] = "data";
        }
    }
    if (count($msg_erro["msg"]) > 0) {
	?>
	    <div class="alert alert-error">
	        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
	    </div>
	<?php
	} else {
		include '../plugins/fileuploader/TdocsMirror.php';
		$tDocs = new TdocsMirror();
		$sql = "SELECT 
					tbl_tdocs.tdocs, 
					tbl_tdocs.tdocs_id, 
					tbl_tdocs.data_input, 
					tbl_tdocs.referencia_id,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome,
                    TO_CHAR(tbl_extrato.data_geracao, 'dd/mm/yyyy') as data_geracao
				FROM tbl_tdocs 
					JOIN tbl_extrato ON tbl_tdocs.referencia_id = tbl_extrato.extrato
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_extrato.posto AND tbl_posto_fabrica.fabrica = tbl_tdocs.fabrica
					JOIN tbl_posto ON tbl_posto.posto = tbl_extrato.posto
				where tbl_tdocs.fabrica = $login_fabrica 
                    AND tbl_tdocs.situacao = 'ativo'
                    AND tbl_tdocs.tdocs_id <> ''
					AND tbl_tdocs.contexto = 'pdf_extrato' ";
		if (strlen($data_inicial) > 0 && strlen($data_final) > 0) {
	        $data_inicialcf = str_replace("'","",fnc_formata_data_pg($data_inicial));
	        $data_finalcf   = str_replace("'","",fnc_formata_data_pg($data_final));    
			$sql .= " AND tbl_tdocs.data_input BETWEEN '$data_inicialcf 00:00:00' and '$data_finalcf 23:59:59' ";
		}
		if (strlen($extrato) > 0) {
			$sql .= " AND tbl_extrato.extrato = {$extrato} AND tbl_tdocs.referencia_id = {$extrato} ";
		}
		if (strlen($codigo_posto) > 0) {
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '{$codigo_posto}' ";
		}
		$resultSql = pg_query($con, $sql);
	}
}
?>
<script type="text/javascript">
	$(function() {
        $.datepickerLoad(Array("data_inicial", "data_final"));
        $.autocompleteLoad(Array("produto", "peca", "posto"));
        Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
		});
	});
</script>

<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_relatorio' method='post' class='form-inline tc_formulario' >
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <div class='row-fluid'>
    </div>
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
            <div class='span6'>
                <div class='control-group <?=(in_array("extrato", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='nf'>Extrato</label>
                    <div class='controls controls-row'>
                        <div class='span6'>
                                <input type="text" name="extrato" id="extrato" size="12" maxlength="10" class='span12' value= "<?=$extrato?>">
                        </div>
                    </div>
                </div>
            </div>    
        <div class='span2'></div>
    </div>
    <p><br/>
        <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));$(this).hide().parents('p').html('Aguarde Processamento')">Pesquisar</button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p><br/>
</form>
<?php 
if (pg_num_rows($resultSql) > 0) {
	?>
	<table id="listagemExtrato" style="margin: 0 auto; width: 100%;" class="tabela_item table table-striped table-bordered table-hover table-large">
		<thead>
			<tr class="titulo_coluna">
                <th>Geração Extrato</th>
				<th>Extrato</th>
				<th>Cód. Posto</th>
				<th>Nome Posto</th>
				<th>Geração</th>
				<th>Ação</th>
			</tr>
		</thead>
		<tbody>
		<?php foreach (pg_fetch_all($resultSql) as $resultado) { 
			$response = $tDocs->get($resultado['tdocs_id']);
		?>
			<tr>
                <td class="tac"><?php echo $resultado['data_geracao'] ?></td>
				<td><?php echo $resultado['referencia_id'];?></td>
				<td><?php echo $resultado['codigo_posto'];?></td>
				<td><?php echo $resultado['nome'];?></td>
				<td><?php echo date_format(date_create($resultado['data_input']), 'd/m/Y');?></td>
				<td><a class="btn btn-primary" target="_blank" href="<?=$response['link']?>">Download</a></td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
	<?php
} else if (((strlen($data_incial) > 0) and (strlen($data_final) > 0)) || (strlen($extrato) > 0)) {?>
<div class="container">
<div class="alert">
        <h4>Nenhum resultado encontrado</h4>
</div>
</div>
<?php } ?>
<script>
	$.dataTableLoad({
	    table : "#listagemExtrato"
	});
</script>

<?php include "rodape.php"; ?>


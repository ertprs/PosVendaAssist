<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';

$osFabrica = "\Posvenda\Fabricas\_".$login_fabrica."\Os";
$oOSClass = new $osFabrica($login_fabrica);

$btn_acao = $_REQUEST["btn_acao"];

if ($btn_acao == 'exportar_ordem') {
    $os = $_REQUEST['os'];
    try {
        try {
            $notaIntegracao = $oOSClass->getDadosNotaExport($os);

            if (!$oOSClass->exportNotificacao($notaIntegracao)) {
                throw new \Exception("Erro ao exportar notificação");
            }
        } catch(\Exception $e) {
            if ($e->getCode() != 200) {
                throw new \Exception($e->getMessage());
            }
        }

        $osIntegracao = $oOSClass->getDadosOSExport($os);

        if (!$oOSClass->exportOS($osIntegracao)) {
            throw new \Exception("Erro ao exportar ordem de serviço");
        }

        $return = array("success" => utf8_encode("Ordem de serviço exportada com sucesso"));
    } catch(\Exception $e) {
        $return = array("error" => utf8_encode($e->getMessage()));
    }

    exit(json_encode($return));
}

if ($btn_acao == 'pesquisar') {
    $data_inicial = $_REQUEST['data_inicial'];
    $data_final = $_REQUEST['data_final'];
    $os = $_REQUEST['os'];
    $codigo_posto = $_REQUEST['codigo_posto'];
    $posto_nome = $_REQUEST['posto_nome'];

    if (strlen($data_inicial) == 0 && strlen($data_final) == 0 && strlen($os) == 0) {
        $msg_erro["msg"][]    = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "data_inicial";
        $msg_erro["campos"][] = "data_final";
    }

    if (count($msg_erro['msg']) == 0 && strlen($os) == 0) {
        if (strlen($data_inicial) == 0) {
            $msg_erro["msg"][]    = "O campo Data Inicial não pode ser vazia";
            $msg_erro["campos"][] = "data_inicial";
        } else {
            $dat = explode ("/", $data_inicial);
            $d = $dat[0];
            $m = $dat[1];
            $y = $dat[2];

            if(!checkdate($m,$d,$y)) {
                $msg_erro["msg"][]    = "O campo Data Inicial não contém uma data válida";
                $msg_erro["campos"][] = "data_inicial";
            } else {
                $aux_data_inicial = $dat[2].'-'.$dat[1].'-'.$dat[0];
            }
        }

        if (strlen($data_final) == 0) {
            $msg_erro["msg"][]    = "O campo Data Final não pode ser vazia";
            $msg_erro["campos"][] = "data_final";
        } else {
            $dat = explode ("/", $data_final);
            $d = $dat[0];
            $m = $dat[1];
            $y = $dat[2];

            if(!checkdate($m,$d,$y)) {
                $msg_erro["msg"][]    = "O campo Data Final não contém uma data válida";
                $msg_erro["campos"][] = "data_final";
            } else {
                $aux_data_final = $dat[2].'-'.$dat[1].'-'.$dat[0];
            }
        }

        if (count($msg_erro['msg']) == 0) {
            if (strtotime($aux_data_inicial) > strtotime($aux_data_final)) {
                $msg_erro["msg"][]    = "O campo Data Inicial é maior que o campo Data Final";
                $msg_erro["campos"][] = "data_inicial";
                $msg_erro["campos"][] = "data_final";
            }
        }
    }

    if (count($msg_erro['msg']) == 0 && (!empty($codigo_posto) || !empty($posto_nome))) {
        if (!empty($codigo_posto)) {
            $sqlPosto = "SELECT * FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND codigo_posto = '{$codigo_posto}';";
            $resPosto = pg_query($con,$sqlPosto);

            if (pg_num_rows($resPosto) > 0) {
                $posto = pg_fetch_result($resPosto, 0, "posto");
            } else {
                $msg_erro["msg"][]    = "Posto não encontrado para a pesquisa";
                $msg_erro["campos"][] = "posto";
            }
        } else {
            $msg_erro["msg"][]    = "Para pesquisar por posto é necessário selecionar através da lupa";
            $msg_erro["campos"][] = "posto";
        }
    }

    if (count($msg_erro['msg']) == 0)  {
        if (strlen($os) > 0) {
            $aux_data_inicial = null;
            $aux_data_final = null;
        }

	    try {
	        $resPdn = $oOSClass->getOsPendenteExportacao($aux_data_inicial, $aux_data_final, $posto, $os);
        	$count = count($resPdn);
	    } catch(\Exception $e) {
		    $msg_erro["msg"][] = $e->getMessage();
	    }
    }
}

$layout_menu = "callcenter";
$title = "Acompanhamento de Integração de Ordem de Serviço";
include "cabecalho_new.php";

$plugins = array(
    "multiselect",
    "lupa",
    "autocomplete",
    "datepicker",
    "mask",
    "dataTable",
    "shadowbox"
);

include "plugin_loader.php"; ?>

<script type="text/javascript" charset="utf-8">
$(function(){
    
    $.datepickerLoad(Array("data_inicial", "data_final"));
    $.autocompleteLoad(Array("posto"));
    Shadowbox.init();
    
    $("span[rel=lupa]").click(function () { $.lupa($(this)); });

    $(document).on('click', '.exportar_os', function() {
        var that = $(this);
        var os = $(this).attr('rel');
        var linha = $("#linha_"+os);

        $(that).text("Exportando Ordem...").prop({ disabled: true });

        $.ajax({
            type: "POST",
            url: "<?= $PHP_SELF; ?>",
            data: { btn_acao: 'exportar_ordem', os: os },
        }).done(function (retorno) {
            retorno = JSON.parse(retorno);
            if (retorno.error == undefined) {
                linha.hide();
                alert(retorno.success);
            } else {
                $(that).text("Exportar").prop({ disabled: false });
                alert(retorno.error);
            }
        });

    });

});

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
    $("#descricao_posto").val(retorno.nome);
}

</script>

<? if (count($msg_erro['msg']) > 0) { ?>
    <div class='alert alert-error'>
        <h4><?= implode("<br />", $msg_erro['msg']); ?></h4>
    </div>
<? }
if (strlen($msg_sucesso) > 0) { ?>
    <div class='alert alert-success'>
        <h4><?= $msg_sucesso; ?></h4>
    </div>
<? } ?>

<div class="row">
    <b class="obrigatorio pull-right">* Campos obrigatórios</b>
</div>
<form name='frm_pedido_manual' method='POST' action='<?= $PHP_SELF; ?>' align='center' class='form-search form-inline tc_formulario'>
    <div class='titulo_tabela'>Parâmetros de Pesquisa</div>
    <br />
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data_inicial", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_inicial'>Data Inicial:</label>
                <div class='controls controls-row'>
                    <h5 class='asteristico'>*</h5>
                    <input type="text" name="data_inicial" id="data_inicial" class="span6" value="<?= $data_inicial; ?>" />
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data_final", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_final'>Data Final:</label>
                <div class='controls controls-row'>
                    <h5 class='asteristico'>*</h5>
                    <input type="text" name="data_final" id="data_final" class="span6" value="<?= $data_final; ?>" />
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?= (in_array("posto", $msg_erro["campos"])) ? "error" : ""; ?>'>
                <label class='control-label' for='codigo_posto'>Código Posto</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" id="codigo_posto" name="codigo_posto" class='span8' maxlength="20" value="<?= $codigo_posto ?>" />
                        <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="referencia" />
                        <input type="hidden" name="posto" value="<?= $posto ?>" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?= (in_array("posto", $msg_erro["campos"])) ? "error" : ""; ?>'>
                <label class='control-label' for='descricao_posto'>Razão Social</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <input type="text" id="descricao_posto" name="posto_nome" class='span12' value="<?= $posto_nome; ?>" />
                        <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span2'>
            <label class='control-label' for='os'>OS</label>
            <div class='controls controls-row'>
                <div class='span12'>
                    <input type="text" id="os" name="os" class='span12' maxlength="20" value="<?= $os; ?>" />
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class="row-fluid tac">
        <input type='hidden' name='btn_acao' id='btn_acao' value='' />
        <input type="button" value="Pesquisar" id='btn_pesquisar' class='btn btn-default' onclick="if ($('#btn_acao').val() == '' ) { $('#btn_acao').val('pesquisar'); $(this).parents('form').submit(); } else { alert('Aguarde submissão'); }" alt="Pesquisar" />
    </div>
</form>

<? if ($btn_acao = 'pesquisar' && $count > 0 && count($msg_erro['msg']) == 0) { ?>
    <table id="resultado_pesquisa" class='table table-striped table-bordered table-hover table-fixed'>
        <thead>
            <tr class='titulo_tabela'>
                <th colspan="4">Ordens com pendência de exportação</th>
            </tr>
            <tr class='titulo_coluna'>
                <th>OS</th>
                <th>Posto</th>
                <th>Notificação</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <? 
	foreach ($resPdn as $v) {
            $resOs = $v["os"];
            $resExp = $v["exportado"];
            $resPosto = $v["posto"]; 
	    ?>
            <tr id="linha_<?= $resOs; ?>">
                <td class="tac"><a href="os_press.php?os=<?= $resOs; ?>" target="_blank"><?= $resOs; ?></a></td>
                <td class="tal"><?= $resPosto; ?></td>
                <td class="tac"><?= $resExp; ?></td>
                <td class="tac" style="vertical-align:middle;">
                    <button class="btn btn-info btn-small exportar_os" rel="<?= $resOs; ?>">Exportar</button>
                </td>
            </tr>
        <? } ?>
        </tbody>
    </table>
    <? if ($count > 50) { ?>
        <script>
            $.dataTableLoad({ table: "#resultado_pesquisa" });
        </script>
    <? }
} else if (count($msg_erro['msg']) == 0 && $btn_acao == 'pesquisar') { ?>
    <div class='alert'>
        <h4>Nenhuma Ordem pendente de exportação encontrada</h4>
    </div>
<? }
include 'rodape.php'; ?>

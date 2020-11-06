<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";

include 'autentica_admin.php';

if ($_POST) {
    $posto              = filter_input(INPUT_POST,'posto_id');
    $codigo_posto       = filter_input(INPUT_POST,'codigo_posto');
    $descricao_posto    = filter_input(INPUT_POST,'descricao_posto');
    $categoria          = filter_input(INPUT_POST,'categoria');

    if (!empty($categoria)) {
        $cond .= "
            AND tbl_posto_fabrica.parametros_adicionais::JSON->>'ultima_categoria' = '$categoria'
        ";
    }

    if (!empty($posto)) {
        $cond .= "
            AND tbl_posto_fabrica.posto = $posto
        ";
    }
}

$layout_menu = "gerencia";
$title = "Relatório de classificação de posto autorizado";

include 'cabecalho_new.php';

$plugins = array(
    "shadowbox",
    "datepicker",
    "mask",
    "datatable_responsive",
    "shadowbox"
);

include("plugin_loader.php");
?>
<script type="text/javascript">
$(function(){
    Shadowbox.init();

    $("span[rel=lupa]").click(function () {
        $.lupa($(this));
    });

});

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
    $("#descricao_posto").val(retorno.nome);
    $("#posto_id").val(retorno.posto);
}


</script>
<div class="container">
    <?php if (count($msg_erro["msg"]) > 0) { ?>
        <div class="alert alert-error">
            <h4><?= implode("<br />", $msg_erro["msg"]); ?></h4>
        </div>
    <?php } ?>
    <div class="container">
        <strong class="obrigatorio pull-right"> * Campos obrigatórios</strong>
    </div>
    <form name='frm_relatorio' method='POST' action='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
        <input type="hidden" id="posto_id" name="posto_id" value="<?= $_POST['posto_id'] ?>" />
        <div class='titulo_tabela'>Parâmetros de Pesquisa</div>
        <br />
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='codigo_posto'>Código Posto</label>
                    <div class='controls controls-row'>
                        <div class='span7 input-append'>
                            <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<?=$codigo_posto?>" >
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
                            <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<?=$descricao_posto?>" >&nbsp;
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
            <div class='span8'>
                <div class='control-group'>
                    <label class='control-label' for='categoria'>Categoria</label>
                    <div class='controls controls-row'>
                        <div class='span12 '>
                            <input type="radio" name="categoria" value="standard" <?=($categoria == "standard") ? "checked" : ""?> />&nbsp;&nbsp;Standard <br />
                            <input type="radio" name="categoria" value="master" <?=($categoria == "master") ? "checked" : ""?> />&nbsp;&nbsp;Master <br />
                            <input type="radio" name="categoria" value="premium" <?=($categoria == "premium") ? "checked" : ""?> />&nbsp;&nbsp;Premium
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <p>
            <br/>
            <button class='btn' id="btn_acao" name="btn_pesquisa">Pesquisar</button>
        </p>
        <br/>
    </form>
</div>
<br />

<table class="table table-bordered table-fixed" id="tabela_postos">
    <thead>
        <tr class="titulo_coluna">
            <th>Posto</th>
            <th>Categoria</th>
            <th>Meses</th>
            <th>Manual</th>
        </tr>
    </thead>
    <tbody>
<?php
    $xls = "POSTO;CATEGORIA;MESES;MANUAL\n";

    $sql = "
        SELECT  tbl_posto.nome,
                tbl_posto_fabrica.codigo_posto,
                tbl_posto_fabrica.parametros_adicionais::JSON->>'ultima_categoria' AS ultima_categoria,
                tbl_posto_fabrica.parametros_adicionais::JSON->'tempo'             AS tempo,
                tbl_posto_fabrica.parametros_adicionais::JSON->>'manual'           AS manual
        FROM    tbl_posto_fabrica
        JOIN    tbl_posto USING(posto)
        WHERE   fabrica = $login_fabrica
        AND     tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
        $cond
  ORDER BY      tbl_posto.nome,
                tbl_posto_fabrica.codigo_posto
    ";

    $res = pg_query($con,$sql);

    while ($results = pg_fetch_object($res)) {
?>
        <tr>
            <td><?=$results->codigo_posto." - ".$results->nome?></td>
            <td><?=strtoupper($results->ultima_categoria)?></td>
            <td><?=$results->tempo?> Meses</td>
            <td><?=($results->manual == TRUE) ? "SIM" : "NÃO" ?></td>
        </tr>
<?php
        $xls .= utf8_encode($results->codigo_posto." - ".$results->nome).";".strtoupper($results->ultima_categoria).";".$results->tempo.";".(($results->manual == TRUE) ? "SIM" : "NAO")."\n";
    }

    $data = date ("dmY");
    $fileName = "relatorio_categoria_posto-{$login_fabrica}-{$data}.csv";

    $file = fopen("/tmp/{$fileName}", "w");
    fwrite($file,$xls);
    fclose($file);

    if (file_exists("/tmp/{$fileName}")) {
        system("mv /tmp/{$fileName} xls/{$fileName}");

        $caminho_xls = "xls/{$fileName}";
    }
?>
    </tbody>
</table>
<br />
<div class="btn_excel" id='gerar_excel'>
    <a role="button" class="btn btn-success" href="<?=$caminho_xls?>" name="gerar_xls" target="_blank">
        <img style="width:40px ; height:40px;" src='imagens/icon_csv.png' />
        <strong>Gerar xls</strong>
    </a>
</div>
<script type="text/javascript">
$(function(){
    $("#tabela_postos").DataTable({
        "language": {
            "lengthMenu": "Qtde _MENU_  por página",
            "search": "Buscar",
            "zeroRecords": "Nenhum resultado encontrado",
            "info": "Visualizando página _PAGE_ de _PAGES_",
            "infoEmpty": "Nenhum resultado encontrado",
            "infoFiltered": "(busca feita pelo total de _MAX_ registros)",
            'paginate': {
                'previous': '<span class="prev-icon"></span>',
                'next': '<span class="next-icon"></span>'
            }
        }
    });
});

</script>

<?php
include "rodape.php";
?>
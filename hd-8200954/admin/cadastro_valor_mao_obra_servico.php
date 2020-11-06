<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "cadastro";
$title = "Parâmetros de Mão de Obra";

include "cabecalho_new.php";

$plugins = array(
    "shadowbox"
);

include("plugin_loader.php");

?>
<script type="text/javascript">

$(function() {
    Shadowbox.init();

    $("a[id^=conf_]").click(function(){
        var aux = $(this).attr("id");
        var split = aux.split("_");
        var familia = split[1];

        Shadowbox.open({
            content:"cadastro_valor_mao_obra_valores.php?familia="+familia,
            player:"iframe",
            modal:true,
            width:800,
            height:600
        });
    });

});
</script>

<?php
    if (strlen($msg) > 0) {
?>
    <div class="alert alert-error">
        <h4><?=$msg?></h4>
    </div>
<?php
}
if (strlen($sucesso) > 0 AND strlen($msg)==0) {
?>
    <div class="alert alert-success">
        <h4><? echo $sucesso; ?></h4>
    </div>
<?php
}
?>
    <form name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>
        <div class='titulo_tabela '>CADASTRO DE MÃO-DE-OBRA POR SERVIÇO REALIZADO</div>
        <br/>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <label class="control-label" ><strong>Família</strong></label>
            </div>
            <div class='span2'></div>
        </div>
<?php
    $sqlFamilia = "
        SELECT
            familia,
            codigo_familia || ' - ' || descricao AS nome_familia
        FROM tbl_familia
        WHERE fabrica = {$login_fabrica}
        AND ativo IS TRUE
        ORDER BY codigo_familia::INT;
    ";
    $resFamilia = pg_query($con,$sqlFamilia);

    while ($dados = pg_fetch_object($resFamilia)) {
?>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control control-row'>
                    <div class='span'>
                        <input type="hidden" name="mao_obra_servico_<?=$dados->familia?>" class="form-control" value="<?=$dados->familia?>" ><?=$dados->nome_familia?></input>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control control-row'>
                    <a class="btn btn-primary" id="conf_<?=$dados->familia?>" role="button">Configurar</a>
                </div>
            </div>
            <div class='span2'></div>
        </div>
<?php
    }
?>
    </form>
</div>
<?php
    include 'rodape.php';
?>

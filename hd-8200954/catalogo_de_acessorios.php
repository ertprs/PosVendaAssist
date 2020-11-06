<? // lorenzetti 19
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include_once 'funcoes.php';
include_once 'helpdesk/mlg_funciones.php';

if ($S3_sdk_OK) {
    include_once S3CLASS;
    $s3 = new anexaS3('ve', (int) $login_fabrica);
    $S3_online = is_object($s3);
}

$listar_tudo = false;

if (isset($_REQUEST["listar_tudo"])) {
    $listar_tudo = true;
}
if ($_REQUEST["acao"] == "PESQUISAR") {

    $condicao_pesquisa = "";
    $xdescricao      = $_POST["descricao"];
    $xdata_inicial   = $_POST["data_inicial"];
    $xdata_final     = $_POST["data_final"];
    $msg_erro        = array();

    if (strlen($xdata_inicial) == 0 && strlen($xdata_inicial) == 0 && strlen($xdescricao) == 0) {
        $msg_erro["msg"][] = traduz("escolha.uma.data.inicial.e.data.final.ou.digite.uma.descricao");
    }

    if (count($msg_erro["msg"]) == 0) {

        if (strlen($xdata_inicial) > 0) {
            $condicao_pesquisa .= " AND tbl_comunicado.data BETWEEN '".formata_data($xdata_inicial)." 00:00:00' AND '".formata_data($xdata_final)." 23:59:59'";
        }      
        if (strlen($xdescricao) > 0)  {
            $condicao_pesquisa .= " AND tbl_comunicado.descricao ILIKE '%$xdescricao%' ";
        }
    }

}
$title = mb_strtoupper(traduz('catalogos.de.acessorios', $con) . ' ' . $login_fabrica_nome);
$layout_menu = "tecnica";

include __DIR__.'/cabecalho_new.php';

$plugins = array(
   "datepicker",
   "maskedinput",
   "alphanumeric",
   "ajaxform",
   "select2",
   "dataTable"
);

include __DIR__.'/admin/plugin_loader.php';

?>

<?php
    if (count($msg_erro["msg"]) > 0) { 
?>
    <div class="alert alert-error">
        <h4><?php echo implode("<br>", $msg_erro["msg"]);?></h4>
    </div>
<?php } ?>
<script>
    $(function() {
        $.datepickerLoad(Array("data_final", "data_inicial"));

    });
</script>
    <div class="row">
        <b class="obrigatorio pull-right">  * <?php echo traduz('campos.obrigatorios');?></b>
    </div>
    <form class='form-search form-inline tc_formulario' name="frm_comunicado" method="post" action="catalogo_de_acessorios.php">
        <input type="hidden" name="acao">
        <div class='titulo_tabela '>
            <?php echo traduz('parametros.de.pesquisa');?>
        </div>
        <br />
        <div class='row-fluid'>
            <div class='span2'></div>
                <div class='span2'>
                    <label class='control-label' for='data_inicial'><?php echo traduz("data.inicial");?></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input class='span12' maxlength="10" type="text" name="data_inicial" id="data_inicial" value="<?=$xdata_inicial?>" onclick="if (this.value == 'dd/mm/aaaa') { this.value=''; }">
                        </div>
                    </div>
                </div>
                <div class='span2'>
                    <label class='control-label' for='data_final'><?php echo traduz("data.final");?></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input class='span12' type="text" name="data_final" id="data_final" maxlength="10" class='Caixa' value="<?=$xdata_final?>" onclick="if (this.value == 'dd/mm/aaaa') { this.value=''; }">
                        </div>
                    </div>
                </div>
            <div class='span2'></div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span8'>
                <label class='control-label' for='data_inicial'><?php echo traduz("descricao.titulo");?></label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <input type="text" name="descricao" class="frm span12" value="<?=$xdescricao?>">
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <br />
        <input type="hidden" name="btn_acao" value="pesquisar" />
        <button class="tac btn" type='submit' style="cursor: pointer;" onClick="document.frm_comunicado.acao.value='PESQUISAR';">
            <?php echo traduz("pesquisar");?>
        </button>
        <a class="tac btn btn-primary" href="<?php echo $_SERVER['PHP_SELF'];?>?listar_tudo=true" style="cursor: pointer;"><?php echo traduz("listar.comunicados");?></a>
        <br /><br />
    </form>
<?php 

if (($_REQUEST["acao"] == "PESQUISAR" && strlen($condicao_pesquisa) > 0) || $listar_tudo == true) {

        $sql = "SELECT tbl_comunicado.comunicado,
                       to_char(tbl_comunicado.data,'dd/mm/yyyy') AS data,
                       tbl_comunicado.tipo,
                       tbl_comunicado.descricao,
                       tbl_comunicado.ativo
                  FROM tbl_comunicado
                 WHERE tbl_comunicado.fabrica = $login_fabrica
                   AND tbl_comunicado.tipo = 'Catálogo de Acessórios'
                 $condicao_pesquisa";
        $res = pg_query($con, $sql);

?>
    <table class="table table-bordered table-striped" style="width: 100% !important" width="100%">
        <thead>
            <tr class="titulo_coluna">
                <th width="15%"><?php echo traduz("data");?></th>
                <th class="tal"><?php echo traduz("descricao");?></th>
                <th width="15%"><?php echo traduz("arquivo");?></th>
            </tr>
        </thead>
        <tbody>
            <?php 
                if (pg_num_rows($res) == 0) {
                    echo ' <tr><td colspan="4">'.traduz("nenhum.catalogo.encontrado").'</td></tr>';
                } else {
                    for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {

                    $descricao         = trim(pg_result ($res,$i,'descricao'));
                    $comunicado        = trim(pg_result ($res,$i,'comunicado'));
                    $data              = trim(pg_result ($res,$i,'data'));
                    $ativo             = trim(pg_result ($res,$i,'ativo'));
                    $linkVE            = "comunicados/$comunicado." . $extensao;

                    if ($S3_online) {
                        if (!$s3->temAnexos($comunicado)):
                            $linkVE = (file_exists($linkVE)) ? $linkVE:'#'; //Deshabilita o link se não existe local
                        else:
                            $linkVE = $s3->url;
                        endif;
                    }
            ?>
            <tr>
                <td class="tac"><?php echo $data;?></td>
                <td class="tal"><?php echo $descricao;?></td>
                <td class="tac"><a href='<?php echo $linkVE;?>' target='_blank' class='btn btn-mini btn-primary'><i class='icon-search icon-white'></i> <?php echo traduz("visualizar");?></a></td>
            </tr>
            <?php }}?>
        </tbody>
    </table>
<?php }?>
<?php include "rodape.php";?>
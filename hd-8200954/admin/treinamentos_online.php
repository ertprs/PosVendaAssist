<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
include_once '../class/AuditorLog.php';

$layout_menu = "cadastro";
$title       = "TREINAMENTOS ONLINE CADASTRADOS";

include 'cabecalho_new.php';

$plugins = array("dataTable", "shadowbox", "mask", "autocomplete");
include("plugin_loader.php");

$codigo_posto = $_POST['codigo_posto'];
$nome_posto   = $_POST['descricao_posto'];

if (!empty($_POST['submit'])) {

    $sql = "SELECT tbl_posto_fabrica.posto
            FROM tbl_posto
            JOIN tbl_posto_fabrica USING(posto)
            WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
            AND UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}')
            ";
    $res = pg_query($con ,$sql);

    if (!pg_num_rows($res)) {
        $msg_erro["msg"][]    = "Posto não encontrado";
        $msg_erro["campos"][] = "posto";
    } else {
        $posto = pg_fetch_result($res, 0, "posto");
    }

    if (count($msg_erro) == 0) {
        $condPosto    = "AND (
            SELECT tbl_treinamento_posto.posto
            FROM tbl_treinamento_posto
            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_treinamento_posto.posto
            AND tbl_posto_fabrica.fabrica = {$login_fabrica}
            WHERE tbl_posto_fabrica.posto = {$posto}
            AND tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
            LIMIT 1
        ) > 0";
    }

}

$sql = "SELECT tbl_treinamento.treinamento,
    tbl_treinamento.titulo,
    tbl_treinamento.ativo,
    tbl_treinamento_tipo.nome AS treinamento_tipo,
    ARRAY_TO_STRING(array_agg(DISTINCT(tbl_linha.nome)), ', ', null) AS linhas,
    ARRAY_TO_STRING(array_agg(DISTINCT(tbl_produto.descricao)), ', ', null) AS produtos,
    tbl_treinamento.linha,
    (
        SELECT COUNT(*)
        FROM tbl_treinamento_posto
        WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
        AND   tbl_treinamento_posto.ativo IS TRUE
    )                                                     AS qtde_postos
FROM tbl_treinamento
    JOIN      tbl_admin   USING(admin)
    LEFT JOIN tbl_treinamento_produto on tbl_treinamento_produto.treinamento = tbl_treinamento.treinamento
    LEFT JOIN tbl_linha on tbl_linha.linha = tbl_treinamento_produto.linha
    LEFT JOIN tbl_produto on tbl_produto.produto = tbl_treinamento_produto.produto
    INNER JOIN tbl_treinamento_tipo ON tbl_treinamento_tipo.treinamento_tipo = tbl_treinamento.treinamento_tipo
WHERE tbl_treinamento.fabrica = $login_fabrica
    AND tbl_treinamento.data_finalizado IS NOT NULL
    AND tbl_treinamento_tipo.nome = 'Online'
    {$condPosto}
GROUP BY tbl_treinamento.treinamento,
    tbl_treinamento.titulo,
    tbl_treinamento.ativo,
    tbl_treinamento_tipo.nome
ORDER BY tbl_treinamento.titulo";
$res      = pg_query($con,$sql);
?>

<script type="text/javascript" src="js/thickbox.js"></script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<script type="text/javascript" src="js/jquery.maskmoney.js"></script>
<script type='text/javascript' src='../js/FancyZoom.js'></script>
<script type='text/javascript' src='../js/FancyZoomHTML.js'></script>

<script type="text/javascript">
$(function(){
    Shadowbox.init();

    $.autocompleteLoad(Array("posto"));
    $("span[rel=lupa]").click(function () {
        $.lupa($(this));
    });

    $(document).on('click', 'a.show_detalhe', function(){
        var url = $(this).data('url');
        Shadowbox.open({
            content: url,
            player: 'iframe',
            width: 1224,
            height: 600
        });
    });
});   

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
    $("#descricao_posto").val(retorno.nome);
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
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
        <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
        <br/>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='codigo_posto'>Código Posto</label>
                    <div class='controls controls-row'>
                        <div class='span7 input-append'>
                            <h5 class='asteristico'>*</h5>
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
                            <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $nome_posto ?>" >&nbsp;
                            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <center>
            <input type="submit" class="btn btn-default" name="submit" value="Pesquisar" />
            <br /><br />
        </center>
</form>
<!-- tabela -->
<table id='tblTreinamento' class='table table-striped table-bordered table-fixed'>
    <thead>
        <tr class='titulo_tabela'><th colspan='5'> Treinamentos Online Agendados</th></tr>
        <tr class='titulo_coluna'>
            <th>Titulo</th>
            <?php if (!in_array($login_fabrica, [175])) { ?>
                    <th>Linhas</th>
            <?php } ?>
            <th>Produtos</th>
            <th>Inscritos</th>
            <th>Ativo</th>
        </tr>
    </thead>
    <tbody>
    <?php
        for ($i=0; $i<pg_num_rows($res); $i++){
            $treinamento       =      pg_fetch_result($res,$i,'treinamento');
            $titulo            = trim(pg_fetch_result($res,$i,'titulo'));
            $treinamento_tipo  = trim(pg_fetch_result($res,$i,'treinamento_tipo'));
            $produtos          = trim(pg_fetch_result($res,$i,'produtos')); 
            $qtde_postos       = trim(pg_fetch_result($res,$i,'qtde_postos'));
            $ativo             = trim(pg_fetch_result($res,$i,'ativo')); 
            
            if (!in_array($login_fabrica, [175])) {
                $linhas        = trim(pg_fetch_result($res,$i,'linhas'));
            }

            $ativo = ($ativo  == 't') ? "<img src='imagens_admin/status_verde.gif' id='tec_img_ativo_$i'>" : "<img src='imagens_admin/status_vermelho.gif' id='tec_img_ativo_$i'>"; ?>

            <tr style="background-color: #F1F4FA">
                <td class="tac"> 
                    <a class='show_detalhe' data-url='detalhes_treinamento.php?treinamento=<?=$treinamento;?>' style='cursor: pointer;'> 
                        <?= $titulo; ?> 
                    </a> 
                </td>
                <?php if (!in_array($login_fabrica, [175])) { ?> 
                        <td class="tac"> <?= $linhas;      ?> </td>
                <?php } ?>
                <td class="tac"> <?= $produtos;    ?> </td>
                <td class="tac"> <?= $qtde_postos; ?> </td>
                <td class="tac"> <?= $ativo;       ?> </td>
            </tr>
<?php   } ?>
    </tbody>
</table>
<!--// tabela -->

<style type="text/css">
.titulo_tabela{
background-color:#596d9b;
font: bold 14px "Arial";
color:#FFFFFF;
text-align:center;
}
.titulo_coluna{
background-color:#596d9b;
font: bold 11px "Arial";
color:#FFFFFF;
text-align:center;
}
.msg_erro{
background-color:#FF0000;
font: bold 16px "Arial";
color:#FFFFFF;
text-align:center;
margin: 0 auto;
}
.texto_avulso{
       font: 14px Arial; color: rgb(89, 109, 155);
       background-color: #d9e2ef;
       text-align: center;
       width:700px;
       margin: 0 auto;
}
.formulario{
background-color:#D9E2EF;
font:11px Arial;
}
.subtitulo{
background-color: #7092BE;
font:bold 11px Arial;
color: #FFFFFF;
}
table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}
.msg_sucesso{
background-color: green;
font: bold 16px "Arial";
color: #FFFFFF;
text-align:center;
width: 700px;
}
.sucesso{
    color:#FFFFFF;
    font:bold 16px "Arial";
    text-align:center;
}
</style>

<? include "rodape.php"; ?>

</body>
</html>
<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$title = "CATEGORIA DE MÃO-DE-OBRA";
$layout_menu = "cadastro";
include 'funcoes.php';
$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';


/**
 * consultaCategoria()
 * - Realiza o carregamento da lista de
 * todas as categorias cadastradas para cálculo
 * individual de mão-de-obra
 *
 * @param $con Conexão com banco de dados
 * @param $login_fabrica ID da fábrica
 * @return Resultado em lista de todas as categorias
 *
 * @author William Ap. Brandino
 */
function consultaCategoria($con,$login_fabrica)
{
    $sql = "SELECT  categoria,
                    descricao,
                    ativo
            FROM    tbl_categoria
            WHERE   fabrica = $login_fabrica
      ORDER BY      categoria
    ";

    $res = pg_query($con,$sql);

    return pg_fetch_all($res);
}

/**
 * gravaCategoria()
 * - Realiza a gravação da categoria
 * para cálculo da mão-de-obra
 *
 * @param $con Conexão com banco de dados
 * @param $login_fabrica ID da fábrica
 * @param $descricao nome da categoria para gravação
 * @return ação de sucesso || erro, caso já exista a categoria
 */
function gravaCategoria($con,$login_fabrica,$descricao)
{
    /*
     * - Necessário verificação de
     * duplicidade de categoria
     */

    $sqlVerifica = "SELECT  count(1)
                    FROM    tbl_categoria
                    WHERE   fabrica     = $login_fabrica
                    AND     descricao   ILIKE '$descricao'
    ";
//     return $sqlVerifica;
    $resVerifica = pg_query($con,$sqlVerifica);
    $verifica = pg_fetch_result($resVerifica,0,0);

    if ($verifica != 0) {
        return json_encode(
            array(
                "ok"        => false,
                "motivo"    => "duplicidade"
            )
        );
    }

    /*
     * - Gravação da categoria
     * após verificação de duplicidade
     */
    pg_query($con,"BEGIN TRANSACTION");
    $sqlGrava = "
        INSERT INTO tbl_categoria (
            fabrica,
            descricao
        ) VALUES (
            $login_fabrica,
            '$descricao'
        )
    ";
    $resGrava = pg_query($con,$sqlGrava);

    if (pg_last_error($con)) {
        pg_query($con,"ROLLBACK TRANSACTION");
        return "erro";
    }

    pg_query($con,"COMMIT TRANSACTION");
    return json_encode(
        array("ok" => true)
    );

}

/**
 * ativaCategoria()
 * - Realiza a ativação / desativação
 * da categoria no cadastro de produtos
 *
 * @param $con Conexão com banco de dados
 * @param $login_fabrica ID da fábrica
 * @param $categoria ID da categoria para atualização
 * @param $ativo Situação da categoria para atualização
 *
 * @return ação de sucesso || erro, caso haja erro na atualização
 */
function ativaCategoria($con,$login_fabrica,$categoria,$ativo)
{
    $ativo = ($ativo == 1)
        ? "TRUE"
        : "FALSE";
    pg_query($con,"BEGIN TRANSACTION");

    $sqlAtt = "
        UPDATE  tbl_categoria
        SET     ativo = $ativo
        WHERE   categoria = $categoria
    ";
//     return $sqlAtt;
    $resAtt = pg_query($con,$sqlAtt);

    if (pg_last_error($con)) {
        pg_query($con,"ROLLBACK TRANSACTION");
        return "erro";
    }

    pg_query($con,"COMMIT TRANSACTION");
    return json_encode(
        array("ok" => true)
    );
}

/**
 * excluiCategoria()
 * - Realiza a excluão da categoria
 * caso não esteja amarrada a um produto
 *
 * @param $con Conexão com banco de dados
 * @param $login_fabrica ID da fábrica
 * @param $categoria ID da categoria para atualização
 *
 * @return ação de sucesso || erro, caso haja amarração com produto || erro, caso haja erro na atualização
 */
function excluiCategoria($con,$login_fabrica,$categoria)
{
    $sqlProduto = "
        SELECT  tbl_produto.produto
        FROM    tbl_produto
        WHERE   tbl_produto.fabrica_i = $login_fabrica
        AND     tbl_produto.categoria = $categoria
    ";
    $resProduto = pg_query($con,$sqlProduto);

    if (pg_num_rows($resProduto) > 0) {
        return json_encode(
            array(
                "ok" => false,
                "motivo" => "Existe produto vinculado com a categoria"
            )
        );
    }

    pg_query($con,"BEGIN TRANSACTION");

    $sqlPre = "
        DELETE
        FROM    tbl_categoria_pais
        WHERE   categoria = $categoria
    ";
    $resPre = pg_query($con,$sqlPre);

    $sql = "
        DELETE
        FROM    tbl_categoria
        WHERE   categoria = $categoria
    ";
    $res = pg_query($con,$sql);

    if (pg_last_error($con)) {
        return "erro".pg_last_error($con);
        pg_query($con,"ROLLBACK TRANSACTION");
    }

    pg_query($con,"COMMIT TRANSACTION");
    return json_encode(
        array("ok"=>true)
    );
}

$ajax = filter_input(INPUT_POST,"ajax",FILTER_VALIDATE_BOOLEAN);

if ($ajax) {
    $acao       = filter_input(INPUT_POST,"acao");
    $categoria  = filter_input(INPUT_POST,"categoria");
    $descricao  = filter_input(INPUT_POST,"descricao");
    $ativo      = filter_input(INPUT_POST,"ativo",FILTER_VALIDATE_BOOLEAN);

    switch ($acao) {
        case "gravaCategoria":
            echo gravaCategoria($con,$login_fabrica,$descricao);
            break;
        case "ativaCategoria":
            echo ativaCategoria($con,$login_fabrica,$categoria,$ativo);
            break;
        case "excluiCategoria":
            echo excluiCategoria($con,$login_fabrica,$categoria);
            break;
    }
    exit;
}

include 'cabecalho_new.php';
$plugins = array(
    "dataTable",
    "shadowbox"
);

include("plugin_loader.php");

?>
<style type="text/css">
    #div_esconde {
        display:none;
    }
</style>

<script type="text/javascript">

$(function(){

    Shadowbox.init();

    $("td[id^=categoria_").css("cursor","pointer");

    $("td[id^=categoria_").click(function(){
        var aux         = this.id.split("_");
        var categoria   = aux[1];
        var url         = "categoria_produto_pais.php?categoria="+categoria;

        Shadowbox.open({
            content: url,
            player: "iframe",
            width: 800,
            height: 600
        });
    });

    $(".btnCategoria").click(function(e){
        e.preventDefault();

        var descricao = $("#categoria_nome").val();

        if (descricao.length > 0) {
            $.ajax({
                url:"categoria_produto.php",
                type:"POST",
                dataType:"JSON",
                data:{
                    ajax:true,
                    acao:"gravaCategoria",
                    descricao:descricao
                }
            })
            .done(function(data){
                if (!data.ok) {
                    $(".alert-error > h4").text("A categoria já foi gravada, não podendo ser repetida");
                    $(".alert-error").show();
                    $(".control-group").first().addClass("error");
                } else {
                    location.reload();
                }
            })
            .fail(function(){
                $(".alert-error > h4").text("Não foi possível realizar a gravação da categoria");
                $(".alert-error").show();
                $(".control-group").first().addClass("error");
            });
        } else {
            $(".alert-error > h4").text("É necessário colocar um nome para a categoria!");
            $(".alert-error").show();
            $(".control-group").first().addClass("error");
        }
    });

    $("input[name^=categoriaAtivo_]").click(function(){
        var aux          = this.name.split("_");
        var categoria    = aux[1];
        var ativo        = this.checked;

        $.ajax({
            url:"categoria_produto.php",
            type:"POST",
            dataType:"JSON",
            data:{
                ajax:true,
                acao:"ativaCategoria",
                categoria:categoria,
                ativo:ativo
            }
        })
        .done(function(data){
            if (data.ok) {
                alert ("Categoria modificada");
            }
        })
        .fail(function(){
            $(".alert-error > h4").text("Não foi possível realizar a alteração da categoria");
            $(".alert-error").show();
        });
    });

    $(".btnApagar").click(function(e){
        e.preventDefault();

        var categoria = $(this).attr("id");

        if (confirm("Tem certeza que deseja excluir a categoria?")) {
            $.ajax({
                url:"categoria_produto.php",
                type:"POST",
                dataType:"JSON",
                data:{
                    ajax:true,
                    acao:"excluiCategoria",
                    categoria:categoria
                }
            })
            .done(function(data){
                if (!data.ok) {
                    $(".alert-error > h4").text(data.motivo);
                    $(".alert-error").show();
                } else {
                    location.reload();
                }
            })
            .fail(function(){
                $(".alert-error > h4").text("Não foi possível excluir a categoria");
                $(".alert-error").show();
            });
        }
    });

});

</script>


<div class='alert alert-error' id="div_esconde">
    <h4><?=$msg_erro?></h4>
</div>
<?php


if (strlen( $msg_sucesso ) > 0) {
?>
<div class="alert alert-success">
    <h4><?=$msg_sucesso?></h4>
</div>

<?php
}
?>

<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_pesquisa' method='POST' action='<?=$PHP_SELF?>' class="form-search form-inline tc_formulario">
<div class="titulo_tabela">Cadastro de Categorias de mão-de-obra</div>

<br />

<div class='row-fluid'>
    <div class='span2'></div>
    <div class="span8">
        <div class="control-group">
            <label class='control-label'>Categoria</label>
            <div class='controls controls-row'>
                <h5 class='asteristico'>*</h5>
                <input type="text" name="categoria_nome" id="categoria_nome" size="12" maxlength="60" class='span4' value= "" />
            </div>
        </div>
    </div>
    <div class="span2"></div>
</div>
<br />
<div class="row-fluid">
    <!-- margem -->
    <div class="span4"></div>

    <div class="span4">
        <div class="control-group">
            <div class="controls controls-row tac">
                <input type="hidden" name="btn_Categoria"  value=''>
                <button class="btnCategoria" name="bt" value='Gravar'>Gravar</button>
            </div>
        </div>
    </div>

    <!-- margem -->
    <div class="span4"> </div>
</div>
</form>

<?php

$categorias = consultaCategoria($con,$login_fabrica);

if (!is_array($categorias)) {
?>
<div class="alert alert-info">
    <h4>Nenhuma categoria cadastrada</h4>
</div>
<?php
} else {
?>
<table class='table table-striped table-bordered table-hover table-fixed'>
    <thead>
        <tr class='titulo_coluna'>
            <th>Descrição</th>
            <th>Ativo</th>
            <th>Ação</th>
        </tr>
    </thead>
    <tbody>
<?php
    foreach ($categorias as $categoria) {
?>
        <tr>
            <td class='tac' id="categoria_<?=$categoria['categoria']?>"><?=$categoria['descricao']?></td>
            <td class='tac'>
                <input type="checkbox" name="categoriaAtivo_<?=$categoria['categoria']?>" <?=($categoria['ativo'] == 't') ? "checked" : ""?> />
            </td>
            <td class='tac'><button class="btn btn-danger btnApagar" id="<?=$categoria['categoria']?>">Apagar</button>
        </tr>
<?php
    }
?>
    </tbody>
</table>

<?php
}
include "rodape.php";
?>

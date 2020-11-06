<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

/**
 * carregaCategoriaPais()
 * - Realiza o carregamento dos valores de mão-de-obra
 * dos países cadastrados de acordo com a categoria
 * pré-estabelecida
 * @param $con Conexão com banco de dados
 * @param $login_fabrica ID da fábrica
 * @param $categoria ID da categoria
 * @return Resultado em lista de todos os valores de mão-de-obra
 *
 * @author William Ap. Brandino
 */
function consultaCategoriaPais($con,$login_fabrica,$categoria)
{
    $sqlBusca = "
        SELECT  tbl_categoria_pais.categoria_pais                           ,
                tbl_categoria.descricao             AS categoria_descricao  ,
                tbl_pais.nome                       AS pais_nome            ,
                tbl_categoria_pais.mao_de_obra      AS valor_mao_obra
        FROM    tbl_categoria_pais
        JOIN    tbl_categoria   USING(fabrica,categoria)
        JOIN    tbl_pais        USING(pais)
        WHERE   fabrica     = $login_fabrica
        AND     categoria   = $categoria
  ORDER BY      tbl_pais.nome
    ";
    $resBusca = pg_query($con,$sqlBusca);

    return pg_fetch_all($resBusca);
}

/**
 * gravaCategoriaPais()
 * - Realiza a gravação do valor
 * da mão-de-obra por país
 * @param $con Conexão com banco de dados
 * @param $login_fabrica ID da fábrica
 * @param $categoria ID da categoria
 * @param $pais ID do país
 * @param $valor_mao_obra valor da mão-de-obra operado pelo país, de acordo com a categoria
 *
 * @return ação de sucesso || erro, caso já exista valor para aquele país
 */
function gravaCategoriaPais($con,$login_fabrica,$categoria,$pais,$valor_mao_obra)
{
    $valor_mao_obra_gravar = str_replace('.','',$valor_mao_obra);
    $valor_mao_obra_gravar = str_replace(',','.',$valor_mao_obra_gravar);
    /*
     * - Evitando duplicidade de valor
     * por país
     */
    $sqlVerifica = "
        SELECT  COUNT(1)
        FROM    tbl_categoria_pais
        WHERE   pais        = '$pais'
        AND     categoria   = $categoria
    ";
    $resVerifica = pg_query($con,$sqlVerifica);
    $verifica = pg_fetch_result($resVerifica,0,0);

    if ($verifica > 0) {
        return json_encode(
            array(
                "ok"        => false,
                "motivo"    => "duplicidade"
            )
        );
    }

    /*
     * - Gravação do valor da mão-de-obra
     * após verificação de duplicidade
     */
    pg_query($con,"BEGIN TRANSACTION");

    $sqlGrava = "
        INSERT INTO tbl_categoria_pais (
            fabrica,
            pais,
            categoria,
            mao_de_obra
        ) VALUES (
            $login_fabrica,
            '$pais',
            $categoria,
            $valor_mao_obra_gravar
        );
    ";
//     return $sqlGrava;
    $resGrava = pg_query($con,$sqlGrava);

    if (pg_last_error($con)) {
        $aux = pg_last_error($con);
        pg_query($con,"ROLLBACK TRANSACTION");
        return "erro: ".$aux;
    }

    pg_query($con,"COMMIT TRANSACTION");
    return json_encode(
        array("ok" => true)
    );
}

/**
 * excluiCategoriaPais
 * - Faz a exclusão do valor de mão-de-obra
 * da categoria selecionada
 */
function excluiCategoriaPais($con,$login_fabrica,$categoriaPais)
{
    pg_query($con,"BEGIN TRANSACTION");

    $sql = "
        DELETE
        FROM    tbl_categoria_pais
        WHERE   categoria_pais = $categoriaPais
    ";
    $res = pg_query($con,$sql);

    if (pg_last_error($con)) {
        pg_query($con,"ROLLBACK TRANSACTION");
        return "erro";
    }

    pg_query($con,"COMMIT TRANSACTION");
    return json_encode(
        array("ok"=>true)
    );
}

$categoria  = filter_input(INPUT_GET,'categoria',FILTER_VALIDATE_INT);
$ajax       = filter_input(INPUT_POST,"ajax",FILTER_VALIDATE_BOOLEAN);

if ($ajax) {
    $acao           = filter_input(INPUT_POST,"acao");
    $categoria      = filter_input(INPUT_POST,"categoria");
    $categoriaPais  = filter_input(INPUT_POST,"categoria_pais");
    $pais           = filter_input(INPUT_POST,"pais");
    $valor_mao_obra = filter_input(INPUT_POST,"valor_mao_obra");

    switch ($acao) {
        case "gravaCategoriaPais":
            echo gravaCategoriaPais($con,$login_fabrica,$categoria,$pais,$valor_mao_obra);
            break;
        case "excluiCategoriaPais":
            echo excluiCategoriaPais($con,$login_fabrica,$categoriaPais);
            break;
    }
    exit;
}
?>
<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
<link href="../../css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/bootstrap/js/bootstrap.min.js"></script>
<script src="plugins/price_format/jquery.price_format.1.7.min.js"></script>
<script src="plugins/price_format/config.js"></script>
<script src="plugins/price_format/accounting.js"></script>

<div class="titulo_tabela">Categorias por País</div>

<style type="text/css">
    #valor_mao_obra {
        text-align:right;
    }
    #principal {
        overflow: hidden;
    }
</style>

<script type="text/javascript">

$(function(){

    $(".btnCategoriaPais").click(function(e){

        e.preventDefault();

        var pais            = $("#pais").val();
        var valor_mao_obra  = $("#valor_mao_obra").val();
        var categoria       = <?=$categoria?>;

        if (valor_mao_obra.length > 0) {
            $.ajax({
                url:"categoria_produto_pais.php",
                type:"POST",
                dataType:"JSON",
                data:{
                    ajax:true,
                    acao:"gravaCategoriaPais",
                    categoria:categoria,
                    pais:pais,
                    valor_mao_obra:valor_mao_obra
                }

            })
            .done(function(data){
                if (data.ok) {
                    location.reload();
                } else {
                    alert("Já existe cadastro de valor de mão-de-obra para este país e categoria");
                }
            })
            .fail(function(){
                alert("Não foi possível gravar o valor de mão-de-obra");
            });
        } else {
            alert("Necessário um valor para cadastro da mão-de-obra!!");
        }
    });

    $(".btnApagar").click(function(e){
        e.preventDefault();

        var categoria_pais = $(this).attr("id");

        $.ajax({
            url:"categoria_produto_pais.php",
            type:"POST",
            dataType:"JSON",
            data:{
                ajax:true,
                acao:"excluiCategoriaPais",
                categoria_pais:categoria_pais
            }
        })
        .done(function(data){
            if (data.ok) {
                location.reload();
            }
        })
        .fail(function(){
            alert("Não foi possível excluir o valor de mão-de-obra");
        });
    });
});
</script>
<br />

<div class='row-fluid'>
    <div class='span2'></div>
    <div class="span4">
        <div class="control-group">
            <label class='control-label'>País</label>
            <select id="pais" name="pais">
                <option value="">&nbsp;</option>
<?php
    $sql = "
        SELECT  DISTINCT
                tbl_pais.nome,
                tbl_pais.pais
        FROM    tbl_pais
  ORDER BY      tbl_pais.nome;
    ";
    $res = pg_query($con,$sql);
    $paises = pg_fetch_all($res);
    foreach($paises as $pais){
?>
                <option value="<?=$pais['pais']?>"><?=$pais['nome']?></option>
<?
    }
?>
            </select>
        </div>
    </div>
    <div class="span4">
        <div class="controlgrup">
            <label class='control-label'>Valor mão-de-obra</label>
            <div class='controls controls-row'>
                <input type="text" price="true" name="valor_mao_obra" id="valor_mao_obra" size="12" maxlength="10" class='span4' value= "<?=$data_inicial?>">
            </div>
        </div>
    </div>
    <div class='span2'></div>
</div>
<div class="row-fluid">
    <!-- margem -->
    <div class="span4"></div>

    <div class="span4">
        <div class="control-group">
            <div class="controls controls-row tac">
                <input type="hidden" name="btn_Categoria_Pais"  value=''>
                <button class="btnCategoriaPais" name="bt" value='Gravar'>Gravar</button>
            </div>
        </div>
    </div>

    <!-- margem -->
    <div class="span4"> </div>
</div>

<?php

$categoriaPais = consultaCategoriaPais($con,$login_fabrica,$categoria);
if (!is_array($categoriaPais)) {
?>
<div class="alert alert-info">
    <h4>Nenhum valor cadastrado</h4>
</div>
<?php
} else {

?>
<table class='table table-striped table-bordered table-hover table-fixed'>
    <thead>
        <tr class="titulo_coluna">
            <th colspan="3">Valores para a categoria: <?=$categoriaPais[0]['categoria_descricao']?></th>
        </tr>
        <tr class='titulo_coluna'>
            <th>País</th>
            <th>Valor</th>
            <th>Ação</th>
        </tr>
    </thead>
    <tbody>
<?php
    foreach ($categoriaPais as $cadaPais) {
        $valorArray = $cadaPais['valor_mao_obra'];
?>
        <tr>
            <td class='tac' ><?=$cadaPais['pais_nome']?></td>
            <td class='tar'><?=number_format($valorArray,2,',','')?></td>
            <td class='tac'><button class="btn btn-danger btnApagar" id="<?=$cadaPais['categoria_pais']?>">Apagar</button>
        </tr>
<?php
    }
?>
    </tbody>
</table>

<?php
}
?>

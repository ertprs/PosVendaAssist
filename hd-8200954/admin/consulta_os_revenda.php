<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

if (filter_input(INPUT_POST,"btn_acao") == "submit") {

    $data_inicial       = filter_input(INPUT_POST,"data_inicial");
    $data_final         = filter_input(INPUT_POST,"data_final");
    $sua_os             = filter_input(INPUT_POST,"sua_os");
    $serie              = filter_input(INPUT_POST,"serie");
    $codigo_posto       = filter_input(INPUT_POST,"codigo_posto");
    $descricao_posto    = filter_input(INPUT_POST,"descricao_posto");
    $revenda_nome       = filter_input(INPUT_POST,"revenda_nome");
    $revenda_cnpj       = filter_input(INPUT_POST,"revenda_cnpj");
    $produto            = filter_input(INPUT_POST,"produto");
    $produto_referencia = filter_input(INPUT_POST,"produto_referencia");
    $produto_descricao  = filter_input(INPUT_POST,"produto_descricao");
    $produto_voltagem   = filter_input(INPUT_POST,"produto_voltagem");
    $tipo_atendimento   = filter_input(INPUT_POST,"tipo_atendimento");

    if (empty($sua_os) && empty($serie)) {
        if (!strlen($data_inicial) || !strlen($data_final)) {
            $msg_erro["msg"][]    = traduz("Preencha os campos obrigatórios");
            $msg_erro["campos"][] = "data";
        } else {
            list($di, $mi, $yi) = explode("/", $data_inicial);
            list($df, $mf, $yf) = explode("/", $data_final);

            if (!checkdate($mi, $di, $yi) || !checkdate($mf, $df, $yf)) {
                $msg_erro["msg"][]    = traduz("Data Inválida");
                $msg_erro["campos"][] = "data";
            } else {

                $aux_data_inicial = "{$yi}-{$mi}-{$di}";
                $aux_data_final   = "{$yf}-{$mf}-{$df}";

                if (strtotime($aux_data_inicial."+3 months" ) < strtotime($aux_data_final)) {
                    $msg_erro["msg"][]    = traduz("Intervalo de pesquisa não pode ser no do que 3 meses.");
                    $msg_erro["campos"][] = "data";
                }

                if (strtotime($aux_data_inicial) > strtotime($aux_data_final)) {
                    $msg_erro["msg"][]    = traduz("Datas estão invertidas.");
                    $msg_erro["campos"][] = "data";
                }
            }
        }

        if (strlen($revenda_cnpj) > 0 && strlen($revenda_nome) > 0) {
            $sql = "SELECT  revenda ,
                            cnpj ,
                            nome
                    FROM    tbl_revenda
                    WHERE   cnpj = '$revenda_cnpj';";
            $res = pg_query($con,$sql);
            if (pg_numrows($res) == 1) {
                $revenda      = pg_result($res,0,revenda);
                $revenda_cnpj = pg_result($res,0,cnpj);
                $revenda_nome = pg_result($res,0,nome);
            } else {
                $msg_erro["msg"][] = traduz("Revenda não encontrada. ");
                $msg_erro["campos"][] = "revenda";
            }
        }

        if (!empty($produto_referencia) && !empty($produto_descricao)) {
            $sql =	"SELECT tbl_produto.produto    ,
                            tbl_produto.referencia ,
                            tbl_produto.descricao  ,
                            tbl_produto.voltagem
                    FROM tbl_produto
                    JOIN tbl_linha USING (linha)
                    WHERE tbl_linha.fabrica    = $login_fabrica
                    AND   tbl_produto.referencia = '$produto_referencia'";
            $res = pg_query($con,$sql);
            if (pg_num_rows($res) == 1) {
                $produto            = pg_result($res,0,produto);
                $produto_referencia = pg_result($res,0,referencia);
                $produto_descricao  = pg_result($res,0,descricao);
                $produto_voltagem   = pg_result($res,0,voltagem);
            } else {
                $msg_erro["msg"][] = traduz("produto não encontrado");
                $msg_erro["campos"][] = "produto";
            }
        }

        if (strlen($codigo_posto) > 0 && strlen($descricao_posto) > 0) {
            $sql = "SELECT  tbl_posto_fabrica.posto        ,
                            tbl_posto_fabrica.codigo_posto ,
                            tbl_posto.nome
                    FROM    tbl_posto
                    JOIN    tbl_posto_fabrica USING (posto)
                    WHERE   tbl_posto_fabrica.fabrica       = $login_fabrica
                    AND     tbl_posto_fabrica.codigo_posto  = '$codigo_posto';";
            $res = pg_query($con,$sql);
            if (pg_numrows($res) == 1) {
                $posto        = pg_result($res,0,posto);
                $codigo_posto = pg_result($res,0,codigo_posto);
                $descricao_posto   = pg_result($res,0,nome);
            } else {
                $msg_erro["msg"][] = traduz(" Posto não encontrado. ");
                $msg_erro["campos"][] = "posto";
            }
        }
    } else {
        if (!empty($sua_os)) {
            if (strlen($sua_os) > 0 && strlen($sua_os) < 3) {
                $msg_erro["msg"][] = traduz(" Digite o número da OS com o mínimo de 3 números. ");
                $msg_erro["campos"][] = "sua_os";
            }
        } else if (!empty($serie)) {
            if (strlen($serie) > 0 && strlen($serie) < 3) {
                $msg_erro["msg"][] = traduz(" Digite o número da série com o mínimo de 3 números. ");
                $msg_erro["campos"][] = "serie";
            }
        }
    }

}

$layout_menu = "callcenter";
$title= traduz("RELAÇÃO DE ORDENS DE SERVIÇO DE REVENDA LANÇADAS");
include "cabecalho_new.php";
$plugins = array(
    "datepicker",
    "mask",
    "alphanumeric",
    "dataTable",
    "shadowbox",
    "multiselect"
);
include("plugin_loader.php");

?>

<script type="text/javascript">
$(function() {
    $.datepickerLoad(Array("data_final", "data_inicial"));
//     $("#sua_os").numeric({ allow: "-"});
    Shadowbox.init();

    $('.imprimir_etiqueta').on('click', function() {
        os = $(this).data('os');
        Shadowbox.open({
            content :   "imprimir_etiqueta.php?sua_os=" + os,
            player  :   "iframe",
            title   :   '<?=traduz("Etiqueta")?>',
            width   :   800,
            height  :   600
        });
    });

    $("span[rel=lupa]").click(function () {
        $.lupa($(this));
    });
});

function retorna_posto (retorno){
    $("#codigo_posto").val(retorno.codigo);
    $("#descricao_posto").val(retorno.nome);
}

function retorna_produto (retorno) {
    $("#produto").val(retorno.produto);
    $("#produto_referencia").val(retorno.referencia);
    $("#produto_descricao").val(retorno.descricao);
}

function retorna_revenda(retorno) {
    $("#revenda_nome").val(retorno.razao);
    $("#revenda_cnpj").val(retorno.cnpj);
}
</script>
<style type="text/css">
#resultado_os {
    width:100%;
}
</style>
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
    <b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
</div>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
    <div class ='titulo_tabela'><?=traduz('Parametros de Pesquisa')?> </div>
    <br />
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_inicial'><?=traduz('Data Inicial')?></label>
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
                <label class='control-label' for='data_final'><?=traduz('Data Final')?></label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <h5 class='asteristico'>*</h5>
                            <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12 text-center' value= "<?=$data_final?>">
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>

    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("sua_os", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='sua_os'><?=traduz('OS Revenda')?></label>
                <div class='controls controls-row'>
                    <div class='span6'>
                        <input type="text" name="sua_os" id="sua_os" class='span12 numeric' value= "<?=$sua_os?>">
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("serie", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='serie'><?=traduz('Número Série')?></label>
                <div class='controls controls-row'>
                    <div class='span6'>
                        <input type="text" name="serie" id="serie" class='span12 numeric' value= "<?=$serie?>">
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
                <label class='control-label' for='codigo_posto'><?=traduz('Código Posto')?></label>
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
                <label class='control-label' for='descricao_posto'><?=traduz('Nome Posto')?></label>
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
        <div class="span4">
            <div class='control-group <?=(in_array('revenda', $msg_erro['campos'])) ? "error" : "" ?>' >
                <label class="control-label" for="revenda_cnpj"><?=traduz('CNPJ')?></label>
                <div class="controls controls-row">
                    <div class="span10 input-append">
                        <input id="revenda_cnpj" name="revenda_cnpj" class="span12" type="text" value="<?=$revenda_cnpj?>" />
                        <span class="add-on" rel="lupa" >
                            <i class="icon-search"></i>
                        </span>
                        <input type="hidden" name="lupa_config" tipo="revenda" parametro="cnpj" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span4">
            <div class='control-group <?=(in_array('revenda', $msg_erro['campos'])) ? "error" : "" ?>' >
                <label class="control-label" for="revenda_nome"><?=traduz('Nome Revenda')?></label>
                <div class="controls controls-row">
                    <div class="span12 input-append">

                        <input id="revenda_nome" name="revenda_nome" class="span12" type="text" maxlength="50" value="<?=$revenda_nome?>" />
                        <span class="add-on" rel="lupa" >
                            <i class="icon-search"></i>
                        </span>
                        <input type="hidden" name="lupa_config" tipo="revenda" parametro="razao_social" />
                    </div>
                </div>
            </div>
        </div>

        <div class='span2'></div>
    </div>

    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array('produto', $msg_erro['campos'])) ? "error" : "" ?>'>
                <label class='control-label' for='produto_referencia'><?=traduz('Ref. Produto')?></label>
                <div class='controls controls-row'>
                    <div class='span10 input-append'>
                        <input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<?=$produto_referencia?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
                        <input type="hidden" name="produto" id="produto" value="<?=$produto?>" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array('produto', $msg_erro['campos'])) ? "error" : "" ?>'>
                <label class='control-label' for='produto_descricao'><?=traduz('Descrição Produto')?></label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<?=$produto_descricao?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>

    <?php
    if (in_array($login_fabrica, [169, 170])){ ?>

        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='produto_referencia'><?=traduz('Tipo de Atendimento')?></label>
                    <div class='controls controls-row'>
                        <div class='span10 input-append'>
                            <select id="tipo_atendimento" name="tipo_atendimento">
                                <option value="">Selecione</option>
                                <?php
                                $sqlTipoAtendimento = "SELECT tipo_atendimento, descricao
                                                       FROM tbl_tipo_atendimento
                                                       WHERE fabrica = {$login_fabrica}
                                                       AND ativo
                                                       AND grupo_atendimento = 'R'";
                                $resTipoAtendimento = pg_query($con, $sqlTipoAtendimento);

                                while ($dados = pg_fetch_object($resTipoAtendimento)) {

                                    $selected = ($dados->tipo_atendimento == $tipo_atendimento) ? "selected" : "";

                                    echo "<option value='{$dados->tipo_atendimento}' {$selected}>{$dados->descricao}</option>";

                                }

                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>

    <?php
    } ?>

    <p><br />
        <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));"><?=traduz('Pesquisar')?></button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p><br />
</form>
<?php
if (filter_input(INPUT_POST,"btn_acao") == "submit") {
    if (count($msg_erro["msg"]) == 0) {
        if (empty($sua_os) && empty($serie)) {

            $condData = " AND tbl_os_revenda.digitacao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'";

            if (strlen($revenda) > 0) {
                $condRevenda = " AND tbl_os_revenda.revenda = $revenda";
            }

            if (strlen($posto) > 0) {
                $condPosto = " AND tbl_os_revenda.posto = $posto";
            }

            if (strlen($produto) > 0) {
                $condProduto = " AND tbl_os_revenda_item.produto = $produto";
            }

        } else {
            if (!empty($sua_os)) {
                $numero_os = explode("-",$sua_os);
                $condOs = " AND (tbl_os_revenda.sua_os LIKE '%".$numero_os[0]."%' OR tbl_os_revenda.os_revenda = ".$numero_os[0].")";
            } else if (!empty($serie)) {
                $condSerie = "AND tbl_os_revenda_item.serie LIKE '%$serie%'";
            }
        }

        if (!empty($tipo_atendimento)) {

            if ($tipo_atendimento == 305) {
                $orReoperacao = "OR (
                    SELECT COUNT(*)
                    FROM tbl_os 
                    WHERE tbl_os.sua_os LIKE tbl_os_revenda.os_revenda || '-%' 
                    AND tbl_os.fabrica = {$login_fabrica}
                    AND tbl_os.tipo_atendimento = {$tipo_atendimento}
                ) > 0 ";
            }

            $condTipoAtendimento = "AND (tbl_os_revenda_item.tipo_atendimento = {$tipo_atendimento} {$orReoperacao})";
        }

        $sql = "
            SELECT  DISTINCT
                    tbl_os_revenda.os_revenda                                               ,
                    COALESCE(tbl_os_revenda.sua_os, tbl_os_revenda.os_revenda::text) as sua_os    ,
                    tbl_os_revenda.explodida                                                ,
                    (
                        SELECT  COUNT(tbl_os_revenda_item.*)
                        FROM    tbl_os
                        JOIN    tbl_os_revenda_item ON tbl_os_revenda_item.os_lote = tbl_os.os
                        WHERE   tbl_os_revenda_item.os_revenda = tbl_os_revenda.os_revenda
                    )                                                   AS qtde_explodida   ,
                    TO_CHAR(tbl_os_revenda.data_abertura,'DD/MM/YYYY')  AS abertura         ,
                    tbl_os_revenda.revenda                                                  ,
                    tbl_os_revenda.posto                                                    ,
                    tbl_revenda.cnpj                                    AS revenda_cnpj     ,
                    tbl_revenda.nome                                    AS revenda_nome     ,
                    tbl_posto_fabrica.tipo_posto     ,
                    tbl_posto.nome as nome_posto
            FROM    tbl_os_revenda
       LEFT JOIN    tbl_os_revenda_item ON  tbl_os_revenda_item.os_revenda  = tbl_os_revenda.os_revenda
       LEFT JOIN    tbl_produto         ON  tbl_produto.produto             = tbl_os_revenda_item.produto
       LEFT JOIN    tbl_revenda         ON  tbl_revenda.revenda             = tbl_os_revenda.revenda
            JOIN    tbl_posto           ON  tbl_posto.posto                 = tbl_os_revenda.posto
            JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto         = tbl_posto.posto
                                        AND tbl_posto_fabrica.fabrica       = $login_fabrica
            WHERE   tbl_os_revenda.fabrica          = $login_fabrica
            AND     tbl_os_revenda.os_manutencao    IS NOT TRUE
            $condData
            $condRevenda
            $condPosto
            $condProduto
            $condOs
            $condSerie
            $condTipoAtendimento
      ORDER BY      tbl_os_revenda.os_revenda DESC
        ";
        
        $res = pg_query($con,$sql);

        if (pg_num_rows($res) > 0) {
?>
</div>
<table id="resultado_os" class = 'table table-striped table-bordered table-hover table-large'>
    <thead>
        <tr class = 'titulo_coluna'>
            <th>OS</th>
            <th><?=traduz('Data')?></th>
            <th><?=traduz('Revenda')?></th>
            <th><?=traduz('Posto')?></th>
            <th><?=traduz('Ações')?></th>
        </tr>
    </thead>
    <tbody>
<?php
            while ($resultado = pg_fetch_object($res)) {
                $os_revenda     = $resultado->os_revenda;
                $sua_os         = $resultado->sua_os;
                $explodida      = $resultado->explodida;
                $qtde_explodida = $resultado->qtde_explodida;
                $abertura       = $resultado->abertura;
                $revenda        = $resultado->revenda;
                $revenda_cnpj   = $resultado->revenda_cnpj;
                $revenda_nome   = $resultado->revenda_nome;
                $posto          = $resultado->posto;
                $nome_posto     = $resultado->nome_posto;
                $tipo_posto     = $resultado->tipo_posto;
?>
        <tr>
            <td>
<?php
                if (!empty($explodida)) {
                    if (isset($novaTelaOsRevenda)) {
                        echo "<a href='os_revenda_press.php?os_revenda=$os_revenda' target='_blank'>".$sua_os."</a></td>";
                    } else {
                        echo "<A HREF='os_revenda_explodida.php?sua_os=$sua_os' target='_blank'>".$sua_os."</A>"."</td>";
                    }
                } else {
                    echo $sua_os;
            }
?>
            </td>
            <td><?=$abertura?></td>
            <td nowrap><?=$revenda_cnpj." - ".$revenda_nome?></td>
            <td nowrap><?=$nome_posto?></td>
            <td>
            <?php 
                if (!in_array($login_fabrica, [169,170])) {
            ?>
                    <?=($qtde_explodida == 0 && empty($explodida)) ? "<a class='btn btn-primary' href='os_revenda.php?os_revenda=$os_revenda' target='_blank'>Alterar</a>" : ""?>
                    <?=(empty($explodida)) ? "<a class='btn btn-warning' href='os_revenda_finalizada.php?os_revenda=$os_revenda&btn_acao=explodir' target='_blank'>Explodir</a>" : ""?>
                    <a class="btn btn-success" href="os_revenda_print.php?os_revenda=<?=$os_revenda?>" target="_blank">Imprimir</a>
                    <?php if (in_array($login_fabrica, [173])) { ?>
                        <button class="btn btn-success imprimir_etiqueta" data-os="<?=$sua_os?>">Imprimir Etiqueta</button>
                    <?php 
                    } 
                } else { ?>

                    <a href="os_revenda_press.php?os_revenda=<?= $os_revenda ?>" class="btn btn-primary" target="_blank">Consultar</a>

                <?php
                }
            ?>
            <?=($qtde_explodida == 0 && empty($explodida) && !in_array($login_fabrica, [169,170])) ? "<a class='btn btn-primary' href='os_revenda.php?os_revenda=$os_revenda' target='_blank'>Alterar</a>" : ""?>
            <?=(empty($explodida) && !in_array($login_fabrica, [169,170])) ? "<a class='btn btn-warning' href='os_revenda_finalizada.php?os_revenda=$os_revenda&btn_acao=explodir' target='_blank'>Explodir</a>" : ""?>
            <a class="btn btn-success" href="os_revenda_print.php?os_revenda=<?=$os_revenda?>" target="_blank">Imprimir</a>
            <?php if (in_array($login_fabrica, [173])) { ?>
                <button class="btn btn-success imprimir_etiqueta" data-os="<?=$sua_os?>"><?=traduz('Imprimir Etiqueta')?></button>
            <?php } ?>
            </td>
        </tr>
<?php
            }
?>
    </tbody>
</table>

<script type="text/javascript">
    $.dataTableLoad({
        table: "#resultado_os",
        type:"basic"
    });
</script>
<?php
        } else {
?>
<div class="container">
    <div class="alert">
        <h4><?=traduz('Nenhum resultado encontrado')?></h4>
    </div>
</div>
<?
        }
    }
}
?>
<? include "rodape.php" ?>

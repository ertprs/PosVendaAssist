<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';
include "funcoes.php";

if (filter_input(INPUT_POST,"acao")) {

    $data_inicial       = filter_input(INPUT_POST,"data_inicial");
    $data_final         = filter_input(INPUT_POST,"data_final");
    $codigo_posto       = filter_input(INPUT_POST,"codigo_posto");
    $descricao_posto    = filter_input(INPUT_POST,"descricao_posto");
    $admin              = filter_input(INPUT_POST,"admin");
    $os                 = filter_input(INPUT_POST,"os",FILTER_VALIDATE_INT);
    $tipo_busca         = filter_input(INPUT_POST,"tipo_busca");

    if (strlen($data_inicial) > 0 OR strlen($data_final) > 0) {
        if (!$aux_data_inicial = dateFormat($data_inicial, 'dmy')) {
            $msg_erro["msg"][]    = "Data Inválida";
            $msg_erro["campos"][] = "data";
        } else if (!$aux_data_final = dateFormat($data_final, 'dmy')) {
            $msg_erro["msg"][]    = "Data Inválida";
            $msg_erro["campos"][] = "data";
        } else {
            if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
                $msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
                $msg_erro["campos"][] = "data";
            }
        }
    }

    if (empty($tipo_busca)) {
        $msg_erro["msg"][] = "Favor preencher os campos obrigatórios";
        $msg_erro["campos"][] = "tipo_busca";
    }

    $cond = "";

    if (!empty($codigo_posto)) {
        $cond .= "
            AND tbl_posto_fabrica.codigo_posto = '$codigo_posto'
        ";
    }

    if (!empty($admin)) {
        $cond .= "
            AND tbl_auditoria_os.admin = $admin
        ";
    }
    if (!empty($os)) {
        $cond .= "
            AND tbl_os.os = $os
        ";
    }

    /*
     * - Faz-se a TEMP dos dados comuns
     * aos dois tipos da busca
     */
    $sqlTemp = "
        SELECT  tbl_os.os                                                           ,
                tbl_os_produto.os_produto                                           ,
                tbl_os_produto.produto                                              ,
                TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')      AS data_abertura    ,
                tbl_posto_fabrica.codigo_posto                                      ,
                tbl_posto.nome                                  AS nome_posto       ,
                tbl_posto.cidade                                AS cidade_posto     ,
                tbl_posto.estado                                AS estado_posto     ,
                tbl_admin.nome_completo                         AS admin_aprovou    ,
                TO_CHAR(tbl_auditoria_os.liberada,'DD/MM/YYYY') AS data_aprovacao
   INTO TEMP    tmp_dados_comuns_busca
        FROM    tbl_os
        JOIN    tbl_os_produto      USING(os)
        JOIN    tbl_posto           USING(posto)
        JOIN    tbl_posto_fabrica   USING(posto,fabrica)
        JOIN    tbl_auditoria_os    USING(os)
        JOIN    tbl_admin           ON tbl_admin.admin = tbl_auditoria_os.admin
        WHERE   tbl_os.fabrica = $login_fabrica
        AND     tbl_auditoria_os.liberada IS NOT NULL
        AND     tbl_auditoria_os.liberada::DATE BETWEEN '$aux_data_inicial' AND '$aux_data_final'
        $cond
    ";
//     exit(nl2br($sqlTemp));
    $resTemp = pg_query($con,$sqlTemp);

    if ($tipo_busca == 'pecas') {
        $sqlTipo = "
            SELECT  tmp_dados_comuns_busca.*,
                    tbl_peca.referencia,
                    tbl_peca.descricao,
                    round((preco * (1 + (ipi / 100::float)) * 1.2 )::numeric,2) AS custo_peca
            FROM    tmp_dados_comuns_busca
            JOIN    tbl_os_item USING(os_produto)
            JOIN    tbl_peca    USING(peca)
        ";
    } elseif ($tipo_busca == 'mao_obra') {
        $sqlTipo = "
            SELECT  DISTINCT tmp_dados_comuns_busca.*,
                    (
                        SELECT SUM(tbl_produto.mao_de_obra) 
                        FROM tbl_produto 
                        WHERE 
                            tbL_produto.produto = tmp_dados_comuns_busca.produto
                    ) AS mao_de_obra
            FROM    tmp_dados_comuns_busca
        ";
    }

    $resTipo = pg_query($con,$sqlTipo);
}

$layout_menu = "gerencia";
$title = "RELATÓRIO DE CUSTOS COM CORTESIA DE OS";

include 'cabecalho_new.php';
$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable"
);

include("plugin_loader.php");
?>

<script type="text/javascript">

$(function() {
    $.datepickerLoad(Array("data_final", "data_inicial"));
    $.autocompleteLoad(Array("posto"));

    Shadowbox.init();

    $("span[rel=lupa]").click(function () {
        $.lupa($(this));
    });

    function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
        $("#descricao_posto").val(retorno.nome);
    }
});

</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error"> <h4><?php echo $msg_erro["msg"][0]; ?></h4> </div>
<?php
}
?>

<div class="row"> <b class="obrigatorio pull-right">  * Campos obrigatórios </b> </div>

<form name="frm_relatorio" method="post" action="<? echo $PHP_SELF ?>" class="form-search form-inline tc_formulario" >
    <div class="titulo_tabela">Parâmetros de Pesquisa</div>
    <br />
    <div class="container tc_container">
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
    </div>

    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='codigo_posto'>Cod. Posto</label>
                <div class='controls controls-row'>
                    <div class='span10 input-append'>
                        <input type="text" id="codigo_posto" name="codigo_posto" class='span8' maxlength="20" value="<? echo $codigo_posto ?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="referencia" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='descricao_posto'>Nome Posto</label>
                <div class='controls controls-row'>
                    <div class='span10 input-append'>
                        <input type="text" id="descricao_posto" name="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="descricao" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
<?PHP
    /*
     * - SELECT de ADMIN's
     * que aprovaram a cortesia da OS
     */
    $sqlAdminCortesia = "
        SELECT  admin,
                nome_completo
        FROM    tbl_admin
        WHERE   ativo IS TRUE
        AND     fabrica = $login_fabrica
        AND     (
                    privilegios = '*'
                OR  privilegios LIKE '%auditoria%'
                )
  ORDER BY      nome_completo
    ";
    $resAdminCortesia = pg_query($con,$sqlAdminCortesia);
    $admins = pg_fetch_all($resAdminCortesia);
?>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group'>
            <label class='control-label' for='codigo_posto'>Admin que aprovou</label>
                <div class='controls controls-row'>
                    <div class='span10 input-append'>
                        <select name="admin">
                            <option value="">Selecione</option>
<?php
    foreach ($admins as $valor) {
        $selected = ($valor['admin'] == $admin)
            ? "selected"
            : "";
?>
                            <option value="<?=$valor['admin']?>" <?=$selected?>><?=$valor['nome_completo']?></option>
<?php
    }
?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='os'>OS</label>
                <div class='controls controls-row'>
                    <div class='span10 input-append'>
                        <input type="text" id="os" name="os" class='span12' value="<? echo $os?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("tipo_busca", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label'>Tipo de resultado</label><h5 class='asteristico'>*</h5>
                <div class='controls controls-row'>
                    <div class='span10 input-append'>
                        <label class='radio'>
                            <input type="radio" name="tipo_busca" value="pecas" <? if($tipo_busca == 'pecas'){ ?> checked <?}?> >
                            Peças
                        </label>
                        <br />
                        <label class='radio'>
                            <input type="radio" name="tipo_busca" value="mao_obra" <? if($tipo_busca == 'mao_obra'){ ?> checked <?}?> >
                            Mão-de-obra
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <br />
    <center>
        <button type="button" class='btn' onclick="javascript: document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit(); return false;" alt="Clique AQUI para pesquisar" value="Pesquisar">Pesquisar</button>
        <input type="hidden" name="acao">
    </center>
    <br />
</form>
<br />

<?php
if (filter_input(INPUT_POST,"acao")) {
    if (pg_num_rows($resTipo)) {
?>

</div>

<table id="resultado_cortesia_custos" class='table table-striped table-bordered table-fixed' style="margin-left: 5px;">
    <thead>
        <tr class='titulo_coluna'>
            <th>OS</th>
            <th>Data Abertura</th>
            <th>Data Aprovação</th>
            <th>Posto</th>
            <th>Cidade</th>
<?php
        if ($tipo_busca == 'pecas') {
?>

            <th>Peça</th>
            <th>Custo Peça</th>

<?php
        } elseif ($tipo_busca == 'mao_obra') {
?>
            <th>Mão-de-obra</th>
<?php
        }
?>
            <th>Admin</th>
        </tr>
    </thead>
    <tbody>
<?php
        $resultados = pg_fetch_all($resTipo);

        foreach ($resultados as $chave => $resultado) {
?>
        <tr>
            <td><?=$resultado['os']?></td>
            <td><?=$resultado['data_abertura']?></td>
            <td><?=$resultado['data_aprovacao']?></td>
            <td nowrap><?=$resultado['codigo_posto']." - ".$resultado['nome_posto']?></td>
            <td nowrap><?=$resultado['cidade_posto']." - ".$resultado['estado_posto']?></td>
<?php
            if ($tipo_busca == 'pecas') {
?>
            <td nowrap><?=$resultado['referencia']. " - ".$resultado['descricao']?></td>
            <td class="tar"><?="R$".number_format($resultado['custo_peca'],2,",","")?></td>
<?php
            } elseif ($tipo_busca == 'mao_obra') {
?>
            <td class="tar"><?="R$".number_format($resultado['mao_de_obra'],2,",","")?></td>
<?php
            }
?>
            <td nowrap><?=$resultado['admin_aprovou']?></td>
        </tr>
<?php
        }
?>
    </tbody>
</table>
<?php
    } else {
?>
<div class="container">
    <div class="alert">
        <h4>Nenhum resultado encontrado</h4>
    </div>
</div>
<?php
    }
}

echo "<br /> <br />";

include 'rodape.php';
?>
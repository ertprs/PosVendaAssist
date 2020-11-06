<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";
include "funcoes.php";
$admin_privilegios = "cadastros";
use Posvenda\DistribuidorSLA;

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
    $tipo_busca = $_GET["busca"];
    $tipo_busca = $_GET["tipo_busca"];

    if (strlen($q)>2){

        if ($tipo_busca=="posto"){
            $sql = "SELECT tbl_posto.posto,tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
                    FROM tbl_posto
                    JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                    WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

            if ($tipo_busca == "codigo"){
                $sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
            }else{
                $sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
            }

            $res = pg_exec($con,$sql);
            if (pg_numrows ($res) > 0) {
                for ($i=0; $i<pg_numrows ($res); $i++ ){
                    $posto = trim(pg_result($res,$i,posto));
                    $cnpj = trim(pg_result($res,$i,cnpj));
                    $nome = trim(pg_result($res,$i,nome));
                    $codigo_posto = trim(pg_result($res,$i,codigo_posto));
                /*Retira todos usu?rios do TIME*/
                $sql = "SELECT *
                        FROM  tbl_empresa_cliente
                        WHERE posto   = $posto
                        AND   fabrica = $login_fabrica";
                $res2 = pg_exec ($con,$sql);
                if (pg_numrows($res2) > 0) continue;
                $sql = "SELECT *
                        FROM  tbl_empresa_fornecedor
                        WHERE posto   = $posto
                        AND   fabrica = $login_fabrica";
                $res2 = pg_exec ($con,$sql);
                if (pg_numrows($res2) > 0) continue;

                $sql = "SELECT *
                        FROM  tbl_erp_login
                        WHERE posto   = $posto
                        AND   fabrica = $login_fabrica";
                $res2 = pg_exec ($con,$sql);
                if (pg_numrows($res2) > 0) continue;

                    echo "$cnpj|$nome|$codigo_posto";
                    echo "\n";
                }
            }
        }
    }
    exit;
}
//cadastra
if ($_POST["btnacao"] == "gravar") {
    
    $msg_erro = array();

    $codigo_posto    = $_POST["codigo_posto"];
    $unidade_negocio = $_POST["unidade_negocio"];
    $preco_fixo      = $_POST["preco_fixo"];
    $observacao      = $_POST["observacao"];

    if (empty($codigo_posto)) {
        $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][]   = "codigo_posto";
    } 

    if (empty($preco_fixo)) {
        $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][]   = "preco_fixo";
    } 

    if (empty($unidade_negocio)) {
        $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][]   = "unidade_negocio";
    } 

    $sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND codigo_posto = '{$codigo_posto}'";
    $res = pg_query($con, $sql);
    if (pg_num_rows($res) == 0) {
        $msg_erro["msg"]["obg"] = "Posto não encontrado";
        $msg_erro["campos"][]   = "codigo_posto";
    } else {
        $posto = pg_fetch_result($res, 0, posto);
    }

    if (empty($msg_erro)) {
        foreach ($unidade_negocio as $key => $distribuidor_sla) {

            $sqlUN = "SELECT tbl_distribuidor_sla.unidade_negocio
                     FROM tbl_posto_preco_unidade 
                     JOIN tbl_distribuidor_sla USING(distribuidor_sla)
                    WHERE tbl_distribuidor_sla.fabrica = {$login_fabrica} 
                      AND tbl_distribuidor_sla.distribuidor_sla = {$distribuidor_sla}
                      AND tbl_posto_preco_unidade.posto = {$posto}";
            $resUN = pg_query($con, $sqlUN);
            if (pg_num_rows($resUN) == 0) {
            
                $sql = "INSERT INTO tbl_posto_preco_unidade (
                                                                fabrica, 
                                                                distribuidor_sla, 
                                                                posto, 
                                                                preco, 
                                                                admin, 
                                                                observacao
                                                            ) VALUES (
                                                                $login_fabrica,
                                                                $distribuidor_sla,
                                                                $posto,
                                                                $preco_fixo,
                                                                $login_admin,
                                                                '$observacao'
                                                            );";
                $res = pg_query($con, $sql);
                if (strlen(pg_last_error($con)) > 0) {
                    $msg_erro["msg"][] = "Erro ao gravar";
                }
            } else {
                $unidade = pg_fetch_result($resUN, 0, unidade_negocio);
                $msg_erro["msg"]["obg"] = "Unidade {$unidade} já cadastrada.";
            }
        }
        if (empty($msg_erro)) {
            $sucesso = true;
            $descricao_posto = "";
            $codigo_posto    = "";
            $unidade_negocio = array();
            $preco_fixo      = "";
            $observacao      = "";

        } else {
            $sucesso = false;
        }

    } 
}

//edita
if ($_POST["btnacao"] == "editar") {

    $msg_erro = array();

    $posto_preco_unidade  = $_POST["posto_preco_unidade"];
    $codigo_posto         = $_POST["codigo_posto"];
    $unidade_negocio      = $_POST["unidade_negocio"];
    $preco_fixo           = $_POST["preco_fixo"];
    $observacao           = $_POST["observacao"];

    if (empty($codigo_posto)) {
        $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][]   = "codigo_posto";
    } 

    if (empty($preco_fixo)) {
        $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][]   = "preco_fixo";
    } 

    if (empty($unidade_negocio)) {
        $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][]   = "unidade_negocio";
    } 

    $sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND codigo_posto = '{$codigo_posto}'";
    $res = pg_query($con, $sql);
    if (pg_num_rows($res) == 0) {
        $msg_erro["msg"]["obg"] = "Posto não encontrado";
        $msg_erro["campos"][]   = "codigo_posto";
    } else {
        $posto = pg_fetch_result($res, 0, posto);
    }

    if (empty($msg_erro)) {

            $sqlUN = "SELECT tbl_distribuidor_sla.distribuidor_sla
                     FROM tbl_posto_preco_unidade 
                     JOIN tbl_distribuidor_sla USING(distribuidor_sla)
                    WHERE tbl_distribuidor_sla.fabrica = {$login_fabrica} 
                      AND tbl_distribuidor_sla.unidade_negocio = '{$unidade_negocio}'
                      AND tbl_posto_preco_unidade.posto = {$posto}";
            $resUN = pg_query($con, $sqlUN);
            if (pg_num_rows($resUN) > 0) {
                $unidade_negocio = pg_fetch_result($resUN, 0, distribuidor_sla);
            }

        $sql = "UPDATE tbl_posto_preco_unidade SET
                                                    fabrica=$login_fabrica, 
                                                    distribuidor_sla=$unidade_negocio, 
                                                    posto=$posto,
                                                    preco=$preco_fixo,
                                                    admin=$login_admin,
                                                    observacao='$observacao'
                                                WHERE posto_preco_unidade={$posto_preco_unidade};";
        $res = pg_query($con, $sql);
        if (strlen(pg_last_error($con)) > 0) {
            $msg_erro["msg"][] = "Erro ao gravar";
        }

        if (empty($msg_erro)) {
            $sucesso = true;
            $posto_preco_unidade  = "";
            $descricao_posto      = "";
            $codigo_posto         = "";
            $unidade_negocio      = "";
            $preco_fixo           = "";
            $observacao           = "";
        } else {
            $sucesso = false;
        }

    } 

}

if ((isset($_GET["acao"]) && $_GET["acao"] == "editar") && (isset($_GET["id"]) && $_GET["id"] > 0)) {
    $posto_preco_unidade = $_GET['id'];
    $sql = "SELECT 
                    tbl_posto_fabrica.codigo_posto,
                    tbl_posto.nome AS posto_autorizado,
                    tbl_distribuidor_sla.distribuidor_sla,
                    tbl_distribuidor_sla.unidade_negocio,
                    tbl_posto_preco_unidade.observacao,
                    tbl_posto_preco_unidade.preco AS preco_fixo ,
                    tbl_posto_preco_unidade.distribuidor_sla,
                    tbl_distribuidor_sla.unidade_negocio||' - '||tbl_cidade.nome AS cidade
              FROM tbl_posto_preco_unidade
              JOIN tbl_posto_fabrica USING(posto, fabrica)
              JOIN tbl_posto ON tbl_posto.posto=tbl_posto_fabrica.posto
              JOIN tbl_distribuidor_sla USING(distribuidor_sla, fabrica)
              JOIN tbl_cidade ON tbl_cidade.cidade=tbl_distribuidor_sla.cidade
             WHERE tbl_distribuidor_sla.fabrica = $login_fabrica 
               AND tbl_posto_preco_unidade.posto_preco_unidade={$posto_preco_unidade}";
    $res = pg_query($con, $sql);
    if (pg_num_rows($res) > 0) {
        $descricao_posto = pg_fetch_result($res, 0, posto_autorizado);
        $codigo_posto    = pg_fetch_result($res, 0, codigo_posto);
        $distribuidor_sla = pg_fetch_result($res, 0, distribuidor_sla);
        $unidade_negocio = pg_fetch_result($res, 0, unidade_negocio);
        $preco_fixo      = number_format(pg_fetch_result($res, 0, preco_fixo), 2);
        $observacao      = pg_fetch_result($res, 0, observacao);
    } else {
        $descricao_posto = "";
        $codigo_posto    = "";
        $unidade_negocio = "";
        $preco_fixo      = "";
        $observacao      = "";
    }

}

//exclui
if (isset($_GET["acao"]) && $_GET["acao"] == "excluir" && isset($_GET["id"]) && $_GET["id"] > 0) {
    $posto_preco_unidade = $_GET['id'];
    $sql = "DELETE FROM tbl_posto_preco_unidade
             WHERE tbl_posto_preco_unidade.fabrica = $login_fabrica 
               AND tbl_posto_preco_unidade.posto_preco_unidade={$posto_preco_unidade}";
    $res = pg_query($con, $sql);
    if (strlen(pg_last_error($con)) > 0) {
        $msg_erro["msg"][]  = "Erro ao excluir";
    } else {
        $msg_sucesso = "Excluido com sucesso";
    }

}

//lista tudo
if ($_GET["btnacao"] == "listar") {

    $sqlPrincipal = "SELECT 
                    tbl_posto_fabrica.codigo_posto||' - '||tbl_posto.nome AS posto_autorizado,
                    tbl_distribuidor_sla.distribuidor_sla,
                    tbl_posto_preco_unidade.observacao,
                    tbl_posto_preco_unidade.preco,
                    tbl_posto_preco_unidade.posto_preco_unidade,
                    tbl_distribuidor_sla.unidade_negocio,
                    tbl_unidade_negocio.nome AS nomecidade
                FROM tbl_posto_preco_unidade
                JOIN tbl_posto_fabrica USING(posto, fabrica)
                JOIN tbl_posto ON tbl_posto.posto=tbl_posto_fabrica.posto
                JOIN tbl_distribuidor_sla USING(distribuidor_sla, fabrica)
                JOIN tbl_unidade_negocio ON tbl_unidade_negocio.codigo=tbl_distribuidor_sla.unidade_negocio              
                WHERE tbl_distribuidor_sla.fabrica = $login_fabrica;";
    $resPrincipal = pg_query($con, $sqlPrincipal);

}
$layout_menu = "cadastro";
$title       = "CADASTRO DE POSTO X PREÇO FIXO POR UNIDADE DE NEGÓCIO";

include "cabecalho_new.php";

$plugins = array(
   "shadowbox",
   "select2",
   "alphanumeric",
   "price_format",
   "dataTable",
   "autocomplete"
);

include "plugin_loader.php";
?>
<style>
    span.add-on {
        cursor: pointer;
    }
</style>
<script>
    $(function(){
        Shadowbox.init();
        $("input.numeric").numeric();
        $("input.price-format").priceFormat({
            prefix: "",
            thousandsSeparator: "",
            centsSeparator: ".",
            centsLimit: 2
        });
        $("select.select2").select2();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
         });
        $.autocompleteLoad(Array("posto"));
    });  

    function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
        $("#descricao_posto").val(retorno.nome);
    }
</script>
<?php if ($sucesso) { ?>
    <br />
    <div class="alert alert-success">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h4>Gravado com sucesso</h4>
    </div>
    <br />
<?php } if (strlen($msg_sucesso) > 0) { ?>
    <br />
    <div class="alert alert-success">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h4><?php echo $msg_sucesso;?></h4>
    </div>
    <br />
<?php } if (count($msg_erro["msg"]) > 0) {?>
    <br />

    <div class="alert alert-error">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>

    <br />
<?php }?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios</b>
</div>
<form method="POST" class="form-search form-inline tc_formulario" action="cadastro_posto_preco_fixo.php" >
    <div class="titulo_tabela" >Cadastro</div>
    <br />
    <div class="row-fluid" >
        <div class="span2" ></div>
        <div class="span3" >
            <div class="control-group <?=(in_array('codigo_posto', $msg_erro['campos'])) ? 'error' : '' ?>" >
                <label class="control-label" for="posto_codigo" >Código</label>
                <div class="controls controls-row">
                    <div class="span10 input-append">
                        <h5 class='asteristico'>*</h5>
                       <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
                        <span class='add-on' rel="lupa">
                            <i class='icon-search' ></i>
                            <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="span5" >
            <div class="control-group <?=(in_array('codigo_posto', $msg_erro['campos'])) ? 'error' : '' ?>" >
                <label class="control-label" for="posto_nome" >Nome</label>
                <div class="controls controls-row" >
                    <div class="span10 input-append" >
                        <h5 class='asteristico'>*</h5>
                        <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
                        <span class='add-on' rel="lupa">
                            <i class='icon-search' ></i>
                            <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="span2" ></div>
    </div>
    <div class="row-fluid" >
        <div class="span2" ></div>
        <div class="span5" >
            <div class="control-group  <?=(in_array('unidade_negocio', $msg_erro['campos'])) ? 'error' : '' ?>" >
                <label class="control-label" >Unidade de Negócio</label>
                <div class="controls controls-row">
                    <div class="span12">
                        <h5 class='asteristico'>*</h5>
                        <?php 
                            if (isset($_GET['acao']) && $_GET['acao'] == 'editar') {
                                $tipo_select = ' name="unidade_negocio"  class="span12"';
                            }  else {
                                $tipo_select = ' name="unidade_negocio[]" multiple="multiple"  class="span12 select2"';
                            }
                        ?>
                        <select <?php echo $tipo_select;?> id="unidade_negocio">
                            <?php 
                                echo '<option value="">Escolha ...</option>';
                                $oDistribuidorSLA = new DistribuidorSLA();
                                $oDistribuidorSLA->setFabrica($login_fabrica);
                                $distribuidores_disponiveis = $oDistribuidorSLA->SelectUnidadeNegocioNotIn();

                                $unidadesMinasGerais = \Posvenda\Regras::getUnidades("unidadesMinasGerais", $login_fabrica);

                                foreach ($distribuidores_disponiveis as $unidadeNegocio) {

                                    // if (in_array($unidadeNegocio["unidade_negocio"], $unidadesMinasGerais)) { HD-7688496
                                    //     unset($unidadeNegocio["unidade_negocio"]);
                                    //     continue;
                                    // }
                                    $unidade_negocio_agrupado[$unidadeNegocio["distribuidor_sla"]] = $unidadeNegocio["cidade"];
                                }

                                foreach ($unidade_negocio_agrupado as $unidade => $descricaoUnidade) {
                                    if (isset($_GET['acao']) && $_GET['acao'] == 'editar') {
                                        $selected = ($unidade == $distribuidor_sla) ? 'selected' : '';
                                    } else {
                                        $selected = (in_array($unidade,$distribuidor_sla)) ? 'selected' : '';
                                    }
                                    echo '<option '.$selected.' value="'.$unidade.'">'.$descricaoUnidade.'</option>';
                                }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="span3" >
            <div class="control-group <?=(in_array('preco_fixo', $msg_erro['campos'])) ? 'error' : '' ?>" >
                <label class="control-label" >Preço Fixo</label>
                <div class="controls controls-row">
                    <div class="span10 input-prepend" >
                        <h5 class='asteristico'>*</h5>
                        <span class="add-on">R$ </span>
                        <input name="preco_fixo" class="span12 price-format valor" type="text" value="<?=$preco_fixo?>" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span2" ></div>
    </div>
    <div class="row-fluid" >
        <div class="span2" ></div>
        <div class="span8" >
            <div class="control-group" >
                <label class="control-label" >Observações</label>
                <div class="controls controls-row">
                    <div class="span12">
                        <textarea name="observacao" class="span12"><?php echo $observacao;?></textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="span2" ></div>
    </div>
    <br />

    <p>
        <?php
            if (isset($_GET['acao']) && $_GET['acao'] == 'editar') {
                echo '<input type="hidden" name="acao" value="editar" />';
                echo '<input type="hidden" name="posto_preco_unidade" value="'.$_GET['id'].'" />';
                echo '<button class="btn btn-primary" type="submit" name="btnacao" value="editar" >Gravar</button>';
            } else {
                echo '<input type="hidden" name="acao" value="add" />';
                echo '<button class="btn btn-primary" type="submit" name="btnacao" value="gravar" >Gravar</button>';
            }
        ?>
        <a href="./cadastro_posto_preco_fixo.php?btnacao=listar" class="btn">Listar todos</a>
    </p>

    <br />
</form>

<?php if ($_GET["btnacao"] == "listar") {?>
    <table class='table table-striped table-bordered table-hover table-fixed'>
        <thead>
        <tr class='titulo_coluna'>
            <td class="tac">Posto</td>
            <td class="tac">Unidade de Negócio</td>
            <td class="tac">Preço Fixo</td>
            <td class="tac">Observação</td>
            <td class="tac">Ações</td>
        </thead>
        <tbody>
            <?php 
                if (pg_num_rows($resPrincipal) == 0) {
                    echo '<tr><td colspan="4" align="center">Nenhum registro encontrado.</td></tr>';
                } else {
                    while ($rows = pg_fetch_array($resPrincipal)) {
                            
                        $nomeUnidade = $rows["unidade_negocio"] . " - " . $rows["nomecidade"];
            ?>
            <tr>
                <td class="tac"><?php echo $rows['posto_autorizado'];?></td>
                <td class="tac"><?php echo strtoupper($nomeUnidade);?></td>
                <td class="tac">R$ <?php echo number_format($rows['preco'], 2, ',', '.');?></td>
                <td class="tac"><?php echo $rows['observacao'];?></td>
                <td class="tac" nowrap>
                    <a href="./cadastro_posto_preco_fixo.php?acao=editar&id=<?php echo $rows['posto_preco_unidade'];?>" class="btn btn-small btn-info">Editar</a>
                    <a href="./cadastro_posto_preco_fixo.php?acao=excluir&id=<?php echo $rows['posto_preco_unidade'];?>" class="btn btn-small btn-danger">Excluir</a>
                </td>
            </tr>
            <?php }}?>
        </tbody>
    </table>

<?php }?>
<?php include "rodape.php";?>
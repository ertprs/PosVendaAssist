<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include_once 'autentica_admin.php';
include_once 'funcoes.php';

$admin_privilegios="auditorias";

$layout_menu = "auditoria";
$title = "RELATÓRIO PEÇAS E PRODUTOS GARANTIA";

include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable"
);

include "plugin_loader.php";

if (isset($_POST["btn_acao"])) {
    $data_inicial               = trim($_POST["data_inicial"]);
    $data_final                 = trim($_POST["data_final"]);
    $codigo_posto               = trim($_POST["codigo_posto"]);
    $somente_pecas              = $_POST["somente_pecas"];
    $somente_produtos           = $_POST["somente_produto"];
    $consulta                   = $_POST["consulta"];

    if (!empty($data_inicial) || !empty($data_final)) {
        $xdata_inicial = formata_data($data_inicial);
        $xdata_final   = formata_data($data_final);

        if (strtotime($xdata_inicial) > strtotime($xdata_final)) {
            $msg_erro["msg"][]    = "Data final não pode ser menor que a inicial";
            $msg_erro["campos"][] = "data";
        }

        if (empty($codigo_posto)) {
            $periodo_entre_datas = '3 months';
            $msg                 = "AS DATAS DEVEM SER DE NO MÁXIMO 3 MESES";
        } else {
            $periodo_entre_datas = '6 months';
            $msg                 = "AS DATAS DEVEM SER DE NO MÁXIMO 6 MESES";

            $sql = "SELECT posto
                    FROM   tbl_posto_fabrica
                    WHERE  codigo_posto = '{$codigo_posto}'
                    AND    fabrica = $login_fabrica";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) == 0) {
                $msg_erro["msg"][]    = "Código do Posto não encontrado";
                $msg_erro["campos"][] = "posto";
            } else {
                $posto = pg_fetch_result($res, 0, "posto");
            }       

        }

        $sql = "SELECT '$xdata_inicial'::date + interval '$periodo_entre_datas' > '$xdata_final'";
        $res = pg_query($con,$sql);
        $periodo_meses = pg_fetch_result($res,0,0);
        if($periodo_meses == 'f'){
           $msg_erro["msg"][]    = $msg;;
           $msg_erro["campos"][] = "data"; 
        }
    } else {
        $msg_erro["msg"][]    = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "data";
    }

    if (!empty($somente_pecas) && empty($somente_produtos)) {
        $condSomentePecas = " AND tbl_peca.produto_acabado IS NOT TRUE ";
    }

    if (!empty($somente_produtos) && empty($somente_pecas)) {
        $condSomenteProdutos = " AND tbl_peca.produto_acabado IS TRUE ";
    }

    if (!empty($posto)) {
        $condPosto = " AND tbl_os.posto = $posto ";
    }

    if ( in_array($login_fabrica, array(11,172)) ) {
        $consulta = "detalhada";
    }

    if ($consulta != "detalhada") {
        $distinct = " DISTINCT ON (tbl_os.os) ";
    } 

    if (count($msg_erro) == 0) {
        $sqlRelatorio = "SELECT $distinct
                                tbl_os_produto.os_produto,
                                tbl_produto.produto,
                                tbl_produto.referencia,
                                tbl_produto.descricao, 
                                tbl_os.os,
                                tbl_os.posto,
                                tbl_os.sua_os,
                                tbl_posto_fabrica.codigo_posto,
                                tbl_posto.nome,
                                tbl_os.data_digitacao,
                                tbl_os_item.os_item,
                                tbl_os_item.pedido,
                                tbl_peca.peca,
                                tbl_peca.referencia as referencia_peca,
                                tbl_peca.descricao  as descricao_peca,
                                tbl_peca.produto_acabado
                         FROM tbl_os
                         JOIN tbl_os_produto    ON tbl_os_produto.os       = tbl_os.os
                         JOIN tbl_os_item       ON tbl_os_item.os_produto  = tbl_os_produto.os_produto
                         JOIN tbl_peca          ON tbl_peca.peca           = tbl_os_item.peca
                         JOIN tbl_posto         ON tbl_posto.posto         = tbl_os.posto
                         JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                         JOIN tbl_produto       ON tbl_produto.produto     = tbl_os_produto.produto
                         WHERE (tbl_os.data_digitacao BETWEEN '{$xdata_inicial}' AND '{$xdata_final}')
                         AND tbl_os.fabrica = $login_fabrica
                         AND tbl_posto_fabrica.fabrica = $login_fabrica
                         $condSomentePecas
                         $condSomenteProdutos
                         $condPosto";           

        $resRelatorio = pg_query($con, $sqlRelatorio);
    }
}

?>

<script type="text/javascript">
    $(function (){
        $.datepickerLoad(Array("data_final", "data_inicial"));
        $.autocompleteLoad(Array("posto"));
        Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });

        $(".visualizar_peca").click(function() {
            var os     = $(this).attr("os");
            var pedido = $(this).attr("pedido");
            
            Shadowbox.open({
                content: "pecas_em_garantia_os.php?os="+os+"&pedido="+pedido,
                player: "iframe",
                title:  "Peças em garantia",
                width:  800,
                height: 500
            });
        });

    });

    function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
        $("#descricao_posto").val(retorno.nome);
    }
</script>
<?
   
?>
<?php
if (strlen($msg_success) > 0) {
?>
    <div class="alert alert-success">
        <h4><?= $msg_success ?></h4>
    </div>
<?php
}else{
    $msg_erro["msg"] = array_filter($msg_erro["msg"]);
    if (count($msg_erro["msg"]) > 0) {
    ?>
        <div class="alert alert-error">
            <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
        </div>
    <?php
    }
}
?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name="frm_produto" method="POST" class="form-search form-inline tc_formulario" action="<?=$_SERVER["PHP_SELF"] ?>">
    <div class="titulo_tabela ">Relatório de Produtos e Peças em Garantia</div>
   <br />
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
    <br />
    <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='codigo_posto'>Código Posto</label>
                    <div class='controls controls-row'>
                        <div class='span7 input-append'>
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
                            <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
                            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <br />
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                 <label class="checkbox">
                    <input type="checkbox" name="somente_pecas" value="somente_pecas" <?= (isset($_POST['btn_acao']) && !empty($_POST["somente_pecas"]) || empty($_POST['btn_acao'])) ? "checked" : "" ?> />
                    Somente Peças
                </label>
                <br />
                <label class="checkbox">
                    <input type="checkbox" name="somente_produto" value="somente_produtos" <?= (isset($_POST['btn_acao']) && !empty($_POST["somente_produto"]) || empty($_POST['btn_acao'])) ? "checked" : "" ?> />
                    Somente Produtos
                </label>
            </div>
            <?php if ( !in_array($login_fabrica, array(11,172)) ) {?>
            <div class='span4'>
                <label class="radio">
                    <input type="radio" name="consulta" value="normal" checked />
                    Consulta detalhada por OS
                </label>   
                <label class="radio">
                    <input type="radio" name="consulta" value="detalhada" />
                    Consulta detalhada por peça (mais lenta) 
                </label>
            </div>
            <?php }?>
            <div class='span2'></div>
        </div>
        <br />
    <input type='hidden' id="btn_click" name='btn_acao' value='' />
    <input class='btn' type="submit" name="btn_acao" value="Pesquisar" />

<br/><br />
</form>
<br />
<?
if (pg_numrows($resRelatorio) > 0) { ?>

    <table id="relatorio_pecas" class="table table-striped table-bordered table-hover table-fixed">
        <thead>
            <tr class="titulo_coluna">
                <th nowrap>Posto</th>
                <th nowrap>OS</th>
                <? if ($consulta == "detalhada") { ?> 
                    <th nowrap>Nota Fiscal</th>
                    <th nowrap>Emissão</th>
                <? } ?>    
                <th nowrap>Produto</th>
                <?php if ($consulta == "normal") { ?>
                    <th nowrap>Ações</th>
                <?php } else { ?>
                    <td class="tac">
                        Peça
                    </td>    
                <?php } ?>   
            </tr>    
        </thead>        
        <tbody>    
        <?
            for ($x = 0 ; $x < pg_numrows($resRelatorio) ; $x++){ 
                $codigo_posto         = trim(pg_result($resRelatorio,$x,'codigo_posto'));
                $nome_posto           = trim(pg_result($resRelatorio,$x,'nome'));
                $sua_os               = trim(pg_result($resRelatorio,$x,'sua_os'));
                $os                   = trim(pg_result($resRelatorio,$x,'os'));
                $emissao              = trim(pg_result($resRelatorio,$x,'emissao'));
                $nota_fiscal          = trim(pg_result($resRelatorio,$x,'nota_fiscal'));
                $referencia_produto   = trim(pg_result($resRelatorio,$x,'referencia'));
                $descricao_produto    = trim(pg_result($resRelatorio,$x,'descricao'));
                $referencia_peca      = trim(pg_result($resRelatorio,$x,'referencia_peca'));
                $descricao_peca       = trim(pg_result($resRelatorio,$x,'descricao_peca'));
                $pedido               = trim(pg_result($resRelatorio,$x,'pedido'));

                ?>    
                <tr>
                    <td class="tal">
                        <?= $codigo_posto." - ".$nome_posto ?>
                    </td>
                    <td class="tac">
                        <a href="os_press.php?os=<?= $os ?>" target='_blank'>   
                            <?= $sua_os ?>   
                        </a>
                    </td>
                    <?php 
                        $sql = "SELECT emissao,nota_fiscal 
                                FROM tbl_faturamento
                                JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
                                WHERE tbl_faturamento_item.pedido = {$pedido}";      
                        $res = pg_query($con, $sql);
                        
                        $nota_fiscal = trim(pg_result($res,0,'nota_fiscal'));  
                        $emissao     = trim(pg_result($res,0,'emissao'));

                    if ($consulta == "detalhada") { ?>          
                        <td class="tac">
                            <?= $nota_fiscal ?>
                        </td>
                        <td class="tac">
                            <?= mostra_data($emissao) ?>
                        </td>
                    <?php } ?>

                    <td class="tal">
                        <?= $referencia_produto." - ".$descricao_produto ?>
                    </td>
                    <?php if ($consulta == "normal") { ?>
                        <td class="tac">
                            <button class="btn btn-primary btn-small visualizar_peca" os="<?= $os ?>" pedido="<?= $pedido ?>">Peças</button>
                        </td>
                    <?php } else { ?>
                        <td class="tac">
                            <?= $referencia_peca." - ".$descricao_peca ?>
                        </td>    
                    <?php } ?>    
                </tr>
                <?
            }
        ?>
        </tbody>
    </table>    
    <?
    flush();

    $xlsdata = date ("d/m/Y H:i:s");

    system("rm /tmp/assist/relatorio-produto-pecas-garantia-$login_fabrica.csv");
    $fp = fopen ("/tmp/assist/relatorio-produto-pecas-garantia-$login_fabrica.csv","w");

    fputs ($fp,";;;Relatório de Produtos e Peças em Garantia\n");

    $cabecalho = array();

    $cabecalho[] = "Posto";
    $cabecalho[] = "OS";
    $cabecalho[] = "Nota Fiscal";
    $cabecalho[] = "Data Emissão";
    $cabecalho[] = "Produto";
    $cabecalho[] = "Peça";

    fputs ($fp, implode(";", $cabecalho)."\n");

    for ($i = 0; $i < pg_num_rows($resRelatorio); $i++){
        $codigo_posto         = trim(pg_result($resRelatorio,$i,'codigo_posto'));
        $nome_posto           = trim(pg_result($resRelatorio,$i,'nome'));
        $sua_os               = trim(pg_result($resRelatorio,$i,'sua_os'));
        $os                   = trim(pg_result($resRelatorio,$i,'os'));
        $emissao              = trim(pg_result($resRelatorio,$i,'emissao'));
        $nota_fiscal          = trim(pg_result($resRelatorio,$i,'nota_fiscal'));
        $referencia_produto   = trim(pg_result($resRelatorio,$i,'referencia'));
        $descricao_produto    = trim(pg_result($resRelatorio,$i,'descricao'));
        $referencia_peca      = trim(pg_result($resRelatorio,$i,'referencia_peca'));
        $descricao_peca       = trim(pg_result($resRelatorio,$i,'descricao_peca'));
        $pedido               = trim(pg_result($resRelatorio,$i,'pedido'));

        $linha = array();

        $linha[] = "$codigo_posto - $nome_posto";
        $linha[] = "$sua_os";

        $sql = "SELECT emissao,nota_fiscal 
                                FROM tbl_faturamento
                                JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
                                WHERE tbl_faturamento_item.pedido = {$pedido}";      
        $res = pg_query($con, $sql);
        
        $nota_fiscal = trim(pg_result($res,0,'nota_fiscal'));  
        $emissao     = trim(pg_result($res,0,'emissao')); 

        $linha[] = "$nota_fiscal";
        $linha[] = "".mostra_data($emissao)."";
        $linha[] = "$referencia_produto - ".str_replace(",", " ", $descricao_produto);
        $linha[] = "$referencia_peca - ".str_replace(",", " ", $descricao_peca);

        fputs($fp, implode(";", $linha)."\n");
    }

    fclose ($fp);

    $data = date("Y-m-d").".".date("H-i-s");

    rename("/tmp/assist/relatorio-produto-pecas-garantia-$login_fabrica.csv", "xls/relatorio-produto-pecas-garantia-$login_fabrica.$data.csv");

    ?>

    <br />
    <table style="width: 100%;" border='0' cellspacing='2' cellpadding='2' align='center' style='cursor: pointer; font-size: 12px;'>
        <tr>
            <td align='center' valign='absmiddle'>
                <a href='xls/relatorio-produto-pecas-garantia-<?= "$login_fabrica.$data.csv" ?>' target='_blank'>
                    <img src='imagens/excel.png' height='20px' width='20px' align='absmiddle'>    Download do Arquivo CSV
                </a>
                <br /><br />
            </td>    
        </tr>
    </table>
    <?php            
} else if (isset($_POST["btn_acao"])) { ?>
    <div class='alert alert-warning'><h4>Nenhum resultado encontrado</h4></div>
<?}
?>
<script>
    $.dataTableLoad({ table: "#relatorio_pecas" });
</script>
<?php    

include "rodape.php";

?>


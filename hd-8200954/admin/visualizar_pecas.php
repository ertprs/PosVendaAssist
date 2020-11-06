<?php
    include_once 'dbconfig.php';
    include_once 'includes/dbconnect-inc.php';
    include_once 'funcoes.php';
    if (empty($admin_privilegios)) {
        $admin_privilegios = "call_center,gerencia";
    }

    include_once 'autentica_admin.php';

    $plugins = array(
        /*"select2",*/
        "autocomplete",
        "datepicker",
        "shadowbox",
        "mask",
        "multiselect",
        "dataTable",
        "alphanumeric"
    );
    
    include_once "plugin_loader.php";

    $pedido = $_REQUEST['pedido'];
?>

<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/bootstrap.css" />
<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/extra.css" />
<link type="text/css" rel="stylesheet" media="screen" href="../css/tc_css.css" />
<link type="text/css" rel="stylesheet" media="screen" href="../css/tooltips.css" />
<link type="text/css" rel="stylesheet" media="screen" href="../plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/ajuste.css" />    

<script src="../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="../plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src="../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
<script src="../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
<script src="../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
<script src="../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
<script src="../bootstrap/js/bootstrap.js"></script>

<style>
    
    th.titulo_coluna {
        background-color: #596D9B !important;
        color: #FFFFFF !important;
        font-weight: bold !important;
    }

    .titulo_tabela{
        background-color:#596d9b;
        font: bold 14px "Arial";
        color:#FFFFFF;
        text-align:center;
    }

    .titulo_coluna{
        background-color:#596d9b;
        font: bold 12px "Arial";
        color:#FFFFFF;
        text-align:center;
    }

    table.tabela tr td{
        font-family: verdana;
        font-size: 11px;
        border-collapse: collapse;
        border:1px solid #596d9b;
    }

</style>

<div class="container" >
    <div class="row">      
        <br>  
        <h4>PEDIDO <i><?=$pedido?></i></h4>
        <br>
    </div>
    <div class="row">
        <?php
        if(!empty($pedido)) {
        /*$sqlPecas = "
            SELECT
                peca.referencia_fabrica peca_referencia_fabrica,
                peca.referencia || ' - ' || peca.descricao AS peca,
                os_item.qtde,
                os_item.os_item,
                os_item.preco,
                peca.peca_critica AS critica,
                peca.devolucao_obrigatoria,
                os_item.pedido,
                servico.descricao AS servico
            FROM tbl_os_item AS os_item
            INNER JOIN tbl_peca AS peca ON peca.peca = os_item.peca AND peca.fabrica = {$login_fabrica}
            INNER JOIN tbl_servico_realizado AS servico ON servico.servico_realizado = os_item.servico_realizado AND servico.fabrica = {$login_fabrica}
            WHERE os_item.pedido = {$pedido}";*/

        $sqlPecas = "SELECT DISTINCT
                        p.referencia_fabrica peca_referencia_fabrica,
                        p.referencia || ' - ' || p.descricao AS peca,
                        pi.qtde,    
                        pi.preco,
                        p.peca_critica AS critica,
                        p.devolucao_obrigatoria,
                        pi.pedido                        
                    FROM tbl_pedido_item pi
                    LEFT JOIN tbl_peca p ON p.peca = pi.peca 
                    LEFT JOIN tbl_os_item oi ON oi.peca = pi.peca                    
                    WHERE pi.pedido = {$pedido}
                    AND p.fabrica = {$login_fabrica}";

        //die(nl2br($sqlPecas));
        $resPecas = pg_query($con, $sqlPecas);

        $pecas = pg_fetch_all($resPecas);
        }        

        ?>
        <table id='tabela_pecas' class="table" >
            <thead>
                <tr class="titulo_coluna" >
                    <th>Peça(s)</th>
                    <th>Qtde</th>
                    <th>Crítica</th>
                    <th>Devolução Obrigatória</th>                                      
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($pecas as $peca) {
                ?>
                    <tr class="tac" >
                        <td><?echo $peca["peca"] . $peca["peca_referencia_fabrica"]; ?></td>
                        <td class="tac" ><?=$peca["qtde"]?></td>
                        <td class="tac" >
                            <?php
                            if ($peca["critica"] == "t") {
                            ?>
                                <span class="label label-success" ><i class="icon-ok icon-white"></i></span>
                            <?php
                            } else {
                            ?>
                                <span class="label label-important" ><i class="icon-remove icon-white"></i></span>
                            <?php
                            }
                            ?>
                        </td>
                        <td class="tac" >
                            <?php
                            if ($peca["devolucao_obrigatoria"] == "t") {
                            ?>
                                <span class="label label-success" ><i class="icon-ok icon-white"></i></span>
                            <?php
                            } else {
                            ?>
                                <span class="label label-important" ><i class="icon-remove icon-white"></i></span>
                            <?php
                            }
                            ?>
                        </td>                                               
                    </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
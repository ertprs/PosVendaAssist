<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
$layout_menu = "cadastro";

include 'autentica_admin.php';
include 'funcoes.php';

$title="INTEGRIDADE - PEÇA X FORNECEDOR";


if($_POST ['ajax']){
    if($_POST['acao'] == "excluir"){
        $ajax_peca          = $_POST['ajax_peca'];
        $ajax_fornecedor    = $_POST['ajax_fornecedor'];

        $res = pg_query($con, "BEGIN TRANSACTION");

        $sql = "DELETE  FROM tbl_fornecedor_peca
                WHERE   fornecedor  = $ajax_fornecedor
                AND     peca        = $ajax_peca
        ";
        $res = pg_query($con,$sql);

        if(!pg_last_error($con)){
            $res = pg_query($con,"COMMIT TRANSACTION");
            $resposta = array("resp"=>"ok");
            echo json_encode($resposta);
        }else{
            $res = pg_query($con,"ROLLBACK TRANSACTION");
            echo "erro";
        }
    }
    exit;
}


include 'cabecalho_new.php';


$plugins = array(
    "shadowbox",
    "mask",
    "dataTable"
);

include("plugin_loader.php");

    if($_POST){
        $btn_acao = $_POST['btn_lista'];
        $referencia = $_POST['referencia'];
        $descricao  = $_POST['descricao'];
        $ativo      = $_POST['ativo'];

        if(!isset($_POST['peca'])){
            if(empty($referencia) OR empty($descricao)){
                $btn_acao = "";
                $msg_erro ="Informe a Peça para Pesquisa";
            }

            $sql = "SELECT peca FROM tbl_peca WHERE fabrica = $login_fabrica AND referencia = '$referencia'";
            $res = pg_query($con,$sql);
            if(pg_numrows($res) == 0){
                $msg_erro = "Peça Inválida";
                $btn_acao = "";
            }
        }
    }

    // ----- Inicio do cadastro ----------

    if ( $btn_acao == "gravar" ) {
        $fornSelect = $_POST['fornSelect'];
        $peca       = $_POST['peca'];
        $referencia = $_POST['referencia'];
        $descricao  = $_POST['descricao'];

        if(empty($fornSelect)){
            $msg_erro = "Selecione um fornecedor";
        }

        if(empty($msg_erro)){
            $sql = "SELECT peca FROM tbl_fornecedor_peca WHERE peca = $peca AND fornecedor = $fornSelect";
//             echo $sql;exit;
            $res = pg_query($con,$sql);
            if(pg_numrows($res) > 0){
                $msg_erro = "Fornecedor já Cadastrado para esta Peça";
            }
        }

        if(empty($msg_erro)) {
            $sql = "INSERT INTO tbl_fornecedor_peca
                                (
                                 peca,
                                 fornecedor,
                                 fabrica
                                )
                              VALUES
                                (
                                 $peca,
                                 $fornSelect,
                                 $login_fabrica
                                )";
            $res = pg_exec($con,$sql);
            $msg_erro = pg_errormessage($con);
        }
            if(empty($msg_erro)) {
                $msg = 'Gravado com Sucesso!';
                pg_exec($con, "COMMIT TRANSACTION");
            }
            else{
                pg_exec($con, "ROLLBACK TRANSACTION");
            }

            $btn_acao = "listar";

    }

    if ( $btn_acao == "nova_pesquisa" ) {
        $peca = "";
        $referencia = "";
        $descricao  = "";
    }

    if($_GET){
        $btn_acao = $_GET['btn_lista'];
    }

?>

<script type="text/javascript">

$(function() {
    Shadowbox.init();

    $("span[rel=lupa]").click(function () {
        $.lupa($(this));
    });

    $("button[id^=exclui_]").click(function(){
        if(confirm("Deseja realmente retirar a integridade de fornecedor dessa peça?")){
            var id          = $(this).attr("id");
            var divisao     = id.split("_");
            var peca        = divisao[1];
            var fornecedor  = divisao[2];

            $.ajax({
                url:"<?=$PHP_SELF?>",
                dataType:"json",
                type:"POST",
                data:{
                    ajax:true,
                    acao:"excluir",
                    ajax_peca:peca,
                    ajax_fornecedor:fornecedor
                },
                beforeSend:function(){
                    $("#fornecedor_"+fornecedor).fadeOut("slow");
                }
            })
            .fail(function(){
                alert("Não foi possível excluir a integridade");
            })
            .done(function(data){
                alert("Fornecedor retirado da integridade da peça");
            });
        }
    });
});

function retorna_peca(retorno){
    $("#referencia").val(retorno.referencia);
    $("#descricao").val(retorno.descricao);
    Shadowbox.init();
}

</script>

<?php

    if($btn_acao == "listar" OR !empty($peca)){
        if(empty($peca)){
            $sqlPeca = "SELECT peca FROM tbl_peca WHERE referencia = '$referencia' AND fabrica = $login_fabrica";
            $resPeca = pg_query($con,$sqlPeca);

            $peca = pg_result($resPeca,0,0);
        }

?>
        <?php
            if (strlen($msg_erro) > 0) {
        ?>
                <div class='alert alert-error'>
                    <h4><?php echo $msg_erro; ?></h4>
                </div>
        <?php
            }
        ?>
        <?php
            if (strlen($msg) > 0) {
        ?>
                <div class="alert alert-success">
                    <h4><?php echo $msg; ?></h4>
                </div>
        <?php
            }
        ?>

        <form name='frm_integridade' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' enctype='multipart/form-data' >
            <div class='titulo_tabela '>Cadastro</div>
            <br/>

            <div class='row-fluid'>
                <div class='span2'></div>
                <div class='span4'>
                    <div class='control-group'>
                        <label class='control-label'><strong>Referência</strong></label>
                        <div class='controls controls-row'>
                            <?php echo $referencia; ?>
                        </div>
                    </div>
                </div>
                <div class='span4'>
                    <div class='control-group'>
                        <label class='control-label'><strong>Descrição</strong></label>
                        <div class='controls controls-row'>
                            <?php echo $descricao; ?>
                        </div>
                    </div>
                </div>
                <div class='span2'></div>
            </div>

            <div class='row-fluid'>
                <div class="span12">
                    <div class='span2'></div>
                    <div class='span4'>
                        <div class='control-group'>
                            <label class='control-label' for='fornSelect'>Fornecedor</label>
                            <div class='controls controls-row'>
                                <div class='span4'>
                                    <select name="fornSelect" id="fornSelect">
                                        <option value="">Selecione Fornecedor</option>
                                        <?php
                                            $sql = "SELECT  fornecedor,
                                                            nome
                                                    FROM    tbl_fornecedor
                                                    JOIN    tbl_fornecedor_fabrica USING(fornecedor)
                                                    WHERE   fabrica = $login_fabrica
                                              ORDER BY      nome";

                                            $res = pg_query($con,$sql);
                                            $total = pg_numrows($res);
                                            if($total > 0){
                                                for($i = 0; $i < $total; $i++){
                                                    $escolha_fornecedor = pg_result($res,$i,fornecedor);
                                                    $escolha_fornecedor_nome = pg_result($res,$i,nome);
?>
                                        <option value="<?=$escolha_fornecedor?>"><?=$escolha_fornecedor_nome?></option>
<?
                                                }
                                            }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="span2"></div>
                </div>
            </div>

            <p><br />
                <input type='hidden' name='peca' value='<?php echo $peca;?>'>
                <input type='hidden' name='fornecedor' value='<?php echo $fornecedor;?>'>
                <input type='hidden' name='referencia' value='<?php echo $referencia;?>'>
                <input type='hidden' name='descricao' value='<?php echo $descricao;?>'>
                <input type='button' class="btn" value='Gravar' onclick='document.frm_integridade.btn_lista.value="gravar"; document.frm_integridade.submit();'>

                <input type='hidden' name='btn_lista' value=''>
                <input type='button' class="btn" value='Nova Pesquisa' onclick='document.frm_integridade.btn_lista.value="nova_pesquisa"; document.frm_integridade.submit();'>

            </p><br />
        </form>

    <?php


        if(!empty($peca)){

            $sqlDef = " SELECT  tbl_fornecedor.fornecedor,
                                tbl_fornecedor.nome
                        FROM    tbl_fornecedor
                        JOIN    tbl_fornecedor_peca     ON  tbl_fornecedor_peca.fornecedor      = tbl_fornecedor.fornecedor
                        JOIN    tbl_fornecedor_fabrica  ON  tbl_fornecedor_fabrica.fornecedor   = tbl_fornecedor.fornecedor
                                                        AND tbl_fornecedor_fabrica.fabrica      = $login_fabrica
                        WHERE   tbl_fornecedor_peca.peca = $peca";

//             echo $sqlDef;exit;

            $resDef = pg_query($con,$sqlDef);
            if(pg_numrows($resDef) > 0){
            ?>

                <table class='table table-striped table-bordered table-hover table-fixed' style='table-layout: fixed !important;' >
                    <thead>
                        <tr class='titulo_coluna' >
                            <th>Fornecedor</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
<?php
                $total = pg_numrows($resDef);

                for($i = 0; $i < $total; $i++){
                    $forn       = pg_result($resDef, $i, fornecedor);
                    $forn_nome  = pg_result($resDef, $i, nome);
?>
                    <tr id="fornecedor_<?=$forn?>">
                        <td><?php echo $forn_nome; ?></td>
                        <td class="tac">
                            <input type="hidden" name="condicao" value="<?=$condicao?>" />
                            <button name='excluir' class='btn btn-small btn-danger btn-ativar' id="exclui_<?=$peca?>_<?=$forn?>">Excluir</button>
                        </td>
                    </tr>
                <?php
                }
            }
        }
?>
            </tbody>
        </table>
<?
    }else{
?>
        <?php
            if (strlen($msg_erro) > 0) {
        ?>
                <div class='alert alert-error'>
                    <h4><?php echo $msg_erro; ?></h4>
                </div>
        <?php
            }
        ?>
        <form name='frm_integridade' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' enctype='multipart/form-data' >

            <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
            <br/>

            <div class='row-fluid'>
                <div class='span2'></div>
                <div class='span4'>
                    <div class='control-group'>
                        <label class='control-label' for='referencia'>Referência</label>
                        <div class='controls controls-row'>
                            <div class='span7 input-append'>
                                <input type="text" id="referencia" name="referencia" class='span12' maxlength="20" value="<? echo $referencia ?>" >
                                <span class='add-on' rel="lupa"><i class='icon-search'></i></span>
                                <input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span4'>
                    <div class='control-group'>
                        <label class='control-label' for='descricao'>Descrição</label>
                        <div class='controls controls-row'>
                            <div class='span12 input-append'>
                                <input type="text" id="descricao" name="descricao" class='span12' value="<? echo $descricao ?>" >
                                <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                                <input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span2'></div>
            </div>

            <p><br/>
                <input type='hidden' name='btn_lista' value=''>
                <input type="button" class="btn" value="Pesquisar" onclick='document.frm_integridade.btn_lista.value="listar"; document.frm_integridade.submit();'  />
            </p><br/>
        </form>

<?php
    }
include 'rodape.php';

?>
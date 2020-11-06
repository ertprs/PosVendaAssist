<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'funcoes.php';
include_once '../class/communicator.class.php';

if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {
    include 'autentica_admin.php';
} else {
    include 'autentica_usuario.php';
}

if(isset($_REQUEST["pedido_item"])){
    $pedido_item                = $_REQUEST['pedido_item'];
    $pedido                     = $_REQUEST["pedido"];
    $referencia_anterior        = $_REQUEST["peca_referencia"];
    $posto_id                   = $_REQUEST["posto_id"];
    $tabela                     = $_REQUEST["tabela"];
    $qtde                       = $_REQUEST["qtde"];
    $valor_desconto             = $_REQUEST["valor_desconto"];
}

if(isset($_POST["btnacao"])){

    $referencia         = $_POST["peca_referencia"];
    $motivo             = $_POST["motivo"];
    $pedido_item        = $_POST["pedido_item"];  
    $pedido                     = $_POST["pedido"];
    $referencia_anterior        = $_POST["referencia_anterior"];
    $posto_id                   = $_POST["posto_id"];
    $tabela                     = $_POST["tabela"];
    $qtde                       = $_POST["qtde"];
    $desconto                   = $_POST["valor_desconto"];


    $sql_posto = "SELECT contato_email FROM tbl_posto_fabrica where posto = $posto_id and fabrica = $login_fabrica";
    $res_posto = pg_query($con, $sql_posto);
    if(pg_num_rows($res_posto)> 0 ){
        $email_posto = pg_fetch_result($res_posto, 0, contato_email);
    }

    $sqlPegaID = "SELECT tbl_peca.peca, tbl_peca.referencia, tbl_tabela_item.preco 
                  FROM tbl_peca 
                  INNER JOIN tbl_tabela_item on tbl_tabela_item.peca = tbl_peca.peca and tbl_tabela_item.tabela = $tabela
                  WHERE fabrica = $login_fabrica 
                  AND referencia = '$referencia' ";
    $resPegaID = pg_query($con, $sqlPegaID);

    if(pg_num_rows($resPegaID)>0){
        $peca_id            = pg_fetch_result($resPegaID, 0, peca);
        $referencia_nova    = pg_fetch_result($resPegaID, 0, referencia);
        $preco              = pg_fetch_result($resPegaID, 0, preco);

        $valorDesconto = ($preco * $qtde) *  ($desconto / 100);

        $valorTotal = ($preco * $qtde) -  $valorDesconto;
        $valorTotal = number_format($valorTotal,2,',','');
        $valorTotal = str_replace(",", ".", str_replace(".", "", $valorTotal));

        $motivo_obs = "A peça $referencia_anterior foi alterada para $referencia_nova pelo admin no dia: ".date("d/m/Y").", pelo motivo: <b> $motivo </b>"; 

        $sqlAtualizaPedido = "UPDATE tbl_pedido_item SET  peca = $peca_id, obs = case when obs is null then '$motivo_obs ' else obs || '$motivo_obs' end , 
         preco = '$preco', preco_base = '$preco', total_item = '$valorTotal'  WHERE pedido_item = $pedido_item and pedido = $pedido";
        $resAtualizaPedido = pg_query($con, $sqlAtualizaPedido);        

        $nome_servidor = $_SERVER['SERVER_NAME'];
        $nome_uri = $_SERVER['REQUEST_URI'];
        $nome_url = $nome_servidor.$nome_uri;
        $action = "update";
        
        auditorLog($pedido,array("referencia" => $referencia_anterior), array("referencia" => $referencia ),"tbl_pedido_item",$nome_url,$action);

        if(strlen(trim(pg_last_error($con)))> 0 ){
            $msg_erro = "Erro ao atualizar pedido.";
        }else{
            $ok .= "Peça trocado com sucesso. ";
        }

        $from_fabrica   = $externalEmail;
        $assunto        = "Alteração de peça no pedido $pedido - $login_fabrica_nome ";
        
        $msg_email = "Prezado(a) autorizado, a peça $referencia_anterior do pedido $pedido, foi trocada pela peça $referencia_nova, pelo seguinte motivo: <b> $motivo </b>, qualquer duvida estamos a disposição. <br> <Br> 
            Atenciosamente.
            <Br><Br>
            $login_fabrica_nome.";

        $mailTc = new TcComm($externalId);
        $res = $mailTc->sendMail(
            $email_posto,
            $assunto,
            $msg_email,
            $from_fabrica
        );

        echo "<script>  window.parent.location.reload();window.parent.Shadowbox.close(); </script>";

    }else{
        $msg_erro .= "Peça não encontrada.";
    }

}


?>
<!DOCTYPE html />
<html>
    <head>
        <meta http-equiv=pragma content=no-cache>
        <link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
        <link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
        <link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
        <link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
        <link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />

        <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
        <script src="bootstrap/js/bootstrap.js"></script>
        <script src="plugins/dataTable.js"></script>
        <script src="plugins/resize.js"></script>
        <script src="plugins/shadowbox_lupa/lupa.js"></script>

        <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
        <script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
        <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
        <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>

        <link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">


        <script type="text/javascript">
            var hora = new Date();
            var engana = hora.getTime();
            var login_fabrica = <?=$login_fabrica?>;
            $(function() {
                $.autocompleteLoad(Array("peca"));
            });
        </script>
    </head>

    <body>

    <?php
        $plugins = array(
        "autocomplete"
    );
    include("plugin_loader.php");
    ?>
        <div id="container_lupa" style="overflow-y:auto;">
            <div id="topo">
                <img class="espaco" src="imagens/logo_new_telecontrol.png">
                <img class="lupa_img pull-right" src="imagens/lupa_new.png">
            </div>
            <br /><hr />

            <?php if(strlen(trim($ok))>0){?>
            <div class="alert alert-success">
               <?= $ok ?>
            </div>
            <?php } ?>
            
            <?php if(strlen(trim($msg_erro))>0){ ?>
            <div class="alert alert-danger">
              <?= $msg_erro ?>
            </div>
            <?php } ?>


            <div class="row-fluid">
            <form name='frm_cadastro' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' id="form_pesquisa">
                <input type="hidden" name="acao"  value="pesquisar">
                <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
                    <br/>
                <div class="row-fluid">

                    <div class="span2"></div>

                    <div class='span4'>
                            <div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
                                <label class='control-label' for='peca_referencia'>Ref. Peças</label>
                                <div class='controls controls-row'>
                                    <div class='span10 input-append'>
                                        <h5 class='asteristico'>*</h5>
                                        <input type="text" id="peca_referencia" name="peca_referencia" class='span12' maxlength="20" value="<? echo $referencia ?>" >
                                        <input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" pesquisa_produto_acabado="true" sem-de-para="true" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='span4'>
                            <div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
                                <label class='control-label' for='peca_descricao'>Descrição Peça</label>
                                <div class='controls controls-row'>
                                    <div class='span12 input-append'>
                                        <input type="text" id="peca_descricao" name="peca_descricao" class='span12' value="<? echo $peca_descricao ?>" >
                                        <input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" pesquisa_produto_acabado="true" sem-de-para="true" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    <div class="span2"></div>
                </div>
                 <div class="row-fluid">

                    <div class="span2"></div>

                    <div class='span8'>
                        <div class='control-group <?=(in_array("motivo", $msg_erro["campos"])) ? "error" : ""?>'>
                            <label class='control-label' for='peca_referencia'>Motivo</label>
                            <div class='controls controls-row'>
                                <div class='span12 input-append'>
                                    <h5 class='asteristico'>*</h5>
                                    <input type="text" id="motivo" name="motivo" class='span12' maxlength="256" value="<? echo $motivo ?>" >
                                </div>
                            </div>
                        </div>


                    </div>

                    <div class="span2"></div>
                </div>
                <p>
                    <br/>
                    <input type="submit" name="btnacao" class='btn' id="btn_acao" value="Gravar">
                    <input type='hidden' name='pedido_item' value='<?=$pedido_item?>' />
                    <input type='hidden' name='pedido' value='<?=$pedido?>' />
                    <input type='hidden' name='referencia_anterior' value='<?=$referencia_anterior?>' />
                    <input type='hidden' name='posto_id' value='<?=$posto_id?>' />
                    <input type='hidden' name='tabela' value='<?=$tabela?>' />
                    <input type='hidden' name='qtde' value='<?=$qtde?>' />
                    <input type='hidden' name='valor_desconto' value='<?=$valor_desconto ?>' />
                    
                </p>

                <br />
            </form>
            </div>
            
    </div>
    </body>
</html>

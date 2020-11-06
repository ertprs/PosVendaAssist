<?php
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include_once __DIR__ . '/dbconfig.php';
include_once __DIR__ . '/includes/dbconnect-inc.php';

if ($areaAdmin === true) {
    $admin_privilegios = "call_center";
    include __DIR__.'/admin/autentica_admin.php';
    include_once('../class/tdocs.class.php');
} else {
    include __DIR__.'/autentica_usuario.php';
    include_once('class/tdocs.class.php');    
} 

include_once __DIR__ . '/funcoes.php';
include_once __DIR__ . '/class/communicator.class.php';
include __DIR__ . '/token_cookie.php';
include __DIR__ . "/classes/mpdf61/mpdf.php";
include __DIR__ . "/plugins/fileuploader/TdocsMirror.php";

?>
<!doctype html>


    <script src="js/jquery-1.8.3.min.js"></script>

    <link media="screen" type="text/css" rel="stylesheet" href="plugins/bootstrap3/css/bootstrap.css" />
    <link media="screen" type="text/css" rel="stylesheet" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
    <script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
    <script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
    <script type="text/javascript" src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
    <script type="text/javascript" src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
    <script type="text/javascript" src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
    <script type="text/javascript" src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
    <script type="text/javascript" src="plugins/bootstrap3/js/bootstrap.js"></script>
    <style> 
        .obrigatorio{
            font-size: 12px;
            color: #d90000;
        }
    </style>
<?

$plugins = array(
    "shadowbox",
    "price_format",
    "mask",
    "ckeditor",
    "autocomplete",
    "ajaxform",
    "fancyzoom",
    "multiselect"    
);

include __DIR__ . "/plugin_loader.php";

/*if ($login_fabrica == 203) {
    $status = $_GET['status'];

    if ($status == "CREDENCIADO") {
        $join_posto_contrato  = "JOIN tbl_posto_contrato ON tbl_contrato.fabrica = tbl_posto_contrato.fabrica 
                                AND tbl_contrato.contrato = tbl_posto_contrato.contrato";
        $where_posto_contrato = "AND  tbl_posto_contrato.confirmacao != 't'  AND  tbl_posto_contrato.fabrica = {$login_fabrica}";

    }
}*/

$sqlContrato = "SELECT DISTINCT
                    tbl_contrato.contrato,
                    tbl_contrato.descricao,
                    tbl_contrato.numero_contrato,
                    tbl_contrato.linhas AS linhas
                    FROM tbl_contrato
                    JOIN tbl_linha ON tbl_linha.fabrica = tbl_contrato.fabrica AND STRPOS(tbl_contrato.linhas::text,tbl_linha.linha::text) > 0
                    JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_linha.linha                 
                    WHERE tbl_contrato.fabrica = {$login_fabrica}
                    AND tbl_posto_linha.posto = {$login_posto}
                    AND tbl_contrato.ativo = 't'
    ";

$resContrato = pg_query($con, $sqlContrato);
$dadosContrato = pg_fetch_all($resContrato);

function getLinha($linhas) {
    global $con,$login_fabrica;

    $sql = "SELECT nome FROM tbl_linha
            WHERE tbl_linha.fabrica = {$login_fabrica}
            AND tbl_linha.linha in(".implode(',',$linhas).")
            AND tbl_linha.ativo = 't'";

    $res = pg_query($con, $sql);
    return pg_fetch_all($res);
}
//echo "<pre>".print_r($dadosContrato,1)."</pre>";exit;



$descricao_contrato = pg_fetch_result($resContrato, 0, 'descricao');

$sqlPosto = "SELECT DISTINCT
                tbl_posto.posto         AS codigo_posto,
                tbl_posto.cnpj          AS cnpj_posto,
                tbl_posto.nome          AS nome_posto,
                tbl_posto.endereco      AS endereco_posto,
                tbl_posto.numero        AS numero_posto,
                tbl_posto.complemento   AS complemento_posto,
                tbl_posto.cep           AS cep_posto,
                tbl_posto.cidade        AS cidade_posto,
                tbl_posto.estado        AS estado_posto,
                tbl_posto.fone          AS fone_posto,
                tbl_posto.ie            AS ie_posto,
                tbl_posto.bairro        AS bairro_posto,
                tbl_posto.pais          AS pais_posto,
                tbl_posto_fabrica.parametros_adicionais::jsonb->>'nome_responsavel' AS responsavel_posto,
                tbl_posto_fabrica.parametros_adicionais::jsonb->>'cpf_responsavel' AS cpf_responsavel_posto,
                tbl_posto_fabrica.parametros_adicionais::jsonb->>'rg_responsavel' AS rg_responsavel_posto               
                FROM tbl_posto
                JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto             
                WHERE tbl_posto_fabrica.fabrica = {$login_fabrica} 
                AND tbl_posto.posto = {$login_posto}";          

$resPosto = pg_query($con, $sqlPosto);

$info_posto = pg_fetch_assoc($resPosto);

$texto_contrato = $descricao_contrato;
foreach ($info_posto as $key => $value) {
    $texto_contrato = str_replace(":{$key}", "{$value}", $texto_contrato);
}

if (strlen($_POST["btn_acao"]) > 0) {
    $btnacao = trim($_POST["btn_acao"]);    
}

if($btnacao == "aceitar"){

    $contrato           = trim($_POST['contrato']);
    $nome_contrato      = trim($_POST['nome']);
    $li_aceito_contrato = trim($_POST['li_aceito']);

    if ($li_aceito_contrato == true) {
        $li_aceito_contrato = 't';
    } else {
        $li_aceito_contrato = 'f';
    }

    if (empty($nome_contrato) && $li_aceito_contrato != 't') {
        $msg_erro = "Preencha os campos obrigatórios";      
    } elseif (empty($nome_contrato)) {
        $msg_erro = "Nome é obrigatório";       
    } elseif ($li_aceito_contrato != 't') {
        $msg_erro = "Leia e concorde com o(s) contrato(s)";       
    } else {    

        $ipexterno          = $_SERVER["REMOTE_ADDR"];
        $ipexterno          = $ipexterno;
        $nomeaceite         = utf8_decode($nome_contrato);
        $campos_adicionais  = array("ip_addr" => $ipexterno, "nome_aceite" => $nomeaceite);         
        $campos_adicionais  = json_encode($campos_adicionais);                  

        $resS = pg_query($con,"BEGIN TRANSACTION ");
        for($x=0; $x<pg_num_rows($resContrato); $x++){          
            unset($obs);
            $cod_contrato       = pg_fetch_result($resContrato, $x, 'contrato');    
            $numero_contrato    = pg_fetch_result($resContrato, $x, 'numero_contrato');
            $linhas             = pg_fetch_result($resContrato, $x, 'linhas');
            $descricao_contrato = pg_fetch_result($resContrato, $x, 'descricao');

            //$texto_contrato = $descricao_contrato;
            //$texto_contrato = str_replace(":{$key}", "{$value}", $texto_contrato);

            $linhas_x = json_decode($linhas);
        
            $campoAdmin = "";
            $valueAdmin = "";
            if (!empty($login_admin)){
                $campoAdmin = "admin,";
                $valueAdmin = "{$login_admin},";
            }
            foreach ($linhas_x as $valor) { 
                $sqlAceitarContrato = "INSERT INTO tbl_posto_contrato (
                                                                        fabrica, 
                                                                        posto, 
                                                                        contrato, 
                                                                        linha, 
                                                                        {$campoAdmin} 
                                                                        confirmacao, 
                                                                        campos_adicionais
                                                                       ) VALUES  (
                                                                        {$login_fabrica}, 
                                                                        {$login_posto}, 
                                                                        {$cod_contrato}, 
                                                                        {$valor}, 
                                                                        {$valueAdmin} 
                                                                        '{$li_aceito_contrato}', 
                                                                        '{$campos_adicionais}'
                                                                    )";

                $resAceitarContrato = pg_query($con, $sqlAceitarContrato);                                          
                if(strlen(pg_last_error()) > 0){
                    $msg_erro .= "Erro ao gravar o aceite do contrato N {$numero_contrato}<br>";
                }
            }


            if (strlen($msg_erro) > 0) {
                $resS = pg_query($con," ROLLBACK TRANSACTION ");
            } else {

                $dir_garantia = "/tmp/";
                $arq_garantia = "contrato_assinatura_digital_{$cod_contrato}_{$login_posto}.pdf";
                $arq_garantia = str_replace("-", "_", $arq_garantia);
                $arquivo_garantia = $dir_garantia.$arq_garantia;
                $gerarPDF = new mPDF();
                $gerarPDF->SetDisplayMode('fullpage');
                $gerarPDF->WriteHTML($texto_contrato);
                $gerarPDF->Output($arquivo_garantia, "F");

                // Enviando Arquivo para o TDocs

                $s3_tdocs = new TdocsMirror();
                $postPDF = $s3_tdocs->post($arquivo_garantia);

                if(!is_array($postPDF)) {
                    $postPDF = json_decode($postPDF, true);
                }

                $uniqueId = $postPDF[0][$arq_garantia]['unique_id'];    

                $obs[0]['acao']         = "anexar";
                $obs[0]["filename"]     = $arq_garantia;
                $obs[0]["data"]         = date("Y-m-d h:i:s");
                $obs[0]["fabrica"]      = $login_fabrica;
                $obs[0]["descricao"]    = "";
                $obs[0]["page"]         = "aceite_contrato.php";
                $obs[0]["source"]       = "contrato-posto-fabrica";
                $obs[0]["typeId"]       = $uniqueId;

                $obs = json_encode($obs);
                
                $sql_tdocs = "INSERT INTO tbl_tdocs (tdocs_id, fabrica, contexto, situacao, referencia, referencia_id, obs) VALUES ('".$uniqueId."',".$login_fabrica.", 'posto', 'ativo', 'posto_contrato', '".$cod_contrato.$login_posto."', '".$obs."')";

                $res_tdocs = pg_query($con, $sql_tdocs);    

                if (pg_last_error()) {
                    $msg_erro .= "Erro ao gravar contrato"; 
                }
            }
        }

        if (strlen($msg_erro) == 0) {

            $sqlAtualizarStatus =" INSERT INTO tbl_credenciamento(fabrica, posto, status, texto) values ($login_fabrica, $login_posto, 'CREDENCIADO','ACEITE DE CONTRATO') ";
                                    
            $resAtualizarStatus = pg_query($con, $sqlAtualizarStatus);

            $sqlAtualizarStatusFabrica = "UPDATE tbl_posto_fabrica 
                                            SET credenciamento = 'CREDENCIADO'
                                            WHERE posto = {$login_posto}
                                            AND fabrica = $login_fabrica";

            $resAtualizarStatusFabrica = pg_query($con, $sqlAtualizarStatusFabrica);
            if(strlen(pg_last_error()) > 0){
                $msg_erro .= "Erro ao Credenciar";
            }    
        }

        if (strlen($msg_erro) > 0) {
            $resS = pg_query($con, " ROLLBACK TRANSACTION ");       
        } else {
            $resS = pg_query($con, " COMMIT TRANSACTION ");     
        }
    }
    ?>


    <?php  
    if (strlen($msg_erro) == 0) {

        ?>
        <script>
            window.parent.fechar();
        </script>
        <?      
    } else {?>
        <script>
            $("#btn_aceitar").show();
        </script>
 
<?php
}
}
?>
<script>
$(function(){
    $("#btn_aceitar").click(function(){
	$(this).hide();
})
})
</script>
 

<div class="container">
    <img width="150" src="logos/logo_brother.jpg" alt="">
    <div style="background: #f6f6f6;padding: 20px;">
        <h3 align="center" style="font-weight: bold;">Aceite de Contrato</h3><br/>
        <p>Olá <b><?php echo $info_posto["nome_posto"];?></b>, você possui o(s) seguinte(s) contrato(s):</p><br/>
        <?php if (strlen($msg) > 0) { ?>
        <div class="alert alert-success">
            <h4><? echo $msg; ?></h4>
        </div>    
        <?php }?>


        <?php if (strlen($msg_erro) > 0) { ?>
        <div class="alert alert-danger">
            <pff><?=$msg_erro?></pff>
        </div> 
        <?php }?>
        <form name="frm_aceite_contrato" method="post" action="" align="center" class='form-search form-inline tc_formulario' >
            <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">

                <?php 
                    foreach ($dadosContrato  as $key => $row) {
                            $linhas_x = json_decode($row['linhas']);
                            $dadosLinha = getLinha($linhas_x);
                            $nomeLinha = "";
                            foreach ($dadosLinha as $key => $value) {
                                $nomeLinha .= $value["nome"];
                            }


                        $xtexto_contrato = $row["descricao"];
                        foreach ($info_posto as $key => $value) {
                            $xtexto_contrato = str_replace(":{$key}", "{$value}", $xtexto_contrato);
                        }


                ?>
                    <div class="panel panel-default">
                        <div class="panel-heading" align="left" role="tab" id="headingOne">
                            <h4 class="panel-title"  align="left" >
                            <?php echo $row['numero_contrato'];?>  - <?php echo $nomeLinha;?> <div class="pull-right"><button  role="button"  data-target="#collapse<?php echo $row['contrato'];?>" data-toggle="collapse" data-parent="#accordion" aria-expanded="true" aria-controls="collapse<?php echo $row['contrato'];?>" class="btn btn-sm btn-primary" style="margin-top: -6px;" type="button"><i class="glyphicon glyphicon-search"></i> Visualizar Contrato</button></div>
                            </h4>
                        </div>
                        <div id="collapse<?php echo $row['contrato'];?>" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingOne">
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-sm-2"></div>
                                    <div class="col-sm-8">
                                        <div class='control-group'>
                                            <label class='control-label'>Contrato</label>
                                            <div class='controls controls-row' name="contrato_div" id="contrato_div" style='background-color: #ffffff; border: 1px solid #cccccc; border-radius: 4px; padding: 5px; overflow: auto; height: 200px;'>
                                                <? echo nl2br($xtexto_contrato); ?>                 
                                            </div>
                                            <textarea name="contrato" id="contrato" style="display: none;"><?=$xtexto_contrato ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php }?>
            </div>
            <br/>
            <div class="row">
                <div class="col-xs-12 col-sm-12 col-md-12" align="left">
                    <label>Nome*:</label>
                    <input class="form-control nome" type="text" id="nome" name="nome" value="<?php echo (isset($_POST["nome"]) && strlen($_POST["nome"]) > 0 ) ? $_POST["nome"] : "";?>" >
                </div>
            </div><br/>
            <div class="row">
                <div class="col-xs-12 col-sm-12 col-md-12" style="justify-content: flex-end;">
                    <input type='checkbox' name='li_aceito' id='li_aceito' value='TRUE' class='li_aceito' <?if($li_aceito == 't') echo "CHECKED";?> />
                    <label class='control-label'>* Li e Concordo com os termos do(s) contrato(s) listado(s) acima</label>                                 
                </div>
                <div class="col-sm-2"></div>
            </div><br/>
            <div class="row">     
                <div class="col-xs-4 col-sm-4"></div>        
                <div class="col-xs-4 col-sm-4">
                            <button name="btn_aceitar" id="btn_aceitar" class='btn btn-block btn-success' type="button" onclick="$('form[name=frm_aceite_contrato]').submit()" alt="Aceitar Contrato"><i class="glyphicon glyphicon-ok"></i> Aceitar os Termos</button>                    
                            <input type='hidden' id="btn_click" name='btn_acao' value='aceitar' />                      
                </div>              
            </div>
            <div class="row">
                <div class="col-xs-12 col-sm-12 col-md-12">
                    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
                </div>
            </div>
        </form>

    </div>

</div>

<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'autentica_admin.php';
include 'funcoes.php';

$title = "Liberação de OS";
$layout_menu = "financeiro";
$admin_privilegios="financeiro";

if ($_POST['btn_pesquisar'] == 'Pesquisar') {
    $data_inicio    = $_POST['data_inicio'];
    $data_fim       = $_POST['data_fim'];
    $codigo_posto   = $_POST['codigo_posto'];
    $status_os      = $_POST['status_os'];
    
    try {
        if( validaData($data_inicio,$data_fim,3) ){

            $xdata_inicio =  fnc_formata_data_pg(trim($data_inicio));
            $xdata_inicio = str_replace("'","",$xdata_inicio);

            $xdata_fim =  fnc_formata_data_pg(trim($data_fim));
            $xdata_fim = str_replace("'","",$xdata_fim);
        }

    } catch (Exception $e) {
        $msg_erro["campos"][] = "data_consulta";
        $msg_erro["msg"][] = $e->getMessage();      
    }

    if(!empty($codigo_posto)){
        $sql = "SELECT  posto
                FROM    tbl_posto_fabrica
                WHERE   fabrica      = $login_fabrica
                AND     codigo_posto = '$codigo_posto';";
        $res = pg_query ($con,$sql);
        if (pg_num_rows($res) > 0){
            $posto = trim(pg_fetch_result($res,0,posto));
            $wherePosto = " AND tbl_posto_fabrica.posto = '$posto' AND tbl_posto_fabrica.fabrica = {$login_fabrica}";
        } else {
            $msg_erro["campos"][] = "posto";
            $msg_erro["msg"][] = "Posto não encontrado!";
        }
    }

    if (empty($status_os)) {
        $status_os = 3;
    }

    if(count($msg_erro)==0){

        $sql = "SELECT  DISTINCT
                    tbl_os.os AS os,
                    TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
                    TO_CHAR(tbl_os.data_abertura,'YYYYMMDDHH24MISS') AS data_abertura_pedido,
                    tbl_os.pecas AS total_pecas,
                    tbl_os.mao_de_obra AS total_mo,
                    (
                        SELECT count(tbl_os_item.os_item) 
                            FROM tbl_os as os2
                                JOIN tbl_os_produto USING(os)
                                JOIN tbl_os_item USING(os_produto)
                            WHERE
                                os2.os = tbl_os.os

                    ) AS qtde_pecas,
                    tbl_posto_fabrica.codigo_posto AS codigo_posto,
                    tbl_posto.nome AS nome_posto
                FROM tbl_os
                    JOIN tbl_posto_fabrica ON  tbl_posto_fabrica.posto = tbl_os.posto
                        AND tbl_posto_fabrica.fabrica   = $login_fabrica
                    JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
                WHERE   tbl_os.fabrica = $login_fabrica
                    AND tbl_os.data_abertura between '$xdata_inicio 00:00:00' and '$xdata_fim 23:59:59'
                    AND tbl_posto_fabrica.prestacao_servico IS TRUE
                    AND tbl_os.status_checkpoint = {$status_os}
                    $wherePosto
                    ORDER BY nome_posto;";

        $resSubmit = pg_query($con,$sql);

        if(strlen(trim(pg_last_error($con)))>0){
            $msg_erro["msg"][] = "Erro ao pesquisar. ";
            unset($resSubmit);
        }

        /* Gera arquivo CSV */
        if ($_POST["gerar_excel"] ) {
            
            $exporta_email = $_POST['exporta_email'];

            $data = date("d-m-Y-H:i");
            $data2 = date("YmdHis");
            $data3 = date("Ymd");

            $arquivo_nome       = "csv_lancamento_os_prestacao_servico-{$data}.csv";            
            $arquivo_zip        = "pedido_os_{$data2}.zip";
            $path               = "xls/";
            $path_tmp           = "/tmp/";

            $arquivo_completo       = $path.$arquivo_nome;
            $arquivo_completo_tmp   = $path_tmp.$arquivo_nome;

            $arquivo_zip_completo = $path.$arquivo_zip;
            $arquivo_zip_completo_tmp = $path_tmp.$arquivo_zip;

            $fp = fopen($arquivo_completo_tmp,"w");

            $cabecalho = array(
                "Código do Posto",
                "Nome do Posto",
                "OS",
                "Data Abertura",
                "Qtde. Peças",
                "Total Peças",
                "Total MO",
                "Total OS"
            );

            $thead = implode(';',$cabecalho)."\r\n".$linha;
            fwrite($fp, $thead);

            $tbody = "";
            $arquivoPedido = array();
            
            while($resultSubmit = pg_fetch_object($resSubmit)){

                $total_os = $resultSubmit->total_mo + $resultSubmit->total_pecas;
                
                $tbody .= "{$resultSubmit->codigo_posto};{$resultSubmit->nome_posto};{$resultSubmit->os};{$resultSubmit->data_abertura};{$resultSubmit->qtde_pecas};{$resultSubmit->total_pecas};{$resultSubmit->total_mo};{$total_os}\r\n";

                //Arquivos de Pedidos            
                
                $arquivo_nome_pedido       = "liberacao_pedido_{$resultSubmit->os}_{$data3}.txt";
                $arquivoPedido[] = $arquivo_nome_pedido; 
                $arquivo_completo_pedido       = $path.$arquivo_nome_pedido;
                $arquivo_completo_pedido_tmp   = $path_tmp.$arquivo_nome_pedido;
                $fpp = fopen($arquivo_completo_pedido_tmp,"w");

                $linha0 = "000".$data2."\r\n";                
                //$linha1 = "001ZB01BR60EWEWZB17".str_pad($resultSubmit->data_abertura_pedido,35,"0",STR_PAD_LEFT)."".$data3."        BR00BT3684139      X000000000000000000\r\n";
                $linha1 = "001ZB01BR60EWEWZB17".$resultSubmit->os."                           ".$data3."        BR00B23684139        XRPV1CA      RPV1CA\r\n";

                $sqlPeca = "SELECT
                                    tbl_posto_fabrica.codigo_posto,
                                    tbl_os.os,
                                    tbl_os_item.peca,
                                    tbl_peca.referencia,
                                    tbl_os_item.qtde,
                                    tbl_os.custo_peca,
                                    tbl_tabela_item.preco,
                                    tbl_posto_fabrica.contato_pais
                                FROM tbl_os
                                    JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                                    JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                                    JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                                    JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca
                                    JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca
                                    JOIN tbl_tabela ON tbl_tabela_item.tabela = tbl_tabela.tabela AND tbl_tabela.tabela = tbl_posto_fabrica.tabela
                                WHERE tbl_os.os = {$resultSubmit->os}
                                    AND tbl_os.fabrica = {$login_fabrica};";
                $resPeca = pg_query($con,$sqlPeca);

                $i = 1;
                $linha2 = "";
                $linha5 = "";
                $linha9 = "";

                while($resultPeca = pg_fetch_object($resPeca)){

                    $linha2 .= "002".str_pad($i, 6, '0', STR_PAD_LEFT)."".str_pad($resultPeca->referencia, 13, '0', STR_PAD_RIGHT)."".str_pad($resultPeca->qtde, 10, '0', STR_PAD_LEFT).".000ZB17".str_pad(number_format($resultPeca->preco, 3), 13, "0", STR_PAD_LEFT)."ZB71\r\n";

                    //$linha2 .= "002".str_pad($i, 6, '0', STR_PAD_LEFT)."".$resultPeca->referencia."".str_pad(number_format($resultPeca->qtde, 3), 13, "0", STR_PAD_LEFT)."ZB17".str_pad(number_format($resultPeca->preco, 3), 12, "0", STR_PAD_LEFT)."ZB71\r\n";
                    $linha5 .= "00400000{$i}ZB00{$resultSubmit->os}\r\n";
                    $i++;
                }

                $qtd_linha = pg_num_rows($resPeca);
                for ($j=1; $j <= $qtd_linha; $j++) {
                    $linha_qtde = (($qtd_linha * 2) + $j)+5;
                    $linha9 .= "999".str_pad($linha_qtde, 6, "0", STR_PAD_LEFT)."\r\n";
                }
                

                $linha3 = "003AG".str_pad($resultSubmit->codigo_posto,10,"0",STR_PAD_LEFT)."00000000000000000000\r\n";
                $linha4 = "004000000ZB00{$resultSubmit->os}\r\n004000000ZB02{$resultSubmit->os}\r\n";

                fwrite($fpp, $linha0);
                fwrite($fpp, $linha1);                
                fwrite($fpp, $linha2);
                fwrite($fpp, $linha3);                
                fwrite($fpp, $linha4);
                fwrite($fpp, $linha5);
                fwrite($fpp, $linha9);
                fclose($fpp);
            
            }            

            fwrite($fp, $tbody);
            fclose($fp);

            //Zipando o arquivo para enviar por e-mail
            $arquivoPedidos = implode(" ", $arquivoPedido);
            system(`cd $path_tmp && rm -f $arquivo_zip ; zip -o $arquivo_zip $arquivoPedidos 1> /dev/null && cp $arquivo_zip $path`,$teste);

            if (is_array($exporta_email)) {

                require_once dirname(__FILE__) . '/../class/email/mailer/class.phpmailer.php';

                $assunto = utf8_decode('OS Aguardando Conserto');

                $mail = new PHPMailer();
                $mail->IsHTML(true);
                $mail->From = 'helpdesk@telecontrol.com.br';
                $mail->FromName = 'Telecontrol';

                if("novodevel.telecontrol.com.br" == $_SERVER['SERVER_NAME']){
                    $mail->AddAddress('thiago.tobias@telecontrol.com.br');
                    $mail->AddAddress('gustavo.paulo@telecontrol.com.br');
                } else {
                    if (!empty($exporta_email)) {
                        foreach ($exporta_email as $email) {
                            $mail->AddAddress($email);
                        }
                    }
                }
                

                
                
                $mail->Subject = $assunto;
                $mail->Body = "MENSAGEM AUTOMÁTICA - NÃO RESPONDER A ESTE EMAIL.<br/><br/>";
                $mail->AddAttachment("$arquivo_completo_tmp", "$arquivo_nome");
                $mail->AddAttachment("$arquivo_zip_completo_tmp", "$arquivo_zip");
                //$mail->Send();
                
                if (!$mail->Send()) {
                    echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
                }
                
            }

            if (file_exists($arquivo_completo_pedido_tmp)) {
                system("mv ".$arquivo_completo_tmp." ".$arquivo_completo."");
                echo $arquivo_completo;
                // system("mv ".$arquivo_zip_completo_tmp." ".$arquivo_zip."");
                // echo $arquivo_zip;
            }

            exit;
        }
    }
}

//AJAX
if( $_POST['aprovarCheck'] == 'ok' ){

    $os_ajax = $_POST['os_ajax'];

    if (!empty($os_ajax)) {
        $res = pg_query($con,"BEGIN TRANSACTION");

        $sqlAprova = "UPDATE tbl_os SET status_checkpoint = 2 WHERE os = {$os_ajax} AND fabrica = {$login_fabrica};";
        $resAprova = pg_query($con,$sqlAprova);
        $msg_erro = pg_errormessage($con);
        
        if (strlen ($msg_erro) == 0) {
            $res = pg_query ($con,"COMMIT TRANSACTION");     
            echo "ok|Exportação concluída com sucesso!.";
        }else{
            $res = pg_query ($con,"ROLLBACK TRANSACTION");
            echo "erro|Ocorreu um erro ($os_ajax).";
        }
    }
    exit;
}

include 'cabecalho_new.php';

$plugins = array(
    "datepicker",
    "shadowbox",
    "maskedinput",
    "ajaxform",
    "dataTable",
    "multiselect"
);

include 'plugin_loader.php';
?>
<script type="text/javascript">

$(function() {
    $("#data_inicio").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");
    $("#data_fim").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");

    /**
     * Inicia o shadowbox, obrigatório para a lupa funcionar
     */
    Shadowbox.init();

    /**
     * Evento que chama a função de lupa para a lupa clicada
     */
    $("span[rel=lupa]").click(function() {
        $.lupa($(this));
    });

    $.dataTableLoad({ table: "#tabela_lancamento_os_prestacao_servico" });

    $("#exporta_email").multiselect({
        selectedText: "# de # opções"
    });

    $('#exporta_email').change(function(){
        var jsonPost = $('#jsonPOST').val();
        var exportaEmail = $(this).val();

        jsonPost = JSON.parse(jsonPost);

        jsonPost.exporta_email = exportaEmail ;
        $('#jsonPOST').val(JSON.stringify(jsonPost));        
    });

});

function retorna_posto(retorno){
    $("#posto_id").val(retorno.posto);
    $("#codigo_posto").val(retorno.codigo);
    $("#descricao_posto").val(retorno.nome);
}

function selecionaTodos(){
    if ($("#checkAll").prop("checked")){
        $('.check').each(
            function(){
                $(this).prop("checked", true);
            }
        );
    }else{
        $('.check').each(
            function(){
                $(this).prop("checked", false);
            }
        );
    }
}

function aprovarTodos(){
    var confirm1 = confirm('Deseja liberar as OSs selecionados ?');

    if (confirm1) {
        $('.check').each(function(){
            if($(this).is(":checked")){
                var os_ajax = $(this).val();
                var remove = $(this);
                $.ajax({
                    url: "<?php echo $_SERVER['PHP_SELF']; ?>",
                    type: "POST",
                    data: {
                        aprovarCheck: 'ok', 
                        os_ajax: os_ajax 
                    },
                    complete: function(data){
                        var results = data.responseText.split("|");                        
                        remove.remove();
                        if (typeof (results[0]) != 'undefined') {
                            if (results[0] == 'ok') {
                                $("label[for='os_liberada_"+os_ajax+"']").css("display","block").html('<span class="label label-success">Aprovado</span>');
                            }else{
                                $("label[for='os_liberada_"+os_ajax+"']").css("display","block").html('<span class="label label-important">Não Aprovado</span>');
                                alert(results[1]);
                            }
                        }else{
                            alert ('Fechamento nao processado');
                        }
                    }
                });
            }
        });
    } else {
        return false;
    }
}
</script>

<?php 
if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php 
} ?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<FORM name='frm_lancamentos_os_prestacao_servico' METHOD='POST' ACTION='<?=$PHP_SELF?>' class="form-search form-inline" enctype="multipart/form-data">
    <div id="div_consulta_lancamento_os" class="tc_formulario">
        <div class="titulo_tabela">Cadastro de Processo</div>
        <br />
        <div class="row-fluid">
            <div class="span2"></div>
            <div class="span2">
                <div class='control-group <?=(in_array("data_consulta", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class="control-label" for="data_inicio">Data Início</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <h5 class='asteristico'>*</h5>  
                            <input id="data_inicio" name="data_inicio" class="span12" type="text" value="<?=$data_inicio;?>" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span2"></div>
            <div class="span2">
                <div class='control-group <?=(in_array("data_consulta", $msg_erro["campos"])) ? "error" : ""?>' >
                    <label class="control-label" for="data_fim">Data Fim</label>                    
                    <div class="controls controls-row">
                        <div class="span12">
                            <h5 class='asteristico'>*</h5>  
                            <input id="data_fim" name="data_fim" class="span12" type="text" value="<?=$data_fim;?>" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span2"></div>
        </div>
        <br />
        <div class="row-fluid">
            <div class="span2"></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='codigo_posto'>Código Posto</label>
                    <div class='controls controls-row'>
                        <div class='span10 input-append'>
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
                        <div class='span11 input-append'>
                            <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
                            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                        </div>
                    </div>
                </div>
            </div>  
            <div class="span2"></div>
        </div>
        <br />
        <div class="row-fluid">
            <div class="span2"></div>
            <div class='span4'>
                <div class='control-group' >
                    <label class="control-label" for="status_os">Status da OS</label>
                    <div class="controls controls-row">
                        <div class="span11">
                            <select id="status_os" name="status_os" class="span12">
                                <option value="">Selecione</option>
                                <?php
                                $sql = "SELECT status_checkpoint,descricao,cor FROm tbl_status_checkpoint WHERE status_checkpoint IN (0,1,2,3,4,9)";
                                $res = pg_query($con,$sql);
                                if (pg_num_rows($res) > 0) {
                                    while ($result = pg_fetch_object($res)) {
                                        $selected  = (trim($result->status_checkpoint) == trim($status_os)) ? "SELECTED" : "";
                                        echo "<option value='{$result->status_checkpoint}' {$selected} >{$result->descricao} </option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>              
            </div>
            <div class="span6"></div>
        </div>
        <br />
        <div class="row-fluid">
            <div class="span4"></div>
            <div class="span4">
                <div class="control-group">
                    <div class="controls controls-row tac">                     
                        <input type="submit" class="btn" id="btn_pesquisar" name="btn_pesquisar" value="Pesquisar" />
                    </div>
                </div>
            </div>
            <div class="span4"></div>
        </div>
    </div>
</FORM>
</div>
<?php
if (isset($resSubmit)) {
    if (pg_num_rows($resSubmit) > 0) { ?>
        <br />
        <table id="tabela_lancamento_os_prestacao_servico" class='table table-striped table-bordered table-hover table-fixed' >
            <thead>
                <tr class="titulo_coluna">
                    <?php
                    if ($status_os == 3) { ?>
                        <th align='center' >Selecionar Todas <br />
                            <input type="checkbox" id="checkAll" onclick="selecionaTodos();">
                        </th>
                    <?php
                    } ?>                    
                    <th>Código do Posto</th>
                    <th>Nome do Posto</th>
                    <th>OS</th>
                    <th>Data Abertura</th>                  
                    <th>Qtde. Peças</th>
                    <th>Total Peças</th>                    
                    <th>Total MO</th>
                    <th>Total OS</th>                   
                </tr>
            </thead>
            <tbody>
            <?php
            $i = 0;
            while($resultSubmit = pg_fetch_object($resSubmit)){
                $total_os = $resultSubmit->total_mo + $resultSubmit->total_pecas;
                ?>
                <tr>
                    <?php
                    if ($status_os == 3) { ?>
                        <td class="tac">
                            <input type="checkbox" class="check" name="os_<?=$i?>" value="<?=$resultSubmit->os?>">
                            <label for="os_liberada_<?=$resultSubmit->os?>" style="display:none;""> 
                        </td>
                    <?php
                    } ?>
                    <td class="tac"><?=$resultSubmit->codigo_posto?></td>
                    <td class="tac"><?=$resultSubmit->nome_posto?></td>
                    <td class="tac">
                        <a href="os_press.php?os=<?=$resultSubmit->os?>" target="_blanck"><?=$resultSubmit->os?></a>
                    </td>
                    <td class="tac"><?=$resultSubmit->data_abertura?></td>
                    <td class="tac"><?=$resultSubmit->qtde_pecas?></td>
                    <td class="tac">R$ <?=number_format($resultSubmit->total_pecas,2,',','')?></td>
                    <td class="tac">R$ <?=number_format($resultSubmit->total_mo,2,',','')?></td>
                    <td class="tac">R$ <?=number_format($total_os,2,',','')?></td>
                </tr>
            <?php
                $i++;
            }
            ?>

            </tbody>
            <?php
            if ($status_os == 3) { ?>
                <tfoot>
                    <tr>
                        <td colspan="2" class="tac">
                            <button class='btn' id="btn_acao" type="button"  onclick="aprovarTodos()" value="liberar">Liberar Selecionados</button>
                        </td>
                        <td colspan="7" class="tac">
                        </td>
                    </tr>
                </tfoot>
            <?php
            } ?>            
        </table>
        <br />
        <?php
        $jsonPOST = excelPostToJson($_POST); ?>
        <div id='gerar_excel' class="btn_excel">
            <input type="hidden" id="jsonPOST" value='<?= $jsonPOST; ?>' />
            <span class="txt">Gerar Arquivo CSV</span>
        </div>
        <?php
        $sqlAdminEmail = "SELECT email FROM tbl_admin WHERE admin = $login_admin AND email <> '' AND email IS NOT NULL;";
        $queryAdminEmail = pg_query($con, $sqlAdminEmail);
        if (pg_num_rows($queryAdminEmail) == 0) { ?>
            <br/>
            <dir class="container">
                <div class="row">
                    <div class="alert alert-warning">
                        Para exportar os extratos é necessário <strong>ter email cadastrado no sistema.</strong>
                        <a href="admin_senha.php">Clique aqui</a> para cadastrar.
                    </div>
                </div>
            </dir>
            
        <?php
        } else {
            $admin_email = pg_fetch_result($queryAdminEmail, 0, 'email');
            ?>
            <br/>
            <div class="container alert alert-info">
                <div class="row">
                    <div class="span11">
                        <div class="control-group">
                            <div>                     
                                Só serão exportados os Extratos que foram <B>Aprovados e Liberados</b>
                                <br/>
                                <br/>
                                Só será enviado por e-mail caso selecione algum e-mail
                                <br />
                                O arquivo será enviado <strong>para o seu email.</strong> Caso deseje enviar para mais admins, selecione abaixo:
                                <br/>
                            </div>
                        </div>
                    </div>
                </div>
                <br />
                <div class="row-fluid">
                    <div class="span10">
                        <div class='control-group' >
                            <label class="control-label" for="status_os">Adicionar Email</label>
                            <div class="controls controls-row">
                                <div class="span11">
                                    <select name="exporta_email[]" id="exporta_email" multiple='multiple'>
                                        <?php
                                        $sqlEmails = "SELECT DISTINCT email FROM tbl_admin WHERE ativo = 't' AND fabrica = 20 AND pais = 'BR' AND email <> '' AND email IS NOT NULL ORDER BY email";
                                        $queryEmails = pg_query($con, $sqlEmails);

                                        if (pg_num_rows($queryEmails) > 0) {
                                            while ($fetch = pg_fetch_object($queryEmails)) {
                                                $selected  = (trim($fetch->status_checkpoint) == trim($status_os)) ? "SELECTED" : "";
                                                ?>
                                                <option value='<?=$fetch->email?>' <?=$selected?> ><?=$fetch->email?></option>
                                            <?php
                                            }
                                        } ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php
        }
    } else { ?>
        <div class='container'>
            <div class='alert'>
                <h4>Nenhum resultado encontrado</h4>
            </div>
        </div>
    <?php
    }
}
include "rodape.php";

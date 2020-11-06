<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';
include 'rocketchat_api.php';

$sqlChatFila = "SELECT posto, 
                       to_char(solicitado_em,'DD/MM/YYYY HH24:MI') AS solicitado_em,
                       chat_fila
                    FROM tbl_chat_fila 
                   WHERE ativo = 't' 
                     AND fabrica = {$login_fabrica} 
                ORDER BY solicitado_em ASC;";
$resChatFila = pg_query($con, $sqlChatFila);
$total       = pg_num_rows($resChatFila);

if ($_POST['arquivar'] == true) {
    
    $chat_fila = $_POST['chat_fila'];

    if (empty($chat_fila)) {
        exit(json_encode(array("erro" => true, "msn" => utf8_encode("Atendimento não encontrado."))));
    }

    $sqlUP = "UPDATE tbl_chat_fila SET ativo = 'f' WHERE chat_fila = {$chat_fila} AND fabrica = {$login_fabrica};";
    $resUP = pg_query($con, $sqlUP);

    if (!pg_last_error($con)) {
        exit(json_encode(array("success" => true, "msn" => utf8_encode("Atendimento arquivado com sucesso."))));
    } else {
        exit(json_encode(array("erro" => true, "msn" => utf8_encode("Erro ao arquivar este atendimento, verifique."))));
    }

}

if ($_POST['ajax_inicia_chat'] == true) {

    $posto_chat_id = $_POST['posto_chat_id'];
    $chat_fila_id  = $_POST['chat_fila_id'];

    $sqlChatFila = "SELECT chat_fila
                    FROM tbl_chat_fila 
                   WHERE ativo = 'f' 
                     AND fabrica = {$login_fabrica} 
                     AND chat_fila = {$chat_fila_id};";
    $resChatFila = pg_query($con, $sqlChatFila);

    if (pg_num_rows($resChatFila) > 0) {
        exit(json_encode(array("erro" => true, "msn" => "Atendimento já iniciado por outro atendente.")));
    }

    $sqlAdminChat  = "SELECT parametros_adicionais
                       FROM tbl_admin 
                      WHERE fabrica = {$login_fabrica} 
                        AND admin = {$login_admin} ;";
    $resAdminChat  = pg_query($con, $sqlAdminChat);

    $xparametros_adicionais = pg_fetch_result($resAdminChat, 0, 'parametros_adicionais');
    $parametros_adicionais  = json_decode($xparametros_adicionais,1);
    $atendente_chat_id      = $parametros_adicionais['rocketchat_id'];

    if (empty($atendente_chat_id)) {
        exit(json_encode(array("erro" => true, "msn" => "Atendente não integrado ao chat.")));
    }

    $iniciaAtendimento = iniciaAtendimento($login_fabrica, $posto_chat_id, $atendente_chat_id);
    if (!empty($iniciaAtendimento['message'])) {
        exit(json_encode(array("erro" => true, "msn" => $iniciaAtendimento['message'])));
    }

    $sqlChatFilaUP = "UPDATE tbl_chat_fila SET ativo='f', chat={$iniciaAtendimento[0]['chat']} WHERE fabrica = {$login_fabrica} AND chat_fila = {$chat_fila_id};";
    $resChatFilaUP = pg_query($con, $sqlChatFilaUP);
 
    exit(json_encode(array("success" => true, "dados" => $iniciaAtendimento)));
}

if ($_POST['ajax'] == true) {
    $sqlChatFila = "SELECT posto, 
                       to_char(solicitado_em,'DD/MM/YYYY HH24:MI') AS solicitado_em,
                       chat_fila
                    FROM tbl_chat_fila 
                   WHERE ativo = 't' 
                     AND fabrica = {$login_fabrica} 
                ORDER BY solicitado_em ASC;";
    $resChatFila = pg_query($con, $sqlChatFila);
    $dadosFila   = pg_fetch_all($resChatFila);


    foreach ($dadosFila as $keyFila => $valueFila) {
        $chat_fila     = $valueFila['chat_fila'];
        $posto         = $valueFila['posto'];
        $solicitado_em = $valueFila['solicitado_em'];
        
        $sqlPosto = "SELECT tbl_posto_fabrica.parametros_adicionais,tbl_posto_fabrica.codigo_posto,tbl_posto.nome AS xnome
                        FROM tbl_posto_fabrica 
                        JOIN tbl_posto ON tbl_posto.posto=tbl_posto_fabrica.posto 
                       WHERE tbl_posto_fabrica.fabrica = {$login_fabrica} 
                         AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
                         AND tbl_posto_fabrica.posto = {$posto};";
        $resPosto = pg_query($con, $sqlPosto);

        $xcodigo_posto          = pg_fetch_result($resPosto, 0, 'codigo_posto');
        $xnome_posto            = pg_fetch_result($resPosto, 0, 'xnome');
        $xparametros_adicionais = pg_fetch_result($resPosto, 0, 'parametros_adicionais');
        $parametros_adicionais  = json_decode($xparametros_adicionais,1);

        $conteudo .='
        <div class="span5">
            <div class="well well-large">
                <p><em>Solicitado em: <b>'.$solicitado_em.'</b></em></p>
                <h5>'.$xcodigo_posto.' - ' .$xnome_posto.'</h5>
                <button type="button" data-id="'.$chat_fila.'" data-postoid="'.$parametros_adicionais['rocketchat_id'].'" class="btn btn-success btn-small btn_inicia_chat"><i class="icon-comment icon-white"></i> Iniciar Chat</button> 
                <button type="button" data-id="'.$chat_fila.'" class="btn btn-info btn-small btn_arquivar"><i class="icon-circle-arrow-down icon-white"></i> Arquivar</button>
            </div>
        </div>';
    }
    exit($conteudo);
}


$layout_menu = "gerencia";
$title       = "CHAT FILA";
include "cabecalho_new.php";

?>
<script type="text/javascript">
    $(function() {
        $("body").on("click",".btn_inicia_chat", function(){
            var posto_chat_id = $(this).data("postoid");
            var chat_fila_id = $(this).data("id");

            if (posto_chat_id == "" || posto_chat_id == undefined) {
                alert("Posto Autorizado, não integrado ao chat.");
                return false;
            }

            $.ajax({
                url: "fila_chat.php",
                type: "POST",
                data: {
                        ajax_inicia_chat    : true, 
                        posto_chat_id   : posto_chat_id,
                        chat_fila_id : chat_fila_id
                      },
                beforeSend: function () {
                    $("#loading-block").show();
                    $("#loading").show();
                    $(this).addClass("disabled");
                },
                complete: function(retorno){
                    var resposta = JSON.parse(retorno.responseText);
                    console.log(resposta);
                    if (resposta.erro == true) {
                        alert(resposta.msn);
                        location.reload();
                        return false;
                    }
                    
                    if (resposta.erro == true) {
                        alert(resposta.msn);
                        location.reload();
                        return false;
                    } else {
                        window.open("http://colormaq.chat.telecontrol.com.br/group/"+resposta.dados[0].protocolo,"_blank");
                    }

                    $(this).removeClass("disabled");
                    $("#loading-block").hide();
                    $("#loading").hide();
                }
            });
        });

        $("body").on("click",".btn_arquivar", function(){
            var r = confirm("Deseja arquivar este atendimento?");
            if (r == true) {
                var chat_fila  = $(this).data("id");
                $.ajax({
                    url: "fila_chat.php",
                    async:false,
                    type: "POST",
                    data: {
                            arquivar    : true, 
                            chat_fila   : chat_fila
                          },
                    complete: function(retorno){
                        var resposta = JSON.parse(retorno.responseText);
                        if (resposta.erro == true) {
                            alert(resposta.msn);
                            location.reload();
                            return false;
                        }
                        
                        if (resposta.success == true) {
                            alert(resposta.msn);
                            location.reload();
                            return false;
                        }

                    }
                });
            }
        });

        setInterval(function(){
            $.ajax("#",{
                method: "POST",
                data: {
                    "ajax": true
                }
            }).done(function(response){
                $("#atendimentos").html(response);
            });
        },15000);
    });
</script>

<div class="row" style="margin: 0 auto" id="atendimentos">

<?php if ($total > 0) {
    
    for ($i = 0; $i < $total; $i++) { 
        $chat_fila     = pg_fetch_result($resChatFila, $i, 'chat_fila');
        $posto         = pg_fetch_result($resChatFila, $i, 'posto');
        $solicitado_em = pg_fetch_result($resChatFila, $i, 'solicitado_em');
        
        $sqlPosto = "SELECT tbl_posto_fabrica.parametros_adicionais,tbl_posto_fabrica.codigo_posto,tbl_posto.nome AS xnome
                        FROM tbl_posto_fabrica 
                        JOIN tbl_posto ON tbl_posto.posto=tbl_posto_fabrica.posto 
                       WHERE tbl_posto_fabrica.fabrica = {$login_fabrica} 
                         AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
                         AND tbl_posto_fabrica.posto = {$posto};";
        $resPosto = pg_query($con, $sqlPosto);

        $xcodigo_posto  = pg_fetch_result($resPosto, 0, 'codigo_posto');
        $xnome_posto    = pg_fetch_result($resPosto, 0, 'xnome');
        $xparametros_adicionais = pg_fetch_result($resPosto, 0, 'parametros_adicionais');
        $parametros_adicionais = json_decode($xparametros_adicionais,1);
    ?>
        <div class="span5">
            <div class="well well-large">
                <p><em>Solicitado em: <b><?php echo $solicitado_em;?></b></em></p>
                <h5><?php echo $xcodigo_posto;?> - <?php echo $xnome_posto;?></h5>
                <button type="button" data-id="<?php echo $chat_fila;?>" data-postoid="<?php echo $parametros_adicionais['rocketchat_id'];?>" class="btn btn-success btn-small btn_inicia_chat"><i class="icon-comment icon-white"></i> Iniciar Chat</button> 
                <button type="button" data-id="<?php echo $chat_fila;?>" class="btn btn-info btn-small btn_arquivar"><i class="icon-circle-arrow-down icon-white"></i> Arquivar</button>
            </div>
        </div>
    <?php }?>
<?php } else {?>
    <div class="alert alert-warning">
        <h4>Nenhuma fila de chat pendente.</h4>
    </div>
<?php }?>
</div>
<?php include 'rodape.php'; ?>

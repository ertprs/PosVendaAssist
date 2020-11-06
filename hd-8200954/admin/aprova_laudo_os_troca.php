<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "../class/email/PHPMailer/class.phpmailer.php";
include "../class/email/PHPMailer/PHPMailerAutoload.php";

$admin_privilegios = "auditoria";

include "autentica_admin.php";
include 'funcoes.php';

function ultima_interacao($os) {
    global $con, $login_fabrica;

    $select = "SELECT admin, posto FROM tbl_os_interacao WHERE fabrica = {$login_fabrica} AND os = {$os} ORDER BY data DESC LIMIT 1";
    $result = pg_query($con, $select);

    if (pg_num_rows($result) > 0) {
        $admin = pg_fetch_result($result, 0, "admin");
        $posto = pg_fetch_result($result, 0, "posto");

        if (!empty($admin)) {
            $ultima_interacao = "fabrica";
        } else {
            $ultima_interacao = "posto";
        }
    }

    return $ultima_interacao;
}


$sql = "SELECT  aprova_laudo
        FROM    tbl_admin
        WHERE   fabrica = $login_fabrica
        AND     admin = $login_admin
";
$res = pg_query($con,$sql);
$aprova_laudo = pg_fetch_result($res,0,aprova_laudo);
// echo nl2br($sql);exit;

$btn_acao    = trim($_POST["btn_acao"]);
# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
    $tipo_busca = $_GET["busca"];

    if (strlen($q)>2){
        $sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
                FROM tbl_posto
                JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
        if ($tipo_busca == "codigo"){
            $sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
        }else{
            $sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
        }

        $res = pg_query($con,$sql);
        if (pg_num_rows ($res) > 0) {
            for ($i=0; $i<pg_num_rows ($res); $i++ ){
                $cnpj         = trim(pg_fetch_result($res,$i,cnpj));
                $nome         = trim(pg_fetch_result($res,$i,nome));
                $codigo_posto = trim(pg_fetch_result($res,$i,codigo_posto));
                echo "$cnpj|$nome|$codigo_posto";
                echo "\n";
            }
        }
    }
    exit;
}

$select_acao = trim($_POST["select_acao"]);

if(strlen($btn_acao)>0 AND strlen($select_acao)>0){
    $qtde_os     = trim($_POST["qtde_os"]);
    $observacao  = trim($_POST["observacao"]);
// echo $select_acao;exit;

    if(strlen($observacao) == 0){
        $msg_erro .= "É Obrigatório o cadastro de motivo";
    }

    $email_os = "";
    $res = pg_query($con,"BEGIN TRANSACTION");
    for ($x=0;$x<$qtde_os;$x++){

        $xxos = trim($_POST["check_".$x]);
        if(strlen($xxos) > 0){
            $email_os[] = $xxos;
        }else{
            continue;
        }
        if (strlen($xxos) > 0 AND strlen($msg_erro) == 0){

            if($select_acao == 193){
                /**
                 * - Caso a auditoria seja APROVADA
                 */

                $fraseEmail = "APROVADO(S)";
                $sqlUp = "
                UPDATE  tbl_os_troca
                SET     status_os = 193
                WHERE   os = $xxos
                ";
                $resUp = pg_query($con,$sqlUp);
                $msg_erro = pg_last_error($con);

                $sqlIns = "
                INSERT INTO tbl_os_status (
                    os        ,
                    status_os ,
                    observacao,
                    admin,
                    status_os_troca
                ) VALUES (
                    $xxos,
                    193,
                    'LAUDO APROVADO. MOTIVO: $observacao',
                    $login_admin,
                    't'
                );
                ";
                $msg_erro = pg_last_error($con);
                $resIns = pg_query($con,$sqlIns);
            }else if($select_acao == 194){
                /**
                 * - Caso a auditoria seja RECUSADA
                 */

                $fraseEmail = "REPROVADO(S)";
                $sqlUp = "
                    UPDATE  tbl_os_troca
                    SET     status_os = 194
                    WHERE   os = $xxos
                ";
                $resUp = pg_query($con,$sqlUp);
                $msg_erro = pg_last_error($con);

                $sqlIns = "
                INSERT INTO tbl_os_status (
                    os        ,
                    status_os ,
                    observacao,
                    admin,
                    status_os_troca
                ) VALUES (
                    $xxos,
                    194,
                    'LAUDO RECUSADO. MOTIVO: $observacao',
                    $login_admin,
                    't'
                );
                ";
                $resIns = pg_query($con,$sqlIns);
                $msg_erro = pg_last_error($con);

                $sqlUp2 = " UPDATE  tbl_laudo_tecnico_os
                            SET     observacao = NULL
                            WHERE   os = $xxos
                ";
                $resUp2 = pg_query($con,$sqlUp2);
                $msg_erro = pg_last_error($con);
            }
        }
    }
    if(strlen($msg_erro) == 0){
        $res = pg_query($con,"COMMIT TRANSACTION");
        $mailer = new PHPMailer;

        $email_os = implode(",",$email_os);
        $sqlMail = "
            SELECT  DISTINCT
                    email,
                    nome_completo
            FROM    tbl_admin
            JOIN    tbl_os_status   ON  tbl_os_status.admin     = tbl_admin.admin
                                    AND tbl_os_status.status_os = 192
            WHERE   tbl_admin.fabrica   = $login_fabrica
            AND     tbl_os_status.os    IN($email_os);
        ";
        $resMail = pg_query($con,$sqlMail);
        $conta = pg_numrows($resMail);

        $mailer->isSMTP();
        $mailer->IsHTML();

        $mailer->From = "no-reply@telecontrol.com.br";
        $mailer->FromName = "Posvenda Telecontrol";
        for($i=0;$i<$conta;$i++){
            $usuario = pg_fetch_result($resMail,$i,nome_completo);
            $email   = pg_fetch_result($resMail,$i,email);
            $mailer->addAddress($email,$usuario);
        }
//         $mailer->addAddress("william.brandino@telecontrol.com.br","William Ap. Brandino");
        $mailer->Subject = "Laudos Técnicos";
        $msg = "Prezado(s),";
        $msg .= "<br />O(s) laudo(s) técnico(s) foi(ram) $fraseEmail";
        $msg .= "<br /><br />Favor, acessar o sistema para verificar a OS de sua responsabilidade:";
        $msg .= "<br /><b>$email_os</b>";
        $mailer->Body = $msg;

        if($mailer->Send()){
            header("Location: $PHP_SELF?msg_sucesso=Gravado com Sucesso");
        }
    }else{
        $res = pg_query($con,"ROLLBACK TRANSACTION");
        header("Location: $PHP_SELF?msg_erro=".$msg_erro);
    }
}

$layout_menu = "auditoria";
$title = "APROVAÇÃO LAUDO ORDEM DE SERVIÇO DE TROCA";

include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable",
    "multiselect"
);

include("plugin_loader.php");
?>

<style type="text/css">

.Tabela{
    border:1px solid #596D9B;
    background-color:#596D9B;
}
.Erro{
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 12px;
    color:#CC3300;
    font-weight: bold;
    background-color:#FFFFFF;
}
.Titulo {
    text-align: center;
    font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: bold;
    color: #FFFFFF;
    background-color: #596D9B;
}
.Conteudo {
    font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: normal;
}

.menu_top {
    text-align: center;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: x-small;
    font-weight: bold;
    border: 1px solid;
    color:#ffffff;
    background-color: #596D9B
}

.table_line {
    text-align: left;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: normal;
    border: 0px solid;
    background-color: #D9E2EF
}

.table_line2 {
    text-align: left;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: normal;
}

/*****************************
ELEMENTOS DE POSICIONAMENTO
*****************************/

#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}
.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}


.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
    width: 700px;
    margin: 0 auto;
}

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
}

.subtitulo {
    background-color: #7092BE;
    font:bold 11px Arial;
    color: #FFFFFF;
}
hr {
    color: #FFFFFF;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.msg_sucesso{
    background-color: green;
    font: bold 16px "Arial";
    color: #FFFFFF;
    text-align:center;
}

.env-interagir{
    width: 800px;
    min-height: 400px;
    border: 3px solid #e2e2e2;
    background: #fff;
    position: fixed;
    top: 50px;
    left: 20%;

    display: none;
}

.env-interagir textarea{
    margin-top: 20px;
    width: 500px;
    height: 100px;

}

.env-buttons{
    margin-top: 15px;
    width: 100%;
}

#interacao-msg{
    min-height: 0px;
    background: #e2e2e2;
    position: absolute;
    width: 100%;
}

#env-interacoes{
    height: 181px;
    overflow-y: scroll;
}

#env-interacoes table{
    width: 100%;
}

</style>
<script type="text/javascript">
function fnc_pesquisa_posto(campo, campo2, tipo) {
    if (tipo == "codigo" ) {
        var xcampo = campo;
    }

    if (tipo == "nome" ) {
        var xcampo = campo2;
    }

    if (xcampo.value != "") {
        var url = "";
        url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
        janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
        janela.codigo  = campo;
        janela.nome    = campo2;
        janela.focus();
    }
}

var ok = false;
var cont=0;

function checkaTodos() {
    f = document.frm_pesquisa2;
    if (!ok) {
        for (i=0; i<f.length; i++){
            if (f.elements[i].type == "checkbox"){
                f.elements[i].checked = true;
                ok=true;
                if (document.getElementById('linha_'+cont)) {
                    document.getElementById('linha_'+cont).style.backgroundColor = "#F0F0FF";
                }
                cont++;
            }
        }
    }else{
        for (i=0; i<f.length; i++) {
            if (f.elements[i].type == "checkbox"){
                f.elements[i].checked = false;
                ok=false;
                if (document.getElementById('linha_'+cont)) {
                    document.getElementById('linha_'+cont).style.backgroundColor = "#FFFFFF";
                }
                cont++;
            }
        }
    }
}

function changeColorLine(os){

    $(".btn-interagir").each(function(idx,elem){
        if($(elem).data("os") == os){
            var tr = $(elem).parents("tr").first();
            $(tr).attr("style","background: #FFDC4C");
        }
    });
}

var timeHelper;
function clearMessage(){
    window.clearTimeout(timeHelper);

    timeHelper =  setTimeout(function(){
        $("#interacao-msg").html("");
    },5000);

}

function getInterations (os) {
    $("#tr-coments").html("");

    <?php if ($login_fabrica == 30) { ?>
            Shadowbox.open({
                content: "relatorio_interacao_os.php?interagir=true&os="+os,
                player: "iframe",
                width: 850,
                height: 600,
                title: "Ordem de Serviço "+os
            });
    <?php } else { ?>

            $.ajax("ajax_interagir_os.php",{
                data:{
                    os: os
                }
            }).done(function(response){

                $.each(response,function(idx,elem){
                    var tr = $("<tr>");

                    $(tr).append($("<td>").html(elem.comentario));
                    if(elem.nome_completo == ""){
                        $(tr).append($("<td>").html(elem.nome_fantasia ));
                    }else{
                        $(tr).append($("<td>").html(elem.nome_completo));
                    }

                    $(tr).append($("<td>").html(elem.data));

                    $("#tr-coments").append(tr);
                });
            });
    <?php } ?>
}

$(function(){
    $(".btn-interagir").click(function(){
        var os = $(this).data("os");

        getInterations(os);

        <?php if ($login_fabrica != 30) { ?>

            $("#btn-grava-interacao").data("os",os);
            $("#txt-interacao").val("");
            $("#os-number-env").html(os);

            $(".env-interagir").fadeIn("500");

    <?php } ?>
    });


    $("#btn-grava-interacao").click(function(){
        var os = $(this).data("os");
        var text = $("#txt-interacao").val();

        if(text == ""){
            $("#interacao-msg").html("Digite uma interação");
            clearMessage();

            return false;
        }

        $("#btn-grava-interacao").html("Gravando...");

        $.post("ajax_interagir_os.php",{
            interacao: text,
            os: os
        }).done(function(response){
            $("#btn-grava-interacao").html("Gravar");

            if(response.exception == undefined && response.msg == "ok"){
                $("#interacao-msg").html("Interação Gravada!!!");
                clearMessage();


                changeColorLine(os);

                setTimeout(function(){
                    $(".env-interagir").fadeOut("500");
                    $("#interacao-msg").html("");
                },1500);
            }else{
                $("#interacao-msg").html(response.exception);
                clearMessage();
            }
        });
    });

    $("#btn-cancela-interacao").click(function(){
        $(".env-interagir").fadeOut("500");
    });
});


</script>

<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>


<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>

<script type="text/javascript" charset="utf-8">
$(function(){
    $.datepickerLoad(Array("data_final", "data_inicial"));
    $.autocompleteLoad(Array("posto"));
    Shadowbox.init();

    $("span[rel=lupa]").click(function () {
        $.lupa($(this));
    });

    $("#classificacao_atendimento").multiselect({
       selectedText: "selecionados # de #"
    });

});

    function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
        $("#descricao_posto").val(retorno.nome);
    }

</script>
<?
if($btn_acao == 'Pesquisar'){
    $data_inicial           = $_POST['data_inicial'];
    $data_final             = $_POST['data_final'];
    $laudo                  = $_POST['laudo'];
    $status                 = $_POST['status'];
    $os_troca_especifica    = $_POST['os_troca_especifica'];
    $posto_codigo           = $_POST['posto_codigo'];

    if((strlen($data_inicial) == 0 or strlen($data_final) == 0) and strlen($os_troca_especifica) == 0) {
        $msg_erro = "É necessário a inclusão das datas ou a busca direta por OS";
    }

    if(strlen($data_inicial) > 0 and strlen($data_final) > 0 and strlen($os_troca_especifica) == 0) {

        if(strlen($msg_erro)==0){
            list($di, $mi, $yi) = explode("/", $data_inicial);
            if(!checkdate($mi,$di,$yi))
                $msg_erro = "Data Inválida";
        }

        if(strlen($msg_erro)==0){
            list($df, $mf, $yf) = explode("/", $data_final);
            if(!checkdate($mf,$df,$yf))
                $msg_erro = "Data Inválida";
        }

        if(strlen($msg_erro)==0){
            $aux_data_inicial = "$yi-$mi-$di";
            $aux_data_final = "$yf-$mf-$df";
        }
        if(strlen($msg_erro)==0){
            if(strtotime($aux_data_final) < strtotime($aux_data_inicial)
            or strtotime($aux_data_final) > strtotime('today')){
                $msg_erro = "Data Final não pode ser maior que Data Atual.";
            }
        }

        if(strlen($msg_erro)==0){
            if (strtotime($aux_data_inicial.'+3 month') < strtotime($aux_data_final) ) {
                    $msg_erro = 'O intervalo entre as datas não pode ser maior que 3 meses';
            }
        }

        $xdata_inicial = formata_data ($data_inicial);
        $xdata_inicial = $xdata_inicial." 00:00:00";

        $xdata_final = formata_data ($data_final);
        $xdata_final = $xdata_final." 23:59:59";
    }

    if(strlen($posto_codigo) > 0){
        $sqlPosto = "SELECT  posto
                FROM    tbl_posto_fabrica
                WHERE   tbl_posto_fabrica.codigo_posto  = '$posto_codigo'
                AND     tbl_posto_fabrica.fabrica       = $login_fabrica ";
        $resPosto = pg_query($con,$sqlPosto);
        if(pg_numrows($resPosto) > 0) {
            $posto = pg_fetch_result($resPosto,0,'posto');
        }else{
            $msg_erro = "Posto informado nao encontrado";
        }
    }
}

if(strlen($msg_erro) > 0){
    if (strpos($msg_erro,"ERROR: ") !== false) {
        $x = explode('ERROR: ',$msg_erro);
        $msg_erro = $x[1];
    }

    echo "<div class='alert alert-danger'><h4>$msg_erro</h4></div>";
}

if(strlen($msg_sucesso) > 0){
    echo "<div class='msg_sucesso' align='center'>$msg_sucesso</div>";
}
?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<div class='env-interagir' style="text-align: center;z-index: 2;">
    <h1 align="center">Interagir na OS <span id="os-number-env">123123</span></h1>
    <div id="interacao-msg"></div>
    <textarea id="txt-interacao" name="txt-interacao" rows="3"></textarea>

    <div class="env-buttons" style="text-align: center;">
        <button type="button" id="btn-grava-interacao" class="btn btn-primary" data-os="">Gravar</button>
        <button type="button" id="btn-cancela-interacao" class="btn btn-danger">Fechar</button>
    </div>
    <hr>
    <div id='env-interacoes'>
        <table border="0" cellspacing="1" class="table">
            <thead>
                <tr class="titulo_coluna">
                    <th>Interação</th>
                    <th>Admin</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody id="tr-coments">
            </tbody>
        </table>
    </div>
</div>




<form name="frm_pesquisa" method="post" class='form-search form-inline tc_formulario' action="<?echo $PHP_SELF?>">
<input type="hidden" name="acao">
    <div class="titulo_tabela">Parâmetros de Pesquisa</div>
    <div class='row-fluid'>
        <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_inicial'>Data Inicial</label>
                    <div class='controls controls-row'>
                        <div class='span4'>
                            <h5 class='asteristico'>*</h5>
                                <input type="text" name="data_inicial" id="data_inicial" maxlength="10" value="<? echo $data_inicial ?>" class="span12" />
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
                            <input type="text" name="data_final" id="data_final" maxlength="10" value="<? echo $data_final ?>" class="span12" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div> 
    <div class='row-fluid'>
        <div class='span2'></div>
        <b>Tipo de Laudo:</b><br />
        <div class='span2'>
             <label class="radio">
                <INPUT TYPE="radio" NAME="laudo" value='' checked> Todos
            </label>
        </div>
        <div class='span1'>
            <label class="radio">
                <INPUT TYPE="radio" NAME="laudo" value='fat' <? if(trim($laudo) == 'fat') echo "checked='checked'"; ?>>FAT
            </label>
        </div>
        <div class='span1'>
            <label class="radio">
                <INPUT TYPE="radio" NAME="laudo" value='far' <? if(trim($laudo) == 'far') echo "checked='checked'"; ?>>FAR
            </label>
        </div>
        <div class='span2'>
            <label class="radio">
                <INPUT TYPE="radio" NAME="laudo" value='fats' <? if(trim($laudo) == 'fats') echo "checked='checked'"; ?>>FAT SINISTRO
            </label>
        </div>
        <div class='span2'>
            <label class="radio">
                <INPUT TYPE="radio" NAME="laudo" value='fatrev' <? if(trim($laudo) == 'fatrev') echo "checked='checked'"; ?>>FAT REVENDA
            </label>
        </div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <b> Status da OS:</b><br />
        <div class='span2'>
            <label class="radio">
                <INPUT TYPE="radio" NAME="status" value='aprovacao' <? if(trim($status) == 'aprovacao' || $status == "") echo "checked='checked'"; ?>>EM APROVAÇÃO
            </label>
        </div>
        <div class='span2'>
            <label class="radio">
                <INPUT TYPE="radio" NAME="status" value='aprovada' <? if(trim($status) == 'aprovada') echo "checked='checked'"; ?>>APROVADAS
            </label>
        </div>
        <div class='span3'>
            <label class="radio">
                <INPUT TYPE="radio" NAME="status" value='reprovada' <? if(trim($status) == 'reprovada') echo "checked='checked'"; ?>>REPROVADAS
            </label>
        </div>
    </div>
    <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='codigo_posto'>Código Posto</label>
                    <div class='controls controls-row'>
                        <div class='span7 input-append'>
                            <input style="position: relative;" type="text" name="posto_codigo" id="codigo_posto" size="9" value="<?echo $posto_codigo?>" class="frm">
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
                            <input style="z-index: 1;" type="text" name="posto_nome" id="descricao_posto" size="31" value="<?echo $posto_nome?>" class="frm">
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
            <div class='span8'>
                <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='codigo_posto'>O.S Troca Específica</label>
                    <div class='controls controls-row'>
                        <div class='span7'>
                            <input type="text" name="os_troca_especifica" id="os_troca_especifica" size="13" value="<?echo $os_troca_especifica?>" class="frm">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span8'>
                <div class='control-group'>
                    <label class='control-label' for='codigo_posto'>Classificação do Atendimento</label>
                    <div class='controls controls-row'>
                        <div class='span7'>
                            <select id="classificacao_atendimento" name="classificacao_atendimento[]" multiple="multiple" size="1" class="frm">
                                <?php
                                    $aux_sql = "SELECT hd_classificacao, descricao FROM tbl_hd_classificacao WHERE fabrica = $login_fabrica AND hd_classificacao IN (50, 51, 52) ORDER BY descricao";
                                    $aux_res = pg_query($con, $aux_sql);
                                    $aux_row = pg_num_rows($aux_res);

                                    for ($wx = 0; $wx < $aux_row; $wx++) { 
                                        $hd_classificacao = pg_fetch_result($aux_res, $wx, 'hd_classificacao');
                                        $hd_descricao     = pg_fetch_result($aux_res, $wx, 'descricao');

                                        if ($_POST["classificacao_atendimento"] == $hd_classificacao) {
                                            $selected = "SELECTED";
                                        } else {
                                            $selected = "";
                                        }

                                        ?> <option <?=$selected;?> value="<?=$hd_classificacao;?>"><?=$hd_descricao;?></option> <?
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <input type='hidden' name='btn_acao' value='Pesquisar2'>
        <input class="btn" type="submit" name="btn_acao" value="Pesquisar">
        <br /><br />
</form>
</div>
<br />

<?

## Aqui começa o PROCESSO DE PESQUISA
if ($btn_acao == 'Pesquisar' and strlen($msg_erro)==0) {
    $codigo_posto = $_POST['posto_codigo'];

    if ($login_fabrica == 30 && !empty($_POST["classificacao_atendimento"])) {
        $classificacao_atendimento = $_POST["classificacao_atendimento"];

        for ($wx=0; $wx < count($classificacao_atendimento); $wx++) { 
            $classificacao_atendimento[$wx] = "'" . $classificacao_atendimento[$wx] . "'";
        }

        $join_os_campo_extra  = " LEFT JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os AND tbl_os_campo_extra.fabrica = $login_fabrica ";
        $where_os_campo_extra .= "\nAND JSON_FIELD('hd_classificacao',tbl_os_campo_extra.campos_adicionais) IN (". implode(",", $classificacao_atendimento) .")";
    } else {
        $join_os_campo_extra  = "";
        $where_os_campo_extra = "";
    }

    $sql="  SELECT  tbl_os_status.os,
                    tbl_os_status.status_os,
                    tbl_os_status.admin
       INTO TEMP    tmp_os_aprovada_antes
            FROM    tbl_os_status
            WHERE   tbl_os_status.fabrica_status = $login_fabrica
            AND     tbl_os_status.os_status IN (
                        SELECT  MAX(tbl_os_status.os_status) AS os_status
                        FROM    tbl_os_status
                        WHERE   tbl_os_status.fabrica_status = $login_fabrica
                        AND     tbl_os_status.status_os IN (192,193,194)
                  GROUP BY      tbl_os_status.os
                    );

            CREATE INDEX idx_os_aprovada_antes ON tmp_os_aprovada_antes(os);

            SELECT  DISTINCT 
                    tbl_laudo_tecnico_os.laudo_tecnico_os,
                    CASE WHEN tbl_laudo_tecnico_os.afirmativa = true
                            THEN 'Aprovado'
                         WHEN tbl_laudo_tecnico_os.afirmativa = false
                            THEN 'Reprovado'
                         ELSE 'Em Aprovação'
                    END AS status_laudo,
                    tbl_os.os                                                                   ,
                    tbl_os.sua_os                                                               ,
					tbl_os.consumidor_estado                                                    ,
                    TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')          AS data_abertura_os     ,
                    TO_CHAR(tbl_laudo_tecnico_os.data,'DD/MM/YYYY')     AS data_laudo           ,
                    tbl_posto_fabrica.codigo_posto                                              ,
                    tbl_posto_fabrica.posto                             AS id_posto             ,
                    tbl_posto.nome                                      AS nome_posto           ,
                    AGE(CURRENT_DATE,tbl_laudo_tecnico_os.data::DATE)   AS dias_decorrentes     ,
                    tbl_produto.referencia                              AS produto_referencia   ,
                    tbl_produto.descricao                               AS produto_descricao    ,
                    tbl_admin.nome_completo                             AS responsavel          ,
                    /* CASE WHEN tbl_os_troca.status_os = 193
                         THEN 'Aprovado'
                         WHEN tbl_os_troca.status_os = 194
                         THEN 'Reprovado'
                         ELSE 'Em Aprovação'
                    END                                                 AS status_laudo         , */
                    JSON_FIELD('laudo',tbl_laudo_tecnico_os.observacao) AS laudo
            FROM    tbl_os
            JOIN    tbl_laudo_tecnico_os    ON  tbl_laudo_tecnico_os.os     = tbl_os.os
            JOIN    tbl_os_troca            ON  tbl_os_troca.os             = tbl_os.os
            JOIN    tbl_posto_fabrica       ON  tbl_posto_fabrica.posto     = tbl_os.posto
                                            AND tbl_posto_fabrica.fabrica   = $login_fabrica
            JOIN    tbl_posto               ON  tbl_posto.posto             = tbl_posto_fabrica.posto
       LEFT JOIN    tbl_os_produto          ON  tbl_os_produto.os           = tbl_os.os
            JOIN    tbl_produto             ON  tbl_produto.produto         = tbl_os.produto
            JOIN    tmp_os_aprovada_antes   ON  tmp_os_aprovada_antes.os    = tbl_os.os
            JOIN    tbl_admin               ON  tbl_admin.admin             = tmp_os_aprovada_antes.admin
            {$join_os_campo_extra}
            WHERE   tbl_os.fabrica = $login_fabrica
            AND (
                SELECT tbl_os_status.os_status 
                FROM tbl_os_status 
                WHERE tbl_os_status.status_os NOT IN (193,194)
                AND tbl_os_status.observacao ILIKE 'LAUDO DEVOLVIDO%'
                AND tbl_os_status.os = tbl_os.os
                LIMIT 1
            ) IS NULL
            {$where_os_campo_extra}
    ";

    if(strlen($posto) > 0){
        $sql .= "\nAND tbl_posto.posto = $posto ";
    }

    if(strlen($xdata_inicial) > 0 and strlen($xdata_final) > 0){
        $sql .= "\nAND tbl_laudo_tecnico_os.data::DATE BETWEEN '$xdata_inicial' AND '$xdata_final' ";
    }
    if(strlen($laudo) > 0){
        switch($laudo){
            case "fat":
                $sql .= "\nAND JSON_FIELD('laudo',tbl_laudo_tecnico_os.observacao) = 'fat'";
            break;
            case "far":
                $sql .= "\nAND JSON_FIELD('laudo',tbl_laudo_tecnico_os.observacao) = 'far'";
            break;
            case "fats":
                $sql .= "\nAND JSON_FIELD('laudo',tbl_laudo_tecnico_os.observacao) = 'fats'";
            break;
            case "fatrev":
                $sql .= "\nAND JSON_FIELD('laudo',tbl_laudo_tecnico_os.observacao) = 'fatrev'";
            break;
        }
    }
    if(strlen($status) > 0){
        switch($status){
            case "aprovacao":
                // $sql .= "\nAND tbl_os_troca.status_os IS NULL";
                $sql .= "\n AND tbl_laudo_tecnico_os.afirmativa IS NULL";
            break;
            case "aprovada":
                // $sql .= "\nAND tbl_os_troca.status_os = 193";
                $sql .= "\n AND tbl_laudo_tecnico_os.afirmativa IS TRUE";
            break;
            case "reprovada":
                // $sql .= "\nAND tbl_os_troca.status_os = 194";
                $sql .= "\n AND tbl_laudo_tecnico_os.afirmativa IS FALSE";
            break;
        }
    }
    if(strlen($os_troca_especifica) > 0){
        $sql .= "\nAND tbl_os.os = $os_troca_especifica";
    }

    // echo nl2br($sql);
    $res = pg_query($con,$sql);
    $qtde = pg_num_rows($res);
    
    if($qtde > 0){
        $excel = "
        <table width='700px' border='0' align='center' cellpadding='3' cellspacing='1' class='tabela'>
            <thead>
                <tr class='titulo_coluna'>
                    <th>OS</th>
                    <th>Data Abertura</th>
                    <th>Data Laudo</th>
                    <th>Dias decorridos</th>
                    <th>Posto</th>
                    <th>Produto</th>
                    <th>Defeito Constatado</th>
                    <th>Status</th>
                    <th>Tipo Laudo</th>
                    <th>Responsável</th>
        ";

        /*HD-4047686*/
        if ($login_fabrica == 30) {
            $excel .= "
                <th>UF</th>
                <th>R$</th>
            ";
        }

        $excel .= "
                </tr>
            </thead>
            <tbody>
        ";

        if($status == "aprovacao"){
            $colspan = "colspan='2'";
        }

?>

    <form name='frm_pesquisa2' method='post' action='<?=$PHP_SELF?>'>
        <?php if($login_fabrica == 30){ ?>
        <div class="legenda-interacao" style="width: 700px;margin: 0 auto;">
            <table>
                <tr>
                    <td style="background: #A6D941;width: 30px;border: 1px solid black;">&nbsp;&nbsp;</td>
                    <td> Interação do Posto</td>
                </tr>
                <tr>
                    <td style="background: #FFDC4C;width: 30px;border: 1px solid black;">&nbsp;&nbsp;</td>
                    <td> Interação do Admin</td>
                </tr>
            </table>
        </div>
        <br />
        <?php } ?>
        <table border='0' align='center' cellpadding='3' cellspacing='1' class='table table-bordered table-fixed'>
            <thead>
                <tr class='titulo_coluna'>
                    <th>OS</th>
                    <th>Data Abertura</th>
                    <th>Data Laudo</th>
                    <th>Dias decorridos</th>
                    <th>Posto</th>
                    <th>Produto</th>
                    <th>Defeito Constatado</th>
                    <th>Status</th>
                    <th <?php echo $colspan; ?> >Laudo</th>
                    <th>Tipo Laudo</th>
                    <th>Responsável</th>
                    <?php
                    if($login_fabrica == 30){
                        ?>
                        <th>UF</th>
                        <th>R$</th>
                        <th>Interagir</th>
                        <?php
                    }
                    ?>

                </tr>
            </thead>
            <tbody>
<?
        for($i=0;$i<$qtde;$i++){
            $auditoria_os                   = pg_fetch_result($res,$i,os);
            $auditoria_sua_os               = pg_fetch_result($res,$i,sua_os);
            $auditoria_data_abertura_os     = pg_fetch_result($res,$i,data_abertura_os);
            $auditoria_data_laudo           = pg_fetch_result($res,$i,data_laudo);
            $consumidor_estado              = pg_fetch_result($res,$i,'consumidor_estado');
            $auditoria_codigo_posto         = pg_fetch_result($res,$i,codigo_posto);
            $auditoria_nome_posto           = pg_fetch_result($res,$i,nome_posto);
            $auditoria_dias_decorrentes     = pg_fetch_result($res,$i,dias_decorrentes);
            $auditoria_produto_referencia   = pg_fetch_result($res,$i,produto_referencia);
            $auditoria_produto_descricao    = pg_fetch_result($res,$i,produto_descricao);
            $auditoria_responsavel          = pg_fetch_result($res,$i,responsavel);
            $auditoria_status_laudo         = pg_fetch_result($res,$i,status_laudo);
            $auditoria_laudo                = pg_fetch_result($res,$i,laudo);

            $auditoria_dias_decorrentes = substr($auditoria_dias_decorrentes,0,2);
            $cor = ($i%2) ? "#F7F5F0": '#F1F4FA';

            $sqlD = "SELECT DISTINCT
                            tbl_defeito_constatado.codigo,
                            tbl_defeito_constatado.descricao
                    FROM    tbl_os_defeito_reclamado_constatado
                    JOIN    tbl_defeito_constatado USING(defeito_constatado)
                    WHERE   os = $auditoria_os";
            $resD = pg_query($con,$sqlD);
            $array_integridade = array();

            for ($j=0;$j<pg_num_rows($resD);$j++){
                $aux_defeito_constatado = pg_fetch_result($resD,$j,0).'-'.pg_fetch_result($resD,$j,1);
                array_push($array_integridade,$aux_defeito_constatado);
            }

            $lista_defeitos = implode($array_integridade,", ");


            if($login_fabrica == 30){
                $ultima_interacao = ultima_interacao($auditoria_os);
                switch ($ultima_interacao) {
                    case "fabrica":
                        $cor = "#FFDC4C";
                        break;

                    case "posto":
                        $cor = "#A6D941";
                        break;

                    default:
                        $cor = "#FFFFFF";
                        break;
                }
            }


?>
                <tr id='linha_<?=$i?>' style='font-size: 9px; font-family: verdana; background-color:<?=$cor?>'>
                    <td class="tac"><a href='os_press.php?os=<?=$auditoria_os?>' target='_blank'><?=$auditoria_sua_os?></a></td>
                    <td class="tac"><?=$auditoria_data_abertura_os?></td>
                    <td class="tac"><?=$auditoria_data_laudo?></td>
                    <td class="tac"><?=$auditoria_dias_decorrentes?></td>
                    <td class="tac"><acronym title="Posto: <?=$auditoria_codigo_posto." - ".$auditoria_nome_posto?>" style="cursor:help;"><?=substr($auditoria_codigo_posto." - ".$auditoria_nome_posto,0,15)?></acronym></td>
                    <td class="tac"><acronym title="Produto: <?=$auditoria_produto_referencia." - ".$auditoria_produto_descricao?>" style="cursor:help;"><?=substr($auditoria_produto_referencia." - ".$auditoria_produto_descricao,0,15)?></acronym></td>
                    <td class="tac"><?=$lista_defeitos?></td>
                    <td class="tac"><?=$auditoria_status_laudo?></td>
<?
            if($auditoria_status_laudo == "Em Aprovação"){
?>
                    <td><a href="cadastro_laudo_troca.php?alterar=<?=$auditoria_os?>&admin=sim&laudo=<?=$auditoria_laudo?>" target="_blank">Auditar </a></td>
<?
            }
?>
                    <td><a href="cadastro_laudo_troca.php?imprimir=<?=$auditoria_os?>&admin=sim&laudo=<?=$auditoria_laudo?>" target="_blank">Visualizar </a></td>
                    <td class="tac">
                    <?php
						$tipo_laudo = "";
                        switch ($auditoria_laudo) {
                            case 'far':
                                $tipo_laudo = "FAR";
                                break;
                            case 'fat':
                                $tipo_laudo = "FAT";
                                break;
                            case 'fats':
                                $tipo_laudo = "FAT SINISTRO";
                                break;
                            case 'fatrev':
                                $tipo_laudo = "FAT REVENDA";
                                break;
                        }
                        echo $tipo_laudo;
                    ?>
                    </td>
                    <td class="tac"><?=$auditoria_responsavel?></td>
                    <?php
                    if($login_fabrica == 30){
                        $id_posto =  pg_fetch_result($res,$i,id_posto);
                        $aux_sql  = "SELECT cobranca_estado, contato_estado FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $id_posto";
                        $aux_res  = pg_query($con, $aux_sql);
                        $uf_posto = pg_fetch_result($aux_res, 0, 'contato_estado');

                        if (empty($uf_posto)) {
                            $uf_posto = pg_fetch_result($aux_res, 0, 'cobranca_estado');                       
                        }

                        $aux_sql  = "select * from tbl_laudo_tecnico_os where os = $auditoria_os";
                        $aux_res  = pg_query($con, $aux_sql);
                        $aux_obs  = (array) json_decode(pg_fetch_result($aux_res, 0, 'observacao'));
                        $rs_posto = (array) $aux_obs["negociacao"];
                        $rs_posto = $rs_posto["valor"];
                    ?>
                        <td> <?=$consumidor_estado;?> </td>
                        <td> <?=$rs_posto;?> </td>
                        <td><button type="button" data-os="<?=$auditoria_sua_os?>" class="btn-interagir btn btn-primary">Interagir</button></td>
                        <?php
                    }
                    ?>
                </tr>
<?
            $excel .= "
                <tr>
                    <td>$auditoria_os</td>
                    <td>$auditoria_data_abertura_os</td>
                    <td>$auditoria_data_laudo</td>
                    <td>$auditoria_dias_decorrentes</td>
                    <td>Posto: $auditoria_codigo_posto - $auditoria_nome_posto</td>
                    <td>Produto: $auditoria_produto_referencia - $auditoria_produto_descricao</td>
                    <td>$lista_defeitos</td>
                    <td>$auditoria_status_laudo</td>
                    <td>$tipo_laudo</td>
                    <td>$auditoria_responsavel</td>
            ";

            if ($login_fabrica == 30) {
                $excel .= "
                    <td>$uf_posto</td>
                    <td>R$ $rs_posto</td>
                ";
            }

            $excel .="
                </tr>
            ";
        }
        $excel .= "
            </tbody>
        </table>
        ";
?>

            </tbody>
            <tfoot>
                <input type='hidden' name='qtde_os' value='<?=$i?>'>
                <input type='hidden' name='btn_acao' value='Pesquisar'>
                <tr style='background-color:#485989;color:white;font-weight:bold;text-align:left'>
                    <td colspan = "16">&nbsp;</td>
                </tr>
            </tfoot>
        </table>
    </form>
<?
        $data_xls = date("Y-m-d_H-i-s");
        $arquivo_nome = "relatorio-laudo-os-troca-$login_fabrica-$data_xls.xls";
        $arquivo_nome_tmp = "relatorio-laudo-os-troca-$login_fabrica-tmp.xls";

        $path       = "xls/";
        $path_tmp   = "/tmp/";

        $arquivo_completo     = $path.$arquivo_nome;
        $arquivo_completo_tmp = $path_tmp.$arquivo_nome_tmp;

        echo `rm $arquivo_completo_tmp`;
        $fp = fopen ($arquivo_completo_tmp,"w");
        fputs ($fp,$excel);
        fclose ($fp);

        if(file_exists($arquivo_completo_tmp)){
            echo `cp $arquivo_completo_tmp $arquivo_completo `;
            echo `rm $arquivo_completo_tmp `;
?>
    <p align="center">
        <img src='imagens/excell.gif'>&nbsp;<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='<?=$arquivo_completo?>'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font>
    </p>
<?
        }
    }else{
?>
        <center><p>Não foi encontrada OS de Troca.</p></center><br>
<?
    }
}

include 'rodape.php';
?>

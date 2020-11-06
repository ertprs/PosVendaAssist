<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="auditoria";
include "autentica_admin.php";
include 'funcoes.php';

if($_GET['historico']){
    $posto = $_GET['posto'];
    $bloqueado = $_GET['bloqueado'];

    if($login_fabrica <> 24){
	$cond_pedido_faturado = ($login_fabrica == 168) ? " AND pedido_faturado is true " : " AND pedido_faturado is false ";
        $sql = "SELECT tbl_posto_bloqueio.observacao,
                       tbl_admin.login,
                       tbl_posto_bloqueio.desbloqueio,
                       to_char(tbl_posto_bloqueio.data_input,'DD/MM/YYYY HH24:MI:SS') AS data_hora,
                       tbl_posto_bloqueio.resolvido
                FROM tbl_posto_bloqueio
                LEFT JOIN tbl_admin ON tbl_posto_bloqueio.admin = tbl_admin.admin
                WHERE tbl_posto_bloqueio.fabrica = $login_fabrica
                AND tbl_posto_bloqueio.posto = $posto
                $cond_pedido_faturado
                ORDER BY tbl_posto_bloqueio.data_input DESC";
        $res = pg_query($con,$sql);

        if(pg_num_rows($res) > 0 ){
            $resultado = "<table width='100%' class='tabela'>
                            <tr style='background-color:#596d9b ; color:#FFFFFF;'>
                                <th>Motivo</th>
                                <th>Admin</th>
                                <th>Data</th>
                                <th>A&ccedil;&atilde;o</th>
                            </tr>";
            for($i = 0; $i < pg_num_rows($res); $i++){

                $obs            = pg_fetch_result($res, $i, 'observacao');
                $login          = pg_fetch_result($res, $i, 'login');
                $desbloqueio    = pg_fetch_result($res, $i, 'desbloqueio');
                $data_hora      = pg_fetch_result($res, $i, 'data_hora');
                $resolvido      = pg_fetch_result($res, $i, 'resolvido');

                $situacao = ($desbloqueio == "t") ? "<font color='#216B2C'>Desbloqueado</font>" : "<font color='#FF0000'>Bloqueado</font>";
                $situacao = ($desbloqueio == "t" AND !empty($resolvido)) ? "<font color='#0000FF'>Resolvido</font>" : $situacao;

                if($bloqueado == 1) {
                    if(empty($j)) {
                        if(strpos($situacao,'Bloqueado') !== false) {
                            $j = 1;
                        }else{
                            $situacao = " ";
                            $situacao = (strlen(trim($situacao)) == 0 and $desbloqueio == "t") ? "<font color='#216B2C'>Desbloqueado</font>" : "<font color='#FF0000'>Bloqueado</font>";
                        }

                    }
                }

                $resultado .= " <tr>
                                    <td>$obs</td>
                                    <td>$login</td>
                                    <td>$data_hora</td>
                                    <td>$situacao</td>
                                </tr>";

            }
            $resultado .= "</table>";

            echo $resultado;
        }
    }else{
        $sql = "SELECT tbl_os.consumidor_nome,
                            to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
                            tbl_os.os,
                            tbl_os_campo_extra.os_bloqueada
                        FROM tbl_os
                        JOIN tbl_os_campo_extra ON tbl_os.os = tbl_os_campo_extra.os AND tbl_os_campo_extra.fabrica = $login_fabrica
                        WHERE tbl_os.fabrica = $login_fabrica
                        AND (tbl_os_campo_extra.os_bloqueada IS true OR tbl_os_campo_extra.os_bloqueada IS false)
                        AND tbl_os.excluida IS NOT true
                        AND tbl_os.cancelada IS NOT TRUE
                        AND tbl_os.finalizada IS NULL
                        AND tbl_os.posto = $posto";
        $res = pg_query($con, $sql);
        if(pg_num_rows($res) > 0 ){
            $resultado = "<table width='100%' class='tabela'>
                            <tr style='background-color:#596d9b ; color:#FFFFFF;'>
                                <th colspan='4' >OSs Bloqueadas</th>
                            </tr>
                            <tr style='background-color:#596d9b ; color:#FFFFFF;'>
                                <th>Numero OS</th>
                                <th>Data Abertura</th>
                                <th>Nome Consumidor</th>
                                <th>Ação</th>
                            </tr>";
            for($i = 0; $i < pg_num_rows($res); $i++){

                $consumidor_nome                    = pg_fetch_result($res, $i, 'consumidor_nome');
                $data_abertura              = pg_fetch_result($res, $i, 'data_abertura');
                $os     = pg_fetch_result($res, $i, 'os');
                $os_bloqueada = pg_fetch_result($res, $i, 'os_bloqueada');

                if($os_bloqueada == 't'){
                    $value_botao = "Desbloquear";
                }else{
                    $value_botao = "Bloquear";
                }

                $resultado .= " <tr>
                                    <td><a href='os_press.php?os=$os' target='_blank' >$os</a></td>
                                    <td>$data_abertura</td>
                                    <td>$consumidor_nome</td>
                                    <td style='text-align: center;'>
                                        <input type='button' value='".$value_botao."' rel='$os' onclick='javascript:bloq_desbloq(\"$os\",\"$posto\");'>
                                    </td>
                                </tr>";

            }
            $resultado .= "</table>";

            echo $resultado;
        }
    }
    exit;
}


if($login_fabrica == 24){
    if($_GET['bloq_desbloq']){
        $os = $_GET['os'];
        $posto = $_GET['posto'];

        $select = "SELECT os_bloqueada, campos_adicionais FROM tbl_os_campo_extra WHERE os = $os AND fabrica = $login_fabrica";
        $res_select = pg_query($con, $select);
        $os_bloq = pg_fetch_result($res_select, 0, 'os_bloqueada');
        $log_bloq_desbloq = pg_fetch_result($res_select, 0, 'campos_adicionais');

        if($os_bloq == 't'){
            $desbloq = "f";
            $statusOS = "OS Desbloqueada";
        }else{
            $desbloq = "t";
            $statusOS = "OS Bloqueada";
        }

        $date = date('d/m/Y H:i');

        $responsalvel = "$date - $statusOS - Admin: $login_admin-$login_login <br /> $log_bloq_desbloq";

        $sql_update = "UPDATE tbl_os_campo_extra set os_bloqueada = '$desbloq', campos_adicionais = '$responsalvel'  where os = $os and fabrica = $login_fabrica";
        $res_update = pg_query($con, $sql_update);



        $sql = "SELECT tbl_os.os
                            FROM tbl_os
                            JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os AND tbl_os_campo_extra.fabrica = $login_fabrica
                            WHERE tbl_os_campo_extra.os_bloqueada IS true
                            AND tbl_os.excluida IS NOT true
                            AND tbl_os.finalizada IS NULL
                            AND tbl_os.posto = $posto";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res) == 0){
            $sqlUpdate = "UPDATE tbl_posto_bloqueio set desbloqueio = 't' where fabrica = $login_fabrica and posto = $posto";
            $resUpdate = pg_query($con, $sqlUpdate);
        }else{
            $sqlUpdate = "UPDATE tbl_posto_bloqueio set desbloqueio = 'f' where fabrica = $login_fabrica and posto = $posto";
            $resUpdate = pg_query($con, $sqlUpdate);
        }

        if(!pg_last_error($con)){
            echo $desbloq;
        }else{
            echo pg_last_error($con);
        }
        exit;
    }
}


$btn_acao    = trim($_POST["btn_acao"]);

if(strlen($btn_acao) AND $btn_acao == "pesquisar"){

    $posto_codigo = $_POST['posto_codigo'];
    $posto_nome   = $_POST['posto_nome'];


    if(!empty($posto_codigo)){
        $sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$posto_codigo'";
        $res = pg_query($con,$sql);
        if(pg_num_rows($res) > 0){
            $posto = pg_fetch_result($res, 0, 'posto');
            $cond .= " AND tbl_posto.posto = $posto ";      }
    }
}

$btn_acao    = trim($_POST["btn_acao"]);

if(strlen($btn_acao) AND $btn_acao == "gravar"){

    $posto          = $_POST['posto_id'];
    $status         = $_POST['status'];
    $motivo         = $_POST['motivo'];

    if(empty($status)){
	$msg_erro = "Informe um ação que deseja";
    }

    if($login_fabrica == 168 and strlen($motivo) == 0){
	if(strlen($msg_erro) > 0){
		$msg_erro .= "<br>";
	}
	$msg_erro .= "Informe o Motivo para o Bloqueio ou Desbloqueio do Posto";
    }

    if(strlen($msg_erro) == 0){

        $res = pg_query('BEGIN');
        $cont = 0;
        //for($j = 0; $j < $qtde_linha; $j++){
            //$posto_desbloqueia = $_POST['check_'.$j];

            //if(!empty($posto_desbloqueia)){
                $cont++;
                if($status == 'desbloquear'){
                    $sql = "INSERT INTO tbl_posto_bloqueio(
                                            fabrica,
                                            posto,
                                            admin,
                                            desbloqueio,
                                            pedido_faturado,
                                            observacao)VALUES(
                                            $login_fabrica,
                                            $posto,
                                            $login_admin,
                                            true,
                                            true,
                                            '$motivo')";

                }else{
                    $sql = "INSERT INTO tbl_posto_bloqueio(
                                            fabrica,
                                            posto,
                                            admin,
                                            pedido_faturado,
                                            observacao)VALUES(
                                            $login_fabrica,
                                            $posto,
                                            $login_admin,
                                            true,
                                            '$motivo')";
                
                }
                $res = pg_query($con,$sql);

                if(pg_last_error($con)){
                    $msg_erro .= pg_last_error($con)."<br>";
                }
            //}
        //}

        if($cont == 0){
            $msg_erro = "Selecione um Posto para o desbloqueio";
        }

        if(empty($msg_erro)){
            $res = pg_query('COMMIT');
            $msg = "Gravado com sucesso";
        }else{
            $res = pg_query('ROLLBACK');
        }

    }
}



if(strlen($btn_acao2) AND $btn_acao2 == "gravar"){
    $posto          = $_POST['posto_id'];
    $status         = $_POST['select_acao'];
    $motivo         = $_POST['motivo'];
    $qtde_linha     = $_POST['qtde_linha'];

    if(!empty($status)){

        $res = pg_query('BEGIN');
        $cont = 0;
        for($j = 0; $j < $qtde_linha; $j++){
            $posto_desbloqueia = $_POST['check_'.$j];

            if(!empty($posto_desbloqueia)){
                $cont++;
                if($status == 'desbloquear'){
                    $sql = "INSERT INTO tbl_posto_bloqueio(
                                            fabrica,
                                            posto,
                                            admin,
                                            desbloqueio,
                                            pedido_faturado,
                                            observacao)VALUES(
                                            $login_fabrica,
                                            $posto_desbloqueia,
                                            $login_admin,
                                            true,
                                            true,
                                            '$motivo')";

                }else{
                    $sql = "INSERT INTO tbl_posto_bloqueio(
                                            fabrica,
                                            posto,
                                            admin,
                                            pedido_faturado,
                                            observacao)VALUES(
                                            $login_fabrica,
                                            $posto_desbloqueia,
                                            $login_admin,
                                            true,
                                            '$motivo')";
                
                }
                $res = pg_query($con,$sql);

                if(pg_last_error($con)){
                    $msg_erro .= pg_last_error($con)."<br>";
                }
            }
        }

        if($cont == 0){
            $msg_erro = "Selecione um Posto para o desbloqueio";
        }

        if(empty($msg_erro)){
            $res = pg_query('COMMIT');
            $msg = "Gravado com sucesso";
        }else{
            $res = pg_query('ROLLBACK');
        }

    }else{
        $msg_erro = "Informe um ação que deseja";
    }
}

$layout_menu = 'auditoria';
$title = strtoupper('Desbloqueio de Postos Autorizados');

include 'cabecalho.php';

?>

<style type="text/css">

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

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}


.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

</style>

<script src="js/jquery-1.3.2.js"    type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<script language="JavaScript">

$(document).ready(function(){
    Shadowbox.init();
});

var ok = false;
var cont=0;
function checkaTodos() {
    f = document.frm_pesquisa2;
    if (!ok) {

        for (i=0; i<f.length; i++){
            if (f.elements[i].type == "checkbox"){
                f.elements[i].checked = true;
                ok=true;

                cont++;
            }
        }
    }else{
        for (i=0; i<f.length; i++) {
            if (f.elements[i].type == "checkbox"){
                f.elements[i].checked = false;
                ok=false;

                cont++;
            }
        }
    }
}

function pesquisaPosto(campo,tipo){
    var campo = campo.value;

    if (jQuery.trim(campo).length > 2){
        Shadowbox.open({
            content:    "posto_pesquisa_nv.php?"+tipo+"="+campo+"&tipo="+tipo,
            player:     "iframe",
            title:      "Pesquisa Posto",
            width:      800,
            height:     500
        });
    }else
        alert("Informar toda ou parte da informação para realizar a pesquisa!");
}

function retorna_posto(posto,codigo_posto,nome,cnpj,pais,cidade,estado,nome_fantasia){
    gravaDados('posto_nome',nome);
    gravaDados('posto_codigo',codigo_posto);
    gravaDados('posto_id',posto);
}

function gravaDados(name, valor){
        try {
                $("input[name="+name+"]").val(valor);
        } catch(err){
                return false;
        }
}

function enviaDados(){
    var acao = $("select[name=select_acao]").val();
    var btn_acao2 = $("input[name=btn_acao2]").val();
    var motivo = $("input[name=motivo]").val();

    if(motivo == ''){
        $("#erro").html("Informe o motivo").show('slow');
        return false;
    }

    if ( btn_acao2 == '' ) {
        btn_acao2 = $("input[name=btn_acao2]").val('gravar');
            document.frm_pesquisa2.submit() ;
    } else {
        alert ('Aguarde submissão da OS...'); }
}

function exibeHistorico(posto,bloqueado){

    if($("#"+posto).is(":visible")){
        $("#"+posto).hide();
    }else{
        $.ajax({
                url: "<?php echo $_SERVER['PHP_SELF']; ?>?historico=1&posto="+posto+"&bloqueado="+bloqueado,
                cache: false,
                success: function(data) {

                    $("#"+posto).html('<td colspan="100%">'+data+'</td>');
                    $("#"+posto).attr('style','display:table-cel');

                }

            });
    }
}

function resolver(posto){

    $.ajax({
        url: "<?php echo $_SERVER['PHP_SELF']; ?>?resolver=1&posto="+posto,
        cache: false,
        success: function(data) {
            if(data == "ok"){
                $("input[rel="+posto+"]").hide();
                $(".td_"+posto) .html("Resolvido");
            }else{
                alert(data);
            }
        }

    });
}

function resolver_suggar(posto,status){

    $.ajax({
        url: "<?php echo $_SERVER['PHP_SELF']; ?>?resolver=1&posto="+posto,
        cache: false,
        success: function(data) {

            if(data == "f"){
                $("input[rel="+posto+"]").val('Desbloquear');
                $("#prev_"+posto).html('<font color="#FF0000">Bloqueado.</font>');
            }else{
                $("input[rel="+posto+"]").val('Bloquear');
                $("#prev_"+posto).html('<font color="#216B2C"><strong>Desbloqueado Admin.</strong></font>');
            }
        }

    });
}

function bloq_desbloq(os, posto){

    $.ajax({
        url: "<?php echo $_SERVER['PHP_SELF']; ?>?bloq_desbloq=1&os="+os+"&posto="+posto,
        cache: false,
        success: function(data) {
            if(data == "t"){
                $("input[rel="+os+"]").val('Desbloquear');
            }else{
                $("input[rel="+os+"]").val('Bloquear');
            }
        }
    });
}

</script>

<?


echo "<div class='msg_erro' id='erro' style='display:none;width:700px;margin:auto;'></div>";


if(strlen($msg) > 0){
    echo "<div class='sucesso' style='width:700px;margin:auto;'>$msg</div>";
}

if(strlen($msg_erro) > 0){
	 echo "<div class='msg_erro' style='width:700px;margin:auto;'>$msg_erro</div>";
}
?>

<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">

<TABLE width="700" align="center" border="0" cellspacing='1' cellpadding='0' class='formulario espaco'>

<TBODY>
<tr class="titulo_tabela"><th colspan="3">Parâmetros de Pesquisa</th></tr>
<tr><td colspan='3'>&nbsp;</td></tr>
<TR>
    <td width='150'>&nbsp;</td>
    <TD>
        Código Posto<br />
        <input type='text' name='posto_codigo' size='12' value='<?=$posto_codigo?>' class='frm' />&nbsp;
        <img src='../imagens/lupa.png' border='0' align='absmiddle' style='cursor: pointer;' onclick="javascript: pesquisaPosto (document.frm_pesquisa.posto_codigo, 'codigo'); " />
    </TD>
    <TD>
        Nome Posto<br />
        <input type='text' name='posto_nome' size='30' value='<?=$posto_nome?>' class='frm' />&nbsp;
        <input type='hidden' name='posto_id' size='30' value='<?=$posto_id?>' class='frm' />&nbsp;
        <img src='../imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: pesquisaPosto (document.frm_pesquisa.posto_nome, 'nome'); " style='cursor: pointer;' />
    </TD>
</TR>
<tr>
    <td width='150'>&nbsp;</td>
    <td>
        Status <Br>
        <select name="status" class='frm'>
            <option value=""></option>
            <option value="desbloquear">Desbloquear</option>
            <option value="bloquear">Bloquear</option>
        </select>
    </td>
    <td>
        Motivo<br>
        <input type="text" name="motivo"  size='35' value="<?=$motivo?>"  class='frm'>
    </td>
</tr>

<!-- <tr><td colspan='3'>&nbsp;</td></tr>

<tr>
        <td colspan='3' align='center'>
            <fieldset style='width:200px;'>
                <legend>Status</legend>
                <input type='radio' name='status' value='bloqueado' checked>Bloqueado
                <input type='radio' name='status' value='desbloqueado' <? echo ($status == 'desbloqueado') ? 'checked' : ''; ?> >Desbloqueado
            </fieldset>

        </td>
</tr> -->

</tbody>
<tr><td colspan='3'>&nbsp;</td></tr>
<TR>
    <TD colspan="3" style="padding-left:0px;" align="center">
        <input type='hidden' name='btn_acao' value=''>
        <input type="button" onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='gravar'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer;" value="Gravar" />
    </TD>
</TR>
<tr><td colspan='3'>&nbsp;</td></tr>
</table>
</form>


<?php


$sql = "select nome, codigo_posto, posto
        INTO temp tmp_posto_$login_admin
        FROM tbl_posto
        join tbl_posto_fabrica using(posto)
        $join_suggar
        WHERE fabrica = $login_fabrica
        AND tbl_posto_fabrica.credenciamento != 'DESCREDENCIADO'
        $cond;

        create index tmp_posto_posto_$login_admin on tmp_posto_$login_admin(posto);

        SELECT DISTINCT codigo_posto,
                        posto,
                        nome,
                        (SELECT desbloqueio FROM tbl_posto_bloqueio B WHERE B.posto  = tmp_posto_$login_admin.posto AND B.fabrica = $login_fabrica $bloqueio_black  ORDER BY B.data_input DESC LIMIT 1) AS status,
                        (SELECT desbloqueio FROM tbl_posto_bloqueio B WHERE B.posto  = tmp_posto_$login_admin.posto AND B.fabrica = $login_fabrica $bloqueio_black  and observacao !~*'extrato' ORDER BY B.data_input DESC LIMIT 1) AS status2,
                        (SELECT desbloqueio FROM tbl_posto_bloqueio B WHERE B.posto  = tmp_posto_$login_admin.posto AND B.fabrica = $login_fabrica $bloqueio_black and observacao ~* 'extrato' ORDER BY B.data_input DESC LIMIT 1) AS status3,
                        (SELECT admin FROM tbl_posto_bloqueio B WHERE B.posto  = tmp_posto_$login_admin.posto AND B.fabrica = $login_fabrica $bloqueio_black  ORDER BY B.data_input DESC LIMIT 1) AS admin_desbloqueio,
                    (SELECT resolvido FROM tbl_posto_bloqueio C WHERE C.posto  = tmp_posto_$login_admin.posto AND C.fabrica = $login_fabrica $bloqueio_black ORDER BY C.data_input DESC LIMIT 1) AS resolvido,
                    (SELECT admin FROM tbl_posto_bloqueio D WHERE D.posto  = tmp_posto_$login_admin.posto AND D.fabrica = $login_fabrica $bloqueio_black ORDER BY D.data_input DESC LIMIT 1) AS admin
                    into temp tmp_posto_bloqueio_$login_admin
                    FROM tmp_posto_$login_admin;

        DELETE FROM tmp_posto_bloqueio_$login_admin WHERE posto not in (select posto FROM tbl_posto_bloqueio where fabrica = $login_fabrica and pedido_faturado is not false) ;

        SELECT codigo_posto, posto, nome, resolvido, admin , 
            case when admin_desbloqueio notnull and status then status
                when status3 <> status2 and status3 then status2 
                when status3 <> status2 and status2 then status3
                else    status end as status
            FROM tmp_posto_bloqueio_$login_admin
            where 1=1
            $cond_suggar
            order by status desc ;
                    ";
$res = pg_query($con,$sql);

if(pg_num_rows($res) > 0){
    $qtde_linha = pg_num_rows($res);
    echo "<br /> <FORM name='frm_pesquisa2' METHOD='POST' ACTION='$PHP_SELF'>
            <input type='hidden' name='btn_acao2' value=''>
            <input type='hidden' name='qtde_linha' value='$qtde_linha'>
            <table align='center' width='700' class='tabela'>";
    echo "<tr class='titulo_coluna'>";
    if($login_fabrica <> 168){
	echo "<th><img border='0' src='imagens_admin/selecione_todas.gif' onclick='javascript: checkaTodos()' alt='Selecionar todos' style='cursor: pointer;' align='center'></th>";
    }else{
	echo "<th></th>";
    }
     echo " <th nowrap>Código Posto</th>
            <th>Nome Posto</th>
            <th>Status</th>
         </tr>";

    for($i = 0; $i < pg_num_rows($res); $i++){

        $posto      = pg_fetch_result($res, $i, 'posto');
        $codigo     = pg_fetch_result($res, $i, 'codigo_posto');
        $nome       = pg_fetch_result($res, $i, 'nome');
        $status     = pg_fetch_result($res, $i, 'status');
        $resolvido  = pg_fetch_result($res, $i, 'resolvido');
        $admin  = pg_fetch_result($res, $i, 'admin');

        $cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";
        $desbloqueado = "";
        if(strlen($admin) > 0){
            $desbloqueado = "<strong>Desbloqueado Admin.</strong>";
        }else{
            $desbloqueado = "Desbloqueado Automático";
        }
        $bloqueado = $status == 't' ? '0': '1';
        echo "<tr bgcolor='$cor'>
                <td><input type='checkbox' name='check_{$i}' value='$posto'> </td><td>$codigo</td>
                <td align='left' nowrap><a href='javascript:void(0);' onclick='javascript: exibeHistorico($posto,$bloqueado)'>$nome</a></td>";

                $situacao = ($status == "t") ? "<font color='#216B2C'>$desbloqueado</font>" : "<font color='#FF0000'>Bloqueado</font>";
                $situacao = ($status == "t" AND !empty($resolvido)) ? "<font color='#0000FF'>Resolvido</font>" : $situacao;

            echo "<td align='left' nowrap id='prev_$posto'>$situacao</td>";

            
            echo "</tr>";
            echo "<tr id='{$posto}' style='display:none'><td></td></tr>";
    }
    echo "<tr class='titulo_tabela'><td colspan='4' align='left'>";
    echo "AÇÃO :
            <select name='select_acao' class='frm'>";

        echo "<option value='desbloquear'>DESBLOQUEAR</option>";
        if($login_fabrica <> 24){
            echo "<option value='bloquear'>BLOQUEAR</option>";
        }

        echo "</select>";
        echo "MOTIVO: <input type='text' name='motivo' size='25' class='frm'>";

    echo "<input type=button onclick='javascript: enviaDados();' name='btn_acao2' style='cursor:pointer;' value='Gravar' />";
    echo "</td></tr>";
    echo "</table>";

    echo "</form>";
}else{
    echo "<center>Nenhum resultado encontrado</center>";
}

include "rodape.php" ?>

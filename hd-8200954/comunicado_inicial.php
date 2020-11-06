<?php

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_usuario.php';
include_once 'token_cookie.php';

include_once 'fn_logoResize.php';
include_once "fn_traducao.php";
include_once "funcoes.php";
include_once 'regras/menu_posto/menu.helper.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?=$page_title?></title>
    <link href="imagens/tc_2009.ico" rel="shortcut icon">
    <link href='https://fonts.googleapis.com/css?family=Roboto:400,300,300italic,400italic,500italic,700,700italic,500' rel='stylesheet' type='text/css'>
    <link href="fmc/css/styles.css" rel="stylesheet" type="text/css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" type="text/css">
    <script src="https://code.jquery.com/jquery-1.9.1.min.js"></script>
    <script type="text/javascript" src="https://posvenda.telecontrol.com.br/assist/admin/js/jquery.mask.js"></script>
    <style type="text/css">
        html, body {
            height: auto;
        }
        #dlg_motivo{
            position: fixed;
            background-color: #f2f2f2;
            padding: 8px 12px;
            border-right: 6px;
            box-shadow: 10px 10px 5px #888888;
        }
        #motivo_header {
            background-color: #373865;
            color: #fff;
            text-align: center;
            text-decoration: bold;
        }
        .main2 {
            height: initial;
        }
    </style>
</head>
<body class="comunicados bg-gray-lighter">
<?php
$comunicados = array();
if($login_fabrica == 52){
    if(strlen($cook_admin) > 0){
        header("Location: menu_inicial.php");
        exit;
    }
}

if ($_COOKIE['cook_comunicado_telecontrol'] == 'naoleu') {
    include_once('tc_comunicado.php');
}

$leio_depois = $_GET['leio_depois'];
$leio_wanke  = $_GET['wanke_click'];

if ($leio_depois=="1" and !isset($_COOKIE['leio_depois['.$login_fabrica_nome.']'])) {
    //  Se ainda não tem, criar a 'cookie' só nestas condições:
    if(in_array($login_fabrica, array(1,2,11,14,43,45,66,80,162,169,170))
            or ($login_fabrica ==  3 and strlen($bloqueia_leio_depois) == 0)
            or ($login_fabrica == 20 and $login_pais<>'BR')) { //HD-3401374
        setcookie("leio_depois[$login_fabrica]","1");
        if ($login_fabrica <> 91){
            header ("Location: ".$PHP_SELF);
            exit;
        }
    }
}

if ($leio_depois=="1" and isset($_COOKIE['leio_depois['.$login_fabrica_nome.']']) and $cook_admin and $login_fabrica == 91 and !$leio_wanke) {
    $leio_depois = "";
} else if ($leio_wanke and $login_fabrica == 91 and $cook_admin) { ?>
    <script language='javascript'>
        window.location = 'menu_inicial.php';
    </script>
<?
}

$cook_leio_depois = $_COOKIE['leio_depois'];
$leio_depois = trim($_COOKIE['leio_depois'][$login_fabrica]);
if (!in_array($login_fabrica, array(1,2,3,11,14,43,45,80,162,169,170))) {//HD-3401374
	if ($cook_leio_depois[$login_fabrica] <> "1") {
		$leio_depois = "";
	}
}

$comunicado_lido = $_GET['comunicado_lido'];

if (strlen ($comunicado_lido) > 0) {


    if ($login_fabrica == 175 AND $login_unico_tecnico_posto == 't'){

        if ($login_unico_tecnico_posto == 't') {

            $campo_login_unico = ", login_unico";
            $value_login_unico = ", $login_unico";
            $cond_login_unico  = "AND login_unico = {$login_unico}";

        } else {
            $cond_login_unico  = "AND login_unico IS NULL";
        }

    }

    $sql = "SELECT comunicado
            FROM tbl_comunicado_posto_blackedecker
            WHERE comunicado = $comunicado_lido
            AND   posto      = $login_posto
            {$cond_login_unico}";
    $res = pg_query ($con,$sql);
    
    if (pg_num_rows($res) == 0){
        $sql = "INSERT INTO tbl_comunicado_posto_blackedecker (comunicado, posto, data_confirmacao {$campo_login_unico}) VALUES ($comunicado_lido, $login_posto, CURRENT_TIMESTAMP {$value_login_unico})";
    }else{
        $sql = "UPDATE tbl_comunicado_posto_blackedecker SET
                       data_confirmacao = CURRENT_TIMESTAMP
                WHERE  comunicado = $comunicado_lido
                AND    posto      = $login_posto";
    }
    $res = pg_query ($con,$sql);

    //HD 204146: Fechamento automático de OS, grava o nome de quem leu comunicado do tipo F AUT
    if ($_GET["leitor"]) {
        $sql = "UPDATE tbl_comunicado_posto_blackedecker SET leitor='" . $_GET["leitor"] . "' WHERE comunicado=$comunicado_lido";
        $res = pg_query ($con,$sql);
    }

    //busca o nome do posto e tipo do posto
    $sql = "SELECT nome
            FROM tbl_posto
            WHERE tbl_posto.posto =  $login_posto  ;";

    $res = pg_query ($con,$sql);
    $nome_posto  = pg_fetch_result($res, 0, 'nome');
    //busca o nome do posto e o tipo do posto

    if ($telecontrol_distrib == "t") {
        $qComunicado = "
            SELECT
                parametros_adicionais->>'os_interacao' AS os_interacao
            FROM tbl_comunicado
            WHERE comunicado = {$comunicado_lido}
            AND parametros_adicionais->>'os_interacao' IS NOT NULL;
        ";
        $rComunicado = pg_query($con, $qComunicado);

        if (pg_num_rows($rComunicado) > 0) {
            $osInteracao = pg_fetch_result($rComunicado, 0, 'os_interacao');

            $qInteracao = "
                SELECT
                    os_interacao
                FROM tbl_os_interacao
                WHERE os_interacao = {$osInteracao}
                AND fabrica = {$login_fabrica};
            ";
            $rInteracao = pg_query($con, $qInteracao);

            if (pg_num_rows($rInteracao) > 0) {
                $qInteracaoLida = "
                    UPDATE tbl_os_interacao
                    SET confirmacao_leitura = CURRENT_TIMESTAMP
                    WHERE os_interacao = {$osInteracao}
                    AND fabrica = {$login_fabrica}
                ";
                $rInteracaoLida = pg_query($con, $qInteracaoLida);
            }
        }
    }

    //funçao envia e-mail
    $sql = "SELECT remetente_email, tbl_posto.nome , descricao
            FROM tbl_comunicado
            JOIN tbl_posto USING (posto)
            WHERE tbl_comunicado.comunicado = $comunicado_lido
            AND tbl_comunicado.posto IS NOT NULL
            AND tbl_comunicado.remetente_email IS NOT NULL";
    $res = pg_query($con,$sql);

    //quando é escolhido um unico posto será enviado o e-mail de confirmacao.
    #if (pg_num_rows($res) == 1 and $login_fabrica <>91) {
    if (pg_num_rows($res) == 1 and (!in_array($login_fabrica, array(91,169,170,175,176,177,186,191,194,200)))) {
        $remetente_email = pg_fetch_result($res, 0, 'remetente_email');
        $posto_nome      = pg_fetch_result($res, 0, 'nome');
        $descricao       = pg_fetch_result($res, 0, 'descricao');
        $assunto      = "Leitura de Comunicado";
        $corpo        = "O Posto $posto_nome leu o comunicado $descricao.";

        include_once 'class/communicator.class.php';
        $mail = new TcComm($externalId, $externalEmail);

        if ($mail->sendMail($remetente_email, stripslashes($assunto), $corpo)) {
        } else {
            $msg_erro = traduz("nao.foi.possivel.enviar.o.email.por.favor.entre.em.contato.com.a.telecontrol");
        }
    }

    if (in_array($login_fabrica, array(1)) && isset($_GET["download_contrato"])) {
        include "gera_contrato_posto.php";
        exit;
    }

    if (in_array($login_fabrica, [35])) {
        $sql = "SELECT tbl_posto.nome , descricao
            FROM tbl_comunicado
            JOIN tbl_posto USING (posto)
            WHERE tbl_comunicado.comunicado = $comunicado_lido
            AND tbl_comunicado.posto = $login_posto
            AND tbl_comunicado.tipo = 'Com. Contrato Posto'";
        $res = pg_query($con,$sql);

        if (pg_num_rows($res) == 1 and (!in_array($login_fabrica, array(91,169,170)))) {
            $posto_nome      = pg_fetch_result($res, 0, 'nome');
            $descricao       = pg_fetch_result($res, 0, 'descricao');
            $assunto      = "Leitura de Comunicado";
            $corpo        = "O Posto $posto_nome leu o comunicado $descricao.";

            include_once 'class/communicator.class.php';
            $mail = new TcComm($externalId, $externalEmail);

            if ($mail->sendMail($remetente_email, stripslashes($assunto), $corpo)) {
            } else {
                $msg_erro = traduz("nao.foi.possivel.enviar.o.email.por.favor.entre.em.contato.com.a.telecontrol");
            }
        }

    }

    if ($telecontrol_distrib) {

        $sqlComunicadoOs = "SELECT parametros_adicionais::jsonb->>'os' as os,
                                   mensagem
                            FROM tbl_comunicado
                            JOIN tbl_os ON tbl_os.os::text = parametros_adicionais::jsonb->>'os'
                            AND tbl_os.posto = {$login_posto}
                            AND tbl_os.fabrica = {$login_fabrica}
                            WHERE comunicado = {$comunicado_lido}
                            AND parametros_adicionais::jsonb->>'os' IS NOT NULL
                            AND tbl_os.finalizada IS NULL";
        $resComunicadoOs = pg_query($con, $sqlComunicadoOs);

        if (pg_num_rows($resComunicadoOs) > 0) {

            $os       = pg_fetch_result($resComunicadoOs, 0, 'os');
            $mensagem = pg_fetch_result($resComunicadoOs, 0, 'mensagem');

            $sqlInteracaoOs = "INSERT INTO tbl_os_interacao (os,fabrica,posto,comentario)
                               VALUES ({$os},{$login_fabrica},{$login_posto},'{$mensagem}')";
            $resInteracaoOs = pg_query($con, $sqlInteracaoOs);

        }

    }

    //HD 204146: Depois que leu, estou direcionando para não deixar o sistema vulnerável pelas variáveis GET
    header("location:$PHP_SELF");
    exit;
}

//funçao envia e-mail
$sql2 = "SELECT tbl_posto_fabrica.codigo_posto           ,
                tbl_posto_fabrica.tipo_posto             ,
                tbl_posto.estado                         ,
                tbl_posto_fabrica.pedido_em_garantia     ,
                tbl_posto_fabrica.pedido_faturado        ,
                tbl_posto_fabrica.digita_os              ,
                tbl_posto_fabrica.categoria              ,
                tbl_posto_fabrica.reembolso_peca_estoque
        FROM    tbl_posto
        LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
        WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
        AND     tbl_posto.posto   = $login_posto ";
$res2 = pg_query ($con,$sql2);

if (pg_num_rows ($res2) > 0) {
    $tipo_posto            = trim(pg_fetch_result($res2, 0, 'tipo_posto'));
    $desc_categoria_posto  = trim(pg_fetch_result($res2, 0, 'categoria'));
    $estado                = trim(pg_fetch_result($res2, 0, 'estado'));//adicionado por raphael
    $estado = strtoupper($estado);
    $pedido_em_garantia     = pg_fetch_result($res2, 0, 'pedido_em_garantia');
    $pedido_faturado        = pg_fetch_result($res2, 0, 'pedido_faturado');
    $digita_os              = pg_fetch_result($res2, 0, 'digita_os');
    $reembolso_peca_estoque = pg_fetch_result($res2, 0, 'reembolso_peca_estoque');
}

$ajax        = $_GET['ajax'];
$nao_antigos = $_GET['nao_antigos'];

if (empty($ajax) AND empty($nao_antigos)) { ?>

    <script language='javascript'>

        var exigir_motivo = "<?php echo ($login_fabrica != 1) ? 'ok' :''; ?>";
        var aguarde_sub = false;

        function excluirOS(os, sua_os) {
            $("#dlg_motivo").css("display", "block");
            if (exigir_motivo != "") {
                $('#dlg_motivo #motivo_os').text(sua_os).attr('alt',os);
                $('#dlg_motivo').show('fast');
            } else {

                if(aguarde_sub == true){
                    alert("Aguarde a Submissão");
                    return;
                }

                aguarde_sub = true;

                window.location='os_consulta_lite.php?excluir='+os;
            }
        };

        function validarCPF(inputCPF){
            var soma = 0;
            var resto;

            inputCPF = inputCPF.replace(".", "");
            inputCPF = inputCPF.replace("/", "");
            inputCPF = inputCPF.replace("-", "");
            inputCPF = inputCPF.replace(".", "");

            if(inputCPF == '00000000000') return false;
            for(i=1; i<=9; i++) soma = soma + parseInt(inputCPF.substring(i-1, i)) * (11 - i);
            resto = (soma * 10) % 11;

            if((resto == 10) || (resto == 11)) resto = 0;
            if(resto != parseInt(inputCPF.substring(9, 10))) return false;

            soma = 0;
            for(i = 1; i <= 10; i++) soma = soma + parseInt(inputCPF.substring(i-1, i))*(12-i);
            resto = (soma * 10) % 11;

            if((resto == 10) || (resto == 11)) resto = 0;
            if(resto != parseInt(inputCPF.substring(10, 11))) return false;
            return true;
        }

        $().ready(function() {

            <?php
            if (in_array($login_fabrica, [85])) {
            ?>

                $(".cpf_comunicado").mask('000.000.000/00');

                $(".btn-li-confirmo").click(function(e){

                    e.preventDefault();

                    let comunicado = $(this).data("comunicado");
                    let that       = $(this);

                    var nome_comunicado = $.trim($("input[name=nome_comunicado][data-comunicado="+comunicado+"]").val());
                    var cpf_comunicado  = $.trim($("input[name=cpf_comunicado][data-comunicado="+comunicado+"]").val());

                    $("#msg_erro_"+comunicado).hide("slow");

                    if (nome_comunicado == "" || cpf_comunicado == "") {

                        $("#msg_erro_"+comunicado).html("<strong>Nome e CPF obrigatórios!</strong>").show("slow");

                    } else if (!validarCPF(cpf_comunicado)) {

                        $("#msg_erro_"+comunicado).html("<strong>CPF informado inválido!</strong>").show("slow");

                    } else {

                        $.ajax({
                            url: "login.php",
                            type: "GET",
                            dataType: "json",
                            data: {
                                comunicado_lido: comunicado,
                                nome_comunicado: nome_comunicado,
                                cpf_comunicado: cpf_comunicado,
                                ajax_comunicado: true
                            },
                            beforeSend: function(){
                                $(that).hide("fast");
                                $(that).closest("span").append("<center><span style='position: relative;top: -10px;font-weight: bolder;'>Aguarde </span><img src='imagens/loading_indicator_big.gif' /></center>");
                            },
                            error: function(){
                                $(that).show("fast");
                                alert("Erro ao confirmar leitura");
                            },
                            success: function(retorno) {

                                if (retorno.success) {

                                    $("#div_comunicado_"+comunicado).remove();

                                    if ($(".div_comunicados_leitura").length == 0) {

                                        window.location = 'menu_inicial.php';

                                    }

                                }
                                
                            }
                        });

                    }

                });
            <?php
            }
            ?>

            $('#dlg_motivo #dlg_fechar,#dlg_motivo #dlg_btn_cancel').click(function () {
                $('#dlg_motivo input').val('');
                $('#dlg_motivo').hide('fast');
            });

            $('#dlg_motivo #dlg_btn_excluir').click(function () {
                var str_motivo = $.trim($('#dlg_motivo input').val());
                var os = $('#dlg_motivo #motivo_os').attr('alt');
                if (str_motivo != '') {

                    if(aguarde_sub == true){
                        alert("Aguarde a Submissão");
                        return;
                    }

                    aguarde_sub = true;

                    $.get('grava_obs_excluida.php',
                         {'motivo':str_motivo,'os':os},
                         function(resposta) {
                            if (resposta == 'ok') {
                                var os = $('#dlg_motivo #motivo_os').text();
                                $('#exclusao').show();
                                setTimeout(function(){
                                    $('#dlg_motivo').hide('fast');
                                    $('#dlg_motivo input').val('');
                                    $('#exclusao').hide(); //hd_chamado=2904468
                                },2000);
                                // window.location='<?=$PHP_SELF?>';
                                $('#conteudo_'+os).remove(); /* Alterado para remover a linha da OS, e não atualizar a tela de consulta */
                                aguarde_sub = false;//hd_chamado=2904468
                            } else {
                                $('#dlg_motivo').hide('fast');
                                aguarde_sub = false; //hd_chamado=2904468
                                alert(resposta);
                                return false;
                            }
                    });//END of GET
                } else {
                    alert('Digite um motivo ou cancele a exclusão.');
                }
            });

        });

        function trim(s) {
           return s.replace(/^\s+|\s+$/g, "");
        }

        function consertadoOS (os , botao, indice ) {
            var dt = new Date;
            url = "os_consulta_lite.php?consertado="+os+"&dt="+dt;
            // console.log(url);
            $.get(
                url,
                function(http) {
                    var results = http.split("|");
                    if (typeof (results[0]) != 'undefined') {
                        if ($.trim(results[0]) == 'ok') {
                            location.reload();
                        }else{
                            if(results[1]){
                                alert(results[1]);
                            }
                            alert('<? fecho("acao.nao.concluida.tente.novamente") ?>');
                        }
                    }else{
                        alert ('<? fecho("acao.nao.foi.concluida.com.sucesso") ?>');
                    }
            });
        }

        function select_acoes(acao, os, sua_os, consertado_i, i) {
            switch (acao) {
                case 'excluir':
                    if (confirm("Deseja realmente excluir a OS " + sua_os + "?") == true) {
                        excluirOS(os, sua_os);
                    }
                    break;
                case 'consertado':
                    if (confirm("Apenas clicar OK se tiver certeza que a data de conserto da OS "+ sua_os + " seja HOJE!") == true) {
                         consertadoOS (os,document.consertado_i, i) ;
                    }
                    break;
                default:
                    Alert("Erro ao executar a ação selecionada.");
            }
        }
    </script>

<? } #HD 669556

if (in_array($login_fabrica, array(1))) { ?>
    <script>

        function download_contrato_servico(contrato){

            var url = "login.php?comunicado_lido="+contrato;
            var url_download = "login.php?comunicado_lido="+contrato+"&download_contrato=true";

            window.open(url_download, "_blank");
            location.href = url;
        }

    </script>

<?php }

#####################   
#   COMUNICADOS     #
#####################
if (strlen($leio_depois) == 0) {

    if($login_fabrica == 1){        //HD 10983
        $sql_cond1=" tbl_comunicado.pedido_em_garantia IS null ";
        $sql_cond2=" AND tbl_comunicado.pedido_faturado IS null ";
        $sql_cond3=" AND tbl_comunicado.digita_os IS FALSE ";
        $sql_cond4=" AND tbl_comunicado.reembolso_peca_estoque IS FALSE ";

        $sql_cond5=" AND (tbl_comunicado.destinatario_especifico = '$login_categoria' or tbl_comunicado.destinatario_especifico = '') ";
        $sql_cond6 = " AND (tbl_comunicado.tipo_posto = '$tipo_posto' or tbl_comunicado.tipo_posto is null) ";
        if ($pedido_em_garantia == "t")     $sql_cond1 ="  tbl_comunicado.pedido_em_garantia IS NOT FALSE ";
        if ($pedido_faturado == "t")        $sql_cond2 ="  OR tbl_comunicado.pedido_faturado IS NOT FALSE ";
        if ($digita_os == "t")              $sql_cond3 ="  OR tbl_comunicado.digita_os IS TRUE ";
        if ($reembolso_peca_estoque == "t") $sql_cond4 ="  OR tbl_comunicado.reembolso_peca_estoque IS TRUE ";
        $sql_cond_total="AND ( $sql_cond1 $sql_cond2 $sql_cond3 $sql_cond4) ";
    }

    if (in_array($login_fabrica,array(1,3,52))) {
        $sql_cond_linha = "
                    AND (tbl_comunicado.linha IN
                            (
                                SELECT tbl_linha.linha
                                FROM tbl_posto_linha
                                JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha
                                WHERE fabrica =$login_fabrica
                                    AND posto = $login_posto
                            )
                            OR tbl_comunicado.linha IS NULL
                        )";
    } else {

        if(in_array($login_fabrica,array(148, 161, 152, 180, 181, 182))) {

                $condORLinhaFamilia = " OR tbl_comunicado.comunicado IN(
                                    SELECT tbl_comunicado_produto.comunicado
                                    FROM tbl_comunicado_produto
                                    JOIN tbl_posto_linha ON tbl_comunicado_produto.linha = tbl_posto_linha.linha
                                    JOIN tbl_linha ON tbl_posto_linha.linha = tbl_linha.linha
                                    WHERE tbl_linha.fabrica = {$login_fabrica}
                                    AND tbl_posto_linha.posto = {$login_posto}
									AND (tbl_comunicado_produto.pais='$login_pais' or tbl_comunicado_produto.pais isnull)
                                ) 

                                OR ( tbl_comunicado.comunicado IN(
                                        SELECT tbl_comunicado_produto.comunicado
                                        FROM tbl_comunicado_produto
                                        JOIN tbl_produto ON tbl_comunicado_produto.familia = tbl_produto.familia
                                        JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_produto.linha
                                        WHERE tbl_produto.fabrica_i = {$login_fabrica}
                                        AND tbl_posto_linha.posto = {$login_posto}
										AND (tbl_comunicado_produto.pais='$login_pais' or tbl_comunicado_produto.pais isnull)
                                    ))

                                OR ( tbl_comunicado.comunicado IN(
                                        SELECT tipo_posto 
                                        FROM tbl_posto_tipo_posto
                                        WHERE fabrica = {$login_fabrica} and posto = {$login_posto} and tipo_posto = {$tipo_posto}
                                    ))

                                OR (    tbl_comunicado.comunicado IN(
                                            SELECT tbl_comunicado_produto.comunicado
                                            FROM tbl_comunicado_produto
                                            WHERE tbl_comunicado_produto.pais = '{$login_pais}'
                                            AND tbl_comunicado_produto.produto IS NULL 
                                            AND tbl_comunicado_produto.linha IS NULL 
                                            AND tbl_comunicado_produto.familia IS NULL 
                                        )) ";
        }

        $sqlPostoLinha = "
                        AND (tbl_comunicado.linha IN
                                (
                                    SELECT tbl_linha.linha
                                    FROM tbl_posto_linha
                                    JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha
                                    WHERE fabrica =$login_fabrica
                                        AND posto = $login_posto
                                )
                                OR (
                                        tbl_comunicado.produto IS NULL AND
                                        tbl_comunicado.comunicado IN (
                                            SELECT tbl_comunicado_produto.comunicado
                                            FROM tbl_comunicado_produto
                                            JOIN tbl_produto ON tbl_comunicado_produto.produto = tbl_produto.produto
                                            JOIN tbl_posto_linha on tbl_posto_linha.linha = tbl_produto.linha
                                            WHERE fabrica_i =$login_fabrica AND
                                                  tbl_posto_linha.posto = $login_posto

                                        )

                                )
                                OR
                                    (
                                    tbl_comunicado.linha IS NULL AND
                                    tbl_comunicado.produto in
                                        (
                                            SELECT tbl_produto.produto
                                            FROM tbl_produto
                                            JOIN tbl_posto_linha ON tbl_produto.linha = tbl_posto_linha.linha
                                            WHERE fabrica_i = $login_fabrica AND
                                            posto = $login_posto
                                        )
                                    )

                                 OR (tbl_comunicado.linha IS NULL AND tbl_comunicado.produto IS NULL AND
                                        (tbl_comunicado.comunicado IN (
                                            SELECT tbl_comunicado_produto.comunicado
                                            FROM tbl_comunicado_produto
                                            JOIN tbl_produto ON tbl_comunicado_produto.produto = tbl_produto.produto
                                            JOIN tbl_posto_linha on tbl_posto_linha.linha = tbl_produto.linha
                                            WHERE fabrica_i =$login_fabrica AND
                                                  tbl_posto_linha.posto = $login_posto

                                            )

                                            $condORLinhaFamilia

                                            or tbl_comunicado_produto.comunicado isnull)
                                    )


                            )";

        if(in_array($login_fabrica,array(1,15,42,191))) $sqlPostoLinha = "";
    }

    if(!empty($login_unico) AND in_array($login_fabrica,array(156,162))){
        $cond = " AND    (tbl_comunicado.posto IS NULL OR (tbl_comunicado.posto = $login_posto AND tbl_comunicado.destinatario = $login_unico_tecnico) OR (tbl_comunicado.posto = $login_posto AND tbl_comunicado.destinatario IS NULL) ) ";
    }else{

        $cond = " AND    (tbl_comunicado.posto IS NULL OR tbl_comunicado.posto = $login_posto) ";
    }

    if( $login_fabrica == 1 )
    {
        $sql = "CREATE TEMP TABLE postos_recentes AS
            SELECT posto FROM tbl_credenciamento
            WHERE fabrica = $login_fabrica AND data > '2017-07-31' and posto = $login_posto ;insert into postos_recentes (posto) select 0 from postos_recentes where posto notnull;
        ";

        $res = pg_query( $con, $sql );

        $exclui_postos_recentes = " AND COALESCE( tbl_comunicado.posto, 0 ) "
            . "NOT IN (SELECT posto FROM postos_recentes) ";

    }

    if(in_array($login_fabrica, array(1, 152,180,181,182))) {
        if($login_fabrica == 1) {
            $cond_contrato = " or (tbl_comunicado.tipo='Contrato' $exclui_postos_recentes ))  ";
        }else{
            $cond_contrato = " or tbl_comunicado.tipo='Contrato') ";
        }
    }else{
        $cond_contrato = " or 1=2) ";
    }

    $sqlDataCredenciamento = "
        SELECT case when data < current_date - interval '90 days' then current_timestamp - interval '30 days' else data end as data
        FROM tbl_credenciamento
        WHERE posto = {$login_posto} AND fabrica = {$login_fabrica}
        AND status = 'CREDENCIADO'
        ORDER BY data DESC
        LIMIT 1
    ";
    $resDataCredenciamento = pg_query($con, $sqlDataCredenciamento);

    if (pg_num_rows($resDataCredenciamento) > 0) {
        $dataCredenciamento = pg_fetch_result($resDataCredenciamento, 0, "data");
    } else {
        $sqlDataCredenciamento = "
            SELECT case when data_input < CURRENT_DATE - interval '90 days' then CURRENT_TIMESTAMP - interval '30 days' else data_input end as data_inputFROM tbl_posto_fabrica WHERE posto = {$login_posto} AND fabrica = {$login_fabrica}
        ";
        $resDataCredenciamento = pg_query($con, $sqlDataCredenciamento);

        $dataCredenciamento = pg_fetch_result($resDataCredenciamento, 0, "data_input");
    }

    if ($login_fabrica == 175 AND $login_unico_tecnico_posto == 't'){
        $cond_tecnico = " AND tbl_comunicado_posto_blackedecker.login_unico = $login_unico ";
    }

    if ($login_fabrica == 177){
        $cond_estado = " AND (tbl_comunicado.parametros_adicionais->'estados' IS NULL OR tbl_comunicado.parametros_adicionais->'estados' ? '$estado') ";
    }else{
        $cond_estado = " AND (tbl_comunicado.estado = '$estado' OR tbl_comunicado.estado IS NULL) ";
    }

    $sql = "SELECT  DISTINCT
                    tbl_comunicado.comunicado   ,
                    tbl_comunicado.descricao    ,
                    tbl_comunicado.extensao     ,
                    tbl_comunicado.mensagem     ,
                    tbl_comunicado.video        ,
                    tbl_comunicado.link_externo ,
                    tbl_comunicado.tipo_posto   ,
                    tbl_comunicado.data         ,
                    tbl_comunicado.tipo         ,
                    TO_CHAR (tbl_comunicado.data, 'DD/MM/YYYY') AS exibedata
            FROM    tbl_comunicado
       LEFT JOIN    tbl_comunicado_posto_blackedecker   ON  tbl_comunicado.comunicado               = tbl_comunicado_posto_blackedecker.comunicado
                                                        AND tbl_comunicado_posto_blackedecker.posto = $login_posto $cond_tecnico
       LEFT JOIN    tbl_comunicado_produto              ON  tbl_comunicado.comunicado               = tbl_comunicado_produto.comunicado
            WHERE  tbl_comunicado.fabrica = $login_fabrica
            AND    tbl_comunicado.obrigatorio_site
            $cond
            $cond_estado ";
            if($login_fabrica == 148) {
                $t_posto = [];
                $sqlTipoPosto = "SELECT fabrica, posto, tipo_posto
                                    FROM tbl_posto_tipo_posto
                                    WHERE fabrica = {$login_fabrica} AND posto = {$login_posto}";

                //die(nl2br($sqlTipoPosto));                                  
                $resTipoPosto = pg_query($con, $sqlTipoPosto);
                for ($i = 0 ; $i < pg_num_rows($resTipoPosto); $i++) {
                    $t_posto[] = trim(pg_fetch_result($resTipoPosto, $i, 'tipo_posto'));
                }
                
                if(in_array(479, $t_posto)) {
                    $sql .= " AND (tbl_comunicado.tipo_posto = 479 or tbl_comunicado.tipo_posto is null) ";
                } else {
                    $sql .= " AND    (tbl_comunicado.tipo_posto = $tipo_posto    OR  tbl_comunicado.tipo_posto     IS NULL) ";
                } 
            } else {
                $sql .= " AND    (tbl_comunicado.tipo_posto = $tipo_posto    OR  tbl_comunicado.tipo_posto     IS NULL) ";
            }

            if($login_fabrica != 191){
                    $sql .= " --AND    ((tbl_comunicado.data >= CURRENT_DATE - INTERVAL '30 days' and tbl_comunicado.ativo)
                    AND ((tbl_comunicado.data >= '{$dataCredenciamento}' AND tbl_comunicado.ativo $cond_contrato)
                    AND    (tbl_comunicado.posto IS NULL OR tbl_comunicado.posto = $login_posto)
                ";
            }

    if($login_fabrica == 153 AND date('Y-m-d') <= '2016-11-30'){
        $sql .= "AND (tbl_comunicado_posto_blackedecker.data_confirmacao IS NULL OR(tbl_comunicado_posto_blackedecker.data_confirmacao IS      NOT NULL AND tbl_comunicado.comunicado = 2781986 AND tbl_comunicado_posto_blackedecker.data_confirmacao::date < CURRENT_DATE)) ";
    }else{
        $sql .= "AND tbl_comunicado_posto_blackedecker.data_confirmacao IS NULL ";
    }

    if ($login_fabrica == 175){
        if ($login_unico_tecnico_posto == 't'){
            $sql .= " AND tbl_comunicado.tecnico = 't' ";
        }else{
            $sql .= " AND (tbl_comunicado.tecnico = 'f' 
                      OR (tbl_comunicado.tecnico = 't' AND tbl_comunicado.descricao ILIKE '%Nova Pré-OS%'))";
        }
    }

    // HD 15687
    if($login_fabrica==11){
    $sql .=" AND tbl_comunicado.tipo !='LGR' ";
    }

    if($login_fabrica == 20){
        $sql .= " AND tbl_comunicado.pais = '$login_pais' ";
    }
    //HD 10983
    if($login_fabrica==1){
        $sql.=" $sql_cond_total ";
        $sql.=" $sql_cond5 ";
        $sql.=" $sql_cond6 ";
    }

    if(in_array($login_fabrica,array(1,3,52))){ // HD 31530
        $sql.=" $sql_cond_linha ";
    }else{
        $sql.=" $sqlPostoLinha ";
    }

    if($ajax =='sim' and $nao_antigos == 'true') {
        $sql.= " LIMIT 1";
    }   

    //die(nl2br($sql));
    $res = pg_query($con,$sql);

    if ( ($login_fabrica != 3 and pg_num_rows($res) > 0) or $login_fabrica == 3 ) {
        if($ajax =='sim' and $nao_antigos == 'true') { # HD 669556
            echo "<resposta>OK</resposta>";exit;
        }

        if($tipo_posto == null ){
        echo "tipo_posto";
        }
        #HD 15726
        echo "<script language='javascript'>
                function abrirAnexo(comunicado,confirma){
                    if (confirma != undefined) {
                        comunicado = comunicado.replace(/\D/g, '');
                        if (document.getElementById('comunicado_id_'+comunicado)){
                            document.getElementById('comunicado_id_'+comunicado).style.display='inline';
						}
						if(document.getElementById('com_anexo_'+comunicado)) {
							document.getElementById('com_anexo_'+comunicado).style.display='none';
						}
                    }
                }
        </script>";

#========================================inicio opcoes britania========================================#
if ($login_fabrica == 3) {
    $status = $_GET['status'];

    if (strlen($status)>0) {

        $titulo_comunicado = "OS com o status ";
        switch ($status) {

            case 'vermelho':

            $sqlMostra = "SELECT    DISTINCT os,
                                        sua_os,
                                        data_digitacao,
                                        to_char(data_abertura,'DD/MM/YYYY') as data_abertura,
                                        tbl_os.os_reincidente,
                                        tbl_os.serie,
                                        excluida,
                                        motivo_atraso,
                                        tipo_os_cortesia,
                                        tbl_os.consumidor_revenda,
                                        tbl_os.consumidor_nome,
                                        tbl_os.revenda_nome,
                                        impressa,
                                        tbl_os.nota_fiscal,
                                        tbl_os.nota_fiscal_saida,
                                        tbl_produto.referencia,
                                        tbl_produto.descricao,
                                        tbl_produto.voltagem,
                                        tipo_atendimento,
                                        tecnico_nome,
                                        tbl_os.admin,
                                        sua_os_offline,
                                        status_os,
                                        rg_produto,
                                        tbl_produto.linha,
                                        data_conserto,
                                        tbl_marca.marca,
                                        tbl_marca.nome as marca_nome,
                                        consumidor_email
                                        into TEMP tmp_mostra_vermelho_$login_posto
                            FROM tbl_os
                    JOIN tbl_os_extra USING(os)
                    JOIN tbl_produto USING(produto)
                    LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
                    WHERE tbl_os.defeito_constatado is null
                    AND   tbl_os.solucao_os is null
                    AND tbl_os.posto = $login_posto
                    AND tbl_os.fabrica = $login_fabrica
                    AND data_conserto IS NULL
                    AND tbl_os.finalizada is NULL
                    AND (tbl_os.excluida IS NULL OR tbl_os.excluida = 'f')";
                    $sqlMostra = "SELECT *FROM tmp_mostra_vermelho_$login_posto";
                    $titulo_comunicado .="\"Aguardando Análise\"";
            break;
            case 'amarelo':

            $sqlMostra = "SELECT    DISTINCT os into TEMP tmp_mostra_vermelho_$login_posto
                                    FROM tbl_os
                                    JOIN tbl_os_extra USING(os)
                                    JOIN tbl_produto USING(produto)
                                    LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
                                    WHERE tbl_os.defeito_constatado is null
                                    AND   tbl_os.solucao_os is null
                                    AND tbl_os.posto = $login_posto
                                    AND tbl_os.fabrica = $login_fabrica
                                    AND data_conserto IS NULL
                                    AND tbl_os.finalizada is NULL
                                    AND (tbl_os.excluida IS NULL OR tbl_os.excluida = 'f')";

                    $resTemp = pg_query($con,$sqlMostra);
                    //echo nl2br($sqlMostra) . ";<br><br>";

                $sqlMostra = "SELECT DISTINCT os,
                                        sua_os,
                                        data_digitacao,
                                        to_char(data_abertura,'DD/MM/YYYY') as data_abertura,
                                        tbl_os.os_reincidente,
                                        tbl_os.serie,
                                        excluida,
                                        motivo_atraso,
                                        tipo_os_cortesia,
                                        tbl_os.consumidor_revenda,
                                        tbl_os.consumidor_nome,
                                        tbl_os.revenda_nome,
                                        impressa,
                                        tbl_os.nota_fiscal,
                                        tbl_os.nota_fiscal_saida,
                                        tbl_produto.referencia,
                                        tbl_produto.descricao,
                                        tbl_produto.voltagem,
                                        tipo_atendimento,
                                        tecnico_nome,
                                        tbl_os.admin,
                                        sua_os_offline,
                                        status_os,
                                        rg_produto,
                                        tbl_produto.linha,
                                        data_conserto,
                                        tbl_marca.marca,
                                        tbl_marca.nome as marca_nome,
                                        consumidor_email
                    FROM tbl_os
                    JOIN tbl_os_extra USING(os)
                    JOIN tbl_os_produto using (os)
                    JOIN tbl_os_item USING (os_produto)
                    JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
                    LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
                    JOIN tbl_peca USING (peca)
                    LEFT JOIN tbl_defeito USING (defeito)
                    LEFT JOIN tbl_servico_realizado USING (servico_realizado)
                    LEFT JOIN tbl_os_item_nf ON tbl_os_item.os_item = tbl_os_item_nf.os_item
                    LEFT JOIN tbl_pedido ON tbl_os_item.pedido = tbl_pedido.pedido
                    LEFT JOIN tbl_pedido_item on tbl_pedido.pedido=tbl_pedido_item.pedido
                    LEFT JOIN tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
                    WHERE
                    tbl_os.posto = $login_posto
                    AND tbl_os.fabrica = $login_fabrica
                    AND tbl_os.finalizada is NULL
                    AND tbl_os.data_conserto IS NULL
                    AND (tbl_os.excluida IS NULL OR tbl_os.excluida = 'f')
                    AND tbl_os_item.peca not in (select peca from tbl_faturamento_item where tbl_faturamento_item.pedido = tbl_os_item.pedido)
                    AND os not in (SELECT os FROM tmp_mostra_vermelho_$login_posto)";
                    $titulo_comunicado .="\"Aguardando Peças\"";
                    //echo nl2br($sqlMostra) . "<br><br>";
            break;

            case 'rosa':

            $sqlMostra = "SELECT    DISTINCT os
                                        into TEMP tmp_mostra_vermelho_$login_posto
                                    FROM tbl_os
                                    JOIN tbl_os_extra USING(os)
                                    JOIN tbl_produto USING(produto)
                                    LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
                                    WHERE
                                    tbl_os.defeito_constatado is null
                                    AND tbl_os.solucao_os is null
                                    AND tbl_os.posto = $login_posto
                                    AND tbl_os.fabrica = $login_fabrica
                                    AND data_conserto IS NULL
                                    AND tbl_os.finalizada is NULL
                                    AND (tbl_os.excluida IS NULL OR tbl_os.excluida = 'f')";

                $resTemp = pg_query($con,$sqlMostra);
                //echo nl2br($sqlMostra);

                $sqlTemp = "SELECT DISTINCT os
                                        into TEMP tmp_os_amarelo_$login_posto
                                    FROM tbl_os
                                    JOIN tbl_os_extra USING(os)
                                    JOIN tbl_os_produto using (os)
                                    JOIN tbl_os_item USING (os_produto)
                                    JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
                                    LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
                                    JOIN tbl_peca USING (peca)
                                    LEFT JOIN tbl_defeito USING (defeito)
                                    LEFT JOIN tbl_servico_realizado USING (servico_realizado)
                                    LEFT JOIN tbl_os_item_nf ON tbl_os_item.os_item = tbl_os_item_nf.os_item
                                    LEFT JOIN tbl_pedido ON tbl_os_item.pedido = tbl_pedido.pedido
                                    LEFT JOIN tbl_pedido_item on tbl_pedido.pedido=tbl_pedido_item.pedido
                                    LEFT JOIN tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
                                    WHERE
                                    tbl_os.posto = $login_posto
                                    AND tbl_os.fabrica = $login_fabrica
                                    AND tbl_os.finalizada is NULL
                                    AND tbl_os.data_conserto IS NULL
                                    AND (tbl_os.excluida IS NULL OR tbl_os.excluida = 'f')
                                    AND tbl_os_item.peca not in (select peca from tbl_faturamento_item where tbl_faturamento_item.pedido = tbl_os_item.pedido)
                                    AND os not in (SELECT os FROM tmp_mostra_vermelho_$login_posto)";

                    $resTemp = pg_query($con,$sqlTemp);
                    //echo nl2br($sqlTemp);

                $sqlMostra = "SELECT DISTINCT os,
                                        sua_os,
                                        data_digitacao,
                                        to_char(data_abertura,'DD/MM/YYYY') as data_abertura,
                                        tbl_os.os_reincidente,
                                        tbl_os.serie,
                                        excluida,
                                        motivo_atraso,
                                        tipo_os_cortesia,
                                        tbl_os.consumidor_revenda,
                                        tbl_os.consumidor_nome,
                                        tbl_os.revenda_nome,
                                        impressa,
                                        tbl_os.nota_fiscal,
                                        tbl_os.nota_fiscal_saida,
                                        tbl_produto.referencia,
                                        tbl_produto.descricao,
                                        tbl_produto.voltagem,
                                        tipo_atendimento,
                                        tecnico_nome,
                                        tbl_os.admin,
                                        sua_os_offline,
                                        status_os,
                                        rg_produto,
                                        tbl_produto.linha,
                                        data_conserto,
                                        tbl_marca.marca,
                                        tbl_marca.nome as marca_nome,
                                        consumidor_email
                                    FROM tbl_os
                                    JOIN tbl_os_extra USING(os)
                                    JOIN tbl_produto USING(produto)
                                    LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
                                    WHERE posto = $login_posto
                                    AND   tbl_os.fabrica = $login_fabrica
                                    AND   data_conserto IS NULL
                                    AND   finalizada is NULL
                                    AND   (excluida IS NULL OR excluida = 'f')
                                    AND os not in (SELECT os from  tmp_os_amarelo_$login_posto)
                                    AND os not in (SELECT os FROM tmp_mostra_vermelho_$login_posto)";
                                    $titulo_comunicado .="\"Aguardando Conserto\"";
                            //echo nl2br($sqlMostra);
            break;
            case 'azul':
                $sqlMostra = "SELECT        os,
                                    sua_os,
                                    data_digitacao,
                                    to_char(data_abertura,'DD/MM/YYYY') as data_abertura,
                                    tbl_os.os_reincidente,
                                    tbl_os.serie,
                                    excluida,
                                    motivo_atraso,
                                    tipo_os_cortesia,
                                    tbl_os.consumidor_revenda,
                                    tbl_os.consumidor_nome,
                                    tbl_os.revenda_nome,
                                    impressa,
                                    tbl_os.nota_fiscal,
                                    tbl_os.nota_fiscal_saida,
                                    tbl_produto.referencia,
                                    tbl_produto.descricao,
                                    tbl_produto.voltagem,
                                    tipo_atendimento,
                                    tecnico_nome,
                                    tbl_os.admin,
                                    sua_os_offline,
                                    status_os,
                                    rg_produto,
                                    tbl_produto.linha,
                                    data_conserto,
                                    tbl_marca.marca,
                                    tbl_marca.nome as marca_nome,
                                    consumidor_email
                                    FROM tbl_os
                                    JOIN tbl_os_extra USING(os)
                                    JOIN tbl_produto USING(produto)
                                    LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
                                    WHERE posto = $login_posto
                                    AND tbl_os. fabrica = $login_fabrica
                                    AND finalizada is NULL
                                    AND data_conserto is not null
                                    AND (excluida IS NULL OR excluida = 'f')";
                                    $titulo_comunicado .="\"Aguardando Retirada\"";
            break;

            case 'todas':

                case 'azul':
                $sqlMostra = "SELECT        os,
                                    sua_os,
                                    data_digitacao,
                                    to_char(data_abertura,'DD/MM/YYYY') as data_abertura,
                                    tbl_os.os_reincidente,
                                    tbl_os.serie,
                                    excluida,
                                    motivo_atraso,
                                    tipo_os_cortesia,
                                    tbl_os.consumidor_revenda,
                                    tbl_os.consumidor_nome,
                                    tbl_os.revenda_nome,
                                    impressa,
                                    tbl_os.nota_fiscal,
                                    tbl_os.nota_fiscal_saida,
                                    tbl_produto.referencia,
                                    tbl_produto.descricao,
                                    tbl_produto.voltagem,
                                    tipo_atendimento,
                                    tecnico_nome,
                                    tbl_os.admin,
                                    sua_os_offline,
                                    status_os,
                                    rg_produto,
                                    tbl_produto.linha,
                                    data_conserto,
                                    tbl_marca.marca,
                                    tbl_marca.nome as marca_nome,
                                    consumidor_email
                                    FROM tbl_os
                                    JOIN tbl_os_extra USING(os)
                                    JOIN tbl_produto USING(produto)
                                    LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
                                    WHERE posto = $login_posto
                                    AND tbl_os. fabrica = $login_fabrica
                                    AND finalizada is NULL
                                    AND (excluida IS NULL OR excluida = 'f')";
                                    $titulo_comunicado .="\"Aguardando Retirada\"";
            break;

        }

        $resMostra = pg_query($con, $sqlMostra);

        if (pg_num_rows($resMostra)>0) { ?>
                <div class="p-tb">
                    <div class="main2 tac">
                        <center>
                            <?php $caminho_logo = ($login_fabrica == 87) ? "logos/jacto_admin1.png" : "logos/logo_telecontrol_2017.png"; ?>
                            <img class="pad-bottom" src="<?=$caminho_logo?>" alt="Telecontrol">
                        </center>
                        <h1 class="title no-m tac"><i class="fa fa-info-circle"></i><?=traduz("existem.comunicados.de.leitura.obrigatoria")?></h1>
                    </div>
                </div>
                </div>
                <div class="p-tb bg-primary">
                    <h1 class="title no-m white"><?=$titulo_comunicado;?></h1>
                </div>
                <div class="main3">
                    <div class="box margin-top-2">
                        <div class="">
                            <div class="row">
                                <div id='dlg_motivo' style="display: none; float: right;">
                                    <div id='motivo_header'>Informe o motivo da exclusão
                                    <div style="display: inline;" id='dlg_fechar'>X</div>
                                    <div id='motivo_container'>
                                        <center><p id="exclusao" style='display:none;font-size:12px;font-weight:bold;color:green;'>OS excluída com sucesso!</p></center>
                                        <p>Qual o Motivo da Exclusão da os <span id="motivo_os" alt=''></span>?</p>
                                        <input type="text" name="str_motivo" id="str_motivo" size='50'>
                                        <br>
                                        <button type="button" class='btn' id="dlg_btn_excluir">Excluir</button>
                                        <button type="button" class='btn' id="dlg_btn_cancel">Cancelar</button>
                                    </div>
                                </div>
                                <table class='table_tc table-bordered  table-hover table-large'>
                                    <thead>
                                        <tr class="titulo_coluna">
                                            <th><?=strtoupper(traduz('os'));?></th>
                                            <th><?=strtoupper(traduz('serie'));?></th>
                                            <th><?=strtoupper(traduz('nf'));?></th>
                                            <th style="cursor:help" title="<?=traduz("Data de abertura da OS");?>"><?=strtoupper(traduz('ab'));?></th>
                                            <th style="cursor:help" title="<?=traduz("data.de.conserto.do.produto");?>"><?=strtoupper(traduz('DC'));?></th>
                                             <th style="cursor:help" title="<?=traduz("data.de.fechamento.registrada.pelo.sistema");?>"><?=strtoupper(traduz('FC'));?></th>
                                            <th><?=strtoupper(traduz('consumidor'));?></th>
                                            <th><?=strtoupper(traduz('marca'));?></th>
                                            <th><?=strtoupper(traduz('produto'));?></th>
                                            <th>AÇÕES</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                <?php
                for ($i = 0 ; $i < pg_num_rows($resMostra) ; $i++) {

                    $os                 = trim(pg_fetch_result($resMostra,$i,os));
                    $sua_os             = trim(pg_fetch_result($resMostra,$i,sua_os));
                    $digitacao          = trim(pg_fetch_result($resMostra,$i,data_digitacao));
                    $abertura           = trim(pg_fetch_result($resMostra,$i,data_abertura));
                    $serie              = trim(pg_fetch_result($resMostra,$i,serie));
                    $excluida           = trim(pg_fetch_result($resMostra,$i,excluida));
                    $motivo_atraso      = trim(pg_fetch_result($resMostra,$i,motivo_atraso));
                    $tipo_os_cortesia   = trim(pg_fetch_result($resMostra,$i,tipo_os_cortesia));
                    $consumidor_revenda = trim(pg_fetch_result($resMostra,$i,consumidor_revenda));
                    $consumidor_nome    = trim(pg_fetch_result($resMostra,$i,consumidor_nome));
                    $revenda_nome       = trim(pg_fetch_result($resMostra,$i,revenda_nome));
                    $impressa           = trim(pg_fetch_result($resMostra,$i,impressa));
                    $nota_fiscal        = trim(pg_fetch_result($resMostra,$i,nota_fiscal));//hd 12737 31/1/2008
                    $nota_fiscal_saida  = trim(pg_fetch_result($resMostra,$i,nota_fiscal_saida));   //
                    $reincidencia       = trim(pg_fetch_result($resMostra,$i,os_reincidente));
                    $produto_referencia = trim(pg_fetch_result($resMostra,$i,referencia));
                    $produto_descricao  = trim(pg_fetch_result($resMostra,$i,descricao));
                    $produto_voltagem   = trim(pg_fetch_result($resMostra,$i,voltagem));
                    $tecnico_nome       = trim(pg_fetch_result($resMostra,$i,tecnico_nome));
                    $admin              = trim(pg_fetch_result($resMostra,$i,admin));
                    $sua_os_offline     = trim(pg_fetch_result($resMostra,$i,sua_os_offline));
                    $status_os          = trim(pg_fetch_result($resMostra,$i,status_os));
                    $rg_produto         = trim(pg_fetch_result($resMostra,$i,rg_produto));
                    $linha              = trim(pg_fetch_result($resMostra,$i,linha));
                    $marca              = trim(pg_fetch_result($resMostra,$i,marca));
                    $marca_nome         = trim(pg_fetch_result($resMostra,$i,marca_nome));
                    $data_conserto      = trim(pg_fetch_result($resMostra,$i,data_conserto));
                    $consumidor_email   = trim(pg_fetch_result($resMostra,$i,consumidor_email));

                if (strlen($sua_os) == 0) $sua_os = $os;
                if ($login_fabrica == 1) $xsua_os =  $codigo_posto.$sua_os ;
                if($fechamento) {
                    $aux_fechamento = $fechamento;
                } else  {
                    $aux_fechamento = "";
                }
                $produto = $produto_referencia . " - " . $produto_descricao;
                ?>

                <tr>
                    <td class='tac'><a href="os_press.php?os=<?=$os;?>" target="_blank"><?=$sua_os;?></a></td>
                    <td><?=$serie;?></td>
                    <td class="tal"><?=$nota_fiscal;?></td>
                    <td class='tac'><?=$abertura;?></td>
                    <td class='tac'><?=$data_conserto;?></td>
                    <td class='tac'><?=$aux_fechamento;?></td>
                    <td class='tal' style="cursor:help" title="<?=$consumidor_nome;?>"><?=substr($consumidor_nome,0,15);?></td>
                    <td  class='tal'><?=strtoupper($marca_nome);?></td>
                    <td style="cursor:help" title="<?=$produto;?>"><?=substr($produto,0,20);?></td>
                    <td class="acoes">
                        <a title="Consultar a OS" class='btn_action' href="os_press.php?os=<?=$os;?>" target="_blank"><i class="fa fa-search-plus fa-lg"></i></a>
                    <?php if(strlen($fechamento==0)){ ?>
                            <a title="Imprimir a OS" class='btn_action' href="os_print.php?os=<?=$os;?>" target="_blank"><i class="fa fa-print fa-lg"></i></a>
                            <a title="Lançar Ítens na OS" class='btn_action' href="os_item.php?os=<?=$os;?>" target="_blank"><i class="fa fa-th-list fa-lg"></i></a>
                    <?php }
                    if (strlen($fechamento) == 0 && $status_checkpoint < 3) {
                        if (!in_array($status_os, array(20,62,65,158,72,87,116,120,122,126,140,141,143,167, 174)) || ($reincidencia=='t')) {
                            if ((($excluida == "f" || strlen($excluida) == 0 ) && !$reparoNaFabrica) || ($reparoNaFabrica && $aux_reparo_produto == "t")) {
                                if (strlen ($admin) == 0 && strlen($data_conserto) == 0 ) {?>
                                    <a title="Excluir a OS" id='excluir_<?=$i;?>' class='btn_action' href="javascript: void(0)" onclick='select_acoes("excluir","<?=$os;?>", "<?=$sua_os;?>");'><i class="fa fa-trash fa-lg"></i></a>
                            <?php }
                            }
                        }
                    } else {?>
                            <a title="Excluir a OS" class='btn btn_disabled' href="#"><i class="fa fa-trash fa-lg"></i></a>
                    <?php } ?>
                        <a title="Fechar a OS" class='btn_action' href="os_fechamento.php?sua_os=<?=$sua_os;?>&btn_acao_pesquisa=continuar" target="_blank"><i class="fa fa-window-close fa-lg"></i></a>
                    <?php
                        if(strlen($fechamento) == 0 && !in_array($status_os, array(62,65,72,87,116,120,122,126,98))){
                            if ($excluida == "f" || strlen($excluida) == 0) { ?>
                                    <a title="Consertada a OS" class='btn_action' href="javascript: void(0)" onclick='select_acoes("consertado", "<?=$os;?>", "<?=$sua_os;?>", "consertado_<?=$i;?>","<?=$i;?>");'><i class="fa fa-wrench fa-lg"></i></a>
                    </td>
                                <?php
                            }
                        }
                    ?>
                 </tr>
                <?php } ?>
                        </tbody>
                    </table>
                </div>
                </div>
                </div>
                </div>
                <center>
                    <a class='btn' href="menu_inicial.php"><i class="fa fa-paper-plane fa-lg"></i>&nbsp; Continuar</a>
                    <br><br>
                </center>
    <?php   }
    }
}

            $sqlStatus = "SELECT DISTINCT os
            into TEMP tmp_amerelo_os_$login_posto
            FROM tbl_os
            JOIN tbl_os_produto using (os)
            JOIN tbl_os_item USING (os_produto)
            JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
            JOIN tbl_peca USING (peca)
            LEFT JOIN tbl_defeito USING (defeito)
            LEFT JOIN tbl_servico_realizado USING (servico_realizado)
            LEFT JOIN tbl_os_item_nf ON tbl_os_item.os_item = tbl_os_item_nf.os_item
            LEFT JOIN tbl_pedido ON tbl_os_item.pedido = tbl_pedido.pedido
            LEFT JOIN tbl_pedido_item on tbl_pedido.pedido=tbl_pedido_item.pedido
            LEFT JOIN tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
            WHERE
            tbl_os.posto = $login_posto
            AND tbl_os.fabrica = $login_fabrica
            AND tbl_os.finalizada is NULL
            AND (tbl_os.excluida IS NULL OR tbl_os.excluida = 'f')
            AND data_conserto IS NULL
            AND tbl_os_item.peca not in (select peca from tbl_faturamento_item where tbl_faturamento_item.pedido = tbl_os_item.pedido)
            AND os not in (select os from tmp_os_vermelha_$login_posto)" ;

        if (pg_num_rows($res) > 0) {

            $comunicado_contrato = false;

            if(in_array($login_fabrica, array(1)) && $login_credenciamento == "CREDENCIADO"){
                $contador_res = pg_num_rows($res);
                for ($i = 0 ; $i < $contador_res; $i++) {

                    $comunicado      = pg_fetch_result($res, $i, 'comunicado');
                    $extensao        = pg_fetch_result($res, $i, 'extensao');
                    $descricao       = pg_fetch_result($res, $i, 'descricao');
                    $video           = trim(pg_fetch_result($res, $i, 'video')); // HD 65474
                    $link            = trim(pg_fetch_result($res, $i, 'link_externo')); // HD 65474
                    $mensagem        = pg_fetch_result($res, $i, 'mensagem');
                    $data            = pg_fetch_result($res, $i, 'data');
                    $exibedata       = pg_fetch_result($res, $i, 'exibedata');
                    $tipo_comunicado = pg_fetch_result($res, $i, 'tipo');

                    if($tipo_comunicado == "Contrato"){

                        if(!in_array($desc_categoria_posto, array("Autorizada", "Locadora Autorizada"))){

                            continue;

                        }else{
                            $contrato_servico = $comunicado_contrato = true;?>
                            <div class="p-tb">
                                <div class="main2 tac">
                                    <img class="pad-bottom" src="logos/logo_telecontrol_2017.png" alt="Telecontrol">
                                    <h1 class="title no-m tac"><i class="fa fa-info-circle"></i>Existe um contrato de leitura obrigatória</h1>
                                </div>
                            </div>
                            </div>
                            <div class="p-tb bg-primary">
                                <h1 class="title no-m white">Contrato</h1>
                            </div>
                            <div class='row-fluid'>
                                <div class='span12'> <br /> <br />
                                        <table width='80%' style='margin: 0 auto;'>
                                            <tr>
                                                <td> <img src='logos/logo_black_2017.png' alt='logo' width='300px'> </td>
                                                <td align='right'> Uberaba, 8 de maio de 2017 </td>
                                            </tr>
                                            <tr>
                                                <td colspan='2' align='center'>
                                                    <br />
                                                    <h4>Prezado parceiro,</h4>
                                                    <br />
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan='2'>
                                                    Com o objetivo de estabelecer os direitos e deveres de nossa parceria e obter 100% dessas informações online, disponibilizamos o ACORDO DE PRESTAÇÃO DE SERVIÇOS revisado.
                                                    <br /> <br />
                                                    Portanto, gentileza imprimir este comunicado e seguir as etapas abaixo para liberação do sistema:
                                                    <br /> <br />
                                                    1. O <strong>Representante Legal</strong> da empresa deverá: <br />
                                                    &nbsp; &nbsp; &nbsp; 1.1. Imprimir uma cópia do Acordo anexado ao comunicado <br />
                                                    &nbsp; &nbsp; &nbsp; 1.2. Rubricar (vistar) as páginas 1 e 2 <br />
                                                    &nbsp; &nbsp; &nbsp; 1.3. Assinar e carimbar a página 3 (conforme RG) <br />
                                                    2. A <u>Testemunha</u> da empresa deverá: <br />
                                                    &nbsp; &nbsp; &nbsp; 2.1. Rubricar (vistar) as páginas 1 e 2 <br />
                                                    &nbsp; &nbsp; &nbsp; 2.2. Na página 3, preencher o nome completo no campo da \"testemunha 2\" <br />
                                                    &nbsp; &nbsp; &nbsp; 2.3. Informar o RG ou CPF <br />
                                                    3. Após assinatura do representante e testemunha: <br />
                                                    &nbsp; &nbsp; &nbsp; 3.1. Anexar o acordo completo no Telecontrol (passo a passo abaixo) <br />
                                                    &nbsp; &nbsp; &nbsp; 3.2. Anexar o Contrato Social da empresa ou Requerimento de Empresário <br />
                                                    &nbsp; &nbsp; &nbsp; 3.3. Anexar RG frente e verso do Representante Legal/Administrador <br />
                                                    <strong>PASSO A PASSO</strong>: Menu inicial > Cadastro > Informações do posto > Upload De Contratos
                                                    <br /> <br />
                                                    <strong>Observações importantes:</strong> Caso seu contrato apresente erro ou as informações não estejam de acordo (apenas nesses casos), gentileza abrir um chamado escolhendo o tipo de solicitação \"Atualização de cadastro\" com o contrato anexado.
                                                    Se os dados estiverem corretos, solicitamos que o contrato de prestação de serviço seja anexado em um arquivo PDF e o contrato social da empresa / RG frente e verso em outro arquivo PDF. <br />
                                                    O sistema Telecontrol será bloqueado automaticamente após 30 dias corridos caso não tivermos retorno da solicitação acima.
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan='2' align='center'>
                                                    <br /> <br />
                                                    Contamos com a colaboração de todos. <br /> <br />
                                                    Qualquer dúvida, gentileza entrar em contato com o suporte de sua região. <br /> <br />
                                                    Departamento de Assistência Técnica <br />
                                                    STANLEY BLACK&DECKER
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan='2' align='center'>
                                                    <br />
                                                    <a href='javascript: download_contrato_servico(<?=$comunicado;?>)' class='btn btn-danger' style='text-transform: uppercase;'>
                                                    <?php fecho("realizar.o.download.do.contrato"); ?>
                                                    </a>
                                                    <br /> <br />
                                                </td>
                                            </tr>
                                        </table>
                                        <br /> <br />
                                </div>
                            </div> -->
                        <?php }
                    }
                }
            }
        }
        if($comunicado_contrato == false){?>

        <?php

            // Inicializa o acesso ao S3...
            if ($S3_sdk_OK) {
                include_once S3CLASS;
                $s3 = new anexaS3('ve', (int) $login_fabrica);
                $S3_online = (is_object($s3));
            }
            $cont = pg_num_rows ($res);
            for ($i = 0 ; $i < $cont ; $i++) {

                $comunicado      = pg_fetch_result($res, $i, 'comunicado');
                $extensao        = pg_fetch_result($res, $i, 'extensao');
                $descricao       = pg_fetch_result($res, $i, 'descricao');
                $video           = trim(pg_fetch_result($res, $i, 'video')); // HD 65474
                $link            = trim(pg_fetch_result($res, $i, 'link_externo')); // HD 65474
                $mensagem        = (mb_check_encoding(pg_fetch_result($res, $i, 'mensagem'), "UTF-8")) ? utf8_decode(pg_fetch_result($res, $i, 'mensagem')) : pg_fetch_result($res, $i, 'mensagem');
                $data            = pg_fetch_result($res, $i, 'data');
                $exibedata       = pg_fetch_result($res, $i, 'exibedata');
                $tipo_comunicado = pg_fetch_result($res, $i, 'tipo');

                $tem_anexo  = false;    //  Para evitar que os links não apareçam depois...

                if(in_array($login_fabrica, array(1)) && $tipo_comunicado == "Contrato"){
                    continue;
                }
                //HD 31528
                if($login_fabrica ==3 or $login_fabrica ==66){
                    $sqld="SELECT (current_date -'$data') > 5 as bloqueia";
                    $resd=pg_query($con,$sqld);
                    $bloqueia=pg_fetch_result($resd, 0, 'bloqueia');
                    if($bloqueia =='t'){
                        $bloqueia_leio_depois = "bloqueia";
                    }
                }

                $comunicados[$comunicado] = array(
                    'nr'       => traduz("nr")." ".$comunicado,
                    'data'     => traduz("data")." ".$exibedata,
                    'title'    => $descricao,
                    'mensagem' => $mensagem,
                );
                ?>

                <?php
                if (is_object($s3) and $s3->set_tipo_anexoS3($tipo_comunicado)->temAnexos((int) $comunicado)) { //hd_chamado=2824422

                    /*if (empty($s3->url)) {
                        if ($tipo_comunicado != $s3->tipo_anexo)
                            $s3->set_tipo_anexoS3($tipo_comunicado);
                        if($login_fabrica == 147) { $tem_anexo = true; }
                            $s3->temAnexos((int) $comunicado);
                        $comLink =  $s3->url;
                    } else {
                        $comLink =  $s3->url;
                    }*/
                    $comLink = "shadowbox_view_comunicado.php?comunicado={$comunicado}";


                    $comunicados[$comunicado]['anexo'] = array(
                        'btn_abrir' => "<div style='float: left;margin-top: -8px;'>".
                            "<a href='javascript: void(0)' class='btn' onclick='window.open(\"$comLink\",\"_blank\", \"toolbar=no, status=no, scrollbars=yes, resizable=yes, width=700, height=500\"); abrirAnexo(\"$comunicado\",\"$comLink\")'><i class='fa fa-paperclip fa-lg'></i>&nbsp;" . traduz("Abrir anexo") . '</a></div>
                        ',
                    );

                    if (!empty($video)) {
                        $comunicados[$comunicado]['video'] = array(
                            'btn_video' => "
                                <div style='float: right;'>
                                    <a class='btn' href='javascript:window.open(\"video.php?video=$video\",\"_blank\",\"toolbar=no, status=no, scrollbars=no, resizable=yes, width=460, height=380\");void(0);abrirAnexo(\"$comunicado\",\"$video\")'><i class= 'fa fa-video-camera fa-lg'></i>&nbsp;
                                        " . traduz("Assistir vídeo anexado") . "
                                    </a>
                                </div>
                                ",
                        );
                    }

                    $tem_anexo = true;
                }

                if($login_fabrica == 42){
                    $tem_anexo = "";
                    if (strlen($link) > 0) {
                        $comunicados[$comunicado]['link'] = array(
                            'descricao' => "<a href='$link' target='_blank'>Clique aqui para acessar ao link</a>",
                        );
                    }
                }

                if(in_array($login_fabrica, array(1)) && $tipo_comunicado == "Contrato"){

                    continue;

                }else{

                    //echo "<td class='tac' nowrap style='vertical-align: inherit; font-size:14px;'>";

                    #HD 15726
                    $esconder_link = "";
                    if ((strlen(trim($extensao)) > 0 AND $login_fabrica == 45) or $tem_anexo) {
                        $esconder_link = " style='display:none'";
                    }

                    if ($tipo_comunicado == "F AUT" || ($login_fabrica == 52 && $tipo_comunicado == 'auditoria_online') || ($login_fabrica == 3 && $descricao == 'OS 150 dias com pedido') ) {

                        if($login_fabrica == 3) {
                            $bloqueia_leio_depois = 1;
                        }

                        if ($tipo_comunicado == 'auditoria_online') {
                            $comunicados[$comunicado]['pdf'] = array(
                                'descricao' => '<a href=\"geraPDF_auditoria.php?comunicado=' . $comunicado . '>Ver anexo</a> |',
                            );
                        }

                        $comunicados[$comunicado]['cl'] = array(
                            'descricao' => "
								<span$esconder_link id='comunicado_id_$comunicado'>
									<center>
                                    <a class='btn'  href='javascript: var leitor = prompt(\"Por favor, digite seu nome\",\"\"); if (trim(leitor)) { window.location=\"$PHP_SELF?comunicado_lido=$comunicado&leitor=\"+leitor; } else {alert(\"Para registrar a leitura deste comunicado é obrigatório informar o nome\"); }
                                    '><i class='fa fa-check-square fa-lg'></i>&nbsp;" .
                                    traduz("ja.li.e.confirmo") ."
                                    </a></center></span>
                            ",
                        );
                    } else {

                            if((in_array($login_fabrica, array(152,180,181,182))) AND $tipo_comunicado == "Contrato"){
                                if (count($msg_erro["msg"]) > 0) {
                                ?>
                                    <div class="alert alert-error">
                                        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
                                    </div>
                                <?php
                                }
                            ?>
                                <form name='frm_contrato' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
                                    <input type="hidden" name="id_contrato" class='span12' maxlength="20" value="<? echo $comunicado ?>" >
                                    <div class='titulo_tabela '><?=traduz('Por favor, informe seu Nome e CPF para continuar')?></div>
                                    <br/>
                                    <div class='row-fluid'>
                                        <div class='span1'></div>
                                        <div class='span5'>
                                            <div class='control-group <?=(in_array("nome_contrato", $msg_erro["campos"])) ? "error" : ""?>'>
                                                <label class='control-label' for='nome_contrato'><?=traduz("nome")?></label>
                                                <div class='controls controls-row'>
                                                    <div class='span12'>
                                                        <input type="text" name="nome_contrato" class='span12' maxlength="20" value="<? echo $nome_contrato ?>" >
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class='span5'>
                                            <div class='control-group <?=(in_array("cpf_contrato", $msg_erro["campos"])) ? "error" : ""?>'>
                                                <label class='control-label' for='cpf_contrato'>CPF</label>
                                                <div class='controls controls-row'>
                                                    <div class='span12'>
                                                        <input type="text" name="cpf_contrato" class='span12' value="<? echo $produto_descricao ?>" >
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class='span1'></div>
                                    </div>
                                    <div class='row-fluid'>
                                        <div class='span1'></div>

                                        <div class='span5'>
                                             <label class="radio">
                                                <input type="radio" name="aceita_contrato" value="sim" checked>
                                                Li e aceito o contrato
                                            </label>
                                        </div>
                                        <div class='span5'>
                                            <label class="radio">
                                                <input type="radio" name="aceita_contrato" value="nao">
                                                Não, não aceito o contrato
                                            </label>
                                        </div>
                                        <div class='span1'></div>
                                    </div>
                                    <p>
                                        <?php
                                        /*echo "<input $esconder_link type='submit' class='btn' id='comunicado_id_$comunicado' name='btn_acao' value='Enviar' />"*/
                                        echo "<input type='submit' class='btn' id='comunicado_id_$comunicado' name='btn_acao' value='Enviar' />";
                                        ?>
                                    </p><br/>
                                </form>

                            <?php
                            }else{
                                //echo "<span$esconder_link id='comunicado_id_$comunicado'>";
                                if (!empty($video)) {
                                    $comunicados[$comunicado]['esconder_link'] = array(
                                        'descricao' => "
                                           <span$esconder_link id='comunicado_id_$comunicado'\>
                                               <center>
                                                    <a class='btn btn-li-confirmo' data-comunicado='{$comunicado}' href='login.php?comunicado_lido=$comunicado'><i class='fa fa-check-square fa-lg'></i>&nbsp;
                                                        " . traduz("ja.li.e.confirmo") . "
                                                    </a>
                                                </center>
                                            </span>
                                        ",
                                    );

                                } else {
                                    $comunicados[$comunicado]['esconder_link'] = array(
                                        'descricao' => "
                                           <span$esconder_link id='comunicado_id_$comunicado'\>
                                               <center>
                                                    <a class='btn btn-li-confirmo' data-comunicado='{$comunicado}' href='login.php?comunicado_lido=$comunicado'><i 
                                                       class='fa fa-check-square fa-lg'></i>&nbsp;
                                                        " . traduz("ja.li.e.confirmo") . "
                                                    </a>
                                                </center>
                                            </span>
                                        ",
                                    );                                 }
                            }
                    }

                    if ($tem_anexo) {
                        if($tipo_comunicado == "Contrato"){
                            $comunicados[$comunicado]['segundo_anexo'] = 
                        "            <strong>
                                        <span style='color: red;' title='Confira o arquivo anexo ao comunicado!' id='com_anexo_$comunicado'>".traduz("Por favor, abrir o anexo para continuar.")."
                                        </span>
                                    </strong>
                                ";
                        }
                    }
                }
            }
        }

        if (pg_num_rows($res)>0) {

            if($contrato_servico == true){
                exit;
            }

            # Adicionado NKS no HD 54445 HD 261366 Precision

            //HD-3401374
            if (
                in_array($login_fabrica, array(1,2,11,45,80,162,169,170)) // Mostra 'leio depois' sempre
                or ($login_fabrica == 91 and $cook_admin)
                or (in_array($login_fabrica, array(3,14,43,66)) and strlen($bloqueia_leio_depois) ==0)
               ) {// Mostra 'leio depois' por 5 dias, depois eles tem que ler

                $leio_wanke = ($login_fabrica == 91 and $cook_admin) ? "&wanke_click=1" : null;

                if(in_array($login_fabrica, array(1))){
                    //$display_tr = ($contrato_servico == false) ? "style='block;'" : "style='display: none;'";
                }

                $info_add['leio_depois'] = array(
                    'descricao' => "
                        <div>
                            <center>
                                <a class='btn' href='$PHP_SELF?leio_depois=1$leio_wanke'><i class='fa fa-eye-slash fa-lg'></i>
                                    &nbsp; ". traduz("leio.depois") ."
                                </a>
                            </center>
                        </div>
                    ",
                );
            }

            $info_add['footer_msg'] = array(
                'descricao' => "
                    <center>
                        <font size='2' color='#484a4b'>
                            *" . traduz("Posicione o mouse sobre o comunicado para visualizar todo o seu conteúdo.") . "
                        </font>
                    </center>
                ",
            );
        }
    }else{
        if($ajax =='sim' and $nao_antigos == 'true') { # HD 669556
            echo "<resposta>NO</resposta>";exit;
        }
    }
}

if(count($comunicados) > 0){?>
    <div class="p-tb">
        <div class="main2 tac">
            <center>
                <?php $caminho_logo = ($login_fabrica == 87) ? "logos/jacto_admin1.png" : "logos/logo_telecontrol_2017.png"; ?>
                <img class="pad-bottom" src="<?=$caminho_logo?>" alt="Telecontrol">
            </center>
            <h1 class="title no-m tac upper"><i class="fa fa-info-circle"></i><?=traduz("existem.comunicados.de.leitura.obrigatoria")?></h1>
        </div>
    </div>
    <div class="p-tb bg-primary">
        <h1 class="title no-m white"><?=strtoupper(traduz("comunicados"));?></h1>
    </div>
    <div class="main2">
    <?php
    $count = 0;
    foreach ($comunicados as $comunicado => $dados) {
        $count++;?>
        <div class="box margin-top-2 div_comunicados_leitura" id="div_comunicado_<?= $comunicado ?>">
            <div class="">
                <div class="row">
                    <ul>
                        <li><?=$dados['nr'];?></li>
                        <li><?=$dados['data'];?></li>
                    </ul>
                </div>
                <div class="row tac content_transition">
                    <hr>
                    <h1 class="title"><?=$dados['title'];?></h1>
                    <p style="text-align: justify !important" ><?=nl2br($dados['mensagem']);?></p>
                </div>
                <?php
                if (in_array($login_fabrica, [85])) { ?>
                    <div class="alert alert-error" id="msg_erro_<?= $comunicado ?>" style="margin: 25px;padding: 10px;text-align: center;background-color: #f2dede;color: #b94a48;border: 1px solid #eed3d7;display: none;">
                        <strong>Nome e CPF obrigatórios</strong>
                    </div>
                    <div class="row-fluid" style="margin-left: 100px;">
                        <div class='span12'>
                            <div class='control-group'>
                                <div class='controls controls-row'>
                                    <div class='span12'>
                                        <h2 style="text-align: center;font-weight: bolder;font-size: 18px;position: relative;left: -50px;">Responsável</h2><br />
                                        <label class='control-label'>
                                            <strong><span style="color: red;">*</span> <span style="color: #66635b;"><?=traduz("Nome")?>:</span>&nbsp;&nbsp;&nbsp;</strong>
                                            <input type="text" style="width: 180px;" placeholder="Informe seu nome" name="nome_comunicado" class='span6 nome' data-comunicado="<?= $comunicado ?>" maxlength="50" />
                                        </label>
                                        <label style="margin-left: 50px;" class='control-label'>
                                            <strong><span style="color: red;">*</span> <span style="color: #66635b;"><?=traduz("CPF")?>:</span>&nbsp;&nbsp;&nbsp;</strong>
                                            <input type="text" style="width: 150px;" placeholder="Informe seu CPF" name="cpf_comunicado" data-comunicado="<?= $comunicado ?>" class='span6 cpf_comunicado' />
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <br />
                <?php
                } ?>
                <?=$dados['anexo']['btn_abrir'];?>
                <?=$dados['anexo']['btn_video'];?>
                <?=$dados['link']['descricao'];?>
                <?=$dados['cl']['descricao'];?>
                <?=$dados['esconder_link']['descricao'];?>
                <?=$dados['segundo_anexo'];?>
            </div>
        </div>
    <?php 
    }
    
    if($count > 0) {?>
        <div class="pad-bottom-4"></div>
        </div>
    <?php 
    }
    
    $btn_ld     = $info_add['leio_depois']['descricao'];
    $footer_msg = $info_add['footer_msg']['descricao'];
    echo $btn_ld . "<br>" . $footer_msg . "<br>";
    ?>
    </body>
    </html>
    
    <?php
    exit;
} else {
	echo '<meta http-equiv="refresh" content="0; url=menu_inicial.php">';
	exit;
}

if($login_fabrica == 86){
    $sql = "SELECT tbl_os.os ,
                    tbl_os.sua_os ,
                    TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura
                FROM tbl_os
                JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i=86
                LEFT JOIN tbl_os_produto using(os)
                WHERE tbl_os.fabrica = $login_fabrica
                AND tbl_os.posto = $login_posto
                AND tbl_os.excluida IS NOT TRUE AND tbl_os.data_abertura < CURRENT_DATE - INTERVAL '3 days' AND tbl_os.data_fechamento IS NULL
                AND coalesce(tbl_os_produto.os_produto,null) is null
                LIMIT 3";
    $res = pg_query($con,$sql);
    if (pg_num_rows($res)>0) { ?>

        <div class="p-tb">
            <div class="main2 tac">
                <?php $caminho_logo = ($login_fabrica == 87) ? "logos/jacto_admin1.png" : "logos/logo_telecontrol_2017.png"; ?>
                <img class="pad-bottom" src="<?=$caminho_logo?>" alt="Telecontrol">
                <h1 class="title no-m tac"><i class="fa fa-info-circle"></i><?=traduz("existem.comunicados.de.leitura.obrigatoria");?></h1>
            </div>
        </div>
        <?php  ?>
        </div>
        <div class="p-tb bg-primary">
            <h1 class="title no-m white"><?=traduz("OS abertas a mais de 3 dias sem lançamento de peças");?></h1>
        </div>
        <div class="main2">
        <div class="box margin-top-2">
            <div class="">
                <div class="row">
                    <h1 class="title"><?=traduz("Observação")?></h1>
                    <p>
                            <?=traduz("Caso a OS não necessite de troca de peças, por favor informe a peça em que foi feito algum reparo ou manutenção e selecione o tipo de ajuste realizado.")?>
                    </p>
                </div>
        <?php
         for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
            $os            = pg_fetch_result($res, $i, 'os');
            $sua_os        = pg_fetch_result($res, $i, 'sua_os');
            $data_abertura = pg_fetch_result($res, $i, 'data_abertura'); ?>
                <hr>
                <div class="row">
                    <ul>
                        <li><?=fecho("os");?> <a href="javascript: window.location='os_item_new.php?os=<?=$os;?>'"><?=$sua_os;?></a></li>
                        <li><?=fecho("Data de Abertura");?> <?=$data_abertura;?></li>
                    </ul>
                </div>
        <?php } ?>
            </div>
            </div>
            </div>
            <div class="pad-bottom-4"></div>
            </div>
            <center>
            <span <?=$esconder_link;?> id='comunicado_id_<?=$comunicado;?>'>
                <a href="javascript: window.location='menu_inicial.php'" class="btn margin-top">
                    <i class='fa fa-check-square fa-lg'></i>&nbsp;
                    <?=fecho("ja.li.e.confirmo");?>
                </a>
            </span>
            </center>
        <?php exit;
    }
}
?>
</body>
</html>

<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
if (isset($_GET['admin']) && $_GET['admin'] = 'true') {
    include 'admin/autentica_admin.php';
}else{
    include 'autentica_usuario.php';
}

if (count($_POST) && $_POST['btn_acao'] == "Agendar") {
    if (empty($_POST['data']) || empty($_POST['justificativa'])) {
        $msg_erro = 'Preencha todos os campos para realizar corretamente o agendamento';
    }else{
        $os = $_POST['os'];
        $data = explode('/',$_POST['data']);
        $data = $data[2].'-'.$data[1].'-'.$data[0];
        $justificativa = $_POST['justificativa'];

        if ($data < date('Y-m-d')) {
            $msg_erro = 'Não será possível agendar uma data anterior ao dia de hoje';
        }else{
            if ($justificativa == 5) { /* Avaliação Inicial */
                $sql = "SELECT
                            tbl_os_visita.hora_chegada_cliente
                        FROM tbl_os_visita
                            LEFT JOIN tbl_justificativa USING(justificativa)
                        WHERE justificativa = 5 AND tbl_os_visita.os = {$os} AND justificativa_valor_adicional IS NULL";
                $res = pg_query($con, $sql);
                if (pg_num_rows($res) > 0) {
                    $msg_erro = 'Não será possível agendar uma avaliação inicial pois já existe um agendamento realizado.';
                }
            }
        }
        if (empty($msg_erro)) {
            $sql = "INSERT INTO tbl_os_visita(
                                    os,
                                    data,
                                    justificativa
                                )VALUES(
                                    {$os},
                                    '{$data}',
                                    {$justificativa}
                                )";

            pg_query($con, $sql);
            if (strlen(pg_last_error()) > 0) {
                $msg_erro = "Ocorreu um erro ao tentar inserir um novo agendamento";
            }else{
                $ok = 'Agendamento realizado com sucesso';
            }
        }
    }
}

$data_atual = date('d/m/Y');

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <title><?='Agendar Visita'?></title>
    <meta name="Author" content="">
    <meta name="Keywords" content="">
    <meta name="Description" content="">
    <meta http-equiv="pragma" content="no-cache">

    <link rel="stylesheet" type="text/css" href="css/lupas/lupas.css">
    <link rel="stylesheet" type="text/css" href="css/posicionamento.css">
    <link rel="stylesheet" type="text/css" href="js/thickbox.css" media="screen">
    <style type="text/css">
        @import "plugins/jquery/datepick/telecontrol.datepick.css";
        body {
            margin: 0;
            font-family: Arial, Verdana, Times, Sans;
            background: #fff;
        }
        .label{
            text-align: left;
        }
    </style>

    <script type="text/javascript" src="js/jquery-1.6.2.js"></script>
    <script src="js/thickbox.js" type="text/javascript"></script>
    <script type="text/javascript" src="js/jquery.maskedinput2.js"></script>
    <script src="plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>
    <script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick.js"></script>
    <script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
    <script type="text/javascript">
        $(function(){
            $('#data').datepick({
                startDate: <?="'$data_atual'"?>,
                minDate: <?="'$data_atual'"?>
            });
            $("#data").maskedinput("99/99/9999");
        });
    </script>
</head>

<body>
    <div class="lp_header">
        <a href='javascript:window.parent.Shadowbox.close();' style='border: 0;'>
            <img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
        </a>
    </div>
    <?php if (!empty($msg_erro)) { ?>
    <div style="background-color: red; color: white;">
        <label><?=$msg_erro; ?></label>
    </div>
    <?php }elseif (!empty($ok)) {
    ?>
    <div style="background-color: green; color: white;">
        <label><?=$ok; ?></label>
    </div>    
    <?php
    } ?>
    <br />
    <div class='lp_nova_pesquisa'>
        <form action='<?=$PHP_SELF?>' method='POST' name='novo_upload' enctype="multipart/form-data">
            <table cellspacing='1' cellpadding='2' border='0'>
                <tr>
                    <td>
                        <input type="hidden" name="os" value="<?=$os; ?>">
                        <label class="label">Data</label>
                        <input type="text" id='data' name="data" value="">
                    </td>
                    <td>
                        <label class="label">Justificativa</label>
                        <select name='justificativa'>
                            <option value=""></option>
                            <?php
                            $sql = "SELECT
                                        justificativa,
                                        descricao
                                    FROM tbl_justificativa
                                    WHERE fabrica = {$login_fabrica} AND ativa = true ORDER BY descricao";
                            $res = pg_query($con, $sql);
                            for ($i = 0; $i < pg_num_rows($res); $i++) {
                                $descricao = pg_fetch_result($res, $i, "descricao");
                                $descricao = (mb_detect_encoding($descricao, 'utf-8', true)) ? utf8_decode($descricao) : $descricao;
                                echo "<option value='".pg_fetch_result($res, $i, "justificativa")."'>{$descricao}</option>";
                            }
                            ?>
                        </select>
                    </td>
                    <td class='btn_acao' valign='bottom'>
                        <input type='submit' name='btn_acao' value='Agendar' />
                    </td>
                </tr>
            </table>
        </form>
    </div>

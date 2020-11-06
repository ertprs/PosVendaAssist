<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios = "call_center";
include 'autentica_admin.php';

include 'funcoes.php';

if (isset($_POST["ajaxRefresh"])) {
        $os = $_POST["os"];

        $sql = "SELECT 
                tbl_os_interacao.os_interacao,
                to_char(tbl_os_interacao.data,'DD/MM/YYYY HH24:MI') as data,
                tbl_os_interacao.comentario,
                tbl_os_interacao.interno,
                tbl_os.posto,
                tbl_posto_fabrica.contato_email as email,
                tbl_admin.nome_completo
              FROM tbl_os_interacao
              JOIN tbl_os            ON tbl_os.os    = tbl_os_interacao.os
              JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
              LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os_interacao.admin
              WHERE tbl_os_interacao.os = $os
              AND tbl_os.fabrica = {$login_fabrica}
              ORDER BY tbl_os_interacao.os_interacao DESC";
    $res  = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
    ?>
        <?php
        $k = 1;

        while ($result = pg_fetch_array($res)) {
            if ($result["interno"] == 't') {
                $cor = "style='font-family: Arial; font-size: 8pt; font-weight: bold; text-align: left; background: #F3F5CF;'";
            } else {
                $cor = "class='conteudo'";
            }
            ?>
            <tr>
                <td width="25" <?=$cor?> ><?=$k?></td>
                <td width="90" <?=$cor?> nowrap ><?=$result["data"]?></td>
                <td <?=$cor?> ><?=$result["comentario"]?></td>
                <td <?=$cor?> nowrap ><?=$result["nome_completo"]?></td>
            </tr>
        <?php
            $k++;
        }
        ?>
    <?php
    }
    exit;
}

if (isset($_POST["ajaxGravar"])) {
    $tipo  = $_POST['tipo'];
    $os    = $_POST['os'];
    $comentario_interacao = $_POST['comentario'];

    if($login_fabrica == 127){
        $comentario = $_REQUEST['comentario'];
    }

    if ($tipo == 'Gravar') {
        $sqlFabrica = "SELECT tbl_fabrica.nome
                       FROM tbl_fabrica
                       WHERE tbl_fabrica.fabrica = $login_fabrica";
        $resFabrica = pg_query($con, $sqlFabrica);

        $nome_fabrica    = pg_fetch_result($resFabrica, 0, 'nome');
        $comentario      = trim(htmlentities($comentario_interacao, ENT_QUOTES, 'UTF-8'));
        $remetente_email = base64_decode($email);
        $assunto         = 'FABRICANTE '.strtoupper($nome_fabrica).' AGUARDANDO RETORNO DA O.S ('.$os.')';
        $mensagem        = 'A O.S de Número '.$os.', esta suspensa, por apresentar irregularidades no seu preenchimento, favor providenciar as correções necessárias para liberação da mesma.';
        $mensagem        .= '<br ><br />Motivo: ' . $comentario;

        $header  = 'MIME-Version: 1.0' . "\r\n";
        $header .= 'FROM: helpdesk@telecontrol.com.br' . "\r\n";
        $header .= 'Content-type: text/html; charset=utf-8' . "\r\n";

        pg_query($con, 'BEGIN');

        $sql = "INSERT INTO tbl_os_interacao (
                    programa,
                    os, data, admin, comentario, exigir_resposta
                ) VALUES (
                    '$programa_insert',
                    $os, current_timestamp, $login_admin, '$comentario_interacao', 't'
                )";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            pg_query($con, 'ROLLBACK');
            echo "erro";
        } else {
            pg_query($con, 'COMMIT');
            echo "ok";
        }
    }

    exit;
}

$sql = "SELECT
        tbl_os_interacao.os_interacao,
        to_char(tbl_os_interacao.data,'DD/MM/YYYY HH24:MI') as data,
        tbl_os_interacao.comentario,
        tbl_os_interacao.interno,
        tbl_os.posto,
        tbl_posto_fabrica.contato_email as email,
        tbl_admin.nome_completo
      FROM tbl_os_interacao
      JOIN tbl_os            ON tbl_os.os    = tbl_os_interacao.os
      JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
      LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os_interacao.admin
      WHERE tbl_os_interacao.os = {$os}
      AND tbl_os.fabrica = {$login_fabrica}
      AND tbl_os_interacao.interno IS NOT TRUE
      ORDER BY tbl_os_interacao.os_interacao DESC";
$res  = pg_query($con, $sql);

?>
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
<link type="text/css" rel="stylesheet" media="screen" href="css/tc_css.css" />
<link type="text/css" rel="stylesheet" media="screen" href="css/tooltips.css" />
<link type="text/css" rel="stylesheet" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />
<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
<script>

function gravarInteracao(os,tipo) {
    var comentario = $.trim($("#comentario").val());

    if (comentario.length == 0) {
        alert("Insira uma mensagem para interagir");
    } else {
        $.ajax({
            url: "exibe_interacao_km_os.php",
            type: "POST",
            data: {
                os: os,
                ajaxGravar: true, 
                tipo: tipo,
                comentario: comentario
            },
            complete: function(data){
                data = data.responseText;

                if (data == "erro") {
                    alert("Ocorreu um erro ao gravar interação");
                } else {
                    $(".alert-success").show();
                    $("#gravado").html("Interação gravada com sucesso!");
                }

                $("#comentario").val("");
                refreshInteracoes(os);
            }
        });
    }
}


function refreshInteracoes(os) {
    $.ajax({
        url: "exibe_interacao_km_os.php",
        type: "POST",
        data: {
            os: os,
            ajaxRefresh: true
        },
        complete: function (data) {
            $("#interacoes").html(data.responseText);
        }
    })
}
</script>
<div class="alert alert-success" style="display: none;">
    <h4 id="gravado"></h4>
</div>    
<table class="table table-bordered table-fixed">
    <thead>
        <tr class='titulo_tabela'>
            <th colspan="100%">Interações OS <?= $os ?></th>
        </tr>
        <tr class='titulo_coluna'>
            <th>Nº</th>
            <th>Data</th>
            <th>Mensagem</th>
            <th>Admin</th>
        </tr>
    </thead>
    <?php
    if (pg_num_rows($res) > 0) {
    ?>
            <tbody id="interacoes">
                <?php
                $k = 1;

                while ($result_i = pg_fetch_array($res)) {
                    if ($result_i["interno"] == 't') {
                        $cor = "style='font-family: Arial; font-size: 8pt; font-weight: bold; text-align: left; background: #F3F5CF;'";
                    } else {
                        $cor = "class='conteudo'";
                    }
                    ?>
                    <tr>
                        <td width="25" <?=$cor?> ><?=$k?></td>
                        <td width="90" <?=$cor?> nowrap ><?=$result_i["data"]?></td>
                        <td <?=$cor?> ><?=$result_i["comentario"]?></td>
                        <td <?=$cor?> nowrap ><?=$result_i["nome_completo"]?></td>
                    </tr>
                <?php
                    $k++;
                }
                ?>
            </tbody>
    <?php
    }
    ?>
</table>

<div class="tac">
    <textarea placeholder="Insira aqui o texto da interação" name="comentario" id="comentario" style="width: 400px;"></textarea>
    <br /><br />
</div>
<div class="tac">
    <input type="button" value="Gravar" class="btn" style="cursor:pointer" onclick="gravarInteracao(<?=$os?>, 'Gravar');">
</div>
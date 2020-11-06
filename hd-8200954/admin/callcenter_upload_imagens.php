<?php
#hd 1334569 novo arquivo para upload de imagens do callcenter

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
include 'funcoes.php';

include_once 'class/aws/s3_config.php';

include_once S3CLASS;

$s3 = new AmazonTC('callcenter', (int) $login_fabrica);

header('Content-Type: text/html; charset=iso-8859-1');
$callcenter = $_POST['callcenter'];
$sql = "SELECT  tbl_hd_chamado.status
        FROM    tbl_hd_chamado
        WHERE   tbl_hd_chamado.hd_chamado = $callcenter ";

$res = pg_query($con,$sql);
$status_item = pg_fetch_result($res,0,0);


if ($_POST['deleta_imagens'] == 'true') {
    $files = $_POST['files'];

    if (count($files) > 0) {
        foreach ($files as $key => $file) {
            $s3->deleteObject($file);

            if ($s3->result == false) {
                echo json_encode(array('erro' => 'Erro ao deletar arquivo'));
                break;
            }
            $sql = "INSERT INTO tbl_hd_chamado_item (
                                hd_chamado  ,
                                comentario  ,
                                admin       ,
                                interno     ,
                                status_item
                         ) VALUES (
                                $callcenter                                                    ,
                                'Imagem deletada: $file pelo(a) usuário(a) <b>$login_login</b>',
                                $login_admin                                                   ,
                                't'                                                            ,
                                '$status_item'
                         ) ";

            $res = pg_query($con,$sql);
            $msg_db = pg_last_error($con);
            if(strlen($msg_db) > 0){
                echo json_encode(array('erro' => 'Erro ao deletar arquivo'));
                break;
            }

        }

        $msg_db = pg_last_error($con);
        if(strlen($msg_db) > 0){
            echo json_encode(array('erro' => 'Erro ao fazer o upload do arquivo'));
        }else{
            echo json_encode(array('success' => 'true'));
        }
    } else {
        echo json_encode(array('erro' => 'Nenhum arquivo selecionado para deletar'));
    }

    exit;
}

if ($_POST['anexar_imagem'] == 'true') {
    if($login_fabrica == 74){
        $types      = array('png', 'jpg', 'jpeg', 'bmp', 'pdf','doc','txt', 'zip','wav');
    }else{
        $types      = array('png', 'jpg', 'jpeg', 'bmp', 'pdf','doc','txt');
    }
    
    $file       = $_FILES[key($_FILES)];
    $hd_chamado = $_POST['file_hd_chamado'];
    $i          = $_POST['file_i'];
    $type  = trim(strtolower(preg_replace('/.+\./', '', $file['name'])));

    if (count($_FILES) > 0) {
        if ($file['size'] <= 4718592) {
            if ($type == 'jpeg') {
                $type = 'jpg';
            }

            if (strlen($file['tmp_name']) > 0 && $file['size'] > 0) {
                if (!in_array($type, $types)) {
                    die(json_encode(array('erro' => utf8_encode('Formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, pdf'))));
                } else {
                    $s3->upload("{$hd_chamado}-{$i}", $file, null, null);

                    if ($type != 'pdf') {
                        $file_mini = $s3->getLink("thumb_{$hd_chamado}-{$i}.{$type}", false, null, null);
                    }

                    $file = $s3->getLink("{$hd_chamado}-{$i}.{$type}", false, null, null);
                }
            } else {
                die(json_encode(array('erro' => 'Erro ao fazer o upload do arquivo')));
            }

            if ($s3->result == false) {
                echo json_encode(array('erro' => 'Erro ao fazer o upload do arquivo'));
            } else {
                $sql = "INSERT INTO tbl_hd_chamado_item (
                                    hd_chamado  ,
                                    comentario  ,
                                    admin       ,
                                    interno     ,
                                    status_item
                             ) VALUES (
                                    $callcenter                                                                 ,
                                    'Arquivo anexado: {$hd_chamado}-{$i} pelo(a) usuário(a) <b>$login_login</b>',
                                    $login_admin                                                                ,
                                    't'                                                                         ,
                                    '$status_item'
                             ) ";
                $res = pg_query($con,$sql);
                $msg_db = pg_last_error($con);

                if(strlen($msg_db) > 0){
                    echo json_encode(array('erro' => 'Erro ao fazer o upload do arquivo'));
                }else{
                    echo json_encode(array('file_mini' => $file_mini, 'file' => $file, 'i' => $i, 'type' => $type, 'file_name' => "{$hd_chamado}-{$i}.{$type}"));
                }
            }
        } else {
            echo json_encode(array('erro' => utf8_encode('O arquivo deve ter no máximo 2Mb')));
        }
    } else {
        echo json_encode(array('erro' => 'Nenhum arquivo selecionado'));
    }
    exit;
}

/*include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$admin_privilegios = "call_center";
$layout_menu       = "callcenter";

include 'cabecalho.php';

$config = array();
$config["tamanho"] = 2097152; // Tamanho máximo do arquivo (em bytes)
$config["limite_anexos"] = 10; // Limite máximo de anexos

$callcenter     = $_REQUEST["callcenter"];
$fileApagar     = @$_REQUEST['fileApagar'];
$imagem_nota    = @$_REQUEST['imagem_nota'];

$caminho    = 'callcenter_digitalizados/';

$arquivo = isset($_FILES["foto"]) ? $_FILES["foto"] : FALSE;

$sql = "SELECT  tbl_hd_chamado.status
        FROM    tbl_hd_chamado
        WHERE   tbl_hd_chamado.hd_chamado = $callcenter
";
$res = pg_query($con,$sql);
$status_item = pg_fetch_result($res,0,0);

if(strlen($fileApagar) > 0){
    if(file_exists($caminho.$fileApagar)){
        unlink($caminho.$fileApagar);
        $sql = "BEGIN TRANSACTION";
        $res = pg_query($con, $sql);
        $msg_erro[] = pg_errormessage($con);

        $sql = "INSERT INTO tbl_hd_chamado_item (
                                                hd_chamado  ,
                                                comentario  ,
                                                admin       ,
                                                interno     ,
                                                status_item
                                             ) VALUES (
                                                $callcenter                                                             ,
                                                'Imagem deletada: $nome_arquivo pelo(a) usuário(a) <b>$login_login</b>' ,
                                                $login_admin                                                            ,
                                                't'                                                                     ,
                                                '$status_item'
                                             )
        ";
        $res = pg_query($con,$sql);
        $msg_erro[] = pg_errormessage($con);

        $msg_erro = implode("", $msg_erro);
        if (strlen($msg_erro)) {

            $sql = "ROLLBACK TRANSACTION";
            $res = pg_query($con, $sql);
        } else {

            $sql = "COMMIT TRANSACTION";
            $res = pg_query($con, $sql);
        }
        echo "<script>window.location = '$PHP_SELF?callcenter=$callcenter'</script>";
    }else{
        echo "<script>window.location = '$PHP_SELF?callcenter=$callcenter&imagem_nota=$imagem_nota'</script>";
    }

}

if ($arquivo) {//HD 158465

    #Verifica o mime-type do arquivo
    if (!preg_match("/\/(pjpeg|jpeg|jpg)/", $arquivo["type"])) {

        $msg_erro = "O arquivo deve estar no formato JPG";

    } else {

        if ($arquivo["size"] > $config["tamanho"]) {
            $msg_erro = "Arquivo tem tamanho muito grande! Deve ser de no máximo 2MB. Envie outro arquivo.";
        }

    }

    if (strlen($msg_erro) == 0) {

        $arquivo         = $arquivo["tmp_name"];
        $caminho         = 'callcenter_digitalizados/';
        $nome_arquivo    = $callcenter . ".jpg";
        $arquivo_destino = $caminho.$nome_arquivo;;
        $numero_arquivo  = 0;

        while (file_exists($arquivo_destino)) {

            $numero_arquivo++;
            $nome_arquivo    = $callcenter . "-$numero_arquivo.jpg";
            $arquivo_destino = $caminho.$nome_arquivo;

            if ($numero_arquivo > $config["limite_anexos"]) {
                $msg_erro = 'Limite de anexos é de '.$config["limite_anexos"].' arquivos!';
                break;
            }

        }

        if (strlen($msg_erro) == 0) {
            copy($arquivo, $arquivo_destino);
            $sql = "BEGIN TRANSACTION";
            $res = pg_query($con, $sql);
            $msg_erro[] = pg_errormessage($con);

            $sql = "INSERT INTO tbl_hd_chamado_item (
                                                    hd_chamado  ,
                                                    comentario  ,
                                                    admin       ,
                                                    interno     ,
                                                    status_item
                                                 ) VALUES (
                                                    $callcenter                                                                         ,
                                                    'Imagem inserida como anexo: $nome_arquivo pelo(a) usuário(a) <b>$login_login</b>'  ,
                                                    $login_admin                                                                        ,
                                                    't'                                                                                 ,
                                                    '$status_item'
                                                 )
            ";
            $res = pg_query($con,$sql);
            $msg_erro[] = pg_errormessage($con);

            $msg_erro = implode("", $msg_erro);
            if (strlen($msg_erro) > 0) {

                $sql = "ROLLBACK TRANSACTION";
                $res = pg_query($con, $sql);
                header("location:" . $PHP_SELF);
                die;

            } else {
                $sql = "COMMIT TRANSACTION";
                $res = pg_query($con, $sql);
                #header("location:" . $PHP_SELF . "?callcenter=$callcenter");
                #die;
                $msg_erro = "Arquivo enviado com sucesso!<br><a href='callcenter_interativo_new.php?callcenter=$callcenter'>Voltar ao Callcenter</a>";
                $imagem_nota = "callcenter_digitalizados/" . $nome_arquivo;

            }
            //move_uploaded_file($arquivo, $arquivo_destino);
        }
    }
}

$tamMB = number_format($config["tamanho"]/1024/1024, 2, '.', ',');?>

<style>
    .titulo {
        font-family: Arial;
        font-size: 9pt;
        text-align: center;
        font-weight: bold;
        color: #FFFFFF;
        background: #408BF2;
    }
    .titulo2 {
        font-family: Arial;
        font-size: 12pt;
        text-align: center;
        font-weight: bold;
        color: #FFFFFF;
        background: #408BF2;
    }

    .conteudo {
        font-family: Arial;
        FONT-SIZE: 8pt;
        text-align: left;
    }

    .mesano {
        font-family: Arial;
        FONT-SIZE: 11pt;
    }

    .Tabela{
        border:1px solid #485989;
        font-family: Arial;
        FONT-SIZE: 9pt;
        text-align: left;
    }
    img{
        border: 0px;
    }
    .caixa{
        border:1px solid #666;
        font-family: courier;
    }

    body {
        margin: 0px;
    }

    .msg {
        color: #FF2222;
        text-align: center;
    }
</style>

<form name='frm_relatorio' method='post' enctype="multipart/form-data">
    <table width='700' class='Tabela' align = 'center' cellpadding='5' cellspacing='0' border='0' >
        <tr>
            <td class="msg"><?=$msg_erro?></td>
        </tr>
        <tr>
            <td align='center'>
                Selecione um arquivo para upload (formato jpg, tamanho máximo <?php echo $tamMB; ?>MB): <input type='file' name='foto'>
            </td>
        </tr>
        <tr>
            <td align='center'>
                <input type='submit' name='btn_acao' value='Enviar Arquivo'>
                <input type='hidden' name='callcenter' value='<?=$callcenter?>'>
            </td>
        </tr>
    </table>
</form>

<div align="center">
<?php
if(file_exists($imagem_nota)){
    if ($imagem_nota != "") {
        echo "<img src='$imagem_nota'><br><a href='$imagem_nota'>Download</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href='$PHP_SELF?callcenter=$callcenter&fileApagar=$nome_arquivo&imagem_nota=$imagem_nota'>Apagar Arquivo</a>";
    }
}?>
</div>
*/

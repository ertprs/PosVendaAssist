<?php
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';

if ($_REQUEST['ajax'] && $_REQUEST['action'] == 'gravar') {
    try {
      
        $fabrica  = $_REQUEST['fabrica'];
        $os       = $_REQUEST['os'];
        $formData = $_REQUEST['formData'];
        $tipo     = $_REQUEST['tipo'];
        $pesquisa = $_REQUEST['pesquisa'];
        if (empty($os)) {
            throw new \Exception('Ordem de Serviço não informada');
        } else {
            $sql = "
                SELECT os, hd_chamado 
                FROM tbl_os 
                WHERE fabrica = {$fabrica} 
                AND os = {$os}
            ";
            $resOs = pg_query($con, $sql);
            
            if (!pg_num_rows($resOs)) {
                throw new \Exception('Ordem de Serviço inválida');
            }
        }
        
        if (empty($formData)) {
            throw new \Exception('Erro ao pegar informações do formulário');
        }
        
            $sql_respostas = "SELECT r.resposta 
                            FROM tbl_resposta r
                            JOIN tbl_pesquisa p on (p.pesquisa = r.pesquisa)
                            WHERE r.os = {$os}
                            AND p.categoria = '{$tipo}'";
            
            $res = pg_query($con, $sql_respostas);

            if (pg_num_rows($res) > 0) { 

                $sql = "UPDATE tbl_resposta SET
                        txt_resposta = '{$formData}',
                        sem_resposta = FALSE
                        WHERE os = {$os}";

                $res = pg_query($con, $sql);

            } else { 
                
                $query = "SELECT pesquisa_formulario 
                            FROM tbl_pesquisa_formulario 
                            WHERE pesquisa = {$pesquisa} 
                            LIMIT 1";

               $res = pg_query($con, $query);

               $res = pg_fetch_assoc($res);

               $insert_query = "INSERT INTO tbl_resposta (os, txt_resposta, pesquisa, sem_resposta, pesquisa_formulario) 
                                VALUES ({$os}, '{$formData}', {$pesquisa}, 'f', {$res['pesquisa_formulario']})";
                
                $tata = pg_query($con, $insert_query);
            }

        if (strlen(pg_last_error()) > 0) {
            throw new \Exception('Erro ao gravar resposta');
        }

        if ($fabrica == '138') {
            $hd_chamado = pg_fetch_result($resOs, 0, 'hd_chamado');
            
            if ($hd_chamado) {
                $sql = "INSERT INTO tbl_hd_chamado_item (
                    hd_chamado,
                    data,
                    comentario,
                    status_item
                ) VALUES (
                    $hd_chamado,
                    CURRENT_TIMESTAMP,
                    'Fechado automaticamente após resposta na pesquisa de satisfação.',
                    'Resolvido'
                )";
                $query = pg_query($con, $sql);

                $sqlUpdate = "UPDATE tbl_hd_chamado SET status = 'Resolvido', resolvido=now() WHERE hd_chamado = $hd_chamado";
                $queryUpdate = pg_query($con, $sqlUpdate);
            }
        }

        exit(json_encode(array('success' => true)));
    } catch(\Exception $e) {
        exit(json_encode(array('error' => utf8_encode($e->getMessage()))));
    }
}

$token = $_GET['token'];
$os    = $_GET['os'];
$tipo  = $_GET['tipo'];

if (empty($token)) {
    http_response_code(401);
    exit;
}
if (isset($tipo) && $tipo == "email") {
    $condCategoria = " AND p.categoria='os_email'";
} else {
    $condCategoria = " AND p.categoria='os'";
}
$respondida = false;
$erro_pesquisa = false;
$sql = "
    SELECT o.fabrica, 
           o.os, 
           pf.formulario, 
           p.descricao AS titulo, 
           o.sua_os, 
           o.consumidor_nome, 
           r.txt_resposta, 
           pd.referencia, 
           pd.descricao,
           p.pesquisa
    FROM tbl_os o
    INNER JOIN tbl_resposta r ON r.os = o.os
    INNER JOIN tbl_pesquisa_formulario pf ON pf.pesquisa_formulario = r.pesquisa_formulario
    INNER JOIN tbl_pesquisa p ON p.pesquisa = r.pesquisa
    INNER JOIN tbl_os_produto op ON op.os = o.os
    INNER JOIN tbl_produto pd ON pd.produto = op.produto
    WHERE o.os = {$os}
    {$condCategoria}
    AND r.sem_resposta IS FALSE
";

$res = pg_query($con, $sql);
if (pg_num_rows($res) > 0) {

    $res = pg_fetch_assoc($res);
    $respondida = true;

    $res = pg_query($con, $sql);

    if (!pg_num_rows($res)) {
        $erro_pesquisa = true;
    }

    $res = pg_fetch_assoc($res);
    
    $fabrica    = $res['fabrica'];
    $formulario = str_replace("&nbsp;", " ", $res['formulario']);
    $titulo     = str_replace("&nbsp;", " ", $res['titulo']);
    $sua_os     = $res['sua_os'];
    $consumidor_nome    = $res['consumidor_nome'];
    $produto_referencia = $res['referencia'];
    $produto_descricao  = $res['descricao'];
    $pesquisa           = $res['pesquisa'];
    $resposta = str_replace("&nbsp;", " ", $res['txt_resposta']);

} else {

    $tipo_pesquisa = "os_email";

    $sql_os = "SELECT o.os, o.sua_os, o.consumidor_nome, pd.referencia, pd.descricao, o.fabrica
               FROM tbl_os o 
               JOIN tbl_os_produto op ON op.os = o.os 
               JOIN tbl_produto pd ON pd.produto = op.produto 
               WHERE o.os = $os";

    $res_os = pg_query($con, $sql_os);
    $res_os = pg_fetch_assoc($res_os);

    $sql = "SELECT pf.formulario, p.descricao AS titulo, p.categoria, p.pesquisa
            FROM tbl_pesquisa p
            JOIN tbl_pesquisa_formulario pf ON pf.pesquisa = p.pesquisa
            WHERE p.fabrica = {$res_os['fabrica']} AND p.categoria = 'os_email' AND p.ativo = 't' AND pf.ativo = 't'";

    $res_pesquisa = pg_query($con, $sql);

    if (pg_num_rows($res_pesquisa) > 0) {

        $res_pesquisa = pg_fetch_assoc($res_pesquisa);

        $fabrica            = $res_os['fabrica'];
        $formulario         = str_replace("&nbsp;", " ", $res_pesquisa['formulario']);
        $titulo             = str_replace("&nbsp;", " ", $res_pesquisa['titulo']);
        $sua_os             = $res_os['sua_os'];
        $consumidor_nome    = $res_os['consumidor_nome'];
        $produto_referencia = $res_os['referencia'];
        $produto_descricao  = $res_os['descricao'];
        $pesquisa           = $res_pesquisa['pesquisa'];
        $resposta           = '';

    } else {

        $erro_pesquisa = true;
    }
}

$imagensLogo = include('../logos.inc.php');
$url_logo = '../logos/'.getFabricaLogo($fabrica, $imagensLogo);

if (sha1($fabrica.$os) != $token) {
    $erro_pesquisa = true;
}

?>

<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Pesquisa de Satisfação</title>
        
        <?php
        $plugins = array(
            'jquery3',
            'bootstrap3',
            'font_awesome',
            'select2',
            'price_format',
            'rateYo',
            'checkradios',
            'telecontrol-form-builder'
        );
        include 'plugin_loader.php';
        ?>
        
        <style>
        
        html {
            overflow: auto;
        }
        
        </style>
    </head>
    <body>

        <?php 
        if ($erro_pesquisa) {
            echo "<div class='container-fluid'><br /><br /><div class='alert alert-warning'>Nenhuma pesquisa encontrada</div></div>";
        } else {
        ?>
        <div class="container-fluid">
            <br /><br />
        
            <div class='build-wrap'></div>
            <form class='render-wrap'></form>
            
            <br /><br />
        </div>
        
        <script>
        window.fbEditing = false;
        window.fbLogo    = '<?=$url_logo?>';
        window.fbTitle   = '<?=$titulo?>';
        window.fbData    = '<?=$formulario?>';
        var respondida   = '<?=$respondida?>';
    
        window.fbCallback = function(data) {
            return new Promise(function(resolve, reject) {
                let errors = [];
                let valid = fbApi.validateRequiredFields(data);
                
                if (valid !== true) {
                    reject(valid);
                } else {
                    (new Promise(function(resolve, reject) {
                        $.ajax({
                            url: window.location,
                            type: 'post',
                            data: {
                                ajax: true,
                                action: 'gravar',
                                formData: JSON.stringify(fbApi.getFormData()),
                                os: <?=$os?>,
                                pesquisa: <?=$pesquisa?>,
                                fabrica: <?=$fabrica?>
                            },
                            timeout: 60000
                        }).fail(function(res) {
                            reject({
                                messages: ['Erro ao gravar pesquisa de satisfação']
                            });
                        }).done(function(res, req){
                            if (req == 'success') {
                                res = JSON.parse(res);
                                
                                if (res.error) {
                                    reject({
                                        messages: [res.error]
                                    });
                                } else {
                                    resolve('Pesquisa de satisfação gravada com sucesso');
                                }
                            } else {
                                reject({
                                    messages: ['Erro ao gravar pesquisa de satisfação']
                                });
                            }
                        });
                    })).then(
                        function(success) {
                            resolve(success);
                        },
                        function(error) {
                            reject(error);
                        }
                    );
                }
            }); 
        }
        if (respondida) {
            window.fbNoActions = true;
        }
        fbInit().then(function() {
            $('.page-header').after('\
                <div class=\'page-header\' style=\'margin-top: unset;\'>\
                    <ul>\
                        <li><h4>Ordem de Serviço: <?=$sua_os?></h4></li>\
                        <li><h4><?=$consumidor_nome?></h4></li>\
                        <li><h4><?=$produto_referencia?> - <?=$produto_descricao?></h4></li>\
                    </ul>\
                </div>\
            ');
            if (respondida) {
                
                fbApi.setFormData('<?php echo utf8_decode($resposta);?>');
                fbApi.setFormReadonly();
            }
        });
        </script>
        <?php }?>
    </body>
</html>

<?php
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';

if (isset($_REQUEST['ajax']) && $_REQUEST['action'] == 'gravar') {
    try {
        $fabrica  = $_REQUEST['fabrica'];
        $pesquisa = $_REQUEST['pesquisa'];
        $formData = $_REQUEST['formData'];
        $posto    = $_REQUEST['posto'];
        $pesquisa_formulario = $_REQUEST['pesquisa_formulario'];
        
        if (empty($formData)) {
            throw new \Exception('Erro ao pegar informações do formulário');
        }

        if (empty($pesquisa)) {
            throw new \Exception('Pesquisa não informada');
        }
 
        $sql = "
            SELECT resposta 
            FROM tbl_resposta 
            WHERE resposta = {$pesquisa}
            AND sem_resposta IS TRUE
        ";
        $res = pg_query($con, $sql);
            
        if (!pg_num_rows($res)) {                      

            $insert = "INSERT INTO tbl_resposta(txt_resposta,sem_resposta,posto,pesquisa,pesquisa_formulario) 
                       VALUES ('{$formData}', 'f', $posto, $pesquisa, $pesquisa_formulario)";

            $res = pg_query($con, $insert);

        } else { 

            $sql = "UPDATE tbl_resposta 
                    SET txt_resposta = '{$formData}',
                        sem_resposta = FALSE
                    WHERE resposta   = {$pesquisa}
            ";
            $res = pg_query($con, $sql);
        }
        
        if (strlen(pg_last_error()) > 0) {

            throw new \Exception('Erro ao gravar resposta');
        }
        
        exit(json_encode(array('success' => true)));

    } catch(\Exception $e) {

        exit(json_encode(array('error' => utf8_encode($e->getMessage()))));
    }
}

$token        = $_GET['token'];
$pesquisa     = $_GET['pesquisa'];
$fabricaParam = $_GET['fabrica'];
$postoParam   = $_GET['posto'];

if (empty($token)) {
    http_response_code(401);
    exit;
}

if (isset($_GET['pesquisa_posto'])) { 

    $sql = "SELECT p.descricao AS titulo, pf.formulario, pf.pesquisa_formulario
            FROM tbl_pesquisa p
            INNER JOIN tbl_pesquisa_formulario pf ON pf.pesquisa = p.pesquisa
            WHERE p.pesquisa = $pesquisa
            AND pf.ativo = 't'";

    $res = pg_query($con, $sql);

    $sqlPosto = "SELECT pf.codigo_posto, p.nome
                 FROM tbl_posto_fabrica pf
                 JOIN tbl_posto p ON p.posto = pf.posto
                 WHERE pf.posto = $postoParam
                 AND   pf.fabrica = $fabricaParam";

    $resPosto = pg_query($con, $sqlPosto);

    if (!pg_num_rows($res) || !pg_num_rows($resPosto)) {

        http_response_code(401);
        exit;
    }

    $res      = pg_fetch_assoc($res);
    $resPosto = pg_fetch_assoc($resPosto);

    $fabrica    = $fabricaParam;
    $posto      = $postoParam;

    $pesquisa_formulario = $res['pesquisa_formulario'];
    $formulario = str_replace("&nbsp;", " ", $res['formulario']);
    $titulo     = str_replace("&nbsp;", " ", $res['titulo']);

    $posto_codigo       = $resPosto['codigo_posto'];
    $posto_razao_social = $resPosto['nome'];

} else { 

    $sql = "
        SELECT r.pesquisa_formulario, r.resposta, r.posto, p.fabrica, pf.formulario, p.descricao AS titulo, psf.codigo_posto, pst.nome
        FROM tbl_resposta r
        INNER JOIN tbl_pesquisa_formulario pf ON pf.pesquisa_formulario = r.pesquisa_formulario
        INNER JOIN tbl_pesquisa p ON p.pesquisa = pf.pesquisa
        INNER JOIN tbl_posto_fabrica psf ON psf.posto = r.posto AND psf.fabrica = p.fabrica
        INNER JOIN tbl_posto pst ON pst.posto = psf.posto
        WHERE r.pesquisa = {$pesquisa}
        AND r.sem_resposta IS TRUE
    "; 

    $res = pg_query($con, $sql);

    if (!pg_num_rows($res)) {
        http_response_code(401);
        exit;
    }

    $res = pg_fetch_assoc($res);

    $fabrica    = $res['fabrica'];
    $posto      = $res['posto'];
    $pesquisa_formulario = $res['pesquisa_formulario'];
    $formulario = str_replace("&nbsp;", " ", $res['formulario']);
    $titulo     = str_replace("&nbsp;", " ", $res['titulo']);
    $posto_codigo = $res['codigo_posto'];
    $posto_razao_social = $res['nome'];
}

$imagensLogo = include('../logos.inc.php');
$url_logo = '../logos/'.getFabricaLogo($fabrica, $imagensLogo);

if (sha1($fabrica.$posto.$pesquisa) != $token) {
    http_response_code(401);
    exit;
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
                                pesquisa: <?=$pesquisa?>,
                                pesquisa_formulario: <?=$pesquisa_formulario?>,
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

                                    window.parent.location.reload();
                                    window.parent.Shadowbox.close();
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
        
        fbInit().then(function() {
            $('.page-header').after('\
                <div class=\'page-header\' style=\'margin-top: unset;\'>\
                    <ul>\
                        <li><h4><?=$posto_codigo?> - <?=$posto_razao_social?></h4></li>\
                    </ul>\
                </div>\
            ');  
        });
        </script>
    </body>
</html>
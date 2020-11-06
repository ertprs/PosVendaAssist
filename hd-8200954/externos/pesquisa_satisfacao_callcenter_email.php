<?php
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';

if ($_REQUEST['ajax'] && $_REQUEST['action'] == 'gravar') {

    try {

        $fabrica    = $_REQUEST['fabrica'];
        $callcenter = $_REQUEST['callcenter'];
        $tipo       = $_REQUEST['tipo'];
        $formData   = $_REQUEST['formData'];
        if (isset($tipo) && $tipo == "email") {
            $condCategoria = " AND tbl_pesquisa.categoria='callcenter_email'";
        } else {
            $condCategoria = " AND tbl_pesquisa.categoria='callcenter'";
        }
        if (empty($callcenter)) {
            throw new \Exception('Atendimento não informado');
        } else {
            $sql = "
                SELECT hd_chamado 
                FROM tbl_hd_chamado
                WHERE fabrica = {$fabrica} 
                AND hd_chamado = {$callcenter}
            ";
            $res = pg_query($con, $sql);
            
            if (!pg_num_rows($res)) {
                throw new \Exception('Atendimento inválido');
            }
        }
        
        if (empty($formData)) {
            throw new \Exception('Erro ao pegar informações do formulário');
        }
        $sqlX = "SELECT tbl_pesquisa.pesquisa 
                  FROM tbl_pesquisa
                  JOIN tbl_resposta USING(pesquisa)
                 WHERE tbl_pesquisa.fabrica = {$fabrica} 
                   AND tbl_resposta.hd_chamado = {$callcenter}
                       {$condCategoria}";
        $resX = pg_query($con, $sqlX);

        if (pg_num_rows($resX) > 0) {
            $xpesquisa = pg_fetch_result($resX, 0, 'pesquisa');

            $sql = " UPDATE tbl_resposta 
                        SET txt_resposta = '{$formData}',
                            sem_resposta = FALSE
                      WHERE hd_chamado = {$callcenter} 
                        AND pesquisa = {$xpesquisa}";
                   
            $res = pg_query($con, $sql);
            
            if (strlen(pg_last_error()) > 0) {
                throw new \Exception('Erro ao gravar resposta');
            }

        } else {
            throw new \Exception('Pesquisa não encontrada');
        }
        
        exit(json_encode(array('success' => true)));

    } catch(\Exception $e) {
        
        exit(json_encode(array('error' => utf8_encode($e->getMessage()))));
    }
}

$token      = $_GET['token'];
$callcenter = $_GET['callcenter'];
$tipo       = $_GET['tipo'];

if (empty($token)) {
    http_response_code(401);
    exit;
}

$erro_pesquisa = false;
$respondida = false;

$sql_callcenter = "SELECT   hd.hd_chamado, 
                            hd.fabrica, 
                            hde.nome consumidor_nome
                   FROM tbl_hd_chamado hd 
                   LEFT JOIN tbl_hd_chamado_extra hde ON hde.hd_chamado = hd.hd_chamado
                   WHERE hd.hd_chamado = {$callcenter}";

$res_callcenter = pg_query($con, $sql_callcenter);
$res_callcenter = pg_fetch_assoc($res_callcenter);

if (isset($tipo) && $tipo == "email") {
    
    $categoria = "callcenter_email";

} else { 

    $categoria = "callcenter";
}

$sql = "SELECT  pf.formulario, 
                p.descricao AS titulo, 
                p.categoria, 
                p.pesquisa
        FROM tbl_pesquisa p
        JOIN tbl_pesquisa_formulario pf ON pf.pesquisa = p.pesquisa
        WHERE p.fabrica = " . $res_callcenter['fabrica'] ."
        AND p.categoria = 'callcenter_email' 
        AND p.ativo = 't'";

$res_pesquisa = pg_query($con, $sql);

if (pg_num_rows($res_pesquisa) > 0) {

        $res_pesquisa = pg_fetch_assoc($res_pesquisa);

        $fabrica            = $res_callcenter['fabrica'];
        $formulario         = str_replace("&nbsp;", " ", $res_pesquisa['formulario']);
        $titulo             = str_replace("&nbsp;", " ", $res_pesquisa['titulo']);
        $hd_chamado         = $res_callcenter['hd_chamado'];
        $consumidor_nome    = $res_callcenter['consumidor_nome'];
        $produto_referencia = $res_callcenter['referencia'];
        $produto_descricao  = $res_callcenter['descricao'];
        $pesquisa           = $res_pesquisa['pesquisa'];
        $resposta           = '';

} else {

    $erro_pesquisa = true;
}


$imagensLogo = include('../logos.inc.php');
$url_logo = '../logos/'.getFabricaLogo($fabrica, $imagensLogo);

if (sha1($fabrica.$callcenter) != $token) {
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
                                    callcenter: '<?=$callcenter?>',
                                    fabrica: '<?=$fabrica?>'
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
                            <li><h4>Protocolo de Atendimento: <?=$callcenter?></h4></li>\
                            <li><h4><?=$consumidor_nome?></h4></li>\
                            <li><h4><?=$produto_referencia?> - <?=$produto_descricao?></h4></li>\
                        </ul>\
                    </div>\
                ');
                if (respondida) {
                    
                    fbApi.setFormData('<?php echo $resposta;?>');
                    fbApi.setFormReadonly();
                }
            });
        </script>
    </body>
</html>
<?php
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';

if ($_REQUEST['ajax'] && $_REQUEST['action'] == 'enviar_email') {

    $callcenter = $_REQUEST['callcenter'];
    $url = $_SERVER["REQUEST_URI"];
    $url = explode("?",$url);
    $url = $url[0];
    
    $redirect = "php " . __DIR__ . "/../rotinas/telecontrol/envia-pesquisa-satisfacao.php envio_manual_cadence_callcenter $callcenter";
    
    $ret = system($redirect, $ret);
    
    exit;
}

if ($_REQUEST['ajax'] && $_REQUEST['action'] == 'gravar') {
    try {
        $fabrica    = $_REQUEST['fabrica'];
        $callcenter = $_REQUEST['callcenter'];
        $tipo       = $_REQUEST['tipo'];
        $formData   = $_REQUEST['formData'];
        $pesquisa_formulario = $_REQUEST['pesquisa_formulario'];
        
        $condCategoria = "callcenter";
        
        if (isset($tipo) && $tipo == "email") {
            $condCategoria = "callcenter_email";
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
                   AND tbl_pesquisa.categoria  = '{$condCategoria}'";
        $resX = pg_query($con, $sqlX);
    
        $xformData = utf8_decode($formData); 

        if (pg_num_rows($resX) > 0) {
            
            $xpesquisa = pg_fetch_result($resX, 0, 'pesquisa');

            $sql = " UPDATE tbl_resposta 
                        SET txt_resposta = '{$formData}',
                            sem_resposta = FALSE
                      WHERE hd_chamado = {$callcenter} 
                        AND pesquisa = {$xpesquisa}";
                   
            $res = pg_query($con, $sql);
        
        } else {

            $query = "SELECT tbl_pesquisa.pesquisa 
                      FROM tbl_pesquisa 
                      WHERE tbl_pesquisa.fabrica = 35 
                      AND tbl_pesquisa.categoria  = '{$condCategoria}'";

            $resX = pg_query($con, $query);

            $xpesquisa = pg_fetch_result($resX, 0, 'pesquisa');

            $sql = " INSERT INTO tbl_resposta 
                    (txt_resposta, sem_resposta, hd_chamado, pesquisa, pesquisa_formulario)
                    VALUES 
                    ('{$xformData}', 'f', $callcenter, $xpesquisa, $pesquisa_formulario)";

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

$token      = $_GET['token'];
$callcenter = $_GET['callcenter'];
$tipo       = $_GET['tipo'];
if (isset($tipo) && $tipo == "email") {
    $condCategoria = " AND p.categoria='callcenter_email'";
} else {
    $condCategoria = " AND p.categoria='callcenter'";
}
if (empty($token)) {
    http_response_code(401);
    exit;
}
$erro_pesquisa = false;
$respondida = false;

$sql = "SELECT hd.fabrica, 
               hd.hd_chamado, 
               pf.formulario, 
               p.pesquisa,
               p.categoria, 
               r.txt_resposta, 
               r.pesquisa_formulario, 
               p.descricao AS titulo, 
               hde.nome AS consumidor_nome, 
               pd.referencia, 
               pf.versao,
               pd.descricao
          FROM tbl_hd_chamado hd
          JOIN tbl_resposta r ON r.hd_chamado = hd.hd_chamado
          JOIN tbl_pesquisa p ON p.pesquisa = r.pesquisa
          JOIN tbl_pesquisa_formulario pf ON pf.pesquisa = p.pesquisa AND pf.ativo IS TRUE
          JOIN tbl_hd_chamado_extra hde ON hde.hd_chamado = hd.hd_chamado
          JOIN tbl_produto pd ON pd.produto = hde.produto
         WHERE hd.hd_chamado = {$callcenter}
           AND r.sem_resposta IS FALSE
               {$condCategoria}";
$res = pg_query($con, $sql);
if (pg_num_rows($res) > 0) {

    $res = pg_fetch_assoc($res);
    $respondida = true;

    $fabrica            = $res['fabrica'];
    $formulario         = str_replace("&nbsp;", " ", $res['formulario']);
    $titulo             = str_replace("&nbsp;", " ", $res['titulo']);
    $hd_chamado         = $res['hd_chamado'];
    $consumidor_nome    = $res['consumidor_nome'];
    $produto_referencia = $res['referencia'];
    $produto_descricao  = $res['descricao'];
    $resposta           = str_replace("&nbsp;", " ", $res['txt_resposta']);
    $categoria          = $res['categoria']; 
    
    if ($categoria == "callcenter_email") {
    
        $resposta = utf8_decode($resposta);
    }

} else {

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

    $sql = "SELECT  pf.pesquisa_formulario,
                    pf.formulario, 
                    p.descricao AS titulo, 
                    p.categoria, 
                    p.pesquisa,
                    r.txt_resposta
            FROM tbl_pesquisa p
            JOIN tbl_pesquisa_formulario pf ON pf.pesquisa = p.pesquisa
            LEFT JOIN tbl_resposta r ON r.pesquisa = p.pesquisa AND r.hd_chamado = $callcenter
            WHERE p.fabrica = " . $res_callcenter['fabrica'] ."
            AND p.categoria = '" . $categoria . "' 
            AND p.ativo = 't'";

    $res_pesquisa = pg_query($con, $sql);

    if (pg_num_rows($res_pesquisa) > 0) {

        if (isset($tipo) && $tipo == "email") {

            $pesquisaCadastrada = True;

        } else { 

            $res_pesquisa = pg_fetch_assoc($res_pesquisa);

            $fabrica             = $res_callcenter['fabrica'];
            $formulario          = str_replace("&nbsp;", " ", $res_pesquisa['formulario']);
            $titulo              = str_replace("&nbsp;", " ", $res_pesquisa['titulo']);
            $hd_chamado          = $res_callcenter['hd_chamado'];
            $consumidor_nome     = $res_callcenter['consumidor_nome'];
            $produto_referencia  = $res_callcenter['referencia'];
            $produto_descricao   = $res_callcenter['descricao'];
            $pesquisa            = $res_pesquisa['pesquisa'];
            $pesquisa_formulario = $res_pesquisa['pesquisa_formulario'];
            $resposta            = $res_pesquisa['txt_resposta'];
            #$resposta            = utf8_decode($resposta);
        }

    } else {

        $erro_pesquisa = true;
    }
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
        <script type="text/javascript">
            $(function () { 
                $("#enviar_email").click(function () {
                    $.ajax({
                        url: window.location,
                        type: 'post',
                        dataType : 'json',
                        data: {
                            ajax: true,
                            callcenter: <?=$callcenter?>,
                            action: 'enviar_email'
                        },
                        beforeSend: function() {
                            $("#loading_enviar_email").show();
                            $("#mensagem").hide();
                        }
                    }).done(function(res) {
       
                        $("#loading_enviar_email").hide();
                        $("#mensagem").html("");

                        if (res.erro) {
                            message = "<div class='container-fluid'><br /><br /><div class='alert alert-danger'>Erro ao enviar Pesquisa de satisfação </div></div>";
                        } else {
                            message = "<div class='container-fluid'><br /><br /><div class='alert alert-success'>Pesquisa de satisfação enviada com sucesso</div></div>";
                        }
                        
                        $("#mensagem").append(message);

                        $("#mensagem").show();
                    });
                  
                });
            });

        </script>
    </head>
        
    <body>
        <?php 
        if ($erro_pesquisa) {
                       
            if ($pesquisaCadastrada) { ?>
                <br><br>
                <div id="loading_enviar_email" style="display: none; padding: 20% 45%; text-align: center; height:56; width: 40px;">
                    <img src="../imagens/loading_img.gif"/>
                </div>
                <div id="mensagem"> 
                    <div class='container-fluid'>
                        <div class='alert alert-warning' style="text-align: center;">
                            <b>Pesquisa de Satisfação não respondida</b><br><br><br> 
                            <p>Ainda é possível enviar um novo e-mail com a pesquisa de satisfação.</p>
                            <br><br>
                            <a id="enviar_email" style="cursor: pointer;">
                                <button class="btn btn-warning">Enviar</button>
                            </a>
                        </div>
                    </div>
                </div>
            <?php } else { 

                 echo "<div class='container-fluid'><br /><br /><div class='alert alert-warning'>Nenhuma pesquisa encontrada</div></div>";
            } 
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
                                    callcenter: '<?=$callcenter?>',
                                    fabrica: '<?=$fabrica?>',
                                    pesquisa_formulario : '<?= $pesquisa_formulario ?>'
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
        <?php } ?>
    </body>
</html>
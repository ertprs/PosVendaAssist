<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

if ($areaAdmin === true) {
    include __DIR__.'/autentica_admin.php';
} else {
    include __DIR__.'/../autentica_usuario.php';
}

if ($_REQUEST['ajax'] && $_REQUEST['action'] == 'gravarLaudoTecnico') {
    try {
        $begin = false;
        
        $os       = $_REQUEST['os'];
        $form     = $_REQUEST['form'];
        $formData = $_REQUEST['formData'];
        
        if (empty($os)) {
            throw new \Exception('Ordem de Serviço não informada');
        } else {
            $sql = "
                SELECT os 
                FROM tbl_os 
                WHERE fabrica = {$login_fabrica} 
                AND posto = {$login_posto} 
                AND os = {$os}
            ";
            $res = pg_query($con, $sql);
            
            if (!pg_num_rows($res)) {
                throw new \Exception('Ordem de Serviço inválida');
            }
            
            if (in_array($login_fabrica, array(175))) {
                $fora_garantia = false;
                
                $sql = "
                    SELECT o.tipo_atendimento
                    FROM tbl_os o
                    INNER JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = {$login_fabrica}
                    WHERE o.os = {$os}
                    AND ta.fora_garantia IS TRUE
                ";
                $res = pg_query($con, $sql);
                
                if (strlen(pg_last_error()) > 0) {
                    throw new \Exception('Erro ao verificar tipo de atendimento');
                }
                
                if (pg_num_rows($res) > 0) {
                    $fora_garantia = true;
                } else {
                    $sql = "
                        SELECT auditoria_status
                        FROM tbl_auditoria_status
                        WHERE fabricante IS TRUE
                    ";
                    $res = pg_query($con, $sql);
                    
                    if (!pg_num_rows($res)) {
                        throw new \Exception('Erro ao buscar auditoria');
                    }
                    
                    $auditoria_status = pg_fetch_result($res, 0, 'auditoria_status');
                }
            }
        }
        
        if (empty($form) || empty($formData)) {
            throw new \Exception('Erro ao pegar informações do formulário');
        }
        
        $begin = true;
        pg_query($con, 'BEGIN');

        $arrayFormData = json_decode($formData, true);

        foreach($arrayFormData as $campo => $ferramenta){
            if (strpos($campo, '-tool') !== false && strlen($ferramenta) > 0) {
                $sqlVerificaValidade = "SELECT tbl_posto_ferramenta.posto_ferramenta
                                        FROM tbl_posto_ferramenta
                                        WHERE tbl_posto_ferramenta.posto = {$login_posto}
                                        AND tbl_posto_ferramenta.posto_ferramenta = {$ferramenta}
                                        AND tbl_posto_ferramenta.validade_certificado >= current_date
                                        ";
                $resVerificaValidade = pg_query($con, $sqlVerificaValidade);

                if (pg_num_rows($resVerificaValidade) == 0) {
                    throw new \Exception('Ferramenta fora da validade do certificado');
                }

            }
        }

        
        if (in_array($login_fabrica, array(175))) {

            $sql = "
                INSERT INTO tbl_laudo_tecnico_os
                (fabrica, os, titulo, observacao, ordem)
                VALUES
                ({$login_fabrica}, {$os}, E'{$form}', E'{$formData}',{$login_unico})
            ";
            $res = pg_query($con, $sql);
            
            if (strlen(pg_last_error()) > 0) {
                throw new \Exception('Erro ao gravar laudo técnico'.pg_last_error());
            }
            $campoTecnicoPreenchimentoLaudo = ", laudo_tecnico_numerico = {$login_unico}";
        
            $sql = "
                UPDATE tbl_os SET
                    data_conserto = CURRENT_TIMESTAMP
                    {$campoTecnicoPreenchimentoLaudo}
                WHERE os = {$os}
            ";
            $res = pg_query($con, $sql);
            
            if (strlen(pg_last_error()) > 0 || pg_affected_rows($res) > 1) {
                throw new \Exception('Erro ao gravar laudo técnico');
            }

            if ($fora_garantia === false) {
                $sql = "
                    INSERT INTO tbl_auditoria_os
                    (os, auditoria_status, observacao)
                    VALUES
                    ({$os}, {$auditoria_status}, 'Auditoria de Fechamento')
                ";
                $res = pg_query($con, $sql);
                
                if (strlen(pg_last_error()) > 0) {
                    throw new \Exception('Erro ao gravar auditoria');
                }
                
                $sql = "
                    UPDATE tbl_os SET
                        status_checkpoint = 14
                    WHERE os = {$os}
                ";
                $res = pg_query($con, $sql);
                
                if (strlen(pg_last_error()) > 0 && pg_affected_rows($res) > 1) {
                    throw new \Exception('Erro ao atualizar status');
                }
            } else {
                include_once "classes/Posvenda/Fabricas/_{$login_fabrica}/Os.php";
                $className = '\\Posvenda\\Fabricas\\_' . $login_fabrica . '\\Os';
                $classOs = new $className($login_fabrica, $os, $con);
                $classOs->finaliza($con);
            }
        } else {

            $sql = "
                INSERT INTO tbl_laudo_tecnico_os
                (fabrica, os, titulo, observacao)
                VALUES
                ({$login_fabrica}, {$os}, E'{$form}', E'{$formData}')
            ";
            $res = pg_query($con, $sql);
            
            if (strlen(pg_last_error()) > 0) {
                throw new \Exception('Erro ao gravar laudo técnico'.pg_last_error());
            }            
        }
        
        pg_query($con, 'COMMIT');
        exit(json_encode(array('success' => true)));
    } catch(\Exception $e) {
        if ($begin === true ){
            pg_query($con, "ROLLBACK");
        }
        exit(json_encode(array('error' => utf8_encode($e->getMessage()))));
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Edição do Laudo Técnico</title>
        
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
    
    <?php
    if ($_GET['readonly']) {
    ?>
        <body class='form-rendered'>
    <?php  
    } else {
    ?>
        <body>
    <?php  
    }
    ?>  
        <div class="container-fluid">
            <br /><br />
        
            <div class='build-wrap'></div>
            <form class='render-wrap'></form>
            
            <br /><br />
        </div>
        
        <script>
            
        <?php
        if (in_array($login_fabrica, array(175))) {
            if (!$_GET["readonly"]) {
                $whereAtivo = "AND ativo IS TRUE";
            }
            
            $sqlGrupoFerramenta = "
                SELECT * FROM tbl_grupo_ferramenta WHERE fabrica = $login_fabrica {$whereAtivo} ORDER BY descricao ASC
            ";
            $resGrupoFerramenta = pg_query($con, $sqlGrupoFerramenta);
            
            if (pg_num_rows($resGrupoFerramenta) > 0) {
                $grupo_ferramenta = array();
                
                while ($row = pg_fetch_object($resGrupoFerramenta)) {
                    $grupo_ferramenta[$row->grupo_ferramenta] = utf8_encode($row->descricao);
                }
                
                $grupo_ferramenta = json_encode($grupo_ferramenta);
                ?>

                window.fbOptionsTools = <?=$grupo_ferramenta?>;
                window.fbOptionsTools[''] = 'Nenhuma';
                
                window.fbNumberAttrs = {
                    tools: {
                        type: 'subtype',
                        label: 'Ferramentas',
                        options: window.fbOptionsTools
                    }
                };
                <?php
                if ((!$areaAdmin && !empty($login_posto)) || ($areaAdmin && $_GET['readonly'])) {
                    if (!$_GET["readonly"]) {
                        $whereAtivo               = "AND ativo IS TRUE";
                        $whereAprovado            = "AND aprovado IS NOT NULL";
                        $whereValidadeCertificado = "AND validade_certificado >= CURRENT_DATE";
                    }
                    
                    if (!$areaAdmin) {
                        $wherePosto = "AND posto = {$login_posto}";
                    } else {
                        $sqlPosto = "
                            SELECT posto FROM tbl_os WHERE os = {$os} AND fabrica = {$login_fabrica}
                        ";
                        $resPosto = pg_query($con, $sqlPosto);
                        
                        $posto = pg_fetch_result($resPosto, 0, 'posto');
                        
                        $wherePosto = "AND posto = {$posto}";
                    }
                    
                    $sqlFerramentas = "
                        SELECT posto_ferramenta, descricao, modelo, fabricante, grupo_ferramenta
                        FROM tbl_posto_ferramenta 
                        WHERE fabrica = {$login_fabrica}
                        {$wherePosto}
                        {$whereAtivo}
                        {$whereAprovado}
                        {$whereValidadeCertificado}
                    ";
                    $resFerramentas = pg_query($con, $sqlFerramentas);
                    
                    if (pg_num_rows($resFerramentas) > 0) {
                        $ferramentas = array();
                        
                        while ($row = pg_fetch_object($resFerramentas)) {
                            $ferramentas[$row->grupo_ferramenta][] = array(
                                'value' => $row->posto_ferramenta,
                                'text'  => utf8_encode("{$row->modelo} - {$row->descricao} ({$row->fabricante})")
                            );
                        }
                        
                        $ferramentas = json_encode($ferramentas);
                        ?>
                        window.fbTools = <?=$ferramentas?>;
                    <?php
                    }
                }
            }
            ?>
            
            window.fbDisabledAttrs = [];
            window.fbRoles = {
                certificado_calibracao: 'Certificado de Calibração'
            };
        <?php
        }
        ?>
        
        window.fbEditing = true;
        
        fbInit();
        
        window.fbLogo;
        window.fbTitle;
        window.fbNoActions;
        
        window.addEventListener('message', function(e) {
            [action, data] = e.data.split("|");
            if (action == 'getFbData') {
                e.source.postMessage('getFbData|'+JSON.stringify(fbApi.getData()), e.origin);
            }

            if (action == 'setFbData') {
                fbApi.setData(data);
            }
            
            if (action == 'clearFbData') {
                fbApi.clearForm();
            }
            
            if (action == 'toggleFbEdit') {
                (async function() {
                    data = JSON.parse(data);
                    console.log(data)
                    if (data.edit === false) {
                        let os = "<?=$_GET['os']?>";
                        
                        window.fbTitle     = data.title;
                        window.fbLogo      = data.logo;
                        window.fbNoActions = data.noActions;
                        
                        await fbApi.toggleEdit(false);
                        
                        <?php if($login_fabrica !== 175){ ?>
                            if (typeof data.formData != 'undefined') {
                                fbApi.setFormData(data.formData);
                                
                                <?php
                                if ($_GET['readonly']) {
                                ?>
                                    fbApi.setFormReadonly();
                                <?php
                                }
                                ?>
                            }
                        <?php } ?>
                        
                        
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
                                                action: 'gravarLaudoTecnico',
                                                form: JSON.stringify(fbApi.getData()),
                                                formData: JSON.stringify(fbApi.getFormData()),
                                                os: os
                                            },
                                            timeout: 60000
                                        }).fail(function(res) {
                                            reject({
                                                messages: ['Erro ao gravar laudo técnico']
                                            });
                                        }).done(function(res, req){
                                            if (req == 'success') {
                                                res = JSON.parse(res);
                                                
                                                if (res.error) {
                                                    reject({
                                                        messages: [res.error]
                                                    });
                                                } else {
                                                    resolve('Laudo Técnico e Data de Conserto gravados com sucesso');
                                                }
                                            } else {
                                                reject({
                                                    messages: ['Erro ao gravar laudo técnico']
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
                        
                        window.fbCallbackFinish = function(success) {
                            if (success === true) {
                                window.parent.postMessage("osConsertada|"+os, '*');
                            }
                        }
                    } else {
                        fbApi.toggleEdit(true);
                    }
                })();
            }
            
            if (action == 'getFbHeight') {
                let height = $('body.form-rendered > div.container-fluid').height();
                e.source.postMessage('getFbHeight|'+height, '*');
            }
        }, false);
            
        </script>
    </body>
</html>

<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

if ($areaAdmin === true) {
    include __DIR__.'/autentica_admin.php';
} else {
    include __DIR__.'/../autentica_usuario.php';
}
?>

<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Edição do Formulário</title>
        
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
        window.fbEditing = true;
        
        fbInit();
        
        window.fbLogo;
        window.fbTitle;
        window.fbNoActions;
        
        window.addEventListener('message', function(e) {
            [action, data] = e.data.split("|");
            
            if (action == 'getFbData') {
                e.source.postMessage('getFbData|'+JSON.stringify(fbApi.getData()).replace(/\'|\\\"|\\/g, ''), e.origin);
            }
            
            if (action == 'setFbData') {
                fbApi.setData(data);
            }
            
            if (action == 'clearFbData') {
                fbApi.clearForm();
            }
            
            if (action == 'viewFbForm') {
                (async function() {
                    data = JSON.parse(data);
                    
                    await fbApi.toggleEdit(true);
                    
                    if (typeof data.data != 'undefined') {
                        fbApi.setData(data.data);
                    }
                    
                    window.fbTitle     = data.title;
                    window.fbLogo      = data.logo;
                    window.fbNoActions = data.noActions;
                    
                    await fbApi.toggleEdit(false);
                })();
            }
            
            if (action == 'toggleFbEdit') {
                (async function() {
                    data = JSON.parse(data);
                    
                    if (data.edit === false) {
                        window.fbTitle     = data.title;
                        window.fbLogo      = data.logo;
                        window.fbNoActions = data.noActions;
                        
                        await fbApi.toggleEdit(false);
                        
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
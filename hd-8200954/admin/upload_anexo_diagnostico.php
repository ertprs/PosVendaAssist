<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {
  include 'autentica_admin.php';
} else {
  include 'autentica_usuario.php';
}
include_once '../class/tdocs.class.php';
$pedido = $_REQUEST['pedido'];

function limpaString ($string){
  $stringLimpa = str_replace("'", "", $string);
  return $stringLimpa;
}

if(isset($_GET['diagnostico'])){
    $diagnostico = (int)$_GET['diagnostico'];
}


if(isset($_POST["btnacao"])){
    $diagnostico = $_POST['diagnostico'];

    if (isset($_FILES) && count($_FILES) > 0 && !empty($_FILES['anexo']['tmp_name'])) {
            
        $tDocs = new TDocs($con, $login_fabrica);
        
        $anexoID = $tDocs->uploadFileS3($_FILES['anexo'],$diagnostico, true, 'diagnostico'); 

        if ($anexoID) {
              // Se ocorrer algum erro, o anexo está salvo:
              $_POST['anexo'] = json_encode($tDocs->sentData);
              if (!is_null($idExcluir)) {
                  $tDocs->deleteFileById($idExcluir);
              }
              $ok = "Anexo cadastrado com sucesso.";

              $caminho = $tDocs->getdocumentsByRef($diagnostico, 'diagnostico')->url;

              $link_anexo =  "<a href=$caminho target=_blank>".
                              "<img src=../helpdesk/imagem/clips.gif width=35px height=35px>".
                              "</a>";
                              
              echo "<script>
    //window.parent.location.reload();
              window.parent.document.getElementById('anexo_$diagnostico').innerHTML = '$link_anexo';
                      window.parent.Shadowbox.close();
              </script>";
        } else {
            $msg_erro .= 'Erro ao salvar o anexo!';
        }
    }
}



?>
<!DOCTYPE html />
<html>
  <head>
    <meta http-equiv=pragma content=no-cache>
    <link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
    <link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
    <link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
    <link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
    <link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />

    <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
    <script src="bootstrap/js/bootstrap.js"></script>
    <script src="plugins/dataTable.js"></script>
    <script src="plugins/resize.js"></script>
    <script src="plugins/shadowbox_lupa/lupa.js"></script>
  </head>
  <body>

    <div id="container_lupa" style="overflow-y:auto;">
        <div id="topo">
            <img class="espaco" src="imagens/logo_new_telecontrol.png">
            <img class="lupa_img pull-right" src="imagens/lupa_new.png">
        </div>
        <br>
  <br>
  <br>
 
        <form method="POST" enctype="multipart/form-data">
        <div class='titulo_tabela '>Manutenção de Anexos</div>
        <?php if(strlen(trim($ok))>0){ ?>
        <div class="alert alert-success" role="alert">
            <?=$ok?>
        </div>
        <?php } ?>
        <?php if(strlen(trim($msg_erro))>0){ ?>
        <div class="alert alert-danger" role="alert">
            <?=$msg_erro?>
        </div>
        <?php } ?>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class="form-group">
                    <label>Anexo</label>
                    <input type="file" name="anexo" id="exampleInputFile">
                </div>
            </div>
            <div class='span4'></div>
        </div>
        <div class='row-fluid'>
            <div class='span4'></div>
                <div class='span4' style='text-align:center'>
                  <input type="hidden" name="diagnostico" id="diagnostico" value="<?=$diagnostico?>">
                    <br />
                    <input type="submit" class='btn' name="btnacao" value="Gravar" >
                    <input type='hidden' id="btn_click" name='btn_acao' value='' />
                </div>
            </div>
            <div class='span4'></div>
        </div>
    </p><br />
        </form>

    </div>
    <br><Br><br><br>
  </body>
</html>

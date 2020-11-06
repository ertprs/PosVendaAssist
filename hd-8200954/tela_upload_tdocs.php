<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'plugins/fileuploader/TdocsMirror.php';

include_once 'funcoes.php';
$tdocs = new TdocsMirror();

if ($_FILES["arquivo"]) {
    $retorno = "";
    $response = $tdocs->post($_FILES["arquivo"]["tmp_name"]);

    if (count($response) > 0) {
        $new_response = current($response);
        $new_response = current($new_response);

        if ($new_response["unique_id"]) {
            
            $new_response = $tdocs->get($new_response["unique_id"]);
            if (strlen($new_response["link"]) > 0) {
                $retorno = "<div class='alert alert-success'><b>Link Tdocs:</b> ".$new_response["link"]."</div>";
            } else {
                $retorno = "<div class='alert alert-danger'><h2>Erro ao efetuar o upload.</h2></div>";
            }
        } else {
            $retorno = "<div class='alert alert-danger'><h2>Erro ao efetuar o upload.</h2></div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>Upload para Tdocs</title>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    </head>
    <body>
        
        <div class="container" style="margin-top: 40px;">
            <div class="panel panel-default">
                <div class="panel-heading">Efetuar Upload para Tdocs</div>
                <div class="panel-body">
                    <?php echo $retorno;?>
                    <form action="tela_upload_tdocs.php"  method="post"  enctype="multipart/form-data" class="form-horizontal">
                        <div class="form-group">
                            <label for="arquivo" class="col-sm-2 control-label">Arquivo:</label>
                            <div class="col-sm-10">
                                <input type="file" class="form-control" name="arquivo" id="arquivo">
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-offset-2 col-sm-10">
                                <button type="submit" class="btn btn-primary"> <i class="glyphicon glyphicon-upload"></i> Efetuar Upload</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
           
        </div>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    </body>
</html>

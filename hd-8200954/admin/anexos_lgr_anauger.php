<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {
    include 'autentica_admin.php';
    define('APPBACK', '../');
    $areaAdmin = true;    
} else {
    define('APPBACK', '');
    include 'autentica_usuario.php';
}
 include_once "../class/tdocs.class.php";
$posto = $_GET['posto'];
header('Content-Type: text/html; charset=iso-8859-1');

if(isset($_GET['faturamento'])){
    $faturamento = $_GET['faturamento'];
}

$title = "Anexos";

if(isset($_POST['anexar'])){

    $anexo = $_FILES["anexo"];
    $faturamento = $_POST['faturamento'];

    if($anexo["size"] > 0){
        $tDocs = new TDocs($con, $login_fabrica);
        /* HD-3980490 Retirado o limite do anexo*/ 
        $tDocs->setContext("lgr");
        $anexoID = $tDocs->uploadFileS3($anexo, $faturamento, false);

        if (!$anexoID) {
            $msg_erro["msg"][] = 'Erro ao salvar o contato!';
            //break;
        }
    }
}

if(isset($_GET['excluir_anexo'])){
    $id= $_GET['id'];
    $faturamento = $_GET['faturamento'];

    if(strlen($id) > 0 && strlen($faturamento) > 0){

        $tDocs = new TDocs($con, $login_fabrica);

        $tDocs->setContext("lgr")->removeDocumentById($id);

    }

    header("location: anexos_lgr_anauger.php?faturamento={$faturamento}");
    exit;
}
$tDocs = new TDocs($con, $login_fabrica);
$info = $tDocs->getdocumentsByRef($faturamento, "lgr");

?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<title><?=$title?></title>
		<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/bootstrap.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/extra.css" />
		<!-- <link type="text/css" rel="stylesheet" media="screen" href="../css/tc_css.css" /> -->
		<!-- <link type="text/css" rel="stylesheet" media="screen" href="../css/tooltips.css" /> -->
		<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/ajuste.css" />

		<script src="../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="../bootstrap/js/bootstrap.js"></script>

        <script type="text/javascript">            
            $(function(){
                $("#btn_atualizar").click(function(){
                    $("#btn_atualizar").hide();
                    $("#loading_pre_cadastro").show();
                });
            });


            function excluir_anexo(id){

                var r = confirm("Você deseja realmente excluir esse anexo?");

                if (r == true) {

                    location.href = "?excluir_anexo=true&faturamento=<?php echo $faturamento; ?>&id="+id;

                }

            }
        </script>
		<style>
            .diferente{
                color:red;
            }			
            .box-anexo{
                float: left;
                margin: 20px;
                text-align: center;
            }
		</style>		
	</head>
<body>
	<div class="container">
        <?php if(strlen($msg_erro)>0){ ?>
        <div class="row-fluid">
            <div class="alert alert-danger"><?=$msg_erro?></div>
        </div>
        <?php } if(strlen($ok)>0){ ?>
        <div class="row-fluid">
            <div class="alert alert-success"><?=$ok?></div>
        </div>
        <?php } ?>
        <?php  if(strlen($msg_erro)==0 and strlen($ok)==0){ ?>
		<div class="row-fluid">

		<table class="table table-striped table-bordered table-hover table-fixed">
            <thead  class="titulo_coluna">
                <tr>
                    <th colspan="2">Anexos</th>
                </tr> 
            </thead>
            <body>
                <tr>
                    <td>                        
                        <?php 

                if(count($info->attachListInfo) > 0){
                    foreach ($info->attachListInfo as $anexo) { 
                        $tdocs_id = $anexo["tdocs_id"];
                        $link_arq = $anexo["link"]; 

                        $icon_pdf = "imagens/pdf_icone.png";

                        $extensao = preg_replace("/.+\./", "", basename($link_arq));

                        if($extensao == 'pdf'){
                            $arquivo = $icon_pdf;
                        }else{
                            $arquivo = $link_arq;
                        }

                        echo "
                        <div class='box-anexo'>
                            <div style='width:130px; height:100px' >
                                <a href='{$link_arq}' target='_blank'>
                                    <img width='120' src='{$arquivo}' /> 
                                </a>
                                <br />   
                            </div> 
                            <button type='button' class='btn btn-danger' onclick='excluir_anexo(\"{$tdocs_id}\")'>
                                Excluir
                            </button>                        
                        </div>
                        ";
                    }
                    
                    echo "<div style='clear: both;'></div>";
                }else{
                    echo "<p style='text-align: center; text-transform: uppercase;'> Nenhum anexo encontrado </p>";
                }
               ?>
                    </td>
                </tr>
                <tr class="titulo_coluna">
                    <th >Anexar</th>
                </tr>
                <tr>
                    <form name="frm_anexo_anauger" method="POST" action="" enctype = multipart/form-data>
                        <td style="text-align: center">
                            Arquivo: <input type='file' name='anexo' value=''>
                            <input type='hidden' name='faturamento' value='<?=$faturamento?>'>
                            <br> 
                            <br>
                            <input class="btn btn-primary" type="submit" name="anexar" value="Gravar">

                        </td>
                    </form>

                </tr>
            </body>
        </table>
		</div>
        <div class="row-fluid">
            <div class="col-md-12">
               
            </div>
        </div>
        <?php } ?>
	</div>
<?php //endif; ?>
</body>
</html>

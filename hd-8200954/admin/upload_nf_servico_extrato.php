<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';
include __DIR__."/class/tdocs.class.php";

include_once S3CLASS;
$s3_extrato = new AmazonTC("extrato", (int) $login_fabrica);

if ($_GET["extrato"]) {
	

	if($_POST['envia_upload_nf'] == "t"){
		$msg = "";
		$msg_erro = [];
    	$extrato = $_GET['extrato'];

        if(strlen($_FILES['arquivo_nota_fiscal_servico']['name']) == 0) {
            $msg_erro["msg"][] = traduz("Erro ao fazer o upload do arquivo");
        } else {

	        $arquivo['name'] 		= $_FILES['arquivo_nota_fiscal_servico']['name'];
	        $arquivo['type'] 		= $_FILES['arquivo_nota_fiscal_servico']['type'];
	        $arquivo['tmp_name'] 	= $_FILES['arquivo_nota_fiscal_servico']['tmp_name'];
	        $arquivo['error'] 		= $_FILES['arquivo_nota_fiscal_servico']['error'];
	        $arquivo['size'] 		= $_FILES['arquivo_nota_fiscal_servico']['size'];

			$types = array("png", "jpg", "pdf", "doc", "docx", "bmp");
			$msg_formatos = traduz("Formato inválido, são aceitos os seguintes formatos: png, jpg, pdf, doc, docx, bmp");
			$type  = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));
			if ($type == "jpeg") {
				$type = "jpg";
			}
	      
	        if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["size"] > 0) {
	            if (!in_array($type, $types)) {
	                $msg_erro["msg"][] = $msg_formatos;
	            } else {
	                $filess = $s3_extrato->getObjectList("{$extrato}-", false);            
	                $qtdeAnexos = !count($filess) ? '' : '-'.count($filess);
	                $s3_extrato->upload("{$extrato}-nota_fiscal_servico{$qtdeAnexos}", $arquivo);

	                $sql = "UPDATE tbl_extrato SET nf_recebida = true WHERE extrato = $extrato";
	                $res = pg_query($con, $sql);
	                $sql_extrato_status = " INSERT INTO tbl_extrato_status (data, obs, pendente, fabrica, extrato, arquivo) values (now(), 'Envio de NFe', true, $login_fabrica, $extrato, '$extrato-nota_fiscal_servico') ";
	                $res_extrato_status = pg_query($con, $sql_extrato_status);
	                $msg = traduz("Upload efetuado com sucesso");
	            }

	        } else {
	            $msg_erro["msg"][]  = traduz("Erro ao fazer o upload do arquivo");
	        }
        }
    }


?>

<!doctype html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
	<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />
        <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
        <?php
        $plugins = array(
            "shadowbox",
            "price_format",
            "bootstrap3",
        );
        include("plugin_loader.php");

        ?>
        <style>
        	body{
        		margin: 0px;padding: 0px;background: #f5f5f5;text-align: center;
        	}
        	input[type=file] {
			    display: inline-block;
			}
			.txt_anexar{
				color:#fff;font-size:18px;margin-top: 10px;padding-bottom: 10px;
			}
			.xfooter{
				width: 100%;padding: 10px;margin-top: 38px;
	        text-align: center;
			}
        </style>
       
    </head>
    <body>
    	<div class='titulo_coluna'>
            <h2 class='txt_anexar'>Anexar Nota Fiscal de Serviço</h2>
        </div>
        <?php if (count($msg_erro["msg"]) > 0) {?>
        	<div class="alert alert-danger"><?php echo implode("<br>", $msg_erro["msg"]);?></div>
        <?php }?>
        <?php if (strlen($msg) > 0) {?>
        	<div class="alert alert-success"><?php echo $msg;?></div>
        	<script>
        		setTimeout(function(){
        			window.parent.location.reload()
        			window.parent.Shadowbox.close();
        		}, 1000);
        	</script>
        <?php }?>
		<form name="form_nota_fiscal_servico" id="form_nota_fiscal_servico" method="POST" enctype="multipart/form-data">
			<input type="hidden" name="envia_upload_nf" value="t">
			<label>Escolha um arquivo:</label><br />
			<div align="center" style="width: 100%;text-align: center;">
				<input type='file' name='arquivo_nota_fiscal_servico' id="arquivo_nota_fiscal_servico" ></div><br/><br/>
			<div class="xfooter">
	        	<button type='submit' class="btn btn-success">Anexar Arquivo</button>
	        </div>
	    </form>
		</div>
    </body>
</html>


<?php } else {
    exit("<div class='alert alert-danger'> Nenhum dados cadastrados</div>");
}
?>


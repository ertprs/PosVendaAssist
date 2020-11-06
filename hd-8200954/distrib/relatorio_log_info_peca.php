<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include_once "../class/tdocs.class.php";

include_once '../class/AuditorLog.php';

$peca = $_REQUEST["peca"];

$sql = "SELECT fabrica,peca 
		FROM tbl_peca 
		WHERE peca = $peca";
$res = pg_query($con, $sql);

$fabrica_peca = pg_fetch_result($res, 0, "fabrica");

$tDocs       = new TDocs($con, $fabrica_peca);

if ($_POST["ajax_anexo_upload"] == true) {

	$posicao = $_POST["anexo_posicao"];
    $chave   = $_POST["anexo_chave"] ? : $peca;

    $arquivo = $_FILES["anexo_upload_{$posicao}"];


    $ext = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));

    if (strlen($arquivo['tmp_name']) > 0) {

        if (!in_array($ext, array('png', 'jpg', 'jpeg', 'bmp', 'gif'))) {

            $retorno = array('error' => utf8_encode('Arquivo em formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, gif'),'posicao' => $posicao);

        } else {

            if ($_FILES["anexo_upload_{$posicao}"]["tmp_name"]) {

            	$tDocs->setContext('peca', 'distrib');

            	$anexoID = $tDocs->uploadFileS3($arquivo, $chave, $peca != $chave);
                $arquivo_nome = $tDocs->sentData;

                if (!$anexoID) {
                    $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'),'posicao' => $posicao);
                } 

            }

            if (empty($anexoID)) {
                $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'),'posicao' => $posicao);
            }

            $link = '//api2.telecontrol.com.br/tdocs/document/id/'.$arquivo_nome['tdocs_id'].'/file/'.$arquivo_nome['filename'];
            $href = $link;
            if (!strlen($link)) {
                $retorno = array('error' => utf8_encode(' 2'),'posicao' => $posicao);
            } else {

                $retorno = compact('link', 'arquivo_nome', 'href', 'ext', 'posicao');
            }
        }
    } else {
        $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'),'posicao' => $posicao);
    }

    exit(json_encode($retorno));

}

?>
<html>
<head>
	<title>Cadastro informações adicionais</title>
	<link type="text/css" rel="stylesheet" href="css/css.css">
	<script src="../admin/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
	<script src="../admin/plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
	<script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
	<script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
	<script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
	<script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
	<script src="../plugins/FancyZoom/FancyZoom.js"></script>
	<script src="../plugins/FancyZoom/FancyZoomHTML.js"></script>
	<script src='../plugins/jquery.form.js'></script>

	<script>
	$(function(){

		setupZoom();

			/* ANEXO DE FOTOS */
        $("input[name^=anexo_upload_]").change(function() {
            var i = $(this).parent("form").find("input[name=anexo_posicao]").val();

            $("#div_anexo_"+i).find("button").hide();
            $("#div_anexo_"+i).find("img.anexo_thumb").hide();
            $("#div_anexo_"+i).find("img.anexo_loading").show();

            $(this).parent("form").submit();
        });

        $("button[name=anexar]").click(function() {
            var posicao = $(this).attr("rel");
            $("input[name=anexo_upload_"+posicao+"]").click();
        });

        $("form[name=form_anexo]").ajaxForm({
            complete: function(data) {
                data = $.parseJSON(data.responseText);
                console.log(data);
            if (data.error) {
                alert(data.error);
                $("#div_anexo_"+data.posicao).find("img.anexo_loading").hide();
                $("#div_anexo_"+data.posicao).find("button").show();
                $("#div_anexo_"+data.posicao).find("img.anexo_thumb").show();
            } else {
                var imagem = $("#div_anexo_"+data.posicao).find("img.anexo_thumb").clone();
                $(imagem).attr({ src: data.link });

                $("#div_anexo_"+data.posicao).find("img.anexo_thumb").remove();

                var link = $("<a></a>", {
                    href: data.href,
                    target: "_blank"
                });

                $(link).html(imagem);

                $("#div_anexo_"+data.posicao).prepend(link);

                setupZoom();

                $("#div_anexo_"+data.posicao).find("input[rel=anexo]").val(data.arquivo_nome);
            }

            $("#div_anexo_"+data.posicao).find("img.anexo_loading").hide();
            $("#div_anexo_"+data.posicao).find("button").show();
            $("#div_anexo_"+data.posicao).find("img.anexo_thumb").show();
        }
        /* FIM ANEXO DE FOTOS */
    	});

    });    
	</script>	
</head>
<body>
<? 
include_once "../admin/relatorio_log_alteracao_new.php";
?>

<?
$sql = "SELECT fabrica,peca 
		FROM tbl_peca 
		WHERE peca = $peca";
$res = pg_query($con, $sql);

$fabrica_peca = pg_fetch_result($res, 0, "fabrica");		

?>

</body>
</html>
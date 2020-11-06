<?php 
#error_reporting(E_ERROR | E_WARNING | E_PARSE);

$logo_fabrica = "SELECT logo 
				 FROM tbl_fabrica 
				 WHERE fabrica = $login_fabrica";

$logo_fabrica = pg_query($con, $logo_fabrica);
$logo_fabrica = pg_fetch_result($logo_fabrica, 0, 'logo');

require_once "$login_fabrica/contratos/contrato_parceria_comercial.php";
require_once __DIR__ . '../../classes/mpdf61/mpdf.php';
require_once __DIR__ . "../../plugins/fileuploader/TdocsMirror.php";

$contrato = "SELECT tdocs_id FROM tbl_tdocs WHERE fabrica = $login_fabrica 
			 AND referencia_id = $login_posto 
			 AND contexto = 'contrato_parceria' 
			 ORDER BY tdocs_id DESC
			 LIMIT 1 ";

$contrato   = pg_query($con, $contrato);
$contratoId = pg_fetch_result($contrato, 0, 'tdocs_id');

$s3_tdocs = new TdocsMirror();

if (!$contratoId) {
	$nome = 'contrato_parceria_comercial_' . $login_posto . '.pdf';
	$arquivo_contrato = '/tmp/' . $nome;
	$gerarPDF = new mPDF();
	$gerarPDF->SetDisplayMode('fullpage');
	$gerarPDF->forcePortraitHeaders = true;
	$gerarPDF->charset_in = 'windows-1252';
	$gerarPDF->WriteHTML($content);
	$gerarPDF->Output($arquivo_contrato, "F");
	$postPDF = $s3_tdocs->post($arquivo_contrato);
	$contratoId = $postPDF[0][$nome]['unique_id'];
}

$postPDF = $s3_tdocs->get($contratoId);

if (isset($_POST['aceitar_termo'])) {

	$contratoId = $_POST['contrato'];

	pg_query($con, "BEGIN");

	$query = "SELECT parametros_adicionais, contato_email 
			  FROM tbl_posto_fabrica 
			  WHERE fabrica = $login_fabrica 
			  AND   posto   = $login_posto";

	$res = pg_query($con, $query);

	$parametrosAdd = pg_fetch_result($res, 0, "parametros_adicionais");
	$email      = pg_fetch_result($res, 0, "contato_email");
	#$email      = "william.castro@telecontrol.com.br";

	$parametrosAdd = json_decode($parametrosAdd, 1);

	$parametrosAdd["contrato"] = 't';
	$parametrosAdd["data_aceite_contrato"] = date('Y-m-d');

	$parametrosAdd = json_encode($parametrosAdd);

	$query = "UPDATE tbl_posto_fabrica 
			  SET parametros_adicionais = '$parametrosAdd'  
			  WHERE fabrica = $login_fabrica 
			  AND   posto   = $login_posto";

	$res = pg_query($con, $query);

	if (strlen(pg_last_error()) == 0) {

		$retornoComunicado = cadastrarComunicado($email);
		$anexos            = salvarTdocs($contratoId);
		$retornoEmail      = emailTermo($email, $anexos);
		if ($retornoComunicado && $retornoEmail) {
			
			pg_query($con, "COMMIT");

			exit(json_encode(["msg" => "success"]));
		}
	}

	pg_query($con, "ROLLBACK");

	exit(json_encode(["msg" => "error"]));
}

function salvarTdocs($contratoId) {
	
	global $login_fabrica, $login_posto, $nome, $con;

  	$obs = json_encode(array(
	    "acao"     => "anexar",
	    "filename" => "{$nome}",
	    "filesize" => "",
	    "data"     => date('d-m-Y'),
	    "fabrica"  => "{$login_fabrica}",
	    "page"     => "termo_uso/template_termo_uso.php",
	    "typeId"   => "contrato",
	    "descricao"=> ""
	));

    $sql = "INSERT INTO tbl_tdocs(tdocs_id,fabrica,contexto,situacao,obs,referencia,referencia_id) 
            VALUES('{$contratoId}', $login_fabrica, 'contrato_parceria', 'ativo', '[$obs]', 'contrato_parceria_comercial', $login_posto) RETURNING tdocs_id";
    
    $res = pg_query($con,$sql);
    $contratoId = pg_fetch_result($res, 0, 'tdocs_id');


    require_once "$login_fabrica/certificados/certificado_parceria_comercial.php";

	$nome = 'certificado_parceria_comercial_' . $login_posto . '.pdf';
	$arquivo_contrato = '/tmp/' . $nome;

	$s3_tdocs = new TdocsMirror();
	$gerarPDF = new mPDF();
	$gerarPDF->SetDisplayMode('fullpage');
	$gerarPDF->forcePortraitHeaders = true;
	$gerarPDF->charset_in = 'windows-1252';
	$gerarPDF->WriteHTML($contentCertificado);
	$gerarPDF->Output($arquivo_contrato, "F");
	$postPDF = $s3_tdocs->post($arquivo_contrato);
	$certificadoId = $postPDF[0][$nome]['unique_id'];
	$postPDF = $s3_tdocs->get($certificadoId);

	if (!empty($contratoId)) {
		$contratoIdLink = $s3_tdocs->get($contratoId);
		$contratoId = $contratoIdLink['link'];
	}
		
  	$obs = json_encode(array(
	    "acao"     => "anexar",
	    "filename" => "{$nome}",
	    "filesize" => "",
	    "data"     => date('d-m-Y'),
	    "fabrica"  => "{$login_fabrica}",
	    "page"     => "termo_uso/template_termo_uso.php",
	    "typeId"   => "certificado",
	    "descricao"=> ""
	));

    $sql = "INSERT INTO tbl_tdocs(tdocs_id,fabrica,contexto,situacao,obs,referencia,referencia_id) 
            VALUES('{$postPDF['link']}', $login_fabrica, 'certificado_parceria', 'ativo', '[$obs]', 'certificado_parceria_comercial', $login_posto) RETURNING tdocs_id";
    
    $res = pg_query($con,$sql);
    $certificadoId = pg_fetch_result($res, 0, 'tdocs_id');

 	return ['certificado' => $certificadoId, 'contrato' => $contratoId];
}

function cadastrarComunicado($email) {

	global $login_fabrica, $login_posto, $con;

	$descricao = "Contrato de prestação de serviço";
	$mensagem  = "Contrato de prestação de serviço aceito.";
	$tipo      = "Contrato";
	$pais      = "BR";

	$comunicado = "INSERT INTO tbl_comunicado (pais, mensagem, fabrica, obrigatorio_site, descricao, tipo, posto, ativo, remetente_email)
	VALUES ('$pais', '$mensagem', $login_fabrica, 't', '$descricao', '$tipo', $login_posto, 't', '$email')";

	$res = pg_query($con, $comunicado);

	if (strlen(pg_last_error()) == 0) {
		return True;
	}

	return False;
}

function emailTermo($email, $anexos) {
	
	global $login_posto, $login_fabrica, $con;

	include_once __DIR__ . '../../class/communicator.class.php';

	$postoInfo = "SELECT tbl_posto_fabrica.codigo_posto, 
							tbl_posto.nome, 
							tbl_posto.cnpj
				 FROM tbl_posto_fabrica
				 LEFT JOIN tbl_posto 
				 	ON tbl_posto.posto = tbl_posto_fabrica.posto
				 WHERE tbl_posto_fabrica.posto = $login_posto 
				 AND tbl_posto_fabrica.fabrica = $login_fabrica";

	$postoInfo = pg_query($con, $postoInfo);
	$postoInfo = pg_fetch_object($postoInfo);

	$assunto = 'Aviso de download de contrato de prestação de serviço - ' . $postoInfo->nome . ' - Anauger';
	$mensagem = 'Prezados, <br /> informamos que o posto <strong>' . $postoInfo->codigo_posto .' - ' . $postoInfo->nome . '</strong> (CNPJ: ' . $postoInfo->cnpj .') recebeu o contrato de prestação de serviços através do sistema da Telecontrol. <br /> Data: '. date("d/m/Y H:i:s") . '<br> Clique <a href="'.$anexos['contrato'].'">aqui</a> para baixar o contrato <br>
		Clique <a href="'.$anexos['certificado'].'">aqui</a> para baixar o certificado (Também disponível na aba OS do sistema Telecontrol)';

	$mailTc = new TcComm('smtp@posvenda');
	$res = $mailTc->sendMail(
	    $email,
	    $assunto,
	    $mensagem,
	    'noreply@telecontrol.com.br'
	);

	if ($res == True) {
		return True;
	}

	return False;
}

?>
<html>
	<head>
		<title>Termo de Uso</title>
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="css/tc_css.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="css/tooltips.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />
		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
		<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
		<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
		<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
		<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>
		<script type="text/javascript" src="plugins/shadowbox/shadowbox.js"></script>
		<link type="text/css" href="plugins/shadowbox/shadowbox.css" rel="stylesheet" media="all">
	</head>

	<style type="text/css">
		
		.bg-primary {
			background-color : #596d9b;	
		}

		.bg-success {
			background-color : #d4edda;
		}

		.bg-error {
			background-color : #f8d7da;
		}

		.text-white {
			color : white;
		}

		.text-center {
			text-align: center;
		}

		.justif-content-around {
			display: flex; 
			justify-content: space-around;
		}

		.logo {
			display: block;
			margin-left: auto;
			margin-right: auto;
			width: 30%;
		}

		.loading {
			margin-left: auto;
			margin-right: auto;
			width: 10%;
		}

		img {width: 50%}

	</style>

	<body>
		<div class="container">
			<div class="row bg-primary text-white text-center">
				<h3>Termos de Uso</h3>
			</div>
			<br><br>
			<div class="row">
			 	<div class="bg-success text-center" id="aceitar_sucesso" style="display: none">
			 		<h3>Termo aceito com sucesso</h3>
			 	</div>
			 	<div class="bg-error text-center" id="aceitar_erro" style="display: none">
			 		<h3>Erro ao aceitar termo</h3>
			 	</div>
			</div>
			<div class="row text-center logo justif-content-around">
			 	<img src="logos/<?= $logo_fabrica ?>">
			</div>
			<div class="row text-center">
				<h3>Contrato de Parceria Empresarial</h3>
			</div>
			<div class="row text-center">
				<h3>Clique aqui para
					<a href="<?=$postPDF['link']?>" target="_blank">baixar</a>
					o contrato em PDF
				</h3>
				<br>
				<button id="concordar" class="btn btn-warning">Li e Concordo com os termos</button>
			</div>
			<br><br>
			<div class="row justif-content-around">
				<button id="recusar" class="btn btn-danger">Recusar</button>
				<button id="aceitar" class="btn btn-success" style="display: none">Aceitar</button>
			</div>
			<br><br>
			<div id="carregando" class="row text-center loading" style="display: none">
				<div class="row justif-content-around">

					<img src="js/loading.gif">
				</div>
				<div class="row">
					<h3>Enviando...</h3>
				</div>
			</div>
		</div>
	</body>
</html>

<script type="text/javascript">
	
	document.getElementById("concordar").onclick = function () {

		document.getElementById("aceitar").style.display = "block";
		document.getElementById("concordar").classList.add("btn-success");
	}

	document.getElementById("aceitar").onclick = function () {
		
		contrato = "<?= $contratoId ?>";

		$.ajax({
                url:"<?=$PHP_SELF?>",
                type:"POST",
                dataType:"JSON",
                data:{
                    aceitar_termo : "aceitar_termo",
                    contrato      : contrato
                },
                beforeSend : function () {
                	$("#recusar").hide();
                	$("#aceitar").hide();
                	$("#concordar").hide();

                	$("#carregando").show();
                }
            }).done(function(data) {

            	if (data['msg'] == 'success') {

            		window.location.href = "login.php";
            	}

            }).fail(function() {
                document.getElementById("aceitar_erro").style.display = "block";
            	$("#recusar").show();
            	$("#concordar").show();

            	$("#carregando").hide();
            });
	};

	document.getElementById("recusar").onclick = function () {

		if (confirm("Deseja mesmo recusar?")) {

			window.location.href = "logout_2.php";
		}
	};

</script>
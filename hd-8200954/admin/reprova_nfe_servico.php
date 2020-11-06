<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";
include 'autentica_admin.php';

include_once S3CLASS;
$s3_extrato = new AmazonTC("extrato", (int) $login_fabrica);
include_once '../class/communicator.class.php';

if ($_REQUEST['ajax']) {
    $xextrato = $_REQUEST['extrato'];
    //$xjustificativa = utf8_encode($_REQUEST['justificativa']);
    $xjustificativa = utf8_decode($_REQUEST['justificativa']);
    $retorno = array();

    if (!empty($xextrato)) {

        pg_query($con, "BEGIN");

        pg_query($con, "INSERT INTO tbl_extrato_status (extrato, data, obs, fabrica)
                        VALUES ({$xextrato}, current_timestamp, 'Nota Fiscal Reprovada. Motivo: {$xjustificativa}', {$login_fabrica})");

        $sqlDadosPosto = "SELECT tbl_posto_fabrica.contato_email,
                                 tbl_posto_fabrica.posto
                          FROM tbl_extrato
                          INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_extrato.posto 
                          AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                          WHERE extrato = {$xextrato}";
        $resDadosPosto = pg_query($con, $sqlDadosPosto);


        if(pg_num_rows($resDadosPosto) > 0){

            $contatoEmail = pg_fetch_result($resDadosPosto, 0, 'contato_email');
            $posto = pg_fetch_result($resDadosPosto, 0, 'posto');

            $sql = "INSERT INTO tbl_comunicado (
                        fabrica, 
                        tipo, 
                        posto, 
                        obrigatorio_os_produto, 
                        obrigatorio_site, 
                        ativo, 
                        descricao, 
                        mensagem
                    ) VALUES (
                        {$login_fabrica}, 
                        'Boletim',
                        {$posto},
                        'f',
                        't',
                        't',
                        'Extrato Recusado', 
                        'NFe do extrato {$xextrato} foi recusada pela fábrica, Motivo: {$xjustificativa}'
                    )";
            $res = pg_query($con, $sql);
        
        }

        if (strlen(pg_last_error()) > 0) {
            $retorno = array("erro" => utf8_encode("Ocorreu um erro ao gravar justificativa"));
            pg_query($con, "ROLLBACK");
        } else {
            $retorno = array("sucesso" => "Justificativa gravada com sucesso");

            $sqlRemoveNfe = "UPDATE tbl_tdocs 
                             SET situacao = 'inativo'
                             WHERE referencia_id = {$xextrato}
                             AND fabrica = {$login_fabrica}
                             AND contexto = 'extrato'";
            $resRemoveNfe = pg_query($con, $sqlRemoveNfe);

            pg_query($con, "COMMIT");

            $mensagem = "Recusado por admin, motivo: {$xjustificativa}";
            $mailTc = new TcComm($externalId);

            if ($_serverEnvironment == 'development') {
                $contatoEmail = "lucas.souza@telecontrol.com.br";
            }

            $res = $mailTc->sendMail(
              $contatoEmail,
              "Extrato Recusado {$xextrato}",
              $mensagem,
              'helpdesk@telecontrol.com.br'
            );

        }
    } else {
        $retorno = array("erro" => utf8_encode("Extrato não encontrado para atualizar informações"));
    }
    echo json_encode($retorno);
    exit;
}
?>


<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv=pragma content=no-cache>
	<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
	<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />

	<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />
	<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
	<script language="javascript">
	    $(function() {
	    	$(document).on("click", ".btn-gravar", function(){

                $(this).prop("disabled", true).text("Aguarde...");

	    		var justificativa = $("textarea[name=justificativa]").val()
				var extrato = $("input[name=extrato]").val()
	    		$.ajax({
                    type: "POST",
                    url: "<?= $PHP_SELF; ?>",
                    data: {ajax: true,justificativa:justificativa,extrato:extrato},
                    error: function () {
                        alert('Falha na solicitação');
                    },
                    complete: function(http){
                        retorno = JSON.parse(http.responseText);
                        if (typeof retorno.erro != 'undefined' && retorno.erro.length > 0) {
                            alert(retorno.erro);
                            $(this).prop("disabled", false).text("Gravar");
                        } else {
                            $(".reprova_nfe[data-extrato="+extrato+"]", window.parent).hide();
                            $(".aprovar_nfe[data-extrato="+extrato+"]", window.parent).hide();
                            
                            alert(retorno.sucesso);
                            window.parent.Shadowbox.close();
                        }
                    }
                });
	    	});
	    });
	</script>
</head>
<body>
<label class="titulo_coluna" style="padding-bottom: 10px;font-size: 21px">Justificativa da Reprova:</label><br>
<div class="container-fluid">
	<form action="" method="post">
		<input type="hidden" name="extrato" value="<?php echo $_GET["extrato"];?>">
		<textarea name="justificativa" style="width: 100%" rows="5"></textarea>
		<div align="center" style="text-align: center;">
		<button type="button" class="btn btn-success btn-gravar">Gravar</button>
		</div>
	</form>
</div>
</body>
</html>

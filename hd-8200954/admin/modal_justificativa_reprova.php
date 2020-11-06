<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";
include "autentica_admin.php";

if ($_REQUEST['ajax']) {
    $xextrato = $_REQUEST['extrato'];
    //$xjustificativa = utf8_encode($_REQUEST['justificativa']);
    $xjustificativa = $_REQUEST['justificativa'];
    $retorno = array();

    if (!empty($xextrato)) {

        $sqlNota = "SELECT nf_autorizacao
                    FROM tbl_extrato_pagamento
                    WHERE extrato = {$xextrato}";
        $resNota = pg_query($con, $sqlNota);

         $jsonParametrosAdicionais = json_encode([
            "notaFiscal" => pg_fetch_result($resNota, 0, 'nf_autorizacao')
        ]);

        pg_query($con, "UPDATE tbl_extrato_pagamento SET justificativa = '{$xjustificativa}' WHERE extrato = {$xextrato};");
        
        pg_query($con, "INSERT INTO tbl_extrato_status (extrato,data,obs,fabrica,admin_conferiu, pendente, parametros_adicionais) VALUES ({$xextrato},current_timestamp,'{$xjustificativa}',{$login_fabrica},{$login_admin}, true, '{$jsonParametrosAdicionais}')");

        if (strlen(pg_last_error()) > 0) {
            $retorno = array("erro" => utf8_encode("Ocorreu um erro ao gravar justificativa"));
        } else {
            $retorno = array("sucesso" => "Justificativa gravada com sucesso");
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
                        } else {
                            alert(retorno.sucesso);
                            window.parent.reprovarNFFull(extrato);
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

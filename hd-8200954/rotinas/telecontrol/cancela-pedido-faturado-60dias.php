<?php

/*
    * Includes
    */

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

    /*
    * Definição
    */
    date_default_timezone_set('America/Sao_Paulo');
    $fabrica = $ARGV[1];
    $data = date('d-m-Y');

    $env = "producao";
    $arquivos = "/tmp";

    $fp_erro = fopen("$arquivos/telecontrol/cancela-pedido-faturado-60dias.err", "w+");
    $fp_log = fopen("$arquivos/telecontrol/cancela-pedido-faturado-60dias.log", "w+");

    $sql = "SET DateStyle TO 'SQL,EUROPEAN'";
    $result = pg_query($con, $sql);

	$dias = 60; 
    if (strlen(pg_last_error($con)) > 0) {
        $msg_erro = "$sql ";
        $msg_erro .= pg_last_error($con). "\n\n";
    }

    if ($fabrica == 81) {
        $tipo_pedido = 153;
    }

    if ($fabrica == 114) {
        $tipo_pedido = 234;
    }

    if ($fabrica == 122) {
        $tipo_pedido = 246;
    }

    if ($fabrica == 123) {
        $tipo_pedido = 252;
		$dias = 180;
    }

    if ($fabrica == 125) {
        $tipo_pedido = 264;
    }

    if ($fabrica == 153) {
        $tipo_pedido = 330;
    }
	if($fabrica == 147) {

        $tipo_pedido = 311;
	}
	if($fabrica == 160) {

        $tipo_pedido = 348;
	}

$sql = "SELECT nome
    FROM tbl_fabrica
    WHERE fabrica = $fabrica";
$result = pg_query($con, $sql);
$fabrica_nome = pg_fetch_result($result, 0, nome);

$sql = "
SELECT
DISTINCT
tbl_posto_fabrica.codigo_posto,
tbl_posto.nome AS posto_nome,
tbl_pedido.posto,
tbl_pedido.pedido,
TO_CHAR(tbl_pedido.finalizado, 'dd/mm/YYYY') AS finalizado

FROM
tbl_pedido
JOIN tbl_pedido_item USING(pedido)
JOIN tbl_posto_fabrica USING(posto, fabrica)
JOIN tbl_posto ON tbl_posto_fabrica.posto=tbl_posto.posto

WHERE
fabrica = $fabrica
AND tipo_pedido = $tipo_pedido
AND qtde <> qtde_faturada_distribuidor+qtde_cancelada
AND finalizado IS NOT NULL
AND finalizado + INTERVAL '$dias days' < NOW()
AND tbl_pedido.distribuidor = 4311
";
$result = pg_query($con, $sql);

if (strlen(pg_last_error($con)) > 0) {
    $msg_erro = "$sql ";
    $msg_erro .= pg_last_error($con). "\n\n";
}

$itens_cancelados_total = "";

for($i=0; $i<pg_num_rows($result); $i++){
    $codigo_posto    = pg_fetch_result($result, $i, codigo_posto);
    $posto_nome      = pg_fetch_result($result, $i, posto_nome);
    $posto           = pg_fetch_result($result, $i, posto);
    $pedido          = pg_fetch_result($result, $i, pedido);
    $finalizado      = pg_fetch_result($result, $i, finalizado);


    $sql = "SELECT
    pedido_item,
    tbl_peca.referencia,
    fn_retira_especiais(tbl_peca.descricao) as descricao,
    qtde-qtde_faturada_distribuidor-qtde_cancelada AS qtde_pendente
    FROM
    tbl_pedido_item
    JOIN tbl_peca ON tbl_pedido_item.peca=tbl_peca.peca
    WHERE
    tbl_pedido_item.pedido=$pedido
    AND qtde <> qtde_faturada_distribuidor+qtde_cancelada
    ";
    $result2 = pg_query($con, $sql);

    if (strlen(pg_last_error($con)) > 0) {
        $msg_erro .= "$sql ";
        $msg_erro .= pg_last_error($con). "\n\n";
        $erro = "*";
    }

    $itens_cancelados = "";
    $cor = "#CCCCCC";

    for($a=0; $a<pg_num_rows($result2); $a++){
        $erro = " ";
        $sql = "BEGIN TRANSACTION";
        $resultX = pg_query($con, $sql);
        $pedido_item        = pg_fetch_result($result2, $a, 'pedido_item');
        $referencia         = pg_fetch_result($result2, $a, 'referencia');
        $descricao          = pg_fetch_result($result2, $a, 'descricao');
        $qtde_pendente      = pg_fetch_result($result2, $a, 'qtde_pendente');

        if ($cor == "#CCCCCC") {
            $cor = "#FFFFFF";
        }
        else {
            $cor = "#CCCCCC";
        }

        $itens_cancelados .= "<tr><td>$referencia - $descricao</td><td>$qtde_pendente</td></tr>";

        $log .= "Pedido: $pedido | Item cancelado: $pedido_item - $qtde_pendente X $referencia - $descricao";
        $log .= "\n";

        $sql = "
        INSERT INTO
        tbl_pedido_cancelado(pedido, posto, fabrica, peca, qtde, motivo, data)
        SELECT
        tbl_pedido.pedido,
        tbl_pedido.posto,
        tbl_pedido.fabrica,
        tbl_peca.peca,
        qtde-qtde_faturada_distribuidor-qtde_cancelada,
        'Cancelado pois excedeu o prazo de atendimento de $dias dias',
        current_date

        FROM
        tbl_pedido_item
        JOIN tbl_pedido ON tbl_pedido_item.pedido=tbl_pedido.pedido
        JOIN tbl_peca ON tbl_pedido_item.peca=tbl_peca.peca

        WHERE
        tbl_pedido_item.pedido_item=$pedido_item
        ";
        $resultX = pg_query($con, $sql);

        if (strlen(pg_last_error($con)) > 0) {
            $msg_erro .= "$sql ";
            $msg_erro .= pg_last_error($con). "\n\n";
            $erro = "*";
        }

        $sql = "UPDATE
        tbl_pedido_item
        SET
        qtde_cancelada=qtde-qtde_faturada_distribuidor
        WHERE
        pedido_item=$pedido_item";
        $resultX = pg_query($con, $sql);

        if (strlen(pg_last_error($con)) > 0) {
            $msg_erro .= "$sql ";
            $msg_erro .=pg_last_error($con). "\n\n";
            $erro = "*";
        }
		if ($erro == "*") {
			$sql = "ROLLBACK TRANSACTION";
			$resultX = pg_query($con, $sql);
		}else{
			$sql = "COMMIT TRANSACTION";
			$resultX = pg_query($con, $sql);
		}
    }
    $itens_cancelados_total .= "<tr><td colspan=2 align=center>Pedido $pedido ($finalizado) - $codigo_posto - $posto_nome</td></tr>$itens_cancelados";
    $mensagem = "<span style=\"font-family: arial; color: #000000; font-size: 12px;\">Informamos que os itens do pedido <b>$pedido</b> finalizado em <b>$finalizado</b>, foram cancelados devido ter expirado o prazo de $dias dias para atendimento. Caso ainda necessite das peças, inseri-las novamente em um novo pedido.<br /><span style=\"color: #ee0000;\">Segue abaixo a lista com os itens cancelados:</span></span><table border=\"1\" style=\"border-collapse: collapse; width: 100%; font-size: 12px;\"><tr><td>Componente</td><td>Qtde Cancelada</td></tr>$itens_cancelados</table>";

    $sql = "
    INSERT INTO
    tbl_comunicado (
        mensagem,
        tipo,
        fabrica,
        posto,
        obrigatorio_site,
        ativo
    )

    VALUES (
        '$mensagem',
        'Comunicado',
        $fabrica,
        $posto,
        't',
        't'
    )
    ";

    $resultX = pg_query($con, $sql);

    if (strlen(pg_last_error($con)) > 0) {
        $msg_erro .= "$sql ";
        $msg_erro .= pg_last_error($con). "\n\n";
        $erro = "*";
    }

    $sql = "SELECT fn_atualiza_status_pedido($fabrica, $pedido)";
    $resultX = pg_query($con, $sql);
    if (strlen(pg_last_error($con)) > 0) {
        $msg_erro .= "$sql ";
        $msg_erro .= pg_last_error($con). "\n\n";
        $erro = "*";
    }


}
$itens_cancelados_total = "<table border=1 width=100%>" . $itens_cancelados_total . "</table>";
fwrite($fp_erro, $msg_erro);
fclose($fp_erro);

fwrite($fp_log, $log);
fclose($fp_log);


if(strlen(trim($log))>0){
    $logClass = new Log2();
    $logClass->adicionaLog(array("titulo" => "$fabrica_nome - Log - Cancelamento de pedidos faturados $dias dias")); // Titulo

    $msg_email_log .= "<font face='arial' color='#000000' size='2'>\n Prezado administrador,<br><br>Alguns itens de pedidos foram cancelados por excederem o prazo para atendimento. Segue abaixo relação:\n</font>\n <br><br>\n  $itens_cancelados_total <br><br>\n Att.<br><br>Suporte Telecontrol\n";

    if ($env == 'producao' ) {
        $logClass->adicionaEmail("jader.abdo@telecontrol.com.br");
        $logClass->adicionaEmail("luis.carlos@telecontrol.com.br");
        $logClass->adicionaEmail("eduardo.oliveira@telecontrol.com.br");
        $logClass->adicionaEmail("eduardo.miranda@telecontrol.com.br");
        //$logClass->adicionaEmail("filipe.souza@esab.com.br");
    } else {
        $logClass->adicionaEmail("lucas.carlos@telecontrol.com.br");
    }

    $logClass->adicionaLog($msg_email_log);
    $logClass->enviaEmails();
}


if(strlen(trim($msg_erro))>0){
    $logClass = new Log2();
    $logClass->adicionaLog(array("titulo" => " $fabrica_nome - Erros ao cancelar pedidos faturados $dias dias")); // Titulo

    $mensagem_erro = "<font face='arial' color='#000000' size='2'>\n";
    $mensagem_erro .= "Ocorreu um erro na rotina que cancela os pedidos faturados não atendidos com mais de $dias dias\n";
    $mensagem_erro .= "<br><br>\n";
    $mensagem_erro .= "</font>\n";

    if ($env == 'producao' ) {
        $logClass->adicionaEmail("helpdesk@telecontrol.com.br");
    } else {
        $logClass->adicionaEmail("lucas.carlos@telecontrol.com.br");
    }

    $logClass->adicionaLog($msg_erro);
    $logClass->enviaEmails();
}

?>

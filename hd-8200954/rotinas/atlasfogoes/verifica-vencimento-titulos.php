<?
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';
require_once "../../class/email/mailer/class.phpmailer.php";

$fabrica = 74;
$vet['fabrica'] = 'atlas';
$vet['tipo']    = 'importaTitulosPagos';
$vet['dest']    = 'helpdesk@telecontrol.com.br';
$vet['log']     = 2;

$data = new DateTime("now");
$diasAntesVencimento = array(3);
$diasVencido = array(7, 10);

$mensagem = array(
    "vencimento" => array(
        "titulo" => "Aten&ccedil;&atilde;o - Alerta Financeiro",
        "msg"    => "Os t&iacute;tulos abaixo possuem vencimento para %data. Atente-se aos prazos, assim evitamos poss&iacute;veis protestos. Caso ainda n&atilde;o tenha recebido o boleto banc&aacute;rio, entre em contato com o Setor Financeiro pelos telefones (46) 2101-1127 ou (46) 2101-1130."
    ),

    "vencido" => array(
        "titulo" => "Aten&ccedil;&atilde;o - Alerta Financeiro",
        "msg"    => "Prezado Autorizado, os t&iacute;tulos mencionados abaixo est&atilde;o vencidos a 7 dias, solicitamos a regulariza&ccedil;&atilde;o da pend&ecirc;ncia dentro do prazo de 3 dias &uacute;teis, excedido esse prazo o documento ser&aacute; protestado e a compra de pe&ccedil;as bloqueada. Caso j&aacute; tenha regularizado esta pend&ecirc;ncia, pedimos para desconsiderar o aviso."
    )
);


/* Verifica se existem títulos à <$dias> dias do vencimento e não estão pagos */
function verificaVencimento($dias){
    global $con;
    global $fabrica;
    global $data;

    switch ($dias) {
        case 3:
            $cond = "AND (vencimento - current_date) = 3 ";
            break;
    }

    $sqlVencimento = "SELECT contas_receber,
                         tbl_contas_receber.posto,
                         tbl_posto.email,
                         documento,
                         vencimento
                    FROM tbl_contas_receber
                    INNER JOIN tbl_posto on tbl_posto.posto = tbl_contas_receber.posto
                    WHERE fabrica = {$fabrica}
                    $cond
                    AND recebimento is null ";
    $res = pg_query($con, $sqlVencimento);

    return pg_fetch_all($res);
}

/* Verifica se existem títulos vencidos à <$dias> dias e não estão pagos */
function verificaVencido($dias){
    global $con;
    global $fabrica;
    global $data;

    switch ($dias) {
        case 7:
            $cond = "AND (vencimento - current_date) = -7 ";
            break;
        
        case 10:
            $cond = "AND (vencimento - current_date) = -10 ";
            break;
    }

    $sqlVencidos = "SELECT contas_receber,
                         tbl_contas_receber.posto,
                         tbl_posto.email,
                         documento,
                         vencimento
                    FROM tbl_contas_receber
                    INNER JOIN tbl_posto on tbl_posto.posto = tbl_contas_receber.posto
                    WHERE fabrica = {$fabrica}
                    $cond
                    AND recebimento is null ";
    $res = pg_query($con, $sqlVencidos);

    return pg_fetch_all($res);

}

function insereComunicado($tipoMsg, $postos, $dataVencimento = null){
    global $con;
    global $fabrica;
    global $mensagem;
    global $vet;
    $titulo = $mensagem[$tipoMsg]["titulo"];
    $msg = $mensagem[$tipoMsg]["msg"];
    if(!empty($dataVencimento)){
        $dataVencimento = new DateTime($dataVencimento);
        $msg = str_replace("%data",$dataVencimento->format("d/m/Y"), $msg);
    }
    foreach($postos as $posto => $arrTitulos){
        $msg .= " Títulos: ". implode(", ", $arrTitulos["titulos"]);

        $insert = "INSERT INTO tbl_comunicado (
                 mensagem,
                 descricao,
                 fabrica,
                 posto,
                 obrigatorio_site,
                 tipo,
                 ativo
                )VALUES(
                 '{$msg}',
                 '{$titulo}',
                 {$fabrica},
                 {$posto},
                 true,
                 'Com. Unico Posto',
                 true
                )";
        $res = pg_query($con, $insert);
        if(!$res){
            Log::envia_email($vet, "Log - Verifica Vencimento de Títulos ATLAS FOGÕES","Erro ao inserir comunicado. 
                                    Posto: ".$posto." ERRO: ".pg_last_error($con));
        }
    }
    

}

function enviaEmail($tipoMsg, $postos, $dataVencimento){
    global $mensagem;
    global $vet;

    $mail = new PHPMailer();
    $mail->IsHTML(true);

    $titulo = $mensagem[$tipoMsg]["titulo"];
    $msg = $mensagem[$tipoMsg]["msg"];

    if(!empty($dataVencimento)){
        $dataVencimento = new DateTime($dataVencimento);
        $msg = str_replace("%data",$dataVencimento->format("d/m/Y"), $msg);
    }

    foreach($postos as $posto => $arrTitulos){

        $msg .= "\n T&iacute;tulos: ". implode(", ", $arrTitulos["titulos"]);

        $mail->AddAddress('otavio.arruda@telecontrol.com.br');
       
        /* $mail->AddAddress($arrTitulos["email"]); */
        $mail->Subject  = $titulo;
        $mail->Body  = $msg;

        $enviado = $mail->Send();

        $mail->ClearAllRecipients();
        $mail->ClearAttachments();

        if ($enviado) {

        } else {
            Log::envia_email($vet, "Log - Verifica Vencimento de Títulos ATLAS FOGÕES","Erro ao enviar email  Erro: ".$mail->ErrorInfo. " Posto: ".$posto);
        }
        
    }
    
}

function bloqueiaPedidoFaturado($postos){
    global $con;
    global $fabrica;
    global $vet;
    if($postos != null and count($postos) > 0){
        foreach($postos as $posto => $arrTitulos){
        
           $update = "UPDATE tbl_posto_fabrica 
                   SET pedido_faturado = false 
                   WHERE posto = {$posto} and 
                   fabrica = $fabrica";

            $res = pg_query($con, $update);
            if(!$res){
                Log::envia_email($vet, "Log - Verifica Vencimento de Títulos ATLAS FOGÕES","Erro ao bloquear pedido faturado  Erro: ".pg_last_error($con). " Posto: ".$posto);
            }
        }
    }
}

function verificaPagos(){
    global $con;
    global $fabrica;
    global $vet;

    $arrVencidosPagos = verificaVencidosPagos(10);
    

    if(count($arrVencidosPagos) > 0 and is_array($arrVencidosPagos)){
        print_r($arrVencidos);
        foreach($arrVencidosPagos as $posto ){
            
            $update = "UPDATE tbl_posto_fabrica set pedido_faturado = true where fabrica = {$fabrica} AND posto = {$posto['posto']}";
            $res = pg_query($con, $update);
            if(!$res){
                Log::envia_email($vet, "Log - Verifica Vencimento de Títulos ATLAS FOGÕES","Erro ao desbloquear pedido faturado  Erro: ".pg_last_error($con). " Posto: ".$posto);
            }

        }


    }
}
/* -> */
pg_query($con, "BEGIN TRANSACTION");
try{
    $pertoVencimento = array();
    foreach($diasAntesVencimento as $dias){
        $titulosPertoVencimento = verificaVencimento($dias);

        foreach($titulosPertoVencimento as $row){

            $posto = $row["posto"];
            $pertoVencimento[$posto]["titulos"][] = $row["documento"];
            $pertoVencimento[$posto]["email"] = $row["email"];
        }

        insereComunicado("vencimento", $pertoVencimento, $titulosPertoVencimento[0]["vencimento"]);
        enviaEmail("vencimento", $pertoVencimento, $titulosPertoVencimento[0]["vencimento"]);
    }

    $vencidos = array();

    foreach($diasVencido as $dias){

        $titulosVencidos = verificaVencido($dias); 

        foreach($titulosVencidos as $row){
            $posto = $row["posto"];
            $vencidos[$posto]["titulos"][] = $row["documento"];
            $vencidos[$posto]["email"] = $row["email"];
        }

        if(count($titulosVencidos) > 0 && is_array($titulosVencidos) ){

            switch($dias){
            case 7:
                insereComunicado("vencido", $vencidos);
                enviaEmail("vencido", $vencidos);
                break;
            case 10:
                bloqueiaPedidoFaturado($vencidos);
                break;
            }
        }

    }


    pg_query($con, "COMMIT TRANSACTION");
}catch(Exception $ex){
    pg_query($con, "ROLLBACK TRANSACTION");
    echo $ex->getMessage();
    Log::envia_email($vet, "Log - Verifica Vencimento de Títulos ATLAS FOGÕES","Erro: ".$ex->getMessage());
}
/* <- */
?>
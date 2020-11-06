<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";
use Posvenda\DistribuidorSLA;
include_once('plugins/fileuploader/TdocsMirror.php');
$tDocs = new TdocsMirror();

$admin_privilegios = "financeiro";
include "autentica_admin.php";

if($telecontrol_distrib || in_array($login_fabrica, [91, 178])) {
  include_once S3CLASS;
  $s3_extrato = new AmazonTC("extrato", (int) $login_fabrica);
  include_once '../class/communicator.class.php';

}

if (isset($_POST['gravaNfeVisualizada'])) {
  
  $extrato = $_POST['extrato'];
  $res['erro'] = false;

  $sqlVisualizaNfe = "INSERT INTO tbl_extrato_status (extrato,data,obs,fabrica)
                      VALUES ({$extrato},current_timestamp,'Nota Fiscal Aprovada',{$login_fabrica});";

    $resVisualizaNfe = pg_query($con, $sqlVisualizaNfe);

  if ($login_fabrica == 91) {

    $sqlExtrato = "SELECT extrato
                   FROM tbl_extrato_pagamento
                   WHERE extrato = $extrato";

    $resExtrato = pg_query($con, $sqlExtrato);
    
    if(strtotime(date('Y-m-10')) < strtotime(date('Y-m-d'))){
        $data = date('Y-m-d', strtotime("+1 month",strtotime(date('Y-m-18'))));
        $data_email = date('d/m/Y', strtotime("+1 month",strtotime(date('Y-m-18'))));
    }else{
        $data = date('Y-m-18');
        $data_email = date('18/m/Y');
    }

    $sqlT = "SELECT total FROM tbl_extrato WHERE extrato = $extrato AND fabrica = $login_fabrica";
    $resT = pg_query($con, $sqlT);
    $liqTtl = (!empty(pg_fetch_result($resT, 0, 'total'))) ? pg_fetch_result($resT, 0, 'total') : 0;

    if (pg_num_rows($resExtrato) > 0) {
    
      $sqlExPag = "UPDATE tbl_extrato_pagamento 
                   SET data_pagamento = '$data',
                   valor_liquido = $liqTtl
                   WHERE extrato = {$extrato}";

    } else {

      $sqlExPag = "INSERT INTO tbl_extrato_pagamento(extrato, data_pagamento, valor_liquido) 
                   VALUES($extrato, '$data', $liqTtl)";
    }

    pg_query($con, $sqlExPag);
    
    if (pg_last_error()) {
      
      exit(json_encode(["erro" => true]));
    }

    $dataAprovacao = date('d/m/Y');
    $infoAdmin = "SELECT nome_completo FROM tbl_admin WHERE admin = $login_admin";
    $infoAdmin = pg_query($con, $infoAdmin);
    $nome = pg_fetch_result($infoAdmin, 0, 'nome_completo');

    $sql_nf = "SELECT tdocs_id FROM tbl_tdocs WHERE referencia_id = $extrato";
    $res_nf = pg_query($con, $sql_nf);

    $tdocs_id = pg_fetch_result($res_nf, 'tdocs_id'); 
    $link = $tDocs->get($tdocs_id)['link'];

    $mensagem = "Nome do Admin: $nome <br> Data da AprovaÁ„o: $dataAprovacao <br> N∫ do Extrato: $extrato <br>
                Data do Pagamento: $data_email<br>
    <a href='$link'>Visualizar Nota fiscal</a>";

    $contato_email = 'anderson.palomo@telecontrol.com.br';
    
    $titulo = 'AprovaÁ„o de Nota Fiscal';

    $mailTc = new TcComm($externalId);
    
    $res = $mailTc->sendMail(
              'nfc-e@wanke.com.br',
              $titulo,
              $mensagem,
              'helpdesk@telecontrol.com.br'
          );

    $emailMsg = 'AprovaÁ„o realizada. Enviamos um e-mail de confirmaÁ„o para nfc-e@wanke.com.br';

    if (!$res) {

      $emailMsg = 'AprovaÁ„o realizada, mas houve erro ao enviar e-mail de confirmaÁ„o';
    } 

    $res['email'] = $emailMsg;

  }  

  if (pg_last_error()) {
    
    exit(json_encode(["erro" => true]));
  } 

  exit(json_encode($res['erro']));
}

if (isset($_POST['desbloquear_extrato'])) {

  $extrato = $_POST['extrato'];

  $sqlDesbloquearExtrato = "INSERT INTO tbl_extrato_status (extrato,data,obs,fabrica)
                            VALUES ({$extrato},current_timestamp,'extrato desbloqueado',{$login_fabrica});

                            UPDATE tbl_extrato SET liberado_telecontrol = current_timestamp, bloqueado = FALSE
                            WHERE extrato = {$extrato};";
  $resDesbloquearExtrato = pg_query($con, $sqlDesbloquearExtrato);

  if (pg_last_error()) {
    exit(json_encode(["erro" => true]));
  } 
  
  exit(json_encode(["erro" => false]));

}

if ($_REQUEST['ajax'] == 'aprovarnf') {
    $xextrato = $_REQUEST['extrato'];
    $retorno = array();

    if (!empty($xextrato)) {

        $sqlNota = "SELECT nf_autorizacao
                    FROM tbl_extrato_pagamento
                    WHERE extrato = {$xextrato}";
        $resNota = pg_query($con, $sqlNota);

         $jsonParametrosAdicionais = json_encode([
            "notaFiscal" => pg_fetch_result($resNota, 0, 'nf_autorizacao')
        ]);


        $updAprova = "UPDATE tbl_extrato_pagamento SET data_entrega_financeiro = now() WHERE extrato = {$xextrato};";
        $resAprova = pg_query($con, $updAprova);

        $sqlStatus = "INSERT INTO tbl_extrato_status (extrato,data,obs,fabrica,admin_conferiu, pendente, parametros_adicionais) 
                      VALUES ({$xextrato},current_timestamp,'Nota Fiscal Aprovada',{$login_fabrica},{$login_admin}, false, '{$jsonParametrosAdicionais}')";
        $resStatus = pg_query($con, $sqlStatus);

        if (strlen(pg_last_error()) > 0) {
            $retorno = array("erro" => utf8_encode("Ocorreu um erro atualizando dados de aprovaÁ„o"));
        } else {
            $retorno = array("sucesso" => "Nota fiscal aprovada com sucesso");
        }
    } else {
        $retorno = array("erro" => utf8_encode("Extrato n„o encontrado para atualizar aprovaÁ„o da Nota fiscal"));
    }
    echo json_encode($retorno);
    exit;
}

if ($_REQUEST['ajax'] == 'reprovarnf') {
    $xextrato = $_REQUEST['extrato'];
    $retorno = array();

    if (!empty($xextrato)) {
        pg_query($con, "UPDATE tbl_extrato SET nf_recebida = FALSE WHERE extrato = {$xextrato};");

        pg_query($con, "UPDATE tbl_extrato_pagamento SET data_recebimento_nf = NULL, data_nf = NULL, nf_autorizacao = '' WHERE extrato = {$xextrato};");

        if (strlen(pg_last_error()) > 0) {
            $retorno = array("erro" => utf8_encode("Ocorreu um erro ao reprovar NF do posto"));
        } else {
            $retorno = array("sucesso" => "Nota fiscal reprovada");
        }
    } else {
        $retorno = array("erro" => utf8_encode("Extrato n„o encontrado para atualizar informaÁıes"));
    }
    echo json_encode($retorno);
    exit;
}

if (isset($_POST['liberar_todos']) && $_POST['liberar_todos'] == true) {
  
  $ms = "";
  $i = "";
  $extrato = "";

  if (isset($_POST['posicao'])) {
    $i = $_POST['posicao'];
  }

  if (isset($_POST['extrato'])) {
    $extrato = $_POST['extrato'];
  }

    $extrato_km_pendente = array();
    $msg_erro = "";

    $res = pg_query ($con,"BEGIN TRANSACTION");
      $km_pendente = false;

        //HD 237498: Barrar liberaÁ„o de Extrato caso tenha OS em IntervenÁ„o de KM
        if (in_array($login_fabrica, $intervencao_km_extrato) && $extrato) {
            $km_pendente = verifica_km_pendente_extrato($extrato);
        }
        else {
            $km_pendente = false;
        }

        if ($km_pendente) { 
            $extrato_km_pendente[] = $extrato;
        } else {
            if (strlen($extrato) > 0 AND strlen($msg_erro) == 0) {

                $sql = "UPDATE tbl_extrato SET liberado = current_date, admin = $login_admin ";

                //HD 205958: N„o pode aprovar nenhum extrato na liberaÁ„o, È uma falha no conceito do negÛcio.
                //           antes de atender qualquer solicitaÁ„o das f·bricas concernentes a isto, verificar conceitos
                //           definidos neste chamado. Apagadas 3 linhas abaixo, verificar nao_sync caso necess·rio
                if (in_array($login_fabrica, $fabricas_acerto_extrato)) {
                } else if (in_array($login_fabrica,array(6,7,11,30,14,15,24,25,35,43,45,46,50,51,59,66,74,80,52,85,88,94,99,90,91)) or $login_fabrica > 99) {
                    //HD 205958: Este conceito est· errado, um extrato nunca pode ser aprovado na liberaÁ„o. Esta linha
                    //           est· aqui provisÛriamente enquanto arrumamos os conceitos das f·bricas
                    $sql .= ", aprovado = current_timestamp ";
                }

                $sql .= "WHERE  tbl_extrato.extrato = $extrato
                         and    tbl_extrato.fabrica = $login_fabrica";
                         //echo $sql;

                $res = pg_query($con,$sql);
                $msg_erro = @pg_errormessage($con);

                //Wellington 14/12/2006 - ENVIA EMAIL PARA O POSTO QDO O EXTRATO √â LIBERADO
                //IGOR HD 17677 - 04/06/2008 
                if (strlen($msg_erro) == 0 && ( in_array($login_fabrica, array(11,172))) ) {
                    include 'email_comunicado.php'; // FunÁıes para enviar e-mail e inserir comunicado para o Posto
                    $sql = "SELECT CASE
                                    WHEN contato_email IS NULL
                                        THEN tbl_posto.email
                                    ELSE contato_email
                                    END AS email, tbl_posto_fabrica.posto FROM tbl_posto_fabrica
                                JOIN tbl_extrato USING (posto,fabrica)
                                JOIN tbl_posto ON tbl_extrato.posto = tbl_posto.posto
                            WHERE extrato = $extrato";
                    $res = pg_query($con,$sql);

        //          Se tem aviso, pega o valor, tanto se foi por GET como POST...
                    $msg_aviso    = (isset($_REQUEST['msg_aviso']))?"AVISO: ".$_REQUEST['msg_aviso']."<BR><BR><BR>":"";
                    $xposto       = trim(pg_fetch_result($res,0,posto));
                    $destinatario = trim(pg_fetch_result($res,0,email));
                    $assunto      = "SEU EXTRATO (N∫ $extrato) FOI LIBERADO";
                    $mensagem     =  "* O EXTRATO N∫".$extrato." EST√Å LIBERADO NO TELECONTROL<br><br>".$msg_aviso ;

          $sql   = "SELECT email
          FROM tbl_admin
          WHERE tbl_admin.admin = {$login_admin}";

          $res   = pg_query($con,$sql);
          $email_admin = pg_fetch_result($res,0,'email');

          $r_email    = pg_fetch_result($res,0,'email');
          $remetente  = "Lenoxx";
                    $headers    = "Return-Path:$r_email \nFrom:".$remetente.
                                  " $r_email\nBcc:$r_email \nContent-type: text/html\n";

                    enviar_email($r_email, utf8_encode($destinatario), utf8_encode($assunto), $mensagem, $remetente, $headers, true);
                    gravar_comunicado("Extrato disponÌvel", $assunto, $mensagem, $xposto, true);
                }
            }

            //wellington liberar
            /* LENOXX - SETA EXTRATO DE DEVOLU«√O PARA OS FATURAMENTOS */
            /*IGOR HD 17677 - 04/06/2008 */
            if (strlen($extrato) > 0 && strlen($msg_erro) == 0 && ( in_array($login_fabrica, array(11,25,172)) )) {

                $sql = "SELECT TO_CHAR(data_geracao-interval '1 month','YYYY-MM-21') AS data_limite
                        FROM tbl_extrato
                        WHERE extrato = $extrato;";
                $res = pg_query($con,$sql);
                $data_limite_nf = trim(pg_fetch_result($res,0,data_limite));

                $sql = "UPDATE tbl_faturamento SET extrato_devolucao = $extrato
                        WHERE  tbl_faturamento.fabrica = $login_fabrica
                        AND    tbl_faturamento.posto   = $xposto
                        AND    tbl_faturamento.extrato_devolucao IS NULL
                        AND    tbl_faturamento.emissao >  '2007-08-30'
                        AND    tbl_faturamento.emissao < '$data_limite_nf'
                        AND    (tbl_faturamento.cfop ILIKE '%59%' OR tbl_faturamento.cfop ILIKE '%69%')
                        ";
                $res = pg_query($con,$sql);

                $sql = "DELETE FROM tbl_extrato_lgr WHERE extrato = $extrato";
                $res = pg_query($con,$sql);

                $sql = "INSERT INTO tbl_extrato_lgr (extrato, posto, peca, qtde) (
                    SELECT tbl_extrato.extrato, tbl_extrato.posto, tbl_faturamento_item.peca, SUM (tbl_faturamento_item.qtde)
                    FROM tbl_extrato
                    JOIN tbl_faturamento      ON tbl_extrato.extrato         = tbl_faturamento.extrato_devolucao
                    JOIN tbl_faturamento_item ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                    WHERE tbl_extrato.fabrica = $login_fabrica
                    AND   tbl_extrato.extrato = $extrato
                    GROUP BY tbl_extrato.extrato, tbl_extrato.posto, tbl_faturamento_item.peca
                    ) ;";
                $res = pg_query($con,$sql);
            }
        }

    if(strlen($msg_erro) == 0){
      $liberado = true;
      $ms = 'liberado';
      $res = pg_query ($con,"COMMIT TRANSACTION");
    }else{
      $ms = 'erro';
      $res = @pg_query ($con,"ROLLBACK TRANSACTION");
    }
  
    //HD 237498: Esta mensagem de erro tem que ficar depois do commit/rollback, pois È apenas informativa, n„o deve impedir que a transacao se concretize
    if (count($extrato_km_pendente)) {
        $extrato_km_pendente = implode(", ", $extrato_km_pendente);
        $msg_erro = "ATEN√á√ÉO: Os extratos a seguir possuem OS em IntervenÁ„o de KM sem aprovaÁ„o/reprovaÁ„o e n„o ser„o liberados atÈ que seja definida uma posiÁ„o da f·brica em relaÁ„o a esta intervenÁ„o.<br>
        Extratos n„o liberados: $extrato_km_pendente";
    }

    echo $ms;
    exit;
}

if($_POST['conferido'] == true){
  $extrato = $_POST['extrato'];
  $sql = "INSERT INTO tbl_extrato_status (data, pendente, conferido, admin_conferiu, obs, extrato, fabrica) values (now(), false, now(), $login_admin, 'Confirmado por admin', $extrato, $login_fabrica)";
  $res = pg_query($con, $sql);
  if(strlen(pg_last_error())==0){
    echo "ok"; 
  }else{
    echo "erro"; 
  }

  exit;
}


if($_POST["recusado"] == true){
  $extrato  = $_POST['extrato'];
  $motivo   = $_POST['motivo'];
  $posto   = $_POST['posto'];

  $sql = "INSERT INTO tbl_extrato_status (data, pendente, conferido, admin_conferiu, obs, extrato, fabrica) values (now(), true, now(), $login_admin, 'Recusado por admin, motivo: $motivo', $extrato, $login_fabrica)";
  $res = pg_query($con, $sql);
  if(strlen(pg_last_error())==0){

    $sql= "INSERT INTO tbl_comunicado (fabrica, tipo, posto, obrigatorio_os_produto, obrigatorio_site, ativo, descricao, mensagem) values ($login_fabrica, 'Boletim', $posto, 'f', 't', 't', 'Extrato Recusado', 'NFe do extrato $extrato foi recusada pela f·brica, Motivo: $motivo')";
    $res = pg_query($con, $sql);

    $sql_nf_recebida = " update  tbl_extrato set nf_recebida = 'f' where extrato = $extrato";
    $res_nf_recebida = pg_query($con, $sql_nf_recebida);

    $sql_posto = "SELECT
                  tbl_posto_fabrica.contato_email
                  FROM
                  tbl_extrato
                  INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_extrato.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                  where extrato = $extrato";
    $res_posto = pg_query($con, $sql_posto);
    if(pg_num_rows($res_posto)>0){
        $contato_email = pg_fetch_result($res_posto, 0, contato_email);
        $mensagem = "Recusado por admin, motivo: $motivo";
        $mailTc = new TcComm($externalId);
        $res = $mailTc->sendMail(
          $contato_email,
          "Extrato Recusado $extrato",
          $mensagem,
          'helpdesk@telecontrol.com.br'
        );
    }
    $s3_extrato->deleteObject("$extrato-nota_fiscal_servico.pdf");

    echo "ok";
  }else{
    echo "erro";
  }
  exit;
}

if(isset($_POST["lgr_provisorio"])){

  $extrato = $_POST["extrato"];
  $status = $_POST["status"];

  if(strlen($extrato) > 0){

    if($status == "checked"){

      $sql = "UPDATE tbl_extrato SET admin_libera_pendencia = {$login_admin}, data_libera_pendencia = current_timestamp WHERE extrato = {$extrato} AND fabrica = {$login_fabrica}";

    }else{
      $sql = "UPDATE tbl_extrato SET admin_libera_pendencia = NULL, data_libera_pendencia = NULL WHERE extrato = {$extrato} AND fabrica = {$login_fabrica}";
    }

    $res = pg_query($con, $sql);


    $msg = (strlen(pg_last_error()) > 0) ? "Erro ao liberar LGR para esse extrato - {#extrato}" : "Extrato liberado LGR com sucesso - {$extrato}";
    exit($msg);

  }else{

    exit("Extrato n„o informado");

  }

}

if(isset($_POST["inibir_extrato"]) && $login_fabrica == 1){

    $inibir = $_POST["inibir"];
    $extrato = $_POST["extrato"];

    $inibir = ($inibir == "true") ? "baixado = CURRENT_DATE" : "baixado = null";

    $sql = "UPDATE tbl_extrato_extra SET {$inibir} WHERE extrato = {$extrato}";
    $res = pg_query($con, $sql);

    if(strlen(pg_last_error()) > 0){
        $dados = array("erro" => utf8_encode(pg_last_error()));
    }else{
        $dados = array("sucesso" => true);
    }

    exit(json_encode($dados));

}

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if(isset($_POST['aprovarCheck']) AND $_POST['aprovarCheck'] == 'ok'){
    //    $nf_mao_obra = $_POST['val'];

    $nf_mao_obra = $_POST['nf_mao_obra'];
    $nf_devolucao = $_POST['nf_devolucao'];
    $entrega_transportadora = $_POST['entrega_transportadora'];
    $extrato = $_POST['extrato'];


    $entrega_transportadora = str_replace (" " , "" , $entrega_transportadora);
    $entrega_transportadora = str_replace ("-" , "" , $entrega_transportadora);
    $entrega_transportadora = str_replace ("/" , "" , $entrega_transportadora);
    $entrega_transportadora = str_replace ("." , "" , $entrega_transportadora);

    if (strlen ($entrega_transportadora) == 6) {
        $entrega_transportadora = "'".substr ($entrega_transportadora,0,4) . "20" . substr ($entrega_transportadora,4,2)."'";
    }

    if (strlen ($entrega_transportadora) > 0) {
        $entrega_transportadora = substr ($entrega_transportadora,0,2) . "/" . substr ($entrega_transportadora,2,2) . "/" . substr ($entrega_transportadora,4,4);
        if (strlen ($entrega_transportadora) < 8) $entrega_transportadora = date ("d/m/Y");
        $entrega_transportadora = "'".substr ($entrega_transportadora,6,4) . "-" . substr ($entrega_transportadora,3,2) . "-" . substr ($entrega_transportadora,0,2)."'";
        } else {
        $entrega_transportadora = 'null';
    }

    $res = pg_query($con,"BEGIN TRANSACTION");

    $sqlAprova = "SELECT tbl_extrato.extrato,
                    tbl_extrato.posto,
                    tbl_posto.nome,
                    tbl_posto.cnpj
                FROM tbl_extrato
                JOIN tbl_posto_fabrica ON tbl_extrato.posto = tbl_posto_fabrica.posto
                JOIN tbl_posto ON tbl_extrato.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                WHERE tbl_extrato.extrato = $extrato";
    $resAprova = pg_query($con,$sqlAprova);

    $posto                          = pg_fetch_result($resAprova, 0, posto);
    $posto_nome                     = pg_fetch_result($resAprova, 0, nome);
    $cnpj                           = pg_fetch_result($resAprova, 0, cnpj);

    if(strlen($nf_devolucao) == 0){
        $nf_devolucao = 'null';
    }

    if(strlen($nf_mao_obra) == 0 ){
        $nf_mao_obra = 'null';
    }

    $sql = "UPDATE tbl_extrato_extra SET
                nota_fiscal_mao_de_obra     = '$nf_mao_obra',
                nota_fiscal_devolucao       = '$nf_devolucao',
                data_entrega_transportadora = $entrega_transportadora
            WHERE extrato = $extrato";

    $res = pg_query($con,$sql);

    # Estava comentado , entao descomentei. Pq comentaram?  N„o tem a explicacao.
    # Estou liberando. HD 4846
    //HD 145478 - Gravando quem aprovou o extrato

    $sql = "
    UPDATE
    tbl_extrato_extra

    SET
    admin = $login_admin

    WHERE
    extrato = $extrato
    ";
    $res = pg_query($con, $sql);

    $sql = "SELECT fn_aprova_extrato($posto,$login_fabrica,$extrato)";
    $res = pg_query($con,$sql);
    $msg_erro = pg_errormessage($con);

    if (strlen ($msg_erro) == 0) {
        $res = pg_query ($con,"COMMIT TRANSACTION");
        echo $extrato; exit;
    }else{
        $res = @pg_query ($con,"ROLLBACK TRANSACTION");
        echo $msg_erro; exit;
    }

}

function verifica_extrato_bloqueado($posto, $extrato, $data_geracao = null) {
	global $login_fabrica, $con;

	$sqlExtratoBloqueado = "SELECT * FROM tbl_extrato WHERE extrato = {$extrato} AND bloqueado IS TRUE;";
        $resExtratoBloqueado = pg_query($con, $sqlExtratoBloqueado);

        $sqlVerificaExtratoLiberado = "SELECT extrato FROM tbl_extrato_status WHERE extrato = {$extrato} AND obs = 'extrato desbloqueado';";
        $resVerificaExtratoLiberado = pg_query($con, $sqlVerificaExtratoLiberado);

        if (pg_num_rows($resVerificaExtratoLiberado) > 0) {
                return false;
        }

        if (pg_num_rows($resExtratoBloqueado) == 0) {
                return false;
        }

	return true;
}

function exibeAgrupar($extrato) {
  global $con;

  $sqlExibe = "SELECT extrato FROM tbl_extrato_pagamento WHERE extrato = $extrato";
  $resExibe = pg_query($con, $sqlExibe);
  
  if (pg_num_rows($resExibe) == 0) {
    return true;
  }

  return false;
}

function getThead($login_fabrica){

    if($login_fabrica == 148){
        return "<thead>
                  <tr>
                    <th>Data</th>
                    <th>N∫ Extrato</th>
                    <th>Posto</th>
                    <th>N∫ OS</th>
                    <th>Produto</th>
                    <th>N∫ SÈrie</th>
                    <th>Valor MO</th>
                    <th>Valor KM</th>
                    <th>Valor Total</th>
                    <th>Consumidor</th>
                  </tr>
                </thead>";
    }else if ($login_fabrica == 35) {
        return "<thead>
                   <tr >
                     <th> CÛdigo</th>
                     <th> Nome do Posto</th>
                     <th> UF</th>
                     <th> Cidade</th>
                     <th> Extrato</th>
                     <th> Data Extrato</th>
                     <th> Data Baixa</th>
                     <th> Aprovado</th>
                     <th> Qtde OS</th>
                     <th> Total</th>
                     <th> Status</th>
                   </tr>
               </thead>";
    }else if($login_fabrica == 86){
        return "<thead>
                   <tr >
                   	   <th> CÛdigo</th>
                	   <th> Nome do Posto</th>
    		   <th> UF</th>
                	   <th> Extrato</th>
    		   <th> Marca</th>
    		   <th> Data</th>
    		   <th> Qtde OS</th>
    		   <th> Total</th>
    		   <th> Data de Baixa</th>
          	       </tr>
               </thead>";
    }else if ($login_fabrica == 1) {
        return "
            <thead>
                <tr>
                    <th bgcolor='#596d9b'><font color='#ffffff'>CÛdigo</font></th>
                    <th bgcolor='#596d9b'><font color='#ffffff'>Nome Posto</font></th>
                    <th bgcolor='#596d9b'><font color='#ffffff'>UF</font></th>
                    <th bgcolor='#596d9b'><font color='#ffffff'>Credenciamento</font></th>
                    <th bgcolor='#596d9b'><font color='#ffffff'>Tipo</font></th>
                    <th bgcolor='#596d9b'><font color='#ffffff'>Protocolo</font></th>
                    <th bgcolor='#596d9b'><font color='#ffffff'>Data</font></th>
                    <th bgcolor='#596d9b'><font color='#ffffff'>Qtde OS</font></th>
                    <th bgcolor='#596d9b'><font color='#ffffff'>Total PeÁa</font></th>
                    <th bgcolor='#596d9b'><font color='#ffffff'>Total MO</font></th>
                    <th bgcolor='#596d9b'><font color='#ffffff'>Total Avulso</font></th>
                    <th bgcolor='#596d9b'><font color='#ffffff'>Total Geral</font></th>
                    <th bgcolor='#596d9b'><font color='#ffffff'>". ((in_array($login_fabrica, array(157)) ? "Previs„o de Pagamento" : "Data Baixa")) ."</font></th>
                    <th bgcolor='#596d9b'><font color='#ffffff'>Tipo Envio</font></th>
                </tr>
            </thead>
        ";
    }elseif($login_fabrica == 131){
        return "<thead>
                   <tr >
                       <th> CÛdigo</th>
                       <th> Nome do Posto</th>
                       <th> UF</th>
                       <th> Extrato</th>
                       <th> Data</th>
                       <th> Qtde OS</th>                       
                       <th> Total Geral</th>
                       <th> Data da Baixa</th>
                   </tr>
               </thead>";
    }else if ($login_fabrica == 183){
        return "
            <thead>
                <tr>
                    <th bgcolor='#596d9b'><font color='#ffffff'>CÛdigo</font></th>
                    <th bgcolor='#596d9b'><font color='#ffffff'>Nome Posto</font></th>
                    <th bgcolor='#596d9b'><font color='#ffffff'>CÛdigo Fornecedor</th>
                    <th bgcolor='#596d9b'><font color='#ffffff'>UF</font></th>
                    <th bgcolor='#596d9b'><font color='#ffffff'>Extrato</font></th>
                    <th bgcolor='#596d9b'><font color='#ffffff'>Data</font></th>
                    <th bgcolor='#596d9b'><font color='#ffffff'>Qtde OS</font></th>
                    <th bgcolor='#596d9b'><font color='#ffffff'>Total MO</font></th>
                    <th bgcolor='#596d9b'><font color='#ffffff'>Total KM</font></th>
                    <th bgcolor='#596d9b'><font color='#ffffff'>Valor Adicional</font></th>
                    <th bgcolor='#596d9b'><font color='#ffffff'>Total</font></th>
                    <th bgcolor='#596d9b'><font color='#ffffff'>Data Baixada</font></th>
                </tr>
            </thead>
        ";
    } else {
        return "<thead>
                   <tr >
                       <th> CÛdigo</th>
                       <th> Nome do Posto</th>
                       <th> UF</th>
                       <th> Extrato</th>
                       <th> Data</th>
                       <th> Qtde OS</th>
                       <th> Total Cortesia</th>
                       <th> Total Geral</th>
                       <th> N.F M„o de Obra</th>
                       <th> N.F Remessa</th>
                       <th> Data Coleta</th>
                       <th> Entrega Transportadora</th>
                       <th> Auditado em</th>
                       <th> Auditor</th>
                       <th> Valores Adicionais</th>
                   </tr>
               </thead>";
    }
}

function getTbody ($res,$login_fabrica)
{
  global $con;

    $tbody = "<tbody>";

    for ($i = 0; $i < pg_num_rows($res); $i++) {

        $result = pg_fetch_object($res, $i);
        $extratoBaixado = (!empty($result->baixado)) ? "Pago" : "Pendente";

        $rs_extrato = $result->extrato;

        if($login_fabrica == 148){
          $sql_x = "SELECT DISTINCT
                      tbl_os.os,
                      tbl_extrato.data_geracao,
                      tbl_posto.nome,
                      tbl_os.consumidor_nome,
                      tbl_produto.produto,
                      tbl_os_produto.serie,
                      tbl_produto.referencia,
                      tbl_produto.produto,
                      tbl_produto.descricao,
                      tbl_posto_fabrica.valor_km,
                      tbl_posto_fabrica.conta_contabil,
                      tbl_os.mao_de_obra as mo,
                      tbl_os.qtde_km_calculada as km_calculado,
                      (tbl_os.mao_de_obra + tbl_os.qtde_km_calculada) AS valor                      
                      FROM tbl_extrato
                      JOIN tbl_posto ON tbl_posto.posto = tbl_extrato.posto
                      JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}                      
                      LEFT JOIN tbl_os_extra ON tbl_os_extra.extrato = tbl_extrato.extrato 
                      LEFT JOIN tbl_os ON tbl_os.os = tbl_os_extra.os 
                      LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os 
                      LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto   
                      WHERE tbl_extrato.fabrica = {$login_fabrica} 
                      AND tbl_extrato.extrato = {$rs_extrato}";

          //die(nl2br($sql_x));
          $res_sql_x = pg_query($con, $sql_x);        

          for($a=0; $a<pg_num_rows($res_sql_x); $a++){
            $result_sql = pg_fetch_object($res_sql_x, $a);
            
            //setlocale(LC_ALL, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
            //$data_x = date("M/y", strtotime($result_sql->data_geracao));
            //$data_x = ucfirst(utf8_encode(strftime("%B/%y", strtotime($result_sql->data_geracao))));

            $mes_atual = date("m", strtotime($result_sql->data_geracao));           
            $ano_atual = date("y", strtotime($result_sql->data_geracao));           

            $data_meses = array(
                              "01" => "Janeiro",
                              "02" => "Fevereiro",
                              "03" => "Marco",
                              "04" => "Abril",
                              "05" => "Maio",
                              "06" => "Junho",
                              "07" => "Julho",
                              "08" => "Agosto",
                              "09" => "Setembro",
                              "10" => "Outubro",
                              "11" => "Novembro",
                              "12" => "Dezembro");           

            foreach ($data_meses as $key => $valor) {
              if($key == $mes_atual){
                $data_x = $valor."/";
                $data_x .= $ano_atual;
              }
            }

            $tbody .= "<tr>";
            $tbody .= "<td>" . $data_x . "</td>";
            $tbody .= "<td>" . $result->extrato . "</td>";
            $tbody .= "<td>" . $result->nome . "</td>";          
            $tbody .= "<td>" . $result_sql->os . "</td>"; 
            if($login_fabrica == 148)  {
              $tbody .= "<td>" . $result_sql->referencia . "</td>";     
            } else {
              $tbody .= "<td>" . $result_sql->descricao . "</td>";   
            }
            $tbody .= "<td>" . $result_sql->serie . "</td>";
            if($login_fabrica == 148)   {
              $tbody .= "<td>R$ " . number_format($result_sql->mo,2,',','.') . "</td>";    
              $tbody .= "<td>" . number_format($result_sql->valor_km,2,',','.') . "</td>";
              $tbody .= "<td>R$ " . number_format($result_sql->valor,2,',','.') . "</td>";    
            } else {
              $tbody .= "<td>R$ " . number_format($result_sql->valor,2,',','.') . "</td>";    
            }            
            $tbody .= "<td>" . $result_sql->consumidor_nome . "</td>";   
            $tbody .= "</tr>";
          }
        } else if($login_fabrica == 35){
            $tbody .= "<tr>";
            $tbody   .= "<td>".$result->codigo_posto ."</td>";
            $tbody   .= "<td>".$result->nome."</td>";
            $tbody   .= "<td>".$result->estado ."</td>";
            $tbody   .= "<td>".$result->cidade_posto ."</td>";
            $tbody   .= "<td>".$result->extrato ."</td>";
            $tbody   .= "<td>".$result->data_geracao."</td>";
            $tbody   .= "<td>".$result->baixado."</td>";
            $tbody   .= "<td>".$result->aprovado."</td>";
            $tbody   .= "<td>".getQtdeOS($result->extrato)."</td>";
            $tbody   .= "<td>".number_format ($result->total,2,',','.')."</td>";
            $tbody   .= "<td>".$extratoBaixado."</td>";
            $tbody .= "</tr>";
        } else if($login_fabrica == 86){
            $tbody .= "<tr>";
    		$tbody	 .= "<td >".$result->codigo_posto ."</td>";
    		$tbody	 .= "<td >".$result->nome."</td>";
    		$tbody	 .= "<td >".$result->estado ."</td>";
    		$tbody	 .= "<td >".$result->extrato ."</td>";
    		$tbody	 .= "<td >".mostraMarcaExtrato($result->extrato) ."</td>";
    		$tbody	 .= "<td >".$result->data_geracao."</td>";
    		$tbody	 .= "<td >".getQtdeOS($result->extrato)."</td>";
    		$tbody	 .= "<td >".$result->total ."</td>";
    		$tbody	 .= "<td >".$result->baixado ."</td>";
            $tbody .= "</tr>";
        } else if ($login_fabrica == 1) {
            $tbody  .= "<tr>";
            $tbody  .= "<td >".$result->codigo_posto ."</td>";
            $tbody  .= "<td >".$result->nome ."</td>";
            $tbody  .= "<td >".$result->estado ."</td>";
            $tbody  .= "<td >".$result->credenciamento ."</td>";
            $tbody  .= "<td >".$result->tipo_posto ."</td>";
            $tbody  .= "<td >".$result->protocolo ."</td>";
            $tbody  .= "<td >".$result->data_geracao ."</td>";
    		$tbody	 .= "<td >".getQtdeOS($result->extrato)."</td>";
    		$tbody	 .= "<td >".getTotalPecas($result->extrato)."</td>";
    		$tbody	 .= "<td >".getTotalMO($result->extrato)."</td>";
    		$tbody	 .= "<td >".getTotalAvulso($result->extrato)."</td>";
    		$tbody	 .= "<td >R$".number_format($result->total,'2',',','.') ."</td>";
    		$tbody	 .= "<td >".$result->baixado ."</td>";
    		$tbody	 .= "<td >".getTipoEnvio($result->extrato,$result->posto,$login_fabrica) ."</td>";
        } elseif($login_fabrica == 131){
            $tbody .= "<tr>";
              $tbody   .= "<td >".$result->codigo_posto ."</td>";
              $tbody   .= "<td >".$result->nome."</td>";
              $tbody   .= "<td >".$result->estado ."</td>";
              $tbody   .= "<td >".$result->extrato ."</td>";
              $tbody   .= "<td >".$result->data_geracao."</td>";
              $tbody   .= "<td >".getQtdeOS($result->extrato)."</td>";
              $tbody   .= "<td >".$result->total ."</td>";
              $tbody   .= "<td >".$result->baixado ."</td>";              
              $tbody .= "</tr>";
        } else if ($login_fabrica == 183){
            $tbody .= "<tr>";
                $tbody   .= "<td>".$result->codigo_posto ."</td>";
                $tbody   .= "<td>".$result->nome."</td>";
                $tbody   .= "<td>".$result->conta_contabil."</td>";                
                $tbody   .= "<td>".$result->estado ."</td>";
                $tbody   .= "<td>".$result->extrato ."</td>";
                $tbody   .= "<td>".$result->data_geracao."</td>";
                $tbody   .= "<td>".getQtdeOS($result->extrato)."</td>";
                $tbody     .= "<td>".getTotalMO($result->extrato)."</td>";
                $tbody   .= "<td>R$ ".number_format ($result->deslocamento,2,',','.')."</td>";
                $tbody   .= "<td>R$ ".number_format ($result->valor_adicional,2,',','.')."</td>";
                $tbody   .= "<td>R$ ".number_format($result->total,'2',',','.') ."</td>";;
                $tbody   .= "<td>".$result->baixado ."</td>";              
            $tbody .= "</tr>";
        } else {

            if(strlen($result->aprovado) == 0){
                $aprovar = "Aprovar";
            }else{
                $aprovar = "Aprovado";
            }

            $tbody .= "<tr>";
            $tbody   .= "<td >".$result->codigo_posto ."</td>";
            $tbody   .= "<td >".$result->nome."</td>";
            $tbody   .= "<td >".$result->estado ."</td>";
            $tbody   .= "<td >".$result->extrato ."</td>";
            $tbody   .= "<td >".$result->data_geracao."</td>";
            $tbody   .= "<td >".getQtdeOS($result->extrato)."</td>";
            $tbody   .= "<td >".$result->total_cortesia."</td>";
            $tbody   .= "<td >".$result->total ."</td>";
            $tbody   .= "<td >".$result->nota_fiscal_mao_de_obra ."</td>";
            $tbody   .= "<td >".$result->nota_fiscal_devolucao ."</td>";
            $tbody   .= "<td >".$result->data_coleta ."</td>";
            $tbody   .= "<td >".$result->data_entrega_transportadora ."</td>";
            $tbody   .= "<td >".$result->aprovado ."</td>";
            $tbody   .= "<td >".$result->nome_completo ."</td>";
            $tbody   .= "<td >".$aprovar ."</td>";
            $tbody .= "</tr>";
        }
    }
    $tbody .= "</tbody>";
    return $tbody;
}

function getTFoot($res){
    return "<tfoot>
               <tr>
                   <td> Total de Registros: ".pg_num_rows($res). "</td>
              </tr>
            </tfoot>";
}
function montaArquivo($fp, $res,$login_fabrica){
    $tHead = "<table border=1>". getThead($login_fabrica);
    $tBody = getTbody($res,$login_fabrica);
    $tFoot = getTFoot($res);

    fwrite($fp, $tHead.$tBody.$tFoot);
}

function getTotalPecas($extrato)
{
    global $con;

    $sql = "SELECT  SUM(tbl_os.pecas)       AS total_pecas
            FROM    tbl_os
            JOIN    tbl_os_extra    USING (os)
            JOIN    tbl_extrato     ON  tbl_extrato.extrato     = tbl_os_extra.extrato
            WHERE   tbl_os_extra.extrato = $extrato
    ";

    $res = pg_query($con,$sql);

    $total = pg_fetch_result($res,0,total_pecas);

    return "R$ ".number_format($total,2,',','.');
}

function getTotalMO($extrato)
{
    global $con;

    $sql = "SELECT  SUM(tbl_os.mao_de_obra) AS total_maodeobra
            FROM    tbl_os
            JOIN    tbl_os_extra    USING (os)
            JOIN    tbl_extrato     ON  tbl_extrato.extrato     = tbl_os_extra.extrato
            WHERE   tbl_os_extra.extrato = $extrato
    ";
    $res = pg_query($con,$sql);

    $total = pg_fetch_result($res,0,total_maodeobra);

    return "R$ ".number_format($total,2,',','.');
}

function getTotalAvulso($extrato)
{
    global $con;

    $sql = "SELECT  tbl_extrato.avulso      AS total_avulso
            FROM    tbl_os
            JOIN    tbl_os_extra    USING (os)
            JOIN    tbl_extrato     ON  tbl_extrato.extrato     = tbl_os_extra.extrato
            WHERE   tbl_os_extra.extrato = $extrato
    ";
    $res = pg_query($con,$sql);

    $total = pg_fetch_result($res,0,total_avulso);

    return "R$ ".number_format($total,2,',','.');
}

function getTipoEnvio($extrato,$posto,$login_fabrica)
{
    global $con;

    $obs = verificaTipoGeracao($extrato);
    $dadosGeracao = json_decode($obs);

    if(isset($dadosGeracao->tipo_de_envio) && strlen($dadosGeracao->tipo_de_envio) > 0){

        $tipo_envio = $dadosGeracao->tipo_de_envio;
    } else {

        $sql = "SELECT tipo_envio_nf
                FROM tbl_tipo_gera_extrato
                WHERE fabrica = $login_fabrica
                AND posto = $posto";

        $res2 = pg_query($con, $sql);

        if (pg_num_rows($res2) > 0) {
            $tipo_envio = str_replace("_"," ",pg_fetch_result($res2, 0, 'tipo_envio_nf'));
        }
    }

    return $tipo_envio;
}

/* ver admin/conta_os_ajax.php  */
function getQtdeOS($extrato){
    global $con;

    $sql = "SELECT count(*) as qtde_os FROM tbl_os_extra WHERE extrato = $extrato";
    $res = pg_query($con,$sql);

    return pg_fetch_result($res, 0, "qtde_os");
}
function getCamposGroupThermoSystem(){
    return "  GROUP BY PO.posto ,
              PO.nome ,
              PO.cnpj ,
              PF.contato_estado ,
              PF.contato_email  ,
              PF.credenciamento ,
              PF.codigo_posto ,
              PF.distribuidor ,
              PF.imprime_os ,
              TP.descricao  ,
              EX.extrato ,
              EX.bloqueado ,
              EX.liberado ,
              EX.estoque_menor_20 ,
              EX.aprovado,
              EX.protocolo,
              EX.data_geracao,
              EX.data_geracao,
              EX.total ,
              EX.pecas ,
              EP.baixa_extrato";
}
function getCamposSqlThermosystem(){

    return "
              PO.posto ,
              PO.nome ,
              PO.cnpj ,
              PF.contato_estado as estado ,
              PF.contato_email AS email ,
              PF.credenciamento ,
              PF.codigo_posto ,
              PF.distribuidor ,
              PF.imprime_os ,
              TP.descricao AS tipo_posto ,
              EX.extrato ,
              EX.bloqueado ,
              EX.liberado ,
              EX.estoque_menor_20 ,
              TO_CHAR (EX.aprovado,'dd/mm/yyyy') AS aprovado ,
              LPAD (EX.protocolo,6,'0') AS protocolo ,
              TO_CHAR (EX.data_geracao,'dd/mm/yyyy') AS data_geracao ,
              EX.data_geracao AS xdata_geracao,
              EX.total ,
              EX.pecas ,
              count(tbl_os_extra.os) as qtde,
              EP.baixa_extrato
                ";
}
//HD 205958: Um extrato pode ser modificado atÈ o momento que for APROVADO pelo admin. ApÛs aprovado
//           n„o poder· mais ser modificado em hipÛtese alguma. Acertos dever„o ser feitos com lan√ßamento
//           de extrato avulso. Verifique as regras definidas neste HD antes de fazer exceÁıes para as f·bricas
//           SER√Å LIBERADO AOS POUCOS, POIS OS PROGRAMAS N√ÉO EST√ÉO PARAMETRIZADOS
//           O array abaixo define quais f·bricas est„o enquadradas no processo novo
$fabricas_acerto_extrato = array(43, 45);

//HD 237498: Barrar liberaÁ„o de Extrato caso tenha OS em IntervenÁ„o de KM
//A funcao abaixo verifica se o extrato tem OS com KM pendente
$intervencao_km_extrato = array(30,  129);
if($login_fabrica == 1){
    function verificaTipoGeracao($extrato){
        global $con;
        $sqlVerificaTipoGeracao = " SELECT obs
                                    FROM tbl_extrato_extra
                                    WHERE extrato = {$extrato} ";
        $resVerificaTipoGeracao = pg_query($con, $sqlVerificaTipoGeracao);
        if(pg_num_rows($resVerificaTipoGeracao) > 0 ){
            $obs = pg_fetch_result($resVerificaTipoGeracao, 0,"obs");

            return $obs;

        }else{
            return "";
        }
    }
}
function verifica_km_pendente_extrato($extrato) {
    global $con;

    //Verifica se a OS em algum momento entrou em intervenÁ„o de KM, status 98 | Aguardando aprovaÁ„o da KM
    $sql = "
	    SELECT OEX.os,
	    (SELECT status_os
		FROM tbl_os_status
		WHERE tbl_os_status.os = OEX.os
		AND status_os IN(98,99,100,101)
		ORDER BY data DESC LIMIT 1) AS status_os
	    FROM tbl_os_extra OEX
	    WHERE OEX.extrato=$extrato
	    ";
    $res_km = pg_query($con, $sql);

    if (pg_num_rows($res_km)) {
        //Caso a OS algum dia tenha entrado em intervenÁ„o de KM, precisa ser verificado se saiu todas as vezes
        //A OS pode sair da intervenÁ„o de KM por um dos status abaixo:
        // 99 | KM Aprovada
        //100 | KM Aprovada com alteraÁ„o
        //101 | km Recusada
	    $n_intervencao_km = pg_fetch_all($res_km);

	    $km_pendente = false;

	   foreach($n_intervencao_km AS $key => $value){

		   if($value['status_os'] == 98){
			$km_pendente = true;
		   }
	   }

    }else {
        $km_pendente = false;
    }

    return($km_pendente);
}

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
    $tipo_busca = $_GET["busca"];

    if (strlen($q)>2){
        $sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
                FROM tbl_posto
                JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

        if ($tipo_busca == "codigo"){
            $q    = substr(preg_replace('/\D/', '', $q),0, 14);
            $sql .= " AND tbl_posto.cnpj = '$q' ";
        }else{
            $sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
        }

        $res = pg_query($con,$sql);
        if (pg_num_rows ($res) > 0) {
            for ($i=0; $i<pg_num_rows ($res); $i++ ){
                $cnpj = trim(pg_fetch_result($res,$i,cnpj));
                $nome = trim(pg_fetch_result($res,$i,nome));
                $codigo_posto = trim(pg_fetch_result($res,$i,codigo_posto));
                echo "$cnpj|$nome|$codigo_posto";
                echo "\n";
            }
        }
    }
    exit;
}

if($ajax=='conta'){
            if($login_fabrica==45){//HD 39377 12/9/2008
                $sql = "SELECT count(*) as qtde_os
                        FROM tbl_os
                        JOIN tbl_os_extra USING(os)
                        WHERE tbl_os.mao_de_obra notnull
                        and tbl_os.pecas       notnull
                        and ((
                                SELECT tbl_os_status.status_os
                                FROM tbl_os_status
                                WHERE tbl_os_status.os = tbl_os.os
                                ORDER BY tbl_os_status.data DESC LIMIT 1
                                ) IS NULL
                            OR (SELECT tbl_os_status.status_os
                                FROM tbl_os_status WHERE tbl_os_status.os = tbl_os.os
                                ORDER BY tbl_os_status.data DESC LIMIT 1
                                ) NOT IN (15)
                            )
                        and tbl_os_extra.extrato = $extrato";
            }else{
                $sql = "SELECT count(*) as qtde_os FROM tbl_os_extra WHERE extrato = $extrato";
            }
            $rres = pg_query($con,$sql);
            if(pg_num_rows($rres)>0){
                $qtde_os = pg_fetch_result($rres,0,qtde_os);
            }
            echo "ok|$qtde_os";
            exit;
}
// AJAX -> solicita a exportaÁ„o dos extratos
if (strlen($_GET["exportar"])>0){
    //include "../ajax_cabecalho.php";
    //system("/www/cgi-bin/bosch/exporta-extrato.pl",$ret);
    $dados = "$login_fabrica\t$login_admin\t".date("d-m-Y H:m:s");
    exec ("echo '$dados' > /tmp/bosch/exporta/pronto.txt");
    echo "ok|ExportaÁ„o concluÌda com sucesso! Dentro de alguns minutos os arquivos de exportaÁ„o estar„o disponÌveis no sistema.";
    exit;
}
// FIM DO AJAX -> solicita a exportaÁ„o dos extratos


// AJAX -> APROVA O EXTRATO SELECIONADO
// ATEN√«√O: NESTE ARQUIVO EXISTEM DUAS ROTINAS PARA APROVAR EXTRATO, UMA COM AJAX E OUTRA SEM
//          QUANDO FOR MODIFICAR UMA, VERIFIQUE SER¡ NECESS¡RIO MODIFICAR A OUTRA
if ($_GET["ajax"] == "APROVAR" && strlen($_GET["aprovar"])>0 && strlen($_GET["posto"])>0){

    $posto   = $_GET["posto"];
    $aprovar = $_GET["aprovar"];

    $res = pg_query($con,"BEGIN TRANSACTION");

    if ($login_fabrica == 1) {

        $sql = "SELECT  posto
                FROM    tbl_tipo_gera_extrato
                WHERE   fabrica = $login_fabrica
                AND     posto   = $posto
                AND     envio_online";

        $res = pg_query($con, $sql);

        if (pg_num_rows($res)) {

            $envio_online = true;

            $sql = "INSERT INTO tbl_extrato_status (
                        extrato    ,
                        fabrica    ,
                        obs        ,
                        data       ,
                        pendente   ,
                        pendencia
                    ) VALUES (
                        $aprovar          ,
                        $login_fabrica,
                        'Aguardando NF de servi√ßos',
                        current_timestamp ,
                        't'         ,
                        't'
                    )";

            $res = pg_query($con,$sql);

        }

    }

    if (in_array($login_fabrica, array(14,20))) {
        $nf_mao_de_obra = $_GET["nf_mao_de_obra"];
        if (strlen(trim($nf_mao_de_obra))==0) {
            $nf_mao_de_obra = 'null';
        }

        $nf_devolucao   = $_GET["nf_devolucao"];
        if (strlen(trim($nf_devolucao))==0) {
            $nf_devolucao = 'null';
        }

        $data_entrega_transportadora = $_GET["data_entrega_transportadora"];
        $data_entrega_transportadora = str_replace (" " , "" , $data_entrega_transportadora);
        $data_entrega_transportadora = str_replace ("-" , "" , $data_entrega_transportadora);
        $data_entrega_transportadora = str_replace ("/" , "" , $data_entrega_transportadora);
        $data_entrega_transportadora = str_replace ("." , "" , $data_entrega_transportadora);

        if (strlen ($data_entrega_transportadora) == 6) {
            $data_entrega_transportadora = substr ($data_entrega_transportadora,0,4) . "20" . substr ($data_entrega_transportadora,4,2);
        }

        if (strlen ($data_entrega_transportadora) > 0) {
            $data_entrega_transportadora = substr ($data_entrega_transportadora,0,2) . "/" . substr ($data_entrega_transportadora,2,2) . "/" . substr ($data_entrega_transportadora,4,4);
            if (strlen ($data_entrega_transportadora) < 8) $data_entrega_transportadora = date ("d/m/Y");
            $data_entrega_transportadora = substr ($data_entrega_transportadora,6,4) . "-" . substr ($data_entrega_transportadora,3,2) . "-" . substr ($data_entrega_transportadora,0,2);
            } else {
            $data_entrega_transportadora = 'null';
        }

        $sql = "UPDATE tbl_extrato_extra SET
                    nota_fiscal_mao_de_obra     = '$nf_mao_de_obra',
                    nota_fiscal_devolucao       = '$nf_devolucao',
                    data_entrega_transportadora = '$data_entrega_transportadora'
                WHERE extrato = $aprovar";
        #$res = pg_query($con,$sql);
        # Estava comentado , entao descomentei. Pq comentaram?  N„o tem a explicacao.
        # Estou liberando. HD 4846

        //HD 145478 - Gravando quem aprovou o extrato
        $sql = "
        UPDATE
        tbl_extrato_extra

        SET
        admin = $login_admin

        WHERE
        extrato = $aprovar
        ";
        $res = pg_query($con, $sql);
    }

    $sql = "
    UPDATE
    tbl_extrato_extra

    SET
    admin = $login_admin

    WHERE
    extrato = $aprovar
    ";
    $res = pg_query($con, $sql);


    $sql = "SELECT fn_aprova_extrato($posto,$login_fabrica,$aprovar)";
    $res = pg_query($con,$sql);
    $msg_erro = pg_errormessage($con);

    if (strlen ($msg_erro) == 0) {
        $res = pg_query ($con,"COMMIT TRANSACTION");
        echo "ok;$aprovar";
    }else{
        $res = @pg_query ($con,"ROLLBACK TRANSACTION");
        echo "erro;$sql ==== $msg_erro ";
    }
    exit;
}

// FIM DO AJAX -> APROVA O EXTRATO SELECIONADO

$msg_erro = "";

if (strlen($_POST["btnacao"]) > 0) $btnacao = trim(strtolower($_POST["btnacao"]));
if (strlen($_GET["btnacao"])  > 0) $btnacao = trim(strtolower($_GET["btnacao"]));

if (strlen($_POST["posto"]) > 0) $posto = $_POST["posto"];
if (strlen($_GET["posto"])  > 0) $posto = $_GET["posto"];

if (strlen($_GET["liberar"]) > 0) $liberar = $_GET["liberar"];

if (strlen($liberar) > 0){
    //HD 237498: Barrar liberaÁ„o de Extrato caso tenha OS em IntervenÁ„o de KM
    if (in_array($login_fabrica, $intervencao_km_extrato)) {
        //Verifica se a OS em algum momento entrou em intervenÁ„o de KM, status 98 | Aguardando aprovaÁ„o da KM
        $sql = "
		SELECT OEX.os,
		(SELECT status_os
		FROM tbl_os_status
		WHERE tbl_os_status.os = OEX.os
		AND status_os IN(98,99,100,101)
		ORDER BY data DESC LIMIT 1) AS status_os
		FROM tbl_os_extra OEX
		WHERE OEX.extrato=$extrato
		";
        $res_km = pg_query($con, $sql);


        if (pg_num_rows($res_km)) {
            //Caso a OS algum dia tenha entrado em intervenÁ„o de KM, precisa ser verificado se saiu todas as vezes
            //A OS pode sair da intervenÁ„o de KM por um dos status abaixo:
            // 99 | KM Aprovada
            //100 | KM Aprovada com alteraÁ„o
            //101 | km Recusada

	    $n_intervencao_km = pg_fetch_all($res_km);

	     $km_pendente = false;

	    foreach($n_intervencao_km AS $key => $value){
		    if($value['status_os'] == 98){
			$km_pendente = true;
		    }
	    }

            if ($km_pendente == true) {
                $msg_erro = "AtenÁ„o: existem OS em intervenÁ„o neste extrato ($liberar). Para que o extrato seja liberado È necess·rio aprovar ou reprovar todas as intervenÁıes de suas OS antes. Consulte o extrato para maiores detalhes.";
            }
        }
    }

    /*IGOR HD 17677 - 04/06/2008 */
    if( in_array($login_fabrica, array(11,25,30,172)) ){
        $sql="SELECT recalculo_pendente
                from tbl_extrato
                where extrato=$liberar
                and fabrica=$login_fabrica";
        $res = @pg_query($con,$sql);
        $recalculo_pendente=pg_fetch_result($res,0,recalculo_pendente);
        if($recalculo_pendente=='t'){
            if ($login_fabrica == 30) {
                $msg_erro = "O extrato $liberar est· pendente de rec·lculo, recalcular antes de liberar";
                $extrato = $liberar;
            }
            else {
                $msg_erro="Este extrato ser· recalculado de noite e poder· ser liberado amanh„";
            }
        }
    }



    if (strlen($msg_erro)==0){
      
        $res = pg_query($con,"BEGIN TRANSACTION");

        if($login_fabrica == 91){
            $sql_extrato_status = "INSERT INTO tbl_extrato_status (fabrica, extrato, data, obs, advertencia) 
                      VALUES ($login_fabrica, $liberar, now(), 'Aguardando Envio da Nota Fiscal', false) ";

          $res_extrato_status = pg_query($con, $sql_extrato_status);
          if(strlen(pg_last_error($con))>0){
            $msg_erro .= "Falha ao liberar o extrato $liberar. <br> ";
          }else{
            $sql = "SELECT posto FROM tbl_extrato WHERE fabrica = {$login_fabrica} AND extrato = {$liberar}
            ";
            $res = pg_query($con, $sql);

            $posto = pg_fetch_result($res, 0, "posto");
            $sql_comunicado = "INSERT INTO tbl_comunicado
                (
                    fabrica,
                    posto,
                    obrigatorio_site,
                    tipo,
                    ativo,
                    descricao,
                    mensagem
                )
                VALUES
                (
                    {$login_fabrica},
                    {$posto},
                    true,
                    'Com. Unico Posto',
                    true,
                    'Extrato Liberado',
                    'O extrato {$liberar} foi liberado est· aguardando nota fiscal. '
                )";

            $res_comunicado = pg_query($con, $sql_comunicado);

            if (strlen(pg_last_error()) > 0) {
              $msg_erro = "Erro ao liberar o extrato";
            }
          }
        }

        if(in_array($login_fabrica, array(152,180,181,182))) {
          $sql_extrato_status = "INSERT INTO tbl_extrato_status (fabrica, extrato, data, obs, advertencia) 
                      VALUES ($login_fabrica, $liberar, now(), 'Aguardando Nota Fiscal do Posto', false) ";

          $res_extrato_status = pg_query($con, $sql_extrato_status);
          if(strlen(pg_last_error($con))>0){
            $msg_erro .= "Falha ao liberar o extrato $liberar. <br> ";
          }else{
            $sql = "SELECT posto FROM tbl_extrato WHERE fabrica = {$login_fabrica} AND extrato = {$liberar}
            ";
            $res = pg_query($con, $sql);

            $posto = pg_fetch_result($res, 0, "posto");
            $sql_comunicado = "INSERT INTO tbl_comunicado
                (
                    fabrica,
                    posto,
                    obrigatorio_site,
                    tipo,
                    ativo,
                    descricao,
                    mensagem
                )
                VALUES
                (
                    {$login_fabrica},
                    {$posto},
                    true,
                    'Com. Unico Posto',
                    true,
                    'Extrato Liberado',
                    'O extrato {$liberar} foi liberado est· aguardando nota fiscal. '
                )";

            $res_comunicado = pg_query($con, $sql_comunicado);

            if (strlen(pg_last_error()) > 0) {
              $msg_erro = "Erro ao liberar o extrato";
            }
          }
        }

        //HD 205958: N„o pode aprovar nenhum extrato na liberaÁ„o, … uma falha no conceito do negÛcio.
        //           antes de atender qualquer solicitaÁ„o das f·bricas concernentes a isto, verificar conceitos
        //           definidos neste chamado. Apagadas 3 linhas abaixo, verificar nao_sync caso necess·rio
        if (in_array($login_fabrica, $fabricas_acerto_extrato)) {
        } else {
            //HD 205958: Este conceito est· errado, um extrato nunca pode ser aprovado na liberaÁ„o. Esta linha
            //           est· aqui provisÛriamente enquanto arrumamos os conceitos das f·bricas
          if($login_fabrica <> 158){
            $aprovar_na_liberacao = "aprovado = current_timestamp,";
          }
        }
        $sql = "
          UPDATE
          tbl_extrato
          SET
          liberado = current_date,
          $aprovar_na_liberacao
          admin = $login_admin
          WHERE extrato = $liberar
        "; //Corrigido! HD 44022

        if(strlen($_GET['aprovacao']) > 0){
          $sql = "
            UPDATE
            tbl_extrato
            SET
            aprovado = current_timestamp,
            admin = $login_admin
            WHERE extrato = $liberar;
          ";
        }

        $res = @pg_query($con,$sql);
        $msg_erro = @pg_errormessage($con);

        if(in_array($login_fabrica, array(163,167,203)) || $login_fabrica >= 174){
          $sql = "
            SELECT posto FROM tbl_extrato WHERE fabrica = {$login_fabrica} AND extrato = {$liberar}
          ";
          $res = pg_query($con, $sql);

          $posto = pg_fetch_result($res, 0, "posto");

          $sql = "INSERT INTO tbl_comunicado
              (
                  fabrica,
                  posto,
                  obrigatorio_site,
                  tipo,
                  ativo,
                  descricao,
                  mensagem
              )
              VALUES
              (
                  {$login_fabrica},
                  {$posto},
                  true,
                  'Com. Unico Posto',
                  true,
                  'Extrato Liberado',
                  'O extrato {$liberar} foi liberado pela f·brica'
              )
          ";
          pg_query($con, $sql);

          if (strlen(pg_last_error()) > 0) {
            $msg_erro = "Erro ao liberar o extrato";
          }
        }

        // IntegraÁ„o Extrato (Telecontrol x SAP)
        if (strlen($msg_erro) == 0 && strlen($liberar) > 0 && $login_fabrica == 158) {

            require_once dirname(__FILE__)."/../classes/Posvenda/Fabricas/_{$login_fabrica}/IntegracaoExtrato.php";
            $oIntegracaoExtrato = new IntegracaoExtrato($login_fabrica, $con);

            try {
                $dadosExportaExtrato = $oIntegracaoExtrato->BuscaDadosExtrato($liberar);
                $env = ($_serverEnvironment == 'development') ? "dev" : "";
                if ($oIntegracaoExtrato->ExportaExtrato($dadosExportaExtrato, $env) === false) {
                    throw new Exception("Extrato {$liberar} n„o foi enviado para o SAP, n„o foi possÌvel aprovar");
                }
            } catch (Exception $e) {
                $msg_erro = $e->getMessage();
		$erroExportacao = $oIntegracaoExtrato->errorMessage;
            }

        }

        //Wellington 14/12/2006 - ENVIA EMAIL PARA O POSTO QDO O EXTRATO … LIBERADO
        /*IGOR HD 17677 - 04/06/2008 */
        /*HD 138813 MLG - N„o enviada para alguns postos porque na tbl_posto n„o tem e-mail.
                          Alterado para pegar das duas, de preferÍncia da tbl_posto_fabrica */
        if (strlen($msg_erro) == 0 && in_array($login_fabrica, array(11,24,25,40,171,172,175))) {
            include 'email_comunicado.php'; // FunÁıes para enviar e-mail e inserir comunicado para o Posto
            $sql = "
            SELECT
            CASE
                            WHEN contato_email IS NULL THEN tbl_posto.email
                            ELSE contato_email
                        END AS email,
            tbl_posto_fabrica.posto

            FROM
            tbl_posto_fabrica
                        JOIN tbl_extrato USING (posto,fabrica)
                        JOIN tbl_posto ON tbl_extrato.posto = tbl_posto.posto

            WHERE
            extrato = $liberar";

            $res = @pg_query($con,$sql);

            if (@pg_num_rows($res)) {
                //Se tem aviso, pega o valor, tanto se foi por GET como POST...
                $msg_aviso    = $_REQUEST['msg_aviso'];
                $xposto       = trim(pg_fetch_result($res,0,posto));
                $destinatario = trim(pg_fetch_result($res,0,email));
                $assunto      = "SEU EXTRATO (N∫ $liberar) FOI LIBERADO";
                $mensagem     =  "* O EXTRATO N∫".$liberar." EST¡ LIBERADO NO SITE: www.telecontrol.com.br *<br><br>".$msg_aviso ;

                $r_email    = "<noreply@telecontrol.com.br>";
                $remetente  = "TELECONTROL";

                if ($login_fabrica == 24) {
                    $r_email    = "<suggat@suggar.com.br>";
                    $remetente  = "SUGGAR FINANCEIRO";
                }
                elseif ($login_fabrica == 25) {
                    $r_email    = "<ronaldo@telecontrol.com.br>";
                    $remetente  = "HBFLEX FINANCEIRO";
                }

                $headers = "Return-Path:$r_email \nFrom:".$remetente.
                       " $r_email \nBcc:$r_email \nContent-type: text/html\n";

                enviar_email($r_email, $destinatario, $assunto, $mensagem, $remetente, $headers, true);
                
                if ($login_fabrica != 175) {
                    gravar_comunicado("Extrato disponÌvel", $assunto, $mensagem, $xposto, true);
                }
            }
        }

        //wellington liberar
        // Fabio 02/10/2007
        // Alterado por Fabio -> tbl_faturamento.emissao <  '2007-10-21' // HD 600
        // Depois da liberaÁ„o, alterar para tbl_faturamento.emissao < current_date - interval'15 day'
        /* LENOXX - SETA EXTRATO DE DEVOLU«√O PARA OS FATURAMENTOS */
        /*IGOR HD 17677 - 04/06/2008 */
        if (strlen($liberar) > 0 && strlen($msg_erro) == 0 && ( in_array($login_fabrica, array(11,25,172)) )) {
            if($login_fabrica == 25 ) {
                $sql = "SELECT TO_CHAR(data_geracao-interval '15 days','YYYY-MM-DD') AS data_limite
                        FROM tbl_extrato
                        WHERE extrato = $liberar;";
            }else{
                $sql = "SELECT TO_CHAR(data_geracao-interval '1 month','YYYY-MM-21') AS data_limite
                        FROM tbl_extrato
                        WHERE extrato = $liberar;";
            }

            $res = pg_query($con,$sql);
            $data_limite_nf = trim(pg_fetch_result($res,0,data_limite));

            $sql = "UPDATE tbl_faturamento SET extrato_devolucao = $liberar
                    WHERE  tbl_faturamento.fabrica = $login_fabrica
                    AND    tbl_faturamento.posto   = $xposto
                    AND    tbl_faturamento.extrato_devolucao IS NULL
                    AND    tbl_faturamento.emissao > '2007-08-30'
                    AND    tbl_faturamento.emissao < '$data_limite_nf'
                    AND    (tbl_faturamento.cfop ILIKE '%59%' OR tbl_faturamento.cfop ILIKE '%69%')
                    ";
            // AND    tbl_faturamento.emissao <  current_date - interval'15 day'
            $res = pg_query($con,$sql);

            $sql = "DELETE FROM tbl_extrato_lgr WHERE extrato = $liberar";
            $res = pg_query($con,$sql);

            $sql = "INSERT INTO tbl_extrato_lgr (extrato, posto, peca, qtde) (
                SELECT tbl_extrato.extrato, tbl_extrato.posto, tbl_faturamento_item.peca, SUM (tbl_faturamento_item.qtde)
                FROM tbl_extrato
                JOIN tbl_faturamento      ON tbl_extrato.extrato         = tbl_faturamento.extrato_devolucao
                JOIN tbl_faturamento_item ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                WHERE tbl_extrato.fabrica = $login_fabrica
                AND   tbl_extrato.extrato = $liberar
                GROUP BY tbl_extrato.extrato, tbl_extrato.posto, tbl_faturamento_item.peca
                ) ;";
            $res = pg_query($con,$sql);
        } 

        if (strlen ($msg_erro) == 0) {
            if($login_fabrica == 52){
                $sql = "SELECT
                            tbl_posto_fabrica.contato_nome      AS nome,
                            tbl_posto_fabrica.contato_email     AS email
                        FROM
                            tbl_extrato
                        JOIN
                            tbl_posto_fabrica ON (tbl_posto_fabrica.posto = tbl_extrato.posto)
                        WHERE
                            tbl_extrato.extrato = $liberar
                            AND tbl_posto_fabrica.fabrica = $login_fabrica;";
                $res = pg_query($con, $sql);

                if (@pg_num_rows($res) == 0) {
                    $sql = "SELECT
                                tbl_posto.nome  AS nome,
                                tbl_posto.email AS email
                            FROM
                                tbl_extrato
                            JOIN
                                tbl_posto ON (tbl_posto.posto = tbl_extrato.posto)
                            WHERE
                                tbl_extrato.extrato = $liberar
                                AND tbl_extrato.fabrica = $login_fabrica;";
                    $res = pg_query($con, $sql);
                }

                $email_posto = @pg_fetch_result($res,0,'email');
                $nome_posto = @pg_fetch_result($res,0,'nome');

                $sql   = "SELECT email
                FROM tbl_admin
                WHERE tbl_admin.admin = {$login_admin}";

                $res   = pg_query($con,$sql);
                $email_admin = pg_fetch_result($res,0,'email');

                if($email_posto != ""){
                    $remetente    = $email_admin;
                    $destinatario = $email_posto;
                    $assunto      = "Extrato Fricon $liberar liberado!\n";
                    $mensagem     = "Prezado(a) {$nome_posto},\n";
                    $mensagem    .="<br /><br />O(s) extrato(s) Fricon N∫ $liberar foi liberado, favor enviar a nota fiscal de prestaÁ„o de servi√ßos para pagamento e informar no corpo da nota os dados banc·rios\n";
                    $mensagem    .="<br /><br />----------\n";
                    $mensagem    .="<br />Qualquer d˙vida entrar em contato com a Fricon.";
                    $headers= "From:".$remetente."\nContent-type: text/html\n";

                    mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers);
                }else{
                    echo "<script language='javascript'>alert('N„o foi possivel encontrar o email do posto, favor atualizar os dados');</script>";
                }
            }

            $liberado = true;
             //$res = @pg_query ($con,"ROLLBACK TRANSACTION");
            $res = pg_query ($con,"COMMIT TRANSACTION");

            if(in_array($login_fabrica, array(152,180,181,182))){
              header("Location: extrato_consulta.php?liberado= $liberado");
            }

        }else{
            $res = @pg_query ($con,"ROLLBACK TRANSACTION");
             if ($login_fabrica == 158) {
	     if(!empty($erroExportacao)) {

		     $sqlErro = "UPDATE tbl_extrato_extra set obs = '$erroExportacao' where extrato = $liberar";
		     $resErro  = pg_query($con,$sqlErro);

			if(pg_last_error($con)) {
				$msg_erro .= "<br> Erro ao  gravar reposta da exportaÁ„o";
			}

		}
		}
        }
    }


      if(in_array($login_fabrica, [152,180,181,182])){ 
	      if (strlen($msg_erro) > 0) {
		$json = ["erro"=> true, "msg" => utf8_encode($msg_erro)];
	      } else {
		$json = ["erro"=> false, "msg" => utf8_encode("Extrato Liberado com sucesso")];
	      }
	      
	      exit(json_encode($json));
      }
    

}

if ($btnacao == 'liberar_tudo'){
    if (strlen($_POST["total_postos"]) > 0) $total_postos = $_POST["total_postos"];
    $extrato_km_pendente = array();

	for ($i=0; $i < $total_postos; $i++) {
		$msg_erro = "";
		$res = pg_query ($con,"BEGIN TRANSACTION");
        $extrato    = $_POST["liberar_".$i];
        $imprime_os = $_POST["imprime_os_".$i];
        $km_pendente = false;
        $aprovado = $_POST["extrato_aprovado_".$i];

        //HD 237498: Barrar liberaÁ„o de Extrato caso tenha OS em IntervenÁ„o de KM
        if (in_array($login_fabrica, $intervencao_km_extrato) && $extrato) {
            $km_pendente = verifica_km_pendente_extrato($extrato);
        }
        else {
            $km_pendente = false;
        }

        if ($km_pendente) { 
            $extrato_km_pendente[] = $extrato;
        } else {
            if (strlen($extrato) > 0 AND strlen($msg_erro) == 0) {

              if(in_array($login_fabrica, array(152,180,181,182))){
                $sql_extrato_status = "INSERT INTO tbl_extrato_status (fabrica, extrato, data, obs, advertencia) 
                            VALUES ($login_fabrica, $extrato, now(), 'Aguardando Nota Fiscal do Posto', false) ";
                $res_extrato_status = pg_query($con, $sql_extrato_status);

                if(strlen(pg_last_error($con))>0){
                  $msg_erro .= "Falha ao liberar o extrato $extrato. <br> ";
                }else{
                  $sql = "SELECT posto FROM tbl_extrato WHERE fabrica = {$login_fabrica} AND extrato = {$extrato}
                  ";
                  $res = pg_query($con, $sql);

                  $posto = pg_fetch_result($res, 0, "posto");
                  $sql_comunicado = "INSERT INTO tbl_comunicado
                      (
                          fabrica,
                          posto,
                          obrigatorio_site,
                          tipo,
                          ativo,
                          descricao,
                          mensagem
                      )
                      VALUES
                      (
                          {$login_fabrica},
                          {$posto},
                          true,
                          'Com. Unico Posto',
                          true,
                          'Extrato Liberado',
                          'O extrato {$extrato} foi liberado est· aguardando nota fiscal. '
                      )";

                  $res_comunicado = pg_query($con, $sql_comunicado);
                  if (strlen(pg_last_error()) > 0) {
                    $msg_erro = "Erro ao liberar o extrato";
                  }
                }
              }

                $sql = "UPDATE tbl_extrato SET liberado = current_date, admin = $login_admin ";

                //HD 205958: N„o pode aprovar nenhum extrato na liberaÁ„o, È uma falha no conceito do negÛcio.
                //           antes de atender qualquer solicitaÁ„o das f·bricas concernentes a isto, verificar conceitos
                //           definidos neste chamado. Apagadas 3 linhas abaixo, verificar nao_sync caso necess·rio
                if (in_array($login_fabrica, $fabricas_acerto_extrato)) {
                } else if (in_array($login_fabrica,array(6,7,11,30,14,15,24,25,35,43,45,46,50,51,59,66,74,80,52,85,88,94,99,90,91)) or $login_fabrica > 99) {
                    //HD 205958: Este conceito est· errado, um extrato nunca pode ser aprovado na liberaÁ„o. Esta linha
                    //           est· aqui provisÛriamente enquanto arrumamos os conceitos das f·bricas
                    $sql .= ", aprovado = current_timestamp ";
                }

                $sql .= "WHERE  tbl_extrato.extrato = $extrato
                         and    tbl_extrato.fabrica = $login_fabrica";
                         //echo $sql;

                if($login_fabrica == 158){
                  if(strlen($aprovado) > 0){
                  	$campo = "liberado = current_date";
                  }else{
                  	$campo = "aprovado = current_timestamp";
                  }

                  $sql = "UPDATE tbl_extrato SET $campo, admin = $login_admin WHERE extrato = $extrato AND fabrica = $login_fabrica";
                }

                $res = pg_query($con,$sql);
                $msg_erro = @pg_errormessage($con);

                if ($login_fabrica == 163 || $login_fabrica >= 174) {
                  $sql = "
                    SELECT posto FROM tbl_extrato WHERE fabrica = {$login_fabrica} AND extrato = {$extrato}
                  ";
                  $res = pg_query($con, $sql);

                  $posto = pg_fetch_result($res, 0, "posto");

                  $sql = "INSERT INTO tbl_comunicado
                      (
                          fabrica,
                          posto,
                          obrigatorio_site,
                          tipo,
                          ativo,
                          descricao,
                          mensagem
                      )
                      VALUES
                      (
                          {$login_fabrica},
                          {$posto},
                          true,
                          'Com. Unico Posto',
                          true,
                          'Extrato Liberado',
                          'O extrato {$extrato} foi liberado pela f·brica'
                      )
                  ";
                  pg_query($con, $sql);

                  if (strlen(pg_last_error()) > 0) {
                    $msg_erro = "Erro ao liberar o extrato {$extrato}";
                  }
                }

                // IntegraÁ„o Extrato (Telecontrol x SAP)
                if (strlen($msg_erro) == 0 && strlen($extrato) > 0 && $login_fabrica == 158) {

                    require_once dirname(__FILE__)."/../classes/Posvenda/Fabricas/_{$login_fabrica}/IntegracaoExtrato.php";
                    $oIntegracaoExtrato = new IntegracaoExtrato($login_fabrica, $con);

                    try {
                        $dadosExportaExtrato = $oIntegracaoExtrato->BuscaDadosExtrato($extrato);
                        $env = ($_serverEnvironment == 'development') ? "dev" : "";
                        if ($oIntegracaoExtrato->ExportaExtrato($dadosExportaExtrato, $env) === false) {
                            throw new Exception("Extrato {$extrato} n„o foi enviado para o SAP, n„o foi possÌvel aprovar");
                        }
                    } catch (Exception $e) {
                        $msg_erro = $e->getMessage();
                    }

                }

                //Wellington 14/12/2006 - ENVIA EMAIL PARA O POSTO QDO O EXTRATO … LIBERADO
                //IGOR HD 17677 - 04/06/2008 
                if (strlen($msg_erro) == 0 && ( in_array($login_fabrica, array(11,25,172))) ) {
                    include 'email_comunicado.php'; // FunÁıes para enviar e-mail e inserir comunicado para o Posto
                    $sql = "SELECT CASE
                                    WHEN contato_email IS NULL
                                        THEN tbl_posto.email
                                    ELSE contato_email
                                    END AS email, tbl_posto_fabrica.posto FROM tbl_posto_fabrica
                                JOIN tbl_extrato USING (posto,fabrica)
                                JOIN tbl_posto ON tbl_extrato.posto = tbl_posto.posto
                            WHERE extrato = $extrato";
                    $res = pg_query($con,$sql);

        //          Se tem aviso, pega o valor, tanto se foi por GET como POST...
                    $msg_aviso    = (isset($_REQUEST['msg_aviso']))?"AVISO: ".$_REQUEST['msg_aviso']."<BR><BR><BR>":"";
                    $xposto       = trim(pg_fetch_result($res,0,posto));
                    $destinatario = trim(pg_fetch_result($res,0,email));
                    $assunto      = "SEU EXTRATO (N∫ $extrato) FOI LIBERADO";
                    $mensagem     =  "* O EXTRATO N∫".$extrato." EST¡ LIBERADO NO TELECONTROL<br><br>".$msg_aviso ;

					$sql   = "SELECT email
					FROM tbl_admin
					WHERE tbl_admin.admin = {$login_admin}";

					$res   = pg_query($con,$sql);
					$email_admin = pg_fetch_result($res,0,'email');

					$r_email    = pg_fetch_result($res,0,'email');
					$remetente  = "Lenoxx";
                    $headers    = "Return-Path:$r_email \nFrom:".$remetente.
                                  " $r_email\nBcc:$r_email \nContent-type: text/html\n";

                    enviar_email($r_email, utf8_encode($destinatario), utf8_encode($assunto), $mensagem, $remetente, $headers, true);
                    gravar_comunicado("Extrato disponÌvel", $assunto, $mensagem, $xposto, true);
                }
            
          
            }

            //wellington liberar
            /* LENOXX - SETA EXTRATO DE DEVOLU«√O PARA OS FATURAMENTOS */
            /*IGOR HD 17677 - 04/06/2008 */
            if (strlen($extrato) > 0 && strlen($msg_erro) == 0 && ( in_array($login_fabrica, array(11,25,172)) )) {

                $sql = "SELECT TO_CHAR(data_geracao-interval '1 month','YYYY-MM-21') AS data_limite
                        FROM tbl_extrato
                        WHERE extrato = $extrato;";
                $res = pg_query($con,$sql);
                $data_limite_nf = trim(pg_fetch_result($res,0,data_limite));

                $sql = "UPDATE tbl_faturamento SET extrato_devolucao = $extrato
                        WHERE  tbl_faturamento.fabrica = $login_fabrica
                        AND    tbl_faturamento.posto   = $xposto
                        AND    tbl_faturamento.extrato_devolucao IS NULL
                        AND    tbl_faturamento.emissao >  '2007-08-30'
                        AND    tbl_faturamento.emissao < '$data_limite_nf'
                        AND    (tbl_faturamento.cfop ILIKE '%59%' OR tbl_faturamento.cfop ILIKE '%69%')
                        ";
                $res = pg_query($con,$sql);

                $sql = "DELETE FROM tbl_extrato_lgr WHERE extrato = $extrato";
                $res = pg_query($con,$sql);

                $sql = "INSERT INTO tbl_extrato_lgr (extrato, posto, peca, qtde) (
                    SELECT tbl_extrato.extrato, tbl_extrato.posto, tbl_faturamento_item.peca, SUM (tbl_faturamento_item.qtde)
                    FROM tbl_extrato
                    JOIN tbl_faturamento      ON tbl_extrato.extrato         = tbl_faturamento.extrato_devolucao
                    JOIN tbl_faturamento_item ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                    WHERE tbl_extrato.fabrica = $login_fabrica
                    AND   tbl_extrato.extrato = $extrato
                    GROUP BY tbl_extrato.extrato, tbl_extrato.posto, tbl_faturamento_item.peca
                    ) ;";
                $res = pg_query($con,$sql);
            }

            //HD 12104
            if($login_fabrica==14 and strlen($imprime_os) > 0){
                $sql =" UPDATE tbl_posto_fabrica set imprime_os ='t'
                            FROM tbl_extrato
                            WHERE tbl_extrato.posto=tbl_posto_fabrica.posto
                            AND extrato=$imprime_os
                            AND tbl_posto_fabrica.fabrica=$login_fabrica ";
                $res=pg_query($con,$sql);
            }
        }
        
        //HD 237498: Coloquei esta linha porque depois que aprovava tudo sempre mostrava o √∫ltimo extrato, sozinho, ficando confuso
        if($login_fabrica == 52 AND strlen($msg_erro) == 0 AND strlen($extrato) > 0){
            $sql = "SELECT
                        tbl_posto_fabrica.contato_nome      AS nome,
                        tbl_posto_fabrica.contato_email     AS email
                    FROM
                        tbl_extrato
                    JOIN
                        tbl_posto_fabrica ON (tbl_posto_fabrica.posto = tbl_extrato.posto)
                    WHERE
                        tbl_extrato.extrato = $extrato
                        AND tbl_posto_fabrica.fabrica = $login_fabrica;";
            $res = pg_query($con, $sql);

            if (@pg_num_rows($res) == 0) {
                $sql = "SELECT
                            tbl_posto.nome  AS nome,
                            tbl_posto.email AS email
                        FROM
                            tbl_extrato
                        JOIN
                            tbl_posto ON (tbl_posto.posto = tbl_extrato.posto)
                        WHERE
                            tbl_extrato.extrato = $extrato
                            AND tbl_extrato.fabrica = $login_fabrica;";
                $res = pg_query($con, $sql);
            }

            $email_posto = @pg_fetch_result($res,0,'email');
            $nome_posto = @pg_fetch_result($res,0,'nome');

            $sql   = "SELECT email
            FROM tbl_admin
            WHERE tbl_admin.admin = {$login_admin}";

            $res   = pg_query($con,$sql);
            $email_admin = pg_fetch_result($res,0,'email');

            if($email_posto != ""){
                $remetente    = $email_admin;
                $destinatario = $email_posto;
                $assunto      = "Extrato Fricon $extrato liberado!\n";
                $mensagem     = "Prezado(a) {$extrato},\n";
                $mensagem    .="<br /><br />O(s) extrato(s) Fricon N∫ $extrato foi liberado, favor enviar a nota fiscal de prestaÁ„o de serviÁos para pagamento e informar no corpo da nota os dados banc·rios\n";
                $mensagem    .="<br /><br />----------\n";
                $mensagem    .="<br />Qualquer d˙vida entrar em contato com a Fricon.";
                $headers=  "From".$remetente."\nContent-type: text/html\n";

                mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers);
            }else{
                echo "<script language='javascript'>alert('N„o foi possivel encontrar o email do posto, favor atualizar os dados');</script>";
            }
        } 
        $btnacao = "";
		$extrato = "";
	
		if(strlen($msg_erro) == 0){
			$liberado = true;
			$res = pg_query ($con,"COMMIT TRANSACTION");
		}else
			$res = @pg_query ($con,"ROLLBACK TRANSACTION");
    }




    //HD 237498: Esta mensagem de erro tem que ficar depois do commit/rollback, pois È apenas informativa, n„o deve impedir que a transacao se concretize
    if (count($extrato_km_pendente)) {
        $extrato_km_pendente = implode(", ", $extrato_km_pendente);
        $msg_erro = "ATEN√«√O: Os extratos a seguir possuem OS em IntervenÁ„o de KM sem aprovaÁ„o/reprovaÁ„o e n„o ser„o liberados atÈ que seja definida uma posiÁ„o da f·brica em relaÁ„o a esta intervenÁ„o.<br>
        Extratos n„o liberados: $extrato_km_pendente";
    }
}

if ($btnacao == "acumular_tudo") {
    if (strlen($_POST["total_postos"]) > 0) $total_postos = $_POST["total_postos"];

    $res = pg_query($con,"BEGIN TRANSACTION");

    for ($i = 0 ; $i < $total_postos ; $i++) {
        $extrato = $_POST["acumular_" . $i];

        if (strlen($extrato) > 0) {
            $xextrato = $extrato;
            $sql = "SELECT fn_acumula_extrato ($login_fabrica, $extrato);";
            $res = pg_query($con,$sql);
            $msg_erro = pg_errormessage($con);

            if ( $login_fabrica == 24 ) {

                $sql = "UPDATE tbl_os_status
                            SET admin = $login_admin
                            WHERE extrato = $extrato";
                $res = pg_query($con,$sql);
                $msg_erro .= pg_errormessage($con);

            }

        }

        if (strlen($msg_erro) > 0) break;
    }

    $destinatario ="";
    if (strlen($msg_erro)==0 AND $login_fabrica==45){ //HD 66773
        if(strlen($xextrato)>0){
            $sql_email = "  SELECT tbl_posto_fabrica.contato_email
                            FROM tbl_extrato
                            JOIN tbl_posto_fabrica USING (posto)
                            WHERE tbl_posto_fabrica.fabrica = $login_fabrica
                            AND   tbl_extrato.extrato       = $xextrato";
            $res_email = pg_query($con, $sql_email);

            if(pg_num_rows($res_email)>0){
                $email_posto = pg_fetch_result($res_email,0,contato_email);
            }
        }
        $mensagem = "At. Respons·vel,<p>As Ordens de ServiÁo do extrato " . $xextrato . " foram acumuladas para o prÛximo mÍs.</p>\n";
        $mensagem.= "<p style='color:red'>NKS</p>";

        if(strlen($email_posto)>0){
            $destinatario= "$email_posto";
    //          $remetente   = "suporte@telecontrol.com.br";
            $remetente   = "maiara@nksonline.com.br";
            $assunto     = "Extrato $xextrato";
            $mensagem    = "<p style='center'>Nota: Este e-mail È gerado automaticamente. <br>".
                           "**** POR FAVOR N√O RESPONDA ESTA MENSAGEM ****.</p>" . $mensagem;
            $headers     ="From:$remetente\r\nContent-type: text/html\r\ncco:gustavo@telecontrol.com.br";
            if(strlen($mensagem)>0) mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers);
        }
    }
    else { header('Location: extrato_consulta.php'); }
    if (strlen($msg_erro) == 0) {
        $res = pg_query($con,"COMMIT TRANSACTION");
    }else{
        $res = pg_query($con,"ROLLBACK TRANSACTION");
    }
}

if ($_POST["agrupar_tudo"]) {

  if (count($_POST["dados"]) > 0) {

    $codigo_agrupado = "";
    $msg_erro = "";

    $res = pg_query($con,"BEGIN TRANSACTION");

    foreach ($_POST["dados"] as $key => $value) {
        $extrato = $value;

        if (empty($extrato)) {
          continue;
        }

        if (empty($codigo_agrupado)) {
          $sql_cod = "SELECT tbl_posto_fabrica.codigo_posto FROM tbl_extrato JOIN tbl_posto_fabrica USING(posto) WHERE extrato = $extrato AND tbl_posto_fabrica.fabrica = $login_fabrica";
          $res_cod = pg_query($con, $sql_cod);
          if (pg_num_rows($res_cod) > 0) {
            $codigo_agrupado = date('Ymd').pg_fetch_result($res_cod, 0, 'codigo_posto');
          }
        }

        $sql = "INSERT INTO tbl_extrato_agrupado (extrato, codigo, admin) VALUES ($extrato, '$codigo_agrupado', $login_admin) ";
        $res = pg_query($con, $sql);
        if (pg_last_error()) {
          $msg_erro .= "Erro ao agrupar o extrato: $extrato <br>"; 
        }
    }
    
    if (empty($msg_erro)) {
        $res = pg_query($con,"COMMIT TRANSACTION");
        echo 'success';
        exit();
    }else{
        $res = pg_query($con,"ROLLBACK TRANSACTION");
        echo 'error';
        exit();
    }
  }
  echo 'success';
  exit();
}


// ATEN√á√ÉO: NESTE ARQUIVO EXISTEM DUAS ROTINAS PARA APROVAR EXTRATO, UMA COM AJAX E OUTRA SEM
//          QUANDO FOR MODIFICAR UMA, VERIFIQUE SE √â NECESS√ÅRIO MODIFICAR A OUTRA
if (strlen($_GET["aprovar"]) > 0) $aprovar = $_GET["aprovar"]; // È o numero do extrato



if (strlen($aprovar) > 0){
    //HD 205958: Acrescentado validaÁ„o com BEGIN, COMMIT, ROLLBACK
    $res = pg_query($con,"BEGIN TRANSACTION");

    $km_pendente = false;

    //HD 237498: Barrar aprovaÁ„o de Extrato caso tenha OS em IntervenÁ„o de KM
    if (in_array($login_fabrica, $intervencao_km_extrato)) {
        $km_pendente = verifica_km_pendente_extrato($aprovar);
    } else {
        $km_pendente = false;
    }

    if ($km_pendente) {
        $msg_erro = "ATEN√«√O: O extrato $aprovar possui OS em IntervenÁ„o de KM sem aprovaÁ„o/reprovaÁ„o e n„o ser„o aprovados atÈ que seja definida uma posiÁ„o da f·brica em relaÁ„o a esta intervenÁ„o";
    } else {
        //atualiza campos de notas fiscais
        if ($login_fabrica == 20 || $login_fabrica == 14) {
            $nf_mao_de_obra = $_GET["nf_mao_de_obra"];
            if (strlen(trim($nf_mao_de_obra)) == 0) {
                $nf_mao_de_obra = 'null';
            }

            $nf_devolucao   = $_GET["nf_devolucao"];
            if (strlen(trim($nf_devolucao))==0) {
                $nf_devolucao = 'null';
            }

            $data_entrega_transportadora = $_GET["data_entrega_transportadora"];
            $data_entrega_transportadora = str_replace (" " , "" , $data_entrega_transportadora);
            $data_entrega_transportadora = str_replace ("-" , "" , $data_entrega_transportadora);
            $data_entrega_transportadora = str_replace ("/" , "" , $data_entrega_transportadora);
            $data_entrega_transportadora = str_replace ("." , "" , $data_entrega_transportadora);

            if (strlen ($data_entrega_transportadora) == 6) {
                $data_entrega_transportadora = "'".substr ($data_entrega_transportadora,0,4) . "20" . substr ($data_entrega_transportadora,4,2)."'";
            }

            if (strlen ($data_entrega_transportadora) > 0) {
                $data_entrega_transportadora = substr ($data_entrega_transportadora,0,2) . "/" . substr ($data_entrega_transportadora,2,2) . "/" . substr ($data_entrega_transportadora,4,4);
                if (strlen ($data_entrega_transportadora) < 8) $data_entrega_transportadora = date ("d/m/Y");
                $data_entrega_transportadora = "'".substr ($data_entrega_transportadora,6,4) . "-" . substr ($data_entrega_transportadora,3,2) . "-" . substr ($data_entrega_transportadora,0,2)."'";
                } else {
                $data_entrega_transportadora = 'null';
            }

            $sql = "UPDATE tbl_extrato_extra SET
                        nota_fiscal_mao_de_obra     = '$nf_mao_de_obra',
                        nota_fiscal_devolucao       = '$nf_devolucao',
                        data_entrega_transportadora = $data_entrega_transportadora
                    WHERE extrato = $aprovar";

            $res = pg_query($con,$sql);

            if (pg_errormessage($con)) {
                $msg_erro = "Ocorreu um erro na aprovaÁ„o do extrato $aprovar";
            }
            #  HD 4846 - Colocado!

            $sql = "
            UPDATE
            tbl_extrato_extra

            SET
            admin = $login_admin

            WHERE
            extrato = $aprovar
            ";
            $res = pg_query($con, $sql);
            if (pg_errormessage($con)) {
                $msg_erro = "Ocorreu um erro na aprovaÁ„o do extrato $aprovar";
            }

        }

        //PARA A INTELBR√ÅS DEIXAR ELE APROVAR EXTRATO, POIS ELES EST√ÉO EM PROCESSO DE TRANSI√á√ÉO, SEGUNDO A RAMONNA
        $sql = "SELECT fn_aprova_extrato($posto,$login_fabrica,$aprovar)";
        $res = pg_query($con,$sql);

        if (pg_errormessage($con)) {
            $msg_erro = "Ocorreu um erro na aprovaÁ„o do extrato $aprovar: " . pg_errormessage($con);
        }

        if (strlen($msg_erro) == 0) {
            $res = pg_query($con,"COMMIT TRANSACTION");
            header("Location: $PHP_SELF?btnacao=filtrar&extrato=$extrato&posto=$posto&data_inicial=$data_inicial&data_final=$data_final&cnpj=$xposto_codigo&razao=$posto_nome");
        } else {
            $res = pg_query($con,"ROLLBACK TRANSACTION");
        }

    }

}

$layout_menu = "financeiro";
$title = traduz("CONSULTA E MANUTEN«√O DE EXTRATOS");

include "cabecalho.php";

?>
<p>

<style type="text/css">
body{ font-family: Verdana, Geneva, Arial, Helvetica, sans-serif; font-size: 12px; }
.menu_top {
text-align: center;
font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
font-size: 10px;
font-weight: bold;
border: 1px solid;
background-color: #D9E2EF
}
.table_line {
text-align: left;
font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
font-size: 10px;
font-weight: normal;
border: 0px solid;
background-color: #D9E2EF
}
.table_line2 {
text-align: left;
font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
font-size: 10px;
font-weight: normal;
}
.quadro{
border: 1px solid #596D9B;
width:450px;
height:50px;
padding:10px;
}
.botao {
border-top: 1px solid #333;
border-left: 1px solid #333;
border-bottom: 1px solid #333;
border-right: 1px solid #333;
font-size: 13px;
margin-bottom: 10px;
color: #0E0659;
font-weight: bolder;
}
.texto_padrao {
font-size: 12px;
}
#Formulario tbody th{
text-align: left;
font-weight: bold;
}
#Formulario tbody td{
text-align: left;
font-weight: none;
}
.titulo_tabela{
background-color:#596d9b;
font: bold 14px "Arial";
color:#FFFFFF;
text-align:center;
}
.titulo_coluna{
background-color:#596d9b !important;
font: bold 11px "Arial" !important;
color:#FFFFFF !important;
text-align:center !important;
}
.msg_erro{
background-color:#FF0000;
font: bold 16px "Arial";
color:#FFFFFF;
text-align:center;
}
.formulario{
background-color:#D9E2EF;
font:11px Arial;
}
.subtitulo{
color: #7092BE
}
table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border:1px solid #ACACAC;
    border-collapse: collapse;
}
.msg_sucesso{
background-color: green;
font: bold 16px "Arial";
color: #FFFFFF;
text-align:center;
}

table.tabela tr th{
color: #FFFFFF !important ;
border:1px solid #ACACAC;
border-collapse: collapse;
}

.ms-parent{
    width: 200px !important;
}

#ms{
    border-radius: 0px !important;
    height: 15px !important;
}

 .ms-choice {
          border-radius: 0px !important;
          border-color: #888 !important;
          border-style: solid;
          border-width: 1px !important;
          background-color:#F0F0F0 !important;
          height: 18px !important;
}

table.tablesorter tbody td{
    background-color: transparent !important;
}

.aprovar_nfe, 
.reprova_nfe, 
.visualizar_nfe {
  color: white;
  cursor: pointer;
  padding: 10px;
  border-radius: 3px;
  border: none;
  font-family: sans-serif;
  font-size: 12px;
  font-weight: bolder;
}
.aprovar_nfe {
  background-color: darkblue;
}
.aprovar_nfe:hover {
  background-color: #0024f2;
  transition: 0.25s ease;
}
.reprova_nfe {
  background-color: darkred;
}
.reprova_nfe:hover {
  background-color: #f00202;
  transition: 0.25s ease;
}
.visualizar_nfe {
  background-color: #008a91;
}
.visualizar_nfe:hover {
  background-color: #02d6c5;
  transition: 0.25s ease;
}

.btn_excel {
    display: block;
    cursor: pointer;
    width: 300px;
    margin: 0 auto;
}

.btn_excel span {
    display: inline-block;
}

.btn_excel span.txt {
    color: #FFF;
    font-size: 14px;
    font-weight: bold;
    border-radius: 4px 4px 4px 4px;
    border-width: 1px;
    border-style: solid;
    border-color: #4D8530;
    background: -moz-linear-gradient(top, #559435 0%, #63AE3D 72%);
    background: -webkit-linear-gradient(top, #559435 0%, #63AE3D 72%);
    background: -o-linear-gradient(top, #559435 0%, #63AE3D 72%);
    background: -ms-linear-gradient(top, #559435 0%, #63AE3D 72%);
    background: linear-gradient(top, #559435 0%, #63AE3D 72%);
    filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#559435', endColorstr='#63AE3D',GradientType=1 );
    line-height: 18px;
    padding-right: 3px;
    padding-left: 3px;
}

.btn_excel span img {
    width: 20px;
    height: 20px;
    border: 0px;
    vertical-align: middle;
}

<?php if($login_fabrica == 1){ ?>

    table.tablesorter tbody td{
        background-color: transparent !important;
    }

<?php } ?>

</style>

<!--[if lt IE 8]>
<style>
table.tabela{
    empty-cells:show;
    border-collapse:collapse;
    border-spacing: 2px;
}
</style>
<![endif]-->

<?php include "javascript_calendario.php"; ?>

<?php include "../js/js_css.php"; ?>

<?php
    $plugins = array("font_awesome");
?>

<?php include("plugin_loader.php"); ?>
<link rel="stylesheet" href="css/multiple-select.css" />
<script src="js/jquery.multiple.select.js"></script>
<script>
    $(function() {
        $('#ms').multipleSelect({
            width: '100%',
            selectAllText:'Selecionar Todos',
            allSelected: 'Todos Selecionados',
            includeSelectAllOption: true,
            numberDisplayed: 1
        });

        $('#estados').multipleSelect({
            buttonWidth: '100%',
            selectAllText:'Selecionar Todos',
            allSelected: 'Todos Selecionados',
            includeSelectAllOption: true,
            numberDisplayed: 1
        });

        $('#unidade_negocio').multipleSelect({
            width: '500px;',
            selectAllText:'Selecionar Todos',
            allSelectedText: 'Todos Selecionados'
        });

        $(".btn-desbloquear-extrato").click(function(){

          let that = $(this);
          let idExtrato = $(that).data("extrato");

          $.ajax({
                type: "POST",
                url: location.href,
                data: {
                  desbloquear_extrato: true,
                  extrato: idExtrato
                },
                error: function () {
                    alert('Falha na solicitaÁ„o');
                },
                complete: function(http){
                    retorno = JSON.parse(http.responseText);
                    
                    if (!retorno.erro) {
                      $(that).closest("td").html("<span style='color: darkgreen;'>Extrato Desbloqueado!</span>");
                    } else {
                      alert("Erro ao desbloquear extrato");
                    }

                }
            });

        });

        $(".aprovar_nfe").click(function(){

          let that    = $(this);
          let extrato = $(that).data("extrato");
          let link    = $(that).data("link");

          $.ajax({
            type: "POST",
            url: location.href,
            data: {
              gravaNfeVisualizada: true,
              extrato: extrato
            },
            dataType: "json",
            error: function () {
                alert('Falha na solicitaÁ„o');
            },
            complete: function(retorno){
                
                if (!retorno.erro) {
                  $(that).hide("fast");
                  $(that).closest("td").next("td").find(".reprova_nfe").hide("fast");
                  alert("NFe aprovada com sucesso!");
                } else {
                  alert("Erro ao marcar NFe como visualizada");
                }

            }
          });

        });

        $(".visualizar_nfe").click(function(){

          let extrato = $(this).data("extrato");

            Shadowbox.init();
            Shadowbox.open({
                content : "view_box_uploader.php?tempUniqueId="+extrato+"&contexto=extrato",
                player  : "iframe",
                title   : "Anexos do Extrato "+extrato,
                width   : 1000,
                height  : 700
            });

        });

        $(".reprova_nfe").click(function(){

          let extrato = $(this).data("extrato");

          Shadowbox.init();
          Shadowbox.open({
              content : "reprova_nfe_servico.php?extrato="+extrato,
              player  : "iframe",
              title   : "Digite a justificativa da reprova",
              width   : 500,
              height  : 250
          });
          
        });

    });

    function aprovarNF(extrato) {
        if (confirm('Tem certeza que deseja aprovar a Nota fiscal?') == true) {
            $.ajax({
                type: "POST",
                url: "<?= $PHP_SELF; ?>",
                data: 'ajax=aprovarnf&extrato='+extrato,
                error: function () {
                    alert('Falha na solicitaÁ„o');
                },
                complete: function(http){
                    retorno = JSON.parse(http.responseText);
                    if (typeof retorno.erro != 'undefined' && retorno.erro.length > 0) {
                        alert(retorno.erro);
                    } else {
                        alert(retorno.sucesso);
                        $("#btnAprovarNf").hide();
                        $("#btnReprovarNf").hide();
                    }
                }
            });
        } else {
            return false;
        }
    }


    function reprovarNFFull(extrato) {
      $.ajax({
            type: "POST",
            url: "<?= $PHP_SELF; ?>",
            data: 'ajax=reprovarnf&extrato='+extrato,
            error: function () {
                alert('Falha na solicitaÁ„o');
            },
            complete: function(http){
                retorno = JSON.parse(http.responseText);
                if (typeof retorno.erro != 'undefined' && retorno.erro.length > 0) {
                    alert(retorno.erro);
                } else {
                    alert(retorno.sucesso);
                    $("#btnAprovarNf").hide();
                    $("#btnReprovarNf").hide();
                }
            }
        });

    }

    function reprovarNF(extrato) {
        if (confirm('Tem certeza que deseja reprovar a Nota fiscal?') == true) {
            Shadowbox.init();
            Shadowbox.open({
                content : "modal_justificativa_reprova.php?extrato="+extrato,
                player  : "iframe",
                title   : "Digite a justificativa da reprova",
                width   : 500,
                height  : 250
            });
        } else {
            return false;
        }
    }

</script>

<script type="text/javascript">

    $(document).ready(function()
    {

        //$("#regiao").multiSelect();

       // $("#estados").multiSelect();

        <?php if($login_fabrica == 20){?>
        $("#grid_list").tablesorter({
            widgets: ["zebra"],
            headers:{
                0:{
                    sorter: false
                },
                1:{
                    sorter: false
                }
            }
        });
        <?php } ?>

        Shadowbox.init();

        // HD 679624
        $("#acumula_extratos").click(function(e)
        {
            if( confirm("Deseja realmente acumular o(s) extrato(s) para o prÛximo m√™s?") )
            {
                document.Selecionar.btnacao.value="acumular_tudo" ;
                document.Selecionar.submit();
            }
            e.preventDefault();
            return false;
        });

        $(".acumula_extrato").click(function(e)
        {
            if( confirm("Deseja realmente acumular o extrato para o prÛximo m√™s?"))
            {
                $(this).parent().find('input').attr('checked','checked');
                document.Selecionar.btnacao.value="acumular_tudo" ;
                document.Selecionar.submit();
            }
            e.preventDefault();
        });
        $(".date").datepick();
        $("#data_inicial").mask("99/99/9999");
        $("#data_final").mask("99/99/9999");

        <?php if (!in_array($login_fabrica,[180, 181, 182])) { ?>
          $("#posto_codigo").mask("99.999.999/9999-99");
        <? } ?>
        $(".data_entrega_transportadora").mask("99/99/9999");

        $("input[id^=encontro_contas_]").click(function(){

            var extrato = $(this).attr("rel");

            Shadowbox.open({
                content : "detalhe_encontro_contas.php?extrato="+extrato,
                player  : "iframe",
                title   : "Detalhe encontro de contas",
                width   : 800,
                height  : 250
            });
        });

<?
if($login_fabrica == 1){
?>
        $("#valor_abaixo").css("text-align","right");
        $("#valor_abaixo").maskMoney({
            showSymbol:"",
            symbol:"",
            decimal:",",
            precision:2,
            thousands:".",
            maxlength:10
        });
<?
}
?>
    
        <?php if ($login_fabrica == 183){ ?>
            $("#grid_list").tablesorter({
                widgets: ["zebra"],
                emptyTo: 'bottom',
                headers:{
                    6:{
                        sorter: false
                    },
                    7:{
                        sorter: false
                    },
                    8:{
                        sorter: false
                    },
                    9:{
                        sorter: false
                    },
                    10:{
                        sorter: false
                    },
                    11:{
                        sorter: false
                    },
                    12:{
                        sorter: false
                    },
                    13:{
                        sorter: false
                    },
                    14:{
                        sorter: false
                    }
                }
            });
        <?php } ?>
    });

<?php
if($login_fabrica == 151){
?>

function insere_nf_servico(nf_servico, extrato){
    $(".nf-servico-"+extrato).html("<a href='../nota_servico_extrato.php?extrato="+extrato+"' rel='shadowbox; width= 400; height= 350;'>"+nf_servico+"</a>");
    Shadowbox.setup();
}

$(function () {

  $("#agrupar_tudo").click(function () {
    let dados = [];
    $("#agrupar:checked").each(function(){
      dados.push($(this).val());
    });

    $.ajax({
      type  : "POST",
      url: "<?php echo $_SERVER['PHP_SELF']; ?>",
      data: { agrupar_tudo: true, dados: dados },
      success: function(data){
        if(data == 'success') {
          alert('Extratos Agrupados Com Sucesso !');
          window.location.reload();
        } else {
          alert('Erro ao Agrupar os Extratos');
          window.location.reload();
        }
      }
    });
  });

});

<?php
}
?>

function liberar_todos() {
  const extratos = [];

  $("input[name^='liberar_']").each(function(){
    if($(this).is(":checked")){
      var posicao = $(this).data('posicao');
      var extrato_linha = $(this).val(); 

      $('.carregando_ajax_hide').hide();
      $('.carregando_ajax_show').css("display", "block");
      $('.extrato_novo_'+extrato_linha).hide();
      $('.extrato_adicionar_'+extrato_linha).hide();
      $('input[name=liberar_'+posicao+']').hide();
      
      extratos.push(new Promise(function(resolve, reject) {
        $.ajax({
                type : "POST",
                url  : "<?php echo $_SERVER['PHP_SELF']; ?>",
                data : { liberar_todos : true, extrato : extrato_linha, posicao : posicao },
                async: true
                
              }).fail(function(res) {
                reject({
                  extrato: extrato_linha
                });
              }).done(function(res, req) {
                  if (req == 'success') {
                    resolve({
                      extrato: extrato_linha
                    });
                  } else {
                    reject({
                      extrato: extrato_linha
                    });
                  }
              });
      }));   
    }
  });

  Promise.all(extratos)
  .then(function(response) {
    if (response.length == extratos.length) {
      setTimeout(function(){ alert('OperaÁ„o ConcluÌda'); window.location.reload(); });      
    }
  })
  .catch(function(response) {
    setTimeout(function(){ alert('Erro na ExecuÁ„o'); window.location.reload(); });      
  });
}

function conferir(extrato){
      $.ajax({
          type  : "POST",
          url: "<?php echo $_SERVER['PHP_SELF']; ?>",
          data: { conferido: true, extrato: extrato },
          success: function(data){
              if(data == 'ok'){
                  $("#recusado_"+extrato).hide();
                  $("#conferido_"+extrato).hide();
                  window.location.reload();
              }if(data == 'erro'){
                  alert("Extrato n„o Conferido")
                  window.location.reload();
              }
          }
      });
  }

function recusar(extrato, posto){

  var motivo = prompt("Informe o motivo de recusa da NFe. ");

  $.ajax({
          type  : "POST",
          url: "<?php echo $_SERVER['PHP_SELF']; ?>",
          data: { recusado: true, extrato: extrato, posto:posto,  motivo:motivo },
          success: function(data){
              if(data == 'ok'){
                  $("#recusado_"+extrato).hide();
                  $("#conferido_"+extrato).hide();
                  $("#visualizar_"+extrato).hide();
                  window.location.reload();
              }
          }
      });

}
function liberar(extrato, autorizacao)
{
  Shadowbox.open({
      content : "extrato_consulta_liberar.php?extrato="+extrato+"&autorizacao="+autorizacao,
      player  : "iframe",
      title   : "Confirmar RequisiÁ„o",
      width   : 800,
      height  : 500
  }); 
}

function confirmar_liberacao(num_requisicao, extrato, autorizacao) 
{

  $.ajax({
    type  : "GET",
    url   : "extrato_consulta.php",
    data  : 'liberar=' + extrato + '&autorizacao=' + autorizacao + "&num_requisicao=" + num_requisicao+"&ajax_liberar=true",
    cache : false,
    dataType: "json",

    success: function(data) {
      
      console.log(data.erro);
      if (data.erro == false) {
        $("#btn_confirmar_liberacao").hide();
        alert("Extrato Liberado");
      } 
  
    },

  });
}

function pesquisaPosto(campo,tipo)
{
    var campo = campo.value;

    if( jQuery.trim(campo).length > 2 )
    {
        Shadowbox.open({
            content : "posto_pesquisa_nv.php?"+tipo+"="+campo+"&tipo="+tipo,
            player  : "iframe",
            title   : "Pesquisa Posto",
            width   : 800,
            height  : 500
        });
    }else alert("Informar toda ou parte da informaÁ„o para realizar a pesquisa!");
}

function retorna_posto(posto,codigo_posto,nome,cnpj,pais,cidade,estado,nome_fantasia)
{
    gravaDados('codigo_posto_codigo', codigo_posto);
    gravaDados('posto_nome', nome);
    gravaDados('posto_codigo', cnpj);
}

function gravaDados(name, valor)
{
    try {
        $("input[name="+name+"]").val(valor);
    }catch(err){
        return false;
    }
}

function somente_numero(campo)
{
    var digits = "0123456789-./"
    var campo_temp;
    for( var i=0; i<campo.value.length; i++ )
    {
        campo_temp = campo.value.substring(i, i+1);
        if( digits.indexOf(campo_temp)==-1 )
        {
            campo.value = campo.value.substring(0, i);
            break;
        }
    }
}
</script>

<script type="text/javascript">
// HD 22752
function refreshTela(tempo){ window.setTimeout("window.location.href = window.location.href", tempo); }

$(document).ready(function()
{
    function formatItem(row){
        return row[0] + " - " + row[1];
    }

    function formatResult(row){
        return row[0];
    }

    /* Busca pelo CÛdigo */
    $("#posto_codigo").autocomplete("<?php echo $PHP_SELF.'?busca=codigo'; ?>", {
        minChars: 3,
        delay: 150,
        width: 350,
        matchContains: true,
        formatItem: formatItem,
        formatResult: function(row) {return row[0];}
    });

    $("#posto_codigo").result(function(event, data, formatted){
        $("#posto_nome").val(data[1]);
    });

    /* Busca pelo Nome */
    $("#posto_nome").autocomplete("<?php echo $PHP_SELF.'?busca=nome'; ?>", {
        minChars: 3,
        delay: 150,
        width: 350,
        matchContains: true,
        formatItem: formatItem,
        formatResult: function(row){ return row[1]; }
    });

    $("#posto_nome").result(function(event, data, formatted)
    {
        $("#posto_codigo").val(data[0]); //alert(data[2]);
    });
});
</script>

<script type="text/javascript">
/* ============= FunÁ„o PESQUISA DE POSTOS ====================
Nome da FunÁ„o : fnc_pesquisa_posto (cnpj,nome)
        Abre janela com resultado da pesquisa de Postos pela
        CÛdigo ou CNPJ (cnpj) ou Raz„o Social (nome).
=================================================================*/

function fnc_pesquisa_posto(campo, campo2, tipo)
{
    if( tipo == "nome" ){ var xcampo = campo;  }
    if( tipo == "cnpj" ){ var xcampo = campo2; }

    if( xcampo.value != "" )
    {
        var url        = "";
        url            = "posto_pesquisa.php?campo=" + xcampo.value + "&tipo=" + tipo ;
        janela         = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=300, top=0, left=0");
        janela.retorno = "<?php echo $PHP_SELF; ?>";
        janela.nome    = campo;
        janela.cnpj    = campo2;
        janela.focus();
    }else{
        alert('Preencha toda ou parte da informaÁ„o para realizar a pesquisa!');
    }
}

var checkflag = "false";

function  check(field){

    $("input[name^='liberar_']").each(function(){
        if($(this).is(":checked")){
            $(this).prop("checked",false);
        }else{
            $(this).prop("checked",true);
        }
    });
}


/*function check(field)
{
    alert("teste "+ field);
    console.log(field);
    if( checkflag == "false" )
    {
        for( i=0; i<field.length; i++ )
        {
            field[i].checked = true;
        }
        checkflag = "true";
        return true;
    }
    else
    {
        for( i=0; i<field.length; i++ )
        {
            field[i].checked = false;
        }
        checkflag = "false";
        return true;
    }
}
*/
function AbrirJanelaObs(extrato)
{
    var largura  = 400;
    var tamanho  = 250;
    var lar      = largura / 2;
    var tam      = tamanho / 2;
    var esquerda = (screen.width / 2)  - lar;
    var topo     = (screen.height / 2) - tam;
    var link     = "extrato_status.php?extrato=" + extrato;
    window.open(link, "janela", "toolbar=no, location=no, status=yes, menubar=no, scrollbars=no, directories=no, resizable=no, width=" + largura + ", height=" + tamanho + ", top=" + topo + ", left=" + esquerda + "");
}

function gerarExportacao(but)
{
    if( but.value == 'Exportar Extratos' )
    {
        if( confirm('Deseja realmente prosseguir com a exportaÁ„o?\n\nSer· exportado somente os extratos aprovados e liberados.') )
        {
            but.value='Exportando...';
            exportar();
        }
    }
    else
    {
         alert('Aguarde submiss„o');
    }

}

function retornaExporta(http)
{
    if( http.readyState == 4 )
    {
        if( http.status == 200 )
        {
            results = http.responseText.split("|");

            if( typeof (results[0]) != 'undefined' )
            {
                if( results[0] == 'ok' )
                {
                    alert(results[1]);
                }
                else
                {
                    alert (results[1]);
                }
            }
            else
            {
                alert("N„o existe extratos a serem exportados.");
            }
        }
    }
}

function exportar()
{
    url = "<?= $PHP_SELF ?>?exportar=sim";
    http.open("GET", url , true);
    http.onreadystatechange = function(){ retornaExporta(http); };
    http.send(null);
}
</script>

<script type="text/javascript">
function createRequestObject()
{
    var request_;
    var browser = navigator.appName;
    if( browser == "Microsoft Internet Explorer" )
    {
        request_ = new ActiveXObject("Microsoft.XMLHTTP");
    }
    else
    {
        request_ = new XMLHttpRequest();
    }
    return request_;
}

var http_data = new Array();
var semafaro  = 0;

function aprovaExtrato(extrato , posto, aprovar, novo,adicionar,acumular,resposta, classTr)
{
    if( semafaro == 1 )
    {
        alert('Aguarde alguns instantes antes de aprovar outro extrato.');
        return;
    }

    if( confirm('Deseja aprovar este extrato?')==false ){ return; }

    var curDateTime = new Date();
    semafaro  = 1;
    url       = "<?=$PHP_SELF?>?ajax=APROVAR&aprovar=" + escape(extrato)+ "&posto=" + escape(posto)+"&data="+curDateTime;
    aprovar   = document.getElementById(aprovar);
    novo      = document.getElementById(novo);
    adicionar = document.getElementById(adicionar);
    acumular  = document.getElementById(acumular);
    resposta  = document.getElementById(resposta);

    http_data[curDateTime] = createRequestObject();
    http_data[curDateTime].open('POST',url,true);
    http_data[curDateTime].setRequestHeader("X-Requested-With","XMLHttpRequest");


    http_data[curDateTime].onreadystatechange = function()
    {
        if( http_data[curDateTime].readyState == 4 )
        {
            if( http_data[curDateTime].status == 200 || http_data[curDateTime].status == 304 )
            {
                var response = http_data[curDateTime].responseText.split(";");

                if( response[0]=="ok" )
                {
                    if( aprovar   ) aprovar.src         = '/assist/imagens/pixel.gif';
                    if( novo      ) novo.src            = '/assist/imagens/pixel.gif';
                    if( adicionar ) adicionar.src       = '/assist/imagens/pixel.gif';
                    if( acumular  ) { acumular.disabled = true; acumular.style.visibility = "hidden"; }
                    if( resposta  ) resposta.innerHTML  = "Aprovado";
                    $("."+classTr).hide();
                }else{
                    alert('Extrato n„o foi aprovado. Tente novamente.');
                }
                semafaro = 0;
            }
        }
    }
    http_data[curDateTime].setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=iso-8859-1");
    http_data[curDateTime].setRequestHeader("Cache-Control", "no-store, no-cache, must-revalidate");
    http_data[curDateTime].setRequestHeader("Cache-Control", "post-check=0, pre-check=0");
    http_data[curDateTime].setRequestHeader("Pragma", "no-cache");
    http_data[curDateTime].send('');
}

function createRequestObject()
{
    var request_;
    var browser = navigator.appName;
    if( browser == "Microsoft Internet Explorer" )
    {
         request_ = new ActiveXObject("Microsoft.XMLHTTP");
    }
    else
    {
         request_ = new XMLHttpRequest();
    }
    return request_;
}

var http_forn  = new Array();
var conta_tudo = 0;

<?php
    /* HD 38185 */
    if( $login_fabrica == 35 or $login_fabrica == 15 ){
        echo " conta_tudo = 1;";
    }
?>

function conta_os(extrato,div,contador)
{
    var extrato = extrato;
    var div     = document.getElementById(div);
    var url     = 'conta_os_ajax.php?extrato=' + extrato + '&cache_bypass=<?= $cache_bypass ?>' ;

    $.ajax({
            type  : "GET",
            url   : "conta_os_ajax.php?extrato=",
            data  : 'extrato=' + extrato + '&cache_bypass=<?= $cache_bypass ?>',
            cache : false,
            beforeSend: function(){
                // enquanto a funÁ„o esta sendo processada, voc√™
                // pode exibir na tela uma
                // msg de carregando
                $(div).html("Espere...");
            },
            success: function(txt){
                // pego o id da div que envolve o select com
                // name="id_modelo" e a substituiu
                // com o texto enviado pelo php, que È um novo
                //select com dados da marca x
                $(div).html(txt);
            },
            error: function(txt){ alert(txt); }
        });
    //  $(div).html(qtde);
}
/*
function conta_os_tudo()
{
    var total = document.getElementById('total_res').value;
    //console.log(total);

    for( i=0; i<total; i++ )
    {
        extrato = document.getElementById('extrato_tudo_'+i).value;
        var div = document.getElementById('qtde_os_'+i);

        $(div).html("Espere...");
        var url  = 'conta_os_ajax.php?extrato=' + extrato + '&cache_bypass=<?= $cache_bypass ?>';
        var qtde = $.ajax({ type  : "GET",
                            url   : url,
                            cache : false,
                            async : false }).responseText;
        $(div).html(qtde);
    }

    contadorOS();
}*/


<?php
if($login_fabrica == 20){
?>
var extrato = "";

function contadorOS(){

    var valor = 0;
    $('div[id^=qtde_os_]').each(function(){
        var valorOS = $(this).text();
        valor += parseInt(valorOS);
    });

    $("#qtdeOS").text('Ordem Servi√ßo: '+valor);

}

<?
}
?>

function addCommas(nStr)
{
    nStr += '';
    x     = nStr.split('.');
    x1    = x[0];
    x2    = x.length > 1 ? '.' + x[1] : '';
    var rgx = /(\d+)(\d{3})/;
    while( rgx.test(x1) )
    {
        x1 = x1.replace(rgx, '$1' + ',' + '$2');
    }
    return x1 + x2;
}

function somarExtratos(selecionar){
    if( selecionar == 'todos' )
    {
        $("input[rel='somatorio']").each(function(){ this.checked = true; });
    }

    var total_extratos = 0;

    $("input[rel='somatorio']:checked").each(function (){
        if( this.checked ){ total_extratos += parseFloat(this.value); }
    });

    total_extratos = total_extratos.toFixed(2);
    $('#total_extratos').html('Soma dos extratos selecionados: <b>R$ '+addCommas(total_extratos)+'</b>');
}

function selecionaTodos(){

    // if( $('#checkAll').attr('checked')==true ){
    //     $('#grid_list input[name*="extrato_"]').each(function(indice){ this.checked = true; });
    // }else{
    //     $('#grid_list input[name*="extrato_"]').each(function(indice){ this.checked = false; });
    // }

    if ($("#checkAll").attr("checked")){
      $('.check').each(
        function(){
            $(this).attr("checked", true);
        }
      );
   }else{
      $('.check').each(
         function(){
            $(this).attr("checked", false);
         }
      );
   }
}

function selecionarExtratos(){

   if ($("#checar").attr("checked")){
      $('.check1').each(
        function(){
            $(this).attr("checked", true);
            if($(this).parents('tr').find('input[name^=aprovado_]').val()){
                $(this).attr("checked", false);
            }
        }
      );
   }else{
      $('.check1').each(
         function(){
            $(this).attr("checked", false);
         }
      );
   }

}



function aprovarTodos(){

    var confirm1 = confirm('Deseja aprovar todos extratos selecionados ?');
      if (confirm1) {
        $('.check1').each(function(){

            if($(this).is(":checked")){

                var nf_mao_obra = $(this).parents('tr').find('input[name^=nota_fiscal_mao_de_obra_]').val();
                var nf_devolucao = $(this).parents('tr').find('input[name^=nota_fiscal_devolucao_]').val();
                var entrega_transportadora = $(this).parents('tr').find('input[name^=data_entrega_transportadora_]').val();
                var extrato = $(this).val();

                $.ajax({

                    url: "<?php echo $_SERVER['PHP_SELF']; ?>",
                    type: "POST",
                    data: {aprovarCheck: 'ok', nf_mao_obra: nf_mao_obra, nf_devolucao: nf_devolucao, entrega_transportadora: entrega_transportadora, extrato: extrato },
                    complete: function(data){
                        var dados = data.responseText;

                        if(dados == extrato){
                            $('.extrato_aprova_'+extrato).hide();
                            $('.extrato_novo_'+extrato).hide();
                            $('.extrato_adicionar_'+extrato).hide();
                            $("label[for='extrato_aprovado_"+extrato+"']").css("display","block").html('Aprovado');

                            //setTimeout("location.reload();", 3000);
                        }else{
                            $('.extrato_aprova_'+extrato).hide();
                            $("label[for='extrato_aprovado_"+extrato+"']").css("display","block").html(data.responseText);
                        }
                    }
                });
            }
        });
      } else {
        return false;
      }

    //funÁ„o each para pegar os selecionados
}

function calcularExtrato()
{
    extrato = "";

    // $('#grid_list input[name^="extrato_"]').each(function(indice){
    $('.extrato_calcula').each(function(indice){
        var value = $(this).val();

        if( $(this).is(':checked'))
        {
            if( parseFloat(extrato) > 0 )
            {
                extrato += ","+value;
            }else
                extrato = $(this).val();
        }
    });

    if( parseFloat(extrato) > 0 )
    {
        Shadowbox.open({
            // content : "calculo_extratos.php?extratos="+extrato,
            content : "calculo_extratos.php",
            player  : "iframe",
            title   : "C·lculo de extratos",
            width   : 800,
            height  : 250
        });
    }else{
        alert("Check os extratos para o c·lculo!");
    }
}

function getExtrato(){
    return extrato;
}

<?php
if($login_fabrica == 50){
?>

function liberar_lrg_provisorio(extrato){

  if(extrato != ""){

    var status = "";

    if($("#extrato_lgr_"+extrato).is(":checked")){
      status = "checked";
    }else{
      status = "no_checked";
    }

    $.ajax({
      url: "<?php echo $_SERVER['PHP_SELF']; ?>",
      type: "post",
      data: {
        lgr_provisorio: true,
        extrato: extrato,
        status: status
      },
      complete: function(data){
        data = data.responseText;
        console.log(data);
      }
    });

  }

}

<?php
}
?>

<?php
if($login_fabrica == 1){
?>

function inibir_extrato(extrato){

    var inibir = ($("#inibir_extrato_"+extrato).is(":checked")) ? true : false;

    $.ajax({
        url : "<?php echo $_SERVER['PHP_SELF']; ?>",
        type: "POST",
        data: {
            inibir_extrato : true,
            inibir : inibir,
            extrato : extrato
        },
        complete: function(data){

            data = JSON.parse(data.responseText);

            if(data.sucesso){

                if(inibir == true){
                    $("tr.linha_"+extrato).attr({"bgcolor" : "#ffffb2"});
                }else{
                    $("tr.linha_"+extrato).attr({"bgcolor" : "#ffffff"});
                }

            }else{
                alert("Erro ao inibri o Extrato");
            }

        }
    });

}

<?php
}
?>

</script>

<? if(in_array($login_fabrica, array(50,91,138,152,180,181,182))){?>

<script type='text/javascript'>

    $(function(){

            $("#download_excel").click(function(){
                var extrato             = $("#extrato").val();
                var data_inicial        = $("#data_inicial").val();
                var data_final          = $("#data_final").val();
                var data_baixa_inicio   = $("#data_baixa_inicio").val();
                var data_baixa_fim      = $("#data_baixa_fim").val();
                var posto_codigo        = $("#posto_codigo").val();
                var posto_nome          = $("#posto_nome").val();

                <?php if(in_array($login_fabrica, array(152,180,181,182))){?>
                    var filtro_status          = $(".filtro_status:checked").val();
					var regiao_estado       = $("#regiao_estado").val();
                <?php }else{  ?>
                      var filtro_status = "";
                      var regiao_estado = "";
                  <?php } ?>

                $.ajax({
                    url:"relatorio_consulta_extratos.php",
                    type:"POST",
                    data:{
                        gerar_excel:     1,
                        extrato          :extrato          ,
                        data_inicial     :data_inicial     ,
                        data_final       :data_final       ,
                        data_baixa_inicio:data_baixa_inicio,
                        data_baixa_fim   :data_baixa_fim   ,
                        posto_codigo     :posto_codigo     ,
                        filtro_status    :filtro_status    , 
            						posto_nome       :posto_nome,
            						regiao_estado    : regiao_estado

                    },
                    complete: function(data){
                        console.log(data.responseText);
                        window.open(data.responseText, "_blank");
                    }
                });
            });

        // add new widget called repeatHeaders
        // $.tablesorter.addWidget({
        //     // give the widget a id
        //     id: "repeatHeaders",
        //     // format is called when the on init and when a sorting has finished
        //     format: function(table){
        //         // cache and collect all TH headers
        //         if( !this.headers )
        //         {
        //             var h = this.headers = [];
        //             $("thead th",table).each(function(col){
        //                 h.push("<td colspan='"+$(this).attr('colspan')+"'>" + $(this).text() + "</td>");
        //             });
        //         }

        //         $("tr.repated-header",table).remove(); // remove appended headers by classname.

        //         // loop all tr elements and insert a copy of the "headers"
        //         for( var i=0; i < table.tBodies[0].rows.length; i++ )
        //         {
        //             // insert a copy of the table head every 10th row
        //             if( (i%20) == 0 )
        //             {
        //                 if( i!=0 )
        //                 {
        //                     $("tbody tr:eq(" + i + ")",table).before(
        //                         $("<tr></tr>").addClass("repated-header").html(this.headers.join(""))
        //
        //                     );
        //                 }
        //             }
        //         }
        //     }
        // });
        // $("table").tablesorter({
        //     widgets: ['zebra','repeatHeaders']
        // });
        //conta_os_tudo();
    });
</script>

<? } ?>

<?php

if(strlen($btnacao) > 0){

    if(in_array($login_fabrica, array(1,35))){

        $estados   = $_POST['estados'];
        $regiao    = $_POST['regiao'];

        $count_regiao = count($regiao);
        $i=1;
        foreach($regiao as $linha){
            $dados .= $linha;

            if($i < $count_regiao){
                $dados .= ", ";
            }
            $i++;
        }
        $dados = str_replace(', ', "', '", "$dados");
        $dados = "'$dados'";


        $count_estados = count($estados);
        $e=1;
        foreach($estados as $linha_estados){
            $dados_estados .= $linha_estados;

            if($e < $count_estados){
                $dados_estados .= ", ";
            }
            $e++;
        }
        $dados_estados = str_replace(', ', "', '", "$dados_estados");
        $dados_estados = "'$dados_estados'";

        if($count_estados > 0 and $count_regiao > 0){
            $conteudo = $dados . ", ". $dados_estados;
        }elseif($count_regiao > 0){
            $conteudo = $dados;
        }elseif($count_estados > 0){
            $conteudo = $dados_estados;
        }

        if(strlen(trim($conteudo))>0){
            $where_estado_regiao = " and PF.contato_estado in ($conteudo) ";
        }

    }

    if($login_fabrica == 131){
        $extrato_pago = $_POST["extrato_pago"];
    }

    if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];
    if (strlen($_POST['data_inicial']) > 0) $data_inicial = $_POST['data_inicial'];

    if (strlen($_GET['data_final']) > 0) $data_final = $_GET['data_final'];
    if (strlen($_POST['data_final']) > 0) $data_final = $_POST['data_final'];

    $posto_nome   = $_POST['posto_nome'];

    if (strlen($_GET['posto_nome']) > 0) $posto_nome = $_GET['posto_nome'];
    if (strlen($_GET['razao']) > 0) $posto_nome = $_GET['razao'];

    $posto_codigo = $_POST['posto_codigo'];

    if (strlen($_GET['posto_codigo']) > 0) $posto_codigo = $_GET['posto_codigo'];
    if (strlen($_GET['cnpj']) > 0) $posto_codigo = $_GET['cnpj'];

    $filtro_requisicao = $_POST['filtro_requisicao'];
    if (strlen($_GET['filtro_requisicao']) > 0) $filtro_requisicao = $_GET['filtro_requisicao'];

    if (strlen($_GET['extrato']) > 0) $extrato = trim($_GET['extrato']);
    if (strlen($_POST['extrato']) > 0) $extrato = trim($_POST['extrato']);

    if (strlen($_GET['extrato_pago']) > 0)  $extrato_pago = $_GET['extrato_pago'];
    if (strlen($_POST['extrato_pago']) > 0) $extrato_pago = $_POST['extrato_pago'];

    $extrato_bloqueado = $_REQUEST['extrato_bloqueado'];

    // HD 49255
    if (strlen($_GET['liberado']) > 0)  $xliberado = $_GET['liberado'];
    if (strlen($_POST['liberado']) > 0) $xliberado = $_POST['liberado'];

    if (strlen($_GET['aguardando_pagamento']) > 0)     $aguardando_pagamento = $_GET['aguardando_pagamento'];
    if (strlen($_POST['aguardando_pagamento']) > 0)    $aguardando_pagamento = $_POST['aguardando_pagamento'];

    if (strlen($_GET['liberacao']) > 0) $aprovacao = $_GET['liberacao'];
    if (strlen($_POST['liberacao']) > 0) $aprovacao = $_POST['liberacao'];

    //HD 286780
    if (strlen($_POST['estado']) > 0) $estado = $_POST['estado'];
    if (strlen($_GET['estado']) > 0)  $estado = $_GET['estado'];

    if (strlen($_POST['marca']) > 0) $marca_aux = $_POST['marca'];
    if (strlen($_GET['marca']) > 0)  $marca_aux = $_GET['marca'];

    if($login_fabrica == 91){

        if (strlen($_POST['data_baixa_inicio']) > 0){
            $data_baixa_inicio = $_POST['data_baixa_inicio'];
        }
        if (strlen($_POST['data_baixa_fim']) > 0){
            $data_baixa_fim = $_POST['data_baixa_fim'];
        }
    }

    if (in_array($login_fabrica, [169,170])) {
	$pedidoSap = $_REQUEST['pedido_sap'];
	$status    = $_REQUEST['status'];
    }

    if($login_fabrica == 1){
        $valor_abaixo       = $_REQUEST['valor_abaixo'];

        $xvalor_abaixo      = str_replace(",",".",$valor_abaixo);
    }

    if($telecontrol_distrib){
        $nf_recebida_validacao = $_POST['nf_recebida'];
    }

    if(empty($data_inicial) and empty($data_final) and empty($posto_nome) and empty($posto_codigo) and empty($extrato) and empty($marca_aux) and empty($aguardando_pagamento) and empty($_REQUEST["data_ano"]) and empty($_REQUEST["data_mes"])){
            if ($telecontrol_distrib) {
              if ($nf_recebida_validacao != "sim" && (empty($data_inicial) || empty($data_final))){
                $msg_erro = "Informe algum Par‚metro para Pesquisa";
              }
            } else if($login_fabrica == 42 ){
                if(empty($mes_referencia) && empty($valor_total) && empty($valor_nf_peca) && empty($nf_autorizacao) && empty($nf_peca) && empty($bordero)){
                    $msg_erro = "Informe algum Par‚metro para Pesquisa";
                }

            }else if($login_fabrica == 91){

                if(empty($data_baixa_inicio) && empty($data_baixa_fim)){
                    $msg_erro = "Informe algum Par‚metro para Pesquisa";
                }else{
                    $msg_erro = "Informe a Data Inicial e a Data Final.";
                }
            } else if (in_array($login_fabrica, array(158))) {
              if (empty($_REQUEST["data_mes"]) || empty($_REQUEST["data_ano"]) || empty($_REQUEST["tipo_extrato"])) {
                $msg_erro = "Informe algum Par‚metro para Pesquisa";
              }
            } elseif (in_array($login_fabrica, [152,180,181,182])) {
              if (empty($_REQUEST["filtro_requisicao"])) {
                $msg_erro = "Informe algum Par‚metro para Pesquisa";
              }
            } else if (in_array($login_fabrica, [169,170])) {

		if(empty($status) && empty($pedidoSap)){
                    $msg_erro = "Informe algum Par‚metro para Pesquisa";
                } else if ((empty($data_inicial) && !empty($data_final)) || (!empty($data_inicial) && empty($data_final))) {
		    $msg_erro = "Informe a Data Inicial e a Data Final";
		}
	    } else {
                $msg_erro = "Informe algum Par‚metro para Pesquisa";
            }

    }

    if(( $login_fabrica == 86 OR $login_fabrica == 104) AND $btnacao == "filtrar"){
        if(empty($marca_aux) AND empty($posto_codigo) AND empty($extrato)){
            $msg_erro = "Informe uma Empresa";
        }
    }

    //hd-1098022 se for Fricon (52) verifica se foi passado numero da OS para filtro
    if($login_fabrica== 52 ){
        if(!empty($_POST["nroOs"])){
            $nroOs = $_POST["nroOs"];
        }
    }

    if($login_fabrica == 42){


        $bordero                    = (strlen($_POST['bordero']) > 0)                   ?   $_POST['bordero']       : "";
        //$data_bordero             = (strlen($_POST['data_bordero']) > 0)              ?   $_POST['data_bordero']  : "NULL";
        //È referente ao campo Data Envio Financeiro
        //$data_entregue_financeiro = (strlen($_POST['data_entregue_financeiro']) > 0)  ? $_POST['data_entregue_financeiro']    : "NULL";
        //$data_aprovacao               = (strlen($_POST['data_aprovacao']) > 0)            ? $_POST['data_aprovacao']              : "NULL";
        //$data_pagamento               = (strlen($_POST['data_pagamento']) > 0)            ? $_POST['data_pagamento']              : "NULL";
        $mes_referencia             = (strlen($_POST['mes_referencia']) > 0)            ? $_POST['mes_referencia']              : "";
        //referente ao campo Valor NF Servi√ßos
        $valor_total                = (strlen($_POST['valor_total']) > 0)               ? $_POST['valor_total']                 : "";
        $valor_nf_peca              = (strlen($_POST['valor_nf_peca']) > 0)             ? $_POST['valor_nf_peca']               : "";
        $nf_autorizacao             = (strlen($_POST['nf_autorizacao']) > 0)            ? $_POST['nf_autorizacao']          : "";
        $nf_peca                    = (strlen($_POST['nf_peca']) > 0)                   ? $_POST['nf_peca']                     : "";
        $posto                      = (strlen($_POST['posto']) > 0)                     ? $_POST['posto']                       : "";
    }

    if (in_array($login_fabrica, [169,170])) {
	if ((!empty($data_inicial) && empty($data_final)) || (empty($data_inicial) && !empty($data_final))) {
		$msg_erro = "Data inicial e final s„o necess·rias para fazer a pesquisa por perÌodo";
	}
    }

    if(!empty($data_inicial) && !empty($data_final)){
        if(strlen($msg_erro)==0){
            $dat = explode ("/", $data_inicial );//tira a barra
                $d = $dat[0];
                $m = $dat[1];
                $y = $dat[2];
                if(!checkdate($m,$d,$y)) $msg_erro = "Data Inv·lida";
        }
        if(strlen($msg_erro)==0){
            $dat = explode ("/", $data_final );//tira a barra
                $d = $dat[0];
                $m = $dat[1];
                $y = $dat[2];
                if(!checkdate($m,$d,$y)) $msg_erro = "Data Inv·lida";
        }
        if(strlen($msg_erro)==0){
            $d_ini = explode ("/", $data_inicial);//tira a barra
            $nova_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...


            $d_fim = explode ("/", $data_final);//tira a barra
            $nova_data_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

            if($nova_data_final < $nova_data_inicial){
                $msg_erro = "Data Inv·lida.";
            }
            //Fim ValidaÁ„o de Datas
        }
    }
}
echo "<FORM METHOD='post' id='teste' NAME='frm_extrato' ACTION=\"$PHP_SELF\">";
echo "<TABLE width='700px' align='center' border='0' cellspacing='1' class='formulario' cellpadding='3'>\n";
echo "<input type='hidden' name='btnacao' value=''>";
if(strlen($msg_erro)>0){
    echo "<TR class='msg_erro'><TD colspan='7'>$msg_erro</TD></TR>";
}

if ($liberado == true) {
  echo "<TR class='msg_sucesso'><TD colspan='7'>Extrato liberado com sucesso</TD></TR>";
}
echo "<TR class='titulo_tabela'>\n";
echo "  <TD COLSPAN='7' ALIGN='center'>";
echo "      ".traduz('Par‚metros de Pesquisa');
echo "  </TD>";
echo "</TR>",
"<tr>
    <td>&nbsp;</td>
</tr>";




echo "<TR align='left'>\n";
echo "<TD width='25'>&nbsp;</TD>";
echo "<TD ALIGN='left'>".traduz('N∫ de extrato')." </TD>";
//hd-1098022: Fricon -> se form Fricon coloca campo Pesquisa OS para filtrar resultados pelo n√∫mero da OS
if($login_fabrica==52){

        echo "<td align='left'>Pesquisa OS</td>";

}

//hd-1059101 - Makita
if($login_fabrica == 42){
    echo "<td>Filtrar por </td>";

}
echo "<td>".traduz('Data Inicial')." </td>";
echo "<td >  ".traduz('Data Final')." </td>";

echo "</tr>";
echo "<tr align='left'><TD width='25'>&nbsp;</TD>"; //inicio dos campos
echo "<td><input type='text' id='extrato' name='extrato' size='12' value='$extrato' class='frm'>&nbsp;";
echo "  </TD>\n";
if($login_fabrica ==52){

    echo "<td><input type='text' name='nroOs' size='12' value='$nroOs' class='frm'>&nbsp;";
}
if($login_fabrica == 42){

    echo "<td>  <select class='frm' size='1' name='tipoData'>";
    echo    "<option ";
                 if (empty($tipoData)) echo "selected";
    echo    "></option>";

    echo    "<option value='dataBordero'";
                 if ($tipoData=="dataBordero") echo "selected";
    echo            ">Data Border√¥</option>";

    echo    "<option value='dataEntregueFinanceiro'";
                 if ($tipoData=="dataEntregueFinanceiro") echo "selected";
    echo                    ">Data Entrega Financeiro</option>";

    echo    "<option value='dataAprovacao'";
                if ($tipoData=="dataAprovacao") echo "selected";
    echo                    ">Data AprovaÁ„o</option>";

    echo    "<option value='dataPagamento'";
                if ($tipoData=="dataPagamento") echo "selected";
    echo                    ">Data Pagamento</option>";

    echo    "</td>";

}
echo "  <TD ALIGN='left' width='50'>";

echo "  <input type='text' size='12' maxlength='10' name='data_inicial' id='data_inicial' rel='data' value='$data_inicial' class='frm date' />\n";
echo "  </TD>\n";

echo "  <TD width='100' ALIGN='left'>";

echo "  <INPUT type='text' size='12' maxlength='10'  name='data_final' id='data_final' rel='data' value='$data_final' class='frm date' />\n";
echo "</TD>";
if($login_fabrica == 91){
    echo "</tr><tr>";
    echo "<TD width='25'>&nbsp;</TD>";
    echo "<td width='100' align='left'>  Data da Baixa InÌcio </td>";
    echo "<td width='100' align='left'>  Data da Baixa Fim </td>";
}
if($login_fabrica == 91){
    echo "</tr><tr>";
    echo "<TD width='25'>&nbsp;</TD>";
    echo "  <td  width='100' align='left'><input type='text' size='10' maxlength='10'  name='data_baixa_inicio' id='data_baixa_inicio' rel='data' value='$data_baixa_inicio' class='frm date' /></td>";
    echo "  <td  width='100' align='left'><input type='text' size='10' maxlength='10'  name='data_baixa_fim' id='data_baixa_fim' rel='data' value='$data_baixa_fim' class='frm date' /></td>";

}

if($login_fabrica == 6){
echo "  <TD width='20%' nowrap>";

    echo " Liberado <input type='radio' name='liberado' value='liberado'>&nbsp;&nbsp;&nbsp;N„o Liberado <input type='radio' name='liberado' value='nao_liberado' />";
    echo "  </TD>";
}
echo "</TR>\n";


#HD 22758
if ($login_fabrica == 24 || $login_fabrica == 142) {

    echo "<tr>\n";
        echo "<td>&nbsp;</td>";
        echo "<td align='left'>";
            //HD 286780
            echo 'Estado <br />';
            echo '<select name="estado" id="estado" style="width:120px; font-size:9px" class="frm">';
                echo '<option value=""   ' . (strlen($estado) == 0   ? " selected " : '') . ' >TODOS OS ESTADOS</option>';
                echo '<option value="AC" ' . ($estado == "AC" ? " selected " : '') . '>AC - Acre</option>';
                echo '<option value="AL" ' . ($estado == "AL" ? " selected " : '') . '>AL - Alagoas</option>';
                echo '<option value="AM" ' . ($estado == "AM" ? " selected " : '') . '>AM - Amazonas</option>';
                echo '<option value="AP" ' . ($estado == "AP" ? " selected " : '') . '>AP - Amap·</option>';
                echo '<option value="BA" ' . ($estado == "BA" ? " selected " : '') . '>BA - Bahia</option>';
                echo '<option value="CE" ' . ($estado == "CE" ? " selected " : '') . '>CE - Cear·</option>';
                echo '<option value="DF" ' . ($estado == "DF" ? " selected " : '') . '>DF - Distrito Federal</option>';
                echo '<option value="ES" ' . ($estado == "ES" ? " selected " : '') . '>ES - EspÌrito Santo</option>';
                echo '<option value="GO" ' . ($estado == "GO" ? " selected " : '') . '>GO - Goi·s</option>';
                echo '<option value="MA" ' . ($estado == "MA" ? " selected " : '') . '>MA - Maranh„o</option>';
                echo '<option value="MG" ' . ($estado == "MG" ? " selected " : '') . '>MG - Minas Gerais</option>';
                echo '<option value="MS" ' . ($estado == "MS" ? " selected " : '') . '>MS - Mato Grosso do Sul</option>';
                echo '<option value="MT" ' . ($estado == "MT" ? " selected " : '') . '>MT - Mato Grosso</option>';
                echo '<option value="PA" ' . ($estado == "PA" ? " selected " : '') . '>PA - Par·</option>';
                echo '<option value="PB" ' . ($estado == "PB" ? " selected " : '') . '>PB - ParaÌba</option>';
                echo '<option value="PE" ' . ($estado == "PE" ? " selected " : '') . '>PE - Pernambuco</option>';
                echo '<option value="PI" ' . ($estado == "PI" ? " selected " : '') . '>PI - PiauÌ</option>';
                echo '<option value="PR" ' . ($estado == "PR" ? " selected " : '') . '>PR - Paran·</option>';
                echo '<option value="RJ" ' . ($estado == "RJ" ? " selected " : '') . '>RJ - Rio de Janeiro</option>';
                echo '<option value="RN" ' . ($estado == "RN" ? " selected " : '') . '>RN - Rio Grande do Norte</option>';
                echo '<option value="RO" ' . ($estado == "RO" ? " selected " : '') . '>RO - Rond√¥nia</option>';
                echo '<option value="RR" ' . ($estado == "RR" ? " selected " : '') . '>RR - Roraima</option>';
                echo '<option value="RS" ' . ($estado == "RS" ? " selected " : '') . '>RS - Rio Grande do Sul</option>';
                echo '<option value="SC" ' . ($estado == "SC" ? " selected " : '') . '>SC - Santa Catarina</option>';
                echo '<option value="SE" ' . ($estado == "SE" ? " selected " : '') . '>SE - Sergipe</option>';
                echo '<option value="SP" ' . ($estado == "SP" ? " selected " : '') . '>SP - S„o Paulo</option>';
                echo '<option value="TO" ' . ($estado == "TO" ? " selected " : '') . '>TO - Tocantins</option>';
            echo '</select>';

        echo "</td>";
        echo "<td colspan='2'></td>";
    echo "</tr>\n";

    if ($login_fabrica == 24) {
    echo "<tr>\n";
        echo "<td></td>";
        echo "<td colspan= '2' align='left'>";

            echo "<table align='left'>";
                echo "<tr>\n";
                    echo "<td><input type='checkbox' name='extrato_bloqueado' value='t' ".(($extrato_bloqueado=='t')?"checked":"")."> Extratos bloqueados <span style='color:#515151;font-size:10px' title='obrigatÛrio digitar a data inicial e a data final ou posto' /> (PerÌodo/Posto obrigatÛrio) </span></TD>\n";
                echo "</tr>\n";
                echo "<tr>\n";
                    echo "<td><input type='checkbox' name='extrato_pago' value='t' ".(($extrato_pago=='t')?"checked":"")."> Extratos pagos <span style='color:#515151;font-size:10px' title='√â obrigatÛrio digitar a data inicial e a data final' /> (PerÌodo obrigatÛrio) </span></TD>\n";
                echo "</tr>\n";
                echo "<tr>\n";
                    echo "<TD><input type='checkbox' name='aguardando_pagamento' value='t' ".(($aguardando_pagamento=='t')?"checked":"")." /> Extratos aguardando pagamento <span style='color:#515151;font-size:10px'> (PerÌodo opcional) </span></TD>\n";
                echo "</tr>\n";
            echo "</table>";

        echo "</td>\n";
        echo "<td></td>";
    echo "</tr>\n";
    }

}

/**
    hd-1059101  - Makita, campos
*/
if($login_fabrica == 42){
    //labels
    echo "<TR align='left'>";
    echo "<TD width='50'>&nbsp;</TD>";
    echo "<TD>N∫ NF M.O. </td>",
         "<td > Valor NF M.O. </td>",
         "<td >N∫ NF Pe√ßas </td>",
         "<td > Valor NF Pe√ßas </td>";
    echo "</tr>";
    //campos
    echo "<TR align='left'>";
    echo "<TD width='50'>&nbsp;</TD>";
    echo "<TD><input type='text' name='nf_autorizacao' size='18' value='$nf_autorizacao' class='frm' ></td>",
         '<td>',
             "<input type='text' name='valor_total' size='18' value='$valor_total' class='frm' >",
        '</td>',
         "<td ><input type='text' name='nf_peca' size='18' value='$nf_peca' class='frm' ></td>",
         "<td ><input type='text' name='valor_nf_peca' size='18' value='$valor_nf_peca' class='frm' ></td>";
    echo "</tr>";
    //labels
    echo "<tr align='left'>";
    echo "<td ></td>",
        "<td >Border√¥ </td>",
         "<td >M√™s Refer√™ncia  </td>",

        "</tr>";
    //campos
    echo "<TR align='left'>";
    echo "<TD width='50'>&nbsp;</TD>";
    echo "<TD><input type='text' name='bordero' size='18' value='$bordero' class='frm' ></td>",
         '<td ><select name="mes_referencia" id="mes_referencia" style="width:120px; font-size:10px" class="frm">',
                    '<option value="" '  . ($mes_referencia  == ""  ? " selected " : '') . '></option>',
                     '<option value="1" '  . ($mes_referencia  == "1"  ? " selected " : '') . '>Janeiro</option>',
                     '<option value="2" '  . ($mes_referencia  == "2"  ? " selected " : '') . '>Fevereiro</option>',
                     '<option value="3" '  . ($mes_referencia  == "3"  ? " selected " : '') . '>Mar√ßo</option>',
                     '<option value="4" '  . ($mes_referencia  == "4"  ? " selected " : '') . '>Abril</option>',
                     '<option value="5" '  . ($mes_referencia  == "5"  ? " selected " : '') . '>Maio</option>',
                     '<option value="6" '  . ($mes_referencia  == "6"  ? " selected " : '') . '>Junho</option>',
                     '<option value="7" '  . ($mes_referencia  == "7"  ? " selected " : '') . '>Julho</option>',
                     '<option value="8" '  . ($mes_referencia  == "8"  ? " selected " : '') . '>Agosto</option>',
                     '<option value="9" '  . ($mes_referencia  == "9"  ? " selected " : '') . '>Setembro</option>',
                     '<option value="10" ' . ($mes_referencia  == "10" ? " selected " : '') . '>Outubro</option>',
                     '<option value="11" ' . ($mes_referencia  == "11" ? " selected " : '') . '>Novembro</option>',
                     '<option value="12" ' . ($mes_referencia  == "12" ? " selected " : '') . '>Dezembro</option>',
                '</select>',
            "</td>",

    "</tr>";
}
echo "<tr>";

    if(in_array($login_fabrica, array(1,35))){
	    foreach($estadosBrasil as $linha => $indice){
		    $selected = (in_array($linha,$estados)) ? "SELECTED" : "";
	            $estados_brasil .="<option value='$linha' $selected>$indice</option>";
        }

        $sql_regiao = "select descricao, estados_regiao from tbl_regiao where fabrica = 1";
        $res_regiao = pg_query($con, $sql_regiao);
        for($i=0; $i<pg_num_rows($res_regiao); $i++){
            $descricao  = pg_fetch_result($res_regiao, $i, 'descricao');
            $estados    = pg_fetch_result($res_regiao, $i, 'estados_regiao');
	    $selected = (in_array($estados,$regiao)) ? "SELECTED" : "";

            $regioes .= "<option value='$estados' $selected>$descricao</option>";
        }
        echo "<TR align='left'>";
        echo "<TD width='25'>&nbsp;</TD>";
        echo "<TD width='15'>";
        echo "Regi„o</td>";
        echo "<td>Estado</td>";
        echo "<tr><TD width='25'>&nbsp;</TD>
              <td width='15' align='left'> ";
        echo "<select id='ms' class='frm' name='regiao[]'  multiple='multiple'>
                $regioes
            </select>";
        echo "</td>";
        echo "<td align='left'>";
            echo "<select  name='estados[]' id='estados' class='frm' multiple='multiple' >";
                echo $estados_brasil;
            echo "</select>";
        echo "</td>";
        echo "<TR>\n";
    }


echo "<TR align='left'>";
echo "<TD width='55'>&nbsp;</TD>";
echo "  <TD width='15'>";
echo traduz('CNPJ')."</td>",
"<td colspan='2'>".traduz('Raz„o Social')."</tr><tr align='left'><TD width='25'>&nbsp;</TD><td>";
echo "<input type='text' name='posto_codigo' id='posto_codigo' size='18' value='$posto_codigo' class='frm' onkeypress='javascript:somente_numero(this);' maxlength='18'>&nbsp;
<img src='imagens/lupa.png' border='0' align='absmiddle' style='cursor: pointer;' onclick=\"javascript: pesquisaPosto (document.frm_extrato.posto_codigo, 'cnpj');\" /></td><td colspan='2'>";
echo "<input type='text' name='posto_nome' id='posto_nome' size='30' value='$posto_nome' class='frm'>&nbsp;
<img src='imagens/lupa.png' border='0' align='absmiddle' onclick=\"javascript: pesquisaPosto (document.frm_extrato.posto_nome, 'nome');\" style='cursor: pointer;' />";
echo "  </TD>";
echo "<TR>\n";

if (in_array($login_fabrica, [169,170])) {


$statusFiltro = [
            "Pagamento Efetivado" => "Pagamento Efetivado",
            "Pagamento Bloqueado" => "Pagamento Bloqueado",
            "Nota Aprovada" => "Nota Aprovada",
            "Nota Emitida" => "Nota Emitida",
            "Liberado" => "Liberado",
        ];

$select_status .= "<option value=''>Selecione ...</option>";
foreach ($statusFiltro as $key => $value) {
    $selected = "";
    if ($_POST["status"] == $key) {
        $selected = "selected";
    }
    $select_status .= "<option {$selected} value='".$key."'>".$value."</option>";
}
    echo "
        <tr align='left'>
            <td width='25'>&nbsp;</td>
            <td width='1'>Status <br>
            <td width='1'>Pedido SAP</td>
            </td>
         <tr>
        <tr align='left'>
            <td width='25'>&nbsp;</td>
            <td width='1'>
               <select  name='status' id='status' class='frm'>
                {$select_status}
               </select>
            </td>
            <td width='1'><input type='text' name='pedido_sap' id='pedido_sap' class='frm' maxlength='12' size='12' value='{$pedidoSap}' /></td>
         <tr>\n
    ";
}

if($login_fabrica == 151) {
  echo "<TR align='left'>";
  echo "<TD width='55'>&nbsp;</TD>";
  echo "<TD width='55'>Centro DistribuiÁ„o</TD>";
  echo "</TR>";
  echo "<TR align='left'>";
  echo "<td bgcolor='#D9E2EF' style='font-size:10px' align='left'  nowrap>&nbsp;</td>";
  echo "<TD width='55'>";
  ?>
      <select name="centro_distribuicao" id="centro_distribuicao" size="1" class="frm" style="width:200px;">
          <option value="mk_vazio" name="mk_vazio" <?php echo ($centro_distribuicao == "mk_vazio") ? "SELECTED" : ""; ?> >ESCOLHA</option>
          <option value="mk_nordeste" name="mk_nordeste" <?php echo ($centro_distribuicao == "mk_nordeste") ? "SELECTED" : ""; ?> >MK Nordeste</option>
          <option value="mk_sul" name="mk_sul" <?php echo ($centro_distribuicao == "mk_sul") ? "SELECTED" : ""; ?> >MK Sul</option>    
      </select>
  <?php
  echo "</TD>";
  echo "<TR>\n";
}

if($login_fabrica == 131){
  echo "<tr> ";
      echo "<TD width='55'>&nbsp;</TD>";
      echo "<TD> <input type='radio' name='extrato_pago' value='nao'> Extrato n„o Pagos</TD>";
      echo "<TD> <input type='radio' name='extrato_pago' value='sim'> Extrato  Pagos</TD>";
  echo "</td>";
}

if($login_fabrica == 148){
  echo "<tr align='left'>";
      echo "<TD width='25'>&nbsp;</TD>";
      echo "<td> Tipo de Atendimento <br>
        <select name='tipo_atendimento' class='frm'>
          <option value=''>Tipo de Atendimento</option>"; 

          $sqlTA = "SELECT tipo_atendimento, descricao FROM tbl_tipo_atendimento WHERE ativo = true and fabrica = $login_fabrica and tipo_atendimento in (220,218,217) ORDER BY descricao ";
          $resTA = pg_query($con, $sqlTA);
          for($t= 0; $t<pg_num_rows($resTA); $t++){
              $tipo_atendimento_bd = pg_fetch_result($resTA, $t, 'tipo_atendimento');
              $descricao        = pg_fetch_result($resTA, $t, 'descricao');

              if($tipo_atendimento == $tipo_atendimento_bd){
                  $selected = " selected ";
              }else{
                  $selected = "  ";
              }

              echo "<option value='$tipo_atendimento_bd' $selected >$descricao</option>";
          }
          ?>
<?php echo "</select>
      </td>";
  echo "</tr>";
}


    if (in_array($login_fabrica, array(158))) {
       $tipo_extrato_imb = $_POST["tipo_extrato_imb"];
       $estado_posto_autorizado = $_POST["estado_posto_autorizado"];
       $array_tipo = array("Fora de Garantia" => "Fora de Garantia", "Garantia" => "Garantia");

    echo "
        <tr align='left'>
          <td width='15'></td>
          <td width='15'>Tipo do Extrato</td>
          <td width='15'>Estado do Posto Autorizado</td>";
    echo ($login_fabrica == 158) ? "<td width='15'>Unidade de NegÛcio</td>" : "<td width='15'></td>";

    echo "<tr>

        <tr align='left'>
          <td width='15'></td>
          <td width='15'>
            <select name='tipo_extrato_imb' class='frm'>
              <option value=''> - Selecione -</option>";
                foreach ($array_tipo as $k => $v) {
                    $selected = ($tipo_extrato_imb == $k) ? 'selected="selected"' : '';
                    echo "<option value='{$v}' {$selected}>{$v}</option>";
               }
        echo "</select>
          </td>
          <td width='15'>
            <select name='estado_posto_autorizado' class='frm'>
                <option value=''> - Selecione -</option>";
                foreach ($array_estados() as $sigla => $estados) {
                    $ufSelected = ($estado_posto_autorizado == $sigla) ? 'selected="selected"' : '';
                    echo "<option value='{$sigla}' {$ufSelected}>{$estados}</option>";
               }
      echo" </select>
          </td>";

    if ($login_fabrica == 158) {
    ?>
        <td>
            <select name="unidade_negocio[]" multiple="multiple"  class="select2" id="unidade_negocio">
                <?php
                    $oDistribuidorSLA = new DistribuidorSLA();
                    $oDistribuidorSLA->setFabrica($login_fabrica);
                    $distribuidores_disponiveis = $oDistribuidorSLA->SelectUnidadeNegocioNotIn();

                    $unidadesMinasGerais = \Posvenda\Regras::getUnidades("unidadesMinasGerais", $login_fabrica);
                    
                    foreach ($distribuidores_disponiveis as $unidadeNegocio) {
                        if (in_array($unidadeNegocio["unidade_negocio"], $unidadesMinasGerais)) {
                            unset($unidadeNegocio["unidade_negocio"]);
                            continue;
                        }
                        $unidade_negocio_agrupado[$unidadeNegocio["unidade_negocio"]] = $unidadeNegocio["cidade"];
                    }

                    foreach ($unidade_negocio_agrupado as $unidade => $descricaoUnidade) {
                        $selected = (in_array($unidade, $unidade_negocio)) ? 'selected' : '';
                        echo '<option '.$selected.' value="'.$unidade.'">'.$descricaoUnidade.'</option>';
                    }
                ?>
            </select>
        </td>
    <?php
    }else{
        echo "<td width='15'></td>";
    }

      echo "<tr>";
    }
    if(in_array($login_fabrica, array(152,180,181,182))){
?>
    <tr align="left">
        <td></td>
        <td><?=traduz('RequisiÁ„o')?></td>
    </tr>
    <tr align="left">
      <td></td>
      <td colspan="3"> 
        <input type="text" value='<?=$filtro_requisicao?>' id="filtro_requisicao" name="filtro_requisicao" size="18" class="frm ac_input">
      </td>
    </tr>
    <?php if ($login_fabrica == 152) { ?>
    	<tr align="left">
            <td></td>
            <td>Estado/Regi„o</td>
    	</tr>
   	<tr align="left">
            <td></td>
            <td colspan="3">
                <select name="regiao_estado" class="frm" id='regiao_estado' >
		    <option value="" ></option>
                    <?php
                    $array_regioes = array(
                    	"BA,SE,AL,PE,PB,RN,CE,PI,MA,SP",
                    	"MG,DF,GO,MT,RO,AC,AM,RR,PA,AP,TO",
                    	"MS,PR,SC,RS,RJ,ES"
                    );
                    if (count($array_regioes) > 0) { ?>
                	<optgroup label="Regioes" >
                        <?php foreach ($array_regioes as $regiao) {
                        	$selected = ($regiao_estado == $regiao) ? "selected" : "";
                                echo "<option value='{$regiao}'  {$selected} >{$regiao}</option>";
                        } ?>
                        </optgroup>
                        <optgroup label="Estados" >
                    <?php }

                    foreach ($array_estados() as $sigla => $estado_nome) {
                 	$selected = ($regiao_estado == $regiao) ? "selected" : "";

                        echo "<option value='{$sigla}' {$selected} >{$estado_nome}</option>";
                    }

                    if (count($array_regioes) > 0) { ?>
                 	</optgroup>
                    <?php } ?>
            	</select>
            </td>
    	</tr>
    <?php }
}

if ($login_fabrica == 1) {
    $check_online = ($_POST["tipo_envio_nf"] == "online") ? "checked" : "";
    $check_correios = ($_POST["tipo_envio_nf"] == "correios") ? "checked" : "";
    echo "<tr>
            <td>&nbsp;</td>
            <td align='left'>
                <label for='extratos_eletronicos'>Envio NF Online</a>
                <input type='radio' name='tipo_envio_nf' id='extratos_eletronicos' value='online' $check_online />
            </td>
            <td align='left'>
                Valor Abaixo
            </td>
          </tr>";
    echo "<tr>
            <td>&nbsp;</td>
            <td align='left'>
              <label for='extratos_pendentes'>Envio NF Correios</a>
              <input type='radio' name='tipo_envio_nf' id='extratos_pendentes' value='correios' $check_correios />
            </td>
            <td align='left'>
              <input type='text' name='valor_abaixo' id='valor_abaixo' size='18' value='$valor_abaixo' class='frm valor' />
            </td>
          </tr>";
}

if($login_fabrica == 15) { ?>
    <tr>
        <td colspan='4' align='left'>
            <input type='checkbox' value='t' name='liberacao' <?PHP if  ($liberacao == 't') {?> checked <?PHP }?>>
                Mostrar somente extratos para liberaÁ„o.
        </td>
    </tr>
<?php }

if($login_fabrica == 20){
    // MLG 2009-08-04 HD 136625
    $sql = "SELECT pais,nome FROM tbl_pais where america_latina is TRUE;";    $res = pg_query($con,$sql);
    $p_tot = pg_num_rows($res);
    for ($i = 0; $i<$p_tot; $i++) {
        list($p_code,$p_nome) = pg_fetch_row($res, $i);
        $sel_paises .= "\t\t\t\t<option value='$p_code'";
        $sel_paises .= ($pais==$p_code)?" selected":"";
        $sel_paises .= ">$p_nome</option>\n";
    }
?>
    <tr bgcolor="#D9E2EF" >
        <td>&nbsp;</td>
        <td align='left'>PaÌs<br />
            <select name='pais' size='1' class='frm'>
            <option value="BR">Brasil</option>
            <?echo $sel_paises;?>
            </select>
        </td>

	<?php
	// hd-2223746
	if($login_fabrica == 20) { ?>
	<td align='left'>Status de Extratos<br />
		<select name='filtro_tipo_extrato' size='1' class='frm'>
			<option value='TODOS'>Todos</option>
			<option value='APROVADOS'>Aprovados</option>
			<option value='NAO_APROVADOS'>N&atilde;o Aprovados</option>
		</select>
	</td>
	<?php } ?>

    </tr>
<?}

if(in_array($login_fabrica,array(35,86,104,146))){

  if ($login_fabrica != 35) {
    if($login_fabrica == 104){
        $sqlM = "SELECT tbl_marca.marca,tbl_marca.nome FROM tbl_marca WHERE tbl_marca.fabrica = $login_fabrica AND tbl_marca.marca in( 184,189) ORDER BY tbl_marca.nome";
    }else{
        $sqlM = "SELECT tbl_marca.marca,tbl_marca.nome FROM tbl_marca WHERE tbl_marca.fabrica = $login_fabrica ORDER BY tbl_marca.nome";
    }

    $resM = pg_exec($con,$sqlM);

  }

    if(pg_num_rows($resM) > 0 || $login_fabrica == 35){
      if ($login_fabrica != 35) {
        echo "<tr>";
        echo "<TD width='50'>&nbsp;</TD>";
        echo "<td align='left'>";
        if($login_fabrica == 104){
            echo "Empresa <br/><select name='marca' class='frm'>";
            echo "<option value=''>Todas as Empresas </option>";
        }else{
            echo "Marca <br/><select name='marca' class='frm'>";
            echo "<option value=''>Todas Marcas</option>";
        }
        for($i = 0; $i < pg_num_rows($resM); $i++){
            $marca = pg_result($resM,$i,'marca');
            $nome_marca = pg_result($resM,$i,'nome');
            $selected = ($nome_marca == $marca_aux) ? "SELECTED" : "";

            echo "<option value='".$nome_marca."' $selected>";
            if($nome_marca == "VONDER"){
                echo "OVD";
            }else{
                echo $nome_marca;
            }

            echo "</option>";
        }
        echo "</select>";
        echo "</td>";
        echo "</tr>";

      }

    	if(in_array($login_fabrica, array(35,86))){

        $labelPendente = ($login_fabrica == 35) ? "Extratos Pendentes" : "Extratos em Aberto";
    ?>

          <tr style="text-align:left;">
        		<td width='50' >&nbsp;</td>
            <td width='200'> Todos os Extratos<input <?=($_REQUEST["extrato_pago"]=="todos" || (empty($_REQUEST["extrato_pago"]))) ? "checked" : ""?> type="radio" name="extrato_pago" value="todos"/> </td>
            <td width='70'><?= $labelPendente ?><input <?=(($_POST["extrato_pago"]=="f") ||( $_GET["extrato_pago"]=="f")) ? "checked" : ""?> type="radio" name="extrato_pago" value="f" /> </td>

        		<td width='50'> Extratos Pagos<input <?=($_POST["extrato_pago"]=="t" || $_GET["extrato_pago"]=="t") ? "checked" : ""?> type="radio" name="extrato_pago" value="t"/> </td>

    	    </tr>

        <?
    	}
    }
}

if($login_fabrica == 1){

    $checked_inibido = (isset($_POST["extratos_inibidos"])) ? "checked" : "";

    echo "<td width='50'> &nbsp; </td>";
    echo "<td colspan='4' align='left'>";
        echo "<input type='checkbox' name='extratos_inibidos' value='sim' $checked_inibido /> Extratos Inibidos";
    echo "<td>";
}

if($usaNotaFiscalServico && !in_array($login_fabrica, [152,180,181,182])){ ?>
  <tr>
    <td width='50'>&nbsp;</td>
    <td align='left'>
      <input type="radio" name="nf_recebida" value="envio_nf_pendente" <?= ($_POST['nf_recebida'] == 'envio_nf_pendente') ? "checked" : "" ?> />Envio de NFe pendente
    </td>
    <td align='left'>
      <input type="radio" name="nf_recebida" value="nf_enviada" <?= ($_POST['nf_recebida'] == 'nf_enviada') ? "checked" : "" ?> />NFe Enviada
    </td>
    <td align='left'>
      <input type="radio" name="nf_recebida" value="extratos_pagos" <?= ($_POST['nf_recebida'] == 'extratos_pagos') ? "checked" : "" ?> />Extrato pago
    </td>
  </tr>
  <tr>
    <td width='50'>&nbsp;</td>
    <td align='left'>
      <input type="radio" name="nf_recebida" value="nf_nao_visualizadas" <?= ($_POST['nf_recebida'] == 'nf_nao_visualizadas') ? "checked" : "" ?> />NFe n„o Aprovadas
    </td>

    <td align='left'>
      <input type="radio" name="nf_recebida" value="nf_visualizadas" <?= ($_POST['nf_recebida'] == 'nf_visualizadas') ? "checked" : "" ?> />NFe Aprovadas
    </td>
    <?php if($login_fabrica == 91) :?>
        <td align='left'>
            <input type="radio" name="nf_recebida" value="nao_liberado" <?= ($_POST['nf_recebida'] == 'nao_liberado') ? "checked" : "" ?> />Extrato n„o liberado
        </td>
    <?php endif; ?>
  </tr>
<?php }

if($telecontrol_distrib){ ?>
  <tr>
    <td width='50'>&nbsp;</td>
    <td align='left'><input type="radio" name="nf_recebida" value="f" <?php if($_POST['nf_recebida'] == 'f'){ echo " checked "; } ?> >Envio de NFe pendente</td>
    <td align='left'><input type="radio" name="nf_recebida" value="t" <?php if($_POST['nf_recebida'] == 't'){ echo " checked "; } ?> >Nfe Enviada</td>
    <td align='left'><input type="radio" name="nf_recebida" value="data_pagamento"  <?php if($_POST['nf_recebida'] == 'data_pagamento'){ echo " checked "; } ?> >Extrato pago</td>
  </tr>
  <tr>
    <td width='50'>&nbsp;</td>
    <td align='left'><input type="radio" name="nf_recebida" value="sim" <?php if($_POST['nf_recebida'] == 'sim'){ echo " checked "; } ?> >Apenas NFe n„o visualizadas</td>

    <td align='left'><input type="radio" name="nf_recebida" value="nao" <?php if($_POST['nf_recebida'] == 'nao'){ echo " checked "; } ?> >Apenas NFe visualizadas</td>
  </tr>
 
<?php }

 if(in_array($login_fabrica, array(152,180,181,182))){ ?>

  <tr>
    <td></td>
    <td align="left">
      <input type="radio" name="filtro_status" class="filtro_status" value="pendente_aprovacao" <?php if($filtro_status == 'pendente_aprovacao' ){ echo " checked "; } ?>> <?=traduz('Pendente de AprovaÁ„o')?>
    </td>
    <td align="left" colspan="2">
      <input type="radio" name="filtro_status"  class="filtro_status" value="aguardando_nf_posto" <?php if($filtro_status == 'aguardando_nf_posto' ){ echo " checked "; } ?>> <?=traduz('Aguardando Nota Fiscal do Posto')?>
    </td>
  </tr>
  <tr>
    <td></td>
    <td align="left">
      <input type="radio" name="filtro_status"  class="filtro_status" value="aguardando_aprovacao_nf" <?php if($filtro_status == 'aguardando_aprovacao_nf' ){ echo " checked "; } ?>> <?=traduz('Aguardando AprovaÁ„o de Nota Fiscal')?>
    </td>
    <td align="left">
      <input type="radio" name="filtro_status"  class="filtro_status" value="aguardando_encerramento" <?php if($filtro_status == 'aguardando_encerramento' ){ echo " checked "; } ?>> <?=traduz('Aguardando Encerramento')?>
    </td>
  </tr>
  <tr>
    <td></td>
    <td align="left">
      <input type="radio" name="filtro_status"  class="filtro_status" value="encerramento" <?php if($filtro_status == 'encerramento' ){ echo " checked "; } ?>> <?=traduz('Encerramento')?>
    </td>
  </tr>    

  <?php } 
echo "<tr><td colspan='4' style='padding: 20px; '><input type='button' style='width:95px; cursor:pointer;' value='Filtrar' onclick=\"javascript: document.frm_extrato.btnacao.value='filtrar' ; document.frm_extrato.submit() ;\" ALT='Filtrar extratos' border='0' ></td></tr>";

echo "</form>";
echo "</TABLE>\n";

if(in_array($login_fabrica, array(50, 160)) or $replica_einhell){

  if(isset($_GET["dashboard"]) && isset($_GET["mes"])){

    if($_GET["dashboard"] == "sim"){

      $mes_get = $_GET["mes"];

      switch($mes_get){
        case "Janeiro": $mes_get   = "01"; break;
        case "Fevereiro": $mes_get = "02"; break;
        case "Mar√ßo": $mes_get     = "03"; break;
        case "Abril": $mes_get     = "04"; break;
        case "Maio": $mes_get      = "05"; break;
        case "Junho": $mes_get     = "06"; break;
        case "Julho": $mes_get     = "07"; break;
        case "Agosto": $mes_get    = "08"; break;
        case "Setembro": $mes_get  = "09"; break;
        case "Outubro": $mes_get   = "10"; break;
        case "Novembro": $mes_get  = "11"; break;
        case "Dezembro": $mes_get  = "12"; break;
      }

      $mes_atual = date("n");

      $ano_atual = date("Y", strtotime(date("Y-m-d")." -1 month"));

      $_POST["data_inicial"] = "01/".$mes_get."/".$ano_atual;
      $_POST["data_final"] =  date("t/m/Y", strtotime("$ano_atual-$mes_get-01"));
      $btnacao = "Filtrar";

    }

  }

}

// INICIO DA SQL
if ($btnacao AND strlen($msg_erro) == 0) {


  if($login_fabrica == 148){
      $tipo_atendimento = $_POST["tipo_atendimento"];
      if($tipo_atendimento){
          $condTipo_atendimento = " and tbl_extrato_agrupado.codigo = '$tipo_atendimento' ";
      }
  }

    if($login_fabrica == 1 && isset($_POST["extratos_inibidos"])){

        $cond_inibido = " AND EE.baixado notnull ";

    }

    $data_inicial = $_POST['data_inicial'];
    if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];

    $data_final   = $_POST['data_final'];
    if (strlen($_GET['data_final']) > 0) $data_final = $_GET['data_final'];

    if($login_fabrica == 91){

        $data_baixa_inicio = "";
        $data_baixa_fim = "";

        if (strlen($_POST['data_baixa_inicio']) > 0){
            $data_baixa_inicio = $_POST['data_baixa_inicio'];
            $x_data_baixa_inicio     = substr ($data_baixa_inicio,6,4) . "-" . substr ($data_baixa_inicio,3,2) . "-" . substr ($data_baixa_inicio,0,2);
        }

        if (strlen($_POST['data_baixa_fim']) > 0){
            $data_baixa_fim = $_POST['data_baixa_fim'];
            $x_data_baixa_fim = substr ($data_baixa_fim,6,4) . "-" . substr ($data_baixa_fim,3,2) . "-" . substr ($data_baixa_fim,0,2);
        }



        if(strlen($x_data_baixa_inicio) == 0 && strlen($x_data_baixa_fim) == 0){
            $sqlJoin_data_baixa = "LEFT JOIN tbl_extrato_pagamento EP ON EP.extrato = EX.extrato";
        }else{
            $x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);
            $sqlJoin_data_baixa = " JOIN tbl_extrato_pagamento EP ON EP.extrato = EX.extrato
                                        AND EP.data_pagamento BETWEEN '{$x_data_baixa_inicio} 00:00:00' AND '{$x_data_baixa_fim} 23:59:59' ";
        }

    }


    if($login_fabrica == 1){
        if(strlen($xvalor_abaixo) > 0){
            $sqlValorBaixa = " AND EX.total <= $xvalor_abaixo";
        }
    }

    $posto_codigo = $_POST['posto_codigo'];
    if (strlen($_GET['posto_codigo']) > 0) $posto_codigo = $_GET['posto_codigo'];
    if (strlen($_GET['cnpj']) > 0) $posto_codigo = $_GET['cnpj'];

    $data_inicial = str_replace (" " , "" , $data_inicial);
    $data_inicial = str_replace ("-" , "" , $data_inicial);
    $data_inicial = str_replace ("/" , "" , $data_inicial);
    $data_inicial = str_replace ("." , "" , $data_inicial);

    $data_final = str_replace (" " , "" , $data_final);
    $data_final = str_replace ("-" , "" , $data_final);
    $data_final = str_replace ("/" , "" , $data_final);
    $data_final = str_replace ("." , "" , $data_final);

    if (strlen ($data_inicial) == 6) $data_inicial = substr ($data_inicial,0,4) . "20" . substr ($data_inicial,4,2);
    if (strlen ($data_final)   == 6) $data_final   = substr ($data_final  ,0,4) . "20" . substr ($data_final  ,4,2);

    $data_inicial = str_replace (" " , "" , $data_inicial);
    $data_inicial = str_replace ("-" , "" , $data_inicial);
    $data_inicial = str_replace ("/" , "" , $data_inicial);
    $data_inicial = str_replace ("." , "" , $data_inicial);

    $data_final = str_replace (" " , "" , $data_final);
    $data_final = str_replace ("-" , "" , $data_final);
    $data_final = str_replace ("/" , "" , $data_final);
    $data_final = str_replace ("." , "" , $data_final);

    if (strlen ($data_inicial) == 6) $data_inicial = substr ($data_inicial,0,4) . "20" . substr ($data_inicial,4,2);
    if (strlen ($data_final)   == 6) $data_final   = substr ($data_final  ,0,4) . "20" . substr ($data_final  ,4,2);

    if (strlen ($data_inicial) > 0) $data_inicial = substr ($data_inicial,0,2) . "/" . substr ($data_inicial,2,2) . "/" . substr ($data_inicial,4,4);
    if (strlen ($data_final)   > 0) $data_final   = substr ($data_final,0,2)   . "/" . substr ($data_final,2,2)   . "/" . substr ($data_final,4,4);

    $pais = $_POST['pais'];
    if (strlen($_GET['pais']) > 0) $pais = $_GET['pais'];

    $cond_extrato = "";

    if (strlen($extrato) > 0) {
        if ($login_fabrica <> 1 AND $login_fabrica <> 19) {
            $cond_extrato = " AND EX.extrato = $extrato";
        } else {
            $cond_extrato = " AND EX.protocolo = '$extrato'";
        }
    }
    
    //hd-2223746
    if ($login_fabrica == 20) {
    	if ($filtro_tipo_extrato != 'TODOS') {
	    $cond_extrato .= " AND EX.aprovado IS ";
	    $cond_extrato .= ($filtro_tipo_extrato == "APROVADOS") ? "NOT NULL" : "NULL";
	}
    }
if ((in_array($login_fabrica, array(158,175)) && (!empty($_REQUEST["data_ano"]) && !empty($_REQUEST["data_mes"])))
    or ($login_fabrica == 15 AND $liberacao == 't')
    or ($login_fabrica == 42 && (strlen ($mes_referencia) > 0 || strlen ($bordero) > 0 || strlen ($valor_total) > 0 ||
        strlen ($valor_nf_peca) > 0 || strlen ($nf_peca) > 0 || strlen ($nf_autorizacao) > 0 ))
    or strlen ($posto_codigo) > 0
    OR (strlen ($data_inicial) > 0 and strlen ($data_final) > 0)
    OR ($telecontrol_distrib && ($_POST['nf_recebida'] == 'sim'))
    OR strlen($extrato) > 0 OR $aguardando_pagamento == 't'
    || in_array($login_fabrica, [169,170])
) {

    if ($login_fabrica == 1) $add_1 = " AND       EX.aprovado IS NULL ";

//--== FIM - Consulta por REQUISICAO ===============================================
    //--== INICIO - Consulta por data ===============================================
    // hd 26685
    if(strlen ($data_inicial) > 0 AND strlen ($data_final) > 0 AND strlen($extrato) == 0){
            $x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);

            $x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);

    }

    //monta sql de acordo com os campos
    if ($login_fabrica == 42) {


        if(!empty($bordero)){
            $condSqlMakita .= " and EP.duplicata = $bordero ";
        }

        if(!empty($mes_referencia)){
            $condSqlMakita .= " and EP.mes_referencia = '$mes_referencia' ";
        }

        if(!empty($valor_total)){
            $condSqlMakita .= " and EP.valor_total = $valor_total ";
        }

        if(!empty($valor_nf_peca)){
            $condSqlMakita .= " and EP.valor_nf_peca = $valor_nf_peca ";
        }

        if(!empty($nf_autorizacao)){
            $condSqlMakita .= " and EP.nf_autorizacao = '$nf_autorizacao' ";
        }

        if(!empty($nf_peca)){
            $condSqlMakita .= " and EP.nf_peca = '$nf_peca' ";
        }

    }

    //monta sql de acordo com a condiÁ„o do extrato
    if ($login_fabrica == 42 /*AND !empty($_POST['status_extrato'])*/) {
        $status_extrato = (strlen($_POST['status_extrato']) > 0) ? $_POST['status_extrato'] : "";
        switch($status_extrato){
            case "ag_liberacao":
                $condStatusMakita .= " AND EX.liberado IS NULL ";
                $joinStatusMakita = "";
                $sqlStatusMakita = "";
            break;

            case "ag_anexo":
                $condStatusMakita .= " AND EX.liberado IS NOT NULL
                AND EX.data_recebimento_nf IS NULL ";
                $joinStatusMakita = "";
                $sqlStatusMakita = "";
            break;

            case "nf_anexada":
                $condStatusMakita .= " AND EX.liberado IS NOT NULL
                AND EX.data_recebimento_nf IS NOT NULL
                AND ES.conferido IS NULL  ";
                $joinStatusMakita = " LEFT JOIN tbl_extrato_status ES ON EX.extrato = ES.extrato AND ES.fabrica = {$login_fabrica} ";
                $sqlStatusMakita = " ES.conferido , ";
            break;
            case "ex_liberado":
                $condStatusMakita .= " AND EX.liberado IS NOT NULL
                AND EX.data_recebimento_nf IS NOT NULL
                AND ES.conferido IS NOT NULL  ";
                $joinStatusMakita = " LEFT JOIN tbl_extrato_status ES ON EX.extrato = ES.extrato AND ES.fabrica = {$login_fabrica} ";
                $sqlStatusMakita = " ES.conferido , ";
            break;
            default:
                $joinStatusMakita = " LEFT JOIN tbl_extrato_status ES ON EX.extrato = ES.extrato AND ES.fabrica = {$login_fabrica} ";
                $sqlStatusMakita = " ES.conferido , ";
            break;


        }
    }
    //monta sql de acordo com a data
    if( ($login_fabrica == 42 ) && (!empty($_POST['tipoData']) ) ){
                /**
            verificar valor para montar a sql

        */
        //valida campo vindo do select
        $tipoData = $_POST['tipoData'];

        switch($_POST['tipoData']){
            case "dataBordero":
                $condSqlMakita .= " and EP.data_bordero between '$x_data_inicial' and '$x_data_final' ";
            break;

            case "dataEntregueFinanceiro":
                $condSqlMakita .= " and EP.data_entrega_financeiro between '$x_data_inicial' and '$x_data_final' ";
            break;

            case "dataAprovacao":
                $condSqlMakita .= " and EP.data_aprovacao between '$x_data_inicial' and '$x_data_final' ";
            break;
            case "dataPagamento":
                $condSqlMakita .= " and EP.data_pagamento between '$x_data_inicial' and '$x_data_final' ";
            break;
        }
    }else{
        if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0 AND strlen($extrato) == 0) {
            if ($login_fabrica == 35 && $extrato_pago == 't') {
              $add_2 = " and EP.data_pagamento between '$x_data_inicial' and '$x_data_final' ";
            } else {
              $add_2 = " AND      EX.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
            }
        }
    }
    //--== FIM - Consulta por data ==================================================

    #HD 22758
    if ($aguardando_pagamento == 't') {

        if($login_fabrica <> 24){
            if (strlen($_GET['data_inicial'])==0 or strlen($_GET['data_final'])==0){
                $data_inicial   = "";
                $data_final     = "";
                $x_data_inicial = "";
                $x_data_final   = "";
                #$add_2          = "";
            }
        }

        $add_1 = "  AND       EP.extrato_pagamento IS NULL
                    AND       EX.aprovado       IS NOT NULL ";
    }

    #HD22758 - HD1918351

    if((in_array($login_fabrica, array(35,86)) && $extrato_pago != "todos") || (!in_array($login_fabrica, array(35,86)))){
    	if ($extrato_pago == 't'){
                $add_5 = " AND       EP.data_pagamento IS NOT NULL ";
    	}

    	if(in_array($login_fabrica, array(35,86))){
    	    if($extrato_pago == "f"){
    		$add_5 = "AND EP.data_pagamento IS NULL";
    	    }
    	}
    }

    if ($login_fabrica == 15 AND $liberacao == 't') {
            $add_6 = " AND liberado IS NULL";
    }

    if($login_fabrica == 6) {
        if($liberado == 'liberado') {
            $add_6 = " AND liberado IS NOT NULL";
        }
        if($liberado == 'nao_liberado') {
            $add_6 = " AND liberado IS NULL";
        }
    }

    if($login_fabrica == 20) {
        $add_7 = " AND liberado_telecontrol IS not null ";
    }

    if (strlen($estado) > 0) {
        $add_8 = " AND PF.contato_estado = '$estado' ";
    }

    if(($login_fabrica == 104 OR $login_fabrica == 86 OR $login_fabrica == 146) AND !empty($marca_aux)){
        $joins = "LEFT JOIN tbl_os_extra          OE ON OE.extrato    = EX.extrato
                  JOIN tbl_os ON OE.os = tbl_os.os AND tbl_os.fabrica = $login_fabrica
                  JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $login_fabrica
                  JOIN tbl_marca ON tbl_produto.marca = tbl_marca.marca AND tbl_marca.fabrica = $login_fabrica";
        if ($login_fabrica == 104){
            $add_9 = (strtoupper($marca_aux) == "DWT" AND $login_fabrica == 104) ? " AND tbl_marca.nome = '$marca_aux' " : "  AND tbl_marca.nome <> 'DWT' ";
        }
        if ($login_fabrica == 86){
            $add_9 = (strtoupper($marca_aux) == "FAMASTIL" AND $login_fabrica == 86) ? " AND tbl_marca.nome = '$marca_aux' " : "  AND tbl_marca.nome like 'Taurus%' ";
        }
        if ($login_fabrica == 146) {
            $add_9 = " AND tbl_marca.nome = '{$marca_aux}' ";
        }
    }

    //--== INICIO - Consulta por data ===============================================
    $xposto_codigo = str_replace (" " , "" , $posto_codigo);
    $xposto_codigo = str_replace ("-" , "" , $xposto_codigo);
    $xposto_codigo = str_replace ("/" , "" , $xposto_codigo);
    $xposto_codigo = str_replace ("." , "" , $xposto_codigo);

    if (strlen ($posto_codigo) > 0 OR strlen ($posto_nome) > 0 ){
        $sql = "SELECT posto
                FROM tbl_posto
                JOIN tbl_posto_fabrica USING(posto)
                WHERE fabrica = $login_fabrica ";
        if (strlen ($posto_codigo) > 0 ) $sql .= " AND tbl_posto.cnpj = '$xposto_codigo' ";
        if (strlen ($posto_nome) > 0 )   $sql .= " AND tbl_posto.nome ILIKE '%$posto_nome%' ";

        $res = pg_query ($con,$sql);
        if(pg_num_rows($res) > 0){
            $posto = pg_fetch_result($res,0,0);
            $add_3 = " AND EX.posto = $posto " ;
        }
    }

    if (in_array($login_fabrica, array(169,170)) && !empty($_REQUEST["admin_sap"])) {
        $whereAdminSap = "AND PF.admin_sap = {$_REQUEST['admin_sap']}";
    }

    if (in_array($login_fabrica, array(169,170)) && !empty($_REQUEST["tipo_posto"])) {
        $whereTipoPosto = "AND PF.tipo_posto = {$_REQUEST['tipo_posto']}";
    }
    //--== FIM - Consulta por Posto ==============================================

    if($login_fabrica == 20) $add_4 = " AND PO.pais = '$pais' ";

    
    if (!empty($_REQUEST["data_mes"]) && !empty($_REQUEST["data_ano"])) {
      $mes = $_REQUEST["data_mes"];
      $ano = $_REQUEST["data_ano"];

      $whereDataAnoExtrato = "
        AND (DATE_PART('MONTH', EX.data_geracao) = {$mes} AND DATE_PART('YEAR', EX.data_geracao) = {$ano})
      ";
    }

    if ($login_fabrica == 158 && !empty($_REQUEST["tipo_extrato"])) {
      $tipo_extrato = $_REQUEST["tipo_extrato"];

      $whereTipoExtrato = "
        AND EX.protocolo = '{$tipo_extrato}'
      ";
    }


//se for Fricon (52) monta condiÁ„o para filtrar tbm pela OS
    if(!empty($nroOs)){
                $sqlOS = " JOIN tbl_os_extra ON EX.extrato = tbl_os_extra.extrato
                            JOIN tbl_os ON tbl_os.os = tbl_os_extra.os and tbl_os.sua_os =  '$nroOs' ";
    }

    if ( isset($_POST['tipo_envio_nf']) ) {
        $tipo_envio_nf = ($_POST['tipo_envio_nf'] == "online") ? "online" : "correios";
        $join_eletronico = "JOIN tbl_tipo_gera_extrato ON PF.posto = tbl_tipo_gera_extrato.posto AND PF.fabrica = tbl_tipo_gera_extrato.fabrica AND tipo_envio_nf like '%$tipo_envio_nf%'";
    }
    if($login_fabrica == 134){
	   $camposSql = getCamposSqlThermoSystem();
    }else{
      if ($login_fabrica == 158) {
        $campoProtocolo = "EX.protocolo,";
      } else {
        $campoProtocolo = "LPAD (EX.protocolo,6,'0') AS protocolo,";
      }

    if($telecontrol_distrib){
      $subqueryPositec = " (select pendente from tbl_extrato_status where extrato = EX.extrato and fabrica = EX.fabrica order by data desc limit 1) as pendente,
(select admin_conferiu from tbl_extrato_status where extrato = EX.extrato and fabrica = EX.fabrica order by data desc limit 1) as admin_conferiu,
(select conferido from tbl_extrato_status where extrato = EX.extrato and fabrica = EX.fabrica order by data desc limit 1) as conferido, ";
      $orberByPositec = "pendente, admin_conferiu, conferido, ";
    }


    if(in_array($login_fabrica, [152,169,170,180,181,182])) {
	$campoPedidoSap = "EP.autorizacao_pagto,";
    }


     $camposSql = "PO.posto                                                 ,
                    PO.nome                                                  ,
                    PO.cnpj                                                  ,
                    PO.cidade AS cidade_posto                                ,
                    PF.contato_estado  as estado                             ,
                    PF.contato_email                                     AS email        ,
                    PF.credenciamento                    ,
                    PF.conta_contabil,
		    PF.codigo_posto                                          ,
		    length(PF.codigo_posto) AS tamanho,
                    PF.distribuidor                                          ,
                    EX.nf_recebida                                           ,
                    $subqueryPositec
                    PF.imprime_os                                            ,
                    TP.descricao                                         AS tipo_posto   ,
                    EX.extrato                                               ,
                    EX.bloqueado                                             ,
                    TO_CHAR (EX.liberado,'dd/mm/yyyy')             AS liberado,
                    EX.estoque_menor_20                                      ,
                    TO_CHAR (EX.aprovado,'dd/mm/yyyy')                   AS aprovado     ,
                    $campoProtocolo
                    TO_CHAR (EX.data_geracao,'dd/mm/yyyy')               AS data_geracao ,
                    EX.data_geracao                                      AS xdata_geracao,
                    EX.total                                                 ,
                    EX.pecas                                                 ,
                    EX.mao_de_obra                                           ,
		    EX.deslocamento					     ,
		    EX.valor_adicional					     ,
                    EX.avulso                                             AS avulso       ,
                    EX.recalculo_pendente                                    ,
                    EP.nf_autorizacao                                        ,
		    EP.data_entrega_financeiro                               ,
                    {$campoPedidoSap}
		    EP.baixa_extrato,
                    TO_CHAR (EX.previsao_pagamento,'dd/mm/yyyy')          AS previsao_pagamento,
                    TO_CHAR (EX.data_recebimento_nf,'dd/mm/yyyy')         AS data_recebimento_nf,
                    TO_CHAR (EP.data_pagamento,'dd/mm/yyyy')              AS baixado      ,
                    EX.admin_libera_pendencia,
                    EX.data_libera_pendencia,
                    EP.valor_liquido                                         ,
                    EE.nota_fiscal_devolucao                                 ,
                    EE.nota_fiscal_mao_de_obra                               ,
                    to_char(EE.data_coleta,'dd/mm/yyyy')                 AS  data_coleta     ,
                    to_char(EE.data_entrega_transportadora,'dd/mm/yyyy') AS  data_entrega_transportadora,
                    to_char(EE.emissao_mao_de_obra,'dd/mm/yyyy')         AS  emissao_mao_de_obra,
                    count(tbl_os_extra.os) as qtde,
                    tbl_admin.nome_completo " ;
        if (in_array($login_fabrica, array(30))) {
            $camposSql .= ",SUM(JSON_FIELD('taxa_entrega', tbl_os_campo_extra.campos_adicionais)::integer) AS taxa_entrega";
        }

        if(in_array($login_fabrica, array(152,180,181,182,183,203))){
          $camposSql .= ", (select obs from tbl_extrato_status where extrato = ex.extrato order by data desc limit 1) as observacao_extrato_status  ";
        }

        if($login_fabrica == 91){
            $campo_obs_wanke = ", tbl_extrato_status.obs as observacao_extrato_status";
            $join_obs_wanke  = " LEFT JOIN tbl_extrato_status on (tbl_extrato_status.extrato = ex.extrato)";
            $grup_by_wanke   = ", tbl_extrato_status.obs";
        }
    }

    if($telecontrol_distrib){

        if($_POST['nf_recebida'] == 't'){
           $condicaoPositec = " AND EX.nf_recebida is true ";
        }elseif($_POST['nf_recebida'] == 'f'){
          $condicaoPositec = " AND (EX.nf_recebida is false OR EX.nf_recebida is null ) ";
        }elseif($_POST['nf_recebida'] == 'data_pagamento'){
          $condicaoPositec = " AND EP.data_pagamento is not null ";
        }

        if($_POST['nf_recebida'] == 'sim'){
          $condicaoStatusPositec = "  pendente is true and conferido isnull ";
        }elseif($_POST['nf_recebida'] == 'nao'){
          $condicaoStatusPositec = "  conferido notnull   ";
        }
    }

    $condUFPostoImb     = "";
    $condTipoExtratoImb = "";

    if ($login_fabrica == 158) {

        if (strlen($estado_posto_autorizado) > 0) {
           $condUFPostoImb = " AND PF.contato_estado = '$estado_posto_autorizado'";
        }


        if (strlen($tipo_extrato_imb) > 0) {
          $condTipoExtratoImb = " AND EX.protocolo = '$tipo_extrato_imb'";
        }

    }

    if ($login_fabrica == 158) {
      $camposSql .= ", CASE WHEN (SELECT COUNT(*) FROM tbl_extrato_lgr WHERE extrato = EX.extrato) > 0 THEN TRUE ELSE FALSE END AS lgr ";
    }

    if (in_array($login_fabrica, [169,170])) {
        $condStatusExtrato = '';
        if ($status == "Pagamento Efetivado") {
            $condStatusExtrato = "AND EP.data_pagamento IS NOT NULL";
        } elseif ($status == 'Pagamento Bloqueado') {
            $condStatusExtrato = "AND EX.bloqueado IS TRUE";
        } elseif ($status == "Nota Aprovada") {
            $condStatusExtrato = "
                AND EP.data_entrega_financeiro IS NOT NULL
                AND EP.data_pagamento IS NULL
                AND EX.bloqueado IS NOT TRUE
            ";
        } elseif ($status == 'Nota Emitida') {
            $condStatusExtrato = "
                AND EX.nf_recebida IS TRUE
                AND EP.data_entrega_financeiro IS NULL
            ";
        } elseif ($status == "Liberado") {
            $condStatusExtrato = "
                AND EP.data_pagamento IS NULL 
                AND (EX.bloqueado IS NOT TRUE OR EX.bloqueado IS NULL)
                AND EP.data_entrega_financeiro IS NULL  
                AND (EX.nf_recebida IS NOT TRUE OR EX.nf_recebida IS NULL)
            ";
        }

	$condPedidoSap = "";
	if (!empty($pedidoSap)) {
	    $condPedidoSap = "AND TRIM(EP.autorizacao_pagto) = TRIM('{$pedidoSap}')";
	}

	$condDataLimit = "";
	if (empty($data_inicial) && empty($data_final) && !empty($status)) {
	    $condDataLimit = "AND EX.data_geracao BETWEEN CURRENT_DATE - INTERVAL '90 days' AND CURRENT_DATE";
	}
    }

    //select que traz os results sets da consulta (aÁ„o do botao Filtrar)
    $sql = "SELECT DISTINCT {$camposSql}
                    {$campo_obs_wanke}
                    INTO    TEMP tmp_extrato_consulta /*hd 39502*/
            FROM      tbl_extrato           EX
            LEFT JOIN tbl_os_extra ON EX.extrato = tbl_os_extra.extrato AND tbl_os_extra.i_fabrica = $login_fabrica 
            $join_obs_wanke";

            if($login_fabrica== 52){
                $sql .= $sqlOS;
            }
            if($login_fabrica == 42){
                if(!empty($condSqlMakita)){
                    //Na sql normal est· fazendo um left join, mas como È preciso filtrar os resultados, tem que fazer um Inner Join
                    $sqlMakita = "JOIN tbl_extrato_pagamento EP ON EX.extrato    = EP.extrato ".$condSqlMakita;
                }

            }
            if ($login_fabrica == 158) {
                $unidade_negocio = array_filter($_POST["unidade_negocio"]);
                if (count($unidade_negocio)) {
                    $unidade_negocio_aux = implode(',', $unidade_negocio);
                    $sql .= " JOIN tbl_extrato_agrupado ON tbl_extrato_agrupado.extrato = ex.extrato AND tbl_extrato_agrupado.codigo::integer IN($unidade_negocio_aux)";
                }
            }
            $sql .= " JOIN      tbl_posto             PO on PO.posto = EX.posto
            JOIN      tbl_posto_fabrica     PF ON EX.posto      = PF.posto      AND PF.fabrica = $login_fabrica
            JOIN      tbl_tipo_posto        TP ON TP.tipo_posto = PF.tipo_posto AND TP.fabrica = $login_fabrica";


            if($login_fabrica == 148){
                $sql .= "left JOIN tbl_extrato_agrupado ON tbl_extrato_agrupado.extrato = ex.extrato ";
            }


            if($login_fabrica == 123){
             // $sql .= " LEFT JOIN tbl_extrato_status ON tbl_extrato_status.extrato = EX.extrato AND tbl_extrato_status.fabrica = $login_fabrica ";
            }

            if($login_fabrica == 42){
                if(!empty($condSqlMakita)){
                    //Na sql normal est· fazendo um left join, mas como È preciso filtrar os resultados, tem que fazer um Inner Join
                    $sqlMakita = " JOIN tbl_extrato_pagamento EP ON EX.extrato    = EP.extrato ".$condSqlMakita;
                    $sql .= $sqlMakita;
                }else{
                    $sql .= " LEFT JOIN tbl_extrato_pagamento EP ON EX.extrato    = EP.extrato ";
                }

            }else if($login_fabrica == 91 ){

                $sql .= $sqlJoin_data_baixa;

            } else {

              if (strlen($_POST['filtro_requisicao']) == 0) {

                $sql .= " LEFT JOIN tbl_extrato_pagamento EP ON EX.extrato    = EP.extrato ";
              } else {
                $sql .= " JOIN tbl_extrato_pagamento EP ON EX.extrato = EP.extrato and ep.autorizacao_pagto = '{$_POST['filtro_requisicao']}' ";
              }
            }

            $condicao = "";

            if ($login_fabrica == 152) {
                $regiao_estado = $_POST['regiao_estado'];
				$regiao_estado = str_replace(",", "','",$regiao_estado);
				if (strlen($regiao_estado) > 0) {
					$condicao = " AND PO.estado IN ('$regiao_estado')";
				}
                // √â atribuÌdo novamente o valor original do POST para a vari·vel utilizar no elemento select
                $regiao_estado = $_POST['regiao_estado'];
            }

            if (in_array($login_fabrica, array(30))) {
                $sql .= "LEFT JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os_extra.os AND tbl_os_campo_extra.fabrica = {$login_fabrica}";
            }
            $sql .= " LEFT JOIN tbl_extrato_extra     EE ON EX.extrato    = EE.extrato
            LEFT JOIN tbl_admin ON EE.admin = tbl_admin.admin
            $joins
            $join_eletronico
            WHERE     EX.fabrica = $login_fabrica";

            if ($login_fabrica <> 139){
                $sql .= " AND PF.distribuidor IS NULL";
            }

            if($login_fabrica == 131){
              if($extrato_pago == 'sim'){
                $sql .= " and EP.data_pagamento is not null "; 
              }elseif($extrato_pago == 'nao'){
                $sql .= " and EP.data_pagamento is null "; 
              }
            }

            $sql .="
            $where_estado_regiao
            $condicao
            $condTipo_atendimento
            $cond_extrato
            $sqlValorBaixa
            $condicaoPositec

            $add_1
            $add_2
            $add_3
            $add_4
            $add_5
            $add_6
            $add_7
            $add_8
            $add_9
            $cond_inibido
            {$condUFPostoImb}
            {$condTipoExtratoImb}
            {$whereTipoExtrato}
            {$whereDataAnoExtrato}
            {$whereAdminSap}
            {$whereTipoPosto}
	    {$condStatusExtrato}
	    {$condPedidoSap}
	    {$condDataLimit}
            ";
    if($login_fabrica == 134){
        $sql .= getCamposGroupThermoSystem();
    }else{
        $sql .= "  GROUP BY PO.posto,
                    PO.nome,
                    PO.cnpj,
                    PF.contato_estado,
                    PF.contato_email,
                    PF.credenciamento,
                    PF.conta_contabil,
		    PF.codigo_posto,
		    tamanho,
                    PF.distribuidor,
                    EX.nf_recebida,
                    $orberByPositec
                    PF.imprime_os,
                    TP.descricao,
                    EX.extrato,
                    EX.bloqueado,
                    EX.liberado,
                    EX.estoque_menor_20,
                    EX.aprovado,
                    EX.protocolo,
		    {$campoPedidoSap}
                    EX.data_geracao,
                    EX.data_geracao,
                    EX.total,
                    EX.pecas,
                    EX.mao_de_obra,
                    EX.avulso,
                    EX.recalculo_pendente,
                    EP.nf_autorizacao,
		    EP.data_entrega_financeiro,
                    EP.baixa_extrato,
                    EX.previsao_pagamento,
                    EX.data_recebimento_nf,
                    EP.data_pagamento,
                    EX.admin_libera_pendencia,
                    EX.data_libera_pendencia,
                    EP.valor_liquido,
                    EE.nota_fiscal_devolucao,
                    EE.nota_fiscal_mao_de_obra,
                    EE.data_coleta,
                    EE.data_entrega_transportadora,
                    EE.emissao_mao_de_obra,
                    tbl_admin.nome_completo 
                    $grup_by_wanke";
    }

    if ($login_fabrica == 158) {
      $sql .= ", lgr ";
    }

    if (in_array($login_fabrica, [169,170])) {
	$sql .= ($status == 'Nota Emitida') ? " ORDER BY data_recebimento_nf" : " ORDER BY EP.autorizacao_pagto";
    } else {
	    if($login_fabrica == 1){
		$sql .= " ORDER BY PF.codigo_posto, EX.data_geracao";
	    }else if($login_fabrica == 85){
		$sql .= " ORDER BY tamanho";
	    }else{
		$sql .= " ORDER BY PO.nome, EX.data_geracao";
	    }
    }
// var_dump($sql);die;
    if(strlen($cond_extrato) == 0 AND strlen($add_2) == 0 AND strlen($add_3) == 0){
	    if($login_fabrica == 24 AND $aguardando_pagamento == "t"){
		    $res = pg_query ($con,$sql);
	    }else if(in_array($login_fabrica, array(158,175)) && isset($whereDataAnoExtrato)) {
        $res = pg_query ($con,$sql);
      }else if ($telecontrol_distrib || in_array($login_fabrica, [169,170])) {
        $res = pg_query ($con,$sql);
      } else{
		    echo "<center>Informe filtros para realizar a pesquisa</center>";
        include "rodape.php";
	      exit;
      }
    }else{

	    $res = pg_query ($con,$sql);

    }

 
    /* hd 39502 */
    if ($login_fabrica==20) {
        $sql = "ALTER table tmp_extrato_consulta add column total_cortesia double precision";
        $res = pg_query ($con,$sql);

        $sql = "SELECT tbl_os_extra.extrato,sum(tbl_os.mao_de_obra) + sum(tbl_os.pecas) AS total
            INTO TEMP tmp_extrato_consulta_aux
                          FROM tbl_os
                          JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os AND tbl_os_extra.i_fabrica=$login_fabrica
                          WHERE tbl_os.fabrica = $login_fabrica
                            AND   tbl_os.tipo_atendimento = 16
                AND tbl_os_extra.extrato in ( select extrato from tmp_extrato_consulta )
            GROUP BY tbl_os_extra.extrato;";

        $res = pg_query ($con,$sql);



        $sql = "UPDATE tmp_extrato_consulta SET
                                        total_cortesia = (
                                                SELECT tmp_extrato_consulta_aux.total
                                                FROM tmp_extrato_consulta_aux
                                                WHERE tmp_extrato_consulta_aux.extrato = tmp_extrato_consulta.extrato);";
        $res = pg_query ($con,$sql);


        /*$sql = "UPDATE tmp_extrato_consulta SET
                    total_cortesia = (
                        SELECT sum(tbl_os.mao_de_obra) + sum(tbl_os.pecas)
                        FROM tbl_os
                        JOIN tbl_os_extra USING(os)
                        WHERE extrato = tmp_extrato_consulta.extrato
                        AND   tbl_os.tipo_atendimento = 16
                    )";
        $res = pg_query ($con,$sql);*/
    }

    if($telecontrol_distrib AND strlen(trim($condicaoStatusPositec))>0){
      $wherePositec = " WHERE $condicaoStatusPositec";
    }

    if ($login_fabrica == 183) {
      $condAnexo = " AND referencia = 'extrato'";
    }

    if ($usaNotaFiscalServico && !in_array($login_fabrica, [152,180,181,182])) {
      if(isset($_POST['nf_recebida'])){
        switch($_POST['nf_recebida']){
          case "envio_nf_pendente":
              $condFiltroStatus = " WHERE observacao_extrato_status = 'Aguardando Envio da Nota Fiscal' 
                                    AND (
                                        SELECT tdocs
                                        FROM tbl_tdocs
                                        WHERE referencia_id = tmp_extrato_consulta.extrato
                                        AND contexto = 'extrato'
                                        AND situacao = 'ativo'
                                        LIMIT 1
                                      ) IS NULL";
          break;
          case "nf_enviada":
              $condFiltroStatus = " WHERE (
                                      SELECT tdocs
                                      FROM tbl_tdocs
                                      WHERE referencia_id = tmp_extrato_consulta.extrato
                                      AND contexto = 'extrato'
                                      AND situacao = 'ativo'
                                      $condAnexo
                                      LIMIT 1
                                    ) IS NOT NULL";
          break;
          case "extratos_pagos":
              $condFiltroStatus = " WHERE baixado IS NOT NULL";
          break;
          case "nf_nao_visualizadas":
            if($login_fabrica == 91){
                $condFiltroStatus = " WHERE observacao_extrato_status ILIKE 'Nota Fiscal Reprovada%'";
            }else{

		if($login_fabrica == 183){ $cond_anexo_itatiaia = " and tbl_tdocs.referencia = 'extrato' "; }

                $condFiltroStatus = " WHERE observacao_extrato_status != 'Nota Fiscal Aprovada' AND (
                    SELECT tdocs
                    FROM tbl_tdocs
                    WHERE referencia_id = tmp_extrato_consulta.extrato
                    AND contexto = 'extrato'
			$cond_anexo_itatiaia
                    AND situacao = 'ativo'
                    LIMIT 1
                  ) IS NOT NULL";
            }
          break;
          case "nf_visualizadas":
              $condFiltroStatus = " WHERE observacao_extrato_status = 'Nota Fiscal Aprovada' ";
          break;
          case "nao_liberado":
                $condFiltroStatus = " WHERE liberado IS NULL";
          break;
        }
      }
    }

    if(in_array($login_fabrica, array(152,180,181,182))){

      $filtro_status = $_POST['filtro_status'];

      if(isset($filtro_status)){
        switch($filtro_status){
          case "pendente_aprovacao":
              $condFiltroStatus = " WHERE observacao_extrato_status = 'Pendente de AprovaÁ„o' ";
          break;
          case "aguardando_nf_posto":
              $condFiltroStatus = " WHERE ( (observacao_extrato_status = 'Aguardando Nota Fiscal do Posto' 
                                              OR
                                             observacao_extrato_status = 'Aguardando Envio da Nota Fiscal' 
                                            )
                                          AND (
                                                SELECT tdocs
                                                FROM tbl_tdocs
                                                WHERE referencia_id = tmp_extrato_consulta.extrato
                                                AND contexto = 'extrato'
                                                AND situacao = 'ativo'
                                                LIMIT 1
                                              ) IS NULL
                                          )";

          break;
          case "aguardando_aprovacao_nf":
              $condFiltroStatus = " WHERE (observacao_extrato_status = 'Aguardando AprovaÁ„o de Nota Fiscal'
                                    or ((SELECT tdocs
                                         FROM tbl_tdocs
                                         WHERE referencia_id = tmp_extrato_consulta.extrato
                                         AND contexto = 'extrato'
                                         AND situacao = 'ativo'
                                         LIMIT 1) IS NOT NULL and observacao_extrato_status ~ 'Aguardand' and observacao_extrato_status ~*'nota') ) ";
          break;
          case "aguardando_encerramento":
              $condFiltroStatus = " WHERE (observacao_extrato_status = 'Aguardando Encerramento' OR observacao_extrato_status = 'Nota Fiscal Aprovada')";
          break;
          case "encerramento":
              $condFiltroStatus = " WHERE (observacao_extrato_status = 'Encerramento')";
          break;
        }
      }
    }

    if($login_fabrica == 151){
        $centro_distribuicao = $_POST['centro_distribuicao'];

        if($centro_distribuicao != "mk_vazio"){
            $campo_p_adicionais = ",tbl_produto.parametros_adicionais::json->>'centro_distribuicao' AS centro_distribuicao";
            $p_adicionais = " AND tbl_produto.parametros_adicionais::json->>'centro_distribuicao' = '$centro_distribuicao'";
            $distinct_P_adicionais = " DISTINCT ";
            $join_p_adicionais = " JOIN tbl_produto ON tbl_produto.fabrica_i = {$login_fabrica}";                       
        }            
    }

    $sql = "SELECT {$distinct_P_adicionais}
          tmp_extrato_consulta.*
          {$campo_p_adicionais}
          FROM tmp_extrato_consulta
          {$join_p_adicionais}
          $wherePositec
          {$p_adicionais}
          $condFiltroStatus";

    if ($login_fabrica == 156) {
        $sql = "SELECT * FROM tmp_extrato_consulta WHERE extrato NOT IN (
            SELECT DISTINCT extrato FROM tmp_extrato_consulta
            JOIN tbl_os_extra USING(extrato)
            JOIN tbl_os using(os)
            WHERE hd_chamado IS NOT NULL
        ) OR extrato NOT IN (
            SELECT DISTINCT tmp_extrato_consulta.extrato
            FROM tmp_extrato_consulta
            JOIN tbl_os_extra ON tbl_os_extra.extrato = tmp_extrato_consulta.extrato
              AND tbl_os_extra.i_fabrica = $login_fabrica
            JOIN tbl_hd_chamado_extra USING(os)
        )";
    }

    $res = pg_query ($con,$sql);


        //echo "<pre>".print_r(pg_fetch_all($res),1)."</pre>";exit;

    if (in_array($login_fabrica,array(1,20,35,86,131,148,152,180,181,182,183))) {
        $data = date("d-m-Y-H:i");
        $fileName = "consulta_extratos".$data.".xls";

        $fp = fopen("/tmp/{$fileName}", "w");

        montaArquivo($fp, $res,$login_fabrica);
        fclose($fp);
        if (file_exists("/tmp/{$fileName}")) {

            system("mv /tmp/{$fileName} xls/{$fileName}");

            $relatorio_excel = "xls/{$fileName}";
        }
    }

    $qtde_extratos = pg_num_rows ($res);

    if ($qtde_extratos == 0) {
        echo "<center><div style='font-family : arial; color: #000000; font-size: 12px'>N„o Foram Encontrados Resultados para esta Pesquisa</div></center>";
    }
    if (pg_num_rows ($res) > 0) {

        $legenda_avulso="";
        if($login_fabrica == 20 ) {
            $legenda_avulso=" (TambÈm Identifica Imposto para paises da AmÈrica Latina)";
        }

        //HD 237498: Marcando os extratos que possuem OS em interven√ßao de KM em aberto
        if (in_array($login_fabrica, $intervencao_km_extrato)) {
            echo "<table width='700px' class='tabela' border='0' cellspacing='0' cellpadding='0' align='center'>";
            echo "<tr>";
            echo "<td align='center' width='16' bgcolor='#FFCC99'>&nbsp;</td>";
            echo "<td align='left'><&nbsp; OS com IntervenÁ„o de KM em aberto</td>";
            echo "</tr><br>";
        }

        echo "<br /><table width='700px' border='0' cellspacing='5' cellpadding='0' align='center'>";
        echo "<tr>";
        echo "<td align='center' width='16' bgcolor='#FFE1E1'>&nbsp;</td>";
        echo "<td align='left'>&nbsp; ".traduz('Extrato Avulso')." $legenda_avulso</td>";

        if (in_array($login_fabrica, [20])) {

          echo "<td align='center' width='16' bgcolor='#fcf568'>&nbsp;</td>";
          echo "<td align='left'>Envio de NFe pendente</td>";

        }

        if($login_fabrica == 148){      
              echo "<td align='center'><div class='btn_excel'>
                <span><img src='imagens/excel.png' /></span>
                <span class='txt' onclick='javascript: window.open(\"xls/{$fileName}\")'; >Gerar Arquivo Excel</span>
              </div></td>";
        }

        echo "</tr>";

        if($login_fabrica==6){//hd 3471
            echo "<tr>";
            echo "<td align='center'>&nbsp;</td>";
            echo "<td align='left'>&nbsp; Extrato com variaÁ„o superior a 15%</td>";
            echo "</tr>";
        }
        if($login_fabrica == 24){//hd 3471
            echo "<tr>";
            echo "<td align='center' width='16' bgcolor='orange'>&nbsp;</td>";
            echo "<td align='left'>&nbsp; Extrato bloqueado</td>";
            echo "</tr>";
        }
        if($login_fabrica==1){
            echo "<tr>";
                echo "<td align='center' width='16'>&nbsp;</td>";
                echo "<td align='left'>&nbsp; Extrato Bloqueado</td>";
            echo "</tr>";
            echo "<tr>";
                echo "<td align='center' width='16' >&nbsp;</td>";
                echo "<td align='left'>&nbsp; Extrato do Posto com itens de estoque menor que 20s</td>";
            echo "</tr>";
            echo "<tr>";
                echo "<td align='center' width='16' bgcolor='#ffffb2'>&nbsp;</td>";
                echo "<td align='left'>&nbsp; Extrato Inibido</td>";
            echo "</tr>";

        }
        echo "</table> <br />";

        if($login_fabrica == 91){
            $total_valor_liquido = 0;
            $total_total = 0;
        }

	$total_os = 0;

        for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {

            $posto                   = trim(pg_fetch_result($res,$i,posto));
            $codigo_posto            = trim(pg_fetch_result($res,$i,codigo_posto));
            $credenciamento          = trim(pg_fetch_result($res,$i,'credenciamento'));
            $nome                    = trim(pg_fetch_result($res,$i,nome));
            $posto_estado            = trim(pg_fetch_result($res,$i,estado));
            $email                   = trim(pg_fetch_result($res,$i,email));
            $tipo_posto              = trim(pg_fetch_result($res,$i,tipo_posto));
            $extrato                 = trim(pg_fetch_result($res,$i,extrato));
            $data_geracao            = trim(pg_fetch_result($res,$i,data_geracao));
            $qtde_os_ex              = trim(pg_fetch_result($res,$i,qtde));
            $total                   = trim(pg_fetch_result($res,$i,total));
            $nf_autorizacao          = trim(pg_fetch_result($res,$i,nf_autorizacao));
	    $data_entrega_financeiro = trim(pg_fetch_result($res,$i,data_entrega_financeiro));
            $previsao_pagamento      = trim(pg_fetch_result($res,$i,previsao_pagamento));
            $data_recebimento_nf     = trim(pg_fetch_result($res,$i,data_recebimento_nf));
            $baixado                 = trim(pg_fetch_result($res,$i,baixado));
            $baixa_extrato           = trim(pg_fetch_result($res,$i,"baixa_extrato"));
            if (in_array($login_fabrica, array(30))) {
                $taxa_entrega = pg_fetch_result($res,$i,"taxa_entrega");
            }

            if($login_fabrica == 183){
                $cod_fornecedor = trim(pg_fetch_result($res,$i,conta_contabil)); 
            }

            if (in_array($login_fabrica, [152,169,170,180,181,182])) {
              $autorizacao_pagto = trim(pg_fetch_result($res, $i, autorizacao_pagto));
            }

            if(in_array($login_fabrica, array(152,180,181,182,203))){
              $observacao_extrato_status = pg_fetch_result($res, $i, observacao_extrato_status);
            }
    	    if(strlen($baixa_extrato) > 0){
    		  $baixa_extrato = new DateTime($baixa_extrato);
    		  $baixa_extrato = $baixa_extrato->format("d/m/Y");
    	    }else{
    		  $baixa_extrato = "";
    	    }
            $distribuidor            = trim(pg_fetch_result($res,$i,distribuidor));
            $xtotal                  = round($total);
            $soma_total = $soma_total + $total; //HD 49532
			$raw_total = $total;
			if($login_fabrica == 1) {
				$sql = "SELECT sum(tbl_os.pecas * (((regexp_replace(campos_adicionais,'(\w)\\\\u','\\1\\\\\\\\u','g')::jsonb->>'TxAdmGrad')::float -1))) 
						from tbl_os
						join tbl_os_extra using(os)
						join tbl_os_campo_extra on tbl_os_campo_extra.os = tbl_os.os and campos_adicionais ~'TxAdm'
						where tbl_os_extra.extrato = $extrato
						and tbl_os.pecas > 0
						and (((regexp_replace(campos_adicionais,'(\w)\\\\u','\\1\\\\\\\\u','g')::jsonb->>'TxAdmGrad')::float)) > 0
			 ";
				$resX = pg_query($con, $sql);
				if(pg_num_rows($resX) > 0) {
					$totalTx = pg_fetch_result($resX,0, 0); 
					$total+= $totalTx;
				}

			}
            $total                   = number_format ($total,2,',','.');

            /* hd 39502 */
            if ($login_fabrica == 20) {
                $total_cortesia = trim(pg_fetch_result($res,$i,total_cortesia));
                $total_cortesia = number_format ($total_cortesia,2,',','.');
            }

            $liberado                    = trim(pg_fetch_result($res,$i,liberado));
            $aprovado                    = trim(pg_fetch_result($res,$i,aprovado));
            $estoque_menor_20            = trim(pg_fetch_result($res,$i,estoque_menor_20));
            $protocolo                   = trim(pg_fetch_result($res,$i,protocolo));
            $nota_fiscal_devolucao       = trim(pg_fetch_result($res,$i,nota_fiscal_devolucao));
            $nota_fiscal_mao_de_obra     = trim(pg_fetch_result($res,$i,nota_fiscal_mao_de_obra));
            $data_coleta                 = trim(pg_fetch_result($res,$i,data_coleta));
            $data_entrega_transportadora = trim(pg_fetch_result($res,$i,data_entrega_transportadora));
            $xdata_geracao               = trim(pg_fetch_result($res,$i,xdata_geracao));
            $bloqueado                   = trim(pg_fetch_result($res,$i,bloqueado));
            $recalculo_pendente          = trim(pg_fetch_result($res,$i,recalculo_pendente));
	    $nf_recebida                 = pg_fetch_result($res, $i, nf_recebida);

            $pecas              = trim(pg_fetch_result($res,$i,pecas));
            $mao_de_obra        = trim(pg_fetch_result($res,$i,mao_de_obra));
            $avulso             = trim(pg_fetch_result($res,$i,avulso));
	    $deslocamento       = trim(pg_fetch_result($res,$i,deslocamento));
	    $valor_adicional    = trim(pg_fetch_result($res,$i,valor_adicional));

            $pecas       = number_format($pecas,2,',','.');
            $mao_de_obra = number_format($mao_de_obra,2,',','.');
            $avulso      = number_format($avulso,2,',','.');
	    $deslocamento= number_format($deslocamento,2,',','.');
	    $valor_adicional = number_format($valor_adicional,2,',','.');


            if ($login_fabrica == 158) {
              $temLGR = pg_fetch_result($res, $i, "lgr");
            }

            if ($telecontrol_distrib) {
              $sql_status = "SELECT pendente, admin_conferiu, conferido FROM tbl_extrato_status WHERE extrato = $extrato and fabrica = $login_fabrica";
              $res_status = pg_query($con, $sql_status);
              if(pg_num_rows($res_status)>0){
                  $pendente         = pg_fetch_result($res_status, 0, 'pendente');
                  $conferido        = pg_fetch_result($res_status, 0, 'conferido');
                  $admin_conferiu   = pg_fetch_result($res_status, 0, 'admin_conferiu');
              }else{
                $pendente = "";
                $conferido = "";
                $admin_conferiu = "";
              }
            }

			if($login_fabrica == 24) {
				$verifica_extrato_bloqueado = verifica_extrato_bloqueado($posto, $extrato, $data_geracao);
			}

            if($login_fabrica == 158){
              /*$sqlUnidade = "SELECT
                                CASE
                                    WHEN tbl_distribuidor_sla.unidade_negocio = '6200' THEN
                                    tbl_distribuidor_sla.unidade_negocio||' - SAO PAULO'
                                    WHEN tbl_distribuidor_sla.unidade_negocio = '6201' THEN
                                    tbl_distribuidor_sla.unidade_negocio||' - SAO PAULO - OESTE'
                                    WHEN tbl_distribuidor_sla.unidade_negocio = '6300' THEN
                                    tbl_distribuidor_sla.unidade_negocio||' - BEBIDAS FRUKI'
                                    WHEN tbl_distribuidor_sla.unidade_negocio = '6500' THEN
                                  tbl_distribuidor_sla.unidade_negocio||' - MATO GROSSO DO SUL'
                                    WHEN tbl_distribuidor_sla.unidade_negocio = '6600' THEN
                                  tbl_distribuidor_sla.unidade_negocio||' - RIO DE JANEIRO'
                                    WHEN tbl_distribuidor_sla.unidade_negocio = '6700' THEN
                                  tbl_distribuidor_sla.unidade_negocio||' - DANONE'
                                  WHEN tbl_distribuidor_sla.unidade_negocio = '6800' THEN
                                  tbl_distribuidor_sla.unidade_negocio||' - WOW NUTRICION'
                                  WHEN tbl_distribuidor_sla.unidade_negocio = '7200' THEN
                                  tbl_distribuidor_sla.unidade_negocio||' - HAAGEN DAZS'
                                  WHEN tbl_distribuidor_sla.unidade_negocio = '6900' THEN
                                  tbl_distribuidor_sla.unidade_negocio||' - RIO GRANDE DO SUL'
                                  WHEN tbl_distribuidor_sla.unidade_negocio = '7000' THEN
                                  tbl_distribuidor_sla.unidade_negocio||' - PARANA'
                                  WHEN tbl_distribuidor_sla.unidade_negocio = '7100' THEN
                                  tbl_distribuidor_sla.unidade_negocio||' - SOLAR GR'
                                    ELSE
                                  tbl_distribuidor_sla.unidade_negocio||' - '||tbl_cidade.nome
                                END AS cidade
                            FROM tbl_distribuidor_sla
                            JOIN tbl_cidade USING(cidade)
                            JOIN tbl_extrato_agrupado ON tbl_extrato_agrupado.codigo=tbl_distribuidor_sla.unidade_negocio
                            WHERE tbl_distribuidor_sla.fabrica = $login_fabrica
                            AND tbl_extrato_agrupado.extrato={$extrato}";*/

              $sqlUnidade = "SELECT DISTINCT ON (tbl_distribuidor_sla.unidade_negocio)                               
                                tbl_distribuidor_sla.unidade_negocio||' - '||tbl_unidade_negocio.nome AS cidade
                                FROM tbl_distribuidor_sla
                                JOIN tbl_unidade_negocio ON tbl_unidade_negocio.codigo = tbl_distribuidor_sla.unidade_negocio                            
                                JOIN tbl_extrato_agrupado ON tbl_extrato_agrupado.codigo = tbl_distribuidor_sla.unidade_negocio
                                WHERE tbl_distribuidor_sla.fabrica = {$login_fabrica}
                                AND tbl_extrato_agrupado.extrato = {$extrato}
                                GROUP BY tbl_distribuidor_sla.unidade_negocio, tbl_unidade_negocio.nome
                                ORDER BY tbl_distribuidor_sla.unidade_negocio ASC";

              $resUnidade = pg_query($con, $sqlUnidade);
              if (pg_num_rows($resUnidade) > 0) {
                  $unidadeNegocio = pg_fetch_result($resUnidade, 0, 'cidade');
              } else {
                  $unidadeNegocio = "";
              }
            }

            //HD 145478: Nome do admin que aprovou o extrato
            $auditor = trim(pg_fetch_result($res, $i, 'nome_completo'));

            //HD 12104
            if ($login_fabrica == 14) {
                $imprime_os          = trim(pg_fetch_result($res,$i,imprime_os));
                $emissao_mao_de_obra = trim(pg_fetch_result($res,$i,emissao_mao_de_obra));// HD 209349
            }

            $msg_os_deletadas="";

            if (trim(pg_fetch_result($res,$i,valor_liquido)) <> '') {
                $total_valor_liquido += pg_fetch_result($res,$i,valor_liquido);
                $valor_liquido = number_format (trim(pg_fetch_result($res,$i,valor_liquido)),2,',','.');
            }else{
                $valor_liquido = number_format(0,2,',','.');
            }

            $newClass = ($login_fabrica == 198) ? "" : "tablesorter";

            if ($i == 0) {
                echo "<form name='Selecionar' method='post' action='$PHP_SELF'>\n";
                echo "<input type='hidden' name='btnacao' value=''>";


                echo "<input type='hidden' name='total_res' id='total_res' value='$totalreg'>";

                echo "<table width='700px' align='center' border='0' id='grid_list' cellspacing='0' cellpadding='2' class='tabela $newClass' style='padding:10px;'>\n";

                echo "<thead>";
                echo "<tr class='titulo_coluna'>";

                if ($login_fabrica == 24) {
                    echo "<th align='center' class='titulo_coluna' nowrap>Soma <input type='checkbox' onClick=\"somarExtratos('todos')\"></th>";
                }

                if ($login_fabrica == 20) {
                    echo "<th align='center' class='titulo_coluna' nowrap>Soma Extrato<br> por marca<input type='checkbox'id='checkAll' onclick='selecionaTodos();'></th>";
                    echo "<th align='center' class='titulo_coluna' nowrap>Aprovar Todos<br> Selecionados<input type='checkbox' id='checar' name='acaoTodas' value='Aprovar' onclick='selecionarExtratos();'></th>";

                }

                echo "<th align='center' class='titulo_coluna' nowrap style='width:85px;'>".traduz('CÛdigo')."</th>";
                if ($telecontrol_distrib) {
                    echo "<th align='center' class='titulo_coluna'>Lote/NF</th>";
                }
                echo "<th align='center' class='titulo_coluna' nowrap>".traduz('Nome do Posto')."</th>\n";
                if($login_fabrica == 183) { echo "<th align='center' class='titulo_coluna' nowrap style='width: 140px;'>".traduz('CÛdigo Fornecedor')."</th>\n";}
                if(!in_array($login_fabrica, [180,181,182])) { echo "<th align='center' class='titulo_coluna' nowrap style='width:25px;' >UF</th>\n";}

                if ($login_fabrica == 158) {echo "<th align='center' class='titulo_coluna'>Unidade de NegÛcio</th>";}
                if ($login_fabrica == 1) echo "<th align='center' class='titulo_coluna'>Credenciamento</th><th align='center' class='titulo_coluna' nowrap>Tipo</th>\n";
                echo ($login_fabrica == 1 OR $login_fabrica == 19) ? "<th align='center' class='titulo_coluna'>Protocolo</th>\n" : "<th align='center' class='titulo_coluna' nowrap style='width:85px;' >".traduz('Extrato')."</th>\n";

                if($login_fabrica == 86 OR $login_fabrica == 104){
                    if($login_fabrica == 104){
                        echo "<th align='center' class='titulo_coluna'>Empresa</th>";
                    }else{
                        echo "<th align='center' class='titulo_coluna'>Marca</th>";
                    }
                }

                if ($login_fabrica == 158) {
                  echo "<th align='center' class='titulo_coluna'>Tipo</th>";
                }

                echo "<th align='center' class='titulo_coluna' nowrap style='width:65px;'>".traduz('Data')."</th>\n";
                if($login_fabrica == 129){
                    echo "<th align='center' class='titulo_coluna' nowrap style='width:75px;'>Liberado</th>\n";
                }

                echo "<th align='center' class='titulo_coluna' nowrap style='width:70px;'>".traduz('Qtde OS')."</th>\n";

        		if($login_fabrica == 183){
        			echo "<th align='center' class='titulo_coluna'>Total MO</th>\n";
        			echo "<th align='center' class='titulo_coluna'>Total KM</th>\n";
        			echo "<th align='center' class='titulo_coluna'>Valor Adicional</th>\n";
        		}

                if ($login_fabrica == 1) {

                    echo "<th align='center' class='titulo_coluna'>Total PeÁa</th>\n";
                    echo "<th align='center' class='titulo_coluna'>Total MO</th>\n";
                    echo "<th align='center' class='titulo_coluna'>Total Avulso</th>\n";
                    echo "<th align='center' class='titulo_coluna'>Total Geral</th>\n";
                    echo "<th align='center' class='titulo_coluna'>Obs.</th>\n";

                } else {

                    //hd 39502
                    if ($login_fabrica == 20) {
                        echo "<th align='center' class='titulo_coluna' nowrap style='width:110px;'>Total cortesia</th>\n";
                        echo "<th align='center' class='titulo_coluna' nowrap style='width:105px;'>Total geral</th>\n";
                    } else {
                        echo "<th align='center' class='titulo_coluna' nowrap style='width:60px;'>".traduz('Total')."</th>\n";
                        if (in_array($login_fabrica, array(81,114,122,123,125,147,160)) or $replica_einhell){ 
                          echo "<th align='center' class='titulo_coluna' nowrap style='width:100px;'>Data/Hora Upload NF</th>\n";
                        }
                    }

                    if (in_array($login_fabrica, [178])) {
                      echo "<th align='center' class='titulo_coluna' nowrap style='width:105px;'>Status NF</th>\n";
                    }

                    if($telecontrol_distrib){
                        echo "<th align='center' class='titulo_coluna' nowrap style='width:100px;'>Data/Hora ConferÍncia</th>\n";
                    }

                    if ($login_fabrica == 6) {//hd 3471
                        echo "<th align='center' class='titulo_coluna'><acronym title='MÈdia de valor pago nos √∫ltimos 6 meses' style='cursor: help;'>MÈdia</th>\n";

                    }
                    // SONO - 04/09/206 exibir valor_liquido para intelbras //
                    if ($login_fabrica == 14 || $login_fabrica == 91) {
                        echo "<th align='center' class='titulo_coluna' nowrap>Total LÌquido</th>\n";
                    }
                }

                if ($login_fabrica == 20) {
                    echo "<th align='center' class='titulo_coluna' nowrap style='width:100px;'>N.F.<br />M. De Obra</th>\n";
                    echo "<th align='center' class='titulo_coluna' nowrap style='width:100px;'>N.F.<br />Remessa</th>\n";
                    echo "<th align='center' class='titulo_coluna' nowrap style='width:85px;'>Data<br />Coleta</th>\n";
                    echo "<th align='center' class='titulo_coluna' nowrap style='width:100px;'>Entrega<br />Transportadora</th>\n";
                }

                if ($login_fabrica == 14) {//HD 209349
                    echo "<th align='center' class='titulo_coluna'>N.F.<br />M. De Obra</th>\n";
                    echo "<th align='center' class='titulo_coluna'>Data<br />Envio NF</th>\n";
                    echo "<th align='center' class='titulo_coluna'>Data<br />Recebimento NF</th>\n";
                }

                if ($login_fabrica == 169) {//HD 209349
                    echo "<th align='center' class='titulo_coluna'>Pedido SAP</th>\n";
                }

		

                if($login_fabrica == 45 or $login_fabrica == 80) echo "<th align='center' class='titulo_coluna' nowrap>Nota Fiscal</th>";
                if(in_array($login_fabrica, array(101,151))) echo "<th align='center' class='titulo_coluna' nowrap>Nota Fiscal de ServiÁo</th>";
                if(in_array($login_fabrica, [152,180,181,182])) echo "<th align='center' class='titulo_coluna'>".traduz('RequisiÁ„o')."</th>";

                if($login_fabrica == 20) {
		    echo "<th align='center' class='titulo_coluna' nowrap style='width:110px;'>Auditado em</th>";
		}
                if($login_fabrica == 20) {
		    echo "<th align='center' class='titulo_coluna'>Auditor</th>";
		} else if (in_array($login_fabrica, [169,170])) { ?>
                    <th align="center" class="titulo_coluna" nowrap style="width:65px;"><label title="Data de Pagamento">Data Pagamento</label></th>
                <?php } else {
		    echo "<th align='center' class='titulo_coluna' nowrap style='width:65px;'><label title='Data de Pagamento'>". ((in_array($login_fabrica, array(157)) ? "Previs„o de Pagamento" : ($login_fabrica == 178) ? "Envio Financeiro" : traduz("Data Baixa"))) ."</th>\n";
		}

                if($login_fabrica == 148){
                  echo "<td>Tipo de Atendimento</td>";
                }

                if (in_array($login_fabrica,array(6,7,14, 15, 11 , 24, 25, 30, 35, 40, 43, 46, 47, 50, 51)) or ($login_fabrica > 51)) {
                    if ($recalculo_pendente == 't') {
                        echo "<th align='center' class='titulo_coluna'>*Aguardando recalculo</th>\n";
                    }
                    
                 	if (in_array($login_fabrica, array(81,114,122,123,125,147,160)) or $replica_einhell){

						echo "<th align='center' class='titulo_coluna' nowrap style='width:65px'> Ver NFe </th>";
					}
                  	
                  	if ($login_fabrica == 158) {
                    	$title_col_liberar = "Liberar/Aprovar";
                    }

                   	if (in_array($login_fabrica, array(81,114,122,123,125,147,160)) or $replica_einhell){ 
                  		echo "<th align='center' class='titulo_coluna' nowrap style='width:65px'> Liberar";
					}else{
					  echo "<th align='center' class='titulo_coluna'> $title_col_liberar";
					}
					
                    if ( ((!$telecontrol_distrib or $controle_distrib_telecontrol) && !in_array($login_fabrica,[123,152,180,181,182]) || (in_array($login_fabrica, [160]))) ) {
                        echo "<input type='checkbox' class='frm' name='marcar' value='tudo' title='Selecione ou desmarque todos' onClick='check(this.form.liberar);'>";
                    }
                    
                    echo "</th>\n";

                    if (in_array($login_fabrica, [152,169,170,180,181,182])) { ?>
                      <th align="center" class="titulo_coluna"><?=traduz('Status')?></th>
                    <?php }
                    if (in_array($login_fabrica, [169,170])) { 
                        echo "<th class='titulo_coluna' >Imprimir</th>";
                        echo "<th class='titulo_coluna' colspan='2'>AÁıes</th>";
                    }

                    if ($login_fabrica == 158) {
                        echo "<th class='titulo_coluna' >LGR</th>";
                    }

                      /* Liberar LGR ProvisÛrio */
                    if($login_fabrica == 50){
                        echo "<th align='center' class='titulo_coluna'>Liberar LGR ProvisÛrio</th>";
                    }

                    if ( in_array($login_fabrica, array(11,25,172)) ) echo "<th align='center' class='titulo_coluna' nowrap>Posto sem<br />email</th>\n";

                    if ((!empty($_POST['posto_codigo']) || !empty($_POST['posto_nome'])) && $login_fabrica == 151) {
                      echo "<th align='center' class='titulo_coluna'>Agrupar</th>";
                    }
                }

                if ($login_fabrica == 1) {
                    echo "<th class='titulo_coluna'>Tipo envio NF</th>";
                    echo "<th class='titulo_coluna'>Inibir</th>";
                    echo "<th align='center' class='titulo_coluna'>Acumular <input type='checkbox' class='frm' name='marcar' value='tudo' title='Selecione ou desmarque todos' onClick='check(this.form.acumular);'></th>\n";
                }
                if($login_fabrica == 35){
                    echo "<th align='center' class='titulo_coluna'>Aprovado</th>";
                }

                $nome_coluna_valores_adicionais = ($telecontrol_distrib) ? traduz("AÁıes") : traduz("Valores Adicionais ao Extrato");

                /*
                  Adicionar o ID da f·brica no IF abaixo & no IF da Linha: 5904
                  "para corrigir os Label's invertidos"
                */
                if (in_array($login_fabrica, [193,198,203])) {
                  if ($usaNotaFiscalServico) {
                    echo "<th align='center' class='titulo_coluna' colspan='3'>Notas Fiscais de ServiÁo</th>\n";
                  }
                }
                
                if (!in_array($login_fabrica, [157,183,190])) {
                  if (in_array($login_fabrica, array(81,85,14,122,123,125,147,160)) or $replica_einhell) {
                    echo "<th align='center' class='titulo_coluna' nowrap style='width:65px' colspan='2'>$nome_coluna_valores_adicionais</th>\n";
                  } else {
                  echo "<th align='center' class='titulo_coluna' colspan='".($login_fabrica == 151 ? 2 : 3)."'>$nome_coluna_valores_adicionais</th>\n";
                  }
                }

                if (!in_array($login_fabrica, [193,198,203])) {
                  if ($usaNotaFiscalServico) {
                    echo "<th align='center' class='titulo_coluna' colspan='3'>Notas Fiscais de ServiÁo</th>\n";
                  }
                }

                if($login_fabrica == 151) {                    
                  echo "<th align='center' class='titulo_coluna' nowrap style='width:65px' colspan='2'>Centro DistribuiÁ„o</th>\n";
                }

                if (in_array($login_fabrica, [15,50])) {
                    echo "<th align='center' class='titulo_coluna' >Previsao de Pagamento</th>";
                    echo "<th align='center' class='titulo_coluna'>Data Chegada</th>";
                }

                if ($login_fabrica == 45) {//HD 66773
                    echo "<th align='center' class='titulo_coluna'>Acumular</th>";
                }

                if ($login_fabrica == 24 ) {

                    echo "<th align='center' class='titulo_coluna'>
                                <span title=\"OpÁ„o para acumular v·lida apenas para extratos com valor de atÈ R$50,00.\">Acumular</span>
                                <input type='checkbox' class='frm' name='marcar' value='tudo' title='Selecione ou desmarque todos' onClick='check(this.form.acumular);'>
                            </th>";

                }

                // hd 12104
                if ($login_fabrica == 14) {
                    echo "<th align='center' class='titulo_coluna'>Liberar 10%</th>";
                }

                if (in_array($login_fabrica, [35,183,190])) {
                    echo "<th align='center' colspan='2' class='titulo_coluna'>AÁıes</th>";
                }

                if(in_array($login_fabrica,array(85))):
                ?>
                    <script type="text/javascript">
                        function checkPrintCheckBox(element){
                            if(element.checked){
                                $('input[type=checkbox][extrato].print').each(function(i,e){
					e.checked = true;
				});
                            }
                            else{
                                $('input[type=checkbox][extrato].print').each(function(i,e){
					e.checked = false;
				});
                            }
                        }
                    </script>
                    <th class='titulo_coluna'>Impress„o <input type="checkbox" onclick="checkPrintCheckBox($(this)[0])" /></th>
                <?php
                endif;


                if($login_fabrica == 30){
                ?>
                    <th align="center" class='titulo_coluna'>N∫ OC</th>
                    <th align="center" class='titulo_coluna'>Data Pagamento</th>
                    <th align="center" class='titulo_coluna'>Valor Pago</th>
                    <th align="center" class='titulo_coluna'>Nota Fiscal</th>
                    <th align="center" class='titulo_coluna'>Valor descontado do Encontro de Contas</th>
                    <th></th>
                <?php
                }

                echo "</tr>";
                echo "</thead>";
                echo "<tbody>";
            }

            $cor = "white";

            $sqlPaisPosto = "SELECT pais FROM tbl_posto WHERE posto = {$posto}";
            $resPaisPosto = pg_query($con, $sqlPaisPosto);

            $paisPosto = pg_fetch_result($resPaisPosto, 0, "pais");

            if (in_array($login_fabrica, [20]) && $paisPosto == "BR" && $extrato > 4289724) {

                /*$sqlValidaAnexos = "SELECT tbl_tdocs.obs
                                   FROM tbl_tdocs
                                   WHERE referencia_id = '{$extrato}'
                                   AND situacao = 'ativo'
                                   AND fabrica = {$login_fabrica}";
                $resValidaAnexos = pg_query($con, $sqlValidaAnexos);

                $anexosInseridos = [];
                while ($dadosTdocs = pg_fetch_object($resValidaAnexos)) {

                    $arrObs = json_decode($dadosTdocs->obs, true);

                    $anexosInseridos[] = $arrObs[0]["typeId"];

                }*/

                if (!anexoExtratoEnviadoBosch($extrato)) { 
                  $cor = "#fcf568";
                }

            }

            ##### LAN«AMENTO DE EXTRATO AVULSO - INÕCIO #####
            if (strlen($extrato) > 0) {
                $sql = "SELECT count(*) as existe
                        FROM   tbl_extrato_lancamento
                        WHERE  extrato = $extrato
                        and    fabrica = $login_fabrica";
                $res_avulso = pg_query($con,$sql);

                if (@pg_num_rows($res_avulso) > 0) {
                    if (@pg_fetch_result($res_avulso, 0, existe) > 0) $cor = "#FFE1E1";
                }

            }
            ##### LAN«AMENTO DE EXTRATO AVULSO - FIM #####

            //HD 237498: Marcando os extratos que possuem OS em interven√ßao de KM em aberto
            if (in_array($login_fabrica, $intervencao_km_extrato)) {
                $km_pendente = verifica_km_pendente_extrato($extrato);

                if ($km_pendente) {
                    $cor = "#FFCC99";
                }
            }

            if ($login_fabrica == 6) {//hd 3471
                $ssql = "SELECT sum(X.total) as total, count(total) as qtde
                        FROM (
                        select posto,
                        total
                        from tbl_extrato
                        where fabrica = $login_fabrica
                        and posto = $posto
                        and data_geracao < '$xdata_geracao'
                        order by extrato
                        desc limit 6) as X";
                $rres = pg_query($con,$ssql);
                if(pg_num_rows($rres)>0){
                    $total_acumulado = pg_fetch_result($rres,0,total);
                    $qtde = pg_fetch_result($rres,0,qtde);
                    if($qtde>0){
                        $total_acumulado = $total_acumulado/$qtde;
                        if($xtotal>round($total_acumulado*1.15)){//hd 3471
                            $cor = "#FFCC99";
                        }
                    }
                }
            }

            if($login_fabrica == 1){

                $sql_inibido = "SELECT baixado FROM tbl_extrato_extra WHERE extrato = {$extrato}";
                $res_inibido = pg_query($con, $sql_inibido);

                $baixado_inibido = pg_fetch_result($res_inibido, 0, "baixado");
                $cor = (strlen($baixado_inibido) > 0) ? "#FFFFB2" : $cor;
                $checked_inibido = (strlen($baixado_inibido) > 0) ? "checked" : "";

            }

            if ($login_fabrica == 24) {
              $hiddenLinha = "";
              if ($extrato_bloqueado == "t" && !$verifica_extrato_bloqueado) {
                $hiddenLinha = "hidden";
              } else if ($verifica_extrato_bloqueado) {
                $cor = "#ffcc00";
              }
            }

			if($valor_abaixo > 0 and (float)str_replace(",",".",$total) >= $valor_abaixo and $login_fabrica == 1) {
					$qtde_extratos--;
					continue;
			}

            echo "<tr class='linha_$extrato' style='background-color: $cor;' bgcolor='$cor' {$hiddenLinha}>\n";

            if ($login_fabrica == 24) {
                echo "<td align='center' nowrap><input type='checkbox' name='extrato_$i' rel='somatorio' value='$xtotal' onClick='somarExtratos()'></td>\n";
            }

            if ($login_fabrica == 20) {
                echo "<td align='center' nowrap><input type='checkbox' class='check extrato_calcula' name='extrato_$i' value='$extrato'></td>\n";

                echo "<td align='center' nowrap><input type='checkbox' class='check1' name='extrato__$i' value='$extrato'></td>\n";
                echo "<input type='hidden' name='aprovado_$i' value='$aprovado'>";
            }
            echo "<td align='left'>$codigo_posto</td>\n";

            if (strlen($extrato) > 0 and $telecontrol_distrib) {
                $distrib_lote = "";
                $data_conferencia = "";
                $sqllote = "SELECT tbl_distrib_lote_os.os, tbl_distrib_lote.lote, tbl_distrib_lote.distrib_lote, 
                            tbl_distrib_lote_os.nota_fiscal_mo
                            FROM tbl_distrib_lote_os
                            JOIN tbl_os_extra USING(os)
                            JOIN tbl_distrib_lote using(distrib_lote)
                        WHERE tbl_os_extra.extrato = $extrato";
                $reslote = pg_query($con,$sqllote);
                
                if(pg_num_rows($reslote) > 0){
                    $lote = trim(pg_fetch_result($reslote,0,lote));
                    $nota_fiscal_mo = trim(pg_fetch_result($reslote,0,nota_fiscal_mo));
                    $distrib_lote = pg_fetch_result($reslote, 0, 'distrib_lote');
                    echo "<td align='center' nowrap>$lote - $nota_fiscal_mo</td>\n";
                }else{
                    $sqllote = "SELECT tbl_distrib_lote.lote, tbl_distrib_lote.distrib_lote,
                                        tbl_extrato_lancamento.nota_fiscal_mo,
                                        tbl_extrato_lancamento.distrib_lote
                                FROM tbl_extrato_lancamento
                                JOIN tbl_distrib_lote USING(distrib_lote)
                            WHERE tbl_extrato_lancamento.extrato = $extrato";
                    $reslote = pg_query($con,$sqllote);
                    if(pg_num_rows($reslote) > 0){
                        $lote = trim(pg_fetch_result($reslote,0,lote));
                        $nota_fiscal_mo = trim(pg_fetch_result($reslote,0,nota_fiscal_mo));
                        $distrib_lote = pg_fetch_result($reslote, 0, 'distrib_lote');
                        echo "<td align='center' nowrap>$lote - $nota_fiscal_mo</td>\n";
                    }else{
                        echo "<td align='center' nowrap>&nbsp;</td>\n";
                    }
                }

                if(!empty($distrib_lote)){
                  $sqlConferencia = "SELECT data_conferencia FROM tbl_distrib_lote_posto where distrib_lote = $distrib_lote and posto = $posto AND nf_mobra = '$nota_fiscal_mo'";
                  $resConferencia = pg_query($con, $sqlConferencia);
                  if(pg_num_rows($resConferencia)>0){
                     $data_conferencia = mostra_data(substr(pg_fetch_result($resConferencia, 0, 'data_conferencia'), 0 ,16));
                  }
                }
            }

            echo "<td align='left' nowrap>".substr($nome,0,20)."</td>\n";
            if($login_fabrica == 183){echo "<td align='center' nowrap>".substr($cod_fornecedor,0,20)."</td>\n";}
            if (!in_array($login_fabrica,[180,181,182])) {echo "<td align='center' nowrap>".$posto_estado."</td>\n";}
            if ($login_fabrica == 158) { echo "<td nowrap>$unidadeNegocio</td>\n";}
            if ($login_fabrica == 1) echo "<td>$credenciamento</td><td align='center' nowrap>$tipo_posto</td>\n";
            if($login_fabrica == 20 ){echo "<td align='center'><a href='extrato_os_aprova";
            }else{
                echo "<td align='center' ";
                if($bloqueado == "t" and $login_fabrica == 1){
                    echo " bgcolor='#FF9E5E' ";
                }
                if ($login_fabrica == 178){
                    echo "><a href='extrato_consulta_os_new";
                }else{
                    echo "><a href='extrato_consulta_os";
                }
            }
            if ($login_fabrica == 14) echo "_intelbras";
            echo ".php?extrato=$extrato&data_inicial=$data_inicial&data_final=$data_final&cnpj=$xposto_codigo&razao=$posto_nome' target='_blank'>";
            echo ($login_fabrica == 1 OR $login_fabrica == 19 ) ? $protocolo : $extrato;
            echo "</a></td>\n";

            if ($login_fabrica == 158) {
                echo "<td align='center'>";
                echo $protocolo;
                echo    "</td>";
            }

            if($login_fabrica == 86 OR $login_fabrica == 104){
                echo "<td align='center'>";
                echo mostraMarcaExtrato($extrato);
                echo    "</td>";
            }

            //IGOR - HD 6924 04/03/2008
            $cor_estoque_menor = "";
            if ($estoque_menor_20 == "t" and $login_fabrica == 1) {
                $cor_estoque_menor = " bgcolor='#CCFF66' ";
            }

            if ($login_fabrica == 85) { 

              $dataAmericano = explode("/", $data_geracao);
              $dataAmericano = array_reverse($dataAmericano);
              $dataAmericano = implode("", $dataAmericano); 
            }

            if ($login_fabrica <> 183){
                echo "<td align='left' $cor_estoque_menor><span hidden>$dataAmericano</span>$data_geracao</td>\n";
            }
            if($login_fabrica == 129){
              echo "<td align='left' $cor_estoque_menor>$liberado</td>\n";
            }
            if ($login_fabrica <> 183){
                echo "<td align='center' nowrap>".$qtde_os_ex."</td>\n";
            }
	    
	    if(in_array($login_fabrica,array(35))){
		$total_os += $qtde_os_ex;
	    }
            // echo "<td align='center' title='Clique aqui para ver a quantidade de OS'><div id='qtde_os_$i'><a href=\"javascript:conta_os($extrato,'qtde_os_$i','".($i+1)."');\" id='conta_os_$i'>VER</a></div><input type='hidden' name='extrato_tudo_$i' id='extrato_tudo_$i' value='$extrato'></td>\n";
            //--== FIM - QTDE de OS no extrato =========================================================

	    if($login_fabrica == 183){
            echo "<td align='left'>$data_geracao</td>\n";
            echo "<td align='center' nowrap>".$qtde_os_ex."</td>\n";
    		echo "<td align='right'>$mao_de_obra</td>\n";
    		echo "<td align='right' nowrap> <a href='gerar_nota_debito.php?extrato={$extrato}' target='_blank'>" . $deslocamento . "</a></td>\n";
            echo "<td align='right' nowrap> $valor_adicional</td>\n";
	    }

            if ($login_fabrica == 1) {
                $sql =  "SELECT SUM(tbl_os.pecas)       AS total_pecas     ,
                                SUM(tbl_os.mao_de_obra) AS total_maodeobra ,
                                tbl_extrato.avulso      AS total_avulso
                        FROM tbl_os
                        JOIN tbl_os_extra USING (os)
                        JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato AND tbl_os_extra.i_fabrica = $login_fabrica
                        WHERE tbl_os_extra.extrato = $extrato
                        GROUP BY tbl_extrato.avulso;";
                $resT = pg_query($con,$sql);

                if (pg_num_rows($resT) == 1) {
                    echo "<td align='right' nowrap> " . number_format(pg_fetch_result($resT,0,total_pecas),2,',','.') . "</td>\n";
                    echo "<td align='right' nowrap> " . $mao_de_obra . "</td>\n";
                    echo "<td align='right' nowrap> " . number_format(pg_fetch_result($resT,0,total_avulso),2,',','.') . "</td>\n";
                }else{
                    echo "<td>&nbsp;$pecas</td>\n";
                    echo "<td>&nbsp;$mao_de_obra</td>\n";
                    echo "<td>&nbsp;$avulso</td>\n";
                }
            }

            //hd 39502
            if ($login_fabrica==20) {
                echo "<td align='right' nowrap> $total_cortesia</td>\n";
            }
			

            //TOTAL EXTRATO
            if (in_array($login_fabrica, array(30,85))) {
                //$raw_total += $taxa_entrega;

				$total = number_format($raw_total, 2, ',', '.');
            }

            if(in_array($login_fabrica, array(190))){
     
        	    $condExR = "tbl_os_extra.extrato = $extrato";
        	    if ($protocolo == "extrato_recebimento") {
        	   	   $condExR = "tbl_os_extra.extrato_recebimento = $extrato";
                }
                $sqlCont = "SELECT DISTINCT tbl_contrato_os.contrato, tbl_contrato.campo_extra
                            FROM tbl_os
                            JOIN tbl_os_extra on tbl_os_extra.os=tbl_os.os
                            JOIN tbl_contrato_os on tbl_contrato_os.os=tbl_os_extra.os
                            JOIN tbl_contrato on tbl_contrato_os.contrato=tbl_contrato.contrato AND tbl_contrato.fabrica = $login_fabrica
                           WHERE {$condExR}
                             AND tbl_os.fabrica = $login_fabrica";

                $resCont = pg_query($con,$sqlCont);
                if (pg_num_rows($resCont) > 0) {
                    foreach (pg_fetch_all($resCont) as $key => $value) {
                        $xcampoExtra = json_decode($value['campo_extra'],1);
                        if (isset($xcampoExtra["valor_mao_obra_fixa"])) {
                            $valor_mao_obra_fixa += $xcampoExtra["valor_mao_obra_fixa"];
                        } else {
                            $valor_mao_obra_fixa += 0;
                        }
                    }
                 }
                $total = number_format(($raw_total+$valor_mao_obra_fixa),2,",",".");
        	}

            echo "<td align='right' nowrap> $total</td>\n";

            if (in_array($login_fabrica, [152,180,181,182])) {
                echo "<td align='center'>";
                
                if( empty($autorizacao_pagto) OR $autorizacao_pagto == 'undefined' ){
                  echo "<button type='button' onclick='liberar({$extrato}, {$autorizacao_pagto})' style='font-size: 10px'> ".traduz('Adicionar RequisiÁ„o')." </button>";
                }else{
                  echo $autorizacao_pagto;
                }

                echo    "</td>";
            }

              if($telecontrol_distrib || in_array($login_fabrica, array(81,114,122,123,125,147,160))){
                $data_upload_nf = "";
                $nota_fiscal_servico = $s3_extrato->getObjectList($extrato."-nota_fiscal_servico.");
                if($nf_recebida == 't' AND strlen($nf_recebida)>0){
                  $data_upload_nf = $s3_extrato->getFileInfo($nota_fiscal_servico[0]);
                  $data_upload_nf = $data_upload_nf['LastModified'];
                }
                  echo "<td id='upload_<?=$extrato?>' align='right' nowrap>$data_upload_nf</td>\n";
              }

              if(in_array($login_fabrica, array(178))){
                $nota_fiscal_servico = $s3_extrato->getObjectList($extrato."-nota_fiscal_servico.");

                $statusNf = (count($nota_fiscal_servico) > 0) ? "<span style='color: darkgreen;'>NF enviada</span>" : "<span style='color: darkred;'>NF pendente</span>";

                echo "<td  align='center' nowrap><strong>{$statusNf}</strong></td>\n";
              }

              if($telecontrol_distrib){
                  echo "<td id='upload_<?=$extrato?>' align='center' nowrap> $data_conferencia</td>\n";
              }

            if ($login_fabrica == 6) {//hd 3471
                echo "<td align='center' nowrap>".number_format($total_acumulado,2,',','.') . "</td>";
            }

            // SONO - 04/09/206 exibir valor_liquido para intelbras //
            if ($login_fabrica == 14 || $login_fabrica == 91 ) {
                echo "<td align='right' nowrap> $valor_liquido</td>\n";
            }

            if ($login_fabrica == 1) echo "<td><a href=\"javascript: AbrirJanelaObs('$extrato');\">OBS.</a></td>\n";

            if ($login_fabrica == 20 || $login_fabrica == 14) {
                echo "<td align='center'><INPUT TYPE='text' NAME='nota_fiscal_mao_de_obra_$i' id='nota_fiscal_mao_de_obra_$i' value='$nota_fiscal_mao_de_obra' size='8' maxlength='16'"; if (strlen($aprovado) > 0 && $login_fabrica != 14) echo " readonly"; echo "></td>";
                if ($login_fabrica == 20) {
                    echo "<td align='center'><INPUT TYPE='text' NAME='nota_fiscal_devolucao_$i' id='nota_fiscal_devolucao_$i' value='$nota_fiscal_devolucao' size='8' maxlength='16'"; if (strlen($aprovado)>0) echo " readonly"; echo "></td>";
                    echo "<td align='center'>$data_coleta</td>"; #HD 219942
                } else {
                    echo "<INPUT TYPE='hidden' NAME='nota_fiscal_devolucao_$i' id='nota_fiscal_devolucao_$i' value='$nota_fiscal_devolucao' size='8' maxlength='16'"; if (strlen($aprovado)>0) echo " readonly"; echo ">";
                }
                if ($login_fabrica == 14) {
                    echo "<td align='center'>$emissao_mao_de_obra</td>"; #HD 209349
                }
                echo "<td align='center'><INPUT size='12' maxlength='10' TYPE='text' NAME='data_entrega_transportadora_$i' class='data_entrega_transportadora' id='data_entrega_transportadora_$i' rel='data2' value='$data_entrega_transportadora'"; if (strlen($aprovado) > 0 && $login_fabrica != 14) echo " disabled"; echo "></td>";
            }

            if ($login_fabrica == 45 or $login_fabrica == 80) echo "<td align='center'>$nf_autorizacao</td>";

            if(in_array($login_fabrica, array(101,151))){

                if(strlen($nf_autorizacao) == 0){
                    $nf_autorizacao = "<a href='../nota_servico_extrato.php?area_admin=true&extrato=$extrato' rel='shadowbox; width= 550; height= 350;'>Informar Nota de ServiÁo</a>";
                }else{
                    $nf_autorizacao = "<a href='../nota_servico_extrato.php?area_admin=true&extrato=$extrato' rel='shadowbox; width= 500; height= 350;'>$nf_autorizacao</a>";
                }

                echo "<td align='center' class='nf-servico-$extrato' nowrap> $nf_autorizacao </td>\n";

            }

		if(in_array($login_fabrica, [169,170])) {

                echo "<td align='center'> $autorizacao_pagto</td>\n";

		}

		if ($login_fabrica == 20) {
			echo "<td align='left'>$aprovado</td>";
		}else if($login_fabrica == 134){
			echo "<td align='left'>".$baixa_extrato."</td>";
		}else {

      if ($login_fabrica == 85) { 

        $baixadoAmericano = explode("/", $baixado);
        $baixadoAmericano = array_reverse($baixadoAmericano);
        $baixadoAmericano = implode("", $baixadoAmericano); 
      }

			echo "<td align='left'><span hidden>$baixadoAmericano</span> $baixado</td>\n";
		}


            //HD 205958: Um extrato pode ser modificado atÈ o momento que for APROVADO pelo admin. ApÛs aprovado
            //           n„o poder· mais ser modificado em hipÛtese alguma. Acertos dever„o ser feitos com lan√ßamento
            //           de extrato avulso. Verifique as regras definidas neste HD antes de fazer exceÁıes para as f·bricas
            //           SER¡ LIBERADO AOS POUCOS, POIS OS PROGRAMAS N√O EST¡O PARAMETRIZADOS

            if($login_fabrica == 1){
                $obs = verificaTipoGeracao($extrato);


                $dadosGeracao = json_decode($obs);

                echo "<td align='left' nowrap>";

                 if(isset($dadosGeracao->tipo_de_envio) && strlen($dadosGeracao->tipo_de_envio) > 0){

                     echo "{$dadosGeracao->tipo_de_envio}";
                 } else {

                     $sql = "SELECT tipo_envio_nf
                    FROM tbl_tipo_gera_extrato
                    WHERE fabrica = $login_fabrica
                    AND posto = $posto";
                     $res2 = pg_query($con, $sql);
                     if(pg_num_rows($res2) > 0){
                         echo str_replace("_"," ",pg_fetch_result($res2, 0, 'tipo_envio_nf'));
                     }
                 }
                echo "</td>";

                echo "<td nowrap> <input type='checkbox' value='{$extrato}' id='inibir_extrato_{$extrato}' onClick='inibir_extrato({$extrato})' {$checked_inibido} /> Inibido </td>";

            }

            if($login_fabrica == 45 && strlen($aprovado) > 0 && strlen($baixado) > 0){
                echo "<td colspan='4'>Extrato j· Aprovado</td>";
            }else{

                if($login_fabrica == 148){
                  $sql_tipo_atendimento_agrupado = "SELECT tbl_tipo_atendimento.descricao as tipo_atendimento_agrupado_descricao FROM tbl_extrato_agrupado join tbl_tipo_atendimento on tbl_tipo_atendimento.tipo_atendimento = tbl_extrato_agrupado.codigo::int where tbl_extrato_agrupado.extrato = $extrato";
                  $res_tipo_atendimento_agrupado = pg_query($con, $sql_tipo_atendimento_agrupado);
                  if(pg_num_rows($res_tipo_atendimento_agrupado)>0){
                    $tipo_atendimento_agrupado_descricao = pg_fetch_result($res_tipo_atendimento_agrupado, 0, 'tipo_atendimento_agrupado_descricao');
                  }else{
                    $tipo_atendimento_agrupado_descricao = "";
                  }
                  echo "<td align='center'>$tipo_atendimento_agrupado_descricao</td>";
                }
                if (in_array($login_fabrica, $fabricas_acerto_extrato)) {
                    echo "<td align='center' nowrap>";
                    //Extrato n„o aprovado, pode aprovar se j· estiver liberado
                    if (strlen($aprovado) == 0) {
                        if (strlen($liberado) == 0) {
                            if ($recalculo_pendente == 't') {
                                echo "*Aguardando recalculo\n";
                            } else {
                                echo "<a href=\"javascript:window.location = '$PHP_SELF?liberar=$extrato&posto=$posto&data_inicial=$data_inicial&data_final=$data_final&cnpj=$xposto_codigo&razao=$posto_nome&msg_aviso='+document.Selecionar.msg_aviso.value \">Liberar</a>";
    //                          echo " <input type='checkbox' class='frm' name='liberar_$i' id='liberar' value='$extrato'>";
                            }
                        } else {
                            if($login_fabrica == 45 && strlen($baixado) == 0){
                                echo "<a href='$PHP_SELF?aprovar=$extrato&posto=$posto&data_inicial=$data_inicial&data_final=$data_final&cnpj=$xposto_codigo&razao=$posto_nome'><img src='imagens_admin/btn_aprovar_azul.gif' id='img_aprovar_$i' ALT='Aprovar o extrato'></a>";
                            }else{
                                echo "<a href='$PHP_SELF?aprovar=$extrato&posto=$posto&data_inicial=$data_inicial&data_final=$data_final&cnpj=$xposto_codigo&razao=$posto_nome'><img src='imagens_admin/btn_aprovar_azul.gif' id='img_aprovar_$i' ALT='Aprovar o extrato'></a>";
                            }
                        }
                    } else {//Extrato j· aprovado, n„o pode mais modificar
                        if($login_fabrica == 45 && strlen($baixado) == 0){
                            if($recalculo_pendente == 't'){
                                echo "*Aguardando recalculo\n";
                            }else{
                                echo "<a href=\"javascript:window.location = '$PHP_SELF?liberar=$extrato&msg_aviso='+document.Selecionar.msg_aviso.value \">Liberar</a>";
                                echo " <input type='checkbox' class='frm' name='liberar_$i' id='liberar' value='$extrato'>";

                            }
                        }
                    }
                    echo "</td>\n";
                } elseif (in_array($login_fabrica,array(6,7,14,15,11,24,25,35,40,42,50,43,51,46,47,74,59,30,45,115,116,117)) or ($login_fabrica > 51) ) {//HD 205958: Rotina antiga
                    echo "<td align='center' nowrap>";
                    if($telecontrol_distrib and !$controle_distrib_telecontrol ){
                      if($nf_recebida == 't' AND strlen($nf_recebida)>0){
                            $nota_fiscal_servico = $s3_extrato->getObjectList($extrato."-nota_fiscal_servico.");
                      }else{
                          $nota_fiscal_servico = null;
                      }
                      if(count($nota_fiscal_servico) > 0){
                          $nota_fiscal_servico = basename($nota_fiscal_servico[0]);
                          $nota_fiscal_servico = $s3_extrato->getLink($nota_fiscal_servico);
                          ?><button type='button' id="visualizar_<?=$extrato?>" onclick="window.location.href='<?=$nota_fiscal_servico?>'"> Visualizar NFe </button><?php;
                      }

                    echo "</td>";
                    echo "<td align='center' nowrap>";

					  if((($login_fabrica == 160) || ($replica_einhell)) and strlen($liberado) == 0) {
						  $rExtrato     = $_REQUEST['extrato'];
							$rDataInicial = $_REQUEST['data_inicial'];
							$rDataFinal   = $_REQUEST['data_final'];
							$rPostoCodigo = $_REQUEST['posto_codigo'];
							$rPostoNome   = $_REQUEST['posto_nome'];
							echo "<a class='' href=\"javascript:window.location = '$PHP_SELF?".$parametro."liberar=$extrato&extrato={$rExtrato}&data_inicial={$rDataInicial}&data_final={$rDataFinal}&posto_codigo={$rPostoCodigo}&posto_nome={$rPostoNome}&btnacao=filtrar&msg_aviso='+document.Selecionar.msg_aviso.value \">Liberar</a>";
                             echo " <input type='checkbox' class='frm' data-posicao='$i' name='liberar_$i' id='liberar' value='$extrato'>";
					  }
					
					echo "</td>";

                    }else{
                      if (strlen($liberado) == 0) {
                          if($recalculo_pendente == 't'){
                              echo "*Aguardando recalculo\n";
                          }else{

                    				if($login_fabrica == 158 and strlen($aprovado) == 0){
                    					$title_link = "Aprovar";
                    					$parametro = "aprovacao=t&";
                    				}else{
                    					$title_link = "Liberar";
                    					$parametro = "";
                    				}
                            /*if($login_fabrica == 152){ ?>
                              <button type='button' onclick="window.location.href = '<?php echo "$PHP_SELF?".$parametro."liberar=$extrato"; ?>' ">Aprovar</button>
                            <?php
                            }else{*/
                            if (!in_array($login_fabrica, [152,180,181,182])) { 
                              if (isset($novaTelaOs)) {
                                $rExtrato     = $_REQUEST['extrato'];
                                $rDataInicial = $_REQUEST['data_inicial'];
                                $rDataFinal   = $_REQUEST['data_final'];
                                $rPostoCodigo = $_REQUEST['posto_codigo'];
                                $rPostoNome   = $_REQUEST['posto_nome'];
                                echo "<a class='' href=\"javascript:window.location = '$PHP_SELF?".$parametro."liberar=$extrato&extrato={$rExtrato}&data_inicial={$rDataInicial}&data_final={$rDataFinal}&posto_codigo={$rPostoCodigo}&posto_nome={$rPostoNome}&btnacao=filtrar&msg_aviso='+document.Selecionar.msg_aviso.value \">$title_link</a>";
                              } else {
                                echo "<a class='' href=\"javascript:window.location = '$PHP_SELF?".$parametro."liberar=$extrato&msg_aviso='+document.Selecionar.msg_aviso.value \">$title_link</a>";    
                              }
                              
                             echo " <input type='checkbox' class='frm' data-posicao='$i' name='liberar_$i' id='liberar' value='$extrato'>";
                          }



                          if($login_fabrica == 158){
                            echo "<input type='hidden' name='extrato_aprovado_$i' value='$aprovado'>";
                          }
                        }
                      }
                    }
                    if($login_fabrica == 35){
                        echo "<td>$aprovado</td>";
                    }

                    if ($verifica_extrato_bloqueado && in_array($login_fabrica, [24])) { ?>
                      <button type="button" class="btn btn-desbloquear-extrato" data-extrato="<?= $extrato ?>" style="color: white;background-color: darkgreen;cursor: pointer;height: 30px;border: solid 1px black;border-radius: 5px;">Desbloquear</button>
                    <?php 
                    }

                    if(in_array($login_fabrica, [152,180,181,182]) AND !empty($autorizacao_pagto) AND $autorizacao_pagto != 'undefined' AND empty($liberado) ){
                      echo "<a  id='btn_confirmar_liberacao' 
                                data-extrato='{$extrato}' 
                                style='cursor: pointer; color: #0000FF; font-size: 15px;' 
                                onclick='onBeforeLiberacao({$autorizacao_pagto}, {$extrato});'/>Liberar</a>"; 
                    }

                    echo "</td>\n";

                    if ((!empty($_POST['posto_codigo']) || !empty($_POST['posto_nome'])) && $login_fabrica == 151) {
                      echo "<td align='center' nowrap>";
                      $sql_agrupado = "SELECT codigo FROM tbl_extrato_agrupado WHERE extrato = $extrato";
                      $res_agrupado = pg_query($con, $sql_agrupado);
                      if (pg_num_rows($res_agrupado) > 0) {
                        echo pg_fetch_result($res_agrupado, 0, 'codigo');
                      } else {
                        if (exibeAgrupar($extrato)) {
                          echo "<input type='checkbox' class='frm' data-posicao='$i' name='agrupar_$i' id='agrupar' value='$extrato'>";
                        }
                      }
                      echo "</td>";
                    }

                    if(in_array($login_fabrica, array(152,180,181,182))){

                      $obs_mostra = "";

                      switch($observacao_extrato_status){
                        case "Pendente de AprovaÁ„o":
                            $obs_mostra = $observacao_extrato_status;
                        break;
                        case "Aguardando Nota Fiscal do Posto":
                        case "Aguardando Envio da Nota Fiscal":
                          $sql_anexo = "SELECT tdocs
                                        FROM tbl_tdocs
                                        WHERE referencia_id = $extrato
                                        AND contexto = 'extrato'
                                        AND situacao = 'ativo'
                                        AND fabrica = $login_fabrica
                                        LIMIT 1 ";
                          $res_anexo = pg_query($con, $sql_anexo);
                          if (pg_num_rows($res_anexo) == 0) {
                            $obs_mostra = "Aguardando Nota Fiscal do Posto";
                          } else {
                            $obs_mostra = "Aguardando AprovaÁ„o de Nota Fiscal";
                          }
                        break;
                        case "Aguardando AprovaÁ„o de Nota Fiscal":
                           $sql_anexo = "SELECT tdocs
                                        FROM tbl_tdocs
                                        WHERE referencia_id = $extrato
                                        AND contexto = 'extrato'
                                        AND situacao = 'ativo'
                                        AND fabrica = $login_fabrica
                                        LIMIT 1 ";
                          $res_anexo = pg_query($con, $sql_anexo);
                          if (pg_num_rows($res_anexo) > 0) {
                            $obs_mostra = $observacao_extrato_status;
                          } else {
                            $obs_mostra = "Aguardando Nota Fiscal do Posto";
                          } 
                        break;
                        case "Aguardando Encerramento":
                        case "Nota Fiscal Aprovada":
                            $obs_mostra = "Aguardando Encerramento";
                        break;
                        case "Encerramento":
                            $obs_mostra = $observacao_extrato_status;
                        break;
                      }

                      echo "<td align='center'>".$obs_mostra."</td>";
                    }

                    /* Liberar LGR ProvisÛrio */
                    if($login_fabrica == 50){

                      $admin_libera_pendencia   = trim(pg_fetch_result($res, $i, "admin_libera_pendencia"));
                      $data_libera_pendencia    = trim(pg_fetch_result($res, $i, "data_libera_pendencia"));

                      $checked_lgr_provisorio = (strlen($admin_libera_pendencia) > 0 && strlen($data_libera_pendencia) > 0) ? "checked" : "";

                      /* $sql_extrato_lgr = "SELECT extrato FROM tbl_extrato_lgr WHERE extrato = {$extrato} AND (qtde_nf ISNULL OR qtde_nf = 0)";
                      $res_extrato_lgr = pg_query($con, $sql_extrato_lgr);

                      if(pg_num_rows($res_extrato_lgr) > 0){
                        $input_check = " <input type='checkbox' value='{$extrato}' id='extrato_lgr_{$extrato}' onclick='liberar_lrg_provisorio(\"{$extrato}\")' {$checked_lgr_provisorio} /> Liberar";
                      }else{
                        $input_check = "";
                      } */

                      echo "
                      <td align='center'>
                        <input type='checkbox' value='{$extrato}' id='extrato_lgr_{$extrato}' onclick='liberar_lrg_provisorio(\"{$extrato}\")' {$checked_lgr_provisorio} /> Liberar
                      </td>";

                    }

                }

                if ($usaNotaFiscalServico) {

                  $sqlTdocsNfe = "SELECT obs FROM tbl_tdocs
                                  WHERE referencia_id = '{$extrato}'
                                  AND contexto = 'extrato'
                                  AND situacao = 'ativo'
                                  AND fabrica = {$login_fabrica}";
                  $resTdocsNfe = pg_query($con, $sqlTdocsNfe);

                  $tiposInseridos = [];
                  while ($dadosTdocs = pg_fetch_object($resTdocsNfe)) {

                    $arrObs = json_decode($dadosTdocs->obs, true);

                    $tiposInseridos[] = $arrObs[0]["typeId"];

                  }

                  if(in_array('nfe_servico', $tiposInseridos) || in_array('boleto', $tiposInseridos)) {

                      if (in_array($login_fabrica, [152,180,181,182])) {
                          $sqlNfeVisualizada = "SELECT ultima_obs.obs,
                                                     TO_CHAR(ultima_obs.data, 'DD/MM/YYYY HH24:MI') as data_conferencia
                                                FROM (
                                                    SELECT obs, data
                                                    FROM tbl_extrato_status 
                                                    WHERE fabrica = {$login_fabrica}
                                                    AND extrato = {$extrato}
                                                    ORDER BY data DESC
                                                    LIMIT 1
                                                ) ultima_obs
                                                WHERE ultima_obs.obs IN ('Nota Fiscal Aprovada', 'Pendente de AprovaÁ„o', 'Encerramento', 'Aguardando Encerramento')";
                      } else {
                          $sqlNfeVisualizada = "SELECT ultima_obs.obs,
                                                     TO_CHAR(ultima_obs.data, 'DD/MM/YYYY HH24:MI') as data_conferencia
                                                FROM (
                                                    SELECT obs, data
                                                    FROM tbl_extrato_status 
                                                    WHERE fabrica = {$login_fabrica}
                                                    AND extrato = {$extrato}
                                                    ORDER BY data DESC
                                                    LIMIT 1
                                                ) ultima_obs
                                                WHERE ultima_obs.obs = 'Nota Fiscal Aprovada'";
                      }

                      $resNfeVisualizada = pg_query($con, $sqlNfeVisualizada);

                      if (pg_num_rows($resNfeVisualizada) == 0) { ?>
                          <td align="center">
                            <button type='button' class="aprovar_nfe" data-link="<?=$nota_fiscal_servico?>" data-extrato="<?= $extrato ?>">Aprovar NFe</button>
                          </td>
                          <td align="center">
                            <button  type='button' class="reprova_nfe" data-extrato="<?= $extrato ?>"> Reprovar NFe </button>
                          </td>
                          <td align="center">
                          <?php
                          } else { ?>
                            <td align="center" style="background-color: #b3ffe0 !important;color: darkgreen;padding: 7px;border-radius: 5px;" colspan="3">
                              <strong>NFe conferida: <?= pg_fetch_result($resNfeVisualizada, 0, 'data_conferencia') ?></strong><BR />
                          <?php
                          } ?>
                        
                          <button type='button' class="visualizar_nfe" data-extrato="<?= $extrato ?>" id="visualizar_<?=$extrato?>"> Visualizar NFe </button>
                        </td>
                      
                      <?php
                  } else {

                      $sqlNfeServico = "SELECT obs, data
                                        FROM tbl_extrato_status 
                                        WHERE fabrica = {$login_fabrica}
                                        AND extrato = {$extrato}
                                        AND obs ILIKE 'Nota Fiscal Reprovada%'
                                        ORDER BY data DESC
                                        LIMIT 1
                                       ";
                      $resNfeServico = pg_query($con, $sqlNfeServico);

                      $descStatusNfe = "<span style='color: darkgreen;'><strong>Aguardando posto Enviar NFe</strong></span>";

                      if (pg_num_rows($resNfeServico) > 0) {
                        if($login_fabrica == 91){
                            $descricao_obs = pg_fetch_result($resNfeServico, 0, 'obs');
                            $obs_desc = explode('.', $descricao_obs);
                            $descStatusNfe = "<strong><span style='color: darkred;'>".$obs_desc[0].".</span>".$obs_desc[1]."<br /> Aguardando o posto enviar uma nova NFe.</strong>";
                        }else{
                            $descStatusNfe = "<span style='color: darkred;'><strong>".pg_fetch_result($resNfeServico, 0, 'obs')."<br /> Aguardando o posto enviar uma nova NFe.</strong></span>";
                        }
                      }

                    echo "<td colspan='3' align='center'>{$descStatusNfe}</td>";
                  }
                }
                
                if ( in_array($login_fabrica, array(11,25,172)) ) {
                    echo "<td align='center' nowrap>";
                    if (strlen($email) == 0) {?>
                        <center>
                        <input type='button' value='Imprimir' onclick="javascript: window.open('extrato_consulta_os_print.php?extrato=<? echo $extrato; ?>','printextrato','toolbar=no,location=no,directories=no,status=no,scrollbars=yes,menubar=yes,resizable=yes,width=700,height=480')" ALT='Imprimir' border='0' style='cursor:pointer;' /><?php
                    } else {
                        echo "&nbsp;";
                    }
                    echo "</td>\n";
                }

                if ($login_fabrica == 24) {
                    echo "<td align='center' nowrap>";
                    if (strlen($email) == 0) {?>
                        <center>
                        <input type='button' value='Imprimir' onclick="javascript: window.open('extrato_consulta_os_print.php?extrato=<? echo $extrato; ?>','printextrato','toolbar=no,location=no,directories=no,status=no,scrollbars=yes,menubar=yes,resizable=yes,width=700,height=480')" ALT='Imprimir' border='0' style='cursor:pointer;' /><?php
                    } else {
                        echo "&nbsp;";
                    }
                    echo "</td>\n";
                }

                if ($login_fabrica == 20) {
		    echo "<td nowrap>$auditor</td>";
		} else if (in_array($login_fabrica, [169,170])) {
                    $statusExtrato = '';
                    if (!empty($baixado)) {
                        $statusExtrato = 'Pagamento Efetivado';
                    } else if ($bloqueado == 't') {
                        $statusExtrato = 'Pagamento Bloqueado';
                    } else if (!empty($data_entrega_financeiro)) {
                        $statusExtrato = 'Nota Aprovada';
                    } else if ($nf_recebida == 't') {
                        $statusExtrato = 'Nota Emitida';
                    } else {
                        $statusExtrato = 'Liberado';
                    }

                    echo "<td align='center' nowrap>";
                    fecho($statusExtrato, $con, $cook_idioma);
                    echo "</td>\n";

                    echo "<td align='center' nowrap>";
                        if ($nf_recebida == 't') {
                            unset($amazonTC, $anexos, $types);
                            $amazonTC = new TDocs($con, $login_fabrica);
                            $amazonTC->setContext("extrato", "nf_autorizacao");
                            $anexo = array();

                            $anexo["nome"] = "nf_autorizacao_{$extrato}_{$login_fabrica}_nota_fiscal_pdf";
							$anexo["url"] = $amazonTC->getDocumentsByName($anexo["nome"], null, $extrato)->url;
                            if(empty($anexo["url"])) $anexo["url"] = $amazonTC->getDocumentsByRef($extrato)->url;
                            if (strlen($anexo["url"]) > 0) { 
                            echo "<a  target='_blank' href='".$anexo['url']."' title='Imprimir' alt='Imprimir'><img title='Imprimir' alt='Imprimir' width='30' src='imagens/icone_pdf.jpg' /></a>";
                            } 

                        }
                    echo "</td>\n";


                    $sqlValidaNF = "SELECT nf_recebida 
                    FROM tbl_extrato 
                    JOIN tbl_extrato_pagamento USING(extrato) 
                    WHERE extrato = {$extrato} 
                    AND fabrica = {$login_fabrica} 
                    AND nf_recebida IS TRUE 
                    AND data_entrega_financeiro IS NULL;";
                    $resValidaNF = pg_query($con, $sqlValidaNF);
                    if (pg_num_rows($resValidaNF) > 0) {
                        echo '
                            <td align="center"><button type="button" onclick="aprovarNF('.$extrato.');" style="cursor:pointer;" alt="Aprovar NF" id="btnAprovarNf">Aprovar NF</button></td>
                            <td align="center"><button type="button" onclick="reprovarNF('.$extrato.');" style="cursor:pointer;" alt="Reprovar NF" id="btnReprovarNf">Reprovar NF</button></td>
                        ';
                    } else {
                        echo '
                            <td align="center"></td>
                            <td align="center"></td>
                        ';
                    }

                }

                if ($login_fabrica == 1 && $msg_os_deletadas == "" && (strlen($aprovado) == 0 || $login_fabrica == 14)) {
                  echo "<td align='center' nowrap>";
                  echo "<input type='checkbox' name='acumular_$i' id='acumular' value='$extrato' class='frm'>\n";
                  echo "</td>\n";
                }

                if (in_array($login_fabrica,array(1,2,8,20,30,40,47,14,42))) {
                    if ($msg_os_deletadas == "") {
                        echo "<td align='center' nowrap>";
                        if (strlen($aprovado) == 0 || $login_fabrica == 14) {
                            if ($login_fabrica == 20 || $login_fabrica == 14) {
                                echo "<a href=\"javascript:if(confirm('Deseja aprovar todas as OS¬¥s deste extrato? '))window.location='$PHP_SELF?aprovar=$extrato&posto=$posto&data_inicial=$data_inicial&data_final=$data_final&cnpj=$xposto_codigo&razao=$posto_nome&nf_mao_de_obra='+document.getElementById('nota_fiscal_mao_de_obra_$i').value+'&nf_devolucao='+document.getElementById('nota_fiscal_devolucao_$i').value+'&data_entrega_transportadora='+document.getElementById('data_entrega_transportadora_$i').value\">";
                                echo "<img class='extrato_aprova_$extrato' src='imagens_admin/btn_aprovar_azul.gif' ALT='Aprovar o extrato'></a>";
                                echo "<label for='extrato_aprovado_$extrato' style='display:none;'>";
                            } else {
                                if ($login_fabrica == 1) {
                                    echo "<a href=\"javascript:aprovaExtrato($extrato,$posto,'img_aprovar_$i','img_novo_$i','img_adicionar_$i','acumular_$i','resposta_$i', 'linha_$extrato');\"><img src='imagens_admin/btn_aprovar_azul.gif' id='img_aprovar_$i' ALT='Aprovar o extrato'></a><span id='resposta_$i'></span>";
                                } else {
                                    echo "<a href='$PHP_SELF?aprovar=$extrato&posto=$posto&data_inicial=$data_inicial&data_final=$data_final&cnpj=$xposto_codigo&razao=$posto_nome'><img src='imagens_admin/btn_aprovar_azul.gif' id='img_aprovar_$i' ALT='Aprovar o extrato'></a>";
                                }
                            }
                            if (!in_array($login_fabrica, [1,20,47])) {
                                echo "<input type='checkbox' name='acumular_$i' id='acumular' value='$extrato' class='frm'>\n";
                            }
                        }
                        echo "</td>\n";
                    }
                }

                if ($login_fabrica == 158) {
                  if ($temLGR == "t") {
                    echo "<td><a href='lgr_os.php?extrato={$extrato}' target='_blank' >Ver LGR</a></td>";
                  } else {
                    echo "<td>&nbsp;</td>";
                  }
                }

                if($telecontrol_distrib and !$controle_distrib_telecontrol){
                  echo "<td style='text-align: center;'> ";
                  if( strlen(trim($nota_fiscal_servico))>0 AND  $pendente != 'f' AND strlen($conferido)==0 and strlen($admin_conferiu)==0 ){
                    echo "<button type='button' class='btn btn-success' id='conferido_$extrato' onclick='conferir($extrato)' > Conferir</button> ";
                  }
                  echo " </td>";

                    echo "<td style='text-align: center;'>";
                    if( strlen(trim($nota_fiscal_servico))>0 AND  $pendente == 't'){
                      echo "<button  type='button' class='btn btn-success' id='recusado_$extrato' onclick='recusar($extrato, $posto)'>Recusar</button>";
                      }
                    echo "</td>";

                }else if (!in_array($login_fabrica, [157])) {
                  // se o msg_os_deletadas for nulo o extrato n„o foi cancelado. Se n„o for nulo, o Extrato foi cancelado
                  if ($msg_os_deletadas == "") {

                      echo "<td style='text-align: center;'>";

                      if($login_fabrica == 45 && strlen($baixado) == 0){
                          echo "<a href='extrato_avulso.php'><img src='imagens/btn_novo_azul.gif' id='img_novo_$i' ALT='Cadastrar um Novo Extrato'></a>";
                      }

                      elseif (strlen($aprovado) == 0 OR $login_fabrica == 30)
                          echo "<a href='extrato_avulso.php'><img class='extrato_novo_$extrato' src='imagens/btn_novo_azul.gif' id='img_novo_$i' ALT='Cadastrar um Novo Extrato'></a>";
                      echo "</td>\n";

                      echo "<td style='text-align: center;'>";

                      if($login_fabrica == 45 && strlen($baixado) == 0){
                          echo "<a href='extrato_avulso.php?extrato=$extrato&posto=$posto'><img src='imagens/btn_adicionar_azul.gif' id='img_adicionar_$i' ALT = 'LanÁar itens no extrato'></a>";
                      }

                      elseif (strlen($aprovado) == 0 OR $login_fabrica == 8 or $login_fabrica == 104 or $login_fabrica == 105)
                          echo "<a href='extrato_avulso.php?extrato=$extrato&posto=$posto'><img class='extrato_adicionar_$extrato' src='imagens/btn_adicionar_azul.gif' id='img_adicionar_$i' ALT = 'LanÁar itens no extrato'></a>";
                      echo "</td>\n";
                      if ($login_fabrica == 45 || ( $login_fabrica == 24  && $xtotal <= 50  ) ) {
                          echo "<td nowrap>";
                          if ($login_fabrica == 24 && strlen($aprovado)==0 ) {

                              echo '<a href="#" class="acumula_extrato">Acumular</a>';

                          }
                          echo (strlen($aprovado)==0) ? "<input type='checkbox' name='acumular_$i' id='acumular' value='$extrato' class='frm'>\n" : "&nbsp; ";
                          echo "</td>";
                      }
                      else if ($login_fabrica == 24) {
                          echo '<td>&nbsp;</td>';
                      }
                  } else { //sÛ entra aqui se o extrato foi excluido e a fabrica eh 2-  DYNACON
                      echo "<td colspan='3' align='center'>";
                      echo "<b style='font-size:10px;color:red'>Extrato cancelado!!</b>";
                      echo "</td>";
                      echo "</tr>";
                      echo "<tr>";
                      echo         "<td></td>";
                      echo        "<td colspan=9 align='left'> <b style='font-size:12px;font-weight:normal'>$msg_os_deletadas</b> </td>";
                      echo    "</td>";
                  }

              }

                if ($login_fabrica == 50 or $login_fabrica == 15) {
                    echo "<td></td><td align='center'>$previsao_pagamento</td>";
                    echo "<td align='center'>$data_recebimento_nf</td>";
                }

                // HD12104
                if ($login_fabrica == 14)   {
                    //echo "<td align='center' nowrap>&nbsp;</td>";
                    echo "<td align='center' nowrap>";
                    echo " <input type='checkbox' class='frm' name='imprime_os_$i' value='$extrato'";
                    if($imprime_os == 't') echo " checked ";
                    echo " >";
                    echo "</td>\n";
                }

                if ($login_fabrica == 35) {
                    echo "<td></td><td align='center'><a href='os_extrato_pecas_retornaveis_cadence.php?extrato=$extrato' target='_blank'><img src='imagens/btn_pecasretornaveis_azul.gif'></a></td>";
                }

            }

            if(in_array($login_fabrica,array(85))):

            ?>
                <td style="text-align: center;"><input class="print" extrato="<?php echo $extrato ?>"  type="checkbox" name="extrato[]" value="<?php echo $extrato ?>" /></td>
            <?php
            endif;

            if($login_fabrica == 30){
                $sqlEncontro = "SELECT  to_char(posto_data_transacao,'DD/MM/YYYY') AS dt_pagamento,
                                        nf_numero_nf,
                                        nf_valor_do_encontro_contas,
                                        encontro_serie,
                                        encontro_titulo_a_pagar,
                                        encontro_parcela,
                                        encontro_valor_liquido,
                                        posto_valor_do_encontro_contas
                                    FROM tbl_encontro_contas
                                    WHERE fabrica = $login_fabrica
                                    AND extrato = $extrato
                                    LIMIT 1";

                $resEncontro = pg_query($con,$sqlEncontro);

                if(pg_num_rows($resEncontro) > 0){
                    $num_oc         = pg_fetch_result($resEncontro, 0, 'encontro_serie');
                    $dt_pagamento   = pg_fetch_result($resEncontro, 0, 'dt_pagamento');
                    $valor_pago     = pg_fetch_result($resEncontro, 0, 'nf_valor_do_encontro_contas');
                    $num_nf         = pg_fetch_result($resEncontro, 0, 'nf_numero_nf');
                    $desconto       = pg_fetch_result($resEncontro, 0, 'posto_valor_do_encontro_contas');
                    $button = "<input type='button' rel='$extrato' value='Encontro Contas' id='encontro_contas_$extrato'>";
                }else{
                    $num_oc = "";
                    $dt_pagamento = "";
                    $valor_pago = "";
                    $num_nf = "";
                    $desconto = "";
                    $button = "&nbsp;";
                }
                ?>
                    <td><?=$num_oc?></td>
                    <td><?=$dt_pagamento?></td>
                    <td><?=number_format($valor_pago,2,',','.')?></td>
                    <td><?=$num_nf?></td>
                    <td><?=number_format($desconto,2,',','.')?></td>
                    <td><?=$button?></td>
                <?php
            }

            if($login_fabrica == 151){
              $parametros_adicionais = pg_fetch_result($res, $i, "centro_distribuicao");
              echo "<td align='left' $cor_estoque_menor>";
              if($parametros_adicionais == "mk_nordeste"){
                  echo "MK Nordeste";
              }else if($parametros_adicionais == "mk_sul") {
                  echo "MK Sul";    
              } else{
                  echo "&nbsp;";    
              }
              echo "</td>\n";
            }

            echo "</tr>\n";
            flush();
        }

        if ($login_fabrica == 50) { //HD 49532 11/11/2008
            $xsoma_total = number_format($soma_total,2, ",", ".");
            echo "<tr bgcolor='$cor'>\n";
                echo "<td colspan='6' align='right'><B>TOTAL</B></td>\n";
                echo "<td>$xsoma_total</td>\n";
                echo "<td colspan='8' align='right'>&nbsp;</td>\n";
            echo "</tr>\n";
        }

        echo "</tbody>";
        echo "<tfoot>";
        echo "<tr>\n";

        if ( in_array($login_fabrica, array(11,172)) ) {
            echo "<td colspan='7'>
                Quando um extrato È liberado, automaticamente È enviado um email para o posto. Se quiser acrescentar uma mensagem digite no campo abaixo.
                <br>
                <INPUT size='60' TYPE='text' NAME='msg_aviso' value=''>
            </td>\n";
        } elseif ($login_fabrica == 24) {
            echo "<td colspan='5'><span id='total_extratos' style='font-size:14px'></span></td>\n";
            echo "<td colspan='2'></td>\n";
        } elseif($login_fabrica == 20){
            echo "<td><input type='button' value='Calcular Extratos' onclick='calcularExtrato(); return false;' /></td>\n";

            echo "<td align='left'>";
                echo "<button type='button' id='aprovar_todos_extratos' onClick='aprovarTodos();'>Aprovar Todos</button>";
            echo "</td>\n";
            echo "<td colspan='17'></td>";

        } else {
            if ($login_fabrica == 14){
                echo "</tr></table><td colspan='7'>&nbsp;<INPUT size='60' TYPE='hidden' NAME='msg_aviso' value=''></td>\n";
            }else if(in_array($login_fabrica,array(91))){
                   echo "<td colspan='6'> <INPUT size='60' TYPE='hidden' NAME='msg_aviso' value=''> </td>\n";

            }else{
		    $colspan = (in_array($login_fabrica, array(40,50,142,145)) || isset($novaTelaOs)) ? 8 : 7;
		    $colspan = (in_array($login_fabrica, array(35))) ? 5 : $colspan;
        $colspan = (in_array($login_fabrica, [169,170,178])) ? 9 : $colspan;
            if (!in_array($login_fabrica, array(81,114,122,123,125,147)) and !$replica_einhell){
                echo "<td colspan='{$colspan}'>&nbsp;<INPUT size='60' TYPE='hidden' NAME='msg_aviso' value=''></td>\n";
              }
            } 
	}
	if (in_array($login_fabrica,array(35))){
		echo "<td align='center'>".$total_os."</td>";
	}
if (!in_array($login_fabrica, [152,180,181,182])) {
        if (in_array($login_fabrica,array(35,91))){
            echo "<td align='right'>".number_format ($soma_total,2,',','.')."</td>";
        }
        if (in_array($login_fabrica,array(91))){

            echo "<td>".number_format ($total_valor_liquido,2,',','.')."</td>";
        }

        if($login_fabrica == 153){
            echo "<td></td>";
        }elseif ($login_fabrica == 183) {
            echo "<td></td><td></td><td></td>";
        }
        if ($login_fabrica == 85 or $login_fabrica == 91) echo '<td>&nbsp;</td>';
        if ((in_array($login_fabrica, array(6,7,11,15,24,25,30,35,40,42,50,51,46,47,59,74,52,160)) or ($login_fabrica > 81)) and ((!$telecontrol_distrib or $controle_distrib_telecontrol or $replica_einhell)) || $login_fabrica == 160) {
		$title_link = ($login_fabrica == 158) ? "Liberar/Aprovar Selecionados" : "Liberar Selecionados";
            if (in_array($login_fabrica, [11,172])) {
              echo "<td></td>";
              echo "<td align='center'>";
              echo "<a class='carregando_ajax_hide' href='javascript: liberar_todos(); '>$title_link</a>";
              echo "<div style='display: none;' class='carregando_ajax_show'><i class='fa fa-spinner fa-pulse fa-2x'></i></div>";    
            } else {
              
              if ($login_fabrica == 151) {
                echo "<td colspan='1'>&nbsp;</td>";
              }

              echo "<td align='center'>";
              echo "<a href='javascript: document.Selecionar.btnacao.value=\"liberar_tudo\" ; document.Selecionar.submit() '>$title_link</a>";
            }
            
             
            echo "<input type='hidden' name='total_postos' value='$i'>";
            echo "</td>\n";
        }
        if($login_fabrica == 153){
            echo "<td></td>";
        }else if ($login_fabrica == 183){
            echo "<td colspan='3'></td><td></td>";
        }
        if($login_fabrica == 40){
            echo "<td colspan='4'>&nbsp;</td>\n";
        }

        if ($login_fabrica == 14) {
            echo "<table class='formulario'><tr><td align='center'>";
            echo "<a href='javascript: document.Selecionar.btnacao.value=\"liberar_tudo\" ; document.Selecionar.submit() '>Liberar Selecionados/a>";
            echo "<input type='hidden' name='total_postos' value='$i'>";
            echo "</td>\n";
        }

        if ($login_fabrica == 1 or $login_fabrica == 45 or $login_fabrica == 24 ) { //HD 66773
            $colspan = ($login_fabrica == 45 || $login_fabrica == 24) ? 4 : 5;
            $colspan = ($login_fabrica == 1) ? 9 : $colspan;
            echo "<td colspan='$colspan'>&nbsp;</td>\n";
            echo "<td align='center'>";
            if ($login_fabrica != 24 )
                $submit_form = "document.Selecionar.submit()";
            echo "<a href='javascript: document.Selecionar.btnacao.value=\"acumular_tudo\" ; $submit_form  ' id=\"acumula_extratos\">Acumular selecionados</a>";
            echo "<input type='hidden' name='total_postos' value='$i'>";
            echo "</td>\n";
        }
        if(!in_array($login_fabrica, array(20,40,81,114,122,123,125,147,160)) and !$replica_einhell){

          if ($login_fabrica == 151) {
            
            if ((!empty($_POST['posto_codigo']) || !empty($_POST['posto_nome']))) {
              echo "<td colspan='1' align='center'><a href='#' id='agrupar_tudo' >Agrupar Extratos</a></td>\n";
            }

            echo "<td colspan='3'>&nbsp;</td>\n";
          
          } else {
        		if ($login_fabrica == 1) {
              echo "<td colspan='3'>&nbsp;</td>\n";
            } else if ($login_fabrica == 198) { 
              echo "<td colspan='5'>&nbsp;</td>\n";
            }else {
              echo "<td colspan='2'>&nbsp;</td>\n";
            }
       	 }
				}
      }
        if(in_array($login_fabrica,array(85))):

        ?>
            <script type="text/javascript">
                function imprimirExtratos(){
                    var form = $('form[hidden].print');
                    form.html('');
                    form.append($('input[type=checkbox][extrato].print').clone());
                    form.submit();
                }
            </script>
            <td>
                <a href="#_blank" onclick="imprimirExtratos()" >
                    Imprimir Selecionados
                </a>
            </td>
        <?php
        endif;
        echo "</tr>\n";
        echo "</tfoot>";
        echo "</table>\n";
        echo "</form>\n";

        echo "<p>".traduz('Extratos').": $qtde_extratos</p>";

        if($login_fabrica == 20){
            echo "<p id='qtdeOS'></p>";
        }

        if(in_array($login_fabrica, array(50,91,138,152,180,181,182))){?>

            <button type="button" id='download_excel' value="t"><?=traduz('Gerar Excel')?></button>

    <? }else if(in_array($login_fabrica, array(1,20,35,86,131,183)) and $qtde_extratos > 0){ ?>
            <!--<button type="button" id='download_excel' onclick="window.open('<?=$relatorio_excel?>','_blank');">Gerar Excel</button>-->
            <div style="display: block;" id='gerar_excel' class="btn_excel" onclick="window.open('<?=$relatorio_excel?>','_blank');">
                <span><img src='imagens/excel.png' /></span>
                <span class="txt">Gerar Arquivo Excel</span>
            </div>
<?      }
    }

    if (strlen($msg_os_deletadas ) >0 and $login_fabrica == 2) {
        echo "<br><div name='os_excluidas' style='border:1px solid #00ffff'><h4>OS excluidas</h4>$msg_os_deletadas;</div>";
    }

    if ($login_fabrica == 3) {

        if (strlen($extrato) > 0) {
            $cond_extrato = " AND tbl_extrato.extrato = $extrato ";
        }

        echo "<br /><br />";

        $sql = "SELECT  tbl_posto.posto               ,
                        tbl_posto.nome                ,
                        tbl_posto.cnpj                ,
                        tbl_posto_fabrica.codigo_posto,
                        tbl_posto_fabrica.distribuidor,
                        tbl_extrato.extrato           ,
                        to_char (tbl_extrato.data_geracao,'dd/mm/yyyy') as data_geracao,
                        tbl_extrato.total,
                        (SELECT count (tbl_os.os) FROM tbl_os JOIN tbl_os_extra USING (os) WHERE tbl_os_extra.extrato = tbl_extrato.extrato) AS qtde_os,
                        to_char (tbl_extrato_pagamento.data_pagamento,'dd/mm/yyyy') as baixado
                FROM    tbl_extrato
                JOIN    tbl_posto USING (posto)
                JOIN    tbl_posto_fabrica ON tbl_extrato.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                left JOIN    tbl_extrato_pagamento ON tbl_extrato.extrato = tbl_extrato_pagamento.extrato
                WHERE   tbl_extrato.fabrica = $login_fabrica
                AND     tbl_posto_fabrica.distribuidor NOTNULL
                $cond_extrato";

        if (strlen ($data_inicial) < 8) $data_inicial = date ("d/m/Y");
            $x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);

        if (strlen ($data_final) < 10) $data_final = date ("d/m/Y");
            $x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);

        if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
        $sql .= " AND      tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";

        $xposto_codigo = str_replace (" " , "" , $posto_codigo);
        $xposto_codigo = str_replace ("-" , "" , $xposto_codigo);
        $xposto_codigo = str_replace ("/" , "" , $xposto_codigo);
        $xposto_codigo = str_replace ("." , "" , $xposto_codigo);

        if (strlen ($posto_codigo) > 0 ) $sql .= " AND tbl_posto.cnpj = '$xposto_codigo' ";
        if (strlen ($posto_nome) > 0 )   $sql .= " AND tbl_posto.nome ILIKE '%$posto_nome%' ";

        $sql .= " GROUP BY tbl_posto.posto ,
                        tbl_posto.nome ,
                        tbl_posto.cnpj ,
                        tbl_posto_fabrica.codigo_posto,
                        tbl_posto_fabrica.distribuidor,
                        tbl_extrato.extrato ,
                        tbl_extrato.liberado ,
                        tbl_extrato.total,
                        tbl_extrato.data_geracao,
                        tbl_extrato_pagamento.data_pagamento
                    ORDER BY tbl_posto.nome, tbl_extrato.data_geracao";

        $res = pg_query ($con,$sql);

        if (pg_num_rows ($res) == 0) {
            echo "<center><font style='font:bold 12px Arial; color:#000;'>'N„o Foram Encontrados Resultados para esta Pesquisa</font></center>";
        }

        if (pg_num_rows ($res) > 0) {
            for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {

                $posto   = trim(pg_fetch_result($res,$i,posto));
                $codigo_posto   = trim(pg_fetch_result($res,$i,codigo_posto));
                $nome           = trim(pg_fetch_result($res,$i,nome));
                $extrato        = trim(pg_fetch_result($res,$i,extrato));
                $data_geracao   = trim(pg_fetch_result($res,$i,data_geracao));
                $qtde_os        = trim(pg_fetch_result($res,$i,qtde_os));
                $total          = trim(pg_fetch_result($res,$i,total));
                $baixado        = trim(pg_fetch_result($res,$i,baixado));
                $extrato        = trim(pg_fetch_result($res,$i,extrato));
                $distribuidor   = trim(pg_fetch_result($res,$i,distribuidor));
                $total          = number_format ($total,2,',','.');

                if (strlen($distribuidor) > 0) {
                    $sql = "SELECT  tbl_posto.nome                ,
                                    tbl_posto_fabrica.codigo_posto
                            FROM    tbl_posto_fabrica
                            JOIN    tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
                            WHERE   tbl_posto_fabrica.posto   = $distribuidor
                            AND     tbl_posto_fabrica.fabrica = $login_fabrica;";
                    $resx = pg_query ($con,$sql);

                    if (pg_num_rows($resx) > 0) {
                        $distribuidor_codigo = trim(pg_fetch_result($resx,0,codigo_posto));
                        $distribuidor_nome   = trim(pg_fetch_result($resx,0,nome));
                    }
                }

                if ($i == 0) {
                    echo "<table width='700px' class='tabela' align='center' border='1' cellspacing='2'>";
                    echo "<tr class='titulo_coluna'>";
                    echo "<td align='center'>CÛdigo</td>";
                    echo "<td align='center' nowrap>Nome do Posto</td>";
                    echo "<td align='center'>Extrato</td>";
                    echo "<td align='center'>Data</td>";
                    echo "<td align='center' nowrap>Qtde. OS</td>";
                    echo "<td align='center'>Total</td>";
                    echo "<td align='center' colspan='2'>Extrato Vinculado a um Distribuidor</td>";
                    echo "</tr>";
                }

                echo "<tr>";

                echo "<td align='left'>";
                echo "$codigo_posto</td>";

                echo "<td align='left' nowrap>$nome</td>";
                echo "<td align='center'><a href='extrato_consulta_os.php?extrato=$extrato' target='_blank'>$extrato</a></td>";

                echo "<td align='left'>$data_geracao</td>";
                echo "<td align='center'>$qtde_os</td>";
                echo "<td align='right' nowrap>R$ $total</td>";
                echo "<td align='left' nowrap>$distribuidor_codigo - $distribuidor_nome</td>";
                echo "</tr>";
            }
            echo "</table>";


        }
    }
}

}
?>

<?php
    if(in_array($login_fabrica,array(85))):
?>  
    <form target="_blank" class="print" action="extrato_consulta_os_print.php" method="GET" hidden="hidden" style="display:none">
    </form>
<?php
    endif;
?>
<br>
<br>
<br>

<?php if ($login_fabrica == 85) { ?> 
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.1/css/responsive.dataTables.min.css">
  <script src="https://cdn.datatables.net/responsive/2.2.1/js/dataTables.responsive.min.js"></script>
  <link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.12/css/jquery.dataTables.min.css" />
  <script src="//cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>
<?php } ?>

<script> 

  <?php if ($login_fabrica == 85) { ?> 
    
    $(document).ready( function () {
      $('#grid_list').DataTable({
        "paging" : false,
        "lengthChange": false
      });    
    });
    
/*    
    window.onload = function() {
      $('#grid_list_codigo').trigger("click");
    };
*/

  <?php } ?>
  function onBeforeLiberacao(autorizacao_pagto, extrato){
    var res = confirm('Deseja liberar o extrato ' + extrato);
    if(res == true){
      confirmar_liberacao(autorizacao_pagto, extrato);
    }
  }

<?php if ($login_fabrica == 151) { ?> 
    
    $(document).ready( function () {
      let total_input = 0;
      $('td #agrupar').each(function(){
        total_input ++;
      });
      if (total_input <= 1) {
        $("#agrupar, #agrupar_tudo").hide();
      }  
    });

<?php } ?>

</script>

<? include "rodape.php"; ?>

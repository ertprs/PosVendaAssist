<? // MONTEIRO
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';
include "../helpdesk.inc.php";
include_once '../class/communicator.class.php';

$programa_insert = $_SERVER['PHP_SELF'];

/*
 * - AJAX Exclusivo para MAKITA
 */

if (filter_input(INPUT_POST,'ajax',FILTER_VALIDATE_BOOLEAN)) {
    $os             = filter_input(INPUT_POST,'os');
    $motivo         = pg_escape_string(filter_input(INPUT_POST,'motivo'));
    $posto          = filter_input(INPUT_POST,'posto');
    $acao           = filter_input(INPUT_POST,'acao');
    $intervencao    = filter_input(INPUT_POST,'intervencao');

    if ($intervencao == 'cortesia_comercial') {
        $cond_tipo = "AND tbl_auditoria_os.observacao ~* 'cortesia comercial'";
    } else {
        $cond_tipo = "AND tbl_auditoria_os.observacao !~* 'cortesia comercial'";
    }

    if ($acao == "garantia") {
        $sqlGarantia = "
            SELECT  DISTINCT
                    CASE WHEN (tbl_os.data_abertura::date - tbl_produto.garantia * INTERVAL '1 month') <= tbl_os.data_nf
                         THEN 1
                         ELSE 0
                    END  AS medicao_garantia
            FROM    tbl_os
            JOIN    tbl_os_produto  USING(os)
            JOIN    tbl_produto     ON tbl_os_produto.produto = tbl_produto.produto
            WHERE   tbl_os.os = $os
        ";
        $resGarantia = pg_query($con,$sqlGarantia);
        $medicao_garantia = pg_fetch_result($resGarantia,0,medicao_garantia);

        $justificativa = ($medicao_garantia == 1)
            ? utf8_encode("OS $os foi aprovada a Garantia")
            : utf8_encode("OS $os está fora do prazo de garantia");

    }

    pg_query($con,'BEGIN TRANSACTION');
    switch ($acao) {
        case "aprovar_os":
            $sqlUpAud = "
                UPDATE  tbl_auditoria_os
                SET     liberada        = CURRENT_TIMESTAMP,
                        bloqueio_pedido = FALSE,
                        justificativa   = '".$motivo."',
                        admin           = $login_admin
                WHERE   os              = $os
                AND     auditoria_status    = 6
                AND     liberada            IS NULL
				AND		cancelada isnull and reprovada isnull
                $cond_tipo
            ";
            $msg = json_encode(array("ok" => "OS $os foi aprovada como cortesia."));
            break;
        case "aprovar_pecas":
            $sqlUpAud = "
                UPDATE  tbl_auditoria_os
                SET     liberada        = CURRENT_TIMESTAMP,
                        bloqueio_pedido = FALSE,
                        paga_mao_obra   = FALSE,
                        justificativa   = '".$motivo."',
                        admin           = $login_admin
                WHERE   os              = $os
                AND     liberada        IS NULL
				AND		cancelada isnull and reprovada isnull
                AND     auditoria_status = 6
                $cond_tipo
            ";
            $msg = json_encode(array("ok" => utf8_encode("As peças da OS $os foram aprovadas como cortesia")));
            break;
        case "garantia":
            $sqlUpAud = "
                UPDATE  tbl_auditoria_os
                SET     liberada        = CURRENT_TIMESTAMP,
                        bloqueio_pedido = FALSE,
                        justificativa   = '".$motivo."',
                        admin           = $login_admin
                WHERE   os = $os
                AND     auditoria_status = 6
                AND     liberada isnull
				AND		reprovada isnull
				AND		cancelada isnull
                $cond_tipo ;

                UPDATE  tbl_os
                SET     tipo_atendimento            = 102,
                        justificativa_adicionais    = '$justificativa'
                WHERE   os = $os;
            ";
            $msg = json_encode(array("ok" => "$justificativa "));
            break;
        case "recusar":
            $sqlUpAud = "
                UPDATE  tbl_auditoria_os
                SET     reprovada       = CURRENT_TIMESTAMP,
                        bloqueio_pedido = TRUE,
                        paga_mao_obra   = FALSE,
                        justificativa   = '".$motivo."',
                        admin           = $login_admin
                WHERE   os          = $os
                AND     reprovada   IS NULL
				AND		liberada isnull
            ";

            $sqlOsCancela = "UPDATE tbl_os SET cancelada = 't' WHERE os = {$os}";
            $resOsCancela = pg_query($con, $sqlOsCancela);

            $msg = json_encode(array("ok" => "OS $os foi recusada como cortesia"));
            break;
    }

    $resUpAud = pg_query($con,$sqlUpAud);
    $msg_erro = pg_last_error($con);
    if (strlen($msg_erro)){
        $msg = $msg_erro;
        pg_query ($con,'ROLLBACK TRANSACTION');
    } else {
        pg_query ($con,'COMMIT TRANSACTION');

        if ($intervencao == 'cortesia_comercial') {

            if ($acao == "aprovar_os") {
                $tipoAcao = " APROVADA.<br /> Motivo: ".utf8_decode($motivo);

            } else if ($acao == "recusar") {
                $tipoAcao = " REPROVADA.<br /> Motivo: ".utf8_decode($motivo);
            }

            $message = "
                A Ordem de Serviço de Cortesia Nº $os foi $tipoAcao.

                <a href='posvenda.telecontrol.com.br/assist/os_press.php?os=$os'>Clique Aqui</a> para acessar o Telecontrol e visualizar mais informações.

                Obs.: Visualize o campo 'Justificativa'

                Atenciosamente,

                Equipe Telecontrol.
            ";
            $titulo = utf8_encode("Ordem de Serviço de cortesia - MAKITA");

            $sqlEmailCons = "
                SELECT  contato_email
                FROM    tbl_posto_fabrica
                JOIN    tbl_os USING(posto,fabrica)
                WHERE   os = $os
                AND     contato_email IS NOT NULL
            ";
            $resEmailCons = pg_query($con,$sqlEmailCons);

            if (pg_num_rows($resEmailCons) == 0) {
                echo $msg;
                exit;
            }

            $mail = new TcComm($externalId);
            $mail->setEmailDest(pg_fetch_result($resEmailCons,0,contato_email))
                ->setEmailFrom('"Telecontrol Pós Venda" <no-reply@telecontrol.com.br>')
                ->setEmailSubject($titulo)
                ->setEmailBody(nl2br(utf8_encode($message)))
                ->sendMail();

        }
    }
    echo $msg;
    exit;
}

// APROVAR //
if($_POST['btn_acao'] == 'aprovar_os') {

    $intervencoes = array();
    $os = $_POST['os'];
    $intervencao = $_POST['intervencao'];
    $posto = $_POST['posto'];
    $auditoria_geral = $_POST['auditoria_geral'];
    $auditorias = $_POST["auditorias"];

    $intervencoes[] = $intervencao;

    if ($auditoria_geral === "t" and !empty($auditorias)) {
        $intervencoes = explode("|", $auditorias);
    }

    $res = pg_query($con,'BEGIN TRANSACTION');

    foreach ($intervencoes as $intervencao) {
        if (($intervencao == 'posto_auditado') OR (preg_match('/posto_auditado/', $intervencao))) {
            $sqlAud = "SELECT tbl_auditoria_os.auditoria_os
                        FROM tbl_auditoria_os
                        WHERE tbl_auditoria_os.os = $os
                        AND auditoria_status = 6
                        ORDER BY tbl_auditoria_os.data_input DESC LIMIT 1";
            $resAud = pg_query($con, $sqlAud);
            $aud_os = pg_fetch_result($resAud, 0, 'auditoria_os');

            $sql = "UPDATE tbl_auditoria_os
                    SET liberada = CURRENT_TIMESTAMP,
                        admin = $login_admin,
                        bloqueio_pedido = 'f'
                    WHERE os = $os
                    AND auditoria_os = $aud_os
					AND		cancelada isnull and reprovada isnull
                    AND auditoria_status = 6";
            $res =  pg_query($con, $sql);

            $msg_erro .= pg_last_error($con);

            $msg = json_encode(array("ok"=>utf8_encode("O Posto foi liberado da Auditoria.")));
        }

        if ($intervencao == 'produto_auditado') {
            $sqlAud = "SELECT tbl_auditoria_os.auditoria_os
                            FROM tbl_auditoria_os
                            WHERE tbl_auditoria_os.os = $os
                            AND auditoria_status = 3
                            ORDER BY tbl_auditoria_os.data_input DESC LIMIT 1";
            $resAud = pg_query($con, $sqlAud);

            $aud_os = pg_fetch_result($resAud, 0, 'auditoria_os');

            $sql = "UPDATE tbl_auditoria_os SET liberada = CURRENT_TIMESTAMP, admin = $login_admin, bloqueio_pedido = 'f'
                    WHERE os = $os
                    AND auditoria_os = $aud_os
					AND		cancelada isnull and reprovada isnull
                    AND auditoria_status = 3";
            $res =  pg_query($con, $sql);
            $msg_erro .= pg_last_error($con);

            $msg = json_encode(array("ok"=>utf8_encode("OS com Produto Auditado foi liberada da Auditoria.")));
        }

        if ($intervencao == 'auditoria_os_pecas') {
            $sqlAud = "SELECT tbl_auditoria_os.auditoria_os
                            FROM tbl_auditoria_os
                            WHERE tbl_auditoria_os.os = $os
                            AND auditoria_status = 4
                            ORDER BY tbl_auditoria_os.data_input DESC LIMIT 1";
            $resAud = pg_query($con, $sqlAud);

            $aud_os = pg_fetch_result($resAud, 0, 'auditoria_os');

            $sql = "UPDATE tbl_auditoria_os SET liberada = CURRENT_TIMESTAMP, admin = $login_admin, bloqueio_pedido = 'f'
                    WHERE os = $os
                    AND auditoria_os = $aud_os
					AND		cancelada isnull and reprovada isnull
                    AND auditoria_status = 4";
            $res =  pg_query($con, $sql);
            $msg_erro .= pg_last_error($con);

            $msg = json_encode(array("ok"=>utf8_encode("OS com Auditoria de Peças foi liberada da Auditoria.")));
        }

        if ($intervencao == 'lista_basica') {
            $sqlAud = "SELECT tbl_auditoria_os.auditoria_os
                            FROM tbl_auditoria_os
                            WHERE tbl_auditoria_os.os = $os
                            AND auditoria_status = 4
                            ORDER BY tbl_auditoria_os.data_input DESC LIMIT 1";
            $resAud = pg_query($con, $sqlAud);

            $aud_os = pg_fetch_result($resAud, 0, 'auditoria_os');

            $sql = "UPDATE tbl_auditoria_os SET liberada = CURRENT_TIMESTAMP, admin = $login_admin, bloqueio_pedido = 'f'
                    WHERE os = $os
                    AND auditoria_os = $aud_os
					AND		cancelada isnull and reprovada isnull
                    AND auditoria_status = 4";
            $res =  pg_query($con, $sql);
            $msg_erro .= pg_last_error($con);

            $msg = json_encode(array("ok"=>utf8_encode("OS com Auditoria de Peças excedentes da lista básica foi liberada da Auditoria.")));
        }

        if ($intervencao == 'intervencao_tecnica') {
            $sql = "INSERT INTO tbl_os_status
                    (os,status_os,data,observacao,admin)
                    VALUES ($os,64,CURRENT_TIMESTAMP,'Liberada de auditoria de Peça Crítica',$login_admin)";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_last_error($con);

            $msg = json_encode(array("ok"=>utf8_encode("Pedido de peças da OS $os foi autorizado. A OS foi liberada para o posto.")));
        }

        if ($intervencao == 'os_reincidente') {
            $sqlAud = "SELECT tbl_auditoria_os.auditoria_os
                            FROM tbl_auditoria_os
                            WHERE tbl_auditoria_os.os = $os
                            AND auditoria_status = 1
                            ORDER BY tbl_auditoria_os.data_input DESC LIMIT 1";
            $resAud = pg_query($con, $sqlAud);

            $aud_os = pg_fetch_result($resAud, 0, 'auditoria_os');

            if(pg_num_rows($resAud) > 0){
                $sql = "UPDATE tbl_auditoria_os SET liberada = CURRENT_TIMESTAMP, admin = $login_admin, bloqueio_pedido = 'f'
                        WHERE os = $os
                        AND auditoria_os = $aud_os
                        AND auditoria_status = 1";
                $res =  pg_query($con, $sql);
                $msg_erro .= pg_last_error($con);
            }else{
                $sql = "INSERT INTO tbl_os_status
                                (os, status_os, data, observacao, admin)
                        VALUES ($os,19,current_timestamp,'OS aprovada pelo fabricante na auditoria de OS reincidente',$login_admin)";
                $res = pg_query($con,$sql);
                $msg_erro .= pg_last_error($con);
            }

            $msg = json_encode(array("ok"=>utf8_encode("OS $os foi autorizada. A OS foi liberada para o posto.")));
        }

        if($intervencao == "pecas_excedentes"){
            $sql = "INSERT INTO tbl_os_status
                        (os, status_os, observacao, admin)
                    VALUES ($os, 187, 'OS aprovada da intervenção de peças excedentes',$login_admin);";
            $res = pg_query($con,$sql);

            $sql = "INSERT INTO tbl_comunicado
                        (mensagem, tipo, fabrica, descricao, posto, ativo, obrigatorio_site)
                    VALUES ('OS aprovada da intervenção de peças excedentes', 'Comunicado Inicial', $login_fabrica, 'OS {$os} FOI APROVADA DA INTERVENÇÃO', $posto, true, true);";
            $res = pg_query($con, $sql);

            $msg = json_encode(array("ok"=>utf8_encode("OS $os foi aprovada da intervenção de peças excedentes")));
        }
    }

    if (strlen($msg_erro)){
        $msg = $msg_erro;
        $res = pg_query ($con,'ROLLBACK TRANSACTION');
    }else {
        if ($auditoria_geral === "t") {
            $msg = '{"ok": "OS ' . $os . ' aprovada com sucesso."}';
        }

        $res = pg_query ($con,'COMMIT TRANSACTION');
    }

    echo $msg;
    exit;
}

// RECUSAR //
if($_POST["recusar"] == true) {

  $os = $_POST['os'];
  $intervencao = $_POST['intervencao'];
  $posto = $_POST['posto'];
  $motivo = $_POST['motivo'];
  $auditoria_geral = $_POST['auditoria_geral'];
  $auditorias = $_POST["auditorias"];

  $intervencoes[] = $intervencao;

  if ($auditoria_geral === "t" and !empty($auditorias)) {
      $intervencoes = explode("|", $auditorias);
  }

  $res = pg_query($con,'BEGIN TRANSACTION');

  foreach ($intervencoes as $intervencao) {
      if(($intervencao == 'posto_auditado') OR (preg_match('/posto_auditado/', $intervencao))){
        $sqlAud = "SELECT tbl_auditoria_os.auditoria_os
                          FROM tbl_auditoria_os
                          WHERE tbl_auditoria_os.os = $os
                          ORDER BY tbl_auditoria_os.data_input DESC LIMIT 1";
        $resAud = pg_query($con, $sqlAud);
        $aud_os = pg_fetch_result($resAud, 0, 'auditoria_os');

        $sql = "UPDATE tbl_auditoria_os SET reprovada = CURRENT_TIMESTAMP, admin = $login_admin, justificativa = '$motivo'
                WHERE os = $os
                AND auditoria_os = $aud_os
				AND auditoria_status = 6
				AND	liberada isnull";
        $res =  pg_query($con, $sql);
        $msg_erro .= pg_last_error($con);

        $msg = json_encode(array("ok"=>utf8_encode("O Posto foi Recusado na Auditoria.")));

        if(strlen($msg_erro) == ''){

          $msg_observacao = "Posto Recusado na Auditoria <br><br> $motivo ";
          $sql = "INSERT INTO tbl_comunicado (
                    descricao              ,
                    mensagem               ,
                    tipo                   ,
                    fabrica                ,
                    obrigatorio_os_produto ,
                    obrigatorio_site       ,
                    posto                  ,
                    ativo
                  ) VALUES (
                    'Posto Recusado na Auditoria',
                    '$msg_observacao',
                    'Pedido de Peças',
                    $login_fabrica,
                    'f' ,
                    't',
                    $posto,
                    't'
              );";
          $res       = pg_query($con,$sql);
          $msg_erro .= pg_last_error($con);
        }
      }

      if($intervencao == 'produto_auditado'){
        $sqlAud = "SELECT tbl_auditoria_os.auditoria_os
                          FROM tbl_auditoria_os
                          WHERE tbl_auditoria_os.os = $os
                          ORDER BY tbl_auditoria_os.data_input DESC LIMIT 1";
        $resAud = pg_query($con, $sqlAud);
        $aud_os = pg_fetch_result($resAud, 0, 'auditoria_os');

        $sql = "UPDATE tbl_auditoria_os SET reprovada = CURRENT_TIMESTAMP, admin = $login_admin, justificativa = '$motivo'
                WHERE os = $os
                AND auditoria_os = $aud_os
                AND auditoria_status = 3
				AND liberada isnull ";
        $res =  pg_query($con, $sql);
        $msg_erro .= pg_last_error($con);

        $msg = json_encode(array("ok"=>utf8_encode("O Produto da OS $os foi Recusado na Auditoria.")));

        if(strlen($msg_erro) == ''){
          $msg_observacao = "O Produto da OS $os foi Recusado na Auditoria <br><br> $motivo ";
          $sql = "INSERT INTO tbl_comunicado (
                    descricao              ,
                    mensagem               ,
                    tipo                   ,
                    fabrica                ,
                    obrigatorio_os_produto ,
                    obrigatorio_site       ,
                    posto                  ,
                    ativo
                  ) VALUES (
                    'Posto Recusado na Auditoria',
                    '$msg_observacao',
                    'Pedido de Peças',
                    $login_fabrica,
                    'f' ,
                    't',
                    $posto,
                    't'
              );";
          $res       = pg_query($con,$sql);
          $msg_erro .= pg_last_error($con);
        }
      }

      if($intervencao == 'auditoria_os_pecas' || $intervencao == 'lista_basica'){
        $sqlAud = "SELECT tbl_auditoria_os.auditoria_os
                          FROM tbl_auditoria_os
                          WHERE tbl_auditoria_os.os = $os
                          ORDER BY tbl_auditoria_os.data_input DESC LIMIT 1";
        $resAud = pg_query($con, $sqlAud);
        $aud_os = pg_fetch_result($resAud, 0, 'auditoria_os');

        $sql = "UPDATE tbl_auditoria_os SET reprovada = CURRENT_TIMESTAMP, admin = $login_admin, justificativa = '$motivo'
                WHERE os = $os
				AND auditoria_os = $aud_os
				AND liberada isnull
                AND auditoria_status = 4";
        $res =  pg_query($con, $sql);
        $msg_erro .= pg_last_error($con);

        if($login_fabrica == 30 AND strlen($msg_erro) == 0){

          $sql = "SELECT servico_realizado FROM tbl_servico_realizado WHERE fabrica = {$login_fabrica} AND descricao = 'Cancelado'";
          $res = pg_query($con,$sql);

          if(pg_num_rows($res) > 0){

            $servico_realizado_cancelado = pg_fetch_result($res, 0, 'servico_realizado');

            $sql = "UPDATE tbl_os_item SET servico_realizado = {$servico_realizado_cancelado}
                    FROM tbl_os_produto
                    WHERE tbl_os_item.os_produto = tbl_os_produto.os_produto
                    AND tbl_os_produto.os = {$os}
                    AND tbl_os_item.pedido IS NULL
                    AND tbl_os_item.obs <> ''";
            $res = pg_query($con,$sql);
          }

        }

        $msg = json_encode(array("ok"=>utf8_encode("A(s) Peça(s) da OS $os foi/foram Recusada(s) na Auditoria.")));

        if(strlen($msg_erro) == ''){
          $msg_observacao = "A(s) Peça(s) da OS $os foi/foram Recusada(s) na Auditoria. <br><br> $motivo ";
          $sql = "INSERT INTO tbl_comunicado (
                    descricao              ,
                    mensagem               ,
                    tipo                   ,
                    fabrica                ,
                    obrigatorio_os_produto ,
                    obrigatorio_site       ,
                    posto                  ,
                    ativo
                  ) VALUES (
                    'Posto Recusado na Auditoria',
                    '$msg_observacao',
                    'Pedido de Peças',
                    $login_fabrica,
                    'f' ,
                    't',
                    $posto,
                    't'
              );";
          $res       = pg_query($con,$sql);
          $msg_erro .= pg_last_error($con);
        }
      }

      if($intervencao == "intervencao_tecnica"){
        $sql = "INSERT INTO tbl_os_status
                  (os,status_os,data,observacao,admin)
                VALUES ($os,81,CURRENT_TIMESTAMP,'Motivo: $motivo',$login_admin)";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_last_error($con);

        $msg = json_encode(array("ok"=>utf8_encode("OS Recusada com sucesso.")));

        if(strlen($msg_erro) == ''){
          $msg_observacao = "Seu pedido de envio de produto para conserto/troca, referente a O.S $sua_os foi cancelada pela fábrica. <br><br> $motivo ";
          $sql = "INSERT INTO tbl_comunicado (
                    descricao              ,
                    mensagem               ,
                    tipo                   ,
                    fabrica                ,
                    obrigatorio_os_produto ,
                    obrigatorio_site       ,
                    posto                  ,
                    ativo
                  ) VALUES (
                    'Pedido de Peças CANCELADO',
                    '$msg_observacao',
                    'Pedido de Peças',
                    $login_fabrica,
                    'f' ,
                    't',
                    $posto,
                    't'
              );";
          $res       = pg_query($con,$sql);
          $msg_erro .= pg_last_error($con);
        }
      }

      if($intervencao == "os_reincidente"){

        $sqlAud = "SELECT tbl_auditoria_os.auditoria_os
                          FROM tbl_auditoria_os
                          WHERE tbl_auditoria_os.os = $os
                          AND auditoria_status = 1
                          AND liberada IS NULL
                          ORDER BY tbl_auditoria_os.data_input DESC LIMIT 1";
        $resAud = pg_query($con, $sqlAud);

        $aud_os = pg_fetch_result($resAud, 0, 'auditoria_os');

        if(pg_num_rows($resAud) > 0){
          $sql = "UPDATE tbl_auditoria_os SET reprovada = CURRENT_TIMESTAMP, admin = $login_admin, justificativa = '$motivo', bloqueio_pedido = 't'
                  WHERE os = $os
                  AND auditoria_os = $aud_os
					AND liberada isnull
                  AND auditoria_status = 1";
          $res =  pg_query($con, $sql);
          $msg_erro .= pg_last_error($con);
        }else{
          $sql = "INSERT INTO tbl_os_status
                    (os, status_os, data, observacao, admin)
                  VALUES ($os,131,current_timestamp,'Motivo: $motivo',$login_admin)";
          $res = pg_query($con,$sql);
          $msg_erro .= pg_last_error($con);
        }

        $sql = "UPDATE tbl_os set finalizada = null, data_fechamento = null
                FROM tbl_os_extra
                WHERE tbl_os.os = $os
                AND tbl_os_extra.os = tbl_os.os AND tbl_os_extra.extrato isnull";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_last_error($con);
        $msg = json_encode(array("ok"=>utf8_encode("OS Recusado com sucesso")));
      }

      if($intervencao == "pecas_excedentes"){
        $msg = "OS reprovada da intervenção de peças excedentes <br>Motivo: $motivo";

        $sql = "INSERT INTO tbl_os_status
                  (os, status_os, observacao, admin)
                VALUES ($os, 185, '$msg',$login_admin);";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_last_error($con);
        $msg = "OS {$os} foi reprovada da intervenção<br>Motivo: $motivo";

        $sql = "INSERT INTO tbl_comunicado
                  (mensagem, tipo, fabrica, descricao, posto, ativo, obrigatorio_site)
                VALUES ('$msg', 'Comunicado Inicial', $login_fabrica, 'OS {$os} FOI REPROVADA DA INTERVENÇÃO', $posto, true, true)";
        $res = pg_query($con, $sql);
        $msg_erro .= pg_last_error($con);
        if($login_fabrica == 104){
          $sql = "UPDATE tbl_os_item SET
                servico_realizado = tbl_servico_realizado.servico_realizado,
                admin = $login_admin
              FROM tbl_servico_realizado
              WHERE os_item IN (
                  SELECT os_item
                  FROM tbl_os
                  JOIN tbl_os_produto USING(os)
                  JOIN tbl_os_item    USING(os_produto)
                  JOIN tbl_servico_realizado USING(servico_realizado)
                  WHERE tbl_os.os = $os
                  AND tbl_os.fabrica = $login_fabrica
                  AND tbl_servico_realizado.gera_pedido
                  AND tbl_os_item.pedido IS NULL
              )
              AND tbl_servico_realizado.fabrica = $login_fabrica
              AND tbl_servico_realizado.descricao ~* 'cancelado'";
          $res = pg_query($con,$sql);
          $msg_erro .= pg_last_error($con);
        }
        $msg = json_encode(array("ok"=>utf8_encode("OS Recusada com Sucesso")));
      }

  }

    if (strlen($msg_erro)){
        $msg = $msg_erro;
        $res = pg_query ($con,"ROLLBACK TRANSACTION");
    }else {
        if ($auditoria_geral === "t") {
            $msg = '{"ok": "OS ' . $os . ' recusada com sucesso."}';
        }

        $res = pg_query ($con,"COMMIT TRANSACTION");
    }
    echo $msg;
    exit;
}

// INTERAGIR //
if ($_POST["interagir"] == true) {

  $os = $_POST['os'];
  $intervencao = $_POST['intervencao'];
  $posto = $_POST['posto'];
  $interacao = utf8_decode(trim($_POST["interacao"]));
  $os        = $_POST["os"];

  if (!strlen($interagir)) {
    $retorno = array("erro" => utf8_encode("Digite a interação"));
  } else if (empty($os)) {
    $retorno = array("erro" => utf8_encode("OS não informada"));
  } else {
    $select = "SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
    $result = pg_query($con, $select);

    if (!pg_num_rows($result)) {
      $retorno = array("erro" => utf8_encode("OS não encontrada"));
    } else {
      $insert = "INSERT INTO tbl_os_interacao
             (programa,os, admin, fabrica, comentario)
             VALUES
             ('{$programa_insert}',{$os}, {$login_admin}, {$login_fabrica}, '{$interacao}')";
      $result = pg_query($con, $insert);

      if (strlen(pg_last_error()) > 0) {
        $retorno = array("erro" => utf8_encode("Erro ao interagir na OS"));
      } else {
        $retorno = array("ok" => true);

        $sql_email_posto = "SELECT tbl_posto_fabrica.contato_email, tbl_posto_fabrica.nome_fantasia
                            FROM tbl_posto_fabrica
                            WHERE tbl_posto_fabrica.posto = $posto
                            AND tbl_posto_fabrica.fabrica = $login_fabrica";
        $res_email_posto = pg_query($con, $sql_email_posto);
        $email_posto = pg_fetch_result($res_email_posto, 0, 'contato_email');
        $nome_posto = pg_fetch_result($res_email_posto, 0, 'nome_fantasia');

        /* Email */
        include_once '../class/email/mailer/class.phpmailer.php';
        $mailer = new PHPMailer();
        $email_responsavel = $email_posto;
        $headers  = "MIME-Version: 1.0 \r\n";
        $headers .= "Content-type: text/html; charset=iso-8859-1 \r\n";
        $headers .= "From: Suporte <helpdesk@telecontrol.com.br> \r\n";

        $assunto = "Existe uma interação na OS $os, por favor verificar.";
        $mensagem = "Olá {$nome_posto},";
        $mensagem .="<br>Existe uma interação feita pela Fábrica na OS $os, por favor verificar";
        if (!mail($email_responsavel, $assunto, $mensagem, $headers)) {
            $msg_erro .= "Erro ao enviar email para $email_responsavel";
        }
      }
    }
  }
  exit(json_encode($retorno));
}

if($_POST['btn_acao'] == 'submit') {
    $data_inicial    = $_POST['data_inicial'];
    $data_final      = $_POST['data_final'];
    $codigo_posto    = $_POST['codigo_posto'];
    $descricao_posto = $_POST['descricao_posto'];
    $os              = $_POST['os'];
    $status_os       = ($_POST['status_os']);
    $intervencao     = ($_POST['intervencao']);

    if (
        (in_array($status_os, ['aprovadas', 'reprovadas']))
        AND 
        (empty($data_inicial) OR empty($data_final))
        AND
        (strlen(trim($os)) == 0)
    ) {
        $msg_erro['msg'][] = "Digite uma data inicial e uma data final ou o número da OS para realizar a pesquisa.";
    }

    if(isset($_POST["gerarexcel"])){
        $gerarexcel = true;
    }

    if($login_fabrica == 30){
      if(strlen($admin_sap) > 0){
        $admin_sap = (int) $_POST['admin_sap'];
        $cond_admin_sap = " AND tbl_posto_fabrica.admin_sap = $admin_sap ";
      }
    }

  if (strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0) {
    $sql = "SELECT tbl_posto_fabrica.posto
              FROM tbl_posto
              JOIN tbl_posto_fabrica USING(posto)
             WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
               AND (
                    UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}')
                OR
                    TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$descricao_posto}'), 'LATIN-9')
               )";
    $res = pg_query($con ,$sql);

    if (!pg_num_rows($res)) {
      $msg_erro['msg'][]    = 'Posto não encontrado';
      $msg_erro['campos'][] = 'posto';
    } else {
      $posto = pg_fetch_result($res, 0, 'posto');
    }
  }

  if(strlen($data_inicial) > 0 OR strlen($data_final) > 0){
      if (!$aux_data_inicial = dateFormat($data_inicial, 'dmy')) {
          $msg_erro["msg"][]    = "Data Inválida";
          $msg_erro["campos"][] = "data";
      } else if (!$aux_data_final = dateFormat($data_final, 'dmy')) {
          $msg_erro["msg"][]    = "Data Inválida";
          $msg_erro["campos"][] = "data";
      } else {
          if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
              $msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
              $msg_erro["campos"][] = "data";
          }
      }
  }

  if(!count($msg_erro['msg'])) {

    if(strlen($data_inicial) > 0){
        if ($login_fabrica == 42) {
            $tipo_data       = $_POST["tipo_data"];
            $campo_digitacao = " ,TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao";
            $order_digitacao = " ,tbl_os.data_digitacao ";

            if ($tipo_data == "digitacao") {
                $cond_data = " AND tbl_os.data_digitacao BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59' ";
            } else {
                $cond_data = " AND tbl_os.data_abertura BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}' ";    
            }
        } else {
            $cond_data = " AND tbl_os.data_abertura BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}' ";
        }
	}else{
      $cond_data = " AND tbl_os.data_abertura BETWEEN current_timestamp - interval '1 year' and current_timestamp";
	}

    if($status_os == 'aprovacao'){
      $cond_excluida   = 'AND tbl_os.excluida IS NOT TRUE';
      $cond_finalizada = 'AND tbl_os.finalizada IS NULL';
      $cond_extrato    = 'AND tbl_os_status.extrato IS NULL';
      $cond_garantia   = 'AND tbl_os.troca_garantia IS NOT TRUE';
    }

    switch ($status_os) {
      case 'aprovacao':

        if($intervencao == "intervencao_tecnica"){
          $ultimo_status = "62,147";
        }
        if($intervencao == "os_reincidente"){
          $ultimo_status = "67,68,70,95,134,157";
          $cond_auditado = " AND tbl_auditoria_os.liberada IS NULL
                                          AND tbl_auditoria_os.reprovada IS NULL";
        }
        if($intervencao == "pecas_excedentes"){
          $ultimo_status = "118";
        }

        if($intervencao == "produto_auditado" or $intervencao == "posto_auditado"){
          $cond_auditado = " AND tbl_auditoria_os.liberada IS NULL AND tbl_auditoria_os.cancelada IS NULL AND tbl_auditoria_os.reprovada IS NULL";
        }

        if($intervencao == "auditoria_os_pecas"){
          $cond_auditado = " AND tbl_auditoria_os.liberada IS NULL
                             AND tbl_auditoria_os.reprovada IS NULL";
        }

        if($intervencao == "lista_basica"){
          $cond_auditado = " AND tbl_auditoria_os.liberada IS NULL
                             AND tbl_auditoria_os.reprovada IS NULL";
        }
        if($intervencao == "cortesia_comercial"){
          $cond_auditado = " AND tbl_auditoria_os.liberada IS NULL
                             AND tbl_auditoria_os.reprovada IS NULL
                             AND observacao != 'Quantidade de OSs abertas no mês atual é maior que o dobro da média.'";
        }
        if($intervencao == "os_acima_media"){
          $cond_auditado = " AND tbl_auditoria_os.liberada IS NULL
                             AND tbl_auditoria_os.reprovada IS NULL
                             AND tbl_auditoria_os.observacao='Quantidade de OSs abertas no mês atual é maior que o dobro da média.'";
        }

        break;
      case 'aprovadas':

        if($intervencao == "produto_auditado" or $intervencao == "posto_auditado"){
          $cond_auditado = " AND tbl_auditoria_os.liberada IS NOT NULL";
        }

        if($intervencao == "auditoria_os_pecas"){
          $cond_auditado = " AND tbl_auditoria_os.liberada IS NOT NULL";
        }

        if($intervencao == "lista_basica" OR $intervencao == "os_reincidente"){
          $cond_auditado = " AND tbl_auditoria_os.liberada IS NOT NULL";
        }

        if($intervencao == "cortesia_comercial"){
          $cond_auditado = " AND tbl_auditoria_os.liberada IS NOT NULL
                             AND observacao != 'Quantidade de OSs abertas no mês atual é maior que o dobro da média.'";
        }

        if($intervencao == "os_acima_media"){
          $cond_auditado = " AND tbl_auditoria_os.liberada IS NOT NULL
                             AND tbl_auditoria_os.observacao='Quantidade de OSs abertas no mês atual é maior que o dobro da média.'";
        }

        if($intervencao == "intervencao_tecnica"){
          $ultimo_status = "64";
        }
        if($intervencao == "os_reincidente"){
          $ultimo_status = "19";
        }
        if($intervencao == "pecas_excedentes"){
          $ultimo_status = "187";
        }
        break;

      case 'reprovadas':
        if($intervencao == "produto_auditado" or $intervencao == "posto_auditado"){
          $cond_auditado = " AND tbl_auditoria_os.reprovada IS NOT NULL";
        }

        if($intervencao == "auditoria_os_pecas"){
          $cond_auditado = " AND tbl_auditoria_os.reprovada IS NOT NULL";
        }

        if($intervencao == "lista_basica" or $intervencao == "os_reincidente"){
          $cond_auditado = " AND tbl_auditoria_os.reprovada IS NOT NULL";
        }
        if($intervencao == "cortesia_comercial"){
          $cond_auditado = " AND tbl_auditoria_os.reprovada IS NOT NULL
                             AND observacao != 'Quantidade de OSs abertas no mês atual é maior que o dobro da média.'";
        }

        if($intervencao == "os_acima_media"){
          $cond_auditado = " AND tbl_auditoria_os.reprovada IS NOT NULL
                             AND observacao = 'Quantidade de OSs abertas no mês atual é maior que o dobro da média.'";
        }

        if($intervencao == "intervencao_tecnica"){
          $ultimo_status = "81,201";
        }
        if($intervencao == "os_reincidente"){
          $ultimo_status = "131";
        }
        if($intervencao == "pecas_excedentes"){
          $ultimo_status = "185";
        }

        $sql_intervencao = "SELECT tbl_auditoria_os.os,
                                   tbl_auditoria_os.data_input AS data_auditoria
                      INTO TEMP tmp_os_intervencao
                      FROM tbl_auditoria_os
                      WHERE tbl_auditoria_os.reprovada IS NOT NULL
                      ORDER BY tbl_auditoria_os.data_input DESC";
        break;
    }

    switch ($intervencao) {

        case 'produto_auditado':
            $sql_intervencao = "SELECT tbl_auditoria_os.os,
                                   tbl_auditoria_os.data_input AS data_auditoria
                      INTO TEMP tmp_os_intervencao
                      FROM tbl_auditoria_os
                      WHERE tbl_auditoria_os.auditoria_status = 3
                      $cond_auditado
                      ORDER BY tbl_auditoria_os.data_input DESC
                      ";
            break;

        case 'posto_auditado':
        case 'os_acima_media':
        case 'cortesia_comercial':
            $sql_intervencao = "SELECT tbl_auditoria_os.os,
                                    tbl_auditoria_os.data_input AS data_auditoria
                              INTO TEMP tmp_os_intervencao
                              FROM tbl_auditoria_os
                             WHERE tbl_auditoria_os.auditoria_status = 6
                               $cond_auditado
                             ORDER BY tbl_auditoria_os.data_input DESC
                      ";

        break;

        case 'lista_basica':
            if($login_fabrica == 30){
                $campos_esmaltec = ", tbl_auditoria_os.justificativa";
            }

            $sql_intervencao = "SELECT tbl_auditoria_os.os,
                                    tbl_auditoria_os.data_input AS data_auditoria
                                $campos_esmaltec
                              INTO TEMP tmp_os_intervencao
                              FROM tbl_auditoria_os
                             WHERE tbl_auditoria_os.auditoria_status = 4
                               $cond_auditado
                             ORDER BY tbl_auditoria_os.data_input DESC
                      ";

            break;

        case 'auditoria_os_pecas':
        case 'os_reincidente':
            if($login_fabrica == 30){
                $campos_esmaltec = ", tbl_auditoria_os.justificativa";
            }
            $sql_intervencao = "SELECT os,
                        tbl_auditoria_os.data_input AS data_auditoria
                                        $campos_esmaltec
                                        INTO TEMP tmp_os_intervencao
                                        FROM tbl_auditoria_os
                                        LEFT JOIN tbl_os USING(os)
                                       WHERE
                                       tbl_os.fabrica = $login_fabrica
                                       AND tbl_auditoria_os.auditoria_status in (1)
                                       $cond_auditado;";
            break;

        case 'intervencao_tecnica':
            $sql_intervencao = "SELECT intervencao.os,
                                        intervencao.data AS data_auditoria
                           INTO TEMP tmp_os_intervencao
                                FROM (
                                SELECT ultimo_status.os, data, (
                                    SELECT status_os
                                        FROM tbl_os_status
                                        JOIN tbl_os USING(os)
                                    WHERE tbl_os_status.os             = ultimo_status.os
                                        AND tbl_os_status.fabrica_status = $login_fabrica
                                        AND status_os IN (62,147,64,81,201)
                                        $cond_excluida
                                        $cond_finalizada
                                        $cond_data
                                ORDER BY os_status DESC LIMIT 1) AS ultimo_status_os
                                    FROM (
                                    SELECT DISTINCT os, data
                                        FROM tbl_os_status
                                        JOIN tbl_os USING(os)
                                    WHERE tbl_os_status.fabrica_status = $login_fabrica
                                        AND status_os IN (62,147,64,81,201)
                                        $cond_excluida
                                        $cond_finalizada
                                        $cond_data
                                    ) ultimo_status) intervencao
                            WHERE intervencao.ultimo_status_os IN ($ultimo_status)";
            break;

        case 'os_reincidente':
            $sql_intervencao = "SELECT  intervencao.os,
                                        intervencao.data AS data_auditoria
                           INTO TEMP tmp_os_intervencao
                                FROM (
                            SELECT ultima.os, data, (
                                SELECT status_os
                                    FROM tbl_os_status
                                    JOIN tbl_os USING(os)
                                    WHERE status_os IN (19,67,68,70,95,131,134,157)
                                    AND tbl_os_status.os = ultima.os
                                    AND tbl_os_status.fabrica_status = tbl_os.fabrica
                                    AND tbl_os.fabrica = $login_fabrica
                                    AND tbl_os.os_reincidente IS TRUE
                                    $cond_extrato
                                    $cond_excluida
                                    $cond_data
                                    ORDER BY os_status DESC LIMIT 1) AS ultimo_status_os
                                FROM (
                                SELECT DISTINCT os, data
                                    FROM tbl_os_status
                                    JOIN tbl_os USING(os)
                                    WHERE status_os IN (19,67,68,70,95,131,134,157)
                                    AND tbl_os_status.fabrica_status = tbl_os.fabrica
                                    AND tbl_os.fabrica = $login_fabrica
                                    AND tbl_os.os_reincidente IS TRUE
                                    $cond_extrato
                                    $cond_excluida
                                    $cond_data
                                ) ultima) intervencao
                        WHERE intervencao.ultimo_status_os IN ($ultimo_status)";
            break;

        case 'pecas_excedentes':
            $sql_intervencao = "
                        SELECT  intervencao.os,
                                intervencao.data AS data_auditoria
                    INTO TEMP   tmp_os_intervencao
                    FROM (
                    SELECT ultimo_status.os, data, (
                        SELECT status_os
                            FROM tbl_os_status
                            JOIN tbl_os USING(os)
                        WHERE tbl_os_status.os             = ultimo_status.os
                            AND tbl_os_status.fabrica_status = $login_fabrica
                            AND status_os IN (118,185,187)
                            $cond_data
                            $cond_excluida
                            $cond_garantia
                    ORDER BY os_status DESC LIMIT 1) AS ultimo_status_os
                        FROM (
                        SELECT DISTINCT os, data
                            FROM tbl_os_status
                            JOIN tbl_os USING(os)
                        WHERE tbl_os_status.fabrica_status = $login_fabrica
                            AND status_os IN (118,185,187)
                            $cond_data
                            $cond_excluida
                            $cond_garantia
                        ) ultimo_status) intervencao
                WHERE intervencao.ultimo_status_os IN ($ultimo_status)";
            break;
    }

    $campo_extra = '';

    if ($login_fabrica == 104 and $status_os == "listar_todas") {
        $sql_intervencao_tecnica = "
            SELECT intervencao.os,
                intervencao.data AS data_auditoria,
                'intervencao_tecnica|Intervenção Técnica'::text AS auditoria
            INTO TEMP tmp_os_intervencao_tecnica
            FROM (
                SELECT ultimo_status.os, data, (
                    SELECT status_os
                    FROM tbl_os_status
                    JOIN tbl_os USING(os)
                    WHERE tbl_os_status.os = ultimo_status.os
                    AND tbl_os_status.fabrica_status = $login_fabrica
                    AND status_os IN (62,147,64,81,201)
                    AND tbl_os.excluida IS NOT TRUE
                    AND tbl_os.finalizada IS NULL
                    AND tbl_os.data_abertura BETWEEN current_timestamp - interval '1 year' and current_timestamp
                    ORDER BY os_status DESC LIMIT 1
                ) AS ultimo_status_os
                FROM (
                    SELECT DISTINCT os, data
                    FROM tbl_os_status
                    JOIN tbl_os USING(os)
                    WHERE tbl_os_status.fabrica_status = $login_fabrica
                    AND status_os IN (62,147,64,81,201)
                    AND tbl_os.excluida IS NOT TRUE
                    AND tbl_os.finalizada IS NULL
                    AND tbl_os.data_abertura BETWEEN current_timestamp - interval '1 year' and current_timestamp
                ) ultimo_status
            ) intervencao
            WHERE intervencao.ultimo_status_os IN (62,147); ";

        $sql_os_reincidente = "
            SELECT os,
                tbl_auditoria_os.data_input AS data_auditoria,
                'os_reincidente|OS Reincidente'::text AS auditoria
            INTO TEMP tmp_os_os_reincidente
            FROM tbl_auditoria_os
            LEFT JOIN tbl_os USING(os)
            WHERE tbl_os.fabrica = $login_fabrica
            AND tbl_auditoria_os.auditoria_status = 1
            AND tbl_auditoria_os.liberada IS NULL
            AND tbl_auditoria_os.reprovada IS NULL; ";

        $sql_pecas_excedentes = "
            SELECT intervencao.os,
                intervencao.data AS data_auditoria,
                'pecas_excedentes|Peças Excedentes'::text AS auditoria
            INTO TEMP tmp_os_pecas_excedentes
            FROM (
                SELECT ultimo_status.os, data, (
                    SELECT status_os
                    FROM tbl_os_status
                    JOIN tbl_os USING(os)
                    WHERE tbl_os_status.os = ultimo_status.os
                    AND tbl_os_status.fabrica_status = $login_fabrica
                    AND status_os IN (118,185,187)
                    AND tbl_os.data_abertura BETWEEN current_timestamp - interval '1 year' and current_timestamp
                    AND tbl_os.excluida IS NOT TRUE
                    AND tbl_os.troca_garantia IS NOT TRUE
                    ORDER BY os_status DESC LIMIT 1
                ) AS ultimo_status_os
                FROM (
                    SELECT DISTINCT os, data
                    FROM tbl_os_status
                    JOIN tbl_os USING(os)
                    WHERE tbl_os_status.fabrica_status = $login_fabrica
                    AND status_os IN (118,185,187)
                    AND tbl_os.data_abertura BETWEEN current_timestamp - interval '1 year' and current_timestamp
                    AND tbl_os.excluida IS NOT TRUE
                    AND tbl_os.troca_garantia IS NOT TRUE
                ) ultimo_status
            ) intervencao
            WHERE intervencao.ultimo_status_os = 118; ";

        $sql_posto_auditado = "
            SELECT tbl_auditoria_os.os,
                tbl_auditoria_os.data_input AS data_auditoria,
                'sql_posto_auditado|Posto Auditado'::text AS auditoria
            INTO TEMP tmp_os_posto_auditado
            FROM tbl_auditoria_os
            WHERE tbl_auditoria_os.auditoria_status = 6
            AND tbl_auditoria_os.liberada IS NULL
            AND tbl_auditoria_os.cancelada IS NULL
            AND tbl_auditoria_os.reprovada IS NULL
            ORDER BY tbl_auditoria_os.data_input DESC; ";

        $sql_produto_auditado = "
            SELECT tbl_auditoria_os.os,
                tbl_auditoria_os.data_input AS data_auditoria,
                'produto_auditado|Produto Auditado'::text AS auditoria
            INTO TEMP tmp_os_produto_auditado
            FROM tbl_auditoria_os
            WHERE tbl_auditoria_os.auditoria_status = 3
            AND tbl_auditoria_os.liberada IS NULL
            AND tbl_auditoria_os.cancelada IS NULL
            AND tbl_auditoria_os.reprovada IS NULL
            ORDER BY tbl_auditoria_os.data_input DESC; ";

        $sql_intervencao = "
            $sql_intervencao_tecnica
            $sql_os_reincidente
            $sql_pecas_excedentes
            $sql_posto_auditado
            $sql_produto_auditado

            SELECT * INTO TEMP tmp_os_intervencao FROM (
                    (SELECT * FROM tmp_os_intervencao_tecnica)
                    UNION ALL
                    (SELECT * FROM tmp_os_os_reincidente)
                    UNION ALL
                    (SELECT * FROM tmp_os_pecas_excedentes)
                    UNION ALL
                    (SELECT * FROM tmp_os_posto_auditado)
                    UNION ALL
                    (SELECT * FROM tmp_os_produto_auditado)
                ) AS osi";

        $campo_extra = ', tmp_os_intervencao.auditoria ';

    }

    $res_intervencao = pg_query($con, $sql_intervencao);

    if($status_os == "aprovacao" or $status_os == "listar_todas"){
      $cond_fechamento = "AND tbl_os.data_fechamento IS NULL AND tbl_os.excluida IS NOT TRUE";
    }

    if(!empty($posto)) {
      $cond_posto = " AND tbl_os.posto = {$posto} ";
    }

    if(strlen($os) > 0) {
      $cond_os = "AND tbl_os.os = $os";
    }

    if($login_fabrica == 30){
        $campos_sql = "tmp_os_intervencao.justificativa, ";
        $groupby_sql = "tmp_os_intervencao.justificativa, ";
    }

    $osFlag = false;
    if (strlen(trim($os)) > 0) {
        $cond_data = "";
        $cond_fechamento = "";
        $cond_posto = "";
        $cond_admin_sap = "";
        $osFlag = true;
    }

    $sql = "SELECT distinct on (tbl_os.os)
              tbl_os.os ,
              TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
              TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
	           tbl_os.posto ,
	           tbl_os.sua_os,
              TO_CHAR(tmp_os_intervencao.data_auditoria,'DD/MM/YYYY') AS data_auditoria,
              $campos_sql
              tbl_posto.nome AS posto_nome ,
              tbl_posto.estado,
              (
                SELECT TO_CHAR(tbl_auditoria_os.liberada,'DD/MM/YYYY HH24:MI')
                FROM tbl_auditoria_os
                WHERE tbl_auditoria_os.os = tbl_os.os
                ORDER BY tbl_auditoria_os.data_input DESC
                LIMIT 1
              ) AS liberada,
              (
                SELECT TO_CHAR(tbl_auditoria_os.reprovada,'DD/MM/YYYY HH24:MI')
                FROM tbl_auditoria_os
                WHERE tbl_auditoria_os.os = tbl_os.os
                ORDER BY tbl_auditoria_os.data_input DESC
                LIMIT 1
              ) AS reprovada,
              tbl_posto_fabrica.codigo_posto AS codigo_posto,
              tbl_posto_fabrica.contato_email AS posto_email ,
              tbl_produto.referencia AS produto_referencia ,
              tbl_produto.descricao AS produto_descricao ,
              tbl_os_extra.os_reincidente
              $campo_extra
              $campo_digitacao
            FROM tbl_os
            JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os
            JOIN tmp_os_intervencao ON tmp_os_intervencao.os = tbl_os.os
            JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
            JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
            JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
            LEFT JOIN tbl_os_status ON tbl_os.os = tbl_os_status.os
            LEFT JOIN tbl_auditoria_os ON tbl_os.os = tbl_auditoria_os.os
           WHERE tbl_os.fabrica = $login_fabrica
            $cond_data
            $cond_fechamento
            $cond_posto
            $cond_os
            $cond_admin_sap
            GROUP BY tbl_os.os,tbl_os.data_abertura,
            tbl_os.data_fechamento,
            tmp_os_intervencao.data_auditoria,
            $groupby_sql
            tbl_os.posto,
            tbl_os.sua_os,
            tbl_posto.nome,
            tbl_auditoria_os.liberada,
            tbl_auditoria_os.reprovada,
            tbl_posto.estado,
            tbl_posto_fabrica.codigo_posto,
            tbl_posto_fabrica.contato_email,
            tbl_produto.referencia,
            tbl_produto.descricao,
            tbl_os_extra.os_reincidente
            $campo_extra
            $order_digitacao
            ORDER BY tbl_os.os";
    $resSubmit = pg_query($con, $sql);

    if ($osFlag === true) {
        $data_aprovacaoFlag = pg_fetch_result($resSubmit, 0, 'liberada');
        $data_reprovacaoFlag = pg_fetch_result($resSubmit, 0, 'reprovada');
    }
  }
  // EXCEL //
  if ($_POST['gerar_excel']) {

    if (pg_num_rows($resSubmit) > 0) {
      $data = date("d-m-Y-H:i");

      $fileName = "relatorio_auditoria_geral-{$data}.xls";

      $file = fopen("/tmp/{$fileName}", "w");
      if($login_fabrica == 30){
        if($status_os <> 'aprovacao'){
            $colspan = "10";
        }else{
            $colspan = "9";
        }
      }else if ($login_fabrica == 42) {
            $colspan = "6";
      } else {
            $colspan = "7";
      }
      $thead = "
        <table border='1'>
          <thead>
            <tr>
              <th colspan='$colspan' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
                RELATÓRIO DE AUDITORIA GERAL
              </th>
            </tr>
            <tr>
              <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>OS</th>
              <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Abertura</th>";
              if($login_fabrica == 30){
                $thead .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Auditoria</th>";
              }

              if ($login_fabrica == 42) {
                $thead .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Digitação</th>";
              }
        if($status_os <> 'aprovacao'){
          $thead .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Fechamento</th></th>";
        }

        if ($status_os == 'aprovadas') {
            $thead .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data aprovação</th></th>";
        } else if ($status_os == 'reprovadas') {
            $thead .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data reprovação</th></th>";
        }

        $thead .=" <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Posto</th>";

        if($login_fabrica == 30){
            $thead .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>UF</th>";
        }

        $thead .="<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Produto</th>";

        if($login_fabrica == 30){
            $thead .="<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Observação</th>";
        }


        if($intervencao == "os_reincidente"){
          $thead .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>OS Reincidente</th>";
        }
        $thead .= "</tr>
          </thead>
          <tbody>";
      fwrite($file, $thead);

      for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {
        $os                 = pg_fetch_result($resSubmit, $i, 'os');
        $sua_os             = pg_fetch_result($resSubmit, $i, 'sua_os');
        $data_abertura      = pg_fetch_result($resSubmit, $i, 'data_abertura');
        $data_fechamento    = pg_fetch_result($resSubmit, $i, 'data_fechamento');
        $data_auditoria     = pg_fetch_result($resSubmit, $i, 'data_auditoria');
        if($login_fabrica == 30){
            $justificativa     = pg_fetch_result($resSubmit, $i, 'justificativa');
        }
        $posto              = pg_fetch_result($resSubmit, $i, 'posto');
        $codigo_posto       = pg_fetch_result($resSubmit, $i, 'codigo_posto');
        $posto_nome         = pg_fetch_result($resSubmit, $i, 'posto_nome');
        $posto_estado       = pg_fetch_result($resSubmit, $i, 'estado');
        $produto_referencia = pg_fetch_result($resSubmit, $i, 'produto_referencia');
        $produto_descricao  = pg_fetch_result($resSubmit, $i, 'produto_descricao');
        $os_reincidente     = pg_fetch_result($resSubmit, $i, 'os_reincidente');
        $data_aprovacao     = pg_fetch_result($resSubmit, $i, 'liberada');
        $data_reprovacao    = pg_fetch_result($resSubmit, $i, 'reprovada');


		if(!empty($os_reincidente)) {
				$sql = "SELECT sua_os FROM tbl_os where os = $os_reincidente ";
				$resr = pg_query($con,$sql);
				$sua_osr = pg_fetch_result($resr,0,0);
		}

        $body .= "<tr id='linha_$os'>
          <td class='tac'>{$sua_os}</td>
          <td class='tac'>{$data_abertura}</td>";
        if ($login_fabrica == 30) {
          $body .="<td class='tac'>{$data_auditoria}</td>";

        }

        if ($login_fabrica == 42) { /*HD - 4375307*/
            $data_digitacao = pg_fetch_result($resSubmit, $i, 'data_digitacao');
            $body           .="<td class='tac'>{$data_digitacao}</td>";
        }

        if($status_os <> "aprovacao"){
          $body .="<td class='tac'>{$data_fechamento}</td>";
        }

        if ($status_os == 'aprovadas') {
            $body .="<td class='tac'>{$data_aprovacao}</td>";
        } else if ($status_os == 'reprovadas') {
            $body .="<td class='tac'>{$data_reprovacao}</td>";
        }

        $body .="<td class='tal'>{$codigo_posto} - {$posto_nome}</td>";

        if ($login_fabrica == 30) {
            $body .=" <td class='tal'>{$posto_estado}</td>";
        }
        $body .=" <td class='tal'>{$produto_referencia} - {$produto_descricao}</td>
        ";

        if($login_fabrica == 30){
            $body .=" <td class='tal'>{$justificativa}</td>";
        }

        if($intervencao == "os_reincidente"){
          $body .= "<td class='tac'><a href='os_press.php?os={$os_reincidente}' target='_blank' >{$sua_osr}</a></td></td>";
        }
      }
      fwrite($file, $body);
      fwrite($file, "
            <tr>
              <th colspan='$colspan' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".pg_num_rows($resSubmit)." registros</th>
            </tr>
          </tbody>
        </table>
      ");

      fclose($file);

      if (file_exists("/tmp/{$fileName}")) {
        system("mv /tmp/{$fileName} xls/{$fileName}");

        echo "xls/{$fileName}";
      }
    }

    exit;
  }
  // FIM EXCEL //
}


$layout_menu = "auditoria";
$title = "AUDITORIA GERAL";
include 'cabecalho_new.php';


$plugins = array(
  "autocomplete",
  "datepicker",
  "shadowbox",
  "mask",
  "dataTable"
);

include("plugin_loader.php");

function ultima_interacao($os) {
  global $con, $login_fabrica;

  $select = "SELECT admin, posto FROM tbl_os_interacao WHERE fabrica = {$login_fabrica} AND os = {$os} AND interno IS NOT TRUE ORDER BY data DESC LIMIT 1";
  $result = pg_query($con, $select);

  if (pg_num_rows($result) > 0) {
    $admin = pg_fetch_result($result, 0, "admin");
    $posto = pg_fetch_result($result, 0, "posto");

    if (!empty($admin)) {
      $ultima_interacao = "fabrica";
    } else {
      $ultima_interacao = "posto";
    }
  }

  return $ultima_interacao;
}
?>

<style>

.legenda {
  display: inline-block;
  width: 36px;
  height: 18px;
  vertical-align: middle;
  margin-right: 5px;
  border-radius: 3px;
}

</style>


<script type="text/javascript">
  var hora = new Date();
  var engana = hora.getTime();

  $(function() {
    var table = new Object();
    table['table'] = '#resultado_auditoria_geral';
    table['type'] = 'full';
    $.dataTableLoad(table);

    $.datepickerLoad(Array("data_final", "data_inicial"));
    $.autocompleteLoad(Array("posto"));
    Shadowbox.init();

    $("span[rel=lupa]").click(function () {
      $.lupa($(this));
    });

  });

  function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
    $("#descricao_posto").val(retorno.nome);
  }


<?php
if ($login_fabrica == 42) {
?>
    function abreMotivoInteracao(os,acao) {

        if (os != undefined && os.length > 0) {
            Shadowbox.open({
                content: $("#DivRecusar").html().replace(/__NumeroOs__/, os+"|"+acao),
                player: "html",
                height: 135,
                width: 400,
                options: {
                enableKeys: false
                }
            });
        }
    }

    $(document).on("click","button.abreMotivo",function() {

        var os = $(this).attr("rel");
        var acao = $(this).attr("name");
        abreMotivoInteracao(os,acao);
    });

    $(document).on("keyup","#text_motivo",function () {
        $(this).next().html(200 - $(this).val().length);
    });

    $(document).on("click","#button_motivo",function () {
        var obj         = $(this).attr("rel");
        var dados       = obj.split("|");
        var os          = dados[0];
        var intervencao = dados[1];
        var posto       = dados[2];
        var acao        = dados[3];
        var motivo = $.trim($("#sb-container").find("textarea[name=text_motivo]").val());

        if (motivo.length == 0) {
            alert("Informe o motivo");
        } else if (os != undefined && os.length > 0) {
            $.ajax({
                url: "aprova_auditoria_geral.php",
                type: "post",
                dataType: "JSON",
                data: {
                    ajax: true,
                    motivo: motivo,
                    os: os,
                    posto: posto,
                    acao: acao,
                    intervencao: intervencao
                },
                beforeSend: function() {
                    $("#sb-container").find("div.conteudo").hide();
                    $("#sb-container").find("div.loading").show();
                }
            })
            .done(function (data) {
                $("#linha_"+os).hide();
                Shadowbox.close();
                alert(data.ok);

                $("#sb-container").find("div.loading").hide();
                $("#sb-container").find("div.conteudo").show();
            })
            .fail(function(){
                alert("Erro na ação da OS");
            });
        }
    });
<?php
} else {
?>
    $(document).on("click", "button[name=aprovar_os]", function () {
        var auditoria_geral = "f";
        <?php if ($login_fabrica == 104): ?>
        if ($("input[name=status_os]:checked").val() == "listar_todas") {
            auditoria_geral = "t";
        }
        <?php endif ?>
        if (ajaxAction()) {
            var obj = $(this).parent().find("input[name=aprovar]").val();
            var dados = obj.split("|");
            var os = dados[0];
            var intervencao = dados[1];
            var posto = dados[2];
            var that = $(this);
            var auditorias = '';

            if (auditoria_geral == "t") {
                auditorias = $("input[name=audit_" + os + "]").val();
            }

            $.ajax({
                url: "<?=$PHP_SELF?>",
                type: "POST",
                dataType: "JSON",
                data: {
                    btn_acao: "aprovar_os",
                    os: os,
                    intervencao: intervencao,
                    auditoria_geral: auditoria_geral,
                    auditorias: auditorias,
                    posto: posto
                }
            })
            .done(function (data) {
                alert(data.ok);
                if (auditoria_geral == "f") {
                    $(that).parents("tr").remove();
                } else {
                    $("._linha_" + os).remove();
                }
            })
            .fail(function(){
                alert("Não foi possível realizar a operação");
            });
        }
    });

    $(document).on("click", "button[name=button_recusar]", function() {
        var auditoria_geral = "f";
        <?php if ($login_fabrica == 104): ?>
        if ($("input[name=status_os]:checked").val() == "listar_todas") {
            auditoria_geral = "t";
        }
        <?php endif ?>
        var obj = $(this).attr("rel");
        var dados = obj.split("|");
        var os = dados[0];
        var intervencao = dados[1];
        var posto = dados[2];
        var auditorias = '';

        if (auditoria_geral == "t") {
            auditorias = $("input[name=audit_" + os + "]").val();
        }

        var motivo = $.trim($("#sb-container").find("textarea[name=text_motivo]").val());
        if (motivo.length == 0) {
            alert("Informe o motivo");
        } else if (os != undefined && os.length > 0) {
            $.ajax({
                url: "aprova_auditoria_geral.php",
                type: "post",
                dataType: "JSON",
                data: {
                    recusar: true,
                    motivo: motivo,
                    os: os,
                    auditoria_geral: auditoria_geral,
                    auditorias: auditorias,
                    posto: posto,
                    intervencao: intervencao
                },
                beforeSend: function() {
                    $("#sb-container").find("div.conteudo").hide();
                    $("#sb-container").find("div.loading").show();
                }
            })
            .done(function (data) {
                if (auditoria_geral == "f") {
                    $("#linha_"+os).hide();
                } else {
                    $("._linha_" + os).hide();
                }
                Shadowbox.close();
                alert(data.ok);

                $("#sb-container").find("div.loading").hide();
                $("#sb-container").find("div.conteudo").show();
            })
            .fail(function(){
                alert("Erro ao recusar OS");
            });
        } else {
            alert("Erro ao recusar OS");
        }
    });

    $(document).on("click", "button[name=recusar]", function () {
      var os = $(this).attr("rel");
      if (os != undefined && os.length > 0) {
        Shadowbox.open({
          content: $("#DivRecusar").html().replace(/__NumeroOs__/, os),
          player: "html",
          height: 135,
          width: 400,
          options: {
            enableKeys: false
          }
        });
      }
    });

<?php
}
?>



  // FIM RECUSAR //

  // INTERAGIR //
    $(document).on("click", "button[name=interagir]", function () {
      //var os = $(this).attr("rel");
      var os = $(this).attr("rel");
      if (os != undefined && os.length > 0) {
    <?php if ($login_fabrica == 30) { ?>
            var ob = $(this).attr("rel");
            var xdados = ob.split("|");
            var xos = xdados[0];
            
            Shadowbox.open({
                content: "relatorio_interacao_os.php?interagir=true&os="+xos,
                player: "iframe",
                width: 850,
                height: 600,
                title: "Ordem de Serviço "+xos
            });
    <?php } else { ?>
            Shadowbox.open({
              content: $("#DivInteragir").html().replace(/__NumeroOs__/, os),
              player: "html",
              height: 135,
              width: 400,
              options: {
                enableKeys: false
              }
            });
    <?php } ?>
      }
    });

    $(document).on("click", "button[name=button_interagir]", function() {
      var obj = $(this).attr("rel");
      var dados = obj.split("|");
      var os = dados[0];
      var intervencao = dados[1];
      var posto = dados[2];
      var interacao = $.trim($("#sb-container").find("textarea[name=text_interacao]").val());
      if (interacao.length == 0) {
        alert("Digite a interação");
      } else if (os != undefined && os.length > 0) {
        $.ajax({
          url: "aprova_auditoria_geral.php",
          type: "post",
          data: { interagir: true, interacao: interacao, os: os, intervencao: intervencao, posto: posto },
          beforeSend: function() {
            $("#sb-container").find("div.conteudo").hide();
            $("#sb-container").find("div.loading").show();
          },complete: function (data) {
            data = data.responseText;

            if (data.erro) {
              alert(data.erro);
            } else {

              $("#linha_"+os).find("td").css({ "background-color": "#FFDC4C" });

              //$("button[name=interagir][rel="+os+"]").parents("tr").find("td").css({ "background-color": "#FFDC4C" });
              Shadowbox.close();
            }

            $("#sb-container").find("div.loading").hide();
            $("#sb-container").find("div.conteudo").show();
          }
        });
      } else {
        alert("Erro ao interagir na OS");
      }
    });
  // FIM INTERAGIR //


  $(document).on("click", "div[name=mostrar_pecas]", function() {
      var linha = $(this);
      linha.next("#m_peca").css({"width":"360px"});
      linha.next("#m_peca").show();
      linha.attr('name', 'esconder_pecas');
      linha.html('<span class="label label-info">Esconder peças</span>');
      $(".acoes").css({"width":"380px"});
  });

  $(document).on("click", "div[name=esconder_pecas]", function() {
      var linha = $(this);
      linha.next("#m_peca").hide();
      linha.attr('name', 'mostrar_pecas');
      linha.html('<span class="label label-info">Mostrar peças</span>');
      $(".acoes").css({"width":"280px"});
  });

</script>


<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
    <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>

    <!-- DATA -->
    <div class='row-fluid'>
      <div class='span2'></div>
        <div class='span4'>
          <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
            <label class='control-label' for='data_inicial'>Data Inicial</label>
            <div class='controls controls-row'>
              <div class='span4'>
                <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
              </div>
            </div>
          </div>
        </div>
      <div class='span4'>
        <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
          <label class='control-label' for='data_final'>Data Final</label>
          <div class='controls controls-row'>
            <div class='span4'>
              <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
            </div>
          </div>
        </div>
      </div>
      <div class='span2'></div>
    </div>
    <!-- FIM - DATA -->

    <!-- POSTO -->
    <div class='row-fluid'>
      <div class='span2'></div>
      <div class='span4'>
        <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
          <label class='control-label' for='codigo_posto'>Código Posto</label>
          <div class='controls controls-row'>
            <div class='span7 input-append'>
              <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
              <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
              <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
            </div>
          </div>
        </div>
      </div>
      <div class='span4'>
        <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
          <label class='control-label' for='descricao_posto'>Nome Posto</label>
          <div class='controls controls-row'>
            <div class='span12 input-append'>
              <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
              <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
              <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
            </div>
          </div>
        </div>
      </div>
      <div class='span2'></div>
    </div>
    <!-- FIM POSTO -->

    <!-- OS -->
    <div class='row-fluid'>
      <div class='span2'></div>
      <div class='span4'>
        <div class='control-group <?=(in_array("os", $msg_erro["campos"])) ? "error" : ""?>'>
          <label class='control-label' for='os'>Número OS</label>
          <div class='controls controls-row'>
            <input type="text" name="os" id="os" class='span8' value="<? echo $os ?>" >
          </div>
        </div>
      </div>
        <!-- INSPETOR -->
        <?php if($login_fabrica == 30){ $aAtendentes = hdBuscarAtendentes(); ?>

          <div class='span4'>
            <div class='control-group <?=(in_array("os", $msg_erro["campos"])) ? "error" : ""?>'>
              <label class='control-label' for='os'>Inspetor</label>
              <div class='controls controls-row'>
                <select class='frm' name="admin_sap" id="admin_sap">
                    <option value=""></option>
                    <?php foreach($aAtendentes as $aAtendente): ?>
                        <option value="<?php echo $aAtendente['admin']; ?>" <?php echo ($aAtendente['admin'] == $admin_sap) ? 'selected="selected"' : '' ; ?>><?php echo empty($aAtendente['nome_completo']) ? $aAtendente['login'] : $aAtendente['nome_completo'] ; ?></option>
                    <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>
          <div class='span2'></div>
        <?php } ?>
        <!-- FIM INSPETOR -->
    </div>
    <div class='row-fluid' style='margin:15px 0'>
        <div class='span2'></div>
        <div class='span8'>
            <div class='controls controls-row alert alert-warning'>
                Se o número da OS for digitado, as datas e o posto serão ignorados.
            </div>
        </div>
    <!-- FIM OS -->
    </div>
    <!-- AUDITORIA -->
    <div class='row-fluid'>
      <div class='span2'></div>
<?php
        if (in_array($login_fabrica, array(42))) {
?>
        <div class='span3'>
        <div class='control-group'>
          <label class='radio'>
            <input type="radio" name="intervencao" value="cortesia_comercial" <? if($intervencao == 'cortesia_comercial'){ ?> checked <?}?> >
            Cortesia comercial
          </label>
        </div>
      </div>
        <div class='span3'>
        <div class='control-group'>
          <label class='radio'>
            <input type="radio" name="intervencao" value="os_acima_media" <? if($intervencao == 'os_acima_media'){ ?> checked <?}?> >
            OS acima da média
          </label>
        </div>
      </div>

<?php
        }
        if (!in_array($login_fabrica, array(30,42))) {
?>
      <div class='span3'>
        <div class='control-group'>
          <label class='radio'>
            <input type="radio" name="intervencao" value="intervencao_tecnica" <? if($intervencao == 'intervencao_tecnica') { ?> checked <? } elseif (empty($intervencao)) { ?> checked <? }?> >
            Intervenção Técnica
          </label>
        </div>
      </div>

      <div class='span3'>
        <div class='control-group'>
          <label class='radio'>
            <input type="radio" name="intervencao" value="os_reincidente" <? if($intervencao == 'os_reincidente'){ ?> checked <?}?> >
            OS Reincidente
          </label>
        </div>
      </div>
      <div class='span3'>
        <div class='control-group'>
          <label class='radio'>
            <input type="radio" name="intervencao" value="pecas_excedentes" <? if($intervencao == 'pecas_excedentes'){ ?> checked <?}?> >
            Peças Excedentes
          </label>
        </div>
      </div>
      <? } ?>

      <? if (in_array($login_fabrica, array(30))) { ?>

      <div class='span3'>
        <div class='control-group'>
          <label class='radio'>
            <input type="radio" name="intervencao" value="os_reincidente" <? if($intervencao == 'os_reincidente'){ ?> checked <?}?> >
            Carência 90 dias
          </label>
        </div>
      </div>
      <div class='span3'>
        <div class='control-group'>
          <label class='radio'>
            <input type="radio" name="intervencao" value="lista_basica" <? if($intervencao == 'lista_basica'){ ?> checked <?}?> >
            Lista Básica
          </label>
        </div>
      </div>
      <? } ?>

      <div class='span1'></div>
    </div>
    <?if (!in_array($login_fabrica, array(30,42))) { ?>
    <div class='row-fluid'>
      <div class='span2'></div>
      <div class='span3'>
        <div class='control-group'>
          <label class='radio'>
            <input type="radio" name="intervencao" value="posto_auditado" <? if($intervencao == 'posto_auditado'){ ?> checked <?}?> >
            Posto Auditado
          </label>
        </div>
      </div>
      <div class='span3'>
        <div class='control-group'>
          <label class='radio'>
            <input type="radio" name="intervencao" value="produto_auditado" <? if($intervencao == 'produto_auditado'){ ?> checked <?}?> >
            Produto Auditado
          </label>
        </div>
      </div>
      <div class='span4'></div>
    </div>
    <? } ?>
    <!-- FIM AUDITORIA -->

    <!-- FILTRO AUDITORIA -->
    <div class='row-fluid'>
      <div class='span2'></div>
      <div class='span3'>
        <div class='control-group'>
          <label class='radio'>
            <input type="radio" name="status_os" value="aprovacao" <? if($status_os == 'aprovacao' OR $filtro_auditoria == ''){ ?> checked <?}?> >
            Em aprovação
          </label>
        </div>
      </div>
      <div class='span3'>
        <div class='control-group'>
          <label class='radio'>
            <input type="radio" name="status_os" value="aprovadas" <? if($status_os == 'aprovadas'){ ?> checked <?}?> >
            Aprovadas
          </label>
        </div>
      </div>
      <div class='span3'>
        <div class='control-group'>
          <label class='radio'>
            <input type="radio" name="status_os" value="reprovadas" <? if($status_os == 'reprovadas'){ ?> checked <?}?> >
            Reprovadas
          </label>
        </div>
      </div>
      <div class='span1'></div>
    </div>

    <?php if ($login_fabrica == 42) { /*HD - 4375307*/ ?>
        <div class='row-fluid'>
            <div class='span2'></div>
                <div class='span3'>
                    <div class='control-group'>
                        <label>
                            Tipo de Data
                        </label>
                    </div>
                </div>
            <div class='span1'></div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
                <div class='span3'>
                    <div class='control-group'>
                        <label class='radio'>
                            <input type="radio" name="tipo_data" <?echo ($_POST["tipo_data"] == "abertura") ? "checked" : ""?> value="abertura">
                            Data Abertura
                        </label>
                    </div>
                </div>
                <div class='span3'>
                    <div class='control-group'>
                        <label class='radio'>
                            <input type="radio" name="tipo_data" <?echo ($_POST["tipo_data"] == "digitacao") ? "checked" : ""?> value="digitacao">
                            Data Digitação
                        </label>
                    </div>
                </div>
            <div class='span1'></div>
        </div>
    <?php } ?>
    <?php if ($login_fabrica == 104): ?>
    <div class='row-fluid'>
      <div class='span2'></div>
      <div class='span3'>
        <div class='control-group'>
          <label class='radio'>
            <input type="radio" name="status_os" value="listar_todas" <? if($status_os == 'listar_todas'){ ?> checked <?}?> >
            Listar todas em aprovação
          </label>
        </div>
      </div>
    </div>
    <?php endif ?>
    <?php if(in_array($login_fabrica, array(30,42,104))){

        $checked = ($_POST['gerarexcel']) ? "checked" : "";

        ?>
        <div class='row-fluid'>
          <div class='span2'></div>
          <div class='span3'>
            <div class='control-group'>
              <label class='radio'>
                <input type="checkbox" name="gerarexcel" value="t" <?= $checked ?>>
                Gerar Excel
              </label>
            </div>
          </div>
        </div>
    <?php } ?>
    <!-- FIM FILTRO AUDITORIA -->
    <p><br/>
      <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
      <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p><br/>
</form>
</div>

<?php
if (isset($resSubmit)) {
    if (pg_num_rows($resSubmit) > 0) {
      echo "<br />";

      if (pg_num_rows($resSubmit) > 500 && in_array($login_fabrica, array(30,42,104))) {
        $count = 500;
        ?>
        <div id='registro_max'>
          <h6>Em tela serão mostrados no máximo 500 registros, para visualizar todos os registros baixe o arquivo Excel no final da tela.</h6>
        </div>
      <?php
      } else {
        $count = pg_num_rows($resSubmit);
      }
    ?>
      <table id="resultado_auditoria_geral" class='table table-striped table-bordered table-fixed' >
        <thead>

          <?php
            if($status_os == "aprovacao"){
          ?>
            <tr>
              <td colspan="6">
                <span class="legenda" style="background: #FFDC4C;" ></span>Fábrica interagiu<br />
                <span class="legenda" style="background: #A6D941;" ></span>Posto interagiu<br />
              </td>
            </tr>
          <?php
            }
          ?>

          <tr class='titulo_coluna'>
            <th>OS</th>
            <th>Abertura</th>
<?PHP
            if ($login_fabrica == 30 or !empty($campo_extra)) {
?>
            <th>Auditoria</th>
<?php
            }
            
            if ($login_fabrica == 42) {
                ?> <th>Digitação</th> <?
            }

            if($status_os <> 'aprovacao' and $status_os <> 'listar_todas'){
              echo "<th>Fechamento</th>";
            }

            if ($status_os == 'aprovadas') { ?>
                <th>Data Aprovação</th>
            <?php
            } else if ($status_os == 'reprovadas') { ?>
                <th>Data Reprovação</th>
            <?php
            }
            ?>
            <th>Posto</th>
<?PHP
            if ($login_fabrica == 30) {
?>
            <th>UF</th>
<?PHP
            }
?>
            <th>Produto</th>
<?php if($login_fabrica == 30 ){?>
            <th>Observação</th>
<?php }?>
            <?php
            if($intervencao == "os_reincidente"){
              echo "<th>OS Reincidente</th>";
            }

            if(in_array($intervencao,array("intervencao_tecnica","cortesia_comercial")) AND $status_os == "aprovacao"){
                echo "<th>Peças</th>";
            }
            
            if ($login_fabrica == 104 && $osFlag === true && strlen($data_aprovacaoFlag) > 0 && strlen($os) > 0) { /*HD - 6353914*/
                $aux_sql = "SELECT os FROM tbl_os WHERE fabrica = 104 AND os = $os AND status_checkpoint = 2";
                $aux_res = pg_query($con, $aux_sql);
                $aux_val = pg_fetch_result($aux_res, 0, 'os');

                if (strlen($aux_val) > 0) {
                    $osFlag = false;
                }
            }

            if(($osFlag === false) AND ($status_os == "aprovacao" or $status_os == "listar_todas")) {
              echo "<th class='acoes'>Ações</th>";
            } elseif ($osFlag === true AND strlen(trim($data_aprovacaoFlag)) == 0 AND strlen(trim($data_reprovacaoFlag)) == 0) {
                echo "<th class='acoes'>Ações</th>";
            }
            ?>

          </tr>
        </thead>
        <tbody>
          <?php
          $os_auditorias = array();

          for ($i = 0; $i < $count; $i++) {
            $os                 = pg_fetch_result($resSubmit, $i, 'os');
            $sua_os             = pg_fetch_result($resSubmit, $i, 'sua_os');
            $data_abertura      = pg_fetch_result($resSubmit, $i, 'data_abertura');
            $data_auditoria     = pg_fetch_result($resSubmit, $i, 'data_auditoria');
            if($login_fabrica == 30){
                $justificativa     = utf8_decode(pg_fetch_result($resSubmit, $i, 'justificativa'));
            }
            $data_fechamento    = pg_fetch_result($resSubmit, $i, 'data_fechamento');
            $posto              = pg_fetch_result($resSubmit, $i, 'posto');
            $codigo_posto       = pg_fetch_result($resSubmit, $i, 'codigo_posto');
            $posto_nome         = pg_fetch_result($resSubmit, $i, 'posto_nome');
            $posto_email        = pg_fetch_result($resSubmit, $i, 'posto_email');
            $posto_estado       = pg_fetch_result($resSubmit, $i, 'estado');
            $produto_referencia = pg_fetch_result($resSubmit, $i, 'produto_referencia');
            $produto_descricao  = pg_fetch_result($resSubmit, $i, 'produto_descricao');
            $os_reincidente     = pg_fetch_result($resSubmit, $i, 'os_reincidente');
            $data_aprovacao     = pg_fetch_result($resSubmit, $i, 'liberada');
            $data_reprovacao    = pg_fetch_result($resSubmit, $i, 'reprovada');

            if (!empty($campo_extra)) {
                $arr_auditoria = explode("|", pg_fetch_result($resSubmit, $i, 'auditoria'));
                $auditoria = $arr_auditoria[1];
            }

			if(!empty($os_reincidente)) {
				$sql = "SELECT sua_os FROM tbl_os where os = $os_reincidente ";
				$resr = pg_query($con,$sql);
				$sua_osr = pg_fetch_result($resr,0,0);
			}

            if($status_os == "aprovacao"){
              $ultima_interacao = ultima_interacao($os);
              switch ($ultima_interacao) {
                case "fabrica":
                  $cor = "#FFDC4C";
                  break;

                case "posto":
                  $cor = '#A6D941';
                  break;

                default:
                  $cor = "#FFFFFF";
                  break;
              }
            }else{
              $cor = "#FFFFFF";
            }

            $body = "<tr id='linha_$os' class='_linha_{$os}'>
                  <td class='tac' style='background-color: $cor' ><a href='os_press.php?os={$os}' target='_blank' >{$sua_os}</a></td>
                  <td class='tac' style='background-color: $cor'>{$data_abertura}</td>";
            if ($login_fabrica == 30 or !empty($campo_extra)) {

                $body .="<td class='tac' style='background-color: $cor'>";

                if (!empty($campo_extra)) {
                    $os_auditorias[$os][] = $arr_auditoria[0];
                    $body .= $auditoria;
                } else {
                    $body .= $data_auditoria;
                }

                $body .= "</td>";
            }

            if ($login_fabrica == 42) { /*HD - 4375307*/
                $data_digitacao = pg_fetch_result($resSubmit, $i, 'data_digitacao');
                $body          .= "<td class='tac' style='background-color: $cor'>{$data_digitacao}</td>";
            }

            if($status_os <> "aprovacao" and $status_os <> "listar_todas"){
              $body .= "<td class='tac'>{$data_fechamento}</td>";
            }

            if ($status_os == "aprovadas") {
                $body .= "<td class='tac'>{$data_aprovacao}</td>";
            } else if ($status_os == "reprovadas") {
                $body .= "<td class='tac'>{$data_reprovacao}</td>";
            }

            $body .="<td class='tal' style='background-color: $cor'>{$codigo_posto} - {$posto_nome}</td>";
            if ($login_fabrica == 30) {

                $body .="<td class='tal' style='background-color: $cor'>{$posto_estado}</td>";
            }
            $body .="<td class='tal' style='background-color: $cor'>{$produto_referencia} - {$produto_descricao}</td>
                ";
            if($login_fabrica == 30){
                $body .="<td class='tal' style='background-color: $cor'>$justificativa</td>";
            }
            if($intervencao == "os_reincidente"){
              $body .= "<td class='tac' style='background-color: $cor'><a href='os_press.php?os={$os_reincidente}' target='_blank' >{$sua_osr}</a></td></td>";
            }

            if(in_array($intervencao,array("intervencao_tecnica","cortesia_comercial")) AND $status_os == "aprovacao"){
                $sql_peca = "
                            SELECT  tbl_os_item.os_item                 ,
                                    tbl_os_item.preco                   ,
                                    tbl_peca.referencia AS referencia   ,
                                    tbl_peca.descricao  AS descricao    ,
                                    tbl_peca.peca       AS peca
                            FROM    tbl_os_produto
                            JOIN    tbl_os_item USING (os_produto)
                            JOIN    tbl_peca    USING (peca)
                            WHERE   tbl_os_produto.os = $os
                ";
              $res_peca = pg_query($con, $sql_peca);

              $resultado = pg_num_rows($res_peca);
              $quantas_pecas = $resultado;

				$pecas = '';
              if ($resultado > 0 ){
                $peca = trim(pg_fetch_result($res_peca, 0, 'peca'));


                for($j=0;$j<$resultado;$j++){


                  $peca_referencia  = trim(pg_fetch_result($res_peca, $j, 'referencia'));
                  $peca_descricao   = trim(pg_fetch_result($res_peca, $j, 'descricao'));
                  $peca_preco       = trim(pg_fetch_result($res_peca, $j, 'preco'));

                  $pecas[$peca_referencia]->desc    = $peca_descricao;
                  $pecas[$peca_referencia]->preco   = number_format($peca_preco,2,',','');
                  $pecas[$peca_referencia]->id      = trim(pg_fetch_result($res_peca, $j, 'peca'));
                  $pecas[$peca_referencia]->cont++;
                }
              }

              $body .= "<td style='background-color: $cor'>";

                if(!empty($pecas)){
                  $body .= "
                    <div name='mostrar_pecas' rel='$os' style='width: 100%; text-align: center; cursor: pointer;'><span class='label label-info'> Mostrar peças</span></div>
                    <table style='display:none;' style='width:385px;' id='m_peca' class='table table-bordered'>
                      <thead>
                        <tr class='titulo_coluna'>
                            <th>Nome</th>
                            <th>Qtde</th>";
                    if ($login_fabrica == 42) {
                        $body .= "
                            <th>Preço</th>
                        ";
                    }
                  $body .= "</tr>
                      </thead>
                      <tbody>";

                    foreach ($pecas as $peca_id => $peca) {
                        $body .="<tr>
                          <td class='peca'><a href='peca_cadastro.php?peca=$peca->id' target='_blank'>$peca->desc</a></td>
                          <td class='peca'> $peca->cont</td>";
                        if ($login_fabrica == 42) {
                            $body .= "
                            <td class='peca'>$peca->preco</td>
                            ";
                        }
                        $body .="</tr>";
                    }


                  $body .= "</table>";
                  if(!in_array($login_fabrica,array(42))){
                  $body .= "<div name='add_remove' style='width: 100%; text-align: center; cursor: pointer;'>
                    <a href='os_item.php?os=$os' target='_blank'><span class='label label-info'> Adicionar/Remover Peças</span></a>
                  </div>
                  ";
                    }
                }
              $body .= "</td>";
            }

            if ($status_os == "aprovacao" or $status_os == "listar_todas" or $osFlag === true) {
                if ($login_fabrica != 42) {
                    if ((strlen(trim($data_aprovacao)) == 0 AND strlen(trim($data_reprovacao)) == 0) || ($login_fabrica == 104 && $osFlag === false)) { /*HD - 6353914*/
                        $body.= "<td class='tac' style='background-color: $cor'><input type='hidden' name='aprovar' value='$os|$intervencao|$posto'>
                        <button type='button' name='aprovar_os' class='btn btn-small btn-success' title='Aprovar OS' >Aprovar</button>
                        <button type='button' rel='$os|$intervencao|$posto' name='recusar' class='btn btn-small btn-danger'>Recusar</button>
                        <button type='button' rel='$os|$intervencao|$posto' name='interagir' class='btn btn-small btn-primary'>Interagir</button>
                        </td>";
                    }
                } else {
                    $body .= "
                        <td class='tac' style='background-color: $cor'><input type='hidden' name='aprovar' value='$os|$intervencao|$posto'>
                            <button type='button' name='aprovar_os'     class='btn btn-small btn-success abreMotivo' rel='$os|$intervencao|$posto' title='Aprovar OS' >Aprovar OS</button>
                            <button type='button' name='aprovar_pecas'  class='btn btn-small btn-warning abreMotivo' rel='$os|$intervencao|$posto' title='Aprovar Peças' >Aprovar Peças</button>
                            <button type='button' name='garantia'       class='btn btn-small btn-info abreMotivo'    rel='$os|$intervencao|$posto' title='Garantia' >Garantia</button>
                            <button type='button' name='recusar'        class='btn btn-small btn-danger abreMotivo'  rel='$os|$intervencao|$posto' >Recusar</button>
                        </td>
                    ";
                }
            }

            $body .= "</tr>";
            echo $body;
          }
          ?>
        </tbody>
      </table>

      <?php
      if ($login_fabrica == 104) {
        echo '<div>';
        foreach ($os_auditorias as $key => $val) {
            echo '<input type="hidden" name="audit_' . $key . '" value="' . implode('|', $val) . '" />' . "\n";
        }
        echo '</div>';
      }
      ?>

      <div id="DivRecusar" style="display: none;" >
        <div class="loading tac" style="display: none;" ><img src="imagens/loading_img.gif" /></div>
        <div class="conteudo" >
          <div class="titulo_tabela" >Informe o Motivo</div>

          <div class="row-fluid">
            <div class="span12">
              <div class="controls controls-row">
                <textarea name="text_motivo" id="text_motivo" class="span12" maxlength="200"></textarea>
                <label style="margin-top: -9px;margin-bottom: -21px;color: darkgrey" id="contador">200</label>
              </div>
            </div>
          </div>
            <p><br/>
<?php
            if ($login_fabrica != 42) {
?>
            <button type="button" name="button_recusar" class="btn btn-block btn-danger" rel="__NumeroOs__" >Recusar</button>
<?php
            } else {
?>
            <button type="button" id = "button_motivo" name="button_motivo" class="btn btn-block btn-success" rel="__NumeroOs__" >Gravar</button>
<?php
            }
?>
          </p><br/>
        </div>
      </div>

      <div id="DivInteragir" style="display: none;" >
        <div class="loading tac" style="display: none;" ><img src="imagens/loading_img.gif" /></div>
        <div class="conteudo" >
          <div class="titulo_tabela" >Interagir na OS</div>

          <div class="row-fluid">
            <div class="span12">
              <div class="controls controls-row">
                <textarea name="text_interacao" class="span12"></textarea>
              </div>
            </div>
          </div>

          <p><br/>
            <button type="button" name="button_interagir" class="btn btn-primary btn-block" rel="__NumeroOs__" >Interagir</button>
          </p><br/>
        </div>
      </div>

      <?php
      if ($count > 50) {
      ?>
        <script>
          $.dataTableLoad({ table: "#resultado_os_atendimento" });
        </script>
      <?php
      }
      ?>

      <br />

      <?php
        if($gerarexcel){
        $jsonPOST = excelPostToJson($_POST);
      ?>
      <div id='gerar_excel' class="btn_excel">
        <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
        <span><img src='imagens/excel.png' /></span>
        <span class="txt">Gerar Arquivo Excel</span>
      </div>
    <?php
        }

    }else{
      echo '
      <div class="container">
      <div class="alert">
            <h4>Nenhum resultado encontrado</h4>
      </div>
      </div>';
    }
  }



include 'rodape.php';?>

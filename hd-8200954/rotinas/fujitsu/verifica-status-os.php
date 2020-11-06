<?php
/**
 *
 * verifica-status-os
 *
 * Verifica posi��o e atrasos
 * de Ordens de Servi�o Fujitsu
 *
 * @author William Ap. Brandino
 * @since 2018-01-05
 */
error_reporting(E_ALL ^ E_NOTICE);

define('ENV', 'producao');

try {
    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';
    include dirname(__FILE__) . '/../../classes/Posvenda/Fabrica.php';
    include dirname(__FILE__) . '/../../classes/Posvenda/Os.php';
    include dirname(__FILE__) . '/../../class/communicator.class.php';

    $fabrica = 138;
    $fabrica_nome = 'fujitsu';

    date_default_timezone_set('America/Sao_Paulo');
    $now = date('Ymd_His');

    if(ENV == 'teste'){
        $log_dir = dirname(__FILE__) . '/fujitsu_teste/logs';;
        $arq_log = $log_dir . '/relatorio-status-os-' . $now . '.log';
        $err_log = $log_dir . '/relatorio-status-os-err-' . $now . '.log';
    } else {
        $log_dir = '/tmp/' . $fabrica_nome;
        $arq_log = $log_dir . '/relatorio-status-os-' . $now . '.log';
        $err_log = $log_dir . '/relatorio-status-os-err-' . $now . '.log';
    }

    if (!is_dir($log_dir)) {
        if (!mkdir($log_dir, 0777, true)) {
            throw new Exception("ERRO: N�o foi poss�vel criar logs. Falha ao criar diret�rio: $log_dir");
        }
    }

    $emailsFujitsu = array(
        'alan.gregorio@br.fujitsu-general.com',
        'ana.batista@br.fujitsu-general.com',
        'rogerio.nascimento@br.fujitsu-general.com',
        'takao.matsumura@br.fujitsu-general.com'
    );

    function interacaoOs($con,$fabrica,$codigo_posto,$os)
    {
        $dados = json_decode($os,TRUE);

        $sqlPosto = "SELECT posto FROM tbl_posto WHERE cnpj = '$codigo_posto'";
        $resPosto = pg_query($con,$sqlPosto);
        $posto = pg_fetch_result($resPosto,0,posto);

        pg_query($con,"BEGIN TRANSACTION");

        $t = 0;
        $sql = "INSERT INTO tbl_os_interacao (os,comentario,interno,exigir_resposta,fabrica,posto,programa) VALUES ";
        foreach ($dados as $valor) {
            $t++;
            $sql .= "(".$valor['f1'].",'Os com pend�ncias de pe�as. Verificar e-mail do posto e contatar a f�brica',TRUE,TRUE,$fabrica,$posto,'verifica-status-os')";

            $sql .= ($t == count($dados)) ? ";" : ",";
        }
        $res = pg_query($con,$sql);

        if (pg_last_error($con)) {
            pg_query($con,"ROLLBACK TRANSACTION");
            return false;
        }

        pg_query($con,"COMMIT TRANSACTION");
        return true;
    }

    function envioEmail($mensagem,$assunto,$destino)
    {
        $mailer = new TcComm('smtp@posvenda');

        $mailer->setEmailSubject($assunto);
        $mailer->addToEmailBody($mensagem);
        $mailer->setEmailFrom("garantia@br.fujitsu-general.com");
        $mailer->addEmailDest($destino);

        if (!$mailer->sendMail()) {
            return false;
        }

        return true;
    }

    /**
     * - formataDadosOs
     * Formata as informa��es das OS para montagem das
     * mensagens de email
     */
    function formataDadosOs($oss,$funcao)
    {
        $montar = json_decode($oss,TRUE);

        switch ($funcao) {
            case "os_sem_peca":
                $msg = "
                    <table cellspacing='2' cellpadding='2' style='border:1px solid #000; width:600px;'>
                        <thead>
                            <tr>
                                <th>OS</th>
                                <th>Data Abertura</th>
                                <th>Usu�rio</th>
                            <tr>
                        </thead>
                        <tbody>";
                foreach($montar as $os) {
                    $msg .= "
                            <tr>
                                <td style='text-align:center;'>".$os['f1']."</td>
                                <td style='text-align:center;'>".$os['f2']."</td>
                                <td style='text-align:center;'>".$os['f3']."</td>
                            </tr>
                    ";
                }
                $msg .= "
                        </tbody>
                    </table>
                ";
                break;
            case "os_faturadas_sem_fechar":
            case "os_aberta_com_nota":
                $msg = "
                    <table cellspacing='2' cellpadding='2' style='border:1px solid #000; width:600px;'>
                        <thead>
                            <tr>
                                <th>OS</th>
                                <th>Data Abertura</th>
                                <th>Usu�rio</th>
                                <th>NF Sa�da</th>
                                <th>Data NF</th>
                            <tr>
                        </thead>
                        <tbody>";
                foreach($montar as $os) {
                    $msg .= "
                            <tr>
                                <td style='text-align:center;'>".$os['f1']."</td>
                                <td style='text-align:center;'>".$os['f2']."</td>
                                <td style='text-align:center;'>".$os['f3']."</td>
                                <td style='text-align:center;'>".$os['f4']."</td>
                                <td style='text-align:center;'>".$os['f5']."</td>
                            </tr>
                    ";
                }
                $msg .= "
                        </tbody>
                    </table>
                ";
                break;
            case "os_aberta_sem_nota":
                $msg = "
                    <table cellspacing='2' cellpadding='2' style='border:1px solid #000; width:600px;'>
                        <thead>
                            <tr>
                                <th>Posto</th>
                                <th>OS</th>
                                <th>Data Abertura</th>
                                <th>Consumidor</th>
                            </tr>
                        </thead>
                        <tbody>
                ";
                foreach($montar as $posto => $dados) {
                    foreach($dados as $itens) {
                        $msg .= "
                                <tr>
                                    <td style='text-align:center;'>".$posto."</td>
                                    <td style='text-align:center;'>".$itens['f1']."</td>
                                    <td style='text-align:center;'>".$itens['f2']."</td>
                                    <td style='text-align:center;'>".$itens['f3']."</td>
                                </tr>
                        ";
                    }
                }
                $msg .= "
                        </tbody>
                    </table>
                ";
                break;
            case "postos_sem_email":
                foreach($montar as $dados) {
                    $msg .= "
                        ".$dados['f1']." - Abertura: ".$dados['f2']." - Consumidor: ".$dados['f3']."<br />
                    ";
                }
                break;
        }
        return $msg;
    }

    /**
     * - Aviso dos status pendentes
     * das ordens de servi�o aos admins,
     * pelos postos que n�o possuem email
     * cadastrado
     */
    function enviaDadosPostoSemEmail($postos,$assunto,$emailsFujitsu)
    {
        $tiposAssuntos = array(
            'osSemPecas'        => "OS SEM PE�AS ABERTAS H� MAIS DE CINCO DIAS",
            'osAbertaFaturada'  => "OS FATURADAS H� MAIS DE DEZ DIAS",
            'osAbertaComNota'   => "OS ABERTAS H� MAIS DE 25 DIAS SEM "
        );

        $postosSemEmail = array_keys($postos);

        $assuntoEmail = "FUJITSU - POSTOS SEM EMAIL PARA ENVIO DE INFORMA��O";
        $msgEmail = "
            Prezados,

            Postos sem email foram detectados com os seguintes problemas:

            VERIFICA��O: ".$tiposAssuntos[$assunto]."

            Postos: ";

        foreach ($postos as $posto=>$dados) {
            $msgEmail .= "
                $posto - ".$dados['nome']."
                Contato: ".$dados['contato_nome']."
                Qtde OS na situa��o: ".$dados['qtde']."
                OS: ".formataDadosOs(json_encode($dados['os']),'postos_sem_email')."

            -------------------------------------------

            ";
        }
        $msgEmail .= "
            Favor, entrar em contato com os postos para regulariza��o das pend�ncias.

            Atenciosamente,

            Departamento Garantia
            garantia@br.fujitsu-general.com
            Fujitsu General do Brasil Ltda
        ";

        if (envioEmail($msgEmail,$assuntoEmail,$emailsFujitsu)) {
            return true;
        }
    }

    /**
     * - Envio de email para postos
     * com OS's abertas sem pe�as
     * h� mais de cinco dias
     */
    function osSemPecas ($con,$fabrica,$emailsFujitsu)
    {
        $sqlOsSemPecas = "
            WITH os_sem_peca AS (
                SELECT  DISTINCT
                        tbl_os.os,
                        tbl_os.sua_os,
                        data_abertura,
                        tbl_os.consumidor_nome,
                        COUNT(tbl_os_item.os_item)
                FROM    tbl_os
                JOIN    tbl_os_produto USING(os)
           LEFT JOIN    tbl_os_item USING(os_produto)
                WHERE   tbl_os.fabrica = $fabrica
                AND     tbl_os.finalizada IS NULL
                AND     tbl_os.data_abertura > '2018-01-01'
          GROUP BY      tbl_os.os,
                        tbl_os.sua_os,
                        tbl_os.data_abertura,
                        tbl_os.consumidor_nome
                HAVING  COUNT(tbl_os_item.os_item) = 0
            )
            SELECT  DISTINCT
                    tbl_posto_fabrica.codigo_posto,
                    tbl_posto.nome,
                    tbl_posto_fabrica.contato_nome,
                    tbl_posto_fabrica.contato_email,
                    JSONB_AGG((os_sem_peca.sua_os,TO_CHAR(os_sem_peca.data_abertura,'DD/MM/YYYY'),os_sem_peca.consumidor_nome))   AS os_sem_pecas,
                    COUNT(os_sem_peca.os)       AS qtde_os_sem_pecas
            FROM    os_sem_peca
            JOIN    tbl_os              USING(os)
            JOIN    tbl_posto           USING(posto)
            JOIN    tbl_posto_fabrica   USING(posto)
            WHERE   tbl_posto_fabrica.fabrica = $fabrica
            AND     os_sem_peca.data_abertura::DATE + INTERVAL '5 days' < CURRENT_DATE
            AND     tbl_posto_fabrica.credenciamento IN ('CREDENCIADO','EM DESCREDENCIAMENTO')
      GROUP BY      tbl_posto_fabrica.codigo_posto,
                    tbl_posto.nome,
                    tbl_posto_fabrica.contato_nome,
                    tbl_posto_fabrica.contato_email
      ORDER BY      tbl_posto_fabrica.contato_email;
        ";
        $resOsSemPecas = pg_query($con,$sqlOsSemPecas);

        if (pg_last_error($con)) {
            $erro["msg"][] = "Erro ao montar tmp_intervencao_fabrica\n";
            $erro["msg"][] = pg_last_error();
            $erro["msg"][] = "\n=================================================\n\n";
            $msg_erro = "Erro ao montar tmp_intervencao_fabrica";
        }
        while ($resultado = pg_fetch_object($resOsSemPecas)) {
            if (empty($resultado->contato_email)) {
                $postoSemEmail[$resultado->codigo_posto]['nome']            = $resultado->nome;
                $postoSemEmail[$resultado->codigo_posto]['contato_nome']    = $resultado->contato_nome;
                $postoSemEmail[$resultado->codigo_posto]['os']              = json_decode($resultado->os_sem_pecas,TRUE);
                $postoSemEmail[$resultado->codigo_posto]['qtde']            = $resultado->qtde_os_sem_pecas;

                continue;
            }

            $assuntoEmail = "FUJITSU - OS ABERTAS H� MAIS DE CINCO DIAS";
            $msgEmail = "
                <p>
                Prezado Sr.(a) ".addslashes($resultado->contato_nome).",
                </p>
                <br />
                <p>
                Informamos que a(s) OS com os dados abaixo, foi aberta h�
                mais de 5 dias sem lan�amento de pe�as e sem fechamento da OS.
                </p>
                <br />
                ".formataDadosOs($resultado->os_sem_pecas,'os_sem_peca')."
                <br />
                <p>
                Caso a OS tenha sido aberta por engano, favor cancelar a OS
                </p>
                <br />
                Atenciosamente,
                <br />
                <p>
                Departamento Garantia <br />
                garantia@br.fujitsu-general.com<br />
                Fujitsu General do Brasil Ltda<br />
                </p>
            ";

            if (!envioEmail($msgEmail,$assuntoEmail,$resultado->contato_email)) {
                $msg_erro[] = "N�o foi poss�vel enviar email para o Posto: ".$resultado->codigo_posto;
            }
        }

        if (is_array($postoSemEmail)) {
            enviaDadosPostoSemEmail($postoSemEmail,'osSemPecas',$emailsFujitsu);
        }

        return true;
    }

    /**
     * - Envio de Email para intera��o
     * Nas OS faturadas e n�o finalizadas 10 dias ap�s
     * o faturamento.
     */
    function osAbertaFaturada($con,$fabrica,$emailsFujitsu)
    {
        $sqlOsAbertaFaturada = "
            WITH os_faturada_sem_fechar AS (
                SELECT  DISTINCT
                        tbl_os.os,
                        tbl_os.sua_os,
                        tbl_os.data_abertura,
                        tbl_os.consumidor_nome,
                        tbl_faturamento.nota_fiscal,
                        tbl_faturamento.emissao
                FROM    tbl_os
                JOIN    tbl_os_produto          USING(os)
                JOIN    tbl_os_item             USING(os_produto)
                JOIN    tbl_pedido_item         USING(pedido_item)
                JOIN    tbl_faturamento_item    USING(pedido_item)
                JOIN    tbl_faturamento         USING(faturamento)
                WHERE   tbl_os.fabrica = $fabrica
                AND     tbl_os.finalizada IS NULL
                AND     tbl_os.data_abertura > '2018-01-01'
                AND     tbl_faturamento.emissao + INTERVAL '10 days' < CURRENT_DATE
            )
            SELECT  DISTINCT
                    tbl_posto_fabrica.codigo_posto,
                    tbl_posto.nome,
                    tbl_posto_fabrica.contato_email,
                    tbl_posto_fabrica.contato_nome,
                    JSONB_AGG((
                        os_faturada_sem_fechar.sua_os,
                        TO_CHAR(os_faturada_sem_fechar.data_abertura,'DD/MM/YYYY'),
                        os_faturada_sem_fechar.consumidor_nome,
                        os_faturada_sem_fechar.nota_fiscal,
                        TO_CHAR(os_faturada_sem_fechar.emissao,'DD/MM/YYYY')
                    ))    AS os_faturadas_sem_fechar,
                    COUNT(os_faturada_sem_fechar.os)        AS qtde_os_faturadas_sem_fechar
            FROM    os_faturada_sem_fechar
            JOIN    tbl_os              USING(os)
            JOIN    tbl_posto           USING(posto)
            JOIN    tbl_posto_fabrica   USING(posto)
            WHERE   tbl_posto_fabrica.fabrica = $fabrica
            AND     tbl_posto_fabrica.credenciamento IN ('CREDENCIADO','EM DESCREDENCIAMENTO')
      GROUP BY      tbl_posto_fabrica.codigo_posto,
                    tbl_posto.nome,
                    tbl_posto_fabrica.contato_email,
                    tbl_posto_fabrica.contato_nome
      ORDER BY      tbl_posto_fabrica.contato_email;
        ";
        $resOsAbertaFaturada = pg_query($con,$sqlOsAbertaFaturada);

        while ($resultado = pg_fetch_object($resOsAbertaFaturada)) {
            if (empty($resultado->contato_email)) {
                $postoSemEmail[$resultado->codigo_posto]['nome']            = $resultado->nome;
                $postoSemEmail[$resultado->codigo_posto]['contato_nome']    = $resultado->contato_nome;
                $postoSemEmail[$resultado->codigo_posto]['os']              = json_decode($resultado->os_faturadas_sem_fechar,TRUE);
                $postoSemEmail[$resultado->codigo_posto]['qtde']            = $resultado->qtde_os_faturadas_sem_fechar;

                continue;
            }
            $assuntoEmail = "FUJITSU - OS ABERTAS FATURADAS H� MAIS DE DEZ DIAS";
            $msgEmail = "
                <p>
                Prezado Sr.(a) ".addslashes($resultado->contato_nome).",
                </p>

                <br />

                <p>
                Informamos que as pe�as solicitadas pela(s) OS com os dados abaixo,
                j� foram faturados h� mais de 10 dias e a OS continua aberta.
                </p>

                <br />

                <p>
                Favor, tomar as provid�ncias devidas e explicar o que est� ocorrendo na �rea de intera��es
                do TELECONTROL da(s) OS abaixo.
                </p>

                ".formataDadosOs($resultado->os_faturadas_sem_fechar,'os_faturadas_sem_fechar')."
                <p>
                Caso necessite de suporte t�cnico, favor ligar no 11-3149-5700 op��o 4-7
                </p>

                <br />
                Atenciosamente,
                <br />
                Departamento Garantia<br />
                garantia@br.fujitsu-general.com<br />
                Fujitsu General do Brasil Ltda<br />
            ";

            if (!envioEmail($msgEmail,$assuntoEmail,$resultado->contato_email)) {
                $msg_erro[] = "N�o foi poss�vel enviar email para o Posto: ".$resultado->codigo_posto;
            }

            interacaoOs($con,$fabrica,$resultado->codigo_posto,$resultado->os_faturadas_sem_fechar);
        }

        if (is_array($postoSemEmail)) {
            enviaDadosPostoSemEmail($postoSemEmail,'osAbertaFaturada',$emailsFujitsu);
        }

        return true;
    }

    /**
     * - osAbertaComNota
     * Envio de email para os postos
     * serem alertados de OS abertas h� mais de
     * vinte cinco dias, faturadas, mas n�o finalizadas
     */
    function osAbertaComNota ($con,$fabrica,$emailsFujitsu)
    {
        $sqlOsAberta = "
        WITH os_aberta_com_nota AS (
            SELECT  DISTINCT
                    tbl_os.os,
                    tbl_os.sua_os,
                    tbl_os.data_abertura,
                    tbl_os.consumidor_nome,
                    tbl_faturamento.nota_fiscal,
                    tbl_faturamento.emissao
            FROM    tbl_os
            JOIN    tbl_os_produto          USING(os)
            JOIN    tbl_os_item             USING(os_produto)
            JOIN    tbl_pedido_item         USING(pedido_item)
            JOIN    tbl_faturamento_item    USING(pedido_item)
            JOIN    tbl_faturamento         USING(faturamento)
            WHERE   tbl_os.data_abertura::DATE + INTERVAL '25 days' < CURRENT_DATE
            AND     tbl_os.data_abertura > '2018-01-01'
            AND     tbl_os.fabrica = $fabrica
            AND     tbl_os.finalizada IS NULL
        )
        SELECT  DISTINCT
                tbl_posto_fabrica.codigo_posto,
                tbl_posto.nome,
                tbl_posto_fabrica.contato_email,
                tbl_posto_fabrica.contato_nome,
                JSONB_AGG((
                    os_aberta_com_nota.sua_os,
                    TO_CHAR(os_aberta_com_nota.data_abertura,'DD/MM/YYYY'),
                    os_aberta_com_nota.consumidor_nome,
                    os_aberta_com_nota.nota_fiscal,
                    TO_CHAR(os_aberta_com_nota.emissao,'DD/MM/YYYY')
                ))   AS os_aberta_com_nota,
                COUNT(os_aberta_com_nota.os) AS qtde_os_aberta
        FROM    os_aberta_com_nota
        JOIN    tbl_os USING(os)
        JOIN    tbl_posto USING(posto)
        JOIN    tbl_posto_fabrica USING(posto,fabrica)
        WHERE   tbl_posto_fabrica.fabrica = $fabrica
        AND     tbl_posto_fabrica.credenciamento IN ('CREDENCIADO','EM DESCREDENCIAMENTO')
  GROUP BY      tbl_posto_fabrica.codigo_posto,
                tbl_posto.nome,
                tbl_posto_fabrica.contato_email,
                tbl_posto_fabrica.contato_nome
  ORDER BY      tbl_posto_fabrica.contato_email
        ";
        $resOsAberta = pg_query($con,$sqlOsAberta);

        while ($resultado = pg_fetch_object($resOsAberta)) {
            if (empty($resultado->contato_email)) {
                $postoSemEmail[$resultado->codigo_posto]['nome']            = $resultado->nome;
                $postoSemEmail[$resultado->codigo_posto]['contato_nome']    = $resultado->contato_nome;
                $postoSemEmail[$resultado->codigo_posto]['os']              = json_decode($resultado->os_aberta_com_nota,TRUE);
                $postoSemEmail[$resultado->codigo_posto]['qtde']            = $resultado->os_aberta_com_nota;

                continue;
            }
            $assuntoEmail = "FUJITSU - OS ABERTAS FATURADAS H� MAIS DE VINTE CINCO DIAS";
            $msgEmail = "
                <p>
                Prezado Sr.(a) ".addslashes($resultado->contato_nome).",
                </p>
                <br />
                <p>
                Informamos que OS com os dados abaixo, continua em aberto h� mais de 25 dias.
                </p>
                <br />
                <p>
                Favor, tomar as provid�ncias devidas para atendimento ao usu�rio o mais r�pido poss�vel.<br />
                Caso j� tenha sido atendido, fechar a OS no TELECONTROL.
                </p>
                <br />

                ".formataDadosOs($resultado->os_aberta_com_nota,'os_aberta_com_nota')."

                <br />
                <p>
                Caso necessite de suporte t�cnico, favor ligar no 11-3149-5700 op��o 4-7
                </p>

                <br />
                Atenciosamente,
                <br />

                Departamento Garantia<br />
                garantia@br.fujitsu-general.com<br />
                Fujitsu General do Brasil Ltda<br />
            ";

            if (!envioEmail($msgEmail,$assuntoEmail,$resultado->contato_email)) {
                $msg_erro[] = "N�o foi poss�vel enviar email para o Posto: ".$resultado->codigo_posto;
            }
        }

        if (is_array($postoSemEmail)) {
            enviaDadosPostoSemEmail($postoSemEmail,'osAbertaComNota',$emailsFujitsu);
        }

        return true;
    }

    /**
     * - osAbertaSemNota
     * Envio de email para os admins
     * serem alertados de OS abertas h� mais de
     * vinte cinco dias, sem faturamento
     */
    function osAbertaSemNota($con,$fabrica,$emailsFujitsu)
    {
        $sqlOsSemNota = "
            WITH os_aberta_sem_nota AS (
                SELECT  DISTINCT
                        tbl_os.os,
                        tbl_os.sua_os,
                        tbl_os.data_abertura,
                        tbl_os.consumidor_nome,
                        JSONB_AGG(tbl_os_item.os_item) AS os_itens,
                        COUNT(tbl_faturamento_item.pedido_item)
                FROM    tbl_os
                JOIN    tbl_os_produto          USING(os)
                JOIN    tbl_os_item             USING(os_produto)
                JOIN    tbl_pedido_item         USING(pedido_item)
           LEFT JOIN    tbl_faturamento_item    USING(pedido_item)
                WHERE   tbl_os.data_abertura::DATE + INTERVAL '25 days' < CURRENT_DATE
                AND     tbl_os.data_abertura > '2018-01-01'
                AND     tbl_os.fabrica = $fabrica
                AND     tbl_os.finalizada IS NULL
				AND		tbl_pedido_item.qtde_cancelada= 0 
          GROUP BY      tbl_os.os,
                        tbl_os.sua_os,
                        tbl_os.data_abertura,
                        tbl_os.consumidor_nome
                HAVING  COUNT(tbl_faturamento_item.pedido_item) = 0
            )
            SELECT  DISTINCT
                    tbl_posto_fabrica.codigo_posto,
                    tbl_posto.nome,
                    JSONB_AGG((
                        os_aberta_sem_nota.sua_os,
                        TO_CHAR(os_aberta_sem_nota.data_abertura,'DD/MM/YYYY'),
                        os_aberta_sem_nota.consumidor_nome,
                        os_aberta_sem_nota.os_itens
                    ))   AS os_aberta_sem_nota
            FROM    os_aberta_sem_nota
            JOIN    tbl_os USING(os)
            JOIN    tbl_posto USING(posto)
            JOIN    tbl_posto_fabrica USING(posto,fabrica)
            WHERE   tbl_posto_fabrica.fabrica = $fabrica
            AND     tbl_posto_fabrica.credenciamento IN ('CREDENCIADO','EM DESCREDENCIAMENTO')
      GROUP BY      tbl_posto_fabrica.codigo_posto,
                    tbl_posto.nome
      ORDER BY      tbl_posto_fabrica.codigo_posto,
                    tbl_posto.nome

        ";
        $resOsSemNota = pg_query($con,$sqlOsSemNota);

        if (pg_num_rows($resOsSemNota) > 0) {
            while ($resultado = pg_fetch_object($resOsSemNota)) {

                $dadosOs[$resultado->codigo_posto] = json_decode($resultado->os_aberta_sem_nota,TRUE);

            }
            $assuntoEmail = "FUJITSU - OS ABERTAS SEM FATURAR H� MAIS DE VINTE CINCO DIAS";
            $msgEmail = "
                Prezados,
                <br />
                <p>
                As OS abaixo est�o h� mais de 25 dias em aberto e ainda n�o foram enviadas as pe�as para o
                credenciado.<br />
                Favor, tomar provid�ncias URGENTES para o atendimento o mais r�pido poss�vel.
                </p>

                ".formataDadosOs(json_encode($dadosOs),'os_aberta_sem_nota')."
                <br />
                Atenciosamente,
                <br />
                <br />

                Departamento Garantia<br />
                garantia@br.fujitsu-general.com<br />
                Fujitsu General do Brasil Ltda<br />
            ";

            if (!envioEmail($msgEmail,$assuntoEmail,$emailsFujitsu)) {
                $msg_erro[] = "N�o foi poss�vel enviar email.";
            }
        }

        return true;
    }

    /*
     *  - Execu��o das verifica��es
     * de Status das OS e envio de
     * e-mails
     */

    osSemPecas($con,$fabrica,$emailsFujitsu);
    osAbertaFaturada($con,$fabrica,$emailsFujitsu);
    osAbertaComNota($con,$fabrica,$emailsFujitsu);
    osAbertaSemNota($con,$fabrica,$emailsFujitsu);

} catch (Exception $e) {
    echo $e->getMessage();
}

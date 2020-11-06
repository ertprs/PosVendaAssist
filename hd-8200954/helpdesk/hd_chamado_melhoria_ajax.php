<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$acao = $_GET["acao"];
$hd_chamado_melhoria = $_GET["hd_chamado_melhoria"];
$justificativa = addslashes($_GET["justificativa"]);

if ($acao != "arquivo") {
    if (strlen($hd_chamado_melhoria) == 0) {
        $acao = "";
    }
    else {
        $hd_chamado_melhoria = intval($hd_chamado_melhoria);
        $sql = "
        SELECT
        hd_chamado_melhoria

        FROM
        tbl_hd_chamado_melhoria

        WHERE
        hd_chamado_melhoria=$hd_chamado_melhoria
        ";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res)) {
        }
        else {
            $acao = "";
        }
    }
}

switch($acao) {
    case "arquivo":
        $sql = "SELECT arquivo, descricao FROM tbl_arquivo WHERE status='ativo' AND descricao ILIKE '%" . $_GET["q"] . "%' ORDER BY descricao LIMIT 10";
        $res = pg_query($con, $sql);

        for ($i = 0; $i < pg_num_rows($res); $i++) {
            $dados = pg_fetch_row($res);
            $dados = implode("|", $dados);
            echo $dados . "\n";
        }
    break;

    case "Gravar Justificativa":
        if (strlen($justificativa)) {
            $sql = "
            SELECT
            nome_completo

            FROM
            tbl_admin

            WHERE
            admin=$login_admin
            ";
            $res = pg_query($con, $sql);
            $nome_completo = pg_result($res, 0, 0);
            $justificativa = "<font class=admin_nome_completo_css>$nome_completo: </font>" . $justificativa;

            $sql = "
            UPDATE
            tbl_hd_chamado_melhoria

            SET
            justificativa = (
                SELECT
                CASE WHEN justificativa IS NULL THEN
                '$justificativa'
                ELSE
                justificativa || '\n$justificativa'
                END

                FROM
                tbl_hd_chamado_melhoria
                WHERE
                hd_chamado_melhoria=$hd_chamado_melhoria
            )

            WHERE
            hd_chamado_melhoria=$hd_chamado_melhoria
            ";
            $res = pg_query($con, $sql);

            //A variável abaixo armazena qual o admin responsável por gerenciar as Melhorias
            //em Programas, normalmente o Tester, ele receberá e-mails de notificações
            $admin_responsavel_melhorias = 2310;

            $sql = "
            SELECT
            email

            FROM
            tbl_admin

            WHERE
            admin=$admin_responsavel_melhorias
            ";
            $res = pg_query($con, $sql);
            $email = pg_result($res, email);

            $sql = "
            SELECT
            justificativa

            FROM
            tbl_hd_chamado_melhoria

            WHERE
            hd_chamado_melhoria=$hd_chamado_melhoria
            ";
            $res = pg_query($con, $sql);
            $justificativa_completa = pg_result($res, 0, 0);
            $justificativa_completa = str_replace("\n", "<br>", $justificativa_completa);

            $mensagem = "A melhoria ID #$hd_chamado_melhoria teve sua justificativa atualizada, conforme abaixo:<br>
            <br>
            $justificativa_completa<br>
            <br>
            Suporte Telecontrol";

            $headers .= "MIME-Version: 1.0\n";
            $headers .= "Content-type: text/html; charset=iso-8859-1\n";
            $headers .= "To: $email" . "\r\n";
            $headers .= "From: Telecontrol Melhorias <suporte@telecontrol.com.br>";// . "\r\n";

            $titulo = "Melhorias: Justificativa ID #$hd_chamado_melhoria";

            $mailer->sendMail($email, $titulo, $mensagem, 'suporte@telecontrol.com.br');

            echo $hd_chamado_melhoria . "|" . $justificativa;
        }
        else {
            echo "falha";
        }
    break;

    case "Cancelar":
        $sql = "
        SELECT
        hd_chamado_melhoria

        FROM
        tbl_hd_chamado_melhoria

        WHERE
        hd_chamado_melhoria=$hd_chamado_melhoria
        AND admin=$login_admin
        AND validacao IS NULL
        AND hd_chamado IS NULL
        ";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res)) {
            $sql = "
            UPDATE
            tbl_hd_chamado_melhoria

            SET
            validacao=NOW()

            WHERE
            hd_chamado_melhoria=$hd_chamado_melhoria
            ";
            $res = pg_query($con, $sql);

            //Gravando registro do cancelamento na justificativa
            $sql = "
            SELECT
            nome_completo

            FROM
            tbl_admin

            WHERE
            admin=$login_admin
            ";
            $res = pg_query($con, $sql);
            $nome_completo = pg_result($res, 0, 0);
            $justificativa = "<font class=admin_nome_completo_css>$nome_completo: </font> Solicitação de melhoria cancelada em ";

            $sql = "
            UPDATE
            tbl_hd_chamado_melhoria

            SET
            justificativa = (
                SELECT
                CASE WHEN justificativa IS NULL THEN
                '$justificativa' || TO_CHAR(validacao, 'DD/MM/YYYY HH24:MI:SS')
                ELSE
                justificativa || '\n$justificativa' || TO_CHAR(validacao, 'DD/MM/YYYY HH24:MI:SS')
                END

                FROM
                tbl_hd_chamado_melhoria
                WHERE
                hd_chamado_melhoria=$hd_chamado_melhoria
            )

            WHERE
            hd_chamado_melhoria=$hd_chamado_melhoria
            ";
            $res = pg_query($con, $sql);

            $sql = "
            SELECT
            TO_CHAR(validacao, 'DD/MM/YYYY HH24:MI:SS') AS validacao

            FROM
            tbl_hd_chamado_melhoria

            WHERE
            hd_chamado_melhoria=$hd_chamado_melhoria
            ";
            $res = pg_query($con, $sql);
            $validacao = pg_result($res, 0, validacao);
            $justificativa .= $validacao;

            echo $hd_chamado_melhoria . "|" . $justificativa . "|" . $validacao;
        }
        else {
            echo "falha";
        }
    break;

    case "Validar":
        $sql = "
        SELECT
        tbl_hd_chamado_melhoria.hd_chamado_melhoria

        FROM
        tbl_hd_chamado_melhoria
        JOIN tbl_hd_chamado ON tbl_hd_chamado_melhoria.hd_chamado=tbl_hd_chamado.hd_chamado

        WHERE
        tbl_hd_chamado_melhoria.hd_chamado_melhoria=$hd_chamado_melhoria
        AND tbl_hd_chamado_melhoria.admin=$login_admin
        AND tbl_hd_chamado_melhoria.validacao IS NULL
        AND tbl_hd_chamado.status='Resolvido'
        ";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res)) {
            $sql = "
            UPDATE
            tbl_hd_chamado_melhoria

            SET
            validacao=NOW()

            WHERE
            hd_chamado_melhoria=$hd_chamado_melhoria
            ";
            $res = pg_query($con, $sql);

            //Gravando registro da validacao na justificativa
            $sql = "
            SELECT
            nome_completo

            FROM
            tbl_admin

            WHERE
            admin=$login_admin
            ";
            $res = pg_query($con, $sql);
            $nome_completo = pg_result($res, 0, 0);
            $justificativa = "<font class=admin_nome_completo_css>$nome_completo: </font> Solicitação de melhoria validada em ";

            $sql = "
            UPDATE
            tbl_hd_chamado_melhoria

            SET
            justificativa = (
                SELECT
                CASE WHEN justificativa IS NULL THEN
                '$justificativa' || TO_CHAR(validacao, 'DD/MM/YYYY HH24:MI:SS')
                ELSE
                justificativa || '\n$justificativa' || TO_CHAR(validacao, 'DD/MM/YYYY HH24:MI:SS')
                END

                FROM
                tbl_hd_chamado_melhoria
                WHERE
                hd_chamado_melhoria=$hd_chamado_melhoria
            )

            WHERE
            hd_chamado_melhoria=$hd_chamado_melhoria
            ";
            $res = pg_query($con, $sql);

            $sql = "
            SELECT
            TO_CHAR(validacao, 'DD/MM/YYYY HH24:MI:SS') AS validacao

            FROM
            tbl_hd_chamado_melhoria

            WHERE
            hd_chamado_melhoria=$hd_chamado_melhoria
            ";
            $res = pg_query($con, $sql);
            $validacao = pg_result($res, 0, validacao);
            $justificativa .= $validacao;

            echo $hd_chamado_melhoria . "|" . $justificativa . "|" . $validacao;
        }
        else {
            echo "falha";
        }
    break;

    default:
        echo "falha";
}

?>

<?php
// vim: sts=2 ts=2 sw=2 et
define ('APP_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR);
$no_pdo = true;
include_once APP_DIR . 'dbconfig.php';
include_once APP_DIR . 'includes/dbconnect-inc.php';
include_once APP_DIR . 'class/fn_sql_cmd.php';
include_once APP_DIR . 'class/json.class.php';

try {
    $opcoes = ['redefinir'=>0, 'confirmar'=>1, 'cancelar'=>2];

    $token         = $_REQUEST['token'];
    $codigo_posto  = $_REQUEST['codigo_posto'];
    $codigo_posto  = preg_replace('/[^[a-zA-Z0-9_.-]]/', '', $codigo_posto);
    $login_fabrica = 160;
    $login_posto   = pg_fetch_result(
        pg_query(
            $con,
            "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$codigo_posto'"
        ), 0, 0
    );

    if (!$login_posto) {
            $err = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$codigo_posto'";
        throw new Exception($err . " Posto não encontrado. Tente novamente ou contate com o Suporte da Telecontrol.", 404);
        // throw new Exception("Erro ao validar as informações de identificação. Por favor, tente novamente daqui alguns minutos.", 500);
    }

    $DBtoken = sha1($login_posto . $codigo_posto);

    if ($DBtoken !== $token) {
        throw new Exception("Erro ao validar o identificador. Verifique se o link é o que consta no e-mail. Se está correto, por favor entre em contato com o Suporte da Telecontrol.", 403);
    }

    $req = json_decode(json_encode($_POST)); // POST -> objet

    if (!$req->action)
        throw new Exception("Requisition error", 400);

    $OS = pg_fetch_object(pg_query($con, "SELECT status, opcao, observacoes FROM arquivo_acao2_dados WHERE os = ".$req->id));
    if (!is_object($OS))
        die ("ERRO: Identificador da OS não localizado.");

    $tableAttrs = array(
        'tableAttrs' => 'data-toggle="table" class="table table-condensed table-bordered table-hover table-striped "'
    );
    $obs = new Json($OS->observacoes);
    $where = 'WHERE ' . sql_where(['posto' => $login_posto, 'os' => $req->id]);

    switch ($req->action) {
        case 'comment':
            $obs->data[] = ['data' => is_date('now'), 'obs' => $req->text];
            $sql = "UPDATE arquivo_acao2_dados SET observacoes = '$obs' $where";
            $msg = "Texto salvo";
            break;

        case 'history':
            $hist = json_decode($OS->observacoes, true);
            foreach ($hist as $i => $row) $hist[$i]['data'] = is_date($row['data'], '', 'EUR');

            die (Convert(array2table($hist, 'Interações'), 'HTML-ENTITIES', 'utf8,Latin-1'));
            break; // sintaxe... ;-)

        case 'confirmar':
            $obs->data[] = ['data' => is_date('now'), 'obs' => "Posto solicitou ".$req->action." a OS."];
            $sql = "UPDATE arquivo_acao2_dados SET observacoes = '$obs', opcao = 1 $where";
            $msg = "Registro atualizado";
            break;

        case 'cancelar':
            $obs->data[] = ['data' => is_date('now'), 'obs' => "Posto solicitou ".$req->action." a OS."];
            $sql = "UPDATE arquivo_acao2_dados SET observacoes = '$obs', opcao = 2 $where";
            $msg = "Registro atualizado";
            break;
    }

    $res = pg_query($con, $sql);

    if (!is_resource($res))
        die ("ERRO: Não foi possível completar a ação. ".$sql);

    die($msg);

} catch (\Exception $e) {
    $error = [
        'code' => $e->getCode(),
        'msg'  => $e->getMessage()
    ];

    die("ERRO: " . $e->getMessage());
}


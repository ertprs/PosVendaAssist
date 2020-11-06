<?php
define ('APP_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR);
$no_pdo = true;
include_once APP_DIR . 'dbconfig.php';
include_once APP_DIR . 'includes/dbconnect-inc.php';
include_once APP_DIR . 'class/fn_sql_cmd.php';

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

    $acao = $_POST['action'];

    if (!$acao)
        throw new Exception("Requisition error", 400);

    $peca   = getPost('peca');
    $pedido = getPost('pedido');
    $posto  = $login_posto;
    $opcao  = $opcoes[$acao] ? : 0;

    if (!is_numeric($pedido) or !is_numeric($posto))
        throw new Exception("$posto - $pedido => Requisition error", 400);

    if (!is_numeric($peca)) {
        // Opera sobre o pedido inteiro.
        // `compact` não adiciona a variável no array se a variável não existe
        unset($peca);
    }
    $data_resposta = is_date('now');

    $upData = compact('opcao', 'data_resposta');
    $where = compact('posto', 'pedido', 'peca');
    $where['status!'] = true;

    $sql = sql_cmd('arquivo_acao1_dados', $upData, $where);

    if (!$sql[0] === 'U')
        throw new Exception("Ocorreu um problema ao atualizar o pedido.", 500);

    // die($sql);

    $res = pg_query($con, $sql);

    if (!is_resource($res))
        throw new Exception("Houve um problema ao atualizar o pedido.", 500);

    switch (pg_affected_rows($res)) {
        case 0:
            die ('Nenhum registro atualizado. '.$sql);
        break;
        case 1:
            if (!$peca)
                die("Registros atualizados");
            die ("Registro atualizado|".is_date($data_resposta, 'ISO', 'EUR'));
        break;
        default:
            die('Registros atualizados');
    }

} catch (\Exception $e) {
    $error = [
        'code' => $e->getCode(),
        'msg'  => $e->getMessage()
    ];

    die("ERRO: " . $e->getMessage());
}


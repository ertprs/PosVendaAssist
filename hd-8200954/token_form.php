<?php
/***********************************************************************************
 * Este include confere e gera uma __token__ para validar a origem                 *
 * e a autenticidade dos valores de uma requisição `POST` no servidor.             *
 * Requisições AJAX serão, pelo menos por enquanto, ignoradas.                     *
 *                                                                                 *
 * Os programas deverão incluir um campo `hidden` em todo formulário a ser enviado.*
 * Se uma requisição AJAX envia a token (por estar a enviar                        *
 * um formulário), poderá ser validada.                                            *
 *                                                                                 *
 ***********************************************************************************/
try
{
    define (
        'SKIP_TOKEN_CHECK',
        (strlen($_SERVER['HTTP_ACCEPT']) < 30
        && !array_key_exists('HTTP_UPGRADE_INSECURE_REQUESTS', $_SERVER))
        || preg_match("/menu_|ajax|lupa|(?:\w*)(?<!comunicado_mostra_)pesquisa/", $_SERVER['SCRIPT_FILENAME'])
        || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'], 'xmlhttprequest') == 0)
        // || array_key_exists('ajax', $_POST)
    );

    if (!SKIP_TOKEN_CHECK) {
        // recuperar e validar token
        $token_form = $_REQUEST['token_form'];

        $errosMsg = [
            'forbidden'    => 'Acesso negado, requisição inválida',
            'external_access'    => 'Acesso negado, a requisição parece ter vindo de fora de nossos servidores',
            'unauthorized' => 'Acesso negado, requisição alterada',
        ];

        $script_name = basename($_SERVER['SCRIPT_FILENAME']);
        // por enquanto
        $script_name = strlen($script_name) > 30
            ? substr($script_name, 0, 20) . '...'.substr($script_name, -7)
            : $script_name;

        $upd_usuario[] = "admin" . (is_numeric($login_admin) ? " = $login_admin" : ' IS NULL');
        $upd_usuario[] = "posto" . (is_numeric($login_posto) ? " = $login_posto" : ' IS NULL');
        $ins_usuario['admin'] = is_numeric($login_admin) ? "$login_admin" : 'NULL';
        $ins_usuario['posto'] = is_numeric($login_posto) ? "$login_posto" : 'NULL';
        $user_data     = implode(",\n\t\t\t\t\t\t");

        // Prepara os dados
        if (count($_REQUEST)) {
            if(!strstr($_SERVER['HTTP_REFERER'],$_SERVER['SERVER_NAME'])){
                throw new Exception($errosMsg['external_access'], 400);
            }

            $reqData = isset($DATAREQUEST) && count($DATAREQUEST)
                ? json_encode($DATAREQUEST) : 'NULL';
            $reqData    = json_encode([
                'SERVER'  => $_SERVER,
                'COOKIE'  => $_COOKIE,
                'REQUEST' => $DATAREQUEST
            ]);

            $reqData = pg_escape_string($con, $reqData);

            // Somente GET (utilizados para consultas) são permitidos passar sem token, todo formulário que realiza um POST deve conter a token
            if (!array_key_exists('token_form', $DATAREQUEST) && $_SERVER['REQUEST_METHOD'] != "GET") {
                throw new Exception($errosMsg['forbidden'], 400);
            } elseif (array_key_exists('token_form', $DATAREQUEST)) {
                $res = pg_query($con,
                        "SELECT form_token
                        FROM tbl_form_token
                        WHERE token = '$token_form'
                        AND aceito      IS NULL
                        AND json_script IS NULL"
                );

                if (!$res or !pg_num_rows($res)) {
                    throw new Exception($errosMsg['unauthorized'], 401);
                }

                $form_token_id = pg_fetch_result($res, 0, 'form_token');
                unset($DATAREQUEST['token_form']);

                $res = pg_query(
                    $con,
                    "UPDATE tbl_form_token
                        SET json_script = '$reqData',
                            script_name = '$script_name',
                            aceito      = CURRENT_TIMESTAMP
                      WHERE form_token = $form_token_id"
                );

                if (!is_resource($res) or pg_affected_rows($res) !== 1) {
                    throw new Exception("Bad Gateway".pg_last_error($con), 502);
                }
            }
        }

        /*******************************************************************************
         * Entrando ou não na condição anterior, uma nova token será gerada e inserida *
         * nos formulários.                                                            *
         *******************************************************************************/
        define ('TOKEN', sha1(uniqid($_SERVER['SCRIPT_FILENAME']) . $_SERVER['SCRIPT_FILENAME']));

        $script_name = basename($_SERVER['SCRIPT_FILENAME']);
        // por enquanto
        $script_name = strlen($script_name) > 30
            ? substr($script_name, 0, 20) . '...'.substr($script_name, -7)
            : $script_name;

        $sql =
            "INSERT INTO tbl_form_token (
                token, admin, posto, script_name
            ) VALUES (
                '".TOKEN."',
                {$ins_usuario['admin']}, {$ins_usuario['posto']},
                '$script_name'
            )";

        $res = pg_query($con, $sql);

        if (!is_resource($res))
            throw new Exception("Back-end error", 500);
        if (pg_last_error($con))
            throw new Exception("Back-end error", 500);
    }

} catch (\Exception $e) {

    $error = [
        'code' => $e->getCode(),
        'msg'  => $e->getMessage()
    ];

    if ($form_token) {
        $sql = "SELECT form_token
                  FROM tbl_form_token
                 WHERE fabrica = $login_fabrica
                   AND script_name = '$script_name'
                   AND ". implode(' AND ', $upd_usuario) ."
                   AND json_script IS NULL
              ORDER BY data_input DESC
                 LIMIT 1";
        $form_token_id = pg_fetch_result(pg_query($con, $sql), 0, 'form_token');

        pg_query(
            $son,
            "UPDATE tbl_form_token
                    SET json_script = '$reqData',
                        rejeitado   = CURRENT_TIMESTAMP
                  WHERE form_token = $form_token_id"
        );
    } else {
        $form_err = strtotime('now') . 'Formulário enviado sem TOKEN';
        $sql = "INSERT INTO tbl_form_token (
                token, admin, posto, script_name, json_script, rejeitado
            ) VALUES (
                '$form_err',
                {$ins_usuario['admin']}, {$ins_usuario['posto']},
                '$script_name',
                '$reqData',
                CURRENT_TIMESTAMP
            )";
        pg_query($con, $sql);
    }
    header(sprintf("HTTP/1.1 %d %s", $error['code'], $error['msg']));

    if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . '40x.php'))
        include(__DIR__ . DIRECTORY_SEPARATOR . '40x.php');
    die;
}

// cleanup
unset($error, $errosMsg, $form_err, $form_token_id, $reqData, $res, $script_name, $sql, $token_form, $user_data);


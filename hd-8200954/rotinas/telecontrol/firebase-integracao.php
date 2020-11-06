<?php
$script_filename = basename($_SERVER["SCRIPT_FILENAME"]);
$ps_aux_grep     = explode("\n", shell_exec("ps aux | grep {$script_filename}"));
$script_filename = str_replace(".", "\\.", $script_filename);

$i = 0;

foreach ($ps_aux_grep as $value) {
    if (preg_match("/(.*)php (.*)\/telecontrol\/{$script_filename}/", $value)) {
        $i += 1;
    }
}

if ($i > 2) {
    die;
}

include __DIR__."/../../dbconfig.php";
include __DIR__."/../../includes/dbconnect-inc.php";
include __DIR__."/../../class/communicator.class.php";

use \Firebase\FirebaseLib;

define(DEFAULT_URL, "https://telecontrol-posto.firebaseio.com/");
define(DEFAULT_TOKEN, "xo7WbIUS3nvOf4QE92COhyMR2R1v1zvW1Kn2z6gB");
define(DEFAULT_PATH, "/osEnviada");

try {
    $firebase = new FirebaseLib(DEFAULT_URL, DEFAULT_TOKEN);
    $firebase->setTimeOut(60);

    $transaction = false;

    foreach (json_decode($firebase->get(DEFAULT_PATH, array("orderBy" => "\"$key\"", "limitToFirst" => 100)), true) as $id => $data) {
        pg_query($con, "BEGIN");
        $transaction = true;
        $os      = $data["os"];
        $fabrica = $data["fabrica"];
        $status  = $data["status"]["codigo"];
        $data    = json_encode($data);

        $sql = "
            INSERT INTO tbl_os_mobile 
                (fabrica, os, dados, status_os_mobile) 
            VALUES
                ({$fabrica}, {$os}, '{$data}', {$status})
        ";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0 || pg_affected_rows($res) != 1) {
            throw new \Exception("Ocorreu um erro na integração com o firebase");
        }

        $firebase->delete(DEFAULT_PATH."/".$id);

        if ($firebase->get(DEFAULT_PATH."/".$id) != "null") {
            throw new \Exception("Ocorreu um erro na integração com o firebase");
        }

        pg_query($con, "COMMIT");
    }
} catch(\Exception $e) {
    if ($transaction) {
        pg_query($con, "ROLLBACK");
    }

    $dest = array(
        "guilherme.curcio@telecontrol.com.br",
        "ronald.santos@telecontrol.com.br",
        "paulo@telecontrol.com.br",
        "waldir@telecontrol.com.br",
        "francisco.ambrozio@telecontrol.com.br",
        "thiago.tobias@telecontrol.com.br",
        "maicon.luiz@telecontrol.com.br"
    );

    $message = "
        Data: ".date("d/m/Y H:i")."<br /><hr />
        Erro: ".pg_last_error()."<br /><hr />
        Query: {$sql}<br /><hr />
        Firebase ID: {$id}<br /><hr />
        Firebase Value: {$data}<br /><hr />
        Exception Message: {$e->getMessage()}
    ";

    $sql = "SELECT parametros_adicionais FROM tbl_fabrica WHERE fabrica = 10";
    $res = pg_query($con, $sql);

    if (strlen(pg_last_error()) > 0 || pg_num_rows($res) == 0) {
        mail(implode(",", $dest), "Erro na integração com o Firebase", str_replace("<br /><hr />", "\n", $message));
    } else {
        $parametros_adicionais = json_decode(pg_fetch_result($res, 0, "parametros_adicionais"), true);
        $externalId = $parametros_adicionais["externalId"];

        $mail = new TcComm($externalId);
        $mail->addEmailDest($dest);
        $mail->setEmailFrom("noreply@telecontrol.com.br");
        $mail->setEmailSubject("Erro na integração com o Firebase");
        $mail->setEmailBody($message);

        if (!$mail->sendMail()) {
            mail(implode(",", $dest), "Erro na integração com o Firebase", str_replace("<br /><hr />", "\n", $message));
        }
    }

    if ($_serverEnvironment != "production") {
        echo str_replace("<br /><hr />", "\n", $message);
    }
}
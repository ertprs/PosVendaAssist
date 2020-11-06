<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
$programa_insert = $_SERVER['PHP_SELF'];

$tipo  = $_REQUEST['tipo'];
$linha = $_REQUEST['linha'];
$posto = $_REQUEST['posto'];
$email = $_REQUEST['email'];
$os    = $_REQUEST['os'];

if($login_fabrica == 127){
    $comentario = $_REQUEST['comentario'];
}

if ($tipo == 'Gravar') {
	if (isset($_REQUEST["linha"])) {
        $sqlFabrica = "SELECT tbl_fabrica.nome
                       FROM tbl_fabrica
                       WHERE tbl_fabrica.fabrica = $login_fabrica";
        $resFabrica = pg_query($con, $sqlFabrica);

        $nome_fabrica    = pg_fetch_result($resFabrica, 0, 'nome');
        $comentario      = trim(htmlentities($_REQUEST['comentario'], ENT_QUOTES, 'UTF-8'));
        $remetente_email = base64_decode($email);
        $assunto         = 'FABRICANTE '.strtoupper($nome_fabrica).' AGUARDANDO RETORNO DA O.S ('.$os.')';
        $mensagem        = 'A O.S de Número '.$os.', esta suspensa, por apresentar irregularidades no seu preenchimento, favor providenciar as correções necessárias para liberação da mesma.';
        $mensagem        .= '<br ><br />Motivo: ' . $comentario;

		$header  = 'MIME-Version: 1.0' . "\r\n";
		$header .= 'FROM: helpdesk@telecontrol.com.br' . "\r\n";
		$header .= 'Content-type: text/html; charset=utf-8' . "\r\n";

		pg_query($con, 'BEGIN');

		$sql = "INSERT INTO tbl_os_interacao (
                    programa,
                    os, data, admin, comentario, exigir_resposta
				) VALUES (
                    '$programa_insert',
                    $os, current_timestamp, $login_admin, '$comentario', 't'
				)";
		$res = pg_query($con, $sql);

        if ($login_fabrica == 52 || $login_fabrica == 91) {
             $sql = "INSERT INTO tbl_comunicado(
                        mensagem,
                        descricao,
                        tipo,
                        fabrica,
                        obrigatorio_site,
                        posto,
                        pais,
                        ativo,
                        remetente_email
                    ) VALUES (
                        '$mensagem',
                        '$assunto',
                        'Comunicado',
                        $login_fabrica,
                        't',
                        $posto,
                        'BR',
                        't',
                        '$remetente_email'
                    )";
            $res = pg_query($con, $sql);
        }
		if (strlen(pg_last_error()) > 0) {
			pg_query($con, 'ROLLBACK');

            echo "erro";
		} else {
			pg_query($con, 'COMMIT');

            if ($login_fabrica == 52 || $login_fabrica == 91) {
                mail($remetente_email, utf8_encode($assunto), utf8_encode($mensagem), $header);
            }

            echo "ok";
		}
	}
}

exit;
?>
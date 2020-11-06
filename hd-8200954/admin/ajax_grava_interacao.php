<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
$programa_insert = $_SERVER['PHP_SELF'];

$tipo  = $_REQUEST['tipo'];
$linha = $_REQUEST['linha'];
$email = $_REQUEST['email'];
$os    = $_REQUEST['os'];

if ($tipo == 'Gravar') {

    $sql = "SELECT posto FROM tbl_os WHERE fabrica = $login_fabrica AND os = $os";
    $res = pg_query($con, $sql);

    $posto = pg_fetch_result($res, 0, "posto");

	if (isset($_REQUEST["linha"])) {

        $sqlFabrica = " SELECT  tbl_fabrica.nome
                        FROM    tbl_fabrica
                        WHERE   tbl_fabrica.fabrica = $login_fabrica
        ";
        $resFabrica = pg_query($con,$sqlFabrica);
        $nome_fabrica = pg_fetch_result($resFabrica,0,'nome');
	$os              = $_REQUEST["os"];
	$comentario = trim(htmlentities($_REQUEST['comentario'],ENT_QUOTES,'UTF-8'));
	#$comentario      = utf8_decode($_POST["comentario"]);
        $remetente_email = base64_decode($email);
        $assunto         = 'FABRICANTE '.strtoupper($nome_fabrica).' AGUARDANDO RETORNO DA O.S ('.$os.')';
        $mensagem        = 'A O.S de Número '.$os.', esta suspensa, por apresentar irregularidades no seu preenchimento, favor providenciar as correções necessárias para liberação da mesma.';
        $mensagem       .= '<br ><br />Motivo: ' . $comentario;

		$header  = 'MIME-Version: 1.0' . "\r\n";
		$header .= 'FROM: helpdesk@telecontrol.com.br' . "\r\n";
		$header .= 'Content-type: text/html; charset=utf-8' . "\r\n";
		
		$res = pg_exec($con, 'BEGIN;');

		$sql = "INSERT INTO tbl_os_interacao (  programa      ,
                                                os            ,
												data          ,
												admin         ,
												comentario    ,
												exigir_resposta
									) VALUES (  '$programa_insert',
                                                $os          ,
												current_timestamp,
												$login_admin  ,
												'$comentario'   ,
												't'
									)";

		$res = pg_exec($con, $sql);

        if ($login_fabrica == 52 || $login_fabrica == 91) {

             $sql = "INSERT INTO tbl_comunicado( mensagem ,
                                                descricao ,
                                                tipo ,
                                                fabrica ,
                                                obrigatorio_site ,
                                                posto ,
                                                pais ,
                                                ativo ,
                                                remetente_email
                                    ) VALUES ( 	'$mensagem' ,
                                                '$assunto' ,
                                                'Comunicado' ,
                                                $login_fabrica ,
                                                't' ,
                                                $posto ,
                                                'BR' ,
                                                't' ,
                                                '$remetente_email'
                                    );";

            $res = pg_exec($con, $sql);
        }
		if (strlen(pg_errormessage($con) ) > 0) {
			$res = pg_exec($con, 'ROLLBACK;');
			echo "|" . pg_errormessage($con);
		} else {
			$res = pg_exec($con, 'COMMIT;');

            if ($login_fabrica == 52 || $login_fabrica == 91) {
                @mail($remetente_email, utf8_encode($assunto), utf8_encode($mensagem), $header);
            }
			echo "|ok|" . $linha . "|" . $os;
		}
		exit;
	}
}

if ($tipo == 'Mostrar') {
?>
    <table border="0" cellspacing="1" cellpadding="0" class="tabela" style="width: 700px; margin: 0 auto;" >
                                <tr>
                                    <th>INTERAGIR NA OS</th>
                                </tr>
                                <tr>
                                    <td class="conteudo" style="text-align: center;" >
                                        <textarea name="comentario_<?=$linha?>" id="comentario_<?=$linha?>" style="width: 400px;"></textarea>
                                    </td>
                                </tr>
                                <?php
                                $sql_i = "SELECT 
                                            tbl_os_interacao.os_interacao,
                                            to_char(tbl_os_interacao.data,'DD/MM/YYYY HH24:MI') as data,
                                            tbl_os_interacao.comentario,
                                            tbl_os_interacao.interno,
                                            tbl_os.posto,
                                            tbl_posto_fabrica.contato_email as email,
                                            tbl_admin.nome_completo
                                          FROM tbl_os_interacao
                                          JOIN tbl_os            ON tbl_os.os    = tbl_os_interacao.os
                                          JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
                                          LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os_interacao.admin
                                          WHERE tbl_os_interacao.os = $os
                                          AND tbl_os.fabrica = {$login_fabrica}
                                          ORDER BY tbl_os_interacao.os_interacao DESC";
                                $res_i  = pg_query($con, $sql_i);
                                ?>
                                <tr>
                                    <td rel="interacoes">
                                        <?php
                                        if (pg_num_rows($res_i) > 0) {
                                        ?>
                                            <table border="0" cellspacing="1" cellpadding="0" style="width: 700px; margin: 0 auto;" >
                                                <thead>
                                                    <tr>
                                                        <th class="titulo">Nº</th>
                                                        <th class="titulo">Data</th>
                                                        <th class="titulo">Mensagem</th>
                                                        <th class="titulo">Admin</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $k = 1;

                                                    while ($result_i = pg_fetch_array($res_i)) {
                                                        if ($result_i["interno"] == 't') {
                                                            $cor = "style='font-family: Arial; font-size: 8pt; font-weight: bold; text-align: left; background: #F3F5CF;'";
                                                        } else {
                                                            $cor = "class='conteudo'";
                                                        }
                                                        $nome_completo = (empty($result_i["nome_completo"])) ? "Posto Autorizado" : $result_i["nome_completo"];
                                                        ?>
                                                        <tr>
                                                            <td width="25" <?=$cor?> ><?=$k?></td>
                                                            <td width="90" <?=$cor?> nowrap ><?=$result_i["data"]?></td>
                                                            <td <?=$cor?> ><?=$result_i["comentario"]?></td>
                                                            <td <?=$cor?> nowrap ><?=$nome_completo?></td>
                                                        </tr>
                                                    <?php
                                                        $k++;
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        <?php
                                        }
                                        ?>
                                    </td>
                                </tr>
                            </table>
    <br /><input style="position: relative;left: 47.5%;" type="button" value="Gravar" class="btn btn-info" onclick="gravarInteracao(<?=$linha?>, <?=$os?>, 'Gravar', '<?=$posto?>', '<? echo base64_encode($email)?>');">|<?=$linha?>
<?
}
?>

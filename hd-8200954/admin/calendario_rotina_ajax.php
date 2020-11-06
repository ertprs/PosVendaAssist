<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

use Posvenda\LogError;
use Posvenda\Cockpit;

$oLogError = new LogError();
$oCockpit = new Cockpit($login_fabrica);

// Diretório de Tickets a serem processados
$dirEntrada = "../rotinas/imbera/entrada/";

$ajax_reprocessar = $_REQUEST['ajax_reprocessar'];

if (isset($ajax_reprocessar)) {
    $xdados = json_decode(stripslashes($_REQUEST['dados']), true);

    $idLogError = $xdados['routineScheduleLogError'];
    $fileNewName = $xdados['fileNewName'];
    $xdadoReprocessar = trim($xdados['ticket'], '"');

    if ($_serverEnvironment == "development") {
        if (!file_exists($dirEntrada.$fileNewName)) {
	    system("touch ".$dirEntrada.$fileNewName);
            $fp = fopen($dirEntrada.$fileNewName, "a");

            if ($fp !== false) {
                fwrite($fp, $xdadoReprocessar);
                fclose($fp);
                $retorno = array("success" => utf8_encode("Ticket gerado e será reprocessado no tempo agendado."), "param" => 1);
            } else {
                $retorno = array("error" => utf8_encode("Ocorreu um erro durante a geração do arquivo."), "param" => 2);
            }
        } else {
            $retorno = array("error" => utf8_encode("Ticket já está na fila para ser processado."), "param" => 3);
        }
    } else {
        $ftp_server = "ftp.telecontrol.com.br";
        $ftp_user   = "imbera";
        $ftp_pass   = "Imb3r@";

        $conexao_ftp = ftp_connect($ftp_server);
        ftp_login($conexao_ftp, $ftp_user, $ftp_pass);
        ftp_pasv($conexao_ftp, true);
        ftp_chdir($conexao_ftp, "imbera-telecontrol");

	$arquivos = ftp_nlist($conexao_ftp, ".");

	if (in_array($fileNewName, $arquivos)) {
	    $retorno = array("error" => utf8_encode("Ticket já está na fila para ser processado."), "param" => 3);
        } else {
            $fp = fopen("/tmp/".$fileNewName, "a");
            if ($fp !== false) {
                fwrite($fp, $xdadoReprocessar);
                fclose($fp);

		$a = ftp_put($conexao_ftp, $fileNewName, "/tmp/".$fileNewName, FTP_ASCII);

		$arquivos = ftp_nlist($conexao_ftp, ".");

		if (in_array($fileNewName, $arquivos)) {
	                $retorno = array("success" => utf8_encode("Ticket gerado e será reprocessado no tempo agendado."), "param" => 1);
		} else {
			$retorno = array("error" => utf8_encode("Ocorreu um erro durante a geração do arquivo."), "param" => 4);
		}
            } else {
                $retorno = array("error" => utf8_encode("Ocorreu um erro durante a geração do arquivo."), "param" => 2);
            }
        }
    }

    echo json_encode($retorno);
    exit;

} ?>

<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
<link type="text/css" rel="stylesheet" media="screen" href="../css/tc_css.css" />
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="bootstrap/js/bootstrap.js"></script>
<script type="text/javascript">
    $(function() {
        /**
         * Botão de reprocessamento de Tickets que não foram processados por Produto Inválido
         */
         $(".reprocessar").click(function() {
            var that = $(this);
            var linha = $(this).attr("rel");
            var dados = $('#dadosTicketError_'+linha).val();
            $.ajax({
                url: "<?= $_SERVER['PHP_SELF'] ?>",
                type: "POST",
                data: { ajax_reprocessar: true, dados: dados },
                beforeSend: function() {
                    if (that.next("img").length == 0) {
                        that.hide().after($("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } }));
                    }
                },
                complete: function(data) {
                    data = $.parseJSON(data.responseText);
                    /* 
                     * data.param (Mostra as ações que ainda podem ser tomadas)
                     * 1 - Sucesso, 2 - Pode tentar reprocessar, 3 - Ticket na fila de processamento
                     */
                    if (data.error) {
                        alert(data.error);
                        that.next().remove();
                        /* Quando pode reprocessar novamente */
                        if (data.param == 2) {
                            that.show();
                        }
                    } else {
                        alert(data.success);
                        that.html("Processado").prop('disabled', true).show();
                        that.next().remove();
                    }
                }
            });
        });
    });
</script>

<div style="overflow:scroll;height:100%;width:100%;">
    <? if ($_REQUEST['show_errors'] == 't') {
        $routineScheduleLog = $_REQUEST['schedule_log'];
        $oLogError->setRoutineScheduleLog($routineScheduleLog);

        $logs_errors = $oLogError->SelectLogErrors();
        $qtde_errors = count($logs_errors);

        if ($qtde_errors > 0) { ?>
            <table class="table table-striped table-bordered table-hover table-large" style="margin:0 auto;">
                <thead>
                    <tr>
                        <th colspan="100%">Erros por linha no Log</th>
                    </tr>
                    <tr>
                        <th>Arquivo</th>
                        <th>Linha</th>
                        <th>Conteúdo</th>
                        <th>Erro</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <? foreach($logs_errors as $u => $log_error) {
                        $routineScheduleLogError = $log_error['routine_schedule_log_error'];
                        $fileName = $log_error['file_name'];
                        $lineNumber = $log_error['line_number'];
                        $contents = $log_error['contents'];
			if (strpos($contents, chr(hexdec("A6")))) {
			    $contents = str_replace(chr(hexdec("A6")), "|", $contents);
			    $contents = utf8_decode($contents);
			    $contents = str_replace("?|", "|", $contents);
			} else {
			    $contents = utf8_decode($contents);
			}
                        $errorMessage = utf8_decode($log_error['error_message']);

                        /*
                         * Compara se já não existe um arquivo para ser processado desse ticket,
                         * valida também se a OSKof não está no cockpit para mostrar o botão de reprocessamento
                         */
                        $fileNewName = explode(".", $fileName);
                        $fileNewName = $fileNewName[0]."_".$lineNumber."_reprocessado".date("Ymd").".".$fileNewName[1];
                        $errorMessageComp = strtoupper(retira_acentos($errorMessage));
                        $jsonTicketError = json_encode(array("routineScheduleLogError" => $routineScheduleLogError, "fileNewName" => $fileNewName, "ticket" => $contents));
                        if (in_array($errorMessageComp, array("PRODUTO INVALIDO", "ERRO AO GRAVAR TICKET")) && !file_exists($dirEntrada.$fileNewName)) {
                            $ticketExplode = explode("|", $contents);
                            $osKof = $ticketExplode[15];
                            if ($oCockpit->cockpitExists($osKof)) {
                                $btnReprocessar = "<strong>Ticket reprocessado</strong>";
                            } else {
                                $btnReprocessar = "<button type='button' class='btn btn-small btn-primary reprocessar' rel='$u'>Reprocessar</button>";
                            }
                        } else {
                            $btnReprocessar = "";
                        } ?>
                        <tr class="ticket_error_<?= $u; ?>">
                            <input type="hidden" id="dadosTicketError_<?= $u; ?>" value='<?= $jsonTicketError; ?>' />
                            <td><?= $fileName; ?></td>
                            <td class="tac"><?= $lineNumber; ?></td>
                            <td><?= $contents; ?></td>
                            <td><?= $errorMessage; ?></td>
                            <td><?= $btnReprocessar; ?></td>
                        </tr>
                    <? } ?>
                </tbody>
            </table>
        <? }
    } ?>
</div>

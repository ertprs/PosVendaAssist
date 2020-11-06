<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$title = "Telecontrol - Assistência Técnica - Ordem de Serviço";

$layout_menu = 'os';
include "cabecalho.php";


$os = $_GET["os"];

$sql = "SELECT  tbl_os.sua_os,
				to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura
		FROM    tbl_os
		WHERE   tbl_os.os = $os";
$res = pg_exec ($con,$sql) ;

$sua_os = pg_result($res,0,sua_os);
$data_abertura = pg_result($res,0,data_abertura);

echo "<BR><BR><BR><center><font size='2' face='verdana'>OS ($sua_os) com data de abertura ($data_abertura) </font><font size='2' face='verdana' color='#bf2828'>superior a 30 dias</font><font size='2' face='verdana'>: para inserir peças,<BR> favor enviar e-mail para <B>assistenciatecnica@britania.com.br</b> <BR>informando o número da OS, código da peça e justificativa.</font></center><BR><BR><BR>";


include "rodape.php";

?>
<?php
/************************************************************************
 *  @name: email_admin.php, email_admin_include.html                   *
 *  @author: Manuel López (manolo@telecontrol.com.br)                   *
 *  @usage: email_admin.php?ajax='açao'                                 *
 *  Esta rotina tem como propósito informar os admins de email gerados  *
 *  pelas diferentes rotinas de importação e exportação do sistema,     *
 *  e assim manté-los informados mesmo que o e-mail seja rejeitado pelo *
 *  servidor ou fique parado por algum outor motivo                     *
 *                                                                      *
 *  O funcionamento é por AJAX: na hora de gerar a tela, o javascript   *
 *  vai pedir a lista de mensagens, que vai vir via JSON, para o JS     *
 *  muntar o html... isto porque quero aprender usar o JSON :P          *
 *                                                                      *
 *  Na hora do usuário clicar numa mensagem, requisita para este prog.  *
 *  o corpo da mensagem.						*
 ***********************************************************************/
require '/var/www/assist/www/dbconfig.php';
include "/var/www/assist/www/includes/dbconnect-inc.php";
include '/var/www/assist/www/admin/autentica_admin.php';

$host = 'http://netuno.telecontrol.com.br/';
$ajax = '';
if (isset($_GET['ajax'])) $ajax = '&ajax=consulta';
if (isset($_GET['ajax']) and isset($_GET['file'])) $ajax = '&ajax=getmsg&file='.$_GET['file'];

$q = "?login_admin=$login_admin&login_fabrica=$login_fabrica" . $ajax;

readfile($host . "email_admin.php". $q);
?>

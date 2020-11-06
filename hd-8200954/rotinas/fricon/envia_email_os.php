<?php

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // producao Alterar para produção ou algo assim


	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';


	$sql = "SELECT tbl_os.sua_os,to_char(tbl_os.data_abertura,'DD/MM/YYYY') as data_abertura,tbl_posto.nome,tbl_posto_fabrica.contato_cidade,tbl_posto_fabrica.contato_estado,(current_date-data_abertura) as dias from tbl_os join tbl_posto using(posto) join tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = 52 where tbl_os.fabrica = 52 and finalizada is null and excluida is not true order by tbl_posto_fabrica.contato_estado,tbl_posto_fabrica.contato_cidade,nome,dias";

	$res = pg_query($sql);
	$total = pg_num_rows($res);

	$table = "<table border=1><tr><td colspan='999999999'>Total OSs Abertas: $total </td></tr><tr><td>OS</td><td>Data Abertura</td><td>Posto</td><td>Cidade</td><td>Estado</td><td>24h</td><td>48hs</td><td>72hrs</td><td>> 72hs</td><td>Dias Aberto</td></tr>";


	for ($i=0;$i<pg_num_rows($res);$i++) {

		$sua_os        = pg_result($res,$i,sua_os);
		$data_abertura = pg_result($res,$i,data_abertura);
		$posto_nome    = pg_result($res,$i,nome);
		$cidade        = pg_result($res,$i,contato_cidade);
		$estado        = pg_result($res,$i,contato_estado);
		$dias          = pg_result($res,$i,dias);
	


		$table .= "<tr>";
		$table .= "<td>$sua_os</td>";
		$table .= "<td>$data_abertura</td>";
		$table .= "<td>$posto_nome</td>";
		$table .= "<td>$cidade</td>";
		$table .= "<td>$estado</td>";

		switch ($dias) {
			case ($dias<=1): $table .="<td align='center'>X</td> <td></td> <td></td> <td></td>";
			break;
			case ($dias ==2): $table .= "<td></td> <td align='center'>X</td> <td></td> <td></td>";
			break;
			case ($dias == 3): $table .= "<td></td> <td></td> <td align='center'>X</td> <td></td>";
			break;
			case ($dias > 3): $table .= "<td></td> <td></td> <td></td> <td align='center'>X</td>";
			break;
		}
	
		$table .= "<td>$dias</td>";
		$table .= "</tr>";

	}
	
	$table .="</table>";



	$file = fopen('/tmp/fricon/tempo_os_aberta.xls','w+');
	fwrite($file,$table);
	fclose($file);


    	$mailer = new PHPMailer();
		$mailer->IsSMTP();
		$mailer->IsHTML();
		$mailer->AddReplyTo("helpdesk@telecontrol.com.br", "Suporte Telecontrol");
		$mailer->AddAddress('posvenda@fricon.com.br');
//		$mailer->AddAddress('waldir@telecontrol.com.br');
		$mailer->AddAttachment('/tmp/fricon/tempo_os_aberta.xls');
		

		$mensagem  = "Segue em anexo, OSs Abertas";

		$mailer->Subject = Date('d/m/Y')." - Ordens de Serviços Abertass";
	    $mailer->Body = $mensagem;
	    $mailer->Send();
		system("echo $mensagem | mail -s '$titulo' -A /tmp/fricon/tempo_os_aberta.xls posvenda@fricon.com.br"); 

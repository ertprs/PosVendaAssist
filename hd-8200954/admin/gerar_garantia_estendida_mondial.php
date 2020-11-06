<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_admin.php';
	include '../classes/mpdf61/mpdf.php';
	include '../plugins/fileuploader/TdocsMirror.php';
	include '../class/email/PHPMailer/class.phpmailer.php';

	$dt_final_garantia 	= $_GET['dt_final'];
	$callcenter 		= $_GET['callcenter'];
	$produto  			= $_GET['produto'];
	$x_admin			= $_GET['admin'];

	$arr_data 		= ["January" => "Janeiro", "February" => "Fevereiro", "March" => "Março", "April" => "Abril", "May" => "Maio", "June" => "Junho", "July" => "Julho", "August" => "Agosto", "September" => "Setembro", "October" => "Outubro", "November" => "Novembro", "December" => "Dezembro"];
	$dia_atual 		= date('d');
	$mes_atual 		= date('F');
	$ano_atual 		= date('Y');
	$x_mes_atual 	= $arr_data[$mes_atual];

	$sql_admin = "SELECT login FROM tbl_admin WHERE admin = {$x_admin}";
	$res_admin = pg_query($con, $sql_admin);

	$log_admin = pg_fetch_result($res_admin, 0, 'login');

	$sql_garantia_estendida = "SELECT 
								hce.hd_chamado,
								hce.nome,
								hce.cpf,
								hce.email,
								hci.nota_fiscal,
								hci.serie,
								hci.produto,								
								p.descricao
								FROM tbl_hd_chamado_extra hce
								JOIN tbl_hd_chamado_item hci ON hci.hd_chamado = hce.hd_chamado
								JOIN tbl_produto p ON p.produto = hci.produto AND p.referencia = '{$produto}' AND p.fabrica_i = {$login_fabrica}
								WHERE hce.hd_chamado = {$callcenter}
								AND hci.produto IS NOT NULL";

								//die(nl2br($sql_garantia_estendida));
//echo $sql_garantia_estendida;
	$res_garantia_estendida = pg_query($con, $sql_garantia_estendida);

	$x_hd_chamado 	= pg_fetch_result($res_garantia_estendida, 0, 'hd_chamado');
	$x_nome_cliente = pg_fetch_result($res_garantia_estendida, 0, 'nome');
	$x_cpf_cliente 	= pg_fetch_result($res_garantia_estendida, 0, 'cpf');
	$x_email  		= pg_fetch_result($res_garantia_estendida, 0, 'email');
	$x_nf 			= pg_fetch_result($res_garantia_estendida, 0, 'nota_fiscal');
	$x_serie 		= pg_fetch_result($res_garantia_estendida, 0, 'serie');
	$x_produto 		= pg_fetch_result($res_garantia_estendida, 0, 'produto');
	$x_descricao 	= utf8_encode(pg_fetch_result($res_garantia_estendida, 0, 'descricao'));	

	$corpo_arquivo = "
					<div class='row-fluid'>
					<div class='span1'></div>
					<div class='span10'>
						<div class='control-group'>
							<img src='../logos/logo_mondial.jpg' border='0' style='max-height:55px;max-width:240px;'><br /><br /><br />
						</div>
						<div class='control-group' style='text-align: center;'>							
			 				<h2><b>CERTIFICADO DE EXTENSÃO DA GARANTIA</b></h2><br /><br /><br />	
			 			</div>
			 			<div class='control-group' style='text-align: justify;'>
							Cliente: {$x_nome_cliente} <br />
							CPF: {$x_cpf_cliente}<br />
							Produto: {$x_descricao}<br /> 
							Número de série: {$x_serie}<br />
							Protocolo de Autorização da Garantia: {$callcenter}<br /><br /><br />
							
								<ol>
									<li>A M.K. ELETRODOMÉSTICOS MONDIAL S/A, inscrita no CNPJ nº 07.666.567/0005-74, assegura ao cliente acima identificado uma garantia até <b>{$dt_final_garantia}</b> sobre o produto acima descrito, nota fiscal <b>{$x_nf}</b>.</li>
									<br />
									<li>Essa garantia cobre somente os defeitos de funcionamento das peças e componentes do equipamento descrito nas condições normais de uso – de acordo com as instruções do manual de operação que acompanha o mesmo.</li>
									<br />
									<li>Essa garantia ficará automaticamente cancelada se o equipamento vier a sofrer reparo por pessoas não autorizadas, receber maus tratos ou sofrer danos decorrentes de acidentes, quedas, variações de tensão elétrica e sobrecarga acima do especificado, ou qualquer ocorrência imprevisível, decorrentes de má utilização do equipamento por parte do usuário.</li>
									<br />
								</ol>
							<br /><br /><br /><br />
						</div>
						<div class='control-group' style='text-align: center;'>
							Barueri, {$dia_atual} de {$x_mes_atual} de {$ano_atual}.<br /><br />
							<img name='assinatura' alt='Assinatura' src='../imagens/assinatura_pos_venda_mondial.png'><br />
			 			</div>
			 		</div>
			 		<div class='span1'></div>	 	
			 	</div>
		 	";

	// Gerando Arquivo PDF						
	$dir_garantia = "/tmp/";
	$arq_garantia = "garantia_estendida_{$callcenter}_{$produto}.pdf";
	$arq_garantia = str_replace("-", "_", $arq_garantia);
	$arquivo_garantia = $dir_garantia.$arq_garantia;
	$gerarPDF = new mPDF();
	$gerarPDF->SetDisplayMode('fullpage');
	$gerarPDF->WriteHTML($corpo_arquivo);
	$gerarPDF->Output($arquivo_garantia, "F");
	
	// Enviando Arquivo para o TDocs
	$s3_tdocs = new TdocsMirror();
	$postPDF = $s3_tdocs->post($arquivo_garantia);	
	if(!is_array($postPDF)) {
		$postPDF = json_decode($postPDF, true);
	}
	$uniqueId = $postPDF[0][$arq_garantia]['unique_id'];	

	$obs[0]['acao'] 		= "anexar";
    $obs[0]["filename"] 	= $arq_garantia;
    $obs[0]["data"] 		= date("Y-m-d h:i:s");
    $obs[0]["fabrica"] 		= $login_fabrica;
    $obs[0]["descricao"] 	= "";
    $obs[0]["page"]   		= "callcenter_interativo_new.php";
    $obs[0]["source"] 		= "garantia-estendida";
    $obs[0]["typeId"] 		= $uniqueId;

    $obs = json_encode($obs);
    
    $sql_tdocs = "INSERT INTO tbl_tdocs (tdocs_id, fabrica, contexto, situacao, referencia, referencia_id, obs) VALUES ('".$uniqueId."',".$login_fabrica.", 'callcenter', 'ativo', 'callcenter', '".$callcenter."', '".$obs."')";

    $res_tdocs = pg_query($con, $sql_tdocs);    

	//Envio de e-mail
	$mail = new PHPMailer();
	$mail->From = 'noreply@telecontrol.com.br';
	//$mail->AddAddress('breno.sabella@telecontrol.com.br');	
	$mail->AddAddress($x_email);			

	$mail->IsHTML(true);
	$mail->CharSet = 'UTF-8';

	//Corpo do Email
	$corpo_email = "
				<div class='row-fluid'>
					<div class='span1'></div>
					<div class='span10'>
			 			<div class='control-group' style='text-align: justify;'>
			 				Bom dia!<br /><br />
							Prezado(a) {$x_nome_cliente}<br /><br />
							Conforme acordado, segue em anexo certificado de extensão de garantia de seu produto, para fins de atendimento em nossa rede de assistências técnicas credenciadas, se necessário.<br /><br />
							Atenciosamente,<br />
							Pós Venda Mondial<br /><br />							
						</div>
			 		</div>
			 		<div class='span1'></div>	 	
			 	</div>";

	$mail->Subject = "Garantia Estendida Mondial";
	$mail->Body = $corpo_email;

	$mail->AddAttachment("{$arquivo_garantia}");

	$enviar = $mail->Send();

	$mail->ClearAllRecipients();
	$mail->ClearAttachments();

	//Gravar Interacao
	$sql_grava_interacao = "INSERT INTO tbl_hd_chamado_item (hd_chamado, status_item, comentario, admin, interno)
							 VALUES ({$callcenter}, 'Aberto', 'Foi gerada uma garantia estendida por <b>{$log_admin}</b> para o Atendimento.', {$x_admin}, 't')";							 
	//die(nl2br($sql_grava_interacao));
	$res_grava_interacao = pg_query($con, $sql_grava_interacao);
?>

<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include 'funcoes.php';

if ($login_fabrica <> 5) {
	include 'login.php';
}
?>

<?//script que limita a quantidade de caracteres dos textarea?>
<script>
	function Contador(field,MaxLength) {
		var aux_cont = document.getElementById(field).value.length;
		if (aux_cont > MaxLength) return false;
	}
</script>



<TITLE> PESQUISA DE SATISFAÇÃO POSTO AUTORIZADO </TITLE>


<?

$sql = "SELECT  tbl_posto.posto                                       ,
				tbl_posto.fantasia                                    ,
				tbl_posto.nome                                        ,
				tbl_posto_fabrica.codigo_posto                        ,
				tbl_posto.endereco||', '||tbl_posto.numero as endereco,
				tbl_posto.cidade                                      ,
				tbl_posto.estado                                      ,
				tbl_posto.cep                                         ,
				tbl_posto.email                                       ,
				tbl_posto.fone
		FROM tbl_posto
		JOIN tbl_posto_fabrica using(posto)
		WHERE tbl_posto.posto = $login_posto
		AND   tbl_posto_fabrica.fabrica = $login_fabrica";
$res = pg_exec($con, $sql);

$posto        = pg_result($res,0,posto);
$fantasia     = pg_result($res,0,fantasia);
$nome         = pg_result($res,0,nome);
$codigo_posto = pg_result($res,0,codigo_posto);
$endereco     = pg_result($res,0,endereco);
$cidade       = pg_result($res,0,cidade);
$estado       = pg_result($res,0,estado);
$cep          = pg_result($res,0,cep);
$email        = pg_result($res,0,email);
$fone         = pg_result($res,0,fone);
if (strlen(trim($fantasia))==0) $fantasia = $nome;

$btn_acao = $_POST['formulario'];
if($btn_acao == 'Enviar'){
	if (strlen($_POST['resp_1'])>0) {
		$resp_1 = trim($_POST['resp_1']);
	} else {
		$msg_erro = "Selecione a resposta para a questão número 1.";
	}

	if (strlen($_POST['resp_2'])>0) {
		$resp_2 = trim($_POST['resp_2']);
	} elseif (strlen($msg_erro)==0) {
		$msg_erro = "Selecione a resposta para a questão número 2.";
	}

	if (strlen($_POST['resp_3'])>0) {
		$resp_3 = trim($_POST['resp_3']);
		//echo $resp_1;
	} elseif (strlen($msg_erro)==0) {
			$msg_erro = "Selecione a resposta para a questão número 3.";
	}

	if (strlen($_POST['resp_4'])>0) {
		$resp_4 = trim($_POST['resp_4']);
	} elseif (strlen($msg_erro)==0) {
		$msg_erro = "Selecione a resposta para a questão número 4.";
	}

	if (strlen($_POST['resp_5'])>0) {
		$resp_5 = trim($_POST['resp_5']);
	} elseif (strlen($msg_erro)==0) {
		$msg_erro = "Selecione a resposta para a questão número 5.";
	}

	if (strlen($_POST['resp_6'])>0) {
		$resp_6 = trim($_POST['resp_6']);
	} elseif (strlen($msg_erro)==0) {
		$msg_erro = "Selecione a resposta para a questão número 6.";
	}

	if (strlen($_POST['resp_7'])>0) {
		$resp_7 = trim($_POST['resp_7']);
		if($resp_7 == 'NÃO'){
			$comentario_7 = $_POST['comentario_7'];
			if(strlen($comentario_7)==0 and strlen($msg_erro)==0) {
				$msg_erro = "Preencha o comentário para a questão número 7 (NÃO. POR QUÊ?).";
			}
		}
	} elseif (strlen($msg_erro)==0) {
		$msg_erro = "Selecione a resposta para a questão número 7.";
	}

	if (strlen($_POST['resp_8'])>0) {
		$resp_8 = trim($_POST['resp_8']);
		if($resp_8 == 'SIM CORREÇÃO') {
			$sim_comentario_8 = $_POST['sim_comentario_8'];
			if(strlen($sim_comentario_8)==0 and strlen($msg_erro)==0) {
				$msg_erro = "Preencha o comentário para a questão número 8 (SIM, COM CORREÇÕES.).";
			}
		}
		if($resp_8 == 'NÃO'){
			$comentario_8 = $_POST['comentario_8'];
			if(strlen($comentario_8)==0 and strlen($msg_erro)==0){
				$msg_erro = "Preencha o comentário para a questão número 8 (NÃO. POR QUÊ?).";
			}
		}
	} elseif (strlen($msg_erro)==0) {
		$msg_erro = "Selecione a resposta para a questão número 8.";
	}

	if (strlen($_POST['comentarios'])>0) {
		$comentarios = trim($_POST['comentarios']);
	}
}


if (strlen($msg_erro) == 0 AND $btn_acao == 'Enviar') {
	//PDF
	include 'includes/fpdf.php';
	$pdf = new FPDF('P','cm','A4');
	$pdf->AddPage();
	$pdf->SetFont('Arial', 'B', 10);

	$pdf->Cell(4,1.003,'',1);
	$pdf->Image('/var/www/assist/www/logos/mondial-pdf.jpg',1.7,1.1,2.6,0.8);
	//---------------------------------------------------------------------------------------------------------//
	$texto = "PESQUISA DE SATISFAÇÃO POSTO AUTORIZADO
	ISO 9001                      Mar 2007";
	$pdf->MultiCell	(0,0.5,$texto, 1, 'C');
	//---------------------------------------------------------------------------------------------------------//
	$pdf->ln(0.2);
	$pdf->SetFont('Arial', '', 8);
	$texto = "Ao Posto Autorizado
	A partir de suas  respostas teremos a oportunidade de detectar os nossos pontos passíveis  de melhoria.
	Obrigado por sua  participação.
	Atenciosamente,

	Giovanni Marins Cardoso
	Diretor Comercial e Marketing";
	$pdf->MultiCell(0,0.4,$texto, 1, 'J');
	//---------------------------------------------------------------------------------------------------------//
	$pdf->ln(0.2);
	$texto = "Nome: ".$fantasia."
	Razão Social: ".$nome."
	Código do Posto: ".$codigo_posto."
	Endereço: ".$endereco."
	Cidade: ".$cidade."          Estado: ".$estado."          CEP: ".$cep."
	E-Mail ".$email."          Telefone: ".$fone;
	$pdf->MultiCell(0,0.4,$texto, 1, 'J');
	//---------------------------------------------------------------------------------------------------------//

	$pdf->ln(0.2);
	//retangulo questoes direita
	$pdf->Rect(1,7.8,9.5,12.5);
	//retangulo questoes eesquerda
	$pdf->Rect(10.5,7.8,9.5,12.5);

	$texto = "1. Como o Sr.(a) classificaria o nosso prazo para atendimento a pedidos (OSG)?\n";
	if ($resp_1 == 'INSUFICIENTE') {$texto.='[x]';}else{$texto.='[  ]';} $texto.= " Insuficiente\n";
	if ($resp_1 == 'REGULAR')      {$texto.='[x]';}else{$texto.='[  ]';} $texto.= " Regular\n";
	if ($resp_1 == 'BOM')          {$texto.='[x]';}else{$texto.='[  ]';} $texto.= " Bom\n";
	if ($resp_1 == 'ÓTIMO')        {$texto.='[x]';}else{$texto.='[  ]';} $texto.= " Ótimo\n";
	if ($resp_1 == 'EXCELENTE')    {$texto.='[x]';}else{$texto.='[  ]';} $texto.= " Excelente";
	$texto.="\n\n";

	$texto.= "2. Como o Sr.(a) classificaria o nosso prazo para atendimento a pedidos (venda de peças fora de garantia)?\n";
	if ($resp_2 == 'INSUFICIENTE') {$texto.='[x]';}else{$texto.='[  ]';} $texto.= " Insuficiente\n";
	if ($resp_2 == 'REGULAR')      {$texto.='[x]';}else{$texto.='[  ]';} $texto.= " Regular\n";
	if ($resp_2 == 'BOM')          {$texto.='[x]';}else{$texto.='[  ]';} $texto.= " Bom\n";
	if ($resp_2 == 'ÓTIMO')        {$texto.='[x]';}else{$texto.='[  ]';} $texto.= " Ótimo\n";
	if ($resp_2 == 'EXCELENTE')    {$texto.='[x]';}else{$texto.='[  ]';} $texto.= " Excelente";
	$texto.="\n\n";
	
	$texto.= "3. As esntregas (venda de peças / OSG) atendem as especificações estabelecidas nos pedidos?\n";
	if ($resp_3 == 'NUNCA')                {$texto.= '[x]';}else{$texto.='[  ]';} $texto.= " Nunca\n";
	if ($resp_3 == 'ALGUMAS VEZES')        {$texto.= '[x]';}else{$texto.='[  ]';} $texto.= " Algumas vezes\n";
	if ($resp_3 == 'METADE DAS VEZES')     {$texto.= '[x]';}else{$texto.='[  ]';} $texto.= " Metade das vezes\n";
	if ($resp_3 == 'NA MAIORIA DAS VEZES') {$texto.= '[x]';}else{$texto.='[  ]';} $texto.= " Na maioria das vezes\n";
	if ($resp_3 == 'SEMPRE')               {$texto.= '[x]';}else{$texto.='[  ]';} $texto.= " Sempre";
	$texto.="\n\n";
	
	$texto.= "4. Como o Sr.(a) classificaria a troca de informações entre a assistência técnica e a Mondial sobre os nossos produtos?\n";
	if ($resp_4 == 'INSUFICIENTE') {$texto.='[x]';}else{$texto.='[  ]';} $texto.= " Insuficiente\n";
	if ($resp_4 == 'REGULAR')      {$texto.='[x]';}else{$texto.='[  ]';} $texto.= " Regular\n";
	if ($resp_4 == 'BOM')          {$texto.='[x]';}else{$texto.='[  ]';} $texto.= " Bom\n";
	if ($resp_4 == 'ÓTIMO')        {$texto.='[x]';}else{$texto.='[  ]';} $texto.= " Ótimo\n";
	if ($resp_4 == 'EXCELENTE')    {$texto.='[x]';}else{$texto.='[  ]';} $texto.= " Excelente";
	$texto.="\n\n";

	$pdf->MultiCell(9.5,0.4,$texto, 0, 'L');



	$texto = "5. O SAC / Pós venda, quando acionado, atende as vossas necessidades?\n";
	if ($resp_5 == 'NUNCA')                {$texto.= '[x]';}else{$texto.='[  ]';} $texto.= " Nunca\n";
	if ($resp_5 == 'ALGUMAS VEZES')        {$texto.= '[x]';}else{$texto.='[  ]';} $texto.= " Algumas vezes\n";
	if ($resp_5 == 'METADE DAS VEZES')     {$texto.= '[x]';}else{$texto.='[  ]';} $texto.= " Metade das vezes\n";
	if ($resp_5 == 'NA MAIORIA DAS VEZES') {$texto.= '[x]';}else{$texto.='[  ]';} $texto.= " Na maioria das vezes\n";
	if ($resp_5 == 'SEMPRE')               {$texto.= '[x]';}else{$texto.='[  ]';} $texto.= " Sempre";
	$texto.="\n\n";

	$texto.= "6. O Sr. (a) está satisfeito com os nossos produtos?\n";
	if ($resp_6 == 'MUITO INSATISFEITO') {$texto.='[x]';}else{$texto.='[  ]';} $texto.= " Muito insatisfeito\n";
	if ($resp_6 == 'INSATISFEITO')       {$texto.='[x]';}else{$texto.='[  ]';} $texto.= " Insatisfeito\n";
	if ($resp_6 == 'POUCO INSATISFEITO') {$texto.='[x]';}else{$texto.='[  ]';} $texto.= " Pouco insatisfeito\n";
	if ($resp_6 == 'SATISFEITO')         {$texto.='[x]';}else{$texto.='[  ]';} $texto.= " Satisfeito\n";
	if ($resp_6 == 'MUITO SATISFEITO')   {$texto.='[x]';}else{$texto.='[  ]';} $texto.= " Muito satisfeito";
	$texto.="\n\n";
	
	$texto.= "7. O Sr. (a) recomendaria a marca Mondial para um amigo?\n";
	if ($resp_7 == 'SIM') {$texto.= '[x]';}else{$texto.='[  ]';} $texto.= " Sim\n";
	if ($resp_7 == 'NÃO') {$texto.= '[x]';}else{$texto.='[  ]';} $texto.= " Não. Por quê?\n";
	if ($resp_7 == 'NÃO') {$texto.= "$comentario_7";}
	$texto.="\n\n";

	$texto.= "8. O Sr. (a) está satisfeito em ser nosso parceiro?\n";
	if ($resp_8 == 'SIM')          {$texto.='[x]'; }else{$texto.='[  ]';} $texto.= " Sim\n";
	if ($resp_8 == 'SIM CORREÇÃO') {$texto.='[x]'; }else{$texto.='[  ]';} $texto.= " Sim, com correções. Quais?\n";
	if ($resp_8 == 'SIM CORREÇÃO') {$texto.="$sim_comentario_8\n"; }
	if ($resp_8 == 'NÃO')          {$texto.='[x]'; }else{$texto.='[  ]';} $texto.= " Não. Por quê?\n";
	if ($resp_8 == 'NÃO')          {$texto.="$comentario_8"; }

	//desloca posição para colocar ao lado esquerdo das primeiras questoes
	$pdf->SetY(7.8);
	$pdf->SetX(10.5);
	$pdf->MultiCell(9.5,0.4,$texto, 0, 'L');

	//desloca posição novamente para baixo para imprimir as sugestões e criticas
	$pdf->SetY(21);
	$pdf->SetX(1);
	$texto = "Espaço reservado para críticas, sugestões e/ou elogios:";

	$pdf->MultiCell(9.5,0.4,$texto, 0, 'L');
	$texto = "\n$comentarios\n\n";
	$pdf->MultiCell(0,0.4,$texto, 1, 'J');

	$pdf->ln(0.1);
	$texto = "FR03003-01";
	$pdf->MultiCell(0,0.4,$texto, 0, 'C');

	$pdf->Close();
	$pdf->Output("/var/www/assist/www/download/mondial-iso-".$posto.".pdf", "F");
	
	$arquivo = "/var/www/assist/www/download/mondial-iso-".$posto.".pdf";

	//$destinatario   = "rogerio.ribas@mondialline.com.br";
	$destinatario   = "wellington@telecontrol.com.br";
	$assunto  = "PA ".$codigo_posto." - "."Pesquisa de Satisfação ISO 9001 Mar2007";
	$mensagem = "Segue em anexo questionário respondido pelo posto autorizado ".$codigo_posto." - ".$nome."<BR><BR>";
	$mensagem.= "Telecontrol Networking<br>";
	$mensagem.= "www.telecontrol.com.br";
	$anexos   = 0;
	$boundary = "XYZ-" . date("dmYis") . "-ZYX";

	$mens  = "--$boundary\n";
	$mens .= "Content-Transfer-Encoding: 8bits\n";
	$mens .= "Content-Type: text/html; charset=\"ISO-8859-1\"\n\n";
	$mens .= "$mensagem\n";
	$mens .= "--$boundary\n";

	$fileContent = file_get_contents($arquivo);
	$anexo       = chunk_split(base64_encode($fileContent));

	$mens .= "Content-Type: application/pdf;"."\n name=\"".basename($arquivo)."\""."\n";
	$mens .= "Content-Disposition: attachment; filename=\"".basename($arquivo)."\""."\n";
	$mens .= "Content-transfer-encoding:base64\n\n";
	$mens .= $anexo."\n";
	$mens .= "--$boundary--";

	$headers  = "MIME-Version: 1.0\n";
	$headers .= "Date: ".date("D, d M Y H:i:s O")."\n";
	$headers .= "From: \"Telecontrol\" <".$email.">\nBcc:wellington@telecontrol.com.br \r\n";
	$headers .= "Content-type: multipart/mixed; boundary=\"$boundary\"\r\n";

	mail($destinatario, utf8_encode($assunto), utf8_encode($mens), $headers);

/*
	if(mail($email, $assunto, $mens, $headers)){

	$email_origem  = "$email"."\nBcc:wellington@telecontrol.com.br";
	$email_destino = "wellington@telecontrol.com.br";
	//$email_destino = "rogerio.ribas@mondialline.com.br";
	$assunto       = "PESQUISA DE SATISFAÇÃO POSTO AUTORIZADO";
	
	$corpo  = "PESQUISA DE SATISFAÇÃO POSTO AUTORIZADO\n\n";
	$corpo .= "Ao Posto Autorizado\n";
	$corpo .= "A partir de suas respostas teremos a oportunidade de detectar os nossos pontos passíveis de melhoria.\n";
	$corpo .= "Obrigado por sua participação.\n";
	$corpo .= "Atenciosamente,";
	$corpo .= "\n\n";
	$corpo .= "Giovanni Marins Cardoso\n";
	$corpo .= "Diretor Comercial e Marketing\n\n\n";
	$corpo .= "Nome: $fantasia\n";
	$corpo .= "Razão Social: $nome\n";
	$corpo .= "Código Posto: $codigo_posto\n";
	$corpo .= "Endereço: $endereco\n";
	$corpo .= "Cidade: $cidade - $estado\n";
	$corpo .= "CEP: $cep\n";
	$corpo .= "E-Mail: $email\n";
	$corpo .= "Telefone: $fone\n\n";

	$corpo .= "1. COMO O SR. (A) CLASSIFICARIA O  NOSSO  PRAZO PARA ATENDIMENTO A PEDIDOS (OSG)?\n";
		if ($resp_1 == 'INSUFICIENTE') {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " INSUFICIENTE\n";
		if ($resp_1 == 'REGULAR')      {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " REGULAR\n";
		if ($resp_1 == 'BOM')          {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " BOM\n";
		if ($resp_1 == 'ÓTIMO')        {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " ÓTIMO\n";
		if ($resp_1 == 'EXCELENTE')    {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " EXCELENTE\n";
	$corpo .= "\n";
	$corpo .= "2. COMO O SR. (A) CLASSIFICARIA O  NOSSO  PRAZO PARA ATENDIMENTO A PEDIDOS (VENDA DE PEÇAS FORA DE GARANTIA)?\n";
		if ($resp_2 == 'INSUFICIENTE') {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " INSUFICIENTE\n";
		if ($resp_2 == 'REGULAR')      {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " REGULAR\n";
		if ($resp_2 == 'BOM')          {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " BOM\n";
		if ($resp_2 == 'ÓTIMO')        {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " ÓTIMO\n";
		if ($resp_2 == 'EXCELENTE')    {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " EXCELENTE\n";
	$corpo .= "\n";
	$corpo .= "3. AS ENTREGAS (VENDAS DE PEÇAS / OSG) ATENDEM AS ESPECIFICAÇÕES ESTABELECIDAS NOS PEDIDOS?\n";
		if ($resp_3 == 'NUNCA')                {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " NUNCA\n";
		if ($resp_3 == 'ALGUMAS VEZES')        {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " ALGUMAS VEZES\n";
		if ($resp_3 == 'METADE DAS VEZES')     {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " METADE DAS VEZES\n";
		if ($resp_3 == 'NA MAIORIA DAS VEZES') {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " NA MAIORIA DAS VEZES\n";
		if ($resp_3 == 'SEMPRE')               {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " SEMPRE\n";
	$corpo .= "\n";
	$corpo .= "4. COMO O SR. CLASSIFICARIA A TROCA DE INFORMAÇÕES ENTRE A ASSISTÊNCIA TÉCNICA E A MONDIAL SOBRE OS NOSSOS PRODUTOS?\n";
		if ($resp_4 == 'INSUFICIENTE') {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " INSUFICIENTE\n";
		if ($resp_4 == 'REGULAR')      {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " REGULAR\n";
		if ($resp_4 == 'BOM')          {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " BOM\n";
		if ($resp_4 == 'ÓTIMO')        {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " ÓTIMO\n";
		if ($resp_4 == 'EXCELENTE')    {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " EXCELENTE\n";
	$corpo .= "\n";
	$corpo .= "5. O SAC / PÓS VENDA, QUANDO ACIONADO, ATENDE AS VOSSAS NECESSIDADES?\n";
		if ($resp_5 == 'NUNCA')                {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " NUNCA\n";
		if ($resp_5 == 'ALGUMAS VEZES')        {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " ALGUMAS VEZES\n";
		if ($resp_5 == 'METADE DAS VEZES')     {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " METADE DAS VEZES\n";
		if ($resp_5 == 'NA MAIORIA DAS VEZES') {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " NA MAIORIA DAS VEZES\n";
		if ($resp_5 == 'SEMPRE')               {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " SEMPRE\n";
	$corpo .= "\n";
	$corpo .= "6. O SR. (A) ESTÁ SATISFEITO COM OS NOSSOS PRODUTOS?\n";
		if ($resp_6 == 'MUITO INSATISFEITO') {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " MUITO INSATISFEITO\n";
		if ($resp_6 == 'INSATISFEITO')       {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " INSATISFEITO\n";
		if ($resp_6 == 'POUCO INSATISFEITO') {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " POUCO INSATISFEITO\n";
		if ($resp_6 == 'SATISFEITO')         {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " SATISFEITO\n";
		if ($resp_6 == 'MUITO SATISFEITO')   {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " MUITO SATISFEITO\n";
	$corpo .= "\n";
	$corpo .= "7. O SR. (A) RECOMENDARIA A MARCA MONDIAL PARA UM AMIGO?\n";
		if ($resp_7 == 'SIM') {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " SIM\n";
		if ($resp_7 == 'NÃO') {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " NÃO. POR QUÊ?\n";
		if ($resp_7 == 'NÃO') {$corpo .= "   --> $comentario_7"; }
	$corpo .= "\n";
	$corpo .= "8. O SR. (A) ESTÁ SATISFEITO EM SER NOSSO PARCEIRO?\n";
		if ($resp_8 == 'SIM')          {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " SIM\n";
		if ($resp_8 == 'SIM CORREÇÃO') {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " SIM, COM CORREÇÕES. QUAIS?\n";
		if ($resp_8 == 'SIM CORREÇÃO') {$corpo .= "   --> $sim_comentario_8\n"; }
		if ($resp_8 == 'NÃO')          {$corpo .= '[x]'; }else{ $corpo .= '[ ]';} $corpo .= " NÃO. POR QUÊ?\n";
		if ($resp_8 == 'NÃO')          {$corpo .= "   --> $comentario_8"; }
	$corpo .= "\n\n";

	$body_top  = "--Message-Boundary\n";
	$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
	$body_top .= "Content-transfer-encoding: 7BIT\n";
	$body_top .= "Content-description: Mail message body\n\n";
	@mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem);
*/
	echo "
		<script>
		window.location='login.php';
		</script>
	";
}
?>


<style type="text/css">

.menu_top {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.border {
	border: 1px solid #ced7e7;
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

.style1 {font-weight: bold}
body {
	margin-left: 0px;
	margin-top: 00px;
	margin-right: 0px;
	margin-bottom: 0px;
}

</style>


<FORM METHOD=POST ACTION="<?$PHP_SELF?>">

<CENTER>

<? if (strlen($msg_erro) > 0) {
	echo "<TABLE width='610' align='center' cellspacing='0' cellpadding='0'>";
	echo "<TR><TD bgcolor='#FF0000' align='center' width='100%'><FONT FACE='arial' SIZE='2' color='#FFFFFF'><B>$msg_erro</B></FONT></TD></TR>";
	echo "</TABLE><BR>";
} ?>

<TABLE width='610' align='center' cellspacing='0' cellpadding='0' style='font-size: 11'>
<TR>
	<TD colspan='2' align='center' bgcolor='#d9e2ef'><FONT FACE='Arial' SIZE='2px'>Para prosseguir com a navegação no site responda o questionário abaixo:</FONT></TD>
</TR>
</TABLE><BR>

<TABLE width='610' align='center' cellspacing='0' cellpadding='0' style='font-size: 11'>
<TR>
	<TD colspan='2' align='center'><FONT FACE='Arial' SIZE='2px'><B>PESQUISA DE SATISFAÇÃO POSTO AUTORIZADO</B></FONT></TD>
</TR>
<TR><TD colspan='2'>&nbsp;</TD></TR>
<TR>
	<TD WIDTH='50%' align='center'><FONT FACE='Arial' SIZE='2'>ISO 9001</FONT></TD>
	<TD WIDTH='50%' align='center'><FONT FACE='Arial' SIZE='2'>Mar 2007</FONT></TD>
</TR>
<TR><TD colspan='2'>&nbsp;</TD></TR>
<TR>
	<TD colspan='2'><FONT FACE='Arial' SIZE='2'>
	Ao Posto Autorizado<BR>
	A partir de suas  respostas teremos a oportunidade de detectar os nossos pontos passíveis de melhoria.<BR>
	Obrigado por sua  participação.<BR>
	Atenciosamente,<BR><BR>
	Giovanni Marins Cardoso<BR>
	Diretor Comercial e Marketing
	</FONT></TD>
</TR>
</TABLE><BR><BR>

<TABLE width='610' class='border' border='0' align='center' cellspacing='0' cellpadding='0' border='1'>
<TR><TD colspan='3'><FONT FACE='Arial' SIZE='-1'>               Nome:         <B><?echo $fantasia    ?></B></FONT></TD></TR>
<TR><TD colspan='3'><FONT FACE='Arial' SIZE='-1'>Razão Social: <B><?echo $nome        ?></B></FONT></TD></TR>
<TR><TD colspan='3'><FONT FACE='Arial' SIZE='-1'>               Código Posto: <B><?echo $codigo_posto?></B></FONT></TD></TR>
<TR><TD colspan='3'><FONT FACE='Arial' SIZE='-1'>               Endereço:     <B><?echo $endereco    ?></B></FONT></TD></TR>
<TR><TD><FONT FACE='Arial' SIZE='-1'>                           Cidade:       <B><?echo $cidade      ?></B></FONT></TD><TD><FONT FACE='Arial' SIZE='-1'>Estado:   <B><?echo  $estado?></B></FONT></TD><TD><FONT FACE='Arial' SIZE='-1'>CEP: <B><?echo $cep?></B></FONT></TD></TR>
<TR><TD colspan='2'><FONT FACE='Arial' SIZE='-1'>               E-Mail:       <B><?echo $email       ?></B></FONT></TD><TD><FONT FACE='Arial' SIZE='-1'> Telefone: <B><?echo $fone  ?></B></FONT></TD></TR>
</TABLE><BR>
	

<TABLE class='border' width='610' align='center' cellspacing='0' cellpadding='0'style='font-size: 11'>
<TR class='menu_top'><TD colspan='2'>&nbsp;1. COMO O SR. (A) CLASSIFICARIA O  NOSSO  PRAZO PARA ATENDIMENTO A PEDIDOS (OSG)?</TD></TR>
<TR class='table_line'><TD width='1%'><INPUT TYPE="radio" NAME="resp_1" value='INSUFICIENTE' <? if ($resp_1 == 'INSUFICIENTE') echo 'checked'; ?>></TD><TD>&nbsp;INSUFICIENTE</TD></TR>
<TR class='table_line'><TD>           <INPUT TYPE="radio" NAME="resp_1" value='REGULAR'      <? if ($resp_1 == 'REGULAR')      echo 'checked'; ?>></TD><TD>&nbsp;REGULAR</TD></TR>
<TR class='table_line'><TD>           <INPUT TYPE="radio" NAME="resp_1" value='BOM'          <? if ($resp_1 == 'BOM')          echo 'checked'; ?>></TD><TD>&nbsp;BOM</TD></TR>
<TR class='table_line'><TD>           <INPUT TYPE="radio" NAME="resp_1" value='ÓTIMO'        <? if ($resp_1 == 'ÓTIMO')        echo 'checked'; ?>></TD><TD>&nbsp;ÓTIMO</TD></TR>
<TR class='table_line'><TD>           <INPUT TYPE="radio" NAME="resp_1" value='EXCELENTE'    <? if ($resp_1 == 'EXCELENTE')    echo 'checked'; ?>></TD><TD>&nbsp;EXCELENTE</TD></TR> 
</TABLE>
<BR>

<TABLE class='border' width='610' align='center' cellspacing='0' cellpadding='0'style='font-size: 11'>
<TR class='menu_top'><TD colspan='2'>&nbsp;2. COMO O SR. (A) CLASSIFICARIA O  NOSSO  PRAZO PARA ATENDIMENTO A PEDIDOS (VENDA DE PEÇAS FORA DE GARANTIA)?</TD></TR>
<TR class='table_line'><TD width='1%'><INPUT TYPE="radio" NAME="resp_2" value='INSUFICIENTE' <? if ($resp_2 == 'INSUFICIENTE') echo 'checked'; ?>></TD><TD>&nbsp;INSUFICIENTE</TD></TR>
<TR class='table_line'><TD>           <INPUT TYPE="radio" NAME="resp_2" value='REGULAR'      <? if ($resp_2 == 'REGULAR')      echo 'checked'; ?>></TD><TD>&nbsp;REGULAR</TD></TR>
<TR class='table_line'><TD>           <INPUT TYPE="radio" NAME="resp_2" value='BOM'          <? if ($resp_2 == 'BOM')          echo 'checked'; ?>></TD><TD>&nbsp;BOM</TD></TR>
<TR class='table_line'><TD>           <INPUT TYPE="radio" NAME="resp_2" value='ÓTIMO'        <? if ($resp_2 == 'ÓTIMO')        echo 'checked'; ?>></TD><TD>&nbsp;ÓTIMO</TD></TR>
<TR class='table_line'><TD>           <INPUT TYPE="radio" NAME="resp_2" value='EXCELENTE'    <? if ($resp_2 == 'EXCELENTE')    echo 'checked'; ?>></TD><TD>&nbsp;EXCELENTE</TD></TR> 
</TABLE>
<BR>

<TABLE class='border' width='610' align='center' cellspacing='0' cellpadding='0'style='font-size: 11'>
<TR class='menu_top'><TD colspan='2'>&nbsp;3. AS ENTREGAS (VENDAS DE PEÇAS / OSG)  ATENDEM AS ESPECIFICAÇÕES ESTABELECIDAS NOS PEDIDOS?</TD></TR>
<TR class='table_line'><TD width='1%'><INPUT TYPE="radio" NAME="resp_3" value='NUNCA'                <? if ($resp_3 == 'NUNCA')                echo 'checked'; ?>></TD><TD>&nbsp;NUNCA</TD></TR>
<TR class='table_line'><TD>           <INPUT TYPE="radio" NAME="resp_3" value='ALGUMAS VEZES'        <? if ($resp_3 == 'ALGUMAS VEZES')        echo 'checked'; ?>></TD><TD>&nbsp;ALGUMAS VEZES</TD></TR>
<TR class='table_line'><TD>           <INPUT TYPE="radio" NAME="resp_3" value='METADE DAS VEZES'     <? if ($resp_3 == 'METADE DAS VEZES')     echo 'checked'; ?>></TD><TD>&nbsp;METADE DAS VEZES</TD></TR>
<TR class='table_line'><TD>           <INPUT TYPE="radio" NAME="resp_3" value='NA MAIORIA DAS VEZES' <? if ($resp_3 == 'NA MAIORIA DAS VEZES') echo 'checked'; ?>></TD><TD>&nbsp;NA MAIORIA DAS VEZES</TD></TR>
<TR class='table_line'><TD>           <INPUT TYPE="radio" NAME="resp_3" value='SEMPRE'               <? if ($resp_3 == 'SEMPRE')               echo 'checked'; ?>></TD><TD>&nbsp;SEMPRE</TD></TR> 
</TABLE>
<BR>

<TABLE class='border' width='610' align='center' cellspacing='0' cellpadding='0'style='font-size: 11'>
<TR class='menu_top'><TD colspan='2'>&nbsp;4. COMO O SR. CLASSIFICARIA A TROCA DE INFORMAÇÕES ENTRE A ASSISTÊNCIA TÉCNICA E A MONDIAL SOBRE OS NOSSOS PRODUTOS?</TD></TR>
<TR class='table_line'><TD width='1%'><INPUT TYPE="radio" NAME="resp_4" value='INSUFICIENTE' <? if ($resp_4 == 'INSUFICIENTE') echo 'checked'; ?>></TD><TD>&nbsp;INSUFICIENTE</TD></TR>
<TR class='table_line'><TD>           <INPUT TYPE="radio" NAME="resp_4" value='REGULAR'      <? if ($resp_4 == 'REGULAR')      echo 'checked'; ?>></TD><TD>&nbsp;REGULAR</TD></TR>
<TR class='table_line'><TD>           <INPUT TYPE="radio" NAME="resp_4" value='BOM'          <? if ($resp_4 == 'BOM')          echo 'checked'; ?>></TD><TD>&nbsp;BOM</TD></TR>
<TR class='table_line'><TD>           <INPUT TYPE="radio" NAME="resp_4" value='ÓTIMO'        <? if ($resp_4 == 'ÓTIMO')        echo 'checked'; ?>></TD><TD>&nbsp;ÓTIMO</TD></TR>
<TR class='table_line'><TD>           <INPUT TYPE="radio" NAME="resp_4" value='EXCELENTE'    <? if ($resp_4 == 'EXCELENTE')    echo 'checked'; ?>></TD><TD>&nbsp;EXCELENTE</TD></TR> 
</TABLE>
<BR>

<TABLE class='border' width='610' align='center' cellspacing='0' cellpadding='0'style='font-size: 11'>
<TR class='menu_top'><TD colspan='2'>&nbsp;5. O SAC / PÓS VENDA,  QUANDO ACIONADO, ATENDE AS  VOSSAS NECESSIDADES?</TD></TR>
<TR class='table_line'><TD width='1%'><INPUT TYPE="radio" NAME="resp_5" value='NUNCA'                <? if ($resp_5 == 'NUNCA')                echo 'checked'; ?>></TD><TD>&nbsp;NUNCA</TD></TR>
<TR class='table_line'><TD>           <INPUT TYPE="radio" NAME="resp_5" value='ALGUMAS VEZES'        <? if ($resp_5 == 'ALGUMAS VEZES')        echo 'checked'; ?>></TD><TD>&nbsp;ALGUMAS VEZES</TD></TR>
<TR class='table_line'><TD>           <INPUT TYPE="radio" NAME="resp_5" value='METADE DAS VEZES'     <? if ($resp_5 == 'METADE DAS VEZES')     echo 'checked'; ?>></TD><TD>&nbsp;METADE DAS VEZES</TD></TR>
<TR class='table_line'><TD>           <INPUT TYPE="radio" NAME="resp_5" value='NA MAIORIA DAS VEZES' <? if ($resp_5 == 'NA MAIORIA DAS VEZES') echo 'checked'; ?>></TD><TD>&nbsp;NA MAIORIA DAS VEZES</TD></TR>
<TR class='table_line'><TD>           <INPUT TYPE="radio" NAME="resp_5" value='SEMPRE'               <? if ($resp_5 == 'SEMPRE')               echo 'checked'; ?>></TD><TD>&nbsp;SEMPRE</TD></TR> 
</TABLE>
<BR>

<TABLE class='border' width='610' align='center' cellspacing='0' cellpadding='0'style='font-size: 11'>
<TR class='menu_top'><TD colspan='2'>&nbsp;6. O SR. (A) ESTÁ SATISFEITO COM OS NOSSOS PRODUTOS?</TD></TR>
<TR class='table_line'><TD width='1%'><INPUT TYPE="radio" NAME="resp_6" value='MUITO INSATISFEITO' <? if ($resp_6 == 'MUITO INSATISFEITO') echo 'checked'; ?>></TD><TD>&nbsp;MUITO INSATISFEITO</TD></TR>
<TR class='table_line'><TD>           <INPUT TYPE="radio" NAME="resp_6" value='INSATISFEITO'       <? if ($resp_6 == 'INSATISFEITO')       echo 'checked'; ?>></TD><TD>&nbsp;INSATISFEITO</TD></TR>
<TR class='table_line'><TD>           <INPUT TYPE="radio" NAME="resp_6" value='POUCO INSATISFEITO' <? if ($resp_6 == 'POUCO INSATISFEITO') echo 'checked'; ?>></TD><TD>&nbsp;POUCO INSATISFEITO</TD></TR>
<TR class='table_line'><TD>           <INPUT TYPE="radio" NAME="resp_6" value='SATISFEITO'         <? if ($resp_6 == 'SATISFEITO')         echo 'checked'; ?>></TD><TD>&nbsp;SATISFEITO</TD></TR>
<TR class='table_line'><TD>           <INPUT TYPE="radio" NAME="resp_6" value='MUITO SATISFEITO'   <? if ($resp_6 == 'MUITO SATISFEITO')   echo 'checked'; ?>></TD><TD>&nbsp;MUITO SATISFEITO</TD></TR> 
</TABLE>
<BR>

<TABLE class='border' width='610' align='center' cellspacing='0' cellpadding='0'style='font-size: 11'>
<TR class='menu_top'><TD colspan='3'>&nbsp;7. O SR. (A) RECOMENDARIA A MARCA MONDIAL  PARA UM AMIGO?</TD></TR>
<TR class='table_line'><TD width='1%'><INPUT TYPE="radio" NAME="resp_7" value='SIM' <? if ($resp_7 == 'SIM') echo 'checked'; ?>></TD><TD colspan='2'>&nbsp;SIM</TD></TR>
<TR class='table_line'><TD>           <INPUT TYPE="radio" NAME="resp_7" value='NÃO' <? if ($resp_7 == 'NÃO') echo 'checked'; ?>></TD><TD>&nbsp;NÃO. POR QUÊ?</TD><TD><TEXTAREA NAME="comentario_7" COLS="56" ROWS="3" class="frm_input" id="comentario_7" onKeyPress="return  Contador('comentario_7',300);"><? if (strlen($comentario_7) > 0) echo $comentario_7 ?></TEXTAREA></TD></TR>
</TABLE>
<BR>

<TABLE class='border' width='610' align='center' cellspacing='0' cellpadding='0'style='font-size: 11'>
<TR class='menu_top'><TD colspan='3'>&nbsp;8. O SR. (A) ESTÁ SATISFEITO EM SER  NOSSO PARCEIRO?</TD></TR>
<TR class='table_line'><TD width='1%'><INPUT TYPE="radio" NAME="resp_8" value='SIM'          <? if ($resp_8 == 'SIM')          echo 'checked'; ?>></TD><TD colspan='2'>&nbsp;SIM</TD></TR>
<TR class='table_line'><TD>           <INPUT TYPE="radio" NAME="resp_8" value='SIM CORREÇÃO' <? if ($resp_8 == 'SIM CORREÇÃO') echo 'checked'; ?>></TD><TD>&nbsp;SIM, COM CORREÇÕES. QUAIS?</TD><TD><TEXTAREA NAME="sim_comentario_8" COLS="50" ROWS="3" class="frm_input" id="sim_comentario_8" onKeyPress="return  Contador('sim_comentario_8',150);"><? if (strlen($sim_comentario_8) > 0) echo $sim_comentario_8 ?></TEXTAREA></TD></TR>
<TR class='table_line'><TD>           <INPUT TYPE="radio" NAME="resp_8" value='NÃO'          <? if ($resp_8 == 'NÃO')          echo 'checked'; ?>></TD><TD>&nbsp;NÃO. POR QUÊ?</TD><TD><TEXTAREA NAME="comentario_8" COLS="50" ROWS="3" class="frm_input" id="comentario_8" onKeyPress="return  Contador('comentario_8',300);"><? if (strlen($comentario_8) > 0) echo $comentario_8 ?></TEXTAREA></TD></TR>
</TABLE>
<BR>

<TABLE class='border' width='610' align='center' cellspacing='0' cellpadding='0'style='font-size: 11'>
<TR class='menu_top'><TD colspan='3'>&nbsp;Espaço reservado para críticas, sugestões e/ou elogios:</TD></TR>
<TR class='table_line'><TD>
<TEXTAREA   NAME="comentarios" COLS="73" ROWS="3" class="frm_input" id="comentarios" onKeyPress="return  Contador('comentarios',400);"><?if (strlen($comentarios) > 0) echo $comentarios ?></TEXTAREA></TD></TR>
</TABLE>
<BR>


<p align='center'><INPUT TYPE="submit" name='formulario' value='Enviar'></p>

</FORM>

</CENTER>
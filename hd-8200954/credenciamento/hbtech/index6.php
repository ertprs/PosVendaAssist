<?

include "/www/assist/www/dbconfig.php";
include "/www/assist/www/includes/dbconnect-inc.php";
//include '../autentica_usuario.php';


$fabrica  = trim($_GET['key']);
$posto    = trim($_GET['id']);
$key      = $fabrica;
$btn_acao = $_POST['btn_acao'];

if(strlen($posto) == 0){
	$posto = $_POST['posto'];
}

$fabrica = "25";

if(strlen($posto) == 0){
	$msg_erro = "Por favor, verifique o link de acesso";
}


if(strlen($msg_erro) == 0){
	$sql = "SELECT posto       ,
					cnpj       ,
					nome       ,
					contato    ,
					fone       ,
					email
				FROM tbl_posto_fabrica 
				JOIN tbl_posto using(posto)
				WHERE posto  = $posto
				AND fabrica  = $fabrica;";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res) == 0){
		$msg_erro = "Por favor verifique o link de acesso.";
	}else{
		$cnpj     = pg_result($res,0,cnpj);
		$razao    = pg_result($res,0,nome);
		$titular  = pg_result($res,0,contato);
		$telefone = pg_result($res,0,fone);
		$email    = pg_result($res,0,email);
	}
}

if($btn_acao == 'Enviar'){
	if(strlen($cnpj)   == 0) $cnpj       = trim($_POST['cnpj']);
	if(strlen($razao)  == 0) $razao      = trim($_POST['empresa']);
	if(strlen($razao)  == 0) $email      = trim($_POST['email']);
	if(strlen($linha1) == 0) $linha1     = trim($_POST['linha1']);
	if(strlen($linha2) == 0) $linha2     = trim($_POST['linha2']);
	if(strlen($linha3) == 0) $linha3     = trim($_POST['linha3']);
	if(strlen($linha4) == 0) $linha4     = trim($_POST['linha4']);

	if(strlen($atendentes0) == 0) $atendentes0  = trim($_POST['atendentes0']);
	if(strlen($atendentes1) == 0) $atendentes1  = trim($_POST['atendentes1']);
	if(strlen($atendentes2) == 0) $atendentes2  = trim($_POST['atendentes2']);
	if(strlen($atendentes3) == 0) $atendentes3  = trim($_POST['atendentes3']);
	if(strlen($atendentes4) == 0) $atendentes4  = trim($_POST['atendentes4']);

	if(strlen($linha1) == 0 AND strlen($linha2) == 0 AND strlen($linha3) == 0 AND strlen($linha4) == 0){
		$msg_erro = "Por favor, escolha ao menos 1 linha de atuação.";
	}

	if(strlen($atendentes0) == 0 AND strlen($atendentes1) == 0 AND strlen($atendentes2) == 0 AND strlen($atendentes3) == 0 AND strlen($atendentes4) == 0){
		$msg_erro = "Por favor, digite ao menos 1 atendente.";
	}else{
		$atendente_posto = trim($atendentes0) . ";" . trim($atendentes1) . ";" . trim($atendentes2) . ";" . trim($atendentes3) . ";" . trim($atendentes4);
	}

	$titular    = trim($_POST['titular']);
	if(strlen($titular) < 2){
		$msg_erro = "Por favor, preencha/verifique o campo Titular.";
	}
	$telefone   = trim($_POST['telefone']);
	if(strlen($telefone) == 0){
		$msg_erro = "Por favor, preencha/verifique o campo do Telefone.";
	}

	if(strlen($msg_erro) == 0 AND strlen($posto) > 0){

		$res = pg_exec ($con,"BEGIN TRANSACTION");

		$sql = "UPDATE tbl_posto_fabrica 
								SET contato_nome           = '$titular'         ,
									contato_fone_comercial = '$telefone'        ,
									contato_atendentes     = '$atendente_posto' ,
									contrato               = current_timestamp
					WHERE tbl_posto_fabrica.fabrica = $fabrica
					AND  tbl_posto_fabrica.posto    = $posto;";
		$res = pg_exec($con,$sql);

		$sql = "DELETE FROM tbl_posto_linha WHERE linha IN(385,404,502,489) AND posto = $posto;";
		$res = pg_exec($con,$sql);

		if(strlen($linha1) > 0){
			$sql = "INSERT INTO tbl_posto_linha (posto, linha, ativo) VALUES ($posto,$linha1,'t');";
			$res = pg_exec($con,$sql);
		}

		if(strlen($linha2) > 0){
			$sql = "INSERT INTO tbl_posto_linha (posto, linha, ativo) VALUES ($posto,$linha2,'t');";
			$res = pg_exec($con,$sql);
		}

		if(strlen($linha3) > 0){
			$sql = "INSERT INTO tbl_posto_linha (posto, linha, ativo) VALUES ($posto,$linha3,'t');";
			$res = pg_exec($con,$sql);
		}

		if(strlen($linha4) > 0){
			$sql = "INSERT INTO tbl_posto_linha (posto, linha, ativo) VALUES ($posto,$linha4,'t');";
			$res = pg_exec($con,$sql);
		}

//		$email_posto = 'fernando@telecontrol.com.br'; //<------
		$email_posto = $email;
		$mensagem    = '';

		$nome       = "HBFLEX";
		$email       = "$email_posto";


		$mensagem  .= "<table border='0' width='500' align='center'>";
		$mensagem  .= "<tr><td colspan='3'><img src='http://www.telecontrol.com.br/assist/credenciamento/hbtech/img_topo.gif'></td></tr>";
		$mensagem  .= "<tr><td width='10'>&nbsp;</td><td align='right'>São Paulo, Abril de 2008</td><td width='10'>&nbsp;</td></tr>";
		$mensagem  .= "<tr><td width='10'>&nbsp;</td>";
		$mensagem  .= "<td><br>Prezado (s),<br><br>";
		$mensagem  .= "<p align='justify'>Antes de darmos as boas vindas, queremos agradecer pela credibilidade e confiança.</p>";
		$mensagem  .= "<p align='justify'>A HBFlex é uma nova marca, assim como a filosofia e a tecnologia que está trazendo ao país. 
						Seu objetivo é ser sempre atual e inovador, tornando acessíveis e simultâneos os lançamentos de novas tecnologias 
						que antes só aconteciam no exterior, demorando alguns meses para chegar ao Brasil. Com uma linha de produtos 
						eletroeletrônicos e de eletrodomésticos, a empresa não quer apenas entrar no lar dos consumidores brasileiros, 
						mas, sim, torná-los ainda mais confortáveis, práticos e seguros.</p>";
		$mensagem  .= "<p align='justify'>Nós da HBFLEX estamos satisfeitos em poder contar com a parceria dessa competente empresa, e temos 
						certeza que nossos consumidores serão sempre muito bem atendidos, pois eles são muito importantes para todos nós.</p>";
		$mensagem  .= "<p align='justify'>Os primeiros lotes dos nossos produtos já estão chegando ao mercado brasileiro, e para conhecê-los, 
						convidamos você e todos os seus colaboradores a visitar o nosso <a href='http://www.hbflex.com' target='BLANK_'>Website</a>.</p>";
		$mensagem  .= "<p align='justify'>Colocamos a disposição de vocês nossa equipe do Departamento de Pós Venda, através do telefone <b>(11) 2142-8300</b> 
						ou pelo endereço eletrônico <b>suporte@hbflex.com</b>, eles estão devidamente treinados e preparados para atender a quaisquer dúvidas.</p>";
		$mensagem  .= "<br><p align='justify'>Sejam bem vindos à HBFLEX.</p>";
		$mensagem  .= "<br><p align='justify'>Sucesso.</p><br><br>";
		$mensagem  .= "<td width='10'>&nbsp;</td></tr>";
		$mensagem  .= "<tr><td colspan='3'><img src='http://www.telecontrol.com.br/assist/credenciamento/hbtech/img_rodape.gif'></td></tr>";
		$mensagem  .= "</table>";

		$assunto   = "AUTO CADASTRAMENTO - HBFLEX";
		$anexos    = 0;
		$boundary = "";

		$mens = "$mensagem\n";

		$headers  = "MIME-Version: 1.0\n";
		$headers .= "Date: ".date("D, d M Y H:i:s O")."\n";
		$headers .= "From: \"HBFLEX\" <suporte@hbflex.com>\r\n";
		$headers .= "Content-type: text/html; charset=\"ISO-8859-1\"\n\n";

//	echo "$mensagem";

		if(mail($email, $assunto, $mens, $headers)){
			echo "<table border='0' width='700' align='center' style='background-repeat: no-repeat;' background='http://www.telecontrol.com.br/assist/credenciamento/hbtech/superior.jpg'>";
			echo "<tr>";
				echo "<td width='650'><br><br><br><br><br><br><br><br>";
			echo "</tr>";
			echo "<tr>";
			echo "<td>";
			echo "<table align='center' width='600' border='0' cellspacing='2' cellpadding='0'>";
			echo "<tr>";
				echo "<td align='center'>Obrigado pelo preenchimento das informações.<br>Em instantes você estará recebendo um e-mail com mais informativos!</td>";
			echo "</tr>";
			echo "</table>";
			echo "</td>";
			echo "</tr>";
			echo "<tr>";
				echo "<td colspan='2' style='background-position: bottom ; background-repeat: no-repeat;' background='http://www.telecontrol.com.br/assist/credenciamento/hbtech/inferior.jpg'><br><br><br><br><br><br><br><br><br></td>";
			echo "</tr>";
			echo "</table>";
		}

		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			exit;
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
}
?>


<TITLE> AUTO CREDENCIAMENTO </TITLE>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 14px;
	font-weight: bold;
	border: 2px solid;
	color:#ffffff;
	background-color: #F38101
}
.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #f5f5f5
}
.table_line2 {
	text-align: left;
	background-color: #fcfcfc
}
.ok {
	text-align: left;
	background-color: #f5f5f5;
	border:1px solid gray;
	font-size:12px;
	font-weight:bold;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
}

</style>


<style type="text/css">

input.botao {
	background:#ffffff;
	color:#000000;
	border:1px solid #d2e4fc;
}

.Tabela{
	border:1px solid #d2e4fc;
}

.Tabela2{
	border:1px dotted #C3C3C3;
}

a.conteudo{
	color: #FFFFFF;
	font-family: Arial;
	FONT-SIZE: 8pt; 
	font-weight: bold;
	text-decoration: none;
	text-align: center;
	border: none;
}
a.conteudo:visited {
	color: #FFFFFF;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-decoration: none;
	text-align: center;
	border: none;
}

a.conteudo:hover {
	color: #FFFFCC;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-decoration: none;
	text-align: center;
	border: none;
}

a.conteudo:active {
	color: #FFFFFF;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-decoration: none;
	text-align: center;
	border: none;
}
</style>

<script type="text/javascript">
function txtBoxFormat(objeto, sMask, evtKeyPress) {
	var i, nCount, sValue, fldLen, mskLen,bolMask, sCod, nTecla;

	if(document.all) { // Internet Explorer
		nTecla = evtKeyPress.keyCode;
	} else if(document.layers) { // Nestcape
		nTecla = evtKeyPress.which;
	} else {
		nTecla = evtKeyPress.which;
		if (nTecla == 8) {
			return true;
		}
	}

	sValue = objeto.value;

	// Limpa todos os caracteres de formatação que
	// já estiverem no campo.
	sValue = sValue.toString().replace( "-", "" );
	sValue = sValue.toString().replace( "-", "" );
	sValue = sValue.toString().replace( ".", "" );
	sValue = sValue.toString().replace( ".", "" );
	sValue = sValue.toString().replace( "/", "" );
	sValue = sValue.toString().replace( "/", "" );
	sValue = sValue.toString().replace( ":", "" );
	sValue = sValue.toString().replace( ":", "" );
	sValue = sValue.toString().replace( "(", "" );
	sValue = sValue.toString().replace( "(", "" );
	sValue = sValue.toString().replace( ")", "" );
	sValue = sValue.toString().replace( ")", "" );
	sValue = sValue.toString().replace( " ", "" );
	sValue = sValue.toString().replace( " ", "" );
	fldLen = sValue.length;
	mskLen = sMask.length;

	i = 0;
	nCount = 0;
	sCod = "";
	mskLen = fldLen;

	while (i <= mskLen) {
		bolMask = ((sMask.charAt(i) == "-") || (sMask.charAt(i) == ".") || (sMask.charAt(i) == "/") || (sMask.charAt(i) == ":"))
		bolMask = bolMask || ((sMask.charAt(i) == "(") || (sMask.charAt(i) == ")") || (sMask.charAt(i) == " "))

	if (bolMask) {
		sCod += sMask.charAt(i);
		mskLen++; }
	else {
		sCod += sValue.charAt(nCount);
		nCount++;
	}

	  i++;
	}

	objeto.value = sCod;

	if (nTecla != 8) { // backspace
		if (sMask.charAt(i-1) == "9") { // apenas números...
			return ((nTecla > 47) && (nTecla < 58)); } 
		else { // qualquer caracter...
			return true;
	}
	}
	else {
		return true;
	}
}

function formata_cnpj(cnpj, form){
	var mycnpj = '';
	mycnpj = mycnpj + cnpj;
	myrecord = "cnpj";
	myform = form;

	if (mycnpj.length == 2){
		mycnpj = mycnpj + '.';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}
	if (mycnpj.length == 6){
		mycnpj = mycnpj + '.';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}
	if (mycnpj.length == 10){
		mycnpj = mycnpj + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}
	if (mycnpj.length == 15){
		mycnpj = mycnpj + '-';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}
}

</script>


<?
if(strlen($msg_erro) > 0){
	echo "<p align='center'>$msg_erro</p>";
}
//	exit;
?>
<FORM METHOD=POST ACTION="<?echo $PHP_SELF;?>" NAME='frm_cadastramento'>
<INPUT TYPE="hidden" name='posto' value='<? echo "$posto"; ?>'>
<table border='0' width='700' align='center' style='background-repeat: no-repeat;' background='http://www.telecontrol.com.br/assist/credenciamento/hbtech/superior.jpg'>
<tr>
	<td width='650'><br><br><br><br><br><br><br><br>
</tr>
<tr>
<td>
<table align='center' width='600' border='0' cellspacing='2' cellpadding='0'>
<tr>
	<td align='center' colspan='2' class="menu_top"><b><u>AUTO CADASTRAMENTO</u></b></td>
</tr>
<tr><? $cnpj = substr($cnpj,0,3) . "." . substr($cnpj,3,3) . "." . substr($cnpj,6,3) . "/" . substr($cnpj,8,4) . "-" . substr($cnpj,12,2); ?>
	<td align='center' class="table_line">CNPJ</td><td class='table_line2'><b><? echo "$cnpj";?></b></td>
</tr>
<tr>
	<td align='center' class="table_line">Empresa</td><td class='table_line2' nowrap><b><? echo "$razao";?></b></td>
</tr>
<tr>
	<td align='center' class="table_line">Titular</td><td class='table_line2'><INPUT TYPE="text" NAME="titular" size='30' value='<? echo "$titular"; ?>'></td>
</tr>
<tr>
	<td align='center' class="table_line">Telefone Comercial</td><td class='table_line2'><INPUT TYPE="text" NAME="telefone" size='15' maxlength='13' onkeypress="return txtBoxFormat(this, '999-9999-9999', event);" value='<? echo "$telefone"; ?>'>Ex. 000-0000-0000</td>
</tr>
<tr>
	<td align='center' class="table_line">E-mail</td><td class='table_line2' nowrap><b><? echo "$email"; ?></b></td>
</tr>
<tr>
	<td align='center' class="table_line">Nome dos atendentes pela recepção dos produtos</td>
	<td class='table_line2'>
		<INPUT TYPE="text" NAME="atendentes0" size='30' maxlength='30'>
		<br><INPUT TYPE="text" NAME="atendentes1" size='30' maxlength='30'>
		<br><INPUT TYPE="text" NAME="atendentes2" size='30' maxlength='30'>
		<br><INPUT TYPE="text" NAME="atendentes3" size='30' maxlength='30'>
		<br><INPUT TYPE="text" NAME="atendentes4" size='30' maxlength='30'>
	</td>
</tr>
<tr>
	<td align='center' class="table_line">Quais as linhas que sua empresa quer atender?</td>
	<td class='table_line2' nowrap>
		    <INPUT TYPE="checkbox" NAME="linha1" value='385'>BRANCA - adega, refrigeração - ar condicionado (split, janela,..).
		<br><INPUT TYPE="checkbox" NAME="linha2" value='404'>MARRON - áudio e video (DVD,MP3, MP4,...).
		<br><INPUT TYPE="checkbox" NAME="linha3" value='502'>ELETROPORTÁTEIS - liquidificadores, ventiladores,...
		<br><INPUT TYPE="checkbox" NAME="linha4" value='489'>INFORMÁTICA - notebook, monitores,...
	</td>
</tr>
<tr>
	<td align='center' colspan='2'><br><INPUT TYPE="submit" name='btn_acao' value='Enviar'></td>
</tr>
</table>
</td>
</tr>
<tr>
	<td colspan='2' style='background-position: bottom ; background-repeat: no-repeat;' background='http://www.telecontrol.com.br/assist/credenciamento/hbtech/inferior.jpg'><br><br><br><br><br><br><br></td>
</tr>
</table>
</FORM>


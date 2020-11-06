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

if(strlen($key) == 0){
	$key = $_POST['key'];
}

$fabrica = "25";

if(strlen($posto) == 0){
	$msg_erro = "Por favor, verifique o link de acesso";
}

if(strlen($msg_erro) == 0) {
	$sql    = "SELECT DISTINCT  tbl_posto.posto,
						upper(nome)   as nome   ,
						endereco       ,
						numero         ,
						complemento    ,
						cidade         ,
						estado         ,
						SUBSTR (tbl_posto.cep,1,2) || '.' || SUBSTR (tbl_posto.cep,3,3) || '-' || SUBSTR (tbl_posto.cep,6,3) AS cep ,
						SUBSTR (tbl_posto.cnpj,1,2) || '.' || SUBSTR (tbl_posto.cnpj,3,3) || '.' || SUBSTR (tbl_posto.cnpj,6,3) || '/' || SUBSTR (tbl_posto.cnpj,9,4) || '-' || SUBSTR (tbl_posto.cnpj,13,2) AS cnpj     ,
						posto          ,
						to_char(current_date,'DD/MM/YYYY') as data_contrato
					FROM tbl_posto JOIN tbl_posto_fabrica using(posto)
					WHERE  posto = $posto;";
	$res = pg_exec($con,$sql);


	$razao     = pg_result($res,0,nome);
	$endereco       = pg_result($res,0,endereco);
	$numero         = pg_result($res,0,numero);
	$complemento    = pg_result($res,0,complemento);
	$cidade         = pg_result($res,0,cidade);
	$estado         = pg_result($res,0,estado);
	$cep            = pg_result($res,0,cep);
	$cnpj           = pg_result($res,0,cnpj);
	$posto          = pg_result($res,0,posto);
	$data_contrato  = pg_result($res,0,data_contrato);
}

##CABEÇALHO
if($key == md5(25)){
	$conteudo = "
		<html xmlns:o='urn:schemas-microsoft-com:office:office'
		xmlns:w='urn:schemas-microsoft-com:office:word'
		xmlns:st1='urn:schemas-microsoft-com:office:smarttags'
		xmlns='http://www.w3.org/TR/REC-html40'>

		<head>
		<meta http-equiv=Content-Type content='text/html; charset=windows-1252'>
		<meta name=ProgId content=Word.Document>
		<meta name=Generator content='Microsoft Word 11'>
		<meta name=Originator content='Microsoft Word 11'>
		<link rel=File-List
		href='Contrato%20Credenciamento%20Postos_arquivos/filelist.xml'>
		<link rel=Preview href='Contrato%20Credenciamento%20Postos_arquivos/preview.wmf'>
		<title>CONTRATO DE CREDENCIAMENTO DE ASSISTÊNCIA TÉCNICA</title>
		<o:SmartTagType namespaceuri='urn:schemas-microsoft-com:office:smarttags'
		 name='PersonName'/>
		<!--[if gte mso 9]><xml>
		 <o:DocumentProperties>
		  <o:Author>Luís Rodolfo Creuz</o:Author>
		  <o:LastAuthor>Túlio Oliveira</o:LastAuthor>
		  <o:Revision>2</o:Revision>
		  <o:TotalTime>2</o:TotalTime>
		  <o:LastPrinted>2113-01-01T03:00:00Z</o:LastPrinted>
		  <o:Created>2007-12-03T19:34:00Z</o:Created>
		  <o:LastSaved>2007-12-03T19:34:00Z</o:LastSaved>
		  <o:Pages>1</o:Pages>
		  <o:Words>3941</o:Words>
		  <o:Characters>21282</o:Characters>
		  <o:Company>Telecontrol</o:Company>
		  <o:Lines>177</o:Lines>
		  <o:Paragraphs>50</o:Paragraphs>
		  <o:CharactersWithSpaces>25173</o:CharactersWithSpaces>
		  <o:Version>11.5606</o:Version>
		 </o:DocumentProperties>
		</xml><![endif]--><!--[if gte mso 9]><xml>
		 <w:WordDocument>
		  <w:PunctuationKerning/>
		  <w:DrawingGridHorizontalSpacing>0 pt</w:DrawingGridHorizontalSpacing>
		  <w:DrawingGridVerticalSpacing>0 pt</w:DrawingGridVerticalSpacing>
		  <w:DisplayHorizontalDrawingGridEvery>0</w:DisplayHorizontalDrawingGridEvery>
		  <w:DisplayVerticalDrawingGridEvery>0</w:DisplayVerticalDrawingGridEvery>
		  <w:UseMarginsForDrawingGridOrigin/>
		  <w:ValidateAgainstSchemas/>
		  <w:SaveIfXMLInvalid>false</w:SaveIfXMLInvalid>
		  <w:IgnoreMixedContent>false</w:IgnoreMixedContent>
		  <w:AlwaysShowPlaceholderText>false</w:AlwaysShowPlaceholderText>
		  <w:DrawingGridHorizontalOrigin>0 pt</w:DrawingGridHorizontalOrigin>
		  <w:DrawingGridVerticalOrigin>0 pt</w:DrawingGridVerticalOrigin>
		  <w:Compatibility>
		   <w:SpaceForUL/>
		   <w:BalanceSingleByteDoubleByteWidth/>
		   <w:DoNotLeaveBackslashAlone/>
		   <w:ULTrailSpace/>
		   <w:DoNotExpandShiftReturn/>
		   <w:AdjustLineHeightInTable/>
		   <w:SelectEntireFieldWithStartOrEnd/>
		   <w:UseWord2002TableStyleRules/>
		  </w:Compatibility>
		  <w:BrowserLevel>MicrosoftInternetExplorer4</w:BrowserLevel>
		 </w:WordDocument>
		</xml><![endif]--><!--[if gte mso 9]><xml>
		 <w:LatentStyles DefLockedState='false' LatentStyleCount='156'>
		 </w:LatentStyles>
		</xml><![endif]--><!--[if !mso]><object
		 classid='clsid:38481807-CA0E-42D2-BF39-B33AF135CC4D' id=ieooui></object>
		<style>
		st1\:*{behavior:url(\#ieooui) }
		</style>
		<![endif]-->
		</head>

		<table cellspacing='0' cellpadding='0' width='600'>
		<tr>
			<td>
				<img width='600' src='http://www.telecontrol.com.br/assist/credenciamento/hbtech/cabecalho_hbtech.gif'></b></p>
			</td>
		</tr>
		<tr>
			<td>
				<p align='center'><b><FONT SIZE='25'>Termo de Adesão</FONT></b></p>
			</td>
		</tr>
		<tr>
			<td><br><br><br></td>
		</tr>
		<tr align='center'>
			<td>
				<FONT SIZE='35'><b>Concordo em compor a Rede Autorizada HBFLEX<br>
				nas seguintes linhas em que sou <br>
				especializado e receberei os valores de<br>
				mão-de-obra informados abaixo.</b></FONT><br><br><br>
			</td>
		</tr>
		<tr>
			<td><br><br><br></td>
		</tr>
		<tr>
			<td>
				<FONT SIZE='15'>(  ) eletrônicos –DVD R$20,00 / MP3,MP4 R$ 10,00</FONT><br>
				<FONT SIZE='15'>(  ) refrigeração -  R$ 40,00 c/ R$ 0,52</FONT><FONT SIZE='5'> acima do 60º km rodado</FONT><br>
				<FONT SIZE='15'>(  ) branca – R$ 18,00</FONT><br>
				<FONT SIZE='15'>(  ) eletroportáteis -  R$ 10,00</FONT><br><br><br>
			</td>
		</tr>
		<tr>
			<td><br><br><br></td>
		</tr>
		<tr>
			<td>
				<FONT SIZE='15'>Razão Social:&nbsp;<b>$razao</b></FONT><br>
				<FONT SIZE='15'>CNPJ:&nbsp;<b>$cnpj</b></FONT><br>
				<FONT SIZE='15'>Banco:</FONT><br>
				<FONT SIZE='15'>Agência:</FONT><br>
				<FONT SIZE='15'>Conta:</FONT><br><br><br>
			</td>
		</tr>
		<tr>
			<td>
				<FONT SIZE='15'>Assinatura: ____________________________</FONT><br>
				<FONT SIZE='15'>Titular   :</FONT><br>
			</td>
		</tr>
		<tr>
			<td><br><br><br></td>
		</tr>
		<tr>
			<td><br><br><br></td>
		</tr>
		<tr>
			<td>
				<img width='600' src='http://www.telecontrol.com.br/assist/credenciamento/hbtech/rodape_hbtech.gif'></b></p>
			</td>
		</tr>
		</table>
	";

	echo `mkdir /tmp/hbtech`;
	echo `chmod 777 /tmp/hbtech`;
	echo `rm /tmp/hbtech/contrato_$posto.htm`;
	echo `rm /tmp/hbtech/contrato_$posto.pdf`;
	echo `rm /var/www/assist/www/credenciamento/contrato/contrato_$posto.pdf`;


	if(strlen($msg_erro) == 0){
		$abrir = fopen("/tmp/hbtech/contrato_$posto.htm", "w");
		if (!fwrite($abrir, $conteudo)) {
			$msg_erro = "Erro escrevendo no arquivo ($filename)";
		}
		fclose($abrir); 
	}


	//gera o pdf
	echo `htmldoc --webpage --no-duplex --no-embedfonts --header ... --permissions no-modify,no-copy --fontsize 8.5 --no-title -f /tmp/hbtech/contrato_$posto.pdf /tmp/hbtech/contrato_$posto.htm`;
	echo `mv  /tmp/hbtech/contrato_$posto.pdf /var/www/assist/www/credenciamento/contrato/contrato_hbtech$posto.pdf`;
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

<INPUT TYPE="hidden" name='posto' value='<? echo "$posto"; ?>'>
<INPUT TYPE="hidden" name='key' value='<? echo "$key"; ?>'>
<table border='0' width='700' align='center' cellpadding='0' cellspacing='0'>
<tr>
	<td><img src='http://www.telecontrol.com.br/assist/credenciamento/hbtech/cabecalho_hbtech.gif'></td>
</tr>
<tr>
	<td align='center' style='font-size: 30px'>Agradecemos a sua Participação !!</td>
</tr>
<tr>
	<td align='center' colspan='2'>Acesse o link abaixo para obter seu formulário:<br><a href='http://www.telecontrol.com.br/assist/credenciamento/contrato/download_contrato2.php?id=<?echo $posto;?>&key=<?echo $key;?>' target='_BLANK'>Termo de Adesão</a></td>
</tr>
<tr>
	<td colspan='2' align='center'><img src='http://www.telecontrol.com.br/assist/credenciamento/hbtech/rodape_hbtech.gif'></td>
</tr>
</table>

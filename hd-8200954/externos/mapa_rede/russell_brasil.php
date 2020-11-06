<? 
include '/var/www/assist/www/dbconfig.php';
include '/var/www/includes/dbconnect-inc.php';
$fabrica= 81;


if(isset($_GET['estado'])) {
	if(empty($_GET['estado'])) {
		echo "<option value=''>Selecione o estado</option>";
		exit;
	}
	$sql = " SELECT distinct contato_cidade
			FROM tbl_posto_fabrica
			WHERE contato_estado='".$_GET['estado']."'
			AND   fabrica = $fabrica
			AND   contato_cidade IS NOT NULL
			ORDER BY contato_cidade asc ";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
		echo "<option value=''>2&deg; Selecione a cidade</option>";
		for($i =0;$i<pg_num_rows($res);$i++) {
			$cidade = pg_fetch_result($res,$i,contato_cidade);
			echo "<option value='$cidade' >".strtoupper($cidade)."</option>";
		}
	}else{
		echo "<option value=''>Nenhuma cidade encontrada</option>";
	}
	exit;
}

if(isset($_GET['cidade'])) {

	if(empty($_GET['cidade'])) {
		echo "<option value=''>Selecione a cidade</option>";
		exit;
	}
	$sql = " SELECT distinct contato_bairro
			FROM tbl_posto_fabrica
			WHERE contato_cidade='".$_GET['cidade']."'
			AND   contato_bairro IS NOT NULL
			AND   fabrica = $fabrica
			ORDER BY contato_bairro asc ";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
		echo "<option value=''>3&deg; Selecione o bairro</option>";
		for($i =0;$i<pg_num_rows($res);$i++) {
			$contato_bairro = pg_fetch_result($res,$i,contato_bairro);
			echo "<option value='$contato_bairro' >".strtoupper($contato_bairro)."</option>";
		}
	}else{
		echo "<option value=''>".$_GET['cidade']."</option>";
	}
	exit;
}

if(isset($_GET['bairro'])) {
	$sql = " SELECT	nome,
					contato_endereco,
					contato_numero,
					contato_bairro,
					contato_cep,
					fone
			FROM tbl_posto
			JOIN tbl_posto_fabrica USING(posto)
			WHERE contato_cidade='".$_GET['bcidade']."'
			AND contato_estado='".$_GET['bestado']."'
			AND contato_bairro='".$_GET['bairro']."'
			AND   fabrica = $fabrica
			ORDER BY contato_bairro asc ";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
		for($i =0;$i<pg_num_rows($res);$i++) {
			echo "<table width='350' border='1' cellpadding='2'  cellspacing='1'>";
			echo "<tr bgcolor='#000000' class='style5'>";
			echo "<td height='5' colspan='2' bgcolor='#FFFFFF' class='style5'></td>";
			echo "</tr>";
			echo "<tr class='style5'>";
			echo "<td width='22%' align='right' bgcolor='#000000' class='txt_cinza'>Nome:</td>";
			echo "<td width='78%' align='left' bgcolor='#000000' class='texto'>".pg_fetch_result($res,$i,nome)."</td>";
			echo "</tr>";
			echo "<tr class='style5'>";
			echo "<td align='right' bgcolor='#000000' class='txt_cinza'>Endere&ccedil;o:</td>";
			echo "<td align='left' bgcolor='#000000' class='texto'>".pg_fetch_result($res,$i,contato_endereco)." ".pg_fetch_result($res,$i,contato_numero)."</td>";
			echo "</tr>";
			echo "<tr class='style5'>";
			echo "<td align='right' bgcolor='#000000' class='txt_cinza'>Bairro:</td>";
			echo "<td align='left' bgcolor='#000000' class='texto'>".pg_fetch_result($res,$i,contato_bairro)."</td>";
			echo "</tr>";
			echo "<tr class='style5'>";
			echo "<td align='right' bgcolor='#000000' class='txt_cinza'>CEP:</td>";
			echo "<td align='left' bgcolor='#000000' class='texto'>".pg_fetch_result($res,$i,contato_cep)."</td>";
			echo "</tr>";
			echo "<tr class='style5'>";
			echo "<td align='right' bgcolor='#000000' class='txt_cinza'>Telefone:</td>";
			echo "<td align='left' bgcolor='#000000' class='texto'>".pg_fetch_result($res,$i,fone)."</td>";
			echo "</tr>";
			echo "</table>";
		}
	}
	exit;
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>ASSISTÊNCIA TÉCNICA - RUSSELL HOBBS</title>
<link href="css/RHSheet.css" type='text/css' rel='stylesheet'>
<script type="text/javascript" src="js/jquery-1.3.2.js"></script>
<script>

function mostraCidade(estado) {
	$.ajax({
		type: "GET",
		url: "<?=$PHP_SELF?>",
		data: "estado=" + estado,
		cache: false,
		success: function(txt) {
			$('#cidade').html(txt);
		}
	});
}

function mostraBairro(cidade) {
	$.ajax({
		type: "GET",
		url: "<?=$PHP_SELF?>",
		data: "cidade=" + cidade,
		cache: false,
		success: function(txt) {
			$('#bairro').html(txt);
		}
	});
}

function mostraPosto(cidade,bairro,estado) {
	$.ajax({
		type: "GET",
		url: "<?=$PHP_SELF?>",
		data: "bcidade=" + cidade+"&bairro="+bairro+"&bestado="+estado,
		cache: false,
		success: function(txt) {
			$('#posto').html(txt);
		}
	});
}

</script>
</head>
<body>

<DIV id=wrapper>
<DIV id=col1>
<DIV id=branding><IMG alt="Russell Hobbs Logo" src="imagens/RussellHobbLogo.gif"> </DIV>
<DIV id=nav>
<UL>
  <LI><A href="http://www.russellhobbs.com.br/default.aspx">Início</A> </LI>
  <LI><A 
  href="http://www.russellhobbs.com.br/Portuguese/linha-glass-main.aspx">Glass 
  Line</A> </LI>
  <LI><A 
  href="http://www.russellhobbs.com.br/Portuguese/linha-stylo-main.aspx">Stylo 
  Line</A> </LI>
  <LI><A href="http://www.russellhobbs.com.br/Portuguese/onde-comprar.aspx">Onde 
  Comprar</A> </LI>
  <LI><A 
  href="http://www.russellhobbs.com.br/Portuguese/perguntas.aspx">Perguntas</A> 
  </LI>
  <LI><A 
  href="http://www.russellhobbs.com.br/Portuguese/Nossa-Historia.aspx">História</A> 
  </LI>
  <LI><A href="http://www.russellhobbs.com.br/Portuguese/Nossa-Empresa.aspx">A 
  Empresa</A> </LI>
  <LI><A 
  href="http://www.russellhobbs.com.br/Portuguese/Contato.aspx">Contato</A> 
</LI></UL></DIV>
<DIV id=mainContent>
<DIV id=BrandHistory>
<DIV id=brandhdrspace><IMG class=imghdrleft 
src="imagens/rh-icon.jpg"> 
<H1>Assistência Técnica</H1></DIV>
<P><select name="estado" class="form_text" id="estado" style="width:260px" onchange='mostraCidade(this.value)'>
						  <option value="" >1&deg; Selecione o estado</option>
						  
						  <option value="AC"  >AC</option>
						  
						  <option value="AL"  >AL</option>
						  
						  <option value="AM"  >AM</option>
						  
						  <option value="AP"  >AP</option>
						  
						  <option value="BA"  >BA</option>
						  
						  <option value="CE"  >CE</option>
						  
						  <option value="DF"  >DF</option>
						  
						  <option value="ES"  >ES</option>
						  
						  <option value="GO"  >GO</option>
						  
						  <option value="MA"  >MA</option>
						  
						  <option value="MG"  >MG</option>
						  
						  <option value="MS"  >MS</option>
						  
						  <option value="MT"  >MT</option>
						  
						  <option value="PA"  >PA</option>
						  
						  <option value="PB"  >PB</option>
						  
						  <option value="PE"  >PE</option>
						  <option value="PI"  >PI</option>
						  <option value="PR"  >PR</option>
						  <option value="RJ"  >RJ</option>
						  <option value="RN"  >RN</option>
						  <option value="RO"  >RO</option>
						  <option value="RR"  >RR</option>
						  <option value="RS"  >RS</option>
						  <option value="SC"  >SC</option>
						  <option value="SE"  >SE</option>
						  <option value="SP" >SP</option>
						  <option value="TO"  >TO</option>
					</select>
<br/>
<br/>
<select name="cidade" class="form_text" id="cidade" style="width:260px"  onchange='mostraBairro(this.value)'>
	<option value="" >2&deg; Selecione a cidade</option>
</select>
<br/>
<br/>
<select name="bairro" class="form_text" id="bairro"  style="width:260px" onchange='mostraPosto(document.getElementById("cidade").value,this.value,document.getElementById("estado").value)'>
	<option value="" >3&deg; Selecione o bairro</option>
</select>
<br/>
<br/>
<p id='posto'></p>
</p></div><br clear=all></div></div>
<div id=col2>
<div id=rightcol>
<DIV class=calloutboxNews><IMG class=imgNewsright alt="Russel Hobbs News Pic" 
src="imagens/calloout-news.jpg"> 
<H1>Hall of<BR>Fame<BR></H1>
<P>Alta tecnologia com a combinação perfeita entre performance e estilo na hora 
de preparar um café expresso.<BR><BR>
<SCRIPT type=text/javascript>

          function launchPlayere62141x5() {

              pwin = window.open(

        "http://vidego.multicastmedia.com/player.php?p=e62141x5",

        "newwindow", "height=295,width=352",

        "toolbar=no,menubar=no,resizable=no,scrollbars=no,status=no,location=no");

          }

</SCRIPT>
<A href="javascript:launchPlayere62141x5()">Assista ao Vídeo...</A></P></DIV>
<DIV class=calloutboxGlass>
<P>Madeira, aço inoxidável e vidro compõem um design elegante e 
exclusivo<BR><BR><A 
href="http://www.russellhobbs.com.br/Portuguese/linha-glass-main.aspx">Veja 
nossos outros produtos...</A></P></DIV><BR clear=all></DIV></DIV><BR clear=all>
<div id=footer>
<UL>
  <LI><A href="http://www.russellhobbs.com.br/Portuguese/Contacto.aspx">Entre em 
  Contato</a> </li></ul></div></div>
</body></html>

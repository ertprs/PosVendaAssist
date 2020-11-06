<?

include "/www/assist/www/dbconfig.php";
include "/www/assist/www/includes/dbconnect-inc.php";
//include '../autentica_usuario.php';


$credenciar = $_GET['credenciar'];
if(strlen($_GET['posto']) > 0)          $posto      = $_GET['posto'];
if(strlen($_POST['posto']) > 0)         $posto      = $_POST['posto'];
if(strlen($_POST["motivo_$posto"]) > 0) $motivo     = $_POST["motivo_$posto"];
if(strlen($_POST["estado"]) > 0)        $estado     = $_POST["estado"];
if(strlen($_POST['recusar']) > 0)       $credenciar = $_POST['recusar'];

if(strlen($credenciar) > 0){

	$sql = "SELECT cnpj,nome FROM tbl_posto WHERE tbl_posto.posto = $posto; ";
	$res = pg_exec($con, $sql);
	if(pg_numrows($res) > 0){
		$cnpj = pg_result($res,0,cnpj);
		$nome = pg_result($res,0,nome);
	}else{
		$msg_erro = "Posto já credenciado/descredenciado.";
	}

	if($credenciar == 'Recusar'){
		if(strlen($motivo) == 0) $msg_erro = "Digite o motivo da recusa.";
		if(strlen($msg_erro) == 0){
			$sql = "INSERT INTO tbl_posto_fabrica ( posto           ,
											fabrica         ,
											codigo_posto    ,
											senha           ,
											tipo_posto      ,
											login_provisorio,
											credenciamento  ,
											data_alteracao
									) VALUES (
											$posto         ,
											25             ,
											'$cnpj'        ,
											'*'            ,
											'119'          ,
											't'            ,
											'DESCREDENCIADO',
											current_timestamp
									); ";
			$res = pg_exec($con, $sql);

			$email_origem  = "telecontrol@telecontrol.com.br";
			$email_destino = "tecnico@telecontrol.com.br";
			$assunto       = "DESCREDENCIAMENTO DE POSTOS PELA HBTECH";
			$corpo .= "POSTO DESCREDENCIADO PELA HBTECH<br>";
			$corpo .= "<br>CNPJ: $cnpj<br>";
			$corpo .= "<br>Nome: $nome<br>";
			$corpo .= "<br>Motivo: $motivo<br>";
			$corpo .= "<br>_____________________________________________\n";
			$corpo .= "<br><br>Telecontrol\n";
			$corpo .= "<br>www.telecontrol.com.br\n";
			$body_top = "--Message-Boundary\n";
			$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
			$body_top .= "Content-transfer-encoding: 7BIT\n";
			$body_top .= "Content-description: Mail message body\n\n";
			@mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ); 
		}
	}else{

		if(strlen($msg_erro) == 0){
			$sql = "INSERT INTO tbl_posto_fabrica ( posto           ,
													fabrica         ,
													codigo_posto    ,
													senha           ,
													tipo_posto      ,
													login_provisorio,
													data_alteracao
											) VALUES (
													$posto         ,
													25             ,
													'$cnpj'        ,
													'*'            ,
													'119'          ,
													't'            ,
													current_timestamp
									); ";
		$res = pg_exec($con, $sql);
		}
	}
}

?>


<TITLE> POSTOS EM CREDENCIAMENTO </TITLE>

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
function MostraEsconde(dados)
{
	if (document.getElementById)
	{
		var style2 = document.getElementById(dados);
		if (style2==false) return;
		if (style2.style.display=="block"){
			style2.style.display = "none";
			}
		else{
			style2.style.display = "block";
		}
	}
}

function informacoes(posto) {
    var url = "";
        url = "informacoes.php?posto=" + posto;
        janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=650,height=600,top=18,left=0");
        janela.focus();
}

function janela(a , b , c , d) {
	var arquivo = a;
	var janela = b;
	var largura = c;
	var altura = d;
	posx = (screen.width/2)-(largura/2);
	posy = (screen.height/2)-(altura/2);
	features="width=" + largura + " height=" + altura + " status=yes scrollbars=yes";
	newin = window.open(arquivo,janela,features);  
	newin.focus();
}
</script>


<TABLE align='center' width='500' style='fon-size: 14px' border='0' class='tabela2' bgcolor='' cellspacing='0' cellpadding='0'>
<TR>
	<TD style='font-size: 25px; font-family: verdana' align='center'>POSTOS EM CREDENCIAMENTO</TD>
</TR>
<TR>
	<TD align='center'>Telecontrol Networking</TD>
</TR>
</TABLE>


<p><? if(strlen($msg_erro) > 0) echo "$msg_erro"; ?></p>
<?$msg_erro = '';?>
<br>

<?
if(1==2){ ?>
<table width='700' border = '0' cellspacing='0' cellpadding='0' class='tabela2' style='font-size: 10px; font-family: verdana;' align='center'>
<tr>
	<td align='center' style='font-size: 12px; color: #FFFFFF' bgcolor='#006077' height='20'><b>Pesquisa</b></td>
</tr>
<tr>
	<td style='font-size: 14px' height='5'></td>
</tr>
<tr>
	<td align='center' style='font-size: 12px'>
		<a href="index.php?estado=AC">AC</a>&nbsp;&nbsp;
		<a href="index.php?estado=AL">AL</a>&nbsp;&nbsp;
		<a href="index.php?estado=AP">AP</a>&nbsp;&nbsp;
		<a href="index.php?estado=AM">AM</a>&nbsp;&nbsp;
		<a href="index.php?estado=BA">BA</a>&nbsp;&nbsp;
		<a href="index.php?estado=CE">CE</a>&nbsp;&nbsp;
		<a href="index.php?estado=DF">DF</a>&nbsp;&nbsp;
		<a href="index.php?estado=ES">ES</a>&nbsp;&nbsp;
		<a href="index.php?estado=GO">GO</a>&nbsp;&nbsp;
		<a href="index.php?estado=MA">MA</a>&nbsp;&nbsp;
		<a href="index.php?estado=SP">MG</a>&nbsp;&nbsp;
		<a href="index.php?estado=MT">MT</a>&nbsp;&nbsp;
		<a href="index.php?estado=MS">MS</a>&nbsp;&nbsp;
		<a href="index.php?estado=PA">PA</a>&nbsp;&nbsp;
		<a href="index.php?estado=PB">PB</a>&nbsp;&nbsp;
		<a href="index.php?estado=PR">PR</a>&nbsp;&nbsp;
		<a href="index.php?estado=PE">PE</a>&nbsp;&nbsp;
		<a href="index.php?estado=PI">PI</a>&nbsp;&nbsp;
		<a href="index.php?estado=RJ">RJ</a>&nbsp;&nbsp;
		<a href="index.php?estado=RN">RN</a>&nbsp;&nbsp;
		<a href="index.php?estado=RS">RS</a>&nbsp;&nbsp;
		<a href="index.php?estado=RO">RO</a>&nbsp;&nbsp;
		<a href="index.php?estado=RR">RR</a>&nbsp;&nbsp;
		<a href="index.php?estado=SP">SC</a>&nbsp;&nbsp;
		<a href="index.php?estado=SE">SE</a>&nbsp;&nbsp;
		<a href="index.php?estado=SP">SP</a>&nbsp;&nbsp;
		<a href="index.php?estado=TO">TO</a><br><br>
		<a href="index.php?estado=todos">TODOS</a>
	</td>
</tr>
<tr>
	<td style='font-size: 12px' height='2'></td>
</tr>
</TABLE>
<?}?>

<?
// pega o endereço do diretório
$diretorio = getcwd(); 

// abre o diretório
$ponteiro  = opendir($diretorio);

// monta os vetores com os itens encontrados na pasta
while ($nome_itens = readdir($ponteiro)) {
	if($nome_itens <> "index.php" AND $nome_itens <> "posto_cadastro_hbtec.php" AND $nome_itens <> "index2.php")
		$itens[] = $nome_itens;
}

// ordena o vetor de itens
sort($itens);

// percorre o vetor para fazer a separacao entre arquivos e pastas 
foreach ($itens as $listar) {

// retira "./" e "../" para que retorne apenas pastas e arquivos
	if ($listar!="." && $listar!=".."){ 

// checa se o tipo de arquivo encontrado é uma pasta
		if (is_dir($listar)) { 

// caso VERDADEIRO adiciona o item à variável de pastas
			$pastas[]=$listar; 
		} else{ 

// caso FALSO adiciona o item à variável de arquivos
			$arquivos[]=$listar;
		}
	}
}

if(strlen($_GET['estado']) > 0)  $estado = strtoupper(trim($_GET['estado']));

// lista os arquivos se houverem
if ($arquivos != "" AND strlen($estado) == 0) {
	$xarquivos = $arquivos;
	
	$sql = "SELECT posto, descricao, fabricantes, cidade, estado
				FROM temp_automatic 
				ORDER BY cidade ";
	$res = pg_exec($con, $sql);

	if(pg_numrows($res) > 0){
	$j = 1;
		for($i = 0; $i < pg_numrows($res); $i++){
//			$nome           = pg_result($res,$i,nome);
			$cidade         = @pg_result($res,$i,cidade);
			$estado         = @pg_result($res,$i,estado);
			$posto          = pg_result($res,$i,posto);
			$fabricantes    = pg_result($res,$i,fabricantes);
			$descricao      = pg_result($res,$i,descricao);
//			$codigo_posto   = @pg_result($res,$i,codigo_posto);
//			$credenciamento = @pg_result($res,$i,credenciamento);

//			$email   = pg_result($res,$i,email);
			
			echo "<br><table  border = '0' cellspacing='0' cellpadding='0' class='tabela' style='font-size: 10px; font-family: verdana;' align='center' width='600'>";
			echo "<tr>";
				echo "<td style='font-size: 12px' nowrap><b onClick=\"MostraEsconde('conteudo$i')\" style='cursor:pointer; cursor:hand;'>POSTO $j</b></td>"; 
				echo "<td align='left' width='100%'>&nbsp;&nbsp;";
				$foto = '0';
				foreach($xarquivos as $xlistar){
					//validações para não imprimir fotos de outros postos com nome final =
					$testa_1 = "$posto" . "_1.jpg";
					$testa_2 = "$posto" . "_2.jpg";
					$testa_3 = "$posto" . "_3.jpg";

					if(substr_count($xlistar, $posto) > 0 AND $foto == '0' AND ($testa_1 == $xlistar OR $testa_2 == $xlistar OR $testa_3 == $xlistar)) {
						echo "<a href= \"javascript: informacoes($posto)\"><img border='0' src='camera_foto.gif' ALT='Tem fotos'></a>&nbsp;&nbsp;";
						$z++;
						$foto = '1';
					}
				}
				echo "<a href= \"javascript: informacoes($posto)\"><img border='0' src='papel.jpg' ALT='Mais informações' width='16' height='16'></a></td>";
			echo "</td>";
			echo "<tr>";
				if(strlen($cidade) > 0){
					echo "<td align='right' colspan='2'>". strtoupper($cidade) . " - " . strtoupper($estado) ."</td>";
				}else{ 
					echo "<td align='right' colspan='2'>Cidade - Estado</td>";
				}
			echo "</tr>";
			echo "<tr>";
			echo "<td colspan='2'>";
			echo "<div id='conteudo$i' style='display: block;'>";
				echo "<table border='0' align='left' cellspacing='0' cellpadding='0' style='font-size: 10px'>";
				echo "<tr>";
					echo "<td align='left'><b>Fabricantes</b>: $fabricantes</td>";
				echo "</tr>";
				echo "<tr>";
					echo "<td><b>Descrição:</b> $descricao</td>";
				echo "</tr>";
				echo "</table>";
			echo "</div>";
			echo "</td>";
			echo "</tr>";
			echo "<tr>";
/*			if(strlen($codigo_posto) == 0){
				echo "<td colspan='2' align='center'><a href='". $PHP_SELF ."?estado=$estado&posto=$posto&credenciar=credenciar'>Credenciar</a>  ||  <b onClick=\"MostraEsconde('desc$i')\" style='cursor:pointer; cursor:hand;'>Descredenciar</b></td>";
			}else{
				if($credenciamento == "CREDENCIADO")
					echo "<td colspan='2' align='center' style='color:#FF0000'>Credenciado</td>";
				else
					echo "<td colspan='2' align='center' style='color:#FF0000'>Descredenciado</td>";
			}
*/			echo "</tr>";
			echo "<tr>";
			echo "<td colspan='2'>";
				echo "<div id='desc$i' style='display: none;'>";
				echo "<FORM METHOD=POST ACTION='".$PHP_SELF ."' NAME='recusa_$posto'>";
				echo "<INPUT TYPE='hidden' NAME='posto' value='$posto'>";
				echo "<INPUT TYPE='hidden' NAME='estado' value='$estado'>";
				echo "<table border='0' align='left' cellspacing='0' cellpadding='0' style='font-size: 10px'>";
				echo "<tr>";
					echo "<td height='50'>Motivo da recusa <INPUT TYPE=\"text\" NAME=\"motivo_$posto\" size='80'><INPUT TYPE=\"submit\" value='Recusar' name='recusar'></td>";
				echo "</tr>";
				echo "</table>";
				echo "</FORM>";
				echo "</div>";
			echo "</td>";
			echo "</tr>";
			echo "</table>";
		$j++;
		}
	}else{
		echo "<br><br>";
		echo "<p style='font-size: 12px; font-family: verdana;' align='center'>Nenhum resultado encontrado para o estado de $estado.</p>";
	}
	echo "Total: " . pg_numrows($res);
	$z = 1;
	$xposto = $posto;

}
?>
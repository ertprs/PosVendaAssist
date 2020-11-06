<?

include "../dbconfig.php";
include "../includes/dbconnect-inc.php";
//include '../autentica_usuario.php';
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

<br><br>

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

$estado = strtoupper(trim($_GET['estado']));

// lista os arquivos se houverem
if ($arquivos != "" AND strlen($estado) > 0) {
	$xarquivos = $arquivos;
	
	$sql = "SELECT nome, cidade, estado, posto , fabricantes, descricao, email
					FROM tbl_posto_extra 
					JOIN tbl_posto using(posto) 
				WHERE (tbl_posto_extra.fabricantes IS NOT NULL OR tbl_posto_extra.descricao IS NOT NULL) ";
	if(strlen($estado) > 0 AND $estado <> 'TODOS'){
		$sql .=" AND UPPER(tbl_posto.estado) = '$estado' ";
	}

	$sql .= " ORDER BY tbl_posto.nome; ";

	$res = pg_exec($con, $sql);
	if(pg_numrows($res) > 0){
		for($i = 0; $i < pg_numrows($res); $i++){
			$nome        = pg_result($res,$i,nome);
			$cidade      = pg_result($res,$i,cidade);
			$estado      = pg_result($res,$i,estado);
			$posto       = pg_result($res,$i,posto);
			$fabricantes = pg_result($res,$i,fabricantes);
			$descricao   = pg_result($res,$i,descricao);

			$email   = pg_result($res,$i,email);
			
			echo "<br><table  border = '0' cellspacing='0' cellpadding='0' class='tabela' style='font-size: 10px; font-family: verdana;' align='center' width='600'>";
			echo "<tr>";
				echo "<td style='font-size: 12px' nowrap><b onClick=\"MostraEsconde('conteudo$i')\" style='cursor:pointer; cursor:hand;'>". strtoupper($nome) ."</b></td>";
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
				echo "<td align='right' colspan='2'>". strtoupper($cidade) . " - " . strtoupper($estado) ."</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td colspan='2'>";
			echo "<div id='conteudo$i' style='display: none;'>";
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
			echo "</table>";
/*			
			$z = 1;
			$foto = '0';
			foreach($xarquivos as $xlistar){
				//validações para não imprimir fotos de outros postos com nome final =
				$testa_1 = "$posto" . "_1.jpg";
				$testa_2 = "$posto" . "_2.jpg";
				$testa_3 = "$posto" . "_3.jpg";

				if(substr_count($xlistar, $posto) > 0 AND $foto == '0' AND ($testa_1 == $xlistar OR $testa_2 == $xlistar OR $testa_3 == $xlistar)) {
					echo "<a href=\"javascript:janela('$xlistar','Fotos',600,500);\">...Mais...</a>&nbsp;&nbsp;&nbsp;&nbsp;";
					$z++;
					$foto = '1';
				}
			}
			echo "</td>";
			echo "</tr>";
			echo "</table>";
*/
		}
	}else{
		echo "<br><br>";
		echo "<p style='font-size: 12px; font-family: verdana;' align='center'>Nenhum resultado encontrado para o estado de $estado.</p>";
	}
	echo "Total: " . pg_numrows($res);
	$z = 1;
	$xposto = $posto;

}





//VERSAO ANTERIOR -->


/*

<script>  
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


// lista os arquivos se houverem
if ($arquivos != "") {
	$xarquivos = $arquivos;

	foreach($arquivos as $listar){
	
		$posicao = strpos($listar, '_');
		$posto = substr($listar, 0, $posicao);


		if( substr($listar, 0, $posicao) <> $xposto ){
			if(strlen($posto) > 0){
				$sql = "SELECT nome, cidade, estado, posto FROM tbl_posto WHERE posto = '$posto'";
				$res = pg_exec($con, $sql);
				if (pg_numrows($res) > 0){
					$nome     = pg_result($res,0,nome);
					$cidade   = pg_result($res,0,cidade);
					$estado   = pg_result($res,0,estado);
					$post     = pg_result($res,0,posto);
					
					echo "<br><table bgcolor='#FEFADE' border = '0' cellspacing='0' cellpadding='0' style='font-size: 10px; font-family: verdana;' align='center' width='600'>";
					echo "<tr>";
						echo "<td style='font-size: 16px'><b>$nome</b></td>";
					echo "</tr>";
					echo "<tr>";
						echo "<td align='right'>$cidade - $estado</td>";
					echo "</tr>";
					echo "<tr>";
					echo "<td align='center'>";
					$z = 1;
					foreach($xarquivos as $xlistar){
						//validações para não imprimir fotos de outros postos com nome final =
						$testa_1 = "$posto" . "_1.jpg";
						$testa_2 = "$posto" . "_2.jpg";
						$testa_3 = "$posto" . "_3.jpg";

						if(substr_count($xlistar, $posto) > 0 AND ($testa_1 == $xlistar OR $testa_2 == $xlistar OR $testa_3 == $xlistar)) {
							echo "<a href=\"javascript:janela('$xlistar','Fotos',600,500);\">foto $z</a>&nbsp;&nbsp;&nbsp;&nbsp;";
						$z++;
						}
					}
				}
			}
			$z = 1;
			$xposto = $posto;
			echo "</td>";
			echo "</tr>";
			echo "</table>";
		}
	}
}


*/


?>

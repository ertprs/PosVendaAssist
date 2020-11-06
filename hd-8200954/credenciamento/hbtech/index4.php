<?

include "../dbconfig.php";
include "../includes/dbconnect-inc.php";
include '../autentica_usuario.php';
?>


<TITLE> POSTOS EM CREDENCIAMENTO </TITLE>

<style type="text/css">
div.banner {
  margin: 0;
  font-size: 80% /*smaller*/;
  font-weight: bold;
  line-height: 1.1;
  text-align: center;
  position: absolute;
  top: 2em;
  left: auto;
  width: 8.5em;
  right: 2em;
}

div.banner p {
  margin: 0; 
  padding: 0.3em 0.4em;
  font-family: Arial, sans-serif;
  background: transparent;
  border: none;
  color: #33CCFF;

}

div.banner <a, div.banner em { display: block; margin: 0 0.5em }
div.banner <a, div.banner em { border-top: none groove #FFFFFF }
div.banner <a:first-child { border-top: none }
div.banner em { color: #FFCC33 }

div.banner a:link { text-decoration: none; color: #9D9D9D }
div.banner a:visited { text-decoration: none; color: #CCC }
div.banner a:hover { background: #C8C8C8; color: white }
body>div.banner {position: fixed}

</style>

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


window.onscroll = function(){
	var p = document.getElementById("janela") || document.all["janela"];
	var y1 = y2 = y3 = 0, x1 = x2 = x3 = 0;

	if (document.documentElement) y1 = document.documentElement.scrollTop || 0;
	if (document.body) y2 = document.body.scrollTop || 0;
	y3 = window.scrollY || 0;
	var y = Math.max(y1, Math.max(y2, y3));

	if (document.documentElement) x1 = document.documentElement.scrollLeft || 0;
	if (document.body) x2 = document.body.scrollLeft || 0;
	x3 = window.scrollX || 0;
	var x = Math.max(x1, Math.max(x2, x3));

	p.style.top = (parseInt(p.initTop) + y) + "px";
	p.style.left = (parseInt(p.initLeft) + x) + "px";
	p.style.marginLeft = (0) + "px";
	p.style.marginTop = (0) + "px";
}

window.onload = function(){
	var p = document.getElementById("janela") || document.all["janela"];
	p.initTop = p.offsetTop; p.initLeft = p.offsetLeft;
	window.onscroll();
}


</script>

<TABLE align='center' style='fon-size: 14px' border='0' bgcolor='' cellspacing='0' cellpadding='0'>
<TR>
	<TD style='font-size: 25px; font-family: verdana'>POSTOS EM CREDENCIAMENTO</TD>
</TR>
<TR>
	<TD align='center'>Telecontrol Networking</TD>
</TR>
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
$navegador = $_SERVER['HTTP_USER_AGENT'];
$mozilla = "Firefox";
$pos = strpos($navegador, $mozilla);
?>

<div class="banner" <? if($pos == false) echo "id='janela'" ?>>
<p>
<em> ...<b>POSTOS</b>... </em>
<a href="index.php?foto=foto">Com foto</a><br>
<a href="index.php?foto=sem">Sem Foto</a><br>
<em> ...<b>ESTADO</b>... </em>
<a href="index.php?estado=AC">AC</a>
<a href="index.php?estado=AL">AL</a>
<a href="index.php?estado=AP">AP</a><br>
<a href="index.php?estado=AM">AM</a>
<a href="index.php?estado=BA">BA</a>
<a href="index.php?estado=CE">CE</a>
<a href="index.php?estado=DF">DF</a><br>
<a href="index.php?estado=ES">ES</a>
<a href="index.php?estado=GO">GO</a>
<a href="index.php?estado=MA">MA</a>
<a href="index.php?estado=MT">MT</a><br>
<a href="index.php?estado=MS">MS</a>
<a href="index.php?estado=PA">PA</a>
<a href="index.php?estado=PB">PB</a>
<a href="index.php?estado=PR">PR</a><br>
<a href="index.php?estado=PE">PE</a>
<a href="index.php?estado=PI">PI</a>
<a href="index.php?estado=RJ">RJ</a>
<a href="index.php?estado=RN">RN</a><br>
<a href="index.php?estado=RS">RS</a>
<a href="index.php?estado=RO">RO</a>
<a href="index.php?estado=RR">RR</a>
<a href="index.php?estado=SE">SE</a>
<a href="index.php?estado=SP">SP</a>
<a href="index.php?estado=TO">TO</a><br>
<em>...<b>TUDO</b>...</em><br>
<a href="index.php">Todos</a>
</p>
</div>



<?
// lista os arquivos se houverem
if ($arquivos != "") {
	$xarquivos = $arquivos;
	
	$estado = trim($_GET['estado']);

	$sql = "SELECT nome, cidade, estado, posto , fabricantes, descricao, email
					FROM tbl_posto_extra 
					JOIN tbl_posto using(posto) 
				WHERE (tbl_posto_extra.fabricantes IS NOT NULL OR tbl_posto_extra.descricao IS NOT NULL) ";
	if(strlen($estado) > 0){
		$sql .=" AND tbl_posto.estado = '$estado' ";
	}

	$sql .= " ORDER BY tbl_posto.estado; ";

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
			
			echo "<br><table bgcolor='#FEFADE' border = '0' cellspacing='0' cellpadding='0' style='font-size: 12px; font-family: verdana;' align='center' width='600'>";
			echo "<tr>";
				echo "<td style='font-size: 16px'><b>$nome<br>$email</b></td>";
			echo "</tr>";
			echo "<tr>";
				echo "<td align='right'>$cidade - $estado</td>";
			echo "</tr>";
			echo "<tr>";
				echo "<td><b><u><FONT SIZE='-1' color='#959595'>Outros Fabricantes:</FONT></u></b> $fabricantes</td>";
			echo "</tr>";
			echo "<tr>";
				echo "<td><b><u><FONT SIZE='-1' color='#959595'>Descrição:</FONT></u></b> $descricao</td>";
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
			echo "</td>";
			echo "</tr>";
			echo "</table>";
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

<?

include "../dbconfig.php";
include "../includes/dbconnect-inc.php";

if(1==2){
?>


<TITLE> POSTOS EM CREDENCIAMENTO </TITLE>

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

// pega o endere? do diret?io
$diretorio = getcwd(); 

// abre o diret?io
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

// checa se o tipo de arquivo encontrado ?uma pasta
		if (is_dir($listar)) { 

// caso VERDADEIRO adiciona o item ?vari?el de pastas
			$pastas[]=$listar; 
		} else{ 

// caso FALSO adiciona o item ?vari?el de arquivos
			$arquivos[]=$listar;
		}
	}
}


// lista os arquivos se houverem
if ($arquivos != "") {
	$xarquivos = $arquivos;

	$sql = "SELECT distinct tbl_posto.nome, cidade, tbl_posto.estado, tbl_posto.posto , fabricantes, descricao, email, contato, fone, cnpj, numero, endereco, bairro
					FROM tbl_posto_extra 
					JOIN tbl_posto using(posto) 
					JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
				WHERE (tbl_posto_extra.fabricantes IS NOT NULL OR tbl_posto_extra.descricao IS NOT NULL)
				ORDER BY tbl_posto.estado;";
	$res = pg_exec($con, $sql);
	echo "<br><table border = '0' cellspacing='0' cellpadding='0' style='font-size: 10px; font-family: verdana;' align='center' width='1000'>";
	for($i = 0; $i < pg_numrows($res); $i++){
		$cnpj        = pg_result($res,$i,cnpj);
		$nome        = pg_result($res,$i,nome);
		$endereco    = pg_result($res,$i,endereco);
		$numero      = pg_result($res,$i,numero);
		$bairro      = pg_result($res,$i,bairro);
		$cidade      = pg_result($res,$i,cidade);
		$estado      = pg_result($res,$i,estado);
		$email       = pg_result($res,$i,email);
		$posto       = pg_result($res,$i,posto);
		$fabricantes = pg_result($res,$i,fabricantes);
		$telefone    = pg_result($res,$i,fone);
		$contato     = pg_result($res,$i,contato);
		
		echo "<tr>";
			echo "<td nowrap>$cnpj</td>";
			echo "<td nowrap><b>$nome</b></td>";
			echo "<td nowrap>$email</td>";
			echo "<td nowrap>$endereco, $numero, $bairro</td>";
			echo "<td nowrap>$cidade</td>";
			echo "<td nowrap>$estado</td>";
			echo "<td nowrap>$contato</td>";
			echo "<td nowrap>$telefone</td>";
		echo "</tr>";
/*		echo "<tr>";
			echo "<td><b><u><FONT SIZE='-1' color='#959595'>Outros Fabricantes:</FONT></u></b> $fabricantes</td>";
		echo "</tr>";
		echo "<tr>";
			echo "<td><b><u><FONT SIZE='-1' color='#959595'>Descri?o:</FONT></u></b> $descricao</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td align='center'>";
		$z = 1;
		foreach($xarquivos as $xlistar){
			//valida?es para n? imprimir fotos de outros postos com nome final =
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
*/		
	}
	echo "</table>";
	echo pg_numrows($res);
	$z = 1;
	$xposto = $posto;

}
}


if(1==1){


?>
	<TABLE>
<?
	$sql = "SELECT distinct cnpj, nome, email, fone, contato, estado, bairro, cidade, endereco
				FROM tbl_posto 
				JOIN tbl_posto_extra using(posto) 
				WHERE (tbl_posto_extra.fabricantes IS NOT NULL OR tbl_posto_extra.descricao IS NOT NULL) ;";
	$res = pg_exec($con,$sql);
	
	for($i=0; $i < pg_numrows($res); $i++){
		$estado  = pg_result($res, $i,estado);
		$cidade  = pg_result($res, $i,cidade);
		$endereco= pg_result($res, $i,endereco);
		$bairro  = pg_result($res, $i,bairro);
		$cnpj    = pg_result($res, $i,cnpj);
		$nome    = pg_result($res, $i,nome);
		$email   = pg_result($res, $i,email);
		$fone    = pg_result($res, $i,fone);
		$contato = pg_result($res, $i,contato);
?>
		<TR>
			<TD nowrap><?echo trim($cnpj)?></TD>
			<TD nowrap><?echo trim($nome)?></TD>
			<TD nowrap><?echo trim($cidade)?></TD>
			<TD nowrap><?echo trim($estado)?></TD>
			<TD nowrap><?echo trim($endereco)?></TD>
			<TD nowrap><?echo trim($bairro)?></TD>
			<TD nowrap><?echo trim($fone)?></TD>
			<TD nowrap><?echo trim($email)?></TD>
			<TD nowrap><?echo trim($contato)?></TD>
		</TR>
	<?}?>
	</TABLE>

<?

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

// pega o endere? do diret?io
$diretorio = getcwd(); 

// abre o diret?io
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

// checa se o tipo de arquivo encontrado ?uma pasta
		if (is_dir($listar)) { 

// caso VERDADEIRO adiciona o item ?vari?el de pastas
			$pastas[]=$listar; 
		} else{ 

// caso FALSO adiciona o item ?vari?el de arquivos
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
						//valida?es para n? imprimir fotos de outros postos com nome final =
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

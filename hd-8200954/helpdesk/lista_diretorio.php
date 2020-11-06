<?
include 'insere_diretorio.php';
print_r($con);
class varrer{
	

	function varre($dir,$filtro="",$nivel="") {

	   //echo "$filename foi modificado em: " . date ("m d Y H:i:s.", filectime($filename));
	//echo "<br>hora: " . date ("H:i", filectime($filename));

		$diraberto = opendir($dir); // Abre o diretorio especificado 
		chdir($dir); // Muda o diretorio atual p/ o especificado 

		while($arq = readdir($diraberto)) { // Le o conteudo do arquivo 
			if($arq == ".." || $arq == ".")continue; // Desconsidera os diretorios 
				$arr_ext = explode(";",$filtro); 
			foreach($arr_ext as $ext) { 
				$extpos = (strtolower(substr($arq,strlen($arq)-strlen($ext)))) == strtolower($ext); 
				$exten = substr($arq,-3); 
				if ($extpos == strlen($arq) and is_file($arq)){                 
					$caminho= getcwd() . "\\$arq";
					$caminho= str_replace('\\', "/",$caminho);

					$inserir = new inserir();
					$retorno = $inserir->insere($caminho);

					echo $retorno; echo '<br>'; // $nivel.$arq; Imprime em forma de arvore 
				}
			} 
			if (is_dir($arq)) { 
				echo ("<br><font color='#ff0000'>Nivel:</font>-".$nivel." \t <font color='#0000ff'>Arquivo:</font>".$arq); // Imprime em forma de arvore 
				$this ->varre($arq,$filtro,($nivel)."&nbsp;&nbsp;&nbsp;&nbsp;"); // Executa a funcao novamente se subdiretorio 
			} 
		} 
		chdir(".."); // Volta um diretorio 
		closedir($diraberto); // Fecha o diretorio atual 
	}
}

$principal = new varrer();

//$sql = "insert into tbl_arquivo(descricao, status) values('/var/www/assist/www/helpdesk/menu.php', 'ativo');";
//$res = pg_exec ($con,$sql);
$principal->varre(".", ".php" );


?>




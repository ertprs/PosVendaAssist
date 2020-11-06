<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';



	function insere($caminho, $con) {

		$sql= "select *
			from tbl_arquivo 
			WHERE descricao= '$caminho'";

		$res = pg_exec ($con,$sql);

		if(pg_numrows($res)>0){
			$arquivo = pg_result($res,0,arquivo);
			
			$erro="ja existe o arquivo > ";
			$data_arquivo = date ("Y-m-d", filemtime($caminho));
			$hora_arquivo = date ("H:i"  , filemtime($caminho));
/*			$sql = "UPDATE tbl_arquivo SET data = '$data_arquivo',hora='$hora_arquivo' WHERE arquivo = $arquivo";
			$res = pg_exec ($con,$sql);
*/
//			echo "$erro $caminho";
		}else{
//			echo "-".$caminho."<br>\n";
			$data_arquivo = date ("Y-m-d", filemtime($caminho));
			$hora_arquivo = date ("H:i"  , filemtime($caminho));
			$sql= "insert into tbl_arquivo(descricao, status ,ultimo_admin,data,hora)
				values('$caminho', 'ativo',435,'$data_arquivo','$hora_arquivo');";

			$res = pg_exec ($con,$sql);
//			echo "<font color='00FF00'><b>INSERIU</b> >$caminho</font>";
			//echo $sql;
		}

	}


	function varre( $con,$dir,$filtro="",$nivel="") {

		$diraberto = opendir($dir); // Abre o diretorio especificado 
		chdir($dir); // Muda o diretorio atual p/ o especificado 

		while($arq = readdir($diraberto)) { // Le o conteudo do arquivo 
			if($arq == ".." || $arq == "." || is_link($arq) == TRUE)continue; // Desconsidera os diretorios 
				$arr_ext = explode(";",$filtro); 
			foreach($arr_ext as $ext) { 
				$extpos = (strtolower(substr($arq,strlen($arq)-strlen($ext)))) == strtolower($ext); 
				$exten = substr($arq,-3); 
				if ($extpos == strlen($arq) and is_file($arq)){                 
					$caminho= getcwd() . "\\$arq";
					$caminho= str_replace('\\', "/",$caminho);

					if(substr($caminho,0,22)<>'/var/www/jpgraph-1.16/')
						insere($caminho, $con);
					
//					echo $retorno; echo '<br>'; // $nivel.$arq; Imprime em forma de arvore 
				}
			} 
			if (is_dir($arq)) { 
//				echo ("<br><font color='#ff0000'>Nivel:</font>-".$nivel." \t <font color='#0000ff'>Arquivo:</font>".$arq); // Imprime em forma de arvore 
				varre( $con, $arq,$filtro,($nivel)."&nbsp;&nbsp;&nbsp;&nbsp;"); // Executa a funcao novamente se subdiretorio 
			} 
		} 
		chdir(".."); // Volta um diretorio 
		closedir($diraberto); // Fecha o diretorio atual 
	}

		$sql= "select *
			from tbl_arquivo 
			";

		$res = pg_exec ($con,$sql);
		$pg=@pg_numrows($res);
//		echo "pg_numrows:". $pg;


varre( $con, ".", ".php");
//$sql = "insert into tbl_arquivo(descricao, status) values('/var/www/assist/www/helpdesk/index_v1.php', 'ativo');";
//$res = pg_exec ($con,$sql);


?>




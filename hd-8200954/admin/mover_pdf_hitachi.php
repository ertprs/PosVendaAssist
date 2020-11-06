<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';


	$login_fabrica = 125;
	$dir = "/home/paulo/pos/*";
	
	$i = 0;
	$nao = 0;
	$inseridos = 0;
	echo glob($dir);
	foreach(glob($dir) as $file) {

		//if($i == 100) break;		//limite para testes
	
		$i++;
		echo $i;
		echo $file;
		echo $fim = explode( '/', $file );
		print_r($fim);
		$extensao = explode('.',$fim[4]);
		$extensao = $extensao[1];
		$arquivo = str_ireplace(".".$extensao,'',$fim[4]);
		$ref = explode('-',$arquivo);
		$ref = $ref[0];
		echo $arquivo . "<br />\n";
	
		if ($arquivo == '' ){ continue;}
		//verifica se o produto com a referencia do PDF existe
		$sql = "SELECT tbl_produto.produto 
				FROM tbl_produto
				JOIN    tbl_linha     USING (linha)
				WHERE (tbl_produto.referencia LIKE '$ref%' ) AND
				tbl_linha.fabrica = $login_fabrica;
				";

		echo $sql . '<br />';

		$res1 = pg_exec ($con,$sql);

		$teste = 0;

		if ( pg_numrows($res1) > 0 ) {

			pg_exec($con,"BEGIN TRANSACTION");

			$prod = pg_result($res1, 0, 0);

			$de = "/home/paulo/pos/$arquivo.$extensao";
			$para = "/home/paulo/pos/$prod.$extensao";
			  
			rename($de, $para);
			
			
			if ( pg_numrows($res1) > 1 ) { //se tiver mais de um produto com a descricao

				for($count = 0; $count < pg_numrows($res1); $count++) {

					if ($teste != 0 ) { 
						$prod = pg_result($res1, $count, 0);
						$sql = "INSERT INTO tbl_comunicado_produto (comunicado,produto) values($comunicado,$prod);	";
						pg_query($con,$sql);
					}

					else {
						$sql = "INSERT INTO tbl_comunicado (tipo,fabrica,extensao,obrigatorio_os_produto,ativo) 
						values('Manual Técnico',$login_fabrica, '$extensao', 'f','t');";
						pg_query($con, $sql);

						$teste = pg_exec ($con,"SELECT currval ('seq_comunicado')");
						$comunicado = pg_result ($teste,0,0);

						$sql2 = "INSERT INTO tbl_comunicado_produto (comunicado,produto) values($comunicado,$prod);	";
						pg_query($con, $sql2);
					}
					
					echo '<br />'.$sql;
					echo isset($sql2) ? '<br />'.$sql2 : '';
					if(isset($sql2)) unset($sql2);

				}
			
			}
			else { //apenas um produto, insere só em uma tabela

				$prod = pg_result($res1, 0, 0);
				$ins = "INSERT INTO tbl_comunicado (tipo,fabrica,produto,extensao,obrigatorio_os_produto, ativo) 
						values('Manual Técnico',$login_fabrica, $prod, '$extensao', 'f', 't');";
				pg_query($con, $ins);

				$com = pg_exec ($con,"SELECT currval ('seq_comunicado')");
				$comunicado = pg_result ($com,0,0);

				echo 'Comunicado: ' . $comunicado . '<br />'.$ins;

			}
			if(strlen(pg_errormessage($con)) > 0){
				pg_exec($con,"ROLLBACK TRANSACTION");
			} else {
				pg_exec($con,"COMMIT TRANSACTION");
			}

			$new_dir = str_replace('/*','',$dir);

			$pdf = $new_dir . '/' . $prod . ".$extensao";

			if(file_exists($pdf)){
				echo "<br />mv $pdf /mnt/webuploads/comunicados.tulio/$comunicado.$extensao";
				system("mv $pdf /mnt/webuploads/comunicados.tulio/$comunicado.$extensao");
				$inseridos++;
			}
			else echo '<br /><font color="red">PDF nao Encontrado</font>';

		}

		else {
			echo '<font color="red">Produto nao existe</font><br />';
			$nao++;
		}

		echo '<hr />';		

	}

	echo 'Total: ' . $i . '<br />';
	echo 'Movidos: ' . $inseridos . '<br />';
	echo 'Nao existem ou nao foram movidos: ' . $nao;
?>

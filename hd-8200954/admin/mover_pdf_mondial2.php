<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	$admin_privilegios="info_tecnica";
	include 'autentica_admin.php';
	
	echo $sql = "select lower(nome) from tbl_fabrica where fabrica = $login_fabrica";
	$res = pg_query($con,$sql);
	$fabrica_nome = pg_result($res,0,0);
	echo $dir = "/var/www/$fabrica_nome/vistas_explodidas_$fabrica_nome/*";
	
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
		$patterns = array ('/.pdf/','/.PDF/');
                $arquivo = preg_replace($patterns, '', $fim[5]);
		echo $arquivo . "<br />\n";
	
		if ($arquivo == '' ){ continue;}
		//verifica se o produto com a referencia do PDF existe
		$sql = "SELECT tbl_produto.produto 
				FROM tbl_produto
				JOIN    tbl_linha     USING (linha)
				WHERE ( tbl_produto.referencia LIKE '$arquivo%' OR  tbl_produto.referencia_pesquisa LIKE '$arquivo%' ) AND
				tbl_linha.fabrica = $login_fabrica;
				";

		echo $sql . '<br />';

		$res1 = pg_exec ($con,$sql);

		$teste = 0;

		if ( pg_numrows($res1) > 0 ) {

			pg_exec($con,"BEGIN TRANSACTION");

			$prod = pg_result($res1, 0, 0);

			$de = "/var/www/$fabrica_nome/vistas_explodidas_$fabrica_nome/$arquivo.PDF";
			$para = "/var/www/$fabrica_nome/vistas_explodidas_$fabrica_nome/$prod.pdf";
			  
			rename($de, $para);
			
			$sql = "select referencia from tbl_comunicado JOIN tbl_produto ON tbl_produto.produto = tbl_comunicado.produto WHERE tbl_produto.produto = $prod";

			$res2 = pg_exec ($con,$sql);

			if ( pg_numrows($res2) > 0 ){ //verifica se já existe comunicado e nao insere
				echo '<font color="red">Comunicado Já Cadastrado</font><hr />';
				$nao++;
				continue;
			}

			if ( pg_numrows($res1) > 1 ) { //se tiver mais de um produto com a descricao

				for($count = 0; $count < pg_numrows($res1); $count++) {

					if ($teste != 0 ) { 
						$prod = pg_result($res1, $count, 0);
						$sql = "INSERT INTO tbl_comunicado_produto (comunicado,produto) values($comunicado,$prod);	";
						pg_query($con,$sql);
					}

					else {
						$sql = "INSERT INTO tbl_comunicado (tipo,fabrica,extensao,obrigatorio_os_produto,ativo) 
						values('Vista Explodida',$login_fabrica, 'pdf', 'f','t');";
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
						values('Vista Explodida',$login_fabrica, $prod, 'pdf', 'f', 't');";
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

			$pdf = $new_dir . '/' . $prod . '.pdf';

			echo "$pdf <br>";
			if(file_exists($pdf)){
				echo "<br />mv $pdf /mnt/webuploads/comunicados.tulio/$comunicado.pdf";
				system("mv $pdf /mnt/webuploads/comunicados.tulio/$comunicado.pdf");
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

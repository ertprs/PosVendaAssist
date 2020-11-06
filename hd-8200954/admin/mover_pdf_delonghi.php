<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	$admin_privilegios="info_tecnica";
	include 'autentica_admin.php';

	$dir = "/var/www/delonghi/vistas_explodidas_delonghi/*";

	$i = 0;
	$nao = 0;
	$inseridos = 0;

	if($login_fabrica != 101) {
		die('Logar como Delonghi !');
	}

	foreach(glob($dir) as $file) {

		//if($i == 100) break;		//limite para testes

		$i++;

		$fim = explode( '/', $file );
		$arquivo = str_replace('.pdf','',$fim[5]);
		//echo $arquivo . "<br />\n";
		$referencia = str_replace(' ', '-',$arquivo);
		//verifica se o produto com a referencia do PDF existe
		$sql = "SELECT tbl_produto.produto, tbl_produto.referencia
				FROM tbl_produto
				JOIN    tbl_linha     USING (linha)
				WHERE tbl_produto.descricao LIKE '$referencia%' AND
				tbl_linha.fabrica = $login_fabrica;
				";

		echo 'Cadastrando vista explodida do produto '.$referencia.'... <br />';

		$res1 = pg_exec ($con,$sql);

		$teste = 0;

		if ( pg_numrows($res1) > 0 ) {

			pg_exec($con,"BEGIN TRANSACTION");

			$prod = pg_result($res1, 0, 0);
			$referencia_prod = pg_result($res1,0,1);

			$sql = "select referencia from tbl_comunicado JOIN tbl_produto ON tbl_produto.produto = tbl_comunicado.produto WHERE tbl_produto.referencia = '$referencia_prod' and tbl_comunicado.tipo = 'Vista Explodida'";

			$res2 = pg_exec ($con,$sql);

			if ( pg_numrows($res2) > 0 ){ //verifica se já existe comunicado e nao insere
				echo '<font color="red">Comunicado J&aacute; Cadastrado</font><hr />';
				$nao++;
				continue;
			}

			if ( pg_numrows($res1) > 1 ) { //se tiver mais de um produto com a descricao

				for($count = 0; $count < pg_numrows($res1); $count++) {

					if ($teste != 0 ) { 
						$prod = pg_result($res1, $count, 0);
						$sql = "INSERT INTO tbl_comunicado_produto (comunicado,produto) values($comunicado,$prod);	";
//						pg_query($con,$sql);
					}

					else {
						$sql = "INSERT INTO tbl_comunicado (tipo,fabrica,extensao,obrigatorio_os_produto,ativo) 
						values('Vista Explodida',$login_fabrica, 'pdf', 'f','t');";
//						pg_query($con, $sql);

						$teste = pg_exec ($con,"SELECT currval ('seq_comunicado')");
						$comunicado = pg_result ($teste,0,0);

						$sql2 = "INSERT INTO tbl_comunicado_produto (comunicado,produto) values($comunicado,$prod);	";
//						pg_query($con, $sql2);
					}
					
					//echo '<br />'.$sql;
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

				echo 'Gravado com Sucesso. Comunicado: ' . $comunicado;

			}

			//pg_exec($con,"ROLLBACK TRANSACTION");
			pg_exec($con,"COMMIT TRANSACTION");

			$new_dir = str_replace('/*','',$dir);
			if(!file_exists($new_dir . '/' . $referencia . '.pdf')) {
				$arquivo = str_replace(' ', '\ ',$arquivo); 
				system("mv $new_dir/$arquivo.pdf $new_dir/$referencia.pdf");
			}
			$pdf = $new_dir . '/' . $referencia . '.pdf';
			if(file_exists($pdf)){
				

				//echo "<br />mv $pdf /var/www/assist/www/comunicados/$comunicado.pdf";
				system("cp $pdf /var/www/assist/www/comunicados/$comunicado.pdf");
				$inseridos++;
			}
			else echo '<br /><font color="red">PDF nao Encontrado</font>' . $pdf;

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

<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
include_once dirname(__FILE__) . '/../class/AuditorLog.php';

$msg_erro = "";
$btn_acao = $_REQUEST['btn_acao'];

if (trim($btn_acao) == "gravar") {

	$qtde_linha_produtos_gravar = $_REQUEST['qtde_linha_prod'];
	$qtde_linha_pecas_gravar = $_REQUEST['qtde_linha_peca'];

	# Procedimento de inserção do KIT
	$kit_peca_referencia = strtoupper(substr(trim($_POST['referencia_kit']),0,19));
	$kit_peca_descricao  = strtoupper(substr($_POST['descricao_kit'],0,50));

	if ( strlen($kit_peca_referencia)==0 ){
		$msg_erro = "Kit Inválido";
	}

	if ( strlen($kit_peca_descricao)==0 ){
		$msg_erro = "Kit Inválido";
	}


	if ( strlen($msg_erro)==0){
		$res = pg_query($con,"BEGIN TRANSACTION");

		$sql = "
			SELECT
				referencia,
				descricao
			FROM tbl_kit_peca
			where tbl_kit_peca.referencia = '$kit_peca_referencia'
			OR   tbl_kit_peca.descricao  = '$kit_peca_descricao'
			AND tbl_kit_peca.fabrica = $login_fabrica
		";
		$res = pg_query($con,$sql);

		if ( pg_num_rows($res) > 0 ){
			$msg_erro = "Ja existe um KIT cadastrado com esta referência ou descrição";

			$res = pg_query($con,"ROLLBACK TRANSACTION");
        } else {
            $objLog = new AuditorLog('insert');
            $sql = "

				INSERT INTO tbl_kit_peca (
					fabrica,
					referencia,
					descricao
				) VALUES (
					$login_fabrica,
					'$kit_peca_referencia',
					'$kit_peca_descricao'
				) returning kit_peca;

			";
			// echo nl2br($sql);
			$res = pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);

			if ($msg_erro){
				$res = pg_query($con,"ROLLBACK TRANSACTION");
			}else{
				$kit_peca_cad = pg_result($res,0,0);
				$objLog->retornaDadosSelect("SELECT referencia,descricao FROM tbl_kit_peca WHERE fabrica = $login_fabrica AND kit_peca = $kit_peca_cad")->enviarLog("insert", "tbl_kit_peca", $login_fabrica."*".$kit_peca_cad);
                unset($objLog);
			}
		}
	}

	# Procedimento de inserção dos produtos e pecas do kit
	if ( strlen($msg_erro)==0 ) {
		$tem_prod = false;
		#PRODUTOS INICIO
		for ($i = 0; $i < $qtde_linha_produtos_gravar; $i++) {
			$produto_kit_referencia = trim($_POST["referencia_prod_$i"]);
			$produto_kit_descricao =  $_POST["descricao_prod_$i"];

			if ($login_fabrica == 15) {
				$serial_in  = trim($_POST["serial_in_$i"]);
				$serial_out =  $_POST["serial_out_$i"];
			}

			if ( strlen($produto_kit_referencia)==0 || strlen($produto_kit_descricao)==0) {
				continue;
			} else {
				$tem_prod = true;
			}

			if ( strlen($msg_erro)==0 ){

				$sql_produto = "
								SELECT
									tbl_produto.produto
								from tbl_produto
								where referencia='$produto_kit_referencia';" ;
				$res_produto = pg_query($con,$sql_produto);

				if (  pg_num_rows($res_produto) == 0 ){
					$msg_erro = "Produto Inválido";
				}else{
					$produto_kit = pg_result($res_produto,0,produto);
				}

				if ( strlen($msg_erro)==0 and strlen($produto_kit_referencia)>0 and  strlen($produto_kit_descricao)>0 and $produto_kit){

					if(empty($serial_in)){ //hd_chamado=2552862
						$serial_in = null;
					}
					if(empty($serial_out)){ //hd_chamado=2552862
						$serial_out = null;
					}
                    $objLog = new AuditorLog('insert');
					$sql_cad_prod = "
							INSERT INTO tbl_kit_peca_produto (
								kit_peca,
								fabrica,
								produto,
								serie_inicial,
								serie_final
							)VALUES(
								$kit_peca_cad,
								$login_fabrica,
								$produto_kit,
								'$serial_in',
								'$serial_out'
							)";
					// echo "<br /><br />".nl2br($sql_cad_prod);
					$res = pg_query($con,$sql_cad_prod);
                    $objLog->retornaDadosSelect("SELECT kit_peca_produto,produto FROM tbl_kit_peca_produto WHERE fabrica = $login_fabrica AND kit_peca = $kit_peca_cad")->enviarLog("insert", "tbl_kit_peca_produto", $login_fabrica."*".$kit_peca_cad);
                    unset($objLog);
					$msg_erro = pg_errormessage($con);
				}
			}

			$produto_kit_referencia = null;
			$produto_kit_descricao = null;
			$produto_kit = null;
		}

		$msg_erro = ($tem_prod == false) ? "Produto Inválido" : $msg_erro;


		$tem_peca = false;
		# Pecas INICIO
        $sqlForn = "
            SELECT * FROM (
                (
        ";
		for ($y = 0; $y < $qtde_linha_pecas_gravar; $y++) {

			$peca_kit_referencia = trim($_POST["referencia_peca_$y"]);
			$peca_kit_descricao = trim($_POST["descricao_peca_$y"]);
			$peca_kit_qtde = trim($_POST["qtde_$y"]);
			$peca_kit_somente_kit = $_POST["somente_kit_$y"];

			if ( empty($peca_kit_referencia) and empty($peca_kit_descricao) and empty($peca_kit_qtde) ){
				continue;
			}else{
				$tem_peca = true;
			}

			if ( strlen($peca_kit_referencia)==0 and strlen($peca_kit_descricao) > 0  and strlen($peca_kit_qtde)>0 ){
				$msg_erro = "Peça Inválida -  Informar Referência";
			}

			if ( strlen($peca_kit_descricao)==0 and  strlen($peca_kit_referencia) > 0 and strlen($peca_kit_qtde)>0 ){
				$msg_erro = "Peça Inválida - Informar Descrição";
			}

			if ( strlen($peca_kit_referencia)>0 and strlen($peca_kit_descricao)>0 and strlen($peca_kit_qtde)==0 ){
				$msg_erro = "Peça Inválida - Informar Qtde.";
			}

			if ( strlen($msg_erro)==0 ){
				$sql_peca = "
							SELECT
								tbl_peca.peca
							from tbl_peca

							where tbl_peca.referencia='$peca_kit_referencia'
							AND tbl_peca.fabrica = $login_fabrica;" ;
				$res_peca = pg_query($con,$sql_peca);

				if ( pg_num_rows($res_peca) == 0 ){
					$msg_erro = "Peça Inválida";
				}else{
					$peca_kit = pg_result($res_peca,0,peca);
				}

                /*
                 * - Peça FORA DE LINHA
                 */

                if (strlen($msg_erro) == 0) {
                    $sqlFora = "SELECT  tbl_peca_fora_linha.peca
                                FROM    tbl_peca_fora_linha
                                WHERE   tbl_peca_fora_linha.fabrica = $login_fabrica
                                AND     tbl_peca_fora_linha.peca = $peca_kit
                                AND     tbl_peca_fora_linha.libera_garantia IS NOT TRUE
                    ";

                    $resFora = pg_query($con,$sqlFora);
                    if (pg_num_rows($resFora) > 0) {
                        $msg_erro = "Peça $peca_kit_referencia está fora de linha e sem autorização para atendimento em garantia.";
                    }
                }

                /*
                 * - Resgate dos fornecedores
                 * da peça, para verificação de
                 * unidade entre as peças
                 */

                if($y > 0){
                    $sqlForn .= "
                        ) INTERSECT (
                    ";
                }
                $sqlForn .= "
                    SELECT  tbl_fornecedor_peca.fornecedor  AS retorno_fornecedor_peca      ,
                            tbl_fornecedor.nome             AS retorno_fornecedor_peca_nome
                    FROM    tbl_fornecedor_peca
                    JOIN    tbl_fornecedor  ON  tbl_fornecedor.fornecedor   = tbl_fornecedor_peca.fornecedor
                                            AND tbl_fornecedor_peca.peca    = $peca_kit
                    WHERE   tbl_fornecedor_peca.fabrica = $login_fabrica
                ";

				#PESQUISA PECA NA LISTA BASICA DO PRODUTO - INICIO
				if (strlen($msg_erro)==0 and !empty($peca_kit) ){

					$peca_kit_somente_kit = (!$peca_kit_somente_kit) ? "f" : $peca_kit_somente_kit;

					for ($i = 0; $i < $qtde_linha_produtos_gravar; $i++)
					{
						$produto_kit_referencia = trim($_POST["referencia_prod_$i"]);
						$produto_kit_descricao =  $_POST["descricao_prod_$i"];

						if ( strlen($produto_kit_referencia)==0 || strlen($produto_kit_descricao)==0){
							continue;
						}


						if ( strlen($msg_erro)==0 ){

							$sql_produto = "
											SELECT
												tbl_produto.produto
											from tbl_produto
											where referencia='$produto_kit_referencia';" ;

							$res_produto = pg_query($con,$sql_produto);

							if (  pg_num_rows($res_produto) == 0 ){
								$msg_erro = "Produto Inválido";
							}else{
								$produto_kit = pg_result($res_produto,0,produto);
							}
						}


						$sql = "
								SELECT
										tbl_lista_basica.lista_basica,
										tbl_lista_basica.peca        ,
										tbl_produto.produto
								FROM 	tbl_lista_basica
								JOIN 	tbl_produto on tbl_lista_basica.produto = tbl_produto.produto
								JOIN 	tbl_peca on tbl_lista_basica.peca = tbl_peca.peca
								WHERE 	tbl_peca.peca = $peca_kit
								AND 	tbl_produto.produto = $produto_kit
						";

						$res = pg_query($con,$sql);

						if (pg_num_rows($res)>0){

							#SE EXISTIR LISTA BÁSICA PARA ESTE PRODUTO, VERIFICA O CAMPO "somomente_kit"
							$lista_basica = pg_result($res,0,lista_basica);

							$sql_qtde_lbm = "SELECT
													qtde
											FROM tbl_lista_basica
											WHERE lista_basica = $lista_basica;
													";
							$res_qtde_lbm = pg_query($con,$sql_qtde_lbm);

							$qtde_lbm = pg_result($res_qtde_lbm,0,'qtde');

							if ($peca_kit_qtde <> $qtde_lbm){
								if($login_fabrica == 91){
									if($peca_kit_qtde > $qtde_lbm){
										$msg_erro = "Qtde. digitada para a peça '$peca_kit_referencia' maior do que a quantidade da Lista Básica";
									} else {
										if($peca_kit_qtde <= 0){
											$msg_erro = "Qtde. digitada para a peça '$peca_kit_referencia' deve ser maior do que ZERO";
										}else{
										}
									}
								}else{
									$msg_erro = "Qtde. digitada para a peça '$peca_kit_referencia' difere da quantidade da Lista Básica";
								}
							}

							if ( strlen($msg_erro)==0){
								#SE O CAMPO SOMENTE KIT NÃO ESTIVER MARCADO COMO TRUE, UPDATE PARA TRUE

								$sql_update_somente_kit = "
															UPDATE tbl_lista_basica SET

																somente_kit = '$peca_kit_somente_kit'

															WHERE lista_basica=$lista_basica
															AND   peca=$peca_kit
															AND   produto=$produto_kit
															AND   fabrica=$login_fabrica;
														";

								$res_update_somente_kit = pg_query($con,$sql_update_somente_kit);
								$msg_erro = pg_last_error($con);

                            }


						}else{

							#SE NÃO EXISTIR CADASTRO NA LISTA BÁSICA DESTE PRODUTO E PECA (MARCADA COM SOMENTE KIT), INSERIR.

							$sql_insere_lista_basica = "
								INSERT INTO tbl_lista_basica (

									fabrica,
									produto,
									qtde,
									peca,
									somente_kit,
									ativo,
									admin

								) VALUES (

									$login_fabrica,
									$produto_kit,
									'$peca_kit_qtde',
									$peca_kit,
									'$peca_kit_somente_kit',
									't',
									$login_admin

								);
							";

							$res_insere_lista_basica = pg_query($con,$sql_insere_lista_basica);
							$msg_erro = pg_errormessage($con);


						}

						if ($msg_erro){

								$res = pg_query($con,"ROLLBACK TRANSACTION");
						}
					}
				}

				#PESQUISA PECA NA LISTA BASICA DO PRODUTO - FIM


				if ( strlen($msg_erro)==0 ){
                    $objLog = new AuditorLog('insert');
                    $sql_cad_peca = "
						INSERT INTO tbl_kit_peca_peca (
							kit_peca,
							peca,
							qtde
						)VALUES(
							$kit_peca_cad,
							$peca_kit,
							$peca_kit_qtde
						);
					";
					$res = pg_query($con,$sql_cad_peca);

					$msg_erro = pg_errormessage($con);
                    $objLog->retornaDadosSelect("
                        SELECT  tbl_kit_peca.kit_peca || '' || tbl_kit_peca_peca.peca AS kit_peca_peca,
                                tbl_kit_peca.kit_peca,
                                tbl_kit_peca_peca.peca,
                                tbl_lista_basica.somente_kit
                        FROM    tbl_kit_peca_peca
                        JOIN    tbl_kit_peca            USING(kit_peca)
                        JOIN    tbl_kit_peca_produto    ON  tbl_kit_peca_produto.kit_peca   = tbl_kit_peca.kit_peca
                        JOIN    tbl_lista_basica        ON  tbl_lista_basica.fabrica        = tbl_kit_peca.fabrica
                                                        AND tbl_lista_basica.produto        = tbl_kit_peca_produto.produto
                                                        AND tbl_lista_basica.peca           = tbl_kit_peca_peca.peca
                        WHERE   tbl_kit_peca.fabrica = $login_fabrica
                        AND     tbl_kit_peca_produto.produto = $produto_kit
                        AND     tbl_kit_peca_peca.peca = $peca_kit
                        AND     tbl_kit_peca.kit_peca = $kit_peca_cad")
                        ->enviarLog("insert", "tbl_kit_peca_peca", $login_fabrica."*".$kit_peca_cad);
                    unset($objLog);

					if ($msg_erro) {
						$res = pg_query($con,"ROLLBACK TRANSACTION");
					}
				}else{

					$res = pg_query($con,"ROLLBACK TRANSACTION");
				}
			}

		}

		$sqlForn .= "
                )
            ) AS forn
		";
		$resForn = pg_query($con,$sqlForn);

		if (pg_num_rows($resForn) == 0 AND $login_fabrica == 91) {
            $msg_erro = "Peças não possuem fornecedores em comum. Verifique cadastro Peça X Fornecedor";
		}

		$msg_erro = ($tem_peca == false) ? "Peça Inválida" : $msg_erro;
		#PECAS FIM

	}else{

		$res = pg_query($con,"ROLLBACK TRANSACTION");

	}

	if ( strlen($msg_erro) == 0 ){
		$res = pg_query($con,"COMMIT TRANSACTION");

		header ("Location: $PHP_SELF?ok=ok");
		exit;
	}else{

		$res = pg_query($con,"ROLLBACK TRANSACTION");
		$_GET['new'] = 's';


	}

}


# Procedimento de atualização do KIT
if ($btn_acao == 'atualizar'){

	$kit_peca = $_POST['kit_peca'];

	$qtde_linha_produtos_gravar = $_REQUEST['qtde_linha_prod'];
	$qtde_linha_pecas_gravar = $_REQUEST['qtde_linha_peca'];
	$kit_peca_referencia = strtoupper(substr(trim($_POST['referencia_kit']),0,19));
	$kit_peca_descricao  = strtoupper(substr($_POST['descricao_kit'],0,50));

	if($login_fabrica == 15){
		$serial_in  = trim($_POST["serial_in_$i"]); //hd_chamado=2552862
		$serial_out =  $_POST["serial_out_$i"]; //hd_chamado=2552862
	}


	$res = pg_query($con,"BEGIN TRANSACTION");

    $objLog = new AuditorLog();
    $objLog->retornaDadosSelect("SELECT kit_peca,referencia,descricao FROM tbl_kit_peca WHERE fabrica = $login_fabrica AND kit_peca = $kit_peca");
	$sql = "
		UPDATE tbl_kit_peca set
			fabrica    = $login_fabrica,
			referencia = '$kit_peca_referencia',
			descricao  = '$kit_peca_descricao'
		WHERE kit_peca = $kit_peca;
	";

	$res = pg_query($con,$sql);
	$msg_erro = pg_errormessage($con);

    if (empty($msg_erro)) {
        $objLog->retornaDadosSelect()->enviarLog("update","tbl_kit_peca",$login_fabrica."*".$kit_peca);
        unset($objLog);
    }

    $objPecas = new AuditorLog();
    $objPecas->retornaDadosSelect("SELECT   DISTINCT
                                            tbl_kit_peca.kit_peca || '' || tbl_kit_peca_peca.peca AS kit_peca_peca,
                                            tbl_kit_peca_peca.peca,
                                            tbl_lista_basica.somente_kit
                                    FROM    tbl_kit_peca_peca
                                    JOIN    tbl_kit_peca            USING(kit_peca)
                                    JOIN    tbl_kit_peca_produto    ON  tbl_kit_peca_produto.kit_peca   = tbl_kit_peca.kit_peca
                                    JOIN    tbl_lista_basica        ON  tbl_lista_basica.fabrica        = tbl_kit_peca.fabrica
                                                                    AND tbl_lista_basica.produto        = tbl_kit_peca_produto.produto
                                                                    AND tbl_lista_basica.peca           = tbl_kit_peca_peca.peca
                                    WHERE   tbl_kit_peca.fabrica = $login_fabrica
                                    AND     tbl_kit_peca.kit_peca = $kit_peca");

	#DELETA TODAS AS PEÇAS DO KIT POIS A TABELA TEM 2 CHAVES PRIMARIAS E NAO PERMITE ATUALIZAR
	$sql_del_pecas = "DELETE from tbl_kit_peca_peca where kit_peca=$kit_peca";
	$res_del_pecas = pg_query($con,$sql_del_pecas);
	$msg_erro = pg_errormessage($con);

	$objProd = new AuditorLog();
    $objProd->retornaDadosSelect("SELECT kit_peca_produto,produto FROM tbl_kit_peca_produto WHERE fabrica = $login_fabrica AND kit_peca = $kit_peca");

	$sql_del_prod = "DELETE FROM tbl_kit_peca_produto WHERE kit_peca=$kit_peca";
	$res_del_prod = pg_query($con,$sql_del_prod);
	$msg_erro = pg_errormessage($con);


	if (strlen($msg_erro)==0){

		$tem_prod_update = false;
		#PRODUTOS INICIO
		for ($i=0; $i < $qtde_linha_produtos_gravar; $i++){

			$produto_kit_referencia = trim($_POST["referencia_prod_$i"]);
			$produto_kit_descricao =  $_POST["descricao_prod_$i"];

			if($login_fabrica == 15){
				$serial_in  = trim($_POST["serial_in_$i"]); //hd_chamado=2552862
				$serial_out =  $_POST["serial_out_$i"]; //hd_chamado=2552862
			}

			if( strlen($produto_kit_referencia)==0 || strlen($produto_kit_descricao)==0 ){
				continue;
			}else{
				$tem_prod_update = true;
			}


            if ( strlen($msg_erro)==0 ) {

				$sql_produto = "
								SELECT tbl_produto.produto
								from tbl_produto
								where referencia='$produto_kit_referencia';" ;
				//echo "<br /><br />".nl2br($sql_produto);
				$res_produto = pg_query($con,$sql_produto);

				if ( pg_num_rows($res_produto) == 0 ){
					$msg_erro = "Produto Inválido";
				}else{
					$produto_kit = pg_result($res_produto,0,'produto');
				}

				if ( strlen($msg_erro)==0 and strlen($produto_kit_referencia)>0 and  strlen($produto_kit_descricao)>0 and $produto_kit){

					if(empty($serial_in)){ //hd_chamado=2552862
						$serial_in = null;
					}
					if(empty($serial_out)){ //hd_chamado=2552862
						$serial_out = null;
					}
					$sql_update_prod = "
							INSERT INTO tbl_kit_peca_produto (
								kit_peca,
								fabrica,
								produto,
								serie_inicial,
								serie_final
							)VALUES(
								$kit_peca,
								$login_fabrica,
								$produto_kit,
								'$serial_in',
								'$serial_out'
							)";

// 					echo "<br /><br />".nl2br($sql_update_prod);
					$res_update_prod = pg_query($con,$sql_update_prod);

					$msg_erro = pg_last_error($con);
// echo "1 ->>".$msg_erro;exit;
				}

			}

			#PRODUTOS FIM
			if ($msg_erro){
				$res = pg_query($con,"ROLLBACK TRANSACTION");
			}else{


			}

		}
// 		exit;
		$msg_erro = ($tem_prod_update == false) ? "Produto Inválido" : $msg_erro;


		#PECAS INICIO
		//var_dump($_POST);
		$tem_peca_update = false;

		$sqlForn = "
            SELECT * FROM (
                (
        ";

		for ($y=0; $y < $qtde_linha_pecas_gravar; $y++) {
			$peca_kit_referencia = trim($_POST["referencia_peca_$y"]);
			$peca_kit_descricao = trim($_POST["descricao_peca_$y"]);
			$peca_kit_qtde = trim($_POST["qtde_$y"]);
			$peca_kit_somente_kit = $_POST["somente_kit_$y"];
			// echo "$y - $peca_kit_somente_kit - ";
			if ( empty($peca_kit_referencia) and empty($peca_kit_descricao) and empty($peca_kit_qtde) and empty($peca_kit_somente_kit) ){
				continue;
			}else{
				$tem_peca_update = true;
			}

			if ( strlen($peca_kit_referencia)==0 and strlen($peca_kit_descricao) > 0  and strlen($peca_kit_qtde)>0 ){
				$msg_erro = "Peça Inválida -  Informar Referência";
			}

			if ( strlen($peca_kit_descricao)==0 and  strlen($peca_kit_referencia) > 0 and strlen($peca_kit_qtde)>0 ){
				$msg_erro = "Peça Inválida - Informar Descrição";
			}

			if ( strlen($peca_kit_referencia)>0 and strlen($peca_kit_descricao)>0 and strlen($peca_kit_qtde)==0 ){
				$msg_erro = "Peça Inválida - Informar Qtde.";
			}
// echo "->>".$msg_erro;exit;
			if ( strlen($msg_erro)==0 ){
				$sql_peca = "SELECT tbl_peca.peca
						from tbl_peca
						where tbl_peca.referencia='$peca_kit_referencia'
						AND tbl_peca.fabrica = $login_fabrica;" ;
				$res_peca = pg_query($con,$sql_peca);
				//echo "erro banco d->".pg_last_error();echo "<br>";
				if ( pg_num_rows($res_peca) == 0 ){
					$msg_erro = "Peça Inválida";
				}else{
					$peca_kit = pg_result($res_peca,0,peca);
				}

                /*
                 * - Peça FORA DE LINHA
                 */

                if (strlen($msg_erro) == 0) {
                    $sqlFora = "SELECT  tbl_peca_fora_linha.peca
                                FROM    tbl_peca_fora_linha
                                WHERE   tbl_peca_fora_linha.fabrica = $login_fabrica
                                AND     tbl_peca_fora_linha.peca = $peca_kit
                                AND     tbl_peca_fora_linha.libera_garantia IS NOT TRUE
                    ";

                    $resFora = pg_query($con,$sqlFora);
                    if (pg_num_rows($resFora) > 0) {
                        $msg_erro = "Peça $peca_kit_referencia está fora de linha e sem autorização para atendimento em garantia.";
                    }
                }

                /*
                 * - Resgate dos fornecedores
                 * da peça, para verificação de
                 * unidade entre as peças
                 */

                if($y > 0){
                    $sqlForn .= "
                        ) INTERSECT (
                    ";
                }
                $sqlForn .= "
                    SELECT  tbl_fornecedor_peca.fornecedor  AS retorno_fornecedor_peca      ,
                            tbl_fornecedor.nome             AS retorno_fornecedor_peca_nome
                    FROM    tbl_fornecedor_peca
                    JOIN    tbl_fornecedor  ON  tbl_fornecedor.fornecedor   = tbl_fornecedor_peca.fornecedor
                                            AND tbl_fornecedor_peca.peca    = $peca_kit
                    WHERE   tbl_fornecedor_peca.fabrica = $login_fabrica
                ";

				#PESQUISA PECA NA LISTA BASICA DO PRODUTO - INICIO

				if (strlen($msg_erro)==0 and !empty($peca_kit) ){
					$peca_kit_somente_kit = (!$peca_kit_somente_kit) ? "f" : $peca_kit_somente_kit;
// echo "Entra?";exit;
					for ($k = 0; $k < $qtde_linha_produtos_gravar; $k++) {
						$produto_kit_referencia = trim($_POST["referencia_prod_$k"]);
						$produto_kit_descricao =  $_POST["descricao_prod_$k"];

						if ( strlen($produto_kit_referencia)==0 || strlen($produto_kit_descricao)==0){
							continue;
						}

						if ( strlen($msg_erro)==0 ){
							$sql_produto = "SELECT tbl_produto.produto
								from tbl_produto
								where referencia='$produto_kit_referencia';" ;
							$res_produto = pg_query($con,$sql_produto);
							//echo "erro banco e->".pg_last_error();echo "<br>";
							if (  pg_num_rows($res_produto) == 0 ){
								$msg_erro = "Produto Inválido";
							}else{
								$produto_kit = pg_result($res_produto,0,produto);
							}
						}


						$sql = "SELECT tbl_lista_basica.lista_basica,
							tbl_lista_basica.peca        ,
							tbl_produto.produto
							FROM 	tbl_lista_basica
							JOIN 	tbl_produto on tbl_lista_basica.produto = tbl_produto.produto
							JOIN 	tbl_peca on tbl_lista_basica.peca = tbl_peca.peca
							WHERE 	tbl_peca.peca = $peca_kit
							AND 	tbl_produto.produto = $produto_kit";

						$res = pg_query($con,$sql);
						if (pg_num_rows($res)>0){

							$lista_basica = pg_result($res,0,lista_basica);

							$sql_qtde_lbm = "SELECT
								qtde
								FROM tbl_lista_basica
								WHERE lista_basica = $lista_basica;";
							$res_qtde_lbm = pg_query($con,$sql_qtde_lbm);

							$qtde_lbm = pg_result($res_qtde_lbm,0,'qtde');

							if ($peca_kit_qtde <> $qtde_lbm){
								if($login_fabrica == 91){
									if($peca_kit_qtde > $qtde_lbm){
										$msg_erro = "Qtde. digitada para a peça '$peca_kit_referencia' maior do que a quantidade da Lista Básica";
									} else {
										if($peca_kit_qtde <= 0){
											$msg_erro = "Qtde. digitada para a peça '$peca_kit_referencia' deve ser maior do que ZERO";
										}else{
										}
									}
								}else{
									$msg_erro = "Qtde. digitada para a peça '$peca_kit_referencia' difere da quantidade da Lista Básica";
								}
							}
							if ( strlen($msg_erro)==0){

								#Atualiza campo somente kit
								$sql_update_somente_kit = "UPDATE tbl_lista_basica SET
									somente_kit = '$peca_kit_somente_kit'
									WHERE  peca=$peca_kit
									AND   produto=$produto_kit
									AND   fabrica=$login_fabrica;";
								$res_update_somente_kit = pg_query($con,$sql_update_somente_kit);
								$msg_erro = pg_errormessage($con);
// 								echo "->>".$msg_erro;exit;
							}
						}else{

							$sql_insere_lista_basica = "
								INSERT INTO tbl_lista_basica (

									fabrica,
									produto,
									qtde,
									peca,
									somente_kit,
									ativo,
									admin

								) VALUES (

									$login_fabrica,
									$produto_kit,
									'$peca_kit_qtde',
									$peca_kit,
									't',
									't',
									$login_admin

								);
							";

							$res_insere_lista_basica = pg_query($con,$sql_insere_lista_basica);
// 							echo "erro banco b->".pg_last_error();echo "<br>";
							$msg_erro = pg_errormessage($con);
						}
						if ($msg_erro){
							$res = pg_query($con,"ROLLBACK TRANSACTION");
						}
					}
				}

				#PESQUISA PECA NA LISTA BASICA DO PRODUTO - FIM

				if ( strlen($msg_erro)==0 and $peca_kit){
					$sql_update_pecas_kit = "SELECT kit_peca FROM tbl_kit_peca_peca where kit_peca=$kit_peca and peca=$peca_kit";
					$res_peca2 = pg_query($con,$sql_update_pecas_kit);

					if ( pg_num_rows($res_peca2) == 0 ){
						$sql_update_pecas_kit = "
							INSERT INTO  tbl_kit_peca_peca (
								kit_peca,
								peca,
								qtde
							)VALUES(
								$kit_peca,
								$peca_kit,
								$peca_kit_qtde
							)";

					}else{

						$sql_update_pecas_kit = "UPDATE  tbl_kit_peca_peca SET qtde = $peca_kit_qtde WHERE kit_peca=$kit_peca AND peca=$peca_kit";
					}

					$res_update_pecas_kit = pg_query($con,$sql_update_pecas_kit);
				}

			}
			$msg_erro = ($tem_peca_update ==  false) ? "Peça Inválida" : $msg_erro;
		}

		$sqlForn .= "
                )
            ) AS forn
		";
		$resForn = pg_query($con,$sqlForn);

		if (pg_num_rows($resForn) == 0 AND $login_fabrica == 91) {
            $msg_erro = "Peças não possuem fornecedores em comum. Verifique cadastro Peça X Fornecedor";
		}

	}

	if ($msg_erro){
		$res = pg_query($con,"ROLLBACK TRANSACTION");
		$_GET['kit_peca'] = $kit_peca;

	}else{

        $objProd->retornaDadosSelect()->enviarLog("update", "tbl_kit_peca_produto", $login_fabrica."*".$kit_peca);
        unset($objProd);

        $objPecas->retornaDadosSelect()->enviarLog("update", "tbl_kit_peca_peca", $login_fabrica."*".$kit_peca);
        unset($objPecas);

		$res = pg_query($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF?ok=ok");
		exit;
	}
}


if ( trim($btn_acao) == "apagar" ) {

	$kit_peca = $_POST['kit_peca'];

	$res = pg_query($con,"BEGIN TRANSACTION");

	#RETIRA CONDIÇÂO SOMENTE KIT DA LISTA BÁSICA
	$sql = "UPDATE tbl_lista_basica SET somente_kit = FALSE
			FROM tbl_kit_peca,tbl_kit_peca_produto,tbl_kit_peca_peca
			WHERE tbl_lista_basica.produto = tbl_kit_peca_produto.produto
			AND tbl_lista_basica.peca = tbl_kit_peca_peca.peca
			AND tbl_kit_peca_produto.kit_peca = tbl_kit_peca_produto.kit_peca
			AND tbl_kit_peca.kit_peca = tbl_kit_peca_peca.kit_peca
			AND tbl_kit_peca.kit_peca = $kit_peca
			AND tbl_lista_basica.somente_kit IS TRUE";
	$res = pg_query($con,$sql);
	$msg_erro = pg_errormessage($con);

	$sql = " DELETE FROM tbl_kit_peca_peca
			WHERE kit_peca   = $kit_peca";

	$res = pg_query($con,$sql);
	$msg_erro = pg_errormessage($con);

	if (strlen($msg_erro)==0){

		$sql = "
				DELETE FROM tbl_kit_peca_produto
				WHERE fabrica = $login_fabrica
				AND   kit_peca =  $kit_peca
		";

		$res = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if ( strlen($msg_erro)==0 ){

		$sql = "DELETE FROM tbl_kit_peca
				WHERE  tbl_kit_peca.fabrica      = $login_fabrica
				AND    tbl_kit_peca.kit_peca = $kit_peca ";

		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);

	}

	if (strlen($msg_erro) == 0) {
		$res = pg_query($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF?ok=ok");
		exit;
	} else {
		$res = pg_query($con,"ROLLBACK TRANSACTION");
	}

}

$layout_menu = "cadastro";
$title       = "Cadastramento de Kit de Peças";

include 'cabecalho.php';
?>
<html>

<head>
<script type='text/javascript' src='js/jquery.js'></script>

<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">

<script language='javascript'>

	function fnc_pesquisa_produto (campo, campo2, tipo) {

		if (tipo == "referencia" ) {
			var xcampo = campo;
		}

		if (tipo == "descricao" ) {
			var xcampo = campo2;
		}

		if (xcampo.value != "") {

			var url = "";

			url    = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&lbm=1"+ "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>" ;
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");

			janela.referencia = campo;
			janela.descricao  = campo2;
			janela.produto    = '';//document.frm_kit.produto;
			janela.focus();

		}else{
			alert("Informar toda ou parte da informação para realizar a pesquisa!");
		}

	}

	function fnc_pesquisa_peca (campo, campo2, tipo) {

		if (tipo == "referencia" ) {
			var xcampo = campo;
		}

		if (tipo == "descricao" ) {
			var xcampo = campo2;
		}

		if (xcampo.value != "") {
			var url = "";
			url = "peca_pesquisa.php?campo=" + xcampo.value + "&tipo=" + tipo ;
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
			janela.retorno = "<? echo $PHP_SELF ?>";
			janela.referencia= campo;
			janela.descricao= campo2;
			janela.focus();
		}else{
			alert("Informar toda ou parte da informação para realizar a pesquisa!");
		}

	}


    function adiciona(){

		totals = $("#qtde_linha_prod").val();
		n_linha = totals;
		n_linha++;
        tbl = document.getElementById("tabela");

        var novaLinha = tbl.insertRow(-1);
        var novaCelula;

        if(totals%2==0) cl = "#F7F5F0";
        else cl = "#F1F4FA";


		<?php
			if($login_fabrica == 15){ //hd_chamado=2552862
		?>

				novaCelula = novaLinha.insertCell(0);
				novaCelula.align = "left";
				novaCelula.style.backgroundColor = cl;
				novaCelula.innerHTML = " <input type='text' class='frm' name='serial_in_"+totals+"' id='serial_in_"+totals+"' value='' size='9' maxlength='8' >";
				novaCelula.focus;

				novaCelula = novaLinha.insertCell(1);
				novaCelula.align = "left";
				novaCelula.style.backgroundColor = cl;
				novaCelula.innerHTML = " <input type='text' class='frm' name='serial_out_"+totals+"' id='serial_out_"+totals+"' value='' size='9' maxlength='8' >";
				novaCelula.focus;


				novaCelula = novaLinha.insertCell(2);
				novaCelula.align = "center";
				novaCelula.style.backgroundColor = cl;
				novaCelula.innerHTML = n_linha;
				novaCelula.focus;


				novaCelula = novaLinha.insertCell(3);
				novaCelula.align = "left";
				novaCelula.style.backgroundColor = cl;
				novaCelula.innerHTML = " <input type='text' class='frm' name='referencia_prod_"+totals+"' id='referencia_prod_"+totals+"' value='' size='18' maxlength='20' >	<img src='../imagens/lupa.png' style='cursor: pointer;' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.frm_kit.referencia_prod_"+totals+",document.frm_kit.descricao_prod_"+totals+",\"referencia\")' />";
				novaCelula.focus;

				novaCelula = novaLinha.insertCell(4);
				novaCelula.align = "left";
				novaCelula.style.backgroundColor = cl;
				novaCelula.innerHTML = "<input type='text' class='frm' name='descricao_prod_"+totals+"' id='descricao_prod_"+totals+"' value='' size='50' maxlength='50' >	<img src='../imagens/lupa.png' border='0' style='cursor:pointer' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.frm_kit.referencia_prod_"+totals+",document.frm_kit.descricao_prod_"+totals+",\"descricao\")' />";
				totals++
				$("#qtde_linha_prod").val(totals);

		<?php
			}else{
		?>
				novaCelula = novaLinha.insertCell(0);
				novaCelula.align = "center";
				novaCelula.style.backgroundColor = cl;
				novaCelula.innerHTML = n_linha;
				novaCelula.focus;

				novaCelula = novaLinha.insertCell(1);
				novaCelula.align = "left";
				novaCelula.style.backgroundColor = cl;
				novaCelula.innerHTML = " <input type='text' class='frm' name='referencia_prod_"+totals+"' id='referencia_prod_"+totals+"' value='' size='18' maxlength='20' >	<img src='../imagens/lupa.png' style='cursor: pointer;' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.frm_kit.referencia_prod_"+totals+",document.frm_kit.descricao_prod_"+totals+",\"referencia\")' />";
				novaCelula.focus;

				novaCelula = novaLinha.insertCell(2);
				novaCelula.align = "left";
				novaCelula.style.backgroundColor = cl;
				novaCelula.innerHTML = "<input type='text' class='frm' name='descricao_prod_"+totals+"' id='descricao_prod_"+totals+"' value='' size='61' maxlength='50' >	<img src='../imagens/lupa.png' border='0' style='cursor:pointer' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.frm_kit.referencia_prod_"+totals+",document.frm_kit.descricao_prod_"+totals+",\"descricao\")' />";

				totals++
				$("#qtde_linha_prod").val(totals);
		<?php
			}
		?>


    }


	function adicionaPeca(){
		totals = $("#qtde_linha_peca").val();

        tbl = document.getElementById("tabela_peca");
		n_linha_peca = totals;
		n_linha_peca++;
        var novaLinha = tbl.insertRow(-1);
        var novaCelula;

        if(totals%2==0) cl = "#F7F5F0";
        else cl = "#F1F4FA";


        novaCelula = novaLinha.insertCell(0);
		novaCelula.align = "left";
        novaCelula.style.backgroundColor = cl;
        novaCelula.innerHTML = n_linha_peca;


		novaCelula = novaLinha.insertCell(1);
		novaCelula.align = "left";
        novaCelula.style.backgroundColor = cl;
        novaCelula.innerHTML = " <input type='text' class='frm' name='referencia_peca_"+totals+"' id='referencia_peca_"+totals+"' value='' size='10' maxlength='20' >	<img src='../imagens/lupa.png' style='cursor: pointer;' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca(document.frm_kit.referencia_peca_"+totals+" , document.frm_kit.descricao_peca_"+totals+" , \"referencia\")' />";


        novaCelula = novaLinha.insertCell(2);
        novaCelula.align = "left";
        novaCelula.style.backgroundColor = cl;
        novaCelula.innerHTML = "<input type='text' class='frm' name='descricao_peca_"+totals+"' id='descricao_peca_"+totals+"' value='' size='45' maxlength='50' >	<img src='../imagens/lupa.png' border='0' style='cursor:pointer' align='absmiddle' onclick='javascript: fnc_pesquisa_peca(document.frm_kit.referencia_peca_"+totals+" , document.frm_kit.descricao_peca_"+totals+" , \"descricao\")' />";


		novaCelula = novaLinha.insertCell(3);
        novaCelula.align = "center";
        novaCelula.style.backgroundColor = cl;
        novaCelula.innerHTML = "<input type='text' class='frm' id='qtde_"+totals+"' name='qtde_"+totals+"' value='' size='5' maxlength='20' onkeyup='re = \/\\D\/g; this.value = this.value.replace(re, \"\");'>";

		novaCelula = novaLinha.insertCell(4);
        novaCelula.align = "center";
        novaCelula.style.backgroundColor = cl;
        novaCelula.innerHTML = "<input type='checkbox' name='somente_kit_"+totals+"' id='somente_kit_"+totals+"' value='t'/>";

		totals++
		$("#qtde_linha_peca").val(totals);
    }

    $(function(){
        Shadowbox.init();
    });

</script>

<? include "javascript_calendario_new.php"; //adicionado por Fabio 27-09-2007 ?>

<style type="text/css">
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
}

.subtitulo{
	background-color: #7092BE;
	color:#FFFFFF;
	font:14px Arial;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
</style>

</head>

<body>

<?php
if ($_GET['ok'] == 'ok') {
?>

	<br />
	<table class='sucesso' style='width:700px' align='center'>
		<tr>
			<td>Gravado com sucesso</td>
		</tr>
	</table>


<?
}

if (strlen($msg_erro) > 0) {

?>
	<br />
	<table class='msg_erro' style='width:700px' align='center'>
		<tr>
			<td><? echo $msg_erro; ?></td>
		</tr>
	</table>

<?php
}


if ( strlen($_GET['new'])==0 and strlen($_GET['kit_peca'])==0 ){

if ( $_POST['btn_acao'] == 'pesquisar'){

	$referencia =  $_POST['referencia_prod'];
	$descricao_prod =  $_POST['descricao_prod'];
	$referencia_kit =  $_POST['referencia_kit'];
	$descricao_kit =  $_POST['descricao_kit'];

}
?>
	<form name="frm_kit" method="post" action="<? echo $PHP_SELF ?>">
		<input type='hidden' name='kit_peca' value='<?=$kit_peca?>' />

		<table width='700px' align='center' class='formulario' cellspacing='0' cellpadding='0'>
			<tr>
				<td  class="titulo_tabela">Parâmetros de Pesquisa</td>
			</tr>
			<tr>
				<td align='center'>

					<table width='600px' align='center' border='0'>

						<tr>
							<td align='left'>
								Referência
							</td>

							<td align='left'>
								Descrição Produto
							</td>
						</tr>

						<tr>
							<td align='left'>
								<input type="text" class='frm' name="referencia_prod" id="referencia" value="<? echo $referencia ?>" size="15" maxlength="20" >

								<img src='../imagens/lupa.png' style="cursor: pointer;" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_kit.referencia_prod,document.frm_kit.descricao_prod,'referencia')" />
							</td>
							<td align='left'>
								<input type="text" class='frm' name="descricao_prod" id="descricao" value="<? echo $descricao_prod; ?>" size="50" maxlength="50" >

								<img src='../imagens/lupa.png' border='0' style="cursor:pointer" align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_kit.referencia_prod,document.frm_kit.descricao_prod,'descricao')" />
							</td>
						</tr>

						<tr> <td colspan='2'> &nbsp; </td> </tr>

						<tr>
							<td align='left'>
								Referência Kit
							</td>

							<td align='left'>
								Descrição Kit
							</td>
						</tr>

						<tr>
							<td align='left'>
								<input type="text" class='frm' name="referencia_kit" size="15" maxlength="20" value='<?=$referencia_kit?>' />
							</td>

							<td align='left'>
								<input type="text" class='frm' name="descricao_kit" size="50" maxlength="50" value='<?=$descricao_kit?>' />
							</td>
						</tr>

					</table>

				</td>
			</tr>

			<tr>
				<td>&nbsp;</td>
			</tr>

			<tr>
				<td align='center'>

					<input type='hidden' name="btn_acao" />

					<input type="button" value="Pesquisar" onclick="document.frm_kit.btn_acao.value = 'pesquisar' ; document.frm_kit.submit()" style="cursor:pointer;"/>

					<input type="button" value='Cadastrar novo Kit' onclick="window.location='<?=$PHP_SELF?>?new=s'" style='cursor:pointer;'/>

				</td>
			</tr>

			<tr>
				<td>&nbsp;</td>
			</tr>

		</table>

	</form>


<?php

}else if (   ($_GET['new'] == 's')  ||  (strlen($_GET['kit_peca'])>0)  ){
	$qtde_linha_prod = 5;
	$qtde_linha_peca = 10;


?>
	<form name="frm_kit" method="post" action="<? echo $PHP_SELF ?>">

	<?
	if ($_GET['kit_peca']){
		$kit_peca = $_GET['kit_peca'];

		$sql_kit = "SELECT tbl_kit_peca.referencia,
					tbl_kit_peca.descricao
					FROM tbl_kit_peca
					WHERE tbl_kit_peca.fabrica = $login_fabrica
					AND tbl_kit_peca.kit_peca = $kit_peca";
		$res_kit = pg_query($con,$sql_kit);

		if(pg_num_rows($res_kit) > 0){
			$kit_referencia = pg_result($res_kit,0,'referencia');
			$kit_descricao = pg_result($res_kit,0,'descricao');
		}

	?>
		<input type="hidden" value='<?=$kit_peca?>' name="kit_peca" id="kit_peca"/>
	<?
	}else if ( trim($btn_acao) == "gravar" || trim($btn_acao)=='atualizar' ) {

							$kit_referencia = $_POST["referencia_kit"];
							$kit_descricao  = $_POST["descricao_kit"];

	}
	?>


		<table class="formulario" width="700px" align="center" cellspacing='0' cellpadding='0'>
			<tr>
				<td class="titulo_tabela">Cadastro de Kit de Peças</td>
			</tr>

			<tr>
				<td>&nbsp;</td>
			</tr>

			<tr>
				<td>

					<table align='center' width='600px' id='tbl_kit'>


						<tr>

							<td align='left'>
								Referência Kit
							</td>

							<td align='left'>
								Descrição Kit
							</td>
						</tr>

						<tr>
							<td align='left'>
								<input type="text" class='frm' name="referencia_kit" size="15" maxlength="20" value='<?=$kit_referencia?>' />
							</td>

							<td align='left'>
								<input type="text" class='frm' name="descricao_kit" size="50" maxlength="50" value='<?=$kit_descricao?>' />
							</td>
						</tr>

						<tr>
							<td>&nbsp;</td>
						</tr>

					</table>


					<table align='center' width='650px' class='tabela' id='tabela'>
						<tr  class='titulo_coluna'>

							<?php
								//hd_chamado=2552862
								if($login_fabrica == 15){
							?>
								<td>In</td>
								<td>Out</td>
							<?php
								}
							?>
							<td>#</td>
							<td>
								Referência
							</td>

							<td>
								Descrição Produto
							</td>
						</tr>

						<?
						if($kit_peca){
							$sql_kit_produto = "
								SELECT tbl_produto.referencia,
										tbl_produto.descricao,
										tbl_kit_peca_produto.serie_inicial,
										tbl_kit_peca_produto.serie_final
								FROM tbl_kit_peca_produto
								JOIN tbl_produto USING(produto)
								WHERE tbl_kit_peca_produto.fabrica = $login_fabrica
								AND tbl_kit_peca_produto.kit_peca = $kit_peca";
							$res_kit_produto = pg_query($con,$sql_kit_produto);

							$qtde_linha_prod = (pg_num_rows($res_kit_produto) > 0) ? pg_num_rows($res_kit_produto) : $qtde_linha_prod;

						} else if ( trim($btn_acao) == "gravar" || trim($btn_acao)=='atualizar' ) {

							$qtde_linha_prod = $qtde_linha_produtos_gravar;

						}

						for ($i = 0; $i < $qtde_linha_prod ; $i++){

							$referencia_prod = "";
							$descricao_prod  = "";

							$n_linha = $i + 1;

							if ($kit_peca){
								if(pg_num_rows($res_kit_produto) > 0){

									$referencia_prod = pg_result($res_kit_produto,$i,'referencia');
									$descricao_prod = pg_result($res_kit_produto,$i,'descricao');

									if($login_fabrica == 15){ //hd_chamado=2552862
										$serial_in 	= pg_result($res_kit_produto,$i,'serie_inicial');
										$serial_out = pg_result($res_kit_produto,$i,'serie_final');
									}
									$join_prod = " JOIN tbl_kit_peca_produto ON tbl_kit_peca.kit_peca = tbl_kit_peca_produto.kit_peca AND tbl_kit_peca_produto.produto = tbl_lista_basica.produto ";

								}

							} else if ( trim($btn_acao) == "gravar" || trim($btn_acao)=='atualizar' ) {
								$referencia_prod = $_POST["referencia_prod_$i"];
								$descricao_prod = $_POST["descricao_prod_$i"];

								if($login_fabrica == 15){ //hd_chamado=2552862
									$serial_in  = trim($_POST["serial_in_$i"]); //hd_chamado=2552862
									$serial_out =  $_POST["serial_out_$i"]; //hd_chamado=2552862
								}

							}

						?>
							<tr style="background-color : #F7F5F0">

								<?php
									if($login_fabrica == 15){ //hd_chamado=2552862
								?>
									<td align='left'>
										<input type="text" class='frm' name="serial_in_<?=$i?>" id="serial_in_<?=$i?>" value="<? echo $serial_in ?>" size="9" maxlength="8" >
									</td>
									<td align='left'>
										<input type="text" class='frm' name="serial_out_<?=$i?>" id="serial_out_<?=$i?>" value="<? echo $serial_out ?>" size="9" maxlength="8" >
									</td>
								<?php
									}
								?>
								<td><?echo $n_linha?></td>
								<td align='left'>
									<input type="text" class='frm' name="referencia_prod_<?=$i?>" id="referencia_prod_<?=$i?>" value="<? echo $referencia_prod ?>" size="18" maxlength="20" >

									<img src='../imagens/lupa.png' style="cursor: pointer;" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_kit.referencia_prod_<?=$i?>,document.frm_kit.descricao_prod_<?=$i?>,'referencia')" />
								</td>

								<td align='left'>
									<input type="text" class='frm' name="descricao_prod_<?=$i?>" id="descricao_prod_<?=$i?>" value="<? echo $descricao_prod ?>" size="61" maxlength="50" >

									<img src='../imagens/lupa.png' border='0' style="cursor:pointer" align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_kit.referencia_prod_<?=$i?>,document.frm_kit.descricao_prod_<?=$i?>,'descricao')" />
								</td>
							</tr>
						<?
						}
						?>

					</table>
					<table width='600px' align='center'>
						<tr>
							<td align='left'>
								<input type='button' align='left' id='incluir' value='Adicionar' onclick='adiciona()'/>
							</td>
						</tr>
					</table>

					<br /><br />

					<table align='center' width='600px' class='tabela' id='tabela_peca'>

						<tr  class='titulo_coluna'>
							<td>#</td>
							<td>
								Referência
							</td>

							<td>
								Descrição Peça
							</td>

							<td>
								Qtde
							</td>

							<td>
								Somente <br /> Kit
							</td>
						</tr>

						<?

						if($kit_peca){

							$sql_kit_peca = "
								SELECT DISTINCT
									tbl_peca.peca,
									tbl_peca.referencia,
									tbl_peca.descricao,
									tbl_kit_peca_peca.qtde,
									tbl_lista_basica.somente_kit

								FROM tbl_kit_peca_peca
								JOIN tbl_peca USING(peca)
								JOIN tbl_kit_peca using(kit_peca)
								JOIN tbl_lista_basica ON tbl_kit_peca_peca.peca = tbl_lista_basica.peca AND tbl_lista_basica.fabrica = $login_fabrica
								$join_prod
								WHERE tbl_kit_peca.fabrica = $login_fabrica
								AND tbl_kit_peca_peca.kit_peca = $kit_peca";
							$res_kit_peca = pg_query($con,$sql_kit_peca);

							$qtde_linha_peca = (pg_num_rows($res_kit_peca) > 0) ? pg_num_rows($res_kit_peca) : $qtde_linha_peca;


						}else if ( trim($btn_acao) == "gravar" || trim($btn_acao)=='atualizar' ) {

							$qtde_linha_peca = $qtde_linha_pecas_gravar;

						}


						for ($i = 0; $i < $qtde_linha_peca ; $i++){

							$n_linha = $i + 1;

							if ($kit_peca){
								if(pg_num_rows($res_kit_peca) > 0){
									$referencia_peca = pg_result($res_kit_peca,$i,'referencia');
									$descricao_peca = pg_result($res_kit_peca,$i,'descricao');
									$qtde_ = pg_result($res_kit_peca,$i,'qtde');
									$somente_kit = pg_result($res_kit_peca,$i,'somente_kit');
								}
							}else if ( trim($btn_acao) == "gravar" || trim($btn_acao)=='atualizar' ) {
								$referencia_peca = $_POST["referencia_peca_$i"];
								$descricao_peca = $_POST["descricao_peca_$i"];
								$qtde_ = $_POST["qtde_$i"];
								$somente_kit = $_POST["somente_kit_$i"];

								if($login_fabrica == 15){ //hd_chamado=2552862
									$serial_in  = trim($_POST["serial_in_$i"]); //hd_chamado=2552862
									$serial_out =  $_POST["serial_out_$i"]; //hd_chamado=2552862
								}

							}
							$checked = ($somente_kit == 't') ? "CHECKED" : null;

						?>
							<tr style="background-color : #F7F5F0">
								<td><?echo $n_linha?></td>

								<td align='left'>
									<input type="text" class='frm' name="referencia_peca_<?=$i?>" id="referencia_peca_<?=$i?>" value="<? echo $referencia_peca ;?>" size="10" maxlength="20" >

									<img src='../imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca(document.frm_kit.referencia_peca_<?=$i?> , document.frm_kit.descricao_peca_<?=$i?> , "referencia")' style='cursor:pointer'>
								</td>

								<td align='left'>
									<input type="text" class='frm' name="descricao_peca_<?=$i?>" id="descricao_peca_<?=$i?>" value="<? echo $descricao_peca; ?>" size="45" maxlength="50" >

									<img src='../imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca(document.frm_kit.referencia_peca_<?=$i?> , document.frm_kit.descricao_peca_<?=$i?> , "descricao")' style='cursor:pointer'>
								</td>

								<td>
									<input type='text' class='frm' id='qtde_<?=$i?>' name='qtde_<?=$i;?>' value='<?echo $qtde_?>' size='5' maxlength='20' onkeyup="re = /\D/g; this.value = this.value.replace(re, '');">
								</td>

								<td>
									<input type="checkbox" name="somente_kit_<?=$i?>" id="somente_kit_<?=$i?>" value='t' <?=$checked?>/>
								</td>
							</tr>
						<?
						}
						?>
					</table>
					<table width='600px' align='center'>
						<tr>
							<td align='left'>
								<input type='button' align='left' id='incluir_peca' value='Adicionar' onclick='adicionaPeca()'/>
							</td>
						</tr>
					</table>


				</td>
			</tr>

			<tr>
				<td>

					<input type='hidden' name="btn_acao" />
					<input type="hidden" value='<?=$qtde_linha_prod?>' name="qtde_linha_prod" id="qtde_linha_prod"/>
					<input type="hidden" value='<?=$qtde_linha_peca?>' name="qtde_linha_peca" id="qtde_linha_peca"/>


					<?
					if (isset($_GET['kit_peca'])) {?>
						<input type="button" value='Atualizar' onclick='document.frm_kit.btn_acao.value = "atualizar" ; document.frm_kit.submit()' style='cursor:pointer;'/>
						<input type='button' value='Apagar' onclick='document.frm_kit.btn_acao.value = "apagar" ; document.frm_kit.submit()' style='cursor:pointer;' /><?php
					}else{?>
						<input type="button" value='Cadastrar' onclick='document.frm_kit.btn_acao.value = "gravar" ; document.frm_kit.submit()' style='cursor:pointer;'/>

					<?
					}?>

					<input type="button" value='Voltar' onclick="window.location='<?=$PHP_SELF?>'"/>

				</td>
			</tr>
		</table>

	</form>

<?php
                    if (isset($_GET['kit_peca'])) {
?>
<br />
<strong><a rel="shadowbox" href='relatorio_log_alteracao_new.php?parametro=tbl_kit_peca&id=<?=$_GET['kit_peca']?>'>Visualizar Log Auditor</a></strong>
<?php
                    }
}

if ( strlen($_GET['new'])==0 and strlen($_GET['kit_peca'])==0 ){

	if ($_POST['btn_acao']=='pesquisar'){

		$pesquisa = 'ok';

		$referencia_kit_pesquisa = strtoupper( trim($_POST['referencia_kit']));
		$descricao_kit_pesquisa = strtoupper( $_POST['descricao_kit'] );
		$referencia_produto_pesquisa = strtoupper( trim($_POST['referencia_prod']) );

	}

	if ($pesquisa){

		$sql = " SELECT tbl_produto.referencia,
						tbl_produto.descricao,
						tbl_kit_peca.kit_peca,
						tbl_kit_peca.referencia as kit_referencia,
						tbl_kit_peca.descricao as kit_descricao
				FROM    tbl_produto
				JOIN    tbl_kit_peca_produto USING(produto)
				JOIN 	tbl_kit_peca ON tbl_kit_peca.kit_peca = tbl_kit_peca_produto.kit_peca
				WHERE   tbl_kit_peca.fabrica = $login_fabrica
				AND     tbl_produto.referencia like '$referencia_produto_pesquisa%'
				AND     tbl_kit_peca.referencia like '$referencia_kit_pesquisa%'
				AND     tbl_kit_peca.descricao like '$descricao_kit_pesquisa%'
				ORDER BY tbl_kit_peca.kit_peca";


		$res = pg_query($con,$sql);

	}else{

		$sql = " SELECT tbl_kit_peca.kit_peca,
						tbl_kit_peca.referencia as kit_referencia,
						tbl_kit_peca.descricao as kit_descricao
				FROM    tbl_kit_peca
				WHERE   tbl_kit_peca.fabrica = $login_fabrica
				ORDER BY tbl_kit_peca.kit_peca";


		$res = pg_query($con,$sql);

	}
	if (pg_num_rows($res) > 0) {
			flush();
			echo "<br />\n";
			echo "<table width='700' align='center' border='0' class='tabela' cellpadding='1' cellspacing='1'>";
			echo "<thead>";
				echo "<tr class='titulo_coluna'>";
					echo "<td align='center' style='width:100px'>";
						echo "Ref. Kit";
					echo "</td>";
					echo "<td align='center'>";
						echo "Descrição";
					echo "</td>";
				echo "</tr>";
			echo "</thead>";

			echo "<tbody>";


			for ($i = 0; $i < pg_num_rows($res); $i++ ) {

				$kit_peca        = pg_result($res,$i,'kit_peca');
				$kit_referencia  = pg_result($res,$i,'kit_referencia');
				$kit_descricao   = pg_result($res,$i,'kit_descricao');

				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";


				echo "<tr style='background-color: $cor'>";
					echo "<td align='center' nowrap><a href='$PHP_SELF?kit_peca=".$kit_peca."'>" . $kit_referencia  . "</a>&nbsp;</td>";
					echo "<td align='center' nowrap><a href='$PHP_SELF?kit_peca=".$kit_peca."'>" . $kit_descricao   . "</a>&nbsp;</td>";
				echo "</tr>";

			}

			echo "</tbody>";

		echo "</table>";

	}else{?>
		<center> Não foram encontrados resultados para a sua pesquisa </center>
	<?
	}

}

include "rodape.php"; ?>

</body>
</html>

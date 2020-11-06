<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include 'funcoes.php';

$abrir = fopen("bloqueio_pedidos/bloqueia_pedido_black.txt", "r");
$ler = fread($abrir, filesize("bloqueio_pedidos/bloqueia_pedido_black.txt"));
fclose($abrir); 

$conteudo = explode(";;", $ler);

$data_inicio = $conteudo[0];
$data_fim    = $conteudo[1];
$comentario  = $conteudo[3];



if (strval(strtotime(date("Y-m-d H:i:s")))  < strval(strtotime("$data_fim"))) { // DATA DA VOLTA
	if (strval(strtotime(date("Y-m-d H:i:s")))  >= strval(strtotime("$data_inicio"))) { // DATA DO BLOQUEIO
		$title     = "Pedido de Peças";
		$cabecalho = "Pedido de Peças";
		$layout_menu = 'pedido';
		
		include "cabecalho.php";

		echo "<br><br>\n";
		echo "<table width='500' border='0' align='center' cellpadding='5' cellspacing='2' style='border-collapse: collapse' bordercolor='000000'>";
		echo "<TR align='center' bgcolor='#336699'>";
		echo "<form method='post' name='frm_condicao' action='$PHP_SELF'>";
		echo "	<TD colspan='3' height='40' style='font-family: verdana, arial ; font-size: 16px; font-size: 14px; color:#FFFFFF;'><B>COMUNICADO BLACK & DECKER</B></TD>";
		echo "<TR align='justify' style='font-family: verdana, arial ; font-size: 10px; color:#000000' bgcolor='#C1E0FF' onmouseout=\"this.bgColor='#C1E0FF'\">";
		echo "<TD><p align='justify' style='font-family: verdana, arial ; font-size: 10px; color:#000000'>$comentario</p></TD>";

		echo "</form>";
		echo "</TR>";
		echo "</table>";
		include "rodape.php";
		exit;
	}
}



$sql = "SELECT  tbl_posto_fabrica.codigo_posto       ,
				tbl_posto_fabrica.tipo_posto         ,
				tbl_posto_fabrica.pedido_faturado    ,
				tbl_posto_fabrica.pedido_em_garantia ,
				tbl_posto.cnpj                       ,
				tbl_posto.ie                         ,
				tbl_posto.nome                       ,
				tbl_posto.estado                     
		FROM    tbl_posto_fabrica
		JOIN    tbl_posto USING(posto)
		WHERE   tbl_posto_fabrica.posto   = $login_posto
		AND     tbl_posto_fabrica.fabrica = $login_fabrica";
$res_posto = @pg_exec ($con,$sql);

if (@pg_numrows ($res_posto) == 0 OR strlen (trim (pg_errormessage($con))) > 0 ) {
	header ("Location: index.php");
	exit;
}

$codigo_posto       = trim(pg_result ($res_posto,0,codigo_posto));
$tipo_posto         = trim(pg_result ($res_posto,0,tipo_posto));
$nome_posto         = trim(pg_result ($res_posto,0,nome));
$cnpj               = trim(pg_result ($res_posto,0,cnpj));
$ie                 = trim(pg_result ($res_posto,0,ie));
$estado             = trim(pg_result ($res_posto,0,estado));
$pedido_faturado    = trim(pg_result ($res_posto,0,pedido_faturado));
$pedido_em_garantia = trim(pg_result ($res_posto,0,pedido_em_garantia));

if ($pedido_faturado == 'f') {
	$title     = "Pedido de Peças";
	$cabecalho = "Pedido de Peças";
	$layout_menu = "pedido";

	include "cabecalho.php";

	echo "<style type=\"text/css\">
			.menu_top { text-align: center; font-family: Verdana, Geneva, Arial, Helvetica, sans-serif; font-size: 10px; font-weight: bold; border: 0px solid; color:'#ffffff'; background-color: '#596D9B'; }
			.table_line1 { font-family: Verdana, Geneva, Arial, Helvetica, sans-serif; font-size: 11px; font-weight: normal; border: 0px solid; }
		</style>";

	echo "<table width='700' border='0' cellpadding='3' cellspacing='1' align='center'>\n";
		echo "<tr>\n";
			echo "<td width='100%' align='left' class='table_line1'>\n";
				echo "<b>Caro $nome_posto</b>, seu pedido de peças deve ser efetuado através de um distribuidor de sua região.\n";
				echo "<br><br>\n";
				echo "Abaixo relação de distribuidores por região:\n";
			echo "</td>\n";
		echo "</tr>\n";
	echo "</table>\n";
	
	$sql = "SELECT  tbl_posto.nome                                                                                 ,
					tbl_posto_fabrica.codigo_posto                                                                 ,
					tbl_posto.fone                                                                                 ,
					tbl_posto.contato                                                                              ,
					tbl_posto.email                                                                                ,
					(tbl_posto.endereco || ', ' || tbl_posto.numero || ' ' || tbl_posto.complemento) AS endereco   ,
					tbl_posto.bairro                                                                               ,
					tbl_posto.cidade                                                                               ,
					tbl_posto.estado                                                                               ,
					(substr(tbl_posto.cep,1,5) || '-' || substr(tbl_posto.cep,6,3))                     AS cep     ,
					(case tbl_posto.estado when '$estado' then '1' else '2' end )                       AS ordem
			FROM    tbl_posto
			JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			WHERE   tbl_posto_fabrica.codigo_posto not in (1122, 23513, 20741, 21957)
			AND     tbl_posto_fabrica.tipo_posto = 39
			OR      tbl_posto_fabrica.tipo_posto = 40
			ORDER BY ordem, tbl_posto.cidade";
	$res = @pg_exec ($con,$sql);
	
	for ($x=0; $x < pg_numrows($res); $x++) {
		$nome     = trim(pg_result($res,$x,nome));
		$posto    = trim(pg_result($res,$x,codigo_posto));
		$estado   = trim(pg_result($res,$x,estado));
		$fone     = trim(pg_result($res,$x,fone));
		$contato  = trim(pg_result($res,$x,contato));
		$email    = trim(pg_result($res,$x,email));
		$endereco = trim(pg_result($res,$x,endereco));
		$bairro   = trim(pg_result($res,$x,bairro));
		$cidade   = trim(pg_result($res,$x,cidade));
		$cep      = trim(pg_result($res,$x,cep));
		
		if ($codigo_posto <> "21957" and $codigo_posto <> "20741") {
			echo "<table width='700' border='0' cellpadding='3' cellspacing='1' align='center'>\n";
				echo "<tr>\n";
					echo "<td align='left'   class='menu_top'><b>DISTRIBUIDOR</b></td>\n";
					echo "<td align='center' class='menu_top'><b>CÓDIGO</b></td>\n";
					echo "<td align='center' class='menu_top'><b>UF</b></td>\n";
					echo "<td align='center' class='menu_top'><b>TELEFONE</b></td>\n";
					echo "<td align='left'   class='menu_top'><b>CONTATO</b></td>\n";
					echo "<td align='center' class='menu_top'><b>E-mail</b></td>\n";
				echo "</tr>\n";
				echo "<tr>\n";
					echo "<td align='left'   class='table_line1'>$posto - $nome</td>\n";
					echo "<td align='center' class='table_line1'>$posto</td>\n";
					echo "<td align='center' class='table_line1'>$estado</td>\n";
					echo "<td align='center' class='table_line1'>$fone</td>\n";
					echo "<td align='left'   class='table_line1'>$contato</td>\n";
					echo "<td align='left'   class='table_line1'>$email</td>\n";
				echo "</tr>\n";
			echo "</table>\n";
			
			echo "<table width='700' border='1' cellpadding='2' cellspacing='0' align='center'>\n";
				echo "<tr>\n";
					echo "<td align='left'   class='menu_top'><b>ENDEREÇO</b></td>\n";
					echo "<td align='center' class='menu_top'><b>BAIRRO</b></td>\n";
					echo "<td align='center' class='menu_top'><b>CIDADE</b></td>\n";
					echo "<td align='center' class='menu_top'><b>CEP</b></td>\n";
				echo "</tr>\n";
				echo "<tr>\n";
					echo "<td align='left'   class='table_line1'>$endereco</td>\n";
					echo "<td align='center' class='table_line1'>$bairro</td>\n";
					echo "<td align='center' class='table_line1'>$cidade</td>\n";
					echo "<td align='center' class='table_line1'>$cep</td>\n";
				echo "</tr>\n";
			echo "</table>\n";
			
			echo "<br>\n";
		}
	}
	echo "<br><br>\n";
	
	include "rodape.php";
	exit;
}

setcookie ("cook_pedido");
$cook_pedido = "";

$sql =	"SELECT  tbl_pedido.pedido,
			substr(tbl_pedido.seu_pedido,4,5)
			AS pedido_blackedecker,
				tbl_pedido.pedido_blackedecker   ,
				suframa.pedido_suframa 
		FROM    tbl_pedido
		LEFT JOIN tbl_pedido suframa ON suframa.pedido_suframa = tbl_pedido.pedido
		WHERE   tbl_pedido.exportado           ISNULL
		AND     tbl_pedido.controle_exportacao ISNULL
		AND     tbl_pedido.admin               ISNULL
		AND     (tbl_pedido.status_pedido       <> 14 or tbl_pedido.status_pedido IS NULL)
		AND     tbl_pedido.pedido_os           IS NOT TRUE
		AND     tbl_pedido.pedido_acessorio    IS NOT TRUE
		AND     tbl_pedido.pedido_sedex        IS NOT TRUE ";


	$sql .= "AND tbl_pedido.tabela = 435 ";


$sql .= "AND tbl_pedido.posto    = $login_posto
		 AND tbl_pedido.fabrica = $login_fabrica;";
//adicionado AND tbl_pedido.fabrica = $login_fabrica;";  HD7653

#if($ip='200.246.170.155')echo $sql;
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	$pedido_suframa      = trim(pg_result($res,0,pedido_suframa));
	$cook_pedido         = trim(pg_result($res,0,pedido));
	$pedido_blackedecker = trim(pg_result($res,0,pedido_blackedecker));
	
	setcookie ("cook_pedido",$cook_pedido,time()+(3600*48));
}

if (strlen($cook_pedido) > 0) {
	$sql = "SELECT  tbl_condicao.condicao
			FROM    tbl_pedido
			JOIN    tbl_condicao ON tbl_condicao.condicao = tbl_pedido.condicao
			WHERE   tbl_pedido.pedido = $cook_pedido";
	$res = @pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$condicao = trim(pg_result($res,0,condicao));
	}
}

if (strlen($cook_pedido) > 0 and strlen($btngravar) == 0 and strlen($finalizar) == 0) {
	$res = pg_exec($con, "BEGIN TRANSACTION");
	
	if (strlen($pedido_suframa) > 0) {
		$sql = "INSERT INTO tbl_pedido_item (
					pedido              ,
					peca                ,
					qtde                ,
					preco               ,
					produto_locador     ,
					nota_fiscal_locador ,
					data_nf_locador     
			)
			SELECT  $cook_pedido                        ,
					tbl_pedido_item.peca                ,
					tbl_pedido_item.qtde                ,
					tbl_pedido_item.preco               ,
					tbl_pedido_item.produto_locador     ,
					tbl_pedido_item.nota_fiscal_locador ,
					tbl_pedido_item.data_nf_locador     
			FROM    tbl_pedido_item
			JOIN    tbl_pedido using (pedido)
			WHERE   tbl_pedido.pedido_suframa = $cook_pedido;";
		$res = @pg_exec ($con,$sql);
		
		if (strlen ( pg_errormessage ($con) ) > 0) {
			$msg_erro = pg_errormessage ($con) ;
		}
		
		if (strlen($msg_erro) == 0) {
			$sql = "SELECT fn_pedido_delete ($cook_pedido, $login_fabrica, null)";
			$res = @pg_exec ($con,$sql);
			
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro = pg_errormessage ($con) ;
			}
		}
	}
	
	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if (strlen($_GET["ignorar"]) > 0) {
	$ignorar = trim($_GET["ignorar"]);

	$sql = "SELECT fn_pedido_delete ($ignorar, $login_fabrica, null)";
	$res = pg_exec ($con,$sql);

	setcookie ("cook_pedido");
	$cook_pedido = "";

	header ("Location: $PHP_SELF");
	exit;
}

//HD 21009 - 27/5/2008
#----------------------- Deletar Item ------------------
if (strlen($_GET["delete"]) > 0) {
	$delete = trim($_GET["delete"]);
	$pedido = trim($_GET["pedido"]);

	$sql = "DELETE FROM tbl_pedido_item
			WHERE  tbl_pedido_item.pedido_item = $delete";
	$res = @pg_exec ($con,$sql);

	$sqlP = "SELECT pedido_item FROM tbl_pedido_item WHERE pedido = $pedido";
	$resP = @pg_exec ($con,$sqlP);
	if(pg_numrows($resP)==0){
		$sql = "DELETE FROM tbl_pedido
		WHERE  tbl_pedido.pedido = $pedido";
		$res = @pg_exec ($con,$sql);
	}

	if (strlen ( pg_errormessage ($con) ) > 0) {
		$msg_erro = pg_errormessage ($con) ;
	}else{
		header ("Location: $PHP_SELF");
		exit;
	}
}


#----------------------- Finalizar Pedido ------------------
if ($finalizar == 1) {
	if (strlen($msg_erro) == 0) {
		$sql = "UPDATE tbl_pedido SET
					finalizado=current_timestamp,
					unificar_pedido = '$unificar'
				WHERE  tbl_pedido.pedido = $cook_pedido
				AND    tbl_pedido.unificar_pedido isnull;";
		$res = @pg_exec ($con,$sql);

		if (strlen ( pg_errormessage ($con) ) > 0) {
			$msg_erro = pg_errormessage ($con) ;
		}
	}

	if (strlen($msg_erro) == 0) {
		if (strlen (trim ($msg_erro)) == 0) {
			$sql = "INSERT INTO tbl_pedido_alteracao (
						pedido
					)VALUES(
						$cook_pedido
					)";
			$res = @pg_exec ($con,$sql);

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro = pg_errormessage ($con) ;
			}
		}
	}

	
	if (strlen($msg_erro) == 0) {
		$sql = "SELECT fn_pedido_suframa($cook_pedido,$login_fabrica);";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen($msg_erro) == 0) {
		$msg = $_GET['msg'];
		header ("Location: pedido_finalizado.php?pedido=$cook_pedido&msg=".$msg);
		exit;
	}
}

#--------------- Gravar Item ----------------------
if ($btngravar == "Gravar") {

	$condicao = trim($_POST['condicao']);
	
	$sql = "SELECT tbl_tabela.tabela
				FROM   tbl_tabela
				WHERE  tbl_tabela.tabela = 435";

	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) > 0) $tabela = pg_result ($res,0,0);
	
	if (strlen($msg_erro) == 0){
		$res = pg_exec($con, "BEGIN TRANSACTION");
		
		if (strlen ($cook_pedido) == 0) {
			$sql =	"INSERT INTO tbl_pedido (
						posto         ,
						condicao      ,
						tabela        ,
						fabrica       ,
						tipo_pedido
					) VALUES (
						$login_posto  ,
						'$condicao'   ,
						'$tabela'     ,
						$login_fabrica,
						202
					)";
			$res = @pg_exec ($con,$sql);

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro = pg_errormessage($con);
			}else{
				$res = pg_exec ($con,"SELECT currval ('seq_pedido')");
				$cook_pedido = pg_result ($res,0,0);

				# cookie expira em 48 horas
				setcookie ("cook_pedido",$cook_pedido,time()+(3600*48));
			}
		}else{
			$sql = "UPDATE tbl_pedido SET
						tabela     = '$tabela'       ,
						condicao   = '$condicao'     ,
						finalizado = null
					WHERE tbl_pedido.pedido = $cook_pedido;";
			$res = @pg_exec ($con,$sql);

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro = pg_errormessage ($con) ;
			}

			if (strlen (trim ($msg_erro)) == 0) {
				$sql = "INSERT INTO tbl_pedido_alteracao (
							pedido
						)VALUES(
							$cook_pedido
						)";
				$res = @pg_exec ($con,$sql);

				if (strlen ( pg_errormessage ($con) ) > 0) {
					$msg_erro = pg_errormessage ($con) ;
				}
			}
		}
		
		if (strlen (trim ($msg_erro)) == 0) {
			for ($i = 0 ; $i < 15 ; $i++) {
				$referencia          = trim($_POST["referencia" . $i]);
				$qtde                = trim($_POST["qtde"       . $i]);
				$xproduto            = trim($_POST["produto"]);
				$xnota               = trim($_POST["nota_fiscal"]);
				$xdata_nota          = trim($_POST["data_emissao"]);
				$xproduto_serie      = trim($_POST["produto_serie"]);
				$xproduto_referencia = trim($_POST["produto_referencia"]);
				$xproduto_tipo       = trim($_POST["produto_tipo"]);

				if (strlen($xproduto) == 0) $xproduto = "null";
				else                        $xproduto = "'" . $xproduto . "'";

				if (strlen($xnota) == 0 ) $xnota = "null";
				else                      $xnota = "'" . $xnota . "'";

				$xdata_nota = fnc_formata_data_pg($xdata_nota);

				$xreferencia = str_replace(" ","",$referencia);
				$xreferencia = str_replace(".","",$xreferencia);
				$xreferencia = str_replace("-","",$xreferencia);
				$xreferencia = str_replace("/","",$xreferencia);
				
				if (strlen($referencia) > 0) {
					//echo $referencia.' - '.$qtde.' - '.$xproduto.' - '.$xnota.' - '.$xdata_nota.' - '.$xproduto_serie.'<br>';
					$sql = "SELECT tbl_peca.peca
							FROM   tbl_peca
							WHERE  tbl_peca.referencia_pesquisa = '$xreferencia'
							AND    fabrica = $login_fabrica";
					$resX = pg_exec ($con,$sql);

					if (strlen($xproduto_serie) == 0 ) $msg_erro = "Antes de digitar as peças, informe o número de série do produto e clique na lupa para pesquisar.";
					else                      $xproduto_serie = "'" . $xproduto_serie . "'";

					if (strlen($msg_erro)==0) {
						//valida garantia do número de série para o caso do posto alterar o que foi pesquisado
						$sql = "SELECT  locacao
								FROM tbl_locacao
								WHERE posto           = $login_posto
								AND   serie           = $xproduto_serie
								AND   produto         = $xproduto";
						$res = @pg_exec ($con,$sql);
						$msg_erro = pg_errormessage($con);
						
						if (strlen($msg_erro) == 0) {
							if (pg_numrows($res)==0) {
								$msg_erro = "Número de serie inválido.";
							} else {
								$sql = "SELECT  locacao
										FROM tbl_locacao
										WHERE posto           = $login_posto
										AND   data_emissao+interval'6 months' >= current_date
										AND   serie           = $xproduto_serie
										AND   produto         = $xproduto";
								$res = @pg_exec ($con,$sql);
								$msg_erro = pg_errormessage($con);
								
								if (strlen($msg_erro) == 0) {
									if (pg_numrows($res)==0) {
										$msg_erro = "Prazo de garantia vencido, excede 6 meses.";
									}
								}
							}
						}
					}

					if (strlen($msg_erro)==0) {
						if (pg_numrows($resX) > 0 AND strlen (trim ($qtde)) > 0 AND $qtde > 0) {
							$peca = pg_result($resX,0,0);
							$sql = "INSERT INTO tbl_pedido_item (
									pedido              ,
									peca                ,
									qtde                ,
									produto_locador     ,
									nota_fiscal_locador ,
									data_nf_locador     ,
									serie_locador        
								)VALUES(
									$cook_pedido ,
									$peca        ,
									$qtde        ,
									$xproduto    ,
									LPAD (TRIM ($xnota),6,'0'),
									$xdata_nota  ,
									$xproduto_serie
								)";
							if($ip=='200.246.170.155') echo nl2br($sql);

							$res = @pg_exec ($con,$sql);
							$msg_erro = pg_errormessage($con);

							if (strlen($msg_erro) == 0) {
								$res         = @pg_exec ($con,"SELECT CURRVAL ('seq_pedido_item')");
								$pedido_item = @pg_result ($res,0,0);
								$msg_erro = pg_errormessage($con);
							}
							
							if (strlen($msg_erro) == 0) {
								$sql = "SELECT fn_valida_pedido_item ($cook_pedido,$peca,$login_fabrica)";
								$res = @pg_exec ($con,$sql);
								$msg_erro = pg_errormessage($con);
							}
							
							if (strlen($msg_erro) == 0) {
								#verifica se a peça pertence ao produto
								$sql = "SELECT peca 
										FROM tbl_lista_basica
										WHERE fabrica = $login_fabrica
										AND   produto = $xproduto
										AND   peca    = $peca";
								$res = @pg_exec($con,$sql);
								$msg_erro = pg_errormessage($con);

								if (pg_numrows($res) == 0) {
									$msg_erro = "A peça $referencia não pertence ao produto $xproduto_referencia, consulte a vista explodida ou lista básica e verifique o código correto.";
								}
							}

							if (strlen($msg_erro) == 0) {
								#verifica quantidade da lista básica
								$sql = "SELECT SUM(qtde)
										FROM tbl_pedido_item
										WHERE tbl_pedido_item.pedido = $cook_pedido
										AND   tbl_pedido_item.serie_locador = $xproduto_serie
										AND   tbl_pedido_item.peca = $peca";
	$sqlx .= $sql;
								$res = @pg_exec($con,$sql);
								$msg_erro = pg_errormessage($con);

								if (strlen($msg_erro) == 0) {
									$qtde_pedida = @pg_result ($res,0,0);
									$msg_erro = pg_errormessage($con);
								}
							}

							if (strlen($msg_erro) == 0) {
								//não duplicar peça dentro do mesmo número de série
								$sql = "SELECT  peca, count(*) as qtde
										FROM   tbl_pedido_item 
										WHERE  pedido        = $cook_pedido
										AND    serie_locador = $xproduto_serie
										AND    peca          = $peca
										group by peca
										having count(*) > 1";
								$res = @pg_exec($con,$sql);
								$msg_erro = pg_errormessage($con);

								if (strlen($msg_erro) == 0) {
									$qtde_pedido_item = @pg_result ($res,0,0);
									$msg_erro = pg_errormessage($con);

									if ($qtde_pedido_item > 1) {
										$msg_erro = "Peça $referencia em duplicidade para o produto $xproduto_referencia de série $xproduto_serie, verifique se a peça foi digitada mais de uma vez, ou se ela já foi pedida no resumo. Caso dejese solicitar mais peças, aumente a quantidade agrupando em um único item.";
									}
								}
							}

							if (strlen($msg_erro) == 0) {
								$sql = "SELECT SUM(qtde)
										FROM tbl_lista_basica
										WHERE fabrica = $login_fabrica
										AND   produto = $xproduto
										AND   type    = '$xproduto_tipo'
										AND   peca    = $peca";
	$sqlx .= "<br><br>".$sql;
								$res = @pg_exec($con,$sql);
								$msg_erro = pg_errormessage($con);

								if (strlen($msg_erro) == 0) {
									$qtde_lbm = @pg_result ($res,0,0);
									$msg_erro = pg_errormessage($con);
								}
								if ($qtde_lbm < $qtde_pedida) {
									$msg_erro = "A quantidade de peça(s) $xreferencia, excede a quantidade de peças que o produto $xproduto_referencia de série $xproduto_serie possui.";
								}
							}
						}else{
							if (strlen (trim ($qtde)) > 0 AND $qtde > 0) {
								$msg_erro = "Item $referencia não existe, Consulte a vista explodida atualizada e verifique o código correto.";
							}else{
								$msg_erro = "Favor informar a quantidade para o item $referencia.";
							}
						}
					}

					if (strlen ($msg_erro) > 0) {
						$erro_linha = "erro_linha" . $i;
						$$erro_linha = 1 ;
						break ;
					}
				}
			}
		}

		if (strlen($msg_erro) == 0) {
			$sql = "SELECT fn_finaliza_pedido_blacktest ($cook_pedido,$login_fabrica)";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
		
		if (strlen($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			header ("Location: $PHP_SELF");
			exit;
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
}

$title     = "Pedido de Peças";
$cabecalho = "Pedido de Peças";

$layout_menu = 'pedido';
include "cabecalho.php";
//echo $sqlx;
?>

<!-- ---------------------- Inicio do HTML -------------------- -->

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 0px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line1 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}

</style>

<?
if ($alterar == 1) {
?>

<table width="400" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td align="center" class="table_line1">
		<b>Antes de lançar Pedido ou OS´s, por favor, <a href='cad_posto.php'>clique aqui</a> <br>e complete seu CNPJ e Inscrição Estadual</b>
	</td>
</tr>
</table>

<?}else{?>
<script language='javascript' src='ajax.js'></script>
<script LANGUAGE="JavaScript">
function FuncPesquisaPeca (peca_referencia, peca_descricao, peca_qtde, produto) {
	var url = "";
	if (peca_referencia.value != "") {
		url = "peca_pesquisa_lista_basica_blackedecker.php?peca=" + peca_referencia.value + "&produto=" + produto.value;
		janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=501,height=400,top=50,left=100");
		janela.referencia	= peca_referencia;
		janela.descricao	= peca_descricao;
		janela.qtde			= peca_qtde;
		janela.focus();
	}
}

function fnc_fora_linha (nome, seq) {
	var url = "";
	if (nome != "") {
		url = "pesquisa_fora_linha.php?nome=" + nome + "&seq=" + seq;
		janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
		janela.focus();
	}
}


function fnc_pesquisa_locacao (produto_serie) {
	var url = "";

	url = "pesquisa_locacao.php?serie=" + produto_serie.value;
	if (produto_serie.value.length >= 3) {
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
		janela.produto      = document.frmpedido.produto;
		janela.descricao    = document.frmpedido.produto_descricao;
		janela.referencia   = document.frmpedido.produto_referencia;
		janela.voltagem     = document.frmpedido.produto_voltagem;
		janela.tipo         = document.frmpedido.produto_tipo;
		janela.nota_fiscal  = document.frmpedido.nota_fiscal;
		janela.data_emissao = document.frmpedido.data_emissao;
	}else{
		alert("Digite pelo menos 3 caracteres!");
	}
}

function abrir_lista_basica(URL) {
	var width = 800;
	var height = 600;
	
	var left = 99;
	var top = 99;

	window.open(URL,'janela', 'width='+width+', height='+height+', top='+top+', left='+left+', scrollbars=yes, status=no, toolbar=no, location=no, directories=no, menubar=no, resizable=no, fullscreen=no');
}


<!-- Início 
nextfield = "condpgto"; // coloque o nome do primeiro campo do form 
netscape = "";
ver = navigator.appVersion; len = ver.length;
for(iln = 0; iln < len; iln++) if (ver.charAt(iln) == "(") break;
netscape = (ver.charAt(iln+1).toUpperCase() != "C");

function keyDown(DnEvents) {
	// ve quando e o netscape ou IE 
	k = (netscape) ? DnEvents.which : window.event.keyCode; 
	if (k == 13) { // preciona tecla enter
		if (nextfield == 'done') {
			return true; // envia quando termina os campos 
		} else {
			// se existem mais campos vai para o proximo
			eval('document.frmpedido.' + nextfield + '.focus()'); 
			return false; 
		}
	}
}

document.onkeydown = keyDown; // work together to analyze keystrokes 
if (netscape) document.captureEvents(Event.KEYDOWN|Event.KEYUP); 
// Fim --> 

</script>

<? include "javascript_pesquisas.php" ?>

<? if ($tipo_posto == 39) { ?>

<!--
<table width="450" border="1" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td bgcolor="#FFCCCC" align='left' nowrap>
		<font face='arial' size='-1' color='#FF0000'><b>PROJETO DE UNIIFICAÇÃO DE PEDIDOS em vigor apartir de 27/09/2004</b>.</font>
	</td>
</tr>
<tr>
	<td bgcolor="#FFFFFF" align='center'>
		<font face='arial' size='-1' color='#000000'>Objetivando melhor atendê-lo, foi desenvolvido o <b>Projeto de Unificação de Pedidos</b>, que possui como principal objetivo eliminar problemas relativos ao controle de pendências, melhor controle no faturamento e embarque de pedidos.&nbsp;&nbsp;&nbsp;<? if ($posto <> "23513" and $posto <> "20741" and $posto <> "21957") { ?><a href='http://cebolinha.telecontrol.com.br/bd/xls/procedimento_distribuidor.doc' target='_blank'>Ler Procedimentos</a>&nbsp;&nbsp;&nbsp;<a href='http://cebolinha.telecontrol.com.br/bd/xls/calendario_2004.xls' target='_blank'>Ver Calendário Fiscal</a><? } ?></font>
	</td>
</tr>
</table>

<p><p>
-->

<? } ?>

<? if ($tipo_posto == 38) { ?>

<!--
<table width="450" border="1" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td bgcolor="#FFCCCC" align='left' nowrap>
		<font face='arial' size='-1' color='#FF0000'><b>PROJETO DE UNIIFICAÇÃO DE PEDIDOS EM VIGOR A PARTIR DE 27/09/2004</b>.</font>
	</td>
</tr>
<tr>
	<td bgcolor="#FFFFFF" align='center'>
		<font face='arial' size='-1' color='#000000'>Objetivando melhor atendê-lo, foi desenvolvido o <b>Projeto de Unificação de Pedidos</b>, que possui como principal objetivo eliminar problemas relativos ao controle de pendências, melhor controle no faturamento e embarque de pedidos.&nbsp;&nbsp;&nbsp;<a href='http://cebolinha.telecontrol.com.br/bd/xls/procedimento_vip.doc' target='_blank'>Ler Procedimentos</a></font>
	</td>
</tr>
</table>

<br>
-->

<? } ?>

<?
if (strlen($cook_pedido) > 0) {
	echo "<br>";
	echo "<div class='contentBlockMiddle' style='width: 600px'>";
	echo "<table border='0' cellpadding='0' cellspacing='0'>";
	echo "<tr>";
	echo "<td><img border='0' src='imagens/esclamachion1.gif'></td>";
	echo "<td align='center'><b>Para que o pedido $pedido_blackedecker seja enviado para a fábrica, grave e finalize o pedido novamente, antes de sair da tela de digitação de pedidos.</b></td>";
	echo "</tr>";
	echo "</table>";
	echo "</div>";
}
?>

<br>

<?
if (strlen ($msg_erro) > 0) {
	if (strpos ($msg_erro,"Fail to add null value in not null attribute peca") > 0)
		$msg_erro = "Peça não existe";
?>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center">
<tr>
	<td valign="middle" align="center" class='error'>
		<? echo $msg_erro ?>
	</td>
</tr>
</table>
<br>
<? } ?>

<form name="frmpedido" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="linha" value="<?echo $linha?>">

<?
//Chamado: 1757
$fiscal = fopen("bloqueio_pedidos/periodo_fiscal.txt", "r");
$ler_fiscal = fread($fiscal, filesize("bloqueio_pedidos/periodo_fiscal.txt"));
fclose($fiscal); 
?>

<table width="500" border="0" cellpadding="2" cellspacing="1" align="center">
<tr>
	<td colspan='2' align='center'><font face='verdana' size='2' color='#0000ff'><b>Data limite para colocação de pedidos neste mês:<br><font color='#ff0000'><? echo $ler_fiscal; ?></font></font></td>
</tr>
</table>
<p>

<table width="500" border="0" cellpadding="2" cellspacing="1" align="center">
<tr>
	<td class='menu_top' colspan='2'>INFORMAÇÕES IMPORTANTES</td>
</tr>


<tr class='table_line1' bgcolor='#F1F4FA'>
	<td>*** ENVIAR PEDIDOS VIA ARQUIVO</td>
	<td align='center'><b><a href='pedido_upload.php'>Clique aqui</a></b></td>
</tr>
</table>

<br>

<? if ($cook_pedido > 0) { ?>
<table width="700" border="0" cellpadding="5" cellspacing="2" align="center">
<tr>
	<td align="center" width="100%" class="table_line1" bgcolor='#f4f4f4'>
		<p align='justify'><font size=1>
		<font color='#FF0000'><b>O SEU PEDIDO NÚMERO</b>: <b><? echo $pedido_blackedecker ?> SERÁ EXPORTADO NA SEGUNDA/QUARTA/SEXTA-FEIRA ÀS 11h45</font>, SE NECESSÁRIO, INCLUA OS ITENS FALTANTES E FINALIZE NOVAMENTE. SE O PEDIDO NÃO FOR FINALIZADO APÓS A INCLUSÃO DE NOVOS ITENS, SERÁ EXPORTADO PARA A BLACK & DECKER APENAS O PEDIDO FINALIZADO INICIALMENTE</b>.<br>
		</font></p>
		<?// HD 27498?>
	</td>
</tr>
</table>
<? } ?>

<br>


<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td align='left' class="menu_top">
		<b>Posto</b>
	</td>

	<td align='left' class="menu_top">
		<b>Razão Social</b>
	</td>

	<td align='left' class="menu_top">
		<b>Cond. Pgto.</b>
	</td>
</tr>
<tr>
	<td align='center' class="table_line1" valign='top'>
		<b><? echo $codigo_posto; ?></b>
	</td>
	<td align='left' class="table_line1" valign='top'>
		<b><? echo $nome_posto; ?></b>
	</td>
	<td align='center' nowrap class="table_line1" valign='top'>
		<select name="condicao" class="frm" onFocus="nextfield ='referencia0'">
			<option value='1639' selected>GARANTIA</option>
		</select>
	</td>
</tr>
</table>

<br>

<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td align='left' class="menu_top">
		<b>N. Série do Produto</b>
	</td>

	<td align='left' class="menu_top">
		<b>Referência</b>
	</td>

	<td align='left' class="menu_top">
		<b>Descrição</b>
	</td>

	<td align='left' class="menu_top">
		<b>Voltagem</b>
	</td>

	<td align='left' class="menu_top">
		<b>Tipo</b>
	</td>

	<td align='left' class="menu_top">
		<b>N. Fiscal</b>
	</td>

	<td align='left' class="menu_top">
		<b>Emissão</b>
	</td>
</tr>
<tr>
	<td align='center' class="table_line1" valign='top' nowrap>
		<input class="frm" type="text" name="produto_serie" size="15" maxlength="30" value="<? echo $produto_serie ?>">&nbsp;<img src='imagens/btn_lupa_novo.gif' style="cursor:pointer" border='0' alt="Clique para pesquisar pelo número de série do produto" align='absmiddle' onclick="javascript: document.frmpedido.produto.value = ''; document.frmpedido.produto_referencia.value = ''; document.frmpedido.produto_descricao.value = ''; document.frmpedido.produto_voltagem.value = ''; document.frmpedido.nota_fiscal.value = ''; document.frmpedido.data_emissao.value = ''; fnc_pesquisa_locacao (document.frmpedido.produto_serie)">
	</td>
	<td align='left' class="table_line1" valign='top'>
		<input class="frm" type="hidden" name="produto" value="<? echo $produto ?>">
		<input class="frm" type="text" name="produto_referencia" size="13" value="<? echo $produto_referencia ?>" readonly>
	</td>
	<td align='left' class="table_line1" valign='top'>
		<input class="frm" type="text" name="produto_descricao" size="40%" value="<? echo $produto_descricao ?>" readonly>
	</td>
	<td align='left' class="table_line1" valign='top'>
		<input class="frm" type="text" name="produto_voltagem" size="10" value="<? echo $produto_voltagem ?>" readonly>
	</td>
	<td align='left' class="table_line1" valign='top'>
		<input class="frm" type="text" name="produto_tipo" size="10" value="<? echo $produto_tipo ?>" readonly>
	</td>
	<td align='left' class="table_line1" valign='top'>
		<input class="frm" type="text" name="nota_fiscal" size="7" value="<? echo $nota_fiscal ?>" readonly>
	</td>
	<td align='left' class="table_line1" valign='top'>
		<input class="frm" type="text" name="data_emissao" size="13" value="<? echo $data_emissao ?>" readonly>
	</td>
</tr>
</table>

<br>
<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
	<tr>
		<td align='center' class="menu_top">
			<b>Referência</b>
		</td>
		<td align='center' class="menu_top">
			<acronym title="Clique para abrir a lista básica do produto."><a href="javascript:abrir_lista_basica('peca_consulta_por_produto.php?produto='+window.document.frmpedido.produto.value+'&tipo='+window.document.frmpedido.produto_tipo.value);">Lista Básica</a></acronym>
		</td>
		<td align='center' class="menu_top">
			<b>Descrição</b>
		</td>
		<td align='center' class="menu_top">
			<b>Quantidade</b>
		</td>
	</tr>

	<? for ($i = 0 ; $i < 15 ; $i ++) {
		$referencia = "referencia" . $i;
		$descricao  = "descricao" . $i;
		$qtde       = "qtde" . $i;
		$erro_linha = "erro_linha" . $i;

		$referencia = $$referencia;
		$descricao  = $$descricao;
		$qtde       = $$qtde;
		$erro_linha = $$erro_linha;

		$prox = $i + 1;
		$done = 14;

		$cor_erro = "#FFFFFF";
		if ($erro_linha == 1) $cor_erro = "#AA6666";
		?>

		<tr bgcolor="<?echo $cor_erro?>">
			<td align='center' nowrap>
				<input type="text" name="referencia<?echo $i?>" size="23%" maxlength="15" value="<?echo $referencia?>" class="textbox" onFocus="nextfield ='qtde<?echo $i?>'">
			</td>
			<td>
				<img src='imagens/btn_lupa.gif' style="cursor:pointer" alt="Clique para pesquisar por referência do componente" border='0' hspace='5' align='absmiddle' onclick="javascript: FuncPesquisaPeca(window.document.frmpedido.referencia<?echo $i?>,window.document.frmpedido.descricao<?echo $i?>,window.document.frmpedido.qtde<?echo $i?>,window.document.frmpedido.produto);">
			</td>
			<td align='center' nowrap>
				<input type="text" name="descricao<?echo $i?>" size="75%" value="<?echo $descricao?>" class="textbox" onFocus="nextfield ='qtde<?echo $i?>'">
			</td>
			<td align='center' nowrap>
				<input type="text" name="qtde<?echo $i?>" size="10%" maxlength="4" value="<?echo $qtde?>" class="textbox" onFocus="nextfield ='produto<?echo $i?>'">
			</td>
		</tr>
	<? } ?>
</table>

<br>

<input type="hidden" name="btngravar" value="x">
<center><a href="javascript: document.frmpedido.btngravar.value='Gravar' ; document.frmpedido.submit() ; "><img src='imagens/btn_gravar.gif' border='0'></a></center>

<br>

<table width="700" border="0" cellpadding="5" cellspacing="2" align="center">
<tr>
	<td align='center' bgcolor='#f4f4f4'>
		<p align='justify'><font size='1'><b>PARA CONTINUAR A DIGITAR ITENS NESTE PEDIDO, BASTA GRAVAR E EM SEGUIDA CONTINUAR DIGITANDO.</b></font></p>
	</td>
</tr>
<tr>
	<td align='center' bgcolor='#f4f4f4'>
		<p align='justify'><font size='1' color='#FF0000'><b>AVISO: APÓS GRAVAR O SEU PEDIDO, IRÁ APARECER O RESUMO DOS ITENS LANÇADOS E ABAIXO DESTE RESUMO, TERÁ O BOTÃO DE FINALIZAÇÃO QUE SOMENTE SERÁ USADO QUANDO NÃO EXISTIREM MAIS ITENS A SEREM LANÇADOS NESTE PEDIDO.</b></font></p>
	</td>
</tr>

</form>
</table>

</form>

<? if (strlen ($cook_pedido) > 0) { ?>
	<br>
	<table width="700" border="0" cellpadding="3" cellspacing="1" align="center">
		<tr>
			<td colspan="6" align="center" class='menu_top'>
				<font face="arial" color="#ffffff" size="+2"><b>Resumo do Pedido</b></font>
			</td>
		</tr>

		<tr>
			<td width="25%" align='center' class="menu_top">
				<b>Referência</b>
			</td>
			<td width="50%" align='center' class="menu_top">
				<b>Descrição</b>
			</td>
			<td width="15%" align='center' class="menu_top">
				<b>Quantidade</b>
			</td>
			<td width="10%" align='center' class="menu_top">
				<b>Preço</b>
			</td>
			<td width="10%" align='center' class="menu_top">
				<b>Produto</b>
			</td>
			<td width="10%" align='center' class="menu_top">
				<b>Série</b>
			</td>
		</tr>
	
		<?
		$sql = "SELECT	a.oid    ,
						a.*      ,
						referencia,
						descricao
				FROM	tbl_peca
				JOIN	(
							SELECT	oid,*
							FROM	tbl_pedido_item
							WHERE	pedido = $cook_pedido
				) a ON tbl_peca.peca = a.peca
				ORDER BY a.pedido_item";
		$res = pg_exec ($con,$sql);
		$total = 0;

		for ($i = 0 ; $i < @pg_numrows ($res) ; $i++) {
			$produto       = pg_result($res,$i,produto_locador);
			$serie_locador = pg_result($res,$i,serie_locador);
			if(strlen($produto)>0){ 
				$sql = "SELECT  tbl_produto.referencia,
								tbl_produto.descricao
						FROM    tbl_produto
						WHERE   tbl_produto.produto = $produto";
				$resx = pg_exec ($con,$sql);
				if (pg_numrows($resx) > 0) {
					$produto_referencia = pg_result($resx,0,referencia);
					$produto_descricao  = pg_result($resx,0,descricao);
				}
			}
			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

			echo "<tr bgcolor='$cor'>";
			echo "<td width='25%' align='left' class='table_line1' nowrap>";

			echo "<a href='$PHP_SELF?delete=" . pg_result ($res,$i,pedido_item) . "&pedido=$cook_pedido'>";

			echo "<img src='imagens/btn_excluir.gif' align='absmiddle' hspace='5' border='0'>";
			echo "</a>";
			echo pg_result ($res,$i,referencia);
			echo "</td>";

			echo "<td width='50%' align='left' class='table_line1'>";
			echo pg_result ($res,$i,descricao);
			echo "</td>";

			echo "<td width='15%' align='center' class='table_line1'>";
			echo pg_result ($res,$i,qtde);
			echo "</td>";

			echo "<td width='10%' align='right' class='table_line1'>";
			echo number_format (pg_result ($res,$i,preco),2,",",".");
			echo "</td>";

			echo "<td nowrap align='left' class='table_line1'>";
			echo $produto_referencia ."-". $produto_descricao;
			echo "</td>";

			echo "<td nowrap align='left' class='table_line1'>";
			echo $serie_locador;
			echo "</td>";

			echo "</tr>";
			
			$total = $total + (pg_result ($res,$i,preco) * pg_result ($res,$i,qtde));
		}
		?>

		<tr>
			<td align="center" colspan="3" class="menu_top">
				<b>T O T A L</b>
			</td>
			<td align='right' class="menu_top" style='text-align:right'>
				<b>
				<? echo number_format ($total,2,",",".") ?>
				</b>
			</td>
			<td align="center" class="menu_top" colspan="2"></td>
		</tr>
	</table>

	<!-- ============================ Botoes de Acao ========================= -->


	<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
	<tr>
		<td align='center'>
		<?
		$sql = "SELECT	*
				FROM	tbl_status_pedido
				LEFT JOIN tbl_pedido USING (status_pedido)
				LEFT JOIN tbl_faturamento USING(pedido)
				WHERE	tbl_pedido.posto = $login_posto
				AND		tbl_pedido.fabrica = $login_fabrica
				AND		tbl_pedido.pedido = $cook_pedido
				AND		tbl_status_pedido.status_pedido IN (4,5)";
		$res = @pg_exec ($con,$sql);

		if (@pg_numrows($res) > 0){
			$link  = "javascript:PedidoPendente();";
			echo "
					<script>
					function PedidoPendente(){
						if(confirm('UNIFICAÇÃO DOS PEDIDOS.\\n\\nDeseja somar as pendências do pedido ".trim(pg_result($res,0,pedido_mfg))." neste novo pedido ?\\n\\nPara confirmar clique em \"OK\",\\ncaso contrário, clique em \"Cancelar\".') == true){
							window.location = '$PHP_SELF?finalizar=1&unificar=t&msg=1';
						}else{
							if(confirm('A pendência, após a finalização do seu novo pedido, será cancelada.\\n\\nConfirma a exclusão da pendência ?\\n\\nPara confirmar clique em \"OK\",\\ncaso contrário, clique em \"Cancelar\".') == true){
								window.location = '$PHP_SELF?finalizar=1&unificar=f&msg=2';
							}
						}
					}
					</script>\n";
		}else{
			$link = "$PHP_SELF?finalizar=1&linha=$linha&unificar=t";
		}
		?>
		<br><a href="<? echo $link; ?>"><img src='imagens/btn_finalizar.gif' border='0'></a><br><br>
		</td>
	</tr>
	<tr>
		<td align='center' bgcolor='#f4f4f4'>
			<p align='justify'><font size='1'><b>CASO JÁ TENHA TERMINADO DE DIGITAR OS ITENS E QUEIRA PASSAR PARA A PRÓXIMA TELA, CLIQUE EM FINALIZAR ACIMA.</b></font></p>
		</td>
	</tr>
	</table>
<?
}
?>


<? } # Final do IF do CNPJ e IE ?>

<p>

<?include "rodape.php";?>

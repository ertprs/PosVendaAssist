<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';


// ALTERAÇÃO DE TABELA DE PREÇOS
if (1 == 1) {
	if (strval(strtotime(date("Y-m-d H:i:s"))) < strval(strtotime("2005-09-30 08:00:00"))) { // DATA DA VOLTA
		if (strval(strtotime(date("Y-m-d H:i:s"))) >= strval(strtotime("2005-09-27 13:45:00"))) { // DATA DO BLOQUEIO
			//OR date("d/m/Y H:m:s") < "02/01/2004 00:00:00") {
			$title     = "Pedido de Peças";
			$cabecalho = "Pedido de Peças";
			$layout_menu = 'os';
		
			include "cabecalho.php";
			
			echo "<br><br>\n";
			
			echo "<table width='650' border='0' cellpadding='2' cellspacing='2' align='center'>\n";
			echo "<tr>\n";
			
			echo "<td width='100%' align='center'>\n";
			echo "<h4><b>";
			echo "Devido ao nosso fechamento mensal, conforme Calendário Fiscal disponível, o site ficará travado para a digitação de pedidos retornando no dia  30/09 às 8 horas. 
				<br><br>
				Salientamos que os pedidos finalizados após esta data, serão enviados para a fábrica somente na segunda-feira dia  03/10 . 
				<br><br>
				 Agradecemos a compreensão.
				<br>
				Faturamento de Peças.
				";
			echo "</b></h3>\n";
			echo "</td>\n";
			
			echo "</tr>\n";
			echo "</table>\n";
			
			include "rodape.php";
			exit;
		}
	}
}

$sql = "SELECT  tbl_posto_fabrica.codigo_posto      ,
				tbl_posto_fabrica.tipo_posto        ,
				tbl_posto_fabrica.pedido_faturado   ,
				tbl_posto_fabrica.pedido_em_garantia,
				tbl_posto.cnpj                      ,
				tbl_posto.ie                        ,
				tbl_posto.nome                      ,
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

	echo "
	<style type=\"text/css\">
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
			JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = 1
			WHERE   tbl_posto_fabrica.codigo_posto not in ('1122', '23513', '20741', '21957')
			AND     ( tbl_posto_fabrica.tipo_posto = 39 OR tbl_posto_fabrica.tipo_posto = 40 )
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
			echo "<td align='center' class='menu_top'><b>eMail</b></td>\n";
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

$sql = "SELECT  tbl_pedido.pedido                                              ,
				lpad(tbl_pedido.pedido_blackedecker,5,0) AS pedido_blackedecker,
				suframa.pedido_suframa
		FROM    tbl_pedido
		LEFT JOIN tbl_pedido suframa on suframa.pedido_suframa = tbl_pedido.pedido
		WHERE   tbl_pedido.exportado isnull
		AND     tbl_pedido.natureza_operacao <> 'SN-GART'
		AND     tbl_pedido.natureza_operacao <> 'VN-REV'
		AND     tbl_pedido.tabela            = 31
		AND     tbl_pedido.posto = $login_posto;";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	$pedido_suframa      = trim(pg_result($res,0,pedido_suframa));
	$cook_pedido         = trim(pg_result($res,0,pedido));
	$pedido_blackedecker = trim(pg_result($res,0,pedido_blackedecker));
	
	setcookie ("cook_pedido",$cook_pedido,time()+(3600*48));
}


if (strlen($cook_pedido) > 0) {
	$sql = "SELECT  tbl_condicao.condicao,
					tbl_pedido.bloco_os
			FROM    tbl_pedido
			JOIN    tbl_condicao ON tbl_condicao.condicao = tbl_pedido.condicao
			WHERE   tbl_pedido.pedido = $cook_pedido";
	$res = @pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$condicao = trim(pg_result($res,0,condicao));
		$bloco_os = trim(pg_result($res,0,bloco_os));;
	}
}


if (strlen($cook_pedido) > 0 and strlen($btngravar) == 0 and strlen($finalizar) == 0) {
	$res = pg_exec($con, "BEGIN TRANSACTION");

	if (strlen($pedido_suframa) > 0) {
		$sql = "INSERT INTO tbl_pedido_item (
					pedido,
					peca  ,
					qtde  ,
					preco
			)
			SELECT  $cook_pedido          ,
					tbl_pedido_item.peca  ,
					tbl_pedido_item.qtde  ,
					tbl_pedido_item.preco
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

#----------------------- Deletar Item ------------------
if (strlen($_GET["delete"]) > 0) {
	$delete = trim($_GET["delete"]);
	
	$sql = "DELETE FROM tbl_pedido_item
			WHERE  tbl_pedido_item.pedido_item = $delete";
	$res = @pg_exec ($con,$sql);
	
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
		$sql = "SELECT tbl_pedido.pedido
				FROM   tbl_pedido
				WHERE  tbl_pedido.exportado isnull
				AND    tbl_pedido.pedido = $cook_pedido;";
		$res = @pg_exec ($con,$sql);
		
		if (pg_numrows($res) == 0) {
			$msg_erro = "Pedido não pode ser mais alterado pois já foi exportado.";
			setcookie ("cook_pedido");
			$cook_pedido = "";
		}
		
		if (strlen($msg_erro) == 0) {
			$sql = "UPDATE tbl_pedido SET
						unificar_pedido = '$unificar'
					WHERE  tbl_pedido.pedido = $cook_pedido
					AND    tbl_pedido.unificar_pedido isnull;";
			$res = @pg_exec ($con,$sql);
			
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro = pg_errormessage ($con) ;
			}
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
		$sql = "SELECT fn_pedido_finaliza ($cook_pedido,$login_fabrica)";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen($msg_erro) == 0) {
		$sql = "SELECT fn_pedido_suframa($cook_pedido,$login_fabrica);";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	$sql = "SELECT tbl_pedido.pedido
			FROM   tbl_pedido
			JOIN   tbl_condicao ON tbl_condicao.condicao = tbl_pedido.condicao
			WHERE  tbl_pedido.pedido = $cook_pedido
			AND    tbl_pedido.total <= 200
			AND    trim(tbl_condicao.codigo_condicao) <> '15'
			AND    trim(tbl_condicao.codigo_condicao) <> '30'
			AND    trim(tbl_condicao.codigo_condicao) <> '60'
			AND    trim(tbl_condicao.codigo_condicao) <> '90';";
	$res = @pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) $msg_erro = "
	<table width='650' border='0' cellpadding='2' cellspacing='2' align='center'>
	<tr>
	<td align='left'>
	<font face='Verdana, Arial' size='2' color='#FFFFFF'>
	<b>Pedidos de valor até R$ 200,00 gerarão parcela única, sendo disponível estas opções</b>:<br>
	<UL>
		<LI>À VISTA ou 30 dias direto (sem taxa financeira);
		<LI>60 dias direto (3%);
		<LI>90 dias direto (6,10%)
	</UL>
	<br><center>Favor alterar a condição de pagamento e clicar em gravar.</center><br><br>
	</font>
	</td>
	</tr>
	</table>";
	
	if (strlen($msg_erro) == 0) {
		$sql = "SELECT tbl_pedido.pedido
				FROM   tbl_pedido
				JOIN   tbl_condicao ON tbl_condicao.condicao = tbl_pedido.condicao
				WHERE  tbl_pedido.pedido = $cook_pedido
				AND    tbl_pedido.total >  200
				AND    tbl_pedido.total <= 400
				AND    trim(tbl_condicao.codigo_condicao) <> '15'
				AND    trim(tbl_condicao.codigo_condicao) <> '30'
				AND    trim(tbl_condicao.codigo_condicao) <> '47'
				AND    trim(tbl_condicao.codigo_condicao) <> '60'
				AND    trim(tbl_condicao.codigo_condicao) <> '76'
				AND    trim(tbl_condicao.codigo_condicao) <> '90';";
		$res = @pg_exec ($con,$sql);
		
		if (pg_numrows($res) > 0) $msg_erro = "
		<table width='650' border='0' cellpadding='2' cellspacing='2' align='center'>
		<tr>
		<td align='left'>
		<font face='Verdana, Arial' size='2' color='#FFFFFF'>
		<b>Pedidos acima de R$ 200,00 e até R$ 400,00 gerarão duas parcelas, sendo disponível estas opções</b>:<br>
		<UL>
			<LI>30/60 dias (1,5%);
			<LI>60/90 dias (4,5%);
		</UL>
		e/ou
		<br>
		<UL>
			<LI>À VISTA ou 30 dias direto (sem taxa financeira);
			<LI>60 dias direto (3%);
			<LI>90 dias direto (6,10%)
		</UL>
		<br><center>Favor alterar a condição de pagamento e clicar em gravar.</center><br><br>
		</font>
		</td>
		</tr>
		</table>";
	}
	
	if (strlen($msg_erro) == 0) {
		$sql = "SELECT tbl_pedido.pedido
				FROM   tbl_pedido
				JOIN   tbl_condicao ON tbl_condicao.condicao = tbl_pedido.condicao
				WHERE  tbl_pedido.pedido = $cook_pedido
				AND    tbl_pedido.total > 400
				AND    trim(tbl_condicao.codigo_condicao) <> '15'
				AND    trim(tbl_condicao.codigo_condicao) <> '30'
				AND    trim(tbl_condicao.codigo_condicao) <> '47'
				AND    trim(tbl_condicao.codigo_condicao) <> '60'
				AND    trim(tbl_condicao.codigo_condicao) <> '62'
				AND    trim(tbl_condicao.codigo_condicao) <> '76'
				AND    trim(tbl_condicao.codigo_condicao) <> '90'
				AND    trim(tbl_condicao.codigo_condicao) <> '191';";
		$res = @pg_exec ($con,$sql);
		
		if (pg_numrows($res) > 0) $msg_erro = "Pedidos acima de R$ 400,00 gerarão três parcelas, sendo disponível estas opções:<br>
		<UL>
			<LI>30/60/90 dias (3%);
			<LI> 60/90/120 dias (6,10%);
		</UL>
		e/ou
		<br>
		<UL>
			<LI>À VISTA ou 30 dias direto (sem taxa financeira);
			<LI>60 dias direto (3%);
			<LI>90 dias direto (6,10%)
		</UL>
		e/ou
		<UL>
			<LI>30/60 dias (1,5%);
			<LI>60/90 dias (4,5%);
		</UL>
		<br>
		<br>Favor alterar a condição de pagamento e clicar em gravar.<br><br>";
	}
	
	if (strlen($msg_erro) == 0) {
		$msg = $_GET['msg'];
		header ("Location: pedido_finalizado.php?msg=".$msg);
		exit;
	}
}

#--------------- Gravar Item ----------------------
if ($btngravar == "Gravar") {
	$condicao = trim($_POST['condicao']);
#	$bloco_os = intval(trim($_POST['bloco_os']));
	$bloco_os = 0;

	if (strlen($bloco_os) == 0) {
		$aux_bloco_os = 0;
	}else{
		if (is_int($bloco_os) == false) {
			$aux_bloco_os = 0;
		}else{
			$fnc          = pg_exec($con,"SELECT fnc_so_numeros('$bloco_os')");
			$aux_bloco_os = pg_result ($fnc,0,0);
		}
	}
	
	if (strlen($msg_erro) == 0) {
		if (strlen ($condicao) == 0 AND strlen ($cook_pedido)== 0) {
			$msg_erro = "Escolha a condição de pagamento";
		}
	}
	
	$sql = "SELECT tbl_tabela.tabela
			FROM   tbl_tabela
			WHERE  tbl_tabela.sigla_tabela = 'BASE1'";
	$res = @pg_exec ($con,$sql);
	if (pg_numrows ($res) > 0) $tabela = pg_result ($res,0,0);
	
	if (strlen($msg_erro) == 0){
		$res = pg_exec($con, "BEGIN TRANSACTION");
		
		if (strlen ($cook_pedido) == 0) {
			$sql = "INSERT INTO tbl_pedido (
						posto          ,
						condicao       ,
						tabela         ,
						bloco_os       ,
						fabrica        ,
						tipo_pedido    ,
						unificar_pedido
					)VALUES(
						$login_posto  ,
						'$condicao'   ,
						'$tabela'     ,
						$aux_bloco_os ,
						$login_fabrica,
						(SELECT tipo_pedido FROM tbl_tipo_pedido WHERE fabrica = $login_fabrica AND UPPER(trim(descricao)) = 'FATURADO' ),
						't'
					)";
			$res = @pg_exec ($con,$sql);
			
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro = pg_errormessage ($con) ;
			}else{
				$res = pg_exec ($con,"SELECT currval ('seq_pedido')");
				$cook_pedido = pg_result ($res,0,0);
				
				# cookie expira em 48 horas
				setcookie ("cook_pedido",$cook_pedido,time()+(3600*48));
			}
		}else{
			$sql = "SELECT tbl_pedido.pedido
					FROM   tbl_pedido
					WHERE  tbl_pedido.exportado isnull
					AND    tbl_pedido.pedido = $cook_pedido;";
			$res = @pg_exec ($con,$sql);
			
			if (pg_numrows($res) == 0) {
				$msg_erro = "Pedido não pode ser mais alterado pois já foi exportado.";
				setcookie ("cook_pedido");
				$cook_pedido = "";
			}
			
			if (strlen($msg_erro) == 0) {
				$sql = "UPDATE tbl_pedido SET
							tabela     = '$tabela'       ,
							condicao   = '$condicao'     ,
							bloco_os   = '$aux_bloco_os' ,
							finalizado = null
						WHERE tbl_pedido.pedido = $cook_pedido;";
				$res = @pg_exec ($con,$sql);
				
				if (strlen ( pg_errormessage ($con) ) > 0) {
					$msg_erro = pg_errormessage ($con) ;
				}
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
				$referencia = trim($_POST["referencia" . $i]);
				$qtde       = trim($_POST["qtde"       . $i]);
				
				$xreferencia = str_replace(" ","",$referencia);
				$xreferencia = str_replace(".","",$xreferencia);
				$xreferencia = str_replace("-","",$xreferencia);
				$xreferencia = str_replace("/","",$xreferencia);
				
				if (strlen($referencia) > 0) {
					$sql = "SELECT tbl_peca.peca
							FROM   tbl_peca
							WHERE  tbl_peca.referencia_pesquisa = '$xreferencia'";
					$resX = pg_exec ($con,$sql);
					
					if (pg_numrows($resX) > 0 AND strlen (trim ($qtde)) > 0 AND $qtde > 0) {
						$peca = pg_result($resX,0,0);
						$sql = "INSERT INTO tbl_pedido_item (
								pedido,
								peca  ,
								qtde
							)VALUES(
								$cook_pedido,
								$peca       ,
								$qtde
							)";
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
						
						if (strlen ($msg_erro) > 0) {
							$erro_linha = "erro_linha" . $i;
							$$erro_linha = 1 ;
							break ;
						}
					}else{
						if (strlen (trim ($qtde)) > 0 AND $qtde > 0) {
							$msg_erro = "Item $referencia não existe, Consulte a vista explodida atualizada e verifique o código correto.";
						}else{
							$msg_erro = "Favor informar a quantidade para o item $referencia.";
						}
						
						if (strlen ($msg_erro) > 0) {
							$erro_linha = "erro_linha" . $i;
							$$erro_linha = 1 ;
							break ;
						}
					}
				}
			}
		}
		
		if (strlen($msg_erro) == 0) {
			$sql = "SELECT fn_finaliza_pedido_blackedecker ($cook_pedido,$login_fabrica)";
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

<script LANGUAGE="JavaScript">
function FuncPesquisaPeca (peca_referencia, peca_descricao, peca_qtde) {
	var url = "";
	if (peca_referencia.value != "") {
		url = "peca_pesquisa_lista_blackedecker.php?peca=" + peca_referencia.value;
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

<? } ?>

<? if ($tipo_posto == 38) { ?>

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

<p><p>

<? } ?>

<!--
<table width="450" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td bgcolor="#FFFFFF" align='center' nowrap>
		<font face='arial' size='-1' color='#000000'><b>Para enviar pedidos de peças via arquivo, <a href='pedido_blackedecker_upload.php'>CLIQUE AQUI</a> e siga as instruções</b>.</font>
	</td>
</tr>
</table>
-->

<!--
<p>
<? if ($pedido_em_garantia == "t") {?>
<table width="400" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td align="center" width="98%" class="f_<?echo $css;?>_10">
		<b>Para fazer pedidos Faturado,  <a href='cad_pedido.php'>Clique Aqui</a></b>
	</td>
</tr>
<tr>
	<td align="center" width="98%" class="f_<?echo $css;?>_10">
		<b>Para fazer pedidos em Garantia,  <a href='cad_pedido_garantia.php'>Clique Aqui</a></b>
	</td>
</tr>
</table>
<? } ?>
-->

<p>

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
<p>
<? } ?>

<form name="frmpedido" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="linha" value="<?echo $linha?>">


<table width="500" border="0" cellpadding="2" cellspacing="1" align="center">
<tr>
	<td class='menu_top' colspan='2'>INFORMAÇÕES IMPORTANTES</td>
</tr>
<? if ($tipo_posto == 39) { ?>
<tr class='table_line1' bgcolor='#F1F4FA'>
	<td>*** PROJETO DE UNIFICAÇÃO DOS PEDIDOS (DISTRIBUIDOR)</td>
	<td align='center'><b><a href='http://www.blackdecker.com.br/xls/procedimento_distribuidor.doc' target='_blank'>Clique aqui</a></b></td>
</tr>
<? } ?>

<? if ($pedido_em_garantia == "t") { ?>
<tr class='table_line1' bgcolor='#F1F4FA'>
	<td>*** PROJETO DE UNIFICAÇÃO DOS PEDIDOS (GARANTIA)</td>
	<td align='center'><b><a href='http://www.blackdecker.com.br/xls/procedimento_garantia.doc' target='_blank'>Clique aqui</a></b></td>
</tr>
<? } ?>

<tr class='table_line1' bgcolor='#F1F4FA'>
	<td>*** CALENDÁRIO FISCAL</td>
	<td align='center'><b><a href='http://www.blackdecker.com.br/xls/calendario_fechamento.xls' target='_blank'>Clique aqui</a></b></td>
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
		<font color='#FF0000'><b>O SEU PEDIDO NÚMERO</b>: <b><? echo $pedido_blackedecker ?> SERÁ EXPORTADO ÀS 13h55</font>, SE NECESSÁRIO, INCLUA OS ITENS FALTANTES E FINALIZE NOVAMENTE. SE O PEDIDO NÃO FOR FINALIZADO APÓS A INCLUSÃO DE NOVOS ITENS, SERÁ EXPORTADO PARA A BLACK & DECKER APENAS O PEDIDO FINALIZADO INICIALMENTE</b>.<br>
		</font></p>
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
<!--<td align='left' class="menu_top">
		<b>Bloco de Os's</b>
	</td>-->
</tr>
<tr>
	<td align='center' class="table_line1" valign='top'>
		<b><? echo $codigo_posto; ?></b>
	</td>

	<td align='left' class="table_line1" valign='top'>
		<b><? echo $nome_posto; ?></b>
	</td>

	<td align='center' nowrap class="table_line1" valign='top'>
		<select name="condicao" class="frm">
		<option value=''></option>
<?
			$sql = "SELECT   tbl_condicao.*
					FROM     tbl_condicao
					JOIN     tbl_posto_condicao USING (condicao)
					WHERE    tbl_posto_condicao.posto = $login_posto
					AND      tbl_condicao.fabrica     = $login_fabrica
					AND      tbl_condicao.visivel       IS TRUE
					AND      tbl_posto_condicao.visivel IS TRUE
					ORDER BY lpad(trim(tbl_condicao.codigo_condicao), 10, 0) ";
			$res = pg_exec ($con,$sql);

			for ($x=0; $x < pg_numrows($res); $x++) {
				echo "<option "; if ($condicao == pg_result($res,$x,condicao)) echo " SELECTED "; echo " value='" . pg_result($res,$x,condicao) . "'>" . pg_result($res,$x,descricao) . "</option>\n";
			}
?>
		</select>
		<?echo nl2br($sql)?>
		<br>
		<font face='arial' size='-2' color='#336633'><b>Favor escolher a condição de pagamento</b></font>
	</td>
<!--<td align='center' class="table_line1" valign='top'>
		<b><input type='text' name='bloco_os' value='<? echo $bloco_os; ?>' size=3 maxlength=3></b>
	</td>-->
</tr>
</table>

<br>

<table width="500" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td width="35%" align='center' class="menu_top">
		<b>Referência</b>
	</td>
	<td width="50%" align='center' class="menu_top">
		<b>Descrição</b>
	</td>
	<td width="15%" align='center' class="menu_top">
		<b>Quantidade</b>
	</td>
</tr>


<?
for ($i = 0 ; $i < 15 ; $i ++) {
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

	$cor_erro = "#ffffff";
	if ($erro_linha == 1) $cor_erro = "#AA6666";

?>

<!--<tr bgcolor="<?echo $cor_erro?>">
	<td align='center'>
		<input type="text" name="referencia<? echo $i ?>" onblur="javascript:fnc_fora_linha(this.value, <?echo $i?>)" size="15" maxlength="15" value="<? echo $referencia ?>" class="textbox" style="width:100px" onFocus="nextfield ='qtde<?echo $i?>'">
	</td>
	<td align='center'>
		<input type="text" name="qtde<? echo $i ?>" size="4" maxlength="4" value="<? echo $qtde ?>" class="textbox" style="width:40px " <? if ($prox <= $done) { echo "onFocus=\"nextfield ='referencia$prox'\""; }else{ echo "onFocus=\"nextfield ='done'\"";}?>>
	</td>
</tr>-->

<tr bgcolor="<?echo $cor_erro?>">
	<td align='center'>
		<input type="text" name="referencia<? echo $i ?>" size="15" maxlength="15" value="<? echo $referencia ?>" class="textbox" onFocus="nextfield ='qtde<?echo $i?>'">
		<img src='imagens/btn_buscar5.gif' style="cursor:pointer" alt="Clique para pesquisar por referência do componente" border='0' hspace='5' align='absmiddle' onclick="javascript: FuncPesquisaPeca(window.document.frmpedido.referencia<? echo $i ?>,window.document.frmpedido.descricao<? echo $i ?>,window.document.frmpedido.qtde<? echo $i ?>);">
	</td>
	<td align='center'>
		<input type="text" name="descricao<? echo $i ?>" size="30" maxlength="30" value="<? echo $descricao ?>" class="textbox" onFocus="nextfield ='qtde<?echo $i?>'">
		<!--<img src='imagens/btn_buscar5.gif' style="cursor:pointer" alt="Clique para pesquisar por descrição do componente" border='0' hspace='5' align='absmiddle' onclick="javascript: fnc_pesquisa_peca_lista ('',window.document.frmpedido.referencia<? echo $i ?>,window.document.frmpedido.descricao<? echo $i ?>,'','descricao')">-->
	</td>
	<td align='center'>
		<input type="text" name="qtde<? echo $i ?>" size="4" maxlength="4" value="<? echo $qtde ?>" class="textbox" <? if ($prox <= $done) { echo "onFocus=\"nextfield ='referencia$prox'\""; }else{ echo "onFocus=\"nextfield ='done'\"";}?>>
	</td>
</tr>

<? } ?>

<tr>
	<td align='center' colspan='2'>
		<br>
		<input type="hidden" name="btngravar" value="x">
		<!--
		<img src="imagens/gravar.gif" onclick="window.document.frmpedido.btngravar.value='1' ; frmpedido.submit() " >
		-->
		<a href="javascript: document.frmpedido.btngravar.value='Gravar' ; document.frmpedido.submit() ; "><img src='imagens/btn_gravar.gif' border='0'></a>
	</td>
</tr>
</table>
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


<?
if (strlen ($cook_pedido) > 0) {
?>
<br>
<table width="700" border="0" cellpadding="3" cellspacing="1" align="center">
<tr>
	<td colspan="4" align="center" class='menu_top'>
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
					)
					a ON tbl_peca.peca = a.peca
					ORDER BY a.pedido_item";
	$res = pg_exec ($con,$sql);
	$total = 0;
	for ($i = 0 ; $i < @pg_numrows ($res) ; $i++) {

		$cor = ($i % 2 == 0) ? '#F1F4FA' : "#F7F5F0";

		echo "<tr bgcolor='$cor'>";
		echo "<td width='25%' align='left' class='table_line1' nowrap>";

		echo "<a href='$PHP_SELF?delete=" . pg_result ($res,$i,pedido_item) . "'>";

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
</tr>
</table>



<!-- ============================ Botoes de Acao ========================= -->


<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td align='center'>
<?
/*
$sql = "SELECT	tbl_posicao_pedido.pedido_mfg , 
				to_char(tbl_posicao_pedido.data_pedido, 'DD/MM/YYYY') AS data_pedido, 
				(	
					SELECT	tbl_posicao_status.status 
					FROM	tbl_posicao_status 
					WHERE	tbl_posicao_pedido.pedido_mfg = tbl_posicao_status.pedido_mfg
					ORDER BY tbl_posicao_status.data_status DESC LIMIT 1 
				) AS status 
		FROM tbl_posicao_pedido
		WHERE	trim(tbl_posicao_pedido.codigo_posto) = '$posto'
		ORDER BY tbl_posicao_pedido.data_pedido DESC LIMIT 1";
*/
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
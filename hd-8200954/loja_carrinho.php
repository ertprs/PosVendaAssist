<?
#session_name("carrinho");
#session_start();
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$layout_menu = 'pedido';
$title = "BEM-VINDO a loja virtual da Britânia ";
include "cabecalho.php";

# PEGA O PEDIDO EM ABERTO QUE AINDA NÃO FOI EXPORTADO PARA O PA CONTINUAR A DIGITAR PEÇAS A ESTE PEDIDO
if (1==2){
	$sql = "SELECT  
				tbl_pedido.pedido,
				to_char(tbl_pedido.finalizado,'DD/MM/YYYY') as finalizado,
				to_char(tbl_pedido.data,'DD/MM/YYYY') as data,
				tbl_pedido.total,
				tbl_pedido.tabela,
				tbl_pedido.linha,
				tbl_pedido.condicao
		FROM  tbl_pedido
		WHERE tbl_pedido.pedido_loja_virtual IS TRUE
		AND   tbl_pedido.exportado IS NULL
		AND   tbl_pedido.posto   = $login_posto
		AND   tbl_pedido.fabrica = $login_fabrica
		ORDER BY tbl_pedido.pedido DESC LIMIT 1";
	$res = pg_exec ($con,$sql);
	if ( pg_numrows($res) > 0 AND 1==2 ) {
		for ($i=0;$i<pg_numrows($res);$i++){
			$Xpedido[$i]      = trim(pg_result($res,0,pedido));
			$Xfinalizado[$i]  = trim(pg_result($res,0,finalizado));
			$Xdata[$i]        = trim(pg_result($res,0,data));
			$Xtabela[$i]      = trim(pg_result($res,0,tabela));
			$Xlinha[$i]       = trim(pg_result($res,0,linha));
			$Xcondicao[$i]    = trim(pg_result($res,0,condicao));
		}
	}
}

$btn_acao    = $_GET['btn_acao'];
$acao        = $_GET['acao'];

if(strlen($acao)>0){

	if ($acao=="adicionar"){
		$peca        = $_POST['cod_produto'];
		$referencia  = $_POST['referencia'];
		$qtde        = $_POST['qtde'];
		$valor       = $_POST['valor'];
		$descricao   = $_POST['descricao'];
		$qtde_maxi   = $_POST['qtde_maxi'];
		$qtde_disp   = $_POST['qtde_disp'];

		if(strlen($peca)>0){

			$res = pg_exec ($con,"BEGIN TRANSACTION");

			$sql = "UPDATE tbl_pedido SET
					finalizado = NULL,
					condicao = NULL
					WHERE tbl_pedido.pedido_loja_virtual IS TRUE
					AND   tbl_pedido.exportado IS NULL
					AND   tbl_pedido.posto     = $login_posto
					AND   tbl_pedido.fabrica   = $login_fabrica";
			$res = pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage($con);

			$peca_promocional = 'f';

			$sql = "SELECT	tbl_peca.promocao_site,
							tbl_peca.qtde_disponivel_site
					FROM   tbl_peca
					WHERE  tbl_peca.peca    = $peca
					AND    tbl_peca.fabrica = $login_fabrica";
			$res = pg_exec ($con,$sql);
			if (pg_numrows($res)>0){
				$pesq_promo = trim(pg_result ($res,0,promocao_site));
				$pesq_qtde  = trim(pg_result ($res,0,qtde_disponivel_site));
				if ($pesq_promo == 't' OR strlen($pesq_qtde) > 0){
					$peca_promocional = 't';
				}
			}

			if ($peca_promocional == 't'){
				$axu_peca_promocional = " AND ( tbl_peca.promocao_site IS TRUE OR tbl_peca.qtde_disponivel_site IS NOT NULL ) ";
			}else{
				$axu_peca_promocional = " AND tbl_peca.promocao_site IS NOT TRUE AND tbl_peca.qtde_disponivel_site IS NULL ";
			}

			$sql = "SELECT DISTINCT tbl_produto.linha,tbl_tabela.tabela
					FROM tbl_produto 
					JOIN tbl_lista_basica USING(produto)
					JOIN tbl_peca USING(peca)
					JOIN tbl_posto_linha USING(linha)
					JOIN tbl_tabela USING(tabela)
					JOIN tbl_tabela_item ON tbl_tabela.tabela = tbl_tabela_item.tabela AND tbl_tabela_item.peca = $peca
					WHERE tbl_posto_linha.posto  = $login_posto
					AND   tbl_peca.peca    = $peca
					AND   tbl_tabela.ativa IS TRUE
					AND   tbl_peca.fabrica = $login_fabrica
					ORDER BY tbl_produto.linha ASC";
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) > 0) {
				$linha    = pg_result ($res,0,linha);
				$tabela   = pg_result ($res,0,tabela);
			}else{
				$msg_erro .= "Peça sem linha para seu posto. ";
			}

			$sql = "SELECT DISTINCT tbl_pedido.pedido
					FROM  tbl_pedido
					JOIN  tbl_pedido_item USING(pedido)
					JOIN  tbl_peca        USING(peca)
					WHERE tbl_pedido.pedido_loja_virtual IS TRUE
					AND   tbl_pedido.exportado IS NULL
					AND   tbl_pedido.posto       = $login_posto
					AND   tbl_pedido.fabrica     = $login_fabrica
					$axu_peca_promocional
					AND   tbl_pedido.linha       = $linha";
			$res = pg_exec ($con,$sql);
			if (pg_numrows($res) > 0 ) {
				$pedido = pg_result($res,0,pedido);
			}

			# ROTINA PARA INSERIR UM PEDIDO SE ESTE PEDIDO AINDA NÃO EXISTE
			if (count($pedido)==0){

				$Xpedido           = $_POST['pedido'];
				$Xcondicao         = $_POST['condicao'];
				$tipo_pedido       = $_POST['tipo_pedido'];
				$pedido_cliente    = $_POST['pedido_cliente'];
				
				if (strlen($Xcondicao) == 0) {
					$aux_condicao = "null";
				}else{
					$aux_condicao = $Xcondicao ;
				}

				if (strlen($pedido_cliente) == 0) {
					$aux_pedido_cliente = "null";
				}else{
					$aux_pedido_cliente = "'". $pedido_cliente ."'";
				}

				if (strlen($tipo_pedido) <> 0) {
					$aux_tipo_pedido = "'". $tipo_pedido ."'";
				}else{
					$sql = "SELECT	tipo_pedido
							FROM	tbl_tipo_pedido
							WHERE	descricao IN ('Faturado','Venda')
							AND		fabrica = $login_fabrica";
					$res = pg_exec ($con,$sql);
					$aux_tipo_pedido = "'". pg_result($res,0,tipo_pedido) ."'";
				}

				#------------------------------------------------------

				if(strlen($msg_erro)==0){
					$sql = "INSERT INTO tbl_pedido (
								posto          ,
								fabrica        ,
								condicao       ,
								pedido_cliente ,
								tipo_pedido    ,
								linha          ,
								pedido_loja_virtual 
							) VALUES (
								$login_posto        ,
								$login_fabrica      ,
								$aux_condicao       ,
								$aux_pedido_cliente ,
								$aux_tipo_pedido    ,
								$linha              ,
								TRUE
							)";
					$res = pg_exec ($con,$sql);
					$msg_erro = pg_errormessage($con);
					if (strlen($msg_erro) == 0){
						$res = pg_exec ($con,"SELECT CURRVAL ('seq_pedido')");
						$pedido  = pg_result ($res,0,0);
					}
				}
			}

			if (strlen($pedido)==0){
				$msg_erro .= "Não foi possível criar um pedido!";
			}

			if (strlen($qtde) == 0 OR $qtde < 1 ) {
				$msg_erro   = "Não foi digitada a quantidade para a Peça $peca_referencia.";
				$linha_erro = $i;
				break;
			}

			//verifica se a peça tem o valor da peca caso nao tenha exibe a msg 
			//só verifica os precos dos campos que tenha a referencia da peça.
			if ($login_fabrica == '3'){
				if($tipo_pedido <> '90'){
					if(strlen($valor) == 0){
						$msg_erro .= 'Existem peças sem preço.<br>';
						$linha_erro = $i; echo "=>".$valor;
						break;
					}
				}
			}

			if (strlen ($msg_erro) == 0) {

				$sql = "SELECT  tbl_peca.peca,
								tbl_peca.referencia,
								tbl_peca.descricao,
								tbl_peca.multiplo_site,
								tbl_peca.qtde_disponivel_site,
								tbl_peca.ipi
						FROM    tbl_peca
						WHERE   tbl_peca.peca    = $peca
						AND     tbl_peca.fabrica = $login_fabrica";
				$res = pg_exec ($con,$sql);

				if (pg_numrows ($res) == 0) {
					$msg_erro  .= "Peça $peca não cadastrada";
					$linha_erro = $i;
					break;
				}else{
					$peca      = pg_result ($res,0,peca);
					$peca_ref  = pg_result ($res,0,referencia);
					$peca_desc = pg_result ($res,0,descricao);
					$peca_mult = pg_result ($res,0,multiplo_site);
					$ipi       = pg_result ($res,0,ipi);
					$qtde_disp = pg_result ($res,0,qtde_disponivel_site);
				}


				########## Validação de Quantidade #########
				$sql = "SELECT	tbl_pedido_item.pedido_item,
								tbl_pedido_item.qtde
						FROM tbl_pedido
						JOIN tbl_pedido_item USING(pedido)
						WHERE  tbl_pedido.pedido      = $pedido 
						AND    tbl_pedido_item.peca   = $peca";
				$res = pg_exec ($con,$sql);
				if (pg_numrows ($res) > 0) {
					$pedido_item = pg_result ($res,0,pedido_item);
					$qtde_pedido = pg_result ($res,0,qtde);
					$qtde_pedido = $qtde_pedido + $qtde;

					if (strlen($msg_erro)==0 AND $qtde_pedido > $qtde_maxi AND strlen($qtde_maxi)>0){
						$msg_erro .= "Quantidade máxima permitida para o produto $peca_ref é de $qtde_maxi";
					}
					if (strlen($msg_erro)==0 AND $qtde_pedido > $qtde_disp AND strlen($qtde_disp)>0){
						#$msg_erro .= "Produto $peca_ref tem $qtde_disp unidades disponíveis.<br> Não foi adicionado ao carrinho. $qtde_pedido > $qtde_disp";
					}
					if (strlen($msg_erro)==0 AND $qtde % $peca_mult <> 0){
						$msg_erro .= "Quantidade deve ser múltiplo de $peca_mult.";
					}

					if (strlen($msg_erro)==0 AND $qtde > $qtde_disp AND strlen($qtde_disp)>0){
						$msg_erro .= "<br>Este produto tem $qtde_disp unidades. Você selecionou $qtde unidades.";
					}
				}else{
					if (strlen($msg_erro)==0 AND strlen($qtde_disp)>0 AND $qtde > $qtde_disp){
						$msg_erro .= "Este produto tem $qtde_disp unidades. Você selecionou $qtde unidades.";
					}
					if (strlen($msg_erro)==0 AND strlen($peca_mult)>0 AND $qtde % $peca_mult <> 0){
						$msg_erro .= "Quantidade deve ser múltiplo de $peca_mult.";
					}
				}

				if (strlen ($msg_erro) == 0) {
					if (strlen($pedido_item) == 0){
						$sql = "INSERT INTO tbl_pedido_item (
										pedido ,
										peca   ,
										qtde   ,
										preco  ,
										ipi
									) VALUES (
										$pedido,
										$peca  ,
										$qtde  ,
										$valor  ,
										$ipi
									)";
					}else{
						$sql = "UPDATE tbl_pedido_item SET
									qtde = COALESCE(qtde,0) + $qtde
								WHERE pedido_item = $pedido_item
								AND   pedido      = $pedido
								AND   peca        = $peca";
					}
					$res = pg_exec ($con,$sql);
					$msg_erro = pg_errormessage($con);

					if (strlen($msg_erro) == 0 AND strlen($pedido_item) == 0) {
						$res         = pg_exec ($con,"SELECT CURRVAL ('seq_pedido_item')");
						$pedido_item = pg_result ($res,0,0);
						$msg_erro = pg_errormessage($con);
					}
/*
					if (strlen($ipi)>0 AND $ipi>0){
						$total_peca = $qtde * $valor + ($qtde * $valor * $ipi)/100;
					}else{
						$total_peca = $qtde * $valor;
					}
*/
					$sql = "UPDATE tbl_pedido 
								SET   total = ROUND (
												(
												SELECT SUM(tbl_pedido_item.preco * tbl_pedido_item.qtde + tbl_pedido_item.preco * tbl_pedido_item.qtde * tbl_pedido_item.ipi / 100)
												FROM   tbl_pedido_item
												JOIN tbl_peca USING(peca)
												WHERE  tbl_pedido_item.pedido = $pedido
												)::NUMERIC , 2
											)
							WHERE tbl_pedido.fabrica  = $login_fabrica
							AND tbl_pedido.pedido     = $pedido
							AND tbl_pedido.posto      = $login_posto";
					$res = pg_exec ($con,$sql);
					$msg_erro = pg_errormessage($con);

					$sql = "UPDATE tbl_peca 
							SET   qtde_disponivel_site = qtde_disponivel_site - $qtde
							WHERE peca     = $peca
							AND   fabrica  = $login_fabrica
							AND qtde_disponivel_site IS NOT NULL";
					$res = pg_exec ($con,$sql);
					$msg_erro = pg_errormessage($con);


					# Verificação para enviar email em caso de peça não tiver mais estoque
					if (strlen($msg_erro) == 0) {
						$sql = "SELECT  tbl_peca.peca,
										tbl_peca.descricao,
										tbl_peca.referencia,
										tbl_peca.qtde_disponivel_site
								FROM tbl_peca
								WHERE peca   = $peca
								AND fabrica  = $login_fabrica
								AND qtde_disponivel_site IS NOT NULL
								AND qtde_disponivel_site < 1";
						$res         = pg_exec ($con,$sql);
						if (pg_numrows ($res) > 0) {
							$peca_peca       = pg_result ($res,0,peca);
							$peca_descricao  = pg_result ($res,0,descricao);
							$peca_referencia = pg_result ($res,0,referencia);
							$qtde_disponivel = pg_result ($res,0,qtde_disponivel_site);
							$msg_erro        = pg_errormessage($con);
							$mandar_email = "sim";
						}
					}

					if (strlen($pedido_item)==0){
						$msg_erro .= "Não foi possível adicionar o produto no carrinho.";
					}

					if (strlen($msg_erro) == 0) {
						$sql = "SELECT fn_valida_pedido_item ($pedido,$peca,$login_fabrica)";
						$res = pg_exec ($con,$sql);
						$msg_erro = pg_errormessage($con);
					}
					if (strlen ($msg_erro) > 0) {
						break ;
					}
				}
				if($login_fabrica == 3)
				{
					#if( strlen($valor) > 0 AND strlen($qtde) > 0){
					#	$total_valor = (($total_valor) + ( str_replace( "," , "." ,$valor) * $qtde));
					#}
				}
			}
			if (strlen ($msg_erro) == 0) {
				$res = pg_exec ($con,"COMMIT TRANSACTION");
				#header ("Location: loja_finalizado.php?pedido=$pedido&loc=1");
				$msg = "Produto adicionado com sucesso!";

				if ($mandar_email == "sim"){
					$sql = "SELECT email_loja_virtual
							FROM tbl_configuracao
							WHERE fabrica=$login_fabrica";
					$res_conf = pg_exec($con,$sql);
					if (pg_numrows($res_conf)>0){
						$email_loja_virtual = trim(pg_result($res_conf,0,email_loja_virtual));
					}
					if (strlen($email_loja_virtual)>0){

						$nome       = "Telecontrol";
						$email       = "$email_loja_virtual,fabio@telecontrol.com.br";
						$mensagem  .= "MENSAGEM AUTOMÁTICA. NÃO RESPONDA A ESTE E-MAIL <br><br>Este email foi enviado pelo sistema de Loja Virtual <br><br>Peça: <b>$peca_referencia - $peca_descricao</b><br>Quantidade disponível em estoque: <b>$qtde_disponivel</b><br><br>Esta peça teve seu estoque zerado. <b>É recomendado voltar ao preço normal.</b><br><br><br>____________________________________________<br>\n";
						$mensagem  .= "Telecontrol Networking<br>\n";
						$mensagem  .= "www.telecontrol.com.br";
						$assunto   = "Loja Virtual - Produto sem estoque - $peca_referencia - $peca_descricao";
						$boundary = "XYZ-" . date("dmYis") . "-ZYX";
						$mens  = "--$boundary\n";
						$mens .= "Content-Transfer-Encoding: 8bits\n";
						$mens .= "Content-Type: text/html; charset=\"ISO-8859-1\"\n\n";
						$mens .= "$mensagem\n";
						$mens .= "--$boundary\n";
						$headers  = "MIME-Version: 1.0\n";
						$headers .= "Date: ".date("D, d M Y H:i:s O")."\n";
						$headers .= "From: \"Telecontrol\" <helpdesk@telecontrol.com.br>\r\n";
						$headers .= "Content-type: multipart/mixed; boundary=\"$boundary\"\r\n";

						@mail($email, utf8_encode($assunto), utf8_encode($mens), $headers);
					}
				}
			}else{
				$res = pg_exec ($con,"ROLLBACK TRANSACTION");
			}
		}else{
			$msg_erro   .= "Selecione o produto.";
		}
	}

	if($acao=='limpa'){
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if (strlen($msg_erro)==0){
			# Retirei o $pedido
			$sql = "UPDATE tbl_peca
					SET qtde_disponivel_site = qtde_disponivel_site + tbl_pedido_item.qtde
					FROM tbl_pedido
					JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_pedido.pedido
					WHERE tbl_pedido_item.peca = tbl_peca.peca
					AND tbl_pedido.fabrica   = $login_fabrica
					AND tbl_pedido.posto     = $login_posto
					AND tbl_pedido.exportado IS NULL
					AND tbl_pedido.pedido_loja_virtual  IS TRUE
					AND tbl_peca.qtde_disponivel_site   IS NOT NULL";
			$res = pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}

		if (strlen($msg_erro)==0){
			$sql = "UPDATE tbl_pedido
					SET fabrica  = 0, 
						total    =  0
					WHERE tbl_pedido.fabrica   = $login_fabrica
					AND   tbl_pedido.posto     = $login_posto
					AND   tbl_pedido.exportado IS NULL
					AND   tbl_pedido.pedido_loja_virtual  IS TRUE ";
			$res = pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}

		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			//$res = pg_exec ($con,"ROLLBACK TRANSACTION");
			$msg = "O carrinho foi limpo!";
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}

	if($acao=='remover'){

			$res = pg_exec ($con,"BEGIN TRANSACTION");

			$pedido      = $_GET['pedido'];
			$peca        = $_GET['peca'];
			$pedido_item = $_GET['pedido_item'];

			$sql = "UPDATE tbl_pedido SET finalizado = null
					WHERE  tbl_pedido.pedido_loja_virtual IS TRUE
					AND    tbl_pedido.exportado IS NULL
					AND    tbl_pedido.posto   = $login_posto
					AND    tbl_pedido.fabrica = $login_fabrica";
			$res = pg_exec ($con,$sql);

			$sql = "SELECT	tbl_pedido_item.pedido_item,
							tbl_pedido_item.qtde,
							tbl_pedido_item.preco,
							tbl_peca.ipi
					FROM tbl_pedido_item
					JOIN tbl_peca USING(peca)
					WHERE  tbl_pedido_item.pedido = $pedido 
					AND    tbl_pedido_item.peca   = $peca
					AND    tbl_pedido.fabrica     = $login_fabrica
					AND    tbl_pedido.posto       = $login_posto
					AND    tbl_pedido.pedido_loja_virtual  IS TRUE
					AND    tbl_pedido.exportado            IS NULL
					";
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) > 0) {
				$pedido_item  = pg_result ($res,0,pedido_item);
				$qtde_remover = pg_result ($res,0,qtde);
				$preco        = pg_result ($res,0,preco);
				$ipi          = pg_result ($res,0,ipi);
			}else{
				$msg_erro .= "Produto não encontrado.";
			}

			if (strlen($msg_erro) == 0) {
				$sql = "DELETE FROM tbl_pedido_item
						WHERE pedido      = $pedido
						AND   peca        = $peca
						AND   pedido_item = $pedido_item";
				$res = pg_exec ($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}

			if (strlen($msg_erro) == 0) {
				$sql = "UPDATE tbl_peca 
						SET   qtde_disponivel_site = qtde_disponivel_site + $qtde_remover
						WHERE peca     = $peca
						AND   fabrica  = $login_fabrica
						AND   qtde_disponivel_site IS NOT NULL";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}

			$sql = "SELECT  count(*) as qtde_itens
				FROM  tbl_pedido
				JOIN  tbl_pedido_item USING(pedido)
				WHERE tbl_pedido.pedido = $pedido
				AND   tbl_pedido.pedido_loja_virtual IS TRUE
				AND   tbl_pedido.exportado IS NULL
				AND   tbl_pedido.posto   = $login_posto
				AND   tbl_pedido.fabrica = $login_fabrica";
			$res = pg_exec ($con,$sql);
			$qtde_itens_pedido = pg_result($res,0,qtde_itens);
			$msg_erro .= pg_errormessage($con);

			# Seta fabrica=0 se não tiver mais produtos
			if ($qtde_itens_pedido==0){
				if (strlen($msg_erro)==0){
					$sql = "UPDATE tbl_pedido
							SET fabrica = 0
							WHERE tbl_pedido.pedido  = $pedido
							AND   tbl_pedido.posto   = $login_posto
							AND   tbl_pedido.fabrica = $login_fabrica
							AND   tbl_pedido.pedido_loja_virtual IS TRUE
							AND   tbl_pedido.exportado           IS NULL";
					$res = pg_exec ($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}
			}else{
				if (strlen($ipi)>0 AND $ipi>0){
					$total_peca = $qtde * $preco + ($qtde * $preco * $ipi)/100;
				}else{
					$total_peca = $qtde * $preco;
				}

				$sql = "UPDATE tbl_pedido
						SET total = COLLAPSE(total,0) - $total_peca
						WHERE pedido = $pedido
						AND fabrica  = $login_fabrica
						AND posto    = $login_posto";
				#$res = pg_exec ($con,$sql);
				#$msg_erro .= pg_errormessage($con);

				$sql = "UPDATE tbl_pedido 
							SET   total = ROUND (
											(
											SELECT SUM(tbl_pedido_item.preco * tbl_pedido_item.qtde + tbl_pedido_item.preco * tbl_pedido_item.qtde * tbl_pedido_item.ipi / 100)
											FROM   tbl_pedido_item
											JOIN tbl_peca USING(peca)
											WHERE  tbl_pedido_item.pedido = $pedido
											)::NUMERIC , 2
										)
						WHERE tbl_pedido.pedido     = $pedido
						AND   tbl_pedido.posto      = $login_posto
						AND   tbl_pedido.fabrica    = $login_fabrica
						AND   tbl_pedido.exportado           IS NULL
						AND   tbl_pedido.pedido_loja_virtual IS TRUE
						";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}

			if (strlen ($msg_erro) == 0) {
				$res = pg_exec ($con,"COMMIT TRANSACTION");
				#$res = pg_exec ($con,"ROLLBACK TRANSACTION");
				$msg = "Produto removido com sucesso!";
			}else{
				$res = pg_exec ($con,"ROLLBACK TRANSACTION");
			}
	}
}

//alterado por Gustavo HD 3780 - refeito por Fabio
if ($btn_acao == "fechar_pedido"){

	if (strlen($pedido)==0){
		$msg_erro .= "Não foi criado o pedido!";
	}

	$res = pg_exec ($con,"BEGIN TRANSACTION");

if (1==2){
	$sql = "SELECT tbl_pedido.pedido
		FROM  tbl_pedido
		WHERE tbl_pedido.pedido_loja_virtual IS TRUE
		AND   tbl_pedido.exportado IS NULL
		AND   tbl_pedido.posto   = $login_posto
		AND   tbl_pedido.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	if ( pg_numrows($res) > 0) {
		for ($i=0;$i<pg_numrows($res);$i++){
			$pedido      = trim(pg_result($res,0,pedido));
			if (strlen ($msg_erro) == 0) {
				$sql2 = "SELECT fn_pedido_finaliza_loja_virtual($login_fabrica,$login_posto)";
				$res2 = @pg_exec ($con,$sql2);
				$msg_erro = pg_errormessage($con);
			}
		}
	}
}

	$sql2 = "SELECT fn_pedido_finaliza_loja_virtual_unico($login_fabrica,$login_posto)";
	$res2 = @pg_exec ($con,$sql2);
	$msg_erro = pg_errormessage($con);

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		$msg = "Pedido finalizado com sucesso!";
		echo "<script languague='javascript'>window.location = 'loja_completa.php?status=finalizado';</script>";
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

//########################################################################################################

$layout_menu = 'pedido';
$title = "Carrinho de Compras";

?>
<style>
.Titulo{
	font-family: Arial;
	font-size: 14px;
	font-weight:bold;
	color: #333;
}
.Conteudo{
	font-family: Arial;
	font-size: 11px;
	color: #333333;
}
</style>
<BODY TOPMARGIN=0>
<?

include 'loja_menu.php';

if(strlen($msg_erro)>0){
	$error = str_replace("ERROR:","",$msg_erro);
	echo "<h4 style='color:red'>".$error."</h4>";
}
if(strlen($msg)>0){
	echo "<h4 style='color:blue'>".$msg."</h4>";
}

if($login_posto){
	$sql="SELECT * FROM tbl_posto WHERE posto = $login_posto";
	$res = pg_exec ($con, $sql);
	if(pg_numrows($res)>0){
		$posto			= trim(pg_result ($res,0,posto));
		$nome			= trim(pg_result ($res,0,nome));
	}
}

echo "<table width='700' border='1' align='center' cellpadding='3' cellspacing='0' style='border:#B5CDE8 1px solid;  bordercolor:#d2e4fc;border-collapse: collapse'>";

echo "<tr>";
echo "<td align='left'  colspan='7' bgcolor='#e6eef7' class='Titulo' ><br><B>Carrinho de Compras</B><br></td>";
echo "</tr>";

echo "<tr>";
echo "<td colspan='7' align='right' class='Conteudo' style='font-size:14px;padding:5px'><a href='loja_completa.php'>Continuar Comprando</a> | <a href=\"javascript:if (confirm('Deseja limpar o seu carrinho de compras?')) window.location='$PHP_SELF?acao=limpa'\">Limpar Carrinho</a> | <a href=\"javascript:if (confirm('Deseja finalizar este pedido? Este pedido será enviado para a Fábrica.')) window.location = '$PHP_SELF?btn_acao=fechar_pedido'\" value='Fechar Pedido'>Fechar Pedido</a>";
echo "</td>";
echo "</tr>";

//cabeca
echo "<tr bgcolor='#e6eef7' class='Titulo'>";
	echo "<td width='20' height='30' align='center' >Remover?&nbsp;&nbsp;</td>";
	echo "<td  height='30' align='left'>Peça</td>";
	echo "<td  height='30' align='left'>Linha</td>";
	echo "<td  height='30' align='right'>Qtde</td>";
	echo "<td  height='30' align='right'>Valor Unit.</td>";
	echo "<td  height='30' align='center'>IPI</td>";
	echo "<td  height='30' align='right'>Valor Total</td>";
echo "</tr>";
//fim cabeca


$sql = "SELECT  
			tbl_pedido.pedido              ,
			tbl_pedido_item.pedido         ,
			tbl_pedido_item.pedido_item    ,
			tbl_peca.peca                  ,
			tbl_peca.referencia            ,
			tbl_peca.descricao             ,
			tbl_peca.ipi                   ,
			tbl_peca.promocao_site         ,
			tbl_peca.qtde_disponivel_site  ,
			tbl_pedido_item.qtde           ,
			tbl_pedido_item.preco          ,
			tbl_linha.nome as linha_desc
	FROM  tbl_pedido
	JOIN  tbl_pedido_item USING (pedido)
	JOIN  tbl_peca        USING (peca)
	LEFT JOIN tbl_linha USING(linha)
	WHERE tbl_pedido.posto   = $login_posto
	AND   tbl_pedido.fabrica = $login_fabrica
	AND   tbl_pedido.pedido_loja_virtual IS TRUE
	AND   tbl_pedido.exportado IS NULL
	ORDER BY tbl_pedido.pedido DESC";
$res = pg_exec ($con,$sql);

$pedido_ant = "";

//echo nl2br($sql);

if (pg_numrows($res) > 0) {
	for($i=0; $i< pg_numrows($res); $i++) {
		$pedido          = trim(pg_result($res,$i,pedido));
		$pedido_item     = trim(pg_result($res,$i,pedido_item));
		$peca            = trim(pg_result($res,$i,peca));
		$referencia      = trim(pg_result($res,$i,referencia));
		$peca_descricao  = trim(pg_result($res,$i,descricao));
		$qtde            = trim(pg_result($res,$i,qtde));
		$preco           = trim(pg_result($res,$i,preco));
		$ipi             = trim(pg_result($res,$i,ipi));
		$promocao_site   = trim(pg_result($res,$i,promocao_site));
		$qtde_disponivel = trim(pg_result($res,$i,qtde_disponivel_site));
		$linha_desc      = trim(pg_result($res,$i,linha_desc));
		
		
		$preco_2         = str_replace(",",".",$preco);

		if (strlen($ipi)>0 AND $ipi>0){
			$valor_total = $preco * $qtde + ($preco*$qtde *$ipi/100);
			$ipi = $ipi." %";
		}else{
			$ipi = "";
			$valor_total = $preco * $qtde;
		}

		$soma       += $valor_total;
		$preco       = number_format($preco, 2, ',', '');
		$preco       = str_replace(".",",",$preco);
		$valor_total = number_format($valor_total, 2, ',', '');

		//EXIBE OS PRODUTOS DA CESTA
		$a++;
		$cor = "#FFFFFF"; 
		if ($a % 2 == 0){
			$cor = '#EEEEE3';
		}

		if ($pedido_ant<>$pedido){
			if ($promocao_site=='t' OR strlen($qtde_disponivel)>0){
				$msg_pedido = "Pedido de Promoção";
			}else{
				$msg_pedido = "Pedido de Peças";
			}
			echo "<tr class='Conteudo'>";
			echo "<td  bgcolor='$cor'  align='left' colspan='7'>$msg_pedido</td>";
			echo "</tr >";
		}
		$pedido_ant = $pedido;

		echo "<tr class='Conteudo'>";
		echo "<td  bgcolor='$cor'  align='center'><a href=\"javascript: if(confirm('Deseja excluir o item $referencia - $peca_descricao?')){window.location='$PHP_SELF?acao=remover&peca=$peca&pedido=$pedido&pedido_item=$pedido_item'}\"> <IMG SRC='imagens/excluir_loja.gif' alt='Remover Produto'border='0'></a></td>";
		echo "<td  bgcolor='$cor' align='left'>$referencia - $peca_descricao</td>";
		echo "<td  bgcolor='$cor' align='center'>$linha_desc</td>";
		echo "<td  bgcolor='$cor' align='right'>$qtde</td>";
		echo "<td  bgcolor='$cor' align='right'>R$ $preco</td>";
		echo "<td  bgcolor='$cor' align='center'>$ipi</td>";
		echo "<td  bgcolor='$cor' align='right'>R$ $valor_total</td>";
		echo "</tr>";
	}
	$soma = number_format($soma, 2, ',', '');
	$soma = str_replace(".",",",$soma);
}else{
		echo "<tr class='Conteudo'>";
		echo "<td  bgcolor='#FFFFFF' colspan='7' align='center'><br>Carrinho vazio<br><br></td>";
		echo "</tr>";
}
//TOTAL
echo "<tr>";
echo "<td  colspan='6' height='30' align='right' bgcolor='#D5D5CB'><font size='2'><B>Total da Compra:</B></font>";
echo "</td>";
echo "<td align='right' bgcolor='#D5D5CB'><font color='#008800'><b>R$ $soma</B></font>";
echo "</td>";
echo "</tr>";
//TOTAL

echo "<tr>";
echo "<td align='center' bgcolor='#e6eef7' colspan='7' class='Titulo' style='padding:5px;'><a href='javascript:history.back()'>Voltar</a></td>";
echo "</tr>";

echo "</table>";

include 'rodape.php'
?>

<?php

	/****
	* HD 397539 - �derson Sandre
	* Arquivo criado para credenciamento da fabrica "Remington" baseado na linha 567 - Eletroport�teis da Fabrica 81 - Saltom
	* Arquivo requisitado na pagina: menu_inicial.php e cabecalho.php "include_once 'pesquisa_remington.php';";
	* Funcionalidade:
	* - Todos os postos que atende a linha 567 da Saltom, ser�o obrigados a passarem pelo formul�rio de pesquisa.
	* - Alguns posto receberam emails, caso optarem por cadastrar em alguma das fabricas, o sistema receber� um parametro via GET e gravaram um COOKIE, desta forma ele ter� o procedimento do formul�rioo de pesquisa.
	*
	****/

	$cookie_pesquisa_remington = $_COOKIE['cookie_pesquisa_remington'];

	/*****
	*** $cookie_pesquisa_remington VALUE 1 ou 2
	*** 1 POSTO SALTON
	*** 2 POSTO N�O SALTON
	*****/

	if (strpos($PHP_SELF,'pesquisa_remington') !== false) {
		include_once "dbconfig.php";
		include_once "includes/dbconnect-inc.php";
		include_once "autentica_usuario.php";
	}
	if($login_fabrica == 81) {
		$sql = "
			SELECT DISTINCT(posto)
			FROM tbl_posto
				JOIN tbl_posto_linha USING(posto)
				JOIN tbl_linha USING(linha)
			WHERE
				tbl_posto_linha.linha = 567
				AND tbl_posto_linha.posto = $login_posto
				AND fabrica = $login_fabrica;
		";
		//echo $sql;
		$res = pg_query($con,$sql);

		if (pg_numrows ($res) > 0 or $cookie_pesquisa_remington == 1 or $cookie_pesquisa_remington == 2) {
			$btn_grava = $_POST['btn_grava'];
			if ($btn_grava == "gravar_dados"){
				$credenciado = $_POST['credenciado'] ;
				$credenciado_value = $_POST['credenciado'] ;

				switch ($credenciado) {
					case 1: $credenciado = "J� sou credenciado da Rede Telecontrol e tamb�m quero atender os Produtos da REMINGTON"; break;
					case 2: $credenciado = "Ainda n�o sou credenciado da Rede Telecontrol e quero atender somente os Produtos REMINGTON"; break;
					case 3: $credenciado = "Ainda n�o sou credenciado da Rede Telecontrol e quero atender todos os Produtos da REMINGTON e da BestWay (Salton Brasil)"; break;
				}

				$sql = "INSERT INTO tbl_pesquisa_remington (fabrica, posto, resposta) VALUES ($login_fabrica, $login_posto, '$credenciado')";
				$res      = pg_query ($con,$sql) ;
			}

			$sql_verifica = "SELECT posto FROM tbl_pesquisa_remington WHERE posto = $login_posto;";
			$res_verifica = pg_query($con,$sql_verifica);

			if (pg_numrows($res_verifica) == 0) {
				?>

				<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
					<html xmlns="http://www.w3.org/1999/xhtml">
					<head>
						<style rel="stylesheet" type="text/css" media="all" >
							html, body {
								margin:0;
								padding:0;
								font-family: Verdana, Arial, Helvetica, sans-serif;
								text-align: center;
							}

							.btn_normal{
								background-image: url('imagens/pesquisa_remington_btn_inscrevase_sprites.png');
								height: 56px ;
								width: 210px ;
								background-position: -5px -5px;
								cursor: pointer
							}

							.btn_normal:hover{
								background-position: -5px -65px;
							}
						</style>
						<title>Fa�a parte desta Revolu��o</title>
					  </head>
					  <body style='text-algin: center'>
						<?php if($cookie_pesquisa_remington == 2){?>
							<div style='width: 700px; height: 1150px; background: #FFF; margin: 0 auto; position: relative; border: 1px solid #000;'>
						<?php }else{?>
							<div style='width: 700px; height: 940px; background: #FFF; margin: 0 auto; position: relative; border: 1px solid #000;'>
						<?php }?>
							<div style='position: absolute; top: 0; left: 0; height: 128px;  width: 700px;'>
								<img src='imagens/pesquisa_remington_topo.jpg' title='Fa�a parte desta revolua��o' />
							</div>
							<div style='position: absolute; top: 128px; left: 0; height: 30px; width: 700px; font-size: 24px; background: #000; color: #FFF; text-align: right; padding: 10px 0; font-weight: 400; font-family: Tahoma'>
								Fa�a parte desta Revolu��o&nbsp;&nbsp;
							</div>
							<!-- FORMULARIO //-->
							<div  style='position: absolute; top: 180px; left: 0; padding: 10px; text-align: left;'>
								<p style=' font-size: 14px; text-align: justify; '>
									<b>Tire suas d�vidas:</b><br /><br />
										A partir de 1� de junho de 2.011 a Rede Autorizada Telecontrol passar� a atender os Produtos da Linha de Cuidados Pessoais da REMINGTON. Trata-se de uma das marcas mais importantes do mercado Norte Americano e que chega com for�a Total no Brasil. A sua conceituada linha � composta de Secadores, Pranchas, Modeladores, Depiladores, Barbeadores, Massageadores, Cortadores de Cabelo e Aparadores de Pelo.
								</p>
							</div>
							<div  style='position: absolute; top: 320px; width: 410px; left: 0; padding: 10px; text-align: left;  font-size: 14px; text-align: justify;'>
								Os contratos e acordos para presta��o de Servi�os ser�o fechados com a TELECONTROL, ou seja, toda a Gest�o Operacional ser� realizada pela nossa equipe.
								<br /><br /><b>Segue as principais informa��es:</b>
								<ul>
									<li style='line-height:150%;'>Taxas: R$15,00 reais para reparos ou intermedia��o de trocas de produto sem conserto ou sem pe�a.</li>
									<li style='line-height:150%;'>Material de apoio: 0800, Sistema TELECONTROL, vistas explodidas e diagramas el�tricos.</li>
									<li style='line-height:150%;'>LGR: Inicialmente n�o ser�o coletados produtos nem pe�as. Permanecer�o por 90 dias a disposi��o para auditoria e em seguida poder�o ser utilizados para gera��o de pe�as.</li>
									<li style='line-height:150%;'>Extrato: Pagamentos mensais ap�s envio da c�pia f�sica da Nota Fiscal de compra para a Telecontrol.</li>
								</ul>
							</div>
							<div style='position: absolute; top: 685px; width: 680px; left: 0; padding: 10px; text-align: left;  font-size: 14px; text-align: justify;'>
								A REDE TELECONTROL � composta por 450 Postos Autorizados qualificados pelos principais indicadores dos nossos clientes do Software. Atualmente nossa Rede atende os Produtos da BestWay (Salton Brasil), entre eles: GEORGE FOREMAN (grill), RUSSELL HOBBS (fornos e cafeteiras).
								<br /><br /><b>Apartir do dia 01/10/2011 voc� est� credenciado a atender a nova linha de Produtos da BestWay (Salton Brasil): Produtos REMINGTON!</b>

								<?php if($cookie_pesquisa_remington == 2){?>
									<form name='frm_pesquisa' method='post' action='<? echo $PHP_SELF ?>' >
										<div style='margin-left: 20px; font-size: 14px; text-align: justify;'>
											<br /><input type="radio" name="credenciado" value="1" id='1' checked="checked" <?php if($credenciado_value == 1) echo "checked='checked'"; ?> />
											<label for='1'>J� sou credenciado da Rede Telecontrol e tamb�m quero atender os Produtos da REMINGTON</label>

											<br /><br /><input type="radio" name="credenciado" value="2" id='2' <?php if($credenciado_value == 2) echo "checked='checked'"; ?> />
											<label for='2'>Ainda n�o sou credenciado da Rede Telecontrol e quero atender somente os Produtos REMINGTON</label>

											<br /><br /><input type="radio" name="credenciado" value="3" id='3' <?php if($credenciado_value == 3) echo "checked='checked'"; ?> />
											<label for='3'>Ainda n�o sou credenciado da Rede Telecontrol e quero atender todos os Produtos da REMINGTON e da BestWay (Salton Brasil)</label>
										</div>
									<br /><br /><center>Voc� ainda tem d�vida? Mande sua mensagem para <a href='mailto: helpdesk@telecontrol.com.br&subject=Credenciamento Remington' title='Suporte Telecontrol'>helpdesk@telecontrol.com.br</a></center>
								<?php }?>

							</div>

							<!-- IMAGEM //-->
							<div style='position: absolute; top: 310px; right: 3px; height: 352px; width:270px; z-index: 10;'>
								<img src='imagens/pesquisa_remington_produto.jpg' title='Remington - Produtos' />
							</div>

							<?php if($cookie_pesquisa_remington != 2){?>
								<div style='text-align: center; padding: 30px; position: absolute; bottom: 50px; left: 0; width: 640px;'>
									<form method='POST'>
										<input type='hidden' name='btn_grava' value='gravar_dados' />
										<input type='submit' name='btn_ok' value=' OK ' style='padding: 5px 30px' />
									</form>
								</div>
							<?php }else{?>
								<div style='position: absolute; bottom: 20px; right: 20px; height: 68px; width:220px; z-index: 10'>
									<div class='btn_normal' onclick="javascript: if(document.frm_pesquisa.btn_grava.value == '' ) { document.frm_pesquisa.btn_grava.value='gravar_dados' ; document.frm_pesquisa.submit() } else { alert ('Aguarde ') }"></div>
								</div>
								<input type="hidden" name="btn_grava" value="">
								</form>
							<?php }?>
							<div style='position: absolute; bottom: 0; left: 0; height: 60px; width: 700px; background: #000; z-index: 0'></div>
						 </div>
					</body>
				</html>
				<?
				exit;
			}
		}
	}
?>

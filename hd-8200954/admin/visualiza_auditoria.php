<?php
	/**
	 * @author Brayan L. Rastelli
	 * @description Relatorio Auditoria. HD 896786
	 */ 	

	include 'dbconfig.php';
	include_once 'helper.php';
	
	include 'includes/dbconnect-inc.php';

	if (strpos($PHP_SELF,'admin') !== false) {

		$layout_menu      	= "callcenter";
		$admin_privilegios	= "call_center";

		include 'autentica_admin.php';

	} else {
 		include '../autentica_usuario.php';

	}


	$auditoria = (int) $_GET['auditoria'];

	if (empty($auditoria)) {

		echo 'Auditoria Inválida.';
		return;
	}

	$sql = "SELECT 
				tbl_auditoria_online.auditoria_online, 
				tbl_auditoria_online.tipo_auditoria,
				tbl_auditoria_online.conclusao_auditoria,
				TO_CHAR(tbl_auditoria_online.data_digitacao, 'dd/mm/YYYY HH24:MI') AS data_digitacao,
				tbl_admin.nome_completo,
				tbl_posto.nome,
				tbl_posto.nome_fantasia,
				tbl_posto_fabrica.contato_nome,
				tbl_posto_fabrica.contato_cidade,
				tbl_posto_fabrica.contato_estado,
				tbl_posto_fabrica.contato_endereco,
				tbl_posto_fabrica.contato_bairro,
				tbl_posto_fabrica.contato_cep,
				tbl_posto_fabrica.contato_email,
				tbl_posto_fabrica.contato_fone_comercial,
				tbl_posto_fabrica.contato_fone_residencial,
				tbl_posto_fabrica.contato_fax,
				tbl_pesquisa.descricao as descricao_pesquisa
			FROM 
				tbl_auditoria_online
				JOIN tbl_admin USING(admin)
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = tbl_auditoria_online.fabrica AND tbl_posto_fabrica.posto = tbl_auditoria_online.posto
				JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
				JOIN tbl_pesquisa USING(pesquisa)
			WHERE 
				tbl_auditoria_online.auditoria_online = $auditoria 
				AND tbl_auditoria_online.fabrica = $login_fabrica";

	$res = pg_query($con, $sql);

	if (pg_num_rows($res) == 0) {
		echo 'Auditoria nÃ£o encontrada';
		return;
	}
	
	$auditoria     	= pg_result($res,0,'auditoria_online');
	$data           = explode (' ', pg_result($res, 0, 'data_digitacao') );
	$tipo_auditoria = pg_result($res,0,'tipo_auditoria');
	$auditor        = pg_result($res,0,'nome_completo');
	$desc_pesquisa  = pg_result($res,0,'descricao_pesquisa');

?>

<!DOCTYPE HTML>
<html lang="en-US">
<head>
	<meta charset="ISO-8859-1">
	<title>Visualizando auditoria</title>
	<style type="text/css">
		#container{
			width:700px;
			margin:auto;
			text-align:center;
		}
		.titulo_coluna {
			background-color:#596d9b;
			font: bold 11px "Arial";
			color:#FFFFFF;
			text-align:center;
		}
		.msg_erro {
			background-color:#FF0000;
			font: bold 14px "Arial";
			color:#FFFFFF;
			text-align:center;
		}
		.tabela tr td {
			border-collapse: collapse;
			font-family: verdana;
			font-size: 11px;
			border: 1px solid #596d9b !important;
		}
		.subtitulo{
		    background-color: #7092BE;
		    font:bold 11px Arial;
		    color: #FFFFFF;
		}
		.print {
			display: none;
		}

		.assinatura {
			margin-top:60px;
			float:left;
			border-top:1px solid black;
			width:240px;
		}

		.assinatura span{
			display: block;
			clear:both;
		}

		.right{
			float:right;
		}

	</style>
	<style type="text/css" media="print">
		
		.print {
			display: block;
		}
		
	</style>
</head>
<body>

	<!-- Div que engloba a página -->
	<div id="container">

		<div class="print" style="">
			<img src="logos/<?=strtolower($login_fabrica_nome)?>.png" alt="Fricon" style="float:left;">
			<h3 class="right" style="float:right; font-family: Verdana; font-size:14px;"><?=$desc_pesquisa?></h3>
		</div>

		<div style="clear:both;"></div>

		<!-- Cabeçalho da auditoria -->
		<table class="tabela" cellspacing="1" cellpadding="" style="min-width:700px; margin: auto;">
			<thead>
				<tr class="titulo_coluna">
					<th class="titulo_coluna" style="width:100px;">Data da Auditoria</th>
					<th class="titulo_coluna" style="width:100px;">Horário</th>
					<th class="titulo_coluna" style="width:120px;">Tipo de Auditoria</th>
					<th class="titulo_coluna" style="width:180px;">Auditor</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><?=$data[0]?></td>
					<td><?=$data[1]?></td>
					<td><?=$tipo_auditoria?></td>
					<td><?=$auditor?></td>
				</tr>
			</tbody>
		</table>
		<!-- Fim cabeçalho auditoria -->

		<!-- Dados do posto -->
		<table class="tabela" cellspacing="1" cellpadding="" style="min-width:700px; margin:20px auto;">
			<tbody>
				<tr class="titulo_coluna">
					<th class="titulo_coluna" colspan="2">Razão Social</th>
					<th class="titulo_coluna">Nome Fantasia</th>
				</tr>
				<tr>
					<td colspan="2"><?=pg_result($res, 0, 'nome')?></td>
					<td><?=pg_result($res, 0, 'nome_fantasia')?></td>
				</tr>
				<tr class="titulo_coluna">
					<th class="titulo_coluna" colspan="2">Endereço Completo</th>
					<th class="titulo_coluna">Bairro</th>
				</tr>
				<tr>
					<td colspan="2"><?=pg_result($res, 0, 'contato_endereco')?></td>
					<td><?=pg_result($res, 0, 'contato_bairro')?></td>
				</tr>
				<tr class="titulo_coluna">
					<th class="titulo_coluna" width="150px">Cidade</th>
					<th class="titulo_coluna" width="150px">Estado</th>
					<th class="titulo_coluna">CEP</th>
				</tr>
				<tr>
					<td><?=pg_result($res, 0, 'contato_cidade')?></td>
					<td><?=pg_result($res, 0, 'contato_estado')?></td>
					<td><?=pg_result($res, 0, 'contato_cep')?></td>
				</tr>
				<tr class="titulo_coluna">
					<th class="titulo_coluna">Fone</th>
					<th class="titulo_coluna">Fax</th>
					<th class="titulo_coluna">E-mail</th>
				</tr>
				<tr>
					<td><?=pg_result($res, 0, 'contato_fone_comercial')?></td>
					<td><?=pg_result($res, 0, 'contato_fax')?></td>
					<td><?=pg_result($res, 0, 'contato_email')?></td>
				</tr>
				<tr>
					<th class="titulo_coluna" colspan="3">Pessoa para contato</th>
				</tr>
				<tr>
					<td colspan="3"><?=pg_result($res, 0, 'contato_nome')?>&nbsp;</td>
				</tr>
				<tr>
					<th class="titulo_coluna" colspan="3">Informações adicionais</th>
				</tr>
				<tr>
					<td colspan="3"><?=pg_result($res, 0, 'conclusao_auditoria')?></td>
				</tr>
			</tbody>
		</table>
		<!-- Fim dados posto -->

		<!-- Mostra perguntas/respostas da auditoria -->

		<?php
			$sql = "SELECT 
						tbl_pergunta.pergunta,
						tbl_pergunta.descricao AS descricao_pergunta,
						tbl_tipo_resposta.tipo_descricao,
						tbl_tipo_pergunta.descricao AS descricao_requisito
					FROM tbl_auditoria_online
						JOIN tbl_pesquisa USING(pesquisa)
						JOIN tbl_pesquisa_pergunta ON tbl_pesquisa.pesquisa = tbl_pesquisa_pergunta.pesquisa
						JOIN tbl_pergunta USING(pergunta)
						JOIN tbl_tipo_resposta USING(tipo_resposta)
						JOIN tbl_tipo_pergunta USING(tipo_pergunta)
					WHERE 
						tbl_auditoria_online.auditoria_online = $auditoria
						";

			try {
				
				$res = pg_query($con, $sql);

				if(pg_num_rows($res) == 0) {
					throw new Exception("Falha ao recuperar as respostas da pesquisa" . pg_errormessage($con));
				}

				$arrPerguntas = pg_fetch_all($res);

				/**
				 * Monta um array do tipo: $perguntas['NomeDoRequisito'] => Array ( 0 => Array('pergunta' => 'nome_pergunta', 'tipo_descricao' => 'radio') )
				 */
				foreach($arrPerguntas as $pergunta) {

					$requisito = $pergunta['descricao_requisito'];
					unset($pergunta['descricao_requisito']);

					$requisitos[$requisito][] = $pergunta;

				}

				unset($arrPerguntas);

				echo '<table class="tabela" style="min-width:700px; margin:10px auto;">';

				foreach($requisitos as $requisito => $perguntas) {

					if ($requisito_antigo != $requisito) {

						echo '<tr class="titulo_coluna">
								<th class="titulo_coluna" colspan="3">'.$requisito.'</th>
							  </tr>
							  <tr class="subtitulo">
							  	<th class="subtitulo">Pergunta</th>
							  	<th class="subtitulo">Resposta(s)</th>
							  	<th class="subtitulo">Evidência</th>
							  </tr>';

					}

					$i = 0;

					foreach($perguntas as $pergunta) {

						$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
						$i++;

						if ($pergunta['tipo_descricao'] != 'checkbox') {

							$sql = "SELECT 
										txt_resposta, 
										nota, 
										observacao, 
										tbl_tipo_resposta_item.descricao
									FROM 
										tbl_resposta
										LEFT JOIN tbl_tipo_resposta_item USING(tipo_resposta_item)
									WHERE 
										pergunta = {$pergunta['pergunta']}
										AND auditoria_online = $auditoria";

							$res = pg_query($con, $sql);

							$obs = @pg_result($res, 0, 'observacao');

							switch($pergunta['tipo_descricao']) {

								case 'text' :
									$resposta = @pg_result($res, 0, 'txt_resposta');
									break;

								case 'range' :
									$resposta = @pg_result($res, 0, 'nota');
									break;

								case 'radio':
									$resposta = @pg_result($res, 0, 'descricao');
									break;

								default:
									$resposta = '';

							}

						} else {
							
							$sql = "SELECT tbl_tipo_resposta_item.descricao, tbl_resposta_item.obs
									FROM tbl_resposta_item
									JOIN tbl_tipo_resposta_item USING(tipo_resposta_item)
									WHERE tbl_resposta_item.pergunta = {$pergunta['pergunta']}
									AND auditoria_online = $auditoria";

							$res2 = pg_query($con, $sql); //@TODO criar campo
							echo pg_errormessage($con);
							$respItens = array();

							for ( $j = 0; $j < pg_num_rows($res2); $j++) {
								$respItens[] = pg_result($res2, $j, 'descricao');
								$obs 	  = pg_result($res2, $j, 'obs');
							}

							$resposta = implode(' | ', $respItens);							

						}

						echo '<tr bgcolor="'.$cor.'">
								<td bgcolor="'.$cor.'">'.$pergunta['descricao_pergunta'].'</td>
								<td bgcolor="'.$cor.'">'.$resposta.'</td>
								<td bgcolor="'.$cor.'">'.$obs.'</td>
							  </tr>';

					}

				}

				echo '</table>';

			} catch (Exception $e) {
				echo $e->getMessage();
			}

		?>

		<!-- Fim mostra pergunta/respostas da auditoria -->
		<div style="clear:both; overflow:hidden;">&nbsp;</div>
		<!-- Anexos -->
		<?php

			$dir = ($login_posto) ? 'admin/' : '';

			$imagens = glob( $dir . "anexos/auditoria_online/$auditoria*");

			if (!empty($imagens)) :

				echo '<table class="tabela" style="margin:10px auto; width:350px; clear:both; overflow:hidden;page-break-inside: avoid;">
						<tr class="titulo_coluna"><td>Anexos</td></tr>';

				foreach($imagens as $img) : 
					
					echo '<tr>
							<td>
								<a href="'.$img.'" target="_blank" style="text-decoration:none;">
									<img src="'.$img.'" width="300px" style="clear:both; margin-top:10px; overflow:hidden;" />
								</a><br />
								<!--<a href="'.$img.'" target="_blank" rel="nozoom">Ver no tamanho original</a>-->
							</td>
						  </tr>';

				endforeach;

				echo '</table>';

			endif;
		?>

		<!-- Fim anexos -->

		<div class="assinatura print">
			<span>Departamento de Pós-Venda</span><br />
			Data: __/__/____
		</div>
		<div class="assinatura right print">
			<span>S.A.F / Carimbo</span><br />
			Data: __/__/____
		</div>

	</div>
	<!-- Fim div#container -->

	<?php if (!empty($img)) : ?>
		<script type='text/javascript' src='../js/FancyZoom.js'></script>
	    <script type='text/javascript' src='../js/FancyZoomHTML.js'></script>
   	<?php endif; ?>

	<script type="text/javascript">

		<?php if (isset($_GET['imprimir'])): ?>
			window.print();
		<?php endif; ?>

		<?php if (!empty($img)) : ?>
        	setupZoom();
		<?php endif; ?>

	</script>

</body>
</html>

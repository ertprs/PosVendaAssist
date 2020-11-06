<?php
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include 'autentica_admin.php';

	header("Expires: 0");
	header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
	header("Pragma: no-cache, public");

	$referencia_produto	= strtoupper(trim($_REQUEST['referencia_produto']));
	$voltagem_produto	= strtoupper(trim($_REQUEST['voltagem_produto']));
	$tipo				= trim($_REQUEST['tipo']);
	$produto			- trim($_REQUEST['produto']);

	$referencia			= strtoupper(trim($_REQUEST['referencia']));
	$descricao			= strtoupper(trim($_REQUEST['descricao']));
	$posicao			= trim($_REQUEST['posicao']);

	if(!is_int($produto)) {
		$produto = null;
	}
	function verificaValorCampo($campo){
		return strlen($campo) > 0 ? $campo : "&nbsp;";
	}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>

		<style type="text/css">
			body {
				margin: 0;
				font-family: Arial, Verdana, Times, Sans;
				background: #fff;
			}

			.kit_titulo {
				font-size: 11pt;
				background-color: #555599;
				color: #FFFFFF;
				text-decoration: none;
			}
		</style>
		<link rel="stylesheet" type="text/css" href="../css/lupas/lupas.css">
		<script type="text/javascript" src="js/jquery-1.4.2.js"></script>
		<script src="../plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>
		<script type='text/javascript'>
			//função para fechar a janela caso a telca ESC seja pressionada!
			$(window).keypress(function(e) { 
				if(e.keyCode == 27) { 
					 window.parent.Shadowbox.close();
				}
			});

			$(document).ready(function() {
				$("#gridRelatorio").tablesorter();
			}); 
		</script>

	</head>

	<body>
		<div class="lp_header">
			<a href='javascript:window.parent.Shadowbox.close();' style='border: 0;'>
				<img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
			</a>
		</div>
		<?
			echo "<div class='lp_nova_pesquisa'>";
				echo "<form action='$PHP_SELF' method='POST' name='nova_pesquisa'>";
					echo "<input type='hidden' name='voltagem' value='$voltagem' />";
					echo "<input type='hidden' name='tipo' value='$tipo' />";
					echo "<input type='hidden' name='referencia_produto' value='$referencia_produto' />";
					echo "<input type='hidden' name='voltagem_produto' value='$voltagem_produto' />";
					echo "<input type='hidden' name='tipo' value='$tipo' />";
					echo "<input type='hidden' name='produto' value='$produto' />";
					echo "<input type='hidden' name='posicao' value='$posicao' />";

					echo "<table cellspacing='1' cellpadding='2' border='0'>";
						echo "<tr>";
							echo "<td>
								<label>Refêrencia</label>
								<input type='text' name='referencia' value='$referencia' style='width: 150px' maxlength='20' />
							</td>"; 
							echo "<td>
								<label>Descrição</label>
								<input type='text' name='descricao' value='$descricao' style='width: 370px' maxlength='80' />
							</td>"; 
							echo "<td colspan='2' class='btn_acao' valign='bottom'><input type='submit' name='btn_acao' value='Pesquisar Novamente' /></td>"; 
						echo "</tr>";
					echo "</table>";
				echo "</form>";
			echo "</div>";

	if ($login_fabrica == 1) {

		$sql = "SELECT produto 
				FROM tbl_produto
				WHERE UPPER(referencia) = UPPER('{$referencia_produto}')
				AND fabrica_i = {$login_fabrica}";
		$res = pg_query($con, $sql);

		$produto_id = pg_fetch_result($res, 0, 'produto');
		
		$sql = "SELECT tbl_produto_troca_opcao.produto, produto_opcao 
				FROM tbl_produto_troca_opcao
				JOIN tbl_produto ON tbl_produto.produto = tbl_produto_troca_opcao.produto_opcao
				AND tbl_produto.fabrica_i = {$login_fabrica} AND tbl_produto.ativo IS TRUE 
				WHERE tbl_produto_troca_opcao.produto = {$produto_id}";
		$res = pg_query($con, $sql);

		//pegar somente o primeiro registro para validar no if
		$produto_troc  = pg_fetch_result($res, 0, 'produto');
		$produto_opcao = pg_fetch_result($res, 0, 'produto_opcao');

		$qtde_produtos_troca = pg_num_rows($res);

		if ($qtde_produtos_troca > 1 || ($qtde_produtos_troca == 1 && $produto_troc != $produto_opcao)) {
			?>
			<div class="alert" style="background-color: #ffd20a;color: black;text-align: center;">
		        <h5><?= traduz("O.modelo.de.origem.esta.indisponivel") ?>.<br /> <?= traduz("selecione.abaixo.o.modelo.desejado") ?></h5>
		    </div>
		<?php 
		} 
	}

	if (strlen($voltagem_produto) > 0) {
		$where = " AND UPPER(voltagem) = '$voltagem_produto'";
	}

	$sql = "SELECT 
			produto 
		FROM tbl_produto
			JOIN tbl_linha USING(linha)
		WHERE fabrica = $login_fabrica
			AND UPPER(referencia) = '$referencia_produto'
			$where";
	$res = pg_exec($con, $sql);

	if (pg_numrows($res) > 0) {
		$produto = pg_result($res,0,0);
	} else {
		echo "<div class='lp_msg_erro'>Não foi encontrado nenhum produto com a referência $referencia_produto.</div>";
		exit;
	}

	$kit_controle = true; //Variável para controlar se a sql vai trazer a coluna KIT ou não. Não traz somente no caso de não ter opção de troca cadastrada, ou seja, vai trazer o próprio produto

	if ($tipo == 'referencia' and strlen($referencia) > 0) {
		echo "<div class='lp_pesquisando_por'>Pesquisando pela referência: $referencia</div>";

		$sql = "SELECT  tbl_produto.produto, 
					tbl_produto.referencia, 
					tbl_produto.descricao, 
					tbl_produto.voltagem,
					tbl_produto_troca_opcao.kit
				FROM tbl_produto_troca_opcao
				JOIN tbl_produto    ON tbl_produto_troca_opcao.produto_opcao = tbl_produto.produto
				WHERE tbl_produto_troca_opcao.produto = $produto
				AND   upper(tbl_produto.referencia)   like '%$referencia%'
				ORDER by kit, descricao";
		$res = pg_exec ($con,$sql);

		//se não tem nenhum opcional mostra o próprio produto
		if (pg_numrows($res) == 0) {
			$sql = "SELECT tbl_produto.produto, 
						tbl_produto.referencia, 
						tbl_produto.descricao, 
						tbl_produto.voltagem
					FROM tbl_produto    
					WHERE tbl_produto.produto = $produto
						AND   upper(tbl_produto.referencia)   like '%$referencia%'
						AND   (select count(*) from tbl_produto_troca_opcao where produto = $produto) = 0 
					ORDER by descricao";
			$res = pg_exec ($con,$sql);
			$kit_controle = false;
		}
	}

	if ($tipo == 'descricao' and strlen($descricao) > 0) {
		echo "<div class='lp_pesquisando_por'>Pesquisando pela descrição: $descricao</div>";

		$sql = "SELECT tbl_produto.produto, 
					tbl_produto.referencia, 
					tbl_produto.descricao, 
					tbl_produto.voltagem,
					tbl_produto_troca_opcao.kit
				FROM tbl_produto_troca_opcao
					JOIN tbl_produto    ON tbl_produto_troca_opcao.produto_opcao = tbl_produto.produto
				WHERE tbl_produto_troca_opcao.produto = $produto
					AND   upper(tbl_produto.descricao)   like '$descricao%'
				ORDER by kit, descricao";
		$res = pg_exec ($con,$sql);
		
		//se não tem nenhum opcional mostra o próprio produto
		if (pg_numrows($res)==0) {
			$sql = "SELECT  tbl_produto.produto, 
						tbl_produto.referencia, 
						tbl_produto.descricao, 
						tbl_produto.voltagem
					FROM tbl_produto 
					WHERE tbl_produto.produto = $produto
					AND   upper(tbl_produto.descricao)   like '$descricao%'
					AND   (select count(*) from tbl_produto_troca_opcao where produto = $produto) = 0 
					ORDER by descricao";
			$res = pg_exec ($con,$sql);
			$kit_controle = false;
		}
	}

	if ( ($tipo=='referencia' and strlen($referencia)==0) or ($tipo=='descricao' and strlen($descricao)==0) ) {
		echo "<div class='lp_pesquisando_por'>Pesquisando opções de troca pelo produto: $referencia_produto</div>";

		$sql = "SELECT  tbl_produto.produto, 
						tbl_produto.referencia, 
						tbl_produto.descricao, 
						tbl_produto.voltagem,
						tbl_produto_troca_opcao.kit
				FROM tbl_produto_troca_opcao
				JOIN tbl_produto    ON tbl_produto_troca_opcao.produto_opcao = tbl_produto.produto
				WHERE tbl_produto_troca_opcao.produto = $produto
				ORDER by kit, descricao";
		$res = pg_exec ($con,$sql);

		//se não tem nenhum opcional mostra o próprio produto
		if (pg_numrows($res)==0) {
			$sql = "SELECT  tbl_produto.produto, 
							tbl_produto.referencia, 
							tbl_produto.descricao, 
							tbl_produto.voltagem
					FROM tbl_produto 
					WHERE tbl_produto.produto = $produto
					AND (select count(*) from tbl_produto_troca_opcao where produto = $produto) = 0 
					ORDER BY descricao";
			$res = pg_exec ($con,$sql);
			$kit_controle = false;
		}
	}

	if(pg_numrows ($res) > 0){?>
		<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio'>
			<thead>
				<tr>
					<th width="20%">Código</th>
					<th width="40%">Nome</th>
					<th width="10%">Voltagem</th>
				</tr>
			</thead>
			<tbody><? 
				$kit_anterior = 0;
				for ($i = 0 ; $i < pg_num_rows($res); $i++) {
					$produto      = trim(pg_result($res,$i,produto));
					$referencia   = trim(pg_result($res,$i,referencia));
					$descricao    = trim(pg_result($res,$i,descricao));
					$voltagem     = trim(pg_result($res,$i,voltagem));

					$descricao			= str_replace ('"','',$descricao);
					$descricao			= str_replace("'","",$descricao);
					$descricao			= str_replace("''","",$descricao);

					$kit = (int)trim(pg_result($res,$i,kit));

					$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
					
					if($login_fabrica == 1){
						$onclickkit = "onclick= \"javascript: window.parent.retorna_dados_produto_troca('$kit','KIT','KIT $kit','KIT $kit','$posicao', '$kit'); window.parent.Shadowbox.close();\"";
					
						$onclick = "onclick= \"javascript: window.parent.retorna_dados_produto_troca('$produto','$referencia','$descricao','$voltagem','$posicao', '$kit'); window.parent.Shadowbox.close();\"";
					}										

					if ($kit == 0) {
						echo "<tr style='background: $cor' $onclick>";
							echo "<td>".verificaValorCampo($referencia)."</td>";
							echo "<td>".verificaValorCampo($descricao)."</td>";
							echo "<td>".verificaValorCampo($voltagem)."</td>";
						echo "</tr>";
					}else {
						if ($kit_anterior == 0) {
							echo "<tr><th colspan='3'><b>KITs:</b> Poderá ser selecionado um KIT para trocar o produto atual por vários outros.</tr>";
						}

						if ($kit != $kit_anterior) {
							echo "<tr style='background: $cor' $onclickkit  colspan='3'><td>KIT $kit - clique aqui para selecionar este KIT</td></tr>";
						}

						echo "<tr style='background: $cor' $onclick>";
							echo "<td>".verificaValorCampo($referencia)."</td>";
							echo "<td>".verificaValorCampo($descricao)."</td>";
							echo "<td>".verificaValorCampo($voltagem)."</td>";
						echo "</tr>";

						$kit_anterior = $kit;
					}
		}
		echo "</table>\n";
	}else{
		echo "<div class='lp_msg_erro'>Nehum resultado encontrado</div>";
	}?>
</body>
</html>

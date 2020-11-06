<?php
    include "dbconfig.php";
    include "includes/dbconnect-inc.php";
    include 'autentica_usuario.php';

	header("Expires: 0");
	header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
	header("Pragma: no-cache, public");

	$produto			= trim($_REQUEST['produto']); //id_produto
	$peca_referencia	= strtoupper(trim($_REQUEST['peca_referencia']));
	$peca_descricao		= strtoupper(trim($_REQUEST['peca_descricao']));
	$posicao			= trim($_REQUEST['posicao']);

	if (!empty(trim($_REQUEST['pecas_desconsidera']))) {

		$pecas_desconsidera = explode("|", $_REQUEST['pecas_desconsidera']);

		foreach ($pecas_desconsidera as $peca) {
			if (!empty($peca)) {
				$pecas_desconsidera_aux[] = $peca;
			}
		}

		if (count($pecas_desconsidera_aux) > 0) {
			$cond_pecas_desconsidera = "AND tbl_peca.peca NOT IN (".implode(",", $pecas_desconsidera_aux).")";
		}

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
		<link rel="stylesheet" type="text/css" href="css/lupas/lupas.css">
		<script type="text/javascript" src="js/jquery-1.4.2.js"></script>
		<script src="plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>
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
					echo "<input type='hidden' name='produto' value='$produto' />";
					echo "<input type='hidden' name='posicao' value='$posicao' />";
					echo "<input type='hidden' name='pecas_desconsidera' value='".$_REQUEST['pecas_desconsidera']."' />";

					echo "<table cellspacing='1' cellpadding='2' border='0'>";
						echo "<tr>";
							echo "<td>
								<label>Peça Refêrencia</label>
								<input type='text' name='peca_referencia' value='$peca_referencia' style='width: 150px' maxlength='20' />
							</td>"; 
							echo "<td>
								<label>Descrição</label>
								<input type='text' name='peca_descricao' value='$peca_descricao' style='width: 370px' maxlength='80' />
							</td>"; 
							echo "<td colspan='2' class='btn_acao' valign='bottom'><input type='submit' name='btn_acao' value='Pesquisar Novamente' /></td>"; 
						echo "</tr>";
					echo "</table>";
				echo "</form>";
			echo "</div>";

	//verifica se o produto existe e é verdadeiro
	$sql_produto = "SELECT 
				produto,
				referencia,
				descricao
			FROM tbl_produto
				JOIN tbl_linha USING(linha)
			WHERE fabrica = $login_fabrica
				AND tbl_produto.produto = $produto;";
	$res_produto = @pg_exec($con, $sql_produto);
	$produto = pg_result($res_produto,0,'produto');
	$produto_referencia = pg_result($res_produto,0,'referencia');
	$produto_descricao = pg_result($res_produto,0,'descricao');

	if(!empty($peca_referencia)){
		echo "<div class='lp_pesquisando_por'>$produto_referencia - $produto_descricao<br />Pesquisando pela referência: $peca_referencia</div>";
		
		$sql = "
			SELECT DISTINCT
				tbl_peca.peca,
				tbl_peca.referencia,
				tbl_peca.descricao,
				tbl_peca.voltagem,
				tbl_lista_basica.qtde,
				tbl_lista_basica.posicao
			FROM tbl_peca
				JOIN tbl_lista_basica ON tbl_peca.peca = tbl_lista_basica.peca AND tbl_lista_basica.produto = $produto AND tbl_lista_basica.fabrica = $login_fabrica
			WHERE 
				tbl_peca.ativo IS TRUE
				AND tbl_peca.fabrica = $login_fabrica
				AND UPPER(tbl_peca.referencia) ILIKE '%$peca_referencia%'
				{$cond_pecas_desconsidera}
			ORDER BY tbl_lista_basica.posicao ASC;";
	}elseif(!empty($peca_descricao)){
		echo "<div class='lp_pesquisando_por'>$produto_referencia - $produto_descricao<br />Pesquisando pela descrição: $peca_descricao</div>";

		$sql = "
			SELECT DISTINCT
				tbl_peca.peca,
				tbl_peca.referencia,
				tbl_peca.descricao,
				tbl_peca.voltagem,
				tbl_lista_basica.qtde,
				tbl_lista_basica.posicao
			FROM tbl_peca
				JOIN tbl_lista_basica ON tbl_peca.peca = tbl_lista_basica.peca AND tbl_lista_basica.produto = $produto AND tbl_lista_basica.fabrica = $login_fabrica
                LEFT JOIN tbl_peca_idioma ON tbl_peca_idioma.peca = tbl_peca.peca AND upper(idioma) = '$sistema_lingua'
			WHERE 
				tbl_peca.ativo IS TRUE
                AND tbl_peca.fabrica = $login_fabrica
				AND (UPPER(tbl_peca.descricao) ILIKE '%$peca_descricao%'
                    OR UPPER(tbl_peca_idioma.descricao) ILIKE '%$peca_descricao%')
                {$cond_pecas_desconsidera}
			ORDER BY tbl_lista_basica.posicao ASC;";
	}else{
		echo "<div class='lp_pesquisando_por'>Todas as peças do produto: $produto_referencia - $produto_descricao</div>";
			
		$sql = "
			SELECT DISTINCT
				tbl_peca.peca,
				tbl_peca.referencia,
				tbl_peca.descricao,
				tbl_peca.voltagem,
				tbl_lista_basica.qtde,
				tbl_lista_basica.posicao
			FROM tbl_peca
				JOIN tbl_lista_basica ON tbl_peca.peca = tbl_lista_basica.peca AND tbl_lista_basica.produto = $produto AND tbl_lista_basica.fabrica = $login_fabrica
			WHERE 
				tbl_peca.ativo IS TRUE	
                AND tbl_peca.fabrica = $login_fabrica
                {$cond_pecas_desconsidera}
			ORDER BY tbl_lista_basica.posicao ASC;";
	}
    //echo nl2br($sql);
	$res = pg_exec ($con,$sql);
	#echo nl2br($sql);
	if(pg_numrows ($res) > 0){?>
		<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio'>
			<thead>
				<tr>
					<th width="20%">Referência</th>
					<th width="*">Descrição</th>
					<th width="10%">Voltagem</th>
					<th width="10%">Quantidade</th>
				</tr>
			</thead>
			<tbody>
			<? 
				if(pg_num_rows($res) == 1){
					$peca      			= trim(pg_result($res,0,peca));
					$peca_referencia   	= trim(pg_result($res,0,referencia));
					$peca_descricao    	= trim(pg_result($res,0,descricao));
					$voltagem    		= trim(pg_result($res,0,voltagem));
					$qtde    			= trim(pg_result($res,0,qtde));
					
					$peca_descricao			= str_replace('"','',$peca_descricao);
					$peca_descricao			= str_replace("'","",$peca_descricao);
					$peca_descricao			= str_replace("''","",$peca_descricao);
					
					echo "<script type='text/javascript'>";
							echo "window.parent.retorna_dados_peca('$peca','$peca_referencia','$peca_descricao','$voltagem','$qtde','$posicao');";
							echo "window.parent.Shadowbox.close();";
					echo "</script>";
				}
			
				for ($i = 0 ; $i < pg_num_rows($res); $i++) {
					$peca      			= trim(pg_result($res,$i,peca));
					$peca_referencia   	= trim(pg_result($res,$i,referencia));
					$peca_descricao    	= trim(pg_result($res,$i,descricao));
					$voltagem    		= trim(pg_result($res,$i,voltagem));
					$qtde    			= trim(pg_result($res,$i,qtde));

					$peca_descricao			= str_replace ('"','',$peca_descricao);
					$peca_descricao			= str_replace("'","",$peca_descricao);
					$peca_descricao			= str_replace("''","",$peca_descricao);


                    if($login_fabrica == 20){
                        $sql_idioma = "SELECT descricao FROM tbl_peca_idioma WHERE peca = $peca AND upper(idioma) = '$sistema_lingua'";
                        $res_idioma = @pg_query($con,$sql_idioma);

                        if (@pg_num_rows($res_idioma) >0) 
                            $peca_descricao = trim(@pg_fetch_result($res_idioma,0,'descricao'));
                   }

					$onclick = "onclick= \"javascript: window.parent.retorna_dados_peca('$peca','$peca_referencia','$peca_descricao','$voltagem','$qtde','$posicao'); window.parent.Shadowbox.close();\"";

					$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
					echo "<tr style='background: $cor' $onclick>";
						echo "<td>".verificaValorCampo($peca_referencia)."</td>";
						echo "<td>".verificaValorCampo($peca_descricao)."</td>";
						echo "<td>".verificaValorCampo($voltagem)."</td>";
						echo "<td>".verificaValorCampo($qtde)."</td>";
					echo "</tr>";
					
				}
		echo "</table>\n";
	}else{
		echo "<div class='lp_msg_erro'>Nehum resultado encontrado</div>";
	}?>
</body>
</html>

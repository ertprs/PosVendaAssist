<?php
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";

	if (!function_exists('ttext')) {
		include_once 'helpdesk/fn_ttext.php';
	}

	$pr_trad = array (
		'titulo' => array (
			'pt-br'	=> 'Pesquisa Revendas',
			'es'	=> 'Busca Distribuidores',
			'en'	=> 'Resellers Search'
		),
		"pesquisa_nome" => array (
			"pt-br"	=> "Resultados da pesquisa pelo <b>nome</b> da Revenda: ",
			"es"	=> "Resultado de la búsqueda por <b>nombre</b> del Distribuidor: ",
		),
		"nome_not_found" => array (
			"pt-br"	=> "Revenda '%s' não encontrada",
			"es"	=> "Distribuidor '%s' no encontrado",
		),
		"pesquisa_cnpj" => array (
			"pt-br"	=> "Resultados da pesquisa pelo <b>CNPJ</b> da Revenda: ",
			"es"	=> "Resultado de la búsqueda por <b>nº ID Fiscal</b> del Distribuidor: ",
		),
		"cnpj_not_found" => array (
			"pt-br"	=> "Revenda de CNPJ '%s' não encontrada",
			"es"	=> "Distribuidor con ID Fiscal '%s' no encontrado",
		),
		"digite_ao_menos" => array (
			"pt-br"	=> "Digite ao menos as 4 primeiras letras para pesquisar por nome, ou os 6 primeiros dígitos do CNPJ",
			"es"	=> "Escriba al menos las primeras 4 letras del nombre, o los 6 primeros dígitos del nº de ID Fiscal",
		),
	);

	$cook_idioma = 'pt-br';
	if ($sistema_lingua == 'ES') {
		$cook_idioma = 'es';
		$img_suffix  = '_es';
	}

	$nome = strtoupper(trim($_REQUEST['nome']));
	$cnpj = strtoupper(trim($_REQUEST['cnpj']));
	$forma = trim($_REQUEST['forma']);

	if ($login_fabrica == 50) {
		$tipo_revenda = (isset($_REQUEST['tipo_revenda']) and $_REQUEST['tipo_revenda'] != 'undefined') ? $_REQUEST['tipo_revenda'] : '' ;
	}

	function verificaValorCampo($campo){
		return strlen($campo) > 0 ? $campo : "&nbsp;";
	}

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv='pragma' content='no-cache'>
		<title><?=ttext($pr_trad, "titulo")?></title>
		<style type="text/css">
			body {
				margin: 0;
				font-family: Arial, Verdana, Times, Sans;
				background: #fff;
			}
		</style>
		<script type="text/javascript" src="js/jquery-1.4.2.js"></script>
		<script src="plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>
		<link rel="stylesheet" type="text/css" href="css/lupas/lupas.css">
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
				echo "<form action='".$_SERVER["PHP_SELF"]."' method='POST' name='nova_pesquisa'>";
					echo "<input type='hidden' name='forma' value='$forma' />";
					echo "<table cellspacing='1' cellpadding='2' border='0'>";
						echo "<tr>";
							echo "<td>
								<label>".traduz("cnpj.revenda",$con,$cook_idioma)."</label>
								<input type='text' name='cnpj' value='$cnpj' style='width: 150px' maxlength='20' />
							</td>"; 
							echo "<td>
								<label>".traduz('nome.revenda',$con,$cook_idioma)."</label>
								<input type='text' name='nome' value='$nome' style='width: 370px' maxlength='80' />
							</td>"; 
							echo "<td colspan='2' class='btn_acao' valign='bottom'><input type='submit' name='btn_acao' value='Pesquisar Novamente' /></td>"; 
						echo "</tr>";
					echo "</table>";
				echo "</form>";
			echo "</div>";

			$usa_rev_fabrica = in_array($login_fabrica, array(3,117));

			$filtrar_pais = ($login_fabrica == 20) ? " AND tbl_revenda.pais='$login_pais'" : '';
			if($cook_idioma == 'pt-br') $cond_cnpj_validado = " AND cnpj_validado IS TRUE ";

			if (strlen($nome) > 2) {
				echo "<div class='lp_pesquisando_por'>".ttext($pr_trad, "pesquisa_nome").": $nome</div>";
				
				$sql = "SELECT DISTINCT
							LPAD(tbl_revenda.cnpj, 14, '0') AS cnpj	,
							tbl_revenda.nome					,
							tbl_revenda.revenda				,
							tbl_revenda.cidade				,
							tbl_revenda.fone					,
							tbl_revenda.endereco				,
							tbl_revenda.numero				,
							tbl_revenda.complemento			,
							tbl_revenda.bairro				,
							tbl_revenda.cep					,
							tbl_revenda.email					,
							tbl_cidade.nome AS nome_cidade		,
							tbl_estado.estado AS nome_estado		,
							tbl_cidade.estado
						FROM tbl_revenda
						     JOIN tbl_cidade USING (cidade)
						     JOIN tbl_estado USING (estado)
						WHERE tbl_revenda.nome LIKE UPPER('$nome%')
							$cond_cnpj_validado
							$filtrar_pais
							AND ativo IS NOT FALSE
						ORDER BY nome_estado, nome_cidade, bairro, nome";
				 if ($usa_rev_fabrica) $sql = "SELECT DISTINCT
							cnpj,
							contato_razao_social AS nome		,
							contato_endereco AS endereco		,
							contato_bairro AS bairro			,
							contato_complemento  AS complemento	,
							contato_numero AS numero			,
							contato_cep AS cep				,
							contato_fone AS fone				,
							contato_email AS email				,
							tbl_cidade.nome AS nome_cidade		,
							tbl_estado.estado AS nome_estado		,
							tbl_cidade.estado AS estado
						FROM tbl_revenda_fabrica
							JOIN tbl_cidade ON tbl_cidade.cidade = tbl_revenda_fabrica.cidade
							JOIN tbl_estado USING (estado)
						WHERE 
						contato_razao_social LIKE UPPER('$nome%')
						AND tbl_revenda_fabrica.fabrica  = $login_fabrica
							$filtrar_pais
						ORDER BY nome_estado, nome_cidade, bairro, nome";
			}elseif(strlen($cnpj) > 2){
				$cnpj = preg_replace('/\D/', '', trim($cnpj));
				
				echo "<div class='lp_pesquisando_por'>".ttext($pr_trad, "pesquisa_cnpj")." $cnpj</div>";
				//if (strlen($cnpj) < 14) echo substr(str_repeat('.', 14 - strlen($cnpj)), 0, 3);

				$cnpj = preg_replace('/\D/', '', trim($cnpj)); 
				$cond_cnpj = (strlen($cnpj) == 14) ? "cnpj = '$cnpj'" : "cnpj ~ E'^$cnpj'";

				$sql = "SELECT DISTINCT
							LPAD(tbl_revenda.cnpj, 14, '0') AS cnpj	,
							tbl_revenda.nome					,
							tbl_revenda.revenda				,
							tbl_revenda.cidade				,
							tbl_revenda.fone					,
							tbl_revenda.endereco				,
							tbl_revenda.numero				,
							tbl_revenda.complemento			,
							tbl_revenda.bairro				,
							tbl_revenda.cep					,
							tbl_revenda.email					,
							tbl_cidade.nome AS nome_cidade		,
							tbl_estado.estado AS nome_estado		,
							tbl_cidade.estado
						FROM tbl_revenda
							JOIN   tbl_cidade USING (cidade)
							JOIN   tbl_estado USING (estado)
						WHERE       tbl_revenda.$cond_cnpj
						$cond_cnpj_validado
						$filtrar_pais
									AND ativo IS NOT FALSE
						ORDER BY    nome_estado, nome_cidade, bairro, nome";

				if ($usa_rev_fabrica) $sql = "
						SELECT DISTINCT
							cnpj							,
							contato_razao_social AS nome		,
							contato_endereco AS endereco		,
							contato_bairro AS bairro			,
							contato_complemento AS complemento	,
							contato_numero AS numero			,
							contato_cep AS cep				,
							contato_fone AS fone				,
							contato_email AS email				,
							tbl_cidade.nome AS nome_cidade		,
							tbl_estado.estado AS nome_estado		,
							tbl_cidade.estado AS estado
						FROM tbl_revenda_fabrica
							JOIN tbl_cidade   ON tbl_cidade.cidade = tbl_revenda_fabrica.cidade
							JOIN tbl_estado USING (estado)
						WHERE
						tbl_revenda_fabrica.$cond_cnpj
						AND tbl_revenda_fabrica.fabrica = $login_fabrica
							$filtrar_pais
						ORDER BY 
							nome_estado, nome_cidade, bairro, nome";
			}

			if(strlen($nome) < 2 AND strlen($cnpj) < 2)
				$msg_erro = "Informar toda ou parte da informação para realizar a pesquisa!";

			if(strlen($msg_erro) == 0){
				//echo $sql;
				$res = pg_query($con, $sql);
				if (pg_num_rows($res) > 0 ){
					
					if (pg_num_rows($res) == 1 ){
						$nome = pg_fetch_result($res,0,nome);
						$cnpj = pg_fetch_result($res,0,cnpj);
						$nome_cidade = pg_fetch_result($res,0,nome_cidade);
						$fone = pg_fetch_result($res,0,fone);
						$endereco = pg_fetch_result($res,0,endereco);
						$numero = pg_fetch_result($res,0,numero);
						$complemento = pg_fetch_result($res,0,complemento);
						$bairro = pg_fetch_result($res,0,bairro);
						$cep = pg_fetch_result($res,0,cep);
						$estado = pg_fetch_result($res,0,estado);
						$revenda_estado = $estado;
						$nome_estado = pg_fetch_result($res,0,nome_estado);
						$email = pg_fetch_result($res,0,email);

						echo "<script type='text/javascript'>";
							echo "window.parent.retorna_peca('$nome','$cnpj','$nome_cidade','$fone','$endereco','$numero','$complemento','$bairro','$cep','$nome_estado','$email','$tipo_revenda', '$revenda_estado'); window.parent.Shadowbox.close();";
						echo "</script>";
					}
					
					?>
					<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio'>
						<thead>
							<tr>
								<th><?=traduz("cnpj.revenda",$con,$cook_idioma)?></th>
								<th><?=traduz('nome.revenda',$con,$cook_idioma)?></th>
								<th><?=traduz('bairro',$con,$cook_idioma)?></th>
								<th><?=traduz('cidade',$con,$cook_idioma)?></th>
								<th><?=traduz('estado',$con,$cook_idioma)?></th>
							</tr>
						</thead>
						<tbody>
							<?
							for ($i = 0 ; $i < pg_num_rows($res); $i++) {

								$nome = pg_fetch_result($res,$i,nome);
								$cnpj = pg_fetch_result($res,$i,cnpj);
								$nome_cidade = pg_fetch_result($res,$i,nome_cidade);
								$fone = pg_fetch_result($res,$i,fone);
								$endereco = pg_fetch_result($res,$i,endereco);
								$numero = pg_fetch_result($res,$i,numero);
								$complemento = pg_fetch_result($res,$i,complemento);
								$bairro = pg_fetch_result($res,$i,bairro);
								$cep = pg_fetch_result($res,$i,cep);
								$estado = pg_fetch_result($res,$i,estado);
								$revenda_estado = $estado;
								$nome_estado = pg_fetch_result($res,$i,nome_estado);
								$email = pg_fetch_result($res,$i,email);

								if ($forma == 'reload'){
									$rev_id = ($usa_rev_fabrica) ? preg_replace('/\D/', '', $cnpj) : $revenda;
									$onclick = "onclick= \"javascript: window.parent.retorna_peca('$rev_id'); window.parent.Shadowbox.close();\"";
								}else
									$onclick = "onclick= \"javascript: window.parent.retorna_peca('$nome','$cnpj','$nome_cidade','$fone','$endereco','$numero','$complemento','$bairro','$cep','$nome_estado','$email','$tipo_revenda', '$revenda_estado'); window.parent.Shadowbox.close();\"";

								$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
								echo "<tr style='background: $cor' $onclick>";
									echo "<td>".verificaValorCampo($cnpj)."</td>";
									echo "<td>".verificaValorCampo($nome)."</td>";
									echo "<td>".verificaValorCampo($bairro)."</td>";
									echo "<td>".verificaValorCampo($nome_cidade)."</td>";
									echo "<td>".verificaValorCampo($nome_estado)."</td>";
								echo "</tr>";
							}
						echo "</tbody>";
					echo "</table>";
				}else{
					echo "<div class='lp_msg_erro'>Nenhum resultado encontrado</div>";
				}
			}?>
	</body>
</html>

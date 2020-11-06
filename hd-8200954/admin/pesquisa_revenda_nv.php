<?
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include 'autentica_admin.php';

	function verificaValorCampo($campo){
		return strlen($campo) > 0 ? $campo : "&nbsp;";
	}

	$nome = strtoupper(trim($_REQUEST['nome']));
	$cnpj = strtoupper(trim($_REQUEST['cnpj']));
	$forma = trim($_REQUEST['forma']);

	$usa_rev_fabrica = in_array($login_fabrica, array(117));
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv='pragma' content='no-cache'>
		<style type="text/css">
			body {
				margin: 0;
				font-family: Arial, Verdana, Times, Sans;
				background: #fff;
			}
		</style>
		<script type="text/javascript" src="../js/jquery-1.4.2.js"></script>
		<script src="../plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>
		<link rel="stylesheet" type="text/css" href="../css/lupas/lupas.css">
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
								<label>CNPJ</label>
								<input type='text' name='cnpj' value='$cnpj' style='width: 150px' maxlength='20' />
							</td>"; 
							echo "<td>
								<label>Nome</label>
								<input type='text' name='nome' value='$nome' style='width: 370px' maxlength='80' />
							</td>"; 
							echo "<td colspan='2' class='btn_acao' valign='bottom'><input type='submit' name='btn_acao' value='Pesquisar Novamente' /></td>"; 
						echo "</tr>";
					echo "</table>";
				echo "</form>";
			echo "</div>";

	if (strlen($nome) > 2) {
		echo "<div class='lp_pesquisando_por'>Pesquisando pelo nome: $nome</div>";
		
		$sql = "SELECT DISTINCT lpad(tbl_revenda.cnpj, 14, '0') AS cnpj ,
				tbl_revenda.nome                       ,
				tbl_revenda.revenda                    ,
				tbl_revenda.cidade                     ,
				tbl_revenda.fone                       ,
				tbl_revenda.endereco                   ,
				tbl_revenda.numero                     ,
				tbl_revenda.complemento                ,
				tbl_revenda.bairro                     ,
				tbl_revenda.cep                        ,
				tbl_revenda.email                      ,
				tbl_cidade.nome         AS nome_cidade ,
				tbl_cidade.estado                      
			FROM tbl_revenda
				LEFT JOIN tbl_cidade USING (cidade)
			WHERE 
				tbl_revenda.nome ILIKE '%$nome%' 
				AND tbl_revenda.cnpj_validado IS TRUE";
			if ($login_fabrica == 1)  
				$sql .= " AND   tbl_revenda.cnpj IS NOT NULL AND ativo IS NOT FALSE ";

			$sql .= " ORDER BY    tbl_cidade.estado,tbl_cidade.nome,tbl_revenda.bairro,tbl_revenda.nome";


 		if ($usa_rev_fabrica) {

 			$cond = '^' . $nome;

 			$whereAdc = " WHERE contato_razao_social ~* '$cond' ";

    		$sql = "SELECT
						tbl_revenda_fabrica.contato_razao_social AS nome,
						tbl_revenda_fabrica.revenda,
					    tbl_cidade.cidade AS cidade 			,
					    tbl_revenda_fabrica.contato_fone AS fone        ,
		                tbl_revenda_fabrica.contato_endereco AS endereco    ,
					    tbl_revenda_fabrica.contato_numero  AS numero     ,
					    tbl_revenda_fabrica.contato_complemento AS complemento ,
					    tbl_revenda_fabrica.contato_bairro AS bairro      ,
					    tbl_revenda_fabrica.contato_cep  AS cep        ,
					    tbl_cidade.nome AS nome_cidade           ,
					    tbl_revenda_fabrica.contato_email AS email       ,
					    tbl_cidade.estado				        
				FROM tbl_revenda_fabrica
				LEFT JOIN tbl_cidade ON tbl_cidade.cidade = tbl_revenda_fabrica.cidade
				$whereAdc
				AND tbl_revenda_fabrica.fabrica = $login_fabrica
				ORDER BY tbl_cidade.estado, tbl_cidade.cidade, contato_razao_social";
		}

	}elseif(strlen($cnpj) > 2){
		$cnpj = preg_replace('/\D/', '', trim($cnpj));
		echo "<div class='lp_pesquisando_por'>Pesquisando pelo CNPJ: $cnpj</div>";

		$cnpj = strtoupper ($cnpj);
		$cnpj = str_replace ("-","",$cnpj);
		$cnpj = str_replace (".","",$cnpj);
		$cnpj = str_replace ("/","",$cnpj);
		$cnpj = str_replace (" ","",$cnpj);

		$sql = "SELECT DISTINCT lpad(tbl_revenda.cnpj, 14, '0') AS cnpj ,
				tbl_revenda.nome              ,
				tbl_revenda.revenda           ,
				tbl_revenda.cidade            ,
				tbl_revenda.fone              ,
				tbl_revenda.endereco          ,
				tbl_revenda.numero            ,
				tbl_revenda.complemento       ,
				tbl_revenda.bairro            ,
				tbl_revenda.cep               ,
				tbl_revenda.email             ,
				tbl_cidade.nome AS nome_cidade,
				tbl_cidade.estado             ,
				tbl_revenda.revenda
			FROM tbl_revenda
				LEFT JOIN tbl_cidade USING (cidade)
			WHERE
				tbl_revenda.cnpj ILIKE '%$cnpj%' 
				AND tbl_revenda.cnpj_validado IS TRUE";
			if ($login_fabrica == 1) 
				$sql .= " AND tbl_revenda.cnpj IS NOT NULL AND ativo IS NOT FALSE";
			$sql .= " ORDER BY tbl_revenda.nome";


			if ($usa_rev_fabrica) {

	 			$valor = str_replace(array(".", ",", "-", "/"), "", $cnpj);

				$whereAdc = " WHERE cnpj ~* '^$valor' ";

	    		$sql = "SELECT
							tbl_revenda_fabrica.contato_razao_social AS nome,
							tbl_revenda_fabrica.revenda,
						    tbl_cidade.cidade AS cidade 			,
						    tbl_revenda_fabrica.contato_fone AS fone        ,
			                tbl_revenda_fabrica.contato_endereco AS endereco    ,
						    tbl_revenda_fabrica.contato_numero  AS numero     ,
						    tbl_revenda_fabrica.contato_complemento AS complemento ,
						    tbl_revenda_fabrica.contato_bairro AS bairro      ,
						    tbl_revenda_fabrica.contato_cep  AS cep        ,
						    tbl_cidade.nome AS nome_cidade           ,
						    tbl_revenda_fabrica.contato_email AS email       ,
						    tbl_cidade.estado				        
						FROM tbl_revenda_fabrica
						LEFT JOIN tbl_cidade ON tbl_cidade.cidade = tbl_revenda_fabrica.cidade
						$whereAdc
						AND tbl_revenda_fabrica.fabrica = $login_fabrica
						ORDER BY tbl_cidade.estado, tbl_cidade.cidade, contato_razao_social";
			}

	}else{
		echo "<div class='lp_msg_erro'>Informar toda ou parte da informação para realizar a pesquisa!</div>";
		exit;
	}
	
	$res = pg_query($con, $sql);

	if (pg_numrows ($res) > 0 ) {?>
		<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio'>
			<thead>
				<tr>
					<th>CNPJ</th>
					<th>Nome</th>
					<th>Bairro</th>
					<th>Cidade</th>
					<th>Estado</th>
				</tr>
			</thead>
			<tbody>
				<?
				for ($i = 0 ; $i < pg_num_rows($res); $i++) {
					$revenda    = trim(pg_result($res,$i,revenda));
					$nome		= trim(pg_result($res,$i,nome));
					$revenda	= trim(pg_result($res,$i,revenda));
					$cnpj		= trim(pg_result($res,$i,cnpj));
					$nome_cidade	= trim(pg_result($res,$i,nome_cidade));
					$fone		= trim(pg_result($res,$i,fone));
					$endereco	= trim(pg_result($res,$i,endereco));
					$numero		= trim(pg_result($res,$i,numero));
					$complemento	= trim(pg_result($res,$i,complemento));
					$bairro		= trim(pg_result($res,$i,bairro)); 
					$cep		= trim(pg_result($res,$i,cep));
					$estado		= trim(pg_result($res,$i,estado));
					$email		= trim(pg_result($res,$i,email));

					if(pg_num_rows($res) == 1){

						if ($login_fabrica == 94)
						{
							echo "<script type='text/javascript'>";
								echo "window.parent.retorna_revenda('$nome','$cnpj','$nome_cidade','$fone','$endereco','$numero','$complemento','$bairro','$cep','$estado','$email','$revenda'); window.parent.Shadowbox.close();";
							echo "</script>";
						}
						else
						{
							echo "<script type='text/javascript'>";
								echo "window.parent.retorna_revenda('$nome','$cnpj','$nome_cidade','$fone','$endereco','$numero','$complemento','$bairro','$cep','$estado','$email'); window.parent.Shadowbox.close();";
							echo "</script>";
						}
					}

					

					if ($login_fabrica == 94)
					{
						$onclick = "onclick= \"javascript: window.parent.retorna_revenda('$nome','$cnpj','$nome_cidade','$fone','$endereco','$numero','$complemento','$bairro','$cep','$estado','$email','$revenda'); window.parent.Shadowbox.close();\"";
					}
					else
					{
						$onclick = "onclick= \"javascript: window.parent.retorna_revenda('$nome','$cnpj','$nome_cidade','$fone','$endereco','$numero','$complemento','$bairro','$cep','$estado','$email'); window.parent.Shadowbox.close();\"";
					}

					$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
					echo "<tr style='background: $cor' $onclick>";
						echo "<td>".verificaValorCampo($cnpj)."</td>";
						echo "<td>".verificaValorCampo($nome)."</td>";
						echo "<td>".verificaValorCampo($bairro)."</td>";
						echo "<td>".verificaValorCampo($nome_cidade)."</td>";
						echo "<td>".verificaValorCampo($estado)."</td>";
					echo "</tr>";
				}
			echo "</tbody>";
		echo "</table>";
		
	}else{
		echo "<div class='lp_msg_erro'>Nehum resultado encontrado</div>";
	}?>
	</body>
</html>

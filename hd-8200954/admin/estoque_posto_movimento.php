<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

//header("Expires: 0");
//header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");

if ($_POST["btn_acao_estoque_pulmao"] && in_array($login_fabrica, array(50,74,134))) {
	if ($_FILES["arquivo_estoque_pulmao"]) {
		$arquivo = $_FILES["arquivo_estoque_pulmao"];

		$tipo_estoque = (in_array($login_fabrica, array(50,74))) ? "pulmao" : "garantia";

		$ext = strtolower(preg_replace("/.+\./", "", basename($arquivo["name"])));

		if (in_array($ext, array("txt", "csv"))) {
			$conteudo_arquivo = explode("\n", file_get_contents($arquivo["tmp_name"]));

			$sql = "SELECT 
						tbl_estoque_posto.posto, 
						tbl_estoque_posto.peca, 
						tbl_estoque_posto.estoque_minimo, 
						tbl_estoque_posto.estoque_maximo,
						tbl_estoque_posto.qtde
					INTO TEMP tmp_estoque_posto 
					FROM tbl_estoque_posto 
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_estoque_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
					WHERE tbl_estoque_posto.fabrica = {$login_fabrica} 
					AND tbl_estoque_posto.tipo = '$tipo_estoque'
					AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'";
			$res = pg_query($con, $sql);

			foreach ($conteudo_arquivo as $key => $linha) {
				if (!strlen(trim($linha))) {
					continue;
				}

				$linha_erro = false;

				if(in_array($login_fabrica, array(50,74))){
					list($posto, $peca, $estoque_min, $estoque_max) = explode(";", $linha);
				}else{
					list($posto, $peca, $qtde, $estoque_min) = explode(";", $linha);
				}
				

				$posto       = trim($posto);
				$peca        = trim($peca);
				$estoque_min = trim($estoque_min);

				if(in_array($login_fabrica, array(50,74))){
					$estoque_max = trim($estoque_max);
				}else{
					$qtde = trim($qtde);
				}
				

				$sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND codigo_posto = '{$posto}'";
				$res = pg_query($con, $sql);

				if (!pg_num_rows($res)) {
					$msg_erro_arquivo_upload .= "Linha {$key}: Posto {$posto} não encontrado<br />";
					$linha_erro = true;
				} else {
					$posto = pg_fetch_result($res, 0, "posto");
				}

				$sql = "SELECT peca FROM tbl_peca WHERE fabrica = {$login_fabrica} AND referencia = '{$peca}'";
				$res = pg_query($con, $sql);

				if ((!pg_num_rows($res))) {
					$msg_erro_arquivo_upload .= "Linha {$key}: Peça {$peca} não encontrada<br />";
					$linha_erro = true;
				} else {
					$peca = pg_fetch_result($res, 0, "peca");
				}

				if (!strlen($estoque_min)) {
					$msg_erro_arquivo_upload .= "Linha {$key}: Estoque Mínimo não informado<br />";
					$linha_erro = true;
				}

				if (!strlen($estoque_max) AND in_array($login_fabrica, array(50,74))) {
					$msg_erro_arquivo_upload .= "Linha {$key}: Estoque Máximo não informado<br />";
					$linha_erro = true;
				}

				if (($estoque_max < $estoque_min) && in_array($login_fabrica, array(50,74))) {
					$msg_erro_arquivo_upload .= "Linha {$key}: Estoque Máximo não pode ser menor que o Estoque Mínimo<br />";
					$linha_erro = true;
				}

				if (!strlen($qtde) AND $login_fabrica == 134) {
					$msg_erro_arquivo_upload .= "Linha {$key}: Quantidade não informada<br />";
					$linha_erro = true;
				}

				if ($linha_erro === true) {
					continue;
				} else {
					$sql = "SELECT * FROM tmp_estoque_posto WHERE posto = {$posto} AND peca = {$peca}";
					$res = pg_query($con, $sql);

					$campo = (in_array($login_fabrica, array(50,74))) ? "estoque_maximo = {$estoque_max}" : "qtde = qtde + {$qtde}";

					if (pg_num_rows($res) > 0) {
						$sql = "UPDATE tbl_estoque_posto 
								SET 
									estoque_minimo = {$estoque_min}, 
									{$campo}
								WHERE fabrica = {$login_fabrica} 
								AND posto = {$posto} 
								AND peca = {$peca}
								AND tipo = '$tipo_estoque'";
						$res = pg_query($con, $sql);

						if (strlen(pg_last_error()) > 0) {
							$msg_erro_arquivo_upload .= "Linha {$key}: Erro ao atualizar estoque<br />";
						} else {
							$sql = "UPDATE tmp_estoque_posto 
									SET 
										estoque_minimo = {$estoque_min}, 
										{$campo}
									WHERE posto = {$posto} 
									AND peca = {$peca}";
							$res = pg_query($con, $sql);
						}
					} else {

						if(in_array($login_fabrica, array(50,74))){
							$campo = "estoque_maximo,qtde";
							$valor = "$estoque_max,0";
						}else{
							$campo = "qtde";
							$valor = $qtde;
						}

						$sql = "INSERT INTO tbl_estoque_posto
								(fabrica, posto, peca, tipo, estoque_minimo, {$campo})
								VALUES
								({$login_fabrica}, {$posto}, {$peca}, '$tipo_estoque', {$estoque_min}, {$valor})";
						$res = pg_query($con, $sql);

						if (strlen(pg_last_error()) > 0) {
							$msg_erro_arquivo_upload .= "Linha {$key}: Erro ao inserir estoque<br />";
						} else {
							$sql = "INSERT INTO tmp_estoque_posto (posto, peca, estoque_minimo, {$campo}) VALUES ({$posto}, {$peca}, {$estoque_min}, {$valor})";
							$res = pg_query($con, $sql);
						}
					}

					if($login_fabrica == 134){
						$sql = "INSERT INTO tbl_estoque_posto_movimento 
																(	fabrica, 
																	posto,  
																	peca, 
																	qtde_entrada,
																	tipo,
																	obs,
																	data
																) VALUES (
																	{$login_fabrica}, 
																	{$posto}, 
																	{$peca}, 
																	{$qtde},
																	'garantia',
																	'Abastecimento de pe&ccedil;a via upload',
																	current_date)";
						$res = pg_query($con,$sql);
					}
				}
			}

			if(in_array($login_fabrica, array(50,74))){

				$sql = "SELECT 
							tbl_posto_fabrica.codigo_posto, 
							tbl_peca.referencia, 
							tmp_estoque_posto.estoque_minimo,
							tmp_estoque_posto.estoque_maximo
						FROM tmp_estoque_posto
						JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tmp_estoque_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
						JOIN tbl_peca ON tbl_peca.peca = tmp_estoque_posto.peca";
				$res = pg_query($con, $sql);

				$rows = pg_num_rows($res);

				if ($rows > 0) {
					if (file_exists("xls/atlas_estoque_peca_posto.csv")) {
						unlink("xls/atlas_estoque_peca_posto.csv");
					}

					$arquivo_download = fopen("xls/atlas_estoque_peca_posto.csv", "w");

					for ($i = 0; $i < $rows; $i++) {
						$posto       = pg_fetch_result($res, $i, "codigo_posto");
						$peca        = pg_fetch_result($res, $i, "referencia");
						$estoque_minimo = pg_fetch_result($res, $i, "estoque_minimo");
						$estoque_maximo = pg_fetch_result($res, $i, "estoque_maximo");

						fwrite($arquivo_download, "{$posto};{$peca};{$estoque_minimo};{$estoque_maximo};\n");
					}

					fclose($arquivo_download);

					$arquivo_download_link = "xls/atlas_estoque_peca_posto.csv";
				}
			}
		} else {
			$msg_erro_arquivo_upload = "O arquivo deve ser do formato txt ou csv";	
		}
	} else {
		$msg_erro_arquivo_upload = "Nenhum arquivo selecionado";
	}
}

$styles = "
<style type='text/css'>
	.menu_top {
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
	}

	.titulo_tabela{
		background-color:#596d9b;
		font: bold 14px 'Arial';
		color:#FFFFFF;
		text-align:center;
	}

	.titulo_coluna{
		background-color:#596d9b;
		font: bold 11px 'Arial';
		color:#FFFFFF;
		text-align:center;
	}

	.msg_erro{
		background-color:#FF0000;
		font: bold 16px 'Arial';
		color:#FFFFFF;
		text-align:center;
		margin: 0 auto;
	}
	.texto_avulso{
		font: 14px Arial; color: rgb(89, 109, 155);
		background-color: #d9e2ef;
		text-align: center;
		width:700px;
		margin: 0 auto;
	}
	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
	}

	.subtitulo{
		background-color: #7092BE;
		font:bold 11px Arial;
		color: #FFFFFF;
	}

	table.tabela tr td{
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}

	.msg_sucesso{
		background-color: green;
		font: bold 16px 'Arial';
		color: #FFFFFF;
		text-align:center;
		margin: 0 auto;
	}
	.frm {
		background-color:#F0F0F0;
		border:1px solid #888888;
		font-family:Verdana;
		font-size:8pt;
		font-weight:bold;
	}
</style>";

$ajax_acerto = $_GET['ajax_acerto'];

if (strlen($ajax_acerto) == 0) {$ajax_acerto = $_POST['ajax_acerto'];}
if (strlen($ajax_acerto) > 0) {
	echo $styles;
	$peca     = $_GET['peca'];
	$posto    = $_GET['posto'];
	$tipo     = $_GET['tipo'];
	$btn_acao = trim($_POST['btn_acao']);
	$hoje     = date("d/m/Y");

	if (strlen($btn_acao) > 0) {

		$data_acerto = $_POST['data_acerto'];
		$qtde_acerto = $_POST['qtde_acerto'];
		$nf_acerto   = $_POST['nf_acerto'];
		$obs_acerto  = $_POST['obs_acerto'];
		$peca        = $_POST['peca'];
		$posto       = $_POST['posto'];
		$tipo        = $_POST['tipo'];
		$estoque_minimo = 0;
		$estoque_maximo = 0;

		if($login_fabrica == 134){
			$numero_pedido = $_POST["numero_pedido"];

			if(strlen(trim($numero_pedido))>0){

				$sqlpedido = "SELECT pedido FROM tbl_pedido where pedido = $numero_pedido and fabrica = $login_fabrica and posto = $posto";
				$resPedido = pg_query($con, $sqlpedido);
				if(pg_num_rows($resPedido)==0){
					$msg_erro .= "Número de pedido inválido. <Br>";
				}
			}
		}		

		$numero_pedido = (strlen($numero_pedido)>0)? $numero_pedido : 'null';

		if(in_array($login_fabrica, array(50,74,134))){
			$estoque_minimo = $_POST['qtde_estoque_minimo'];
			$estoque_maximo = $_POST['qtde_estoque_maximo'];

			$estoque_minimo = (strlen($estoque_minimo) > 0) ? $estoque_minimo : 0;
			$estoque_maximo = (strlen($estoque_maximo) > 0) ? $estoque_maximo : 0;
		}else{
			$estoque_maximo = '0';
			$estoque_minimo = '0';
		}

		if (in_array($login_fabrica, array(30,50,74,134))) {
			$tipo_estoque= $_POST['tipo_estoque'];
		}

		if (strlen($tipo) == 0) {
			$tipo     = "qtde_entrada";
			$operador = " + ";
			$msg_erro = "Por favor, selecione o tipo de movimentação(Entrada ou Saída)";
		} else {
			if ($tipo == "E") {$tipo = "qtde_entrada"; $operador = " + ";}
			if ($tipo == "S") {$tipo = "qtde_saida"; $operador = " - ";}
		}

		if (($login_fabrica == 30 || $login_fabrica == 134) && !$tipo_estoque) {
			$msg_erro = "Por favor, selecione o tipo de estoque(Faturada ou Garantia)";
		}

		if (in_array($login_fabrica, array(50,74)) && !$tipo_estoque) {
			$msg_erro = "Por favor, selecione o tipo de estoque(Antigo ou Pulmão)";
		}


		$data_acerto = fnc_formata_data_pg($data_acerto);
		if (strlen(trim($obs_acerto)) == 0) {
			$msg_erro = "Por favor, informar a observação";
		} else {
			$obs_acerto = "'". $obs_acerto . "'";
		}

		$nf_acerto = (strlen($nf_acerto) == 0) ? "null" : "'". $nf_acerto . "'";

		if (strlen($qtde_acerto) == 0) $msg_erro = "Favor informar quantidade";

		if (strlen($msg_erro) == 0) {
			$sql = "INSERT INTO tbl_estoque_posto_movimento(
								fabrica      ,
								posto        ,
								peca         ,
								$tipo        ,
								data         ,
								obs          ,
								nf           ,
								pedido, ";
			if (in_array($login_fabrica, array(30,50,74,134))) {
				$sql .= "       tipo         , ";
			}
			$sql .= "           admin
						) values (
								$login_fabrica,
								$posto        ,
								$peca         ,
								$qtde_acerto  ,
								$data_acerto  ,
								$obs_acerto   ,
								$nf_acerto    ,
								$numero_pedido, ";
			if (in_array($login_fabrica, array(30,50,74,134))) {
				$sql .= " '$tipo_estoque', ";
			}
			$sql .= " $login_admin)";			
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);

			if (strlen($msg_erro) == 0) {
				$sql = "SELECT peca
						FROM tbl_estoque_posto
						WHERE peca = $peca
						AND posto = $posto ";
				if (in_array($login_fabrica, array(30,50,74,134))) {
					$sql .= "AND tipo = '$tipo_estoque'";
				}

				$sql .= " AND fabrica = $login_fabrica;";

				$res = pg_query($con,$sql);
				if (pg_num_rows($res) > 0) {
					$sql = "UPDATE tbl_estoque_posto set
							qtde = qtde $operador $qtde_acerto ";

					if(in_array($login_fabrica, array(50,74))){

						$sql .= " , estoque_minimo = $estoque_minimo, estoque_maximo = $estoque_maximo ";

					}

					if($login_fabrica == 134){

						$sql .= " , estoque_minimo = $estoque_minimo ";

					}

					$sql .= " WHERE peca  = $peca
							AND posto   = $posto";
					if (in_array($login_fabrica, array(30,50,74,134))) {
						$sql .= " AND tipo = '$tipo_estoque'";
					}

					$sql .= " AND fabrica = $login_fabrica;";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);

				} else {

					if($login_fabrica == 134){
						$sql = "INSERT into tbl_estoque_posto(fabrica, posto, peca, qtde,tipo,estoque_minimo)
							values($login_fabrica,$posto,$peca,$qtde_acerto,'$tipo_estoque', $estoque_minimo)";
					}else{
						$sql = "INSERT into tbl_estoque_posto(fabrica, posto, peca, qtde,tipo,estoque_minimo, estoque_maximo)
							values($login_fabrica,$posto,$peca,$qtde_acerto,'$tipo_estoque', $estoque_minimo, $estoque_maximo)";
					}
							
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}
			}
		}

		if($nf_acerto == "null"){
			$nf_acerto = "";
		}

		echo (strlen($msg_erro) > 0) ? "<div class='msg_erro'>$msg_erro</div>" : "<div class='msg_sucesso'>Atualizado com sucesso!</div>";

		if (strlen($msg_erro) == 0) {

			$sql = "SELECT codigo_posto
					FROM tbl_posto_fabrica
					WHERE fabrica = $login_fabrica
					AND   posto = $posto";
			$res = pg_query($con,$sql);
			$codigo_posto = pg_fetch_result($res,0,'codigo_posto');

			$sql = "SELECT referencia
					FROM tbl_peca
					WHERE fabrica = $login_fabrica
					AND   peca = $peca";
			$res = pg_query($con,$sql);
			$referencia = pg_fetch_result($res,0,'referencia');

			echo '<script>parent.window.location.href="'.$PHP_SELF.'?codigo_posto='.$codigo_posto.'&referencia='.$referencia.'&l=1";</script>';
		}
	}

	if (strlen($peca) > 0 and strlen($posto) > 0) {

		if(in_array($login_fabrica, array(50,74)) and !empty($tipo) AND $tipo == "pulmao"){
			$cond = " AND tbl_estoque_posto.tipo = '$tipo' ";
		}

		$sql = "SELECT tbl_peca.referencia as peca_referencia,
				tbl_peca.descricao  as peca_descricao    ,
				tbl_posto.nome as nome_posto             ,
				tbl_posto_fabrica.codigo_posto           ,
				tbl_estoque_posto.estoque_minimo 		 ,
				tbl_estoque_posto.estoque_maximo     	 ,
				tbl_estoque_posto.qtde
			FROM tbl_estoque_posto
			JOIN tbl_posto on tbl_estoque_posto.posto = tbl_posto.posto
			JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto
			AND  tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_peca on tbl_estoque_posto.peca = tbl_peca.peca
			WHERE tbl_estoque_posto.fabrica = $login_fabrica
			AND   tbl_estoque_posto.posto = $posto
			AND   tbl_estoque_posto.peca = $peca
			$cond";
		//echo nl2br($sql);
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) > 0) {

			$peca_referencia = pg_fetch_result($res,0,peca_referencia);
			$peca_descricao  = pg_fetch_result($res,0,peca_descricao);
			$nome_posto      = pg_fetch_result($res,0,nome_posto);
			$codigo_posto    = pg_fetch_result($res,0,codigo_posto);
			$qtde            = pg_fetch_result($res,0,qtde);
			$estoque_minimo  = pg_fetch_result($res,0,estoque_minimo);
			$estoque_maximo  = pg_fetch_result($res,0,estoque_maximo);

			if ($qtde < 0) {
				$xqtde = $qtde * -1;
			} else {
				$xqtde = $qtde;
			}

		} else {

			$sql = "SELECT tbl_peca.referencia,
			               tbl_peca.descricao ,
						   tbl_posto.nome     ,
						   tbl_posto_fabrica.codigo_posto
						 FROM  tbl_peca
						 JOIN  tbl_posto_fabrica USING(fabrica)
						 JOIN  tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
						 WHERE tbl_posto.posto = $posto
						 AND   tbl_posto_fabrica.fabrica = $login_fabrica
						 AND   tbl_peca.peca = $peca
						 AND   tbl_peca.fabrica = $login_fabrica ";

			$res = pg_query($con,$sql);

			$peca_referencia = pg_fetch_result($res,0,'referencia');
			$peca_descricao  = pg_fetch_result($res,0,'descricao');
			$nome_posto      = pg_fetch_result($res,0,'nome');
			$codigo_posto    = pg_fetch_result($res,0,'codigo_posto');
			$xqtde           = 0;

		}

		if (strlen($msg_erro) > 0) {
			$xqtde = $_POST['qtde_acerto'];
			$tipo  = $_POST['tipo'];
		} ?>

		<script language='javascript' src='ajax.js'></script>
		<script type='text/javascript' src='js/jquery.js'></script>
		<script type='text/javascript' src='js/jquery.alphanumeric.js'></script>
		<script type='text/javascript'>
			$(function(){
				$("#qtde_acerto").numeric();
			});
		</script><?php
		echo "<table cellpadding='3' cellspacing='1' width='100%' align='center' class='formulario'>";
			echo "<tr class='titulo_tabela'>";
				echo "<td>Posto: <b>$codigo_posto</b></td>";
				echo "<td><b>$nome_posto</b></td>";
				echo "<td>Peça: <b>$peca_referencia</b></td>";
				echo "<td>$peca_descricao</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td class='subtitulo' colspan='4'>Qtde Estoque: <B>$qtde</b></td>";
			echo "</tr>";
		echo "</table>";
		echo "<form name='frm_acerto' method='post' action='$PHP_SELF'>";
		echo "<table cellpadding='3' cellspacing='1' class='formulario' width='100%' align='center' border='0'>";
			echo "<tr>";
			echo "<td colspan='3' class='subtitulo' align='center'>Para acertar o estoque do posto basta inserir uma nova movimentação com os valores abaixo:</td>";
			echo "</tr>";
			echo "<tr><td width='10px'>&nbsp;</td>";
			echo "<td><B>Peça: </B>$peca_referencia - $peca_descricao </td>";
			echo "<td><B>Data: </B>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='text' name='data_acerto' size='10' maxlength='10' value='$hoje' class='frm'></td>";
			echo "</tr>";
			echo "<tr><td width='10px'>&nbsp;</td>";
			echo "<td><B>Qtde Estoque: </B> <input type='text' name='qtde_acerto' id='qtde_acerto' size='4' maxlength='4' value='$xqtde' class='frm'></td>";
			echo "<td><B>Nota Fiscal: </B> <input type='text' name='nf_acerto' size='10' maxlength='20' value='".str_replace("'","",$nf_acerto)."' class='frm'></td>";
			echo "</tr>";

			if(in_array($login_fabrica, array(50,74))){
				echo "
					<tr>
						<td></td>
						<td><strong>Estoque Mínimo:</strong> <input type='text' name='qtde_estoque_minimo' size='4' maxlength='4' class='frm' value='$estoque_minimo'></td>
						<td><strong>Estoque Maximo:</strong> <input type='text' name='qtde_estoque_maximo' size='4' maxlength='4' class='frm' value='$estoque_maximo'></td>
					</tr>
				";
			}

			if($login_fabrica == 134){
				echo "
					<tr>
						<td></td>
						<td><strong>Estoque Mínimo:</strong> <input type='text' name='qtde_estoque_minimo' size='4' maxlength='4' class='frm' value='$estoque_minimo'></td>
						<td><strong>Pedido:</strong>
							<input type='text' name='numero_pedido' maxlength='15' size='11' value='' class='frm'></td>
					</tr>
				";
			}

			#HD 159888
			if ($login_fabrica <> 30 && $login_fabrica <> 134 ) {
				if(!in_array($login_fabrica, array(50,74))) {
					$colspan = 2;
				}
				echo "<tr>";
					echo "<td width='10px'>&nbsp;</td>";
					echo "<td colspan='$colspan'>";
						echo "<fieldset style='width:120px'>";
							echo "<legend>Tipo</legend>";
							echo "<input type='radio' name='tipo' value='E'";
							if($tipo=="E") echo "checked";
							echo "> Entrada";if(in_array($login_fabrica, array(50,74))){
					                $tipo = $_POST['tipo'];
					                $conds .= " AND tipo = '$tipo' ";
					        }

							echo "<input type='radio' name='tipo' value='S'";
							if($tipo=="S") echo "checked";
							echo "> Saída";
						echo "</fieldset>";
					echo "</td>";
					if(in_array($login_fabrica, array(50,74))){
						echo "<td>";
							echo "<fieldset style='width:150px'>";
								echo "<legend>Tipo de Estoque</legend>";
								if($login_fabrica != 50){
									echo "<input type='radio' name='tipo_estoque' id='tipo_estoque_antigo' value='estoque' />
										<label for='tipo_estoque_antigo' style='cursor:pointer;'>Antigo</label>";
								}
								echo "<input type='radio' name='tipo_estoque' id='tipo_estoque_pulmao' value='pulmao' />
										<label for='tipo_estoque_pulmao' style='cursor:pointer;'>Pulmão</label>";
							echo "</fieldset>";
						echo "</td>";					}
				echo "</tr>";
			} else {
				echo "</table>";
				echo "<table cellpadding='3' cellspacing='1' class='formulario' width='100%' align='center' border='0'>";
					echo "<tr><td width='10px'>&nbsp;</td>";
						echo "<td colspan='2'>";
							echo "<fieldset style='width:150px'>";
								echo "<legend>Tipo de Estoque</legend>";
								
								if($login_fabrica != 134){
									echo "<input type='radio' name='tipo_estoque' id='tipo_estoque_faturada' value='faturada' ";
									if($tipo_estoque == 'faturada') echo "checked"; echo "/>";
									echo "<label for='tipo_estoque_faturada' style='cursor:pointer;'>Faturada</label>";
								
									echo "<input type='radio' name='tipo_estoque' id='tipo_estoque_garantia' value='garantia' ";
									if($tipo_estoque == 'garantia') echo "checked"; echo "/>";
								}else{
									echo "<input type='radio' name='tipo_estoque' id='tipo_estoque_garantia' value='garantia' checked />";
								}
								echo "<label for='tipo_estoque_garantia' style='cursor:pointer;'>Garantia</label>";
							echo "</fieldset>";
						echo "</td>";
						echo "<td>";
							echo "<fieldset style='width:110px'>";
								echo "<legend>Tipo de Movimentação</legend>";
								echo "<input type='radio' name='tipo' id='tipo_entrada' value='E'";
								if ($tipo == "E") echo "checked";echo ">";
								echo "<label for='tipo_entrada' style='cursor:pointer;'>Entrada</label>";
								echo "<input type='radio' name='tipo' id='tipo_saida' value='S'";
								if ($tipo == "S") echo "checked";echo ">";
								echo "<label for='tipo_saida' style='cursor:pointer'>Saída</label>";
							echo"</fieldset>";
						echo "</td>";
					echo "</tr>";
				echo "</table>";

				echo "<table cellpadding='3' cellspacing='1' class='formulario' width='100%' align='center' border='0'>";
			}

			echo "<tr><td width='10px'>&nbsp;</td>";
			echo "<td colspan='3' align='center'><B>Observação: </B><BR><TEXTAREA NAME='obs_acerto' ROWS='5' COLS='50'  class='frm'>".str_replace("'", "",$obs_acerto)."</TEXTAREA>";
			echo "<input type='hidden' name='posto' value='$posto'>";
			echo "<input type='hidden' name='peca' value='$peca'>";
			echo "<input type='hidden' name='btn_acao' value=''>";
			echo "<input type='hidden' name='ajax_acerto' value='true'>";
			echo "<BR><BR><input type='button' value='Gravar' onclick=\"javascript: if (document.frm_acerto.btn_acao.value == '' ) { document.frm_acerto.btn_acao.value='gravar' ; document.frm_acerto.submit() } else { alert ('Aguarde ') }\" ALT=\"Gravar itens da Ordem de Serviço\" border='0' style=\"cursor:pointer;\">";
			echo "</td>";
			echo "</tr>";
		echo "</table>";
		echo "</form>";
	}
	exit;
}

$ajax = $_GET['ajax'];
if (strlen($ajax) > 0) {
	echo $styles;
	$peca         = $_GET['peca'];
	$posto        = $_GET['posto'];
	$data_inicial = $_GET['data_inicial'];
	$data_final   = $_GET['data_final'];

	$tipo         = $_GET['tipo'];

    if(in_array($login_fabrica, array(30,50,74,134))){
    	if(empty($tipo)){
    		$estoque = null;
    	}else{
        	$estoque = " AND tbl_estoque_posto_movimento.tipo = '$tipo' ";
    	}

    }else{
        $estoque = "AND (tbl_estoque_posto_movimento.qtde_entrada notnull  OR tbl_estoque_posto_movimento.qtde_entrada IS NULL)";
    }

	if (strlen($peca) > 0) {

		if (!in_array($login_fabrica, [3])) {
			$sql = "SELECT 	tbl_estoque_posto_movimento.peca                              ,
							tbl_peca.referencia                                           ,
							tbl_peca.descricao as peca_descricao                          ,
							tbl_os.sua_os                                                 ,
							tbl_os_excluida.sua_os as sua_os_excluida                     ,
							tbl_estoque_posto_movimento.os                                ,
							to_char(tbl_estoque_posto_movimento.data,'DD/MM/YYYY') as data,
							tbl_estoque_posto_movimento.qtde_entrada                      ,
							tbl_estoque_posto_movimento.qtde_saida                        ,
							tbl_estoque_posto_movimento.admin                             ,
							tbl_estoque_posto_movimento.pedido								,
							SUBSTR(tbl_pedido.seu_pedido,4) as seu_pedido,
							tbl_estoque_posto_movimento.obs                               ,
							tbl_estoque_posto_movimento.tipo							  ,
							tbl_estoque_posto_movimento.nf
					FROM  tbl_estoque_posto_movimento
					JOIN  tbl_peca ON tbl_peca.peca =  tbl_estoque_posto_movimento.peca
					AND   tbl_peca.fabrica = $login_fabrica
					LEFT  JOIN tbl_os ON tbl_estoque_posto_movimento.os = tbl_os.os AND tbl_os.fabrica = $login_fabrica
					LEFT  JOIN tbl_os_excluida ON tbl_os_excluida.os = tbl_estoque_posto_movimento.os
					LEFT JOIN tbl_pedido USING(pedido)
					WHERE tbl_estoque_posto_movimento.posto   = $posto
					AND   tbl_estoque_posto_movimento.peca    = $peca
					AND   tbl_estoque_posto_movimento.fabrica = $login_fabrica
	                {$estoque}
					ORDER BY tbl_estoque_posto_movimento.data, tbl_estoque_posto_movimento.data_digitacao ";

			$sql .= ($login_fabrica==1) ? 'ASC ':'DESC';

		} else {

			$sql = "SELECT 	tbl_estoque_posto_movimento.peca                              ,
							tbl_peca.referencia                                           ,
							tbl_peca.descricao as peca_descricao                          ,
							tbl_os.sua_os                                                 ,
							tbl_os_excluida.sua_os as sua_os_excluida                     ,
							tbl_estoque_posto_movimento.os                                ,
							to_char(tbl_estoque_posto_movimento.data,'DD/MM/YYYY') as data,
							tbl_estoque_posto_movimento.qtde_entrada                      ,
							tbl_estoque_posto_movimento.qtde_saida                        ,
							tbl_estoque_posto_movimento.admin                             ,
							tbl_estoque_posto_movimento.pedido								,
							SUBSTR(tbl_pedido.seu_pedido,4) as seu_pedido,
							tbl_estoque_posto_movimento.obs                               ,
							tbl_estoque_posto_movimento.tipo							  ,
							tbl_estoque_posto_movimento.nf
					FROM  tbl_estoque_posto_movimento
					JOIN  tbl_peca ON tbl_peca.peca =  tbl_estoque_posto_movimento.peca
					AND   tbl_peca.fabrica = $login_fabrica
					LEFT  JOIN tbl_os ON tbl_estoque_posto_movimento.os = tbl_os.os AND tbl_os.fabrica = $login_fabrica
					LEFT  JOIN tbl_os_excluida ON tbl_os_excluida.os = tbl_estoque_posto_movimento.os
					LEFT JOIN tbl_pedido USING(pedido)
					WHERE tbl_estoque_posto_movimento.posto   = $posto
					AND   tbl_estoque_posto_movimento.peca    = $peca
					AND   tbl_estoque_posto_movimento.fabrica = $login_fabrica
	                {$estoque}

						UNION

					SELECT DISTINCT tbl_os_item.peca, 
									tbl_peca.referencia,
									tbl_peca.descricao as peca_descricao,
									tbl_os.sua_os,
									tbl_os_excluida.sua_os as sua_os_excluida,
									tbl_os.os,
									TO_CHAR(tbl_os.data_abertura, 'MM/DD/YYYY') as data,
									tbl_os_item.qtde as qtde_entrada,
									0 as qtde_saida,
									0 as admin,
									tbl_os_item.pedido,
									SUBSTR(tbl_pedido.seu_pedido,4) as seu_pedido,
									'Entrada via OS' as obs,
									'' as tipo,
									tbl_faturamento.nota_fiscal as nf
					FROM tbl_os JOIN tbl_os_produto using(os)
					JOIN tbl_os_item using(os_produto)
					JOIN tbl_pedido ON tbl_pedido.pedido = tbl_os_item.pedido
					AND tbl_pedido.fabrica = {$login_fabrica}
					JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca
					JOIN tbl_faturamento_item ON tbl_faturamento_item.os = tbl_os.os 
					AND tbl_faturamento_item.peca = tbl_os_item.peca
					JOIN tbl_faturamento ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
					LEFT  JOIN tbl_os_excluida ON tbl_os_excluida.os = tbl_os.os
					WHERE tbl_os_item.peca 	= {$peca}
					AND tbl_os.posto 		= {$posto} 
					AND tbl_os.fabrica 		= {$login_fabrica}
					AND tbl_os_item.servico_realizado = 20
					AND tbl_os.finalizada IS NULL
					";
		}

		 /*,
				tbl_estoque_posto_movimento.data,
				tbl_estoque_posto_movimento.qtde_saida,
				tbl_estoque_posto_movimento.os HD 151164 */
				/* hd 151164 tirei a ordenação da movimentação por data, e deixei para mostrar na sequencia que foi inserida a movimentação no sistema. Então dá para você ver que está correta, ou seja, saiu uma vez na baixa, entrou uma vez na recusa, e saiu novamente na outra baixa. ass Samuel*/
		//  echo nl2br($sql);
		$res = pg_query($con,$sql);
		# HD 5630 -> AND   (tbl_estoque_posto_movimento.qtde_entrada > 0 OR tbl_estoque_posto_movimento.qtde_entrada IS NULL)
		//	AND   tbl_estoque_posto_movimento.data between '$data_inicial' and '$data_final'
		if (pg_num_rows($res) > 0) {
			if ($login_fabrica <> 30 && $login_fabrica <> 134) {
			echo "<table width='700px' cellpadding='3' cellspacing='1' id='fechar_".pg_fetch_result ($res,0,peca)."' class='titulo_tabela'><tr><td width='95%' align='center'>". pg_fetch_result ($res,0,referencia) . " - " . pg_fetch_result ($res,0,peca_descricao) . "</td><td><a style='color:#FF0000' href='javascript:fechar(". pg_fetch_result ($res,0,peca) .");'>Fechar</a></td></tr></table>";
			}
			echo "<table border='0' cellpadding='3' cellspacing='1' width='700px' class='tabela' align='center'>";
			echo "<tr class='titulo_coluna'>";
			echo "<td>Movimenta&ccedil;&atilde;o</td>";
			if (($login_fabrica == 30) || ($login_fabrica == 134)) {
				echo	"<td>Tipo de Estoque</td>";
			}
			echo "<td>Data</td>";
			echo "<td>Entrada</td>";
			echo "<td>Sa&iacute;da</td>";
			echo ($login_fabrica == 3) ? "<td>Nota Fiscal</td>":"<td>Pedido</td>";
			if($login_fabrica == 1){
				echo "<td>Admin</td>";
			}
			echo "<td>OS</td>";
			if (($login_fabrica == 30) || ($login_fabrica == 134)) {
				echo	"<td>Nota Fiscal</td>";
			}
			echo "<td>Observa&ccedil;&atilde;o</td>";
			if (in_array($login_fabrica, [3])) {
				echo "<td>Saldo</td>";
			}
			echo "</tr>";

			$saldo = 0;

			for ($i = 0; pg_num_rows($res) > $i; $i++) {

				$os              = pg_fetch_result ($res,$i,os);
				$sua_os          = pg_fetch_result ($res,$i,sua_os);
				$sua_os_excluida = pg_fetch_result ($res,$i,sua_os_excluida);
				$referencia      = pg_fetch_result ($res,$i,referencia);
				$peca_descricao  = pg_fetch_result ($res,$i,peca_descricao);
				$data            = pg_fetch_result ($res,$i,data);
				$qtde_entrada    = pg_fetch_result ($res,$i,qtde_entrada);
				$qtde_saida      = pg_fetch_result ($res,$i,qtde_saida);
				$admin           = pg_fetch_result ($res,$i,admin);
				$obs             = pg_fetch_result ($res,$i,obs);
				$nf              = pg_fetch_result ($res,$i,nf);
				$pedido          = pg_fetch_result ($res,$i,pedido);
				$tipo            = pg_fetch_result ($res,$i,tipo);
				$nome_admin_movimento = null;
				if($login_fabrica == 1 and !empty($admin)){
					$sql_admin  = "SELECT login from tbl_admin where admin = $admin";
					$res_admin  = pg_query($con, $sql_admin);
					$nome_admin_movimento = pg_fetch_result($res_admin, 0, 'login');

					$nome_admin_movimento = (empty($nome_admin_movimento)) ? "Automático" : $nome_admin_movimento;
				}

				$seu_pedido = pg_result($res,$i,'seu_pedido');

				$saida_total   = $saida_total + $qtde_saida;
				$entrada_total = $entrada_total + $qtde_entrada;

				$movimentacao = ($qtde_entrada>0) ? "<font color='#35532f'>Entrada</font>" : "<font color='#f31f1f'>Sa&iacute;da</font>";

				$cor = ($i % 2 == 0) ? '#F7F5F0' : "#F1F4FA";

				$saldo += $qtde_entrada;
				$saldo -= $qtde_saida;

				echo "<tr bgcolor='$cor'>";
				echo "<td align='center'>$movimentacao</td>";
				if (($login_fabrica == 30) OR ($login_fabrica == 134)) {
					echo "<td align='center'>".ucfirst($tipo)."</td>";
				}
				echo "<td align='center'>$data</td>";
				echo "<td align='center'>$qtde_entrada &nbsp;</td>";
				echo "<td align='center'>$qtde_saida &nbsp;</td>";
				echo "<td align='center'>";
				if ($login_fabrica != 1) {

					if ($login_fabrica == 15){
						echo "<a href='pedido_admin_consulta.php?pedido=$pedido' target='_blank'>$pedido</a>";
					}else{

						echo ($login_fabrica == 3) ? $nf :"<a href='pedido_finalizado.php?pedido=$pedido' target='_blank'>$pedido</a>";

					}
				}
				else {

					echo ($login_fabrica == 3) ? $nf :"<a href='pedido_finalizado.php?pedido=$pedido' target='_blank'>$seu_pedido</a>";

				}
				echo "</td>";
				if($login_fabrica == 1){
					echo "<td>$nome_admin_movimento</td>";
				}
				if(!empty($sua_os_excluida) AND ($login_fabrica == 50 OR $login_fabrica == 52)){
					echo "<td><a href='os_press.php?os=$os' target='_blank'>$sua_os_excluida </a> </td>";
				}else{
					echo "<td><a href='os_press.php?os=$os' target='_blank'>$sua_os </a> </td>";
				}
				if (($login_fabrica == 30) OR ($login_fabrica == 134)){
					echo "<td align='left'>$nf</td>";
				}

				if($login_fabrica == 15 OR $login_fabrica == 120 OR $login_fabrica == 134){
					echo "<td align='left'>$obs &nbsp;</td>";
				}else{
					if (strlen($sua_os) == 0 and strlen($os) > 0) {
						if ($login_fabrica != 1 and $login_fabrica != 50 and $login_fabrica != 52){
							echo "<td>$sua_os_excluida &nbsp;</td>";
						}
						echo "<td align='left'>Esta OS foi exclu&iacute;da pelo posto. <br>$obs</td>";
					} else {

						if (!in_array($login_fabrica, array(1,3,50,74)) and strlen($sua_os) > 0){

							echo "<td><a href='os_press.php?os=$os' target='_blank'>$sua_os</a>&nbsp;</td>";
						}
						echo "<td align='left'>$obs &nbsp;</td>";
					}
				}
				echo "</td>";

				if (in_array($login_fabrica, [3])) {
					echo "<td>{$saldo}</td>";
				}

				echo "</tr>";

			}

			#HD 159888 INICIO
			if ($login_fabrica == 30) {

				$cond_os = ($login_fabrica==30) ? "tbl_servico_realizado.troca_de_peca IS TRUE" : "tbl_servico_realizado.peca_estoque IS TRUE";

				$sqlOS = "SELECT tbl_os.os                                                     ,
								 tbl_os.sua_os                                                 ,
								 to_char(tbl_os.data_digitacao,'DD/MM/YYYY') as data		   ,
								 tbl_os_item.qtde
						FROM tbl_os

						JOIN tbl_os_produto USING(os)
						JOIN tbl_os_item USING(os_produto)
						JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_os.fabrica = tbl_servico_realizado.fabrica
						WHERE tbl_os.fabrica = $login_fabrica
						AND tbl_os.posto = $posto
						AND tbl_os.data_fechamento IS NULL
						AND tbl_os.excluida IS NOT TRUE
						AND tbl_os_item.servico_realizado IS NOT NULL
						AND $cond_os
						AND tbl_os_item.peca = $peca;";
				#echo nl2br($sqlOS); #exit;
				$resOS = pg_exec($con, $sqlOS);
				if(pg_numrows($resOS)>0){
					$total = $qtde_total;
					$qtde_os_nao_finalizada_total = 0;
					for($y=0; $y<pg_numrows($resOS); $y++){


						$os                          = pg_result($resOS,$y,os);
						$sua_os                      = pg_result($resOS,$y,sua_os);
						$qtde_os_nao_finalizada      = pg_result($resOS,$y,qtde);
						$data_os_nao_finalizada      = pg_result($resOS,$y,data);


						$movimentacao = "<font color='#f31f1f'>Sa&iacute;da</font>";

						$cor_2 = ($y % 2 == 0) ? '#F1F4FA' : "#F7F5F0";

						echo "<tr bgcolor='$cor_2'>";
							echo "<td align='center'>$movimentacao</td>";
							echo "<td align='center'>Venda</td>";
							echo "<td align='center'>$data_os_nao_finalizada</td>";
							echo "<td> &nbsp;</td>";
							echo "<td align='center'>$qtde_os_nao_finalizada</td>";
							echo "<td>&nbsp;</td>";
							echo "<td><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>";
							echo "<td><font color='#f31f1f'><b>Os N&atilde;o Finalizada<b></font></td>";
						echo "</tr>";

						$qtde_os_nao_finalizada_total += $qtde_os_nao_finalizada;
					}

				}

			}

			if ($login_fabrica==30){
				$total = ($entrada_total - $saida_total)-$qtde_os_nao_finalizada_total;
			}else if ($login_fabrica==1){
				$total = $entrada_total - $saida_total;
			}else{
				$total = $saida_total;
			}

			echo "<tr class='titulo_coluna'>";
			if ($login_fabrica == 1 OR $login_fabrica == 30){
				echo "<td colspan='3' align='center'>SALDO TOTAL DE PE&Ccedil;AS</td>";
			}else{
				echo "<td colspan='3' align='center'>TOTAL DE PE&Ccedil;AS USADAS EM OS</td>";
			}
			echo "<td colspan='2' align='center'>";
			echo $total;
			echo "</td>";
			echo "<td  colspan='4' >&nbsp;</td>";
			echo "</tr>";
			echo "</table><BR>";
		}else{
			echo "<BR><center>Nenhum resultado encontrado</center><BR>";
		}
	}
	exit;
}



$ajax_autorizacao = $_GET['ajax_autorizacao'];
if(strlen($ajax_autorizacao)>0){
	$xpecas_negativas = $_GET['xpecas_negativas'];
	$observacao = $_GET['observacao'];
	$xposto     = $_GET['xposto'];
	$xpecas_negativas = "(".$xpecas_negativas.")";

	$sql = "BEGIN TRANSACTION";
	$res = pg_query($con,$sql);

	if(strlen(trim($observacao))==0) {
		$msg_erro = "Por favor, colocar observação";
		echo "Por favor, colocar observação";
	}
	if(strlen($msg_erro)==0) {
		$sql = "SELECT	peca,
						posto,
						(qtde*-1)  as qtde
				from tbl_estoque_posto
				where peca in $xpecas_negativas
				and posto = $xposto
				and fabrica = $login_fabrica";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res)>0){
			for($i=0;pg_num_rows($res)>$i;$i++){
				$posto = pg_fetch_result($res,$i,posto);
				$qtde = pg_fetch_result($res,$i,qtde);
				$peca = pg_fetch_result($res,$i,peca);

				$ysql = "INSERT INTO tbl_estoque_posto_movimento(
							fabrica      ,
							posto        ,
							peca         ,
							qtde_entrada   ,
							data,
							obs,
							admin
							)values(
							$login_fabrica,
							$posto        ,
							$peca         ,
							$qtde         ,
							current_date  ,
							'Automático: $observacao',
							$login_admin
					)";
				
				$yres = pg_query($con,$ysql);
				$msg_erro .= pg_errormessage($con);
				if(strlen($msg_erro)==0){
					$ysql = "SELECT peca
							FROM tbl_estoque_posto
							WHERE peca = $peca
							AND posto = $posto
							AND fabrica = $login_fabrica;";
					$yres = pg_query($con,$ysql);
					if(pg_num_rows($res)>0){
						$ysql = "UPDATE tbl_estoque_posto set
								qtde = qtde + $qtde
								WHERE peca  = $peca
								AND posto   = $posto
								AND fabrica = $login_fabrica;";
						$yres = pg_query($con,$ysql);
						$msg_erro .= pg_errormessage($con);
					}else{
						$ysql = "INSERT into tbl_estoque_posto(fabrica, posto, peca, qtde)
								values($login_fabrica,$posto,$peca,$qtde)";
						$yres = pg_query($con,$ysql);
						$msg_erro .= pg_errormessage($con);
					}
				}
			}
		}
		if (strlen($msg_erro) == 0) {
			$res = pg_query($con,"COMMIT TRANSACTION");
			echo "<span style='background-color: #FF3300;'>Peça(s) aceita(s) com sucesso!</span>";
		}else{
			$res = pg_query($con,"ROLLBACK TRANSACTION");
			echo "<span style='background-color: #FF3300;'>Erro no processo: $msg_erro</span>";
		}
	}
	exit;
}

if($_POST['btn_importa']){
	$arquivo = $_FILES['arquivo'];
	$config["tamanho"] = 2048000; // Tamanho máximo do arquivo (em bytes)
	if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["tmp_name"] != "none"){
		preg_match("/\.(xls){1}$/i", $arquivo["name"], $ext);

		if ($ext[1] <>'xls'){
			$msg_erro = "Arquivo em formato inválido!";
		} else {
			if ($arquivo["size"] > $config["tamanho"])
				$msg_erro = "Arquivo tem tamanho muito grande! Deve ser de no máximo 2MB. Envie outro arquivo.";
		}

		if (strlen($msg_erro) == 0) {
			$aux_extensao = "'".$ext[1]."'";

			$nome_sem_espaco = implode("", explode(" ",$arquivo["name"]));

			$nome_anexo = __DIR__ . "/xls/produto.xls";

			if (copy($arquivo["tmp_name"], $nome_anexo)) {
				require_once 'xls_reader.php';
				$data = new Spreadsheet_Excel_Reader();
				$data->setOutputEncoding('CP1251');
				$data->read('xls/produto.xls');
				$data->sheets[0]['numRows'];
				$res = pg_query ($con,"BEGIN TRANSACTION");

				for ($i = 1; $i <= $data->sheets[0]['numRows']; $i++) {
					$peca   = "";
					$qtde   = "";
					$posto  = "";
					$tipo   = "";
					for ($j = 1; $j <= $data->sheets[0]['numCols']; $j++) {
						if($data->sheets[0]['numCols'] <> 4) {
							$msg_erro .= "Por favor, verificar o conteúdo de Excel, está faltando algumas colunas";
						}
						switch($j) {
							case 1:
								$cnpj = str_replace (".","",$data->sheets[0]['cells'][$i][$j]);
								$cnpj = str_replace ("-","",$cnpj);
								$cnpj = str_replace ("/","",$cnpj);
								$cnpj = str_replace (" ","",$cnpj);
								$cnpj = trim($cnpj);
								$sql = "SELECT tbl_posto.posto
										FROM tbl_posto
										JOIN tbl_posto_fabrica ON tbl_posto.posto= tbl_posto_fabrica.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
										WHERE tbl_posto.cnpj = '$cnpj'";
								$res = pg_query($con,$sql);
								if(pg_num_rows($res) > 0){
									$posto = pg_fetch_result($res,0,0);
								}else{
									$msg_erro .= "Posto ".$data->sheets[0]['cells'][$i][$j]." não encontrado no sistema<br>";
								}
								break;
							case 2:
								$referencia_peca = str_replace (".","",$data->sheets[0]['cells'][$i][$j]);
								$referencia_peca = str_replace ("-","",$referencia_peca);
								$referencia = str_replace ("/","",$referencia_peca);
								$referencia_peca = str_replace (" ","",$referencia_peca);
								$referencia_peca = trim($referencia_peca);
								$sql = " SELECT peca
										FROM tbl_peca
										WHERE fabrica = $login_fabrica
										AND   (tbl_peca.referencia_pesquisa =  '$referencia_peca' or tbl_peca.referencia ='$referencia_peca'); ";
								$res = pg_query($con,$sql);
								if(pg_num_rows($res) > 0){
									$peca = pg_fetch_result($res,0,0);
								}else{
									$msg_erro .= "Peça ".$data->sheets[0]['cells'][$i][$j]." não encontrada no sistema<br>";
								}
								break;
							case 3: $qtde = $data->sheets[0]['cells'][$i][$j];
								$qtde = str_replace(",",".",$qtde);;
							break;
							case 4:
								$tipo = $data->sheets[0]['cells'][$i][$j];
								$tipo = trim($tipo);
							break;
						}
					}

					if(empty($msg_erro) and !empty($peca) and !empty($qtde)) {
						$sql = "SELECT peca
								FROM tbl_estoque_posto
								WHERE fabrica = $login_fabrica
								AND posto = $posto
								AND peca  = $peca
								AND tipo  = '$tipo'";
						$res = pg_query($con,$sql);
						if(pg_num_rows($res) > 0){
							$sql = "UPDATE tbl_estoque_posto SET qtde = qtde + $qtde
									WHERE fabrica = $login_fabrica
									AND posto = $posto
									AND peca  = $peca
									AND tipo  = '$tipo'";
						}else{
							$sql = "INSERT INTO tbl_estoque_posto (
										fabrica        ,
										posto          ,
										peca           ,
										qtde           ,
										tipo
									) VALUES (
										$login_fabrica,
										$posto        ,
										$peca         ,
										$qtde         ,
										lower('$tipo')
							)";
						}
						$res = pg_query ($con,$sql);
						$msg_erro .= pg_errormessage($con);

						if(empty($msg_erro)){
							$sql = "INSERT INTO tbl_estoque_posto_movimento(
													fabrica,
													posto,
													peca,
													data,
													qtde_entrada,
													admin,
													obs,
													tipo) VALUES(
													$login_fabrica,
													$posto,
													$peca,
													current_date,
													$qtde,
													$login_admin,
													'Acerto de estoque',
													lower('$tipo'))";
							$res = pg_query($con,$sql);
							$msg_erro .= pg_errormessage($con);
						}
					}
				}

				if(empty($msg_erro)){
						$res = pg_query ($con,"COMMIT TRANSACTION");
						$msg = "Arquivo importado com sucesso";
				}else{
						$res = pg_query ($con,"ROLLBACK TRANSACTION");
				}

			}
		}
	}

}

$layout_menu = "gerencia";
$titulo = "MOVIMENTAÇÃO DE PEÇAS DO POSTO";
$title = "MOVIMENTAÇÃO DE PEÇAS DO POSTO";
include 'cabecalho.php';
echo '<center>';
include "javascript_pesquisas_novo.php";
echo $styles;
?>
<script language="javascript" src="js/assist.js"></script>
<script language='javascript' src='ajax.js'></script>
<script type="text/javascript" src="js/jquery-1.3.2.js"></script>
<script type="text/javascript" src="js/jquery.corner.js"></script>
<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
<script src="../plugins/shadowbox/shadowbox.js"	type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<!--[if IE]>
<link rel="stylesheet" href="css/thickbox_ie.css" type="text/css" media="screen" />
<![endif]-->
<script language="JavaScript">

$(function () {
	Shadowbox.init();
});

function fechar(peca){
	if (document.getElementById('dados_'+ peca)){
		var style2 = document.getElementById('dados_'+ peca);
		if (style2==false) return;
		if (style2.style.display=="block"){
			style2.style.display = "none";
			document.getElementById('linha_'+ peca).style.display = "none";
		}else{
			style2.style.display = "block";
		}
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
	}
	else
		alert('Preencha toda ou parte da informação para realizar a pesquisa!');
}

function createRequestObject(){
	var request_;
	var browser = navigator.appName;
	if(browser == "Microsoft Internet Explorer"){
		 request_ = new ActiveXObject("Microsoft.XMLHTTP");
	}else{
		 request_ = new XMLHttpRequest();
	}
	return request_;
}

function mostraMovimentacao(peca,posto,data_inicial,data_final,tipo){
	if (document.getElementById('dados_' + peca)){
		var style2 = document.getElementById('dados_' + peca);

		if (style2==false) return;
		if (style2.style.display=="block"){
			$('#dados_'+peca).slideUp("slow");

			$('#linha_'+peca).attr('colspan','100%');
			$('#linha_'+peca).fadeOut("slow");
		}else{
			$('#linha_'+peca).show();
			$('#dados_'+peca).slideDown("slow");

			style2.style.display = "block";
			if ($('#dados_'+peca).attr('rel')!='1'){
			retornaMovimentacao(peca,posto,data_inicial,data_final,tipo);
			}
			$('#dados_'+peca).attr('rel','1');
		}
	}
}

function retornaMovimentacao(peca,posto,data_inicial,data_final,tipo){

	var curDateTime = new Date();
	$.ajax({
		type: "GET",
		url: "<?=$PHP_SELF?>",
		data: 'ajax=true&peca='+ peca +"&posto=" + posto + "&data_inicial=" + data_inicial + "&data_final="+ data_final+"&data="+curDateTime+"&tipo="+tipo ,
		beforeSend: function(){
			$('#dados_'+peca).html("&nbsp;&nbsp;Carregando...&nbsp;&nbsp;<br><img src='js/loadingAnimation.gif'> ");
		},
		error: function (){
			$('#dados_'+peca).html("erro");
		},
		complete: function(http) {
			results = http.responseText;
			$('#dados_'+peca).html(results).addClass('z-index','2');
		}
	});
}

function acertaEstoque(peca,posto){
	var div = document.getElementById('div_acertaEstoque');
	div.style.display = (div.style.display=="") ? "none" : "";
	acertaEstoque_pop(peca,posto);
}
var http4 = new Array();
function acertaEstoque_pop(peca,posto){

	var curDateTime = new Date();
	http4[curDateTime] = createRequestObject();

	url = "<? $PHP_SELF; ?>?ajax_acerto=true";
	http4[curDateTime].open('get',url);
	var campo = document.getElementById('div_acertaEstoque');
	Page.getPageCenterX();
	campo.style.top = (Page.top + Page.height/2)-160;
	campo.style.left = Page.width/2-220;

	http4[curDateTime].onreadystatechange = function(){
		if(http4[curDateTime].readyState == 1) {
			campo.innerHTML = "<font size='1' face='verdana'>Aguarde..</font>";
		}
		if (http4[curDateTime].readyState == 4){
			if (http4[curDateTime].status == 200 || http4[curDateTime].status == 304){

				var results = http4[curDateTime].responseText;
				$( campo ).html( "<div class='msg_sucesso'>" + results + "</div>" );

			}else {
				campo.innerHTML = "Erro";
			}
			//$( campo ).text( results );
		}
	}
	http4[curDateTime].send(null);

}

var Page = new Object();
Page.width;
Page.height;
Page.top;

Page.loadOut = function (){
	document.getElementById('div_acertaEstoque').innerHTML ='';
}
Page.getPageCenterX = function (){
	var fWidth;
	var fHeight;
	//For old IE browsers
	if(document.all) {
		fWidth = document.body.clientWidth;
		fHeight = document.body.clientHeight;
	}
	//For DOM1 browsers
	else if(document.getElementById &&!document.all){
			fWidth = innerWidth;
			fHeight = innerHeight;
		}
		else if(document.getElementById) {
				fWidth = innerWidth;
				fHeight = innerHeight;
			}
			//For Opera
			else if (is.op) {
					fWidth = innerWidth;
					fHeight = innerHeight;
				}
				//For old Netscape
				else if (document.layers) {
						fWidth = window.innerWidth;
						fHeight = window.innerHeight;
					}
	Page.width = fWidth;
	Page.height = fHeight;
	Page.top = window.document.body.scrollTop;
}

var http13 = new Array();
function gravaAutorizao(){
	var xpecas_negativas = document.getElementById('xpecas_negativas').value;
	xpecas_negativas = xpecas_negativas.split(",");
	/*for (i=0; i<5;i++){
		alert(xpecas_negativas[i]);
	}*/
	var xposto = document.getElementById('xposto');
	var autorizacao_texto = document.getElementById('autorizacao_texto');
	var curDateTime = new Date();
	http13[curDateTime] = createRequestObject();
//alert(xpecas_negativas.value);
	url = "<? echo $PHP_SELF;?>?ajax_autorizacao=gravar&xpecas_negativas="+xpecas_negativas+"&observacao="+autorizacao_texto.value + "&xposto="+xposto.value;
	http13[curDateTime].open('get',url);

	var campo = document.getElementById('mensagem');

	http13[curDateTime].onreadystatechange = function(){
		if(http13[curDateTime].readyState == 1) {
			campo.innerHTML = "<img src='imagens_admin/carregando_callcenter.gif' border='0'><font size='1' face='verdana'>Aguarde..</font>";
		}
		if (http13[curDateTime].readyState == 4){
			if (http13[curDateTime].status == 200 || http13[curDateTime].status == 304){


				var results = http13[curDateTime].responseText;

				var procurar = "Peça";
				var posicao = results.search(procurar);


				if(posicao != -1)
					$( campo ).html( "<div class='msg_sucesso' style='width: 700px'>Peça(s) aceita(s) com sucesso!</div>" );
				else
					$( campo ).html( "<div class='msg_erro' style='width: 700px'>" + results + "</div>" );

				//campo.innerHTML = results;


			}else {
				campo.innerHTML = "Erro";
			}
		}
	}
	http13[curDateTime].send(null);
}

function fnc_pesquisa_peca_2 (referencia, descricao) {

	if (referencia.length > 2 || descricao.length > 2) {
		Shadowbox.open({
			content:	"peca_pesquisa_nv.php?referencia=" + referencia + "&descricao=" + descricao,
			player:	"iframe",
			title:		"Pesquisa Peça",
			width:	800,
			height:	500
		});
	}
	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}
}

function retorna_dados_peca (peca, referencia, descricao, ipi, origem, estoque, unidade, ativo, posicao)
{
	gravaDados("referencia", referencia);
	gravaDados("descricao", descricao);
}

function retorna_posto (codigo_posto, posto, nome, cnpj, cidade, estado, credenciamento, num_posto)
{
	gravaDados("codigo_posto", codigo_posto);
	gravaDados("posto_nome", nome);
}
</script>

<?

$btn_acao = $_POST['btn_acao'];

#Caso esteja vazio, pega o valor de l que irá por GET da ThickBox
$btn_acao = !empty($btn_acao) ? $btn_acao : $_GET['l'];
if (strlen($btn_acao)>0){

	$msg_erro = "";

	$codigo_posto = ($_POST['codigo_posto']) ? $_POST['codigo_posto'] : null;
	$posto_nome   = ($_POST['posto_nome']) ? $_POST['posto_nome'] : null;

	$referencia  = ($_POST['referencia']) ? $_POST['referencia'] : null;
	$descricao   = ($_POST['descricao']) ? $_POST['descricao'] : null;

	$tipo  = ($_POST['tipo']) ? $_POST['tipo'] : null;

	if(!$codigo_posto){
		$codigo_posto = ($_GET['codigo_posto']) ? $_GET['codigo_posto'] : null;
		$posto_nome = ' ';
	}

	if(!$referencia){
		$referencia = ($_GET['referencia']) ? $_GET['referencia'] : null;
	}

	$negativo     = $_POST['negativo'];

	if(in_array($login_fabrica, array(30,50,74,134)) AND !empty($_POST['tipo'])){
		$tipo = $_POST['tipo'];
		$conds .= " AND tipo = '$tipo' ";
	}

	if(in_array($login_fabrica, array(50,74))){
		$tipo = $_POST['tipo'];
		$conds .= " AND tipo = '$tipo' ";
	}

	if (strlen($codigo_posto)==0 or strlen($posto_nome)==0){
		$msg_erro = "Escolha o Posto que deseja Pesquisar";
	}else{
		$sql = "SELECT tbl_posto_fabrica.posto,tbl_posto.nome
				FROM tbl_posto_fabrica
				JOIN tbl_posto USING(posto)
				WHERE tbl_posto_fabrica.codigo_posto = '$codigo_posto'
				AND tbl_posto_fabrica.fabrica = $login_fabrica";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res)>0){
			$posto = pg_fetch_result($res,0,'posto');
			$posto_nome = pg_fetch_result($res,0,'nome');
		}else{
			$msg_erro = "Posto não encontrado";
		}

	}

	if (strlen($referencia)>0 and strlen($msg_erro)==0){
		$sql = "SELECT peca,descricao
				FROM tbl_peca
				WHERE tbl_peca.fabrica= $login_fabrica
				and tbl_peca.referencia='$referencia'";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res)>0){
			$peca = pg_fetch_result($res,0,'peca');
			$descricao = pg_fetch_result($res,0,'descricao');
		}else{
			$msg_erro = "Peça não encontrada";
		}

	}

}

if ($_POST["btn_acao_estoque_pulmao"] && in_array($login_fabrica, array(50,74,134))) {
	if (strlen($msg_erro_arquivo_upload) > 0) {
		echo "<div class='msg_erro' style='width: 700px;'>$msg_erro_arquivo_upload</div>";
	}else{
?>
	<div class='msg_sucesso' style='width: 700px;'>
		Upload realizado com sucesso!<br />
	</div>

	<?php
	}
	if (strlen($arquivo_download_link) > 0 AND in_array($login_fabrica,array(74))) {
		echo "<br /><div style='width: 700px; margin: 0 auto; font-size: 14px;'>
			Gerado arquivo com peças que não estavam no upload<br />
			<a style='color: #7893D1; font-size: 14px;' href='{$arquivo_download_link}' target='_blank'>	
			Download
			</a>
		</div><br />";
	}
	?>
<?php
}

echo "<div id='div_acertaEstoque' style='display:none;width:700px; class='formulario'>&nbsp;</div>";
//echo "<div id='mensagem' style='width: 700px'></div>";
$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if(!empty($msg_erro)){
	echo "<div class='msg_erro' style='margin:auto;width:700px;'>$msg_erro</div>";
}

if(!empty($msg)){
	echo "<div class='msg_sucesso' style='margin:auto;width:700px;'>$msg</div>";
}

echo "<form name='frm_consulta' method='post' action='$PHP_SELF' enctype='multipart/form-data'>";
echo "<table cellspacing='1' cellpadding='3' align='center' width='700px' class='formulario'>";
echo "<tr>";
echo "<td colspan='3' class='titulo_tabela'>Parâmetros de Pesquisa</td>";
echo "</tr>";
echo "<tr><td>&nbsp;</td></tr>";
echo "<tr>";

echo "<td width='23%'>&nbsp;</td>";
echo "<td width='180px'>";
echo "Código Posto <br /><input type='text' name='codigo_posto' id='codigo_posto' size='8' value='$codigo_posto' class='frm'>";
?>
<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto ('', document.getElementById('codigo_posto'), '')">
<?
echo "</td>";
echo "<td>";
echo "Nome Posto <br /><input type='text' name='posto_nome' id='posto_nome' size='30' value='$posto_nome' class='frm'>";
?>
<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto ('', '', document.getElementById('posto_nome'))">
<?
echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td width='10%'>&nbsp;</td>";
echo "<td style='padding:10px 0 0 0;'>Referência<br /><input class='frm' type='text' name='referencia' value='$referencia' size='8' maxlength='20'><a href=\"javascript: fnc_pesquisa_peca_2 ($('input[name=referencia]').val(), '')\"><IMG SRC='imagens/lupa.png' style='cursor : pointer'></a></td>";

echo "<td style='padding:10px 0 0 0;'>Descrição <br /><input class='frm' type='text' name='descricao'  value='$descricao' size='30' maxlength='50'>
<a href=\"javascript: fnc_pesquisa_peca_2('', $('input[name=descricao]').val())\">
<IMG SRC='imagens/lupa.png' style='cursor : pointer'></a></td>";
echo "</tr>";

if (($login_fabrica == 30) OR ($login_fabrica == 134)){
        $checked_tipo = ($tipo == "faturada") ? "checked" : "";
?>
        <tr>
                <td width='10%'>&nbsp;</td>
                <td colspan='2' style='padding:10px 0 0 0;'>
                        <fieldset style='width:250px;'>
                                <legend>Tipo Estoque</legend>
                                <input type='radio' name='tipo' value='garantia' checked>Estoque Garantia &nbsp;&nbsp;
				<? if($login_fabrica == 30){ ?>
                                	<input type='radio' name='tipo' value='faturada' <?=$checked_tipo?>>Estoque Faturada
				<? } ?>
                        </fieldset>
                </td>
        </tr>
<?php
}

if (in_array($login_fabrica, array(50,74))){
	$checked_pulmao = ($tipo == "pulmao") ? "checked" : "";
?>
	<tr>
		<td width='10%'>&nbsp;</td>
		<td colspan='2' style='padding:10px 0 0 0;'>
			<fieldset style='width:250px;'>
				<legend>Tipo Estoque</legend>
				<?php if($login_fabrica != 50){ ?><input type='radio' name='tipo' value='estoque' checked>Estoque Antigo &nbsp;&nbsp; <?php } ?>
				<input type='radio' name='tipo' value='pulmao' <?=$checked_pulmao?>>Estoque Pulmão
			</fieldset>
		</td>
	</tr>
<?php
}


echo "<tr>";

echo "<td width='10%'>&nbsp;</td>";
echo "<td colspan='2' style='padding:10px 0 0 0;'>";
echo "<input type='checkbox' name='negativo' value='true'";
if (strlen ($negativo) > 0 ) echo " checked ";
echo "> Apenas peça(s) negativa(s)";
echo "</td>";
echo "</tr>";

if ($login_fabrica==15){
	echo "<tr>";
	echo "<td width='10%'>&nbsp;</td>";
	echo "<td colspan='2' style='padding:10px 0 0 0;'>";
	echo "<input type='checkbox' name='devolucao_obrigatoria' id='devolucao_obrigatoria' value='true' onclick='verificaPecas(this.id)'";
	if ($devolucao_obrigatoria) echo " checked ";
	echo "> Peças de devolução obrigatória";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td width='10%'>&nbsp;</td>";
	echo "<td colspan='2' style='padding:10px 0 0 0;'>";
	echo "<input type='checkbox' name='pecas_plasticas' id='pecas_plasticas' value='true' onclick='verificaPecas(this.id)'";
	if ($pecas_plasticas) echo " checked ";
	echo "> Peças plásticas";
	echo "</td>";
	echo "</tr>";
}

echo "<tr><td>&nbsp;</td></tr>";
echo "<tr>";

echo "<td colspan='3' align='center'><input type='submit' name='btn_acao' value='Pesquisar'>";
echo "</td>";
echo "</tr><tr><td>&nbsp;</td></tr>";
echo "</table>";

	if (in_array($login_fabrica, array(50,74,134))) {
		$tipo_texto = (in_array($login_fabrica, array(50,74))) ? "Pulmão" : "";
	?>
		<table cellspacing='1' cellpadding='3'  align='center' width='700px' class='formulario'>
			<tr>
				<td colspan='3' class='titulo_tabela'>Upload Estoque <?=$tipo_texto?></td>
			</tr>

			<tr><td>&nbsp;</td></tr>

			<tr>
				<td width='10%'>&nbsp;</td>

				<td style='padding:10px 0 0 0;'>
					<b>Layout arquivo txt, csv ( colunas separadas por ; )</b>
					
					<ul>
						<li>Código do Posto</li>
						<li>Referência da Peça</li>
						<? if(in_array($login_fabrica, array(50,74))){ ?>
							<li>Estoque Mínimo</li>
							<li>Estoque Máximo</li>
						<? }else{ ?>
							<li>Qtde</li>
							<li>Estoque Mínimo</li>
						<? } ?>
					</ul>
				</td>

				<td>
					<input type="file" name="arquivo_estoque_pulmao" />
				</td>
			</tr>
			
			<tr><td>&nbsp;</td></tr>

			<tr>
				<td colspan='3' align='center'>
				<?php 
					if($login_fabrica == 134){
						$nome_btn_upload = "Upload Estoque Recompra";
					}else{
						$nome_btn_upload = "Upload Estoque Pulmão";
					}
				?>
					<input type='submit' name='btn_acao_estoque_pulmao' value='<?=$nome_btn_upload?>'>
				</td>
			</tr>
			
			<tr><td>&nbsp;</td></tr>
			
		</table>
	<?php
	}

echo "</form>";

if($login_fabrica == 30){
?>
	<br />
	<form name="frm_importa" method="post" enctype="multipart/form-data">
		<table align="center" width="700" class="formulario">
			<caption class="titulo_tabela">Importar arquivo (.XLS)</caption>
			<tr><td>&nbsp;</td></tr>
			<tr>
				<td align='center' style="font-size:11px;"><b>O arquivo deverá conter os campos: Posto(CNPJ),Peça,Qtde,Tipo('Garantia','Faturada'). <br> Não será necessário cabeçalho.</b></td>
			</tr>
			<tr><td>&nbsp;</td></tr>
			<tr>
				<td align='center'>
					<input type="file" name="arquivo" class="frm">
				</td>
			</tr>
			<tr><td>&nbsp;</td></tr>
			<tr>
				<td align='center'>
					<input type="submit" name="btn_importa" value="Importar">
				</td>
			</tr>
			<tr><td>&nbsp;</td></tr>
		</table>
	</form>
<?
}


if (strlen($btn_acao) > 0) {

	$cond_1 = (strlen($peca)>0) ? "  tbl_estoque_posto.peca = $peca " : " 1=1 ";

	$cond_2 = (strlen($negativo)>0) ? "  tbl_estoque_posto.qtde < 0 " : " 1=1 ";


	if(!$_POST['devolucao_obrigatoria'] || !$_POST['pecas_plasticas']){

		if($_POST['devolucao_obrigatoria'] == 'true'){
			$conds .= " AND tbl_peca.devolucao_obrigatoria IS TRUE ";
		}

		if($_POST['pecas_plasticas'] == 'true'){
			$conds .= " AND tbl_peca.devolucao_obrigatoria IS FALSE ";
		}

	}


	if (strlen($msg_erro) == 0) {

		$sql = "SELECT 	DISTINCT
					tbl_peca.referencia,tbl_peca.peca                   ,
					tbl_peca.descricao                                  ,
					tbl_estoque_posto.qtde                           	,
					tbl_estoque_posto.estoque_minimo 								
					";

		if ($login_fabrica == 15){
			$sql .= "
					, tbl_estoque_posto.consumo_mensal
			";
		}

		$sql .= "
				FROM tbl_estoque_posto
				JOIN tbl_peca on tbl_estoque_posto.peca = tbl_peca.peca				
				WHERE  tbl_estoque_posto.posto = $posto
				AND $cond_1
				AND $cond_2
				$conds
				AND tbl_estoque_posto.fabrica = $login_fabrica
					ORDER BY tbl_peca.descricao";
					
		$res = pg_query ($con,$sql);

		if (pg_num_rows($res) > 0) {

			$data = date ("d/m/Y H:i:s");
			$total = pg_num_rows ($res);

			$fp = fopen ("xls/estoque-posto-movimento-$login_fabrica.xls","w+");

			fputs ($fp,"<html>");
			fputs ($fp,"<head>");
			fputs ($fp,"<title>RELATÓRIO DE ESTOQUE DO POSTO: $codigo_posto - $data - $msg");
			fputs ($fp,"</title>");
			fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
			fputs ($fp,"</head>");
			fputs ($fp,"<body>");
			fputs ($fp,"<TABLE width='700' align='center' border='1' cellspacing='0' cellpadding='1'>\n");

			fputs ($fp, "<tr  align='center'>\n");
			$colspan=($login_fabrica == 15) ? 5 : 3;
			fputs ($fp, "<td colspan='$colspan' bgcolor='#0000FF'><FONT  COLOR='#FFFFFF'><b>RELATÓRIO DE ESTOQUE DO POSTO $codigo_posto - $data - $msg</b></FONT></td>\n");
			fputs ($fp, "</tr>\n");

			fputs ($fp,"<TR class='menu_top'>\n");
			fputs ($fp,"	<TD  bgcolor='#FFCC00'>CODIGO PECA</TD>\n");
			fputs ($fp,"	<TD  bgcolor='#FFCC00'>DESCRICAO PECA</TD>\n");
			fputs ($fp,"	<TD  bgcolor='#FFCC00'>ESTOQUE ATUAL</TD>\n");
			if($login_fabrica == 15){
				fputs ($fp,"	<TD  bgcolor='#FFCC00'>ESTOQUE DE SEGURANCA</TD>\n");
				fputs ($fp,"	<TD  bgcolor='#FFCC00'>CONSUMO MENSAL</TD>\n");
			}
			fputs ($fp,"</TR>\n");

			if ($login_fabrica == 1) {

				for ($x = 0; pg_num_rows($res) > $x; $x++) {
					$peca              = pg_fetch_result($res, $x, 'peca');
					$pecas_negativas[] = $peca;
				}

				echo "<div id='div_estoque' style='margin 0 auto;width:700px;'>";
					echo "<table cellpadding='3' cellspacing='1' align='center' width='700px' class='formulario'>";
						echo "<tr>";
							echo "<td align='center' class='texto_avulso'><strong>Atenção</strong><BR>";
							echo "Para <strong>ACEITAR TODAS</strong> as peças que estão <font color='#FF3300'>negativas</font> do <br />estoque informe o motivo e clique em continuar.<br />";
							echo "<textarea name='autorizacao_texto' class='frm' id='autorizacao_texto' rows='5' cols='40'></textarea>";
							echo "<input type='hidden' name='xposto' id='xposto' value='$posto'>";
							echo "<input type='hidden' name='xpecas_negativas' id='xpecas_negativas' value='".implode(",",$pecas_negativas)."'>";
							echo "<br/><br/><input type=\"button\" value=\"Confirmar\" border='0' style='cursor:pointer;' onClick='gravaAutorizao();'></td>";
						echo "</tr>";
					echo "</table><br />";
				echo "</div>";

			} ?>

			<br />
			<br />

			<table class='tabela' width="700px" cellspacing="1" cellpadding="3" align='center'>
				<thead>
					<tr class='titulo_coluna'>
						<td>Peça</td>
						<td>Descrição</td>
							<?php if($login_fabrica == 15){?>
								<td>Estoque de Segurança</td>
								<td>Consumo Mensal</td>
							<?}?>
							<td colspan='2'>Saldo</td>
						<td>Opção</td>
					</tr>
				</thead>
				<tbody><?php

				for ($x = 0; pg_num_rows($res) > $x; $x++) {

					$peca            = pg_fetch_result($res,$x,'peca');
					$peca_referencia = pg_fetch_result($res,$x,'referencia');
					$peca_descricao  = pg_fetch_result($res,$x,'descricao');
					$qtde 			 = pg_fetch_result($res,$x,qtde);
					$estoque_minimo = pg_fetch_result($res,$x,'estoque_minimo');
					if ($login_fabrica == 15){
						$consumo_mensal = pg_fetch_result($res,$x,'consumo_mensal');
					}

					if (in_array($login_fabrica, [3])) {

						$sqlMovimentoPedido = " SELECT COALESCE(SUM(tbl_os_item.qtde), 0) as qtde_pedido
												FROM tbl_os 
												JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
												JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
												AND tbl_os_item.fabrica_i = {$login_fabrica}
												AND tbl_os_item.peca = {$peca}
												JOIN tbl_faturamento_item ON tbl_faturamento_item.os = tbl_os.os 
												AND tbl_faturamento_item.peca = tbl_os_item.peca
												WHERE tbl_os.posto 		= {$posto}
												AND tbl_os.fabrica 		= {$login_fabrica}
												AND tbl_os_item.servico_realizado = 20
												AND tbl_os.finalizada IS NULL
												AND tbl_os.data_digitacao > '2016-01-01 00:00:00'";
						$resMovimentoPedido = pg_query($con, $sqlMovimentoPedido);

						$qtde += pg_fetch_result($resMovimentoPedido, 0, 'qtde_pedido');

					}
					
					fputs ($fp,"<TR>\n");
					fputs ($fp,"	<TD  bgcolor='#FFCC00'>$peca_referencia</TD>\n");
					fputs ($fp,"	<TD  bgcolor='#FFCC00'>$peca_descricao</TD>\n");
					fputs ($fp,"	<TD  bgcolor='#FFCC00'>$qtde</TD>\n");
					if($login_fabrica == 15){
						fputs ($fp,"	<TD  bgcolor='#FFCC00'>$estoque_minimo</TD>\n");
						fputs ($fp,"	<TD  bgcolor='#FFCC00'>$consumo_mensal</TD>\n");
					}
					fputs ($fp,"</TR>\n");

					$cor = ($x % 2 == 0) ? "#F7F5F0" : "#F1F4FA";
					if ($qtde > -20 and $login_fabrica == 1) $cor = "#FF9933";

					if (in_array($login_fabrica, array(50,74)) && $tipo == "estoque") {
						$qtde = 0;

						$sqlSaldo = "SELECT
										qtde_entrada AS saldo_entrada,
										qtde_saida AS saldo_saida
									FROM tbl_estoque_posto_movimento
									WHERE fabrica = {$login_fabrica}
									AND posto = {$posto}
									AND peca = {$peca}
									AND tipo != 'pulmao'";
						$resSaldo = pg_query($con, $sqlSaldo);

						$rowsSaldo = pg_num_rows($resSaldo);

						for ($k = 0; $k < $rowsSaldo; $k++) {
							$saldo_entrada = pg_fetch_result($resSaldo, $k, "saldo_entrada");
							$saldo_saida   = pg_fetch_result($resSaldo, $k, "saldo_saida");

							if (!strlen($saldo_entrada)) {
								$saldo_entrada = 0;
							}

							if (!strlen($saldo_saida)) {
								$saldo_saida = 0;
							}

							$qtde = $qtde - $saldo_saida;
							$qtde = $qtde + $saldo_entrada;
						}
					}

					?>

					<tr bgcolor='<? echo $cor;?>'>
						<td align='left'><?php
						echo "<a href=\"javascript:mostraMovimentacao($peca,$posto,'$data_inicial','$data_final','$tipo');\">$peca_referencia</a>
									<input type='hidden' id='peca_$x' name='peca_$x' value='$peca;'>
								</td>
								<td><a href=\"javascript:mostraMovimentacao($peca,$posto,'$data_inicial','$data_final','$tipo');\"> $peca_descricao</a></td>";

						if($login_fabrica == 15){?>
							<td align="center"> <?php echo ($estoque_minimo) ? $estoque_minimo : 0;?></td>
							<td align="center"> <?php echo ($consumo_mensal) ? $consumo_mensal : 0;?></td>
						<?} ?>

							<td align='center' colspan='2'> <?=$qtde;?>&nbsp;
								<input type='hidden' id='qtde_pendente_<?=$x;?>' name='qtde_pendente_<? echo $x; ?>' value='<? echo $qtde; ?>' />
							</td>
							<td align='center' colspan='2'>
								<input type='button' value='Acertar Estoque' alt="<?=$PHP_SELF.'?ajax_acerto=true&peca='.$peca.'&posto='.$posto.'&tipo='.$tipo.'&keepThis=trueTB_iframe=true&height=400&width=500&tipo='.$tipo?>" class="thickbox" />
							</td>

					</tr><?php
					echo "<tr>";
						echo "<td colspan='100%' id='linha_$peca' rel='' style='display:none;'>";
							echo "<div id='dados_$peca' style='display:none;border: 1px solid #949494;'></div>";
						echo "</td>";
					echo "</tr>";

				}

				echo "</tbody>";
			echo "</table>";

			fputs ($fp,"</body>");
			fputs ($fp,"</html>");
			fclose ($fp);

			echo "<div align='center'>";
				echo "<input type='button' value='Download em Excel' onclick='window.location.href=\"xls/estoque-posto-movimento-$login_fabrica.xls\"' >";
			echo "</div>";

		} else { ?>
			<br />
			<div>Nenhum resultado Encontrado</div>
			<?php if($peca){ ?>
				<input type='button' value='Acertar Estoque' alt="<?=$PHP_SELF.'?ajax_acerto=true&peca='.$peca.'&posto='.$posto.'&tipo='.$tipo.'&keepThis=trueTB_iframe=true&height=400&width=500'?>" class="thickbox">
			<?php
			}
		}

	} else {?>
		<div class="msg_erro" id="msg_erro" style="display:none;"><?=$msg_erro?></div>
		<script type="text/javascript">
			$("#msg_erro").appendTo("#mensagem").fadeIn("slow");
		</script><?php
	}

}

include "rodape.php";

?>

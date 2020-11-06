<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'includes/funcoes.php';
	include "autentica_admin.php";
	header("Cache-Control: no-cache, must-revalidate");
	header('Pragma: no-cache');
	$layout_menu = "gerencia";
	$title = "RELATÓRIO DE OS COM DESLOCAMENTO";
	include "cabecalho.php";
	
?>

<style type="text/css">
	.titulo_tabela{
		background-color:#596d9b;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.titulo_coluna{
		background-color:#596d9b;
		font: bold 11px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.msg_erro{
		background-color:#FF0000;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
		text-align:left;
	}
	table.tabela tr td{
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}
	div.formulario table{
		padding:10px 0 10px;
		text-align:left;
	}
	div.formulario table tr td{ min-width:100px; }
	div.formulario form p{ margin:0; padding:0; }
</style>

<?php include "javascript_calendario.php";?>

<div class="formulario" style="width:700px; margin:auto;">
	<div id="msg"></div>
	<div class="titulo_tabela">Parâmetros de Pesquisa</div>
	<form action="<?=$_SERVER['PHP_SELF'];?>" method="POST">
		<table cellspacing="1" align="center">
			<tr>
				<td>
					<label for="mes">Mês</label><br />
					<?php
					
						$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
						
					
					?>
					
					<select name="mes" size="1" class="frm" id="mes">
						<option value=''></option>
						<?php
							for ($i = 1 ; $i <= count($meses) ; $i++) {
								echo "<option value='$i'";
								if ($mes == $i) echo " selected";
								echo ">" . $meses[$i] . "</option>";
							}
						?>
					</select>
					
				</td>
				<td>
					<label for="ano">Ano</label><br />
					<select name="ano" size="1" class="frm" id="ano">
						<option value=''></option>
						<?php
							for ($i = date("Y") ; $i >= 2003 ; $i--) {
								echo "<option value='$i'";
								if ($ano == $i) echo " selected";
								echo ">$i</option>";
							}
						?>
					</select>
				</td>
			<tr>
				<td colspan="2" style="padding-top:15px; text-align:center; padding-right:20px;">
					<input type="submit" name="gerar" value="Gerar" />
				</td>
			</tr>
		</table>
	</form>
	<!-- resultado da requisição -->
	<?php 
		if ( isset($_POST['gerar']) ) {
		
			$mes = trim ( $_POST['mes'] );
		
			$ano = trim ( $_POST['ano'] );
		
			if(strlen($mes)==0){
				$msg_erro = "Informe o mês para pesquisa.";
			}
		
			else if(strlen($ano)==0){
				$msg_erro = "Informe o ano para pesquisa.";
			}
		
			if (strlen($mes) > 0 AND strlen($msg_erro)==0) {
				$xdata_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
				$xdata_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
			}
			
			if(strlen($msg_erro)==0) {

				// LEFT JOIN por causa das OS com deslocamento que não entram em intervenção. HD 732742

				$sql = "SELECT tbl_os.os,
						tbl_posto.cnpj::text,
						tbl_posto.nome,
						tbl_posto_fabrica.contato_cidade,
						tbl_posto_fabrica.contato_estado,
						tbl_os.consumidor_nome,
						tbl_os.consumidor_cidade,
						tbl_os.consumidor_estado,
						tbl_produto.descricao,
						tbl_os.qtde_km,
						tbl_os.qtde_km_calculada AS valor_km,
						tbl_status_os.descricao AS status_os
						
						FROM tbl_os
						JOIN tbl_posto USING (posto)
						JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
						JOIN tbl_produto USING (produto)
						LEFT JOIN tbl_status_os ON status_os = (SELECT status_os 
																FROM tbl_os_status 
																WHERE os = tbl_os.os
																AND status_os IN(98,99,100,101)
																ORDER BY data DESC
																LIMIT 1)
						
						WHERE tbl_os.fabrica = $login_fabrica
						AND tipo_atendimento IN (21,22)
						AND tbl_os.data_digitacao BETWEEN '$xdata_inicial' AND '$xdata_final'";

				//echo nl2br($sql); die;
				$res = pg_query($con,$sql);
				if (pg_numrows($res) > 0) {

					//gera xls
					$arq      = "xls/relatorio-os-km-$login_fabrica.xls";
					$arq_html = "/tmp/assist/relatorio-os-km-gera.html";
					
					if(file_exists($arq_html))
						exec ("rm -f $arq_html");
					if(file_exists($arq))
						exec ("rm -f $arq");
					$fp = fopen($arq_html,"w");

					fputs($fp, '
						<html>
							<head>
								<title>RELATÓRIO DE OS COM DESLOCAMENTO</title>
							</head>
							<body>
								<table border="1">
									<tr>
										<th>OS</th>
										<th>CNPJ</th>
										<th>Nome Posto</th>
										<th>Cidade Posto</th>
										<th>Estado Posto</th>
										<th>Nome Consumidor</th>
										<th>Cidade Consumidor</th>
										<th>Estado Consumidor</th>
										<th>Produto</th>
										<th>Qtde KM</th>
										<th>Valor total KM</th>
										<th>Status</th>
									</tr>
					');

					for ( $i=0; $i < pg_numrows($res); $i++ ) {
					
						$cnpj = pg_result($res,$i, 'cnpj');
						$cnpj .= '.';

						$status_os = pg_result($res,$i, 'status_os');
						$status_os = empty($status_os) ? 'Aprovada Automaticamente' : $status_os;
						$qtde_km   = str_replace('.',',',pg_result($res,$i, 'qtde_km') );
						fputs($fp, '
							<tr>
								<td>'. pg_result($res,$i, 'os') .'</td>
								<td>'. $cnpj .'</td>
								<td>'. pg_result($res,$i, 'nome') .'</td>
								<td>'. pg_result($res,$i, 'contato_cidade') .'</td>
								<td>'. pg_result($res,$i, 'contato_estado') .'</td>
								<td>'. pg_result($res,$i, 'consumidor_nome') .'</td>
								<td>'. pg_result($res,$i, 'consumidor_cidade') .'</td>
								<td>'. pg_result($res,$i, 'consumidor_estado') .'</td>
								<td>'. pg_result($res,$i, 'descricao') .'</td>
								<td align="right">'. $qtde_km .'</td>
								<td align="right">'. number_format( pg_result($res,$i, 'valor_km'), 2, ',', '.' ) .'</td>
								<td>'. $status_os .'</td>
							</tr>
						');

					}

					fputs($fp, "\t\t	</table>
							</body>
						</html>" . PHP_EOL);

					rename($arq_html, $arq);

					echo '<hr /><center><button type="button" onclick="window.open(\'xls/relatorio-os-km-'.$login_fabrica.'.xls\')">Download em EXCEL</button></center><br />';
					
					$sql = "SELECT email
							FROM tbl_admin
							WHERE admin = $login_admin;";
							
					$res   = pg_query($con,$sql);
					$email = @pg_result($res,0,0);
					
					$headers  = 'MIME-Version: 1.0' . "\r\n";
					$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
					$headers .= 'To: <'.$email.'>' . "\r\n";
					$headers .= 'From: Suporte Telecontrol <helpdesk@telecontrol.com.br>' . "\r\n";
					
					
					
					$assunto = 'Relatório OS com Deslocamento';
					
					$msg	 = "Relatório do mês $mes/$ano gerado com sucesso, clique <a href='".$_SERVER['SERVER_NAME']. dirname($_SERVER['REQUEST_URI']) . "/xls/relatorio-os-km-$login_fabrica.xls' target='_blank'>aqui</a> para fazer o download.<br />
								------<br />
								Atte,<br />
								Suporte Telecontrol.<br />
								Essa é uma mensagem automática, não responda este e-mail.";
					
					mail ($email, utf8_encode($assunto), utf8_encode($msg), $headers);

				}
				else
					echo '<p style="text-align:center;">
							Não foram encontrados resultados para esta pesquisa.
						  </p>';
			}
			else
				echo '<div id="erro" class="msg_erro" style="display:none;">'.$msg_erro.'</div>';
		}
	?>
</div>

<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript">
	<?php if ( !empty($msg_erro) ){ ?>
		$("#erro").appendTo("#msg").fadeIn("slow");
	<?php } ?>
</script>

<?php include 'rodape.php'; ?>

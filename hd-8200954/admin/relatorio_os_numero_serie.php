<?php
	include "dbconfig.php";
    include "includes/dbconnect-inc.php";
    include "autentica_admin.php";
    include "funcoes.php";
	
	$situacao_os = "todas";
	if($_POST){

		$serie_inicial = strtoupper($_POST['serie_inicial']);
		$serie_final   = strtoupper($_POST['serie_final']);
		$situacao_os   = $_POST['situacao_os'];
		$gera_xls      = $_POST['gerar_xls'];
		$btn_acao      = $_POST['btn_acao'];
		if ($serie_inicial) {
			$msg_erro = (!preg_match('/^[A-Z]{2}\d{7}$/', $serie_inicial)) ? 'Número de Serie Inicial Inválido!' :'';
		}

		if ($serie_final) {
			$msg_erro .= (!preg_match('/^[A-Z]{2}\d{7}$/', $serie_final)) ? 'Número de Serie Final Inválido!' :'';
		}

	}

	
?>

<?php
	$title = "RELATÓRIO DE OS POR NÚMERO DE SÉRIE (MASTERFRIO)";
	include "cabecalho.php";	
    include "javascript_pesquisas.php";
	include "javascript_calendario.php";
?>
<script type='text/javascript' src='js/bibliotecaAJAX.js'></script>
<script type="text/javascript" charset="utf-8" src="js/jquery.alphanumeric.js"></script>
<script language="JavaScript">


</script>

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
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
	text-align:center;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
	border-collapse: collapse;
	border:1px solid #596d9b;
}



</style>
<br />
<div class="texto_avulso" style="width:700px;"><b>OBS:</b> O número de série deverá conter 2 letras seguidas por 7 números. Ex: AB1234567. </div>
<br />

<form name='frm_pesquisa' action="<?php echo $PHP_SELF ;?>" method="post">
	<table align="center" class="formulario espaco" width="700" >
		<?php
			if(strlen($msg_erro) > 0){
		?>
				<tr class="msg_erro" >
					<td colspan="4">
						<?php echo $msg_erro; ?>
					</td>
				</tr> 
		<?php
			}
		?>
		
		<tr class="titulo_tabela" >
			<td colspan="2" height='25' valign='middle'>
				Parâmetros de Pesquisa
			</td>
		</tr>
		<tr><td colspan='2'>&nbsp;</td></tr>
		<tr>
			<td width='150' style='padding-left:200px;'>
				Nº Série Inicial <br />
				<input type="text" name="serie_inicial" id="serie_inicial" 
						class="frm" size="20" maxlength="18" value="<?php echo $serie_inicial?>" onkeyup="somenteMaiusculaSemAcento(this);"/>				
			</td>
			<td>
				Nº Série Final <br />
				<input type="text" name="serie_final" id="serie_final" class="frm" value="<?php echo $serie_final; ?>" size="20" maxlength="50" onkeyup="somenteMaiusculaSemAcento(this);"/>
			</td>
		</tr>

		<tr>
			<td colspan='2' style='padding-left:200px;'>
				<fieldset style='width:275px'>
					<legend>Situação da OS</legend>
					<table width='100%'>
						<tr>
							<td>
								<input type='radio' name='situacao_os' value='abertas' <? if($situacao_os=="abertas") echo "checked";?>>&nbsp;Aberta
							</td>
							<td>
								<input type='radio' name='situacao_os' value='fechadas' <? if($situacao_os=="fechadas") echo "checked";?>>&nbsp;Fechadas
							</td>
							<td>
								<input type='radio' name='situacao_os' value='todas' <? if($situacao_os=="todas") echo "checked";?>>&nbsp;Todas
							</td>
						</tr>
					</table>
				</fieldset>
			</td>
			
		</tr>
		<tr>
			<td colspan="2" style='padding-left:200px;'>
				<input type='checkbox' name='gerar_xls' value='xls' <? if(strlen($gera_xls)>0) echo "checked";?>> Gerar Excel
			</td>
		</tr>
		<tr>
			<td colspan="3" align="center" style="padding-left:0px;">
				<input type="submit" value="Pesquisar" name='btn_acao'>
			</td>
		</tr>
	</table>
</form>
	<br />
<?php
	if(strlen($msg_erro)==0 and strlen($btn_acao) > 0){

		if($serie_inicial and !$serie_final){
			$cond = "AND tbl_os.serie BETWEEN '$serie_inicial' and 'ZZ9999999'";
		}

		if(!$serie_inicial and $serie_final){
			$cond = "AND tbl_os.serie BETWEEN 'AA0000000' and '$serie_final'";
		}

		if($serie_inicial and $serie_final){
			$cond = "AND tbl_os.serie BETWEEN '$serie_inicial' and '$serie_final'";
		}

		if($situacao_os == "abertas"){
			$cond2 = "AND tbl_os.finalizada IS NULL";
		}

		if($situacao_os == "fechadas"){
			$cond2 = "AND tbl_os.finalizada IS NOT NULL";
		}

		$sql = "SELECT tbl_os.serie, 
					   tbl_produto.referencia, 
					   tbl_posto.nome, 
					   tbl_posto.estado, 
					   tbl_defeito_constatado.descricao AS defeito
					FROM  tbl_os 
					JOIN  tbl_produto ON tbl_os.produto = tbl_produto.produto
					JOIN  tbl_posto ON tbl_os.posto=tbl_posto.posto 
					JOIN  tbl_defeito_constatado ON tbl_os.defeito_constatado=tbl_defeito_constatado.defeito_constatado
					WHERE tbl_os.fabrica = $login_fabrica 
					AND   tbl_os.posto <> 6359
					$cond
					$cond2";
	
		$res = pg_query($con, $sql);
		$total = pg_num_rows($res);
		if($total > 0 and $total <= 5000){
			$arquivo = "relatorio-os-numero-serie-".$login_fabrica.".xls";
			echo `rm /var/www/assist/www/admin/xls/$arquivo`;
			$fp = fopen ("/var/www/assist/www/admin/xls/$arquivo","w");
			$conteudo = "<html><body>";
			$conteudo .= "<table  width='700' align='center' cellspacing='1' class='tabela'>\n";
			$conteudo .= "<caption class='titulo_tabela'>OS Geradas Número de Série</caption>";
			$conteudo.= "<tr class='titulo_coluna' bgcolor='#596d9b'>
							<th>Nº Série</th>
							<th>Ref. Produto</th>
							<th>Razão Social do Posto</th>
							<th>UF</th>
							<th>Defeito Constatado</th>
						</tr>\n";
						
			for($i=0;$i< $total;$i++){
				$numero_serie = pg_result($res,$i,serie);
				$referencia   = pg_result($res,$i,referencia);
				$posto_nome   = pg_result($res,$i,nome);
				$estado       = pg_result($res,$i,estado);
				$defeito      = pg_result($res,$i,defeito);			
				
				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
			

				$conteudo.="<tr bgcolor='$cor'>
						<td>$numero_serie</td>
						<td>$referencia</td>
						<td align='left'>&nbsp;$posto_nome</td>
						<td>$estado</td>
						<td>$defeito</td>
					</tr>\n";
			}
			$conteudo .= "<tr class='subtitulo'><td colspan='5' align='center'>Total de OS - $total</td></tr>";
			$conteudo .= "</table>\n";

			$sql = "SELECT count(tbl_os.os) AS def_total, tbl_defeito_constatado.descricao AS defeito
					FROM  tbl_os 
					JOIN  tbl_produto ON tbl_os.produto = tbl_produto.produto
					JOIN  tbl_posto ON tbl_os.posto=tbl_posto.posto 
					JOIN  tbl_defeito_constatado ON tbl_os.defeito_constatado=tbl_defeito_constatado.defeito_constatado
					WHERE tbl_os.fabrica = $login_fabrica 
					AND   tbl_os.posto <> 6359
					$cond
					$cond2
					GROUP BY(tbl_defeito_constatado.descricao)";
			$res = pg_query($con, $sql);
			$total2 = pg_num_rows($res);
			$conteudo .= "<br /><table width='700' align='center' cellspacing='1' class='tabela'>\n";
			$conteudo .= "<caption class='titulo_tabela' bgcolor='#596d9b'>Quantidade por Defeito</caption>\n";
			$conteudo .= "<tr class='titulo_coluna' bgcolor='#596d9b'><th>Defeito</th><th>Total</th><th>%</th></tr>\n";
			
			for($i=0;$i< $total2;$i++){
				$defeito      = pg_result($res,$i,defeito);
				$total_def    = pg_result($res,$i,def_total);
				$porcentagem  = ($total_def / $total)*100;
				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
				$conteudo.="<tr bgcolor='$cor'>
						<td align='left'>&nbsp;$defeito</td>
						<td>$total_def</td>
						<td>".number_format($porcentagem,2, ',', '.')." %</td>
					</tr>\n";
			}
			$conteudo .= "</table>\n";

			$sql = "SELECT count(tbl_os.os) AS def_total, tbl_posto.estado
					FROM  tbl_os 
					JOIN  tbl_produto ON tbl_os.produto = tbl_produto.produto
					JOIN  tbl_posto ON tbl_os.posto=tbl_posto.posto 
					JOIN  tbl_defeito_constatado ON tbl_os.defeito_constatado=tbl_defeito_constatado.defeito_constatado
					WHERE tbl_os.fabrica = $login_fabrica
					AND   tbl_os.posto <> 6359
					$cond
					$cond2
					GROUP BY(tbl_posto.estado)";
			$res = pg_query($con, $sql);
			$total3 = pg_num_rows($res);
			$conteudo .= "<br /><table width='700'align='center' cellspacing='1' class='tabela'>\n";
			$conteudo .= "<caption class='titulo_tabela'>Quantidade por Estado</caption>\n";
			$conteudo .= "<tr class='titulo_coluna' bgcolor='#596d9b'><th>Estado</th><th>Total</th><th>%</th></tr>\n";
			
			for($i=0;$i< $total3;$i++){
				$estado       = pg_result($res,$i,estado);
				$total_def    = pg_result($res,$i,def_total);
				$porcentagem  = ($total_def / $total)*100;
				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
				$conteudo.="<tr bgcolor='$cor'>
						<td>$estado</td>
						<td>$total_def</td>
						<td>".number_format($porcentagem,2, ',', '.')." %</td>
					</tr>\n";
			}

			$conteudo .= "</table>\n";
			
			$sql = "SELECT count(tbl_os.os) AS def_total, tbl_posto.nome
					FROM  tbl_os 
					JOIN  tbl_produto ON tbl_os.produto = tbl_produto.produto
					JOIN  tbl_posto ON tbl_os.posto=tbl_posto.posto 
					JOIN  tbl_defeito_constatado ON tbl_os.defeito_constatado=tbl_defeito_constatado.defeito_constatado
					WHERE tbl_os.fabrica = $login_fabrica 
					AND   tbl_os.posto <> 6359
					$cond
					$cond2
					GROUP BY(tbl_posto.nome)";
			
			$res = pg_query($con, $sql);
			$total4 = pg_num_rows($res);
			$conteudo .= "<br /><table width='700' align='center' cellspacing='1' class='tabela'>\n";
			$conteudo .= "<caption class='titulo_tabela'>Quantidade por Posto</caption>\n";
			$conteudo .= "<tr class='titulo_coluna' bgcolor='#596d9b'><th>Posto</th><th>Total</th><th>%</th></tr>\n";
			
			for($i=0;$i< $total4;$i++){
				$posto       = pg_result($res,$i,nome);
				$total_def    = pg_result($res,$i,def_total);
				$porcentagem  = ($total_def / $total)*100;
				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
				$conteudo.="<tr bgcolor='$cor'>
						<td align='left'>&nbsp;$posto</td>
						<td>$total_def</td>
						<td>".number_format($porcentagem,2, ',', '.')." %</td>
					</tr>\n";
			}
			$conteudo .= "</table>\n";
			$conteudo .= "</body></html>";
			fputs($fp, $conteudo);
			fclose($fp);
		
			if($total <= 500 and strlen($gera_xls)==0){
				echo $conteudo;
			}
			elseif($total > 500 and strlen($gera_xls)==0){
				echo '<center><font size="2">Relatório com mais de 500 registros ou com a opção "Gerar Excel" </font></center>';
			}

		?>
			<br />
			<input type='button' value='Download em Excel' onclick="window.location='xls/<?=$arquivo?>'">		
			

<?php
		}
		elseif($total > 5000){
			echo "<center><font size='2'>Relatório com mais de 5000 registros, escolha um intervalo menor entre os Números de Série</font></center>";
		}

		else{
			echo "<center>Nenhum Resultado Encontrado</center>";
		}
	}
	include "rodape.php";
?>

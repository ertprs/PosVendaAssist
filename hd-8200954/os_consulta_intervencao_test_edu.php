<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
include "funcoes.php";
include "cabecalho.php";
echo "<br>";
echo "<br>";

if (strlen($_POST["btn_acao"]) > 0)
	$btn_acao = strtoupper($_POST["btn_acao"]);
$meses = array(1 => traduz("janeiro",$con,$cook_idioma), traduz("fevereiro",$con,$cook_idioma), traduz("marco",$con,$cook_idioma), traduz("abril",$con,$cook_idioma), traduz("maio",$con,$cook_idioma), traduz("junho",$con,$cook_idioma), traduz("julho",$con,$cook_idioma), traduz("agosto",$con,$cook_idioma), traduz("setembro",$con,$cook_idioma), traduz("outubro",$con,$cook_idioma), traduz("novembro",$con,$cook_idioma), traduz("dezembro",$con,$cook_idioma));
if (strlen($btn_acao) > 0 ){
	$mes = trim (strtoupper ($_POST['mes']));
	if (strlen($mes)==0) $mes = trim(strtoupper($_GET['mes']));
	$ano = trim (strtoupper ($_POST['ano']));
	if (strlen($ano)==0) $ano = trim(strtoupper($_GET['ano']));
}
if (strlen($_POST["btn_gravar"]) > 0){
	$qtde        = trim($_POST['qtde']);
	$nota_fiscal = trim($_POST['nota_fiscal']);
	$data_envio  = trim($_POST['data_envio']);
	$pac         = trim($_POST['pac']);
	for ($i = 0; $i < $qtde; $i++){
		$os = trim($_POST["selecao_".$i]);
		if (strlen($os)>0){
			$sql = "UPDATE tbl_os_retorno
					SET nota_fiscal_envio         = '$nota_fiscal',
						data_nf_envio             = '$data_envio',
						numero_rastreamento_envio = '$pac'
					WHERE os=$os";
			$res = @pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);
			echo"<br>";
			$sql = "INSERT INTO tbl_os_status 
						(os,
						status_os,
						data,
						observacao) 
					values 
						($os,
						64,
						current_timestamp,
						'Produto com reparo realizado pela fábrica e recebido pelo posto')";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}
	}
}
?>

<style type="text/css">
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 8px;
	font-weight: normal;
	text-align: left;
	background: #F4F7FB;
}
.conteudo2 {
	font-family: Arial;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-align: left;
	background: #FFDCDC;
}
.conteudo_sac {
    font-family: Arial;
    FONT-SIZE: 10pt;
    text-align: left;
    background: #F4F7FB;
}
.titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 7px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
	background: #ced7e7;
	padding-right: 1ex;
	text-transform: uppercase;
}
.titulo2 {
	font-family: Arial;
	font-size: 7pt;
	text-align: center;
	color: #000000;
	background: #ced7e7;
	text-transform: uppercase;
}
.titulo3 {
	font-family: Arial;
	font-size: 10px;
	text-align: right;
	color: #000000;
	background: #ced7e7;
	height:16px;
	padding-left:5px;
	padding-right: 1ex;
	text-transform: uppercase;
}
.titulo4 {
	font-family: Arial;
	font-size: 10px;
	text-align: left;
	color: #000000;
	background: #ced7e7;
	height:16px;
	padding-left:0px;
}
.inpu{
	border:1px solid #666;
}
.inicio {
	font-family: Arial;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-align: left;
	color: #FFFFFF;
	padding-right: 1ex;
	text-transform: uppercase;
}
.justificativa{
	font-family: Arial;
	FONT-SIZE: 10px;
	background: #F4F7FB;
}
.Tabela{
	border:1px solid #d2e4fc;
	background-color:#485989;
}
.subtitulo {
	font-family: Verdana;
	FONT-SIZE: 9px;
	text-align: left;
	background: #F4F7FB;
	padding-left:5px
}
</style>

<?
if (strlen($msg) > 0){
	echo "<h1>$msg</h1>";
}
if (strlen($msg_erro) > 0){
	echo "<font face='arial' size='+1' color='#FF6633'><b>$msg_erro</b></font>";
}
if (strlen($mes) > 0) {
	$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
	$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
}
if ((strlen($btn_acao) > 0 AND strlen($msg) == 0)){
	$sql = "SELECT distinct tbl_os.os ,
				tbl_os.sua_os                                                   AS OS ,
				TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')                      AS ABERTURA,
				tbl_os.serie                                                    AS SERIE,
				tbl_os.consumidor_nome                                          AS CONSUMIDOR,
				tbl_os.nota_fiscal                                              AS NF,
				tbl_produto.descricao                                           AS PRODUTO,
				(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os 
				ORDER BY data DESC LIMIT 1) AS status_os
				FROM ( SELECT os
				FROM tbl_os
				JOIN tbl_os_extra USING (os)
				LEFT JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
				WHERE fabrica = $login_fabrica
				AND tbl_os.posto = $login_posto
				AND tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final') oss 
				JOIN tbl_os ON tbl_os.os = oss.os
				JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
				LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
				JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os LEFT JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.os = tbl_os.os
				LEFT JOIN tbl_posto_fabrica distrib ON tbl_os.digitacao_distribuidor = distrib.posto AND distrib.fabrica = $login_fabrica
				LEFT JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_produto.linha AND tbl_posto_linha.posto = tbl_os.posto
				LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
				JOIN tbl_os_retorno ON tbl_os_retorno.os = tbl_os.os
				WHERE tbl_os.fabrica = $login_fabrica
				AND tbl_os.posto = $login_posto
				AND (status_os NOT IN (65) or status_os IS NULL)
				AND tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'
				AND tbl_os_retorno.os IS NOT NULL
				AND tbl_os_retorno.nota_fiscal_envio IS NULL
				AND tbl_os_retorno.data_nf_envio IS NULL
				ORDER BY tbl_os.sua_os DESC";
	$res = pg_exec ($con,$sql);
	$msg_erro .= pg_errormessage($con);
	echo "<br>";
	echo "<br>";
	if (strlen ($msg_erro) == 0 and pg_numrows($res) <> 0){
		echo "<form name='frm_gravar' method='post' action='$PHP_SELF'>";
			echo "<table border=1 cellpadding=1 cellspacing=0 style=border-collapse: collapse bordercolor=#d2e4fc align=center width=600>";
				echo "<tr class=Titulo>";
					echo "<td><font size='2'>Os</td>";
					echo "<td><font size='2'>Serie</td>";
					echo "<td><font size='2'>Nota Fiscal</td>";
					echo "<td><font size='2'>Data de Abertura</td>";
					echo "<td><font size='2'>Nome do Consumidor</td>";
					echo "<td><font size='2'>Descrição do Produto</td>";
					if ($login_fabrica != 14){
						echo "<td><font size='2'>Selecionar</td>";
					}
				echo "</tr>";
				$total = pg_numrows($res);
				for ($i=0; $i<pg_numrows($res); $i++){
					$os          = trim(pg_result($res,$i,os));
					$serie       = trim(pg_result($res,$i,serie));
					$nf          = trim(pg_result($res,$i,nf));
					$abertura    = trim(pg_result($res,$i,abertura));
					$consumidor  = trim(pg_result($res,$i,consumidor));
					$produto     = trim(pg_result($res,$i,produto));
					if($cor =="#F1F4FA")
						$cor = "#F7F5F0";
					else
						$cor = "#F1F4FA";
					echo "<tr>";
						echo "<td bgcolor=$cor align=left nowrap><font size='2'><a href=os_press.php?os=$os target=_blank>$os</a></td>";
						echo "<td bgcolor=$cor align=left nowrap><font size='2'>$serie</td>";
						echo "<td bgcolor=$cor align=left nowrap><font size='2'>$nf</td>";
						echo "<td bgcolor=$cor align=left nowrap><font size='2'>$abertura</td>";
						echo "<td bgcolor=$cor align=left nowrap><font size='2'>$consumidor</td>";
						echo "<td bgcolor=$cor align=left nowrap><font size='2'>$produto</td>";
						if ($login_fabrica != 14){
							echo "<td bgcolor=$cor align=center nowrap><input type=checkbox name=\"selecao_$i\" id=\"selecao_$i\" value=\"$os\"> </td>";
						}
					echo "</tr>";
				}
			echo "</table>";
			if (strlen($total) >0){
			if ($login_fabrica != 14){?>
				<br>
				<br>
				<input class='inpu' type='hidden' name='qtde' value='<?=$total?>'>
				<table width='400' border="1" cellspacing="2" cellpadding="0" align='center' style='border-collapse: collapse' bordercolor='#485989'>
					<tr>
						<td class="inicio" background='admin/imagens_admin/azul.gif' height='19px'>
							&nbsp;<? echo traduz("envio.do.produto.a.fabrica",$con,$cook_idioma);?>
						</td>
					</tr>
					<tr>
						<td class="subtitulo" height='19px'>
							<? echo strtoupper(traduz("preencha.os.dados.do.envio.do.produto.a.fabrica",$con,$cook_idioma));?>
						</td>
					</tr>
					<tr>
						<td class="titulo3">
							<br>
							<? echo traduz("numero.da.nota.fiscal",$con,$cook_idioma);?>
								&nbsp;<input class="inpu" type="text" name="nota_fiscal" size="25" maxlength="6" value="<? echo $nota_fiscal_envio_p ?>">
							<br>
							<? echo  traduz("data.da.nota.fiscal.do.envio",$con,$cook_idioma);?> &nbsp;<input class="inpu" type="text" name="data_envio" size="25" maxlength="10" value="<? echo $data_envio_p ?>">
							<br>
							<? echo traduz("numero.o.objeto.pac",$con,$cook_idioma);?> &nbsp;<input class="inpu" type="text" name="pac" size="25" maxlength="13" value="<? echo $numero_rastreio_p ?>"> <br>
							Ex.: SS987654321
							<br>
							<center>
								<input type="hidden" name="btn_gravar" value="">
								<img src='imagens/btn_gravar.gif' onclick="javascript: 
									if (document.frm_gravar.nota_fiscal.value == ''){
										alert('ENTRE COM A NOTA FISCAL');
									}else{
										if (document.frm_gravar.data_envio.value == ''){
											alert('ENTRE COM A DATA DE ENVIO DA NOTA FISCAL');
										}else{
											if (document.frm_gravar.btn_gravar.value == ''){
												document.frm_gravar.btn_gravar.value='gravar' ; document.frm_gravar.submit() 
											}else{
												alert('<?fecho ("aguarde.submissao",$con,$cook_idioma);?>')
											}
										}
									}"
									ALT="<?fecho("gravar.dados",$con,$cook_idioma);?>" id='btn_gravar' border='0' style="cursor:pointer;">
							</center>
							<br>
						</td>
					</tr>
				</table>
				<br>
				<br>
			<?}
			}
		echo"</form>";
	}
}?>
<form name="frm_consulta" method="post" action="<?echo $PHP_SELF?>">
	<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
		<tr class="Titulo" height="30">
			<td align="center"><font size='2'>
				Selecione os parâmetros para a pesquisa
			</td>
		</tr>
	</table>
	<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
		<tr class="Conteudo" bgcolor="#D9E2EF" align='center'>
			<td colspan ='5' align = "center"><font size='2'>
				Data referente a digitação da O.S no site
			</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align = "left">
			<td width=80>
			</td>
			<td width=160><font size='2'>
				MÊS
			</td>
			<td>
			</td>
			<td><font size='2'>
				ANO
			</td>
			<td>
			</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" >
			<td>
			</td>
			<td>
				<select name="mes"  size="1" class="frm">
					<option value=''>
					</option>
				<?
					for ($i = 1 ; $i <= count($meses) ; $i++){
						echo "<option value='$i'";
							if ($mes == $i)
								echo " selected";
						echo ">" . $meses[$i] . "</option>";
					}
				?>
				</select>
			</td>
			<td>
			</td>
			<td>
				<select name="ano" size="1" class="frm">
					<option value=''>
					</option>
					<?
						for($i = date("Y"); $i > 2003; $i--){
							echo "<option value='$i'";
								if ($ano == $i)
									echo " selected";
							echo ">$i</option>";
						}
					?>
				</select>
			</td>
			<td>
			</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF">
			<td colspan='5' align='center'>
				<br>
				<input type="submit" name="btn_acao" value="pesquisar">
			</td>
		</tr>
	</table>
</form>
<?include "rodape.php"?>
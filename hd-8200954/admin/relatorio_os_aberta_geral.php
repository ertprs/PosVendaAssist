<?php

$admin_privilegios="auditoria";
$layout_menu = "auditoria";
$title = "RELATORIO GERAL DE ORDENS DE SERVIÇO";

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'autentica_admin.php';
include "cabecalho.php";
include "javascript_pesquisas.php";
include "javascript_calendario.php";

// teste erro Gustavo
//		background-color: #FF0000;
//		font-weight: bold;
//		color: #ffffff;
//		font-size: 12pt;
//		font-family: Arial;

$btngerar = $_GET['acao'];
if ($btngerar=="Pesquisar"){
	if(strlen($_POST["estado"]) > 0){
		$estado = trim($_POST["estado"]);
		$mostraMsgEstado = "<br>no ESTADO $estado";
	}

	if (($_GET["datainicial"]) && ($_GET["datafinal"]))
	{
		$data_inicial = $_GET["datainicial"];
		$data_final =  $_GET["datafinal"];
	}

	if (strlen(trim($_POST["codigo_posto"])) > 0) $codigo_posto  = trim($_POST["codigo_posto"]);
	if (strlen(trim($_GET["codigo_posto"])) > 0)  $codigo_posto  = trim($_GET["codigo_posto"]);

	if (strlen(trim($_POST["posto_nome"])) > 0)   $posto_nome    = trim($_POST["posto_nome"]);
	if (strlen(trim($_GET["posto_nome"])) > 0)    $posto_nome    = trim($_GET["posto_nome"]);

	if (strlen(trim($_POST["tipo_posto"])) > 0)   $tipo_posto    = trim($_POST["tipo_posto"]);
	if (strlen(trim($_GET["tipo_posto"])) > 0)    $tipo_posto    = trim($_GET["tipo_posto"]);

	if(strlen($data_inicial)==0 OR strlen($data_final)==0){
		$msg_erro = "Data Inválida";
	}
	
	if(strlen($msg_erro)==0){
		$dat = explode ("/", $data_inicial );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
	}
	if(strlen($msg_erro)==0){
		$dat = explode ("/", $data_final );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
	}
	if(strlen($msg_erro)==0){
		$d_ini = explode ("/", $data_inicial);//tira a barra
		$nova_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...


		$d_fim = explode ("/", $data_final);//tira a barra
		$nova_data_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

		if($nova_data_final < $nova_data_inicial){
			$msg_erro = "Data Inválida.";
		}


		//Fim Validação de Datas
	}

}

?>
<style>
	input
	{
		border: #6699CC 1px solid;
	}

	#pecanome
	{
		width: 528px;
	}

	.relcabecalho
	{
		background-color: #596D9B;
		border: 1px solid #d9e2ef;
		background:url('imagens_admin/azul.gif');
		color: #FFFFFF;
		height: 25px;
		font-weight: bold;
	}

	.relerro
	{
		color: #FF0000;
		font-size: 11pt;
		padding: 20px;
		background-color: #F7F7F7;
		text-align: center;
	}

	.rellinha0
	{
		background-color: #F1F4FA;
		border: solid 1px #d9e2ef;
	}

	.rellinha1
	{
		background-color: #F7F5F0;
		border: solid 1px #d9e2ef;
	}

	.relinstrucoes
	{
		text-align: center;
		font-weight: bold;
		color: #FFFFFF;
		background:url('imagens_admin/azul.gif');
		height:25px;
	}

	.relopcoes
	{
		text-align: center;
		background-color: #DBE5F5;
		height: 30px;
		text-align: center;
		width: 696px;
	}

	.relprincipal
	{
		border-collapse: collapse;
		font-size: 8pt;
		font-family: Arial;
		font-weight: normal;
		border: #d9e2ef 1px solid;
	}

	.reltitulo
	{
		text-align: center;
		background-color: #596D9B;
		height: 30px;
		font-weight: bold;
		color: #FFFFFF;
		background:url('imagens_admin/azul.gif');
	}

	.rellink
	{
		text-decoration: none;
		font-weight: normal;
		color: #000000;
	}

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

.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
}
</style>

<script language="javascript">

$(function(){
	$('#datainicial').datePicker({startDate:'01/01/2000'});
	$('#datafinal').datePicker({startDate:'01/01/2000'});
	$("#datainicial").maskedinput("99/99/9999");
	$("#datafinal").maskedinput("99/99/9999");
});

</script>


	<form method='get' name='frm_relatorio'>
	<table width="700" border="0"  align='center' class='formulario'>
		<?php if(strlen($msg_erro)>0){ ?>
				<tr class="msg_erro"><td colspan="4"><?php echo $msg_erro; ?></td></tr>
		<?php } ?>
		<tr class='titulo_tabela'>
			<td colspan='4'>
				Parâmetros de Pesquisa
			</td>
		</tr>
		<tr>
			<td colspan="4">&nbsp;</td>
		</tr>
		<tr>
			<td align=right>
				Data Inicial:
			</td>
			<td>
				<input type='text' id='datainicial' name='datainicial' size=10 value='<?php echo $_GET["datainicial"];?>' class='frm'>
			</td>
			<td align=right>
				Data Final:
			</td>
			<td>
				<input type=text id='datafinal' name='datafinal' size=10 value='<?php echo $_GET["datafinal"];?>' class='frm'>
			</td>
		</tr>

		<tr>
		<td align=right> Código Posto: </td>
		<td align=left>
			<input type="text" name="codigo_posto" size="15" <? if ($login_fabrica == 1) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'codigo');" <? } ?> value="<? echo $codigo_posto ?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'codigo')">
		</td>
		<td align=right> Nome do Posto: </td>
		<td align=left nowrap>
			<input type="text" name="posto_nome" size="30" <? if ($login_fabrica == 1) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'nome');" <? } ?> value="<?echo $posto_nome?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'nome')">
		</td>
		</tr>
		<tr>
			<td align=right> Estado: </td>
			<td align=left>
				<select name="estado" class='frm'>
					<option value=""   <? if (strlen($estado) == 0)    echo " selected "; ?>>TODOS OS ESTADOS</option>
					<option value="AC" <? if ($estado == "AC") echo " selected "; ?>>AC - Acre</option>
					<option value="AL" <? if ($estado == "AL") echo " selected "; ?>>AL - Alagoas</option>
					<option value="AM" <? if ($estado == "AM") echo " selected "; ?>>AM - Amazonas</option>
					<option value="AP" <? if ($estado == "AP") echo " selected "; ?>>AP - Amapá</option>
					<option value="BA" <? if ($estado == "BA") echo " selected "; ?>>BA - Bahia</option>
					<option value="CE" <? if ($estado == "CE") echo " selected "; ?>>CE - Ceará</option>
					<option value="DF" <? if ($estado == "DF") echo " selected "; ?>>DF - Distrito Federal</option>
					<option value="ES" <? if ($estado == "ES") echo " selected "; ?>>ES - Espírito Santo</option>
					<option value="GO" <? if ($estado == "GO") echo " selected "; ?>>GO - Goiás</option>
					<option value="MA" <? if ($estado == "MA") echo " selected "; ?>>MA - Maranhão</option>
					<option value="MG" <? if ($estado == "MG") echo " selected "; ?>>MG - Minas Gerais</option>
					<option value="MS" <? if ($estado == "MS") echo " selected "; ?>>MS - Mato Grosso do Sul</option>
					<option value="MT" <? if ($estado == "MT") echo " selected "; ?>>MT - Mato Grosso</option>
					<option value="PA" <? if ($estado == "PA") echo " selected "; ?>>PA - Pará</option>
					<option value="PB" <? if ($estado == "PB") echo " selected "; ?>>PB - Paraíba</option>
					<option value="PE" <? if ($estado == "PE") echo " selected "; ?>>PE - Pernambuco</option>
					<option value="PI" <? if ($estado == "PI") echo " selected "; ?>>PI - Piauí</option>
					<option value="PR" <? if ($estado == "PR") echo " selected "; ?>>PR - Paraná</option>
					<option value="RJ" <? if ($estado == "RJ") echo " selected "; ?>>RJ - Rio de Janeiro</option>
					<option value="RN" <? if ($estado == "RN") echo " selected "; ?>>RN - Rio Grande do Norte</option>
					<option value="RO" <? if ($estado == "RO") echo " selected "; ?>>RO - Rondônia</option>
					<option value="RR" <? if ($estado == "RR") echo " selected "; ?>>RR - Roraima</option>
					<option value="RS" <? if ($estado == "RS") echo " selected "; ?>>RS - Rio Grande do Sul</option>
					<option value="SC" <? if ($estado == "SC") echo " selected "; ?>>SC - Santa Catarina</option>
					<option value="SE" <? if ($estado == "SE") echo " selected "; ?>>SE - Sergipe</option>
					<option value="SP" <? if ($estado == "SP") echo " selected "; ?>>SP - São Paulo</option>
					<option value="TO" <? if ($estado == "TO") echo " selected "; ?>>TO - Tocantins</option>
				</select>
			</td>

			<td>Tipo Posto:</td>
			<td align='left'>
				<select name="tipo_posto" class='frm'>
					<option></option>
					<?
						$sqlTP = "SELECT  tbl_tipo_posto.tipo_posto,
										tbl_tipo_posto.descricao
								FROM tbl_tipo_posto
								WHERE fabrica = $login_fabrica
								AND   ativo is true
								order by tbl_tipo_posto.descricao";
								#$teste = $sqlTP;
						$resTP = pg_exec($con, $sqlTP);

						if(pg_numrows($resTP)>0){
							for($i=0; $i<pg_numrows($resTP); $i++){
								$xtipo_posto = pg_result($resTP,$i,tipo_posto);
								$xdescricao  = pg_result($resTP,$i,descricao);
								echo "<option value='$xtipo_posto'";
								if($xtipo_posto==$tipo_posto) echo "selected";
								echo ">$xdescricao</option>";
							}
						}
					?>
				</select>
				<? #echo $teste;?>
			</td>
		</tr>
		<tr>
			<td colspan="4">&nbsp;</td>
		</tr>
		<tr>
			<td colspan='4'>
				<input type="hidden" name="acao" value="">
				<input type="button" style="background:url(imagens_admin/btn_pesquisar_400.gif); width:400px;cursor:pointer;" value="&nbsp;" onclick="javascript: document.frm_relatorio.acao.value='Pesquisar'; document.frm_relatorio.submit();" alt="Preencha as opções e clique aqui para pesquisar" border="0" />
			</td>
		</tr>

	</table>
	<br>
	</form>

<?php
if (strlen($codigo_posto) > 0 or strlen($tipo_posto) > 0 or (strlen($data_inicial) > 0 AND strlen($data_final) > 0) AND strlen($msg_erro)==0) {
	$sql =	"SELECT tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome                ,
					tbl_posto.posto
			FROM tbl_posto
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
									AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_posto_fabrica.fabrica = $login_fabrica
			AND   tbl_posto_fabrica.codigo_posto = '$codigo_posto';";
	$res = pg_exec($con,$sql);
	if (pg_numrows($res) > 0) {
		$posto        = pg_result($res,0,posto);
		$codigo_posto = pg_result($res,0,codigo_posto);
		$posto_nome   = pg_result($res,0,nome);
	}else{
		$msg .= " Posto não encontrado. ";
	}

$cond1=' AND 1=1';
if (strlen($posto) > 0){
	$cond1=" AND tbl_posto.posto=$posto";
	$cond11=" AND tbl_os.posto=$posto";
}

$cond2=' AND 1=1';
if (strlen($estado) > 0){
	$cond2=" AND tbl_Posto.estado='$estado'";
}

$cond3=' AND 1=1';
if (strlen($tipo_posto) > 0){
	$cond3=" AND tbl_posto_fabrica.tipo_posto='$tipo_posto'";
}

	$sql = "
	SELECT posto,os
	INTO TEMP temp_os_aberta_$login_admin
	from tbl_os
	WHERE tbl_os.fabrica=$login_fabrica
	AND tbl_os.excluida is not TRUE
	$cond11
	AND tbl_os.data_abertura BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59';

	create index temp_os_aberta_posto_os_$login_admin on temp_os_aberta_$login_admin(posto,os);

	SELECT
	tbl_posto_fabrica.codigo_posto,
	tbl_posto.nome,
	tbl_posto.cnpj,
	tbl_posto.cidade,
	tbl_posto.estado,
	(SELECT COUNT(os)
	FROM temp_os_aberta_$login_admin
	WHERE temp_os_aberta_$login_admin.posto=tbl_posto.posto
	) AS num_os,
	tbl_tipo_posto.descricao AS tipo_posto_descricao,
	tbl_posto_fabrica.credenciamento
	FROM
	tbl_posto
	JOIN tbl_posto_fabrica ON tbl_posto.posto=tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica=$login_fabrica
	JOIN tbl_tipo_posto ON tbl_posto_fabrica.tipo_posto=tbl_tipo_posto.tipo_posto
	WHERE tbl_posto_fabrica.fabrica=$login_fabrica
	$cond1
	$cond2
	$cond3
	";
	#echo nl2br($sql);

	$res = pg_exec($con, $sql);

	$qtde = pg_numrows($res);

	//************************* HTML DA TELA *************************//
	$colunas = 4;

	if ($res)
	{
		echo "
		<table  border=0 align=center class='tabela' cellspacing='1'>
			<tr class='titulo_coluna'>
				<td width=100>
					Código
				</td>
				<td width=300>
					Nome
				</td>
				<td width=150>
					CNPJ
				</td>
				<td width=200>
					Cidade
				</td>
				<td width=50>
					Estado
				</td>
				<td width=100>
					Qtde OS Abertas
				</td>
				<td width=120>
					Tipo do Posto
				</td>
				<td width=120>
					Status do Posto
				</td>
			</tr>";

		for($i = 0; $i < pg_numrows($res); $i++)
		{

			$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";
			
			$codigo_posto	= pg_result($res, $i, codigo_posto);
			$nome			= pg_result($res, $i, nome);
			$cnpj			= pg_result($res, $i, cnpj);
			$cidade			= pg_result($res, $i, cidade);
			$estado			= pg_result($res, $i, estado);
			$num_os			= pg_result($res, $i, num_os);
			$tipo_posto		= pg_result($res, $i, tipo_posto_descricao);
			$credenciamento	= pg_result($res, $i, credenciamento);

			echo "
			<tr bgcolor='$cor'>
				<td>
				$codigo_posto
				</td>
				<td align='left'>
				$nome
				</td>
				<td>
				$cnpj
				</td>
				<td>
				$cidade
				</td>
				<td>
				$estado
				</td>
				<td>
				$num_os
				</td>
				<td>
				$tipo_posto
				</td>
				<td>
				$credenciamento
				</td>
			</tr>";
		}

		echo "
		</table>";

		//************************* FIM HTML DA TELA *************************//
	}
	else
	{
		echo "
		<div class=relerro>
		Nenhuma acesso encontrado para as datas informadas
		</div>";
	}
}
include "rodape.php";

?>
